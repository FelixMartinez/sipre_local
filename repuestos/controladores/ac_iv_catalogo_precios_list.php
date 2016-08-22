<?php


function buscarArticulo($frmBuscar) {
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
		$frmBuscar['cbxVerUbicDisponible'],
		$frmBuscar['cbxVerUbicSinDisponible'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCatalogoPrecio(0, "codigo_articulo", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['idmoneda']) {
			$selected = "selected=\"selected\"";
		} else if ($row['predeterminada'] == 1 && $selId == "") {
			$selected = "selected=\"selected\"";
		}
		
		$html .= "<option ".$selected." value=\"".$row['idmoneda']."\">".utf8_encode($row['descripcion']." (".$row['abreviacion'].")")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_articulo']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarCatalogo($frmBuscar) {
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
		$frmBuscar['cbxVerUbicDisponible'],
		$frmBuscar['cbxVerUbicSinDisponible'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_catalogo_precios_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formArticuloPrecio($idArticuloPrecio, $idArticulo, $idPrecio){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_catalogo_precios_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarArticuloPrecio').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM iv_articulos art
	WHERE art.id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DEL PRECIO
	$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.id_precio = %s;",
		valTpDato($idPrecio, "int"));
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPrecio = mysql_num_rows($rsPrecio);
	$rowPrecio = mysql_fetch_assoc($rsPrecio);
	
	// BUSCA LOS DATOS DEL PRECIO DEL ARTICULO
	$query = sprintf("SELECT * FROM iv_articulos_precios art_precio
	WHERE art_precio.id_articulo_precio = %s;",
		valTpDato($idArticuloPrecio, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdArticuloPrecio","value",$row['id_articulo_precio']);
	$objResponse->assign("hddIdArticulo","value",$idArticulo);
	$objResponse->assign("hddIdPrecio","value",$idPrecio);
	$objResponse->assign("txtDescripcion","value",$rowPrecio['descripcion_precio']);
	$objResponse->assign("txtPrecioArt","value",number_format($row['precio'], 2, ".", ","));
	$objResponse->loadCommands(cargaLstMoneda($row['id_moneda']));
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML",utf8_encode("Editar Precio (Artículo Código: ".elimCaracter($rowArticulo['codigo_articulo'],";")." - ".$rowArticulo['descripcion']).")");
	
	return $objResponse;
}

function guardarArticuloPrecio($frmArticuloPrecio, $frmBuscar, $frmListaArticulos){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$idArticuloPrecio = $frmArticuloPrecio['hddIdArticuloPrecio'];
	
	if ($idArticuloPrecio > 0) {
		if (!xvalidaAcceso($objResponse,"iv_catalogo_precios_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE iv_articulos_precios SET
			precio = %s,
			id_moneda = %s
		WHERE id_articulo_precio = %s;",
			valTpDato($frmArticuloPrecio['txtPrecioArt'], "real_inglesa"),
			valTpDato($frmArticuloPrecio['lstMoneda'], "int"),
			valTpDato($idArticuloPrecio, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_catalogo_precios_list","editar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO iv_articulos_precios (id_empresa, id_articulo, id_precio, precio, id_moneda)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($frmArticuloPrecio['hddIdArticulo'], "int"),
			valTpDato($frmArticuloPrecio['hddIdPrecio'], "int"),
			valTpDato($frmArticuloPrecio['txtPrecioArt'], "real_inglesa"),
			valTpDato($frmArticuloPrecio['lstMoneda'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idArticuloPrecio = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Precio de Venta Guardado con Éxito"));
	
	$objResponse->script("
	byId('btnCancelarArticuloPrecio').click();");
	
	$objResponse->loadCommands(listaCatalogoPrecio(
		$frmListaArticulos['pageNum'],
		$frmListaArticulos['campOrd'],
		$frmListaArticulos['tpOrd'],
		$frmListaArticulos['valBusq']));
	
	return $objResponse;
}

function listaCatalogoPrecio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("precio.porcentaje <> 0 AND precio.estatus IN (1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
		
	$queryPrecio = sprintf("SELECT *
	FROM pg_empresa_precios emp_precio
		INNER JOIN pg_precios precio ON (emp_precio.id_precio = precio.id_precio) %s
	ORDER BY precio.id_precio ASC;", $sqlBusq);
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPrecio = mysql_num_rows($rsPrecio);
	
	$sqlBusq = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT posee_iva FROM iv_articulos art
		WHERE id_articulo = vw_iv_art_emp.id_articulo) = %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tipo_articulo = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("clasificacion LIKE %s",
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
		$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[6], "text"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_articulo = %s
		OR descripcion LIKE %s
		OR codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[7], "int"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT *,
		(SELECT posee_iva FROM iv_articulos art
		WHERE id_articulo = vw_iv_art_emp.id_articulo) AS posee_iva
		
	FROM vw_iv_articulos_empresa vw_iv_art_emp %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitArticulo = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimitArticulo);
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
		$htmlTh .= ordenarCampo("xajax_listaCatalogoPrecio", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaCatalogoPrecio", "26%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaCatalogoPrecio", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaCatalogoPrecio", "6%", $pageNum, "cantidad_disponible_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unid. Disponible"));
		while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
			$htmlTh .= "<td width=\"".(54 / $totalRowsPrecio)."%\">".utf8_encode($rowPrecio['descripcion_precio'])."</td>";
			$arrayIdPrecio[] = array($rowPrecio['id_precio'], $rowPrecio['actualizar_con_costo']);
		}
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgAplicaIva = ($row['posee_iva'] == 1) ? "<img src=\"../img/iconos/accept.png\" title=\"Si Aplica Impuesto\"/>" : "";
				
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgAplicaIva."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".htmlentities("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".htmlentities("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".htmlentities("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".htmlentities("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".htmlentities("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".htmlentities("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= ($row['cantidad_disponible_fisica'] > 0) ? "<td align=\"right\" class=\"divMsjInfo\">" : (($row['cantidad_disponible_fisica'] < 0) ? "<td align=\"right\" class=\"divMsjError\">" : "<td align=\"right\">");
				$htmlTb .= number_format($row['cantidad_disponible_fisica'], 2, ".", ",");
			$htmlTb .= "</td>";
			if ($arrayIdPrecio) {
				foreach ($arrayIdPrecio as $indice => $valor) {
					$queryPrecio = sprintf("SELECT art_precio.*,
						moneda.abreviacion
					FROM iv_articulos_precios art_precio
						INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
					WHERE art_precio.id_articulo = %s
						AND art_precio.id_empresa = %s
						AND art_precio.id_precio = %s;",
						valTpDato($row['id_articulo'], "int"),
						valTpDato($row['id_empresa'], "int"),
						valTpDato($arrayIdPrecio[$indice][0], "int"));
					$rsPrecio = mysql_query($queryPrecio);
					if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowPrecio = mysql_fetch_assoc($rsPrecio);
		
					$htmlTb .= "<td align=\"right\">";
						$htmlTb .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
						$htmlTb .= "<tr>";
							$htmlTb .= "<td>";
								$htmlTb .= ($arrayIdPrecio[$indice][1] == 1 && $row['cantidad_disponible_fisica'] > 0 && $rowPrecio['precio'] > 0) ? "" : sprintf("<a class=\"modalImg\" id=\"aEditarArticuloPrecio:%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblArticuloPrecio', '%s', '%s', '%s')\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\"/></a>",
									$contFila,
									$rowPrecio['id_articulo_precio'],
									$row['id_articulo'],
									$arrayIdPrecio[$indice][0]);
							$htmlTb .= "</td>";
							$htmlTb .= "<td align=\"right\" width=\"100%\">".$rowPrecio['abreviacion']." ".number_format($rowPrecio['precio'], 2, ".", ",")."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
				}
			}
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"".(5 + $totalRowsPrecio)."\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"".(5 + $totalRowsPrecio)."\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticulos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarCatalogo");
$xajax->register(XAJAX_FUNCTION,"formArticuloPrecio");
$xajax->register(XAJAX_FUNCTION,"guardarArticuloPrecio");
$xajax->register(XAJAX_FUNCTION,"listaCatalogoPrecio");
?>