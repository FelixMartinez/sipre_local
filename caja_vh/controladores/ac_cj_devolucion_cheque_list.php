<?php

//ANULA CHEQUE DEVUELVE SALDO CAJA
function anularCheque($formAnulacion){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if(!xvalidaAcceso($objResponse,"cj_devolucion_cheque_list","insertar")){ return $objResponse; }
	
	mysql_query("START TRANSACTION");

	$idCheque = $formAnulacion["hddIdCheque"];
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	//VALIDO APERTURA DE LAS CAJAS
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	//VERIFICO QUE TODOS LOS PAGOS REALIZADOS MEDIANTE EL CHEQUE ESTEN ANULADOS
	$query = sprintf("SELECT
			  (SELECT COUNT(*) FROM an_pagos WHERE id_cheque = %s AND estatus = 1) as pagos_vehiculos,
			  (SELECT COUNT(*) FROM cj_cc_detalleanticipo WHERE id_cheque = %s AND estatus = 1) as pagos_anticipos,
			  (SELECT COUNT(*) FROM cj_det_nota_cargo WHERE id_cheque = %s AND estatus = 1) as pagos_nota_cargo,
			  (SELECT COUNT(*) FROM sa_iv_pagos WHERE id_cheque = %s AND estatus = 1) as pagos_repuestos_servicios",
			  $idCheque,
			  $idCheque,
			  $idCheque,
			  $idCheque);
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	if($row["pagos_vehiculos"] > 0 || $row["pagos_anticipos"] > 0 ||$row["pagos_nota_cargo"] > 0 || $row["pagos_repuestos_servicios"] > 0){
		return $objResponse->alert("El cheque tiene pagos activos, debe anularlos primero");
	}
	
	//BUSCO INFORMACION DEL CHEQUE
	$query = sprintf("SELECT monto_neto_cheque, id_departamento, estatus FROM cj_cc_cheque WHERE id_cheque = %s",
		$idCheque);
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
	$rowCheque = mysql_fetch_assoc($rs);
	
	if($rowCheque["estatus"] != 1) { return $objResponse->alert("El cheque ya ha sido anulado"); }
	
	$idModulo = $rowCheque["id_departamento"];
	$montoCheque = $rowCheque["monto_neto_cheque"];
	
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

	//RESTO EL MONTO YA ENTRADO DEL CHEQUE
	$query = sprintf("UPDATE ".$apertCajaPpal." SET
				saldoCheques = saldoCheques - %s,
				saldoCaja = saldoCaja - %s
		WHERE id = %s;",
				valTpDato($montoCheque, "real_inglesa"),
				valTpDato($montoCheque, "real_inglesa"),
				valTpDato($rowAperturaCaja['id'], "int"));
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
	
	// ACTUALIZO EL ESTADO DEL CHEQUE A ANULADO (0 = Anulado, 1 = Activo)
	$query = sprintf("UPDATE cj_cc_cheque SET
		estatus = 0,
		fecha_anulado = %s,
		id_empleado_anulado = %s,
		motivo_anulacion = %s
	WHERE id_cheque = %s;",
		valTpDato("NOW()", "campo"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($formAnulacion["txtMotivoAnulacion"], "text"),
		valTpDato($idCheque, "int"));
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
								
	mysql_query("COMMIT");
								
	$objResponse->alert("Cheque anulado correctamente");
	$objResponse->script("byId('btnCancelarAnulacion').click();
						  byId('btnBuscar').click();");
	
	return $objResponse;
}

function buscarCheque($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatus'],
		implode(",",$frmBuscar['lstEstadoCheque']),
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaCheque(0, "id_cheque", "DESC", $valBusq));
	
	return $objResponse;
}

function cargarCheque($idCheque){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT numero_cheque, id_departamento FROM cj_cc_cheque WHERE id_cheque = %s LIMIT 1",
					  $idCheque);
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
		
	$rowCheque = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNroCheque","value",$rowCheque["numero_cheque"]);
	$objResponse->assign("hddIdCheque","value",$idCheque);
		
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s)", valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_enlace_concepto'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_enlace_concepto']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstOrientacionPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("V" => "Vertical", "H" => "Horizontal");
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirCheque(xajax.getFormValues('frmBuscar'));\"";
	
	$html = "<select id=\"lstOrientacionPDF\" name=\"lstOrientacionPDF\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstOrientacionPDF","innerHTML",$html);
	
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
	
	$objResponse->script("xajax_buscarCheque(xajax.getFormValues('frmBuscar'));");
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	return $objResponse;
}

//ANULA CHEQUE CREA NOTA DE CARGO
function devolverCheque($idCheque){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	if(!xvalidaAcceso($objResponse,"cj_devolucion_cheque_list","insertar")){ return $objResponse; }

	mysql_query("START TRANSACTION");

	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];

	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }

	//BUSCO INFORMACION DEL CHEQUE
	$sqlCheque = sprintf("SELECT
		numero_cheque,
		monto_neto_cheque,
		id_cliente,
		id_banco_cliente,
		estatus
	FROM cj_cc_cheque
	WHERE id_cheque = %s",
		$idCheque);
	$rsCheque = mysql_query($sqlCheque);
	if (!$rsCheque){ return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlCheque); }
	$rowCheque = mysql_fetch_assoc($rsCheque);
	
	if($rowCheque["estatus"] != 1) { return $objResponse->alert("El cheque ya ha sido devuelto"); }
	
	$numeroCheque = $rowCheque["numero_cheque"];
	$monto = $rowCheque["monto_neto_cheque"];
	$idCliente = $rowCheque["id_cliente"];
	$idBanco = $rowCheque["id_banco_cliente"];

	//INSERCCION DE LA NOTA DE CARGO EN TESORERIA Y REBAJAR EL SALDO DE LA CUENTA
	$sqlSelectDatosBanco = sprintf("SELECT
		idBancoAdepositar AS idBancoAdepositar,
		(SELECT nombreBanco FROM bancos ba WHERE ba.idBanco = deta.idBancoAdepositar) AS nombreBanco,
		(SELECT idCuentas FROM cuentas cu WHERE cu.numeroCuentaCompania LIKE deta.numeroCuentaBancoAdepositar) AS idCuenta
	FROM an_detalledeposito deta
	WHERE numeroCheque LIKE %s AND banco = %s ",
		valTpDato($numeroCheque,'text'),
		valTpDato($idBanco,'int'));
	$rsSelectDatosBanco = mysql_query($sqlSelectDatosBanco);
	if (!$rsSelectDatosBanco) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlSelectDatosBanco);
	$rowSelectDatosBanco = mysql_fetch_array($rsSelectDatosBanco);
	
	$idCuentaCompania = $rowSelectDatosBanco['idCuenta'];

	//VERIFICA QUE EL CHEQUE SE ENCUENTRE DEPOSITADO
	$sqlSelectCheque = sprintf("SELECT *
	FROM an_detalledeposito
	WHERE numeroCheque LIKE %s AND banco = %s",
			valTpDato($numeroCheque,'text'),
			valTpDato($idBanco,'int'));
	$rsSelectCheque = mysql_query($sqlSelectCheque);
	if (!$rsSelectCheque) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlSelectCheque);
	$rowSelectCheque = mysql_fetch_array($rsSelectCheque);
	$totalRowsCheque = mysql_num_rows($rsSelectCheque);
	
	if ($totalRowsCheque == 0) {	
		return $objResponse->alert('El Cheque que intenta devolver aun no ha sido depositado.');
	}

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
		valTpDato($numeroCheque,'text'),
		valTpDato("NOW()",'campo'),
		valTpDato($numeroActual,'int'),
		valTpDato("NOW()",'campo'),
		valTpDato($monto,'double'),
		valTpDato($monto,'double'),
		valTpDato(0,'int'), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
		valTpDato('DEVOLUCION DE CHEQUE '.$numeroCheque,'text'),
		valTpDato(0,'double'),
		valTpDato($idCliente,'int'),
		valTpDato(2,'int'), // 0 = Repuestos, 1 = Servicios, 2 = Vehìculos, 3 = Administración
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
		valTpDato("CHEQUE DEVUELTO ".$rowSelectDatosBanco['nombreBanco'],'text'),
		valTpDato("NOW()", "campo"),
		valTpDato("NOW()", "campo"),
		valTpDato(0,'int'),
		valTpDato(2,'int'), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
		valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
		valTpDato($idUsuario,'int'),
		valTpDato($monto,'double'),
		valTpDato($idEmpresa,'int'),
		valTpDato($numeroCheque,'text'));
	$rsInsertNotaDebitoTesoreria = mysql_query($sqlInsertNotaDebitoTesoreria);
	if (!$rsInsertNotaDebitoTesoreria) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlInsertNotaDebitoTesoreria);
	$idNotaDebito = mysql_insert_id();
	
	$sqlInsertEstadoCuentaTesoreria = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
	VALUES('ND', %s, NOW(), %s, %s, %s, 0, %s, 1, %s, 2, 0)",
		valTpDato($idNotaDebito,'int'),
		valTpDato($idCuentaCompania,'int'),
		valTpDato($idEmpresa,'int'),
		valTpDato($monto,'double'),
		valTpDato($numeroCheque,'text'),
		valTpDato("CHEQUE DEVUELTO ".$rowSelectDatosBanco['nombreBanco'],'text'));
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
	
	// ACTUALIZO EL ESTADO DEL CHEQUE A ANULADO (0 = Anulado, 1 = Activo)
	$query = sprintf("UPDATE cj_cc_cheque SET
		saldo_cheque = 0,
		estatus = 0,
		fecha_anulado = %s,
		id_empleado_anulado = %s,
		motivo_anulacion = %s
	WHERE id_cheque = %s;",
		valTpDato("NOW()", "campo"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato('DEVOLUCION DE CHEQUE '.$numeroCheque, "text"),
		valTpDato($idCheque, "int"));
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
	
	$objResponse->alert("Cheque devuelto, se genero nota de cargo");
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->script(sprintf("verVentana('reportes/cjvh_comprobante_devolucion_nota_cargo.php?valBusq=%s|%s',960,550)", $idEmpresa, $idNotaCargo));

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

function imprimirCheque($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatus'],
		implode(",",$frmBuscar['lstEstadoCheque']),
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjvh_devolucion_cheque_pdf.php?valBusq=%s&lstOrientacionPDF=%s',890,550)", $valBusq, $frmBuscar['lstOrientacionPDF']));
	
	$objResponse->assign("tdlstOrientacionPDF","innerHTML","");
	
	return $objResponse;
}

function listaCheque($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ch.id_departamento IN (%s)",
		valTpDato($idModuloPpal, "campo"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ch.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = ch.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ch.fecha_cheque BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ch.estatus = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ch.estado_cheque IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ch.id_departamento IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ch.numero_cheque LIKE %s
		OR banco.nombreBanco LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s
		OR ch.observacion_cheque LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT
		ch.id_cheque,
		ch.tipo_cheque,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		ch.monto_neto_cheque,
		IF (ch.estatus = 1, ch.saldo_cheque, 0) AS saldo_cheque,
		ch.fecha_cheque,
		ch.numero_cheque,
		banco.nombreBanco,
		ch.id_departamento,
		ch.estatus,
		IF (ch.estatus = 1, ch.estado_cheque, NULL) AS estado_cheque,
		(CASE ch.estatus
			WHEN 1 THEN
				(CASE ch.estado_cheque
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
				END)
			ELSE
				'Anulado'
		END) AS descripcion_estado_cheque,
		ch.motivo_anulacion,
		ch.observacion_cheque,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, 
			CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), 
			vw_iv_emp_suc.nombre_empresa) AS nombre_empresa		
		
	FROM cj_cc_cheque ch
		INNER JOIN cj_cc_cliente cliente ON (ch.id_cliente = cliente.id)
		INNER JOIN bancos banco ON (ch.id_banco_cliente = banco.idBanco)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ch.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaCheque", "", $pageNum, "id_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "", $pageNum, "estatus", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "6%", $pageNum, "fecha_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Cheque");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "6%", $pageNum, "tipo_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Cheque");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "16%", $pageNum, "numero_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Cheque");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "30%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "8%", $pageNum, "descripcion_estado_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "8%", $pageNum, "saldo_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "8%", $pageNum, "monto_neto_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
		$htmlTh .= "<td colspan=\"4\"></td>";
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
			$imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Anulado\"/>";
		} else if ($row['estatus'] == 1){ // 0 = ANULADO ; 1 = ACTIVO
			$imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>";
		}
		
		
		if($row["tipo_cheque"] == 1){
			$tipoCheque = "Cliente";
		}elseif($row["tipo_cheque"] == 2){
			$tipoCheque = "Bono Suplidor";
		}elseif($row["tipo_cheque"] == 3){
			$tipoCheque = "PND";
		}
			
		switch($row['estado_cheque']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;				
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			default : $class = ""; break;
		}	
		
		$btnAnularCheque = "";
		$btnDevolverCheque = "";
		if($row["fecha_cheque"] == date("Y-m-d") && $row["estatus"] == 1){//SOLO ANULAR LOS DEL MISMO DIA
			$btnAnularCheque = sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onClick=\"abrirDivFlotante1(this,%s)\"><img  src=\"../img/iconos/delete.png\" title=\"Anular Cheque\" class=\"puntero\"/></a>",
			$contFila,
			$row["id_cheque"]);
		}elseif($row["estatus"] == 1){//ANTERIORES NO SE ANULAN, SE DEVUELVEN CON NOTA DE CARGO
			$btnDevolverCheque = sprintf("<img onClick=\"if(confirm('¿Seguro deseas generar nota de cargo para el Cheque Nro: %s?')){ xajax_devolverCheque(%s); }\" src=\"../img/iconos/arrow_rotate_clockwise.png\" title=\"Devolver Cheque\" class=\"puntero\"/>",
			$row["numero_cheque"],
			$row["id_cheque"]);
		}

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_cheque']))."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoCheque."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".utf8_encode($row['nombreBanco'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_cheque'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['observacion_cheque']) > 0) ? "<tr><td class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_cheque'])."</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".utf8_encode($row['descripcion_estado_cheque'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_cheque'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_neto_cheque'],2,".",",")."</td>";
			$htmlTb .= "<td>".$btnAnularCheque."</td>";
			$htmlTb .= "<td>".$btnDevolverCheque."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"../cxc/cc_cheque_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\"/ title=\"Ver\"></a>",
					$row['id_cheque']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if (in_array($row['id_departamento'], array(2,4))){ // 2 = Vehiculos, 4 = Alquiler
				$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=5&id=%s", $row['id_cheque']);
			} else if (in_array($row['id_departamento'], array(0,1,3))){ // 0 = Repuestos, 1 = Servicios, 3 = Administración
				$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=5&id=%s", $row['id_cheque']);
			}
				$htmlTb .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".("Recibo(s) de Pago(s)")."\"/></a>" : "";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[10] += $row['saldo_cheque'];
		$arrayTotal[11] += $row['monto_neto_cheque'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"4\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[10] += $row['saldo_cheque'];
				$arrayTotalFinal[11] += $row['monto_neto_cheque'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[10], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"4\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCheque(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheque(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListadoCheques","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalCheques += $row['monto_neto_cheque'];
		$totalSaldo += $row['saldo_cheque'];
	}
	
	$objResponse->assign("spnTotalCheques","innerHTML",number_format($totalCheques, 2, ".", ","));
	$objResponse->assign("spnSaldoCheques","innerHTML",number_format($totalSaldo, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularCheque");
$xajax->register(XAJAX_FUNCTION,"buscarCheque");
$xajax->register(XAJAX_FUNCTION,"cargarCheque");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstOrientacionPDF");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"devolverCheque");
$xajax->register(XAJAX_FUNCTION,"imprimirCheque");
$xajax->register(XAJAX_FUNCTION,"listaCheque");

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
		return array(false, "Debe cerrar la caja del dia: ".date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja'])));
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