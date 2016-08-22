<?php
require_once("../connections/conex.php");

require_once("../inc_sesion.php");
	
$idPedido = $_GET['id'];

$sql = sprintf("SELECT
	ped_vent.id_pedido,
	ped_vent.numeracion_pedido,
	ped_vent.id_empresa,
	ped_vent.id_presupuesto,
	pres_vent.numeracion_presupuesto,
	uni_fis.id_uni_bas,
	ped_vent.fecha,
	ped_vent.id_clave_movimiento,
	ped_vent.id_unidad_fisica,
	ped_vent.id_unidad_fisica,
	ped_vent.gerente_ventas,
	ped_vent.fecha_gerente_ventas,
	ped_vent.administracion,
	ped_vent.fecha_administracion,
	ped_vent.precio_retoma,
	ped_vent.fecha_retoma,
	ped_vent.estado_pedido,
	ped_vent.precio_venta,
	ped_vent.monto_descuento,
	ped_vent.tipo_inicial,
	ped_vent.porcentaje_inicial,
	ped_vent.inicial,
	ped_vent.saldo_financiar,
	ped_vent.meses_financiar,
	ped_vent.interes_cuota_financiar,
	ped_vent.cuotas_financiar,
	ped_vent.id_banco_financiar,
	ped_vent.total_inicial_gastos,
	ped_vent.total_adicional_contrato,
	ped_vent.monto_flat,
	ped_vent.total_accesorio,
	ped_vent.observaciones,
	ped_vent.asesor_ventas,
	ped_vent.anticipo,
	ped_vent.complemento_inicial,
	ped_vent.forma_pago_precio_total,
	ped_vent.id_poliza,
	ped_vent.inicial_poliza,
	ped_vent.cuotas_poliza,
	ped_vent.monto_seguro,
	ped_vent.id_cliente,
	ped_vent.fecha_reserva_venta,
	ped_vent.fecha_entrega,
	ped_vent.total_pedido,
	ped_vent.porcentaje_iva,
	ped_vent.porcentaje_impuesto_lujo,
	ped_vent.porcentaje_flat,
	ped_vent.meses_poliza,
	ped_vent.empresa_accesorio,
	ped_vent.exacc1,
	ped_vent.exacc2,
	ped_vent.exacc3,
	ped_vent.exacc4,
	ped_vent.vexacc1,
	ped_vent.vexacc2,
	ped_vent.vexacc3,
	ped_vent.vexacc4
FROM an_pedido ped_vent
	LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
	LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
WHERE ped_vent.id_pedido = %s;",
	valTpDato($idPedido, "int"));
$r = mysql_query($sql, $conex);
if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowPedido = mysql_fetch_assoc($r);

$idEmpresa = $rowPedido['id_empresa'];
$idUnidadBasica = $rowPedido['id_uni_bas'];
$idUnidadFisica = $rowPedido['id_unidad_fisica'];

$lstPrecioVenta = $rowPedido['precio_venta'];

if ($_GET['view'] == "") {
	$loadscript = "onload=\"";
		$loadscript .= ($_GET['view'] == "print") ? " window.print();" : "";
		$loadscript .= " percent();";
	$loadscript .= "\"";
	
	include "an_ventas_pedido_insertar.php";
} else if (in_array($_GET['view'],array("view","print","import"))) {
	$idCliente = $rowPedido['id_cliente'];
	
	// BUSCA LOS DATOS DEL CLIENTE
	$sql = sprintf("SELECT
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		cliente.nombre,
		cliente.apellido,
		CONCAT_WS(': ', CONCAT_WS('-', cliente.lci, cliente.ci), CONCAT_WS(' ', cliente.nombre, cliente.apellido)) AS nombre_cliente,
		cliente.telf,
		cliente.direccion,
		cliente.correo,
		cliente.ciudad,
		cliente.otrotelf,
		IF(cliente.tipo = 'Natural', IF(perfil_prospecto.sexo = 'M', 'Masculino', 'Femenino'),'') AS sexo_cliente,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.reputacionCliente,
		cliente.tipo_cuenta_cliente,
		cliente.tipo,
		cliente.paga_impuesto
	FROM cj_cc_cliente cliente
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
	WHERE cliente.id = %s;",
		valTpDato($idCliente, "int"));
	$r = mysql_query($sql, $conex);
	if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($r);
	
	$nombreCliente = utf8_encode($rowCliente['nombre_cliente']);
	$hddPagaImpuesto = ($rowCliente['paga_impuesto']);
	
	$most = "false";
	if ($rowCliente['tipo_cuenta_cliente'] == 1) {
		$rep_val = '#FFFFCC'; $rep_tipo = $rowCliente['reputacionCliente'];
	} else {
		switch ($rowCliente['id_reputacion_cliente']) {
			case 1 : $rep_val = '#FFEEEE'; $rep_tipo = $rowCliente['reputacionCliente']; $most = "true"; break;
			case 2 : $rep_val = '#DDEEFF'; $rep_tipo = $rowCliente['reputacionCliente']; break;
			case 3 : $rep_val = '#006500'; $rep_tipo = $rowCliente['reputacionCliente']; break;
		}
	}
	
	// VERIFICA SI TIENE IMPUESTO
	if (getmysql("SELECT UPPER(isan_uni_bas)
	FROM an_uni_bas
		INNER JOIN an_unidad_fisica ON (an_uni_bas.id_uni_bas = an_unidad_fisica.id_uni_bas)
	WHERE id_unidad_fisica = ".valTpDato($idUnidadFisica,"int").";") == 1 && $hddPagaImpuesto == 1) {
		$query = sprintf("SELECT
			iva.iva,
			iva.observacion
		FROM an_unidad_basica_impuesto uni_bas_impuesto
			INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo = 6;",
			valTpDato($idUnidadBasica, "int"));
		$rs = mysql_query($query);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$txtNuevoPorcIva = $row['iva'];
		$cond = (strlen($eviva) > 0) ? " e " : "";
		$eviva .= $cond.$row['observacion'];
	} else {
		$txtNuevoPorcIva = 0;
		$eviva .= "Exento";
	}
	
	if (getmysql("SELECT impuesto_lujo
	FROM an_uni_bas
		INNER JOIN an_unidad_fisica ON (an_uni_bas.id_uni_bas = an_unidad_fisica.id_uni_bas)
	WHERE id_unidad_fisica = ".valTpDato($idUnidadFisica,"int").";") == 1 && $hddPagaImpuesto == 1) {
		$query = sprintf("SELECT
			iva.iva,
			iva.observacion
		FROM an_unidad_basica_impuesto uni_bas_impuesto
			INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo = 2;",
			valTpDato($idUnidadBasica, "int"));
		$rs = mysql_query($query);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$txtNuevoPorcIvaLujo = $row['iva'];
		$cond = (strlen($eviva) > 0) ? " e " : "";
		$eviva .= $cond.$row['observacion'];
	} else {
		$txtNuevoPorcIvaLujo = 0;
	}
	
	$txtSubTotalIva = (($txtPrecioBase - $txtDescuento) * $txtPorcIva) / 100;
	$txtSubTotalIvaLujo = (($txtPrecioBase - $txtDescuento) * $txtPorcIvaLujo) / 100;
	$txtMontoImpuesto = $txtSubTotalIva + $txtSubTotalIvaLujo;
	$txtPrecioVenta = ($txtPrecioBase - $txtDescuento) + $txtMontoImpuesto;
	
	if ($idPedido > 0) {
		//cargando accesorios:
		$sqla = sprintf("SELECT
			acc_ped.id_accesorio_pedido,
			acc_ped.id_pedido,
			acc_ped.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_ped.id_tipo_accesorio,
			(CASE acc_ped.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			acc_ped.iva_accesorio,
			acc_ped.porcentaje_iva_accesorio,
			(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_ped.costo_accesorio
		FROM an_accesorio_pedido acc_ped
			INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
		WHERE acc_ped.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$ra = mysql_query($sqla, $conex);
		if (!$ra) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowa = mysql_fetch_assoc($ra)) {
			$scriptAdicional .= sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');",
				$rowa['id_accesorio'],
				"",
				$rowa['precio_con_iva'],
				utf8_encode($rowa['nom_accesorio']),
				"3",
				$rowa['iva_accesorio'],
				$rowa['costo_accesorio'],
				$rowa['porcentaje_iva_accesorio'],
				$rowa['id_tipo_accesorio'],
				$rowa['id_accesorio_pedido']);
		}
		
		$sqlp = sprintf("SELECT
			paq_ped.id_paquete_pedido,
			paq_ped.id_pedido,
			paq_ped.id_acc_paq,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (paq_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			paq_ped.id_tipo_accesorio,
			(CASE paq_ped.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			paq_ped.iva_accesorio,
			paq_ped.porcentaje_iva_accesorio,
			(paq_ped.precio_accesorio + (paq_ped.precio_accesorio * paq_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			paq_ped.costo_accesorio
		FROM an_paquete_pedido paq_ped
			INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.id_acc_paq)
			INNER JOIN an_accesorio acc on (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE paq_ped.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$rp = mysql_query($sqlp, $conex);
		if (!$rp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowa = mysql_fetch_assoc($rp)) {
			$scriptAdicional .= sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');",
				$rowa['id_accesorio'],
				$rowa['id_acc_paq'],
				$rowa['precio_con_iva'],
				utf8_encode($rowa['nom_accesorio']),
				3,
				$rowa['iva_accesorio'],
				$rowa['costo_accesorio'],
				$rowa['porcentaje_iva_accesorio'],
				$rowa['id_tipo_accesorio'],
				$rowa['id_paquete_pedido']);
		}
	}
	
	if ($_GET['view'] == "" && $idUnidadFisica > 0) {
		if ($txtPorcIva != $txtNuevoPorcIva) {
			$txtPorcIva = $txtNuevoPorcIva;
			$arrayMsg[] = "Se ha actualizado el Impuesto en: ".$txtPorcIva."%";
		}
		if ($txtPorcIvaLujo != $txtNuevoPorcIvaLujo) {
			$txtPorcIvaLujo = $txtNuevoPorcIvaLujo;
			$arrayMsg[] = "Se ha actualizado el Impuesto al lujo en: ".$txtPorcIvaLujo."%";
		}
		(count($arrayMsg) > 0) ? "alert('".implode($arrayMsg,"\n")."');" : "";
	}
	
	// DATOS DEL PEDIDO
	$numeroPedido = $rowPedido['numeracion_pedido'];
	$idPresupuesto = $rowPedido['id_presupuesto'];
	$numeroPresupuesto = $rowPedido['numeracion_presupuesto'];
	
	$idAsesorVentas = $rowPedido['asesor_ventas'];
	$idClaveMovimiento = $rowPedido['id_clave_movimiento'];
	
	// VENTA DE LA UNIDAD
	$txtPrecioBase = $rowPedido['precio_venta'];
	$txtDescuento = $rowPedido['monto_descuento'];
	$txtPorcIva = $rowPedido['porcentaje_iva'];
	$txtPorcIvaLujo = $rowPedido['porcentaje_impuesto_lujo'];
	$txtPrecioVenta = $rowPedido['precio_venta'];
	
	$hddTipoInicial = $rowPedido['tipo_inicial'];
	$txtPorcInicial = $rowPedido['porcentaje_inicial'];
	$txtMontoInicial = $rowPedido['inicial'];
	
	$txtTotalInicialGastos = $rowPedido['total_inicial_gastos'];
	$txtSaldoFinanciar = $rowPedido['saldo_financiar'];
	$txtTotalAdicionalContrato = $rowPedido['total_adicional_contrato'];
	
	$txtTotalPedido = $rowPedido['total_pedido'];
	
	// BUSCA LOS DATOS DEL PRESUPUESTO DE ACCESORIOS
	$exacc1 = $rowPedido['exacc1'];
	$vexacc1 = $rowPedido['vexacc1'];
	$exacc2 = $rowPedido['exacc2'];
	$vexacc2 = $rowPedido['vexacc2'];
	$exacc3 = $rowPedido['exacc3'];
	$vexacc3 = $rowPedido['vexacc3'];
	$exacc4 = $rowPedido['exacc4'];
	$vexacc4 = $rowPedido['vexacc4'];
	$txtTotalAccesorio = $rowPedido['total_accesorio'];
	$empresa_accesorio = $rowPedido['empresa_accesorio'];
	
	// FORMA DE PAGO
	$txtMontoAnticipo = $rowPedido['anticipo'];
	$txtMontoComplementoInicial = $rowPedido['complemento_inicial'];
	
	// FINANCIAMIENTO
	$lstBancoFinanciar = $rowPedido['id_banco_financiar'];
	$lstMesesFinanciar = $rowPedido['meses_financiar'];
	$txtInteresCuotaFinanciar = $rowPedido['interes_cuota_financiar'];
	$txtCuotasFinanciar = numformat($rowPedido['cuotas_financiar'],2);
	$txtPorcFLAT = $rowPedido['porcentaje_flat'];
	$txtMontoFLAT = $rowPedido['monto_flat'];
	$valores = array(
		"lstMesesFinanciar*".$lstMesesFinanciar,
		"txtInteresCuotaFinanciar*".$txtInteresCuotaFinanciar,
		"txtCuotasFinanciar*".$txtCuotasFinanciar);
	
	$txtPrecioTotal = $rowPedido['forma_pago_precio_total'];
	
	// SEGURO
	$idPoliza = $rowPedido['id_poliza'];
	$txtMontoSeguro = $rowPedido['monto_seguro'];
	$txtInicialPoliza = $rowPedido['inicial_poliza'];
	$txtMesesPoliza = $rowPedido['meses_poliza'];
	$txtCuotasPoliza = $rowPedido['cuotas_poliza'];
	
	$txtObservacion = $rowPedido['observaciones'];
	
	$idGerenteVenta = $rowPedido['gerente_ventas'];
	$txtFechaVenta = date("d-m-Y", strtotime($rowPedido['fecha_gerente_ventas']));
	$idGerenteAdministracion = $rowPedido['administracion'];
	$txtFechaAdministracion = date("d-m-Y", strtotime($rowPedido['fecha_administracion']));
	
	$txtFechaReserva = date("d-m-Y", strtotime($rowPedido['fecha_reserva_venta']));
	$txtFechaEntrega = date("d-m-Y", strtotime($rowPedido['fecha_entrega']));
	
	$txtPrecioRetoma = $rowPedido['precio_retoma'];
	$txtFechaRetoma = implode("-",array_reverse(explode("-",$rowPedido['fecha_retoma'])));
	
	$loadscript = "onload=\"";
		$loadscript .= ($scriptAdicional != "") ? $scriptAdicional : "";
		$loadscript .= ($arrayMsg != "") ? $arrayMsg : "";
		$loadscript .= ($_GET['view'] == "print") ? " window.print();" : "";
		$loadscript .= " percent(); reputacion('".$rep_val."','".$rep_tipo."',".$most.",'".$tipoCuentaCliente."');";
		if ($txtPorcInicial < 100 && $_GET['view'] == "") {
			if ($lstBancoFinanciar > 0) {
			} else {
				$loadscript .= "
				byId('cbxSinBancoFinanciar').checked = true;
				byId('aDesbloquearSinBancoFinanciar').style.display = 'none';
				byId('hddSinBancoFinanciar').value = 1;";
			}
		}
		$loadscript .= ($rowPedido['estado_pedido'] == 3) ? "alert('El pedido ".$idPedido." está desautorizado');" : "";
		$loadscript .= ($lstBancoFinanciar > 0) ? "if (eval((typeof('asignarBanco') != 'undefined')) && window.asignarBanco) { asignarBanco('".$lstBancoFinanciar."','".implode("|",$valores)."'); }" : "";
	$loadscript .= "\"";
	
	include "an_ventas_pedido_formato.php";
} ?>