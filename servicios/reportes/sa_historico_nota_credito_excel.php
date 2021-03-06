<?php
set_time_limit(100000);
ini_set('memory_limit', '-1');

require("../../connections/conex.php");
require('../../clases/excelXml/excel_xml.php');

$excel = new excel_xml();

$headerStyle = array('bold' => 1, 'size' => '8', 'color' => '#FFFFFF', 'bgcolor' => '#021933');

$trCabecera =  array('bold' => 1, 'size' => '8', 'color' => '#000000');

$trResaltar4 = array('size' => '8', 'bgcolor' => '#FFFFFF');
$trResaltar5 = array('size' => '8', 'bgcolor' => '#D7D7D7');
$trResaltarTotal = array('size' => '8', 'bgcolor' => '#E6FFE6', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal2 = array('size' => '8', 'bgcolor' => '#DDEEFF', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal3 = array('size' => '8', 'bgcolor' => '#FFEED5', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');

$excel->add_style('header', $headerStyle);
$excel->add_style('trCabecera', $trCabecera);
$excel->add_style('trResaltar4', $trResaltar4);
$excel->add_style('trResaltar5', $trResaltar5);
$excel->add_style('trResaltarTotal', $trResaltarTotal);
$excel->add_style('trResaltarTotal2', $trResaltarTotal2);
$excel->add_style('trResaltarTotal3', $trResaltarTotal3);

$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];

$startRow = $pageNum * $maxRows;

// DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura = 1");

	//sino se le envia busqueda que agarre la de la session
	if($valCadBusq[0] == ""){
		$valCadBusq[0] = $_SESSION['idEmpresaUsuarioSysGts'];
		}
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(cj_cc_notacredito.fechaNotaCredito) BETWEEN %s AND %s",
	//$sqlBusq .= $cond.sprintf("DATE(tiempo_orden) BETWEEN %s AND %s",//antes por fecha de orden
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_empleado = %s",
		valTpDato($valCadBusq[3],"int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_tipo_orden = %s",
		valTpDato($valCadBusq[4], "int"));
}

/*if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_estado_orden = %s",
		valTpDato($valCadBusq[5], "int"));
}*/



if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	
	OR orden.numero_orden LIKE %s
	OR cj_cc_notacredito.numeracion_nota_credito LIKE %s
	OR recepcion.numeracion_recepcion LIKE %s
	OR nom_uni_bas LIKE %s
	OR placa LIKE %s
	OR chasis LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"));
}
        $modoFiltro = $valCadBusq[11];   
        if ($modoFiltro == "1"){
            $andOr = " OR ";
        }elseif($modoFiltro == "2"){
            $andOr = " AND ";
        }
        $join = NULL;
        if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {//con Repuestos            
                $cond = (strlen($join) > 0) ? $andOr : " AND ( ";
		$sqlBusq .= $cond.sprintf("sa_det_fact_articulo.id_det_fact_articulo IS NOT NULL");
                $join .= " LEFT JOIN sa_det_fact_articulo ON fact_vent.idFactura = sa_det_fact_articulo.idFactura ";
	}
        if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {//con Manos de obra
		$cond = (strlen($join) > 0) ? $andOr : " AND ( ";
		$sqlBusq .= $cond.sprintf("sa_det_fact_tempario.id_det_fact_tempario IS NOT NULL");
                $join .= " LEFT JOIN sa_det_fact_tempario ON fact_vent.idFactura = sa_det_fact_tempario.idFactura ";
	}
        if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {//con TOT
		$cond = (strlen($join) > 0) ? $andOr : " AND ( ";
		$sqlBusq .= $cond.sprintf("sa_det_fact_tot.id_det_fact_tot IS NOT NULL");
                $join .= " LEFT JOIN sa_det_fact_tot ON fact_vent.idFactura = sa_det_fact_tot.idFactura ";
	}
        if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {//con nota de cargo
		$cond = (strlen($join) > 0) ? $andOr : " AND ( ";
		$sqlBusq .= $cond.sprintf("sa_det_fact_notas.id_det_fact_nota IS NOT NULL");
                $join .= " LEFT JOIN sa_det_fact_notas ON fact_vent.idFactura = sa_det_fact_notas.idFactura ";
	}
        if ($join != NULL) { $sqlBusq .= ")"; }
		
$queryMaestro = sprintf("SELECT *,
	orden.tiempo_orden,
	orden.id_orden,
	recepcion.id_recepcion,
	orden.id_empresa,
	tipo_orden.nombre_tipo_orden,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	
	(SELECT nombre_empleado FROM vw_pg_empleados
	WHERE id_empleado = orden.id_empleado) AS nombre_empleado,
	
	(SELECT CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) 
			FROM sa_det_orden_tempario
			INNER JOIN sa_mecanicos ON sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico
			INNER JOIN pg_empleado ON sa_mecanicos.id_empleado = pg_empleado.id_empleado
			WHERE sa_det_orden_tempario.id_mecanico IS NOT NULL
			AND sa_det_orden_tempario.id_orden = orden.id_orden LIMIT 1
			) AS nombre_tecnico,
	
	uni_bas.nom_uni_bas,
	placa,
	chasis,																		
	(estado_orden.tipo_estado + 0) AS id_tipo_estado,
	nombre_estado,
	color_estado,
	color_fuente,
	id_orden_retrabajo,
	((((orden.subtotal - orden.subtotal_descuento) * orden.iva) / 100) + (orden.subtotal - orden.subtotal_descuento)) AS total
FROM sa_orden orden
	INNER JOIN sa_recepcion recepcion ON (orden.id_recepcion = recepcion.id_recepcion)
	INNER JOIN sa_cita cita ON (recepcion.id_cita = cita.id_cita)
	INNER JOIN cj_cc_cliente cliente ON (orden.id_cliente = cliente.id)
	INNER JOIN en_registro_placas reg_placas ON (cita.id_registro_placas = reg_placas.id_registro_placas)
	INNER JOIN an_uni_bas uni_bas ON (reg_placas.id_unidad_basica = uni_bas.id_uni_bas)
	INNER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
	INNER JOIN sa_estado_orden estado_orden ON (orden.id_estado_orden = estado_orden.id_estado_orden)
	INNER JOIN cj_cc_encabezadofactura fact_vent ON (orden.id_orden = fact_vent.numeroPedido)
	LEFT JOIN sa_retrabajo_orden orden_retrabajo ON (orden.id_orden = orden_retrabajo.id_orden)
	INNER JOIN cj_cc_notacredito ON (fact_vent.idFactura = cj_cc_notacredito.idDocumento) %s %s
	GROUP BY fact_vent.idFactura ORDER BY cj_cc_notacredito.numeracion_nota_credito DESC", 
        $join,
        $sqlBusq);

$rsMaestro = mysql_query($queryMaestro);
if (!$rsMaestro) die(mysql_error()."<br><br>Line: ".__LINE__);

$totalRowsMaestro = mysql_num_rows($rsMaestro);
$contFila = 0;
$arrayTotalPagina = NULL;
$arrayPagina = NULL;
while ($rowMaestro = mysql_fetch_assoc($rsMaestro)) {
	$contFila++;
	
	$arrayCol[$contFila][0] = date("d-m-Y",strtotime($rowMaestro['fechaNotaCredito']));
	$arrayCol[$contFila][1] = $rowMaestro['numeracion_nota_credito'];
	$arrayCol[$contFila][2] = $rowMaestro['numeroFactura'];
	$arrayCol[$contFila][3] = $rowMaestro['numeroControl'];
	$arrayCol[$contFila][4] = date("d-m-Y",strtotime($rowMaestro['tiempo_orden']));
	$arrayCol[$contFila][5] = ($rowMaestro['numero_orden']);
	$arrayCol[$contFila][6] = ($rowMaestro['numeracion_recepcion']);
	$arrayCol[$contFila][7] = utf8_encode($rowMaestro['nombre_empleado'])." ";
	$arrayCol[$contFila][8] = utf8_encode($rowMaestro['nombre_tecnico']);
	$arrayCol[$contFila][9] = utf8_encode($rowMaestro['nombre_tipo_orden']);
	$arrayCol[$contFila][10] = utf8_encode($rowMaestro['nombre_cliente']);
	$arrayCol[$contFila][11] = utf8_encode($rowMaestro['nom_uni_bas']);
	$arrayCol[$contFila][12] = utf8_encode($rowMaestro['placa']);
	$arrayCol[$contFila][13] = utf8_encode($rowMaestro['chasis']);
	$arrayCol[$contFila][14] = utf8_encode($rowMaestro['nombre_estado']);
	$arrayCol[$contFila][15] = ($rowMaestro['id_orden_retrabajo']);
	$arrayCol[$contFila][16] = $rowMaestro['total_orden'];
	
	// TOTALES
	$arrayTotalPagina[15] += $arrayCol[$contFila][16];
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
		
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","","","","","",""));
		
		// DATOS DE LA EMPRESA
		$excel->add_row(array(
			$rowEmp['nombre_empresa']."|19"
		), 'trCabecera');
		$excel->add_row(array(
			$spanRIF." ".$rowEmp['rif']."|19"
		), 'trCabecera');
		if (strlen($rowEmp['direccion']) > 1) {
			$direcEmpresa = utf8_encode($rowEmp['direccion']).".";
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
		
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","",""));
		$excel->add_row(array('ORDENES DE SERVICIO - NOTAS DE CRÉDITO'."|19"), 'trCabecera');
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","",""));
		
		// DETALLE ARTICULOS
		$excel->add_row(array(
			'Fecha NC',
			'Nº NC',
			'Nº Factura',
			'Nº Control NC',
			'Fecha Orden',
			'N° Orden',
			'N° Recepción',
			'Asesor',
			'Técnico',
			'Tipo Orden',
			'Cliente',
			'Catálogo',
			'Placa',
			'Chasis',
			'Estado',
			'Ord. Retrabajo',
			'Total'
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
					$valor2[12],
					$valor2[13],
					$valor2[14],
					$valor2[15],
					round($valor2[16],2)
				), $clase);
			}
		}
		
		if (isset($valor[4])) {
			$excel->add_row(array(
				"Total Página:|15",
				round($valor[4][15],2)
			), 'trResaltarTotal');
		}
		
		$excel->create_worksheet($valor[0]);
	}
}

$xml = $excel->generate();

$excel->download('Historico notas de credito de servicios.xls');

?>