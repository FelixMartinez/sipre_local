<?php


function asignarClaveMovimiento($idClaveMovimiento, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s;",
		valTpDato($idClaveMovimiento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['pago_contado'] == 1 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 1 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	}
	
	$objResponse->script($accion);

	return $objResponse;
}

function asignarDepartamento($frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = $frmDcto['lstModulo'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	
	if ($frmDcto['lstModulo'] >= 0) {
		if (in_array($idModulo, array(0,1,3))) {
			$idCaja = 2;
		} else if (in_array($idModulo, array(2))) {
			$idCaja = 1;
		}
		
		$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"), $idCaja);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	$objResponse->loadCommands(cargaLstVendedor($idEmpresa));
	
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, $frmDcto['lstTipoMovimiento'], $frmDcto['hddTipoPagoCliente'], "3", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));\""));
	
	if ($frmDcto['cbxNroAutomatico'] == 1) {
		$objResponse->script("
		byId('txtNumeroNotaCredito').readOnly = true;
		byId('txtNumeroNotaCredito').className = 'inputInicial';");
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			$objResponse->assign("tdtxtFecha","innerHTML","<input type=\"text\" id=\"txtFecha\" name=\"txtFecha\" size=\"10\" style=\"text-align:center\" value=\"".date("d-m-Y")."\"/>");
			
			$Result1 = buscarNumeroControl($idEmpresa, $idClaveMovimiento);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->assign("txtNumeroControlNotaCredito","value",($Result1[1]));
			}
			
			$objResponse->script("
			byId('txtNumeroControlNotaCredito').readOnly = false;
			byId('txtNumeroControlNotaCredito').className = 'inputHabilitado';
			byId('txtFecha').readOnly = true;
			byId('txtFecha').className = 'inputInicial';");
			
			$objResponse->assign("txtFecha","value",date("d-m-Y"));
		} else {
			$objResponse->call("selectedOption","lstClaveMovimiento","-1");
			
			$objResponse->script("
			byId('lstClaveMovimiento').style.display = 'none';
			
			byId('txtNumeroControlNotaCredito').readOnly = true;
			byId('txtNumeroControlNotaCredito').className = 'inputInicial';
			byId('txtFecha').readOnly = false;
			byId('txtFecha').className = 'inputHabilitado';
			
			jQuery(function($){
				$('#txtFecha').maskInput('99-99-9999',{placeholder:' '});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFecha\",
				dateFormat:\"%d-%m-%Y\",
				cellColorScheme:\"purple\"
			});");
			
			$objResponse->assign("txtNumeroNotaCredito","value","");
			$objResponse->assign("txtNumeroControlNotaCredito","value","");
			$objResponse->assign("txtFecha","value","");
		}
	} else {
		$objResponse->call("selectedOption","lstClaveMovimiento","-1");
		
		$objResponse->script("
		byId('lstClaveMovimiento').style.display = 'none';
		
		byId('txtNumeroNotaCredito').readOnly = false;
		byId('txtNumeroNotaCredito').className = 'inputHabilitado';
		byId('txtNumeroControlNotaCredito').readOnly = false;
		byId('txtNumeroControlNotaCredito').className = 'inputHabilitado';");
		
		$objResponse->assign("txtNumeroControlNotaCredito","value","");
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			$objResponse->assign("tdtxtFecha","innerHTML","<input type=\"text\" id=\"txtFecha\" name=\"txtFecha\" size=\"10\" style=\"text-align:center\" value=\"".date("d-m-Y")."\"/>");
			
			$objResponse->script("
			byId('txtFecha').readOnly = true;
			byId('txtFecha').className = 'inputInicial';");
			
			$objResponse->assign("txtFecha","value",date("d-m-Y"));
		} else {
			$objResponse->script("
			byId('txtFecha').readOnly = false;
			byId('txtFecha').className = 'inputHabilitado';
			
			jQuery(function($){
				$('#txtFecha').maskInput('99-99-9999',{placeholder:' '});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFecha\",
				dateFormat:\"%d-%m-%Y\",
				cellColorScheme:\"purple\"
			});");
			
			$objResponse->assign("txtFecha","value","");
		}
	}
	
	if ($frmDcto['lstModulo'] >= 0) {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		
		byId('txtIdCliente').onblur = function() { }
		
		byId('lstAplicaLibro').className = 'inputInicial';
		byId('lstAplicaLibro').onchange = function () {
			selectedOption(this.id,'".$frmDcto['lstAplicaLibro']."');
		}");
	} else {
		$objResponse->script("
		byId('txtIdCliente').onblur = function() { xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false'); }
		
		byId('lstAplicaLibro').className = 'inputHabilitado';
		byId('lstAplicaLibro').onchange = function () { }");
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false"){
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente.id = %s",
		valTpDato($idCliente, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
		
	if ($estatusCliente != "-1" && $estatusCliente != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.status = %s",
			valTpDato($estatusCliente, "text"));
	}
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto,
		cliente.status
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$idClaveMovimiento = ($idClaveMovimiento == "") ? $rowCliente['id_clave_movimiento_predeterminado'] : $idClaveMovimiento;
	
	if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
		$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
			valTpDato($rowCliente['id_cliente_empresa'], "int"));
		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
		
		$fechaVencimiento = suma_fechas("d-m-Y",date("d-m-Y"),$txtDiasCreditoCliente);
		
		$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
		$objResponse->assign("hddTipoPagoCliente","value",0);
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "0", "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','0','1','".$idClaveMovimiento."','onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"');
		}");*/
	} else {
		$fechaVencimiento = date("d-m-Y");
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		$objResponse->assign("hddTipoPagoCliente","value",1);
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','1','1','".$idClaveMovimiento."','onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"');
		}");*/
	}
	
	if ($rowCliente['id'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">Cliente se encuentra Inactivo</div>" : "";
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",$tdMsjCliente);
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarFactura($idFactura, $cerrarVentana = "true"){
	$objResponse = new xajaxResponse();
		
	$objResponse->script("
	byId('txtValorNumeroFaNC').readOnly = true;
	byId('txtSubTotal').readOnly = true;
	byId('txtSubTotalDescuento').readOnly = true;
	byId('txtFlete').readOnly = true;
	byId('txtTotalNotaCredito').readOnly = true;
	byId('txtMontoExonerado').readOnly = true;
	byId('txtMontoExento').readOnly = true;");
	
	$queryFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFactura = mysql_fetch_array($rsFactura);
	
	$objResponse->assign("hddIdFacturaNotaCargo","value",$idFactura);
	$objResponse->assign("txtValorNumeroFaNC","value",$rowFactura['numeroFactura']);
	
	$objResponse->loadCommands(asignarEmpresaUsuario($rowFactura['id_empresa'], "Empresa", "ListaEmpresa"));
	$objResponse->loadCommands(asignarCliente($rowFactura['idCliente'], $rowFactura['id_empresa'], ""));
	$objResponse->loadCommands(cargaLstModulo($rowFactura['idDepartamentoOrigenFactura'], "selectedOption(this.id,'".$rowFactura['idDepartamentoOrigenFactura']."');", true));
	$objResponse->loadCommands(cargaLstVendedor($rowFactura['id_empresa'], $rowFactura['idVendedor'], true));
	$objResponse->call("selectedOption","lstAplicaLibro",$rowFactura['aplicaLibros']);
	$objResponse->call("selectedOption","lstTipoMovimiento",2);
	
	$objResponse->script("
	byId('lstAplicaLibro').onchange = function() {
		selectedOption(this.id,'".$rowFactura['aplicaLibros']."');
	}");
	
	if ($rowFactura['aplicaLibros'] == 1) {
		$objResponse->script("
		byId('cbxNroAutomatico').checked = true;");
	} else {
		$objResponse->script("
		byId('cbxNroAutomatico').checked = false;
		jQuery(function($){
			$('#txtFecha').maskInput('99-99-9999',{placeholder:' '});
		});
		
		new JsDatePick({
			useMode:2,
			target:\"txtFecha\",
			dateFormat:\"%d-%m-%Y\",
			cellColorScheme:\"purple\"
		});");
	}
	
	$txtSubTotal = ($rowFactura['estadoFactura'] == 2) ? $rowFactura['saldoFactura'] / (($rowFactura['porcentajeIvaFactura'] / 100) + 1) : $rowFactura['subtotalFactura'];
	$txtBaseImponible = ($rowFactura['estadoFactura'] == 2) ? $rowFactura['saldoFactura'] / (($rowFactura['porcentajeIvaFactura'] / 100) + 1) : $rowFactura['baseImponible'];
	
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($rowFactura['descuentoFactura'], 2, ".", ","));
	$objResponse->assign("txtFlete","value",number_format($rowFactura['fletesFactura'], 2, ".", ","));
	$objResponse->assign("txtTotalNotaCredito","value",number_format($rowFactura['saldoFactura'], 2, ".", ","));
	$objResponse->assign("txtMontoExonerado","value",number_format($rowFactura['montoExonerado'], 2, ".", ","));
	$objResponse->assign("txtMontoExento","value",number_format($rowFactura['montoExento'], 2, ".", ","));
	
	// CARGA LOS IMPUESTOS 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("
	SELECT 
		cxc_fact.idFactura,
		cxc_fact.baseImponible AS base_imponible,
		cxc_fact.calculoIvaFactura AS subtotal_iva,
		(SELECT iva.idIva FROM pg_iva iva WHERE estado = 1 AND tipo IN (6) AND activo = 1) AS id_iva,
		cxc_fact.porcentajeIvaFactura AS iva,
		(SELECT iva.observacion FROM pg_iva iva WHERE estado = 1 AND tipo IN (6) AND activo = 1) AS observacion,
		(SELECT IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE estado = 1 AND tipo IN (6) AND activo = 1) AS lujo
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE cxc_fact.idFactura = %s
		AND cxc_fact.calculoIvaFactura > 0
	
	UNION
	
	SELECT 
		cxc_fact.idFactura,
		cxc_fact.base_imponible_iva_lujo AS base_imponible,
		cxc_fact.calculoIvaDeLujoFactura AS subtotal_iva,
		(SELECT iva.idIva FROM pg_iva iva WHERE estado = 1 AND tipo IN (2) AND activo = 1) AS id_iva,
		cxc_fact.porcentajeIvaDeLujoFactura AS iva,
		(SELECT iva.observacion FROM pg_iva iva WHERE estado = 1 AND tipo IN (2) AND activo = 1) AS observacion,
		(SELECT IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE estado = 1 AND tipo IN (2) AND activo = 1) AS lujo
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE cxc_fact.idFactura = %s
		AND cxc_fact.calculoIvaDeLujoFactura > 0
	
	ORDER BY 1",
		valTpDato($idFactura, "int"),
		valTpDato($idFactura, "int"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$indice = 0;
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$indice++;
		
		// INSERTA EL ITEM SIN INJECT
		$objResponse->script(sprintf("
		var elemento = '".
			"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
				"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
					"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
					"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
				"<td></td>".
				"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
			"</tr>';
			
			$('#trNetoOrden').before(elemento);",
			$indice,
				$indice, utf8_encode($rowIva['observacion']),
					$indice, $indice, $rowIva['id_iva'],
					$indice, $indice, $rowIva['lujo'],
					$indice,
				$indice, $indice, number_format(round($rowIva['base_imponible'],2), 2, ".", ","),
				$indice, $indice, $rowIva['iva'], "%",
				$indice, $indice, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
			
			$indice));
	}
	
	$objResponse->script("
	xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarMotivo($idMotivo, $nombreObjeto, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_motivo
	WHERE id_motivo = %s
		AND modulo LIKE 'CC'
		AND ingreso_egreso LIKE 'E';",
		valTpDato($idMotivo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$row['id_motivo']);
	$objResponse->assign("txt".$nombreObjeto,"value",utf8_encode($row['descripcion']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaMotivo').click();");
	}
	
	return $objResponse;
}

function asignarNotaCargo($idNotaCargo, $cerrarVentana = "true"){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('txtValorNumeroFaNC').readOnly = true;
	byId('txtSubTotal').readOnly = true;
	byId('txtSubTotalDescuento').readOnly = true;
	byId('txtFlete').readOnly = true;
	byId('txtTotalNotaCredito').readOnly = true;
	byId('txtMontoExonerado').readOnly = true;
	byId('txtMontoExento').readOnly = true;");
	
	$queryNotaCargo = sprintf("SELECT * FROM cj_cc_notadecargo WHERE idNotaCargo = %s;",
		valTpDato($idNotaCargo, "int"));
	$rsNotaCargo = mysql_query($queryNotaCargo);
	if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNotaCargo = mysql_fetch_array($rsNotaCargo);
	
	$objResponse->assign("hddIdFacturaNotaCargo","value",$idNotaCargo);
	$objResponse->assign("txtValorNumeroFaNC","value",$rowNotaCargo['numeroNotaCargo']);
	
	$objResponse->loadCommands(asignarEmpresaUsuario($rowNotaCargo['id_empresa'], "Empresa", "ListaEmpresa"));
	$objResponse->loadCommands(asignarCliente($rowNotaCargo['idCliente'], $rowNotaCargo['id_empresa'], ""));
	$objResponse->loadCommands(cargaLstModulo($rowNotaCargo['idDepartamentoOrigenNotaCargo'], "selectedOption(this.id,'".$rowNotaCargo['idDepartamentoOrigenNotaCargo']."');", true));
	$objResponse->loadCommands(cargaLstVendedor($rowNotaCargo['id_empresa'], $rowNotaCargo['idVendedor']));
	$objResponse->call("selectedOption","lstAplicaLibro",$rowNotaCargo['aplicaLibros']);
	$objResponse->call("selectedOption","lstTipoMovimiento",2);
	
	$objResponse->script("
	byId('lstAplicaLibro').onchange = function() {
		selectedOption(this.id,'".$rowNotaCargo['aplicaLibros']."');
	}");
	
	if ($rowNotaCargo['aplicaLibros'] == 1) {
		$objResponse->script("
		byId('cbxNroAutomatico').checked = true;");
	} else {
		$objResponse->script("
		byId('cbxNroAutomatico').checked = false;
		jQuery(function($){
			$('#txtFecha').maskInput('99-99-9999',{placeholder:' '});
		});
		
		new JsDatePick({
			useMode:2,
			target:\"txtFecha\",
			dateFormat:\"%d-%m-%Y\",
			cellColorScheme:\"purple\"
		});");
	}
	
	$txtSubTotal = ($rowNotaCargo['estadoNotaCargo'] == 2) ? $rowNotaCargo['saldoNotaCargo'] / (($rowNotaCargo['porcentajeIvaNotaCargo'] / 100) + 1) : $rowNotaCargo['subtotalNotaCargo'];
	$txtBaseImponible = ($rowNotaCargo['estadoNotaCargo'] == 2) ? $rowNotaCargo['saldoNotaCargo'] / (($rowNotaCargo['porcentajeIvaNotaCargo'] / 100) + 1) : $rowNotaCargo['baseImponibleNotaCargo'];
	
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($rowNotaCargo['descuentoNotaCargo'], 2, ".", ","));
	$objResponse->assign("txtFlete","value",number_format($rowNotaCargo['fletesNotaCargo'], 2, ".", ","));
	$objResponse->assign("txtTotalNotaCredito","value",number_format($rowNotaCargo['saldoNotaCargo'], 2, ".", ","));
	$objResponse->assign("txtMontoExonerado","value",number_format($rowNotaCargo['montoExoneradoNotaCargo'], 2, ".", ","));
	$objResponse->assign("txtMontoExento","value",number_format($rowNotaCargo['montoExentoNotaCargo'], 2, ".", ","));
	
	// CARGA LOS IMPUESTOS 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("
	SELECT 
		cxc_nd.idNotaCargo,
		cxc_nd.baseImponibleNotaCargo AS base_imponible,
		cxc_nd.calculoIvaNotaCargo AS subtotal_iva,
		(SELECT iva.idIva FROM pg_iva iva WHERE estado = 1 AND tipo IN (6) AND activo = 1) AS id_iva,
		cxc_nd.porcentajeIvaNotaCargo AS iva,
		(SELECT iva.observacion FROM pg_iva iva WHERE estado = 1 AND tipo IN (6) AND activo = 1) AS observacion,
		(SELECT IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE estado = 1 AND tipo IN (6) AND activo = 1) AS lujo
	FROM cj_cc_notadecargo cxc_nd
	WHERE cxc_nd.idNotaCargo = %s
		AND cxc_nd.calculoIvaNotaCargo > 0
	
	UNION
	
	SELECT 
		cxc_nd.idNotaCargo,
		cxc_nd.base_imponible_iva_lujo AS base_imponible,
		cxc_nd.ivaLujoNotaCargo AS subtotal_iva,
		(SELECT iva.idIva FROM pg_iva iva WHERE estado = 1 AND tipo IN (2) AND activo = 1) AS id_iva,
		cxc_nd.porcentaje_iva_lujo AS iva,
		(SELECT iva.observacion FROM pg_iva iva WHERE estado = 1 AND tipo IN (2) AND activo = 1) AS observacion,
		(SELECT IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE estado = 1 AND tipo IN (2) AND activo = 1) AS lujo
	FROM cj_cc_notadecargo cxc_nd
	WHERE cxc_nd.idNotaCargo = %s
		AND cxc_nd.ivaLujoNotaCargo > 0
	
	ORDER BY 1",
		valTpDato($idNotaCargo, "int"),
		valTpDato($idNotaCargo, "int"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$indice = 0;
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$indice++;
		
		// INSERTA EL ITEM SIN INJECT
		$objResponse->script(sprintf("
		var elemento = '".
			"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
				"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
					"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
					"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
				"<td></td>".
				"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
			"</tr>';
			
			$('#trNetoOrden').before(elemento);",
			$indice,
				$indice, utf8_encode($rowIva['observacion']),
					$indice, $indice, $rowIva['id_iva'],
					$indice, $indice, $rowIva['lujo'],
					$indice,
				$indice, $indice, number_format(round($rowIva['base_imponible'],2), 2, ".", ","),
				$indice, $indice, $rowIva['iva'], "%",
				$indice, $indice, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
			
			$indice));
	}
	
	$objResponse->script("
	xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarFactura($frmBuscarFactura, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarFactura['txtCriterioBuscarFactura']);
		
	$objResponse->loadCommands(listaFactura(0, "idFactura", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarMotivo($frmBuscarMotivo) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarMotivo['hddObjDestinoMotivo'],
		$frmBuscarMotivo['txtCriterioBuscarMotivo']);
	
	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarNotaCargo($frmBuscarNotaCargo, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarNotaCargo['txtCriterioBuscarNotaCargo']);
		
	$objResponse->loadCommands(listaNotasCargo(0, "idNotaCargo", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Débito, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	if ($selId != "-1" && $selId != "") {
		$cond = (strlen($sqlBusq) > 0) ? " OR " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_clave_movimiento = %s",
			valTpDato($selId, "int"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = "";
			if ($selId == $rowClaveMov['id_clave_movimiento']) {
				$selected = "selected=\"selected\"";
				
				$objResponse->loadCommands(asignarClaveMovimiento($rowClaveMov['id_clave_movimiento'], ""));
			}
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstVendedor($idEmpresa = "", $selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((clave_filtro IN (1,3,5,7,9,10) AND activo = 1)
	OR id_empleado = %s)",
		valTpDato($selId, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$query = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado %s ORDER BY nombre_empleado;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstVendedor\" name=\"lstVendedor\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"0\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {                   
		$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstVendedor","innerHTML",$html);
		
	return $objResponse;
}

function cargarIvas(){
	$objResponse = new xajaxResponse();
	
	$queryIva = "SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE tipo IN (2,6) AND estado = 1";
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowIva = mysql_fetch_assoc($rsIva)){
		$contFila++;
		
		// INSERTA EL ITEM SIN INJECT
		$objResponse->script(sprintf("
		var elemento = '".
			"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
				"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
					"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
					"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputHabilitado\" onblur=\"calcularTotal();\" style=\"text-align:right\" value=\"%s\"/></td>".
				"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
				"<td></td>".
				"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
			"</tr>';
			
			$('#trNetoOrden').before(elemento);",
			$contFila,
				$contFila, utf8_encode($rowIva['observacion']),
					$contFila, $contFila, $rowIva['idIva'],
					$contFila, $contFila, $rowIva['lujo'],
					$contFila,
				$contFila, $contFila, number_format(round($rowIva['base_imponible'],2), 2, ".", ","),
				$contFila, $contFila, $rowIva['iva'], "%",
				$contFila, $contFila, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
			
			$contFila));
		
		$arrayObjIva[] = $contFila;
	}
	$objResponse->assign("hddObjIva","value",((count($arrayObjIva) > 0) ? implode("|",$arrayObjIva) : ""));
	
	return $objResponse;
}

function formDcto($idNotaCredito, $hddTipoDcto){
	$objResponse = new xajaxResponse();
	
	if ($idNotaCredito > 0) {
		$objResponse->script("
		byId('aListarFactura').style.display = 'none';
		byId('aListarNotaCargo').style.display = 'none';
		
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		byId('txtIdCliente').readOnly = true;
		byId('txtNumeroNotaCredito').readOnly = true;
		byId('lblNroAutomatico').style.display = 'none';
		byId('txtFecha').readOnly = true;
		byId('aListarMotivo').style.display = 'none';
		byId('txtIdMotivo').readOnly = true;
		byId('txtIdMotivo').className = 'inputInicial';
		byId('txtObservacion').readOnly = true;
		
		byId('btnNotaCreditoPDF').style.display = 'none';
		
		byId('txtSubTotal').readOnly = true;
		byId('txtSubTotal').className = 'inputSinFondo';
		byId('txtSubTotalDescuento').readOnly = true;
		byId('txtSubTotalDescuento').className = 'inputSinFondo';
		byId('txtFlete').readOnly = true;
		byId('txtFlete').className = 'inputSinFondo';
		byId('txtMontoExonerado').readOnly = true;
		byId('txtMontoExonerado').className = 'inputSinFondo';
		byId('txtMontoExento').readOnly = true;
		byId('txtMontoExento').className = 'inputSinFondo';
		
		byId('trListaDctoPagado').style.display = '';
		
		byId('fieldsetFactura').style.display = 'none';");
		
		// BUSCA LOS DATOS DE LA NOTA DE CREDITO
		$queryNotaCredito = sprintf("SELECT cxc_nc.*,
			(CASE cxc_nc.estadoNotaCredito
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado (No Asignado)'
				WHEN 2 THEN 'Cancelado Parcial'
				WHEN 3 THEN 'Asignado'
			END) AS estado_nota_credito,
			motivo.descripcion AS descripcion_motivo
		FROM pg_motivo motivo
			RIGHT JOIN cj_cc_notacredito cxc_nc ON (motivo.id_motivo = cxc_nc.id_motivo)
		WHERE idNotaCredito = %s",
			valTpDato($idNotaCredito, "int"));
		$rsNotaCredito = mysql_query($queryNotaCredito);
		if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
		
		if ($hddTipoDcto == 4) { // 1 = NORMAL, 2 = DESDE FACTURA, 3 = DESDE NOTA CARGO, 4 = EDITAR, 5 = CONSULTAR
			$objResponse->script("
			byId('lstAplicaLibro').className = 'inputHabilitado';
			byId('txtNumeroControlNotaCredito').className = 'inputHabilitado';");
		} else {
			$objResponse->script("
			byId('txtNumeroControlNotaCredito').readOnly = true;
			
			byId('btnGuardar').style.display = 'none'");
			
			$objResponse->script("
			byId('lstAplicaLibro').onchange = function() {
				selectedOption(this.id,'".$rowNotaCredito['aplicaLibros']."');
			}");
		}
		
		switch($rowNotaCredito['estadoNotaCredito']) {
			case 0 : $classEstatus = "divMsjError"; $imgEstatus = "<img src=\"../img/iconos/no_cancelado.png\">"; break;
			case 1 : $classEstatus = "divMsjInfo"; $imgEstatus = "<img src=\"../img/iconos/cancelado.png\">"; break;
			case 2 : $classEstatus = "divMsjAlerta"; $imgEstatus = "<img src=\"../img/iconos/cancelado_parcial.png\">"; break;
			case 3 : $classEstatus = "divMsjInfo2"; $imgEstatus = "<img src=\"../img/iconos/cancelado.png\">"; break;
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowNotaCredito['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarCliente($rowNotaCredito['idCliente'], $rowNotaCredito['id_empresa'], ""));
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $rowNotaCredito['idDepartamentoNotaCredito'], "2", "", "3", $rowNotaCredito['id_clave_movimiento'], "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"")); 
		
		$objResponse->assign("txtFecha","value",date("d-m-Y",strtotime($rowNotaCredito['fechaNotaCredito'])));
		$objResponse->assign("hddIdNotaCredito","value",$idNotaCredito);
		$objResponse->assign("txtNumeroNotaCredito","value",$rowNotaCredito['numeracion_nota_credito']);
		$objResponse->assign("txtNumeroControlNotaCredito","value",$rowNotaCredito['numeroControl']);
		$objResponse->loadCommands(cargaLstModulo($rowNotaCredito['idDepartamentoNotaCredito'], "selectedOption(this.id,'".$rowNotaCredito['idDepartamentoNotaCredito']."');", true));
		$objResponse->loadCommands(cargaLstVendedor($rowNotaCredito['id_empresa'], $rowNotaCredito['id_empleado_vendedor'], true));
		$objResponse->call("selectedOption","lstAplicaLibro",$rowNotaCredito['aplicaLibros']);
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $classEstatus));
		$objResponse->assign("txtEstatus","value",$rowNotaCredito['estado_nota_credito']);
		$objResponse->call("selectedOption","lstTipoMovimiento",2);
		$objResponse->loadCommands(asignarMotivo($rowNotaCredito['id_motivo'],"Motivo"));
		$objResponse->assign("txtObservacion","value",utf8_encode($rowNotaCredito['observacionesNotaCredito']));
		$objResponse->assign("tdTipoPago","innerHTML","<input type=\"hidden\" id=\"hddTipoPago\" name=\"hddTipoPago\" value=\"".$rowNotaCredito['condicionDePago']."\"/><input type=\"text\" id=\"txtTipoPago\" name=\"txtTipoPago\" class=\"divMsjInfo2\" readonly=\"readonly\" size=\"20\" style=\"text-align:center\" value=\"".(($rowNotaCredito['condicionDePago'] == 0) ? "CRÉDITO" : "CONTADO")."\"/>");
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id,'".(2)."');
		}");
		
		$objResponse->script("
		byId('lstClaveMovimiento').className = 'inputInicial';
		byId('lstClaveMovimiento').onchange = function() {
			selectedOption(this.id,'".($rowNotaCredito['id_clave_movimiento'])."');
		}");
		
		switch ($rowNotaCredito['idDepartamentoNotaCredito']) {
			case 0 : // REPUESTOS
				$aVerDcto = sprintf("verVentana('../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
					$rowNotaCredito['idNotaCredito']);
				break;
			case 1 : // SERVICIOS
				$aVerDcto = sprintf("verVentana('../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
					$rowNotaCredito['idNotaCredito']);
				break;
			case 2 : // VEHICULOS
				$aVerDcto = sprintf("verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
					$rowNotaCredito['idNotaCredito']);
				break;
			case 3 : // ADMINISTRACION
				$aVerDcto = sprintf("verVentana('../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
					$rowNotaCredito['idNotaCredito']);
				break;
		}
		
		$objResponse->script("
		byId('btnNotaCreditoPDF').style.display = '';
		byId('btnNotaCreditoPDF').onclick = function() { ".$aVerDcto." }");
		
		$objResponse->assign("txtSubTotal","value",number_format($rowNotaCredito['subtotalNotaCredito'], 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowNotaCredito['subtotal_descuento'], 2, ".", ","));
		$objResponse->assign("txtFlete","value",number_format($rowNotaCredito['fletesNotaCredito'], 2, ".", ","));
		$objResponse->assign("txtMontoExento","value",number_format($rowNotaCredito['montoExentoCredito'], 2, ".", ","));
		$objResponse->assign("txtMontoExonerado","value",number_format($rowNotaCredito['montoExoneradoCredito'], 2, ".", ","));
		$objResponse->assign("txtTotalNotaCredito","value",number_format($rowNotaCredito['montoNetoNotaCredito'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowNotaCredito['saldoNotaCredito'], 2, ".", ","));
		
		// CARGA LOS IMPUESTOS 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
		/*$queryIva = sprintf("
		SELECT 
			cxc_nc.idNotaCredito,
			cxc_nc.baseimponibleNotaCredito AS base_imponible,
			cxc_nc.ivaNotaCredito AS subtotal_iva,
			(SELECT iva.idIva FROM pg_iva iva WHERE estado = 1 AND tipo IN (6) AND activo = 1) AS id_iva,
			cxc_nc.porcentajeIvaNotaCredito AS iva,
			(SELECT iva.observacion FROM pg_iva iva WHERE estado = 1 AND tipo IN (6) AND activo = 1) AS observacion,
			(SELECT IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE estado = 1 AND tipo IN (6) AND activo = 1) AS lujo
		FROM cj_cc_notacredito cxc_nc
		WHERE cxc_nc.idNotaCredito = %s
			AND cxc_nc.ivaNotaCredito > 0
		
		UNION
		
		SELECT 
			cxc_nc.idNotaCredito,
			NULL AS base_imponible,
			cxc_nc.ivaLujoNotaCredito AS subtotal_iva,
			(SELECT iva.idIva FROM pg_iva iva WHERE estado = 1 AND tipo IN (2) AND activo = 1) AS id_iva,
			cxc_nc.porcentajeIvaDeLujoNotaCredito AS iva,
			(SELECT iva.observacion FROM pg_iva iva WHERE estado = 1 AND tipo IN (2) AND activo = 1) AS observacion,
			(SELECT IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE estado = 1 AND tipo IN (2) AND activo = 1) AS lujo
		FROM cj_cc_notacredito cxc_nc
		WHERE cxc_nc.idNotaCredito = %s
			AND cxc_nc.ivaLujoNotaCredito > 0
		
		ORDER BY 1",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idNotaCredito, "int"));*/
		$queryIva = sprintf("SELECT 
			cxc_nc_iva.id_nota_credito_iva,
			cxc_nc_iva.id_nota_credito,
			cxc_nc_iva.base_imponible,
			cxc_nc_iva.subtotal_iva,
			cxc_nc_iva.id_iva,
			cxc_nc_iva.iva,
			cxc_nc_iva.lujo,
			iva.observacion
		FROM cj_cc_nota_credito_iva cxc_nc_iva
			INNER JOIN pg_iva iva ON (cxc_nc_iva.id_iva = iva.idIva)
		WHERE cxc_nc_iva.id_nota_credito = %s;",
			valTpDato($idNotaCredito, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$indice = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$indice++;
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
						"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trNetoOrden').before(elemento);",
				$indice,
					$indice, utf8_encode($rowIva['observacion']),
						$indice, $indice, $rowIva['id_iva'],
						$indice, $indice, $rowIva['lujo'],
						$indice,
					$indice, $indice, number_format(round($rowIva['base_imponible'],2), 2, ".", ","),
					$indice, $indice, $rowIva['iva'], "%",
					$indice, $indice, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
				
				$indice));
		}
		
		// BUSCA LOS DOCUMENTOS PAGADOS
		$query = sprintf("SELECT
			cxc_pago.idPago AS id_pago,
			cxc_pago.id_factura,
			NULL AS id_nota_cargo,
			NULL AS id_anticipo,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
			cxc_pago.estatus
		FROM an_pagos cxc_pago
		WHERE cxc_pago.formaPago IN (8)
			AND cxc_pago.numeroDocumento = %s
		
		UNION
		
		SELECT
			cxc_pago.idPago,
			cxc_pago.id_factura,
			NULL AS id_nota_cargo,
			NULL AS id_anticipo,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
			cxc_pago.estatus
		FROM sa_iv_pagos cxc_pago
		WHERE cxc_pago.formaPago IN (8)
			AND cxc_pago.numeroDocumento = %s
		
		UNION
		
		SELECT
			cxc_pago.id_det_nota_cargo,
			NULL AS id_factura,
			cxc_pago.idNotaCargo,
			NULL AS id_anticipo,
			cxc_pago.idCaja,
			cxc_pago.monto_pago,
			cxc_pago.estatus
		FROM cj_det_nota_cargo cxc_pago
		WHERE cxc_pago.idFormaPago IN (8)
			AND cxc_pago.numeroDocumento = %s
		
		UNION
		
		SELECT
			cxc_pago.idDetalleAnticipo,
			NULL AS id_factura,
			NULL AS idNotaCargo,
			cxc_pago.idAnticipo,
			cxc_pago.idCaja,
			cxc_pago.montoDetalleAnticipo,
			cxc_pago.estatus
		FROM cj_cc_detalleanticipo cxc_pago
		WHERE cxc_pago.id_forma_pago IN (8)
			AND cxc_pago.numeroControlDetalleAnticipo = %s;",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idNotaCredito, "int"),
			valTpDato($idNotaCredito, "int"),
			valTpDato($idNotaCredito, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemDctoPagado($contFila, $row['id_pago'], $row['id_factura'], $row['id_nota_cargo'], $row['id_anticipo'], $row['idCaja']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
			
			// SUMA LOS PAGOS
			$txtTotalDctoPagado += (in_array($row['estatus'],array(1))) ? $row['montoPagado'] : 0;
		}
		$objResponse->assign("txtTotalDctoPagado","value",number_format($txtTotalDctoPagado, 2, ".", ","));
								
		if ($rowNotaCredito['tipoDocumento'] != "NC"){
			$objResponse->assign("hddIdFacturaNotaCargo","value",$rowNotaCredito['idDocumento']);
			$objResponse->script("byId('fieldsetFactura').style.display = '';");
			
			if ($rowNotaCredito['tipoDocumento'] == "FA"){
				$queryDcto = sprintf("SELECT
					cxc_fact.idFactura,
					cxc_fact.numeroFactura AS numero_documento,
					cxc_fact.numeroControl AS numero_control,
					cxc_fact.fechaRegistroFactura AS fecha_documento,
					modulo.descripcionModulo
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN pg_modulos modulo ON (cxc_fact.idDepartamentoOrigenFactura = modulo.id_modulo)
				WHERE idFactura = %s;",
					valTpDato($rowNotaCredito['idDocumento'], "int"));
				$rsDcto = mysql_query($queryDcto);
				if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowDcto = mysql_fetch_array($rsDcto);
				
				$objResponse->assign("spnFactura","innerHTML","Factura");
				$aVerDcto = "<a id=\"aVerNotaCredito\" href=\"cc_factura_form.php?id=".$rowDcto['idFactura']."&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Factura\"/><a>";
			} else {
				$queryDcto = sprintf("SELECT
					cxc_nd.idNotaCargo,
					cxc_nd.numeroNotaCargo AS numero_documento,
					cxc_nd.numeroControlNotaCargo AS numero_control,
					cxc_nd.fechaRegistroNotaCargo AS fecha_documento,
					modulo.descripcionModulo
				FROM cj_cc_notadecargo cxc_nd
					INNER JOIN pg_modulos modulo ON (cxc_nd.idDepartamentoOrigenNotaCargo = modulo.id_modulo)
				WHERE idNotaCargo = %s;",
					valTpDato($rowNotaCredito['idDocumento'], "int"));
				$rsDcto = mysql_query($queryDcto);
				if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowDcto = mysql_fetch_array($rsDcto);
				
				$objResponse->assign("spnFactura","innerHTML","Nota de Débito");
				$aVerDcto = "<a id=\"aVerNotaCredito\" href=\"cc_nota_debito_form.php?id=".$rowDcto['idNotaCargo']."&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Nota de Débito\"/><a>";
			}
			
			$html .= "<table border=\"0\" cellpadding=\"2\" width=\"100%\">";
			$html .= "<tr align=\"center\" class=\"tituloColumna\">";
				$html .= "<td width=\"22%\">Nro. Documento</td>";
				$html .= "<td width=\"22%\">Nro. Control</td>";
				$html .= "<td width=\"16%\">Fecha</td>";
				$html .= "<td width=\"22%\">Departamento</td>";
				$html .= "<td width=\"18%\">Monto</td>";
			$html .= "</tr>";
			
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$html .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$html .= "<td>";
					$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
					$html .= "<tr align=\"right\">";
                        $html .= "<td>".$aVerDcto."</td>";
						$html .= "<td></td>";
						$html .= "<td width=\"100%\">".$rowDcto['numero_documento']."</td>";
					$html .= "</tr>";
					$html .= "</table>";
				$html .= "</td>";
				$html .= "<td align=\"right\">".$rowDcto['numero_control']."</td>";
				$html .= "<td align=\"center\">".implode("-",array_reverse(explode("-",$rowDcto['fecha_documento'])))."</td>";
				$html .= "<td align=\"center\">".$rowDcto['descripcionModulo']."</td>";
				$html .= "<td align=\"right\">".number_format($rowNotaCredito['montoNetoNotaCredito'],2, ".", ",")."</td>";
			$html .= "</tr>";
			$html .= "</table>";
		}
		
		$objResponse->assign("divFactura","innerHTML",$html);
	} else {
		$objResponse->script("
		byId('aListarFactura').style.display = 'none';
		byId('aListarNotaCargo').style.display = 'none';
		
		byId('aListarEmpresa').style.display = 'none';
		byId('aListarCliente').style.display = 'none';
		
		byId('btnNotaCreditoPDF').style.display = 'none';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');"));
		
		$objResponse->assign("hddTipoDcto","value",$hddTipoDcto);
		
		if ($hddTipoDcto == 2) { // 1 = NORMAL, 2 = DESDE FACTURA, 3 = DESDE NOTA CARGO, 4 = EDITAR, 5 = CONSULTAR
			$objResponse->script("
			byId('tituloPagina').innerHTML = 'Nota de Crédito desde Factura';
			byId('aListarFactura').style.display = '';
			
			/*byId('txtNumeroNotaCredito').readOnly = true;
			byId('lblNroAutomatico').style.display = 'none';*/
			byId('tdTituloNumeroFaNC').style.display = '';
			byId('tdValorNumeroFaNC').style.display = '';
			byId('trMotivo').style.display = 'none';
			
			byId('txtObservacion').className = 'inputHabilitado';
			
			byId('trListaDctoPagado').style.display = 'none';
			
			byId('fieldsetFactura').style.display = 'none';");
		} else if ($hddTipoDcto == 3) { // 1 = NORMAL, 2 = DESDE FACTURA, 3 = DESDE NOTA CARGO, 4 = EDITAR, 5 = CONSULTAR
			$objResponse->script("
			byId('tituloPagina').innerHTML = 'Nota de Crédito desde Nota de Débito';
			byId('aListarNotaCargo').style.display = '';
			
			/*byId('txtNumeroNotaCredito').readOnly = true;
			byId('lblNroAutomatico').style.display = 'none';*/
			byId('tdTituloNumeroFaNC').style.display = '';
			byId('tdValorNumeroFaNC').style.display = '';
			byId('trMotivo').style.display = 'none';
			
			byId('txtObservacion').className = 'inputHabilitado';
			
			byId('trListaDctoPagado').style.display = 'none';
			
			byId('fieldsetFactura').style.display = 'none';");
		} else {
			$objResponse->script("
			byId('tituloPagina').innerHTML = 'Nota de Crédito';
			
			byId('aListarEmpresa').style.display = '';
			byId('txtIdEmpresa').className = 'inputHabilitado';
			byId('aListarCliente').style.display = '';
			byId('txtIdCliente').className = 'inputHabilitado';
			byId('lstAplicaLibro').className = 'inputHabilitado';
			byId('txtFecha').className = 'inputHabilitado';
			byId('txtSubTotal').className = 'inputHabilitado';
			byId('txtSubTotalDescuento').className = 'inputHabilitado';
			byId('txtFlete').className = 'inputHabilitado';
			byId('txtMontoExonerado').className = 'inputHabilitado';
			byId('txtMontoExento').className = 'inputHabilitado';
			byId('aListarMotivo').style.display = '';
			byId('txtIdMotivo').readOnly = false;
			byId('txtIdMotivo').className = 'inputHabilitado';
			byId('txtObservacion').className = 'inputHabilitado';
			
			byId('trListaDctoPagado').style.display = 'none';
			
			byId('fieldsetFactura').style.display = 'none';
			
			jQuery(function($){
				$('#txtFecha').maskInput('99-99-9999',{placeholder:' '});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFecha\",
				dateFormat:\"%d-%m-%Y\",
				cellColorScheme:\"purple\"
			});");
			$objResponse->loadCommands(cargaLstModulo(-1, "validarAsignarDepartamento();"));
			$objResponse->loadCommands(cargaLstVendedor($idEmpresa));
			$objResponse->loadCommands(cargarIvas());
			
			$objResponse->script("
			byId('lstTipoMovimiento').onchange = function() {
				selectedOption('lstTipoMovimiento', '2');
			}");
			$objResponse->call("selectedOption","lstTipoMovimiento",2);
			
			$objResponse->script("xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));");
		}
	}
		
	return $objResponse;
}

function guardarDcto($frmDcto, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idNotaCredito = $frmDcto['hddIdNotaCredito'];
	$idModulo = $frmDcto['lstModulo']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idClaveMovimiento = ($frmDcto['lstClaveMovimiento'] > 0) ? $frmDcto['lstClaveMovimiento'] : "";
	$idTipoPago = $frmDcto['rbtTipoPago'];
	$hddTipoDcto = $frmDcto['hddTipoDcto'];
	
	mysql_query("START TRANSACTION;");
	
	if ($idNotaCredito > 0) {
		// CONSULTA EL NUMERO DE CONTROL  ANTERIOR
		$queryNotaCredito = sprintf("SELECT * FROM cj_cc_notacredito WHERE idNotaCredito = %s;",
			valTpDato($idNotaCredito, "int"));
		$rsNotaCredito = mysql_query($queryNotaCredito);
		if (!$rsNotaCredito) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
		
		$updateSQL = sprintf("UPDATE cj_cc_notacredito SET
			numeroControl = %s,
			aplicaLibros = %s
		WHERE idNotaCredito = %s;",
			valTpDato($frmDcto['txtNumeroControlNotaCredito'], "text"),
			valTpDato($frmDcto['lstAplicaLibro'], "int"), // 0 = No, 1 = Si
			valTpDato($idNotaCredito, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// GUARDA EN LA AUDITORIA EL USUARIO QUE REALIZO LA MODIFICACION
		$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios (tipo_documento, id_documento, id_usuario, fecha_cambio, tabla_editada) 
		VALUES (4, %s, %s, NOW(), %s);",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idUsuario, "int"),
			valTpDato("cj_cc_notacredito", "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idAuditoria = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios_detalle (id_auditoria_cambios, campo_editado, valor_antiguo, valor_nuevo)
		VALUES (%s, %s, %s, %s);",
			valTpDato($idAuditoria, "int"),
			valTpDato("numeroControl", "text"),
			valTpDato($rowNotaCredito['numeroControl'], "text"),
			valTpDato($frmDcto['txtNumeroControlNotaCredito'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if ($frmDcto['lstModulo'] >= 0) {
			if (in_array($idModulo, array(0,1,3))) {
				$idCaja = 2;
			} else if (in_array($idModulo, array(2))) {
				$idCaja = 1;
			}
			
			$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"), $idCaja);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
		
		// VALIDACION DE FACTURAS A DEVOLVER
		if ($hddTipoDcto == 2) { // 1 = NORMAL, 2 = DESDE FACTURA, 3 = DESDE NOTA CARGO, 4 = EDITAR, 5 = CONSULTAR
			$query = sprintf("SELECT *,
				(CASE idDepartamentoOrigenFactura
					WHEN 0 THEN
						(SELECT COUNT(fact_det.id_factura) FROM cj_cc_factura_detalle fact_det
						WHERE fact_det.id_factura = cxc_fact.idFactura)
					WHEN 1 THEN
						(SELECT COUNT(fact_det.id_factura) FROM cj_cc_factura_detalle fact_det
						WHERE fact_det.id_factura = cxc_fact.idFactura)
					WHEN 2 THEN
						IFNULL((SELECT COUNT(fact_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios fact_det_acc
						WHERE fact_det_acc.id_factura = cxc_fact.idFactura),0)
						+
						IFNULL((SELECT COUNT(fact_det_veh.id_factura) FROM cj_cc_factura_detalle_vehiculo fact_det_veh
						WHERE fact_det_veh.id_factura = cxc_fact.idFactura),0)
				END) AS cantidad_detalle
			FROM cj_cc_encabezadofactura cxc_fact
			WHERE cxc_fact.idFactura = %s;",
				valTpDato($frmDcto['hddIdFacturaNotaCargo'], "int"));
			$rs = mysql_query($query);
			if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);			
			
			if ($row['cantidad_detalle'] > 0 && $row['subtotalFactura'] == str_replace(",","",$frmTotalDcto['txtSubTotal'])) {
				return $objResponse->alert('No se puede realizar esta Nota de Crédito');
			}
		}
		
		$txtSubTotalDescuento = (str_replace(",","",$frmTotalDcto['txtSubTotalDescuento']) > 0) ? str_replace(",","",$frmTotalDcto['txtSubTotalDescuento']) : 0;
		$txtDescuento = (100 * $txtSubTotalDescuento) / str_replace(",","",$frmTotalDcto['txtSubTotal']);
		$txtDescuento = ($txtDescuento != "") ? $txtDescuento : 0;
		
		$txtBaseImponibleIva = 0;
		$txtIva = 0;
		$txtSubTotalIva = 0;
		$txtBaseImponibleIvaLujo = 0;
		$txtIvaLujo = 0;
		$txtSubTotalIvaLujo = 0;
		// INSERTA LOS IMPUESTOS DEL PEDIDO
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				switch ($frmTotalDcto['hddLujoIva'.$valor]) {
					case 0 :
						$txtBaseImponibleIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
						$txtIva += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
						$txtSubTotalIva += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
						break;
					case 1 :
						$txtBaseImponibleIvaLujo = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
						$txtIvaLujo += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
						$txtSubTotalIvaLujo += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
						break;
				}
			}
		}
		
		if ($frmDcto['cbxNroAutomatico'] == 1) {
			// NUMERACION DEL DOCUMENTO
			if (in_array($idModulo,array(0,1,2,3,4)) && $frmDcto['lstAplicaLibro'] == 1){
			} else {
				$idNumeraciones = 22; // 22 = Nota Crédito CxC
			}
			
			// NUMERACION DEL DOCUMENTO
			$queryNumeracion = sprintf("SELECT *
			FROM pg_empresa_numeracion emp_num
				INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
			WHERE (emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
											WHERE clave_mov.id_clave_movimiento = %s)
					OR emp_num.id_numeracion = %s)
				AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																								WHERE suc.id_empresa = %s)))
			ORDER BY aplica_sucursales DESC LIMIT 1;",
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($idNumeraciones, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			$rsNumeracion = mysql_query($queryNumeracion);
			if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
			
			$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
			$idNumeraciones = $rowNumeracion['id_numeracion'];
			$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
			
			// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
			$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
			WHERE id_empresa_numeracion = %s;",
				valTpDato($idEmpresaNumeracion, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			if ($frmDcto['lstAplicaLibro'] == 1) {
				$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			}
			
			if (in_array($idModulo,array(0,1,2,3,4)) && $frmDcto['lstAplicaLibro'] == 1){
				$numeroActualControl = $frmDcto['txtNumeroControlNotaCredito'];
			} else {
				$numeroActualControl = $numeroActual;
			}
		} else {
			$numeroActual = $frmDcto['txtNumeroNotaCredito'];
			$numeroActualControl = $frmDcto['txtNumeroControlNotaCredito'];
		}
		
		if (!(strlen($numeroActual) > 0)) {
			return $objResponse->alert("El número de nota de crédito no puede ser nulo");
		}
		
		$txtFechaVencimiento = ($frmDcto['lstTipoPago'] == 1) ? $frmDcto['txtFecha'] : date("d-m-Y",strtotime($frmDcto['txtFecha']) + 2592000);
			
		$txtDiasCreditoCliente = (strtotime($txtFechaVencimiento) - strtotime($frmDcto['txtFecha'])) / 86400;
		
		$insertSQL = sprintf("INSERT INTO cj_cc_notacredito (numeracion_nota_credito, idCliente, montoNetoNotaCredito, saldoNotaCredito, fechaNotaCredito, id_clave_movimiento, id_empleado_vendedor, observacionesNotaCredito, estadoNotaCredito, idDocumento, tipoDocumento, porcentajeIvaNotaCredito, ivaNotaCredito, subtotalNotaCredito, porcentaje_descuento, subtotal_descuento, porcentajeIvaDeLujoNotaCredito, ivaLujoNotaCredito, idDepartamentoNotaCredito, montoExoneradoCredito, montoExentoCredito, aplicaLibros, baseimponibleNotaCredito, fletesNotaCredito, numeroControl, id_empresa, estatus_nota_credito, impreso, id_motivo)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActual, "text"),
			valTpDato($idCliente, "int"),
			valTpDato($frmTotalDcto['txtTotalNotaCredito'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalNotaCredito'], "real_inglesa"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato($idClaveMovimiento, "int"),
			valTpDato($frmDcto['lstVendedor'], "int"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
			valTpDato(0, "int"),
			valTpDato("NC", "text"),
			valTpDato($txtIva, "real_inglesa"),
			valTpDato($txtSubTotalIva, "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($txtDescuento, "real_inglesa"),
			valTpDato($txtSubTotalDescuento, "real_inglesa"),
			valTpDato($txtIvaLujo, "real_inglesa"),
			valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
			valTpDato($idModulo, "int"),
			valTpDato($frmTotalDcto['txtMontoExonerado'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtMontoExento'], "real_inglesa"),
			valTpDato($frmDcto['lstAplicaLibro'], "boolean"),
			valTpDato($txtBaseImponibleIva, "real_inglesa"),
			valTpDato($frmTotalDcto['txtFlete'], "real_inglesa"),
			valTpDato($numeroActualControl, "text"),
			valTpDato($idEmpresa, "int"),
			valTpDato(2, "int"), // 1 = Aprobada, 2 = Aplicada
			valTpDato(0, "boolean"), // 0 = No, 1 = Si
			valTpDato($frmDcto['txtIdMotivo'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idNotaCredito = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	
		$arrayIdDctoContabilidad = array(
			$idNotaCredito,
			$idModulo,
			"NOTA_CREDITO_CXC");
		
		// INSERTA LOS IMPUESTOS DEL PEDIDO
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				if (str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valor]) > 0) {
					$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_iva (id_nota_credito, base_imponible, subtotal_iva, id_iva, iva, lujo)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idNotaCredito, "int"),
						valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"),
						valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['hddLujoIva'.$valor], "boolean"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("NC", "text"),
			valTpDato($idNotaCredito, "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato("4", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		if ($frmDcto['hddIdFacturaNotaCargo'] > 0) {
			if ($hddTipoDcto == 2) { // 1 = NORMAL, 2 = DESDE FACTURA, 3 = DESDE NOTA CARGO, 4 = EDITAR, 5 = CONSULTAR
				$editarFactura = sprintf("UPDATE cj_cc_encabezadofactura SET
					saldoFactura = '0',
					estadoFactura = 1
				WHERE idFactura = %s;",
					valTpDato($frmDcto['hddIdFacturaNotaCargo'], "int"));
				$rsEditarFactura = mysql_query($editarFactura);
				if (!$rsEditarFactura) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$editarNotaCredito = sprintf("UPDATE cj_cc_notacredito SET
					saldoNotaCredito = '0',
					estadoNotaCredito = 3,
					idDocumento = %s,
					tipoDocumento = 'FA'
				WHERE idNotaCredito = %s",
					valTpDato($frmDcto['hddIdFacturaNotaCargo'], "int"),
					valTpDato($idNotaCredito, "int"));
				$rsEditarNotaCredito = mysql_query($editarNotaCredito);
				if (!$rsEditarNotaCredito) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$tabla = (in_array($idModulo,array(0,1))) ? "sa_iv_pagos" : "an_pagos";
					
				$insertPago = sprintf("INSERT INTO ".$tabla." (fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, tomadoEnComprobante, tomadoEnCierre, idCaja)
				VALUES (NOW(), 8, %s, 1, 1, '-', '%s', '%s', '-', 1, 2, 1)",
					$idNotaCredito,
					$frmTotalDcto['txtTotalNotaCredito'],
					$frmDcto['txtValorNumeroFaNC']);
				$rsInsertPago = mysql_query($insertPago);
				if (!$rsInsertPago) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			} else if ($hddTipoDcto == 3) { // 1 = NORMAL, 2 = DESDE FACTURA, 3 = DESDE NOTA CARGO, 4 = EDITAR, 5 = CONSULTAR
				$editarNotaCargo = sprintf("UPDATE cj_cc_notadecargo SET
					saldoNotaCargo = '0',
					estadoNotaCargo = 1
				WHERE idNotaCargo = %s;",
					$frmDcto['hddIdFacturaNotaCargo']);
				$rsEditarNotaCargo = mysql_query($editarNotaCargo);
				if (!$rsEditarNotaCargo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$editarNotaCredito = sprintf("UPDATE cj_cc_notacredito SET
					saldoNotaCredito = '0',
					estadoNotaCredito = 3,
					idDocumento = %s,
					tipoDocumento = 'ND'
				WHERE idNotaCredito = %s",
					$frmDcto['hddIdFacturaNotaCargo'],
					$idNotaCredito);
				$rsEditarNotaCredito = mysql_query($editarNotaCredito);
				if (!$rsEditarNotaCredito) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$insertPago = sprintf("INSERT INTO cj_det_nota_cargo (fechaPago, idFormaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, monto_pago, idNotaCargo, tipoCheque, tomadoEnComprobante, tomadoEnCierre, idCaja)
				VALUES (NOW(), 8, %s, 1, 1, '-', '%s', '%s', '-', 1, 2, 1)",
					$idNotaCredito,
					$frmTotalDcto['txtTotalNotaCredito'],
					$frmDcto['hddIdFacturaNotaCargo']);
				$rsInsertPago = mysql_query($insertPago);
				if (!$rsInsertPago) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Nota de Crédito Guardada con Éxito");
	
	$objResponse->script("window.location.href = 'cc_consulta_nota_credito_list.php';");
	
	if ($frmDcto['lstAplicaLibro'] == 1) {
		switch($idModulo) {
			case 0 : $objResponse->script(sprintf("verVentana('../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s',960,550)", $idNotaCredito)); break;
			case 2 : $objResponse->script(sprintf("verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s',960,550)", $idNotaCredito)); break;
			case 3 : $objResponse->script(sprintf("verVentana('../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s',960,550)", $idNotaCredito)); break;
		}
	}
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA_CREDITO_CXC") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasReSinDetalle")) { generarNotasReSinDetalle($idNotaCredito,"",""); } break;
					//case 1 : if (function_exists("generarNotasCargoRe")) { generarNotasCargoRe($idNotaCredito,"",""); } break;
					//case 2 : if (function_exists("generarNotasCargoVe")) { generarNotasCargoVe($idNotaCredito,"",""); } break;
					//case 3 : if (function_exists("generarComprasAd")) { generarComprasAd($idNotaCredito,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
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
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaFactura($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estadoFactura IN (0,2)");
	
	// VALIDA LAS NOTAS DE CREDITO POR MODULOS DE ERP NO SE PUEDAN DEVOLVER MEDIANTE ESTE METODO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CASE cxc_fact.idDepartamentoOrigenFactura
		WHEN 0 THEN
			(SELECT COUNT(fact_det.id_factura) FROM cj_cc_factura_detalle fact_det
			WHERE fact_det.id_factura = cxc_fact.idFactura)
		WHEN 1 THEN
			(SELECT COUNT(fact_det.id_factura) FROM cj_cc_factura_detalle fact_det
			WHERE fact_det.id_factura = cxc_fact.idFactura)
		WHEN 2 THEN
			IFNULL((SELECT COUNT(fact_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios fact_det_acc
			WHERE fact_det_acc.id_factura = cxc_fact.idFactura),0)
			+
			IFNULL((SELECT COUNT(fact_det_veh.id_factura) FROM cj_cc_factura_detalle_vehiculo fact_det_veh
			WHERE fact_det_veh.id_factura = cxc_fact.idFactura),0)
	END) = 0");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = cxc_fact.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', nombre, apellido) LIKE %s
		OR CONCAT_WS('-', lci, ci) LIKE %s
		OR numeroFactura LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_fact.idFactura,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.numeroFactura,
		cxc_fact.fechaVencimientoFactura,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) as ci_cliente,
		cxc_fact.idDepartamentoOrigenFactura,
		cxc_fact.montoTotalFactura,
		cxc_fact.saldoFactura,
		cxc_fact.estadoFactura,
		cxc_fact.numeroPedido
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id) %s", $sqlBusq);
							
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaFactura", "10%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Origen");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "10%", $pageNum, "fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Vencimiento");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "16%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "36%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "14%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "14%", $pageNum, "montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['idDepartamentoOrigenFactura']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			default : $row['idDepartamentoOrigenFactura'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarFactura('".$row['idFactura']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$imgPedidoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaVencimientoFactura']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoTotalFactura'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFactura(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CC'
	AND ingreso_egreso LIKE 'E'");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM pg_motivo %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "10%", $pageNum, "id_motivo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "54%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "16%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Transacción");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['modulo']) {
			case "CC" :
				$imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>";
				$descripcionModulo = "Cuentas por Cobrar";
				break;
			case "CP" :
				$imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>";
				$descripcionModulo = "Cuentas por Pagar";
				break;
			case "CJ" :
				$descripcionModulo = "Caja"; break;
			case "TE" :
				$imgPedidoModulo = "<img src=\"../img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>";
				$descripcionModulo = "Tesoreria";
				break;
			default : $imgPedidoModulo = ""; $descripcionModulo = $row['modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarMotivo('".$row['id_motivo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($descripcionModulo)."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".(($row['ingreso_egreso'] == "I") ? "Ingreso" : "Egreso")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMotivo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divListaMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotasCargo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estadoNotaCargo IN (0,2)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nd.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = cxc_nd.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = cxc_nd.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', nombre, apellido) LIKE %s
		OR CONCAT_WS('-', lci, ci) LIKE %s
		OR numeroFactura LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_nd.idNotaCargo,
		cxc_nd.fechaRegistroNotaCargo,
		cxc_nd.numeroNotaCargo,
		cxc_nd.fechaVencimientoNotaCargo,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) as ci_cliente,
		cxc_nd.idDepartamentoOrigenNotaCargo,
		cxc_nd.montoTotalNotaCargo,
		cxc_nd.saldoNotaCargo,
		cxc_nd.estadoNotaCargo
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotasCargo", "10%", $pageNum, "fechaRegistroNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Origen");
		$htmlTh .= ordenarCampo("xajax_listaNotasCargo", "10%", $pageNum, "fechaVencimientoNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Vencimiento");
		$htmlTh .= ordenarCampo("xajax_listaNotasCargo", "16%", $pageNum, "numeroNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotasCargo", "36%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaNotasCargo", "14%", $pageNum, "saldoNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listaNotasCargo", "14%", $pageNum, "montoTotalNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['idDepartamentoOrigenNotaCargo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			default : $row['idDepartamentoOrigenNotaCargo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNotaCargo('".$row['idNotaCargo']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$imgPedidoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaRegistroNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaVencimientoNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroNotaCargo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoTotalNotaCargo'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotasCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotasCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotasCargo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotasCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotasCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarFactura");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"asignarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarFactura");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"cargarIvas");
$xajax->register(XAJAX_FUNCTION,"formDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaFactura");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaNotasCargo");

// FUNCION AGREGADA EL 17-09-2012
function actualizarNumeroControl($idEmpresa, $idClaveMovimiento){
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
			
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	return array(true, "");
}

function buscarNumeroControl($idEmpresa, $idClaveMovimiento){
	// VERIFICA VALORES DE CONFIGURACION (Formato Nro. Control)
	$queryConfig401 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 401 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig401 = mysql_query($queryConfig401);
	if (!$rsConfig401) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig401 = mysql_num_rows($rsConfig401);
	$rowConfig401 = mysql_fetch_assoc($rsConfig401);
	
	if (!($totalRowsConfig401 > 0)) return array(false, "No existe un formato de numero de control establecido");
		
	$valor = explode("|",$rowConfig401['valor']);
	$separador = $valor[0];
	$formato = (strlen($separador) > 0) ? explode($separador,$valor[1]) : $valor[1];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	if (strlen($separador) > 0 && isset($formato)) {
		foreach($formato as $indice => $valor) {
			$numeroActualFormato[] = ($indice == count($formato)-1) ? str_pad($numeroActual,strlen($valor),"0",STR_PAD_LEFT) : str_pad(0,strlen($valor),"0",STR_PAD_LEFT);
		}
		$numeroActualFormato = implode($separador, $numeroActualFormato);
	} else {
		$numeroActualFormato = str_pad($numeroActual,strlen($formato),"0",STR_PAD_LEFT);
	}

	return array(true, $numeroActualFormato);
}

function insertarItemDctoPagado($contFila, $idPago = "", $idFactura = "", $idNotaCargo = "", $idAnticipo = "", $idCaja = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoClientePago = "", $txtCuentaClientePago = "", $txtMontoPagado = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("
		SELECT 
			cxc_pago.idPago,
			cxc_pago.id_factura,
			NULL AS idNotaCargo,
			NULL AS idAnticipo,
			cxc_pago.fechaPago,
			cxc_pago.numeroDocumento AS id_documento,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			(CASE cxc_pago.formaPago
				WHEN 8 THEN
					(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
					FROM cj_cc_notacredito cxc_nc
						INNER JOIN pg_motivo motivo ON (cxc_nc.id_motivo = motivo.id_motivo)
					WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			cxc_pago.id_factura AS id_documento_pagado,
			'FA' AS tipo_documento_pagado,
			(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS numero_documento_pagado,
			(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS id_modulo_documento_pagado,
			NULL AS descripcion_motivo_pagado,
			(SELECT cxc_fact.observacionFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS observacion_documento_pagado,
			
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
		WHERE cxc_pago.idPago = %s
			AND cxc_pago.id_factura = %s
			AND cxc_pago.idCaja = %s
			
		UNION
		
		SELECT 
			cxc_pago.idPago,
			cxc_pago.id_factura,
			NULL AS idNotaCargo,
			NULL AS idAnticipo,
			cxc_pago.fechaPago,
			cxc_pago.numeroDocumento AS id_documento,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			(CASE cxc_pago.formaPago
				WHEN 8 THEN
					(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
					FROM cj_cc_notacredito cxc_nc
						INNER JOIN pg_motivo motivo ON (cxc_nc.id_motivo = motivo.id_motivo)
					WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			cxc_pago.id_factura AS id_documento_pagado,
			'FA' AS tipo_documento_pagado,
			(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS numero_documento_pagado,
			(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS id_modulo_documento_pagado,
			NULL AS descripcion_motivo_pagado,
			(SELECT cxc_fact.observacionFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS observacion_documento_pagado,
			
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
		WHERE cxc_pago.idPago = %s
			AND cxc_pago.id_factura = %s
			AND cxc_pago.idCaja = %s
			
		UNION
		
		SELECT
			cxc_pago.id_det_nota_cargo,
			NULL AS id_factura,
			cxc_pago.idNotaCargo,
			NULL AS idAnticipo,
			cxc_pago.fechaPago,
			cxc_pago.numeroDocumento AS id_documento,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			(CASE cxc_pago.idFormaPago
				WHEN 8 THEN
					(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
					FROM cj_cc_notacredito cxc_nc
						INNER JOIN pg_motivo motivo ON (cxc_nc.id_motivo = motivo.id_motivo)
					WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			cxc_pago.idNotaCargo AS id_documento_pagado,
			'ND' AS tipo_documento_pagado,
			(SELECT cxc_nd.numeroNotaCargo FROM cj_cc_notadecargo cxc_nd WHERE cxc_nd.idNotaCargo = cxc_pago.idNotaCargo) AS numero_documento_pagado,
			(SELECT cxc_nd.idDepartamentoOrigenNotaCargo FROM cj_cc_notadecargo cxc_nd WHERE cxc_nd.idNotaCargo = cxc_pago.idNotaCargo) AS id_modulo_documento_pagado,
			
			(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
			FROM cj_cc_notadecargo cxc_nd
				INNER JOIN pg_motivo motivo ON (cxc_nd.id_motivo = motivo.id_motivo)
			WHERE cxc_nd.idNotaCargo = cxc_pago.idNotaCargo) AS descripcion_motivo_pagado,
			
			(SELECT cxc_nd.observacionNotaCargo FROM cj_cc_notadecargo cxc_nd WHERE cxc_nd.idNotaCargo = cxc_pago.idNotaCargo) AS observacion_documento_pagado,
			
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.monto_pago,
			1 AS estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND recibo.idTipoDeDocumento = 2)
		WHERE cxc_pago.id_det_nota_cargo = %s
			AND cxc_pago.idNotaCargo = %s
			AND cxc_pago.idCaja = %s
		
		UNION
		
		SELECT
			cxc_pago.idDetalleAnticipo,
			NULL AS id_factura,
			NULL AS idNotaCargo,
			cxc_pago.idAnticipo,
			cxc_pago.fechaPagoAnticipo,
			cxc_pago.numeroControlDetalleAnticipo AS id_documento,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
			END) AS id_modulo,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
				ELSE
					cxc_pago.numeroControlDetalleAnticipo
			END) AS numero_documento,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 8 THEN
					(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
					FROM cj_cc_notacredito cxc_nc
						INNER JOIN pg_motivo motivo ON (cxc_nc.id_motivo = motivo.id_motivo)
					WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			cxc_pago.idAnticipo AS id_documento_pagado,
			'AN' AS tipo_documento_pagado,
			(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.idAnticipo) AS numero_documento_pagado,
			(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.idAnticipo) AS id_modulo_documento_pagado,
			NULL AS descripcion_motivo_pagado,
			(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.idAnticipo) AS observacion_documento_pagado,
			
			cxc_pago.bancoClienteDetalleAnticipo,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numeroCuentaCliente AS numero_cuenta_cliente,
			cxc_pago.bancoCompaniaDetalleAnticipo,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.numeroCuentaCompania,
			cxc_pago.montoDetalleAnticipo,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS numeroComprobante
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
			INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoClienteDetalleAnticipo = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion)
		WHERE cxc_pago.idDetalleAnticipo = %s
			AND cxc_pago.idAnticipo = %s
			AND cxc_pago.idCaja;",
			valTpDato($idPago, "int"),
			valTpDato($idFactura, "int"),
			valTpDato($idCaja, "int"),
			valTpDato($idPago, "int"),
			valTpDato($idFactura, "int"),
			valTpDato($idCaja, "int"),
			valTpDato($idPago, "int"),
			valTpDato($idNotaCargo, "int"),
			valTpDato($idCaja, "int"),
			valTpDato($idPago, "int"),
			valTpDato($idAnticipo, "int"),
			valTpDato($idCaja, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	} else {
		$cbxItm = sprintf("<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" value=\"%s\"/>",
			$contFila);
	}
	
	$classMontoPago = "";
	if ($totalRows > 0 && $row['estatus'] == 2) {
		$classMontoPago = "class=\"divMsjAlerta\"";
	} else if ($totalRows > 0 && $row['estatus'] != 1) {
		$classMontoPago = "class=\"divMsjError\"";
	}
	
	$txtFechaPago = ($txtFechaPago == "" && $totalRows > 0) ? $row['fechaPago'] : $txtFechaPago;
	$txtNumeroRecibo = ($txtNumeroRecibo == "" && $totalRows > 0) ? $row['numeroComprobante'] : $txtNumeroRecibo;
	$txtMetodoPago = ($txtMetodoPago == "" && $totalRows > 0) ? $row['tipo_documento_pagado'] : $txtMetodoPago;
	$txtIdNumeroDctoPago = ($txtIdNumeroDctoPago == "" && $totalRows > 0) ? $row['id_documento_pagado'] : $txtIdNumeroDctoPago;
	$txtNumeroDctoPago = ($txtNumeroDctoPago == "" && $totalRows > 0) ? $row['numero_documento_pagado'] : $txtNumeroDctoPago;
	$txtBancoCompaniaPago = ($txtBancoCompaniaPago == "" && $totalRows > 0) ? $row['nombre_banco_empresa'] : $txtBancoCompaniaPago;
	$txtCuentaCompaniaPago = ($txtCuentaCompaniaPago == "" && $totalRows > 0) ?  $row['cuentaEmpresa'] : $txtCuentaCompaniaPago;
	$txtBancoClientePago = ($txtBancoClientePago == "" && $totalRows > 0) ? $row['nombre_banco_cliente'] : $txtBancoClientePago;
	$txtCuentaClientePago = ($txtCuentaClientePago == "" && $totalRows > 0) ?  $row['numero_cuenta_cliente'] : $txtCuentaClientePago;
	$txtCajaPago = ($txtCajaPago == "" && $totalRows > 0) ? $row['nombre_caja'] : $txtCajaPago;
	$txtMontoPagado = ($txtMontoPagado == "" && $totalRows > 0) ? $row['montoPagado'] : $txtMontoPagado;
	$hddEstatusPago = ($hddEstatusPago == "" && $totalRows > 0) ? $row['estatus'] : 1;
	$descripcionMotivo = (strlen($row['descripcion_motivo_pagado']) > 0) ? "<div align=\"left\"><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_motivo_pagado'])."</span></div>" : "";
	$observacionDctoPago = (strlen($row['observacion_documento_pagado']) > 0) ? "<div align=\"left\"><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_documento_pagado'])."</span></div>" : "";
	$estatusPago = "";
	if ($totalRows > 0 && $row['estatus'] == 2) {
		$estatusPago = "<div align=\"center\">PAGO PENDIENTE</div>";
	} else if ($totalRows > 0 && $row['estatus'] != 1) {
		$estatusPago = "<div align=\"center\">PAGO ANULADO</div>";
	}
	$empleadoCreadorPago = (strlen($row['nombre_empleado']) > 0) ? "<span class=\"texto_9px\">Registrado por:</span><br><span class=\"textoNegrita_9px\">".$row['nombre_empleado']."</span>" : "";
	$empleadoAnuladoPago = (strlen($row['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".$row['nombre_empleado_anulado']."<br>(".date("d-m-Y",strtotime($row['fecha_anulado'])).")</span></div>" : "";
	
	switch($row['id_modulo_documento_pagado']) {
		case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
		case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
		case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
		case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
		case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
		default : $imgDctoModulo = $row['id_modulo_documento_pagado'];
	}
	
	if (in_array($row['id_modulo_documento_pagado'],array(2,4))) {
		if ($idFactura > 0 || $idNotaCargo > 0) {
			$aVerReciboAux = sprintf("../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
		} else if ($idAnticipo > 0) {
			$aVerReciboAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
		}
	} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
		if ($idFactura > 0 || $idNotaCargo > 0) {
			$aVerReciboAux = sprintf("../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
		} else if ($idAnticipo > 0) {
			$aVerReciboAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
		}
	}
	$aVerRecibo = (strlen($aVerReciboAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerReciboAux."\', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>" : "";
	
	if ($idFactura > 0) {
		$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_factura_form.php?id=%s&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Factura\"/><a>",
			$txtIdNumeroDctoPago);
		switch ($row['id_modulo_documento_pagado']) {
			case 0 : // REPUESTOS
				$aVerDctoAux = sprintf("../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
			case 1 : // SERVICIOS
				$aVerDctoAux = sprintf("../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
			case 2 : // VEHICULOS
				$aVerDctoAux = sprintf("../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
			case 3 : // ADMINISTRACION
				$aVerDctoAux = sprintf("../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
			case 4 : // ALQUILER
				$aVerDctoAux = sprintf("../alquiler/reportes/al_factura_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
		}
		$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>" : "";
	} else if ($idNotaCargo > 0) {
		$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_nota_debito_form.php?id=%s&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Nota de Débito\"/><a>",
			$txtIdNumeroDctoPago);
		$aVerDcto .= sprintf("<a href=\"javascript:verVentana(\'../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Débito PDF\"/></a>",
			$txtIdNumeroDctoPago);
	} else if ($idAnticipo > 0) {
		$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_anticipo_form.php?id=%s&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Anticipo\"/><a>",
			$txtIdNumeroDctoPago);
		if (in_array($row['id_modulo_documento_pagado'],array(2,4))) {
			$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s", $txtIdNumeroDctoPago);
		} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
			$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s", $txtIdNumeroDctoPago);
		}
		$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo Anticipo PDF\"/></a>" : "";
	}
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieDctoPagado').before('".
		"<tr id=\"trItmDctoPagado:%s\" align=\"left\" class=\"textoGris_11px %s\" height=\"24\">".
			"<td align=\"center\" title=\"trItmDctoPagado:%s\">%s".
				"<input type=\"checkbox\" id=\"cbx4\" name=\"cbx4[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmDctoPago:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtFechaPago%s\" name=\"txtFechaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr align=\"right\"><td>%s</td><td width=\"%s\">%s</td></tr></table></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtMetodoPago%s\" name=\"txtMetodoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr><td nowrap=\"nowrap\">%s</td><td>%s</td><td><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr></table>".
				"%s".
				"%s".
				"%s</td>".
			"<td %s><input type=\"text\" id=\"txtBancoClientePago%s\" name=\"txtBancoClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaClientePago%s\" name=\"txtCuentaClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtCajaPago%s\" name=\"txtCajaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtMontoPagado%s\" name=\"txtMontoPagado%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusPago%s\" name=\"hddEstatusPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $cbxItm,
				$contFila,
			$contFila, $contFila,
			$classMontoPago, $contFila, $contFila, utf8_encode(date("d-m-Y", strtotime($txtFechaPago))),
				$empleadoCreadorPago,
			$classMontoPago, "100%", $aVerRecibo, "100%", $txtNumeroRecibo,
			$classMontoPago, $contFila, $contFila, ($txtMetodoPago),
				$txtMetodoPagoConcepto,
				$estatusPago,
			$classMontoPago, "100%", $aVerDcto, $imgDctoModulo, $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
				$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				$descripcionMotivo,
				preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$observacionDctoPago)))),
				$empleadoAnuladoPago,
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoClientePago),
				$contFila, $contFila, utf8_encode($txtCuentaClientePago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
				$contFila, $contFila, utf8_encode($txtCuentaCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCajaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode(number_format($txtMontoPagado, 2, ".", ",")),
				$contFila, $contFila, $idPago,
				$contFila, $contFila, $hddEstatusPago);
	
	return array(true, $htmlItmPie, $contFila);
}

function validarAperturaCaja($idEmpresa, $fecha, $idCaja) {
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
	$queryCierreCaja = sprintf("
	SELECT fechaAperturaCaja FROM an_apertura
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND idCaja = %s
		AND id_empresa = %s
		
	UNION
	
	SELECT fechaAperturaCaja FROM sa_iv_apertura
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND idCaja = %s
		AND id_empresa = %s;",
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idCaja, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idCaja, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
	
	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("
		SELECT * FROM an_apertura
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND idCaja = %s
			AND id_empresa = %s
			
		UNION
		
		SELECT * FROM sa_iv_apertura
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND idCaja = %s
			AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idCaja, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idCaja, "int"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);
		
		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}
?>