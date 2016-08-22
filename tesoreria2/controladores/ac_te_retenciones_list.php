<?php
function buscarRetenciones($valForm) {
	$objResponse = new xajaxResponse();
	$objResponse->script(sprintf("xajax_listadoRetenciones(0,'','','%s' + '|' + '%s' + '|' + '%s'+ '|' + '%s');",
		$valForm['hddIdEmpresa'],
		$valForm['hddBePro'],
		$valForm['txtFecha'],
		$valForm['NumFactura']
		));
		
		
	//$objResponse ->alert($valForm['selCuenta']);
	
	//$objResponse->script(sprintf("xajax_listadoEstadoCuenta(0,'','');"));
	
	return $objResponse;
}

function listadoRetenciones($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
		if (!xvalidaAcceso($objResponse,"te_retenciones_list")){
		$objResponse->assign("tdListadoRetenciones","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond."vw_te_retencion_cheque.anulado IS NULL";
        
	if ($valCadBusq[0] == ''){
            //$sqlBusq .= " vw_te_retencion_cheque.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
        }else if ($valCadBusq[0] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."vw_te_retencion_cheque.id_empresa = '".$valCadBusq[0]."'";
        }
        
	if ($valCadBusq[1] != 0){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."vw_te_retencion_cheque.id_proveedor = '".$valCadBusq[1]."'";
        }
        
	if ($valCadBusq[2] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%Y/%m') = '".date("Y/m",strtotime('01-'.$valCadBusq[2]))."'";
        }
        
	if ($valCadBusq[3] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."vw_te_retencion_cheque.numero_factura = '".$valCadBusq[3]."'";
        }
		
			

	
	$query = sprintf("SELECT 
		vw_te_retencion_cheque.rif_proveedor,
		vw_te_retencion_cheque.nombre,
		vw_te_retencion_cheque.numero_control_factura,
		vw_te_retencion_cheque.numero_factura,
		vw_te_retencion_cheque.id_factura,
		vw_te_retencion_cheque.codigo,
		vw_te_retencion_cheque.subtotal_factura,
		vw_te_retencion_cheque.monto_retenido,
		vw_te_retencion_cheque.porcentaje_retencion,
		vw_te_retencion_cheque.id_retencion_cheque,
		vw_te_retencion_cheque.base_imponible_retencion,
		vw_te_retencion_cheque.tipo,
		vw_te_retencion_cheque.tipo_documento,                
		DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%s') as fecha_registro_formato
	FROM vw_te_retencion_cheque
	",'%d-%m-%Y').$sqlBusq;  
						
	//$objResponse->alert($query);//alerta
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit);
        if(!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
                if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "5%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "10%", $pageNum, "fecha_registro_formato", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "15%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "RIF Retenido");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "5%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Documento");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "15%", $pageNum, "numero_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "15%", $pageNum, "numero_control_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "10%", $pageNum, "codigo", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo Concepto");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Pago");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "15%", $pageNum, "subtotal_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Operaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "5%", $pageNum, "base_imponible_retencion", $campOrd, $tpOrd, $valBusq, $maxRows, "Base Retenci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "10%", $pageNum, "porcentaje_retencion", $campOrd, $tpOrd, $valBusq, $maxRows, "Porcentaje Retenci&oacute;n");
                $htmlTh .= "<td width=\"5%\"></td>";
	$htmlTh .= "</tr>";
	
	$cont = 0;
	$contb = 1;
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$contb."</td>";
			$htmlTb .= "<td align=\"center\">".$row['fecha_registro_formato']."</td>";
			if ($row['tipo'] == 1) {
                            $queryNota = sprintf("SELECT 
                                                cp_proveedor.lrif,
                                                cp_proveedor.rif
                                                FROM
                                                cp_notadecargo
                                                INNER JOIN cp_proveedor ON (cp_notadecargo.id_proveedor = cp_proveedor.id_proveedor) WHERE cp_notadecargo.id_notacargo = %s", $row['id_factura']);
                            $rsNota = mysql_query($queryNota);
                            if(!$rsNota) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                            $rowNota = mysql_fetch_array($rsNota);

                            $htmlTb .= "<td align=\"center\">" . $rowNota['lrif'] . "-" . $rowNota['rif'] . "</td>";
                        } else {
                            $htmlTb .= "<td align=\"center\">" . $row['rif_proveedor'] . "</td>";
                        }
                        $tipoDocumento = "";
                        if($row["tipo"] == 0){
                            $tipoDocumento = "FA";
                        }elseif($row["tipo"] == 1){
                            $tipoDocumento = "ND";
                        }
                        
                        $tipoPago = "";
                        if($row["tipo_documento"] == 0){
                            $tipoPago = "CH";
                        }elseif($row["tipo_documento"] == 1){
                            $tipoPago = "TR";
                        }
                        
                        $htmlTb .= "<td align=\"center\">".$tipoDocumento."</td>";
                        $htmlTb .= "<td align=\"center\">".$row['numero_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_control_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['codigo']."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoPago."</td>";
			$htmlTb .= "<td align=\"center\">".$row['subtotal_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['base_imponible_retencion']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['porcentaje_retencion']."</td>";
			$htmlTb .= "<td align=\"center\" title=\"Imprimir\"><img class=\"puntero\" src=\"../img/iconos/ico_print.png\" onclick=\"verVentana('reportes/te_imprimir_constancia_retencion_pdf.php?id=".$row['id_retencion_cheque']."&documento=3',700,700);\" ></td>";
			$cont +=  $row['monto_retenido'];
			$contb += 1;
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
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
	$htmlTblFin .= "<br><br>";
	
	$queryTotales = sprintf("SELECT 
						  vw_te_retencion_cheque.rif_proveedor,
						  vw_te_retencion_cheque.nombre,
						  vw_te_retencion_cheque.numero_control_factura,
						  vw_te_retencion_cheque.id_factura,
						  vw_te_retencion_cheque.codigo,
						  vw_te_retencion_cheque.monto_retenido,
						  vw_te_retencion_cheque.porcentaje_retencion,
                                                  vw_te_retencion_cheque.id_retencion_cheque,
                                                  vw_te_retencion_cheque.base_imponible_retencion,
                                                  vw_te_retencion_cheque.tipo,
                                                  vw_te_retencion_cheque.tipo_documento,  
						  DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%s') as fecha_registro_formato
						FROM
						  vw_te_retencion_cheque
						",'%d-%m-%Y').$sqlBusq; 
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	while($rowTotales = mysql_fetch_array($rsTotales)){
	
				$contTotal +=  $rowTotales['monto_retenido'];
	
	}
	
	$htmlx.="<table align=\"center\" class=\"tabla\" border=\"1\" width=\"60%\">";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total por P&aacute;gina:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($cont,'2','.',',')."</td>";
		$htmlx.="</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($contTotal,'2','.',',')."</td>";
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	$objResponse->assign("tdListadoRetenciones","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx);
	
		
	return $objResponse;
}

function listarBeneficiarios1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
        global $spanCI;
        global $spanRIF;
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" WHERE CONCAT(lci_rif,'-',ci_rif_beneficiario) LIKE %s
		OR CONCAT(lci_rif,ci_rif_beneficiario) LIKE %s
		OR nombre_beneficiario LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id_beneficiario AS id,
		CONCAT(lci_rif,'-',ci_rif_beneficiario) as rif_beneficiario,
		nombre_beneficiario
	FROM te_beneficiarios %s", $sqlBusq);

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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios1", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("C&oacute;digo"));
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios1", "20%", $pageNum, "rif_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI."/".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios1", "65%", $pageNum, "nombre_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarBeneficiario1('".$row['id']."');\" title=\"Seleccionar Beneficiario\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_beneficiario']."</td>";
			$htmlTb .= "<td>".  utf8_encode($row['nombre_beneficiario'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
        $objResponse->assign("tdContenido","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	
	$objResponse->script("
		<!--document.getElementById('trBuscarCliente').style.display = '';-->
		
		document.getElementById('tblBeneficiariosProveedores').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Clientes");
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if (document.getElementById('divFlotante1').style.display == 'none') {
			document.getElementById('divFlotante1').style.display = '';
			centrarDiv(document.getElementById('divFlotante1'));
			
			document.forms['frmBuscarCliente'].reset();
			document.getElementById('txtCriterioBusqBeneficiario').focus();
		}
	");
	
	return $objResponse;
}


function listarProveedores1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
        global $spanCI;
        global $spanRIF;
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" WHERE CONCAT(lrif,'-',rif) LIKE %s
		OR CONCAT(lrif,rif) LIKE %s
		OR nombre LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id_proveedor AS id,
		CONCAT(lrif,'-',rif) as rif_proveedor,
		nombre
	FROM cp_proveedor %s", $sqlBusq);

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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarProveedores1", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listarProveedores1", "20%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI."/".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listarProveedores1", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor1('".$row['id']."');\" title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
        $objResponse->assign("tdContenido","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	//$objResponse->assign("tdCabeceraEstado","innerHTML","");
	
	$objResponse->script("
		<!--document.getElementById('trBuscarCliente').style.display = '';-->
		
		document.getElementById('tblBeneficiariosProveedores').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Clientes");
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if (document.getElementById('divFlotante1').style.display == 'none') {
			document.getElementById('divFlotante1').style.display = '';
			centrarDiv(document.getElementById('divFlotante1'));
			
			document.forms['frmBuscarCliente'].reset();
			document.getElementById('txtCriterioBusqProveedor').focus();
		}
	");
	
	return $objResponse;
}


//function buscarCliente1($valform,$pro_bene) {
//	$objResponse = new xajaxResponse();
//	
//	
//	if($pro_bene==1){
//	
//	$valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']);
//	$objResponse->loadCommands(listarProveedores1(0, "", "", $valBusq));
//	}
//	else{
//	$valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']);
//	$objResponse->loadCommands(listarBeneficiarios1(0, "", "", $valBusq));
//	}
//	return $objResponse;
//}

function buscarCliente1($valform,$pro_bene) {
	$objResponse = new xajaxResponse();
		
	if($pro_bene==1){	
            $valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']);
            $objResponse->loadCommands(listarProveedores1(0, "", "", $valBusq));
	}
	elseif($pro_bene=="0"){
            $valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']);
            $objResponse->loadCommands(listarBeneficiarios1(0, "", "", $valBusq));
        }else{//boton buscar pro_bene es null porque no se envia
             if($valform['buscarProv']=="1"){
                 $valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']);
                 $objResponse->loadCommands(listarProveedores1(0, "", "", $valBusq));
             }elseif($valform['buscarProv']=="2"){
                 $valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']);
	$objResponse->loadCommands(listarBeneficiarios1(0, "", "", $valBusq));
             }
        }
	return $objResponse;
}

function asignarProveedor1($id_proveedor){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$id_proveedor."'";
	$rs = mysql_query($query);
        if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddBePro","value",$row['id_proveedor']);
	$objResponse->assign("hddSelBePro","value",'1');
	$objResponse->assign("txtBePro","value",  utf8_encode($row['nombre']));
    $objResponse->script("document.getElementById('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarBeneficiario1($id_beneficiario){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_beneficiarios WHERE id_beneficiario = '".$id_beneficiario."'";
	$rs = mysql_query($query);
        if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddBePro","value",$row['id_beneficiario']);
	$objResponse->assign("hddSelBePro","value",'0');
	$objResponse->assign("txtBePro","value",  utf8_encode($row['nombre_beneficiario']));
    $objResponse->script("document.getElementById('divFlotante1').style.display = 'none';");             
	
	return $objResponse;
}

function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
        if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
        
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa);
        if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa);
        if(!$rsLimitEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa);
                if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsEmpresa);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
            $htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
            $htmlTh .= ordenarCampo("xajax_listEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
            $htmlTh .= ordenarCampo("xajax_listEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");			
	$htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitEmpresa)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$rowBanco['id_empresa_reg']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombre_empresa']." - ".$rowBanco['nombre_empresa_suc'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listEmpresa(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
		$objResponse->assign("tdDescripcionArticulo","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
		$objResponse->script("document.getElementById('divFlotante2').style.display = '';
								  document.getElementById('tblListados2').style.display = '';
								  document.getElementById('tdFlotanteTitulo2').innerHTML = 'Seleccione Empresa';
								  centrarDiv(document.getElementById('divFlotante2'))");	
	return $objResponse;
}	
function asignarEmpresa($idEmpresa){
	$objResponse = new xajaxResponse();
	if ($idEmpresa=='')
	$idEmpresa=$_SESSION['idEmpresaUsuarioSysGts'];
	
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
		$rsEmpresa = mysql_query($queryEmpresa);
                if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
		$nombreSucursal = "";
		
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
		
		$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
		
		$objResponse -> assign("txtNombreEmpresa","value",$empresa);
		$objResponse -> assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
		$objResponse->script("document.getElementById('divFlotante2').style.display = 'none';");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarRetenciones");
$xajax->register(XAJAX_FUNCTION,"listadoRetenciones");
$xajax->register(XAJAX_FUNCTION,"listarBeneficiarios1");
$xajax->register(XAJAX_FUNCTION,"listarProveedores1");
$xajax->register(XAJAX_FUNCTION,"buscarCliente1");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor1");
$xajax->register(XAJAX_FUNCTION,"asignarBeneficiario1");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");



function tipoDocumento($id){
	
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$id);

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	if($row['tipo_documento'] == 'NC'){
		$queryNC = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'", $row['id_documento']);
		$rsNC = mysql_query($queryNC);
		if (!$rsNC) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowNC = mysql_fetch_array($rsNC);
		if($rowNC['tipo_nota_credito'] == '1')
			$respuesta = "NC";
		else if($rowNC['tipo_nota_credito'] == '2')
			$respuesta = "NC/TD";
		else if($rowNC['tipo_nota_credito'] == '3')
			$respuesta = "NC/TC";
	}
	if($row['tipo_documento'] == 'ND')
		$respuesta = "ND";
	if($row['tipo_documento'] == 'TR')
		$respuesta = "TR";
	if($row['tipo_documento'] == 'CH')
		$respuesta = "CH";
	if($row['tipo_documento'] == 'DP')
		$respuesta = "DP";
	
	return $respuesta;
}



?>