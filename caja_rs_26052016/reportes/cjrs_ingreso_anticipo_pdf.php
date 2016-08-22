<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("24","20","24");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"20");

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];
$cbxNoCancelado = $valCadBusq[1];
$cbxCancelado = $valCadBusq[2];
$cbxParcialCancelado = $valCadBusq[3];
$cbxAsignado = $valCadBusq[4];
$txtFechaDesde = $valCadBusq[5];
$txtFechaHasta = $valCadBusq[6];
$lstModulo = $valCadBusq[7];
$txtCriterio = $valCadBusq[8];

//PUEDE SER NULL AL SELECCIONAR [TODOS] EN LA BUSQUEDA
if ($idEmpresa == NULL || $idEmpresa == -1) {
	$idEmpresa = '1';
}

$totalRows = 1;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// LISTADO DE ANTICIPOS ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s",
	$idEmpresa);
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
				$pdf->Cell(200,9,utf8_encode($rowEmpresa['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmpresa['rif']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,utf8_encode($spanRIF.": ".$rowEmpresa['rif']),0,2,'L');
				}
				if (strlen($rowEmpresa['direccion']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(100,9,utf8_encode($rowEmpresa['direccion']),0,2,'L');
				}
				if (strlen($rowEmpresa['web']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,utf8_encode($rowEmpresa['web']),0,0,'L');
					$pdf->Ln();
				}
			}
			
			$pdf->Cell('',8,'',0,2);

			//FECHA
				$fechaHoy = date("d-m-Y");
				$horaActual = date("H:i:s");
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',7);
				$pdf->Cell(560,20,"Fecha de Emisión: ".$fechaHoy.'  '.$horaActual."",0,0,'R');
				$pdf->Ln();
				
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Ln();
			$pdf->Cell(562,5,$nombreCajaPpal,0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(562,5,"LISTADO DE ANTICIPOS",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("100","80","70","124","90","50","50");
			$arrayCol = array("EMPRESA\n","FECHA ANTICIPO\n","NRO. ANTICIPO\n","CLIENTE\n","ESTADO\n","TOTAL\n","SALDO\n");
			
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			
			foreach ($arrayCol as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
				
				$pdf->MultiCell($arrayTamCol[$indice],8,$valor,1,'C',true);
				
				$posX += $arrayTamCol[$indice];
			}
		}
		
		//RESTAURACION DE COLORES Y FUENTES
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');
		
		//$pdf->SetFillColor(234,244,255); // blanco
		$pdf->SetFillColor(255,255,255); // azul
		
		//CONSULTA EL LISTADO DE ANTICIPOS SEGUN BUSQUEDA
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idDepartamento IN (%s)",
			valTpDato($idModuloPpal, "campo"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf("an.id_empresa = %s)",
				valTpDato($valCadBusq[0], "int"));
		}
		
		if (($valCadBusq[1] != "-1" && $valCadBusq[1] != "")
		|| ($valCadBusq[2] != "-1" && $valCadBusq[2] != "")
		|| ($valCadBusq[3] != "-1" && $valCadBusq[3] != "")
		|| ($valCadBusq[4] != "-1" && $valCadBusq[4] != "")) {
			if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") $array[] = $valCadBusq[1];
			if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") $array[] = $valCadBusq[2];
			if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") $array[] = $valCadBusq[3];
			if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") $array[] = $valCadBusq[4];
			
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("estadoAnticipo IN (%s)",
				valTpDato(implode(",",$array), "campo"));
		}
		
		if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("fechaAnticipo BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
		}
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("idDepartamento = %s",
				valTpDato($valCadBusq[7], "int"));
		}
		
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf("numeroAnticipo LIKE %s
				OR an.idCliente IN (SELECT cl.id FROM cj_cc_cliente cl WHERE cl.nombre LIKE %s OR cl.apellido LIKE %s))",
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"));
		}
			
		// DETALLE DEL LSITADO
			$queryDetalle = sprintf("SELECT
				an.idAnticipo,
				an.idCliente,
				(SELECT CONCAT_WS(' ',cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id = an.idCliente) AS nombre_cliente,
				(SELECT CONCAT_WS('-',cl.lci,cl.ci) FROM cj_cc_cliente cl WHERE cl.id = an.idCliente) AS ci_cliente,
				an.montoNetoAnticipo,
				an.saldoAnticipo,
				an.fechaAnticipo,
				an.estadoAnticipo,
				an.numeroAnticipo,
				an.idDepartamento,
				(CASE estadoAnticipo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado/No Asignado'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
				END) AS estadoAnticipo,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM
				cj_cc_anticipo an
			INNER JOIN cj_cc_cliente ON (an.idCliente = cj_cc_cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s ORDER BY idAnticipo DESC", $sqlBusq);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
				
				$nombreEmpresa = $rowDetalle['nombre_empresa'];
				$fechaAnticipo = $rowDetalle['fechaAnticipo'];
				$numeroAnticipo = $rowDetalle['numeroAnticipo'];
				$nombreCliente = $rowDetalle['nombre_cliente'];
				$estadoAnticipo = $rowDetalle['estadoAnticipo'];
				$montoAnticipo = $rowDetalle['montoNetoAnticipo'];
				$saldoAnticipo = $rowDetalle['saldoAnticipo'];
				
						$pdf->Cell($arrayTamCol[0],12,$nombreEmpresa,'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[1],12,date("d-m-Y", strtotime($fechaAnticipo)),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[2],12,utf8_encode($numeroAnticipo),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[3],12,utf8_encode($nombreCliente),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[4],12,utf8_encode($estadoAnticipo),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[5],12,number_format($montoAnticipo,2,".",","),'LR',0,'R',true);
						$pdf->Cell($arrayTamCol[6],12,number_format($saldoAnticipo,2,".",","),'LR',0,'R',true);
						$pdf->Ln();
						
				$saldoTotalAnticipo += $saldoAnticipo;
				$montoTotalAnticipo += $montoAnticipo;
			}
			
			$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
			
			$pdf->Ln();
			
			$pdf->SetFillColor(255);
			$pdf->Cell(562,5,"",'T',0,'L',true);
			$pdf->Ln();
						
			// TOTAL ANTCIPOS
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(392,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"TOTALES: ",1,0,'L',true);
			$pdf->Cell(50,14,number_format($montoTotalAnticipo,2,".",","),1,0,'R',true);
			$pdf->Cell(50,14,number_format($saldoTotalAnticipo,2,".",","),1,0,'R',true);
			
		$fill = !$fill;
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {
		
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');
			
			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}
$pdf->SetDisplayMode("real");
//$pdf->AutoPrint(true);
$pdf->Output();
?>