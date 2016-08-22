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

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// HISTORICO DE FACTURAS DE IMPRESAS ///////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
			$pdf->Cell(762,5,"FACTURAS IMPRESAS ".$nombreCajaPpal,0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("50","100","100","60","65","60","100","60","45","60");
			$arrayCol = array("FECHA\n\n","CONSECUTIVO FISCAL/SERIAL IMPRESORA","FECHA/HORA IMPRESORA\n\n","NRO. FACTURA\n\n","NRO. CONTROL\n\n","NRO. PEDIDO\n\n","CLIENTE\n\n","CONDICIÓN DE PAGO\n\n","PLACA\n\n","ADICIONALES\n\n","TOTAL\n\n");
			
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			
			foreach ($arrayCol as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
				
				$pdf->MultiCell($arrayTamCol[$indice],10,$valor,1,'C',true);
				
				$posX += $arrayTamCol[$indice];
			}
		}
		
		//RESTAURACION DE COLORES Y FUENTES
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');
		
		//$pdf->SetFillColor(234,244,255); // blanco
		$pdf->SetFillColor(255,255,255); // azul
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($idModuloPpal, "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_fact.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_fact.idVendedor LIKE %s",
				valTpDato($valCadBusq[3],"text"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_fact.aplicaLibros = %s",
				valTpDato($valCadBusq[4], "boolean"));
		}
		
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_fact.estadoFactura IN (%s)",
				valTpDato($valCadBusq[5], "campo"));
		}
		
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_fact.condicionDePago = %s",
				valTpDato($valCadBusq[6], "boolean"));
		}
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura = %s",
				valTpDato($valCadBusq[7], "int"));
		}
		
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_fact.anulada LIKE %s",
				valTpDato($valCadBusq[8],"text"));
		}
		
		if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
			$arrayConsecutivoFiscal = explode(",",$valCadBusq[9]);
			if (count($arrayConsecutivoFiscal) == 1) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				if (in_array(1,$arrayConsecutivoFiscal) && !in_array(2,$arrayConsecutivoFiscal)) {
					$sqlBusq .= $cond.sprintf("cxc_fact.consecutivo_fiscal IS NOT NULL");
				} else if (!in_array(1,$arrayConsecutivoFiscal) && in_array(2,$arrayConsecutivoFiscal)) {
					$sqlBusq .= $cond.sprintf("cxc_fact.consecutivo_fiscal IS NULL");
				}
			}
		}
		
		if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
			OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
			OR cxc_fact.numeroFactura LIKE %s
			OR cxc_fact.numeroControl LIKE %s
			OR cxc_fact.consecutivo_fiscal LIKE %s
			OR cxc_fact.serial_impresora LIKE %s
			OR ped_vent.id_pedido_venta_propio LIKE %s
			OR orden.numero_orden LIKE %s
			OR an_ped_vent.numeracion_pedido LIKE %s)",
				valTpDato("%".$valCadBusq[10]."%", "text"),
				valTpDato("%".$valCadBusq[10]."%", "text"),
				valTpDato("%".$valCadBusq[10]."%", "text"),
				valTpDato("%".$valCadBusq[10]."%", "text"),
				valTpDato("%".$valCadBusq[10]."%", "text"),
				valTpDato("%".$valCadBusq[10]."%", "text"),
				valTpDato("%".$valCadBusq[10]."%", "text"),
				valTpDato("%".$valCadBusq[10]."%", "text"),
				valTpDato("%".$valCadBusq[10]."%", "text"));
		}
	
		// DETALLE DEL LSITADO
		$queryDetalle = sprintf("SELECT 
			cxc_fact.idFactura,
			cxc_fact.id_empresa,
			cxc_fact.fechaRegistroFactura,
			cxc_fact.fechaVencimientoFactura,
			cxc_fact.numeroFactura,
			cxc_fact.numeroControl,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
			cxc_fact.condicionDePago,
			cxc_fact.numeroPedido,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			cxc_fact.estadoFactura,
			(CASE cxc_fact.estadoFactura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS descripcion_estado_factura,
			cxc_fact.aplicaLibros,
			cxc_fact.anulada,
			cxc_fact.montoTotalFactura,
			cxc_fact.saldoFactura,
			
			(IFNULL(cxc_fact.subtotalFactura, 0)
				- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
			
			IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
					WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_iva,
			
			(IFNULL(cxc_fact.subtotalFactura, 0)
				- IFNULL(cxc_fact.descuentoFactura, 0)
				+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
							WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
							WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0)) AS total,
				
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
			
			(CASE cxc_fact.idDepartamentoOrigenFactura
				WHEN 0 THEN
					ped_vent.id_pedido_venta_propio
				WHEN 1 THEN
					orden.numero_orden
				WHEN 2 THEN
					an_ped_vent.numeracion_pedido
				ELSE
					NULL
			END) AS numero_pedido,
			
			(CASE cxc_fact.idDepartamentoOrigenFactura
				WHEN 0 THEN
					(SELECT COUNT(fact_det.id_factura) FROM cj_cc_factura_detalle fact_det
					WHERE fact_det.id_factura = cxc_fact.idFactura)
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
					IFNULL((SELECT COUNT(fact_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios fact_det_acc
							WHERE fact_det_acc.id_factura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(fact_det_vehic.id_factura) FROM cj_cc_factura_detalle_vehiculo fact_det_vehic
							WHERE fact_det_vehic.id_factura = cxc_fact.idFactura), 0)
				WHEN 3 THEN
					(SELECT COUNT(fact_det_adm.id_factura) FROM cj_cc_factura_detalle_adm fact_det_adm
					WHERE fact_det_adm.id_factura = cxc_fact.idFactura)
			END) AS cant_items,
			
			(SELECT uni_fis.placa
			FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
				INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			WHERE cxc_fact_det_vehic.id_factura = cxc_fact.idFactura) AS placa,
			
			cxc_fact.consecutivo_fiscal,
			cxc_fact.serial_impresora,
			cxc_fact.fecha_impresora,
			cxc_fact.hora_impresora,
			CONCAT_WS('/', cxc_fact.consecutivo_fiscal, cxc_fact.serial_impresora) AS consecutivo_serial,
			CONCAT_WS(' ', cxc_fact.fecha_impresora, cxc_fact.hora_impresora) AS fecha_hora
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			LEFT JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
			LEFT JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
			LEFT JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		ORDER BY cxc_fact.idFactura DESC", $sqlBusq);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsDetalle);
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
			$contFila++;
			
			if ($rowDetalle['fecha_hora'] != ''){
				$fechaHora = date("d-m-Y H:i",strtotime($rowDetalle['fecha_hora']));
			}
			
			$condicionDePago = ($rowDetalle['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO";
				
			$pdf->Cell($arrayTamCol[0],12,date("d-m-Y", strtotime($rowDetalle['fechaRegistroFactura'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[1],12,utf8_encode($rowDetalle['consecutivo_serial']),'LR',0,'C',true);		
			$pdf->Cell($arrayTamCol[2],12,$fechaHora,'LR',0,'C',true);							
			$pdf->Cell($arrayTamCol[3],12,utf8_encode($rowDetalle['numeroFactura']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[4],12,utf8_encode($rowDetalle['numeroControl']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[5],12,utf8_encode($rowDetalle['numeroPedido']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[6],12,utf8_encode($rowDetalle['nombre_cliente']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[7],12,($condicionDePago),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[8],12,utf8_encode($rowDetalle['placa']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[9],12,utf8_encode($rowDetalle['cant_items']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[10],12,utf8_encode($rowDetalle['montoTotalFactura']),'LR',0,'R',true);
			$pdf->Ln();
			
			$saldoTotalFactura += $rowDetalle['saldoFactura'];
			$montoTotalFactura += $rowDetalle['montoTotalFactura'];
			
			$fill = !$fill;
		}
			
		$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
		
		$pdf->SetFillColor(255);
		$pdf->Cell(562,5,"",'T',0,'L',true);
		$pdf->Ln();
					
		// TOTAL ANTCIPOS
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(600,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(45,14,"TOTALES: ",1,0,'L',true);
		$pdf->Cell(50,14,number_format($montoTotalFactura,2,".",","),1,0,'R',true);
		$pdf->Cell(50,14,number_format($saldoTotalFactura,2,".",","),1,0,'R',true);
		
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