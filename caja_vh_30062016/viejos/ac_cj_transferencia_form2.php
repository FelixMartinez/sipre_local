<?php

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
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	
	$objResponse->script("byId('divFlotante').style.display = 'none';");
	$objResponse->script("limpiarTransferencia();");	
	
	return $objResponse;
}

function asignarFecha(){
	$objResponse = new xajaxResponse();
	
	$fecha = date("d-m-Y");
	
	$objResponse->assign("txtFecha","value",$fecha);
	
	return $objResponse;
}

function buscarAnticipo($valForm, $frmAnticipos, $frmDcto){
    $objResponse = new xajaxResponse();

	if($frmDcto['txtIdCliente'] == ""){
		return $objResponse->alert("Debe seleccionar cliente");
	}

    // DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS	
    foreach($frmAnticipos["anticipoAgregado"] as $key => $valor){
            $idExistenteArray[] = array_shift(explode("|",$valor));
    }

    $cadenaIdAnticipos = implode(",",$idExistenteArray);

    $valBusq = sprintf("%s|%s|%s|%s",
            $valForm['txtCriterioAnticipo'],
            $cadenaIdAnticipos,
            $frmDcto['lstTipoTransferencia'],
            $frmDcto['txtIdCliente']);

    $objResponse->loadCommands(listadoAnticipo(0,"","",$valBusq));

    return $objResponse;
}

function buscarCliente($valForm, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$valForm['txtCriterioBusqCliente']);
	
	$objResponse->loadCommands(listadoClientes(0,"id","ASC",$valBusq));
	
	return $objResponse;
}

function cargarSaldoDocumento($idDocumento){
    $objResponse = new xajaxResponse();

	//ojo se usa el monto completo porque ya no tiene saldo al agregarse a la factura
    $query = sprintf("SELECT (montoNetoAnticipo - totalPagadoAnticipo) AS saldoDocumento, numeroAnticipo AS numeroDocumento
    FROM cj_cc_anticipo WHERE idAnticipo = %s", $idDocumento);
    $documento = "Anticipo";

    $rsSelectDocumento = mysql_query($query);
    if (!$rsSelectDocumento) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
    $rowSelectDocumento = mysql_fetch_array($rsSelectDocumento);
	
    $objResponse->assign("hddIdDocumento","value",$idDocumento);
    $objResponse->assign("txtNroDocumento","value",$rowSelectDocumento['numeroDocumento']); 
    $objResponse->assign("txtSaldoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'],2,'.',','));
    $objResponse->assign("txtMontoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'],2,'.',','));

    $objResponse->assign("tdFlotanteTitulo1","innerHTML",$documento);
    $objResponse->script("
    if (byId('divFlotante1').style.display == 'none') {
            byId('divFlotante1').style.display = '';
            centrarDiv(byId('divFlotante1'));

            byId('txtMontoDocumento').focus();}");

    return $objResponse;
}

function cargarPago($frmAnticipos, $frmDetalleAnticipo){
    $objResponse = new xajaxResponse();

    if (str_replace(",","",$frmAnticipos['txtMontoPorPagar']) < str_replace(",","",$frmDetalleAnticipo['txtMontoDocumento'])){
        return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la Transferencia");
    }

    foreach($frmAnticipos["anticipoAgregado"] as $key => $valor){
        $idExistente = array_shift(explode("|",$valor));

        if($idExistente == $frmDetalleAnticipo['hddIdDocumento']){
                return $objResponse->alert("El anticipo ya se encuentra agregado");
        }
    }

    $query = sprintf("SELECT observacionesAnticipo, fechaAnticipo
                    FROM cj_cc_anticipo 
                    WHERE idAnticipo = %s LIMIT 1", 
                    $frmDetalleAnticipo['hddIdDocumento']);

    $rsSelectDocumento = mysql_query($query);
    if (!$rsSelectDocumento) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query); }
    $rowSelectDocumento = mysql_fetch_array($rsSelectDocumento);

    $numeroAnticipo = $frmDetalleAnticipo['txtNroDocumento'];
    $idDocumento = $frmDetalleAnticipo['hddIdDocumento'];	
    $montoPagado = $frmDetalleAnticipo['txtMontoDocumento'];
    $descripcionAnticipo = str_replace("\n","", utf8_decode($rowSelectDocumento['observacionesAnticipo']));
    $fechaAnticipo = date("d-m-Y",strtotime($rowSelectDocumento['fechaAnticipo']));

    $checkBox = sprintf("<input type=\"checkbox\" checked=\"checked\" style=\"display:none;\" name=\"anticipoAgregado[]\" value=\"%s|%s\" />",
                        $idDocumento,
                        $montoPagado);
    $botonEliminar = "<button title=\"Eliminar\" onclick=\"eliminarAnticipo(this);\" type=\"button\"><img src=\"../img/iconos/delete.png\"></button>";

    $td = sprintf("<tr><td>%s%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>",	
            $checkBox,
            $numeroAnticipo,		
            $fechaAnticipo,
            $descripcionAnticipo,
            $montoPagado,
            $botonEliminar
            );

    $objResponse->script("$('#trItmPie').before('".$td."');");
    $objResponse->script("colorTabla();");
    $objResponse->script("calcularTotal();");	

    return $objResponse;
	
}

function calcularTotal($montoTransferencia, $frmAnticipos){
	
    $objResponse = new xajaxResponse();

    $montoTransferencia = str_replace(",","", $montoTransferencia);//viene con formato 0,000.00	
    $totalAnticipos = 0;

    foreach($frmAnticipos["anticipoAgregado"] as $key => $valor){
            $arrayInfoAnticipo = explode("|",$valor);//0 id 1 montoPagado

            $totalAnticipos += str_replace(",","", $arrayInfoAnticipo[1]);//viene con formato 0,000.00		
    }

    $totalFaltaPorPagar = $montoTransferencia - $totalAnticipos;

    $objResponse->assign("txtMontoPorPagar","value",number_format($totalFaltaPorPagar,2,'.',','));
    $objResponse->assign("txtMontoPagado","value",number_format($totalAnticipos,2,'.',','));

    if($totalAnticipos == 0){//desbloquear monto Transferencia
            $objResponse->script("byId('txtMontoTransferencia').readOnly = false;
								byId('txtMontoTransferencia').className = 'inputHabilitado';");
    }else{//bloquear monto Transferencia
            $objResponse->script("byId('txtMontoTransferencia').readOnly = true;
								byId('txtMontoTransferencia').className = '';");
    }

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

function cargarBancoCliente($idTd, $idSelect){
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT idBanco, nombreBanco FROM bancos WHERE idBanco <> 1 ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<select name='%s' id='%s' class='inputHabilitado'>",$idSelect,$idSelect);
		$html .= sprintf("<option value = ''>[ Seleccione ]");
		while ($row = mysql_fetch_array($rs)){
			$html .= sprintf("<option value = '%s'>%s",$row["idBanco"],utf8_encode($row["nombreBanco"]));
		}
	$html .= "</select>";
	
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function cargarBancoCompania($tipoPago){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT idBanco, (SELECT nombreBanco FROM bancos WHERE bancos.idBanco = cuentas.idBanco) AS banco FROM cuentas GROUP BY cuentas.idBanco ORDER BY banco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<select name='selBancoCompania' id='selBancoCompania' class='inputHabilitado' onchange='xajax_cargarCuentasCompania(this.value,".$tipoPago.");' >");
		$html .= sprintf("<option value = ''>Seleccione");
		while ($row = mysql_fetch_array($rs)){
			$html .= sprintf("<option value = '%s'>%s",$row["idBanco"],utf8_encode($row["banco"]));
		}
	$html .= "</select>";
	
	$objResponse->assign("tdBancoCompania","innerHTML",$html);
	
	return $objResponse;
}

function cargarCuentasCompania($idBanco, $tipoPago){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas WHERE idBanco = %s AND estatus = 1",
				valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<select name='selNumeroCuenta' id='selNumeroCuenta' class='inputHabilitado' >");
	$registros = mysql_num_rows($rs);
	if ($registros > 1 || $registros == 0)
		$html .= sprintf("<option value = ''>Seleccione");
		
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value = '%s'>%s",$row["idCuentas"],utf8_encode($row["numeroCuentaCompania"]));
	}
	$html .= "</select>";
	
	$objResponse->assign("tdNumeroCuentaSelect","innerHTML",$html);
	
	return $objResponse;
}

function guardarTransferencia($frmDcto, $frmDetallePago, $frmAnticipos){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cj_transferencia_list","insertar")){ errorCargarPago($objResponse); return $objResponse; }
	
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = $frmDcto['lstModulo'];
	$idCliente = $frmDcto['txtIdCliente'];
	$tipoTransferencia = $frmDcto['lstTipoTransferencia'];
	$idBancoCliente = $frmDetallePago['selBancoCliente'];
	$idBancoCompania = $frmDetallePago['selBancoCompania'];
	$idCuentaCompania = $frmDetallePago['selNumeroCuenta'];
	
	$numeroCuentaCompania = numeroCuenta($idCuentaCompania);
	$numeroCuentaCliente = $numeroCuentaCompania; //usa la misma en las dos
	$numeroTransferencia = trim($frmDetallePago['numeroTransferencia']);//nro de referencia
	
	$montoTransferencia = str_replace(",","",$frmDetallePago['txtMontoTransferencia']);
	$saldoTransferencia = str_replace(",","",$frmAnticipos['txtMontoPorPagar']);
	$montoPagado = $montoTransferencia;
	$fecha = date("Y-m-d", strtotime($frmDcto['txtFecha']));

	/*var_dump(
		"idUsuario:".$idUsuario
		." idEmpresa:".$idEmpresa
		." idModulo:".$idModulo
		." idCliente:".$idCliente
		." tipoTransferencia:".$tipoTransferencia
		." idBancoCliente:".$idBancoCliente
		." idBancoCompania:".$idBancoCompania
		." idCuentaCompania:".$idCuentaCompania
		." numeroCuentaCompania:".$numeroCuentaCompania
		." numeroCuentaCliente:".$numeroCuentaCliente
		." numeroTransferencia:".$numeroTransferencia
		." montoTransferencia:".$montoTransferencia
		." saldoTransferencia:".$saldoTransferencia
		." montoPagado:".$montoPagado
	);*/
		
	if($idModulo == ""){ return $objResponse->alert("No se envio el modulo"); }
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorCargarPago($objResponse); return $objResponse->alert($Result1[1]); }

	if($montoTransferencia <= 0){ return $objResponse->alert("El monto de la transferencia no debe ser cero ni negativo");	}	
	if($saldoTransferencia < 0){ return $objResponse->alert("El saldo de la transferencia no debe ser negativo"); }	
	if($idCliente == ""){ return $objResponse->alert("Debe seleccionar un cliente"); }

	if($tipoTransferencia == 1){//tipo cliente
		if($saldoTransferencia != $montoTransferencia){ return $objResponse->alert("El monto de la transferencia y el saldo deben coincidir"); }
		$estado = 1;
		
	}elseif($tipoTransferencia == 2 || $tipoTransferencia == 3){//2 = tipo bono, 3 = tipo pnd
		
		if($frmAnticipos["anticipoAgregado"] == ""){ return $objResponse->alert("Debe seleccionar almenos un anticipo"); }				
		if($saldoTransferencia == 0){ $estado = 3;	}else{ $estado = 2;	}
		if($saldoTransferencia != 0){ return $objResponse->alert("Debe usar el saldo completo de la transferencia para pagar anticipos"); }
		
	}else{//no se envio id tipo
		return $objResponse->alert("No se especifico tipo de transferencia");
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
	if (!$rsAperturaCaja) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);

	$fechaRegistroPago = $rowAperturaCaja["fechaAperturaCaja"];
	
	//TODA TRANSFERENCIA INGRESA INMEDIATAMENTE A CAJA:	
	$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
				saldoTransferencia = saldoTransferencia + %s,
				saldoCaja = saldoCaja + %s
		WHERE id = %s;",
				valTpDato($montoTransferencia, "real_inglesa"),
				valTpDato($montoTransferencia, "real_inglesa"),
				valTpDato($rowAperturaCaja['id'], "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
	$sqlInsertTransferencia = sprintf("INSERT INTO cj_cc_transferencia (id_cliente, tipo_transferencia, id_banco_cliente, id_banco_compania, id_cuenta_compania, cuenta_cliente, cuenta_compania, monto_neto_transferencia, saldo_transferencia, total_pagado_transferencia, fecha_transferencia, id_empleado_registro, observacion_transferencia, estado_transferencia, numero_transferencia, id_departamento, id_empresa, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",									
						valTpDato($idCliente, "int"),
						valTpDato($tipoTransferencia, "int"),
						valTpDato($idBancoCliente, "int"),
						valTpDato($idBancoCompania, "int"),
						valTpDato($idCuentaCompania, "int"),
						valTpDato($numeroCuentaCliente, "text"),//cuenta cliente usa el mismo de la compania
						valTpDato($numeroCuentaCompania, "text"),
						$montoTransferencia,
						$saldoTransferencia,
						$montoPagado,
						valTpDato($fechaRegistroPago,"date"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($frmDcto['txtObservacionTransferencia'], "text"),
						valTpDato($estado, "int"), // 0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
						valTpDato($numeroTransferencia, "text"),
						valTpDato($frmDcto['lstModulo'], "int"),
						valTpDato($idEmpresa, "int"),
						1,
						0,
						valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
						0);
	$rsInsertTransferencia = mysql_query($sqlInsertTransferencia);
	if (!$rsInsertTransferencia) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertTransferencia); }
	
	$idTransferencia = mysql_insert_id();
	
	/*INSERT EN EL ESTADO DE CUENTA*/
	$insertEstadoCuenta = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
									VALUES ('TB', %s, %s, 6)",
									$idTransferencia, 
									valTpDato($fechaRegistroPago,"date"));
	
	$rsEstadoCuenta = mysql_query($insertEstadoCuenta);
	if (!$rsEstadoCuenta) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertEstadoCuenta); }
	
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
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
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
	if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idReporteImpresionTransferencia = mysql_insert_id();
	
		
	if($tipoTransferencia == 1){//1 = tipo cliente
	
	}elseif($tipoTransferencia == 2 || $tipoTransferencia == 3){//tipo 2 = Bono suplidor, 3 = PND
	
		foreach($frmAnticipos["anticipoAgregado"] as $key => $valor){
			$anticipoArray = explode("|",$valor);
			$idAnticipo = $anticipoArray[0];
			$montoPagadoAnticipo = str_replace(",","",$anticipoArray[1]);
										
			//consulto anticipo para verificar monto, se usa monto porque el saldo ya esta en cero al cargarse a una fact
			$sqlAnticipos = sprintf("SELECT numeroAnticipo, (montoNetoAnticipo - totalPagadoAnticipo) AS monto_faltante_pago FROM cj_cc_anticipo WHERE idAnticipo = %s LIMIT 1",
                                                $idAnticipo);
			$rsAnticipos = mysql_query($sqlAnticipos);
			if (!$rsAnticipos){ errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlAnticipos); }			
			$rowAnticipo = mysql_fetch_assoc($rsAnticipos);
			
			$nuevoSaldoAnticipo = $rowAnticipo['monto_faltante_pago'] - $montoPagadoAnticipo;
			
			if($nuevoSaldoAnticipo < 0){
				errorCargarPago($objResponse); 
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
				valTpDato(45, "int"), // 45 = Recibo de Pago Vehículos
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			$rsNumeracion = mysql_query($queryNumeracion);
			if (!$rsNumeracion) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
			
			$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
			$idNumeraciones = $rowNumeracion['id_numeracion'];
			$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
			
			// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
			$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
			WHERE id_empresa_numeracion = %s;",
				valTpDato($idEmpresaNumeracion, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
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
			if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idReporteImpresion = mysql_insert_id();
			
			
			$sqlInsertDetalleAnticipo = sprintf("INSERT INTO cj_cc_detalleanticipo (id_reporte_impresion, id_transferencia, tipoPagoDetalleAnticipo, id_forma_pago, bancoClienteDetalleAnticipo, bancoCompaniaDetalleAnticipo, numeroCuentaCliente, numeroCuentaCompania, numeroControlDetalleAnticipo, montoDetalleAnticipo, idAnticipo, fechaPagoAnticipo, tomadoEnCierre, idCaja, idCierre)
			VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 0)", 
				$idReporteImpresion,
				$idTransferencia,				
				valTpDato('TB', "text"),
				valTpDato(4, "int"),//4 = Transferencia Bancaria
				valTpDato($idBancoCliente, "int"),
				valTpDato($idBancoCompania, "int"),
				valTpDato($numeroCuentaCliente, "int"),//cuenta cliente usa el mismo de la compania
				valTpDato($numeroCuentaCompania, "text"),
				valTpDato($numeroTransferencia, "text"),
				valTpDato($montoPagadoAnticipo,"double"),
				valTpDato($idAnticipo, "int"),
				valTpDato($fechaRegistroPago,"date"),//fecha del documento
				valTpDato(0, "int"),//tomado en cierre es cero
				valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			$rsInsertDetalleAnticipo = mysql_query($sqlInsertDetalleAnticipo);
			if (!$rsInsertDetalleAnticipo)  { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDetalleAnticipo);}
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
			if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
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
			if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
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
			if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
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
				if (!$rsPago) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
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
								$idNumeraciones = $rowNumeracion['id_numeracion'];
								$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
								
								// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
								$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
								WHERE id_empresa_numeracion = %s;",
									valTpDato($idEmpresaNumeracion, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
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
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$idEncabezadoReciboPago = mysql_insert_id();
								
								// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
								$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_v (id_factura, fecha_pago)
								VALUES (%s, %s)",
									valTpDato($idFactura, "int"),
									valTpDato($fechaRegistroPago, "date"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$idEncabezadoPago = mysql_insert_id();
								
								// INSERTA EL NUEVO PAGO CON LA APERTURA DE CAJA ACTUAL
								$insertSQL = sprintf("INSERT INTO an_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipo_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_encabezado_v)
								SELECT id_factura, %s, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipo_transferencia, %s, %s, idCaja, %s, %s, %s
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
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
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
								if (!$rsNumeracion) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
								
								$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
								$idNumeraciones = $rowNumeracion['id_numeracion'];
								$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
								
								// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
								$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
								WHERE id_empresa_numeracion = %s;",
									valTpDato($idEmpresaNumeracion, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
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
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$idEncabezadoReciboPago = mysql_insert_id();
							
								// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
								$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
								VALUES (%s, %s)",
									valTpDato($idFactura, "int"),
									valTpDato($fechaRegistroPago, "date"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL); }
								$idEncabezadoPago = mysql_insert_id();
								
								// INSERTA EL NUEVO PAGO CON LA APERTURA DE CAJA ACTUAL
								$insertSQL = sprintf("INSERT INTO sa_iv_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipo_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_encabezado_rs)
								SELECT id_factura, %s, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipo_transferencia, %s, %s, idCaja, %s, %s, %s
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
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$idPago = mysql_insert_id();
								mysql_query("SET NAMES 'latin1';");
							}
							
							// INSERTA EL DETALLE DEL RECIBO DE PAGO
							$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
							VALUES (%s, %s)",
								valTpDato($idEncabezadoReciboPago, "int"),
								valTpDato($idPago, "int"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
							// ACTUALIZA EL SALDO DE LA FACTURA DEPENDIENDO DE SUS PAGOS
							$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
								saldoFactura = saldoFactura - IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
																		WHERE cxc_pago.id_factura = cxc_fact.idFactura
																			AND cxc_pago.estatus = 1), 0)
							WHERE idFactura = %s;",
								valTpDato($idFactura, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
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
							if (!$rsNumeracion) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
							
							$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
							$idNumeraciones = $rowNumeracion['id_numeracion'];
							$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
							
							// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
							$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
							WHERE id_empresa_numeracion = %s;",
								valTpDato($idEmpresaNumeracion, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$idEncabezadoReciboPago = mysql_insert_id();
							
							if (in_array($idCaja,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
								// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
								$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_nc_v (id_nota_cargo, fecha_pago)
								VALUES (%s, %s)",
									valTpDato($idNotaCargo, "int"),
									valTpDato($fechaRegistroPago, "date"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$idEncabezadoPago = mysql_insert_id();
							} else if (in_array($idCaja,array(2))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
								// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
								$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_nc_rs (id_nota_cargo, fecha_pago)
								VALUES (%s, %s)",
									valTpDato($idNotaCargo, "int"),
									valTpDato($fechaRegistroPago, "date"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$idEncabezadoPago = mysql_insert_id();
							}
							
							// INSERTA EL NUEVO PAGO CON LA APERTURA DE CAJA ACTUAL
							$insertSQL = sprintf("INSERT INTO cj_det_nota_cargo (idNotaCargo, fechaPago, idFormaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, monto_pago, tipo_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_encabezado_nc)
							SELECT idNotaCargo, %s, idFormaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, monto_pago, tipo_transferencia, %s, %s, idCaja, %s, %s, %s
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$idPago = mysql_insert_id();
							mysql_query("SET NAMES 'latin1';");
							
							// INSERTA EL DETALLE DEL RECIBO DE PAGO
							$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
							VALUES (%s, %s)",
								valTpDato($idEncabezadoReciboPago, "int"),
								valTpDato($idPago, "int"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
							// ACTUALIZA EL SALDO DE LA NOTA DE CARGO
							$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
								saldoNotaCargo = IFNULL(cxc_nd.subtotalNotaCargo, 0)
													- IFNULL(cxc_nd.descuentoNotaCargo, 0)
													+ IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
													+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)
							WHERE idNotaCargo = %s;",
								valTpDato($idNotaCargo, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
							// ACTUALIZA EL SALDO DE LA NOTA DE CARGO DEPENDIENDO DE SUS PAGOS
							$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
								saldoNotaCargo = saldoNotaCargo - IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
																			WHERE cxc_pago.idNotaCargo = cxc_nd.idNotaCargo
																				AND cxc_pago.estatus = 1), 0)
							WHERE idNotaCargo = %s;",
								valTpDato($idNotaCargo, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
							// ACTUALIZA EL ESTATUS DE LA NOTA DE CARGO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							} else if (in_array($idCaja,array(2))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
								// ACTUALIZA EL ESTADO DEL PAGO
								$udpateSQL = sprintf("UPDATE sa_iv_pagos SET estatus = 1 WHERE idPago = %s;",
									valTpDato($rowPago['idPago'], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($udpateSQL);
								if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
							// ACTUALIZA EL SALDO DE LA FACTURA DEPENDIENDO DE SUS PAGOS
							$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
								saldoFactura = saldoFactura - IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
																		WHERE cxc_pago.id_factura = cxc_fact.idFactura
																			AND cxc_pago.estatus = 1), 0)
							WHERE idFactura = %s;",
								valTpDato($idFactura, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
							// ACTUALIZA EL SALDO DE LA NOTA DE CARGO
							$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
								saldoNotaCargo = IFNULL(cxc_nd.subtotalNotaCargo, 0)
													- IFNULL(cxc_nd.descuentoNotaCargo, 0)
													+ IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
													+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)
							WHERE idNotaCargo = %s;",
								valTpDato($idNotaCargo, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
							// ACTUALIZA EL SALDO DE LA NOTA DE CARGO DEPENDIENDO DE SUS PAGOS
							$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
								saldoNotaCargo = saldoNotaCargo - IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
																			WHERE cxc_pago.idNotaCargo = cxc_nd.idNotaCargo
																				AND cxc_pago.estatus = 1), 0)
							WHERE idNotaCargo = %s;",
								valTpDato($idNotaCargo, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
							// ACTUALIZA EL ESTATUS DE LA NOTA DE CARGO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
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
							if (!$Result1) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						}
						
					}//fin else cierre == 0 
				}//fin while todos los pagos anticipo
			}//fin if estado anticipo = 3			
						
			$objResponse->script(sprintf("verVentana('reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s',960,550)", $idReporteImpresion));
			
		}//fin foreach de anticipos agregados
	
	}//fin else tipo transferencia 2 y 3

	$objResponse->alert("Transferencia guardada correctamente");		
	$objResponse->script(sprintf("window.location.href='cj_transferencia_list.php';"));	
	$objResponse->script(sprintf("verVentana('reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=6&id=%s',960,550)", $idTransferencia));
	
	mysql_query("COMMIT;");
	
	// MODIFICADO ERNESTO
	/////////////if (function_exists("generarAnticiposVe")) { generarAnticiposVe($idAnticipo,"",""); } 
	// MODIFICADO ERNESTO
	
	return $objResponse;
}

function listadoAnticipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	
    global $spanClienteCxC;

    $objResponse = new xajaxResponse();

    $valCadBusq = explode("|", $valBusq);
    $startRow = $pageNum * $maxRows;

    //$valCadBusq[0] = criterio busqueda anticipo
    //$valCadBusq[1] = cadena id anticipos ya cargados
    //$valCadBusq[2] = id tipo de transferencia list
    //$valCadBusq[3] = id cliente

    $idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

    if($valCadBusq[2] == 2){
		$idConcepto = "6";//6 = bono suplidor
    }elseif($valCadBusq[2] == 3){
		$idConcepto = "7,8";//7 = PND Seguro 8 = PND Garantia Extendida
    }else{
		return $objResponse->alert("Debe seleccionar tipo de transferencia Bono Suplidor o PND para agregar anticipos");
    }

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
            valTpDato($idEmpresa, "int"),
            valTpDato($idEmpresa, "int"),
            valTpDato($idEmpresa, "int"),
            valTpDato($idEmpresa, "int"));	

    $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";	
    $sqlBusq .= $cond.sprintf("cxc_ant.estadoAnticipo IN (0,4) AND cxc_ant.estatus = 1 AND concepto_forma_pago.id_concepto IN (%s)",
    $idConcepto);//estadoAnticipo (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))

    if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";		
            $sqlBusq .= $cond.sprintf(" (cxc_ant.numeroAnticipo = %s 
						OR cxc_ant.observacionesAnticipo LIKE %s 
						OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s 
						OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s) ",
                    valTpDato($valCadBusq[0], "int"),
                    valTpDato($valCadBusq[0], "text"),
                    valTpDato("%".$valCadBusq[0]."%", "text"),
                    valTpDato("%".$valCadBusq[0]."%", "text"));
    }

    if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf(" cxc_ant.idAnticipo NOT IN (%s) ",
                                    $valCadBusq[1]);
    }

   /*if($valCadBusq[2] == 3 && $valCadBusq[3] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf(" cj_cc_anticipo.idCliente = %s ",
                                    $valCadBusq[3]);
    }*/

    $query = sprintf("SELECT
            cxc_ant.idAnticipo AS idDocumento,
            (cxc_ant.montoNetoAnticipo - cxc_ant.totalPagadoAnticipo) AS saldoDocumento,
            cxc_ant.numeroAnticipo AS numeroDocumento,
            cxc_ant.fechaAnticipo AS fechaDocumento,
            cxc_ant.observacionesAnticipo AS observacionDocumento,
            CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
            CONCAT_WS('-', cliente.lci, cliente.ci) as lci_ci,
			
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
			
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
				AND cxc_pago.id_forma_pago IN (11)
			 ) AS descripcion_concepto_forma_pago
    FROM
            cj_cc_anticipo cxc_ant
    INNER JOIN cj_cc_anticipo_concepto concepto_forma_pago ON cxc_ant.idAnticipo = concepto_forma_pago.id_anticipo
    INNER JOIN cj_cc_cliente cliente ON cxc_ant.idCliente = cliente.id
            %s", $sqlBusq);


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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "5%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Documento"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "8%", $pageNum, "fechaDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "12%", $pageNum, "lci_ci", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "30%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "75%", $pageNum, "observacionDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Observaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "10%", $pageNum, "estadoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Anticipo");
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "10%", $pageNum, "descripcion_concepto_forma_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Concepto");
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "5%", $pageNum, "montoNetoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Monto"));
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++;
			
			switch($row['estadoAnticipo']) {
				case "" : $class = "class=\"divMsjInfo5\""; break;
				case 0 : $class = "class=\"divMsjError\""; break;
				case 1 : $class = "class=\"divMsjInfo\""; break;
				case 2 : $class = "class=\"divMsjAlerta\""; break;
				case 3 : $class = "class=\"divMsjInfo3\""; break;
				case 4 : $class = "class=\"divMsjInfo4\""; break;
			}

            $htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
                    $htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_cargarSaldoDocumento('".$row['idDocumento']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['numeroDocumento']."</td>";
                    $htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaDocumento']))."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['lci_ci']."</td>";
                    $htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente'])."</td>";			
                    $htmlTb .= "<td align=\"left\">".utf8_decode($row['observacionDocumento'])."</td>";
					$htmlTb .= "<td align=\"center\" ".$class.">".utf8_encode($row['descripcion_estado_anticipo'])."</td>";
					$htmlTb .= "<td align=\"center\"><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</span></td>";
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum > 0) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"100\">";

                                                    $htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s)\">",
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum < $totalPages) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
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
            $htmlTb .= "<td colspan=\"15\">";
                    $htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
                    $htmlTb .= "<tr>";
                            $htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
                            $htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
                    $htmlTb .= "</tr>";
                    $htmlTb .= "</table>";
            $htmlTb .= "</td>";
    }

    $objResponse->assign("tdListadoAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

    $objResponse->script("
    if (byId('divFlotante2').style.display == 'none') {
            byId('divFlotante2').style.display = '';
            centrarDiv(byId('divFlotante2'));
			openImg(nomObjeto);
            document.forms['frmBuscarAnticipo'].reset();
            byId('txtCriterioAnticipo').focus();
    }");

    return $objResponse;
}

function listadoClientes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
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
    if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
    if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            $totalRows = mysql_num_rows($rs);
    }
    $totalPages = ceil($totalRows/$maxRows)-1;

    $htmlTblIni .= "<table border=\"0\" width=\"100%\">";
    $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
            $htmlTh .= "<td></td>";
            $htmlTh .= ordenarCampo("xajax_listadoClientes", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
            $htmlTh .= ordenarCampo("xajax_listadoClientes", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
            $htmlTh .= ordenarCampo("xajax_listadoClientes", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
            $htmlTh .= ordenarCampo("xajax_listadoClientes", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++;

            $htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
                    $htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum > 0) { 
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"100\">";

                                                    $htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoClientes(%s,'%s','%s','%s',%s)\">",
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum < $totalPages) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
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
    byId('trBuscarCliente').style.display = '';
    byId('tblListados').style.display = '';
    ");

    $objResponse->assign("tdFlotanteTitulo","innerHTML","Listar");
    $objResponse->assign("tblListados","width","700");
    $objResponse->script("
    if (byId('divFlotante').style.display == 'none') {
            byId('divFlotante').style.display = '';
            centrarDiv(byId('divFlotante'));

            document.forms['frmBuscarCliente'].reset();
            byId('txtCriterioBusqCliente').focus();
            byId('txtCriterioBusqCliente').select();
    }
    ");

    return $objResponse;
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

function validarAperturaCajaXajax($idEmpresa = false){
    $objResponse = new xajaxResponse();
    
    if($idEmpresa == false){
        $idEmpresa = $_SESSION["idEmpresaUsuarioSysGts"];
    }
    
    $Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
    if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
        
}

function opcionBonoSuplidor(){//si es puerto rico, permitir cambio y uso de tipo de transferencia suplidor
    $objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
    $queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
                                                    INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
                                    WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
    valTpDato($_SESSION["idEmpresaUsuarioSysGts"], "int"));
    $rsConfig403 = mysql_query($queryConfig403);
    if (!$rsConfig403) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

    $rowConfig403 = mysql_fetch_assoc($rsConfig403);
	$html = "";
    if($rowConfig403['valor'] == "3"){//puerto rico
		$html.= "<select id=\"lstTipoTransferencia\" name=\"lstTipoTransferencia\" onchange=\"cambioTipoTransferencia(this.value);\" class=\"inputHabilitado\">";
        	$html.= "<option value=\"\">[ Seleccione ]</option>";
            $html.= "<option value=\"1\">Cliente</option>";
            $html.= "<option value=\"2\">Bono Suplidor</option>";
            $html.= "<option value=\"3\">PND</option>";
        $html.= "</select>";
    }else{
		$html.= "<select id=\"lstTipoTransferencia\" name=\"lstTipoTransferencia\" class=\"inputHabilitado\">";
            $html.= "<option value=\"1\">Cliente</option>";
        $html.= "</select>";
    }
	
	$objResponse->assign("tdTipoTransferenciaList","innerHTML", $html);

    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarFecha");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargarCuentasCompania");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"cargarPago");
$xajax->register(XAJAX_FUNCTION,"calcularTotal");
$xajax->register(XAJAX_FUNCTION,"guardarTransferencia");
$xajax->register(XAJAX_FUNCTION,"listadoAnticipo");
$xajax->register(XAJAX_FUNCTION,"listadoClientes");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCajaXajax");
$xajax->register(XAJAX_FUNCTION,"opcionBonoSuplidor");

function errorCargarPago($objResponse){
	$objResponse->script("
	byId('btnGuardarPago').disabled = false;");
}

function nombreBanco($idBanco){
    $query = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s",$idBanco);
    $rsQuery = mysql_query($query) or die(mysql_error()." Linea:".__LINE__);
    $rowQuery = mysql_fetch_array($rsQuery);

    return $rowQuery['nombreBanco'];
}

function numeroCuenta($idCuenta){
    $sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",$idCuenta);
    $rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta) or die(mysql_error()." Linea:".__LINE__);
    $rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);

    return $rowBuscarNumeroCuenta['numeroCuentaCompania'];
}
?>