<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// VERIFICA SI TIENE IMPUESTO DE VENTA
$queryIva = sprintf("SELECT 
	cxc_fact.baseImponible,
	cxc_fact.porcentajeIvaFactura,
	cxc_fact.calculoIvaFactura
FROM cj_cc_encabezadofactura cxc_fact
WHERE cxc_fact.idFactura = %s
	AND cxc_fact.calculoIvaFactura > 0;",
	valTpDato($idDocumento, "int"));
$rsIva = mysql_query($queryIva, $conex);
if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsIva = mysql_num_rows($rsIva);

// VERIFICA SI TIENE DETALLE DE IMPUESTO
$queryFactIva = sprintf("SELECT * FROM cj_cc_factura_iva
WHERE id_factura = %s
	AND id_iva IN (SELECT idIva FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1);",
	valTpDato($idDocumento, "int"));
$rsFactIva = mysql_query($queryFactIva, $conex);
if (!$rsFactIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFactIva = mysql_num_rows($rsFactIva);

if ($totalRowsIva > 0 && !($totalRowsFactIva > 0)) {
	$rowIva = mysql_fetch_assoc($rsIva);
	
	// INSERTA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
	SELECT %s, %s, %s, idIva, %s, IF (iva.tipo IN (3,2), 1, NULL) FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1;",
		valTpDato($idDocumento, "int"),
		valTpDato($rowIva['baseImponible'], "real_inglesa"),
		valTpDato($rowIva['calculoIvaFactura'],"real_inglesa"),
		valTpDato($rowIva['porcentajeIvaFactura'], "real_inglesa"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
}

// VERIFICA SI TIENE IMPUESTO DE VENTA AL LUJO
$queryIva = sprintf("SELECT 
	cxc_fact.base_imponible_iva_lujo,
	cxc_fact.porcentajeIvaDeLujoFactura,
	cxc_fact.calculoIvaDeLujoFactura
FROM cj_cc_encabezadofactura cxc_fact
WHERE cxc_fact.idFactura = %s
	AND cxc_fact.calculoIvaDeLujoFactura > 0;",
	valTpDato($idDocumento, "int"));
$rsIva = mysql_query($queryIva, $conex);
if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsIva = mysql_num_rows($rsIva);

// VERIFICA SI TIENE DETALLE DE IMPUESTO
$queryFactIva = sprintf("SELECT * FROM cj_cc_factura_iva
WHERE id_factura = %s
	AND id_iva IN (SELECT idIva FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (2) AND iva.activo = 1);",
	valTpDato($idDocumento, "int"));
$rsFactIva = mysql_query($queryFactIva, $conex);
if (!$rsFactIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFactIva = mysql_num_rows($rsFactIva);

if ($totalRowsIva > 0 && !($totalRowsFactIva > 0)) {
	$rowIva = mysql_fetch_assoc($rsIva);
	
	// INSERTA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
	SELECT %s, %s, %s, idIva, %s, IF (iva.tipo IN (3,2), 1, NULL) FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (2) AND iva.activo = 1;",
		valTpDato($idDocumento, "int"),
		valTpDato($rowIva['base_imponible_iva_lujo'], "real_inglesa"),
		valTpDato($rowIva['calculoIvaDeLujoFactura'],"real_inglesa"),
		valTpDato($rowIva['porcentajeIvaDeLujoFactura'], "real_inglesa"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
}

// BUSCA LOS DATOS DEL DOCUMENTO
$queryEncabezado = sprintf("SELECT 
	cxc_fact.id_empresa,
	cxc_fact.numeroFactura,
	ped_vent.id_pedido,
	ped_vent.numeracion_pedido,
        bancos.nombreBanco,
        ped_vent.meses_financiar,
        ped_vent.cuotas_financiar,
        ped_vent.interes_cuota_financiar,
		ped_vent.fecha_entrega,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.fechaVencimientoFactura,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	cliente.correo,
        prospecto.fecha_nacimiento,
	cxc_fact.observacionFactura,
	cxc_fact.subtotalFactura AS subtotal_factura,
	cxc_fact.porcentaje_descuento,
	cxc_fact.descuentoFactura AS subtotal_descuento,
	cxc_fact.baseImponible AS base_imponible,
	cxc_fact.porcentajeIvaFactura AS porcentaje_iva,
	cxc_fact.calculoIvaFactura AS subtotal_iva,
	cxc_fact.porcentajeIvaDeLujoFactura AS porcentaje_iva_lujo,
	cxc_fact.calculoIvaDeLujoFactura AS subtotal_iva_lujo,
	cxc_fact.montoExento AS monto_exento,
	cxc_fact.montoExonerado AS monto_exonerado,
	cxc_fact.id_credito_tradein
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	INNER JOIN pg_empleado empleado ON (cxc_fact.idVendedor = empleado.id_empleado)
	LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
        LEFT JOIN crm_perfil_prospecto prospecto ON (cliente.id = prospecto.id)
        LEFT JOIN bancos ON ped_vent.id_banco_financiar = bancos.idBanco
WHERE cxc_fact.idFactura = %s
	AND cxc_fact.idDepartamentoOrigenFactura IN (2)",
	valTpDato($idDocumento, "int"));
$rsEncabezado = mysql_query($queryEncabezado);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_array($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Datos del Sistema GNV en la Impresion de la Factura de Venta y Nota de Crédito)
$queryConfigDatosGNV = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 202 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfigDatosGNV = mysql_query($queryConfigDatosGNV, $conex);
if (!$rsConfigDatosGNV) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowConfigDatosGNV = mysql_fetch_assoc($rsConfigDatosGNV);

// VERIFICA VALORES DE CONFIGURACION (Incluir Saldo PND en Precio de Venta de la Unidad (Copia Banco))
$queryConfig208 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 208 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig208 = mysql_query($queryConfig208);
if (!$rsConfig208) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig208 = mysql_num_rows($rsConfig208);
$rowConfig208 = mysql_fetch_assoc($rsConfig208);

// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig403 = mysql_num_rows($rsConfig403);
$rowConfig403 = mysql_fetch_assoc($rsConfig403);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Dcto. Identificación (C.I. / R.I.F. / R.U.C. / LIC / SSN) en Documentos Fiscales.)
$queryConfig409 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 409 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig409 = mysql_query($queryConfig409);
if (!$rsConfig409) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig409 = mysql_num_rows($rsConfig409);
$rowConfig409 = mysql_fetch_assoc($rsConfig409);

if(in_array($rowConfig403['valor'],array(1,2))){
	$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
	
	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);
	
	$posY = 9;
	imagestring($img,1,300,$posY,str_pad("FACTURA SERIE - V", 34, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 9;
	$posY += 9;
	imagestring($img,1,300,$posY,utf8_decode("FACTURA NRO."),$textColor);
	imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numeroFactura'],$textColor);
	
	$posY += 9;
	imagestring($img,1,300,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
	imagestring($img,1,375,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fechaRegistroFactura'])),$textColor);
	
	$posY += 9;
	imagestring($img,1,300,$posY,utf8_decode("FECHA VENC."),$textColor);
	imagestring($img,1,375,$posY,": ".date("d-m-Y", strtotime($rowEncabezado['fechaVencimientoFactura'])),$textColor);
	
	$posY += 18;
	imagestring($img,1,300,$posY,utf8_decode("PEDIDO NRO."),$textColor);
	imagestring($img,1,375,$posY,": ".$rowEncabezado['numeracion_pedido'],$textColor);
	
	$posY += 9;
	imagestring($img,1,300,$posY,utf8_decode("VENDEDOR"),$textColor);
	imagestring($img,1,375,$posY,": ".strtoupper($rowEncabezado['nombre_empleado']),$textColor);
	
	$posY = 28;
	imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id'], 8, " ", STR_PAD_LEFT),$textColor);
	
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['nombre_cliente'],0,60)),$textColor); // <----
	
	if (in_array($rowConfig409['valor'],array("","1"))) {
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($rowEncabezado['ci_cliente']),$textColor);
	}
	
	$direccionCliente = strtoupper(str_replace(";", "", $rowEncabezado['direccion_cliente']));
	$posY += 9;
	imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,54)),$textColor);
	
	$posY += 9;
	imagestring($img,1,0,$posY,trim(substr($direccionCliente,54,54)),$textColor);
	
	$posY += 9;
	imagestring($img,1,0,$posY,trim(substr($direccionCliente,108,30)),$textColor);
	imagestring($img,1,155,$posY,utf8_decode("TELEFONO"),$textColor);
	imagestring($img,1,195,$posY,": ".$rowEncabezado['telf'],$textColor);
	
	$posY += 9;
	imagestring($img,1,0,$posY,trim(substr($direccionCliente,138,30)),$textColor);
	imagestring($img,1,205,$posY,$rowEncabezado['otrotelf'],$textColor);
	
	
	// BUSCA LOS DATOS DE LA UNIDAD
	$queryUnidad = sprintf("SELECT 
		uni_bas.nom_uni_bas,
		marca.nom_marca,
		modelo.nom_modelo,
		vers.nom_version,
		ano.nom_ano,
		uni_fis.placa,
		cond_unidad.descripcion AS condicion_unidad,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.kilometraje,
		color1.nom_color AS color_externo,
		cxc_fact_det_vehic.precio_unitario,
		uni_bas.com_uni_bas,
		codigo_unico_conversion,
		marca_kit,
		marca_cilindro,
		modelo_regulador,
		serial1,
		serial_regulador,
		capacidad_cilindro,
		fecha_elaboracion_cilindro
	FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
		INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
	WHERE cxc_fact_det_vehic.id_factura = %s",
		valTpDato($idDocumento, "int"));
	$rsUnidad = mysql_query($queryUnidad);
	if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsUnidad = mysql_num_rows($rsUnidad);
	$rowUnidad = mysql_fetch_array($rsUnidad);
	
	$posY = 90;
	imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 18, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,95,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 56, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,380,$posY,str_pad(utf8_decode("TOTAL"), 18, " ", STR_PAD_BOTH),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	
	$posY += 9;
	if ($totalRowsUnidad > 0) {
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
	
		if ($rowConfigDatosGNV['valor'] == 1
		|| ($rowConfigDatosGNV['valor'] == 2
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
		imagestring($img,1,380,$posY,str_pad(formatoNumero($rowUnidad['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);
	
		$posY += 18;
	}
	
	$queryDet = sprintf("SELECT
        cxc_fact_det_acc.id_tipo_accesorio,
		cxc_fact_det_acc.id_factura_detalle_accesorios,
		cxc_fact_det_acc.id_accesorio,
		cxc_fact_det_acc.costo_compra,
		cxc_fact_det_acc.precio_unitario,
		(CASE
			WHEN cxc_fact_det_acc.id_iva = 0 THEN
				CONCAT(acc.nom_accesorio, ' (E)')
			ELSE
				acc.nom_accesorio
		END) AS nom_accesorio,
		cxc_fact_det_acc.tipo_accesorio
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
	WHERE cxc_fact_det_acc.id_factura = %s",
		valTpDato($idDocumento, "int"));
	$rsDet = mysql_query($queryDet);
	if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($rowDet = mysql_fetch_array($rsDet)) {
		imagestring($img,1,95,$posY,strtoupper($rowDet['nom_accesorio']),$textColor);
		imagestring($img,1,380,$posY,strtoupper(str_pad(formatoNumero($rowDet['precio_unitario']), 18, " ", STR_PAD_LEFT)),$textColor);
		
		$posY += 9;
	}
	
	
	$posY = 460;
	
	$posY += 9;
	imagestring($img,1,0,$posY,"OBSERVACIONES :",$textColor);
	
	$arrayObservacionDcto = str_split(strtoupper($rowEncabezado['observacionFactura']), 45);
	if (isset($arrayObservacionDcto)) {
		foreach ($arrayObservacionDcto as $indice => $valor) {
			$posY += 9;
			imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
		}
	}
	
	$posY = 460;
	
	$posY += 9;
	imagestring($img,1,255,$posY,str_pad("SUBTOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal_factura']), 18, " ", STR_PAD_LEFT),$textColor); // <----
	
	if ($rowEncabezado['subtotal_descuento'] > 0) {
		$posY += 9;
		imagestring($img,1,255,$posY,str_pad("DESCUENTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal_descuento']), 18, " ", STR_PAD_LEFT),$textColor); // <----
	}
			
	$queryIvaFact = sprintf("SELECT
		iva.observacion,
		cxc_fact_iva.base_imponible,
		cxc_fact_iva.iva,
		cxc_fact_iva.subtotal_iva
	FROM cj_cc_factura_iva cxc_fact_iva
		INNER JOIN pg_iva iva ON (cxc_fact_iva.id_iva = iva.idIva)
	WHERE id_factura = %s;",
		valTpDato($idDocumento, "int"));
	$rsIvaFact = mysql_query($queryIvaFact);
	if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
		$posY += 9;
		imagestring($img,1,255,$posY,str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,395,$posY,str_pad(formatoNumero($rowIvaFact['base_imponible']), 15, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,255,$posY,str_pad(substr($rowIvaFact['observacion'],0,16), 16, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,350,$posY,str_pad(formatoNumero($rowIvaFact['iva'])."%", 8, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(formatoNumero($rowIvaFact['subtotal_iva']), 15, " ", STR_PAD_LEFT),$textColor);
		
		$totalIva += $rowIvaFact['subtotal_iva'];
	}
	
	$posY += 9;
	imagestring($img,1,255,$posY,str_pad("MONTO EXENTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['monto_exento']), 18, " ", STR_PAD_LEFT),$textColor); // <----
	
	$posY += 9;
	imagestring($img,1,255,$posY,str_pad("MONTO EXONERADO", 16, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['monto_exonerado']), 18, " ", STR_PAD_LEFT),$textColor); // <----
	
	$posY += 8;
	imagestring($img,1,260,$posY,"------------------------------------------",$textColor);
	
	$posY += 8;
	$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
	imagestring($img,1,255,$posY,"TOTAL FACTURA",$textColor);
	imagestring($img,1,340,$posY,":",$textColor);
	imagestring($img,2,362,$posY,str_pad(formatoNumero($totalFactura), 18, " ", STR_PAD_LEFT),$textColor);

	$arrayImg[] = "tmp/"."factura_vehiculo".$pageNum.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
} else if (in_array($rowConfig403['valor'],array(3))) {
	for ($pageNum = 0; $pageNum < 2; $pageNum++) {
		$img = @imagecreate(470, 578) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		
		//////////////////////////////////////////////////////////// COPIA BANCO ////////////////////////////////////////////////////////////
		
		if ($copiaBanco == true) {
		} else {
			// MARCA DE AGUA
			$src = imagecreatefrompng("../img/copiacliente.png");
			if(!imagecopyresampled($img, $src, 0, 40, 0, 0, 470, 500, 470, 500)){//imagen crear, imagen copiar, x,y destino. x,y a copiar, width height a copiar, 100 transparencia
				die ("Error marca de agua");
			}
		}
		
		//ENCABEZADO
		$posY = 0;
		imagestring($img,1,70,$posY,$rowEmp["nombre_empresa"],$textColor);
		
		$direccion = explode("\n",$rowEmp["direccion"]);
		if (isset($direccion)) {
			foreach ($direccion as $indice => $valor) {
				$posY += 9;
				imagestring($img,1,70,$posY,strtoupper(trim($direccion[$indice])),$textColor);
			}
		}
		
		if ($rowEmp["fax"] != ""){
			$fax = " FAX ".$rowEmp["fax"];
		}
		$posY += 8;
		imagestring($img,1,70,$posY,"Tel.: ".$rowEmp["telefono1"]." ".$rowEmp["telefono2"].$fax,$textColor);
		$posY += 8;  	 
		
		
		$posY = 8*2;
		imagestring($img,1,300,$posY,str_pad("FACTURA SERIE - V", 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 8;
		imagestring($img,1,300,$posY,utf8_decode("FACTURA NRO."),$textColor);
		imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numeroFactura'],$textColor);
		
		$posY += 8;
		if ($copiaBanco == true) {
			imagestring($img,1,300,$posY,
				strtoupper(str_pad(utf8_decode("FECHA ENTREGA"), 15, " ", STR_PAD_RIGHT).": ".
				implode("-",array_reverse(explode("-",$rowEncabezado['fecha_entrega'])))),$textColor);
		} else {
			imagestring($img,1,300,$posY,
				strtoupper(str_pad(utf8_decode("FECHA EMISIÓN"), 15, " ", STR_PAD_RIGHT).": ".
				date("d-m-Y", strtotime($rowEncabezado['fechaRegistroFactura']))),$textColor);
		}
		
		//$posY += 8;
		/*imagestring($img,1,300,$posY,
			strtoupper(str_pad(utf8_decode("FECHA VENC."), 15, " ", STR_PAD_RIGHT).": ".
			date("d-m-Y", strtotime($rowEncabezado['fechaVencimientoFactura']))),$textColor);*/
		
		$posY += 8;
		imagestring($img,1,300,$posY,
			strtoupper(str_pad(utf8_decode("PEDIDO NRO."), 15, " ", STR_PAD_RIGHT).": ".
			$rowEncabezado['numeracion_pedido']),$textColor);
		
		$posY += 8;
		imagestring($img,1,300,$posY,
			strtoupper(str_pad(utf8_decode("VENDEDOR"), 15, " ", STR_PAD_RIGHT).": ".
			($rowEncabezado['nombre_empleado'])),$textColor);
		
		
		$posY += 16;
		imageline($img, 0, $posY-5, 468, $posY-5, $textColor); // linea H -
		imagestring($img,1,0,$posY,"CLIENTE: ".strtoupper($rowEncabezado['nombre_cliente']),$textColor);
		imagestring($img,1,230,$posY,str_pad(strtoupper(utf8_decode("CÓDIGO")), 10, " ", STR_PAD_RIGHT).":".str_pad($rowEncabezado['id'], 8, " ", STR_PAD_LEFT),$textColor);
		(in_array($rowConfig409['valor'],array("","1"))) ? imagestring($img,1,330,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($rowEncabezado['ci_cliente']),$textColor) : "";
		
		$direccionCliente = strtoupper(str_replace(";", "", utf8_decode("DIRECCIÓN: ").$rowEncabezado['direccion_cliente']));
		$posY += 8;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,84)),$textColor);
		
		if (strlen($direccionCliente) > 85) {
			$posY += 8;
			imagestring($img,1,0,$posY,trim(substr($direccionCliente,84,84)),$textColor);
		}
		
		if ($rowEncabezado['fecha_nacimiento'] != NULL && $rowEncabezado['fecha_nacimiento'] != "1969-12-31" && $rowEncabezado['fecha_nacimiento'] != "0000-00-00"){
		   $fechaNacimiento = " FECHA NAC.: ".date("m-d-Y", strtotime($rowEncabezado['fecha_nacimiento']));
		}
		
		$posY += 8;
		imagestring($img,1,0,$posY,utf8_decode("TELÉFONOS: ").$rowEncabezado['telf'] ." ".$rowEncabezado['otrotelf'] . "   EMAIL: ".strtoupper($rowEncabezado["correo"]).$fechaNacimiento,$textColor);
		$posY += 8;
		
		imageline($img, 0, $posY + 5, 468, $posY+5, $textColor); // linea H -
		
		// BUSCA LOS DATOS DE LA UNIDAD
		$queryUnidad = sprintf("SELECT 
			uni_bas.nom_uni_bas,
			marca.nom_marca,
			modelo.nom_modelo,
			vers.nom_version,
			ano.nom_ano,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.kilometraje,
			color1.nom_color AS color_externo,
			cxc_fact_det_vehic.precio_unitario,
			uni_bas.com_uni_bas,
			codigo_unico_conversion,
			marca_kit,
			marca_cilindro,
			modelo_regulador,
			serial1,
			serial_regulador,
			capacidad_cilindro,
			fecha_elaboracion_cilindro
		FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
			INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
			INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
		WHERE cxc_fact_det_vehic.id_factura = %s",
			valTpDato($idDocumento, "int"));
		$rsUnidad = mysql_query($queryUnidad);
		if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsUnidad = mysql_num_rows($rsUnidad);
		$rowUnidad = mysql_fetch_array($rsUnidad);
		
		if ($totalRowsUnidad > 0) {
			$posY += 9;
			imagestring($img,1,0,$posY,strtoupper(substr($rowUnidad['nom_uni_bas'],0,20)),$textColor);
			
			imagestring($img,1,190,$posY,str_pad(utf8_decode("AÑO"), 10, " ", STR_PAD_RIGHT),$textColor);
			imagestring($img,1,240,$posY,": ".strtoupper($rowUnidad['nom_ano']),$textColor);
			
			imagestring($img,1,340,$posY,str_pad(utf8_decode("MARCA"), 10, " ", STR_PAD_RIGHT),$textColor);
			imagestring($img,1,390,$posY,": ".strtoupper($rowUnidad['nom_marca']),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad(utf8_decode("MODELO"), 10, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,60,$posY,strtoupper($rowUnidad['nom_modelo']),$textColor);
			
			imagestring($img,1,190,$posY,str_pad(utf8_decode("VERSIÓN"), 10, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,250,$posY,strtoupper(substr($rowUnidad['nom_version'],0,28)),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode($spanPlaca)), 10, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,2,60,$posY-3,strtoupper($rowUnidad['placa']),$textColor);
			
			imagestring($img,1,190,$posY,str_pad(utf8_decode("COLOR"), 10, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,250,$posY,strtoupper(substr($rowUnidad['color_externo'],0,16)),$textColor);
			
			imagestring($img,1,340,$posY,str_pad(strtoupper(utf8_decode($spanKilometraje)), 10, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,400,$posY,strtoupper($rowUnidad['kilometraje']),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode($spanSerialCarroceria)), 10, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,2,60,$posY-3,strtoupper($rowUnidad['serial_carroceria']),$textColor);
			
			imagestring($img,1,190,$posY,str_pad(strtoupper(utf8_decode($spanSerialMotor)), 10, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,250,$posY,strtoupper($rowUnidad['serial_motor']),$textColor);
			
			if ($copiaBanco == true) {
			} else {
				imagestring($img,1,340,$posY,str_pad(strtoupper(utf8_decode("FECHA ENTREGA")), 13, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,415,$posY,implode("-",array_reverse(explode("-",$rowEncabezado['fecha_entrega']))),$textColor);
			}
			
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("CONDICIÓN")), 10, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,60,$posY,strtoupper($rowUnidad['condicion_unidad']),$textColor);
			
			
			$posY += 11; imageline($img, 0, $posY, 468, $posY, $textColor); $posY -= 3; // linea H -
		}
		
		$posY += 8;
		imagestring($img,1,0,$posY,"FORMA PAGO: ".((strlen($rowEncabezado['nombreBanco']) > 0) ? "FINANCIADO" : "CONTADO"),$textColor);
		imagestring($img,1,130,$posY,"FINANCIADO POR: ".$rowEncabezado['nombreBanco'],$textColor);
		
		$queryDet = sprintf("SELECT
				cxc_fact_det_acc.id_tipo_accesorio,
			cxc_fact_det_acc.id_factura_detalle_accesorios,
			cxc_fact_det_acc.id_accesorio,
			cxc_fact_det_acc.costo_compra,
			cxc_fact_det_acc.precio_unitario,
			(CASE
				WHEN cxc_fact_det_acc.id_iva = 0 THEN
					CONCAT(acc.nom_accesorio, ' (E)')
				ELSE
					acc.nom_accesorio
			END) AS nom_accesorio,
			cxc_fact_det_acc.tipo_accesorio,
			
			(SELECT acc_ped.id_condicion_pago FROM an_accesorio_pedido acc_ped
			WHERE acc_ped.id_accesorio = cxc_fact_det_acc.id_accesorio
				AND acc_ped.id_pedido = cxc_fact.numeroPedido) AS id_condicion_pago_accesorio,
			
			(SELECT acc_ped.id_condicion_mostrar FROM an_accesorio_pedido acc_ped
			WHERE acc_ped.id_accesorio = cxc_fact_det_acc.id_accesorio
				AND acc_ped.id_pedido = cxc_fact.numeroPedido) AS id_condicion_mostrar_accesorio
		FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
			INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
			INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
		WHERE cxc_fact_det_acc.id_factura = %s",
			valTpDato($idDocumento, "int"));
		$rsDet = mysql_query($queryDet);
		if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		$montoPrecioVenta = 0;
		$arrayContrato = array();
		$arrayAdicionales = array();
		$arrayAdicionalesPagados = array();
		$totalContrato = 0;
		$totalAdicionales = 0;
		$totalAdicionalesPagados = 0;
		while ($rowDet = mysql_fetch_array($rsDet)) {
			if ($rowDet['id_tipo_accesorio'] == 1) { // 1 = Adicionales
				if ($copiaBanco == true && $rowDet['id_condicion_pago_accesorio'] == 1) { // 1 = Pagado, 2 = Financiado
					$totalAdicionalesPagados += $rowDet['precio_unitario'];
					$arrayAdicionalesPagados[] = array(
						"nom_accesorio" => $rowDet['nom_accesorio'],
						"precio_unitario" => $rowDet['precio_unitario']);
				} else if ($copiaBanco == true && $rowDet['id_condicion_mostrar_accesorio'] == 1) { // Null = Individual, 1 = En Precio de Venta
					$montoPrecioVenta += $rowDet['precio_unitario'];
				} else if ($copiaBanco != true
				|| ($copiaBanco == true && $rowDet['id_condicion_pago_accesorio'] != 1) // 1 = Pagado, 2 = Financiado
				|| ($copiaBanco == true && $rowDet['id_condicion_mostrar_accesorio'] != 1)) { // Null = Individual, 1 = En Precio de Venta
					$totalAdicionales += $rowDet['precio_unitario'];
					$arrayAdicionales[] = array(
						"nom_accesorio" => $rowDet['nom_accesorio'],
						"precio_unitario" => $rowDet['precio_unitario']);
				}
			} else if ($rowDet['id_tipo_accesorio'] == 3) { // 3 = Contratos
				$totalContrato += $rowDet['precio_unitario'];
				$arrayContrato[] = array(
					"nom_accesorio" => $rowDet['nom_accesorio'],
					"precio_unitario" => $rowDet['precio_unitario']);
				
			}
		}
		
		// PAGOS DE CONTADO Y PAGOS BONO (7 = Anticipo, 8 = Nota de Crédito)
		$queryPagos = sprintf("SELECT 
			(SELECT
				SUM(cxc_pago.montoPagado)
			FROM an_pagos cxc_pago                   
			WHERE cxc_pago.id_factura = %s 
				AND (cxc_pago.formaPago NOT IN (7,8)
					OR (cxc_pago.formaPago IN (8)
							AND (((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
									WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento
										AND cxc_nc.idDocumento <> cxc_pago.id_factura) LIKE 'FA')
									OR ((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
										WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) LIKE 'NC'))
							AND cxc_pago.numeroDocumento NOT IN (SELECT tradein_cxc.id_nota_credito_cxc FROM an_tradein_cxc tradein_cxc
																WHERE tradein_cxc.id_anticipo IS NOT NULL
																	AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)))
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_contado,
			
			(SELECT
				SUM(DISTINCT cxc_pago.montoPagado)
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND (SELECT COUNT(cxc_pago2.idAnticipo) FROM cj_cc_detalleanticipo cxc_pago2
					WHERE cxc_pago2.idAnticipo = cxc_pago.numeroDocumento
						AND cxc_pago2.id_concepto IS NOT NULL) = 0
				AND cxc_ant_det.id_concepto IS NULL
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_anticipo,
			
			(SELECT 
				SUM(DISTINCT cxc_pago.montoPagado)
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (2)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_tradein,
			
			(SELECT 
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (7,8)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_pnd,
			
			(SELECT
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (1,6)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_bono",
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"));
		$rsPagos = mysql_query($queryPagos);
		if (!$rsPagos) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagos); }    
		$rowPagos = mysql_fetch_assoc($rsPagos);
		
		// PAGOS DE NOTAS DE CREDITO CON ANTIPO CERO O NEGATIVO
		$queryPagosOtro = sprintf("SELECT
			(SELECT motivo.descripcion
			FROM cj_cc_notacredito cxc_nc
				INNER JOIN pg_motivo motivo ON (cxc_nc.id_motivo = motivo.id_motivo)
			WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) AS descripcion,
			cxc_pago.montoPagado
		FROM an_pagos cxc_pago                   
		WHERE cxc_pago.id_factura = %s 
			AND (cxc_pago.formaPago IN (8)
					AND (((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento
								AND cxc_nc.idDocumento <> cxc_pago.id_factura) LIKE 'FA')
							OR ((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
								WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) LIKE 'NC'))
					AND cxc_pago.numeroDocumento IN (SELECT tradein_cxc.id_nota_credito_cxc FROM an_tradein_cxc tradein_cxc
													WHERE tradein_cxc.id_anticipo IS NOT NULL
														AND tradein_cxc.id_nota_credito_cxc IS NOT NULL
														AND tradein_cxc.id_anticipo IN (SELECT cxc_ant.idAnticipo FROM cj_cc_anticipo cxc_ant
																						WHERE cxc_ant.montoNetoAnticipo = 0)))
			AND cxc_pago.estatus IN (1,2)
			AND cxc_pago.id_condicion_mostrar = 1",
			valTpDato($idDocumento, "int"));
		/*$rsPagosOtro = mysql_query($queryPagosOtro);
		if (!$rsPagosOtro) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagosOtro); }    
		$totalRowsPagosOtro = mysql_num_rows($rsPagosOtro);
		$pagosNCTradeIn = 0;
		while ($rowPagosOtro = mysql_fetch_assoc($rsPagosOtro)) {
			$pagosNCTradeIn += $rowPagosOtro['montoPagado'];
		}*/
		
		// PAGOS DE CONTADO Y PAGOS BONO (7 = Anticipo, 8 = Nota de Crédito)
		$queryPagosMostrarTradeIn = sprintf("SELECT 
			(SELECT
				SUM(cxc_pago.montoPagado)
			FROM an_pagos cxc_pago                   
			WHERE cxc_pago.id_factura = %s 
				AND (cxc_pago.formaPago NOT IN (7,8)
					OR (cxc_pago.formaPago IN (8)
							AND (((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
									WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento
										AND cxc_nc.idDocumento <> cxc_pago.id_factura) LIKE 'FA')
									OR ((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
										WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) LIKE 'NC'))
							AND cxc_pago.numeroDocumento NOT IN (SELECT tradein_cxc.id_nota_credito_cxc FROM an_tradein_cxc tradein_cxc
																WHERE tradein_cxc.id_anticipo IS NOT NULL
																	AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)))
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_contado,
			
			(SELECT
				SUM(DISTINCT cxc_pago.montoPagado)
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND (SELECT COUNT(cxc_pago2.idAnticipo) FROM cj_cc_detalleanticipo cxc_pago2
					WHERE cxc_pago2.idAnticipo = cxc_pago.numeroDocumento
						AND cxc_pago2.id_concepto IS NOT NULL) = 0
				AND cxc_ant_det.id_concepto IS NULL
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_anticipo,
			
			(SELECT 
				SUM(DISTINCT cxc_pago.montoPagado)
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (2)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_tradein,
			
			(SELECT 
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (7,8)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_pnd,
			
			(SELECT
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (1,6)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_bono",
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"));
		$rsPagosMostrarTradeIn = mysql_query($queryPagosMostrarTradeIn);
		if (!$rsPagosMostrarTradeIn) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagos); }    
		$rowPagosMostrarTradeIn = mysql_fetch_assoc($rsPagosMostrarTradeIn);
		
		// PAGOS DE CONTADO Y PAGOS BONO (7 = Anticipo, 8 = Nota de Crédito)
		$queryPagosMostrarContado = sprintf("SELECT 
			(SELECT 
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (7,8)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 1
			) AS pagos_pnd,
			
			(SELECT
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (1,6)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 1
			) AS pagos_bono",
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"));
		$rsPagosMostrarContado = mysql_query($queryPagosMostrarContado);
		if (!$rsPagosMostrarContado) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagos); }    
		$rowPagosMostrarContado = mysql_fetch_assoc($rsPagosMostrarContado);
		
		$pagosContado = $rowPagos['pagos_contado'] + $rowPagos['pagos_anticipo'];
		$pagosContado -= ($copiaBanco == true) ? $totalAdicionalesPagados : 0;
		
		$pagosTradeIn = $rowPagos['pagos_tradein'];
		$pagosPND = $rowPagos['pagos_pnd'];
		$pagosBono = $rowPagos['pagos_bono'] + $pagosNCTradeIn;
		
		// SUMA EN PAGO DE CONTADO AQUELLOS PAGOS SELECCIONADOS SOLO EN LA COPIA DEL BANCO
		$pagosContado += (($copiaBanco == true) ? $rowPagosMostrarContado['pagos_pnd'] : 0);
		$pagosContado += (($copiaBanco == true) ? $rowPagosMostrarContado['pagos_bono'] : 0);
		
		$pagosPND -= (($copiaBanco == true) ? $rowPagosMostrarTradeIn['pagos_pnd'] + $rowPagosMostrarContado['pagos_pnd'] : 0);
		$pagosBono -= (($copiaBanco == true) ? $rowPagosMostrarTradeIn['pagos_bono'] + $rowPagosMostrarContado['pagos_bono'] : 0);
		
		
		// CONSULTO SI LA FACTURA TIENE PAGO ANTICIPO Y ES DE TIPO TRADE-IN
		$queryTradein = sprintf("SELECT DISTINCT
			tradein.id_tradein,
			cxc_pago.montoPagado,
			cxc_ant.saldoAnticipo,
			tradein.allowance,
			tradein.payoff,
			tradein.acv,
			tradein.total_credito,
			marca.nom_marca,
			uni_fis.placa,
			uni_fis.serial_carroceria, 
			uni_fis.kilometraje,
			uni_fis.serial_motor,
			color1.nom_color AS color_externo,
			ano.nom_ano,
			modelo.nom_modelo,
			CONCAT_WS(' ', ano.nom_ano, modelo.nom_modelo) AS ano_modelo,
			prov.nombre AS nombre_cliente_adeudado
		FROM an_pagos cxc_pago
			INNER JOIN cj_cc_anticipo cxc_ant ON (cxc_pago.numeroDocumento = cxc_ant.idAnticipo)
			INNER JOIN an_tradein tradein ON (cxc_ant.idAnticipo = tradein.id_anticipo)
			LEFT JOIN an_tradein_cxp tradein_cxp ON (tradein.id_tradein = tradein_cxp.id_tradein
				AND (tradein_cxp.estatus = 1 OR (tradein_cxp.estatus IS NULL AND DATE(tradein_cxp.fecha_anulado) > cxc_pago.fechaPago)))
			LEFT JOIN cp_proveedor prov ON (tradein_cxp.id_proveedor = prov.id_proveedor)
			INNER JOIN an_unidad_fisica uni_fis ON (tradein.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN an_uni_bas ON (uni_fis.id_uni_bas = an_uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
			INNER JOIN an_marca marca ON (an_uni_bas.mar_uni_bas = marca.id_marca)
			INNER JOIN an_modelo modelo ON (an_uni_bas.mod_uni_bas = modelo.id_modelo)
			LEFT JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
		WHERE cxc_pago.id_factura = %s
			AND cxc_pago.formaPago IN (7)
			AND cxc_pago.estatus IN (1)
			AND cxc_ant.estatus = 1
		LIMIT 2;", // 7 = Anticipo, 1 = Activo
			valTpDato($idDocumento, "int"));
		$rsTradein = mysql_query($queryTradein);
		if (!$rsTradein) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__); }
		$totalRowsTradein = mysql_num_rows($rsTradein);
		
		if ($totalRowsTradein > 0) {
			$posY += 16;
			// TRADEIN
			imagestring($img,1,0,$posY,str_pad(utf8_decode("VEHÍCULO TOMADO A CAMBIO"), 44, " ", STR_PAD_BOTH),$textColor);
		}
		
		$posYVenta = $posY; // es usado en los accesorios del recuadro de la derecha
		
		$posY += 11; imageline($img, 0, $posY, 230, $posY, $textColor); $posY -= 3; // linea H -
		$contTradeIn = 0;
		while ($rowTradein = mysql_fetch_assoc($rsTradein)) {
			$contTradeIn++;
			
			$posY += 8;
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("MARCA"), 14, " ", STR_PAD_RIGHT).": ".substr($rowTradein["nom_marca"],0,10)),$textColor);
			
			$posY += 8;
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("MODELO"), 14, " ", STR_PAD_RIGHT).": ".substr($rowTradein['nom_modelo'],0,12)),$textColor);
			
			
			$posY += 8;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode($spanSerialCarroceria)), 14, " ", STR_PAD_RIGHT),$textColor);
			imagestring($img,2,75,$posY-3,": ".strtoupper($rowTradein['serial_carroceria']),$textColor);
			
			$posY += 8;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode($spanPlaca)), 10, " ", STR_PAD_RIGHT),$textColor);
			imagestring($img,2,55,$posY-3,": ".strtoupper($rowTradein['placa']),$textColor);   
			
			imagestring($img,1,115,$posY,strtoupper(str_pad(utf8_decode($spanKilometraje), 10, " ", STR_PAD_RIGHT).": ".$rowTradein['kilometraje']),$textColor);
			
			//imagestring($img,1,330,$posY,utf8_decode("STOCK"),$textColor);
			//imagestring($img,1,355,$posY,": ".strtoupper($rowTradein['serial_motor']),$textColor);
			
			$posY += 8;        
			
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("AÑO"), 10, " ", STR_PAD_RIGHT).": ".$rowTradein['nom_ano']),$textColor);
			
			imagestring($img,1,100,$posY,strtoupper(str_pad(utf8_decode("COLOR"), 6, " ", STR_PAD_RIGHT).": ".substr($rowTradein['color_externo'],0,20)),$textColor);
			
			$posY += 8;
			imagestring($img,1,5,$posY,utf8_decode("ADEUDADO A:"),$textColor);
			imagestring($img,1,55,$posY,": ".strtoupper(substr($rowTradein['nombre_cliente_adeudado'],0,28)),$textColor);        
			
			$posY += 8;
			imagestring($img,1,65,$posY,strtoupper(substr($rowTradein['nombre_cliente_adeudado'],28,28)),$textColor);
			
			
			$montoAllowance = $rowTradein['allowance'];
			$montoACV = $rowTradein['acv'];
			$montoUpsideDown = $rowTradein['allowance'] - $rowTradein['acv'];
			
			$montoACV += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_pnd'] : 0);
			$montoACV += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_bono'] : 0);
			$montoPayoff = $rowTradein['payoff'];
			$montoCreditoNeto = $montoACV + $montoUpsideDown - $montoPayoff;
			if ($copiaBanco == true) {
				if ($rowTradein['payoff'] > $rowTradein['allowance'] && in_array($rowEncabezado['id_credito_tradein'], array(1))) {
					$montoACV = $rowTradein['payoff'] - $montoUpsideDown;
					$montoACV += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_pnd'] : 0);
					$montoACV += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_bono'] : 0);
					$montoPayoff = $rowTradein['payoff'];
					$montoCreditoNeto = $montoACV + $montoUpsideDown - $montoPayoff;
					$montoPrecioVenta += $rowTradein['payoff'] - $rowTradein['allowance'];
				}
			}
			$pagosTradeIn += ($montoCreditoNeto >= 0) ? 0 : $montoCreditoNeto;
			
			$posY += 8;
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("CRÉDITO POR AUTO USADO"), 26, " ", STR_PAD_RIGHT)).": ",$textColor);
			imagestring($img,1,140,$posY,str_pad(formatoNumero($montoACV), 17, " ", STR_PAD_LEFT),$textColor);
			
			if ($montoUpsideDown > 0) {
				// PAGOS DE NOTAS DE CREDITO CON ANTIPO CERO O NEGATIVO
				$queryPagosUpsideDown = sprintf("SELECT
					(SELECT motivo.descripcion
					FROM cj_cc_notacredito cxc_nc
						INNER JOIN pg_motivo motivo ON (cxc_nc.id_motivo = motivo.id_motivo)
					WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) AS descripcion,
					cxc_pago.montoPagado
				FROM an_pagos cxc_pago                   
				WHERE cxc_pago.id_factura = %s 
					AND (cxc_pago.formaPago IN (8)
							AND (((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
									WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento
										AND cxc_nc.idDocumento <> cxc_pago.id_factura) LIKE 'FA')
									OR ((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
										WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) LIKE 'NC'))
							AND cxc_pago.numeroDocumento IN (SELECT tradein_cxc.id_nota_credito_cxc FROM an_tradein_cxc tradein_cxc
															WHERE tradein_cxc.id_tradein = %s
																AND tradein_cxc.id_anticipo IS NOT NULL
																AND tradein_cxc.id_nota_credito_cxc IS NOT NULL
																AND tradein_cxc.id_anticipo IN (SELECT cxc_ant.idAnticipo FROM cj_cc_anticipo cxc_ant
																								WHERE cxc_ant.montoNetoAnticipo = 0)))
					AND cxc_pago.estatus IN (1,2)",
					valTpDato($idDocumento, "int"),
					valTpDato($rowTradein['id_tradein'], "int"));
				$rsPagosUpsideDown = mysql_query($queryPagosUpsideDown);
				if (!$rsPagosUpsideDown) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagosUpsideDown); }
				$totalRowsPagosUpsideDown = mysql_num_rows($rsPagosUpsideDown);
				$rowPagosUpsideDown = mysql_fetch_assoc($rsPagosUpsideDown);
				
				$posY += 8;
				imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode($rowPagosUpsideDown['descripcion']), 26, " ", STR_PAD_RIGHT)).": ",$textColor);
				imagestring($img,1,140,$posY,str_pad(formatoNumero($montoUpsideDown), 17, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 8;
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("BALANCE ADEUDADO"), 26, " ", STR_PAD_RIGHT)).": ",$textColor);
			imagestring($img,1,140,$posY,str_pad(formatoNumero($montoPayoff), 17, " ", STR_PAD_LEFT),$textColor);
			
			$posY += 6;
			imagestring($img,1,140,$posY,strtoupper(str_pad("", 17, "-", STR_PAD_RIGHT)),$textColor);
			
			$posY += 8;
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("CRÉDITO NETO"), 26, " ", STR_PAD_RIGHT)).": ",$textColor);
			imagestring($img,2,125,$posY-3,str_pad(formatoNumero($montoCreditoNeto), 17, " ", STR_PAD_LEFT),$textColor);
			
			$posY += 11; imageline($img, 0, $posY, 230, $posY, $textColor); $posY -= 3; // linea H -
		}
		
		if ($copiaBanco == true) {
			if ($pagosPND > 0 && in_array($rowConfig208['valor'], array(1))) {
				$montoPrecioVenta -= $pagosPND;
				$pagosPND -= $pagosPND;
			}
		}
		
		$posY += 8;        
		imagestring($img,1,5,$posY,str_pad(utf8_decode("PAGO CONTADO"), 26, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,140,$posY,str_pad(formatoNumero($pagosContado), 17, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 8;
		imagestring($img,1,5,$posY,str_pad(utf8_decode("PND"), 26, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,140,$posY,str_pad(formatoNumero($pagosPND), 17, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 8;
		if ($copiaBanco == true) {
			imagestring($img,1,5,$posY,str_pad(utf8_decode("OTROS PAGOS"), 26, " ", STR_PAD_RIGHT).":",$textColor);
		} else {
			// PAGOS DE NOTAS DE CREDITO CON ANTIPO CERO O NEGATIVO
			/*$rsPagosOtro = mysql_query($queryPagosOtro);
			if (!$rsPagosOtro) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagosBono); }    
			$totalRowsPagosOtro = mysql_num_rows($rsPagosOtro);*/
			
			// PAGOS BONO
			$queryPagosBono = sprintf("SELECT DISTINCT
				concepto_forma_pago.descripcion,
				IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado) AS montoPagado,
				IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0),
					(IFNULL(cxc_pago.montoPagado,0)
						- IFNULL(cxc_ant_det.montoDetalleAnticipo,0)
						- IFNULL((SELECT SUM(cxc_ant_det2.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_ant_det2
							WHERE cxc_ant_det2.idAnticipo = cxc_ant.idAnticipo
								AND (id_concepto IS NULL OR id_concepto NOT IN (1,6))),0)), cxc_pago.montoPagado) AS montoPagado2
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON (cxc_pago.numeroDocumento = cxc_ant.idAnticipo)
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON (cxc_ant.idAnticipo = cxc_ant_det.idAnticipo)
				LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_ant_det.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.id_factura = %s
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (1,6)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1",
				valTpDato($idDocumento, "int"));
			$rsPagosBono = mysql_query($queryPagosBono);
			if (!$rsPagosBono) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagosBono); }    
			$totalRowsPagosBono = mysql_num_rows($rsPagosBono);
			if ($totalRowsPagosOtro > 0 || $totalRowsPagosBono > 0) {
				imagestring($img,1,5,$posY,str_pad(utf8_decode("OTROS PAGOS"), 26, " ", STR_PAD_RIGHT),$textColor);
				
				/*while ($rowPagosOtro = mysql_fetch_assoc($rsPagosOtro)) {
					$posY += 8;
					imagestring($img,1,15,$posY,str_pad(substr(strtoupper(utf8_decode($rowPagosOtro['descripcion'])),0,23), 24, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,140,$posY,str_pad(formatoNumero($rowPagosOtro['montoPagado']), 17, " ", STR_PAD_LEFT),$textColor);
				}*/
				
				while ($rowPagosBono = mysql_fetch_assoc($rsPagosBono)) {
					$posY += 8;
					imagestring($img,1,15,$posY,str_pad(substr(strtoupper(utf8_decode($rowPagosBono['descripcion'])),0,23), 24, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,140,$posY,str_pad(formatoNumero($rowPagosBono['montoPagado']), 17, " ", STR_PAD_LEFT),$textColor);
				}
				
				$posY += 6;
				imagestring($img,1,140,$posY,strtoupper(str_pad("", 17, "-", STR_PAD_RIGHT)),$textColor);
				$posY += 8;
				imagestring($img,1,5,$posY,str_pad(utf8_decode("TOTAL OTROS PAGOS"), 26, " ", STR_PAD_RIGHT).":",$textColor);
			} else {
				imagestring($img,1,5,$posY,str_pad(utf8_decode("OTROS PAGOS"), 26, " ", STR_PAD_RIGHT).":",$textColor);
			}
		}
		imagestring($img,1,140,$posY,str_pad(formatoNumero($pagosBono), 17, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 8;
		imagestring($img,1,5,$posY,str_pad(utf8_decode("MONTO POLIZA"), 26, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,140,$posY,str_pad(formatoNumero($rowTradein['']), 17, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 6;
		imagestring($img,1,140,$posY,strtoupper(str_pad("", 17, "-", STR_PAD_RIGHT)),$textColor);
		
		$posY += 8;
		imagestring($img,1,5,$posY,str_pad(utf8_decode("CRÉDITO A FAVOR"), 26, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,140,$posY,str_pad(formatoNumero($pagosContado + $pagosPND + $pagosBono), 17, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 8;
		imagestring($img,1,5,$posY,str_pad(utf8_decode("VENC. POLIZA"), 26, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,140,$posY,str_pad($rowTradein[''], 17, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 8;
		imagestring($img,1,5,$posY,utf8_decode("ASEGURADORA: "),$textColor);
		imagestring($img,1,70,$posY,strtoupper(substr("",0,27)),$textColor);
		
		$posY += 11; imageline($img, 0, $posY, 230, $posY, $textColor); $posY -= 3; // linea H -
		if ($totalRowsTradein > 0) {
			$posY += 7;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("NOTA: EL COMPRADOR CERTIFICA QUE LA UNIDAD")), 44, " ", STR_PAD_BOTH),$textColor);
			$posY += 7;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("TOMADA A CAMBIO ESTÁ LIBRE DE CUALQUIER")), 44, " ", STR_PAD_BOTH),$textColor);
			$posY += 7;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("GRAVAMEN O VENTA CONDICIONAL. ASÍ MISMO, SE")), 44, " ", STR_PAD_BOTH),$textColor);
			$posY += 7;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("PACTA QUE DE HABER CUALQUIER DEUDA, EL")), 44, " ", STR_PAD_BOTH),$textColor);
			$posY += 7;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("COMPRADOR SE HARÁ RESPONSABLE. EJ. MULTAS,")), 44, " ", STR_PAD_BOTH),$textColor);
			$posY += 7;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("SEGURO, ETC.")), 44, " ", STR_PAD_BOTH),$textColor);
		}
		
		$posY += 8;
		imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("SE COBRARÁ 0.95 CENTAVOS POR CADA MILLA")), 44, " ", STR_PAD_BOTH),$textColor);
		$posY += 7;
		imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("CORRIDA A PARTIR DE LA FECHA DE COMPRA")), 44, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 11;
		imageline($img, 0, $posYVenta + 11, 0, $posY, $textColor);//linea V |
		imageline($img, 230, $posYVenta + 11, 230, $posY, $textColor);//linea V |
		imageline($img, 0, $posY, 230, $posY, $textColor); // linea H -
		$posY -= 3;
		
		$creditoTotal = $pagosContado + $pagosTradeIn + $pagosPND + $pagosBono;
		
		$posY += 8;
		imagestring($img,1,0,$posY,str_pad(utf8_decode("CRÉDITO TOTAL"), 26, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,2,125,$posY-3,str_pad(formatoNumero($creditoTotal), 17, " ", STR_PAD_LEFT),$textColor);
		
		if (strlen($rowEncabezado['nombreBanco']) > 0) {
			$posY += 16;
			imagestring($img,1,0,$posY,str_pad(utf8_decode("CRÉDITO APROBADO BANCO"), 26, " ", STR_PAD_RIGHT).": ",$textColor);
			$posY += 8;
			imagestring($img,1,20,$posY,utf8_decode($rowEncabezado['nombreBanco']),$textColor);
		}
		
		if ($rowEncabezado["meses_financiar"] > 0){
			$mesesFinanciar = $rowEncabezado["meses_financiar"]." MESES. APR: ".$rowEncabezado["interes_cuota_financiar"]." %";
		}
		if ($mesesFinanciar) {
			$posY += 8;
			imagestring($img,1,0,$posY,"FINANCIAMIENTO EN: ".$mesesFinanciar,$textColor);
			//$posY += 8;
			//imagestring($img,1,0,$posY,"PRIMER PAGO MENSUAL DE: ".formatoNumero($rowEncabezado["cuotas_financiar"]),$textColor);
			$posY += 8;
			imagestring($img,1,20,$posY,($rowEncabezado["meses_financiar"])." PAGOS MENSUALES DE: ".formatoNumero($rowEncabezado["cuotas_financiar"]),$textColor);
		}
		
		$arrayObservacionDcto = str_split(strtoupper($rowEncabezado['observacionFactura']), 45);
		if (isset($arrayObservacionDcto)) {
			if (strlen($arrayObservacionDcto[0]) > 0) {
				$posY += 8;
				imagestring($img,1,0,$posY,utf8_decode("OBSERVACIONES: "),$textColor);
			}
			
			foreach ($arrayObservacionDcto as $indice => $valor) {
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
			}
		}
		
		$posY = $posYVenta;//a partir de la linea del recuadro inicial
		
		$posY += 11; imageline($img, 235, $posY, 468, $posY, $textColor); $posY -= 3; // linea H -
		
		$posY += 9;
		$montoPrecioVenta = $rowUnidad['precio_unitario'] + $montoPrecioVenta;
		imagestring($img,1,240,$posY,"PRECIO VENTA:",$textColor);
		imagestring($img,1,370,$posY,str_pad(formatoNumero($montoPrecioVenta), 18, " ", STR_PAD_LEFT),$textColor);
		$posY += 9;
		
		foreach ($arrayAdicionales as $key => $accAdi){
			$posY += 9;
			imagestring($img,1,240,$posY,strtoupper($accAdi['nom_accesorio']),$textColor);	
			imagestring($img,1,370,$posY,str_pad(formatoNumero($accAdi['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);
		}
		
		$posY += 11; imageline($img, 235, $posY, 468, $posY, $textColor); $posY -= 3; // linea H -
		$totalVehiculoAdicionales = $montoPrecioVenta + $totalAdicionales;
		$posY += 9;
		imagestring($img,1,240,$posY,"TOTAL:",$textColor);	
		imagestring($img,2,353,$posY-3,str_pad(formatoNumero($totalVehiculoAdicionales), 18, " ", STR_PAD_LEFT),$textColor);
		$posY += 11; imageline($img, 235, $posY, 468, $posY, $textColor); $posY -= 3; // linea H -
		
		$posY += 9;
		imagestring($img,1,240,$posY,utf8_decode("TOTAL CRÉDITO:"),$textColor);	
		imagestring($img,1,370,$posY,str_pad(formatoNumero($creditoTotal), 18, " ", STR_PAD_LEFT),$textColor);
		
		$balancePagar = $totalVehiculoAdicionales - $creditoTotal;
		$posY += 14;
		imagestring($img,1,240,$posY,utf8_decode("BALANCE A PAGAR:"),$textColor);	
		imagestring($img,2,353,$posY-3,str_pad(formatoNumero($balancePagar), 18, " ", STR_PAD_LEFT),$textColor);
		$posY += 11; imageline($img, 235, $posY, 468, $posY, $textColor); $posY -= 3; // linea H -
		
		$posY += 9;
		imagestring($img,1,240,$posY,"OTROS:",$textColor);	
		$posY += 9;
		
		foreach ($arrayContrato as $key => $contratos){
			$posY += 9;
			imagestring($img,1,240,$posY,strtoupper(substr($contratos['nom_accesorio'],0,30)),$textColor);	
			imagestring($img,1,370,$posY,str_pad(formatoNumero($contratos['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);
		}
		
		
		$posY += 18;
		imagestring($img,1,240,$posY,"TOTAL:",$textColor);	
		imagestring($img,1,370,$posY,str_pad(formatoNumero($totalContrato), 18, " ", STR_PAD_LEFT),$textColor);
		$posY += 11; imageline($img, 235, $posY, 468, $posY, $textColor); $posY -= 3; // linea H -
		
		
		$posY += 9;
		imagestring($img,1,240,$posY,utf8_decode("BALANCE DE CONTRATO:"),$textColor);	
		imagestring($img,2,353,$posY-3,str_pad(formatoNumero($balancePagar + $totalContrato), 18, " ", STR_PAD_LEFT),$textColor);
		
		$posYVenta += 11;
		$posY += 11;
		imageline($img, 235, $posYVenta, 235, $posY, $textColor);//linea V |
		imageline($img, 468, $posYVenta, 468, $posY, $textColor);//linea V |
		imageline($img, 235, $posY, 468, $posY, $textColor); // linea H -
		$posY -= 3;
		
		if (count($arrayAdicionalesPagados) > 0) {
			$posY += 9;
			imagestring($img,1,240,$posY,"ADICIONALES PAGADOS:",$textColor);	
			$posY += 9;
		}
		foreach ($arrayAdicionalesPagados as $indice => $valor){
			$posY += 9;
			imagestring($img,1,240,$posY,strtoupper($valor['nom_accesorio']),$textColor);	
			imagestring($img,1,370,$posY,str_pad(formatoNumero($valor['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);
		}
		
		
		$posY = 562;
		imagestring($img,1,0,$posY,utf8_decode("______________________"),$textColor);
		imagestring($img,1,180,$posY,utf8_decode("______________________"),$textColor);
		imagestring($img,1,360,$posY,utf8_decode("______________________"),$textColor);
		
		$posY += 9; 
		imagestring($img,1,0,$posY,str_pad(utf8_decode("FIRMA DEL CLIENTE"), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,180,$posY,str_pad(utf8_decode("FIRMA DEL GERENTE"), 20, " ", STR_PAD_LEFT),$textColor);	
		imagestring($img,1,360,$posY,str_pad(utf8_decode("FIRMA DEL VENDEDOR"), 20, " ", STR_PAD_LEFT),$textColor);	
		
		$arrayImg[] = "tmp/"."factura_vehiculo".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
		
		$copiaBanco = true;
	}
}


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Vehículos)
$queryConfig206 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 206 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig206 = mysql_query($queryConfig206, $conex);
if (!$rsConfig206) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig206 = mysql_num_rows($rsConfig206);
$rowConfig206 = mysql_fetch_assoc($rsConfig206);

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
$rutaLogo = "../../".$rowEmp["logo_familia"];

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, $rowConfig206['valor'], 580, 688);
		
		if ($idEmpresa > 0 && $rowConfig403['valor'] == 3) {
			$pdf->Image($rutaLogo,15,$rowConfig206['valor'] + 5,80);
		}
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

function formatoNumero($monto){
    return number_format($monto, 2, ".", ",");
}
?>