<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
require_once("../../connections/conex.php");
session_start();

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

include("clase_excel.php");

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$rs = mysql_query(sprintf("SELECT IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa FROM vw_iv_empresas_sucursales vw_iv_emp_suc WHERE vw_iv_emp_suc.id_empresa_reg = %s;", valTpDato($valCadBusq[0], "int")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayEmpresa[] = $row['nombre_empresa'];
	}
	$arrayCriterioBusqueda[] = "Empresa: ".((isset($arrayEmpresa)) ? implode(", ", $arrayEmpresa) : "");
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$arrayCriterioBusqueda[] = "Fecha: Desde ".$valCadBusq[1]." Hasta ".$valCadBusq[2];
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	foreach (explode(",", $valCadBusq[3]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM vw_pg_empleados empleado WHERE id_empleado = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayVendedor[] = $row['nombre_empleado'];
		}
	}
	$arrayCriterioBusqueda[] = "Vendedor: ".((isset($arrayVendedor)) ? implode(", ", $arrayVendedor) : "");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$lstAplicaLibro = array(0 => "No", 1 => "Si");
	foreach ($lstAplicaLibro as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[4]))) {
			$arrayAplicaLibro[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Aplica Libro: ".((isset($arrayAplicaLibro)) ? implode(", ", $arrayAplicaLibro) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$lstEstadoFactura = array(0 => "No Cancelado", 1 => "Cancelado", 2 => "Cancelado Parcial");
	foreach ($lstEstadoFactura as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[5]))) {
			$arrayEstadoFactura[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estado Factura: ".((isset($arrayEstadoFactura)) ? implode(", ", $arrayEstadoFactura) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$lstTipoPago = array(0 => "Crédito", 1 => "Contado");
	foreach ($lstTipoPago as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[6]))) {
			$arrayTipoPago[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Tipo Pago: ".((isset($arrayTipoPago)) ? implode(", ", $arrayTipoPago) : "");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	foreach (explode(",", $valCadBusq[7]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE id_enlace_concepto = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayModulo[] = $row['descripcionModulo'];
		}
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$lstEstadoPedido = array("NO" => "Factura", "SI" => "Factura (Con Devolución)");
	foreach ($lstEstadoPedido as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[8]))) {
			$arrayEstadoPedido[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Ver: ".((isset($arrayEstadoPedido)) ? implode(", ", $arrayEstadoPedido) : "");
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[10];
}

////////// CRITERIO DE BUSQUEDA //////////
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
	$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
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
	$sqlBusq .= $cond.sprintf("cxc_fact.condicionDePago = %s",
		valTpDato($valCadBusq[6], "boolean"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($valCadBusq[7], "campo"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.anulada LIKE %s",
		valTpDato($valCadBusq[8], "text"));
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

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	cxc_fact.idFactura,
	cxc_fact.id_empresa,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.fechaVencimientoFactura,
	cxc_fact.numeroFactura,
	cxc_fact.numeroControl,
	cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
	cxc_fact.condicionDePago,
	ped_vent.id_pedido,
	ped_vent.numeracion_pedido,
	pres_vent.id_presupuesto,
	pres_vent.numeracion_presupuesto,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	uni_fis.placa,
	ped_comp_det.flotilla,
	cxc_fact.estadoFactura,
	(CASE cxc_fact.estadoFactura
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS descripcion_estado_factura,
	cxc_fact.aplicaLibros,
	cxc_fact.anulada,
	cxc_fact.observacionFactura,
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
ORDER BY idFactura DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$queryFormaPago = ("SELECT * FROM formapagos ORDER BY nombreFormaPago ASC");
$rsFormaPago = mysql_query($queryFormaPago);
if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFormaPago = mysql_num_rows($rsFormaPago);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Formas de Pago");
$contColumFP = "P";
for ($i = 1; $i < $totalRowsFormaPago; $i++) { $contColumFP++; }
$objPHPExcel->getActiveSheet()->getStyle("P".$contFila.":".($contColumFP).$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->mergeCells("P".$contFila.":".($contColumFP).$contFila);

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Venc. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Pedido / Orden");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Estado Factura");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Saldo Factura");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Total Factura");

$contColum = "O";
while ($rowFormaPago = mysql_fetch_assoc($rsFormaPago)) {
	$contColum++;
	
	$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $rowFormaPago['nombreFormaPago']);
}
$contColumUlt = $contColum;

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".($contColumUlt).$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['id_modulo']) {
		case 0 : $imgDctoModulo = "Repuestos"; break;
		case 1 : $imgDctoModulo = "Servicios"; break;
		case 2 : $imgDctoModulo = "Vehículos"; break;
		case 3 : $imgDctoModulo = "Administración"; break;
		case 4 : $imgDctoModulo = "Alquiler"; break;
		default : $imgDctoModulo = $row['id_modulo'];
	}
	
	$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "Creada por CxC";
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgDctoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgDctoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date("d-m-Y",strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date("d-m-Y",strtotime($row['fechaVencimientoFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['numeroControl'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFila, $row['numero_pedido'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($row['ci_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($row['descripcion_estado_factura']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['saldoFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['montoTotalFactura']);
	
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFormaPago = mysql_num_rows($rsFormaPago);
	$contColum = "O";
	while ($rowFormaPago = mysql_fetch_assoc($rsFormaPago)) {
		$contColum++;
		
		$queryDctoPago = sprintf("SELECT *
		FROM (SELECT 
				cxc_pago.id_factura,
				cxc_pago.fechaPago,
				cxc_pago.formaPago,
				cxc_pago.montoPagado
			FROM sa_iv_pagos cxc_pago
			
			UNION
			
			SELECT 
				cxc_pago.id_factura,
				cxc_pago.fechaPago,
				cxc_pago.formaPago,
				cxc_pago.montoPagado
			FROM an_pagos cxc_pago) AS query
		WHERE query.id_factura = %s
			AND query.formaPago = %s",
			valTpDato($row['idFactura'], "int"),
			valTpDato($rowFormaPago['idFormaPago'], "int"));
		$rsDctoPago = mysql_query($queryDctoPago);
		if (!$rsDctoPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsDctoPago = mysql_num_rows($rsDctoPago);
		$totalPagosDcto = 0;
		while ($rowDctoPago = mysql_fetch_assoc($rsDctoPago)) {
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $rowDctoPago['montoPagado']);
			$totalPagosDcto += $rowDctoPago['montoPagado'];
		}
		
		$arrayTotalPago[$contColum] += $totalPagosDcto;
	}
	$contColumUlt = $contColum;
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".($contColumUlt).$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$contColum = "O";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	}
	
	
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$contColum = "O";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	}
	
	$arrayTotal[12] += $row['cant_items'];
	$arrayTotal[13] += $row['saldoFactura'];
	$arrayTotal[14] += $row['montoTotalFactura'];
	$cont = 14;
	if (isset($arrayTotalPago)) {
		foreach ($arrayTotalPago as $indice => $valor) {
			$cont++;
			$arrayTotal[$cont] = $arrayTotalPago[$indice];
		}
	}
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":".($contColumUlt).$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$contColum = "L";
if (isset($arrayTotalPago)) {
	foreach ($arrayTotal as $indice => $valor) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $arrayTotal[$indice]);
		
		if ($indice == 12) {
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		} else {
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
		}
	}
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."L".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila.":".($contColumUlt).$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."L".$contFila);

for ($col = "A"; $col != ($contColumUlt); $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, ($contColumUlt), true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Factura de Venta";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:".($contColumUlt)."7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:".($contColumUlt)."9");
}

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
//$objPHPExcel->getSecurity()->setLockWindows(true);
//$objPHPExcel->getSecurity()->setLockStructure(true);

$objPHPExcel->getProperties()->setCreator("SIPRE 3.0");
//$objPHPExcel->getProperties()->setLastModifiedBy("autor");
$objPHPExcel->getProperties()->setTitle($tituloDcto);
//$objPHPExcel->getProperties()->setSubject("Asunto");
//$objPHPExcel->getProperties()->setDescription("Descripcion");

// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$tituloDcto.'.xlsx"');
header('Cache-Control: max-age=0');
 
//Creamos el Archivo .xlsx
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>