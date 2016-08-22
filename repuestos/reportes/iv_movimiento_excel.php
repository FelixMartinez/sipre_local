<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
require_once("../../connections/conex.php");
session_start();

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

include("clase_excel.php");

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = $valCadBusq[0];
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);
	
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

//iteramos para los resultados
$query = sprintf("SELECT *,
		
	(SELECT clave_mov.clave
	FROM pg_clave_movimiento clave_mov
	WHERE clave_mov.id_clave_movimiento = vw_iv_movimiento.id_clave_movimiento) AS clave
	
FROM vw_iv_movimiento %s
ORDER BY id_tipo_movimiento, clave, id_movimiento ASC;", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$contFila = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$idModulo = $row['id_modulo'];
		
	switch ($idModulo) {
		case 0 : $imgModuloDcto = "Repuestos"; break;
		case 1 : $imgModuloDcto = "Servicios"; break;
		case 2 : $imgModuloDcto = "Vehículos"; break;
		case 3 : $imgModuloDcto = "Administración"; break;
		default : $imgModuloDcto = "";
	}
	
	if ($row['tipo_proveedor_cliente_empleado'] == 1) { // PROVEEDOR
		$queryProvClienteEmpleado = sprintf("SELECT
			CONCAT_WS('-', lrif, rif) AS rif_proveedor,
			nombre
		FROM cp_proveedor
		WHERE id_proveedor = %s;",
			$row['id_proveedor_cliente_empleado']);
		$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
		if (!$rsProvClienteEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
		$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre'];
		$rifProvClienteEmpleado = $rowProvClienteEmpleado['rif_proveedor'];
	} else if ($row['tipo_proveedor_cliente_empleado'] == 2) { // CLIENTE
		$queryProvClienteEmpleado = sprintf("SELECT
			CONCAT_WS('-', lci, ci) AS ci_cliente,
			CONCAT_WS(' ', nombre, apellido) AS nombre_cliente
		FROM cj_cc_cliente
		WHERE id = %s ",
			$row['id_proveedor_cliente_empleado']);
		$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
		if (!$rsProvClienteEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
		$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre_cliente'];
		$rifProvClienteEmpleado = $rowProvClienteEmpleado['ci_cliente'];
	} else if ($row['tipo_proveedor_cliente_empleado'] == 3) { // EMPLEADO
		$queryProvClienteEmpleado = sprintf("SELECT
			cedula,
			CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado
		FROM pg_empleado
		WHERE id_empleado = %s",
			$row['id_proveedor_cliente_empleado']);
		$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
		if (!$rsProvClienteEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
		$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre_empleado'];
		$rifProvClienteEmpleado = $rowProvClienteEmpleado['cedula'];
	}
	
	if ($auxActual != $row['id_clave_movimiento']) {
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode($row['descripcion_tipo_movimiento'])." - ".$row['clave'].") ".utf8_encode($row['descripcion_clave_movimiento']));
	
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":O".$contFila)->applyFromArray($styleArrayTitulo);
	
		$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":O".$contFila);
		
		$auxActual = $row['id_clave_movimiento'];
		
		$contFila++;
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro. Dcto:");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $imgModuloDcto);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['numero_documento']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Control / Folio:");
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['folio'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Fecha Dcto.:");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, date("d-m-Y",strtotime($row['fecha_documento'])));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Fecha Registro / Captura:");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, date("d-m-Y",strtotime($row['fecha_captura'])));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->applyFromArray($styleArrayCampo);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":C".$contFila);
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Prov./Clnte./Emp.:");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $rifProvClienteEmpleado);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $nombreProvClienteEmpleado);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Orden::");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['numero_orden']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Remis:");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['numero_documento']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->applyFromArray($styleArrayCampo);
	
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":C".$contFila);
	$objPHPExcel->getActiveSheet()->mergeCells("E".$contFila.":H".$contFila);
	
	$contFila++;
	$primero = $contFila;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Lote");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Cant.");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Descripción Precio");
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $spanPrecioUnitario);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Costo Unit.");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Importe Precio");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Dscto.");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Neto");
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Importe Costo");
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Utl.");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "%Utl. S/V");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "%Utl. S/C");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "%Dscto.");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":O".$contFila)->applyFromArray($styleArrayColumna);
	
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
		valTpDato($row['id_documento'], "int"),
		valTpDato($row['id_movimiento'], "int"));
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$contFila2 = 0;
	$arrayTotal = NULL;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)){
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		$importeCosto = $rowDetalle['cantidad'] * $rowDetalle['costo'];
		$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio'];
		$descuento = $rowDetalle['porcentaje_descuento'] * $importePrecio / 100;
		$neto = $importePrecio - $descuento;
		
		$importeCosto = ($row['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
		
		$porcUtilidadCosto = 0;
		$porcUtilidadVenta = 0;
		if ($importePrecio > 0) {
			$utilidad = $neto - $importeCosto;
			
			$porcUtilidadCosto = $utilidad * 100 / $importeCosto;
			$porcUtilidadVenta = $utilidad * 100 / $importePrecio;
		}
		
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter($rowDetalle['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $rowDetalle['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $rowDetalle['id_articulo_costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $rowDetalle['cantidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $rowDetalle['descripcion_precio']);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $rowDetalle['precio']);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $rowDetalle['costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $importePrecio);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $descuento);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $neto);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $importeCosto);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $utilidad);
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $porcUtilidadVenta);
		$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $porcUtilidadCosto);
		$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $rowDetalle['porcentaje_descuento']);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":O".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayTotal['cant_dctos'] = $rowDetalle['cant_dctos'];
		$arrayTotal['cantidad'] += $rowDetalle['cantidad'];
		$arrayTotal['importe_precio'] += $importePrecio;
		$arrayTotal['descuento'] += $descuento;
		$arrayTotal['importe_neto'] += $neto;
		$arrayTotal['importe_costo'] += $importeCosto;
		$arrayTotal['utilidad'] += $utilidad;
	}
	$ultimo = $contFila;
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total Dcto. ".$row['documento'].":");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $arrayTotal['cantidad']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotal['importe_precio']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotal['descuento']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $arrayTotal['importe_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTotal['importe_costo']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotal['utilidad']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, (($arrayTotal['utilidad'] > 0) ? ($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_precio'] : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, (($arrayTotal['utilidad'] > 0) ? ($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_costo'] : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, (($arrayTotal['importe_precio'] > 0) ? ($arrayTotal['descuento'] * 100) / $arrayTotal['importe_precio'] : 0));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila.":"."O".$contFila)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":C".$contFila);
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
	
	//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "O");

$tituloDcto = "Movimientos";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:O7");
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
 
//Trabajamos con la hoja activa secundaria
$objPHPExcel->createSheet(NULL, 1);
$objPHPExcel->setActiveSheetIndex(1);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Clave de Movimiento");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Cant. Dctos.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Importe Precio");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Dscto.");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Importe Neto");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Importe Costo");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Utl.");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "%Utl. S/V");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "%Utl. S/C");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "%Dscto.");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":K".$contFila)->applyFromArray($styleArrayColumna);

for ($idTipoMovimiento = 1; $idTipoMovimiento <= 4; $idTipoMovimiento++) {
	$contFila++;
	
	$arrayTipoMovimiento = array("", "Compra", "Entrada", "Venta", "Salida");
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $arrayTipoMovimiento[$idTipoMovimiento]);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":K".$contFila)->applyFromArray($styleArrayCampo);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("id_tipo_movimiento IN (%s)",
		valTpDato($idTipoMovimiento, "int"));
	
	$query = sprintf("SELECT
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
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$contFila2 = 0;
	$arrayDet = NULL;
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("mov.id_clave_movimiento = %s",
			valTpDato($row['id_clave_movimiento'], "int"));
		
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
		if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$arrayTotal = NULL;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
			$importeCosto = $rowDetalle['cantidad'] * $rowDetalle['costo'];
			$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio'];
			$descuento = $rowDetalle['porcentaje_descuento'] * $importePrecio / 100;
			$neto = $importePrecio - $descuento;
			
			$importeCosto = ($row['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
			
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
		
		switch($row['id_modulo']) {
			case 0 : $imgModulo = "R"; break;
			case 1 : $imgModulo = "S"; break;
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode($imgModulo));
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['clave'].") ".$row['descripcion_clave_movimiento']));
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotal['cant_dctos']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $arrayTotal['importe_precio']);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $arrayTotal['descuento']);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTotal['importe_neto']);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotal['importe_costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotal['utilidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $porcUtilidadVenta);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $porcUtilidadCosto);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $porcDescuento);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":K".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayDet[3] += $arrayTotal['cant_dctos'];
		$arrayDet[4] += $arrayTotal['importe_precio'];
		$arrayDet[5] += $arrayTotal['descuento'];
		$arrayDet[6] += $arrayTotal['importe_neto'];
		$arrayDet[7] += $arrayTotal['importe_costo'];
		$arrayDet[8] += $arrayTotal['utilidad'];
		
		$arrayTotalMovimiento[$idTipoMovimiento] = $arrayDet;
	}
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode("Total ".$arrayTipoMovimiento[$idTipoMovimiento].":"));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotalMovimiento[$idTipoMovimiento][3]);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $arrayTotalMovimiento[$idTipoMovimiento][4]);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $arrayTotalMovimiento[$idTipoMovimiento][5]);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTotalMovimiento[$idTipoMovimiento][6]);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotalMovimiento[$idTipoMovimiento][7]);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotalMovimiento[$idTipoMovimiento][8]);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, (($arrayTotalMovimiento[$idTipoMovimiento][8] > 0) ? (($arrayTotalMovimiento[$idTipoMovimiento][8] * 100) / $arrayTotalMovimiento[$idTipoMovimiento][4]) : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, (($arrayTotalMovimiento[$idTipoMovimiento][8] > 0) ? (($arrayTotalMovimiento[$idTipoMovimiento][8] * 100) / $arrayTotalMovimiento[$idTipoMovimiento][7]) : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($arrayTotalMovimiento[$idTipoMovimiento][4] > 0) ? ($arrayTotalMovimiento[$idTipoMovimiento][5] * 100) / $arrayTotalMovimiento[$idTipoMovimiento][4] : 0));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":B".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila.":"."K".$contFila)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "K");

$tituloDcto2 = "Resumen Movimientos";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto2);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:K7");
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto2,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);

//Titulo del libro y seguridad
$objPHPExcel->getSecurity()->setLockWindows(true);
$objPHPExcel->getSecurity()->setLockStructure(true);

$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getProperties()->setCreator("SIPRE ".cVERSION);
//$objPHPExcel->getProperties()->setLastModifiedBy("autor");
$objPHPExcel->getProperties()->setTitle($tituloDcto);
//$objPHPExcel->getProperties()->setSubject("Asunto");
//$objPHPExcel->getProperties()->setDescription("Descripcion");

// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$tituloDcto.'.xlsx"');
header('Cache-Control: max-age=0');
 
//Creamos el Archivo .xlsx
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>