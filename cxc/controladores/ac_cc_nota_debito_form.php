<?php


function asignarClaveMovimiento($idClaveMovimiento, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento
	WHERE id_clave_movimiento = %s;",
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
	
	if ($frmDcto['lstModulo'] >= 0) {
		if (in_array($idModulo, array(0,1,3))) {
			$idCaja = 2;
		} else if (in_array($idModulo, array(2))) {
			$idCaja = 1;
		}
		
		$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"), $idCaja);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	if ($frmDcto['cbxNroAutomatico'] == 1) {
		$objResponse->script("
		byId('txtNumeroNotaCargo').readOnly = true;
		byId('txtNumeroNotaCargo').className = 'inputInicial';
		byId('txtNumeroControlNotaCargo').readOnly = true;
		byId('txtNumeroControlNotaCargo').className = 'inputInicial';");
		
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
			
			$objResponse->assign("txtNumeroNotaCargo","value","");
			$objResponse->assign("txtNumeroControlNotaCargo","value","");
			$objResponse->assign("txtFecha","value","");
		}
	} else {
		$objResponse->script("
		byId('txtNumeroNotaCargo').readOnly = false;
		byId('txtNumeroNotaCargo').className = 'inputHabilitado';
		byId('txtNumeroControlNotaCargo').readOnly = false;
		byId('txtNumeroControlNotaCargo').className = 'inputHabilitado';");
		
		$objResponse->assign("txtNumeroControlNotaCargo","value","");
		
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

function asignarMotivo($idMotivo, $nombreObjeto, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_motivo
	WHERE id_motivo = %s
		AND modulo LIKE 'CC'
		AND ingreso_egreso LIKE 'I';",
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

function buscarCliente($frmBuscarCliente, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
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

function cargaLstSumarPagoItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$array = array(
		"" => array("abrev" => "-", "descripcion" => "-"),
		"1" => array("abrev" => "C", "descripcion" => "Pago de Contado"),
		"2" => array("abrev" => "T", "descripcion" => "Trade In"));
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:40px\">";
	foreach ($array as $indice => $valor) {
		$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
		$html .= "<optgroup label=\"".utf8_encode($valor['descripcion'])."\">";
			$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$indice."\">".($valor['abrev'])."</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	
	return $html;
}

function formDcto($idNotaCargo, $acc){
	$objResponse = new xajaxResponse();
	
	if ($idNotaCargo > 0) {
		$objResponse->script("
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		byId('txtIdCliente').readOnly = true;
		byId('txtNumeroNotaCargo').readOnly = true;
		byId('lblNroAutomatico').style.display = 'none';
		byId('txtFecha').readOnly = true;
		byId('aListarMotivo').style.display = 'none';
		byId('txtIdMotivo').readOnly = true;
		byId('txtIdMotivo').className = 'inputInicial';
		byId('txtObservacion').readOnly = true;
		
		byId('btnNotaCargoPDF').style.display = 'none';
		byId('btnReciboPagoPDF').style.display = 'none';
		
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
		
		byId('trListaPagoDcto').style.display = '';");
		
		if ($acc == 0) { // 0 = CONSULTAR, 1 = EDITAR
			$objResponse->script("
			byId('txtNumeroControlNotaCargo').readOnly = true;
			
			byId('btnGuardar').style.display = 'none'");
		} else if ($acc == 1) {
			$objResponse->script("
			byId('txtNumeroControlNotaCargo').className = 'inputHabilitado';");
		}
		
		$queryNotaCargo = sprintf("SELECT cxc_nd.*,
			(CASE cxc_nd.estadoNotaCargo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_nota_cargo,
			motivo.descripcion AS descripcion_motivo
		FROM pg_motivo motivo
			RIGHT JOIN cj_cc_notadecargo cxc_nd ON (motivo.id_motivo = cxc_nd.id_motivo)
		WHERE idNotaCargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsNotaCargo = mysql_query($queryNotaCargo);
		if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCargo = mysql_fetch_array($rsNotaCargo);
		
		switch($rowNotaCargo['estadoNotaCargo']) {
			case 0 : $classEstatus = "divMsjError"; $imgEstatus = "<img src=\"../img/iconos/no_cancelado.png\">"; break;
			case 1 : $classEstatus = "divMsjInfo"; $imgEstatus = "<img src=\"../img/iconos/cancelado.png\">"; break;
			case 2 : $classEstatus = "divMsjAlerta"; $imgEstatus = "<img src=\"../img/iconos/cancelado_parcial.png\">"; break;
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowNotaCargo['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarCliente($rowNotaCargo['idCliente'], $rowNotaCargo['id_empresa'], ""));
		
		$objResponse->assign("txtFecha","value",date("d-m-Y",strtotime($rowNotaCargo['fechaRegistroNotaCargo'])));
		$objResponse->assign("hddIdNotaCargo","value",$idNotaCargo);
		$objResponse->assign("txtNumeroNotaCargo","value",$rowNotaCargo['numeroNotaCargo']);
		$objResponse->assign("txtNumeroControlNotaCargo","value",$rowNotaCargo['numeroControlNotaCargo']);
		$objResponse->loadCommands(cargaLstModulo($rowNotaCargo['idDepartamentoOrigenNotaCargo'], "selectedOption(this.id,'".$rowNotaCargo['idDepartamentoOrigenNotaCargo']."');", true));
		$objResponse->call("selectedOption","lstAplicaLibro",$rowNotaCargo['aplicaLibros']);
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $classEstatus));
		$objResponse->assign("txtEstatus","value",$rowNotaCargo['estado_nota_cargo']);
		$objResponse->loadCommands(asignarMotivo($rowNotaCargo['id_motivo'],"Motivo"));
		$objResponse->assign("txtObservacion","value",utf8_encode($rowNotaCargo['observacionNotaCargo']));
		$objResponse->assign("tdTipoPago","innerHTML","<input type=\"hidden\" id=\"hddTipoPago\" name=\"hddTipoPago\" value=\"".$rowNotaCargo['tipoNotaCargo']."\"/><input type=\"text\" id=\"txtTipoPago\" name=\"txtTipoPago\" class=\"divMsjInfo2\" readonly=\"readonly\" size=\"20\" style=\"text-align:center\" value=\"".(($rowNotaCargo['tipoNotaCargo'] == 0) ? "CRÉDITO" : "CONTADO")."\"/>");
		
		$objResponse->script("
		byId('lstAplicaLibro').onchange = function() {
			selectedOption(this.id,'".$rowNotaCargo['aplicaLibros']."');
		}");
		
		$aVerDcto = sprintf("verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);",
			$idNotaCargo);
		
		$objResponse->script("
		byId('btnNotaCargoPDF').style.display = '';
		byId('btnNotaCargoPDF').onclick = function() { ".$aVerDcto." }");
		
		$objResponse->assign("txtSubTotal","value",number_format($rowNotaCargo['subtotalNotaCargo'],2,".",","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowNotaCargo['descuentoNotaCargo'],2,".",","));
		$objResponse->assign("txtFlete","value",number_format($rowNotaCargo['fletesNotaCargo'],2,".",","));
		$objResponse->assign("txtBaseImponible","value",number_format($rowNotaCargo['baseImponibleNotaCargo'],2,".",","));
		$objResponse->assign("txtMontoExento","value",number_format($rowNotaCargo['montoExentoNotaCargo'],2,".",","));
		$objResponse->assign("txtMontoExonerado","value",number_format($rowNotaCargo['montoExoneradoNotaCargo'],2,".",","));
		$objResponse->assign("txtTotalNotaCargo","value",number_format($rowNotaCargo['montoTotalNotaCargo'],2,".",","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowNotaCargo['saldoNotaCargo'], 2, ".", ","));
		
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
		
		// BUSCA LOS PAGOS DEL DOCUMENTO
		$queryPago = sprintf("SELECT 
			cxc_pago.id_det_nota_cargo,
			cxc_pago.idNotaCargo,
			cxc_pago.fechaPago,
			cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
			cxc_pago.numeroDocumento AS id_documento,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo_documento_pago,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.monto_pago,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		WHERE cxc_pago.idNotaCargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsPago = mysql_query($queryPago);
		if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPago = mysql_num_rows($rsPago);
		while ($rowPago = mysql_fetch_assoc($rsPago)) {
			$Result1 = insertarItemMetodoPago($contFila, $rowPago['id_det_nota_cargo'], $rowPago['idNotaCargo'], $rowPago['idCaja']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
			
			// SUMA LOS PAGOS
			$txtTotalPago += ($rowPago['estatus'] == 1) ? $rowPago['monto_pago'] : 0;
		}
		$objResponse->assign("txtTotalPago","value",number_format($txtTotalPago, 2, ".", ","));
		
		if ($totalRowsPago > 0) {
			if (in_array($rowFactura['idDepartamentoOrigenFactura'],array(2,4))) {
				$aVerPago = sprintf("verVentana('../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idTpDcto=2&id=%s', 960, 550);", $idNotaCargo);
			} else if (in_array($rowFactura['idDepartamentoOrigenFactura'],array(0,1,3))) {
				$aVerPago = sprintf("verVentana('../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idTpDcto=2&id=%s', 960, 550);", $idNotaCargo);
			}
			
			$objResponse->script("
			byId('btnReciboPagoPDF').style.display = '';
			byId('btnReciboPagoPDF').onclick = function() { ".$aVerPago." }");
		}
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
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
		
		byId('btnNotaCargoPDF').style.display = 'none';
		byId('btnReciboPagoPDF').style.display = 'none';
		
		byId('trListaPagoDcto').style.display = 'none';
		
		jQuery(function($){
			$('#txtFecha').maskInput('99-99-9999',{placeholder:' '});
		});
		
		new JsDatePick({
			useMode:2,
			target:\"txtFecha\",
			dateFormat:\"%d-%m-%Y\",
			cellColorScheme:\"purple\"
		});");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');"));
		
		$objResponse->loadCommands(cargaLstModulo(-1, "xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));"));
		$objResponse->loadCommands(cargaLstVendedor($idEmpresa));
		$objResponse->loadCommands(cargarIvas());
	}
	
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
					$contFila, $contFila, $rowIva['id_iva'],
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

function guardarDcto($frmDcto, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idNotaCargo = $frmDcto['hddIdNotaCargo'];
	$idModulo = $frmDcto['lstModulo']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	$idTipoPago = $frmDcto['rbtTipoPago'];
	
	mysql_query("START TRANSACTION;");
	
	if ($idNotaCargo > 0) {
		// CONSULTA EL NUMERO DE CONTROL  ANTERIOR
		$selectNotaCargo = sprintf("SELECT * FROM cj_cc_notadecargo WHERE idNotaCargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsNotaCargo = mysql_query($selectNotaCargo);
		if (!$rsNotaCargo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNotaCargo = mysql_fetch_array($rsNotaCargo);
		
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo SET
			numeroControlNotaCargo = %s
		WHERE idNotaCargo = %s;",
			valTpDato($frmDcto['txtNumeroControlNotaCargo'], "text"),
			valTpDato($idNotaCargo, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// GUARDA EN LA AUDITORIA EL USUARIO QUE REALIZO LA MODIFICACION
		$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios (tipo_documento, id_documento, id_usuario, fecha_cambio, tabla_editada) 
		VALUES (2, %s, %s, NOW(), %s);",
			valTpDato($idNotaCargo, "int"),
			valTpDato($idUsuario, "int"),
			valTpDato("cj_cc_notadecargo", "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idAuditoria = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios_detalle (id_auditoria_cambios, campo_editado, valor_antiguo, valor_nuevo)
		VALUES (%s, %s, %s, %s);",
			valTpDato($idAuditoria, "int"),
			valTpDato("numeroControlNotaCargo", "text"),
			valTpDato($rowNotaCargo['numeroControlNotaCargo'], "text"),
			valTpDato($frmDcto['txtNumeroControlNotaCargo'], "text"));
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
		
		$txtSubTotalDescuento = (str_replace(",","",$frmTotalDcto['txtSubTotalDescuento']) > 0) ? str_replace(",","",$frmTotalDcto['txtSubTotalDescuento']) : 0;
		$txtDescuento = ($frmTotalDcto['txtSubTotal'] > 0) ? (100 * $txtSubTotalDescuento) / str_replace(",","",$frmTotalDcto['txtSubTotal']) : 0;
		$txtDescuento = ($txtDescuento != "") ? $txtDescuento : 0;
		
		$txtBaseImponibleIva = 0;
		$txtIva = 0;
		$txtSubTotalIva = 0;
		$txtBaseImponibleIvaLujo = 0;
		$txtIvaLujo = 0;
		$txtSubTotalIvaLujo = 0;
		// INSERTA LOS IMPUESTOS DEL PEDIDO
		if (isset($frmTotalDcto['cbxIva'])) {
			foreach ($frmTotalDcto['cbxIva'] as $indice => $valor) {
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
			if (in_array($idModulo,array(0,1,3)) && $frmDcto['lstAplicaLibro'] == 1){
				$idNumeraciones = 23; // 23 = Nota de Débito Repuestos y Servicios
			} else if (in_array($idModulo,array(2)) && $frmDcto['lstAplicaLibro'] == 1){
				$idNumeraciones = 13; // 13 = Nota de Débito Vehículos
			} else {
				$idNumeraciones = 24; // 24 = Nota de Débito CxC
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
			
			$numeroActualControl = $numeroActual;
		} else {
			$numeroActual = $frmDcto['txtNumeroNotaCargo'];
			$numeroActualControl = $frmDcto['txtNumeroControlNotaCargo'];
		}
		
		$txtFechaVencimiento = ($frmDcto['lstTipoPago'] == 1) ? $frmDcto['txtFecha'] : date("d-m-Y",strtotime($frmDcto['txtFecha']) + 2592000);
			
		$txtDiasCreditoCliente = (strtotime($txtFechaVencimiento) - strtotime($frmDcto['txtFecha'])) / 86400;
		
		$insertSQL = sprintf("INSERT INTO cj_cc_notadecargo (numeroControlNotaCargo, fechaRegistroNotaCargo, numeroNotaCargo, fechaVencimientoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, estadoNotaCargo, observacionNotaCargo, fletesNotaCargo, idCliente, idDepartamentoOrigenNotaCargo, descuentoNotaCargo, baseImponibleNotaCargo, porcentajeIvaNotaCargo, calculoIvaNotaCargo, subtotalNotaCargo, interesesNotaCargo, tipoNotaCargo, base_imponible_iva_lujo, porcentaje_iva_lujo, ivaLujoNotaCargo, diasDeCreditoNotaCargo, montoExentoNotaCargo, montoExoneradoNotaCargo, aplicaLibros, referencia_nota_cargo, id_empresa, id_motivo)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActualControl, "text"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato($numeroActual, "text"),
			valTpDato(date("Y-m-d",strtotime($txtFechaVencimiento)), "date"),
			valTpDato($frmTotalDcto['txtTotalNotaCargo'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalNotaCargo'], "real_inglesa"),
			valTpDato("0", "int"), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmTotalDcto['txtFlete'], "real_inglesa"),
			valTpDato($idCliente, "int"),
			valTpDato($idModulo, "int"),
			valTpDato($txtSubTotalDescuento, "real_inglesa"),
			valTpDato($txtBaseImponibleIva, "real_inglesa"),
			valTpDato($txtIva, "real_inglesa"),
			valTpDato($txtSubTotalIva, "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmDcto['rbtTipoPago'], "int"), // 0 = Credito, 1 = Contado
			valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
			valTpDato($txtIvaLujo, "real_inglesa"),
			valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
			valTpDato($txtDiasCreditoCliente, "int"),
			valTpDato($frmTotalDcto['txtMontoExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtMontoExonerado'], "real_inglesa"),
			valTpDato($frmDcto['lstAplicaLibro'], "boolean"), // 0 = No, 1 = Si
			valTpDato(1, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($frmDcto['txtIdMotivo'], "int"));	
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idNotaCargo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$arrayIdDctoContabilidad = array(
			$idNotaCargo,
			$idModulo,
			"NOTA_CARGO_CXC");
		
		// INSERTA LOS IMPUESTOS DEL PEDIDO
		/*if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				if (str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valor]) > 0) {
					$insertSQL = sprintf("INSERT INTO cj_cc_nota_cargo_iva (id_nota_cargo, base_imponible, subtotal_iva, id_iva, iva, lujo)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
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
		}*/
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("ND", "text"),
			valTpDato($idNotaCargo, "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Nota de Débito Guardada con Éxito");
		
	$objResponse->script("window.location.href = 'cc_consulta_nota_cargo_list.php';");
	
	$objResponse->script(sprintf("verVentana('reportes/cc_nota_cargo_pdf.php?valBusq=%s',960,550)", $idNotaCargo));
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA_CARGO_CXC") {
				$idNotaCargo = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasCargoRe")) { generarNotasCargoRe($idNotaCargo,"",""); } break;
					case 1 : if (function_exists("generarNotasCargoRe")) { generarNotasCargoRe($idNotaCargo,"",""); } break;
					case 2 : if (function_exists("generarNotasCargoVe")) { generarNotasCargoVe($idNotaCargo,"",""); } break;
					//case 3 : if (function_exists("generarComprasAd")) { generarComprasAd($idNotaCargo,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanClienteCxC;
	
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
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
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
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
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

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CC'
	AND ingreso_egreso LIKE 'I'");
	
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

$xajax->register(XAJAX_FUNCTION,"asignarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstSumarPagoItm");
$xajax->register(XAJAX_FUNCTION,"formDcto");
$xajax->register(XAJAX_FUNCTION,"cargarIvas");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");

function insertarItemMetodoPago($contFila, $idPago = "", $idNotaCargo = "", $idCaja = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoClientePago = "", $txtCuentaClientePago = "", $txtMontoPago = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("
		SELECT
			cxc_pago.id_det_nota_cargo,
			cxc_pago.idNotaCargo,
			cxc_pago.fechaPago,
			cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
			IF (cxc_pago.id_cheque IS NOT NULL,
				cxc_pago.id_cheque,
				IF (cxc_pago.id_transferencia IS NOT NULL,
					cxc_pago.id_transferencia,
					cxc_pago.numeroDocumento)) AS id_documento,
			
			cxc_pago.idNotaCargo AS id_documento_pagado,
			(SELECT cxc_nd.numeroNotaCargo FROM cj_cc_notadecargo cxc_nd WHERE cxc_nd.idNotaCargo = cxc_pago.idNotaCargo) AS numero_documento_pagado,
			'ND' AS tipo_documento_pagado,
			
			(CASE cxc_pago.idFormaPago
				WHEN 2 THEN
					IF (cxc_pago.id_cheque IS NOT NULL,
						(SELECT cxc_ch.id_departamento FROM cj_cc_cheque cxc_ch WHERE cxc_ch.id_cheque = cxc_pago.id_cheque),
						NULL)
				WHEN 4 THEN
					IF (cxc_pago.id_transferencia IS NOT NULL,
						(SELECT cxc_tb.id_departamento FROM cj_cc_transferencia cxc_tb WHERE cxc_tb.id_transferencia = cxc_pago.id_transferencia),
						NULL)
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo_documento_pago,
			
			(CASE cxc_pago.idFormaPago
				WHEN 2 THEN
					IF (cxc_pago.id_cheque IS NOT NULL,
						(SELECT cxc_ch.numero_cheque FROM cj_cc_cheque cxc_ch WHERE cxc_ch.id_cheque = cxc_pago.id_cheque),
						cxc_pago.numeroDocumento)
				WHEN 4 THEN
					IF (cxc_pago.id_transferencia IS NOT NULL,
						(SELECT cxc_tb.numero_transferencia FROM cj_cc_transferencia cxc_tb WHERE cxc_tb.id_transferencia = cxc_pago.id_transferencia),
						cxc_pago.numeroDocumento)
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
				WHEN 2 THEN
					IF (cxc_pago.id_cheque IS NOT NULL,
						(SELECT cxc_ch.observacion_cheque FROM cj_cc_cheque cxc_ch WHERE cxc_ch.id_cheque = cxc_pago.id_cheque),
						NULL)
				WHEN 4 THEN
					IF (cxc_pago.id_transferencia IS NOT NULL,
						(SELECT cxc_tb.observacion_transferencia FROM cj_cc_transferencia cxc_tb WHERE cxc_tb.id_transferencia = cxc_pago.id_transferencia),
						NULL)
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			
			(SELECT tipo_tarjeta.descripcionTipoTarjetaCredito
			FROM cj_cc_retencion_punto_pago ret_punto_pago
				INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
				INNER JOIN tipotarjetacredito tipo_tarjeta ON (ret_punto.id_tipo_tarjeta = tipo_tarjeta.idTipoTarjetaCredito)
			WHERE ret_punto_pago.id_pago = cxc_pago.id_det_nota_cargo
				AND ret_punto_pago.id_caja = cxc_pago.idCaja
				AND id_tipo_documento = 2) AS nombre_tarjeta,
			
			cxc_pago.monto_pago,
			cxc_pago.estatus,
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
			AND cxc_pago.idCaja = %s;",
			valTpDato($idPago, "int"),
			valTpDato($idNotaCargo, "int"),
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
	$txtMetodoPago = ($txtMetodoPago == "" && $totalRows > 0) ? $row['nombreFormaPago'] : $txtMetodoPago;
	$txtNombreTarjeta = ($txtNombreTarjeta == "" && $totalRows > 0) ? $row['nombre_tarjeta'] : $txtNombreTarjeta;
	$txtIdNumeroDctoPago = ($txtIdNumeroDctoPago == "" && $totalRows > 0) ? $row['id_documento'] : $txtIdNumeroDctoPago;
	$txtNumeroDctoPago = ($txtNumeroDctoPago == "" && $totalRows > 0) ? $row['numero_documento'] : $txtNumeroDctoPago;
	$txtBancoCompaniaPago = ($txtBancoCompaniaPago == "" && $totalRows > 0) ? $row['nombre_banco_empresa'] : $txtBancoCompaniaPago;
	$txtCuentaCompaniaPago = ($txtCuentaCompaniaPago == "" && $totalRows > 0) ?  $row['cuentaEmpresa'] : $txtCuentaCompaniaPago;
	$txtBancoClientePago = ($txtBancoClientePago == "" && $totalRows > 0) ? $row['nombre_banco_cliente'] : $txtBancoClientePago;
	$txtCuentaClientePago = ($txtCuentaClientePago == "" && $totalRows > 0) ?  $row['numero_cuenta_cliente'] : $txtCuentaClientePago;
	$txtCajaPago = ($txtCajaPago == "" && $totalRows > 0) ? $row['nombre_caja'] : $txtCajaPago;
	$txtMontoPago = ($txtMontoPago == "" && $totalRows > 0) ? $row['monto_pago'] : $txtMontoPago;
	$hddEstatusPago = ($hddEstatusPago == "" && $totalRows > 0) ? $row['estatus'] : 1;
	$descripcionMotivo = (strlen($row['descripcion_motivo']) > 0) ? "<div align=\"left\"><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_motivo'])."</span></div>" : "";
	$observacionDctoPago = (strlen($row['observacion_documento']) > 0) ? "<div align=\"left\"><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_documento'])."</span></div>" : "";
	$estatusPago = "";
	if ($totalRows > 0 && $row['estatus'] == 2) {
		$estatusPago = "<div align=\"center\">PAGO PENDIENTE</div>";
	} else if ($totalRows > 0 && $row['estatus'] != 1) {
		$estatusPago = "<div align=\"center\">PAGO ANULADO</div>";
	}
	$empleadoCreadorPago = (strlen($row['nombre_empleado']) > 0) ? "<span class=\"texto_9px\">Registrado por:</span><br><span class=\"textoNegrita_9px\">".$row['nombre_empleado']."</span>" : "";
	$empleadoAnuladoPago = (strlen($row['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".$row['nombre_empleado_anulado']."<br>(".date("d-m-Y",strtotime($row['fecha_anulado'])).")</span></div>" : "";
	$checkedCondicionMostrar = ($row['id_condicion_mostrar'] > 0) ?  "checked=\"checked\"" : "";
	$checkedMostrarContado = $row['id_mostrar_contado'];
	
	
	if (in_array($row['id_modulo'],array(2,4))) {
		$aVerReciboAux = sprintf("../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
	} else if (in_array($row['id_modulo'],array(0,1,3))) {
		$aVerReciboAux = sprintf("../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
	}
	$aVerRecibo = (strlen($aVerReciboAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerReciboAux."\', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>" : "";
		
	switch($row['id_modulo_documento_pago']) {
		case "" : $imgDctoModulo = ""; break;
		case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
		case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
		case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
		case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
		case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
		default : $imgDctoModulo = $row['id_modulo'];
	}
	
	$txtMetodoPagoConcepto = "";
	switch ($row['idFormaPago']) { // 2 = Cheques, 4 = Transferencia Bancaria, 7 = Anticipo, 8 = Nota Crédito
		case 2 : // 2 = Cheques
			$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_cheque_form.php?id=%s&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Cheque\"/><a>",
				$txtIdNumeroDctoPago);
			if (in_array($row['id_modulo_documento_pago'],array(2,4))) {
				$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=5&id=%s", $txtIdNumeroDctoPago);
			} else if (in_array($row['id_modulo_documento_pago'],array(0,1,3))) {
				$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=5&id=%s", $txtIdNumeroDctoPago);
			}
			$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo Cheque PDF\"/></a>" : "";
			break;
		case 4 : // 4 = Transferencia Bancaria
			$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_transferencia_form.php?id=%s&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Transferencia\"/><a>",
				$txtIdNumeroDctoPago);
			if (in_array($row['id_modulo_documento_pago'],array(2,4))) {
				$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=6&id=%s", $txtIdNumeroDctoPago);
			} else if (in_array($row['id_modulo_documento_pago'],array(0,1,3))) {
				$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=6&id=%s", $txtIdNumeroDctoPago);
			}
			$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo Transferencia PDF\"/></a>" : "";
			break;
		case 7 : // 7 = Anticipo
			$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_anticipo_form.php?id=%s&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Anticipo\"/><a>",
				$txtIdNumeroDctoPago);
			if (in_array($row['id_modulo_documento_pago'],array(2,4))) {
				$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s", $txtIdNumeroDctoPago);
			} else if (in_array($row['id_modulo_documento_pago'],array(0,1,3))) {
				$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s", $txtIdNumeroDctoPago);
			}
			$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo Anticipo PDF\"/></a>" : "";
			
			// BUSCA EL TIPO DEL ANTICIPO
			$queryAnticipo = sprintf("SELECT
				concepto_forma_pago.descripcion
			FROM cj_cc_detalleanticipo det_anticipo
				INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
				INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT';",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
			while($rowAnticipo = mysql_fetch_array($rsAnticipo)) {
				$arrayConceptoAnticipo[] = $rowAnticipo['descripcion'];
			}
			$txtMetodoPagoConcepto = (($totalRowsAnticipo > 0) ? "<br><span class=\"textoNegrita_10px\">(".implode(", ", $arrayConceptoAnticipo).")</span>" : "");
			
			break;
		case 8 : // 8 = Nota Crédito
			$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_nota_credito_form.php?id=%s&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Nota de Crédito\"/><a>",
				$txtIdNumeroDctoPago);
			switch ($row['id_modulo_documento_pago']) {
				case 0 : // REPUESTOS
					$aVerDctoAux = sprintf("../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
				case 1 : // SERVICIOS
					$aVerDctoAux = sprintf("../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
				case 2 : // VEHICULOS
					$aVerDctoAux = sprintf("../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
				case 3 : // ADMINISTRACION
					$aVerDctoAux = sprintf("../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
				case 4 : // ALQUILER
					$aVerDctoAux = sprintf("../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $txtIdNumeroDctoPago); break;
			}
			$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Crédito PDF\"/></a>" : "";
			break;
	}
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPiePago').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\" height=\"24\">".
			"<td align=\"center\" title=\"trItm:%s\">%s".
				"<input type=\"checkbox\" id=\"cbx2\" name=\"cbx2[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"divMsjInfo2\">%s</td>".
			"<td class=\"divMsjInfo\">%s</td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtFechaPago%s\" name=\"txtFechaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr align=\"right\"><td>%s</td><td width=\"%s\">%s</td></tr></table></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtMetodoPago%s\" name=\"txtMetodoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s".
				"%s".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr><td nowrap=\"nowrap\">%s</td><td>%s</td><td width=\"%s\"><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr></table>".
				"%s".
				"%s".
				"%s</td>".
			"<td %s><input type=\"text\" id=\"txtBancoClientePago%s\" name=\"txtBancoClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaClientePago%s\" name=\"txtCuentaClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtCajaPago%s\" name=\"txtCajaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtMontoPago%s\" name=\"txtMontoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusPago%s\" name=\"hddEstatusPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byName('cbxCondicionMostrar%s')[0].onclick = function () {
			xajax_asignarCondicionMostrar('%s', xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byName('lstSumarA%s')[0].onchange = function () {
			xajax_asignarMostrarContado('%s', this.value, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}",
		$contFila, $clase,
			$contFila, $cbxItm,
				$contFila,
			(in_array(idArrayPais,array(3))) ? "<input type=\"checkbox\" id=\"cbxCondicionMostrar\" name=\"cbxCondicionMostrar".$contFila."\" ".$checkedCondicionMostrar." value=\"1\">" : "",
			(in_array(idArrayPais,array(3))) ? cargaLstSumarPagoItm("lstSumarA".$contFila, $checkedMostrarContado) : "",
			$classMontoPago, $contFila, $contFila, utf8_encode(date("d-m-Y", strtotime($txtFechaPago))),
				$empleadoCreadorPago,
			$classMontoPago, "100%", $aVerRecibo, "100%", $txtNumeroRecibo,
			$classMontoPago, $contFila, $contFila, ($txtMetodoPago),
				$txtNombreTarjeta,
				$txtMetodoPagoConcepto,
				$estatusPago,
			$classMontoPago, "100%", $aVerDcto, $imgDctoModulo, "100%", $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
				$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				$descripcionMotivo,
				preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$observacionDctoPago)))),
				$empleadoAnuladoPago,
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoClientePago),
				$contFila, $contFila, utf8_encode($txtCuentaClientePago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
				$contFila, $contFila, utf8_encode($txtCuentaCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCajaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode(number_format($txtMontoPago, 2, ".", ",")),
				$contFila, $contFila, $idPago,
				$contFila, $contFila, $hddEstatusPago,
			
			$contFila,
				$idPago,
			
			$contFila,
				$idPago);
	
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