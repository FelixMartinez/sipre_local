<?php
require_once("../../connections/conex.php");
include("../../controladores/ac_if_generar_cierre_mensual.php");
set_time_limit(0);
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
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
$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];
$valFecha = explode("-", $valCadBusq[1]);

mysql_query("SET GLOBAL innodb_stats_on_metadata = 0;");
	
$sqlBusq = " ";
if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("config_emp.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
} else {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("config_emp.id_empresa = (SELECT emp.id_empresa
														FROM pg_empresa emp
															LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa)
														ORDER BY emp.id_empresa_padre ASC
														LIMIT 1)");
}

// VERIFICA VALORES DE CONFIGURACION (Incluir Notas en el Informe Gerencial)
$queryConfig300 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 300 AND config_emp.status = 1 %s;", $sqlBusq);
$rsConfig300 = mysql_query($queryConfig300);
if (!$rsConfig300) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig300 = mysql_num_rows($rsConfig300);
$rowConfig300 = mysql_fetch_assoc($rsConfig300);

// VERIFICA VALORES DE CONFIGURACION (Tipos de Orden a Mostrar en el Informe Gerencial)
$queryConfig301 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 301 AND config_emp.status = 1 %s;", $sqlBusq);
$rsConfig301 = mysql_query($queryConfig301);
if (!$rsConfig301) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig301 = mysql_num_rows($rsConfig301);
$rowConfig301 = mysql_fetch_assoc($rsConfig301);

// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Producción Otros))
$queryConfig304 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 304 AND config_emp.status = 1 %s;", $sqlBusq);
$rsConfig304 = mysql_query($queryConfig304);
if (!$rsConfig304) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig304 = mysql_num_rows($rsConfig304);
$rowConfig304 = mysql_fetch_assoc($rsConfig304);

// DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

// BUSCA LOS DATOS DEL CIERRE MENSUAL
$query = sprintf("SELECT cierre_mensual.*,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
FROM iv_cierre_mensual cierre_mensual
	INNER JOIN pg_empleado empleado ON (cierre_mensual.id_empleado_creador = empleado.id_empleado)
WHERE (cierre_mensual.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cierre_mensual.id_empresa))
	AND cierre_mensual.mes = %s
	AND cierre_mensual.ano = %s;",
	valTpDato($idEmpresa, "int"),
	valTpDato($idEmpresa, "int"),
	valTpDato($valFecha[0], "int"),
	valTpDato($valFecha[1], "int"));
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while ($row = mysql_fetch_assoc($rs)) {
	$idCierreMensual[] = $row['id_cierre_mensual'];
	
	$htmlMsj = "(".utf8_encode("Cierre generado el ".date("d-m-Y", strtotime($row['fecha_creacion']))." a las ".date("h:ia", strtotime($row['fecha_creacion']))." por ".$row['nombre_empleado']).")";
}
$idCierreMensual = (isset($idCierreMensual)) ? implode(",",$idCierreMensual) : "-1";


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION TALLER
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("tipo_orden.orden_generica = 0");

if (strlen($rowConfig301['valor']) > 0) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("filtro_orden.id_filtro_orden IN (%s)",
		valTpDato($rowConfig301['valor'], "campo"));
}

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(tipo_orden.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = tipo_orden.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

// TIPOS DE ORDENES
$sqlTiposOrden = sprintf("SELECT filtro_orden.*
FROM sa_tipo_orden tipo_orden
	INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s
GROUP BY filtro_orden.id_filtro_orden", $sqlBusq);
$rsTiposOrden = mysql_query($sqlTiposOrden);
if (!$rsTiposOrden) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayProdTipoOrden = array();
while ($rowTipoOrden = mysql_fetch_assoc($rsTiposOrden)) {
	$arrayProdTipoOrden[$rowTipoOrden['id_filtro_orden']] = array('nombre' => $rowTipoOrden['descripcion']);
}

// TIPOS DE MANO DE OBRA
$sqlOperadores = "SELECT * FROM sa_operadores ORDER BY id_operador";
$rsOperadores = mysql_query($sqlOperadores);
if (!$rsOperadores) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayOperador = array();
while ($rowOperadores = mysql_fetch_assoc($rsOperadores)) {
	$arrayOperador[$rowOperadores['id_operador']] = $rowOperadores['descripcion_operador'];
}
$idTot = count($arrayOperador) + 1;
$arrayOperador[$idTot] = "Trabajos Otros Talleres";

// TIPOS DE ARTICULOS
$sqlTiposArticulos = "SELECT * FROM iv_tipos_articulos ORDER BY id_tipo_articulo";
$rsTiposArticulos = mysql_query($sqlTiposArticulos);
if (!$rsTiposArticulos) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayTipoArticulo = array();
while ($rowTipoArticulo = mysql_fetch_assoc($rsTiposArticulos)) {
	$arrayTipoArticulo[$rowTipoArticulo['id_tipo_articulo']] = $rowTipoArticulo['descripcion'];
}

$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("a.aprobado = 1");

if (strlen($rowConfig301['valor']) > 0) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
		valTpDato($rowConfig301['valor'], "campo"));
}

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = a.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
	AND YEAR(a.fecha_filtro) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// SOLO APLICA PARA LAS MANO DE OBRA
$sqlBusq2 = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("a.estado_tempario IN ('FACTURADO','TERMINADO')");

// MANO DE OBRAS FACTURAS DE SERVICIOS
$queryMOFact = sprintf("SELECT
	a.id_filtro_orden,
	a.id_tipo_orden,
	a.operador,
	
	(CASE a.id_modo
		WHEN 1 THEN -- UT
			(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
		WHEN 2 THEN -- PRECIO
			a.precio
	END) AS total_tempario_orden
	
FROM sa_v_informe_final_tempario a %s %s;", $sqlBusq, $sqlBusq2);
$rsMOFact = mysql_query($queryMOFact);
if (!$rsMOFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayMO = array();
$arrayTotalMOTipoOrden = array();
$arrayTotalMOOperador = array();
while ($rowMOFact = mysql_fetch_assoc($rsMOFact)) {
	$valor = $rowMOFact['total_tempario_orden'];
	
	$arrayMO[$rowMOFact['operador']][$rowMOFact['id_filtro_orden']] += $valor;

	$arrayTotalMOTipoOrden[$rowMOFact['id_filtro_orden']] += $valor;
	$arrayTotalMOOperador[$rowMOFact['operador']] += $valor;
}

// MANO DE OBRAS NOTAS DE CREDITO DE SERVICIOS
$queryMONotaCred = sprintf("SELECT
	a.id_filtro_orden,
	a.id_tipo_orden,
	a.operador,
	
	(CASE a.id_modo
		WHEN 1 THEN -- UT
			(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
		WHEN 2 THEN -- PRECIO
			a.precio
	END) AS total_tempario_dev_orden
	
FROM sa_v_informe_final_tempario_dev a %s %s;", $sqlBusq, $sqlBusq2);
$rsMONotaCred = mysql_query($queryMONotaCred);
if (!$rsMONotaCred) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowMONotaCred = mysql_fetch_assoc($rsMONotaCred)) {
	$valor = $rowMONotaCred['total_tempario_dev_orden'];
	
	$arrayMO[$rowMONotaCred['operador']][$rowMONotaCred['id_filtro_orden']] -= $valor;

	$arrayTotalMOTipoOrden[$rowMONotaCred['id_filtro_orden']] -= $valor;
	$arrayTotalMOOperador[$rowMONotaCred['operador']] -= $valor;
}

// MANO DE OBRAS VALE DE SALIDA DE SERVICIOS
$queryMOValeSal = sprintf("SELECT
	a.id_filtro_orden,
	a.id_tipo_orden,
	a.operador,
	
	(CASE a.id_modo
		WHEN 1 THEN -- UT
			(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
		WHEN 2 THEN -- PRECIO
			a.precio
	END) AS total_tempario_vale
	
FROM sa_v_vale_informe_final_tempario a %s %s;", $sqlBusq, $sqlBusq2);
$rsMOValeSal = mysql_query($queryMOValeSal);
if (!$rsMOValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowMOValeSal = mysql_fetch_assoc($rsMOValeSal)) {
	$valor = $rowMOValeSal['total_tempario_vale'];
	
	$arrayMO[$rowMOValeSal['operador']][$rowMOValeSal['id_filtro_orden']] += $valor;

	$arrayTotalMOTipoOrden[$rowMOValeSal['id_filtro_orden']] += $valor;
	$arrayTotalMOOperador[$rowMOValeSal['operador']] += $valor;
}

// MANO DE OBRAS VALE DE ENTRADA DE SERVICIOS
$queryMOValeEnt = sprintf("SELECT
	a.id_filtro_orden,
	a.id_tipo_orden,
	a.operador,
	
	(CASE a.id_modo
		WHEN 1 THEN -- UT
			(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
		WHEN 2 THEN -- PRECIO
			a.precio
	END) AS total_tempario_vale
	
FROM sa_v_vale_informe_final_tempario_dev a %s %s;", $sqlBusq, $sqlBusq2);
$rsMOValeEnt = mysql_query($queryMOValeEnt);
if (!$rsMOValeEnt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowMOValeEnt = mysql_fetch_assoc($rsMOValeEnt)) {
	$valor = $rowMOValeEnt['total_tempario_vale'];
	
	$arrayMO[$rowMOValeEnt['operador']][$rowMOValeEnt['id_filtro_orden']] -= $valor;

	$arrayTotalMOTipoOrden[$rowMOValeEnt['id_filtro_orden']] -= $valor;
	$arrayTotalMOOperador[$rowMOValeEnt['operador']] -= $valor;
}


// TOT FACTURAS DE SERVICIOS
$queryTotFact = sprintf("SELECT * FROM sa_v_informe_final_tot a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotFact = mysql_query($queryTotFact);
if (!$rsTotFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowTotFact = mysql_fetch_assoc($rsTotFact)) {
	$valor = $rowTotFact['monto_total'] + (($rowTotFact['porcentaje_tot'] * $rowTotFact['monto_total']) / 100);

	$arrayMO[$idTot][$rowTotFact['id_filtro_orden']] += $valor;

	$arrayTotalMOTipoOrden[$rowTotFact['id_filtro_orden']] += $valor;
	$arrayTotalMOOperador[$idTot] += $valor;
}

// TOT NOTAS DE CREDITO DE SERVICIOS
$queryTotNotaCred = sprintf("SELECT * FROM sa_v_informe_final_tot_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotNotaCred = mysql_query($queryTotNotaCred);
if (!$rsTotNotaCred) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowTotNotaCred = mysql_fetch_assoc($rsTotNotaCred)) {
	$valor = $rowTotNotaCred['monto_total'] + (($rowTotNotaCred['porcentaje_tot'] * $rowTotNotaCred['monto_total']) / 100);

	$arrayMO[$idTot][$rowTotNotaCred['id_filtro_orden']] -= $valor;

	$arrayTotalMOTipoOrden[$rowTotNotaCred['id_filtro_orden']] -= $valor;
	$arrayTotalMOOperador[$idTot] -= $valor;
}

// TOT VALE DE SALIDA DE SERVICIOS
$queryTotValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_tot a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotValeSal = mysql_query($queryTotValeSal);
if (!$rsTotValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowTotValeSal = mysql_fetch_assoc($rsTotValeSal)) {
	$valor = $rowTotValeSal['monto_total'] + (($rowTotValeSal['porcentaje_tot'] * $rowTotValeSal['monto_total']) / 100);

	$arrayMO[$idTot][$rowTotValeSal['id_filtro_orden']] += $valor;
	
	$arrayTotalMOTipoOrden[$rowTotValeSal['id_filtro_orden']] += $valor;
	$arrayTotalMOOperador[$idTot] += $valor;
}

// TOT VALE DE ENTRADA DE SERVICIOS
$queryTotValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_tot_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotValeEnt = mysql_query($queryTotValeEnt);
if (!$rsTotValeEnt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowTotValeEnt = mysql_fetch_assoc($rsTotValeEnt)) {
	$valor = $rowTotValeEnt['monto_total'] + (($rowTotValeEnt['porcentaje_tot'] * $rowTotValeEnt['monto_total']) / 100);

	$arrayMO[$idTot][$rowTotValeEnt['id_filtro_orden']] -= $valor;
	
	$arrayTotalMOTipoOrden[$rowTotValeEnt['id_filtro_orden']] -= $valor;
	$arrayTotalMOOperador[$idTot] -= $valor;
}


// REPUESTOS FACTURAS DE SERVICIOS
$queryRepFact = sprintf("SELECT * FROM sa_v_informe_final_repuesto a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepFact = mysql_query($queryRepFact);
if (!$rsRepFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayRepuesto = array();
$arrayTotalRepuestoTipo = array();
$arrayTotalRepuestoTipoOrden = array();
$arrayTotalDescuentoRepuestoTipoOrden = array();
while ($rowRepFact = mysql_fetch_assoc($rsRepFact)) {
	$valor = $rowRepFact['precio_unitario'] * $rowRepFact['cantidad'];

	$desc = (($valor * $rowRepFact['porcentaje_descuento_orden']) / 100);

	$arrayRepuesto[$rowRepFact['id_tipo_articulo']][$rowRepFact['id_filtro_orden']] += $valor;

	$arrayTotalRepuestoTipo[$rowRepFact['id_tipo_articulo']] += $valor;
	$arrayTotalRepuestoTipoOrden[$rowRepFact['id_filtro_orden']] += $valor;
	$arrayTotalDescuentoRepuestoTipoOrden[$rowRepFact['id_filtro_orden']] += $desc;
}

// REPUESTOS NOTAS DE CREDITO DE SERVICIOS
$queryRepNotaCred = sprintf("SELECT * FROM sa_v_informe_final_repuesto_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepNotaCred = mysql_query($queryRepNotaCred);
if (!$rsRepNotaCred) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowRepNotaCred = mysql_fetch_assoc($rsRepNotaCred)) {
	$valor = $rowRepNotaCred['precio_unitario'] * $rowRepNotaCred['cantidad'];

	$desc = (($valor * $rowRepNotaCred['porcentaje_descuento_orden']) / 100);

	$arrayRepuesto[$rowRepNotaCred['id_tipo_articulo']][$rowRepNotaCred['id_filtro_orden']] -= $valor;

	$arrayTotalRepuestoTipo[$rowRepNotaCred['id_tipo_articulo']] -= $valor;
	$arrayTotalRepuestoTipoOrden[$rowRepNotaCred['id_filtro_orden']] -= $valor;
	$arrayTotalDescuentoRepuestoTipoOrden[$rowRepNotaCred['id_filtro_orden']] -= $desc;
}

// REPUESTOS VALE DE SALIDA DE SERVICIOS
$queryRepValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepValeSal = mysql_query($queryRepValeSal);
if (!$rsRepValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowRepValeSal = mysql_fetch_assoc($rsRepValeSal)) {
	$valor = $rowRepValeSal['precio_unitario'] * $rowRepValeSal['cantidad'];

	$desc = (($valor * $rowRepValeSal['porcentaje_descuento_orden']) / 100);

	$arrayRepuesto[$rowRepValeSal['id_tipo_articulo']][$rowRepValeSal['id_filtro_orden']] += $valor;

	$arrayTotalRepuestoTipo[$rowRepValeSal['id_tipo_articulo']] += $valor;
	$arrayTotalRepuestoTipoOrden[$rowRepValeSal['id_filtro_orden']] += $valor;
	$arrayTotalDescuentoRepuestoTipoOrden[$rowRepValeSal['id_filtro_orden']] += $desc;
}

// REPUESTOS VALE DE ENTRADA DE SERVICIOS
$queryRepValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepValeEnt = mysql_query($queryRepValeEnt);
if (!$rsRepValeEnt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowRepValeEnt = mysql_fetch_assoc($rsRepValeEnt)) {
	$valor = $rowRepValeEnt['precio_unitario'] * $rowRepValeEnt['cantidad'];

	$desc = (($valor * $rowRepValeEnt['porcentaje_descuento_orden']) / 100);

	$arrayRepuesto[$rowRepValeEnt['id_tipo_articulo']][$rowRepValeEnt['id_filtro_orden']] -= $valor;

	$arrayTotalRepuestoTipo[$rowRepValeEnt['id_tipo_articulo']] -= $valor;
	$arrayTotalRepuestoTipoOrden[$rowRepValeEnt['id_filtro_orden']] -= $valor;
	$arrayTotalDescuentoRepuestoTipoOrden[$rowRepValeEnt['id_filtro_orden']] -= $desc;
}


// NOTAS FACTURAS DE SERVICIOS
$queryNotaFact = sprintf("SELECT * FROM sa_v_informe_final_notas a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotaFact = mysql_query($queryNotaFact);
if (!$rsNotaFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayTotalNotaTipoOrden = array();
$totalNota = 0;
while ($rowNotaFact = mysql_fetch_assoc($rsNotaFact)) {
	$arrayTotalNotaTipoOrden[$rowNotaFact['id_filtro_orden']] += $rowNotaFact['precio'];
	$totalNota += $rowNotaFact['precio'];
}

// NOTAS NOTAS DE CREDITO DE SERVICIOS
$queryNotaNotaCred = sprintf("SELECT * FROM sa_v_informe_final_notas_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotaNotaCred = mysql_query($queryNotaNotaCred);
if (!$rsNotaNotaCred) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowNotaNotaCred = mysql_fetch_assoc($rsNotaNotaCred)) {
	$arrayTotalNotaTipoOrden[$rowNotaNotaCred['id_filtro_orden']] -= $rowNotaNotaCred['precio'];
	$totalNota -= $rowNotaNotaCred['precio'];
}

// NOTAS VALE DE SALIDA DE SERVICIOS
$queryNotaValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_notas a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotaValeSal = mysql_query($queryNotaValeSal);
if (!$rsNotaValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowNotaValeSal = mysql_fetch_assoc($rsNotaValeSal)) {
	$arrayTotalNotaTipoOrden[$rowNotaValeSal['id_filtro_orden']] += $rowNotaValeSal['precio'];
	$totalNota += $rowNotaValeSal['precio'];
}

// NOTAS VALE DE ENTRADA DE SERVICIOS
$queryNotaValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_notas_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotaValeEnt = mysql_query($queryNotaValeEnt);
if (!$rsNotaValeEnt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowNotaValeEnt = mysql_fetch_assoc($rsNotaValeEnt)) {
	$arrayTotalNotaTipoOrden[$rowNotaValeEnt['id_filtro_orden']] -= $rowNotaValeEnt['precio'];
	$totalNota -= $rowNotaValeEnt['precio'];
}

// CALCULO DEL TOTAL
(count($arrayTotalMOTipoOrden) > 0) ? "" : $arrayTotalMOTipoOrden[0] = 0;
(count($arrayTotalRepuestoTipoOrden) > 0) ? "" : $arrayTotalRepuestoTipoOrden[0] = 0;
(count($arrayTotalNotaTipoOrden) > 0) ? "" : $arrayTotalNotaTipoOrden[0] = 0;
(count($arrayTotalDescuentoRepuestoTipoOrden) > 0) ? "" : $arrayTotalDescuentoRepuestoTipoOrden[0] = 0;

$totalProdTaller = array_sum($arrayTotalMOTipoOrden) + array_sum($arrayTotalRepuestoTipoOrden) - array_sum($arrayTotalDescuentoRepuestoTipoOrden);
$totalProdTaller += ($rowConfig300['valor'] == 1) ? array_sum($arrayTotalNotaTipoOrden) : 0;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION OTROS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("tipo_orden.orden_generica = 0");

if (strlen($rowConfig304['valor']) > 0) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("filtro_orden.id_filtro_orden IN (%s)",
		valTpDato($rowConfig304['valor'], "campo"));
}

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(tipo_orden.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = tipo_orden.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

// TIPOS DE ORDENES
$sqlTiposOrden = sprintf("SELECT filtro_orden.*
FROM sa_tipo_orden tipo_orden
	INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s
GROUP BY filtro_orden.id_filtro_orden", $sqlBusq);
$rsTiposOrden = mysql_query($sqlTiposOrden);
if (!$rsTiposOrden) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayOtroProdTipoOrden = array();
while ($rowTipoOrden = mysql_fetch_assoc($rsTiposOrden)) {
	$arrayOtroProdTipoOrden[$rowTipoOrden['id_filtro_orden']] = array('nombre' => $rowTipoOrden['descripcion']);
}

// TIPOS DE MANO DE OBRA
$sqlOperadores = "SELECT * FROM sa_operadores ORDER BY id_operador";
$rsOperadores = mysql_query($sqlOperadores);
if (!$rsOperadores) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayOtroOperador = array();
while ($rowOperadores = mysql_fetch_assoc($rsOperadores)) {
	$arrayOtroOperador[$rowOperadores['id_operador']] = $rowOperadores['descripcion_operador'];
}
$idTot = count($arrayOtroOperador) + 1;
$arrayOtroOperador[$idTot] = "Trabajos Otros Talleres";

// TIPOS DE ARTICULOS
$sqlTiposArticulos = "SELECT * FROM iv_tipos_articulos ORDER BY id_tipo_articulo";
$rsTiposArticulos = mysql_query($sqlTiposArticulos);
if (!$rsTiposArticulos) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayOtroTipoArticulo = array();
while ($rowTipoArticulo = mysql_fetch_assoc($rsTiposArticulos)) {
	$arrayOtroTipoArticulo[$rowTipoArticulo['id_tipo_articulo']] = $rowTipoArticulo['descripcion'];
}

$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("a.aprobado = 1");

if (strlen($rowConfig304['valor']) > 0) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
		valTpDato($rowConfig304['valor'], "campo"));
}

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = a.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
	AND YEAR(a.fecha_filtro) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// SOLO APLICA PARA LAS MANO DE OBRA
$sqlBusq2 = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("a.estado_tempario IN ('FACTURADO','TERMINADO')");

// MANO DE OBRAS FACTURAS DE SERVICIOS
$queryMOFact = sprintf("SELECT
	a.id_filtro_orden,
	a.id_tipo_orden,
	a.operador,
	
	(CASE a.id_modo
		WHEN 1 THEN -- UT
			(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
		WHEN 2 THEN -- PRECIO
			a.precio
	END) AS total_tempario_orden
	
FROM sa_v_informe_final_tempario a %s %s;", $sqlBusq, $sqlBusq2);
$rsMOFact = mysql_query($queryMOFact);
if (!$rsMOFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayOtroMO = array();
$arrayOtroTotalMOTipoOrden = array();
$arrayOtroTotalMOOperador = array();
while ($rowMOFact = mysql_fetch_assoc($rsMOFact)) {
	$valor = $rowMOFact['total_tempario_orden'];
	
	$arrayOtroMO[$rowMOFact['operador']][$rowMOFact['id_filtro_orden']] += $valor;

	$arrayOtroTotalMOTipoOrden[$rowMOFact['id_filtro_orden']] += $valor;
	$arrayOtroTotalMOOperador[$rowMOFact['operador']] += $valor;
}

// MANO DE OBRAS NOTAS DE CREDITO DE SERVICIOS
$queryMONotaCred = sprintf("SELECT
	a.id_filtro_orden,
	a.id_tipo_orden,
	a.operador,
	
	(CASE a.id_modo
		WHEN 1 THEN -- UT
			(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
		WHEN 2 THEN -- PRECIO
			a.precio
	END) AS total_tempario_dev_orden
	
FROM sa_v_informe_final_tempario_dev a %s %s;", $sqlBusq, $sqlBusq2);
$rsMONotaCred = mysql_query($queryMONotaCred);
if (!$rsMONotaCred) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowMONotaCred = mysql_fetch_assoc($rsMONotaCred)) {
	$valor = $rowMONotaCred['total_tempario_dev_orden'];
	
	$arrayOtroMO[$rowMONotaCred['operador']][$rowMONotaCred['id_filtro_orden']] -= $valor;

	$arrayOtroTotalMOTipoOrden[$rowMONotaCred['id_filtro_orden']] -= $valor;
	$arrayOtroTotalMOOperador[$rowMONotaCred['operador']] -= $valor;
}

// MANO DE OBRAS VALE DE SALIDA DE SERVICIOS
$queryMOValeSal = sprintf("SELECT
	a.id_filtro_orden,
	a.id_tipo_orden,
	a.operador,
	
	(CASE a.id_modo
		WHEN 1 THEN -- UT
			(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
		WHEN 2 THEN -- PRECIO
			a.precio
	END) AS total_tempario_vale
	
FROM sa_v_vale_informe_final_tempario a %s %s;", $sqlBusq, $sqlBusq2);
$rsMOValeSal = mysql_query($queryMOValeSal);
if (!$rsMOValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowMOValeSal = mysql_fetch_assoc($rsMOValeSal)) {
	$valor = $rowMOValeSal['total_tempario_vale'];
	
	$arrayOtroMO[$rowMOValeSal['operador']][$rowMOValeSal['id_filtro_orden']] += $valor;

	$arrayOtroTotalMOTipoOrden[$rowMOValeSal['id_filtro_orden']] += $valor;
	$arrayOtroTotalMOOperador[$rowMOValeSal['operador']] += $valor;
}

// MANO DE OBRAS VALE DE ENTRADA DE SERVICIOS
$queryMOValeEnt = sprintf("SELECT
	a.id_filtro_orden,
	a.id_tipo_orden,
	a.operador,
	
	(CASE a.id_modo
		WHEN 1 THEN -- UT
			(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
		WHEN 2 THEN -- PRECIO
			a.precio
	END) AS total_tempario_vale
	
FROM sa_v_vale_informe_final_tempario_dev a %s %s;", $sqlBusq, $sqlBusq2);
$rsMOValeEnt = mysql_query($queryMOValeEnt);
if (!$rsMOValeEnt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowMOValeEnt = mysql_fetch_assoc($rsMOValeEnt)) {
	$valor = $rowMOValeEnt['total_tempario_vale'];
	
	$arrayOtroMO[$rowMOValeEnt['operador']][$rowMOValeEnt['id_filtro_orden']] -= $valor;

	$arrayOtroTotalMOTipoOrden[$rowMOValeEnt['id_filtro_orden']] -= $valor;
	$arrayOtroTotalMOOperador[$rowMOValeEnt['operador']] -= $valor;
}


// TOT FACTURAS DE SERVICIOS
$queryTotFact = sprintf("SELECT * FROM sa_v_informe_final_tot a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotFact = mysql_query($queryTotFact);
if (!$rsTotFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowTotFact = mysql_fetch_assoc($rsTotFact)) {
	$valor = $rowTotFact['monto_total'] + (($rowTotFact['porcentaje_tot'] * $rowTotFact['monto_total']) / 100);

	$arrayOtroMO[$idTot][$rowTotFact['id_filtro_orden']] += $valor;

	$arrayOtroTotalMOTipoOrden[$rowTotFact['id_filtro_orden']] += $valor;
	$arrayOtroTotalMOOperador[$idTot] += $valor;
}

// TOT NOTAS DE CREDITO DE SERVICIOS
$queryTotNotaCred = sprintf("SELECT * FROM sa_v_informe_final_tot_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotNotaCred = mysql_query($queryTotNotaCred);
if (!$rsTotNotaCred) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowTotNotaCred = mysql_fetch_assoc($rsTotNotaCred)) {
	$valor = $rowTotNotaCred['monto_total'] + (($rowTotNotaCred['porcentaje_tot'] * $rowTotNotaCred['monto_total']) / 100);

	$arrayOtroMO[$idTot][$rowTotNotaCred['id_filtro_orden']] -= $valor;

	$arrayOtroTotalMOTipoOrden[$rowTotNotaCred['id_filtro_orden']] -= $valor;
	$arrayOtroTotalMOOperador[$idTot] -= $valor;
}

// TOT VALE DE SALIDA DE SERVICIOS
$queryTotValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_tot a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotValeSal = mysql_query($queryTotValeSal);
if (!$rsTotValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowTotValeSal = mysql_fetch_assoc($rsTotValeSal)) {
	$valor = $rowTotValeSal['monto_total'] + (($rowTotValeSal['porcentaje_tot'] * $rowTotValeSal['monto_total']) / 100);

	$arrayOtroMO[$idTot][$rowTotValeSal['id_filtro_orden']] += $valor;
	
	$arrayOtroTotalMOTipoOrden[$rowTotValeSal['id_filtro_orden']] += $valor;
	$arrayOtroTotalMOOperador[$idTot] += $valor;
}

// TOT VALE DE ENTRADA DE SERVICIOS
$queryTotValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_tot_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotValeEnt = mysql_query($queryTotValeEnt);
if (!$rsTotValeEnt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowTotValeEnt = mysql_fetch_assoc($rsTotValeEnt)) {
	$valor = $rowTotValeEnt['monto_total'] + (($rowTotValeEnt['porcentaje_tot'] * $rowTotValeEnt['monto_total']) / 100);

	$arrayOtroMO[$idTot][$rowTotValeEnt['id_filtro_orden']] -= $valor;
	
	$arrayOtroTotalMOTipoOrden[$rowTotValeEnt['id_filtro_orden']] -= $valor;
	$arrayOtroTotalMOOperador[$idTot] -= $valor;
}


// REPUESTOS FACTURAS DE SERVICIOS
$queryRepFact = sprintf("SELECT * FROM sa_v_informe_final_repuesto a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepFact = mysql_query($queryRepFact);
if (!$rsRepFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayOtroRepuesto = array();
$arrayOtroTotalRepuestoTipo = array();
$arrayOtroTotalRepuestoTipoOrden = array();
$arrayOtroTotalDescuentoRepuestoTipoOrden = array();
while ($rowRepFact = mysql_fetch_assoc($rsRepFact)) {
	$valor = $rowRepFact['precio_unitario'] * $rowRepFact['cantidad'];

	$desc = (($valor * $rowRepFact['porcentaje_descuento_orden']) / 100);

	$arrayOtroRepuesto[$rowRepFact['id_tipo_articulo']][$rowRepFact['id_filtro_orden']] += $valor;

	$arrayOtroTotalRepuestoTipo[$rowRepFact['id_tipo_articulo']] += $valor;
	$arrayOtroTotalRepuestoTipoOrden[$rowRepFact['id_filtro_orden']] += $valor;
	$arrayOtroTotalDescuentoRepuestoTipoOrden[$rowRepFact['id_filtro_orden']] += $desc;
}

// REPUESTOS NOTAS DE CREDITO DE SERVICIOS
$queryRepNotaCred = sprintf("SELECT * FROM sa_v_informe_final_repuesto_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepNotaCred = mysql_query($queryRepNotaCred);
if (!$rsRepNotaCred) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowRepNotaCred = mysql_fetch_assoc($rsRepNotaCred)) {
	$valor = $rowRepNotaCred['precio_unitario'] * $rowRepNotaCred['cantidad'];

	$desc = (($valor * $rowRepNotaCred['porcentaje_descuento_orden']) / 100);

	$arrayOtroRepuesto[$rowRepNotaCred['id_tipo_articulo']][$rowRepNotaCred['id_filtro_orden']] -= $valor;

	$arrayOtroTotalRepuestoTipo[$rowRepNotaCred['id_tipo_articulo']] -= $valor;
	$arrayOtroTotalRepuestoTipoOrden[$rowRepNotaCred['id_filtro_orden']] -= $valor;
	$arrayOtroTotalDescuentoRepuestoTipoOrden[$rowRepNotaCred['id_filtro_orden']] -= $desc;
}

// REPUESTOS VALE DE SALIDA DE SERVICIOS
$queryRepValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepValeSal = mysql_query($queryRepValeSal);
if (!$rsRepValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowRepValeSal = mysql_fetch_assoc($rsRepValeSal)) {
	$valor = $rowRepValeSal['precio_unitario'] * $rowRepValeSal['cantidad'];

	$desc = (($valor * $rowRepValeSal['porcentaje_descuento_orden']) / 100);

	$arrayOtroRepuesto[$rowRepValeSal['id_tipo_articulo']][$rowRepValeSal['id_filtro_orden']] += $valor;

	$arrayOtroTotalRepuestoTipo[$rowRepValeSal['id_tipo_articulo']] += $valor;
	$arrayOtroTotalRepuestoTipoOrden[$rowRepValeSal['id_filtro_orden']] += $valor;
	$arrayOtroTotalDescuentoRepuestoTipoOrden[$rowRepValeSal['id_filtro_orden']] += $desc;
}

// REPUESTOS VALE DE ENTRADA DE SERVICIOS
$queryRepValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepValeEnt = mysql_query($queryRepValeEnt);
if (!$rsRepValeEnt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowRepValeEnt = mysql_fetch_assoc($rsRepValeEnt)) {
	$valor = $rowRepValeEnt['precio_unitario'] * $rowRepValeEnt['cantidad'];

	$desc = (($valor * $rowRepValeEnt['porcentaje_descuento_orden']) / 100);

	$arrayOtroRepuesto[$rowRepValeEnt['id_tipo_articulo']][$rowRepValeEnt['id_filtro_orden']] -= $valor;

	$arrayOtroTotalRepuestoTipo[$rowRepValeEnt['id_tipo_articulo']] -= $valor;
	$arrayOtroTotalRepuestoTipoOrden[$rowRepValeEnt['id_filtro_orden']] -= $valor;
	$arrayOtroTotalDescuentoRepuestoTipoOrden[$rowRepValeEnt['id_filtro_orden']] -= $desc;
}


// NOTAS FACTURAS DE SERVICIOS
$queryNotaFact = sprintf("SELECT * FROM sa_v_informe_final_notas a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotaFact = mysql_query($queryNotaFact);
if (!$rsNotaFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$arrayOtroTotalNotaTipoOrden = array();
$totalNota = 0;
while ($rowNotaFact = mysql_fetch_assoc($rsNotaFact)) {
	$arrayOtroTotalNotaTipoOrden[$rowNotaFact['id_filtro_orden']] += $rowNotaFact['precio'];
	$totalNota += $rowNotaFact['precio'];
}

// NOTAS NOTAS DE CREDITO DE SERVICIOS
$queryNotaNotaCred = sprintf("SELECT * FROM sa_v_informe_final_notas_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotaNotaCred = mysql_query($queryNotaNotaCred);
if (!$rsNotaNotaCred) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowNotaNotaCred = mysql_fetch_assoc($rsNotaNotaCred)) {
	$arrayOtroTotalNotaTipoOrden[$rowNotaNotaCred['id_filtro_orden']] -= $rowNotaNotaCred['precio'];
	$totalNota -= $rowNotaNotaCred['precio'];
}

// NOTAS VALE DE SALIDA DE SERVICIOS
$queryNotaValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_notas a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotaValeSal = mysql_query($queryNotaValeSal);
if (!$rsNotaValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowNotaValeSal = mysql_fetch_assoc($rsNotaValeSal)) {
	$arrayOtroTotalNotaTipoOrden[$rowNotaValeSal['id_filtro_orden']] += $rowNotaValeSal['precio'];
	$totalNota += $rowNotaValeSal['precio'];
}

// NOTAS VALE DE ENTRADA DE SERVICIOS
$queryNotaValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_notas_dev a %s ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotaValeEnt = mysql_query($queryNotaValeEnt);
if (!$rsNotaValeEnt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowNotaValeEnt = mysql_fetch_assoc($rsNotaValeEnt)) {
	$arrayOtroTotalNotaTipoOrden[$rowNotaValeEnt['id_filtro_orden']] -= $rowNotaValeEnt['precio'];
	$totalNota -= $rowNotaValeEnt['precio'];
}

// CALCULO DEL TOTAL
(count($arrayOtroTotalMOTipoOrden) > 0) ? "" : $arrayOtroTotalMOTipoOrden[0] = 0;
(count($arrayOtroTotalRepuestoTipoOrden) > 0) ? "" : $arrayOtroTotalRepuestoTipoOrden[0] = 0;
(count($arrayOtroTotalNotaTipoOrden) > 0) ? "" : $arrayOtroTotalNotaTipoOrden[0] = 0;
(count($arrayTotalRepuestoDescuentoTipoOrden) > 0) ? "" : $arrayTotalRepuestoDescuentoTipoOrden[0] = 0;

$totalProdOtro = array_sum($arrayOtroTotalMOTipoOrden) + array_sum($arrayOtroTotalRepuestoTipoOrden) - array_sum($arrayTotalRepuestoDescuentoTipoOrden);
$totalProdOtro += ($rowConfig300['valor'] == 1) ? array_sum($arrayOtroTotalNotaTipoOrden) : 0;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION REPUESTOS MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
AND fact_vent.aplicaLibros = 1");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
AND nota_cred.tipoDocumento = 'FA'
AND nota_cred.aplicaLibros = 1
AND nota_cred.estatus_nota_credito = 2");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = fact_vent.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = nota_cred.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
	AND YEAR(fact_vent.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
	AND YEAR(nota_cred.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// PRODUCCIÓN REPUESTOS MOSTRADOR
$query = sprintf("SELECT
	fact_vent.condicionDePago,
	(fact_vent.subtotalFactura - IFNULL(fact_vent.descuentoFactura, 0)) AS neto
FROM cj_cc_encabezadofactura fact_vent %s
	
UNION ALL

SELECT
	fact_vent2.condicionDePago,
	((-1)*(nota_cred.subtotalNotaCredito - IFNULL(nota_cred.subtotal_descuento, 0))) AS neto
FROM cj_cc_notacredito nota_cred
	INNER JOIN cj_cc_encabezadofactura fact_vent2 ON (nota_cred.idDocumento = fact_vent2.idFactura) %s;",
	$sqlBusq,
	$sqlBusq2);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$arrayVentaMost = array();
while ($row = mysql_fetch_assoc($rs)) {
	switch($row['condicionDePago']) {
		case 0 : $arrayVentaMost[1] += round($row['neto'],2); break;
		case 1 : $arrayVentaMost[0] += round($row['neto'],2); break;
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// MARGEN DE REPUESTOS POR SERVICIOS Y MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("a.aprobado = 1");

if (strlen($rowConfig301['valor']) > 0) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
		valTpDato($rowConfig301['valor'], "campo"));
}

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = a.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
	AND YEAR(a.fecha_filtro) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// COSTO DE VENTAS REPUESTOS POR SERVICIOS Y LATONERIA Y PINTURA
$query2 = sprintf("SELECT
	SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
FROM (
	SELECT (costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_informe_final_repuesto a %s
	
	UNION ALL
	
	SELECT (-1)*(costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_informe_final_repuesto_dev a %s
	
	UNION ALL
	
	SELECT (costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_vale_informe_final_repuesto a %s) AS query",
	$sqlBusq,
	$sqlBusq,
	$sqlBusq);
$rs2 = mysql_query($query2);
if (!$rs2) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$row2 = mysql_fetch_assoc($rs2);
$arrayCostoRepServ[0] = round($row2['total_costo_repuesto_orden'],2);


$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
AND fact_vent.aplicaLibros = 1");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
AND nota_cred.tipoDocumento = 'FA'
AND nota_cred.aplicaLibros = 1
AND nota_cred.estatus_nota_credito = 2");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = fact_vent.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = nota_cred.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
	AND YEAR(fact_vent.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
	AND YEAR(nota_cred.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// COSTO DE VENTAS REPUESTOS
$query = sprintf("SELECT
	(SELECT SUM((fact_vent_det.cantidad * fact_vent_det.costo_compra)) AS costo_total
	FROM cj_cc_factura_detalle fact_vent_det
	WHERE fact_vent_det.id_factura = fact_vent.idFactura) AS neto
FROM cj_cc_encabezadofactura fact_vent %s
	
UNION ALL

SELECT
	((-1)*(SELECT SUM((nota_cred_det.cantidad * nota_cred_det.costo_compra)) AS costo_total
	FROM cj_cc_nota_credito_detalle nota_cred_det
	WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito)) AS neto
FROM cj_cc_notacredito nota_cred %s;",
	$sqlBusq,
	$sqlBusq2);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalCostoRepMost = 0;
while ($rowDetalle = mysql_fetch_assoc($rs)) {
	$totalCostoRepMost += round($rowDetalle['neto'],2);
}
$arrayCostoRepMost[0] = $totalCostoRepMost;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ANÁLISIS DE INVENTARIO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";	
if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
	$sqlBusq .= $cond.sprintf("(cierre_mens.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cierre_mens.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre_mens.mes = %s
	AND cierre_mens.ano = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

$queryDetalle = sprintf("SELECT
	analisis_inv_det.id_analisis_inventario,
	analisis_inv_det.cantidad_existencia,
	analisis_inv_det.cantidad_disponible_logica,
	analisis_inv_det.cantidad_disponible_fisica,
	analisis_inv_det.costo,
	(analisis_inv_det.costo * analisis_inv_det.cantidad_existencia) AS costo_total,
	(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
	analisis_inv_det.promedio_diario,
	analisis_inv_det.promedio_mensual,
	(analisis_inv_det.promedio_mensual * 2) AS inventario_recomendado,
	(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * 2)) AS sobre_stock,
	((analisis_inv_det.promedio_mensual * 2) - analisis_inv_det.cantidad_existencia) AS sugerido,
	analisis_inv_det.clasificacion
FROM iv_analisis_inventario_detalle analisis_inv_det
	INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
	INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
	INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s
ORDER BY clasificacion ASC;", $sqlBusq);
$rsDetalle = mysql_query($queryDetalle);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
	$cantExistencia = round($rowDetalle['cantidad_existencia'],2);
	$costoInv = round($rowDetalle['costo_total'],2);
	$promVenta = round($rowDetalle['promedio_mensual'] * $rowDetalle['costo'],2);
	
	$existeAnalisisInv = false;
	if (isset($arrayAnalisisInv)) {
		foreach ($arrayAnalisisInv as $indice2 => $valor2) {
			if ($rowDetalle['clasificacion'] == $arrayAnalisisInv[$indice2][0]) {
				$existeAnalisisInv = true;
				
				$arrayAnalisisInv[$indice2][1]++;
				$arrayAnalisisInv[$indice2][2] += $cantExistencia;
				$arrayAnalisisInv[$indice2][3] += $costoInv;
				$arrayAnalisisInv[$indice2][4] += $promVenta;
				$arrayAnalisisInv[$indice2][5] += (($arrayAnalisisInv[$indice2][4] > 0) ? ($arrayAnalisisInv[$indice2][3] / $arrayAnalisisInv[$indice2][4]) : 0);
			}
		}
	}
	
	if ($existeAnalisisInv == false) {
		$arrayAnalisisInv[] = array(
			$rowDetalle['clasificacion'],
			1,
			$cantExistencia,
			$costoInv,
			$promVenta,
			(($promVenta > 0) ? ($costoInv / $promVenta) : 0));
	}
	
	$totalCantArt++;
	$totalExistArt += $cantExistencia;
	$totalCostoInv += $costoInv;
	$totalPromVentas += $promVenta;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// CANTIDAD DE ITEMS Y ARTÍCULOS VENDIDOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";	
if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
	$sqlBusq .= $cond.sprintf("(cierre_mens.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cierre_mens.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre_mens.mes = %s
	AND cierre_mens.ano = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// AGRUPA LAS CLASIFICACIONES PARA CALCULAR SUS TOTALES
$queryTipoMov = sprintf("SELECT
	analisis_inv.id_analisis_inventario,
	cierre_mens.id_empresa,
	cierre_mens.ano,
	analisis_inv_det.clasificacion_anterior
FROM iv_analisis_inventario_detalle analisis_inv_det
	INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
	INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
	INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s
GROUP BY analisis_inv.id_analisis_inventario, clasificacion_anterior", $sqlBusq);
$rsTipoMov = mysql_query($queryTipoMov);
if (!$rsTipoMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowTipoMov = mysql_fetch_assoc($rsTipoMov)) {
	$queryNroVend = sprintf("SELECT
		cierre_anual.%s AS numero_vendido
	FROM iv_cierre_anual cierre_anual
	WHERE cierre_anual.id_articulo IN (SELECT id_articulo FROM iv_analisis_inventario_detalle analisis_inv_det
			WHERE analisis_inv_det.id_analisis_inventario = %s AND analisis_inv_det.clasificacion_anterior = %s)
		AND cierre_anual.ano = %s
		AND cierre_anual.%s IS NOT NULL
		AND cierre_anual.%s > 0
		AND cierre_anual.id_empresa = %s",
		valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
		valTpDato($rowTipoMov['id_analisis_inventario'], "int"), valTpDato($rowTipoMov['clasificacion_anterior'], "text"),
		valTpDato($rowTipoMov['ano'], "int"),
		valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
		valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
		valTpDato($rowTipoMov['id_empresa'], "int"));
	$rsNroVend = mysql_query($queryNroVend);
	if (!$rsNroVend) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsNroVend = mysql_num_rows($rsNroVend);

	$queryCantVend = sprintf("SELECT SUM(IFNULL(cierre_anual.%s, 0)) AS cantidad_vendida
	FROM iv_cierre_anual cierre_anual
	WHERE cierre_anual.id_articulo IN (SELECT id_articulo FROM iv_analisis_inventario_detalle analisis_inv_det
			WHERE analisis_inv_det.id_analisis_inventario = %s AND analisis_inv_det.clasificacion_anterior = %s)
		AND cierre_anual.ano = %s
		AND cierre_anual.id_empresa = %s",
		valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
		valTpDato($rowTipoMov['id_analisis_inventario'], "int"), valTpDato($rowTipoMov['clasificacion_anterior'], "text"),
		valTpDato($rowTipoMov['ano'], "int"),
		valTpDato($rowTipoMov['id_empresa'], "int"));
	$rsCantVend = mysql_query($queryCantVend);
	if (!$rsCantVend) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowCantVend = mysql_fetch_assoc($rsCantVend);
	
	$existeCantArtVend = false;
	if (isset($arrayCantArtVend)) {
		foreach ($arrayCantArtVend as $indice2 => $valor2) {
			if ($rowTipoMov['clasificacion_anterior'] == $arrayCantArtVend[$indice2][0]) {
				$existeCantArtVend = true;
				
				$arrayCantArtVend[$indice2][1] += $totalRowsNroVend;
				$arrayCantArtVend[$indice2][2] += $rowCantVend['cantidad_vendida'];
			}
		}
	}
	
	if ($existeCantArtVend == false) {
		$arrayCantArtVend[] = array(
			$rowTipoMov['clasificacion_anterior'],
			$totalRowsNroVend,
			$rowCantVend['cantidad_vendida']);
	}
	
	$totalNroArt += $totalRowsNroVend;
	$totalCantArtVend += $rowCantVend['cantidad_vendida'];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// INDICADORES DE TALLER
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(recepcion.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = recepcion.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(recepcion.fecha_entrada) = %s
	AND YEAR(recepcion.fecha_entrada) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// TIPO VALE RECEPCION
$queryTipoValeRecepcion = sprintf("SELECT tipo_vale.* FROM sa_tipo_vale tipo_vale");
$rsTipoValeRecepcion = mysql_query($queryTipoValeRecepcion);
if (!$rsTipoValeRecepcion) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRowsTipoValeRecepcion = mysql_num_rows($rsTipoValeRecepcion);
while($rowTipoValeRecepcion = mysql_fetch_assoc($rsTipoValeRecepcion)) {
	$arrayValeRecepcion[$rowTipoValeRecepcion['id_tipo_vale']][0] = $rowTipoValeRecepcion['descripcion'];
}

// ENTRADA DE VEHICULOS
$queryValeRecepcion = sprintf("SELECT recepcion.* FROM sa_recepcion recepcion %s", $sqlBusq);
$rsValeRecepcion = mysql_query($queryValeRecepcion);
if (!$rsValeRecepcion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsValeRecepcion = mysql_num_rows($rsValeRecepcion);
while($rowValeRecepcion = mysql_fetch_assoc($rsValeRecepcion)) {
	$arrayValeRecepcion[$rowValeRecepcion['id_tipo_vale']][1] += 1;
}

if (count($idCierreMensual) > 0 && $idCierreMensual != "-1") {
	$queryCierreMensualOrden = sprintf("SELECT
		tipo_orden.id_tipo_orden,
		tipo_orden.nombre_tipo_orden,
		cierre_mensual_orden.cantidad_abiertas,
		cierre_mensual_orden.cantidad_cerradas,
		cierre_mensual_orden.cantidad_fallas_abiertas,
		cierre_mensual_orden.cantidad_fallas_cerradas,
		cierre_mensual_orden.cantidad_uts_cerradas
	FROM iv_cierre_mensual_orden cierre_mensual_orden
		INNER JOIN sa_tipo_orden tipo_orden ON (cierre_mensual_orden.id_tipo_orden = tipo_orden.id_tipo_orden)
		INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden)
	WHERE cierre_mensual_orden.id_cierre_mensual IN (%s)
	ORDER BY filtro_orden.descripcion;",
		valTpDato($idCierreMensual, "campo"));
	$rsCierreMensualOrden = mysql_query($queryCierreMensualOrden);
	if (!$rsCierreMensualOrden) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowCierreMensualOrden = mysql_fetch_assoc($rsCierreMensualOrden)) {
		$arrayTipoOrden[$rowCierreMensualOrden['id_tipo_orden']] = array(
			"nombre_tipo_orden" => $rowCierreMensualOrden['nombre_tipo_orden'],
			"cantidad_abiertas" => $rowCierreMensualOrden['cantidad_abiertas'],
			"cantidad_cerradas" => $rowCierreMensualOrden['cantidad_cerradas'],
			"cantidad_fallas_abiertas" => $rowCierreMensualOrden['cantidad_fallas_abiertas'],
			"cantidad_fallas_cerradas" => $rowCierreMensualOrden['cantidad_fallas_cerradas'],
			"cantidad_uts_cerradas" => $rowCierreMensualOrden['cantidad_uts_cerradas']);
	}
} else {
	// ORDENES DE SERVICIOS ABIERTAS Y CERRADAS
	$Result1 = cierreOrdenesServicio($idEmpresa, $valFecha[0], $valFecha[1]);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		die($Result1[1]); 
	} else {
		$arrayTipoOrden = $Result1[1];
	}
}

// AGRUPA LOS TIPO DE ORDEN POR FILTRO DE ORDEN
if (isset($arrayTipoOrden)) {
	foreach ($arrayTipoOrden as $indice => $valor) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_orden.id_tipo_orden = %s",
			valTpDato($indice, "int"));
			
		if (strlen($rowConfig301['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("filtro_orden.id_filtro_orden IN (%s)",
				valTpDato($rowConfig301['valor'], "campo"));
		}
		
		$queryFiltroOrden = sprintf("SELECT
			filtro_orden.id_filtro_orden,
			filtro_orden.descripcion
		FROM sa_tipo_orden tipo_orden
			INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s", $sqlBusq);
		$rsFiltroOrden = mysql_query($queryFiltroOrden);
		if (!$rsFiltroOrden) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
			$existe = false;
			if (isset($arrayFiltroOrden)) {
				foreach ($arrayFiltroOrden as $indice2 => $valor2) {
					if ($indice2 == $rowFiltroOrden['id_filtro_orden']) {
						$existe = true;
						
						$arrayFiltroOrden[$indice2]['cantidad_abiertas'] += $arrayTipoOrden[$indice]['cantidad_abiertas'];
						$arrayFiltroOrden[$indice2]['cantidad_cerradas'] += $arrayTipoOrden[$indice]['cantidad_cerradas'];
						$arrayFiltroOrden[$indice2]['cantidad_fallas_abiertas'] += $arrayTipoOrden[$indice]['cantidad_fallas_abiertas'];
						$arrayFiltroOrden[$indice2]['cantidad_fallas_cerradas'] += $arrayTipoOrden[$indice]['cantidad_fallas_cerradas'];
						$arrayFiltroOrden[$indice2]['cantidad_uts_cerradas'] += $arrayTipoOrden[$indice]['cantidad_uts_cerradas'];
					}
				}
			}
				
			if ($existe == false) {
				$arrayFiltroOrden[$rowFiltroOrden['id_filtro_orden']] = array(
					"nombre_tipo_orden" => $rowFiltroOrden['descripcion'],
					"cantidad_abiertas" => $arrayTipoOrden[$indice]['cantidad_abiertas'],
					"cantidad_cerradas" => $arrayTipoOrden[$indice]['cantidad_cerradas'],
					"cantidad_fallas_abiertas" => $arrayTipoOrden[$indice]['cantidad_fallas_abiertas'],
					"cantidad_fallas_cerradas" => $arrayTipoOrden[$indice]['cantidad_fallas_cerradas'],
					"cantidad_uts_cerradas" => $arrayTipoOrden[$indice]['cantidad_uts_cerradas']);
			}
			
			$totalTipoOrdenAbierta += $arrayTipoOrden[$indice]['cantidad_abiertas'];
			$totalTipoOrdenCerrada += $arrayTipoOrden[$indice]['cantidad_cerradas'];
			$totalFallaTipoOrdenAbierta += $arrayTipoOrden[$indice]['cantidad_fallas_abiertas'];
			$totalFallaTipoOrdenCerrada += $arrayTipoOrden[$indice]['cantidad_fallas_cerradas'];
			$totalUtsTipoOrdenCerrada += $arrayTipoOrden[$indice]['cantidad_uts_cerradas'];
		}
	}
}
$arrayTipoOrden = $arrayFiltroOrden;

$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("tipo NOT IN ('FERIADO')");
	
if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fecha_baja) = %s
	AND (YEAR(fecha_baja) = %s OR YEAR(fecha_baja) = '0000')",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// BUSCA LOS DIAS FERIADOS
$queryDiasFeriados = sprintf("SELECT * FROM pg_fecha_baja %s;", $sqlBusq);
$rsDiasFeriados = mysql_query($queryDiasFeriados);
if (!$rsDiasFeriados) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDiasFeriados = mysql_num_rows($rsDiasFeriados);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN ASESORES DE SERVICIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
if (count($idCierreMensual) > 0 && $idCierreMensual != "-1") {
	$queryCierreFacturacion = sprintf("SELECT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		cierre_mensual_fact.id_tipo_orden,
		cierre_mensual_fact.cantidad_ordenes,
		cierre_mensual_fact.total_ut,
		cierre_mensual_fact.total_mano_obra,
		cierre_mensual_fact.total_tot,
		cierre_mensual_fact.total_repuesto
	FROM iv_cierre_mensual_facturacion cierre_mensual_fact
		INNER JOIN pg_empleado empleado ON (cierre_mensual_fact.id_empleado = empleado.id_empleado)
	WHERE cierre_mensual_fact.id_cierre_mensual IN (%s)
		AND cierre_mensual_fact.id_modulo IN (1)
		AND cierre_mensual_fact.id_tipo_orden IS NOT NULL;",
		valTpDato($idCierreMensual, "campo"));
	$rsCierreFacturacion = mysql_query($queryCierreFacturacion);
	if (!$rsCierreFacturacion) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowCierreFacturacion = mysql_fetch_assoc($rsCierreFacturacion)) {
		$totalMoAsesor = $rowCierreFacturacion['total_mano_obra'];
		$totalTotAsesor = $rowCierreFacturacion['total_tot'];
		$totalRepuetosAsesor = $rowCierreFacturacion['total_repuesto'];
		
		$arrayVentaAsesor[] = array(
			"id_empleado" => $rowCierreFacturacion['id_empleado'],
			"nombre_asesor" => $rowCierreFacturacion['nombre_empleado'],
			"id_tipo_orden" => $rowCierreFacturacion['id_tipo_orden'],
			"cantidad_ordenes" => $rowCierreFacturacion['cantidad_ordenes'],
			"total_ut" => $rowCierreFacturacion['total_ut'],
			"total_mo" => $totalMoAsesor,
			"total_repuestos" => $totalRepuetosAsesor,
			"total_tot" => $totalTotAsesor,
			"total_asesor" => $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor);
		
		//$totalVentaAsesores += $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor;
	}
} else {
	$Result1 = facturacionAsesores($idEmpresa, $valFecha[0], $valFecha[1]);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		die($Result1[1]); 
	} else {
		$arrayVentaAsesor = $Result1[1];
		//$totalVentaAsesores = $Result1[2];
	}
}

// AGRUPA LOS TIPO DE ORDEN POR FILTRO DE ORDEN
$arrayFiltroOrden = NULL;
if (isset($arrayVentaAsesor)) {
	foreach ($arrayVentaAsesor as $indice => $valor) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(tipo_orden.id_tipo_orden = %s)",
			valTpDato($arrayVentaAsesor[$indice]['id_tipo_orden'], "int"));
		
		if (strlen($rowConfig301['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("filtro_orden.id_filtro_orden IN (%s)",
				valTpDato($rowConfig301['valor'], "campo"));
		}
		
		$queryFiltroOrden = sprintf("SELECT
			filtro_orden.id_filtro_orden,
			filtro_orden.descripcion
		FROM sa_tipo_orden tipo_orden
			INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s", $sqlBusq);
		$rsFiltroOrden = mysql_query($queryFiltroOrden);
		if (!$rsFiltroOrden) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFiltroOrden = mysql_num_rows($rsFiltroOrden);
		while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
			$existe = false;
			$arrayDetalleFiltroOrden = NULL;
			if (isset($arrayFiltroOrden)) {
				foreach ($arrayFiltroOrden as $indice2 => $valor2) {
					if ($arrayFiltroOrden[$indice2]['id_empleado'] == $arrayVentaAsesor[$indice]['id_empleado']) {
						$existe = true;
						
						$existeFiltroOrden = false;
						$arrayDetalleFiltroOrden = NULL;
						if (isset($arrayFiltroOrden[$indice2]['array_tipo_orden'])) {
							foreach ($arrayFiltroOrden[$indice2]['array_tipo_orden'] as $indice3 => $valor3) {
								$arrayDetalleFiltroOrden = $valor3;
								if ($arrayDetalleFiltroOrden['id_tipo_orden'] == $rowFiltroOrden['id_filtro_orden']) {
									$existeFiltroOrden = true;
									
									$arrayDetalleFiltroOrden['cantidad_ordenes'] += round($arrayVentaAsesor[$indice]['cantidad_ordenes'],2);
									$arrayDetalleFiltroOrden['total_ut'] += round($arrayVentaAsesor[$indice]['total_ut'],2);
									$arrayDetalleFiltroOrden['total_mo'] += round($arrayVentaAsesor[$indice]['total_mo'],2);
									$arrayDetalleFiltroOrden['total_repuestos'] += round($arrayVentaAsesor[$indice]['total_repuestos'],2);
									$arrayDetalleFiltroOrden['total_tot'] += round($arrayVentaAsesor[$indice]['total_tot'],2);
									$arrayDetalleFiltroOrden['total_asesor'] += round($arrayVentaAsesor[$indice]['total_asesor'],2);
								}
								
								$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['cantidad_ordenes'] = $arrayDetalleFiltroOrden['cantidad_ordenes'];
								$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_ut'] = $arrayDetalleFiltroOrden['total_ut'];
								$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_mo'] = $arrayDetalleFiltroOrden['total_mo'];
								$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_repuestos'] = $arrayDetalleFiltroOrden['total_repuestos'];
								$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_tot'] = $arrayDetalleFiltroOrden['total_tot'];
								$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_asesor'] = $arrayDetalleFiltroOrden['total_asesor'];
							}
						}
						
						if ($existeFiltroOrden == false) {
							$arrayDetalleFiltroOrden = array(
								"id_tipo_orden" => $rowFiltroOrden['id_filtro_orden'],
								"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
								"cantidad_ordenes" => $arrayVentaAsesor[$indice]['cantidad_ordenes'],
								"total_ut" => $arrayVentaAsesor[$indice]['total_ut'],
								"total_mo" => $arrayVentaAsesor[$indice]['total_mo'],
								"total_repuestos" => $arrayVentaAsesor[$indice]['total_repuestos'],
								"total_tot" => $arrayVentaAsesor[$indice]['total_tot'],
								"total_asesor" => $arrayVentaAsesor[$indice]['total_asesor']);
							
							$arrayFiltroOrden[$indice2]['array_tipo_orden'][] = $arrayDetalleFiltroOrden;
						}
						
						$arrayFiltroOrden[$indice2]['cantidad_ordenes'] += $arrayVentaAsesor[$indice]['cantidad_ordenes'];
						$arrayFiltroOrden[$indice2]['total_ut'] += $arrayVentaAsesor[$indice]['total_ut'];
						$arrayFiltroOrden[$indice2]['total_mo'] += $arrayVentaAsesor[$indice]['total_mo'];
						$arrayFiltroOrden[$indice2]['total_repuestos'] += $arrayVentaAsesor[$indice]['total_repuestos'];
						$arrayFiltroOrden[$indice2]['total_tot'] += $arrayVentaAsesor[$indice]['total_tot'];
						$arrayFiltroOrden[$indice2]['total_asesor'] += $arrayVentaAsesor[$indice]['total_asesor'];
					}
				}
			}
			
			if ($existe == false) {
				$arrayDetalleFiltroOrden[] = array(
					"id_tipo_orden" => $rowFiltroOrden['id_filtro_orden'],
					"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
					"cantidad_ordenes" => $arrayVentaAsesor[$indice]['cantidad_ordenes'],
					"total_ut" => $arrayVentaAsesor[$indice]['total_ut'],
					"total_mo" => $arrayVentaAsesor[$indice]['total_mo'],
					"total_repuestos" => $arrayVentaAsesor[$indice]['total_repuestos'],
					"total_tot" => $arrayVentaAsesor[$indice]['total_tot'],
					"total_asesor" => $arrayVentaAsesor[$indice]['total_asesor']);
				
				$arrayFiltroOrden[] = array(
					"id_empleado" => $arrayVentaAsesor[$indice]['id_empleado'],
					"nombre_asesor" => $arrayVentaAsesor[$indice]['nombre_asesor'],
					//"id_tipo_orden" => $rowFiltroOrden['id_filtro_orden'],
					//"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
					"array_tipo_orden" => $arrayDetalleFiltroOrden,
					"cantidad_ordenes" => $arrayVentaAsesor[$indice]['cantidad_ordenes'],
					"total_ut" => $arrayVentaAsesor[$indice]['total_ut'],
					"total_mo" => $arrayVentaAsesor[$indice]['total_mo'],
					"total_repuestos" => $arrayVentaAsesor[$indice]['total_repuestos'],
					"total_tot" => $arrayVentaAsesor[$indice]['total_tot'],
					"total_asesor" => $arrayVentaAsesor[$indice]['total_asesor']);
			}
			
			$totalVentaAsesores += $arrayVentaAsesor[$indice]['total_asesor'];
		}
		
		if (!($totalRowsFiltroOrden > 0) && !($arrayVentaAsesor[$indice]['id_tipo_orden'] > 0) && ($arrayVentaAsesor[$indice]['cantidad_ordenes'] > 0 || $arrayVentaAsesor[$indice]['total_asesor'] > 0)) {
			$arrayFiltroOrden[] = array(
				"id_empleado" => $arrayVentaAsesor[$indice]['id_empleado'],
				"nombre_asesor" => $arrayVentaAsesor[$indice]['nombre_asesor'],
				//"id_tipo_orden" => $rowFiltroOrden['id_filtro_orden'],
				//"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
				"cantidad_ordenes" => $arrayVentaAsesor[$indice]['cantidad_ordenes'],
				"total_ut" => $arrayVentaAsesor[$indice]['total_ut'],
				"total_mo" => $arrayVentaAsesor[$indice]['total_mo'],
				"total_repuestos" => $arrayVentaAsesor[$indice]['total_repuestos'],
				"total_tot" => $arrayVentaAsesor[$indice]['total_tot'],
				"total_asesor" => $arrayVentaAsesor[$indice]['total_asesor']);
			$totalVentaAsesores += $arrayVentaAsesor[$indice]['total_asesor'];
		}
	}
}
$arrayVentaAsesor = $arrayFiltroOrden;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN VENDEDORES DE REPUESTOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (count($idCierreMensual) > 0 && $idCierreMensual != "-1") {
	$queryCierreFacturacion = sprintf("SELECT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		cierre_mensual_fact.total_facturacion_contado,
		cierre_mensual_fact.total_facturacion_credito,
		cierre_mensual_fact.total_devolucion_contado,
		cierre_mensual_fact.total_devolucion_credito
	FROM iv_cierre_mensual_facturacion cierre_mensual_fact
		INNER JOIN pg_empleado empleado ON (cierre_mensual_fact.id_empleado = empleado.id_empleado)
	WHERE cierre_mensual_fact.id_cierre_mensual IN (%s)
		AND cierre_mensual_fact.id_modulo IN (0);",
		valTpDato($idCierreMensual, "campo"));
	$rsCierreFacturacion = mysql_query($queryCierreFacturacion);
	if (!$rsCierreFacturacion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($rowCierreFacturacion = mysql_fetch_assoc($rsCierreFacturacion)) {
		$totalFacturacionContado = $rowCierreFacturacion['total_facturacion_contado'];
		$totalFacturacionCredito = $rowCierreFacturacion['total_facturacion_credito'];
		$totalDevolucionContado = $rowCierreFacturacion['total_devolucion_contado'];
		$totalDevolucionCredito = $rowCierreFacturacion['total_devolucion_credito'];
		
		$arrayVentaVendedor[] = array(
			$rowCierreFacturacion['id_empleado'],
			$rowCierreFacturacion['nombre_empleado'],
			$totalFacturacionContado,
			$totalFacturacionCredito,
			$totalDevolucionContado,
			$totalDevolucionCredito,
			$totalFacturacionContado - $totalDevolucionContado, // TOTAL FACTURACION CONTADO
			$totalFacturacionCredito - $totalDevolucionCredito, // TOTAL FACTURACION CREDITO
			($totalFacturacionContado - $totalDevolucionContado) + ($totalFacturacionCredito - $totalDevolucionCredito), // TOTAL FACTURACION CONTADO Y CREDITO
			($totalFacturacionContado - $totalDevolucionContado) + ($totalFacturacionCredito - $totalDevolucionCredito)); // TOTAL FACTURACION REPUESTOS
		
		$totalVentaVendedores += ($totalFacturacionContado - $totalDevolucionContado) + ($totalFacturacionCredito - $totalDevolucionCredito);
	}
} else {
	$Result1 = facturacionMostrador($idEmpresa, $valFecha[0], $valFecha[1]);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		die($Result1[1]); 
	} else {
		$arrayVentaVendedor = $Result1[1];
		$totalVentaVendedores = $Result1[2];
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN TÉCNICOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(equipo_mec.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = equipo_mec.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

$queryEquipo = sprintf("SELECT
	equipo_mec.id_equipo_mecanico,
	CONCAT(equipo_mec.nombre_equipo, ' ( ', CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido), ' )') AS nombre_equipo
FROM sa_equipos_mecanicos equipo_mec
	INNER JOIN pg_empleado empleado ON (equipo_mec.id_empleado_jefe_taller = empleado.id_empleado) %s
ORDER BY nombre_equipo", $sqlBusq);
$rsEquipo = mysql_query($queryEquipo);
if (!$rsEquipo) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowEquipo = mysql_fetch_assoc($rsEquipo)) {
	$totalMecanicoBs = 0;
	$totalMecanicoUts = 0;
	
	$queryEmpleado = sprintf("SELECT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		mec.id_mecanico,
		mec.nivel,
		empleado.activo
	FROM sa_mecanicos mec
		INNER JOIN pg_empleado empleado ON (mec.id_empleado = empleado.id_empleado)
	WHERE id_equipo_mecanico = %s
	ORDER BY nombre_empleado",
		valTpDato($rowEquipo['id_equipo_mecanico'], "int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsMercanicos = mysql_num_rows($rsEmpleado);

	$arrayTecnico = NULL;
	while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("det_orden_temp.id_mecanico = %s
		AND a.aprobado = 1
		AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
			valTpDato($rowEmpleado['id_mecanico'], "int"));

		if (strlen($rowConfig301['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig301['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($valFecha[0] != "-1" && $valFecha[0] != ""
		&& $valFecha[1] != "-1" && $valFecha[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
		}
		
		// FACTURAS
		$sql2 = sprintf("SELECT
			IFNULL(SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					a.ut / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					0
			END)), 0) AS uts,
			
			IFNULL(SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.precio
			END)), 0) AS valor_uts
			
		FROM sa_det_orden_tempario det_orden_temp
			INNER JOIN sa_v_informe_final_tempario a ON (det_orden_temp.id_det_orden_tempario = a.id_det_orden_tempario) %s;", $sqlBusq);
		$rs2 = mysql_query($sql2);
		if (!$rs2) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsFact = mysql_num_rows($rs2);
		$row2 = mysql_fetch_assoc($rs2);

		// NOTAS DE CREDITO
		$sql3 = sprintf("SELECT
			IFNULL(SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					a.ut / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					0
			END)), 0) AS uts_dev,
			
			IFNULL(SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.precio
			END)), 0) AS valor_uts_dev

			
		FROM sa_det_orden_tempario det_orden_temp
			INNER JOIN sa_v_informe_final_tempario_dev a ON (det_orden_temp.id_det_orden_tempario = a.id_det_orden_tempario) %s;", $sqlBusq);
		$rs3 = mysql_query($sql3);
		if (!$rs3) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsNotaCred = mysql_num_rows($rs3);
		$row3 = mysql_fetch_assoc($rs3);

		// VALES DE SALIDA
		$sql4 = sprintf("SELECT
			IFNULL(SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					a.ut / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					0
			END)), 0) AS uts_vale,
			
			IFNULL(SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.precio
			END)), 0) AS valor_uts_vale
			
		FROM sa_det_vale_salida_tempario det_orden_temp
			INNER JOIN sa_v_vale_informe_final_tempario a ON (det_orden_temp.id_det_vale_salida_tempario = a.id_det_vale_salida_tempario) %s;", $sqlBusq);
		$rs4 = mysql_query($sql4);
		if (!$rs4) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsValeSal = mysql_num_rows($rs4);
		$row4 = mysql_fetch_assoc($rs4);
		
		$totalMecanicoUts = $row2['uts'] - $row3['uts_dev'] + $row4['uts_vale'];
		$totalMecanicoBs = $row2['valor_uts'] - $row3['valor_uts_dev'] + $row4['valor_uts_vale'];
		
		if ($rowEmpleado['activo'] == 1 || $totalMecanicoBs != 0) {
			if ($totalMecanicoBs != 0) {
				$arrayTecnico[] = array(
					"nombre_mecanico" => $rowEmpleado['nombre_empleado'],
					"total_uts" => $totalMecanicoUts,
					"total_bs" => $totalMecanicoBs);
			}
			
			switch($rowEmpleado['nivel']) {
				case 'AYUDANTE' : $arrayMecanico[$rowEquipo['id_equipo_mecanico']][0] += 1; break;
				case 'PRINCIPIANTE' : $arrayMecanico[$rowEquipo['id_equipo_mecanico']][1] += 1; break;
				case 'NORMAL' : $arrayMecanico[$rowEquipo['id_equipo_mecanico']][1] += 1; break;
				case 'EXPERTO' : $arrayMecanico[$rowEquipo['id_equipo_mecanico']][1] += 1; break;
			}
			$arrayMecanico[$rowEquipo['id_equipo_mecanico']][2] = $rowEquipo['nombre_equipo'];
		}
	}
	
	$totalUtsEquipo = 0;
	$totalBsEquipo = 0;
	if (isset($arrayTecnico)) {
		foreach ($arrayTecnico as $indice => $valor) {
			$totalUtsEquipo += $arrayTecnico[$indice]['total_uts'];
			$totalBsEquipo += $arrayTecnico[$indice]['total_bs'];
		}
	}
	
	$totalTotalUtsEquipos += $totalUtsEquipo;
	$totalTotalBsEquipos += $totalBsEquipo;
	
	$arrayEquipo[] = array(
		$rowEquipo['nombre_equipo'],
		$arrayTecnico,
		$totalUtsEquipo,
		$totalBsEquipo);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// COMPRAS DE REPUESTOS Y ACCESORIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$Result1 = facturacionMovimiento($idEmpresa, $valFecha[0], $valFecha[1], "0", "1,4");
if ($Result1[0] != true && strlen($Result1[1]) > 0) {
	die($Result1[1]); 
} else {
	$arrayMovCompras = $Result1[1];
	$totalNetoClaveMovCompras = $Result1[2];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS DE REPUESTOS POR MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$Result1 = facturacionMovimiento($idEmpresa, $valFecha[0], $valFecha[1], "0", "2,3");
if ($Result1[0] != true && strlen($Result1[1]) > 0) {
	die($Result1[1]); 
} else {
	$arrayMovVentas = $Result1[1];
	$totalNetoClaveMovVentas = $Result1[2];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS DE REPUESTOS POR SERVICIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$Result1 = facturacionMovimiento($idEmpresa, $valFecha[0], $valFecha[1], "1", "2,3,4");
if ($Result1[0] != true && strlen($Result1[1]) > 0) {
	die($Result1[1]); 
} else {
	$arrayMovVentasServ = $Result1[1];
	$totalNetoClaveMovVentasServ = $Result1[2];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode($htmlMsj)), 152, " ", STR_PAD_BOTH),$textColor);
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// TOTAL FACTURACION
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL FACTURACIÓN"), 60, " ", STR_PAD_BOTH),$textColor);

$totalFacturacionPostVenta = $totalProdTaller + $totalProdOtro + array_sum($arrayVentaMost);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 60, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("Facturado")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(utf8_decode("%"), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 60, "-", STR_PAD_BOTH),$textColor);
		
// TOTAL SERVICIOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Servicios"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($totalProdTaller, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round((($totalFacturacionPostVenta > 0) ? $totalProdTaller * 100 / $totalFacturacionPostVenta : 0), 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// TOTAL REPUESTOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Repuestos"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round(array_sum($arrayVentaMost), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round((($totalFacturacionPostVenta > 0) ? array_sum($arrayVentaMost) * 100 / $totalFacturacionPostVenta : 0), 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
		
// TOTAL OTROS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Otros"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($totalProdOtro, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round((($totalFacturacionPostVenta > 0) ? $totalProdOtro * 100 / $totalFacturacionPostVenta : 0), 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 60, "-", STR_PAD_BOTH),$textColor);

// TOTAL
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Facturación:"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($totalFacturacionPostVenta, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION TALLER
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("PRODUCCIÓN TALLER"), 152, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 22, " ", STR_PAD_BOTH),$textColor);
$posX += 110;
if (isset($arrayProdTipoOrden)) {
	foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
		imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode($tipo['nombre']),0,14)), 15, " ", STR_PAD_BOTH),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(strtoupper(utf8_decode("Total")), 16, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,720,$posY,str_pad(utf8_decode("%"), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// MANOS DE OBRA
$porcMO = 0;
if (isset($arrayOperador)) {
	foreach ($arrayOperador as $idOperador => $operador) {
		$porcOperador = ($totalProdTaller > 0) ? ($arrayTotalMOOperador[$idOperador] * 100) / $totalProdTaller : 0;
		$porcMO += $porcOperador;
		
		$posX = 0; $posY += 10;
		imagestring($img,1,$posX,$posY,strtoupper(substr($operador,0,22)),$textColor);
		$posX += 110;
		if (isset($arrayProdTipoOrden)) {
			foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
				imagestring($img,1,$posX,$posY,str_pad(number_format(((isset($arrayMO[$idOperador][$idTipo])) ? $arrayMO[$idOperador][$idTipo] : 0), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				$posX += 75;
			}
		}
		imagestring($img,1,640,$posY,str_pad(number_format(round($arrayTotalMOOperador[$idOperador], 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,720,$posY,str_pad(number_format(round($porcOperador, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE LA MANO DE OBRA
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Subtotal Mano de Obra:"),0,22)),$textColor);
$posX += 110;
$subTotalMo = 0;
if (isset($arrayProdTipoOrden)) {
	foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
		$subTotalMo += $arrayTotalMOTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayTotalMOTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(number_format(round($subTotalMo, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcMO, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// REPUESTOS
$porcRepuestos = 0;
if (isset($arrayTipoArticulo)) {
	foreach ($arrayTipoArticulo as $idTipoArticulo => $tipoArticulo) {
		if ($arrayTotalRepuestoTipo[$idTipoArticulo] != 0) {
			$porcTipoRepuestos = ($totalProdTaller > 0) ? ($arrayTotalRepuestoTipo[$idTipoArticulo] * 100) / $totalProdTaller : 0;
			$porcRepuestos += $porcTipoRepuestos;
			
			$posX = 0; $posY += 10;
			imagestring($img,1,$posX,$posY,strtoupper(substr($tipoArticulo,0,22)),$textColor);
			$posX += 110;
			if (isset($arrayProdTipoOrden)) {
				foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
					imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayRepuesto[$idTipoArticulo][$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					$posX += 75;
				}
			}
			imagestring($img,1,640,$posY,str_pad(number_format(round($arrayTotalRepuestoTipo[$idTipoArticulo], 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,720,$posY,str_pad(number_format(round($porcTipoRepuestos, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
		}
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE REPUESTOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Repuestos:"),0,22)),$textColor);
$posX += 110;
$subTotalRepServ = 0;
if (isset($arrayProdTipoOrden)) {
	foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
		$subTotalRepServ += $arrayTotalRepuestoTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayTotalRepuestoTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(number_format(round($subTotalRepServ, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcRepuestos, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// TOTAL DE DESCUENTO DE REPUESTOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Descuento Repuestos:"),0,22)),$textColor);
$posX += 110;
$totalDescuentoRepServ = 0;
if (isset($arrayProdTipoOrden)) {
	foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
		$totalDescuentoRepServ += $arrayTotalDescuentoRepuestoTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format((-1)*round($arrayTotalDescuentoRepuestoTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
$porcDescuentoRepServ = ($totalProdTaller > 0) ? ($totalDescuentoRepServ * 100) / $totalProdTaller : 0;
imagestring($img,1,640,$posY,str_pad(number_format((-1)*round($totalDescuentoRepServ, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format((-1)*round($porcDescuentoRepServ, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL FINAL DE REPUESTOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Subtotal Repuestos:"),0,22)),$textColor);
$posX += 110;
if (isset($arrayProdTipoOrden)) {
	foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayTotalRepuestoTipoOrden[$idTipo] - $arrayTotalDescuentoRepuestoTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(number_format(round($subTotalRepServ - $totalDescuentoRepServ, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcRepuestos - $porcDescuentoRepServ, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// TOTAL DE NOTAS
if ($rowConfig300['valor'] == 1) {
	$posX = 0; $posY += 10;
	imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Subtotal Notas:"),0,22)),$textColor);
	$posX += 110;
	if (isset($arrayProdTipoOrden)) {
		foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
			imagestring($img,1,$posX,$posY,str_pad(round($arrayTotalNotaTipoOrden[$idTipo], 2), 15, " ", STR_PAD_LEFT),$textColor);
			$posX += 75;
		}
	}
	$porcNota = ($totalProdTaller > 0) ? ($totalNota * 100) / $totalProdTaller : 0;
	imagestring($img,1,640,$posY,str_pad(round($totalNota, 2), 16, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,720,$posY,str_pad(round($porcNota, 2), 8, " ", STR_PAD_LEFT),$textColor);
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL SERVICIOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Total Producción Taller:"),0,22)),$textColor);
$posX += 110;
$totalTipoOrden = 0;
if (isset($arrayProdTipoOrden)) {
	foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
		$totalTipoOrden = $arrayTotalMOTipoOrden[$idTipo] + $arrayTotalRepuestoTipoOrden[$idTipo] - $arrayTotalDescuentoRepuestoTipoOrden[$idTipo];
		$totalTipoOrden += ($rowConfig300['valor'] == 1) ? $arrayTotalNotaTipoOrden[$idTipo] : 0;
		$porcTotalTipoOrden[$idTipo] = ($totalProdTaller > 0) ? ($totalTipoOrden * 100) / $totalProdTaller : 0;
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($totalTipoOrden, 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
$porcTotalProdTaller = $porcMO + $porcRepuestos - $porcDescuentoRepServ;
$porcTotalProdTaller += ($rowConfig300['valor'] == 1) ? $porcNota : 0;
imagestring($img,1,640,$posY,str_pad(number_format(round($totalProdTaller, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcTotalProdTaller, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// PARTICIPACION
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("% Participación"),0,22)),$textColor);
$posX += 110;
$porcentajeTotal = 0;
if (isset($arrayProdTipoOrden)) {
	foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
		$porcentajeTotal += $porcTotalTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($porcTotalTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
$porcTotalProdTaller = $porcMO + $porcRepuestos + $porcNota - $porcDescuentoRepServ;
imagestring($img,1,640,$posY,str_pad(number_format(round($porcentajeTotal, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode($htmlMsj)), 152, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION OTRO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("PRODUCCIÓN OTRO"), 152, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 22, " ", STR_PAD_BOTH),$textColor);
$posX += 110;
if (isset($arrayOtroProdTipoOrden)) {
	foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
		imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode($tipo['nombre']),0,14)), 15, " ", STR_PAD_BOTH),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(strtoupper(utf8_decode("Total")), 16, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,720,$posY,str_pad(utf8_decode("%"), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// MANOS DE OBRA
$porcMO = 0;
if (isset($arrayOtroOperador)) {
	foreach ($arrayOtroOperador as $idOperador => $operador) {
		$porcOperador = ($totalProdOtro > 0) ? ($arrayOtroTotalMOOperador[$idOperador] * 100) / $totalProdOtro : 0;
		$porcMO += $porcOperador;
		
		$posX = 0; $posY += 10;
		imagestring($img,1,$posX,$posY,strtoupper(substr($operador,0,22)),$textColor);
		$posX += 110;
		if (isset($arrayOtroProdTipoOrden)) {
			foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
				imagestring($img,1,$posX,$posY,str_pad(number_format(((isset($arrayOtroMO[$idOperador][$idTipo])) ? $arrayOtroMO[$idOperador][$idTipo] : 0), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				$posX += 75;
			}
		}
		imagestring($img,1,640,$posY,str_pad(number_format(round($arrayOtroTotalMOOperador[$idOperador], 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,720,$posY,str_pad(number_format(round($porcOperador, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE LA MANO DE OBRA
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Subtotal Mano de Obra:"),0,22)),$textColor);
$posX += 110;
$subTotalMoOtro = 0;
if (isset($arrayOtroProdTipoOrden)) {
	foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
		$subTotalMoOtro += $arrayOtroTotalMOTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayOtroTotalMOTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(number_format(round($subTotalMoOtro, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcMO, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// REPUESTOS
$porcRepuestos = 0;
if (isset($arrayOtroTipoArticulo)) {
	foreach ($arrayOtroTipoArticulo as $idTipoArticulo => $tipoArticulo) {
		if ($arrayOtroTotalRepuestoTipo[$idTipoArticulo] != 0) {
			$porcTipoRepuestos = ($totalProdOtro > 0) ? ($arrayOtroTotalRepuestoTipo[$idTipoArticulo] * 100) / $totalProdOtro : 0;
			$porcRepuestos += $porcTipoRepuestos;
			
			$posX = 0; $posY += 10;
			imagestring($img,1,$posX,$posY,strtoupper(substr($tipoArticulo,0,22)),$textColor);
			$posX += 110;
			if (isset($arrayOtroProdTipoOrden)) {
				foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
					imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayOtroRepuesto[$idTipoArticulo][$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					$posX += 75;
				}
			}
			imagestring($img,1,640,$posY,str_pad(number_format(round($arrayOtroTotalRepuestoTipo[$idTipoArticulo], 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,720,$posY,str_pad(number_format(round($porcTipoRepuestos, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
		}
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE REPUESTOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Repuestos:"),0,22)),$textColor);
$posX += 110;
$subTotalOtroRepServ = 0;
if (isset($arrayOtroProdTipoOrden)) {
	foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
		$subTotalOtroRepServ += $arrayOtroTotalRepuestoTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayOtroTotalRepuestoTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(number_format(round($subTotalOtroRepServ, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcRepuestos, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// TOTAL DE DESCUENTO DE REPUESTOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Descuento Repuestos:"),0,22)),$textColor);
$posX += 110;
$totalDescuentoOtroRepServ = 0;
if (isset($arrayOtroProdTipoOrden)) {
	foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
		$totalDescuentoOtroRepServ += $arrayOtroTotalDescuentoRepuestoTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format((-1)*round($arrayOtroTotalDescuentoRepuestoTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
$porcDescuentoRepServ = ($totalProdOtro > 0) ? ($totalDescuentoOtroRepServ * 100) / $totalProdOtro : 0;
imagestring($img,1,640,$posY,str_pad(number_format((-1)*round($totalDescuentoOtroRepServ, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format((-1)*round($porcDescuentoRepServ, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL FINAL DE REPUESTOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Subtotal Repuestos:"),0,22)),$textColor);
$posX += 110;
if (isset($arrayOtroProdTipoOrden)) {
	foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayOtroTotalRepuestoTipoOrden[$idTipo] - $arrayOtroTotalDescuentoRepuestoTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(number_format(round($subTotalOtroRepServ - $totalDescuentoOtroRepServ, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcRepuestos - $porcDescuentoRepServ, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// TOTAL DE NOTAS
if ($rowConfig300['valor'] == 1) {
	$posX = 0; $posY += 10;
	imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Subtotal Notas:"),0,22)),$textColor);
	$posX += 110;
	if (isset($arrayOtroProdTipoOrden)) {
		foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
			imagestring($img,1,$posX,$posY,str_pad(round($arrayOtroTotalNotaTipoOrden[$idTipo], 2), 15, " ", STR_PAD_LEFT),$textColor);
			$posX += 75;
		}
	}
	$porcNota = ($totalProdOtro > 0) ? ($totalNota * 100) / $totalProdOtro : 0;
	imagestring($img,1,640,$posY,str_pad(round($totalNota, 2), 16, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,720,$posY,str_pad(round($porcNota, 2), 8, " ", STR_PAD_LEFT),$textColor);
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL SERVICIOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Total Producción Taller:"),0,22)),$textColor);
$posX += 110;
$totalTipoOrden = 0;
if (isset($arrayOtroProdTipoOrden)) {
	foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
		$totalTipoOrden = $arrayOtroTotalMOTipoOrden[$idTipo] + $arrayOtroTotalRepuestoTipoOrden[$idTipo] - $arrayOtroTotalDescuentoRepuestoTipoOrden[$idTipo];
		$totalTipoOrden += ($rowConfig300['valor'] == 1) ? $arrayOtroTotalNotaTipoOrden[$idTipo] : 0;
		$porcTotalTipoOrden[$idTipo] = ($totalProdOtro > 0) ? ($totalTipoOrden * 100) / $totalProdOtro : 0;
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($totalTipoOrden, 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
$porcTotalProdTaller = $porcMO + $porcRepuestos - $porcDescuentoRepServ;
$porcTotalProdTaller += ($rowConfig300['valor'] == 1) ? $porcNota : 0;
imagestring($img,1,640,$posY,str_pad(number_format(round($totalProdOtro, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcTotalProdTaller, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// PARTICIPACION
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("% Participación"),0,22)),$textColor);
$posX += 110;
$porcentajeTotal = 0;
if (isset($arrayOtroProdTipoOrden)) {
	foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
		$porcentajeTotal += $porcTotalTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($porcTotalTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
$porcTotalProdTaller = $porcMO + $porcRepuestos + $porcNota - $porcDescuentoRepServ;
imagestring($img,1,640,$posY,str_pad(number_format(round($porcentajeTotal, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION REPUESTOS MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("PRODUCCIÓN REPUESTOS MOSTRADOR"), 100, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("Contado")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Crédito")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("Total")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,460,$posY,str_pad(utf8_decode("%"), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

// VENTAS ITINERANTES
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Ventas Itinerantes"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round(0, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round(0, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(0, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,460,$posY,str_pad(number_format(round(0, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// MOSTRADOR PUBLICO
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Mostrador Público"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($arrayVentaMost[0], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round($arrayVentaMost[1], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(array_sum($arrayVentaMost), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,460,$posY,str_pad(number_format(round(((array_sum($arrayVentaMost) > 0) ? array_sum($arrayVentaMost) * 100 / (array_sum($arrayVentaMost)) : 0), 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

// TOTAL REPUESTOS MOSTRADOR
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Producción Repuestos Mostrador:"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($arrayVentaMost[0], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round($arrayVentaMost[1], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(array_sum($arrayVentaMost), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,460,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

// PARTICIPACION
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("% Participación"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round(((array_sum($arrayVentaMost) > 0) ? $arrayVentaMost[0] * 100 / array_sum($arrayVentaMost) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round(((array_sum($arrayVentaMost) > 0) ? $arrayVentaMost[1] * 100 / array_sum($arrayVentaMost) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode($htmlMsj)), 152, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// MARGEN DE REPUESTOS POR SERVICIOS Y MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("MARGEN DE REPUESTOS POR SERVICIOS Y MOSTRADOR"), 92, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("Costo")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Utl. Bruta")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("%Utl. Bruta")), 20, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// REPUESTOS POR SERVICIOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Repuestos por Servicios"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($arrayCostoRepServ[0], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round((($subTotalRepServ - $totalDescuentoRepServ) - $arrayCostoRepServ[0]), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(((($subTotalRepServ - $totalDescuentoRepServ) > 0) ? ((($subTotalRepServ - $totalDescuentoRepServ) - $arrayCostoRepServ[0]) * 100) / ($subTotalRepServ - $totalDescuentoRepServ) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

// REPUESTOS POR MOSTRADOR
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Repuestos por Mostrador"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($arrayCostoRepMost[0], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round((array_sum($arrayVentaMost) - $arrayCostoRepMost[0]), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(((array_sum($arrayVentaMost) > 0) ? ((array_sum($arrayVentaMost) - $arrayCostoRepMost[0]) * 100) / array_sum($arrayVentaMost) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ANÁLISIS DE INVENTARIO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("ANÁLISIS DE INVENTARIO"), 152, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clasif.")), 12, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,60,$posY,str_pad(strtoupper(utf8_decode("Nro. Items")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,130,$posY,str_pad(strtoupper(utf8_decode("% Items")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(strtoupper(utf8_decode("Existencia")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,270,$posY,str_pad(strtoupper(utf8_decode("% Existencia")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,340,$posY,str_pad(strtoupper(utf8_decode("Importe ".cAbrevMoneda)), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,410,$posY,str_pad(strtoupper(utf8_decode("% Importe ".cAbrevMoneda)), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,480,$posY,str_pad(strtoupper(utf8_decode("Prom. Ventas ".cAbrevMoneda)), 16, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,560,$posY,str_pad(strtoupper(utf8_decode("Meses Exist.")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,660,$posY,str_pad(strtoupper(utf8_decode("Exist. / Nro. Items")), 20, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayAnalisisInv)) {
	foreach ($arrayAnalisisInv as $indice => $valor) {
		$arrayFila = array(
			$arrayAnalisisInv[$indice][0],
			round($arrayAnalisisInv[$indice][1],2),
			round((($totalCantArt > 0) ? ($arrayAnalisisInv[$indice][1] * 100 / $totalCantArt) : 0),2),
			round($arrayAnalisisInv[$indice][2],2),
			round((($totalExistArt > 0) ? ($arrayAnalisisInv[$indice][2] * 100 / $totalExistArt) : 0),2),
			round($arrayAnalisisInv[$indice][3],2),
			round((($totalCostoInv > 0) ? ($arrayAnalisisInv[$indice][3] * 100 / $totalCostoInv) : 0),2),
			round($arrayAnalisisInv[$indice][4],2),
			round($arrayAnalisisInv[$indice][3],2) / round($arrayAnalisisInv[$indice][4],2),
			round(($arrayAnalisisInv[$indice][2] / $arrayAnalisisInv[$indice][1]),2));
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,12)), 12, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,60,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,130,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,200,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,270,$posY,str_pad(number_format($arrayFila[4], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,340,$posY,str_pad(number_format($arrayFila[5], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,410,$posY,str_pad(number_format($arrayFila[6], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,480,$posY,str_pad(number_format($arrayFila[7], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,560,$posY,str_pad(number_format($arrayFila[8], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,660,$posY,str_pad(number_format($arrayFila[9], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Totales:"),0,12)),$textColor);
imagestring($img,1,60,$posY,str_pad(number_format(round($totalCantArt, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,130,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,200,$posY,str_pad(number_format(round($totalExistArt, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,270,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,340,$posY,str_pad(number_format(round($totalCostoInv, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,410,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,480,$posY,str_pad(number_format(round($totalPromVentas, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,560,$posY,str_pad(number_format(round((($totalPromVentas > 0) ? ($totalCostoInv / $totalPromVentas) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,660,$posY,str_pad(number_format(round($totalExistNroArt, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// CANTIDAD DE ITEMS Y ARTÍCULOS VENDIDOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("CANTIDAD DE ITEMS Y ARTÍCULOS VENDIDOS"), 92, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clasif.")), 12, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,60,$posY,str_pad(strtoupper(utf8_decode("Nro. Items Vendidos")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("% Items Vendidos")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Cant. Art. Vendidos")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("% Art. Vendidos")), 20, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayCantArtVend)) {
	foreach ($arrayCantArtVend as $indice => $valor) {
		$arrayFila = NULL;
		$arrayFila[] = $arrayCantArtVend[$indice][0];
		$arrayFila[] = round($arrayCantArtVend[$indice][1],2);
		$arrayFila[] = round((($totalNroArt > 0) ? ($arrayCantArtVend[$indice][1] * 100 / $totalNroArt) : 0),2);
		$arrayFila[] = round($arrayCantArtVend[$indice][2],2);
		$arrayFila[] = round((($totalCantArtVend > 0) ? ($arrayCantArtVend[$indice][2] * 100 / $totalCantArtVend) : 0),2);
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,12)), 12, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,60,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,160,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[4], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Totales:"),0,12)),$textColor);
imagestring($img,1,60,$posY,str_pad(number_format($totalNroArt, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(100, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalCantArtVend, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(100, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// INDICADORES DE TALLER
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("INDICADORES DE TALLER"), 114, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Indicador")), 52, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Unidad")), 62, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);

// MANO DE OBRA
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Mano de Obra"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($subTotalMo, 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);

// DIAS HABILES
$diaHabiles = evaluaFecha(diasHabiles('01-'.$valFecha[0].'-'.$valFecha[1], ultimoDia($valFecha[0],$valFecha[1]).'-'.$valFecha[0].'-'.$valFecha[1]));
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Días Hábiles Mes"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($diaHabiles - $totalRowsDiasFeriados, 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);

if (isset($arrayMecanico)) {
	foreach ($arrayMecanico as $indice => $valor) {
		// NUMERO DE TECNICOS
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Nro. Técnicos ".$arrayMecanico[$indice][2]),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayMecanico[$indice][1], 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);
		
		// HORAS DISPONIBLE VENTA TECNICOS
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. Disp. Venta Técnicos"),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayMecanico[$indice][1] * 7.1 * ($diaHabiles - $totalRowsDiasFeriados), 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);
		
		// HORAS PROMEDIO TECNICOS
		$posY += 10;
		$HrsPromTec = ($arrayMecanico[$indice][1] > 0) ? ($arrayMecanico[$indice][1] * 7.1 * ($diaHabiles - $totalRowsDiasFeriados)) / $arrayMecanico[$indice][1] : 0;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. Prom / Técnicos"),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($HrsPromTec, 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);
		
		// NUMERO DE TECNICOS EN FORMACION
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Nro. Técnicos en Formación ".$arrayMecanico[$indice][2]),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayMecanico[$indice][0], 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);
		
		// HORAS DISPONIBLE VENTA TECNICOS EN FORMACION
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. Disp. Venta Técnicos en Formación"),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayMecanico[$indice][0] * 3.57 * ($diaHabiles - $totalRowsDiasFeriados), 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);
		
		// HORAS PROMEDIO TECNICOS EN FORMACION
		$posY += 10;
		$HrsPromTecFormacion = ($arrayMecanico[$indice][0] > 0) ? ($arrayMecanico[$indice][0] * 3.57 * ($diaHabiles - $totalRowsDiasFeriados)) / $arrayMecanico[$indice][0] : 0;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. Prom / Técnicos en Formación"),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($HrsPromTecFormacion, 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);

// ENTRADA DE VEHICULOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Entrada de Vehículos ".$arrayValeRecepcion[1][0]),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($arrayValeRecepcion[1][1], 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);

// ENTRADA DE VEHICULOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Entrada de Vehículos ".$arrayValeRecepcion[2][0]." y ".$arrayValeRecepcion[3][0]),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($arrayValeRecepcion[2][1] + $arrayValeRecepcion[3][1], 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);


$posY += 10;
imagestring($img,1,260,$posY,str_pad("CANT. FALLAS", 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,365,$posY,str_pad("CANT. UT'S", 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,470,$posY,str_pad("CANT. ORDENES", 20, " ", STR_PAD_BOTH),$textColor);
// ORDENES DE SERVICIOS ABIERTAS
if (isset($arrayTipoOrden)) {
	foreach ($arrayTipoOrden as $indice => $valor) {
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("O/R Abiertas ".$arrayTipoOrden[$indice]['nombre_tipo_orden']),0,52)),$textColor);
		
		imagestring($img,1,260,$posY,str_pad(number_format($arrayTipoOrden[$indice]['cantidad_fallas_abiertas'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,365,$posY,str_pad(number_format($arrayTipoOrden[$indice]['cantidad_uts_abiertas'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,470,$posY,str_pad(number_format($arrayTipoOrden[$indice]['cantidad_abiertas'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE ORDENES ABIERTAS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total O/R Abiertas"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalFallaTipoOrdenAbierta, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,365,$posY,str_pad(number_format($totalUtsTipoOrdenAbierta, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,470,$posY,str_pad(number_format($totalTipoOrdenAbierta, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);


$posY += 10;
imagestring($img,1,260,$posY,str_pad("CANT. FALLAS", 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,365,$posY,str_pad("CANT. UT'S", 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,470,$posY,str_pad("CANT. ORDENES", 20, " ", STR_PAD_BOTH),$textColor);
// ORDENES DE SERVICIOS CERRADAS
if (isset($arrayTipoOrden)) {
	foreach ($arrayTipoOrden as $indice => $valor) {
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("O/R Cerradas ".$arrayTipoOrden[$indice]['nombre_tipo_orden']),0,52)),$textColor);
		
		imagestring($img,1,260,$posY,str_pad(number_format($arrayTipoOrden[$indice]['cantidad_fallas_cerradas'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,365,$posY,str_pad(number_format($arrayTipoOrden[$indice]['cantidad_uts_cerradas'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,470,$posY,str_pad(number_format($arrayTipoOrden[$indice]['cantidad_cerradas'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE ORDENES CERRADAS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total O/R Cerradas"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalFallaTipoOrdenCerrada, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,365,$posY,str_pad(number_format($totalUtsTipoOrdenCerrada, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,470,$posY,str_pad(number_format($totalTipoOrdenCerrada, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);

// REPUESTOS POR SERVICIOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Rptos. Servicios"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($subTotalRepServ - $totalDescuentoRepServ, 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);

// BS REPUESTOS ENTRE ORDENES
$posY += 10;
$totalTipoOrdenCerrada = ($totalTipoOrdenCerrada > 0) ? $totalTipoOrdenCerrada : 1;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode(cAbrevMoneda." Rptos / OR"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(($subTotalRepServ - $totalDescuentoRepServ) / $totalTipoOrdenCerrada, 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);

// HORAS ENTRE ORDENES
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. / OR"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalTotalUtsEquipos / $totalTipoOrdenCerrada, 2, ".", ","), 62, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode($htmlMsj)), 152, " ", STR_PAD_BOTH),$textColor);
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN ASESORES DE SERVICIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("FACTURACIÓN ASESORES DE SERVICIOS"), 152, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Asesor")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("Cant. O/R Cerradas")), 18, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,250,$posY,str_pad(strtoupper(utf8_decode("UT'S")), 18, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,340,$posY,str_pad(strtoupper(utf8_decode("M/Obra")), 18, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,430,$posY,str_pad(strtoupper(utf8_decode("Rptos.")), 18, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,520,$posY,str_pad(strtoupper(utf8_decode("T.O.T.")), 18, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,610,$posY,str_pad(strtoupper(utf8_decode("Total")), 22, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,720,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayVentaAsesor)) {
	foreach ($arrayVentaAsesor as $indice => $valor) {
		$porcAsesor = ($arrayVentaAsesor[$indice]['total_asesor'] * 100) / $totalVentaAsesores;
		
		$arrayFila = array(
			utf8_encode($arrayVentaAsesor[$indice]['nombre_asesor']),
			round($arrayVentaAsesor[$indice]['cantidad_ordenes'],2),
			round($arrayVentaAsesor[$indice]['total_ut'],2),
			round($arrayVentaAsesor[$indice]['total_mo'],2),
			round($arrayVentaAsesor[$indice]['total_repuestos'],2),
			round($arrayVentaAsesor[$indice]['total_tot'],2),
			round($arrayVentaAsesor[$indice]['total_asesor'],2),
			round($porcAsesor,2));
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr($arrayFila[0],0,32)),$textColor);
		imagestring($img,1,160,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,250,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,340,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,430,$posY,str_pad(number_format($arrayFila[4], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,520,$posY,str_pad(number_format($arrayFila[5], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,610,$posY,str_pad(number_format($arrayFila[6], 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,720,$posY,str_pad(number_format($arrayFila[7], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
		
		$totalVentaOrden += $arrayVentaAsesor[$indice]['cantidad_ordenes'];
		$totalVentaUT += $arrayVentaAsesor[$indice]['total_ut'];
		$totalVentaMO += $arrayVentaAsesor[$indice]['total_mo'];
		$totalVentaRepuestos += $arrayVentaAsesor[$indice]['total_repuestos'];
		$totalVentaTot += $arrayVentaAsesor[$indice]['total_tot'];
		$porcTotalAsesor += $porcAsesor;
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Facturación Asesores:"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($totalVentaOrden, 2), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(number_format(round($totalVentaUT, 2), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,340,$posY,str_pad(number_format(round($totalVentaMO, 2), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,430,$posY,str_pad(number_format(round($totalVentaRepuestos, 2), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,520,$posY,str_pad(number_format(round($totalVentaTot, 2), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,610,$posY,str_pad(number_format(round($totalVentaAsesores, 2), 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcTotalAsesor, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN VENDEDORES DE REPUESTOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("FACTURACIÓN VENDEDORES DE REPUESTOS"), 100, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Vendedor")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("Contado")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Crédito")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("Total")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,460,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayVentaVendedor)) {
	foreach ($arrayVentaVendedor as $indice => $valor) {
		$porcVendedor = ($arrayVentaVendedor[$indice][8] * 100) / $totalVentaVendedores;
		
		$arrayFila = array(
			$arrayVentaVendedor[$indice][1],
			round($arrayVentaVendedor[$indice][6],2),
			round($arrayVentaVendedor[$indice][7],2),
			round($arrayVentaVendedor[$indice][8],2),
			round($porcVendedor,2));
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr($arrayFila[0],0,32)),$textColor);
		imagestring($img,1,160,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad(number_format($arrayFila[4], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
		
		$totalVentaContado += $arrayVentaVendedor[$indice][6];
		$totalVentaCredito += $arrayVentaVendedor[$indice][7];
		$porcTotalVendedor += $porcVendedor;
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Facturación Vendedores:"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($totalVentaContado, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round($totalVentaCredito, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round($totalVentaVendedores, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,460,$posY,str_pad(number_format(round($porcTotalVendedor, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode($htmlMsj)), 152, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN TÉCNICOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("FACTURACIÓN TÉCNICOS"), 100, " ", STR_PAD_BOTH),$textColor);

if (isset($arrayEquipo)) {
	foreach ($arrayEquipo as $indice => $valor) {
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad($arrayEquipo[$indice][0], 100, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Técnicos ".$arrayEquipo[$indice][0])), 52, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("UT'S")), 20, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode(cAbrevMoneda)), 20, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,460,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
		
		$arrayTecnico = $arrayEquipo[$indice][1];
		$porcTotalEquipo = 0;
		$arrayMec = NULL;
		if (isset($arrayTecnico)) {
			foreach ($arrayTecnico as $indice2 => $valor2) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$porcMecanico = ($totalTotalBsEquipos > 0) ? ($arrayTecnico[$indice2]['total_bs'] * 100) / $totalTotalBsEquipos : 0;
		
				$arrayFila = array(
					$valor2['nombre_mecanico'],
					round($valor2['total_uts'],2),
					round($valor2['total_bs'],2),
					round($porcMecanico,2));
				
				$posY += 10;
				imagestring($img,1,0,$posY,strtoupper(substr($arrayFila[0],0,52)),$textColor);
				imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,460,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
				
				$porcTotalEquipo += $porcMecanico;
			}
		}
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Facturación ".$arrayEquipo[$indice][0].":"),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayEquipo[$indice][2], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayEquipo[$indice][3], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad(number_format($porcTotalEquipo, 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalFactTecnicos[0] += $arrayEquipo[$indice][2];
		$arrayTotalFactTecnicos[1] += $arrayEquipo[$indice][3];
		$arrayTotalFactTecnicos[2] += $porcTotalEquipo;
	}
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Facturación Técnicos"),0,52)),$textColor);
	imagestring($img,1,260,$posY,str_pad(number_format($arrayTotalFactTecnicos[0], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,360,$posY,str_pad(number_format($arrayTotalFactTecnicos[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,460,$posY,str_pad(number_format($arrayTotalFactTecnicos[2], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// COMPRAS DE REPUESTOS Y ACCESORIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("COMPRAS DE REPUESTOS Y ACCESORIOS"), 80, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clave de Movimiento")), 52, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Importe ".cAbrevMoneda)), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayMovCompras)) {
	foreach ($arrayMovCompras as $indice => $valor) {
		if (in_array($arrayMovCompras[$indice]['id_tipo_movimiento'], array(1,3))) {
			$arrayMovCompras[$indice]['total_neto'] = $arrayMovCompras[$indice]['total_neto'];
		} else if (in_array($arrayMovCompras[$indice]['id_tipo_movimiento'], array(2,4))) {
			switch($arrayMovCompras[$indice]['tipo_documento_movimiento']) { // 1 = Vale Entrada / Salida, 2 = Nota Credito
				case 1 : $arrayMovCompras[$indice]['total_neto'] = $arrayMovCompras[$indice]['total_neto']; break;
				case 2 : $arrayMovCompras[$indice]['total_neto'] = (-1) * $arrayMovCompras[$indice]['total_neto']; break;
			}
		}
		
		$arrayFila = array(
			utf8_encode($arrayMovCompras[$indice]['clave_movimiento']),
			round($arrayMovCompras[$indice]['total_neto'],2),
			round(($arrayMovCompras[$indice]['total_neto'] * 100) / $totalNetoClaveMovCompras,2));
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode($arrayFila[0]),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Compras Repuestos y Accesorios:"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalNetoClaveMovCompras, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(100, 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode($htmlMsj)), 152, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS DE REPUESTOS POR MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS DE REPUESTOS POR MOSTRADOR"), 80, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clave de Movimiento")), 52, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Importe ".cAbrevMoneda)), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayMovVentas)) {
	foreach ($arrayMovVentas as $indice => $valor) {
		if (in_array($arrayMovVentas[$indice]['id_tipo_movimiento'], array(1,3))) {
			$arrayMovVentas[$indice]['total_neto'] = $arrayMovVentas[$indice]['total_neto'];
		} else if (in_array($arrayMovVentas[$indice]['id_tipo_movimiento'], array(2,4))) {
			switch($arrayMovVentas[$indice]['tipo_documento_movimiento']) { // 1 = Vale Entrada / Salida, 2 = Nota Credito
				case 1 : $arrayMovVentas[$indice]['total_neto'] = $arrayMovVentas[$indice]['total_neto']; break;
				case 2 : $arrayMovVentas[$indice]['total_neto'] = (-1) * $arrayMovVentas[$indice]['total_neto']; break;
			}
		}
		
		$arrayFila = array(
			utf8_encode($arrayMovVentas[$indice]['clave_movimiento']),
			round($arrayMovVentas[$indice]['total_neto'],2),
			round(($arrayMovVentas[$indice]['total_neto'] * 100) / $totalNetoClaveMovVentas,2));
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode($arrayFila[0]),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Ventas Repuestos y Accesorios:"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalNetoClaveMovVentas, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(100, 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS DE REPUESTOS POR SERVICIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS DE REPUESTOS POR SERVICIOS"), 80, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clave de Movimiento")), 52, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Importe ".cAbrevMoneda)), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayMovVentasServ)) {
	foreach ($arrayMovVentasServ as $indice => $valor) {
		if (in_array($arrayMovVentasServ[$indice]['id_tipo_movimiento'], array(1,3))) {
			$arrayMovVentasServ[$indice]['total_neto'] = $arrayMovVentasServ[$indice]['total_neto'];
		} else if (in_array($arrayMovVentasServ[$indice]['id_tipo_movimiento'], array(2,4))) {
			switch($arrayMovVentasServ[$indice]['tipo_documento_movimiento']) { // 1 = Vale Entrada / Salida, 2 = Nota Credito
				case 1 : $arrayMovVentasServ[$indice]['total_neto'] = $arrayMovVentasServ[$indice]['total_neto']; break;
				case 2 : $arrayMovVentasServ[$indice]['total_neto'] = (-1) * $arrayMovVentasServ[$indice]['total_neto']; break;
			}
		}
		
		$arrayFila = array(
			utf8_encode($arrayMovVentasServ[$indice]['clave_movimiento']),
			round($arrayMovVentasServ[$indice]['total_neto'],2),
			round(($arrayMovVentasServ[$indice]['total_neto'] * 100) / $totalNetoClaveMovVentasServ,2));
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode($arrayFila[0]),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Ventas Repuestos y Accesorios por Servicios:"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalNetoClaveMovVentasServ, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(100, 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		// CABECERA DEL DOCUMENTO 
		if ($idEmpresa != "") {
			if (strlen($rowEmp['logo_familia']) > 5) {
				$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
			}
			
			$pdf->SetY(15);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',5);
			$pdf->SetX(88);
			$pdf->Cell(200,9,$rowEmp['nombre_empresa'],0,2,'L');
			
			if (strlen($rowEmp['rif']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_encode($spanRIF.": ".$rowEmp['rif']),0,2,'L');
			}
			if (strlen($rowEmp['direccion']) > 1) {
				(strlen($rowEmp['telefono1']) > 1) ? $arrayTelefono[] = $rowEmp['telefono1'] : "";
				(strlen($rowEmp['telefono2']) > 1) ? $arrayTelefono[] = $rowEmp['telefono2'] : "";
				
				$pdf->SetX(88);
				$pdf->Cell(100,9,$rowEmp['direccion'].". ".((isset($arrayTelefono)) ? "Telf.: ".implode(" / ", $arrayTelefono) : ""),0,2,'L');
			}
			if (strlen($rowEmp['web']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_encode($rowEmp['web']),0,0,'L');
				$pdf->Ln();
			}
		}
		//$pdf->SetY(-20);
		
		$pdf->Image($valor, 15, 60, 760, 520);
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
	}
}
?>