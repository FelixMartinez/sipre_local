<?php

//ANULA TRANSFERENCIA DEVUELVE SALDO CAJA
function anularTransferencia($formAnulacion){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if(!xvalidaAcceso($objResponse,"cjrs_devolucion_transferencia_list","insertar")){ return $objResponse; }
	
	mysql_query("START TRANSACTION");

	$idTransferencia = $formAnulacion["hddIdTransferencia"];
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	//VALIDO APERTURA DE LAS CAJAS
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	//VERIFICO QUE TODOS LOS PAGOS REALIZADOS MEDIANTE LA TRANSFERENCIA ESTEN ANULADOS
	$query = sprintf("SELECT
			  (SELECT COUNT(*) FROM an_pagos WHERE id_transferencia = %s AND estatus = 1) as pagos_vehiculos,
			  (SELECT COUNT(*) FROM cj_cc_detalleanticipo WHERE id_transferencia = %s AND estatus = 1) as pagos_anticipos,
			  (SELECT COUNT(*) FROM cj_det_nota_cargo WHERE id_transferencia = %s AND estatus = 1) as pagos_nota_cargo,
			  (SELECT COUNT(*) FROM sa_iv_pagos WHERE id_transferencia = %s AND estatus = 1) as pagos_repuestos_servicios",
			  $idTransferencia,
			  $idTransferencia,
			  $idTransferencia,
			  $idTransferencia);
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	if($row["pagos_vehiculos"] > 0 || $row["pagos_anticipos"] > 0 ||$row["pagos_nota_cargo"] > 0 || $row["pagos_repuestos_servicios"] > 0){
		return $objResponse->alert("La transferencia tiene pagos activos, debe anularlos primero");
	}
	
	//BUSCO INFORMACION DE LA TRANSFERENCIA
	$query = sprintf("SELECT monto_neto_transferencia, id_departamento, estatus FROM cj_cc_transferencia WHERE id_transferencia = %s",
		$idTransferencia);
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
	$rowTransferencia = mysql_fetch_assoc($rs);	
	
	if($rowTransferencia["estatus"] != 1) { return $objResponse->alert("La transferencia ya ha sido anulado"); }
	
	$idModulo = $rowTransferencia["id_departamento"];
	$montoTransferencia = $rowTransferencia["monto_neto_transferencia"];
	
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

	//RESTO EL MONTO YA ENTRADO DE LA TRANSFERENCIA
	$query = sprintf("UPDATE ".$apertCajaPpal." SET
				saldoTransferencia = saldoTransferencia - %s,
				saldoCaja = saldoCaja - %s
		WHERE id = %s;",
				valTpDato($montoTransferencia, "real_inglesa"),
				valTpDato($montoTransferencia, "real_inglesa"),
				valTpDato($rowAperturaCaja['id'], "int"));
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
	
	//ACTUALIZO EL ESTADO DE LA TRANSFERENCIA A ANULADO
	$query = sprintf("UPDATE cj_cc_transferencia SET estatus = 0, id_empleado_anulado = %s, motivo_anulacion = %s WHERE id_transferencia = %s",//0 = anulado, 1 = activo
					  valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					  valTpDato($formAnulacion["txtMotivoAnulacion"], "text"),
					  $idTransferencia);
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
								
	mysql_query("COMMIT");
								
	$objResponse->alert("Transferencia anulada correctamente");
	$objResponse->script("byId('btnCancelarAnulacion').click();
						  byId('btnBuscar').click();");
	
	return $objResponse;
}

function buscarTransferencia($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		implode(",",$frmBuscar['lstEstadoTransferencia']),
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio'],
		$frmBuscar['lstEstatus']);
		
	$objResponse->loadCommands(listaTransferencia(0, "id_transferencia", "DESC", $valBusq));
	
	return $objResponse;
}

function cargarTransferencia($idTransferencia){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT numero_transferencia, id_departamento FROM cj_cc_transferencia WHERE id_transferencia = %s LIMIT 1",
					  $idTransferencia);
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
		
	$rowTransferencia = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNroTransferencia","value",$rowTransferencia["numero_transferencia"]);
	$objResponse->assign("hddIdTransferencia","value",$idTransferencia);	
		
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s)", valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_enlace_concepto'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_enlace_concepto']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargarPagina($idEmpresa){
	$objResponse = new xajaxResponse();
	
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
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	if ($rowConfig400['valor'] == 0) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, "onchange=\"selectedOption(this.id,'".$idEmpresa."');\""));
	} else {
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa));
	}
	
	$objResponse->script("xajax_buscarTransferencia(xajax.getFormValues('frmBuscar'));");
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	return $objResponse;
}

//ANULA TRANSFERENCIA CREA NOTA DE CARGO
function devolverTransferencia($idTransferencia){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	if(!xvalidaAcceso($objResponse,"cjrs_devolucion_transferencia_list","insertar")){ return $objResponse; }

	mysql_query("START TRANSACTION");

	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];

	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }

	//BUSCO INFORMACION DE LA TRANSFERENCIA
	$sqlTransferencia = sprintf("SELECT
		trans.numero_transferencia, 
		trans.monto_neto_transferencia, 
		trans.id_cliente, 
		trans.id_banco_cliente, 
		trans.estatus, 
		trans.id_banco_compania, 
		trans.id_cuenta_compania, 
		bancos.nombreBanco
	FROM cj_cc_transferencia trans
		INNER JOIN bancos ON trans.id_banco_compania = bancos.idBanco
	WHERE trans.id_transferencia = %s",
		$idTransferencia);
	$rsTransferencia = mysql_query($sqlTransferencia);
	if (!$rsTransferencia){ return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlTransferencia); }
	$rowTransferencia = mysql_fetch_assoc($rsTransferencia);
	
	if($rowTransferencia["estatus"] != 1) { return $objResponse->alert("La transferencia ya ha sido devuelta"); }
	
	$numeroTransferencia = $rowTransferencia["numero_transferencia"];
	$monto = $rowTransferencia["monto_neto_transferencia"];
	$idCliente = $rowTransferencia["id_cliente"];
	$idBanco = $rowTransferencia["id_banco_cliente"];
	
	$idBancoCompania = $rowTransferencia["id_banco_compania"];
	$idCuentaCompania = $rowTransferencia["id_cuenta_compania"];
	$nombreBancoCompania = $rowTransferencia["nombreBanco"];

	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = %s
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato(((in_array($idCajaPpal,array(1))) ? 13 : 23), "int"), // 13 = Nota Cargo Vehículos, 23 = Nota Cargo Repuestos y Servicios
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);

	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];

	if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado la numeracion para notas de cargo"); }

	$queryInsertNotaCargo = sprintf("INSERT INTO cj_cc_notadecargo (numeroControlNotaCargo, fechaRegistroNotaCargo, numeroNotaCargo, fechaVencimientoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, estadoNotaCargo, observacionNotaCargo, fletesNotaCargo, idCliente, idDepartamentoOrigenNotaCargo, descuentoNotaCargo, porcentajeIvaNotaCargo, calculoIvaNotaCargo, subtotalNotaCargo, interesesNotaCargo, tipoNotaCargo, ivaLujoNotaCargo, diasDeCreditoNotaCargo, montoExentoNotaCargo, montoExoneradoNotaCargo, baseImponibleNotaCargo, aplicaLibros, referencia_nota_cargo, idBanco, id_empresa, id_motivo)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 0, 0, 0, %s, 0, 0, 0, 0, 0, 0, %s, 0, 0, %s, %s, %s)",
		valTpDato($numeroTransferencia,'text'),
		valTpDato("NOW()",'campo'),
		valTpDato($numeroActual,'int'),
		valTpDato("NOW()",'campo'),
		valTpDato($monto,'double'),
		valTpDato($monto,'double'),
		valTpDato(0,'int'), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
		valTpDato('DEVOLUCION DE TRANSFERENCIA '.$numeroTransferencia,'text'),
		valTpDato(0,'double'),
		valTpDato($idCliente,'int'),
		valTpDato(0,'int'), // 0 = Repuestos, 1 = Servicios, 2 = Vehìculos, 3 = Administración
		valTpDato($monto,'double'),
		valTpDato($monto,'double'),
		valTpDato($idBanco,'int'),
		valTpDato($idEmpresa,'int'),
		valTpDato(364,'int')); // 364 = motivo cheque devuelto, usa el mismo para transferencias
	$rsInsertNotaCargo = mysql_query($queryInsertNotaCargo);
	if (!$rsInsertNotaCargo) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryInsertNotaCargo);
	$idNotaCargo = mysql_insert_id();

	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

	//INSERCCION cj_cc_estado_cuenta
	$sqlInsertEstadoCuenta = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN) VALUES ('ND', %s, NOW(), 2)",
									  $idNotaCargo);
	$rsInsertEstadoCuenta = mysql_query($sqlInsertEstadoCuenta);
	if (!$rsInsertEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuenta);
	//FIN INSERCCION cj_cc_estado_cuenta

	//CONSULTAR EL NUMERO DE FOLIO DE NOTA DEBITO EN TESORERIA
	$sqlNumeroFolio = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 1");
	$rsNumeroFolio = mysql_query($sqlNumeroFolio);
	if (!$rsNumeroFolio) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlNumeroFolio);
	$rowNumeroFolio = mysql_fetch_array($rsNumeroFolio);

	$updateNumeroFolio = "UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = 1";
	$rsUpdateNumeroFolio = mysql_query($updateNumeroFolio);
	if (!$rsUpdateNumeroFolio) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$updateNumeroFolio);

	$sqlInsertNotaDebitoTesoreria = sprintf("INSERT INTO te_nota_debito (id_numero_cuenta, fecha_registro, folio_tesoreria, id_beneficiario_proveedor, observaciones, fecha_aplicacion, fecha_movimiento_banco, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_debito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_debito, id_motivo)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 0, %s, 1, %s, 515)",
		valTpDato($idCuentaCompania,'int'),
		valTpDato("NOW()", "campo"),
		valTpDato($rowNumeroFolio['numero_actual'],'int'),
		valTpDato(0,'int'),
		valTpDato("TRANSFERENCIA DEVUELTA ".$nombreBancoCompania,'text'),
		valTpDato("NOW()", "campo"),
		valTpDato("NOW()", "campo"),
		valTpDato(0,'int'),
		valTpDato(2,'int'), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
		valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
		valTpDato($idUsuario,'int'),
		valTpDato($monto,'double'),
		valTpDato($idEmpresa,'int'),
		valTpDato($numeroTransferencia,'text'));
	$rsInsertNotaDebitoTesoreria = mysql_query($sqlInsertNotaDebitoTesoreria);
	if (!$rsInsertNotaDebitoTesoreria) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlInsertNotaDebitoTesoreria);
	$idNotaDebito = mysql_insert_id();
	
	$sqlInsertEstadoCuentaTesoreria = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
	VALUES('ND', %s, NOW(), %s, %s, %s, 0, %s, 1, %s, 2, 0)",
		valTpDato($idNotaDebito,'int'),
		valTpDato($idCuentaCompania,'int'),
		valTpDato($idEmpresa,'int'),
		valTpDato($monto,'double'),
		valTpDato($numeroTransferencia,'text'),
		valTpDato("TRANSFERENCIA DEVUELTO ".$nombreBancoCompania,'text'));
	$rsInsertEstadoCuentaTesoreria = mysql_query($sqlInsertEstadoCuentaTesoreria);
	if (!$rsInsertEstadoCuentaTesoreria) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuentaTesoreria);

	$sqlSaldoCuenta = sprintf("SELECT saldo_tem FROM cuentas WHERE idCuentas = %s",valTpDato($idCuentaCompania,'int'));
	$rsSaldoCuenta = mysql_query($sqlSaldoCuenta);
	if (!$rsSaldoCuenta) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlSaldoCuenta);
	$rowSaldoCuenta = mysql_fetch_array($rsSaldoCuenta);
	
	$sqlUpdateSaldoCuenta = sprintf("UPDATE cuentas SET saldo_tem = %s WHERE idCuentas = %s",
									valTpDato($rowSaldoCuenta['saldo_tem'] - $monto,'double'),
									valTpDato($idCuentaCompania,'int'));
	$rsUpdateSaldoCuenta = mysql_query($sqlUpdateSaldoCuenta);
	if (!$rsUpdateSaldoCuenta) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlUpdateSaldoCuenta);
	//FIN INSERCCION DE LA NOTA DE CARGO EN TESORERIA Y REBAJAR EL SALDO DE LA CUENTA
	
	//ACTUALIZO EL ESTADO DE LA TRANSFERENCIA A ANULADO
	$query = sprintf("UPDATE cj_cc_transferencia SET saldo_transferencia = 0, estatus = 0, id_empleado_anulado = %s, motivo_anulacion = %s WHERE id_transferencia = %s",//0 = anulado, 1 = activo
					  valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					  valTpDato('DEVOLUCION DE TRANSFERENCIA '.$numeroTransferencia, "text"),
					  $idTransferencia);
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
	
	$objResponse->alert("Transferencia devuelta, se genero nota de cargo");		
	$objResponse->script("byId('btnBuscar').click();");		
	$objResponse->script(sprintf("verVentana('reportes/cjrs_comprobante_devolucion_nota_cargo.php?valBusq=%s|%s',960,550)", $idEmpresa, $idNotaCargo));

	mysql_query("COMMIT");

	// MODIFICADO ERNESTO
	if (in_array($idCajaPpal,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		if (function_exists("generarNotasCargoVe")) { generarNotasCargoVe($idNotaCargo,"",""); }
	} else {
		if (function_exists("generarNotasCargoRe")) { generarNotasCargoRe($idNotaCargo,"",""); }
	}
	if (function_exists("generarNotaDebitoTe")) { generarNotaDebitoTe($idNotaDebito,"",""); }
	// MODIFICADO ERNESTO

	return $objResponse;
}

function imprimirDevolucionTransferencia($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		implode(",",$frmBuscar['lstEstadoTransferencia']),
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio'],
		$frmBuscar['lstEstatus']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjrs_devolucion_transferencia_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaTransferencia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tb.id_departamento IN (%s)",
		valTpDato($idModuloPpal, "campo"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(tb.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = tb.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tb.estado_transferencia IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tb.fecha_transferencia BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])),"date"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tb.id_departamento IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(tb.numero_transferencia LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tb.estatus = %s",
			valTpDato($valCadBusq[6], "int"));
	}
	
	$query = sprintf("SELECT
		tb.id_transferencia,
		tb.tipo_transferencia,
		tb.id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		tb.monto_neto_transferencia,
		IF (tb.estatus = 1, tb.saldo_transferencia, 0) AS saldo_transferencia,
		tb.fecha_transferencia,
		tb.numero_transferencia,
		tb.id_departamento,
		tb.estatus,
		tb.observacion_transferencia,
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
		END) AS descripcion_estado_transferencia,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, 
			CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), 
			vw_iv_emp_suc.nombre_empresa) AS nombre_empresa		
		
	FROM cj_cc_transferencia tb
		INNER JOIN cj_cc_cliente cliente ON (tb.id_cliente = cliente.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (tb.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryLimit); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "", $pageNum, "id_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "", $pageNum, "estatus", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "7%", $pageNum, "fecha_transferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Transferencia");		
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "7%", $pageNum, "tipo_transferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Transferencia");
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "8%", $pageNum, "numero_transferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Transferencia");
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "38%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "10%", $pageNum, "descripcion_estado_transferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado");
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "8%", $pageNum, "saldo_transferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listaTransferencia", "8%", $pageNum, "monto_neto_transferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_departamento']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgDctoModulo = $row['id_departamento'];
		}
		
		if ($row['estatus'] == 0){ // 0 = ANULADO ; 1 = ACTIVO
			$imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Transferencia Anulado\"/>";
		} else if ($row['estatus'] == 1){ // 0 = ANULADO ; 1 = ACTIVO
			$imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Transferencia Activo\"/>";
		}
		
		
		if($row["tipo_transferencia"] == 1){
			$tipoTransferencia = "Cliente";
		}elseif($row["tipo_transferencia"] == 2){
			$tipoTransferencia = "Bono Suplidor";
		}elseif($row["tipo_transferencia"] == 3){
			$tipoTransferencia = "PND";
		}
			
		switch($row['estado_transferencia']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;						
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			default : $class = ""; break;
		}	
		
		$btnAnularTransferencia = "";
		$btnDevolverTransferencia = "";
		if($row["fecha_transferencia"] == date("Y-m-d") && $row["estatus"] == 1){//SOLO ANULAR LOS DEL MISMO DIA
			$btnAnularTransferencia = sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onClick=\"abrirDivFlotante1(this,%s)\"><img  src=\"../img/iconos/delete.png\" title=\"Anular Transferencia\" class=\"puntero\"/></a>",
			$contFila,
			$row["id_transferencia"]);
		}elseif($row["estatus"] == 1){//ANTERIORES NO SE ANULAN, SE DEVUELVEN CON NOTA DE CARGO
			$btnDevolverTransferencia = sprintf("<img onClick=\"if(confirm('¿Seguro deseas generar nota de cargo para la Transferencia Nro: %s?')){ xajax_devolverTransferencia(%s); }\" src=\"../img/iconos/arrow_rotate_clockwise.png\" title=\"Devolver Transferencia\" class=\"puntero\"/>",
			$row["numero_transferencia"],
			$row["id_transferencia"]);
		}

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_transferencia']))."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoTransferencia."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_transferencia']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= ((strlen($row['observacion_transferencia']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_transferencia'])."</span></td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".utf8_encode($row['descripcion_estado_transferencia'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_transferencia'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_neto_transferencia'],2,".",",")."</td>";
			$htmlTb .= "<td>".$btnAnularTransferencia."</td>";
			$htmlTb .= "<td>".$btnDevolverTransferencia."</td>";
			$htmlTb .= "<td>";			
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=6&id=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Recibo Transferencia PDF\"/></a>",
					$row['id_transferencia']);			
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[9] += $row['saldo_transferencia'];
		$arrayTotal[10] += $row['monto_neto_transferencia'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[9] += $row['saldo_transferencia'];
				$arrayTotalFinal[10] += $row['monto_neto_transferencia'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[9], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[10], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"3\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"15\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTransferencia(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListadoTransferencias","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalCheques += $row['monto_neto_transferencia'];
		$totalSaldo += $row['saldo_transferencia'];
	}
	
	$objResponse->assign("spnTotalTransferencias","innerHTML",number_format($totalCheques, 2, ".", ","));
	$objResponse->assign("spnSaldoTransferencias","innerHTML",number_format($totalSaldo, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularTransferencia");
$xajax->register(XAJAX_FUNCTION,"buscarTransferencia");
$xajax->register(XAJAX_FUNCTION,"cargarTransferencia");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"devolverTransferencia");
$xajax->register(XAJAX_FUNCTION,"imprimirDevolucionTransferencia");
$xajax->register(XAJAX_FUNCTION,"listaTransferencia");

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