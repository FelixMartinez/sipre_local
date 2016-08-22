<?php
	session_start();
	$con = ConectarBD();
	
	// VERIFICA VALORES DE CONFIGURACION (Consulta el Idioma del sistema)
	$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
		INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
	$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
	$rowConfig403 = ObtenerFetch($rsConfig403);
	$valor = $rowConfig403['0'];// 1 = Español, 2 = Ingles
	
	
	//---------------------ARREGLOS---------------------
	//TITULOS
	$balanceComprobacionTituloArray = array(1=>"BALANCE DE COMPROBACION", 2=>"BALANCE DE COMPROBACION", 3=>"TRIAL BALANCE");
	$balanceGeneralTituloArray = array(1=>"BALANCE GENERAL", 2=>"BALANCE GENERAL", 3=>"BALANCE SHEET");
	
	//GENERALES
	$fechaEncabezadoArray = array(1=>"Fecha", 2=>"Fecha", 3=>"Date");
	$horaEncabezadoArray = array(1=>"Hora", 2=>"Hora", 3=>"Time");
	$emitidoPorEncabezadoArray = array(1=>"Emitido Por", 2=>"Emitido Por", 3=>"Issued by");
	$alArray = array(1=>"Al", 2=>"Al", 3=>"As Of");
	$desdeArray = array(1=>"Desde", 2=>"Desde", 3=>"From");
	$hastaArray = array(1=>"Hasta", 2=>"Hasta", 3=>"To");
	$codigoArray = array(1=>"Codigo", 2=>"Codigo", 3=>"Account");
	$descripcionArray = array(1=>"Nombre de la Cuenta", 2=>"Nombre de la Cuenta", 3=>"Description");
	$saldoAnteriorArray = array(1=>"Saldo Anterior", 2=>"Saldo Anterior", 3=>"Previous Balance");
	$debeArray = array(1=>"Debe", 2=>"Debe", 3=>"Debit");
	$haberArray = array(1=>"Haber", 2=>"Haber", 3=>"Credit");
	$saldoActualArray = array(1=>"Saldo Actual", 2=>"Saldo Actual", 3=>"Current Balance");
	$totalesArray = array(1=>"Totales", 2=>"Totales", 3=>"Totals");
	
	//BALANCE GENERAL
	$utilidadEjercicioArray = array(1=>"UTILIDAD EJERCICIO ACTUAL", 2=>"UTILIDAD EJERCICIO ACTUAL", 3=>"USEFULNESS OF THE CURRENT YEAR");
	$totalMasCapitalArray = array(1=>"TOTAL PASIVO + CAPITAL", 2=>"TOTAL PASIVO + CAPITAL", 3=>"TOTAL LIABILITIES + CAPITAL");

	
	//---------------------VARIABLES---------------------
	//TITULOS
	$balanceComprobacionTitulo = $balanceComprobacionTituloArray[$valor];
	$balanceGeneralTitulo = $balanceGeneralTituloArray[$valor];
	
	//GENERALES
	$fechaEncabezado = $fechaEncabezadoArray[$valor];
	$horaEncabezado = $horaEncabezadoArray[$valor];
	$emitidoPorEncabezado = $emitidoPorEncabezadoArray[$valor];
	$al = $alArray[$valor];
	$desde = $desdeArray[$valor];
	$hasta = $hastaArray[$valor];
	$codigo = $codigoArray[$valor];
	$descripcion = $descripcionArray[$valor];
	$saldoAnterior = $saldoAnteriorArray[$valor];
	$debe = $debeArray[$valor];
	$haber = $haberArray[$valor];
	$saldoActual = $saldoActualArray[$valor];
	$totales = $totalesArray[$valor];
	
	//BALANCE GENERAL
	$utilidadEjercicio = $utilidadEjercicioArray[$valor];
	$totalMasCapital = $totalMasCapitalArray[$valor];
?>