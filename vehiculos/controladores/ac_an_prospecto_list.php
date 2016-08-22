<?php


// MUESTRA LA SECUENCIA DE LAS ACTIVIDADES
function actividadSeguimiento($idCliente){
	$objResponse = new xajaxResponse();
	
	// MUESTRA EL LISTADO DE ACTIVIDADES
	$sqlActividadTipo = sprintf("SELECT * FROM crm_actividad
	WHERE tipo = 'Ventas' AND activo = 1 AND id_empresa = %s", $_SESSION['idEmpresaUsuarioSysGts']);
	$queryActividadTipo = mysql_query($sqlActividadTipo);
	if (!$queryActividadTipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($queryActividadTipo);
	
	$htmltabA = "<table border=\"0\" width=\"100%\">";
	$htmltabA .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmltabA .= "<td></td>";
		$htmltabA .= "<td>Actividad</td>";
		$htmltabA .= "<td>Estado</td>";
	$htmltabA .= "</tr>";
	
	while ($rowsqueryActividadTipo = mysql_fetch_array($queryActividadTipo)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar6";
		$contFila++;
				
		$posicionActividad = $rowsqueryActividadTipo['posicion_actividad'];
		$nombreActividad = utf8_encode($rowsqueryActividadTipo['nombre_actividad']);
		
		$htmltabA .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmltabA .= "<td align=\"right\">".$posicionActividad."</td>";
			$htmltabA .= "<td>".$nombreActividad."</td>";
			
			$queryBuscarActividad = sprintf("SELECT * FROM crm_actividades_ejecucion
			WHERE id = %s
				AND id_actividad = %s
			LIMIT 1",
				valTpDato($idCliente, "int"),
				valTpDato($rowsqueryActividadTipo['id_actividad'], "int"));
			$buscarActividad = mysql_query($queryBuscarActividad);					
			if(!$buscarActividad) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$existe = mysql_num_rows($buscarActividad);				
			$datosActividad = mysql_fetch_array($buscarActividad);
			
			if ($existe) {
				if($datosActividad['estatus'] == "1" ){//asignado
					$imgEstado = '<img src="../img/iconos/ico_aceptar_azul.png" title="Asignado">';
				}elseif($datosActividad['estatus'] == "0" or $datosActividad['estatus'] == "2"){//finalizado
					$imgEstado= '<img src="../img/iconos/ico_aceptar.gif" title="Finalizada">';
				}elseif($datosActividad['estatus'] == "3"){//finalizado automatico
					$imgEstado= '<img src="../img/iconos/arrow_rotate_clockwise.png"/>';
				}
			} else {
				$imgEstado = '<b style="color:#F00">Sin asignar</b>';
			}
			
			$htmltabA .= "<td align='center'>".$imgEstado."</td>";
		$htmltabA .= "</tr>";
	}
	
	if (!($totalRows > 0)) {
		$htmltabA .= "<tr>";
			$htmltabA .= "<td colspan=\"14\">";
				$htmltabA .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmltabA .= "<tr>";
					$htmltabA .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmltabA .= "<td align=\"center\">No existen actividades para el dia de hoy</td>";
				$htmltabA .= "</tr>";
				$htmltabA .= "</table>";
			$htmltabA .= "</td>";
		$htmltabA .= "</tr>";
	}
	
	$htmltabA .= "</table>"; 
	
	$objResponse->assign("divActividadSeguimiento","innerHTML",$htmltabA);	
	
	return $objResponse;
}

function asignarEmpleado($idEmpleado, $idEmpresa, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
	
	// 1 = ASESOR VENTAS VEHICULOS, 2 = GERENTE VENTAS VEHICULOS
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.clave_filtro IN (1,2)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$queryEmpleado = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtIdEmpleado","value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
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

function buscarEmpleado($frmBuscarEmpleado, $frmProspecto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmProspecto['txtIdEmpresa'],
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado']);
	
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listaEmpresa(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarModelo($frmBuscarModelo, $frmProspecto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmProspecto['txtIdEmpresa'],
		$frmBuscarModelo['txtCriterioBuscarModelo']);
	
	$objResponse->loadCommands(listaModelo(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarCliente($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstTipoPago'],
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['lstPagaImpuesto'],
		$frmBuscar['lstTipoCuentaCliente'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstClaveMovimiento($idTipoClave, $idModulo, $tipoPago = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo = %s",
		valTpDato($idModulo, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tipo = %s",
		valTpDato($idTipoClave, "int"));
	
	if ($tipoPago == 0) { // Crédito
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1 OR pago_credito = 1)");
	} else if ($tipoPago == 1) { // Contado
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1 AND pago_credito = 0)");
	}
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento %s ORDER BY clave", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstClaveMovimiento\" name=\"lstClaveMovimiento\" class=\"inputHabilitado\" style=\"width:99%;\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = ($rowClaveMov['id_modulo'] == 0) ? "divMsjInfoSinBorde2" : "divMsjInfoSinBorde";
		
		$selected = ($selId == $row['id_clave_movimiento']) ? "selected=\"selected\"" : "";
		
		$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$row['id_clave_movimiento']."\">".utf8_encode($row['clave'].") ".$row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstClaveMovimiento","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCredito($selId = "") {
	$objResponse = new xajaxResponse();
	
	$arrayDetCredito[0] = "1";
	$arrayDetCredito[1] = "Contado";
	$arrayCredito[] = $arrayDetCredito;
	$arrayDetCredito[0] = "0";
	$arrayDetCredito[1] = "Crédito";
	$arrayCredito[] = $arrayDetCredito;
	
	if ($selId == "0") { // 0 = Crédito
		$onChange = sprintf("selectedOption('lstCredito', 0);");
	} else if ($selId == "1") { // 1 = Contado
		$onChange = sprintf("selectedOption('lstCredito', 1);");
	}
	$onChange .= sprintf("xajax_cargaLstClaveMovimiento('3','0',this.value,'%s');",
		"19");
	
	$html = "<select id=\"lstCredito\" name=\"lstCredito\" onchange=\"".$onChange."\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($arrayCredito as $indice => $valor) {
		$selected = ($selId == $arrayCredito[$indice][0]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$arrayCredito[$indice][0]."\">".$arrayCredito[$indice][1]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCredito","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstado($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$array[] = "Amazonas";			$array[] = "Anzoátegui";		$array[] = "Apure";				$array[] = "Aragua";
	$array[] = "Barinas";			$array[] = "Bolívar";			$array[] = "Carabobo";			$array[] = "Cojedes";
	$array[] = "Delta Amacuro";		$array[] = "Distrito Capital";	$array[] = "Falcón";			$array[] = "Guárico";
	$array[] = "Lara";				$array[] = "Mérida";			$array[] = "Miranda";			$array[] = "Monagas";
	$array[] = "Nueva Esparta";		$array[] = "Portuguesa";		$array[] = "Sucre";				$array[] = "Táchira";
	$array[] = "Trujillo";			$array[] = "Vargas";			$array[] = "Yaracuy";			$array[] = "Zulia";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
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
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoCivil","innerHTML",$html);
	
	return $objResponse;
}

function cargarLstEstatus($id_estatus = "") {
	$objResponse = new xajaxResponse();

	// LLAMA SELECT ESTATUS
	$sql_estatus = "SELECT id_estatus, nombre_estatus FROM crm_estatus
	WHERE activo = 1
		AND id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."';";
	$query_estatus = mysql_query($sql_estatus);
	$rs_estatus = mysql_num_rows($query_estatus);
	$select_estatus = "<select id='id_estatus' name='estatus' class='inputHabilitado'>";
		$select_estatus .= '<option value="">[ Seleccione ]</option>';
	while ($fila_estatus = mysql_fetch_array($query_estatus)) {
		$selected = ($fila_estatus['id_estatus'] == $id_estatus) ? "selected=\"selected\"" : "";
		
		$select_estatus .= '<option '.$selected.' value="'.$fila_estatus['id_estatus'].'">'.utf8_encode($fila_estatus['nombre_estatus']).'</option>';
	}
	$select_estatus .= "</select>";
	$objResponse->assign('td_select_estatus', 'innerHTML', $select_estatus);
	
	return $objResponse;
}

function cargaLstMedio($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'medios' AND status = 1
	ORDER BY item");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMedio\" name=\"lstMedio\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idItem']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMedio","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMotivoRechazo($selId = "", $motivo) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM crm_motivo_rechazo WHERE activo = 1 AND id_empresa = %s;", $_SESSION['idEmpresaUsuarioSysGts']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstMotivoRechazo\" name=\"lstMotivoRechazo\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	if($motivo == 'Rechazo') {
		while ($row = mysql_fetch_assoc($rs)) {
			$idMotivoRechazo = $row['id_motivo_rechazo'];
			$nombreMotivoRechazo = utf8_encode($row['nombre_motivo_rechazo']);
			
			$selected = ($selId == $idMotivoRechazo) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$idMotivoRechazo."\">".$nombreMotivoRechazo."</option>";
		}
	}
	$html .= "</select>";
	$objResponse->assign("td_select_motivo_rechazo","innerHTML",$html);
	
	return $objResponse;

}
	
function cargarLstNivelInfluencia($id_nivel_influencia = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMA SELECT NIVEL INFLUENCIA
	$sql_nivel_influencia = "SELECT id_nivel_influencia, nombre_nivel_influencia FROM crm_nivel_influencia
	WHERE activo = 1
		AND id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."';";
	$query_nivelt_influencia = mysql_query($sql_nivel_influencia);
	$rs_nivel_influencia = mysql_num_rows($query_nivelt_influencia);
	$select_nivel_influencia = "<select id='id_nivel_influencia' name='nivel_influencia' class='inputHabilitado'>";
		$select_nivel_influencia .= '<option value="">[ Seleccione ]</option>';				
	while ($fila_nivel_influencia = mysql_fetch_array($query_nivelt_influencia)) {
		$selected = ($fila_nivel_influencia['id_nivel_influencia'] == $id_nivel_influencia) ? "selected=\"selected\"" : "";
		
		$select_nivel_influencia .= '<option '.$selected.' value="'.$fila_nivel_influencia['id_nivel_influencia'].'">'.utf8_encode($fila_nivel_influencia['nombre_nivel_influencia']).'</option>';
	}
	$select_nivel_influencia .= "</select>";
	$objResponse->assign('td_select_nivel_influencia', 'innerHTML', $select_nivel_influencia);
	
	return $objResponse;
}

function cargaLstPlanPago($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'planesDePago' AND status = 1
	ORDER BY item");
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
	
function cargarLstPosibilidadCierre($id_posibilidad_cierre = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMA SELECT POSIBILIDAD DE CIERRE
	$sql_posibilidad_cierre = "SELECT id_posibilidad_cierre, nombre_posibilidad_cierre FROM crm_posibilidad_cierre
	WHERE activo = 1
		AND id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."';";
	$query_posibilidad_cierre = mysql_query($sql_posibilidad_cierre);
	if (!$query_posibilidad_cierre) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rs_posibilidad_cierre = mysql_num_rows($query_posibilidad_cierre);
	$select_posibilidad_cierre = "<select id='posibilidad_cierre' name='posibilidad_cierre' class='inputHabilitado' onchange='motivoRechazo(this.value)'>";
		$select_posibilidad_cierre .= '<option value="">[ Seleccione ]</option>';
	while ($fila_posibilidad_cierre = mysql_fetch_array($query_posibilidad_cierre)) {
		$selected = ($fila_posibilidad_cierre['id_posibilidad_cierre'] == $id_posibilidad_cierre) ? "selected=\"selected\"" : "";
		
		$select_posibilidad_cierre .= '<option '.$selected.' value="'.$fila_posibilidad_cierre['id_posibilidad_cierre'].'">'.utf8_encode($fila_posibilidad_cierre['nombre_posibilidad_cierre']).'</option>';
	}
	$select_posibilidad_cierre .= "</select>";
	$objResponse->assign('td_select_posibilidad_cierre', 'innerHTML', $select_posibilidad_cierre);

	return $objResponse;
}

function cargarLstPuesto($id_puesto = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMAR SELECT PUESTO
	$sql_puesto = "SELECT id_puesto, nombre_puesto FROM crm_puesto WHERE activo = 1 AND id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."';";
	$query_puesto = mysql_query($sql_puesto);
	$rs_puesto = mysql_num_rows($query_puesto);
	$select_puesto = "<select id='id_puesto' name='puesto' class='inputHabilitado'>";
		$select_puesto .= '<option value="">[ Seleccione ]</option>';
	while ($fila_puesto = mysql_fetch_array($query_puesto)) {
		$selected = ($fila_puesto['id_puesto'] == $id_puesto) ? "selected=\"selected\"" : "";
		
		$select_puesto .= '<option '.$selected.' value="'.$fila_puesto['id_puesto'].'">'.utf8_encode($fila_puesto['nombre_puesto']).'</option>';
	}
	$select_puesto .= "</select>";
	$objResponse->assign('td_select_puesto', 'innerHTML', $select_puesto);
	
	return $objResponse;
}
	
function cargarLstSector($id_sector = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMAR SELECT SECTOR
	$sql_sector = "SELECT id_sector, nombre_sector FROM crm_sector WHERE activo = 1 AND id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."';";
	$query_sector = mysql_query($sql_sector);
	$rs_sector = mysql_num_rows($query_sector);
	$select_sector = "<select id='id_sector' name='sector' class='inputHabilitado'>";
		$select_sector .= '<option value="">[ Seleccione ]</option>';
	while ($fila_sector = mysql_fetch_array($query_sector)) {
		$selected = ($fila_sector['id_sector'] == $id_sector) ? "selected=\"selected\"" : "";
		
		$select_sector .= '<option '.$selected.' value="'.$fila_sector['id_sector'].'">'.utf8_encode($fila_sector['nombre_sector']).'</option>';
	}
	$select_sector .= "</select>";
	$objResponse->assign('td_select_sector', 'innerHTML', $select_sector);
	
	return $objResponse;
}
	
function cargarLstTitulo($id_titulo = "") {
	$objResponse = new xajaxResponse();
	
	// LLENAR SELECT TITULO
	$sql_titulo = "SELECT id_titulo, nombre_titulo FROM crm_titulo WHERE activo = 1 AND id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
	$query_titulo = mysql_query($sql_titulo);
	if (!$query_titulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rs_titulo = mysql_num_rows($query_titulo);	
	$select_titulo = "<select id='id_titulo' name='titulo' class='inputHabilitado'>";
		$select_titulo .= '<option value="">[ Seleccione ]</option>';
	while ($fila_titulo = mysql_fetch_array($query_titulo)) {
		$selected = ($fila_titulo['id_titulo'] == $id_titulo) ? "selected=\"selected\"" : "";
		
		$select_titulo .= '<option '.$selected.' value="'.$fila_titulo['id_titulo'].'">' .utf8_encode($fila_titulo['nombre_titulo']). '</option>';
	}
	$select_titulo .= "</select>";
	$objResponse->assign("td_select_titulo","innerHTML",$select_titulo);
	
	return $objResponse;
}

//MUESTRA LOS DOCUMENTOS SEGUN EL PLAN DE PAGO SELECCIONADO POR EL CLIENTE
function cargaDocumentosNecesario($idCliente){
	$objResponse = new xajaxResponse();
	
	//CONSULTA SI TIENE DOCUMENTOS RECAUDADOS
	$sqlDocumentosrecaudados = sprintf("SELECT
		crm_documentos_recaudados.id_perfil_prospecto,
		id,
		id_documento_venta
	FROM crm_documentos_recaudados
		LEFT JOIN crm_perfil_prospecto ON crm_perfil_prospecto.id_perfil_prospecto = crm_documentos_recaudados.id_perfil_prospecto
	WHERE id = %s",
		valTpDato($idCliente, "int"));
	$queryDocumentosRecaudados = mysql_query($sqlDocumentosrecaudados);
	if (!$queryDocumentosRecaudados) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowsDocumentoRecaudados = mysql_fetch_array($queryDocumentosRecaudados)){
		$idPerfilRecaudados = $rowsDocumentoRecaudados['id_perfil_prospecto'];
		$idDocumentoVenta = $rowsDocumentoRecaudados['id_documento_venta'];
		
		$documento .= " ".$idPerfilRecaudados." ".$idDocumentoVenta."<br>";
		$documentosRecaudados[] = $rowsDocumentoRecaudados['id_documento_venta'];
	}
	
	// CONSULTA EL TIPO DE PAGO
	$sqlTipoPago = sprintf("SELECT id_prospecto_vehiculo, id_cliente, id_plan_pago, idItem, item
	FROM an_prospecto_vehiculo
		LEFT JOIN grupositems ON grupositems.idItem = an_prospecto_vehiculo.id_plan_pago
	WHERE id_cliente = %s;",
		valTpDato($idCliente, "int"));
	$queryTipoPago = mysql_query($sqlTipoPago);
	if (!$queryTipoPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsTipoPago = mysql_num_rows($queryTipoPago);
	$rowsTipoPago = mysql_fetch_array($queryTipoPago);
	
	$tipoPago = $rowsTipoPago['idItem'];
	// CONSULTA EL DOCUMENTO SEGUN EL TIPO DE PAGO						
	$sqlDocumentos = sprintf("SELECT * FROM crm_documentos_ventas WHERE id_tipo_documento = '%s' AND activo = 1;", $tipoPago);
	$queryDocumentos = mysql_query($sqlDocumentos);
	if (!$queryDocumentos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($queryDocumentos);
	
	$htmlTab = "<table width=\"100%\">";
	while ($rows = mysql_fetch_array($queryDocumentos)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar6";
		$contFila++;
		
		if ($documentosRecaudados){
			if (in_array($rows['id_documento_venta'], $documentosRecaudados)) {
				$checked = "checked = 'checked' disabled='disabled';";
				$t = '<img src="../img/minselect.png" width="13" height="13" />';
			} else {
				$checked = " ";
				$t = " ";
			}
		}
		
		$htmlTab .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTab .= '<td>'.utf8_encode($rows['descripcion_documento']).'</td>';
			$htmlTab .= '<td><input id="checkDocuemento" name="checkDocuemento[]" type="checkbox", '.$checked.' value="'.$rows['id_documento_venta'].'"/>'.$t.'</td>';
		$htmlTab .= '</tr>';
	}
	
	if (!($totalRows > 0)) {
		$htmlTab .= "<tr>";
			$htmlTab .= "<td colspan=\"14\">";
				$htmlTab .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTab .= "<tr>";
					$htmlTab .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTab .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTab .= "</tr>";
				$htmlTab .= "</table>";
			$htmlTab .= "</td>";
		$htmlTab .= "</tr>";
	}
	
	$htmlTab .= '</table>';
	
	// CONSULTA SI ESTE ID TIENE UN PERFIL PROSPECTO
	$sqlPerfilProspecto = sprintf("SELECT id_perfil_prospecto FROM crm_perfil_prospecto
	WHERE id = %s;",
		valTpDato($idCliente, "int"));
	$queryPerfilProspecto = mysql_query($sqlPerfilProspecto);
	$rowsPerfilProspecto = mysql_fetch_array($queryPerfilProspecto);
	$rowsPerfilProspecto['id_perfil_prospecto'];
	
	$objResponse->assign("hddIdPerfilProspecto","value",$rowsPerfilProspecto);
	
	$objResponse->script("mostrarDocumentosRecaudos()");
	$objResponse->script("mostrarActividadAsignadas()");	
	$objResponse->script("mostarSeguimiento()");
	
	$objResponse->assign("divDocumentosAEntregar","innerHTML",$htmlTab);
	
	return $objResponse;
}

function eliminarClienteEmpresa($frmCliente){
	$objResponse = new xajaxResponse();
	
	if (isset($frmCliente['cbxItm'])) {
		foreach($frmCliente['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
		$objResponse->script("xajax_eliminarClienteEmpresa(xajax.getFormValues('frmCliente'));");
	}
	
	return $objResponse;
}

function eliminarModelo($frmProspecto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmProspecto['cbxItmModeloInteres'])) {
		foreach($frmProspecto['cbxItmModeloInteres'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmModeloInteres:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}		
		$objResponse->script("xajax_eliminarModelo(xajax.getFormValues('frmProspecto'));");
	}
	
	return $objResponse;
}

function exportarCliente($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstTipoPago'],
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['lstPagaImpuesto'],
		$frmBuscar['lstTipoCuentaCliente'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/an_prospecto_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formCliente($idCliente, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCliente['cbx'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
	}
	
	if ($idCliente > 0) {
		if (!xvalidaAcceso($objResponse,"cc_cliente_list","editar")) { $objResponse->script("byId('btnCancelarCliente').click();"); return $objResponse; }
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		// BUSCA LOS DATOS DEL CLIENTE
		$query = sprintf("SELECT *,
			(CASE
				WHEN lci IS NOT NULL THEN
					CONCAT_WS('-',lci,ci)
				ELSE 
					ci
			END) AS ci_cliente,
			
			(CASE
				WHEN lci2 IS NOT NULL THEN
					CONCAT_WS('-',lci2,cicontacto)
				ELSE 
					cicontacto
			END) AS ci_contacto
		FROM cj_cc_cliente
		WHERE id = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if ($row['tipo_cuenta_cliente'] == 1) { // 1 = Prospecto, 2 = Cliente
			$objResponse->script("
			byId('lstTipo').className = 'inputHabilitado';
			byId('txtCedula').className = 'inputHabilitado';
			byId('txtNombre').className = 'inputHabilitado';
			byId('txtApellido').className = 'inputHabilitado';
			
			byId('txtCedula').readOnly = false;
			byId('txtNombre').readOnly = false;
			byId('txtApellido').readOnly = false;");
		}
		
		$objResponse->assign("hddIdCliente","value",$row['id']);
		
		$tipoPago = ($row['credito'] == "si") ? "0" : "1";
		$objResponse->loadCommands(cargaLstCredito($tipoPago));
		$objResponse->loadCommands(cargaLstClaveMovimiento("3","0",$tipoPago,$row['id_clave_movimiento_predeterminado']));
		
		$objResponse->script("selectedOption('lstTipo', '".$row['tipo']."')");
		$objResponse->script("byId('lstTipo').onchange = function() { selectedOption(this.id, '".$row['tipo']."'); }");
		$objResponse->assign("txtCedula","value",$row['ci_cliente']);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombre']));
		$objResponse->assign("txtNit","value",$row['nit']);
		$objResponse->assign("txtApellido","value",utf8_encode($row['apellido']));
		$objResponse->script("selectedOption('lstContribuyente', '".$row['contribuyente']."')");
		$objResponse->assign("txtEstado","value",utf8_encode($row['estado']));
		$objResponse->assign("txtCiudad","value",utf8_encode($row['ciudad']));
		
		$arrayDireccion = explode(";",utf8_encode($row['direccion']));
		$objResponse->assign("txtUrbanizacion","value",trim($arrayDireccion[0]));
		$objResponse->assign("txtCalle","value",trim($arrayDireccion[1]));
		$objResponse->assign("txtCasa","value",trim($arrayDireccion[2]));
		$objResponse->assign("txtMunicipio","value",trim($arrayDireccion[3]));
		
		$objResponse->assign("txtTelefono","value",$row['telf']);
		$objResponse->assign("txtOtroTelefono","value",$row['otrotelf']);
		$objResponse->assign("txtCorreo","value",$row['correo']);
		
		$objResponse->script("selectedOption('lstReputacionCliente', '".((strlen($row['reputacionCliente']) > 0) ? $row['reputacionCliente'] : "CLIENTE B")."');");
		$objResponse->script("selectedOption('lstTipoCliente', '".((strlen($row['tipocliente']) > 0) ? $row['tipocliente'] : "Vehiculos")."');");
		$objResponse->script("selectedOption('lstDescuento', '".$row['descuento']."');");
		$objResponse->script("
		byId('lstDescuento').onchange = function() {
			selectedOption(this.id, ".$row['descuento'].");
		}");
		$objResponse->script("selectedOption('lstEstatus', '".$row['status']."');");
		$objResponse->script("byId('cbxPagaImpuesto').checked = ".(($row['paga_impuesto'] == "1") ? 'true' : 'false'));
		$objResponse->script("byId('cbxBloquearVenta').checked = ".(($row['bloquea_venta'] == "1") ? 'true' : 'false'));
		
		$objResponse->assign("txtFechaCreacion","value",date("d-m-Y",strtotime($row['fcreacion'])));
		$objResponse->assign("txtFechaDesincorporar","value",implode("-",array_reverse(explode("-",$row['fdesincorporar']))));
		
		$objResponse->assign("txtCedulaContacto","value",$row['ci_contacto']);
		$objResponse->assign("txtNombreContacto","value",utf8_encode($row['contacto']));
		$objResponse->assign("txtTelefonoContacto","value",$row['telfcontacto']);
		$objResponse->assign("txtCorreoContacto","value",$row['correocontacto']);
		
		$queryClienteEmpresa = sprintf("SELECT * FROM cj_cc_cliente_empresa cliente_emp
		WHERE cliente_emp.id_cliente = %s
		ORDER BY cliente_emp.id_empresa ASC;",
			valTpDato($idCliente, "int"));
		$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
		if (!$rsClienteEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClienteEmpresa = mysql_fetch_array($rsClienteEmpresa)) {
			$Result1 = insertarItemClienteEmpresa($contFila, $rowClienteEmpresa['id_cliente_empresa']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
	} else {
		if (!xvalidaAcceso($objResponse,"cc_cliente_list","insertar")) { $objResponse->script("byId('btnCancelarCliente').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstCredito("1"));
		$objResponse->call("selectedOption","lstContribuyente","No");
		$objResponse->call("selectedOption","lstEstatus","Activo");
		$objResponse->assign("txtFechaCreacion","value",date("d-m-Y"));
		$objResponse->assign("txtFechaDesincorporar","value",date("d-m-Y",dateAddLab(strtotime(date("d-m-Y")),364,false)));
		$objResponse->loadCommands(cargaLstClaveMovimiento("3","0","1","19"));
		$objResponse->call("selectedOption","lstDescuento",0);
		$objResponse->script("
		byId('lstDescuento').onchange = function() {
			selectedOption(this.id, ".(0).");
		}");
		$objResponse->call("selectedOption","lstTipoCliente","Vehiculos");
		$objResponse->call("selectedOption","lstReputacionCliente","CLIENTE B");
	}
	
	return $objResponse;
}

function formCredito($hddNumeroItm, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_clientes_credito")) { $objResponse->script("byId('btnCancelarCredito').click();"); return $objResponse; }
	
	if ($frmCliente['lstCredito'] != "0") {
		sleep(1);
		$objResponse->alert("Tipo de cliente inválido para esta acción");
		$objResponse->script("byId('btnCancelarCredito').click();");
		return $objResponse;
	}
	
	$objResponse->assign("hddNumeroItm","value",$hddNumeroItm);
	$objResponse->assign("txtDiasCredito","value",$frmCliente['txtDiasCredito'.$hddNumeroItm]);
	$objResponse->assign("txtLimiteCredito","value",$frmCliente['txtLimiteCredito'.$hddNumeroItm]);
	$objResponse->loadCommands(cargaLstFormaPago($frmCliente['txtFormaPago'.$hddNumeroItm]));
	
	return $objResponse;
}

function formProspecto($idCliente, $frmProspecto) {
	$objResponse = new xajaxResponse();
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmProspecto['cbx1'];
	
	if (isset($arrayObj1)) {
		foreach($arrayObj1 as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmModeloInteres:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
		
	// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);
	
	if ($idCliente > 0) {
		if (!xvalidaAcceso($objResponse,"an_prospecto_list","editar")) { $objResponse->script("byId('btnCancelarProspecto').click();"); return $objResponse; }
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';");
		
		$query = sprintf("SELECT *,
			(CASE
				WHEN lci IS NOT NULL THEN
					CONCAT_WS('-',lci,ci)
				ELSE 
					ci
			END) AS ci_cliente,
			
			(SELECT CONCAT_WS(' ',nombre_empleado,apellido) AS nombre_empleado FROM pg_empleado
			WHERE id_empleado = cj_cc_cliente.id_empleado_creador) AS nombre_empleado
		FROM cj_cc_cliente
		WHERE id = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if ($row['tipo_cuenta_cliente'] == 1) { // 1 = Prospecto, 2 = Cliente
			$objResponse->script("
			byId('lstTipoProspecto').className = 'inputHabilitado';
			byId('txtCedulaProspecto').className = 'inputHabilitado';
			byId('txtNombreProspecto').className = 'inputHabilitado';
			byId('txtApellidoProspecto').className = 'inputHabilitado';
			
			byId('txtCedulaProspecto').readOnly = false;
			byId('txtNombreProspecto').readOnly = false;
			byId('txtApellidoProspecto').readOnly = false;
			
			byId('tdFlotanteTitulo1').innerHTML = 'Editar Prospecto';");
		} else {
			$objResponse->script("
			byId('tdFlotanteTitulo1').innerHTML = 'Editar Cliente';");
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "", false));
		
		$txtIdEmpleado = ($row['id_empleado_creador'] > 0) ? $row['id_empleado_creador'] : $rowUsuario['id_empleado'];
		$txtNombreEmpleado = ($row['id_empleado_creador'] > 0) ? $row['nombre_empleado'] : $rowUsuario['nombre_empleado'];
		$objResponse->assign("txtIdEmpleado","value",$txtIdEmpleado);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($txtNombreEmpleado));
		$objResponse->assign("hddIdClienteProspecto","value",$row['id']);
		switch ($row['tipo']) {
			case "Natural" : $lstTipoProspecto = "Natural"; break;
			case "Juridico" : $lstTipoProspecto = "Juridico"; break;
		}
		$objResponse->script("selectedOption('lstTipoProspecto', '".$lstTipoProspecto."');");
		$objResponse->script("byId('lstTipoProspecto').onchange = function() { selectedOption(this.id, '".$lstTipoProspecto."'); }");
		$objResponse->assign("txtCedulaProspecto","value",$row['ci_cliente']);
		$objResponse->assign("txtNombreProspecto","value",utf8_encode($row['nombre']));
		$objResponse->assign("txtApellidoProspecto","value",utf8_encode($row['apellido']));
		
		$objResponse->assign("txtUrbanizacionProspecto","value",utf8_encode($row['urbanizacion']));
		$objResponse->assign("txtCalleProspecto","value",utf8_encode($row['calle']));
		$objResponse->assign("txtCasaProspecto","value",utf8_encode($row['casa']));
		$objResponse->assign("txtMunicipioProspecto","value",utf8_encode($row['municipio']));
		$objResponse->assign("txtCiudadProspecto","value",utf8_encode($row['ciudad']));
		$objResponse->assign("txtEstadoProspecto","value",utf8_encode($row['estado']));
		$objResponse->assign("txtTelefonoProspecto","value",$row['telf']);
		$objResponse->assign("txtOtroTelefonoProspecto","value",$row['otrotelf']);
		$objResponse->assign("txtEmailProspecto","value",utf8_encode($row['correo']));
		
		$objResponse->assign("txtUrbanizacionComp","value",utf8_encode($row['urbanizacion_comp']));
		$objResponse->assign("txtCalleComp","value",utf8_encode($row['calle_comp']));
		$objResponse->assign("txtCasaComp","value",utf8_encode($row['casa_comp']));
		$objResponse->assign("txtMunicipioComp","value",utf8_encode($row['municipio_comp']));
		$objResponse->assign("txtEstadoComp","value",utf8_encode($row['estado_comp']));
		$objResponse->assign("txtTelefonoComp","value",$row['telf_comp']);
		$objResponse->assign("txtOtroTelefonoComp","value",$row['otro_telf_comp']);
		$objResponse->assign("txtEmailComp","value",utf8_encode($row['correo_comp']));
		$objResponse->assign("txtFechaUltAtencion","value",date("d-m-Y",strtotime($row['fechaUltimaAtencion'])));
		$objResponse->assign("txtFechaUltEntrevista","value",date("d-m-Y",strtotime($row['fechaUltimaEntrevista'])));
		$objResponse->assign("txtFechaProxEntrevista","value",date("d-m-Y",strtotime($row['fechaProximaEntrevista'])));
		
		// BUSCA LOS MODELOS DE INTERES
		$query = sprintf("SELECT 
			id_prospecto_vehiculo,
			id_cliente,
			id_unidad_basica,
			precio_unidad_basica,
			id_medio,
			id_nivel_interes,
			id_plan_pago
		FROM an_prospecto_vehiculo prosp_vehi
		WHERE id_cliente = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemModeloInteres($contFila, $row['id_prospecto_vehiculo']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		$sql_perfil_prospecto = "SELECT * FROM crm_perfil_prospecto WHERE id = $idCliente;";
		$query_perfil_prospecto = mysql_query($sql_perfil_prospecto);
		if(!$query_perfil_prospecto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$existe_perfil =mysql_num_rows($query_perfil_prospecto);
		$ros = mysql_fetch_array($query_perfil_prospecto);
			
		$id_puesto = $ros['id_puesto'];
		$id_titulo = $ros['id_titulo'];
		$id_sector = $ros['id_sector']; 
		$id_posibilidad_cierre = $ros['id_posibilidad_cierre'];
		$id_nivel_influencia = $ros['id_nivel_influencia'];
		$id_motivo_rechazo = $ros['id_motivo_rechazo'];
		$id_estatus = $ros['id_estatus'];
		$compania = $ros['compania'];
		$estado_civil = $ros['id_estado_civil'];
		$sexo = $ros['sexo'];
		$clase_social =$ros['clase_social'];
		$observacion = $ros['observacion'];
		
		$objResponse->loadCommands(cargaLstEstadoCivil($estado_civil));
		switch ($sexo) {
			case "F" : $objResponse->script("byId('rdbSexoF').checked = true;"); break;
			case "M" : $objResponse->script("byId('rdbSexoM').checked = true;"); break;
		}

		$objResponse->assign("txtCompania","value",utf8_encode($compania));
		$objResponse->assign("txtFechaNacimiento","value",implode("-",array_reverse(explode("-",$ros['fecha_nacimiento']))));
		$objResponse->assign("txtObservacion","innerHTML",utf8_encode($observacion));
		$objResponse->script("selectedOption('lstNivelSocial', '".$clase_social."')");
		$objResponse->loadCommands(cargarLstPuesto($id_puesto));
		$objResponse->loadCommands(cargarLstTitulo($id_titulo));
		$objResponse->loadCommands(cargarLstSector($id_sector)); 
		$objResponse->loadCommands(cargarLstNivelInfluencia($id_nivel_influencia));
		$objResponse->loadCommands(cargarLstEstatus($id_estatus));
		$objResponse->loadCommands(cargarLstPosibilidadCierre($id_posibilidad_cierre));
		
		$queryPosibilidad = sprintf("SELECT * FROM crm_posibilidad_cierre WHERE id_posibilidad_cierre = %s;",
			valTpDato($id_posibilidad_cierre, "int"));
		$rs = mysql_query($queryPosibilidad);
		if(!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$motivo = $row['nombre_posibilidad_cierre'];
			
		$objResponse->loadCommands(cargaLstMotivoRechazo($id_motivo_rechazo, $motivo));
		
	} else {
		if (!xvalidaAcceso($objResponse,"an_prospecto_list","insertar")) { $objResponse->script("byId('btnCancelarProspecto').click();"); return $objResponse; }
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';");
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "", false));
		
		$objResponse->assign("txtIdEmpleado","value",$rowUsuario['id_empleado']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']));
		$objResponse->script("byId('lstTipoProspecto').onchange = function() { }");
		
		$objResponse->loadCommands(cargaLstEstadoCivil());
		$objResponse->loadCommands(cargarLstPuesto());
		$objResponse->loadCommands(cargarLstTitulo());
		$objResponse->loadCommands(cargarLstSector()); 
		$objResponse->loadCommands(cargarLstNivelInfluencia());
		$objResponse->loadCommands(cargarLstEstatus());
		$objResponse->loadCommands(cargarLstPosibilidadCierre());
		$objResponse->loadCommands(cargaLstMotivoRechazo('', $motivo));
	}
	
	return $objResponse;
}

function guardarCliente($frmCliente, $frmListaCliente) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $arrayValidarCI;
	global $arrayValidarRIF;
	global $arrayValidarNIT;
	global $spanEstado;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCliente['cbx'];
	
	mysql_query("START TRANSACTION;");
	
	$idCliente = $frmCliente['hddIdCliente'];
	
	switch($frmCliente['lstTipo']) {
		case 1 :
			$lstTipo = "Natural";
			$arrayValidar = $arrayValidarCI;
			break;
		case 2 :
			$lstTipo = "Juridico";
			$arrayValidar = $arrayValidarRIF;
			break;
	}
	
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmCliente['txtCedula'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtCedula').className = 'inputErrado'");
			errorGuardarCliente($objResponse);
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$arrayValidar = $arrayValidarNIT;
	if (isset($arrayValidar)) {
		if (strlen($frmCliente['txtNit']) > 0) {
			$valido = false;
			foreach ($arrayValidar as $indice => $valor) {
				if (preg_match($valor, $frmCliente['txtNit'])) {
					$valido = true;
				}
			}
			
			if ($valido == false) {
				$objResponse->script("byId('txtNit').className = 'inputErrado'");
				errorGuardarCliente($objResponse);
				return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
			}
		}
	}
	
	$arrayValidar = array_merge($arrayValidarCI, $arrayValidarRIF);
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmCliente['txtCedulaContacto'])) {
				$valido = true;
			}
		}
		
		if ($valido == false && strlen($frmCliente['txtCedulaContacto']) > 0) {
			$objResponse->script("byId('txtCedulaContacto').className = 'inputErrado'");
			errorGuardarCliente($objResponse);
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$txtCiCliente = explode("-",$frmCliente['txtCedula']);
	if (is_numeric($txtCiCliente[0]) == true) {
		$txtCiCliente = implode("-",$txtCiCliente);
	} else {
		$txtLciCliente = $txtCiCliente[0];
		array_shift($txtCiCliente);
		$txtCiCliente = implode("-",$txtCiCliente);
	}
	
	$txtCiContacto = explode("-",$frmCliente['txtCedulaContacto']);
	if (is_numeric($txtCiContacto[0]) == true) {
		$txtCiContacto = implode("-",$txtCiContacto);
	} else {
		$txtLciContacto = $txtCiContacto[0];
		array_shift($txtCiContacto);
		$txtCiContacto = implode("-",$txtCiContacto);
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
		valTpDato($idCliente, "int"),
		valTpDato($idCliente, "int"));
	$rs = mysql_query($query);
	if (!$rs) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		errorGuardarCliente($objResponse);
		return $objResponse->alert("Ya existe la ".$spanClienteCxC." ingresada");
	}
	
	$frmCliente['txtUrbanizacion'] = str_replace(",", "", $frmCliente['txtUrbanizacion']);
	$frmCliente['txtCalle'] = str_replace(",", "", $frmCliente['txtCalle']);
	$frmCliente['txtCasa'] = str_replace(",", "", $frmCliente['txtCasa']);
	$frmCliente['txtMunicipio'] = str_replace(",", "", $frmCliente['txtMunicipio']);
	$frmCliente['txtCiudad'] = str_replace(",", "", $frmCliente['txtCiudad']);
	$frmCliente['txtEstado'] = str_replace(",", "", $frmCliente['txtEstado']);
	
	$txtDireccion = implode("; ", array(
		$frmCliente['txtUrbanizacion'],
		$frmCliente['txtCalle'],
		$frmCliente['txtCasa'],
		$frmCliente['txtMunicipio'],
		$frmCliente['txtCiudad'],
		((strlen($frmCliente['txtEstado']) > 0) ? $spanEstado : "")." ".$frmCliente['txtEstado']));
	
	$lstCredito = ($frmCliente['lstCredito'] == "0") ? "si" : "no";
	$cbxPagaImpuesto = (isset($frmCliente['cbxPagaImpuesto'])) ? 1 : 0;
	$cbxBloquearVenta = (isset($frmCliente['cbxBloquearVenta'])) ? 1 : 0;

	if ($idCliente > 0) {
		if (!((($frmCliente['lstCredito'] == "0" || $frmCliente['lstCredito'] == "Si") && xvalidaAcceso($objResponse,"cc_clientes_credito","editar"))
		|| (($frmCliente['lstCredito'] != "0" || $frmCliente['lstCredito'] != "Si") && xvalidaAcceso($objResponse,"cc_clientes_contado","editar")))) {
			errorGuardarCliente($objResponse); return $objResponse;
		}
		
		$updateSQL = sprintf("UPDATE cj_cc_cliente SET
			nit = %s,
			contribuyente = %s,
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
			contacto = %s,
			lci2 = %s,
			cicontacto = %s,
			telfcontacto = %s,
			correocontacto = %s,
			reputacionCliente = %s,
			descuento = %s,
			fcreacion = %s,
			status = %s,
			credito = %s,
			tipocliente = %s,
			fdesincorporar = %s,
			id_clave_movimiento_predeterminado = %s,
			paga_impuesto = %s,
			bloquea_venta = %s,
			tipo_cuenta_cliente = %s
		WHERE id = %s;",
			valTpDato($frmCliente['txtNit'], "text"),
			valTpDato($frmCliente['lstContribuyente'], "text"),
			valTpDato($frmCliente['txtUrbanizacion'], "text"),
			valTpDato($frmCliente['txtCalle'], "text"),
			valTpDato($frmCliente['txtCasa'], "text"),
			valTpDato($frmCliente['txtMunicipio'], "text"),
			valTpDato($frmCliente['txtCiudad'], "text"),
			valTpDato($frmCliente['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmCliente['txtTelefono'], "text"),
			valTpDato($frmCliente['txtOtroTelefono'], "text"),
			valTpDato($frmCliente['txtCorreo'], "text"),
			valTpDato($frmCliente['txtNombreContacto'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCiContacto, "text"),
			valTpDato($frmCliente['txtTelefonoContacto'], "text"),
			valTpDato($frmCliente['txtCorreoContacto'], "text"),
			valTpDato($frmCliente['lstReputacionCliente'], "text"),
			valTpDato($frmCliente['lstDescuento'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmCliente['txtFechaCreacion'])),"date"),
			valTpDato($frmCliente['lstEstatus'], "text"),
			valTpDato($lstCredito, "text"),
			valTpDato($frmCliente['lstTipoCliente'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmCliente['txtFechaDesincorporar'])),"date"),
			valTpDato($frmCliente['lstClaveMovimiento'], "int"),
			valTpDato($cbxPagaImpuesto, "int"),
			valTpDato($cbxBloquearVenta, "int"),
			valTpDato(2, "int"), // 1 = Prospecto, 2 = Cliente
			valTpDato($idCliente, "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!((($frmCliente['lstCredito'] == "0" || $frmCliente['lstCredito'] == "Si") && xvalidaAcceso($objResponse,"cc_clientes_credito","insertar"))
		|| (($frmCliente['lstCredito'] != "0" || $frmCliente['lstCredito'] != "Si") && xvalidaAcceso($objResponse,"cc_clientes_contado","insertar")))) {
			errorGuardarCliente($objResponse); return $objResponse;
		}
		
		$insertSQL = sprintf("INSERT INTO cj_cc_cliente (tipo, nombre, apellido, lci, ci, nit, contribuyente, urbanizacion, calle, casa, municipio, ciudad, estado, direccion, telf, otrotelf, correo, contacto, lci2, cicontacto, telfcontacto, correocontacto, reputacionCliente, descuento, fcreacion, status, credito, tipocliente, fdesincorporar, id_clave_movimiento_predeterminado, paga_impuesto, bloquea_venta, tipo_cuenta_cliente)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($lstTipo, "text"),
			valTpDato($frmCliente['txtNombre'], "text"),
			valTpDato($frmCliente['txtApellido'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCiContacto, "text"),
			valTpDato($frmCliente['txtNit'], "text"),
			valTpDato($frmCliente['lstContribuyente'], "text"),
			valTpDato($frmCliente['txtUrbanizacion'], "text"),
			valTpDato($frmCliente['txtCalle'], "text"),
			valTpDato($frmCliente['txtCasa'], "text"),
			valTpDato($frmCliente['txtMunicipio'], "text"),
			valTpDato($frmCliente['txtCiudad'], "text"),
			valTpDato($frmCliente['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmCliente['txtTelefono'], "text"),
			valTpDato($frmCliente['txtOtroTelefono'], "text"),
			valTpDato($frmCliente['txtCorreo'], "text"),
			valTpDato($frmCliente['txtNombreContacto'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCiContacto, "text"),
			valTpDato($frmCliente['txtTelefonoContacto'], "text"),
			valTpDato($frmCliente['txtCorreoContacto'], "text"),
			valTpDato($frmCliente['lstReputacionCliente'], "text"),
			valTpDato($frmCliente['lstDescuento'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmCliente['txtFechaCreacion'])),"date"),
			valTpDato($frmCliente['lstEstatus'], "text"),
			valTpDato($lstCredito, "text"),
			valTpDato($frmCliente['lstTipoCliente'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmCliente['txtFechaDesincorporar'])),"date"),
			valTpDato($frmCliente['lstClaveMovimiento'], "int"),
			valTpDato($cbxPagaImpuesto, "int"),
			valTpDato($cbxBloquearVenta, "int"),
			valTpDato(2, "int")); // 1 = Prospecto, 2 = Cliente
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idCliente = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICA SI LAS EMPRESAS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryClienteEmpresa = sprintf("SELECT * FROM cj_cc_cliente_empresa cliente_emp
	WHERE id_cliente = %s;",
		valTpDato($idCliente, "int"));
	$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
	if (!$rsClienteEmpresa) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowClienteEmpresa = mysql_fetch_assoc($rsClienteEmpresa)) {
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if ($rowClienteEmpresa['id_cliente_empresa'] == $frmCliente['hddIdClienteEmpresa'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM cj_cc_credito
			WHERE id_cliente_empresa = %s
				AND creditoreservado = 0;",
				valTpDato($rowClienteEmpresa['id_cliente_empresa'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$deleteSQL = sprintf("DELETE FROM cj_cc_cliente_empresa WHERE id_cliente_empresa = %s;",
				valTpDato($rowClienteEmpresa['id_cliente_empresa'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA LAS EMPRESAS PARA EL CLIENTE
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idClienteEmpresa = $frmCliente['hddIdClienteEmpresa'.$valor];
			$idEmpresa = $frmCliente['hddIdEmpresa'.$valor];
			$idCredito = $frmCliente['hddIdCredito'.$valor];
			
			if ($idClienteEmpresa > 0) {
			} else {
				$insertSQL = sprintf("INSERT INTO cj_cc_cliente_empresa (id_cliente, id_empresa)
				VALUE (%s, %s);",
					valTpDato($idCliente, "int"),
					valTpDato($idEmpresa, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idClienteEmpresa = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			if (in_array($frmCliente['lstCredito'], array("0","Si"))) {
				if ($idCredito > 0) {
					if (!xvalidaAcceso($objResponse,"cc_clientes_credito","editar")) { errorGuardarCliente($objResponse); return $objResponse; }
					
					if ($frmCliente['txtDiasCredito'.$valor] == 0 && $frmCliente['txtLimiteCredito'.$valor] == 0) {
						$deleteSQL = sprintf("DELETE FROM cj_cc_credito
						WHERE id = %s
							AND creditoreservado = 0;",
							valTpDato($idCredito, "int"));
						$Result1 = mysql_query($deleteSQL);
						if (!$Result1) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					} else {
						$updateSQL = sprintf("UPDATE cj_cc_credito SET
							diascredito = %s,
							limitecredito = %s,
							fpago = %s
						WHERE id = %s;",
							valTpDato($frmCliente['txtDiasCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtLimiteCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtFormaPago'.$valor], "text"),
							valTpDato($idCredito, "int"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				} else {
					if (str_replace(",","",$frmCliente['txtLimiteCredito'.$valor]) > 0) {
						if (!xvalidaAcceso($objResponse,"cc_clientes_credito","insertar")) { errorGuardarCliente($objResponse); return $objResponse; }
						
						$insertSQL = sprintf("INSERT INTO cj_cc_credito (id_cliente_empresa, diascredito, limitecredito, fpago)
						VALUE (%s, %s, %s, %s);",
							valTpDato($idClienteEmpresa, "int"),
							valTpDato($frmCliente['txtDiasCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtLimiteCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtFormaPago'.$valor], "text"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}	
				}
			}
			
			// ACTUALIZA EL CREDITO DISPONIBLE
			$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
				creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
															WHERE fact_vent.idCliente = cliente_emp.id_cliente
																AND fact_vent.id_empresa = cliente_emp.id_empresa
																AND fact_vent.estadoFactura IN (0,2)), 0)
													+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
															WHERE nota_cargo.idCliente = cliente_emp.id_cliente
																AND nota_cargo.id_empresa = cliente_emp.id_empresa
																AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
													- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
															WHERE anticip.idCliente = cliente_emp.id_cliente
																AND anticip.id_empresa = cliente_emp.id_empresa
																AND anticip.estadoAnticipo IN (1,2)
																AND anticip.estatus = 1), 0)
													- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
															WHERE nota_cred.idCliente = cliente_emp.id_cliente
																AND nota_cred.id_empresa = cliente_emp.id_empresa
																AND nota_cred.estadoNotaCredito IN (1,2)), 0)
													+ IFNULL((SELECT
																SUM(IFNULL(ped_vent.subtotal, 0)
																	- IFNULL(ped_vent.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																			WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
																	+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																			WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
															FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_cliente = cliente_emp.id_cliente
																AND ped_vent.id_empresa = cliente_emp.id_empresa
																AND ped_vent.estatus_pedido_venta IN (2)), 0)),
				creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
											WHERE fact_vent.idCliente = cliente_emp.id_cliente
												AND fact_vent.id_empresa = cliente_emp.id_empresa
												AND fact_vent.estadoFactura IN (0,2)), 0)
									+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
											WHERE nota_cargo.idCliente = cliente_emp.id_cliente
												AND nota_cargo.id_empresa = cliente_emp.id_empresa
												AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
									- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
											WHERE anticip.idCliente = cliente_emp.id_cliente
												AND anticip.id_empresa = cliente_emp.id_empresa
												AND anticip.estadoAnticipo IN (1,2)
												AND anticip.estatus = 1), 0)
									- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idCliente = cliente_emp.id_cliente
												AND nota_cred.id_empresa = cliente_emp.id_empresa
												AND nota_cred.estadoNotaCredito IN (1,2)), 0)
									+ IFNULL((SELECT
												SUM(IFNULL(ped_vent.subtotal, 0)
													- IFNULL(ped_vent.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
															WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
													+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
															WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
											FROM iv_pedido_venta ped_vent
											WHERE ped_vent.id_cliente = cliente_emp.id_cliente
												AND ped_vent.id_empresa = cliente_emp.id_empresa
												AND ped_vent.estatus_pedido_venta IN (2)
												AND id_empleado_aprobador IS NOT NULL), 0))
			WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
				AND cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
				valTpDato($idCliente, "int"),
				valTpDato($idEmpresa, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarCliente($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarCliente($objResponse);
	$objResponse->alert("Cliente guardado con éxito.");
	
	$objResponse->script("byId('btnCancelarCliente').click();");
	
	$objResponse->loadCommands(listaCliente(
		$frmListaCliente['pageNum'],
		$frmListaCliente['campOrd'],
		$frmListaCliente['tpOrd'],
		$frmListaCliente['valBusq']));
	
	return $objResponse;
}
	
//GUARDA CADA UNO DE LOS DOCUMENTOS GUARDADOS 
function guardarDocumentosRecaudados($datoChex){
	$objResponse = new xajaxResponse();
	
	$valores = "";
	if(isset($datoChex["checkDocuemento"])){
		foreach($datoChex["checkDocuemento"] AS $indice => $valor){
			$valores .= $valor."<br>";
			$sqlChebox = sprintf("INSERT INTO crm_documentos_recaudados (id_perfil_prospecto, id_documento_venta)
			VALUES (%s,%s)",
				$datoChex['hddIdPerfilProspecto'], 
				$valor);
			mysql_query("SET NAMES 'utf8'");
			$queryChebox = mysql_query($sqlChebox);				
			if (!$queryChebox) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1'");
		}
		
		$objResponse->alert("Fuente de Informacion guardada con éxito.");
	}

	$objResponse->assign("tdChebox","innerHTML",$valores);
	
	return $objResponse;
}

function guardarProspecto($frmProspecto, $frmListaCliente) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $arrayValidarCI;
	global $arrayValidarRIF;
	global $arrayValidarNIT;
	global $spanEstado;
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmProspecto['cbx1'];
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmProspecto['txtIdEmpresa'];
	$idProspecto = $frmProspecto['hddIdClienteProspecto'];
	
	switch ($frmProspecto['lstTipoProspecto']) {
		case 1 :
			$lstTipoProspecto = "Natural";
			$arrayValidar = $arrayValidarCI;
			break;
		case 2 :
			$lstTipoProspecto = "Juridico";
			$arrayValidar = $arrayValidarRIF;
			break;
	}
	
	if (!(count($arrayObj1) > 0)) {
		errorGuardarProspecto($objResponse);
		return $objResponse->alert("Debe agregar un modelo de interés");
	}
	
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmProspecto['txtCedulaProspecto'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtCedulaProspecto').className = 'inputErrado'");
			errorGuardarProspecto($objResponse);
			return $objResponse->alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
		}
	}
	
	$txtCiCliente = explode("-",$frmProspecto['txtCedulaProspecto']);
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
	if (!$rs) { errorGuardarProspecto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		errorGuardarCliente($objResponse);
		return $objResponse->alert("Ya existe la ".$spanClienteCxC." ingresada");
	}
	
	$frmProspecto['txtUrbanizacionProspecto'] = str_replace(",", "", $frmProspecto['txtUrbanizacionProspecto']);
	$frmProspecto['txtCalleProspecto'] = str_replace(",", "", $frmProspecto['txtCalleProspecto']);
	$frmProspecto['txtCasaProspecto'] = str_replace(",", "", $frmProspecto['txtCasaProspecto']);
	$frmProspecto['txtMunicipioProspecto'] = str_replace(",", "", $frmProspecto['txtMunicipioProspecto']);
	$frmProspecto['txtCiudadProspecto'] = str_replace(",", "", $frmProspecto['txtCiudadProspecto']);
	$frmProspecto['txtEstadoProspecto'] = str_replace(",", "", $frmProspecto['txtEstadoProspecto']);
	
	$txtDireccion = implode("; ", array(
		$frmProspecto['txtUrbanizacionProspecto'],
		$frmProspecto['txtCalleProspecto'],
		$frmProspecto['txtCasaProspecto'],
		$frmProspecto['txtMunicipioProspecto'],
		$frmProspecto['txtCiudadProspecto'],
		((strlen($frmProspecto['txtEstadoProspecto']) > 0) ? $spanEstado : "")." ".$frmProspecto['txtEstadoProspecto']));
	
	if ($idProspecto > 0) {
		if (!xvalidaAcceso($objResponse,"an_prospecto_list","editar")) { errorGuardarProspecto($objResponse); return $objResponse; }
		
		// EDITA LOS DATOS DEL PROSPECTO
		$updateSQL = sprintf("UPDATE cj_cc_cliente SET
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
			valTpDato($frmProspecto['txtUrbanizacionProspecto'], "text"),
			valTpDato($frmProspecto['txtCalleProspecto'], "text"),
			valTpDato($frmProspecto['txtCasaProspecto'], "text"),
			valTpDato($frmProspecto['txtMunicipioProspecto'], "text"),
			valTpDato($frmProspecto['txtCiudadProspecto'], "text"),
			valTpDato($frmProspecto['txtEstadoProspecto'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmProspecto['txtTelefonoProspecto'], "text"),
			valTpDato($frmProspecto['txtOtroTelefonoProspecto'], "text"),
			valTpDato($frmProspecto['txtEmailProspecto'], "text"),
			valTpDato($frmProspecto['txtUrbanizacionComp'], "text"),
			valTpDato($frmProspecto['txtCalleComp'], "text"),
			valTpDato($frmProspecto['txtCasaComp'], "text"),
			valTpDato($frmProspecto['txtMunicipioComp'], "text"),
			valTpDato($frmProspecto['txtEstadoComp'], "text"),
			valTpDato($frmProspecto['txtTelefonoComp'], "text"),
			valTpDato($frmProspecto['txtOtroTelefonoComp'], "text"),
			valTpDato($frmProspecto['txtEmailComp'], "text"),
			valTpDato("Activo", "text"),
			valTpDato(date("Y-m-d",strtotime($frmProspecto['txtFechaUltAtencion'])), "date"),
			valTpDato(date("Y-m-d",strtotime($frmProspecto['txtFechaUltEntrevista'])), "date"),
			valTpDato(date("Y-m-d",strtotime($frmProspecto['txtFechaProxEntrevista'])), "date"),
			valTpDato($frmProspecto['txtIdEmpleado'], "int"),
			valTpDato($idProspecto, "int")); //este es el valor que tengo que almacenar en el perfil
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			errorGuardarProspecto($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
		
	} else {
		if (!xvalidaAcceso($objResponse,"an_prospecto_list","insertar")) { errorGuardarProspecto($objResponse); return $objResponse; }
		
		// INSERTA LOS DATOS DEL PROSPECTO
		$insertSQL = sprintf("INSERT INTO cj_cc_cliente (tipo, nombre, apellido, lci, ci, urbanizacion, calle, casa, municipio, ciudad, estado, direccion, telf, otrotelf, correo, urbanizacion_comp, calle_comp, casa_comp, municipio_comp, estado_comp, telf_comp, otro_telf_comp, correo_comp, status, fecha_creacion_prospecto, fechaUltimaAtencion, fechaUltimaEntrevista, fechaProximaEntrevista, id_empleado_creador, tipo_cuenta_cliente, fcreacion)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($lstTipoProspecto, "text"),
			valTpDato($frmProspecto['txtNombreProspecto'], "text"),
			valTpDato($frmProspecto['txtApellidoProspecto'], "text"),
			valTpDato($txtLciCliente, "text"),
			valTpDato($txtCiCliente, "text"),
			valTpDato($frmProspecto['txtUrbanizacionProspecto'], "text"),
			valTpDato($frmProspecto['txtCalleProspecto'], "text"),
			valTpDato($frmProspecto['txtCasaProspecto'], "text"),
			valTpDato($frmProspecto['txtMunicipioProspecto'], "text"),
			valTpDato($frmProspecto['txtCiudadProspecto'], "text"),
			valTpDato($frmProspecto['txtEstadoProspecto'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmProspecto['txtTelefonoProspecto'], "text"),
			valTpDato($frmProspecto['txtOtroTelefonoProspecto'], "text"),
			valTpDato($frmProspecto['txtEmailProspecto'], "text"),
			valTpDato($frmProspecto['txtUrbanizacionComp'], "text"),
			valTpDato($frmProspecto['txtCalleComp'], "text"),
			valTpDato($frmProspecto['txtCasaComp'], "text"),
			valTpDato($frmProspecto['txtMunicipioComp'], "text"),
			valTpDato($frmProspecto['txtEstadoComp'], "text"),
			valTpDato($frmProspecto['txtTelefonoComp'], "text"),
			valTpDato($frmProspecto['txtOtroTelefonoComp'], "text"),
			valTpDato($frmProspecto['txtEmailComp'], "text"),
			valTpDato("Activo", "text"),
			valTpDato("NOW()", "campo"),
			valTpDato(date("Y-m-d",strtotime($frmProspecto['txtFechaUltAtencion'])), "date"),
			valTpDato(date("Y-m-d",strtotime($frmProspecto['txtFechaUltEntrevista'])), "date"),
			valTpDato(date("Y-m-d",strtotime($frmProspecto['txtFechaProxEntrevista'])), "date"),
			valTpDato($frmProspecto['txtIdEmpleado'], "int"),
			valTpDato(1, "int"),
			valTpDato("NOW()", "campo")); // 1 = Prospecto, 2 = Cliente
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			errorGuardarProspecto($objResponse);
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
	$queryPerfil = sprintf("SELECT id FROM crm_perfil_prospecto WHERE id = %s;", $idProspecto);
	$rsPerfil = mysql_query($queryPerfil);
	$totalRows = mysql_num_rows($rsPerfil);
	
	if ($totalRows > 0) {
		//EDITA LOS DATOS DEL PERFIL DEL PROSPECTO SI EXISTE
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
			valTpDato($frmProspecto['puesto'], "int"),
			valTpDato($frmProspecto['titulo'], "int"),
			valTpDato($frmProspecto['posibilidad_cierre'], "int"),
			valTpDato($frmProspecto['sector'], "int"),
			valTpDato($frmProspecto['nivel_influencia'], "int"),
			valTpDato($frmProspecto['estatus'], "int"),
			valTpDato($frmProspecto['txtCompania'], "text"),
			valTpDato($frmProspecto['lstEstadoCivil'], "int"), 
			valTpDato($frmProspecto['rdbSexo'], "text"),
			valTpDato(implode("-",array_reverse(explode("-",$frmProspecto['txtFechaNacimiento']))), "date"),
			valTpDato($frmProspecto['lstNivelSocial'], "text"),
			valTpDato($frmProspecto['txtObservacion'], "text"),
			valTpDato($frmProspecto['lstMotivoRechazo'], "int"),
			valTpDato($idProspecto, "int"));
		mysql_query("SET NAME 'utf8'");
		$queryPerfilProspecto = mysql_query($updatePerfilProspecto);
		if (!$queryPerfilProspecto) {
			errorGuardarProspecto($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		//INSERTA LOS DATOS DEL PERFIL DEL PROSPECTO
		$insertPerfilProspecto = sprintf("INSERT INTO crm_perfil_prospecto (id, id_puesto, id_titulo, id_posibilidad_cierre, id_sector, id_nivel_influencia, id_estatus, Compania, id_estado_civil, sexo, fecha_nacimiento, clase_social, observacion, id_motivo_rechazo)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idProspecto, "int"),
			valTpDato($frmProspecto['puesto'], "int"),
			valTpDato($frmProspecto['titulo'], "int"),
			valTpDato($frmProspecto['posibilidad_cierre'], "int"),
			valTpDato($frmProspecto['sector'], "int"),
			valTpDato($frmProspecto['nivel_influencia'], "int"),
			valTpDato($frmProspecto['estatus'], "int"),
			valTpDato($frmProspecto['txtCompania'], "text"),
			valTpDato($frmProspecto['lstEstadoCivil'], "int"), 
			valTpDato($frmProspecto['rdbSexo'], "text"),
			valTpDato(implode("-",array_reverse(explode("-",$frmProspecto['txtFechaNacimiento']))), "date"),
			valTpDato($frmProspecto['lstNivelSocial'], "text"),
			valTpDato($frmProspecto['txtObservacion'], "text"), 
			valTpDato($frmProspecto['lstMotivoRechazo'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertPerfilProspecto);
		if (!$Result1) {
			errorGuardarProspecto($objResponse);
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
	if (!$rsModelo) { errorGuardarProspecto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowModelo = mysql_fetch_assoc($rsModelo)) {
		$existModelo = false;
		if (isset($arrayObj1)) {
			foreach ($arrayObj1 as $indice => $valor) {
				if ($rowModelo['id_prospecto_vehiculo'] == $frmProspecto['hddIdProspectoVehiculo'.$valor]) {
					$existModelo = true;
				}
			}
		}
		
		if ($existModelo == false) {
			$deleteSQL = sprintf("DELETE FROM an_prospecto_vehiculo WHERE id_prospecto_vehiculo = %s",
				valTpDato($rowModelo['id_prospecto_vehiculo'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarProspecto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA LOS MODELOS DE INTERES NUEVOS
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice => $valor) {
			if ($valor != "") {
				if ($frmProspecto['hddIdProspectoVehiculo'.$valor] == "") {
					$insertSQL = sprintf("INSERT INTO an_prospecto_vehiculo (id_cliente, id_unidad_basica, precio_unidad_basica, id_medio, id_nivel_interes, id_plan_pago)
					VALUE (%s, %s, %s, %s, %s, %s);", 
						valTpDato($idProspecto, "int"),
						valTpDato($frmProspecto['hddIdUnidadBasica'.$valor], "int"),
						valTpDato($frmProspecto['hddPrecioUnidadBasica'.$valor], "real_inglesa"),
						valTpDato($frmProspecto['hddIdMedio'.$valor], "int"),
						valTpDato($frmProspecto['hddIdNivelInteres'.$valor], "int"),
						valTpDato($frmProspecto['hddIdPlanPago'.$valor], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarProspecto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
	}
	
	// VERIFICA SI TIENE LA EMPRESA AGREGADA
	$query = sprintf("SELECT * FROM cj_cc_cliente_empresa
	WHERE id_cliente = %s
		AND id_empresa = %s;",
		valTpDato($idProspecto, "int"),
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) { errorGuardarProspecto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	
	if ($totalRows == 0) {
		$insertSQL = sprintf("INSERT INTO cj_cc_cliente_empresa (id_cliente, id_empresa)
		VALUE (%s, %s);",
			valTpDato($idProspecto, "int"),
			valTpDato($idEmpresa, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarProspecto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idClienteEmpresa = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarProspecto($objResponse);
	$objResponse->alert("Prospecto guardado con éxito.");
	
	if ($idPerfilProspecto > 0) {
		$objResponse->alert("Perfil del Prospecto guardado con éxito.");
	}
	
	$objResponse->script("byId('btnCancelarProspecto').click();");
	
	$objResponse->loadCommands(listaCliente(
		$frmListaCliente['pageNum'],
		$frmListaCliente['campOrd'],
		$frmListaCliente['tpOrd'],
		$frmListaCliente['valBusq']));
	
	return $objResponse;
}

function insertarClienteEmpresa($idEmpresa, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCliente['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idEmpresa > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmCliente['hddIdEmpresa'.$valor] == $idEmpresa) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemClienteEmpresa($contFila, "", $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				// DESBLOQUEA LOS BOTONES DEL LISTADO
				for ($cont = 1; $cont <= 20; $cont++) {
					$objResponse->script(sprintf("byId('btnInsertarEmpresa%s').disabled = false;",
						$cont));
				}
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarEmpresa%s').disabled = false;",
			$cont));
	}
	
	return $objResponse;
}

function insertarModelo($frmModelo, $frmProspecto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmProspecto['cbx1'];
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
		vw_pg_empleado.nombre_cargo
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
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleado('".$row['id_empleado']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarEmpresa%s\" onclick=\"validarInsertarEmpresa('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
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
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaModelo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelos.catalogo = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
	
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
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

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
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
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, $spanClienteCxC);
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "apellido", $campOrd, $tpOrd, $valBusq, $maxRows, "Apellido");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Teléfono");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "compania", $campOrd, $tpOrd, $valBusq, $maxRows, "Compañia");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "cantidad_modelos", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant. Modelos");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "paga_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Paga Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= "<td colspan=\"3\"></td>";
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
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_articulo']);
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['apellido'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['telf'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['compania'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad_modelos']."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['paga_impuesto'] == 1) ? "SI" : "NO")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo'])."</td>";
			$htmlTb .= "<td align=\"center\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblProspecto', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['tipo_cuenta_cliente'] == 1) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAprobar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCliente', '%s');\"><img class=\"puntero\" src=\"../img/iconos/accept.png\" title=\"Aprobar Prospecto\"/></a>",
					$contFila,
					$row['id']);
			}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}
	
//MUESTRA LAS ACTIVIDADES ASIGNADAS A ESE CLIENTE	
function muestraActividades($idCliente){
	$objResponse = new xajaxResponse();
	
	$sqlActividadCliente = sprintf("SELECT
		id_actividad_ejecucion,
		crm_actividades_ejecucion.id_actividad,
		nombre_actividad,
		id,
		fecha_asignacion,
		crm_integrantes_equipos.id_empleado,
		CONCAT_WS(' ', nombre_empleado, apellido) AS Asesor, estatus
	FROM crm_actividades_ejecucion
		LEFT JOIN crm_integrantes_equipos ON crm_integrantes_equipos.id_integrante_equipo = crm_actividades_ejecucion.id_integrante_equipo
		LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado
		LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
	WHERE id = %s;",
		valTpDato($idCliente, "int"));
	$queryActividadCliente = mysql_query($sqlActividadCliente);
	if (!$queryActividadCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($queryActividadCliente);
	
	$htmltabA .= "<table width=\"100%\">";
	$htmltabA .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmltabA .= "<td>Actividad</td>";
		$htmltabA .= "<td>Asesor</td>";
	$htmltabA .= "</tr>";
	
	while ($rowsqueryActividadCliente = mysql_fetch_array($queryActividadCliente)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar6";
		$contFila++;
		
		$nombreActividad = $rowsqueryActividadCliente['nombre_actividad'];
		$asesor = $rowsqueryActividadCliente['Asesor'];
		
		$htmltabA .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmltabA .= "<td>".$nombreActividad."</td>";
			$htmltabA .= "<td align='center'>".$asesor."</td>";
		$htmltabA .= "</tr>";
	}
	
	if (!($totalRows > 0)) {
		$htmltabA .= "<tr>";
			$htmltabA .= "<td colspan=\"14\">";
				$htmltabA .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmltabA .= "<tr>";
					$htmltabA .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmltabA .= "<td align=\"center\">No existen actividades para el dia de hoy</td>";
				$htmltabA .= "</tr>";
				$htmltabA .= "</table>";
			$htmltabA .= "</td>";
		$htmltabA .= "</tr>";
	}
	
	$htmltabA .= "</table>"; 
	
	$objResponse->assign("divActividad","innerHTML",$htmltabA);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"actividadSeguimiento");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarModelo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarModelo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCredito");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCivil");
$xajax->register(XAJAX_FUNCTION,"cargarLstEstatus");
$xajax->register(XAJAX_FUNCTION,"cargaLstMedio");
$xajax->register(XAJAX_FUNCTION,"cargaLstMotivoRechazo");
$xajax->register(XAJAX_FUNCTION,"cargarLstNivelInfluencia"); 
$xajax->register(XAJAX_FUNCTION,"cargaLstPlanPago");
$xajax->register(XAJAX_FUNCTION,"cargarLstPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"cargarLstPuesto");
$xajax->register(XAJAX_FUNCTION,"cargarLstSector"); 
$xajax->register(XAJAX_FUNCTION,"cargarLstTitulo");
$xajax->register(XAJAX_FUNCTION,"cargaDocumentosNecesario");
$xajax->register(XAJAX_FUNCTION,"eliminarClienteEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarModelo");
$xajax->register(XAJAX_FUNCTION,"exportarCliente");
$xajax->register(XAJAX_FUNCTION,"formCliente");
$xajax->register(XAJAX_FUNCTION,"formCredito");
$xajax->register(XAJAX_FUNCTION,"formProspecto");
$xajax->register(XAJAX_FUNCTION,"guardarCliente");
$xajax->register(XAJAX_FUNCTION,"guardarDocumentosRecaudados");
$xajax->register(XAJAX_FUNCTION,"guardarProspecto");
$xajax->register(XAJAX_FUNCTION,"insertarClienteEmpresa");
$xajax->register(XAJAX_FUNCTION,"insertarModelo");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaModelo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"muestraActividades");

function insertarItemClienteEmpresa($contFila, $idClienteEmpresa = "", $idEmpresa = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idClienteEmpresa > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryClienteEmpresa = sprintf("SELECT
			cliente_emp.id_cliente_empresa,
			cliente_emp.id_empresa,
			cred.id AS id_credito,
			cred.diascredito,
			cred.fpago,
			cred.limitecredito,
			cred.creditoreservado,
			cred.creditodisponible,
			cred.intereses
		FROM cj_cc_credito cred
			RIGHT JOIN cj_cc_cliente_empresa cliente_emp ON (cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
		WHERE cliente_emp.id_cliente_empresa = %s;",
			valTpDato($idClienteEmpresa, "int"));
		$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
		if (!$rsClienteEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsClienteEmpresa = mysql_num_rows($rsClienteEmpresa);
		$rowClienteEmpresa = mysql_fetch_assoc($rsClienteEmpresa);
	}
	
	$idEmpresa = ($idEmpresa == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_empresa'] : $idEmpresa;
	$idCredito = ($idCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_credito'] : $idCredito;
	$txtDiasCredito = ($txtDiasCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['diascredito'] : $txtDiasCredito;
	$txtFormaPago = ($txtFormaPago == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['fpago'] : $txtFormaPago;
	$txtLimiteCredito = ($txtLimiteCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['limitecredito'] : $txtLimiteCredito;
	$txtCreditoReservado = ($txtCreditoReservado == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditoreservado'] : $txtCreditoReservado;
	$txtCreditoDisponible = ($txtCreditoDisponible == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditodisponible'] : $txtCreditoDisponible;
	$txtIntereses = ($txtIntereses == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['intereses'] : $txtIntereses;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE id_empresa_reg = %s;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDiasCredito%s\" name=\"txtDiasCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtFormaPago%s\" name=\"txtFormaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtLimiteCredito%s\" name=\"txtLimiteCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoReservado%s\" name=\"txtCreditoReservado%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoDisponible%s\" name=\"txtCreditoDisponible%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><a id=\"aEditarCredito%s\" class=\"modalImg\" rel=\"#divFlotante2\"><img class=\"puntero\" src=\"../img/iconos/edit_privilegios.png\" title=\"Editar Crédito\"/></a>".
				"<input type=\"hidden\" id=\"hddIdClienteEmpresa%s\" name=\"hddIdClienteEmpresa%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCredito%s\" name=\"hddIdCredito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarCredito%s').onclick = function() {
			abrirDivFlotante2(this, 'tblCredito', '%s');
		}",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['nombre_empresa']),
			$contFila, $contFila, number_format($txtDiasCredito, 0, ".", ","),
			$contFila, $contFila, $txtFormaPago,
			$contFila, $contFila, number_format($txtLimiteCredito, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoReservado, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoDisponible, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $idClienteEmpresa,
				$contFila, $contFila, $idCredito,
				$contFila, $contFila, $idEmpresa,
			
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
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
		"<tr id=\"trItmModeloInteres:%s\" align=\"left\" class=\"textoGris_11px %s\">".
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

function errorGuardarCliente($objResponse) {
	$objResponse->script("
	byId('btnGuardarCliente').disabled = false;
	byId('btnCancelarCliente').disabled = false;");
}

function errorGuardarProspecto($objResponse) {
	$objResponse->script("
	byId('btnGuardarProspecto').disabled = false;
	byId('btnCancelarProspecto').disabled = false;");
}
?>
