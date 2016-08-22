<?php


function asignarCliente($nombreObjeto, $idCliente, $idEmpresa, $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	WHERE id = %s
		AND id_empresa = %s
		AND status = 'Activo';",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));
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
		
		$fechaVencimiento = suma_fechas("Y-m-d",date("d-m-Y"),$rowClienteCredito['diascredito']);
		
		$objResponse->assign("txtDiasCreditoCliente","value",$rowClienteCredito['diascredito']);
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
	} else {
		$fechaVencimiento = date("d-m-Y");
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
	}
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowCliente['id']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",utf8_encode($rowCliente['direccion']));
	$objResponse->assign("txtTelefonos".$nombreObjeto,"value",utf8_encode($rowCliente['telf']));
	$objResponse->assign("txtRif".$nombreObjeto,"value",utf8_encode($rowCliente['ci_cliente']));
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	}
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $nombreObjeto, $asigDescuento = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor
	WHERE id_proveedor = %s",
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowProv['nombre']));
	$objResponse->assign("txtRif".$nombreObjeto,"value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContacto".$nombreObjeto,"value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$nombreObjeto,"value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonos".$nombreObjeto,"value",$rowProv['telefono']);
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	}
	
	return $objResponse;
}

function asignarNotaCargo($idNotaCargo, $frmTradeInCxP, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// BUSCA EL ID DEL DOCUMENTO DEL an_tradein_cxp A MODIFICAR
	$query = sprintf("SELECT * FROM an_tradein_cxp WHERE id_tradein_cxp = %s;",
		valTpDato($frmTradeInCxP['hddIdTradeInCxP'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	($row['id_nota_cargo_cxp'] > 0) ? $arrayIdNotaCargo[] = array($row['id_nota_cargo_cxp'], "Ant") : "";
	($idNotaCargo > 0) ? $arrayIdNotaCargo[] = array($idNotaCargo, "") : "";
	
	foreach ($arrayIdNotaCargo as $indice => $valor) {
		$idNotaCargo = $arrayIdNotaCargo[$indice][0];
		$nombreObjeto = $arrayIdNotaCargo[$indice][1];
		
		$query = sprintf("SELECT 
			cxp_nd.id_notacargo,
			cxp_nd.numero_notacargo,
			cxp_nd.numero_control_notacargo,
			cxp_nd.fecha_notacargo,
			cxp_nd.fecha_origen_notacargo,
			cxp_nd.fecha_vencimiento_notacargo,
			prov.id_proveedor,
			CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
			prov.nombre AS nombre_proveedor,
			cxp_nd.id_modulo,
			motivo.id_motivo,
			motivo.descripcion AS descripcion_motivo,
			cxp_nd.estatus_notacargo,
			cxp_nd.observacion_notacargo,
			cxp_nd.total_cuenta_pagar,
			cxp_nd.saldo_notacargo,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cp_notadecargo cxp_nd
			INNER JOIN cp_proveedor prov ON (cxp_nd.id_proveedor = prov.id_proveedor)
			LEFT JOIN pg_motivo motivo ON (cxp_nd.id_motivo = motivo.id_motivo)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxp_nd.id_notacargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdNotaCargo".$nombreObjeto,"value",$row['id_notacargo']);
		$objResponse->assign("txtNumeroNotaCargo".$nombreObjeto,"value",utf8_encode($row['numero_notacargo']));
		$objResponse->assign("txtFechaRegistroNotaCargo".$nombreObjeto,"value",date("d-m-Y",strtotime($row['fecha_origen_notacargo'])));
		$objResponse->assign("txtIdProvTradeInCxP".$nombreObjeto,"value",utf8_encode($row['id_proveedor']));
		$objResponse->assign("txtNombreProvTradeInCxP".$nombreObjeto,"value",utf8_encode($row['nombre_proveedor']));
		$objResponse->assign("txtTotalOrdenTradeInCxP".$nombreObjeto,"value",number_format($row['total_cuenta_pagar'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	}
	
	return $objResponse;
}

function asignarMotivo($idMotivo, $nombreObjeto, $cxPcxC = NULL, $ingresoEgreso = NULL, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	if ($cxPcxC != "-1" && $cxPcxC != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("modulo LIKE %s",
			valTpDato($cxPcxC, "text"));
	}
	
	if ($ingresoEgreso != "-1" && $ingresoEgreso != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ingreso_egreso LIKE %s",
			valTpDato($ingresoEgreso, "text"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_motivo = %s",
		valTpDato($idMotivo, "int"));
	
	$query = sprintf("SELECT * FROM pg_motivo %s;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$row['id_motivo']);
	$objResponse->assign("txt".$nombreObjeto,"value",utf8_encode($row['descripcion']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	}
	
	return $objResponse;
}

function asignarTipoVale($idTipoVale) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("txtIdCliente","value","");
	$objResponse->assign("txtNombreCliente","value","");
	$objResponse->assign("hddIdDcto","value","");
	$objResponse->assign("txtNroDcto","value","");
	$idTipoVale = 1;
	if ($idTipoVale == 1) { // DE ENTRADA O SALIDA
		$objResponse->script("
		byId('txtIdCliente').className = 'inputHabilitado';
		//byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		//byId('lstTipoMovimiento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputCompletoHabilitado';
		
		byId('txtIdCliente').readOnly = false;
		byId('btnListarCliente').style.display = '';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value, '', '5,6');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
	} else if ($idTipoVale == 3) { // DE NOTA DE CREDITO DE CxC
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('asignarTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputHabilitado';
		byId('lstTipoMovimiento').className = 'inputInicial';
		byId('txtObservacion').className = 'inputCompletoHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = '';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id,2);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value, '', '3');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",2);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", 2, "", "3"));
	} else {
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		byId('lstTipoMovimiento').className = 'inputInicial';
		byId('txtObservacion').className = 'inputCompletoHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		//byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = true;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value);
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", -2));
	}
	
	return $objResponse;
}

function asignarUnidadBasica($nombreObjeto, $idUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
	FROM an_uni_bas uni_bas
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	WHERE id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtClaveUnidadBasica".$nombreObjeto, "value", utf8_encode($row['clv_uni_bas']));
	$objResponse->assign("txtDescripcion".$nombreObjeto, "value", utf8_encode($row['des_uni_bas']));
	$objResponse->assign("hddIdMarcaUnidadBasica".$nombreObjeto,"value",$row['id_marca']);
	$objResponse->assign("txtMarcaUnidadBasica".$nombreObjeto,"value",utf8_encode($row['nom_marca']));
	$objResponse->assign("hddIdModeloUnidadBasica".$nombreObjeto,"value",$row['id_modelo']);
	$objResponse->assign("txtModeloUnidadBasica".$nombreObjeto,"value",utf8_encode($row['nom_modelo']));
	$objResponse->assign("hddIdVersionUnidadBasica".$nombreObjeto,"value",$row['id_version']);
	$objResponse->assign("txtVersionUnidadBasica".$nombreObjeto,"value",utf8_encode($row['nom_version']));
	
	return $objResponse;
}

function asignarVehiculo($idUnidadFisica){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		id_unidad_fisica,
		serial_carroceria,
		serial_motor,
		serial_chasis,
		placa,
		estado_compra,
		precio_compra
	FROM an_unidad_fisica 
	WHERE id_unidad_fisica = %s
	LIMIT 1 ",
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtIdUnidadFisicaAjuste","value",$row['id_unidad_fisica']);
	$objResponse->assign("txtPlacaAjuste","value",$row['placa']);
	$objResponse->assign("txtSerialCarroceriaAjuste","value",$row['serial_carroceria']);
	$objResponse->assign("txtSerialMotorAjuste","value",$row['serial_motor']);
	$objResponse->assign("txtNumeroVehiculoAjuste","value",$row['serial_chasis']);
	$objResponse->assign("txtEstadoVenta","value",utf8_encode($row['estado_venta']));
	$objResponse->assign("txtAllowance","value",$row['precio_compra']);
	$objResponse->assign("txtAllowanceAnt","value",$row['precio_compra']);
	$objResponse->assign("txtCreditoNeto","value",$row['precio_compra']);
	$objResponse->assign("txtCreditoNetoAnt","value",$row['precio_compra']);
	$objResponse->assign("txtMontoAnticipo","value",$row['precio_compra']);
	$objResponse->assign("txtSubTotal","value",$row['precio_compra']);
	
	$objResponse->script("
	calcularMonto();
	byId('imgCerrarDivFlotante3').click();");
	
	return $objResponse;
}

function buscarCarroceria($frmAjusteInventario) {
	$objResponse = new xajaxResponse();
	
	$idUnidadFisica = $frmAjusteInventario['txtIdUnidadFisicaAjuste'];
	
	// VERIFICA QUE NO EXISTA EL SERIAL DEL CHASIS, CARROCERIA Y MOTOR
	$query = sprintf("SELECT * FROM an_unidad_fisica
	WHERE (serial_carroceria LIKE %s)
		AND estatus = 1
		AND (id_unidad_fisica <> %s OR %s IS NULL)
		AND estado_venta IN ('VENDIDO','ENTREGADO');", // OR serial_motor LIKE %s OR serial_chasis LIKE %s
		valTpDato($frmAjusteInventario['txtSerialCarroceriaAjuste'], "text")/*,
		valTpDato($frmAjusteInventario['txtSerialMotorAjuste'], "text"),
		valTpDato($frmAjusteInventario['txtNumeroVehiculoAjuste'], "text")*/,
		valTpDato($idUnidadFisica, "int"),
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		if ($frmAjusteInventario['cbxAsignarUnidadFisica'] == 1) {
			$objResponse->assign("txtIdUnidadFisicaAjuste","value",$row['id_unidad_fisica']);
			$objResponse->loadCommands(cargaLstUnidadBasica($row['id_uni_bas']));
			$objResponse->loadCommands(asignarUnidadBasica('Ajuste', $row['id_uni_bas']));
			$objResponse->loadCommands(cargaLstAno($row['ano']));
			$objResponse->loadCommands(cargaLstCondicion($row['id_condicion_unidad']));
			$objResponse->assign("txtFechaFabricacionAjuste","value",date("d-m-Y",strtotime($row['fecha_fabricacion'])));
			$objResponse->assign("txtKilometraje","value",$row['kilometraje']);
			
			$objResponse->loadCommands(cargaLstColor("lstColorExterno1",$row['id_color_externo1']));
			$objResponse->loadCommands(cargaLstColor("lstColorInterno1",$row['id_color_interno1']));
			$objResponse->loadCommands(cargaLstColor("lstColorExterno2",$row['id_color_externo2']));
			$objResponse->loadCommands(cargaLstColor("lstColorInterno2",$row['id_color_interno2']));
			
			$objResponse->assign("txtPlacaAjuste","value",$row['placa']);
			$objResponse->assign("txtSerialCarroceriaAjuste","value",$row['serial_carroceria']);
			$objResponse->assign("txtSerialMotorAjuste","value",$row['serial_motor']);
			$objResponse->assign("txtNumeroVehiculoAjuste","value",$row['serial_chasis']);
			$objResponse->assign("txtRegistroLegalizacionAjuste","value",$row['registro_legalizacion']);
			$objResponse->assign("txtRegistroFederalAjuste","value",$row['registro_federal']);
			
			$objResponse->script("
			byId('txtSerialCarroceriaAjuste').className = '';
			byId('txtSerialCarroceriaAjuste').readOnly = true;");
		} else {
			$objResponse->script("byId('trAsignarUnidadFisica').style.display = '';");
			
			$objResponse->assign("txtIdUnidadFisicaAjuste","value","");
		}
	} else {
		if (!($frmAjusteInventario['cbxAsignarUnidadFisica'] == 1)) {
			if (!($idUnidadFisica > 0)) {
				$objResponse->script("byId('trAsignarUnidadFisica').style.display = 'none';");
			}
			
			$objResponse->assign("txtIdUnidadFisicaAjuste","value","");
		}
	}
	
	/*$objResponse->script("
	byId('txtSerialCarroceriaAjuste').className = 'inputCompletoHabilitado';");*/
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmAjusteInventario) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmAjusteInventario['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente'],
		$frmBuscarCliente['hddObjDestinoCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarProveedor['hddObjDestinoProveedor'],
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarMotivo($frmBuscarMotivo) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarMotivo['hddObjDestinoMotivo'],
		$frmBuscarMotivo['hddPagarCobrarMotivo'],
		$frmBuscarMotivo['hddIngresoEgresoMotivo'],
		$frmBuscarMotivo['txtCriterioBuscarMotivo']);
	
	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarNotaCargo($frmBuscarNotaCargo, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscarNotaCargo['txtCriterioBuscarNotaCargo']);
	
	$objResponse->loadCommands(listaNotaCargo(0, "cxp_nd.id_notacargo", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		implode(",",$frmBuscar['lstEstadoCompraBuscar']),
		implode(",",$frmBuscar['lstEstadoVentaBuscar']),
		implode(",",$frmBuscar['lstAlmacen']),
		$frmBuscar['txtCriterio'],
				$frmBuscar['lstAnuladoTradein'],
				$frmBuscar['txtFechaDesde'],
				$frmBuscar['txtFechaHasta']);
	
	$objResponse->loadCommands(listaUnidadFisica(0, "CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarVehiculo($txtCriterio){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$txtCriterio);
	
	$objResponse->loadCommands(listaVehiculos(0, "uni_fis.id_unidad_fisica", "DESC", $valBusq));
	
	return $objResponse;
}

function calcularTradeIn($frmAjusteInventario) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAjusteInventario['cbx'];
	
	$txtCreditoNeto = str_replace(",", "", $frmAjusteInventario['txtCreditoNeto']);
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtMontoPago = str_replace(",", "", $frmAjusteInventario['txtMontoPago'.$valor]);
			$txtMontoAnticipo = str_replace(",", "", $frmAjusteInventario['txtMontoAnticipo'.$valor]);
			$txtMontoAnticipo = ($txtMontoPago > 0) ? $txtMontoPago : $txtMontoAnticipo;
			
			$objResponse->assign("txtMontoAnticipo".$valor,"value",$txtMontoAnticipo);
			$objResponse->assign("hddMontoAnticipo".$valor,"value",$txtMontoAnticipo);
			
			$txtTotalPoliza += $txtMontoAnticipo;
		}
	}
	
	$objResponse->assign("txtTotalPoliza","value",number_format($txtTotalPoliza, 2, ".", ","));
	$objResponse->assign("txtTotalCredito","value",number_format($txtCreditoNeto + $txtTotalPoliza, 2, ".", ","));
	
	if (str_replace(",", "", $frmAjusteInventario['hddMontoCxP']) > 0) {
		$txtObservacionCxP = "NOTA DE DEBITO POR TRADE-IN, CLIENTE ".$frmAjusteInventario['txtNombreCliente']." PRESENTABA BALANCE ADEUDADO SOBRE EL AUTO USADO.".
		((strlen($frmAjusteInventario['txtSerialCarroceriaAjuste']) > 0) ? " ".$spanSerialCarroceria.": ".$frmAjusteInventario['txtSerialCarroceriaAjuste'] : "").
		((strlen($frmAjusteInventario['txtPlacaAjuste'])) ? " ".$spanPlaca.": ".$frmAjusteInventario['txtPlacaAjuste'] : "").
		(($frmAjusteInventario['hddIdTradeInAjusteInventario'] > 0) ? " AJUSTE POSTERIOR AL REGISTRO DEL TRADE-IN" : "");
	} else if (str_replace(",", "", $frmAjusteInventario['hddMontoCxP']) < 0) {
		$txtObservacionCxP = "NOTA DE CREDITO POR TRADE-IN, CLIENTE ".$frmAjusteInventario['txtNombreCliente']." PRESENTABA BALANCE ADEUDADO SOBRE EL AUTO USADO.".
		((strlen($frmAjusteInventario['txtSerialCarroceriaAjuste']) > 0) ? " ".$spanSerialCarroceria.": ".$frmAjusteInventario['txtSerialCarroceriaAjuste'] : "").
		((strlen($frmAjusteInventario['txtPlacaAjuste'])) ? " ".$spanPlaca.": ".$frmAjusteInventario['txtPlacaAjuste'] : "").
		(($frmAjusteInventario['hddIdTradeInAjusteInventario'] > 0) ? " AJUSTE POSTERIOR AL REGISTRO DEL TRADE-IN" : "");
	}
	
	if (str_replace(",", "", $frmAjusteInventario['hddMontoCxC']) > 0) {
		$txtObservacionCxC = "NOTA DE DEBITO POR TRADE-IN, ";
		if (str_replace(",", "", $frmAjusteInventario['txtAcvAnt']) > 0
		&& str_replace(",", "", $frmAjusteInventario['txtAcv']) != str_replace(",", "", $frmAjusteInventario['txtAcvAnt'])) {
			$txtObservacionCxC .= "ACTUALIZACION DEL CREDITO OTORGADO.";
		} else if (str_replace(",", "", $frmAjusteInventario['txtPayoffAnt']) > 0
		&& str_replace(",", "", $frmAjusteInventario['txtPayoff']) != str_replace(",", "", $frmAjusteInventario['txtPayoffAnt'])) {
			$txtObservacionCxC .= "ACTUALIZACION DEL BALANCE ADEUDADO.";
		} else if (str_replace(",", "", $frmAjusteInventario['txtAllowance']) > 0
		&& str_replace(",", "", $frmAjusteInventario['txtAllowance']) != str_replace(",", "", $frmAjusteInventario['txtAllowanceAnt'])) {
			$txtObservacionCxC .= "ACTUALIZACION DEL CREDITO OTORGADO.";
		} else {
			$txtObservacionCxC .= "BALANCE ADEUDADO ES MENOR AL CRÉDITO POR AUTO USADO.";
		}
		$txtObservacionCxC .= ((strlen($frmAjusteInventario['txtSerialCarroceriaAjuste']) > 0) ? " ".$spanSerialCarroceria.": ".$frmAjusteInventario['txtSerialCarroceriaAjuste'] : "").
			((strlen($frmAjusteInventario['txtPlacaAjuste'])) ? " ".$spanPlaca.": ".$frmAjusteInventario['txtPlacaAjuste'] : "").
			(($frmAjusteInventario['hddIdTradeInAjusteInventario'] > 0) ? " AJUSTE POSTERIOR AL REGISTRO DEL TRADE-IN" : "");
	} else if (str_replace(",", "", $frmAjusteInventario['hddMontoCxC']) < 0) {
		$txtObservacionCxC = "NOTA DE CREDITO POR TRADE-IN, ";
		if (str_replace(",", "", $frmAjusteInventario['txtAcvAnt']) > 0
		&& str_replace(",", "", $frmAjusteInventario['txtAcv']) != str_replace(",", "", $frmAjusteInventario['txtAcvAnt'])) {
			$txtObservacionCxC .= "ACTUALIZACION DEL CREDITO OTORGADO.";
		} else if (str_replace(",", "", $frmAjusteInventario['txtPayoffAnt']) > 0
		&& str_replace(",", "", $frmAjusteInventario['txtPayoff']) != str_replace(",", "", $frmAjusteInventario['txtPayoffAnt'])) {
			$txtObservacionCxC .= "ACTUALIZACION DEL BALANCE ADEUDADO.";
		} else if (str_replace(",", "", $frmAjusteInventario['txtAllowance']) > 0
		&& str_replace(",", "", $frmAjusteInventario['txtAllowance']) != str_replace(",", "", $frmAjusteInventario['txtAllowanceAnt'])) {
			$txtObservacionCxC .= "ACTUALIZACION DEL CREDITO OTORGADO.";
		} else {
			$txtObservacionCxC .= "BALANCE ADEUDADO SUPERA AL CRÉDITO POR AUTO USADO.";
		}
		$txtObservacionCxC .= ((strlen($frmAjusteInventario['txtSerialCarroceriaAjuste']) > 0) ? " ".$spanSerialCarroceria.": ".$frmAjusteInventario['txtSerialCarroceriaAjuste'] : "").
			((strlen($frmAjusteInventario['txtPlacaAjuste'])) ? " ".$spanPlaca.": ".$frmAjusteInventario['txtPlacaAjuste'] : "").
			(($frmAjusteInventario['hddIdTradeInAjusteInventario'] > 0) ? " AJUSTE POSTERIOR AL REGISTRO DEL TRADE-IN" : "");
	}
	
	$objResponse->assign("hddObservacionCxP","value",$txtObservacionCxP);
	$objResponse->assign("hddObservacionCxC","value",$txtObservacionCxC);
	
	return $objResponse;
}

function cargaLstAlmacen($nombreObjeto, $idEmpresa, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : " onchange=\"byId('btnBuscar').click();\"";
	
	$query = sprintf("SELECT * FROM an_almacen alm WHERE alm.id_empresa = %s ORDER BY alm.nom_almacen",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".utf8_encode($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstAno($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = "SELECT id_ano, nom_ano FROM an_ano ORDER BY nom_ano DESC";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAno\" name=\"lstAno\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['id_ano']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=".$row['id_ano'].">".htmlentities($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAno","innerHTML",$html);
	
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
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
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
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\"  ".$accion." style=\"width:99%\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s AND id_clave_movimiento = 82 ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstColor($nombreObjeto, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM an_color ORDER BY nom_color");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_color']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_color']."\">".htmlentities($row['nom_color'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCondicion($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM an_condicion_unidad ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstCondicion\" name=\"lstCondicion\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_condicion_unidad'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_condicion_unidad']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCondicion","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoCompraBuscar($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste") ? $array[] = "ALTA" : "";
	($accion != "Ajuste") ? $array[] = "IMPRESO" : "";
	$array[] = "COMPRADO";
	$array[] = "REGISTRADO";
	($accion != "Ajuste") ? $array[] = "CANCELADO" : "";
	
	$html = "<select ".((count($array) > 2) ? "multiple": "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice] || count($array) == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoVenta($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	//($accion != "Ajuste" && $accion != "Venta") ? $array[] = "TRANSITO" : "";
	//($accion != "Ajuste" && $accion != "Venta") ? $array[] = "POR REGISTRAR" : "";
	//($accion != "Ajuste") ? $array[] = "SINIESTRADO" : "";
	$array[] = "DISPONIBLE";
	//($accion != "Ajuste" && $accion != "Venta") ? $array[] = "RESERVADO" : "";
	//($accion != "Ajuste" && $accion != "Venta") ? $array[] = "VENDIDO" : "";
	//($accion != "Ajuste" && $accion != "Venta") ? $array[] = "ENTREGADO" : "";
	//($accion != "Venta") ? $array[] = "PRESTADO" : "";
	//($accion != "Venta") ? $array[] = "ACTIVO FIJO" : "";
	//($accion != "Venta") ? $array[] = "INTERCAMBIO" : "";
	//($accion != "Venta") ? $array[] = "DEVUELTO" : "";
	//($accion != "Venta") ? $array[] = "ERROR EN TRASPASO" : "";
	
	if($selId == ''){
		$inputHabilitado = "class=\"inputHabilitado\"";
	}
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$inputHabilitado." style=\"width:99%\">";
	if ($selId == '') { //nuevo
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		foreach ($array as $indice => $valor) {
			$selected = ($selId == $array[$indice] || count($array) == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
		}
	} else { //cargando
		$html .= "<option selected=\"selected\" value=\"".$selId."\">".$selId."</option>";//solo mostrar
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoVentaBuscar($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "TRANSITO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "POR REGISTRAR" : "";
	($accion != "Ajuste") ? $array[] = "SINIESTRADO" : "";
	$array[] = "DISPONIBLE";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "RESERVADO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "VENDIDO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "ENTREGADO" : "";
	($accion != "Venta") ? $array[] = "PRESTADO" : "";
	($accion != "Venta") ? $array[] = "ACTIVO FIJO" : "";
	($accion != "Venta") ? $array[] = "INTERCAMBIO" : "";
	($accion != "Venta") ? $array[] = "DEVUELTO" : "";
	($accion != "Venta") ? $array[] = "ERROR EN TRASPASO" : "";
	
	$html = "<select ".((count($array) > 2) ? "multiple": "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice] || count($array) == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMoneda($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['idmoneda']) {
			$selected = "selected=\"selected\"";
		} else if ($row['predeterminada'] == 1 && $selId == "") {
			$selected = "selected=\"selected\"";
		}
		
		$html .= "<option ".$selected." value=\"".$row['idmoneda']."\">".utf8_encode($row['descripcion']." (".$row['abreviacion'].")")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstPaisOrigen($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_origen ORDER BY nom_origen");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstPaisOrigen\" name=\"lstPaisOrigen\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_origen'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_origen']."\">".utf8_encode($row['nom_origen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstPaisOrigen","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstProveedorTradeInCxP($idTradeIn, $selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT DISTINCT prov.id_proveedor, prov.nombre
	FROM an_tradein_cxp tradein_cxp
		INNER JOIN cp_notadecargo cxp_nd ON (tradein_cxp.id_nota_cargo_cxp = cxp_nd.id_notacargo)
		INNER JOIN cp_proveedor prov ON (cxp_nd.id_proveedor = prov.id_proveedor)
	WHERE tradein_cxp.id_tradein = %s
		AND tradein_cxp.estatus = 1
	ORDER BY nombre",
		valTpDato($idTradeIn, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstProveedorTradeInCxP\" name=\"lstProveedorTradeInCxP\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_proveedor'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_proveedor']."\">".utf8_encode($row['nombre'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstProveedorTradeInCxP","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoMovimiento($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
	
//	$array = array(
//		1 => "COMPRA",
//		2 => "ENTRADA",
//		3 => "VENTA",
//		4 => "SALIDA");
	$array = array(
		2 => "ENTRADA");
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" style=\"width:99%\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $indice || count($array) == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".$indice.".- ".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);	
	
	return $objResponse;
}

function cargaLstUnidadBasica($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_asignarUnidadBasica('Ajuste', this.value);\"";
	
	$query = sprintf("SELECT * FROM an_uni_bas ORDER BY nom_uni_bas");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstUnidadBasica\" name=\"lstUnidadBasica\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uni_bas']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_uni_bas']."\">".htmlentities($row['nom_uni_bas'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUnidadBasica","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstUso($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_uso ORDER BY nom_uso");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstUso\" name=\"lstUso\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uso']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$row['id_uso']."\" ".$selected.">".htmlentities($row['nom_uso'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUso","innerHTML",$html);
	
	return $objResponse;
}

function eliminarPoliza($frmAjusteInventario) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmAjusteInventario['cbxItm'])) {
		foreach ($frmAjusteInventario['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarPoliza(xajax.getFormValues('frmAjusteInventario'));");
	}
	
	return $objResponse;
}

function formAjusteInventario($frmUnidadFisica, $frmAjusteInventario, $existeUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAjusteInventario['cbx'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	if (!xvalidaAcceso($objResponse,"an_tradein_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();"); return $objResponse; }
	
	if ($existeUnidadFisica == 1) {
		$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
	} else {
		$idUnidadFisica = "";
	}
	
	// BUSCA LOS DATOS DE LA UNIDAD
	$query = sprintf("SELECT 
		alm.id_empresa,
		uni_fis.costo_compra,
		uni_fis.precio_compra
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
		INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
		LEFT JOIN an_color color_ext2 ON (uni_fis.id_color_externo2 = color_ext2.id_color)
		LEFT JOIN an_color color_int2 ON (uni_fis.id_color_interno2 = color_int2.id_color)
		LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
		INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
		INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
		INNER JOIN an_combustible comb ON (uni_bas.com_uni_bas = comb.id_combustible)
	WHERE uni_fis.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($frmUnidadFisica['hddEstadoVenta'] == "DISPONIBLE" && $frmUnidadFisica['lstEstadoVenta'] != "DISPONIBLE") {
		$lstTipoMovimiento = 4;
		$documentoGenera = 5; // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
	} else if ($frmUnidadFisica['hddEstadoVenta'] != "DISPONIBLE" && $frmUnidadFisica['lstEstadoVenta'] == "DISPONIBLE") {
		$lstTipoMovimiento = 2;
		$documentoGenera = 6; // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
	}
	
	if ($existeUnidadFisica == 1) {
		$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "Empresa", "ListaEmpresa", "", false));
		
		$objResponse->call("selectedOption","lstTipoVale",1);
		$objResponse->loadCommands(asignarTipoVale(1));
		$objResponse->loadCommands(cargaLstTipoMovimiento("lstTipoMovimiento", $lstTipoMovimiento));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", 2, $lstTipoMovimiento, "", $documentoGenera));
		$objResponse->assign("txtIdUnidadFisicaAjuste","value",$idUnidadFisica);
		$objResponse->assign("txtEstadoVenta","value",$frmUnidadFisica['lstEstadoVenta']);
		$objResponse->assign("txtSubTotal","value",number_format($row['precio_compra'], 2, ".", ","));
		
		$objResponse->script("
		byId('lstTipoVale').onchange = function() {
			selectedOption(this.id, ".(1).");
		}
		byId('txtSubTotal').readOnly = true;");
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id, ".$lstTipoMovimiento.");
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', 2, this.value, '', ".$documentoGenera.");
		}");
	} else {
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));"));
		$objResponse->assign("txtFecha","value",date("d-m-Y"));
		$objResponse->assign("hddIdEmpleado","value",$rowUsuario['id_empleado']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']));
		
		$objResponse->loadCommands(asignarTipoVale(""));
		$objResponse->loadCommands(cargaLstTipoMovimiento("lstTipoMovimiento", $lstTipoMovimiento));
		
		$objResponse->loadCommands(cargaLstUnidadBasica());
		
		$objResponse->loadCommands(cargaLstAno());
		$objResponse->loadCommands(cargaLstCondicion());
		
		$objResponse->loadCommands(cargaLstColor("lstColorExterno1"));
		$objResponse->loadCommands(cargaLstColor("lstColorInterno1"));
		$objResponse->loadCommands(cargaLstColor("lstColorExterno2"));
		$objResponse->loadCommands(cargaLstColor("lstColorInterno2"));
		
		$objResponse->loadCommands(cargaLstPaisOrigen());
		$objResponse->loadCommands(cargaLstUso());
		
		$objResponse->loadCommands(cargaLstAlmacen('lstAlmacenAjuste', $_SESSION['idEmpresaUsuarioSysGts']));
		$objResponse->assign("txtEstadoCompraAjuste","value","REGISTRADO");
		$objResponse->loadCommands(cargaLstEstadoVenta("lstEstadoVentaAjuste", "Ajuste"));
		$objResponse->loadCommands(cargaLstMoneda());
		
		$objResponse->script("
		byId('lstTipoVale').onchange = function() {
			xajax_asignarTipoVale(this.value);
		}");
	}
		
	$objResponse->script("calcularMonto();");
	
	return $objResponse;
}

function formEditarTradeIn($idTradeIn) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_tradein_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();"); return $objResponse; }
	
	$query = sprintf("SELECT 
		tradein.id_tradein,
		tradein.id_anticipo,
		tradein.id_unidad_fisica,
		tradein.allowance,
		tradein.payoff,
		tradein.acv,
		tradein.total_credito,
		tradein.id_proveedor,
		cxc_ant.montoNetoAnticipo,
		cxc_ant.numeroAnticipo,
		cxc_ant.idDepartamento,
		cxc_ant.id_empresa,
		cxc_ant.observacionesAnticipo,
		pg_modulos.descripcionModulo,
		cliente.id,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
		cxc_pago.id_concepto,
		cj_conceptos_formapago.descripcion,
		formapagos.idFormaPago,
		formapagos.nombreFormaPago,
		(SELECT cp_proveedor.nombre FROM cp_proveedor WHERE cp_proveedor.id_proveedor = tradein.id_proveedor) as nombre_cliente_adeudado
	FROM an_tradein tradein
		INNER JOIN cj_cc_anticipo cxc_ant ON (tradein.id_anticipo = cxc_ant.idAnticipo)
		INNER JOIN pg_modulos ON (cxc_ant.idDepartamento = pg_modulos.id_modulo)
		INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		INNER JOIN cj_conceptos_formapago ON (cxc_pago.id_concepto = cj_conceptos_formapago.id_concepto)
		INNER JOIN formapagos ON (cj_conceptos_formapago.id_formapago = formapagos.idFormaPago)
	WHERE id_tradein = %s
	LIMIT 1;",
		valTpDato($idTradeIn, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$idAnticipo = $row['id_anticipo'];
	
	// BUSCA LOS DATOS DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
	$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo
	WHERE idAnticipo = %s
		AND estadoAnticipo IN (2,3,4)",
		valTpDato($idAnticipo, "int"));
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
	
	// SI EL ANTICIPO DEL TRADE-IN YA FUE UTILIZADO
	/*if ($totalRowsAnticipo > 0) {
		$objResponse->script("
		byId('txtAcv').className = 'inputInicial';
		byId('txtAcv').readOnly = true;");
	} else {*/
		$objResponse->script("
		byId('txtAllowance').className = 'inputHabilitado';
		byId('txtAcv').className = 'inputHabilitado';
		byId('txtPayoff').className = 'inputHabilitado';
		byId('txtSubTotal').className = 'inputCompleto';
		byId('txtAllowance').readOnly = false;
		byId('txtAcv').readOnly = false;");
	//}
	
	$idEmpresa = $row['id_empresa'];
	
	$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "", false));
	$objResponse->loadCommands(asignarCliente("Cliente", $row['id'], $idEmpresa));
	
	$objResponse->assign("hddIdTradeInAjusteInventario","value",$row['id_tradein']);
	
	$objResponse->assign("txtAllowance","value",number_format($row['allowance'], 2, ".", ","));
	$objResponse->assign("txtAllowanceAnt","value",number_format($row['allowance'], 2, ".", ","));
	$objResponse->assign("txtPayoff","value",number_format($row['payoff'], 2, ".", ","));
	$objResponse->assign("txtPayoffAnt","value",number_format($row['payoff'], 2, ".", ","));
	$objResponse->assign("txtAcv","value",number_format($row['acv'], 2, ".", ","));
	$objResponse->assign("txtAcvAnt","value",number_format($row['acv'], 2, ".", ","));
	$objResponse->assign("txtCreditoNeto","value",number_format($row['total_credito'], 2, ".", ","));
	$objResponse->assign("txtCreditoNetoAnt","value",number_format($row['total_credito'], 2, ".", ","));
	
	$idUnidadFisica = $row['id_unidad_fisica'];
	
	if ($idUnidadFisica > 0) {
		$query = sprintf("SELECT 
			uni_fis.id_unidad_fisica,
			uni_bas.id_uni_bas,
			uni_bas.nom_uni_bas,
			uni_bas.clv_uni_bas,
			uni_bas.des_uni_bas,
			marca.nom_marca,
			modelo.nom_modelo,
			vers.nom_version,
			uni_fis.ano,
			ano.nom_ano,
			uni_fis.placa,
			uni_fis.id_condicion_unidad,
			cond_unidad.descripcion AS condicion_unidad,  
			uni_fis.kilometraje,
			uni_fis.fecha_fabricacion,
			uni_bas.imagen_auto,
			uni_fis.id_almacen,
			alm.nom_almacen,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			uni_fis.id_color_externo1,
			uni_fis.id_color_interno1,
			uni_fis.id_color_externo2,
			uni_fis.id_color_interno2,
			color_ext1.nom_color AS color_externo1,
			color_int1.nom_color AS color_interno1,
			color_ext2.nom_color AS color_externo2,
			color_int2.nom_color AS color_interno2,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.registro_legalizacion,
			uni_fis.registro_federal,
			pais_origen.nom_origen,
			clase.nom_clase,
			uso.nom_uso,
			uni_bas.pto_uni_bas,
			uni_bas.cil_uni_bas,
			uni_bas.ccc_uni_bas,
			uni_bas.cab_uni_bas,
			trans.nom_transmision,
			comb.nom_combustible,
			uni_bas.cap_uni_bas,
			uni_bas.uni_uni_bas,
			uni_bas.anos_de_garantia,
			uni_bas.kilometraje AS kilometraje_garantia,
			uni_fis.fecha_fabricacion,
			uni_fis.serial1,
			uni_fis.codigo_unico_conversion,
			uni_fis.marca_kit,
			uni_fis.modelo_regulador,
			uni_fis.serial_regulador,
			uni_fis.marca_cilindro,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro,
			uni_fis.moneda_costo_compra
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
			INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
			LEFT JOIN an_color color_ext2 ON (uni_fis.id_color_externo2 = color_ext2.id_color)
			LEFT JOIN an_color color_int2 ON (uni_fis.id_color_interno2 = color_int2.id_color)
			LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
			INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
			INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
			INNER JOIN an_combustible comb ON (uni_bas.com_uni_bas = comb.id_combustible)
		WHERE uni_fis.id_unidad_fisica = %s;",
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("txtIdUnidadFisicaAjuste","value",$row['id_unidad_fisica']);
		$objResponse->assign("txtEstadoVenta","value",utf8_encode($row['estado_venta']));
		$objResponse->loadCommands(cargaLstUnidadBasica($row['id_uni_bas'], true));
		$objResponse->loadCommands(asignarUnidadBasica('Ajuste', $row['id_uni_bas']));
		$objResponse->loadCommands(cargaLstAno($row['ano'], true));
		$objResponse->loadCommands(cargaLstCondicion($row['id_condicion_unidad'], true));
		$objResponse->assign("txtFechaFabricacionAjuste","value",date("d-m-Y",strtotime($row['fecha_fabricacion'])));
		$objResponse->assign("txtKilometraje","value",$row['kilometraje']);
		
		$objResponse->loadCommands(cargaLstColor("lstColorExterno1",$row['id_color_externo1'], true));
		$objResponse->loadCommands(cargaLstColor("lstColorInterno1",$row['id_color_interno1'], true));
		$objResponse->loadCommands(cargaLstColor("lstColorExterno2",$row['id_color_externo2'], true));
		$objResponse->loadCommands(cargaLstColor("lstColorInterno2",$row['id_color_interno2'], true));
		
		$objResponse->assign("txtPlacaAjuste","value",$row['placa']);
		$objResponse->assign("txtSerialCarroceriaAjuste","value",$row['serial_carroceria']);
		$objResponse->assign("txtSerialMotorAjuste","value",$row['serial_motor']);
		$objResponse->assign("txtNumeroVehiculoAjuste","value",$row['serial_chasis']);
		$objResponse->assign("txtRegistroLegalizacionAjuste","value",$row['registro_legalizacion']);
		$objResponse->assign("txtRegistroFederalAjuste","value",$row['registro_federal']);
		
		$objResponse->loadCommands(cargaLstAlmacen('lstAlmacenAjuste', $idEmpresa, $row['id_almacen'], true));
		$objResponse->assign("txtEstadoCompraAjuste","value","REGISTRADO");
		$objResponse->loadCommands(cargaLstEstadoVenta("lstEstadoVentaAjuste", "Ajuste", $row['estado_venta']));
		$objResponse->loadCommands(cargaLstMoneda($row['moneda_costo_compra'], true));
		
		$objResponse->script("calcularMonto();");
	}
	
	return $objResponse;
}

function formTradeIn($idTradeIn, $frmAjusteInventario) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAjusteInventario['cbx'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	if (!xvalidaAcceso($objResponse,"an_tradein_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();"); return $objResponse; }
	
	$query = sprintf("SELECT 
		tradein.id_tradein,
		tradein.id_unidad_fisica,
		tradein.allowance,
		tradein.payoff,
		tradein.acv,
		tradein.total_credito,
		tradein.id_proveedor,
		cxc_ant.montoNetoAnticipo,
		cxc_ant.numeroAnticipo,
		cxc_ant.idDepartamento,
		cxc_ant.id_empresa,
		cxc_ant.observacionesAnticipo,
		pg_modulos.descripcionModulo,
		cliente.id,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
		cj_cc_detalleanticipo.id_concepto,
		cj_conceptos_formapago.descripcion,
		formapagos.idFormaPago,
		formapagos.nombreFormaPago,
		(SELECT cp_proveedor.nombre FROM cp_proveedor WHERE cp_proveedor.id_proveedor = tradein.id_proveedor) as nombre_cliente_adeudado
	FROM an_tradein tradein
		INNER JOIN cj_cc_anticipo cxc_ant ON (tradein.id_anticipo = cxc_ant.idAnticipo)
		INNER JOIN pg_modulos ON (cxc_ant.idDepartamento = pg_modulos.id_modulo)
		INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
		INNER JOIN cj_cc_detalleanticipo ON (cxc_ant.idAnticipo = cj_cc_detalleanticipo.idAnticipo)
		INNER JOIN cj_conceptos_formapago ON (cj_cc_detalleanticipo.id_concepto = cj_conceptos_formapago.id_concepto)
		INNER JOIN formapagos ON (cj_conceptos_formapago.id_formapago = formapagos.idFormaPago)
	WHERE id_tradein = %s
	LIMIT 1;",
		valTpDato($idTradeIn, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdEmpresa", "value", $row['id_empresa']);
	$objResponse->assign("txtIdCliente_2", "value", $row['id']);
	$objResponse->assign("txtNombreCliente_2", "value", utf8_encode($row['nombre_cliente']));
	
	$objResponse->assign("txtIdTradeIn", "value", $row['id_tradein']);
	
	$objResponse->assign("txtAllowance_2", "value", $row['allowance']);
	$objResponse->assign("txtPayoff_2", "value", $row['payoff']);
	$objResponse->assign("txtAcv_2", "value", $row['acv']);
	$objResponse->assign("txtCreditoNeto_2", "value", $row['total_credito']);
	
	$objResponse->assign("txtNumeroAnticipo_2", "value", $row['numeroAnticipo']);
	$objResponse->assign("hddIdModulo", "value", $row['idDepartamento']);
	$objResponse->assign("tdlstModulo_2", "innerHTML", $row['descripcionModulo']);
	$objResponse->assign("txtMontoAnticipo_2", "value", $row['montoNetoAnticipo']);
	
	$objResponse->assign("hddIdFormaPago", "value", $row['idFormaPago']);
	$objResponse->assign("lstFormaPago_2", "value", $row['nombreFormaPago']);
	$objResponse->assign("hddIdConceptoPago", "value", $row['id_concepto']);
	$objResponse->assign("lstConceptoPago_2", "value", $row['descripcion']);
	$objResponse->assign("txtSubTotal_2", "value", $row['allowance']);
	
	
	$objResponse->assign("txtIdProv_2", "value", $row['id_proveedor']);
	$objResponse->assign("txtNombreProv_2", "value", utf8_encode($row['nombre_cliente_adeudado']));
	
	$objResponse->assign("txtObservacion_2", "value", utf8_encode($row['observacionesAnticipo']));
	
	
	$idUnidadFisica = $row['id_unidad_fisica'];
	
	if ($idUnidadFisica > 0) {
		$query = sprintf("SELECT 
			uni_fis.id_unidad_fisica,
			uni_bas.id_uni_bas,
			uni_bas.nom_uni_bas,
			uni_bas.clv_uni_bas,
			uni_bas.des_uni_bas,
			marca.nom_marca,
			modelo.nom_modelo,
			vers.nom_version,
			ano.nom_ano,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,  
			uni_fis.kilometraje,
			uni_fis.fecha_fabricacion,
			uni_bas.imagen_auto,
			alm.nom_almacen,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			color_ext1.nom_color AS color_externo1,
			color_int1.nom_color AS color_interno1,
			color_ext2.nom_color AS color_externo2,
			color_int2.nom_color AS color_interno2,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.registro_legalizacion,
			uni_fis.registro_federal,
			pais_origen.nom_origen,
			clase.nom_clase,
			uso.nom_uso,
			uni_bas.pto_uni_bas,
			uni_bas.cil_uni_bas,
			uni_bas.ccc_uni_bas,
			uni_bas.cab_uni_bas,
			trans.nom_transmision,
			comb.nom_combustible,
			uni_bas.cap_uni_bas,
			uni_bas.uni_uni_bas,
			uni_bas.anos_de_garantia,
			uni_bas.kilometraje AS kilometraje_garantia,
			uni_fis.fecha_fabricacion,
			uni_fis.serial1,
			uni_fis.codigo_unico_conversion,
			uni_fis.marca_kit,
			uni_fis.modelo_regulador,
			uni_fis.serial_regulador,
			uni_fis.marca_cilindro,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
			INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
			LEFT JOIN an_color color_ext2 ON (uni_fis.id_color_externo2 = color_ext2.id_color)
			LEFT JOIN an_color color_int2 ON (uni_fis.id_color_interno2 = color_int2.id_color)
			LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
			INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
			INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
			INNER JOIN an_combustible comb ON (uni_bas.com_uni_bas = comb.id_combustible)
		WHERE uni_fis.id_unidad_fisica = %s;",
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("txtIdUnidadFisica", "value", $row['id_unidad_fisica']);
		$objResponse->assign("hddIdUnidadBasica", "value", $row['id_uni_bas']);
		$objResponse->assign("txtNombreUnidadBasica", "value", utf8_encode($row['nom_uni_bas']));
		$objResponse->assign("txtClaveUnidadBasica", "value", utf8_encode($row['clv_uni_bas']));
		$objResponse->assign("txtDescripcion", "innerHTML", utf8_encode($row['des_uni_bas']));
		$objResponse->assign("txtMarcaUnidadBasica", "value", utf8_encode($row['nom_marca']));
		$objResponse->assign("txtModeloUnidadBasica", "value", utf8_encode($row['nom_modelo']));
		$objResponse->assign("txtVersionUnidadBasica", "value", utf8_encode($row['nom_version']));
		$objResponse->assign("txtAno", "value", utf8_encode($row['nom_ano']));
		$objResponse->assign("txtPlaca", "value", utf8_encode($row['placa']));
		$objResponse->assign("txtKilometraje_2", "value", $row['kilometraje']);
		$objResponse->assign("txtCondicion", "value", utf8_encode($row['condicion_unidad']));
		$txtFechaFabricacion = ($row['fecha_fabricacion'] != "") ? date("d-m-Y", strtotime($row['fecha_fabricacion'])) : "----------";
		$objResponse->assign("txtFechaFabricacion", "value", $txtFechaFabricacion);
		$objResponse->assign("txtAlmacen", "value", utf8_encode($row['nom_almacen']));
		$objResponse->assign("txtEstadoCompra", "value", utf8_encode($row['estado_compra']));
		$objResponse->loadCommands(cargaLstEstadoVenta("lstEstadoVenta", "Ajuste", utf8_encode($row['estado_venta'])));
		$objResponse->assign("hddEstadoVenta", "value", utf8_encode($row['estado_venta']));
	
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		$objResponse->assign("imgArticulo","src",$imgFoto);
		$objResponse->assign("hddUrlImagen","value",$row['imagen_auto']);
		
		$objResponse->assign("txtColorExterno1", "value", utf8_encode($row['color_externo1']));
		$objResponse->assign("txtColorInterno1", "value", utf8_encode($row['color_interno1']));
		$objResponse->assign("txtColorExterno2", "value", utf8_encode($row['color_externo2']));
		$objResponse->assign("txtColorInterno2", "value", utf8_encode($row['color_interno2']));
		
		$objResponse->assign("txtSerialCarroceria", "value", utf8_encode($row['serial_carroceria']));
		$objResponse->assign("txtSerialMotor", "value", utf8_encode($row['serial_motor']));
		$objResponse->assign("txtNumeroVehiculo", "value", utf8_encode($row['serial_chasis']));
		$objResponse->assign("txtRegistroLegalizacion", "value", utf8_encode($row['registro_legalizacion']));
		$objResponse->assign("txtRegistroFederal", "value", utf8_encode($row['registro_federal']));
		
		$objResponse->assign("txtPaisOrigen", "value", utf8_encode($row['nom_origen']));
		$objResponse->assign("txtClase", "value", utf8_encode($row['nom_clase']));
		$objResponse->assign("txtUso", "value", utf8_encode($row['nom_uso']));
		$objResponse->assign("txtNumeroPuertas", "value", utf8_encode($row['pto_uni_bas']));
		$objResponse->assign("txtNumeroCilindros", "value", utf8_encode($row['cil_uni_bas']));
		$objResponse->assign("txtCilindrada", "value", utf8_encode($row['ccc_uni_bas']));
		$objResponse->assign("txtCaballosFuerza", "value", utf8_encode($row['cab_uni_bas']));
		$objResponse->assign("txtTransmision", "value", utf8_encode($row['nom_transmision']));
		$objResponse->assign("txtCombustible", "value", utf8_encode($row['nom_combustible']));
		$objResponse->assign("txtCapacidad", "value", $row['cap_uni_bas']);
		$objResponse->assign("txtUnidad", "value", $row['uni_uni_bas']);
		$objResponse->assign("txtAnoGarantia", "value", $row['anos_de_garantia']);
		$objResponse->assign("txtKmGarantia", "value", number_format($row['kilometraje_garantia'], 2, ".", ","));
		
		if (strlen($row['serial1']) > 0) {
			$objResponse->script("byId('trSistemaGNV').style.display = '';");
		}
		$objResponse->assign("txtSerial1", "value", utf8_encode($row['serial1']));
		$objResponse->assign("txtCodigoUnico", "value", utf8_encode($row['codigo_unico_conversion']));
		$objResponse->assign("txtMarcaKit", "value", utf8_encode($row['marca_kit']));
		$objResponse->assign("txtModeloRegulador", "value", utf8_encode($row['modelo_regulador']));
		$objResponse->assign("txtSerialRegulador", "value", utf8_encode($row['serial_regulador']));
		$objResponse->assign("txtMarcaCilindro", "value", utf8_encode($row['marca_cilindro']));
		$objResponse->assign("txtCapacidadCilindro", "value", utf8_encode($row['capacidad_cilindro']));
		$txtFechaCilindro = ($row['fecha_elaboracion_cilindro'] != "") ? date("d-m-Y", strtotime($row['fecha_elaboracion_cilindro'])) : "----------";
		$objResponse->assign("txtFechaCilindro", "value", $txtFechaCilindro);
	}
	
	return $objResponse;
}

function formTradeInCxP($idTradeIn) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_tradein_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL TRADE-IN
	$queryTradeIn = sprintf("SELECT * FROM an_tradein WHERE id_tradein = %s;",
		valTpDato($idTradeIn, "int"));
	$rsTradeIn = mysql_query($queryTradeIn);
	if (!$rsTradeIn) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowTradeIn = mysql_fetch_assoc($rsTradeIn);
	
	$objResponse->loadCommands(cargaLstProveedorTradeInCxP($idTradeIn, $rowTradeIn['id_proveedor']));
	
	$queryTradeInCxP = sprintf("SELECT query.*,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
		query.total
	FROM (SELECT
			tradein_cxp.*,
			2 AS tipoDocumentoN,
			'ND' AS tipoDocumento,
			cxp_nd.id_notacargo,
			cxp_nd.numero_notacargo,
			cxp_nd.fecha_origen_notacargo,
			cxp_nd.id_modulo,
			cxp_nd.estatus_notacargo,
			(CASE cxp_nd.estatus_notacargo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS descripcion_estado_nota_cargo,
			cxp_nd.observacion_notacargo,
			cxp_nd.saldo_notacargo,
			cxp_nd.id_motivo,
			
			(IFNULL(cxp_nd.subtotal_notacargo, 0)
			- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
			+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) AS total_gasto FROM cp_notacargo_gastos cxp_nd_gasto
					WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
						AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) AS total_iva FROM cp_notacargo_iva cxp_nd_iva
					WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0)) AS total
		FROM an_tradein_cxp tradein_cxp
			INNER JOIN cp_notadecargo cxp_nd ON (tradein_cxp.id_nota_cargo_cxp = cxp_nd.id_notacargo)
		
		UNION
		
		SELECT
			tradein_cxp.*,
			4 AS tipoDocumentoN,
			'NC' AS tipoDocumento,
			cxp_nc.id_notacredito,
			cxp_nc.numero_nota_credito,
			cxp_nc.fecha_registro_notacredito,
			cxp_nc.id_departamento_notacredito,
			cxp_nc.estado_notacredito,
			(CASE cxp_nc.estado_notacredito
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Sin Asignar'
				WHEN 2 THEN 'Asignado Parcial'
				WHEN 3 THEN 'Asignado'
			END) AS descripcion_estado_nota_credito,
			cxp_nc.observacion_notacredito,
			cxp_nc.saldo_notacredito,
			cxp_nc.id_motivo,
			
			(IFNULL(cxp_nc.subtotal_notacredito, 0)
			- IFNULL(cxp_nc.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
					WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
						AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
					WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)) AS total
		FROM an_tradein_cxp tradein_cxp
			INNER JOIN cp_notacredito cxp_nc ON (tradein_cxp.id_nota_credito_cxp = cxp_nc.id_notacredito)) AS query
		INNER JOIN cp_proveedor prov ON (query.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_motivo motivo ON (query.id_motivo = motivo.id_motivo)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (query.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
	WHERE query.id_tradein = %s;",
		valTpDato($idTradeIn, "int"));
	$rsTradeInCxP = mysql_query($queryTradeInCxP);
	if (!$rsTradeInCxP) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsTradeInCxP = mysql_num_rows($rsTradeInCxP);
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"8%\">Tipo de Dcto.</td>";
		$htmlTh .= "<td width=\"6%\">Fecha Registro</td>";
		$htmlTh .= "<td width=\"6%\">Nro. Dcto.</td>";
		$htmlTh .= "<td width=\"56%\">Proveedor</td>";
		$htmlTh .= "<td width=\"8%\">Estado Dcto.</td>";
		$htmlTh .= "<td width=\"8%\">Saldo Dcto.</td>";
		$htmlTh .= "<td width=\"8%\">Total Dcto.</td>";
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	while ($rowTradeInCxP = mysql_fetch_array($rsTradeInCxP)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		// PARA QUE SIRVA COMO REFERENCIA EN CASO DE QUE EL USUARIO SOLO DESEE CAMBIAR EL PROVEEDOR Y NO ALGUN DOCUMENTO
		$objResponse->assign("hddIdTradeInCxP","value",$rowTradeInCxP['id_tradein_cxp']);
		
		switch($rowTradeInCxP['estatus_notacargo']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$classTradeInCxP = ($rowTradeInCxP['estatus'] != 1) ? "class=\"divMsjError\"" : "";
		$estatusTradeInCxP = ($rowTradeInCxP['estatus'] != 1) ? "<div align=\"center\">RELACION ANULADA</div>" : "";
		$empleadoAnuladoTradeInCxP = (strlen($rowTradeInCxP['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".$rowTradeInCxP['nombre_empleado_anulado']."<br>(".date("d-m-Y",strtotime($rowTradeInCxP['fecha_anulado'])).")</span></div>" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowTradeInCxP['tipoDocumento'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($rowTradeInCxP['fecha_origen_notacargo']))."</td>";
			$htmlTb .= "<td align=\"right\" ".$classTradeInCxP.">".$rowTradeInCxP['numero_notacargo'].$estatusTradeInCxP.$empleadoAnuladoTradeInCxP."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($rowTradeInCxP['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($rowTradeInCxP['serial_carroceria']) > 0) ? "<tr><td><span class=\"textoNegrita_10px\">".utf8_encode($rowTradeInCxP['serial_carroceria'])."</span></td></tr>" : "";
				$htmlTb .= ($rowTradeInCxP['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$rowTradeInCxP['id_motivo'].".- ".utf8_encode($rowTradeInCxP['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($rowTradeInCxP['observacion_notacargo']) > 0) ? "<tr><td>".utf8_encode($rowTradeInCxP['observacion_notacargo'])."</td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$rowTradeInCxP['descripcion_estado_nota_cargo']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowTradeInCxP['saldo_notacargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowTradeInCxP['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if (in_array($rowTradeInCxP['tipoDocumentoN'],array(2)) && $rowTradeInCxP['estatus'] == 1) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aCambiar%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblLista2', 'NotaCargo', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_cambio.png\" title=\"Cambiar Nota de Débito CxP\"/></a>",
					$contFila,
					$rowTradeInCxP['id_tradein_cxp']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			switch ($rowTradeInCxP['tipoDocumentoN']) {
				case 2 :
					$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cxp/reportes/cp_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Débito PDF\"/></a>",
						$rowTradeInCxP['id_notacargo']);
					break;
				case 4 :
					$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Crédito PDF\"/></a>",
						$rowTradeInCxP['id_notacargo']);
					break;
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if ($rowTradeInCxP['estatus'] == 1) {
			$arrayTotal[7] += ((in_array($rowTradeInCxP['tipoDocumentoN'],array(4))) ? (-1) : 1) * $rowTradeInCxP['total'];
		}
	}
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\" colspan=\"6\">"."Total Payoff:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
		$htmlTb .= "<td colspan=\"2\"></td>";
	$htmlTb .= "</tr>";
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaTradeInCxP","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

/*
* Se encarga de generar un nuevo anticipo a partir del anterior, por si anularon un anticipo ya creado,
* no tener que dar reingreso al vehiculo sino crear otro anticipo con el ingresado
* @param Array $frmAjusteInventario xajax Form
* @param int $esNuevo bool
* @return \xajaxResponse Objeto xajax
*/
function generarNuevoAnticipo($frmAjusteInventario, $esNuevo = 0){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_tradein_list","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAjusteInventario['cbx'];
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script("byId('lstConceptoPago".$valor."').className = 'inputCompletoHabilitado'");
			$objResponse->script("byId('txtMontoAnticipo".$valor."').className = 'inputCompletoHabilitado'");
			$objResponse->script("byId('txtObservacion".$valor."').className = 'inputCompletoHabilitado'");
			
			if (!($frmAjusteInventario['lstConceptoPago'.$valor] > 0)) {
				$arrayCantidadInvalida[] = "lstConceptoPago".$valor;
			}
			
			if (!(str_replace(",", "", $frmAjusteInventario['txtMontoPago'.$valor]) > 0 
			|| str_replace(",", "", $frmAjusteInventario['txtMontoAnticipo'.$valor]) > 0)) {
				$arrayCantidadInvalida[] = "txtMontoPago".$valor;
				$arrayCantidadInvalida[] = "txtMontoAnticipo".$valor;
			}
			
			if (!(strlen($frmAjusteInventario['txtObservacion'.$valor]) > 0)) {
				$arrayCantidadInvalida[] = "txtObservacion".$valor;
			}
		}
	}
	
	// SI HAY CANTIDADES INVALIDAS O NO DISPONIBLES SE PINTARAN DICHOS OBJETOS, CONTENIDOS EN LAS MATRICES
	if (count($arrayCantidadInvalida) > 0) {
		if (count($arrayCantidadInvalida) > 0) {
			foreach ($arrayCantidadInvalida as $indice => $valor) {
				$objResponse->script("byId('".$valor."').className = 'inputCompletoErrado';");
			}
		}
		
		return $objResponse->alert(("Los campos señalados en rojo son invalidos"));
	}
	
	$arrayApertura = validarAperturaCaja(0);//diferente de 1 para que devuelva array y no xajax
	if($arrayApertura[0] === false) { return $objResponse->alert($arrayApertura[1]); }
    
    mysql_query("START TRANSACTION;");
	
	if ($esNuevo) { // Viene de vehiculo ya registrado
		//frmAjusteInventario
        $arrayGuardadoAnticipo = $frmAjusteInventario;
        
        $idUnidadFisica = $frmAjusteInventario['txtIdUnidadFisicaAjuste'];
        $txtAllowance = $frmAjusteInventario['txtAllowance'];
        $txtAcv = $frmAjusteInventario['txtAcv'];
        $txtPayoff = $frmAjusteInventario['txtPayoff'];
        $txtCreditoNeto = $frmAjusteInventario['txtCreditoNeto'];
		$idCliente = $frmAjusteInventario['txtIdCliente']; 
        $idProveedorAdeudado = $frmAjusteInventario['txtIdProv'];
        $tipoRegistro = 2;
		
		$Result1 = guardarAnticipoCxC($arrayGuardadoAnticipo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->script($Result1[5]);
			$arrayIdDctoContabilidad[] = array(
				$Result1[1],
				$Result1[2],
				"ANTICIPO_CXC");
			$idAnticipoTradeIn = $Result1[1];
		}
		
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				$Result1 = guardarAnticipoCxC($frmAjusteInventario, $valor);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$objResponse->script($Result1[5]);
					$arrayIdDctoContabilidad[] = array(
						$Result1[1],
						$Result1[2],
						"ANTICIPO_CXC");
					$idAnticipo = $Result1[1];
				}
			}
		}
		
		if ($frmAjusteInventario['txtIdMotivo'] > 0) {
			$Result1 = guardarNotaCargoCxP($arrayGuardadoAnticipo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"NOTA_CARGO_CXP");
				$idNotaCargoCxP = $Result1[1];
			}
		}
		
		if ($frmAjusteInventario['txtIdMotivoCxC'] > 0) {
			$Result1 = guardarNotaCargoCxC($arrayGuardadoAnticipo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"NOTA_CARGO_CXC");
				$idNotaCargoCxC = $Result1[1];
			}
		}
		
		$sql = sprintf("UPDATE an_tradein SET
			anulado = 1
		WHERE id_unidad_fisica = %s;",
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($sql);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
		$insertSQL = sprintf("INSERT INTO an_tradein (id_anticipo, id_unidad_fisica, id_nota_cargo_cxp, id_nota_cargo_cxc, id_proveedor, allowance, payoff, acv, total_credito, id_empleado, tipo_registro)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idAnticipoTradeIn, "int"),
			valTpDato($idUnidadFisica, "int"),
			valTpDato($idNotaCargoCxP, "int"),
			valTpDato($idNotaCargoCxC, "int"),
			valTpDato($idProveedorAdeudado, "int"),
			valTpDato($txtAllowance, "real_inglesa"),
			valTpDato($txtPayoff, "real_inglesa"),
			valTpDato($txtAcv, "real_inglesa"),
			valTpDato($txtCreditoNeto, "real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($tipoRegistro, "int")); // 1 = Nuevo, 2 = Vehiculo ya Registrado, 3 = Trade In ya Registrado
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idTradeIn = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA LA AUDITORIA DE LOS MONTOS INGRESADOS
		$insertSQL = sprintf("INSERT INTO an_tradein_auditoria (id_tradein, allowance, acv, payoff, total_credito, id_empleado_registro)
		VALUES (%s, %s, %s, %s, %s, %s)",
			valTpDato($idTradeIn, "int"),
			valTpDato($txtAllowance, "real_inglesa"),
			valTpDato($txtAcv, "real_inglesa"),
			valTpDato($txtPayoff, "real_inglesa"),
			valTpDato($txtCreditoNeto, "real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		if ($idProveedorAdeudado > 0) {
			$insertSQL = sprintf("INSERT INTO an_tradein_cxp (id_tradein, id_nota_cargo_cxp, id_proveedor, estatus)
			VALUES (%s, %s, %s, %s);",
				valTpDato($idTradeIn, "int"),
				valTpDato($idNotaCargoCxP, "int"),
				valTpDato($idProveedorAdeudado, "int"),
				valTpDato(1, "int")); // Null = Anulado, 1 = Activo
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		if ($idCliente > 0) {
			$insertSQL = sprintf("INSERT INTO an_tradein_cxc (id_tradein, id_nota_cargo_cxc, id_cliente, estatus)
			VALUES (%s, %s, %s, %s);",
				valTpDato($idTradeIn, "int"),
				valTpDato($idNotaCargoCxC, "int"),
				valTpDato($idCliente, "int"),
				valTpDato(1, "int")); // Null = Anulado, 1 = Activo
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
    } else { // Viene de tradein ya registrado
		//frmUnidadFisica
        $arrayGuardadoAnticipo = $frmAjusteInventario;
		
        $arrayGuardadoAnticipo['txtIdEmpresa'] = $frmAjusteInventario['hddIdEmpresa'];
		$arrayGuardadoAnticipo['txtIdCliente'] = $frmAjusteInventario['txtIdCliente_2'];
		$arrayGuardadoAnticipo['txtMontoPago'] = $frmAjusteInventario['txtMontoAnticipo_2'];
		$arrayGuardadoAnticipo['hddMontoAnticipo'] = $frmAjusteInventario['txtMontoAnticipo_2'];
		$arrayGuardadoAnticipo['txtMontoAnticipo'] = $frmAjusteInventario['txtMontoAnticipo_2'];
		$arrayGuardadoAnticipo['txtObservacion'] = $frmAjusteInventario['txtObservacion_2'];
		$arrayGuardadoAnticipo['lstModulo'] = $frmAjusteInventario['hddIdModulo'];
		$arrayGuardadoAnticipo['lstFormaPago'] = $frmAjusteInventario['hddIdFormaPago'];
		$arrayGuardadoAnticipo['lstConceptoPago'] = $frmAjusteInventario['hddIdConceptoPago'];
		$arrayGuardadoAnticipo['txtSubTotal'] = $frmAjusteInventario['txtSubTotal_2'];
		
        $idUnidadFisica = $frmAjusteInventario['txtIdUnidadFisica'];
        $txtAllowance = $frmAjusteInventario['txtAllowance_2'];
        $txtAcv = $frmAjusteInventario['txtAcv_2'];
        $txtPayoff = $frmAjusteInventario['txtPayoff_2'];
        $txtCreditoNeto = $frmAjusteInventario['txtCreditoNeto_2'];
		$idCliente = $frmAjusteInventario['txtIdCliente_2'];
        $idProveedorAdeudado = $frmAjusteInventario['txtIdProv_2'];
        $tipoRegistro = 3;
		
		$Result1 = guardarAnticipoCxC($arrayGuardadoAnticipo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->script($Result1[5]);
			$arrayIdDctoContabilidad[] = array(
				$Result1[1],
				$Result1[2],
				"ANTICIPO_CXC");
			$idAnticipoTradeIn = $Result1[1];
		}
		
		
		// ANULA EL REGISTRO DEL TRADE-IN ANTERIOR
		$sql = sprintf("UPDATE an_tradein SET
			anulado = 1
		WHERE id_tradein = %s
			AND id_unidad_fisica = %s;",
			valTpDato($frmAjusteInventario['txtIdTradeIn'], "int"),
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($sql);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// INSERTA CON ALGUNOS DATOS DEL TRADE-IN ANTERIOR EL TRADE-IN NUEVO
		$insertSQL = sprintf("INSERT INTO an_tradein (id_anticipo, id_unidad_fisica, id_nota_cargo_cxp, id_nota_cargo_cxc, id_proveedor, allowance, payoff, acv, total_credito, id_empleado, tipo_registro)
		SELECT %s, id_unidad_fisica, id_nota_cargo_cxp, id_nota_cargo_cxc, id_proveedor, allowance, payoff, acv, total_credito, %s, %s FROM an_tradein
		WHERE id_tradein = %s;",
			valTpDato($idAnticipoTradeIn, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($tipoRegistro, "int"), // 1 = Nuevo, 2 = Vehiculo ya Registrado, 3 = Trade In ya Registrado
			valTpDato($frmAjusteInventario['txtIdTradeIn'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idTradeIn = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA LA AUDITORIA DE LOS MONTOS INGRESADOS
		$insertSQL = sprintf("INSERT INTO an_tradein_auditoria (id_tradein, allowance, acv, payoff, total_credito, id_empleado_registro)
		VALUES (%s, %s, %s, %s, %s, %s)",
			valTpDato($idTradeIn, "int"),
			valTpDato($txtAllowance, "real_inglesa"),
			valTpDato($txtAcv, "real_inglesa"),
			valTpDato($txtPayoff, "real_inglesa"),
			valTpDato($txtCreditoNeto, "real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		
		// REEMPLAZA EL CLIENTE ANTERIOR AL EL CLIENTE NUEVO EN LOS DOCUMENTOS AUN PENDIENTES
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo SET
			idCliente = %s
		WHERE idNotaCargo IN (SELECT tradein_cxc.id_nota_cargo_cxc FROM an_tradein_cxc tradein_cxc
								WHERE tradein_cxc.id_tradein = %s)
			AND estadoNotaCargo IN (0);",
			valTpDato($idCliente, "int"),
			valTpDato($frmAjusteInventario['txtIdTradeIn'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$updateSQL = sprintf("UPDATE cj_cc_notacredito SET
			idCliente = %s
		WHERE idNotaCredito IN (SELECT tradein_cxc.id_nota_credito_cxc FROM an_tradein_cxc tradein_cxc
								WHERE tradein_cxc.id_tradein = %s)
			AND estadoNotaCredito IN (0,1);",
			valTpDato($idCliente, "int"),
			valTpDato($frmAjusteInventario['txtIdTradeIn'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		
		// INSERTA LOS DOCUMENTOS DE CUENTAS POR COBRAR DEL TRADE-IN ANTERIOR EN EL TRADE-IN NUEVO
		$insertSQL = sprintf("INSERT INTO an_tradein_cxc (id_tradein, id_nota_cargo_cxc, id_nota_credito_cxc, id_anticipo, id_cliente, estatus, fecha_anulado, id_empleado_anulado)
		SELECT
			%s,
			id_nota_cargo_cxc,
			id_nota_credito_cxc,
			id_anticipo,
			(CASE
				WHEN (id_nota_cargo_cxc IS NOT NULL) THEN
					(SELECT cxc_nd.idCliente FROM cj_cc_notadecargo cxc_nd
					WHERE cxc_nd.idNotaCargo = tradein_cxc.id_nota_cargo_cxc)
				WHEN (id_nota_credito_cxc IS NOT NULL) THEN
					(SELECT cxc_nc.idCliente FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = tradein_cxc.id_nota_credito_cxc)
			END),
			estatus,
			fecha_anulado,
			id_empleado_anulado
		FROM an_tradein_cxc tradein_cxc
		WHERE id_tradein = %s;",
			valTpDato($idTradeIn, "int"),
			valTpDato($frmAjusteInventario['txtIdTradeIn'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ANULA LOS DOCUMENTOS ASOCIADOS DEL TRADE-IN ANTERIOR
		$updateSQL = sprintf("UPDATE an_tradein_cxc SET
			estatus = NULL,
			fecha_anulado = %s,
			id_empleado_anulado = %s
		WHERE id_tradein = %s;",
			valTpDato("NOW()", "campo"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($frmAjusteInventario['txtIdTradeIn'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		
		// INSERTA LOS DOCUMENTOS DE CUENTAS POR COBRAR DEL TRADE-IN ANTERIOR EN EL TRADE-IN NUEVO
		$insertSQL = sprintf("INSERT INTO an_tradein_cxp (id_tradein, id_nota_cargo_cxp, id_nota_credito_cxp, id_proveedor, estatus, fecha_anulado, id_empleado_anulado)
		SELECT
			%s,
			id_nota_cargo_cxp,
			id_nota_credito_cxp,
			(CASE
				WHEN (id_nota_cargo_cxp IS NOT NULL) THEN
					(SELECT cxp_nd.id_proveedor FROM cp_notadecargo cxp_nd
					WHERE cxp_nd.id_notacargo = tradein_cxp.id_nota_cargo_cxp)
				WHEN (id_nota_credito_cxp IS NOT NULL) THEN
					(SELECT cxp_nc.id_proveedor FROM cp_notacredito cxp_nc
					WHERE cxp_nc.id_notacredito = tradein_cxp.id_nota_credito_cxp)
			END),
			estatus,
			fecha_anulado,
			id_empleado_anulado
		FROM an_tradein_cxp tradein_cxp
		WHERE id_tradein = %s;",
			valTpDato($idTradeIn, "int"),
			valTpDato($frmAjusteInventario['txtIdTradeIn'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ANULA LOS DOCUMENTOS ASOCIADOS DEL TRADE-IN ANTERIOR
		$updateSQL = sprintf("UPDATE an_tradein_cxp SET
			estatus = NULL,
			fecha_anulado = %s,
			id_empleado_anulado = %s
		WHERE id_tradein = %s;",
			valTpDato("NOW()", "campo"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($frmAjusteInventario['txtIdTradeIn'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
    }
	
    mysql_query("COMMIT;");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "ANTICIPO_CXC") {
				$idAnticipo = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 2 : if (function_exists("generarAnticiposVe")) { generarAnticiposVe($idAnticipo,"",""); } break;
				}
			} else if ($tipoDcto == "NOTA_CARGO_CXP") {
				$idNotaCargo = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasCargoCpRe")) { generarNotasCargoCpRe($idNotaCargo,"",""); } break;
					case 1 : if (function_exists("generarNotasCargoCpRe")) { generarNotasCargoCpRe($idNotaCargo,"",""); } break;
					case 2 : if (function_exists("generarNotasCargoCpVe")) { generarNotasCargoCpVe($idNotaCargo,"",""); } break;
					case 3 : if (function_exists("generarNotasCargoCpAd")) { generarNotasCargoCpAd($idNotaCargo,"",""); } break;
				}
			} else if ($tipoDcto == "NOTA_CARGO_CXC") {
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
    
    if ($esNuevo) {
        $objResponse->alert("Nuevo Trade-in y Anticipo Generado Correctamente");
    } else {
        $objResponse->alert("Anticipo Generado Correctamente");
    }
    
    $objResponse->script("byId('imgCerrarDivFlotante1').click();");
    $objResponse->script("byId('btnBuscar').click();");
    
    return $objResponse;    
}

function guardarAjusteInventario($frmAjusteInventario, $frmUnidadFisica, $frmListaUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	global $arrayValidarCarroceria;
	
	if (!xvalidaAcceso($objResponse,"an_tradein_list","insertar")) { return $objResponse; }     
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAjusteInventario['cbx'];
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script("byId('lstConceptoPago".$valor."').className = 'inputCompletoHabilitado'");
			$objResponse->script("byId('txtMontoAnticipo".$valor."').className = 'inputCompletoHabilitado'");
			$objResponse->script("byId('txtObservacion".$valor."').className = 'inputCompletoHabilitado'");
			
			if (!($frmAjusteInventario['lstConceptoPago'.$valor] > 0)) {
				$arrayCantidadInvalida[] = "lstConceptoPago".$valor;
			}
			
			if (!(str_replace(",", "", $frmAjusteInventario['txtMontoPago'.$valor]) > 0 
			|| str_replace(",", "", $frmAjusteInventario['txtMontoAnticipo'.$valor]) > 0)) {
				$arrayCantidadInvalida[] = "txtMontoPago".$valor;
				$arrayCantidadInvalida[] = "txtMontoAnticipo".$valor;
			}
			
			if (!(strlen($frmAjusteInventario['txtObservacion'.$valor]) > 0)) {
				$arrayCantidadInvalida[] = "txtObservacion".$valor;
			}
		}
	}
	
	// SI HAY CANTIDADES INVALIDAS O NO DISPONIBLES SE PINTARAN DICHOS OBJETOS, CONTENIDOS EN LAS MATRICES
	if (count($arrayCantidadInvalida) > 0) {
		if (count($arrayCantidadInvalida) > 0) {
			foreach ($arrayCantidadInvalida as $indice => $valor) {
				$objResponse->script("byId('".$valor."').className = 'inputCompletoErrado';");
			}
		}
		
		return $objResponse->alert(("Los campos señalados en rojo son invalidos"));
	}
	
	$arrayApertura = validarAperturaCaja(0);//diferente de 1 para que devuelva array y no xajax
	if($arrayApertura[0] === false) { return $objResponse->alert($arrayApertura[1]); }
        
	mysql_query("START TRANSACTION;");
	
	$idTradeIn = $frmAjusteInventario['hddIdTradeInAjusteInventario'];
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idCliente = $frmAjusteInventario['txtIdCliente'];
	$idUnidadFisica = $frmAjusteInventario['txtIdUnidadFisicaAjuste'];
	$idProveedorAdeudado = $frmAjusteInventario['txtIdProv'];
	
	$arrayValidar = $arrayValidarCarroceria;
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmAjusteInventario['txtSerialCarroceriaAjuste'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtSerialCarroceriaAjuste').className = 'inputErrado';");
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	// BUSCA LOS DATOS DEL TRADE-IN
	$queryTradeIn = sprintf("SELECT * FROM an_tradein WHERE id_tradein = %s;",
		valTpDato($idTradeIn, "int"));
	$rsTradeIn = mysql_query($queryTradeIn);
	if (!$rsTradeIn) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsTradeIn = mysql_num_rows($rsTradeIn);
	$rowTradeIn = mysql_fetch_assoc($rsTradeIn);
	
	if ($frmAjusteInventario['lstTipoVale'] == 1) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		$idClaveMovimiento = $frmAjusteInventario['lstClaveMovimiento'];
	} else if ($frmAjusteInventario['lstTipoVale'] == 3) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		switch ($frmAjusteInventario['lstTipoMovimiento']) { // 2 = ENTRADA, 4 = SALIDA
			case 2 : $documentoGenera = 6; break;
			case 4 : $documentoGenera = 5; break;
		}
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.tipo = %s
			AND clave_mov.documento_genera = %s
			AND clave_mov.id_modulo IN (2)
		ORDER BY clave DESC 
		LIMIT 1;",
			valTpDato($frmAjusteInventario['lstTipoMovimiento'], "int"),
			valTpDato($documentoGenera, "int"));
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
		
		$idClaveMovimiento = $rowClaveMov['id_clave_movimiento'];
	}
	
	if ($idUnidadFisica > 0) {
		if ($frmAjusteInventario['cbxAsignarUnidadFisica'] == 1) {
			$idUnidadFisica = $frmAjusteInventario['txtIdUnidadFisicaAjuste'];
			$idUnidadBasica = $frmAjusteInventario['lstUnidadBasica'];
			$txtEstadoVenta = $frmAjusteInventario['lstEstadoVentaAjuste'];
			
			$updateSQL = sprintf("UPDATE an_unidad_fisica SET
				id_condicion_unidad = %s,
				kilometraje = %s,
				id_almacen = %s,
				placa = %s,
				costo_trade_in = (costo_compra - costo_depreciado - %s),
				estado_venta = %s,
				estatus = %s
			WHERE id_unidad_fisica = %s;",
				valTpDato($frmAjusteInventario['lstCondicion'], "int"),
				valTpDato($frmAjusteInventario['txtKilometraje'], "int"),
				valTpDato($frmAjusteInventario['lstAlmacenAjuste'], "int"),
				valTpDato($frmAjusteInventario['txtPlacaAjuste'], "text"),
				valTpDato($frmAjusteInventario['txtAllowance'], "real_inglesa"),
				valTpDato($txtEstadoVenta, "text"),
				valTpDato(1, "boolean"), // Null = Anulada, 1 = Activa
				valTpDato($idUnidadFisica, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		} else if (!($idTradeIn > 0)) {
			$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
			$idUnidadBasica = $frmUnidadFisica['hddIdUnidadBasica'];
			$txtEstadoVenta = $frmAjusteInventario['txtEstadoVenta'];
		}
	} else {
		$idUnidadBasica = $frmAjusteInventario['lstUnidadBasica'];
		$txtEstadoVenta = $frmAjusteInventario['lstEstadoVentaAjuste'];
	
		// VERIFICA QUE NO EXISTA EL SERIAL DEL CHASIS, CARROCERIA Y MOTOR
		$query = sprintf("SELECT * FROM an_unidad_fisica
		WHERE (serial_carroceria LIKE %s)
			AND estatus = 1
			AND (id_unidad_fisica <> %s OR %s IS NULL);", // OR serial_motor LIKE %s OR serial_chasis LIKE %s
			valTpDato($frmAjusteInventario['txtSerialCarroceriaAjuste'], "text")/*,
			valTpDato($frmAjusteInventario['txtSerialMotorAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtNumeroVehiculoAjuste'], "text")*/,
			valTpDato($idUnidadFisica, "int"),
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_array($rs);
		
		if ($totalRows > 0) {
			return $objResponse->alert("Ya existe una unidad con alguno de los datos de ".$spanSerialCarroceria.", ".$spanSerialMotor." o Nro. Vehículo ingresados");
		}
		
		// BUSCA LOS DATOS DE LA UNIDAD BASICA
		$queryUnidadBasica = sprintf("SELECT * FROM an_uni_bas WHERE id_uni_bas = %s;",
			valTpDato($idUnidadBasica, "int"));
		$rsUnidadBasica = mysql_query($queryUnidadBasica);
		if (!$rsUnidadBasica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
		
		$insertSQL = sprintf("INSERT INTO an_unidad_fisica (id_uni_bas, ano, id_uso, id_clase, capacidad, id_condicion_unidad, kilometraje, id_color_externo1, id_color_externo2, id_color_interno1, id_color_interno2, id_origen, id_almacen, serial_carroceria, serial_motor, serial_chasis, placa, fecha_fabricacion, registro_legalizacion, registro_federal, descuento_compra, porcentaje_iva_compra, iva_compra, porcentaje_impuesto_lujo_compra, impuesto_lujo_compra, costo_compra, moneda_costo_compra, tasa_cambio_costo_compra, precio_compra, moneda_precio_compra, tasa_cambio_precio_compra, marca_cilindro, capacidad_cilindro, fecha_elaboracion_cilindro, marca_kit, modelo_regulador, serial_regulador, codigo_unico_conversion, serial1, descripcion_siniestro, fecha_pago_venta, estado_compra, estado_venta, estatus, fecha_ingreso, propiedad)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idUnidadBasica, "int"),
			valTpDato($frmAjusteInventario['lstAno'], "int"),
			valTpDato($rowUnidadBasica['tip_uni_bas'], "int"),
			valTpDato($rowUnidadBasica['cla_uni_bas'], "int"),
			valTpDato($rowUnidadBasica['cap_uni_bas'], "real_inglesa"),
			valTpDato($frmAjusteInventario['lstCondicion'], "int"),
			valTpDato($frmAjusteInventario['txtKilometraje'], "int"),
			valTpDato($frmAjusteInventario['lstColorExterno1'], "int"),
			valTpDato($frmAjusteInventario['lstColorExterno2'], "int"),
			valTpDato($frmAjusteInventario['lstColorInterno1'], "int"),
			valTpDato($frmAjusteInventario['lstColorInterno2'], "int"),
			valTpDato($rowUnidadBasica['ori_uni_bas'], "int"),
			valTpDato($frmAjusteInventario['lstAlmacenAjuste'], "int"),
			valTpDato($frmAjusteInventario['txtSerialCarroceriaAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtSerialMotorAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtNumeroVehiculoAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtPlacaAjuste'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmAjusteInventario['txtFechaFabricacionAjuste'])), "date"),
			valTpDato($frmAjusteInventario['txtRegistroLegalizacionAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtRegistroFederalAjuste'], "text"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['lstMoneda'], "int"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['lstMoneda'], "int"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtMarcaCilindro'], "text"),
			valTpDato($frmAjusteInventario['txtCapacidadCilindro'], "text"),
			valTpDato($frmAjusteInventario['txtFechaCilindro'], "text"),
			valTpDato($frmAjusteInventario['txtMarcaKit'], "text"),
			valTpDato($frmAjusteInventario['txtModeloRegulador'], "text"),
			valTpDato($frmAjusteInventario['txtSerialRegulador'], "text"),
			valTpDato($frmAjusteInventario['txtCodigoUnico'], "text"),
			valTpDato($frmAjusteInventario['txtSerial1'], "text"),
			valTpDato("", "text"),
			valTpDato("", "date"),
			valTpDato($frmAjusteInventario['txtEstadoCompraAjuste'], "text"),
			valTpDato($txtEstadoVenta, "text"),
			valTpDato(1, "boolean"), // Null = Anulada, 1 = Activa
			valTpDato("NOW()", "campo"),
			valTpDato("PROPIO", "text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nQuery:".$insertSQL); }
		$idUnidadFisica = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	if (!($idTradeIn > 0)) {
		if ($frmAjusteInventario['lstTipoVale'] == 3) { // 3 = Nota de Crédito de CxC
			// INSERTA LA UNIDAD EN EL DETALLE DE LA NOTA DE CREDITO
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo (id_nota_credito, id_unidad_fisica, costo_compra, precio_unitario)
			VALUE (%s, %s, %s, %s);",
				valTpDato($frmAjusteInventario['hddIdDcto'], "int"),
				valTpDato($idUnidadFisica, "int"),
				valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
				valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nQuery:".$insertSQL); }
			$idNotaCreditoDetalleVehiculo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// BUSCA LOS DATOS DE LOS IMPUESTOS DE LA UNIDAD
			$queryUnidadBasicaImpuesto = sprintf("SELECT
				iva.idIva AS id_iva,
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (6,2);",
				valTpDato($idUnidadBasica, "int"));
			$rsUnidadBasicaImpuesto = mysql_query($queryUnidadBasicaImpuesto);
			if (!$rsUnidadBasicaImpuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($rowUnidadBasicaImpuesto = mysql_fetch_assoc($rsUnidadBasicaImpuesto)) {
				$hddIdIvaItm = $rowUnidadBasicaImpuesto['id_iva'];
				$hddIvaItm = $rowUnidadBasicaImpuesto['iva'];
				
				$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo_impuesto (id_nota_credito_detalle_vehiculo, id_impuesto, impuesto) 
				VALUE (%s, %s, %s);",
					valTpDato($idNotaCreditoDetalleVehiculo, "int"),
					valTpDato($hddIdIvaItm, "int"),
					valTpDato($hddIvaItm, "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
				
			$arrayIdDctoContabilidad[] = array(
				$frmAjusteInventario['hddIdDcto'],
				$idModulo,
				"NOTA_CREDITO");
		}
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
										WHERE clave_mov.id_clave_movimiento = %s)
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato($idClaveMovimiento, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		switch ($frmAjusteInventario['lstTipoMovimiento']) {
			case 2 : // ENTRADA
				// REGISTRA EL VALE DE ENTRADA
				$insertSQL = sprintf("INSERT INTO an_vale_entrada (numeracion_vale_entrada, id_empresa, fecha, id_documento, id_unidad_fisica, id_cliente, id_clave_movimiento, subtotal_factura, tipo_vale_entrada, observacion)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($numeroActual, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato(date("Y-m-d"), "date"),
					valTpDato($frmAjusteInventario['hddIdDcto'], "int"),
					valTpDato($idUnidadFisica, "int"),
					valTpDato($idCliente, "int"),
					valTpDato($idClaveMovimiento, "int"),
					valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
					valTpDato($frmAjusteInventario['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
					valTpDato($frmAjusteInventario['txtObservacion'], "text"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idVale = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				$arrayIdDctoContabilidad[] = array(
					$idVale,
					$idModulo,
					"ENTRADA");
				
				$estadoKardex = 0;
				break;
			case 4 : // SALIDA
				// REGISTRA EL VALE DE SALIDA
				$insertSQL = sprintf("INSERT INTO an_vale_salida (numeracion_vale_salida, id_empresa, fecha, id_unidad_fisica, id_cliente, id_clave_movimiento, subtotal_factura, tipo_vale_salida, observacion)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($numeroActual, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato(date("Y-m-d"), "date"),
					valTpDato($idUnidadFisica, "int"),
					valTpDato($idCliente, "int"),
					valTpDato($idClaveMovimiento, "int"),
					valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
					valTpDato($frmAjusteInventario['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
					valTpDato($frmAjusteInventario['txtObservacion'], "text"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idVale = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				$arrayIdDctoContabilidad[] = array(
					$idVale,
					$idModulo,
					"SALIDA");
				
				$estadoKardex = 1;
				break;
		}
		
		// REGISTRA EL MOVIMIENTO DEL ARTICULO
		$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idVale, "int"),
			valTpDato($idUnidadBasica, "int"),
			valTpDato($idUnidadFisica, "int"),
			valTpDato($frmAjusteInventario['lstTipoMovimiento'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
			valTpDato($idClaveMovimiento, "int"),
			valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
			valTpDato(1, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato($estadoKardex, "int"), // 0 = Entrada, 1 = Salida
			valTpDato("NOW()", "campo"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL ESTADO DE VENTA DE LA UNIDAD FÍSICA
		$updateSQL = sprintf("UPDATE an_unidad_fisica SET
			estado_venta = %s
		WHERE id_unidad_fisica = %s;",
			valTpDato($txtEstadoVenta, "text"),
			valTpDato($idUnidadFisica, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL ESTADO DE LA UNIDAD FÍSICA
		$updateSQL = sprintf("UPDATE an_unidad_fisica SET
			estatus = %s
		WHERE id_unidad_fisica = %s
			AND estado_venta IN ('DEVUELTO');",
			valTpDato(NULL, "int"), // NULL = Anulada, 1 = Activa
			valTpDato($idUnidadFisica, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	if (str_replace(",", "", $frmAjusteInventario['txtMontoPago']) >= 0 
	|| str_replace(",", "", $frmAjusteInventario['txtMontoAnticipo']) >= 0) {
		$Result1 = guardarAnticipoCxC($frmAjusteInventario);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->script($Result1[5]);
			$arrayIdDctoContabilidad[] = array(
				$Result1[1],
				$Result1[2],
				"ANTICIPO_CXC");
			$idAnticipoTradeIn = $Result1[1];
			$idNotaCargoAntCxCTradeIn = $Result1[3];
			$idNotaCreditoAntCxCTradeIn = $Result1[4];
		}
	}
	
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$Result1 = guardarAnticipoCxC($frmAjusteInventario, $valor);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[5]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"ANTICIPO_CXC");
				$idAnticipo = $Result1[1];
			}
		}
	}
	
	if ($frmAjusteInventario['txtIdMotivo'] > 0) {
		if (str_replace(",", "", $frmAjusteInventario['hddMontoCxP']) > 0) {
			$Result1 = guardarNotaCargoCxP($frmAjusteInventario);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"NOTA_CARGO_CXP");
				$idNotaCargoCxP = $Result1[1];
			}
		} else if (str_replace(",", "", $frmAjusteInventario['hddMontoCxP']) < 0) {
			$Result1 = guardarNotaCreditoCxP($frmAjusteInventario);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"NOTA_CREDITO_CXP");
				$idNotaCreditoCxP = $Result1[1];
			}
		}
	}
	
	if ($frmAjusteInventario['txtIdMotivoCxC'] > 0) {
		if (str_replace(",", "", $frmAjusteInventario['hddMontoCxC']) > 0) {
			$Result1 = guardarNotaCargoCxC($frmAjusteInventario);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"NOTA_CARGO_CXC");
				$idNotaCargoCxC = $Result1[1];
			}
		} else if (str_replace(",", "", $frmAjusteInventario['hddMontoCxC']) < 0) {
			$Result1 = guardarNotaCreditoCxC($frmAjusteInventario);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"NOTA_CREDITO_CXC");
				$idNotaCreditoCxC = $Result1[1];
			}
		}
	}
	
	if ($idTradeIn > 0) {
		// VERIFICA EL ESTATUS DEL ANTICIPO
		$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo cxc_ant
		WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
		$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
		
		// ACTUALIZA LOS VALORES DEL TRADE-IN
		$updateSQL = sprintf("UPDATE an_tradein SET
			allowance = %s,
			payoff = %s,
			acv = %s,
			total_credito = %s
		WHERE id_tradein = %s;",
			valTpDato($frmAjusteInventario['txtAllowance'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtPayoff'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtAcv'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtCreditoNeto'], "real_inglesa"),
			valTpDato($idTradeIn, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		if (str_replace(",", "", $frmAjusteInventario['txtAllowance']) != str_replace(",", "", $frmAjusteInventario['txtAllowanceAnt'])) {
			if ($idNotaCargoCxP > 0) {
				$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_cargo_cxp, id_empleado_registro, monto)
				VALUES (%s, %s, %s, %s);",
					valTpDato($idUnidadFisica, "int"),
					valTpDato($idNotaCargoCxP, "int"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato($frmAjusteInventario['txtMontoCxP'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
			
			if ($idNotaCreditoCxP > 0) {
				$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_credito_cxp, id_empleado_registro, monto)
				VALUES (%s, %s, %s, %s);",
					valTpDato($idUnidadFisica, "int"),
					valTpDato($idNotaCreditoCxP, "int"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato($frmAjusteInventario['txtMontoCxP'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
			
			if ($idNotaCargoCxC > 0) {
				$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_cargo_cxc, id_empleado_registro, monto)
				VALUES (%s, %s, %s, %s);",
					valTpDato($idUnidadFisica, "int"),
					valTpDato($idNotaCargoCxC, "int"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato($frmAjusteInventario['txtMontoCxC'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
			
			if ($idNotaCreditoCxC > 0) {
				$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_credito_cxc, id_empleado_registro, monto)
				VALUES (%s, %s, %s, %s);",
					valTpDato($idUnidadFisica, "int"),
					valTpDato($idNotaCreditoCxC, "int"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato($frmAjusteInventario['txtMontoCxC'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		// SI EL ANTICIPO DEL TRADE-IN YA FUE APLICADO, EL DOCUMENTO (NC o ND) QUE AFECTA LA UTILIDAD DEL VEHICULO VENDIDO NO PODRA SER
		// RELACIONADO AL ANTICIPO. POR ESA RAZON SE VERA REFLEJADO EN EL COSTO DEL VEHICULO USADO
		if (in_array($rowAnticipo,array(2,3,4))) {
			if (str_replace(",", "", $frmAjusteInventario['txtAllowance']) != str_replace(",", "", $frmAjusteInventario['txtAcv'])
			|| str_replace(",", "", $frmAjusteInventario['txtAcv']) != str_replace(",", "", $frmAjusteInventario['txtAcvAnt'])
			|| str_replace(",", "", $frmAjusteInventario['txtAllowance']) != str_replace(",", "", $frmAjusteInventario['txtAllowanceAnt'])) {
				if ($idNotaCargoAntCxCTradeIn > 0) {
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_cargo_cxc, id_empleado_registro, monto)
					VALUES (%s, %s, %s, %s);",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($idNotaCargoAntCxCTradeIn, "int"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($frmAjusteInventario['txtMontoAntCxC'], "real_inglesa"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
				
				if ($idNotaCreditoAntCxCTradeIn > 0) {
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_credito_cxc, id_empleado_registro, monto)
					VALUES (%s, %s, %s, %s);",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($idNotaCreditoAntCxCTradeIn, "int"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($frmAjusteInventario['txtMontoAntCxC'], "real_inglesa"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		// ACTUALIZA EL COSTO DE LOS AGREGADOS
		$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
			costo_agregado = (SELECT SUM(IF((id_factura_cxp IS NOT NULL
											OR id_nota_cargo_cxp IS NOT NULL
											OR id_nota_credito_cxc IS NOT NULL
											OR id_vale_salida IS NOT NULL), 1, (-1)) * monto) FROM an_unidad_fisica_agregado uni_fis_agregado
								WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica
									AND uni_fis_agregado.estatus = 1)
		WHERE id_unidad_fisica = %s;",
			valTpDato($idUnidadFisica, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		$insertSQL = sprintf("INSERT INTO an_tradein (id_anticipo, id_unidad_fisica, id_vale_entrada, id_nota_cargo_cxp, id_nota_cargo_cxc, id_proveedor, allowance, payoff, acv, total_credito, id_empleado, tipo_registro)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idAnticipoTradeIn, "int"),
			valTpDato($idUnidadFisica, "int"),
			valTpDato($idVale, "int"),
			valTpDato($idNotaCargoCxP, "int"),
			valTpDato($idNotaCargoCxC, "int"),
			valTpDato($idProveedorAdeudado, "int"),
			valTpDato($frmAjusteInventario['txtAllowance'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtPayoff'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtAcv'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtCreditoNeto'], "real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato(1, "int")); // 1 = Nuevo, 2 = Vehiculo ya Registrado, 3 = Trade In ya Registrado
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idTradeIn = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// INSERTA LA AUDITORIA DE LOS MONTOS INGRESADOS
	$insertSQL = sprintf("INSERT INTO an_tradein_auditoria (id_tradein, allowance, acv, payoff, total_credito, id_empleado_registro)
	VALUES (%s, %s, %s, %s, %s, %s)",
		valTpDato($idTradeIn, "int"),
		valTpDato($frmAjusteInventario['txtAllowance'], "real_inglesa"),
		valTpDato($frmAjusteInventario['txtAcv'], "real_inglesa"),
		valTpDato($frmAjusteInventario['txtPayoff'], "real_inglesa"),
		valTpDato($frmAjusteInventario['txtCreditoNeto'], "real_inglesa"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ASOCIACION DEL DOCUMENTO (NC o ND) QUE AFECTA LA UTILIDAD DEL VEHICULO VENDIDO AL ANTICIPO DEL TRADE-IN 
	if ($idCliente > 0 && ($idNotaCargoAntCxCTradeIn > 0 || $idNotaCreditoAntCxCTradeIn > 0)) {
		// SI EL ANTICIPO FUE APLICADO NO LO ASOCIA AL ANTICIPO DEL TRADE-IN
		// (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		if (in_array($rowAnticipo['estadoAnticipo'],array(2,3,4))) {
			$insertSQL = sprintf("INSERT INTO an_tradein_cxc (id_tradein, id_nota_cargo_cxc, id_nota_credito_cxc, id_cliente, estatus)
			VALUES (%s, %s, %s, %s, %s)",
				valTpDato($idTradeIn, "int"),
				valTpDato($idNotaCargoAntCxCTradeIn, "int"),
				valTpDato($idNotaCreditoAntCxCTradeIn, "int"),
				valTpDato($idCliente, "int"),
				valTpDato(1, "int")); // Null = Anulado, 1 = Activo
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			$insertSQL = sprintf("INSERT INTO an_tradein_cxc (id_tradein, id_nota_cargo_cxc, id_nota_credito_cxc, id_anticipo, id_cliente, estatus)
			VALUES (%s, %s, %s, %s, %s, %s)",
				valTpDato($idTradeIn, "int"),
				valTpDato($idNotaCargoAntCxCTradeIn, "int"),
				valTpDato($idNotaCreditoAntCxCTradeIn, "int"),
				valTpDato($idAnticipoTradeIn, "int"),
				valTpDato($idCliente, "int"),
				valTpDato(1, "int")); // Null = Anulado, 1 = Activo
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	if ($idProveedorAdeudado > 0 && ($idNotaCargoCxP > 0 || $idNotaCreditoCxP > 0)) {
		$insertSQL = sprintf("INSERT INTO an_tradein_cxp (id_tradein, id_nota_cargo_cxp, id_nota_credito_cxp, id_proveedor, estatus)
		VALUES (%s, %s, %s, %s, %s)",
			valTpDato($idTradeIn, "int"),
			valTpDato($idNotaCargoCxP, "int"),
			valTpDato($idNotaCreditoCxP, "int"),
			valTpDato($idProveedorAdeudado, "int"),
			valTpDato(1, "int")); // Null = Anulado, 1 = Activo
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	if ($idCliente > 0 && ($idNotaCargoCxC > 0 || $idNotaCreditoCxC > 0)) {
		$insertSQL = sprintf("INSERT INTO an_tradein_cxc (id_tradein, id_nota_cargo_cxc, id_nota_credito_cxc, id_cliente, estatus)
		VALUES (%s, %s, %s, %s, %s)",
			valTpDato($idTradeIn, "int"),
			valTpDato($idNotaCargoCxC, "int"),
			valTpDato($idNotaCreditoCxC, "int"),
			valTpDato($idCliente, "int"),
			valTpDato(1, "int")); // Null = Anulado, 1 = Activo
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");

	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "ENTRADA") {
				$idVale = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeEntradaRe")) { generarValeEntradaRe($idVale,"",""); } break;
					case 2 : if (function_exists("generarValeEntradaVe")) { generarValeEntradaVe($idVale,"",""); } break;
				}
			} else if ($tipoDcto == "SALIDA") {
				$idVale = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeSalidaRe")) { generarValeSalidaRe($idVale,"",""); } break;
					case 1 : if (function_exists("generarValeSe")) { generarValeSe($idVale,"",""); } break;
					case 2 : if (function_exists("generarValeSalidaVe")) { generarValeSalidaVe($idVale,"",""); } break;
				}
			} else if ($tipoDcto == "NOTA_CREDITO") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 2 : if (function_exists("generarNotasVentasVe")) { generarNotasVentasVe($idNotaCredito,"",""); } break;
				}
			} else if ($tipoDcto == "ANTICIPO_CXC") {
				$idAnticipo = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 2 : if (function_exists("generarAnticiposVe")) { generarAnticiposVe($idAnticipo,"",""); } break;
				}
			} else if ($tipoDcto == "NOTA_CARGO_CXP") {
				$idNotaCargo = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasCargoCpRe")) { generarNotasCargoCpRe($idNotaCargo,"",""); } break;
					case 1 : if (function_exists("generarNotasCargoCpRe")) { generarNotasCargoCpRe($idNotaCargo,"",""); } break;
					case 2 : if (function_exists("generarNotasCargoCpVe")) { generarNotasCargoCpVe($idNotaCargo,"",""); } break;
					case 3 : if (function_exists("generarNotasCargoCpAd")) { generarNotasCargoCpAd($idNotaCargo,"",""); } break;
				}
			} else if ($tipoDcto == "NOTA_CREDITO_CXP") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
				}
			} else if ($tipoDcto == "NOTA_CARGO_CXC") {
				$idNotaCargo = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasCargoRe")) { generarNotasCargoRe($idNotaCargo,"",""); } break;
					case 1 : if (function_exists("generarNotasCargoRe")) { generarNotasCargoRe($idNotaCargo,"",""); } break;
					case 2 : if (function_exists("generarNotasCargoVe")) { generarNotasCargoVe($idNotaCargo,"",""); } break;
					//case 3 : if (function_exists("generarComprasAd")) { generarComprasAd($idNotaCargo,"",""); } break;
				}
			} else if ($tipoDcto == "NOTA_CREDITO_CXC") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert(("Trade In guardado con éxito."));
	
	if ($idVale > 0) {
		switch ($frmAjusteInventario['lstTipoMovimiento']) {
			case 2 : $objResponse->script(sprintf("verVentana('reportes/an_ajuste_inventario_vale_entrada_imp.php?id=%s', 960, 550);", $idVale)); break;
			case 4 : $objResponse->script(sprintf("verVentana('reportes/an_ajuste_inventario_vale_salida_imp.php?id=%s', 960, 550);", $idVale)); break;
		}
	}
	
	$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	
	$objResponse->loadCommands(listaUnidadFisica(
		$frmListaUnidadFisica['pageNum'],
		$frmListaUnidadFisica['campOrd'],
		$frmListaUnidadFisica['tpOrd'],
		$frmListaUnidadFisica['valBusq']));
	
	return $objResponse;
}

function guardarTradeInCxP($frmTradeInCxP, $frmListaUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_tradein_list","editar")) { return $objResponse; }
	
	$idTradeInCxP = $frmTradeInCxP['hddIdTradeInCxP'];
	
	mysql_query("START TRANSACTION;");
	
	$udpateSQL = sprintf("UPDATE an_tradein SET
		id_nota_cargo_cxp = (SELECT tradein_cxp.id_nota_cargo_cxp FROM an_tradein_cxp tradein_cxp
							WHERE tradein_cxp.id_tradein = (SELECT id_tradein FROM an_tradein_cxp WHERE id_tradein_cxp = %s)
								AND tradein_cxp.id_proveedor = %s
								AND tradein_cxp.estatus = 1),
		id_proveedor = %s
	WHERE id_tradein = (SELECT id_tradein FROM an_tradein_cxp WHERE id_tradein_cxp = %s);",
		valTpDato($idTradeInCxP, "int"),
		valTpDato($frmTradeInCxP['lstProveedorTradeInCxP'], "int"),
		valTpDato($frmTradeInCxP['lstProveedorTradeInCxP'], "int"),
		valTpDato($idTradeInCxP, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($udpateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	if ($frmTradeInCxP['hddIdNotaCargoAnt'] > 0 && $frmTradeInCxP['hddIdNotaCargo'] > 0) {
		$objResponse->script("
		byId('txtTotalOrdenTradeInCxPAnt').className = 'inputCompleto';
		byId('txtTotalOrdenTradeInCxP').className = 'inputCompleto';");
		
		if (str_replace(",", "", $frmTradeInCxP['txtTotalOrdenTradeInCxP']) != str_replace(",", "", $frmTradeInCxP['txtTotalOrdenTradeInCxPAnt'])) {
			$objResponse->script("
			byId('txtTotalOrdenTradeInCxPAnt').className = 'inputCompletoErrado';
			byId('txtTotalOrdenTradeInCxP').className = 'inputCompletoErrado';");
			return $objResponse->alert("Los montos de las Notas de Débito deben coincidir.");
		}
		
		// ANULA LA NOTA DE CARGO ANTERIOR
		$udpateSQL = sprintf("UPDATE an_tradein_cxp SET
			estatus = NULL,
			fecha_anulado = %s,
			id_empleado_anulado = %s
		WHERE id_tradein_cxp = %s
			AND estatus = 1;",
			valTpDato("NOW()", "campo"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($idTradeInCxP, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($udpateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA LA NUEVA NOTA DE CARGO
		$insertSQL = sprintf("INSERT INTO an_tradein_cxp (id_tradein, id_nota_cargo_cxp, id_proveedor, estatus)
		SELECT id_tradein, %s, %s, %s FROM an_tradein_cxp WHERE id_tradein_cxp = %s;",
			valTpDato($frmTradeInCxP['hddIdNotaCargo'], "int"),
			valTpDato($frmTradeInCxP['txtIdProvTradeInCxP'], "int"),
			valTpDato(1, "boolean"),
			valTpDato($idTradeInCxP, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(("Nota de Débito guardada con éxito."));
	$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	
	$objResponse->loadCommands(listaUnidadFisica(
		$frmListaUnidadFisica['pageNum'],
		$frmListaUnidadFisica['campOrd'],
		$frmListaUnidadFisica['tpOrd'],
		$frmListaUnidadFisica['valBusq']));
	
	return $objResponse;
}

function insertarPoliza($frmAjusteInventario) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAjusteInventario['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$contFila++;
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPiePoliza').before('".
		"<tr id=\"trItm:%s\" align=\"left\">".
			"<td colspan=\"2\" title=\"trItm:%s\">".
			"<fieldset><legend class=\"legend\">Datos del Anticipo</legend>".
				"<table width=\"%s\">".
				"<tr>".
					"<td>".
						"<input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
						"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdClienteItm%s\" name=\"hddIdClienteItm%s\" value=\"%s\"/>".
					"</td>".
					"<td>".
						"<table width=\"%s\">".
                        "<tr>".
                        	"<td valign=\"top\" width=\"%s\">".
                            	"<table border=\"0\" width=\"%s\">".
                                "<tr align=\"left\">".
                                    "<td align=\"right\" class=\"tituloCampo\" width=\"%s\"><span class=\"textoRojoNegrita\">*</span>Nro. Anticipo:</td>".
                                    "<td width=\"%s\">".
                                        "<input type=\"text\" id=\"txtNumeroAnticipo%s\" name=\"txtNumeroAnticipo%s\" placeholder=\"Por Asignar\" readonly=\"readonly\" size=\"20\" style=\"text-align:center\"/>".
                                    "</td>".
                                    "<td align=\"right\" class=\"tituloCampo\" width=\"%s\"><span class=\"textoRojoNegrita\">*</span>Forma de Pago:</td>".
                                    "<td width=\"%s\">%s</td>".
                                "</tr>".
                                "<tr align=\"left\">".
                                    "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Módulo:</td>".
                                    "<td>%s</td>".
                                    "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Concepto de Pago:</td>".
                                    "<td>%s</td>".
                                "</tr>".
                                "<tr align=\"left\">".
                                	"<td></td>".
                                	"<td></td>".
                                    "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Monto Anticipo:</td>".
                                    "<td><input type=\"text\" id=\"txtMontoPago%s\" name=\"txtMontoPago%s\" class=\"inputCompletoHabilitado\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\"/>".
										"<input type=\"hidden\" id=\"txtMontoAnticipo%s\" name=\"txtMontoAnticipo%s\"/>".
										"<input type=\"hidden\" id=\"hddMontoAnticipo%s\" name=\"hddMontoAnticipo%s\"/></td>".
                                "</tr>".
                                "</table>".
							"</td>".
                        	"<td valign=\"top\" width=\"%s\">".
                            	"<table width=\"%s\">".
                                "<tr align=\"left\">".
                                    "<td class=\"tituloCampo\">Observación:</td>".
                                "</tr>".
                                "<tr align=\"left\">".
                                    "<td><textarea id=\"txtObservacion%s\" name=\"txtObservacion%s\" class=\"inputCompletoHabilitado\" rows=\"3\"></textarea></td>".
                                "</tr>".
                                "</table>".
                            "</td>".
                        "</tr>".
                        "</table>".
					"</td>".
				"</tr>".
				"</table>".
			"</fieldset>".
			"</td>".
		"</tr>');
		
		byId('txtMontoPago%s').onblur = function() { xajax_calcularTradeIn(xajax.getFormValues('frmAjusteInventario')); setFormatoRafk(this,2); }
		byId('txtMontoAnticipo%s').onblur = function() { xajax_calcularTradeIn(xajax.getFormValues('frmAjusteInventario')); setFormatoRafk(this,2); }", 
		$contFila,
			$contFila,
				"100%",
					$contFila,
					$contFila,
					$contFila, $contFila, $contFila,
						"100%",
							"60%",
								"100%",
									"20%",
									"30%",
										$contFila, $contFila,
									"20%",
									"30%", cargaLstFormaPagoItem("lstFormaPago".$contFila, 11),
									cargaLstModuloItem("lstModulo".$contFila),
									cargaLstConceptoPagoItem("lstConceptoPago".$contFila, 'OT', ""),
									$contFila, $contFila,
									$contFila, $contFila,
									$contFila, $contFila,
							"40%",
								"100%",
									$contFila, $contFila,
		$contFila,
		$contFila);
	
	return $objResponse->script($htmlItmPie);
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanClienteCxC;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CR&Eacute;DITO");
	
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
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$valCadBusq[2]."', '".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['ci_cliente'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
	
	$objResponse->assign("divLista2","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
        
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif_lrif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
	
	$objResponse->assign("divLista2","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaMontoTradeIn($idTradeIn) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT 
		tradein.id_tradein,
		tradein.id_anticipo,
		tradein.id_unidad_fisica,
		tradein.allowance,
		tradein.payoff,
		tradein.acv,
		tradein.total_credito,
		tradein.id_proveedor,
		cxc_ant.montoNetoAnticipo,
		cxc_ant.numeroAnticipo,
		cxc_ant.idDepartamento,
		cxc_ant.id_empresa,
		cxc_ant.observacionesAnticipo,
		pg_modulos.descripcionModulo,
		cliente.id,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
		cxc_pago.id_concepto,
		cj_conceptos_formapago.descripcion,
		formapagos.idFormaPago,
		formapagos.nombreFormaPago,
		(SELECT cp_proveedor.nombre FROM cp_proveedor WHERE cp_proveedor.id_proveedor = tradein.id_proveedor) as nombre_cliente_adeudado
	FROM an_tradein tradein
		INNER JOIN cj_cc_anticipo cxc_ant ON (tradein.id_anticipo = cxc_ant.idAnticipo)
		INNER JOIN pg_modulos ON (cxc_ant.idDepartamento = pg_modulos.id_modulo)
		INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		INNER JOIN cj_conceptos_formapago ON (cxc_pago.id_concepto = cj_conceptos_formapago.id_concepto)
		INNER JOIN formapagos ON (cj_conceptos_formapago.id_formapago = formapagos.idFormaPago)
	WHERE id_tradein = %s
	LIMIT 1;",
		valTpDato($idTradeIn, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$htmlTblIni = "<table border=\"0\" width=\"298\">";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\" width=\"45%\">".("Allowance:")."</td>";
		$htmlTb .= "<td width=\"55%\">".number_format($row['allowance'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("ACV:")."</td>";
		$htmlTb .= "<td>".number_format($row['acv'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Payoff:")."</td>";
		$htmlTb .= "<td>".number_format($row['payoff'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Crédito Neto:")."</td>";
		$htmlTb .= "<td>".number_format($row['total_credito'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divPrecios","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("modulo LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ingreso_egreso LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
			valTpDato("%".$valCadBusq[3]."%", "text"));
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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

function listaNotaCargo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nd.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = cxp_nd.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = cxp_nd.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_nd.numero_notacargo LIKE %s
		OR cxp_nd.numero_control_notacargo LIKE %s
		OR (SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
			WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) LIKE %s
		OR cxp_nd.observacion_notacargo LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxp_nd.id_notacargo,
		cxp_nd.numero_notacargo,
		cxp_nd.numero_control_notacargo,
		cxp_nd.fecha_notacargo,
		cxp_nd.fecha_origen_notacargo,
		cxp_nd.fecha_vencimiento_notacargo,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		cxp_nd.id_modulo,
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		cxp_nd.estatus_notacargo,
		cxp_nd.observacion_notacargo,
		cxp_nd.total_cuenta_pagar,
		cxp_nd.saldo_notacargo,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_notadecargo cxp_nd
		INNER JOIN cp_proveedor prov ON (cxp_nd.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_motivo motivo ON (cxp_nd.id_motivo = motivo.id_motivo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "14%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "fecha_origen_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "fecha_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Nota Cargo Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "fecha_vencimiento_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Nota Cargo");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "numero_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota Cargo");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "46%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "8%", $pageNum, "saldo_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Nota Cargo");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Nota Cargo");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNotaCargo('".$row['id_notacargo']."', xajax.getFormValues('frmTradeInCxP'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_origen_notacargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_notacargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_vencimiento_notacargo']))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".(($row['id_factura_planmayor'] > 0 || $row['id_detalles_pedido_compra'] > 0) ? "<img src=\"../img/iconos/ico_plan_mayor.png\" title=\"Nota de Débito de Factura por Plan Mayor\"/>" : "")."</td>";
					$htmlTb .= "<td align=\"right\" width=\"100%\">".$row['numero_notacargo']."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['serial_carroceria']) > 0) ? "<tr><td><span class=\"textoNegrita_10px\">".utf8_encode($row['serial_carroceria'])."</span></td></tr>" : "";
				$htmlTb .= ($row['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$row['id_motivo'].".- ".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['observacion_notacargo']) > 0) ? "<tr><td>".utf8_encode($row['observacion_notacargo'])."</td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_notacargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_cuenta_pagar'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divLista2","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('SINIESTRADO', 'DISPONIBLE', 'RESERVADO', 'VENDIDO', 'ENTREGADO', 'PRESTADO', 'ACTIVO FIJO', 'INTERCAMBIO', 'DEVUELTO', 'ERROR EN TRASPASO')");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = alm.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[1])."'", "defined", "'".str_replace(",","','",$valCadBusq[1])."'"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[2])."'", "defined", "'".str_replace(",","','",$valCadBusq[2])."'"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cxc_ant.numeroAnticipo LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
        
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[5] == "0") {
			$sqlBusq .= $cond.sprintf("tradein.anulado IS NULL");
		} else if($valCadBusq[5] == "1") {
			$sqlBusq .= $cond.sprintf("tradein.anulado = %s",
				valTpDato($valCadBusq[5], "int"));
		}
	}
        
	if ($valCadBusq[6] != "" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(tradein.tiempo_registro) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[6])),"date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[7])),"date"));
	}
	
	$query = sprintf("SELECT DISTINCT
		vw_iv_modelo.id_uni_bas,
		vw_iv_modelo.nom_uni_bas,
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
		(CASE vw_iv_modelo.catalogo
			WHEN 0 THEN ''
			WHEN 1 THEN 'En Catálogo'
		END) AS mostrar_catalogo
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) 
			INNER JOIN an_tradein tradein ON (uni_fis.id_unidad_fisica = tradein.id_unidad_fisica)
			INNER JOIN cj_cc_anticipo cxc_ant ON (tradein.id_anticipo = cxc_ant.IdAnticipo)
			INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id) %s", $sqlBusq);
		
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
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "1%", $pageNum, "anulado", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Unidad F&iacute;sica"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "4%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Anticipo"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "tiempo_registro", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Registro"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Color"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Estado Venta"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Almac&eacute;n"));
			$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "saldoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo Anticipo"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "montoNetoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total Anticipo"));		
			$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "costo_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Costo"));
		$htmlTh .= "<td colspan=\"7\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"30\">".($row['vehiculo'].((strlen($row['mostrar_catalogo']) > 0) ? " <b>[".$row['mostrar_catalogo']."]</b>" : ""))."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'], "int"));
		
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
				cxc_ant.idAnticipo,
				cxc_ant.numeroAnticipo,                       
				cxc_ant.id_empresa as id_empresa_anticipo,                       
				cxc_ant.estatus,                       
				cxc_ant.saldoAnticipo,
				cxc_ant.montoNetoAnticipo,
				tradein.id_tradein,
				tradein.tiempo_registro,
				tradein.anulado,
				tradein.id_vale_entrada,
				
				(SELECT GROUP_CONCAT(tradein_cxp.id_nota_cargo_cxp SEPARATOR ', ') FROM an_tradein_cxp tradein_cxp
				WHERE tradein_cxp.id_tradein = tradein.id_tradein
					AND (tradein_cxp.estatus = 1 OR (tradein_cxp.estatus IS NULL AND DATE(tradein_cxp.fecha_anulado) > DATE(NOW())))) AS id_nota_cargo_cxp,
				
				tradein.id_nota_cargo_cxc,
				cliente.id,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
				recibo.numeroReporteImpresion,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (an_ve.fecha IS NOT NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
				(CASE
					WHEN (an_ve.fecha IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
				END),
			0) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			alm.nom_almacen,
			uni_fis.costo_compra,
			(uni_fis.precio_compra - uni_fis.costo_depreciado - uni_fis.costo_trade_in) AS precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			
			(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
			WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND an_ve.fecha IS NOT NULL
				AND an_ve.tipo_vale_entrada = 1)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
				INNER JOIN an_tradein tradein ON (uni_fis.id_unidad_fisica = tradein.id_unidad_fisica)
				INNER JOIN cj_cc_anticipo cxc_ant ON (tradein.id_anticipo = cxc_ant.IdAnticipo)
				INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
				INNER JOIN pg_reportesimpresion recibo ON (cxc_ant.idAnticipo = recibo.idDocumento AND recibo.tipoDocumento = 'AN' AND recibo.id_departamento = 2) %s %s", $sqlBusq, $sqlBusq2);
		
		$queryUnidadFisica .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayTotal = NULL;
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			$imgActivo = ($rowUnidadFisica['anulado'] === "1") ? "../img/iconos/ico_rojo.gif" : "../img/iconos/ico_verde.gif";
			
			switch($rowUnidadFisica['estado_venta']) {
				case "SINIESTRADO" : $class = "class=\"divMsjError\""; break;
				case "DISPONIBLE" : $class = "class=\"divMsjInfo\""; break;
				case "RESERVADO" : $class = "class=\"divMsjAlerta\""; break;
				case "VENDIDO" : $class = "class=\"divMsjInfo3\""; break;
				case "ENTREGADO" : $class = "class=\"divMsjInfo4\""; break;
				case "ACTIVO FIJO" : $class = "class=\"divMsjInfo5\""; break;
				default : $class = ""; break;
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\"><img src=\"".$imgActivo."\" /></td>";
				$htmlTb .= "<td align=\"right\" title=\"Id Trade-In: ".$rowUnidadFisica['id_tradein']."\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
				$htmlTb .= "<td align=\"center\" title=\"Id Anticipo: ".$rowUnidadFisica['idAnticipo']."\">".$rowUnidadFisica['numeroAnticipo']."</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= date("d-m-Y",strtotime($rowUnidadFisica['tiempo_registro']));
					$htmlTb .= ($rowUnidadFisica['dias_inventario'] > 0) ? "<br><span class=\"textoNegrita_9px\">".($rowUnidadFisica['dias_inventario']." días")."</span>" : "";
				$htmlTb .= "</td>";
				$htmlTb .= "<td title=\" id: ".$rowUnidadFisica['id']."\">".utf8_encode($rowUnidadFisica['nombre_cliente'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr class=\"textoNegrita_10px\">";
						$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['condicion_unidad'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">".utf8_encode($rowUnidadFisica['estado_venta'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowUnidadFisica['saldoAnticipo'], 2, ".", ",")."</td>";
				$htmlTb .= sprintf("<td align=\"right\" onmouseover=\"Tip('<div id=divPrecios></div><div id=divCostos></div>', TITLE, '%s) Trade In (%s - %s: %s)', WIDTH, 300); xajax_listaMontoTradeIn('%s');\" onmouseout=\"UnTip();\">%s</td>",
					$rowUnidadFisica['id_unidad_fisica'],
					utf8_encode($row['vehiculo']),
					$spanSerialCarroceria,
					utf8_encode($rowUnidadFisica['serial_carroceria']),
					$rowUnidadFisica['id_tradein'],
					number_format($rowUnidadFisica['montoNetoAnticipo'], 2, ".", ","));
				$htmlTb .= "<td align=\"right\">";
					$htmlTb .= number_format($rowUnidadFisica['precio_compra'], 2, ".", ",");
					$htmlTb .= ($rowUnidadFisica['costo_agregado'] != 0) ? "<br><label class=\"textoVerdeNegrita_10px\" title=\"".htmlentities("Total Agregados")."\">[".number_format($rowUnidadFisica['costo_agregado'], 2, ".", ",")."]</label>" : "";
					$htmlTb .= ($rowUnidadFisica['costo_depreciado'] > 0) ? "<br><label class=\"textoRojoNegrita_10px\" title=\"".htmlentities("Total Depreciación")."\">[-".number_format($rowUnidadFisica['costo_depreciado'], 2, ".", ",")."]</label>" : "";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\">";
				// SI EL ANTICIPO ESTA ANULADO Y EL TRADE-IN ESTA ACTIVO, PERMITIR GENERAR OTRO ANTICIPO
				if ($rowUnidadFisica['estatus'] === "0" && $rowUnidadFisica['anulado'] !== "1") {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aGenerar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadFisica', '%s');\"><img class=\"puntero\" src=\"../img/iconos/book_next.png\" title=\"Volver a Generar Anticipo\"/></a>",
						$contFila,
						$rowUnidadFisica['id_tradein']);
				}
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
				// SI EL ANTICIPO ESTA ACTIVO, EL TRADE-IN ESTA ACTIVO Y LA  UNIDAD NO ESTA VENDIDA O ENTREGADA
				if ($rowUnidadFisica['estatus'] === "1" && $rowUnidadFisica['anulado'] != "1" && !in_array($rowUnidadFisica['estado_venta'],array("VENDIDO", "ENTREGADO"))) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAjusteInventario', '-1', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Trade-In\"/></a>",
						$contFila,
						$rowUnidadFisica['id_tradein']);
				}
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
				if ($rowUnidadFisica['id_nota_cargo_cxp']) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aCambiar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblTradeInCxP', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_cambio.png\" title=\"Cambiar Nota de Débito CxP\"/></a>",
						$contFila,
						$rowUnidadFisica['id_tradein']);
				}
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
				if ($rowUnidadFisica['id_vale_entrada']) {
					$htmlTb .= sprintf("<a onclick=\"verVentana('reportes/an_ajuste_inventario_vale_entrada_imp.php?id=%s', 960, 550);\"><img class=\"puntero\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Vale Entrada PDF\"/></a>",
						$rowUnidadFisica['id_vale_entrada']);                                                                
				}
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= sprintf("<a onclick=\"verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s',960,550);\"><img class=\"puntero\" src=\"../img/iconos/page.png\" title=\"Ver Anticipo\"/></a>",
						$rowUnidadFisica['idAnticipo']);                                                                
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
				if ($rowUnidadFisica['id_nota_cargo_cxp']) {
					$htmlTb .= sprintf("<a onclick=\"verVentana('../cxp/reportes/cp_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img class=\"puntero\" src=\"../img/iconos/page_green.png\" title=\"Ver Nota Cargo CxP\"/></a>",
						$rowUnidadFisica['id_nota_cargo_cxp']);   
				}                                                             
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
				if ($rowUnidadFisica['id_nota_cargo_cxc']) {
					$htmlTb .= sprintf("<a onclick=\"verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img class=\"puntero\" src=\"../img/iconos/page_red.png\" title=\"Ver Nota Cargo CxC\"/></a>",
						$rowUnidadFisica['id_nota_cargo_cxc']);   
				}                                                             
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotal[13] += $rowUnidadFisica['precio_compra'];
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">"."Subtotal:"."</td>";
			$htmlTb .= "<td>".number_format($contFila2, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"7\"></td>";
		$htmlTb .= "</tr>";
	}
	if ($pageNum == $totalPages) {
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
				cxc_ant.idAnticipo,
				cxc_ant.numeroAnticipo,                       
				cxc_ant.id_empresa as id_empresa_anticipo,                       
				cxc_ant.estatus,                       
				cxc_ant.saldoAnticipo,
				cxc_ant.montoNetoAnticipo,
				tradein.id_tradein,
				tradein.tiempo_registro,
				tradein.anulado,
				tradein.id_vale_entrada,
				
				(SELECT GROUP_CONCAT(tradein_cxp.id_nota_cargo_cxp SEPARATOR ', ') FROM an_tradein_cxp tradein_cxp
				WHERE tradein_cxp.id_tradein = tradein.id_tradein
					AND (tradein_cxp.estatus = 1 OR (tradein_cxp.estatus IS NULL AND DATE(tradein_cxp.fecha_anulado) > DATE(NOW())))) AS id_nota_cargo_cxp,
				
				tradein.id_nota_cargo_cxc,
				cliente.id,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
				recibo.numeroReporteImpresion,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (an_ve.fecha IS NOT NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
				(CASE
					WHEN (an_ve.fecha IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
				END),
			0) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			alm.nom_almacen,
			uni_fis.costo_compra,
			(uni_fis.precio_compra - uni_fis.costo_depreciado - uni_fis.costo_trade_in) AS precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND an_ve.fecha IS NOT NULL
				AND an_ve.tipo_vale_entrada = 1)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
				INNER JOIN an_tradein tradein ON (uni_fis.id_unidad_fisica = tradein.id_unidad_fisica)
				INNER JOIN cj_cc_anticipo cxc_ant ON (tradein.id_anticipo = cxc_ant.IdAnticipo)
				INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
				INNER JOIN pg_reportesimpresion recibo ON (cxc_ant.idAnticipo = recibo.idDocumento AND recibo.tipoDocumento = 'AN' AND recibo.id_departamento = 2) %s;", $sqlBusq);
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$contFila2++;
			
			$arrayTotalFinal[12] = $contFila2;
			$arrayTotalFinal[13] += $rowUnidadFisica['precio_compra'];
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">"."Total de Totales:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"7\"></td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadFisica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaVehiculos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('DISPONIBLE', 'PRESTADO', 'ACTIVO FIJO', 'INTERCAMBIO', 'DEVUELTO', 'ERROR EN TRASPASO')");
	
	//if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = alm.id_empresa))",
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"),
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	//}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s		
		OR numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
        
        
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_unidad_fisica NOT IN (SELECT tradein.id_unidad_fisica FROM an_tradein tradein WHERE tradein.id_unidad_fisica = uni_fis.id_unidad_fisica)");
                   
        
	$query = sprintf("SELECT 
		vw_iv_modelo.id_uni_bas,
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
			uni_fis.id_unidad_fisica,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					cxp_fact.fecha_origen
				WHEN (an_ve.fecha IS NOT NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
				(CASE
					WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(cxp_fact.fecha_origen)
					WHEN (an_ve.fecha IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
				END),
			0) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			cxp_fact.numero_factura_proveedor,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
			AND an_ve.fecha IS NOT NULL
			AND an_ve.tipo_vale_entrada = 1)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
                %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
        
        
        if (!$rsLimit) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nLinea: ".$queryLimit); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
                $htmlTh .= "<td></td>";		
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "2%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Unidad F&iacute;sica"));		
		//$htmlTh .= ordenarCampo("xajax_listaVehiculos", "9%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "9%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Color"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Ingreso"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "4%", $pageNum, "(TO_DAYS(NOW()) - TO_DAYS(cxp_fact.fecha_origen))", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("D&iacute;as"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "8%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Estado Venta"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Asignaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "14%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "8%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Almac&eacute;n"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "7%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Fact. Compra"));		                
                $htmlTh .= ordenarCampo("xajax_listaVehiculos", "8%", $pageNum, "costo_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Costo"));
		
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
                        
				$htmlTb .= "<td align=\"center\"><button title=\"Seleccionar\" onclick=\"xajax_asignarVehiculo(".$row['id_unidad_fisica'].");\" type=\"button\"><img src=\"../img/iconos/tick.png\"></button></td>";
				$htmlTb .= "<td align=\"right\">".($row['id_unidad_fisica'])."</td>";
				
				//$htmlTb .= "<td align=\"center\" title=\" id: ".$row['id']."\">".$row['nombre_cliente']."</td>";
				$htmlTb .= "<td>".utf8_encode($row['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($row['serial_carroceria'])."</td>";
				$htmlTb .= "<td>".utf8_encode($row['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
				$htmlTb .= "<td align=\"center\">".implode("-",array_reverse(explode("-",$rowUnidadFisica['fecha_origen'])))."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['dias_inventario'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($row['estado_venta'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['idAsignacion'])."</td>";
				$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($row['nom_almacen'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['numero_factura_proveedor'])."</td>";				
                                $htmlTb .= "<td align=\"right\">".number_format($row['precio_compra'], 2, ".", ",")."</td>";
				
				
			$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"18\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaVehiculos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
        $objResponse->script("byId('divFlotante3').style.display = '';");
        $objResponse->script("centrarDiv(byId('divFlotante3'));");
		
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	//$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (2)");//2 = vehiculos
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstFormaPago(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM formapagos WHERE idFormaPago IN (11)");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstFormaPago\" name=\"lstFormaPago\" onchange=\"cambiar()\" style=\"width:99%\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)){
		$selected = ($selId == $row['idFormaPago']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPago']."\">".utf8_encode($row['nombreFormaPago'])."</option>";
	}
	$html .= sprintf("</select>");
	
	$objResponse->assign("tdTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarConceptoPago(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cj_conceptos_formapago WHERE id_formapago = 11 AND estatus = 1 AND id_concepto = 2");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstConceptoPago\" name=\"lstConceptoPago\" style=\"width:99%\">";
		//$html .= sprintf("<option value=\"-1\">[ Seleccione ]");
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value=\"%s\">%s</option>",$row['id_concepto'], utf8_encode($row['descripcion']));
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstConceptoPago","innerHTML",$html);
	
	return $objResponse;
}

function validarAperturaCaja($xajax = 1){
	$objResponse = new xajaxResponse();
        
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$fecha = date("Y-m-d");
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM an_apertura WHERE statusAperturaCaja <> 0 AND fechaAperturaCaja NOT LIKE %s AND id_empresa = %s",
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
        if (!$rsCierreCaja){ $arrayApertura = array(false, mysql_error()."\n\nLine: ".__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCierreCaja); }
	
	if (mysql_num_rows($rsCierreCaja) > 0) {
		$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
		$fechaUltimaApertura = date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja']));
		$objResponse->alert("Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
		usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();");
		
		$arrayApertura = array(false, "Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
	} else {
		//VERIFICA SI LA CAJA TIENE APERTURA
		//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
		$queryVerificarApertura = sprintf("SELECT * FROM an_apertura WHERE fechaAperturaCaja = %s AND statusAperturaCaja <> 0 AND id_empresa = %s",
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura){ $arrayApertura = array(false, mysql_error()."\n\nLine: ".__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL:".$queryVerificarApertura); }
		
		if (mysql_num_rows($rsVerificarApertura) == 0){
			$objResponse->alert("Esta caja no tiene apertura.");
			
			usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();");
			
			$arrayApertura = array(false, "Esta caja no tiene apertura.");
		}    
	}
	
	if ($xajax) {
		return $objResponse;
	} else {
		return $arrayApertura;
	}
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"asignarTipoVale");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"buscarCarroceria");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"calcularTradeIn");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacen");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstColor");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicion");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCompraBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVentaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstPaisOrigen");
$xajax->register(XAJAX_FUNCTION,"cargaLstProveedorTradeInCxP");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"cargaLstUso");
$xajax->register(XAJAX_FUNCTION,"eliminarPoliza");
$xajax->register(XAJAX_FUNCTION,"formAjusteInventario");
$xajax->register(XAJAX_FUNCTION,"generarNuevoAnticipo");
$xajax->register(XAJAX_FUNCTION,"guardarAjusteInventario");
$xajax->register(XAJAX_FUNCTION,"guardarTradeInCxP");
$xajax->register(XAJAX_FUNCTION,"insertarPoliza");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaMontoTradeIn");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaNotaCargo");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");

$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarVehiculo");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarVehiculo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstFormaPago");
$xajax->register(XAJAX_FUNCTION,"cargarConceptoPago");
$xajax->register(XAJAX_FUNCTION,"formEditarTradeIn");
$xajax->register(XAJAX_FUNCTION,"formTradeIn");
$xajax->register(XAJAX_FUNCTION,"formTradeInCxP");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaVehiculos");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");

function cargaLstModuloItem($nombreObjeto, $selId = "") {
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (2)");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
		$html .= ($totalRows > 1) ? "<option value=\"-1\">[ Seleccione ]</option>" : "";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstFormaPagoItem($nombreObjeto, $selId = "") {
	$query = sprintf("SELECT * FROM formapagos WHERE idFormaPago IN (11)
	ORDER BY idFormaPago");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
		$html .= ($totalRows > 1) ? "<option value=\"-1\">[ Seleccione ]</option>" : "";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idFormaPago']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPago']."\">".utf8_encode($row['nombreFormaPago'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstConceptoPagoItem($nombreObjeto, $aliasFormaPago, $selId = "") {
	$query = sprintf("SELECT concepto_forma_pago.*
	FROM cj_conceptos_formapago concepto_forma_pago
		INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
	WHERE forma_pago.aliasFormaPago LIKE %s
		AND concepto_forma_pago.id_concepto IN (7,8)
		AND concepto_forma_pago.estatus = 1
	ORDER BY idFormaPago",
		valTpDato($aliasFormaPago, "text"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
		$html .= ($totalRows > 1) ? "<option value=\"-1\">[ Seleccione ]</option>" : "";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_concepto']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_concepto']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function guardarAnticipoCxC($frmAjusteInventario, $contFila = ""){
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	//$frmAjusteInventario['txtSubTotal'] = implode("",explode(",",$frmAjusteInventario['txtSubTotal']));//no se usa anticipo
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	$idCliente = $frmAjusteInventario['txtIdCliente'];
	
	// OTRO
	$idModulo = $frmAjusteInventario['lstModulo'.$contFila];
	$idCaja = (in_array($idModulo, array(2))) ? 1 : 2; // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	$idFormaPago = $frmAjusteInventario['lstFormaPago'.$contFila];
	$idConcepto = $frmAjusteInventario['lstConceptoPago'.$contFila];
	$txtMontoPago = str_replace(",", "", $frmAjusteInventario['txtMontoPago'.$contFila]);
	$txtMontoAnticipo = str_replace(",", "", $frmAjusteInventario['txtMontoAnticipo'.$contFila]);
	if ($txtMontoPago > $txtMontoAnticipo) {
		$existePagoND = true;
		$txtMontoAnticipo = $txtMontoPago;
	}
	$txtObservacion = $frmAjusteInventario['txtObservacion'.$contFila];
	$idBancoCliente = 1;
	$idBancoCompania = 1;
	$numeroCuentaCliente = "-";
	$numeroCuenta = "-";
	$numeroDocumento = "-";
	$campo = "saldoOtro";
	$tomadoEnCierre = 2; // 0 = Pago Insertado; 1 = Pendiente por Depositar; 2 = Pago Depositado
	$txtMontoSaldoCaja = $txtMontoPago;
	
	// BUSCA LOS DATOS DE LA CAJA APERTURADA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
	$queryAperturaCaja = sprintf("SELECT * FROM an_apertura
	WHERE idCaja = %s
		AND statusAperturaCaja IN (1,2)
		AND (an_apertura.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = an_apertura.id_empresa));",
		valTpDato($idCaja, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsAperturaCaja = mysql_query($queryAperturaCaja);
	if (!$rsAperturaCaja) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
	$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
	
	if (str_replace(",", "", $frmAjusteInventario['hddMontoAnticipo'.$contFila]) >= 0) {
		// NUMERACION DEL DOCUMENTO (ANTICIPO)
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(43, "int"), // 43 = Anticipo CXC Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (ANTICIPO)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualAnticipo = $numeroActual;
		
		// INSERTA LOS DATOS DEL ANTICIPO
		$sqlInsertAnticipo = sprintf("INSERT INTO cj_cc_anticipo (idCliente, id_empleado_creador, montoNetoAnticipo, saldoAnticipo, totalPagadoAnticipo, fechaAnticipo, observacionesAnticipo, estadoAnticipo, numeroAnticipo, idDepartamento, id_empresa)
		VALUES (%s, %s, %s, %s, %s, NOW(), %s, %s, %s, %s, %s)",
			valTpDato($idCliente, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($txtMontoAnticipo, "real_inglesa"),
			valTpDato($txtMontoAnticipo, "real_inglesa"),
			valTpDato($txtMontoAnticipo, "real_inglesa"),
			valTpDato($txtObservacion, "text"),
			valTpDato(1, "int"),// 0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
			valTpDato($numeroActualAnticipo, "int"),
			valTpDato($idModulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsInsertAnticipo = mysql_query($sqlInsertAnticipo);
		if (!$rsInsertAnticipo) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idAnticipo = mysql_insert_id();
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("AN", "text"),
			valTpDato($idAnticipo, "int"),
			valTpDato("NOW()", "campo"),
			valTpDato("3", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// NUMERACION DEL DOCUMENTO (Recibos de Pagos)
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
		if (!$rsNumeracion) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (Recibos de Pagos)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$sqlInsertReporteImpresion = sprintf("INSERT INTO pg_reportesimpresion (fechaDocumento, numeroReporteImpresion, tipoDocumento, idDocumento, idCliente, id_departamento, id_empleado_creador)
		VALUES(%s, %s, %s, %s, %s, %s, %s)",
			valTpDato(date("Y-m-d"), "date"),
			valTpDato($numeroActualPago, "text"),
			valTpDato("AN", "text"),
			valTpDato($idAnticipo, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($idModulo, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$rsInsertReporteImpresion = mysql_query($sqlInsertReporteImpresion);
		if (!$rsInsertReporteImpresion) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idReporteImpresion = mysql_insert_id();
		
		// INSERTA EL PAGO DEL ANTICIPO
		$sqlInsertDetalleAnticipo = sprintf("INSERT INTO cj_cc_detalleanticipo (id_reporte_impresion, tipoPagoDetalleAnticipo, id_forma_pago, id_concepto, bancoClienteDetalleAnticipo, bancoCompaniaDetalleAnticipo, numeroCuentaCliente, numeroCuentaCompania, numeroControlDetalleAnticipo, montoDetalleAnticipo, idAnticipo, fechaPagoAnticipo, tomadoEnCierre, idCaja, idCierre)
		VALUES(%s, (SELECT aliasFormaPago FROM formapagos WHERE idFormaPago = %s), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idReporteImpresion, "int"),
			valTpDato($idFormaPago,"text"),
			valTpDato($idFormaPago, "int"),
			valTpDato($idConcepto, "int"),
			valTpDato($idBancoCliente, "int"),
			valTpDato($idBancoCompania, "int"),
			valTpDato($numeroCuentaCliente, "int"),
			valTpDato($numeroCuenta,"text"),
			valTpDato($numeroDocumento,"text"),
			valTpDato($txtMontoPago,"real_inglesa"),
			valTpDato($idAnticipo, "int"),
			valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
			valTpDato($tomadoEnCierre, "int"),
			valTpDato($idCaja, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato(0, "int"));
		$rsInsertDetalleAnticipo = mysql_query($sqlInsertDetalleAnticipo);
		if (!$rsInsertDetalleAnticipo) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idDetalleAnticipo = mysql_insert_id();
		
		// INSERTA EL CONCEPTO DE PAGO PARA HISTORICO
		$queryAnticipoConcepto = sprintf("INSERT INTO cj_cc_anticipo_concepto (id_anticipo, numero_anticipo, idCliente, fecha_registro, caja, id_usuario, monto_total_anticipo, id_empresa, observacion, id_concepto)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idAnticipo, "int"),
			valTpDato($numeroActualAnticipo, "int"),
			valTpDato($idCliente, "int"),
			valTpDato("NOW()", "campo"),
			valTpDato($idCaja, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($_SESSION['idUsuarioSysGts'], "int"),
			valTpDato($txtMontoPago, "real_inglesa"),
			valTpDato($idEmpresa, "int"),
			valTpDato("Anticipo Por Concepto / Vehiculos", "text"),
			valTpDato($idConcepto, "int"));
		$rsAnticipoConcepto = mysql_query($queryAnticipoConcepto);
		if (!$rsAnticipoConcepto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// BUSCA LOS DATOS DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo
		WHERE idAnticipo = %s
			AND estadoAnticipo IN (1,2,3)",
			valTpDato($idAnticipo, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
		$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
	
		if ($totalRowsAnticipo > 0) {
			// ACTUALIZA LOS SALDOS EN LA APERTURA DE LA CAJA
			$updateSQL = sprintf("UPDATE an_apertura SET
				%s = %s + %s,
				saldoCaja = saldoCaja + %s
			WHERE id = %s",
				$campo, $campo, valTpDato($txtMontoPago, "real_inglesa"),
				valTpDato($txtMontoSaldoCaja, "real_inglesa"),
				valTpDato($rowAperturaCaja['id'], "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	} else {
		// BUSCA LOS DATOS DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$queryAnticipo = sprintf("SELECT *
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN an_tradein tradein ON (cxc_ant.idAnticipo = tradein.id_anticipo)
		WHERE tradein.id_tradein = %s",
			valTpDato($frmAjusteInventario['hddIdTradeInAjusteInventario'], "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
		$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
		
		$idAnticipo = $rowAnticipo['idAnticipo'];
		$numeroActualAnticipo = $rowAnticipo['numeroAnticipo'];
	}
	
	
	if (str_replace(",", "", $frmAjusteInventario['hddMontoAntCxC'.$contFila]) > 0) {
		if ($existePagoND == true) {
			$arrayPago = array(
				"cbx2" => array(1),
				"txtIdFormaPago1" => 7,
				"hddIdPago1" => "",
				"txtMonto1" => $frmAjusteInventario['txtMontoAntCxC'.$contFila],
				"txtIdNumeroDctoPago1" => $idAnticipo,
				"txtIdBancoCliente1" => "",
				"txtCuentaClientePago1" => "",
				"txtNumeroDctoPago1" => "",
				"txtIdBancoCompania1" => "",
				"txtIdCuentaCompaniaPago1" => "",
				"txtFechaDeposito1" => "",
				"txtTipoTarjeta1" => "");
		}
		
		// NOTA DE CREDITO QUE SE GUARDA POR IR EN CONTRA DE LA UTILIDAD
		$Result1 = guardarNotaCargoCxC(
			array(
				"txtIdEmpresa" => $idEmpresa,
				"txtIdCliente" => $idCliente,
				"txtIdMotivoCxC" => $frmAjusteInventario['txtIdMotivoAntCxC'.$contFila],
				"txtMontoCxC" => $frmAjusteInventario['txtMontoAntCxC'.$contFila],
				"hddObservacionCxC" => "NOTA DE DEBITO ASOCIADA AL ANTICIPO NRO. ".$numeroActualAnticipo." POR TRADE-IN".
					((strlen($frmAjusteInventario['txtSerialCarroceriaAjuste']) > 0) ? " ".$spanSerialCarroceria.": ".$frmAjusteInventario['txtSerialCarroceriaAjuste'] : "").
					((strlen($frmAjusteInventario['txtPlacaAjuste'])) ? " ".$spanPlaca.": ".$frmAjusteInventario['txtPlacaAjuste'] : "").
					", MONTO A FAVOR DE LA UTILIDAD DEL NEGOCIO",
				"txtObservacionCxC" => ""),
			$arrayPago);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return array(false, $Result1[1]); 
		} else if ($Result1[0] == true) {
			$script = $Result1[3];
			$arrayIdDctoContabilidad[] = array(
				$Result1[1],
				$Result1[2],
				"NOTA_CARGO_CXC");
			$idNotaCargoCxC = $Result1[1];
		}
	} else if (str_replace(",", "", $frmAjusteInventario['hddMontoAntCxC'.$contFila]) < 0) {
		// NOTA DE CREDITO QUE SE GUARDA POR IR EN CONTRA DE LA UTILIDAD
		$Result1 = guardarNotaCreditoCxC(array(
			"txtIdEmpresa" => $idEmpresa,
			"txtIdCliente" => $idCliente,
			"txtIdMotivoCxC" => $frmAjusteInventario['txtIdMotivoAntCxC'.$contFila],
			"txtMontoCxC" => $frmAjusteInventario['txtMontoAntCxC'.$contFila],
			"hddObservacionCxC" => "NOTA DE CREDITO ASOCIADA AL ANTICIPO NRO. ".$numeroActualAnticipo." POR TRADE-IN".
				((strlen($frmAjusteInventario['txtSerialCarroceriaAjuste']) > 0) ? " ".$spanSerialCarroceria.": ".$frmAjusteInventario['txtSerialCarroceriaAjuste'] : "").
				((strlen($frmAjusteInventario['txtPlacaAjuste'])) ? " ".$spanPlaca.": ".$frmAjusteInventario['txtPlacaAjuste'] : "").
				", MONTO CONTRA LA UTILIDAD DEL NEGOCIO.",
			"txtObservacionCxC" => ""));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return array(false, $Result1[1]); 
		} else if ($Result1[0] == true) {
			$script = $Result1[3];
			$arrayIdDctoContabilidad[] = array(
				$Result1[1],
				$Result1[2],
				"NOTA_CREDITO_CXC");
			$idNotaCreditoCxC = $Result1[1];
		}
	}
	
	if ($idNotaCreditoCxC > 0 && str_replace(",", "", $frmAjusteInventario['hddMontoAnticipo'.$contFila]) > 0) {
		$idFormaPago = 8; // 8 = Nota de Crédito
		$idConcepto = "";
		$idCheque = "";
		$tipoTransferencia = "";
		$idBancoCliente = 1;
		$idBancoCompania = 1;
		$numeroCuentaCliente = "-";
		$numeroCuenta = "-";
		$numeroDocumento = $idNotaCreditoCxC;
		$campo = "saldoNotaCredito";
		$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
		if (str_replace(",", "", $frmAjusteInventario['txtMontoAntCxC'.$contFila]) <= str_replace(",", "", $frmAjusteInventario['hddMontoAnticipo'.$contFila])) {
			$txtMonto = str_replace(",", "", $frmAjusteInventario['txtMontoAntCxC'.$contFila]);
		} else {
			$txtMonto = str_replace(",", "", $frmAjusteInventario['hddMontoAnticipo'.$contFila]) - str_replace(",", "", $frmAjusteInventario['txtMontoPago'.$contFila]);
		}
		$txtMontoSaldoCaja = $txtMonto;
		$estatusPago = 1;
		
		if ($txtMonto > 0) {
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
				valTpDato($numeroCuenta, "text"),
				valTpDato($numeroDocumento, "text"),
				valTpDato($txtMonto, "real_inglesa"),
				valTpDato($idAnticipo, "int"),
				valTpDato($fechaRegistroPago, "date"),
				valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				valTpDato($idCaja, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato(0, "int"),
				valTpDato($estatusPago, "int")); // Null = Anulado, 1 = Activo, 2 = Pendiente
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idPago = mysql_insert_id();
			
			// ACTUALIZA LOS SALDOS EN LA APERTURA DE LA CAJA
			$updateSQL = sprintf("UPDATE an_apertura SET
				%s = %s + %s,
				saldoCaja = saldoCaja + %s
			WHERE id = %s",
				$campo, $campo, valTpDato($txtMontoPago, "real_inglesa"),
				valTpDato($txtMontoSaldoCaja, "real_inglesa"),
				valTpDato($rowAperturaCaja['id'], "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
		
		
		// ACTUALIZA EL SALDO DEL NOTA CREDITO DEPENDIENDO DE SUS PAGOS (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
			saldoNotaCredito = montoNetoNotaCredito
		WHERE idNotaCredito = %s
			AND estadoNotaCredito IN (0,1,2,3,4);",
			valTpDato($idNotaCreditoCxC, "int")); // AND (cxc_nc_det.id_concepto IS NULL OR cxc_nc_det.id_concepto NOT IN (6))
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ACTUALIZA EL SALDO DEL NOTA CREDITO SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
		$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
			saldoNotaCredito = saldoNotaCredito
								- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
										WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
											AND cxc_pago.formaPago IN (8)
											AND cxc_pago.estatus IN (1,2)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
											WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
												AND cxc_pago.formaPago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
											WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
												AND cxc_pago.idFormaPago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
											WHERE cxc_pago.numeroControlDetalleAnticipo = cxc_nc.idNotaCredito
												AND cxc_pago.id_forma_pago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0))
		WHERE cxc_nc.idNotaCredito = %s
			AND cxc_nc.estadoNotaCredito IN (0,1,2,3,4);",
			valTpDato($idNotaCreditoCxC, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ACTUALIZA EL ESTATUS DEL NOTA CREDITO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
			estadoNotaCredito = (CASE
								WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) > 0) THEN
									0
								WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) <= 0
									AND cxc_nc.idNotaCredito IN (SELECT * 
																FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (8)
																		AND cxc_pago.estatus = 1
																	
																	UNION
																	
																	SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (8)
																		AND cxc_pago.estatus = 1
																	
																	UNION
																	
																	SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																	WHERE cxc_pago.idFormaPago IN (8)
																		AND cxc_pago.estatus = 1
																	
																	UNION
																	
																	SELECT cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
																	WHERE cxc_pago.id_forma_pago IN (8)
																		AND cxc_pago.estatus = 1) AS q)) THEN
									3
								WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
									AND ROUND(montoNetoNotaCredito, 2) = ROUND(saldoNotaCredito, 2)) THEN
									1
								WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
									AND ROUND(montoNetoNotaCredito, 2) > ROUND(saldoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) > 0) THEN
									2
								WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) <= 0) THEN
									3
								WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) <= 0) THEN
									4
							END)
		WHERE cxc_nc.idNotaCredito = %s
			AND cxc_nc.estadoNotaCredito IN (0,1,2,3,4);",
			valTpDato($idNotaCreditoCxC, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	// ACTUALIZA EL SALDO Y EL MONTO PAGADO DEL ANTICIPO
	$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
		saldoAnticipo = montoNetoAnticipo,
		totalPagadoAnticipo = IFNULL((SELECT SUM(cxc_ant_det.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_ant_det
										WHERE cxc_ant_det.idAnticipo = cxc_ant.idAnticipo
											AND (cxc_ant_det.id_forma_pago NOT IN (11)
												OR (cxc_ant_det.id_forma_pago IN (11) AND cxc_ant_det.id_concepto NOT IN (6,7,8)))
											AND cxc_ant_det.id_empleado_anulado IS NULL), 0)
	WHERE cxc_ant.idAnticipo = %s;",
		valTpDato($idAnticipo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
	// ACTUALIZA EL SALDO DEL ANTICIPO DEPENDIENDO DE SUS PAGOS
	$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
		saldoAnticipo = saldoAnticipo - (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
												WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
													AND cxc_pago.formaPago = 7
													AND cxc_pago.estatus IN (1,2)), 0)
											+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
														WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
															AND cxc_pago.formaPago = 7
															AND cxc_pago.estatus IN (1,2)), 0)
											+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
														WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
															AND cxc_pago.idFormaPago = 7
															AND cxc_pago.estatus IN (1,2)), 0))
	WHERE cxc_ant.idAnticipo = %s;",
		valTpDato($idAnticipo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
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
																WHERE cxc_pago.formaPago = 7
																	AND cxc_pago.estatus = 1
																
																UNION
																
																SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																WHERE cxc_pago.formaPago = 7
																	AND cxc_pago.estatus = 1
																
																UNION
																
																SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																WHERE cxc_pago.idFormaPago = 7
																	AND cxc_pago.estatus = 1) AS q)) THEN
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
	if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$script .= sprintf("verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s',960,550);", $idAnticipo);
	
	return array(true, $idAnticipo, $idModulo, $idNotaCargoCxC, $idNotaCreditoCxC, $script);
}

function guardarNotaCargoCxC($frmAjusteInventario, $frmListaPagos) {
	$idCajaPpal = 1;
	$apertCajaPpal = "an_apertura";
	$cierreCajaPpal = "an_cierredecaja";
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	
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
		valTpDato(24, "int"), // 24 = Nota Cargo CxC
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$numeroActualControl = $numeroActual;
	
	$idCliente = $frmAjusteInventario['txtIdCliente'];
	$idMotivo = $frmAjusteInventario['txtIdMotivoCxC'];
	$txtFechaRegistro = date("d-m-Y");
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$lstTipoPago = 0; // 0 = Credito, 1 = Contado
	$txtFechaVencimiento = ($lstTipoPago == 0) ? date("d-m-Y",strtotime($txtFechaRegistro) + 2592000) : $txtFechaRegistro;
	$txtDiasCreditoCliente = (strtotime($txtFechaVencimiento) - strtotime($txtFechaRegistro)) / 86400;
	$txtSubTotalNotaCargo = str_replace(",", "", $frmAjusteInventario['txtMontoCxC']);
	$txtSubTotalDescuento = 0;
	$txtFlete = 0;
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
	$txtTotalNotaCargo = $txtSubTotalNotaCargo;
	$txtMontoExento = $txtSubTotalNotaCargo;
	$txtMontoExonerado = 0;
	$txtObservacion = $frmAjusteInventario['hddObservacionCxC'].". ".$frmAjusteInventario['txtObservacionCxC'];
	
	// INSERTA LA NOTA DE CREDITO
	$insertSQL = sprintf("INSERT INTO cj_cc_notadecargo (numeroControlNotaCargo, fechaRegistroNotaCargo, numeroNotaCargo, fechaVencimientoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, estadoNotaCargo, observacionNotaCargo, fletesNotaCargo, idCliente, idDepartamentoOrigenNotaCargo, descuentoNotaCargo, baseImponibleNotaCargo, porcentajeIvaNotaCargo, calculoIvaNotaCargo, subtotalNotaCargo, interesesNotaCargo, tipoNotaCargo, base_imponible_iva_lujo, porcentaje_iva_lujo, ivaLujoNotaCargo, diasDeCreditoNotaCargo, montoExentoNotaCargo, montoExoneradoNotaCargo, aplicaLibros, referencia_nota_cargo, id_empresa, id_motivo)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActualControl, "text"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistro)), "date"),
		valTpDato($numeroActual, "text"),
		valTpDato(date("Y-m-d",strtotime($txtFechaVencimiento)), "date"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
		valTpDato($txtObservacion, "text"),
		valTpDato($txtFlete, "real_inglesa"),
		valTpDato($idCliente, "int"),
		valTpDato($idModulo, "int"),
		valTpDato($txtSubTotalDescuento, "real_inglesa"),
		valTpDato($txtBaseImponibleIva, "real_inglesa"),
		valTpDato($txtIva, "real_inglesa"),
		valTpDato($txtSubTotalIva, "real_inglesa"),
		valTpDato($txtSubTotalNotaCargo, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($lstTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
		valTpDato($txtIvaLujo, "real_inglesa"),
		valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
		valTpDato($txtDiasCreditoCliente, "int"),
		valTpDato($txtMontoExento, "real_inglesa"),
		valTpDato($txtMontoExonerado, "real_inglesa"),
		valTpDato(0, "boolean"), // 0 = No, 1 = Si
		valTpDato(1, "int"), // 0 = Cheque Devuelto, 1 = Otros
		valTpDato($idEmpresa, "int"),
		valTpDato($idMotivo, "int"));	
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaCargo = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("ND", "text"),
		valTpDato($idNotaCargo, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistro)), "date"),
		valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// ASOCIA EL PAGO A LA NOTA DE DEBITO
	// INSERTA EL PAGO DEL DOCUMENTO (PAGO DE NOTAS DE DEBITO) SOLO SI ES DE CONTADO
	if ($idTipoPago == 1 || count($arrayObj2) > 0) { // 0 = Credito, 1 = Contado
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
		if (!$rsAperturaCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$idApertura = $rowAperturaCaja['id'];
		$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
		
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
		if (!$rsNumeracion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
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
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_nc_v (id_nota_cargo, fecha_pago)
		VALUES (%s, %s)",
			valTpDato($idNotaCargo, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoPago = mysql_insert_id();
		
		foreach($arrayObj2 as $indicePago => $valorPago) {
			$idFormaPago = $frmListaPagos['txtIdFormaPago'.$valorPago];
			
			if (!($frmListaPagos['hddIdPago'.$valorPago] > 0)) {
				if (isset($idFormaPago)) {
					$idCheque = "";
					$tipoCheque = "-";
					$idTransferencia = "";
					$tipoTransferencia = "-";
					$estatusPago = 1;
					if ($idFormaPago == 7) { // 7 = Anticipo
						$idBancoCliente = 1;
						$txtCuentaClientePago = "-";
						$idBancoCompania = 1;
						$txtCuentaCompaniaPago = "-";
						$txtIdNumeroDctoPago = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
						$campo = "saldoAnticipo";
						$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$txtMontoSaldoCaja = 0;
						
						// BUSCA LOS DATOS DEL ANTICIPO (0 = Anulado; 1 = Activo)
						$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo cxc_ant
						WHERE cxc_ant.idAnticipo = %s
							AND cxc_ant.estatus = 1;",
							valTpDato($txtIdNumeroDctoPago, "int"));
						$rsAnticipo = mysql_query($queryAnticipo);
						if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$rowAnticipo = mysql_fetch_array($rsAnticipo);
						
						// (0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
						$estatusPago = (in_array($rowAnticipo['estadoAnticipo'], array(0))) ? 2 : $estatusPago;
					}
					
					// NO SUMA 7 = Anticipo EN EL SALDO DE LA CAJA
					$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
						%s = %s + %s,
						saldoCaja = saldoCaja + %s
					WHERE id = %s;",
						$campo, $campo, valTpDato($txtMonto, "real_inglesa"),
						valTpDato($txtMontoSaldoCaja, "real_inglesa"),
						valTpDato($rowAperturaCaja['id'], "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					// INSERTA LOS PAGOS DEL DOCUMENTO
					$insertSQL = sprintf("INSERT INTO cj_det_nota_cargo (idNotaCargo, fechaPago, idFormaPago, numeroDocumento, bancoOrigen, numero_cuenta_cliente, bancoDestino, cuentaEmpresa, monto_pago, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, id_apertura, estatus, id_encabezado_nc)
					VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
						valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
						valTpDato($idFormaPago, "int"),
						valTpDato($txtIdNumeroDctoPago, "text"),
						valTpDato($idBancoCliente, "int"),
						valTpDato($txtCuentaClientePago, "text"),
						valTpDato($idBancoCompania, "int"),
						valTpDato($txtCuentaCompaniaPago, "text"),
						valTpDato($frmListaPagos['txtMonto'.$valorPago], "real_inglesa"),
						valTpDato($tipoCheque, "text"),
						valTpDato($idCheque, "int"),
						valTpDato($tipoTransferencia, "text"),
						valTpDato($idTransferencia, "int"),
						valTpDato(1, "int"),
						valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
						valTpDato($idApertura, "int"),
						valTpDato($estatusPago, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
						valTpDato($idEncabezadoPago, "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idPago = mysql_insert_id();
					
					$arrayIdDctoContabilidad[] = array(
						$idPago,
						$idModulo,
						"CAJAENTRADA");
					
					if ($idFormaPago == 7) { // 7 = Anticipo
						// ACTUALIZA EL SALDO Y EL MONTO PAGADO DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
							saldoAnticipo = montoNetoAnticipo,
							totalPagadoAnticipo = IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
															WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
																AND (cxc_pago.id_forma_pago NOT IN (11)
																	OR (cxc_pago.id_forma_pago IN (11) AND cxc_pago.id_concepto NOT IN (6,7,8)))
																AND cxc_pago.estatus IN (1,2)), 0)
						WHERE cxc_ant.idAnticipo = %s
							AND cxc_ant.estadoAnticipo IN (0,1,2);",
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int")); // AND (cxc_pago.id_concepto IS NULL OR cxc_pago.id_concepto NOT IN (6))
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
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
						WHERE cxc_ant.idAnticipo = %s
							AND cxc_ant.estadoAnticipo IN (0,1,2);",
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
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
						WHERE cxc_ant.idAnticipo = %s
							AND cxc_ant.estadoAnticipo IN (0,1,2);",
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						// VERIFICA EL SALDO DEL ANTICIPO A VER SI ESTA NEGATIVO
						$querySaldoDcto = sprintf("SELECT cxc_ant.*,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
						FROM cj_cc_anticipo cxc_ant
							INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
						WHERE idAnticipo = %s
							AND saldoAnticipo < 0;",
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int"));
						$rsSaldoDcto = mysql_query($querySaldoDcto);
						if (!$rsSaldoDcto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);			
						$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
						$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
						if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("El Anticipo Nro. ".$rowSaldoDcto['numeroAnticipo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
					}
					
					// INSERTA EL DETALLE DEL RECIBO DE PAGO
					$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
					VALUES (%s, %s)",
						valTpDato($idEncabezadoReciboPago, "int"),
						valTpDato($idPago, "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
		}
		
		// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
			saldoNotaCargo = montoTotalNotaCargo
		WHERE idNotaCargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO DEPENDIENDO DE SUS PAGOS
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
			saldoNotaCargo = saldoNotaCargo - IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
														WHERE cxc_pago.idNotaCargo = cxc_nd.idNotaCargo
															AND cxc_pago.estatus = 1), 0)
		WHERE idNotaCargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
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
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	$script = sprintf("verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s',960,550)", $idNotaCargo);
	
	return array(true, $idNotaCargo, $idModulo, $script);
}

function guardarNotaCreditoCxC($frmAjusteInventario) {
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	
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
		valTpDato(22, "int"), // 22 = Nota Crédito CxC
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$numeroActualControl = $numeroActual;
	
	$idCliente = $frmAjusteInventario['txtIdCliente'];
	$idMotivo = $frmAjusteInventario['txtIdMotivoCxC'];
	$txtFechaRegistro = date("d-m-Y");
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$lstTipoPago = 0; // 0 = Credito, 1 = Contado
	$txtFechaVencimiento = ($lstTipoPago == 0) ? date("d-m-Y",strtotime($txtFechaRegistro) + 2592000) : $txtFechaRegistro;
	$txtDiasCreditoCliente = (strtotime($txtFechaVencimiento) - strtotime($txtFechaRegistro)) / 86400;
	$txtSubTotalNotaCredito = str_replace(",", "", $frmAjusteInventario['txtMontoCxC']);
	$txtSubTotalDescuento = 0;
	$txtFlete = 0;
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
	$txtTotalNotaCredito = $txtSubTotalNotaCredito;
	$txtMontoExento = $txtSubTotalNotaCredito;
	$txtMontoExonerado = 0;
	$txtObservacion = $frmAjusteInventario['hddObservacionCxC'].". ".$frmAjusteInventario['txtObservacionCxC'];
	
	// INSERTA LA NOTA DE CREDITO
	$insertSQL = sprintf("INSERT INTO cj_cc_notacredito (numeracion_nota_credito, numeroControl, idCliente, montoNetoNotaCredito, saldoNotaCredito, fechaNotaCredito, id_clave_movimiento, id_empleado_vendedor, observacionesNotaCredito, estadoNotaCredito, idDocumento, tipoDocumento, porcentajeIvaNotaCredito, ivaNotaCredito, subtotalNotaCredito, porcentaje_descuento, subtotal_descuento, base_imponible_iva_lujo, porcentajeIvaDeLujoNotaCredito, ivaLujoNotaCredito, idDepartamentoNotaCredito, montoExoneradoCredito, montoExentoCredito, aplicaLibros, baseimponibleNotaCredito, fletesNotaCredito, id_empresa, id_orden, id_motivo)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActual, "text"),
		valTpDato($numeroActualControl, "text"),
		valTpDato($idCliente, "int"),
		valTpDato($txtTotalNotaCredito, "real_inglesa"),
		valTpDato($txtTotalNotaCredito, "real_inglesa"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistro)), "date"),
		valTpDato("", "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($txtObservacion, "text"),
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato(0, "int"),
		valTpDato("NC", "text"),
		valTpDato($txtIva, "real_inglesa"),
		valTpDato($txtSubTotalIva, "real_inglesa"),
		valTpDato($txtSubTotalNotaCredito, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($txtSubTotalDescuento, "real_inglesa"),
		valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
		valTpDato($txtIvaLujo, "real_inglesa"),
		valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
		valTpDato($idModulo, "int"),
		valTpDato($txtMontoExonerado, "real_inglesa"),
		valTpDato($txtMontoExento, "real_inglesa"),
		valTpDato(0, "boolean"), // 0 = No, 1 = Si
		valTpDato($txtBaseImponibleIva, "real_inglesa"),
		valTpDato($txtFlete, "real_inglesa"),
		valTpDato($idEmpresa, "int"),
		valTpDato("", "int"),
		valTpDato($idMotivo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistro)), "date"),
		valTpDato("4", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	$script = sprintf("verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s',960,550)", $idNotaCredito);
	
	return array(true, $idNotaCredito, $idModulo, $script);
}

function guardarNotaCargoCxP($frmAjusteInventario) {
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	$idProveedor = $frmAjusteInventario['txtIdProv'];
	
	// BUSCA LOS DATOS DEL PROVEEDOR DEL PLAN MAYOR
	$queryProv = sprintf("SELECT
		prov.id_proveedor,
		prov.nombre,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.direccion,
		prov.contacto,
		prov.correococtacto,
		prov.telefono,
		prov.fax,
		prov.credito,
		(SELECT prov_cred.diascredito FROM cp_prove_credito prov_cred
		WHERE prov_cred.id_proveedor = prov.id_proveedor) AS diascredito
	FROM cp_proveedor prov
	WHERE prov.id_proveedor = %s;",
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$idProveedor = $rowProv['id_proveedor'];
	$txtDiasCreditoProv = $rowProv['diascredito'];
	$lstTipoPago = ($rowProv['diascredito'] == 'Si' || $rowProv['diascredito'] == 1) ? 0 : 1; // 0 = Credito, 1 = Contado
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = %s
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato(3, "int"), // 3 = Nota Cargo CxP
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];

	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$idMotivo = $frmAjusteInventario['txtIdMotivo'];
	$txtFechaRegistro = date("d-m-Y");
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$txtFechaProveedor = date("d-m-Y");
	$txtFechaVencimiento = ($lstTipoPago == 0) ? suma_fechas("d-m-Y",$txtFechaProveedor,$txtDiasCreditoProv) : $txtFechaProveedor;
	$txtSubTotalNotaCargo = str_replace(",", "", $frmAjusteInventario['txtMontoCxP']);
	$txtSubTotalDescuento = 0;
	$txtFlete = 0;
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
	$txtTotalNotaCargo = $txtSubTotalNotaCargo;
	$txtMontoExento = $txtSubTotalNotaCargo;
	$txtMontoExonerado = 0;
	$txtObservacion = $frmAjusteInventario['hddObservacionCxP'].". ".$frmAjusteInventario['txtObservacionCxP'];
	
	// GUARDA LOS DATOS DE LA NOTA DE CARGO
	$insertSQL = sprintf("INSERT INTO cp_notadecargo (id_empresa, numero_notacargo, numero_control_notacargo, fecha_notacargo, id_proveedor, fecha_origen_notacargo, fecha_vencimiento_notacargo, id_modulo, estatus_notacargo, observacion_notacargo, tipo_pago_notacargo, monto_exento_notacargo, monto_exonerado_notacargo, subtotal_notacargo, subtotal_descuento_notacargo, total_cuenta_pagar, saldo_notacargo, aplica_libros_notacargo, chasis, id_detalles_pedido_compra, id_motivo, id_empleado_creador)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($numeroActual, "text"),
		valTpDato($numeroActual, "text"),
		valTpDato(date("Y-m-d",strtotime($txtFechaProveedor)), "date"),
		valTpDato($idProveedor, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistro)), "date"),
		valTpDato(date("Y-m-d",strtotime($txtFechaVencimiento)), "date"),
		valTpDato($idModulo, "int"),
		valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		valTpDato($txtObservacion, "text"),
		valTpDato($lstTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($txtMontoExento, "real_inglesa"),
		valTpDato($txtMontoExonerado, "real_inglesa"),
		valTpDato($txtSubTotalNotaCargo, "real_inglesa"),
		valTpDato($txtSubTotalDescuento, "real_inglesa"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato(0, "int"), // 1 = Si, 0 = No
		valTpDato("", "text"),
		valTpDato("", "int"),
		valTpDato($idMotivo, "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaCargo = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("ND", "text"),
		valTpDato($idNotaCargo, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistro)), "date"),
		valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$updateSQL = sprintf("UPDATE cp_prove_credito prov_cred SET
		saldoDisponible = limitecredito - (IFNULL((SELECT SUM(cxp_fact.saldo_factura) FROM cp_factura cxp_fact
													WHERE cxp_fact.id_proveedor = prov_cred.id_proveedor
														AND cxp_fact.estatus_factura IN (0,2)), 0)
											+ IFNULL((SELECT SUM(cxp_nd.saldo_notacargo) FROM cp_notadecargo cxp_nd
													WHERE cxp_nd.id_proveedor = prov_cred.id_proveedor
														AND cxp_nd.estatus_notacargo IN (0,2)), 0)
											- IFNULL((SELECT SUM(cxp_ant.saldoanticipo) FROM cp_anticipo cxp_ant
													WHERE cxp_ant.id_proveedor = prov_cred.id_proveedor
														AND cxp_ant.estado IN (1,2)), 0)
											- IFNULL((SELECT SUM(cxp_nc.saldo_notacredito) FROM cp_notacredito cxp_nc
													WHERE cxp_nc.id_proveedor = prov_cred.id_proveedor
														AND cxp_nc.estado_notacredito IN (1,2)), 0))
	WHERE prov_cred.id_proveedor = %s;",
		valTpDato($idProveedor, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$script = sprintf("verVentana('../cxp/reportes/cp_nota_cargo_pdf.php?valBusq=%s',960,550)", $idNotaCargo);
	
	return array(true, $idNotaCargo, $idModulo, $script);
}

function guardarNotaCreditoCxP($frmAjusteInventario) {
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	$idProveedor = $frmAjusteInventario['txtIdProv'];
	
	// BUSCA LOS DATOS DEL PROVEEDOR DEL PLAN MAYOR
	$queryProv = sprintf("SELECT
		prov.id_proveedor,
		prov.nombre,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.direccion,
		prov.contacto,
		prov.correococtacto,
		prov.telefono,
		prov.fax,
		prov.credito,
		(SELECT prov_cred.diascredito FROM cp_prove_credito prov_cred
		WHERE prov_cred.id_proveedor = prov.id_proveedor) AS diascredito
	FROM cp_proveedor prov
	WHERE prov.id_proveedor = %s;",
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$idProveedor = $rowProv['id_proveedor'];
	$txtDiasCreditoProv = $rowProv['diascredito'];
	$lstTipoPago = ($rowProv['diascredito'] == 'Si' || $rowProv['diascredito'] == 1) ? 0 : 1; // 0 = Credito, 1 = Contado
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = %s
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato(9, "int"), // 9 = Nota Crédito CxP
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];

	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$idMotivo = $frmAjusteInventario['txtIdMotivo'];
	$txtFechaRegistro = date("d-m-Y");
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$txtFechaProveedor = date("d-m-Y");
	$txtFechaVencimiento = ($lstTipoPago == 0) ? suma_fechas("d-m-Y",$txtFechaProveedor,$txtDiasCreditoProv) : $txtFechaProveedor;
	$txtSubTotalNotaCredito = str_replace(",", "", $frmAjusteInventario['txtMontoCxP']);
	$txtSubTotalDescuento = 0;
	$txtFlete = 0;
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
	$txtTotalNotaCredito = $txtSubTotalNotaCredito;
	$txtMontoExento = $txtSubTotalNotaCredito;
	$txtMontoExonerado = 0;
	$txtObservacion = $frmAjusteInventario['hddObservacionCxP'].". ".$frmAjusteInventario['txtObservacionCxP'];
	
	// GUARDA LOS DATOS DE LA NOTA DE CARGO
	$insertSQL = sprintf("INSERT INTO cp_notacredito (id_empresa, numero_nota_credito, numero_control_notacredito, fecha_notacredito, id_proveedor, fecha_registro_notacredito, id_departamento_notacredito, id_documento, tipo_documento, estado_notacredito, observacion_notacredito, monto_exento_notacredito, monto_exonerado_notacredito, subtotal_notacredito, subtotal_descuento, total_cuenta_pagar, saldo_notacredito, aplica_libros_notacredito, id_motivo, id_empleado_creador)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($numeroActual, "text"),
		valTpDato($numeroActual, "text"),
		valTpDato(date("Y-m-d",strtotime($txtFechaProveedor)), "date"),
		valTpDato($idProveedor, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistro)), "date"),
		valTpDato($idModulo, "int"),
		valTpDato(0, "int"),
		valTpDato("NC", "text"),
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato($txtObservacion, "text"),
		valTpDato($txtMontoExento, "real_inglesa"),
		valTpDato($txtMontoExonerado, "real_inglesa"),
		valTpDato($txtSubTotalNotaCredito, "real_inglesa"),
		valTpDato($txtSubTotalDescuento, "real_inglesa"),
		valTpDato($txtTotalNotaCredito, "real_inglesa"),
		valTpDato($txtTotalNotaCredito, "real_inglesa"),
		valTpDato(0, "int"), // 1 = Si, 0 = No
		valTpDato($idMotivo, "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistro)), "date"),
		valTpDato("4", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$updateSQL = sprintf("UPDATE cp_prove_credito prov_cred SET
		saldoDisponible = limitecredito - (IFNULL((SELECT SUM(cxp_fact.saldo_factura) FROM cp_factura cxp_fact
													WHERE cxp_fact.id_proveedor = prov_cred.id_proveedor
														AND cxp_fact.estatus_factura IN (0,2)), 0)
											+ IFNULL((SELECT SUM(cxp_nd.saldo_notacargo) FROM cp_notadecargo cxp_nd
													WHERE cxp_nd.id_proveedor = prov_cred.id_proveedor
														AND cxp_nd.estatus_notacargo IN (0,2)), 0)
											- IFNULL((SELECT SUM(cxp_ant.saldoanticipo) FROM cp_anticipo cxp_ant
													WHERE cxp_ant.id_proveedor = prov_cred.id_proveedor
														AND cxp_ant.estado IN (1,2)), 0)
											- IFNULL((SELECT SUM(cxp_nc.saldo_notacredito) FROM cp_notacredito cxp_nc
													WHERE cxp_nc.id_proveedor = prov_cred.id_proveedor
														AND cxp_nc.estado_notacredito IN (1,2)), 0))
	WHERE prov_cred.id_proveedor = %s;",
		valTpDato($idProveedor, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$script = sprintf("verVentana('../cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s',960,550)", $idNotaCredito);
	
	return array(true, $idNotaCredito, $idModulo, $script);
}
?>