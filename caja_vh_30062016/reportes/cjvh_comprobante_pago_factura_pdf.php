<?php
require_once ("../../connections/conex.php");
require_once("../../inc_sesion.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

include('../../clases/num2letras.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("24","20","24");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
//$pdf->nombreRegistrado = $_SESSION['nombreEmpleadoSysGts'];

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

if ($valCadBusq[0] > 0 && $valCadBusq[1] > 0 && $valCadBusq[2] > 0 && $valCadBusq[3] > 0) {
	$idEmpresa = $valCadBusq[0];
	$nroFactura = $valCadBusq[1];
	$nroRecibo = $valCadBusq[2];
	$idFactura = $valCadBusq[3];
	
	header(sprintf("location: cjrs_recibo_pago_pdf.php?idTpDcto=1&id=%s", $idFactura));
} else if ($valCadBusq[0] > 0 && $valCadBusq[1] > 0 && $valCadBusq[2] > 0) {
	$idEmpresa = $valCadBusq[0];
	$idFactura = $valCadBusq[1];
	$nroRecibo = $valCadBusq[2];
	
	header(sprintf("location: cjrs_recibo_pago_pdf.php?idTpDcto=1&id=%s", $idFactura));
} else {
	$idFactura = $valCadBusq[0];
	$idRecibo = $valCadBusq[1];
	
	header(sprintf("location: cjrs_recibo_pago_pdf.php?idRecibo=%s", $idRecibo));
}

global $spanClienteCxC;

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// COMPROBANTE DE PAGO ///////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
$rsMoneda = mysql_query($queryMoneda);
if (!$rsMoneda) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowMoneda = mysql_fetch_assoc($rsMoneda);

// DATOS DEL RECIBO
$queryRecibo = sprintf("SELECT recibo.*,
	vw_pg_empleado.nombre_empleado
FROM cj_encabezadorecibopago recibo
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (recibo.id_empleado_creador = vw_pg_empleado.id_empleado)
WHERE (recibo.idTipoDeDocumento = 1 AND recibo.numero_tipo_documento = %s AND %s IS NULL)
	OR (recibo.idComprobante = %s AND %s IS NOT NULL);",
	valTpDato($idFactura, "int"),
	valTpDato($idRecibo, "int"),
	valTpDato($idRecibo, "int"),
	valTpDato($idRecibo, "int"));
$rsRecibo = mysql_query($queryRecibo);
if (!$rsRecibo) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsRecibo = mysql_num_rows($rsRecibo);
while ($rowRecibo = mysql_fetch_assoc($rsRecibo)) {
	$idRecibo = $rowRecibo['idComprobante'];
	$nroRecibo = $rowRecibo['numeroComprobante'];
	$idFactura = $rowRecibo['numero_tipo_documento'];
	
	// DATOS DE LA FACTURA
	$queryFactura = sprintf("SELECT
		forma_pago.idFormaPago,
		forma_pago.nombreFormaPago,
		pago.idPago,
		pago.numeroDocumento,
		pago.fechaPago,
		pago.bancoOrigen,
		pago.bancoDestino,
		pago.cuentaEmpresa,
		pago.montoPagado,
		pago.tomadoEnComprobante,
		pago.idCaja,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.direccion,
		cliente.estado,
		cliente.telf,
		banco.nombreBanco,
		cxc_fact.idFactura,
		cxc_fact.id_empresa,
		cxc_fact.numeroFactura,
		cxc_fact.observacionFactura
	FROM formapagos forma_pago
		INNER JOIN an_pagos pago ON (forma_pago.idFormaPago = pago.formaPago)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (pago.id_factura = cxc_fact.idFactura)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		LEFT JOIN bancos banco ON (pago.bancoOrigen = banco.idBanco)
	WHERE pago.id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = $rowFactura['id_empresa'];
	$nroFactura = $rowFactura['numeroFactura'];
	
	// DATOS DE LA CAJA DEL ANTICIPO
	$queryCaja = sprintf("SELECT * FROM caja WHERE caja.idCaja = %s;",
		valTpDato($rowFactura['idCaja'], "int"));
	$rsCaja = mysql_query($queryCaja);
	if (!$rsCaja) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowCaja = mysql_fetch_assoc($rsCaja);
	
	// ENCABEZADO EMPRESA
	$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$pdf->AddPage();
	
	// CABECERA DEL DOCUMENTO
	if ($idEmpresa != "") {
		$pdf->Image("../../".$rowEmpresa['logo_familia'],15,17,80);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',9);
		$pdf->SetX(100);
		$pdf->Cell(200,9,($rowEmpresa['nombre_empresa']),0,2,'L');
		
		if (strlen($rowEmpresa['rif']) > 1) {
			$pdf->SetX(100);
			$pdf->Cell(200,9,($spanRIF.": ".$rowEmpresa['rif']),0,2,'L');
		}
		if (strlen($rowEmpresa['direccion']) > 1) {
			$pdf->SetX(100);
			$pdf->Cell(100,9,($rowEmpresa['direccion']),0,2,'L');
		}
		if (strlen($rowEmpresa['web']) > 1) {
			$pdf->SetX(100);
			$pdf->Cell(200,9,($rowEmpresa['web']),0,0,'L');
			$pdf->Ln();
		}
	}
	
	$pdf->Ln(); $pdf->Ln(); $pdf->Ln();
	
	//FECHA Y HORA EMISION
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',11);
	$pdf->Ln();
	$pdf->Cell(562,15,$rowCaja['descripcion'],0,0,'C');
	$pdf->Ln(); $pdf->Ln();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(562,15,"Fecha de Emisión: ".date("d-m-Y",strtotime($rowRecibo['fechaComprobante'])),0,0,'R');
	$pdf->Ln();
	$pdf->Cell(562,5,"COMPROBANTE DE PAGO - FACTURA",0,0,'C');
	$pdf->Ln(); $pdf->Ln();
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(562,5,"Nro. Recibo: ".$nroRecibo."  Nro. Factura: ".$nroFactura."",0,0,'C');
	
	$pdf->Ln(); $pdf->Ln();$pdf->Ln(); $pdf->Ln();$pdf->Ln();
	
	$fechaActual = date("Y-m-d");
	
	$pdf->SetFont('Arial','',11);
	$pdf->Cell(380,15,"Id: ".$rowFactura['id']."",0,0,'L');
	$pdf->Cell(160,15,"".$spanClienteCxC.": "."".$rowFactura['ci_cliente']."",0,0,'L');
	$pdf->Ln();
	$pdf->Cell(380,15,"Cliente: ".$rowFactura['nombre_cliente']."",0,0,'L');
	$pdf->Cell(160,15,"Teléfono: ".$rowFactura['telf']."",0,0,'L');
	
	$pdf->Ln(); $pdf->Ln();
	/* COLUMNAS */
	// COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
	$pdf->SetFillColor(204,204,204);
	$pdf->SetTextColor(0,0,0);
	$pdf->SetDrawColor(153,153,153);
	$pdf->SetLineWidth(1);
	$pdf->SetFont('Arial','',8);
	
	// ENCABEZADO DE LA TABLA
	$arrayTamCol = array("76","132","92","152","112");
	$arrayCol = array("FECHA PAGO","FORMA DE PAGO","NRO. REFERENCIA","BANCO","IMPORTE");
	
	$posY = $pdf->GetY();
	$posX = $pdf->GetX();
	foreach ($arrayCol as $indice => $valor) {
		$pdf->SetY($posY);
		$pdf->SetX($posX);
		
		$pdf->MultiCell($arrayTamCol[$indice], 16, $valor, 1, 'C', true);
		
		$posX += $arrayTamCol[$indice];
	}
	
	// RESTAURACION DE COLORES Y FUENTES
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('');
	
	//$pdf->SetFillColor(234,244,255); // blanco
	$pdf->SetFillColor(255,255,255); // azul
	
	// DETALLE DE LOS PAGOS
	$queryPago = sprintf("SELECT *
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
		LEFT JOIN bancos banco ON (cxc_pago.bancoOrigen = banco.idBanco)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
		INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND recibo.idTipoDeDocumento = 1)
	WHERE recibo_det.idComprobantePagoFactura = %s;",
		valTpDato($idRecibo, "int"));
	$rsPago = mysql_query($queryPago);
	if (!$rsPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsPago = mysql_num_rows($rsPago);
	$montoTotal = 0;
	while ($rowPago = mysql_fetch_assoc($rsPago)){
		$contFila++;
		
		// RESTAURACION DE COLOR Y FUENTE
		($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
		
		$descripcionConcepto = "";
		if ($rowPago["idFormaPago"] == 9) { // RETENCION
			$numeroDcto = $rowPago["numeroDocumento"];
		} else if ($rowPago["idFormaPago"] == 7) { // ANTICIPO
			$queryAnticipo = sprintf("SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = %s;",
				valTpDato($rowPago['numeroDocumento'], "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$rowAnticipo = mysql_fetch_array($rsAnticipo);
			$numeroDcto = $rowAnticipo["numeroAnticipo"];
						
			// CONSULTO EN CONCEPTO DEL ANTICIPO
			$queryDetAnticipo = sprintf("SELECT id_concepto FROM cj_cc_detalleanticipo WHERE idAnticipo = %s;",
				valTpDato($rowPago['numeroDocumento'], "int"));;
			$rsDetAnticipo = mysql_query($queryDetAnticipo);
			if (!$rsDetAnticipo) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowDetAnticipo = mysql_fetch_array($rsDetAnticipo);
			$idConceptoAnticipo = $rowDetAnticipo["id_concepto"];
						
			$queryConcepto = sprintf("SELECT * FROM cj_conceptos_formapago WHERE id_concepto = %s",
				valTpDato($idConceptoAnticipo, "int"));
			$rsConcepto = mysql_query($queryConcepto);
			if (!$rsConcepto) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowConcepto = mysql_fetch_assoc($rsConcepto);
			
			if ($idConceptoAnticipo > 0) {
				$descripcionConcepto = " (".$rowConcepto['descripcion'].")";
			}
		} else {
			$numeroDcto = $rowPago["numeroDocumento"];
		}
		
		$tipoPago = $rowPago['nombreFormaPago'].$descripcionConcepto;
		
		if ($rowPago['idFormaPago'] != 3) { // DEPOSITO
			$queryBanco = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
				valTpDato($rowPago['bancoOrigen'], "int"));
			$rsBanco = mysql_query($queryBanco);
			$rowBanco = mysql_fetch_array($rsBanco);
			$nombreBanco = strtoupper(utf8_encode($rowBanco['nombreBanco']));
		} else {
			$queryBanco = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
				valTpDato($rowPago['bancoDestino'], "int"));
			$rsBanco = mysql_query($queryBanco);
			$rowBanco = mysql_fetch_array($rsBanco);
			$nombreBanco = strtoupper(utf8_encode($rowBanco['nombreBanco']));
		}
		
		$pdf->Cell($arrayTamCol[0],14,date("d-m-Y", strtotime($rowPago['fechaPago'])),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[1],14,utf8_encode($tipoPago),'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[2],14,utf8_encode($numeroDcto),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[3],14,utf8_encode($nombreBanco),'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[4],14,$rowMoneda['abreviacion'].number_format($rowPago['montoPagado'],2,".",","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
			
		$montoTotal += $rowPago['montoPagado'];
	}
	
	// TOTAL DOCUMENTOS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(300,14,"",'T',0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell(152,14,"TOTAL: ",1,0,'R',true);
	$pdf->Cell(112,14,$rowMoneda['abreviacion'].number_format($montoTotal,2,".",","),1,0,'R',true);
	
	$pdf->SetFont('Arial','',11);
	$pdf->Ln(); $pdf->Ln();$pdf->Ln(); $pdf->Ln();
	$pdf->MultiCell(550,15,"OBSERVACIÓN: ".$rowFactura['observacionFactura'], 0, 'L');
	$pdf->Cell(20,5,"",0,0,'L');
	$pdf->Cell(150,15,"Hemos recibido de: ".$rowFactura['nombre_cliente']."",0,0,'L');
	$pdf->Ln();
	$pdf->Cell(20,5,"",0,0,'L');
	$pdf->MultiCell(550, 15, "La Cantidad de: ".utf8_decode(strtoupper(num2letras($montoTotal,false,true,$rowMoneda['descripcion']))), 0, 'L');
	$pdf->Ln();
	
	$queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado
	WHERE vw_pg_empleado.id_empleado = %s",
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowEmpleado = mysql_fetch_array($rsEmpleado);
	
	$pdf->Cell(0,230,"Emitido por: ".utf8_decode((strlen($rowRecibo['nombre_empleado']) > 0) ? $rowRecibo['nombre_empleado'] : $rowEmpleado["nombre_empleado"]),0,0,'C');
	
	$pdf->Cell(array_sum($arrayTamCol),0,'','T');
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>