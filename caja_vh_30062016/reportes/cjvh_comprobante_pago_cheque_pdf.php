<?php
require_once ("../../connections/conex.php");
require_once("../../inc_sesion.php");
session_start();
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
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

$idCheque = $valCadBusq[0];
$idRecibo = $valCadBusq[1];

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
FROM pg_reportesimpresion recibo
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (recibo.id_empleado_creador = vw_pg_empleado.id_empleado)
WHERE (recibo.tipoDocumento LIKE 'CH' AND recibo.idDocumento = %s AND %s IS NULL)
	OR (recibo.idReporteImpresion = %s AND %s IS NOT NULL);",
	valTpDato($idCheque, "int"),
	valTpDato($idRecibo, "int"),
	valTpDato($idRecibo, "int"),
	valTpDato($idRecibo, "int"));
$rsRecibo = mysql_query($queryRecibo);
if (!$rsRecibo) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsRecibo = mysql_num_rows($rsRecibo);
while ($rowRecibo = mysql_fetch_assoc($rsRecibo)) {
	$idRecibo = $rowRecibo['idReporteImpresion'];
	$nroRecibo = $rowRecibo['numeroReporteImpresion'];
	$idCheque = $rowRecibo['idDocumento'];
	
	// DATOS DEL CHEQUE
	$queryCheque = sprintf("SELECT cxc_cheque.*,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.telf
	FROM cj_cc_cheque cxc_cheque
		INNER JOIN cj_cc_cliente cliente ON (cxc_cheque.id_cliente = cliente.id)
	WHERE cxc_cheque.id_cheque = %s;",
		valTpDato($idCheque, "int"));
	$rsCheque = mysql_query($queryCheque);
	if (!$rsCheque) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsCheque = mysql_num_rows($rsCheque);
	$rowCheque = mysql_fetch_assoc($rsCheque);
	
	$idEmpresa = $rowCheque['id_empresa'];
	$nroCheque = $rowCheque['numero_cheque'];
	
	// DATOS DE LA CAJA DEL CHEQUE
	$queryCaja = sprintf("SELECT * FROM caja WHERE caja.idCaja = %s;",
		valTpDato($rowCheque['idCaja'], "int"));
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
	$pdf->Cell(562,5,"COMPROBANTE DE PAGO - CHEQUE",0,0,'C');
	$pdf->Ln(); $pdf->Ln();
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(562,5,"Nro. Recibo: ".$nroRecibo."  Nro. Cheque: ".$nroCheque."",0,0,'C');
	
	$pdf->Ln(); $pdf->Ln();$pdf->Ln(); $pdf->Ln();$pdf->Ln();
	
	$fechaActual = date("Y-m-d");
	
	$pdf->SetFont('Arial','',11);
	$pdf->Cell(380,15,"Id: ".$rowCheque['id_cheque']."",0,0,'L');
	$pdf->Cell(160,15,"".$spanClienteCxC.": "."".$rowCheque['ci_cliente']."",0,0,'L');
	$pdf->Ln();
	$pdf->Cell(380,15,"Cliente: ".$rowCheque['nombre_cliente']."",0,0,'L');
	$pdf->Cell(160,15,"Teléfono: ".$rowCheque['telf']."",0,0,'L');
	
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
		
		
	$pdf->SetFillColor(255,255,255);
	
	$queryBanco = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($rowCheque['id_banco_cliente'], "int"));
	$rsBanco = mysql_query($queryBanco);
	$rowBanco = mysql_fetch_array($rsBanco);
	$nombreBanco = strtoupper(utf8_encode($rowBanco['nombreBanco']));	
	
	$pdf->Cell($arrayTamCol[0],14,date("d-m-Y", strtotime($rowCheque['fecha_cheque'])),'LR',0,'C',true);
	$pdf->Cell($arrayTamCol[1],14,"Cheque",'LR',0,'L',true);
	$pdf->Cell($arrayTamCol[2],14,utf8_encode($rowCheque["numero_cheque"]),'LR',0,'C',true);
	$pdf->Cell($arrayTamCol[3],14,utf8_encode($nombreBanco),'LR',0,'L',true);
	$pdf->Cell($arrayTamCol[4],14,$rowMoneda['abreviacion'].number_format($rowCheque['monto_neto_cheque'],2,".",","),'LR',0,'R',true);
	$pdf->Ln();	
		
	$montoTotal += $rowCheque['monto_neto_cheque'];	
	
	// TOTAL DOCUMENTOS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(300,14,"",'T',0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell(152,14,"TOTAL: ",1,0,'R',true);
	$pdf->Cell(112,14,$rowMoneda['abreviacion'].number_format($montoTotal,2,".",","),1,0,'R',true);
	
	$pdf->SetFont('Arial','',11);
	$pdf->Ln(); $pdf->Ln();$pdf->Ln(); $pdf->Ln();
	$pdf->MultiCell(550,15,"OBSERVACIÓN: ".utf8_decode($rowCheque['observacion_cheque']), 0, 'L');
	$pdf->Cell(20,5,"",0,0,'L');
	$pdf->Cell(150,15,"Hemos recibido de: ".$rowCheque['nombre_cliente']."",0,0,'L');
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