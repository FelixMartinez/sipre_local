<?php
function asignarCliente($idCliente){
	$objResponse = new xajaxResponse();
	
	$queryProveedor = sprintf("SELECT id,
		CONCAT_WS(' ',nombre,apellido) AS nombre_cliente,
		CONCAT_WS('-',lci,ci) AS cedula_cliente,
		direccion,
		telf
	FROM cj_cc_cliente
	WHERE id = %s",
		valTpDato($idCliente, "int"));					
	$rsProveedor = mysql_query($queryProveedor);
	if (!$rsProveedor) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	$rowProveedor = mysql_fetch_assoc($rsProveedor);
	
	$objResponse->assign("txtIdCliente","value",$rowProveedor['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowProveedor['nombre_cliente']));
	$objResponse->assign("txtRifCliente","value",$rowProveedor['cedula_cliente']);
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowProveedor['direccion']));
	$objResponse->assign("txtTelefonosCliente","value",$rowProveedor['telf']);
	if ($rowProveedor['nit'] > 0) {
		$objResponse->assign("txtNITCliente","value",$rowProveedor['nit']);
	} else {
		$objResponse->assign("txtNITCliente","value","N/A");
	}
	$objResponse->script("$('divFlotante').style.display='none';");
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarCliente['txtPalabra']);
		
	$objResponse->loadCommands(listadoClientes(0, "", "", $valBusq));
	
	return $objResponse;
}

function cargarAnticipo($idAnticipo,$acc){
	$objResponse = new xajaxResponse();
	
	if($acc == 0)
	
	$objResponse->script("$('btnGuardar').style.display = 'none'");
	
	$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo WHERE idAnticipo = %s",
		$idAnticipo);
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAnticipo = mysql_fetch_array($rsAnticipo);
	
	$objResponse->loadCommands(asignarCliente($rowAnticipo['idCliente']));
	$objResponse->loadCommands(asignarEmpresaUsuario($rowAnticipo['id_empresa'], "Empresa", "ListaEmpresa"));	
	$objResponse->assign("hddIdAnticipo","value",$idAnticipo);
	$objResponse->assign("txtNumeroAnticipo","value",$rowAnticipo['numeroAnticipo']);
	$objResponse->assign("txtFecha","value",date("d-m-Y",strtotime($rowAnticipo['fechaAnticipo'])));
	$objResponse->loadCommands(cargarDepartamento($rowAnticipo['idDepartamento']));
	$objResponse->assign("txtObservacionAnticipo","value",$rowAnticipo['observacionesAnticipo']);
	$objResponse->assign("txtMontoAnticipo","value",number_format($rowAnticipo['montoNetoAnticipo'],2,".",","));
	$objResponse->assign("txtSaldoAnticipo","value",number_format($rowAnticipo['saldoAnticipo'],2,".",","));
	
	$objResponse->loadCommands(desglosePagos(0, "", "", $rowAnticipo['idAnticipo']));
	$objResponse->loadCommands(documentosPagos(0, "", "", $rowAnticipo['idAnticipo']));
	
	if ($rowAnticipo['estatus'] == 0){ // ANULADO
		
		$objResponse->script("$('fieldAnulado').style.display = ''");
		//$objResponse->script("$('tdEstatus').style.display = ''");
		//$objResponse->script("$('tdEmpleadoAnulacion').style.display = ''");
		
		$queryEmpleado = sprintf("SELECT nombre_empleado, apellido FROM pg_empleado WHERE id_empleado = %s",
			$rowAnticipo['id_empleado_anulado']);
		$rsEmpleado = mysql_query($queryEmpleado);
		if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowEmpleado = mysql_fetch_array($rsEmpleado);
		
		$empleadoAnulacion = $rowEmpleado['nombre_empleado'].' '.$rowEmpleado['apellido'];
		$motivoAnulacion = $rowAnticipo['motivo_anulacion'];
		$estatus = 'ANULADO';
		
		$objResponse->assign("txtEstatus","value",$estatus);
		$objResponse->assign("txtEmpleadoAnulacion","value",$empleadoAnulacion);
		$objResponse->assign("txtMotivoAnulacion","value",$motivoAnulacion);
		
	} else {
	
		$objResponse->script("$('fieldAnulado').style.display = 'none'");
		//$objResponse->script("$('tdEstatus').style.display = 'none'");
		//$objResponse->script("$('tdEmpleadoAnulacion').style.display = 'none'");
	
	}
	
	return $objResponse;
}

function cargarDepartamento($idDepartamento){
	$objResponse = new xajaxResponse();
	
	$queryDepartamento = sprintf("SELECT descripcionModulo FROM pg_modulos WHERE id_enlace_concepto = %s",
		$idDepartamento);
	$rsDepartamento = mysql_query($queryDepartamento);
	if (!$rsDepartamento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDepartamento = mysql_fetch_array($rsDepartamento);
	
	$html .= "<input type='text' id='slctDepartamento' name='slctDepartamento' readonly='readonly' value='".$rowDepartamento['descripcionModulo']."'/>";
	
	$objResponse->assign("tdDeparmamento","innerHTML",$html);
	
	return $objResponse;
}

function desglosePagos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		cj_cc_detalleanticipo.idAnticipo,
		cj_cc_detalleanticipo.tipoPagoDetalleAnticipo,
		cj_cc_detalleanticipo.id_concepto,
		cj_cc_detalleanticipo.bancoClienteDetalleAnticipo,
		(SELECT nombreBanco FROM bancos WHERE idBanco = cj_cc_detalleanticipo.bancoCompaniaDetalleAnticipo) as bancoCompania,
		cj_cc_detalleanticipo.numeroCuentaCompania,
		cj_cc_detalleanticipo.numeroControlDetalleAnticipo,
		cj_cc_detalleanticipo.montoDetalleAnticipo,
		bancos.idBanco,
		bancos.nombreBanco
	FROM
		cj_cc_detalleanticipo
		INNER JOIN bancos ON (cj_cc_detalleanticipo.bancoClienteDetalleAnticipo = bancos.idBanco)
	WHERE
		idAnticipo = %s", $valBusq);
							 
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
		$htmlTh .= ordenarCampo("xajax_desglosePagos", "15", $pageNum, "tipoPagoDetalleAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_desglosePagos", "23%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco Cliente");
		$htmlTh .= ordenarCampo("xajax_desglosePagos", "23%", $pageNum, "bancoCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco Compañia");
		$htmlTh .= ordenarCampo("xajax_desglosePagos", "19%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Cuenta");
		$htmlTh .= ordenarCampo("xajax_desglosePagos", "10%", $pageNum, "numeroControlDetalleAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_desglosePagos", "10%", $pageNum, "montoDetalleAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
	$htmlTh .= "</tr>";
	
		$contFila = 0;
		while ($row = mysql_fetch_assoc($rsLimit)) {
			$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
			
			$contFila ++;
			
			if($row['tipoPagoDetalleAnticipo'] == 'EF') {
				$tipoPago = "Efectivo";
			} else if ($row['tipoPagoDetalleAnticipo'] == 'CH') {
				$tipoPago = "Cheque";
			} else if ($row['tipoPagoDetalleAnticipo'] == 'DP') {
				$tipoPago = "Deposito";
			} else if ($row['tipoPagoDetalleAnticipo'] == 'TB') {
				$tipoPago = "Transferencia Bancaria";
			} else if ($row['tipoPagoDetalleAnticipo'] == 'TC') {
				$tipoPago = "Tarjeta de Credito";
			} else if ($row['tipoPagoDetalleAnticipo'] == 'TD') {
				$tipoPago = "Tarjeta de Debito";
			} else if ($row['tipoPagoDetalleAnticipo'] == 'OT') {
				$queryConcepto = sprintf("SELECT * FROM cj_conceptos_formapago WHERE id_concepto = %s", valTpDato($row['id_concepto'], "int"));
				$rsConcepto = mysql_query($queryConcepto);
				if (!$rsConcepto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowConcepto = mysql_fetch_assoc($rsConcepto);
				$descripcionConcepto = $rowConcepto['descripcion'];
				$tipoPago = "Otro / ".utf8_encode($descripcionConcepto);
			}
			
			$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
				$htmlTb .= "<td align=\"center\">".$tipoPago."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombreBanco'])."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($row['bancoCompania'])."</td>";
				$htmlTb .= "<td align=\"center\">".$row['numeroCuentaCompania']."</td>";
				$htmlTb .= "<td align=\"right\">".$row['numeroControlDetalleAnticipo']."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($row['montoDetalleAnticipo'],2,'.',',')."</td>";
			$htmlTb .= "</tr>";
		}
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdDesglosePagos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function documentosPagos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		'Nota de Débito' AS tipoDocumento,
		nota_cargo.idDepartamentoOrigenNotaCargo AS idDepartamento,
		nota_cargo.numeroNotaCargo AS numeroDocumento,
		nota_cargo.numeroControlNotaCargo AS numeroControl,
		det_nota_cargo.idFormaPago AS idFormaPago,
		det_nota_cargo.fechaPago AS fechaPago,
		det_nota_cargo.monto_pago AS monto_pago,
		det_nota_cargo.idNotaCargo AS id_documento,
		'cc_nota_de_cargo.php?id=' AS ruta
	FROM cj_det_nota_cargo det_nota_cargo
		INNER JOIN cj_cc_notadecargo nota_cargo ON (det_nota_cargo.idNotaCargo = nota_cargo.idNotaCargo)
	WHERE det_nota_cargo.idFormaPago = 7
		AND numeroDocumento = %s
		 
	UNION
	
	SELECT 'Factura' AS tipoDocumento,
		fact_vent.idDepartamentoOrigenFactura AS idDepartamento,
		fact_vent.numeroFactura AS numeroDocumento,
		fact_vent.numeroControl AS numeroControl,
		formaPago AS idFormaPago,
		fechaPago AS fechaPago,
		montoPagado AS monto_pago,
		fact_vent.idFactura AS id_documento,
		'cc_factura.php?id=' AS ruta
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN sa_iv_pagos sa_iv_pago ON (fact_vent.numeroFactura = sa_iv_pago.numeroFactura)
	WHERE fact_vent.idDepartamentoOrigenFactura IN (0,1,3)
		AND fact_vent.montoTotalFactura > 0
		AND formaPago = 7
		AND numeroDocumento = %s
		
	UNION
	
	SELECT 'Factura' AS tipoDocumento,
		fact_vent.idDepartamentoOrigenFactura AS idDepartamento,
		fact_vent.numeroFactura AS numeroDocumento,
		fact_vent.numeroControl AS numeroControl,
		formaPago AS idFormaPago,
		fechaPago AS fechaPago,
		montoPagado AS monto_pago,
		fact_vent.idFactura AS id_documento,
		'cc_factura.php?id=' AS ruta
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN an_pagos an_pago ON (fact_vent.numeroFactura = an_pago.numeroFactura)
	WHERE fact_vent.idDepartamentoOrigenFactura IN (2,4)
		AND fact_vent.montoTotalFactura > 0
		AND formaPago = 7
		AND numeroDocumento = %s",
			valTpDato($valBusq,'int'),
			valTpDato($valBusq,'int'),
			valTpDato($valBusq,'int'));
							
	$sqlOrd = ($campOrd != "") ? sprintf("ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
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
			$htmlTh .= ordenarCampo("xajax_documentosPagos", "", $pageNum, "idDepartamento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
			$htmlTh .= ordenarCampo("xajax_documentosPagos", "20%", $pageNum, "fechaPago", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha de Pago");
			$htmlTh .= ordenarCampo("xajax_documentosPagos", "20%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
			$htmlTh .= ordenarCampo("xajax_documentosPagos", "20%", $pageNum, "numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
			$htmlTh .= ordenarCampo("xajax_documentosPagos", "20%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
			$htmlTh .= ordenarCampo("xajax_documentosPagos", "20%", $pageNum, "monto_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "</tr>";
		
		$contFila = 0;
		while ($row = mysql_fetch_assoc($rsLimit)) {
			$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
			$contFila ++;
			
			switch($row['idDepartamento']) {
				case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
				case 3 : $imgPedidoModulo = "Administración"; break;			
				default : $row['idDepartamento'];
			}
			
			$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
				$htmlTb .= "<td align=\"center\">".$imgPedidoModulo."</td>";
				$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaPago']))."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($row['numeroDocumento'])."<a id=\"aVerDocumento\" href=\"".$row['ruta'].$row['id_documento'].$rowPago['idNotaCredito']."&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Documento\"/><a></td>";
				$htmlTb .= "<td align=\"left\">".$row['numeroControl']."</a></td>";
				$htmlTb .= "<td align=\"center\">".$row['tipoDocumento']."</a></td>";
				$htmlTb .= "<td align=\"right\">".number_format($row['monto_pago'],2,'.',',')."</td>";
			$htmlTb .= "</tr>";
		}
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdDocumentosPagos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"cargarAnticipo");
$xajax->register(XAJAX_FUNCTION,"cargarDepartamento");
$xajax->register(XAJAX_FUNCTION,"desglosePagos");
$xajax->register(XAJAX_FUNCTION,"documentosPagos");

?>