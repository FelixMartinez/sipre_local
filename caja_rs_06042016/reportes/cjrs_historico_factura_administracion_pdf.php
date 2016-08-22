<?php
require_once ("../../connections/conex.php");
session_start();
set_time_limit(0);
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
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
/////////////////////////////////////////////// HISTORICO DE FACTURAS DE ADMINISTRACION //////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa,"int"));
$rsEmpresa = mysql_query($queryEmpresa);
if (!$rsEmpresa) return die(mysql_error()."\n\nLine: ".__LINE__);
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
			$pdf->Cell(562,10,"HISTÓRICO DE FACTURAS DE ADMINISTRACIÓN",0,0,'C');
			
			$pdf->Ln(); $pdf->Ln();
			
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("50","50","60","60","60","135","50","50","50");
			$arrayCol = array("FECHA\n","F. VENC.\n","NRO. FACTURA\n","NRO. CONTROL\n","NRO. PED/ORD\n","CLIENTE\n","TIPO PAGO\n","SALDO\n","TOTAL\n");
			
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
		$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (3)");
		
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
			$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
			OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
			OR cxc_fact.numeroFactura LIKE %s
			OR cxc_fact.numeroControl LIKE %s
			OR ped_vent.id_pedido_venta_propio LIKE %s
			OR orden.numero_orden LIKE %s
			OR an_ped_vent.numeracion_pedido LIKE %s)",
				valTpDato("%".$valCadBusq[9]."%", "text"),
				valTpDato("%".$valCadBusq[9]."%", "text"),
				valTpDato("%".$valCadBusq[9]."%", "text"),
				valTpDato("%".$valCadBusq[9]."%", "text"),
				valTpDato("%".$valCadBusq[9]."%", "text"),
				valTpDato("%".$valCadBusq[9]."%", "text"),
				valTpDato("%".$valCadBusq[9]."%", "text"));
		}
	
		$query = sprintf("SELECT 
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
			END) AS cant_items
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			LEFT JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
			LEFT JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
			LEFT JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
			ORDER BY cxc_fact.idFactura DESC", $sqlBusq);
		$rsDetalle = mysql_query($query);
		if (!$rsDetalle) return die(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsDetalle);
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
			$contFila++;
			
			// RESTAURACION DE COLOR Y FUENTE
			($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
			
			$pdf->Cell($arrayTamCol[0],12,date("d-m-Y", strtotime($rowDetalle['fechaRegistroFactura'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[1],12,date("d-m-Y", strtotime($rowDetalle['fechaVencimientoFactura'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[2],12,utf8_encode($rowDetalle['numeroFactura']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[3],12,utf8_encode($rowDetalle['numeroControl']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[4],12,utf8_encode($rowDetalle['numero_pedido']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[5],12,utf8_encode($rowDetalle['nombre_cliente']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[6],12,(($rowDetalle['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[7],12,number_format($rowDetalle['saldoFactura'], 2, ".", ","),'LR',0,'R',true);
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
		$pdf->Cell(415,14,"",'T',0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(50,14,"TOTALES: ",1,0,'R',true);
		$pdf->Cell(50,14,number_format($saldoFinalFactura,2,".",","),1,0,'R',true);
		$pdf->Cell(50,14,number_format($totalFinalFactura,2,".",","),1,0,'R',true);
	}
}
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>