<?php
function formClaveUsuario() {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_clave_especial_list","insertar")){
		return $objResponse;
	}

	
	$objResponse->script("
		xajax_cargaLstUsuario();
		xajax_cargaLstModuloClave();
	
		document.forms['frmClaveEspecial'].reset();
		$('hddIdClaveUsuario').value = '';
		
		$('lstUsuario').className = 'inputInicial';
		$('lstModuloClave').className = 'inputInicial';
		$('txtContrasena').className = 'inputInicial';
	");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Clave Especial");
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
	");
	
	return $objResponse;
}

function cargarClaveUsuario($idClaveUsuario) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_clave_especial_list","editar")){
		return $objResponse;
	}
	
	$query = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_clave_usuario = %s",
		valTpDato($idClaveUsuario, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdClaveUsuario","value",$idClaveUsuario);
	$objResponse->script(sprintf("xajax_cargaLstUsuario('SI','%s');",$row['id_usuario']));
	$objResponse->script(sprintf("xajax_cargaLstModuloClave('%s');",$row['id_clave_modulo']));
	$objResponse->assign("txtContrasena","value",$row['contrasena']);
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Clave Especial");
	$objResponse->script("
		$('lstUsuario').disabled = true;
	
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
	");
	
	return $objResponse;
}

function guardarClaveUsuario($valForm, $valFormListadoClavesEspeciales) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdClaveUsuario'] > 0) {
		$updateSQL = sprintf("UPDATE pg_claves_usuarios SET
			id_clave_modulo = %s,
			contrasena = %s
		WHERE id_clave_usuario = %s",
			valTpDato($valForm['lstModuloClave'], "int"),
			valTpDato($valForm['txtContrasena'], "text"),
			valTpDato($valForm['hddIdClaveUsuario'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		$insertSQL = sprintf("INSERT INTO pg_claves_usuarios (id_clave_modulo, id_usuario, contrasena) VALUE (%s, %s, %s)",
			valTpDato($valForm['lstModuloClave'], "int"),
			valTpDato($valForm['lstUsuario'], "int"),
			valTpDato($valForm['txtContrasena'], "text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Clave Especial Guardada con Exito");
	
	$objResponse->script("$('divFlotante').style.display = 'none';");
	
	$objResponse->script(sprintf("xajax_listadoClavesUsuarios(%s,'%s','%s','%s')",
		$valFormListadoClavesEspeciales['pageNum'],
		$valFormListadoClavesEspeciales['campOrd'],
		$valFormListadoClavesEspeciales['tpOrd'],
		$valFormListadoClavesEspeciales['valBusq']));
	
	return $objResponse;
}

function eliminarClaveUsuario($idClaveUsuario, $valFormListadoClavesEspeciales) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_clave_especial_list","eliminar")){
		return $objResponse;
	}
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM pg_claves_usuarios WHERE id_clave_usuario = %s",
		valTpDato($idClaveUsuario, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script(sprintf("xajax_listadoClavesUsuarios(%s,'%s','%s','%s')",
		$valFormListadoClavesEspeciales['pageNum'],
		$valFormListadoClavesEspeciales['campOrd'],
		$valFormListadoClavesEspeciales['tpOrd'],
		$valFormListadoClavesEspeciales['valBusq']));
	
	return $objResponse;
}

function buscarClaveUsuario($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$valForm['txtUsuarioBuscar'],
		$valForm['lstModuloClaveBuscar']);

	$objResponse->script("xajax_listadoClavesUsuarios(0,'','','".$valBusq."');");
	
	return $objResponse;

}

function listadoClavesUsuarios($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_clave_especial_list")){
		$objResponse->assign("tdListadoClavesEspeciales","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo = %s",
		valTpDato("3", "int"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre_usuario LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_clave_modulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$query = sprintf("SELECT * FROM vw_pg_claves_modulos %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "20%", $pageNum, "nombre_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, "Usuario");
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "80%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "M&oacute;dulo");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltarTesoreria1") ? $clase = "trResaltarTesoreria2" : $clase = "trResaltarTesoreria1";
		
		$contFila ++;
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['nombre_usuario'])."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['descripcion'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_cargarClaveUsuario('%s');\" src=\"../img/iconos/ico_edit.png\"/></td>",
				$row['id_clave_usuario']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_clave_usuario']);
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClavesUsuarios(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClavesUsuarios(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoClavesUsuarios(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClavesUsuarios(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClavesUsuarios(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
        $objResponse->assign("tdListadoClavesEspeciales","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
        
	return $objResponse;
}

function cargaLstUsuario($bloquea = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($bloquea != "") {
		$disabled = "disabled=\"disabled\"";
	}
	
	$query = sprintf("SELECT * FROM pg_usuario ORDER BY nombre_usuario");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstUsuario\" name=\"lstUsuario\" ".$disabled.">";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_usuario'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_usuario']."\" ".$seleccion.">".htmlentities($row['nombre_usuario'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUsuario","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModuloClave($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_claves_modulos
	WHERE id_modulo = 3
	ORDER BY descripcion");
        
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstModuloClave\" name=\"lstModuloClave\">";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_clave_modulo'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_clave_modulo']."\" ".$seleccion.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModuloClave","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModuloClaveBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_claves_modulos
	WHERE id_modulo = 3
	ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstModuloClaveBuscar\" name=\"lstModuloClaveBuscar\">";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_clave_modulo'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_clave_modulo']."\" ".$seleccion.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModuloClaveBuscar","innerHTML",$html);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"formClaveUsuario");
$xajax->register(XAJAX_FUNCTION,"cargarClaveUsuario");
$xajax->register(XAJAX_FUNCTION,"guardarClaveUsuario");
$xajax->register(XAJAX_FUNCTION,"eliminarClaveUsuario");
$xajax->register(XAJAX_FUNCTION,"buscarClaveUsuario");
$xajax->register(XAJAX_FUNCTION,"listadoClavesUsuarios");
$xajax->register(XAJAX_FUNCTION,"cargaLstUsuario");
$xajax->register(XAJAX_FUNCTION,"cargaLstModuloClave");
$xajax->register(XAJAX_FUNCTION,"cargaLstModuloClaveBuscar");
?>