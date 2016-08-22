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
$idEmpresa = $valCadBusq[0];
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
	valTpDato("2", "campo"));

$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
$sqlBusq3 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
	valTpDato("2", "campo"));

$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
$sqlBusq4 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
	valTpDato("2", "campo"));

$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
$sqlBusq6 .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
	valTpDato("2", "campo"));

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("vale_ent.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
		
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_nc.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_fact.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
		
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("vale_sal.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
		
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("cxp_nc.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(cxp_fact.fecha_origen) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("DATE(vale_ent.fecha) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("DATE(cxc_nc.fechaNotaCredito) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("DATE(cxc_fact.fechaRegistroFactura) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("DATE(vale_sal.fecha) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("DATE(cxp_nc.fecha_registro_notacredito) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("query.id_tipo_movimiento IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

/*if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_clave_movimiento = %s",
		valTpDato($valCadBusq[5], "int"));
}*/

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("query.id_empleado_vendedor = %s",
		valTpDato($valCadBusq[6], "int"));
}

if ($valCadBusq[7] != "" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("(query.numero_documento LIKE %s
	OR query.numero_control_documento LIKE %s
	OR query.ci_cliente LIKE %s
	OR query.nombre_cliente LIKE %s)",
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT *
FROM (SELECT 
		cxp_fact.id_factura AS id_documento,
		cxp_fact.numero_factura_proveedor AS numero_documento,
		cxp_fact.numero_control_factura AS numero_control_documento,
		cxp_fact.fecha_factura_proveedor AS fecha_documento,
		cxp_fact.fecha_origen AS fecha_registro,
		cxp_fact.id_modulo,
		CONCAT_WS('-', prov.lrif, prov.rif) AS ci_cliente,
		prov.nombre AS nombre_cliente,
		NULL AS id_empleado_vendedor,
		1 AS id_tipo_movimiento,
		NULL AS tipo_documento_movimiento
	FROM cp_factura cxp_fact
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor) %s
	
	UNION
		
	SELECT 
		vale_ent.id_vale_entrada,
		vale_ent.numeracion_vale_entrada,
		vale_ent.numeracion_vale_entrada,
		vale_ent.fecha,
		vale_ent.fecha,
		2 AS id_modulo,
		CONCAT_WS('-', cliente.lci, cliente.ci),
		CONCAT_WS('-', cliente.nombre, cliente.apellido),
		NULL AS id_empleado_vendedor,
		2 AS id_tipo_movimiento,
		1 AS tipo_documento_movimiento
	FROM an_vale_entrada vale_ent
		INNER JOIN cj_cc_cliente cliente ON (vale_ent.id_cliente = cliente.id) %s
	
	UNION
	
	SELECT 
		cxc_nc.idNotaCredito,
		cxc_nc.numeracion_nota_credito,
		cxc_nc.numeroControl,
		cxc_nc.fechaNotaCredito,
		cxc_nc.fechaNotaCredito,
		cxc_nc.idDepartamentoNotaCredito,
		CONCAT_WS('-', cliente.lci, cliente.ci),
		CONCAT_WS('-', cliente.nombre, cliente.apellido),
		cxc_nc.id_empleado_vendedor,
		2 AS id_tipo_movimiento,
		2 AS tipo_documento_movimiento
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id) %s
	
	UNION
		
	SELECT
		cxc_fact.idFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.idDepartamentoOrigenFactura,
		CONCAT_WS('-', cliente.lci, cliente.ci),
		CONCAT_WS('-', cliente.nombre, cliente.apellido),
		cxc_fact.idVendedor AS id_empleado_vendedor,
		3 AS id_tipo_movimiento,
		NULL AS tipo_documento_movimiento
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id) %s
	
	UNION
		
	SELECT 
		vale_sal.id_vale_salida,
		vale_sal.numeracion_vale_salida,
		vale_sal.numeracion_vale_salida,
		vale_sal.fecha,
		vale_sal.fecha,
		2 AS id_modulo,
		CONCAT_WS('-', cliente.lci, cliente.ci),
		CONCAT_WS('-', cliente.nombre, cliente.apellido),
		NULL AS id_empleado_vendedor,
		4 AS id_tipo_movimiento,
		1 AS tipo_documento_movimiento
	FROM an_vale_salida vale_sal
		INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id) %s
	
	UNION
		
	SELECT 
		cxp_nc.id_notacredito,
		cxp_nc.numero_nota_credito,
		cxp_nc.numero_control_notacredito,
		cxp_nc.fecha_notacredito,
		cxp_nc.fecha_registro_notacredito,
		cxp_nc.id_departamento_notacredito,
		CONCAT_WS('-', prov.lrif, prov.rif) AS ci_cliente,
		prov.nombre AS nombre_cliente,
		NULL AS id_empleado_vendedor,
		4 AS id_tipo_movimiento,
		2 AS tipo_documento_movimiento
	FROM cp_notacredito cxp_nc
		INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor) %s) AS query %s
ORDER BY query.id_tipo_movimiento, query.id_documento ASC;", $sqlBusq, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6, $sqlBusq7);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$contFila = 0;
while ($row = mysql_fetch_assoc($rs)) {
	if ($row['id_tipo_movimiento'] == 1) {
		$queryDetalle = sprintf("SELECT
			vw_iv_modelo.nom_uni_bas,
			CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
			1 AS cantidad,
			cxp_fact_det_unidad.costo_unitario AS costo_compra,
			cxp_fact_det_unidad.costo_unitario AS precio_unitario
		FROM cp_factura_detalle_unidad cxp_fact_det_unidad
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (cxp_fact_det_unidad.id_unidad_basica = vw_iv_modelo.id_uni_bas)
		WHERE cxp_fact_det_unidad.id_factura = %s
		
		UNION
		
		SELECT 
			acc.nom_accesorio,
			acc.des_accesorio,
			cxp_fact_det_acc.cantidad,
			cxp_fact_det_acc.costo_unitario,
			cxp_fact_det_acc.costo_unitario
		FROM cp_factura_detalle_accesorio cxp_fact_det_acc
			INNER JOIN an_accesorio acc ON (cxp_fact_det_acc.id_accesorio = acc.id_accesorio)
		WHERE cxp_fact_det_acc.id_factura = %s;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_documento'], "int"));
	} else if ($row['id_tipo_movimiento'] == 2) {
		if ($row['tipo_documento_movimiento'] == 1) {
			$queryDetalle = sprintf("SELECT
				vw_iv_modelo.nom_uni_bas,
				CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
				1 AS cantidad,
				subtotal_factura AS costo_compra,
				subtotal_factura AS precio_unitario
			FROM an_vale_entrada vale_ent
				INNER JOIN an_unidad_fisica uni_fis ON (vale_ent.id_unidad_fisica = uni_fis.id_unidad_fisica)
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			WHERE vale_ent.id_vale_entrada = %s;",
				valTpDato($row['id_documento'], "int"));
		} else if ($row['tipo_documento_movimiento'] == 2) {
			$queryDetalle = sprintf("SELECT
				vw_iv_modelo.nom_uni_bas,
				CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
				1 AS cantidad,
				cxc_nc_det_vehic.costo_compra,
				cxc_nc_det_vehic.precio_unitario
			FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
				INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			WHERE cxc_nc_det_vehic.id_nota_credito = %s
			
			UNION
			
			SELECT 
				acc.nom_accesorio,
				acc.des_accesorio,
				cxc_nc_det_acc.cantidad,
				cxc_nc_det_acc.costo_compra,
				cxc_nc_det_acc.precio_unitario
			FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
				INNER JOIN an_accesorio acc ON (cxc_nc_det_acc.id_accesorio = acc.id_accesorio)
			WHERE cxc_nc_det_acc.id_nota_credito = %s;",
				valTpDato($row['id_documento'], "int"),
				valTpDato($row['id_documento'], "int"));
		}
	} else if ($row['id_tipo_movimiento'] == 3) {
		$queryDetalle = sprintf("SELECT
			vw_iv_modelo.nom_uni_bas,
			CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
			1 AS cantidad,
			cxc_fact_det_vehic.costo_compra,
			cxc_fact_det_vehic.precio_unitario
		FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
		  INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		  INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		WHERE cxc_fact_det_vehic.id_factura = %s
		
		UNION
		
		SELECT 
			acc.nom_accesorio,
			acc.des_accesorio,
			cxc_fact_det_acc.cantidad,
			cxc_fact_det_acc.costo_compra,
			cxc_fact_det_acc.precio_unitario
		FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
			INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
		WHERE cxc_fact_det_acc.id_factura = %s;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_documento'], "int"));
	} else if ($row['id_tipo_movimiento'] == 4) {
		if ($row['tipo_documento_movimiento'] == 1) {
			$queryDetalle = sprintf("SELECT
				vw_iv_modelo.nom_uni_bas,
				CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
				1 AS cantidad,
				subtotal_factura AS costo_compra,
				subtotal_factura AS precio_unitario
			FROM an_vale_salida vale_sal
				INNER JOIN an_unidad_fisica uni_fis ON (vale_sal.id_unidad_fisica = uni_fis.id_unidad_fisica)
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			WHERE vale_sal.id_vale_salida = %s;",
				valTpDato($row['id_documento'], "int"));
		} else if ($row['tipo_documento_movimiento'] == 2) {
			$queryDetalle = sprintf("SELECT
				vw_iv_modelo.nom_uni_bas,
				CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
				1 AS cantidad,
				cxp_fact_det_unidad.costo_unitario,
				cxp_fact_det_unidad.costo_unitario
			FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (cxp_fact_det_unidad.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det_unidad.id_factura = cxp_nc.id_documento)
			WHERE cxp_nc.id_notacredito = %s
				AND cxp_nc.tipo_documento LIKE 'FA'
			
			UNION
			
			SELECT 
				acc.nom_accesorio,
				acc.des_accesorio,
				cxp_fact_det_acc.cantidad,
				cxp_fact_det_acc.costo_unitario,
				cxp_fact_det_acc.costo_unitario
			FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				INNER JOIN an_accesorio acc ON (cxp_fact_det_acc.id_accesorio = acc.id_accesorio)
				INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det_acc.id_factura = cxp_nc.id_documento)
			WHERE cxp_nc.id_notacredito = %s
				AND cxp_nc.tipo_documento LIKE 'FA';",
				valTpDato($row['id_documento'], "int"),
				valTpDato($row['id_documento'], "int"));
		}
	}
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	
	if ($totalRowsDetalle > 0) {
		$contFila++;
		
		$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Nro. Dcto:");
		$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFila, utf8_encode($row['numero_documento']));
		$objPHPExcel->getActiveSheet()->SetCellValue("D".$contFila, "Folio:");
		$objPHPExcel->getActiveSheet()->SetCellValue("E".$contFila, utf8_encode($row['numero_control_documento']));
		$objPHPExcel->getActiveSheet()->SetCellValue("F".$contFila, "Fecha Cap:");
		$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFila, date("d-m-Y",strtotime($row['fecha_registro'])));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->applyFromArray($styleArrayCampo);
		
		$contFila++;
		$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Prov./Clnte./Emp.:");
		$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFila, utf8_encode($row['ci_cliente']));
		$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFila, utf8_encode($row['nombre_cliente']));
		$objPHPExcel->getActiveSheet()->SetCellValue("F".$contFila, "Nro. Orden:");
		$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFila, date("d-m-Y",strtotime($row['fecha_registro'])));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->applyFromArray($styleArrayCampo);
		
		$objPHPExcel->getActiveSheet()->mergeCells("C".$contFila.":E".$contFila);
		
		$contFila++;
		$primero = $contFila;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Cantidad");
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Costo Unit.");
		$objPHPExcel->getActiveSheet()->SetCellValue("E".$contFila, $spanPrecioUnitario);
		$objPHPExcel->getActiveSheet()->SetCellValue("F".$contFila, "Importe Precio");
		$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFila, "Dscto.");
		$objPHPExcel->getActiveSheet()->SetCellValue("H".$contFila, "Neto");
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Importe Costo");
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Utl.");
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "%Utl.");
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "%Dscto.");
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($styleArrayColumna);
	}
	
	$arrayTotal = NULL;
	$contFila2 = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)){
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio_unitario'];
		$descuento = $rowDetalle['porcentaje_descuento'] * $importePrecio / 100;
		$neto = $importePrecio - $descuento;
		
		$importeCosto = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $rowDetalle['cantidad'] * $rowDetalle['costo_compra'];
		
		$porcUtilidad = 0;
		if ($importePrecio > 0) {
			$utilidad = $neto - $importeCosto;
			$porcUtilidad = $utilidad * 100 / $importePrecio;
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode($rowDetalle['nom_uni_bas']));
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($rowDetalle['vehiculo']));
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $rowDetalle['cantidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $rowDetalle['costo_compra']);
		$objPHPExcel->getActiveSheet()->SetCellValue("E".$contFila, $rowDetalle['precio_unitario']);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $importePrecio);
		$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFila, $descuento);
		$objPHPExcel->getActiveSheet()->SetCellValue("H".$contFila, $neto);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $importeCosto);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $utilidad);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $porcUtilidad);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $rowDetalle['porcentaje_descuento']);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayTotal[3] += $rowDetalle['cantidad'];
		$arrayTotal[6] += $importePrecio;
		$arrayTotal[7] += $descuento;
		$arrayTotal[8] += $neto;
		$arrayTotal[9] += $importeCosto;
		$arrayTotal[10] += $utilidad;
	}
	$ultimo = $contFila;
	
	if ($totalRowsDetalle > 0) {
		$contFila++;
		$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Total:");
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotal[3]);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTotal[6]);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotal[7]);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotal[8]);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotal[9]);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $arrayTotal[10]);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($arrayTotal[6] > 0) ? $arrayTotal[10] * 100 / $arrayTotal[6] : 0));
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, (($arrayTotal[6] > 0) ? $arrayTotal[7] * 100 / $arrayTotal[6] : 0));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila.":"."L".$contFila)->applyFromArray($styleArrayResaltarTotal);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);
		
		$contFila++;
		$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "");
					
		$arrayTotalFinal[3] += $arrayTotal[3];
	}
	
	//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
}

for ($col = "A"; $col != "L"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "L");

$tituloDcto = "Listado de Movimientos";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:L7");

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
$objPHPExcel->getSecurity()->setLockWindows(true);
$objPHPExcel->getSecurity()->setLockStructure(true);

$objPHPExcel->getProperties()->setCreator("SIPRE ".cVERSION);
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