<?php


function asignarDepartamento($frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = $frmDcto['lstModulo'];
	
	if ($frmDcto['cbxNroAutomatico'] == 1) {
		$objResponse->script("
		byId('txtNumeroAnticipo').readOnly = true;
		byId('txtNumeroAnticipo').className = 'inputInicial';");
	} else {
		$objResponse->script("
		byId('txtNumeroAnticipo').readOnly = false;
		byId('txtNumeroAnticipo').className = 'inputHabilitado';");
	}
	
	if ($frmDcto['lstModulo'] >= 0) {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		
		byId('txtIdCliente').onblur = function() { }");
	} else {
		$objResponse->script("
		byId('txtIdCliente').onblur = function() { xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false'); }");
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
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
	$totalRowsCliente = mysql_num_rows($rsCliente);
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
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",$tdMsjCliente);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarEmpleado($idEmpleado, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	$queryEmpleado = sprintf("SELECT vw_pg_empleado.* FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtIdEmpleado","value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function calcularPagos($frmListaPagos, $frmListaDctoPagado, $frmDcto){
    $objResponse = new xajaxResponse();

    $txtMontoPago = str_replace(",", "", $frmDcto['txtTotalAnticipo']); // Viene con formato 0,000.00	
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmListaDctoPagado['cbx4'];
	if (isset($arrayObj4)) {
		$i = 0;
		foreach ($arrayObj4 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDctoPagado:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDctoPago:".$valor,"innerHTML",$i);
			
			$arrayAnticipo = explode("|",$frmListaDctoPagado['cbxDctoAgregado'][$indice]);
			
			$txtTotalDctoPagadosAnticipo += ($frmListaDctoPagado['hddEstatusPago'.$valor] == 1) ? str_replace(",", "", $frmListaDctoPagado['txtMontoPagado'.$valor]) : 0;
		}
	}
	
    $totalFaltaPorPagar = $txtMontoPago - $txtTotalDctoPagadosAnticipo;
	
    $objResponse->assign("txtTotalDctoPagado","value",number_format($txtTotalDctoPagadosAnticipo, 2, ".", ","));
    $objResponse->assign("txtMontoRestante","value",number_format($totalFaltaPorPagar, 2, ".", ","));
	
	if (count($arrayObj4) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtMontoPago').readOnly = true;
		byId('txtMontoPago').className = 'inputInicial';");
    } else if (!($frmDcto['hddIdCheque'] > 0)) {
		$objResponse->script("
		byId('txtMontoPago').readOnly = false;
		byId('txtMontoPago').className = 'inputHabilitado';");
    }

    return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
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

function formDcto($idAnticipo, $hddTipoDcto){
	$objResponse = new xajaxResponse();
	
	if ($idAnticipo > 0) {
		$objResponse->script("
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		byId('txtIdCliente').readOnly = true;
		byId('txtNumeroAnticipo').readOnly = true;
		byId('txtFecha').readOnly = true;
		byId('txtObservacion').readOnly = true;
		
		byId('btnAnticipoPDF').style.display = 'none';
		
		byId('trListaDctoPagado').style.display = '';");
		
		$queryAnticipo = sprintf("SELECT cxc_ant.*,
			IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, 0) AS saldoAnticipo,
			IF (cxc_ant.estatus = 1, cxc_ant.estadoAnticipo, NULL) AS estadoAnticipo,
			(CASE cxc_ant.estatus
				WHEN 1 THEN
					(CASE cxc_ant.estadoAnticipo
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado (No Asignado)'
						WHEN 2 THEN 'Asignado Parcial'
						WHEN 3 THEN 'Asignado'
						WHEN 4 THEN 'No Cancelado (Asignado)'
					END)
				ELSE
					'Anulado'
			END) AS descripcion_estado_anticipo
		FROM cj_cc_anticipo cxc_ant WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
		$rowAnticipo = mysql_fetch_array($rsAnticipo);
		
		switch($rowAnticipo['estadoAnticipo']) {
			case "" : $classEstatus = "divMsjInfo5"; break;
			case 0 : $classEstatus = "divMsjError"; $imgEstatus = "<img src=\"../img/iconos/no_cancelado.png\">"; break;
			case 1 : $classEstatus = "divMsjInfo"; $imgEstatus = "<img src=\"../img/iconos/cancelado.png\">"; break;
			case 2 : $classEstatus = "divMsjAlerta"; $imgEstatus = "<img src=\"../img/iconos/cancelado_parcial.png\">"; break;
			case 3 : $classEstatus = "divMsjInfo3"; break;
			case 4 : $classEstatus = "divMsjInfo4"; break;
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowAnticipo['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarEmpleado($rowAnticipo['id_empleado_creador'], false));
		$objResponse->loadCommands(asignarCliente($rowAnticipo['idCliente'], $rowAnticipo['id_empresa'], ""));
		
		$objResponse->assign("hddIdAnticipo","value",$idAnticipo);
		$objResponse->assign("txtFecha","value",date("d-m-Y",strtotime($rowAnticipo['fechaAnticipo'])));
		$objResponse->assign("txtNumeroAnticipo","value",$rowAnticipo['numeroAnticipo']);
		$objResponse->loadCommands(cargaLstModulo($rowAnticipo['idDepartamento'], "selectedOption(this.id,'".$rowAnticipo['idDepartamento']."');", true));
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $classEstatus));
		$objResponse->assign("txtEstatus","value",$rowAnticipo['descripcion_estado_anticipo']);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowAnticipo['observacionesAnticipo']));
		
		if (in_array($rowAnticipo['idDepartamento'],array(2,4))) {
			$aVerDcto = sprintf("verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
				$rowAnticipo['idAnticipo']);
		} else if (in_array($rowAnticipo['idDepartamento'],array(0,1,3))) {
			$aVerDcto = sprintf("verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
				$rowAnticipo['idAnticipo']);
		}
		
		$objResponse->script("
		byId('btnAnticipoPDF').style.display = '';
		byId('btnAnticipoPDF').onclick = function() { ".$aVerDcto." }");
		
		$objResponse->assign("txtTotalAnticipo","value",number_format($rowAnticipo['montoNetoAnticipo'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowAnticipo['saldoAnticipo'], 2, ".", ","));
		
		// BUSCA LOS PAGOS DEL DOCUMENTO
		$queryPago = sprintf("
		SELECT 
			cxc_pago.idDetalleAnticipo AS idPago,
			cxc_pago.idAnticipo,
			cxc_pago.fechaPagoAnticipo AS fechaPago,
			cxc_pago.numeroControlDetalleAnticipo AS id_documento,
			(CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
				ELSE
					cxc_pago.numeroControlDetalleAnticipo
			END) AS numero_documento,
			forma_pago.idFormaPago,
			CONCAT_WS(' ', forma_pago.nombreFormaPago, IF(concepto_forma_pago.descripcion IS NOT NULL, CONCAT('(', concepto_forma_pago.descripcion, ')'), NULL)) AS nombreFormaPago,
			cxc_pago.id_concepto,
			cxc_pago.bancoClienteDetalleAnticipo AS bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numeroCuentaCliente AS numero_cuenta_cliente,
			cxc_pago.bancoCompaniaDetalleAnticipo AS bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
			cxc_pago.montoDetalleAnticipo AS montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
			LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoClienteDetalleAnticipo = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		WHERE cxc_pago.idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsPago = mysql_query($queryPago);
		if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPago = mysql_num_rows($rsPago);
		while ($rowPago = mysql_fetch_array($rsPago)) {
			$Result1 = insertarItemMetodoPago($contFila, $rowPago['idPago'], $rowPago['idAnticipo'], $rowPago['idCaja']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj2[] = $contFila;
			}
			
			// SUMA LOS PAGOS
			$txtTotalPago += ($rowPago['estatus'] == 1 && !in_array($rowPago['id_concepto'], array(6,7,8))) ? $rowPago['montoPagado'] : 0;
		}
		$objResponse->assign("txtTotalPago","value",number_format($txtTotalPago, 2, ".", ","));
		
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
		WHERE cxc_pago.formaPago IN (7)
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
		WHERE cxc_pago.formaPago IN (7)
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
		WHERE cxc_pago.idFormaPago IN (7)
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
		WHERE cxc_pago.id_forma_pago IN (7)
			AND cxc_pago.numeroControlDetalleAnticipo = %s;",
			valTpDato($idAnticipo, "int"),
			valTpDato($idAnticipo, "int"),
			valTpDato($idAnticipo, "int"),
			valTpDato($idAnticipo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemDctoPagado($contFila, $row['id_pago'], $row['id_factura'], $row['id_nota_cargo'], $row['id_anticipo'], $row['idCaja']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj4[] = $contFila;
			}
			
			// SUMA LOS PAGOS
			$txtTotalDctoPagado += (in_array($row['estatus'],array(1))) ? $row['montoPagado'] : 0;
		}
		$objResponse->assign("txtTotalDctoPagado","value",number_format($txtTotalDctoPagado, 2, ".", ","));
		
	    $objResponse->script("calcularPagos();");
	} else {
		$objResponse->script("
		byId('aListarEmpresa').style.display = 'none';
		byId('aListarCliente').style.display = 'none';
		
		byId('btnAnticipoPDF').style.display = 'none';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');"));
		
		$objResponse->assign("hddTipoDcto","value",$hddTipoDcto);
		
		$objResponse->script("
		byId('tituloPagina').innerHTML = 'Anticipo';
		
		byId('aListarEmpresa').style.display = '';
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('aListarCliente').style.display = '';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtFecha').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('trListaDctoPagado').style.display = 'none';
		
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
		
		$objResponse->script("xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));");
	}
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstSumarPagoItm");
$xajax->register(XAJAX_FUNCTION,"formDcto");

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

function insertarItemMetodoPago($contFila, $idPago = "", $idAnticipo = "", $idCaja = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoClientePago = "", $txtCuentaClientePago = "", $txtMontoPago = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("
		SELECT 
			cxc_pago.idDetalleAnticipo AS idPago,
			cxc_pago.idAnticipo,
			cxc_pago.fechaPagoAnticipo AS fechaPago,
			cxc_ant.idDepartamento AS id_modulo,
			IF (cxc_pago.id_cheque IS NOT NULL,
				cxc_pago.id_cheque,
				IF (cxc_pago.id_transferencia IS NOT NULL,
					cxc_pago.id_transferencia,
					cxc_pago.numeroControlDetalleAnticipo)) AS id_documento,
			
			cxc_pago.idAnticipo AS id_documento_pagado,
			(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.idAnticipo) AS numero_documento_pagado,
			'AN' AS tipo_documento_pagado,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 2 THEN
					IF (cxc_pago.id_cheque IS NOT NULL,
						(SELECT cxc_ch.id_departamento FROM cj_cc_cheque cxc_ch WHERE cxc_ch.id_cheque = cxc_pago.id_cheque),
						NULL)
				WHEN 4 THEN
					IF (cxc_pago.id_transferencia IS NOT NULL,
						(SELECT cxc_tb.id_departamento FROM cj_cc_transferencia cxc_tb WHERE cxc_tb.id_transferencia = cxc_pago.id_transferencia),
						NULL)
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
			END) AS id_modulo_documento_pago,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 2 THEN
					IF (cxc_pago.id_cheque IS NOT NULL,
						(SELECT cxc_ch.numero_cheque FROM cj_cc_cheque cxc_ch WHERE cxc_ch.id_cheque = cxc_pago.id_cheque),
						cxc_pago.numeroControlDetalleAnticipo)
				WHEN 4 THEN
					IF (cxc_pago.id_transferencia IS NOT NULL,
						(SELECT cxc_tb.numero_transferencia FROM cj_cc_transferencia cxc_tb WHERE cxc_tb.id_transferencia = cxc_pago.id_transferencia),
						cxc_pago.numeroControlDetalleAnticipo)
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
				WHEN 2 THEN
					IF (cxc_pago.id_cheque IS NOT NULL,
						(SELECT cxc_ch.observacion_cheque FROM cj_cc_cheque cxc_ch WHERE cxc_ch.id_cheque = cxc_pago.id_cheque),
						NULL)
				WHEN 4 THEN
					IF (cxc_pago.id_transferencia IS NOT NULL,
						(SELECT cxc_tb.observacion_transferencia FROM cj_cc_transferencia cxc_tb WHERE cxc_tb.id_transferencia = cxc_pago.id_transferencia),
						NULL)
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			CONCAT_WS(' ', forma_pago.nombreFormaPago, IF(concepto_forma_pago.descripcion IS NOT NULL, CONCAT('(', concepto_forma_pago.descripcion, ')'), NULL)) AS nombreFormaPago,
			cxc_pago.id_concepto,
			cxc_pago.bancoClienteDetalleAnticipo AS bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numeroCuentaCliente AS numero_cuenta_cliente,
			cxc_pago.bancoCompaniaDetalleAnticipo AS bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
			
			(SELECT tipo_tarjeta.descripcionTipoTarjetaCredito
			FROM cj_cc_retencion_punto_pago ret_punto_pago
				INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
				INNER JOIN tipotarjetacredito tipo_tarjeta ON (ret_punto.id_tipo_tarjeta = tipo_tarjeta.idTipoTarjetaCredito)
			WHERE ret_punto_pago.id_pago = cxc_pago.idDetalleAnticipo
				AND ret_punto_pago.id_caja = cxc_pago.idCaja
				AND id_tipo_documento = 4) AS nombre_tarjeta,
			
			cxc_pago.montoDetalleAnticipo AS montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.id_condicion_mostrar,
			cxc_pago.id_mostrar_contado,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS numeroComprobante
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
			INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
			LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoClienteDetalleAnticipo = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion)
		WHERE cxc_pago.idDetalleAnticipo = %s
			AND cxc_pago.idAnticipo = %s
			AND caja.idCaja = %s;",
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
	$txtMetodoPago = ($txtMetodoPago == "" && $totalRows > 0) ? $row['nombreFormaPago'] : $txtMetodoPago;
	$txtNombreTarjeta = ($txtNombreTarjeta == "" && $totalRows > 0) ? $row['nombre_tarjeta'] : $txtNombreTarjeta;
	$txtIdNumeroDctoPago = ($txtIdNumeroDctoPago == "" && $totalRows > 0) ? $row['id_documento'] : $txtIdNumeroDctoPago;
	$txtNumeroDctoPago = ($txtNumeroDctoPago == "" && $totalRows > 0) ? $row['numero_documento'] : $txtNumeroDctoPago;
	$txtBancoCompaniaPago = ($txtBancoCompaniaPago == "" && $totalRows > 0) ? $row['nombre_banco_empresa'] : $txtBancoCompaniaPago;
	$txtCuentaCompaniaPago = ($txtCuentaCompaniaPago == "" && $totalRows > 0) ?  $row['cuentaEmpresa'] : $txtCuentaCompaniaPago;
	$txtBancoClientePago = ($txtBancoClientePago == "" && $totalRows > 0) ? $row['nombre_banco_cliente'] : $txtBancoClientePago;
	$txtCuentaClientePago = ($txtCuentaClientePago == "" && $totalRows > 0) ?  $row['numero_cuenta_cliente'] : $txtCuentaClientePago;
	$txtCajaPago = ($txtCajaPago == "" && $totalRows > 0) ? $row['nombre_caja'] : $txtCajaPago;
	$txtMontoPago = ($txtMontoPago == "" && $totalRows > 0) ? $row['montoPagado'] : $txtMontoPago;
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
		$aVerReciboAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
	} else if (in_array($row['id_modulo'],array(0,1,3))) {
		$aVerReciboAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
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
				"<input type=\"checkbox\" id=\"cbx\" name=\"cbx[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
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
			$classMontoPago, $contFila, $contFila, utf8_encode($txtMetodoPago),
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
?>