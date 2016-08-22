<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_historico_cierre_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();

$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp, $conex);
if (!$rsEmp) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

(strlen($rowEmp['telefono1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono1'] : "";
(strlen($rowEmp['telefono2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono2'] : "";
(strlen($rowEmp['telefono_taller1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller1'] : "";
(strlen($rowEmp['telefono_taller2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller2'] : "";

(isset($_GET['tipoPago'])) ? $arrayTipoPago = explode(",",$_GET['tipoPago']) : ""; // 0 = Crédito, 1 = Contado
$arrayDescripcionTipoPago = array(0 => "Crédito", 1 => "Contado");

// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato(1, "int")); // 1 = Empresa cabecera
$rsConfig400 = mysql_query($queryConfig400);
if (!$rsConfig400) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRowsConfig400 = mysql_num_rows($rsConfig400);
$rowConfig400 = mysql_fetch_assoc($rsConfig400);

if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
	$andEmpresaAPER = sprintf(" AND ape.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
	$andEmpresaAPER = "";
}
	
$queryAperturaCaja = sprintf("SELECT *,
	(CASE ape.statusAperturaCaja
		WHEN 0 THEN 'CERRADA TOTALMENTE'
		WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja,'%s'))
		WHEN 2 THEN 'CERRADA PARCIALMENTE'
		ELSE 'CERRADA TOTALMENTE'
	END) AS estatus_apertura_caja
FROM ".$apertCajaPpal." ape
	INNER JOIN caja ON (ape.idCaja = caja.idCaja)
	INNER JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
WHERE cierre.idCierre = %s %s;",
	valTpDato("%d-%m-%Y", "campo"),
	$_GET['idCierre'],
	$andEmpresaAPER);
$rsAperturaCaja = mysql_query($queryAperturaCaja);
if (!$rsAperturaCaja) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);

$fechaApertura = $rowAperturaCaja['fechaAperturaCaja'];

switch($rowAperturaCaja['statusAperturaCaja']) {
	case 0 : $classApertura = "divMsjError"; break;
	case 1 : $classApertura = "divMsjInfo"; break;
	case 2 : $classApertura = "divMsjAlerta"; break;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Pagos Cargados del Día <?php echo $arrayDescripcionTipoPago[$arrayTipoPago[0]]; ?></title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css">
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	
	<script language="JavaScript">
	function printPage() {
		byId("hddPresionoImprimirCorteCaja").value = 1;
		
		for (cont = 1; byId('tblPagos' + cont) != undefined; cont++) {
			byId('tblPagos' + cont).border = '1';
			byId('tblPagos' + cont).className = 'tabla';
			//byId('tblPagos' + cont).setAttribute("style", "border-collapse:collapse;");
		}
		byId('divButtons').style.visibility = 'hidden';
		window.print();
		for (cont = 1; byId('tblPagos' + cont) != undefined; cont++) {
			byId('tblPagos' + cont).border = '0';
			byId('tblPagos' + cont).className = '';
			//byId('tblPagos' + cont).removeAttribute("style");
		}
		byId('divButtons').style.visibility = 'visible';
	}
	</script>
</head>
<body>
<div id="divGeneralPorcentaje">
	<table border="0" width="100%">
	<tr>
		<td>
			<table>
            <tr align="left">
                <td>
                    <table style="text-align:center; background:#FFF; border-radius:0.4em;">
                    <tr>
                        <td><img id="imgLogoEmpresa" name="imgLogoEmpresa" src="../<?php echo $rowEmp['logo_familia']; ?>" width="180"></td>
                    </tr>
                    </table>
                </td>
                <td class="textoNegroNegrita_10px" style="padding:4px">
                    <p>
                        <?php echo utf8_encode($rowEmp['nombre_empresa']); ?>
                        <br>
                        <?php echo utf8_encode($rowEmp['rif']); ?>
                        <br>
                        <?php echo utf8_encode($rowEmp['direccion']); ?>
                        <br>
                        <?php echo (count($arrayTelefonos) > 0) ? "Telf.: ".implode(" / ", $arrayTelefonos): ""; ?>
                        <br>
                        <?php echo utf8_encode($rowEmp['web']); ?>
                    </p>
                </td>
            </tr>
            </table>
		</td>
	</tr>
	<tr>
    	<td>
        	<table>
            <tr align="left" height="24">
                <td width="120"></td>
                <td width="200"></td>
                <td align="right" class="tituloCampo" width="120">Fecha Actual:</td>
                <td align="center" width="200"><?php echo date("d-m-Y"); ?></td>
            </tr>
            <tr align="left" height="24">
                <td align="right" class="tituloCampo">Fecha Apertura:</td>
                <td align="center"><?php echo date("d-m-Y",strtotime($rowAperturaCaja['fechaAperturaCaja'])); ?></td>
                <td align="right" class="tituloCampo">Ejecución Apertura:</td>
                <td align="center"><?php echo date("d-m-Y H:i:s",strtotime($rowAperturaCaja['fechaAperturaCaja']." ".$rowAperturaCaja['horaApertura'])); ?></td>
            </tr>
            <tr align="left" height="24" <?php if (in_array($rowAperturaCaja['statusAperturaCaja'], array(1,2))) { echo "style=\"display:none\""; } ?>>
                <td align="right" class="tituloCampo">Fecha Cierre:</td>
                <td align="center"><?php echo date("d-m-Y",strtotime($rowAperturaCaja['fechaCierre'])); ?></td>
                <td align="right" class="tituloCampo">Ejecución Cierre:</td>
                <td align="center"><?php echo date("d-m-Y H:i:s",strtotime($rowAperturaCaja['fechaEjecucionCierre']." ".$rowAperturaCaja['horaEjecucionCierre'])); ?></td>
            </tr>
            <tr align="left" height="24">
	            <td></td>
	            <td></td>
                <td align="right" class="tituloCampo">Estado de Caja:</td>
                <td align="center" class="<?php echo $classApertura; ?>"><?php echo $rowAperturaCaja['estatus_apertura_caja']; ?></td>
            </tr>
            </table>
		</td>
	</tr>
	<tr>
		<td align="center"><strong>CORTE DE CAJA <?php echo strtoupper($arrayDescripcionTipoPago[$arrayTipoPago[0]]); ?><br>(<?php echo $nombreCajaPpal; ?>)<br>RECIBOS POR MEDIO DE PAGO</strong></td>
	</tr>
	</table>	
	<br>
	
<?php
if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
	$andEmpresaPagoFA = sprintf(" AND cxc_fact.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$andEmpresaPagoND = sprintf(" AND cxc_nd.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$andEmpresaPagoAN = sprintf(" AND cxc_ant.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$andEmpresaPagoCH = sprintf(" AND cxc_ch.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$andEmpresaPagoTB = sprintf(" AND cxc_tb.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
	$andEmpresaPagoFA = "";
	$andEmpresaPagoND = "";
	$andEmpresaPagoAN = "";
	$andEmpresaPagoCH = "";
	$andEmpresaPagoTB = "";
}

if ((count($arrayTipoPago) > 1 && in_array(0, $arrayTipoPago) && in_array(1, $arrayTipoPago)) || !isset($arrayTipoPago)) { // 0 = Crédito, 1 = Contado
	$andCondicionPagoFA = "";
	$andCondicionPagoND = "";
	$andCondicionPagoAN = "";
	$andCondicionPagoCH = "";
	$andCondicionPagoTB = "";
} else if (count($arrayTipoPago) > 0 && in_array(0, $arrayTipoPago)) {
	$andCondicionPagoFA = " AND cxc_fact.condicionDePago = 0";
	$andCondicionPagoND = " AND cxc_nd.fechaRegistroNotaCargo <> '".$fechaApertura."'";
	$andCondicionPagoAN = " AND cxc_ant.fechaAnticipo <> '".$fechaApertura."'";
	$andCondicionPagoCH = " AND cxc_ch.fecha_cheque <> '".$fechaApertura."'";
	$andCondicionPagoTB = " AND cxc_tb.fecha_transferencia <> '".$fechaApertura."'";
} else if (count($arrayTipoPago) > 0 && in_array(1, $arrayTipoPago)) {
	$andCondicionPagoFA = " AND cxc_fact.condicionDePago = 1";
	$andCondicionPagoND = " AND cxc_nd.fechaRegistroNotaCargo = '".$fechaApertura."'";
	$andCondicionPagoAN = " AND cxc_ant.fechaAnticipo = '".$fechaApertura."'";
	$andCondicionPagoCH = " AND cxc_ch.fecha_cheque = '".$fechaApertura."'";
	$andCondicionPagoTB = " AND cxc_tb.fecha_transferencia = '".$fechaApertura."'";
}

$queryFormaPago = "SELECT
	query.formaPago,
	query.nombreFormaPago,
	query.tipoDoc,
	query.id_empresa
FROM (SELECT
		cxc_pago.formaPago,
		forma_pago.nombreFormaPago,
		1 AS tipoDoc,
		cxc_fact.id_empresa AS id_empresa
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		INNER JOIN formapagos forma_pago on (cxc_pago.formaPago = forma_pago.idFormaPago)
		INNER JOIN bancos banco ON (cxc_pago.bancoOrigen = banco.idBanco)
	WHERE cxc_pago.idCierre = ".$_GET['idCierre']."
	GROUP BY cxc_pago.formaPago, cxc_fact.id_empresa
							
	UNION ALL
	
	SELECT
		cxc_pago.idFormaPago AS formaPago,
		forma_pago.nombreFormaPago,
		2 AS tipoDoc,
		cxc_nd.id_empresa AS id_empresa
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
		INNER JOIN formapagos forma_pago on (cxc_pago.idFormaPago = forma_pago.idFormaPago)
		INNER JOIN bancos banco ON (cxc_pago.bancoOrigen = banco.idBanco)
	WHERE cxc_pago.idCierre = ".$_GET['idCierre']."	
	GROUP BY cxc_pago.idFormaPago, cxc_nd.id_empresa
					
	UNION ALL
	
	SELECT
		cxc_pago.id_forma_pago AS formaPago,
		forma_pago.nombreFormaPago,
		4 AS tipoDoc,
		cxc_ant.id_empresa AS id_empresa
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		INNER JOIN formapagos forma_pago on (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
		INNER JOIN bancos banco ON (cxc_pago.bancoClienteDetalleAnticipo = banco.idBanco)
	WHERE cxc_pago.idCierre = ".$_GET['idCierre']."
	GROUP BY cxc_pago.id_forma_pago, cxc_ant.id_empresa
	
	UNION ALL
	
	SELECT
		2 AS formaPago,
		(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 2) AS nombreFormaPago,
		5 AS tipoDoc,
		cxc_ch.id_empresa AS id_empresa
	FROM cj_cc_cheque cxc_ch
	WHERE cxc_ch.idCierre = ".$_GET['idCierre']."
	GROUP BY 2, cxc_ch.id_empresa
	
	UNION ALL
	
	SELECT
		4 AS formaPago,
		(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 4) AS nombreFormaPago,
		6 AS tipoDoc,
		cxc_tb.id_empresa AS id_empresa
	FROM cj_cc_transferencia cxc_tb
	WHERE cxc_tb.idCierre = ".$_GET['idCierre']."
	GROUP BY 2, cxc_tb.id_empresa) AS query
		
GROUP BY query.formaPago, query.id_empresa
ORDER BY query.formaPago";
$rsFormaPago = mysql_query($queryFormaPago); //echo $queryFormaPago."<br><br>";
if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$sw = "";
while ($rowFormaPago = mysql_fetch_array($rsFormaPago)) {
	$idFormaPago = $rowFormaPago['formaPago'];
	$nombreFormaPago = $rowFormaPago['nombreFormaPago'];
	
	if ($sw != $idFormaPago) {
		$queryPago = "SELECT 
			cxc_pago.idPago,
			'FACTURA' AS tipoDoc,
			cxc_fact.idDepartamentoOrigenFactura AS idDepartamento,
			cxc_fact.idFactura AS id_documento_pagado,
			cxc_fact.numeroFactura AS numero_documento,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPago,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			cxc_pago.formaPago,
			forma_pago.nombreFormaPago,
			NULL AS id_concepto,
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento_pago,
			cxc_pago.bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'sa_iv_pagos' AS tabla,
			'idPago' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago on (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_pago.fechaPago = '".$fechaApertura."'
			AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
			AND (cxc_pago.formaPago NOT IN (2,4)
				OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
				OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
			AND cxc_pago.tomadoEnComprobante = 1
			AND cxc_pago.formaPago = ".$idFormaPago."
			".$andEmpresaPagoFA." ".$andCondicionPagoFA."
			
		UNION
		
		SELECT 
			cxc_pago.id_det_nota_cargo AS idPago,
			'NOTA DEBITO' AS tipoDoc,
			cxc_nd.idDepartamentoOrigenNotaCargo AS idDepartamento,
			cxc_nd.idNotaCargo AS id_documento_pagado,
			cxc_nd.numeroNotaCargo AS numero_documento,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPago,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			cxc_pago.idFormaPago AS formaPago,
			forma_pago.nombreFormaPago,
			NULL AS id_concepto,
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento_pago,
			cxc_pago.bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.monto_pago AS montoPagado,
			cxc_pago.estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'cj_det_nota_cargo' AS tabla,
			'id_det_nota_cargo' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			INNER JOIN formapagos forma_pago on (cxc_pago.idFormaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_nd.idDepartamentoOrigenNotaCargo = recibo.id_departamento AND recibo.idTipoDeDocumento = 2)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_pago.fechaPago = '".$fechaApertura."'
			AND cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
			AND (cxc_pago.idFormaPago NOT IN (2,4)
				OR (cxc_pago.id_cheque IS NULL AND cxc_pago.idFormaPago IN (2))
				OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.idFormaPago IN (4)))
			AND cxc_pago.tomadoEnComprobante = 1
			AND cxc_pago.idFormaPago = ".$idFormaPago."
			".$andEmpresaPagoND." ".$andCondicionPagoND."
		
		UNION
		
		SELECT 
			cxc_pago.idDetalleAnticipo AS idPago,
			CONCAT_WS(' ', 'ANTICIPO', IF(concepto_forma_pago.descripcion IS NOT NULL, CONCAT('(', concepto_forma_pago.descripcion, ')'), NULL)) AS tipoDoc,
			cxc_ant.idDepartamento AS idDepartamento,
			cxc_ant.idAnticipo AS id_documento_pagado,
			cxc_ant.numeroAnticipo AS numero_documento,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPagoAnticipo AS fechaPago,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			cxc_pago.id_forma_pago AS formaPago,
			forma_pago.nombreFormaPago,
			cxc_pago.id_concepto AS id_concepto,
			cxc_pago.numeroControlDetalleAnticipo AS numero_documento_pago,
			cxc_pago.bancoClienteDetalleAnticipo AS bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoCompaniaDetalleAnticipo AS bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoDetalleAnticipo AS montoPagado,
			cxc_ant.estatus AS estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'cj_cc_detalleanticipo' AS tabla,
			'idDetalleAnticipo' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
			INNER JOIN formapagos forma_pago on (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoClienteDetalleAnticipo = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoCompaniaDetalleAnticipo = banco_destino.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion AND cxc_ant.idDepartamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'AN')
			LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_pago.fechaPagoAnticipo = '".$fechaApertura."'
			AND cxc_ant.idDepartamento IN (".valTpDato($idModuloPpal, "campo").")
			AND (cxc_pago.id_forma_pago NOT IN (2,4)
				OR (cxc_pago.id_cheque IS NULL AND cxc_pago.id_forma_pago IN (2))
				OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.id_forma_pago IN (4)))
			AND cxc_pago.id_forma_pago = ".$idFormaPago."
			".$andEmpresaPagoAN." ".$andCondicionPagoAN."
		
		UNION
		
		SELECT 
			cxc_ch.id_cheque AS idPago,
			'CHEQUE' AS tipoDoc,
			cxc_ch.id_departamento AS idDepartamento,
			NULL AS id_documento_pagado,
			'-' AS numero_documento,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_ch.fecha_cheque AS fechaPago,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			2 AS formaPago,
			(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 2) AS nombreFormaPago,
			NULL AS id_concepto,
			cxc_ch.numero_cheque AS numero_documento_pago,
			cxc_ch.id_banco_cliente AS bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			1 AS bancoDestino,
			'-' AS nombre_banco_destino,
			'-' AS cuentaEmpresa,
			cxc_ch.idCaja,
			cxc_ch.total_pagado_cheque AS montoPagado,
			cxc_ch.estatus AS estatus,
			cxc_ch.estatus AS estatus_pago,
			DATE(cxc_ch.fecha_anulado) AS fecha_anulado,
			'cj_cc_cheque' AS tabla,
			'id_cheque' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_cheque cxc_ch
			INNER JOIN cj_cc_cliente cliente ON (cxc_ch.id_cliente = cliente.id)
			INNER JOIN bancos banco_origen on (cxc_ch.id_banco_cliente = banco_origen.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_ch.id_cheque = recibo.idDocumento AND cxc_ch.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'CH')
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ch.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_ch.fecha_cheque = '".$fechaApertura."'
			AND cxc_ch.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
			AND cxc_ch.tomadoEnComprobante = 1
			AND 2 = ".$idFormaPago."
			".$andEmpresaPagoCH." ".$andCondicionPagoCH."
		
		UNION
		
		SELECT 
			cxc_tb.id_transferencia AS idPago,
			'TRANSFERENCIA' AS tipoDoc,
			cxc_tb.id_departamento AS idDepartamento,
			NULL AS id_documento_pagado,
			'-' AS numero_documento,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_tb.fecha_transferencia AS fechaPago,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			4 AS formaPago,
			(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 4) AS nombreFormaPago,
			NULL AS id_concepto,
			cxc_tb.numero_transferencia AS numero_documento_pago,
			cxc_tb.id_banco_cliente AS bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			1 AS bancoDestino,
			'-' AS nombre_banco_destino,
			'-' AS cuentaEmpresa,
			cxc_tb.idCaja,
			cxc_tb.total_pagado_transferencia AS montoPagado,
			cxc_tb.estatus AS estatus,
			cxc_tb.estatus AS estatus_pago,
			DATE(cxc_tb.fecha_anulado) AS fecha_anulado,
			'cj_cc_transferencia' AS tabla,
			'id_transferencia' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_transferencia cxc_tb
			INNER JOIN cj_cc_cliente cliente ON (cxc_tb.id_cliente = cliente.id)
			INNER JOIN bancos banco_origen on (cxc_tb.id_banco_cliente = banco_origen.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_tb.id_transferencia = recibo.idDocumento AND cxc_tb.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'TB')
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_tb.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_tb.fecha_transferencia = '".$fechaApertura."'
			AND cxc_tb.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
			AND cxc_tb.tomadoEnComprobante = 1
			AND 4 = ".$idFormaPago."
			".$andEmpresaPagoTB." ".$andCondicionPagoTB.";"; //echo $queryPago."<br><br>";
		$rsPago = mysql_query($queryPago);
		if (!$rsPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsPago = mysql_num_rows($rsPago);
		if ($totalRowsPago > 0) {
			$cont++; ?>
			<table id="<?php echo "tblPagos".$cont; ?>" border="0" class="texto_9px" width="100%">
			<tr>
				<td class="tituloArea" colspan="10"><?php echo $nombreFormaPago; ?></td>
			</tr>
			<tr align="center" class="tituloColumna">
				<td width="7%">Tipo Documento</td>
				<td width="6%">Nro. Dcto. Pagado</td>
				<td width="6%">Nro. Recibo</td>
				<td width="7%"><?php echo $spanClienteCxC; ?></td>
				<td width="16%">Cliente</td>
				<td width="8%"><?php echo ($idFormaPago != 1) ? "Nro. ".$nombreFormaPago : "-"; ?></td>
				<td width="15%">Banco Cliente</td>
				<td width="15%">Banco Empresa</td>
				<td width="14%">Nro. Cuenta</td>
				<td width="6%">Monto</td>
			</tr>
			<?php
			$totalPagos = 0;
			$contFila = 0;
			while ($rowPago = mysql_fetch_array($rsPago)) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				// ESTATUS DEL PAGO
				/*$classDcto = "";
				$estatusDcto = "";
				if ($rowPago['estatus'] == NULL) { // 0 = ANULADO
					$classDcto = "divMsjError";
					$estatusDcto = "<br>ANULADO";
				}*/
				
				// PAGO DE FACTURA DEVUELTA
				$classPago = "";
				$estatusPago = "";
				if ($rowPago['estatus_pago'] == NULL && $rowPago['fechaPago'] == $rowPago['fecha_anulado']){ // Null = Anulado, 1 = Activo, 2 = Pendiente
					$classPago = "divMsjError";
					$estatusPago = '<br>[PAGO ANULADO]';
				} else if ($rowPago['estatus_pago'] == NULL && $rowPago['fechaPago'] != $rowPago['fecha_anulado']){
					$classPago = "divMsjInfo5";
					$estatusPago = '<br>[PAGO PENDIENTE ANULADO]';
				} else if ($rowPago['estatus_pago'] == 2) {
					$classPago = "divMsjAlerta";
					$estatusPago = '<br>[PAGO PENDIENTE]';
				} else if (in_array($rowPago['id_concepto'], array(6,7,8))) {
					$classPago = "divMsjAlerta";
				}
				
				switch ($rowPago['tabla']) {
					case 'sa_iv_pagos' : // FACTURA
						$aVerDcto = sprintf("<a href=\"javascript:verVentana('reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s', 960, 550);\" class=\"noprint\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
							$rowPago['id_recibo_pago']);
						break;
					case 'cj_det_nota_cargo' : // NOTA DE CARGO
						$aVerDcto = sprintf("<a href=\"javascript:verVentana('reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s', 960, 550);\" class=\"noprint\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
							$rowPago['id_recibo_pago']);
						break;
					case 'cj_cc_detalleanticipo' : // ANTICIPO
						$aVerDcto = sprintf("<a href=\"javascript:verVentana('reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s', 960, 550);\" class=\"noprint\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
							$rowPago['id_recibo_pago']);
						break;
					case 'cj_cc_cheque' : // CHEQUE
						$aVerDcto = sprintf("<a href=\"javascript:verVentana('reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s', 960, 550);\" class=\"noprint\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
							$rowPago['id_recibo_pago']);
						break;
					case 'cj_cc_transferencia' : // TRANSFERENCIA
						$aVerDcto = sprintf("<a href=\"javascript:verVentana('reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s', 960, 550);\" class=\"noprint\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
							$rowPago['id_recibo_pago']);
						break;
				}
				
				$totalPagos += ($rowPago['estatus_pago'] == NULL && $rowPago['fechaPago'] == $rowPago['fecha_anulado']) ? 0 : $rowPago['montoPagado']; ?>
				<tr align="left" class="<?php echo $clase; ?>" height="22">
					<td align="center" class="<?php echo $classDcto." ".$classPago; ?>"><?php echo utf8_encode($rowPago["tipoDoc"]).$estatusDcto.$estatusPago; ?></td>
					<td align="right" class="<?php echo $classDcto." ".$classPago; ?>"><?php echo $rowPago["numero_documento"]; ?></td>
					<td align="right" class="<?php echo $classDcto." ".$classPago; ?>">
                    	<table width="100%">
                        <tr>
                        	<td><?php echo $aVerDcto; ?></td>
                            <td align="right"><?php echo $rowPago["nro_comprobante"]; ?></td>
                		</tr>
                        </table>
                    </td>
					<td align="right" class="<?php echo $classDcto." ".$classPago; ?>"><?php echo $rowPago['ci_cliente']; ?></td>
					<td class="<?php echo $classDcto." ".$classPago; ?>"><?php echo utf8_encode(strtoupper($rowPago["nombre_cliente"])); ?></td>
					<td align="right" class="<?php echo $classDcto." ".$classPago; ?>"><?php echo $rowPago['numero_documento_pago']; ?></td>
					<td class="<?php echo $classDcto." ".$classPago; ?>"><?php echo utf8_encode($rowPago['nombre_banco_origen']); ?></td>
					<td class="<?php echo $classDcto." ".$classPago; ?>"><?php echo utf8_encode($rowPago['nombre_banco_destino']); ?></td>
					<td align="center" class="<?php echo $classDcto." ".$classPago; ?>"><?php echo $rowPago["cuentaEmpresa"]; ?></td>
					<td align="right" class="<?php echo $classDcto." ".$classPago; ?>"><?php echo number_format($rowPago['montoPagado'],2,".",","); ?></td>
				</tr>
			<?php
			} ?>
			<tr align="right" height="22">
				<td class="tituloColumna" colspan="9">Total en <?php echo $nombreFormaPago; ?>:</td>
				<td class="trResaltarTotal"><?php echo number_format($totalPagos,2,".",","); ?></td>
			</tr>
			</table>
		<?php
		}
		echo "</br>";
	}
	$sw = $idFormaPago;
}


if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
	$andEmpresaVentaContadoFA = sprintf(" AND cxc_fact.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$andEmpresaVentaContadoND = sprintf(" AND cxc_nd.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$andEmpresaVentaContadoAN = sprintf(" AND cxc_ant.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$andEmpresaVentaContadoCH = sprintf(" AND cxc_ch.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$andEmpresaVentaContadoTB = sprintf(" AND cxc_tb.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
	$andEmpresaVentaContadoFA = "";
	$andEmpresaVentaContadoND = "";
	$andEmpresaVentaContadoAN = "";
	$andEmpresaVentaContadoCH = "";
	$andEmpresaVentaContadoTB = "";
}

$queryVentaContado = "SELECT
	SUM(IFNULL(cxc_pago.montoPagado, 0)) AS total,
	cxc_pago.estatus,
	cxc_pago.estatus AS estatus_pago
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
WHERE cxc_pago.fechaPago = '".$fechaApertura."'
	AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
	AND (cxc_pago.formaPago NOT IN (2,4)
		OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
		OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
	AND cxc_pago.tomadoEnComprobante = 1
	AND cxc_pago.formaPago IN (SELECT idFormaPago FROM formapagos WHERE idFormaPago NOT IN (7,8))
	AND (cxc_pago.estatus IN (1)
		OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado)))
	".$andEmpresaVentaContadoFA." ".$andCondicionPagoFA."

UNION ALL

SELECT
	SUM(IFNULL(cxc_pago.monto_pago, 0)) AS total,
	cxc_pago.estatus,
	cxc_pago.estatus AS estatus_pago
FROM cj_cc_notadecargo cxc_nd
	INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
WHERE cxc_pago.fechaPago = '".$fechaApertura."'
	AND cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
	AND (cxc_pago.idFormaPago NOT IN (2,4)
		OR (cxc_pago.id_cheque IS NULL AND cxc_pago.idFormaPago IN (2))
		OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.idFormaPago IN (4)))
	AND cxc_pago.tomadoEnComprobante = 1
	AND cxc_pago.idFormaPago IN (SELECT idFormaPago FROM formapagos WHERE idFormaPago NOT IN (7,8))
	AND (cxc_pago.estatus IN (1)
		OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado)))
	".$andEmpresaVentaContadoND." ".$andCondicionPagoND."

UNION ALL

SELECT
	SUM(IFNULL(cxc_pago.montoDetalleAnticipo, 0)) AS total,
	cxc_ant.estatus,
	cxc_pago.estatus AS estatus_pago
FROM cj_cc_anticipo cxc_ant
	INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
WHERE cxc_pago.fechaPagoAnticipo = '".$fechaApertura."'
	AND cxc_ant.idDepartamento IN (".valTpDato($idModuloPpal, "campo").")
	AND (cxc_pago.id_forma_pago NOT IN (2,4)
		OR (cxc_pago.id_cheque IS NULL AND cxc_pago.id_forma_pago IN (2))
		OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.id_forma_pago IN (4)))
	AND cxc_pago.tipoPagoDetalleAnticipo IN (SELECT aliasFormaPago FROM formapagos)
	AND (cxc_pago.id_concepto IS NULL OR cxc_pago.id_concepto IN (SELECT id_concepto FROM cj_conceptos_formapago WHERE id_concepto NOT IN (6,7,8)))
	AND cxc_ant.estatus IN (1)
	AND (cxc_pago.estatus IN (1)
		OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado)))
	".$andEmpresaVentaContadoAN." ".$andCondicionPagoAN."
GROUP BY cxc_ant.estatus

UNION ALL

SELECT 
	SUM(IFNULL(cxc_ch.total_pagado_cheque, 0)) AS total,
	cxc_ch.estatus,
	cxc_ch.estatus AS estatus_pago
FROM cj_cc_cheque cxc_ch
WHERE cxc_ch.fecha_cheque = '".$fechaApertura."'
	AND cxc_ch.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
	AND cxc_ch.tomadoEnComprobante = 1
	AND (cxc_ch.estatus IN (1)
		OR (cxc_ch.estatus IS NULL AND cxc_ch.fecha_cheque <> DATE(cxc_ch.fecha_anulado)))
	".$andEmpresaPagoCH." ".$andCondicionPagoCH."
GROUP BY cxc_ch.estatus

UNION ALL

SELECT 
	SUM(IFNULL(cxc_tb.total_pagado_transferencia, 0)) AS total,
	cxc_tb.estatus,
	cxc_tb.estatus AS estatus_pago
FROM cj_cc_transferencia cxc_tb
WHERE cxc_tb.fecha_transferencia = '".$fechaApertura."'
	AND cxc_tb.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
	AND cxc_tb.tomadoEnComprobante = 1
	AND (cxc_tb.estatus IN (1)
		OR (cxc_tb.estatus IS NULL AND cxc_tb.fecha_transferencia <> DATE(cxc_tb.fecha_anulado)))
	".$andEmpresaPagoTB." ".$andCondicionPagoTB."
GROUP BY cxc_tb.estatus;"; //echo $queryVentaContado."<br><br>";
$rsVentaContado = mysql_query($queryVentaContado);
if (!$rsVentaContado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalVentaContado = 0;
while ($rowVentaContado = mysql_fetch_assoc($rsVentaContado)) {
	$totalVentaContado += $rowVentaContado['total'];
}


if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
	$andEmpresaVentaCredito = sprintf(" AND cxc_fact.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
	$andEmpresaVentaCredito = "";
}

$queryVentaCredito = "SELECT
	SUM(cxc_fact.montoTotalFactura) AS monto_total_ventas_credito
FROM cj_cc_encabezadofactura cxc_fact
WHERE cxc_fact.fechaRegistroFactura = '".$fechaApertura."'
	AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
	AND cxc_fact.condicionDePago = 0
	AND ((cxc_fact.anulada = 'SI'
			AND (SELECT COUNT(cxc_nc.idNotaCredito) FROM cj_cc_notacredito cxc_nc
				WHERE cxc_nc.idDocumento = cxc_fact.idFactura
					AND cxc_nc.tipoDocumento LIKE 'FA'
					AND cxc_nc.fechaNotaCredito = cxc_fact.fechaRegistroFactura) = 0)
		OR cxc_fact.anulada <> 'SI') ".$andEmpresaVentaCredito.";";
$rsVentaCredito = mysql_query($queryVentaCredito);
if (!$rsVentaCredito) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowVentaCredito = mysql_fetch_assoc($rsVentaCredito); ?>
	
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr id="tblVentasAContado" align="right" height="24">
        <td class="tituloColumna" width="94%">Total Pagos:</td>
        <td class="trResaltarTotal2" width="6%"><?php echo number_format($totalVentaContado, 2, ".", ","); ?></td>
    </tr>
    <tr id="tblVentasACredito" align="right" height="24" <?php if (count($arrayTipoPago) > 0) { echo "style=\"display:none\""; } ?>>
        <td class="tituloColumna">Ventas a Crédito:</td>
        <td class="trResaltarTotal2"><?php echo number_format($rowVentaCredito['monto_total_ventas_credito'], 2, ".", ","); ?></td>
    </tr>
    <tr id="tblVentasContadoCredito" align="right" height="24" <?php if (count($arrayTipoPago) > 0) { echo "style=\"display:none\""; } ?>>
        <td class="tituloColumna">Total Pagos + Ventas a Crédito:</td>
        <td class="trResaltarTotal3"><?php echo number_format($rowVentaCredito['monto_total_ventas_credito'] + $totalVentaContado, 2, ".", ","); ?></td>
    </tr>
    </table>
	
    <div id="divButtons" name="divButtons">
        <table border="0" width="100%">
        <tr>
            <td align="right"><hr>
            <form id="frmCerrar" name="frmCerrar" method="post">
                <button type="button" id="btnImprimir" name="btnImprimir" onclick="printPage();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                <button type="button" id="btnVolver" name="btnVolver" onclick="top.history.back()"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/return.png"/></td><td>&nbsp;</td><td>Volver</td></tr></table></button>
                <input type="hidden" id="hddPresionoImprimirCorteCaja" name="hddPresionoImprimirCorteCaja" value="0"/>
            </form>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>