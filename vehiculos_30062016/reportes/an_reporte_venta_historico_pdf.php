<?php
require_once("../../connections/conex.php");

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
$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];

$totalRows = 1;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// PRESUPUESTO DE ACCESORIOS ///////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ENCABEZADO PRESUPUESTO
/*$queryEncabezado = sprintf("SELECT * FROM an_presupuesto_accesorio
							WHERE id_presupuesto_accesorio = %s",
								$idPresupuesto);
$rsEncabezado = mysql_query($queryEncabezado);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);*/

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmpresa = mysql_query($queryEmpresa);
if (!$rsEmpresa) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmpresa = mysql_fetch_assoc($rsEmpresa);

if ($totalRows > 0) {
	// DATA
	$contFila = 0;
	$fill = false;
	while ($contFila < 1) {
		$contFila++;
		
		if ($contFila % 45 == 1) {
			$pdf->AddPage();
			
			// CABECERA DEL DOCUMENTO 
			if ($idEmpresa != "") {
				$pdf->Image("../../".$rowEmpresa['logo_familia'],15,17,80);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',6);
				$pdf->SetX(100);
				$pdf->Cell(200,9,htmlentities($rowEmpresa['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmpresa['rif']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,htmlentities($spanRIF.": ".$rowEmpresa['rif']),0,2,'L');
				}
				if (strlen($rowEmpresa['direccion']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(100,9,htmlentities($rowEmpresa['direccion']),0,2,'L');
				}
				if (strlen($rowEmpresa['web']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,htmlentities($rowEmpresa['web']),0,0,'L');
					$pdf->Ln();
				}
			}
			$pdf->Cell('',8,'',0,2);

			//FECHA
			$fechaHoy = date("d-m-Y");
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(700,20,"FECHA: ".$fechaHoy."",0,0,'R');
			$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Ln();
			$pdf->Cell(750,5,"Histórico de Reporte de Venta",0,0,'C');
/*			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(562,5,"".'Asociado a Presupuesto Nro.: '.$rowEncabezado['id_presupuesto']."",0,0,'C');*/
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("50","44","44","44","44","117","124","50","62","55","55","55");
//			$arrayCol = array("CÓDIGO\n\n","DESCRIPCIÓN\n\n","PRECIO SIN I.V.A\n\n","I.V.A 12%\n\n","PRECIO CON I.V.A\n\n");
			$arrayCol = array("Fecha\n\n","Nro. Factura\n\n","Nro. Control\n\n","Nro. Pedido\n\n","Nro. Presupuesto\n","Cliente\n\n","Entidad Bancaria\n\n","Tipo de Pago\n\n","Seguro\n\n","Monto del Seguro\n\n","Subtotal Accesorios\n","Total Factura\n\n");
			
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
		
		// IVA PREDETERMINADO
		/*$queryIva = sprintf("SELECT * FROM pg_iva iva WHERE iva.tipo = 6 AND iva.activo = 1 AND iva.estado = 1;");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryIva);		
		$rowIva = mysql_fetch_assoc($rsIva);
		
		$iva = $rowIva['iva'];*/
			
		// DETALLE DEL PRESUPUESTO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
		
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
			$sqlBusq .= $cond.sprintf("fechaRegistroFactura BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_fact.idVendedor IN (%s)",
				valTpDato($valCadBusq[3], "campo"));
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
			$sqlBusq .= $cond.sprintf("ped_vent.id_banco_financiar IN (%s)",
				valTpDato($valCadBusq[6], "campo"));
		}
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
			OR cxc_fact.numeroControl LIKE %s
			OR ped_vent.id_pedido LIKE %s
			OR ped_vent.numeracion_pedido LIKE %s
			OR pres_vent.id_presupuesto LIKE %s
			OR pres_vent.numeracion_presupuesto LIKE %s
			OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
			OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
			OR CONCAT(uni_bas.nom_uni_bas,': ', modelo.nom_modelo, ' - ', vers.nom_version) LIKE %s
			OR uni_fis.placa LIKE %s
			OR poliza.nombre_poliza LIKE %s
			OR pres_acc.id_presupuesto_accesorio LIKE %s)",
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"));
		}
		
		$query = sprintf("SELECT 
			cxc_fact.idFactura,
			cxc_fact.id_empresa,
			cxc_fact.fechaRegistroFactura,
			cxc_fact.fechaVencimientoFactura,
			cxc_fact.numeroFactura,
			cxc_fact.numeroControl,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
			cxc_fact.condicionDePago AS condicion_pago,
			ped_vent.id_pedido,
			ped_vent.numeracion_pedido,
			pres_vent.id_presupuesto,
			pres_vent.numeracion_presupuesto,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			CONCAT(uni_bas.nom_uni_bas, ': ', marca.nom_marca, ' ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
			cxc_fact.estadoFactura,
			(CASE cxc_fact.estadoFactura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS descripcion_estado_factura,
			banco.nombreBanco,
			poliza.nombre_poliza,
			ped_vent.monto_seguro,
			pres_acc.id_presupuesto_accesorio,
			vw_pg_empleado.nombre_empleado,
			ped_vent.vexacc1 AS subtotal_accesorios,
			
			(IFNULL(cxc_fact.subtotalFactura, 0)
				- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
			cxc_fact.calculoIvaFactura AS total_iva,
			
			(IFNULL(cxc_fact.subtotalFactura, 0)
				- IFNULL(cxc_fact.descuentoFactura, 0)
				+ IFNULL(cxc_fact.calculoIvaFactura, 0) 
				+ IFNULL(cxc_fact.calculoIvaDeLujoFactura, 0)
			) AS total,
			
			cxc_fact.saldoFactura,
			cxc_fact.anulada
		FROM cj_cc_encabezadofactura cxc_fact
			LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			LEFT JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			LEFT JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			LEFT JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
			LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco)
			LEFT JOIN an_presupuesto_accesorio pres_acc ON (ped_vent.id_presupuesto = pres_acc.id_presupuesto)
			LEFT JOIN an_poliza poliza ON (ped_vent.id_poliza = poliza.id_poliza)
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
		ORDER BY numeroControl DESC",$sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_assoc($rs)){
			$contFila++;
			
			$pdf->Cell($arrayTamCol[0],12,date("d-m-Y", strtotime($row['fechaRegistroFactura'])),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[1],12,$row['numeroFactura'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[2],12,$row['numeroControl'],'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[3],12,$row['numeracion_pedido'],'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[4],12,$row['numeracion_presupuesto'],'LR',0,'R',true);		
			$pdf->Cell($arrayTamCol[5],12,utf8_encode(substr($row['nombre_cliente'],0,26)),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[6],12,utf8_encode(substr($row['nombreBanco'],0,30)),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[7],12,(($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO"),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[8],12,htmlentities($row['nombre_poliza']),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[9],12,number_format($row['monto_seguro'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[10],12,number_format($row['subtotal_accesorios'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[11],12,number_format($row['total'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Ln();
			
			$arrayTotal[5] += 1;
			$arrayTotal[15] += $row['monto_seguro'];
			$arrayTotal[16] += $row['subtotal_accesorios'];
			$arrayTotal[17] += $row['saldoFactura'];
			$arrayTotal[18] += $row['total'];
			
			$totalNeto += $row['total_neto'];
			$totalIva += $row['total_iva'];
			$totalFacturas += $row['total'];
		}
			
		$pdf->MultiCell('',0,'',1,'C',true); // CIERRA LINEA DE TABLA
		
		$pdf->Ln();
		
		$pdf->SetFillColor(255);
		$pdf->Cell(562,1,"",'T',0,'L',true);
		$pdf->Ln();
		
		// SUB-TOTAL PRESUPUESTO
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(467,12,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(112,14,"TOTAL DE TOTALES: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($arrayTotal[15],2,".",","),1,0,'R',true);
		$pdf->Cell(55,14,number_format($arrayTotal[16],2,".",","),1,0,'R',true);
		$pdf->Cell(55,14,number_format($arrayTotal[18],2,".",","),1,0,'R',true);
		$pdf->Ln();
		$pdf->Ln();
		
		// TOTAL PRESUPUESTO
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total Neto: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalNeto,2,".",","),1,0,'R',true);
		$pdf->Ln();
		
		//$precioFinal = $rowEncabezado['subtotal'] + $rowEncabezado['subtotal_iva'];

		// TOTAL PRESUPUESTO
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total I.V.A.: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalIva,2,".",","),1,0,'R',true);
		$pdf->Ln();
		
		// TOTAL FACTURA
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total Factura(s): ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalFacturas,2,".",","),1,0,'R',true);
		
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
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>