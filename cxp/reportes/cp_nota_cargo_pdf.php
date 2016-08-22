<?php
set_time_limit(0);
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/
$maxRows = 34;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// BUSCA LOS DATOS DEL DOCUMENTO
$queryEncabezado = sprintf("SELECT 
	nota_cargo.id_notacargo,
	nota_cargo.id_empresa,
	nota_cargo.numero_notacargo,
	nota_cargo.numero_control_notacargo,
	nota_cargo.fecha_notacargo,
	nota_cargo.fecha_vencimiento_notacargo,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	prov.nombre AS nombre_proveedor,
	prov.direccion AS direccion_proveedor,
	prov.telefono,
	prov.otrotelf,
	prov_cred.diascredito,
	nota_cargo.tipo_pago_notacargo AS condicionDePago,
	motivo.id_motivo,
	motivo.descripcion AS descripcion_motivo,
	nota_cargo.subtotal_notacargo,
	nota_cargo.subtotal_descuento_notacargo,
	nota_cargo.monto_exento_notacargo,
	nota_cargo.monto_exonerado_notacargo,
	nota_cargo.observacion_notacargo,
	vw_pg_empleado.nombre_empleado
FROM cp_notadecargo nota_cargo
	INNER JOIN cp_proveedor prov ON (nota_cargo.id_proveedor = prov.id_proveedor)
	LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	LEFT JOIN pg_motivo motivo ON (nota_cargo.id_motivo = motivo.id_motivo)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (nota_cargo.id_empleado_creador = vw_pg_empleado.id_empleado)
WHERE nota_cargo.id_notacargo = %s;",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

if ($totalRowsDetalle == 0) {
	$tieneDetalle = false;
	$queryDetalle = sprintf("SELECT
		NULL AS id_subseccion,
		NULL AS codigo_articulo,
		NULL AS descripcion_tipo,
		NULL AS descripcion_articulo,
		NULL AS descripcion_seccion,
		NULL AS cantidad,
		NULL AS precio_unitario,
		NULL AS id_iva,
		NULL AS iva,
		NULL AS id_articulo,
		NULL AS id_factura_detalle");
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	if (fmod($contFila, $maxRows) == 1) {
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		
		$posY = 9;
		imagestring($img,1,300,$posY,str_pad("NOTA DE CARGO", 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. NOTA CARGO"),$textColor);
		imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numero_notacargo'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. CONTROL"),$textColor);
		imagestring($img,1,375,$posY,": ".$rowEncabezado['numero_control_notacargo'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
		imagestring($img,1,375,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fecha_notacargo'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA VENC."),$textColor);
		imagestring($img,1,375,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fecha_vencimiento_notacargo'])),$textColor);
		
		$posY += 9;
		if ($rowEncabezado['condicionDePago'] == 0) { // 0 = Credito, 1 = Contado
			imagestring($img,1,385,$posY,"CRED. ".number_format($rowEncabezado['diascredito'])." DIAS",$textColor);
		}
		
		$posY = 28;
		imagestring($img,1,0,$posY,strtoupper($rowEncabezado['nombre_proveedor']),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($rowEncabezado['rif_proveedor']),$textColor);
		imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id_proveedor'], 8, " ", STR_PAD_LEFT),$textColor);
		
		$direccionCliente = strtoupper(str_replace(",", "", $rowEncabezado['direccion_proveedor']));
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,54,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,108,30)),$textColor);
		imagestring($img,1,155,$posY,utf8_decode("TELEFONO"),$textColor);
		imagestring($img,1,195,$posY,": ".$rowEncabezado['telefono'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,138,30)),$textColor);
		imagestring($img,1,205,$posY,$rowEncabezado['otrotelf'],$textColor);
		
		
		$posY = 90;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 28, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,255,$posY,str_pad(utf8_decode("CANTIDAD"), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,strtoupper(str_pad(utf8_decode($spanPrecioUnitario), 15, " ", STR_PAD_BOTH)),$textColor);
		imagestring($img,1,395,$posY,str_pad(utf8_decode("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	if (isset($tieneDetalle)) {
		$observacion = $rowEncabezado['observacion_notacargo'];
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper(substr($observacion,0,51)),$textColor);
		imagestring($img,1,255,$posY,strtoupper(str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_LEFT)),$textColor);
		imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($rowEncabezado['subtotal_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
		imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowEncabezado['subtotal_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
		if (strlen($observacion) > 51) {
			$posY += 9;
			imagestring($img,1,0,$posY,strtoupper(substr($observacion,51,51)),$textColor);
		}
		if (strlen($observacion) > 102) {
			$posY += 9;
			imagestring($img,1,0,$posY,strtoupper(substr($observacion,102,51)),$textColor);
		}
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['id_motivo'].".- ".$rowEncabezado['descripcion_motivo'],0,51)),$textColor);
	} else {
		$posY += 9;
		imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($rowDetalle['descripcion_articulo'],0,28)),$textColor);
		imagestring($img,1,255,$posY,strtoupper(str_pad(number_format($rowDetalle['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT)),$textColor);
		imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($rowDetalle['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
		imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowDetalle['cantidad'] * $rowDetalle['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	}
		
	if (fmod($contFila, $maxRows) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = 425;
			if ($totalRowsConfig4 > 0) {
				$valor = $rowConfig4['valor'];
				
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,0,94))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,94,188))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,188,282))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,282,376))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,376,470))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,470,564))),$textColor);
			}
			
			$queryGasto = sprintf("SELECT
				nota_cargo_gasto.id_notacargo_gastos,
				nota_cargo_gasto.id_notacargo,
				nota_cargo_gasto.tipo,
				nota_cargo_gasto.porcentaje_monto,
				nota_cargo_gasto.monto,
				nota_cargo_gasto.estatus_iva,
				nota_cargo_gasto.id_iva,
				nota_cargo_gasto.iva,
				gasto.*
			FROM pg_gastos gasto
				INNER JOIN cp_notacargo_gastos nota_cargo_gasto ON (gasto.id_gasto = nota_cargo_gasto.id_gastos)
			WHERE nota_cargo_gasto.id_notacargo = %s;",
				valTpDato($idDocumento, "text"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$posY = 460;
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				$porcentajeGasto = number_format($rowGasto['porcentaje_monto'], 2, ".", ",")."%";
				$gasto = number_format($rowGasto['monto'], 2, ".", ",");
				
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper($rowGasto['nombre']),$textColor);
				imagestring($img,1,90,$posY,":",$textColor);
				imagestring($img,1,100,$posY,str_pad($porcentajeGasto, 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,145,$posY,str_pad($gasto, 15, " ", STR_PAD_LEFT),$textColor);
				
				if ($rowGasto['estatus_iva'] == 0) {
					$totalGastosSinIva += $rowGasto['monto'];
				} else if ($rowGasto['estatus_iva'] == 1) {
					$totalGastosConIva += $rowGasto['monto'];
				}
				
				$totalGasto += $rowGasto['monto'];
			}

			
			$observacion = (isset($tieneDetalle)) ? "" : $rowEncabezado['observacion_notacargo'];
			if (strlen($observacion) > 0 || strlen($rowEncabezado['numero_siniestro']) > 0) {
				$posY += 9;
				imagestring($img,1,0,$posY,"---------------------------------------------------",$textColor);
				if (strlen($observacion) > 0) {
					$posY += 9;
					imagestring($img,1,0,$posY,trim(substr($observacion,0,62)),$textColor);
					$posY += 9;
					imagestring($img,1,0,$posY,trim(substr($observacion,62,62)),$textColor);
				}
				if (strlen($rowEncabezado['numero_siniestro']) > 0) {
					$posY += 9;
					imagestring($img,1,0,$posY,utf8_decode("NRO. SINIESTRO"),$textColor);
					imagestring($img,1,70,$posY,": ".$rowEncabezado['numero_siniestro'],$textColor);
				}
			}
			
			$posY = 460;
			
			$posY += 9;
			imagestring($img,1,255,$posY,"SUB TOTAL",$textColor);
			imagestring($img,1,325,$posY,":",$textColor);
			imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowEncabezado['subtotal_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
			
			$porcDescuento = ($rowEncabezado['subtotal_notacargo'] > 0) ? ($rowEncabezado['subtotal_descuento_notacargo'] * 100) / $rowEncabezado['subtotal_notacargo'] : 0;
			if ($rowEncabezado['subtotal_descuento_notacargo'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"DESCUENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,345,$posY,strtoupper(str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
				imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowEncabezado['subtotal_descuento_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
			}
			
			if ($totalGastosConIva > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"GASTOS C/IMPTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$queryIvaDcto = sprintf("SELECT
				iva.observacion,
				nota_cargo_iva.baseimponible AS base_imponible,
				nota_cargo_iva.iva,
				nota_cargo_iva.subtotal_iva
			FROM cp_notacargo_iva nota_cargo_iva
				INNER JOIN pg_iva iva ON (nota_cargo_iva.id_iva = iva.idIva)
			WHERE nota_cargo_iva.id_notacargo = %s;",
				valTpDato($idDocumento, "text"));
			$rsIvaDcto = mysql_query($queryIvaDcto);
			if (!$rsIvaDcto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			while ($rowIvaDcto = mysql_fetch_assoc($rsIvaDcto)) {
				$posY += 9;
				imagestring($img,1,255,$posY,"BASE IMPONIBLE",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaDcto['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$posY += 9;
				imagestring($img,1,255,$posY,substr($rowIvaDcto['observacion'],0,14),$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,360,$posY,str_pad(number_format($rowIvaDcto['iva'], 2, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaDcto['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$totalIva += $rowIvaDcto['subtotal_iva'];
			}
			
			if ($totalGastosSinIva > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"GASTOS S/IMPTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor); // <---
			}
			
			if ($rowEncabezado['monto_exento_notacargo'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"EXENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowEncabezado['monto_exento_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
			}
			
			$posY += 7;
			imagestring($img,1,255,$posY,str_pad("", 43, "-", STR_PAD_LEFT),$textColor);
			
			$totalFactura = $rowEncabezado['subtotal_notacargo'] - $rowEncabezado['subtotal_descuento_notacargo'] + $totalIva + $totalGastosSinIva + $totalGastosConIva;
			$posY += 7;
			imagestring($img,1,255,$posY,"TOTAL",$textColor);
			imagestring($img,1,325,$posY,":",$textColor);
			imagestring($img,2,380,$posY,strtoupper(str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."nota_cargo".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
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

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		// CABECERA DEL DOCUMENTO 
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
				$pdf->Cell(200,9,utf8_encode($spanRIF.": ".$rowEmp['rif']),0,2,'L');
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
				$pdf->Cell(200,9,utf8_encode($rowEmp['web']),0,0,'L');
				$pdf->Ln();
			}
		}
		//$pdf->SetY(-20);
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 690);
		
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',7);
		$pdf->Cell(0,8,((strlen($rowEncabezado['nombre_empleado']) > 0) ? "Registrado por: ".$rowEncabezado['nombre_empleado'] : ""),0,0,'L');
		$pdf->Cell(0,8,"Impreso: ".date("d-m-Y h:i a"),0,0,'R');
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',8);
		$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
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