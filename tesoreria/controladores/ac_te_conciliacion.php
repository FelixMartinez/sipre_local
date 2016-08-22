<?php
function buscarEstadoCuenta($valForm) {
	$objResponse = new xajaxResponse();
	
        $fecha = $valForm['txtFecha'];
	
	$objResponse->script(sprintf("xajax_listadoEstadoCuenta(0,'fecha','DESC','%s' + '|' + '%s' + '|' + '%s' + '|' + '%s');",
		$valForm['hddIdEmpresa'],
		$valForm['selCuenta'],
		$fecha,
		$valForm['selEstado']
		));
	
	//$objResponse->script(sprintf("xajax_listadoEstadoCuenta(0,'','');"));
	
	return $objResponse;
}

function listadoEstadoCuenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

        $valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] == '')
		$sqlBusq .= " AND te_conciliacion.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
	
	else if ($valCadBusq[0] != '')
		$sqlBusq .= " AND te_conciliacion.id_empresa = '".$valCadBusq[0]."'";
		
	if ($valCadBusq[1] != '0' && $valCadBusq[1] != '-1' )
		$sqlBusq .= " AND te_conciliacion.id_cuenta = '".$valCadBusq[1]."'";
	
	if ($valCadBusq[2] != '')
		$sqlBusq .= " AND DATE_FORMAT(te_conciliacion.fecha,'%Y/%m') = '".date("Y/m",strtotime('01-'.$valCadBusq[2]))."'";
		
	$query = sprintf("SELECT 
                              te_conciliacion.id_conciliacion,
                              te_conciliacion.id_cuenta,
                              te_conciliacion.id_banco,
                              te_conciliacion.monto_conciliado,
                              te_conciliacion.monto_libro,
                              te_conciliacion.id_empresa,
                              DATE_FORMAT(te_conciliacion.fecha,'%s') as fecha_registro_formato
                            FROM
                              te_conciliacion
                            WHERE
                              te_conciliacion.id_banco <> 0",'%m-%Y').$sqlBusq;

        //$objResponse -> alert("$query");
	
       
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit) or die(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "15%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "10%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Conciliaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "18%", $pageNum, "id_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "17%", $pageNum, "id_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "17%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "10%", $pageNum, "monto_conciliado", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Conciliado");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "10%", $pageNum, "monto_libro", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo en Libro");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	$conta = 0;
	$contb = 0;
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria2" : "trResaltarTesoreria1";
		$contFila++;
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".empresa($row['id_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['fecha_registro_formato']."</td>";
			$htmlTb .= "<td align=\"center\">".nombreBanco($row['id_cuenta'])."</td>";
			$htmlTb .= "<td align=\"center\">".cuenta($row['id_cuenta'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['monto_conciliado'],2,'.',',')."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['monto_libro'],2,'.',',')."</td>";
			$htmlTb .= "<td align='center' class=\"puntero\" title=\"Editar Conciliacion\">"."<img src=\"../img/iconos/pencil.png\" onclick=\"window.open('te_conciliacion_editar.php?id_con=".$row['id_conciliacion']."','_self');\"/>"."</td>";
			$htmlTb .= "<td align=\"center\" title=\"Resumen Conciliaci&oacute;n\"><img class=\"puntero\" src=\"../img/iconos/page_white_acrobat.png\" onclick=\"verVentana('reportes/conciliacion_pdf2.php?valBusq=".$row['id_empresa']."|".$row['id_cuenta']."|".$row['id_conciliacion']."',700,700);\" ></td>";
			$htmlTb .= "<td align=\"center\" title=\"Detalle\"><img class=\"puntero\" src=\"../img/iconos/pdf_ico.png\" onclick=\"verVentana('reportes/conciliacion_detalle_pdf.php?valBusq=".$row['id_empresa']."|".$row['id_cuenta']."|".$row['id_conciliacion']."',700,700);\" ></td>";
			$htmlTb .= "<td align=\"center\" title=\"Anular Conciliación\"><img class=\"puntero\" src=\"../img/iconos/ico_quitar.gif\" onclick=\"abrirAccesoAnulacion(".$row['id_conciliacion'].")\" ></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
        $objResponse->assign("tdListadoEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function nuevaConciliacion(){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("document.getElementById('divFlotante3').style.display = '';
                              document.getElementById('tblNuevaConciliacion').style.display = '';
                              document.getElementById('tdFlotanteTitulo3').innerHTML = 'Nueva Conciliacion';
                              centrarDiv(document.getElementById('divFlotante3'))");
	
	
	return $objResponse;
}

function listBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT * FROM bancos 
                        INNER JOIN cuentas ON bancos.idBanco = cuentas.idBanco 
                        WHERE bancos.idBanco != '1' GROUP BY bancos.idBanco";
	$rsBanco = mysql_query($queryBanco) or die(mysql_error());
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitBanco = sprintf(" %s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitBanco = mysql_query($queryLimitBanco) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco) or die(mysql_error());
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
			$htmlTb .= "<td align=\"center\">".$rowBanco['sucursal']."</td>";
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
	
	$objResponse->assign("txtNombreBanco","value",  utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmBuscar'))");
	
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
	
	$html = "<select id=\"selCuenta\" name=\"selCuenta\" ".$disabled.">";
			$html .= "<option value=\"-1\">Seleccione</option>";
		while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
			$html .= "<option value=\"".$rowCuentas['idCuentas']."\">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuenta","innerHTML",$html);
	
		
	return $objResponse;
}
function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
        if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
        
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
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

function listBanco1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT * FROM bancos 
                        INNER JOIN cuentas ON bancos.idBanco = cuentas.idBanco 
                        WHERE bancos.idBanco != '1' GROUP BY bancos.idBanco";
	$rsBanco = mysql_query($queryBanco) or die(mysql_error());
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitBanco = sprintf(" %s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitBanco = mysql_query($queryLimitBanco) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco) or die(mysql_error());
		$totalRows = mysql_num_rows($rsBanco);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
                $htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
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
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['sucursal']."</td>";
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
	
	$objResponse->assign("txtNombreBancoCon","value",  utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBancoCon","value",$row['idBanco']);
	
	$objResponse->script("xajax_comboCuentas1(xajax.getFormValues('frmBuscar1'))");
	
	return $objResponse;
}
function comboCuentas1($valForm){
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdBancoCon'] == -1){
		$disabled = "disabled=\"disabled\"";
	}
	else{
		$condicion = "WHERE idBanco = '".$valForm['hddIdBancoCon']."' AND id_empresa = '".$valForm['hddIdEmpresaCon']."'";
		$disabled = "";
	}
	
	$queryCuentas = "SELECT * FROM cuentas ".$condicion."";
	$rsCuentas = mysql_query($queryCuentas) or die(mysql_error());
	
	$html = "<select id=\"selCuenta1\" name=\"selCuenta1\" ".$disabled.">";
			$html .= "<option value=\"-1\">Seleccione</option>";
		while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
			$html .= "<option value=\"".$rowCuentas['idCuentas']."\">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuenta1","innerHTML",$html);
	
		
	return $objResponse;
}
function listEmpresa1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
        if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
			
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
		$totalRows = mysql_num_rows($rsEmpresa);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
            $htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
            $htmlTh .= ordenarCampo("xajax_listEmpresa1", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
            $htmlTh .= ordenarCampo("xajax_listEmpresa1", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");			
	$htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitEmpresa)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa1('".$rowBanco['id_empresa_reg']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listEmpresa1(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa1(%s,'%s','%s','%s',%s);\">%s</a>",
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
function asignarEmpresa1($idEmpresa){
	$objResponse = new xajaxResponse();
	
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
		$rsEmpresa = mysql_query($queryEmpresa) or die (mysql_error());
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
		$nombreSucursal = "";
		
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
		
		$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
		
		$objResponse -> assign("txtEmpresaCon","value",$empresa);
		$objResponse -> assign("hddIdEmpresaCon","value",$rowEmpresa['id_empresa_reg']);
		$objResponse->script("document.getElementById('divFlotante2').style.display = 'none';");
	
	return $objResponse;
}

function enviarDocs($valForm){
		$objResponse = new xajaxResponse();	
		
                $queryvalida = sprintf("SELECT * FROM te_conciliacion WHERE id_banco = '%s' AND id_cuenta = '%s' ",$valForm['hddIdBancoCon'],$valForm['selCuenta1']);  			
                $rsvalida = mysql_query($queryvalida) or die(mysql_error());
                
                $cantidadConciliaciones = mysql_num_rows($rsvalida);

                $fecha_compara = split('-',$valForm['txtFecha1']);
                $fecha_comp = $fecha_compara[1]."-".$fecha_compara[2];

                $count = 0;
                $countMesAnterior = 0;
                
                $anterior = date("m-Y",strtotime("01-".$fecha_comp." -1 MONTH"));//mes año anterior                

                while($rowvalida = mysql_fetch_array($rsvalida)){/* Buscar la manera de validar contra la fecha  */
                        $fecha_bd = split('-',$rowvalida['fecha']);
                        $fecha_bd_comp = $fecha_bd[1]."-".$fecha_bd[0];

                                if($fecha_bd_comp == $fecha_comp){
                                    $count++;
                                }
                                
                                if($fecha_bd_comp == $anterior){
                                    $countMesAnterior++;
                                }
                }

                if($count != 0){
                    $objResponse-> alert("Ya hay una Concilicion para ese Mes seleccione Otro");
                }else if($countMesAnterior == 0 && $cantidadConciliaciones != 0){//si no se concilio mes anterior, y sino es la primera conciliacion
                    $objResponse-> alert("Ya hay una fecha sin conciliar anterior: ".$anterior);
                }else{
                    $objResponse -> script("window.open('te_conciliacion_proceso.php?b=".$valForm['hddIdBancoCon'].",f=".$valForm['hddIdBancoCon'].",e=".$valForm['hddIdBancoCon']." &vw=v','_self');document.frmBuscar1.submit();");	
                }
                //$fecha_compara = split(ads,);

		return $objResponse;
}

function accesoAnulacion($clave){
    
        $objResponse = new xajaxResponse();
        
        if($clave == ""){
            return $objResponse->alert("Debes escribir la clave");
        }
        
        $queryClave = sprintf("SELECT contrasena FROM vw_pg_claves_modulos WHERE id_usuario = %s AND modulo = 'te_anular_conciliacion'",valTpDato($_SESSION['idUsuarioSysGts'],'int'));
	$rsClave = mysql_query($queryClave);
	if (!$rsClave) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);
	
	if (mysql_num_rows($rsClave)){
		$rowClave = mysql_fetch_array($rsClave);
		if ($rowClave['contrasena'] == $clave){
                        $objResponse->script("xajax_cargarConciliacionAnular(document.getElementById('idConciliacionEliminar').value)");
		}else{
                        $objResponse->alert("Clave Errada.");
                }
	}
	else{
		$objResponse->alert("No tiene permiso para realizar esta accion");		
		$objResponse->script("cerrarAccesoClave();");
	}
        
        return $objResponse;
}

function cargarConciliacionAnular($idConciliacion){
        
        $objResponse = new xajaxResponse();
    
        $query = sprintf("SELECT 
                              te_conciliacion.id_conciliacion,
                              te_conciliacion.id_cuenta,
                              te_conciliacion.id_banco,                             
                              te_conciliacion.id_empresa,
                              (SELECT MAX(b.fecha) FROM te_conciliacion b WHERE b.id_cuenta = te_conciliacion.id_cuenta ) as ultima_fecha_conciliada,
                              te_conciliacion.fecha
                            FROM te_conciliacion
                            WHERE te_conciliacion.id_conciliacion = %s",
                            $idConciliacion);
        $rs = mysql_query($query);
        if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
        $row = mysql_fetch_assoc($rs);
        
        //compruebo que sea el ultima conciliacion:
        if($row["fecha"] != $row["ultima_fecha_conciliada"]){
            $fechaElegida = date("m-Y",strtotime($row["fecha"]));
            $ultFechaConciliada = date("m-Y",strtotime($row["ultima_fecha_conciliada"]));
            
            $objResponse->script("cerrarAccesoClave();");            
            return $objResponse->alert("Solo se puede anular conciliaciones recientes, fecha elegida: ".$fechaElegida." fecha reciente: ".$ultFechaConciliada);
        }
        
        $queryTotalReversion = sprintf("SELECT (SELECT SUM(monto) FROM te_estado_cuenta WHERE suma_resta = 1 AND id_conciliacion = %s) as total_credito, 
                                     (SELECT SUM(monto) FROM te_estado_cuenta WHERE suma_resta = 0 AND id_conciliacion = %s) as total_debito", 
                                $idConciliacion,
                                $idConciliacion);
        $rsTotalReversion = mysql_query($queryTotalReversion);
        if(!$rsTotalReversion){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }        
        $rowTotal = mysql_fetch_assoc($rsTotalReversion);
                
        $resta = $rowTotal["total_credito"] - $rowTotal["total_debito"];
        
        $queryCuenta = sprintf("SELECT saldo FROM cuentas WHERE idCuentas = '%s'",$row['id_cuenta']);
        $rsCuenta = mysql_query($queryCuenta);
        if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }   
        $rowCuenta = mysql_fetch_assoc($rsCuenta);
		
        $saldo = $rowCuenta["saldo"];
        
	$montoSaldoTotal = $saldo - $resta; 
        
        $fecha = date("m-Y",strtotime($row['fecha']));
        
        $objResponse->assign("nombreEmpresaAnular","innerHTML",empresa($row['id_empresa']));
        $objResponse->assign("fechaConciliacionAnular","innerHTML",$fecha);
        $objResponse->assign("nombreBancoAnular","innerHTML",nombreBanco($row['id_cuenta']));
        $objResponse->assign("cuentaAnular","innerHTML",cuenta($row['id_cuenta']));
        
        $objResponse->assign("restaAnular","innerHTML",$resta);
        $objResponse->assign("saldoCuentaAnular","innerHTML",$saldo);
        $objResponse->assign("nuevoSaldoAnular","innerHTML","<b>".$montoSaldoTotal."</b>");
        
        $objResponse->script(" document.getElementById('tblclaveAnularConciliacion').style.display='none';"
                . " document.getElementById('tblAnularConciliacion').style.display=''; "
                . " centrarDiv(document.getElementById('divFlotante4')); ");
              
        return $objResponse;
}

function anularConciliacion($idConciliacion){
    $objResponse = new xajaxResponse();
    
    mysql_query("START TRANSACTION");
    
    $query = sprintf("SELECT 
                          te_conciliacion.id_cuenta
                        FROM te_conciliacion
                        WHERE te_conciliacion.id_conciliacion = %s",
                        $idConciliacion);
    $rs = mysql_query($query);
    if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
    $row = mysql_fetch_assoc($rs);

    $queryTotalReversion = sprintf("SELECT (SELECT SUM(monto) FROM te_estado_cuenta WHERE suma_resta = 1 AND id_conciliacion = %s) as total_credito, 
                                 (SELECT SUM(monto) FROM te_estado_cuenta WHERE suma_resta = 0 AND id_conciliacion = %s) as total_debito", 
                            $idConciliacion,
                            $idConciliacion);
    $rsTotalReversion = mysql_query($queryTotalReversion);
    if(!$rsTotalReversion){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }        
    $rowTotal = mysql_fetch_assoc($rsTotalReversion);

    $resta = $rowTotal["total_credito"] - $rowTotal["total_debito"];

    $queryCuenta = sprintf("SELECT saldo FROM cuentas WHERE idCuentas = '%s'",$row['id_cuenta']);
    $rsCuenta = mysql_query($queryCuenta);
    if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }   
    $rowCuenta = mysql_fetch_assoc($rsCuenta);

    $saldo = $rowCuenta["saldo"];

    $montoSaldoTotal = $saldo - $resta; 

    
    //BUSCO TODOS LOS DOCUMENTOS CONCILIADOS
    $queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_conciliacion = '%s'",$idConciliacion);
                        $rsEstadoCuenta = mysql_query($queryEstadoCuenta);
                        
    if (!$rsEstadoCuenta) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
    
    
    //SEGUN EL TIPO DE DOCUMENTO LO ACTUALIZO
    
    while ($rowEstadoCuenta = mysql_fetch_assoc($rsEstadoCuenta)){
    
        //DEPOSITOS
        if($rowEstadoCuenta['tipo_documento'] == 'DP' ){
            $queryActualiza = sprintf("UPDATE te_depositos SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_deposito = '%s'",2,$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }     
        }
        //NOTA DE CREDITO
        if($rowEstadoCuenta['tipo_documento'] == 'NC'){
            $queryActualiza = sprintf("UPDATE te_nota_credito SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_nota_credito = '%s'",2,$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
        }
        //NOTA DE DEBITO
        if($rowEstadoCuenta['tipo_documento'] == 'ND'){
            $queryActualiza = sprintf("UPDATE te_nota_debito  SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_nota_debito  = '%s'",2,$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
        }
        //CHEQUES
        if($rowEstadoCuenta['tipo_documento'] == 'CH'){
            $queryActualiza = sprintf("UPDATE te_cheques  SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_cheque  = '%s'",2,$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
        }
        //TRANSFERENCIAS
        if($rowEstadoCuenta['tipo_documento'] == 'TR'){
            $queryActualiza = sprintf("UPDATE te_transferencia SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_transferencia = '%s'",2,$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }      
        }
    
    }
    
    //ESTADOS DE CUENTA
    $queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s' WHERE id_conciliacion = '%s'",2,$idConciliacion);           
    $rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
    if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }   

    
//    //DEPOSITOS
//    $queryActualiza = sprintf("UPDATE te_depositos SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_conciliacion = '%s'",2,$idConciliacion);                            
//    $rsActualiza = mysql_query($queryActualiza);
//    if (!$rsActualiza){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }  
//
//    //NOTA DE CREDITO
//    $queryActualiza = sprintf("UPDATE te_nota_credito SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_conciliacion = '%s'",2,$idConciliacion);
//    $rsActualiza = mysql_query($queryActualiza);
//    if (!$rsActualiza){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); } 
//
//    //NOTA DE DEBITO
//    $queryActualiza = sprintf("UPDATE te_nota_debito  SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_conciliacion  = '%s'",2,$idConciliacion);
//    $rsActualiza = mysql_query($queryActualiza);
//    if (!$rsActualiza){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); } 
//
//    //CHEQUES
//    $queryActualiza = sprintf("UPDATE te_cheques  SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_conciliacion  = '%s'",2,$idConciliacion);
//    $rsActualiza = mysql_query($queryActualiza);
//    if (!$rsActualiza){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); } 
//
//    //TRANSFERENCIAS
//    $queryActualiza = sprintf("UPDATE te_transferencia SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_conciliacion = '%s'",2,$idConciliacion);
//    $rsActualiza = mysql_query($queryActualiza);
//    if (!$rsActualiza){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); } 

    //CUENTAS IMPORTANTE
    $updateCuenta = sprintf("UPDATE cuentas SET saldo = '%s' WHERE idCuentas = '%s'",$montoSaldoTotal,$row['id_cuenta']);
    $rsUpdateCuenta = mysql_query($updateCuenta);
    if (!$rsUpdateCuenta) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); } 
    
    //ELIMINAR CONCILIACION
    $queryEliminar = sprintf("DELETE FROM te_conciliacion WHERE id_conciliacion = %s",
                    valTpDato($idConciliacion,"int"));
    $rsEliminar = mysql_query($queryEliminar);
    if (!$rsEliminar) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); } 
    mysql_query("COMMIT");
    
    $objResponse->alert("Conciliacion Anulada Correctamente");
    $objResponse->script("cerrarAccesoClave();");
    $objResponse->script("document.getElementById('btnBuscar').click();");
    
    return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"listadoEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"nuevaConciliacion");
$xajax->register(XAJAX_FUNCTION,"listBanco");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"listBanco1");
$xajax->register(XAJAX_FUNCTION,"asignarBanco1");
$xajax->register(XAJAX_FUNCTION,"comboCuentas1");
$xajax->register(XAJAX_FUNCTION,"listEmpresa1");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa1");
$xajax->register(XAJAX_FUNCTION,"enviarDocs");
$xajax->register(XAJAX_FUNCTION,"accesoAnulacion");
$xajax->register(XAJAX_FUNCTION,"cargarConciliacionAnular");
$xajax->register(XAJAX_FUNCTION,"anularConciliacion");

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
	return utf8_encode($respuesta);
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
	return utf8_encode($respuesta);
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
		$respuesta = "Por Aplicar";
	else if($row['id_estados_principales'] == 2)
		$respuesta = "Aplicados";
	
	
	return $respuesta;
}

function fecha($id){

	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['fecha_concialicion'] == NULL)
		$respuesta = "";
	else
		$respuesta = date("d/m/Y",strtotime($row['fecha_conciliacion']));
		
	return $respuesta; 

}

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
		$respuesta = "ND";
	
	return $respuesta;
}


?>