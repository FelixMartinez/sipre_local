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
	$sqlBusq .= $cond.sprintf("(vw_iv_art_emp.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vw_iv_art_emp.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.posee_iva = %s",
		valTpDato($valCadBusq[1], "text"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.clasificacion LIKE %s",
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
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[6], "text"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[7], "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	vw_iv_art_emp.id_empresa,
	vw_iv_art_emp.id_articulo,
	vw_iv_art_emp.codigo_articulo,
	vw_iv_art_emp.descripcion,
	art.posee_iva,
	vw_iv_art_emp.cantidad_disponible_fisica,
	
	(SELECT art_precio.precio FROM iv_articulos_precios art_precio
	WHERE art_precio.id_articulo = art.id_articulo
		AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
		AND art_precio.id_precio = 1) AS precio,
	
	(CASE art.posee_iva
		WHEN 1 THEN
			((SELECT SUM(iva) FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1)
				* (SELECT art_precio.precio FROM iv_articulos_precios art_precio
					WHERE art_precio.id_articulo = art.id_articulo
						AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
						AND art_precio.id_precio = 1)) / 100
		ELSE
			0
	END) AS monto_iva,
	
	(CASE art.posee_iva
		WHEN 1 THEN
			(SELECT art_precio.precio FROM iv_articulos_precios art_precio
			WHERE art_precio.id_articulo = art.id_articulo
				AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
				AND art_precio.id_precio = 1) + (((SELECT SUM(iva) FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1)
													* (SELECT art_precio.precio FROM iv_articulos_precios art_precio
													WHERE art_precio.id_articulo = art.id_articulo
														AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
														AND art_precio.id_precio = 1)) / 100)
		ELSE
			(SELECT art_precio.precio FROM iv_articulos_precios art_precio
			WHERE art_precio.id_articulo = art.id_articulo
				AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
				AND art_precio.id_precio = 1)
	END) AS precio_total,
	
	(SELECT moneda.abreviacion FROM pg_monedas moneda
	WHERE moneda.idmoneda = (SELECT art_precio.id_moneda FROM iv_articulos_precios art_precio
							WHERE art_precio.id_articulo = art.id_articulo
								AND art_precio.id_empresa = vw_iv_art_emp.id_empresa
								AND art_precio.id_precio = 1)) AS abreviacion_moneda,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_articulos_empresa vw_iv_art_emp
	INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_art_emp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY art.codigo_articulo ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, ".");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Unid. Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $spanPrecioUnitario);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Precio Total");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":H".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFilaColor, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	$contFilaColor++;
	
	$imgAplicaIva = ($row['posee_iva'] == 1) ? "Si Aplica Impuesto" : "";
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgAplicaIva);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, elimCaracter(utf8_encode($row['codigo_articulo']),";"));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['descripcion']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['cantidad_disponible_fisica']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['precio']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['monto_iva']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['precio_total']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":H".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda'].'"#,##0.00');
}
$ultFila = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":H".$ultFila);

for ($col = "A"; $col != "H"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "H");

$tituloDcto = "Listado de Precios";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:H7");

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