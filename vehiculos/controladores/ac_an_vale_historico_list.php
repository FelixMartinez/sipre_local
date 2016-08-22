<?php


function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstTipoMovimiento'],
		$frmBuscar['lstTipoVale'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaVale(0, "CONCAT(fecha, numeracion_vale, tipo_vale)", "DESC", $valBusq));
	
	return $objResponse;
}

function listaVale($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vale.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vale.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vale.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_movimiento = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_vale = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("numeracion_vale LIKE %s
		OR nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT vale.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM (
		SELECT
			vale_ent.id_vale_entrada AS id_vale,
			vale_ent.numeracion_vale_entrada AS numeracion_vale,
			vale_ent.id_empresa,
			vale_ent.fecha,
			vale_ent.id_documento,
			vale_ent.id_cliente,
			(CASE
				WHEN (vale_ent.tipo_vale_entrada IN (1,2,3)) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente FROM cj_cc_cliente cliente
					WHERE cliente.id = vale_ent.id_cliente)
				WHEN (vale_ent.tipo_vale_entrada IN (4,5)) THEN
					(SELECT CONCAT_WS(_latin1' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado FROM pg_empleado empleado
					WHERE empleado.id_empleado = vale_ent.id_cliente)
			END) AS nombre_cliente,
			1 AS cant_items,
			vale_ent.subtotal_factura AS subtotal_documento,
			vale_ent.tipo_vale_entrada AS tipo_vale,
			vale_ent.observacion,
			2 AS id_tipo_movimiento,
			'VL ENTRADA' AS tipo_documento
		FROM an_vale_entrada vale_ent
			INNER JOIN an_unidad_fisica uni_fis ON (vale_ent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		
		UNION
		
		SELECT
			vale_sal.id_vale_salida,
			vale_sal.numeracion_vale_salida,
			vale_sal.id_empresa,
			vale_sal.fecha,
			vale_sal.id_documento,
			vale_sal.id_cliente,
			(CASE
				WHEN (vale_sal.tipo_vale_salida IN (1,2,3)) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente FROM cj_cc_cliente cliente
					WHERE cliente.id = vale_sal.id_cliente)
				WHEN (vale_sal.tipo_vale_salida IN (4,5)) THEN
					(SELECT CONCAT_WS(_latin1' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado FROM pg_empleado empleado
					WHERE empleado.id_empleado = vale_sal.id_cliente)
			END) AS nombre_cliente,
			1 AS cant_items,
			vale_sal.subtotal_factura,
			vale_sal.tipo_vale_salida,
			vale_sal.observacion,
			4 AS id_tipo_movimiento,
			'VL SALIDA' AS tipo_documento
		FROM an_vale_salida vale_sal
			INNER JOIN an_unidad_fisica uni_fis ON (vale_sal.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)) AS vale
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vale.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaVale", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaVale", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaVale", "8%", $pageNum, "numeracion_vale", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Vale");
		$htmlTh .= ordenarCampo("xajax_listaVale", "14%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Documento");
		$htmlTh .= ordenarCampo("xajax_listaVale", "36%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente / Empleado");
		$htmlTh .= ordenarCampo("xajax_listaVale", "8%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaVale", "14%", $pageNum, "subtotal_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Subtotal");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch($row['tipo_vale']) {
			case 2 : $img = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Factura CxC\""; break;
			case 3 : $img = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Nota Crédito CxC\""; break;
			case 4 : $img = "<img src=\"../img/iconos/ico_cambio.png\" title=\"Movimiento Inter-Almacen\""; break;
			default : $img = "";
		}
		
		switch($row['id_tipo_movimiento']) {
			case 2 : $aValePDF = sprintf("verVentana('reportes/an_ajuste_inventario_vale_entrada_imp.php?id=%s', 960, 550);", $row['id_vale']); break;
			case 4 : $aValePDF = sprintf("verVentana('reportes/an_ajuste_inventario_vale_salida_imp.php?id=%s', 960, 550);", $row['id_vale']); break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$img."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_vale']."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities($row['tipo_documento'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cant_items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['subtotal_documento'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<a href=\"javascript:".$aValePDF."\" target=\"_self\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Vale PDF\"/></a>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['id_tipo_movimiento'] == 2 && $row['id_documento'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_devolucion_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota Crédito PDF\"/>",
					$row['id_documento']);
			}
			$htmlTb .= "</td>";
			// MODIFICADO ERNESTO
			$sPar = "idobject=".$row['id_vale'];
			switch ($row['tipo_movimiento']) {
				case 2 : // ENTRADA
					$sPar .= "&ct=10";
					$sPar .= "&dt=06";
					$sPar .= "&cc=04";
					break;
				case 4 : // SALIDA
					$sPar .= "&ct=11";
					$sPar .= "&dt=06";
					$sPar .= "&cc=04";
					break;
			}
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?".$sPar."', 960, 550);\"><img src=\"../img/iconos/new_window.png\" title=\"Movimiento Contable\"/></a>");
			$htmlTb .= "</td>";
			// MODIFICADO ERNESTO
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s Registros de un total de %s&nbsp;",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVale(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVale(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaVale(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVale(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVale(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaVale","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"listaVale");
?>