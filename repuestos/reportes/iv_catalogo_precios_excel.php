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

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT posee_iva FROM iv_articulos art
	WHERE id_articulo = vw_iv_art_emp.id_articulo) = %s",
		valTpDato($valCadBusq[1], "text"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_articulo = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("clasificacion LIKE %s",
		valTpDato($valCadBusq[3], "text"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != ""
&& ($valCadBusq[5] == "-1" || $valCadBusq[5] == "")) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_fisica > 0");
}

if (($valCadBusq[4] == "-1" || $valCadBusq[4] == "")
&& $valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_fisica <= 0");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[6], "text"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(id_articulo = %s
	OR descripcion LIKE %s
	OR codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[7], "int"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT *,
	(SELECT posee_iva FROM iv_articulos art
	WHERE id_articulo = vw_iv_art_emp.id_articulo) AS posee_iva
	
FROM vw_iv_articulos_empresa vw_iv_art_emp %s
ORDER BY codigo_articulo ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Aplica Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Unid. Disponible");
$queryCantidadPrecios = "SELECT * FROM pg_precios WHERE id_precio NOT IN (6,7) AND estatus = 1 ORDER BY id_precio ASC;";
$rsPrecio = mysql_query($queryCantidadPrecios);
if (!$rsPrecio) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$totalRowsPrecio = mysql_num_rows($rsPrecio);
$col1 = "E";
while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
	$col1++;
	$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, $rowPrecio['descripcion_precio']);
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col1.$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$queryPrecio = sprintf("SELECT * FROM iv_articulos_precios art_precio
	WHERE art_precio.id_articulo = %s
		AND art_precio.id_empresa = %s
		AND (SELECT estatus FROM pg_precios precio
			WHERE precio.id_precio = art_precio.id_precio) = 1
	ORDER BY art_precio.id_precio ASC;",
		valTpDato($row['id_articulo'], "int"),
		valTpDato($row['id_empresa'], "int"));
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) die(mysql_error()."<br><br>Line: ".__LINE__);
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, (($row['posee_iva'] == 1) ? "Si" : "No"));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, $row['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['clasificacion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['cantidad_disponible_fisica']);
	$col2 = "E";
	while ($rowPrecio = mysql_fetch_array($rsPrecio)) {
		$col2++;
		$objPHPExcel->getActiveSheet()->setCellValue($col2.$contFila, $rowPrecio['precio']);
		
		$objPHPExcel->getActiveSheet()->getStyle($col2.$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col1.$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":".$col1.$ultimo);

for ($col = "A"; $col != $col1; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, $col1);

$tituloDcto = "Catálogo de Precios";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:".$col1."7");

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