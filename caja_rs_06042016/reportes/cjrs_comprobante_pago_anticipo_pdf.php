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

if ($valCadBusq[0] > 0 && $valCadBusq[1] > 0 && $valCadBusq[2] > 0) {
	$idEmpresa = $valCadBusq[0];
	$idAnticipo = $valCadBusq[1];
	$nroAnticipo = $valCadBusq[2];
	$nroRecibo = $valCadBusq[3];
} else {
	$idAnticipo = $valCadBusq[0];
	$idRecibo = $valCadBusq[1];
}

global $spanClienteCxC;

$updateSQL = sprintf("UPDATE cj_cc_detalleanticipo cxc_ant SET
    id_forma_pago = (SELECT idFormaPago FROM formapagos WHERE aliasFormaPago LIKE cxc_ant.tipoPagoDetalleAnticipo)
WHERE id_forma_pago IS NULL;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$updateSQL = sprintf("UPDATE cj_cc_detalleanticipo cxc_ant SET
    id_reporte_impresion = (SELECT idReporteImpresion FROM pg_reportesimpresion
							WHERE tipoDocumento LIKE 'AN'
								AND idDocumento = cxc_ant.idAnticipo
								AND fechaDocumento = cxc_ant.fechaPagoAnticipo)
WHERE cxc_ant.id_reporte_impresion IS NULL;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

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
FROM pg_reportesimpresion recibo
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (recibo.id_empleado_creador = vw_pg_empleado.id_empleado)
WHERE (recibo.tipoDocumento LIKE 'AN' AND recibo.idDocumento = %s AND %s IS NULL)
	OR (recibo.idReporteImpresion = %s AND %s IS NOT NULL);",
	valTpDato($idAnticipo, "int"),
	valTpDato($idRecibo, "int"),
	valTpDato($idRecibo, "int"),
	valTpDato($idRecibo, "int"));
$rsRecibo = mysql_query($queryRecibo);
if (!$rsRecibo) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsRecibo = mysql_num_rows($rsRecibo);
while ($rowRecibo = mysql_fetch_assoc($rsRecibo)) {
	$idRecibo = $rowRecibo['idReporteImpresion'];
	$nroRecibo = $rowRecibo['numeroReporteImpresion'];
	$idAnticipo = $rowRecibo['idDocumento'];
	
	// DATOS DEL ANTICIPO
	$queryAnticipo = sprintf("SELECT cxc_ant.*, cxc_pago.*,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.telf
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
	WHERE cxc_ant.idAnticipo = %s;",
		valTpDato($idAnticipo, "int"));
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
	
	$idEmpresa = $rowAnticipo['id_empresa'];
	$nroAnticipo = $rowAnticipo['numeroAnticipo'];
	
	// DATOS DE LA CAJA DEL ANTICIPO
	$queryCaja = sprintf("SELECT * FROM caja WHERE caja.idCaja = %s;",
		valTpDato($rowAnticipo['idCaja'], "int"));
	$rsCaja = mysql_query($queryCaja);
	if (!$rsCaja) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowCaja = mysql_fetch_assoc($rsCaja);
	
	// ENCABEZADO EMPRESA
	$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsEmp = mysql_query($queryEmp);
	if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowEmp = mysql_fetch_assoc($rsEmp);
	
	$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
	$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
	$pdf->rif = utf8_encode($spanRIF.": ".$rowEmp['rif']);
	$pdf->direccion = $rowEmp['direccion'];
	$pdf->telefono1 = $rowEmp['telefono1'];
	$pdf->telefono2 = $rowEmp['telefono2'];
	$pdf->web = $rowEmp['web'];
	$pdf->mostrarHeader = 1;
	
	$pdf->AddPage();
	
	$pdf->Ln(); $pdf->Ln(); $pdf->Ln();
	
	//FECHA Y HORA EMISION
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',11);
	$pdf->Ln();
	$pdf->Cell(562,15,$rowCaja['descripcion'],0,0,'C');
	$pdf->Ln(); $pdf->Ln();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(562,15,"Fecha de Emisión: ".date("d-m-Y",strtotime($rowRecibo['fechaDocumento'])),0,0,'R');
	$pdf->Ln();
	$pdf->Cell(562,5,"COMPROBANTE DE PAGO - ANTICIPO",0,0,'C');
	$pdf->Ln(); $pdf->Ln();
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(562,5,"Nro. Recibo: ".$nroRecibo."  Nro. Anticipo: ".$nroAnticipo."",0,0,'C');
	
	$pdf->Ln(); $pdf->Ln();$pdf->Ln(); $pdf->Ln();$pdf->Ln();
	
	$fechaActual = date("Y-m-d");
	
	$pdf->SetFont('Arial','',11);
	$pdf->Cell(380,15,"Id: ".$rowAnticipo['id']."",0,0,'L');
	$pdf->Cell(160,15,"".$spanClienteCxC.": "."".$rowAnticipo['ci_cliente']."",0,0,'L');
	$pdf->Ln();
	$pdf->Cell(380,15,"Cliente: ".$rowAnticipo['nombre_cliente']."",0,0,'L');
	$pdf->Cell(160,15,"Teléfono: ".$rowAnticipo['telf']."",0,0,'L');
	
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
	$queryPago = sprintf("SELECT cxc_pago.*,
		CONCAT_WS(' ', forma_pago.nombreFormaPago, IF(concepto_forma_pago.descripcion IS NOT NULL, CONCAT('(', concepto_forma_pago.descripcion, ')'), NULL)) AS nombreFormaPago,
		concepto_forma_pago.descripcion AS descripcion_concepto_forma_pago,
		banco_cliente.nombreBanco AS nombre_banco_cliente,
		banco_emp.nombreBanco AS nombre_banco_empresa
	FROM cj_cc_detalleanticipo cxc_pago
		LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoClienteDetalleAnticipo = banco_cliente.idBanco)
		LEFT JOIN bancos banco_emp ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_emp.idBanco)
		INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
		LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
	WHERE cxc_pago.id_reporte_impresion = %s;",
		valTpDato($idRecibo, "int"));
	$rsPago = mysql_query($queryPago);
	if (!$rsPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsPago = mysql_num_rows($rsPago);
	$montoTotal = 0;
	while ($rowPago = mysql_fetch_assoc($rsPago)){
		$contFila++;
		
		// RESTAURACION DE COLOR Y FUENTE
		($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
		
		if ($rowPago['tipoPagoDetalleAnticipo'] != 'DP') { // DEPOSITO
			$queryBanco = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
				valTpDato($rowPago['bancoClienteDetalleAnticipo'], "int"));
			$rsBanco = mysql_query($queryBanco);
			$rowBanco = mysql_fetch_array($rsBanco);
			$nombreBanco = strtoupper(utf8_encode($rowBanco['nombreBanco']));
		} else {
			$queryBanco = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
				valTpDato($rowPago['bancoCompaniaDetalleAnticipo'], "int"));
			$rsBanco = mysql_query($queryBanco);
			$rowBanco = mysql_fetch_array($rsBanco);
			$nombreBanco = strtoupper(utf8_encode($rowBanco['nombreBanco']));
		}
		
		$pdf->Cell($arrayTamCol[0],14,date("d-m-Y", strtotime($rowPago['fechaPagoAnticipo'])),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[1],14,utf8_encode($rowPago['nombreFormaPago']),'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[2],14,utf8_encode($rowPago["numeroControlDetalleAnticipo"]),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[3],14,utf8_encode($nombreBanco),'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[4],14,$rowMoneda['abreviacion'].number_format($rowPago['montoDetalleAnticipo'],2,".",","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
			
		$montoTotal += $rowPago['montoDetalleAnticipo'];
	}
	
	// TOTAL DOCUMENTOS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(300,14,"",'T',0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell(152,14,"TOTAL: ",1,0,'R',true);
	$pdf->Cell(112,14,$rowMoneda['abreviacion'].number_format($montoTotal,2,".",","),1,0,'R',true);
	
	$pdf->SetFont('Arial','',11);
	$pdf->Ln(); $pdf->Ln();$pdf->Ln(); $pdf->Ln();
	$pdf->MultiCell(550,15,"OBSERVACIÓN: ".$rowAnticipo['observacionesAnticipo'], 0, 'L');
	$pdf->Cell(20,5,"",0,0,'L');
	$pdf->Cell(150,15,"Hemos recibido de: ".$rowAnticipo['nombre_cliente']."",0,0,'L');
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