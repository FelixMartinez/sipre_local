<?php


function asignarCliente($nombreObjeto, $idCliente, $idEmpresa = "", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false"){
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id = %s
	AND status = 'Activo'",
		valTpDato($idCliente, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
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
		cliente.paga_impuesto
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
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "0", "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));*/
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','0','1','".$idClaveMovimiento."','onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"');
		}");
	} else {
		$fechaVencimiento = date("d-m-Y");
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		$objResponse->assign("hddTipoPagoCliente","value",1);
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));*/
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','1','1','".$idClaveMovimiento."','onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"');
		}");
	}
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowCliente['id']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",utf8_encode($rowCliente['direccion']));
	$objResponse->assign("txtTelefono".$nombreObjeto,"value",$rowCliente['telf']);
	$objResponse->assign("txtRif".$nombreObjeto,"value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNIT".$nombreObjeto,"value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0 && $rowCliente['id'] > 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
	
	//$objResponse->script("xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
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
	$objResponse->assign("txt".$nombreObjeto,"value",htmlentities($row['descripcion']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaMotivo').click();");
	}
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente'],
		$frmBuscarCliente['hddObjDestinoCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
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

function buscarMovimiento($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstTipoMovimiento']) ? implode(",",$frmBuscar['lstTipoMovimiento']) : $frmBuscar['lstTipoMovimiento']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaMovimiento(0, "query.id_tipo_movimiento, query.id_documento", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	$idModulo = (is_array($idModulo)) ? implode(",",$idModulo) : $idModulo;
	$idTipoClave = (is_array($idTipoClave)) ? implode(",",$idTipoClave) : $idTipoClave;
	
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
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq3);
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

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT id_empleado, nombre_empleado FROM vw_pg_empleados empleado
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (2)");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstClaveMovimiento('lstClaveMovimiento', $('#lstModulo').val(), $('#lstTipoMovimiento').val());\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = (in_array($row['id_modulo'],explode(",",$selId))) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarMovimiento($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstTipoMovimiento']) ? implode(",",$frmBuscar['lstTipoMovimiento']) : $frmBuscar['lstTipoMovimiento']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/an_movimiento_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formCierreVenta($idFactura, $frmCierreVenta) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cj_factura_venta_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarCierreVenta').click();"); return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCierreVenta['cbx'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFactura = sprintf("SELECT cxc_fact.*,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	WHERE cxc_fact.idFactura = %s",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$objResponse->assign("hddIdFacturaCierreVenta","value",$idFactura);
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Cierre de Venta (Nro. Factura: ".$rowFactura['numeroFactura'].", Cliente: ".$rowFactura['nombre_cliente'].")");
	
	// BUSCA EL DETALLE DE LA FACTURA
	$queryFacturaDet = sprintf("SELECT *
	FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	WHERE cxc_fact_det_vehic.id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rsFacturaDet = mysql_query($queryFacturaDet);
	if (!$rsFacturaDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFacturaDet = mysql_num_rows($rsFacturaDet);
	while ($rowFacturaDet = mysql_fetch_array($rsFacturaDet)) {
		$Result1 = insertarItemUnidad($contFila, $rowFacturaDet['id_factura_detalle_vehiculo'], $rowFacturaDet['id_unidad_fisica'], 3);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	}
	
	// BUSCA EL DETALLE DE LA FACTURA
	$queryFacturaDet = sprintf("SELECT *
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
	WHERE cxc_fact_det_acc.id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rsFacturaDet = mysql_query($queryFacturaDet);
	if (!$rsFacturaDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFacturaDet = mysql_num_rows($rsFacturaDet);
	while ($rowFacturaDet = mysql_fetch_array($rsFacturaDet)) {
		$Result1 = insertarItemAdicional($contFila, $rowFacturaDet['id_factura_detalle_accesorios']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	}
	
	return $objResponse;
}

function guardarCierreVenta($frmCierreVenta, $frmListaMovimiento) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	if (!xvalidaAcceso($objResponse,"cj_factura_venta_list","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCierreVenta['cbx'];
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script("
			byId('txtCostoItm".$valor."').className = 'inputCompletoHabilitado';");
			
			if (!($frmCierreVenta['txtCostoItm'.$valor] >= 0)) {
				$arrayInvalido[] = "txtCostoItm".$valor;
			}
			
			if ($frmCierreVenta['hddTipoAccesorioItm'.$valor] == 3) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato
				$objResponse->script("
				byId('txtIdClienteItm".$valor."').className = 'inputHabilitado';
				byId('txtIdMotivoItm".$valor."').className = 'inputHabilitado';");
				
				if ($frmCierreVenta['txtIdMotivoItm'.$valor] > 0 && !($frmCierreVenta['txtIdClienteItm'.$valor] > 0)) {
					$arrayInvalido[] = "txtIdClienteItm".$valor;
				}
				
				if (!($frmCierreVenta['txtIdMotivoItm'.$valor] > 0) && $frmCierreVenta['txtIdClienteItm'.$valor] > 0) {
					$arrayInvalido[] = "txtIdMotivoItm".$valor;
				}
			}
		}
	}
	
	if (isset($arrayInvalido)) {
		foreach ($arrayInvalido as $indice => $valor) {
			$objResponse->script("byId('".$valor."').className = 'inputErrado'");
		}
		
		if (count($arrayInvalido) > 0) {
			return $objResponse->alert("Los campos señalados en rojo son invalidos");
		}
	}
	
	$idFactura = $frmCierreVenta['hddIdFacturaCierreVenta'];
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFactura = sprintf("SELECT cxc_fact.*,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	WHERE cxc_fact.idFactura = %s",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = $rowFactura['id_empresa'];
	
	// BUSCA LOS DATOS DE LA DEVOLUCION SI LA TIENE
	$queryNotaCred = sprintf("SELECT cxc_nc.*,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
	WHERE cxc_nc.idDocumento = %s
		AND cxc_nc.tipoDocumento LIKE 'FA';",
		valTpDato($idFactura, "int"));
	$rsNotaCred = mysql_query($queryNotaCred);
	if (!$rsNotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsNotaCred = mysql_num_rows($rsNotaCred);
	$rowNotaCred = mysql_fetch_assoc($rsNotaCred);
	
	$idNotaCredito = $rowNotaCred['idNotaCredito'];
	
	// ACTUALIZA LOS DATOS DE LA FACTURA
	$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
		estatus_factura = %s
	WHERE idFactura = %s;",
		valTpDato(2, "int"), // Null o 1 = Aprobada, 2 = Aplicada / Cerrada
		valTpDato($idFactura, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (in_array($frmCierreVenta['hddTpItm'.$valor],array(1,2))) { // 1 = Por Paquete, 2 = Individual
				$idNotaCargoCxC = "";
				if ($frmCierreVenta['hddTipoAccesorioItm'.$valor] == 3) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato
					$idCliente = $frmCierreVenta['txtIdClienteItm'.$valor];
					$idMotivo = $frmCierreVenta['txtIdMotivoItm'.$valor];
					$txtDescItm = $frmCierreVenta['txtDescItm'.$valor];
					$txtMontoCxC = str_replace(",","",$frmCierreVenta['txtPrecioItm'.$valor]) - str_replace(",","",$frmCierreVenta['txtCostoItm'.$valor]);
					
					if ($idCliente > 0 && $idMotivo > 0) {
						// NOTA DE DEBITO QUE SE GUARDA POR LA UTILIDAD DEL ADICIONAL TIPO CONTRATO
						$Result1 = guardarNotaCargoCxC(array(
							"txtIdEmpresa" => $idEmpresa,
							"txtIdCliente" => $idCliente,
							"txtIdMotivoCxC" => $idMotivo,
							"txtMontoCxC" => $txtMontoCxC,
							"hddObservacionCxC" => "NOTA DE DEBITO POR LA UTILIDAD DEL ADICIONAL (".$txtDescItm.") ASOCIADA A LA FACTURA NRO. ".$rowFactura['numeroFactura'].
								((strlen($rowFactura['ci_cliente']) > 0) ? " ".$spanClienteCxC.": ".$rowFactura['ci_cliente'] : "").
								((strlen($rowFactura['nombre_cliente'])) ? " - ".$rowFactura['nombre_cliente'] : ""),
							"txtObservacionCxC" => ""));
						if ($Result1[0] != true && strlen($Result1[1]) > 0) {
							return $objResponse->alert($Result1[1]); 
						} else if ($Result1[0] == true) {
							$script = $Result1[3];
							$arrayIdDctoContabilidad[] = array(
								$Result1[1],
								$Result1[2],
								"NOTA_CARGO_CXC");
							$idNotaCargoCxC = $Result1[1];
						}
					}
				}
				
				// ACTUALIZA LOS DATOS DEL DETALLE DE LA FACTURA
				$updateSQL = sprintf("UPDATE cj_cc_factura_detalle_accesorios SET
					costo_compra = %s,
					id_nota_cargo_cxc = %s
				WHERE id_factura_detalle_accesorios = %s;",
					valTpDato($frmCierreVenta['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato($idNotaCargoCxC, "int"),
					valTpDato($frmCierreVenta['hddIdFacturaDet'.$valor], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA LOS DATOS DEL DETALLE DE LA DEVOLUCION
				$updateSQL = sprintf("UPDATE cj_cc_nota_credito_detalle_accesorios SET
					costo_compra = %s
				WHERE id_nota_credito = %s
					AND id_accesorio IN (SELECT id_accesorio FROM cj_cc_factura_detalle_accesorios
										WHERE id_factura_detalle_accesorios = %s);",
					valTpDato($frmCierreVenta['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato($idNotaCredito, "int"),
					valTpDato($frmCierreVenta['hddIdFacturaDet'.$valor], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA LOS DATOS DEL DETALLE DEL PEDIDO
				$updateSQL = sprintf("UPDATE an_partida SET
					costo_partida = %s
				WHERE id_factura_venta = %s
					AND id_accesorio = (SELECT id_accesorio FROM cj_cc_factura_detalle_accesorios
										WHERE id_factura_detalle_accesorios = %s);",
					valTpDato($frmCierreVenta['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato($idFactura, "int"),
					valTpDato($frmCierreVenta['hddIdFacturaDet'.$valor], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			} else if ($frmCierreVenta['hddTpItm'.$valor] == 3) {
				// ACTUALIZA LOS DATOS DEL DETALLE DE LA FACTURA
				$updateSQL = sprintf("UPDATE cj_cc_factura_detalle_vehiculo SET
					costo_compra = %s
				WHERE id_factura_detalle_vehiculo = %s;",
					valTpDato($frmCierreVenta['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato($frmCierreVenta['hddIdFacturaDet'.$valor], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA LOS DATOS DEL DETALLE DE LA DEVOLUCION
				$updateSQL = sprintf("UPDATE cj_cc_nota_credito_detalle_vehiculo SET
					costo_compra = %s
				WHERE id_nota_credito = %s
					AND id_unidad_fisica IN (SELECT id_unidad_fisica FROM cj_cc_factura_detalle_vehiculo
											WHERE id_factura_detalle_vehiculo = %s);",
					valTpDato($frmCierreVenta['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato($idNotaCredito, "int"),
					valTpDato($frmCierreVenta['hddIdFacturaDet'.$valor], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA ES COSTO EN EL KARDEX
				$updateSQL = sprintf("UPDATE an_kardex SET
					costo = %s
				WHERE id_documento IN (SELECT id_factura FROM cj_cc_factura_detalle_vehiculo
										WHERE id_factura_detalle_vehiculo = %s)
					AND idUnidadFisica IN (SELECT id_unidad_fisica FROM cj_cc_factura_detalle_vehiculo
											WHERE id_factura_detalle_vehiculo = %s)
					AND tipoMovimiento = 3;",
					valTpDato($frmCierreVenta['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato($frmCierreVenta['hddIdFacturaDet'.$valor], "int"),
					valTpDato($frmCierreVenta['hddIdFacturaDet'.$valor], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$updateSQL = sprintf("UPDATE an_kardex SET
					costo = %s
				WHERE id_documento = %s
					AND idUnidadFisica IN (SELECT id_unidad_fisica FROM cj_cc_factura_detalle_vehiculo
											WHERE id_factura_detalle_vehiculo = %s)
					AND tipoMovimiento = 2;",
					valTpDato($frmCierreVenta['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato($idNotaCredito, "int"),
					valTpDato($frmCierreVenta['hddIdFacturaDet'.$valor], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Cierre de venta guardado con éxito.");
	
	$objResponse->script("byId('btnCancelarCierreVenta').click();");
	
	$objResponse->loadCommands(listaMovimiento(
		$frmListaMovimiento['pageNum'],
		$frmListaMovimiento['campOrd'],
		$frmListaMovimiento['tpOrd'],
		$frmListaMovimiento['valBusq']));
	
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
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$valCadBusq[2]."', '".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "56%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "14%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Transacción");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['modulo']) {
			case "CC" :
				$imgDctoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>";
				$descripcionModulo = "Cuentas por Cobrar";
				break;
			case "CP" :
				$imgDctoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>";
				$descripcionModulo = "Cuentas por Pagar";
				break;
			case "CJ" :
				$imgDctoModulo = "";
				$descripcionModulo = "Caja"; break;
			case "TE" :
				$imgDctoModulo = "<img src=\"../img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>";
				$descripcionModulo = "Tesoreria";
				break;
			default : $imgDctoModulo = ""; $descripcionModulo = $row['modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarMotivo('".$row['id_motivo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgDctoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($descripcionModulo)."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".(($row['ingreso_egreso'] == "I") ? "Ingreso" : "Egreso")."</td>";
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

function listaMovimiento($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
		valTpDato("2", "campo"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
		valTpDato("2", "campo"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato("2", "campo"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
		valTpDato("2", "campo"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_fact.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vale_ent.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_nc.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_fact.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
			
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("vale_sal.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
			
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("cxp_nc.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(cxp_fact.fecha_origen) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("DATE(vale_ent.fecha) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("DATE(cxc_nc.fechaNotaCredito) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("DATE(cxc_fact.fechaRegistroFactura) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("DATE(vale_sal.fecha) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("DATE(cxp_nc.fecha_registro_notacredito) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("query.id_tipo_movimiento IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vale_ent.id_clave_movimiento IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_nc.id_clave_movimiento IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_fact.id_clave_movimiento IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
			
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("vale_sal.id_clave_movimiento IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("query.id_empleado_vendedor = %s",
			valTpDato($valCadBusq[6], "int"));
	}
	
	if ($valCadBusq[7] != "" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("(query.numero_documento LIKE %s
		OR query.numero_control_documento LIKE %s
		OR query.ci_cliente LIKE %s
		OR query.nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT query.*,
		
		(CASE
			WHEN (query.tipoDocumento IN ('FA','ND')) THEN
				(CASE query.estado_pago_documento
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END)
			WHEN (query.tipoDocumento IN ('AN','NC','CH','TB')) THEN
				(CASE query.estado_pago_documento
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
					WHEN 4 THEN 'No Cancelado (Asignado)'
				END)
		END) AS estado_documento,
		
		clave_mov.clave,
		clave_mov.descripcion
	FROM (SELECT 
			cxp_fact.id_factura AS id_documento,
			cxp_fact.numero_factura_proveedor AS numero_documento,
			cxp_fact.numero_control_factura AS numero_control_documento,
			cxp_fact.fecha_factura_proveedor AS fecha_documento,
			cxp_fact.fecha_origen AS fecha_registro,
			cxp_fact.id_modulo,
			CONCAT_WS('-', prov.lrif, prov.rif) AS ci_cliente,
			prov.nombre AS nombre_cliente,
			NULL AS id_empleado_vendedor,
			cxp_fact.estatus_factura AS estado_pago_documento,
			'FA' AS tipoDocumento,
			1 AS id_tipo_movimiento,
			NULL AS tipo_documento_movimiento,
			NULL AS id_clave_movimiento,
			NULL AS numero_pedido,
			NULL AS estatus_documento
		FROM cp_factura cxp_fact
			INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor) %s
		
		UNION
			
		SELECT 
			vale_ent.id_vale_entrada,
			vale_ent.numeracion_vale_entrada,
			vale_ent.numeracion_vale_entrada,
			vale_ent.fecha,
			vale_ent.fecha,
			2 AS id_modulo,
			CONCAT_WS('-', cliente.lci, cliente.ci),
			CONCAT_WS(' ', cliente.nombre, cliente.apellido),
			NULL AS id_empleado_vendedor,
			NULL AS estado_pago_documento,
			'VE' AS tipoDocumento,
			2 AS id_tipo_movimiento,
			1 AS tipo_documento_movimiento,
			vale_ent.id_clave_movimiento,
			NULL AS numero_pedido,
			NULL AS estatus_documento
		FROM an_vale_entrada vale_ent
			INNER JOIN cj_cc_cliente cliente ON (vale_ent.id_cliente = cliente.id) %s
		
		UNION
		
		SELECT 
			cxc_nc.idNotaCredito,
			cxc_nc.numeracion_nota_credito,
			cxc_nc.numeroControl,
			cxc_nc.fechaNotaCredito,
			cxc_nc.fechaNotaCredito,
			cxc_nc.idDepartamentoNotaCredito,
			CONCAT_WS('-', cliente.lci, cliente.ci),
			CONCAT_WS(' ', cliente.nombre, cliente.apellido),
			cxc_nc.id_empleado_vendedor,
			cxc_nc.estadoNotaCredito AS estado_pago_documento,
			'NC' AS tipoDocumento,
			2 AS id_tipo_movimiento,
			2 AS tipo_documento_movimiento,
			cxc_nc.id_clave_movimiento,
			NULL AS numero_pedido,
			cxc_nc.estatus_nota_credito AS estatus_documento
		FROM cj_cc_notacredito cxc_nc
			INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id) %s
		
		UNION
			
		SELECT
			cxc_fact.idFactura,
			cxc_fact.numeroFactura,
			cxc_fact.numeroControl,
			cxc_fact.fechaRegistroFactura,
			cxc_fact.fechaRegistroFactura,
			cxc_fact.idDepartamentoOrigenFactura,
			CONCAT_WS('-', cliente.lci, cliente.ci),
			CONCAT_WS(' ', cliente.nombre, cliente.apellido),
			cxc_fact.idVendedor AS id_empleado_vendedor,
			cxc_fact.estadoFactura AS estado_pago_documento,
			'FA' AS tipoDocumento,
			3 AS id_tipo_movimiento,
			NULL AS tipo_documento_movimiento,
			cxc_fact.id_clave_movimiento,
			an_ped_vent.numeracion_pedido AS numero_pedido,
			cxc_fact.estatus_factura AS estatus_documento
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			INNER JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2) %s
		
		UNION
			
		SELECT 
			vale_sal.id_vale_salida,
			vale_sal.numeracion_vale_salida,
			vale_sal.numeracion_vale_salida,
			vale_sal.fecha,
			vale_sal.fecha,
			2 AS id_modulo,
			CONCAT_WS('-', cliente.lci, cliente.ci),
			CONCAT_WS(' ', cliente.nombre, cliente.apellido),
			NULL AS id_empleado_vendedor,
			NULL AS estado_pago_documento,
			'VS' AS tipoDocumento,
			4 AS id_tipo_movimiento,
			1 AS tipo_documento_movimiento,
			vale_sal.id_clave_movimiento,
			NULL AS numero_pedido,
			NULL AS estatus_documento
		FROM an_vale_salida vale_sal
			INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id) %s
		
		UNION
			
		SELECT 
			cxp_nc.id_notacredito,
			cxp_nc.numero_nota_credito,
			cxp_nc.numero_control_notacredito,
			cxp_nc.fecha_notacredito,
			cxp_nc.fecha_registro_notacredito,
			cxp_nc.id_departamento_notacredito,
			CONCAT_WS('-', prov.lrif, prov.rif) AS ci_cliente,
			prov.nombre AS nombre_cliente,
			NULL AS id_empleado_vendedor,
			estado_notacredito AS estado_pago_documento,
			'NC' AS tipoDocumento,
			4 AS id_tipo_movimiento,
			2 AS tipo_documento_movimiento,
			NULL AS id_clave_movimiento,
			NULL AS numero_pedido,
			NULL AS estatus_documento
		FROM cp_notacredito cxp_nc
			INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor) %s) AS query
		LEFT JOIN pg_clave_movimiento clave_mov ON (query.id_clave_movimiento = clave_mov.id_clave_movimiento) %s", $sqlBusq, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6, $sqlBusq7);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$idModulo = $row['id_modulo'];
			
		switch ($idModulo) {
			case 0 : $imgModuloDcto = "<img src=\"../img/iconos/ico_repuestos.gif\"/ title=\"Repuestos\">"; break;
			case 1 : $imgModuloDcto = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgModuloDcto = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgModuloDcto = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgModuloDcto = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgModuloDcto = "";
		}
		
		switch($row['estado_pago_documento']) {
			case "" : $class = ""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		switch ($row['id_tipo_movimiento']) {
			case 1 : // 1 = COMPRA
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_factura_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Registro Compra")."\"/><a>",
					$row['id_documento']);
				switch ($idModulo) {
					case 0: $aVerDctoAux = "../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=".$row['id_documento']; break;
					case 2: $aVerDctoAux = "../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=".$row['id_documento']; break;
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Registro Compra PDF")."\"/></a>" : "";
				break;
			case 2 : // 2 = ENTRADA
				switch ($row['tipo_documento_movimiento']) {
					case 1 : // VALE ENTRADA
						switch ($idModulo) {
							case 0 : $aVerDctoAux = "../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$row['id_documento']."|2"; break;
							case 1 : $aVerDctoAux = "../servicios/sa_devolucion_vale_salida_pdf.php?valBusq=1|".$row['id_documento']; break;
							case 2 : $aVerDctoAux = "../vehiculos/reportes/an_ajuste_inventario_vale_entrada_imp.php?id=".$row['id_documento']; break;
							default : $aVerDctoAux = "";
						}
						$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Vale Entrada PDF")."\"/></a>" : "";
						break;
					case 2 : // NOTA DE CREDITO
						$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota Crédito")."\"/><a>",
							$row['id_documento']);
						switch ($idModulo) {
							case 0 : $aVerDctoAux = "../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=".$row['id_documento']; break;
							case 1 : $aVerDctoAux = "../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=".$row['id_documento']; break;
							case 2 : $aVerDctoAux = "../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=".$row['id_documento']; break;
							case 3 : $aVerDctoAux = "../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=".$row['id_documento']; break;
							case 4 : $aVerDctoAux = "../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=".$row['id_documento']; break;
							default : $aVerDctoAux = "";
						}
						$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Nota Crédito PDF")."\"/></a>" : "";
						break;
				}
				break;
			case 3 : // 3 = VENTA
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
					$row['id_documento']);
				switch ($idModulo) {
					case 0 : $aVerDctoAux = "../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=".$row['id_documento']; break;
					case 1 : $aVerDctoAux = "../servicios/reportes/sa_factura_venta_pdf.php?valBusq=".$row['id_documento']; break;
					case 2 : $aVerDctoAux = "../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=".$row['id_documento']; break;
					case 3 : $aVerDctoAux = "../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=".$row['id_documento']; break;
					case 4 : $aVerDctoAux = "../alquiler/reportes/al_factura_venta_pdf.php?valBusq=".$row['id_documento']; break;
					default : $aVerDctoAux = "";
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Factura Venta PDF")."\"/></a>" : "";
				break;
			case 4 : // 4 = SALIDA
				switch ($row['tipo_documento_movimiento']) {
					case 1 : // VALE SALIDA
						switch ($idModulo) {
							case 0 : $aVerDctoAux = "../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$row['id_documento']."|4"; break;
							case 1 : $aVerDctoAux = "../servicios/sa_imprimir_historico_vale.php?valBusq=".$row['id_documento']."|2|3"; break;
							case 2 : $aVerDctoAux = "../vehiculos/reportes/an_ajuste_inventario_vale_salida_imp.php?id=".$row['id_documento']; break;
							default : $aVerDctoAux = "";
						}
						$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Vale Salida PDF")."\"/></a>" : "";
						break;
					case 2 : // NOTA DE CREDITO
						$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_nota_credito_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota Crédito")."\"/><a>",
							$row['id_documento']);
						$aVerDcto .= sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('../cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Nota Crédito PDF")."\"/></a>",
							$row['id_documento']);
						break;
				}
				break;
			default : $aVerDcto = "";
		}
		
		if ($row['id_tipo_movimiento'] == 1) { // 1 = Compra
			$queryDetalle = sprintf("SELECT
				vw_iv_modelo.nom_uni_bas,
				CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
				1 AS cantidad,
				cxp_fact_det_unidad.costo_unitario AS costo_compra,
				cxp_fact_det_unidad.costo_unitario AS precio_unitario,
				NULL AS id_tipo_accesorio
			FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (cxp_fact_det_unidad.id_unidad_basica = vw_iv_modelo.id_uni_bas)
			WHERE cxp_fact_det_unidad.id_factura = %s
			
			UNION
			
			SELECT 
				acc.nom_accesorio,
				acc.des_accesorio,
				cxp_fact_det_acc.cantidad,
				cxp_fact_det_acc.costo_unitario,
				cxp_fact_det_acc.costo_unitario,
				NULL AS id_tipo_accesorio
			FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				INNER JOIN an_accesorio acc ON (cxp_fact_det_acc.id_accesorio = acc.id_accesorio)
			WHERE cxp_fact_det_acc.id_factura = %s;",
				valTpDato($row['id_documento'], "int"),
				valTpDato($row['id_documento'], "int"));
		} else if ($row['id_tipo_movimiento'] == 2) { // 2 = Entrada
			if ($row['tipo_documento_movimiento'] == 1) {
				$queryDetalle = sprintf("SELECT
					vw_iv_modelo.nom_uni_bas,
					CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
					1 AS cantidad,
					subtotal_factura AS costo_compra,
					subtotal_factura AS precio_unitario,
					NULL AS id_tipo_accesorio
				FROM an_vale_entrada vale_ent
					INNER JOIN an_unidad_fisica uni_fis ON (vale_ent.id_unidad_fisica = uni_fis.id_unidad_fisica)
					INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
				WHERE vale_ent.id_vale_entrada = %s;",
					valTpDato($row['id_documento'], "int"));
			} else if ($row['tipo_documento_movimiento'] == 2) {
				$queryDetalle = sprintf("SELECT q.*,
					(CASE q.id_tipo_accesorio
						WHEN 1 THEN	'Adicional'
						WHEN 2 THEN 'Accesorio'
						WHEN 3 THEN 'Contrato'
					END) AS descripcion_tipo_accesorio
				FROM (
						SELECT
							vw_iv_modelo.nom_uni_bas,
							CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
							1 AS cantidad,
							cxc_nc_det_vehic.costo_compra,
							cxc_nc_det_vehic.precio_unitario,
							NULL AS id_tipo_accesorio
						FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
							INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
							INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
						WHERE cxc_nc_det_vehic.id_nota_credito = %s
						
						UNION
						
						SELECT 
							acc.nom_accesorio,
							acc.des_accesorio,
							cxc_nc_det_acc.cantidad,
							cxc_nc_det_acc.costo_compra,
							cxc_nc_det_acc.precio_unitario,
							cxc_nc_det_acc.id_tipo_accesorio
						FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
							INNER JOIN an_accesorio acc ON (cxc_nc_det_acc.id_accesorio = acc.id_accesorio)
						WHERE cxc_nc_det_acc.id_nota_credito = %s) AS q;",
					valTpDato($row['id_documento'], "int"),
					valTpDato($row['id_documento'], "int"));
			}
		} else if ($row['id_tipo_movimiento'] == 3) { // 3 = Venta
			$queryDetalle = sprintf("SELECT q.*,
				(CASE q.id_tipo_accesorio
					WHEN 1 THEN	'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
				END) AS descripcion_tipo_accesorio,
				cxc_nd.idNotaCargo,
				cxc_nd.numeroNotaCargo,
				cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cxc_nd.estadoNotaCargo,
				(CASE cxc_nd.estadoNotaCargo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS descripcion_estado_nota_cargo
			FROM (
					SELECT
						vw_iv_modelo.nom_uni_bas,
						CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
						1 AS cantidad,
						cxc_fact_det_vehic.costo_compra,
						cxc_fact_det_vehic.precio_unitario,
						NULL AS id_tipo_accesorio,
						NULL AS id_nota_cargo_cxc,
						uni_fis.serial_carroceria,
						uni_fis.serial_motor,
						uni_fis.serial_chasis,
						uni_fis.placa,
						cond_unidad.descripcion AS condicion_unidad
					FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
						INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
						INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
					WHERE cxc_fact_det_vehic.id_factura = %s
					
					UNION
					
					SELECT 
						acc.nom_accesorio,
						acc.des_accesorio,
						cxc_fact_det_acc.cantidad,
						cxc_fact_det_acc.costo_compra,
						cxc_fact_det_acc.precio_unitario,
						cxc_fact_det_acc.id_tipo_accesorio,
						cxc_fact_det_acc.id_nota_cargo_cxc,
						NULL AS serial_carroceria,
						NULL AS serial_motor,
						NULL AS serial_chasis,
						NULL AS placa,
						NULL AS condicion_unidad
					FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
						INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
					WHERE cxc_fact_det_acc.id_factura = %s) AS q
				LEFT JOIN cj_cc_notadecargo cxc_nd ON (q.id_nota_cargo_cxc = cxc_nd.idNotaCargo)
				LEFT JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
				LEFT JOIN pg_motivo motivo ON (cxc_nd.id_motivo = motivo.id_motivo);",
				valTpDato($row['id_documento'], "int"),
				valTpDato($row['id_documento'], "int"));
		} else if ($row['id_tipo_movimiento'] == 4) { // 4 = Salida
			if ($row['tipo_documento_movimiento'] == 1) {
				$queryDetalle = sprintf("SELECT
					vw_iv_modelo.nom_uni_bas,
					CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
					1 AS cantidad,
					subtotal_factura AS costo_compra,
					subtotal_factura AS precio_unitario,
					NULL AS id_tipo_accesorio
				FROM an_vale_salida vale_sal
					INNER JOIN an_unidad_fisica uni_fis ON (vale_sal.id_unidad_fisica = uni_fis.id_unidad_fisica)
					INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
				WHERE vale_sal.id_vale_salida = %s;",
					valTpDato($row['id_documento'], "int"));
			} else if ($row['tipo_documento_movimiento'] == 2) {
				$queryDetalle = sprintf("SELECT
					vw_iv_modelo.nom_uni_bas,
					CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
					1 AS cantidad,
					cxp_fact_det_unidad.costo_unitario,
					cxp_fact_det_unidad.costo_unitario,
					NULL AS id_tipo_accesorio
				FROM cp_factura_detalle_unidad cxp_fact_det_unidad
					INNER JOIN vw_iv_modelos vw_iv_modelo ON (cxp_fact_det_unidad.id_unidad_basica = vw_iv_modelo.id_uni_bas)
					INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det_unidad.id_factura = cxp_nc.id_documento)
				WHERE cxp_nc.id_notacredito = %s
					AND cxp_nc.tipo_documento LIKE 'FA'
				
				UNION
				
				SELECT 
					acc.nom_accesorio,
					acc.des_accesorio,
					cxp_fact_det_acc.cantidad,
					cxp_fact_det_acc.costo_unitario,
					cxp_fact_det_acc.costo_unitario,
					NULL AS id_tipo_accesorio
				FROM cp_factura_detalle_accesorio cxp_fact_det_acc
					INNER JOIN an_accesorio acc ON (cxp_fact_det_acc.id_accesorio = acc.id_accesorio)
					INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det_acc.id_factura = cxp_nc.id_documento)
				WHERE cxp_nc.id_notacredito = %s
					AND cxp_nc.tipo_documento LIKE 'FA';",
					valTpDato($row['id_documento'], "int"),
					valTpDato($row['id_documento'], "int"));
			}
		}
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsDetalle = mysql_num_rows($rsDetalle);
		
		if ($totalRowsDetalle > 0) {
			$htmlTb .= "<tr align=\"left\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" title=\"Id Movimiento: ".$row['id_movimiento']."\">Nro. Dcto:</td>";
				$htmlTb .= "<td colspan=\"2\">";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
						$htmlTb .= "<td>".$imgModuloDcto."</td>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numero_documento'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Nro. Control / Folio:</td>
							<td align=\"right\" colspan=\"2\">".$row['numero_control_documento']."</td>
							<td align=\"right\" class=\"tituloCampo\">Fecha Dcto.:</td>
							<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_documento']))."</td>
							<td align=\"right\" class=\"tituloCampo\">Fecha Registro / Captura:</td>
							<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_registro']))."</td>";
				$htmlTb .= "<td align=\"right\" colspan=\"3\">";
					if ($row['id_tipo_movimiento'] == 3) {
						if ($row['estatus_documento'] == 2) {
							$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo6\" width=\"100%\">";
							$htmlTb .= "<tr align=\"center\">";
								$htmlTb .= "<td height=\"25\" width=\"25\"><img src=\"../img/iconos/lock.png\"/></td>";
								$htmlTb .= "<td>Venta Cerrada</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						} else {
							$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aCerrarVenta\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCierreVenta', '%s');\">
								<button type=\"button\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img class=\"puntero\" src=\"../img/iconos/lock_go.png\" title=\"Cerrar Venta\"/></td><td>&nbsp;</td><td>Cerrar Venta</td></tr></table></button>
							</a>",
								$row['id_documento']);
						}
					}
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr align=\"left\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Prov./Clnte./Emp.:</td>
							<td align=\"right\">".$row['ci_cliente']."</td>
							<td colspan=\"2\">".utf8_encode($row['nombre_cliente'])."</td>
							<td align=\"center\" ".$class." colspan=\"2\">".$row['estado_documento']."</td>
							<td align=\"right\" class=\"tituloCampo\">Nro. Orden:</td>
							<td align=\"right\">".$row['numero_pedido']."</td>
							<td align=\"right\" class=\"tituloCampo\">Clave Mov.:</td>
							<td colspan=\"4\">".utf8_encode($row['clave'].") ".$row['descripcion'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"4%\"></td>
							<td width=\"10%\">Código</td>
							<td width=\"20%\">Descripción</td>
							<td width=\"6%\">Cantidad</td>
							<td width=\"6%\">".$spanPrecioUnitario."</td>
							<td width=\"6%\">Costo Unit.</td>
							<td width=\"8%\">Importe Precio</td>
							<td width=\"8%\">Dscto.</td>
							<td width=\"8%\">Neto</td>
							<td width=\"8%\">Importe Costo</td>
							<td width=\"8%\">Utl.</td>
							<td width=\"4%\">%Utl.</td>
							<td width=\"4%\">%Dscto.</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotal = NULL;
		$contFila2 = 0;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)){
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			$clase .= (in_array($rowDetalle['id_tipo_accesorio'],array(3))) ? " textoGrisOscuro" : "";		
			
			$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio_unitario'];
			$descuento = $rowDetalle['porcentaje_descuento'] * $importePrecio / 100;
			$neto = $importePrecio - $descuento;
			
			$importeCosto = ($row['id_tipo_movimiento'] == 1) ? $neto : $rowDetalle['cantidad'] * $rowDetalle['costo_compra'];
			
			$porcUtilidad = 0;
			if ($importePrecio > 0) {
				$utilidad = $neto - $importeCosto;
				$porcUtilidad = $utilidad * 100 / $importePrecio;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".($contFila2)."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($rowDetalle['nom_uni_bas'])."</td>";
				$htmlTb .= "<td align=\"left\">";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($rowDetalle['vehiculo'])."</td>";
						$htmlTb .= "<td>".utf8_encode($rowDetalle['descripcion_tipo_accesorio'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= (strlen($rowDetalle['serial_carroceria']) > 0) ? "<tr>"."<td colspan=\"2\">".utf8_encode($rowDetalle['serial_carroceria'])."</td>"."</tr>" : "";
					$htmlTb .= (strlen($rowDetalle['condicion_unidad']) > 0) ? "<tr class=\"textoNegrita_10px\">"."<td colspan=\"2\">".utf8_encode($rowDetalle['condicion_unidad'])."</td>"."</tr>" : "";
					if (strlen($rowDetalle['numeroNotaCargo']) > 0) {
						switch($rowDetalle['id_modulo']) {
							case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
							case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
							case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
							case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
							case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
							default : $imgDctoModulo = $rowDetalle['id_modulo'];
						}
						
						switch($rowDetalle['estadoNotaCargo']) {
							case "" : $class = ""; break;
							case 0 : $class = "class=\"divMsjError\""; break;
							case 1 : $class = "class=\"divMsjInfo\""; break;
							case 2 : $class = "class=\"divMsjAlerta\""; break;
							case 3 : $class = "class=\"divMsjInfo3\""; break;
							case 4 : $class = "class=\"divMsjInfo4\""; break;
						}
						
						$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_debito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".("Ver Nota de Débito")."\"/><a>",
							$rowDetalle['idNotaCargo']);
						$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Nota de Débito PDF")."\"/><a>",
							$rowDetalle['idNotaCargo']);
						
						$htmlTb .= "<tr>";
							$htmlTb .= "<td colspan=\"2\"><fieldset>";
								$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
									$htmlTb .= "<tr align=\"right\">";
										$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
										$htmlTb .= "<td>".$imgDctoModulo."</td>";
										$htmlTb .= "<td width=\"100%\">".utf8_encode($rowDetalle['numeroNotaCargo'])."</td>";
									$htmlTb .= "</tr>";
								$htmlTb .= "<tr><td align=\"center\" ".$class." colspan=\"3\">".$rowDetalle['descripcion_estado_nota_cargo']."</td></tr>";
								$htmlTb .= (strlen($rowDetalle['nombre_cliente']) > 0) ? "<tr><td colspan=\"3\">".utf8_encode($rowDetalle['nombre_cliente'])."</td></tr>" : "";
								$htmlTb .= ($rowDetalle['id_motivo'] > 0) ? "<tr><td colspan=\"3\"><span class=\"textoNegrita_9px\">".utf8_encode($rowDetalle['id_motivo'].".- ".$rowDetalle['descripcion_motivo'])."</span></td></tr>" : "";
								$htmlTb .= "</table>";
							$htmlTb .= "</fieldset></td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".number_format($rowDetalle['cantidad'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($rowDetalle['precio_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($rowDetalle['costo_compra'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($importePrecio, 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($descuento, 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($neto, 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($importeCosto, 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($utilidad, 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($porcUtilidad, 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($rowDetalle['porcentaje_descuento'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotal[3] += $rowDetalle['cantidad'];
			$arrayTotal[6] += $importePrecio;
			$arrayTotal[7] += $descuento;
			$arrayTotal[8] += $neto;
			$arrayTotal[9] += $importeCosto;
			$arrayTotal[10] += $utilidad;
		}
		
		if ($totalRowsDetalle > 0) {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"3\">Total Dcto. ".$row['numero_documento'].":</td>
							<td>".number_format($arrayTotal[3], 2, ".", ",")."</td>
							<td>"."</td>
							<td>"."</td>
							<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>
							<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>
							<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>
							<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>
							<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>
							<td>".number_format((($arrayTotal[10] > 0) ? ($arrayTotal[10] * 100) / $arrayTotal[6] : 0), 2, ".", ",")."</td>
							<td>".number_format((($arrayTotal[6] > 0) ? ($arrayTotal[7] * 100) / $arrayTotal[6] : 0), 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			
			if ($contFila < $maxRows && (($maxRows * $pageNum) + $contFila) < $totalRows)
				$htmlTb .= "<tr><td colspan=\"12\">&nbsp;</td></tr>";
		}
	}
		
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMovimiento(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimiento(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMovimiento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"exportarMovimiento");
$xajax->register(XAJAX_FUNCTION,"formCierreVenta");
$xajax->register(XAJAX_FUNCTION,"guardarCierreVenta");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaMovimiento");

function guardarNotaCargoCxC($frmAjusteInventario) {
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
	
	$script = sprintf("verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s',960,550)", $idNotaCargo);
	
	return array(true, $idNotaCargo, $idModulo, $script);
}

function insertarItemUnidad($contFila, $hddIdFacturaDet, $idUnidadFisica, $hddTpItmAccPed){
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($hddIdFacturaDet > 0) {
		$queryFacturaDet = sprintf("SELECT cxc_fact_det_vehic.*,
			uni_fis.id_uni_bas,
			uni_bas.nom_uni_bas,
			CONCAT(uni_bas.nom_uni_bas, ': ', marca.nom_marca, ' ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
			cxc_fact.numeroPedido,
			
			(IFNULL(uni_fis.precio_compra, 0)
				+ IFNULL(costo_agregado, 0)
				- IFNULL(costo_depreciado, 0)
				- IFNULL(costo_trade_in, 0)) AS costo_final_unidad
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
			INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		WHERE cxc_fact_det_vehic.id_factura_detalle_vehiculo = %s",
			valTpDato($hddIdFacturaDet, "int"));
		$rsFacturaDet = mysql_query($queryFacturaDet);
		if (!$rsFacturaDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$totalRowsFacturaDet = mysql_num_rows($rsFacturaDet);
		$rowFacturaDet = mysql_fetch_assoc($rsFacturaDet);
		
		$hddIdFacturaDet = $rowFacturaDet['id_factura_detalle_vehiculo'];
		$hddIdUnidadBasicaItm = $rowFacturaDet['id_uni_bas'];
		$hddIdUnidadFisicaItm = $rowFacturaDet['id_unidad_fisica'];
		//$hddTipoAccesorioItm = $rowFacturaDet['id_tipo_accesorio'];
		$divCodigoItm = utf8_encode($rowFacturaDet['nom_uni_bas']);
		$divDescripcionItm = utf8_encode($rowFacturaDet['vehiculo']);
		//$divDescripcionItm .= ($rowFacturaDet['id_condicion_pago'] == 1) ? " <span class=\"textoVerdeNegrita\">[ Pagado ]</span>": "";
		$txtPrecioItm = $rowFacturaDet['precio_unitario'];
		$txtCostoItm = $rowFacturaDet['costo_final_unidad'];
		
	} else if ($idUnidadFisica > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT
			uni_bas.id_uni_bas,
			uni_bas.nom_uni_bas,
			marca.nom_marca,
			modelo.nom_modelo,
			vers.nom_version,
			ano.nom_ano,
			uni_fis.id_unidad_fisica,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			color_ext1.nom_color AS color_externo,
			color_int1.nom_color AS color_interno,
			uni_fis.marca_cilindro,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro,
			uni_fis.marca_kit,
			uni_fis.modelo_regulador,
			uni_fis.serial_regulador,
			uni_fis.codigo_unico_conversion,
			uni_fis.serial1,
			ped_vent.precio_venta,
			ped_vent.monto_descuento,
			ped_vent.porcentaje_iva,
			uni_fis.precio_compra,
			uni_fis.costo_depreciado
		FROM an_pedido ped_vent
			INNER JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
			INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
		WHERE ped_vent.id_unidad_fisica = %s
			AND ped_vent.estado_pedido IN (1);", 
			valTpDato($idUnidadFisica, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdUnidadBasicaItm = $rowPedidoDet['id_uni_bas'];
		$hddIdUnidadFisicaItm = $rowPedidoDet['id_unidad_fisica'];
		$divCodigoItm = $rowPedidoDet['nom_uni_bas'];
		$txtPrecioItm = $rowPedidoDet['precio_venta'];
		$hddMontoDescuentoItm = $rowPedidoDet['monto_descuento'];
		$hddTotalDescuentoItm = $rowPedidoDet['monto_descuento'];
		$txtCostoItm = $rowPedidoDet['precio_compra'] - $rowPedidoDet['costo_depreciado'];
		$porcIva = $rowPedidoDet['porcentaje_iva'];
		
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT uni_bas_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
			INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND tipo IN (2,6)
			AND (%s IS NOT NULL AND %s > 0);", 
			valTpDato($hddIdUnidadBasicaItm, "int"),
			valTpDato($porcIva, "real_inglesa"),
			valTpDato($porcIva, "real_inglesa"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$contIva = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$contIva++;
			
			$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
			"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
				$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
				$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
				$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
				$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
				$contFila.":".$contIva);
		}
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieAdicionalOtro').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"%s\" height=\"24\">".
			"<td title=\"trItm:%s\"><input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><div id=\"divCodigoItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtDescItm%s\" name=\"txtDescItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddMontoDescuentoItm%s\" name=\"hddMontoDescuentoItm%s\" value=\"%s\"/></td>".
			"<td>".
				"<input type=\"text\" id=\"txtCostoItm%s\" name=\"txtCostoItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td><div id=\"divIvaItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaDet%s\" name=\"hddIdFacturaDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdUnidadBasicaItm%s\" name=\"hddIdUnidadBasicaItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTotalDescuentoItm%s\" name=\"hddTotalDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoAccesorioItm%s\" name=\"hddTipoAccesorioItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('txtCostoItm%s').onblur = function() {
			setFormatoRafk(this,2);
		}",
		$contFila, $clase,
			$contFila, $contFila,
			$contFila, $contFila, 
			$contFila, $divCodigoItm, 
			$contFila, $contFila, $divDescripcionItm, $htmlContrato,
			$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, number_format($hddMontoDescuentoItm, 2, ".", ","), 
			$contFila, $contFila, number_format($txtCostoItm, 2, ".", ","), 
			$contFila, $ivaUnidad, 
			$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, $hddIdFacturaDet, 
				$contFila, $contFila, $hddIdUnidadBasicaItm, 
				$contFila, $contFila, $hddIdUnidadFisicaItm, 
				$contFila, $contFila, $hddTpItmAccPed, 
				$contFila, $contFila, number_format($hddTotalDescuentoItm, 2, ".", ","), 
				$contFila, $contFila, $hddTipoAccesorioItm,
		
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemAdicional($contFila, $hddIdFacturaDet, $hddIdItmAccPed, $hddTpItmAccPed, $hddIdAccesorioItm){
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($hddIdFacturaDet > 0) {
		$queryFacturaDet = sprintf("SELECT cxc_fact_det_acc.*,
			acc.nom_accesorio,
			cxc_fact.numeroPedido
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (cxc_fact.idFactura = cxc_fact_det_acc.id_factura)
			INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
		WHERE cxc_fact_det_acc.id_factura_detalle_accesorios = %s",
			valTpDato($hddIdFacturaDet, "int"));
		$rsFacturaDet = mysql_query($queryFacturaDet);
		if (!$rsFacturaDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$totalRowsFacturaDet = mysql_num_rows($rsFacturaDet);
		$rowFacturaDet = mysql_fetch_assoc($rsFacturaDet);
		
		$hddIdFacturaDet = $rowFacturaDet['id_factura_detalle_accesorios'];
		$hddIdAccesorioItm = $rowFacturaDet['id_accesorio'];
		$hddTipoAccesorioItm = $rowFacturaDet['id_tipo_accesorio'];
		$divCodigoItm = "";
		$divDescripcionItm = utf8_encode($rowFacturaDet['nom_accesorio']);
		$divDescripcionItm .= ($rowFacturaDet['id_condicion_pago'] == 1) ? " <span class=\"textoVerdeNegrita\">[ Pagado ]</span>": "";
		$txtPrecioItm = $rowFacturaDet['precio_unitario'];
		$txtCostoItm = $rowFacturaDet['costo_compra'];
		
		$hddTpItmAccPed = $rowFacturaDet['tipo_accesorio'];
		if ($hddTpItmAccPed == 1) { // 1 = Por Paquete, 2 = Individual
			$queryPedidoDet = sprintf("SELECT
				paq_ped.id_paquete_pedido,
				cliente.id AS id_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo
			FROM an_paquete_pedido paq_ped
				INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.Id_acc_paq)
				INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
				LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
				LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
			WHERE paq_ped.id_pedido = %s
				AND acc_paq.id_accesorio = %s",
				valTpDato($rowFacturaDet['numeroPedido'], "int"),
				valTpDato($rowFacturaDet['id_accesorio'], "int"));
		} else if ($hddTpItmAccPed == 2) { // 1 = Por Paquete, 2 = Individual
			$queryPedidoDet = sprintf("SELECT
				acc_ped.id_accesorio_pedido,
				cliente.id AS id_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo
			FROM an_accesorio_pedido acc_ped
				INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
				LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
				LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
			WHERE acc_ped.id_pedido = %s
				AND acc_ped.id_accesorio = %s",
				valTpDato($rowFacturaDet['numeroPedido'], "int"),
				valTpDato($rowFacturaDet['id_accesorio'], "int"));
		}
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		// id_unidad_fisica, id_accesorio_pedido, id_paquete_pedido
		$hddIdItmAccPed = ($hddTpItmAccPed == 1) ? $rowPedidoDet['id_paquete_pedido'] : $rowPedidoDet['id_accesorio_pedido'];
		
		$htmlContrato = "";
		if ($hddTipoAccesorioItm == 3) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato
			$htmlContrato = "<table border=\"0\" width=\"100%\">".
			"<tr>".
            	"<td align=\"right\" class=\"tituloCampo\" width=\"15%\"><span class=\"textoRojoNegrita\">*</span>Cliente:</td>".
				"<td width=\"85%\">".
					"<table cellpadding=\"0\" cellspacing=\"0\">".
					"<tr>";
						$htmlContrato .= sprintf(
						"<td><input type=\"text\" id=\"txtIdClienteItm%s\" name=\"txtIdClienteItm%s\" class=\"inputHabilitado\" onblur=\"xajax_asignarCliente(\'ClienteItm%s\', this.value, \'\', \'\', \'\', \'true\', \'false\');\" size=\"6\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td><a class=\"modalImg\" id=\"aListarClienteItm\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, \'tblLista\', \'Cliente\', \'ClienteItm%s\');\">"."<button type=\"button\" title=\"Listar\"><img src=\"../img/iconos/help.png\"/></button>"."</a></td>".
						"<td><input type=\"text\" id=\"txtNombreClienteItm%s\" name=\"txtNombreClienteItm%s\" readonly=\"readonly\" size=\"40\" value=\"%s\"/></td>",
							$contFila, $contFila, $contFila, $rowPedidoDet['id_cliente'],
							$contFila,
							$contFila, $contFila, $rowPedidoDet['nombre_cliente']);
					$htmlContrato .= "</tr>".
					"</table>".
				"</td>".
			"</tr>".
			"<tr>".
            	"<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Motivo:</td>".
				"<td>".
					"<table cellpadding=\"0\" cellspacing=\"0\">".
					"<tr>";
						$htmlContrato .= sprintf(
						"<td><input type=\"text\" id=\"txtIdMotivoItm%s\" name=\"txtIdMotivoItm%s\" class=\"inputHabilitado\" onblur=\"xajax_asignarMotivo(this.value, \'MotivoItm%s\', \'CC\', \'I\', \'false\');\" size=\"6\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td><a class=\"modalImg\" id=\"aListarMotivoItm\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, \'tblListaMotivo\', \'MotivoItm%s\', \'CC\', \'I\');\">"."<button type=\"button\" title=\"Listar\"><img src=\"../img/iconos/help.png\"/></button>"."</a></td>".
						"<td><input type=\"text\" id=\"txtMotivoItm%s\" name=\"txtMotivoItm%s\" readonly=\"readonly\" size=\"40\" value=\"%s\"/></td>",
							$contFila, $contFila, $contFila, $rowPedidoDet['id_motivo'],
							$contFila,
							$contFila, $contFila, $rowPedidoDet['descripcion_motivo']);
					$htmlContrato .= "</tr>".
					"</table>";
					
					$htmlContrato .= sprintf(
					"<input type=\"hidden\" id=\"hddIdTipoComisionItm%s\" name=\"hddIdTipoComisionItm%s\" value=\"%s\">".
					"<input type=\"hidden\" id=\"hddPorcentajeComisionItm%s\" name=\"hddPorcentajeComisionItm%s\" value=\"%s\">".
					"<input type=\"hidden\" id=\"hddMontoComisionItm%s\" name=\"hddMontoComisionItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['id_tipo_comision'],
						$contFila, $contFila, $rowPedidoDet['porcentaje_comision'],
						$contFila, $contFila, $rowPedidoDet['monto_comision']);
				$htmlContrato .= "</td>".
			"</tr>".
			"</table>";
		}
	} else if ($hddIdItmAccPed > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		if ($hddTpItmAccPed == 1) { // 1 = Por Paquete, 2 = Individual
			$queryPedidoDet = sprintf("SELECT 
				paq_ped.id_paquete_pedido,
				acc.id_accesorio,
				(CASE paq_ped.iva_accesorio
					WHEN 1 THEN
						acc.nom_accesorio
					ELSE
						CONCAT(acc.nom_accesorio, ' (E)')
				END) AS nom_accesorio,
				paq_ped.id_tipo_accesorio,
				(CASE paq_ped.id_tipo_accesorio
					WHEN 1 THEN	'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
				END) AS descripcion_tipo_accesorio,
				paq_ped.precio_accesorio,
				paq_ped.costo_accesorio,
				paq_ped.iva_accesorio,
				paq_ped.porcentaje_iva_accesorio,
				paq_ped.id_condicion_pago,
				paq_ped.estatus_paquete_pedido,
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo,
				cliente.id AS id_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				acc.id_tipo_comision,
				acc.porcentaje_comision,
				acc.monto_comision
			FROM an_acc_paq acc_paq
				INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
				LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
				LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
				INNER JOIN an_paquete_pedido paq_ped ON (acc_paq.Id_acc_paq = paq_ped.id_acc_paq)
			WHERE paq_ped.id_paquete_pedido = %s;", 
				valTpDato($hddIdItmAccPed, "int"));
		} else if ($hddTpItmAccPed == 2) { // 1 = Por Paquete, 2 = Individual
			$queryPedidoDet = sprintf("SELECT 
				acc_ped.id_accesorio_pedido,
				acc.id_accesorio,
				(CASE acc_ped.iva_accesorio
					WHEN 1 THEN
						acc.nom_accesorio
					ELSE
						CONCAT(acc.nom_accesorio, ' (E)')
				END) AS nom_accesorio,
				acc_ped.id_tipo_accesorio,
				(CASE acc_ped.id_tipo_accesorio
					WHEN 1 THEN	'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
				END) AS descripcion_tipo_accesorio,
				acc_ped.precio_accesorio,
				acc_ped.costo_accesorio,
				acc_ped.iva_accesorio,
				acc_ped.porcentaje_iva_accesorio,
				acc_ped.id_condicion_pago,
				acc_ped.estatus_accesorio_pedido,
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo,
				cliente.id AS id_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				acc.id_tipo_comision,
				acc.porcentaje_comision,
				acc.monto_comision
			FROM an_accesorio acc
				LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
				LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
				INNER JOIN an_accesorio_pedido acc_ped ON (acc.id_accesorio = acc_ped.id_accesorio)
			WHERE acc_ped.id_accesorio_pedido = %s", 
				valTpDato($hddIdItmAccPed, "int"));
		}
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdAccesorioItm = $rowPedidoDet['id_accesorio'];
		$hddTipoAccesorioItm = $rowPedidoDet['id_tipo_accesorio'];
		$divCodigoItm = "";
		$divDescripcionItm = utf8_encode($rowPedidoDet['nom_accesorio']);
		$divDescripcionItm .= ($rowPedidoDet['id_condicion_pago'] == 1) ? " <span class=\"textoVerdeNegrita\">[ Pagado ]</span>": "";
		$txtPrecioItm = $rowPedidoDet['precio_accesorio'];
		$txtCostoItm = $rowPedidoDet['costo_accesorio'];
		
		$htmlContrato = "";
		if ($hddTipoAccesorioItm == 3) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato
			$htmlContrato = "<table width=\"100%\">";
				$htmlContrato .= (strlen($rowPedidoDet['nombre_cliente']) > 0) ? "<tr><td><span class=\"textoNegrita_10px\">".utf8_encode($rowPedidoDet['nombre_cliente'])." ".(($rowPedidoDet['id_motivo'] > 0) ? "(Comisión: ".(($rowPedidoDet['id_tipo_comision'] == 1) ? number_format($rowPedidoDet['porcentaje_comision'], 2, ".", ",")."%" : number_format($rowPedidoDet['monto_comision'], 2, ".", ",")).")" : "")."</span></td></tr>" : "";
				$htmlContrato .= ($rowPedidoDet['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$rowPedidoDet['id_motivo'].".- ".utf8_encode($rowPedidoDet['descripcion_motivo'])."</span></td></tr>" : "";
			$htmlContrato .= "<tr>";
				$htmlContrato .= "<td>";
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"txtIdClienteItm%s\" name=\"txtIdClienteItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['id_cliente']);
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"txtIdMotivoItm%s\" name=\"txtIdMotivoItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['id_motivo']);
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddIdTipoComisionItm%s\" name=\"hddIdTipoComisionItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['id_tipo_comision']);
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddPorcentajeComisionItm%s\" name=\"hddPorcentajeComisionItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['porcentaje_comision']);
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddMontoComisionItm%s\" name=\"hddMontoComisionItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['monto_comision']);
				$htmlContrato .= "</td>";
			$htmlContrato .= "</tr>";
			$htmlContrato .= "</table>";
		}
		
		if ($rowPedidoDet['iva_accesorio'] == 1) {
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE tipo = 6 AND estado = 1 AND activo = 1 ORDER BY iva;");
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
			$contIva = 0;
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
				"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
					$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
					$contFila.":".$contIva);
			}
		}
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieAdicionalOtro').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"%s\" height=\"24\">".
			"<td title=\"trItm:%s\"><input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><div id=\"divCodigoItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtDescItm%s\" name=\"txtDescItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddMontoDescuentoItm%s\" name=\"hddMontoDescuentoItm%s\" value=\"%s\"/></td>".
			"<td>".
				"<input type=\"text\" id=\"txtCostoItm%s\" name=\"txtCostoItm%s\" class=\"inputCompletoHabilitado\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td><div id=\"divIvaItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaDet%s\" name=\"hddIdFacturaDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAccesorioItm%s\" name=\"hddIdAccesorioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTotalDescuentoItm%s\" name=\"hddTotalDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoAccesorioItm%s\" name=\"hddTipoAccesorioItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('txtCostoItm%s').onblur = function() {
			setFormatoRafk(this,2);
		}",
		$contFila, $clase,
			$contFila, $contFila,
			$contFila, $contFila, 
			$contFila, $divCodigoItm, 
			$contFila, $contFila, $divDescripcionItm, $htmlContrato,
			$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, number_format($hddMontoDescuentoItm, 2, ".", ","), 
			$contFila, $contFila, number_format($txtCostoItm, 2, ".", ","), 
			$contFila, $ivaUnidad, 
			$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, $hddIdFacturaDet, 
				$contFila, $contFila, $hddIdAccesorioItm, 
				$contFila, $contFila, $hddIdItmAccPed, 
				$contFila, $contFila, $hddTpItmAccPed, 
				$contFila, $contFila, number_format($hddTotalDescuentoItm, 2, ".", ","), 
				$contFila, $contFila, $hddTipoAccesorioItm,
		
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>