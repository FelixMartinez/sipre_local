<?php


function cerrarCaja(){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	
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
		$andEmpresa = sprintf(" AND id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresa2 = sprintf(" AND vw_forma_pagos_formato_corte_caja_rs.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresa3 = sprintf(" AND an.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresa4 = sprintf(" AND fv.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$groupBy = sprintf(" ,vw_forma_pagos_formato_corte_caja_rs.id_empresa");
			
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresa = '';
		$andEmpresa2 = '';
		$andEmpresa3 = '';
		$andEmpresa4 = '';
		$groupBy = '';
	}
	
	//COPIAR sa_iv_apertura EN sa_iv_cierredecaja
	$sqlSelectApertura = sprintf("SELECT * FROM sa_iv_apertura
	WHERE statusAperturaCaja = %s
	%s",
		valTpDato(1,"int"), // 0 = cerrada; 1 = abierta; 2 = cerrada parcial
		$andEmpresa);
	$rsSelectApertura = mysql_query($sqlSelectApertura);
	if (!$rsSelectApertura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectApertura);
	$rowSelectApertura = mysql_fetch_array($rsSelectApertura);
	
	$idApertura = $rowSelectApertura['id'];
	$fechaActual = $rowSelectApertura['fechaAperturaCaja'];
	
	//Se inserta la fecha de apertura en la fecha de cierre y se inserta la fecha real de cierre en campo: fechaEjecucionCierre (campo informatico para auditorias, revisiones, controles...) // Para que el listado del historico de cierres no se descuadre.
	$fechaCierre = $rowSelectApertura['fechaAperturaCaja']; // FECHA DE APERTURA, PARA EVITAR DIFERENCIAS EN EL HISTORICO DE CIERRES DE CAJA
	$fechaEjecucionCierre = date("Y-m-d"); // FECHA REAL DEL CIERRE DE LA CAJA
	
	$sqlCopiarAperturaEnCierre = sprintf("INSERT INTO sa_iv_cierredecaja (id, tipoCierre, fechaCierre, horaEjecucionCierre, fechaEjecucionCierre, cargaEfectivoCaja, saldoCaja, saldoEfectivo, saldoCheques, saldoDepositos, saldoTransferencia, saldoTarjetaCredito, saldoTarjetaDebito, saldoAnticipo, saldoNotaCredito, saldoRetencion, saldoCashBack, id_usuario, id_empresa, observacion)
	VALUES(%s, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($idApertura,"int"),
		valTpDato(1,"int"),
		valTpDato($fechaCierre,"date"),
		valTpDato($fechaEjecucionCierre,"date"),
		valTpDato($rowSelectApertura['cargaEfectivoCaja'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoCaja'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoEfectivo'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoCheques'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoDepositos'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoTransferencia'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoTarjetaCredito'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoTarjetaDebito'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoAnticipo'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoNotaCredito'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoRetencion'],"real_inglesa"),
		valTpDato($rowSelectApertura['saldoCashBack'],"real_inglesa"),
		valTpDato($idUsuario,"int"),
		valTpDato($idEmpresa,"int"),
		valTpDato(0,"int"));
	$rsCopiarAperturaEnCierre = mysql_query($sqlCopiarAperturaEnCierre);
	if (!$rsCopiarAperturaEnCierre) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlCopiarAperturaEnCierre);
	$idCierre = mysql_insert_id();
	
	$sqlSelectFormasDePago = sprintf("SELECT
		vw_forma_pagos_formato_corte_caja_rs.formaPago,
		vw_forma_pagos_formato_corte_caja_rs.tipoDoc,
		vw_forma_pagos_formato_corte_caja_rs.id_empresa,
		formapagos.nombreFormaPago
	FROM vw_forma_pagos_formato_corte_caja_rs,
		formapagos
	WHERE vw_forma_pagos_formato_corte_caja_rs.formaPago = formapagos.idFormaPago
	".$andEmpresa2."
	GROUP BY vw_forma_pagos_formato_corte_caja_rs.formaPago ".$groupBy."
	ORDER BY vw_forma_pagos_formato_corte_caja_rs.formaPago");
	$rsSelectFormasDePago = mysql_query($sqlSelectFormasDePago);
	if (!$rsSelectFormasDePago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFormasDePago);
	
	while ($rowSelectFormasDePago = mysql_fetch_array($rsSelectFormasDePago)){
		$nombreFormaPago = $rowSelectFormasDePago["nombreFormaPago"];
		$idFormaPago = $rowSelectFormasDePago["formaPago"];
		
		if ($idFormaPago == 1) {
			$formaPagoTexto = "EF";
		} else if ($idFormaPago == 2) {
			$existePagoEnEfectivoOcheque = 1;
			$formaPagoTexto = "CH";
		} else if ($idFormaPago == 3) {
			$formaPagoTexto = "DP";
		} else if ($idFormaPago == 4) {
			$formaPagoTexto = "TB";
		} else if ($idFormaPago == 5) {
			$formaPagoTexto = "TC";
		} else if ($idFormaPago == 6) {
			$formaPagoTexto = "TD";
		} else if ($idFormaPago == 7) {
			$formaPagoTexto = "AN";
		} else if ($idFormaPago == 8) {
			$formaPagoTexto = "NC";
		} else if ($idFormaPago == 9) {
			$formaPagoTexto = "RC";
		} else if ($idFormaPago == 10) {
			$formaPagoTexto = "ISLR";
		} else if ($idFormaPago == 11) {
			$formaPagoTexto = "CB";
		}
		
		$contTC = 0;
		$contTD = 0;
		$arregloCuentaTarjetaCredito = array();
		$arregloCuentaTarjetaDebito = array();
		
		$sqlMostrarPorFormaPago = "SELECT 
			pa.idDetalleAnticipo AS idPago,
			pa.tipoPagoDetalleAnticipo AS formaPago,
			'ANTICIPO' AS tipoDoc,
			pg_reportesimpresion.numeroReporteImpresion AS nro_comprobante,
			an.numeroAnticipo AS idDocumento,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			pa.numeroControlDetalleAnticipo AS numeroDocumento,
			pa.bancoClienteDetalleAnticipo AS bancoOrigen,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoClienteDetalleAnticipo) AS nombre_banco_origen,
			pa.bancoCompaniaDetalleAnticipo AS bancoDestino,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoCompaniaDetalleAnticipo) AS nombre_banco_destino,
			pa.numeroCuentaCompania AS cuentaEmpresa,
			pa.montoDetalleAnticipo AS montoPagado,
			'cj_cc_detalleanticipo' AS tabla
		FROM
			cj_cc_anticipo an
			INNER JOIN pg_reportesimpresion ON (an.idAnticipo = pg_reportesimpresion.idDocumento)
			INNER JOIN cj_cc_cliente ON (an.idCliente = cj_cc_cliente.id),
			cj_cc_detalleanticipo pa
		WHERE
			pa.fechaPagoAnticipo = '".$fechaActual."'
			AND pg_reportesimpresion.id_departamento IN (0,1,3)
			AND an.idAnticipo = pa.idAnticipo
			AND an.idDepartamento IN (0,1,3)
			AND an.estatus =1
			".$andEmpresa3."
			AND pa.tipoPagoDetalleAnticipo = '".$formaPagoTexto."'
			
		UNION
		
		SELECT
			pa.idPago,
			pa.formaPago,
			'FACTURA' AS tipoDoc,
			cj_encabezadorecibopago.numeroComprobante AS nro_comprobante,
			fv.numeroFactura AS idDocumento,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			(CASE pa.formaPago
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = pa.numeroDocumento)
				ELSE
					pa.numeroDocumento
			END) AS numeroDocumento,
			pa.bancoOrigen,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoOrigen) AS nombre_banco_origen,
			pa.bancoDestino,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoDestino) AS nombre_banco_destino,
			pa.cuentaEmpresa,
			pa.montoPagado,
			'sa_iv_pagos' AS tabla
		FROM cj_cc_encabezadofactura fv
			INNER JOIN cj_encabezadorecibopago ON (fv.idFactura = cj_encabezadorecibopago.numero_tipo_documento)
			INNER JOIN cj_cc_cliente ON (fv.idCliente = cj_cc_cliente.id)
			INNER JOIN sa_iv_pagos pa ON (fv.idFactura = pa.id_factura)
			INNER JOIN cj_detallerecibopago ON (cj_encabezadorecibopago.idComprobante = cj_detallerecibopago.idComprobantePagoFactura)
				AND (pa.idPago = cj_detallerecibopago.idPago)
		WHERE pa.fechaPago = '".$fechaActual."'
			AND cj_encabezadorecibopago.id_departamento IN (0,1,3)
			AND fv.idFactura = pa.id_factura
			AND pa.tomadoEnComprobante = 1
			AND cj_encabezadorecibopago.fechaComprobante = '".$fechaActual."'
			".$andEmpresa4."
			AND fv.idDepartamentoOrigenFactura IN (0,1,3)
			AND pa.formaPago = ".$idFormaPago."
			
		UNION
		
		SELECT 
			pa.id_det_nota_cargo AS idPago,
			pa.idFormaPago AS formaPago,
			'NOTA CARGO' AS tipoDoc,
			cj_encabezadorecibopago.numeroComprobante AS nro_comprobante,
			fv.numeroNotaCargo AS idDocumento,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			(CASE pa.idFormaPago
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = pa.numeroDocumento)
				ELSE
					pa.numeroDocumento
			END) AS numeroDocumento,
			pa.bancoOrigen,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoOrigen) AS nombre_banco_origen,
			pa.bancoDestino,
			(SELECT nombreBanco FROM bancos WHERE idBanco = pa.bancoDestino) AS nombre_banco_destino,
			pa.cuentaEmpresa,
			pa.monto_pago AS montoPagado,
			'cj_det_nota_cargo' AS tabla
		FROM cj_cc_notadecargo fv
			INNER JOIN cj_encabezadorecibopago ON (fv.idNotaCargo = cj_encabezadorecibopago.numero_tipo_documento)
			INNER JOIN cj_cc_cliente ON (fv.idCliente = cj_cc_cliente.id)
			INNER JOIN cj_det_nota_cargo pa ON (fv.idNotaCargo = pa.idNotaCargo)
			INNER JOIN cj_detallerecibopago ON (pa.id_det_nota_cargo = cj_detallerecibopago.idPago)
				AND (cj_detallerecibopago.idComprobantePagoFactura = cj_encabezadorecibopago.idComprobante)
		WHERE pa.fechaPago = '".$fechaActual."'
			AND cj_encabezadorecibopago.id_departamento IN (0,1,3)
			AND fv.idDepartamentoOrigenNotaCargo IN (0,1,3)
			AND pa.tomadoEnCierre = 0
			AND pa.tomadoEnComprobante = 1
			".$andEmpresa4."
			AND cj_encabezadorecibopago.fechaComprobante = '".$fechaActual."'
			AND pa.idFormaPago = ".$idFormaPago;
		$rsMostrarPorFormaPago = mysql_query($sqlMostrarPorFormaPago);
		if (!$rsMostrarPorFormaPago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlMostrarPorFormaPago);
		
		while ($rowMostrarPorFormaPago = mysql_fetch_array($rsMostrarPorFormaPago)){
			if ($rowMostrarPorFormaPago['tipoDoc'] == 'ANTICIPO'){
				$nombreTabla = "cj_cc_det_pagos_deposito_anticipos";
				$idTabla = "idDetalleAnticipo";
			} else if ($rowMostrarPorFormaPago['tipoDoc'] == 'FACTURA'){
				$nombreTabla = "an_det_pagos_deposito_factura";
				$idTabla = "idPago";
			} else if ($rowMostrarPorFormaPago['tipoDoc'] == 'NOTA CARGO'){
				$nombreTabla = "cj_det_pagos_deposito_nota_cargo";
				$idTabla = "id_det_nota_cargo";
			}
			
			if ($idFormaPago == 1 || $idFormaPago == 2 || $idFormaPago == 11) // 1 = Efectivo ; 2 = Cheques; 11 = Cash Back
				$sqlUpdatePago = sprintf("UPDATE %s SET tomadoEnCierre = 1, idCierre = %s WHERE %s = %s",
					$rowMostrarPorFormaPago['tabla'], $idCierre, $idTabla, $rowMostrarPorFormaPago['idPago']);
			else
				$sqlUpdatePago = sprintf("UPDATE %s SET tomadoEnCierre = 2, idCierre = %s WHERE %s = %s",
					$rowMostrarPorFormaPago['tabla'], $idCierre, $idTabla, $rowMostrarPorFormaPago['idPago']);
			$rsUpdatePago = mysql_query($sqlUpdatePago);
			if (!$rsUpdatePago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdatePago);
			
			if ($idFormaPago == 3){ // Deposito
				$montoTotalEfectivoDeposito = 0;
				$montoTotalChequeDeposito = 0;
				
				//CONSULTA MONTO TOTAL EFECTIVO Y CHEQUE
				$sqlSelectMontos = sprintf("SELECT SUM(monto) AS monto, idFormaPago FROM %s WHERE %s = %s GROUP BY idFormaPago",
					$nombreTabla,
					$idTabla,
					valTpDato($rowMostrarPorFormaPago['idPago'],"int"));
				$rsSelectMontos = mysql_query($sqlSelectMontos);
				if (!$rsSelectMontos) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectMontos);
				while ($rowSelectMontos = mysql_fetch_array($rsSelectMontos)){
					if ($rowSelectMontos['idFormaPago'] == 1)
						$montoTotalEfectivoDeposito = $rowSelectMontos['monto'];
					else if ($rowSelectMontos['idFormaPago'] == 2)
						$montoTotalChequeDeposito = $rowSelectMontos['monto'];
				}
				
				//CONSULTA PARA EL ID DE LA CUENTA
				$sqlSelectIdCuenta = sprintf("SELECT idCuentas FROM cuentas WHERE numeroCuentaCompania LIKE %s",
					valTpDato($rowMostrarPorFormaPago['cuentaEmpresa'],"text"));
				$rsSelectIdCuenta = mysql_query($sqlSelectIdCuenta);
				if (!$rsSelectIdCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectIdCuenta);
				$rowSelectIdCuenta = mysql_fetch_array($rsSelectIdCuenta);
				
				//CONSULTA CORRELATIVO NUMERO DE FOLIO
				$sqlSelectFolioTesoreriaDeposito = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 2");
				$rsSelectFolioTesoreriaDeposito = mysql_query($sqlSelectFolioTesoreriaDeposito);
				if (!$rsSelectFolioTesoreriaDeposito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFolioTesoreriaDeposito);
				$rowSelectFolioTesoreriaDeposito = mysql_fetch_array($rsSelectFolioTesoreriaDeposito);
				
				$folioDeposito = $rowSelectFolioTesoreriaDeposito['numero_actual'];
				
				//AUMENTAR EL CORRELATIVO DEL FOLIO
				$sqlUpdateFolioTesoreriaDeposito = sprintf("UPDATE te_folios SET numero_actual = %s WHERE id_folios = 2",
					valTpDato($folioDeposito + 1,"int"));
				$rsUpdateFolioTesoreriaDeposito = mysql_query($sqlUpdateFolioTesoreriaDeposito);
				if (!$rsUpdateFolioTesoreriaDeposito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateFolioTesoreriaDeposito);
				
				//INSERTAR EL DEPOSITO EN TESORERIA
				$sqlInsertDepositoTesoreria = sprintf("INSERT INTO te_depositos (id_numero_cuenta, fecha_registro, fecha_aplicacion, numero_deposito_banco, estado_documento, origen, id_usuario, monto_total_deposito, id_empresa, desincorporado, monto_efectivo, monto_cheques_total, observacion, folio_deposito)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 'INGRESO CAJA R&S DIA %s DEPOSITOS CLIENTES (%s)', %s)",
					valTpDato($rowSelectIdCuenta['idCuentas'],"int"),
					valTpDato($fechaActual,"date"),
					valTpDato($fechaActual,"date"),
					valTpDato($rowMostrarPorFormaPago['numeroDocumento'],"text"),
					valTpDato(2,"int"),
					valTpDato(2,"int"),
					valTpDato($idUsuario,"int"),
					valTpDato($rowMostrarPorFormaPago['montoPagado'],"double"),
					valTpDato($idEmpresa, "int"),
					valTpDato(1, "int"),
					valTpDato($montoTotalEfectivoDeposito, "int"),
					valTpDato($montoTotalChequeDeposito, "int"),
					date("d-m-Y",strtotime($fechaActual)),$rowMostrarPorFormaPago['numeroDocumento'],
					valTpDato($folioDeposito,"int"));
				$rsInsertDepositoTesoreria = mysql_query($sqlInsertDepositoTesoreria);
				if (!$rsInsertDepositoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDepositoTesoreria);
				$idDeposito = mysql_insert_id();
				
				//INSERTAR DETALLES DEPOSITO
				$sqlSelectDetallesDepositoPago = sprintf("SELECT idBanco, numero_cuenta, numero_cheque, monto FROM %s WHERE %s = %s AND idFormaPago = 2",
					$nombreTabla,
					$idTabla,
					valTpDato($rowMostrarPorFormaPago['idPago'],"int"));
				$rsSelectDetallesDepositoPago = mysql_query($sqlSelectDetallesDepositoPago);
				if (!$rsSelectDetallesDepositoPago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectDetallesDepositoPago);
				while ($rowSelectDetallesDepositoPago = mysql_fetch_array($rsSelectDetallesDepositoPago)){
					$sqlInsertDetalleDeposito = sprintf("INSERT INTO te_deposito_detalle (id_deposito, id_banco, numero_cuenta_cliente, numero_cheques, monto)
					VALUES (%s, %s, %s, %s, %s)",
						valTpDato($idDeposito,"int"),
						valTpDato($rowSelectDetallesDepositoPago['idBanco'],"int"),
						valTpDato($rowSelectDetallesDepositoPago['numero_cuenta'],"text"),
						valTpDato($rowSelectDetallesDepositoPago['numero_cheque'],"text"),
						valTpDato($rowSelectDetallesDepositoPago['monto'],"double"));
					$rsInsertDetalleDeposito = mysql_query($sqlInsertDetalleDeposito);
					if (!$rsInsertDetalleDeposito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDetalleDeposito);
				}
				
				//INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA
				$sqlInsertEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES ('DP', %s, NOW(), %s, %s, %s, 1, %s, 1, 'INGRESO CAJA R&S DIA %s DEPOSITO CLIENTE (%s)', 2, 0)",
					valTpDato($idDeposito,"int"),
					valTpDato($rowSelectIdCuenta['idCuentas'],"int"),
					valTpDato($idEmpresa,"int"),
					valTpDato($rowMostrarPorFormaPago['montoPagado'],"double"),
					valTpDato($rowMostrarPorFormaPago['numeroDocumento'],"text"),
					date("d-m-Y",strtotime($fechaActual)),$rowMostrarPorFormaPago['numeroDocumento']);
				$rsInsertEstadoCuenta = mysql_query($sqlInsertEstadoCuenta);
				if (!$rsInsertEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuenta);
				
				//AFECTAR EL SALDO EN CUENTA
				$sqlUpdateSaldoTemCuenta = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem + %s WHERE idCuentas = %s",
					valTpDato($rowMostrarPorFormaPago['montoPagado'],"double"),
					valTpDato($rowSelectIdCuenta['idCuentas'],"int"));
				$rsUpdateSaldoTemCuenta = mysql_query($sqlUpdateSaldoTemCuenta);
				if (!$rsUpdateSaldoTemCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateSaldoTemCuenta);
			}
			else if ($idFormaPago == 4){ // Transferencia Bancaria
				//CONSULTA PARA EL ID DE LA CUENTA
				$sqlSelectIdCuenta = sprintf("SELECT idCuentas FROM cuentas WHERE numeroCuentaCompania LIKE %s",
					valTpDato($rowMostrarPorFormaPago['cuentaEmpresa'],"text"));
				$rsSelectIdCuenta = mysql_query($sqlSelectIdCuenta);
				if (!$rsSelectIdCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectIdCuenta);
				$rowSelectIdCuenta = mysql_fetch_array($rsSelectIdCuenta);
				
				//CONSULTA CORRELATIVO NUMERO DE FOLIO
				$sqlSelectFolioTesoreriaNotaCredito = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 3");
				$rsSelectFolioTesoreriaNotaCredito = mysql_query($sqlSelectFolioTesoreriaNotaCredito );
				if (!$rsSelectFolioTesoreriaNotaCredito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFolioTesoreriaNotaCredito);
				$rowSelectFolioTesoreriaNotaCredito = mysql_fetch_array($rsSelectFolioTesoreriaNotaCredito);
				
				$folioNotaCredito = $rowSelectFolioTesoreriaNotaCredito['numero_actual'];
				
				//AUMENTAR EL CORRELATIVO DEL FOLIO
				$sqlUpdateFolioTesoreriaNotaCredito = sprintf("UPDATE te_folios SET numero_actual = %s WHERE id_folios = 3",
					valTpDato($folioNotaCredito + 1,"int"));
				$rsUpdateFolioTesoreriaNotaCredito = mysql_query($sqlUpdateFolioTesoreriaNotaCredito );
				if (!$rsUpdateFolioTesoreriaNotaCredito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateFolioTesoreriaNotaCredito);
				
				//INSERTAR LA NOTA DE CREDITO EN TESORERIA
				$sqlInsertNotaCreditoTesoreria = sprintf("INSERT INTO te_nota_credito (id_numero_cuenta, fecha_registro, fecha_aplicacion, folio_tesoreria, id_beneficiario_proveedor, observaciones, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_credito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_credito, tipo_nota_credito, id_motivo)
				VALUES (%s, %s, %s, %s, 0, 'INGRESO CAJA R&S DIA %s TRANSFERENCIA (%s)', 0, 2, 2, %s, %s, '', %s, 1, %s, 4, 289)",
					valTpDato($rowSelectIdCuenta['idCuentas'],"int"),
					valTpDato($fechaActual,"date"),
					valTpDato($fechaActual,"date"),
					valTpDato($folioNotaCredito,"int"),
					date("d-m-Y",strtotime($fechaActual)),$rowMostrarPorFormaPago['numeroDocumento'],
					valTpDato($idUsuario,"int"),
					valTpDato($rowMostrarPorFormaPago['montoPagado'],"double"),
					valTpDato($idEmpresa, "int"),
					valTpDato($rowMostrarPorFormaPago['numeroDocumento'],"text"));
				$rsInsertNotaCreditoTesoreria = mysql_query($sqlInsertNotaCreditoTesoreria);
				if (!$rsInsertNotaCreditoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertNotaCreditoTesoreria);
				$idNotaCredito = mysql_insert_id();
				
				//INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA
				$sqlInsertEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES ('NC', %s, NOW(), %s, %s, %s, 1, %s, 1, 'INGRESO CAJA R&S DIA %s TRANSFERENCIA (%s)', 2, 0)",
					valTpDato($idNotaCredito,"int"),
					valTpDato($rowSelectIdCuenta['idCuentas'],"int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($rowMostrarPorFormaPago['montoPagado'],"double"),
					valTpDato($rowMostrarPorFormaPago['numeroDocumento'],"text"),
					date("d-m-Y",strtotime($fechaActual)),$rowMostrarPorFormaPago['numeroDocumento']);
				$rsInsertEstadoCuenta = mysql_query($sqlInsertEstadoCuenta);
				if (!$rsInsertEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuenta);
				
				//AFECTAR EL SALDO EN CUENTA
				$sqlUpdateSaldoTemCuenta = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem + %s WHERE idCuentas = %s",
					valTpDato($rowMostrarPorFormaPago['montoPagado'],"double"),
					valTpDato($rowSelectIdCuenta['idCuentas'],"int"));
				$rsUpdateSaldoTemCuenta = mysql_query($sqlUpdateSaldoTemCuenta);
				if (!$rsUpdateSaldoTemCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateSaldoTemCuenta);
			}
			else if ($idFormaPago == 5){ // Tarjeta De Credito
					//CONSULTA PARA EL ID DE LA CUENTA
					$sqlSelectIdCuenta = sprintf("SELECT idCuentas FROM cuentas WHERE numeroCuentaCompania LIKE %s",
						valTpDato($rowMostrarPorFormaPago['cuentaEmpresa'],"text"));
					$rsSelectIdCuenta = mysql_query($sqlSelectIdCuenta);
					if (!$rsSelectIdCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectIdCuenta);
					$rowSelectIdCuenta = mysql_fetch_array($rsSelectIdCuenta);
					
					$pos = array_search ($rowSelectIdCuenta['idCuentas'],$arregloCuentaTarjetaCredito);
					if (in_array($rowSelectIdCuenta['idCuentas'],$arregloCuentaTarjetaCredito)){
						//$arregloCuentaTarjetaCredito[$pos] = $rowSelectIdCuenta['idCuentas'];
						$arregloMontoTarjetaCredito[$pos] += $rowMostrarPorFormaPago['montoPagado'];
					}
					else {
						$arregloCuentaTarjetaCredito[$contTC] = $rowSelectIdCuenta['idCuentas'];
						$arregloMontoTarjetaCredito[$contTC] += $rowMostrarPorFormaPago['montoPagado'];
						$contTC ++;
					}
				}
			else if ($idFormaPago == 6){ //Tarjeta De Debito
					//CONSULTA PARA EL ID DE LA CUENTA
					$sqlSelectIdCuenta = sprintf("SELECT idCuentas FROM cuentas WHERE numeroCuentaCompania LIKE %s",
						valTpDato($rowMostrarPorFormaPago['cuentaEmpresa'],"text"));
					$rsSelectIdCuenta = mysql_query($sqlSelectIdCuenta);
					if (!$rsSelectIdCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectIdCuenta);
					$rowSelectIdCuenta = mysql_fetch_array($rsSelectIdCuenta);
					
					$pos = array_search ($rowSelectIdCuenta['idCuentas'],$arregloCuentaTarjetaDebito);
					if (in_array($rowSelectIdCuenta['idCuentas'],$arregloCuentaTarjetaDebito)){
						//$arregloCuentaTarjetaDebito[$pos] = $rowSelectIdCuenta['idCuentas'];
						$arregloMontoTarjetaDebito[$pos] += $rowMostrarPorFormaPago['montoPagado'];
					}
					else {
						$arregloCuentaTarjetaDebito[$contTD] = $rowSelectIdCuenta['idCuentas'];
						$arregloMontoTarjetaDebito[$contTD] += $rowMostrarPorFormaPago['montoPagado'];
						$contTD ++;
					}
				}
		}
		
		if (isset($arregloCuentaTarjetaCredito)){
			
			foreach($arregloCuentaTarjetaCredito as $indiceTC => $valorTC){
				//CONSULTA CORRELATIVO NUMERO DE FOLIO NOTA CREDITO
				$sqlSelectFolioTesoreriaNotaCredito = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 3");
				$rsSelectFolioTesoreriaNotaCredito = mysql_query($sqlSelectFolioTesoreriaNotaCredito );
				if (!$rsSelectFolioTesoreriaNotaCredito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFolioTesoreriaNotaCredito);
				$rowSelectFolioTesoreriaNotaCredito = mysql_fetch_array($rsSelectFolioTesoreriaNotaCredito);
				
				$folioNotaCredito = $rowSelectFolioTesoreriaNotaCredito['numero_actual'];
				
				//AUMENTAR EL CORRELATIVO DEL FOLIO NOTA CREDITO
				$sqlUpdateFolioTesoreriaNotaCredito = sprintf("UPDATE te_folios SET numero_actual = %s WHERE id_folios = 3",valTpDato($folioNotaCredito + 1,"int"));
				$rsUpdateFolioTesoreriaNotaCredito = mysql_query($sqlUpdateFolioTesoreriaNotaCredito );
				if (!$rsUpdateFolioTesoreriaNotaCredito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateFolioTesoreriaNotaCredito);
				
				//SE VA A COMENTAR ESTO PORQUE A TESORERIA NO DEBE IR LA NOTA DE DEBITO 28-05-2012
				//CONSULTA CORRELATIVO NUMERO DE FOLIO NOTA DEBITO
				/*$sqlSelectFolioTesoreriaNotaDebito = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 1");
				$rsSelectFolioTesoreriaNotaDebito = mysql_query($sqlSelectFolioTesoreriaNotaDebito);
				if (!$rsSelectFolioTesoreriaNotaDebito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFolioTesoreriaNotaDebito);
				$rowSelectFolioTesoreriaNotaDebito = mysql_fetch_array($rsSelectFolioTesoreriaNotaDebito);
				
				$folioNotaDebito = $rowSelectFolioTesoreriaNotaDebito['numero_actual'];
				
				//AUMENTAR EL CORRELATIVO DEL FOLIO NOTA DEBITO
				$sqlUpdateFolioTesoreriaNotaDebito = sprintf("UPDATE te_folios SET numero_actual = %s WHERE id_folios = 1",valTpDato($folioNotaDebito + 1,"int"));
				$rsUpdateFolioTesoreriaNotaDebito = mysql_query($sqlUpdateFolioTesoreriaNotaDebito);
				if (!$rsUpdateFolioTesoreriaNotaDebito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateFolioTesoreriaNotaDebito);
				
				//CONSULTA EL PORCENTAJE DE RETENCION DEL PUNTO POR TC*/
				$sqlSelectPorcentajeRetencion = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto
				 WHERE id_cuenta = %s AND id_tipo_tarjeta != 6
				 GROUP BY id_cuenta",
					valTpDato($valorTC,"int"));
				$rsSelectPorcentajeRetencion = mysql_query($sqlSelectPorcentajeRetencion);
				if (!$rsSelectPorcentajeRetencion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectPorcentajeRetencion);
				$rowSelectPorcentajeRetencion = mysql_fetch_array($rsSelectPorcentajeRetencion);
				$porcentajeRetencionComision = $rowSelectPorcentajeRetencion['porcentaje_comision'];
				$porcentajeRetencionISLR = $rowSelectPorcentajeRetencion['porcentaje_islr'];

				//CAMBIO 13-06-2012 PORQUE A TESORERIA VA EL MONTO MENOS LA COMISION Y EL ISLR
				$montoNotaCreditoTC = $arregloMontoTarjetaCredito[$indiceTC] - (($arregloMontoTarjetaCredito[$indiceTC] * $porcentajeRetencionComision / 100) + $arregloMontoTarjetaCredito[$indiceTC] / 1.12 * $porcentajeRetencionISLR / 100);
				//$montoNotaDebitoTC = $arregloMontoTarjetaCredito[$indiceTC] * $porcentajeRetencion / 100;
				
				//INSERTAR LA NOTA DE CREDITO EN TESORERIA
				$sqlInsertNotaCreditoTesoreria = sprintf("INSERT INTO te_nota_credito (id_numero_cuenta, fecha_registro, fecha_aplicacion, folio_tesoreria, id_beneficiario_proveedor, observaciones, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_credito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_credito, tipo_nota_credito, id_motivo)
				VALUES (%s, %s, %s, %s, 0, 'INGRESO CAJA R&S DIA %s TARJETA CREDITO', 0, 2, 2, %s, %s, '', %s, 1, 0, 3, 289)",
					valTpDato($valorTC,"int"),
					valTpDato($fechaActual,"date"),
					valTpDato($fechaActual,"date"),
					valTpDato($folioNotaCredito,"int"),
					date("d-m-Y",strtotime($fechaActual)),
					valTpDato($idUsuario,"int"),
					valTpDato($montoNotaCreditoTC,"double"),
					valTpDato($idEmpresa, "int"));
				$rsInsertNotaCreditoTesoreria = mysql_query($sqlInsertNotaCreditoTesoreria);
				if (!$rsInsertNotaCreditoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertNotaCreditoTesoreria);
				$idNotaCredito = mysql_insert_id();
				
				//INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA (NOTA CREDITO)
				$sqlInsertEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES ('NC', %s, NOW(), %s, %s, %s, 1, 0, 1, 'INGRESO CAJA R&S DIA %s TARJETA DE CREDITO', 2, 0)",
					valTpDato($idNotaCredito,"int"),
					valTpDato($valorTC,"int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($montoNotaCreditoTC,"double"),
					date("d-m-Y",strtotime($fechaActual)));
				$rsInsertEstadoCuenta = mysql_query($sqlInsertEstadoCuenta);
				if (!$rsInsertEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuenta);
				
				//INSERTAR LA NOTA DE DEBITO EN TESORERIA
				/*$sqlInsertNotaDebitoTesoreria = sprintf("INSERT INTO te_nota_debito (id_numero_cuenta, fecha_registro, folio_tesoreria, id_beneficiario_proveedor, observaciones, fecha_aplicacion, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_debito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_debito, id_motivo)
				VALUES (%s, %s, %s, 0, 'EGRESO CAJA R&S DIA %s COMISION BANCARIA POR TARJETA DE CREDITO', %s, 0, 2, 2, %s, %s, 1, %s, 1, 0, 16)",
					valTpDato($valorTC,"int"),
					valTpDato($fechaActual,"date"),
					valTpDato($folioNotaDebito,"int"),
					date("d-m-Y",strtotime($fechaActual)),
					valTpDato($fechaActual,"date"),
					valTpDato($_SESSION['idUsuarioSysGts'],"int"),
					valTpDato($montoNotaDebitoTC,"double"),
					valTpDato($idEmpresa, "int"));
				$rsInsertNotaDebitoTesoreria = mysql_query($sqlInsertNotaDebitoTesoreria);
				if (!$rsInsertNotaDebitoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertNotaDebitoTesoreria);
				$idNotaDebito = mysql_insert_id();
				
				//INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA (NOTA DEBITO)
				$sqlInsertEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES ('ND', %s, NOW(), %s, %s, %s, 0, 0, 1, 'EGRESO POR COMISION BANCARIA CAJA R&S DIA %s TARJETA DE CREDITO', 2, 0)",
					valTpDato($idNotaDebito,"int"),
					valTpDato($valorTC,"int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($montoNotaDebitoTC,"double"),
					date("d-m-Y",strtotime($fechaActual)));
				$rsInsertEstadoCuenta = mysql_query($sqlInsertEstadoCuenta);
				if (!$rsInsertEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuenta);*/
				
				//AFECTAR EL SALDO EN CUENTA - $montoNotaDebitoTC
				$sqlUpdateSaldoTemCuenta = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem + %s WHERE idCuentas = %s",
					valTpDato($montoNotaCreditoTC,"double"),
					valTpDato($valorTC,"int"));
				$rsUpdateSaldoTemCuenta = mysql_query($sqlUpdateSaldoTemCuenta);
				if (!$rsUpdateSaldoTemCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateSaldoTemCuenta);
			}
		}
		
		if (isset($arregloCuentaTarjetaDebito)){
			foreach($arregloCuentaTarjetaDebito as $indiceTD => $valorTD){
				
				//CONSULTA CORRELATIVO NUMERO DE FOLIO NOTA CREDITO
				$sqlSelectFolioTesoreriaNotaCredito = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 3");
				$rsSelectFolioTesoreriaNotaCredito = mysql_query($sqlSelectFolioTesoreriaNotaCredito );
				if (!$rsSelectFolioTesoreriaNotaCredito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFolioTesoreriaNotaCredito);
				$rowSelectFolioTesoreriaNotaCredito = mysql_fetch_array($rsSelectFolioTesoreriaNotaCredito);
				
				$folioNotaCredito = $rowSelectFolioTesoreriaNotaCredito['numero_actual'];
				
				//AUMENTAR EL CORRELATIVO DEL FOLIO NOTA CREDITO
				$sqlUpdateFolioTesoreriaNotaCredito = sprintf("UPDATE te_folios SET numero_actual = %s WHERE id_folios = 3",
					valTpDato($folioNotaCredito + 1,"int"));
				$rsUpdateFolioTesoreriaNotaCredito = mysql_query($sqlUpdateFolioTesoreriaNotaCredito );
				if (!$rsUpdateFolioTesoreriaNotaCredito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateFolioTesoreriaNotaCredito);
				
				//SE VA A COMENTAR ESTO PORQUE A TESORERIA NO DEBE IR LA NOTA DE DEBITO 28-05-2012
				//CONSULTA CORRELATIVO NUMERO DE FOLIO NOTA DEBITO
				/*$sqlSelectFolioTesoreriaNotaDebito = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 1");
				$rsSelectFolioTesoreriaNotaDebito = mysql_query($sqlSelectFolioTesoreriaNotaDebito);
				if (!$rsSelectFolioTesoreriaNotaDebito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFolioTesoreriaNotaDebito);
				$rowSelectFolioTesoreriaNotaDebito = mysql_fetch_array($rsSelectFolioTesoreriaNotaDebito);
				
				$folioNotaDebito = $rowSelectFolioTesoreriaNotaDebito['numero_actual'];
				
				//AUMENTAR EL CORRELATIVO DEL FOLIO NOTA DEBITO
				$sqlUpdateFolioTesoreriaNotaDebito = sprintf("UPDATE te_folios SET numero_actual = %s WHERE id_folios = 1",valTpDato($folioNotaDebito + 1,"int"));
				$rsUpdateFolioTesoreriaNotaDebito = mysql_query($sqlUpdateFolioTesoreriaNotaDebito);
				if (!$rsUpdateFolioTesoreriaNotaDebito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateFolioTesoreriaNotaDebito);*/
				
				//CONSULTA EL PORCENTAJE DE RETENCION DEL PUNTO POR TD
				$sqlSelectPorcentajeRetencion = sprintf("SELECT porcentaje_comision AS porcentaje_retencion 
				FROM te_retencion_punto
				WHERE id_cuenta = %s AND id_tipo_tarjeta = 6
				GROUP BY id_cuenta",
					valTpDato($valorTD,"int"));
				$rsSelectPorcentajeRetencion = mysql_query($sqlSelectPorcentajeRetencion);
				if (!$rsSelectPorcentajeRetencion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectPorcentajeRetencion);
				$rowSelectPorcentajeRetencion = mysql_fetch_array($rsSelectPorcentajeRetencion);
				$porcentajeRetencion = $rowSelectPorcentajeRetencion['porcentaje_retencion'];
				
				$montoNotaCreditoTD = $arregloMontoTarjetaDebito[$indiceTD] - ($arregloMontoTarjetaDebito[$indiceTD] * $porcentajeRetencion / 100);
				//$montoNotaDebitoTD = $arregloMontoTarjetaDebito[$indiceTD] * $porcentajeRetencion / 100;
				
				//INSERTAR LA NOTA DE CREDITO EN TESORERIA
				$sqlInsertNotaCreditoTesoreria = sprintf("INSERT INTO te_nota_credito (id_numero_cuenta, fecha_registro, fecha_aplicacion, folio_tesoreria, id_beneficiario_proveedor, observaciones, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_credito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_credito, tipo_nota_credito, id_motivo)
				VALUES (%s, %s, %s, %s, 0, 'INGRESO CAJA R&S DIA %s TARJETA DEBITO', 0, 2, 2, %s, %s, '', %s, 1, 0, 2, 289)",
					valTpDato($valorTD,"int"),
					valTpDato($fechaActual,"date"),
					valTpDato($fechaActual,"date"),
					valTpDato($folioNotaCredito,"int"),
					date("d-m-Y",strtotime($fechaActual)),
					valTpDato($idUsuario,"int"),
					valTpDato($montoNotaCreditoTD,"double"),
					valTpDato($idEmpresa, "int"));
				$rsInsertNotaCreditoTesoreria = mysql_query($sqlInsertNotaCreditoTesoreria);
				if (!$rsInsertNotaCreditoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertNotaCreditoTesoreria);
				$idNotaCredito = mysql_insert_id();
				
				//INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA (NOTA CREDITO)
				$sqlInsertEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES ('NC', %s, NOW(), %s, %s, %s, 1, 0, 1, 'INGRESO CAJA R&S DIA %s TARJETA DE DEBITO', 2, 0)",
					valTpDato($idNotaCredito,"int"),
					valTpDato($valorTD,"int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($montoNotaCreditoTD,"double"),
					date("d-m-Y",strtotime($fechaActual)));
				$rsInsertEstadoCuenta = mysql_query($sqlInsertEstadoCuenta);
				if (!$rsInsertEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuenta);
				
				//INSERTAR LA NOTA DE DEBITO EN TESORERIA
				/*$sqlInsertNotaDebitoTesoreria = sprintf("INSERT INTO te_nota_debito (id_numero_cuenta, fecha_registro, folio_tesoreria, id_beneficiario_proveedor, observaciones, fecha_aplicacion, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_debito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_debito, id_motivo)
				VALUES (%s, %s, %s, 0, 'EGRESO CAJA R&S DIA %s COMISION BANCARIA POR TARJETA DE CREDITO', %s, 0, 2, 2, %s, %s, 1, %s, 1, 0, 16)",
					valTpDato($valorTD,"int"),
					valTpDato($fechaActual,"date"),
					valTpDato($folioNotaDebito,"int"),
					date("d-m-Y",strtotime($fechaActual)),
					valTpDato($fechaActual,"date"),
					valTpDato($idUsuario,"int"),
					valTpDato($montoNotaDebitoTD,"double"),
					valTpDato($idEmpresa, "int"));
				$rsInsertNotaDebitoTesoreria = mysql_query($sqlInsertNotaDebitoTesoreria);
				if (!$rsInsertNotaDebitoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertNotaDebitoTesoreria);
				$idNotaDebito = mysql_insert_id();
				
				//INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA (NOTA DEBITO)
				$sqlInsertEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES ('ND', %s, NOW(), %s, %s, %s, 0, 0, 1, 'EGRESO POR COMISION BANCARIA CAJA R&S DIA %s TARJETA DE CREDITO', 2, 0)",
					valTpDato($idNotaDebito,"int"),
					valTpDato($valorTD,"int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($montoNotaDebitoTD,"double"),
					date("d-m-Y",strtotime($fechaActual)));
				$rsInsertEstadoCuenta = mysql_query($sqlInsertEstadoCuenta);
				if (!$rsInsertEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuenta);*/
				
				//AFECTAR EL SALDO EN CUENTA - $montoNotaDebitoTD
				$sqlUpdateSaldoTemCuenta = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem + %s WHERE idCuentas = %s",
					valTpDato($montoNotaCreditoTD,"double"),
					valTpDato($valorTD,"int"));
				$rsUpdateSaldoTemCuenta = mysql_query($sqlUpdateSaldoTemCuenta);
				if (!$rsUpdateSaldoTemCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateSaldoTemCuenta);
			}
		}
	}
	
	//ACTUALIZA LA FECHA DE EJECUCION DEL CIERRE DE LA CAJA
	$sql = sprintf("UPDATE sa_iv_cierredecaja SET fechaEjecucionCierre = %s,
		horaEjecucionCierre = NOW()
	WHERE id = %s",
		valTpDato($fechaEjecucionCierre,"date"),
		valTpDato($rowSelectApertura['id'],"int"));
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sql);
	
	//CAMBIA ESTATUS DE CAJA; 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
	$sqlUpdateApertura = sprintf("UPDATE sa_iv_apertura SET statusAperturaCaja = 0 WHERE id = %s",
		valTpDato($rowSelectApertura['id'],"int"));
	$rsUpdateApertura = mysql_query($sqlUpdateApertura);
	if (!$rsUpdateApertura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateApertura);
	
	//CONSULTA TODOS ENCABEZADOS DE LOS PAGOS GENERADOS AL DIA
	$sqlSelectEncabezadoPagoFACT = sprintf("SELECT * FROM cj_cc_encabezado_pago_rs
	WHERE fecha_pago = %s",
		valTpDato($fechaActual,"date"));
	$rsSelectEncabezadoPagoFACT = mysql_query($sqlSelectEncabezadoPagoFACT);
	if (!$rsSelectEncabezadoPagoFACT) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectEncabezadoPagoFACT);
	
	$sqlSelectEncabezadoPagoNC = sprintf("SELECT * FROM cj_cc_encabezado_pago_nc_rs
	WHERE fecha_pago = %s",
		valTpDato($fechaActual,"date"));
	$rsSelectEncabezadoPagoNC = mysql_query($sqlSelectEncabezadoPagoNC);
	if (!$rsSelectEncabezadoPagoNC) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectEncabezadoPagoNC);
	
	//CONSULTA LAS NOTAS DE CREDITO GENERADAS POR TARJETAS DE DEBITO/CREDITO
	$sqlNotaCreditoTe = sprintf("SELECT * FROM te_nota_credito
	WHERE fecha_registro = %s
	AND origen = %s %s",
		valTpDato($fechaActual,"date"),
		valTpDato(2,"int"),
		$andEmpresa);
	$rssqlNotaCreditoTe = mysql_query($sqlNotaCreditoTe);
	if (!$rssqlNotaCreditoTe) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlNotaCreditoTe);
	
	$objResponse->alert("Caja cerrada exitosamente.");
	
	mysql_query("COMMIT;");
	
	while ($rowFACT = mysql_fetch_assoc($rsSelectEncabezadoPagoFACT)) {
		$idEncabezadoPagoFACT = $rowFACT['id_encabezado_rs'];	
		//ENVIA A CONTABILIDAD LOS PAGOS DE FACTURAS DEL DIA
		// MODIFICADO ERNESTO
		if (function_exists("generarCajasEntradaRe")) { generarCajasEntradaRe($idEncabezadoPagoFACT,"",""); }
		// MODIFICADO ERNESTO
	}
	
	while ($rowNC = mysql_fetch_assoc($rsSelectEncabezadoPagoNC)) {
		$idEncabezadoPagoNC = $rowNC['id_encabezado_nc_rs'];	
		//ENVIA A CONTABILIDAD LOS PAGOS DE NOTAS DE CARGO DEL DIA
		// MODIFICADO ERNESTO
		if (function_exists("generarCajasEntradaNotasCargoRe")) { generarCajasEntradaNotasCargoRe($idEncabezadoPagoNC,"",""); }
		// MODIFICADO ERNESTO
	}
	
	while ($rowTe = mysql_fetch_assoc($rssqlNotaCreditoTe)) {
		$idNotaCreditoTe = $rowTe['id_nota_credito'];	
		//ENVIA A CONTABILIDAD LOS PAGOS DE NOTAS DE CARGO DEL DIA
		// MODIFICADO ERNESTO
		if (function_exists("generarNotaCreditoTe_2")) { generarNotaCreditoTe_2($idNotaCreditoTe,"",""); }
		// MODIFICADO ERNESTO
	}
	
	//ENVIA A CONTABILIDAD LAS COMISIONES BANCARIAS DEL DIA, GENERAL
	// MODIFICADO ERNESTO
	if (function_exists("generarComisionesBancarias")) { generarComisionesBancarias(0,$fechaActual,$fechaActual); }
	// MODIFICADO ERNESTO
	
	$objResponse->script("window.location.href = 'index.php'");
	
	return $objResponse;
}

function validarDepositos(){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
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
		$andEmpresa = sprintf(" AND cj_cc_anticipo.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresa2 = sprintf(" AND fact.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresa3 = sprintf(" AND nota.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
			
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresa = '';
		$andEmpresa2 = '';
		$andEmpresa3 = '';
	}
			
	//VERIFICA SI SE HICIERON LOS DEPOSITOS DEL DIA ANTERIOR
	//ANTICIPOS
	$sqlExistePagosAnticiposNoDepositados = "SELECT COUNT(*) AS nroRegistrosPagosAnticipos
	FROM cj_cc_detalleanticipo
		INNER JOIN cj_cc_anticipo ON (cj_cc_detalleanticipo.idAnticipo = cj_cc_anticipo.idAnticipo)	
	WHERE tipoPagoDetalleAnticipo IN ('EF','CH','CB')
		AND tomadoEnCierre = '1'
		AND idCaja = 2
		AND cj_cc_anticipo.estatus = 1
		".$andEmpresa."";
	$consultaExistePagosAnticiposNoDepositados = mysql_query($sqlExistePagosAnticiposNoDepositados);
	if (!$consultaExistePagosAnticiposNoDepositados) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlExistePagosAnticiposNoDepositados);
	
	$nroPagosAnticiposNoDepositados = mysql_fetch_array($consultaExistePagosAnticiposNoDepositados);
	$nroAnticipos = $nroPagosAnticiposNoDepositados["nroRegistrosPagosAnticipos"];
	
	//FACTURAS
	$sqlExistePagosNoDepositados = "SELECT COUNT(*) AS nroRegistrosPagos
	FROM sa_iv_pagos
		INNER JOIN cj_cc_encabezadofactura fact ON (sa_iv_pagos.id_factura = fact.idFactura)	
	WHERE formaPago IN (1,2)
		AND tomadoEnCierre = '1'
		".$andEmpresa2."";
	$consultaExistePagosNoDepositados = mysql_query($sqlExistePagosNoDepositados);
	if (!$consultaExistePagosNoDepositados) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlExistePagosNoDepositados);
	
	$nroPagosNoDepositados = mysql_fetch_array($consultaExistePagosNoDepositados);
	$nroFacturas = $nroPagosNoDepositados["nroRegistrosPagos"];	
	
	//NOTAS DE CARGO
	$sqlExisteNcargoNoDepositados = "SELECT COUNT(*) AS nroRegistrosPagos
	FROM cj_det_nota_cargo
		INNER JOIN cj_cc_notadecargo nota ON (cj_det_nota_cargo.idNotaCargo = nota.idNotaCargo)	
	WHERE idFormaPago IN (1,2)
		AND idDepartamentoOrigenNotaCargo IN (0,1,3)
		AND tomadoEnCierre = '1'
		".$andEmpresa3."";
	$consultaExisteNcargoNoDepositados = mysql_query($sqlExisteNcargoNoDepositados);
	if (!$consultaExistePagosNoDepositados) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlExisteNcargoNoDepositados);
	
	$nroNcargoNoDepositados = mysql_fetch_array($consultaExisteNcargoNoDepositados);
	$nroNotaCargo = $nroNcargoNoDepositados["nroRegistrosPagos"];
	
	if($nroAnticipos > 0 || $nroFacturas > 0 || $nroNotaCargo > 0){
		$objResponse->alert("No se ha realizado el deposito a Bancos. No se puede realizar el Cierre de Caja.");
		$objResponse->script("window.location.href = 'cjrs_depositos_form.php'");
	}
	
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"cerrarCaja");
$xajax->register(XAJAX_FUNCTION,"validarDepositos");
?>