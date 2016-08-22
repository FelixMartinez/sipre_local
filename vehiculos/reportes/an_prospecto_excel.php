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

$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa LIKE %s",
		valTpDato($valCadBusq[0], "text"));
}

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
	$sqlBusq .= $cond.sprintf("paga_impuesto = %s ",
		valTpDato($valCadBusq[3], "boolean"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	if ($valCadBusq[4] == 1) {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0
		AND tipo_cuenta_cliente = 1)");
	} else if ($valCadBusq[4] == 2) {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) = 0
		AND tipo_cuenta_cliente = 2)");
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0)
		AND tipo_cuenta_cliente = 2");
	}
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', nombre, apellido) LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
	OR compania LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	cliente.id,
	cliente.tipo,
	cliente.nombre,
	cliente.apellido,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	cliente.telf,
	cliente.credito,
	cliente.status,
	cliente.tipocliente,
	bloquea_venta,
	paga_impuesto,
	perfil_prospecto.compania,
	(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos
FROM cj_cc_cliente cliente
	LEFT JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id) %s
ORDER BY id DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFila, "Id");
$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->SetCellValue("D".$contFila, "Nombre");
$objPHPExcel->getActiveSheet()->SetCellValue("E".$contFila, "Apellido");
$objPHPExcel->getActiveSheet()->SetCellValue("F".$contFila, "Teléfono");
$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFila, "Compañia");
$objPHPExcel->getActiveSheet()->SetCellValue("H".$contFila, "Cant. Modelos");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Paga Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Tipo");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Tipo de Pago");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":K".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['status']) {
		case "Inactivo" : $imgEstatus = "Inactivo"; break;
		case "Activo" : $imgEstatus = "Activo"; break;
		default : $imgEstatus = "Inactivo";
	}
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, $imgEstatus);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $row['id']);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['ci_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['nombre']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['apellido']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['telf']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['compania']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['cantidad_modelos']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, (($row['paga_impuesto'] == 1) ? "SI" : "NO"));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['tipo']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTipoPago[strtoupper($row['credito'])]);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":K".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":K".$ultimo);

for ($col = "A"; $col != "K"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "K");

$tituloDcto = "Prospectos";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:K7");

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