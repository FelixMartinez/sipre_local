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

// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('TRANSITO', 'POR REGISTRAR', 'SINIESTRADO', 'DISPONIBLE', 'RESERVADO')");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.fecha_origen BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version = %s",
		valTpDato($valCadBusq[5], "int"));
}
	
if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
}
	
if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[7])."'", "defined", "'".str_replace(",","','",$valCadBusq[7])."'"));
}
	
if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
		valTpDato($valCadBusq[8], "campo"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
	OR vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.placa LIKE %s
	OR numero_factura_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	vw_iv_modelo.id_uni_bas,
	CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo
FROM an_unidad_fisica uni_fis
	INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
	INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
	LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
	LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
	LEFT JOIN an_pedido ped_vent ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica AND uni_fis.estado_venta NOT IN ('DISPONIBLE'))
	LEFT JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version) ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$contFila = 0;
$primero = $contFila;
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, $row['vehiculo']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":Q".$contFila);
	
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Nro. Unidad Física");
	$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFila, $spanSerialCarroceria);
	$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFila, $spanSerialMotor);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Color");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $spanPlaca);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Ingreso");
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Días en Inv.");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Estado Venta");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Estado Compra");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Empresa");
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Almacén");
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Nro. Fact. Compra");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Costo");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Fecha Pedido");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Nro. Pedido");
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Cliente");
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Vendedor");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Q".$contFila)->applyFromArray($styleArrayColumna);
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE "; // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$sqlBusq2 .= $cond.sprintf("(ped_vent.estado_pedido IN (0,1,3)
	OR ped_vent.estado_pedido IS NULL)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
		valTpDato($row['id_uni_bas'], "int"));
	
	$queryDetalle = sprintf("SELECT
		uni_fis.id_unidad_fisica,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		color_ext.nom_color AS color_externo1,
		color_int.nom_color AS color_interno1,
		(CASE
			WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
				cxp_fact.fecha_origen
			WHEN (an_ve.fecha IS NOT NULL) THEN
				an_ve.fecha
		END) AS fecha_origen,
		IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(cxp_fact.fecha_origen)
				WHEN (an_ve.fecha IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
			END),
		0) AS dias_inventario,
		uni_fis.estado_compra,
		uni_fis.estado_venta,
		alm.nom_almacen,
		cxp_fact.numero_factura_proveedor,
		uni_fis.costo_compra,
		uni_fis.precio_compra,
		uni_fis.costo_agregado,
		uni_fis.costo_depreciado,
		uni_fis.costo_trade_in,
		ped_vent.id_pedido,
		ped_vent.numeracion_pedido,
		ped_vent.fecha AS fecha_pedido,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		vw_pg_empleado.nombre_empleado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
			AND an_ve.fecha IS NOT NULL
			AND an_ve.tipo_vale_entrada = 1)
		LEFT JOIN an_pedido ped_vent ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica AND uni_fis.estado_venta NOT IN ('DISPONIBLE'))
		LEFT JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s
	ORDER BY CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version) ASC;", $sqlBusq, $sqlBusq2);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$arrayTotal = NULL;
	$contFila2 = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)){
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, $rowDetalle['id_unidad_fisica']);
		$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFila, utf8_encode($rowDetalle['serial_carroceria']));
		$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFila, utf8_encode($rowDetalle['serial_motor']));
		$objPHPExcel->getActiveSheet()->SetCellValue("D".$contFila, utf8_encode($rowDetalle['color_externo1']));
		$objPHPExcel->getActiveSheet()->SetCellValue("E".$contFila, utf8_encode($rowDetalle['placa']));
		$objPHPExcel->getActiveSheet()->SetCellValue("F".$contFila, implode("-",array_reverse(explode("-",$rowDetalle['fecha_origen']))));
		$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFila, $rowDetalle['dias_inventario']);
		$objPHPExcel->getActiveSheet()->SetCellValue("H".$contFila, utf8_encode($rowDetalle['estado_venta']));
		$objPHPExcel->getActiveSheet()->SetCellValue("I".$contFila, (($rowDetalle['estado_venta'] == "RESERVADO" && $rowDetalle['estado_compra'] != "REGISTRADO") ? "(".utf8_encode($rowDetalle['estado_compra']).")" : ""));
		$objPHPExcel->getActiveSheet()->SetCellValue("J".$contFila, utf8_encode($rowDetalle['nombre_empresa']));
		$objPHPExcel->getActiveSheet()->SetCellValue("K".$contFila, utf8_encode($rowDetalle['nom_almacen']));
		$objPHPExcel->getActiveSheet()->SetCellValue("L".$contFila, $rowDetalle['numero_factura_proveedor']);
		$objPHPExcel->getActiveSheet()->SetCellValue("M".$contFila, ($rowDetalle['precio_compra'] + $rowDetalle['costo_agregado'] - $rowDetalle['costo_depreciado'] - $rowDetalle['costo_trade_in']));
		$objPHPExcel->getActiveSheet()->SetCellValue("N".$contFila, utf8_encode(implode("-",array_reverse(explode("-",$rowDetalle['fecha_pedido'])))));
		$objPHPExcel->getActiveSheet()->SetCellValue("O".$contFila, $rowDetalle['numeracion_pedido']);
		$objPHPExcel->getActiveSheet()->SetCellValue("P".$contFila, utf8_encode($rowDetalle['nombre_cliente']));
		$objPHPExcel->getActiveSheet()->SetCellValue("Q".$contFila, utf8_encode($rowDetalle['nombre_empleado']));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Q".$contFila)->applyFromArray($clase);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayTotal[10] = $contFila2;
		$arrayTotal[11] += $rowDetalle['precio_compra'] + $rowDetalle['costo_agregado'] - $rowDetalle['costo_depreciado'] - $rowDetalle['costo_trade_in'];
		$arrayTotal[12] += $rowDetalle['precio_unitario'];
	}
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Subtotal:");
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotal[10]);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $arrayTotal[11]);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila.":"."Q".$contFila)->applyFromArray($styleArrayResaltarTotal);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":K".$contFila);
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "");
		
	$arrayTotalFinal[10] += $arrayTotal[10];
	$arrayTotalFinal[11] += $arrayTotal[11];
	$arrayTotalFinal[12] += $arrayTotal[12];
}

$contFila++;
$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Total de Totales:");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotalFinal[10]);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $arrayTotalFinal[11]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("L".$contFila.":"."Q".$contFila)->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":K".$contFila);

$ultimo = $contFila;
//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":S".$ultimo);

cabeceraExcel($objPHPExcel, $idEmpresa, "Q");

$tituloDcto = "Disponibilidad de Vehículos con Asignación";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:Q7");

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