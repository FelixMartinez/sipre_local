<?php


function asignarAnticipo($idAnticipo, $nombreObjeto) {
	$objResponse = new xajaxResponse();
	
	$queryDcto = sprintf("SELECT 
		anticipo.id_anticipo,
		anticipo.fechaanticipo,
		anticipo.numeroAnticipo,
		prov.nombre,
		anticipo.total,
		anticipo.saldoanticipo,
		anticipo.estado
	FROM cp_anticipo anticipo
		INNER JOIN cp_proveedor prov ON (anticipo.id_proveedor = prov.id_proveedor)
	WHERE anticipo.id_anticipo = %s",
		valTpDato($idAnticipo,"int"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$idAnticipo);
	$objResponse->assign("txtNumero".$nombreObjeto,"value",$rowDcto['numeroAnticipo']);
	$objResponse->assign("txtFecha".$nombreObjeto,"value",date("d-m-Y", strtotime($rowDcto['fechaanticipo'])));
	$objResponse->assign("txtEstado".$nombreObjeto,"value",$rowDcto['estado']);
	$objResponse->assign("txtTotal".$nombreObjeto,"value",number_format($rowDcto['total'], 2, ".", ","));
	$objResponse->assign("txtSaldo".$nombreObjeto,"value",number_format($rowDcto['saldoanticipo'], 2, ".", ","));
	
	$objResponse->script("
	byId('btnCancelarLista".$nombreObjeto."').click();");
	
	return $objResponse;
}

function asignarDepartamento($frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = $frmDcto['lstModulo'];
	
	if ($frmDcto['cbxNroAutomatico'] == 1) {
		$objResponse->script("
		byId('txtNumeroNotaCargo').readOnly = true;
		byId('txtNumeroNotaCargo').className = 'inputInicial';");
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			$objResponse->assign("tdtxtFechaProveedor","innerHTML","<input type=\"text\" id=\"txtFechaProveedor\" name=\"txtFechaProveedor\" size=\"10\" style=\"text-align:center\" value=\"".date("d-m-Y")."\"/>");
			
			$objResponse->script("
			byId('txtNumeroControl').readOnly = false;
			byId('txtNumeroControl').className = 'inputHabilitado';
			byId('txtFechaProveedor').readOnly = true;
			byId('txtFechaProveedor').className = 'inputInicial';");
		} else {
			$objResponse->script("
			byId('txtNumeroControl').readOnly = true;
			byId('txtNumeroControl').className = 'inputInicial';
			byId('txtFechaProveedor').readOnly = false;
			byId('txtFechaProveedor').className = 'inputHabilitado';
			
			jQuery(function($){
				$('#txtFechaProveedor').maskInput('99-99-9999',{placeholder:' '});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFechaProveedor\",
				dateFormat:\"%d-%m-%Y\",
				cellColorScheme:\"torqoise\"
			});");
			
			$objResponse->assign("txtNumeroNotaCargo","value","");
			$objResponse->assign("txtNumeroControl","value","");
			$objResponse->assign("txtFechaProveedor","value","");
		}
	} else {
		$objResponse->script("
		byId('txtNumeroNotaCargo').readOnly = false;
		byId('txtNumeroNotaCargo').className = 'inputHabilitado';
		byId('txtNumeroControl').readOnly = false;
		byId('txtNumeroControl').className = 'inputHabilitado';");
		
		$objResponse->assign("txtNumeroControl","value","");
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			$objResponse->assign("tdtxtFechaProveedor","innerHTML","<input type=\"text\" id=\"txtFechaProveedor\" name=\"txtFechaProveedor\" size=\"10\" style=\"text-align:center\" value=\"".date("d-m-Y")."\"/>");
			
			$objResponse->script("
			byId('txtFechaProveedor').readOnly = true;
			byId('txtFechaProveedor').className = 'inputInicial';");
		} else {
			$objResponse->script("
			byId('txtFechaProveedor').readOnly = false;
			byId('txtFechaProveedor').className = 'inputHabilitado';
			
			jQuery(function($){
				$('#txtFechaProveedor').maskInput('99-99-9999',{placeholder:' '});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFechaProveedor\",
				dateFormat:\"%d-%m-%Y\",
				cellColorScheme:\"torqoise\"
			});");
			
			$objResponse->assign("txtFechaProveedor","value","");
		}
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

function asignarFechaRegistro($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Nota de Débito de CxP)
	$queryConfig404 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 404 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig404 = mysql_query($queryConfig404);
	if (!$rsConfig404) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig404 = mysql_num_rows($rsConfig404);
	$rowConfig404 = mysql_fetch_assoc($rsConfig404);
	
	$valor = explode("|",$rowConfig404['valor']);
	
	$txtFechaProveedor = explode("-",$frmDcto['txtFechaProveedor']);
	if ($txtFechaProveedor[1] > 0 && $txtFechaProveedor[0] > 0 && $txtFechaProveedor[2] > 0) {
		if (checkdate($txtFechaProveedor[1], $txtFechaProveedor[0], $txtFechaProveedor[2])) { // EVALUA QUE LA FECHA EXISTA
			$txtFechaRegistroCompra = date("d-m-Y");
			$txtFechaProveedor = $frmDcto['txtFechaProveedor'];
			if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
				if ((date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date("d-m-Y"))))
					&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date("d-m-Y")))))
				|| restaFechas("d-m-Y", $txtFechaProveedor, date("d-m-Y"), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
					if (restaFechas("d-m-Y", date("01-m-Y"), date("d-m-Y"), "dias") <= $valor[1]
					|| date("m",strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
						$txtFechaRegistroCompra = $txtFechaProveedor;
					} else {
						$objResponse->script("byId('cbxFechaRegistro').checked = false;");
						$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
					}
				} else if (!(date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date("d-m-Y"))))
					&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date("d-m-Y")))))
				|| restaFechas("d-m-Y", $txtFechaProveedor, date("d-m-Y"), "meses") > $valor[2]) {
					$objResponse->script("byId('cbxFechaRegistro').checked = false;");
					$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
				} else {
					$txtFechaRegistroCompra = $txtFechaProveedor;
				}
			} else if ($frmDcto['cbxFechaRegistro'] == 1) {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
			}
			
			$objResponse->assign("txtFechaRegistroCompra","value",$txtFechaRegistroCompra);
		} else {
			$objResponse->assign("txtFechaProveedor","value","");
		}
	} else {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
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
	
	if (strtoupper($rowProv['credito']) == "SI" || $rowProv['credito'] == 1) {
		$queryProvCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s;",
			valTpDato($rowProv['id_proveedor'], "int"));
		$rsProvCredito = mysql_query($queryProvCredito);
		if (!$rsProvCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProvCredito = mysql_fetch_assoc($rsProvCredito);
		
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value",$rowProvCredito['diascredito']);
		
		$objResponse->call("selectedOption","lstTipoPago",1);
	} else {
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value","0");
		
		$objResponse->call("selectedOption","lstTipoPago",0);
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaProveedor').click();");
	}
	
	return $objResponse;
}

function asignarMetodoPago($idMetodoPago){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('fieldsetTransferencia').style.display = 'none';
	byId('fieldsetCheque').style.display = 'none';
	byId('fieldsetAnticipo').style.display = 'none';
	byId('fieldsetNotaCredito').style.display = 'none';");
	
	switch($idMetodoPago) {
		case 1 : $objResponse->script("byId('fieldsetTransferencia').style.display = '';"); break;
		case 2 : $objResponse->script("byId('fieldsetCheque').style.display = '';"); break;
		case 3 : $objResponse->script("byId('fieldsetAnticipo').style.display = '';"); break;
		case 4 : $objResponse->script("byId('fieldsetNotaCredito').style.display = '';"); break;
	}
	
	return $objResponse;
}

function asignarMotivo($idMotivo, $nombreObjeto, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_motivo
	WHERE id_motivo = %s
		AND modulo LIKE 'CP'
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

function asignarNotaCredito($idNotaCredito, $nombreObjeto) {
	$objResponse = new xajaxResponse();
	
	$queryDcto = sprintf("SELECT 
		nota_cred.id_notacredito,
		nota_cred.fecha_notacredito,
		nota_cred.numero_nota_credito,
		prov.nombre,
		
		(IFNULL(nota_cred.subtotal_notacredito, 0)
			- IFNULL(nota_cred.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
					FROM cp_notacredito_gastos nota_cred_gasto
					WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito), 0)
			+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
					FROM cp_notacredito_iva nota_cred_iva
					WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
		) AS total,
		
		nota_cred.saldo_notacredito,
		nota_cred.estado_notacredito
	FROM cp_notacredito nota_cred
		INNER JOIN cp_proveedor prov ON (nota_cred.id_proveedor = prov.id_proveedor)
	WHERE nota_cred.id_notacredito = %s",
		valTpDato($idNotaCredito,"int"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$idNotaCredito);
	$objResponse->assign("txtNumero".$nombreObjeto,"value",$rowDcto['numero_nota_credito']);
	$objResponse->assign("txtFecha".$nombreObjeto,"value",date("d-m-Y", strtotime($rowDcto['fecha_notacredito'])));
	$objResponse->assign("txtEstado".$nombreObjeto,"value",$rowDcto['estado_notacredito']);
	$objResponse->assign("txtTotal".$nombreObjeto,"value",number_format($rowDcto['total'], 2, ".", ","));
	$objResponse->assign("txtSaldo".$nombreObjeto,"value",number_format($rowDcto['saldo_notacredito'], 2, ".", ","));
	
	$objResponse->script("
	byId('btnCancelarLista".$nombreObjeto."').click();");
	
	return $objResponse;
}

function buscarAnticipo($frmBuscarAnticipo, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarAnticipo['hddObjDestinoAnticipo'],
		$frmDcto['txtIdProv'],
		$frmBuscarAnticipo['txtCriterioBuscarAnticipo']);
	
	$objResponse->loadCommands(listaAnticipo(0, "numeroAnticipo", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
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

function buscarNotaCredito($frmBuscarNotaCredito, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarNotaCredito['hddObjDestinoNotaCredito'],
		$frmDcto['txtIdEmpresa'],
		$frmDcto['txtIdProv'],
		$frmBuscarNotaCredito['txtCriterioBuscarNotaCredito']);
	
	$objResponse->loadCommands(listaNotaCredito(0, "numero_nota_credito", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarProveedor['hddObjDestinoProveedor'],
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx'];
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valorItm,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valorItm,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// SUMA LOS PAGOS
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalPago += ($frmListaPagoDcto['hddEstatusPago'.$valor] == 1) ? str_replace(",","",$frmListaPagoDcto['txtMontoPago'.$valor]) : 0;
		}
	}
	
	$txtSubTotal = round(str_replace(",", "", $frmTotalDcto['txtSubTotal']),2);
	$txtDescuento = round(str_replace(",", "", $frmTotalDcto['txtDescuento']),2);
	$txtSubTotalDescuento = round(str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']),2);
	$txtTotalExento = round(str_replace(",", "", $frmTotalDcto['txtTotalExento']),2);
	$txtTotalExonerado = round(str_replace(",", "", $frmTotalDcto['txtTotalExonerado']),2);
	
	if ($subTotalDescuentoItm > 0) {
		$txtDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
	
	if (isset($frmTotalDcto['cbxIva'])) {
		foreach ($frmTotalDcto['cbxIva'] as $indice => $valor) {
			// BUSCA EL IVA DE VENTA POR DEFECTO PARA CALCULAR EL EXENTO
			$query = sprintf("SELECT * FROM pg_iva WHERE estado = 1 AND tipo IN (1) AND activo = 1 AND idIva = %s ORDER BY iva",
				valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBaseImpIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
			
			$txtIva = str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
			$txtSubTotalIva = $txtBaseImpIva * $txtIva / 100;
			
			$objResponse->assign("txtSubTotalIva".$valor,"value",number_format($txtSubTotalIva, 2, ".", ","));
			
			$totalSubtotalIva += round($txtSubTotalIva, 2);
			
			// BUSCA LA BASE IMPONIBLE MAYOR
			if ($totalRows > 0 && $txtBaseImpIva > 0) {
				$txtBaseImpIvaVenta = $txtBaseImpIva;
			}
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = true;
			byId('txtDescuento').className = 'inputInicial';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = true;
			byId('txtSubTotalDescuento').className = 'inputInicial';");
		}
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = false;
			byId('txtDescuento').className = 'inputHabilitado';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = false;
			byId('txtSubTotalDescuento').className = 'inputHabilitado';");
		}
	}
	$txtDescuento = ($txtDescuento > 0) ? $txtDescuento : 0;
	$txtSubTotalDescuento = ($txtSubTotalDescuento > 0) ? $txtSubTotalDescuento : 0;
	
	$txtTotalOrden = $txtSubTotal - $txtSubTotalDescuento + $totalSubtotalIva;
	$txtTotalExento = $txtSubTotal - $txtSubTotalDescuento - $txtBaseImpIvaVenta;
	
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalPago","value",number_format($txtTotalPago, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstBanco($selId = "", $nombreObjeto = "", $onChange = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM bancos ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCuenta($idBanco, $selId = "", $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM cuentas WHERE idBanco = %s ORDER BY numeroCuentaCompania ASC",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idCuentas']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".utf8_encode($row['numeroCuentaCompania'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
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

function eliminarMetodoPago($frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaPagoDcto['cbxItm'])) {
		foreach ($frmListaPagoDcto['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function formNotaCargo($idNotaCargo, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idNotaCargo > 0) {
		$objResponse->script("
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('aListarProv').style.display = 'none';
		byId('txtIdProv').readOnly = true;
		byId('txtIdProv').className = 'inputInicial';
		byId('txtNumeroNotaCargo').readOnly = true;
		byId('txtNumeroNotaCargo').className = 'inputInicial';
		byId('lblNroAutomatico').style.display = 'none';
		byId('txtNumeroControl').readOnly = true;
		byId('txtNumeroControl').className = 'inputInicial';
		byId('txtFechaProveedor').readOnly = true;
		byId('txtFechaProveedor').className = 'inputInicial';
		byId('lblFechaRegistro').style.display = 'none';
		byId('lstTipoPago').className = 'inputInicial';
		byId('lstAplicaLibro').className = 'inputInicial';
		byId('aListarMotivo').style.display = 'none';
		byId('txtIdMotivo').readOnly = true;
		byId('txtIdMotivo').className = 'inputInicial';
		byId('txtObservacion').readOnly = true;
		byId('txtObservacion').className = 'inputInicial';
		
		byId('btnNotaCargoPDF').style.display = 'none';
		
		byId('txtSubTotal').readOnly = true;
		byId('txtSubTotal').className = 'inputSinFondo';
		byId('txtSubTotal').onblur = function() {
			setFormatoRafk(this,2);
		}
		byId('txtDescuento').readOnly = true;
		byId('txtDescuento').onblur = function() {
			setFormatoRafk(this,2);
		}
		byId('txtTotalExento').readOnly = true;
		byId('txtTotalExento').className = 'inputSinFondo';
		byId('txtTotalExento').onblur = function() {
			setFormatoRafk(this,2);
		}
		byId('txtTotalExonerado').readOnly = true;
		byId('txtTotalExonerado').className = 'inputSinFondo';
		byId('txtTotalExonerado').onblur = function() {
			setFormatoRafk(this,2);
		}
		
		byId('trBtnListaPagoDcto').style.display = 'none';
		byId('trListaPagoDcto').style.display = '';
		
		byId('btnGuardar').style.display = 'none';
		
		byId('fieldsetPlanMayor').style.display = 'none';");
		
		// BUSCA LOS DATOS DE LA NOTA DE CARGO
		$queryNotaCargo = sprintf("SELECT nota_cargo.*,
			(CASE nota_cargo.estatus_notacargo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_nota_cargo
		FROM cp_notadecargo nota_cargo
		WHERE id_notacargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsNotaCargo = mysql_query($queryNotaCargo);
		if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);
		
		// BUSCA LOS DATOS DE LA FACTURA POR PLAN MAYOR
		$queryFactura = sprintf("SELECT
			fact_comp.id_factura,
			fact_comp.numero_factura_proveedor,
			fact_comp.numero_control_factura,
			fact_comp.fecha_origen,
			fact_comp.fecha_factura_proveedor,
			fact_comp.estatus_factura,
			(CASE fact_comp.estatus_factura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_factura,
			(CASE fact_comp.tipo_pago
				WHEN 0 THEN 'Contado'
				WHEN 1 THEN 'Crédito'
			END) AS tipo_pago_factura,
			fact_comp.saldo_factura,
			modulo.descripcionModulo,
			(CASE fact_comp.aplica_libros
				WHEN 0 THEN 'NO'
				WHEN 1 THEN 'SI'
			END) AS aplica_libros_factura
		FROM cp_notadecargo nota_cargo
			INNER JOIN an_unidad_fisica uni_fis ON (nota_cargo.id_detalles_pedido_compra = uni_fis.id_pedido_compra_detalle)
			INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
			INNER JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
			INNER JOIN pg_modulos modulo ON (fact_comp.id_modulo = modulo.id_modulo)
		WHERE nota_cargo.id_notacargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFactura = mysql_num_rows($rsFactura);
		$rowFactura = mysql_fetch_assoc($rsFactura);
		
		if ($totalRowsFactura > 0) {
			$objResponse->script("byId('fieldsetPlanMayor').style.display = '';");
		}
		
		if ($rowNotaCargo['saldo_notacargo'] > 0 && $_GET['vw'] != "v" && $_GET['vw'] != "e") {
			$objResponse->script("
			byId('trBtnListaPagoDcto').style.display = '';
			
			byId('btnGuardar').style.display = '';");
		}
		
		switch($rowNotaCargo['estatus_notacargo']) {
			case 0 : $claseEstatus = "divMsjError"; break;
			case 1 : $claseEstatus = "divMsjInfo"; break;
			case 2 : $claseEstatus = "divMsjAlerta"; break;
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowNotaCargo['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarProveedor($rowNotaCargo['id_proveedor'], "Prov", "false"));
		$objResponse->loadCommands(asignarEmpleado($rowNotaCargo['id_empleado_creador']));
		
		$objResponse->assign("txtFechaRegistroCompra","value",date("d-m-Y",strtotime($rowNotaCargo['fecha_origen_notacargo'])));
		$objResponse->assign("txtIdNotaCargo","value",$rowNotaCargo['id_notacargo']);
		$objResponse->assign("txtNumeroNotaCargo","value",$rowNotaCargo['numero_notacargo']);
		$objResponse->assign("txtNumeroControl","value",$rowNotaCargo['numero_control_notacargo']);
		$objResponse->assign("txtFechaProveedor","value",date("d-m-Y",strtotime($rowNotaCargo['fecha_notacargo'])));
		$objResponse->call("selectedOption","lstTipoPago",$rowNotaCargo['tipo_pago_notacargo']);
		$objResponse->loadCommands(cargaLstModulo($rowNotaCargo['id_modulo'], true));
		$objResponse->call("selectedOption","lstAplicaLibro",$rowNotaCargo['aplica_libros_notacargo']);
		$objResponse->loadCommands(asignarMotivo($rowNotaCargo['id_motivo'],"Motivo"));
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $claseEstatus));
		$objResponse->assign("txtEstatus","value",$rowNotaCargo['estado_nota_cargo']);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowNotaCargo['observacion_notacargo']));
		
		$objResponse->script("
		byId('lstAplicaLibro').onchange = function() {
			selectedOption(this.id,'".$rowNotaCargo['aplica_libros_notacargo']."');
		}");
		
		$objResponse->script("
		byId('lstTipoPago').onchange = function() {
			selectedOption(this.id,'".$rowNotaCargo['tipo_pago_notacargo']."');
		}");
		
		$objResponse->script("
		byId('btnNotaCargoPDF').style.display = '';
		byId('btnNotaCargoPDF').onclick = function() {
			verVentana('reportes/cp_nota_cargo_pdf.php?valBusq=".$rowNotaCargo['id_notacargo']."', 960, 550);
		}");
		
		// ASIGNA LOS DATOS DE LA FACTURA
		switch($rowFactura['estatus_factura']) {
			case 0 : $claseEstatus = "divMsjError"; break;
			case 1 : $claseEstatus = "divMsjInfo"; break;
			case 2 : $claseEstatus = "divMsjAlerta"; break;
		}
		
		$objResponse->assign("txtIdFactura","value",$rowFactura['id_factura']);
		$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
		$objResponse->assign("txtNumeroControlFactura","value",$rowFactura['numero_control_factura']);
		$objResponse->assign("txtFechaRegistroFactura","value",date("d-m-Y", strtotime($rowFactura['fecha_origen'])));
		$objResponse->assign("txtFechaFactura","value",date("d-m-Y", strtotime($rowFactura['fecha_factura_proveedor'])));
		$objResponse->assign("txtTipoPago","value",$rowFactura['tipo_pago_factura']);
		$objResponse->assign("txtModulo","value",$rowFactura['descripcionModulo']);
		$objResponse->assign("txtAplicaLibro","value",$rowFactura['aplica_libros_factura']);
		/*$objResponse->assign("txtIdMotivo","value",$rowFactura['id_motivo']);
		$objResponse->assign("txtMotivo","value",$rowFactura['descripcion']);*/
		$objResponse->script(sprintf("byId('tdtxtEstatusFactura').className = '%s';", $claseEstatus));
		$objResponse->assign("txtEstatusFactura","value",$rowFactura['estado_factura']);
		
		$objResponse->script(sprintf("byId('aVerFactura').href = 'cp_factura_form.php?id=%s&vw=v';", $rowFactura['id_factura']));
		
		// CARGA LOS IMPUESTOS
		$queryIva = sprintf("SELECT
			nota_cargo_iva.id_notacarg_iva,
			nota_cargo_iva.id_notacargo,
			nota_cargo_iva.baseimponible,
			nota_cargo_iva.subtotal_iva,
			nota_cargo_iva.id_iva,
			nota_cargo_iva.iva,
			iva.observacion
		FROM cp_notacargo_iva nota_cargo_iva
			INNER JOIN pg_iva iva ON (nota_cargo_iva.id_iva = iva.idIva)
		WHERE nota_cargo_iva.id_notacargo = %s
		ORDER BY iva",
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
						"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trGastosSinIva').before(elemento);",
				$indice,
					$indice, utf8_encode($rowIva['observacion']),
						$indice, $indice, $rowIva['id_iva'],
						$indice,
					$indice, $indice, number_format(round($rowIva['baseimponible'],2), 2, ".", ","),
					$indice, $indice, $rowIva['iva'], "%",
					$indice, $indice, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
				
				$indice,
				
				$indice,
				
				$indice));
		}
		
		$porcDescuento = $rowNotaCargo['subtotal_descuento_notacargo'] * 100 / $rowNotaCargo['subtotal_notacargo'];
		$objResponse->assign("txtSubTotal","value",number_format($rowNotaCargo['subtotal_notacargo'], 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($porcDescuento, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowNotaCargo['subtotal_descuento_notacargo'], 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($rowNotaCargo['monto_exento_notacargo'], 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($rowNotaCargo['monto_exonerado_notacargo'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowNotaCargo['saldo_notacargo'], 2, ".", ","));
		
		// BUSCA LOS PAGOS DEL DOCUMENTO
		$query = sprintf("SELECT * FROM cp_pagos_documentos pago_dcto
		WHERE tipo_documento_pago LIKE 'ND'
			AND id_documento_pago = %s;",
			valTpDato($idNotaCargo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemMetodoPago($contFila, $row['id_pago']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdProv').className = 'inputHabilitado';
		byId('txtNumeroNotaCargo').className = 'inputHabilitado';
		byId('txtNumeroControl').className = 'inputHabilitado';
		byId('txtFechaProveedor').className = 'inputHabilitado';
		byId('lstTipoPago').className = 'inputHabilitado';
		byId('lstAplicaLibro').className = 'inputHabilitado';
		byId('txtIdMotivo').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('btnNotaCargoPDF').style.display = 'none';
		
		byId('txtSubTotal').className = 'inputHabilitado';
		byId('txtSubTotal').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('rbtInicialMonto').checked = true;
		
		byId('txtTotalExento').className = 'inputHabilitado';
		byId('txtTotalExento').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtTotalExonerado').className = 'inputHabilitado';
		byId('txtTotalExonerado').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('trBtnListaPagoDcto').style.display = 'none';
		byId('trListaPagoDcto').style.display = 'none';
		
		byId('fieldsetPlanMayor').style.display = 'none';");
		
		// CARGA LOS IMPUESTOS
		$queryIva = sprintf("SELECT * FROM pg_iva WHERE estado = 1 AND tipo IN (1,3) ORDER BY iva");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$indiceIva = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$indiceIva++;
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputHabilitado\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trGastosSinIva').before(elemento);
				
				byId('txtBaseImpIva%s').onblur = function() {
					setFormatoRafk(this,2);
					xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
				}
				byId('txtBaseImpIva%s').onkeypress = function(e) {
					return validarSoloNumerosReales(e);
				}",
				$indiceIva,
					$indiceIva, utf8_encode($rowIva['observacion']),
						$indiceIva, $indiceIva, $rowIva['idIva'],
						$indiceIva,
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
					$indiceIva, $indiceIva, $rowIva['iva'], "%",
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
				
				$indiceIva,
				
				$indiceIva,
				
				$indiceIva));
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
		$objResponse->assign("txtFechaRegistroCompra","value",date("d-m-Y"));
		$objResponse->loadCommands(cargaLstModulo());
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts']));
		$objResponse->assign("txtSubTotal","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format(0, 2, ".", ","));
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
		
		$objResponse->script("
		jQuery(function($){
			$('#txtFechaProveedor').maskInput('99-99-9999',{placeholder:' '});
		});
		
		new JsDatePick({
			useMode:2,
			target:\"txtFechaProveedor\",
			dateFormat:\"%d-%m-%Y\",
			cellColorScheme:\"torqoise\"
		});");
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx'];
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idNotaCargo = $frmDcto['txtIdNotaCargo'];
	
	mysql_query("START TRANSACTION;");
	
	if ($idNotaCargo > 0) {
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmListaPagoDcto['hddIdPago'.$valor] == 0) {
					$insertSQL = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
					VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
						valTpDato('ND', "text"),
						valTpDato($frmListaPagoDcto['txtMetodoPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "text"),
						valTpDato(date("Y-m-d", strtotime($frmListaPagoDcto['txtFechaPago'.$valor])), "text"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($frmListaPagoDcto['txtNumeroDctoPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtBancoProveedorPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtBancoCompaniaPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtCuentaProveedorPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtCuentaCompaniaPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtMontoPago'.$valor], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					switch ($frmListaPagoDcto['txtMetodoPago'.$valor]) {
						case "Transferencia" :
							break;
						case "Cheque" :
							break;
						case "AN" :
							// VERIFICA SI EL SALDO DEL ANTICIPO ES MAYOR AL MONTO
							$query = sprintf("SELECT * FROM cp_anticipo
							WHERE id_anticipo = %s
								AND saldoanticipo >= %s;",
								valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"),
								valTpDato($frmListaPagoDcto['txtMontoPago'.$valor], "real_inglesa"));
							$rs = mysql_query($query);
							if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRows = mysql_num_rows($rs);
							$row = mysql_fetch_assoc($rs);
							
							if ($totalRows > 0) {
								// ACTUALIZA EL SALDO DEL ANTICIPO
								$updateSQL = sprintf("UPDATE cp_anticipo SET
									saldoanticipo = saldoanticipo - %s
								WHERE id_anticipo = %s;",
									valTpDato($frmListaPagoDcto['txtMontoPago'.$valor], "real_inglesa"),
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
								
								// ACTUALIZA EL ESTATUS DEL ANTICIPO
								$updateSQL = sprintf("UPDATE cp_anticipo SET
									estado = (CASE
													WHEN saldoanticipo = 0 THEN	3
													WHEN saldoanticipo > 0 THEN	2
												END)
								WHERE id_anticipo = %s;",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
							} else {
								return $objResponse->alert("No posee saldo suficiente en el Anticipo, elimine dicho pago y agréguelo nuevamente");
							}
							break;
						case "NC" :
							// VERIFICA SI EL SALDO DE LA NOTA DE CREDITO ES MAYOR AL MONTO
							$query = sprintf("SELECT * FROM cp_notacredito
							WHERE id_notacredito = %s
								AND saldo_notacredito >= %s;",
								valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"),
								valTpDato($frmListaPagoDcto['txtMontoPago'.$valor], "real_inglesa"));
							$rs = mysql_query($query);
							if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRows = mysql_num_rows($rs);
							$row = mysql_fetch_assoc($rs);
							
							if ($totalRows > 0) {
								// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
								$updateSQL = sprintf("UPDATE cp_notacredito nota_credito SET
									saldo_notacredito = (IFNULL(nota_credito.subtotal_notacredito, 0)
														- IFNULL(nota_credito.subtotal_descuento, 0)
														+ IFNULL((SELECT SUM(nota_credito_gasto.monto_gasto_notacredito) AS total_gasto
																FROM cp_notacredito_gastos nota_credito_gasto
																WHERE nota_credito_gasto.id_notacredito = nota_credito.id_notacredito), 0)
														+ IFNULL((SELECT SUM(nota_credito_iva.subtotal_iva_notacredito) AS total_iva
																FROM cp_notacredito_iva nota_credito_iva
																WHERE nota_credito_iva.id_notacredito = nota_credito.id_notacredito), 0)
														) - IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
															WHERE ((tipo_pago LIKE 'NC' AND id_documento = nota_credito.id_notacredito)
																	OR (tipo_documento_pago LIKE 'NC' AND id_documento_pago = nota_credito.id_notacredito))
																AND pago_dcto.estatus = 1), 0)
								WHERE id_notacredito = %s
									AND estado_notacredito NOT IN (3);",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
								
								// ACTUALIZA EL ESTATUS DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Sin Asignar, 2 = Asignado Parcial, 3 = Asignado)
								$updateSQL = sprintf("UPDATE cp_notacredito nota_credito SET
									estado_notacredito = (CASE
															WHEN (saldo_notacredito = 0) THEN
																3
															WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL(nota_credito.subtotal_notacredito, 0)
																		- IFNULL(nota_credito.subtotal_descuento, 0)
																		+ IFNULL((SELECT SUM(nota_credito_gasto.monto_gasto_notacredito) AS total_gasto
																				FROM cp_notacredito_gastos nota_credito_gasto
																				WHERE nota_credito_gasto.id_notacredito = nota_credito.id_notacredito), 0)
																		+ IFNULL((SELECT SUM(nota_credito_iva.subtotal_iva_notacredito) AS total_iva
																				FROM cp_notacredito_iva nota_credito_iva
																				WHERE nota_credito_iva.id_notacredito = nota_credito.id_notacredito), 0))) THEN
																2
															WHEN (saldo_notacredito = (IFNULL(nota_credito.subtotal_notacredito, 0)
																		- IFNULL(nota_credito.subtotal_descuento, 0)
																		+ IFNULL((SELECT SUM(nota_credito_gasto.monto_gasto_notacredito) AS total_gasto
																				FROM cp_notacredito_gastos nota_credito_gasto
																				WHERE nota_credito_gasto.id_notacredito = nota_credito.id_notacredito), 0)
																		+ IFNULL((SELECT SUM(nota_credito_iva.subtotal_iva_notacredito) AS total_iva
																				FROM cp_notacredito_iva nota_credito_iva
																				WHERE nota_credito_iva.id_notacredito = nota_credito.id_notacredito), 0))) THEN
																1
															ELSE
																0
														END)
								WHERE id_notacredito = %s;",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
								
								// VERIFICA EL SALDO DE LA NOTA DE CREDITO A VER SI ESTA NEGATIVO
								$querySaldoDcto = sprintf("SELECT cxp_nc.*,
									prov.nombre AS nombre_proveedor
								FROM cp_notacredito cxp_nc
									INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)
								WHERE cxp_nc.id_notacredito = %s
									AND cxp_nc.saldo_notacredito < 0;",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								$rsSaldoDcto = mysql_query($querySaldoDcto);
								if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
								$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
								$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
								if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Crédito Nro. ".$rowSaldoDcto['numero_nota_credito']." del Proveedor ".$rowSaldoDcto['nombre_proveedor']." presenta un saldo negativo"); }
							} else {
								return $objResponse->alert("No posee saldo suficiente en la Nota de Crédito, elimine dicho pago y agréguelo nuevamente");
							}
							break;
					}
					
					// ACTUALIZA EL SALDO DE LA NOTA DE CARGO
					$updateSQL = sprintf("UPDATE cp_notadecargo SET
						saldo_notacargo = saldo_notacargo - %s
					WHERE id_notacargo = %s;",
						valTpDato($frmListaPagoDcto['txtMontoPago'.$valor], "real_inglesa"),
						valTpDato($idNotaCargo, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL ESTATUS DE LA NOTA DE CARGO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
					$updateSQL = sprintf("UPDATE cp_notadecargo SET
						estatus_notacargo = (CASE
												WHEN (saldo_notacargo = 0 OR saldo_notacargo < 0) THEN
													1
												WHEN (saldo_notacargo > 0) THEN
													2
											END)
					WHERE id_notacargo = %s;",
						valTpDato($idNotaCargo, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// VERIFICA EL SALDO DE LA NOTA DE CARGO A VER SI ESTA NEGATIVO
					$querySaldoDcto = sprintf("SELECT cxp_nd.*,
						prov.nombre AS nombre_proveedor
					FROM cp_notadecargo cxp_nd
						INNER JOIN cp_proveedor prov ON (cxp_nd.id_proveedor = prov.id_proveedor)
					WHERE cxp_nd.id_notacargo = %s
						AND cxp_nd.saldo_notacargo < 0;",
						valTpDato($idNotaCargo, "int"));
					$rsSaldoDcto = mysql_query($querySaldoDcto);
					if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
					$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
					$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
					if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Débito Nro. ".$rowSaldoDcto['numero_notacargo']." del Proveedor ".$rowSaldoDcto['nombre_proveedor']." presenta un saldo negativo"); }
				}
			}
		}
		
		mysql_query("COMMIT;");
	} else {
		// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Nota de Débito de CxP)
		$queryConfig404 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 404 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsConfig404 = mysql_query($queryConfig404);
		if (!$rsConfig404) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsConfig404 = mysql_num_rows($rsConfig404);
		$rowConfig404 = mysql_fetch_assoc($rsConfig404);
		
		$valor = explode("|",$rowConfig404['valor']);
		
		$txtFechaRegistroCompra = date("d-m-Y");
		$txtFechaProveedor = $frmDcto['txtFechaProveedor'];
		if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
			if ((date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date("d-m-Y"))))
				&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date("d-m-Y")))))
			|| restaFechas("d-m-Y", $txtFechaProveedor, date("d-m-Y"), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
				if (restaFechas("d-m-Y", date("01-m-Y"), date("d-m-Y"), "dias") <= $valor[1]
				|| date("m",strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
					$txtFechaRegistroCompra = $txtFechaProveedor;
				} else {
					$objResponse->script("byId('cbxFechaRegistro').checked = false;");
					$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
				}
			} else if (!(date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date("d-m-Y"))))
				&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date("d-m-Y")))))
			|| restaFechas("d-m-Y", $txtFechaProveedor, date("d-m-Y"), "meses") > $valor[2]) {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
			} else {
				$txtFechaRegistroCompra = $txtFechaProveedor;
			}
		} else if ($frmDcto['cbxFechaRegistro'] == 1) {
			$objResponse->script("byId('cbxFechaRegistro').checked = false;");
			return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
		}
		
		if ($frmDcto['cbxNroAutomatico'] == 1) {
			// NUMERACION DEL DOCUMENTO
			if (in_array($idModulo,array(0,1,2,3)) && $frmDcto['lstAplicaLibro'] == 1){
				$idNumeraciones = 3; // 3 = Nota Cargo CxP
			} else {
				$idNumeraciones = 3; // 3 = Nota Cargo CxP
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
			
			if (in_array($idModulo,array(0,1,2,3)) && $frmDcto['lstAplicaLibro'] == 1){
				$numeroActualControl = $frmDcto['txtNumeroControl'];
			} else {
				$numeroActualControl = $numeroActual;
			}
		} else {
			$numeroActual = $frmDcto['txtNumeroNotaCargo'];
			$numeroActualControl = $frmDcto['txtNumeroControl'];
		}
		
		// BUSCA LOS DATOS DEL PROVEEDOR
		$queryProv = sprintf("SELECT prov.credito, prov_cred.*
		FROM cp_proveedor prov
			LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
		WHERE prov.id_proveedor = %s;",
			valTpDato($frmDcto['txtIdProv'], "int"));
		$rsProv = mysql_query($queryProv);
		if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProv = mysql_fetch_assoc($rsProv);
		
		$txtDiasCreditoProv = ($rowProv['diascredito'] > 0) ? $rowProv['diascredito'] : 0;
		
		// SI EL PROVEEDOR ES A CREDITO SE LE SUMA LA CANTIDAD DE DIAS DE CREDITO PARA LA FECHA DE VENCIMIENTO
		$fechaVencimiento = ($rowProv['credito'] == "Si") ? suma_fechas("d-m-Y",$txtFechaProveedor,$txtDiasCreditoProv) : $txtFechaProveedor;
		
		// INSERTAR LOS DATOS DE LA NOTA DE CARGO
		$insertSQL = sprintf("INSERT INTO cp_notadecargo (numero_notacargo, numero_control_notacargo, fecha_notacargo, id_proveedor, fecha_origen_notacargo, fecha_vencimiento_notacargo, id_modulo, estatus_notacargo, observacion_notacargo, tipo_pago_notacargo, monto_exento_notacargo, monto_exonerado_notacargo, subtotal_notacargo, subtotal_descuento_notacargo, total_cuenta_pagar, saldo_notacargo, aplica_libros_notacargo, id_empresa, id_motivo, id_empleado_creador)
		VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActual, "text"),
			valTpDato($numeroActualControl, "text"),
			valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
			valTpDato($frmDcto['txtIdProv'], "int"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato(date("Y-m-d", strtotime($fechaVencimiento)), "date"),
			valTpDato($frmDcto['lstModulo'], "int"),
			valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmDcto['lstTipoPago'], "int"), // 0 = Contado, 1 = Credito
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
			valTpDato($frmDcto['lstAplicaLibro'], "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($frmDcto['txtIdMotivo'], "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCargo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				if (str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]) > 0) {
					$insertSQL = sprintf("INSERT INTO cp_notacargo_iva (id_notacargo, baseimponible, subtotal_iva, id_iva, iva)
					VALUE (%s, %s, %s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
						valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("ND", "text"),
			valTpDato($idNotaCargo, "int"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		mysql_query("COMMIT;");
			
		$objResponse->assign("txtIdNotaCargo","value",$idNotaCargo);
		
		// MODIFICADO ERNESTO
		switch ($frmDcto['lstModulo']) {
			case 0 : if (function_exists("generarNotasCargoCpRe")) { generarNotasCargoCpRe($idNotaCargo,"",""); } break;
			case 1 : if (function_exists("generarNotasCargoCpRe")) { generarNotasCargoCpRe($idNotaCargo,"",""); } break;
			case 2 : if (function_exists("generarNotasCargoCpVe")) { generarNotasCargoCpVe($idNotaCargo,"",""); } break;
			case 3 : if (function_exists("generarNotasCargoCpAd")) { generarNotasCargoCpAd($idNotaCargo,"",""); } break;
		}
		// MODIFICADO ERNESTO
	}
	
	
	$objResponse->alert("Nota de Débito Guardado con Éxito");
	
	$comprobanteRetencion = ($frmTotalDcto['rbtRetencion'] == 1) ? 0 : 1;
	
	$objResponse->script("verVentana('reportes/cp_nota_cargo_pdf.php?valBusq=".$idNotaCargo."', 960, 550);");
	
	$objResponse->script(sprintf("window.location.href='cp_nota_cargo_historico_list.php';"));
	
	return $objResponse;
}

function insertarMetodoPago($frmMetodoPago, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	$arrayMetodoPago = array(1 => "Transferencia", 2 => "Cheque", 3 => "AN", 4 => "NC");
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	switch($frmMetodoPago['lstMetodoPago']) { // 1 = Transferencia, 2 = Cheque, 3 = Anticipo, 4 = Nota Credito
		case 1 : // Transferencia
			// BUSCA LOS DATOS DEL BANCO 
			$query = sprintf("SELECT * FROM bancos
			WHERE idBanco = %s;",
				valTpDato($frmMetodoPago['lstBancoCompaniaTransferencia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBancoCompaniaPago = $row['nombreBanco'];
			
			// BUSCA LOS DATOS DE LA CUENTA
			$query = sprintf("SELECT * FROM cuentas
			WHERE idCuentas = %s;",
				valTpDato($frmMetodoPago['lstCuentaCompaniaTransferencia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtCuentaCompaniaPago = $row['numeroCuentaCompania'];
			
			// BUSCA LOS DATOS DEL BANCO 
			$query = sprintf("SELECT * FROM bancos
			WHERE idBanco = %s;",
				valTpDato($frmMetodoPago['lstBancoProveedorTransferencia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBancoProveedorPago = $row['nombreBanco'];
			
			$Result1 = insertarItemMetodoPago($contFila, "", date("d-m-Y"), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], "", $frmMetodoPago['txtNumeroTransferencia'], $txtBancoCompaniaPago, $txtCuentaCompaniaPago, $txtBancoProveedorPago, $frmMetodoPago['txtCuentaProveedorTransferencia'], str_replace(",","",$frmMetodoPago['txtMontoTransferencia']));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
			break;
		case 2 : // Cheque
			// BUSCA LOS DATOS DEL BANCO 
			$query = sprintf("SELECT * FROM bancos
			WHERE idBanco = %s;",
				valTpDato($frmMetodoPago['lstBancoCompaniaCheque'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBancoCompaniaPago = $row['nombreBanco'];
			
			// BUSCA LOS DATOS DE LA CUENTA
			$query = sprintf("SELECT * FROM cuentas
			WHERE idCuentas = %s;",
				valTpDato($frmMetodoPago['lstCuentaCompaniaCheque'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtCuentaCompaniaPago = $row['numeroCuentaCompania'];
			
			$Result1 = insertarItemMetodoPago($contFila, "", date("d-m-Y"), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], "", $frmMetodoPago['txtNumeroCheque'], $txtBancoCompaniaPago, $txtCuentaCompaniaPago, "-", "-", str_replace(",","",$frmMetodoPago['txtMontoCheque']));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
			break;
		case 3 : // AN
			// VERIFICA SI EL SALDO DEL ANTICIPO ES MAYOR AL MONTO
			$query = sprintf("SELECT * FROM cp_anticipo
			WHERE id_anticipo = %s
				AND saldoanticipo >= %s;",
				valTpDato($frmMetodoPago['txtIdAnticipo'], "int"),
				valTpDato($frmMetodoPago['txtMontoAnticipo'], "real_inglesa"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			if ($totalRows > 0) {
				$Result1 = insertarItemMetodoPago($contFila, "", date("d-m-Y"), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], $frmMetodoPago['txtIdAnticipo'], $frmMetodoPago['txtNumeroAnticipo'], "-", "-", "-", "-", str_replace(",","",$frmMetodoPago['txtMontoAnticipo']));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
			} else {
				$objResponse->loadCommands(asignarAnticipo($frmMetodoPago['txtIdAnticipo'], "Anticipo"));
				
				return $objResponse->alert("No posee saldo suficiente en el Anticipo");
			}
			break;
		case 4 : // NC
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					if (!($frmListaPagoDcto['hddIdPago'.$valor] > 0)
					&& $frmListaPagoDcto['txtMetodoPago'.$valor] == $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']]
					&& $frmListaPagoDcto['txtIdNumeroDctoPago'.$valor] == $frmMetodoPago['txtIdNotaCredito']) {
						return $objResponse->alert("Este item ya se encuentra incluido");
					}
				}
			}
			
			// VERIFICA SI EL SALDO DE LA NOTA DE CREDITO ES MAYOR AL MONTO
			$query = sprintf("SELECT * FROM cp_notacredito
			WHERE id_notacredito = %s
				AND saldo_notacredito >= %s;",
				valTpDato($frmMetodoPago['txtIdNotaCredito'], "int"),
				valTpDato($frmMetodoPago['txtMontoNotaCredito'], "real_inglesa"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			if ($totalRows > 0) {
				$Result1 = insertarItemMetodoPago($contFila, "", date("d-m-Y"), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], $frmMetodoPago['txtIdNotaCredito'], $frmMetodoPago['txtNumeroNotaCredito'], "-", "-", "-", "-", str_replace(",","",$frmMetodoPago['txtMontoNotaCredito']));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
			} else {
				$objResponse->loadCommands(asignarNotaCredito($frmMetodoPago['txtIdNotaCredito'], "NotaCredito"));
				
				return $objResponse->alert("No posee saldo suficiente en la Nota de Crédito");
			}
			break;
	}
	
	$objResponse->script("
	byId('btnCancelarMetodoPago').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function listaAnticipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("anticipo.estado NOT IN (0,3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("prov.id_proveedor = %s",
		valTpDato($valCadBusq[1], "int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR CONCAT_WS('', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR anticipo.numeroAnticipo LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		anticipo.id_anticipo,
		anticipo.fechaanticipo,
		anticipo.numeroAnticipo,
		prov.nombre,
		anticipo.total,
		anticipo.saldoanticipo,
		anticipo.estado
	FROM cp_anticipo anticipo
		INNER JOIN cp_proveedor prov ON (anticipo.id_proveedor = prov.id_proveedor) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "fechaanticipo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "18%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Anticipo"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "16%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarAnticipo('".$row['id_anticipo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaanticipo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroAnticipo']."</td>";
			$htmlTb .= "<td>".utf8_decode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CP'
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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

function listaNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("nota_cred.estado_notacredito NOT IN (0,3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(nota_cred.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = nota_cred.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = nota_cred.id_empresa))",
		valTpDato($valCadBusq[1], "int"),
		valTpDato($valCadBusq[1], "int"),
		valTpDato($valCadBusq[1], "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("prov.id_proveedor = %s",
		valTpDato($valCadBusq[2], "int"));
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR CONCAT_WS('', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR nota_cred.numero_nota_credito LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		nota_cred.id_notacredito,
		nota_cred.fecha_notacredito,
		nota_cred.numero_nota_credito,
		prov.nombre AS nombre_proveedor,
		nota_cred.observacion_notacredito,
		
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		
		(IFNULL(nota_cred.subtotal_notacredito, 0)
			- IFNULL(nota_cred.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
					FROM cp_notacredito_gastos nota_cred_gasto
					WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito), 0)
			+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
					FROM cp_notacredito_iva nota_cred_iva
					WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
		) AS total,
		
		nota_cred.saldo_notacredito,
		nota_cred.estado_notacredito,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_proveedor prov
		INNER JOIN cp_notacredito nota_cred ON (prov.id_proveedor = nota_cred.id_proveedor)
		LEFT JOIN pg_motivo motivo ON (nota_cred.id_motivo = motivo.id_motivo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (nota_cred.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "20%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "fecha_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Nota de Crédito Proveedor"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "numero_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Nota de Crédito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "44%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "saldo_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Saldo Nota de Crédito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNotaCredito('".$row['id_notacredito']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_notacredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_nota_credito']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= ($row['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$row['id_motivo'].".- ".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['observacion_notacredito']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_notacredito'])."</span></td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_notacredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNotaCredito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarAnticipo");
$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarFechaRegistro");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarMetodoPago");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"asignarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstBanco");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"eliminarMetodoPago");
$xajax->register(XAJAX_FUNCTION,"formNotaCargo");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarMetodoPago");
$xajax->register(XAJAX_FUNCTION,"listaAnticipo");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaNotaCredito");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");

function insertarItemMetodoPago($contFila, $idPago = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoProveedorPago = "", $txtCuentaProveedorPago = "", $txtMontoPago = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("SELECT pago_dcto.*,
			(CASE tipo_pago
				WHEN 'AN' THEN
					(SELECT anticipo.numeroAnticipo FROM cp_anticipo anticipo
					WHERE anticipo.id_anticipo = pago_dcto.id_documento)
				WHEN 'NC' THEN
					(SELECT nota_cred.numero_nota_credito FROM cp_notacredito nota_cred
					WHERE nota_cred.id_notacredito = pago_dcto.id_documento)
				WHEN 'TRANSFERENCIA' THEN
					IFNULL((SELECT transf.numero_transferencia FROM te_transferencia transf
					WHERE transf.id_transferencia = pago_dcto.id_documento), pago_dcto.numero_documento)
				WHEN 'CHEQUE' THEN
					IFNULL((SELECT cheque.numero_cheque FROM te_cheques cheque
					WHERE cheque.id_cheque = pago_dcto.id_documento), pago_dcto.numero_documento)
				ELSE
					pago_dcto.numero_documento
			END) AS numero_documento,
			
			(CASE tipo_pago
				WHEN 'NC' THEN
					(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
					FROM cp_notacredito nota_cred
						INNER JOIN pg_motivo motivo ON (nota_cred.id_motivo = motivo.id_motivo)
					WHERE nota_cred.id_notacredito = pago_dcto.id_documento)
			END) AS descripcion_motivo,
			
			(CASE tipo_pago
				WHEN 'ISLR' THEN
					(SELECT
						(CASE ret_cheque.tipo_documento
							WHEN 0 THEN
								CONCAT('RETENCION DEL CHEQUE NRO.', IFNULL(cheque.numero_cheque, cheque_anulado.numero_cheque))
							WHEN 1 THEN
								CONCAT('RETENCION DE LA TRANSFERENCIA NRO.', IFNULL(transferencia.numero_transferencia, transferencia_anulada.numero_transferencia))
						END)
					FROM te_retencion_cheque ret_cheque
						LEFT JOIN te_cheques cheque ON (ret_cheque.id_cheque = cheque.id_cheque
							AND ret_cheque.tipo_documento = 0)
						LEFT JOIN te_cheques_anulados cheque_anulado ON (ret_cheque.id_cheque = cheque_anulado.id_cheque
							AND ret_cheque.tipo_documento = 0)
						LEFT OUTER JOIN te_transferencias_anuladas transferencia_anulada ON (ret_cheque.id_cheque = transferencia_anulada.id_transferencia_anulada
							AND ret_cheque.tipo_documento = 1)
						LEFT OUTER JOIN te_transferencia transferencia ON (ret_cheque.id_cheque = transferencia.id_transferencia
							AND ret_cheque.tipo_documento = 1)
					WHERE ret_cheque.id_retencion_cheque = pago_dcto.id_documento)
				WHEN 'NC' THEN
					(SELECT cxp_nc.observacion_notacredito FROM cp_notacredito cxp_nc
					WHERE cxp_nc.id_notacredito = pago_dcto.id_documento)
			END) AS observacion_documento,
			
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
		FROM cp_pagos_documentos pago_dcto
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (pago_dcto.id_empleado_creador = vw_pg_empleado.id_empleado)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (pago_dcto.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		WHERE pago_dcto.id_pago = %s;",
			valTpDato($idPago, "int"));
		$rs = mysql_query($query);
		if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	} else {
		$cbxItm = sprintf("<input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>",
			$contFila);
	}
	
	$classMontoPago = ($row['estatus'] != 1 && $totalRows > 0) ? "class=\"divMsjError\"" : "";
	
	$txtFechaPago = ($txtFechaPago == "" && $totalRows > 0) ? $row['fecha_pago'] : $txtFechaPago;
	$txtMetodoPago = ($txtMetodoPago == "" && $totalRows > 0) ? $row['tipo_pago'] : $txtMetodoPago;
	$txtIdNumeroDctoPago = ($txtIdNumeroDctoPago == "" && $totalRows > 0) ? $row['id_documento'] : $txtIdNumeroDctoPago;
	$txtNumeroDctoPago = ($txtNumeroDctoPago == "" && $totalRows > 0) ? $row['numero_documento'] : $txtNumeroDctoPago;
	$txtBancoCompaniaPago = ($txtBancoCompaniaPago == "" && $totalRows > 0) ? $row['banco_compania'] : $txtBancoCompaniaPago;
	$txtCuentaCompaniaPago =($txtCuentaCompaniaPago == "" && $totalRows > 0) ?  $row['cuenta_compania'] : $txtCuentaCompaniaPago;
	$txtBancoProveedorPago = ($txtBancoProveedorPago == "" && $totalRows > 0) ? $row['banco_proveedor'] : $txtBancoProveedorPago;
	$txtCuentaProveedorPago = ($txtCuentaProveedorPago == "" && $totalRows > 0) ? $row['cuenta_proveedor'] : $txtCuentaProveedorPago;
	$txtMontoPago = ($txtMontoPago == "" && $totalRows > 0) ? $row['monto_cancelado'] : $txtMontoPago;
	$hddEstatusPago = ($hddEstatusPago == "" && $totalRows > 0) ? $row['estatus'] : 1;
	$descripcionMotivo = (strlen($row['descripcion_motivo']) > 0) ? "<div align=\"left\"><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_motivo'])."</span></div>" : "";
	$observacionDctoPago = (strlen($row['observacion_documento']) > 0) ? "<div align=\"left\"><span class=\"textoNegritaCursiva_9px\">".preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$row['observacion_documento']))))."</span></div>" : "";
	$estatusPago = ($row['estatus'] != 1 && $totalRows > 0) ? "<div align=\"left\">PAGO ANULADO</div>" : "";
	$empleadoCreadorPago = (strlen($row['nombre_empleado']) > 0) ? "<span class=\"texto_9px\">Registrado por:</span><br><span class=\"textoNegrita_9px\">".$row['nombre_empleado']."</span>" : "";
	$empleadoAnuladoPago = (strlen($row['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".$row['nombre_empleado_anulado']."<br>(".date("d-m-Y",strtotime($row['fecha_anulado'])).")</span></div>" : "";
	
	switch ($txtMetodoPago) {
		case "NC" :
			$aVerNotaCredito = "<a id=\"aVerNotaCredito\" href=\"cp_nota_credito_form.php?id=".$txtIdNumeroDctoPago."&vw=v\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Nota de Crédito\"/><a>";
			$aVerNotaCredito .= "<a href=\"javascript:verVentana(\'../cxp/reportes/cp_nota_credito_pdf.php?valBusq=".$txtIdNumeroDctoPago."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Crédito PDF\"/><a>"; break;
		case "RETENCION" :
			$aVerNotaCredito = "<a href=\"javascript:verVentana(\'../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=".$txtIdNumeroDctoPago."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Comprobante de Retención\"/><a>"; break;
	}
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\" height=\"24\">".
			"<td align=\"center\" title=\"trItm:%s\">%s".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtFechaPago%s\" name=\"txtFechaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s</td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtMetodoPago%s\" name=\"txtMetodoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr><td>%s</td><td><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr></table>".
				"%s".
				"%s".
				"%s</td>".
			"<td %s><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtBancoProveedorPago%s\" name=\"txtBancoProveedorPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtCuentaProveedorPago%s\" name=\"txtCuentaProveedorPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtMontoPago%s\" name=\"txtMontoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusPago%s\" name=\"hddEstatusPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $cbxItm,
				$contFila,
			$classMontoPago, $contFila, $contFila, utf8_encode(date("d-m-Y", strtotime($txtFechaPago))),
				$empleadoCreadorPago,
			$classMontoPago, $contFila, $contFila, ($txtMetodoPago),
				$estatusPago,
			$classMontoPago, "100%", $aVerNotaCredito, $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
				$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				$descripcionMotivo,
				$observacionDctoPago,
				$empleadoAnuladoPago,
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCuentaCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoProveedorPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCuentaProveedorPago),
			$classMontoPago, $contFila, $contFila, utf8_encode(number_format($txtMontoPago, 2, ".", ",")),
				$contFila, $contFila, $idPago,
				$contFila, $contFila, $hddEstatusPago);
	
	return array(true, $htmlItmPie, $contFila);
}
?>