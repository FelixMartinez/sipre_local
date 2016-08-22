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

function asignarFecha(){
	$objResponse = new xajaxResponse();
	
	$fecha = date("d-m-Y");
	
	$objResponse->assign("txtFecha","value",$fecha);
	
	return $objResponse;
}

function buscarCliente($valForm, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$valForm['txtCriterioBusqCliente']);
	
	$objResponse->loadCommands(listadoClientes(0,"id","ASC",$valBusq));
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (0,1,3)");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargarBancoCliente($idTd, $idSelect){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT idBanco, nombreBanco FROM bancos WHERE idBanco <> 1 ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<select name='%s' id='%s'>",$idSelect,$idSelect);
		$html .= sprintf("<option value = ''>[ Seleccione ]");
		while ($row = mysql_fetch_array($rs)){
			$html .= sprintf("<option value = '%s'>%s",$row["idBanco"],utf8_encode($row["nombreBanco"]));
		}
	$html .= "</select>";
	
	mysql_query("COMMIT");
	
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function cargarBancoCompania($tipoPago = ""){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT idBanco, (SELECT nombreBanco FROM bancos WHERE bancos.idBanco = cuentas.idBanco) AS banco FROM cuentas GROUP BY cuentas.idBanco ORDER BY banco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<select name='selBancoCompania' id='selBancoCompania' onchange='xajax_cargarCuentasCompania(this.value,".$tipoPago.");' >");
		$html .= sprintf("<option value = ''>[ Seleccione ]");
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
		$html .= sprintf("<option value = ''>[ Seleccione ]");
		
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
	
//	if (str_replace(",","",$formDetallePago['hddMontoFaltaPorPagar']) < str_replace(",","",$formDetallePago['montoPago']))
	//	return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo del Anticipo");
	
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
		if (!$rsBuscarNumeroCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlBuscarNumeroCuenta);
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
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $formDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $formDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $formDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = 6;
	}
	else if($formDetallePago['selTipoPago'] == 11){
		$tipoPago = "Cash Back";
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
	
	$objResponse->script("document.forms['frmDetallePago'].reset();");
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
	if (!$rsQuery) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
	$rowQuery = mysql_fetch_array($rsQuery);
	
	$objResponse->assign("porcentajeRetencion","value",$rowQuery['porcentaje_islr']);
	$objResponse->assign("porcentajeComision","value",$rowQuery['porcentaje_comision']);
	
	$objResponse->script("calcularMontoTotalTarjetaCredito();");
	
	return $objResponse;
}

function cargarTarjetaCuenta($idCuenta,$tipoPago){
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 5) {
		$query = sprintf("SELECT idTipoTarjetaCredito, descripcionTipoTarjetaCredito
						FROM tipotarjetacredito 
						WHERE idTipoTarjetaCredito IN (SELECT id_tipo_tarjeta
														FROM te_retencion_punto
														WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta NOT IN(6))",$idCuenta);
		$rsQuery = mysql_query($query);
		if (!$rsQuery) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
		
		$html = "<select id='tarjeta' name='tarjeta' onchange='xajax_cargarPorcentajeTarjetaCredito(".$idCuenta.",this.value)'>
				 	<option value=''>[ Seleccione ]</option>";
		
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
	
	$query = sprintf("SELECT * FROM formapagos where idFormaPago <= 6 OR idFormaPago = 11");
	$rs = mysql_query($query);
	
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<div align='justify'>
						<select name='selTipoPago' id='selTipoPago' onChange='cambiar()'>
							<option value=''>[ Seleccione ]</option>");
	
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
							<option value=''>[ Seleccione ]</option>");
	
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

function guardarAnticipo($frmDetallePago,$frmListadoPagos,$frmDcto){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cjrs_anticipo_list","insertar")) { errorCargarPago($objResponse); return $objResponse; }
	
	$objResponse->loadCommands(validarAperturaCaja());
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$montoAnticipo = $frmDcto['txtMontoAnticipo'];

	if ($frmListadoPagos['txtMontoPagadoAnticipo'] <> $frmDcto['txtMontoAnticipo']) {
		 { errorCargarPago($objResponse); return $objResponse->alert('El Monto a pagar no coincide con el Monto Total del Anticipo');}
	} 
	
	// NUMERACION DEL DOCUMENTO (ANTICIPO)
	$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
	WHERE id_numeracion = %s
		AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																		WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC
	LIMIT 1;",
		valTpDato(42, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$numeroActualAnticipo = $rowNumeracion['numero_actual'];
	
	$sqlInsertAnticipo = sprintf("INSERT INTO cj_cc_anticipo (idCliente, montoNetoAnticipo, saldoAnticipo, totalPagadoAnticipo, fechaAnticipo, observacionesAnticipo, estadoAnticipo, numeroAnticipo, idDepartamento, id_empresa)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($idCliente,"int"),
		valTpDato($montoAnticipo, "real_inglesa"),
		valTpDato($montoAnticipo, "real_inglesa"),
		valTpDato($montoAnticipo, "real_inglesa"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFecha'])), "date"),
		valTpDato(utf8_encode($frmDcto['txtObservacionAnticipo']), "text"),
		valTpDato(1,"int"),//0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato($numeroActualAnticipo,"int"),
		valTpDato($frmDcto['lstModulo'],"int"),
		valTpDato($idEmpresa,"int"));
	$rsInsertAnticipo = mysql_query($sqlInsertAnticipo);
	if (!$rsInsertAnticipo) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertAnticipo);}
	$idAnticipo = mysql_insert_id();
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (ANTICIPO)
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	/*INSERT EN EL ESTADO DE CUENTA*/
	$insertEstadoCuenta = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUES ('AN', %s, NOW(), 3)",$idAnticipo);
	$rsEstadoCuenta = mysql_query($insertEstadoCuenta);
	if (!$rsEstadoCuenta) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertEstadoCuenta);}
	
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
				$formaPago = "EF";
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuentaCliente = "-";
				$numeroCuenta = "-";
				$numeroDocumento = "-";
				$campo = "saldoEfectivo";
			}
			else if ($frmListadoPagos['txtFormaPago'.$valor] == 2){//CHEQUE
				$formaPago = "CH";
				$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valor];
				$bancoCompania = 1;
				$numeroCuentaCliente = $frmListadoPagos['txtNumeroCuenta'.$valor];
				$numeroCuenta = $frmListadoPagos['txtNumeroCuenta'.$valor];
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$campo = "saldoCheques";
			}
			else if ($frmListadoPagos['txtFormaPago'.$valor] == 3){//DEPOSITO
				$formaPago = "DP";
				$bancoCliente = '1';
				$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valor];
				$numeroCuentaCliente = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$campo = "saldoDepositos";
			}
			else if ($frmListadoPagos['txtFormaPago'.$valor] == 4){//TRANSFERENCIA BANCARIA
				$formaPago = "TB";
				$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valor];
				$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valor];
				$numeroCuentaCliente = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$campo = "saldoTransferencia";
			}
			else if ($frmListadoPagos['txtFormaPago'.$valor] == 5){//TARJETA DE CREDITO
				$formaPago = "TC";
				$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valor];
				$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valor];
				$numeroCuentaCliente = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$campo = "saldoTarjetaCredito";
			}
			else if ($frmListadoPagos['txtFormaPago'.$valor] == 6){//TARJETA DE DEBITO
				$formaPago = "TD";
				$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valor];
				$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valor];
				$numeroCuentaCliente = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valor];
				$campo = "saldoTarjetaDebito";
			}
			else if ($frmListadoPagos['txtFormaPago'.$valor] == 11){//CASH BACK
				$formaPago = "CB";
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuentaCliente = "-";
				$numeroCuenta = "-";
				$numeroDocumento = "-";
				$campo = "saldoCashBack";
			}
			
			$sqlSelectDatosAperturaCaja = sprintf("SELECT saldoCaja, id, %s FROM sa_iv_apertura
			WHERE idCaja = 2
				AND statusAperturaCaja IN (1,2)
				AND id_empresa = %s",
				$campo,
				$idEmpresa);
			$rsSelectDatosAperturaCaja = mysql_query($sqlSelectDatosAperturaCaja);
			if (!$rsSelectDatosAperturaCaja)  { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectDatosAperturaCaja);}
			$rowSelectDatosAperturaCaja = mysql_fetch_array($rsSelectDatosAperturaCaja);
			
			$sqlUpdateDatosAperturaCaja = sprintf("UPDATE sa_iv_apertura SET %s = %s, saldoCaja = %s WHERE id = %s",
				$campo,
				valTpDato($rowSelectDatosAperturaCaja[$campo] + $frmListadoPagos['txtMonto'.$valor],"double"),
				valTpDato($rowSelectDatosAperturaCaja['saldoCaja'] + $frmListadoPagos['txtMonto'.$valor],"double"),
				valTpDato($rowSelectDatosAperturaCaja['id'],"int"));
			$rsUpdateDatosAperturaCaja = mysql_query($sqlUpdateDatosAperturaCaja);
			if (!$rsUpdateDatosAperturaCaja)  { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateDatosAperturaCaja);}
			
			$sqlInsertDetalleAnticipo = sprintf("INSERT INTO cj_cc_detalleanticipo (tipoPagoDetalleAnticipo, bancoClienteDetalleAnticipo, bancoCompaniaDetalleAnticipo, numeroCuentaCliente, numeroCuentaCompania, numeroControlDetalleAnticipo, montoDetalleAnticipo, idAnticipo, fechaPagoAnticipo, tomadoEnCierre, idCaja, idCierre)
			VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, 0, 2, 0)",
				valTpDato($formaPago,"text"),
				valTpDato($bancoCliente,"int"),
				valTpDato($bancoCompania,"int"),
				valTpDato($numeroCuentaCliente,"int"),
				valTpDato($numeroCuenta,"text"),
				valTpDato($numeroDocumento,"text"),
				valTpDato($frmListadoPagos['txtMonto'.$valor],"double"),
				valTpDato($idAnticipo,"int"),
				valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])),"date"));
			$rsInsertDetalleAnticipo = mysql_query($sqlInsertDetalleAnticipo);
			if (!$rsInsertDetalleAnticipo)  { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDetalleAnticipo);}
			$idDetalleAnticipo = mysql_insert_id();
			
			if ($frmListadoPagos['txtFormaPago'.$valor] == 3){
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
						}
						else{
							$bancoDetalleDeposito = $arrayBanco[$indiceDeposito];
							$nroCuentaDetalleDeposito = $arrayNroCuenta[$indiceDeposito];
							$nroChequeDetalleDeposito = $arrayNroCheque[$indiceDeposito];
						}
						
						$sqlInsertDetalleDepositoAnticipo = sprintf("INSERT INTO cj_cc_det_pagos_deposito_anticipos (idDetalleAnticipo, fecha_deposito, idFormaPago, idBanco, numero_cuenta, numero_cheque, monto, id_tipo_documento, idCaja)
						VALUES (%s, %s, %s, %s, %s, %s, %s, 4, 2)",
							valTpDato($idDetalleAnticipo,"int"),
							valTpDato(date("Y-m-d",strtotime($frmListadoPagos['txtFechaDeposito'.$valor])),"date"),
							valTpDato($arrayFormaPago[$indiceDeposito],"int"),
							valTpDato($bancoDetalleDeposito,"int"),
							valTpDato($nroCuentaDetalleDeposito,"text"),
							valTpDato($nroChequeDetalleDeposito,"text"),
							valTpDato($arrayMonto[$indiceDeposito],"double"));
						$rsInsertDetalleDepositoAnticipo = mysql_query($sqlInsertDetalleDepositoAnticipo);
						if (!$rsInsertDetalleDepositoAnticipo)  { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDetalleDepositoAnticipo);}
					}
				}	
			}
			else if($frmListadoPagos['txtFormaPago'.$valor] == 5 || $frmListadoPagos['txtFormaPago'.$valor] == 6){
				$sqlSelectRetencionPunto = sprintf("SELECT id_retencion_punto FROM te_retencion_punto WHERE id_cuenta = %s AND id_tipo_tarjeta = %s",
					valTpDato($frmListadoPagos['txtNumeroCuenta'.$valor],"int"),
					valTpDato($frmListadoPagos['txtTipoTarjeta'.$valor],"int"));
				$rsSelectRetencionPunto = mysql_query($sqlSelectRetencionPunto);
				if (!$rsSelectRetencionPunto)  { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectRetencionPunto);}
				$rowSelectRetencionPunto = mysql_fetch_array($rsSelectRetencionPunto);
				
				$sqlInsertRetencionPuntoPago = sprintf("INSERT INTO cj_cc_retencion_punto_pago (id_caja, id_pago, id_tipo_documento, id_retencion_punto)
				VALUES (2, %s, 4, %s)",
					valTpDato($idDetalleAnticipo,"int"),
					valTpDato($rowSelectRetencionPunto['id_retencion_punto'],"int"));
				$rsInsertRetencionPuntoPago = mysql_query($sqlInsertRetencionPuntoPago);
				if (!$rsInsertRetencionPuntoPago)  { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertRetencionPuntoPago);}				
			}
		}
	}
	
	// NUMERACION DEL DOCUMENTO (Recibos de Pagos)
	$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
	WHERE id_numeracion = %s
		AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																		WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC
	LIMIT 1;",
		valTpDato(44, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$numeroActual = $rowNumeracion['numero_actual'];
	
	$sqlInsertReporteImpresion = sprintf("INSERT INTO pg_reportesimpresion (fechaDocumento, numeroReporteImpresion, tipoDocumento, idDocumento, idCliente, id_departamento, id_empleado_creador)
	VALUES('%s', %s, 'AN', %s, %s, 1, %s)",
		date("Y-m-d"),
		$numeroActual,
		$idAnticipo,
		$frmDcto['txtIdCliente'],
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	$rsInsertReporteImpresion = mysql_query($sqlInsertReporteImpresion);
	if (!$rsInsertReporteImpresion) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertReporteImpresion);}
	$idReporteImpresion = mysql_insert_id();
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (Recibos de Pagos)
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$objResponse->alert("Anticipo guardado correctamente");
		
	$objResponse->script(sprintf("window.location.href='cjrs_anticipo_list.php';"));
	
	$objResponse->script(sprintf("verVentana('reportes/cjrs_comprobante_pago_anticipo_pdf.php?valBusq=%s|%s|%s|%s',960,550)", $idEmpresa, $idAnticipo,$numeroActualAnticipo,$numeroActual));
	
	mysql_query("COMMIT;");
	
	// MODIFICADO ERNESTO
	if (function_exists("generarAnticiposRe")) { generarAnticiposRe($idAnticipo,"",""); } 
	// MODIFICADO ERNESTO
	
	return $objResponse;
}

function listadoClientes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CREDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_WS(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"6\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoClientes(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
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
	$('trBuscarCliente').style.display = '';
	
	$('tblDetallePago').style.display = 'none';
	$('tblListados').style.display = '';
	");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Listar");
	$objResponse->assign("tblListados","width","700");
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display = '';
		centrarDiv($('divFlotante'));
		
		document.forms['frmBuscarCliente'].reset();
		$('txtCriterioBusqCliente').focus();
		$('txtCriterioBusqCliente').select();
	}
	");
	
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
		$objResponse->script("location.href='cjrs_anticipo_list.php'");
		
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
			$objResponse->script("location.href='cjrs_anticipo_list.php'");
		}
	}
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentes");
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentesDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarFecha");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargarCuentasCompania");
$xajax->register(XAJAX_FUNCTION,"cargarPago");
$xajax->register(XAJAX_FUNCTION,"cargarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"cargarTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargarTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargarTipoPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDepositoForzado");
$xajax->register(XAJAX_FUNCTION,"guardarAnticipo");
$xajax->register(XAJAX_FUNCTION,"listadoClientes");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");

function errorCargarPago($objResponse){
	$objResponse->script("
	byId('btnGuardarPago').disabled = false;");
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