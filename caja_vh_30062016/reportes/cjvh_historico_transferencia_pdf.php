<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

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

$idEmpresa = $valCadBusq[0];

//PUEDE SER NULL AL SELECCIONAR [TODOS] EN LA BUSQUEDA
if ($idEmpresa == NULL || $idEmpresa == -1) {
	$idEmpresa = '1';
}

$totalRows = 1;

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmpresa = mysql_query($queryEmpresa);
if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
if ($totalRows > 0) {
	// DATA
	$contFila = 0;
	$fill = false;
	while ($contFila<1) {
		$contFila++;
		
		if ($contFila % 45 == 1) {
			$pdf->AddPage();
			
			// CABECERA DEL DOCUMENTO 
			if ($idEmpresa != "") {
				$pdf->Image("../../".$rowEmpresa['logo_familia'],15,17,80);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',6);
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
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',10);
			$pdf->Cell(562,10,"Fecha de Emisión: ".date("d-m-Y"),0,0,'R');
			$pdf->Ln();
			$pdf->Cell(562,10,$nombreCajaPpal,0,0,'C');
			$pdf->Ln();
			$pdf->Cell(562,10,"LISTADO DE TRANSFERENCIAS",0,0,'C');
			
			$pdf->Ln(); $pdf->Ln();
			
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("100","80","70","124","90","50","50");
			$arrayCol = array("EMPRESA\n","FECHA TRANSFERENCIA\n","NRO. REFERENCIA\n","CLIENTE\n","ESTADO\n","SALDO\n","TOTAL\n");
			
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			
			foreach ($arrayCol as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
				
				$pdf->MultiCell($arrayTamCol[$indice],14,$valor,1,'C',true);
				
				$posX += $arrayTamCol[$indice];
			}
		}
		
		//RESTAURACION DE COLORES Y FUENTES
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');
		
		//$pdf->SetFillColor(234,244,255); // blanco
		$pdf->SetFillColor(255,255,255); // azul
		
		//CONSULTA EL LISTADO DE TRANSFERENCIAS SEGUN BUSQUEDA
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tb.id_departamento IN (%s)",
			valTpDato($idModuloPpal, "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(tb.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = tb.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tb.estado_transferencia IN (%s)",
				valTpDato($valCadBusq[1], "campo"));
		}
		
		if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tb.fecha_transferencia BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[3])),"date"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tb.id_departamento = %s",
				valTpDato($valCadBusq[4], "int"));
		}
		
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(tb.numero_transferencia LIKE %s
			OR cliente.nombre LIKE %s
			OR cliente.apellido LIKE %s)",
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"));
		}
		
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tb.estatus = %s",
				valTpDato($valCadBusq[6], "int"));
		}
			
		$queryDetalle = sprintf("SELECT
			tb.id_transferencia,
			tb.tipo_transferencia,
			tb.id_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			tb.monto_neto_transferencia,
			tb.saldo_transferencia,
			tb.fecha_transferencia,
			tb.estado_transferencia,
			tb.numero_transferencia,
			tb.id_departamento,
			tb.estado_transferencia,
			(CASE tb.estado_transferencia
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado/No Asignado'
				WHEN 2 THEN 'Asignado Parcial'
				WHEN 3 THEN 'Asignado'
			END) AS descripcion_estado_transferencia,
			tb.observacion_transferencia,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
			tb.estatus
		FROM cj_cc_transferencia tb
			INNER JOIN cj_cc_cliente cliente ON (tb.id_cliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (tb.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		ORDER BY tb.id_transferencia DESC", $sqlBusq);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle){ die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__); }
		$totalRows = mysql_num_rows($rsDetalle);
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
			$contFila++;
			
			// RESTAURACION DE COLOR Y FUENTE
			($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
			
			$pdf->Cell($arrayTamCol[0],14,$rowDetalle['nombre_empresa'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[1],14,date("d-m-Y", strtotime($rowDetalle['fecha_transferencia'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[2],14,utf8_encode($rowDetalle['numero_transferencia']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[3],14,utf8_encode($rowDetalle['nombre_cliente']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[4],14,utf8_encode($rowDetalle['descripcion_estado_transferencia']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[5],14,number_format($rowDetalle['saldo_transferencia'],2,".",","),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[6],14,number_format($rowDetalle['monto_neto_transferencia'],2,".",","),'LR',0,'R',true);
			$pdf->Ln();
			
			$fill = !$fill;
			
			$saldoFinalTransferencia += $rowDetalle['saldo_transferencia'];
			$totalFinalTransferencia += $rowDetalle['monto_neto_transferencia'];
		}
		
		$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
		
		$pdf->Ln();
		
		// TOTAL DOCUMENTOS
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(374,14,"",'T',0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(90,14,"TOTALES: ",1,0,'R',true);
		$pdf->Cell(50,14,number_format($saldoFinalTransferencia,2,".",","),1,0,'R',true);
		$pdf->Cell(50,14,number_format($totalFinalTransferencia,2,".",","),1,0,'R',true);
	}
}
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>