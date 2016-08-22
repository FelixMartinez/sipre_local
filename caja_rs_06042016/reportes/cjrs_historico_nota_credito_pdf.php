<?php
require_once ("../../connections/conex.php");
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

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// HISTÓRICO DE NOTAS DE CRÉDITO ///////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa,"int"));
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
			$pdf->Cell(562,10,"CAJA DE REPUESTOS Y SERVICIOS",0,0,'C');
			$pdf->Ln();
			$pdf->Cell(562,10,"HISTÓRICO DE NOTAS DE CRÉDITO",0,0,'C');
			
			$pdf->Ln(); $pdf->Ln();
			
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("100","45","45","45","45","45","119","40","40","40");
			$arrayCol = array("EMPRESA\n","FECHA N. CRÉDITO\n","NRO. N. CRÉDITO\n","NRO. CONTROL\n","FECHA FACT.\n","NRO. FACTURA\n","CLIENTE\n","ESTADO\n","SALDO\n","TOTAL\n");
			
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
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus_nota_credito IN (2)
		AND idDepartamentoNotaCredito IN (0,1,3)");
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cxc_nc.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_nc.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("fechaNotaCredito BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_nc.id_empleado_vendedor = %s",
				valTpDato($valCadBusq[3], "int"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_nc.aplicaLibros = %s",
				valTpDato($valCadBusq[4], "boolean"));
		}
		
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_nc.estadoNotaCredito IN (%s)",
				valTpDato($valCadBusq[5], "campo"));
		}
		
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito = %s",
				valTpDato($valCadBusq[6], "int"));
		}
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(numeracion_nota_credito LIKE %s
			OR cxc_nc.numeroControl LIKE %s
			OR numeroFactura LIKE %s
			OR CONCAT_WS('-', lci, ci) LIKE %s
			OR CONCAT_WS(' ', nombre, apellido) LIKE %s)",
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"));
		}
			
		// DETALLE DEL LSITADO
		$queryDetalle = sprintf("SELECT
			cxc_fact.fechaRegistroFactura,
			cxc_fact.numeroFactura,
			
			cliente.id,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			
			cxc_nc.idNotaCredito,
			cxc_nc.numeracion_nota_credito,
			cxc_nc.fechaNotaCredito,
			cxc_nc.numeroControl,
			cxc_nc.idDepartamentoNotaCredito,
			cxc_nc.subtotalNotaCredito,
			cxc_nc.montoNetoNotaCredito AS total,
			cxc_nc.saldoNotaCredito,
			cxc_nc.estadoNotaCredito,
			(CASE cxc_nc.estadoNotaCredito
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado No Asignado'
				WHEN 2 THEN 'Asignado Parcial'
				WHEN 3 THEN 'Asignado'
			END) AS descripcion_estado_nota_credito,
			cxc_nc.aplicaLibros,
			
			(CASE cxc_nc.idDepartamentoNotaCredito
				WHEN 0 THEN
					(SELECT COUNT(cxc_nc_det.id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
					WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito)
				WHEN 1 THEN
					IFNULL((SELECT COUNT(fact_det.idFactura) FROM sa_det_fact_articulo fact_det
							WHERE fact_det.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(fact_det.idFactura) FROM sa_det_fact_notas fact_det
							WHERE fact_det.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(fact_det.idFactura) FROM sa_det_fact_tempario fact_det
							WHERE fact_det.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(fact_det.idFactura) FROM sa_det_fact_tot fact_det
							WHERE fact_det.idFactura = cxc_fact.idFactura), 0)
				WHEN 2 THEN
					IFNULL((SELECT COUNT(cxc_nc_det_acc.id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
							WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito), 0)
					+ IFNULL((SELECT COUNT(cxc_nc_det_vehic.id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
							WHERE cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito), 0)
				WHEN 3 THEN
					(SELECT COUNT(cxc_nc_det_adm.id_nota_credito) FROM cj_cc_nota_credito_detalle_adm cxc_nc_det_adm
					WHERE cxc_nc_det_adm.id_nota_credito = cxc_nc.idNotaCredito)
			END) AS cant_items,
			
			(IFNULL(cxc_nc.subtotalNotaCredito, 0)
				- IFNULL(cxc_nc.subtotal_descuento, 0)) AS total_neto,
			(SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
			WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito) AS total_iva,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notacredito cxc_nc
			LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura)
			INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		ORDER BY cxc_nc.numeroControl DESC", $sqlBusq);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsDetalle);
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
			$contFila++;
			
			// RESTAURACION DE COLOR Y FUENTE
			($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
			
			$pdf->Cell($arrayTamCol[0],12,$rowDetalle['nombre_empresa'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[1],12,date("d-m-Y", strtotime($rowDetalle['fechaNotaCredito'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[2],12,utf8_encode($rowDetalle['numeracion_nota_credito']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[3],12,utf8_encode($rowDetalle['numeroControl']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[4],12,date("d-m-Y", strtotime($rowDetalle['fechaRegistroFactura'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[5],12,utf8_encode($rowDetalle['numeroFactura']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[6],12,utf8_encode($rowDetalle['nombre_cliente']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[7],12,utf8_encode($rowDetalle['descripcion_estado_nota_credito']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[8],12,number_format($rowDetalle['saldoNotaCredito'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[9],12,number_format($rowDetalle['total'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Ln();
			
			$fill = !$fill;
			
			$saldoFinalNotaCredito += $rowDetalle['saldoNotaCredito'];
			$totalFinalNotaCredito += $rowDetalle['total'];
		}
		
		$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
		
		$pdf->Ln();
		
		// TOTAL DOCUMENTOS
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(325,14,"",'T',0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(159,14,"TOTALES: ",1,0,'R',true);
		$pdf->Cell(40,14,number_format($saldoFinalNotaCredito,2,".",","),1,0,'R',true);
		$pdf->Cell(40,14,number_format($totalFinalNotaCredito,2,".",","),1,0,'R',true);
	}
}
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>