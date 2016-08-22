<?php
require_once("../connections/conex.php");

require_once("../inc_sesion.php");

if (isset($_POST['rbtUnidadFisica'])) {
	foreach($_POST['rbtUnidadFisica'] as $indiceItm => $valorItm) {
		$idUnidadFisica = $valorItm;
	}
}

mysql_query("START TRANSACTION;");

$idEmpresa = getmysqlnum($_POST['txtIdEmpresa']);

$idPedido = excape($_POST['txtIdPedido']);
$idPresupuesto = $_POST['txtIdPresupuesto'];
$idFactura = excape($_POST['txtIdFactura']);
$idClaveMovimiento =  excape($_POST['lstClaveMovimiento']);

$idCliente = excape($_POST['txtIdCliente']);
$idUnidadBasica = excape($_POST['txtIdUnidadBasica']);
$idAsesorVentas = excape($_POST['lstAsesorVenta']);
$txtObservacion = excape($_POST['observaciones']);

$idGerenteVenta = excape($_POST['lstGerenteVenta']);
$txtFechaVenta = excape($_POST['txtFechaVenta']);
$idGerenteAdministracion = excape($_POST['lstGerenteAdministracion']);
$txtFechaAdministracion = excape($_POST['txtFechaAdministracion']);
$txtFechaReserva = excape($_POST['txtFechaReserva']);
$txtFechaEntrega = excape($_POST['txtFechaEntrega']);

$txtPrecioBase = getmysqlnum($_POST['txtPrecioBase']);
$txtDescuento = getmysqlnum($_POST['txtDescuento']);
$txtPrecioVenta = getmysqlnum($_POST['txtPrecioVenta']);
$txtPorcIva = getmysqlnum($_POST['porcentaje_iva']);
$txtPorcIvaLujo = getmysqlnum($_POST['porcentaje_impuesto_lujo']);
$hddTipoInicial = getmysqlnum($_POST['hddTipoInicial']);
$txtPorcInicial = getmysqlnum($_POST['txtPorcInicial']);
$inicial = getmysqlnum($_POST['txtMontoInicial']);

$txtMontoAnticipo = getmysqlnum($_POST['txtMontoAnticipo']);
$txtPrecioTotal = getmysqlnum($_POST['txtPrecioTotal']);
$txtMontoComplementoInicial = getmysqlnum($_POST['txtMontoComplementoInicial']);

$txtPrecioRetoma = $_POST['txtPrecioRetoma'];
$txtFechaRetoma = excape($_POST['txtFechaRetoma']);

// FINANCIAMIENTO
$lstBancoFinanciar = ($_POST['lstBancoFinanciar'] > 0) ? $_POST['lstBancoFinanciar'] : "";
$txtSaldoFinanciar = getmysqlnum($_POST['txtSaldoFinanciar']);
$lstMesesFinanciar = getemptynum(($_POST['lstMesesFinanciar']),'null');
$txtInteresCuotaFinanciar = $_POST['txtInteresCuotaFinanciar'];
$txtCuotasFinanciar = getmysqlnum($_POST['txtCuotasFinanciar']);

$txtTotalAccesorio = getempty(getmysqlnum($_POST['txtTotalAccesorio']),"null");
$txtTotalInicialGastos = getmysqlnum($_POST['txtTotalInicialGastos']);
$txtTotalAdicionalContrato = getmysqlnum($_POST['txtTotalAdicionalContrato']);
$txtPorcFLAT = getmysqlnum($_POST['porcentaje_flat']);
$txtMontoFLAT = getemptynum($_POST['txtMontoFLAT'],'null');
$txtTotalPedido = getmysqlnum($_POST['txtTotalPedido']);

// ACCESORIOS
$empresa_accesorio = excape($_POST['empresa_accesorio']);
$exacc1 = excape($_POST['exacc1']);
$vexacc1 = getmysqlnum($_POST['vexacc1']);
$exacc2 = excape($_POST['exacc2']);
$vexacc2 = getmysqlnum($_POST['vexacc2']);
$exacc3 = excape($_POST['exacc3']);
$vexacc3 = getmysqlnum($_POST['vexacc3']);
$exacc4 = excape($_POST['exacc4']);
$vexacc4 = getmysqlnum($_POST['vexacc4']);

// POLIZA
$idPoliza = $_POST['lstPoliza'];
$txtMontoSeguro = getempty(getmysqlnum($_POST['txtMontoSeguro']),'null');
$txtInicialPoliza = getempty(getmysqlnum($_POST['txtInicialPoliza']),'null');
$txtCuotasPoliza = getempty(getmysqlnum($_POST['txtCuotasPoliza']),'null');
$txtMesesPoliza = getempty(getmysqlnum($_POST['txtMesesPoliza']),'null');

if ($txtPorcInicial == 100) {
	$lstBancoFinanciar = "";
	$lstMesesFinanciar = "";
	$txtInteresCuotaFinanciar = "";
	$txtCuotasFinanciar = "";
	
	$txtPorcFLAT = "";
	$txtMontoFLAT = "";
}

// EXTRAE LOS IMPUESTOS
$txtPorcTotalImpuesto = $txtPorcIva + $txtPorcIvaLujo;
if ($txtPorcTotalImpuesto > 0) {
	$txtPrecioVenta = $txtPrecioVenta - ($txtPrecioVenta * $txtPorcTotalImpuesto / (100 + $txtPorcTotalImpuesto));
}

conectar();

if ($idFactura > 0) {
	validaModulo("cj_factura_venta_list",insertar);
	
	$insertSQL = "INSERT INTO an_pedido (numeracion_pedido, id_empresa, id_cliente, id_factura_cxc, id_presupuesto, id_clave_movimiento, id_unidad_fisica, fecha, gerente_ventas, fecha_gerente_ventas, administracion, fecha_administracion, precio_retoma, fecha_retoma, estado_pedido, precio_venta, monto_descuento, tipo_inicial, porcentaje_inicial, inicial, saldo_financiar, meses_financiar, interes_cuota_financiar, cuotas_financiar, id_banco_financiar, total_inicial_gastos, total_adicional_contrato, monto_flat, total_accesorio, observaciones, asesor_ventas, anticipo, complemento_inicial, forma_pago_precio_total, id_poliza, inicial_poliza, cuotas_poliza, monto_seguro, fecha_reserva_venta, fecha_entrega, total_pedido, porcentaje_iva, porcentaje_impuesto_lujo, porcentaje_flat, meses_poliza, empresa_accesorio, exacc1, exacc2, exacc3, exacc4, vexacc1, vexacc2, vexacc3, vexacc4)
	SELECT
		CONCAT(SUBSTRING_INDEX(numeracion_pedido, '(', 1), '(', ((SELECT COUNT(an_ped_vent.id_factura_cxc) FROM an_pedido an_ped_vent
																WHERE an_ped_vent.id_empresa = an_pedido.id_empresa
																	AND an_ped_vent.numeracion_pedido LIKE CONCAT(SUBSTRING_INDEX(an_pedido.numeracion_pedido, '(', 1), '%')) + 1),')'), ".
		valTpDato($idEmpresa, "int").", ".
		valTpDato($idCliente, "int").", ".
		valTpDato($idFactura, "int").", ".
		valTpDato($idPresupuesto, "int").", ".
		valTpDato($idClaveMovimiento, "int").", ".
		valTpDato($idUnidadFisica, "int").", ".
		valTpDato("CURRENT_DATE()", "campo").", ".
		valTpDato($idGerenteVenta, "int").", ".
		valTpDato(date("Y-m-d",strtotime($txtFechaVenta)), "date").", ".
		valTpDato($idGerenteAdministracion, "int").", ".
		valTpDato(date("Y-m-d",strtotime($txtFechaAdministracion)), "date").", ".
		valTpDato($txtPrecioRetoma, "real_inglesa").", ".
		setmysqlfecha($txtFechaRetoma).", ".
		valTpDato(1, "int").", ". // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
		valTpDato($txtPrecioBase, "real_inglesa").", ".
		valTpDato($txtDescuento, "real_inglesa").", ".
		valTpDato($hddTipoInicial, "int").", ".
		valTpDato($txtPorcInicial, "real_inglesa").", ".
		valTpDato($inicial, "real_inglesa").", ".
		valTpDato($txtSaldoFinanciar, "real_inglesa").", ".
		valTpDato($lstMesesFinanciar, "real_inglesa").", ".
		valTpDato($txtInteresCuotaFinanciar, "real_inglesa").", ".
		valTpDato($txtCuotasFinanciar, "real_inglesa").", ".
		valTpDato($lstBancoFinanciar, "int").", ".
		valTpDato($txtTotalInicialGastos, "real_inglesa").", ".
		valTpDato($txtTotalAdicionalContrato, "real_inglesa").", ".
		valTpDato($txtMontoFLAT, "real_inglesa").", ".
		valTpDato($txtTotalAccesorio, "real_inglesa").", ".
		valTpDato($txtObservacion, "text").", ".
		valTpDato($idAsesorVentas, "int").", ".
		valTpDato($txtMontoAnticipo, "real_inglesa").", ".
		valTpDato($txtMontoComplementoInicial, "real_inglesa").", ".
		valTpDato($txtPrecioTotal, "real_inglesa").", ".
		valTpDato($idPoliza, "int").", ".
		valTpDato($txtInicialPoliza, "real_inglesa").", ".
		valTpDato($txtCuotasPoliza, "real_inglesa").", ".
		valTpDato($txtMontoSeguro, "real_inglesa").", ".
		valTpDato(date("Y-m-d",strtotime($txtFechaReserva)), "date").", ".
		valTpDato(date("Y-m-d",strtotime($txtFechaEntrega)), "date").", ".
		valTpDato($txtTotalPedido, "real_inglesa").", ".
		valTpDato($txtPorcIva, "real_inglesa").", ".
		valTpDato($txtPorcIvaLujo, "real_inglesa").", ".
		valTpDato($txtPorcFLAT, "real_inglesa").", ".
		valTpDato($txtMesesPoliza, "real_inglesa").", ".
		valTpDato($empresa_accesorio, "text").", ".
		valTpDato($exacc1, "text").", ".
		valTpDato($exacc2, "text").", ".
		valTpDato($exacc3, "text").", ".
		valTpDato($exacc4, "text").", ".
		valTpDato($vexacc1, "real_inglesa").", ".
		valTpDato($vexacc2, "real_inglesa").", ".
		valTpDato($vexacc3, "real_inglesa").", ".
		valTpDato($vexacc4, "real_inglesa")."
	FROM an_pedido
	WHERE id_pedido = ".valTpDato($idPedido, "int").";";
	$Result1 = mysql_query($insertSQL, $conex);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$idPedido = mysql_insert_id($conex);
} else if ($idPedido > 0){
	validaModulo("an_pedido_venta_list",editar);
	
	$updateSQL = "UPDATE an_pedido SET
		id_clave_movimiento = ".valTpDato($idClaveMovimiento, "int").",
		id_unidad_fisica = ".valTpDato($idUnidadFisica, "int").",
		gerente_ventas = ".valTpDato($idGerenteVenta, "int").",
		fecha_gerente_ventas = ".valTpDato(date("Y-m-d",strtotime($txtFechaVenta)), "date").",
		administracion = ".valTpDato($idGerenteAdministracion, "int").",
		fecha_administracion = ".valTpDato(date("Y-m-d",strtotime($txtFechaAdministracion)), "date").",
		precio_retoma = ".valTpDato($txtPrecioRetoma, "real_inglesa").",
		fecha_retoma = ".setmysqlfecha($txtFechaRetoma).",
		precio_venta = ".valTpDato($txtPrecioBase, "real_inglesa").",
		monto_descuento = ".valTpDato($txtDescuento, "real_inglesa").",
		tipo_inicial = ".valTpDato($hddTipoInicial, "int").",
		porcentaje_inicial = ".valTpDato($txtPorcInicial, "real_inglesa").",
		inicial = ".valTpDato($inicial, "real_inglesa").",
		saldo_financiar = ".valTpDato($txtSaldoFinanciar, "real_inglesa").",
		meses_financiar = ".valTpDato($lstMesesFinanciar, "real_inglesa").",
		interes_cuota_financiar = ".valTpDato($txtInteresCuotaFinanciar, "real_inglesa").",
		cuotas_financiar = ".valTpDato($txtCuotasFinanciar, "real_inglesa").",
		id_banco_financiar = ".valTpDato($lstBancoFinanciar, "int").",
		total_inicial_gastos = ".valTpDato($txtTotalInicialGastos, "real_inglesa").",
		total_adicional_contrato = ".valTpDato($txtTotalAdicionalContrato, "real_inglesa").",
		monto_flat = ".valTpDato($txtMontoFLAT, "real_inglesa").",
		total_accesorio = ".valTpDato($txtTotalAccesorio, "real_inglesa").",
		observaciones = ".valTpDato($txtObservacion, "text").",
		asesor_ventas = ".valTpDato($idAsesorVentas, "int").",
		anticipo = ".valTpDato($txtMontoAnticipo, "real_inglesa").",
		complemento_inicial = ".valTpDato($txtMontoComplementoInicial, "real_inglesa").",
		forma_pago_precio_total = ".valTpDato($txtPrecioTotal, "real_inglesa").",
		id_poliza = ".valTpDato($idPoliza, "int").",
		inicial_poliza = ".valTpDato($txtInicialPoliza, "real_inglesa").",
		cuotas_poliza = ".valTpDato($txtCuotasPoliza, "real_inglesa").",
		monto_seguro = ".valTpDato($txtMontoSeguro, "real_inglesa").",
		fecha_reserva_venta = ".valTpDato(date("Y-m-d",strtotime($txtFechaReserva)), "date").",
		fecha_entrega = ".valTpDato(date("Y-m-d",strtotime($txtFechaEntrega)), "date").",
		total_pedido = ".valTpDato($txtTotalPedido, "real_inglesa").",
		porcentaje_iva = ".valTpDato($txtPorcIva, "real_inglesa").",
		porcentaje_impuesto_lujo = ".valTpDato($txtPorcIvaLujo, "real_inglesa").",
		id_cliente = ".valTpDato($idCliente, "int").",
		porcentaje_flat = ".valTpDato($txtPorcFLAT, "real_inglesa").",
		meses_poliza = ".valTpDato($txtMesesPoliza, "real_inglesa").",
		empresa_accesorio = '".$empresa_accesorio."',
		exacc1 = ".valTpDato($exacc1, "text").",
		exacc2 = ".valTpDato($exacc2, "text").",
		exacc3 = ".valTpDato($exacc3, "text").",
		exacc4 = ".valTpDato($exacc4, "text").",
		vexacc1 = ".valTpDato($vexacc1, "real_inglesa").",
		vexacc2 = ".valTpDato($vexacc2, "real_inglesa").",
		vexacc3 = ".valTpDato($vexacc3, "real_inglesa").",
		vexacc4 = ".valTpDato($vexacc4, "real_inglesa")."
	WHERE id_pedido = ".valTpDato($idPedido, "int").";";
	$Result1 = mysql_query($updateSQL, $conex);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
} else {
	validaModulo("an_pedido_venta_list",insertar);
	
	// VERIFICA QUE EL CLIENTE DEL PEDIDO ESTE CREADO COMO CLIENTE
	$tipoCuentaCliente = getmysql("SELECT tipo_cuenta_cliente FROM cj_cc_cliente WHERE id = ".$idCliente.";");
	if ($tipoCuentaCliente == 1) {
		echo "<script language=\"javascript\" type=\"text/javascript\">
			alert('".utf8_decode("El Prospecto perteneciente a este Pedido, no está Aprobado como Cliente. Recomendamos lo apruebe en la pantalla de Prospectación, para así generar dicho Presupuesto")."');
			history.go(-1);
		</script>";
		exit;
	}
	
	// VERIFICA QUE LA UNIDAD FISICA NO HAYA SIDO RESERVADA ANTES
	$queryUnidadReservada = sprintf("SELECT estado_venta FROM an_unidad_fisica
	WHERE id_unidad_fisica = %s
		AND estado_venta IN ('RESERVADO');",
		valTpDato($idUnidadFisica, "int"));
	$rsUnidadReservada = mysql_query($queryUnidadReservada);
	if (!$rsUnidadReservada) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsUnidadReservada = mysql_num_rows($rsUnidadReservada);
	if ($totalRowsUnidadReservada > 0) {
		echo "<script language=\"javascript\" type=\"text/javascript\">
			alert('La unidad seleccionada ya se ha reservado hace pocos instantes');
			history.go(-1);
		</script>";
		exit;
	}
	
	// VERIFICA QUE EL PRESUPUESTO NO HAYA SIDO GENERADO ANTERIORMENTE
	if ($idPresupuesto > 0) {
		$pedidoc = getmysql("SELECT COUNT(*) FROM an_pedido WHERE id_presupuesto = ".$idPresupuesto.";");
		if ($pedidoc == 1) {
			echo "<script language=\"javascript\" type=\"text/javascript\">
				alert('El Pedido del Presupesto ".$idPresupuesto." ya fu&eacute; Generado');
				window.location = 'an_pedido_venta_list.php';
			</script>";
			exit;
		}
	}
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = %s
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato(39, "int"), // 39 = Pedido Venta Vehículos
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	
	// INSERTA LOS DATOS DEL PEDIDO
	$insertSQL = "INSERT INTO an_pedido (numeracion_pedido, id_presupuesto, id_clave_movimiento, id_unidad_fisica, fecha, gerente_ventas, fecha_gerente_ventas, administracion, fecha_administracion, precio_retoma, fecha_retoma, estado_pedido, precio_venta, monto_descuento, tipo_inicial, porcentaje_inicial, inicial, saldo_financiar, meses_financiar, interes_cuota_financiar, cuotas_financiar, id_banco_financiar, total_inicial_gastos, total_adicional_contrato, monto_flat, total_accesorio, observaciones, asesor_ventas, anticipo, complemento_inicial, forma_pago_precio_total, id_poliza, inicial_poliza, cuotas_poliza, monto_seguro, fecha_reserva_venta, fecha_entrega, total_pedido, porcentaje_iva, porcentaje_impuesto_lujo, id_cliente, id_empresa, porcentaje_flat, meses_poliza, empresa_accesorio, exacc1, exacc2, exacc3, exacc4, vexacc1, vexacc2, vexacc3, vexacc4)
	VALUES (".valTpDato($numeroActual, "text").", ".
		valTpDato($idPresupuesto, "int").", ".
		valTpDato($idClaveMovimiento, "int").", ".
		valTpDato($idUnidadFisica, "int").", ".
		valTpDato("CURRENT_DATE()", "campo").", ".
		valTpDato($idGerenteVenta, "int").", ".
		valTpDato(date("Y-m-d",strtotime($txtFechaVenta)), "date").", ".
		valTpDato($idGerenteAdministracion, "int").", ".
		valTpDato(date("Y-m-d",strtotime($txtFechaAdministracion)), "date").", ".
		valTpDato($txtPrecioRetoma, "real_inglesa").", ".
		setmysqlfecha($txtFechaRetoma).", ".
		valTpDato(1, "int").", ". // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
		valTpDato($txtPrecioBase, "real_inglesa").", ".
		valTpDato($txtDescuento, "real_inglesa").", ".
		valTpDato($hddTipoInicial, "int").", ".
		valTpDato($txtPorcInicial, "real_inglesa").", ".
		valTpDato($inicial, "real_inglesa").", ".
		valTpDato($txtSaldoFinanciar, "real_inglesa").", ".
		valTpDato($lstMesesFinanciar, "real_inglesa").", ".
		valTpDato($txtInteresCuotaFinanciar, "real_inglesa").", ".
		valTpDato($txtCuotasFinanciar, "real_inglesa").", ".
		valTpDato($lstBancoFinanciar, "int").", ".
		valTpDato($txtTotalInicialGastos, "real_inglesa").", ".
		valTpDato($txtTotalAdicionalContrato, "real_inglesa").", ".
		valTpDato($txtMontoFLAT, "real_inglesa").", ".
		valTpDato($txtTotalAccesorio, "real_inglesa").", ".
		valTpDato($txtObservacion, "text").", ".
		valTpDato($idAsesorVentas, "int").", ".
		valTpDato($txtMontoAnticipo, "real_inglesa").", ".
		valTpDato($txtMontoComplementoInicial, "real_inglesa").", ".
		valTpDato($txtPrecioTotal, "real_inglesa").", ".
		valTpDato($idPoliza, "int").", ".
		valTpDato($txtInicialPoliza, "real_inglesa").", ".
		valTpDato($txtCuotasPoliza, "real_inglesa").", ".
		valTpDato($txtMontoSeguro, "real_inglesa").", ".
		valTpDato(date("Y-m-d",strtotime($txtFechaReserva)), "date").", ".
		valTpDato(date("Y-m-d",strtotime($txtFechaEntrega)), "date").", ".
		valTpDato($txtTotalPedido, "real_inglesa").", ".
		valTpDato($txtPorcIva, "real_inglesa").", ".
		valTpDato($txtPorcIvaLujo, "real_inglesa").", ".
		valTpDato($idCliente, "int").", ".
		valTpDato($idEmpresa, "int").", ".
		valTpDato($txtPorcFLAT, "real_inglesa").", ".
		valTpDato($txtMesesPoliza, "real_inglesa").", ".
		valTpDato($empresa_accesorio, "text").", ".
		valTpDato($exacc1, "text").", ".
		valTpDato($exacc2, "text").", ".
		valTpDato($exacc3, "text").", ".
		valTpDato($exacc4, "text").", ".
		valTpDato($vexacc1, "real_inglesa").", ".
		valTpDato($vexacc2, "real_inglesa").", ".
		valTpDato($vexacc3, "real_inglesa").", ".
		valTpDato($vexacc4, "real_inglesa").");";
	$Result1 = mysql_query($insertSQL, $conex);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$idPedido = mysql_insert_id($conex);
}

// Para que codifique la entrada de caracteres especiales utf-8
inputmysqlutf8();

// ADICIONALES
$hddAdicional = $_POST['ac'];
for ($i = 0; $i < count($hddAdicional); $i++) {
	$idAccesorio = "acc".$hddAdicional[$i];
	
	$idDetalle = getmysqlnum(getempty($_POST['hddIdDetItm'.$idAccesorio],'null'));
	$precioUnitario = getmysqlnum($_POST['txtPrecioConIvaItm'.$idAccesorio]);
	$ivaAccesorio = getmysqlnum(getempty($_POST['hddPorcIvaItm'.$idAccesorio],'9'));
	$montoIvaAccesorio = getempty($_POST['hddMontoIvaItm'.$idAccesorio],'null');
	$costoAccesorio = getmysqlnum(getempty($_POST['hddCostoUnitarioItm'.$idAccesorio],'0'));
	if ($ivaAccesorio != 0) {
		$precioUnitario = $precioUnitario - ($precioUnitario * $ivaAccesorio / (100 + $ivaAccesorio));
	}
	$cbxCondicion = getmysqlnum(getempty($_POST['cbxCondicionItm'.$idAccesorio],'null'));
	$cbxMostrar = getmysqlnum(getempty($_POST['cbxMostrarItm'.$idAccesorio],'null'));
	$lstTipoAccesorioItm = getmysqlnum(getempty($_POST['lstTipoAccesorioItm'.$idAccesorio],'null'));
	
	if ($_POST['acp'][$i] == '') {
		// ACCESORIOS
		if ($_POST['acaccion'][$i] == 1 || ($_POST['acaccion'][$i] == 3 && $idFactura > 0)) {
			$insertSQL = sprintf("INSERT INTO an_accesorio_pedido (id_pedido, id_accesorio, id_tipo_accesorio, precio_accesorio, iva_accesorio, costo_accesorio, porcentaje_iva_accesorio, id_condicion_pago, id_condicion_mostrar)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idPedido, "int"),
				valTpDato($hddAdicional[$i], "int"),
				valTpDato($lstTipoAccesorioItm, "int"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($montoIvaAccesorio, "real_inglesa"),
				valTpDato($costoAccesorio, "real_inglesa"),
				valTpDato($ivaAccesorio, "real_inglesa"),
				valTpDato($cbxCondicion, "int"),
				valTpDato($cbxMostrar, "int"));	
			$Result1 = mysql_query($insertSQL, $conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);	
				
		} else if ($_POST['acaccion'][$i] == 3) {
			$updateSQL = sprintf("UPDATE an_accesorio_pedido SET
				id_tipo_accesorio = %s,
				precio_accesorio = %s,
				iva_accesorio = %s,
				costo_accesorio = %s,
				porcentaje_iva_accesorio = %s,
				id_condicion_pago = %s,
				id_condicion_mostrar = %s
			WHERE id_accesorio_pedido = %s;",
				valTpDato($lstTipoAccesorioItm, "int"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($montoIvaAccesorio, "real_inglesa"),
				valTpDato($costoAccesorio, "real_inglesa"),
				valTpDato($ivaAccesorio, "real_inglesa"),
				valTpDato($cbxCondicion, "int"),
				valTpDato($cbxMostrar, "int"),
				valTpDato($idDetalle, "int"));
			$Result1 = mysql_query($updateSQL, $conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);	
			
		} else {
			$deleteSQL = sprintf("DELETE FROM an_accesorio_pedido WHERE id_accesorio_pedido = %s;",
				valTpDato($idDetalle, "int"));
			$Result1 = mysql_query($deleteSQL, $conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		}
	} else {
		// PAQUETES
		if ($_POST['acaccion'][$i] == 1 || ($_POST['acaccion'][$i] == 3 && $idFactura > 0)) {
			$insertSQL = sprintf("INSERT INTO an_paquete_pedido (id_pedido, id_acc_paq, id_tipo_accesorio, precio_accesorio, iva_accesorio, costo_accesorio, porcentaje_iva_accesorio, id_condicion_pago, id_condicion_mostrar)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idPedido, "int"),
				valTpDato($_POST['acp'][$i], "int"),
				valTpDato($lstTipoAccesorioItm, "int"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($montoIvaAccesorio, "real_inglesa"),
				valTpDato($costoAccesorio, "real_inglesa"),
				valTpDato($ivaAccesorio, "real_inglesa"),
				valTpDato($cbxCondicion, "int"),
				valTpDato($cbxMostrar, "int"));		
			$Result1 = mysql_query($insertSQL, $conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);	
			
		} else if ($_POST['acaccion'][$i] == 3) {
			$updateSQL = "UPDATE an_paquete_pedido SET
				id_tipo_accesorio = ".$lstTipoAccesorioItm.",
				precio_accesorio = ".$precioUnitario.",
				iva_accesorio = ".$montoIvaAccesorio.",
				costo_accesorio = ".$costoAccesorio.",
				porcentaje_iva_accesorio = ".$ivaAccesorio.",
				id_condicion_pago = ".$cbxCondicion.",
				id_condicion_mostrar = ".$cbxMostrar."
			WHERE id_paquete_pedido = ".$idDetalle.";";		
			$Result1 = mysql_query($updateSQL, $conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			
		} else {
			$deleteSQL = sprintf("DELETE FROM an_paquete_pedido WHERE id_paquete_pedido = %s;",
				valTpDato($idDetalle, "int"));
			$Result1 = mysql_query($deleteSQL, $conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		}	
	}
}

if (!($idFactura > 0)) {
	// ACTUALIZA EL ESTADO DEL PRESUPUESTO
	$updateSQL = sprintf("UPDATE an_presupuesto SET
		estado = 1
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
	$Result1 = mysql_query($updateSQL, $conex);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	
	// RESERVA LA UNIDAD FISICA.
	$updateSQL = sprintf("UPDATE an_unidad_fisica SET
		estado_venta = 'RESERVADO'
	WHERE id_unidad_fisica = %s
		AND estado_venta IN ('POR REGISTRAR','DISPONIBLE');",
		valTpDato($idUnidadFisica, "int"));
	$Result1 = mysql_query($updateSQL, $conex);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
}

// ACTUALIZA EL ESTATUS DEL PEDIDO SI LA UNIDAD NO HA SIDO TOTALMMENTE REGISTRADA O SI PERTENECE AL ALMACEN DE OTRA EMPRESA
// (0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada)
$updateSQL = sprintf("UPDATE an_pedido ped_vent SET
	ped_vent.estado_pedido = 3
WHERE ped_vent.id_pedido = %s
	AND ((SELECT uni_fis.estado_compra FROM an_unidad_fisica uni_fis
		WHERE uni_fis.id_unidad_fisica = %s) IN ('COMPRADO')
		OR (SELECT alm.id_empresa
			FROM an_almacen alm
				INNER JOIN an_unidad_fisica uni_fis ON (alm.id_almacen = uni_fis.id_almacen)
			WHERE uni_fis.id_unidad_fisica = %s) <> ped_vent.id_empresa);",
	valTpDato($idPedido, "int"),
	valTpDato($idUnidadFisica, "int"),
	valTpDato($idUnidadFisica, "int"));
$Result1 = mysql_query($updateSQL, $conex);
if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

mysql_query("COMMIT;");

cerrar();

echo '<script language="javascript" type="text/javascript">
	alert("Se ha registrado el pedido");
	window.location = "an_ventas_pedido_editar.php?view=import&id='.$idPedido.'";
</script>';
?>