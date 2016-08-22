<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function buscarMovimiento($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstTipoMovimiento']),
		implode(",",$frmBuscar['lstModulo']),
		implode(",",$frmBuscar['lstClaveMovimiento']),
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaMovimientos(0, "id_tipo_movimiento, clave, id_movimiento", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	$idModulo = (is_array($idModulo)) ? implode(",",$idModulo) : $idModulo;
	$idTipoClave = (is_array($idTipoClave)) ? implode(",",$idTipoClave) : $idTipoClave;
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT id_empleado, nombre_empleado FROM vw_pg_empleados empleado
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstClaveMovimiento('lstClaveMovimiento', $('#lstModulo').val(), $('#lstTipoMovimiento').val());\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = (in_array($row['id_modulo'],explode(",",$selId))) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarMovimiento($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstTipoMovimiento']),
		implode(",",$frmBuscar['lstModulo']),
		implode(",",$frmBuscar['lstClaveMovimiento']),
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_movimiento_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaMovimientos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 50, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_movimiento) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tipo_movimiento IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_clave_movimiento IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empleado_vendedor = %s",
			valTpDato($valCadBusq[6], "int"));
	}
	
	if ($valCadBusq[7] != "" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_documento LIKE %s
		OR folio LIKE %s
		OR (CASE (vw_iv_movimiento.tipo_proveedor_cliente_empleado)
				WHEN (1) THEN
					(SELECT nombre FROM cp_proveedor
					WHERE id_proveedor = vw_iv_movimiento.id_proveedor_cliente_empleado)
					
				WHEN (2) THEN
					(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
					WHERE id = vw_iv_movimiento.id_proveedor_cliente_empleado)
					
				WHEN (3) THEN
					(SELECT CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado FROM pg_empleado
					WHERE id_empleado = vw_iv_movimiento.id_proveedor_cliente_empleado)
			END) LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT *,
			
		(SELECT clave_mov.clave
		FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.id_clave_movimiento = vw_iv_movimiento.id_clave_movimiento) AS clave
		
	FROM vw_iv_movimiento %s", $sqlBusq);
	
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
	while ($rowMovDet = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$idModulo = $rowMovDet['id_modulo'];
			
		switch ($idModulo) {
			case 0 : $imgModuloDcto = "<img src=\"../img/iconos/ico_repuestos.gif\"/ title=\"Repuestos\">"; break;
			case 1 : $imgModuloDcto = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgModuloDcto = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgModuloDcto = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			default : $imgModuloDcto = "";
		}
		
		switch ($rowMovDet['id_tipo_movimiento']) {
			case 1 : // COMPRA
				switch ($rowMovDet['id_modulo']) {
					case 0: $aVerDcto = "javascript:verVentana('../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=".$rowMovDet['id_documento']."', 960, 550);"; break;
					case 2: $aVerDcto = "javascript:verVentana('../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=".$rowMovDet['id_documento']."', 960, 550);"; break;
				}
				$aVerDcto = "<a id=\"aVerDcto\" href=\"".$aVerDcto."\" target=\"_self\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Registro Compra PDF\"/><a>";
				break;
			case 2 : // ENTRADA
				switch ($rowMovDet['tipo_documento_movimiento']) {
					case 1 : // VALE ENTRADA
						switch ($rowMovDet['id_modulo']) {
							case 0 : $aVerDcto = "javascript:verVentana('../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowMovDet['id_documento']."|2', 960, 550);"; break;
							case 1 : $aVerDcto = "javascript:verVentana('../servicios/sa_devolucion_vale_salida_pdf.php?valBusq=1|".$rowMovDet['id_documento']."', 960, 550);"; break;
							case 2 : $aVerDcto = "javascript:verVentana('../vehiculos/reportes/an_ajuste_inventario_vale_entrada_imp.php?id=".$rowMovDet['id_documento']."', 960, 550);"; break;
						}
						$aVerDcto = "<a id=\"aVerDcto\" href=\"".$aVerDcto."\" target=\"_self\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Vale Entrada PDF\"/><a>";
						break;
					case 2 : // NOTA DE CREDITO
						switch ($rowMovDet['id_modulo']) {
							case 0 : $aVerDcto = "javascript:verVentana('../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=".$rowMovDet['id_documento']."', 960, 550);"; break;
							case 1 : $aVerDcto = "javascript:verVentana('../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=".$rowMovDet['id_documento']."', 960, 550);"; break;
							case 2 : $aVerDcto = "javascript:verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=".$rowMovDet['id_documento']."', 960, 550);"; break;
						}
						$aVerDcto = "<a id=\"aVerDcto\" href=\"".$aVerDcto."\" target=\"_self\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Ver Nota Crédito PDF")."\"/><a>";
						break;
				}
				break;
			case 3 : // VENTA
				switch ($rowMovDet['id_modulo']) {
					case 0 : $aVerDcto = "javascript:verVentana('../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=".$rowMovDet['id_documento']."', 960, 550);"; break;
					case 1 : $aVerDcto = "javascript:verVentana('../servicios/reportes/sa_factura_venta_pdf.php?valBusq=".$rowMovDet['id_documento']."', 960, 550);"; break;
					case 2 : $aVerDcto = "javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=".$rowMovDet['id_documento']."', 960, 550);"; break;
				}
				$aVerDcto = "<a id=\"aVerDcto\" href=\"".$aVerDcto."\" target=\"_self\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Factura Venta PDF\"/><a>";
				break;
			case 4 : // SALIDA
				switch ($rowMovDet['tipo_documento_movimiento']) {
					case 1 : // VALE SALIDA
						switch ($rowMovDet['id_modulo']) {
							case 0 : $aVerDcto = "javascript:verVentana('../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowMovDet['id_documento']."|4', 960, 550);"; break;
							case 1 : $aVerDcto = "javascript:verVentana('../servicios/sa_imprimir_historico_vale.php?valBusq=".$rowMovDet['id_documento']."|2|3', 960, 550);"; break;
							case 2 : $aVerDcto = "javascript:verVentana('../vehiculos/reportes/an_ajuste_inventario_vale_salida_imp.php?id=".$rowMovDet['id_documento']."', 960, 550);"; break;
						}
						$aVerDcto = "<a id=\"aVerDcto\" href=\"".$aVerDcto."\" target=\"_self\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Vale Salida PDF\"/><a>";
						break;
					case 2 : // NOTA DE CREDITO
						$aVerDcto = ""; break;
				}
				break;
			default :
				$aVerDcto = "";
		}
		
		if ($rowMovDet['tipo_proveedor_cliente_empleado'] == 1) { // PROVEEDOR
			$queryProvClienteEmpleado = sprintf("SELECT
				CONCAT_WS('-', lrif, rif) AS rif_proveedor,
				nombre
			FROM cp_proveedor
			WHERE id_proveedor = %s;",
				$rowMovDet['id_proveedor_cliente_empleado']);
			$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
			if (!$rsProvClienteEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
			$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre'];
			$rifProvClienteEmpleado = $rowProvClienteEmpleado['rif_proveedor'];
		} else if ($rowMovDet['tipo_proveedor_cliente_empleado'] == 2) { // CLIENTE
			$queryProvClienteEmpleado = sprintf("SELECT
				CONCAT_WS('-', lci, ci) AS ci_cliente,
				CONCAT_WS(' ', nombre, apellido) AS nombre_cliente
			FROM cj_cc_cliente
			WHERE id = %s ",
				$rowMovDet['id_proveedor_cliente_empleado']);
			$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
			if (!$rsProvClienteEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
			$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre_cliente'];
			$rifProvClienteEmpleado = $rowProvClienteEmpleado['ci_cliente'];
		} else if ($rowMovDet['tipo_proveedor_cliente_empleado'] == 3) { // EMPLEADO
			$queryProvClienteEmpleado = sprintf("SELECT
				cedula,
				CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado
			FROM pg_empleado
			WHERE id_empleado = %s",
				$rowMovDet['id_proveedor_cliente_empleado']);
			$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
			if (!$rsProvClienteEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
			$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre_empleado'];
			$rifProvClienteEmpleado = $rowProvClienteEmpleado['cedula'];
		}
		
		$queryDetalle = sprintf("SELECT 
			mov_det.id_movimiento_detalle,
			art.codigo_articulo,
			art.descripcion,
			mov_det.id_kardex,
			mov_det.cantidad,
			(CASE mov.id_tipo_movimiento
				WHEN 1 THEN -- COMPRA
					(IFNULL(mov_det.precio,0)
						+ IFNULL(mov_det.costo_cargo,0)
						+ IFNULL(mov_det.costo_diferencia,0))
				ELSE
					mov_det.precio
			END) AS precio,
			(IFNULL(mov_det.costo,0)
				+ IFNULL(mov_det.costo_cargo,0)
				+ IFNULL(mov_det.costo_diferencia,0)) AS costo,
			mov_det.porcentaje_descuento,
			
			(SELECT 
				precio.descripcion_precio
			FROM cj_cc_encabezadofactura cxc_fact
				INNER JOIN cj_cc_factura_detalle cxc_fact_det ON (cxc_fact.idFactura = cxc_fact_det.id_factura)
				LEFT JOIN iv_pedido_venta_detalle ped_vent_det ON (cxc_fact.numeroPedido = ped_vent_det.id_pedido_venta
					AND cxc_fact_det.id_articulo = ped_vent_det.id_articulo
					AND cxc_fact_det.cantidad = ped_vent_det.cantidad
					AND cxc_fact.idDepartamentoOrigenFactura IN (0))
				LEFT JOIN sa_det_orden_articulo det_orden_art ON (cxc_fact.numeroPedido = det_orden_art.id_orden
					AND cxc_fact_det.id_articulo = det_orden_art.id_articulo
					AND cxc_fact_det.cantidad = det_orden_art.cantidad
					AND det_orden_art.estado_articulo IN ('FACTURADO','DEVUELTO')
					AND cxc_fact.idDepartamentoOrigenFactura IN (1))
				LEFT JOIN pg_precios precio ON ((ped_vent_det.id_precio = precio.id_precio AND cxc_fact.idDepartamentoOrigenFactura IN (0))
					OR (det_orden_art.id_precio = precio.id_precio AND cxc_fact.idDepartamentoOrigenFactura IN (1)))
			WHERE cxc_fact.idFactura = %s
				AND cxc_fact_det.id_articulo = mov_det.id_articulo
				AND cxc_fact_det.cantidad = mov_det.cantidad
			LIMIT 1) AS descripcion_precio
		FROM iv_movimiento mov
			INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
			INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo)
		WHERE mov_det.id_movimiento = %s
		ORDER BY id_movimiento_detalle;",
			valTpDato($rowMovDet['id_documento'], "int"),
			valTpDato($rowMovDet['id_movimiento'], "int"));
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$htmlTb .= "<tr>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				if ($auxActual != $rowMovDet['id_clave_movimiento']) {
					$htmlTb .= "<tr align=\"left\" class=\"trResaltar6 textoNegrita_12px\" height=\"24\">";
						$htmlTb .= "<td colspan=\"12\">".utf8_encode($rowMovDet['descripcion_tipo_movimiento'])." - ".$rowMovDet['clave'].") ".utf8_encode($rowMovDet['descripcion_clave_movimiento'])."</td>";
					$htmlTb .= "</tr>";
				
					$auxActual = $rowMovDet['id_clave_movimiento'];
				}
				$htmlTb .= "<tr align=\"left\">";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" title=\"Id Movimiento: ".$rowMovDet['id_movimiento']."\">Nro. Dcto:</td>";
					$htmlTb .= "<td colspan=\"2\">";
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr>";
							$htmlTb .= "<td>".$imgModuloDcto."</td>";
							$htmlTb .= "<td>".$aVerDcto."</td>";
							$htmlTb .= "<td align=\"right\" width=\"100%\">".utf8_encode($rowMovDet['numero_documento'])."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Nro. Control / Folio:</td>
								<td align=\"right\" colspan=\"2\">".$rowMovDet['folio']."</td>
								<td align=\"right\" class=\"tituloCampo\">Fecha Dcto.:</td>
								<td align=\"center\">".date("d-m-Y",strtotime($rowMovDet['fecha_documento']))."</td>
								<td align=\"right\" class=\"tituloCampo\">Fecha Registro / Captura:</td>
								<td align=\"center\">".date("d-m-Y",strtotime($rowMovDet['fecha_captura']))."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr align=\"left\">";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Prov./Clnte./Emp.:</td>
								<td align=\"right\">".$rifProvClienteEmpleado."</td>
								<td colspan=\"4\">".utf8_encode($nombreProvClienteEmpleado)."</td>
								<td align=\"right\" class=\"tituloCampo\">Nro. Orden:</td>
								<td align=\"right\">".$rowMovDet['numero_orden']."</td>
								<td align=\"right\" class=\"tituloCampo\" title=\"Nro. Dcto\">Remis:</td>
								<td align=\"right\">".$rowMovDet['numero_documento']."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr class=\"tituloColumna\">";
					$htmlTb .= "<td width=\"10%\">Código</td>
								<td width=\"24%\">Descripción</td>
								<td width=\"6%\">Cantidad</td>
								<td width=\"6%\">".$spanPrecioUnitario."</td>
								<td width=\"6%\">Costo Unit.</td>
								<td width=\"8%\">Importe Precio</td>
								<td width=\"8%\">Dscto.</td>
								<td width=\"8%\">Importe Neto</td>
								<td width=\"8%\">Importe Costo</td>
								<td width=\"8%\">Utl.</td>
								<td width=\"4%\">%Utl.</td>
								<td width=\"4%\">%Dscto.</td>";
				$htmlTb .= "</tr>";
				
				$contFila2 = 0;
				$arrayTotal = NULL;
				while ($rowDetalle = mysql_fetch_array($rsDetalle)){
					$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila2++;
					
					$importeCosto = $rowDetalle['cantidad'] * $rowDetalle['costo'];
					$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio'];
					$descuento = $rowDetalle['porcentaje_descuento'] * $importePrecio / 100;
					$neto = $importePrecio - $descuento;
					
					$importeCosto = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
					
					$porcUtilidadCosto = 0;
					$porcUtilidadVenta = 0;
					if ($importePrecio > 0) {
						$utilidad = $neto - $importeCosto;
						
						$porcUtilidadCosto = $utilidad * 100 / $importeCosto;
						$porcUtilidadVenta = $utilidad * 100 / $importePrecio;
					}
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
						$htmlTb .= "<td align=\"left\" title=\"Id Kardex: ".$rowDetalle['id_kardex']."\nId Mov. Det.: ".$rowDetalle['id_movimiento_detalle']."\">";
							$htmlTb .= elimCaracter(utf8_encode($rowDetalle['codigo_articulo']),";");
							$htmlTb .= ($rowDetalle['id_articulo_costo'] > 0) ? "<br><span id=\"spnLote".$contFila2."\" class=\"textoNegrita_9px\">LOTE: ".$rowDetalle['id_articulo_costo']."</span>" : "";
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"left\">".utf8_encode($rowDetalle['descripcion'])."</td>";
						$htmlTb .= "<td>".number_format($rowDetalle['cantidad'], 2, ".", ",")."</td>";
						$htmlTb .= "<td title=\"".$rowDetalle['descripcion_precio']."\">".number_format($rowDetalle['precio'], 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($rowDetalle['costo'], 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($importePrecio, 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($descuento, 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($neto, 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($importeCosto, 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($utilidad, 2, ".", ",")."</td>";
						$htmlTb .= "<td>";
							$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
							$htmlTb .= "<tr align=\"right\">";
								$htmlTb .= "<td>S/V:</td>";
								$htmlTb .= "<td width=\"100%\">".number_format($porcUtilidadVenta, 2, ".", ",")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "<tr align=\"right\">";
								$htmlTb .= "<td>S/C:</td>";
								$htmlTb .= "<td>".number_format($porcUtilidadCosto, 2, ".", ",")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td>".number_format($rowDetalle['porcentaje_descuento'], 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
					
					$arrayTotal['cant_dctos'] = $rowDetalle['cant_dctos'];
					$arrayTotal['cantidad'] += $rowDetalle['cantidad'];
					$arrayTotal['importe_precio'] += $importePrecio;
					$arrayTotal['descuento'] += $descuento;
					$arrayTotal['importe_neto'] += $neto;
					$arrayTotal['importe_costo'] += $importeCosto;
					$arrayTotal['utilidad'] += $utilidad;
				}
					
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"2\">Total Dcto. ".$rowMovDet['numero_documento'].":</td>
								<td>".number_format($arrayTotal['cantidad'], 2, ".", ",")."</td>
								<td>"."</td>
								<td>"."</td>
								<td>".number_format($arrayTotal['importe_precio'], 2, ".", ",")."</td>
								<td>".number_format($arrayTotal['descuento'], 2, ".", ",")."</td>
								<td>".number_format($arrayTotal['importe_neto'], 2, ".", ",")."</td>
								<td>".number_format($arrayTotal['importe_costo'], 2, ".", ",")."</td>
								<td>".number_format($arrayTotal['utilidad'], 2, ".", ",")."</td>";
					$htmlTb .= "<td>";
						$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>S/V:</td>";
							$htmlTb .= "<td width=\"100%\">".number_format((($arrayTotal['utilidad'] > 0) ? ($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_precio'] : 0), 2, ".", ",")."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>S/C:</td>";
							$htmlTb .= "<td>".number_format((($arrayTotal['utilidad'] > 0) ? ($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_costo'] : 0), 2, ".", ",")."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td>".number_format((($arrayTotal['importe_precio'] > 0) ? ($arrayTotal['descuento'] * 100) / $arrayTotal['importe_precio'] : 0), 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if ($contFila < $maxRows && (($maxRows * $pageNum) + $contFila) < $totalRows)
			$htmlTb .= "<tr><td><hr></td></tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td>";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMovimientos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"24%\">Clave de Movimiento</td>
					<td width=\"6%\">Cant. Dctos.</td>
					<td width=\"10%\">Importe Precio</td>
					<td width=\"10%\">Dscto.</td>
					<td width=\"10%\">Importe Neto</td>
					<td width=\"10%\">Importe Costo</td>
					<td width=\"10%\">Utl.</td>
					<td width=\"10%\">%Utl.</td>
					<td width=\"10%\">%Dscto.</td>";
	$htmlTh .= "</tr>";	
	$htmlTb = "";
	for ($idTipoMovimiento = 1; $idTipoMovimiento <= 4; $idTipoMovimiento++) {
		$arrayTipoMovimiento = array("", "Compra", "Entrada", "Venta", "Salida");
		
		$htmlTb .= "<tr align=\"left\" class=\"tituloColumna\" height=\"24\">";
			$htmlTb .= "<td colspan=\"10\">".$arrayTipoMovimiento[$idTipoMovimiento]."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("id_tipo_movimiento IN (%s)",
			valTpDato($idTipoMovimiento, "int"));
		
		// Agrupa los tipos de movimiento por clave de movimiento para luego calcular el total de ese movimiento
		$queryTipoMov = sprintf("SELECT
			id_clave_movimiento,
			
			(SELECT clave_mov.clave FROM pg_clave_movimiento clave_mov
			WHERE clave_mov.id_clave_movimiento = vw_iv_movimiento.id_clave_movimiento) AS clave,
			
			descripcion_clave_movimiento,
			id_tipo_movimiento,
			descripcion_tipo_movimiento,
			id_modulo
		FROM vw_iv_movimiento %s %s
		GROUP BY id_clave_movimiento, descripcion_clave_movimiento, id_tipo_movimiento
		ORDER BY clave ASC;", $sqlBusq, $sqlBusq2);
		$rsTipoMov = mysql_query($queryTipoMov);
		if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila2 = 0;
		$arrayDet = NULL;
		while($rowMovDet = mysql_fetch_array($rsTipoMov)){
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			$contFila2++;
			
			switch($rowMovDet['id_modulo']) {
				case 0 : $imgModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			}
			
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("mov.id_clave_movimiento = %s",
				valTpDato($rowMovDet['id_clave_movimiento'], "int"));
			
			if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("DATE(mov.fecha_movimiento) BETWEEN %s AND %s",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			}
			
			if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("
				(CASE
					WHEN (mov.id_tipo_movimiento = 2) THEN
						(CASE
							WHEN (mov.tipo_documento_movimiento = 2) THEN
								(SELECT cxc_nc.id_empleado_vendedor FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = mov.id_documento)
						end)
					WHEN (mov.id_tipo_movimiento = 3) THEN
						(SELECT cxc_fact.idVendedor FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = mov.id_documento)
				END) = %s",
					valTpDato($valCadBusq[6], "int"));
			}
			
			if ($valCadBusq[7] != "" && $valCadBusq[7] != "") {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(
				(CASE
					WHEN (clave_mov.tipo = 1 OR mov.id_tipo_movimiento = 1) THEN -- COMPRA
						(SELECT fact.numero_factura_proveedor AS numero_factura_proveedor FROM cp_factura fact
						WHERE fact.id_factura = mov.id_documento)
					WHEN (clave_mov.tipo = 2 OR mov.id_tipo_movimiento = 2) THEN -- ENTRADA
						(CASE mov.tipo_documento_movimiento
							WHEN 1 THEN
								(SELECT vale_ent.numeracion_vale_entrada AS numeracion_vale_entrada FROM iv_vale_entrada vale_ent
								WHERE vale_ent.id_vale_entrada = mov.id_documento)
							WHEN 2 THEN
								(SELECT nota_cred.numeracion_nota_credito AS numeracion_nota_credito FROM cj_cc_notacredito nota_cred
								WHERE nota_cred.idNotaCredito = mov.id_documento)
						END)
					WHEN (clave_mov.tipo = 3 OR mov.id_tipo_movimiento = 3) THEN -- VENTA
						(SELECT fact_venta.numeroFactura AS numeroFactura FROM cj_cc_encabezadofactura fact_venta
						WHERE fact_venta.idFactura = mov.id_documento)
					WHEN (clave_mov.tipo = 4 OR mov.id_tipo_movimiento = 4) THEN -- SALIDA
						(CASE mov.tipo_documento_movimiento
							WHEN 1 THEN
								(CASE clave_mov.id_modulo
									WHEN 0 THEN
										(SELECT vale_sal.numeracion_vale_salida AS numeracion_vale_salida FROM iv_vale_salida vale_sal
										WHERE vale_sal.id_vale_salida = mov.id_documento)
									WHEN 1 THEN
										(SELECT vale_sal.numero_vale AS numero_vale
										FROM sa_vale_salida vale_sal
											INNER JOIN sa_orden orden ON (vale_sal.id_orden = orden.id_orden)
										WHERE vale_sal.id_vale_salida = mov.id_documento)
								END)
							WHEN 2 THEN
								(SELECT cp_nota_cred.numero_nota_credito AS numero_nota_credito FROM cp_notacredito cp_nota_cred
								WHERE cp_nota_cred.id_notacredito = mov.id_documento)
						END)
				END) LIKE %s
				OR (CASE (CASE
							WHEN (clave_mov.tipo = 1 OR mov.id_tipo_movimiento = 1) THEN 1
							WHEN (clave_mov.tipo = 2 OR mov.id_tipo_movimiento = 2) THEN
								(CASE mov.tipo_documento_movimiento
									WHEN 1 THEN
										(CASE (SELECT vale_entrada.tipo_vale_entrada AS tipo_vale_entrada
												FROM iv_vale_entrada vale_entrada
												WHERE vale_entrada.id_vale_entrada = mov.id_documento)
											WHEN 1 THEN 2
											WHEN 2 THEN 2
											WHEN 3 THEN 2
											WHEN 4 THEN 3
											WHEN 5 THEN 3
										END)
									WHEN 2 THEN 2
								END)
							WHEN (clave_mov.tipo = 3 OR mov.id_tipo_movimiento = 3) THEN 2
							WHEN (clave_mov.tipo = 4 OR mov.id_tipo_movimiento = 4) THEN
								(CASE mov.tipo_documento_movimiento
									WHEN 1 THEN
										(CASE (SELECT vale_salida.tipo_vale_salida AS tipo_vale_salida
												FROM iv_vale_salida vale_salida
												WHERE vale_salida.id_vale_salida = mov.id_documento)
											WHEN 1 THEN 2
											WHEN 2 THEN 2
											WHEN 3 THEN 2
											WHEN 4 THEN 3
											WHEN 5 THEN 3
										END)
									WHEN 2 THEN 1
								END)
						END)
					WHEN 1 THEN
						(SELECT nombre FROM cp_proveedor
						WHERE id_proveedor = mov.id_cliente_proveedor)
						
					WHEN 2 THEN
						(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
						WHERE id = mov.id_cliente_proveedor)
						
					WHEN 3 THEN
						(SELECT CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado FROM pg_empleado
						WHERE id_empleado = mov.id_cliente_proveedor)
				END) LIKE %s)",
					valTpDato("%".$valCadBusq[7]."%", "text"),
					valTpDato("%".$valCadBusq[7]."%", "text"));
			}
			
			$sqlBusq3 = "";
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("mov_det.id_movimiento IN (SELECT
					mov.id_movimiento
				FROM iv_movimiento mov
					INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);
			
			$queryDetalle = sprintf("SELECT 
				art.codigo_articulo,
				mov_det.cantidad,
				(CASE mov.id_tipo_movimiento
					WHEN 1 THEN -- COMPRA
						(IFNULL(mov_det.precio,0)
							+ IFNULL(mov_det.costo_cargo,0)
							+ IFNULL(mov_det.costo_diferencia,0))
					ELSE
						mov_det.precio
				END) AS precio,
				(IFNULL(mov_det.costo,0)
					+ IFNULL(mov_det.costo_cargo,0)
					+ IFNULL(mov_det.costo_diferencia,0)) AS costo,
				mov_det.porcentaje_descuento,
				
				(SELECT COUNT(mov.id_movimiento)
				FROM iv_movimiento mov
					INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s) AS cant_dctos
			FROM iv_movimiento mov
				INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
				INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
			ORDER BY id_movimiento_detalle;", $sqlBusq2, $sqlBusq3);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$arrayTotal = NULL;
			while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
				$importeCosto = $rowDetalle['cantidad'] * $rowDetalle['costo'];
				$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio'];
				$descuento = $rowDetalle['porcentaje_descuento'] * $importePrecio / 100;
				$neto = $importePrecio - $descuento;
				
				$importeCosto = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
				
				$porcUtilidadCosto = 0;
				$porcUtilidadVenta = 0;
				if ($importePrecio > 0) {
					$utilidad = $neto - $importeCosto;
					
					$porcUtilidadCosto = $utilidad * 100 / $importeCosto;
					$porcUtilidadVenta = $utilidad * 100 / $importePrecio;
				}
				
				$arrayTotal['cant_dctos'] = $rowDetalle['cant_dctos'];
				$arrayTotal['importe_precio'] += $importePrecio;
				$arrayTotal['descuento'] += $descuento;
				$arrayTotal['importe_neto'] += $neto;
				$arrayTotal['importe_costo'] += $importeCosto;
				$arrayTotal['utilidad'] += $utilidad;
			}
			
			if ($arrayTotal['importe_precio'] > 0) {
				$porcUtilidadCosto = ($arrayTotal['utilidad'] > 0) ? (($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_costo']) : 0;
				$porcUtilidadVenta = ($arrayTotal['utilidad'] > 0) ? (($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_precio']) : 0;
				
				$porcDescuento = (($arrayTotal['descuento'] * 100) / $arrayTotal['importe_precio']);
			} else {
				$porcUtilidadCosto = 0;
				$porcUtilidadVenta = 0;
				
				$porcDescuento = 0;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td>".$imgModulo."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($rowMovDet['clave'].") ".$rowMovDet['descripcion_clave_movimiento'])."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['importe_precio'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['descuento'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['importe_neto'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['importe_costo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['utilidad'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td>S/V:</td>";
						$htmlTb .= "<td width=\"100%\">".number_format($porcUtilidadVenta, 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td>S/C:</td>";
						$htmlTb .= "<td>".number_format($porcUtilidadCosto, 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".number_format($porcDescuento, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			
			$arrayDet[3] += $arrayTotal['cant_dctos'];
			$arrayDet[4] += $arrayTotal['importe_precio'];
			$arrayDet[5] += $arrayTotal['descuento'];
			$arrayDet[6] += $arrayTotal['importe_neto'];
			$arrayDet[7] += $arrayTotal['importe_costo'];
			$arrayDet[8] += $arrayTotal['utilidad'];
			
			$arrayTotalMovimiento[$idTipoMovimiento] = $arrayDet;
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"2\">Total ".$arrayTipoMovimiento[$idTipoMovimiento].":</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento][3], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento][4], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento][5], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento][6], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento][7], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento][8], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td>S/V:</td>";
					$htmlTb .= "<td width=\"100%\">".number_format((($arrayTotalMovimiento[$idTipoMovimiento][8] > 0) ? (($arrayTotalMovimiento[$idTipoMovimiento][8] * 100) / $arrayTotalMovimiento[$idTipoMovimiento][4]) : 0), 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td>S/C:</td>";
					$htmlTb .= "<td>".number_format((($arrayTotalMovimiento[$idTipoMovimiento][8] > 0) ? (($arrayTotalMovimiento[$idTipoMovimiento][8] * 100) / $arrayTotalMovimiento[$idTipoMovimiento][7]) : 0), 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".number_format((($arrayTotalMovimiento[$idTipoMovimiento][4] > 0) ? ($arrayTotalMovimiento[$idTipoMovimiento][5] * 100) / $arrayTotalMovimiento[$idTipoMovimiento][4] : 0), 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaResumenMovimientos","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"exportarMovimiento");
$xajax->register(XAJAX_FUNCTION,"listaMovimientos");
?>