<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
require_once ("../../connections/conex.php");

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

$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("credito LIKE %s",
		valTpDato($valCadBusq[1], "text"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status LIKE %s",
		valTpDato($valCadBusq[2], "text"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CONCAT(lrif,'-',rif) LIKE %s
	OR CONCAT(lrif,'',rif) LIKE %s
	OR nombre LIKE %s)",
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	id_proveedor,
	tipo,
	CONCAT_WS('-', lrif, rif) AS rif,
	nombre,
	telefono,
	correo,
	credito,
	status
FROM cp_proveedor %s
ORDER BY id_proveedor DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFila, $spanProvCxP);
$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFila, "Nombre");
$objPHPExcel->getActiveSheet()->SetCellValue("D".$contFila, "Teléfono");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Correo");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Tipo de Pago");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":F".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['status']) {
		case "Inactivo" : $imgEstatus = "Inactivo"; break;
		case "Activo" : $imgEstatus = "Activo"; break;
		default : $imgEstatus = "Inactivo";
	}
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, $imgEstatus);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $row['rif']);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['nombre']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['telefono']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['correo']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTipoPago[strtoupper($row['credito'])]);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":F".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":F".$ultimo);

for ($col = 'A'; $col <= 'F'; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "F");

$tituloDcto = "Proveedores";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:F7");

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
$objPHPExcel->getSecurity()->setLockWindows(true);
$objPHPExcel->getSecurity()->setLockStructure(true);

$objPHPExcel->getProperties()->setCreator("SIPRE 2.0");
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