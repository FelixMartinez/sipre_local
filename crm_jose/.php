<?php 

//AVERIGUAR VENTA O POSTVENTA
$queryUsuario = sprintf("SELECT id_usuario, nombre_usuario, pg_usuario.id_empleado, nombre_empleado, apellido, pg_empleado.id_cargo_departamento, id_cargo,
			id_departamento, clave_filtro
		FROM pg_usuario
			LEFT JOIN pg_empleado ON pg_usuario.id_empleado = pg_empleado.id_empleado
			LEFT JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
		WHERE id_usuario = %s ",$_SESSION['idUsuarioSysGts']);
	$rsUsuario = mysql_query($queryUsuario);
		
	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rsUsuario);
		
	if ($row['clave_filtro'] == 1 || $row['clave_filtro'] == 2){
		$tipoUsuario = 'Ventas';
		
		} elseif ($row['clave_filtro'] == 4 || $row['clave_filtro'] == 5 || $row['clave_filtro'] == 6 ||
				  $row['clave_filtro'] == 7 || $row['clave_filtro'] == 8 || $row['clave_filtro'] == 26 || $row['clave_filtro'] == 400) {
			$tipoUsuario = 'Postventa';
			
			}  

//ASIGNA LOS EMPLEADO AL EQUIPO	 
function asignarEmpleadoEquipo($idEmpleado, $idEquipo){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT * FROM crm_integrantes_equipos
	WHERE id_equipo = %s
		AND id_empleado = %s;" ,
		valTpDato($idEquipo, "int"),
		valTpDato($idEmpleado, "int"));
	$queryValida = mysql_query($sql);
	$rows = mysql_fetch_array($queryValida);
	$num = mysql_num_rows($queryValida);
	
	//SE VALIDA SI EXISTE EL EMPLEADO Y ESTA ACTIVO
	if($num and $rows['activo'] == 1){ return $objResponse->alert("Este Empleado ya Existe en el Equipo"); }
	
	//VALIDA SI TIENE PERMISO DE INSERTAR					
	if (!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	if($num and $rows['activo'] == 0){
		$updateEmpleadoEquipo = sprintf("UPDATE crm_integrantes_equipos SET activo = 1 WHERE id_empleado = %s;",valTpDato($idEmpleado, "int"));
		$queryEquipoUp= mysql_query($updateEmpleadoEquipo);
		if (!$queryEquipoUp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		}else{
			$insertEmpleadoEquipo = sprintf("INSERT INTO crm_integrantes_equipos (id_empleado, id_equipo, activo)
			VALUES (%s,%s,1)",
				valTpDato($idEmpleado, "int"),
				valTpDato($idEquipo, "int"));
			mysql_query("SET NAMES 'utf8'");
			$queryEquipo= mysql_query($insertEmpleadoEquipo);
			if (!$queryEquipo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Empleado fue integrado al equipo con exito");
	
	$valBusq = sprintf("|%s|%s",
		$idEmpresa,
		$idEquipo);
						
	$objResponse->loadCommands(listaEquipoEmpleado(0, "id_empleado", "ASC", $valBusq));
	
	return $objResponse;
}

//PARA ASIGNAR UN JEFE DE EQUIPO
function asignarJefeEquipo($idEmpleado, $nombreEmpleado, $apellidoEmpleado){
	$objResponse = new xajaxResponse();
		
	//$objResponse->alert($idEmpleado." ". $nombreEmpleado."  ". $apellidoEmpleado);
	$objResponse->assign("idHiddJefeEquipo","value",$idEmpleado);
	$nombreApellidoEmpleado = $nombreEmpleado."  ". $apellidoEmpleado;
	$objResponse->assign("textJefeEquipo", "value", $nombreApellidoEmpleado);
	$objResponse->script("byId('butCancelarJefeEquipo').click();");
	return $objResponse;
	}
		
//HACE LA BUSQUDA SEGUN LA EMPRESA SELECCIONADO
function buscarEquipo($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['lstEmpresa']);
	$objResponse->loadCommands(listaEquipo(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
		
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

//HACE BUSQUEDA POR EMPLEADO
function buscaEmpleado($valForm){
	$objResponse = new xajaxResponse();
		
	$valBusq = sprintf("%s|%s|%s|%s",
		$valForm['hiddIdEmpresa'],
		$valForm['textCriterio'],
		$valForm['hiddIdEquipo'],
		$valForm['hiddTipoEquipo']);
		
	$IdEquipo = $valForm['hiddIdEquipo'];
	$IdEmpresa = $valForm['hiddIdEmpresa'];
	
	//var_dump($valBusq ,$IdEquipo, $IdEmpresa);
		
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
	return $objResponse;
}

//BUSCAR LOS EMPLEADO PARA JEFE DE EQUIPO	
function buscaEmpleadoJefeEquipo($valForm){
	$objResponse = new xajaxResponse();
		
	$valBusq = sprintf("%s|%s|%s",
		$valForm['idEmpresaJefeEquipo'],
		$valForm['criterioJefeEquipo'],
		$valForm['tipoEquipoJefeEquipo']);
		
	$objResponse->loadCommands(listaEmpleadoJefeEquipo(0, "", "", $valBusq));
		
	return $objResponse;
}

//CARGAR EL FORMULARIO PARA EDITAR EQUIPO
function cargarEquipo($nomObjeto, $idEquipo) {
	$objResponse = new xajaxResponse();
	
	//VALIS SI TIENE PERMISO DE EDITAR
	if (!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","editar")) { return $objResponse; }
	
	//LLAMA LA FUNCION JAVASCRIPT ABRIRNUEVO
	$objResponse->script("abrirNuevo('editar');");
			
	$query = sprintf("SELECT
						id_equipo, nombre_equipo, descripcion_equipo, crm_equipo.activo, crm_equipo.id_empresa, nombre_empresa, jefe_equipo, tipo_equipo,
						CONCAT(nombre_empleado,' ', apellido) AS nombre_empleado
					FROM crm_equipo
						LEFT JOIN pg_empresa ON pg_empresa.id_empresa = crm_equipo.id_empresa
						LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_equipo.jefe_equipo
					WHERE id_equipo = %s;",
		valTpDato($idEquipo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	$idEquipo = $row['id_equipo'];
		
		if($idEquipo == 1){
			$objResponse->script("$('#comboxTipoEquipo').attr('disabled', true);");
		} else {
		$objResponse->script("$('#comboxTipoEquipo').attr('disabled', false);");			
			}
			
	$nombreEquipo = utf8_encode($row['nombre_equipo']);
	$descripcionEquipo = utf8_encode($row['descripcion_equipo']);
	$activo = $row['activo'];
	$idFejeEquipo =  $row['jefe_equipo'];
	$tipoEquipo = $row['tipo_equipo'];
	
	//SE LLENAN LOS CAMPOS SEGUN LO ALMACENADO EN TABLA
	$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa"));
	$objResponse->assign("hddidEquipo","value",$idEquipo);//COTIENE EL QUE VIEN DEL LINK
	$objResponse->assign("txtNombreEquipo","value",$nombreEquipo);
	$objResponse->call("selectedOption","comboxTipoEquipo",$tipoEquipo);
	$objResponse->assign("areaEquipoDescripcion","value",$descripcionEquipo);
	$objResponse->assign("textJefeEquipo","value",utf8_encode($row['nombre_empleado']));
	$objResponse->assign("idHiddJefeEquipo","value",$idFejeEquipo);
	$objResponse->call("selectedOption","listEstatus",$activo);
	
	$objResponse->script('activarBoton();');
	return $objResponse;
}

function comboxTipoEquipo($tipo){
	$objResponse = new xajaxResponse();
	
	global $tipoUsuario;
	
		$html = "<select id='comboxTipoEquipo' name='comboxTipoEquipo' onchange='activarBoton(this.value); limpiarCampo();'>";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		
		if($tipoUsuario){
			if ($tipo == $tipoUsuario)
				$checked = "selected='selected'";
			else
				$checked = "";
							$html .= sprintf('<option id="%s" %s>%s</option>', $tipoUsuario, $checked, $tipoUsuario);
			} 
		
		else {;
				
		$result = mysql_query('SHOW COLUMNS FROM crm_equipo WHERE field="tipo_equipo"');
		
		while ($row = mysql_fetch_row($result)) {
				foreach(explode("','",substr($row[1],6,-2)) as $option) {
					if ($tipo == $option)
				$checked = "selected='selected'";
			else
				$checked = "";
							$html .= sprintf('<option id="%s" %s>%s</option>', $option, $checked, $option);
				} 
			}
		}
	$html .= "</select>";
	
	$objResponse->assign("tdTipoEquipo","innerHTML",$html);
	
	return $objResponse;
	}

//SELECCIONA EL EQUIPO AL HACER CLIK
function equipoSelect($nombre_equipo, $id_equipo, $tipoEquipo){
	$objResponse = new xajaxResponse();
	
	$sqlSelect = sprintf("SELECT id_empresa, id_equipo, nombre_equipo, tipo_equipo FROM crm_equipo  WHERE id_equipo = %s;",
	valTpDato($id_equipo, "int"));
	$queryEquipo= mysql_query($sqlSelect);
	if(!$queryEquipo)return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($queryEquipo);
	
	$idEquipo =$row['id_equipo'];
	$idEmpresa =$row['id_empresa'];
	$nombre_equipo = utf8_encode($row['nombre_equipo']);
	
	$nombreTipoEquipo = $nombre_equipo. " - ". $tipoEquipo;
 
 	$objResponse->assign("tdNombreGrupo", "innerHTML", $nombreTipoEquipo);
	$objResponse->assign("hiddIdEquipo", "value",$idEquipo);
	$objResponse->assign("hiddIdEmpresa", "value",$idEmpresa);
	$objResponse->assign("hiddTipoEquipo", "value",$tipoEquipo);
	$objResponse->script("abreIntegrante();");
	
	$valBusq = sprintf("|%s|%s",
		$idEmpresa,
		$idEquipo);
	
	$objResponse->loadCommands(listaEquipoEmpleado(0, "id_empleado", "ASC", $valBusq));
	
	$valBusq = sprintf("%s||%s|%s",
		$idEmpresa,
		$idEquipo,
		$tipoEquipo);
	$objResponse->loadCommands(listaEmpleado (0, "id_empleado", "ASC", $valBusq));
	
	return $objResponse;
}

//ELIMINAR LOS EQUIPO
function eliminarEquipo($idEquipo ) {
	$objResponse = new xajaxResponse();
	
	//VALIDA SI TIENE PERMISO DE ELIMINAR
	if (!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","eliminar")) {
		return $objResponse; 
	}
	
	$query = sprintf("SELECT * FROM crm_integrantes_equipos WHERE id_equipo = %s", $idEquipo);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rows = mysql_fetch_assoc($rs);
	
	$num = mysql_num_rows($rs);
	if ($num == 0) {//VALIDO SI EL EQUIPO TIENE INTEGRANTE ASIGNADOS A ESE EQUIPO
		
			mysql_query("START TRANSACTION;");
			
			$deleteSQL = sprintf("UPDATE crm_equipo SET activo = 0 WHERE id_equipo = %s;", 
			valTpDato($idEquipo, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
			mysql_query("COMMIT;");
		
		} else {
						
			return $objResponse->alert("El equipo no puede ser eliminado, contien " .$num. " integrante");

			}
				
	$objResponse->alert("Se elimino con Éxito");
	$objResponse->loadCommands(listaEquipo(0,'',''));
	
	return $objResponse;
}	

//ELIMINA LOS INTEGRANTES DEL GRUPO SELECCIONADO
function eliminarIntegrante($idIntegranteEquipo, $idEquipo){
	$objResponse = new xajaxResponse();

	//VALIDA SI TIENE PERMISO DE ELIMINAR	
	if (!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","eliminar")) { return $objResponse; }
	mysql_query("START TRANSACTION;");
	
	//COSULTO LA EXISTENCIA DE UNA RECEPCIONISTA O UNA ASESOR DE SERVICIO EN EL EQUIPO 
	$sql = sprintf("SELECT id_integrante_equipo, id_equipo, crm_integrantes_equipos.id_empleado,
			pg_empleado.id_cargo_departamento, clave_filtro, pg_cargo_departamento.id_cargo, nombre_cargo
		FROM crm_integrantes_equipos
			LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado
			LEFT JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento
			LEFT JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
		WHERE id_equipo = %s AND id_integrante_equipo = %s AND (clave_filtro = 25 OR clave_filtro = 5)",$idEquipo,$idIntegranteEquipo);
	$querySql =mysql_query($sql);
		if (!$querySql) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rsSql = mysql_fetch_array($querySql);
	$rowsSql = mysql_num_rows($querySql);

		if($idEquipo == 1){
		//SI ES DISTINTO A 0 EXISTE LA RECEPCIONISTA EXISTE
			if($rowsSql != 0){
				switch($rsSql['clave_filtro']){
					case 25:
						return $objResponse->alert('No se puede eliminar la '.$rsSql['nombre_cargo']);
						break;
					case 5:
						return $objResponse->alert('No se puede eliminar el '.$rsSql['nombre_cargo']);
						break;
					}
				} 		
			}
	$querySel = sprintf("SELECT * FROM crm_actividades_ejecucion WHERE id_integrante_equipo = %s AND estatus = 1", $idIntegranteEquipo);
	$rs = mysql_query($querySel);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rows = mysql_fetch_assoc($rs);
	$nums = mysql_num_rows($rs);
	
		if($nums == 0) { //VALIDO SI EL INTEGRANTE SELECCIONADO TIENE ACTIVIDFAD ASIGNADA CON ESTATUS 1
			mysql_query("START TRANSACTION;");
			$deleteSQL = sprintf("UPDATE  crm_integrantes_equipos SET activo = 0 WHERE id_integrante_equipo = %s", 
			valTpDato($idIntegranteEquipo, "int"));
			$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("COMMIT;");
				$objResponse->alert("Se elimino el integrante de equipo");
			} else {
				return $objResponse->alert("El integrante no puede ser eliminado, contien " .$nums. " actividades asignada sin finalizar");
				}
	
	$valBusq = sprintf("|%s|%s",
		$idEmpresa,
		$idEquipo);

	$objResponse->loadCommands(listaEquipoEmpleado(0, "id_empleado", "ASC", $valBusq));
	
	return $objResponse;
}

//GUARDA Y EDITA LOS DATOS DE EQUIPO
function guadarFormEquipo($datosForm){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$queryIdEmpleado = sprintf("SELECT id_empleado FROM pg_usuario WHERE id_usuario = %s LIMIT 1",
						valTpDato($_SESSION["idUsuarioSysGts"],"int"));
	$rsIdEmpleado = mysql_query($queryIdEmpleado);
	if (!$rsIdEmpleado) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rows = mysql_fetch_assoc($rsIdEmpleado);
	$idEmpleado = $rows["id_empleado"];
	
	if ($datosForm['hddidEquipo'] > 0) {
		//VALIDA SI TIENE PERMISO DE EDITAR
		if(!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","editar")){ return $objResponse; }
		
		$updateEquipo = sprintf("UPDATE crm_equipo SET
			nombre_equipo = %s,
			descripcion_equipo = %s,
			id_empleado = %s,
			id_empresa = %s,
			activo = %s,
			jefe_equipo = %s,
			tipo_equipo = %s,
			fecha_edicion = now()
		WHERE id_equipo = %s;",
			valTpDato($datosForm['txtNombreEquipo'], "text"),
			valTpDato($datosForm['areaEquipoDescripcion'], "text"),
			valTpDato($idEmpleado, "int"),	//id del usuario que lo crea
			valTpDato($datosForm['txtIdEmpresa'], "int"),
			valTpDato($datosForm['listEstatus'], "boolean"), 
			valTpDato($datosForm['idHiddJefeEquipo'], "int"),
			valTpdato($datosForm['comboxTipoEquipo'], "text"),
			valTpDato($datosForm['hddidEquipo'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$queryEquipo = mysql_query($updateEquipo);
		if (!$queryEquipo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		$objResponse->alert("Fuente de Informacion Guardada con Éxito");
										 
	} else {
		//VALIDA SI TIENE PERMISO DE INSERTAR
		if (!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","insertar")) {return $objResponse; }
		
		$insetEquipo = sprintf("INSERT INTO crm_equipo(nombre_equipo, descripcion_equipo, id_empleado, id_empresa, activo, jefe_equipo, tipo_equipo)
		VALUES (%s,%s,%s,%s,%s,%s,%s)",
			valTpDato($datosForm['txtNombreEquipo'], "text"),
			valTpDato($datosForm['areaEquipoDescripcion'], "text"),
			valTpDato($idEmpleado, "int"),	//id del usuario que lo crea
			valTpDato($datosForm['txtIdEmpresa'], "int"),
			valTpDato($datosForm['listEstatus'], "boolean"), 
			valTpDato($datosForm['idHiddJefeEquipo'], "int"),
			valTpdato($datosForm['comboxTipoEquipo'], "text"));
		mysql_query("SET NAMES 'utf8'");
		$queryEquipo= mysql_query($insetEquipo);
		if (!$queryEquipo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		$objResponse->alert("Fuente de Informacion Guardada con Éxito");
	}

	mysql_query("COMMIT;");
	
	$objResponse->script("cerrarNuevo()");
	$objResponse->script("byId('butCerrarEquipo').click();"); //CIERRA LA IMG QUE SE MUESTRAL EN LA PARTE DE ATRAS
	
	$objResponse->loadCommands(listaEquipo(0,'',''));
	
	return $objResponse;
}

//MUESTRA LA CONSULTA DE LOS EQUIPOS REGISTRADOS
function listaEquipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL){ 
	
	$objResponse = new xajaxResponse();
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $tipoUsuario;
	
	if($tipoUsuario){	
		$sqlBusq .= $cond.sprintf("WHERE tipo_equipo = %s",
			valTpDato($tipoUsuario, "text"));
	 }
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_equipo.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
			
	}

	$query = sprintf("SELECT
		id_equipo,
		nombre_equipo,
		tipo_equipo,
		descripcion_equipo,
		crm_equipo.id_empresa,
		crm_equipo.id_empleado,
		pg_empresa.id_empresa, nombre_empresa, jefe_equipo,
		pg_empleado.id_empleado,
    CONCAT_WS(' ',nombre_empleado, apellido) AS nombre_jefe_equipo,
    crm_equipo.activo	FROM crm_equipo
		LEFT JOIN pg_empresa ON pg_empresa.id_empresa = crm_equipo.id_empresa
		LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_equipo.jefe_equipo %s", $sqlBusq);
		
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "20%", $pageNum, "nombre_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre de Equipo");
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "5%", $pageNum, "tipo_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Equipo");
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "35%", $pageNum, "descripcion_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion de Equipo");
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Jefe de Equipo");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) { //AQUI ESPESIFICO EL ESTILO Y EL COLOR AL MOVER EL MOUSE SOBRE LOS REGISTRO
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$idEquipo = $row['id_equipo'];
		$nombreEquipo = utf8_encode($row['nombre_equipo']); //htmlentities()
		$tipoEquipo = $row['tipo_equipo'];
		$descripcionEquipo = utf8_encode($row['descripcion_equipo']);//htmlentities()
		$nombreApellidoEmpleado = utf8_encode($row['nombre_jefe_equipo']); //htmlentities()
		$idEmpresa = $row['id_empresa']	;
		$nombreEmpresa = utf8_encode($row['nombre_empresa']);
			
		switch ($row['activo']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = ""; break; 
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".$nombreEmpresa."</td>";
			$htmlTb .= "<td>".$nombreEquipo."</td>";
			$htmlTb .= "<td>".$tipoEquipo."</td>";
			$htmlTb .= "<td>".$descripcionEquipo."</td>";
			$htmlTb .= "<td>".$nombreApellidoEmpleado."</td>";
			$htmlTb .= sprintf("<td align=\"right\" class=\"noprint\"><img class=\"puntero\" onclick=\"xajax_equipoSelect('%s','%s','%s','%s'); openImg(this); \" src=\"../img/iconos/user_suit.png\" title=\"Agregar integrantes\"/ rel=\"#divIntegrante\"></td>",//AGREGAR INTEGRANTES
				$nombreEquipo,
				$idEquipo,
				$tipoEquipo,
				$idEmpresa);
			$htmlTb .= "<td>";
			$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotanteEquipo\" onclick=\"xajax_cargarEquipo(this.id,'%s'); openImg(this); ;\"><img class=\"puntero\" src=\"../img/iconos/ico_edit.png\" title=\"Editar equipo\"/></a>", //EDITAR EQUIPO
					$contFila,
					$idEquipo);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar equipo\"/></td>",
				$idEquipo);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s)\">", 
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
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
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListEquipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	return $objResponse;

	}

//LISTA LOS EMPLEADO SEGUN LA EMPRESA PARA AGREGARLOS AL EQUIPO SELECCIONADO
function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq ="", $maxRows = 10, $totalRows = NULL){
	
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $valCadBusq[0];
	$textCriterio = $valCadBusq[1];
	$idEquipo = $valCadBusq[2];
	$tipoEquipo = $valCadBusq[3];
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s", 
			valTpDato($idEmpresa, "int"));
	}
	
	switch ($tipoEquipo) {
	case "Ventas":
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("clave_filtro IN (1, 2) AND
		activo = 1");
		break;
		
	case "Postventa":
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("clave_filtro IN (4, 5, 6, 7, 8, 26, 400, 25)  AND
		activo = 1");
		break;
	}
	
	//BUSCA POR CEDULA, NOMBRE EMPLEADO, NOMBRE CARGO
	if ($textCriterio != "-1" && $textCriterio != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s )", 
			valTpDato("%".$textCriterio."%", "text"), 
			valTpDato("%".$textCriterio."%", "text"), 
			valTpDato("%".$textCriterio."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado, 
		vw_pg_empleado.cedula, 
		vw_pg_empleado.nombre_empleado, 
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
		//$objResponse->alert($queryLimit);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("C.I. / R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= sprintf("<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleadoEquipo('".$row['id_empleado']."', '".$idEquipo."');\" title=\"Seleccionar Empresa\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>"); // EL BOTON PARA SELECCIONAR
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".$row['nombre_empleado']."</td>";
			$htmlTb .= "<td>".$row['nombre_cargo']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s)\">", 
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf .="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .="selected=\"selected\"";
									}
									$htmlTf .= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s, );\">%s</a>", 
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
	
	$objResponse->assign("divListEmpleado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}
	
//LISTADO DE EMPLEADOS ASIGNADOS AL EQUIPO "SON LOS EMPLEADO QUE INTEGRAN LOS EQUIPOS"
function listaEquipoEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	/*
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s", 
			valTpDato($valCadBusq[0], "int"));
	}*/
	/*
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pg_empleado.cedula LIKE %s
		OR pg_empleado.nombre_empleado LIKE %s
		OR pg_cargo.nombre_cargo LIKE %s)", 
			valTpDato("%".$valCadBusq[1]."%", "text"), 
			valTpDato("%".$valCadBusq[1]."%", "text"), 
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	*/
	$sqlOrd ="crm_integrantes_equipos.activo = 1";
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_integrantes_equipos.id_equipo = %s AND crm_integrantes_equipos.activo = 1", 
			valTpDato($valCadBusq[2], "int"));
	}
		
	$query = sprintf("SELECT id_integrante_equipo, id_equipo,
				crm_integrantes_equipos.id_empleado, cedula,
				concat(nombre_empleado,' ',apellido) AS nombre_empleado, pg_cargo_departamento.id_cargo, nombre_cargo,
				crm_integrantes_equipos.activo
			FROM crm_integrantes_equipos
				LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado
				LEFT JOIN pg_cargo_departamento on pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento
				LEFT JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
				 %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("C.I. / R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Nombre Integrantes"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Cargo"));
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminarIntegrante('%s','%s')\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar Integrante\"/></td>",
				$row['id_integrante_equipo'],
				$row['id_equipo']);// id_integrante_equipo eliminar empleado asignado 
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipoEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipoEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEquipoEmpleado(%s, '%s', '%s', '%s', %s)\">", 
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf .="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .="selected=\"selected\"";
									}
									$htmlTf .= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipoEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipoEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
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
	
	$objResponse->assign("divListIntegrantes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

//PARA LISTAR Y SELECCIONAR LOS JEFES DE EQUIPO "SON LOS EMPLEADO QUE PUEDE SER POSIBLES JEFE DE EQUIPO"
function listaEmpleadoJefeEquipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$tipoEquipo = $valCadBusq[2];
	
	global $tipoUsuario;
	
	if(isset($tipoUsuario)){
		switch ($tipoUsuario) {
				case "Ventas":
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("clave_filtro IN (1, 2) AND
					activo = 1");
					break;
					
				case "Postventa":
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("clave_filtro IN (4, 5, 6, 7, 8, 26, 400) AND
					activo = 1");
					break;
				}
		
		} else{
			switch ($tipoEquipo) {
				case "Ventas":
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("clave_filtro IN (1, 2) AND
					activo = 1");
					break;
					
				case "Postventa":
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("clave_filtro IN (4, 5, 6, 7, 8, 26, 400) AND
					activo = 1");
					break;
				}
			}
	
	

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s", 
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
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
						
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	//return $objResponse->alert($queryLimit);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpleadoJefeEquipo", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleadoJefeEquipo", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("C.I. / R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaEmpleadoJefeEquipo", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleadoJefeEquipo", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= sprintf("<td>"."<button class=\"close\" type=\"button\" onclick=\"xajax_asignarJefeEquipo('".$row['id_empleado']."','".utf8_encode($row['nombre_empleado'])."','".$apellidoEmpleado."'); cerrarJefeEquipo();\" title=\"Seleccionar jefe de equipo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>"); //EL BOTON PARA SELECCIONAR
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleadoJefeEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleadoJefeEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleadoJefeEquipo(%s, '%s', '%s', '%s', %s)\">", 
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleadoJefeEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleadoJefeEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
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
	
	$objResponse->assign("tdLisJefeEquipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

/*REGISTRO LAS FUNCIONES*/
$xajax->register(XAJAX_FUNCTION,"asignarEmpleadoEquipo"); 
$xajax->register(XAJAX_FUNCTION,"asignarJefeEquipo");
$xajax->register(XAJAX_FUNCTION,"buscarEquipo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscaEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscaEmpleadoJefeEquipo");
$xajax->register(XAJAX_FUNCTION,"cargarEquipo"); 
$xajax->register(XAJAX_FUNCTION,"comboxTipoEquipo");
$xajax->register(XAJAX_FUNCTION,"equipoSelect"); 
$xajax->register(XAJAX_FUNCTION,"eliminarEquipo");
$xajax->register(XAJAX_FUNCTION,"eliminarIntegrante");
$xajax->register(XAJAX_FUNCTION,"guadarFormEquipo"); 
$xajax->register(XAJAX_FUNCTION,"listaEquipo"); 
$xajax->register(XAJAX_FUNCTION,"listaEquipoEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");  
$xajax->register(XAJAX_FUNCTION,"listaEmpleadoJefeEquipo"); 

?>