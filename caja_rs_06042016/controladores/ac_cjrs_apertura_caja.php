<?php


function aperturaCaja($frmApertura){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$fechaApertura = date("Y-m-d"); // FECHA DE APERTURA SIEMPRE SERÁ LA FECHA ACTUAL.
	$horaApertura = date("h:i:s"); // HORA DE APERTURA SIEMPRE SERÁ LA HORA ACTUAL.
	$cargaEfectivo = $frmApertura['txtCargaEfectivo'];
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	/*$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) { errorAperturaCaja($objResponse); return $objResponse->alert(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_); }
	$totalRows400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) { errorAperturaCaja($objResponse); return $objResponse->alert(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_); }
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	} else if (!($totalRows400 > 0)) {
		errorAperturaCaja($objResponse); return $objResponse->alert("No puede aperturar la caja por esta empresa");
	}*/
	
	//CONSULA DATOS DE LA CAJA PARA SABER SI YA TIENE APERTURA
	$sql = sprintf("SELECT
		fechaAperturaCaja,
		saldoCaja,
		cargaEfectivoCaja
	FROM sa_iv_apertura
	WHERE idCaja = 2
		AND fechaAperturaCaja = %s
		AND id_empresa = %s",
		valTpDato($fechaApertura,"text"),
		valTpDato($idEmpresa,"int"));
	$consulta = mysql_query($sql);
	if (!$consulta){ errorAperturaCaja($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sql); }
	
	//VERIFICA SI LA CAJA YA TIENE APERTURA
	if(mysql_num_rows($consulta) > 0) {
		errorAperturaCaja($objResponse); 
		$objResponse->alert("Esta Caja ya tiene un registro de apertura con fecha ".date("d-m-Y",strtotime($fechaApertura))."");
	} else {
		// BUSCO LA CAJA QUE ESTA ABIERTA TOTAL O PARCIAL
		$sqlConsultarFecha = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura
		WHERE idCaja = 2
			AND statusAperturaCaja IN (1,2)
			AND id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$consultaConsultaFecha = mysql_query($sqlConsultarFecha);
		if (!$consultaConsultaFecha){ errorAperturaCaja($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlConsultarFecha); }
		
		if(mysql_num_rows($consultaConsultaFecha)) {
			$fechaAperturaCaja = mysql_fetch_array($consultaConsultaFecha);
			if($fechaAperturaCaja['fechaAperturaCaja'] != $fechaApertura){
				errorAperturaCaja($objResponse);
				$objResponse->alert("La caja esta abierta con fecha anterior a la actual. Cierrela y aperture nuevamente.");
				$objResponse->script("window.location.href='cjrs_cierre_caja.php'");
			} 
		} else {
			//CONSULTA SI EXISTEN PLANILLAS PENDIENTES POR DEPOSITAR
			$sqlExisteRegistrosEnPlanillasPendientesPorDepositar = sprintf("SELECT COUNT(*) AS nroRegistros
			FROM an_detalledeposito
				INNER JOIN an_encabezadodeposito ON (an_detalledeposito.idPlanilla = an_encabezadodeposito.idPlanilla)
			WHERE conformado = %s
				AND an_encabezadodeposito.idCaja = %s
				AND an_encabezadodeposito.id_empresa = %s",
				valTpDato(1,"int"),
				valTpDato(2,"int"), //1 = caja vehiculos ; 2 = caja repuestos y servicios
				valTpDato($idEmpresa,"int"));
			$consultaExistePlanillasPendientesPorDepositar = mysql_query($sqlExisteRegistrosEnPlanillasPendientesPorDepositar);
			if (!$consultaExistePlanillasPendientesPorDepositar){ errorAperturaCaja($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlExisteRegistrosEnPlanillasPendientesPorDepositar); }
			$nroRegistrosEnPlanillaPendientes = mysql_fetch_array($consultaExistePlanillasPendientesPorDepositar);
			
			if($nroRegistrosEnPlanillaPendientes["nroRegistros"] > 0){
				errorAperturaCaja($objResponse);
				$objResponse->alert("No se puede realizar la apertura de caja, debido a que existen planillas sin conformar.");
				$objResponse->script("window.location.href = 'cjrs_depositos_form.php'");
			} else {
				$cargaDeEfectivo = str_replace(",","",$cargaEfectivo);
				
				//INSERTA LA APERTURA DE LA CAJA
				$sql2 = sprintf("INSERT INTO sa_iv_apertura (idCaja, fechaAperturaCaja, horaApertura, saldoCaja, cargaEfectivoCaja, statusAperturaCaja, saldoEfectivo, saldoCheques, saldoDepositos, saldoTransferencia, saldoTarjetaCredito, saldoTarjetaDebito, saldoAnticipo, saldoNotaCredito, saldoRetencion, id_usuario, id_empresa)
				VALUES(%s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato(2,"int"), //1 = caja vehiculos ; 2 = caja repuestos y servicios
					valTpDato($fechaApertura,"date"),
					valTpDato($cargaDeEfectivo,"real_inglesa"),
					valTpDato($cargaDeEfectivo,"real_inglesa"),
					valTpDato(1,"int"), // 0 = cerrada; 1 = abierta; 2 = cerrada parcial
					valTpDato(0,"int"),
					valTpDato(0,"int"),
					valTpDato(0,"int"),
					valTpDato(0,"int"),
					valTpDato(0,"int"),
					valTpDato(0,"int"),
					valTpDato(0,"int"),
					valTpDato(0,"int"),
					valTpDato(0,"int"),
					valTpDato($idUsuario,"int"),
					valTpDato($idEmpresa,"int"));
				$consulta = mysql_query($sql2);
				if (!$consulta){ errorAperturaCaja($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sql2); }
				$idCajaAperturada = mysql_insert_id();
				
				//INSERTA LA APERTURA EN EL CIERRE
				//INSERTA EN LA FECHA DE CIERRE la fecha de apertura, esto para que no haya diferencias en el historico de cierre.
				//INSERTA MOMENTANEAMENTE EN fechaEjecucionCierre la fecha de apertura, esta será actualizada al realizar el cierre de la caja.
				$queryInsertCierre = sprintf("INSERT INTO sa_iv_cierredecaja (id, tipoCierre, fechaCierre, horaEjecucionCierre, fechaEjecucionCierre, cargaEfectivoCaja, saldoCaja, saldoEfectivo, saldoCheques, saldoDepositos, saldoTransferencia, saldoTarjetaCredito, saldoTarjetaDebito, saldoAnticipo, saldoNotaCredito, saldoRetencion, id_usuario, id_empresa, observacion)
				VALUES (%s, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idCajaAperturada,"int"),
					valTpDato(1,"int"),
					valTpDato($fechaApertura,"date"),
					valTpDato($fechaApertura,"date"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato(0,"real_inglesa"),
					valTpDato($idUsuario,"int"),
					valTpDato($idEmpresa,"int"),
					valTpDato('',"text"));
				$rsInsertCierre = mysql_query($queryInsertCierre);
				if (!$rsInsertCierre){ errorAperturaCaja($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryInsertCierre); }
				
				errorAperturaCaja($objResponse);
				$objResponse->alert("La Caja ha sido abierta.");
				$objResponse->script("window.location.href='index.php'");
			}
		}
	}
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function cargarDatosCaja(){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$fecha = date("Y-m-d");
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	/*$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryEmpresa);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryEmpresa);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}*/
	
	//CONSULTA DATOS DE LA EMPRESA
	$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
		valTpDato($idEmpresa,"int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryEmpresa);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	//ASIGNA DATOS DE LA EMPRESA
	$nombreEmpresa = $rowEmpresa['nombre_empresa'];
	$rifEmpresa = $rowEmpresa['rif'];
	
	$objResponse->assign("txtNombreEmpresa","value",$nombreEmpresa);
	$objResponse->assign("txtRif","value",$rifEmpresa);

	//ASIGNA DATOS DE CAJA
	$objResponse->assign("txtFechaApertura","value",date("d-m-Y",strtotime($fecha)));
	
	//CONSULTA EL ESTATUS DE LA CAJA
	$queryCaja = "SELECT
		ape.fechaAperturaCaja,
		ape.statusAperturaCaja
	FROM sa_iv_apertura as ape
	WHERE ape.statusAperturaCaja IN (1,2)
		AND ape.idCaja = 2
		AND ape.id_empresa = ".$idEmpresa."";
	$rsCaja = mysql_query($queryCaja);
	if (!$rsCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCaja);
	$rowCaja = mysql_fetch_assoc($rsCaja);
	
	if ($rowCaja['statusAperturaCaja'] == 0){
		$objResponse->assign("txtEstadoDeCaja","value",'CERRADA TOTALMENTE');
	}else if ($rowCaja['statusAperturaCaja'] == 1){
		$objResponse->assign("txtEstadoDeCaja","value",'ABIERTA - '.date("d-m-Y",strtotime($rowCaja['fechaAperturaCaja'])));
	}else if ($rowCaja['statusAperturaCaja'] == 2){
		$objResponse->assign("txtEstadoDeCaja","value",'CERRADA PARCIALMENTE');
	}
	
	if(mysql_num_rows($rsCaja) > 0) {
		if($rowCaja['fechaAperturaCaja'] != $fecha){
			$objResponse->alert("La caja esta abierta con fecha: ".date("d-m-Y",strtotime($rowCaja['fechaAperturaCaja'])).". Cierrela y aperture nuevamente.");
		} 
	}
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"aperturaCaja");
$xajax->register(XAJAX_FUNCTION,"cargarDatosCaja");

function errorAperturaCaja($objResponse){
	$objResponse->script("
	byId('btnApertura').disabled = false;");
}
?>