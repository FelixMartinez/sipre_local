<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
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
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstMesesSinRotacion'],
		$frmBuscar['lstTipoArticulo'],
		$frmBuscar['lstVerClasificacion'],
		$frmBuscar['cbxVerArtDisponible'],
		$frmBuscar['cbxVerArtNoDisponible'],
		$frmBuscar['cbxVerArtReservada'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaArticulo(0, "CONCAT(descripcion_almacen, ubicacion)", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstMeses($selId = ""){
	$objResponse = new xajaxResponse();
	
	$html = "<select id=\"lstMesesSinRotacion\" name=\"lstMesesSinRotacion\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	for ($cont = 2; $cont <= 24; $cont++) {
		$selected = ($selId == $cont) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$cont."\">".htmlentities($cont)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMesesSinRotacion","innerHTML",$html);
	
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
		$selected = ($selId == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_articulo']."\">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarArticulo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstMesesSinRotacion'],
		$frmBuscar['lstTipoArticulo'],
		$frmBuscar['lstVerClasificacion'],
		$frmBuscar['cbxVerArtDisponible'],
		$frmBuscar['cbxVerArtNoDisponible'],
		$frmBuscar['cbxVerArtReservada'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_articulo_rotacion_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $valCadBusq[0];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(estatus_articulo_almacen = 1
	OR (estatus_articulo_almacen IS NULL AND existencia > 0)
	OR (estatus_articulo_almacen IS NULL AND cantidad_reservada > 0))");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.id_casilla IS NOT NULL");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT
			DATE(kardex.fecha_movimiento) AS fecha_movimiento
		FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_emp_ubic.id_articulo
			AND (CASE kardex.tipo_movimiento
					WHEN 1 THEN
						(SELECT ped_compra.id_empresa AS id_empresa
						FROM cp_factura fact
							JOIN iv_orden_compra ord_compra ON (fact.id_orden_compra = ord_compra.id_orden_compra)
							JOIN iv_pedido_compra ped_compra ON (ord_compra.id_pedido_compra = ped_compra.id_pedido_compra)
						WHERE fact.id_factura = kardex.id_documento)
					WHEN 2 THEN
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN
								(SELECT vale_ent.id_empresa AS id_empresa FROM iv_vale_entrada vale_ent
								WHERE vale_ent.id_vale_entrada = kardex.id_documento)
							WHEN 2 THEN
								(SELECT nota_cred.id_empresa AS id_empresa FROM cj_cc_notacredito nota_cred
								WHERE nota_cred.idDepartamentoNotaCredito = 0
									AND nota_cred.idNotaCredito = kardex.id_documento)
						END)
					WHEN 3 THEN
						(SELECT fact_venta.id_empresa AS id_empresa FROM cj_cc_encabezadofactura fact_venta
						WHERE fact_venta.idFactura = kardex.id_documento)
					WHEN 4 THEN
						(CASE kardex.id_modulo
							WHEN 0 THEN
								(SELECT vale_sal.id_empresa AS id_empresa FROM iv_vale_salida vale_sal
								WHERE vale_sal.id_vale_salida = kardex.id_documento)
							WHEN 1 THEN
								(SELECT vale_sal.id_empresa AS id_empresa FROM sa_vale_salida vale_sal
								WHERE vale_sal.id_vale_salida = kardex.id_documento)
						END)
				END) = vw_iv_art_emp_ubic.id_empresa
		ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) DESC
		LIMIT 1) < DATE_SUB(CURDATE(), INTERVAL %s MONTH)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.clasificacion LIKE %s",
			valTpDato($valCadBusq[3], "text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != ""
	&& ($valCadBusq[5] == "-1" || $valCadBusq[5] == "")
	&& ($valCadBusq[6] == "-1" || $valCadBusq[6] == "")) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.cantidad_disponible_fisica > 0");
	}
	
	if (($valCadBusq[4] == "-1" || $valCadBusq[4] == "")
	&& $valCadBusq[5] != "-1" && $valCadBusq[5] != ""
	&& ($valCadBusq[6] == "-1" || $valCadBusq[6] == "")) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.cantidad_disponible_fisica <= 0");
	}
	
	if (($valCadBusq[4] == "-1" || $valCadBusq[4] == "")
	&& ($valCadBusq[5] == "-1" || $valCadBusq[5] == "")
	&& $valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.cantidad_reservada > 0");
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[7], "text"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_emp_ubic.id_articulo = %s
		OR vw_iv_art_emp_ubic.descripcion LIKE %s
		OR vw_iv_art_emp_ubic.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[8], "int"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT vw_iv_art_emp_ubic.*,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
		
		(SELECT
			(CASE (SELECT valor FROM pg_configuracion_empresa config_emp
						INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_emp_ubic.id_empresa)
				WHEN 1 THEN	art_costo.costo
				WHEN 2 THEN	art_costo.costo_promedio
			END)
		FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo = vw_iv_art_emp_ubic.id_articulo
			AND art_costo.id_empresa = vw_iv_art_emp_ubic.id_empresa
		ORDER BY art_costo.id_articulo_costo
		DESC LIMIT 1) AS costo,
		
		(SELECT
			DATE(kardex.fecha_movimiento) AS fecha_movimiento
		FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_emp_ubic.id_articulo
			AND (CASE kardex.tipo_movimiento
					WHEN 1 THEN
						(SELECT ped_compra.id_empresa AS id_empresa
						FROM cp_factura fact
							JOIN iv_orden_compra ord_compra ON (fact.id_orden_compra = ord_compra.id_orden_compra)
							JOIN iv_pedido_compra ped_compra ON (ord_compra.id_pedido_compra = ped_compra.id_pedido_compra)
						WHERE fact.id_factura = kardex.id_documento)
					WHEN 2 THEN
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN
								(SELECT vale_ent.id_empresa AS id_empresa FROM iv_vale_entrada vale_ent
								WHERE vale_ent.id_vale_entrada = kardex.id_documento)
							WHEN 2 THEN
								(SELECT nota_cred.id_empresa AS id_empresa FROM cj_cc_notacredito nota_cred
								WHERE nota_cred.idDepartamentoNotaCredito = 0
									AND nota_cred.idNotaCredito = kardex.id_documento)
						END)
					WHEN 3 THEN
						(SELECT fact_venta.id_empresa AS id_empresa FROM cj_cc_encabezadofactura fact_venta
						WHERE fact_venta.idFactura = kardex.id_documento)
					WHEN 4 THEN
						(CASE kardex.id_modulo
							WHEN 0 THEN
								(SELECT vale_sal.id_empresa AS id_empresa FROM iv_vale_salida vale_sal
								WHERE vale_sal.id_vale_salida = kardex.id_documento)
							WHEN 1 THEN
								(SELECT vale_sal.id_empresa AS id_empresa FROM sa_vale_salida vale_sal
								WHERE vale_sal.id_vale_salida = kardex.id_documento)
						END)
				END) = vw_iv_art_emp_ubic.id_empresa
		ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) DESC
		LIMIT 1) AS fecha_movimiento
		
	FROM vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic
		INNER JOIN iv_articulos art ON (vw_iv_art_emp_ubic.id_articulo = art.id_articulo) %s ", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitArticulo = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimitArticulo);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "18%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "10%", $pageNum, "codigo_articulo_prov", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Prov.");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "5%", $pageNum, "unidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "6%", $pageNum, "fecha_movimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ult. Mov.");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "10%", $pageNum, "CONCAT(descripcion_almacen, ubicacion)", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicación");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "5%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "6%", $pageNum, "cantidad_disponible_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Disponible");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "6%", $pageNum, "(cantidad_disponible_fisica * costo)", $campOrd, $tpOrd, $valBusq, $maxRows, "Valor Disponible");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "6%", $pageNum, "cantidad_reservada", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Reservada");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "6%", $pageNum, "(cantidad_reservada * costo)", $campOrd, $tpOrd, $valBusq, $maxRows, "Valor Reservada");
	$htmlTh .= "</tr>";
		
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$costoUnit = $row['costo'];
		
		$cantKardex = 0;
		$subTotalKardex = $cantKardex * $costoUnit;
		
		$cantDisponible = $row['cantidad_disponible_fisica']; // SALDO - RESERVADAS
		$subTotalDisponible = $cantDisponible * $costoUnit;
		
		$cantReservada = $row['cantidad_reservada'];
		$subTotalReservada = $cantReservada * $costoUnit;
		
		$cantDiferencia = $row['existencia'] - 0;
		$subTotalDiferencia = $cantDiferencia * $costoUnit;
		
		$classEstatusAlmacen = ($row['estatus_articulo_almacen'] == 1) ? "class=\"texto_9px\"" : "class=\"divMsjError texto_9px\"";
		
		$classDisponible = ($cantDisponible > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		
		$classReservada = ($cantReservada > 0) ? "class=\"divMsjAlerta\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb.= "<td align=\"center\" class=\"textoNegrita_9px\">".($contFila + (($pageNum) * $maxRows))."</td>"; // <----
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".htmlentities($row['descripcion'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['codigo_articulo_prov'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['unidad'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_movimiento']))."</td>";
			$htmlTb .= "<td align=\"center\" ".$classEstatusAlmacen." nowrap=\"nowrap\">";
				$htmlTb .= "<span class=\"textoNegrita_10px\">".utf8_encode(strtoupper($row['descripcion_almacen']))."</span><br>";
				$htmlTb .= utf8_encode(str_replace("-[]", "", $row['ubicacion']));
				$htmlTb .= ($row['estatus_articulo_almacen'] == 1) ? "" : "<br>(Inactiva)";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($costoUnit, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format($cantDisponible, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($subTotalDisponible, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classReservada.">".number_format($cantReservada, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($subTotalReservada, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[0] += $cantDisponible;
		$arrayTotal[1] += $subTotalDisponible;
		$arrayTotal[2] += $cantReservada;
		$arrayTotal[3] += $subTotalReservada;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[0],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[1],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[2],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[3],2)."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$costoUnit = $row['costo'];
				
				$cantKardex = 0;
				$subTotalKardex = $cantKardex * $costoUnit;
				
				$cantDisponible = $row['cantidad_disponible_fisica']; // EXISTENCIA - RESERVADAS
				$subTotalDisponible = $cantDisponible * $costoUnit;
				
				$cantReservada = $row['cantidad_reservada'];
				$subTotalReservada = $cantReservada * $costoUnit;
				
				$cantDiferencia = $row['existencia'] - 0;
				$subTotalDiferencia = $cantDiferencia * $costoUnit;
				
				$arrayTotalFinal[0] += $cantDisponible;
				$arrayTotalFinal[1] += $subTotalDisponible;
				$arrayTotalFinal[2] += $cantReservada;
				$arrayTotalFinal[3] += $subTotalReservada;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[0],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[1],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[2],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[3],2)."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstMeses");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarArticulo");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
?>