<?php


function actualizarObjetosExistentes($frmPlanilla){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$frmPlanilla['hddObjDetallePago']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$cadena = "";
	
	foreach($arrayObj as $indice => $valor) {
		if (isset($frmPlanilla['hddIdTabla'.$valor])){
			$cadena .= "|".$valor;
		}
	}
	
	$objResponse->assign("hddObjDetallePago","value",$cadena);
	$objResponse->script("$('btnBuscar').click();");
	$objResponse->script("xajax_calcularTotalDeposito(xajax.getFormValues('frmPlanilla'))");
	
	if ($indice <= 0) {
		$objResponse->script("$('trPlanillaDeposito').style.display='none';");		
		$objResponse->script("document.forms['frmPlanilla'].reset();");
	}
	return $objResponse;
}

function buscar($frmPagos){
	$objResponse = new xajaxResponse();
	
	$critierioBusqueda = sprintf("%s",
		valTpDato($frmPagos['selTipoPago'],"int"));
	
	$objResponse->script("xajax_listaPagoDepositar(0,'','','".$critierioBusqueda."',10,'',xajax.getFormValues('frmPlanilla'));");
	
	return $objResponse;	
}

function calcularTotalDeposito($frmPlanilla){
	$objResponse = new xajaxResponse();
	
	if ($frmPlanilla['hddObjDetallePago'] != ""){
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayVal = explode("|",$frmPlanilla['hddObjDetallePago']);
		
		foreach ($arrayVal as $indice => $valor) {
			if ($valor != ""){
				$arrayCriterioBusqueda = explode("|",$frmPlanilla['hddIdTabla'.$valor]);
				
				//POS 0 nombre "tabla"
				//POS 1 nombre "id tabla"
				//POS 2 idPago
				//POS 3 nombre "montoPagado"
				//POS 4 nombre "formaPago"
				if ($arrayCriterioBusqueda[0] != "") {
					$sqlConsultaPago = sprintf("SELECT %s, %s FROM %s WHERE %s = %s",
						$arrayCriterioBusqueda[3], $arrayCriterioBusqueda[4], $arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
					
					$rsConsultaPago = mysql_query($sqlConsultaPago);
					if (!$rsConsultaPago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlConsultaPago);
					$rowConsultaPago = mysql_fetch_array($rsConsultaPago);
					
					if ($rowConsultaPago[$arrayCriterioBusqueda[4]] == 1 || $rowConsultaPago[$arrayCriterioBusqueda[4]] == 'EF') {
						$totalEfectivo += $rowConsultaPago[$arrayCriterioBusqueda[3]];
					} else if ($rowConsultaPago[$arrayCriterioBusqueda[4]] == 2 || $rowConsultaPago[$arrayCriterioBusqueda[4]] == 'CH') {
						$totalCheque += $rowConsultaPago[$arrayCriterioBusqueda[3]];
					} else if ($rowConsultaPago[$arrayCriterioBusqueda[4]] == 3 || $rowConsultaPago[$arrayCriterioBusqueda[4]] == 'CB') {
						$totalCashBack += $rowConsultaPago[$arrayCriterioBusqueda[3]];
					}
				}
			}
		}
	}
	
	$objResponse->assign("txtTotalEfectivo","value",number_format($totalEfectivo,2,',','.'));
	$objResponse->assign("txtTotalCheques","value",number_format($totalCheque,2,',','.'));
	$objResponse->assign("txtTotalCashBack","value",number_format($totalCashBack,2,',','.'));
	$objResponse->assign("txtTotalDeposito","value",number_format($totalEfectivo + $totalCheque,2,',','.'));
	
	if ($totalEfectivo + $totalCheque + $totalCashBack > 0) {
		$objResponse->script("$('btnDepositar').disabled = ''");
	} else {
		$objResponse->script("$('btnDepositar').disabled = 'disabled'");
	}
	
	$objResponse->script("$('btnBuscar').click();");
	
	return $objResponse;
}

function cargarAPlanilla($varPagos, $hddObjDetallePago){
	$objResponse = new xajaxResponse();
	
	$arregloPagos = explode("|",$varPagos);
	
	for ($cont = 0; $cont <= strlen($hddObjDetallePago); $cont++) {
		$caracter = substr($hddObjDetallePago, $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadenaDetallePago.= $caracter;
		else {
			$arrayObjDetallePago[] = $cadenaDetallePago;
			$cadenaDetallePago = "";
		}
	}
	
	$sigValor = $arrayObjDetallePago[count($arrayObjDetallePago)-1] + 1;
	
	foreach($arregloPagos as $indice => $valor){
		if (fmod($indice, 2) == 0) {
			$idPago = $valor;
		} else {
			$tabla = $valor;
			
			if ($tabla == "sa_iv_pagos"){
				$nombreIdPago = "idPago";
				$nombreTipoPago = "formaPago";
				$bancoClientePago = "bancoOrigen";
				$numeroCuentaPago = "cuentaEmpresa";
				$numeroChequePago = "numeroDocumento";
				$montoPago = "montoPagado";
			} else if ($tabla == "cj_det_nota_cargo"){
				$nombreIdPago = "id_det_nota_cargo";
				$nombreTipoPago = "idFormaPago";
				$bancoClientePago = "bancoOrigen";
				$numeroCuentaPago = "cuentaEmpresa";
				$numeroChequePago = "numeroDocumento";
				$montoPago = "monto_pago";
			} else if ($tabla == "cj_cc_detalleanticipo"){
				$nombreIdPago = "idDetalleAnticipo";
				$nombreTipoPago = "tipoPagoDetalleAnticipo";
				$bancoClientePago = "bancoClienteDetalleAnticipo";
				$numeroCuentaPago = "numeroCuentaCompania";
				$numeroChequePago = "numeroControlDetalleAnticipo";
				$montoPago = "montoDetalleAnticipo";
			}
			
			$sqlDetallePago = sprintf("SELECT %s, %s, %s, %s, %s FROM %s WHERE %s = %s",
				$nombreIdPago, $numeroCuentaPago, $bancoClientePago, $numeroChequePago, $montoPago, $tabla, $nombreIdPago, $idPago);
			$rsDetallePago = mysql_query($sqlDetallePago);
			if (!$rsDetallePago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlDetallePago);
			$rowDetallePago = mysql_fetch_array($rsDetallePago);
			
			if ($rowDetallePago[$bancoClientePago] != "" && $rowDetallePago[$bancoClientePago] != "1"){
				$sqlBanco = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s",
					valTpDato($rowDetallePago[$bancoClientePago],"int"));
				$rsBanco = mysql_query($sqlBanco);
				if (!$rsBanco) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlBanco);
				$rowBanco = mysql_fetch_array($rsBanco);
				
				$nombreBanco = $rowBanco['nombreBanco'];
				$numeroCuentaCheque = $rowDetallePago[$numeroCuentaPago];
				$numeroCheque = $rowDetallePago[$numeroChequePago];
			} else {
				$nombreBanco = "-";
				$numeroCuentaCheque = "-";
				$numeroCheque = "-";
			}
			
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			
			$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px %s', 'onmouseover':'this.className = \'trSobre\'', 'onmouseout':'this.className = \'".$clase."\'', 'height':'22', 'title':'trItm:%s'}).adopt([
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s\"),
					new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"<button type='button' onclick='confirmarEliminarPago(%s);' title='Eliminar Pago' class='puntero'><img src='../img/iconos/delete.png'/></button>".
					"<input type='hidden' id='hddIdTabla%s' name='hddIdTabla%s' value='%s' title='hddIdTabla'/>\")
					]);
					elemento.injectBefore('trPie');",
					$sigValor, $clase, $sigValor,
					utf8_encode($nombreBanco),
					$numeroCuentaCheque,
					$numeroCheque,
					number_format($rowDetallePago[$montoPago],2,'.',','),
					$sigValor, $sigValor,
					$sigValor, $sigValor, $tabla."|".$nombreIdPago."|".$rowDetallePago[$nombreIdPago]."|".$montoPago."|".$nombreTipoPago));
			
			$arrayObjDetallePago[] = $sigValor;
			foreach($arrayObjDetallePago as $indiceDetallePago => $valorDetallePago) {
				$cadena = $hddObjDetallePago."|".$valorDetallePago;
			}
			$hddObjDetallePago = $cadena;
			$sigValor++;
		}
	}
	
	$objResponse->assign("hddObjDetallePago","value",$cadena);
	$objResponse->script("$('btnBuscar').click();");
	$objResponse->script("xajax_calcularTotalDeposito(xajax.getFormValues('frmPlanilla'))");
	
	$objResponse->script("$('trPlanillaDeposito').style.display = '';");
	
	return $objResponse;
}

function cargaSelBanco(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT banco.*
	FROM bancos banco
		INNER JOIN cuentas cuenta ON (banco.idBanco = cuenta.idBanco)
	GROUP BY banco.idBanco ORDER BY banco.nombreBanco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = sprintf("<select id=\"selBanco\" name=\"selBanco\" class=\"inputHabilitado\" onchange=\"xajax_cargarCuentas(this.value);\">");
		$html .= ($totalRows > 1) ? "<option value=\"\">[ Seleccione ]</option>" : "";
	while ($row = mysql_fetch_array($rs)){
		$selected = ($selId == $row['idBanco'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargarCuentas($row["idBanco"])); }
		
		$html .= "<option ".$selected." value=\"".$row["idBanco"]."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdSelBanco","innerHTML",$html);
	
	return $objResponse;
}

function cargarCuentas($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas WHERE idBanco = %s AND estatus = 1",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"selNumeroCuenta\" name=\"selNumeroCuenta\" class=\"inputHabilitado\" style=\"width:250px\">";
		$html .= ($totalRows > 1) ? "<option value=\"\">[ Seleccione ]</option>" : "";
	while ($row = mysql_fetch_array($rs)){
		$selected = ($selId == $row['idCuentas'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row["idCuentas"]."\">".utf8_encode($row["numeroCuentaCompania"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdSelNumeroCuenta","innerHTML",$html);
	
	return $objResponse;
}

function depositarPlanilla($frmPlanilla){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	
	$fecha = date("d-m-Y");
	$numeroDeposito = date("Y").date("m").date("d");
		
	// INSERTA EL ENCABEZADO DEL DEPÓSITO
	$queryCabeceraPlanillaCaja = sprintf("INSERT INTO an_encabezadodeposito (fechaPlanilla, numeroDeposito, id_usuario, idCaja, id_empresa)
	VALUES (NOW(), %s, %s, %s, %s)",
		valTpDato($numeroDeposito, "int"),
		valTpDato($idUsuario, "int"),
		valTpDato(2, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idEmpresa,"int"));
	$rsCabeceraPlanillaCaja = mysql_query($queryCabeceraPlanillaCaja);
	if (!$rsCabeceraPlanillaCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryCabeceraPlanillaCaja);
	$idCabeceraPlanillaCaja = mysql_insert_id();
	
	// CONSULTA EL NUMERO ACTUAL DE FOLIO EN TESORERIA
	$selectFolioDepositoTesoreria = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 2");
	$rsFolioDepositoTesoreria = mysql_query($selectFolioDepositoTesoreria);
	if (!$rsFolioDepositoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$selectFolioDepositoTesoreria);
	$rowFolioDepositoTesoreria = mysql_fetch_array($rsFolioDepositoTesoreria);
	
	// ACTUALIZA EL NUMERO ACTUAL DE FOLIO EN TESORERIA
	//id_folios = 2: deposito
	$sqlUpdateFolioDepositoTesoreria = sprintf("UPDATE te_folios SET numero_actual = %s WHERE id_folios = 2",
		valTpDato($rowFolioDepositoTesoreria['numero_actual'] + 1,"int"));
	$rsUpdateFolioDepositoTesoreria = mysql_query($sqlUpdateFolioDepositoTesoreria);
	if (!$rsUpdateFolioDepositoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlUpdateFolioDepositoTesoreria);
	
	// CONSULTA NUMERO DE CUENTA Y BANCO A DEPOSITAR
	$selectNumeroCuenta = sprintf("SELECT numeroCuentaCompania, idBanco FROM cuentas WHERE idCuentas = %s", $frmPlanilla['selNumeroCuenta']);
	$rsNumeroCuenta = mysql_query($selectNumeroCuenta);
	if (!$rsNumeroCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$selectNumeroCuenta);
	$rowNumeroCuenta = mysql_fetch_array($rsNumeroCuenta);
	
	// INSERTA EL DEPOSITO EN TESORERIA
	$queryCabeceraPlanillaTesoreria = sprintf("INSERT INTO te_depositos (id_numero_cuenta, fecha_registro, fecha_aplicacion, numero_deposito_banco, estado_documento, origen, id_usuario, monto_total_deposito, id_empresa, desincorporado, monto_efectivo, monto_cheques_total, observacion, folio_deposito)
	VALUES (%s, NOW(), NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, 'Deposito Cierre Caja RS %s', %s)",
		valTpDato($frmPlanilla['selNumeroCuenta'], "int"),
		valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
		valTpDato(2, "int"), //1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
		valTpDato(2, "int"), //0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
		valTpDato($idUsuario, "int"),
		valTpDato(0, "real_inglesa"),
		valTpDato($idEmpresa,"int"),
		valTpDato(1, "int"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		$fecha,
		valTpDato($rowFolioDepositoTesoreria['numero_actual'],"int"));
	$rsCabeceraPlanillaTesoreria = mysql_query($queryCabeceraPlanillaTesoreria);
	if (!$rsCabeceraPlanillaTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryCabeceraPlanillaTesoreria);
	$idCabeceraPlanillaTesoreria = mysql_insert_id();
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$frmPlanilla['hddObjDetallePago']);
	
	foreach ($arrayVal as $indice => $valor) {
		if ($valor != ""){
			$arrayCriterioBusqueda = explode("|",$frmPlanilla['hddIdTabla'.$valor]);
			
			//POSICIONES DE $arrayCriterioBusqueda
			//POS 0 nombre "tabla"
			//POS 1 nombre "id tabla"
			//POS 2 idPago
			//POS 3 nombre "montoPagado"
			//POS 4 nombre "formaPago"
			
			if ($arrayCriterioBusqueda[0] == 'sa_iv_pagos') { // FACTURA
				$sqlConsultaPago = sprintf("SELECT
					idPago AS idPago,
					formaPago AS tipoPago,
					bancoOrigen AS bancoOrigen,
					bancoDestino AS bancoDestino,
					cuentaEmpresa AS numeroCuenta,
					numeroDocumento AS numeroControl,
					montoPagado AS montoPago,
					fechaPago AS fechaPago,
					1 AS tipoDocumento
				FROM %s
				WHERE %s = %s",
					$arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
			} else if ($arrayCriterioBusqueda[0] == 'cj_det_nota_cargo') { // NOTA DE CARGO
				$sqlConsultaPago = sprintf("SELECT
					id_det_nota_cargo AS idPago,
					idFormaPago AS tipoPago,
					bancoOrigen AS bancoOrigen,
					bancoDestino AS bancoDestino,
					cuentaEmpresa AS numeroCuenta,
					numeroDocumento AS numeroControl,
					monto_pago AS montoPago,
					fechaPago AS fechaPago,
					2 AS tipoDocumento
				FROM %s
				WHERE %s = %s",
					$arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
			} else if ($arrayCriterioBusqueda[0] == 'cj_cc_detalleanticipo') { // ANTICIPO
				$sqlConsultaPago = sprintf("SELECT
					idDetalleAnticipo AS idPago,
					tipoPagoDetalleAnticipo AS tipoPago,
					bancoClienteDetalleAnticipo AS bancoOrigen,
					bancoCompaniaDetalleAnticipo AS bancoDestino,
					numeroCuentaCompania AS numeroCuenta,
					numeroControlDetalleAnticipo AS numeroControl,
					montoDetalleAnticipo AS montoPago,
					fechaPagoAnticipo AS fechaPago,
					4 AS tipoDocumento
				FROM %s
				WHERE %s = %s",
					$arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
			}
					
			// CAMBIAR EL ESTADO DEL PAGO
			$sqlUpdatePago = sprintf("UPDATE %s SET tomadoEnCierre = 2 WHERE %s = %s",
				$arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
			$rsUpdatePago = mysql_query($sqlUpdatePago);
			if (!$rsUpdatePago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlUpdatePago);
			
			// DE LA CONSULTA: sqlConsultaPago
			$rsConsultaPago = mysql_query($sqlConsultaPago);
			if (!$rsConsultaPago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlConsultaPago);
			$rowConsultaPago = mysql_fetch_array($rsConsultaPago);
			
			
			if ($rowConsultaPago['tipoPago'] == 1 || $rowConsultaPago['tipoPago'] == 'EF') { // PAGO EN EFECTIVO
				$totalEfectivo += $rowConsultaPago['montoPago'];
				
				// INSERTA EL DETALLE DEL DEPÓSITO EN EFECTIVO
				$queryDetallePlanillaCaja = sprintf("INSERT INTO an_detalledeposito (idPagoRelacionadoConNroCheque, numeroCheque, banco, numeroCuenta, monto, idBancoAdepositar, numeroCuentaBancoAdepositar, formaPago, conformado, tipoDeCheque, numeroDeposito, idTipoDocumento, idPlanilla, anulada, idCaja)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($rowConsultaPago['idPago'],"int"),
					valTpDato('-',"text"),
					valTpDato(1,"int"),
					valTpDato('-',"text"),
					valTpDato($rowConsultaPago['montoPago'],"double"),
					valTpDato($rowNumeroCuenta['idBanco'],"int"),
					valTpDato($rowNumeroCuenta['numeroCuentaCompania'],"text"),
					valTpDato(1,"int"),
					valTpDato(2,"int"), // 1 = Por Conformar, 2 = Conformado
					valTpDato(0,"int"), // 0 = Efectivo, 1 = Local, 2 = Otra Plaza
					valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
					valTpDato($rowConsultaPago['tipoDocumento'],"int"),
					valTpDato($idCabeceraPlanillaCaja,"int"),
					valTpDato('NO',"text"),
					valTpDato(2,"int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			} else if ($rowConsultaPago['tipoPago'] == 2 || $rowConsultaPago['tipoPago'] == 'CH') { // PAGO EN CHEQUE
				$totalCheque += $rowConsultaPago['montoPago'];
				
				$tipoCheque = ($rowConsultaPago['bancoOrigen'] == $rowNumeroCuenta['idBanco']) ? 1 : 2;
				
				// INSERTA EL DETALLE DEL DEPÓSITO EN CHEQUES
				$queryDetallePlanillaCaja = sprintf("INSERT INTO an_detalledeposito (idPagoRelacionadoConNroCheque, numeroCheque, banco, numeroCuenta, monto, idBancoAdepositar, numeroCuentaBancoAdepositar, formaPago, conformado, tipoDeCheque, numeroDeposito, idTipoDocumento, idPlanilla, anulada, idCaja)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($rowConsultaPago['idPago'],"int"),
					valTpDato($rowConsultaPago['numeroControl'],"text"),
					valTpDato($rowConsultaPago['bancoOrigen'],"int"),
					valTpDato($rowConsultaPago['numeroCuenta'],"text"),
					valTpDato($rowConsultaPago['montoPago'],"double"),
					valTpDato($rowNumeroCuenta['idBanco'],"int"),
					valTpDato($rowNumeroCuenta['numeroCuentaCompania'],"text"),
					valTpDato(2,"int"),
					valTpDato(2,"int"), // 1 = Por Conformar, 2 = Conformado
					valTpDato($tipoCheque,'int'), // 0 = Efectivo, 1 = Local, 2 = Otra Plaza
					valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
					valTpDato($rowConsultaPago['tipoDocumento'],'int'),
					valTpDato($idCabeceraPlanillaCaja,'int'),
					valTpDato('NO', "text"),
					valTpDato(2,"int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
						
				// INSERTA EL DETALLE DEL DEPÓSITO EN CHEQUES EN TESORERIA
				$queryDetallePlanillaTesoreria = sprintf("INSERT INTO te_deposito_detalle (id_deposito, id_banco, numero_cuenta_cliente, numero_cheques, monto)
				VALUES (%s, %s, %s, %s, %s)",
					valTpDato($idCabeceraPlanillaTesoreria,"int"),
					valTpDato($rowNumeroCuenta['idBanco'],"int"),
					valTpDato($rowConsultaPago['numeroCuenta'],"text"),
					valTpDato($rowConsultaPago['numeroControl'],"text"),
					valTpDato($rowConsultaPago['montoPago'],"double"));
				$rsDetallePlanillaTesoreria = mysql_query($queryDetallePlanillaTesoreria);
				if (!$rsDetallePlanillaTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryDetallePlanillaTesoreria);	
			} else if ($rowConsultaPago['tipoPago'] == 11 || $rowConsultaPago['tipoPago'] == 'CB'){
				$totalCashBack += $rowConsultaPago['montoPago'];
				
				//INSERTA EL DETALLE DEL DEPÓSITO EN CASH BACK
				$queryDetallePlanillaCaja = sprintf("INSERT INTO an_detalledeposito (idPagoRelacionadoConNroCheque, numeroCheque, banco, numeroCuenta, monto, idBancoAdepositar, numeroCuentaBancoAdepositar, formaPago, conformado, tipoDeCheque, numeroDeposito, idTipoDocumento, idPlanilla, anulada, idCaja)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($rowConsultaPago['idPago'],"int"),
					valTpDato('-',"text"),
					valTpDato(1,"int"),
					valTpDato('-',"text"),
					valTpDato($rowConsultaPago['montoPago'],"double"),
					valTpDato($rowNumeroCuenta['idBanco'],"int"),
					valTpDato($rowNumeroCuenta['numeroCuentaCompania'],"text"),
					valTpDato(11,"int"),
					valTpDato(2,"int"), // 1 = Por Conformar, 2 = Conformado
					valTpDato(0,"int"), // 0 = Efectivo, 1 = Local, 2 = Otra Plaza
					valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
					valTpDato($rowConsultaPago['tipoDocumento'],"int"),
					valTpDato($idCabeceraPlanillaCaja,"int"),
					valTpDato('NO',"text"),
					valTpDato(2,"int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			}
			// DE LA CONSULTA: queryDetallePlanillaCaja
			$rsDetallePlanillaCaja = mysql_query($queryDetallePlanillaCaja);
			if (!$rsDetallePlanillaCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryDetallePlanillaCaja);
			
			$objResponse->loadCommands(eliminarPago($valor));
		}
	}
	
	// ACTUALIZA MONTOS EN EL DEPOSITO DE TESORERIA
	$updateDepositoTesoreria = sprintf("UPDATE te_depositos SET
		monto_total_deposito = %s,
		monto_efectivo = %s,
		monto_cheques_total = %s
	WHERE id_deposito = %s",
		valTpDato($totalEfectivo + $totalCheque + $totalCashBack,"double"),
		valTpDato($totalEfectivo + $totalCashBack,"double"),
		valTpDato($totalCheque,"double"),
		valTpDato($idCabeceraPlanillaTesoreria,"int"));
	$rsUpdateDepositoTesoreria = mysql_query($updateDepositoTesoreria);
	if (!$rsUpdateDepositoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$updateDepositoTesoreria);
	
	// INSERTA EL DEPOSITO EN EL ESTADO DE CUENTA DE TESORERIA
	$sqlInsertEstadoCuentaTesoreria = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
	VALUES ('DP', %s, NOW(), %s, %s, %s, 1, %s, 1, 'Deposito Cierre Caja RS %s', 2, 0)",
		valTpDato($idCabeceraPlanillaTesoreria,"int"),
		valTpDato($frmPlanilla['selNumeroCuenta'],"int"),
		valTpDato($idEmpresa,"int"),
		valTpDato($totalEfectivo + $totalCheque + $totalCashBack,"double"),
		valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
		$fecha);
	$rsInsertEstadoCuentaTesoreria = mysql_query($sqlInsertEstadoCuentaTesoreria);
	if (!$rsInsertEstadoCuentaTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlInsertEstadoCuentaTesoreria);
	
	mysql_query("COMMIT");
	
	$objResponse->alert("Planilla de Deposito guardada exitosamente.");
	
	$objResponse->assign("hddObjDetallePago","value","");
	$objResponse->assign("txtNumeroPlanilla","value","");
	
	$objResponse->script("$('btnBuscar').click();");
	
	// MODIFICADO ERNESTO
		if (function_exists("generarDepositosTeRe")) { generarDepositosTeRe($idCabeceraPlanillaTesoreria,"",""); } 
	// MODIFICADO ERNESTO
	
	return $objResponse;
}

function eliminarPago($pos){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	fila = document.getElementById('trItm:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
	
	$objResponse->script("xajax_actualizarObjetosExistentes(xajax.getFormValues('frmPlanilla'))");
	
	return $objResponse;
}

function listaPagoDepositar($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL, $frmPlanilla){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] == 1) {
		$tipoPagoAnticipo = " AND cxc_pago.tipoPagoDetalleAnticipo IN ('EF')";
		$tipoPagoFactura = " AND cxc_pago.formaPago IN (1)";
		$tipoPagoNotaCargo = " AND cxc_pago.idFormaPago IN (1)";
	} else if ($valCadBusq[0] == 2) {
		$tipoPagoAnticipo = " AND cxc_pago.tipoPagoDetalleAnticipo IN ('CH')";
		$tipoPagoFactura = " AND cxc_pago.formaPago IN (2)";
		$tipoPagoNotaCargo = " AND cxc_pago.idFormaPago IN (2)";
	} else if ($valCadBusq[0] == 3) {
		$tipoPagoAnticipo = " AND cxc_pago.tipoPagoDetalleAnticipo IN ('CB')";
		$tipoPagoFactura = " AND cxc_pago.formaPago IN (11)";
		$tipoPagoNotaCargo = " AND cxc_pago.idFormaPago IN (11)";
	} else {
		$tipoPagoAnticipo = " AND cxc_pago.tipoPagoDetalleAnticipo IN ('EF', 'CH', 'CB')";
		$tipoPagoFactura = " AND cxc_pago.formaPago IN (1,2,11)";
		$tipoPagoNotaCargo = " AND cxc_pago.idFormaPago IN (1,2,11)";
	}
	
	if ($frmPlanilla['hddObjDetallePago'] != "") {
		$arregloPos = explode("|",$frmPlanilla['hddObjDetallePago']);
		
		foreach ($arregloPos as $indicePos => $valosPos) {
			$arregloDetallePago = explode("|",$frmPlanilla['hddIdTabla'.$valosPos]);
			
			if ($arregloDetallePago[0] == "sa_iv_pagos") {
				$campoIdPagoFA = $arregloDetallePago[1];
				$arrayIdFactura[] = $arregloDetallePago[2];
			} else if ($arregloDetallePago[0] == "cj_det_nota_cargo") {
				$campoIdPagoND = $arregloDetallePago[1];
				$arrayIdNotaCargo[] = $arregloDetallePago[2];
			} else if ($arregloDetallePago[0] == "cj_cc_detalleanticipo") {
				$campoIdPagoAN = $arregloDetallePago[1];
				$arrayIdAnticipo[] = $arregloDetallePago[2];
			}
		}
		
		if (count($arrayIdFactura) > 0) { $idFactura = " AND cxc_pago.".$campoIdPagoFA." NOT IN (".implode(",", $arrayIdFactura).")"; }
		if (count($arrayIdNotaCargo) > 0) { $idNotaCargo = " AND cxc_pago.".$campoIdPagoND." NOT IN (".implode(",", $arrayIdNotaCargo).")"; }
		if (count($arrayIdAnticipo) > 0) { $idAnticipo = " AND cxc_pago.".$campoIdPagoAN." NOT IN (".implode(",", $arrayIdAnticipo).")"; }
	}
					
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
		
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$andEmpresaPagoFA = sprintf(" AND fv.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresaPagoND = sprintf(" AND fv.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresaPagoAN = sprintf(" AND an.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresaPagoFA = "";
		$andEmpresaPagoND = "";
		$andEmpresaPagoAN = "";
	}
	
	$query = sprintf("SELECT 
		cxc_pago.idDetalleAnticipo AS idPago,
		cxc_pago.tipoPagoDetalleAnticipo AS formaPago,
		'ANTICIPO' AS tipoDoc,
		pg_reportesimpresion.numeroReporteImpresion AS nro_comprobante,
		an.numeroAnticipo AS idDocumento,
		CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
		cxc_pago.numeroControlDetalleAnticipo AS numeroDocumento,
		cxc_pago.bancoClienteDetalleAnticipo AS bancoOrigen,
		(SELECT nombreBanco FROM bancos WHERE idBanco = cxc_pago.bancoClienteDetalleAnticipo) AS nombre_banco_origen,
		cxc_pago.bancoCompaniaDetalleAnticipo AS bancoDestino,
		(SELECT nombreBanco FROM bancos WHERE idBanco = cxc_pago.bancoCompaniaDetalleAnticipo) AS nombre_banco_destino,
		cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
		cxc_pago.montoDetalleAnticipo AS montoPagado,
		'cj_cc_detalleanticipo' AS tabla
	FROM cj_cc_anticipo an
		INNER JOIN pg_reportesimpresion ON (an.idAnticipo = pg_reportesimpresion.idDocumento)
		INNER JOIN cj_cc_cliente ON (an.idCliente = cj_cc_cliente.id),
		cj_cc_detalleanticipo cxc_pago
	WHERE pg_reportesimpresion.id_departamento IN (0,1,3)
		AND an.idAnticipo = cxc_pago.idAnticipo
		AND an.idDepartamento IN (0,1,3)
		".$tipoPagoAnticipo."
		AND cxc_pago.tomadoEnCierre = 1
		".$andEmpresaPagoAN."
		".$idAnticipo."
		AND an.estatus = 1
		
	UNION
	
	SELECT 
		cxc_pago.idPago,
		cxc_pago.formaPago,
		'FACTURA' AS tipoDoc,
		cj_encabezadorecibopago.numeroComprobante AS nro_comprobante,
		fv.numeroFactura AS idDocumento,
		CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
		(CASE cxc_pago.formaPago
			WHEN 8 THEN
				(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
			ELSE
				cxc_pago.numeroDocumento
		END) AS numeroDocumento,
		cxc_pago.bancoOrigen,
		(SELECT nombreBanco FROM bancos WHERE idBanco = cxc_pago.bancoOrigen) AS nombre_banco_origen,
		cxc_pago.bancoDestino,
		(SELECT nombreBanco FROM bancos WHERE idBanco = cxc_pago.bancoDestino) AS nombre_banco_destino,
		cxc_pago.cuentaEmpresa,
		cxc_pago.montoPagado,
		'sa_iv_pagos' AS tabla
	FROM cj_cc_encabezadofactura fv
		INNER JOIN cj_encabezadorecibopago ON (fv.idFactura = cj_encabezadorecibopago.numero_tipo_documento)
		INNER JOIN cj_cc_cliente ON (fv.idCliente = cj_cc_cliente.id)
		INNER JOIN sa_iv_pagos cxc_pago ON (fv.idFactura = cxc_pago.id_factura)
		INNER JOIN cj_detallerecibopago ON (cj_encabezadorecibopago.idComprobante = cj_detallerecibopago.idComprobantePagoFactura)
			AND (cxc_pago.idPago = cj_detallerecibopago.idPago)
	WHERE cj_encabezadorecibopago.id_departamento IN (0,1,3)
		AND fv.idFactura = cxc_pago.id_factura
		AND cxc_pago.tomadoEnComprobante = 1
		AND fv.idDepartamentoOrigenFactura IN (0,1,3)
		".$tipoPagoFactura."
		AND cxc_pago.tomadoEnCierre = 1
		".$andEmpresaPagoFA."
		".$idFactura."
	
	UNION
	
	SELECT 
		cxc_pago.id_det_nota_cargo AS idPago,
		cxc_pago.idFormaPago AS formaPago,
		'NOTA CARGO' AS tipoDoc,
		cj_encabezadorecibopago.numeroComprobante AS nro_comprobante,
		fv.numeroNotaCargo AS idDocumento,
		CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
		(CASE cxc_pago.idFormaPago
			WHEN 8 THEN
				(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
			ELSE
				cxc_pago.numeroDocumento
		END) AS numeroDocumento,
		cxc_pago.bancoOrigen,
		(SELECT nombreBanco FROM bancos WHERE idBanco = cxc_pago.bancoOrigen) AS nombre_banco_origen,
		cxc_pago.bancoDestino,
		(SELECT nombreBanco FROM bancos WHERE idBanco = cxc_pago.bancoDestino) AS nombre_banco_destino,
		cxc_pago.cuentaEmpresa,
		cxc_pago.monto_pago AS montoPagado,
		'cj_det_nota_cargo' AS tabla
	FROM cj_cc_notadecargo fv
		INNER JOIN cj_encabezadorecibopago ON (fv.idNotaCargo = cj_encabezadorecibopago.numero_tipo_documento)
		INNER JOIN cj_cc_cliente ON (fv.idCliente = cj_cc_cliente.id)
		INNER JOIN cj_det_nota_cargo cxc_pago ON (fv.idNotaCargo = cxc_pago.idNotaCargo)
		INNER JOIN cj_detallerecibopago ON (cxc_pago.id_det_nota_cargo = cj_detallerecibopago.idPago)
			AND (cj_detallerecibopago.idComprobantePagoFactura = cj_encabezadorecibopago.idComprobante)
	WHERE cj_encabezadorecibopago.id_departamento IN (0,1,3)
		AND fv.idDepartamentoOrigenNotaCargo IN (0,1,3)
		AND cxc_pago.tomadoEnComprobante = 1
		".$tipoPagoNotaCargo."
		AND cxc_pago.tomadoEnCierre = 1
		".$andEmpresaPagoND."
		".$idNotaCargo."");
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
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "10%", $pageNum, "3", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Documento");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "10%", $pageNum, "2", $campOrd, $tpOrd, $valBusq, $maxRows, "Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "10%", $pageNum, "4", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Recibo");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "20%", $pageNum, "10", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "20%", $pageNum, "13", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Cuenta Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "20%", $pageNum, "8", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Cheque");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "10%", $pageNum, "14", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "<td width=\"\" colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if ($row['formaPago'] == 1 || $row['formaPago'] == 'EF') {
			$img = "<img src='../img/iconos/money.png' title='Efectivo'/>";
			$formaPago = "Efectivo";
		} else if ($row['formaPago'] == 2 || $row['formaPago'] == 'CH') {
			$img = "<img src='../img/iconos/cheque.png' title='Cheque'/>";
			$formaPago = "Cheque";
		} else if ($row['formaPago'] == 11 || $row['formaPago'] == 'CB') {
			$img = "<img src='../img/iconos/text_signature.png' title='Cash Back'/>";
			$formaPago = "Cash Back";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$row['tipoDoc']."</td>";
			$htmlTb .= "<td align=\"center\">".$formaPago."</td>";
			$htmlTb .= "<td align=\"center\">".$row['nro_comprobante']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_banco_origen'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cuentaEmpresa']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroDocumento']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoPagado'],2,'.',',')."</td>";
			$htmlTb .= "<td align=\"center\">".$img."</td>";
			$htmlTb .= "<td align=\"center\"><input type='checkbox' id='cbxItm' name='cbxItm[]' class='puntero' title='Seleccionar Pago' value='".$row['idPago']."|".$row['tabla']."'/></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("$('btnCargarPlanilla').style.display = '';");
	
	return $objResponse;
}

function validarCargarAPlanilla($formPagos, $formPlanilla){
	$objResponse = new xajaxResponse();
	
	$arregloPagos = "";
	
	$hddObjDetallePago = $formPlanilla['hddObjDetallePago'];
	
	if (isset($formPagos['cbxItm'])){
		foreach($formPagos['cbxItm'] as $indiceCbxItm => $valorCbxItm)
			$arregloPagos .= $valorCbxItm."|";
			
		$objResponse->loadCommands(cargarAPlanilla($arregloPagos, $hddObjDetallePago));
	} else {
		$objResponse->alert("Debe seleccionar al menos un pago.");
	}
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentes");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"calcularTotalDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarAPlanilla");
$xajax->register(XAJAX_FUNCTION,"cargaSelBanco");
$xajax->register(XAJAX_FUNCTION,"cargarCuentas");
$xajax->register(XAJAX_FUNCTION,"depositarPlanilla");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"listaPagoDepositar");
$xajax->register(XAJAX_FUNCTION,"validarCargarAPlanilla");
?>