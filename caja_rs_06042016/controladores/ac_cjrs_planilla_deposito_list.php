<?php


function buscarDeposito($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoDeposito(0,'idPlanilla','DESC','%s' + '|' + '%s');",
		$frmBuscar['txtCriterio'],
		$frmBuscar['txtFecha']));
		
	return $objResponse;
}

function listadoDeposito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
					
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
		
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$andEmpresa = sprintf(" AND an_encabezadodeposito.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
			
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresa = '';
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(an_encabezadodeposito.idCaja = 2 ".$andEmpresa.")");
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = sprintf(" AND an_encabezadodeposito.numeroDeposito LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fechaPlanilla = %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"));
	}
	
	$query = sprintf("SELECT DISTINCT (an_encabezadodeposito.idPlanilla) AS idPlanilla,
		an_encabezadodeposito.fechaPlanilla,
		an_encabezadodeposito.numeroDeposito,
		pg_usuario.id_usuario, 
		pg_usuario.nombre_usuario,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM
		an_encabezadodeposito
	INNER JOIN an_detalledeposito ON (an_encabezadodeposito.idPlanilla = an_detalledeposito.idPlanilla)
	INNER JOIN pg_usuario ON (an_encabezadodeposito.id_usuario = pg_usuario.id_usuario)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an_encabezadodeposito.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	
	if (!$rsLimit) return $objResponse ->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryLimit);
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "30%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "35%", $pageNum, "fechaPlanilla", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Dep&oacute;sito"));
		$htmlTh .= ordenarCampo("xajax_listadoDeposito", "35%", $pageNum, "numeroDeposito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Dep&oacute;sito"));
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
			//$htmlTb .= "<td align=\"left\">".$row['idPlanilla']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaPlanilla']))."</td>";
			$htmlTb .= "<td align=\"center\">".($row['numeroDeposito'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formDetalleDeposito('%s');\" src=\"../img/iconos/pencil.png\" title=\"Editar Planilla\"/></td>",$row['idPlanilla']); //EDITAR
			//$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" title=\"Imprimir Planilla\" src=\"../img/iconos/ico_print.png\" border=\"0\" onclick=\"window.open('reimpresionPlanillasAdepositar.php?idPlanilla=".$row['idPlanilla']."','_blank');\"></td>"; //IMPRIMIR
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('reportes/cjrs_impresion_planilla_deposito_pdf.php?valBusq=%s|%s', 1010, 500);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir\"/></td>",$idEmpresa, $row['idPlanilla']); // IMPRIMIR
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_cj_rs.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoDeposito","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function EditarPlanillaDeposito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	if ($valCadBusq[0] != '') //Busqueda(Servidor/Usuario)
		$sqlBusq .= " WHERE (idPlanilla LIKE '%".$valCadBusq[0]."%'
		OR fechaPlanilla LIKE '%".$valCadBusq[0]."%')";
	
	$query = "SELECT DISTINCT
		an_encabezadodeposito.idPlanilla,
		an_detalledeposito.idDeposito,
		bancos.idBanco,
		an_encabezadodeposito.fechaPlanilla,
		an_detalledeposito.numeroDeposito,
		bancos.nombreBanco,
		an_detalledeposito.numeroCuentaBancoAdepositar,
		an_detalledeposito.anulada
	FROM an_encabezadodeposito
		INNER JOIN an_detalledeposito ON (an_encabezadodeposito.idPlanilla = an_detalledeposito.idPlanilla)
		INNER JOIN bancos ON (an_detalledeposito.idBancoAdepositar = bancos.idBanco)
	WHERE an_encabezadodeposito.idPlanilla = '".$valCadBusq[0]."'
		AND an_encabezadodeposito.id_empresa = '".$idEmpresa."'
	GROUP BY numeroDeposito";
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse ->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_EditarPlanillaDeposito", "20%", $pageNum, "fechaPlanilla", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Planilla"));
		$htmlTh .= ordenarCampo("xajax_EditarPlanillaDeposito", "20%", $pageNum, "numeroDeposito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Planilla"));
		$htmlTh .= ordenarCampo("xajax_EditarPlanillaDeposito", "20%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Banco"));
		$htmlTh .= ordenarCampo("xajax_EditarPlanillaDeposito", "20%", $pageNum, "numeroCuentaBancoAdepositar", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Cuenta"));
		$htmlTh .= ordenarCampo("xajax_EditarPlanillaDeposito", "20%", $pageNum, "anulada", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Anulada?"));
		$htmlTh .= "<td></td>";// EDITAR
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		$botonAccionImagen = ($row['anulada'] == 'SI') ? "src=\"../img/iconos/ico_edit_disabled.png\"" : " onclick=\"xajax_DivEditarPlanilla('".$row['idDeposito']."')\" src=\"../img/iconos/pencil.png\"";
		
		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaPlanilla']))."</td>";
			$htmlTb .= "<td align=\"center\">".($row['numeroDeposito'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['numeroCuentaBancoAdepositar'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['anulada'])."</td>";
			$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" ".$botonAccionImagen."/></td>"; //Editar
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"right\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_EditarPlanillaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_EditarPlanillaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_EditarPlanillaDeposito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_EditarPlanillaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_EditarPlanillaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_cj_rs.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdEditarPlanillaDeposito","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function formDetalleDeposito($idPlanilla){
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(EditarPlanillaDeposito(0,"","",$idPlanilla));
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Listado de Planillas");
	$objResponse->script("
	if (byId('divFlotante').style.display == 'none') {
		byId('divFlotante').style.display='';
		centrarDiv(byId('divFlotante'));
	}");
	
	return $objResponse;
}

function DivEditarPlanilla($idDeposito){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("document.forms['frmNroPlanilla'].reset();
		byId('divFlotante2').style.display = '';
		byId('divFlotanteTitulo2').innerHTML = 'Editar Nro. Planilla';
		byId('NroPlanilla').readOnly = true;
		byId('EditNroPlanilla').readOnly = false;
		centrarDiv(byId('divFlotante2'))");

	$queryEditar = sprintf("SELECT * FROM an_detalledeposito WHERE idDeposito = %s ",valTpDato($idDeposito,"int"));
	$rs = mysql_query($queryEditar);
	if(!$rs) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryEditar);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("NroPlanilla","value",$row['numeroDeposito']);
	$objResponse->assign("EditNroPlanilla","value",$row['']);
	$objResponse->assign("hddIdBanco","value",$row['idBancoAdepositar']);
	$objResponse->assign("hddIdDeposito","value",$row['idPlanilla']);
	
	$html ="<hr><td align=\"right\">";
	$html .= "<input type=\"button\" value=\"Editar\" onclick=\"validarTodoForm2();\">";
	$html .= "<input type=\"button\" value=\"Cancelar\" onclick=\"byId('divFlotante2').style.display='none';\">";
	$html .="</td>";
	
	$objResponse->script("byId('btnEditar').style.display = ''");
	
	return $objResponse;
}

function editarPlanilla($frmNroPlanilla){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	/*VERIFICAR SI YA EXISTE EL NUMERO DE PLANILLA (DEL MISMO BANCO) A REGISTRAR EN SISTEMA*/
	$sqlVerificar = sprintf("SELECT * FROM an_detalledeposito WHERE idBancoAdepositar = %s AND numeroDeposito LIKE %s AND anulada LIKE 'NO'",
							valTpDato($frmNroPlanilla['hddIdBanco'],"int"),
							valTpDato($frmNroPlanilla['EditNroPlanilla'],"text"));
	$rsVerificar = mysql_query($sqlVerificar);
	if (!$rsVerificar) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlVerificar);
	
	if (mysql_num_rows($rsVerificar))
		$objResponse->alert("Ya existe una planilla de deposito con el numero ".valTpDato($frmNroPlanilla['EditNroPlanilla'],"text"));
	else {
		// COPIA LA PLANILLA AGREGADA Y LA INSERTA NUEVAMENTE CON ESTADO "NO" anulado
		$sqlCopiarNuevaPlanilla = sprintf("INSERT INTO an_detalledeposito (idPagoRelacionadoConNroCheque, numeroCheque, banco, numeroCuenta, monto, idBancoAdepositar, numeroCuentaBancoAdepositar, formaPago, conformado, tipoDeCheque, numeroDeposito, idTipoDocumento, idPlanilla, anulada, idCaja)
		SELECT
			idPagoRelacionadoConNroCheque,
			numeroCheque,
			banco,
			numeroCuenta,
			monto,
			idBancoAdepositar,
			numeroCuentaBancoAdepositar,
			formaPago,
			conformado,
			tipoDeCheque,
			%s,
			idTipoDocumento,
			idPlanilla,
			'NO',
			idCaja
		FROM
			an_detalledeposito
		WHERE
			idBancoAdepositar = %s
			AND idPlanilla = %s
			AND anulada NOT LIKE 'SI'",
				valTpDato($frmNroPlanilla['EditNroPlanilla'],"text"),
				valTpDato($frmNroPlanilla['hddIdBanco'],"int"),
				valTpDato($frmNroPlanilla['hddIdDeposito'],"text"));
		$rsCopiarNuevaPlanilla = mysql_query($sqlCopiarNuevaPlanilla);
		if (!$rsCopiarNuevaPlanilla) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlCopiarNuevaPlanilla);
		
		$sqlUpdate = sprintf("UPDATE an_detalledeposito SET anulada = 'SI'
		WHERE idBancoAdepositar = %s
		AND numeroDeposito LIKE %s",
			valTpDato($frmNroPlanilla['hddIdBanco'],"int"),
			valTpDato($frmNroPlanilla['NroPlanilla'],"text"));
		$rsUpdate = mysql_query($sqlUpdate);
		if (!$rsUpdate) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdate);
		
		//EDITAR PLANILLA TESORERIA
		$sqlUpdateTesoreriaDeposito = sprintf("UPDATE te_depositos
		SET numero_deposito_banco = %s
		WHERE numero_deposito_banco = %s
			AND (SELECT idBanco FROM cuentas WHERE idCuentas = id_numero_cuenta) = %s",
				valTpDato($frmNroPlanilla['EditNroPlanilla'],"text"),
				valTpDato($frmNroPlanilla['NroPlanilla'],"text"),
				valTpDato($frmNroPlanilla['hddIdBanco'],"int"));
		$rsUpdateTesoreriaDeposito = mysql_query($sqlUpdateTesoreriaDeposito);
		if (!$rsUpdateTesoreriaDeposito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateTesoreriaDeposito);
		
		$sqlUpdateTesoreriaEstadoCuenta = sprintf("UPDATE te_estado_cuenta
		SET numero_documento = %s
		WHERE numero_documento = %s AND (SELECT idBanco FROM cuentas WHERE idCuentas = id_cuenta) = %s AND tipo_documento LIKE 'DP'",
			valTpDato($frmNroPlanilla['EditNroPlanilla'],"text"),
			valTpDato($frmNroPlanilla['NroPlanilla'],"text"),
			valTpDato($frmNroPlanilla['hddIdBanco'],"int"));
		$rsUpdateTesoreriaEstadoCuenta = mysql_query($sqlUpdateTesoreriaEstadoCuenta);
		if (!$rsUpdateTesoreriaEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateTesoreriaEstadoCuenta);
		
		$objResponse->script("xajax_formDetalleDeposito('".$frmNroPlanilla['hddIdDeposito']."')");
		$objResponse->alert("Nro. de Planilla editado exitosamente.");
		$objResponse->script("byId('divFlotante2').style.display = 'none'");
	}
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"buscarDeposito");
$xajax->register(XAJAX_FUNCTION,"listadoDeposito");
$xajax->register(XAJAX_FUNCTION,"EditarPlanillaDeposito");
$xajax->register(XAJAX_FUNCTION,"formDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"DivEditarPlanilla");
$xajax->register(XAJAX_FUNCTION,"editarPlanilla");
?>