<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
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

//PUEDE SER NULL AL SELECCIONAR [TODOS] EN LA BUSQUEDA
if ($idEmpresa == NULL || $idEmpresa == -1) {
	$idEmpresa = '1';
}

$totalRows = 1;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// HISTÓRICO DE NOTAS DE CRÉDITO ///////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
			$pdf->Cell(740,20,"Fecha de Emisión: ".$fechaHoy.'  '.$horaActual."",0,0,'R');
			$pdf->Ln();
				
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(762,5,"NOTAS DE CRÉDITO IMPRESAS",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("80","100","100","50","50","50","60","40","70","45","50","50");
			$arrayCol = array("EMPRESA\n\n","CONSECUTIVO FISCAL/SERIAL IMPRESORA","FECHA/HORA IMPRESORA\n\n","FECHA N. CRÉDITO\n\n","NRO. N. CRÉDITO\n\n","NRO. FACTURA\n\n","NRO. CONTROL\n\n","NRO. PED./ORD.\n\n","CLIENTE\n\n","ESTADO\n\n","SALDO\n\n","TOTAL\n\n");
			
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
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus_nota_credito IN (2)
		AND idDepartamentoNotaCredito IN (%s)",
			valTpDato($idModuloPpal, "campo"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cxc_nc.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_nc.id_empresa))",
				valTpDato($valCadBusq[0],"int"),
				valTpDato($valCadBusq[0],"int"));
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
			$arrayConsecutivoFiscal = explode(",",$valCadBusq[7]);
			if (count($arrayConsecutivoFiscal) == 1) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				if (in_array(1,$arrayConsecutivoFiscal) && !in_array(2,$arrayConsecutivoFiscal)) {
					$sqlBusq .= $cond.sprintf("cxc_nc.consecutivo_fiscal IS NOT NULL");
				} else if (!in_array(1,$arrayConsecutivoFiscal) && in_array(2,$arrayConsecutivoFiscal)) {
					$sqlBusq .= $cond.sprintf("cxc_nc.consecutivo_fiscal IS NULL");
				}
			}
		}
		
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cxc_nc.numeracion_nota_credito LIKE %s
			OR cxc_nc.numeroControl LIKE %s
			OR cxc_nc.consecutivo_fiscal LIKE %s
			OR cxc_nc.serial_impresora LIKE %s
			OR cxc_fact.numeroFactura LIKE %s
			OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
			OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
			OR cxc_nc.observacionesNotaCredito LIKE %s)",
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"));
		}
			
		// DETALLE DEL LISTADO
		$queryDetalle = sprintf("SELECT
			cxc_fact.fechaRegistroFactura,
			cxc_fact.numeroFactura,
			
			cliente.id,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			
			cxc_nc.idNotaCredito,
			cxc_nc.numeracion_nota_credito,
			cxc_nc.numeroControl,
			cxc_nc.fechaNotaCredito,
			cxc_nc.idDepartamentoNotaCredito,
			cxc_nc.observacionesNotaCredito,
			cxc_nc.subtotalNotaCredito,
			cxc_nc.subtotal_descuento,
			
			(IFNULL(cxc_nc.subtotalNotaCredito, 0)
				- IFNULL(cxc_nc.subtotal_descuento, 0)) AS total_neto,
			
			IFNULL((SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
					WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito), 0) AS total_iva,
			
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
			
			motivo.id_motivo,
			motivo.descripcion AS descripcion_motivo,
			
			(CASE cxc_nc.idDepartamentoNotaCredito
				WHEN 0 THEN
					IFNULL((SELECT COUNT(cxc_nc_det.id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
							WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito), 0)
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
					IFNULL((SELECT COUNT(cxc_nc_det_adm.id_nota_credito) FROM cj_cc_nota_credito_detalle_adm cxc_nc_det_adm
							WHERE cxc_nc_det_adm.id_nota_credito = cxc_nc.idNotaCredito), 0)
			END) AS cant_items,
			
			cxc_nc.consecutivo_fiscal,
			cxc_nc.serial_impresora,
			cxc_nc.fecha_impresora,
			cxc_nc.hora_impresora,
			CONCAT_WS('/', cxc_nc.consecutivo_fiscal, cxc_nc.serial_impresora) AS consecutivo_serial,
			CONCAT_WS(' ', cxc_nc.fecha_impresora, cxc_nc.hora_impresora) AS fecha_hora,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notacredito cxc_nc
			LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura)
			INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
			LEFT JOIN pg_motivo motivo ON (cxc_nc.id_motivo = motivo.id_motivo)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		ORDER BY cxc_nc.idNotaCredito DESC", $sqlBusq);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsDetalle);
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
			$contFila++;
			
			if ($rowDetalle['fecha_hora'] != '') {
				$fechaHora = date("d-m-Y H:i",strtotime($rowDetalle['fecha_hora']));
			}
			
			$pdf->Cell($arrayTamCol[0],12,$rowDetalle['nombre_empresa'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[1],12,utf8_encode($rowDetalle['consecutivo_serial']),'LR',0,'C',true);		
			$pdf->Cell($arrayTamCol[2],12,$fechaHora,'LR',0,'C',true);							
			$pdf->Cell($arrayTamCol[3],12,date("d-m-Y", strtotime($rowDetalle['fechaNotaCredito'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[4],12,utf8_encode($rowDetalle['numeracion_nota_credito']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[5],12,utf8_encode($rowDetalle['numeroFactura']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[6],12,utf8_encode($rowDetalle['numeroControl']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[7],12,utf8_encode($rowDetalle['numeroPedido']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[8],12,utf8_encode($rowDetalle['nombre_cliente']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[9],12,utf8_encode($rowDetalle['descripcion_estado_nota_credito']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[10],12,utf8_encode($rowDetalle['saldoNotaCredito']),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[11],12,utf8_encode($rowDetalle['total']),'LR',0,'R',true);
			$pdf->Ln();
			
			$saldoTotalNotaCredito += $rowDetalle['saldoNotaCredito'];
			$montoTotalNotaCredito += $rowDetalle['total'];
			
			$fill = !$fill;
		}
			
		$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
		
		$pdf->Ln();
		
		$pdf->SetFillColor(255);
		$pdf->Cell(562,5,"",'T',0,'L',true);
		$pdf->Ln();
					
		// TOTAL ANTCIPOS
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(593,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(72,14,"TOTALES: ",1,0,'L',true);
		$pdf->Cell(40,14,number_format($saldoTotalNotaCredito,2,".",","),1,0,'R',true);
		$pdf->Cell(40,14,number_format($montoTotalNotaCredito,2,".",","),1,0,'R',true);
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {
		
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');
			
			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>