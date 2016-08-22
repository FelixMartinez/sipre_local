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

$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;
$txtFechaDesde = $valCadBusq[1];
$txtFechaHasta = $valCadBusq[2];
$txtCriterio = $valCadBusq[3];

$totalRows = 1;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// FACTURACIÓN DE VEHICULOS ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s",
	$idEmpresa);
$rsEmpresa = mysql_query($queryEmpresa);
if (!$rsEmpresa) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
			$pdf->Cell(562,5,"FACTURACIÓN DE VEHICULOS",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("60","45","45","50","94","80","40","50","50","50");
			$arrayCol = array("EMPRESA\n\n","FECHA\n\n","NRO. PEDIDO\n\n","NRO. PRESUPUESTO\n","CLIENTE\n\n","VEHÍCULO\n\n","PLACA\n\n","PRECIO VENTA\n","% INICIAL\n\n","TOTAL GENERAL\n");
			
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
		$sqlBusq .= $cond.sprintf("estado_pedido IN (1,2,4)");
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(
			(SELECT COUNT(acc_ped.id_pedido) FROM an_accesorio_pedido acc_ped
			WHERE acc_ped.id_pedido = ped_vent.id_pedido
				AND acc_ped.estatus_accesorio_pedido = 0) > 0
			OR (SELECT COUNT(paq_ped.id_pedido) FROM an_paquete_pedido paq_ped
				WHERE paq_ped.id_pedido = ped_vent.id_pedido AND paq_ped.estatus_paquete_pedido = 0) > 0
			OR (SELECT COUNT(uni_fis.id_unidad_fisica) FROM an_unidad_fisica uni_fis
				WHERE uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica
					AND uni_fis.estado_venta = 'RESERVADO') > 0)");
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("ped_vent.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("ped_vent.fecha BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(ped_vent.id_pedido LIKE %s
			OR ped_vent.id_presupuesto LIKE %s
			OR ped_vent.id_cliente LIKE %s
			OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
			OR uni_fis.placa LIKE %s)",
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"));
		}
			
		// DETALLE DEL LSITADO
			$queryDetalle = sprintf("SELECT 
				ped_vent.id_pedido,
				ped_vent.fecha,
				ped_vent.id_presupuesto,
				pres_vent_acc.id_presupuesto_accesorio,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				CONCAT(uni_bas.nom_uni_bas, ': ', marca.nom_marca, ' ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
				uni_fis.placa,
				ped_vent.precio_venta,
				
				(ped_vent.precio_venta + IFNULL((SELECT SUM(ped_vent.precio_venta * iva.iva / 100)
					FROM an_unidad_basica_impuesto uni_bas_impuesto
						INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
					WHERE uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas
						AND iva.tipo IN (2,6)), 0)) AS precio_venta,
				
				ped_vent.porcentaje_inicial,
				ped_vent.inicial AS monto_inicial,
				ped_vent.total_inicial_gastos AS total_general,
				
				(SELECT an_factura_venta.tipo_factura FROM an_factura_venta
				WHERE an_factura_venta.numeroPedido = ped_vent.id_pedido
					AND (SELECT COUNT(an_factura_venta.numeroPedido) FROM an_factura_venta
					WHERE an_factura_venta.numeroPedido = ped_vent.id_pedido
						AND tipo_factura IN (1,2)) = 1) AS tipo_factura,
				pres_vent.estado AS estado_presupuesto,
				ped_vent.estado_pedido,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM an_pedido ped_vent
				INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
				LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
				LEFT JOIN an_presupuesto pres_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
				LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
				LEFT JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
				LEFT JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
				LEFT JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
				LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version) 
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s ORDER BY id_pedido DESC", $sqlBusq);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
				
				$nombreEmpresa = $rowDetalle['nombre_empresa'];
				$fecha = $rowDetalle['fecha'];
				$numeroPedido = $rowDetalle['id_pedido'];
				$numeroPresupuesto = $rowDetalle['id_presupuesto'];
				$nombreCliente = $rowDetalle['nombre_cliente'];
				$vehiculo = $rowDetalle['vehiculo'];
				$placa = $rowDetalle['placa'];
				$precioventa = $rowDetalle['precio_venta'];
				$inicial = $rowDetalle['porcentaje_inicial'];
				$totalGeneral = $rowDetalle['total_general'];
					
						$pdf->Cell($arrayTamCol[0],12,$nombreEmpresa,'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[1],12,date("d-m-Y", strtotime($fecha)),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[2],12,utf8_encode($numeroPedido),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[3],12,utf8_encode($numeroPresupuesto),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[4],12,utf8_encode($nombreCliente),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[5],12,utf8_encode($vehiculo),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[6],12,utf8_encode($placa),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[7],12,number_format($precioventa,2,".",","),'LR',0,'R',true);
						$pdf->Cell($arrayTamCol[8],12,number_format($inicial,2,".",",")."%",'LR',0,'R',true);
						$pdf->Cell($arrayTamCol[9],12,number_format($totalGeneral,2,".",","),'LR',0,'R',true);
						$pdf->Ln();
						
				$precioventaTotal += $precioventa;
			}
			
			$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
			
			$pdf->Ln();
			
			$pdf->SetFillColor(255);
			$pdf->Cell(562,5,"",'T',0,'L',true);
			$pdf->Ln();
						
			// TOTAL ANTCIPOS
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(438,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"TOTAL: ",1,0,'L',true);
			$pdf->Cell(54,14,number_format($precioventaTotal,2,".",","),1,0,'R',true);
			
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