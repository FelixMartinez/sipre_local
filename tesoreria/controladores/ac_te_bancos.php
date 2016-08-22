<?php
function buscarBanco($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listarBancos(0, "nombreBanco", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarBanco($id_banco){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_bancos","eliminar")){
		return $objResponse;
	}
	
	$queryEliminar = "DELETE FROM bancos WHERE idBanco = '".$id_banco."'";
        $rs = mysql_query($queryEliminar);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
        
        if(mysql_affected_rows()){
            $objResponse->script("xajax_listarBancos(0,'nombreBanco','ASC','')");
            $objResponse->alert("Banco eliminado exitosamente.");
        }else{
            $objResponse->alert("Error no se pudo eliminar");
        }
	
		
	return $objResponse;
}

function guardarBanco($formBanco){
	$objResponse = new xajaxResponse();
		
	if ($formBanco['hddIdBanco'] == 0){
		$cadena = "insertado";
		
		$queryBanco = sprintf("INSERT INTO bancos (nombreBanco, sucursal, direccion, telf, fax, email, porcentaje_flat, diasSalvoBuenCobroLocales, diasSalvoBuenCobroForaneos, rif, codigo1, codigo2)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($formBanco['txtNombreBanco'],"text"),
			valTpDato($formBanco['txtSucursalBanco'],"text"),
			valTpDato($formBanco['txtDireccionBanco'],"text"),
			valTpDato($formBanco['txtTelefonoBanco'],"text"),
			valTpDato($formBanco['txtFaxBanco'],"text"),
			valTpDato($formBanco['txtEmailBanco'],"text"),
			valTpDato($formBanco['txtPorcentajeFlatBanco'],"double"),
			valTpDato($formBanco['txtDSBCLocalesBanco'],"int"),
			valTpDato($formBanco['txtDSBCForaneosBanco'],"int"),
			valTpDato($formBanco['txtRIF'],"text"),                        
			valTpDato($formBanco['txtCodigo1'],"text"),
			valTpDato($formBanco['txtCodigo2'],"text"));
	
	}else{
		$cadena = "modificado";
	
		$queryBanco = sprintf("UPDATE bancos SET 
			nombreBanco = %s,
			sucursal = %s,
			direccion = %s,
			telf = %s,
			fax = %s,
			email = %s,
			porcentaje_flat = %s,
			diasSalvoBuenCobroLocales = %s,
			diasSalvoBuenCobroForaneos = %s,
			rif = %s,                        
			codigo1 = %s,
			codigo2 = %s
		WHERE idBanco = %s;",
			valTpDato($formBanco['txtNombreBanco'],"text"),
			valTpDato($formBanco['txtSucursalBanco'],"text"),
			valTpDato($formBanco['txtDireccionBanco'],"text"),
			valTpDato($formBanco['txtTelefonoBanco'],"text"),
			valTpDato($formBanco['txtFaxBanco'],"text"),
			valTpDato($formBanco['txtEmailBanco'],"text"),
			valTpDato($formBanco['txtPorcentajeFlatBanco'],"double"),
			valTpDato($formBanco['txtDSBCLocalesBanco'],"int"),
			valTpDato($formBanco['txtDSBCForaneosBanco'],"int"),
			valTpDato($formBanco['txtRIF'],"text"),
                        valTpDato($formBanco['txtCodigo1'],"text"),
			valTpDato($formBanco['txtCodigo2'],"text"),
			valTpDato($formBanco['hddIdBanco'],"int")                        
			);
	}
	
	$rsBanco = mysql_query($queryBanco);	
        if (!$rsBanco) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$objResponse->script("xajax_listarBancos(0,'nombreBanco','ASC','');
		$('divFlotante').style.display = 'none';");
	
	$objResponse->alert("Banco ".$cadena." exitosamente");
	
	return $objResponse;
}

function levantarDivFlotante(){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_bancos","insertar")){
		return $objResponse;
	}
	$objResponse->script("document.forms['frmBanco'].reset();
						$('divFlotante').style.display = '';
						centrarDiv($('divFlotante'));
						$('btnGuardar').style.display = '';
						$('tdFlotanteTitulo').innerHTML = 'Nuevo Banco';
						$('hddIdBanco').value = 0;");
	
	return $objResponse;
}

function listarBancos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = " WHERE (nombreBanco <> '-') ";
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
	$sqlBusq .= $cond.sprintf("nombreBanco LIKE %s)",
		valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT *
	FROM bancos
		%s", $sqlBusq);
					 
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarBancos", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listarBancos", "40%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");
		$htmlTh .= ordenarCampo("xajax_listarBancos", "20%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Telefono");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria2" : "trResaltarTesoreria1";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['sucursal'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['telf'])."</td>";
			$htmlTb .= "<td><img src='../img/iconos/ico_view.png' class=\"puntero\" title=\"Ver\" onclick='xajax_verBanco(".$row['idBanco'].",1)' /></td>";
			$htmlTb .= "<td><img src='../img/iconos/pencil.png' class=\"puntero\" title=\"Editar\" onclick='xajax_verBanco(".$row['idBanco'].",2)' /></td>";
			$htmlTb .= "<td><img src='../img/iconos/cross.png' class=\"puntero\" title=\"Eliminar\" onclick=\"if (confirm('Desea eliminar ".utf8_encode($row['nombreBanco'])."?') == true) { xajax_eliminarBanco(".$row['idBanco'].");}\"/></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBancos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBancos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarBancos(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBancos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBancos(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("tdListaBancos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function verBanco($id_banco,$accion){
	$objResponse = new xajaxResponse();
	
	if ($accion == 2){
		if (!xvalidaAcceso($objResponse,"te_bancos","editar")){
			return $objResponse;
		}
	}
	
	$queryBanco = "SELECT * FROM bancos WHERE idBanco = '".$id_banco."'";
	$rsBanco = mysql_query($queryBanco) or die(mysql_error());
	$rowBanco = mysql_fetch_array($rsBanco);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($rowBanco['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$rowBanco['idBanco']);
	$objResponse->assign("txtSucursalBanco","value",utf8_encode($rowBanco['sucursal']));
	$objResponse->assign("txtDireccionBanco","value",utf8_encode($rowBanco['direccion']));
	$objResponse->assign("txtCodigo1","value",$rowBanco['codigo1']);
	$objResponse->assign("txtCodigo2","value",$rowBanco['codigo2']);
	$objResponse->assign("txtRIF","value",$rowBanco['rif']);
	$objResponse->assign("txtTelefonoBanco","value",$rowBanco['telf']);
	$objResponse->assign("txtFaxBanco","value",$rowBanco['fax']);
	$objResponse->assign("txtEmailBanco","value",$rowBanco['email']);
	$objResponse->assign("txtPorcentajeFlatBanco","value",$rowBanco['porcentaje_flat']);
	$objResponse->assign("txtDSBCLocalesBanco","value",$rowBanco['diasSalvoBuenCobroLocales']);
	$objResponse->assign("txtDSBCForaneosBanco","value",$rowBanco['diasSalvoBuenCobroForaneos']);
	
	$objResponse->script("$('divFlotante').style.display = '';
		centrarDiv($('divFlotante'));");
	
	if ($accion == 1){
		$objResponse->script("$('tdFlotanteTitulo').innerHTML = 'Ver Banco';
			$('btnGuardar').style.display = 'none'");
			
		$objResponse->script("$('txtNombreBanco').readOnly = true;
			$('txtSucursalBanco').readOnly = true;
			$('txtDireccionBanco').readOnly = true;
			$('txtRIF').readOnly = true;
			$('txtTelefonoBanco').readOnly = true;
			$('txtFaxBanco').readOnly = true;
			$('txtEmailBanco').readOnly = true;
			$('txtPorcentajeFlatBanco').readOnly = true;
			$('txtDSBCLocalesBanco').readOnly = true;
			$('txtDSBCForaneosBanco').readOnly = true;");
	} else {
		$objResponse->script("$('tdFlotanteTitulo').innerHTML = 'Editar Banco';
			$('btnGuardar').style.display = ''");
			
		$objResponse->script("$('txtNombreBanco').readOnly = false;
			$('txtSucursalBanco').readOnly = false;
			$('txtDireccionBanco').readOnly = false;
			$('txtRIF').readOnly = false;
			$('txtTelefonoBanco').readOnly = false;
			$('txtFaxBanco').readOnly = false;
			$('txtEmailBanco').readOnly = false;
			$('txtPorcentajeFlatBanco').readOnly = false;
			$('txtDSBCLocalesBanco').readOnly = false;
			$('txtDSBCForaneosBanco').readOnly = false;");
	}
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarBanco");
$xajax->register(XAJAX_FUNCTION,"eliminarBanco");
$xajax->register(XAJAX_FUNCTION,"guardarBanco");
$xajax->register(XAJAX_FUNCTION,"levantarDivFlotante");
$xajax->register(XAJAX_FUNCTION,"listarBancos");
$xajax->register(XAJAX_FUNCTION,"verBanco");

?>