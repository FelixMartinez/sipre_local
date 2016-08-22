<?php


function abrirPortadaCaja($frmVerificacionPortadaCaja){
	$objResponse = new xajaxResponse();
	$idAbrir = $frmVerificacionPortadaCaja['hddIdAbrir'];
	$idApertura = $frmVerificacionPortadaCaja['hddIdAperturaPortada'];
	$idCierre = $frmVerificacionPortadaCaja['hddIdCierrePortada'];
	$txtFechaCierre = $frmVerificacionPortadaCaja['txtFechaCierre'];
	$tipoPago = $frmVerificacionPortadaCaja['slctVerificacionPortadaCaja'];
	
	if ($idAbrir == 1) { // PORTADA DE CAJA
		if ($tipoPago == 2) { // TODOS
			$objResponse->script("window.open('cjrs_cierre_caja_historico.php?idApertura=".$idApertura."&idCierre=".$idCierre."&fecha=".$txtFechaCierre."','_self');");
		}else if ($tipoPago == 1) { // CONTADO
			$objResponse->script("window.open('cjrs_cierre_caja_historico_contado.php?idApertura=".$idApertura."&idCierre=".$idCierre."&fecha=".$txtFechaCierre."','_self');");
		} else if ($tipoPago == 0) { // CREDITO
			$objResponse->script("window.open('cjrs_cierre_caja_historico_credito.php?idApertura=".$idApertura."&idCierre=".$idCierre."&fecha=".$txtFechaCierre."','_self');");
		}
	} else if ($idAbrir == 2) { // RECIBOS POR MEDIO DE PAGO
		if ($tipoPago == 2) { // TODOS
			$objResponse->script("window.open('cjrs_detalle_caja_historico.php?idApertura=".$idApertura."&idCierre=".$idCierre."&fecha=".$txtFechaCierre."','_self');");	
		}else if ($tipoPago == 1) { // CONTADO
			$objResponse->script("window.open('cjrs_detalle_caja_historico_contado.php?idApertura=".$idApertura."&idCierre=".$idCierre."&fecha=".$txtFechaCierre."','_self');");	
		} else if ($tipoPago == 0) { // CREDITO
			$objResponse->script("window.open('cjrs_detalle_caja_historico_credito.php?idApertura=".$idApertura."&idCierre=".$idCierre."&fecha=".$txtFechaCierre."','_self');");	
		}
	}
	
	return $objResponse;
}

function buscarCierre($frmBuscar){
	$objResponse = new xajaxResponse();

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
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$idEmpresa = $frmBuscar['lstEmpresa'];
	}
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$idEmpresa,
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['slctVerificacion']);
		
	$objResponse->loadCommands(listaCierre(0, "apertura.fechaAperturaCaja", "DESC", $valBusq));
	
	return $objResponse;
}

function cargarPagina($idEmpresa){
	
	$objResponse = new xajaxResponse();
	
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
		$objResponse->script("
		byId('trEmpresa').style.display = 'none';");
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$objResponse->script("
		byId('trEmpresa').style.display = '';");
	}
	
	return $objResponse;
}

function exportarListadoCierre($frmBuscar){
	$objResponse = new xajaxResponse();

	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
	$valBusq = sprintf("%s|%s|%s|%s",
		$idEmpresa,
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['slctVerificacion']);
	
	$objResponse->script("window.open('reportes/cjrs_historico_cierre_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formVerificacion($idApertura, $idCierre){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$fechaActual = date("d-m-Y");
	$horaActual = date("h:i:s");
	
	$objResponse->script("
	document.forms['frmVerificacion'].reset();");
	
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
		$andEmpresa = sprintf(" AND cj_verificacion_cierre.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresa = '';
	}
	
	//VERIFICO QUE SE HAYA REALIZADO LA APROBACION
	$sqlAprobacion = sprintf("SELECT
		cj_verificacion_cierre.*,
		pg_empleado.nombre_empleado,
		pg_empleado.apellido,
		pg_usuario.nombre_usuario,
		pg_empresa.nombre_empresa,
		sa_iv_cierredecaja.fechaCierre,
		sa_iv_cierredecaja.horaEjecucionCierre
	FROM cj_verificacion_cierre
		INNER JOIN pg_empleado ON (cj_verificacion_cierre.id_empleado = pg_empleado.id_empleado)
		INNER JOIN pg_usuario ON (cj_verificacion_cierre.id_usuario = pg_usuario.id_usuario)
		INNER JOIN pg_empresa ON (cj_verificacion_cierre.id_empresa = pg_empresa.id_empresa)
		INNER JOIN sa_iv_cierredecaja ON (cj_verificacion_cierre.id_cierre = sa_iv_cierredecaja.idCierre)
	WHERE cj_verificacion_cierre.id_caja = %s
		AND cj_verificacion_cierre.id_apertura = %s
		AND cj_verificacion_cierre.id_cierre = %s
		AND cj_verificacion_cierre.accion = %s %s",
		valTpDato(2,"int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idApertura, "int"),
		valTpDato($idCierre, "int"),
		valTpDato(1, "int"), // 1 = APROBACION ; 2 = VALIDACION
		$andEmpresa);
	$rsAprobacion = mysql_query($sqlAprobacion);
	if (!$rsAprobacion) { errorGuardarAprobacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowAprobacion = mysql_fetch_assoc($rsAprobacion);
	
	//SI YA EXISTE LA APROBACION MUESTRA LOS DATOS GUARDADOS
	if (mysql_num_rows($rsAprobacion) > 0) {
		$objResponse->assign("txtFechaCaja","value",(date("d-m-Y",strtotime($rowAprobacion['fechaCierre']))));
		$objResponse->assign("txtHoraCierre","value",($rowAprobacion['horaEjecucionCierre']));
		$objResponse->assign("txtFechaAprobacion","value",(date("d-m-Y",strtotime($rowAprobacion['fecha']))));
		$objResponse->assign("txtHoraAprobacion","value",($rowAprobacion['hora']));
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($rowAprobacion['id_empresa']));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowAprobacion['nombre_empresa']));
		$objResponse->assign("txtIdEmpleadoAprobacion","value",utf8_encode($rowAprobacion['id_empleado']));
		$objResponse->assign("txtEmpleadoAprobacion","value",utf8_encode($rowAprobacion['nombre_empleado'].' '.$rowAprobacion['apellido']));
		$objResponse->assign("txtIdUsuarioAprobacion","value",utf8_encode($rowAprobacion['id_usuario']));
		$objResponse->assign("txtUsuarioAprobacion","value",utf8_encode($rowAprobacion['nombre_usuario']));
		$objResponse->assign("hddIdApertura","value",utf8_encode($rowAprobacion['id_apertura']));
		$objResponse->assign("hddIdCierre","value",utf8_encode($rowAprobacion['id_cierre']));
	} else {
		//SI NO EXISTE LA APROBACION MUESTRA LOS DATOS DEL USUARIO CONECTADO
		$sql = sprintf("SELECT fechaCierre, horaEjecucionCierre FROM sa_iv_cierredecaja
		WHERE id = %s
			AND idCierre = %s
			AND id_empresa = %s",
			valTpDato($idApertura, "int"),
			valTpDato($idCierre, "int"),
			valTpDato($idEmpresa, "int"));
		$rs = mysql_query($sql);
		if (!$rs) { errorGuardarAprobacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		$sqlEmpleado = "SELECT
			pg_empleado.id_empleado,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido,
			pg_usuario.nombre_usuario,
			pg_empresa.nombre_empresa
		FROM pg_empleado
			INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
			INNER JOIN pg_empresa ON (pg_empresa.id_empresa)
		WHERE pg_usuario.id_usuario = ".$idUsuario;
		$rsEmpleado = mysql_query($sqlEmpleado);
		if (!$rsEmpleado) { errorGuardarAprobacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
		
		$objResponse->assign("txtFechaCaja","value",(date("d-m-Y",strtotime($row['fechaCierre']))));
		$objResponse->assign("txtHoraCierre","value",($row['horaEjecucionCierre']));
		$objResponse->assign("txtFechaAprobacion","value",($fechaActual));
		$objResponse->assign("txtHoraAprobacion","value",($horaActual));
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($idEmpresa));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpleado['nombre_empresa']));
		$objResponse->assign("txtIdEmpleadoAprobacion","value",utf8_encode($rowEmpleado['id_empleado']));
		$objResponse->assign("txtEmpleadoAprobacion","value",utf8_encode($rowEmpleado['nombre_empleado'].' '.$rowEmpleado['apellido']));
		$objResponse->assign("txtIdUsuarioAprobacion","value",utf8_encode($idUsuario));
		$objResponse->assign("txtUsuarioAprobacion","value",utf8_encode($rowEmpleado['nombre_usuario']));
		$objResponse->assign("hddIdApertura","value",utf8_encode($idApertura));
		$objResponse->assign("hddIdCierre","value",utf8_encode($idCierre));
	}
	
	//SI YA EXISTE LA APROBACION
	if (mysql_num_rows($rsAprobacion) > 0) {
		$objResponse->script("byId('btnGuardarAprobacion').style.display='none';");
		$objResponse->script("byId('btnGuardarValidacion').style.display='';");
	} else {
		$objResponse->script("byId('btnGuardarAprobacion').style.display='';");
		$objResponse->script("byId('btnGuardarValidacion').style.display='none';");
	}
	
	//VERIFICO QUE SE HAYA REALIZADO LA VALIDACION
	$sqlValidacion = sprintf("SELECT
		cj_verificacion_cierre.*,
		pg_empleado.nombre_empleado,
		pg_empleado.apellido,
		pg_usuario.nombre_usuario,
		pg_empresa.nombre_empresa,
		sa_iv_cierredecaja.fechaCierre,
		sa_iv_cierredecaja.horaEjecucionCierre
	FROM cj_verificacion_cierre
		INNER JOIN pg_empleado ON (cj_verificacion_cierre.id_empleado = pg_empleado.id_empleado)
		INNER JOIN pg_usuario ON (cj_verificacion_cierre.id_usuario = pg_usuario.id_usuario)
		INNER JOIN pg_empresa ON (cj_verificacion_cierre.id_empresa = pg_empresa.id_empresa)
		INNER JOIN sa_iv_cierredecaja ON (cj_verificacion_cierre.id_cierre = sa_iv_cierredecaja.idCierre)
	WHERE cj_verificacion_cierre.id_caja = %s
		AND cj_verificacion_cierre.id_apertura = %s
		AND cj_verificacion_cierre.id_cierre = %s
		AND cj_verificacion_cierre.accion = %s %s",
		valTpDato(2,"int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idApertura, "int"),
		valTpDato($idCierre, "int"),
		valTpDato(2, "int"), // 1 = APROBACION ; 2 = VALIDACION
		$andEmpresa);
	$rsValidacion = mysql_query($sqlValidacion);
	if (!$rsValidacion) { errorGuardarAprobacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowValidacion = mysql_fetch_assoc($rsValidacion);
	
	//SI YA EXISTE LA VALIDACION MUESTRA LOS DATOS GUARDADOS
	if (mysql_num_rows($rsValidacion) > 0) {
		$objResponse->assign("txtFechaCaja","value",(date("d-m-Y",strtotime($rowValidacion['fechaCierre']))));
		$objResponse->assign("txtHoraCierre","value",($rowValidacion['horaEjecucionCierre']));
		$objResponse->assign("txtFechaValidacion","value",(date("d-m-Y",strtotime($rowValidacion['fecha']))));
		$objResponse->assign("txtHoraValidacion","value",($rowValidacion['hora']));
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($rowValidacion['id_empresa']));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowValidacion['nombre_empresa']));
		$objResponse->assign("txtIdEmpleadoValidacion","value",utf8_encode($rowValidacion['id_empleado']));
		$objResponse->assign("txtEmpleadoValidacion","value",utf8_encode($rowValidacion['nombre_empleado'].' '.$rowValidacion['apellido']));
		$objResponse->assign("txtIdUsuarioValidacion","value",utf8_encode($rowValidacion['id_usuario']));
		$objResponse->assign("txtUsuarioValidacion","value",utf8_encode($rowValidacion['nombre_usuario']));
		$objResponse->assign("hddIdApertura","value",utf8_encode($rowValidacion['id_apertura']));
		$objResponse->assign("hddIdCierre","value",utf8_encode($rowValidacion['id_cierre']));
	} else if (mysql_num_rows($rsAprobacion) > 0) {
		//SI NO EXISTE LA VALIDACION MUESTRA LOS DATOS DEL USUARIO CONECTADO
		$sql = sprintf("SELECT fechaCierre, horaEjecucionCierre FROM sa_iv_cierredecaja
		WHERE id = %s
			AND idCierre = %s
			AND id_empresa = %s",
			valTpDato($idApertura, "int"),
			valTpDato($idCierre, "int"),
			valTpDato($idEmpresa, "int"));
		$rs = mysql_query($sql);
		if (!$rs) { errorGuardarAprobacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		$sqlEmpleado = "SELECT
			pg_empleado.id_empleado,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido,
			pg_usuario.nombre_usuario,
			pg_empresa.nombre_empresa
		FROM pg_empleado
			INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
			INNER JOIN pg_empresa ON (pg_empresa.id_empresa)
		WHERE pg_usuario.id_usuario = ".$idUsuario;
		$rsEmpleado = mysql_query($sqlEmpleado);
		if (!$rsEmpleado) { errorGuardarAprobacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
		
		$objResponse->assign("txtFechaCaja","value",(date("d-m-Y",strtotime($row['fechaCierre']))));
		$objResponse->assign("txtHoraCierre","value",($row['horaEjecucionCierre']));
		$objResponse->assign("txtFechaValidacion","value",($fechaActual));
		$objResponse->assign("txtHoraValidacion","value",($horaActual));
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($idEmpresa));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpleado['nombre_empresa']));
		$objResponse->assign("txtIdEmpleadoValidacion","value",utf8_encode($rowEmpleado['id_empleado']));
		$objResponse->assign("txtEmpleadoValidacion","value",utf8_encode($rowEmpleado['nombre_empleado'].' '.$rowEmpleado['apellido']));
		$objResponse->assign("txtIdUsuarioValidacion","value",utf8_encode($idUsuario));
		$objResponse->assign("txtUsuarioValidacion","value",utf8_encode($rowEmpleado['nombre_usuario']));
		$objResponse->assign("hddIdApertura","value",utf8_encode($idApertura));
		$objResponse->assign("hddIdCierre","value",utf8_encode($idCierre));
	}
	
	//SI YA EXISTE LA VALIDACION
	if (mysql_num_rows($rsValidacion) > 0) {
		$objResponse->script("byId('btnGuardarAprobacion').style.display='none';");
		$objResponse->script("byId('btnGuardarValidacion').style.display='none';");
	} else {
		if (mysql_num_rows($rsAprobacion) > 0) { //SI YA EXISTE LA APROBACION
			$objResponse->script("byId('btnGuardarAprobacion').style.display='none';");
			$objResponse->script("byId('btnGuardarValidacion').style.display='';");
		}
	}
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Verificacion del Cierre de Caja");
	$objResponse->script("
	if (byId('divFlotante').style.display == 'none') {
		byId('divFlotante').style.display='';
		centrarDiv(byId('divFlotante'));
	}");
			
	mysql_query("COMMIT;");
	
	errorGuardarAprobacion($objResponse);
	
	return $objResponse;
}

function formVerificacionPortadaCaja($idApertura, $idCierre, $fechaCierre){
	$objResponse = new xajaxResponse();
	
	$idAbrir = '1'; // PORTADA DE CAJA
	$objResponse->assign("hddIdAbrir","value",($idAbrir));
	$objResponse->assign("hddIdAperturaPortada","value",($idApertura));
	$objResponse->assign("hddIdCierrePortada","value",($idCierre));
	$objResponse->assign("txtFechaCierre","value",($fechaCierre));
		
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Portada de Caja");
	$objResponse->script("
	if (byId('divFlotante2').style.display == 'none') {
		byId('divFlotante2').style.display='';
		centrarDiv(byId('divFlotante2'));
	}");
			
	return $objResponse;
}

function formVerificacionRecibosPorMedioPago($idApertura, $idCierre, $fechaCierre){
	$objResponse = new xajaxResponse();
	
	$idAbrir = '2'; // RECIBOS POR MEDIO DE PAGO
	$objResponse->assign("hddIdAbrir","value",($idAbrir));
	$objResponse->assign("hddIdAperturaPortada","value",($idApertura));
	$objResponse->assign("hddIdCierrePortada","value",($idCierre));
	$objResponse->assign("txtFechaCierre","value",($fechaCierre));
		
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Recibos por Medio de Pago");
	$objResponse->script("
	if (byId('divFlotante2').style.display == 'none') {
		byId('divFlotante2').style.display='';
		centrarDiv(byId('divFlotante2'));
	}");
			
	return $objResponse;
}

function guardarAprobacion($frmVerificacion){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	//CONSULTA EL CARGO DEL USUARIO
	$sqlEmpleado = sprintf("SELECT id_cargo_departamento FROM pg_empleado
	WHERE id_empleado = %s",
		valTpDato($frmVerificacion['txtIdEmpleadoAprobacion'], "int"));
	$rsEmpleado = mysql_query($sqlEmpleado);
	if (!$rsEmpleado) { errorGuardarAprobacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	$idCargoDepartamento = $rowEmpleado['id_cargo_departamento'];
	
	//CONSULTA LA CLAVE FILTRO PARA DAR ACCESO
	$sqlClaveFiltro = sprintf("SELECT id_cargo_departamento, clave_filtro FROM pg_cargo_departamento
	WHERE id_cargo_departamento = %s
		AND clave_filtro IN (3,9,10)", // 3 = Gte. Administracion ; 9 = Jefe Fact. y Cobranza RS ; 10 = Jefe Fact. y Cobranza Vehiculos
		valTpDato($idCargoDepartamento, "int"));
	$rsClaveFiltro = mysql_query($sqlClaveFiltro);
	if (!$rsClaveFiltro) { errorGuardarAprobacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowClaveFiltro = mysql_fetch_assoc($rsClaveFiltro);
	
	//SI EXISTE CLAVE FILTRO SE ADMITE ACCESO PARA VALIDAR
	if (mysql_num_rows($rsClaveFiltro) > 0) {
		$sqlInsert = sprintf("INSERT INTO cj_verificacion_cierre (fecha, hora, id_empresa, id_empleado, id_usuario, accion, id_caja, id_apertura, id_cierre)
		VALUES (NOW(), NOW(), %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($frmVerificacion['txtIdEmpresa'], "int"),
			valTpDato($frmVerificacion['txtIdEmpleadoAprobacion'], "int"),
			valTpDato($frmVerificacion['txtIdUsuarioAprobacion'], "int"),
			valTpDato(1, "int"), //1 = APROBACION ; 2 = VALIDACION
			valTpDato(2,"int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($frmVerificacion['hddIdApertura'], "int"),
			valTpDato($frmVerificacion['hddIdCierre'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$rsInsert = mysql_query($sqlInsert);
		if (!$rsInsert) { errorGuardarAprobacion($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsert); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		errorGuardarAprobacion($objResponse); return $objResponse->alert('Ud. no posee el cargo necesario para realizar esta accion');
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarAprobacion($objResponse);
	$objResponse->alert("Aprobacion realizada con exito");
	
	$objResponse->script("byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listaCierre(0, 'fechaCierre','DESC', $valBusq));
	
	return $objResponse;
}

function guardarValidacion($frmVerificacion){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	//CONSULTA EL CARGO DEL USUARIO
	$sqlEmpleado = sprintf("SELECT id_cargo_departamento FROM pg_empleado
	WHERE id_empleado = %s",
		valTpDato($frmVerificacion['txtIdEmpleadoValidacion'], "int"));
	$rsEmpleado = mysql_query($sqlEmpleado);
	if (!$rsEmpleado) { errorGuardarValidacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	$idCargoDepartamento = $rowEmpleado['id_cargo_departamento'];
	
	//CONSULTA LA CLAVE FILTRO PARA DAR ACCESO
	$sqlClaveFiltro = sprintf("SELECT id_cargo_departamento, clave_filtro FROM pg_cargo_departamento
	WHERE  id_cargo_departamento = %s
		AND clave_filtro IN (3)", // 3 = Gte. Administracion
		valTpDato($idCargoDepartamento, "int"));
	$rsClaveFiltro = mysql_query($sqlClaveFiltro);
	if (!$rsClaveFiltro) { errorGuardarValidacion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowClaveFiltro = mysql_fetch_assoc($rsClaveFiltro);
	
	//SI EXISTE CLAVE FILTRO SE ADMITE ACCESO PARA VALIDAR
	if (mysql_num_rows($rsClaveFiltro) > 0) {
		$sqlInsert = sprintf("INSERT INTO cj_verificacion_cierre (fecha, hora, id_empresa, id_empleado, id_usuario, accion, id_caja, id_apertura, id_cierre)
		VALUES (NOW(), NOW(), %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($frmVerificacion['txtIdEmpresa'], "int"),
			valTpDato($frmVerificacion['txtIdEmpleadoValidacion'], "int"),
			valTpDato($frmVerificacion['txtIdUsuarioValidacion'], "int"),
			valTpDato(2, "int"), //1 = APROBACION ; 2 = VALIDACION
			valTpDato(2,"int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($frmVerificacion['hddIdApertura'], "int"),
			valTpDato($frmVerificacion['hddIdCierre'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$rsInsert = mysql_query($sqlInsert);
		if (!$rsInsert) { errorGuardarValidacion($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsert); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		errorGuardarValidacion($objResponse); return $objResponse->alert('Ud. no posee el cargo necesario para realizar esta accion');
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarValidacion($objResponse);
	$objResponse->alert("Validacion realizada con exito");
	
	$objResponse->script("byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listaCierre(0, 'fechaCierre','DESC', $valBusq));
	
	return $objResponse;
}

function listaCierre($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
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
		$andEmpresa = sprintf(" AND verificacion.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresa2 = sprintf(" AND id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresa3 = sprintf(" AND cj_verificacion_cierre.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresa = '';
		$andEmpresa2 = '';
		$andEmpresa3 = '';
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre.idCierre = (SELECT MAX(cierre2.idCierre) FROM sa_iv_cierredecaja cierre2 WHERE cierre2.id = apertura.id)
	AND apertura.statusAperturaCaja IN (0)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre.fechaCierre BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[3] == 1) { // SI
			$sqlBusq .= $cond.sprintf("cierre.idCierre IN (SELECT id_cierre FROM cj_verificacion_cierre WHERE id_caja = %s %s)",
				valTpDato(2, "int"), $andEmpresa); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		} else if ($valCadBusq[3] == 0) { // NO
			$sqlBusq .= $cond.sprintf("cierre.idCierre NOT IN (SELECT id_cierre FROM cj_verificacion_cierre WHERE id_caja = %s %s)",
				valTpDato(2, "int"), $andEmpresa2); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		}
	}
	
	$query = sprintf("SELECT
		apertura.id,
		apertura.idCaja,
		apertura.fechaAperturaCaja,
		apertura.id_usuario AS idUsuarioApertura,
		cierre.idCierre,
		cierre.fechaCierre,
		cierre.id_usuario AS idUsuarioCierre,
		cierre.tipoCierre,
		cierre.saldoCaja,
		cierre.saldoAnticipo,
		
		(SELECT nombre_usuario FROM pg_usuario usuario
		WHERE usuario.id_usuario = apertura.id_usuario) AS usuarioApertura,
		
		(SELECT nombre_usuario FROM pg_usuario usuario
		WHERE usuario.id_usuario = cierre.id_usuario) AS usuarioCierre,
		
		(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado empleado
		WHERE empleado.id_empleado = (SELECT id_empleado FROM pg_usuario usuario
									WHERE usuario.id_usuario = apertura.id_usuario)) AS empleadoApertura,
									
		(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado empleado
		WHERE empleado.id_empleado = (SELECT id_empleado FROM pg_usuario usuario
									WHERE usuario.id_usuario = cierre.id_usuario)) AS empleadoCierre,
									
		(SELECT COUNT(cj_cc_encabezadofactura.fechaRegistroFactura) FROM cj_cc_encabezadofactura
		WHERE cj_cc_encabezadofactura.fechaRegistroFactura = cierre.fechaCierre
			AND idDepartamentoOrigenFactura IN (0,1,3)
		GROUP BY cj_cc_encabezadofactura.fechaRegistroFactura) AS factCred,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM sa_iv_apertura apertura
		INNER JOIN sa_iv_cierredecaja cierre ON (apertura.id = cierre.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cierre.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY cierre.fechaCierre", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaCierre", "", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaCierre", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCierre", "10%", $pageNum, "fechaAperturaCaja", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Apertura"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "10%", $pageNum, "usuarioApertura", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Usuario Apertura"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "20%", $pageNum, "empleadoApertura", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado Apertura"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "10%", $pageNum, "fechaCierre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Cierre"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "10%", $pageNum, "usuarioCierre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Usuario Cierre"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "20%", $pageNum, "empleadoCierre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado Cierre"));
		$htmlTh .= "<td align=\"center\" colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$sqlVerificacion = sprintf("SELECT MAX(accion) AS accion FROM cj_verificacion_cierre
		WHERE cj_verificacion_cierre.id_caja = %s
			AND cj_verificacion_cierre.id_apertura = %s
			AND cj_verificacion_cierre.id_cierre = %s %s",
			valTpDato($row['idCaja'], "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($row['id'], "int"),
			valTpDato($row['idCierre'], "int"),
			$andEmpresa3);
		$rsVerificacion = mysql_query($sqlVerificacion);
		if (!$rsVerificacion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsVerificacion =  mysql_num_rows($rsVerificacion);
		$rowVerificacion = mysql_fetch_assoc($rsVerificacion);
		
		// SI YA EXISTE LA VERIFICACION MUESTRA LOS DATOS GUARDADOS
		switch ($rowVerificacion['accion']) {
			case 1 : $estatus = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Aprobada\"/>"; break;
			case 2 : $estatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Validada\"/>"; break;
			default : $estatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"No Verificada\"/>"; break;
		}
	
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$estatus."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaAperturaCaja']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['usuarioApertura'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['empleadoApertura'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaCierre']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['usuarioCierre'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['empleadoCierre'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formVerificacionPortadaCaja('%s','%s','%s');\" src=\"../img/iconos/ico_examinar.png\" title=\"Portada de Caja\"/></td>",$row['id'],$row['idCierre'],$row['fechaCierre']); //Portada de Caja
			//$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"window.open('cjrs_cierre_caja_historico.php?idApertura=".$row['id']."&idCierre=".$row['idCierre']."&fecha=".$row['fechaCierre']."','_self')\" src=\"../img/iconos/ico_examinar.png\" title=\"Portada de Caja\"/></td>"); //Portada de Caja		
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formVerificacionRecibosPorMedioPago('%s','%s','%s');\" src=\"../img/iconos/application_view_columns.png\" title=\"Recibos por medio de Pago\"/></td>",$row['id'],$row['idCierre'],$row['fechaCierre']); //Recibo por mediio de pago
			//$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" src=\"../img/iconos/application_view_columns.png\" title=\"Recibos por medio de Pago\" border=\"0\" onclick=\"window.open('cjrs_detalle_caja_historico.php?idApertura=".$row['id']."&idCierre=".$row['idCierre']."&fecha=".$row['fechaCierre']."','_self')\"></td>"; //Recibo por medio de pago
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formVerificacion('%s','%s');\" src=\"../img/iconos/find.png\" title=\"Verificar Cierre\"/></td>",$row['id'],$row['idCierre']); //Verificacion de Caja
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCierre(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierre(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"11\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoCierre","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"abrirPortadaCaja");
$xajax->register(XAJAX_FUNCTION,"buscarCierre");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"exportarListadoCierre");
$xajax->register(XAJAX_FUNCTION,"formVerificacion");
$xajax->register(XAJAX_FUNCTION,"formVerificacionPortadaCaja");
$xajax->register(XAJAX_FUNCTION,"formVerificacionRecibosPorMedioPago");
$xajax->register(XAJAX_FUNCTION,"guardarAprobacion");
$xajax->register(XAJAX_FUNCTION,"guardarValidacion");
$xajax->register(XAJAX_FUNCTION,"listaCierre");

function errorGuardarAprobacion($objResponse){
	$objResponse->script("
	byId('btnGuardarAprobacion').disabled = false;
	byId('btnCancelar').disabled = false;");
}

function errorGuardarValidacion($objResponse){
	$objResponse->script("
	byId('btnGuardarValidacion').disabled = false;
	byId('btnCancelar').disabled = false;");
}
?>