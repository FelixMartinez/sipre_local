<?php
set_time_limit(0);

function generarComision($idDocumento, $generarDirecto = true) {
	global $conex;
	
	$queryFact = sprintf("SELECT
		cxc_fact.idFactura,
		cxc_fact.id_empresa,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.idVendedor,
		cxc_fact.idDepartamentoOrigenFactura,
		cxc_fact.subtotalFactura,
		cxc_fact.porcentaje_descuento,
		cxc_fact.descuentoFactura,
		cxc_fact.estatus_factura,
		cxc_fact.estadoFactura,
		
		(IFNULL((SELECT SUM((cantidad * costo_compra)) FROM cj_cc_factura_detalle cxc_fact_det
				WHERE cxc_fact_det.id_factura = cxc_fact.idFactura), 0)
			+ IFNULL((SELECT SUM(costo_compra) FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_veh
					WHERE cxc_fact_det_veh.id_factura = cxc_fact.idFactura), 0)
			+ IFNULL((SELECT SUM(costo_compra) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
					WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura), 0)) AS total_costo,
		
		(SELECT SUM(cxc_fact_iva.iva) FROM cj_cc_factura_iva cxc_fact_iva
		WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura) AS porcentajeIvaFactura,
		
		(SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
		WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura) AS total_impuesto,
		
		(SELECT COUNT(id_factura) FROM cj_cc_factura_detalle cxc_fact_det
			INNER JOIN iv_articulos art ON (cxc_fact_det.id_articulo = art.id_articulo)
		WHERE cxc_fact_det.id_factura = cxc_fact.idFactura) AS items_repuestos,
		
		(SELECT COUNT(id_factura) FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
		WHERE cxc_fact_det_vehic.id_factura = cxc_fact.idFactura) AS items_vehiculos,
		
		(SELECT COUNT(id_factura) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
			INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
		WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura) AS items_accesorios
		
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE cxc_fact.idFactura = %s;",
		valTpDato($idDocumento, "int"));
	$rsFact = mysql_query($queryFact, $conex);
	if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$porcDescuentoFact = $rowFact['porcentaje_descuento'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura'];
	$idVendedor = $rowFact['idVendedor'];
	$porcIva = $rowFact['porcentajeIvaFactura'];
	
	if ($rowFact['items_repuestos'] > 0 || $rowFact['items_vehiculos'] > 0 || $rowFact['items_accesorios'] > 0) {
		// 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario; 8 = Facturado
		($rowFact['items_repuestos'] > 0) ? $arrayTipoComision[] = 4 : "";
		($rowFact['items_vehiculos'] > 0) ? $arrayTipoComision[] = 5 : "";
		($rowFact['items_accesorios'] > 0) ? $arrayTipoComision[] = 6 : "";
		$arrayTipoComision[] = 8;
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_comision IN (%s)",
			valTpDato(implode(",",$arrayTipoComision), "campo"));
	} else {
		if ($rowFact['items_repuestos'] == 0 && $rowFact['items_vehiculos'] == 0 && $rowFact['items_accesorios'] == 0 && $idModulo == 0) { // CxC
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision IN (4,8)");
		} else {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision = -1");
		}
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	if($idModulo > 0){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo = %s",
			valTpDato($idModulo, "int"));
	}
	
	if($rowFact['items_vehiculos'] == 0){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((modo_comision = 2 AND id_empleado <> %s)
		OR (modo_comision = 1 AND id_empleado = %s))",
				valTpDato($idVendedor, "int"),
				valTpDato($idVendedor, "int"));
	} else{
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(modo_comision = 1 )");
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	$tipo_porcentaje = ($rowFact['items_vehiculos'] > 0) ? 3 : 2;
	
	if ($generarDirecto == false){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje = %s",
				valTpDato($tipo_porcentaje, "int")); // 2 = Productividad, 3 = Rango
	}
	
	if($rowFact['items_vehiculos'] > 0){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empleado = %s",
				valTpDato($idVendedor, "int"));
	}
	
	$queryEmpleado = sprintf("SELECT DISTINCT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		cargo_dep.id_cargo_departamento
	FROM pg_empleado empleado
		INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
		LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
		INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento) %s
	ORDER BY comision.porcentaje_comision", $sqlBusq);

	$rsEmpleado = mysql_query($queryEmpleado, $conex);
	if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRowsDet = mysql_num_rows($rsEmpleado);
	
	if($totalRowsDet > 0){
		while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) {
			$idEmpleado = $rowEmpleado['id_empleado'];
			
			$queryComision = sprintf("SELECT * FROM pg_comision_empleado
			WHERE id_empleado = %s
				AND id_cargo_departamento = %s
				AND id_factura = %s;",
				valTpDato($idEmpleado, "int"),
				valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
				valTpDato($idDocumento, "int"));
			$rsComision = mysql_query($queryComision);
			if (!$rsComision) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$totalRowsComision = mysql_num_rows($rsComision);
			$rowComision = mysql_fetch_array($rsComision);
			
			$idComisionEmpleado = $rowComision['id_comision_empleado'];
			
			if (!($idComisionEmpleado > 0)) {
				$insertSQL = sprintf("INSERT INTO pg_comision_empleado (id_empleado, id_cargo_departamento, id_factura)
				VALUE (%s, %s, %s);",
					valTpDato($idEmpleado, "int"),
					valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
					valTpDato($idDocumento, "int"));
				
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				$idComisionEmpleado = mysql_insert_id();
			}
			
			if ($idComisionEmpleado > 0) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 = $cond.sprintf("id_empleado = %s",
					valTpDato($idEmpleado, "int"));
							
				$query = sprintf("SELECT
					comision.id_comision,
					empleado.id_empleado,
					CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
					porcentaje_comision,
					tipo_porcentaje,
					tipo_importe,
					aplica_iva,
					tipo_comision,
					modo_comision
				FROM pg_empleado empleado
					INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
					LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision) %s %s
				ORDER BY porcentaje_comision", $sqlBusq, $sqlBusq2);
				
				$rs = mysql_query($query, $conex);
						
				if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				$arrayComision = NULL;

				while ($row = mysql_fetch_assoc($rs)) {
					
					$idTipoPorcentaje = $row['tipo_porcentaje'];
					
					if (($generarDirecto == true && $idTipoPorcentaje != 2)
					|| ($generarDirecto == false && ($idTipoPorcentaje == 2 || $idTipoPorcentaje == 3))) { // 2 = Productividad, 3 = Vehiculo
						$totalRowsFactDet = NULL;
						
						if ($idTipoPorcentaje == 2) { // 2 = Productividad
							// TOTAL UT MENSUAL
							$queryMensualUT = sprintf("SELECT
								iv_cierre_mensual_facturacion.id_cierre_mensual,
								id_cierre_mensual_facturacion,
								id_empleado,
								total_ut_fisica,
								total_ut
							FROM iv_cierre_mensual_facturacion
								INNER JOIN iv_cierre_mensual ON (iv_cierre_mensual_facturacion.id_cierre_mensual = iv_cierre_mensual.id_cierre_mensual)
							WHERE id_empleado = %s AND mes = %s AND ano = %s AND id_empresa = %s AND total_ut_fisica > %s",
								valTpDato($idEmpleado, "int"),
								valTpDato(date("m",strtotime($rowFact['fechaRegistroFactura'])), "text"),
								valTpDato(date("Y",strtotime($rowFact['fechaRegistroFactura'])), "text"),
								valTpDato($idEmpresa, "int"),
								valTpDato(0, "int"));
							$ResultMensualUT = mysql_query($queryMensualUT); 
							if (!$ResultMensualUT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$rowMensualUT = mysql_fetch_array($ResultMensualUT);
							
							$horasFacturadas = $rowMensualUT['total_ut'];
							$horasFisicas = $rowMensualUT['total_ut_fisica'];
							
							$porcentajeProductividad = round(($horasFacturadas / $horasFisicas) * 100, 0); // PORCENTAJE DE PRODUCTIVIDAD
							
							//SQL NIVEL DE PRODUCTIVIDAD
							$queryNivel = sprintf("SELECT * FROM pg_comision_productividad
							WHERE id_comision = %s
								AND %s BETWEEN mayor AND menor OR (%s > mayor AND menor = 0);", 
								valTpDato($row['id_comision'], "int"), 
								valTpDato($porcentajeProductividad, "real_inglesa"),
								valTpDato($porcentajeProductividad, "real_inglesa"));
							$ResultNivel = mysql_query($queryNivel);
							if (!$ResultNivel) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$rowNivelProd = mysql_fetch_array($ResultNivel);
							
							$porcComision = $rowNivelProd['porcentaje'];
						}  else {
							$porcComision = $row['porcentaje_comision'];
						}
						
						if ($rowFact['items_repuestos'] == 0 && $rowFact['items_vehiculos'] == 0 && $rowFact['items_accesorios'] == 0) {
							$cantidad = 1;
							$precioUnitario = $rowFact['subtotalFactura'];
							$costoUnitario = 0;
							$porcIva = $rowFact['porcentajeIvaFactura'];
							
							$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
							$baseComision = floatval($precioUnitario - $descuento);
							$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
							$montoComision = floatval(($porcComision * $baseComision) / 100);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += $cantidad * $descuento;
							$arrayComision[2] += $cantidad * $costoUnitario;
							$arrayComision[3] += $porcComision;
							$arrayComision[4] += $cantidad * $montoComision;
						}
						
						if ($row['tipo_comision'] == 4 && $rowFact['items_repuestos'] > 0) {
							$queryDet = sprintf("SELECT *,
								(SELECT SUM(cxc_fact_det_imp.impuesto) FROM cj_cc_factura_detalle_impuesto cxc_fact_det_imp
								WHERE cxc_fact_det_imp.id_factura_detalle = cxc_fact_det.id_factura_detalle) AS porcentaje_impuesto
							FROM cj_cc_factura_detalle cxc_fact_det
								INNER JOIN iv_articulos art ON (cxc_fact_det.id_articulo = art.id_articulo)
							WHERE art.genera_comision = 1
								AND cxc_fact_det.id_factura = %s;",
								valTpDato($idDocumento, "int"));
							$rsDet = mysql_query($queryDet, $conex);
							if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$totalRowsDet = mysql_num_rows($rsDet);
							
							while ($rowDet = mysql_fetch_assoc($rsDet)) {
								$cantidad = $rowDet['cantidad'];
								$precioUnitario = $rowDet['precio_unitario'];
								$costoUnitario = $rowDet['costo_compra'];
								$porcIva = $rowDet['porcentaje_impuesto'];
								
								$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
								$baseComision = floatval($precioUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
								
								$montoComision = floatval(($porcComision * $baseComision) / 100);
								
								$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_articulo, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
								VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
									valTpDato($idComisionEmpleado, "int"),
									valTpDato($idTipoPorcentaje, "int"),
									valTpDato($rowDet['id_articulo'], "int"),
									valTpDato($rowDet['cantidad'], "real_inglesa"),
									valTpDato($costoUnitario, "real_inglesa"),
									valTpDato($precioUnitario, "real_inglesa"),
									valTpDato($porcComision, "real_inglesa"),
									valTpDato($montoComision, "real_inglesa"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
								
								$arrayComision[0] += $cantidad * $precioUnitario;
								$arrayComision[1] += $cantidad * $descuento;
								$arrayComision[2] += $cantidad * $costoUnitario;
								$arrayComision[3] += $porcComision;
								$arrayComision[4] += $cantidad * $montoComision;
							}
						}
						
						if ($row['tipo_comision'] == 5 && $rowFact['items_vehiculos'] > 0) {
							
							$queryCom = sprintf("SELECT * FROM `pg_comision` 
									WHERE `tipo_porcentaje` = 3 AND `tipo_importe` = 5 AND `tipo_comision` = 5 AND `id_comision` = %s;",
									valTpDato($row['id_comision'], "int"));
							$rsCom = mysql_query($queryCom);
							$valComision = mysql_num_rows($rsCom);
							
							if( $rowFact['estatus_factura'] == 2 &&$rowFact['estadoFactura'] == 1 && $valComision > 0){  // estatus_factura = 1 => Aprobado -> (1 = cancelado), estadoFactura = 1 => 
								$idComision = $row['id_comision'];
								
								//Informacion detalla de la factura del vehiculo
								$queryDet = sprintf("SELECT *,
									(SELECT SUM(cxc_fact_det_veh_imp.impuesto) FROM cj_cc_factura_detalle_vehiculo_impuesto cxc_fact_det_veh_imp
									WHERE cxc_fact_det_veh_imp.id_factura_detalle_vehiculo = cxc_fact_det_veh.id_factura_detalle_vehiculo) AS porcentaje_impuesto
								FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_veh
								WHERE cxc_fact_det_veh.id_factura = %s;",
									valTpDato($idDocumento, "int"));
								$rsDet = mysql_query($queryDet, $conex);
								if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
								$totalRowsDet = mysql_num_rows($rsDet);
								
								// Calculo de la unidades vendida por el empleado
								$mesComision = date('m');
								$anoComision = date('Y');
								
								$queryEmp = sprintf("SELECT idFactura, id_empresa, idCliente, fechaRegistroFactura, cc_cl.nombre as nombre_cliente
											FROM cj_cc_encabezadofactura enfac
											LEFT JOIN cj_cc_cliente cc_cl ON cc_cl.id = idCliente
											WHERE (YEAR(enfac.fechaRegistroFactura) < %s OR (YEAR(enfac.fechaRegistroFactura) = %s 
										    AND MONTH(enfac.fechaRegistroFactura) <= %s )) AND enfac.estatus_factura = 2 AND enfac.idVendedor = %s;",
										valTpDato($anoComision , "int"),
										valTpDato($anoComision, "int"),
										valTpDato($mesComision, "int"),
										valTpDato($row['id_empleado'], "int"));
								$rsEmp = mysql_query($queryEmp, $conex);
								
								//Se consulta la condicion de la unidad vendida
								$unidadNueva = 0;
								$unidadUsada = 0;
								
								while ($rowUniFis = mysql_fetch_assoc($rsEmp)) {
									$queryUniFis = sprintf("SELECT uf.id_condicion_unidad FROM cj_cc_encabezadofactura ef
										LEFT JOIN cj_cc_factura_detalle_vehiculo fdv ON ef.idFactura = fdv.id_factura
										LEFT JOIN an_unidad_fisica uf ON uf.id_unidad_fisica = fdv.id_unidad_fisica
									    WHERE ef.idFactura = %s;",
											valTpDato($rowUniFis['idFactura'], "int"));
									$rsUniFis = mysql_query($queryUniFis, $conex);
									if (!$rsUniFis) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
									$con = mysql_fetch_assoc($rsUniFis);
									
									if($con['id_condicion_unidad'] == 1){
										$unidadNueva++;
									} else{
										$unidadUsada++;
									}
								} 
								
								//Se consulta 
								$queryIdPro = sprintf("SELECT id_comision FROM pg_comision 
										WHERE id_modulo = 2 AND tipo_porcentaje = 4 AND tipo_importe = 3 AND tipo_comision = 6
										AND id_cargo_departamento = %s;",
										valTpDato($rowEmpleado['id_cargo_departamento'], "int"));
								$rsIdPro = mysql_query($queryIdPro, $conex);
								$rowIdPro = mysql_fetch_assoc($rsIdPro);
								$idComisionProducto = $rowIdPro['id_comision'];
								
								// Calculo del producto de la comision
								$queryProduct = sprintf("SELECT SUM(coma.monto) as monto, SUM(coma.porcentaje) as porcentaje FROM cj_cc_factura_detalle_accesorios facc
											LEFT JOIN pg_comision_articulo coma ON coma.id_articulo = facc.id_accesorio
										    WHERE facc.id_factura = %s AND coma.id_comision = %s;",
										valTpDato($idDocumento, "int"),
										valTpDato($idComisionProducto, "int"));
								$rsPro = mysql_query($queryProduct, $conex);
								if (!$rsPro) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
									
								$rowPro = mysql_fetch_assoc($rsPro);
								$productos = $rowPro['monto'];
								
								//	CALCULO DEL PORCENTAJE DE COMISION SEGUN EL RANGO
								$queryPor = sprintf("SELECT * FROM `pg_comision_productividad_unidad` com_pro
											LEFT JOIN pg_comision com ON com.id_comision = com_pro.id_comision
											WHERE com.id_cargo_departamento = %s;",
										valTpDato($rowEmpleado['id_cargo_departamento'], "int"));
								
								$rsPor = mysql_query($queryPor, $conex);
								if (!$rsPor) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);

// 								$unidadNueva = 7;
// 								$unidadUsada = 3;
								
								while ($rowPor = mysql_fetch_assoc($rsPor)) {
									if($rowPor['tipo_2'] < 0){
										if($unidadUsada < $unidadNueva || $unidadUsada == $unidadNueva){
											if($unidadNueva >= 25 && $rowPor['menor_igual'] == 0){
												$porcentaje = $rowPor['porcentaje'];
											} elseif( in_array($unidadNueva , range($rowPor['mayor_igual'], $rowPor['menor_igual'])) && $unidadNueva > 0 && $rowPor['menor_igual'] > 0) {
												$porcentaje = $rowPor['porcentaje'];
											}
										} else{
											if( in_array($unidadUsada, range($rowPor['mayor_igual'], $rowPor['menor_igual']))  && $unidadUsada > 0 && $rowPor['menor_igual'] > 0) {
												$porcentaje = $rowPor['porcentaje'];
											}
										}
									} else{ 
										if($unidadUsada > 0){
											if( in_array($unidadNueva, range($rowPor['mayor_igual'], $rowPor['menor_igual'])) ) {
												if( in_array($unidadUsada, range($rowPor['mayor_igual_2'], $rowPor['menor_igual_2'])) ) {
													$porcentaje = $rowPor['porcentaje'];
												}
											}
										}
									}
								}
								
								$porcentaje = ($porcentaje == '' || $porcentaje == 0) ? 0 : $porcentaje;

// 								print_r($porcentaje);exit;
							
								while ($rowDet = mysql_fetch_assoc($rsDet)) {
		
									$cantidad = 1;
									$precioUnitario = $rowDet['precio_unitario'];
									$costoUnitario = $rowDet['costo_compra'];
									
									
									
									/**
									Preguntar el bono al superar los 22%
									*/
									
									
									
									if($porcentaje >= 22) $precioUnitario += 1000; // Bono al empleado al superar el 22% de la comision
									if($porcentaje > 0) $montoComision = floatval(($precioUnitario-$costoUnitario)*$porcentaje/100 + $productos);
									else $montoComision = 0;
									
									$porcentaje = ($montoComision == 0)? 0 : $porcentaje;
									$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_unidad_fisica, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
									VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
										valTpDato($idComisionEmpleado, "int"),
										valTpDato($idTipoPorcentaje, "int"),
										valTpDato($rowDet['id_unidad_fisica'], "int"),
										valTpDato($cantidad, "real_inglesa"),
										valTpDato($costoUnitario, "real_inglesa"),
										valTpDato($precioUnitario, "real_inglesa"),
										valTpDato($porcentaje, "real_inglesa"),
										valTpDato($montoComision, "real_inglesa"));
									
									$Result1 = mysql_query($insertSQL);
									if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
					
									$arrayComision[0] += $cantidad * $precioUnitario;
									$arrayComision[1] += $cantidad * $descuento;
									$arrayComision[2] += $cantidad * $costoUnitario;
									$arrayComision[3] += $porcentaje;
									$arrayComision[4] += $cantidad * $montoComision;
								}
							}
						}
						
						if ($row['tipo_comision'] == 6 && $rowFact['items_accesorios'] > 0 && $rowFact['items_vehiculos'] == 0) {
							$queryDet = sprintf("SELECT * FROM an_accesorio acc
									INNER JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (acc.id_accesorio = cxc_fact_det_acc.id_accesorio)
									WHERE acc.genera_comision = 1
									AND cxc_fact_det_acc.id_factura = %s;",
								valTpDato($idDocumento, "int"));
							$rsDet = mysql_query($queryDet, $conex);
							if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$totalRowsDet = mysql_num_rows($rsDet);
							while ($rowDet = mysql_fetch_assoc($rsDet)) {
								$cantidad = 1;
								$precioUnitario = $rowDet['precio_unitario'];
								$costoUnitario = $rowDet['costo_compra'];
								$porcIva = $rowFact['porcentajeIvaFactura'];
								
								$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
								$baseComision = floatval($precioUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
								
								$montoComision = floatval(($porcComision * $baseComision) / 100);
								
								$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_accesorio, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
								VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
									valTpDato($idComisionEmpleado, "int"),
									valTpDato($idTipoPorcentaje, "int"),
									valTpDato($rowDet['id_accesorio'], "int"),
									valTpDato($cantidad, "real_inglesa"),
									valTpDato($costoUnitario, "real_inglesa"),
									valTpDato($precioUnitario, "real_inglesa"),
									valTpDato($porcComision, "real_inglesa"),
									valTpDato($montoComision, "real_inglesa"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
								
								$arrayComision[0] += $cantidad * $precioUnitario;
								$arrayComision[1] += $cantidad * $descuento;
								$arrayComision[2] += $cantidad * $costoUnitario;
								$arrayComision[3] += $porcComision;
								$arrayComision[4] += $cantidad * $montoComision;
							}
						}
						
						if ($row['tipo_comision'] == 5 && $rowFact['items_vehiculos'] > 0) {
							$porcentajeCom = "porcentaje_comision = {$porcentaje}";
						} else{
							$porcentajeCom = sprintf("porcentaje_comision = (%s / (SELECT COUNT(id_comision_empleado) FROM pg_comision_empleado_detalle
											WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado))", 
											valTpDato($arrayComision[3], "real_inglesa"));
						}
						
						if($arrayComision[0] > 0){
							$updateSQL = sprintf("UPDATE pg_comision_empleado SET
								venta_bruta = %s,
								monto_descuento = %s,
								costo_compra = %s,
								%s,
								monto_comision = %s
							WHERE id_comision_empleado = %s;",
								valTpDato($arrayComision[0], "real_inglesa"),
								valTpDato($arrayComision[1], "real_inglesa"),
								valTpDato($arrayComision[2], "real_inglesa"),
								$porcentajeCom,
								valTpDato($arrayComision[4], "real_inglesa"),
								valTpDato($idComisionEmpleado, "int"));
							
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						} elseif ($row['tipo_comision'] == 5 && $rowFact['items_vehiculos'] > 0) {
							if($idComisionEmpleado > 0){
								$deleteSQL = sprintf("DELETE FROM `pg_comision_empleado` WHERE id_comision_empleado = %s",
										valTpDato($idComisionEmpleado, "int"));
								$Result = mysql_query($deleteSQL);
								if (!$Result) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							}
						} 
					}
				}
			}
		}
	}
	return array(true, "");
}

function devolverComision($idDocumento, $generarDirecto = true) {
	global $conex;
	
	$queryFact = sprintf("SELECT
		cxc_nc.idNotaCredito,
		cxc_nc.id_empresa,
		cxc_nc.idDocumento,
		cxc_nc.fechaNotaCredito,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.idVendedor,
		cxc_nc.idDepartamentoNotaCredito,
		cxc_nc.subtotalNotaCredito,
		cxc_nc.porcentaje_descuento,
		cxc_nc.subtotal_descuento,
		cxc_nc.estatus_nota_credito,
		
		(IFNULL((SELECT SUM((cantidad * costo_compra)) FROM cj_cc_nota_credito_detalle cxc_nc_det
				WHERE ((SELECT COUNT(com_empleado_det.id_articulo)
						FROM pg_comision_empleado_detalle com_empleado_det
							INNER JOIN pg_comision_empleado com_empleado ON (com_empleado_det.id_comision_empleado = com_empleado.id_comision_empleado)
						WHERE com_empleado.id_factura = cxc_nc.idDocumento
							AND com_empleado_det.id_articulo = cxc_nc_det.id_articulo) > 0)
					AND cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito), 0)
			+ IFNULL((SELECT SUM(costo_compra) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_veh
					WHERE cxc_nc_det_veh.id_nota_credito = cxc_nc.idNotaCredito), 0)
			+ IFNULL((SELECT SUM(costo_compra) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
					WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito), 0)) AS total_costo,
		
		(SELECT SUM(cxc_nc_iva.iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
		WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito) AS porcentajeIvaNotaCredito,
		
		(SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
		WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito) AS total_impuesto,
		
		(SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
			INNER JOIN iv_articulos art ON (cxc_nc_det.id_articulo = art.id_articulo)
		WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito) AS items_repuestos,
		
		(SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		WHERE cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito) AS items_vehiculos,
		
		(SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
			INNER JOIN an_accesorio acc ON (cxc_nc_det_acc.id_accesorio = acc.id_accesorio)
		WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito) AS items_accesorios
		
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura)
	WHERE cxc_nc.tipoDocumento = 'FA'
		AND cxc_nc.idNotaCredito = %s;",
		valTpDato($idDocumento, "int"));
	$rsFact = mysql_query($queryFact, $conex);
	if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$rowFact = mysql_fetch_assoc($rsFact);
	$rowNota = mysql_num_rows($rsFact);
	
	if($rowNota > 0){
		$idEmpresa = $rowFact['id_empresa'];
		$idFactura = $rowFact['idDocumento'];
		$porcDescuentoFact = $rowFact['porcentaje_descuento'];
		$idModulo = $rowFact['idDepartamentoNotaCredito'];
		$idVendedor = $rowFact['idVendedor'];
		$porcIva = $rowFact['porcentajeIvaNotaCredito'];
		
		if ($rowFact['items_repuestos'] > 0 || $rowFact['items_vehiculos'] > 0 || $rowFact['items_accesorios'] > 0) {
			// 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario; 8 = Facturado
			($rowFact['items_repuestos'] > 0) ? $arrayTipoComision[] = 4 : "";
			($rowFact['items_vehiculos'] > 0) ? $arrayTipoComision[] = 5 : "";
			($rowFact['items_accesorios'] > 0) ? $arrayTipoComision[] = 6 : "";
			$arrayTipoComision[] = 8;
			
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision IN (%s)",
				valTpDato(implode(",",$arrayTipoComision), "campo"));
		} else {
			if ($rowFact['items_repuestos'] == 0 && $rowFact['items_vehiculos'] == 0 && $rowFact['items_accesorios'] == 0 && $idModulo == 0) { // CxC
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("tipo_comision IN (4,8)");
			} else {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("tipo_comision = -1");
			}
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo = %s",
			valTpDato($idModulo, "int"));
		
		if($rowFact['items_vehiculos'] == 0){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("((modo_comision = 2 AND id_empleado <> %s)
			OR (modo_comision = 1 AND id_empleado = %s))",
					valTpDato($idVendedor, "int"),
					valTpDato($idVendedor, "int"));
		} else{
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(modo_comision = 1)");
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = 1");
		
		if ($generarDirecto == false && $rowFact['items_vehiculos'] <= 0){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje = %s",
					valTpDato(2, "int")); // 2 = Productividad, 3 = Rango
		} elseif($rowFact['items_vehiculos'] > 0){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje = %s",
					valTpDato(3, "int")); // 2 = Productividad, 3 = Rango
		}
		
		if($rowFact['items_vehiculos'] > 0){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("id_empleado = %s",
					valTpDato($idVendedor, "int"));
		}
		
		$queryEmpleado = sprintf("SELECT DISTINCT
			empleado.id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
			cargo_dep.id_cargo_departamento
		FROM pg_empleado empleado
			INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
			LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
			INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento) %s
		ORDER BY comision.porcentaje_comision", $sqlBusq);
		$rsEmpleado = mysql_query($queryEmpleado, $conex);
		if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) {
			$idEmpleado = $rowEmpleado['id_empleado'];
			
			$queryComision = sprintf("SELECT * FROM pg_comision_empleado
			WHERE id_empleado = %s
				AND id_cargo_departamento = %s
				AND id_factura = %s
				AND id_nota_credito = %s;",
				valTpDato($idEmpleado, "int"),
				valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
				valTpDato($idFactura, "int"),
				valTpDato($idDocumento, "int"));
			$rsComision = mysql_query($queryComision);
			if (!$rsComision) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$totalRowsComision = mysql_num_rows($rsComision);
			$rowComision = mysql_fetch_array($rsComision);
			
			$idComisionEmpleado = $rowComision['id_comision_empleado'];
			
			if (!($idComisionEmpleado > 0)) {
				$insertSQL = sprintf("INSERT INTO pg_comision_empleado (id_empleado, id_cargo_departamento, id_factura, id_nota_credito)
				VALUE (%s, %s, %s, %s);",
					valTpDato($idEmpleado, "int"),
					valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
					valTpDato($idFactura, "int"),
					valTpDato($idDocumento, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				$idComisionEmpleado = mysql_insert_id();
				
			}
			
			if ($idComisionEmpleado > 0) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 = $cond.sprintf("id_empleado = %s",
					valTpDato($idEmpleado, "int"));
				
				$query = sprintf("SELECT
					comision.id_comision,
					empleado.id_empleado,
					CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
					porcentaje_comision,
					tipo_porcentaje,
					tipo_importe,
					aplica_iva,
					tipo_comision,
					modo_comision
				FROM pg_empleado empleado
					INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
					LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision) %s %s
				ORDER BY porcentaje_comision", $sqlBusq, $sqlBusq2);
				$rs = mysql_query($query, $conex);
				
				if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				$arrayComision = NULL;
				
				while ($row = mysql_fetch_assoc($rs)) {
					$idTipoPorcentaje = $row['tipo_porcentaje'];
					
					if (($generarDirecto == true && $idTipoPorcentaje != 2)
					|| ($generarDirecto == false && $idTipoPorcentaje == 2)) { // 2 = Productividad
						$totalRowsFactDet = NULL;
						
						if ($idTipoPorcentaje == 2) { // 2 = Productividad
							// TOTAL UT MENSUAL
							$queryMensualUT = sprintf("SELECT
								iv_cierre_mensual_facturacion.id_cierre_mensual,
								id_cierre_mensual_facturacion,
								id_empleado,
								total_ut_fisica,
								total_ut
							FROM iv_cierre_mensual_facturacion
								INNER JOIN iv_cierre_mensual ON (iv_cierre_mensual_facturacion.id_cierre_mensual = iv_cierre_mensual.id_cierre_mensual)
							WHERE id_empleado = %s AND mes = %s AND ano = %s AND id_empresa = %s AND total_ut_fisica > %s",
								valTpDato($idEmpleado, "int"),
								valTpDato(date("m",strtotime($rowFact['fechaRegistroFactura'])), "text"),
								valTpDato(date("Y",strtotime($rowFact['fechaRegistroFactura'])), "text"),
								valTpDato($idEmpresa, "int"),
								valTpDato(0, "int"));
							$ResultMensualUT = mysql_query($queryMensualUT); 
							if (!$ResultMensualUT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$rowMensualUT = mysql_fetch_array($ResultMensualUT);
							
							$horasFacturadas = $rowMensualUT['total_ut'];
							$horasFisicas = $rowMensualUT['total_ut_fisica'];
							
							$porcentajeProductividad = round(($horasFacturadas / $horasFisicas) * 100, 0); // PORCENTAJE DE PRODUCTIVIDAD
							
							//SQL NIVEL DE PRODUCTIVIDAD
							$queryNivel = sprintf("SELECT * FROM pg_comision_productividad
							WHERE id_comision = %s
								AND %s BETWEEN mayor AND menor OR (%s > mayor AND menor = 0);", 
								valTpDato($row['id_comision'], "int"), 
								valTpDato($porcentajeProductividad, "real_inglesa"),
								valTpDato($porcentajeProductividad, "real_inglesa"));
							$ResultNivel = mysql_query($queryNivel);
							if (!$ResultNivel) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$rowNivelProd = mysql_fetch_array($ResultNivel);
							
							$porcComision = $rowNivelProd['porcentaje'];
						} else {
							$porcComision = $row['porcentaje_comision'];
						}
						
						if ($rowFact['items_repuestos'] == 0 && $rowFact['items_vehiculos'] == 0 && $rowFact['items_accesorios'] == 0) {
							$cantidad = 1;
							$precioUnitario = $rowFact['subtotalNotaCredito'];
							$costoUnitario = 0;
							$porcIva = $rowFact['porcentajeIvaNotaCredito'];
							
							$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
							$baseComision = floatval($precioUnitario - $descuento);
							$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
							$montoComision = floatval(($porcComision * $baseComision) / 100);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += $cantidad * $descuento;
							$arrayComision[2] += $cantidad * $costoUnitario;
							$arrayComision[3] += $porcComision;
							$arrayComision[4] += $cantidad * $montoComision;
						}
						
						if ($row['tipo_comision'] == 4 && $rowFact['items_repuestos'] > 0) {
							$queryDet = sprintf("SELECT *,
								(SELECT SUM(cxc_nc_det_imp.impuesto) FROM cj_cc_nota_credito_detalle_impuesto cxc_nc_det_imp
								WHERE cxc_nc_det_imp.id_nota_credito_detalle = cxc_nc_det.id_nota_credito_detalle) AS porcentaje_impuesto
							FROM cj_cc_nota_credito_detalle cxc_nc_det
								INNER JOIN iv_articulos art ON (cxc_nc_det.id_articulo = art.id_articulo)
							WHERE (art.genera_comision = 1
								OR (SELECT COUNT(com_empleado_det.id_articulo)
									FROM pg_comision_empleado_detalle com_empleado_det
										INNER JOIN pg_comision_empleado com_empleado ON (com_empleado_det.id_comision_empleado = com_empleado.id_comision_empleado)
									WHERE com_empleado.id_factura = %s
										AND com_empleado_det.id_articulo = cxc_nc_det.id_articulo) > 0)
								AND cxc_nc_det.id_nota_credito = %s;",
								valTpDato($idFactura, "int"),
								valTpDato($idDocumento, "int"));
							$rsDet = mysql_query($queryDet, $conex);
							if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$totalRowsDet = mysql_num_rows($rsDet);
							while ($rowDet = mysql_fetch_assoc($rsDet)) {
								$cantidad = $rowDet['cantidad'];
								$precioUnitario = $rowDet['precio_unitario'];
								$costoUnitario = $rowDet['costo_compra'];
								$porcIva = $rowDet['porcentaje_impuesto'];
								
								$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
								$baseComision = floatval($precioUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
								
								$montoComision = floatval(($porcComision * $baseComision) / 100);
								
								$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_articulo, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
								VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
									valTpDato($idComisionEmpleado, "int"),
									valTpDato($idTipoPorcentaje, "int"),
									valTpDato($rowDet['id_articulo'], "int"),
									valTpDato($rowDet['cantidad'], "real_inglesa"),
									valTpDato($costoUnitario, "real_inglesa"),
									valTpDato($precioUnitario, "real_inglesa"),
									valTpDato($porcComision, "real_inglesa"),
									valTpDato($montoComision, "real_inglesa"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
								
								$arrayComision[0] += $cantidad * $precioUnitario;
								$arrayComision[1] += $cantidad * $descuento;
								$arrayComision[2] += $cantidad * $costoUnitario;
								$arrayComision[3] += $porcComision;
								$arrayComision[4] += $cantidad * $montoComision;
							}
						}
						
						if ($row['tipo_comision'] == 5 && $rowFact['items_vehiculos'] > 0) {
							
							$queryCom = sprintf("SELECT * FROM `pg_comision`
									WHERE `tipo_porcentaje` = 3 AND `tipo_importe` = 5 AND `tipo_comision` = 5 AND `id_comision` = %s;",
									valTpDato($row['id_comision'], "int"));
							$rsCom = mysql_query($queryCom);
							$valComision = mysql_num_rows($rsCom);
								
							if( $valComision > 0 && $rowFact['estatus_nota_credito'] == 2){  //estatus_nota_credito = 2 => Aplicada
								$idComision = $row['id_comision'];
								
								// Calculo de la unidades vendida por el empleado
								$mesComision = date('m');
								$anoComision = date('Y');
								
								// Calculo de la unidades vendida por el empleado
								$queryEmp = sprintf("SELECT idNotaCredito, idDocumento, fechaNotaCredito, cc_cl.nombre as nombre_cliente
											FROM `cj_cc_notacredito`
										    LEFT JOIN cj_cc_cliente cc_cl ON cc_cl.id = idCliente
											WHERE (YEAR(fechaNotaCredito) < %s OR (YEAR(fechaNotaCredito) = %s AND MONTH(fechaNotaCredito) <= %s )) 
										    	AND estatus_nota_credito = 2 AND id_empleado_vendedor = %s AND idDocumento <> 0;",
										valTpDato($anoComision , "int"),
										valTpDato($anoComision, "int"),
										valTpDato($mesComision, "int"),
										valTpDato($row['id_empleado'], "int"));
								$rsEmp = mysql_query($queryEmp, $conex);
								if (!$rsEmp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
								
								//Se consulta la condicion de la unidad vendida
								$unidadNueva = 0;
								$unidadUsada = 0;
								
								while ($rowUniFis = mysql_fetch_assoc($rsEmp)) {
									$queryUniFis = sprintf("SELECT uf.id_condicion_unidad FROM cj_cc_encabezadofactura ef
											LEFT JOIN cj_cc_factura_detalle_vehiculo fdv ON ef.idFactura = fdv.id_factura
											LEFT JOIN an_unidad_fisica uf ON uf.id_unidad_fisica = fdv.id_unidad_fisica
										    WHERE ef.idFactura = %s;",
											valTpDato($rowUniFis['idDocumento'], "int"));
									$rsUniFis = mysql_query($queryUniFis, $conex);
									if (!$rsUniFis) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
									$con = mysql_fetch_assoc($rsUniFis);
								
									if($con['id_condicion_unidad'] == 1){
										$unidadNueva++;
									} else{
										$unidadUsada++;
									}
								}
								
								//Se consulta
								$queryIdPro = sprintf("SELECT id_comision FROM pg_comision
										WHERE id_modulo = 2 AND tipo_porcentaje = 4 AND tipo_importe = 3 AND tipo_comision = 6
										AND id_cargo_departamento = %s;",
										valTpDato($rowEmpleado['id_cargo_departamento'], "int"));
								$rsIdPro = mysql_query($queryIdPro, $conex);
								$rowIdPro = mysql_fetch_assoc($rsIdPro);
								$idComisionProducto = $rowIdPro['id_comision'];
								
								// Calculo del producto de la comision
								$queryProduct = sprintf("SELECT SUM(coma.monto) as monto, SUM(coma.porcentaje) as porcentaje FROM cj_cc_factura_detalle_accesorios facc
												LEFT JOIN pg_comision_articulo coma ON coma.id_articulo = facc.id_accesorio
											    WHERE facc.id_factura = %s AND coma.id_comision = %s;",
										valTpDato($rowFact['idDocumento'], "int"),
										valTpDato($idComisionProducto, "int"));
								$rsPro = mysql_query($queryProduct, $conex);
								if (!$rsPro) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
									
								$rowPro = mysql_fetch_assoc($rsPro);
								$productos = $rowPro['monto'];
								
								//	CALCULO DEL PORCENTAJE DE COMISION SEGUN EL RANGO
								$queryPor = sprintf("SELECT * FROM `pg_comision_productividad_unidad` com_pro
											LEFT JOIN pg_comision com ON com.id_comision = com_pro.id_comision
											WHERE com.id_cargo_departamento = %s;",
										valTpDato($rowEmpleado['id_cargo_departamento'], "int"));
								
								$rsPor = mysql_query($queryPor, $conex);
								if (!$rsPor) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
								while ($rowPor = mysql_fetch_assoc($rsPor)) {
									if($rowPor['tipo_2'] < 0){
										if($unidadUsada < $unidadNueva || $unidadUsada == $unidadNueva){
											if($unidadNueva >= 25 && $rowPor['menor_igual'] == 0){
												$porcentaje = $rowPor['porcentaje'];
											} elseif( in_array($unidadNueva , range($rowPor['mayor_igual'], $rowPor['menor_igual'])) && $unidadNueva > 0 && $rowPor['menor_igual'] > 0) {
												$porcentaje = $rowPor['porcentaje'];
											}
										} else{
											if( in_array($unidadUsada, range($rowPor['mayor_igual'], $rowPor['menor_igual']))  && $unidadUsada > 0 && $rowPor['menor_igual'] > 0) {
												$porcentaje = $rowPor['porcentaje'];
											}
										}
									} else{ 
										if($unidadUsada > 0){
											if( in_array($unidadNueva, range($rowPor['mayor_igual'], $rowPor['menor_igual'])) ) {
												if( in_array($unidadUsada, range($rowPor['mayor_igual_2'], $rowPor['menor_igual_2'])) ) {
													$porcentaje = $rowPor['porcentaje'];
												}
											}
										}
									}
								}
								
								$porcentaje = ($porcentaje == '' || $porcentaje == 0) ? 0 : $porcentaje;
								
								$queryDet = sprintf("SELECT * FROM cj_cc_factura_detalle_vehiculo WHERE id_factura = %s;",
										valTpDato($rowFact['idDocumento'], "int"));
								$rsDet = mysql_query($queryDet, $conex);
								if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);

								while ($rowDet = mysql_fetch_assoc($rsDet)) {
									$cantidad = 1;
									$precioUnitario = $rowDet['precio_unitario'];
									$costoUnitario = $rowDet['costo_compra'];
										
									if($porcentaje >= 22) $productos += 1000; // Bono al empleado al superar el 22% de la comision
									if($porcentaje > 0) $montoComision = floatval(($precioUnitario-$costoUnitario)*$porcentaje/100 + $productos);
									else $montoComision = 0;
									$porcentaje = ($montoComision == 0)? 0 : $porcentaje;
							
									$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_unidad_fisica, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
										VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
											valTpDato($idComisionEmpleado, "int"),
											valTpDato($idTipoPorcentaje, "int"),
											valTpDato($rowDet['id_unidad_fisica'], "int"),
											valTpDato($cantidad, "real_inglesa"),
											valTpDato($costoUnitario, "real_inglesa"),
											valTpDato($precioUnitario, "real_inglesa"),
											valTpDato($porcentaje, "real_inglesa"),
											valTpDato($montoComision, "real_inglesa"));
									$Result1 = mysql_query($insertSQL);
										
									if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
										
									$arrayComision[0] += $cantidad * $precioUnitario;
									$arrayComision[1] += $cantidad * $descuento;
									$arrayComision[2] += $cantidad * $costoUnitario;
									$arrayComision[3] += $porcentaje;
									$arrayComision[4] += $cantidad * $montoComision;
								}
							}
						}
						if ($row['tipo_comision'] == 6 && $rowFact['items_accesorios'] > 0) {
							$queryDet = sprintf("SELECT * FROM an_accesorio acc
								INNER JOIN cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc ON (acc.id_accesorio = cxc_nc_det_acc.id_accesorio)
							WHERE acc.genera_comision = 1
								AND cxc_nc_det_acc.id_nota_credito = %s;",
								valTpDato($idDocumento, "int"));
							$rsDet = mysql_query($queryDet, $conex);
							if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$totalRowsDet = mysql_num_rows($rsDet);
							while ($rowDet = mysql_fetch_assoc($rsDet)) {
								$cantidad = 1;
								$precioUnitario = $rowDet['precio_unitario'];
								$costoUnitario = $rowDet['costo_compra'];
								$porcIva = $rowFact['porcentajeIvaFactura'];
								
								$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
								$baseComision = floatval($precioUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
								
								$montoComision = floatval(($porcComision * $baseComision) / 100);
								
								$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_accesorio, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
								VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
									valTpDato($idComisionEmpleado, "int"),
									valTpDato($idTipoPorcentaje, "int"),
									valTpDato($rowDet['id_accesorio'], "int"),
									valTpDato($cantidad, "real_inglesa"),
									valTpDato($costoUnitario, "real_inglesa"),
									valTpDato($precioUnitario, "real_inglesa"),
									valTpDato($porcComision, "real_inglesa"),
									valTpDato($montoComision, "real_inglesa"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
								
								$arrayComision[0] += $cantidad * $precioUnitario;
								$arrayComision[1] += $cantidad * $descuento;
								$arrayComision[2] += $cantidad * $costoUnitario;
								$arrayComision[3] += $porcComision;
								$arrayComision[4] += $cantidad * $montoComision;
							}
						}
						
						if ($row['tipo_comision'] == 5 && $rowFact['items_vehiculos'] > 0) {
							$porcentajeCom = "porcentaje_comision = {$porcentaje}";
						} else{
							$porcentajeCom = sprintf("porcentaje_comision = (%s / (SELECT COUNT(id_comision_empleado) FROM pg_comision_empleado_detalle
											WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado))",
									valTpDato($arrayComision[3], "real_inglesa"));
						}

						if($arrayComision[0] > 0){
							$updateSQL = sprintf("UPDATE pg_comision_empleado SET
								venta_bruta = %s,
								monto_descuento = %s,
								costo_compra = %s,
								%s,
								monto_comision = %s
							WHERE id_comision_empleado = %s;",
									valTpDato($arrayComision[0], "real_inglesa"),
									valTpDato($arrayComision[1], "real_inglesa"),
									valTpDato($arrayComision[2], "real_inglesa"),
									$porcentajeCom,
									valTpDato($arrayComision[4], "real_inglesa"),
									valTpDato($idComisionEmpleado, "int"));
								
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						} elseif ($row['tipo_comision'] == 5 && $rowFact['items_vehiculos'] > 0) {
							if($idComisionEmpleado > 0){
								$deleteSQL = sprintf("DELETE FROM `pg_comision_empleado` WHERE id_comision_empleado = %s",
										valTpDato($idComisionEmpleado, "int"));
								$Result = mysql_query($deleteSQL);
								if (!$Result) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							}
						} 						
					}
				}
			}
		}
	}
	return array(true, "");
}
?>