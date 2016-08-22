<?php
set_time_limit(0);
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/
$maxRows = 18;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// BUSCA LOS DATOS DEL DOCUMENTO
$queryEncabezado = sprintf("SELECT 
	cxp_nc.id_notacredito,
	cxp_nc.id_empresa,
	cxp_nc.numero_nota_credito,
	cxp_nc.numero_control_notacredito,
	cxp_nc.fecha_registro_notacredito,
	cxp_nc.fecha_notacredito,
	cxp_nc.fecha_notacredito AS fecha_vencimiento_notacredito,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	prov.nombre AS nombre_proveedor,
	prov.direccion AS direccion_proveedor,
	prov.telefono,
	prov.otrotelf,
	prov_cred.diascredito,
	1 AS condicionDePago,
	cxp_nc.id_departamento_notacredito,
	motivo.descripcion AS descripcion_motivo,
	cxp_nc.subtotal_notacredito,
	cxp_nc.subtotal_descuento,
	cxp_nc.monto_exento_notacredito,
	cxp_nc.monto_exonerado_notacredito,
	cxp_nc.observacion_notacredito,
	vw_pg_empleado.nombre_empleado
FROM cp_notacredito cxp_nc
	INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)
	LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	LEFT JOIN pg_motivo motivo ON (cxp_nc.id_motivo = motivo.id_motivo)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxp_nc.id_empleado_creador = vw_pg_empleado.id_empleado)
WHERE cxp_nc.id_notacredito = %s;",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$queryClaveMov = sprintf("SELECT
	clave_mov.id_clave_movimiento,
	clave_mov.descripcion,
	(CASE mov.id_tipo_movimiento
		WHEN 1 THEN 'COMPRA'
		WHEN 2 THEN 'ENTRADA'
		WHEN 3 THEN 'VENTA'
		WHEN 4 THEN 'SALIDA'
	END) AS tipo_movimiento
FROM pg_clave_movimiento clave_mov
  INNER JOIN iv_movimiento mov ON (clave_mov.id_clave_movimiento = mov.id_clave_movimiento)
WHERE mov.id_documento = %s
	AND mov.id_tipo_movimiento IN (4)
	AND mov.tipo_documento_movimiento IN (2);",
	valTpDato($idDocumento,"int"));
$rsClaveMov = mysql_query($queryClaveMov, $conex);
if (!$rsClaveMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowClaveMov = mysql_fetch_assoc($rsClaveMov);

// DETALLES DE LOS REPUESTOS
if ($rowEncabezado['id_departamento_notacredito'] == 0) {
	$queryDetalle = sprintf("SELECT 
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		cxp_fact_det.cantidad,
		cxp_fact_det.pendiente,
		cxp_fact_det.precio_unitario,
		cxp_fact_det.id_iva,
		cxp_fact_det.iva,
		cxp_fact_det.id_casilla
	FROM cp_factura_detalle cxp_fact_det
		INNER JOIN iv_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
		INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det.id_factura = cxp_nc.id_documento)
	WHERE cxp_nc.id_notacredito = %s
		AND cxp_nc.tipo_documento = 'FA'
	ORDER BY id_factura_detalle ASC",
		valTpDato($idDocumento,"int"));
} else if ($rowEncabezado['id_departamento_notacredito'] == 3) {
	$queryDetalle = sprintf("SELECT 
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		cxp_fact_det.cantidad,
		cxp_fact_det.pendiente,
		cxp_fact_det.precio_unitario,
		cxp_fact_det.id_iva,
		cxp_fact_det.iva,
		cxp_fact_det.id_casilla
	FROM cp_factura_detalle cxp_fact_det
		INNER JOIN ga_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
		INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det.id_factura = cxp_nc.id_documento)
	WHERE cxp_nc.id_notacredito = %s
		AND cxp_nc.tipo_documento = 'FA'
	ORDER BY id_factura_detalle ASC",
		valTpDato($idDocumento,"int"));
}
if (strlen($queryDetalle) > 0) {
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

if ($totalRowsDetalle == 0) {
	$tieneDetalle = false;
	$queryDetalle = sprintf("SELECT
		NULL AS id_subseccion,
		NULL AS codigo_articulo,
		NULL AS descripcion_tipo,
		NULL AS descripcion,
		NULL AS descripcion_seccion,
		NULL AS cantidad,
		NULL AS precio_unitario,
		NULL AS id_iva,
		NULL AS iva,
		NULL AS id_articulo,
		NULL AS id_factura_detalle");
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	if (fmod($contFila, $maxRows) == 1) {
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		
		$posY = 9;
		imagestring($img,1,300,$posY,str_pad(utf8_decode("NOTA DE CRÉDITO"), 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA REGISTRO"),$textColor);
		imagestring($img,1,375,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fecha_registro_notacredito'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. NOTA CRÉD."),$textColor);
		imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numero_nota_credito'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. CONTROL"),$textColor);
		imagestring($img,1,375,$posY,": ".$rowEncabezado['numero_control_notacredito'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA NC PROV."),$textColor);
		imagestring($img,1,375,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fecha_notacredito'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA VENC."),$textColor);
		imagestring($img,1,375,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fecha_vencimiento_notacredito'])),$textColor);
		
		$posY += 9;
		if ($rowEncabezado['condicionDePago'] == 0) { // 0 = Credito, 1 = Contado
			imagestring($img,1,385,$posY,"CRED. ".number_format($rowEncabezado['diascredito'])." DIAS",$textColor);
		}
		
		if (strlen($rowClaveMov['tipo_movimiento']) > 0) {
			$posY += 9;
			imagestring($img,1,300,$posY,utf8_decode("TIPO MOV."),$textColor);
			imagestring($img,1,375,$posY,": ".strtoupper($rowClaveMov['tipo_movimiento']),$textColor);
		}
		
		if (strlen($rowClaveMov['descripcion']) > 0) {
			$posY += 9;
			imagestring($img,1,300,$posY,utf8_decode("CLAVE MOV."),$textColor);
			imagestring($img,1,375,$posY,": ".strtoupper($rowClaveMov['descripcion']),$textColor);
		}
		
		$posY = 28;
		imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id_proveedor'], 8, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['nombre_proveedor'],0,60)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($rowEncabezado['rif_proveedor']),$textColor);
		
		$direccionCliente = strtoupper(str_replace(",", "", $rowEncabezado['direccion_proveedor']));
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,54,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,108,30)),$textColor);
		imagestring($img,1,155,$posY,utf8_decode("TELEFONO"),$textColor);
		imagestring($img,1,195,$posY,": ".$rowEncabezado['telefono'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,138,30)),$textColor);
		imagestring($img,1,205,$posY,$rowEncabezado['otrotelf'],$textColor);
		
		
		$posY = 90;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 28, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,255,$posY,str_pad(utf8_decode("CANTIDAD"), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,strtoupper(str_pad(utf8_decode($spanPrecioUnitario), 15, " ", STR_PAD_BOTH)),$textColor);
		imagestring($img,1,395,$posY,str_pad(utf8_decode("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	if (isset($tieneDetalle)) {
		$observacion = $rowEncabezado['observacion_notacredito'];
		$posY += 8;
		imagestring($img,1,0,$posY,strtoupper(substr($observacion,0,51)),$textColor);
		imagestring($img,1,255,$posY,strtoupper(str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_LEFT)),$textColor);
		imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($rowEncabezado['subtotal_notacredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
		imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowEncabezado['subtotal_notacredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
		if (strlen($observacion) > 51) {
			$posY += 8;
			imagestring($img,1,0,$posY,strtoupper(substr($observacion,51,51)),$textColor);
		}
		if (strlen($observacion) > 102) {
			$posY += 8;
			imagestring($img,1,0,$posY,strtoupper(substr($observacion,102,51)),$textColor);
		}
		$posY += 8;
		imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['descripcion_motivo'],0,51)),$textColor);
	} else {
		if ($rowEncabezado['id_departamento_notacredito'] == 0) {
			$queryArtAlm = sprintf("SELECT
				vw_iv_art_alm.descripcion_almacen,
				vw_iv_art_alm.ubicacion
			FROM vw_iv_articulos_almacen vw_iv_art_alm
				INNER JOIN iv_kardex kardex ON (vw_iv_art_alm.id_casilla = kardex.id_casilla)
			WHERE kardex.id_documento = %s
				AND kardex.id_articulo = %s
				AND kardex.tipo_movimiento IN (4)
				AND kardex.tipo_documento_movimiento IN (2);",
				valTpDato($idDocumento, "int"),
				valTpDato($rowDetalle['id_articulo'], "int"));
		} else if ($rowEncabezado['id_departamento_notacredito'] == 3) {
			$queryArtAlm = sprintf("SELECT
				almacen.descripcion AS descripcion_almacen,
				CONCAT_WS('-', calle.descripcion_calle, estante.descripcion_estante, tramo.descripcion_tramo, casilla.descripcion_casilla) AS ubicacion
			FROM ga_almacenes almacen
				INNER JOIN ga_calles calle ON (almacen.id_almacen = calle.id_almacen)
				INNER JOIN ga_estantes estante ON (calle.id_calle = estante.id_calle)
				INNER JOIN ga_tramos tramo ON (estante.id_estante = tramo.id_estante)
				INNER JOIN ga_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
				INNER JOIN ga_kardex kardex ON (casilla.id_casilla = kardex.id_casilla)
			WHERE kardex.id_documento = %s
				AND kardex.id_articulo = %s
				AND kardex.tipo_movimiento IN (4);",
				valTpDato($idDocumento, "int"),
				valTpDato($rowDetalle['id_articulo'], "int"));
		}
		$rsArtAlm = mysql_query($queryArtAlm);
		if (!$rsArtAlm) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
		$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
		
		$posY += 8;
		imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($rowDetalle['descripcion'],0,28)),$textColor);
		imagestring($img,1,255,$posY,strtoupper(str_pad(number_format($rowDetalle['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT)),$textColor);
		imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($rowDetalle['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
		imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowDetalle['cantidad'] * $rowDetalle['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
		
		$posY += 8;
		imagestring($img,1,115,$posY,strtoupper(substr($rowArtAlm['descripcion_almacen']." ".str_replace("-[]", "", $rowArtAlm['ubicacion']),0,34)),$textColor);
	}
		
	if (fmod($contFila, $maxRows) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = 425;
			if ($totalRowsConfig4 > 0) {
				$valor = $rowConfig4['valor'];
				
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,0,94))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,94,188))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,188,282))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,282,376))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,376,470))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,470,564))),$textColor);
			}
			
			$queryGasto = sprintf("SELECT
				cxp_nc_gasto.id_notacredito_gastos,
				cxp_nc_gasto.id_gastos_notacredito AS id_notacredito,
				cxp_nc_gasto.tipo_gasto_notacredito AS tipo,
				cxp_nc_gasto.porcentaje_monto,
				cxp_nc_gasto.monto_gasto_notacredito AS monto,
				cxp_nc_gasto.estatus_iva_notacredito AS estatus_iva,
				cxp_nc_gasto.id_iva_notacredito AS id_iva,
				cxp_nc_gasto.iva_notacredito AS iva,
				gasto.id_gasto,
				IF ((cxp_nc_gasto.id_iva_notacredito > 0), gasto.nombre, CONCAT_WS(' ', gasto.nombre, '(E)')) AS nombre,
				gasto.id_modo_gasto
			FROM pg_gastos gasto
				INNER JOIN cp_notacredito_gastos cxp_nc_gasto ON (gasto.id_gasto = cxp_nc_gasto.id_gastos_notacredito)
			WHERE cxp_nc_gasto.id_notacredito = %s
				AND cxp_nc_gasto.id_modo_gasto IN (1,3);",
				valTpDato($idDocumento, "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$posY = 460;
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				$porcGasto = $rowGasto['porcentaje_monto'];
				$montoGasto = $rowGasto['monto'];
				
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(substr($rowGasto['nombre'],0,25)),$textColor);
				imagestring($img,1,130,$posY,":",$textColor);
				imagestring($img,1,140,$posY,str_pad(number_format($porcGasto, 2, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,175,$posY,str_pad(number_format($montoGasto, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				if ($rowGasto['id_modo_gasto'] == 1) { // 1 = Gastos
					if ($rowGasto['id_iva'] > 0) {
						$totalGastosConIvaOrigen += $montoGasto;
					} else if ($rowGasto['id_iva'] == 0) {
						$totalGastosSinIvaOrigen += $montoGasto;
					}
				} else if ($rowGasto['id_modo_gasto'] == 3) { // 3 = Gastos por Importacion
					if ($rowGasto['id_iva'] > 0) {
						$totalGastosConIvaLocal += $montoGasto;
					} else if ($rowGasto['id_iva'] == 0) {
						$totalGastosSinIvaLocal += $montoGasto;
					}
				}
			}
			
			
			$observacion = (isset($tieneDetalle)) ? "" : $rowEncabezado['observacion_notacredito'];
			if (strlen($observacion) > 0 || strlen($rowEncabezado['numero_siniestro']) > 0) {
				if (strlen($observacion) > 0) {
					$posY += 9;
					imagestring($img,1,0,$posY,trim(substr($observacion,0,62)),$textColor);
					$posY += 9;
					imagestring($img,1,0,$posY,trim(substr($observacion,62,62)),$textColor);
				}
				if (strlen($rowEncabezado['numero_siniestro']) > 0) {
					$posY += 9;
					imagestring($img,1,0,$posY,utf8_decode("NRO. SINIESTRO"),$textColor);
					imagestring($img,1,70,$posY,": ".$rowEncabezado['numero_siniestro'],$textColor);
				}
			}
			
			$posY = 460;
			
			$posY += 9;
			imagestring($img,1,255,$posY,"SUB TOTAL",$textColor);
			imagestring($img,1,325,$posY,":",$textColor);
			imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotal_notacredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$porcDescuento = ($rowEncabezado['subtotal_notacredito'] > 0) ? ($rowEncabezado['subtotal_descuento'] * 100) / $rowEncabezado['subtotal_notacredito'] : 0;
			if ($rowEncabezado['subtotal_descuento'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"DESCUENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,350,$posY,str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotal_descuento'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$totalGastosConIva = $totalGastosConIvaOrigen + $totalGastosConIvaLocal;
			if ($totalGastosConIva > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"GASTOS C/IMPTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$queryIvaDcto = sprintf("SELECT
				iva.observacion,
				cxp_nc_iva.baseimponible_notacredito AS base_imponible,
				cxp_nc_iva.iva_notacredito AS iva,
				cxp_nc_iva.subtotal_iva_notacredito AS subtotal_iva
			FROM cp_notacredito_iva cxp_nc_iva
				INNER JOIN pg_iva iva ON (cxp_nc_iva.id_iva_notacredito = iva.idIva)
			WHERE cxp_nc_iva.id_notacredito = %s;",
				valTpDato($idDocumento, "text"));
			$rsIvaDcto = mysql_query($queryIvaDcto);
			if (!$rsIvaDcto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			while ($rowIvaDcto = mysql_fetch_assoc($rsIvaDcto)) {
				$posY += 9;
				imagestring($img,1,255,$posY,"BASE IMPONIBLE",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaDcto['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$posY += 9;
				imagestring($img,1,255,$posY,substr($rowIvaDcto['observacion'],0,14),$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,350,$posY,str_pad(number_format($rowIvaDcto['iva'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaDcto['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$totalIva += $rowIvaDcto['subtotal_iva'];
			}
			
			$totalGastosSinIva = $totalGastosSinIvaOrigen + $totalGastosSinIvaLocal;
			if ($totalGastosSinIva > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"GASTOS S/IMPTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor); // <---
			}
			
			$montoExento = $rowEncabezado['monto_exento_notacredito'] - ($totalGastosSinIvaOrigen + $totalGastosSinIvaLocal);
			if ($montoExento > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"EXENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($montoExento, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 7;
			imagestring($img,1,255,$posY,str_pad("", 43, "-", STR_PAD_LEFT),$textColor);
			
			$totalFactura = $rowEncabezado['subtotal_notacredito'] - $rowEncabezado['subtotal_descuento'] + $totalIva + $totalGastosSinIvaOrigen + $totalGastosConIvaOrigen + $totalGastosSinIvaLocal + $totalGastosConIvaLocal;
			$posY += 7;
			imagestring($img,1,255,$posY,"TOTAL",$textColor);
			imagestring($img,1,325,$posY,":",$textColor);
			imagestring($img,2,380,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."nota_credito".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
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
		//$pdf->SetY(-20);
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 690);
		
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',7);
		$pdf->Cell(0,8,((strlen($rowEncabezado['nombre_empleado']) > 0) ? "Registrado por: ".$rowEncabezado['nombre_empleado'] : ""),0,0,'L');
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