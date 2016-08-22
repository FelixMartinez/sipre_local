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

// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
$queryConfig12 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig12 = mysql_query($queryConfig12);
if (!$rsConfig12) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig12 = mysql_num_rows($rsConfig12);
$rowConfig12 = mysql_fetch_assoc($rsConfig12);

$sqlBusq = " ";
$sqlBusq2 = " ";
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("fact_comp.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art_costo.id_proveedor = %s",
		valTpDato($valCadBusq[1], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("fact_comp.id_proveedor = %s",
		valTpDato($valCadBusq[1], "int"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != ""
&& $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art_costo.fecha BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("fact_comp.fecha_origen BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
		valTpDato($valCadBusq[4], "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("art.id_tipo_articulo = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[5], "text"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[5], "text"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[6], "int"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(art.id_articulo = %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[6], "int"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	art_costo.id_articulo_costo,
	vw_iv_art_emp.id_empresa,
	art_costo.id_proveedor,
	(SELECT prov.nombre AS nombre FROM cp_proveedor prov
	WHERE prov.id_proveedor = art_costo.id_proveedor) AS nombre_proveedor,
	art_costo.fecha,
	vw_iv_art_emp.existencia,
	art_costo.costo,
	art_costo.costo_promedio,
	art_costo.id_moneda,
	(SELECT moneda.abreviacion AS abreviacion FROM pg_monedas moneda
	WHERE moneda.idmoneda = art_costo.id_moneda) AS abreviacion_moneda,
	(SELECT moneda.predeterminada FROM pg_monedas moneda
	WHERE moneda.idmoneda = art_costo.id_moneda
		AND moneda.estatus = 1
		AND moneda.predeterminada = 1) AS predeterminada,
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	art.codigo_articulo_prov,
	art.id_tipo_articulo,
	vw_iv_art_emp.clasificacion,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM iv_articulos art
	LEFT JOIN iv_articulos_costos art_costo ON (art_costo.id_articulo = art.id_articulo)
	LEFT JOIN vw_iv_articulos_empresa vw_iv_art_emp ON (art.id_articulo = vw_iv_art_emp.id_articulo)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_art_emp.id_empresa = vw_iv_emp_suc.id_empresa_reg)
WHERE (art_costo.id_articulo_costo = (SELECT art_costo.id_articulo_costo FROM iv_articulos_costos art_costo
										WHERE art_costo.id_articulo = art.id_articulo
											AND art_costo.id_empresa = vw_iv_art_emp.id_empresa
										ORDER BY art_costo.id_articulo_costo DESC
										LIMIT 1)
	OR ISNULL(art_costo.id_articulo_costo)) %s
UNION
SELECT
	NULL AS id_articulo_costo,
	fact_comp.id_empresa,
	fact_comp.id_proveedor,
	(SELECT prov.nombre AS nombre FROM cp_proveedor prov
	WHERE prov.id_proveedor = fact_comp.id_proveedor) AS nombre_proveedor,
	fact_comp.fecha_origen,
	NULL AS existencia,
	fact_comp_det_imp.costo_unitario,
	fact_comp_det_imp.costo_unitario,
	fact_comp_imp.id_moneda_tasa_cambio,
	(SELECT moneda.abreviacion AS abreviacion FROM pg_monedas moneda
	WHERE moneda.idmoneda = fact_comp_imp.id_moneda_tasa_cambio) AS abreviacion_moneda,
	(SELECT moneda.predeterminada FROM pg_monedas moneda
	WHERE moneda.idmoneda = fact_comp_imp.id_moneda_tasa_cambio
		AND moneda.estatus = 1
		AND moneda.predeterminada = 1) AS predeterminada,
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	art.codigo_articulo_prov,
	art.id_tipo_articulo,
	art.clasificacion,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cp_factura_detalle_importacion fact_comp_det_imp
	JOIN cp_factura_detalle fact_comp_det ON (fact_comp_det_imp.id_factura_detalle = fact_comp_det.id_factura_detalle)
	JOIN cp_factura fact_comp ON (fact_comp_det.id_factura = fact_comp.id_factura)
	JOIN cp_factura_importacion fact_comp_imp ON (fact_comp.id_factura = fact_comp_imp.id_factura)
	JOIN iv_articulos art ON (fact_comp_det.id_articulo = art.id_articulo)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (fact_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg)
WHERE fact_comp_det.id_factura_detalle = (SELECT fact_comp_det2.id_factura_detalle
											FROM cp_factura_detalle_importacion fact_comp_det_imp2
												JOIN cp_factura_detalle fact_comp_det2 ON (fact_comp_det_imp2.id_factura_detalle = fact_comp_det2.id_factura_detalle)
												JOIN cp_factura fact_comp2 ON (fact_comp_det2.id_factura = fact_comp2.id_factura)
											WHERE fact_comp_det2.id_articulo = art.id_articulo
											ORDER BY fact_comp2.fecha_origen DESC, fact_comp_det_imp2.costo_unitario DESC
											LIMIT 1) %s
ORDER BY codigo_articulo ASC", $sqlBusq, $sqlBusq2);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Fecha");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Costo");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$costoUnitario = ($rowConfig12['valor'] == 1) ? round($row['costo'],3) : round($row['costo_promedio'],3);
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $row['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, implode("-",array_reverse(explode("-",$row['fecha']))));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['nombre_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $costoUnitario);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda'].'"#,##0.00');
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":E".$ultimo);

for ($col = "A"; $col != "E"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "E");

$tituloDcto = "Catálogo de Costos";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:E7");

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