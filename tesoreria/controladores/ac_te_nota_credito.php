<?php

function buscarNotaDebito($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoNotaDebito(0,'id_nota_credito','DESC','%s' + '|' + '%s' + '|' + '%s' + '|' + '%s' + '|' + '%s' + '|' + '%s' + '|' + '%s');",
		$valForm['txtBusq'],
		$valForm['selEmpresa'],
		$valForm['selEstado'],
		$valForm['txtFecha'],
		$valForm['hddBePro'],
		$valForm['hddSelBePro'],
                $valForm['txtFecha1']));
	
	return $objResponse;
}

function listadoNotaDebito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_nota_credito")){
		$objResponse->assign("tdListadoNotaDebito","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" AND (te_nota_credito.id_nota_credito LIKE %s
                              OR te_nota_credito.observaciones LIKE %s)",
		valTpDato("%".$valCadBusq[0]."%", "text"),
        	valTpDato("%".$valCadBusq[0]."%", "text"));
	
	if ($valCadBusq[1] == -1)
		$sqlBusq .= " AND te_nota_credito.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
	
	else if ($valCadBusq[1] != 0)
		$sqlBusq .= " AND te_nota_credito.id_empresa = '".$valCadBusq[1]."'";
		
	if ($valCadBusq[2] == 0)
		$sqlBusq .= " AND te_nota_credito.estado_documento <> '".$valCadBusq[2]."'";
	
	else if ($valCadBusq[2] != 0)
		$sqlBusq .= " AND te_nota_credito.estado_documento = '".$valCadBusq[2]."'";

	if ($valCadBusq[3] != '' && $valCadBusq[6] != ''){
		$sqlBusq .= sprintf("AND DATE(te_nota_credito.fecha_registro) BETWEEN '%s' AND '%s' ",
                                        date("Y-m-d",strtotime($valCadBusq[3])),
                                        date("Y-m-d",strtotime($valCadBusq[6]))); 
        }
        
	if($valCadBusq[5] == '1')
		$sqlBusq .= " AND te_nota_credito.id_beneficiario_proveedor = '".$valCadBusq[4]."' AND te_nota_credito.control_beneficiario_proveedor = '".$valCadBusq[5]."'";
	
	if($valCadBusq[5] == '0')
		$sqlBusq .= " AND te_nota_credito.id_beneficiario_proveedor = '".$valCadBusq[4]."' AND te_nota_credito.control_beneficiario_proveedor = '".$valCadBusq[5]."'";
	
	$query = sprintf("SELECT 
		te_nota_credito.id_nota_credito,
		te_nota_credito.id_numero_cuenta,
		te_nota_credito.fecha_registro,
		te_nota_credito.fecha_aplicacion,
		te_nota_credito.fecha_conciliacion,
		te_nota_credito.folio_tesoreria,
		te_nota_credito.id_beneficiario_proveedor,
		te_nota_credito.observaciones,
		te_nota_credito.folio_estado_cuenta_banco,
		te_nota_credito.estado_documento,
		te_nota_credito.origen,
		te_nota_credito.id_usuario,
		te_nota_credito.monto_nota_credito,
		te_nota_credito.control_beneficiario_proveedor,
		te_nota_credito.id_empresa,
		te_nota_credito.desincorporado,
		te_nota_credito.numero_nota_credito,
		te_nota_credito.tipo_nota_credito,
		cuentas.idCuentas,
		cuentas.idBanco,
		cuentas.numeroCuentaCompania,
		bancos.idBanco,
		bancos.nombreBanco,
	 	pg_motivo.descripcion,
       		pg_motivo.id_motivo,
       		CONCAT_WS('. ', pg_motivo.id_motivo, pg_motivo.descripcion) AS motivo
	FROM
		bancos
		INNER JOIN cuentas ON (bancos.idBanco = cuentas.idBanco)
		INNER JOIN te_nota_credito ON (cuentas.idCuentas = te_nota_credito.id_numero_cuenta)
       		LEFT JOIN pg_motivo ON te_nota_credito.id_motivo = pg_motivo.id_motivo
	WHERE
		te_nota_credito.desincorporado <> '0'").$sqlBusq;

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
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
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "", $pageNum, "origen", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "8%", $pageNum, "id_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Nota Cr&eacute;dito"));
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "5%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Registro"));
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "5%", $pageNum, "fecha_aplicacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "5%", $pageNum, "fecha_conciliacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Conciliaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "15%", $pageNum, "id_numero_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "15%", $pageNum, "id_numero_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta");
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "30%", $pageNum, "observaciones", $campOrd, $tpOrd, $valBusq, $maxRows, "observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoNotaDebito", "", $pageNum, "monto_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
		
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
       		$motivo = "<br><small><b>".utf8_encode($row['motivo'])."</b></small>";
               
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".estadoNota($row['estado_documento'])."</td>";
			$htmlTb .= "<td align=\"center\">".origenImg($row['origen'])."</td>";
			$htmlTb .= "<td align=\"center\">".empresa($row['id_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_nota_credito']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d/m/Y",strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".fechaAplicacion($row['id_nota_credito'])."</td>";
			$htmlTb .= "<td align=\"center\">".fecha($row['id_nota_credito'])."</td>";
			$htmlTb .= "<td align=\"center\">".nombreBanco($row['id_numero_cuenta'])."</td>";
			$htmlTb .= "<td align=\"center\">".cuenta($row['id_numero_cuenta'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['observaciones']).$motivo."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['monto_nota_credito'],'2','.',',')."</td>";
			$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"xajax_cargarDatos(".$row['id_nota_credito'].")\" src=\"../img/iconos/ico_view.png\" /></td>";
			if($row['estado_documento']==3 || $row['origen'] != 0){
                            $htmlTb .= "<td align=\"center\"><img class=\"puntero\")\" src=\"../img/iconos/ico_quitarf2.gif\" /></td>";
                        }
			if($row['estado_documento']!=3 && $row['origen'] == 0){
                            $htmlTb .= "<td align=\"center\"><img class=\"puntero\")\" src=\"../img/iconos/ico_quitarf2.gif\" /></td>";
                            //$htmlTb .= "<td align=\"center\"><img class=\"puntero\" onclick=\"xajax_eliminarNota(".$row['id_nota_credito'].")\" src=\"../img/iconos/ico_quitar.gif\" /></td>";
                        }
			$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"verVentana('reportes/te_imprimir_nc_pdf.php?id=".$row['id_nota_credito']."',1100,600);\" src=\"../img/iconos/ico_print.png\"></td>";
			// Modificado Ernesto
			$sPar = "idobject=".$row['id_nota_credito'];
				 $sPar.= "&ct=07";
				 $sPar.= "&dt=03";
				 $sPar.= "&cc=05";
			// Modificado Ernesto
			$htmlTb .= "<td  align=\"center\">";
				$htmlTb .= "<img style=\"cursor:pointer;\" onclick=\"verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?$sPar', 1000, 500);\" src=\"../img/iconos/new_window.png\" title=\"Ver Movimiento Contable\"/>";
			$htmlTb .= "</td>";	
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoNotaDebito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoNotaDebito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
			
	return $objResponse;
}

function nuevoNotaDebito(){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_nota_credito","insertar")){
		return $objResponse;
	}

	$objResponse->script("
		document.forms['frmNotaDebito'].reset();
		document.getElementById('txtCiRifBeneficiario').className = 'inputInicial';
		document.getElementById('txtNombreBanco').className = 'inputInicial';
		document.getElementById('txtObservacionNotaDebito').className = 'inputInicial';
		document.getElementById('txtImporteMovimiento').className = 'inputInicial';");

	$objResponse->script("document.getElementById('divFlotante').style.display = '';
						  document.getElementById('divFlotante2').style.display = 'none';
					      document.getElementById('tblBanco').style.display = '';
						  document.getElementById('tblConsulta').style.display = 'none';
						  document.getElementById('tdFlotanteTitulo').innerHTML = 'Nueva Nota de Credito';
						  centrarDiv(document.getElementById('divFlotante'))");
	
	 
	$objResponse->script("
		document.forms['frmNotaDebito'].reset();");	
	
	$fecha = date("d-m-Y");
	$estado = "Por Aplicar";
	
	$objResponse->assign("txtFechaRegistro","value",$fecha);
	$objResponse->assign("txtFechaAplicacion","value",$fecha);
	$objResponse->assign("txtEstadoNotaDebito","value",$estado);
	
	$html ="<hr><td align=\"right\">";		
	$html .= "<input type=\"button\" value=\"Guardar\" id=\"btnGuardar\" onclick=\"this.disabled = true; validarFormInsertar();\">";
	$html .= "<input type=\"button\" value=\"Cancelar\" onclick=\"document.getElementById('divFlotante').style.display='none';\">";
	$html .="</td>";
	
	$objResponse->assign("tdNotaDebitoBotones","innerHTML",$html);
	$objResponse->script("xajax_asignarEmpresa(1);");
	$objResponse->script("mostrarTarjetas();");
	
	return $objResponse;
}

function listBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT bancos.idBanco, bancos.nombreBanco, bancos.sucursal FROM bancos INNER JOIN cuentas ON (cuentas.idBanco = bancos.idBanco) WHERE bancos.idBanco != '1' GROUP BY bancos.idBanco";
	$rsBanco = mysql_query($queryBanco);
        if(!$rsBanco) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitBanco = sprintf(" %s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);
	$rsLimitBanco = mysql_query($queryLimitBanco);
        if(!$rsLimitBanco) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco);
                if(!$rsBanco) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsBanco);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";

	$htmlTh .= "<tr class=\"tituloColumna\">";
                $htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
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
		
		$objResponse->assign("tdDescripcionArticulo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
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
	$rs = mysql_query($query);
        if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("txtTelefonoBanco","value",$row['telf']);
	$objResponse->assign("txtEmailBanco","value",$row['email']);
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmNotaDebito'))");
	
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
	$rsCuentas = mysql_query($queryCuentas);
        if(!$rsCuentas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"selCuenta\" name=\"selCuenta\" ".$disabled." onchange=\"xajax_cargaSaldoCuenta(this.value)\">";
			$html .= "<option value=\"-1\">Seleccione</option>";
		while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
			$html .= "<option value=\"".$rowCuentas['idCuentas']."\">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuentas","innerHTML",$html);
	
		
	return $objResponse;
}

function guardarNotaDebito($valForm){
	$objResponse = new xajaxResponse();

	$objResponse->script("desbloquearGuardado();");
        
	mysql_query("START TRANSACTION;");
	
	$queryFolio = sprintf("SELECT * FROM te_folios WHERE id_folios = '3'");
	$rsFolio = mysql_query($queryFolio);
	if (!$rsFolio) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowFolio = mysql_fetch_array($rsFolio);
	
	$querySaldoCuenta = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valForm['selCuenta']);
	$rsSaldoCuenta = mysql_query($querySaldoCuenta);
	if (!$rsSaldoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowSaldoCuenta = mysql_fetch_array($rsSaldoCuenta);
	
	$restoCuenta = $rowSaldoCuenta['saldo_tem'] + $valForm['txtImporteMovimiento'];
	
	$numeroFolio = $rowFolio['numero_actual'];	
	$numeroFolioNuevo = $rowFolio['numero_actual'] + 1;
	$queryFoliosUpdate = sprintf("UPDATE te_folios SET numero_actual = '%s' WHERE id_folios = '3'",$numeroFolioNuevo);		
	$rsFoliosUpdate = mysql_query($queryFoliosUpdate);
	if (!$rsFoliosUpdate) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$queryCuentaActualiza = sprintf("UPDATE cuentas SET saldo_tem = '%s' WHERE idCuentas = '%s'", $restoCuenta, $valForm['selCuenta']);
	$rsCuentaActualiza = mysql_query($queryCuentaActualiza);
	if (!$rsCuentaActualiza) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);/*afregar  a  deposito*/

	$queryNotaDebito = sprintf ("INSERT INTO te_nota_credito(id_numero_cuenta, fecha_registro, folio_tesoreria, id_beneficiario_proveedor, observaciones, fecha_aplicacion, estado_documento, origen, id_usuario, monto_nota_credito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_credito,tipo_nota_credito,id_motivo, monto_original_nota_credito, porcentaje_comision, porcentaje_islr)
	VALUES ('%s', '%s', '%s','%s', '%s',  NOW(), '%s', '%s', '%s', '%s','%s','%s','%s','%s','%s','%s', '%s', '%s', '%s')",//NULL NOW	
			$valForm['selCuenta'],
			date("Y-m-d",strtotime($valForm['txtFechaRegistro'])),
			$rowFolio['numero_actual'],
			NULL,
			$valForm['txtObservacionNotaDebito'],
			2,
			0,//$valForm['selOrigen'], //0 tesoreria
			$_SESSION['idUsuarioSysGts'],
			$valForm['txtImporteMovimiento'],
			NULL,
			$valForm['hddIdEmpresa'],
			1,
			$valForm['txtNumeroDocumento'],
			$valForm['selTipoNotaCredito'],
			$valForm['selMotivo'],
			$valForm['montoBase'],
			$valForm['porcentajeComision'],
			$valForm['porcentajeRetencion']);
				
	$consultaNotaDebito = mysql_query($queryNotaDebito);
	if (!$consultaNotaDebito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$idNotaDebito = mysql_insert_id();
		
	$queryEstadoCuenta = sprintf ( "INSERT INTO te_estado_cuenta ( tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales)
	VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			'NC',
			$idNotaDebito,
			date("Y-m-d h:i:s",strtotime($valForm['txtFechaRegistro'])),
			$valForm['selCuenta'],
			$valForm['hddIdEmpresa'],
			$valForm['txtImporteMovimiento'],
			'1',
			$valForm['txtNumeroDocumento'],
			'1',
			$valForm['txtObservacionNotaDebito'],
			'2');
	
        $rsEstadoCuenta = mysql_query($queryEstadoCuenta);
        if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryEstadoCuenta);

	$objResponse->alert("Los Datos se han Guardado Correctamente");		
	$objResponse->script("document.getElementById('divFlotante').style.display = 'none'");		
	$objResponse->script("xajax_listadoNotaDebito();");
		
	mysql_query("COMMIT;");
		
	//Modifcar Ernesto
	if(function_exists("generarNotaCreditoTe")){
	   generarNotaCreditoTe($idNotaDebito,"","");
	}
	//Modifcar Ernesto

	return $objResponse;
}

function cargaSaldoCuenta($id_cuenta){
	$objResponse = new xajaxResponse();

	$queryCuenta = sprintf("SELECT * FROM cuentas WHERE  idCuentas = '%s'",$id_cuenta);
	$rsCuenta = mysql_query($queryCuenta);
        if(!$rsCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuenta = mysql_fetch_array($rsCuenta);
	
	$objResponse->assign("txtSaldoCuenta","value",number_format($rowCuenta['saldo_tem'],'2','.',','));
	
	return $objResponse;
}

function cargarDatos($id){
	$objResponse = new xajaxResponse();
	
	$htmlx .= "<table border=\"0\" id=\"Conulta\" width=\"100%\">";
        $htmlx .= "<tr>";
        	$htmlx .= "<td>";
        	$htmlx .= "<fieldset><legend><span style=\"color:#990000\">Datos Bancos</span></legend>";
           $htmlx .= " <table>";
                $htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Banco:</td>";
                    $htmlx .= "<td colspan=\"3\" align=\"left\">";
                        $htmlx .= "<table cellpadding=\"0\" cellspacing=\"0\">";
                            $htmlx .= "<tr>";
                                $htmlx .= "<td><input type=\"text\" id=\"txtNombreBancoConsulta\" name=\"txtNombreBancoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
                           $htmlx .= "</tr>";
                        $htmlx .= "</table>";
        			$htmlx .= "</td>";
        		$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Cuentas:</td>";
                    $htmlx .= "<td colspan=\"3\" id=\"tdSelCuentas\"><input type=\"text\" id=\"txtCuentasConsulta\" name=\"txtCuentasConsulta\" size=\"25\" readonly=\"readonly\" style=\"text-align:right\"/></td>";
        		$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Telefono Banco:</td>";
                    $htmlx .= "<td><input type=\"text\" id=\"txtTelefonoBancoConsulta\" name=\"txtTelefonoBancoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Email Banco:</td>";
                    $htmlx .= "<td><input type=\"text\" id=\"txtEmailBancoConsulta\" name=\"txtEmailBancoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
        		$htmlx .= "</tr>";
        	$htmlx .= "</table>";
            $htmlx .= "</fieldset>";
            $htmlx .= "<fieldset><legend><span style=\"color:#990000\">Datos Nota Debito</span></legend>";
        	$htmlx .= "<table>";
				$htmlx .= "<tr>";
					$htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Empresa:</td>";
					$htmlx .= "<td colspan=\"3\"><input type=\"text\" id=\"txtEmpresaConsulta\" name=\"txtEmpresaConsulta\" size=\"50\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
            	$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Fecha Registro:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtFechaRegistroConsulta\" name=\"txtFechaRegistroConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
                $htmlx .= "<tr>";    
					$htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Fecha Aplicacion:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtFechaAplicacionConsulta\" name=\"txtFechaAplicacionConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
				$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Fecha Conciliacion:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtFechaConciliacionConsulta\" name=\"txtFechaConciliacionConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
				$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Numero Nota Credito:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtNumeroDocumentoConsulta\" name=\"txtNumeroDocumentoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
				$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Tipo Nota Credito:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtTipoNotaCreditoDocumentoConsulta\" name=\"txtTipoNotaCreditoDocumentoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
				$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Motivo:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtMotivoDocumentoConsulta\" name=\"txtMotivoDocumentoConsulta\" size=\"50\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
				$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Origen:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtOrigenDocumentoConsulta\" name=\"txtOrigenDocumentoConsulta\" size=\"50\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Observacion:</td>";
                    $htmlx .= "<td colspan=\"3\"><textarea name=\"txtObservacionNotaDebitoConsulta\" cols=\"72\" rows=\"2\" id=\"txtObservacionNotaDebitoConsulta\" readonly=\"readonly\"></textarea></td>";
        		$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Estado:</td>";
                    $htmlx .= "<td><input type=\"text\" id=\"txtEstadoNotaDebitoConsulta\" name=\"txtEstadoNotaDebitoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
        		$htmlx .= "</tr>";
                $htmlx .= "<tr>";
                	$htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Importe de Movimiento:</td>";
                    $htmlx .= "<td><input type=\"text\" id=\"txtImporteMovimientoConsulta\" name=\"txtImporteMovimientoConsulta\"  style=\"text-align:right\" size=\"25\" onkeypress=\"return validarSoloNumerosReales(event);\" readonly=\"readonly\"/></td>";
        		$htmlx .= "</tr>";
           $htmlx .= "</table>";
            $htmlx .= "</fieldset>";
        $htmlx .= "</td>";
    $htmlx .= "</tr>";
    $htmlx .= "<tr>";
        $htmlx .= "<td align=\"right\" id=\"tdNotaDebitoBotones\"><hr><input type=\"button\" onclick=\"document.getElementById('divFlotante').style.display='none';\" value=\"Cancelar\"></td>";
		$htmlx .= "</fieldset>";
    $htmlx .= "</tr>";
    $htmlx .= "</table>";
	
	$objResponse -> assign("tdConsultaNotas","innerHTML",$htmlx);
	
	$queryConsulta = sprintf("SELECT 
								  te_nota_credito.id_nota_credito,
								  te_nota_credito.id_numero_cuenta,
								  te_nota_credito.fecha_registro,
								  te_nota_credito.fecha_aplicacion,
								  te_nota_credito.fecha_conciliacion,
								  te_nota_credito.folio_tesoreria,
								  te_nota_credito.id_beneficiario_proveedor,
								  te_nota_credito.observaciones,
								  te_nota_credito.folio_estado_cuenta_banco,
								  te_nota_credito.estado_documento,
								  te_nota_credito.origen,
								  te_nota_credito.id_usuario,
								  te_nota_credito.monto_nota_credito,
								  te_nota_credito.control_beneficiario_proveedor,
								  te_nota_credito.id_empresa,
								  te_nota_credito.desincorporado,
								  te_nota_credito.numero_nota_credito,
								  te_nota_credito.tipo_nota_credito,
								  te_nota_credito.id_motivo,
								  cuentas.idCuentas,
								  cuentas.id_empresa,
								  cuentas.numeroCuentaCompania,
								  bancos.idBanco,
								  bancos.nombreBanco,
								  cuentas.saldo_tem,
								  bancos.telf,
								  bancos.email,
								  vw_iv_empresas_sucursales.nombre_empresa,
								  te_estados_principales.id_estados_principales,
								  te_estados_principales.descripcion
								FROM
								  te_nota_credito
								  INNER JOIN cuentas ON (te_nota_credito.id_numero_cuenta = cuentas.idCuentas)
								  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
								  INNER JOIN vw_iv_empresas_sucursales ON (cuentas.id_empresa = vw_iv_empresas_sucursales.id_empresa_reg)
								  INNER JOIN te_estados_principales ON (te_nota_credito.estado_documento = te_estados_principales.id_estados_principales)
								WHERE
								  te_nota_credito.id_nota_credito ='%s'",$id);

	$rsConsulta = mysql_query($queryConsulta);
        if(!$rsConsulta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowConsulta = mysql_fetch_array($rsConsulta);

	$objResponse->assign("txtNombreBancoConsulta","value",utf8_encode($rowConsulta['nombreBanco']));
	$objResponse->assign("txtTipoNotaCreditoDocumentoConsulta","value",tipoNotaCredito($rowConsulta['tipo_nota_credito']));/**/
	$objResponse->assign("txtCuentasConsulta","value",$rowConsulta['numeroCuentaCompania']);
	//$objResponse->assign("txtSaldoCuentaConsulta","value",number_format($rowConsulta['saldo_tem'],'2','.',','));
	$objResponse->assign("txtTelefonoBancoConsulta","value",$rowConsulta['telf']);
	$objResponse->assign("txtEmailBancoConsulta","value",$rowConsulta['email']);
	$objResponse->assign("txtFechaRegistroConsulta","value",date("d/m/Y",strtotime($rowConsulta['fecha_registro'])));
	$objResponse->assign("txtFechaAplicacionConsulta","value",fechaAplicacion($rowConsulta['id_nota_credito']));
	$objResponse->assign("txtFechaConciliacionConsulta","value",fecha($rowConsulta['id_nota_credito']));
	$objResponse->assign("txtEmpresaConsulta","value",utf8_encode($rowConsulta['nombre_empresa']));
	$objResponse->assign("txtObservacionNotaDebitoConsulta","value",$rowConsulta['observaciones']);
	$objResponse->assign("txtEstadoNotaDebitoConsulta","value",$rowConsulta['descripcion']);
	$objResponse->assign("txtImporteMovimientoConsulta","value",number_format($rowConsulta['monto_nota_credito'],'2','.',','));
	$objResponse->assign("txtNumeroDocumentoConsulta","value",$rowConsulta['numero_nota_credito']);

	$objResponse->assign("txtMotivoDocumentoConsulta","value",$rowConsulta['id_motivo']." - ".motivo($rowConsulta['id_motivo']));
	$objResponse->assign("txtOrigenDocumentoConsulta","value",origen($rowConsulta['origen']));
	$objResponse->script("document.getElementById('divFlotante').style.display = '';
								  document.getElementById('tblBanco').style.display = 'none';
								  document.getElementById('tblConsulta').style.display = '';
								  document.getElementById('tdFlotanteTitulo').innerHTML = 'Consultar Nota de Credito';
								  centrarDiv(document.getElementById('divFlotante'))");	
	
	
	return $objResponse;
}

function eliminarNota($id){
	$objResponse = new xajaxResponse();
	
	$htmlx .= "<table border=\"0\" id=\"Conulta\" width=\"100%\">";
        $htmlx .= "<tr>";
        	$htmlx .= "<td>";
        		/*$htmlx .= "<fieldset><legend><span style=\"color:#990000\">Datos Proveedor o Beneficiario</span></legend>";
        		$htmlx .= "<table border=\"0\" id=\"tblVerAlmacen\">";
					$htmlx .= "<tr>";
						$htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Beneficiario o Proveedor:</td>";        				
						$htmlx .= "<td colspan=\"3\" align=\"left\">";
        					$htmlx .= "<table cellspacing=\"0\" cellpadding=\"0\">";
								$htmlx .= "<tr>";
        							$htmlx .= "<td><input type=\"text\" id=\"txtCiRifBeneficiarioConsulta\" name=\"txtCiRifBeneficiarioConsulta\" size=\"25\" readonly=\"readonly\"/><input type=\"hidden\" id=\"hddIdBeneficiarioConsulta\" name=\"hddIdBeneficiarioConsulta\"/><input type=\"hidden\" id=\"hddBeneficiario_O_Provedor\" name=\"hddBeneficiario_O_Provedor\"/></td>";
        						$htmlx .= "</tr>";
       					 	$htmlx .= "</table>";
        				$htmlx .= "</td>";
        			$htmlx .= "</tr>";
                    $htmlx .= "<tr>";
                        $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Nombre:</td>";
                        $htmlx .= "<td colspan=\"3\"><input type=\"text\" id=\"txtNombreBeneficiarioConsulta\" name=\"txtNombreBeneficiarioConsulta\" size=\"50\" readonly=\"readonly\"/></td>";
                    $htmlx .= "</tr>";
                    $htmlx .= "<tr>";
                        $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Telefono:</td>";
                        $htmlx .= "<td><input type=\"text\" id=\"txtTelefonoBeneficiarioConsulta\" name=\"txtTelefonoBeneficiarioConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
                        $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Email:</td>";
                        $htmlx .= "<td><input type=\"text\" id=\"txtEmailBeneficiarioConsulta\" name=\"txtEmailBeneficiarioConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
                    $htmlx .= "</tr>";
			        $htmlx .= "<tr>";
                       $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Direccion:</td>";
                        $htmlx .= "<td colspan=\"3\"><textarea name=\"textDireccionBeneficiarioConsulta\" cols=\"72\" rows=\"2\" id=\"textDireccionBeneficiarioConsulta\" readonly=\"readonly\"></textarea></td>";
                    $htmlx .= "</tr>";
        	$htmlx .= "</table>";
        	$htmlx .= "</fieldset>";*/
        	$htmlx .= "<fieldset><legend><span style=\"color:#990000\">Datos Bancos</span></legend>";
           $htmlx .= " <table>";
                $htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Banco:</td>";
                    $htmlx .= "<td colspan=\"3\" align=\"left\">";
                        $htmlx .= "<table cellpadding=\"0\" cellspacing=\"0\">";
                            $htmlx .= "<tr>";
                                $htmlx .= "<td><input type=\"text\" id=\"txtNombreBancoConsulta\" name=\"txtNombreBancoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
                           $htmlx .= "</tr>";
                        $htmlx .= "</table>";
        			$htmlx .= "</td>";
        		$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Cuentas:</td>";
                    $htmlx .= "<td colspan=\"3\" id=\"tdSelCuentas\"><input type=\"text\" id=\"txtCuentasConsulta\" name=\"txtCuentasConsulta\" size=\"25\" readonly=\"readonly\" style=\"text-align:right\"/></td>";
        		$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Telefono Banco:</td>";
                    $htmlx .= "<td><input type=\"text\" id=\"txtTelefonoBancoConsulta\" name=\"txtTelefonoBancoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Email Banco:</td>";
                    $htmlx .= "<td><input type=\"text\" id=\"txtEmailBancoConsulta\" name=\"txtEmailBancoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
        		$htmlx .= "</tr>";
        	$htmlx .= "</table>";
            $htmlx .= "</fieldset>";
            $htmlx .= "<fieldset><legend><span style=\"color:#990000\">Datos Nota Credito</span></legend>";
        	$htmlx .= "<table>";
				$htmlx .= "<tr>";
					$htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Empresa:</td>";
					$htmlx .= "<td colspan=\"3\"><input type=\"text\" id=\"txtEmpresaConsulta\" name=\"txtEmpresaConsulta\" size=\"50\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
            	$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Fecha Registro:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtFechaRegistroConsulta\" name=\"txtFechaRegistroConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
                $htmlx .= "<tr>";    
					$htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Fecha Aplicacion:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtFechaAplicacionConsulta\" name=\"txtFechaAplicacionConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
				$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Fecha Conciliacion:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtFechaConciliacionConsulta\" name=\"txtFechaConciliacionConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
				$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Numero Nota Credito:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtNumeroDocumentoConsulta\" name=\"txtNumeroDocumentoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
				$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Tipo Nota Credito:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtTipoNotaCreditoDocumentoConsulta\" name=\"txtTipoNotaCreditoDocumentoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Motivo:</td>";
                    $htmlx .= "<td align=\"left\"><input type=\"text\" id=\"txtMotivoDocumentoConsulta\" name=\"txtMotivoCreditoDocumentoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
				$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Observacion:</td>";
                    $htmlx .= "<td colspan=\"3\"><textarea name=\"txtObservacionNotaDebitoConsulta\" cols=\"72\" rows=\"2\" id=\"txtObservacionNotaDebitoConsulta\" readonly=\"readonly\"></textarea></td>";
        		$htmlx .= "</tr>";
        		$htmlx .= "<tr>";
                    $htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Estado:</td>";
                    $htmlx .= "<td><input type=\"text\" id=\"txtEstadoNotaDebitoConsulta\" name=\"txtEstadoNotaDebitoConsulta\" size=\"25\" readonly=\"readonly\"/></td>";
        		$htmlx .= "</tr>";
                $htmlx .= "<tr>";
                	$htmlx .= "<td align=\"right\" class=\"tituloCampo\" width=\"120\">Importe de Movimiento:</td>";
                    $htmlx .= "<td><input type=\"text\" id=\"txtImporteMovimientoConsulta\" name=\"txtImporteMovimientoConsulta\"  style=\"text-align:right\" size=\"25\" onkeypress=\"return validarSoloNumerosReales(event);\" readonly=\"readonly\"/></td>";
        		$htmlx .= "</tr>";
           $htmlx .= "</table>";
            $htmlx .= "</fieldset>";
        $htmlx .= "</td>";
    $htmlx .= "</tr>";
    $htmlx .= "<tr>";
        $htmlx .= "<td align=\"right\"><hr><input type=\"button\" onclick=\"xajax_desabilitarNota($id);\" value=\"Eliminar\"><input type=\"button\" onclick=\"document.getElementById('divFlotante').style.display='none';\" value=\"Cancelar\"></td>";
    $htmlx .= "</tr>";
    $htmlx .= "</table>";
	
	$objResponse -> assign("tdConsultaNotas","innerHTML",$htmlx);
	
	$queryConsulta = sprintf("SELECT 
								  te_nota_credito.id_nota_credito,
								  te_nota_credito.id_numero_cuenta,
								  te_nota_credito.fecha_registro,
								  te_nota_credito.fecha_aplicacion,
								  te_nota_credito.fecha_conciliacion,
								  te_nota_credito.folio_tesoreria,
								  te_nota_credito.id_beneficiario_proveedor,
								  te_nota_credito.observaciones,
								  te_nota_credito.folio_estado_cuenta_banco,
								  te_nota_credito.estado_documento,
								  te_nota_credito.origen,
								  te_nota_credito.id_usuario,
								  te_nota_credito.monto_nota_credito,
								  te_nota_credito.control_beneficiario_proveedor,
								  te_nota_credito.id_empresa,
								  te_nota_credito.desincorporado,
								  te_nota_credito.numero_nota_credito,
								  te_nota_credito.tipo_nota_credito,
								  te_nota_credito.id_motivo,
								  cuentas.idCuentas,
								  cuentas.id_empresa,
								  cuentas.numeroCuentaCompania,
								  bancos.idBanco,
								  bancos.nombreBanco,
								  cuentas.saldo_tem,
								  bancos.telf,
								  bancos.email,
								  vw_iv_empresas_sucursales.nombre_empresa,
								  te_estados_principales.id_estados_principales,
								  te_estados_principales.descripcion
								FROM
								  te_nota_credito
								  INNER JOIN cuentas ON (te_nota_credito.id_numero_cuenta = cuentas.idCuentas)
								  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
								  INNER JOIN vw_iv_empresas_sucursales ON (cuentas.id_empresa = vw_iv_empresas_sucursales.id_empresa_reg)
								  INNER JOIN te_estados_principales ON (te_nota_credito.estado_documento = te_estados_principales.id_estados_principales)
								WHERE
								  te_nota_credito.id_nota_credito ='%s'",$id);
	$rsConsulta = mysql_query($queryConsulta);
        if(!$rsConsulta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowConsulta = mysql_fetch_array($rsConsulta);
	
	//$objResponse->assign("txtCiRifBeneficiarioConsulta","value",ciRifBp($rowConsulta['id_nota_credito']));
	//$objResponse->assign("txtNombreBeneficiarioConsulta","value",nombreBp($rowConsulta['id_nota_credito']));
	//$objResponse->assign("txtTelefonoBeneficiarioConsulta","value",telfBp($rowConsulta['id_nota_credito']));
	//$objResponse->assign("txtEmailBeneficiarioConsulta","value",emailBp($rowConsulta['id_nota_credito']));
	//$objResponse->assign("textDireccionBeneficiarioConsulta","value",direccionBp($rowConsulta['id_nota_credito']));
	$objResponse->assign("txtNombreBancoConsulta","value",  utf8_encode($rowConsulta['nombreBanco']));
	$objResponse->assign("txtTipoNotaCreditoDocumentoConsulta","value",tipoNotaCredito($rowConsulta['tipo_nota_credito']));
	$objResponse->assign("txtCuentasConsulta","value",$rowConsulta['numeroCuentaCompania']);
	//$objResponse->assign("txtSaldoCuentaConsulta","value",number_format($rowConsulta['saldo_tem'],'2','.',','));
	$objResponse->assign("txtTelefonoBancoConsulta","value",$rowConsulta['telf']);
	$objResponse->assign("txtEmailBancoConsulta","value",$rowConsulta['email']);
	$objResponse->assign("txtFechaRegistroConsulta","value",date("d/m/Y",strtotime($rowConsulta['fecha_registro'])));
	$objResponse->assign("txtFechaAplicacionConsulta","value",fechaAplicacion($rowConsulta['id_nota_credito']));
	$objResponse->assign("txtFechaConciliacionConsulta","value",fecha($rowConsulta['id_nota_credito']));
	$objResponse->assign("txtEmpresaConsulta","value",  utf8_encode($rowConsulta['nombre_empresa']));
	$objResponse->assign("txtObservacionNotaDebitoConsulta","value",$rowConsulta['observaciones']);
	$objResponse->assign("txtEstadoNotaDebitoConsulta","value",$rowConsulta['descripcion']);
	$objResponse->assign("txtImporteMovimientoConsulta","value",number_format($rowConsulta['monto_nota_credito'],'2','.',','));
	$objResponse->assign("txtNumeroDocumentoConsulta","value",$rowConsulta['numero_nota_credito']);
	
	$objResponse->assign("txtMotivoDocumentoConsulta","value",$rowConsulta['id_motivo']." -- ".motivo($rowConsulta['id_motivo']));
	
	$objResponse->script("document.getElementById('divFlotante').style.display = '';
								  document.getElementById('tblBanco').style.display = 'none';
								  document.getElementById('tblConsulta').style.display = '';
								  document.getElementById('tdFlotanteTitulo').innerHTML = 'Eliminar Nota de Credito';
								  centrarDiv(document.getElementById('divFlotante'))");	
	
	
	return $objResponse;
}
	
function desabilitarNota($id){
	$objResponse = new xajaxResponse();

        if (!xvalidaAcceso($objResponse,"te_nota_credito","eliminar")){
		return $objResponse;
	}

	mysql_query("START TRANSACTION;");
	
	$queryNota = sprintf("SELECT 
							  cuentas.saldo_tem,
							  te_nota_credito.id_nota_credito,
							  te_nota_credito.id_numero_cuenta,
							  te_nota_credito.fecha_registro,
							  te_nota_credito.folio_tesoreria,
							  te_nota_credito.id_beneficiario_proveedor,
							  te_nota_credito.observaciones,
							  te_nota_credito.fecha_aplicacion,
							  te_nota_credito.fecha_conciliacion,
							  te_nota_credito.folio_estado_cuenta_banco,
							  te_nota_credito.estado_documento,
							  te_nota_credito.origen,
							  te_nota_credito.id_usuario,
							  te_nota_credito.monto_nota_credito,
							  te_nota_credito.control_beneficiario_proveedor,
							  te_nota_credito.id_empresa,
							  te_nota_credito.desincorporado
							FROM
							  te_nota_credito
							  INNER JOIN cuentas ON (te_nota_credito.id_numero_cuenta = cuentas.idCuentas)
							WHERE
							  te_nota_credito.id_nota_credito = '%s'",$id);
	$rsNota = mysql_query($queryNota);
        if(!$rsNota) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowNota = mysql_fetch_array($rsNota);

	 $saldoNuevo = $rowNota['saldo_tem'] - $rowNota['monto_nota_credito'];
	
	$queryDesabilita = sprintf("UPDATE te_nota_credito SET desincorporado = '%s' WHERE id_nota_credito = '%s'", 0,$id);
	$rsDesabilita = mysql_query($queryDesabilita);
	if (!$rsDesabilita) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryActualiza = sprintf("UPDATE cuentas SET saldo_tem = '%s' WHERE idCuentas = '%s'", $saldoNuevo, $rowNota['id_numero_cuenta']);
	$rsActualiza = mysql_query($queryActualiza);
	if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryActualizaEstadoCuenta = sprintf("UPDATE te_estado_cuenta SET desincorporado = '%s' WHERE tipo_documento = '%s' AND id_documento = '%s'", 0, 'NC',$id);
	$rsActualizaEstadoCuenta = mysql_query($queryActualizaEstadoCuenta);
	if (!$rsActualizaEstadoCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$objResponse -> alert("La Nota de Credito ha Sido Eliminada con Exito");
	
	$objResponse->script("document.getElementById('divFlotante').style.display = 'none';");
	$objResponse->script("xajax_listadoNotaDebito();");
	
	mysql_query("COMMIT;");
	
	return $objResponse;	
}

function comboEmpresa($valForm){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY id_empresa_reg",$_SESSION['idUsuarioSysGts']);
		$rs = mysql_query($query);
                if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$html = "<select id=\"selEmpresa\" name=\"selEmpresa\" onChange=\"xajax_buscarNotaDebito(xajax.getFormValues('frmBuscar'))\">";
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
		$rs = mysql_query($query);
                if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$html = "<select id=\"selEstado\" name=\"selEstado\" onChange=\"xajax_buscarNotaDebito(xajax.getFormValues('frmBuscar'))\">";
		$html .="<option selected=\"selected\" value=\"0\">[ Todos ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = "selected='selected'";
			$html .= "<option value=\"".$row['id_estados_principales']."\">".htmlentities($row['descripcion'].$nombreSucursal)."</option>";
		}
		$html .= "</select>";
	
		$objResponse->assign("tdSelEstado","innerHTML",$html);
	
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
		
		$objResponse->assign("tdDescripcionArticulo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
		$objResponse->script("document.getElementById('divFlotante2').style.display = '';
								  document.getElementById('tblListados2').style.display = '';
								  document.getElementById('txtNombreBanco').value = '';
								  document.getElementById('txtSaldoCuenta').value = '';
								  document.getElementById('txtTelefonoBanco').value = '';
								  document.getElementById('txtEmailBanco').value = '';
								  document.getElementById('tdFlotanteTitulo2').innerHTML = 'Seleccione Empresa';
								  centrarDiv(document.getElementById('divFlotante2'))");	
	return $objResponse;
}

function asignarEmpresa($idEmpresa){
	$objResponse = new xajaxResponse();
	
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
		$rsEmpresa = mysql_query($queryEmpresa);
                if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
		$nombreSucursal = "";
		
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
		
		$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
		
		$objResponse->assign("txtNombreEmpresa","value",$empresa);
		$objResponse->assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
		$objResponse->script("document.getElementById('divFlotante2').style.display = 'none';");
	
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

function cargaLstTarjetaCuenta($idCuenta, $tipoPago, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 3) { // Tarjeta de Credito
		$query = sprintf("SELECT idTipoTarjetaCredito, descripcionTipoTarjetaCredito FROM tipotarjetacredito 
		WHERE idTipoTarjetaCredito IN (SELECT id_tipo_tarjeta FROM te_retencion_punto
										WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta NOT IN (6))
		ORDER BY descripcionTipoTarjetaCredito",
			valTpDato($idCuenta, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select id=\"tarjeta\" name=\"tarjeta\" onchange=\"xajax_asignarPorcentajeTarjetaCredito(".$idCuenta.",this.value)\" style=\"width:120px\">";
			$html .= "<option value=\"\">[ Seleccione ]</option>";
		while($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row['idTipoTarjetaCredito'] || $totalRows == 1) ? "selected=\"selected\"" : "";
			if ($totalRows == 1) { $objResponse->loadCommands(asignarPorcentajeTarjetaCredito($idCuenta, $row["idTipoTarjetaCredito"])); }
			
			$html .= "<option ".$selected." value=\"".$row['idTipoTarjetaCredito']."\">".$row['descripcionTipoTarjetaCredito']."</option>";
		}
		$html .= "</select>";
		
		
	} else if ($tipoPago == 2) { // Tarjeta de Debito
		$query = sprintf("SELECT porcentaje_comision FROM te_retencion_punto WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta IN (6);",
			valTpDato($idCuenta,'int'));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
		$objResponse->script("calcularPorcentajeTarjetaCredito();");
	}else{
	
	}
	
	$objResponse->assign("tdtarjeta","innerHTML",$html);
	
	return $objResponse;
}

function asignarPorcentajeTarjetaCredito($idCuenta, $idTarjeta) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto
	WHERE id_cuenta = %s
		AND id_tipo_tarjeta = %s",
		valTpDato($idCuenta, "int"),
		valTpDato($idTarjeta, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("porcentajeRetencion","value",$row['porcentaje_islr']);
	$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
	
	$objResponse->script("calcularPorcentajeTarjetaCredito();");
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"buscarNotaDebito");
$xajax->register(XAJAX_FUNCTION,"listadoNotaDebito");
$xajax->register(XAJAX_FUNCTION,"nuevoNotaDebito");
$xajax->register(XAJAX_FUNCTION,"listBanco");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"guardarNotaDebito");
$xajax->register(XAJAX_FUNCTION,"cargaSaldoCuenta");
$xajax->register(XAJAX_FUNCTION,"cargarDatos");
$xajax->register(XAJAX_FUNCTION,"eliminarNota");
$xajax->register(XAJAX_FUNCTION,"desabilitarNota");
$xajax->register(XAJAX_FUNCTION,"comboEmpresa");
$xajax->register(XAJAX_FUNCTION,"comboEstado");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"listMotivo");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"asignarPorcentajeTarjetaCredito");

function empresa($id){
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = utf8_encode($row['nombre_empresa']);
	
	return $respuesta;
}

function nombreBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = utf8_encode($rowProveedor['nombre']);
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = utf8_encode($rowBeneficiario['nombre_beneficiario']);
	}
	return $respuesta;
}

function ciRifBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'",$id);
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
	
	$query = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'",$id);
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
	
	$query = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'",$id);
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
	
	$query = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'",$id);
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

function fecha($id){

	$query = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['fecha_concialicion'] == NULL)
		$respuesta = "";
	else
		$respuesta = date("d/m/Y",strtotime($row['fecha_conciliacion']));
		
	return $respuesta; 

}

function fechaAplicacion($id){

	$query = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['fecha_aplicacion'] == NULL)
		$respuesta = "";
	else
		$respuesta = date("d/m/Y",strtotime($row['fecha_aplicacion']));
		
	return $respuesta; 

}

function tipoNotaCredito($id){

	if($id == 1)
		$respuesta = "Normal";
	if($id == 2)
		$respuesta = "Tarjeta de Debito";
	if($id == 3)
		$respuesta = "Tarjeta de Credito";
	if($id == 4)
		$respuesta = "Transferencia";

	return $respuesta;
}

function motivo($id){

	$query = "SELECT * FROM pg_motivo WHERE id_motivo = '".$id."'";
	$rs = mysql_query($query);
	$row = mysql_fetch_array($rs);
	$respuesta = utf8_encode($row['descripcion']);
	return $respuesta;
}


function origen($id){

	$query = "SELECT * FROM te_origen WHERE id = '".$id."'";
	$rs = mysql_query($query);
	$row = mysql_fetch_array($rs);
	$respuesta = $row['descripcion'];
	return $respuesta;
}

function origenImg($id){

	if($id == 0)
		$respuesta = "<img src=\"../img/iconos/ico_tesoreria.gif\">";
	if($id == 1)
		$respuesta = "<img src=\"../img/iconos/ico_caja_vehiculo.gif\">";
	if($id == 2)
		$respuesta = "<img src=\"../img/iconos/ico_caja_rs.gif\">";
	if($id == 3)
		$respuesta = "<img src=\"../img/iconos/ico_ingregos_bonificaciones.gif\">";
	if($id == 4)
		$respuesta = "<img src=\"../img/iconos/ico_otros_ingresos.gif\">";
	
	return $respuesta;
}

?>