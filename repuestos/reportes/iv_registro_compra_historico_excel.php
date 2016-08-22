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

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("id_modulo IN (0)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(fact_comp.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = fact_comp.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fecha_origen BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
	WHERE mov.id_tipo_movimiento IN (1)
		AND mov.id_documento = fact_comp.id_factura
	LIMIT 1) = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_comp.id_modo_compra = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
	OR prov.nombre LIKE %s
	OR fact_comp.numero_control_factura LIKE %s
	OR fact_comp.numero_factura_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	fact_comp.id_factura,
	fact_comp.id_modo_compra,
	fact_comp.fecha_origen,
	fact_comp.fecha_factura_proveedor,
	fact_comp.numero_factura_proveedor,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	prov.nombre AS nombre_proveedor,
	
	(SELECT COUNT(fact_compra_det.id_factura)
	FROM cp_factura_detalle fact_compra_det
	WHERE fact_compra_det.id_factura = fact_comp.id_factura) AS cant_items,
	
	(SELECT SUM(fact_compra_det.cantidad)
	FROM cp_factura_detalle fact_compra_det
	WHERE fact_compra_det.id_factura = fact_comp.id_factura) AS cant_piezas,
	
	moneda_local.abreviacion AS abreviacion_moneda_local,
	
	(SELECT retencion.idRetencionCabezera
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.idFactura = fact_comp.id_factura
	LIMIT 1) AS idRetencionCabezera,
	
	(SELECT DISTINCT ped_comp.estatus_pedido_compra
	FROM cp_factura_detalle fact_comp_det
		INNER JOIN iv_pedido_compra ped_comp ON (fact_comp_det.id_pedido_compra = ped_comp.id_pedido_compra)
	WHERE fact_comp_det.id_factura = fact_comp.id_factura
	LIMIT 1) AS estatus_pedido_compra,
	
	(IFNULL(fact_comp.subtotal_factura, 0)
		- IFNULL(fact_comp.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
				FROM cp_factura_gasto fact_compra_gasto
				WHERE fact_compra_gasto.id_factura = fact_comp.id_factura
					AND fact_compra_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
	
	(IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
			FROM cp_factura_iva fact_compra_iva
			WHERE fact_compra_iva.id_factura = fact_comp.id_factura), 0)) AS total_iva,
	
	(IFNULL(fact_comp.subtotal_factura, 0)
		- IFNULL(fact_comp.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
				FROM cp_factura_gasto fact_compra_gasto
				WHERE fact_compra_gasto.id_factura = fact_comp.id_factura
					AND fact_compra_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
				FROM cp_factura_iva fact_compra_iva
				WHERE fact_compra_iva.id_factura = fact_comp.id_factura), 0)) AS total,
	
	fact_comp.activa,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cp_factura fact_comp
	INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
	LEFT JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (fact_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY id_factura DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Fecha Registro Compra");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Tipo Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Referencia");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Piezas");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Total Neto");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Total Factura");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['estatus_pedido_compra']) {
		case "" : $imgPedidoModulo = "CxP"; break;
		default : $imgPedidoModulo = "R";
	}
		
	$queryFactDet = sprintf("SELECT id_pedido_compra FROM cp_factura_detalle
	WHERE id_factura = %s
	GROUP BY id_pedido_compra;",
		valTpDato($row['id_factura'], "int"));
	$rsFactDet = mysql_query($queryFactDet);
	if (!$rsFactDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$arrayEstatusPedidoCompra = NULL;
	$arrayTipoPedidoCompra = NULL;
	$arrayIdPedidoCompraPropio = NULL;
	$arrayIdPedidoCompraReferencia = NULL;
	while ($rowFactDet = mysql_fetch_assoc($rsFactDet)) {
		$queryPedComp = sprintf("SELECT * FROM vw_iv_pedidos_compra WHERE id_pedido_compra = %s;",
			valTpDato($rowFactDet['id_pedido_compra'], "int"));
		$rsPedComp = mysql_query($queryPedComp);
		if (!$rsPedComp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowPedComp = mysql_fetch_assoc($rsPedComp);
		
		$arrayEstatusPedidoCompra[] = $rowPedComp['estatus_pedido_compra'];
		$arrayTipoPedidoCompra[] = $rowPedComp['tipo_pedido_compra'];
		$arrayIdPedidoCompraPropio[] = $rowPedComp['id_pedido_compra_propio'];
		$arrayIdPedidoCompraReferencia[] = $rowPedComp['id_pedido_compra_referencia'];
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, date("d-m-Y", strtotime($row['fecha_origen'])));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['numero_factura_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode(implode(", ",$arrayTipoPedidoCompra)));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode(implode(", ",$arrayIdPedidoCompraPropio)));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode(implode(", ",$arrayIdPedidoCompraReferencia)));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['nombre_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['cant_piezas']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['total_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['total_iva']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['total']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$cantFact++;
	$totalItems += $row['cant_items'];
	$totalPiezas += $row['cant_piezas'];
	$totalNeto += $row['total_neto'];
	$totalIva += $row['total_iva'];
	$totalFacturacion += $row['total'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":K".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $cantFact);
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $totalItems);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $totalPiezas);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $totalNeto);
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $totalIva);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $totalFacturacion);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."L".$contFila)->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);

for ($col = "A"; $col != "L"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "L");

$tituloDcto = "Histórico de Registro de Compra";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:L7");

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
$objPHPExcel->getSecurity()->setLockWindows(true);
$objPHPExcel->getSecurity()->setLockStructure(true);

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