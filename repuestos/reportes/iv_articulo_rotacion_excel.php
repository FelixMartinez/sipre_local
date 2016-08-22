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

//iteramos para los resultados
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
	INNER JOIN iv_articulos art ON (vw_iv_art_emp_ubic.id_articulo = art.id_articulo) %s
ORDER BY CONCAT(descripcion_almacen, ubicacion) ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Código Prov.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Ult. Mov");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Ubicación");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Costo");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Unid. Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Valor Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Unid. Reservada");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Valor Reservada");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":N".$contFila)->applyFromArray($styleArrayColumna);

$objPHPExcel->getActiveSheet()->mergeCells("G".$contFila.":I".$contFila);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
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
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $row['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, $row['codigo_articulo_prov'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['unidad'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $row['clasificacion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date("d-m-Y", strtotime($row['fecha_movimiento'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['descripcion_almacen'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFila, str_replace("-[]", "", $row['ubicacion']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, (($row['estatus_articulo_almacen'] == 1) ? "" : "(Inactiva)"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $costoUnit);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $cantDisponible);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $subTotalDisponible);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $cantReservada);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $subTotalReservada);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":N".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":N".$ultimo);

for ($col = "A"; $col != "N"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "N");

$tituloDcto = "Artículos Sin Rotación";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:N7");

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