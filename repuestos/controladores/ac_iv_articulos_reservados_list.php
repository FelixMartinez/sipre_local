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
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaArticulosReservados(0, "codigo_articulo", "ASC", $valBusq));
	
	return $objResponse;
}

function exportarArticulosReservados($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_articulos_reservados_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function totalArticulosReservados($valBusq) {
	$valCadBusq = explode("|", $valBusq);
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_sol_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_art_sol_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_datos_basicos.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_datos_basicos.id_articulo = %s
		OR vw_iv_art_sol_vent.id_orden LIKE %s
		OR vw_iv_art_datos_basicos.descripcion LIKE %s
		OR vw_iv_art_datos_basicos.codigo_articulo_prov LIKE %s
		OR vw_iv_art_sol_vent.nombre_cliente LIKE %s)",
			valTpDato($valCadBusq[2], "int"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		vw_iv_art_datos_basicos.id_articulo,
		vw_iv_art_datos_basicos.codigo_articulo,
		vw_iv_art_datos_basicos.descripcion
	FROM vw_iv_articulos_solicitud_venta vw_iv_art_sol_vent
		INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_basicos ON (vw_iv_art_sol_vent.id_articulo = vw_iv_art_datos_basicos.id_articulo) %s", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$cantTotalPaginaArt = 0;
	$importePrecioTotalPaginaArt = 0;
	$importeCostoTotaPaginalArt = 0;
	while ($row = mysql_fetch_assoc($rs)) {
		$idArticulo = $row['id_articulo'];
		
		$sqlBusq = NULL;
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("vw_iv_art_sol_vent.id_articulo = %s",
			valTpDato($idArticulo, "int"));
		
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(vw_iv_art_sol_vent.id_orden LIKE %s
			OR vw_iv_art_sol_vent.nombre_cliente LIKE %s)",
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"));
		}
		
		$queryDet = sprintf("SELECT *,
			(SELECT det_orden_art.precio_unitario
			FROM sa_det_orden_articulo det_orden_art
				INNER JOIN sa_solicitud_repuestos sol_rep ON (det_orden_art.id_orden = sol_rep.id_orden)
				INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
			WHERE sol_rep.id_solicitud = vw_iv_art_sol_vent.id_solicitud
				AND det_orden_art.id_orden = vw_iv_art_sol_vent.id_orden
				AND det_sol_rep.id_estado_solicitud = vw_iv_art_sol_vent.id_estado_solicitud
				AND det_orden_art.id_articulo = vw_iv_art_sol_vent.id_articulo
				AND det_sol_rep.id_casilla = vw_iv_art_sol_vent.id_casilla
				AND sol_rep.estado_solicitud = vw_iv_art_sol_vent.estado_solicitud
			LIMIT 1) AS precio_unitario,
			
			(SELECT
				(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_sol_vent.id_empresa)
					WHEN 1 THEN	art_costo.costo
					WHEN 2 THEN	art_costo.costo_promedio
				END)
			FROM iv_articulos_costos art_costo
			WHERE art_costo.id_articulo = vw_iv_art_sol_vent.id_articulo
				AND art_costo.id_empresa = vw_iv_art_sol_vent.id_empresa
			ORDER BY art_costo.id_articulo_costo
			DESC LIMIT 1) AS costo_unitario
			
		FROM vw_iv_articulos_solicitud_venta vw_iv_art_sol_vent
			INNER JOIN vw_iv_articulos_almacen vw_iv_art_alm ON (vw_iv_art_sol_vent.id_articulo = vw_iv_art_alm.id_articulo
				AND vw_iv_art_sol_vent.id_casilla = vw_iv_art_alm.id_casilla) %s", $sqlBusq);
		$rsDet = mysql_query($queryDet);
		if (!$rsDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila2 = 0;
		$arrayTotalRenglon = NULL;
		while ($rowDet = mysql_fetch_assoc($rsDet)) {
			$arrayTotalRenglon[6] += $rowDet['total_cantidad'];
			$arrayTotalRenglon[9] += $rowDet['total_cantidad'] * $rowDet['precio_unitario'];
			$arrayTotalRenglon[10] += $rowDet['total_cantidad'] * $rowDet['costo_unitario'];
		}
		
		$arrayTotal[6] += $arrayTotalRenglon[6];
		$arrayTotal[9] += $arrayTotalRenglon[9];
		$arrayTotal[10] += $arrayTotalRenglon[10];
	}
	
	return array(6 => $arrayTotal[6], 9 => $arrayTotal[9], 10 => $arrayTotal[10]);
}

function listaArticulosReservados($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_sol_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_art_sol_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_datos_basicos.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_datos_basicos.id_articulo = %s
		OR vw_iv_art_sol_vent.id_orden LIKE %s
		OR vw_iv_art_datos_basicos.descripcion LIKE %s
		OR vw_iv_art_datos_basicos.codigo_articulo_prov LIKE %s
		OR vw_iv_art_sol_vent.nombre_cliente LIKE %s)",
			valTpDato($valCadBusq[2], "int"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		vw_iv_art_datos_basicos.id_articulo,
		vw_iv_art_datos_basicos.codigo_articulo,
		vw_iv_art_datos_basicos.descripcion
	FROM vw_iv_articulos_solicitud_venta vw_iv_art_sol_vent
		INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_basicos ON (vw_iv_art_sol_vent.id_articulo = vw_iv_art_datos_basicos.id_articulo) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$idArticulo = $row['id_articulo'];
		
		$htmlTb .= "<tr>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"left\" height=\"22\">";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">".("Código:")."</a></td>";
					$htmlTb .= "<td colspan=\"8\">".elimCaracter(htmlentities($row['codigo_articulo']),";")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr align=\"left\" height=\"22\">";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">".("Descripción:")."</td>";
					$htmlTb .= "<td colspan=\"8\">".htmlentities($row['descripcion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr class=\"tituloColumna\">";
					$htmlTb .= ordenarCampo("xajax_listaArticulosReservados", "6%", $pageNum, "tiempo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Orden");
					$htmlTb .= ordenarCampo("xajax_listaArticulosReservados", "8%", $pageNum, "id_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Orden");
					$htmlTb .= ordenarCampo("xajax_listaArticulosReservados", "8%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Orden");
					$htmlTb .= ordenarCampo("xajax_listaArticulosReservados", "26%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
					$htmlTb .= "<td width=\"12%\">"."Ubicación"."</td>";
					$htmlTb .= "<td width=\"8%\">"."Cantidad"."</td>";
					$htmlTb .= "<td width=\"8%\">".$spanPrecioUnitario."</td>";
					$htmlTb .= "<td width=\"8%\">"."Costo Unit."."</td>";
					$htmlTb .= "<td width=\"8%\">"."Importe Precio"."</td>";
					$htmlTb .= "<td width=\"8%\">"."Importe Costo"."</td>";
				$htmlTb .= "</tr>";
		
		
		$sqlBusq = NULL;
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("vw_iv_art_sol_vent.id_articulo = %s",
			valTpDato($idArticulo, "int"));
		
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(vw_iv_art_sol_vent.id_orden LIKE %s
			OR vw_iv_art_sol_vent.nombre_cliente LIKE %s)",
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"));
		}
		
		$queryDet = sprintf("SELECT *,
			(SELECT det_orden_art.precio_unitario
			FROM sa_det_orden_articulo det_orden_art
				INNER JOIN sa_solicitud_repuestos sol_rep ON (det_orden_art.id_orden = sol_rep.id_orden)
				INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
			WHERE sol_rep.id_solicitud = vw_iv_art_sol_vent.id_solicitud
				AND det_orden_art.id_orden = vw_iv_art_sol_vent.id_orden
				AND det_sol_rep.id_estado_solicitud = vw_iv_art_sol_vent.id_estado_solicitud
				AND det_orden_art.id_articulo = vw_iv_art_sol_vent.id_articulo
				AND det_sol_rep.id_casilla = vw_iv_art_sol_vent.id_casilla
				AND sol_rep.estado_solicitud = vw_iv_art_sol_vent.estado_solicitud
			LIMIT 1) AS precio_unitario,
			
			(SELECT
				(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_sol_vent.id_empresa)
					WHEN 1 THEN	art_costo.costo
					WHEN 2 THEN	art_costo.costo_promedio
				END)
			FROM iv_articulos_costos art_costo
			WHERE art_costo.id_articulo = vw_iv_art_sol_vent.id_articulo
				AND art_costo.id_empresa = vw_iv_art_sol_vent.id_empresa
			ORDER BY art_costo.id_articulo_costo
			DESC LIMIT 1) AS costo_unitario
			
		FROM vw_iv_articulos_solicitud_venta vw_iv_art_sol_vent
			INNER JOIN vw_iv_articulos_almacen vw_iv_art_alm ON (vw_iv_art_sol_vent.id_articulo = vw_iv_art_alm.id_articulo
				AND vw_iv_art_sol_vent.id_casilla = vw_iv_art_alm.id_casilla) %s", $sqlBusq);
		$rsDet = mysql_query($queryDet);
		if (!$rsDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila2 = 0;
		$arrayTotalRenglon = NULL;
		while ($rowDet = mysql_fetch_assoc($rsDet)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($rowDet['tiempo_orden']))."</td>";
				$htmlTb .= "<td>".$rowDet['id_orden']."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowDet['nombre_tipo_orden'])."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($rowDet['nombre_cliente'])."</td>";
				$htmlTb .= "<td align=\"center\" ".$classEstatusAlmacen." nowrap=\"nowrap\">";
					$htmlTb .= utf8_encode($rowDet['descripcion_almacen'])."<br><span class=\"textoNegrita_10px\">".utf8_encode(str_replace("-[]", "", $rowDet['ubicacion']))."</span>";
					$htmlTb .= ($rowDet['estatus_articulo_almacen'] == 1) ? "" : "<br><span class=\"textoRojoNegrita_10px\">".utf8_encode("Relacion Inactiva")."</span>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td>".number_format($rowDet['total_cantidad'], 2, ".", ",")."</td>";
					$htmlTb .= "<tr>";
						$htmlTb .= ($rowDet['id_articulo_costo'] > 0) ? "<tr><td><span id=\"spnLote".$contFila2."\" class=\"textoNegrita_9px\">LOTE: ".$rowDet['id_articulo_costo']."</span></td><tr>" : "";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".number_format($rowDet['precio_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".$rowDet['abreviacion_moneda_local'].number_format($rowDet['costo_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($rowDet['total_cantidad'] * $rowDet['precio_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".$rowDet['abreviacion_moneda_local'].number_format($rowDet['total_cantidad'] * $rowDet['costo_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalRenglon[6] += $rowDet['total_cantidad'];
			$arrayTotalRenglon[9] += $rowDet['total_cantidad'] * $rowDet['precio_unitario'];
			$arrayTotalRenglon[10] += $rowDet['total_cantidad'] * $rowDet['costo_unitario'];
		}
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"22\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"5\">Totales:<br>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalRenglon[6], 2, ".", ",")."</td>";
					$htmlTb .= "<td>"."</td>";
					$htmlTb .= "<td>"."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalRenglon[9], 2, ".", ",")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalRenglon[10], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if ($contFila < $maxRows && (($maxRows * $pageNum) + $contFila) < $totalRows)
			$htmlTb .= "<tr align=\"left\"><td>&nbsp;</td></tr>";
		
		$arrayTotal[6] += $arrayTotalRenglon[6];
		$arrayTotal[9] += $arrayTotalRenglon[9];
		$arrayTotal[10] += $arrayTotalRenglon[10];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
					$htmlTb .= "<td class=\"tituloCampo\" width=\"60%\">Total Página:</td>";
					$htmlTb .= "<td width=\"8%\">".number_format($arrayTotal[6], 2, ".", ",")."</td>";
					$htmlTb .= "<td width=\"8%\">"."</td>";
					$htmlTb .= "<td width=\"8%\">"."</td>";
					$htmlTb .= "<td width=\"8%\">".number_format($arrayTotal[9], 2, ".", ",")."</td>";
					$htmlTb .= "<td width=\"8%\">".number_format($arrayTotal[10], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$totalFinal = totalArticulosReservados($valBusq);
			$htmlTb .= "<tr>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table border=\"0\" width=\"100%\">";
					$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
						$htmlTb .= "<td class=\"tituloCampo\" width=\"60%\">Total de Totales:</td>";
						$htmlTb .= "<td width=\"8%\">".number_format($totalFinal[6], 2, ".", ",")."</td>";
						$htmlTb .= "<td width=\"8%\">"."</td>";
						$htmlTb .= "<td width=\"8%\">"."</td>";
						$htmlTb .= "<td width=\"8%\">".number_format($totalFinal[9], 2, ".", ",")."</td>";
						$htmlTb .= "<td width=\"8%\">".number_format($totalFinal[10], 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulosReservados(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulosReservados(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticulosReservados(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulosReservados(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulosReservados(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaArticulosReservados","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarArticulosReservados");
$xajax->register(XAJAX_FUNCTION,"listaArticulosReservados");
?>