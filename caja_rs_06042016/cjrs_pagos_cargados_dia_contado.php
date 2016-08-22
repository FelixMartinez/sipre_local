<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_pagos_cargados_dia_contado"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_pagos_cargados_dia.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Caja de Repuestos y Servicios - Histórico de Recibos por Medio de Pago/Contado</title>
	<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
	
	<script language="JavaScript">
	function printPage() {
		document.getElementById("ocultoPresionoImprimirCorteDeCaja").value = 1;
		
		for (cont = 1; document.getElementById('tblPagos' + cont) != undefined; cont++) {
			document.getElementById('tblPagos' + cont).border = '1';
			document.getElementById('tblPagos' + cont).className = 'tabla';
			//document.getElementById('tblPagos' + cont).setAttribute("style", "border-collapse:collapse;");
		}
		document.getElementById('divButtons').style.visibility = 'hidden';
		window.print();
		for (cont = 1; document.getElementById('tblPagos' + cont) != undefined; cont++) {
			document.getElementById('tblPagos' + cont).border = '0';
			document.getElementById('tblPagos' + cont).className = '';
			//document.getElementById('tblPagos' + cont).removeAttribute("style");
		}
		document.getElementById('divButtons').style.visibility = 'visible';
	}
	</script>
	
	<style>
		.tituloArea{
			border:0px;
		}
	</style>
</head>
<body>
<div id="divGeneralPorcentaje">
	<?php
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$rs = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_assoc($rs);
	?>
	<table border="0" width="100%">
	<tr>
		<td align="left" colspan="2">
			<table>
			<tr>
				<td><img src="../<?php echo $row['logo_familia'];?>" height="90"></td>
				<td class="textoNegroNegrita_10px">
					<table width="100%">
					<tr align="left">
						<td><?php echo utf8_encode($row['nombre_empresa']); ?></td>
					</tr>
					<tr align="left">
						<td><?php echo $spanRIF; ?>: <?php echo utf8_encode($row['rif']); ?></td>
					</tr>
				<?php if (strlen($row['direccion']) > 1) { ?>
					<tr align="left">
						<td>
							<?php
							$direcEmpresa = $row['direccion'].".";
							$telfEmpresa = "";
							if (strlen($row['telefono1']) > 1) {
								$telfEmpresa .= "Telf.: ".$row['telefono1'];
							}
							if (strlen($row['telefono2']) > 1) {
								$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
								$telfEmpresa .= $row['telefono2'];
							}
							
							echo utf8_encode($direcEmpresa." ".$telfEmpresa); ?>
						 </td>
					</tr>
				<?php } ?>
					<tr align="left">
						<td><?php echo utf8_encode($row['web']); ?></td>
					</tr>
					</table>
				</td>
			</tr>
			</table>
		</td>
	</tr>
	<tr align="left" height="22">
		<td align="right" class="tituloCampo" width="10%">Fecha:</td>
		<td width="90%"><?php echo $fechaDeImpresionDelCierreCaja = date("d-m-Y").' / '.date("H:i:s");?></td>
	</tr>
	<tr>
		<?php
		if ($_GET['acc'] == 2)
			echo "<td align='center' colspan='2'><strong>CORTE DE CAJA (REPUESTOS Y SERVICIOS)<BR>RECIBOS POR MEDIO DE PAGO</strong></td>";
		else
			echo "<td align='center' colspan='2'><strong>PAGOS CARGADOS DEL DÍA - CONTADO (REPUESTOS Y SERVICIOS)</strong></td>";
		?>
	</tr>
	</table>	
	<br>
	
<?php
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato(1, "int")); // 1 = Empresa cabecera
$rsConfig400 = mysql_query($queryConfig400);
if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRowsConfig400 = mysql_num_rows($rsConfig400);
$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
	$andEmpresa1 = sprintf(" AND sa_iv_apertura.id_empresa = %s",
		valTpDato($idEmpresa,"int"));
	$andEmpresa2 = sprintf(" AND vw_pagos_del_dia_rs_contado.id_empresa = %s",
		valTpDato($idEmpresa,"int"));
	$andFormaPago = sprintf(" AND an.id_empresa = %s",
		valTpDato($idEmpresa,"int"));
	$andFormaPago2 = sprintf(" AND fv.id_empresa = %s",
		valTpDato($idEmpresa,"int"));
		
} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
	$andEmpresa1 = '';
	$andEmpresa2 = '';
	$andFormaPago = '';
	$andFormaPago2 = '';
}
					
if ($_GET['acc'] == 2){
	
	$sqlFechaActual = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura
	WHERE idCaja = 2
		AND statusAperturaCaja IN(1,2) ".$andEmpresa."");
	$rsFechaActual = mysql_query($sqlFechaActual);
	$rowFechaActual = mysql_fetch_array($rsFechaActual);
	
	$fechaActual = $rowFechaActual['fechaAperturaCaja'];
	
} else {
	
	$fechaActual = date("Y-m-d");
}

$sqlFormaPago = "SELECT 
		vw_pagos_del_dia_rs_contado.formaPago,
		vw_pagos_del_dia_rs_contado.tipoDoc,
		vw_pagos_del_dia_rs_contado.id_empresa,
		formapagos.nombreFormaPago
	FROM vw_pagos_del_dia_rs_contado,
		formapagos
	WHERE vw_pagos_del_dia_rs_contado.formaPago = formapagos.idFormaPago
	".$andEmpresa2."
	GROUP BY formaPago, vw_pagos_del_dia_rs_contado.id_empresa
	ORDER BY vw_pagos_del_dia_rs_contado.formaPago";
$consultaFormaPago = mysql_query($sqlFormaPago) or die(mysql_error());
$sw = '';
$existePagoEnEfectivoOcheque = 0;
while ($rowFormaPago = mysql_fetch_array($consultaFormaPago)) {
	$nombreFormaPago = $rowFormaPago["nombreFormaPago"];
	$idFormaPago = $rowFormaPago["formaPago"];
	
	if ($sw != $idFormaPago) {
		if ($idFormaPago == 1) {
			$existePagoEnEfectivoOcheque = 1;
			$registro = "EF";
		} else if ($idFormaPago == 2) {
			$existePagoEnEfectivoOcheque = 1;
			$registro = "CH";
		} else if ($idFormaPago == 3) {
			$registro = "DP";
		} else if ($idFormaPago == 4) {
			$registro = "TB";
		} else if ($idFormaPago == 5) {
			$registro = "TC";
		} else if ($idFormaPago == 6) {
			$registro = "TD";
		} else if ($idFormaPago == 7) {
			$registro = "AN";
		} else if ($idFormaPago == 8) {
			$registro = "NC";
		} else if ($idFormaPago == 9) {
			$registro = "RC";
		} else if ($idFormaPago == 10) {
			$registro = "ISLR";
		} else if ($idFormaPago == 11) {
			$registro = "CB";
		}
		
		$sqlMostrarPorFormaPago = "SELECT 
			pa.idDetalleAnticipo AS idPago,
			pa.tipoPagoDetalleAnticipo AS formaPago,
			'ANTICIPO' AS tipoDoc,
			pg_reportesimpresion.numeroReporteImpresion AS nro_comprobante,
			an.numeroAnticipo AS idDocumento,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			pa.numeroControlDetalleAnticipo AS numeroDocumento,
			pa.bancoClienteDetalleAnticipo AS bancoOrigen,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoClienteDetalleAnticipo) AS nombre_banco_origen,
			pa.bancoCompaniaDetalleAnticipo AS bancoDestino,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoCompaniaDetalleAnticipo) AS nombre_banco_destino,
			pa.numeroCuentaCompania AS cuentaEmpresa,
			pa.montoDetalleAnticipo AS montoPagado,
			an.estatus AS estatus
		FROM cj_cc_anticipo an
			INNER JOIN pg_reportesimpresion ON (an.idAnticipo = pg_reportesimpresion.idDocumento)
			INNER JOIN cj_cc_cliente ON (an.idCliente = cj_cc_cliente.id),
			cj_cc_detalleanticipo pa
		WHERE pa.fechaPagoAnticipo = '".$fechaActual."'
			AND pg_reportesimpresion.id_departamento IN (0,1,3)
			AND an.idAnticipo = pa.idAnticipo
			AND an.idDepartamento IN (0,1,3)
			".$andFormaPago."
			AND pa.tipoPagoDetalleAnticipo = '".$registro."'
			
		UNION
		
		SELECT 
			pa.idPago,
			pa.formaPago,
			'FACTURA' AS tipoDoc,
			cj_encabezadorecibopago.numeroComprobante AS nro_comprobante,
			fv.numeroFactura AS idDocumento,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			(CASE pa.formaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = pa.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = pa.numeroDocumento)
				ELSE
					pa.numeroDocumento
			END) AS numeroDocumento,
			pa.bancoOrigen,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoOrigen) AS nombre_banco_origen,
			pa.bancoDestino,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoDestino) AS nombre_banco_destino,
			pa.cuentaEmpresa,
			pa.montoPagado,
			1 AS estatus
		FROM cj_cc_encabezadofactura fv
			INNER JOIN cj_encabezadorecibopago ON (fv.idFactura = cj_encabezadorecibopago.numero_tipo_documento)
			INNER JOIN cj_cc_cliente ON (fv.idCliente = cj_cc_cliente.id)
			INNER JOIN sa_iv_pagos pa ON (fv.idFactura = pa.id_factura)
			INNER JOIN cj_detallerecibopago ON (cj_encabezadorecibopago.idComprobante = cj_detallerecibopago.idComprobantePagoFactura)
				AND (pa.idPago = cj_detallerecibopago.idPago)
		WHERE pa.fechaPago = '".$fechaActual."'
			AND cj_encabezadorecibopago.id_departamento IN (0,1,3)
			AND fv.idFactura = pa.id_factura
			AND pa.tomadoEnComprobante = 1
			AND cj_encabezadorecibopago.fechaComprobante = '".$fechaActual."'
			".$andFormaPago2."			
			AND fv.idDepartamentoOrigenFactura IN (0,1,3)
			AND pa.formaPago = ".$idFormaPago."
			AND fv.condicionDePago = 1
		
		UNION
		
		SELECT 
			pa.id_det_nota_cargo AS idPago,
			pa.idFormaPago AS formaPago,
			'NOTA CARGO' AS tipoDoc,
			cj_encabezadorecibopago.numeroComprobante AS nro_comprobante,
			fv.numeroNotaCargo AS idDocumento,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			(CASE pa.idFormaPago
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = pa.numeroDocumento)
				ELSE
					pa.numeroDocumento
			END) AS numeroDocumento,
			pa.bancoOrigen,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoOrigen) AS nombre_banco_origen,
			pa.bancoDestino,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoDestino) AS nombre_banco_destino,
			pa.cuentaEmpresa,
			pa.monto_pago AS montoPagado,
			1 AS estatus
		FROM cj_cc_notadecargo fv
			INNER JOIN cj_encabezadorecibopago ON (fv.idNotaCargo = cj_encabezadorecibopago.numero_tipo_documento)
			INNER JOIN cj_cc_cliente ON (fv.idCliente = cj_cc_cliente.id)
			INNER JOIN cj_det_nota_cargo pa ON (fv.idNotaCargo = pa.idNotaCargo)
			INNER JOIN cj_detallerecibopago ON (pa.id_det_nota_cargo = cj_detallerecibopago.idPago)
				AND (cj_detallerecibopago.idComprobantePagoFactura = cj_encabezadorecibopago.idComprobante)
		WHERE pa.fechaPago = '".$fechaActual."'
			AND cj_encabezadorecibopago.id_departamento IN (0,1,3)
			AND fv.idDepartamentoOrigenNotaCargo IN (0,1,3)
			AND pa.tomadoEnCierre = 0
			AND pa.tomadoEnComprobante = 1
			".$andFormaPago2."	
			AND cj_encabezadorecibopago.fechaComprobante = '".$fechaActual."'
			AND fv.fechaRegistroNotaCargo = '".$fechaActual."'
			AND pa.idFormaPago = ".$idFormaPago;
		$consultaMostrarPorFormaPago = mysql_query($sqlMostrarPorFormaPago)or die(mysql_error());
		$numReg = mysql_num_rows($consultaMostrarPorFormaPago);
		if ($numReg > 0) {
			$cont++;
			if ($existePagoEnEfectivoOcheque == 1) {
				$existePagoEnEfectivoOcheque = 2;
			} ?>
			<table id="<?php echo "tblPagos".$cont; ?>" border="0" class="texto_9px" width="100%">
			<tr>
				<td class="tituloArea" colspan="10"><?php echo $nombreFormaPago;?></td>
			</tr>
			<tr align="center" class="tituloColumna">
				<td width="7%">Tipo Documento</td>
				<td width="6%">Nro. Recibo</td>
				<td width="6%">Nro. Documento</td>
				<td width="7%"><?php echo $spanClienteCxC; ?></td>
				<td width="16%">Cliente</td>
				<td width="8%"><?php echo ($idFormaPago != 1) ? "Nro. ".$nombreFormaPago : "-"; ?></td>
				<td width="15%">Banco Cliente</td>
				<td width="15%">Bco. Empresa</td>
				<td width="14%">Nro. Cuenta</td>
				<td width="6%">Monto</td>
			</tr>
			<?php
			$montoTotal = 0;
			$contFila = 0;
			while ($lafila = mysql_fetch_array($consultaMostrarPorFormaPago)) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				if ($lafila["estatus"] == 0) { // 0 = ANULADO
					$tipoDoc = $lafila["tipoDoc"].'/ANULADO';
					$montoAnticipoAnulado = $lafila["montoPagado"];
					$claseAnulado = "divMsjError";
				} else if ($lafila["estatus"] == 1){ // 1 = ACTIVO
					$tipoDoc = $lafila["tipoDoc"];
					$claseAnulado = "";
				}
				
				$montoTotal += ($lafila["estatus"] == 0) ? 0 : $lafila["montoPagado"];
				 ?>
				<tr class="<?php echo $clase; ?>" onmouseover="this.className = 'trSobre';" onmouseout="this.className = '<?php echo $clase; ?>';" height="22">
					<td align="center" class="<?php echo $claseAnulado; ?>"><?php echo $tipoDoc; ?></td>
					<td align="right"><?php echo $lafila["nro_comprobante"];?></td>
					<td align="right"><?php echo $lafila["idDocumento"]; ?></td>
					<td align="right"><?php echo $lafila['ci_cliente']; ?></td>
					<td align="left"><?php echo utf8_encode(strtoupper($lafila["nombre_cliente"])); ?></td>
					<td align="center"><?php echo $lafila['numeroDocumento'];?>
					</td>
					<td align="left"><?php echo utf8_encode($lafila['nombre_banco_origen']); ?></td>
					<td align="left"><?php echo utf8_encode($lafila['nombre_banco_destino']); ?></td>
					<td align="center"><?php echo $lafila["cuentaEmpresa"]; ?></td>
					<td align="right"><?php echo number_format($lafila["montoPagado"],2,".",","); ?></td>
				</tr>
			<?php
			} ?>
			<tr align="right" height="22">
				<td class="tituloColumna" colspan="9">Total en <?php echo $nombreFormaPago;?>:</td>
				<td class="trResaltarTotal3"><?php echo number_format($montoTotal,2,".",",");?></td>
			</tr>
			</table>
		<?php
		}
		echo "</br>";
	}
	$sw = $idFormaPago;
} ?>

	<table border="0" id="tblVentasAContado" cellpadding="0" cellspacing="0" width="100%">
	<tr align="right" height="22">
		<td class="tituloColumna" width="94%">Total Contado:</td>
        <td class="trResaltarTotal" id="tdTotalVentasContado" width="6%"></td>
	</tr>
	</table>
	<?php
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
		$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato(1, "int")); // 1 = Empresa cabecera
		$rsConfig400 = mysql_query($queryConfig400);
		if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsConfig400 = mysql_num_rows($rsConfig400);
		$rowConfig400 = mysql_fetch_assoc($rsConfig400);
			
		if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
			$andEmpresa = sprintf(" AND ape.id_empresa = %s",
				valTpDato($idEmpresa,"int"));
			$andFormaPago = sprintf(" AND an.id_empresa = %s",
				valTpDato($idEmpresa,"int"));
			$andFormaPago2 = sprintf(" AND fv.id_empresa = %s",
				valTpDato($idEmpresa,"int"));
				
		} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
			$andEmpresa = '';
			$andFormaPago = '';
			$andFormaPago2 = '';
		}
		
		$fechaApertura = "(SELECT
				ape.fechaAperturaCaja
			FROM
				caja ca
				INNER JOIN sa_iv_apertura ape ON (ca.idCaja = ape.idCaja)
			WHERE
				ape.statusAperturaCaja IN (1,2)
				AND ape.idCaja = 2
				".$andEmpresa.")";
				
		$queryVentasAcontado = "SELECT
			SUM(pa.montoDetalleAnticipo) AS total,
			an.estatus AS estatus
		FROM cj_cc_detalleanticipo pa,
			cj_cc_anticipo an
		WHERE pa.fechaPagoAnticipo = '".$fechaActual."'
			AND pa.tomadoEnCierre = 0
			AND an.idAnticipo = pa.idAnticipo
			AND an.idDepartamento IN (0,1,3)
			".$andFormaPago."
			AND pa.tipoPagoDetalleAnticipo IN (SELECT aliasFormaPago FROM formapagos)
			AND an.estatus = 1
			
		UNION ALL
		
		SELECT
			SUM(pa.montoPagado) AS total,
			'' AS estatus
		FROM sa_iv_pagos pa,
			cj_cc_encabezadofactura fv
		WHERE pa.fechaPago = '".$fechaActual."'
			AND fv.idDepartamentoOrigenFactura IN (0,1,3)
			AND pa.tomadoEnCierre = 0
			AND fv.idFactura = pa.id_factura
			AND pa.tomadoEnComprobante = 1
			".$andFormaPago2."
			AND pa.formaPago IN (SELECT idFormaPago FROM formapagos)
			AND pa.formaPago NOT IN (7,8)
			AND fv.condicionDePago = 1
			
		UNION ALL
	
		SELECT
			SUM(pa.monto_pago) AS total,
			'' AS estatus
		FROM cj_det_nota_cargo pa,
			cj_cc_notadecargo fv
		WHERE pa.fechaPago = '".$fechaActual."'
			AND pa.tomadoEnCierre = 0
			AND fv.idNotaCargo = pa.idNotaCargo
			AND pa.tomadoEnComprobante = 1
			AND fv.idDepartamentoOrigenNotaCargo IN (0,1,3)
			".$andFormaPago2."
			AND fv.fechaRegistroNotaCargo = $fechaApertura
			AND pa.idFormaPago IN (SELECT idFormaPago FROM formapagos WHERE idFormaPago NOT IN (7,8))";
		$rsVentasAcontado = mysql_query($queryVentasAcontado)or die(mysql_error()."<br><br>Line: ".__LINE__."<br><br>SQL: ".$queryVentasAcontado);
		
		$total = 0;
		while ($rowVentasAcontado = mysql_fetch_assoc($rsVentasAcontado)) {
			$total += $rowVentasAcontado['total'];
		}
	
		/*if (!$total) {
			echo "<script>document.getElementById('tblVentasAContado').style.display='none';</script>";
		} else {
			echo sprintf("<script>document.getElementById('tdTotalVentasContado').innerHTML = '%s';</script>", number_format($total, 2,".",","));
		}*/
		echo sprintf("<script>document.getElementById('tdTotalVentasContado').innerHTML = '%s';</script>", number_format($total, 2,".",","));	
	?>
		
	<!--<table border="0" id="tblVentasACredito" cellpadding="0" cellspacing="0" width="100%">
	<tr align="right" height="22">
		<td class="tituloColumna" width="94%">Total Cr&eacute;dito:</td>
		<td class="trResaltarTotal2" id="tdTotalVentasCredito" width="6%"></td>
	</tr>
	</table>-->
	
	<?php
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
				
		// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
		$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato(1, "int")); // 1 = Empresa cabecera
		$rsConfig400 = mysql_query($queryConfig400);
		if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsConfig400 = mysql_num_rows($rsConfig400);
		$rowConfig400 = mysql_fetch_assoc($rsConfig400);
			
		if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
			$andFormaPago = sprintf(" AND cj_cc_encabezadofactura.id_empresa = %s",
				valTpDato($idEmpresa,"int"));
			$andFormaPago2 = sprintf(" AND fv.id_empresa = %s",
				valTpDato($idEmpresa,"int"));
				
		} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
			$andFormaPago = '';
			$andFormaPago2 = '';
		}
		
		$queryVentasAcredito = "SELECT
			SUM(cj_cc_encabezadofactura.montoTotalFactura) AS monto_total_ventas_credito
		FROM cj_cc_encabezadofactura
		WHERE cj_cc_encabezadofactura.condicionDePago = 0
			AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura IN (0,1,3)
			AND cj_cc_encabezadofactura.fechaRegistroFactura = '".$fechaActual."'
			AND cj_cc_encabezadofactura.idFactura NOT IN (SELECT cxc_nc.idDocumento FROM cj_cc_notacredito cxc_nc
															WHERE cxc_nc.fechaNotaCredito = '".$fechaActual."'
																AND cxc_nc.tipoDocumento LIKE 'FA') ".$andFormaPago.";";
		$rsVentasAcredito = mysql_query($queryVentasAcredito)or die(mysql_error().__LINE__.$queryVentasAcredito);
		$rowVentasAcredito = mysql_fetch_assoc($rsVentasAcredito);
		
		/*if ($rowVentasAcredito['monto_total_ventas_credito'] == 0) {
			echo "<script>document.getElementById('tblVentasACredito').style.display='none';</script>";
		} else {
			echo sprintf("<script>document.getElementById('tdTotalVentasCredito').innerHTML = '%s';</script>", number_format($rowVentasAcredito['monto_total_ventas_credito'], 2,".",","));
		}*/
		echo sprintf("<script>document.getElementById('tdTotalVentasCredito').innerHTML = '%s';</script>", number_format($rowVentasAcredito['monto_total_ventas_credito'], 2,".",","));		
	?>
	<!--<table border="0" id="tblVentasContadoCredito" cellpadding="0" cellspacing="0" width="100%">
	<tr align="right" height="22">
		<td class="tituloColumna" width="94%">Total Contado + Cr&eacute;dito:</td>
		<td class="trResaltarTotal" id="tdTotalContadoCredito" width="6%"></td>
	</tr>
	</table>-->
	<?php
		echo sprintf("<script>document.getElementById('tdTotalContadoCredito').innerHTML = '%s';</script>", number_format($rowVentasAcredito['monto_total_ventas_credito'] + $total, 2,".",","));
	?>
	
<div id="divButtons" name="divButtons">
	<table border="0" width="100%">
	<tr>
		<td align="right"><hr>
		<form id="form2" name="form2" method="post">
			<button type="button" id="Imprimir" name="Imprimir" onclick="printPage();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button><br/><br/>
			<button type="button" id="cerrarCaja" name="cerrarCaja" onclick="if (confirm('Desea Cerrar La Caja?')){xajax_cerrarCaja();}" style="visibility:hidden" value="Cerrar Caja"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/lock.png"/></td><td>&nbsp;</td><td>Cerrar Caja</td></tr></table></button>
			<button type="button" id="txtVolver" name="txtVolver" onclick="top.history.back()"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/return.png"/></td><td>&nbsp;</td><td>Volver</td></tr></table></button>
			
			<input type="hidden" id="ocultoPresionoImprimirCorteDeCaja" name="ocultoPresionoImprimirCorteDeCaja" value="0"/>
			<input type="hidden" id="ocultoExisteRegistro" name="ocultoExisteRegistro" value="0"/>
			<input type="hidden" id="ocultoCierreCaja" name="ocultoCierreCaja" value="1"/>
			<?php
			if ($sw == "") {?>
				<script>
				document.getElementById("Imprimir").disabled = true;
				document.getElementById("ocultoExisteRegistro").value = 1;
				</script>
			<?php
			}?>
			<script>
			function imprimirPlanilla(k) {
				if (document.getElementById("ocultoExisteRegistro").value == 1) {
					if (confirm("Esta seguro de realizar el cierre de caja?")) {
						return true;
					} else {
						return false;
					}
				} else {
					if (document.getElementById("ocultoPresionoImprimirCorteDeCaja").value == 1) {
						if (confirm("Imprimio Correctamente el formato de Corte de Caja?")) {
							if (k == 1) {
								window.location.href="cjrs_depositos_form.php";
							}
							return true;
						} else {
							document.getElementById("Imprimir").focus();
							return false;
						}
					} else {
						alert ("Debe Imprimir el corte de Caja");
						document.getElementById("Imprimir").focus();
						return false;
					}
				}
			}
			</script>
		</form>
		</td>
	</tr>
	<tr id="infoConformacion" style="visibility:hidden">
		<td class="divMsjInfo2">
			<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
			<tr>
				<td width="25"><img src="../img/iconos/ico_info.gif"/></td>
				<td align="center">No se puede conformar planillas de deposito debido a que no existen pagos en Efectivo o en Cheques.</td>
			</tr>
			</table>
		</td>
	</tr>
	<tr id="infoPagosNoRealizados" style="visibility:hidden">
		<td class="divMsjInfo2">
			<table width="76%" align="center">
			<tr>
				<td width="6%">
					<img src="../img/iconos/ico_info.gif" alt=""/>
				</td>
				<td width="94%">
					No puede realizar el cierre, debido a que existen facturas de contado sin pagos o con pagos parciales.<br/><br/>
					<div id="facurasSinPagos"></div>
				</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
</div>

<script>
xajax_validarDepositos();

if (<?php echo $_GET['acc'];?> == 2)
	document.getElementById("cerrarCaja").style.visibility="visible";
</script>
<?php
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
		
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$andSql = sprintf(" AND id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andSql2 = sprintf(" AND sa_iv_apertura.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
			
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andSql = '';
		$andSql2 = '';
	}
	
	$sqlFacturasSinPagos = "SELECT
		numerofactura,
		id_empresa
	FROM cj_cc_encabezadofactura
	WHERE idDepartamentoOrigenFactura IN (0,1,3)
		AND condicionDePago = 1
		AND saldofactura != 0.00
		".$andSql."
		AND fechaRegistroFactura = (SELECT
										sa_iv_apertura.fechaAperturaCaja
									FROM sa_iv_apertura
										INNER JOIN sa_iv_cierredecaja ON (sa_iv_apertura.id = sa_iv_cierredecaja.id)
									WHERE sa_iv_cierredecaja.idCierre = (SELECT
																			MAX(sa_iv_cierredecaja.idCierre) AS maximo
																		FROM sa_iv_apertura
																			INNER JOIN sa_iv_cierredecaja ON (sa_iv_apertura.id = sa_iv_cierredecaja.id),
																			sa_iv_pagos
																		WHERE sa_iv_apertura.idCaja = 2
																		".$andSql2.")
										)";
	$consultaFacturasSinPagos = mysql_query($sqlFacturasSinPagos) or die(mysql_error().__LINE__.$sqlFacturasSinPagos);
	$numeroFacturasSinPagos = "";
	if (mysql_num_rows($consultaFacturasSinPagos) && $_GET['acc'] == 2) {
		while ($FacturasSinPagos = mysql_fetch_array($consultaFacturasSinPagos)) {
			if ($numeroFacturasSinPagos == "") {
				$numeroFacturasSinPagos.= $FacturasSinPagos['numerofactura'];
			} else {
				$numeroFacturasSinPagos.= ", ".$FacturasSinPagos['numerofactura'];
			}
		}
		echo "<script>document.getElementById('facurasSinPagos').innerHTML= 'Los Numeros de Facturas que se encuentran sin pagos son las siguientes:&nbsp;&nbsp;&nbsp;<font color=red>".$numeroFacturasSinPagos."</font>';
		document.getElementById('infoPagosNoRealizados').style.visibility='visible';
		document.getElementById('cerrarCaja').disabled = true;
		document.getElementById('Imprimir').disabled = true;</script>";
	}
?>
</div>
</body>
</html>