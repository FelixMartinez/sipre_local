<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
require_once("../../connections/conex.php");
session_start();

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

include("clase_excel.php");

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = $valCadBusq[0];
	
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	((CASE kardex.tipoMovimiento
		WHEN 1 THEN -- COMPRA
			(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
		WHEN 2 THEN -- ENTRADA
			(CASE kardex.tipo_documento_movimiento
				WHEN 1 THEN -- ENTRADA CON VALE
					(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
					(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
			END)
		WHEN 3 THEN -- VENTA
			(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
		WHEN 4 THEN -- SALIDA
			(CASE kardex.tipo_documento_movimiento
				WHEN 1 THEN -- SALIDA CON VALE
					(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
				WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
					(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
			END)
	END) = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = (CASE kardex.tipoMovimiento
										WHEN 1 THEN -- COMPRA
											(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
										WHEN 2 THEN -- ENTRADA
											(CASE kardex.tipo_documento_movimiento
												WHEN 1 THEN -- ENTRADA CON VALE
													(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
												WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
													(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
											END)
										WHEN 3 THEN -- VENTA
											(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
										WHEN 4 THEN -- SALIDA
											(CASE kardex.tipo_documento_movimiento
												WHEN 1 THEN -- SALIDA CON VALE
													(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
												WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
													(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
											END)
									END)))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(fechaMovimiento) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d",strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("(vw_iv_modelo.id_uni_bas = %s
	OR vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_marca LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s)",
		valTpDato($valCadBusq[4], "int"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT 
	vw_iv_modelo.id_uni_bas,
	vw_iv_modelo.nom_uni_bas,
	CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo
FROM an_kardex kardex
	INNER JOIN an_unidad_fisica uni_fis ON (kardex.idUnidadFisica = uni_fis.id_unidad_fisica)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas) %s
GROUP BY 1,2,3
ORDER BY vw_iv_modelo.nom_uni_bas ASC;", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
$nroHoja = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$idUnidadBasica = $row['id_uni_bas'];
	
	//Trabajamos con la hoja activa secundaria
	if ($nroHoja > 0 && $valCadBusq[3] == 1) {
		$objPHPExcel->createSheet(NULL, $nroHoja);
		$contFilaY = 0;
	}
	$objPHPExcel->setActiveSheetIndex($nroHoja);
	
	if ($valCadBusq[3] == 2) {
		$contFilaY++;
		$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFilaY, "Kardex ".$row['nom_uni_bas']);
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayTitulo);
		$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":P".$contFilaY);
	}
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFilaY, "Unidad Básica:");
	$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFilaY, $row['nom_uni_bas']);
	$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFilaY, "C: Compra");
	$objPHPExcel->getActiveSheet()->SetCellValue("H".$contFilaY, "E: Entrada");
	$objPHPExcel->getActiveSheet()->SetCellValue("I".$contFilaY, "E-NC: Entrada por Nota de Crédito");
	$objPHPExcel->getActiveSheet()->SetCellValue("L".$contFilaY, "E-TRNS.ALM: Entrada por Transferencia de Almacen");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":B".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("C".$contFilaY.":F".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("I".$contFilaY.":K".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("L".$contFilaY.":O".$contFilaY);
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFilaY, "Descripción:");
	$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFilaY, $row['vehiculo']);
	$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFilaY, "V: Venta");
	$objPHPExcel->getActiveSheet()->SetCellValue("H".$contFilaY, "S: Salida");
	$objPHPExcel->getActiveSheet()->SetCellValue("I".$contFilaY, "S-GRNTA: Salida por Garantía");
	$objPHPExcel->getActiveSheet()->SetCellValue("L".$contFilaY, "S-TRNS.ALM: Salida por Transferencia de Almacen");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":B".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("C".$contFilaY.":F".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("I".$contFilaY.":K".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("L".$contFilaY.":O".$contFilaY);
	
	$contFilaY++;
	$primero = $contFilaY;
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFilaY, "     ");
	$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFilaY, "     ");
	$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFilaY, "Fecha");
	$objPHPExcel->getActiveSheet()->SetCellValue("D".$contFilaY, "Empresa");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFilaY, $spanSerialCarroceria);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFilaY, $spanSerialMotor);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFilaY, "T");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY, "Nro. Documento");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFilaY, "C/P/N");
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFilaY, "E/S");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, "Lote");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, "Saldo");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, $spanPrecioUnitario);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, "Costo Unit.");
	
	$objPHPExcel->getActiveSheet()->mergeCells("H".$contFilaY.":I".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("J".$contFilaY.":K".$contFilaY);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":P".$contFilaY)->applyFromArray($styleArrayColumna);
	
	$queryArticuloSaldoAnt = sprintf("SELECT
		(IFNULL((SELECT SUM(k.cantidad) FROM an_kardex k
		WHERE k.idUnidadBasica = %s
			AND DATE(k.fechaMovimiento) < %s
			AND k.tipoMovimiento IN (1,2)),0)
		-
		IFNULL((SELECT SUM(k.cantidad) FROM an_kardex k
		WHERE k.idUnidadBasica = %s
			AND DATE(k.fechaMovimiento) < %s
			AND k.tipoMovimiento IN (3,4)),0)) AS saldo_anterior",
		valTpDato($idUnidadBasica, "int"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
		valTpDato($idUnidadBasica, "int"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"));
	$rsArticuloSaldoAnt = mysql_query($queryArticuloSaldoAnt);
	if (!$rsArticuloSaldoAnt) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowArticuloSaldoAnt = mysql_fetch_assoc($rsArticuloSaldoAnt);
	$totalEntrada = 0;
	$totalValorEntrada = 0;
	$totalSalida = 0;
	$totalValorSalida = 0;
	$entradaSalida = 0;
	$contFilaY2 = 0;
	if ($rowArticuloSaldoAnt['saldo_anterior'] != 0) {
		$clase = (fmod($contFilaY2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFilaY++;
		$contFilaY2++;
		
		$totalEntrada = $rowArticuloSaldoAnt['saldo_anterior'];
		$entradaSalida = $rowArticuloSaldoAnt['saldo_anterior'];
		
		$saldoAnterior = $rowArticuloSaldoAnt['saldo_anterior'];
		
		$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFilaY, "Saldo Anterior al Intervalo de Fecha Seleccionado:");
		$objPHPExcel->getActiveSheet()->SetCellValue("N".$contFilaY, $saldoAnterior);
		
		$objPHPExcel->getActiveSheet()->mergeCells("B".$contFilaY.":I".$contFilaY);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":P".$contFilaY)->applyFromArray($clase);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY.":I".$contFilaY)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("uni_bas.id_uni_bas = %s",
		valTpDato($row['id_uni_bas'],"int"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("
		((CASE kardex.tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
			WHEN 2 THEN -- ENTRADA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
					WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
						(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
				END)
		END) = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = (CASE kardex.tipoMovimiento
											WHEN 1 THEN -- COMPRA
												(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- ENTRADA CON VALE
														(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
													WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
														(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
												END)
											WHEN 3 THEN -- VENTA
												(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
											WHEN 4 THEN -- SALIDA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- SALIDA CON VALE
														(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
													WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
														(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
												END)
										END)))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("DATE(fechaMovimiento) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(vw_iv_modelo.id_uni_bas = %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_marca LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$queryDetalle = sprintf("SELECT
		kardex.idKardex,
		
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
			WHEN 2 THEN -- ENTRADA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
					WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
						(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
				END)
		END) AS id_empresa,
		
		kardex.fechaMovimiento,
		(SELECT
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		WHERE vw_iv_emp_suc.id_empresa_reg = (CASE kardex.tipoMovimiento
												WHEN 1 THEN -- COMPRA
													(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
												WHEN 2 THEN -- ENTRADA
													(CASE kardex.tipo_documento_movimiento
														WHEN 1 THEN -- ENTRADA CON VALE
															(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
														WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
															(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
													END)
												WHEN 3 THEN -- VENTA
													(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
												WHEN 4 THEN -- SALIDA
													(CASE kardex.tipo_documento_movimiento
														WHEN 1 THEN -- SALIDA CON VALE
															(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
														WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
															(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
													END)
											END)) AS nombre_empresa,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		kardex.id_documento,
		(CASE tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT fact_comp.numero_factura_proveedor FROM cp_factura fact_comp WHERE fact_comp.id_factura = kardex.id_documento)
			WHEN 2 THEN -- ENTRADA
				(CASE tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(SELECT vale_ent.numeracion_vale_entrada FROM an_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT nota_cred.numeracion_nota_credito FROM cj_cc_notacredito nota_cred WHERE nota_cred.idNotaCredito = kardex.id_documento)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT fact_vent.numeroFactura FROM cj_cc_encabezadofactura fact_vent WHERE fact_vent.idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT vale_sal.numeracion_vale_salida FROM an_vale_salida vale_sal WHERE vale_sal.id_vale_salida = kardex.id_documento)
					WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
						(SELECT nota_cred.numero_nota_credito FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
				END)
		END) AS numero_documento,
		
		2 AS id_modulo,
		(CASE 2
			WHEN 0 THEN		'R'
			WHEN 1 THEN		'S'
			WHEN 2 THEN		'V'
		END) AS nombre_modulo,
		
		kardex.tipoMovimiento,
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN	'C'
			WHEN 2 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						(CASE (SELECT tipo_vale_entrada FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							WHEN 4 THEN
								'E-TRNS.ALM'
							ELSE
								'E'
						END)
					WHEN 2 THEN
						'E-NC'
				END)
			WHEN 3 THEN 'V'
			WHEN 4 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						(CASE (SELECT tipo_vale_salida FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
							WHEN 4 THEN
								'S-TRNS.ALM'
							ELSE
								'S'
						END)
					WHEN 2 THEN
						'S-NC'
				END)
		END) AS nombre_tipo_movimiento,
		
		kardex.claveKardex,
		kardex.tipo_documento_movimiento,
		kardex.estadoKardex,
		DATE(kardex.fechaMovimiento) AS fechaMovimiento,
		
		(CASE tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT CONCAT_WS('-', prov.lrif, prov.rif) AS ciPCE
				FROM cp_factura fact_comp
					INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
				WHERE fact_comp.id_factura = kardex.id_documento
					AND fact_comp.id_modulo IN (2))
			WHEN 2 THEN -- ENTRADA
				(CASE tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(SELECT CONCAT_WS('-',cliente.lci,cliente.ci) AS ciPCE
						FROM an_vale_entrada vale_ent
							INNER JOIN cj_cc_cliente cliente ON (vale_ent.id_cliente = cliente.id)
						WHERE vale_ent.id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT CONCAT_WS('-',cliente.lci,cliente.ci) AS ciPCE
						FROM cj_cc_notacredito nota_cred
							INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
							INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id)
						WHERE nota_cred.idNotaCredito = kardex.id_documento
							AND nota_cred.idDepartamentoNotaCredito IN (2)
							AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT CONCAT_WS('-',cliente.lci,cliente.ci) AS ciPCE
				FROM cj_cc_encabezadofactura fact_vent
					INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
				WHERE fact_vent.idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT CONCAT_WS('-',cliente.lci,cliente.ci) AS ciPCE
						FROM an_vale_salida vale_sal
							INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
						WHERE vale_sal.id_vale_salida = kardex.id_documento)
				END)
		END) AS ciPCE,
		
		(CASE tipoMovimiento
			WHEN 1 THEN
				(SELECT prov.nombre
				FROM cp_factura fact_comp
					INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
				WHERE fact_comp.id_factura = kardex.id_documento
					AND fact_comp.id_modulo IN (2))
			WHEN 2 THEN
				(CASE tipo_documento_movimiento
					WHEN 1 THEN
						(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
						FROM an_vale_entrada vale_ent
							INNER JOIN cj_cc_cliente cliente ON (vale_ent.id_cliente = cliente.id)
						WHERE vale_ent.id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN
						(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
						FROM cj_cc_notacredito nota_cred
							INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
							INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id)
						WHERE nota_cred.idNotaCredito = kardex.id_documento
							AND nota_cred.idDepartamentoNotaCredito IN (2)
							AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				END)
			WHEN 3 THEN
				(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
				FROM cj_cc_encabezadofactura fact_vent
					INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
				WHERE fact_vent.idFactura = kardex.id_documento)
			WHEN 4 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
						FROM an_vale_salida vale_sal
							INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
						WHERE vale_sal.id_vale_salida = kardex.id_documento)
				END)
		END) AS nombrePCE,
		
		(CASE tipoMovimiento
			WHEN 2 THEN
				(CASE tipo_documento_movimiento
					WHEN 1 THEN
						(SELECT vale_ent.tipo_vale_entrada FROM an_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = kardex.id_documento)
				END)
			WHEN 4 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						(SELECT vale_sal.tipo_vale_salida FROM an_vale_salida vale_sal WHERE vale_sal.id_vale_salida = kardex.id_documento)
				END)
		END) AS tipo_vale,
		
		kardex.cantidad,
		kardex.precio,
		kardex.costo,
		kardex.costo_cargo,
		kardex.porcentaje_descuento,
		kardex.subtotal_descuento,
		
		(CASE tipoMovimiento
			WHEN 1 THEN
				uni_fis.precio_compra
			WHEN 2 THEN
				(CASE tipo_documento_movimiento
					WHEN 1 THEN
						uni_fis.precio_compra
					WHEN 2 THEN
						(SELECT fact_vent_det_vehic.precio_unitario
						FROM cj_cc_notacredito nota_cred
							INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
						WHERE nota_cred.idNotaCredito = kardex.id_documento
							AND nota_cred.idDepartamentoNotaCredito IN (2)
							AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				END)
			WHEN 3 THEN
				(SELECT fact_vent_det_vehic.precio_unitario FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
				WHERE fact_vent_det_vehic.id_factura = kardex.id_documento
					AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
			WHEN 4 THEN
				(CASE tipo_documento_movimiento
					WHEN 1 THEN
						uni_fis.precio_compra
					WHEN 2 THEN
						uni_fis.precio_compra
				END)
		END) AS precio_unidad_dcto,
		
		(CASE tipoMovimiento
			WHEN 1 THEN
				uni_fis.precio_compra
			WHEN 2 THEN
				(CASE tipo_documento_movimiento
					WHEN 1 THEN
						uni_fis.precio_compra
					WHEN 2 THEN
						(SELECT fact_vent_det_vehic.costo_compra
						FROM cj_cc_notacredito nota_cred
							INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
						WHERE nota_cred.idNotaCredito =kardex.id_documento
							AND nota_cred.idDepartamentoNotaCredito IN (2)
							AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				END)
			WHEN 3 THEN
				(SELECT fact_vent_det_vehic.costo_compra FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
				WHERE fact_vent_det_vehic.id_factura = kardex.id_documento
					AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
			WHEN 4 THEN
				(CASE tipo_documento_movimiento
					WHEN 1 THEN
						uni_fis.precio_compra
					WHEN 2 THEN
						uni_fis.precio_compra
				END)
		END) AS costo_unidad_dcto
	FROM an_unidad_fisica uni_fis
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_kardex kardex ON (uni_fis.id_unidad_fisica = kardex.idUnidadFisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas) %s
	ORDER BY kardex.fechaMovimiento ASC, kardex.idKardex ASC", $sqlBusq2);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	$contFilaY2 = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)){
		$clase = (fmod($contFilaY2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFilaY++;
		$contFilaY2++;
			
		$idEmpresa = $rowDetalle['id_empresa'];
		$idModulo = $rowDetalle['id_modulo'];
			
		switch ($rowDetalle['tipoMovimiento']) {
			case 1 : // COMPRA
				$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
				$precioUnitario = $costoUnitario; break;
			case 2 : // ENTRADA
				switch($rowDetalle['tipo_documento_movimiento']) {
					case 1 : // VALE
						$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
						$precioUnitario = ($idModulo == 1) ? $rowDetalle['precio'] : $costoUnitario;
						break;
					case 2 : // NOTA CREDITO
						$costoUnitario = $rowDetalle['costo'];
						$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
						break;
				}
				break;
			case 3 : // VENTA
				$costoUnitario = $rowDetalle['costo'];
				$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
			case 4 : // SALIDA
				switch($rowDetalle['tipo_documento_movimiento']) {
					case 1 : 
						$costoUnitario = $rowDetalle['costo'];
						$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
						break;
					case 2 : 
						$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
						$precioUnitario = $costoUnitario;
						break;
				}
				break;
		}
		
		if ($rowDetalle['estadoKardex'] == 0) {
			$totalEntrada += $rowDetalle['cantidad'];
			$totalValorEntrada += $rowDetalle['cantidad'] * $precioUnitario;
			$entradaSalida += $rowDetalle['cantidad'];
		} else if ($rowDetalle['estadoKardex'] == 1) {
			$totalSalida += $rowDetalle['cantidad'];
			$totalValorSalida += $rowDetalle['cantidad'] * $precioUnitario;
			$entradaSalida -= $rowDetalle['cantidad'];
		}
		
		$imgInterAlmacen = ($rowDetalle['nombre_tipo_movimiento'] == "E-TRNS.ALN" || $rowDetalle['nombre_tipo_movimiento'] == "S-TRNS.ALN") ? "TRNS.ALN" : " ";
		
		$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFilaY, $contFilaY2); //$rowDetalle['idKardex']
		$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFilaY, $imgInterAlmacen);
		$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFilaY, implode("-",array_reverse(explode("-",$rowDetalle['fechaMovimiento']))));
		$objPHPExcel->getActiveSheet()->SetCellValue("D".$contFilaY, utf8_encode($rowDetalle['nombre_empresa']));
		$objPHPExcel->getActiveSheet()->SetCellValue("E".$contFilaY, utf8_encode($rowDetalle['serial_carroceria']));
		$objPHPExcel->getActiveSheet()->SetCellValue("F".$contFilaY, utf8_encode($rowDetalle['serial_motor']));
		$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFilaY, utf8_encode($rowDetalle['nombre_tipo_movimiento']));
		$objPHPExcel->getActiveSheet()->SetCellValue("H".$contFilaY, utf8_encode($rowDetalle['nombre_modulo']));
		$objPHPExcel->getActiveSheet()->SetCellValue("I".$contFilaY, utf8_encode($rowDetalle['numero_documento']));
		$objPHPExcel->getActiveSheet()->SetCellValue("J".$contFilaY, utf8_encode($rowDetalle['ciPCE']));
		$objPHPExcel->getActiveSheet()->SetCellValue("K".$contFilaY, utf8_encode($rowDetalle['nombrePCE']));
		$objPHPExcel->getActiveSheet()->SetCellValue("L".$contFilaY, $rowDetalle['cantidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, $rowDetalle['id_articulo_costo']);
		$objPHPExcel->getActiveSheet()->SetCellValue("N".$contFilaY, $entradaSalida);
		$objPHPExcel->getActiveSheet()->SetCellValue("O".$contFilaY, $precioUnitario);
		$objPHPExcel->getActiveSheet()->SetCellValue("P".$contFilaY, $costoUnitario);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":P".$contFilaY)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}
	$ultimo = $contFilaY;
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFilaY, "Totales:");
	
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, "E #:");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, $totalEntrada);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, "E:");
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $totalValorEntrada);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":I".$contFilaY);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFilaY.":"."P".$contFilaY)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, "S #:");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, $totalSalida);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, "S:");
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $totalValorSalida);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":I".$contFilaY);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFilaY.":"."P".$contFilaY)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":P".$ultimo);
	
	if ($valCadBusq[3] == 1 || ($valCadBusq[3] == 2 && $contFila == $totalRows)) {
		cabeceraExcel($objPHPExcel, $idEmpresa, "P");
	
		$tituloDcto = ($valCadBusq[3] == 1) ? $row['nom_uni_bas'] : "Listado Kardex ";
		$tituloHoja = ($valCadBusq[3] == 1) ? "Kardex ".$row['nom_uni_bas'] : "Listado Kardex";
		$tituloHoja .= " (".$valCadBusq[1]." al ".$valCadBusq[2].")";
		$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloHoja);
		$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
		$objPHPExcel->getActiveSheet()->mergeCells("A7:P7");
		
		//Titulo del libro y seguridad
		$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
		$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
		$objPHPExcel->getSecurity()->setLockWindows(true);
		$objPHPExcel->getSecurity()->setLockStructure(true);
		
		$nroHoja++;
	} else {
		cabeceraExcel($objPHPExcel, $idEmpresa, "P", false);
		
		$contFilaY++;
		$contFilaY++;
		$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFilaY, "");
	}
}

$tituloDcto = "ERP KARDEX";
$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getProperties()->setCreator("SIPRE ".cVERSION);
//$objPHPExcel->getProperties()->setLastModifiedBy("autor");
$objPHPExcel->getProperties()->setTitle($tituloDcto);
//$objPHPExcel->getProperties()->setSubject("Asunto");
//$objPHPExcel->getProperties()->setDescription("Descripcion");

// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$tituloDcto.'.xlsx"');
header('Cache-Control: max-age=0');

//Creamos el Archivo .xlsx
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>