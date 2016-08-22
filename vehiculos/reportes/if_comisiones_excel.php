<?php
require_once("../../connections/conex.php");

include '../../clases/excelXml/excel_xml.php';
$excel = new excel_xml();

$headerStyle = array('bold' => 1, 'size' => '8', 'color' => '#FFFFFF', 'bgcolor' => '#021933');

$trCabecera =  array('bold' => 1, 'size' => '8', 'color' => '#000000');

$trResaltar4 = array('size' => '8', 'bgcolor' => '#FFFFFF');
$trResaltar5 = array('size' => '8', 'bgcolor' => '#D7D7D7');
$trResaltarTotal = array('size' => '8', 'bgcolor' => '#E6FFE6', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal2 = array('size' => '8', 'bgcolor' => '#DDEEFF', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal3 = array('size' => '8', 'bgcolor' => '#FFEED5', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');

$excel->add_style('header', $headerStyle);
$excel->add_style('trCabecera', $trCabecera);
$excel->add_style('trResaltar4', $trResaltar4);
$excel->add_style('trResaltar5', $trResaltar5);
$excel->add_style('trResaltarTotal', $trResaltarTotal);
$excel->add_style('trResaltarTotal2', $trResaltarTotal2);
$excel->add_style('trResaltarTotal3', $trResaltarTotal3);

$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);
$valFecha = explode("-", $valCadBusq[0]);

$startRow = $pageNum * $maxRows;


if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			MONTH((SELECT fact_vent.fechaRegistroFactura
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idFactura = comision_emp.id_factura))
		WHEN (id_nota_credito IS NOT NULL) THEN
			MONTH((SELECT nota_cred.fechaNotaCredito
			FROM cj_cc_notacredito nota_cred
			WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito))
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			MONTH((SELECT vale_sal.fecha_vale
			FROM sa_vale_salida vale_sal
			WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida))
	END) = %s",
		valTpDato($valFecha[0], "int"));
}

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			YEAR((SELECT fact_vent.fechaRegistroFactura
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idFactura = comision_emp.id_factura))
		WHEN (id_nota_credito IS NOT NULL) THEN
			YEAR((SELECT nota_cred.fechaNotaCredito
			FROM cj_cc_notacredito nota_cred
			WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito))
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			YEAR((SELECT vale_sal.fecha_vale
			FROM sa_vale_salida vale_sal
			WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida))
	END) = %s",
		valTpDato($valFecha[1], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT cargo_dep.id_cargo
	FROM pg_cargo_departamento cargo_dep
		INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
		INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
	WHERE cargo_dep.id_cargo_departamento = empleado.id_cargo_departamento) = %s",
		valTpDato($valCadBusq[1], "int"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("comision_emp.id_empleado = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			(SELECT fact_vent.idDepartamentoOrigenFactura
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idFactura = comision_emp.id_factura)
		WHEN (id_nota_credito IS NOT NULL) THEN
			(SELECT nota_cred.idDepartamentoNotaCredito
			FROM cj_cc_notacredito nota_cred
			WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito)
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			(SELECT 1
			FROM sa_vale_salida vale_sal
			WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida)
	END) = %s",
		valTpDato($valCadBusq[3], "int"));
}

$queryComision = sprintf("SELECT
	empleado.id_empleado,
	empleado.cedula,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			(SELECT fact_vent.idDepartamentoOrigenFactura
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idFactura = comision_emp.id_factura)
		WHEN (id_nota_credito IS NOT NULL) THEN
			(SELECT nota_cred.idDepartamentoNotaCredito
			FROM cj_cc_notacredito nota_cred
			WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito)
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			(SELECT 1
			FROM sa_vale_salida vale_sal
			WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida)
	END) AS id_modulo,
	
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			MONTH((SELECT fact_vent.fechaRegistroFactura
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idFactura = comision_emp.id_factura))
		WHEN (id_nota_credito IS NOT NULL) THEN
			MONTH((SELECT nota_cred.fechaNotaCredito
			FROM cj_cc_notacredito nota_cred
			WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito))
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			MONTH((SELECT vale_sal.fecha_vale
			FROM sa_vale_salida vale_sal
			WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida))
	END) AS mes_documento,
	
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			YEAR((SELECT fact_vent.fechaRegistroFactura
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idFactura = comision_emp.id_factura))
		WHEN (id_nota_credito IS NOT NULL) THEN
			YEAR((SELECT nota_cred.fechaNotaCredito
			FROM cj_cc_notacredito nota_cred
			WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito))
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			YEAR((SELECT vale_sal.fecha_vale
			FROM sa_vale_salida vale_sal
			WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida))
	END) AS ano_documento
FROM pg_comision_empleado comision_emp
	INNER JOIN pg_empleado empleado ON (comision_emp.id_empleado = empleado.id_empleado)
%s
GROUP BY 1,2,3", $sqlBusq);
$rsComision = mysql_query($queryComision);
if (!$rsComision) die(mysql_error()."<br><br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rsComision);
$cont = 0;
while ($rowComision = mysql_fetch_assoc($rsComision)) {
	$sqlBusq2 = "";
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("id_empleado = %s",
		valTpDato($rowComision['id_empleado'], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("comision_emp.monto_comision > 0");
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(SELECT AVG(comision_emp_det.porcentaje_comision) FROM pg_comision_empleado_detalle comision_emp_det
	WHERE comision_emp_det.id_comision_empleado = comision_emp.id_comision_empleado) > 0");
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			MONTH((SELECT fact_vent.fechaRegistroFactura
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idFactura = comision_emp.id_factura))
		WHEN (id_nota_credito IS NOT NULL) THEN
			MONTH((SELECT nota_cred.fechaNotaCredito
			FROM cj_cc_notacredito nota_cred
			WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito))
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			MONTH((SELECT vale_sal.fecha_vale
			FROM sa_vale_salida vale_sal
			WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida))
	END) = %s",
		valTpDato($rowComision['mes_documento'], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			YEAR((SELECT fact_vent.fechaRegistroFactura
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idFactura = comision_emp.id_factura))
		WHEN (id_nota_credito IS NOT NULL) THEN
			YEAR((SELECT nota_cred.fechaNotaCredito
			FROM cj_cc_notacredito nota_cred
			WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito))
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			YEAR((SELECT vale_sal.fecha_vale
			FROM sa_vale_salida vale_sal
			WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida))
	END) = %s",
		valTpDato($rowComision['ano_documento'], "int"));
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT fact_vent.idDepartamentoOrigenFactura
				FROM cj_cc_encabezadofactura fact_vent
				WHERE fact_vent.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT nota_cred.idDepartamentoNotaCredito
				FROM cj_cc_notacredito nota_cred
				WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito)
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT 1
				FROM sa_vale_salida vale_sal
				WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida)
		END) = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	$queryDetalle = sprintf("SELECT
		comision_emp.id_comision_empleado,
	
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT fact_vent.idDepartamentoOrigenFactura
				FROM cj_cc_encabezadofactura fact_vent
				WHERE fact_vent.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT nota_cred.idDepartamentoNotaCredito
				FROM cj_cc_notacredito nota_cred
				WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito)
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				1
		END) AS id_modulo,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				'FA'
			WHEN (id_nota_credito IS NOT NULL) THEN
				'NC'
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				'VS'
			WHEN (id_vale_entrada IS NOT NULL) THEN
				'VE'
		END) AS tipo_documento,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT fact_vent.numeroFactura
				FROM cj_cc_encabezadofactura fact_vent
				WHERE fact_vent.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT nota_cred.numeracion_nota_credito
				FROM cj_cc_notacredito nota_cred
				WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito)
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT vale_sal.numero_vale
				FROM sa_vale_salida vale_sal
				WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida)
		END) AS numero_documento,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT fact_vent.condicionDePago
				FROM cj_cc_encabezadofactura fact_vent
				WHERE fact_vent.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT fact_venta.condicionDePago AS condicionDePago
				FROM cj_cc_encabezadofactura fact_venta
					JOIN cj_cc_notacredito nota_cred on (fact_venta.idFactura = nota_cred.idDocumento)
				WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito)
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT tp_ord.nombre_tipo_orden
				FROM sa_orden ord
					INNER JOIN sa_tipo_orden tp_ord ON (ord.id_tipo_orden = tp_ord.id_tipo_orden)
					INNER JOIN sa_vale_salida vale_sal ON (ord.id_orden = vale_sal.id_orden)
				WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida)
		END) AS tipo_pago,

		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT fact_venta.idCliente AS idCliente
				FROM cj_cc_encabezadofactura fact_venta
				WHERE (fact_venta.idFactura = comision_emp.id_factura))
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT fact_venta.idCliente AS idCliente
				FROM (cj_cc_notacredito nota_cred
					JOIN cj_cc_encabezadofactura fact_venta on ((nota_cred.idDocumento = fact_venta.idFactura)))
				WHERE (nota_cred.idNotaCredito = comision_emp.id_nota_credito))
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
								FROM sa_recepcion r
								WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
													FROM sa_orden o
													WHERE o.id_orden = vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																				FROM sa_cita c
																				WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																								FROM sa_recepcion r
																								WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																											FROM sa_orden o
																											WHERE o.id_orden = vs.id_orden)))) AS id_cliente
							FROM sa_vale_salida vs
							WHERE (vs.id_vale_salida = comision_emp.id_vale_salida))
			WHEN (id_vale_entrada IS NOT NULL) THEN
				NULL
		END) AS id_cliente,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
				FROM cj_cc_cliente cliente
				WHERE (cliente.id = (SELECT fact_venta.idCliente AS idCliente FROM cj_cc_encabezadofactura fact_venta
									WHERE (fact_venta.idFactura = comision_emp.id_factura))))
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
				FROM cj_cc_cliente cliente
				WHERE (cliente.id = (SELECT fact_venta.idCliente AS idCliente
									FROM (cj_cc_notacredito nota_cred
										JOIN cj_cc_encabezadofactura fact_venta on ((nota_cred.idDocumento = fact_venta.idFactura)))
									WHERE (nota_cred.idNotaCredito = comision_emp.id_nota_credito))))
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
				FROM cj_cc_cliente cliente
				WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
									FROM sa_recepcion r
									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
													FROM sa_orden o
													WHERE o.id_orden = vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																				FROM sa_cita c
																				WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																								FROM sa_recepcion r
																								WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																											FROM sa_orden o
																											WHERE o.id_orden = vs.id_orden)))) AS id_cliente
							FROM sa_vale_salida vs
							WHERE vs.id_vale_salida = comision_emp.id_vale_salida))
			WHEN (id_vale_entrada IS NOT NULL) THEN
				NULL
		END) AS ci_cliente,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
				FROM cj_cc_cliente cliente
				WHERE (cliente.id = (SELECT fact_venta.idCliente AS idCliente FROM cj_cc_encabezadofactura fact_venta
									WHERE (fact_venta.idFactura = comision_emp.id_factura))))
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
				FROM cj_cc_cliente cliente
				WHERE (cliente.id = (SELECT fact_venta.idCliente AS idCliente FROM (cj_cc_notacredito nota_cred
										JOIN cj_cc_encabezadofactura fact_venta on ((nota_cred.idDocumento = fact_venta.idFactura)))
									WHERE (nota_cred.idNotaCredito = comision_emp.id_nota_credito))))
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
				FROM cj_cc_cliente cliente
				WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
									FROM sa_recepcion r
									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
													FROM sa_orden o
													WHERE o.id_orden = vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																				FROM sa_cita c
																				WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																								FROM sa_recepcion r
																								WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																											FROM sa_orden o
																											WHERE o.id_orden = vs.id_orden)))) AS id_cliente
							FROM sa_vale_salida vs
							WHERE vs.id_vale_salida = comision_emp.id_vale_salida))
			WHEN (id_vale_entrada IS NOT NULL) THEN
				NULL
		END) AS nombre_cliente,
		
		comision_emp.venta_bruta,
		comision_emp.monto_descuento,
		(comision_emp.venta_bruta - comision_emp.monto_descuento) AS venta_neta,
		comision_emp.costo_compra,
		
		(	(comision_emp.venta_bruta - comision_emp.monto_descuento)
			-
			comision_emp.costo_compra) AS utilidad_bruta,
		
		(	((	(comision_emp.venta_bruta - comision_emp.monto_descuento)
				-
				comision_emp.costo_compra) * 100)
			/
			(comision_emp.venta_bruta - comision_emp.monto_descuento)) AS porcentaje_utilidad_venta,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT fact_vent.fechaRegistroFactura
				FROM cj_cc_encabezadofactura fact_vent
				WHERE fact_vent.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT nota_cred.fechaNotaCredito
				FROM cj_cc_notacredito nota_cred
				WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito)
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT vale_sal.fecha_vale
				FROM sa_vale_salida vale_sal
				WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida)
		END) AS fecha_documento,
		
		comision_emp.monto_comision,
			
		(SELECT AVG(comision_emp_det.porcentaje_comision) FROM pg_comision_empleado_detalle comision_emp_det
		WHERE comision_emp_det.id_comision_empleado = comision_emp.id_comision_empleado) AS promedio_porcentaje_comision,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				MONTH((SELECT fact_vent.fechaRegistroFactura
				FROM cj_cc_encabezadofactura fact_vent
				WHERE fact_vent.idFactura = comision_emp.id_factura))
			WHEN (id_nota_credito IS NOT NULL) THEN
				MONTH((SELECT nota_cred.fechaNotaCredito
				FROM cj_cc_notacredito nota_cred
				WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito))
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				MONTH((SELECT vale_sal.fecha_vale
				FROM sa_vale_salida vale_sal
				WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida))
		END) AS mes_documento,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				YEAR((SELECT fact_vent.fechaRegistroFactura
				FROM cj_cc_encabezadofactura fact_vent
				WHERE fact_vent.idFactura = comision_emp.id_factura))
			WHEN (id_nota_credito IS NOT NULL) THEN
				YEAR((SELECT nota_cred.fechaNotaCredito
				FROM cj_cc_notacredito nota_cred
				WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito))
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				YEAR((SELECT vale_sal.fecha_vale
				FROM sa_vale_salida vale_sal
				WHERE vale_sal.id_vale_salida = comision_emp.id_vale_salida))
		END) AS ano_documento,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(to_days((SELECT fact_vent.fechaRegistroFactura
						FROM cj_cc_encabezadofactura fact_vent
						WHERE fact_vent.idFactura = comision_emp.id_factura)) - to_days((SELECT uni_fis.fecha_ingreso
				FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (fact_vent_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE fact_vent_det_vehic.id_factura = comision_emp.id_factura)))
			WHEN (id_nota_credito IS NOT NULL) THEN
				(to_days((SELECT nota_cred.fechaNotaCredito
						FROM cj_cc_notacredito nota_cred
						WHERE nota_cred.idNotaCredito = comision_emp.id_nota_credito)) - to_days((SELECT uni_fis.fecha_ingreso
				FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (fact_vent_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE fact_vent_det_vehic.id_factura = comision_emp.id_factura)))
		END) AS dias_inventario
	
	FROM pg_comision_empleado comision_emp %s
	ORDER BY 2,3,4", $sqlBusq2);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br><b>Line: ".__LINE__);
	$contFila = 0;
	$arrayTotalRep = NULL;
	$arrayTotalServ = NULL;
	$arrayTotalVehic = NULL;
	$arrayCol = NULL;
	while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		$contFila++;
		
		if ($rowDetalle['tipo_documento'] == "FA") {
			$indice = 0;
			$signo = 1;
		} else if ($rowDetalle['tipo_documento'] == "NC") {
			$indice = 1;
			$signo = (-1);
		} else if ($rowDetalle['tipo_documento'] == "VS") {
			$indice = 2;
			$signo = 1;
		}
		
		if ($rowDetalle['id_modulo'] == 0) {
			$imgModulo = "R";
			
			$arrayTotalRep[$indice][7] ++;
			$arrayTotalRep[$indice][0] += $signo * $rowDetalle['venta_bruta'];
			$arrayTotalRep[$indice][1] += $signo * $rowDetalle['monto_descuento'];
			$arrayTotalRep[$indice][2] += $signo * $rowDetalle['venta_neta'];
			$arrayTotalRep[$indice][3] += $signo * $rowDetalle['costo_compra'];
			$arrayTotalRep[$indice][4] += $signo * $rowDetalle['utilidad_bruta'];
			$arrayTotalRep[$indice][5] = ($arrayTotalRep[$indice][7] > 0) ? ($arrayTotalRep[$indice][4] * 100) / $arrayTotalRep[$indice][2] : 0; // PORCENTAJE UTILIDAD VENTA
			$arrayTotalRep[$indice][6] += $signo * $rowDetalle['monto_comision'];
		} else if ($rowDetalle['id_modulo'] == 1) {
			$imgModulo = "S";
			
			$arrayTotalServ[$indice][7] ++;
			$arrayTotalServ[$indice][0] += $signo * $rowDetalle['venta_bruta'];
			$arrayTotalServ[$indice][1] += $signo * $rowDetalle['monto_descuento'];
			$arrayTotalServ[$indice][2] += $signo * $rowDetalle['venta_neta'];
			$arrayTotalServ[$indice][3] += $signo * $rowDetalle['costo_compra'];
			$arrayTotalServ[$indice][4] += $signo * $rowDetalle['utilidad_bruta'];
			$arrayTotalServ[$indice][5] = ($arrayTotalServ[$indice][7] > 0) ? ($arrayTotalServ[$indice][4] * 100) / $arrayTotalServ[$indice][2] : 0; // PORCENTAJE UTILIDAD VENTA
			$arrayTotalServ[$indice][6] += $signo * $rowDetalle['monto_comision'];
		} else if ($rowDetalle['id_modulo'] == 2) {
			$imgModulo = "V";
			
			$arrayTotalVehic[$indice][7] ++;
			$arrayTotalVehic[$indice][0] += $signo * $rowDetalle['venta_bruta'];
			$arrayTotalVehic[$indice][1] += $signo * $rowDetalle['monto_descuento'];
			$arrayTotalVehic[$indice][2] += $signo * $rowDetalle['venta_neta'];
			$arrayTotalVehic[$indice][3] += $signo * $rowDetalle['costo_compra'];
			$arrayTotalVehic[$indice][4] += $signo * $rowDetalle['utilidad_bruta'];
			$arrayTotalVehic[$indice][5] = ($arrayTotalVehic[$indice][7] > 0) ? ($arrayTotalVehic[$indice][4] * 100) / $arrayTotalVehic[$indice][2] : 0; // PORCENTAJE UTILIDAD VENTA
			$arrayTotalVehic[$indice][6] += $signo * $rowDetalle['monto_comision'];
		}
		
		$arrayCol[$contFila][0] = $imgModulo;
		$arrayCol[$contFila][1] = $rowDetalle['tipo_documento'];
		$arrayCol[$contFila][2] = $rowDetalle['numero_documento'];
		switch ($rowDetalle['tipo_pago']) {
			case "0" :	$arrayCol[$contFila][3] = "CRÉDITO"; break;
			case "1" :	$arrayCol[$contFila][3] = "CONTADO"; break;
			default :	$arrayCol[$contFila][3] = $rowDetalle['tipo_pago']; break;
		}
		$arrayCol[$contFila][4] = $rowDetalle['ci_cliente'];
		$arrayCol[$contFila][5] = utf8_encode($rowDetalle['nombre_cliente']);
		$arrayCol[$contFila][6] = round($signo * $rowDetalle['venta_bruta'],2);
		$arrayCol[$contFila][7] = round($signo * $rowDetalle['monto_descuento'],2);
		$arrayCol[$contFila][8] = round($signo * $rowDetalle['venta_neta'],2);
		$arrayCol[$contFila][9] = round($signo * $rowDetalle['costo_compra'],2);
		$arrayCol[$contFila][10] = round($signo * $rowDetalle['utilidad_bruta'],2);
		$arrayCol[$contFila][11] = round($signo * $rowDetalle['porcentaje_utilidad_venta'],2);
		$arrayCol[$contFila][12] = date("d-m-Y",strtotime($rowDetalle['fecha_documento']));
		$arrayCol[$contFila][13] = ($rowDetalle['dias_inventario'] != "") ? round($rowDetalle['dias_inventario'],2) : "-";
		$arrayCol[$contFila][14] = round($signo * $rowDetalle['promedio_porcentaje_comision'],3);
		$arrayCol[$contFila][15] = round($signo * $rowDetalle['monto_comision'],2);
	}
	
	// TOTAL FACTURAS
	$arrayTotal[0][7] = $arrayTotalRep[0][7] + $arrayTotalServ[0][7] + $arrayTotalVehic[0][7];
	$arrayTotal[0][0] = $arrayTotalRep[0][0] + $arrayTotalServ[0][0] + $arrayTotalVehic[0][0];
	$arrayTotal[0][1] = $arrayTotalRep[0][1] + $arrayTotalServ[0][1] + $arrayTotalVehic[0][1];
	$arrayTotal[0][2] = $arrayTotalRep[0][2] + $arrayTotalServ[0][2] + $arrayTotalVehic[0][2];
	$arrayTotal[0][3] = $arrayTotalRep[0][3] + $arrayTotalServ[0][3] + $arrayTotalVehic[0][3];
	$arrayTotal[0][4] = $arrayTotalRep[0][4] + $arrayTotalServ[0][4] + $arrayTotalVehic[0][4];
	$arrayTotal[0][5] = ($arrayTotal[0][7] > 0) ? ($arrayTotal[0][4] * 100) / $arrayTotal[0][2] : 0; // PORCENTAJE UTILIDAD VENTA
	$arrayTotal[0][6] = $arrayTotalRep[0][6] + $arrayTotalServ[0][6] + $arrayTotalVehic[0][6];
	
	// TOTAL NOTAS CREDITO
	$arrayTotal[1][7] = $arrayTotalRep[1][7] + $arrayTotalServ[1][7] + $arrayTotalVehic[1][7];
	$arrayTotal[1][0] = $arrayTotalRep[1][0] + $arrayTotalServ[1][0] + $arrayTotalVehic[1][0];
	$arrayTotal[1][1] = $arrayTotalRep[1][1] + $arrayTotalServ[1][1] + $arrayTotalVehic[1][1];
	$arrayTotal[1][2] = $arrayTotalRep[1][2] + $arrayTotalServ[1][2] + $arrayTotalVehic[1][2];
	$arrayTotal[1][3] = $arrayTotalRep[1][3] + $arrayTotalServ[1][3] + $arrayTotalVehic[1][3];
	$arrayTotal[1][4] = $arrayTotalRep[1][4] + $arrayTotalServ[1][4] + $arrayTotalVehic[1][4];
	$arrayTotal[1][5] = ($arrayTotal[1][7] > 0) ? ($arrayTotal[1][4] * 100) / $arrayTotal[1][2] : 0; // PORCENTAJE UTILIDAD VENTA
	$arrayTotal[1][6] = $arrayTotalRep[1][6] + $arrayTotalServ[1][6] + $arrayTotalVehic[1][6];
	
	// TOTAL VALES SALIDA
	$arrayTotal[2][7] = $arrayTotalServ[2][7];
	$arrayTotal[2][0] = $arrayTotalServ[2][0];
	$arrayTotal[2][1] = $arrayTotalServ[2][1];
	$arrayTotal[2][2] = $arrayTotalServ[2][2];
	$arrayTotal[2][3] = $arrayTotalServ[2][3];
	$arrayTotal[2][4] = $arrayTotalServ[2][4];
	$arrayTotal[2][5] = ($arrayTotal[2][7] > 0) ? ($arrayTotal[2][4] * 100) / $arrayTotal[2][2] : 0; // PORCENTAJE UTILIDAD VENTA
	$arrayTotal[2][6] = $arrayTotalServ[2][6];
	
	// TOTAL COMISION
	$arrayTotalComision[7] = $arrayTotal[0][7] + $arrayTotal[1][7] + $arrayTotal[2][7];
	$arrayTotalComision[0] = $arrayTotal[0][0] + $arrayTotal[1][0] + $arrayTotal[2][0];
	$arrayTotalComision[1] = $arrayTotal[0][1] + $arrayTotal[1][1] + $arrayTotal[2][1];
	$arrayTotalComision[2] = $arrayTotal[0][2] + $arrayTotal[1][2] + $arrayTotal[2][2];
	$arrayTotalComision[3] = $arrayTotal[0][3] + $arrayTotal[1][3] + $arrayTotal[2][3];
	$arrayTotalComision[4] = $arrayTotal[0][4] + $arrayTotal[1][4] + $arrayTotal[2][4];
	$arrayTotalComision[5] = ($arrayTotalComision[7] > 0) ? ($arrayTotalComision[4] * 100) / $arrayTotalComision[2] : 0; // PORCENTAJE UTILIDAD VENTA
	$arrayTotalComision[6] = $arrayTotal[0][6] + $arrayTotal[1][6] + $arrayTotal[2][6];
	
	$arrayVendedor[$cont][0] = $rowComision['cedula'];
	$arrayVendedor[$cont][1] = $rowComision['nombre_empleado'];
	$arrayVendedor[$cont][2] = $mes[$rowComision['mes_documento']]." ".$rowComision['ano_documento'];
	$arrayVendedor[$cont][3] = $arrayCol;
	$arrayVendedor[$cont][4] = $arrayTotalRep;
	$arrayVendedor[$cont][5] = $arrayTotalServ;
	$arrayVendedor[$cont][6] = $arrayTotalVehic;
	$arrayVendedor[$cont][7] = $arrayTotal;
	$arrayVendedor[$cont][8] = $arrayTotalComision;
	
	$cont++;
}


if (isset($arrayVendedor)) {
	foreach ($arrayVendedor as $indice => $valor) {
		$excel->add_row(array(
			"*Empleado:|2*",
			$valor[0],
			$valor[1]."|1",
			" |6",
			"*Mes / Año:*",
			$valor[2]."|1"
		));
		
		$excel->add_row(array(" |5", "Venta Bruta", "Descuento", "Venta Neta", "Costo", "Utl. Bruta", "%Utl. Bruta", "", "", "", "Comisión"), 'header');
		
		if ($valor[4][0][7] > 0 || $valor[5][0][7] > 0 || $valor[6][0][7] > 0) {
			if ($valor[4][0][7] > 0) {
				$excel->add_row(array(" |3", "R",
					$valor[4][0][7],
					round($valor[4][0][0],2),
					round($valor[4][0][1],2),
					round($valor[4][0][2],2),
					round($valor[4][0][3],2),
					round($valor[4][0][4],2),
					round($valor[4][0][5],2),
					"", "", "",
					round($valor[4][0][6],2)), 'trResaltarTotal2');
			}
			if ($valor[5][0][7] > 0) {
				$excel->add_row(array(" |3", "S",
					$valor[5][0][7],
					round($valor[5][0][0],2),
					round($valor[5][0][1],2),
					round($valor[5][0][2],2),
					round($valor[5][0][3],2),
					round($valor[5][0][4],2),
					round($valor[5][0][5],2),
					"", "", "",
					round($valor[5][0][6],2)), 'trResaltarTotal2');
			}
			if ($valor[6][0][7] > 0) {
				$excel->add_row(array(" |3", "V",
					$valor[6][0][7],
					round($valor[6][0][0],2),
					round($valor[6][0][1],2),
					round($valor[6][0][2],2),
					round($valor[6][0][3],2),
					round($valor[6][0][4],2),
					round($valor[6][0][5],2),
					"", "", "",
					round($valor[6][0][6],2)), 'trResaltarTotal2');
			}
			$excel->add_row(array("Total Facturas: |3", "",
				$valor[7][0][7],
				round($valor[7][0][0],2),
				round($valor[7][0][1],2),
				round($valor[7][0][2],2),
				round($valor[7][0][3],2),
				round($valor[7][0][4],2),
				round($valor[7][0][5],2),
				"", "", "",
				round($valor[7][0][6],2)), 'trResaltarTotal');
		}
		
		if ($valor[4][1][7] > 0 || $valor[5][1][7] > 0 || $valor[6][1][7] > 0) {
			if ($valor[4][1][7] > 0) {
				$excel->add_row(array(" |3", "R",
					$valor[4][1][7],
					round($valor[4][1][0],2),
					round($valor[4][1][1],2),
					round($valor[4][1][2],2),
					round($valor[4][1][3],2),
					round($valor[4][1][4],2),
					round($valor[4][1][5],2),
					"", "", "",
					round($valor[4][1][6],2)), 'trResaltarTotal2');
			}
			if ($valor[5][1][7] > 0) {
				$excel->add_row(array(" |3", "S",
					$valor[5][1][7],
					round($valor[5][1][0],2),
					round($valor[5][1][1],2),
					round($valor[5][1][2],2),
					round($valor[5][1][3],2),
					round($valor[5][1][4],2),
					round($valor[5][1][5],2),
					"", "", "",
					round($valor[5][1][6],2)), 'trResaltarTotal2');
			}
			if ($valor[6][1][7] > 0) {
				$excel->add_row(array(" |3", "V",
					$valor[6][1][7],
					round($valor[6][1][0],2),
					round($valor[6][1][1],2),
					round($valor[6][1][2],2),
					round($valor[6][1][3],2),
					round($valor[6][1][4],2),
					round($valor[6][1][5],2),
					"", "", "",
					round($valor[6][1][6],2)), 'trResaltarTotal2');
			}
			$excel->add_row(array("Total Notas Créd.: |3", "",
				$valor[7][1][7],
				round($valor[7][1][0],2),
				round($valor[7][1][1],2),
				round($valor[7][1][2],2),
				round($valor[7][1][3],2),
				round($valor[7][1][4],2),
				round($valor[7][1][5],2),
				"", "", "",
				round($valor[7][1][6],2)), 'trResaltarTotal');
		}
		
		if ($valor[5][2][7]) {
			if ($valor[5][2][7]) {
				$excel->add_row(array(" |3", "S",
				$valor[5][2][7],
				round($valor[5][2][0],2),
				round($valor[5][2][1],2),
				round($valor[5][2][2],2),
				round($valor[5][2][3],2),
				round($valor[5][2][4],2),
				round($valor[5][2][5],2),
				"", "", "",
				round($valor[5][2][6],2)), 'trResaltarTotal2');
			}
			$excel->add_row(array("Total Vales Salida: |3", "",
				$valor[7][2][7],
				round($valor[7][2][0],2),
				round($valor[7][2][1],2),
				round($valor[7][2][2],2),
				round($valor[7][2][3],2),
				round($valor[7][2][4],2),
				round($valor[7][2][5],2),
				"", "", "",
				round($valor[7][2][6],2)), 'trResaltarTotal');
		}
		
		$excel->add_row(array("Total: |3", "", "",
			$valor[8][0],
			round($valor[8][1],2),
			round($valor[8][2],2),
			round($valor[8][3],2),
			round($valor[8][4],2),
			round($valor[8][5],2),
			"", "", "",
			round($valor[8][6],2)), 'trResaltarTotal3');
		
		$excel->add_row(" |15");
		
		$excel->add_row(array(
			'Folio Factura|2',
			'Tipo Pago',
			$spanClienteCxC,
			'Cliente',
			'Venta Bruta',
			'Descuento',
			'Venta Neta',
			'Costo',
			'Utl. Bruta',
			'%Utl. Bruta',
			'Fecha Dcto.',
			"Dias de Inv.",
			"% Comisión",
			'Comisión'
		), 'header');
		
		
		if (isset($valor[3])) {
			$contFila = 0;
			foreach ($valor[3] as $indice2 => $valor2) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$excel->add_row(array(
					$valor2[0],
					$valor2[1],
					$valor2[2],
					$valor2[3],
					$valor2[4],
					$valor2[5],
					$valor2[6],
					$valor2[7],
					$valor2[8],
					$valor2[9],
					$valor2[10],
					$valor2[11],
					$valor2[12],
					$valor2[13],
					$valor2[14],
					$valor2[15]
				), $clase);
			}
		}
		
		$excel->create_worksheet($valor[1]);	
	}
}

$xml = $excel->generate();

$excel->download('ERP_Comisiones.xls');
?>