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
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idVendedor LIKE %s",
		valTpDato($valCadBusq[3],"text"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.anulada LIKE %s",
		valTpDato($valCadBusq[4],"text"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	switch ($valCadBusq[5]) {
		case 1 : // Vehiculo
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_vehic2.id_factura)
			FROM cj_cc_factura_detalle_vehiculo fact_det_vehic2 WHERE fact_det_vehic2.id_factura = cxc_fact.idFactura) > 0");
			break;
		case 2 : // Adicionales
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
			FROM cj_cc_factura_detalle_accesorios fact_det_acc2
				INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
			WHERE fact_det_acc2.id_factura = cxc_fact.idFactura
				AND acc.id_tipo_accesorio IN (1)) > 0");
			break;
		case 3 : // Accesorios
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
			FROM cj_cc_factura_detalle_accesorios fact_det_acc2
				INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
			WHERE fact_det_acc2.id_factura = cxc_fact.idFactura
				AND acc.id_tipo_accesorio IN (2)) > 0");
			break;
	}
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
	OR cxc_fact.numeroControl LIKE %s
	OR ped_vent.id_pedido LIKE %s
	OR ped_vent.numeracion_pedido LIKE %s
	OR pres_vent.id_presupuesto LIKE %s
	OR pres_vent.numeracion_presupuesto LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR placa LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	cxc_fact.idFactura,
	cxc_fact.id_empresa,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.fechaVencimientoFactura,
	cxc_fact.numeroFactura,
	cxc_fact.numeroControl,
	cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
	cxc_fact.condicionDePago,
	ped_vent.id_pedido,
	ped_vent.numeracion_pedido,
	
	(SELECT ped_vent2.id_pedido FROM an_pedido ped_vent2
	WHERE ped_vent2.id_factura_cxc = cxc_fact.idFactura
		AND ped_vent2.estado_pedido IN (0,1,2,3,4)) AS id_pedido_reemplazo,
	
	pres_vent.id_presupuesto,
	pres_vent.numeracion_presupuesto,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	uni_fis.placa,
	cond_unidad.descripcion AS condicion_unidad,
	ped_comp_det.flotilla,
	cxc_fact.estadoFactura,
	(CASE cxc_fact.estadoFactura
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS descripcion_estado_factura,
	cxc_fact.aplicaLibros,
	cxc_fact.anulada,
	cxc_fact.estatus_factura,
	cxc_fact.observacionFactura,
	cxc_fact.montoTotalFactura,
	cxc_fact.saldoFactura,
	
	(IFNULL(cxc_fact.subtotalFactura, 0)
		- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
	
	IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
			WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_iva,
	
	(IFNULL(cxc_fact.subtotalFactura, 0)
		- IFNULL(cxc_fact.descuentoFactura, 0)
		+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
					WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
		+ IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
					WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0)) AS total,
	
	(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios FROM cj_cc_factura_detalle_accesorios fact_det_acc2
	WHERE fact_det_acc2.id_factura = cxc_fact.idFactura) AS cantidad_accesorios,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
	RIGHT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
	LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY numeroControl DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFila, "");
$objPHPExcel->getActiveSheet()->SetCellValue("D".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Id Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Estado Factura");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Condición");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Adicionales");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, "Saldo Factura");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, "Total Factura");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":U".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
		
	switch($row['id_modulo']) {
		case 0 : $imgPedidoModulo = "Factura Repuestos"; break;
		case 1 : $imgPedidoModulo = "Factura Servicios"; break;
		case 2 : $imgPedidoModulo = "Factura Vehículos"; break;
		case 3 : $imgPedidoModulo = "Factura Administración"; break;
		default : $imgPedidoModulo = $row['id_modulo'];
	}
	
	$imgPedidoModuloCondicion = ($row['id_pedido'] > 0) ? "" : "Creada por CxC";
		
	$imgEstatusPedido = ($row['anulada'] == "SI") ? "Factura (Con Devolución)" : "Facturado";
		
	switch ($row['flotilla']) {
		case 0 : $imgEstatusUnidadAsignacion = "Vehículo Normal"; break;
		case 1 : $imgEstatusUnidadAsignacion = "Vehículo por Flotilla"; break;
		default : $imgEstatusUnidadAsignacion = "";
	}
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgPedidoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $imgEstatusPedido);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $imgEstatusUnidadAsignacion);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date("d-m-Y", strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFila, $row['numeroControl'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, $row['numeracion_pedido'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($row['id_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, utf8_encode($row['ci_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['nombre_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($row['observacionFactura']));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, (($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, utf8_encode($row['descripcion_estado_factura']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("P".$contFila, $row['serial_carroceria'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['condicion_unidad']);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("R".$contFila, $row['placa'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $row['cantidad_accesorios']);
	$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $row['saldoFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $row['montoTotalFactura']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":U".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	
	$arrayTotal[7] = $cantFact++;
	$arrayTotal[19] += $row['cantidad_accesorios'];
	$arrayTotal[20] += $row['saldoFactura'];
	$arrayTotal[21] += $row['montoTotalFactura'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":U".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotal[7]);
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $arrayTotal[19]);
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $arrayTotal[20]);
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $arrayTotal[21]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila.":"."U".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":F".$contFila);

for ($col = "A"; $col != "U"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "U");

$tituloDcto = "Histórico Factura Venta";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:U7");

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