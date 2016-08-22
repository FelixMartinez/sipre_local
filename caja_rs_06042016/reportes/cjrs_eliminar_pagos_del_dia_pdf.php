<?php
require_once ("../../connections/conex.php");

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
$lstTipoPago = $valCadBusq[1];
$txtCriterio = $valCadBusq[2];

//PUEDE SER NULL AL SELECCIONAR [TODOS] EN LA BUSQUEDA
if ($idEmpresa == NULL || $idEmpresa == -1) {
	$idEmpresa = '1';
}

$totalRows = 1;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// ELIMINACIÓN DE PAGOS DEL DÍA ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
			$pdf->Cell(562,5,"CAJA DE REPUESTOS Y SERVICIOS",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(562,5,"ELIMINACIÓN DE PAGOS DEL DÍA",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("100","70","60","60","130","70","74");
			$arrayCol = array("EMPRESA\n\n","TIPO DCTO.\n\n","NRO. DCTO.\n\n","NRO. REFERENCIA\n\n","CLIENTE\n\n","TIPO PAGO\n\n","MONTO\n\n");
			
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
		$fechaActual = date("Y-m-d");

		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
			$sqlBusqNotaCargo .= $cond.sprintf("cj_cc_notadecargo.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
				
			$sqlBusqFactura .= $cond.sprintf("cj_cc_encabezadofactura.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));			
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$sqlBusqNotaCargo .= " AND ".sprintf("cj_det_nota_cargo.idFormaPago = %s",
				valTpDato($valCadBusq[1], "int"));
				
			$sqlBusqFactura .= " AND ".sprintf("sa_iv_pagos.formaPago = %s",
				valTpDato($valCadBusq[1], "int"));
		}
		
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
			$sqlBusqNotaCargo .= $cond.sprintf("(cj_cc_notadecargo.numeroNotaCargo LIKE %s
			OR cj_cc_notadecargo.numeroNotaCargo LIKE %s)",
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"));
				
			$sqlBusqFactura .= $cond.sprintf("(cj_cc_encabezadofactura.numeroFactura LIKE %s
			OR cj_cc_encabezadofactura.numeroFactura LIKE %s)",
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"));			
		}
		
		// DETALLE DEL LSITADO
			$queryDetalle = sprintf("SELECT
				'NOTA CARGO' AS tipo_documento,
				cj_cc_notadecargo.id_empresa AS id_empresa,
				cj_det_nota_cargo.id_det_nota_cargo AS id_pago,
				cj_det_nota_cargo.idFormaPago AS id_forma_pago,
				formapagos.nombreFormaPago AS tipo_pago,
				cj_det_nota_cargo.numeroDocumento AS numero_control_pago,
				cj_det_nota_cargo.monto_pago AS monto_pagado,
				cj_cc_notadecargo.numeroNotaCargo AS numero_documento,
				cj_cc_notadecargo.idCliente AS id_cliente,
				CONCAT_WS(' ', nombre, apellido ) AS cliente,
				cj_det_nota_cargo.idNotaCargo AS id_documento,
				'cj_det_nota_cargo' AS tabla_detalle,
				'cj_cc_notadecargo' AS tabla_cabecera,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM
				cj_cc_notadecargo
				INNER JOIN cj_det_nota_cargo ON (cj_cc_notadecargo.idNotaCargo = cj_det_nota_cargo.idNotaCargo)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_notadecargo.id_empresa = vw_iv_emp_suc.id_empresa_reg)
				INNER JOIN formapagos ON (cj_det_nota_cargo.idFormaPago = formapagos.idFormaPago)
				INNER JOIN cj_cc_cliente ON (cj_cc_notadecargo.idCliente = cj_cc_cliente.id)
			WHERE
				cj_det_nota_cargo.fechaPago = %s AND 
				cj_det_nota_cargo.idCaja = 2 %s
				
			UNION
			
			SELECT
				'FACTURA' AS tipo_documento,
				cj_cc_encabezadofactura.id_empresa AS id_empresa,
				sa_iv_pagos.idPago AS id_pago,
				sa_iv_pagos.formaPago AS id_forma_pago,
				formapagos.nombreFormaPago AS tipo_pago,
				sa_iv_pagos.numeroDocumento AS numero_control_pago,
				sa_iv_pagos.montoPagado AS monto_pagado,
				sa_iv_pagos.numeroFactura AS numero_documento,
				cj_cc_encabezadofactura.idCliente AS id_cliente,
				CONCAT_WS(' ', nombre, apellido ) AS cliente,
				(SELECT idFactura FROM cj_cc_encabezadofactura WHERE cj_cc_encabezadofactura.numeroFactura = sa_iv_pagos.numeroFactura
						AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura IN (0,1,3)
						AND cj_cc_encabezadofactura.montoTotalFactura > 0) AS id_documento,
				'sa_iv_pagos' AS tabla_detalle,
				'cj_cc_encabezadofactura' AS tabla_cabecera,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM
				cj_cc_encabezadofactura
				INNER JOIN sa_iv_pagos ON (cj_cc_encabezadofactura.idFactura = sa_iv_pagos.id_factura)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_encabezadofactura.id_empresa = vw_iv_emp_suc.id_empresa_reg)
				INNER JOIN formapagos ON (sa_iv_pagos.formaPago = formapagos.idFormaPago)
				INNER JOIN cj_cc_cliente ON (cj_cc_encabezadofactura.idCliente = cj_cc_cliente.id)
			WHERE
				sa_iv_pagos.fechaPago = %s AND 
				sa_iv_pagos.idCaja = 2 %s", 
			valTpDato($fechaActual,'date'),	$sqlBusqNotaCargo,
			valTpDato($fechaActual,'date'),	$sqlBusqFactura);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
				
				$nombreEmpresa = $rowDetalle['nombre_empresa'];
				$tipoDocumento = $rowDetalle['tipo_documento'];
				$numeroDocumento = $rowDetalle['numero_documento'];
				$numeroControlPago = $rowDetalle['numero_control_pago'];
				$cliente = $rowDetalle['cliente'];
				$tipoPago = $rowDetalle['tipo_pago'];
				$montoPagado = $rowDetalle['monto_pagado'];
				
				if ($condicionPago == 0)
					$condicionPago = 'Credito';
				else
					$condicionPago = 'Contado';
						$pdf->Cell($arrayTamCol[0],12,$nombreEmpresa,'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[1],12,utf8_encode($tipoDocumento),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[2],12,utf8_encode($numeroDocumento),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[3],12,utf8_encode($numeroControlPago),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[4],12,utf8_encode($cliente),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[5],12,utf8_encode($tipoPago),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[6],12,number_format($montoPagado,2,".",","),'LR',0,'R',true);
						$pdf->Ln();
						
				$montoTotalPago += $montoPagado;
			}
			
			$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
			
			$pdf->Ln();
			
			$pdf->SetFillColor(255);
			$pdf->Cell(562,5,"",'T',0,'L',true);
			$pdf->Ln();
						
			// TOTAL ANTCIPOS
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(442,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"TOTAL: ",1,0,'L',true);
			$pdf->Cell(50,14,number_format($montoTotalPago,2,".",","),1,0,'R',true);
			
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