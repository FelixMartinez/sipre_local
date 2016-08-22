<?php


function buscarPrecioArt($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAplicaIva'],
		$frmBuscar['lstTipoArticulo'],
		$frmBuscar['lstVerClasificacion'],
		$frmBuscar['cbxVerArtDisponible'],
		$frmBuscar['cbxVerArtNoDisponible'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPrecioArticulo(0, "art.codigo_articulo", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($idTipoArticulo == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$row['id_tipo_articulo']."\" ".$selected.">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarPreciosArt($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAplicaIva'],
		$frmBuscar['lstTipoArticulo'],
		$frmBuscar['lstVerClasificacion'],
		$frmBuscar['cbxVerArtDisponible'],
		$frmBuscar['cbxVerArtNoDisponible'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_lista_precio_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirPrecioArt($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAplicaIva'],
		$frmBuscar['lstTipoArticulo'],
		$frmBuscar['lstVerClasificacion'],
		$frmBuscar['cbxVerArtDisponible'],
		$frmBuscar['cbxVerArtNoDisponible'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/iv_lista_precio_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaPrecioArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_emp.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_art_emp.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.posee_iva = %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.clasificacion LIKE %s",
			valTpDato($valCadBusq[3], "text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != ""
	&& ($valCadBusq[5] == "-1" || $valCadBusq[5] == "")) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_fisica > 0");
	}
	
	if (($valCadBusq[4] == "-1" || $valCadBusq[4] == "")
	&& $valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_fisica <= 0");
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[6], "text"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[7], "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_iv_art_emp.id_empresa,
		vw_iv_art_emp.id_articulo,
		vw_iv_art_emp.codigo_articulo,
		vw_iv_art_emp.descripcion,
		art.posee_iva,
		vw_iv_art_emp.cantidad_disponible_fisica,
		
		(SELECT art_precio.precio FROM iv_articulos_precios art_precio
		WHERE art_precio.id_articulo = art.id_articulo
			AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
			AND art_precio.id_precio = 1) AS precio,
		
		(CASE art.posee_iva
			WHEN 1 THEN
				((SELECT SUM(iva) FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1)
					* (SELECT art_precio.precio FROM iv_articulos_precios art_precio
						WHERE art_precio.id_articulo = art.id_articulo
							AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
							AND art_precio.id_precio = 1)) / 100
			ELSE
				0
		END) AS monto_iva,
		
		(CASE art.posee_iva
			WHEN 1 THEN
				(SELECT art_precio.precio FROM iv_articulos_precios art_precio
				WHERE art_precio.id_articulo = art.id_articulo
					AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
					AND art_precio.id_precio = 1) + (((SELECT SUM(iva) FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1)
														* (SELECT art_precio.precio FROM iv_articulos_precios art_precio
														WHERE art_precio.id_articulo = art.id_articulo
															AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
															AND art_precio.id_precio = 1)) / 100)
			ELSE
				(SELECT art_precio.precio FROM iv_articulos_precios art_precio
				WHERE art_precio.id_articulo = art.id_articulo
					AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
					AND art_precio.id_precio = 1)
		END) AS precio_total,
		
		(SELECT moneda.abreviacion FROM pg_monedas moneda
		WHERE moneda.idmoneda = (SELECT art_precio.id_moneda FROM iv_articulos_precios art_precio
								WHERE art_precio.id_articulo = art.id_articulo
									AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
									AND art_precio.id_precio = 1)) AS abreviacion_moneda,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_articulos_empresa vw_iv_art_emp
		INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_art_emp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "42%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "8%", $pageNum, "cantidad_disponible_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unid. Disponible"));
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "8%", $pageNum, "precio", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanPrecioUnitario));
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "8%", $pageNum, "monto_iva", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Impuesto"));
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "8%", $pageNum, "precio_total", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Precio Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgAplicaIva = ($row['posee_iva'] == 1) ? "<img src=\"../img/iconos/accept.png\" title=\"Si Aplica Impuesto\"/>" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgAplicaIva."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"right\" ".(($row['cantidad_disponible_fisica'] > 0) ? "class=\"divMsjInfo\"" : (($row['cantidad_disponible_fisica'] < 0) ? "class=\"divMsjError\"" : "")).">";
				$htmlTb .= number_format($row['cantidad_disponible_fisica'], 2, ".", ",");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['abreviacion_moneda']).number_format($row['precio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['abreviacion_moneda']).number_format($row['monto_iva'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['abreviacion_moneda']).number_format($row['precio_total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[0] += $row['cantidad_disponible_fisica'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">".utf8_encode("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[0],2)."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[0] += $row['cantidad_disponible_fisica'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">".utf8_encode("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[0],2)."</td>";
			$htmlTb .= "</tr>";
		}
	}
		
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPreciosArt","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPrecioArt");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarPreciosArt");
$xajax->register(XAJAX_FUNCTION,"imprimirPrecioArt");
$xajax->register(XAJAX_FUNCTION,"listaPrecioArticulo");
?>