<?php
function cargarRetencion($idRetencion){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_retenciones","editar")){
		return $objResponse;
	}
	
	$queryRetencion = "SELECT * FROM te_retenciones WHERE id = '".$idRetencion."'";
	$rsRetencion = mysql_query($queryRetencion) or die(mysql_error());
	$rowRetencion = mysql_fetch_array($rsRetencion);
	
	$objResponse->assign("txtDescripcion","value",$rowRetencion['descripcion']);
	$objResponse->assign("hddIdRetencion","value",$rowRetencion['id']);
	$objResponse->assign("txtImporte","value",$rowRetencion['importe']);
	$objResponse->assign("txtRetencion","value",$rowRetencion['porcentaje']);
	$objResponse->assign("txtUnidadTributaria","value",$rowRetencion['unidadtributaria']);
	$objResponse->assign("txtSustraendo","value",$rowRetencion['sustraendo']);
	$objResponse->assign("txtCodigo","value",$rowRetencion['codigo']);
	$objResponse->assign("selActivo","value",$rowRetencion['activo']);
	
	$objResponse->script("$('divFlotante').style.display = '';
						  $('divFlotanteTitulo').innerHTML = 'Editar Retencion';
						  centrarDiv($('divFlotante'));");
	
	return $objResponse;
}


function guardarRetencion($formRetencion){
	$objResponse = new xajaxResponse();
	
	if ($formRetencion['hddIdRetencion'] == 0){
            $cadena = "insertada";

            $queryRetencion = "INSERT INTO te_retenciones(descripcion, importe, porcentaje, unidadtributaria, sustraendo, codigo, activo)
                        VALUES ('".$formRetencion['txtDescripcion']."',
                        '".$formRetencion['txtImporte']."',
                        '".$formRetencion['txtRetencion']."', 
                        '".$formRetencion['txtUnidadTributaria']."', 
                        '".$formRetencion['txtSustraendo']."',
                        '".$formRetencion['txtCodigo']."',
                        '".$formRetencion['selActivo']."');";
	}else{
            $cadena = "modificada";

            $queryRetencion = "UPDATE te_retenciones SET 
                    descripcion = '".$formRetencion['txtDescripcion']."',
                    importe = '".$formRetencion['txtImporte']."',
                    porcentaje = '".$formRetencion['txtRetencion']."',
                    unidadtributaria = '".$formRetencion['txtUnidadTributaria']."',
                    sustraendo = '".$formRetencion['txtSustraendo']."',
                    codigo = '".$formRetencion['txtCodigo']."',
                    activo = '".$formRetencion['selActivo']."'
                    WHERE id = '".$formRetencion['hddIdRetencion']."';";
	}
	
	$rsRetencion = mysql_query($queryRetencion);
        if(!$rsRetencion){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__."\n\nQuery: ".$queryRetencion); }
	
	$objResponse->script("xajax_listarRetenciones(0,'','','');
                            $('divFlotante').style.display = 'none';");
	
	$objResponse->alert("Retencion ".$cadena." exitosamente");
	
	return $objResponse;
}

function levantarDivFlotante(){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_retenciones","insertar")){
		return $objResponse;
	}
	$objResponse->script("document.forms['frmCuenta'].reset();
						$('divFlotante').style.display = '';
						$('divFlotanteTitulo').innerHTML = 'Nuevo';
						centrarDiv($('divFlotante'));
						$('hddIdRetencion').value = '0';");
	
	return $objResponse;
}

function listarRetenciones($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_retenciones")){
		$objResponse->assign("tdListaRetenciones","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$startRow = $pageNum * $maxRows;
		
	$queryRetenciones = "SELECT * FROM te_retenciones";
	$rsRetenciones = mysql_query($queryRetenciones) or die(mysql_error());
	
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitRetenciones = sprintf("%s %s LIMIT %d OFFSET %d", $queryRetenciones, $sqlOrd, $maxRows, $startRow);        
	//$queryLimitRetenciones = $queryRetenciones." LIMIT ".$maxRows." OFFSET ".$startRow.";";
        
	$rsLimitRetenciones = mysql_query($queryLimitRetenciones) or die(mysql_error());
			
	if ($totalRows == NULL) {
		$rsRetenciones = mysql_query($queryRetenciones) or die(mysql_error());
		$totalRows = mysql_num_rows($rsRetenciones);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
        
         $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
                $htmlTh .= '<td></td>';
		$htmlTh .= ordenarCampo("xajax_listarRetenciones", "", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listarRetenciones", "", $pageNum, "importe", $campOrd, $tpOrd, $valBusq, $maxRows, "Importe >= Para Aplicar");
		$htmlTh .= ordenarCampo("xajax_listarRetenciones", "", $pageNum, "unidadtributaria", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad Tributaria");
		$htmlTh .= ordenarCampo("xajax_listarRetenciones", "", $pageNum, "porcentaje", $campOrd, $tpOrd, $valBusq, $maxRows, "% Retencion");
		$htmlTh .= ordenarCampo("xajax_listarRetenciones", "", $pageNum, "sustraendo", $campOrd, $tpOrd, $valBusq, $maxRows, "Sustraendo");
		$htmlTh .= '<td></td>';
	$htmlTh .= "</tr>";
	
	while ($rowRetencion = mysql_fetch_assoc($rsLimitRetenciones)) {
            $contFila++;
            $clase = ($clase == "trResaltarTesoreria1") ? $clase = "trResaltarTesoreria2" : $clase = "trResaltarTesoreria1";

            if($rowRetencion["activo"] == "1"){
                $imgActivo = "<img src=\"../img/iconos/ico_verde.gif\">";
            }else{
                $imgActivo = "<img src=\"../img/iconos/ico_rojo.gif\">";
            }
            
            $htmlTb .= "<tr class=\"".$clase."\">";            
                    $htmlTb .= "<td align='center'>".$imgActivo."</td>";
                    $htmlTb .= "<td align='center'>".htmlentities($rowRetencion['descripcion'])."</td>";
                    $htmlTb .= "<td align='right'>".$rowRetencion['importe']."</td>";
                    $htmlTb .= "<td align='right'>".$rowRetencion['unidadtributaria']."</td>";
                    $htmlTb .= "<td align='right'>".$rowRetencion['porcentaje']."</td>";
                    $htmlTb .= "<td align='right'>".$rowRetencion['sustraendo']."</td>";
                    $htmlTb .= "<td><img src='../img/iconos/ico_edit.png' class='puntero' onclick='xajax_cargarRetencion(".$rowRetencion['id'].")' /></td>";			
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarRetenciones(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
        
	$objResponse->assign("tdListaRetenciones","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargarRetencion");
$xajax->register(XAJAX_FUNCTION,"guardarRetencion");
$xajax->register(XAJAX_FUNCTION,"levantarDivFlotante");
$xajax->register(XAJAX_FUNCTION,"listarRetenciones");
?>