<?php 
function asignarEmpresa($idEmpresa, $idObjetoDestino = ""){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = ($idEmpresa != "") ? $idEmpresa : $_SESSION['idEmpresaUsuarioSysGts'];
	
	$query = sprintf("SELECT id_empresa_reg, id_empresa_suc, CONCAT_WS(' - ',nombre_empresa,nombre_empresa_suc) AS nombre_empresa, sucursal 
			FROM vw_iv_empresas_sucursales 
		WHERE id_empresa_reg = %s",
	valTpDato($idEmpresa,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rows = mysql_fetch_array($rs);
	
	switch($idObjetoDestino){
		default: 
			$inputTextId = "textIdEmpresaBus";
			$inputText = "textEmpresaBus";
		break;
	}
	$objResponse->assign($inputTextId,"value",$rows['id_empresa_reg']);
	$objResponse->assign($inputText,"value",sprintf("%s (%s)",$rows['nombre_empresa'],$rows['sucursal']));

	return $objResponse;
}

function asignarCliente($idCLiente){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT cliente_emp.id_empresa, cliente.id, CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente, cliente.credito
		FROM cj_cc_cliente cliente
			INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)  
		WHERE status = 'Activo' AND cliente.id = %s",
	valTpDato($idCLiente, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rows = mysql_fetch_array($rs);

	$objResponse->assign("idClienteHidd","value",$rows['id']);
	$objResponse->assign("textNombreCliente","value",$rows['nombre_cliente']); 
	
	$objResponse->script("byId('butCerraListCliente').click();");

	return $objResponse;
}

//TOMA EL VALOS DEL CAMPO PARA HACER LA BUSQUEDA
function buscarCliente($frmBuscarCliente){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$_SESSION['idEmpresaUsuarioSysGts'],
		$frmBuscarCliente['textCriterio'],
		$frmBuscarCliente['textTipoActividad']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

//BUSCA LOS CLIENTES DE SERVISIO
function buscarClienteServicio($formBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s", 
	$formBuscar['LisMeses'],
	$formBuscar['textCriterio']);
	
	$objResponse->loadCommands(ListServicioPendiente(0, "", "DESC", $valBusq));
	
	return $objResponse;
}

function cargarDatos($fromTipoEquipo,$idIntegrante,$horaAsignacion,$idActividaEjecucion){
	$objResponse = new xajaxResponse();

	if($idActividaEjecucion == 0){
		$sqlIntegranten = sprintf("SELECT id_integrante_equipo,
				CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_apellido_empleado
			FROM crm_integrantes_equipos
				INNER JOIN pg_empleado ON crm_integrantes_equipos.id_empleado= pg_empleado.id_empleado  
			WHERE id_integrante_equipo = %s", 
			valTpDato($idIntegrante, "int"));
	}else{
		$sqlIntegranten = sprintf("SELECT id_actividad_ejecucion,
			crm_actividades_ejecucion.id_integrante_equipo,
			crm_integrantes_equipos.id_empleado,
			CONCAT_WS(' ', nombre_empleado, pg_empleado.apellido) AS nombre_apellido_empleado,
			crm_actividades_ejecucion.id,
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_apellido_cliente,
			crm_actividades_ejecucion.id_actividad,
			nombre_actividad,
			tipo_finalizacion,
			notas,
			estatus   
		FROM crm_actividades_ejecucion
			INNER JOIN crm_integrantes_equipos ON crm_integrantes_equipos.id_integrante_equipo = crm_actividades_ejecucion.id_integrante_equipo
			INNER JOIN pg_empleado ON pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado
			INNER JOIN crm_actividad ON  crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			INNER JOIN cj_cc_cliente ON cj_cc_cliente.id = crm_actividades_ejecucion.id
		WHERE id_actividad_ejecucion = %s", 
			valTpDato($idActividaEjecucion, "int"));
	}

	$rsIntegrante = mysql_query($sqlIntegranten);
	if(!$rsIntegrante)$objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowIntegreante = mysql_fetch_array($rsIntegrante);

	//LLENA LOS CAMPOS CON LOS DATOS DE LA TABLA
	switch($fromTipoEquipo['comboxTipoEquipo']){
		case "Postventa": $objResponse->assign("tdNombreCliente","innerHTML","Nombre del Cliente:"); break;
		case "Ventas": $objResponse->assign("tdNombreCliente","innerHTML","Nombre del Prospecto:"); break;
	}

	$objResponse->assign("txtTipoActividad","value", $fromTipoEquipo['comboxTipoEquipo']);
	$objResponse->assign("nombreVendedor","value", utf8_encode($rowIntegreante['nombre_apellido_empleado']));
	$objResponse->assign("textFechAsignacion","value", $fromTipoEquipo['fechaSelectEquipo']);
	$objResponse->assign("textHoraAsignacion", "value", date("h:i",strtotime($horaAsignacion))); // HORA MOSTRADA
	$objResponse->assign("textHoraAsignacion2","value", $horaAsignacion); // HORA EN CAMPO OCULTO 
	$objResponse->assign("textIdIntegrante","value", $rowIntegreante['id_integrante_equipo']);  
	$objResponse->assign("hddActEjecucion","value", $idActividaEjecucion);

	$objResponse->loadCommands(cargarLstActivida($fromTipoEquipo['comboxTipoEquipo'],"tdListActividad",$rowIntegreante['id_actividad']));
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", "||".$fromTipoEquipo['comboxTipoEquipo']));
	
	$objResponse->assign("idClienteHidd","value", $rowIntegreante['id']);
	$objResponse->assign("textNombreCliente","value", utf8_encode($rowIntegreante['nombre_apellido_cliente']));
	$objResponse->assign("comboxEstadoActAgenda","value", $rowIntegreante['tipo_finalizacion']);
	$objResponse->assign("textNotaCliente","value", utf8_decode($rowIntegreante['notas']));
	
	// SI EXISTE UNA ACTIVIDAD ASIGNADA
	if($idActividaEjecucion > 0){
		$objResponse->script("acciones('listCliente','disabled',true)");
		if($rowIntegreante['estatus'] == 1){
			$objResponse->script("acciones('botEliminar','show')");
			$objResponse->script("acciones('btnGuardar','show')");
		}else{
			$objResponse->script("acciones('botEliminar','hide')");
			$objResponse->script("acciones('btnGuardar','hide')");
		}
		$objResponse->script("acciones('trTipoFinalizacion','show')");
	} else{
		$objResponse->script("acciones('listCliente','disabled',false)");
		$objResponse->script("acciones('botEliminar','hide')");
		$objResponse->script("acciones('btnGuardar','show')");
		$objResponse->script("acciones('trTipoFinalizacion','hide')");
	}
	
	return $objResponse;
}

//COMBO LISTA LAS ACTIVIDADES
function cargarLstActivida($tipoEquipo, $idObjDestino, $idActividad = "", $option ="Seleccione"){
	$objResponse = new xajaxResponse();

		if($idObjDestino == "tdListTipoActividaGrafico"){
			$sqlBusq2 = sprintf("LEFT JOIN crm_actividades_ejecucion ON crm_actividad.id_actividad= crm_actividades_ejecucion.id_actividad");
			
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
			
		} else {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("id_empresa = %s",
				valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato(1, "int"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo = %s",
			valTpDato($tipoEquipo, "text"));

		$query = sprintf("SELECT * FROM crm_actividad %s %s",$sqlBusq2,$sqlBusq);
		
		$sqlOrd = ($idObjDestino == "tdListTipoActividaGrafico") ? " GROUP BY crm_actividades_ejecucion.id_actividad" : "ORDER BY posicion_actividad";

		$queryFin = sprintf("%s %s",$query,$sqlOrd);
		$rs = mysql_query($queryFin);
		if (!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryFin);
		while($rowAct = mysql_fetch_array($rs)){
			$selected = ($idActividad == $rowAct['id_actividad']) ? "selected=\"selected\"" : "";
			$htmlOption.= sprintf("<option value=\"%s\" %s >%s. %s</option>",
				$rowAct['id_actividad'],$selected,$rowAct['posicion_actividad'],utf8_encode($rowAct['nombre_actividad']));
		}
		$html = "<select id=\"listActividad\" name=\"listActividad\" class='inputHabilitado'>";
			$html .= sprintf("<option value=\"\">[ %s ]</option>",$option);
			$html .= $htmlOption;
		$html.="</select>";
		
		$objResponse->assign($idObjDestino, 'innerHTML', $html);
		
	return $objResponse;
}
//FUNCTION PARA GENERAR EL COMBOX DE LAS HORA
function cargarlistHora($datosServicio){
	$objResponse = new xajaxResponse();
	
	$fechaSeleccionada = date('Y-m-d', strtotime($datosServicio['fechaAsignacion2']));
		
	$sql = sprintf("SELECT crm_equipo.id_equipo, crm_integrantes_equipos.id_integrante_equipo, fecha_asignacion
			FROM crm_equipo
			LEFT JOIN crm_integrantes_equipos ON crm_integrantes_equipos.id_equipo = crm_equipo.id_equipo
			LEFT JOIN crm_actividades_ejecucion ON crm_actividades_ejecucion.id_integrante_equipo = crm_integrantes_equipos.id_integrante_equipo
		WHERE DATE(fecha_asignacion) = %s AND crm_equipo.id_equipo = %s AND crm_actividades_ejecucion.id_integrante_equipo = %s;",
	valTpDato(date('Y-m-d', strtotime($datosServicio['fechaAsignacion2'])),"text"),
	valTpDato($datosServicio['listEquipoServicio'], "int"),
	valTpDato($datosServicio['listIntegrantesServicio'], "int"));	
	$query = mysql_query($sql);
	
	if (!$query) return $objResponse->alert("Error: ".mysql_error(). "\n\nLine:".__LINE__);
	while($rows = mysql_fetch_array($query)){
		$fechaAsignacion = $rows["fecha_asignacion"];
		$arrayTiempo[] = date('H:i',strtotime($fechaAsignacion));
		$idActividadEjecucion = $rows["id_actividad_ejecucion"];
	}

	$horaInicio= date("H:i",strtotime("07:00"));
	$interval = 30;
	$horaFin =  date("H:i",strtotime("19:00"));
	
	$arrayHoras[] = $horaInicio;
	
	$resta = abs(date("H", strtotime($horaInicio)) - date("H", strtotime($horaFin)));
	$aux = 0;
	while ($arrayHoras[$aux] != $horaFin){
		$arrayHoras[$aux+1] = date("H:i", strtotime("+ ".$interval." minutes", strtotime($arrayHoras[$aux])));
		$aux++;
	}
	
	if (isset($arrayTiempo)){
		$horasOption = array_diff($arrayHoras, $arrayTiempo); //ELIMINA LOS ARRAY IGUALES 
	} else {
		$horasOption = $arrayHoras;
	}
	
	$selectH .= "<select id=\"horaSelect\" name=\"horaSelect\" class=\"inputHabilitado\">";
	$selectH .= "<option value=\"\">[ Seleccionar ]</option>";
	foreach($horasOption as $fechaLibre){
		$selectH .= sprintf("<option value=\"%s\">%s</option>",
				$fechaLibre,
				date("h:i A",strtotime($fechaLibre)));
	}
	$selectH .= "</select>";
	
	$objResponse->assign("tdHoraAsignacion","innerHTML",$selectH);
	
	return $objResponse;
}

function cargarLstEquipo($tipoEduipo,$idObjDestino,$option = "Seleccione"){
	$objResponse = new xajaxResponse();

	$tipoEduipo = (is_array($tipoEduipo)) ? $tipoEduipo['comboxTipoEquipo']: $tipoEduipo;
	$sql =sprintf("SELECT DISTINCT crm_integrantes_equipos.id_equipo, 
			nombre_equipo, 
			crm_equipo.activo,
			tipo_equipo
		FROM crm_integrantes_equipos
			LEFT JOIN crm_equipo ON crm_equipo.id_equipo = crm_integrantes_equipos.id_equipo
		WHERE crm_equipo.activo = %s AND 
		crm_integrantes_equipos.activo = %s AND 
		tipo_equipo = %s AND id_empresa = %s",
	valTpDato(1, "int"), 
	valTpDato(1, "int"), 
	valTpDato($tipoEduipo, "text"),
	valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	$rs = mysql_query($sql);
	if(!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
//$objResponse->alert($sql);	
	if($idObjDestino == "tdListEquipo"){
		$onchange = "onchange = \"byId('btnBuscarAgenda').click();\"";
	} elseif($idObjDestino == "tdListEquipoGrafico"){
		$onchange = "onchange=\"xajax_cargarLstIntegranteEquipo('tdListIntegrantesGrafico',this.value,'Todo')\"";
	} elseif($idObjDestino == "tdEquipoServicio"){
		$onchange = "onchange=\"xajax_cargarLstIntegranteEquipo('tdListIntegrantesServicio',this.value)\"";
	}
	$idobjeto =($idObjDestino == "tdEquipoServicio")?"comboListEquipoS":"comboListEquipo";
	
	$html = sprintf("<select id =\"%s\"  name =\"%s\" class=\"inputHabilitado\" %s>",$idobjeto,$idobjeto,$onchange);
	$html .= ($idObjDestino != "tdListEquipoGrafico2")? sprintf("<option value=\"\">[ %s ]</option>",$option):"";
		
	while($rows = mysql_fetch_array($rs)){
		$html .= sprintf("<option value=\"%s\">%s</option>",
		$rows['id_equipo'], $rows['nombre_equipo']);	
	}

	$html .= "</select>";
	$objResponse->assign($idObjDestino,"innerHTML",$html);
	
	return $objResponse;
}
function cargarLstTipoEquipo(){
	$objResponse = new xajaxResponse();

	$result = buscaTipo();
	if($result[0] != true){
		return $objResponse->alert($result[1]);	
	}
	if($result[1] != NULL){
		$tipo = ($result[1] == "Ventas") ? $objResponse->script("acciones('tabsServicio','hide','');") : $objResponse->script("acciones('tabsServicio','show','');");
		$onchange = sprintf("onchange=\"selectedOption(this.id,'".$result[1]."');\"");
		$class = "class=\"inputInicial\"";
		$objResponse->loadCommands(cargarLstEquipo($result[1],'tdListEquipo'));
	}else{
		$onchange = "onchange=\"xajax_cargarLstEquipo(this.value,'tdListEquipo')\"";
		$class = "class=\"inputHabilitado\"";
	}

	$sql = sprintf("SHOW COLUMNS FROM crm_equipo WHERE field= %s",
		valTpDato("tipo_equipo", "text"));
	$rs = mysql_query($sql);
	if(!$rs)$objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = sprintf("<select id=\"comboxTipoEquipo\" name=\"comboxTipoEquipo\" %s %s>",$class,$onchange);
	$html .= "<option value=\"\">[ Seleccione ]</option>";
	
	while ($rows = mysql_fetch_row($rs)) {
		foreach(explode("','",substr($rows[1],6,-2)) as $inde => $valor) {
			$checked = ($result[1] == $valor) ? "selected='selected'" : "";
			$html .= sprintf("<option value= %s %s>%s</option>", 
			valTpDato($valor,"text"), $checked, $valor);
		} 
	}
	
	$html .= "</select>";
	
	$objResponse->assign("tdTipoEquipo","innerHTML",$html);

	return $objResponse;	
}

function cargarLstIntegranteEquipo($idObjDestino, $idEquipo = "", $option = "Seleccione"){
	$objResponse = new xajaxResponse();

	if($idEquipo != ""){
		$sqlEquipo = sprintf("AND id_equipo = %s ",
		valTpDato($idEquipo,"int"));
	}
		
	$sql = sprintf("SELECT id_integrante_equipo,
				CONCAT(nombre_empleado,' ', apellido) AS nombre_apellido_integrante,
				crm_integrantes_equipos.activo
			FROM crm_integrantes_equipos
				LEFT JOIN pg_empleado ON  pg_empleado.id_empleado= crm_integrantes_equipos.id_empleado
					WHERE crm_integrantes_equipos.activo = %s %s;",
	valTpDato(1,"text"),
	$sqlEquipo);
	$rs = mysql_query($sql);
	if(!$rs)$objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$html = sprintf("<select id=\"comboListIntegrante\" name=\"comboListIntegrante\" class=\"inputHabilitado\" %s>",$onchange);
	$html .= sprintf("<option value=\"\">[ %s ]</option>",$option);
		
	while($rows = mysql_fetch_array($rs)){
		$html .= sprintf("<option value=\"%s\">%s</option>", $rows['id_integrante_equipo'], 
		utf8_encode($rows['nombre_apellido_integrante']));	
	}

	$html .= "</select>";
	$objResponse->assign($idObjDestino,"innerHTML",$html);
	
	return $objResponse;	
}

//FUNCION QUE GENERA EL COMBO LIS DE LOS MESE EN LA PESTAÑA SERVICIO
function cargarLstMeses($meseBus = ""){
	$objResponse = new xajaxResponse();
	
	$html = "<select id=\"LisMeses\" name=\"LisMeses\" onchange=\"byId('butBuscarServicio').click();\" class=\"inputHabilitado\">";	
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		$mes = 0;
		while ($mes <= 11) {
			$mes++;
			$texto = ($mes == 1) ? "Mes":"Meses";
			$selected = ($mes == $meseBus) ? "selected='selected'" : "";
			$html .= sprintf('<option value="%s" %s>%s - %s	</option>', $mes, $selected, $mes, $texto);
		}
	$html .= "</select>";
	
	$objResponse->assign("tdConboListMese","innerHTML",$html);	
	
	return $objResponse;
}	

//PARA CALCULAR EL CUMPLEAÑO Y MOSTRAR UNA IMAGEN
function cumpleano(){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT
		cj_cc_cliente.id, CONCAT_WS(' ',nombre,apellido) AS nombre_apellido_cliente, fecha_nacimiento, telf, correo
	FROM cj_cc_cliente
		LEFT JOIN crm_perfil_prospecto ON crm_perfil_prospecto.id = cj_cc_cliente.id
	WHERE MONTH(fecha_nacimiento) = MONTH(CURDATE())
		AND DAY(fecha_nacimiento) = DAY(CURDATE());");
	$query = mysql_query($sql);
	if (!$query) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	while($rows = mysql_fetch_array($query)){
		$objResponse->script(sprintf("acciones('cumpleano%s','show','')",$rows['id']));
	}
	return $objResponse;
}
	
function eliminarActivida($datosFormEliminar) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","eliminar")) { return $objResponse; }

	$deleteActividad = sprintf("DELETE FROM crm_actividades_ejecucion WHERE id_actividad_ejecucion = %s",
		$datosFormEliminar['hddActEjecucion']);
	mysql_query("SET NAMES 'utf8'");
	$queryActividad = mysql_query($deleteActividad);
	if (!$queryActividad) return $objResponse->alert(mysql_query(). "\n\nLinea: ".__LINE__);
	
	$objResponse->alert("Se elimino la actividad");
	$objResponse->script("byId('butCancelarAsignacion').click();");//LLAMA AL BOTON PARA CERRAR LA IMG D FONDO
	$objResponse->script("xajax_selectIntegrante(document.getElementById('fechaSelectEquipo').value, document.getElementById('comboListEquipo').value)");
	

	return $objResponse;
}

function eliminarTrRango($formCargarKm){
	$objResponse = new xajaxResponse();

	if(isset($formCargarKm['checkTr'])){
		foreach($formCargarKm['checkTr'] as $indice => $valor)	{
			$objResponse->script(sprintf("
				fila = document.getElementById('trItemsRango%s');
				padre = fila.parentNode;
				padre.removeChild(fila);
			",
			valTpDato($valor,"int"))
	); 
		}
	}
	
	return $objResponse;
}

//CARGA LOS DATOS DEL FORMULARIO document.getElementById('radioCorreo_no').checked = true
function formCargarKm($nomObjeto, $idRegistroPlaca) {
	$objResponse = new xajaxResponse();

	// CONSULTA LOS DATOS DE LA UNIDAD
	$sql = sprintf("SELECT MAX(id_recepcion) AS ultimo_id_recepcion, sa_recepcion.fecha_entrada, MAX(sa_recepcion.kilometraje) AS Km, 
				sa_cita.id_registro_placas, sa_cita.id_cliente_contacto, placa, nom_marca, nom_modelo
			FROM sa_recepcion
				LEFT JOIN sa_cita ON sa_cita.id_cita = sa_recepcion.id_cita
				LEFT JOIN en_registro_placas ON en_registro_placas.id_registro_placas = sa_cita.id_registro_placas
				LEFT JOIN an_uni_bas ON an_uni_bas.id_uni_bas = en_registro_placas.id_unidad_basica
				LEFT JOIN an_marca ON id_marca = mar_uni_bas
				LEFT JOIN an_modelo ON id_modelo = mod_uni_bas
			WHERE sa_cita.id_registro_placas = %s GROUP BY placa",
		valTpDato($idRegistroPlaca,"int"));
	$query = mysql_query($sql);
	if (!$query) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rows = mysql_fetch_array($query);
	
	// CONSULTA EL RANGO DE KILOMETRAGE ASOCIADO A ESA MARCA
	$queryRango = sprintf("SELECT id_revision, mese, rango_km_i, rango_km_f, crm_revision.id_marca, nom_marca, activo, id_empresa
		FROM crm_revision
			LEFT JOIN an_marca ON an_marca.id_marca = crm_revision.id_marca
		WHERE nom_marca = %s AND activo = %s",
	valTpDato($rows['nom_marca'], "text"),
	valTpDato(1,"int"));

	$rsRango =  mysql_query($queryRango);
	if (!$rsRango) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowsRango = mysql_fetch_array($rsRango);
		
	if (!mysql_num_rows($rsRango)) { return $objResponse->alert("Esta marca no posee un rango de kilometraje"); }

	$objResponse->script("
		openImg(byId('divFlotante3'))
		document.forms['frmPuesto'].reset();
		byId('idRegistroPLaca').value = '';
		byId('idClienteCotacto').value = '';
		byId('comboxTipoEquipo').value = '';
	");

	// CONSULTA LOS SERVICIO OFRECIDO A ESTA UNIDAD
	$querySerOfrecido = sprintf("SELECT id_registro_placas, servicio_ofrecido, servicio_realizado_afuera, enviar_email
		FROM crm_ofrecer_servicio
			WHERE id_registro_placas = %s ",
	valTpDato($idRegistroPlaca,"int"));
	$rsSerOfrecido = mysql_query($querySerOfrecido);
	if (!$rsSerOfrecido) return $objResponse->alert(mysql_error."\nError Nro: ".mysql_errno()."\nLine: ". __LINE__);
	while($rowsSerOfrecido = mysql_fetch_array($rsSerOfrecido)){
		$servicioOfrecido[$rowsSerOfrecido['servicio_ofrecido']] = $rowsSerOfrecido['servicio_ofrecido'];
		$servicioRealizadoAfuera[$rowsSerOfrecido['servicio_realizado_afuera']] = $rowsSerOfrecido['servicio_realizado_afuera'];
		$enviarEmail[$rowsSerOfrecido['enviar_email']] = $rowsSerOfrecido['enviar_email'];
	}
	// PARA GENERAR EL RANGO DE KM DEFINIDO EN LA CONFIGURCION

	for ($rango = $rowsRango['rango_km_i']; $rango <= $rowsRango['rango_km_f']; $rango += $rowsRango['rango_km_i']){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$disabled = ($kilometraje >= $rango) ? "disabled = 'disabled';" : "";
		
		//valida check rangoKm
		$checked = (isset($servicioOfrecido) && $servicioOfrecido[$rango] == $rango) ? "checked = 'checked' disabled='disabled';" : "";
		
		//valida chek de servicio afuera
		$checkedSA = (isset($servicioRealizadoAfuera) && $servicioRealizadoAfuera[$rango] == $rango) ? "checked = 'checked' disabled='disabled';" : "";

		//valida chek de enviar email		
		$checkedE = (isset($enviarEmail) && $enviarEmail[$rango] == $rango) ? "checked = 'checked';" : "";
		$objResponse->script(sprintf("$('#trRangoKm').before('".
		"<tr id=\"trItemsRango%s\" align=\"left\" class=\"textoGris_11px %s\">". 
			"<td>%s Km <input id=\"checkTr%s\" type=\"checkbox\" value=\"%s\" name=\"checkTr[]\" checked=\"checked\" style=\"display:none\"></td>".
			"<td align=\"center\" title=\"Ofrecer Servicio\"><input id=\"checkRangoKm\" type=\"checkbox\" %s %s value=\"%s\" name=\"checkRangoKm[]\"> </td>".
			"<td align=\"center\" title=\"Servicio Realizado Aqui\"><input id=\"checkServicioAfuera\" type=\"checkbox\" %s value=\"%s\" name=\"checkServicioAfuera[]\"></td>".
			"<td align=\"center\" title=\"Enviar Correo\"><input id=\"checkEmail\" type=\"checkbox\" %s %s value=\"%s\" name=\"checkEmail[]\"  disabled=\"disabled\"></td>".
		"</tr>')",
		$contFila,$clase,
			$rango,$contFila,$contFila,
			$checked,$disabled,$rango,
			$checkedSA,$rango,
			$checkedE,$disabled,$rango));
	}

	//

	$objResponse->assign("numPlaca","value", $rows['placa']);
	$objResponse->assign("nombreMarca","value",$rows['nom_marca']);
	$objResponse->assign("nombreModelo","value",$rows['nom_modelo']);
	$objResponse->assign("textUltimoKm","value",$rows['Km']);
	$objResponse->assign("idRegistroPLaca","value",$rows['id_registro_placas']);
	$objResponse->assign("idClienteCotacto","value",$rows['id_cliente_contacto']);
		
	$sqlCorreo = sprintf("SELECT * FROM crm_no_enviar_correo WHERE id_cliente_contacto = %s;",
		valTpDato($rows['id_cliente_contacto'],"int"));
	$queryCorreo = mysql_query($sqlCorreo);
	if (!$queryCorreo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowsCorreo = mysql_fetch_array($queryCorreo);

	$radioCorreo = ($rowsCorreo['enviar_correo'] == "no") ? "radioCorreo2" : "radioCorreo";

	$objResponse->assign($radioCorreo,"checked",true);

	$objResponse->assign("tdFlotanteCargarKm","innerHTML","Ofrecer Servico");
	$objResponse->script("centrarDiv(byId('tdFlotanteCargarKm'));");
	
	return $objResponse;
}

function guardaActividadAgenda($datosForm){
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");		
	
	if($datosForm['hddActEjecucion'] > 0) { // ACTULIZA Y FINALIZA SI EL CAMPO EXISTE
		if(!xvalidaAcceso($objResponse,"crm_asignacion_actividad_list","editar")){ return $objResponse; }
		
		if ($datosForm['listActividad'] != "") {
			$cond = (strlen($sqlBusq) > 0) ? ", " : "  ";
			$sqlBusq .= $cond.sprintf("id_actividad = %s",
				valTpDato($datosForm['listActividad'], "int"));
		}
		if ($datosForm['idClienteHidd'] != "") {
			$cond = (strlen($sqlBusq) > 0) ? ", " : "  ";
			$sqlBusq .= $cond.sprintf("id = %s",
				valTpDato($datosForm['idClienteHidd'], "int"));
		}
	
		$cond = (strlen($sqlBusq) > 0) ? " , " : "  ";
			$sqlBusq .= $cond.sprintf("fecha_actualizacion = now()");
			
		if ($datosForm['textIdIntegrante'] != "") {
			$cond = (strlen($sqlBusq) > 0) ? ", " : "  ";
			$sqlBusq .= $cond.sprintf("id_integrante_equipo = %s",
				valTpDato($datosForm['textIdIntegrante'], "int"));
		}
		
		if ($datosForm['textNotaCliente'] != "") {
			$cond = (strlen($sqlBusq) > 0) ? ", " : "  ";
			$sqlBusq .= $cond.sprintf("notas = %s",
				valTpDato($datosForm['textNotaCliente'], "text"));
		}
		
		if ($datosForm['comboxEstadoActAgenda'] != "") {//2 FINALIZADO TARDE; 0 FINALIZADO
			$estatu =(date("Y-m-d H:i",strtotime($datosForm['textFechAsignacion'].$datosForm['textHoraAsignacion2'])) < date("Y-m-d H:i"))? 2: 0; 
			
			$cond = (strlen($sqlBusq) > 0) ? ", " : "  ";
			$sqlBusq .= $cond.sprintf("estatus = %s",
				valTpDato($estatu, "int"));
			
			$cond = (strlen($sqlBusq) > 0) ? ", " : "  ";
			$sqlBusq .= $cond.sprintf("tipo_finalizacion = %s",
				valTpDato($datosForm['comboxEstadoActAgenda'], "int"));
		}		
		
		$sqlUpdate = sprintf("UPDATE crm_actividades_ejecucion SET
			%s
		WHERE id_actividad_ejecucion = %s;",
			$sqlBusq,
		valTpDato($datosForm['hddActEjecucion'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$rsUpdate = mysql_query($sqlUpdate);
		if (!$rsUpdate) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	} else {
		
		if(!xvalidaAcceso($objResponse,"crm_asignacion_actividad_list","insertar")){ return $objResponse; }
		
		if(date("Y-m-d",strtotime($datosForm['textFechAsignacion'])) < date("Y-m-d")){
			return $objResponse->alert("La Fecha de Asignacion no Puede Ser Menor a la Fecha Actual");
		}

		$insetActividad = sprintf("INSERT INTO crm_actividades_ejecucion (id_actividad, id_integrante_equipo, id, fecha_asignacion, fecha_creacion, estatus, notas, id_empresa) VALUES (%s, %s, %s, %s, now(), 1, %s, %s)",
			valTpDato($datosForm['listActividad'], "int"),
			valTpDato($datosForm['textIdIntegrante'], "int"),
			valTpDato($datosForm['idClienteHidd'], "int"),
			valTpDato(date("Y-m-d H:i",strtotime($datosForm['textFechAsignacion'] . $datosForm['textHoraAsignacion2'])), "text"),
			valTpDato($datosForm['textNotaCliente'], "text"), 
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$queryActividad= mysql_query($insetActividad);
		if (!$queryActividad) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); 
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Fuente de Informacion Guardada con Exito");
	$objResponse->script("byId('butCancelarAsignacion').click();");
	$objResponse->script(sprintf("xajax_selectIntegrante(%s,document.getElementById('comboListEquipo').value);",
		valTpDato($datosForm['textFechAsignacion'],"text")));

	return $objResponse;
}

function guardaActividadPostVenta($datosForm){
	$objResponse = new xajaxResponse();
	if(!xvalidaAcceso($objResponse,"crm_asignacion_actividad_list","insertar")) return $objResponse;
	
	if (date("Y-m-d",strtotime($datosForm['fechaAsignacion2'])) < date("Y-m-d")){ 
		return	$objResponse->alert("La Fecha de Asignacion no Puede ser Menor a la Fecha Actual"); 
	}
	
	$insetActividad = sprintf("INSERT INTO crm_actividades_ejecucion (id_actividad, id_integrante_equipo, id, fecha_asignacion, fecha_creacion, estatus, notas, id_empresa) VALUES (%s, %s, %s, %s, now(), 1, %s, %s)",
		valTpDato($datosForm['listActividad'], "int"),
		valTpDato($datosForm['comboListIntegrante'], "int"),
		valTpDato($datosForm['textIdNombreClienteS'], "int"),
		valTpDato(date("Y-m-d H:i",strtotime($datosForm['fechaAsignacion2'] . $datosForm['horaSelect'])), "text"),
		valTpDato($datosForm['notaServicio'], "text"), 
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	$queryActividad= mysql_query($insetActividad);
	if (!$queryActividad) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); 
	
	$objResponse->alert("Fuente de Informacion Guardada con Éxito");
	$objResponse->script("byId('butCerrar2').click();");
	
	return $objResponse;
}

//GUARDAR LAS ACTIVIDADES ASIGNADAS	
function guardaActividadAuto($datosForm){
	$objResponse = new xajaxResponse();

	if(!isset($datosForm["chekAsignaActividad"])){
		return $objResponse->alert("Debes seleccionar al menos 1 cliente");
	}

	//COMPRUEBO QUE EXITE EL EQUIPO 1 CON LA RECEPCIONISTA O ASESOR DE SERVICIO
	$queryInteg= "SELECT id_integrante_equipo, id_equipo, crm_integrantes_equipos.id_empleado, 
		pg_empleado.id_cargo_departamento, clave_filtro, pg_cargo_departamento.id_cargo
	FROM crm_integrantes_equipos
		LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado
		LEFT JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento
		LEFT JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
	WHERE crm_integrantes_equipos.activo = 1
		AND id_equipo = 1
		AND (clave_filtro = 25 OR clave_filtro = 5) LIMIT 1";
	$rsInteg = mysql_query($queryInteg);
	if (!$rsInteg) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$numInteg = mysql_num_rows($rsInteg);
	$rowsInteg = mysql_fetch_array($rsInteg);

	if ($numInteg == 0) { return $objResponse->alert("No se Puede Asignar Actividad Automatica Debido a que en el Equipo 1 no Existe una Recepcionista ó Asesor de Servicio Activo"); } 

	// CONSULTA PARA SABER LAS HORAS OCUPADA PARA EL INTEGRANTE
	$queryHoras = sprintf("SELECT id_actividad_ejecucion, id_integrante_equipo, fecha_asignacion
		FROM crm_actividades_ejecucion
			WHERE DATE(fecha_asignacion) = DATE(NOW())
		AND id_integrante_equipo = %s",
	valTpDato($rowsInteg['id_integrante_equipo'],"int"));
	$rsHoras = mysql_query($queryHoras);
	if (!$rsHoras) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	while($rows = mysql_fetch_array($query)){
		$fechaAsignacion = $rows["fecha_asignacion"];
		$arrayTiempo[] = date('H:i',strtotime($fechaAsignacion));
		$idActividadEjecucion = $rows["id_actividad_ejecucion"];
	}
//var_dump($arrayTiempo);
	// CONSTRUYE LAS HORA
	$horaInicio = date("H:i",strtotime("07:00"));
	$interval = 30;
	$horaFin =  date("H:i",strtotime("19:00"));
	
	$arrayHoras[] = $horaInicio;
	
	$resta = abs(date("H", strtotime($horaInicio)) - date("H", strtotime($horaFin)));
	$aux = 0;
	while ($arrayHoras[$aux] != $horaFin){
		$arrayHoras[$aux+1] = date("H:i", strtotime("+ ".$interval." minutes", strtotime($arrayHoras[$aux])));
		$aux++;
	}
	
	if (isset($arrayTiempo)){
		$horasOption = array_diff($arrayHoras, $arrayTiempo); //ELIMINA LOS ARRAY IGUALES date("h:i A",strtotime())
	} else {
		$horasOption = $arrayHoras;
	}
	
	// eliminar horas transcurridas en el dia, si es las 3 eliminar las de la mañana las elimina	
	$horaActual = date("H:i");
	foreach ($horasOption as $key => $value){
		if (strtotime($value) <= strtotime($horaActual)){
			unset($horasOption[$key]);
		}
	}
	
	// SI NO EXISTEN HORAS DISPONIBLES
	if (count($horasOption) <= 0){
		return $objResponse->alert("No Existe Horas Disponibles Para Este Dia");
	}
	
	// APARTIUR DE LA HORA ACTUAL LIBRE
	$horaCliente;
	$auxiliar = 0;
	foreach($horasOption as $fechaLibre){
		if (array_key_exists($auxiliar,$datosForm['chekAsignaActividad'])){
			// CONTIENE LA HORA LIBRE COMO INDICE EL ID DEL CLIENTE COMO VALR DEL ARRAY
			$horaCliente[$fechaLibre] = $datosForm['chekAsignaActividad'][$auxiliar]; 
		}
	$auxiliar++;
	}	
	
	$cantidadHora = count($horasOption);//conteo de horas libres
	$cantidadHoraCliente = count($horaCliente);//conteo de hora y asociado al cliente

	// PARA BUSCAR LA ACTIVIDAD QUE ESTAS SELECCIONADO COMO AUTOMATICA
	$queryActAuto = "SELECT * FROM crm_actividad WHERE actividad_auto = 1";
	$rsActAuto = mysql_query($queryActAuto);
	$numActAuto = mysql_num_rows($rsActAuto);
	if (!$rsActAuto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowActAuto = mysql_fetch_array($rsActAuto);
	
	if($numActAuto == 0){return $objResponse->alert("No Tiene Ninguna Actividad Configurada Como Automatica");}

	// RECORRRO EL ARRAY PARA SACAR LOS DATOS (hora y idCliente) Y HACER EL INSERT
	foreach($horaCliente as $hora => $idCliente){
		$fechaAsignacion = date("Y-m-d")." ".$hora;
		$sqlActAuto = sprintf("INSERT INTO crm_actividades_ejecucion (id_actividad, id_integrante_equipo, id, fecha_asignacion, fecha_creacion, estatus, notas, id_empresa) VALUES (%s, %s, %s, %s, now(), 1, %s, %s)",
			valTpDato($rowActAuto['id_actividad'], "int"), 
			valTpDato($rowsInteg['id_integrante_equipo'],"int"),
			valTpDato($idCliente, "int"),
			valTpDato($fechaAsignacion, "text"),
			valTpDato("Actividad Creada Automaticamente", "text"), 
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

		$queryActiAutoInsert= mysql_query($sqlActAuto);
		if (!$queryActiAutoInsert) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}//fin foreach

	$texto = (count($horaCliente) == 1) ? "Se Registro ".count($horaCliente)." Actividad" : "Se Registraron ".count($horaCliente)." Actividades";
	$objResponse->alert($texto);

	return $objResponse;
}

//ALMACENA EL RANGO DE KM QUE SE LE OFRECIO AL CLIENTE
function guardarKm($datosFormKm){
	
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"crm_asignacion_actividad_list","insertar")){ return $objResponse; }
	
	if (isset($datosFormKm['checkRangoKm'])){
		$aux['checkRangoKm'] = $datosFormKm['checkRangoKm'];
	}
	
	if (isset($datosFormKm['checkServicioAfuera'])){
		$aux['checkServicioAfuera'] = $datosFormKm['checkServicioAfuera'];
	}
	
	if (isset($datosFormKm['checkEmail'])){
		$aux['checkEmail'] = $datosFormKm['checkEmail'];
	}

	if (isset($datosFormKm['checkEmail']) || isset($datosFormKm['checkServicioAfuera']) || isset($datosFormKm['checkRangoKm'])){
	
				foreach($aux as $nombreFormulario => $arrayDatos){
					foreach($arrayDatos as $datoGuardar){
					if ($nombreFormulario == "checkRangoKm"){
						$campo = "servicio_ofrecido";
						
					} else if ($nombreFormulario == "checkServicioAfuera"){
						$campo = "servicio_realizado_afuera";
						
					} else if ($nombreFormulario == "checkEmail"){
						$campo = "enviar_email";
					}
			
					$sqlSelect=sprintf("SELECT placa, servicio_ofrecido FROM crm_ofrecer_servicio WHERE placa = %s AND (servicio_ofrecido = %s OR servicio_realizado_afuera = %s OR enviar_email = %s);",
					valTpDato($datosFormKm['numPlaca'], "text"), $datoGuardar, $datoGuardar, $datoGuardar);
					$querySelect= mysql_query($sqlSelect);
						if (!$querySelect) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					
						if (mysql_num_rows($querySelect) > 0){
								
							$updateRangoKm =sprintf("UPDATE crm_ofrecer_servicio SET 
							%s = %s
							WHERE placa = %s AND (servicio_ofrecido = %s OR servicio_realizado_afuera = %s OR enviar_email = %s);",
							$campo,
							$datoGuardar,
							valTpDato($datosFormKm['numPlaca'], "text"),
							valTpDato($datoGuardar,"int"),
							valTpDato($datoGuardar,"int"),
							valTpDato($datoGuardar,"int"));
							
							$queryRangoKm= mysql_query($updateRangoKm);
							if (!$queryRangoKm) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
							
						} else {
							
							$insetRangoKm = sprintf("INSERT INTO crm_ofrecer_servicio (id_registro_placas, placa, id_cliente_contacto, %s)
							VALUES (%s, %s, %s, %s)",
							$campo,
							valTpDato($datosFormKm['idRegistroPLaca'], "int"),
							valTpDato($datosFormKm['numPlaca'], "text"),
							valTpDato($datosFormKm['idClienteCotacto'], "int"),
							valTpDato($datoGuardar, "int"));
							
							mysql_query("SET NAMES 'utf8'");
							$queryRangoKm= mysql_query($insetRangoKm);
							if (!$queryRangoKm) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
							
						}
					}
				}
		//SI DESEA ENVIAR CORREO
			$sqlCorreo = sprintf("SELECT * FROM crm_no_enviar_correo WHERE id_cliente_contacto = %s;",valTpDato($datosFormKm['idClienteCotacto'], "int"));
			$queryCorreo = mysql_query($sqlCorreo);
			if (!$queryCorreo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
			if (mysql_num_rows($queryCorreo) > 0){
				
					$sqlEmail = sprintf("UPDATE crm_no_enviar_correo SET
										 	enviar_correo = %s
										 WHERE id_cliente_contacto = %s;",
											 valTpDato($datosFormKm['radioCorreo'], "text"),
											 valTpDato($datosFormKm['idClienteCotacto'], "int"));
					mysql_query("SET NAMES 'utf8'");
					$queryEmail = mysql_query($sqlEmail);
					if (!$queryEmail) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
							
				} else {
					
					$sqlEmail = sprintf("INSERT INTO crm_no_enviar_correo (enviar_correo,id_cliente_contacto,rangoKM) VALUES (%s,%s,%s);",
						valTpDato($datosFormKm['radioCorreo'], "text"),
						valTpDato($datosFormKm['idClienteCotacto'], "int"),
						$datoGuardar);
					mysql_query("SET NAMES 'utf8'");
					$queryEmail = mysql_query($sqlEmail);
					if (!$queryEmail) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					
				}
	} else {
		return $objResponse->alert("Debe seleccionar algun valor");	
	}
	
	$objResponse->alert("Fuente de Informacion Guardada con Éxito");
	$objResponse->script("byId('butCancelar').click();");
	
	return $objResponse;
	
}

function generarGrafico($datosGrafico){
	$objResponse = new xajaxResponse();
	
	if (date("Y", strtotime($datosGrafico['fechaInicio'])) != date("Y", strtotime($datosGrafico['fechaFin']))){
		return $objResponse->alert("Debe seleccionar el mismo periodo de año");
	}
	
	if (strtotime($datosGrafico['fechaInicio']) > strtotime($datosGrafico['fechaFin'])){
		return $objResponse->alert("La fecha de inicio no debe ser mayor a la fecha fin");
	}
	
	$mesInicio =date("m",strtotime($datosGrafico['fechaInicio'])); //mes donde indica el inicio de la tabla 5-6 1-12 etc 
	$mesFin =date("m",strtotime($datosGrafico['fechaFin'])); //mes donde indica el fin de la tabla
	$cantidadMes = $mesInicio - $mesFin;//resta de meses para saber cuantos hay 3-6 = 3 y luego sumo +1 para que de correcto 4
	$cantidadMes = (abs($cantidadMes))+1;//sumo 1 para que de correcto 4
	$rangoMeses = array_fill($mesInicio,$cantidadMes,"0|0|0|0");//Lo lleno con la cantidad de keys como meses 6-7-8-9-10 y llenarlo

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_asignacion) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($datosGrafico['fechaInicio'])), "text"),
			valTpDato(date("Y-m-d",strtotime($datosGrafico['fechaFin'])), "text"));
			
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));		
	
	if($datosGrafico['comboListEquipo'] != ""){// POR EQUIPO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_equipo.id_equipo = %s",
			valTpDato($datosGrafico['comboListEquipo'], "int"));
	}
	if($datosGrafico['comboListIntegrante'] != ""){// POR INTEGRANTE
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_integrantes_equipos.id_integrante_equipo = %s",
			valTpDato($datosGrafico['comboListIntegrante'], "int"));
	}
	if($datosGrafico['listActividad'] != ""){ // POR ACTIVIDADES
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_actividad = %s",
			valTpDato($datosGrafico['listActividad'], "int"));
	}
	
	$sql = sprintf("SELECT COUNT(id_actividad_ejecucion) as total_mes, 
			fecha_asignacion,
			MONTH(fecha_asignacion) as mes, 
			nombre_actividad, 
			estatus, 
			nombre_equipo,
			 crm_integrantes_equipos.id_integrante_equipo,
			crm_integrantes_equipos.id_empleado,
			CONCAT_WS(' ',nombre_empleado, apellido)  AS nombre_integrante
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON  crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			LEFT JOIN crm_integrantes_equipos ON crm_actividades_ejecucion.id_integrante_equipo = crm_integrantes_equipos.id_integrante_equipo
			LEFT JOIN crm_equipo ON crm_integrantes_equipos.id_equipo = crm_equipo.id_equipo
			LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado
			%s
		GROUP BY MONTH(fecha_asignacion), estatus", $sqlBusq);
	$rs= mysql_query($sql);
	if(!$rs)$objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'utf8'");
	$registro = mysql_num_rows($rs);

	foreach($rangoMeses as $mes => $contenidoMes){
		while($rows = mysql_fetch_assoc($rs)){
			$arrayConsulta [0] = ($datosGrafico['listActividad'] != "") ? utf8_encode($rows["nombre_actividad"]):"(Todas las Actividades)";
			$arrayConsulta [1] = ($datosGrafico['comboListIntegrante'] != "") ? utf8_encode($rows["nombre_integrante"]):"(Todos los Integrantes)";
			
			if ($mes == $rows["mes"] && $rows["estatus"] == 0){
				$datos = explode("|", $rangoMeses[$mes]);
				$datos[0] = $rows["total_mes"];					
				$rangoMeses[$mes] = implode("|",$datos);
			}
			if ($mes == $rows["mes"] && $rows["estatus"] == 1){
				$datos = explode("|", $rangoMeses[$mes]);
				$datos[1] = $rows["total_mes"];					
				$rangoMeses[$mes] = implode("|",$datos);
			}
			if ($mes == $rows["mes"] && $rows["estatus"] == 2){
				$datos = explode("|", $rangoMeses[$mes]);
				$datos[2] = $rows["total_mes"];					
				$rangoMeses[$mes] = implode("|",$datos);
			}
			if ($mes == $rows["mes"] && $rows["estatus"] == 3){
				$datos = explode("|", $rangoMeses[$mes]);
				$datos[3] = $rows["total_mes"];					
				$rangoMeses[$mes] = implode("|",$datos);
			}
		}	
		if ($registro){
			mysql_data_seek($rs,0);//Una vez que termina el while, no se puede volver a recorrer hasta que inicializas el puntero a 0
		}
	}		

	foreach($rangoMeses as $indice => $valor){
		$objResponse->script(sprintf("array_js[%s] = %s;",
			valTpDato($indice,"int"),
			valTpDato($valor,"text")));
	}
	
	$objResponse->script(sprintf("crearGrafico(array_js,%s,%s);",
		valTpDato($arrayConsulta [0],"text"),
		valTpDato($arrayConsulta [1],"text")));
		
	return $objResponse;	
}
	
function generarGrafico2($datosGrafico){
	$objResponse = new xajaxResponse();

	if (date("Y", strtotime($datosGrafico['fechaInicio2'])) != date("Y", strtotime($datosGrafico['fechaFin2']))){
		return $objResponse->alert("Debe seleccionar el mismo periodo de año");
	}
	
	if (strtotime($datosGrafico['fechaInicio2']) > strtotime($datosGrafico['fechaFin2'])){
		return $objResponse->alert("La fecha de inicio no debe ser mayor a la fecha fin");
	}
	
	if($datosGrafico["comboListEquipo"] != ""){ // POR EQUIPO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_integrantes_equipos.id_equipo = %s",
			valTpDato($datosGrafico['comboListEquipo'], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_integrantes_equipos.activo = %s",
			valTpDato(1, "int"));
			
	$sqlIntegrantes = sprintf("SELECT 
			id_integrante_equipo,
			nombre_equipo,
			CONCAT(nombre_empleado,' ', apellido) AS nombre_apellido_integrante
		FROM crm_integrantes_equipos
			LEFT JOIN crm_equipo ON crm_integrantes_equipos.id_equipo = crm_equipo.id_equipo
			LEFT JOIN pg_empleado ON  pg_empleado.id_empleado=crm_integrantes_equipos.id_empleado
		%s",
	$sqlBusq);
//$objResponse->alert($sqlIntegrantes);
	$queryIntegrantes = mysql_query($sqlIntegrantes);
	if(!$queryIntegrantes)$objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($queryIntegrantes)){
		$arrayNombIntegra[$row["id_integrante_equipo"]] = utf8_encode($row["nombre_apellido_integrante"]);
		$titulo= $row["nombre_equipo"];
	}

	if($datosGrafico["comboListEquipo"] != ""){ // POR EQUIPO
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("crm_integrantes_equipos.id_equipo = %s",
			valTpDato($datosGrafico['comboListEquipo'], "int"));
	}
	
	$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";// POR EMPRESA
	$sqlBusq2 .= $cond2.sprintf("id_empresa = %s",
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE "; // POR FECHA
	$sqlBusq2 .= $cond2.sprintf("DATE(fecha_asignacion) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d",strtotime($datosGrafico['fechaInicio2'])), "text"),
		valTpDato(date("Y-m-d",strtotime($datosGrafico['fechaFin2'])), "text"));

	if($datosGrafico["tipoFinalizado"] != ""){ // POR TIPO DE FINALIZACION
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("tipo_finalizacion = %s",
			valTpDato($datosGrafico['tipoFinalizado'], "int"));
			
		$subTitulo =($datosGrafico['tipoFinalizado'] == 0) ? "Actividades Finalizadas No Efectivas": "Actividades Finalizadas Efectivas";
	}else{
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("tipo_finalizacion IS NOT NULL");
		
		$subTitulo =" Todas Las Actividades Finalizadas ";
	}
	
	$sqlActInteg = sprintf("SELECT id_actividad_ejecucion,
			id_actividad,
			crm_actividades_ejecucion.id_integrante_equipo,
			crm_integrantes_equipos.id_equipo,
			id_empresa,
			estatus,
			tipo_finalizacion,
			COUNT(id_actividad_ejecucion) AS total_act_Finalizadas
		FROM crm_actividades_ejecucion 
			INNER JOIN crm_integrantes_equipos ON crm_integrantes_equipos.id_integrante_equipo = crm_actividades_ejecucion.id_integrante_equipo
			%s
		GROUP BY tipo_finalizacion,crm_actividades_ejecucion.id_integrante_equipo", $sqlBusq2);
//$objResponse->alert($sqlActInteg);
	$rsActInteg = mysql_query($sqlActInteg);
	if(!$rsActInteg)$objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$arrayTipoFinalizacion = NULL;
	$arrayTotalAct = NULL;
	while ($rowActInteg = mysql_fetch_assoc($rsActInteg)){
		$arrayTotalAct [$rowActInteg['id_integrante_equipo']] [$rowActInteg['tipo_finalizacion']] =  $rowActInteg['total_act_Finalizadas'];
	}
	
	foreach($arrayNombIntegra as $indiceNomb => $valorNomb){
		foreach($arrayTotalAct as $indiceAct => $valorAct){
			if($indiceAct == $indiceNomb){
				$arrayIntAct [$valorNomb] = $valorAct[0]."|".$valorAct[1];
			}
		}
	}

/*print_r($arrayIntAct);
echo "<pre>";*/

	foreach($arrayIntAct as $indice => $valor){
		$objResponse->script(sprintf("arrayActInteg[%s] = %s",
			valTpDato($indice,"text"),
			valTpDato($valor,"text")));
	}			

	$objResponse->script(sprintf("crearGrafico2(%s,%s);",
		valTpDato($titulo,"text"),
		valTpDato($subTitulo,"text")));

	return $objResponse;	
	
}

//HISTORICO DE ACTIVIDADES POR CLIENTE POR ASESOR Y EQUIPO
function historicoActividad($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT id_actividad_ejecucion, crm_actividades_ejecucion.id_actividad, nombre_actividad, crm_actividades_ejecucion.id_integrante_equipo,
			crm_integrantes_equipos.id_empleado, CONCAT_WS(' ', nombre_empleado, pg_empleado.apellido) AS nombre_del_integrante,
			crm_actividades_ejecucion.id, estatus, notas
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			LEFT JOIN crm_integrantes_equipos ON crm_integrantes_equipos.id_integrante_equipo=crm_actividades_ejecucion.id_integrante_equipo
			LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado
			LEFT JOIN cj_cc_cliente ON cj_cc_cliente.id = crm_actividades_ejecucion.id
		WHERE crm_actividades_ejecucion.id = %s",
	$valBusq, $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf("ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
		//$objResponse->alert($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_HistoricoActividad", "25%", $pageNum, "nombre_actividad", $campOrd, $tpOrd, $valBusq, $maxRows, "Actividad");
		$htmlTh .= ordenarCampo("xajax_HistoricoActividad", "25%", $pageNum, "nombre_del_integrante", $campOrd, $tpOrd, $valBusq, $maxRows, "Asesor");
		$htmlTh .= ordenarCampo("xajax_HistoricoActividad", "50%", $pageNum, "notas", $campOrd, $tpOrd, $valBusq, $maxRows, "Nota");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$nombreActividad = utf8_encode($row['nombre_actividad']);
		$nombreIntegrante = utf8_encode($row['nombre_del_integrante']);
		$nota = utf8_encode($row['notas']);
		$estatus = $row['estatus'];
		
		switch($estatus){
			case 0: $img ="<img src=\"../img/iconos/ico_aceptar.gif\">"; break;//finalizado
			case 1: $img ="<img src=\"../img/iconos/ico_aceptar_azul.png\">"; break;//asignado
			case 2: $img ="<img src=\"../img/cita_entrada_retrazada.png\">"; break;//Finalizo tarde
			case 3: $img ="<img src=\"../img/iconos/arrow_rotate_clockwise.png\">";	break;//Finalizado auto
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">".$nombreActividad."</td>";
		$htmlTb .= "<td align=\"center\">".$nombreIntegrante."</td>";
		$htmlTb .= "<td align=\"center\">".$nota."</td>";
		$htmlTb .= "<td align=\"left\">".$img."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_HistoricoActividad(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_HistoricoActividad(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_HistoricoActividad(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_HistoricoActividad(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_HistoricoActividad(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}	
	
	$objResponse->assign("tdHitoricoActividades","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

//MUESTRA EL HISTORIAL DE LOS VEHICULOS 
function historicoServicio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT sa_cita.id_cita, fecha_entrada, id_registro_placas, motivo, descripcion_submotivo, descripcion_falla
		FROM sa_cita
			LEFT JOIN sa_recepcion ON sa_recepcion.id_cita = sa_cita.id_cita
			LEFT JOIN sa_motivo_cita ON  sa_motivo_cita.id_motivo_cita = sa_cita.id_motivo_cita
			LEFT JOIN sa_recepcion_falla ON sa_recepcion_falla.id_recepcion = sa_recepcion.id_recepcion
			LEFT JOIN sa_submotivo ON sa_submotivo.id_submotivo = sa_cita.id_submotivo
		WHERE id_registro_placas = %s AND fecha_entrada IS NOT NULL ORDER BY id_cita %s",
	$valCadBusq[0], $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf("ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_historicoServicio", "10%", $pageNum, "fecha_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha de Entrada");
		$htmlTh .= ordenarCampo("xajax_historicoServicio", "20%", $pageNum, "motivo", $campOrd, $tpOrd, $valBusq, $maxRows, "Motivo");
		$htmlTh .= ordenarCampo("xajax_historicoServicio", "20%", $pageNum, "descripcion_submotivo", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion Submotivo");
		$htmlTh .= ordenarCampo("xajax_historicoServicio", "50%", $pageNum, "descripcion_falla", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion de Falla");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$fechaEntrada = $row['fecha_entrada'];
		$motivo = utf8_encode($row['motivo']);
		$descripcionSubmotivo = utf8_encode($row['descripcion_submotivo']);
		$descripcionFalla = utf8_encode($row['descripcion_falla']);
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$fechaEntrada."</td>";
			$htmlTb .= "<td align=\"center\">".$motivo."</td>";
			$htmlTb .= "<td align=\"center\">".$descripcionSubmotivo."</td>";
			$htmlTb .= "<td>".$descripcionFalla."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_historicoServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_historicoServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_historicoServicio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_historicoServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_historicoServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}	
	
	$objResponse->assign("tdHistorialServicio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

//LISTA DE ACTIVIDADES ACTUALES
function listaActDiaActual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(fecha_asignacion) = CURRENT_DATE()");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	
	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[1], "text"));
	}

	$query = sprintf("SELECT COUNT(id_actividad_ejecucion) AS total, crm_actividades_ejecucion.id_actividad, nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY nombre_actividad", 
	$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$htmlTh .= ordenarCampo("xajax_listaActDiaActual", "80%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Actividades Asignadas del Día");
	$htmlTh .= ordenarCampo("xajax_listaActDiaActual", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActDiaActual(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActDiaActual(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActDiaActual(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActDiaActual(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActDiaActual(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";


				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	
	}
	
	$objResponse->assign("tdActividadesDiaActual","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES ATRASADAS DEL DIA
function listaActAtrasadasDia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fecha_asignacion < CURRENT_TIMESTAMP()");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	
	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[1], "text"));
	}

	$query = sprintf("SELECT COUNT(id_actividad_ejecucion) AS total, crm_actividades_ejecucion.id_actividad, nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY nombre_actividad", 
	$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$htmlTh .= ordenarCampo("xajax_listaActAtrasadasDia", "80%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Actividades Atrasadas del Dia");
	$htmlTh .= ordenarCampo("xajax_listaActAtrasadasDia", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActAtrasadasDia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActAtrasadasDia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActAtrasadasDia(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActAtrasadasDia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActAtrasadasDia(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";


				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	
	}
	
	$objResponse->assign("tdActividadesAtrazadasDia","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES DEL DIA SIGUIENTE
function listaActDiaSiguiente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(fecha_asignacion) = DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	
	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[1], "text"));
	}

	$query = sprintf("SELECT COUNT(id_actividad_ejecucion) AS total, crm_actividades_ejecucion.id_actividad, nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY nombre_actividad", 
	$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$htmlTh .= ordenarCampo("xajax_listaActAtrasadasDia", "80%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Actividades para Mañana");
	$htmlTh .= ordenarCampo("xajax_listaActAtrasadasDia", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActDiaSiguiente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActDiaSiguiente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActAtrasadasDia(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActDiaSiguiente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActDiaSiguiente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";

				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	}
	
	$objResponse->assign("tdActividadesDiaSiguiente","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES POR EQUIPO DETALLADO
function listaActPorEquipoDet($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_equipo.activo = %s",
		valTpDato(1, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	
	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_equipo= %s",
			valTpDato($valCadBusq[0], "text"));
	}

	$query = sprintf("SELECT DISTINCT crm_integrantes_equipos.id_equipo, nombre_equipo, crm_equipo.activo
		FROM crm_integrantes_equipos
			LEFT JOIN crm_equipo ON crm_equipo.id_equipo = crm_integrantes_equipos.id_equipo 
		 %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$htmlTh .= ordenarCampo("xajax_listaActPorEquipoDet", "100%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Detalle Actividades Asiganadas Por Equipo");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	$numEquipo = mysql_num_rows($rsLimit);
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= sprintf("<td align=\"left\">%s",$row['nombre_equipo']);
				$htmlTb .=sprintf("
	<img id=\"imgVerActividades%s\" align=\"right\" class=\"puntero\" title=\"Ver Detalle\" src=\"../img/iconos/plus.png\" onclick=\"mostrarOcultatActividad(%s)\" name=\"imgVerActividades\">",$row['id_equipo'],$row['id_equipo']);
				$htmlTb .=sprintf("<img id=\"imgOcutarActividades%s\" style=\"display:none\" align=\"right\" class=\"puntero\" title=\"Ver Detalle\" src=\"../img/iconos/minus.png\" onclick=\"mostrarOcultatActividad(%s)\" name=\"imgOcutarActividades\">",$row['id_equipo'],$row['id_equipo']);
			$htmlTb .="</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr>";
			$htmlTb .= sprintf("<td id=\"DetEquipoActividades%s\" colspan=\"2\" style=\"display:none\"></td>",$row['id_equipo']);
		$htmlTb .= "</tr>";
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActPorEquipoDet(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActPorEquipoDet(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActAtrasadasDia(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActPorEquipoDet(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActPorEquipoDet(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";


				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdNombreEquipo","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DETALLE INTEGRANTE Y ACTIVIDADES
function listaDetActIntegrante($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_equipo = %s",
			valTpDato($valCadBusq[0], "int"));
	}

	$query = sprintf("SELECT
		id_actividad_ejecucion,
		crm_actividades_ejecucion.id_actividad,
		crm_actividades_ejecucion.id_integrante_equipo,
		nombre_actividad,
		estatus,
		tipo_finalizacion,
		COUNT(id_actividad_ejecucion) AS total
	FROM crm_actividades_ejecucion
		LEFT JOIN crm_integrantes_equipos ON crm_actividades_ejecucion.id_integrante_equipo = crm_integrantes_equipos.id_integrante_equipo
		LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
	%s
	GROUP BY crm_actividades_ejecucion.id_actividad,estatus", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$htmlTh .= ordenarCampo("xajax_listaDetActIntegrante", "90%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Actividades Asignadas");
	$htmlTh .= ordenarCampo("xajax_listaDetActIntegrante", "5%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "<td align=\"center\"></td>";
	$htmlTh .= "<td align=\"center\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus']){
			case 0:	$imgEstatus = "<img title=\"Finalizada\" src=\"../img/iconos/ico_aceptar.gif\">"; break;
			case 1:	$imgEstatus = "<img title=\"Asignada\" src=\"../img/iconos/ico_aceptar_azul.png\">"; break;
			case 2:	$imgEstatus = "<img title=\"Finalizada Tarde\" src=\"../img/cita_entrada_retrazada.png\">"; break;
			case 3:	$imgEstatus = "<img title=\"Finalizada Automatica\"src=\"../img/iconos/arrow_rotate_clockwise.png\">"; break;
			default :	$imgEstatus = ""; break;
		}
		switch($row['tipo_finalizacion']){
			case 0:	$imgFinalisado = "<img title=\"No Efectiva\" src=\"../img/iconos/cross.png\">"; break;
			case 1:	$imgFinalisado = "<img title=\"Efectiva\" src=\"../img/iconos/tick.png\">"; break;
			default : $imgFinalisado = "";
		}
		
		if($row['estatus'] == 1 || $row['estatus'] == 3){$imgFinalisado = "";}
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"center\">".$imgFinalisado."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDetActIntegrante(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDetActIntegrante(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaDetActIntegrante(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDetActIntegrante(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDetActIntegrante(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";


				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td colspan=\"3\" class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	}
	
	$objResponse->assign("DetEquipoActividades".$valCadBusq[0],"innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES DE HACE UNA SEMANA
function listaActSemPasada($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(fecha_asignacion) >= DATE_SUB(CURDATE(),INTERVAL 1 WEEK)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(fecha_asignacion) <= CURDATE()");

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	
	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[0], "text"));
	}

	$query = sprintf("SELECT 
			COUNT(id_actividad_ejecucion) AS total, 
			crm_actividades_ejecucion.id_actividad, 
			nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY nombre_actividad", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
$htmlTh .= ordenarCampo("xajax_listaActSemPasada", "80%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Actividades de la semana pasada");
$htmlTh .= ordenarCampo("xajax_listaActSemPasada", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActSemPasada(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActSemPasada(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActSemPasada(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActSemPasada(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActSemPasada(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";


				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	
	}
	
	$objResponse->assign("tdActividadesUnSemana","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES DE HACE UNA SEMANA
function listaActTresMeses($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(fecha_asignacion) >= DATE_SUB(CURDATE(),INTERVAL 3 MONTH)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(fecha_asignacion) <= CURDATE()");

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	
	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[0], "text"));
	}

	$query = sprintf("SELECT 
			COUNT(id_actividad_ejecucion) AS total, 
			crm_actividades_ejecucion.id_actividad, 
			nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY nombre_actividad", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
$htmlTh .= ordenarCampo("xajax_listaActTresMeses", "80%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Actividades de haces 3 meses");
$htmlTh .= ordenarCampo("xajax_listaActTresMeses", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActTresMeses(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActTresMeses(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActTresMeses(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActTresMeses(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActTresMeses(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	}
	
	$objResponse->assign("tdActividadesTresMeses","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES FINALIZADAS AUTOMATICAS POR SISTENA
function listaActFinalizadasAuto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus= %s",
			valTpDato($valCadBusq[0], "text"));
	}
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[1], "text"));
	}


	$query = sprintf("SELECT 
			COUNT(id_actividad_ejecucion) AS total, 
			crm_actividades_ejecucion.id_actividad, 
			nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY nombre_actividad", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
$htmlTh .= ordenarCampo("xajax_listaActFinalizadasAuto", "80%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Actividades Finalizadas automaticamentes");
$htmlTh .= ordenarCampo("xajax_listaActFinalizadasAuto", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasAuto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasAuto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActTresMeses(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasAuto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasAuto(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	}
	
	$objResponse->assign("tdActividadesFinalizadasAuto","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES FINALIZADAS TARDES
function listaActFinalizadasTarde($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus= %s",
			valTpDato($valCadBusq[0], "text"));
	}
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[1], "text"));
	}


	$query = sprintf("SELECT 
			COUNT(id_actividad_ejecucion) AS total, 
			crm_actividades_ejecucion.id_actividad, 
			nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY nombre_actividad", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
$htmlTh .= ordenarCampo("xajax_listaActFinalizadasTarde", "80%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Actividades Finalizadas Tarde");
$htmlTh .= ordenarCampo("xajax_listaActFinalizadasTarde", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasTarde(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasTarde(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActTresMeses(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasTarde(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasTarde(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	}
	
	$objResponse->assign("tdAcitivadaAsignacioFinalizadasTardes","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES FINALIZADAS NO EFECTIVAS
function listaActFinalizadasNoEfectiva($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus IN (0, 2, 3)");
	
	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_finalizacion = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[1], "text"));
	}


	$query = sprintf("SELECT 
			COUNT(id_actividad_ejecucion) AS total, 
			crm_actividades_ejecucion.id_actividad, 
			nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY crm_actividades_ejecucion.id_actividad", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
$htmlTh .= ordenarCampo("xajax_listaActFinalizadasNoEfectiva", "80%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Actividades Finalizadas No Efectiva");
$htmlTh .= ordenarCampo("xajax_listaActFinalizadasNoEfectiva", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasNoEfectiva(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasNoEfectiva(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActFinalizadasNoEfectiva(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasNoEfectiva(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasNoEfectiva(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	}
	
	$objResponse->assign("tdActividaddesFinalizadasNoEfectivas","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES FINALIZADAS EFECTIVAS
function listaActFinalizadasEfectiva($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus IN (0, 2, 3)");
	
	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_finalizacion = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[1], "text"));
	}


	$query = sprintf("SELECT 
			COUNT(id_actividad_ejecucion) AS total, 
			crm_actividades_ejecucion.id_actividad, 
			nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY crm_actividades_ejecucion.id_actividad", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$htmlTh .= ordenarCampo("xajax_listaActFinalizadasEfectiva", "80%", $pageNum, "nombre_actividad", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Actividades Finalizadas Efectiva");
	$htmlTh .= ordenarCampo("xajax_listaActFinalizadasEfectiva", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasEfectiva(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasEfectiva(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActFinalizadasEfectiva(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasEfectiva(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadasEfectiva(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	}
	
	$objResponse->assign("tdActividaddesFinalizadasEfectivas","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES ASIGNADAS
function listaActAsignadas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[1], "text"));
	}


	$query = sprintf("SELECT 
			COUNT(id_actividad_ejecucion) AS total, 
			crm_actividades_ejecucion.id_actividad, 
			nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY crm_actividades_ejecucion.id_actividad", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$htmlTh .= ordenarCampo("xajax_listaActAsignadas", "80%", $pageNum, "nombre_actividad", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Actividades Asignadas");
	$htmlTh .= ordenarCampo("xajax_listaActAsignadas", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActAsignadas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActAsignadas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActAsignadas(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActAsignadas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActAsignadas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	}
	
	$objResponse->assign("tdActividadesAsignadad","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA DE ACTIVIDADES Finalizadas
function listaActFinalizadas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_actividades_ejecucion.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus != %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo= %s",
			valTpDato($valCadBusq[1], "text"));
	}


	$query = sprintf("SELECT 
			COUNT(id_actividad_ejecucion) AS total, 
			crm_actividades_ejecucion.id_actividad, 
			nombre_actividad, fecha_asignacion
		FROM crm_actividades_ejecucion
			LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
			%s
		GROUP BY crm_actividades_ejecucion.id_actividad", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$htmlTh .= ordenarCampo("xajax_listaActFinalizadas", "80%", $pageNum, "nombre_actividad", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Actividades Finalizadas");
	$htmlTh .= ordenarCampo("xajax_listaActFinalizadas", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_actividad']."".$imgAuto." </td>";
			$htmlTb .= "<td align=\"center\">".$row['total']."</td>";
		$htmlTb .= "</tr>";
		
	$arrayTotal[] = $row['total'];
	}

	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActFinalizadas(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActFinalizadas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}else{
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\">Total de Actividades</td>";
			$htmlTb .= '<td>'.((isset($arrayTotal)) ? array_sum($arrayTotal) : 0).'</td>';
		$htmlTb .= '</tr>';
	}
	
	$objResponse->assign("tdActividadesFinalizadas","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//LISTA LOS CLIENTES
function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");

	switch($valCadBusq[2]){
		case 'Postventa': 
			$objResponse->assign("tituloTdListCleinte", "innerHTML", "Listado de Clientes");
			$tituloColun = "Cliente";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_cuenta_cliente = 2");
			break;
		case 'Ventas': 
			$objResponse->assign("tituloTdListCleinte", "innerHTML", "Listado de Prospectos");
			$tituloColun = "Prospecto";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_cuenta_cliente = 1");
				break;
	}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s", $sqlBusq);
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
//$objResponse->alert($queryLimit);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "5%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "15%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("C.I. / R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "60%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities($tituloColun));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "20%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= sprintf("<td>"."<button  type=\"button\" onclick=\"xajax_asignarCliente(%s);\" title=\"Seleccionar\">
			<img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>",$row['id']); 
			$htmlTb .= "<td align=\"center\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}
	
//LISTA LOS VHEICULOS QUE EL CLIENTE A LLEVADO A SERVICIO
function listMostrarVehiculos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$idClienteContacto = $valCadBusq[0];
	$meses = $valCadBusq[1];
	
	if ($meses == ""){
		$meses = 3;	
	}

	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT MAX(id_recepcion), sa_recepcion.id_cita, sa_recepcion.fecha_entrada, MAX(sa_recepcion.kilometraje) AS Km,
		sa_cita.id_cita, sa_cita.id_registro_placas, sa_cita.id_cliente_contacto, id_motivo_cita,
		en_registro_placas.id_registro_placas, id_cliente_registro, id_unidad_basica, placa,
		an_uni_bas.id_uni_bas, mar_uni_bas, nom_marca, mod_uni_bas, nom_modelo
	FROM sa_recepcion
		LEFT JOIN sa_cita ON sa_cita.id_cita = sa_recepcion.id_cita
		LEFT JOIN en_registro_placas ON en_registro_placas.id_registro_placas = sa_cita.id_registro_placas
		LEFT JOIN an_uni_bas ON an_uni_bas.id_uni_bas = en_registro_placas.id_unidad_basica
		LEFT JOIN an_marca ON id_marca = mar_uni_bas
		LEFT JOIN an_modelo ON id_modelo = mod_uni_bas
	WHERE DATE(fecha_entrada) >= DATE_SUB(CURDATE(),INTERVAL %s MONTH)
		AND sa_cita.id_cliente_contacto = %s GROUP BY placa", 
		$meses, $idClienteContacto);
						  
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
		//$objResponse->alert($queryLimit);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
		$htmlTableIni .= "<table border=\"0\" width=\"55%\" align=\"center\">";
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= ordenarCampo("xajax_listMostrarVehiculos", "25%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, "Placa");
			$htmlTh .= ordenarCampo("xajax_listMostrarVehiculos", "25%", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Marca");
			$htmlTh .= ordenarCampo("xajax_listMostrarVehiculos", "25%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "Modelo"); 
			$htmlTh .= ordenarCampo("xajax_listMostrarVehiculos", "25%", $pageNum, "Km", $campOrd, $tpOrd, $valBusq, $maxRows, "Kilometraje"); 
			$htmlTh .= "<td></td>";
		$htmlTh .= "</tr>";
		
		$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$placa = $row['placa'];
		$nombreMarca = utf8_encode($row['nom_marca']);
		$nombreModelo = utf8_encode($row['nom_modelo']);
		$kilometraje = $row['Km'];
		$idRegistroPlaca = $row['id_registro_placas'];
		$idClienteContacto = $row['id_cliente_contacto'];

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"center\">".$placa."</td>";
			$htmlTb .= "<td align=\"center\">".$nombreMarca."</td>";
			$htmlTb .= "<td align=\"left\">".$nombreModelo."</td>";
			$htmlTb .= "<td align=\"center\">".$kilometraje."</td>";
			$htmlTb .= "<td align=\"center\">  
			<a id=\"aNuevo\" class=\"modalImg\" onclick=\"xajax_formCargarKm(this.id,".$idRegistroPlaca.");\" rel=\"#divFlotante3\"> 
				<button type=\"button\" id=\"\" name=\"\" onclick=\"\">
					<img id=\"\" class=\"puntero\" align=\"right\" onclick=\"\" title=\"Agragar\" src=\"../img/iconos/plus.png\">
				</button>
			</a>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;", 
						$contFila, 
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listMostrarVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listMostrarVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listMostrarVehiculos(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listMostrarVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listMostrarVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";

				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("clienteContacto".$idClienteContacto."","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

//MUESTRA LOS CLIENTE CON REBISION DE HACER 3 MESE 
function ListServicioPendiente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);

	$meses = $valCadBusq[0];
	$nombreCliente = $valCadBusq[1];

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond." tipo = 'Natural' ";

	if ($valCadBusq[0] != "") {
		$tituloTable= sprintf("Lista de Clientes con Servicios de hace %s Meses",$valCadBusq[0]);
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_entrada) >= DATE_SUB(CURDATE(),INTERVAL %s MONTH)",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("CONCAT_Ws(' ', nombre, apellido) LIKE %s",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}	

	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT id_recepcion, sa_recepcion.id_cita, sa_recepcion.fecha_entrada, sa_recepcion.kilometraje, sa_cita.id_cita,
		sa_cita.id_registro_placas, sa_cita.id_cliente_contacto, id_motivo_cita, cj_cc_cliente.id, 
		CONCAT_WS(' ',nombre,apellido) AS nombre_apellido_cliente, fecha_nacimiento, telf, correo, en_registro_placas.id_registro_placas,
		id_cliente_registro, id_unidad_basica, placa, an_uni_bas.id_uni_bas, mar_uni_bas, nom_marca, mod_uni_bas, nom_modelo
	FROM sa_recepcion
		LEFT JOIN sa_cita ON sa_cita.id_cita = sa_recepcion.id_cita
		LEFT JOIN en_registro_placas ON en_registro_placas.id_registro_placas = sa_cita.id_registro_placas
		LEFT JOIN cj_cc_cliente ON cj_cc_cliente.id = sa_cita.id_cliente_contacto
		LEFT JOIN crm_perfil_prospecto ON crm_perfil_prospecto.id = cj_cc_cliente.id
		LEFT JOIN an_uni_bas ON an_uni_bas.id_uni_bas = en_registro_placas.id_unidad_basica
		LEFT JOIN an_marca ON id_marca = mar_uni_bas
		LEFT JOIN an_modelo ON id_modelo = mod_uni_bas %s
	GROUP BY cj_cc_cliente.id", $sqlBusq);

//$objResponse->alert($query);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">"; 
		$htmlTh .= sprintf("<tr class=\"tituloColumna\"><td colspan=\"8\">%s</td></tr>",$tituloTable);
		$htmlTh .= "<tr class=\"tituloColumna\"><td></td>";
			$htmlTh .= ordenarCampo("xajax_ListServicioPendiente", "60%", $pageNum, "nombre_apellido_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
			$htmlTh .= ordenarCampo("xajax_ListServicioPendiente", "20%", $pageNum, "correo", $campOrd, $tpOrd, $valBusq, $maxRows, "Direccion de Correo");
			$htmlTh .= ordenarCampo("xajax_ListServicioPendiente", "10%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Telefono");
			$htmlTh .= ordenarCampo("xajax_ListServicioPendiente", "10%", $pageNum, "fecha_nacimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha de Nacimiento");
			$htmlTh .= "<td><button type=\"button\" id=\"imgMostrarCliente\" onclick=\"seleccionarTodosCheckbox();\" title=\"Seleccionar todos\"><img src=\"../img/iconos/select.png\"/></button></td>";
			$htmlTh .= "<td></td>";
			$htmlTh .= "<td></td>";
		$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$fechaNacimiento =  ($row['fecha_nacimiento']) == NULL ? "" : date('d-m-Y',strtotime($row['fecha_nacimiento']));

		$htmlTb.= sprintf("<tr class=\"%s\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='%s';\" height=\"22\">",$clase,$clase);
			
			$htmlTb .= sprintf("<td align=\"left\"><img src=\"../img/iconos/cake.png\" align=\"middle\" id=\"cumpleano%s\" title=\"Estas de cumpleaño\" style=\"display:none\"/></td>",$row['id_cliente_contacto']);
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",utf8_encode($row['nombre_apellido_cliente'])); 
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",$row['correo']);
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",$row['telf']);
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",$fechaNacimiento);
			$htmlTb .= sprintf("<td align=\"center\"><input id=\"chekAsignaActividad\" name=\"chekAsignaActividad[]\" value=\"%s\" type=\"checkbox\"/></td>",$row['id_cliente_contacto']);
			$htmlTb .= sprintf("<td align=\"left\">
			<button type=\"button\" id=\"butMostraVehiculo%s\" name=\"butMostraVehiculo\" class=\"close\" onclick=\"mostrarOcultatVehiculo(%s)\" title=\"Ver Vehículos del Cliente\"><img src=\"../img/iconos/plus.png\" align=\"right\" title=\"Ver\" class=\"puntero\"  id=\"imgMostrarCliente\"/></button>
			<button type=\"button\" id=\"butOcultarVehiculo%s\" name=\"butOcultarVehiculo\" class=\"close\" style=\"display:none\" onclick=\"mostrarOcultatVehiculo(%s)\" title=\"Ocultar Vehículos del Cliente\">
			<img src=\"../img/iconos/minus.png\" align=\"right\" title=\"Oclutar\" class=\"puntero\" id=\"imgOcultarCliente\"/></button></td>",$row['id_cliente_contacto'],$row['id_cliente_contacto'],$row['id_cliente_contacto'],$row['id_cliente_contacto']);
			$htmlTb .= sprintf("<td><a id=\"aNuevo2\" rel=\"#divFlotante5\" class=\"modalImg\" onclick=\"abrirNuevaAsignacionPostVenta(this,%s,'%s')\" title=\"Registrar Actividad\"><button type=\"button\" id=\"butSeguimiento%s\" name=\"butSeguimiento\" ><img src=\"../img/iconos/ico_aceptar_azul.png\" align=\"right\" class=\"puntero\" id=\"imgAsignarActividad\"/></button></a></td>",$row['id_cliente_contacto'],utf8_encode($row['nombre_apellido_cliente']),$row['id_cliente_contacto']);
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr>";
			$htmlTb .= sprintf("<td colspan=\"8\" id=\"clienteContacto%s\" style=\"display:none\"\></td>",$row['id_cliente_contacto']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;", 
						$contFila, 
						$totalRows);
					$htmlTf .= sprintf("<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"%s\"/>",$valBusq);
					$htmlTf .= sprintf("<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"%s\"/>",$campOrd);
					$htmlTf .= sprintf("<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"%s\"/>",$tpOrd);
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_ListServicioPendiente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_ListServicioPendiente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_ListServicioPendiente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_ListServicioPendiente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_ListServicioPendiente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";

				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divServicio","innerHTML",$htmlFromIni.$htmlTableIni.$thmlCap.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin.$htmlFromFin);
		
	$objResponse->loadCommands(cumpleano());

	return $objResponse;	
}

//SELECIONA LOS INTEGRANTES SEGUN EL EQUIPO SELECCIONADO
function selectIntegrante($fechaSeleccionada,$idEquipo){
	$objResponse = new xajaxResponse();

	//CONSULA LOS INTEGRANTE DEL EQUIPO
	$sqlIntegrantes = sprintf("SELECT crm_equipo.id_equipo, jefe_equipo, id_integrante_equipo, crm_integrantes_equipos.id_empleado, nombre_empleado, apellido, crm_integrantes_equipos.activo
	FROM crm_equipo
	INNER JOIN crm_integrantes_equipos ON crm_equipo.id_equipo = crm_integrantes_equipos.id_equipo
	INNER JOIN pg_empleado ON crm_integrantes_equipos.id_empleado = pg_empleado.id_empleado
	WHERE crm_integrantes_equipos.activo = %s
		AND crm_equipo.id_equipo = %s
		AND (crm_integrantes_equipos.id_empleado = %s
			OR (crm_equipo.jefe_equipo = %s
			OR %s NOT IN (SELECT int_equipo.id_empleado FROM crm_integrantes_equipos int_equipo)));",
	valTpDato(1, "int"),
	valTpDato($idEquipo, "int"),
	valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
	valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
	valTpDato($_SESSION['idEmpleadoSysGts'],"int"));
	mysql_query("SET NAMES 'utf8'");
	$queryIntegrantes = mysql_query($sqlIntegrantes);
	if (!$queryIntegrantes) return $objResponse->alert("Error al mostar los integrantes del equipos \n\n".mysql_error()."\n\nLine: ".__LINE__);
	$arrayIntegrantes;
//$objResponse->alert($_SESSION['idEmpleadoSysGts']."\n".$sqlIntegrantes);
	while ($filaIntegrantes = mysql_fetch_array($queryIntegrantes)){
		$integrantes .= $filaIntegrantes['nombre_empleado']. " " .$filaIntegrantes['apellido']. "</br>";		
		$arrayIntegrantes[$filaIntegrantes['id_integrante_equipo']] = $filaIntegrantes['nombre_empleado']. " " .$filaIntegrantes['apellido']; 
	}
	
	$objResponse->assign("tdTituloTipoEquipo", "innerHTML", "<h2>".date("l, F d Y", strtotime($fechaSeleccionada))."</h2>");

	//LLAMADA A LA FUNCION PHP
	$Result1 = tablaHora($fechaSeleccionada, $arrayIntegrantes); 
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$table = $Result1[1];
	}
	
	$objResponse->assign("tablaHora", "innerHTML", $table);

	return $objResponse;
}

//REGISTRO LAS FUNCIONES
$xajax->register(XAJAX_FUNCTION, "asignarEmpresa");
$xajax->register(XAJAX_FUNCTION, "asignarCliente");
$xajax->register(XAJAX_FUNCTION, "buscarClienteServicio");
$xajax->register(XAJAX_FUNCTION, "buscarCliente");

$xajax->register(XAJAX_FUNCTION, "cargarLstActivida"); 
$xajax->register(XAJAX_FUNCTION, "cargarDatos"); 
$xajax->register(XAJAX_FUNCTION, "cargarLstEquipo"); 
$xajax->register(XAJAX_FUNCTION, "cargarlistHora"); 
$xajax->register(XAJAX_FUNCTION, "cargarLstTipoEquipo");
$xajax->register(XAJAX_FUNCTION, "cargarLstIntegranteEquipo"); 
$xajax->register(XAJAX_FUNCTION, "cargarLstMeses");
$xajax->register(XAJAX_FUNCTION, "cumpleano");

$xajax->register(XAJAX_FUNCTION, "eliminarActivida"); 
$xajax->register(XAJAX_FUNCTION, "eliminarTrRango"); 

$xajax->register(XAJAX_FUNCTION, "finalizaActividadAutomatica");
$xajax->register(XAJAX_FUNCTION, "formCargarKm");

$xajax->register(XAJAX_FUNCTION, "generarGrafico"); 
$xajax->register(XAJAX_FUNCTION, "generarGrafico2");
$xajax->register(XAJAX_FUNCTION, "historicoActividad");
$xajax->register(XAJAX_FUNCTION, "historicoServicio");

$xajax->register(XAJAX_FUNCTION, "guardaActividadAgenda");
$xajax->register(XAJAX_FUNCTION, "guardaActividadPostVenta");
$xajax->register(XAJAX_FUNCTION, "guardaActividadAuto");
$xajax->register(XAJAX_FUNCTION, "guardarKm");

$xajax->register(XAJAX_FUNCTION, "listaActDiaActual");  
$xajax->register(XAJAX_FUNCTION, "listaActAtrasadasDia");
$xajax->register(XAJAX_FUNCTION, "listaActDiaSiguiente");
$xajax->register(XAJAX_FUNCTION, "listaActPorEquipoDet");
$xajax->register(XAJAX_FUNCTION, "listaDetActIntegrante");
$xajax->register(XAJAX_FUNCTION, "listaActTresMeses");
$xajax->register(XAJAX_FUNCTION, "listaActFinalizadasAuto");
$xajax->register(XAJAX_FUNCTION, "listaActFinalizadasTarde");
$xajax->register(XAJAX_FUNCTION, "listaActFinalizadasNoEfectiva");
$xajax->register(XAJAX_FUNCTION, "listaActFinalizadasEfectiva");
$xajax->register(XAJAX_FUNCTION, "listaActAsignadas");
$xajax->register(XAJAX_FUNCTION, "listaActFinalizadas");
$xajax->register(XAJAX_FUNCTION, "listTipoActividad");
$xajax->register(XAJAX_FUNCTION, "listMostrarVehiculos");
$xajax->register(XAJAX_FUNCTION, "ListServicioPendiente");
$xajax->register(XAJAX_FUNCTION, "listaActSemPasada");
$xajax->register(XAJAX_FUNCTION, "listaCliente"); 

$xajax->register(XAJAX_FUNCTION, "selectIntegrante");
$xajax->register(XAJAX_FUNCTION, "culo");



// CAPTURA EL IDUSUARIOSYSGTS PARA SABER SI EL USUARIO DE VENTAS O POSTVENTA
function buscaTipo(){
//AVERIGUAR VENTA O POSTVENTA
	$queryUsuario = sprintf("SELECT id_usuario, nombre_usuario,
        CONCAT_WS(' ', nombre_empleado, apellido) AS nombre,
        clave_filtro,
		(CASE clave_filtro
			  WHEN 1 THEN 'Ventas'		
              WHEN 2 THEN 'Ventas'
			  WHEN 4 THEN 'Postventa'
              WHEN 5 THEN 'Postventa'
              WHEN 6 THEN 'Postventa'
              WHEN 7 THEN 'Postventa'
              WHEN 8 THEN 'Postventa'
              WHEN 26 THEN 'Postventa'
              WHEN 400 THEN 'Postventa'
		END) AS tipo
        
	FROM pg_usuario
		INNER JOIN pg_empleado ON pg_usuario.id_empleado = pg_empleado.id_empleado
		INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
	WHERE id_usuario = %s ",
	valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryUsuario);
	$row = mysql_fetch_array($rsUsuario);

	return array(true, $row['tipo']);
}

//FINALIZAR ACTIVIDADES AUTOMATICA CADA SEMANAS 2
function finalizaActividadAutomatica(){
	
	mysql_query("START TRANSACTION;");

	$sqlTipo = "UPDATE crm_actividades_ejecucion SET
		estatus = 3
	WHERE DATE(fecha_asignacion) < DATE(DATE_SUB(CURDATE(),INTERVAL 1 WEEK))
		AND estatus = 1 order by fecha_asignacion";
	
	mysql_query("COMMIT;");	
			
	$queryTipo = mysql_query($sqlTipo);
	
	return $queryTipo;
}
	
function tablaHora($fechaSeleccionada, $arrayIntegrantes){

	$fechaSeleccionada = implode("-",array_reverse(explode("-",$fechaSeleccionada)));
	$horaInicio= date("H:i",strtotime("07:00"));
	$interval = 30;
	$horaFin =  date("H:i",strtotime("19:00"));
	
	$arrayHoras[] = $horaInicio;
	
	$resta = abs(date("H", strtotime($horaInicio)) - date("H", strtotime($horaFin)));
	
	$aux = 0;
	$table = "<table border=\"0\" width=\"100%\">";
	$table .= "<tr align=\"center\" class=\"tituloColumna\">";
		$table .= "<td width=\"10%\">Horas</td>";
	foreach ($arrayIntegrantes as $idIntegrante => $nombreIntegrante){
		$table.= "<td width=\"".(90 / count($arrayIntegrantes))."%\">".$nombreIntegrante."</td>";
	}
	$table .= "</tr>";
	
	while ($arrayHoras[$aux] != $horaFin){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$arrayHoras[$aux+1] = date("H:i", strtotime("+ ".$interval." minutes", strtotime($arrayHoras[$aux])));
		
		$table .= sprintf("<tr align=\"left\" class=\"%s\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='%s';\" height=\"24\">",$clase,$clase);
		$table .= sprintf("<td align='center'>%s</td>",date("h:i A",strtotime($arrayHoras[$aux+1])));
		$aux++;
		
		foreach ($arrayIntegrantes as $idIntegrante => $nombreIntegrante){
			$fechaHora1 = $fechaSeleccionada." ".$arrayHoras[$aux];
			$fechaHora2 = $fechaSeleccionada." ".date("H:i",strtotime("+ ".$interval." minutes", strtotime($arrayHoras[$aux])));
			
			$sqlActividadEjecucion = sprintf("SELECT
					id_actividad_ejecucion,
					id_integrante_equipo,
					crm_actividades_ejecucion.id_actividad,
					estatus,
					nombre_actividad,
					crm_actividades_ejecucion.id,
					CONCAT_WS('',nombre,apellido) AS nomre_cliente,				
					crm_actividad.tipo,
					notas,
					fecha_asignacion,
					fecha_creacion
				FROM crm_actividades_ejecucion
					LEFT JOIN crm_actividad ON  crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
					LEFT JOIN cj_cc_cliente ON cj_cc_cliente.id = crm_actividades_ejecucion.id
				WHERE id_integrante_equipo = %s AND fecha_asignacion >= %s AND fecha_asignacion <  %s", 
					valTpDato($idIntegrante,"int"),
				valTpDato($fechaHora1,"text"),
				valTpDato($fechaHora2,"text"));
			$queryActividadEjecucion = mysql_query($sqlActividadEjecucion);
			if (!$queryActividadEjecucion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rsfilas = mysql_fetch_array($queryActividadEjecucion);
			
			$idActividaEjecucion = ($rsfilas["id_actividad_ejecucion"] != "") ? $rsfilas["id_actividad_ejecucion"] : 0 ;

			//VALIDO SI TIENE FECHA
			$fechaCreacion = ($rsfilas['fecha_creacion']) ? date('d-m-Y h:i a', strtotime($rsfilas['fecha_creacion'])) : "";
			
			$fechaAsignacion = $rsfilas['fecha_asignacion'];
			
			//ASIGNAR IMAGEN SEGUN EL ESTATUS
			switch ($rsfilas['estatus']){
				case "0" : $imgEstatus = "<img src='../img/iconos/ico_aceptar.gif'/>"; break;
				case "1" : $imgEstatus = "<img src='../img/iconos/ico_aceptar_azul.png'/>"; break;
				case "2" : $imgEstatus = "<img src='../img/cita_entrada_retrazada.png'/>"; break;
				case "3" : $imgEstatus = "<img src='../img/iconos/arrow_rotate_clockwise.png'/>"; break;
				default; $imgEstatus = ""; break;
			}
			
			//SABER SI ES TIENE ACTIVIDAD REGISTRADA A ESA HORA
			if ($rsfilas["nombre_actividad"] == NULL) {
				$atrasada = NULL;
			} else {
				if (strtotime($fechaAsignacion) < strtotime(date("Y-m-d H:i:s")) && $rsfilas['estatus'] == "1"){
					$atrasada = "<img alt='' src='../img/cita_programada_incumplida.png'/>";
				} else {
					$atrasada = NULL;	
				}
			}
		
			$table .= sprintf("<td class=\"modalImg puntero\" onClick=\"openImg(this);abrirNuevaAsignacion(%s,%s,%s)\" rel=\"#divFlotante\">".
				"<table border=\"0\">".	
					"<tr><td colspan =\"2\">%s</td></tr>".
					"<tr><td><b>%s</b></td><td align=\"center\">%s</td></tr>".
					"<tr><td>%s</td><td>%s</td></tr>".
				"</table></td>",
				valTpDato($idIntegrante,"int"),valTpDato($arrayHoras[$aux],"text"),valTpDato($idActividaEjecucion,"int"),
				$rsfilas["nombre_actividad"],
				$rsfilas['nomre_cliente'],$imgEstatus,
				$fechaCreacion,$atrasada); 
		}
		$table .= "</tr>";
		
		if ($arrayHoras[$aux] == $horaFin || date("H",strtotime($arrayHoras[$aux])) >= date("H",strtotime($horaFin))){
			break;
		}
	}
	$table .= '</table>';
	
	return array(true, $table);
}	
?>