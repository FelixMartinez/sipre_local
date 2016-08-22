<?php
set_time_limit(0);
ini_set('memory_limit', '-1');

require_once ("../../connections/conex.php");

include '../../clases/excelXml/excel_xml.php';
$excel = new excel_xml();

$headerStyle = array('bold' => 1, 'size' => '8', 'color' => '#FFFFFF', 'bgcolor' => '#021933');
$trCabecera =  array('bold' => 1, 'size' => '8', 'color' => '#000000');
$trTitulo =  array('bold' => 1, 'size' => '10', 'color' => '#000000');

$trResaltar4 = array('size' => '8', 'bgcolor' => '#FFFFFF');
$trResaltar5 = array('size' => '8', 'bgcolor' => '#D7D7D7');
$trResaltarTotal = array('size' => '8', 'bgcolor' => '#E6FFE6', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal2 = array('size' => '8', 'bgcolor' => '#DDEEFF', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal3 = array('size' => '8', 'bgcolor' => '#FFEED5', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');

$excel->add_style('header', $headerStyle);
$excel->add_style('trCabecera', $trCabecera);
$excel->add_style('trTitulo', $trTitulo);
$excel->add_style('trResaltar4', $trResaltar4);
$excel->add_style('trResaltar5', $trResaltar5);
$excel->add_style('trResaltarTotal', $trResaltarTotal);
$excel->add_style('trResaltarTotal2', $trResaltarTotal2);
$excel->add_style('trResaltarTotal3', $trResaltarTotal3);

$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];
$fecha = $valCadBusq[1];
$verificacion = $valCadBusq[2];

$startRow = $pageNum * $maxRows;

// DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

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
			valTpDato(1, "int"), $andEmpresa); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	} else if ($valCadBusq[3] == 0) { // NO
		$sqlBusq .= $cond.sprintf("cierre.idCierre NOT IN (SELECT id_cierre FROM cj_verificacion_cierre WHERE id_caja = %s %s)",
			valTpDato(1, "int"), $andEmpresa2); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	}
}

$queryMaestro = sprintf("SELECT
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
		AND idDepartamentoOrigenFactura IN (2)
	GROUP BY cj_cc_encabezadofactura.fechaRegistroFactura) AS factCred,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM sa_iv_apertura apertura
	INNER JOIN sa_iv_cierredecaja cierre ON (apertura.id = cierre.id)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cierre.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
GROUP BY cierre.fechaCierre ORDER BY fechaCierre DESC", $sqlBusq);
$rsMaestro = mysql_query($queryMaestro);
if (!$rsMaestro) die(mysql_error()."<br><br>Line: ".__LINE__);
$totalRowsMaestro = mysql_num_rows($rsMaestro);

$contFila = 0;
$arrayTotalPagina = NULL;
$arrayPagina = NULL;
while ($rowMaestro = mysql_fetch_assoc($rsMaestro)) {
	$contFila++;
	
	$sqlVerificacion = sprintf("SELECT
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
		AND cj_verificacion_cierre.id_empresa = %s",
		valTpDato($rowMaestro['idCaja'], "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($rowMaestro['id'],"int"),
		valTpDato($rowMaestro['idCierre'],"int"),
		valTpDato($idEmpresa,"int"));
	$rsVerificacion = mysql_query($sqlVerificacion);
	if (!$rsVerificacion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowVerificacion = mysql_fetch_assoc($rsVerificacion);
	
	$estatus = (mysql_num_rows($rsVerificacion) > 0) ? "Verificada" : "No Verificada";
	
	$arrayCol[$contFila][0] = utf8_encode($estatus)." ";
	$arrayCol[$contFila][1] = utf8_encode($rowMaestro['nombre_empresa'])." ";
	$arrayCol[$contFila][2] = date("d-m-Y", strtotime($rowMaestro['fechaAperturaCaja']))." ";
	$arrayCol[$contFila][3] = utf8_encode($rowMaestro['usuarioApertura'])." ";
	$arrayCol[$contFila][4] = utf8_encode($rowMaestro['empleadoApertura'])." ";
	$arrayCol[$contFila][5] = date("d-m-Y", strtotime($rowMaestro['fechaCierre']))." ";
	$arrayCol[$contFila][6] = utf8_encode($rowMaestro['usuarioCierre'])." ";
	$arrayCol[$contFila][7] = utf8_encode($rowMaestro['empleadoCierre'])." ";
	$arrayCol[$contFila][8] = utf8_encode($rowVerificacion['fecha'])." ";
	$arrayCol[$contFila][9] = utf8_encode($rowVerificacion['hora'])." ";
	$arrayCol[$contFila][10] = utf8_encode($rowVerificacion['nombre_empleado'].' '.$rowVerificacion['apellido'])." ";
	$arrayCol[$contFila][11] = utf8_encode($rowVerificacion['nombre_usuario'])." ";
}
	
$arrayPagina[0][0] = "Página 1";
$arrayPagina[0][1] = "";
$arrayPagina[0][2] = "";
$arrayPagina[0][3] = $arrayCol;
$arrayPagina[0][4] = $arrayTotalPagina;
$arrayPagina[0][5] = $array;
$arrayPagina[0][6] = array($totalCantArt, $totalExist, $totalValorExist);


if (isset($arrayPagina)) {
	foreach ($arrayPagina as $indice => $valor) {
		
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","",""));
		
		// DATOS DE LA EMPRESA
		$excel->add_row(array(
			$rowEmp['nombre_empresa']."|19"
		), 'trCabecera');
		$excel->add_row(array(
			"R.I.F.: ".$rowEmp['rif']."|19"
		), 'trCabecera');
		if (strlen($rowEmp['direccion']) > 1) {
			$direcEmpresa = $rowEmp['direccion'].".";
			$telfEmpresa = "";
			if (strlen($rowEmp['telefono1']) > 1) {
				$telfEmpresa .= "Telf.: ".$rowEmp['telefono1'];
			}
			if (strlen($rowEmp['telefono2']) > 1) {
				$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
				$telfEmpresa .= $rowEmp['telefono2'];
			}
			if (strlen($rowEmp['telefono3']) > 1) {
				$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
				$telfEmpresa .= $rowEmp['telefono3'];
			}
			if (strlen($rowEmp['telefono4']) > 1) {
				$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
				$telfEmpresa .= $rowEmp['telefono4'];
			}
			
			$excel->add_row(array(
				$direcEmpresa." ".$telfEmpresa."|19"
			), 'trCabecera');
		}
		$excel->add_row(array(
			$rowEmp['web']."|19"
		), 'trCabecera');
		
		$excel->add_row(array(
			"Fecha de Emisión: ".date("d-m-Y")."  Hora: ".date("H:i:s")."|19"
		), 'trCabecera');
		
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","",""));
		$excel->add_row(array('HISTORICO DE CIERRE - CAJA DE REPUESTOS Y SERVICIOS'."|19"), 'trTitulo');
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","",""));
		
		// DETALLE ARTICULOS
		$excel->add_row(array(
			'Estatus',
			'Empresa',
			'Fecha Apertura',
			'Usuario Apertura',
			'Empleado Apertura',
			'Fecha Cierre',
			'Usuario Cierre',
			'Empleado Cierre',
			'Fecha Verificacion',
			'Hora Verificacion',
			'Empleado Verificacion',
			'Usuario Verificacion',
		), 'header');
		
		if (isset($valor[3])) {
			$contFila = 0;
			foreach ($valor[3] as $indice2 => $valor2) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$excel->add_row(array(
					$valor2[0],
					$valor2[1],
					$valor2[2],
					$valor2[3],
					$valor2[4],
					$valor2[5],
					$valor2[6],
					$valor2[7],
					$valor2[8],
					$valor2[9],
					$valor2[10],
					$valor2[11],
				), $clase);
			}
		}
		
		/*if (isset($valor[4])) {
			$excel->add_row(array(
				"Total Página:|7",
				round($valor[4][8],2),
				round($valor[4][9],2),
				round($valor[4][10],2),
				round($valor[4][11],2)
			), 'trResaltarTotal');
		}*/
		
		$excel->create_worksheet($valor[0]);
	}
}

$xml = $excel->generate();

$excel->download('SIPRE_Historico_Cierre_RS.xls');
?>