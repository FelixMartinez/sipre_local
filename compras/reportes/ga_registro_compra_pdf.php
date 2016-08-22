<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/
$maxRows = 17;
$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

$queryEncabezado = sprintf("SELECT
  cp_factura.id_factura, cp_factura.id_empresa,cp_factura.id_modo_compra,numero_factura_proveedor,numero_control_factura,
  cp_factura.id_proveedor,fecha_factura_proveedor,fecha_origen,cp_factura.id_modulo,monto_exento,monto_exonerado, subtotal_factura,subtotal_descuento,
  nombre_empresa,
  nombre,cp_proveedor.rif,telefono,cp_proveedor.direccion,cp_proveedor.correo,cp_proveedor.fax,contacto
FROM cp_factura
  INNER JOIN pg_empresa ON pg_empresa.id_empresa = cp_factura.id_empresa
  INNER JOIN cp_proveedor ON cp_proveedor.id_proveedor = cp_factura.id_proveedor
WHERE id_factura = %s;",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];
$idModoCompra = $rowEncabezado['id_modo_compra'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$queryClaveMov = sprintf("SELECT
	clave_mov.id_clave_movimiento,
	clave_mov.descripcion
FROM pg_clave_movimiento clave_mov
  INNER JOIN ga_movimiento mov ON (clave_mov.id_clave_movimiento = mov.id_clave_movimiento)
WHERE clave_mov.tipo = 1
	AND mov.id_documento = %s;",
	valTpDato($idDocumento,"int"));
$rsClaveMov = mysql_query($queryClaveMov, $conex);
if (!$rsClaveMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowClaveMov = mysql_fetch_assoc($rsClaveMov);

$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

//ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

$posY = 0;
imagestring($img,1,160,$posY,str_pad("REGISTRO DE COMPRA", 62, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,320,$posY,utf8_decode("ID REG. COMPRA"),$textColor);
imagestring($img,1,390,$posY,": ".$rowEncabezado['id_factura'],$textColor);

$posY += 9;
imagestring($img,1,320,$posY,utf8_decode("FECHA"),$textColor);
imagestring($img,1,390,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fecha_origen'])),$textColor);

$posY += 9;
imagestring($img,1,160,$posY,utf8_decode("FACT. PROV. NRO."),$textColor);
imagestring($img,1,245,$posY,": ".$rowEncabezado['numero_factura_proveedor'],$textColor);
imagestring($img,1,320,$posY,utf8_decode("NRO. CONTROL"),$textColor);
imagestring($img,1,390,$posY,": ".$rowEncabezado['numero_control_factura'],$textColor);

$posY += 9;
imagestring($img,1,160,$posY,utf8_decode("FECHA FACT. PROV."),$textColor);
imagestring($img,1,245,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fecha_factura_proveedor'])),$textColor);

$posY += 9;
imagestring($img,1,160,$posY,utf8_decode("TIPO"),$textColor);////////////////////////////////////////////////////
imagestring($img,1,245,$posY,": "."COMPRA",$textColor);
imagestring($img,1,320,$posY,utf8_decode("CLAVE"),$textColor);////////////////////////////////////////////////////
imagestring($img,1,390,$posY,": ".strtoupper($rowClaveMov['descripcion']),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,str_pad(("DATOS DEL PROVEEDOR"), 94, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,("RAZÓN SOCIAL"),$textColor);
imagestring($img,1,60,$posY,": ".strtoupper($rowEncabezado['nombre']),$textColor);
imagestring($img,1,310,$posY,($spanProvCxP),$textColor);//"R.I.F."
imagestring($img,1,380,$posY,": ".strtoupper($rowEncabezado['rif']),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,("CONTACTO"),$textColor);/////////////////////////////////////////
imagestring($img,1,45,$posY,": ".strtoupper(substr($rowEncabezado['contacto'],0,30)),$textColor);
imagestring($img,1,310,$posY,("EMAIL"),$textColor);///////////////////////////////////////////////////
imagestring($img,1,350,$posY,": ".strtoupper($rowEncabezado['correo']),$textColor);

$direccionProveedor = strtoupper(str_replace(",", " ", $rowEncabezado['direccion']));
$posY += 9;
imagestring($img,1,0,$posY,("DIRECCIÓN"),$textColor);/////////////////////////////////////////
imagestring($img,1,45,$posY,": ".trim(substr($direccionProveedor,0,48)),$textColor);
imagestring($img,1,310,$posY,("TELÉFONO"),$textColor);///////////////////////////////////////////////////
imagestring($img,1,350,$posY,": ".$rowEncabezado['telefono'],$textColor);

$posY += 9;
imagestring($img,1,55,$posY,trim(substr($direccionProveedor,48,48)),$textColor);
imagestring($img,1,310,$posY,("FAX"),$textColor);///////////////////////////////////////////////////
imagestring($img,1,350,$posY,": ".$rowEncabezado['fax'],$textColor);

$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad(("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,115,$posY,str_pad(("DESCRIPCIÓN"), 27, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,255,$posY,str_pad(("RECIB."), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,290,$posY,str_pad(("PRECIO UNIT."), 13, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(("%IMPTO"), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,395,$posY,str_pad(("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

//DETALLES DE LOS ARTICULOS
$queryDetalle = sprintf("SELECT cp_factura_detalle.id_factura_detalle,id_factura,id_pedido_compra,cantidad,pendiente,precio_unitario,
			(SELECT SUM(iva) AS iva
				FROM ga_factura_detalle_iva 
			WHERE ga_factura_detalle_iva.id_factura_detalle = cp_factura_detalle.id_factura_detalle
			GROUP BY ga_factura_detalle_iva.id_factura_detalle) AS iva, 
		codigo_articulo,descripcion
	FROM cp_factura_detalle
		INNER JOIN ga_articulos ON cp_factura_detalle.id_articulo = ga_articulos.id_articulo
	WHERE cp_factura_detalle.id_factura = %s GROUP BY cp_factura_detalle.id_articulo;",
	valTpDato($idDocumento,"int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {//MODIFICAR POR LA CANTIDAD DE IVAS QUE TENGA EL ART
	$pedidos = $rowDetalle['cantidad'];
	$recibidos = $rowDetalle['cantidad'] /*- $rowDetalle['pendiente']*/;
	$pendiente = $rowDetalle['pendiente'];
	$precioUnit = $rowDetalle['precio_unitario'];
	$total = ($recibidos * $precioUnit);
	
	$porcIva = ($rowDetalle['iva'] > 0) ? number_format($rowDetalle['iva'], 2, ".", ",") : "-";
	
	$posY += 9;
	imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'], ";"),$textColor);
	imagestring($img,1,115,$posY,strtoupper(substr($rowDetalle['descripcion'],0,27)),$textColor);
	imagestring($img,1,255,$posY,str_pad(number_format($recibidos, 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,290,$posY,str_pad(number_format($precioUnit, 2, ".", ","), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,360,$posY,str_pad($porcIva, 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
}

//CONSUTLA LOS GASTO DE LA FACTURA
$queryGasto = sprintf("SELECT
	cp_factura_gasto.id_factura_gasto,
	id_factura,
	cp_factura_gasto.id_gasto,nombre,
	cp_factura_gasto.estatus_iva,cp_factura_gasto.id_modo_gasto,cp_factura_gasto.afecta_documento,
	ga_factura_detalle_iva_gasto.id_iva, ga_factura_detalle_iva_gasto.iva,
		(ga_factura_detalle_iva_gasto.porcentaje_monto) AS porcentaje_monto_gasto,
		(ga_factura_detalle_iva_gasto.monto) AS monto_gasto
	FROM cp_factura_gasto
		INNER JOIN ga_factura_detalle_iva_gasto ON cp_factura_gasto.id_factura_gasto = ga_factura_detalle_iva_gasto.id_factura_gasto
		INNER JOIN pg_gastos ON pg_gastos.id_gasto = cp_factura_gasto.id_gasto
	WHERE id_factura = %s GROUP BY id_gasto;",
	valTpDato($idDocumento, "int"));
$rsGasto = mysql_query($queryGasto);
if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$posY = 460;
while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
	$porcGasto = $rowGasto['porcentaje_monto_gasto'];
	$montoGasto = $rowGasto['monto_gasto'];
	
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(substr($rowGasto['nombre'],0,25)),$textColor);
	imagestring($img,1,130,$posY,":",$textColor);
	imagestring($img,1,140,$posY,str_pad(number_format($porcGasto, 2, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,175,$posY,str_pad(number_format($montoGasto, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
	if ($rowGasto['id_iva'] > 0) {
		$totalGastosConIva += $rowGasto['monto_gasto'];
	} else if ($rowGasto['id_iva'] == 0 || $rowGasto['id_iva'] == "" || $rowGasto['id_iva'] == NULL) {
		$totalGastosSinIva += $rowGasto['monto_gasto'];
	}
	
	$totalGasto += $rowGasto['monto_gasto'];
}


$posY = 440;

$subTotal = number_format($rowEncabezado['subtotal_factura'], 2, ".", ",");
$posY += 9;
imagestring($img,1,255,$posY,"SUB-TOTAL",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,1,395,$posY,str_pad($subTotal, 15, " ", STR_PAD_LEFT),$textColor);

$descuento = number_format($rowEncabezado['subtotal_descuento'],2,".",",");
$posY += 9;
imagestring($img,1,255,$posY,"DESCUENTO",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,1,395,$posY,str_pad($descuento, 15, " ", STR_PAD_LEFT),$textColor);

$gastosConIva = number_format($totalGastosConIva,2,".",",");
$posY += 9;
imagestring($img,1,255,$posY,"GASTOS C/IMPTO",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,1,395,$posY,str_pad($gastosConIva, 15, " ", STR_PAD_LEFT),$textColor);

$queryIvaFact = sprintf("SELECT
	iva.observacion,
	fact_comp_iva.base_imponible,
	fact_comp_iva.iva,
	fact_comp_iva.subtotal_iva
FROM cp_factura_iva fact_comp_iva
	INNER JOIN pg_iva iva ON (fact_comp_iva.id_iva = iva.idIva)
WHERE id_factura = %s;",
	valTpDato($idDocumento, "int"));
$rsIvaFact = mysql_query($queryIvaFact);
if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
	/*$posY += 9;
	imagestring($img,1,255,$posY,"BASE IMPONIBLE",$textColor);
	imagestring($img,1,325,$posY,":",$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);*/
	
	$posY += 9;
	imagestring($img,1,255,$posY,substr($rowIvaFact['observacion'],0,14),$textColor);
	imagestring($img,1,325,$posY,":",$textColor);
	imagestring($img,1,380,$posY,str_pad(number_format($rowIvaFact['iva'], 0, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,340,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
	$totalIva += $rowIvaFact['subtotal_iva'];
}

$gastosSinIva = number_format($totalGastosSinIva,2,".",",");
$posY += 9;
imagestring($img,1,255,$posY,"GASTOS S/IMPTO",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,1,395,$posY,str_pad($gastosSinIva, 15, " ", STR_PAD_LEFT),$textColor); // <---
			
$posY += 9;
$montoExento = $rowEncabezado['monto_exento'] - ($totalGastosSinIvaOrigen + $totalGastosSinIvaLocal);
imagestring($img,1,255,$posY,"MONTO EXENTO",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,1,395,$posY,str_pad(number_format($montoExento, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor); // <---

$posY += 8;
imagestring($img,1,255,$posY,str_pad("", 54, "-", STR_PAD_LEFT),$textColor);

$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $totalIva+$totalGasto;
$montoTotalFactura = number_format($totalFactura,2,".",",");
$posY += 8;
imagestring($img,1,255,$posY,"TOTAL FACTURA",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,1,395,$posY,str_pad($montoTotalFactura, 15, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."registro_compra".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10
	AND config_emp.status = 1
	AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

$queryNomEmpleado = sprintf("SELECT id_empleado_creador, CONCAT_WS(' ',nombre_empleado, apellido) AS Nombre_empleado 
						FROM cp_factura
					LEFT JOIN pg_empleado ON pg_empleado.id_empleado = cp_factura.id_empleado_creador
						WHERE id_factura = %s;", valTpDato($idDocumento,"int" ));
$rsEmpleado = mysql_query($queryNomEmpleado, $conex);
if (!$rsEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmpleado = mysql_fetch_assoc($rsEmpleado);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		//CABECERA DEL DOCUMENTO 
		if ($idEmpresa != "") {
			if (strlen($rowEmp['logo_familia']) > 5) {
				$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
			}
			
			$pdf->SetY(15);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',5);
			$pdf->SetX(88);
			$pdf->Cell(200,9,$rowEmp['nombre_empresa'],0,2,'L');
			
			if (strlen($rowEmp['rif']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,htmlentities("R.I.F.: ".$rowEmp['rif']),0,2,'L');
			}
			if (strlen($rowEmp['direccion']) > 1) {
				$direcEmpresa = $rowEmp['direccion'].".";
				$telfEmpresa = "";
				if (strlen($rowEmp['telefono1']) > 1) {
					$telfEmpresa .= "Telf.: ".$rowEmp['telefono1'];
				}
				if (strlen($rowEmp['telefono2']) > 1) {
					$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
					$telfEmpresa .= $rowEmp['telefono2'];
				}
				
				$pdf->SetX(88);
				$pdf->Cell(100,9,$direcEmpresa." ".$telfEmpresa,0,2,'L');
			}
			if (strlen($rowEmp['web']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,htmlentities($rowEmp['web']),0,0,'L');
				$pdf->Ln();
			}
		}
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 690);
		
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',6);
		$pdf->Cell(0,8,"Impreso: ".date("d-m-Y h:i a"),0,0,'R');
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',8);
		$pdf->Cell(0,10,("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',8);
		$pdf->Cell(0,8,"Impreso por: ".$rowEmpleado['Nombre_empleado'],0,0,'L');
		
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>