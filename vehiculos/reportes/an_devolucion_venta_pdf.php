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
$maxRows = 34;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// BUSCA LOS DATOS DE LA NOTA DE CRÉDITO
$queryEncabezado = sprintf("SELECT nota_cred.*,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_empleado
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_notacredito nota_cred ON (cliente.id = nota_cred.idCliente)
	LEFT JOIN pg_empleado ON (nota_cred.id_empleado_vendedor = pg_empleado.id_empleado)
WHERE nota_cred.idNotaCredito = %s
	AND nota_cred.idDepartamentoNotaCredito IN (2);",
	valTpDato($idDocumento, "int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsEncabezado = mysql_num_rows($rsEncabezado);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];
$idFactura = $rowEncabezado['idDocumento'];

// BUSCA LOS DATOS DE LA FACTURA
$queryFact = sprintf("SELECT
	cj_cc_encabezadofactura.*
FROM cj_cc_encabezadofactura
WHERE cj_cc_encabezadofactura.idFactura = %s;",
	valTpDato($idFactura,"int"));
$rsFact = mysql_query($queryFact, $conex);
if (!$rsFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFact = mysql_num_rows($rsFact);
$rowFact = mysql_fetch_assoc($rsFact);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Datos del Sistema GNV en la Impresion de la Factura de Venta y Nota de Crédito)
$queryConfig202 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 202 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig202 = mysql_query($queryConfig202, $conex);
if (!$rsConfig202) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowConfig202 = mysql_fetch_assoc($rsConfig202);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Dcto. Identificación (C.I. / R.I.F. / R.U.C. / LIC / SSN) en Documentos Fiscales.)
$queryConfig409 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 409 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig409 = mysql_query($queryConfig409);
if (!$rsConfig409) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig409 = mysql_num_rows($rsConfig409);
$rowConfig409 = mysql_fetch_assoc($rsConfig409);


// BUSCA LOS DATOS DE LA UNIDAD
$queryUnidad = sprintf("SELECT 
	uni_bas.nom_uni_bas,
	marca.nom_marca,
	modelo.nom_modelo,
	vers.nom_version,
	ano.nom_ano,
	uni_fis.placa,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	color1.nom_color AS color_externo,
	nota_cred_det_vehic.precio_unitario,
	uni_bas.com_uni_bas,
	codigo_unico_conversion,
	marca_kit,
	marca_cilindro,
	modelo_regulador,
	serial1,
	serial_regulador,
	capacidad_cilindro,
	fecha_elaboracion_cilindro
FROM cj_cc_nota_credito_detalle_vehiculo nota_cred_det_vehic
	INNER JOIN an_unidad_fisica uni_fis ON (nota_cred_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
	INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
	INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
	INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
	INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
	INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
WHERE nota_cred_det_vehic.id_nota_credito = %s",
	valTpDato($idDocumento, "int"));
$rsUnidad = mysql_query($queryUnidad);
if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsUnidad = mysql_num_rows($rsUnidad);
$rowUnidad = mysql_fetch_array($rsUnidad);

$queryDetalle = sprintf("SELECT
	nota_cred_det_acc.id_nota_credito_detalle_accesorios,
	nota_cred_det_acc.id_accesorio,
	nota_cred_det_acc.costo_compra,
	nota_cred_det_acc.precio_unitario,
	(CASE
		WHEN nota_cred_det_acc.id_iva = 0 THEN
			CONCAT(acc.nom_accesorio, ' (E)')
		ELSE
			acc.nom_accesorio
	END) AS nom_accesorio,
	nota_cred_det_acc.tipo_accesorio
FROM cj_cc_nota_credito_detalle_accesorios nota_cred_det_acc
	INNER JOIN an_accesorio acc ON (nota_cred_det_acc.id_accesorio = acc.id_accesorio)
WHERE nota_cred_det_acc.id_nota_credito = %s",
	valTpDato($idDocumento, "int"));
$rsDetalle = mysql_query($queryDetalle);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

if ($totalRowsUnidad == 0 && $totalRowsDetalle == 0) {
	$tieneDetalle = false;
	$queryDetalle = sprintf("SELECT
		NULL AS id_subseccion,
		NULL AS codigo_articulo,
		NULL AS descripcion_tipo,
		NULL AS descripcion_articulo,
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

$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

$posY = 9;
imagestring($img,1,300,$posY,str_pad(utf8_decode("NOTA DE CRÉDITO SERIE - V"), 34, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,300,$posY,utf8_decode("NOTA CRÉD. NRO."),$textColor);
imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numeracion_nota_credito'],$textColor);

$posY += 9;
imagestring($img,1,300,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
imagestring($img,1,375,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fechaNotaCredito'])),$textColor);

$posY += 9;
imagestring($img,1,300,$posY,("FACTURA NRO."),$textColor);
imagestring($img,1,375,$posY,": ".$rowFact['numeroFactura'],$textColor);

$posY += 9;
imagestring($img,1,300,$posY,("VENDEDOR"),$textColor);
imagestring($img,1,375,$posY,": ".strtoupper($rowEncabezado['nombre_empleado']),$textColor);

$posY = 28;
imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id'], 8, " ", STR_PAD_LEFT),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['nombre_cliente'],0,60)),$textColor); // <----

if (in_array($rowConfig409['valor'],array("","1"))) {
	$posY += 9;
	imagestring($img,1,0,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($rowEncabezado['ci_cliente']),$textColor);
}

$direccionCliente = strtoupper(str_replace(",", "", elimCaracter(utf8_encode($rowEncabezado['direccion_cliente']),";")));
$posY += 9;
imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,54)),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,trim(substr($direccionCliente,54,54)),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,trim(substr($direccionCliente,108,30)),$textColor);
imagestring($img,1,155,$posY,("TELEFONO"),$textColor);
imagestring($img,1,195,$posY,": ".$rowEncabezado['telf'],$textColor);

$posY += 9;
imagestring($img,1,0,$posY,trim(substr($direccionCliente,138,30)),$textColor);
imagestring($img,1,205,$posY,$rowEncabezado['otrotelf'],$textColor);

$posY = 90;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
if (!isset($tieneDetalle)) {
	imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 18, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,95,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 56, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,380,$posY,str_pad(utf8_decode("TOTAL"), 18, " ", STR_PAD_BOTH),$textColor);
} else {
	imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 28, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,260,$posY,str_pad(utf8_decode("CANTIDAD"), 10, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,315,$posY,strtoupper(str_pad(utf8_decode($spanPrecioUnitario), 15, " ", STR_PAD_BOTH)),$textColor);
	imagestring($img,1,395,$posY,str_pad(utf8_decode("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
}
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

if ($totalRowsUnidad > 0 && !isset($tieneDetalle)) {
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(substr($rowUnidad['nom_uni_bas'],0,18)),$textColor);
	imagestring($img,1,95,$posY,utf8_decode("MARCA"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_marca']),$textColor);
	
	$posY += 9;
	imagestring($img,1,95,$posY,utf8_decode("MODELO"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_modelo']),$textColor);
	
	$posY += 9;
	imagestring($img,1,95,$posY,utf8_decode("VERSIÓN"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_version']),$textColor);
	
	$posY += 9;
	imagestring($img,1,95,$posY,utf8_decode("AÑO"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_ano']),$textColor);
	
	$posY += 12;
	imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanPlaca)),$textColor);
	imagestring($img,2,210,$posY-3,": ".strtoupper($rowUnidad['placa']),$textColor);
	
	$posY += 12;
	imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanSerialCarroceria)),$textColor);
	imagestring($img,2,210,$posY-3,": ".strtoupper($rowUnidad['serial_carroceria']),$textColor);
	
	$posY += 12;
	imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanSerialMotor)),$textColor);
	imagestring($img,2,210,$posY-3,": ".strtoupper($rowUnidad['serial_motor']),$textColor);
	
	$posY += 18;
	imagestring($img,1,95,$posY,utf8_decode("COLOR CARROCERIA"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['color_externo']),$textColor);
	
	if ($rowConfig202['valor'] == 1
	|| ($rowConfig202['valor'] == 2
		&& (strlen($rowUnidad['codigo_unico_conversion']) > 1
			|| strlen($rowUnidad['marca_kit']) > 1
			|| strlen($rowUnidad['marca_cilindro']) > 1
			|| strlen($rowUnidad['modelo_regulador']) > 1
			|| strlen($rowUnidad['serial1']) > 1
			|| strlen($rowUnidad['serial_regulador']) > 1
			|| strlen($rowUnidad['capacidad_cilindro']) > 1
			|| strlen($rowUnidad['fecha_elaboracion_cilindro']) > 1))) {
		if ($rowUnidad['com_uni_bas'] == 2 || $rowUnidad['com_uni_bas'] == 5) {
			$posY += 18;
			imagestring($img,1,95,$posY,str_pad(utf8_decode("SISTEMA GNV"), 65, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,95,$posY,utf8_decode("CÓDIGO UNICO"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['codigo_unico_conversion']),$textColor);
			
			$posY += 9;
			imagestring($img,1,95,$posY,utf8_decode("MARCA KIT"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['marca_kit']),$textColor);
			
			$posY += 9;
			imagestring($img,1,95,$posY,utf8_decode("MARCA CILINDRO"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['marca_cilindro']),$textColor);
			
			$posY += 9;
			imagestring($img,1,95,$posY,utf8_decode("MODELO REGULADOR"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['modelo_regulador']),$textColor);
			
			$posY += 9;
			imagestring($img,1,95,$posY,utf8_decode("SERIAL 1"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial1']),$textColor);
			
			$posY += 9;
			imagestring($img,1,95,$posY,utf8_decode("SERIAL REGULADOR"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial_regulador']),$textColor);
			
			$posY += 9;
			imagestring($img,1,95,$posY,utf8_decode("CAPACIDAD CILINDRO (NG)"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['capacidad_cilindro']),$textColor);
			
			$posY += 9;
			imagestring($img,1,95,$posY,utf8_decode("FECHA ELAB. CILINDRO"),$textColor);
			imagestring($img,1,210,$posY,($rowUnidad['fecha_elaboracion_cilindro']) ? ": ".date("d-m-Y",strtotime($rowUnidad['fecha_elaboracion_cilindro'])) : ": "."----------",$textColor);
		}
	}
	
	$posY += 9;
	imagestring($img,1,95,$posY,"--------------------------------------------------------",$textColor);
	
	$posY += 9;
	imagestring($img,1,95,$posY,utf8_decode("MONTO VEHÍCULO"),$textColor);
	imagestring($img,1,380,$posY,str_pad(number_format($rowUnidad['precio_unitario'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
}

if ($totalRowsDetalle > 0 && !isset($tieneDetalle)) {
	while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
		$posY += 9;
		imagestring($img,1,95,$posY,strtoupper($rowDetalle['nom_accesorio']),$textColor);
		imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowDetalle['precio_unitario'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
	}
}

if (isset($tieneDetalle)) {
	$observacionDcto = strtoupper($rowEncabezado['observacionesNotaCredito']);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,0,50))),$textColor);
	imagestring($img,1,260,$posY,strtoupper(str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_BOTH)),$textColor);
	imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($rowEncabezado['subtotalNotaCredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowEncabezado['subtotalNotaCredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,50,50))),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,100,50))),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,150,50))),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,200,50))),$textColor);
}

$posY = 460;

if ($totalRowsFact > 0) {
	$posY += 9;
	imagestring($img,1,0,$posY,str_pad("", 50, "-", STR_PAD_BOTH),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,"NOTA DE CREDITO QUE HACE REFERENCIA A",$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,"FACT. NRO ".$rowFact['numeroFactura']." NRO CONTROL ".$rowFact['numeroControl'],$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,"DE FECHA ".date("d-m-Y", strtotime($rowFact['fechaRegistroFactura'])),$textColor);
}

if (!isset($tieneDetalle)) {
	$observacionDcto = strtoupper($rowEncabezado['observacionesNotaCredito']);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,0,50))),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,50,50))),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,100,50))),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,150,50))),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,200,50))),$textColor);
}

$posY = 460;

$posY += 9;
imagestring($img,1,260,$posY,"SUB-TOTAL",$textColor);
imagestring($img,1,340,$posY,":",$textColor);
imagestring($img,1,380,$posY,str_pad(number_format($rowEncabezado['subtotalNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);

if ($rowEncabezado['porcentaje_descuento'] == "") {
	$porcDescuento = ($rowEncabezado['descuentoFactura'] * 100) / $rowEncabezado['subtotalNotaCredito'];
	$subtotalDescuento = $rowEncabezado['descuentoFactura'];
} else {
	$porcDescuento = $rowEncabezado['porcentaje_descuento'];
	$subtotalDescuento = $rowEncabezado['subtotal_descuento'];
}

if ($subtotalDescuento > 0) {
	$posY += 9;
	imagestring($img,1,260,$posY,"DESCUENTO",$textColor);
	imagestring($img,1,340,$posY,":",$textColor);
	imagestring($img,1,345,$posY,strtoupper(str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($subtotalDescuento, 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
}

if ($totalGastosConIva != 0) {
	$posY += 9;
	imagestring($img,1,260,$posY,"GASTOS C/IMPTO",$textColor);
	imagestring($img,1,340,$posY,":",$textColor);
	imagestring($img,1,380,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
}

// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
$queryIva = sprintf("SELECT * FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1;");
$rsIva = mysql_query($queryIva);
if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowIva = mysql_fetch_assoc($rsIva);

$posY += 9;
imagestring($img,1,260,$posY,"BASE IMPONIBLE",$textColor);
imagestring($img,1,340,$posY,":",$textColor);
imagestring($img,1,380,$posY,str_pad(number_format($rowEncabezado['baseimponibleNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);

if ($rowEncabezado['porcentajeIvaNotaCredito'] > 0) {
	$porcIva = $rowEncabezado['porcentajeIvaNotaCredito'];
} else if ($rowEncabezado['baseimponibleNotaCredito'] > 0) {
	$porcIva = (doubleval($rowEncabezado['ivaNotaCredito']) * 100) / doubleval($rowEncabezado['baseimponibleNotaCredito']);
}
$posY += 9;
imagestring($img,1,260,$posY,strtoupper($rowIva['observacion']),$textColor);
imagestring($img,1,340,$posY,":",$textColor);
imagestring($img,1,345,$posY,strtoupper(str_pad(number_format($porcIva, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowEncabezado['ivaNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);

if ($rowEncabezado['ivaLujoNotaCredito'] > 0) {
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT * FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (2) AND iva.activo = 1;");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowIva = mysql_fetch_assoc($rsIva);
	
	if ($rowEncabezado['ivaLujoNotaCredito'] > 0) {
		$porcIvaLujo = $rowEncabezado['ivaLujoNotaCredito'];
	} else if ($rowEncabezado['baseimponibleNotaCredito'] > 0) {
		$porcIvaLujo = (doubleval($rowEncabezado['ivaLujoNotaCredito']) * 100) / doubleval($rowEncabezado['baseimponibleNotaCredito']);
	}
	$posY += 9;
	imagestring($img,1,260,$posY,strtoupper($rowIva['observacion']),$textColor);
	imagestring($img,1,340,$posY,":",$textColor);
	imagestring($img,1,345,$posY,strtoupper(str_pad(number_format($porcIvaLujo, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowEncabezado['ivaLujoNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
}

if ($totalGastosSinIva != 0) {
	$posY += 9;
	imagestring($img,1,260,$posY,"GASTOS S/IMPTO",$textColor);
	imagestring($img,1,340,$posY,":",$textColor);
	imagestring($img,1,380,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor); // <---
}

if ($rowEncabezado['montoExentoCredito'] > 0) {
	$posY += 9;
	imagestring($img,1,260,$posY,"EXENTO",$textColor);
	imagestring($img,1,340,$posY,":",$textColor);
	imagestring($img,1,380,$posY,str_pad(number_format($rowEncabezado['montoExentoCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
}

if ($rowEncabezado['montoExoneradoCredito'] > 0) {
	$posY += 9;
	imagestring($img,1,260,$posY,"MONTO EXONERADO",$textColor);
	imagestring($img,1,340,$posY,":",$textColor);
	imagestring($img,1,380,$posY,str_pad(number_format($rowEncabezado['montoExoneradoCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
}

$posY += 8;
imagestring($img,1,260,$posY,"------------------------------------------",$textColor);

$posY += 8;
imagestring($img,1,260,$posY,"TOTAL",$textColor);
imagestring($img,1,340,$posY,":",$textColor);
imagestring($img,2,360,$posY,strtoupper(str_pad(number_format($rowEncabezado['montoNetoNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);

$pageNum++;
$arrayImg[] = "tmp/"."devolucion_venta_vehiculo".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

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
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 688);
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