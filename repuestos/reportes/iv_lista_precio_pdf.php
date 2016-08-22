<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idEmpresa = $valCadBusq[0];

$maxRows = 38;
$campOrd = "art.codigo_articulo";
$tpOrd = "ASC";

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.id_empresa = %s",
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

$query = sprintf("SELECT
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
								AND art_precio.id_precio = 1)) AS abreviacion_moneda
	
FROM vw_iv_articulos_empresa vw_iv_art_emp
	INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo) %s", $sqlBusq);

$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
$rsLimit = mysql_query($queryLimit);
if (!$rsLimit) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
if ($totalRows == NULL) {
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
}
$totalPages = ceil($totalRows/$maxRows)-1;

$arrayImg = NULL;
$contFila = 0;
for ($pageNum = 0; $pageNum <= $totalPages; $pageNum++) {
	$startRow = $pageNum * $maxRows;
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit, $conex);
	if (!$rsLimit) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query, $conex);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		if (fmod($contFila, $maxRows) == 1) {
			$img = @imagecreate(614, 403) or die("No se puede crear la imagen");
			
			// ESTABLECIENDO LOS COLORES DE LA PALETA
			$backgroundColor = imagecolorallocate($img, 255, 255, 255);
			$textColor = imagecolorallocate($img, 0, 0, 0);
			$backgroundGris = imagecolorallocate($img, 230, 230, 230);
			$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
			
			$posY = 0;
			imagestring($img,1,0,$posY,str_pad("LISTA DE PRECIOS AL ".date("d-m-Y"), 123, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 0, $posY-4, 619, $posY+4+9, $backgroundGris);
			imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 40, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,320,$posY,str_pad(utf8_decode("UNID. DISP."), 12, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,385,$posY,strtoupper(str_pad(utf8_decode("PVJusto"), 14, " ", STR_PAD_BOTH)),$textColor);
			imagestring($img,1,460,$posY,str_pad(utf8_decode("IMPUESTO"), 14, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,535,$posY,str_pad(utf8_decode("TOTAL"), 16, " ", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
		}
		
		$posY += 9;
		(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 619, $posY+9, $backgroundAzul);
		imagestring($img,1,0,$posY,elimCaracter($row['codigo_articulo'],";"),$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($row['descripcion'],0,40)),$textColor);
		imagestring($img,1,320,$posY,str_pad(valTpDato(number_format($row['cantidad_disponible_fisica'], 2, ".", ","),"cero_por_vacio"), 10, " ", STR_PAD_LEFT),$textColor);-
		imagestring($img,1,385,$posY,str_pad(strtoupper($row['abreviacion_moneda']).valTpDato(number_format($row['precio'], 2, ".", ","),"cero_por_vacio"), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad(strtoupper($row['abreviacion_moneda']).valTpDato(number_format($row['monto_iva'], 2, ".", ","),"cero_por_vacio"), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,535,$posY,str_pad(strtoupper($row['abreviacion_moneda']).valTpDato(number_format($row['precio_total'], 2, ".", ","),"cero_por_vacio"), 16, " ", STR_PAD_LEFT),$textColor);
		
		if (fmod($contFila, $maxRows) == 0 || $contFila == $totalRows) {
			$arrayImg[] = "tmp/"."precio_lista".$pageNum.".png";
			$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
		}
	}
}


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

//$pdf->nombreRegistrado = $row['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 758, 498);
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
	}
}
?>