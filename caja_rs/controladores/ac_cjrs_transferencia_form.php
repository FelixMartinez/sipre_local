<?php


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
		
		$fechaVencimiento = suma_fechas("d-m-Y",date("d-m-Y"),$rowClienteCredito['diascredito']);
		
		$objResponse->assign("txtDiasCreditoCliente","value",$rowClienteCredito['diascredito']);
		$objResponse->assign("txtCreditoCliente","value",number_format($rowClienteCredito['creditodisponible'], 2, ".", ","));
		
		/*$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "0", "1", $idClaveMovimiento, "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""));
		
		$objResponse->script("
		byId('aDesbloquearClaveMovimiento').style.display = '';
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','0','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
		}");*/
	} else {
		$fechaVencimiento = date("d-m-Y");
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", $idClaveMovimiento, "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""));
		
		$objResponse->script("
		byId('aDesbloquearClaveMovimiento').style.display = '';
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','1','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
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

function buscarAnticipo($frmBuscarAnticipo, $frmDcto, $frmListaDctoPagado){
    $objResponse = new xajaxResponse();
	
    // DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS	
    foreach($frmListaDctoPagado['cbxDctoAgregado'] as $key => $valor){
		$arrayAnticipo = explode("|",$valor);
		$hddIdPago = $arrayAnticipo[0];
		if (!($hddIdPago > 0)) {
			$arrayIdAnticipo[] = $arrayAnticipo[2];
		}
    }

    $valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmDcto['txtIdCliente'],
		$frmDcto['lstTipoTransferencia'],
		$frmBuscarAnticipo['lstTipoDcto'],
		implode(",",$arrayIdAnticipo),
		$frmBuscarAnticipo['txtFechaDesde'],
		$frmBuscarAnticipo['txtFechaHasta'],
		$frmBuscarAnticipo['txtCriterioBuscarAnticipo']);
	
	switch ($frmBuscarAnticipo['lstTipoDcto']) {
    	case "FACTURA" : $objResponse->loadCommands(listaFacturaVenta(0, "numeroControl", "DESC", $valBusq)); break;
    	case "NOTA DEBITO" : $objResponse->loadCommands(listaNotaDebito(0, "idNotaCargo", "DESC", $valBusq)); break;
    	case "ANTICIPO" : $objResponse->loadCommands(listaAnticipo(0, "idAnticipo", "DESC", $valBusq)); break;
    	case "ANTICIPO_OTRO" : $objResponse->loadCommands(listaAnticipo(0, "idAnticipo", "DESC", $valBusq)); break;
	}

    return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0,"id","ASC",$valBusq));
	
	return $objResponse;
}

function calcularPagos($txtMontoPago, $frmListaDctoPagado, $frmDcto){
    $objResponse = new xajaxResponse();

    $txtMontoPago = str_replace(",", "", $txtMontoPago); // Viene con formato 0,000.00	
	
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
			
			$txtTotalDctoPagadosAnticipo += str_replace(",", "", $frmListaDctoPagado['txtMontoPagado'.$valor]);
		}
	}
	
    $totalFaltaPorPagar = $txtMontoPago - $txtTotalDctoPagadosAnticipo;
	
    $objResponse->assign("txtTotalDctoPagado","value",number_format($txtTotalDctoPagadosAnticipo, 2, ".", ","));
    $objResponse->assign("txtMontoRestante","value",number_format($totalFaltaPorPagar, 2, ".", ","));
	
	if (count($arrayObj4) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtMontoPago').readOnly = true;
		byId('txtMontoPago').className = 'inputInicial';");
    } else if (!($frmDcto['hddIdTransferencia'] > 0)) {
		$objResponse->script("
		byId('txtMontoPago').readOnly = false;
		byId('txtMontoPago').className = 'inputHabilitado';");
    }

    return $objResponse;
}

function cargaLstBancoCliente($nombreObjeto, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$query = sprintf("SELECT idBanco, nombreBanco FROM bancos WHERE idBanco <> 1 ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected."  value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstBancoCompania($tipoPago = "", $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_cargaLstCuentaCompania(this.value,".$tipoPago.");\"";
	
	$query = sprintf("SELECT idBanco, (SELECT nombreBanco FROM bancos WHERE bancos.idBanco = cuentas.idBanco) AS banco FROM cuentas GROUP BY cuentas.idBanco ORDER BY banco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select name=\"selBancoCompania\" id=\"selBancoCompania\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstCuentaCompania($row["idBanco"], $tipoPago)); }
		
		$html .= "<option ".$selected."  value=\"".$row["idBanco"]."\">".utf8_encode($row["banco"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselBancoCompania","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCuentaCompania($idBanco, $tipoPago, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_cargaLstTarjetaCuenta(this.value,".$tipoPago.");\"";
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas
	WHERE idBanco = %s
		AND estatus = 1",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select name=\"selNumeroCuenta\" id=\"selNumeroCuenta\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idCuentas'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstTarjetaCuenta($row["idCuentas"], $tipoPago)); }
		
		$html .= "<option ".$selected." value=\"".$row["idCuentas"]."\">".utf8_encode($row["numeroCuentaCompania"])."</option>";	
	}
	$html .= "</select>";
	$objResponse->assign("divselNumeroCuenta","innerHTML",$html);
	
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

function cargaLstTarjetaCuenta($idCuenta, $tipoPago, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 5) { // Tarjeta de Crédito
		$query = sprintf("SELECT idTipoTarjetaCredito, descripcionTipoTarjetaCredito FROM tipotarjetacredito 
		WHERE idTipoTarjetaCredito IN (SELECT id_tipo_tarjeta FROM te_retencion_punto
										WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta NOT IN (6))
		ORDER BY descripcionTipoTarjetaCredito",
			valTpDato($idCuenta, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select id=\"tarjeta\" name=\"tarjeta\" class=\"inputHabilitado\" onchange=\"xajax_asignarPorcentajeTarjetaCredito(".$idCuenta.",this.value)\" style=\"width:200px\">";
			$html .= "<option value=\"\">[ Seleccione ]</option>";
		while($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row['idTipoTarjetaCredito'] || $totalRows == 1) ? "selected=\"selected\"" : "";
			if ($totalRows == 1) { $objResponse->loadCommands(asignarPorcentajeTarjetaCredito($idCuenta, $row["idTipoTarjetaCredito"])); }
			
			$html .= "<option ".$selected." value=\"".$row['idTipoTarjetaCredito']."\">".$row['descripcionTipoTarjetaCredito']."</option>";
		}
		$html .= "</select>";
		$objResponse->assign("tdtarjeta","innerHTML",$html);
	} else if ($tipoPago == 6) { // Tarjeta de Debito
		$query = sprintf("SELECT porcentaje_comision FROM te_retencion_punto WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta IN (6);",
			valTpDato($idCuenta,'int'));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
	}
	
	return $objResponse;
}

function cargaLstTipoTransferencia($selId = "", $bloquearObj = false){//si es puerto rico, permitir cambio y uso de tipo de transferencia suplidor
    $objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"asignarTipoTransferencia(this.value);\"";
	
	// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
    $queryConfig403 = sprintf("SELECT *
	FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
    valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
    $rsConfig403 = mysql_query($queryConfig403);
    if (!$rsConfig403) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
    $rowConfig403 = mysql_fetch_assoc($rsConfig403);
    
	$html = "";
    if (in_array($rowConfig403['valor'],array(3))) { // 3 = Puerto Rico
		$array = ($idCajaPpal == 1) ? array("1" => "Cliente", "2" => "Bono Suplidor", "3" => "PND") : array("1" => "Cliente");
		$totalRows = count($array);
		
		$html .= "<select id=\"lstTipoTransferencia\" name=\"lstTipoTransferencia\" ".$class." ".$onChange." style=\"width:99%\">";
        	$html .= "<option value=\"\">[ Seleccione ]</option>";
		foreach ($array as $indice => $valor) {
			$selected = ($selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
			if ($totalRows == 1) { $objResponse->call(asignarTipoTransferencia,$indice); }
			
			$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
		}
        $html .= "</select>";
    } else {
		$array = array("1" => "Cliente");
		$totalRows = count($array);
		
		$html .= "<select id=\"lstTipoTransferencia\" name=\"lstTipoTransferencia\" ".$class." ".$onChange." style=\"width:99%\">";
		foreach ($array as $indice => $valor) {
			$selected = ($selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
		}
        $html .= "</select>";
    }
	
	$objResponse->assign("tdlstTipoTransferencia","innerHTML", $html);

    return $objResponse;
}

function cargaLstTipoDcto($selId = ""){
	$objResponse = new xajaxResponse();
	
	$array = array("FACTURA" => "Factura", "NOTA DEBITO" => "Nota de Débito", "ANTICIPO" => "Anticipo", "ANTICIPO_OTRO" => "Anticipo (Bono, PND)");
	
	$html = "<select id=\"lstTipoDcto\" name=\"lstTipoDcto\" class=\"inputHabilitado\" onchange=\"byId('btnBuscarAnticipo').click();\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoDcto","innerHTML",$html);
	
	return $objResponse;
}

function cargarDcto($idTransferencia){
    $objResponse = new xajaxResponse();
	
	global $idCajaPpal;
    
	if ($idTransferencia > 0) {
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		
		byId('txtObservacionTransferencia').readOnly = true;
		
		byId('divselNumeroCuenta').style.display = '';
		byId('txtNumeroCuenta').style.display = 'none';
		
		byId('txtNumeroCuenta').readOnly = true;
		byId('txtNumeroDctoPago').readOnly = true;
		byId('txtMontoPago').readOnly = true;");
		
		// BUSCA LOS DATOS DEL ANTICIPO
		$queryTransferencia = sprintf("SELECT *,
			IF (tb.estatus = 1, tb.saldo_transferencia, 0) AS saldo_transferencia,
			IF (tb.estatus = 1, tb.estado_transferencia, NULL) AS estado_transferencia,
			(CASE tb.estatus
				WHEN 1 THEN
					(CASE tb.estado_transferencia
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado (No Asignado)'
						WHEN 2 THEN 'Asignado Parcial'
						WHEN 3 THEN 'Asignado'
					END)
				ELSE
					'Anulado'
			END) AS descripcion_estado_transferencia
		FROM cj_cc_transferencia tb WHERE id_transferencia = %s;",
			valTpDato($idTransferencia, "int"));
		$rsTransferencia = mysql_query($queryTransferencia);
		if (!$rsTransferencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTransferencia = mysql_num_rows($rsTransferencia);
		$rowTransferencia = mysql_fetch_assoc($rsTransferencia);
		
		if (!($rowTransferencia['estatus'] == 1)) {
			$objResponse->alert(utf8_encode("La transferencia no puede ser aplicada porque se encuentra anulada."));
			
			return $objResponse->script(sprintf("window.location.href='cjrs_transferencia_list.php';"));
		}
		
		switch($rowTransferencia['estado_transferencia']) {
			case "" : $classEstatus = "divMsjInfo5"; break;
			case 0 : $classEstatus = "divMsjError"; $imgEstatus = "<img src=\"../img/iconos/no_cancelado.png\">"; break;
			case 1 : $classEstatus = "divMsjInfo"; $imgEstatus = "<img src=\"../img/iconos/cancelado.png\">"; break;
			case 2 : $classEstatus = "divMsjAlerta"; $imgEstatus = "<img src=\"../img/iconos/cancelado_parcial.png\">"; break;
			case 3 : $classEstatus = "divMsjInfo3"; break;
			case 4 : $classEstatus = "divMsjInfo4"; break;
		}
		
		$idEmpresa = $rowTransferencia['id_empresa'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "", false));
		$objResponse->loadCommands(asignarEmpleado($rowTransferencia['id_empleado_registro'], false));
		
		$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
		
		$objResponse->assign("hddIdTransferencia","value",$idTransferencia);
		$objResponse->loadCommands(asignarCliente($rowTransferencia['id_cliente']));
		$objResponse->loadCommands(cargaLstTipoTransferencia($rowTransferencia['tipo_transferencia'], true));
		$objResponse->loadCommands(cargaLstModulo($rowTransferencia['id_departamento'], "", true));
		$objResponse->assign("txtFecha","value",date("d-m-Y",strtotime($rowTransferencia['fecha_transferencia'])));
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $classEstatus));
		$objResponse->assign("txtEstatus","value",$rowTransferencia['descripcion_estado_transferencia']);
		$objResponse->assign("txtObservacionTransferencia","value",$rowTransferencia['observacion_transferencia']);
		
		$objResponse->loadCommands(cargaLstBancoCliente("selBancoCliente", $rowTransferencia['id_banco_cliente'], true));
		$objResponse->loadCommands(cargaLstBancoCompania(4, $rowTransferencia['id_banco_compania'], true));
		$objResponse->loadCommands(cargaLstCuentaCompania($rowTransferencia['id_banco_compania'], 4, $rowTransferencia['id_cuenta_compania'], true));
		$objResponse->assign("txtNumeroCuenta","value",$rowTransferencia['cuenta_compania']);
		$objResponse->assign("txtNumeroDctoPago","value",$rowTransferencia['numero_transferencia']);
		$objResponse->assign("txtMontoPago","value",number_format($rowTransferencia['monto_neto_transferencia'], 2, ".", ","));
		
		$objResponse->call(asignarTipoTransferencia,$rowTransferencia['tipo_transferencia']);
		$objResponse->loadCommands(cargaLstTipoDcto());
		
		// CARGA LOS PAGOS EN DONDE SE A APLICADO LA TRANSFERENCIA
		$queryPago = sprintf("SELECT q.*,
			(CASE q.tipoDoc
				WHEN ('FACTURA') THEN
					cxc_fact.fechaRegistroFactura
				WHEN ('NOTA DEBITO') THEN
					cxc_nd.fechaRegistroNotaCargo
				WHEN ('ANTICIPO') THEN
					cxc_ant.fechaAnticipo
			END) AS fechaRegistroFactura,
			
			(CASE q.tipoDoc
				WHEN ('FACTURA') THEN
					cxc_fact.numeroFactura
				WHEN ('NOTA DEBITO') THEN
					cxc_nd.numeroNotaCargo
				WHEN ('ANTICIPO') THEN
					cxc_ant.numeroAnticipo
			END) AS numeroFactura,
			
			(CASE q.tipoDoc
				WHEN ('FACTURA') THEN
					cxc_fact.idDepartamentoOrigenFactura
				WHEN ('NOTA DEBITO') THEN
					cxc_nd.idDepartamentoOrigenNotaCargo
				WHEN ('ANTICIPO') THEN
					cxc_ant.idDepartamento
			END) AS id_modulo,
			
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			
			(CASE q.tipoDoc
				WHEN ('FACTURA') THEN
					cxc_fact.estadoFactura
				WHEN ('NOTA DEBITO') THEN
					cxc_nd.estadoNotaCargo
				WHEN ('ANTICIPO') THEN
					cxc_ant.estadoAnticipo
			END) AS estadoFactura,
			
			(CASE q.tipoDoc
				WHEN ('FACTURA') THEN
					(CASE cxc_fact.estadoFactura
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado'
						WHEN 2 THEN 'Cancelado Parcial'
					END)
				WHEN ('NOTA DEBITO') THEN
					(CASE cxc_nd.estadoNotaCargo
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado'
						WHEN 2 THEN 'Cancelado Parcial'
					END)
				WHEN ('ANTICIPO') THEN
					(CASE cxc_ant.estadoAnticipo
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado (No Asignado)'
						WHEN 2 THEN 'Asignado Parcial'
						WHEN 3 THEN 'Asignado'
						WHEN 4 THEN 'No Cancelado (Asignado)'
					END)
			END) AS descripcion_estado_factura,
			
			(CASE q.tipoDoc
				WHEN ('FACTURA') THEN
					cxc_fact.observacionFactura
				WHEN ('NOTA DEBITO') THEN
					cxc_nd.observacionNotaCargo
				WHEN ('ANTICIPO') THEN
					cxc_ant.observacionesAnticipo
			END) AS observacionFactura,
			
			(CASE q.tipoDoc
				WHEN ('ANTICIPO') THEN
					(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
					FROM cj_cc_detalleanticipo cxc_pago
						INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
					WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
						AND cxc_pago.id_forma_pago IN (11))
			END) AS descripcion_concepto_forma_pago,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM (SELECT
					cxc_pago.idPago,
					'FACTURA' AS tipoDoc,
					cxc_pago.id_factura,
					cxc_pago.fechaPago,
					cxc_pago.formaPago,
					cxc_pago.tipo_transferencia,
					cxc_pago.id_transferencia,
					cxc_pago.montoPagado,
					cxc_pago.estatus,
					cxc_pago.tiempo_registro,
					'an_pagos' AS tabla
				FROM an_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (4)
					AND cxc_pago.estatus IN (1)
					AND cxc_pago.id_transferencia IS NOT NULL
				
				UNION
				
				SELECT
					cxc_pago.idPago,
					'FACTURA' AS tipoDoc,
					cxc_pago.id_factura,
					cxc_pago.fechaPago,
					cxc_pago.formaPago,
					cxc_pago.tipo_transferencia,
					cxc_pago.id_transferencia,
					cxc_pago.montoPagado,
					cxc_pago.estatus,
					cxc_pago.tiempo_registro,
					'sa_iv_pagos' AS tabla
				FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (4)
					AND cxc_pago.estatus IN (1)
					AND cxc_pago.id_transferencia IS NOT NULL
				
				UNION
				
				SELECT
					cxc_pago.id_det_nota_cargo,
					'NOTA DEBITO' AS tipoDoc,
					cxc_pago.idNotaCargo,
					cxc_pago.fechaPago,
					cxc_pago.idFormaPago,
					cxc_pago.tipo_transferencia,
					cxc_pago.id_transferencia,
					cxc_pago.monto_pago,
					cxc_pago.estatus,
					cxc_pago.tiempo_registro,
					'cj_det_nota_cargo' AS tabla
				FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.idFormaPago IN (4)
					AND cxc_pago.estatus IN (1)
					AND cxc_pago.id_transferencia IS NOT NULL
				
				UNION
				
				SELECT
					cxc_pago.idDetalleAnticipo,
					'ANTICIPO' AS tipoDoc,
					cxc_pago.idAnticipo,
					cxc_pago.fechaPagoAnticipo,
					cxc_pago.id_forma_pago,
					cxc_pago.tipo_transferencia,
					cxc_pago.id_transferencia,
					cxc_pago.montoDetalleAnticipo,
					cxc_pago.estatus,
					cxc_pago.tiempo_registro,
					'cj_cc_detalleanticipo' AS tabla
				FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.id_forma_pago IN (4)
					AND cxc_pago.estatus IN (1)
					AND cxc_pago.id_transferencia IS NOT NULL) AS q
			LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (q.id_factura = cxc_fact.idFactura AND q.tipoDoc IN ('FACTURA'))
			LEFT JOIN cj_cc_notadecargo cxc_nd ON (q.id_factura = cxc_nd.idNotaCargo AND q.tipoDoc IN ('NOTA DEBITO'))
			LEFT JOIN cj_cc_anticipo cxc_ant ON (q.id_factura = cxc_ant.idAnticipo AND q.tipoDoc IN ('ANTICIPO'))
			RIGHT JOIN cj_cc_cliente cliente ON ((cxc_fact.idCliente = cliente.id AND q.tipoDoc IN ('FACTURA'))
				OR (cxc_nd.idCliente = cliente.id AND q.tipoDoc IN ('NOTA DEBITO'))
				OR (cxc_ant.idCliente = cliente.id AND q.tipoDoc IN ('ANTICIPO')))
			RIGHT JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON ((cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg AND q.tipoDoc IN ('FACTURA'))
				OR (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg AND q.tipoDoc IN ('NOTA DEBITO'))
				OR (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg AND q.tipoDoc IN ('ANTICIPO')))
		WHERE q.id_transferencia = %s
		ORDER BY q.tiempo_registro ASC;",
			valTpDato($idTransferencia, "int"));
		$rsPago = mysql_query($queryPago);
		if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj4 = NULL;
		while ($rowPago = mysql_fetch_assoc($rsPago)) {
			$Result1 = insertarItemDctoPagado($contFila, $rowPago['idPago'], $rowPago['tabla'], $rowPago['tipoDoc'], $rowPago['id_factura'], $rowPago['id_modulo'], $rowPago['fechaPago'], $rowPago['nombre_empresa'], $rowPago['fechaRegistroFactura'], $rowPago['numeroFactura'], $rowPago['nombre_cliente'], $rowPago['estadoFactura'], $rowPago['descripcion_concepto_forma_pago'], $rowPago['observacionFactura'], $rowPago['descripcion_estado_factura'], $rowPago['montoPagado']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj4[] = $contFila;
			}
		}
		
	    $objResponse->script("calcularPagos();");
	} else {
		$objResponse->script("
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtNumeroCuenta').className = 'inputHabilitado';
		byId('txtNumeroDctoPago').className = 'inputHabilitado';
		byId('txtMontoPago').className = 'inputHabilitado';
		byId('txtObservacionTransferencia').className = 'inputHabilitado';
		
		byId('divselNumeroCuenta').style.display = '';
		byId('txtNumeroCuenta').style.display = 'none';");
		
        $idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "", false));
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts'], false));
		$objResponse->loadCommands(cargaLstTipoTransferencia((($idCajaPpal == 1) ? "" : 1)));
		$objResponse->loadCommands(cargaLstModulo());
    
		$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
		
		$objResponse->loadCommands(cargaLstBancoCliente("selBancoCliente"));
		$objResponse->loadCommands(cargaLstBancoCompania(4));
		$objResponse->call(asignarTipoTransferencia,(($idCajaPpal == 1) ? "" : 1));
		$objResponse->loadCommands(cargaLstTipoDcto());
	}
	
	$objResponse->script("
	cerrarVentana = false;");
	
	return $objResponse;
}

function cargarSaldoDocumentoPagar($tipoDcto, $idDocumento, $frmListaDctoPagado){
    $objResponse = new xajaxResponse();

	//ojo se usa el monto completo porque ya no tiene saldo al agregarse a la factura
	switch ($tipoDcto) {
		case "FACTURA" : 
			$documento = "Factura";
			$hddTipoDocumento = "FACTURA";
			$queryDocumento = sprintf("SELECT
				cxc_fact.numeroFactura AS numeroDocumento,
				IFNULL(cxc_fact.saldoFactura,0) AS saldoDocumento
			FROM cj_cc_encabezadofactura cxc_fact
			WHERE cxc_fact.idFactura = %s",
				valTpDato($idDocumento, "int"));
			break;
		case "NOTA DEBITO" : 
			$documento = "Nota de Débito";
			$hddTipoDocumento = "NOTA DEBITO";
			$queryDocumento = sprintf("SELECT
				cxc_nd.numeroNotaCargo AS numeroDocumento,
				IFNULL(cxc_nd.saldoNotaCargo,0) AS saldoDocumento
			FROM cj_cc_notadecargo cxc_nd
			WHERE cxc_nd.idNotaCargo = %s",
				valTpDato($idDocumento, "int"));
			break;
		case "ANTICIPO" : 
			$documento = "Anticipo";
			$hddTipoDocumento = "ANTICIPO";
			$queryDocumento = sprintf("SELECT
				cxc_ant.numeroAnticipo AS numeroDocumento,
				(IFNULL(cxc_ant.montoNetoAnticipo,0)
					- IFNULL(cxc_ant.totalPagadoAnticipo,0)) AS saldoDocumento
			FROM cj_cc_anticipo cxc_ant
			WHERE cxc_ant.idAnticipo = %s",
				valTpDato($idDocumento, "int"));
			break;
	}
	$rsDocumento = mysql_query($queryDocumento);
	if (!$rsDocumento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDocumento = mysql_fetch_array($rsDocumento);
	
	foreach ($frmListaDctoPagado['cbxDctoAgregado'] as $key => $valor){
		$arrayAnticipo = explode("|",$valor);
		$hddIdPago = $arrayAnticipo[0];
		$tipoDcto = $arrayAnticipo[1];
		$idAnticipo = $arrayAnticipo[2];
		
        if (!($hddIdPago > 0) && $tipoDcto == $hddTipoDocumento && $idAnticipo == $idDocumento) {
			usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante2').click();");
			return $objResponse->alert("El documento seleccionado ya se encuentra agregado");
        }
    }
	
    $objResponse->assign("hddIdDocumento","value",$idDocumento);
	$objResponse->assign("hddTipoDocumento","value",$hddTipoDocumento);
    $objResponse->assign("txtNroDocumento","value",$rowDocumento['numeroDocumento']); 
    $objResponse->assign("txtSaldoDocumento","value",number_format($rowDocumento['saldoDocumento'], 2, ".", ","));
    $objResponse->assign("txtMontoDocumento","value",number_format($rowDocumento['saldoDocumento'], 2, ".", ","));
	
    $objResponse->assign("tdFlotanteTitulo2","innerHTML",$documento);
	
	$objResponse->script("
	byId('txtMontoDocumento').focus();
	byId('txtMontoDocumento').select();");

    return $objResponse;
}

function guardarTransferencia($frmDcto, $frmDetallePago, $frmListaDctoPagado){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cjrs_transferencia_list","insertar")){ return $objResponse; }
	
	$idTransferencia = $frmDcto['hddIdTransferencia'];
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = $frmDcto['lstModulo'];
	$idCliente = $frmDcto['txtIdCliente'];
	$lstTipoTransferencia = $frmDcto['lstTipoTransferencia'];
	
	$idBancoCliente = $frmDetallePago['selBancoCliente'];
	$idBancoCompania = $frmDetallePago['selBancoCompania'];
	$numeroCuentaCliente = "-";
	$idCuentaCompania = $frmDetallePago['selNumeroCuenta'];
	$numeroCuentaCompania = asignarNumeroCuenta($idCuentaCompania);
	$numeroTransferencia = trim($frmDetallePago['txtNumeroDctoPago']);
	$txtMontoPago = str_replace(",", "", $frmDetallePago['txtMontoPago']);
	$saldoTransferencia = str_replace(",", "", $frmListaDctoPagado['txtMontoRestante']);
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	
	if ($txtMontoPago <= 0) { return $objResponse->alert("El monto de la transferencia no debe ser cero ni negativo");	}	
	if ($saldoTransferencia < 0) { return $objResponse->alert("El saldo de la transferencia no debe ser negativo"); }	
	if ($idCliente == "") { return $objResponse->alert("Debe seleccionar un cliente"); }
	
	if (in_array($lstTipoTransferencia,array(2,3))) { // 2 = tipo bono, 3 = tipo pnd
		if ($frmListaDctoPagado['cbxDctoAgregado'] == "") { return $objResponse->alert("Debe seleccionar almenos un anticipo"); }				
		//if ($saldoTransferencia != 0) { return $objResponse->alert("Debe usar el saldo completo de la transferencia para pagar anticipos"); }
	}

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
	if (!$rsAperturaCaja) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
	
	$fechaRegistroPago = $rowAperturaCaja["fechaAperturaCaja"];
	
	if ($idTransferencia > 0) {
	} else {
		$insertSQL = sprintf("INSERT INTO cj_cc_transferencia (id_cliente, tipo_transferencia, id_banco_cliente, id_banco_compania, id_cuenta_compania, cuenta_cliente, cuenta_compania, monto_neto_transferencia, saldo_transferencia, total_pagado_transferencia, fecha_transferencia, id_empleado_registro, observacion_transferencia, estado_transferencia, numero_transferencia, id_departamento, id_empresa, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",									
			valTpDato($idCliente, "int"),
			valTpDato($lstTipoTransferencia, "int"),
			valTpDato($idBancoCliente, "int"),
			valTpDato($idBancoCompania, "int"),
			valTpDato($idCuentaCompania, "int"),
			valTpDato($numeroCuentaCliente, "text"),
			valTpDato($numeroCuentaCompania, "text"),
			valTpDato($txtMontoPago, "real_inglesa"),
			valTpDato($txtMontoPago, "real_inglesa"),
			valTpDato($txtMontoPago, "real_inglesa"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($frmDcto['txtObservacionTransferencia'], "text"),
			valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
			valTpDato($numeroTransferencia, "int"),
			valTpDato($frmDcto['lstModulo'], "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato(1, "int"),
			valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato(0, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idTransferencia = mysql_insert_id();
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("TB", "text"),
			valTpDato($idTransferencia, "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato("6", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC, 5 = CH, 6 = TB
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(((in_array($idCajaPpal,array(1))) ? 45 : 44), "int"), // 44 = Recibo de Pago Repuestos y Servicios, 45 = Recibo de Pago Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado numeracion de comprobantes de pago"); }
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO pg_reportesimpresion (fechaDocumento, numeroReporteImpresion, tipoDocumento, idDocumento, idCliente, id_departamento, id_empleado_creador)
		VALUES(%s, %s, %s, %s, %s, %s, %s)",
			valTpDato($fechaRegistroPago, "date"),
			valTpDato($numeroActualPago, "int"),
			valTpDato("TB", "text"),
			valTpDato($idTransferencia, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($idModulo, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idReporteImpresionTransferencia = mysql_insert_id();
		
		// TODA TRANSFERENCIA INGRESA INMEDIATAMENTE A CAJA:	
		$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
			saldoTransferencia = saldoTransferencia + %s,
			saldoCaja = saldoCaja + %s
		WHERE id = %s;",
			valTpDato($txtMontoPago, "real_inglesa"),
			valTpDato($txtMontoPago, "real_inglesa"),
			valTpDato($rowAperturaCaja['id'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	if (isset($frmListaDctoPagado['cbxDctoAgregado'])) {
		foreach($frmListaDctoPagado['cbxDctoAgregado'] as $key => $valor){
			$arrayAnticipo = explode("|",$valor);
			$hddIdPago = $arrayAnticipo[0];
			$tipoDcto = $arrayAnticipo[1];
			$montoPagadoAnticipo = str_replace(",", "", $arrayAnticipo[3]);
			
			if ($tipoDcto == "FACTURA" && !($hddIdPago > 0)) {
				$idFactura = $arrayAnticipo[2];
				
				//consulto anticipo para verificar monto, se usa monto porque el saldo ya esta en cero al cargarse a una fact
				$queryFactura = sprintf("SELECT
					cxc_fact.idDepartamentoOrigenFactura,
					cxc_fact.numeroFactura,
					cxc_fact.saldoFactura AS monto_faltante_pago
				FROM cj_cc_encabezadofactura cxc_fact
				WHERE cxc_fact.idFactura = %s;",
					valTpDato($idFactura, "int"));
				$rsFactura = mysql_query($queryFactura);
				if (!$rsFactura) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
				$rowFactura = mysql_fetch_assoc($rsFactura);
				
				$idModulo = $rowFactura['idDepartamentoOrigenFactura'];
				$nuevoSaldoFactura = $rowFactura['monto_faltante_pago'] - $montoPagadoFactura;
				
				if ($nuevoSaldoFactura < 0) {
					return $objResponse->alert("El pago de la factura Nro ".$rowFactura['numeroFactura']." no puede quedar en negativo: ".$nuevoSaldoFactura);
				}
				
				// NUMERACION DEL DOCUMENTO
				$queryNumeracion = sprintf("SELECT *
				FROM pg_empresa_numeracion emp_num
					INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
				WHERE emp_num.id_numeracion = %s
					AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																									WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC LIMIT 1;",
					valTpDato(((in_array($idCajaPpal,array(1))) ? 45 : 44), "int"), // 44 = Recibo de Pago Repuestos y Servicios, 45 = Recibo de Pago Vehículos
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
				$rsNumeracion = mysql_query($queryNumeracion);
				if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
				
				$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
				$idNumeraciones = $rowNumeracion['id_numeracion'];
				$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
				
				if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado numeracion de comprobantes de pago"); }
				
				// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
				$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeracion, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$numeroActualPago = $numeroActual;
				
				// INSERTA EL RECIBO DE PAGO
				$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
				VALUES (%s, %s, %s, %s, %s, %s, %s)",
					valTpDato($numeroActualPago, "int"),
					valTpDato($fechaRegistroPago, "date"),
					valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
					valTpDato(0, "int"),		
					valTpDato($idFactura, "int"),
					valTpDato($idModulo, "int"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idEncabezadoReciboPago = mysql_insert_id();
				
				// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
				$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
				VALUES (%s, %s)",
					valTpDato($idFactura, "int"),
					valTpDato($fechaRegistroPago, "date"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idEncabezadoPago = mysql_insert_id();
				
				$idFormaPago = 4; // 4 = Transferencia Bancaria
				$numeroDocumento = $numeroTransferencia;
				$numeroActualFactura = $rowFactura['numeroFactura'];
				$tipoCheque = 0;
				$tipoTransferencia = 0;
				$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$estatusPago = 1;
				
				// INSERTA LOS PAGOS DEL DOCUMENTO
				$insertSQL = sprintf("INSERT INTO sa_iv_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_condicion_mostrar, id_mostrar_contado, id_encabezado_rs)
				VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
					valTpDato($idFormaPago, "int"),
					valTpDato($numeroDocumento, "text"),
					valTpDato($idBancoCliente, "int"),
					valTpDato($idBancoCompania, "int"),
					valTpDato($numeroCuentaCompania, "text"),
					valTpDato($montoPagadoAnticipo, "real_inglesa"),
					valTpDato($numeroActualFactura, "text"),
					valTpDato($tipoCheque, "text"),
					valTpDato($idCheque, "int"),
					valTpDato($tipoTransferencia, "text"),
					valTpDato($idTransferencia, "int"),
					valTpDato(1, "int"),
					valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
					valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					valTpDato(0, "int"),
					valTpDato($estatusPago, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
					valTpDato($frmListaDctoPagado['cbxCondicionMostrar'.$valorPago], "int"), // Null = No, 1 = Si
					valTpDato($frmListaDctoPagado['cbxMostrarContado'.$valorPago], "int"), // Null = No, 1 = Si
					valTpDato($idEncabezadoPago, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idPago = mysql_insert_id();
				
				// INSERTA EL DETALLE DEL RECIBO DE PAGO
				$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
				VALUES (%s, %s)",
					valTpDato($idEncabezadoReciboPago, "int"),
					valTpDato($idPago, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA EL SALDO DE LA FACTURA
				$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
					saldoFactura = IFNULL(cxc_fact.subtotalFactura, 0)
										- IFNULL(cxc_fact.descuentoFactura, 0)
										+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
												WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
										+ IFNULL((SELECT SUM(cxc_fact_impuesto.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_impuesto
												WHERE cxc_fact_impuesto.id_factura = cxc_fact.idFactura), 0)
				WHERE idFactura = %s;",
					valTpDato($idFactura, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA EL SALDO DE LA FACTURA DEPENDIENDO DE SUS PAGOS
				$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
					saldoFactura = IFNULL(saldoFactura, 0)
										- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
													WHERE cxc_pago.id_factura = cxc_fact.idFactura
														AND cxc_pago.estatus IN (1)), 0)
											+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
													WHERE cxc_pago.id_factura = cxc_fact.idFactura
														AND cxc_pago.estatus IN (1)), 0))
				WHERE idFactura = %s;",
					valTpDato($idFactura, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
				$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
					estadoFactura = (CASE
										WHEN (ROUND(saldoFactura, 2) <= 0) THEN
											1
										WHEN (ROUND(saldoFactura, 2) > 0 AND ROUND(saldoFactura, 2) < ROUND(montoTotalFactura, 2)) THEN
											2
										ELSE
											0
									END)
				WHERE idFactura = %s;",
					valTpDato($idFactura, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// VERIFICA EL SALDO DE LA FACTURA A VER SI ESTA NEGATIVO
				$querySaldoDcto = sprintf("SELECT cxc_fact.*,
					CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
					(SELECT COUNT(q.id_factura)
					FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura FROM an_pagos cxc_pago
						WHERE cxc_pago.estatus IN (2)
						
						UNION
						
						SELECT cxc_pago.idPago, cxc_pago.id_factura FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.estatus IN (2)) AS q
					WHERE q.id_factura = cxc_fact.idFactura) AS cant_pagos_pendientes
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
				WHERE cxc_fact.idFactura = %s
					AND (cxc_fact.saldoFactura < 0
						OR (cxc_fact.saldoFactura < (SELECT SUM(q.montoPagado)
														FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
															WHERE cxc_pago.estatus IN (2)
															
															UNION
															
															SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
															WHERE cxc_pago.estatus IN (2)) AS q
														WHERE q.id_factura = cxc_fact.idFactura)));",
					valTpDato($idFactura, "int"));
				$rsSaldoDcto = mysql_query($querySaldoDcto);
				if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
				$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
				$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
				if ($totalRowsSaldoDcto > 0) {
					if ($rowSaldoDcto['saldoFactura'] < 0) {
						return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo");
					} else if ($rowSaldoDcto['cant_pagos_pendientes'] > 0) {
						return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." no puede ser pagada en su totalidad debido a que posee ".$rowSaldoDcto['cant_pagos_pendientes']." pagos pendientes. Por favor termine de registrar o anular dichos pagos.");
					}
				}
				
				$arrayIdReciboVentana[] = $idEncabezadoReciboPago;
			} else if ($tipoDcto == "NOTA DEBITO" && !($hddIdPago > 0)) {
				$idNotaCargo = $arrayAnticipo[2];
				
				//consulto anticipo para verificar monto, se usa monto porque el saldo ya esta en cero al cargarse a una fact
				$queryNotaDebito = sprintf("SELECT
					cxc_nd.idDepartamentoOrigenNotaCargo,
					cxc_nd.numeroNotaCargo,
					cxc_nd.saldoNotaCargo AS monto_faltante_pago
				FROM cj_cc_notadecargo cxc_nd
				WHERE cxc_nd.idNotaCargo = %s;",
					valTpDato($idNotaCargo, "int"));
				$rsNotaDebito = mysql_query($queryNotaDebito);
				if (!$rsNotaDebito) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
				$rowNotaDebito = mysql_fetch_assoc($rsNotaDebito);
				
				$idModulo = $rowNotaDebito['idDepartamentoOrigenNotaCargo'];
				$nuevoSaldoNotaDebito = $rowNotaDebito['monto_faltante_pago'] - $montoPagadoNotaDebito;
				
				if ($nuevoSaldoNotaDebito < 0) {
					return $objResponse->alert("El pago de la nota de débito Nro ".$rowNotaDebito['numeroNotaDebito']." no puede quedar en negativo: ".$nuevoSaldoNotaDebito);
				}
				
				// NUMERACION DEL DOCUMENTO
				$queryNumeracion = sprintf("SELECT *
				FROM pg_empresa_numeracion emp_num
					INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
				WHERE emp_num.id_numeracion = %s
					AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																									WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC LIMIT 1;",
					valTpDato(((in_array($idCajaPpal,array(1))) ? 45 : 44), "int"), // 44 = Recibo de Pago Repuestos y Servicios, 45 = Recibo de Pago Vehículos
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
				$rsNumeracion = mysql_query($queryNumeracion);
				if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
				
				$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
				$idNumeraciones = $rowNumeracion['id_numeracion'];
				$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
				
				if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado numeracion de comprobantes de pago"); }
				
				// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
				$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeracion, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$numeroActualPago = $numeroActual;
				
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
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idEncabezadoReciboPago = mysql_insert_id();
				
				// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
				$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_nc_v (id_nota_cargo, fecha_pago)
				VALUES (%s, %s)",
					valTpDato($idNotaCargo, "int"),
					valTpDato($fechaRegistroPago, "date"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idEncabezadoPago = mysql_insert_id();
				
				$idFormaPago = 4; // 4 = Transferencia Bancaria
				$numeroDocumento = $numeroTransferencia;
				$tipoCheque = 0;
				$tipoTransferencia = 0;
				$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$estatusPago = 1;
				
				// INSERTA LOS PAGOS DEL DOCUMENTO
				$insertSQL = sprintf("INSERT INTO cj_det_nota_cargo (idNotaCargo, fechaPago, idFormaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, monto_pago, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_encabezado_nc)
				VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idNotaCargo, "int"),
					valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
					valTpDato($idFormaPago, "int"),
					valTpDato($numeroDocumento, "text"),
					valTpDato($idBancoCliente, "int"),
					valTpDato($idBancoCompania, "int"),
					valTpDato($numeroCuentaCompania, "text"),
					valTpDato($montoPagadoAnticipo, "real_inglesa"),
					valTpDato($tipoCheque, "text"),
					valTpDato($idCheque, "int"),
					valTpDato($tipoTransferencia, "text"),
					valTpDato($idTransferencia, "int"),
					valTpDato(1, "int"),
					valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
					valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					valTpDato(0, "int"),
					valTpDato($estatusPago, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
					valTpDato($idEncabezadoPago, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idPago = mysql_insert_id();
				
				// INSERTA EL DETALLE DEL RECIBO DE PAGO
				$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
				VALUES (%s, %s)",
					valTpDato($idEncabezadoReciboPago, "int"),
					valTpDato($idPago, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO
				$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
					saldoNotaCargo = montoTotalNotaCargo
				WHERE idNotaCargo = %s;",
					valTpDato($idNotaCargo, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO DEPENDIENDO DE SUS PAGOS
				$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
					saldoNotaCargo = saldoNotaCargo - IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
																WHERE cxc_pago.idNotaCargo = cxc_nd.idNotaCargo
																	AND cxc_pago.estatus IN (1)), 0)
				WHERE idNotaCargo = %s;",
					valTpDato($idNotaCargo, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA EL ESTATUS DE LA NOTA DE DEBITO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
				$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
					estadoNotaCargo = (CASE
										WHEN (ROUND(saldoNotaCargo, 2) <= 0) THEN
											1
										WHEN (ROUND(saldoNotaCargo, 2) > 0 AND ROUND(saldoNotaCargo, 2) < ROUND(montoTotalNotaCargo, 2)) THEN
											2
										ELSE
											0
									END)
				WHERE idNotaCargo = %s;",
					valTpDato($idNotaCargo, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// VERIFICA EL SALDO DE LA NOTA DE DEBITO A VER SI ESTA NEGATIVO
				$querySaldoDcto = sprintf("SELECT cxc_nd.*,
					CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
				FROM cj_cc_notadecargo cxc_nd
					INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
				WHERE idNotaCargo = %s
					AND saldoNotaCargo < 0;",
					valTpDato($idNotaCargo, "int"));
				$rsSaldoDcto = mysql_query($querySaldoDcto);
				if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
				$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
				$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
				if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Débito Nro. ".$rowSaldoDcto['numeroNotaCargo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
				
				$arrayIdReciboVentana[] = $idEncabezadoReciboPago;
			} else if ($tipoDcto == "ANTICIPO" && !($hddIdPago > 0)) {
				$idAnticipo = $arrayAnticipo[2];
				
				//consulto anticipo para verificar monto, se usa monto porque el saldo ya esta en cero al cargarse a una fact
				$queryAnticipo = sprintf("SELECT
					cxc_ant.idDepartamento,
					cxc_ant.numeroAnticipo,
					(cxc_ant.montoNetoAnticipo - cxc_ant.totalPagadoAnticipo) AS monto_faltante_pago
				FROM cj_cc_anticipo cxc_ant
				WHERE cxc_ant.idAnticipo = %s;",
					valTpDato($idAnticipo, "int"));
				$rsAnticipo = mysql_query($queryAnticipo);
				if (!$rsAnticipo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
				$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
				
				$idModulo = $rowAnticipo['idDepartamento'];
				$nuevoSaldoAnticipo = $rowAnticipo['monto_faltante_pago'] - $montoPagadoAnticipo;
				
				if ($nuevoSaldoAnticipo < 0) {
					return $objResponse->alert("El pago del anticipo Nro ".$rowAnticipo['numeroAnticipo']." no puede quedar en negativo: ".$nuevoSaldoAnticipo);
				}
				
				// NUMERACION DEL DOCUMENTO
				$queryNumeracion = sprintf("SELECT *
				FROM pg_empresa_numeracion emp_num
					INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
				WHERE emp_num.id_numeracion = %s
					AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																									WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC LIMIT 1;",
					valTpDato(((in_array($idCajaPpal,array(1))) ? 45 : 44), "int"), // 44 = Recibo de Pago Repuestos y Servicios, 45 = Recibo de Pago Vehículos
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
				$rsNumeracion = mysql_query($queryNumeracion);
				if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
				
				$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
				$idNumeraciones = $rowNumeracion['id_numeracion'];
				$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
				
				// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
				$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeracion, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$numeroActualPago = $numeroActual;	
				
				// INSERTA EL RECIBO DE PAGO
				$insertSQL = sprintf("INSERT INTO pg_reportesimpresion (fechaDocumento, numeroReporteImpresion, tipoDocumento, idDocumento, idCliente, id_departamento, id_empleado_creador)
				VALUES(%s, %s, %s, %s, %s, %s, %s)",
					valTpDato($fechaRegistroPago, "date"),
					valTpDato($numeroActualPago, "int"),
					valTpDato("AN", "text"),
					valTpDato($idAnticipo, "int"),
					valTpDato($idCliente, "int"),
					valTpDato($idModulo, "int"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idReporteImpresion = mysql_insert_id();
				
				$idFormaPago = 4; // 4 = Transferencia Bancaria
				$numeroDocumento = $numeroTransferencia;
				$tipoCheque = 0;
				$tipoTransferencia = 0;
				$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$estatusPago = 1;
				
				// INSERTA EL PAGO DEL ANTICIPO
				$insertSQL = sprintf("INSERT INTO cj_cc_detalleanticipo (id_reporte_impresion, tipoPagoDetalleAnticipo, id_forma_pago, id_concepto, id_cheque, tipo_transferencia, id_transferencia, bancoClienteDetalleAnticipo, bancoCompaniaDetalleAnticipo, numeroCuentaCliente, numeroCuentaCompania, numeroControlDetalleAnticipo, montoDetalleAnticipo, idAnticipo, fechaPagoAnticipo, tomadoEnCierre, idCaja, idCierre, estatus)
				VALUES(%s, (SELECT aliasFormaPago FROM formapagos WHERE idFormaPago = %s), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idReporteImpresion, "int"),
					valTpDato($idFormaPago, "text"),
					valTpDato($idFormaPago, "int"),
					valTpDato($idConcepto, "int"),
					valTpDato($idCheque, "int"),
					valTpDato($tipoTransferencia, "text"),
					valTpDato($idTransferencia, "int"),
					valTpDato($idBancoCliente, "int"),
					valTpDato($idBancoCompania, "int"),
					valTpDato($numeroCuentaCliente, "int"),
					valTpDato($numeroCuentaCompania, "text"),
					valTpDato($numeroDocumento, "text"),
					valTpDato($montoPagadoAnticipo, "real_inglesa"),
					valTpDato($idAnticipo, "int"),
					valTpDato($fechaRegistroPago, "date"),
					valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
					valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					valTpDato(0, "int"),
					valTpDato($estatusPago, "int")); // Null = Anulado, 1 = Activo, 2 = Pendiente
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idDetalleAnticipo = mysql_insert_id();			
				
				// ACTUALIZA EL SALDO Y EL MONTO PAGADO DEL ANTICIPO
				$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
					saldoAnticipo = montoNetoAnticipo,
					totalPagadoAnticipo = IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
													WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
														AND (cxc_pago.id_forma_pago NOT IN (11)
															OR (cxc_pago.id_forma_pago IN (11) AND cxc_pago.id_concepto NOT IN (6,7,8)))
														AND cxc_pago.estatus IN (1,2)), 0)
				WHERE cxc_ant.idAnticipo = %s;",
					valTpDato($idAnticipo, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA EL SALDO DEL ANTICIPO SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
				$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
					saldoAnticipo = saldoAnticipo
										- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
													WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
														AND cxc_pago.formaPago IN (7)
														AND cxc_pago.estatus IN (1,2)), 0)
											+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
														WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
															AND cxc_pago.formaPago IN (7)
															AND cxc_pago.estatus IN (1,2)), 0)
											+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
														WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
															AND cxc_pago.idFormaPago IN (7)
															AND cxc_pago.estatus IN (1,2)), 0))
				WHERE cxc_ant.idAnticipo = %s;",
					valTpDato($idAnticipo, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
				// ACTUALIZA EL ESTATUS DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
				$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
					estadoAnticipo = (CASE
										WHEN (ROUND(montoNetoAnticipo, 2) > ROUND(totalPagadoAnticipo, 2)
											AND ROUND(saldoAnticipo, 2) > 0) THEN
											0
										WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
											AND ROUND(saldoAnticipo, 2) <= 0
											AND cxc_ant.idAnticipo IN (SELECT * 
																		FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																			WHERE cxc_pago.formaPago IN (7)
																				AND cxc_pago.estatus IN (1)
																			
																			UNION
																			
																			SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																			WHERE cxc_pago.formaPago IN (7)
																				AND cxc_pago.estatus IN (1)
																			
																			UNION
																			
																			SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																			WHERE cxc_pago.idFormaPago IN (7)
																				AND cxc_pago.estatus IN (1)) AS q)) THEN
											3
										WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
											AND ROUND(montoNetoAnticipo, 2) = ROUND(saldoAnticipo, 2)) THEN
											1
										WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
											AND ROUND(montoNetoAnticipo, 2) > ROUND(saldoAnticipo, 2)
											AND ROUND(saldoAnticipo, 2) > 0) THEN
											2
										WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
											AND ROUND(saldoAnticipo, 2) <= 0) THEN
											3
										WHEN (ROUND(montoNetoAnticipo, 2) > ROUND(totalPagadoAnticipo, 2)
											AND ROUND(saldoAnticipo, 2) <= 0) THEN
											4
									END)
				WHERE cxc_ant.idAnticipo = %s;",
					valTpDato($idAnticipo, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// VERIFICA EL SALDO DEL ANTICIPO A VER SI ESTA NEGATIVO
				$querySaldoDcto = sprintf("SELECT cxc_ant.*,
					CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
				FROM cj_cc_anticipo cxc_ant
					INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
				WHERE idAnticipo = %s
					AND saldoAnticipo < 0;",
					valTpDato($idAnticipo, "int"));
				$rsSaldoDcto = mysql_query($querySaldoDcto);
				if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
				$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
				$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
				if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("El Anticipo Nro. ".$rowSaldoDcto['numeroAnticipo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
				
				
				// BUSCA LOS DATOS DEL ANTICIPO
				$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo WHERE idAnticipo = %s;",
					valTpDato($idAnticipo, "int"));
				$rsAnticipo = mysql_query($queryAnticipo);
				if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
				$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
			
				// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado)
				if ($rowAnticipo['estadoAnticipo'] == 3) {
					// BUSCO SI EL ANTICIPO ESTABA ASIGNADO COMO PAGO PENDIENTE
					$queryPago = sprintf("SELECT query.* FROM (SELECT 
							cxc_pago.idPago,
							cxc_pago.id_factura,
							cxc_fact.id_empresa,
							cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
							1 AS tipo_documento,
							cxc_fact.saldoFactura,
							cxc_pago.fechaPago,
							cxc_pago.formaPago,
							cxc_pago.numeroDocumento,
							cxc_pago.montoPagado,
							cxc_pago.tomadoEnComprobante,
							cxc_pago.tomadoEnCierre,
							cxc_pago.idCaja,
							cxc_pago.idCierre,
							cxc_pago.estatus
						FROM cj_cc_encabezadofactura cxc_fact
							INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
						WHERE cxc_pago.formaPago IN (7)
						
						UNION
						
						SELECT 
							cxc_pago.idPago,
							cxc_pago.id_factura,
							cxc_fact.id_empresa,
							cxc_fact.idDepartamentoOrigenFactura,
							1 AS tipo_documento,
							cxc_fact.saldoFactura,
							cxc_pago.fechaPago,
							cxc_pago.formaPago,
							cxc_pago.numeroDocumento,
							cxc_pago.montoPagado,
							cxc_pago.tomadoEnComprobante,
							cxc_pago.tomadoEnCierre,
							cxc_pago.idCaja,
							cxc_pago.idCierre,
							cxc_pago.estatus
						FROM cj_cc_encabezadofactura cxc_fact
							INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
						WHERE cxc_pago.formaPago IN (7)
						
						UNION
						
						SELECT 
							cxc_pago.id_det_nota_cargo,
							cxc_pago.idNotaCargo,
							cxc_nd.id_empresa,
							cxc_nd.idDepartamentoOrigenNotaCargo,
							2 AS tipo_documento,
							cxc_nd.saldoNotaCargo,
							cxc_pago.fechaPago,
							cxc_pago.idFormaPago,
							cxc_pago.numeroDocumento,
							cxc_pago.monto_pago,
							cxc_pago.tomadoEnComprobante,
							cxc_pago.tomadoEnCierre,
							cxc_pago.idCaja,
							cxc_pago.idCierre,
							cxc_pago.estatus
						FROM cj_cc_notadecargo cxc_nd
							INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
						WHERE cxc_pago.idFormaPago IN (7)) AS query
					WHERE query.numeroDocumento = %s
						AND query.estatus IN (2);",
						valTpDato($idAnticipo, "int"));
					$rsPago = mysql_query($queryPago);
					if (!$rsPago) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsPago = mysql_num_rows($rsPago);
					while ($rowPago = mysql_fetch_assoc($rsPago)) {
						$idCaja = $rowPago['idCaja'];
						$idModulo = $rowPago['id_modulo'];
						
						if ($rowPago['idCierre'] > 0) {
							if ($rowPago['tipo_documento'] == 1) { // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
								$idFactura = $rowPago['id_factura'];
								
								if (in_array($idCaja,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
									// ANULA EL PAGO
									$udpateSQL = sprintf("UPDATE an_pagos SET
										estatus = NULL,
										fecha_anulado = %s,
										id_empleado_anulado = %s
									WHERE idPago = %s;",
										valTpDato("NOW()", "campo"),
										valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
										valTpDato($rowPago['idPago'], "int"));
									$Result1 = mysql_query($udpateSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
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
									if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
									
									$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
									$idNumeraciones = $rowNumeracion['id_numeracion'];
									$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
									
									// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
									$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
									WHERE id_empresa_numeracion = %s;",
										valTpDato($idEmpresaNumeracion, "int"));
									$Result1 = mysql_query($updateSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									
									$numeroActualPago = $numeroActual;
									
									// INSERTA EL RECIBO DE PAGO
									$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
									VALUES (%s, %s, %s, %s, %s, %s, %s)",
										valTpDato($numeroActualPago, "int"),
										valTpDato($fechaRegistroPago, "date"),
										valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
										valTpDato(0, "int"),		
										valTpDato($idFactura, "int"),
										valTpDato($idModulo, "int"),
										valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
									$Result1 = mysql_query($insertSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$idEncabezadoReciboPago = mysql_insert_id();
									
									// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
									$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_v (id_factura, fecha_pago)
									VALUES (%s, %s)",
										valTpDato($idFactura, "int"),
										valTpDato($fechaRegistroPago, "date"));
									$Result1 = mysql_query($insertSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$idEncabezadoPago = mysql_insert_id();
									
									// INSERTA EL NUEVO PAGO CON LA APERTURA DE CAJA ACTUAL
									$insertSQL = sprintf("INSERT INTO an_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_encabezado_v)
									SELECT id_factura, %s, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, %s, %s, idCaja, %s, %s, %s
									FROM an_pagos WHERE idPago = %s;",
										valTpDato($fechaRegistroPago, "date"),
										valTpDato(1, "int"),
										valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
										valTpDato(0, "int"),
										valTpDato(1, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
										valTpDato($idEncabezadoPago, "int"),
										valTpDato($rowPago['idPago'], "int"));
									mysql_query("SET NAMES 'utf8';");
									$Result1 = mysql_query($insertSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$idPago = mysql_insert_id();
									mysql_query("SET NAMES 'latin1';");
								} else if (in_array($idCaja,array(2))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
									// ANULA EL PAGO
									$udpateSQL = sprintf("UPDATE sa_iv_pagos SET
										estatus = NULL,
										fecha_anulado = %s,
										id_empleado_anulado = %s
									WHERE idPago = %s;",
										valTpDato("NOW()", "campo"),
										valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
										valTpDato($rowPago['idPago'], "int"));
									$Result1 = mysql_query($udpateSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									
									// NUMERACION DEL DOCUMENTO
									$queryNumeracion = sprintf("SELECT *
									FROM pg_empresa_numeracion emp_num
										INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
									WHERE emp_num.id_numeracion = %s
										AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																														WHERE suc.id_empresa = %s)))
									ORDER BY aplica_sucursales DESC LIMIT 1;",
										valTpDato(44, "int"), // 44 = Recibo de Pago Repuestos y Servicios
										valTpDato($idEmpresa, "int"),
										valTpDato($idEmpresa, "int"));
									$rsNumeracion = mysql_query($queryNumeracion);
									if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
									
									$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
									$idNumeraciones = $rowNumeracion['id_numeracion'];
									$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
									
									// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
									$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
									WHERE id_empresa_numeracion = %s;",
										valTpDato($idEmpresaNumeracion, "int"));
									$Result1 = mysql_query($updateSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									
									$numeroActualPago = $numeroActual;
									
									// INSERTA EL RECIBO DE PAGO
									$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
									VALUES (%s, %s, %s, %s, %s, %s, %s)",
										valTpDato($numeroActualPago, "int"),
										valTpDato($fechaRegistroPago, "date"),
										valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
										valTpDato(0, "int"),		
										valTpDato($idFactura, "int"),
										valTpDato($idModulo, "int"),
										valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
									$Result1 = mysql_query($insertSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$idEncabezadoReciboPago = mysql_insert_id();
								
									// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
									$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
									VALUES (%s, %s)",
										valTpDato($idFactura, "int"),
										valTpDato($fechaRegistroPago, "date"));
									$Result1 = mysql_query($insertSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$idEncabezadoPago = mysql_insert_id();
									
									// INSERTA EL NUEVO PAGO CON LA APERTURA DE CAJA ACTUAL
									$insertSQL = sprintf("INSERT INTO sa_iv_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_encabezado_rs)
									SELECT id_factura, %s, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, %s, %s, idCaja, %s, %s, %s
									FROM sa_iv_pagos WHERE idPago = %s;",
										valTpDato($fechaRegistroPago, "date"),
										valTpDato(1, "int"),
										valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
										valTpDato(0, "int"),
										valTpDato(1, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
										valTpDato($idEncabezadoPago, "int"),
										valTpDato($rowPago['idPago'], "int"));
									mysql_query("SET NAMES 'utf8';");
									$Result1 = mysql_query($insertSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$idPago = mysql_insert_id();
									mysql_query("SET NAMES 'latin1';");
								}
								
								// INSERTA EL DETALLE DEL RECIBO DE PAGO
								$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
								VALUES (%s, %s)",
									valTpDato($idEncabezadoReciboPago, "int"),
									valTpDato($idPago, "int"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL SALDO DE LA FACTURA
								$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
									saldoFactura = IFNULL(cxc_fact.subtotalFactura, 0)
														- IFNULL(cxc_fact.descuentoFactura, 0)
														+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
																WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
														+ IFNULL((SELECT SUM(cxc_fact_impuesto.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_impuesto
																WHERE cxc_fact_impuesto.id_factura = cxc_fact.idFactura), 0)
								WHERE idFactura = %s;",
									valTpDato($idFactura, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL SALDO DE LA FACTURA DEPENDIENDO DE SUS PAGOS
								$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
									saldoFactura = IFNULL(saldoFactura, 0)
														- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
																	WHERE cxc_pago.id_factura = cxc_fact.idFactura
																		AND cxc_pago.estatus IN (1)), 0)
															+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
																	WHERE cxc_pago.id_factura = cxc_fact.idFactura
																		AND cxc_pago.estatus IN (1)), 0))
								WHERE idFactura = %s;",
									valTpDato($idFactura, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
								$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
									estadoFactura = (CASE
														WHEN (ROUND(saldoFactura, 2) <= 0) THEN
															1
														WHEN (ROUND(saldoFactura, 2) > 0 AND ROUND(saldoFactura, 2) < ROUND(montoTotalFactura, 2)) THEN
															2
														ELSE
															0
													END)
								WHERE idFactura = %s;",
									valTpDato($idFactura, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// VERIFICA EL SALDO DE LA FACTURA A VER SI ESTA NEGATIVO
								$querySaldoDcto = sprintf("SELECT cxc_fact.*,
									CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
									(SELECT COUNT(q.id_factura)
									FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura FROM an_pagos cxc_pago
										WHERE cxc_pago.estatus IN (2)
										
										UNION
										
										SELECT cxc_pago.idPago, cxc_pago.id_factura FROM sa_iv_pagos cxc_pago
										WHERE cxc_pago.estatus IN (2)) AS q
									WHERE q.id_factura = cxc_fact.idFactura) AS cant_pagos_pendientes
								FROM cj_cc_encabezadofactura cxc_fact
									INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
								WHERE cxc_fact.idFactura = %s
									AND (cxc_fact.saldoFactura < 0
										OR (cxc_fact.saldoFactura < (SELECT SUM(q.montoPagado)
																		FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
																			WHERE cxc_pago.estatus IN (2)
																			
																			UNION
																			
																			SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
																			WHERE cxc_pago.estatus IN (2)) AS q
																		WHERE q.id_factura = cxc_fact.idFactura)));",
									valTpDato($idFactura, "int"));
								$rsSaldoDcto = mysql_query($querySaldoDcto);
								if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
								$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
								$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
								if ($totalRowsSaldoDcto > 0) {
									if ($rowSaldoDcto['saldoFactura'] < 0) {
										return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo");
									} else if ($rowSaldoDcto['cant_pagos_pendientes'] > 0) {
										return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." no puede ser pagada en su totalidad debido a que posee ".$rowSaldoDcto['cant_pagos_pendientes']." pagos pendientes. Por favor termine de registrar o anular dichos pagos.");
									}
								}
							} else if ($rowPago['tipo_documento'] == 2) { // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
								$idNotaCargo = $rowPago['id_factura'];
								
								// ANULA EL PAGO
								$udpateSQL = sprintf("UPDATE cj_det_nota_cargo SET
									estatus = NULL,
									fecha_anulado = %s,
									id_empleado_anulado = %s
								WHERE idPago = %s;",
									valTpDato("NOW()", "campo"),
									valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
									valTpDato($rowPago['idPago'], "int"));
								$Result1 = mysql_query($udpateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								if (in_array($idCaja,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
									$idNumeracion = 45; // 45 = Recibo de Pago Vehículos
								} else if (in_array($idCaja,array(2))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
									$idNumeracion = 44; // 44 = Recibo de Pago Repuestos y Servicios
								}
								
								// NUMERACION DEL DOCUMENTO
								$queryNumeracion = sprintf("SELECT *
								FROM pg_empresa_numeracion emp_num
									INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
								WHERE emp_num.id_numeracion = %s
									AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																													WHERE suc.id_empresa = %s)))
								ORDER BY aplica_sucursales DESC LIMIT 1;",
									valTpDato($idNumeracion, "int"), // 44 = Recibo de Pago Repuestos y Servicios, 45 = Recibo de Pago Vehículos
									valTpDato($idEmpresa, "int"),
									valTpDato($idEmpresa, "int"));
								$rsNumeracion = mysql_query($queryNumeracion);
								if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
								
								$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
								$idNumeraciones = $rowNumeracion['id_numeracion'];
								$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
								
								// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
								$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
								WHERE id_empresa_numeracion = %s;",
									valTpDato($idEmpresaNumeracion, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								$numeroActualPago = $numeroActual;
								
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
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$idEncabezadoReciboPago = mysql_insert_id();
								
								if (in_array($idCaja,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
									// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
									$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_nc_v (id_nota_cargo, fecha_pago)
									VALUES (%s, %s)",
										valTpDato($idNotaCargo, "int"),
										valTpDato($fechaRegistroPago, "date"));
									$Result1 = mysql_query($insertSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$idEncabezadoPago = mysql_insert_id();
								} else if (in_array($idCaja,array(2))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
									// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
									$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_nc_rs (id_nota_cargo, fecha_pago)
									VALUES (%s, %s)",
										valTpDato($idNotaCargo, "int"),
										valTpDato($fechaRegistroPago, "date"));
									$Result1 = mysql_query($insertSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									$idEncabezadoPago = mysql_insert_id();
								}
								
								// INSERTA EL NUEVO PAGO CON LA APERTURA DE CAJA ACTUAL
								$insertSQL = sprintf("INSERT INTO cj_det_nota_cargo (idNotaCargo, fechaPago, idFormaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, monto_pago, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_encabezado_nc)
								SELECT idNotaCargo, %s, idFormaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, monto_pago, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, %s, %s, idCaja, %s, %s, %s
								FROM cj_det_nota_cargo WHERE id_det_nota_cargo = %s;",
									valTpDato($fechaRegistroPago, "date"),
									valTpDato(1, "int"),
									valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
									valTpDato(0, "int"),
									valTpDato(1, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
									valTpDato($idEncabezadoPago, "int"),
									valTpDato($rowPago['idPago'], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$idPago = mysql_insert_id();
								mysql_query("SET NAMES 'latin1';");
								
								// INSERTA EL DETALLE DEL RECIBO DE PAGO
								$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
								VALUES (%s, %s)",
									valTpDato($idEncabezadoReciboPago, "int"),
									valTpDato($idPago, "int"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO
								$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
									saldoNotaCargo = IFNULL(cxc_nd.subtotalNotaCargo, 0)
														- IFNULL(cxc_nd.descuentoNotaCargo, 0)
														+ IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
														+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)
								WHERE idNotaCargo = %s;",
									valTpDato($idNotaCargo, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO DEPENDIENDO DE SUS PAGOS
								$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
									saldoNotaCargo = saldoNotaCargo - IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
																				WHERE cxc_pago.idNotaCargo = cxc_nd.idNotaCargo
																					AND cxc_pago.estatus IN (1)), 0)
								WHERE idNotaCargo = %s;",
									valTpDato($idNotaCargo, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL ESTATUS DE LA NOTA DE DEBITO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
								$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
									estadoNotaCargo = (CASE
														WHEN (ROUND(saldoNotaCargo, 2) <= 0) THEN
															1
														WHEN (ROUND(saldoNotaCargo, 2) > 0 AND ROUND(saldoNotaCargo, 2) < ROUND(montoTotalNotaCargo, 2)) THEN
															2
														ELSE
															0
													END)
								WHERE idNotaCargo = %s;",
									valTpDato($idNotaCargo, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// VERIFICA EL SALDO DE LA NOTA DE DEBITO A VER SI ESTA NEGATIVO
								$querySaldoDcto = sprintf("SELECT cxc_nd.*,
									CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
								FROM cj_cc_notadecargo cxc_nd
									INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
								WHERE idNotaCargo = %s
									AND saldoNotaCargo < 0;",
									valTpDato($idNotaCargo, "int"));
								$rsSaldoDcto = mysql_query($querySaldoDcto);
								if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
								$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
								$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
								if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Débito Nro. ".$rowSaldoDcto['numeroNotaCargo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
							}
						} else {
							if ($rowPago['tipo_documento'] == 1) { // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
								$idFactura = $rowPago['id_factura'];
								
								if (in_array($idCaja,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
									// ACTUALIZA EL ESTADO DEL PAGO
									$udpateSQL = sprintf("UPDATE an_pagos SET estatus = 1 WHERE idPago = %s;",
										valTpDato($rowPago['idPago'], "int"));
									mysql_query("SET NAMES 'utf8';");
									$Result1 = mysql_query($udpateSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									mysql_query("SET NAMES 'latin1';");
								} else if (in_array($idCaja,array(2))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
									// ACTUALIZA EL ESTADO DEL PAGO
									$udpateSQL = sprintf("UPDATE sa_iv_pagos SET estatus = 1 WHERE idPago = %s;",
										valTpDato($rowPago['idPago'], "int"));
									mysql_query("SET NAMES 'utf8';");
									$Result1 = mysql_query($udpateSQL);
									if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
									mysql_query("SET NAMES 'latin1';");
								}
								
								// ACTUALIZA EL SALDO DE LA FACTURA
								$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
									saldoFactura = IFNULL(cxc_fact.subtotalFactura, 0)
														- IFNULL(cxc_fact.descuentoFactura, 0)
														+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
																WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
														+ IFNULL((SELECT SUM(cxc_fact_impuesto.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_impuesto
																WHERE cxc_fact_impuesto.id_factura = cxc_fact.idFactura), 0)
								WHERE idFactura = %s;",
									valTpDato($idFactura, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL SALDO DE LA FACTURA DEPENDIENDO DE SUS PAGOS
								$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
									saldoFactura = IFNULL(saldoFactura, 0)
														- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
																	WHERE cxc_pago.id_factura = cxc_fact.idFactura
																		AND cxc_pago.estatus IN (1)), 0)
															+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
																	WHERE cxc_pago.id_factura = cxc_fact.idFactura
																		AND cxc_pago.estatus IN (1)), 0))
								WHERE idFactura = %s;",
									valTpDato($idFactura, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
								$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
									estadoFactura = (CASE
														WHEN (ROUND(saldoFactura, 2) <= 0) THEN
															1
														WHEN (ROUND(saldoFactura, 2) > 0 AND ROUND(saldoFactura, 2) < ROUND(montoTotalFactura, 2)) THEN
															2
														ELSE
															0
													END)
								WHERE idFactura = %s;",
									valTpDato($idFactura, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// VERIFICA EL SALDO DE LA FACTURA A VER SI ESTA NEGATIVO
								$querySaldoDcto = sprintf("SELECT cxc_fact.*,
									CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
									(SELECT COUNT(q.id_factura)
									FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura FROM an_pagos cxc_pago
										WHERE cxc_pago.estatus IN (2)
										
										UNION
										
										SELECT cxc_pago.idPago, cxc_pago.id_factura FROM sa_iv_pagos cxc_pago
										WHERE cxc_pago.estatus IN (2)) AS q
									WHERE q.id_factura = cxc_fact.idFactura) AS cant_pagos_pendientes
								FROM cj_cc_encabezadofactura cxc_fact
									INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
								WHERE cxc_fact.idFactura = %s
									AND (cxc_fact.saldoFactura < 0
										OR (cxc_fact.saldoFactura < (SELECT SUM(q.montoPagado)
																		FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
																			WHERE cxc_pago.estatus IN (2)
																			
																			UNION
																			
																			SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
																			WHERE cxc_pago.estatus IN (2)) AS q
																		WHERE q.id_factura = cxc_fact.idFactura)));",
									valTpDato($idFactura, "int"));
								$rsSaldoDcto = mysql_query($querySaldoDcto);
								if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
								$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
								$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
								if ($totalRowsSaldoDcto > 0) {
									if ($rowSaldoDcto['saldoFactura'] < 0) {
										return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo");
									} else if ($rowSaldoDcto['cant_pagos_pendientes'] > 0) {
										return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." no puede ser pagada en su totalidad debido a que posee ".$rowSaldoDcto['cant_pagos_pendientes']." pagos pendientes. Por favor termine de registrar o anular dichos pagos.");
									}
								}
							} else if ($rowPago['tipo_documento'] == 2) { // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
								$idNotaCargo = $rowPago['id_factura'];
								
								// ACTUALIZA EL ESTADO DEL PAGO
								$udpateSQL = sprintf("UPDATE cj_det_nota_cargo SET estatus = 1 WHERE id_det_nota_cargo = %s",
									valTpDato($rowPago['idPago'], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($udpateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
								
								// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO
								$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
									saldoNotaCargo = IFNULL(cxc_nd.subtotalNotaCargo, 0)
														- IFNULL(cxc_nd.descuentoNotaCargo, 0)
														+ IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
														+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)
								WHERE idNotaCargo = %s;",
									valTpDato($idNotaCargo, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO DEPENDIENDO DE SUS PAGOS
								$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
									saldoNotaCargo = saldoNotaCargo - IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
																				WHERE cxc_pago.idNotaCargo = cxc_nd.idNotaCargo
																					AND cxc_pago.estatus IN (1)), 0)
								WHERE idNotaCargo = %s;",
									valTpDato($idNotaCargo, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// ACTUALIZA EL ESTATUS DE LA NOTA DE DEBITO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
								$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
									estadoNotaCargo = (CASE
														WHEN (ROUND(saldoNotaCargo, 2) <= 0) THEN
															1
														WHEN (ROUND(saldoNotaCargo, 2) > 0 AND ROUND(saldoNotaCargo, 2) < ROUND(montoTotalNotaCargo, 2)) THEN
															2
														ELSE
															0
													END)
								WHERE idNotaCargo = %s;",
									valTpDato($idNotaCargo, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
								// VERIFICA EL SALDO DE LA NOTA DE DEBITO A VER SI ESTA NEGATIVO
								$querySaldoDcto = sprintf("SELECT cxc_nd.*,
									CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
								FROM cj_cc_notadecargo cxc_nd
									INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
								WHERE idNotaCargo = %s
									AND saldoNotaCargo < 0;",
									valTpDato($idNotaCargo, "int"));
								$rsSaldoDcto = mysql_query($querySaldoDcto);
								if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
								$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
								$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
								if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Débito Nro. ".$rowSaldoDcto['numeroNotaCargo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
							}
						}//fin else cierre == 0
					}//fin while todos los pagos anticipo
				}//fin if estado anticipo = 3
				
				$arrayIdReciboVentana[] = $idEncabezadoReciboPago;
				$arrayIdReciboImpresionVentana[] = $idReporteImpresion;
			}
			
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
				valTpDato($idCliente, "int"),
				valTpDato($idEmpresa, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} // FIN foreach de cbxDctoAgregado
	}
	
	// ACTUALIZA EL SALDO DE LA TRANSFERENCIA (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
	$updateSQL = sprintf("UPDATE cj_cc_transferencia cxc_tb SET
		saldo_transferencia = monto_neto_transferencia,
		total_pagado_transferencia = monto_neto_transferencia
	WHERE cxc_tb.id_transferencia = %s
		AND cxc_tb.estado_transferencia IN (0,1,2);",
		valTpDato($idTransferencia, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// ACTUALIZA EL SALDO DE LA TRANSFERENCIA SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
	$updateSQL = sprintf("UPDATE cj_cc_transferencia cxc_tb SET
		saldo_transferencia = saldo_transferencia
						- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
									AND cxc_pago.formaPago IN (4)
									AND cxc_pago.estatus IN (1,2)), 0)
							+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
										AND cxc_pago.formaPago IN (4)
										AND cxc_pago.estatus IN (1,2)), 0)
							+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
										AND cxc_pago.idFormaPago IN (4)
										AND cxc_pago.estatus IN (1,2)), 0)
							+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
									WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
										AND cxc_pago.id_forma_pago IN (4)
										AND cxc_pago.estatus IN (1,2)), 0))
	WHERE cxc_tb.id_transferencia = %s
		AND cxc_tb.estado_transferencia IN (0,1,2);",
		valTpDato($idTransferencia, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// ACTUALIZA EL ESTATUS DE LA TRANSFERENCIA (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
	$updateSQL = sprintf("UPDATE cj_cc_transferencia cxc_tb SET
		estado_transferencia = (CASE
							WHEN (ROUND(monto_neto_transferencia, 2) > ROUND(total_pagado_transferencia, 2)
								AND ROUND(saldo_transferencia, 2) > 0) THEN
								0
							WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
								AND ROUND(saldo_transferencia, 2) <= 0
								AND cxc_tb.id_transferencia IN (SELECT * 
															FROM (SELECT cxc_pago.id_transferencia FROM an_pagos cxc_pago
																WHERE cxc_pago.formaPago IN (4)
																	AND cxc_pago.estatus IN (1)
																
																UNION
																
																SELECT cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
																WHERE cxc_pago.formaPago IN (4)
																	AND cxc_pago.estatus IN (1)
																
																UNION
																
																SELECT cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
																WHERE cxc_pago.idFormaPago IN (4)
																	AND cxc_pago.estatus IN (1)
																
																UNION
																
																SELECT cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
																WHERE cxc_pago.id_forma_pago IN (4)
																	AND cxc_pago.estatus IN (1)) AS q)) THEN
								3
							WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
								AND ROUND(monto_neto_transferencia, 2) = ROUND(saldo_transferencia, 2)) THEN
								1
							WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
								AND ROUND(monto_neto_transferencia, 2) > ROUND(saldo_transferencia, 2)
								AND ROUND(saldo_transferencia, 2) > 0) THEN
								2
							WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
								AND ROUND(saldo_transferencia, 2) <= 0) THEN
								3
							WHEN (ROUND(monto_neto_transferencia, 2) > ROUND(total_pagado_transferencia, 2)
								AND ROUND(saldo_transferencia, 2) <= 0) THEN
								4
						END)
	WHERE cxc_tb.id_transferencia = %s
		AND cxc_tb.estado_transferencia IN (0,1,2);",
		valTpDato($idTransferencia, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// VERIFICA EL SALDO DE LA TRANSFERENCIA A VER SI ESTA NEGATIVO
	$querySaldoDcto = sprintf("SELECT cxc_tb.*,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM cj_cc_transferencia cxc_tb
		INNER JOIN cj_cc_cliente cliente ON (cxc_tb.id_cliente = cliente.id)
	WHERE id_transferencia = %s
		AND saldo_transferencia < 0;",
		valTpDato($idTransferencia, "int"));
	$rsSaldoDcto = mysql_query($querySaldoDcto);
	if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
	$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
	$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
	if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Transferencia Nro. ".$rowSaldoDcto['numero_transferencia']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Transferencia guardada correctamente");
	
	if (count($arrayIdReciboVentana) > 0) {
		$objResponse->script(sprintf("verVentana('reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s',960,550);", implode(",",$arrayIdReciboVentana)));
	}
	
	if (count($arrayIdReciboImpresionVentana) > 0) {
		$objResponse->script(sprintf("verVentana('reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s',960,550);", implode(",",$arrayIdReciboImpresionVentana)));
	}
	
	if (!($frmDcto['hddIdTransferencia'] > 0)) {
		$objResponse->script(sprintf("verVentana('reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=6&id=%s',960,550)", $idTransferencia));
	}
	
	$objResponse->script(sprintf("
	cerrarVentana = true;
	window.location.href='cjrs_transferencia_list.php';"));
	
	// MODIFICADO ERNESTO
	/////////////if (function_exists("generarAnticiposVe")) { generarAnticiposVe($idAnticipo,"",""); } 
	// MODIFICADO ERNESTO
	
	return $objResponse;
}

function insertarDctoPagado($frmAnticipoNotaCreditoChequeTransferencia, $frmListaDctoPagado, $frmListaAnticipo){
    $objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmListaDctoPagado['cbx4'];
	$contFila = $arrayObj4[count($arrayObj4)-1];
	
    if (str_replace(",", "", $frmListaDctoPagado['txtMontoRestante']) < str_replace(",", "", $frmAnticipoNotaCreditoChequeTransferencia['txtMontoDocumento'])){
        return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la transferencia");
    }

    foreach ($frmListaDctoPagado['cbxDctoAgregado'] as $key => $valor){
		$arrayAnticipo = explode("|",$valor);
		$hddIdPago = $arrayAnticipo[0];
		$tipoDcto = $arrayAnticipo[1];
		$idAnticipo = $arrayAnticipo[2];
		
        if (!($hddIdPago > 0) && $tipoDcto == $frmAnticipoNotaCreditoChequeTransferencia['hddTipoDocumento'] && $idAnticipo == $frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento']) {
			return $objResponse->alert("El documento seleccionado ya se encuentra agregado");
        }
    }
	
	$hddTipoDocumento = $frmAnticipoNotaCreditoChequeTransferencia['hddTipoDocumento'];
	
	switch ($hddTipoDocumento) {
		case "FACTURA" :
			$queryDocumento = sprintf("SELECT DISTINCT
				cxc_fact.idFactura,
				cxc_fact.fechaRegistroFactura,
				cxc_fact.fechaVencimientoFactura,
				cxc_fact.numeroFactura,
				cxc_fact.numeroControl,
				cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
				cxc_fact.condicionDePago,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_ped_vent.id_pedido_venta
					WHEN 2 THEN	ped_vent.id_pedido
				END) AS id_pedido,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_ped_vent.id_pedido_venta_propio
					WHEN 2 THEN	ped_vent.numeracion_pedido
				END) AS numeracion_pedido,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_pres_vent.id_presupuesto_venta
					WHEN 2 THEN	pres_vent.id_presupuesto
				END) AS id_presupuesto,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_pres_vent.numeracion_presupuesto
					WHEN 2 THEN	pres_vent.numeracion_presupuesto
				END) AS numeracion_presupuesto,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				uni_fis.placa,
				ped_comp_det.flotilla,
				cxc_fact.estadoFactura,
				(CASE cxc_fact.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS descripcion_estado_factura,
				cxc_fact.aplicaLibros,
				cxc_fact.observacionFactura,
				cxc_fact.anulada,
				cxc_fact.saldoFactura,
				cxc_fact.montoTotalFactura,
				
				(IFNULL(cxc_fact.subtotalFactura, 0)
					- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
				IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
						WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_iva,
				(IFNULL(cxc_fact.subtotalFactura, 0)
					- IFNULL(cxc_fact.descuentoFactura, 0)
					+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
								WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
								WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0)) AS total,
				
				(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios FROM cj_cc_factura_detalle_accesorios fact_det_acc2
				WHERE fact_det_acc2.id_factura = cxc_fact.idFactura) AS cantidad_accesorios,
				cxc_fact.anulada,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM cj_cc_encabezadofactura cxc_fact
				LEFT JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (cxc_fact.idFactura = cxc_fact_det_acc.id_factura)
				LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
				INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
				LEFT JOIN iv_pedido_venta iv_ped_vent ON (cxc_fact.numeroPedido = iv_ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
				LEFT JOIN iv_presupuesto_venta iv_pres_vent ON (iv_ped_vent.id_presupuesto_venta = iv_pres_vent.id_presupuesto_venta)
				LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
				LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
				LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_fact.idFactura = %s;", 
				valTpDato($frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'], "int"));
			break;
		case "NOTA DEBITO" : 
			$queryDocumento = sprintf("SELECT
				cxc_nd.idNotaCargo AS idFactura,
				cxc_nd.fechaRegistroNotaCargo AS fechaRegistroFactura,
				cxc_nd.fechaVencimientoNotaCargo,
				cxc_nd.numeroNotaCargo AS numeroFactura,
				cxc_nd.numeroControlNotaCargo,
				cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				cxc_nd.estadoNotaCargo AS estadoFactura,
				(CASE cxc_nd.estadoNotaCargo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS descripcion_estado_factura,
				cxc_nd.aplicaLibros,
				cxc_nd.observacionNotaCargo AS observacionFactura,
				cxc_nd.montoTotalNotaCargo,
				cxc_nd.saldoNotaCargo,
				
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo,
				
				(IFNULL(cxc_nd.subtotalNotaCargo, 0)
					- IFNULL(cxc_nd.descuentoNotaCargo, 0)) AS total_neto,
				
				(IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
					+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)) AS total_iva,
				
				(IFNULL(cxc_nd.subtotalNotaCargo, 0)
					- IFNULL(cxc_nd.descuentoNotaCargo, 0)
					+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
						+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0))) AS total,
				
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM cj_cc_notadecargo cxc_nd
				INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
				LEFT JOIN pg_motivo motivo ON (cxc_nd.id_motivo = motivo.id_motivo)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_nd.idNotaCargo = %s;", 
				valTpDato($frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'], "int"));
			break;
		case "ANTICIPO" : 
			$queryDocumento = sprintf("SELECT
				cxc_ant.idAnticipo AS idFactura,
				cxc_ant.idCliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				cxc_ant.montoNetoAnticipo,
				cxc_ant.totalPagadoAnticipo,
				cxc_ant.saldoAnticipo,
				cxc_ant.fechaAnticipo AS fechaRegistroFactura,
				cxc_ant.numeroAnticipo AS numeroFactura,
				cxc_ant.idDepartamento AS id_modulo,
				cxc_ant.estadoAnticipo AS estadoFactura,
				(CASE cxc_ant.estadoAnticipo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
					WHEN 4 THEN 'No Cancelado (Asignado)'
				END) AS descripcion_estado_factura,
				cxc_ant.observacionesAnticipo AS observacionFactura,
				
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
					AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
				
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
				cxc_ant.estatus
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_ant.idAnticipo = %s;", 
				valTpDato($frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'], "int"));
			break;
	}
    $rsDocumento = mysql_query($queryDocumento);
    if (!$rsDocumento) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
    $rowDocumento = mysql_fetch_array($rsDocumento);
	
    $idDocumento = $frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'];	
    $txtMontoPagado = str_replace(",", "", $frmAnticipoNotaCreditoChequeTransferencia['txtMontoDocumento']);
	
	$Result1 = insertarItemDctoPagado($contFila, "", "", $hddTipoDocumento, $idDocumento, $rowDocumento['id_modulo'], date("Y-m-d"), $rowDocumento['nombre_empresa'], $rowDocumento['fechaRegistroFactura'], $rowDocumento['numeroFactura'], $rowDocumento['nombre_cliente'], $rowDocumento['estadoFactura'], $rowDocumento['descripcion_concepto_forma_pago'], $rowDocumento['observacionFactura'], $rowDocumento['descripcion_estado_factura'], $txtMontoPagado);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj4[] = $contFila;
	}
	
    $objResponse->script("calcularPagos();");
	
	$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	
	switch ($hddTipoDocumento) {
		case "FACTURA" :
			$objResponse->loadCommands(listaFacturaVenta(
				$frmListaAnticipo['pageNum'],
				$frmListaAnticipo['campOrd'],
				$frmListaAnticipo['tpOrd'],
				$frmListaAnticipo['valBusq']));
			break;
		case "NOTA DEBITO" :
			$objResponse->loadCommands(listaNotaDebito(
				$frmListaAnticipo['pageNum'],
				$frmListaAnticipo['campOrd'],
				$frmListaAnticipo['tpOrd'],
				$frmListaAnticipo['valBusq']));
			break;
		case "ANTICIPO" :
			$objResponse->loadCommands(listaAnticipo(
				$frmListaAnticipo['pageNum'],
				$frmListaAnticipo['campOrd'],
				$frmListaAnticipo['tpOrd'],
				$frmListaAnticipo['valBusq']));
			break;
	}

    return $objResponse;
}

function listaAnticipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
    $objResponse = new xajaxResponse();
	
	global $idModuloPpal;
    global $spanClienteCxC;

    $valCadBusq = explode("|", $valBusq);
    $startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)
	AND (cxc_ant.montoNetoAnticipo - cxc_ant.totalPagadoAnticipo) > 0",
		valTpDato($idModuloPpal, "campo"));
	
	// estadoAnticipo (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
    $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";	
    $sqlBusq .= $cond.sprintf("cxc_ant.estadoAnticipo IN (0,4) AND cxc_ant.estatus = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(cxc_ant.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
					WHERE suc.id_empresa_padre = cxc_ant.id_empresa)
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_ant.id_empresa)
			OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_ant.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[3] == "ANTICIPO") {
		if ($valCadBusq[2] == 1 && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_ant.idCliente = %s",
				valTpDato($valCadBusq[1], "int"));
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";	
		$sqlBusq .= $cond.sprintf("concepto_forma_pago.id_concepto IS NULL");
	} else if ($valCadBusq[3] == "ANTICIPO_OTRO") {
		if ($valCadBusq[2] == 2) { // 2 = Bono Suplidor
			$idConcepto = "6"; // 6 = Bono Suplidor
		} else if ($valCadBusq[2] == 3) { // 3 = PND
			$idConcepto = "7,8"; // 7 = PND Seguro, 8 = PND Garantia Extendida
		} else {
			usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarListaAnticipo').click();");
			return $objResponse->alert("Debe seleccionar tipo de transferencia Bono Suplidor o PND para agregar anticipos");
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";	
		$sqlBusq .= $cond.sprintf("concepto_forma_pago.id_concepto IN (%s)",
			valTpDato($idConcepto, "campo"));
	}
	
    if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_ant.idAnticipo NOT IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
    }
	
	if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_ant.fechaAnticipo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
	}
	
    if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";		
		$sqlBusq .= $cond.sprintf("(cxc_ant.numeroAnticipo LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
				AND cxc_pago.id_forma_pago IN (11)) LIKE %s
		OR cxc_ant.observacionesAnticipo LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
    }

    $query = sprintf("SELECT
		cxc_ant.idAnticipo,
		cxc_ant.idCliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		cxc_ant.montoNetoAnticipo,
		cxc_ant.totalPagadoAnticipo,
		IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, 0) AS saldoAnticipo,
		(cxc_ant.montoNetoAnticipo - cxc_ant.totalPagadoAnticipo) AS saldoPorCobrarAnticipo,
		cxc_ant.fechaAnticipo,
		cxc_ant.numeroAnticipo,
		cxc_ant.idDepartamento,
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
		END) AS descripcion_estado_anticipo,
		cxc_ant.observacionesAnticipo,
		
		(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
			AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		cxc_ant.estatus
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
		LEFT JOIN cj_cc_anticipo_concepto concepto_forma_pago ON (cxc_ant.idAnticipo = concepto_forma_pago.id_anticipo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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

    $htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
    $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "6%", $pageNum, "fechaAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "8%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "42%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "10%", $pageNum, "estadoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "10%", $pageNum, "saldoPorCobrarAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Por Cobrar");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "10%", $pageNum, "montoNetoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Anticipo");
		$htmlTh .= "<td colspan=\"3\"></td>";
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['idDepartamento']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgDctoModulo = $row['idDepartamento'];
		}
		
		if ($row['estatus'] == 0){ // 0 = ANULADO ; 1 = ACTIVO
			$imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Anticipo Anulado\"/>";
		} else if ($row['estatus'] == 1){ // 0 = ANULADO ; 1 = ACTIVO
			$imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Anticipo Activo\"/>";
		}
		
		switch($row['estadoAnticipo']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAgregar%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblAnticipoNotaCreditoChequeTransferencia', 'ANTICIPO', '%s');\"><button type=\"button\" title=\"%s\"><img src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$row['idAnticipo'],
					utf8_encode("Seleccionar"));
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaAnticipo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroAnticipo']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['observacionesAnticipo']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionesAnticipo'])."</span></td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".utf8_encode($row['descripcion_estado_anticipo'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoPorCobrarAnticipo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td colspan=\"2\">".number_format($row['montoNetoAnticipo'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				if ($row['totalPagadoAnticipo'] != $row['montoNetoAnticipo'] && $row['totalPagadoAnticipo'] > 0) {
					$htmlTb .= "<tr align=\"right\" class=\"textoNegrita_9px\">";
						$htmlTb .= "<td>Pagado:</td>";
						$htmlTb .= "<td width=\"100%\">".number_format($row['totalPagadoAnticipo'], 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
				}
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
					$row['idAnticipo']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[9] += $row['saldoPorCobrarAnticipo'];
		$arrayTotal[10] += $row['montoNetoAnticipo'];
    }
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">".("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[9] += $row['saldoPorCobrarAnticipo'];
				$arrayTotalFinal[10] += $row['montoNetoAnticipo'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">".("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[9], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[10], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"3\"></td>";
			$htmlTb .= "</tr>";
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
    }

    $objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

    return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaFacturaVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)
	AND cxc_fact.saldoFactura > 0
	AND cxc_fact.estadoFactura NOT IN (1)",
		valTpDato($idModuloPpal, "campo"));
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[2] == 1 && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
    if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idFactura NOT IN (%s) ",
			valTpDato($valCadBusq[4], "campo"));
    }
	
	if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s
		OR an_ped_vent.id_pedido LIKE %s
		OR an_ped_vent.numeracion_pedido LIKE %s
		OR pres_vent.id_presupuesto LIKE %s
		OR pres_vent.numeracion_presupuesto LIKE %s
		OR uni_fis.placa LIKE %s
		OR (CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cxc_fact.idFactura,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.fechaVencimientoFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
		cxc_fact.condicionDePago,
		cxc_fact.numeroPedido,
		an_ped_vent.id_pedido,
		an_ped_vent.numeracion_pedido,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		uni_fis.placa,
		ped_comp_det.flotilla,
		cxc_fact.estadoFactura,
		(CASE cxc_fact.estadoFactura
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_factura,
		cxc_fact.aplicaLibros,
		cxc_fact.observacionFactura,
		cxc_fact.anulada,
		cxc_fact.saldoFactura,
		cxc_fact.montoTotalFactura,
		
		(IFNULL(cxc_fact.subtotalFactura, 0)
			- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
		IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
				WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_iva,
		(IFNULL(cxc_fact.subtotalFactura, 0)
			- IFNULL(cxc_fact.descuentoFactura, 0)
			+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
						WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
			+ IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
						WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0)) AS total,
		
		(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios
		FROM cj_cc_factura_detalle_accesorios fact_det_acc2 WHERE fact_det_acc2.id_factura = cxc_fact.idFactura) AS cantidad_accesorios,
		cxc_fact.anulada,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) AS numero_pedido
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		RIGHT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		LEFT JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
		LEFT JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
		LEFT JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"5\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc.");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "LPAD(numero_pedido, 20, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "12%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "condicionDePago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "8%", $pageNum, "estadoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "8%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "cantidad_accesorios", $campOrd, $tpOrd, $valBusq, $maxRows, "Adicionales");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Por Cobrar");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		$imgDctoModuloCondicion = ($row['numeroPedido'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
			
		$imgEstatusPedido = ($row['anulada'] == "SI") ? "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>" : "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
		
		switch($row['id_modulo']) {
			case 2 : 
				switch ($row['flotilla']) {
					case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
					case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
					default : $imgEstatusUnidadAsignacion = "";
				}
				break;
			default : $imgEstatusUnidadAsignacion = "";
		}
		
		switch($row['estadoFactura']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
			
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAgregar%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblAnticipoNotaCreditoChequeTransferencia', 'FACTURA', '%s');\"><button type=\"button\" title=\"%s\"><img src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$row['idFactura'],
					utf8_encode("Seleccionar"));
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".$imgDctoModulo."</td>";
			$htmlTb .= "<td>".$imgDctoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaVencimientoFactura']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_pedido']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicionDePago'] == 1) ? "divMsjInfo" : "divMsjAlerta")."\">";
				$htmlTb .= ($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\"".$class.">".$row['descripcion_estado_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cantidad_accesorios'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				switch ($row['id_modulo']) {
					case 0 : // REPUESTOS
						$htmlTb .= sprintf("<a href=\"javascript:verVentana('../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>",
							$row['idFactura']);
						break;
					case 1 : // SERVICIOS
						$htmlTb .= sprintf("<a href=\"javascript:verVentana('../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>",
							$row['idFactura']);
						break;
					case 2 : // VEHICULOS
						$htmlTb .= sprintf("<a href=\"javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>",
							$row['idFactura']);
						break;
					case 3 : // ADMINISTRACION
						$htmlTb .= sprintf("<a href=\"javascript:verVentana('../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>",
							$row['idFactura']);
						break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/cjrs_recibo_pago_pdf.php?idTpDcto=1&id=%s', 960, 550);\" src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/>",
					$row['idFactura']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[16] += $row['cantidad_accesorios'];
		$arrayTotal[17] += $row['saldoFactura'];
		$arrayTotal[18] += $row['montoTotalFactura'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"15\">".("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[16], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[17], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[18], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"2\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[16] += $row['cantidad_accesorios'];
				$arrayTotalFinal[17] += $row['saldoFactura'];
				$arrayTotalFinal[18] += $row['montoTotalFactura'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"15\">".("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[16], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[17], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[18], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"3\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotaDebito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)
	AND cxc_nd.saldoNotaCargo > 0
	AND cxc_nd.estadoNotaCargo NOT IN (1)",
		valTpDato($idModuloPpal, "campo"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nd.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nd.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[2] == 1 && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
    if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.idNotaCargo NOT IN (%s) ",
			valTpDato($valCadBusq[4], "campo"));
    }
	
	if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.fechaRegistroNotaCargo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR cxc_nd.numeroNotaCargo LIKE %s
		OR cxc_nd.numeroControlNotaCargo LIKE %s
		OR cxc_nd.observacionNotaCargo LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_nd.idNotaCargo,
		cxc_nd.fechaRegistroNotaCargo,
		cxc_nd.fechaVencimientoNotaCargo,
		cxc_nd.numeroNotaCargo,
		cxc_nd.numeroControlNotaCargo,
		cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		cxc_nd.estadoNotaCargo,
		(CASE cxc_nd.estadoNotaCargo
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_nota_cargo,
		cxc_nd.aplicaLibros,
		cxc_nd.observacionNotaCargo,
		cxc_nd.montoTotalNotaCargo,
		cxc_nd.saldoNotaCargo,
		
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		
		(IFNULL(cxc_nd.subtotalNotaCargo, 0)
			- IFNULL(cxc_nd.descuentoNotaCargo, 0)) AS total_neto,
		
		(IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
			+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)) AS total_iva,
		
		(IFNULL(cxc_nd.subtotalNotaCargo, 0)
			- IFNULL(cxc_nd.descuentoNotaCargo, 0)
			+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
				+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0))) AS total,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
		LEFT JOIN pg_motivo motivo ON (cxc_nd.id_motivo = motivo.id_motivo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "fechaRegistroNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "fechaVencimientoNotaCargo",$campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "numeroNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "numeroControlNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "36%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "8%", $pageNum, "descripcion_estado_nota_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "8%", $pageNum, "saldoNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Por Cobrar");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "8%", $pageNum, "montoTotalNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Nota de Débito");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		switch($row['estadoNotaCargo']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAgregar%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblAnticipoNotaCreditoChequeTransferencia', 'NOTA DEBITO', '%s');\"><button type=\"button\" title=\"%s\"><img src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$row['idNotaCargo'],
					utf8_encode("Seleccionar"));
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaRegistroNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaVencimientoNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroNotaCargo']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroControlNotaCargo']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= ($row['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$row['id_motivo'].".- ".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['observacionNotaCargo']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionNotaCargo'])."</span></td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\"".$class.">".$row['descripcion_estado_nota_cargo']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoTotalNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Débito PDF\"/></a>",
					$row['idNotaCargo']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/cjrs_recibo_pago_pdf.php?idTpDcto=2&id=%s', 960, 550);\" src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/>",
					$row['idNotaCargo']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[13] += $row['saldoNotaCargo'];
		$arrayTotal[14] += $row['montoTotalNotaCargo'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">".("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[13] += $row['saldoNotaCargo'];
				$arrayTotalFinal[14] += $row['montoTotalNotaCargo'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">".("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"3\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuentaCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoTransferencia");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoDcto");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumentoPagar");
$xajax->register(XAJAX_FUNCTION,"guardarTransferencia");
$xajax->register(XAJAX_FUNCTION,"insertarDctoPagado");
$xajax->register(XAJAX_FUNCTION,"listaAnticipo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaFacturaVenta");
$xajax->register(XAJAX_FUNCTION,"listaNotaDebito");

function asignarBanco($idBanco) {
	$query = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s;", valTpDato($idBanco, "int"));
	$rs = mysql_query($query) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	return utf8_encode($row['nombreBanco']);
}

function asignarNumeroCuenta($idCuenta) {
	$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s;", valTpDato($idCuenta, "int"));
	$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	
	return $rowBuscarNumeroCuenta['numeroCuentaCompania'];
}

function insertarItemDctoPagado($contFila, $hddIdPago, $tablaPago, $hddTipoDocumento = "", $idDocumento = "", $idModulo = "", $txtFechaPago = "", $nombreEmpresa = "", $fechaRegistroFactura = "", $numeroFactura = "", $nombreCliente = "", $estadoFactura = "", $descripcionConceptoFormaPago = "", $observacionFactura = "", $descripcionEstadoFactura = "", $txtMontoPagado = "") {
	$contFila++;
	
	switch($idModulo) {
		case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
		case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
		case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
		case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
		case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
		default : $imgDctoModulo = $idModulo;
	}
	
	switch($estadoFactura) {
		case 0 : $class = "class=\"divMsjError\""; break;
		case 1 : $class = "class=\"divMsjInfo\""; break;
		case 2 : $class = "class=\"divMsjAlerta\""; break;
		case 3 : $class = "class=\"divMsjInfo3\""; break;
		case 4 : $class = "class=\"divMsjInfo4\""; break;
	}
	
	switch ($hddTipoDocumento) {
		case "FACTURA" :
			switch ($idModulo) {
				case 0 : // REPUESTOS
					$aVerDctoAux = sprintf("javascript:verVentana(\'../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s\', 960, 550);",
						$idDocumento);
					break;
				case 1 : // SERVICIOS
					$aVerDctoAux = sprintf("javascript:verVentana(\'../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s\', 960, 550);",
						$idDocumento);
					break;
				case 2 : // VEHICULOS
					$aVerDctoAux = sprintf("javascript:verVentana(\'../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s\', 960, 550);",
						$idDocumento);
					break;
				case 3 : // ADMINISTRACION
					$aVerDctoAux = sprintf("javascript:verVentana(\'../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s\', 960, 550);",
						$idDocumento);
					break;
			}
			$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>" : "";
			break;
		case "NOTA DEBITO" :
			$aVerDcto .= sprintf("<a href=\"javascript:verVentana(\'../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Débito PDF\"/></a>",
				$idDocumento);
			break;
		case "ANTICIPO" :
			if (in_array($idModulo,array(2,4))) {
				$aVerDctoAux = sprintf("javascript:verVentana(\'../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s\', 960, 550);",
					$idDocumento);
			} else if (in_array($idModulo,array(0,1,3))) {
				$aVerDctoAux = sprintf("javascript:verVentana(\'../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s\', 960, 550);",
					$idDocumento);
			}
			$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/print.png\" title=\"Recibo Anticipo PDF\"/></a>" : "";
			break;
	}
	
	$btnEliminar = (!($hddIdPago > 0 && $tablaPago != "")) ? "<button type=\"button\" onclick=\"validarEliminarDcto(this);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"></button>" : "";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieDctoPagado').before('".
		"<tr id=\"trItmDctoPagado:%s\" name=\"trItmDctoPagado\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td><input type=\"checkbox\" name=\"cbxDctoAgregado[]\" checked=\"checked\" style=\"display:none;\" value=\"%s|%s|%s|%s\"/>".
				"<input id=\"cbx4\" name=\"cbx4[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmDctoPago:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td>%s</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td>".
				"<table border=\"0\" width=\"%s\">".
				"<tr>".
					"<td nowrap=\"nowrap\">%s</td><td>%s</td>".
					"<td align=\"right\" width=\"%s\">%s</td>".
				"</tr>".
				"</table>".
			"</td>".
			"<td>".
				"<table border=\"0\" width=\"%s\">".
				"<tr>".
					"<td width=\"%s\">%s</td>".
					"%s".
					"%s".
				"</tr>".
				"</table>".
			"</td>".
			"<td align=\"center\" %s>%s</td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMontoPagado%s\" name=\"txtMontoPagado%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoDocumento%s\" name=\"hddTipoDocumento%s\" value=\"%s\"/></td>".
		"</tr>');",	
		$contFila, $clase,
			$hddIdPago, $hddTipoDocumento, $idDocumento, $txtMontoPagado,
				$contFila,
			$contFila, $contFila,
			date("d-m-Y",strtotime($txtFechaPago)),
			$nombreEmpresa,
			$hddTipoDocumento,
			date("d-m-Y",strtotime($fechaRegistroFactura)),
			"100%",
				$aVerDcto, $imgDctoModulo,
				"100%", $numeroFactura,
			"100%",
				"100%", utf8_encode($nombreCliente),
				((strlen($descripcionConceptoFormaPago) > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".utf8_encode($descripcionConceptoFormaPago)."</span></td></tr>" : ""),
				((strlen($observacionFactura) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($observacionFactura)."</span></td></tr>" : ""),
				$class, utf8_encode($descripcionEstadoFactura),
			$contFila, $contFila, number_format($txtMontoPagado, 2, ".", ","),
			$btnEliminar,
				$contFila, $contFila, $hddIdPago,
				$contFila, $contFila, $hddTipoDocumento);
	
	return array(true, $htmlItmPie, $contFila);
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