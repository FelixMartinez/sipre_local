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
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	
	$objResponse->script("$('divFlotante').style.display = 'none';");
	
	return $objResponse;
}

function buscarAnticipoNotaCreditoCheque($valForm, $idCliente, $tipoPago, $valFormObj, $valFormListado){
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
		
	$objResponse->loadCommands(listadoAnticipoNotaCreditoCheque(0,"","",$valBusq));
	
	return $objResponse;
}

function cargarDcto($idNotaCargo){
	$objResponse = new xajaxResponse();
	
	$sqlSelectNotaCargo = sprintf("SELECT * FROM cj_cc_notadecargo WHERE idNotaCargo = %s",$idNotaCargo);
	$rsSelectNotaCargo = mysql_query($sqlSelectNotaCargo);
	if (!$rsSelectNotaCargo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectNotaCargo);
	$rowSelectNotaCargo = mysql_fetch_array($rsSelectNotaCargo);
	
	$sqlSelectModulo = sprintf("SELECT descripcionModulo FROM pg_modulos WHERE id_enlace_concepto = %s",$rowSelectNotaCargo['idDepartamentoOrigenNotaCargo']);
	$rsSelectModulo = mysql_query($sqlSelectModulo);
	if (!$rsSelectModulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectModulo);
	$rowSelectModulo = mysql_fetch_array($rsSelectModulo);
	
	$objResponse->loadCommands(asignarCliente($rowSelectNotaCargo['idCliente']));
	$objResponse->assign("hddIdNotaCargo","value",$idNotaCargo);
	$objResponse->assign("txtNumeroNotaCargo","value",$rowSelectNotaCargo['numeroNotaCargo']);
	$objResponse->assign("txtNumeroControlNotaCargo","value",$rowSelectNotaCargo['numeroControlNotaCargo']);
	$objResponse->assign("txtFecha","value",date("d-m-Y",strtotime($rowSelectNotaCargo['fechaRegistroNotaCargo'])));
	$objResponse->assign("txtFechaVencimiento","value",date("d-m-Y",strtotime($rowSelectNotaCargo['fechaVencimientoNotaCargo'])));
	$objResponse->assign("txtSaldoNotaCargo","value",$rowSelectNotaCargo['saldoNotaCargo']);
	$objResponse->assign("txtMontoPorPagar","value",$rowSelectNotaCargo['saldoNotaCargo']);
	$objResponse->assign("hddMontoPorPagar","value",$rowSelectNotaCargo['saldoNotaCargo']);
	$objResponse->assign("hddMontoFaltaPorPagar","value",$rowSelectNotaCargo['saldoNotaCargo']);
	$objResponse->loadCommands(cargaLstModulo($rowSelectNotaCargo['idDepartamentoOrigenNotaCargo'],"",true));
	
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
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$objResponse->loadCommands(asignarEmpresaUsuario($rowSelectNotaCargo['id_empresa'], "Empresa", "ListaEmpresa"));
		$idEmpresa = $rowSelectNotaCargo['id_empresa'];
	}
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
	
	
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
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas WHERE idBanco = %s AND estatus = 1",
						valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<div align='justify'><strong>
						<select name='selNumeroCuenta' id='selNumeroCuenta' onchange='xajax_cargarTarjetaCuenta(this.value,".$tipoPago.");'>");
	$registros = mysql_num_rows($rs);
	if ($registros > 1){
		$html .= sprintf("<option value = ''>Seleccione");
	}
		
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value = '%s'>%s",$row["idCuentas"],utf8_encode($row["numeroCuentaCompania"]));
		if ($registros == 1){
			$objResponse->loadCommands(cargarTarjetaCuenta($row["idCuentas"],$tipoPago));
		}
	}
	$html .= "</select></strong></div>";
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdNumeroCuentaSelect","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s)", valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargarPago($formDetallePago, $formDetalleDeposito){
	$objResponse = new xajaxResponse();
	
	if (str_replace(",","",$formDetallePago['hddMontoFaltaPorPagar']) < str_replace(",","",$formDetallePago['montoPago'])){
		errorCargarPago($objResponse);
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la Nota de Débito");
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
		$arrayInformacionCheque = informacionCheque($formDetallePago['hddIdAnticipoNotaCreditoCheque']);
		$tipoPago = "Cheque";
		$tipoTarjetaCredito = "-";
		$bancoCliente = $arrayInformacionCheque["nombre_banco_cliente"];
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $arrayInformacionCheque["numero_cuenta_cliente"];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = $formDetallePago['hddIdAnticipoNotaCreditoCheque'];
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $arrayInformacionCheque["id_banco_cliente"];
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = $arrayInformacionCheque["numero_cuenta_cliente"];
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
		$idDocumento = $formDetallePago['hddIdAnticipoNotaCreditoCheque'];
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
		$idDocumento = $formDetallePago['hddIdAnticipoNotaCreditoCheque'];
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
							$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';");
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
	
	$objResponse->script("$('montoPago').addClass('inputHabilitado');");	
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
	
	if ($formaPago == 7) {//ANTICIPOS
		$query = sprintf("SELECT saldoAnticipo AS saldoDocumento, numeroAnticipo AS numeroDocumento
		FROM cj_cc_anticipo WHERE idAnticipo = %s", $idDocumento);
		$documento = "Anticipo";
	} elseif ($formaPago == 8){//NOTAS DE CREDITO
		$query = sprintf("SELECT saldoNotaCredito AS saldoDocumento, numeracion_nota_credito AS numeroDocumento
		FROM cj_cc_notacredito WHERE idNotaCredito = %s", $idDocumento);
		$documento = "Nota de Credito";
	} elseif ($formaPago == 2){//CHEQUES
		$query = sprintf("SELECT saldo_cheque AS saldoDocumento, numero_cheque AS numeroDocumento
		FROM cj_cc_cheque WHERE id_cheque = %s", $idDocumento);
		$documento = "Cheque";
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
                                                                    WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta NOT IN (6))",
							valTpDato($idCuenta,'int'));
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
	
	//EXCLUYE LAS Nota De Credito, Retencion y Retencion I.S.L.R COMO FORMAS DE PAGO
	$query = sprintf("SELECT * FROM formapagos WHERE idFormaPago <= 8 ORDER BY nombreFormaPago ASC;");
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
			
	$objResponse->script("xajax_actualizarObjetosExistentes(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListaPagos'))");
	
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

function guardarPago($frmDetallePago, $frmListaPagos, $frmDcto){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cj_nota_cargo_por_pagar_list","insertar")) { errorCargarPago($objResponse); return $objResponse; }
	
	$idNotaCargo = $_GET['id'];
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$idModulo = $frmDcto['lstModulo'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorCargarPago($objResponse); return $objResponse->alert($Result1[1]); }
	
	mysql_query("START TRANSACTION;");

	// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
	$queryAperturaCaja = sprintf("SELECT * FROM ".$apertCajaPpal." ape
	WHERE idCaja = %s
		AND statusAperturaCaja IN (1,2)
		AND (ape.id_empresa = %s
			OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
											WHERE suc.id_empresa = %s));",
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsAperturaCaja = mysql_query($queryAperturaCaja);
	if (!$rsAperturaCaja) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
	$fechaRegistroPago = $rowAperturaCaja["fechaAperturaCaja"];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = %s
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato(45, "int"), // 45 = Recibo de Pago Vehículos
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$numeroActualPago = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
        if ($rowNumeracion['numero_actual'] == "") { errorCargarPago($objResponse); return $objResponse->alert("No se ha configurado numeracion de comprobantes de pago"); }
		
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// INSERTA EL RECIBO DE PAGO
	$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
	VALUES (%s, %s, %s, %s, %s, %s, %s)",
		valTpDato($numeroActualPago, "int"),
		valTpDato($fechaRegistroPago, "date"),
		valTpDato(2, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
		valTpDato(0, "int"),		
		valTpDato($idNotaCargo, "int"),
		valTpDato($idModulo, "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL); }
	$idEncabezadoReciboPago = mysql_insert_id();
		
	// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
	$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_nc_rs (id_nota_cargo, fecha_pago)
	VALUES (%s, %s)",
		valTpDato($idNotaCargo, "int"),
		valTpDato($fechaRegistroPago, "date"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL); }
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
		if (isset($frmListaPagos['txtFormaPago'.$valor])){
			$estatusPago = 1;
			$idCheque = "";
			if ($frmListaPagos['txtFormaPago'.$valor] == 1){//EFECTIVO
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuenta = "-";
				$numeroDocumento = "-";
				$tipoCheque = "-";
				$campo = "saldoEfectivo";
				$txtMonto = $frmListaPagos['txtMonto'.$valor];
			} else if ($frmListaPagos['txtFormaPago'.$valor] == 2){//CHEQUE	
				$idCheque = $frmListaPagos['txtIdDocumento'.$valor];
				$bancoCliente = $frmListaPagos['txtBancoCliente'.$valor];
				$bancoCompania = 1;
				$numeroCuenta = $frmListaPagos['txtNumeroCuenta'.$valor];
				$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "0";
				$campo = "saldoCheques";
				$txtMonto = 0;
			} else if ($frmListaPagos['txtFormaPago'.$valor] == 3){//DEPOSITO
				$bancoCliente = '1';
				$bancoCompania = $frmListaPagos['txtBancoCompania'.$valor];
				$numeroCuenta = numeroCuenta($frmListaPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoDepositos";
				$txtMonto = $frmListaPagos['txtMonto'.$valor];
			} else if ($frmListaPagos['txtFormaPago'.$valor] == 4){//TRANSFERENCIA BANCARIA
				$bancoCliente = $frmListaPagos['txtBancoCliente'.$valor];
				$bancoCompania = $frmListaPagos['txtBancoCompania'.$valor];
				$numeroCuenta = numeroCuenta($frmListaPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoTransferencia";
				$txtMonto = $frmListaPagos['txtMonto'.$valor];
			} else if ($frmListaPagos['txtFormaPago'.$valor] == 5){//TARJETA DE CREDITO
				$bancoCliente = $frmListaPagos['txtBancoCliente'.$valor];
				$bancoCompania = $frmListaPagos['txtBancoCompania'.$valor];
				$numeroCuenta = numeroCuenta($frmListaPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoTarjetaCredito";
				$txtMonto = $frmListaPagos['txtMonto'.$valor];
			} else if ($frmListaPagos['txtFormaPago'.$valor] == 6){//TARJETA DE DEBITO
				$bancoCliente = $frmListaPagos['txtBancoCliente'.$valor];
				$bancoCompania = $frmListaPagos['txtBancoCompania'.$valor];
				$numeroCuenta = numeroCuenta($frmListaPagos['txtNumeroCuenta'.$valor]);
				$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoTarjetaDebito";
				$txtMonto = $frmListaPagos['txtMonto'.$valor];
			} else if ($frmListaPagos['txtFormaPago'.$valor] == 7){//ANTICIPO
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuenta = "-";
				$numeroDocumento = $frmListaPagos['txtIdDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoAnticipo";
				$txtMonto = 0;
					
				// BUSCA LOS DATOS DEL ANTICIPO (0 = Anulado; 1 = Activo)
				$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo cxc_ant
				WHERE cxc_ant.idAnticipo = %s
					AND cxc_ant.estatus = 1;",
					valTpDato($numeroDocumento, "int"));
				$rsAnticipo = mysql_query($queryAnticipo);
				if (!$rsAnticipo) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowAnticipo = mysql_fetch_array($rsAnticipo);
					
				// (0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
				$estatusPago = (in_array($rowAnticipo['estadoAnticipo'], array(0))) ? 2 : $estatusPago;
			} else if ($frmListaPagos['txtFormaPago'.$valor] == 8){//NOTA CREDITO
				$bancoCliente = 1;
				$bancoCompania = 1;
				$numeroCuenta = "-";
				$numeroDocumento = $frmListaPagos['txtIdDocumento'.$valor];
				$tipoCheque = "-";
				$campo = "saldoNotaCredito";
			}
			
			// NO SUMA ANTICIPOS(7) NI CHEQUES(2) EN EL SALDO DE LA CAJA, YA QUE ESTOS SE SUMAN EN LAS DEMAS FORMAS DE PAGO (EF, CH, DP, TB, TC, TD)
			if($frmListaPagos['txtFormaPago'.$valor] != 2){
				$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
					%s = %s + %s,
					saldoCaja = saldoCaja + %s
				WHERE id = %s",
					$campo, $campo, valTpDato($frmListaPagos['txtMonto'.$valor], "real_inglesa"),
					valTpDato($txtMonto, "real_inglesa"),
					valTpDato($rowAperturaCaja['id'], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
			
			// INSERTA LOS PAGOS DEL DOCUMENTO
			$insertSQL = sprintf("INSERT INTO cj_det_nota_cargo (id_cheque, fechaPago, idFormaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, monto_pago, idNotaCargo, tipoCheque, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_encabezado_nc)
			VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idCheque, "int"),
				valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
				valTpDato($frmListaPagos['txtFormaPago'.$valor], "int"),
				valTpDato($numeroDocumento, "text"),
				valTpDato($bancoCliente, "int"),
				valTpDato($bancoCompania, "int"),
				valTpDato($numeroCuenta, "text"),
				valTpDato($frmListaPagos['txtMonto'.$valor], "real_inglesa"),
				valTpDato($frmDcto['hddIdNotaCargo'], "int"),
				valTpDato($tipoCheque, "text"),
				valTpDato(1, "int"),
				valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato(0, "int"),
				valTpDato($estatusPago, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
				valTpDato($idEncabezadoPago, "int"));
			$rsInsertPago = mysql_query($insertSQL);
			if (!$rsInsertPago) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL); }
			$idPago = mysql_insert_id();
			
			$arrayIdDctoContabilidad[] = array(
				$idPago,
				$idModulo,
				"CAJAENTRADA");
			
			if ($frmListaPagos['txtFormaPago'.$valor] == 3){ //DEPOSITO
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
						
						$insertSQL = sprintf("INSERT INTO cj_det_pagos_deposito_nota_cargo (id_det_nota_cargo, fecha_deposito, idFormaPago, idBanco, numero_cuenta, numero_cheque, monto, id_tipo_documento, idCaja)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
							valTpDato($idPago, "int"),
							valTpDato(date("Y-m-d",strtotime($frmListaPagos['txtFechaDeposito'.$valor])), "date"),
							valTpDato($arrayFormaPago[$indiceDeposito], "int"),
							valTpDato($bancoDetalleDeposito, "int"),
							valTpDato($nroCuentaDetalleDeposito, "text"),
							valTpDato($nroChequeDetalleDeposito, "text"),
							valTpDato($arrayMonto[$indiceDeposito], "real_inglesa"),
							valTpDato(2, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
							valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL); }
					}
				}
			} else if(in_array($frmListaPagos['txtFormaPago'.$valor], array(5,6))){ // T. CREDITO Y DEBITO
				$sqlSelectRetencionPunto = sprintf("SELECT id_retencion_punto FROM te_retencion_punto
				WHERE id_cuenta = %s
					AND id_tipo_tarjeta = %s",
					valTpDato($frmListaPagos['txtNumeroCuenta'.$valor], "int"),
					valTpDato($frmListaPagos['txtTipoTarjeta'.$valor], "int"));
				$rsSelectRetencionPunto = mysql_query($sqlSelectRetencionPunto);
				if (!$rsSelectRetencionPunto) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectRetencionPunto); }
				$rowSelectRetencionPunto = mysql_fetch_array($rsSelectRetencionPunto);
				
				$insertSQL = sprintf("INSERT INTO cj_cc_retencion_punto_pago (id_caja, id_pago, id_tipo_documento, id_retencion_punto)
				VALUES (%s, %s, %s, %s)",
					valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					valTpDato($idPago, "int"),
					valTpDato(2, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
					valTpDato($rowSelectRetencionPunto['id_retencion_punto'], "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL); }
				
			} else if ($frmListaPagos['txtFormaPago'.$valor] == 7){ //ANTICIPO
				$sqlSelectAnticipo = sprintf("SELECT * FROM cj_cc_anticipo WHERE idAnticipo = %s",
				valTpDato($frmListaPagos['txtIdDocumento'.$valor], "int"));
				$rsSelectAnticipo = mysql_query($sqlSelectAnticipo);
				if (!$rsSelectAnticipo) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectAnticipo); }
				$rowSelectAnticipo = mysql_fetch_array($rsSelectAnticipo);
				
				$totalAnticipo = $rowSelectAnticipo['saldoAnticipo'] - $frmListaPagos['txtMonto'.$valor];
				$estatusAnticipo = ($totalAnticipo == 0) ? 3 : 2;
				
				$updateSQL = sprintf("UPDATE cj_cc_anticipo SET
					saldoAnticipo = %s,
					estadoAnticipo = %s
				WHERE idAnticipo = %s",
					valTpDato($totalAnticipo, "real_inglesa"),
					valTpDato($estatusAnticipo, "int"),
					valTpDato($frmListaPagos['txtIdDocumento'.$valor], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$updateSQL); }
			} else if ($frmListaPagos['txtFormaPago'.$valor] == 8) { //NOTA CREDITO
				$sqlSelectNotaCredito = sprintf("SELECT * FROM cj_cc_notacredito WHERE idNotaCredito = %s",
					valTpDato($frmListaPagos['txtIdDocumento'.$valor], "int"));
				$rsSelectNotaCredito = mysql_query($sqlSelectNotaCredito);
				if (!$rsSelectNotaCredito) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectNotaCredito); }
				$rowSelectNotaCredito = mysql_fetch_array($rsSelectNotaCredito);
				
				$totalNotaCredito = $rowSelectNotaCredito['saldoNotaCredito'] - $frmListaPagos['txtMonto'.$valor];
				$estatusNotaCredito = ($totalNotaCredito == 0) ? 3 : 2;
				
				$updateSQL = sprintf("UPDATE cj_cc_notacredito SET
					saldoNotaCredito = %s,
					estadoNotaCredito = %s
				WHERE idNotaCredito = %s",
					valTpDato($totalNotaCredito, "real_inglesa"),
					valTpDato($estatusNotaCredito, "int"),
					valTpDato($frmListaPagos['txtIdDocumento'.$valor], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$updateSQL); }
			}elseif($frmListaPagos['txtFormaPago'.$valor] == 2){//CHEQUES
				
				$sqlCheque = sprintf("SELECT numero_cheque, saldo_cheque FROM cj_cc_cheque WHERE id_cheque = %s;",
					valTpDato($idCheque, "int"));
				$rsCheque = mysql_query($sqlCheque);
				if (!$rsCheque) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlCheque); }
				$rowCheque = mysql_fetch_assoc($rsCheque);
				
				$saldoCheque = $rowCheque['saldo_cheque'] - $frmListaPagos['txtMonto'.$valor];
				$estatusCheque = ($saldoCheque == 0) ? 3 : 2;
				if ($idCheque > 0 && $saldoCheque < 0) { errorCargarPago($objResponse); return $objResponse->alert("El saldo del cheque Nro: ".$rowCheque['numero_cheque']." no puede quedar en negativo: ".$saldoCheque); }
				
				$sqlUpdateCheque = sprintf("UPDATE cj_cc_cheque SET 
					saldo_cheque = %s, 
					estado_cheque = %s
				WHERE id_cheque = %s;",
					valTpDato($saldoCheque, "real_inglesa"),
					valTpDato($estatusCheque, "int"),
					valTpDato($idCheque, "int"));
				$rsUpdateCheque = mysql_query($sqlUpdateCheque);
				if (!$rsUpdateCheque) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateCheque); }
			}
			
			// INSERTA EL DETALLE DEL RECIBO DE PAGO
			$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
			VALUES (%s, %s)",
				valTpDato($idEncabezadoReciboPago, "int"),
				valTpDato($idPago, "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL); }
		}
	}
	
	// ACTUALIZA SALDOS Y ESTATUS DEL DOCUMENTO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
	if (str_replace(",", "", $frmListaPagos['txtMontoPorPagar']) > 0) {	
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo SET
			saldoNotaCargo = %s,
			estadoNotaCargo = 2
		WHERE idNotaCargo = %s",
			valTpDato($frmListaPagos['txtMontoPorPagar'], "real_inglesa"),
			valTpDato($idNotaCargo, "int"));
	} else {
		
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo SET
			saldoNotaCargo = 0,
			estadoNotaCargo = 1
		WHERE idNotaCargo = %s",
			valTpDato($idNotaCargo, "int"));
	}
	
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$updateSQL);	}
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$queryNotaCargo = sprintf("SELECT idCliente, id_empresa FROM cj_cc_notadecargo
	WHERE idNotaCargo = %s",
		valTpDato($_GET['id'], "int"));
	$rsNotaCargo = mysql_query($queryNotaCargo);
	if (!$rsNotaCargo) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryNotaCargo); }
	$rowNotaCargo = mysql_fetch_array($rsNotaCargo);
	
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
		valTpDato($rowNotaCargo['idCliente'], "int"),
		valTpDato($rowNotaCargo['id_empresa'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	mysql_query("COMMIT;");
	
	errorCargarPago($objResponse);
	$objResponse->alert("Pago cargado correctamente");
	
	$objResponse->script(sprintf("window.location.href='cj_nota_cargo_por_pagar_list.php';"));
	$objResponse->script(sprintf("verVentana('reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s',960,550)", $idEncabezadoReciboPago));
	
	return $objResponse;
}

function listadoAnticipoNotaCreditoCheque($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	
	if($valCadBusq[2] == 7){//ANTICIPOS
		$campoIdCliente = "idCliente";
	}elseif($valCadBusq[2] == 8){//NOTAS DE CREDITO
		$campoIdCliente = "idCliente";
	}elseif($valCadBusq[2] == 2){//CHEQUES
		$campoIdCliente = "id_cliente";
	}
	
	$sqlBusq .= $cond.sprintf("%s = %s
	AND (id_empresa = %s
		OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
			WHERE suc.id_empresa_padre = id_empresa)
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = id_empresa)
		OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = id_empresa))",
		$campoIdCliente,
		valTpDato($valCadBusq[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	
	if($valCadBusq[2] == 7){//ANTICIPOS
		$sqlBusq .= $cond.sprintf("estadoAnticipo IN (1,2)");
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = 1");
	}elseif($valCadBusq[2] == 8){//NOTAS DE CREDITO
		$sqlBusq .= $cond.sprintf("estadoNotaCredito IN (1,2)");
	}elseif($valCadBusq[2] == 2){//CHEQUES
		$sqlBusq .= $cond.sprintf("cj_cc_cheque.estatus IN (1,2) AND saldo_cheque > 0 AND tipo_cheque = 1");//1 = tipo cliente
	}
		
	if($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if($valCadBusq[2] == 7) {//ANTICIPOS
			$sqlBusq .= $cond.sprintf(" numeroAnticipo = %s ",
				valTpDato($valCadBusq[2], "int"));
		}elseif($valCadBusq[2] == 8){//NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf(" numeracion_nota_credito = %s ",
				valTpDato($valCadBusq[2], "int"));
		}elseif($valCadBusq[2] == 2){//CHEQUES
			$sqlBusq .= $cond.sprintf(" cj_cc_cheque.numero_cheque = %s ",
				valTpDato($valCadBusq[2], "int"));
		}
	}
	
	if($valCadBusq[3] != "-1" && $valCadBusq[3] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if($valCadBusq[2] == 7) {//ANTICIPOS
			$sqlBusq .= $cond.sprintf(" idAnticipo NOT IN (%s) ",
			$valCadBusq[3]);
		}elseif($valCadBusq[2] == 8){//NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf(" idNotaCredito NOT IN (%s) ",
			$valCadBusq[3]);
		}elseif($valCadBusq[2] == 2){//CHEQUES
			$sqlBusq .= $cond.sprintf(" cj_cc_cheque.id_cheque NOT IN (%s) ",
			$valCadBusq[3]);
		}
	}
	
	if($valCadBusq[2] == 7){//ANTICIPOS
		$query = sprintf("SELECT
			idAnticipo AS idDocumento,
			saldoAnticipo AS saldoDocumento,
			numeroAnticipo AS numeroDocumento,
			fechaAnticipo AS fechaDocumento,
			observacionesAnticipo AS observacionDocumento
		FROM
			cj_cc_anticipo %s", $sqlBusq);
	}elseif($valCadBusq[2] == 8){//NOTAS DE CREDITO
		$query = sprintf("SELECT
			idNotaCredito AS idDocumento,
			saldoNotaCredito AS saldoDocumento,
			numeracion_nota_credito AS numeroDocumento,
			fechaNotaCredito AS fechaDocumento,
			observacionesNotaCredito AS observacionDocumento
		FROM
			cj_cc_notacredito %s", $sqlBusq);
	}elseif($valCadBusq[2] == 2){//CHEQUES
		$query = sprintf("SELECT
			cj_cc_cheque.id_cliente AS idCliente,
			cj_cc_cheque.id_cheque AS idDocumento,
			cj_cc_cheque.saldo_cheque AS saldoDocumento,
			cj_cc_cheque.numero_cheque AS numeroDocumento,
			cj_cc_cheque.fecha_cheque AS fechaDocumento,
			cj_cc_cheque.observacion_cheque AS observacionDocumento
		FROM
			cj_cc_cheque %s", $sqlBusq);
	}
				
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);	
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit);
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCreditoCheque", "20%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Documento"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCreditoCheque", "15%", $pageNum, "fechaDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCreditoCheque", "40%", $pageNum, "observacionDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Observaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCreditoCheque", "25%", $pageNum, "saldoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo"));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while($row = mysql_fetch_assoc($rsLimit)){
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCreditoCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCreditoCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoAnticipoNotaCreditoCheque(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCreditoCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCreditoCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
	$('trBuscarAnticipoNotaCreditoCheque').style.display = '';
	
	$('tblDetallePago').style.display = 'none';
	$('tblListados').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Listado");
	$objResponse->assign("tblListados","width","700");
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display = '';
		centrarDiv($('divFlotante'));
		
		document.forms['frmBuscarAnticipoNotaCreditoCheque'].reset();
		$('txtCriterioBusqAnticipoNotaCredito').focus();
		$('txtCriterioBusqAnticipoNotaCredito').select();
	}");
	
	return $objResponse;
}

//
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentes");
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentesDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarFecha");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCreditoCheque");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargarCuentasCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
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
$xajax->register(XAJAX_FUNCTION,"listadoAnticipoNotaCreditoCheque");

function errorCargarPago($objResponse){
	$objResponse->script("
	byId('agregar').disabled = false;
	byId('btnGuardarPago').disabled = false;
	byId('btnCancelar').disabled = false;");
}

function nombreBanco($idBanco){
	$query = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s",$idBanco);
	$rsQuery = mysql_query($query) or die(mysql_error()." Linea: ".__LINE__." Query: ".$query);
	$rowQuery = mysql_fetch_array($rsQuery);
	
	return $rowQuery['nombreBanco'];
}

function numeroCuenta($idCuenta){
	$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",$idCuenta);
	$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta) or die(mysql_error()." Linea: ".__LINE__." Query: ".$sqlBuscarNumeroCuenta);
	$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	
	return $rowBuscarNumeroCuenta['numeroCuentaCompania'];
}

function informacionCheque($idCheque){
	$query = sprintf("SELECT 
		cj_cc_cheque.id_banco_cliente,
		cj_cc_cheque.cuenta_cliente AS numero_cuenta_cliente,
		bancos.nombreBanco AS nombre_banco_cliente
	FROM cj_cc_cheque 
		INNER JOIN bancos ON cj_cc_cheque.id_banco_cliente = bancos.idBanco
	WHERE cj_cc_cheque.id_cheque = %s LIMIT 1",
		valTpDato($idCheque, "int"));
	$rsQuery = mysql_query($query) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowQuery = mysql_fetch_assoc($rsQuery);
	if(mysql_num_rows($rsQuery) == 0) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$query); }
	
	return $rowQuery;
}

function validarAperturaCaja($idEmpresa, $fecha) {
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM ".$apertCajaPpal." ape
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND id_empresa = %s;",
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
	
	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);
		
		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}
?>