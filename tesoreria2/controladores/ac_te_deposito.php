<?php
function buscarDeposito($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoDeposito(0,'fecha_registro','DESC','%s' + '|' + '%s' + '|' + '%s' + '|' + '%s' + '|' + '%s');",
		$valForm['txtBusq'],
		$valForm['selEmpresa'],
		$valForm['selEstado'],
		$valForm['txtFecha'],
                $valForm['txtFecha1']));
	
	return $objResponse;
}

function listadoDeposito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_deposito")){
		$objResponse->assign("tdListadoDeposito","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	$objResponse -> setCharacterEncoding('UTF-8');
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" AND (te_depositos.numero_deposito_banco LIKE %s 
                              OR te_depositos.observacion LIKE %s)",
		valTpDato("%".$valCadBusq[0]."%", "text"),
       valTpDato("%".$valCadBusq[0]."%", "text"));
	
	if ($valCadBusq[1] == -1)
		$sqlBusq .= " AND te_depositos.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
	
	else if ($valCadBusq[1] != 0)
		$sqlBusq .= " AND te_depositos.id_empresa = '".$valCadBusq[1]."'";
		
	if ($valCadBusq[2] == 0)
		$sqlBusq .= " AND te_depositos.estado_documento <> '".$valCadBusq[2]."'";
	
	else if ($valCadBusq[2] != 0)
		$sqlBusq .= " AND te_depositos.estado_documento = '".$valCadBusq[2]."'";

	if ($valCadBusq[3] != '' && $valCadBusq[4] != ''){
		$sqlBusq .= sprintf("AND DATE(te_depositos.fecha_registro) BETWEEN '%s' AND '%s' ",
                                        date("Y-m-d",strtotime($valCadBusq[3])),
                                        date("Y-m-d",strtotime($valCadBusq[4]))); 
        } 
	
	$queryDeposito = sprintf("SELECT 
		te_depositos.id_deposito,
		te_depositos.id_numero_cuenta,
		te_depositos.fecha_registro,
		te_depositos.fecha_aplicacion,
		te_depositos.fecha_conciliacion,
		te_depositos.fecha_movimiento_banco,
		te_depositos.numero_deposito_banco,
		te_depositos.estado_documento,
		te_depositos.origen,
		te_depositos.id_usuario,
		te_depositos.monto_total_deposito,
		te_depositos.id_empresa,
		te_depositos.desincorporado,
		te_depositos.monto_efectivo,
		te_depositos.monto_cheques_total,
		te_depositos.observacion,
		te_depositos.folio_deposito,       
       pg_motivo.descripcion,
       pg_motivo.id_motivo,
       CONCAT_WS('. ', pg_motivo.id_motivo, pg_motivo.descripcion) AS motivo
	FROM
		te_depositos
       LEFT JOIN pg_motivo ON te_depositos.id_motivo = pg_motivo.id_motivo
	WHERE
		te_depositos.desincorporado <> 0").$sqlBusq;
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $queryDeposito, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($queryDeposito);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTableIni .= "<table border=\"0\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "", $pageNum, "origen", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "7%", $pageNum, "numero_deposito_banco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dep&oacute;sito");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "5%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "5%", $pageNum, "fecha_aplicacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "5%", $pageNum, "fecha_conciliacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Conciliaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "15%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco Compa&ntilde;ia");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "15%", $pageNum, "id_numero_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta Compa&ntilde;ia");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "30%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "", $pageNum, "monto_total_deposito", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($rowDeposito = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
       $motivo = "<br><small><b>".utf8_encode($rowDeposito['motivo'])."</b></small>";
        
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".estadoNota($rowDeposito['estado_documento'])."</td>";
			$htmlTb .= "<td align=\"center\">".Origen($rowDeposito['origen'])."</td>";
			$htmlTb .= "<td align=\"center\">".empresa($rowDeposito['id_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".$rowDeposito['numero_deposito_banco']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d/m/Y",strtotime($rowDeposito['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".fechaAplica($rowDeposito['id_deposito'])."</td>";
			$htmlTb .= "<td align=\"center\">".fecha($rowDeposito['id_deposito'])."</td>";
			$htmlTb .= "<td align=\"center\">".nombreBanco($rowDeposito['id_numero_cuenta'])."</td>";
			$htmlTb .= "<td align=\"center\">".cuenta($rowDeposito['id_numero_cuenta'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowDeposito['observacion']).$motivo."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($rowDeposito['monto_total_deposito'],'2','.',',')."</td>";
			$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"xajax_cargarDatosDepositos(".$rowDeposito['id_deposito'].")\" src=\"../img/iconos/ico_view.png\" /></td>";
		if($rowDeposito['estado_documento']==3 || $rowDeposito['origen'] != 0){
			$htmlTb .= "<td align=\"center\"><img src=\"../img/iconos/ico_quitarf2.gif\"/></td>";	
		}else if($rowDeposito['estado_documento']!=3 && $rowDeposito['origen'] == 0){
                        $htmlTb .= "<td align=\"center\"><img src=\"../img/iconos/ico_quitarf2.gif\"/></td>";	
			//$htmlTb .= "<td align=\"center\"><img class=\"puntero\" onclick=\"xajax_eliminarDeposito(".$rowDeposito['id_deposito'].")\" src=\"../img/iconos/ico_quitar.gif\" /></td>";
                }
		if($rowDeposito['origen']==0){//TESORERIA
			$sPar = "idobject=".$rowDeposito['id_deposito'];
				 $sPar.= "&ct=18";
				 $sPar.= "&dt=03";
				 $sPar.= "&cc=05";}
				 
		elseif($rowDeposito['origen']==1){//VEHICULO
			$sPar = "idobject=".$rowDeposito['id_deposito'];
				 $sPar.= "&ct=13";
				 $sPar.= "&dt=03";
				 $sPar.= "&cc=05";}
				 
		elseif($rowDeposito['origen']==2){//REPUESTO
			$sPar = "idobject=".$rowDeposito['id_deposito'];
				 $sPar.= "&ct=05";
				 $sPar.= "&dt=03";
				 $sPar.= "&cc=05";}
			// Modificado Ernesto
			$htmlTb .= "<td  align=\"center\">";
				$htmlTb .= "<img onclick=\"verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?$sPar', 1000, 500);\" src=\"../img/iconos/new_window.png\" title=\"Ver Movimiento Contable\"/>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"15\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoDeposito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
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
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoDeposito","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);

	return $objResponse;
}	

function nuevoDeposito($valForm){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_deposito","insertar")){
		return $objResponse;
	}
	
	//$objResponse->script("xajax_comboMotivo(xajax.getFormValues())");//cerrado
	$objResponse->script("
		document.forms['frmDeposito'].reset();
		document.getElementById('txtNombreBanco').className = 'inputInicial';
		document.getElementById('txtNombreEmpresa').className = 'inputInicial';
		document.getElementById('txtObservacion').className = 'inputInicial';
		document.getElementById('txtTotalEfectivo').readOnly = false;
		document.getElementById('txtObservacion').readOnly = false;
		document.getElementById('txtNumeroPlanilla').readOnly = false;
		document.getElementById('tblCheques').style.display = '';
		document.getElementById('tdSelCuentas1').style.display = 'none';
		document.getElementById('tdSelCuentas').style.display = '';
		document.getElementById('trCosiliacion').style.display = 'none';
		document.getElementById('btnListEmpresa').disabled = false;
		document.getElementById('btnListBanco').disabled = false;
		document.getElementById('trFolio').style.display = 'none';
		document.getElementById('trSaldoCuenta').style.display = '';
		document.getElementById('tdListadoCheques').style.display = 'none';
		document.getElementById('txtImporteMovimiento').className = 'inputInicial';");
	
	$objResponse->script("document.getElementById('divFlotante').style.display = '';
							document.getElementById('divFlotante2').style.display = 'none';
							document.getElementById('tblBanco').style.display = '';
							document.getElementById('tblConsulta').style.display = 'none';
						  document.getElementById('tdFlotanteTitulo').innerHTML = 'Nueva Deposito';
						  
						  centrarDiv(document.getElementById('divFlotante'))");
	
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++){
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
				
		}else {
			
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}

	foreach($arrayObj as $indiceItmRep=>$valorItmRep) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trModComp:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmRep));
	}
	
	$fecha = date("d-m-Y");
	$estado = "Por Aplicar";
	
	$objResponse->assign("txtFechaRegistro","value",$fecha);
	$objResponse->assign("txtFechaAplicacion","value",$fecha);
	$objResponse->assign("txtEstado","value",$estado);
	
	$html ="<hr><td align=\"right\">";		
	$html .= "<input type=\"button\" value=\"Guardar\" id=\"btnGuardar\" onclick=\"this.disabled = true; validarFormInsertar();\">";
	$html .= "<input type=\"button\" value=\"Cancelar\" onclick=\"document.getElementById('divFlotante').style.display='none';\">";
	$html .="</td>";
	
	$objResponse->assign("tdDepositoBotones","innerHTML",$html);
	
	return $objResponse;
}

function listBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT bancos.idBanco, bancos.nombreBanco, bancos.sucursal FROM bancos INNER JOIN cuentas ON (cuentas.idBanco = bancos.idBanco) WHERE bancos.idBanco != '1' GROUP BY idBanco";
	$rsBanco = mysql_query($queryBanco) or die(mysql_error());
	
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitBanco = sprintf("%s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);        
	
	$rsLimitBanco = mysql_query($queryLimitBanco) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco) or die(mysql_error());
		$totalRows = mysql_num_rows($rsBanco);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
        
        $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
                $htmlTh .= "<td width=\"5%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listBanco", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listBanco", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listBanco", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");
		
	$htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitBanco)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarBanco('".$rowBanco['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['sucursal'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listBanco(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
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
								  document.getElementById('tdFlotanteTitulo2').innerHTML = 'Seleccione Banco';
								  centrarDiv(document.getElementById('divFlotante2'))");	
	return $objResponse;
}

function asignarBanco($id_banco){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("document.getElementById('divFlotante2').style.display = 'none'");	
	
	$query = "SELECT * FROM bancos WHERE idBanco = '".$id_banco."'";
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtNombreBanco","value",$row['nombreBanco']);
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmDeposito'))");
	
	return $objResponse;
}

function listBanco1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT * FROM bancos WHERE nombreBanco <> '-' GROUP BY idBanco";
	$rsBanco = mysql_query($queryBanco) or die(mysql_error());	
	
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitBanco = sprintf("%s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow); 
        
	$rsLimitBanco = mysql_query($queryLimitBanco) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco) or die(mysql_error());
		$totalRows = mysql_num_rows($rsBanco);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
        
        $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
                $htmlTh .= "<td width=\"5%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listBanco1", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listBanco1", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listBanco1", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");
		
	$htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitBanco)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarBanco1('".$rowBanco['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".  utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".  utf8_encode($rowBanco['sucursal'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listBanco1(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
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
								  document.getElementById('tdFlotanteTitulo2').innerHTML = 'Seleccione Banco';
								  centrarDiv(document.getElementById('divFlotante2'))");	
	return $objResponse;
}

function asignarBanco1($id_banco){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("document.getElementById('divFlotante2').style.display = 'none'");	
	
	$query = "SELECT * FROM bancos WHERE idBanco = '".$id_banco."'";
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtBancoCheque","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBancoCheque","value",$row['idBanco']);
	
	return $objResponse;
}

function comboCuentas($valForm){
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdBanco'] == -1){
		$disabled = "disabled=\"disabled\"";
	}
	else{
		$condicion = "WHERE idBanco = '".$valForm['hddIdBanco']."' AND id_empresa = '".$valForm['hddIdEmpresa']."'";
		$disabled = "";
	}
	
	$queryCuentas = "SELECT * FROM cuentas ".$condicion."";
	$rsCuentas = mysql_query($queryCuentas) or die(mysql_error());
	
	$html = "<select id=\"selCuenta\" name=\"selCuenta\" ".$disabled." onchange=\"xajax_cargaSaldoCuenta(this.value)\">";
			$html .= "<option value=\"-1\">Seleccione</option>";
		while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
			$html .= "<option value=\"".$rowCuentas['idCuentas']."\">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuentas","innerHTML",$html);
		
	return $objResponse;
}

function cargaSaldoCuenta($id_cuenta){
	$objResponse = new xajaxResponse();

	$queryCuenta = sprintf("SELECT * FROM cuentas WHERE  idCuentas = '%s'",$id_cuenta);
	$rsCuenta = mysql_query($queryCuenta) or die(mysql_error());
	$rowCuenta = mysql_fetch_array($rsCuenta);
	
	$objResponse->assign("txtSaldoCuenta","value",number_format($rowCuenta['saldo_tem'],'2','.',','));
	
	return $objResponse;
}

function comboEmpresa($valForm){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY id_empresa_reg",$_SESSION['idUsuarioSysGts']);
			
		$rs = mysql_query($query) or die (mysql_error());
		$html = "<select id=\"selEmpresa\" name=\"selEmpresa\" onChange=\"xajax_buscarDeposito(xajax.getFormValues('frmBuscar'))\">";
		$html .="<option value=\"0\">Todas</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$nombreSucursal = "";
			if ($row['id_empresa_padre_suc'] > 0)
				$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
			
			$selected = "";
			if ($selId == $row['id_empresa_reg'] || $_SESSION['idEmpresaUsuarioSysGts'] == $row['id_empresa_reg'])
				$selected = "selected='selected'";
		
			$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".  utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
		}
		$html .= "</select>";
	
		$objResponse->assign("tdSelEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function comboEstado($valForm){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_estados_principales ORDER BY id_estados_principales");
		$rs = mysql_query($query) or die (mysql_error());
		$html = "<select id=\"selEstado\" name=\"selEstado\" onChange=\"xajax_buscarDeposito(xajax.getFormValues('frmBuscar'))\">";
		$html .="<option selected=\"selected\" value=\"0\">[ Todos ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = "selected='selected'";
			$html .= "<option value=\"".$row['id_estados_principales']."\">".htmlentities($row['descripcion'].$nombreSucursal)."</option>";
		}
		$html .= "</select>";
	
		$objResponse->assign("tdSelEstado","innerHTML",$html);
	
	return $objResponse;
}

//function comboMotivo($valForm){//cerrado
//	$objResponse = new xajaxResponse();
//
//	$queryMotivo = "SELECT * FROM pg_motivo WHERE modulo = 'TE' AND ingreso_egreso = 'I'";
//	$rsMotivo = mysql_query($queryMotivo) or die(mysql_error());
//	
//	$html = "<select id=\"selMotivo\" name=\"selMotivo\">";
//			$html .="<option value=\"\">Seleccione</option>";
//		while ($rowMotivo = mysql_fetch_assoc($rsMotivo)){
//			$html .= "<option value=\"".$rowMotivo['id_motivo']."\">".utf8_encode($rowMotivo['descripcion'])."</option>";
//	}
//
//	$html .= "</select>";
//	
//	$objResponse->assign("tdSelMotivo","innerHTML",$html);
//	
//		
//	return $objResponse;
//}

function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
        if($campOrd == "") { $campOrd = 'id_empresa_reg'; }

	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
	
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitEmpresa = sprintf("%s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        	
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
		$totalRows = mysql_num_rows($rsEmpresa);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";		
        
        $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width='5%'></td>";
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");		
	$htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitEmpresa)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$rowBanco['id_empresa_reg']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombre_empresa'].$rowBanco['nombre_empresa_suc'])."</td>";
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"3\">";
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
								  document.getElementById('tdFlotanteTitulo2').innerHTML = 'Seleccione Empresa';
								  document.getElementById('tblListados2').style.display = '';
								  document.getElementById('txtNombreBanco').value = '';
								  document.getElementById('txtSaldoCuenta').value = '';
								  centrarDiv(document.getElementById('divFlotante2'))");	
	return $objResponse;
}

function asignarEmpresa($idEmpresa){
	$objResponse = new xajaxResponse();
	
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
		$rsEmpresa = mysql_query($queryEmpresa) or die (mysql_error());
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

function formAgregarCheques(){
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("tdFlotanteTitulo3","innerHTML","Agregar Cheques");
	$objResponse->assign("tblMontos","width","400px");
	
	$objResponse->script("
		document.forms['frmMonto'].reset();
		
		document.getElementById('txtBancoCheque').className = 'inputInicial';
		document.getElementById('hddIdBancoCheque').className = 'inputInicial';
		document.getElementById('txtNumeroCuentaCheque').className = 'inputInicial';
		document.getElementById('txtMontoCheque').className = 'inputInicial';
		document.getElementById('txtNumeroCheque').className = 'inputInicial';");
		
	
	$objResponse->script("		
		document.getElementById('divFlotante3').style.display='';
		document.getElementById('tblMontos').style.display='';
		 document.getElementById('trSaldoCuenta').style.display = 'none';
		centrarDiv(document.getElementById('divFlotante3'));
	");
	
	
	return $objResponse;
}

function insertarCheques($valForm, $valFormCheques) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
		document.getElementById('divFlotante3').style.display='none';");

/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++) {
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$sigValor = $arrayObj[count($arrayObj)-1] + 1;
	
        if ($sigValor %2 == 0){//par
            $resaltar = "trResaltarTesoreria2";
        }else{//impar
            $resaltar = "trResaltarTesoreria1";
        }
        
	/*$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trModComp:%s', 'class':'textoGris_11px ".$resaltar."', 'title':'trModComp:%s'}).adopt([
			new Element('td', {'align':'center'}).setHTML(\"<input id='cbxMonto' name='cbxItm[]' type='checkbox' value='%s'/>\"),
			new Element('td', {'align':'center'}).setHTML(\"%s"."<input id='hddBancoCheque%s' name='hddBancoCheque%s' type='hidden' value='%s' />\"),
			new Element('td', {'align':'center'}).setHTML(\"%s"."<input id='hddNumeroCuentaCheque%s' name='hddNumeroCuentaCheque%s' type='hidden' value='%s' />\"),
			new Element('td', {'align':'center'}).setHTML(\"%s"."<input id='hddNumeroCheque%s' name='hddNumeroCheque%s' type='hidden' value='%s' />\"),
			new Element('td', {'align':'right'}).setHTML(\"%s"."<input id='hddMontoCheques%s' name='hddMontoCheques%s' type='hidden'  value='%s' />\")
		]);
		elemento.injectBefore('trMontosDepositos');
		
		",
		$sigValor, $sigValor,
		$sigValor,
		$valFormCheques['txtBancoCheque'],$sigValor, $sigValor, $valFormCheques['hddIdBancoCheque'],
		$valFormCheques['txtNumeroCuentaCheque'],$sigValor, $sigValor, $valFormCheques['txtNumeroCuentaCheque'],
		$valFormCheques['txtNumeroCheque'],$sigValor, $sigValor, $valFormCheques['txtNumeroCheque'],
		$valFormCheques['txtMontoCheque'],$sigValor, $sigValor, $valFormCheques['txtMontoCheque']
		));*/
        
        $objResponse->script(sprintf(""
                . "var nuevoTr = \"<tr id='trModComp:%s' class='textoGris_11px ".$resaltar."' title= 'trModComp:%s'>".
		"<td align='center'><input id='cbxMonto' name='cbxItm[]' type='checkbox' value='%s'/></td>".
		"<td align='center'>%s<input id='hddBancoCheque%s' name='hddBancoCheque%s' type='hidden' value='%s' /></td>".
                "<td align='center'>%s<input id='hddNumeroCuentaCheque%s' name='hddNumeroCuentaCheque%s' type='hidden' value='%s' /></td>".
                "<td align='center'>%s<input id='hddNumeroCheque%s' name='hddNumeroCheque%s' type='hidden' value='%s' /></td>".
                "<td align='right'>%s<input id='hddMontoCheques%s' name='hddMontoCheques%s' type='hidden'  value='%s' /></td>".
            "</tr>\";".
		"$(nuevoTr).insertBefore('#trMontosDepositos');",
		$sigValor, $sigValor,
		$sigValor,
		$valFormCheques['txtBancoCheque'],$sigValor, $sigValor, $valFormCheques['hddIdBancoCheque'],
		$valFormCheques['txtNumeroCuentaCheque'],$sigValor, $sigValor, $valFormCheques['txtNumeroCuentaCheque'],
		$valFormCheques['txtNumeroCheque'],$sigValor, $sigValor, $valFormCheques['txtNumeroCheque'],
		$valFormCheques['txtMontoCheque'],$sigValor, $sigValor, $valFormCheques['txtMontoCheque']
		));
	
	
	$arrayObj[] = $sigValor;
	foreach($arrayObj as $indice => $valor) {
		$cadena = $valForm['hddObj']."|".$valor;
		$montoCheques += $valForm['hddMontoCheques'.$valor]; 
	}
	
	$montoCheques += $valFormCheques['txtMontoCheque']; 
	
	$montoTotal = $montoCheques + $valForm['txtTotalEfectivo'];
	
	$objResponse->assign("hddObj","value",$cadena);
	$objResponse->assign("txtTotalCheques","value",$montoCheques);
	$objResponse->assign("txtTotalDeposito","value",$montoTotal);
	
	return $objResponse;
}

function actualizarObjetosExistentes($valForm,$valFormCheques){
	$objResponse = new xajaxResponse();
	 
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS	*/
	for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++){
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
				
		}else {
			
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}

	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valForm['hddMontoCheques'.$valor]))
			$cadena .= "|".$valor;
	}
	
	$objResponse->assign("hddObj","value",$cadena);
	
	$cadena2=$cadena;
	$cadena="";
	for ($cont = 0; $cont <= strlen($cadena2); $cont++) {
		$caracter = substr($cadena2, $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
		}else {
			$arrayObj2[] = $cadena;
			$cadena = "";
		}	
	}
		
	foreach($arrayObj2 as $indice => $valor) {
		$montoCheques += $valForm["hddMontoCheques".$valor];
	}

		$objResponse->assign("txtTotalCheques","value",$montoCheques);
		
	$objResponse->script("xajax_actualizarMonto(xajax.getFormValues('frmDeposito'));");
	
	return $objResponse;
}

function actualizarMonto($valForm){
	$objResponse = new xajaxResponse();

	$montoTotal = $valForm['txtTotalCheques'] + $valForm['txtTotalEfectivo'];
	
	$objResponse -> assign("txtTotalDeposito","value",$montoTotal);


	return $objResponse;
}

function eliminaElementos($valForm){
	$objResponse = new xajaxResponse();	
	
	if (isset($valForm['cbxItm'])) {
		foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trModComp:%s');
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
			$objResponse->script(sprintf("
				fila = document.getElementById('trModComp:%s');
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	
	$objResponse->script("xajax_actualizarObjetosExistentes(xajax.getFormValues('frmDeposito'),xajax.getFormValues('frmMonto'))");
			
	return $objResponse;	
}

function guardarDeposito($valForm){
	$objResponse = new xajaxResponse();	
	
        $objResponse->script("desbloquearGuardado();");
        
	mysql_query("START TRANSACTION;");
	
	//* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS	*/
	for ($cont = 1; $cont <= strlen($valForm['hddObj']); $cont++){
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
				
		}else {
			
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$queryFolio = sprintf("SELECT * FROM te_folios WHERE id_folios = '2'");
	$rsFolio = mysql_query($queryFolio);
	if (!$rsFolio) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	$rowFolio = mysql_fetch_array($rsFolio);
		
	$numeroFolio = $rowFolio['numero_actual'];	
	$numeroFolioNuevo = $rowFolio['numero_actual'] + 1;
	$queryFoliosUpdate = sprintf("UPDATE te_folios SET numero_actual = '%s' WHERE id_folios = '2'",$numeroFolioNuevo);		
	$rsFoliosUpdate = mysql_query($queryFoliosUpdate);
	if (!$rsFoliosUpdate) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	/* INSERTA DEPOSITO CABECERA */
	
	$queryDepositos = sprintf ( "INSERT INTO te_depositos( id_numero_cuenta, fecha_registro, fecha_aplicacion, numero_deposito_banco, estado_documento, origen, id_usuario, monto_total_deposito, id_empresa, desincorporado, monto_efectivo, monto_cheques_total, observacion, folio_deposito, id_motivo)VALUE('%s', '%s', NOW(), '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s')",
		$valForm['selCuenta'],
		date("Y/m/d",strtotime($valForm['txtFechaRegistro'])),
		$valForm['txtNumeroPlanilla'],
		2,
		0,
		$_SESSION['idUsuarioSysGts'],
		$valForm['txtTotalDeposito'],
		$valForm['hddIdEmpresa'],
		1,
		$valForm['txtTotalEfectivo'],
		$valForm['txtTotalCheques'],
		$valForm['txtObservacion'],
		$rowFolio['numero_actual'],
		$valForm['selMotivo']);
	$rsDepositos = mysql_query($queryDepositos);
	if (!$rsDepositos) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	$idDeposito = mysql_insert_id();
	
	$queryEstadoCuenta = sprintf ( "INSERT INTO te_estado_cuenta ( tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales)VALUE('%s', '%s','%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
		'DP',
		$idDeposito,
		date("Y-m-d h:i:s",strtotime($valForm['txtFechaRegistro'])),
		$valForm['selCuenta'],
		$valForm['hddIdEmpresa'],
		$valForm['txtTotalDeposito'],
		'1',
		$valForm['txtNumeroPlanilla'],
		'1',
		$valForm['txtObservacion'],
		'2');
		$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
		if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	if(isset($arrayObj)){
		foreach($arrayObj as $indice => $valor) {
			$queryDepositosCheques = sprintf ( "INSERT INTO te_deposito_detalle ( id_deposito, id_banco, numero_cuenta_cliente, numero_cheques, monto)VALUE('%s', '%s', '%s', '%s', '%s')",
			$idDeposito,
			$valForm['hddBancoCheque'.$valor],
			$valForm['hddNumeroCuentaCheque'.$valor],
			$valForm['hddNumeroCheque'.$valor],
			$valForm['hddMontoCheques'.$valor]);
			$rsDepositosCheques = mysql_query($queryDepositosCheques);
		if (!$rsDepositosCheques) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
		}
	}
	
	$querySaldoCuenta = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valForm['selCuenta']);
	$rsSaldoCuenta = mysql_query($querySaldoCuenta);
	if (!$rsSaldoCuenta) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	$rowSaldoCuenta = mysql_fetch_array($rsSaldoCuenta);
	
	$sumaDepositoCuenta = $rowSaldoCuenta['saldo_tem'] + $valForm['txtTotalDeposito'];
	
	$queryCuentaActualiza = sprintf("UPDATE cuentas SET saldo_tem = '%s' WHERE idCuentas = '%s'", $sumaDepositoCuenta, $valForm['selCuenta']);
	$rsCuentaActualiza = mysql_query($queryCuentaActualiza);
	if (!$rsCuentaActualiza) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$objResponse -> alert("Los Datos se han Guardado Correctamente");
	
	$objResponse->script("document.getElementById('divFlotante').style.display = 'none';");
	
	$objResponse->script("xajax_listadoDeposito(0,'','','' + '|' + -1 + '|' + 0 + '|' + '');");
	
	mysql_query("COMMIT;");
	
	//Modifcar Ernesto
	if(function_exists("generarDepositoTe")){
		generarDepositoTe($idDeposito,"","");
	}
	//Modifcar Ernesto
	
	return $objResponse;	
}

function cargarDatosDepositos($id){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("document.getElementById('divFlotante').style.display = '';
						  document.getElementById('txtTotalEfectivo').readOnly = true;
						  document.getElementById('txtObservacion').readOnly = true;
						  document.getElementById('txtNumeroPlanilla').readOnly = true;
						  document.getElementById('tblCheques').style.display = 'none';
						  document.getElementById('tdSelCuentas1').style.display = '';
						  document.getElementById('tdSelCuentas').style.display = 'none';
						  document.getElementById('btnListEmpresa').disabled = true;
						  document.getElementById('btnListBanco').disabled = true;
						  document.getElementById('trCosiliacion').style.display = '';
						  document.getElementById('trFolio').style.display = '';
						  document.getElementById('tdListadoCheques').style.display = '';
						  document.getElementById('trSaldoCuenta').style.display = 'none';
						  document.getElementById('tdFlotanteTitulo').innerHTML = 'Consultar Deposito';
						  centrarDiv(document.getElementById('divFlotante'))");
	
	$queryDeposito = sprintf("SELECT 
								  te_depositos.id_deposito,
								  te_depositos.id_numero_cuenta,
								  te_depositos.fecha_registro,
								  te_depositos.fecha_aplicacion,
								  te_depositos.fecha_conciliacion,
								  te_depositos.fecha_movimiento_banco,
								  te_depositos.numero_deposito_banco,
								  te_depositos.estado_documento,
								  te_depositos.origen,
								  te_depositos.id_usuario,
								  te_depositos.monto_total_deposito,
								  te_depositos.id_empresa,
								  te_depositos.desincorporado,
								  te_depositos.monto_efectivo,
								  te_depositos.monto_cheques_total,
								  te_depositos.observacion,
								  te_depositos.folio_deposito,
								  te_depositos.id_motivo,
								  cuentas.idCuentas,
								  cuentas.idBanco,
								  cuentas.numeroCuentaCompania,
								  bancos.idBanco,
								  bancos.nombreBanco,
								  cuentas.saldo_tem,
								  te_estados_principales.id_estados_principales,
								  te_estados_principales.descripcion
								FROM
								  te_depositos
								  INNER JOIN cuentas ON (te_depositos.id_numero_cuenta = cuentas.idCuentas)
								  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
								  INNER JOIN te_estados_principales ON (te_depositos.estado_documento = te_estados_principales.id_estados_principales)
								WHERE
								  te_depositos.id_deposito = '%s'",$id);
								  		  
			
								
								
	$rsDeposito = mysql_query($queryDeposito) or die(mysql_error());
	$rowDeposito = mysql_fetch_array($rsDeposito);
	
	
	$objResponse->assign("txtNombreEmpresa","value",empresa($rowDeposito['id_empresa']));
	$objResponse->assign("txtNombreBanco","value",$rowDeposito['nombreBanco']);
	$objResponse->assign("txtCuentaBanco","value",$rowDeposito['numeroCuentaCompania']);
	//$objResponse->assign("txtSaldoCuenta","value",number_format($rowDeposito['saldo_tem'],'2','.',','));
	$objResponse->assign("txtFechaRegistro","value",date("d/m/Y",strtotime($rowDeposito['fecha_registro'])));
	$objResponse->assign("txtFechaAplicacion","value",date("d/m/Y",strtotime($rowDeposito['fecha_aplicacion'])));
	$objResponse->assign("txtFechaConsiliacion","value",fecha($valForm['fecha_conciliacion']));
	$objResponse->assign("txtNumeroPlanilla","value",$rowDeposito['numero_deposito_banco']);
	$objResponse->assign("txtObservacion","value",$rowDeposito['observacion']);
	$objResponse->assign("txtEstado","value",$rowDeposito['descripcion']);
	$objResponse->assign("txtTotalEfectivo","value",number_format($rowDeposito['monto_efectivo'],'2','.',','));
	$objResponse->assign("txtTotalCheques","value",number_format($rowDeposito['monto_cheques_total'],'2','.',','));
	$objResponse->assign("txtTotalDeposito","value",number_format($rowDeposito['monto_total_deposito'],'2','.',','));
	$objResponse->assign("txtFolioDeposito","value",$rowDeposito['folio_deposito']);
        
        if($rowDeposito['id_motivo'] != "0" && $rowDeposito['id_motivo'] != NULL){
            
            $query = sprintf("SELECT id_motivo, descripcion FROM pg_motivo WHERE id_motivo = %s LIMIT 1", $rowDeposito['id_motivo']);
            $rs = mysql_query($query);
            if(!$rs) { return $objResponse->alert(mysql_error()."\n\n".__LINE__); }

            $row = mysql_fetch_assoc($rs);
            
            $objResponse->assign("selMotivo","value",$rowDeposito['id_motivo']);
            $objResponse->assign("txtSelMotivo","value",utf8_encode($row['descripcion']));
        }else{
            $objResponse->assign("selMotivo","value","");
            $objResponse->assign("txtSelMotivo","value","");
        }
	
	$objResponse->script(sprintf("xajax_listCheques(0,'','','%s');",$rowDeposito['id_deposito']));
	
	$html ="<hr><td align=\"right\">";		
	$html .= "<input type=\"button\" value=\"Cancelar\" onclick=\"document.getElementById('divFlotante').style.display='none';\">";
	$html .="</td>";
	
	$objResponse->assign("tdDepositoBotones","innerHTML",$html);
	
	return $objResponse;
}

function listCheques($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT 
					  bancos.nombreBanco,
					  bancos.idBanco,
					  te_deposito_detalle.id_deposito_detalle,
					  te_deposito_detalle.id_deposito,
					  te_deposito_detalle.id_banco,
					  te_deposito_detalle.numero_cuenta_cliente,
					  te_deposito_detalle.numero_cheques,
					  te_deposito_detalle.monto
					FROM
					  te_deposito_detalle
					  INNER JOIN bancos ON (te_deposito_detalle.id_banco = bancos.idBanco)
					WHERE
					  te_deposito_detalle.id_deposito = '$valBusq'";
	$rsBanco = mysql_query($queryBanco) or die(mysql_error());
	
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitBanco = sprintf("%s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);        
	
	$rsLimitBanco = mysql_query($queryLimitBanco) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco) or die(mysql_error());
		$totalRows = mysql_num_rows($rsBanco);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
		
        $htmlTblIni .= "<table border=\"0\" width=\"100%\">";
        
        $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";		
		$htmlTh .= ordenarCampo("xajax_listCheques", "25%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listCheques", "25%", $pageNum, "numero_cuenta_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Cuenta");
		$htmlTh .= ordenarCampo("xajax_listCheques", "25%", $pageNum, "numero_cheques", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Cheque");
		$htmlTh .= ordenarCampo("xajax_listCheques", "25%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Cheque");		
	$htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitBanco)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".$rowBanco['nombreBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['numero_cuenta_cliente']."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['numero_cheques']."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($rowBanco['monto'],'2','.',',')."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listCheques(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listCheques(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$objResponse->assign("tdListadoCheques","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);		
		
	return $objResponse;
}

function eliminarDeposito($id){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_deposito","eliminar")){
		return $objResponse;
	}
	
	$objResponse->script("document.getElementById('divFlotante').style.display = '';
						  document.getElementById('txtTotalEfectivo').readOnly = true;
						  document.getElementById('txtObservacion').readOnly = true;
						  document.getElementById('txtNumeroPlanilla').readOnly = true;
						  document.getElementById('tblCheques').style.display = 'none';
						  document.getElementById('tdSelCuentas1').style.display = '';
						  document.getElementById('tdSelCuentas').style.display = 'none';
						  document.getElementById('btnListEmpresa').disabled = true;
						  document.getElementById('btnListBanco').disabled = true;
						  document.getElementById('trCosiliacion').style.display = '';
						  document.getElementById('trFolio').style.display = '';
						  document.getElementById('tdListadoCheques').style.display = '';
						  document.getElementById('trSaldoCuenta').style.display = 'none';
						 
						  document.getElementById('tdFlotanteTitulo').innerHTML = 'Consultar Deposito';
						  centrarDiv(document.getElementById('divFlotante'))");
	
	$queryDeposito = sprintf("SELECT 
								  te_depositos.id_deposito,
								  te_depositos.id_numero_cuenta,
								  te_depositos.fecha_registro,
								  te_depositos.fecha_aplicacion,
								  te_depositos.fecha_conciliacion,
								  te_depositos.fecha_movimiento_banco,
								  te_depositos.numero_deposito_banco,
								  te_depositos.estado_documento,
								  te_depositos.origen,
								  te_depositos.id_usuario,
								  te_depositos.monto_total_deposito,
								  te_depositos.id_empresa,
								  te_depositos.desincorporado,
								  te_depositos.monto_efectivo,
								  te_depositos.monto_cheques_total,
								  te_depositos.observacion,
								  te_depositos.folio_deposito,
								  cuentas.idCuentas,
								  cuentas.idBanco,
								  cuentas.numeroCuentaCompania,
								  bancos.idBanco,
								  bancos.nombreBanco,
								  cuentas.saldo_tem,
								  te_estados_principales.id_estados_principales,
								  te_estados_principales.descripcion
								FROM
								  te_depositos
								  INNER JOIN cuentas ON (te_depositos.id_numero_cuenta = cuentas.idCuentas)
								  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
								  INNER JOIN te_estados_principales ON (te_depositos.estado_documento = te_estados_principales.id_estados_principales)
								WHERE
								  te_depositos.id_deposito = '%s'",$id);
	
	
		
	
	$rsDeposito = mysql_query($queryDeposito) or die(mysql_error());
	$rowDeposito = mysql_fetch_array($rsDeposito);
	
	$objResponse->assign("txtNombreEmpresa","value",empresa($rowDeposito['id_empresa']));
	$objResponse->assign("txtNombreBanco","value",$rowDeposito['nombreBanco']);
	$objResponse->assign("txtCuentaBanco","value",$rowDeposito['numeroCuentaCompania']);
	//$objResponse->assign("txtSaldoCuenta","value",number_format($rowDeposito['saldo_tem'],'2','.',','));
	$objResponse->assign("txtFechaRegistro","value",date("d/m/Y",strtotime($rowDeposito['fecha_registro'])));
	$objResponse->assign("txtFechaAplicacion","value",date("d/m/Y",strtotime($rowDeposito['fecha_aplicacion'])));
	$objResponse->assign("txtFechaConsiliacion","value",fecha($valForm['fecha_conciliacion']));
	$objResponse->assign("txtNumeroPlanilla","value",$rowDeposito['numero_deposito_banco']);
	$objResponse->assign("txtObservacion","value",$rowDeposito['observacion']);
	$objResponse->assign("txtEstado","value",$rowDeposito['descripcion']);
	$objResponse->assign("txtTotalEfectivo","value",number_format($rowDeposito['monto_efectivo'],'2','.',','));
	$objResponse->assign("txtTotalCheques","value",number_format($rowDeposito['monto_cheques_total'],'2','.',','));
	$objResponse->assign("txtTotalDeposito","value",number_format($rowDeposito['monto_total_deposito'],'2','.',','));
	$objResponse->assign("txtFolioDeposito","value",$rowDeposito['folio_deposito']);
	
	$objResponse->script(sprintf("xajax_listCheques(0,'','','%s');",$rowDeposito['id_deposito']));
	
	$html ="<hr><td align=\"right\">";		
	$html .= "<input type=\"button\" value=\"Eliminar\" onclick=\"xajax_eliminar($rowDeposito[id_deposito])\">";
	$html .= "<input type=\"button\" value=\"Cancelar\" onclick=\"document.getElementById('divFlotante').style.display='none';\">";
	$html .="</td>";
	
	$objResponse->assign("tdDepositoBotones","innerHTML",$html);
	
	return $objResponse;	
}

function eliminar($id){
	
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$queryDeposito = sprintf("SELECT 
								  cuentas.idCuentas,
								  cuentas.numeroCuentaCompania,
								  te_depositos.id_deposito,
								  te_depositos.id_numero_cuenta,
								  te_depositos.fecha_registro,
								  te_depositos.fecha_aplicacion,
								  te_depositos.fecha_conciliacion,
								  te_depositos.fecha_movimiento_banco,
								  te_depositos.numero_deposito_banco,
								  te_depositos.estado_documento,
								  te_depositos.origen,
								  te_depositos.id_usuario,
								  te_depositos.monto_total_deposito,
								  te_depositos.id_empresa,
								  te_depositos.desincorporado,
								  te_depositos.monto_efectivo,
								  te_depositos.monto_cheques_total,
								  te_depositos.observacion,
								  te_depositos.folio_deposito,
								  cuentas.saldo_tem
								FROM
								  te_depositos
								  INNER JOIN cuentas ON (te_depositos.id_numero_cuenta = cuentas.idCuentas)
								WHERE
								  te_depositos.id_deposito = '%s'",$id);
	$rsDeposito = mysql_query($queryDeposito) or die(mysql_error());
	$rowDeposito = mysql_fetch_array($rsDeposito);
	if (!$rowDeposito) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	 $saldoNuevo = $rowDeposito['saldo_tem'] - $rowDeposito['monto_total_deposito'];
	
	$queryDesabilita = sprintf("UPDATE te_depositos SET desincorporado = '%s' WHERE id_deposito = '%s'", 0,$id);
	$rsDesabilita = mysql_query($queryDesabilita);
	if (!$rsDesabilita) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$queryActualizaEstadoCuenta = sprintf("UPDATE te_estado_cuenta SET desincorporado = '%s' WHERE tipo_documento = '%s' AND id_documento = '%s'", 0, 'DP',$id);
	$rsActualizaEstadoCuenta = mysql_query($queryActualizaEstadoCuenta);
	if (!$rsActualizaEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$queryActualiza = sprintf("UPDATE cuentas SET saldo_tem = '%s' WHERE idCuentas = '%s'", $saldoNuevo, $rowDeposito['id_numero_cuenta']);
	$rsActualiza = mysql_query($queryActualiza);
	if (!$rsActualiza) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$objResponse -> alert("El Deposito ha Sido Eliminada con Exito");
	
	$objResponse->script("document.getElementById('divFlotante').style.display = 'none';");
	
	$objResponse->script("xajax_listadoDeposito(0,'','','' + '|' + -1 + '|' + 0 + '|' + '');");
	
	mysql_query("COMMIT;");
	
	return $objResponse;	
	
}

function buscarMotivo($frmBuscarMotivo){
    $objResponse = new xajaxResponse;
    
    $objResponse->script(sprintf("xajax_listMotivo(0,'','','%s')",
                                $frmBuscarMotivo['txtCriterioBuscarMotivo']));    
    return $objResponse;
}

function listMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
        if ($valCadBusq[0] != ""){
            $criterio = sprintf("AND descripcion LIKE %s ",                                                
                            valTpDato("%".$valCadBusq[0]."%","text")
                            );
        }
	
	$query = sprintf("SELECT * FROM pg_motivo WHERE modulo = 'TE' AND ingreso_egreso = 'I' %s",
                        $criterio);

        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	        
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
        if(!$rsLimit) { return $objResponse->alert(mysql_error()."\n\n".__LINE__); }
		
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
                if(!$rs) { return $objResponse->alert(mysql_error()."\n\n".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";		
        
        $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width='5%'></td>";
		$htmlTh .= ordenarCampo("xajax_listMotivo", "15%", $pageNum, "id_motivo", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listMotivo", "40%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");		
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
                
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarMotivo('".$row['id_motivo']."');\" title=\"Seleccionar Motivo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listMotivo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdListSelMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);        
        $objResponse->script("document.getElementById('divFlotante4').style.display = '';
                              centrarDiv(document.getElementById('divFlotante4'))");
        
	return $objResponse;
}

function asignarMotivo($idMotivo){
    $objResponse = new xajaxResponse();

    $query = sprintf("SELECT id_motivo, descripcion FROM pg_motivo WHERE id_motivo = %s LIMIT 1", $idMotivo);
    $rs = mysql_query($query);
    if(!$rs) { return $objResponse->alert(mysql_error()."\n\n".__LINE__); }

    $row = mysql_fetch_assoc($rs);

    $objResponse -> assign("selMotivo","value",$row["id_motivo"]);
    $objResponse -> assign("txtSelMotivo","value",$row["descripcion"]);
    $objResponse->script("document.getElementById('divFlotante4').style.display = 'none';");

    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarDeposito");
$xajax->register(XAJAX_FUNCTION,"listadoDeposito");
$xajax->register(XAJAX_FUNCTION,"nuevoDeposito");
$xajax->register(XAJAX_FUNCTION,"listBanco");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"listBanco1");
$xajax->register(XAJAX_FUNCTION,"asignarBanco1");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"comboEmpresa");
$xajax->register(XAJAX_FUNCTION,"comboEstado");
//$xajax->register(XAJAX_FUNCTION,"comboMotivo");//cerrado
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"formAgregarCheques");
$xajax->register(XAJAX_FUNCTION,"cargaSaldoCuenta");
$xajax->register(XAJAX_FUNCTION,"insertarCheques");
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentes");
$xajax->register(XAJAX_FUNCTION,"actualizarMonto");
$xajax->register(XAJAX_FUNCTION,"eliminaElementos");
$xajax->register(XAJAX_FUNCTION,"guardarDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarDatosDepositos");
$xajax->register(XAJAX_FUNCTION,"listCheques");
$xajax->register(XAJAX_FUNCTION,"eliminarDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminar");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"listMotivo");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");

function empresa($id){
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = utf8_encode($row['nombre_empresa']);
	
	return $respuesta;
}

function nombreBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['nombre'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['nombre_beneficiario'];
	}
	return $respuesta;
}

function ciRifBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['lrif']."-".$rowProveedor['rif'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['lci_rif']."-".$rowBeneficiario['ci_rif_beneficiario'];
	}
	return $respuesta;
}

function direccionBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['direccion'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['direccion'];
	}
	return $respuesta;
}


function emailBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['correo'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['email'];
	}
	return $respuesta;
}

function telfBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['telefono'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['telfs'];
	}
	return $respuesta;
}

function nombreBanco($id){
	
	$query = sprintf("SELECT 
						  bancos.idBanco,
						  bancos.nombreBanco,
						  cuentas.idCuentas,
						  cuentas.numeroCuentaCompania
						FROM
						  bancos
						  INNER JOIN cuentas ON (bancos.idBanco = cuentas.idBanco)
						WHERE
						  cuentas.idCuentas = '%s'",$id);
	
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = utf8_encode($row['nombreBanco']);
	
	return $respuesta;		
}

function cuenta($id){
	
	$query = sprintf("SELECT 
						  bancos.idBanco,
						  bancos.nombreBanco,
						  cuentas.idCuentas,
						  cuentas.numeroCuentaCompania
						FROM
						  bancos
						  INNER JOIN cuentas ON (bancos.idBanco = cuentas.idBanco)
						WHERE
						  cuentas.idCuentas = '%s'",$id);
	
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = $row['numeroCuentaCompania'];
	
	return $respuesta;	
	
}

function estadoNota($id){

	$query = sprintf("SELECT * FROM te_estados_principales WHERE id_estados_principales = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['id_estados_principales'] == 1)
		$respuesta = "<img src=\"../img/iconos/ico_rojo.gif\">";
	if($row['id_estados_principales'] == 2)
		$respuesta = "<img src=\"../img/iconos/ico_amarillo.gif\">";
	if($row['id_estados_principales'] == 3)
		$respuesta = "<img src=\"../img/iconos/ico_verde.gif\">";
	
	return $respuesta;
}

function Origen($id){

	if($id == 0)
		$respuesta = "<img src=\"../img/iconos/ico_tesoreria.gif\">";
	if($id == 1)
		$respuesta = "<img src=\"../img/iconos/ico_caja_vehiculo.gif\">";
	if($id == 2)
		$respuesta = "<img src=\"../img/iconos/ico_caja_rs.gif\">";
	
	return $respuesta;
}


function fecha($id){

	$query = sprintf("SELECT * FROM te_depositos WHERE id_deposito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['fecha_concialicion'] == NULL)
		$respuesta = "";
	else
		$respuesta = date("d/m/Y",strtotime($row['fecha_conciliacion']));
	
		
	return $respuesta; 

}

function fechaAplica($id){

	$query = sprintf("SELECT * FROM te_depositos WHERE id_deposito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['fecha_aplicacion'] == NULL)
		$respuesta = "";
	else
		$respuesta = date("d/m/Y",strtotime($row['fecha_aplicacion']));
	
		
	return $respuesta; 

}
?>