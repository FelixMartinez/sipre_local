<?php
function comboActiva($activa){
	$objResponse = new xajaxResponse();
	
	if ($activa == 'SI'){
		$si = "selected='selected'";
		$no = "";
	}
	else{
		$si = "";
		$no = "selected='selected'";
	}
	
	$html = "<select id=\"selChequeraActiva\" name=\"selChequeraActiva\">";
		$html .= "<option value=\"SI\" ".$si.">SI</option>";
		$html .= "<option value=\"NO\" ".$no.">NO</option>";
	$html .= "</select>";
	
	$objResponse->assign("tdSelChequeraActiva","innerHTML",$html);
	
	return $objResponse;
}

function comboBancos($idBanco,$idTd,$idSel,$onchange){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE nombreBanco <> '-'");
	$rs = mysql_query($query) or die(mysql_error());
		
	$html = "<select id=\"".$idSel."\" name=\"".$idSel."\" onchange=\"".$onchange."\">";
	$html .= "<option value=\"0\">Todos</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		if ($row['idBanco'] == $idBanco){
			$selected = "selected=\"selected\"";
		}
		else{
			$selected = "";
		}
			$html .= "<option value=\"".$row['idBanco']."\" ".$selected.">".utf8_encode($row['nombreBanco'])."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function comboCuentas($idBanco,$idCuentas){
	$objResponse = new xajaxResponse();
	
	if ($idBanco == 0){
		$disabled = "disabled=\"disabled\"";
	}
	else{
		$condicion = "WHERE idBanco = '".$idBanco."'";
		$disabled = "";
	}
	
	$queryCuentas = "SELECT * FROM cuentas ".$condicion."";
	$rsCuentas = mysql_query($queryCuentas) or die(mysql_error());
	
	$html = "<select id=\"selCuentaNuevaChequera\" name=\"selCuentaNuevaChequera\" ".$disabled.">";
	$html .= "<option value=\"0\">Todos</option>";
	while ($rowCuentas = mysql_fetch_assoc($rsCuentas)) {
		if ($idCuentas == $rowCuentas['idCuentas']){
			$selected = "selected=\"selected\"";
		}
		else{
			$selected = "";
		}
			$html .= "<option value=\"".$rowCuentas['idCuentas']."\" ".$selected.">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuentas","innerHTML",$html);
	
	return $objResponse;
}

function editarChequera($idChequera){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_chequeras","editar")){
		$objResponse->script("$('btnBuscar').click();");
		return $objResponse;
	}
	
        mysql_query("START TRANSACTION");
        
	$queryConsultar = "SELECT activa FROM te_chequeras WHERE id_chq = '".$idChequera."'";
	$rsConsultar = mysql_query($queryConsultar);
        if(!$rsConsultar) { return  $objResponse->alert(mysql_error()."\n LINEA: ".__LINE__." \n query: ".$queryConsultar); }
	$rowConsultar = mysql_fetch_array($rsConsultar);
	
	if ($rowConsultar['activa'] == "SI"){
		$estatus = "NO";
	}
	else{
		$estatus = "SI";
	}
	
	$queryEditar = "UPDATE te_chequeras SET activa = '".$estatus."', disponibles = 0 WHERE id_chq = '".$idChequera."'";
	$rsEditar = mysql_query($queryEditar);
        if(!$rsEditar) { return $objResponse->alert(mysql_error()."\n LINEA: ".__LINE__."\n query: ".$queryEditar); }
	
        mysql_query("COMMIT");
        
	$objResponse->script("$('btnBuscar').click();");
	
	$objResponse->alert("Chequera Editada Exitosamente");
	
	return $objResponse;
}

function eliminarChequera($idChequera){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_chequeras","eliminar")){
		return $objResponse;
	}
	
	$queryEliminar = "DELETE FROM te_chequeras WHERE id_chq = '".$idChequera."'";
	
	if ($rs = mysql_query($queryEliminar) == true){
		$objResponse->script("xajax_listarChequeras(0,'','','')");
		$objResponse->alert("Chequera eliminada exitosamente.");
	}
	
		
	return $objResponse;
}

function guardarChequera($formChequera){
	$objResponse = new xajaxResponse();
	

		  $cantidad =  $formChequera['txtNumeroFinal'] - $formChequera['txtNumeroInicial'] + 1; 
		  $ultimoNum= $formChequera['txtNumeroInicial'] - 1;
		   
	$queryInsert = "INSERT INTO te_chequeras 
                        (id_chq, id_cuenta, nro_inicial, nro_final, cantidad, ultimo_nro_chq, anulados, impresos, disponibles, estatus, activa) 
                        
                        VALUES ('', '".$formChequera['selCuentaNuevaChequera']."', '".$formChequera['txtNumeroInicial']."', '".$formChequera['txtNumeroFinal']."', '".$cantidad."', '".$ultimoNum."', 0, 0, '".$cantidad."', 0, '".$formChequera['selChequeraActiva']."');";
	
	mysql_query($queryInsert) or die(mysql_error());

	$objResponse->script("$('btnBuscar').click();
						  $('divFlotante').style.display = 'none';");
						  
	$objResponse->script("xajax_listarChequeras(0,'','','0');");
	
	$objResponse->alert("Chequera insertada exitosamente");
	
	return $objResponse;
}

function levantarDivFlotante(){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_chequeras","insertar")){
		return $objResponse;
	}
	$objResponse->script("document.forms['frmChequera'].reset();
						$('divFlotante').style.display = '';
						$('divFlotanteTitulo').innerHTML = 'Nueva Chequera';
						centrarDiv($('divFlotante'));
						$('bttGuardar').style.display = '';
						xajax_comboBancos(0,'tdSelBancoNuevaChequera','selBancoNuevaChequera','xajax_comboCuentas((this.value),0)');
						xajax_comboCuentas(0,0);
						$('hddIdChequera').value = 0;
						$('td1').style.display = 'none';
						$('td2').style.display = 'none';
						$('tr4').style.display = 'none';
						$('tr5').style.display = 'none';");
	
	return $objResponse;
}

function listarChequeras($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_chequeras")){
		$objResponse->assign("tdListaChequeras","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != 0){
		$condicion = "WHERE idBanco = '".$valCadBusq[0]."' AND ";
	}
	else{
		$condicion = "WHERE";
	}
	
	$queryChequeras = "SELECT * FROM vw_te_chequeras ".$condicion." (numeroCuentaCompania LIKE '%".$valCadBusq[1]."%')";
	$rsChequeras = mysql_query($queryChequeras) or die(mysql_error());
	
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitChequera = sprintf("%s %s LIMIT %d OFFSET %d", $queryChequeras, $sqlOrd, $maxRows, $startRow);        
	
	$rsLimitChequera = mysql_query($queryLimitChequera) or die(mysql_error());
			
	if ($totalRows == NULL) {
		$rsChequera = mysql_query($queryChequeras) or die(mysql_error());
		$totalRows = mysql_num_rows($rsChequeras);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
        
        $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarChequeras", "", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listarChequeras", "", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Numero Cuenta");
		$htmlTh .= ordenarCampo("xajax_listarChequeras", "", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Cantidad");
		$htmlTh .= ordenarCampo("xajax_listarChequeras", "", $pageNum, "impresos", $campOrd, $tpOrd, $valBusq, $maxRows, "Impresos");
		$htmlTh .= ordenarCampo("xajax_listarChequeras", "", $pageNum, "anulados", $campOrd, $tpOrd, $valBusq, $maxRows, "Anulados");
		$htmlTh .= ordenarCampo("xajax_listarChequeras", "", $pageNum, "disponibles", $campOrd, $tpOrd, $valBusq, $maxRows, "Disponibles");
		$htmlTh .= ordenarCampo("xajax_listarChequeras", "", $pageNum, "id_chq", $campOrd, $tpOrd, $valBusq, $maxRows, "Activa");
		$htmlTh .= '<td></td>';
	$htmlTh .= "</tr>";
	
	while ($rowChequera = mysql_fetch_assoc($rsLimitChequera)) {
		$clase = ($clase == "trResaltarTesoreria1") ? $clase = "trResaltarTesoreria2" : $clase = "trResaltarTesoreria1";
		
		$queryBanco = "SELECT nombreBanco FROM bancos WHERE idBanco = '".$rowChequera['idBanco']."'";
		$rsBanco = mysql_query($queryBanco) or die(mysql_error());
		$rowBanco = mysql_fetch_array($rsBanco);
		
		if ($rowChequera['activa'] == "SI"){
			$checked = "checked='checked'";
		}
		else{
			$checked = "";
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>".  utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td>".$rowChequera['numeroCuentaCompania']."</td>";
			$htmlTb .= "<td>".$rowChequera['cantidad']."</td>";
			$htmlTb .= "<td>".$rowChequera['impresos']."</td>";
			$htmlTb .= "<td>".$rowChequera['anulados']."</td>";
			$htmlTb .= "<td>".$rowChequera['disponibles']."</td>";
			$htmlTb .= "<td><input type='checkbox' ".$checked." onClick=\"xajax_editarChequera(".$rowChequera['id_chq'].")\"></td>";
			$htmlTb .= "<td><img src='../img/iconos/ico_view.png' onclick='xajax_verChequera(".$rowChequera['id_chq'].")' /></td>";
			/*$htmlTb .= "<td><img src='../img/iconos/ico_quitar.gif' onclick=\"if (confirm('Desea eliminar la chequera?') == true) {
			xajax_eliminarChequera(".$rowChequera['id_chq'].");
		}\"/></td>";*/
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarChequeras(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarChequeras(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarChequeras(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarChequeras(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarChequeras(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
        $objResponse->assign("tdListaChequeras","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function verChequera($idChequera){
	$objResponse = new xajaxResponse();
	
	$queryChequera = "SELECT * FROM vw_te_chequeras WHERE id_chq = '".$idChequera."'";
	$rsChequera = mysql_query($queryChequera) or die(mysql_error());
	$rowChequera = mysql_fetch_array($rsChequera);

	$objResponse->script("xajax_comboBancos(".$rowChequera['idBanco'].",'tdSelBancoNuevaChequera','selBancoCuentaNueva','xajax_comboCuentas(this.value)');");
	$objResponse->script("xajax_comboCuentas(".$rowChequera['idBanco'].",".$rowChequera['id_cuenta'].");");
	$objResponse->script("xajax_comboActiva('".$rowChequera['activa']."');");
	$objResponse->assign("hddIdChequera","value",$rowChequera['id_chq']);
	$objResponse->assign("txtUltimoNumeroCheque","value",$rowChequera['ultimo_nro_chq']);	
	$objResponse->assign("txtDisponibles","value",$rowChequera['disponibles']);
	$objResponse->assign("txtNumeroInicial","value",$rowChequera['nro_inicial']);
	$objResponse->assign("txtNumeroFinal","value",$rowChequera['nro_final']);
	$objResponse->assign("txtImpresos","value",$rowChequera['impresos']);
	$objResponse->assign("txtAnulados","value",$rowChequera['anulados']);
	
	$objResponse->assign("txtCantidadCheque","value",$rowChequera['cantidad']);
	
	$objResponse->script("$('divFlotante').style.display = '';
						  centrarDiv($('divFlotante'));
                                                  $('divFlotanteTitulo').innerHTML = 'Visualizar Chequera';
						  $('td1').style.display = '';
						  $('td2').style.display = '';
						  $('tr4').style.display = '';
						  $('tr5').style.display = '';");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"comboActiva");
$xajax->register(XAJAX_FUNCTION,"comboBancos");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"editarChequera");
$xajax->register(XAJAX_FUNCTION,"eliminarChequera");
$xajax->register(XAJAX_FUNCTION,"guardarChequera");
$xajax->register(XAJAX_FUNCTION,"levantarDivFlotante");
$xajax->register(XAJAX_FUNCTION,"listarChequeras");
$xajax->register(XAJAX_FUNCTION,"verChequera");
?>