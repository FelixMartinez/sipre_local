<?php
// QUITAR EL COMENTARIO SI SE VA A UTILIZAR ESTE ARCHIVO PARA LA EJECUCION EN MASA
require_once("../../connections/conex.php");
$idModulo = 1;
$fechaDesde = "01-09-2015";
$fechaHasta = "30-09-2015";

mysql_query("START TRANSACTION;");

/*
DELETE FROM pg_comision_empleado
WHERE id_factura IN (SELECT idFactura FROM cj_cc_encabezadofactura
					WHERE idDepartamentoOrigenFactura IN (1)
						AND MONTH(fechaRegistroFactura) = '04'
						AND YEAR(fechaRegistroFactura) =  '2015')
	AND id_nota_credito IS NULL;*/

# ELIMINA LAS FACTURAS
$deleteSQL = sprintf("DELETE FROM pg_comision_empleado
WHERE id_factura IN (SELECT idFactura FROM cj_cc_encabezadofactura
					WHERE idDepartamentoOrigenFactura IN (%s)
						AND fechaRegistroFactura BETWEEN %s AND %s)
	AND id_nota_credito IS NULL;",
	valTpDato($idModulo, "int"),
	valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
	valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
$Result1 = mysql_query($deleteSQL, $conex);
if (!$Result1) die(mysql_error()."<br><br>Line: ".__LINE__);

# ELIMINA LAS NOTA DE CREDITO 
$deleteSQL = sprintf("DELETE FROM pg_comision_empleado
WHERE id_nota_credito IN (SELECT idNotaCredito FROM cj_cc_notacredito
						WHERE idDepartamentoNotaCredito IN (%s)
							AND fechaNotaCredito BETWEEN %s AND %s);",
	valTpDato($idModulo, "int"),
	valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
	valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
$Result1 = mysql_query($deleteSQL, $conex);
if (!$Result1) die(mysql_error()."<br><br>Line: ".__LINE__);

if ($idModulo == 0) {
	require_once("../../controladores/ac_pg_calcular_comision.php");
	
	# GENERA COMISIONES FACTURAS
	$query = sprintf("SELECT * FROM cj_cc_encabezadofactura
	WHERE idDepartamentoOrigenFactura IN (%s)
		AND fechaRegistroFactura BETWEEN %s AND %s
		AND (SELECT COUNT(fact_vent_det.id_factura) FROM cj_cc_factura_detalle fact_vent_det
			WHERE fact_vent_det.id_factura = cj_cc_encabezadofactura.idFactura) > 0;",
		valTpDato($idModulo, "int"),
		valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
		valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
	$rs = mysql_query($query, $conex);
	if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = generarComision($row['idFactura']);
		if ($Result1[0] != true) die($Result1[1]);
	}
	
	# GENERA COMISIONES NOTAS DE CREDITO
	$query = sprintf("SELECT * FROM cj_cc_notacredito
	WHERE idDepartamentoNotaCredito IN (%s)
		AND fechaNotaCredito BETWEEN %s AND %s
		AND (SELECT COUNT(nota_cred_det.id_nota_credito) FROM cj_cc_nota_credito_detalle nota_cred_det
			WHERE nota_cred_det.id_nota_credito = cj_cc_notacredito.idNotaCredito) > 0
		AND estatus_nota_credito IN (2);",
		valTpDato($idModulo, "int"),
		valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
		valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
	$rs = mysql_query($query, $conex);
	if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = devolverComision($row['idNotaCredito']);
		if ($Result1[0] != true) die($Result1[1]);
	}
} else if ($idModulo == 1) {
	require_once("../../controladores/ac_pg_calcular_comision_servicio.php");
	
	# ELIMINA LOS VALES DE SALIDA
	$deleteSQL = sprintf("DELETE FROM pg_comision_empleado
	WHERE id_vale_salida IN (SELECT id_vale_salida FROM sa_vale_salida
							WHERE DATE(fecha_vale) BETWEEN %s AND %s);",
		valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
		valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
	$Result1 = mysql_query($deleteSQL, $conex);
	if (!$Result1) die(mysql_error()."<br><br>Line: ".__LINE__);
	
	
	# GENERA COMISIONES FACTURAS
	$query = sprintf("SELECT * FROM cj_cc_encabezadofactura
	WHERE idDepartamentoOrigenFactura IN (%s)
		AND fechaRegistroFactura BETWEEN %s AND %s
		AND numeroPedido > 0;",
		valTpDato($idModulo, "int"),
		valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
		valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
	$rs = mysql_query($query, $conex);
	if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = calcular_comision_factura($row['idFactura']);
		if ($Result1[0] != true) die($Result1[1]);
	}
	
	# GENERA COMISIONES VALE SALIDA
	$query = sprintf("SELECT * FROM sa_vale_salida
	WHERE DATE(fecha_vale) BETWEEN %s AND %s;",
		valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
		valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
	$rs = mysql_query($query, $conex);
	if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = calcular_comision_vale_salida($row['id_vale_salida']);
		if ($Result1[0] != true) die($Result1[1]);
	}
	
	# GENERA COMISIONES NOTAS DE CREDITO
	$query = sprintf("SELECT * FROM cj_cc_notacredito
	WHERE idDepartamentoNotaCredito IN (%s)
		AND fechaNotaCredito BETWEEN %s AND %s
		AND idDocumento > 0;",
		valTpDato($idModulo, "int"),
		valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
		valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
	$rs = mysql_query($query, $conex);
	if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = devolverComisionNC($row['idNotaCredito'], $row['idDocumento']);
		if ($Result1[0] != true) die($Result1[1]);
	}
} else if ($idModulo == 2) {
	require_once("../../controladores/ac_pg_calcular_comision.php");
	
	$query = sprintf("SELECT * FROM cj_cc_encabezadofactura
	WHERE idDepartamentoOrigenFactura IN (%s)
		AND fechaRegistroFactura BETWEEN %s AND %s;",
		valTpDato($idModulo, "int"),
		valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
		valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
	$rs = mysql_query($query, $conex);
	if (!$rs) return mysql_error()."<br><br>Line: ".__LINE__;
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = generarComision($row['idFactura']);
		if ($Result1[0] != true) die($Result1[1]);
	}
	
	$query = sprintf("SELECT * FROM cj_cc_notacredito
	WHERE idDepartamentoNotaCredito IN (%s)
		AND fechaNotaCredito BETWEEN %s AND %s
		AND idDocumento > 0;",
		valTpDato($idModulo, "int"),
		valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
		valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
	$rs = mysql_query($query, $conex);
	if (!$rs) return mysql_error()."<br><br>Line: ".__LINE__;
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = devolverComision($row['idNotaCredito']);
		if ($Result1[0] != true) die($Result1[1]);
	}
}

mysql_query("COMMIT;");

echo "<h1>COMISIONES GENERADAS CON EXITO</h1>";
?>