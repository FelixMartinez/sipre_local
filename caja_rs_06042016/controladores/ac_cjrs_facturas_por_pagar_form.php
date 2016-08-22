<?php


function actualizarObjetosExistentes($valForm,$valFormListadoPagos){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valForm['hddObjDetallePago']); $cont++) {
		$caracter = substr($valForm['hddObjDetallePago'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valFormListadoPagos['txtFormaPago'.$valor])){
			$cadena .= "|".$valor;
		}
	}
	
	$objResponse->assign("hddObjDetallePago","value",$cadena);
	$objResponse->script("calcularTotal();");
	
	return $objResponse;
}

function actualizarObjetosExistentesDetalleDeposito($valForm){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valForm['hddObjDetallePagoDeposito']); $cont++) {
		$caracter = substr($valForm['hddObjDetallePagoDeposito'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valForm['txtFormaPagoDetalleDeposito'.$valor])){
			$cadena .= "|".$valor;
		}
	}
	
	$objResponse->assign("hddObjDetallePagoDeposito","value",$cadena);
	$objResponse->script("calcularTotalDeposito()");
	
	return $objResponse;
}

function asignarCliente($idCliente){
	$objResponse = new xajaxResponse();
	
	$queryCliente = sprintf("SELECT
		id,
		CONCAT_WS('-', lci, ci) AS ci_cliente,
		CONCAT_WS(' ', nombre, apellido) AS nombre_cliente,
		direccion,
		telf
	FROM cj_cc_cliente WHERE id = %s",
		valTpDato($idCliente, "int"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
	$objResponse->assign("txtTelefonosCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	
	$objResponse->script("$('divFlotante').style.display = 'none';");
	
	return $objResponse;
}

function buscarAnticipoNotaCredito($valForm, $idCliente, $tipoPago, $valFormObj, $valFormListado){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valFormObj['hddObjDetallePago']); $cont++) {
		$caracter = substr($valFormObj['hddObjDetallePago'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valFormListado['txtFormaPago'.$valor])){
			if ($valFormListado['txtFormaPago'.$valor] == $tipoPago)
			$cadena .= ",".$valFormListado['txtIdDocumento'.$valor];
		}
	}
	
	if ($cadena != '')
		$cadena = substr($cadena,1,strlen($cadena));
		
	$valBusq = sprintf("%s|%s|%s|%s",
		$valForm['txtCriterioBusqCliente'],
		$idCliente,
		$tipoPago,
		$cadena);
		
	$objResponse->loadCommands(listadoAnticipoNotaCredito(0,"","",$valBusq));
	
	return $objResponse;
}

function cargarFactura($idFactura){
	$objResponse = new xajaxResponse();
	
	$sqlSelectFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s",$idFactura);
	$rsSelectFactura = mysql_query($sqlSelectFactura);
	if (!$rsSelectFactura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFactura);
	$rowSelectFactura = mysql_fetch_array($rsSelectFactura);
	
	$sqlSelectModulo = sprintf("SELECT descripcionModulo FROM pg_modulos WHERE id_enlace_concepto = %s",$rowSelectFactura['idDepartamentoOrigenFactura']);
	$rsSelectModulo = mysql_query($sqlSelectModulo);
	if (!$rsSelectModulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectModulo);
	$rowSelectModulo = mysql_fetch_array($rsSelectModulo);
	
	$objResponse->loadCommands(asignarCliente($rowSelectFactura['idCliente']));
	$objResponse->assign("txtNumeroFactura","value",$rowSelectFactura['numeroFactura']);
	$objResponse->assign("txtNumeroControlFactura","value",$rowSelectFactura['numeroControl']);
	$objResponse->assign("hddIdModulo","value",$rowSelectFactura['idDepartamentoOrigenFactura']);
	$objResponse->assign("txtDepartamento","value",utf8_encode($rowSelectModulo['descripcionModulo']));
	$objResponse->assign("txtFecha","value",date("d-m-Y",strtotime($rowSelectFactura['fechaRegistroFactura'])));
	$objResponse->assign("txtFechaVencimiento","value",date("d-m-Y",strtotime($rowSelectFactura['fechaVencimientoFactura'])));
	$objResponse->assign("txtSaldoFactura","value",$rowSelectFactura['saldoFactura']);
	$objResponse->assign("txtMontoPorPagar","value",$rowSelectFactura['saldoFactura']);
	$objResponse->assign("hddMontoPorPagar","value",$rowSelectFactura['saldoFactura']);
	$objResponse->assign("hddMontoFaltaPorPagar","value",$rowSelectFactura['saldoFactura']);
	
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
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$objResponse->loadCommands(asignarEmpresaUsuario($rowSelectFactura['id_empresa'], "Empresa", "ListaEmpresa"));
	}
	
	return $objResponse;
}

function cargarBancoCliente($idTd, $idSelect){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT idBanco, nombreBanco FROM bancos WHERE idBanco <> 1 ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<select name='%s' id='%s'>",$idSelect,$idSelect);
		$html .= sprintf("<option value = ''>Seleccione");
		while ($row = mysql_fetch_array($rs)){
			$html .= sprintf("<option value = '%s'>%s",$row["idBanco"],utf8_encode($row["nombreBanco"]));
		}
	$html .= "</select>";
	
	mysql_query("COMMIT");
	
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function cargarBancoCompania($tipoPago){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT idBanco, (SELECT nombreBanco FROM bancos WHERE bancos.idBanco = cuentas.idBanco) AS banco FROM cuentas GROUP BY cuentas.idBanco ORDER BY banco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<select name='selBancoCompania' id='selBancoCompania' onchange='xajax_cargarCuentasCompania(this.value,".$tipoPago.");' >");
		$html .= sprintf("<option value = ''>Seleccione");
		while ($row = mysql_fetch_array($rs)){
			$html .= sprintf("<option value = '%s'>%s",$row["idBanco"],utf8_encode($row["banco"]));
		}
	$html .= "</select>";
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdBancoCompania","innerHTML",$html);
	
	return $objResponse;
}

function cargarCuentasCompania($idBanco, $tipoPago){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas WHERE idBanco = %s AND estatus = 1",$idBanco);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<div align='justify'><strong>
						<select name='selNumeroCuenta' id='selNumeroCuenta' onchange='xajax_cargarTarjetaCuenta(this.value,".$tipoPago.");'>");
	$registros = mysql_num_rows($rs);
	if ($registros > 1)
		$html .= sprintf("<option value = ''>Seleccione");
		
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value = '%s'>%s",$row["idCuentas"],utf8_encode($row["numeroCuentaCompania"]));
		if ($registros == 1)
			$objResponse->loadCommands(cargarTarjetaCuenta($row["idCuentas"],$tipoPago));
	}
	$html .= "</select></strong></div>";
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdNumeroCuentaSelect","innerHTML",$html);
	
	return $objResponse;
}

function cargarPago($formDetallePago, $formDetalleDeposito){
	$objResponse = new xajaxResponse();
	
	if (str_replace(",","",$formDetallePago['hddMontoFaltaPorPagar']) < str_replace(",","",$formDetallePago['montoPago'])){
		errorCargarPago($objResponse);
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la Factura");
	}
	
	for ($cont = 0; $cont <= strlen($formDetallePago['hddObjDetallePago']); $cont++) {
			$caracter = substr($formDetallePago['hddObjDetallePago'], $cont, 1);
			
			if ($caracter != "|" && $caracter != "")
				$cadenaDetallePago.= $caracter;
			else {
				$arrayObjDetallePago[] = $cadenaDetallePago;
				$cadenaDetallePago = "";
				$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			}
		}
		
	$sigValor = $arrayObjDetallePago[count($arrayObjDetallePago)-1] + 1;
	
	if ($formDetallePago['selTipoPago'] == 3 || $formDetallePago['selTipoPago'] == 4|| $formDetallePago['selTipoPago'] == 5|| $formDetallePago['selTipoPago'] == 6){
		$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",$formDetallePago['selNumeroCuenta']);
		$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta);
		if (!$rsBuscarNumeroCuenta){ errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlBuscarNumeroCuenta); }
		$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	}
	
	if($formDetallePago['selTipoPago'] == 1){
		$tipoPago = "Efectivo";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = "-";
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 2){
		$tipoPago = "Cheque";
		$tipoTarjetaCredito = "-";
		$bancoCliente = nombreBanco($formDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $formDetallePago['numeroCuenta'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $formDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = $formDetallePago['numeroCuenta'];
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 3){
		$tipoPago = "Deposito";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = $formDetallePago['txtFechaDeposito'];
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = nombreBanco($formDetallePago['selBancoCompania']);
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = $formDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $formDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 4){
		$tipoPago = "Transferencia Bancaria";
		$tipoTarjetaCredito = "-";
		$bancoCliente = nombreBanco($formDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = nombreBanco($formDetallePago['selBancoCompania']);
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $formDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $formDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $formDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 5){
		$tipoPago = "Tarjeta de Credito";
		$tipoTarjetaCredito = $formDetallePago['tarjeta'];
		$bancoCliente = nombreBanco($formDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = $formDetallePago['porcentajeRetencion'];
		$montoTotalRetencion = $formDetallePago['montoTotalRetencion'];
		$bancoCompania = nombreBanco($formDetallePago['selBancoCompania']);
		$porcentajeComision = $formDetallePago['porcentajeComision'];
		$montoTotalComision = $formDetallePago['montoTotalComision'];
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $formDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $formDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $formDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = $formDetallePago['tarjeta'];
	}
	else if($formDetallePago['selTipoPago'] == 6){
		$tipoPago = "Tarjeta de Debito";
		$tipoTarjetaCredito = "-";
		$bancoCliente = nombreBanco($formDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = nombreBanco($formDetallePago['selBancoCompania']);
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $formDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $formDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $formDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = 6;
	}
	else if($formDetallePago['selTipoPago'] == 7){
		$tipoPago = "Anticipo";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = $formDetallePago['hddIdAnticipoNotaCredito'];
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 8){
		$tipoPago = "Nota de Credito";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = $formDetallePago['hddIdAnticipoNotaCredito'];
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 9){
		$tipoPago = "Retencion";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 10){
		$tipoPago = "Retencion ISLR";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	
	$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItm:%s', 'align':'left', 'class':'textoGris_11px %s', 'height':'24', 'title':'trItm:%s'}).adopt([
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"<button type='button' onclick='confirmarEliminarPago(%s);' title='Eliminar'><img src='../img/iconos/delete.png'/></button>".
			"<input type='hidden' id='txtFechaDeposito%s' name='txtFechaDeposito%s' readonly='readonly' value='%s' title='fechaDeposito'/>".
			"<input type='hidden' id='txtFormaPago%s' name='txtFormaPago%s' readonly='readonly' value='%s' title='txtFormaPago'/>".
			"<input type='hidden' id='txtNumeroDocumento%s' name='txtNumeroDocumento%s' readonly='readonly' value='%s' title='txtNumeroDocumento'/>".
			"<input type='hidden' id='txtIdDocumento%s' name='txtIdDocumento%s' readonly='readonly' value='%s' title='txtIdDocumento'/>".
			"<input type='hidden' id='txtBancoCompania%s' name='txtBancoCompania%s' readonly='readonly' value='%s' title='txtBanco'/>".
			"<input type='hidden' id='txtBancoCliente%s' name='txtBancoCliente%s' readonly='readonly' value='%s' title='txtBancoCliente'/>".
			"<input type='hidden' id='txtNumeroCuenta%s' name='txtNumeroCuenta%s' readonly='readonly' value='%s' title='txtNumeroCuenta'/>".
			"<input type='hidden' id='txtMonto%s' name='txtMonto%s' readonly='readonly' value='%s' title='txtMonto'/>".
			"<input type='hidden' id='txtTipoTarjeta%s' name='txtTipoTarjeta%s' value='%s' title='txtTipoTarjeta'/>\")
			]);
			elemento.injectBefore('trItmPie');",
			$sigValor, $clase, $sigValor,
			$tipoPago,
			utf8_encode($bancoCliente),
			utf8_encode($bancoCompania),
			$numeroCuenta,
			$numeroControl,
			number_format($montoPagado,2,'.',','),
			$sigValor, $sigValor,
			$sigValor, $sigValor, $fechaDeposito,
			$sigValor, $sigValor, $formDetallePago['selTipoPago'],
			$sigValor, $sigValor, $numeroControl,
			$sigValor, $sigValor, $idDocumento,
			$sigValor, $sigValor, utf8_encode($bancoCompaniaOculto),
			$sigValor, $sigValor, utf8_encode($bancoClienteOculto),
			$sigValor, $sigValor, $numeroCuentaOculto,
			$sigValor, $sigValor, $montoPagado,
			$sigValor, $sigValor, $tipoTarjetaOculto));
	
	if($formDetallePago['selTipoPago'] == 3){
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		for ($cont = 0; $cont <= strlen($formDetalleDeposito['hddObjDetallePagoDeposito']); $cont++) {
			$caracter = substr($formDetalleDeposito['hddObjDetallePagoDeposito'], $cont, 1);
			
			if ($caracter != "|" && $caracter != "")
				$cadena .= $caracter;
			else {
				$arrayObj[] = $cadena;
				$cadena = "";
			}	
		}
		
		$cadena = '';
		$cadenaFormaPagoDeposito = '';
		$cadenaNroDocumentoDeposito = '';
		$cadenaBancoClienteDeposito = '';
		$cadenaNroCuentaDeposito = '';
		$cadenaMontoDeposito = '';
		foreach($arrayObj as $indice => $valor) {
			if (isset($formDetalleDeposito['txtFormaPagoDetalleDeposito'.$valor])){
				$cadenaPosicionDeposito .= $sigValor."|";
				$cadenaFormaPagoDeposito .= $formDetalleDeposito['txtFormaPagoDetalleDeposito'.$valor]."|";				
				$cadenaNroDocumentoDeposito .= $formDetalleDeposito['txtNumeroDocumentoDetalleDeposito'.$valor]."|";
				$cadenaBancoClienteDeposito .= $formDetalleDeposito['txtBancoClienteDetalleDeposito'.$valor]."|";
				$cadenaNroCuentaDeposito .= $formDetalleDeposito['txtNumeroCuentaDetalleDeposito'.$valor]."|";
				$cadenaMontoDeposito .= $formDetalleDeposito['txtMontoDetalleDeposito'.$valor]."|";
			}
		}
		$cadenaPosicionDeposito = $formDetallePago['hddObjDetalleDeposito'].$cadenaPosicionDeposito;
		$cadenaFormaPagoDeposito = $formDetallePago['hddObjDetalleDepositoFormaPago'].$cadenaFormaPagoDeposito;
		$cadenaBancoClienteDeposito = $formDetallePago['hddObjDetalleDepositoBanco'].$cadenaBancoClienteDeposito;
		$cadenaNroCuentaDeposito = $formDetallePago['hddObjDetalleDepositoNroCuenta'].$cadenaNroCuentaDeposito;
		$cadenaNroDocumentoDeposito = $formDetallePago['hddObjDetalleDepositoNroCheque'].$cadenaNroDocumentoDeposito;
		$cadenaMontoDeposito = $formDetallePago['hddObjDetalleDepositoMonto'].$cadenaMontoDeposito;
	}
	
	$arrayObjDetallePago[] = $sigValor;
	foreach($arrayObjDetallePago as $indiceDetallePago => $valorDetallePago) {
		$cadena = $formDetallePago['hddObjDetallePago']."|".$valorDetallePago;
	}
	
	$objResponse->script("document.forms['frmDetallePago'].reset();
							$('btnAgregarDetDeposito').style.display = 'none';
							$('agregar').style.display = 'none';
							$('btnAgregarDetAnticipoNotaCredito').style.display = 'none';");
	$objResponse->script("$('divFlotante').style.display='none'; xajax_eliminarPagoDetalleDepositoForzado(xajax.getFormValues('frmDetalleDeposito'));");
	
	$objResponse->assign("hddObjDetallePago","value",$cadena);
	if($formDetallePago['selTipoPago'] == 3){
		$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosicionDeposito);
		$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPagoDeposito);
		$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBancoClienteDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuentaDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroDocumentoDeposito);
		$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMontoDeposito);
	}
	
	$objResponse->script("calcularTotal()");
	
	errorCargarPago($objResponse);
	
	return $objResponse;
}

function cargarPagoDetalleDeposito($formDetalleDeposito){
	$objResponse = new xajaxResponse();
		
	for ($cont = 0; $cont <= strlen($formDetalleDeposito['hddObjDetallePagoDeposito']); $cont++) {
			$caracter = substr($formDetalleDeposito['hddObjDetallePagoDeposito'], $cont, 1);
			
			if ($caracter != "|" && $caracter != "")
				$cadenaDetallePago.= $caracter;
			else {
				$arrayObjDetallePago[] = $cadenaDetallePago;
				$cadenaDetallePago = "";
			}
		}
	
	$sigValor = $arrayObjDetallePago[count($arrayObjDetallePago)-1] + 1;
	
	if($formDetalleDeposito['lstTipoPago'] == 1){
		$tipoPago = "Efectivo";
		$bancoCliente = "-";
		$numeroCuenta = "-";
		$numeroControl = "-";
		$montoPagado = str_replace(",","",$formDetalleDeposito['txtMontoDeposito']);
		
		$bancoClienteOculto = "-";
	}
	else if($formDetalleDeposito['lstTipoPago'] == 2){
		$tipoPago = "Cheque";
		$bancoCliente = nombreBanco($formDetalleDeposito['lstBancoDeposito']);
		$numeroCuenta = $formDetalleDeposito['txtNroCuentaDeposito'];
		$numeroControl = $formDetalleDeposito['txtNroChequeDeposito'];
		$montoPagado = str_replace(",","",$formDetalleDeposito['txtMontoDeposito']);
		
		$bancoClienteOculto = $formDetalleDeposito['lstBancoDeposito'];
	}			
	
	$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItmDetalle:%s', 'class':'textoGris_11px', 'title':'trItmDetalle:%s'}).adopt([
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"<button type='button' onclick='confirmarEliminarPagoDetalleDeposito(%s);' title='Eliminar'><img src='../img/iconos/delete.png'/></button>".
			"<input type='hidden' id='txtFormaPagoDetalleDeposito%s' name='txtFormaPagoDetalleDeposito%s' readonly='readonly' value='%s'/>".
			"<input type='hidden' id='txtNumeroDocumentoDetalleDeposito%s' name='txtNumeroDocumentoDetalleDeposito%s' readonly='readonly' value='%s'/>".
			"<input type='hidden' id='txtBancoClienteDetalleDeposito%s' name='txtBancoClienteDetalleDeposito%s' readonly='readonly' value='%s'/>".
			"<input type='hidden' id='txtNumeroCuentaDetalleDeposito%s' name='txtNumeroCuentaDetalleDeposito%s' readonly='readonly' value='%s'/>".
			"<input type='hidden' id='txtMontoDetalleDeposito%s' name='txtMontoDetalleDeposito%s' readonly='readonly' value='%s'/>\")
			]);
			elemento.injectBefore('trItmPieDeposito');",
			$sigValor, $sigValor,
			$tipoPago,
			utf8_encode($bancoCliente),
			$numeroCuenta,
			$numeroControl,
			number_format($montoPagado,2,'.',','),
			$sigValor, $sigValor,
			$sigValor, $sigValor, $formDetalleDeposito['lstTipoPago'],
			$sigValor, $sigValor, $numeroControl,
			$sigValor, $sigValor, utf8_encode($bancoClienteOculto),
			$sigValor, $sigValor, $numeroCuenta,
			$sigValor, $sigValor, $montoPagado));
	
	$arrayObjDetallePago[] = $sigValor;
	foreach($arrayObjDetallePago as $indiceDetallePago => $valorDetallePago) {
		$cadena = $formDetalleDeposito['hddObjDetallePagoDeposito']."|".$valorDetallePago;
	}
	
	$objResponse->assign("txtMontoDeposito","value","");
	$objResponse->assign("txtNroCuentaDeposito","value","");
	$objResponse->assign("txtNroChequeDeposito","value","");
	
	$objResponse->assign("hddObjDetallePagoDeposito","value",$cadena);
	
	$objResponse->script("calcularTotalDeposito()");
	
	return $objResponse;
}

function cargarPorcentajeTarjetaCredito($idCuenta,$idTarjeta){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto 
	WHERE id_cuenta = %s 
		AND id_tipo_tarjeta = %s",
		valTpDato($idCuenta,'int'),
		valTpDato($idTarjeta,'int'));
	$rsQuery = mysql_query($query);
	if (!$rsQuery) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
	$rowQuery = mysql_fetch_array($rsQuery);
	
	$objResponse->assign("porcentajeRetencion","value",$rowQuery['porcentaje_islr']);
	$objResponse->assign("porcentajeComision","value",$rowQuery['porcentaje_comision']);
	
	$objResponse->script("calcularMontoTotalTarjetaCredito();");
	
	return $objResponse;
}

function cargarSaldoDocumento($idDocumento,$formaPago){
	$objResponse = new xajaxResponse();
	
	if ($formaPago == 7) { // ANTICIPOS
		$query = sprintf("SELECT saldoAnticipo AS saldoDocumento, numeroAnticipo AS numeroDocumento
		FROM cj_cc_anticipo WHERE idAnticipo = %s", $idDocumento);
		$documento = "Anticipo";
	} else { // NOTAS DE CREDITO
		$query = sprintf("SELECT saldoNotaCredito AS saldoDocumento, numeracion_nota_credito AS numeroDocumento
		FROM cj_cc_notacredito WHERE idNotaCredito = %s", $idDocumento);
		$documento = "Nota de Credito";
	}
	$rsSelectDocumento = mysql_query($query);
	if (!$rsSelectDocumento) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
	$rowSelectDocumento = mysql_fetch_array($rsSelectDocumento);
	
	$objResponse->assign("hddIdDocumento","value",$idDocumento);
	$objResponse->assign("txtNroDocumento","value",$rowSelectDocumento['numeroDocumento']);
	$objResponse->assign("txtSaldoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'],2,'.',','));
	$objResponse->assign("txtMontoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'],2,'.',','));
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML",$documento);
	$objResponse->script("
	if ($('divFlotante1').style.display == 'none') {
		$('divFlotante1').style.display = '';
		centrarDiv($('divFlotante1'));
		
		$('txtMontoDocumento').focus();}");
		
	return $objResponse;
}

function cargarTarjetaCuenta($idCuenta,$tipoPago){
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 5) {
		$query = sprintf("SELECT idTipoTarjetaCredito, descripcionTipoTarjetaCredito
						FROM tipotarjetacredito 
						WHERE idTipoTarjetaCredito IN (SELECT id_tipo_tarjeta
														FROM te_retencion_punto
														WHERE id_cuenta = %s AND porcentaje_islr <> '')",$idCuenta);
		$rsQuery = mysql_query($query);
		if (!$rsQuery) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
		
		$html = "<select id='tarjeta' name='tarjeta' onchange='xajax_cargarPorcentajeTarjetaCredito(".$idCuenta.",this.value)'>
				 	<option value=''>Seleccione...</option>";
		
		while($rowQuery = mysql_fetch_array($rsQuery)){
			$html .= sprintf("<option value='%s'>%s</option>",$rowQuery['idTipoTarjetaCredito'],$rowQuery['descripcionTipoTarjetaCredito']);
		}
		$html .= "</select>";
		
		$objResponse->assign("tdTipoTarjetaCredito","innerHTML",$html);
	} else if ($tipoPago == 6) {
		$query = sprintf("SELECT porcentaje_comision FROM te_retencion_punto WHERE id_cuenta = %s AND id_tipo_tarjeta = 6",
							valTpDato($idCuenta,'int'));
		$rsQuery = mysql_query($query);
		if (!$rsQuery) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
		
		$rowQuery = mysql_fetch_array($rsQuery);
		
		$objResponse->assign("porcentajeComision","value",$rowQuery['porcentaje_comision']);
	}
	
	return $objResponse;
}

function cargarTipoPago(){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	//INCLUYE TODAS LAS FORMAS DE PAGO
	$query = sprintf("SELECT * FROM formapagos WHERE idFormaPago NOT IN (11) ORDER BY nombreFormaPago ASC;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<div align='justify'>
						<select name='selTipoPago' id='selTipoPago' onChange='cambiar()'>
							<option value=''>Seleccione...</option>");
	
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value = '%s'>%s",$row["idFormaPago"],$row["nombreFormaPago"]);
	}
	$html .= sprintf("</select>
						</div>");
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarTipoPagoDetalleDeposito(){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT * FROM formapagos where idFormaPago <= 2");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<div align='justify'>
						<select name='lstTipoPago' id='lstTipoPago' onChange='cambiarTipoPagoDetalleDeposito()'>
							<option value=''>Seleccione...</option>");
	
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value = '%s'>%s",$row["idFormaPago"],$row["nombreFormaPago"]);
	}
	$html .= sprintf("</select>
						</div>");
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdlstTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function eliminarDetalleDeposito($pos,$valForm){
	$objResponse = new xajaxResponse();
	
	$arrayPosiciones = explode("|",$valForm['hddObjDetalleDeposito']);
	$arrayFormaPago = explode("|",$valForm['hddObjDetalleDepositoFormaPago']);
	$arrayBanco = explode("|",$valForm['hddObjDetalleDepositoBanco']);
	$arrayNroCuenta = explode("|",$valForm['hddObjDetalleDepositoNroCuenta']);
	$arrayNroCheque = explode("|",$valForm['hddObjDetalleDepositoNroCheque']);
	$arrayMonto = explode("|",$valForm['hddObjDetalleDepositoMonto']);
	
	$cadenaPosiciones = "";
	$cadenaFormaPago = "";
	$cadenaBanco = "";
	$cadenaNroCuenta = "";
	$cadenaNroCheque = "";
	$cadenaMonto = "";
	
	foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
		if ($valorDeposito != $pos && $valorDeposito != ''){
			$cadenaPosiciones .= $valorDeposito."|";
			$cadenaFormaPago .= $arrayFormaPago[$indiceDeposito]."|";
			$cadenaBanco .= $arrayBanco[$indiceDeposito]."|";
			$cadenaNroCuenta .= $arrayNroCuenta[$indiceDeposito]."|";
			$cadenaNroCheque .= $arrayNroCheque[$indiceDeposito]."|";
			$cadenaMonto .= $arrayMonto[$indiceDeposito]."|";
		}
	}
	
	$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosiciones);
	$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPago);
	$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBanco);
	$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuenta);
	$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroCheque);
	$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMonto);
	
	return $objResponse;
}

function eliminarPago($formDetallePago,$pos){
	$objResponse = new xajaxResponse();
	
	if ($formDetallePago['txtFormaPago'.$pos] == 3)
		$objResponse->script("xajax_eliminarDetalleDeposito(".$pos.",xajax.getFormValues('frmDetallePago'))");
		
	$objResponse->script(sprintf("
				fila = document.getElementById('trItm:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$pos));
			
	$objResponse->script("xajax_actualizarObjetosExistentes(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListadoPagos'))");
	
	return $objResponse;
}

function eliminarPagoDetalleDeposito($formDetalleDeposito,$pos){
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("
				fila = document.getElementById('trItmDetalle:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$pos));
			
	$objResponse->script("xajax_actualizarObjetosExistentesDetalleDeposito(xajax.getFormValues('frmDetalleDeposito'))");
	
	return $objResponse;
}

function eliminarPagoDetalleDepositoForzado($valForm){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valForm['hddObjDetallePagoDeposito']); $cont++) {
		$caracter = substr($valForm['hddObjDetallePagoDeposito'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valForm['txtFormaPagoDetalleDeposito'.$valor])){
			$objResponse->script(sprintf("
				fila = document.getElementById('trItmDetalle:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
					$valor));
		}
	}
	$objResponse->assign("hddObjDetallePagoDeposito","value","");
	
	return $objResponse;
}

function guardarPago($frmDetallePago, $frmListadoPagos, $frmDcto){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cjrs_facturas_por_pagar_list","insertar")) { errorCargarPago($objResponse); return $objResponse; }
	
	$objResponse->loadCommands(validarAperturaCaja());
	
	mysql_query("START TRANSACTION;");	
	
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
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		$andEmpresa = sprintf(" AND sa_iv_apertura.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresa2 = sprintf(" AND id_empresa = %s",
			valTpDato($idEmpresa,"int"));			
			
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$idEmpresa = $frmDcto['txtIdEmpresa'];
		$andEmpresa = '';
		$andEmpresa2 = '';
	}
	
	$idModulo = $frmDcto['hddIdModulo'];
	
	//CONSULTA FECHA DE APRTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
	$sqlFechaAperturaCaja = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura WHERE idCaja = %s AND statusAperturaCaja IN(1,2) %s",
		valTpDato(2,"int"),
		$andEmpresa);
	$rsFechaAperturaCaja = mysql_query($sqlFechaAperturaCaja);
	if (!$rsFechaAperturaCaja) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlFechaAperturaCaja); }
	if($rowFechaAperturaCaja = mysql_fetch_array($rsFechaAperturaCaja)){
		$fechaRegistroPago = $rowFechaAperturaCaja["fechaAperturaCaja"];
	}
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
	WHERE id_numeracion = %s
		AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																		WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC
	LIMIT 1;",
		valTpDato(44, "int"), // 5 = Recibos de Pago
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$numeroActual = $rowNumeracion['numero_actual'];
	
	// INSERTA EL RECIBO DE PAGO
	$sqlInsertEncabezadoReciboPago = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
	VALUES (%s, %s, %s, %s, %s, %s, %s)",
		valTpDato($numeroActual,"int"),
		valTpDato($fechaRegistroPago,"date"),
		valTpDato(1,"int"),
		valTpDato(0,"int"),		
		valTpDato($_GET['id_factura'],"int"),
		valTpDato($idModulo,"int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	$rsInsertEncabezadoReciboPago = mysql_query($sqlInsertEncabezadoReciboPago);
	if (!$rsInsertEncabezadoReciboPago) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEncabezadoReciboPago); }
	$idEncabezadoReciboPago = mysql_insert_id();
		
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (Recibos de Pago)
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	//FUNCION AGREGADA EL 26/06/2014
	// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
	$sqlInsertEncabezadoPago = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
	VALUES (%s, %s)",
		valTpDato($_GET['id_factura'],"int"),
		valTpDato($fechaRegistroPago,"date"));
	$rsInsertEncabezadoPago = mysql_query($sqlInsertEncabezadoPago);
	if (!$rsInsertEncabezadoPago) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEncabezadoPago); }
	$idEncabezadoPago = mysql_insert_id();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($frmDetallePago['hddObjDetallePago']); $cont++) {
		$caracter = substr($frmDetallePago['hddObjDetallePago'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($frmListadoPagos['txtFormaPago'.$valor])){
			if ($frmListadoPagos['txtFormaPago'.$valor] == 1){//EFECTIVO
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuenta = "-";
				$numeroDocumento = "-";
				$tipoCheque = "-";
				$campo = "saldoEfectivo";
				$txtMonto = $frmListadoPagos['txtMonto'.$valor];
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 2){//CHEQUE
				$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valor];
				$bancoCompania = 1;
				$numeroCuenta = $frmListadoPagos['txtNumeroCuenta'.$valor];
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "0";
				$campo = "saldoCheques";
				$txtMonto = $frmListadoPagos['txtMonto'.$valor];
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 3){//DEPOSITO
				$bancoCliente = '1';
				$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valor];
				$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoDepositos";
				$txtMonto = $frmListadoPagos['txtMonto'.$valor];
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 4){//TRANSFERENCIA BANCARIA
				$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valor];
				$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valor];
				$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoTransferencia";
				$txtMonto = $frmListadoPagos['txtMonto'.$valor];
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 5){//TARJETA DE CREDITO
				$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valor];
				$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valor];
				$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoTarjetaCredito";
				$txtMonto = $frmListadoPagos['txtMonto'.$valor];
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 6){//TARJETA DE DEBITO
				$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valor];
				$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valor];
				$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoTarjetaDebito";
				$txtMonto = $frmListadoPagos['txtMonto'.$valor];
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 7){//ANTICIPO
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuenta = "-";
				$numeroDocumento = $frmListadoPagos['txtIdDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoAnticipo";
				$txtMonto = 0;
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 8){//NOTA CREDITO
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuenta = "-";
				$numeroDocumento = $frmListadoPagos['txtIdDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoNotaCredito";
				$txtMonto = $frmListadoPagos['txtMonto'.$valor];
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 9){//RETENCION
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuenta = "-";
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoRetencion";
				$txtMonto = $frmListadoPagos['txtMonto'.$valor];
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 10){//RETENCION ISLR
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuenta = "-";
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoRetencion";
				$txtMonto = $frmListadoPagos['txtMonto'.$valor];
			}
			
			$sqlSelectDatosAperturaCaja = sprintf("SELECT saldoCaja, id, %s
			FROM sa_iv_apertura
			WHERE idCaja = 2
				AND statusAperturaCaja IN (1,2)
				%s",
				$campo,
				$andEmpresa2);
			$rsSelectDatosAperturaCaja = mysql_query($sqlSelectDatosAperturaCaja);
			if (!$rsSelectDatosAperturaCaja) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectDatosAperturaCaja); }
			$rowSelectDatosAperturaCaja = mysql_fetch_array($rsSelectDatosAperturaCaja);
			
			// NO SUMA ANTICIPOS(7) EN EL SALDO DE LA CAJA, YA QUE ESTE INGRESO SE SUMA EN LAS DEMAS FORMAS DE PAGO (EF, CH, DP, TB, TC, TD)
			$sqlUpdateDatosAperturaCaja = sprintf("UPDATE sa_iv_apertura SET
				%s = %s,
				saldoCaja = %s
			WHERE id = %s",
				$campo,
				valTpDato($rowSelectDatosAperturaCaja[$campo] + $frmListadoPagos['txtMonto'.$valor],"double"),
				valTpDato($rowSelectDatosAperturaCaja['saldoCaja'] + $txtMonto,"double"),
				valTpDato($rowSelectDatosAperturaCaja['id'],"int"));
			$rsUpdateDatosAperturaCaja = mysql_query($sqlUpdateDatosAperturaCaja);
			if (!$rsUpdateDatosAperturaCaja) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateDatosAperturaCaja); }
			
			// INSERTA LOS PAGOS DEL DOCUMENTO
			$sqlInsertPago = sprintf("INSERT INTO sa_iv_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, id_encabezado_rs)
			VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($_GET['id_factura'],"int"),
				valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)),"date"),
				valTpDato($frmListadoPagos['txtFormaPago'.$valor],"int"),
				valTpDato($numeroDocumento,"text"),
				valTpDato($bancoCliente,"int"),
				valTpDato($bancoCompania,"int"),
				valTpDato($numeroCuenta,"text"),
				valTpDato($frmListadoPagos['txtMonto'.$valor],"double"),
				valTpDato($frmDcto['txtNumeroFactura'],"text"),
				valTpDato($tipoCheque,"text"),
				valTpDato(1,"int"),
				valTpDato(0,"int"),
				valTpDato(2,"int"),
				valTpDato(0,"int"),
				valTpDato($idEncabezadoPago,"int"));
			$rsInsertPago = mysql_query($sqlInsertPago);
			if (!$rsInsertPago) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertPago); }
			$idPago = mysql_insert_id();
			
			$arrayDetIdDctoContabilidad[0] = $idPago;
			$arrayDetIdDctoContabilidad[1] = $_GET['id_factura'];
			$arrayIdDctoContabilidad[] = $arrayDetIdDctoContabilidad;
			
			if ($frmListadoPagos['txtFormaPago'.$valor] == 3){ //DEPOSITO
				$arrayPosiciones = explode("|",$frmDetallePago['hddObjDetalleDeposito']);
				$arrayFormaPago = explode("|",$frmDetallePago['hddObjDetalleDepositoFormaPago']);
				$arrayBanco = explode("|",$frmDetallePago['hddObjDetalleDepositoBanco']);
				$arrayNroCuenta = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCuenta']);
				$arrayNroCheque = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCheque']);
				$arrayMonto = explode("|",$frmDetallePago['hddObjDetalleDepositoMonto']);
				
				foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
					if ($valorDeposito == $valor){
						if ($arrayFormaPago[$indiceDeposito] == 1){
							$bancoDetalleDeposito = "";
							$nroCuentaDetalleDeposito = "";
							$nroChequeDetalleDeposito = "";
						} else {
							$bancoDetalleDeposito = $arrayBanco[$indiceDeposito];
							$nroCuentaDetalleDeposito = $arrayNroCuenta[$indiceDeposito];
							$nroChequeDetalleDeposito = $arrayNroCheque[$indiceDeposito];
						}
						
						$sqlInsertDetalleDeposito = sprintf("INSERT INTO an_det_pagos_deposito_factura (idPago, fecha_deposito, idFormaPago, idBanco, numero_cuenta, numero_cheque, monto, id_tipo_documento, idCaja)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
							valTpDato($idPago,"int"),
							valTpDato(date("Y-m-d",strtotime($frmListadoPagos['txtFechaDeposito'.$valor])),"date"),
							valTpDato($arrayFormaPago[$indiceDeposito],"int"),
							valTpDato($bancoDetalleDeposito,"int"),
							valTpDato($nroCuentaDetalleDeposito,"text"),
							valTpDato($nroChequeDetalleDeposito,"text"),
							valTpDato($arrayMonto[$indiceDeposito],"double"),
							valTpDato(1,"int"),
							valTpDato(2,"int")); // 1 = CAJA VEHICULOS ; 2 = CAJA REPUESTOS Y SERVICIOS
						$rsInsertDetalleDeposito = mysql_query($sqlInsertDetalleDeposito);
						if (!$rsInsertDetalleDeposito) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDetalleDeposito); }
					}
				}
			} else if($frmListadoPagos['txtFormaPago'.$valor] == 5 || $frmListadoPagos['txtFormaPago'.$valor] == 6){ //T. CREDITO Y DEBITO
				$sqlSelectRetencionPunto = sprintf("SELECT id_retencion_punto FROM te_retencion_punto
				WHERE id_cuenta = %s
					AND id_tipo_tarjeta = %s",
					valTpDato($frmListadoPagos['txtNumeroCuenta'.$valor],"int"),
					valTpDato($frmListadoPagos['txtTipoTarjeta'.$valor],"int"));
				$rsSelectRetencionPunto = mysql_query($sqlSelectRetencionPunto);
				if (!$rsSelectRetencionPunto) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectRetencionPunto); }
				$rowSelectRetencionPunto = mysql_fetch_array($rsSelectRetencionPunto);
				
				$sqlInsertRetencionPuntoPago = sprintf("INSERT INTO cj_cc_retencion_punto_pago (id_caja, id_pago, id_tipo_documento, id_retencion_punto)
				VALUES (%s, %s, %s, %s)",
					valTpDato(2,"int"),
					valTpDato($idPago,"int"),
					valTpDato(1,"int"),
					valTpDato($rowSelectRetencionPunto['id_retencion_punto'],"int"));
				$rsInsertRetencionPuntoPago = mysql_query($sqlInsertRetencionPuntoPago);
				if (!$rsInsertRetencionPuntoPago) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertRetencionPuntoPago); }
				
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 7){ //ANTICIPO
				$sqlSelectAnticipo = sprintf("SELECT * FROM cj_cc_anticipo WHERE idAnticipo = %s",
					$frmListadoPagos['txtIdDocumento'.$valor]);
				$rsSelectAnticipo = mysql_query($sqlSelectAnticipo);
				if (!$rsSelectAnticipo) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectAnticipo); }
				$rowSelectAnticipo = mysql_fetch_array($rsSelectAnticipo);
				
				$totalAnticipo = $rowSelectAnticipo['saldoAnticipo'] - $frmListadoPagos['txtMonto'.$valor];
				$estatusAnticipo = ($totalAnticipo == 0) ? 3 : 2;
				
				$sqlUpdateAnticipo = sprintf("UPDATE cj_cc_anticipo SET
					saldoAnticipo = %s,
					estadoAnticipo = %s
				WHERE idAnticipo = %s",
					valTpDato($totalAnticipo,"double"),
					valTpDato($estatusAnticipo,"int"),
					$frmListadoPagos['txtIdDocumento'.$valor]);
				$rsUpdateAnticipo = mysql_query($sqlUpdateAnticipo);
				if (!$rsUpdateAnticipo) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateAnticipo); }
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 8) { //NOTA CREDITO
				$sqlSelectNotaCredito = sprintf("SELECT * FROM cj_cc_notacredito WHERE idNotaCredito = %s",
					$frmListadoPagos['txtIdDocumento'.$valor]);
				$rsSelectNotaCredito = mysql_query($sqlSelectNotaCredito);
				if (!$rsSelectNotaCredito) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectNotaCredito); }
				$rowSelectNotaCredito = mysql_fetch_array($rsSelectNotaCredito);
				
				$totalNotaCredito = $rowSelectNotaCredito['saldoNotaCredito'] - $frmListadoPagos['txtMonto'.$valor];
				$estatusNotaCredito = ($totalNotaCredito == 0) ? 3 : 2;
				
				$sqlUpdateNotaCredito = sprintf("UPDATE cj_cc_notacredito SET
					saldoNotaCredito = %s,
					estadoNotaCredito = %s
				WHERE idNotaCredito = %s",
					valTpDato($totalNotaCredito,"double"),
					valTpDato($estatusNotaCredito,"int"),
					$frmListadoPagos['txtIdDocumento'.$valor]);
				$rsUpdateNotaCredito = mysql_query($sqlUpdateNotaCredito);
				if (!$rsUpdateNotaCredito) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateNotaCredito); }
			} else if ($frmListadoPagos['txtFormaPago'.$valor] == 9){ //RETENCION
				$sqlSelectFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura
				WHERE idFactura = %s",
					valTpDato($_GET['id_factura'],"int"));
				$rsSelectFactura = mysql_query($sqlSelectFactura);
				if (!$rsSelectFactura) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFactura); }
				$rowSelectFactura = mysql_fetch_array($rsSelectFactura);
				
				$porcentajeAlicuota = $rowSelectFactura['porcentajeIvaFactura'] + $rowSelectFactura['porcentajeIvaDeLujoFactura'];
				$impuestoIva = $rowSelectFactura['calculoIvaFactura'] + $rowSelectFactura['calculoIvaDeLujoFactura'];
				$porcentajeRetenido = ($impuestoIva > 0) ? $frmListadoPagos['txtMonto'.$valor] * 100 / $impuestoIva : 0;
				
				$sqlInsertRetencionCabecera = sprintf("INSERT INTO cj_cc_retencioncabezera (numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idCliente, idRegistrosUnidadesFisicas)
				VALUES (%s, %s, %s, %s, %s, %s)",
					valTpDato($frmListadoPagos['txtNumeroDocumento'.$valor],"text"),
					valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)),"date"),
					valTpDato(date("Y",strtotime($fechaRegistroPago)),"text"),
					valTpDato(date("m",strtotime($fechaRegistroPago)),"text"),
					valTpDato($frmDcto['txtIdCliente'],"int"),
					valTpDato(0,"int"));
				$rsInsertRetencionCabecera = mysql_query($sqlInsertRetencionCabecera);
				if (!$rsInsertRetencionCabecera) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertRetencionCabecera); }
				$idRetencionCabecera = mysql_insert_id();
				
				$sqlInsertRetencionDetalle = sprintf("INSERT INTO cj_cc_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
				VALUES (%s, %s, %s, %s, '', '', '', '', %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idRetencionCabecera,"int"),
					valTpDato($rowSelectFactura['fechaRegistroFactura'],"date"),
					valTpDato($rowSelectFactura['idFactura'],"int"),
					valTpDato($rowSelectFactura['numeroControl'],"text"),
					valTpDato($rowSelectFactura['montoTotalFactura'],"double"),
					valTpDato($rowSelectFactura['subtotalFactura'],"double"),
					valTpDato($rowSelectFactura['baseImponible'],"double"),
					valTpDato($porcentajeAlicuota,"double"),
					valTpDato($impuestoIva,"double"),
					valTpDato($frmListadoPagos['txtMonto'.$valor],"double"),
					valTpDato($porcentajeRetenido,"int"));
				$rsInsertRetencionDetalle = mysql_query($sqlInsertRetencionDetalle);
				if (!$rsInsertRetencionDetalle) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertRetencionDetalle); }
			}
			
			$sqlInsertDetalleReciboPago = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
			VALUES (%s, %s)",
				valTpDato($idEncabezadoReciboPago,"int"),
				valTpDato($idPago,"int"));
			$rsInsertDetalleReciboPago = mysql_query($sqlInsertDetalleReciboPago);
			if (!$rsInsertDetalleReciboPago) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDetalleReciboPago); }
		}
	}
	
	//ACTUALIZA SALDOS Y ESTATUS DEL DOCUMENTO
	//0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
	if ($frmListadoPagos['txtMontoPorPagar'] == 0) {
		$sqlUpdateFactura = sprintf("UPDATE cj_cc_encabezadofactura SET
			saldoFactura = 0,
			estadoFactura = 1
		WHERE idFactura = %s;",
			valTpDato($_GET['id_factura'],"int"));
	} else {
		$montoPorPagar = str_replace(",","",$frmListadoPagos['txtMontoPorPagar']);
		$sqlUpdateFactura = sprintf("UPDATE cj_cc_encabezadofactura SET
			saldoFactura = %s,
			estadoFactura = 2
		WHERE idFactura = %s;",
			valTpDato($montoPorPagar,"double"),
			valTpDato($_GET['id_factura'],"int"));
	}
	
	$rsUpdateFactura = mysql_query($sqlUpdateFactura);
	if (!$rsUpdateFactura) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateFactura);	}
			
	// ACTUALIZA EL CREDITO DISPONIBLE
	$queryFactura = sprintf("SELECT idCliente, id_empresa FROM cj_cc_encabezadofactura
	WHERE idFactura = %s",
		valTpDato($_GET['id_factura'],"int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryFactura); }
	$rowFactura = mysql_fetch_array($rsFactura);
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
		creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
													WHERE fact_vent.idCliente = cliente_emp.id_cliente
														AND fact_vent.id_empresa = cliente_emp.id_empresa
														AND fact_vent.estadoFactura IN (0,2)), 0)
											+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
													WHERE nota_cargo.idCliente = cliente_emp.id_cliente
														AND nota_cargo.id_empresa = cliente_emp.id_empresa
														AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
											- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
													WHERE cxc_ant.idCliente = cliente_emp.id_cliente
														AND cxc_ant.id_empresa = cliente_emp.id_empresa
														AND cxc_ant.estadoAnticipo IN (1,2)
														AND cxc_ant.estatus = 1), 0)
											- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
													WHERE nota_cred.idCliente = cliente_emp.id_cliente
														AND nota_cred.id_empresa = cliente_emp.id_empresa
														AND nota_cred.estadoNotaCredito IN (1,2)), 0)
											+ IFNULL((SELECT
														SUM(IFNULL(ped_vent.subtotal, 0)
															- IFNULL(ped_vent.subtotal_descuento, 0)
															+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																	WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
															+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																	WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
													FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_cliente = cliente_emp.id_cliente
														AND ped_vent.id_empresa = cliente_emp.id_empresa
														AND ped_vent.estatus_pedido_venta IN (2)), 0)),
		creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
									WHERE fact_vent.idCliente = cliente_emp.id_cliente
										AND fact_vent.id_empresa = cliente_emp.id_empresa
										AND fact_vent.estadoFactura IN (0,2)), 0)
							+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
									WHERE nota_cargo.idCliente = cliente_emp.id_cliente
										AND nota_cargo.id_empresa = cliente_emp.id_empresa
										AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
							- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
									WHERE cxc_ant.idCliente = cliente_emp.id_cliente
										AND cxc_ant.id_empresa = cliente_emp.id_empresa
										AND cxc_ant.estadoAnticipo IN (1,2)
										AND cxc_ant.estatus = 1), 0)
							- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
									WHERE nota_cred.idCliente = cliente_emp.id_cliente
										AND nota_cred.id_empresa = cliente_emp.id_empresa
										AND nota_cred.estadoNotaCredito IN (1,2)), 0)
							+ IFNULL((SELECT
										SUM(IFNULL(ped_vent.subtotal, 0)
											- IFNULL(ped_vent.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
													WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
											+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
													WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
									FROM iv_pedido_venta ped_vent
									WHERE ped_vent.id_cliente = cliente_emp.id_cliente
										AND ped_vent.id_empresa = cliente_emp.id_empresa
										AND ped_vent.estatus_pedido_venta IN (2)
										AND id_empleado_aprobador IS NOT NULL), 0))
	WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
		AND cliente_emp.id_cliente = %s
		AND cliente_emp.id_empresa = %s;",
		valTpDato($rowFactura['idCliente'], "int"),
		valTpDato($rowFactura['id_empresa'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	errorCargarPago($objResponse);
	$objResponse->alert("Pago cargado correctamente");
	
	$objResponse->script(sprintf("window.location.href='cjrs_facturas_por_pagar_list.php';"));
	
	$objResponse->script(sprintf("verVentana('reportes/cjrs_comprobante_pago_factura_pdf.php?valBusq=%s|%s|%s',960,550)", $idEmpresa, $_GET['id_factura'],$numeroActual));
	
	return $objResponse;
}

function listadoAnticipoNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idCliente = %s
	AND (id_empresa = %s
		OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
			WHERE suc.id_empresa_padre = id_empresa)
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = id_empresa)
		OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = id_empresa))",
		valTpDato($valCadBusq[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if($valCadBusq[2] == 7) {
		$sqlBusq .= $cond.sprintf("estadoAnticipo IN (1,2)");
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = 1"); // 0 = Anulado ; 1 = Activo
	} else {
		$sqlBusq .= $cond.sprintf("estadoNotaCredito IN (1,2)");
	}
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if($valCadBusq[2] == 7)
			$sqlBusq .= $cond.sprintf(" numeroAnticipo = %s ",
				valTpDato($valCadBusq[2], "int"));
		else
			$sqlBusq .= $cond.sprintf(" numeracion_nota_credito = %s ",
				valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if($valCadBusq[2] == 7)
			$sqlBusq .= $cond.sprintf(" idAnticipo NOT IN (%s) ",
			$valCadBusq[3]);
		else
			$sqlBusq .= $cond.sprintf(" idNotaCredito NOT IN (%s) ",
			$valCadBusq[3]);
	}
	
	if($valCadBusq[2] == 7)
		$query = sprintf("SELECT
			idAnticipo AS idDocumento,
			saldoAnticipo AS saldoDocumento,
			numeroAnticipo AS numeroDocumento,
			fechaAnticipo AS fechaDocumento,
			observacionesAnticipo AS observacionDocumento
		FROM
			cj_cc_anticipo %s", $sqlBusq);
	else
		$query = sprintf("SELECT
			idNotaCredito AS idDocumento,
			saldoNotaCredito AS saldoDocumento,
			numeracion_nota_credito AS numeroDocumento,
			fechaNotaCredito AS fechaDocumento,
			observacionesNotaCredito AS observacionDocumento
		FROM
			cj_cc_notacredito %s", $sqlBusq);
								
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCredito", "20%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Documento"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCredito", "15%", $pageNum, "fechaDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCredito", "40%", $pageNum, "observacionDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Observaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCredito", "25%", $pageNum, "saldoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo"));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_cargarSaldoDocumento('".$row['idDocumento']."','".$valCadBusq[2]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroDocumento']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaDocumento']))."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['observacionDocumento'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoDocumento'],2,'.',',')."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf.="<option value=\"".$nroPag."\"";
								if ($pageNum == $nroPag) {
									$htmlTf.="selected=\"selected\"";
								}
								$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("
	$('trBuscarAnticipoNotaCredito').style.display = '';
	
	$('tblDetallePago').style.display = 'none';
	$('tblListados').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Listado");
	$objResponse->assign("tblListados","width","700");
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display = '';
		centrarDiv($('divFlotante'));
		
		document.forms['frmBuscarAnticipoNotaCredito'].reset();
		$('txtCriterioBusqAnticipoNotaCredito').focus();
		$('txtCriterioBusqAnticipoNotaCredito').select();
	}");
	
	return $objResponse;
}

function validarAperturaCaja(){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$fecha = date("Y-m-d");
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura WHERE statusAperturaCaja <> 0 AND fechaAperturaCaja NOT LIKE %s AND id_empresa = %s",
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCierreCaja);
	
	if (mysql_num_rows($rsCierreCaja) > 0){
		$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
		$fechaUltimaApertura = date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja']));
		$objResponse->alert("Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
		$objResponse->script("location.href='cjrs_facturas_por_pagar_list.php'");
		
	} else {
		//VERIFICA SI LA CAJA TIENE APERTURA
		//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
		$queryVerificarApertura = sprintf("SELECT * FROM sa_iv_apertura WHERE fechaAperturaCaja = %s AND statusAperturaCaja <> 0 AND id_empresa = %s",
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL:".$queryVerificarApertura);
		
		if (mysql_num_rows($rsVerificarApertura) == 0){
			$objResponse->alert("Esta caja no tiene apertura.");
			$objResponse->script("location.href='cjrs_facturas_por_pagar_list.php'");
		}
	}
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentes");
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentesDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarFecha");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCredito");
$xajax->register(XAJAX_FUNCTION,"cargarFactura");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargarCuentasCompania");
$xajax->register(XAJAX_FUNCTION,"cargarPago");
$xajax->register(XAJAX_FUNCTION,"cargarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"cargarTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargarTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargarTipoPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDepositoForzado");
$xajax->register(XAJAX_FUNCTION,"guardarPago");
$xajax->register(XAJAX_FUNCTION,"listadoAnticipoNotaCredito");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");

function errorCargarPago($objResponse){
	$objResponse->script("
	byId('agregar').disabled = false;
	byId('btnGuardarPago').disabled = false;
	byId('btnCancelar').disabled = false;");
}

function nombreBanco($idBanco){
	$query = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s",$idBanco);
	$rsQuery = mysql_query($query) or die(mysql_error());
	$rowQuery = mysql_fetch_array($rsQuery);
	
	return $rowQuery['nombreBanco'];
}

function numeroCuenta($idCuenta){
	$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",$idCuenta);
	$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta) or die(mysql_error());
	$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	
	return $rowBuscarNumeroCuenta['numeroCuentaCompania'];
}
?>