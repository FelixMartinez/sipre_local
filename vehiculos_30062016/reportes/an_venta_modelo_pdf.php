<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/
$maxRows = 26;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.anulada LIKE 'NO'");

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
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version = %s",
		valTpDato($valCadBusq[5], "int"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
	OR vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.placa LIKE %s
	OR cxp_fact.numero_factura_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

$query = sprintf("SELECT DISTINCT
	vw_iv_modelo.id_uni_bas,
	vw_iv_modelo.nom_uni_bas,
	CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo
FROM an_unidad_fisica uni_fis
	INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
	INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
	LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
	LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
	INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (uni_fis.id_unidad_fisica = cxc_fact_det_vehic.id_unidad_fisica)
	INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_vehic.id_factura = cxc_fact.idFactura)
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version) ASC", $sqlBusq);
$rs = mysql_query($query, $conex) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
		valTpDato($row['id_uni_bas'], "int"));
	
	$queryDetalle = sprintf("SELECT
		uni_fis.id_unidad_fisica,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		color_ext.nom_color AS color_externo1,
		color_int.nom_color AS color_interno1,
		(CASE
			WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
				cxp_fact.fecha_origen
			WHEN (cxp_fact.fecha_origen IS NULL) THEN
				an_ve.fecha
		END) AS fecha_origen,
		(CASE
			WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
				TO_DAYS(cxc_fact.fechaRegistroFactura) - TO_DAYS(cxp_fact.fecha_origen)
			WHEN (cxp_fact.fecha_origen IS NULL) THEN
				TO_DAYS(cxc_fact.fechaRegistroFactura) - TO_DAYS(an_ve.fecha)
		END) AS dias_inventario,
		uni_fis.estado_venta,
		alm.nom_almacen,
		cxp_fact.numero_factura_proveedor,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.numeroFactura,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		vw_pg_empleado.nombre_empleado,
		cxc_fact_det_vehic.precio_unitario,
		cxc_fact_det_vehic.costo_compra AS costo_unitario,
		uni_fis.costo_compra,
		uni_fis.precio_compra,
		uni_fis.costo_agregado,
		uni_fis.costo_depreciado,
		uni_fis.costo_trade_in,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (uni_fis.id_unidad_fisica = cxc_fact_det_vehic.id_unidad_fisica)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_vehic.id_factura = cxc_fact.idFactura)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado)
		LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
			AND cxp_fact.fecha_origen IS NULL
			AND an_ve.tipo_vale_entrada = 1)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $sqlBusq2);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	$arrayFila = NULL;
	$subTotalPrecio = 0;
	$subTotalCosto = 0;
	$arrayFila[] = array(
		'vehiculo' => $row['vehiculo']);
	while($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		if (strlen($rowDetalle['id_unidad_fisica']) > 0) {
			$arrayFila[] = array(
				'id_unidad_fisica' => $rowDetalle['id_unidad_fisica'],
				'serial_carroceria' => $rowDetalle['serial_carroceria'],
				'color_externo1' => $rowDetalle['color_externo1'],
				'fecha_origen' => $rowDetalle['fecha_origen'],
				'fechaRegistroFactura' => $rowDetalle['fechaRegistroFactura'],
				'numeroFactura' => $rowDetalle['numeroFactura'],
				'nombre_empresa' => $rowDetalle['nombre_empresa'],
				'numero_factura_proveedor' => $rowDetalle['numero_factura_proveedor']);
		}
		
		if (strlen($rowDetalle['serial_motor']) > 0) {
			$arrayFila[] = array(
				'serial_motor' => $rowDetalle['serial_motor'],
				'dias_inventario' => $rowDetalle['dias_inventario'],
				'nombre_cliente' => $rowDetalle['nombre_cliente'],
				'precio_unitario' => $rowDetalle['precio_unitario']);
		}
		
		if (strlen($rowDetalle['placa']) > 0 || strlen($rowDetalle['costo_unitario'])) {
			$arrayFila[] = array(
				'placa' => $rowDetalle['placa'],
				'nombre_empleado' => $rowDetalle['nombre_empleado'],
				'costo_unitario' => $rowDetalle['costo_unitario']);
		}
		
		$subTotalPrecio += $rowDetalle['precio_unitario'];
		$subTotalCosto += $rowDetalle['costo_unitario'];
	}
	$subtotalMontoUtilidad = $subTotalPrecio - $subTotalCosto;
	$subtotalPorcUtilidad = $subtotalMontoUtilidad * 100 / $subTotalPrecio;
	
	$arrayFila[] = array(
		'linea' => "-");
	$arrayFila[] = array(
		'cant_items' => $totalRowsDetalle,
		'subtotal_precio' => $subTotalPrecio);
	$arrayFila[] = array(
		'subtotal_costo' => $subTotalCosto);
	$arrayFila[] = array(
		'linea_subtotal_utilidad' => "-");
	$arrayFila[] = array(
		'monto_utilidad' => $subtotalMontoUtilidad,
		'porcentaje_utilidad' => $subtotalPorcUtilidad);
	$arrayFila[] = array(
		'doble_linea' => "=");
	
	$arrayTotalFinal[12] += $totalRowsDetalle;
	$arrayTotalFinal[13] += $subTotalPrecio;
	$arrayTotalFinal[14] += $subTotalCosto;
	
	if ($contFila == $totalRows) {
		$totalMontoUtilidad = $arrayTotalFinal[13] - $arrayTotalFinal[14];
		$totalPorcUtilidad = $totalMontoUtilidad * 100 / $arrayTotalFinal[13];
		
		$arrayFila[] = array(
			'total_cant_items' => $arrayTotalFinal[12],
			'total_subtotal_precio' => $arrayTotalFinal[13]);
		
		$arrayFila[] = array(
			'total_subtotal_costo' => $arrayTotalFinal[14]);
			
		$arrayFila[] = array(
			'linea_total_utilidad' => "-");
		
		$arrayFila[] = array(
			'total_monto_utilidad' => $totalMontoUtilidad,
			'total_porcentaje_utilidad' => $totalPorcUtilidad);
	}
	
	$contFila2 = 0;
	if (isset($arrayFila)) {
		foreach ($arrayFila as $indice => $valor) {
			$contFilaY++;
			
			if (fmod($contFilaY, $maxRows) == 1) {
				$img = @imagecreate(570, 390) or die("No se puede crear la imagen");
				
				// ESTABLECIENDO LOS COLORES DE LA PALETA
				$backgroundColor = imagecolorallocate($img, 255, 255, 255);
				$textColor = imagecolorallocate($img, 0, 0, 0);
				
				$posY = 0;
				imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS POR MODELO"), 114, " ", STR_PAD_BOTH),$textColor);
				
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad(utf8_decode(""), 6, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,35,$posY,str_pad(utf8_decode("NRO."), 8, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,80,$posY,str_pad(strtoupper(utf8_decode($spanSerialCarroceria)), 20, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,185,$posY,str_pad(utf8_decode("COLOR"), 14, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,260,$posY,str_pad(utf8_decode("FECHA ING."), 10, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,315,$posY,str_pad(utf8_decode("FECHA VENT"), 10, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,370,$posY,str_pad(utf8_decode("NRO FACT"), 8, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,415,$posY,str_pad(utf8_decode("EMPRESA"), 16, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,500,$posY,str_pad(utf8_decode("FACT COMP"), 14, " ", STR_PAD_BOTH),$textColor);
				$posY += 10;
				imagestring($img,1,35,$posY,str_pad(utf8_decode("UND FÍS"), 8, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,80,$posY,str_pad(strtoupper(utf8_decode($spanSerialMotor)), 20, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,260,$posY,str_pad(utf8_decode("DIAS"), 10, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,370,$posY,str_pad(utf8_decode("VENTA"), 8, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,415,$posY,str_pad(utf8_decode("CLIENTE"), 16, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,500,$posY,str_pad(utf8_decode("PRECIO"), 14, " ", STR_PAD_BOTH),$textColor);
				$posY += 10;
				imagestring($img,1,80,$posY,str_pad(strtoupper(utf8_decode($spanPlaca)), 20, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,415,$posY,str_pad(utf8_decode("VENDEDOR"), 16, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,500,$posY,str_pad(utf8_decode("COSTO"), 14, " ", STR_PAD_BOTH),$textColor);
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
			}
			
			if (strlen($arrayFila[$indice]['vehiculo']) > 0) {
				$posY += 10;
				imagestring($img,2,0,$posY,utf8_encode($row['vehiculo']),$textColor);
				$posY += 10;
			} else if (strlen($arrayFila[$indice]['id_unidad_fisica']) > 0) {
				$contFila2++;
				
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad($contFila2.")", 6, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,35,$posY,str_pad($arrayFila[$indice]['id_unidad_fisica'], 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,80,$posY,str_pad(utf8_encode($arrayFila[$indice]['serial_carroceria']), 20, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,185,$posY,str_pad(utf8_decode($arrayFila[$indice]['color_externo1']), 14, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,260,$posY,str_pad(implode("-",array_reverse(explode("-",$arrayFila[$indice]['fecha_origen']))), 10, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,315,$posY,str_pad(implode("-",array_reverse(explode("-",$arrayFila[$indice]['fechaRegistroFactura']))), 10, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,370,$posY,str_pad(utf8_decode($arrayFila[$indice]['numeroFactura']), 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,415,$posY,str_pad(substr(utf8_decode($arrayFila[$indice]['nombre_empresa']),0,16), 14, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,500,$posY,str_pad(utf8_decode($arrayFila[$indice]['numero_factura_proveedor']), 14, " ", STR_PAD_LEFT),$textColor);
			} else if (strlen($arrayFila[$indice]['serial_motor']) > 0) {
				$posY += 10;
				imagestring($img,1,80,$posY,str_pad(utf8_encode($arrayFila[$indice]['serial_motor']), 20, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,260,$posY,str_pad(utf8_decode($arrayFila[$indice]['dias_inventario']), 10, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,415,$posY,str_pad(substr(utf8_encode($arrayFila[$indice]['nombre_cliente']),0,16), 16, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,500,$posY,str_pad(number_format($arrayFila[$indice]['precio_unitario'], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
			} else if (strlen($arrayFila[$indice]['placa']) > 0 || strlen($arrayFila[$indice]['nombre_empleado']) > 0 || strlen($arrayFila[$indice]['costo_unitario']) > 0) {
				$posY += 10;
				imagestring($img,1,80,$posY,str_pad(utf8_encode($arrayFila[$indice]['placa']), 20, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,415,$posY,str_pad(substr(strtoupper(utf8_encode($arrayFila[$indice]['nombre_empleado'])),0,16), 16, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,500,$posY,str_pad(number_format($arrayFila[$indice]['costo_unitario'], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
			} else if (strlen($arrayFila[$indice]['linea']) > 0) {
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad("", 152, $arrayFila[$indice]['linea'], STR_PAD_BOTH),$textColor);
			} else if (strlen($arrayFila[$indice]['cant_items']) > 0) {
				$posY += 8;
				imagestring($img,2,0,$posY,str_pad("TOTAL ".utf8_encode($row['nom_uni_bas']).":", 64, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,2,396,$posY,str_pad(utf8_encode($arrayFila[$indice]['cant_items']), 14, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,2,485,$posY,str_pad(number_format($arrayFila[$indice]['subtotal_precio'], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
			} else if (strlen($arrayFila[$indice]['subtotal_costo']) > 0) {
				$posY += 12;
				imagestring($img,2,485,$posY,str_pad(number_format($arrayFila[$indice]['subtotal_costo'], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
			} else if (strlen($arrayFila[$indice]['linea_subtotal_utilidad']) > 0) {
				$posY += 12;
				imagestring($img,1,396,$posY,str_pad("", 35, $arrayFila[$indice]['linea_subtotal_utilidad'], STR_PAD_BOTH),$textColor);
			} else if (strlen($arrayFila[$indice]['monto_utilidad']) > 0) {
				$posY += 8;
				imagestring($img,2,0,$posY,str_pad("%UTL / UTL ".utf8_encode($row['nom_uni_bas']).":", 64, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,2,396,$posY,str_pad(number_format($arrayFila[$indice]['porcentaje_utilidad'], 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,2,485,$posY,str_pad(number_format($arrayFila[$indice]['monto_utilidad'], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
			} else if (strlen($arrayFila[$indice]['doble_linea']) > 0) {
				$posY += 16;
				imagestring($img,1,0,$posY,str_pad("", 152, $arrayFila[$indice]['doble_linea'], STR_PAD_BOTH),$textColor);
			} else if (strlen($arrayFila[$indice]['total_cant_items']) > 0) {
				$posY += 8;
				imagestring($img,2,0,$posY,str_pad("TOTAL DE TOTALES:", 64, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,2,396,$posY,str_pad(utf8_encode($arrayFila[$indice]['total_cant_items']), 14, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,2,485,$posY,str_pad(number_format($arrayFila[$indice]['total_subtotal_precio'], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
			} else if (strlen($arrayFila[$indice]['total_subtotal_costo']) > 0) {
				$posY += 12;
				imagestring($img,2,485,$posY,str_pad(number_format($arrayFila[$indice]['total_subtotal_costo'], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
			} else if (strlen($arrayFila[$indice]['linea_total_utilidad']) > 0) {
				$posY += 12;
				imagestring($img,1,396,$posY,str_pad("", 35, $arrayFila[$indice]['linea_total_utilidad'], STR_PAD_BOTH),$textColor);
			} else if (strlen($arrayFila[$indice]['total_monto_utilidad']) > 0) {
				$posY += 8;
				imagestring($img,2,0,$posY,str_pad("TOTAL %UTL / UTL:", 64, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,2,396,$posY,str_pad(number_format($arrayFila[$indice]['total_porcentaje_utilidad'], 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,2,485,$posY,str_pad(number_format($arrayFila[$indice]['total_monto_utilidad'], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
			}
			
			if (fmod($contFilaY, $maxRows) == 0 || ($contFila == $totalRows && $contFila2 == $totalRowsDetalle && strlen($arrayFila[$indice]['total_monto_utilidad']) > 0)) {
				$pageNum++;
				$arrayImg[] = "tmp/"."existencia_unidad_fisica".$pageNum.".png";
				$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
			}
		}
	}
}

// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

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
				$direcEmpresa = $rowEmp['direccion'].".";
				$telfEmpresa = "";
				if (strlen($rowEmp['telefono1']) > 1) {
					$telfEmpresa .= "Telf.: ".$rowEmp['telefono1'];
				}
				if (strlen($rowEmp['telefono2']) > 1) {
					$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
					$telfEmpresa .= $rowEmp['telefono2'];
				}
				
				$pdf->SetX(88);
				$pdf->Cell(100,9,$direcEmpresa." ".$telfEmpresa,0,2,'L');
			}
			if (strlen($rowEmp['web']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_encode($rowEmp['web']),0,0,'L');
				$pdf->Ln();
			}
		}
		
		$pdf->Image($valor, 16, $rowConfig10['valor'], 758, 520);
		
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',6);
		$pdf->Cell(0,8,"Impreso: ".date("d-m-Y h:i a"),0,0,'R');
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',8);
		$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
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