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

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// HISTORICO DE FACTURAS DE VEH�CULOS ///////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
			$pdf->Cell(562,10,"Fecha de Emisi�n: ".date("d-m-Y"),0,0,'R');
			$pdf->Ln();
			$pdf->Cell(562,10,$nombreCajaPpal,0,0,'C');
			$pdf->Ln();
			$pdf->Cell(562,10,"HIST�RICO DE FACTURAS DE VEH�CULOS",0,0,'C');
			
			$pdf->Ln(); $pdf->Ln();
			
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("50","50","60","60","135","60","50","50","50");
			$arrayCol = array("FECHA\n","NRO. FACTURA\n","NRO. CONTROL\n","NRO. PEDIDO\n","CLIENTE\n","TIPO PAGO\n","PLACA\n","ADICIONALES\n","TOTAL\n");
			
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
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			switch ($valCadBusq[9]) {
				case 1 : // Vehiculo
					$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_vehic2.id_factura)
					FROM cj_cc_factura_detalle_vehiculo fact_det_vehic2 WHERE fact_det_vehic2.id_factura = cxc_fact.idFactura) > 0");
					break;
				case 2 : // Adicionales
					$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
					FROM cj_cc_factura_detalle_accesorios fact_det_acc2
						INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
					WHERE fact_det_acc2.id_factura = cxc_fact.idFactura
						AND acc.id_tipo_accesorio IN (1)) > 0");
					break;
				case 3 : // Accesorios
					$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
					FROM cj_cc_factura_detalle_accesorios fact_det_acc2
						INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
					WHERE fact_det_acc2.id_factura = cxc_fact.idFactura
						AND acc.id_tipo_accesorio IN (2)) > 0");
					break;
			}
		}
		
		if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
			OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
			OR cxc_fact.numeroFactura LIKE %s
			OR cxc_fact.numeroControl LIKE %s
			OR ped_vent.id_pedido LIKE %s
			OR ped_vent.numeracion_pedido LIKE %s
			OR pres_vent.id_presupuesto LIKE %s
			OR pres_vent.numeracion_presupuesto LIKE %s
			OR placa LIKE %s)",
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
		$queryDetalle = sprintf("SELECT DISTINCT
			cxc_fact.idFactura,
			cxc_fact.fechaRegistroFactura,
			cxc_fact.numeroFactura,
			cxc_fact.numeroControl,
			cxc_fact.condicionDePago,
			ped_vent.id_pedido,
			ped_vent.numeracion_pedido,
			pres_vent.id_presupuesto,
			pres_vent.numeracion_presupuesto,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			uni_fis.placa,
			ped_comp_det.flotilla,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
			cxc_fact.estadoFactura,
			(CASE cxc_fact.estadoFactura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS descripcion_estado_factura,
			cxc_fact.aplicaLibros,
			cxc_fact.anulada,
			cxc_fact.saldoFactura,
			cxc_fact.montoTotalFactura,
			
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
			
			(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios
			FROM cj_cc_factura_detalle_accesorios fact_det_acc2 WHERE fact_det_acc2.id_factura = cxc_fact.idFactura) AS cantidad_accesorios,
			cxc_fact.anulada,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
			RIGHT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
			LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
			LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		ORDER BY cxc_fact.idFactura DESC", $sqlBusq);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsDetalle);
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
			$contFila++;
			
			// RESTAURACION DE COLOR Y FUENTE
			($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
			
			$pdf->Cell($arrayTamCol[0],12,date("d-m-Y", strtotime($rowDetalle['fechaRegistroFactura'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[1],12,utf8_encode($rowDetalle['numeroFactura']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[2],12,utf8_encode($rowDetalle['numeroControl']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[3],12,utf8_encode($rowDetalle['numeracion_pedido']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[4],12,utf8_encode($rowDetalle['nombre_cliente']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[5],12,(($row['condicionDePago'] == 1) ? "CONTADO" : "CR�DITO"),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[6],12,utf8_encode($rowDetalle['placa']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[7],12,utf8_encode($rowDetalle['cantidad_accesorios']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[8],12,number_format($rowDetalle['total'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Ln();
			
			$fill = !$fill;
			
			$saldoFinalFactura += $rowDetalle['saldoFactura'];
			$totalFinalFactura += $rowDetalle['total'];
		}
		
		$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
		
		$pdf->Ln();
		
		// TOTAL DOCUMENTOS
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(355,14,"",'T',0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(160,14,"TOTALES: ",1,0,'R',true);
		$pdf->Cell(50,14,number_format($totalFinalFactura,2,".",","),1,0,'R',true);
	}
}
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>