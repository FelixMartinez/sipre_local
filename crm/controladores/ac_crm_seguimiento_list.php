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
		case "divFlotante6": 
			$inputTextId = "textIdEmpresaPosibleCierre";
			$inputText = "textEmpresaPosibleCierre";
		break;
		default: 
			$inputTextId = "txtIdEmpresa";
			$inputText = "txtEmpresa";
		break;
	}
	$objResponse->assign($inputTextId,"value",$rows['id_empresa_reg']);
	$objResponse->assign($inputText,"value",sprintf("%s (%s)",$rows['nombre_empresa'],$rows['sucursal']));

	return $objResponse;
}

function asignarEmpleado($idEmpleado, $idEmpresa){
	$objResponse = new xajaxResponse();

		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");

		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));

		$queryEmpleado = sprintf("SELECT
			vw_pg_empleado.id_empleado,
			vw_pg_empleado.cedula,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado.nombre_cargo
		FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
		$rsEmpleado = mysql_query($queryEmpleado);
		if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowEmpleado = mysql_fetch_assoc($rsEmpleado);

		$objResponse->assign("hddIdEmpleado","value",$rowEmpleado['id_empleado']);
		$objResponse->assign("txtNombreEmpleado","value", utf8_encode($rowEmpleado['nombre_empleado']));
	
	return $objResponse;
}

function asignarModelo($idUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
		uni_bas.pvp_venta1
	FROM an_uni_bas uni_bas
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas)
	WHERE uni_bas.id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdUnidadBasica","value",$idUnidadBasica);
	$objResponse->assign("txtUnidadBasica","value",utf8_encode($row['vehiculo']));
	$objResponse->assign("txtPrecioUnidadBasica","value",number_format($row['pvp_venta1'], 2, ".", ","));
	
	return $objResponse;
}

function buscarCliente($frmBusCliente, $frmSeguimiento) {
	$objResponse = new xajaxResponse();
	
	if($frmBusCliente['lstTipoCuentaCliente'] == "-1"){
		$objResponse->script("byId('lstTipoCuentaCliente').className = 'inputErrado';");
		return $objResponse->alert("Debe Seleccionar el tipo de Cliente");
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmSeguimiento['txtIdEmpresa'],
		$frmBusCliente['lstTipoPago'],
		$frmBusCliente['lstEstatusBuscar'],
		$frmBusCliente['lstPagaImpuesto'],
		$frmBusCliente['lstTipoCuentaCliente'],
		$frmBusCliente['txtCriterio']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarModelo($frmBuscarModelo, $frmSeguimiento) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmSeguimiento['txtIdEmpresaBuscarModelo'],
		$frmBuscarModelo['txtCriterioBuscarModelo']);

	$objResponse->loadCommands(listaModelo(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarPosibleCierre($frmBuscarPosibleCierre) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s||%s",
		$frmBuscarPosibleCierre['textHddIdEmpresa'],
		$frmBuscarPosibleCierre['textCriterioPosibleCierre']);

	$objResponse->loadCommands(listaPosibleCierre(0, "posicion_posibilidad_cierre", "", $valBusq));
	
	return $objResponse;
}

function buscarSeguimiento($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstPosibilidadCierreBus'],
		$frmBuscar['textDesdeCita'],
		$frmBuscar['textHastaCita'],
		$frmBuscar['textDesdeCreacion'],
		$frmBuscar['textHastaCreacion'],
		$frmBuscar['listVendedorEquipo'],
		$frmBuscar['textCriterio']);

	$objResponse->loadCommands(lstSeguimiento(0, "seguimiento.id_seguimiento","ASC", $valBusq));
	
	return $objResponse;
}

function cargarDatos($idSeguimiento = "", $idCliente = ""){
	$objResponse = new xajaxResponse();

	$objResponse->script("
		byId('hddIdPerfilProspecto').value = '';
		byId('hddIdClienteProspecto').value = '';
		byId('hddIdSeguimiento').value = '';
	");
	
	$raTipoControlTrafico = false;
	if($idSeguimiento != "" || $idCliente != ""){
		$raTipoControlTrafico = true;
		if ($idSeguimiento != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("seguimiento.id_seguimiento = %s",
				valTpDato($idSeguimiento, "int"));
		}
		
		if ($idCliente != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cliente.id = %s",
				valTpDato($idCliente, "int"));
		}
		
		$query = sprintf("SELECT cliente.id, id_perfil_prospecto, perfil.id, seguimiento.id_seguimiento, seguimiento.id_cliente, diario.id_seguimiento_diario,
			nombre, apellido, CONCAT_WS('-',lci, ci) AS ci, nit, contribuyente,  cargo, ocupacion, estado_civil, nota, 
			urbanizacion, calle, casa, municipio, estado, direccion, telf, otrotelf, correo, urbanizacion_comp, calle_comp, casa_comp, 
			municipio_comp, estado_comp, telf_comp, otro_telf_comp, correo_comp, direccionCompania, contacto, lci2, cicontacto, 
			telfcontacto, correocontacto, reputacionCliente, descuento, fcreacion, status, codigocontable, credito, tipocliente, 
			fdesincorporar, ciudad, id_clave_movimiento_predeterminado, fecha_creacion_prospecto, fechaUltimaAtencion, 
			fechaUltimaEntrevista, fechaProximaEntrevista, cliente.id_empleado_creador, tipo_cuenta_cliente, vehiculo, plan, medio, 
			nivelDeInteres, paga_impuesto, bloquea_venta, tipo,
			(CASE cliente.tipo
					WHEN 'Natural' THEN 1
					WHEN 'Juridico' THEN 2
			END) AS id_tipo,
			perfil.id_puesto, perfil.id_titulo, perfil.id_posibilidad_cierre,  perfil.id_sector, perfil.id_nivel_influencia, 
			perfil.id_motivo_rechazo, perfil.id_estatus, perfil.fecha_creacion, perfil.fecha_actualizacion, perfil.compania, 
			perfil.id_estado_civil, perfil.sexo, perfil.fecha_nacimiento, perfil.clase_social, perfil.observacion,    
			seguimiento.id_empleado_creador, seguimiento.id_empleado_actualiza, seguimiento.id_empresa, seguimiento.observacion_seguimiento,
			diario.id_equipo, diario.id_empleado_vendedor, diario.fecha_registro, fecha_asignacion_vendedor
			
		FROM cj_cc_cliente cliente
		LEFT JOIN crm_perfil_prospecto perfil ON cliente.id = perfil.id 
		LEFT JOIN crm_seguimiento seguimiento ON  seguimiento.id_cliente = cliente.id
		LEFT JOIN crm_seguimiento_diario diario ON  diario.id_seguimiento = seguimiento.id_seguimiento
		%s",$sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
		$row = mysql_fetch_assoc($rs);
	}

	if($raTipoControlTrafico == true){
		$rdTipoTrafico = ($row['tipo_cuenta_cliente'] == 1) ? "rdProspecto" : "rdCliente"; // 1 = prospecto; 3 = prospecto aprobado
		$objResponse->assign($rdTipoTrafico,"checked",true);	
	}
	$objResponse->call("selectedOption","lstTipoProspecto",$row['id_tipo']); //1 = Natural; 2 = Juridico
	$objResponse->assign("txtCedulaProspecto","value",$row['ci']);
	$objResponse->assign("txtNombreProspecto","value",utf8_encode($row['nombre']));
	$objResponse->assign("txtApellidoProspecto","value",utf8_encode($row['apellido']));
	$objResponse->assign("txtUrbanizacionProspecto","value",utf8_encode($row['urbanizacion']));
	$objResponse->assign("txtCalleProspecto","value",utf8_encode($row['calle']));
	$objResponse->assign("txtCasaProspecto","value",utf8_encode($row['casa']));
	$objResponse->assign("txtMunicipioProspecto","value",utf8_encode($row['municipio']));
	$objResponse->assign("txtCiudadProspecto","value",utf8_encode($row['ciudad']));
	$objResponse->assign("txtEstadoProspecto","value",utf8_encode($row['estado']));
	$objResponse->assign("txtTelefonoProspecto","value",utf8_encode($row['telf']));
	$objResponse->assign("txtOtroTelefonoProspecto","value",utf8_encode($row['otrotelf']));
	$objResponse->assign("txtEmailProspecto","value",utf8_encode($row['correo']));
	$objResponse->assign("txtUrbanizacionComp","value",utf8_encode($row['urbanizacion_comp']));
	$objResponse->assign("txtCalleComp","value",utf8_encode($row['calle_comp']));
	$objResponse->assign("txtCasaComp","value",utf8_encode($row['casa_comp']));
	$objResponse->assign("txtMunicipioComp","value",utf8_encode($row['municipio_comp']));
	$objResponse->assign("txtEstadoComp","value",utf8_encode($row['estado_comp']));
	$objResponse->assign("txtTelefonoComp","value",utf8_encode($row['telf_comp']));
	$objResponse->assign("txtOtroTelefonoComp","value",utf8_encode($row['otro_telf_comp']));
	$objResponse->assign("txtEmailComp","value",utf8_encode($row['correo_comp']));
	
	//DATOS DEL PERFIL
	$objResponse->assign("txtCompania","value",utf8_encode($row['compania']));
	$objResponse->loadCommands(cargaLstEstadoCivil($row['id_estado_civil']));
	$objResponse->loadCommands(cargarLstEstatus($row['id_estatus']));
	$objResponse->loadCommands(cargaLstMotivoRechazo("",$row['id_motivo_rechazo']));//
	$objResponse->loadCommands(cargarLstNivelInfluencia($row['id_nivel_influencia']));
	$objResponse->call("selectedOption","lstNivelSocial",$row['clase_social']);
	$objResponse->loadCommands(cargarLstPuesto($row['id_puesto']));
	$objResponse->loadCommands(cargarLstSector($row['id_sector']));
	$objResponse->loadCommands(cargarLstTitulo($row['id_titulo']));
	$fechNa = ($row['fecha_nacimiento'] != "") ? date("d-m-Y", strtotime($row['fecha_nacimiento'])) : "";
	$objResponse->assign("txtFechaNacimiento","value",$fechNa);
	$rdSexo = ($row['sexo'] == "M") ? "rdbSexoM" : "rdbSexoF";
	$objResponse->assign($rdSexo,"checked",true);
	$objResponse->assign("txtObservacion","value",utf8_decode($row['observacion']));
	
	//CONSULTA LA POSIBILIDA DE CIERRE
	if($row['id_posibilidad_cierre'] != ""){
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("id_posibilidad_cierre = %s",
			valTpDato($row['id_posibilidad_cierre'], "int"));
	}else{
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("por_defecto = %s",
			valTpDato(1, "int"));
	}
	
	$idEmpresa = ($row['id_empresa']!= "") ? valTpDato($row['id_empresa'], "int") : $_SESSION['idEmpresaUsuarioSysGts'];
	$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond2.sprintf("id_empresa = %s",
		$idEmpresa) ;
				
	$query2 = sprintf("SELECT * FROM crm_posibilidad_cierre %s",$sqlBusq2);
	$rs2 = mysql_query($query2);
	if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row2 = mysql_fetch_assoc($rs2);
//$objResponse->alert($query2);	
	$objResponse->loadCommands(cargarLstPosibilidadCierre($row2['id_posibilidad_cierre'],"tdLstPosibilidadCierre",$idEmpresa));
	$objResponse->script(sprintf("
		byId('lstPosibilidadCierre').onchange = function() {
			selectedOption(this.id,%s);
		}",
	($row2['id_posibilidad_cierre'] !="") ? $row2['id_posibilidad_cierre'] : "-1"));
		
	//BUSCA LA IMG DE POSIBILIDAD DE CIEERE
	$imgFoto = (!file_exists($row2['img_posibilidad_cierre'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row2['img_posibilidad_cierre'];
	$objResponse->assign("imgPosibleCierrePerfil","src",$imgFoto);		
	
	//DATOS DE ENTREVISTA
	$fechAte = ($row['fechaUltimaAtencion'] != "") ? date("d-m-Y", strtotime($row['fechaUltimaAtencion'])) : "";
	$fechEnr = ($row['fechaUltimaEntrevista'] != "") ? date("d-m-Y", strtotime($row['fechaUltimaEntrevista'])) : "";
	$fechProxEnt = ($row['fechaProximaEntrevista'] != "") ? date("d-m-Y", strtotime($row['fechaProximaEntrevista'])) : "";
	
	$objResponse->assign("txtFechaUltAtencion","value", $fechAte);
	$objResponse->assign("txtFechaUltEntrevista","value", $fechEnr);
	$objResponse->assign("txtFechaProxEntrevista","value", $fechProxEnt);
	
	$objResponse->assign("hddIdPerfilProspecto","value",$row['id_perfil_prospecto']);
	$objResponse->assign("hddIdClienteProspecto","value",$row['id']);
	$objResponse->assign("hddIdSeguimiento","value",$row['id_seguimiento']);

	//MODE LO DE INTERES
	//ELIMINA LAS MODE PARA EVITAR LA DUPLICACION
	$objResponse->script("xajax_eliminarModelo(xajax.getFormValues('frmSeguimiento'),false);");
	// BUSCA LOS MODELOS DE INTERES
	$query2 = sprintf("SELECT 
		id_prospecto_vehiculo,
		id_cliente,
		id_unidad_basica,
		precio_unidad_basica,
		id_medio,
		id_nivel_interes,
		id_plan_pago
	FROM an_prospecto_vehiculo prosp_vehi
	WHERE id_cliente = %s;",
		valTpDato($row['id'], "int"));
	$rs2 = mysql_query($query2);
	if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row2 = mysql_fetch_assoc($rs2)) {
		$Result1 = insertarItemModeloInteres($contFila, $row2['id_prospecto_vehiculo']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj1[] = $contFila;
		}
	}

	$objResponse->assign("textAreaObservacion","value",utf8_encode($row['observacion_seguimiento']));
	
	//CONSULTO EN LA TABLA DE INTEGRANTE Y SACO EL EQUIO Y SELECCIONO EL VENTEDOR	
	if($row['id_empleado_vendedor'] != ""){
		$objResponse->loadCommands(cargaLstEquipo($row['id_empresa'],$row['id_equipo']));	
		$objResponse->loadCommands(insertarIntegrante($row['id_equipo'],$row['id_empleado_vendedor']));
	}else{
		$objResponse->script("$('.remover').remove();");
	}

	return $objResponse;
}

function cargarDtosAsignacion($idSeguimiento,$idActividad){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT seguimiento.id_seguimiento,seguimiento.id_cliente, CONCAT_WS(' ',nombre,cliente.apellido) AS nombre_cliente,
		seguimiento_diario.id_equipo,
		seguimiento_diario.id_empleado_vendedor, (SELECT CONCAT_WS(' ',nombre_empleado,empld.apellido) AS nombre_empleado 
				FROM pg_empleado empld 
				WHERE empld.id_empleado = seguimiento_diario.id_empleado_vendedor ) AS nombre_empleado
	FROM crm_seguimiento seguimiento
		INNER JOIN cj_cc_cliente cliente ON cliente.id = seguimiento.id_cliente
		INNER JOIN crm_seguimiento_diario seguimiento_diario ON seguimiento_diario.id_seguimiento = seguimiento.id_seguimiento
	WHERE seguimiento.id_seguimiento = %s",
	valTpDato($idSeguimiento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rows = mysql_fetch_assoc($rs);	
	
	if($rows['id_empleado_vendedor'] == ""){
		return $objResponse->alert("Debe Asignar un Vendedor al Seguimiento");
	}
	
	// CONSULTA EL ID DEL INTEGRANTE
	$query2 = sprintf("SELECT * FROM crm_integrantes_equipos
		WHERE activo = %s AND id_equipo = %s AND id_empleado = %s",
	valTpDato(1,"int"),
	valTpDato($rows['id_equipo'],"int"),
	valTpDato($rows['id_empleado_vendedor'],"int"));
	$rs2 = mysql_query($query2);
	if (!$rs2) {return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
	$rows2 = mysql_fetch_assoc($rs2);
	$objResponse->loadCommands(cargaLstActividad($idActividad));
	$objResponse->assign("hddIdIntegrante","value", $rows2['id_integrante_equipo']);
	$objResponse->assign("textIdEmpVendedor","value", $rows['id_empleado_vendedor']);
	$objResponse->assign("nombreVendedor","value", utf8_encode($rows['nombre_empleado']));
	$objResponse->assign("textFechAsignacion","value", date("d-m-Y"));
	$objResponse->assign("idClienteHidd","value", $rows['id_cliente']);
	$objResponse->assign("textNombreCliente","value", utf8_encode($rows['nombre_cliente']));
	$objResponse->assign("hddIdSeguimientoAct","value", $rows['id_seguimiento']);
	$objResponse->assign("hddIdEquipo","value", $rows['id_equipo']);	
	
	$objResponse->script("xajax_cargarListHora(xajax.getFormValues('formAsignarActividadSeg'));");
		
	return $objResponse;
}

function cargaLstActividad($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM crm_actividad");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_actividad']) ? "selected=\"selected\"" : "";
		if ($selId == $row['id_actividad']) {$tipActividad = $row['tipo'];}
		$htmlOption .= sprintf("<option %s value=\"%s\">%s</option>",$selected,$row['id_actividad'],utf8_encode($row['nombre_actividad']));
	}
	
	$html .= sprintf("<select id=\"lstActividadSeg\" name=\"lstActividadSeg\" onchange=\"selectedOption(this.id,%s);\" style=\"width:150px\">",$selId);
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	switch($tipActividad){
		case "Postventa": $objResponse->assign("tdNombreCliente","innerHTML","Nombre del Cliente:"); break;
		default: $objResponse->assign("tdNombreCliente","innerHTML","Nombre del Prospecto:"); break;
	}
	$objResponse->assign("txtTipoActividad","value", $tipActividad);
	$objResponse->assign("tdListActividad","innerHTML",$html);
	
	return $objResponse;
}


function cargaLstEstadoCivil($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'estadoCivil' AND git.status = 1
	ORDER BY item");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$html = "<select id=\"lstEstadoCivil\" name=\"lstEstadoCivil\" class=\"inputHabilitado\" style=\"width:150px\">";
			$html .= "<option value=\"\">[ Seleccione ]</option>";
			while ($row = mysql_fetch_assoc($rs)) {
				$selected = ($selId == $row['idItem']) ? "selected=\"selected\"" : "";
				$html .= sprintf("<option %s value=\"%s\">%s</option>",$selected,$row['idItem'],utf8_encode($row['item']));
			}
		$html .= "</select>";
	$objResponse->assign("tdlstEstadoCivil","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEquipo($idEmpresa, $selId = "", $tipo = "Ventas") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT id_equipo, nombre_equipo FROM crm_equipo 
		WHERE activo = %s AND tipo_equipo = %s AND id_empresa = %s",
	valTpDato(1,"int"),
	valTpDato($tipo,"text"),
	valTpDato($idEmpresa,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEquipo\" name=\"lstEquipo\" class=\"inputHabilitado\" style=\"width:150px\" onchange=\"xajax_insertarIntegrante(this.value);\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_equipo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_equipo']."\">".utf8_encode($row['nombre_equipo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdTipoEquipo","innerHTML","Equipo de ".$tipo);
	$objResponse->assign("tdLstEquipo","innerHTML",$html);
	
	return $objResponse;
}

function cargarLstEstatus($idEstatus = "") {
	$objResponse = new xajaxResponse();

	// LLAMA SELECT ESTATUS
	$query = sprintf("SELECT id_estatus, nombre_estatus FROM crm_estatus
		WHERE activo = %s
		AND id_empresa = %s;",
	valTpDato(1, "int"),
	valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	$numRs = mysql_num_rows($rs);
	while ($rows = mysql_fetch_array($rs)) {
		$selected = ($rows['id_estatus'] == $idEstatus) ? "selected=\"selected\"" : "";
		$htmlOption .= sprintf("<option %s value=\"%s\">%s</option>",$selected,$rows['id_estatus'],utf8_encode($rows['nombre_estatus']));
	}
	
	$html = "<select id=\"lstEstatus\" name=\"lstEstatus\" class=\"inputHabilitado\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign('td_select_estatus', 'innerHTML', $html);
	
	return $objResponse;
}

function cargarListHora($datosActSeg){
	$objResponse = new xajaxResponse();

	//CONSULTA LAS ACTIVIDADES ASIGNADA PARA EL INTEGRANTE DE EQUIPO
	$sql = sprintf("SELECT crm_equipo.id_equipo, crm_integrantes_equipos.id_integrante_equipo, fecha_asignacion
			FROM crm_equipo
			LEFT JOIN crm_integrantes_equipos ON crm_integrantes_equipos.id_equipo = crm_equipo.id_equipo
			LEFT JOIN crm_actividades_ejecucion ON crm_actividades_ejecucion.id_integrante_equipo = crm_integrantes_equipos.id_integrante_equipo
		WHERE DATE(fecha_asignacion) = %s AND crm_equipo.id_equipo = %s AND crm_actividades_ejecucion.id_integrante_equipo = %s;",
	valTpDato(date('Y-m-d', strtotime($datosActSeg['textFechAsignacion'])),"text"),
	valTpDato($datosActSeg['hddIdEquipo'], "int"),
	valTpDato($datosActSeg['hddIdIntegrante'], "int"));	
	$query = mysql_query($sql);
	
	if (!$query) return $objResponse->alert("Error: ".mysql_error(). "\n\nLine:".__LINE__);
	while($rows = mysql_fetch_array($query)){
		$fechaAsignacion = $rows["fecha_asignacion"];
		$arrayTiempo[] = date('H:i',strtotime($fechaAsignacion));
		$idActividadEjecucion = $rows["id_actividad_ejecucion"];
	}

	$horaInicio= date("H:i",strtotime("07:30"));
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
	
	$hActual = (date("H"));
	$minActual = (date("i") > "01" && date("i") < "30") ? "00" :"30";
	$horaActual = sprintf("%s:%s",$hActual,$minActual);

	foreach($horasOption as $fechaLibre){
		$selected = ($fechaLibre == $horaActual)? "selected=\"selected\"" : "";
		$selectOptionH .= sprintf("<option value=\"%s\" %s>%s</option>", $fechaLibre, $selected, date("h:i A",strtotime($fechaLibre)));
	}
	
	$selectH .= "<select id=\"listHora\" name=\"listHora\" class=\"inputHabilitado\">";
	$selectH .= "<option value=\"\">[ Seleccionar ]</option>";
		$selectH .= $selectOptionH;
	$selectH .= "</select>";
	
	$objResponse->assign("tdSelectHora","innerHTML",$selectH);
	
	return $objResponse;
}

function cargaLstMedio($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
		FROM grupositems git
			LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
		WHERE gps.grupo = 'medios' AND status = %s
		ORDER BY item",
	valTpDato(1,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMedio\" name=\"lstMedio\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['idItem']) ? "selected=\"selected\"" : "";
			$html .= sprintf("<option %s value=\"%s\">%s</option>",$selected,$row['idItem'],utf8_encode($row['item']));
		}
	$html .= "</select>";
	$objResponse->assign("tdlstMedio","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMotivoRechazo($motivo,$selId = "") {
	$objResponse = new xajaxResponse();
	$query = sprintf("SELECT * FROM crm_motivo_rechazo 
		WHERE activo = %s AND id_empresa = %s;", 
	valTpDato(1,"int"),
	valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstMotivoRechazo\" name=\"lstMotivoRechazo\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	//if($motivo == 'Rechazo') {
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['id_motivo_rechazo']) ? "selected=\"selected\"" : "";
			$html .= sprintf("<option %s value=\"%s\">%s</option>",$selected,$row['id_motivo_rechazo'],utf8_encode($row['nombre_motivo_rechazo']));
		}
	//}
	$html .= "</select>";
	$objResponse->assign("tdLstMotivoRechazo","innerHTML",$html);
	
	return $objResponse;
}

function cargarLstNivelInfluencia($idNivelInfluencia = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM crm_nivel_influencia
		WHERE activo = %s AND id_empresa = %s",
	valTpDato(1,"int"),
	valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"));
	$rs = mysql_query($query);
	$rsNum = mysql_num_rows($rs);
	$html = "<select id=\"lstNivelInfluencia\" name=\"lstNivelInfluencia\" class=\"inputHabilitado\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";				
		while ($rows = mysql_fetch_array($rs)) {
			$selected = ($rows['id_nivel_influencia'] == $idNivelInfluencia) ? "selected=\"selected\"" : "";
			$html .= sprintf("<option %s value=\"%s\">%s</option>",$selected,$rows['id_nivel_influencia'],utf8_encode($rows['nombre_nivel_influencia']));
	}
	$html .= "</select>";
	$objResponse->assign('tdLstNivelInfluencia', 'innerHTML', $html);
	
	return $objResponse;
}

function cargaLstPlanPago($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
		FROM grupositems git
			LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
		WHERE gps.grupo = 'planesDePago' AND status = %s
		ORDER BY item",
	valTpDato(1,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstPlanPago\" name=\"lstPlanPago\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idItem']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstPlanPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarLstPosibilidadCierre($idPosibilidadCierre = "", $idObjDestino = "",$idEmpresa = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM crm_posibilidad_cierre
		WHERE activo = %s AND id_empresa = %s AND fin_trafico IS NULL ORDER BY posicion_posibilidad_cierre ASC",
	valTpDato(1,"int"),
	valTpDato((($idEmpresa == "") ? $_SESSION['idEmpresaUsuarioSysGts'] : $idEmpresa) ,""));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rsNum = mysql_num_rows($rs);
	
	$idObjSelect = ($idObjDestino == "tdLstPosibilidadCierre") ? "lstPosibilidadCierre" : "lstPosibilidadCierreBus";
	$nameObjSelect = ($idObjDestino == "tdLstPosibilidadCierre") ? "lstPosibilidadCierre" : "lstPosibilidadCierreBus";
	$onchange = ($idObjDestino == "tdLstPosibilidadCierre") ? "" : "onchange=\"byId('btnBuscar').click();\"";
	$class = ($idObjDestino == "tdLstPosibilidadCierre") ? "inputInicial" : "inputHabilitado";
	
	while ($rows = mysql_fetch_array($rs)) {
		$selected = ($rows['id_posibilidad_cierre'] == $idPosibilidadCierre) ? "selected=\"selected\"" : "";
		$htmlOption .= sprintf("<option %s value=\"%s\">%s.- %s</option>",
			$selected, $rows['id_posibilidad_cierre'],$rows['posicion_posibilidad_cierre'], utf8_encode($rows['nombre_posibilidad_cierre']));
	}
	
	$html .= sprintf("<select style='width:200px' id=\"%s\" name=\"%s\" class=\"%s\" %s>",$idObjSelect,$nameObjSelect,$class,$onchange);
		$html .= '<option value="-1">[ Seleccione ]</option>';
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign($idObjDestino, 'innerHTML', $html);

	return $objResponse;
}

function cargarLstPuesto($idPuesto = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM crm_puesto 
		WHERE activo = %s AND id_empresa = %s;",
	valTpDato(1,"int"),
	valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rsNum = mysql_num_rows($rs);
	
	$html = "<select id=\"LstPuesto\" name=\"LstPuesto\" class=\"inputHabilitado\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		while ($rows = mysql_fetch_array($rs)) {
			$selected = ($rows['id_puesto'] == $idPuesto) ? "selected=\"selected\"" : "";
			$html .= sprintf('<option %s value="%s">%s</option>',$selected,$rows['id_puesto'],utf8_encode($rows['nombre_puesto']));
		}
	$html .= "</select>";
	
	$objResponse->assign('tdLstPuesto', 'innerHTML', $html);
	
	return $objResponse;
}

function cargarLstSector($idSector = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM crm_sector 
		WHERE activo = %s AND id_empresa = %s;",
	valTpDato(1, "int"),
	valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rsNum = mysql_num_rows($rs);
	
	$html = "<select id=\"LstSector\" name=\"LstSector\" class=\"inputHabilitado\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		while ($rows = mysql_fetch_array($rs)) {
			$selected = ($rows['id_sector'] == $idSector) ? "selected=\"selected\"" : "";
			$html .= sprintf("<option %s value=\"%s\">%s</option>",$selected,$rows['id_sector'],utf8_encode($rows['nombre_sector']));
		}
	$html .= "</select>";
	
	$objResponse->assign('tdLstSector', 'innerHTML', $html);
	
	return $objResponse;
}

function cargarLstTitulo($idTitulo = "") {
	$objResponse = new xajaxResponse();
	
	// LLENAR SELECT TITULO
	$query = sprintf("SELECT * FROM crm_titulo 
		WHERE activo = %s AND id_empresa = %s",
	valTpDato(1,"int"),
	valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rsNum = mysql_num_rows($rs);	
	$html = "<select id=\"lstTitulo\" name=\"lstTitulo\" class=\"inputHabilitado\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($rows = mysql_fetch_array($rs)) {
		$selected = ($rows['id_titulo'] == $idTitulo) ? "selected=\"selected\"" : "";
		$html .= sprintf("<option %s value=\"%s\">%s</option>",$selected,$rows['id_titulo'],utf8_encode($rows['nombre_titulo']));
	}
	$html .= "</select>";
	$objResponse->assign("tdLstTitulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstVendedor($idEmpresa = ""){
	$objResponse = new xajaxResponse();
	
	if($idEmpresa != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_equipo.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_equipo.activo = %s",
		valTpDato(1, "int"));
				
	$arrayClaveFiltro = claveFiltroEmpleado();
	if($arrayClaveFiltro[0] == true){//CONDICION TIPO DE EQUIPO
		if($arrayClaveFiltro[1] == 1 || $arrayClaveFiltro[1] == 2){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("crm_equipo.tipo_equipo = %s",
				valTpDato($arrayClaveFiltro[2], "int"));	
		}
		
	}else{
		$objResponse->alert($arrayClaveFiltro[1]);	
	}
	//CONSULTA LOS EQUIPO 
	$queryEquipo = sprintf("SELECT  distinct crm_equipo.id_equipo,
			nombre_equipo,
			tipo_equipo,
			crm_equipo.activo,
			crm_equipo.id_empresa 
		FROM crm_equipo 
		INNER JOIN crm_integrantes_equipos ON crm_integrantes_equipos.id_equipo = crm_equipo.id_equipo
		%s ORDER BY id_equipo ASC",$sqlBusq);
	$rsEquipo = mysql_query($queryEquipo);
	if(!$rsEquipo) return $objResponse->alert(mysql_error."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rsNumEquipo = mysql_num_rows($rsEquipo);
	$count = 0;
	while($rowEuipo = mysql_fetch_array($rsEquipo)){
		$htmlOption .= sprintf("<optgroup label=\"%s\">","Equipo - ".$rowEuipo['nombre_equipo']);

		//CONSULTA LOS EMPLEAO POR EQUIPO
		$queryEmp = sprintf("SELECT 
				vw_pg_empleado.id_empleado,
				vw_pg_empleado.nombre_empleado,
				vw_pg_empleado.nombre_cargo,
				vw_pg_empleado.clave_filtro,
				vw_pg_empleado.activo,
				crm_integrantes_equipos.id_equipo,
				crm_integrantes_equipos.activo
			FROM vw_pg_empleados vw_pg_empleado
				LEFT JOIN crm_integrantes_equipos ON  vw_pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado   
			WHERE crm_integrantes_equipos.activo = %s AND crm_integrantes_equipos.id_equipo = %s
			ORDER BY crm_integrantes_equipos.id_equipo DESC",
		valTpDato(1, "int"),
		valTpDato($rowEuipo['id_equipo'], "int"));
		$rsEmp = mysql_query($queryEmp);
		if(!$rsEmp)return $objResponse->alert(mysql_error."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rsNumEmp = mysql_num_rows($rsEmp);
		$count = 0;
		while($rowEmp =  mysql_fetch_array($rsEmp)){
			$count ++;
			$htmlOption .= sprintf("<option value=\"%s\">%s.-  %s</option>",
				$rowEmp['id_empleado'],$count,utf8_encode($rowEmp['nombre_empleado']));
		}
		
		$htmlOption .= "</optgroup>";
	}
	
	$html = "<select style='width:200px' id=\"listVendedorEquipo\" name=\"listVendedorEquipo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Selecione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdLstVendedor","innerHTML",$html);
	return $objResponse;
}

function eliminarModelo($frmProspecto,$liminarTr = true) {
	$objResponse = new xajaxResponse();
	
	switch($liminarTr){
		case true:
			if (isset($frmProspecto['cbxItmModeloInteres'])) {
				foreach($frmProspecto['cbxItmModeloInteres'] as $indiceItm=>$valorItm) {
					$objResponse->script(sprintf("
					fila = document.getElementById('trItmModeloInteres:%s');
					padre = fila.parentNode;
					padre.removeChild(fila);",
						$valorItm));
				}		
				$objResponse->script("xajax_eliminarModelo(xajax.getFormValues('frmSeguimiento'));");
			}
		break;	
		default:
			if (isset($frmProspecto['cbx1'])) {
				foreach($frmProspecto['cbx1'] as $indiceItm=>$valorItm) {
					$objResponse->script(sprintf("
					fila = document.getElementById('trItmModeloInteres:%s');
					padre = fila.parentNode;
					padre.removeChild(fila);",
					$valorItm));
				}		
				$objResponse->script("xajax_eliminarModelo(xajax.getFormValues('frmSeguimiento'));");
			}
		break;
	}

	return $objResponse;
}

function eliminarActividadSeguimiento($idSeguimiento,$frmLstSeguimiento){//
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	//CONSULTA LAS ACTIVIDADES ASIGNADAS PARA EL SEGUIMIENTO
	$query = sprintf("SELECT * FROM crm_actividad_seguimiento 
		WHERE id_seguimiento = %s",
	valTpDato($idSeguimiento, "int"));
	$rs = mysql_query($query);
	if (!$rs) {return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);}
	while($row = mysql_fetch_array($rs)){
		$existActSeguimiento = false;
		if (isset($frmLstSeguimiento['checkActividad'.$idSeguimiento])) {
			foreach($frmLstSeguimiento['checkActividad'.$idSeguimiento] as $indice => $valorChekAct){
				if($row['id_actividad'] == $valorChekAct){
					$existActSeguimiento = true;
				}
			}	
		}
		//ELIMINA LAS ACTIVIDADES QUE EXISTAN EN BD Y NO ESTEN SELECCIONADA EN EL LISTADO
		if($existActSeguimiento == false){
			//CONSULTA LA ACTIVIDAD EN EJECUCION POR ID SEGUIMIENTO DE LA ACTIVIDAD
			$query2 = sprintf("SELECT * FROM crm_actividades_ejecucion 
				WHERE id_actividad_seguimiento = %s AND 
					estatus = %s AND
					tipo_finalizacion IS NULL",
			valTpDato($row['id_actividad_seguimiento'], "int"),
			valTpDato(1, "int"));//1 es asignado. 0 es finalizado. 2 Finalizo tarde. 3 Finalizado auto		
			$rs2 = mysql_query($query2);
			if (!$rs2) {return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query2);}
			$numRows = mysql_num_rows($rs2);
			$row2 = mysql_fetch_array($rs2);
			//ELIMINA LA ACTIVIDAD DE LA AGENDA SI EL ESTATUS ES ASIGNADO
			if($numRows > 0){
				$query3 = sprintf("DELETE FROM crm_actividades_ejecucion 
					WHERE id_actividad_ejecucion = %s",
				valTpDato($row2['id_actividad_ejecucion'], "int"));
				$rs3 = mysql_query($query3);
				if (!$rs3) {return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query3);}
			}
			//ELIMINA LA ACTIVIDAD DE SEGUIMINETO
			$query4 = sprintf("DELETE FROM crm_actividad_seguimiento 
				WHERE id_actividad_seguimiento = %s",
			valTpDato($row['id_actividad_seguimiento'], "int"));
			$rs4 = mysql_query($query4);
			if (!$rs4) {return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query4);}
		}
	}
	mysql_query("COMMIT;");

	$objResponse->script("byId('btnBuscar').click();");
	
	return $objResponse;
}

function guardarSeguimiento($frmSeguimiento){
	$objResponse = new xajaxResponse();

	global $spanClienteCxC;
	global $arrayValidarCI;
	global $arrayValidarRIF;
	global $arrayValidarNIT;
	global $spanEstado;

	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmSeguimiento['txtIdEmpresa'];
	$idProspecto = $frmSeguimiento['hddIdClienteProspecto'];
	
	// TIPO DE CLIENTE NATURAL JURIDICO
	switch ($frmSeguimiento['lstTipoProspecto']) {
		case 1 :
			$lstTipoProspecto = "Natural";
			$arrayValidar = $arrayValidarCI;
			break;
		case 2 :
			$lstTipoProspecto = "Juridico";
			$arrayValidar = $arrayValidarRIF;
			break;
	}
	
	// VERIFICA SI EXISTE UN MODELO DE INTERES AGREGADO PARA EL CLIENTE
	if (!(count($frmSeguimiento['cbx1']) > 0)) {
		$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
		return $objResponse->alert("Debe agregar un modelo de interés");
	}
	// VALIDA LA CEDULA O EL RIF DEL CLIENTE
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmSeguimiento['txtCedulaProspecto'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false); 
				byId('txtCedulaProspecto').className = 'inputErrado'");
			return $objResponse->alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
		}
	}

	$txtCiCliente = explode("-",$frmSeguimiento['txtCedulaProspecto']);
	if (is_numeric($txtCiCliente[0]) == true) {
		$txtCiCliente = implode("-",$txtCiCliente);
	} else {
		$txtLciCliente = $txtCiCliente[0];
		array_shift($txtCiCliente);
		$txtCiCliente = implode("-",$txtCiCliente);
	}

	// VERIFICA QUE NO EXISTA LA CEDULA
	$query = sprintf("SELECT * FROM cj_cc_cliente
	WHERE ((lci IS NULL AND %s IS NULL AND ci LIKE %s)
			OR (lci IS NOT NULL AND lci LIKE %s AND ci LIKE %s))
		AND (id <> %s OR %s IS NULL);",
		valTpDato($txtLciCliente, "text"),
		valTpDato($txtCiCliente, "text"),
		valTpDato($txtLciCliente, "text"),
		valTpDato($txtCiCliente, "text"),
		valTpDato($idProspecto, "int"),
		valTpDato($idProspecto, "int"));
	$rs = mysql_query($query);
	if (!$rs) { 
		$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
		return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); 
	}
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
		return $objResponse->alert("Ya existe la ".$spanClienteCxC." ingresada");
	}
	
	$frmSeguimiento['txtUrbanizacionProspecto'] = str_replace(",", "", $frmSeguimiento['txtUrbanizacionProspecto']);
	$frmSeguimiento['txtCalleProspecto'] = str_replace(",", "", $frmSeguimiento['txtCalleProspecto']);
	$frmSeguimiento['txtCasaProspecto'] = str_replace(",", "", $frmSeguimiento['txtCasaProspecto']);
	$frmSeguimiento['txtMunicipioProspecto'] = str_replace(",", "", $frmSeguimiento['txtMunicipioProspecto']);
	$frmSeguimiento['txtCiudadProspecto'] = str_replace(",", "", $frmSeguimiento['txtCiudadProspecto']);
	$frmSeguimiento['txtEstadoProspecto'] = str_replace(",", "", $frmSeguimiento['txtEstadoProspecto']);

	$txtFechaUltAtencion = ($frmSeguimiento['txtFechaUltAtencion'] != "") ? date("Y-m-d",strtotime($frmSeguimiento['txtFechaUltAtencion'])) : "" ;
	$txtFechaUltEntrevista = ($frmSeguimiento['txtFechaUltEntrevista'] != "") ? date("Y-m-d",strtotime($frmSeguimiento['txtFechaUltEntrevista'])) : "" ;
	$txtFechaProxEntrevista = ($frmSeguimiento['txtFechaProxEntrevista'] != "") ? date("Y-m-d",strtotime($frmSeguimiento['txtFechaProxEntrevista'])) : "" ;
	
	$txtDireccion = implode("; ", array(
		$frmSeguimiento['txtUrbanizacionProspecto'],
		$frmSeguimiento['txtCalleProspecto'],
		$frmSeguimiento['txtCasaProspecto'],
		$frmSeguimiento['txtMunicipioProspecto'],
		$frmSeguimiento['txtCiudadProspecto'],
		((strlen($frmSeguimiento['txtEstadoProspecto']) > 0) ? $spanEstado : "")." ".$frmSeguimiento['txtEstadoProspecto']));

	//DATOS DEL PROSPECTO
	if ($idProspecto > 0) {// EDITA LOS DATOS DEL PROSPECTO
		if (!xvalidaAcceso($objResponse,"crm_seguimiento_list","editar")) { 
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse;
		}

		$updateSQLProsp= sprintf("UPDATE cj_cc_cliente SET
			urbanizacion = %s,
			calle = %s,
			casa = %s,
			municipio = %s,
			ciudad = %s,
			estado = %s,
			direccion = %s,
			telf = %s,
			otrotelf = %s,
			correo = %s,
			urbanizacion_comp = %s,
			calle_comp = %s,
			casa_comp = %s,
			municipio_comp = %s,
			estado_comp = %s,
			telf_comp = %s,
			otro_telf_comp = %s,
			correo_comp = %s,
			status = %s,
			fechaUltimaAtencion = %s,
			fechaUltimaEntrevista = %s,
			fechaProximaEntrevista = %s,
			id_empleado_creador = %s
		WHERE id = %s;",
			valTpDato($frmSeguimiento['txtUrbanizacionProspecto'], "text"),
			valTpDato($frmSeguimiento['txtCalleProspecto'], "text"),
			valTpDato($frmSeguimiento['txtCasaProspecto'], "text"),
			valTpDato($frmSeguimiento['txtMunicipioProspecto'], "text"),
			valTpDato($frmSeguimiento['txtCiudadProspecto'], "text"),
			valTpDato($frmSeguimiento['txtEstadoProspecto'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmSeguimiento['txtTelefonoProspecto'], "text"),
			valTpDato($frmSeguimiento['txtOtroTelefonoProspecto'], "text"),
			valTpDato($frmSeguimiento['txtEmailProspecto'], "text"),
			valTpDato($frmSeguimiento['txtUrbanizacionComp'], "text"),
			valTpDato($frmSeguimiento['txtCalleComp'], "text"),
			valTpDato($frmSeguimiento['txtCasaComp'], "text"),
			valTpDato($frmSeguimiento['txtMunicipioComp'], "text"),
			valTpDato($frmSeguimiento['txtEstadoComp'], "text"),
			valTpDato($frmSeguimiento['txtTelefonoComp'], "text"),
			valTpDato($frmSeguimiento['txtOtroTelefonoComp'], "text"),
			valTpDato($frmSeguimiento['txtEmailComp'], "text"),
			valTpDato("Activo", "text"),
			valTpDato($txtFechaUltAtencion, "date"),
			valTpDato($txtFechaUltEntrevista, "date"),
			valTpDato($txtFechaProxEntrevista, "date"),
			valTpDato($frmSeguimiento['txtIdEmpleado'], "int"),
			valTpDato($idProspecto, "int")); //este es el valor que se almacenar para en el perfil
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQLProsp);
		if (!$Result1) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
		
	}else{ //CREA NUEVO PROSPECTO
		if (!xvalidaAcceso($objResponse,"crm_seguimiento_list","insertar")) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse; 
		}
		// INSERTA LOS DATOS DEL PROSPECTO
		$insertSQLProsp = sprintf("INSERT INTO cj_cc_cliente (tipo, nombre, apellido, lci, ci, urbanizacion, calle, casa, municipio, ciudad, estado, direccion, telf, otrotelf, correo, urbanizacion_comp, calle_comp, casa_comp, municipio_comp, estado_comp, telf_comp, otro_telf_comp, correo_comp, status, fecha_creacion_prospecto, fechaUltimaAtencion, fechaUltimaEntrevista, fechaProximaEntrevista, id_empleado_creador, tipo_cuenta_cliente, fcreacion)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($lstTipoProspecto, "text"),
			valTpDato($frmSeguimiento['txtNombreProspecto'], "text"),
			valTpDato($frmSeguimiento['txtApellidoProspecto'], "text"),
			valTpDato($txtLciCliente, "text"),
			valTpDato($txtCiCliente, "text"),
			valTpDato($frmSeguimiento['txtUrbanizacionProspecto'], "text"),
			valTpDato($frmSeguimiento['txtCalleProspecto'], "text"),
			valTpDato($frmSeguimiento['txtCasaProspecto'], "text"),
			valTpDato($frmSeguimiento['txtMunicipioProspecto'], "text"),
			valTpDato($frmSeguimiento['txtCiudadProspecto'], "text"),
			valTpDato($frmSeguimiento['txtEstadoProspecto'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmSeguimiento['txtTelefonoProspecto'], "text"),
			valTpDato($frmSeguimiento['txtOtroTelefonoProspecto'], "text"),
			valTpDato($frmSeguimiento['txtEmailProspecto'], "text"),
			valTpDato($frmSeguimiento['txtUrbanizacionComp'], "text"),
			valTpDato($frmSeguimiento['txtCalleComp'], "text"),
			valTpDato($frmSeguimiento['txtCasaComp'], "text"),
			valTpDato($frmSeguimiento['txtMunicipioComp'], "text"),
			valTpDato($frmSeguimiento['txtEstadoComp'], "text"),
			valTpDato($frmSeguimiento['txtTelefonoComp'], "text"),
			valTpDato($frmSeguimiento['txtOtroTelefonoComp'], "text"),
			valTpDato($frmSeguimiento['txtEmailComp'], "text"),
			valTpDato("Activo", "text"),
			valTpDato("NOW()", "campo"),
			valTpDato($txtFechaUltAtencion, "date"),
			valTpDato($txtFechaUltEntrevista, "date"),
			valTpDato($txtFechaProxEntrevista, "date"),
			valTpDato($frmSeguimiento['txtIdEmpleado'], "int"),
			valTpDato(1, "int"),// 1 = Prospecto, 2 = Cliente
			valTpDato("NOW()", "campo")); 
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQLProsp);
		if (!$Result1) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		$idProspecto = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");	
	}


	//COSULTO SI EL CLIENTE TIENE UN PERFIL O NO
	$queryPerfil = sprintf("SELECT id FROM crm_perfil_prospecto 
		WHERE id = %s;", 
	valTpDato($idProspecto,"int"));
	$rsPerfil = mysql_query($queryPerfil);
	$totalRows = mysql_num_rows($rsPerfil);

	if ($totalRows > 0) {//EDITA LOS DATOS DEL PERFIL DEL PROSPECTO SI EXISTE
		if (!xvalidaAcceso($objResponse,"crm_seguimiento_list","editar")) { 
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse;
		}
		$updatePerfilProspecto = sprintf("UPDATE crm_perfil_prospecto SET
			id_puesto = %s,
			id_titulo = %s,
			id_posibilidad_cierre = %s ,
			id_sector = %s,
			id_nivel_influencia = %s,
			id_estatus = %s,
			fecha_actualizacion = NOW(),
			compania = %s,
			id_estado_civil = %s,
			sexo = %s,
			fecha_nacimiento = %s,
			clase_social = %s,
			observacion = %s,
			id_motivo_rechazo = %s
		WHERE id = %s;",
			valTpDato($frmSeguimiento['LstPuesto'], "int"),
			valTpDato($frmSeguimiento['lstTitulo'], "int"),
			($frmSeguimiento['lstPosibilidadCierre'] != "-1") ? valTpDato($frmSeguimiento['lstPosibilidadCierre'], "int") : valTpDato(NULL, "text"),
			valTpDato($frmSeguimiento['LstSector'], "int"),
			valTpDato($frmSeguimiento['lstNivelInfluencia'], "int"),
			valTpDato($frmSeguimiento['lstEstatus'], "int"),
			valTpDato($frmSeguimiento['txtCompania'], "text"),
			valTpDato($frmSeguimiento['lstEstadoCivil'], "int"), 
			valTpDato($frmSeguimiento['rdbSexo'], "text"),
			valTpDato(implode("-",array_reverse(explode("-",$frmSeguimiento['txtFechaNacimiento']))), "date"),
			valTpDato($frmSeguimiento['lstNivelSocial'], "text"),
			valTpDato($frmSeguimiento['txtObservacion'], "text"),
			valTpDato($frmSeguimiento['lstMotivoRechazo'], "int"),
			valTpDato($idProspecto, "int"));
		mysql_query("SET NAME 'utf8'");
		$queryPerfilProspecto = mysql_query($updatePerfilProspecto);
		if (!$queryPerfilProspecto) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$updatePerfilProspecto);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {//INSERTA LOS DATOS DEL PERFIL DEL PROSPECTO
		if (!xvalidaAcceso($objResponse,"crm_seguimiento_list","insertar")) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse; 
		}
		$insertPerfilProspecto = sprintf("INSERT INTO crm_perfil_prospecto (id, id_puesto, id_titulo, id_posibilidad_cierre, id_sector, id_nivel_influencia, id_estatus, Compania, id_estado_civil, sexo, fecha_nacimiento, clase_social, observacion, id_motivo_rechazo)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idProspecto, "int"),
			valTpDato($frmSeguimiento['LstPuesto'], "int"),
			valTpDato($frmSeguimiento['lstTitulo'], "int"),
			($frmSeguimiento['lstPosibilidadCierre'] == "-1")? valTpDato(NULL, "text"): valTpDato($frmSeguimiento['lstPosibilidadCierre'], "int"),
			valTpDato($frmSeguimiento['LstSector'], "int"),
			valTpDato($frmSeguimiento['lstNivelInfluencia'], "int"),
			valTpDato($frmSeguimiento['lstEstatus'], "int"),
			valTpDato($frmSeguimiento['txtCompania'], "text"),
			valTpDato($frmSeguimiento['lstEstadoCivil'], "int"), 
			valTpDato($frmSeguimiento['rdbSexo'], "text"),
			valTpDato(implode("-",array_reverse(explode("-",$frmSeguimiento['txtFechaNacimiento']))), "date"),
			valTpDato($frmSeguimiento['lstNivelSocial'], "text"),
			valTpDato($frmSeguimiento['txtObservacion'], "text"), 
			valTpDato($frmSeguimiento['lstMotivoRechazo'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertPerfilProspecto);
		if (!$Result1) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$insertPerfilProspecto);
			}
		}
		$idPerfilProspecto = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}

	// VERIFICAR SI EXISTEN AUN LOS MODELOS DE INTERES QUE ESTABAN EN LA BD
	$queryModelo = sprintf("SELECT * FROM an_prospecto_vehiculo WHERE id_cliente = %s;",
		valTpDato($idProspecto, "int"));
	$rsModelo = mysql_query($queryModelo);
	if (!$rsModelo) { 
		$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
		return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	while ($rowModelo = mysql_fetch_assoc($rsModelo)) {
		$existModelo = false;
		if (isset($frmSeguimiento['cbx1'])) {
			foreach ($frmSeguimiento['cbx1'] as $indice => $valor) {
				if ($rowModelo['id_prospecto_vehiculo'] == $frmSeguimiento['hddIdProspectoVehiculo'.$valor]) {
					$existModelo = true;
				}
			}
		}
		
		if ($existModelo == false) {
			$deleteSQL = sprintf("DELETE FROM an_prospecto_vehiculo WHERE id_prospecto_vehiculo = %s",
				valTpDato($rowModelo['id_prospecto_vehiculo'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) {
				$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); 
			}
		}
	}

	// INSERTA LOS MODELOS DE INTERES NUEVOS
	if (isset($frmSeguimiento['cbx1'])) {
		foreach ($frmSeguimiento['cbx1'] as $indice => $valor) {
			if ($valor != "") {
				if ($frmSeguimiento['hddIdProspectoVehiculo'.$valor] == "") {
					$insertSQL = sprintf("INSERT INTO an_prospecto_vehiculo (id_cliente, id_unidad_basica, precio_unidad_basica, id_medio, id_nivel_interes, id_plan_pago)
					VALUE (%s, %s, %s, %s, %s, %s);", 
						valTpDato($idProspecto, "int"),
						valTpDato($frmSeguimiento['hddIdUnidadBasica'.$valor], "int"),
						valTpDato($frmSeguimiento['hddPrecioUnidadBasica'.$valor], "real_inglesa"),
						valTpDato($frmSeguimiento['hddIdMedio'.$valor], "int"),
						valTpDato($frmSeguimiento['hddIdNivelInteres'.$valor], "int"),
						valTpDato($frmSeguimiento['hddIdPlanPago'.$valor], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) {
						$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
						return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); 
					}
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
	}
	
	//SEGUIMIENTO DEL CLIENTE
	if($frmSeguimiento['hddIdSeguimiento'] > 0){//ACTUALIZA EL SEGUIMIENTO
		if (!xvalidaAcceso($objResponse,"crm_seguimiento_list","editar")) { 
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse; 
		}
		// ACTULIZA EL SEGUIMIENTO
		$query = sprintf("UPDATE crm_seguimiento SET
			id_empleado_actualiza = %s,
			id_empresa = %s,
			observacion_seguimiento = %s
		WHERE 
			id_seguimiento = %s",
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($frmSeguimiento['txtIdEmpresa'],"int"),
		valTpDato($frmSeguimiento['textAreaObservacion'],"text"),
		valTpDato($frmSeguimiento['hddIdSeguimiento'],"int"));
		mysql_query("SET NAMES 'utf8';");
		$rs = mysql_query($query);
		if (!$rs) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		mysql_query("SET NAMES 'latin1';");
		
		//ACTUALIZA EL VENDEDOR ASIGNADO

		$query2 = sprintf(" UPDATE crm_seguimiento_diario SET
			id_equipo = %s,
			id_empleado_vendedor = %s,
			fecha_asignacion_vendedor = %s
			WHERE 
			id_seguimiento = %s",
		valTpDato($frmSeguimiento['lstEquipo'],"int"),
		($frmSeguimiento['rdItemIntegrante'] != "") ? $frmSeguimiento['rdItemIntegrante'] : valTpDato(NULL,"text"),
		($frmSeguimiento['rdItemIntegrante'] != "") ? "NOW()" : valTpDato(NULL,"text"),		
		valTpDato($frmSeguimiento['hddIdSeguimiento'],"int"));
		mysql_query("SET NAMES 'utf8';");
		$rs2 = mysql_query($query2);
		if (!$rs2) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		mysql_query("SET NAMES 'latin1';");

	}else{ // CREA EL SEGUIMIENTO
		if (!xvalidaAcceso($objResponse,"crm_seguimiento_list","insertar")) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse; 
		}
		$query = sprintf("INSERT INTO crm_seguimiento (id_cliente, id_empleado_creador, id_empresa, observacion_seguimiento) VALUE 
			(%s,%s,%s,%s);", 
		valTpDato($idProspecto, "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($frmSeguimiento['txtIdEmpresa'],"int"),
		valTpDato($frmSeguimiento['textAreaObservacion'],"text"));
		mysql_query("SET NAMES 'utf8';");
		$rs = mysql_query($query);
		if (!$rs) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		$idSeguimiento = mysql_insert_id();
		
		//INSERTA EL SEGUIMIENTO DIARIO
		$query2 = sprintf("INSERT INTO crm_seguimiento_diario (id_seguimiento,id_equipo, id_empleado_vendedor,fecha_registro, fecha_asignacion_vendedor) VALUE (%s,%s,%s,NOW(),%s);",
		valTpDato($idSeguimiento,"int"),
		valTpDato($frmSeguimiento['lstEquipo'],"int"),
		($frmSeguimiento['rdItemIntegrante'] != "") ? valTpDato($frmSeguimiento['rdItemIntegrante'],"int"):valTpDato(NULL,"text"),		
		($frmSeguimiento['rdItemIntegrante'] != "") ? "NOW()":valTpDato(NULL,"text"));
		mysql_query("SET NAMES 'utf8';");
		$rs2 = mysql_query($query2);
		if (!$rs2) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query2);
		}
		
		//CONSULTA LA POSIBILIDAD DE CIERRES POR DEFECTO
		$query3 = sprintf("SELECT * FROM crm_posibilidad_cierre WHERE por_defecto = %s AND id_empresa = %s ",
		valTpDato(1,"int"),
		valTpDato($frmSeguimiento['txtIdEmpresa'],"int"));
		$rs3 = mysql_query($query3);
		$row = mysql_fetch_array($rs3);
		$numRow = mysql_num_rows($rs3);
		if(!$numRow){
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse->alert("No existe una posibilidad de cierres de estatus inicial, por favor configure una posibilidad de cierre como estatus inicial");
		}
		
		//INSERTA LA POSIBILIDAD DE CIERRE
		$query4 = sprintf("INSERT INTO crm_seguimiento_cierre (id_seguimiento, id_posibilidad_cierre, fecha_actualizacion) VALUE (%s,%s,NOW())",
		valTpDato($idSeguimiento,"int"),
		valTpDato($frmSeguimiento['lstPosibilidadCierre'],"int"));
		$rs4 = mysql_query($query4);
		if (!$rs4) {
			$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);");
			return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query4);
		}		

		mysql_query("SET NAMES 'latin1';");
		
	}

	mysql_query("COMMIT;");
	
	$objResponse->alert("Datos Guardados Con Exito");
	$objResponse->script("RecorrerForm('frmSeguimiento','button','disabled',false);
	byId('btnCancelarProspecto').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function guardarActividadSeguimiento($formActividadSeg){
	$objResponse = new xajaxResponse();
	
	if (date("Y-m-d",strtotime($formActividadSeg['textFechAsignacion'])) < date("Y-m-d")){ 
		return	$objResponse->alert("La Fecha de Asignacion no Puede ser Menor a la Fecha Actual"); 
	}
	
	mysql_query("START TRANSACTION;");
	//INSERTA LA ACTIVIDAD PARA EL SEGUIMIENTO
	$query = sprintf("INSERT INTO crm_actividad_seguimiento (id_seguimiento, id_actividad) VALUE (%s, %s);", 
		valTpDato($formActividadSeg['hddIdSeguimientoAct'], "int"),
		valTpDato($formActividadSeg['lstActividadSeg'], "int"));
	$rs = mysql_query($query);
	if (!$rs) {return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);}
	$idActSeguimiento = mysql_insert_id();
	
	//AGENDA LA ACTIVADA DE ESTE SEGUIMIENTO
	$query2 = sprintf("INSERT INTO crm_actividades_ejecucion (id_actividad,id_actividad_seguimiento, id_integrante_equipo, id, fecha_asignacion, fecha_creacion, estatus, notas, id_empresa)
			VALUES (%s, %s, %s, %s, %s, NOW(), %s, %s, %s)",
		valTpDato($formActividadSeg['lstActividadSeg'],"int"),
		valTpDato($idActSeguimiento,"int"),
		valTpDato($formActividadSeg['hddIdIntegrante'],"int"),
		valTpDato($formActividadSeg['idClienteHidd'],"int"),
		valTpDato(date("Y-m-d H:i",strtotime($formActividadSeg['textFechAsignacion'] . $formActividadSeg['listHora'])), "text"),	
		valTpDato(1,"int"),//1 es asignado. 0 es finalizado. 2 Finalizo tarde. 3Finalizado auto
		valTpDato($formActividadSeg['textNotaCliente'],"text"),
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$rs2 = mysql_query($query2);
	if (!$rs2) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query2); }
	mysql_query("COMMIT;");
//$objResponse->alert($query2);
	$objResponse->alert("Datos Guardados con Exito");
	$objResponse->script("byId('butCancelarAsignacion').click();");
	$objResponse->script("byId('btnBuscar').click();");
	
	return $objResponse;
}

function GuardarPosibleCierre($idSeguimiento,$idPosibleCierre){
	$objResponse = new xajaxResponse();

	// CONSULTA EL SEGUIMIENTO PARA SACAR EL CLIENTE
	$query = sprintf("SELECT * FROM crm_seguimiento WHERE id_seguimiento = %s",
	valTpDato($idSeguimiento));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	$numRows = mysql_num_rows($rs);

	//ACTULIZA LA POSIBILIDAD DE CIEER EN EL PERFIL DEL PROSPECTO
	$queryUpdate = sprintf("UPDATE crm_perfil_prospecto SET
		id_posibilidad_cierre = %s 
		WHERE 
		id = %s",
	valTpDato($idPosibleCierre,"int"),
	valTpDato($row['id_cliente'],"int"));
	$rsUpdate = mysql_query($queryUpdate);
	if (!$rsUpdate) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	//INSERTA EL POSIBLE CIERRE PARA LLEVAR UN HISTORICO
	$queryInsert = sprintf("INSERT INTO crm_seguimiento_cierre (id_seguimiento, id_posibilidad_cierre,fecha_actualizacion) 
		VALUE (%s,%s, NOW())",
	valTpDato($idSeguimiento,"int"),
	valTpDato($idPosibleCierre,"int"));
	$rsInsert = mysql_query($queryInsert);
	if (!$rsInsert) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$objResponse->alert("Posibilidad de Cierre Agregada con Exito");
	$objResponse->script("byId('btnCerrafrmPosibleCierre').click();
	byId('btnBuscar').click();");	
	
	return $objResponse;
}

function insertarIntegrante($idEquipo, $checkIdEmpleado = ""){
	$objResponse = new xajaxResponse();

	$objResponse->script("$('.remover').remove();");

	$query = sprintf("SELECT *,
			IFNULL((SELECT jefe_equipo FROM crm_equipo 
				WHERE crm_equipo.jefe_equipo = crm_integrantes_equipos.id_empleado AND 
						crm_equipo.id_equipo = crm_integrantes_equipos.id_equipo ), 
			null) AS jefe_equipo
		FROM crm_integrantes_equipos 
		WHERE activo = %s AND id_equipo = %s",
	valTpDato(1, "int"),
	valTpDato($idEquipo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
//$objResponse->alert($query);
	while($row = mysql_fetch_assoc($rs)){
		$checkEmpleado = ($checkIdEmpleado == $row['id_empleado']) ? $checkIdEmpleado : "";
		$Result1 = itemIntegrante($contFila,$row['id_empleado'],$row['jefe_equipo'], $checkEmpleado);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
		}	
	}
	
	return $objResponse;
}

function insertarModelo($frmModelo, $frmSeguimiento) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmSeguimiento['cbx1'];
	$contFila = $arrayObj1[count($arrayObj1)-1];
	
	$idUnidadBasica = $frmModelo['hddIdUnidadBasica'];
	$hddPrecioUnidadBasica = str_replace(",","",$frmModelo['txtPrecioUnidadBasica']);
	$txtIdMedio = $frmModelo['lstMedio'];
	$txtIdNivelInteres = $frmModelo['lstNivelInteres'];
	$txtIdPlanPago = $frmModelo['lstPlanPago'];
	
	$Result1 = insertarItemModeloInteres($contFila, "", $idUnidadBasica, $hddPrecioUnidadBasica, $txtIdMedio, $txtIdNivelInteres, $txtIdPlanPago);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj1[] = $contFila;
	}
	
	$objResponse->script("byId('btnCancelarModelo').click();");
	
	return $objResponse;
}

function listaActSegEncabezado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("actividad_seguimiento = %s",
		valTpDato(1,"int"));
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = %s",
		valTpDato(1,"int"));

	$query = sprintf("SELECT * FROM crm_actividad %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTb = "<table border=\"0\" width=\"100%\" class=\"divGris\">";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		$htmlTb .= (fmod($contFila, 4) == 1) ? "<tr align=\"center\">" : "";
		
		$htmlTb .= sprintf("<td width=\"%s\" align=\"center\" title=\"%s\">%s</td>",
			(100 / $totalRows)."%",utf8_encode($row['nombre_actividad']),utf8_encode($row['nombre_actividad_abreviatura']));
		$htmlTb .= (fmod($contFila, 4) == 0) ? "</tr>" : "";
	}

	$htmlTb .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td>";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td align=\"center\">No Tiene Actividad de Seguimiento Configuradas</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdActividadLstEncabezado","innerHTML",$htmlTb);
	
	return $objResponse;
}

function listaActSegSelect ($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") { // EMPRESA 
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("actividad_seguimiento = %s",
		valTpDato(1,"int"));
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = %s",
		valTpDato(1,"int"));

	$query = sprintf("SELECT * FROM crm_actividad %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
	
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTb = "<table border=\"0\" width=\"100%\" class=\"divGris\">";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$query2 =  sprintf("SELECT * FROM crm_actividad_seguimiento WHERE id_seguimiento = %s AND id_actividad = %s",
			valTpDato($valCadBusq[1],"int"),
			valTpDato($row['id_actividad'],"int"));
		$rs2 = mysql_query($query2);
		if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query2);
		$numRows2 = mysql_num_rows($rs2);
		$row2 = mysql_fetch_assoc($rs2);
		
		$check = ($row['id_actividad'] == $row2['id_actividad']) ? "checked='checked'":"";
		$funcion = ($row['id_actividad'] == $row2['id_actividad']) ? sprintf("onclick='xajax_eliminarActividadSeguimiento(%s,xajax.getFormValues(\"frmLstSeguimiento\"))'",$valCadBusq[1]) :sprintf("onclick='abrirFrom(this, \"formAsignarActividadSeg\", \"tdFlotanteTitulo7\", %s, this.value);'" ,$valCadBusq[1]);
		
		$htmlTb .= (fmod($contFila, 4) == 1) ? "<tr align=\"center\">" : "";
		$htmlTb .= sprintf("<td width=\"%s\" align=\"center\" title=\"%s-%s\">".
			"<input id=\"checkActividad_%s_%s\" name=\"checkActividad%s[]\" class=\"modalImg\" rel=\"#divFlotante7\" type=\"checkbox\" 
			value=\"%s\" %s %s >".
			"<input name=\"hiddIdActEjecucionSeg%s.%s\" id=\"hiddIdActEjecucionSeg%s.%s\" type=\"hidden\" value=\"%s\" />".
		"</td>",
			(100 / $totalRows)."%",utf8_encode($row['nombre_actividad']),$valCadBusq[1],
				$row['id_actividad'],$valCadBusq[1],$valCadBusq[1],$row['id_actividad'],$check,$funcion,
				$valCadBusq[1],$row['id_actividad'],$valCadBusq[1],$row['id_actividad'],$row2['id_actividad_seguimiento']);
		$htmlTb .= (fmod($contFila, 4) == 0) ? "</tr>" : "";

	}
	$htmlTb .= "</table>";
//xajax_guardarSeguimientoActividad(%s,xajax.getFormValues('frmLstSeguimiento'))
	if (!($totalRows > 0)) {
		$htmlTb .= "<td>";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\" height=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td align=\"center\"></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdActividadLstSelect".$valCadBusq[1],"innerHTML",$htmlTb);
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");

	$objResponse->call("selectedOption","lstTipoCuentaCliente",$valCadBusq[4]);
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa LIKE %s",
			valTpDato($valCadBusq[0], "text"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("credito LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("status LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("paga_impuesto = %s ",
			valTpDato($valCadBusq[3], "boolean"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		if ($valCadBusq[4] == 1) {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0
			AND tipo_cuenta_cliente = 1)");
		} else if ($valCadBusq[4] == 2) {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) = 0
			AND tipo_cuenta_cliente = 2)");
		} else {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0)
			AND tipo_cuenta_cliente = 2");
		}
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', nombre, apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR perfil_prospecto.compania LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cliente.id,
		cliente.tipo,
		cliente.nombre,
		cliente.apellido,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		cliente.telf,
		cliente.credito,
		cliente.status,
		cliente.tipocliente,
		bloquea_venta,
		paga_impuesto,
		tipo_cuenta_cliente,
		perfil_prospecto.compania,
		(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos
	FROM cj_cc_cliente cliente
		LEFT JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id) %s", $sqlBusq);
	
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

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, $spanClienteCxC);
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "apellido", $campOrd, $tpOrd, $valBusq, $maxRows, "Apellido");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Teléfono");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "compania", $campOrd, $tpOrd, $valBusq, $maxRows, "Compañia");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "cantidad_modelos", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant. Modelos");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "paga_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Paga Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['status']) {
			case "Inactivo" : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case "Activo" : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td>%s</td>",$imgEstatus);
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['apellido'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['telf'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['compania'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad_modelos']."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['paga_impuesto'] == 1) ? "SI" : "NO")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo'])."</td>";
			$htmlTb .= "<td align=\"center\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
			$htmlTb .= sprintf("<td><button type=\"button\" id=\"btnCliente\" name=\"btnCliente\" title=\"Listar\" onclick=\"xajax_cargarDatos('',%s); byId('btnCerraCliente').click();\"><img src=\"../img/iconos/tick.png\"/></button></td>",$row['id']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"17\">";
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
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
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
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}


function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
	
	// 1 = ASESOR VENTAS VEHICULOS, 2 = GERENTE VENTAS VEHICULOS
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.clave_filtro IN (1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo,
        id_empresa		
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanCI));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		//asignarVendedor($idVEndedor, $idEmpresa)
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><button type=\"button\" onclick=\"xajax_asignarEmpleado(%s,%s);byId('btnCerrarEmpleado').click();\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button></td>",$row['id_empleado'],$row['id_empresa']);
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
	
	$objResponse->assign("tdListEmpleado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_empresa_reg <> 100");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR nombre_empresa_suc LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "8%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanRIF));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "33%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "33%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarEmpresa%s\" onclick=\"xajax_asignarEmpresa(%s); byId('btnCerrarEmp').click();\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_empresa_reg']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($nombreSucursal)."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function lstSeguimiento($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	global $spanClienteCxC;

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seguimiento.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	$arrayClave = claveFiltroEmpleado();
	if($arrayClave[0] == true){
		if($arrayClave[1] == 1 || $arrayClave[1] == 2){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(seguimiento.id_empleado_creador = %s OR id_empleado_vendedor = %s 
					OR (SELECT jefe_equipo FROM crm_equipo equipo WHERE activo = %s AND jefe_equipo = %s LIMIT %s))",
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			valTpDato(1,"int"),
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			valTpDato(1,"int"));
		}
	}else{
		$objResponse->alert($arrayClave[1]);
	}

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("perfil_prospecto.id_posibilidad_cierre = %s",
			valTpDato($valCadBusq[1], "int"));
	}else{
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("activo = %s",
			valTpDato(1, "int"));
			
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("fin_trafico IS NULL");
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond2.sprintf("id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
		}else{
			$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond2.sprintf("id_empresa = %s",
				valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("perfil_prospecto.id_posibilidad_cierre IN (SELECT id_posibilidad_cierre 
																			FROM crm_posibilidad_cierre %s)", $sqlBusq2);	
	}
		
	if($valCadBusq[2] != "-1" && $valCadBusq[2] != "" && $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fechaProximaEntrevista BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"));
	}
	
	if($valCadBusq[4] != "-1" && $valCadBusq[4] != "" && $valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_registro) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[5])), "text"));
	}
	
	if($valCadBusq[6] != "-1" && $valCadBusq[6]) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seguimiento_diario.id_empleado_vendedor = %s",
			valTpDato($valCadBusq[6], "int"));
	}

	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("observacion_seguimiento LIKE %s
		OR CONCAT_WS(' ',cliente.nombre, cliente.apellido) LIKE %s
		OR ((SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
					WHERE empleado.id_empleado = seguimiento_diario. id_empleado_vendedor) IN (
								SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
										WHERE empleado.id_empleado = seguimiento_diario.id_empleado_vendedor AND 
											CONCAT_WS(' ',nombre_empleado, empleado.apellido) LIKE %s))
		OR ((SELECT nom_uni_bas FROM an_prospecto_vehiculo prospecto_vehiculo
				INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
			WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}

	$query = sprintf("SELECT 
		seguimiento.id_seguimiento, seguimiento.id_cliente, seguimiento.id_empleado_creador, seguimiento.id_empleado_actualiza, seguimiento.id_empresa, seguimiento.observacion_seguimiento,
		CONCAT_WS(' ',cliente.nombre, cliente.apellido) AS nombre_cliente,
		seguimiento_diario.id_seguimiento_diario, seguimiento_diario.id_equipo, equipo.jefe_equipo, seguimiento_diario.id_empleado_vendedor, fecha_registro, fecha_asignacion_vendedor,
		perfil_prospecto.id_perfil_prospecto, perfil_prospecto.id_posibilidad_cierre, fechaProximaEntrevista,
		posibilidad_cierre.nombre_posibilidad_cierre, img_posibilidad_cierre,
		grupositems.item,
		(SELECT nom_uni_bas FROM an_prospecto_vehiculo prospecto_vehiculo
					INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
				WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) AS nom_uni_bas,
				
			(SELECT precio_unidad_basica  FROM an_prospecto_vehiculo prospecto_vehiculo
					INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
				WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) AS precio_unidad_basica,
				
			IFNULL((SELECT COUNT(an_unidad_fisica.id_uni_bas) FROM an_unidad_fisica 
				WHERE  an_unidad_fisica.id_uni_bas = (SELECT an_uni_bas.id_uni_bas FROM an_prospecto_vehiculo
															INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
														WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
					AND estado_venta = 'DISPONIBLE'
				GROUP BY an_unidad_fisica.id_uni_bas ), 0) AS disponible_unidad_fisica,
				
		IFNULL((SELECT uni_bas.nom_uni_bas 
					FROM an_tradein tradein 
				INNER JOIN cj_cc_anticipo cxc_ant ON tradein.id_anticipo = cxc_ant.idAnticipo
				INNER JOIN an_unidad_fisica uni_fis ON tradein.id_unidad_fisica = uni_fis.id_unidad_fisica
				INNER JOIN an_uni_bas uni_bas ON uni_fis.id_uni_bas = uni_bas.id_uni_bas
					WHERE cxc_ant.idCliente = seguimiento.id_cliente), '-') AS tradeIn,
					
		CONCAT_WS(' ',empleado.nombre_empleado, empleado.apellido) AS nombre_usuario_creador,
		(SELECT CONCAT_WS(' ',nombre_empleado, pg_empleado.apellido) FROM pg_empleado 
				WHERE pg_empleado.id_empleado = seguimiento_diario.id_empleado_vendedor) AS nobre_vendedor
	FROM crm_seguimiento seguimiento 
		INNER JOIN cj_cc_cliente cliente ON cliente.id = seguimiento.id_cliente
		INNER JOIN crm_seguimiento_diario seguimiento_diario ON seguimiento_diario.id_seguimiento = seguimiento.id_seguimiento
		INNER JOIN crm_perfil_prospecto perfil_prospecto ON cliente.id = perfil_prospecto.id   
		LEFT JOIN crm_posibilidad_cierre posibilidad_cierre ON posibilidad_cierre.id_posibilidad_cierre = perfil_prospecto.id_posibilidad_cierre
		LEFT JOIN crm_equipo equipo ON equipo.id_equipo = seguimiento_diario.id_equipo
		INNER JOIN grupositems ON grupositems.idItem = (SELECT id_medio FROM an_prospecto_vehiculo
															INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
														WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
		INNER JOIN pg_empleado empleado ON empleado.id_empleado = seguimiento.id_empleado_creador
	%s",
	 $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
//$objResponse->alert($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr align=\"center\">";
			$htmlTh .= "<td>";
				$htmlTh .= "<table width=\"100%\" border=\"0\">";
					$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
						$htmlTh .= "<td style=\"min-width:15px\" rowspan=\"2\"></td>";
						$htmlTh .= "<td width=\"8%\">Entrada</td>";
						$htmlTh .= "<td width=\"15%\">Fuente</td>";
						$htmlTh .= "<td width=\"15%\">Nombre Cliente</td>";
						$htmlTh .= "<td width=\"15%\">Modelo Interes</td>";
						$htmlTh .= "<td width=\"10%\">Cant. Disp.</td>";
						$htmlTh .= "<td id=\"tdActividadLstEncabezado\" style=\"min-width:20%\" rowspan=\"2\" ></td>";
						$htmlTh .= sprintf("<td width=\"%s\">%s Precio</td>","15%",cAbrevMoneda);
						$htmlTh .= sprintf("<td style=\"min-width:80px\" rowspan=\"2\"></td>");
					$htmlTh .= "</tr>";
					$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
						$htmlTh .= "<td>Asignada</td>";
						$htmlTh .= "<td>Gerente de Mesa</td>";
						$htmlTh .= "<td>Proxima Cita</td>";
						$htmlTh .= "<td>Trade In</td>";
						$htmlTh .= "<td>Vendedor</td>";
						$htmlTh .= "<td >Comentarios</td>";
					$htmlTh .= "</tr>";
				$htmlTh .= "</table>";
			$htmlTh .= "</td>";
		$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$arrayIdSeguimiento [] = $row['id_seguimiento'];		
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA 
		$imgFoto = (!file_exists($row['img_posibilidad_cierre'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['img_posibilidad_cierre'];
		$colo =($row['disponible_unidad_fisica'] == 0) ? "background-color:#ffeeee" :"";

		$htmlTb.= sprintf("<tr onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='%s';\" height=\"22\">",$clase);
			$htmlTb .= "<td>";
					$htmlTb .= "<table border=\"0\" class=\"divGris trResaltar4\" width=\"100%\">";
					$htmlTb .= sprintf("<tr align=\"left\" class=\"%s\">",$clase);
						$htmlTb .= sprintf("<td style=\"min-width:15px\" onmouseover=\"this.className='trSobre puntero'\" onmouseout=\"this.className='".$clase."';\" class=\"modalImg\" id=\"aEditar\" title=\"Editar\" rel=\"#divFlotante\" onclick=\"abrirFrom(this,'frmSeguimiento','tdFlotanteTitulo', %s, 'tblProspecto')\" rowspan=\"2\">%s</td>",
							$row['id_seguimiento'],
							$row['id_seguimiento']);
						$htmlTb .= "<td align=\"center\" style=\"min-width:8%\">".date("d-m-Y h:i a", strtotime($row['fecha_registro']))."</td>";
						$htmlTb .= "<td style=\"min-width:15%\">".utf8_encode($row['item'])."</td>";
						$htmlTb .= "<td style=\"min-width:14%\">".utf8_encode($row['nombre_cliente'])."</td>";
						$htmlTb .= "<td style=\"min-width:14%\">".utf8_encode($row['nom_uni_bas'])."</td>";
						$htmlTb .= sprintf("<td align=\"center\" style=\"min-width:%s; %s\">%s</td>",
							"10%",
							$colo,
							$row['disponible_unidad_fisica']);
						$htmlTb .= sprintf("<td id=\"tdActividadLstSelect%s\" style=\"min-width:%s\" rowspan=\"2\"></td>",
							$row['id_seguimiento'],
							"20%");
						$htmlTb .= sprintf("<td align=\"right\" style=\"min-width:%s\">%s</td>",
							"15%",
							cAbrevMoneda." ".number_format($row['precio_unidad_basica'],2,".",","));
						$htmlTb .= sprintf("<td id=\"aPosibleCierre\" class=\"modalImg puntero\" onclick=\"abrirFrom(this, 'frmBusPosibleCierre', 'tdFlotanteTitulo6', %s, 'tblLstPosibleCierre')\" rel=\"#divFlotante6\" rowspan=\"2\" style=\"min-width:%s\">".
							"<img src=\"%s\" title=\"%s\" height=\"80\" width=\"80\"/>".
						"</td>",
							$row['id_seguimiento'], "30%",
							$imgFoto, utf8_encode($row['nombre_posibilidad_cierre']));
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr class=\"".$clase."\">";
						$htmlTb .= sprintf("<td>%s</td>",
							$hora = ($row['hora_asignacion'] != "") ?  date("h.i a", strtotime($row['hora_asignacion'])) : "");
						$htmlTb .= "<td>".$row['nombre_usuario_creador']."</td>";
						$htmlTb .= "<td>".(($row['fechaProximaEntrevista'] != "") ? date("d-m-Y", strtotime($row['fechaProximaEntrevista'])): "")."</td>";
						$htmlTb .= "<td>".$row['tradeIn']."</td>";
						$htmlTb .= "<td>".utf8_encode($row['nobre_vendedor'])."</td>";
						//$htmlTb .= "<td rowspan=\"2\"></td>";
						$htmlTb .= "<td>".utf8_encode($row['observacion_seguimiento'])."</td>";
						//$htmlTb .= "<td rowspan=\"2\"></td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"17\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divLstSeguimiento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->loadCommands(listaActSegEncabezado(0,"","",$valCadBusq[0]));
	
	if(isset($arrayIdSeguimiento)){
		foreach($arrayIdSeguimiento as $indice => $valor){
			$objResponse->loadCommands(listaActSegSelect(0,"","",$valCadBusq[0]."|".$valor));
		}	
	}
	
	
	return $objResponse;
}

function listaModelo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelos.catalogo = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_uni_bas LIKE %s
		OR nom_modelo LIKE %s
		OR nom_version LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_modelos
		INNER JOIN sa_unidad_empresa unid_emp ON (vw_iv_modelos.id_uni_bas = unid_emp.id_unidad_basica) %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
				$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
				
				$htmlTb .= "<td rowspan=\"5\">"."<button type=\"button\" onclick=\"xajax_asignarModelo('".$row['id_uni_bas']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
				$htmlTb .= sprintf("<td rowspan=\"5\" style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>", "100%",
					utf8_encode($row['nom_uni_bas']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_marca']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_modelo']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_version']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>Año %s</td>", utf8_encode($row['nom_ano']));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaModelo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaModelo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPosibleCierre($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 9, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = %s",
		valTpDato(1, "int"));

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("posicion_posibilidad_cierre IS NOT NULL");

	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_posibilidad_cierre LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM crm_posibilidad_cierre %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		if($row['por_defecto'] == 1){
			$imgIcono = "<input id=\"checkPosicibilidadCierre\" type=\"checkbox\" title=\"Estatus Inicial\" name=\"checkPosicibilidadCierre\" disabled=\"disabled\" checked=\"checked\">";
		}else if($row['fin_trafico'] == 1){
			$imgIcono = "<img title=\"Finaliza control de trafico\" src=\"../img/iconos/aprob_jefe_taller.png\">";
		}else{
			$imgIcono = "";
		}
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['img_posibilidad_cierre'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['img_posibilidad_cierre'];
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"center\">";
					$htmlTb .= sprintf("<td width=\"%s\" >%s.- %s</td>", "100%",$row['posicion_posibilidad_cierre'],utf8_encode($row['nombre_posibilidad_cierre']));
					$htmlTb .= sprintf("<td>%s</td>",$imgIcono);
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr align=\"center\">";
					$htmlTb .= sprintf("<td style=\"background-color:#FFFFFF\">".
						"<img class=\"puntero\" src=\"%s\" height=\"80\" width=\"80\" title=\"%s\" onclick=\"xajax_GuardarPosibleCierre(%s,%s)\"/>".
						"</td>",$imgFoto,$row['nombre_posibilidad_cierre'],$valCadBusq[1],$row['id_posibilidad_cierre']);
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPosibleCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPosibleCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPosibleCierre(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPosibleCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPosibleCierre(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divfrmPosibleCierre","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarModelo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarModelo");
$xajax->register(XAJAX_FUNCTION,"buscarPosibleCierre");
$xajax->register(XAJAX_FUNCTION,"buscarSeguimiento");
$xajax->register(XAJAX_FUNCTION,"cargarDatos");
$xajax->register(XAJAX_FUNCTION,"cargarDtosAsignacion");
$xajax->register(XAJAX_FUNCTION,"cargaLstActividad");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCivil");
$xajax->register(XAJAX_FUNCTION,"cargaLstEquipo");
$xajax->register(XAJAX_FUNCTION,"cargarLstEstatus");
$xajax->register(XAJAX_FUNCTION,"cargarListHora");
$xajax->register(XAJAX_FUNCTION,"cargaLstMedio");
$xajax->register(XAJAX_FUNCTION,"cargaLstMotivoRechazo");
$xajax->register(XAJAX_FUNCTION,"cargarLstNivelInfluencia");
$xajax->register(XAJAX_FUNCTION,"cargaLstPlanPago");
$xajax->register(XAJAX_FUNCTION,"cargarLstPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"cargarLstPuesto");
$xajax->register(XAJAX_FUNCTION,"cargarLstSector");
$xajax->register(XAJAX_FUNCTION,"cargarLstTitulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"eliminarActividadSeguimiento");
$xajax->register(XAJAX_FUNCTION,"eliminarModelo");
$xajax->register(XAJAX_FUNCTION,"guardarActividadSeguimiento");
$xajax->register(XAJAX_FUNCTION,"guardarSeguimiento");
$xajax->register(XAJAX_FUNCTION,"GuardarPosibleCierre");
$xajax->register(XAJAX_FUNCTION,"insertarIntegrante");
$xajax->register(XAJAX_FUNCTION,"insertarModelo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listadoActividad");
$xajax->register(XAJAX_FUNCTION,"listaActSegSelect");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"lstSeguimiento");
$xajax->register(XAJAX_FUNCTION,"listaModelo");
$xajax->register(XAJAX_FUNCTION,"listaPosibleCierre");


function claveFiltroEmpleado(){
	
	//AVERIGUAR VENTA O POSTVENTA
	$queryUsuario = sprintf("SELECT id_empleado,
	CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado,
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
        
	FROM pg_empleado 
		INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
	WHERE id_empleado = %s ",
	valTpDato($_SESSION['idEmpleadoSysGts'],"int"));
	
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryUsuario);
	$row = mysql_fetch_array($rsUsuario);

	return array(true, $rowClave['clave_filtro'], $row['tipo']);

}

function insertarItemModeloInteres($contFila, $idProspectoVehiculo = "", $idUnidadBasica = "", $hddPrecioUnidadBasica = "", $txtIdMedio = "", $txtIdNivelInteres = "", $txtIdPlanPago = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idProspectoVehiculo > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryProspectoVehiculo = sprintf("SELECT 
			prospecto_veh.id_prospecto_vehiculo,
			prospecto_veh.id_cliente,
			prospecto_veh.id_unidad_basica,
			prospecto_veh.precio_unidad_basica,
			prospecto_veh.id_medio,
			prospecto_veh.id_plan_pago,
			prospecto_veh.id_nivel_interes
		FROM an_prospecto_vehiculo prospecto_veh
		WHERE prospecto_veh.id_prospecto_vehiculo = %s;",
			valTpDato($idProspectoVehiculo, "int"));
		$rsProspectoVehiculo = mysql_query($queryProspectoVehiculo);
		if (!$rsProspectoVehiculo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsProspectoVehiculo = mysql_num_rows($rsProspectoVehiculo);
		$rowProspectoVehiculo = mysql_fetch_assoc($rsProspectoVehiculo);
	}
	
	$idUnidadBasica = ($idUnidadBasica == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_unidad_basica'] : $idUnidadBasica;
	$hddPrecioUnidadBasica = ($hddPrecioUnidadBasica == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['precio_unidad_basica'] : $hddPrecioUnidadBasica;
	$txtIdMedio = ($txtIdMedio == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_medio'] : $txtIdMedio;
	$txtIdNivelInteres = ($txtIdNivelInteres == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_nivel_interes'] : $txtIdNivelInteres;
	$txtIdPlanPago = ($txtIdPlanPago == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_plan_pago'] : $txtIdPlanPago;
	
	// BUSCA LOS DATOS DE LA UNIDAD BASICA
	$query = sprintf("SELECT
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo
	FROM vw_iv_modelos vw_iv_modelo
	WHERE id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// BUSCA LOS DATOS DEL MEDIO
	$query = sprintf("SELECT item AS medio FROM grupositems WHERE idItem = %s;",
		valTpDato($txtIdMedio, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowMedio = mysql_fetch_assoc($rs);
	
	// BUSCA LOS DATOS DEL PLAN DE PAGO
	$query = sprintf("SELECT item AS plan_pago FROM grupositems WHERE idItem = %s;",
		valTpDato($txtIdPlanPago, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowPlanPago = mysql_fetch_assoc($rs);
	
	switch($txtIdNivelInteres) {
		case "1" : $txtNivelInteres = "Bajo"; break;
		case "2" : $txtNivelInteres = "Medio"; break;
		case "3" : $txtNivelInteres = "Alto"; break;
	}
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieModeloInteres').before('".
		"<tr id=\"trItmModeloInteres:%s\" align=\"left\"  class=\"textoGris_11px %s\" >".
			"<td title=\"trItmModeloInteres:%s\"><input id=\"cbxItmModeloInteres\" name=\"cbxItmModeloInteres[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"hddPrecioUnidadBasica%s\" name=\"hddPrecioUnidadBasica%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdProspectoVehiculo%s\" name=\"hddIdProspectoVehiculo%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdUnidadBasica%s\" name=\"hddIdUnidadBasica%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdMedio%s\" name=\"hddIdMedio%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdNivelInteres%s\" name=\"hddIdNivelInteres%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPlanPago%s\" name=\"hddIdPlanPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['vehiculo']),
				$contFila, $contFila, number_format($hddPrecioUnidadBasica, 2, ".", ","),
			utf8_encode($rowMedio['medio']),
			utf8_encode($txtNivelInteres),
			utf8_encode($rowPlanPago['plan_pago']),
				$contFila, $contFila, $idProspectoVehiculo,
				$contFila, $contFila, $idUnidadBasica,
				$contFila, $contFila, $txtIdMedio,
				$contFila, $contFila, $txtIdNivelInteres,
				$contFila, $contFila, $txtIdPlanPago);
	
	return array(true, $htmlItmPie, $contFila);
}

function itemIntegrante($contFila, $idEmpleado = "", $idEmpleadoJefe ="", $checkIdEmpleado = ""){
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;

	$query = sprintf("SELECT id_empleado, nombre_empleado, nombre_cargo,nombre_departamento
		FROM vw_pg_empleados vw_pg_empleado 
		WHERE vw_pg_empleado.id_empleado = %s",
	valTpDato($idEmpleado,"int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
	$rows = mysql_fetch_array($rs);
	
	$check = ($checkIdEmpleado != "") ? "checked=\"checked\"":"";
	$jefe = ($idEmpleado == $idEmpleadoJefe) ? "<img src=\"../img/iconos/user_suit.png\" />" :"";
	$htmlItmPie = sprintf("$('#trItmIntegrante').before('".
		"<tr id=\"trItmIntegrante%s\" class=\"%s textoGris_11px remover\">".
			"<td>".
				"<input id=\"rdItemIntegrante%s\" name=\"rdItemIntegrante\" %s type=\"radio\" value=\"%s\">".
"<input type=\"checkbox\" id=\"checkHddntemIntegrante\" name=\"checkHddntemIntegrante[]\" checked=\"checked\" style=\"display:none\" value =\"%s\"/>".
				"<input id=\"hddIdEmpleado%s\" type=\"hidden\" value=\"%s\" name=\"hddIdEmpleado%s\">".
			"</td>".
			"<td class=\"textoNegrita_9px\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"center\">%s</td>".
		"</tr>')",
			$contFila,$clase,
				  $rows['id_empleado'],$check,$rows['id_empleado'],
					$contFila,
					$contFila,$rows['id_empleado'],$contFila,
				$contFila,
				utf8_encode($rows['nombre_empleado']),
				utf8_encode($rows['nombre_cargo']),
				$rows['nombre_cargo'],
				$jefe);

	return array(true, $htmlItmPie, $contFila,$query);
}
function sanear_string($string)
{

    $string = trim($string);

    $string = str_replace(
        array(/*'á',*/ 'à', 'ä', 'â', 'ª', /*'Á',*/ 'À', 'Â', 'Ä'),
        array(/*'a',*/ 'a', 'a', 'a', 'a', /*'A',*/ 'A', 'A', 'A'),
        $string
    );

    $string = str_replace(
        array(/*'é',*/ 'è', 'ë', 'ê', /*'É',*/ 'È', 'Ê', 'Ë'),
        array(/*'e',*/ 'e', 'e', 'e', /*'E',*/ 'E', 'E', 'E'),
        $string
    );

    $string = str_replace(
        array(/*'í',*/ 'ì', 'ï', 'î', /*'Í',*/ 'Ì', 'Ï', 'Î'),
        array(/*'i',*/ 'i', 'i', 'i', /*'I',*/ 'I', 'I', 'I'),
        $string
    );

    $string = str_replace(
        array(/*'ó',*/ 'ò', 'ö', 'ô', /*'Ó',*/ 'Ò', 'Ö', 'Ô'),
        array(/*'o',*/ 'o', 'o', 'o', /*'O',*/ 'O', 'O', 'O'),
        $string
    );

    $string = str_replace(
        array(/*'ú',*/ 'ù', 'ü', 'û', /*'Ú',*/ 'Ù', 'Û', 'Ü'),
        array(/*'u',*/ 'u', 'u', 'u', /*'U',*/ 'U', 'U', 'U'),
        $string
    );

    $string = str_replace(
        array(/*'ñ', 'Ñ',*/ 'ç', 'Ç'),
        array(/*'n', 'N',*/ 'c', 'C',),
        $string
    );

    //Esta parte se encarga de eliminar cualquier caracter extraño
    $string = str_replace(
        array("\\", "¨", "º", "-","_", "~", "#", "@", "|", "!", "\"", "·", "$", "%", "&", /*"/",*/ "(", ")", "?",
		   "'","¡", "¿","[", "^", "`", "]","+", "}", "{", "¨", "´",">", "< ", ";", /*",",*/ ":","."/*, " "*/),
		' ',
        $string
    );


    return $string;
}


?>