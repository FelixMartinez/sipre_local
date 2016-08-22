<?php
set_time_limit(20000);
function listadoLibroVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15000, $totalRows = NULL) {
	
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	global $spanClienteCxC;
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$filtroEmpresa .= $cond.sprintf("(CASE 
											WHEN (SELECT COUNT(emp.id_empresa) FROM pg_empresa emp WHERE emp.id_empresa_padre = %s) > 1 THEN
												id_empresa IN (SELECT emp.id_empresa FROM pg_empresa emp WHERE emp.id_empresa_padre = %s) OR id_empresa IN (%s)
											ELSE
												id_empresa IN (%s)
										END)",
			valTpDato($valCadBusq[3], "int"),
			valTpDato($valCadBusq[3], "int"),
			valTpDato($valCadBusq[3], "int"),
			valTpDato($valCadBusq[3], "int"));
	}
	
	mysql_query("START TRANSACTION;");
	
	$query = sprintf("SELECT
		fechaRegistroFactura AS fecha_origen,
		aplicaLibros AS aplica_libros
	FROM
		cj_cc_encabezadofactura
	WHERE
		DATE(fechaRegistroFactura) BETWEEN '%s' AND '%s'
		AND aplicaLibros = '1'
		AND idDepartamentoOrigenFactura IN (%s) %s
	GROUP BY fechaRegistroFactura
		
		UNION
		
	SELECT
		fechaNotaCredito AS fecha_origen,
		aplicaLibros AS aplica_libros
	FROM
		cj_cc_notacredito
	WHERE
		DATE(fechaNotaCredito) BETWEEN '%s' AND '%s'
		AND aplicaLibros= '1'
		AND idDepartamentoNotaCredito IN (%s) %s					 
	GROUP BY fechaNotaCredito
		
		UNION
		
	SELECT
		fechaRegistroNotaCargo AS fecha_origen,
		aplicaLibros AS aplica_libros
	FROM
		cj_cc_notadecargo
	WHERE
		DATE(fechaRegistroNotaCargo) BETWEEN '%s' AND '%s'
		AND aplicaLibros = '1'
		AND idDepartamentoOrigenNotaCargo IN (%s) %s						 
	GROUP BY fechaRegistroNotaCargo
	ORDER BY 1",
		date("Y-m-d",strtotime($valCadBusq[0])),
		date("Y-m-d",strtotime($valCadBusq[1])),
		$valCadBusq[2],
		$filtroEmpresa,
		date("Y-m-d",strtotime($valCadBusq[0])),
		date("Y-m-d",strtotime($valCadBusq[1])),
		$valCadBusq[2],
		$filtroEmpresa,
		date("Y-m-d",strtotime($valCadBusq[0])),
		date("Y-m-d",strtotime($valCadBusq[1])),
		$valCadBusq[2],
		$filtroEmpresa);
		
	/*Consulta brutal donde me llame toooooooooodo*/
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$queryIva = sprintf("SELECT * FROM pg_iva
	WHERE estado = 1
		AND (pg_iva.tipo = 6 OR pg_iva.tipo = 2)
	ORDER BY pg_iva.tipo DESC");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
	$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig403 = mysql_query($queryConfig403);
	if (!$rsConfig403) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig403 = mysql_num_rows($rsConfig403);
	$rowConfig403 = mysql_fetch_assoc($rsConfig403);
	$valor = $rowConfig403['valor'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
	
	$contColum = mysql_num_rows($rsIva) + 1;
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" style=\"font-size:9px\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td rowspan=\"3\" width=\"\">"."Fecha del Documento"."</td>
				<td rowspan=\"3\" width=\"\">"."Tipo De Documento"."</td>
				<td rowspan=\"3\" width=\"\">"."N° de Factura"."</td>
				<td rowspan=\"3\" width=\"\">"."N° de Control"."</td>";
	
	$colspan = '11';
	
	if ($valor == 2 || $valor == 3) {//1 = VENEZUELA ; 2 = PANAMA ; 3 = PUERTO RICO
		$colspan = '12';
		$htmlTh .= "<td rowspan=\"3\" width=\"\">"."N° Fiscal"."</td>";
	}
				
	$htmlTh .= "<td rowspan=\"3\" width=\"\">"."Codigo"."</td>
				<td rowspan=\"3\" width=\"\">".$spanClienteCxC."</td>
				<td rowspan=\"3\" width=\"\">"."Cliente"."</td>
				<td rowspan=\"3\" width=\"\">"."N° Nota de Credito / Debito"."</td>
				<td rowspan=\"3\" width=\"\">"."Numero de Factura Afectada"."</td>
				<td rowspan=\"3\" width=\"\">"."Numero de Comprobante de Retencion"."</td>
				<td rowspan=\"3\" width=\"\">"."Fecha de Comprobante de Retencion. "."</td>
				<td rowspan=\"3\" width=\"\">"."Total de Ventas Incluyendo el IVA"."</td>
				<td rowspan=\"3\" width=\"\">"."Ventas Exentas"."</td>
				<td rowspan=\"3\" width=\"\">"."Ventas Exoneradas"."</td>";
	
	$htmlTh .= "<td colspan=\"".$contColum."\" width=\"\">"."No Contribuyente"."</td>
				<td colspan=\"".$contColum."\" width=\"\">"."Contribuyente"."</td>";
	
	$htmlTh .= "<td rowspan=\"3\" width=\"\">"."Impuesto Retenido"."</td>
			</tr>";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$htmlTh .= "<td rowspan=\"2\" width=\"\">"."Base Imponible"."</td>";
	
	while ($rowIva = mysql_fetch_array($rsIva)) {
		$htmlTh .= "<td width=\"\">"."Alicuota IVA"."</td>";
	}
	
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$htmlTh .= "<td rowspan=\"2\" width=\"\">"."Base Imponible"."</td>";
	
	while ($rowIva = mysql_fetch_array($rsIva)) {
		$htmlTh .= "<td width=\"\">"."Alicuota IVA"."</td>";
	}
	$htmlTh .= "</tr>";
	
	/* COLUMNAS DE IVAS DE Ventas No Contribuyentes*/
	$arrayIvaNoContribuyente = NULL;
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$contPosNoContribuyente = 0;
	while ($rowIva = mysql_fetch_array($rsIva)) {
		$htmlTh .= "<td width=\"\">".$rowIva['iva']."%</td>";
	}
	
	/* COLUMNAS DE IVAS DE Ventas de contribuyentes*/
	$arrayIvaContribuyente = NULL;
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$contPosContribuyente = 0;
	while ($rowIva = mysql_fetch_array($rsIva)) {		
		//if ($rowIva['activo'] == 1) {
			$htmlTh .= "<td width=\"\">".$rowIva['iva']."%</td>";
		//}		
	}
	$htmlTh .= "</tr>";
	
	//CONSULTA DE LAS RETENCIONES DE FACTURAS DE OTROS PERIODOS
	$queryRetencionesOtrosPeriodos = sprintf("SELECT
		pa.fechaPago, 
		fa.fechaRegistroFactura AS fecha_factura,
		pa.numeroFactura AS numero_factura,
		fa.numeroControl AS numero_control_factura,
		fa.consecutivo_fiscal AS consecutivo_fiscal,
		fa.idCliente AS id_cliente,
		CONCAT_WS('-', cli.lci, cli.ci) AS cedula_cliente,
		CONCAT_WS(' ', cli.nombre, cli.apellido) AS nombre_cliente,
		de.IvaRetenido AS iva_retenido,
		ca.fechaComprobante AS fecha_comprobante,
		ca.numeroComprobante AS numero_comprobante,
		fa.idFactura AS idFactura
		FROM sa_iv_pagos pa
		INNER JOIN cj_cc_encabezadofactura fa ON (fa.idFactura = pa.id_factura) 
		INNER JOIN cj_cc_cliente cli ON (cli.id = fa.idCliente)
		INNER JOIN cj_cc_retenciondetalle de ON (de.idFactura = fa.idFactura)
		INNER JOIN cj_cc_retencioncabezera ca ON (ca.idRetencionCabezera = de.idRetencionCabezera)
		WHERE (pa.fechaPago BETWEEN '%s' AND '%s')
			AND pa.formaPago = 9
			AND fa.fechaRegistroFactura < '%s'
			AND idDepartamentoOrigenFactura IN (%s)
			AND idDepartamentoOrigenFactura IN (0,1,3) %s
		
	UNION
		
	SELECT
		pa.fechaPago, 
		fa.fechaRegistroFactura AS fecha_factura,
		pa.numeroFactura AS numero_factura,
		fa.numeroControl AS numero_control_factura,
		fa.consecutivo_fiscal AS consecutivo_fiscal,
		fa.idCliente AS id_cliente,
		CONCAT_WS('-', cli.lci, cli.ci) AS cedula_cliente,
		CONCAT_WS(' ', cli.nombre, cli.apellido) AS nombre_cliente,
		de.IvaRetenido AS iva_retenido,
		ca.fechaComprobante AS fecha_comprobante,
		ca.numeroComprobante AS numero_comprobante,
		fa.idFactura AS idFactura
		FROM an_pagos pa
		INNER JOIN cj_cc_encabezadofactura fa ON (fa.idFactura = pa.id_factura) 
		INNER JOIN cj_cc_cliente cli ON (cli.id = fa.idCliente)
		INNER JOIN cj_cc_retenciondetalle de ON (de.idFactura = fa.idFactura)
		INNER JOIN cj_cc_retencioncabezera ca ON (ca.idRetencionCabezera = de.idRetencionCabezera)
		WHERE (pa.fechaPago BETWEEN '%s' AND '%s')
			AND pa.formaPago = 9
			AND fa.fechaRegistroFactura < '%s' 
			AND idDepartamentoOrigenFactura IN (%s)
			AND idDepartamentoOrigenFactura IN (2,4) %s
	ORDER BY 3 ASC",
		date("Y-m-d",strtotime($valCadBusq[0])),
		date("Y-m-d",strtotime($valCadBusq[1])),
		date("Y-m-d",strtotime($valCadBusq[0])),
		$valCadBusq[2],
		$filtroEmpresa,
		date("Y-m-d",strtotime($valCadBusq[0])),
		date("Y-m-d",strtotime($valCadBusq[1])),
		date("Y-m-d",strtotime($valCadBusq[0])),
		$valCadBusq[2],
		$filtroEmpresa);
	$rsRetencionesOtrosPeriodos = mysql_query($queryRetencionesOtrosPeriodos);
	if (!$rsRetencionesOtrosPeriodos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	while ($rowRetencionesOtrosPeriodos = mysql_fetch_array($rsRetencionesOtrosPeriodos)){
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
		
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".date("d-m-Y",strtotime($rowRetencionesOtrosPeriodos['fecha_factura']))."</td>";
			$htmlTb .= "<td align=\"center\">CR</td>";
			$htmlTb .= "<td align=\"right\">".$rowRetencionesOtrosPeriodos['numero_factura']."</td>";
			$htmlTb .= "<td align=\"right\">".$rowRetencionesOtrosPeriodos['numero_control_factura']."</td>";
			if ($valor == 2 || $valor == 3) {//1 = VENEZUELA ; 2 = PANAMA ; 3 = PUERTO RICO
				$htmlTb .= "<td align=\"right\">".$rowRetencionesOtrosPeriodos['consecutivo_fiscal']."</td>";
			}
			$htmlTb .= "<td align=\"center\">".$rowRetencionesOtrosPeriodos['id_cliente']."</td>";
			$htmlTb .= "<td align=\"right\">".$rowRetencionesOtrosPeriodos['cedula_cliente']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($rowRetencionesOtrosPeriodos['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\">".$rowRetencionesOtrosPeriodos['numero_comprobante']."</td>";
			$htmlTb .= "<td align=\"right\">".date("d-m-Y",strtotime($rowRetencionesOtrosPeriodos['fecha_comprobante']))."</td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"center\"></td>";
			$htmlTb .= "<td align=\"center\"></td>";
			$htmlTb .= "<td align=\"center\"></td>";
			$htmlTb .= "<td align=\"right\">".$rowRetencionesOtrosPeriodos['iva_retenido']."</td>";
		$htmlTb .= "</tr>";
		
		$totalRetencionesOtrosPeriodos += $rowRetencionesOtrosPeriodos['iva_retenido'];
	}
	
	$htmlTb.= "<tr class=\"trResaltar6\">";
			$htmlTb .= "<td align=\"center\" colspan='".$colspan."'>Total Retenciones de otros Periodos </td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"></td>";			
			$htmlTb .= "<td align=\"right\">".number_format($totalRetencionesOtrosPeriodos,2,",","")."</td>";
		$htmlTb .= "</tr>";
	
	$totalesComprasRetenido += $totalRetencionesOtrosPeriodos;
		
	$primerNumeroFacturaRepuesto = '-';
	$ultimoNumeroFacturaRepuesto = '-';
	$primerNumeroFacturaServicio = '-';
	$ultimoNumeroFacturaServicio = '-';
	$primerNumeroFacturaAlicuotaGeneralMasAdicional = '-';
	$ultimoNumeroFacturaAlicuotaGeneralMasAdicional = '-';
	$primerNumeroFacturaAlicuotaGeneral = '-';
	$ultimoNumeroFacturaAlicuotaGeneral = '-';
	$primerNumeroFacturaAdministracion = '-';
	$ultimoNumeroFacturaAdministracion = '-';
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$sqlEstadoCuenta = sprintf("
		SELECT
			cj_cc_estadocuenta.*,
			cj_cc_encabezadofactura.numeroControl
		FROM cj_cc_estadocuenta
			INNER JOIN cj_cc_encabezadofactura ON (cj_cc_estadocuenta.idDocumento = cj_cc_encabezadofactura.idFactura)
		WHERE cj_cc_estadocuenta.tipoDocumento= 'FA'
			AND idDepartamentoOrigenFactura IN (%s)
			AND DATE(fechaRegistroFactura) BETWEEN '%s' AND '%s' %s
			
		UNION
		
		SELECT
			cj_cc_estadocuenta.*,
			cj_cc_notacredito.numeroControl
		FROM cj_cc_estadocuenta
			INNER JOIN cj_cc_notacredito ON (cj_cc_estadocuenta.idDocumento = cj_cc_notacredito.idNotaCredito)
		WHERE cj_cc_estadocuenta.tipoDocumento= 'NC'
			AND idDepartamentoNotaCredito IN (%s)
			AND DATE(fechaNotaCredito) BETWEEN '%s' AND '%s' %s
			
		UNION
		
		SELECT
			cj_cc_estadocuenta.*,
			cj_cc_notadecargo.numeroControlNotaCargo
		FROM cj_cc_estadocuenta
			INNER JOIN cj_cc_notadecargo ON (cj_cc_estadocuenta.idDocumento = cj_cc_notadecargo.idNotaCargo)
		WHERE cj_cc_estadocuenta.tipoDocumento= 'ND'
			AND idDepartamentoOrigenNotaCargo IN (%s)
			AND DATE(fechaRegistroNotaCargo) BETWEEN '%s' AND '%s' %s
		ORDER BY 6",
			$valCadBusq[2],
			date("Y-m-d",strtotime($valCadBusq[0])),
			date("Y-m-d",strtotime($valCadBusq[1])),
			$filtroEmpresa,
			$valCadBusq[2],
			date("Y-m-d",strtotime($valCadBusq[0])),
			date("Y-m-d",strtotime($valCadBusq[1])),
			$filtroEmpresa,
			$valCadBusq[2],
			date("Y-m-d",strtotime($valCadBusq[0])),
			date("Y-m-d",strtotime($valCadBusq[1])),
			$filtroEmpresa);
		$rsEstadoCuenta = mysql_query($sqlEstadoCuenta);
		if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$totalTotalCompraConIva = 0;
		$totalComprasExentas = 0;
		$totalComprasExonerado = 0;
		//$arrayTotalIvaDocNoContribuyente = NULL;
		//$arrayTotalIvaDocContribuyente = NULL;
		$totalRetenido = 0;
		
		$totalBaseImponibleDiaNoContribuyente = 0;
		$totalIvaDiaNoContribuyente = 0;
		$totalIvaLujoDiaNoContribuyente = 0;
		$totalBaseImponibleDiaContribuyente = 0;
		$totalIvaDiaContribuyente = 0;
		$totalIvaLujoDiaContribuyente = 0;
		
		while($rowEstadoCuenta = mysql_fetch_array($rsEstadoCuenta)) {
			$sumaRetenciones = 0;
			
			for ($i = 0; $i < $contPosNoContribuyente; $i++) {
				$arrayIvaDocNoContribuyente[$i] = "";
			}
			for ($i = 0; $i < $contPosContribuyente; $i++) {
				$arrayIvaDocContribuyente[$i] = "";
			}
			
			if($rowEstadoCuenta['tipoDocumento'] == "FA") { /*-------FACTURA--------*/
				$sqlDocumento = sprintf("SELECT * FROM cj_cc_encabezadofactura
				WHERE idFactura = '%s'
					AND fechaRegistroFactura = '%s'
					AND aplicaLibros = '1'
					AND idDepartamentoOrigenFactura IN (%s) %s
				ORDER BY fechaRegistroFactura",
					$rowEstadoCuenta['idDocumento'],
					$row['fecha_origen'],
					$valCadBusq[2],
					$filtroEmpresa);
				
				/*$sqlFacturaGasto = sprintf("SELECT * FROM cp_factura_gasto
				WHERE id_factura = '%s'",
					$rowEstadoCuenta['idDocumento']);
				$rsFacturaGasto = mysql_query($sqlFacturaGasto);
				if (!$rsFacturaGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				while ($rowFacturaGasto = mysql_fetch_array($rsFacturaGasto)) {
					$ivaGasto = ($rowFacturaGasto['monto']*($rowFacturaGasto['iva']/100));
					$gastosFactura += $rowFacturaGasto['monto']*($rowFacturaGasto['iva']/100);
					$gastoTotalFactura += $ivaGasto + $rowFacturaGasto['monto'];
				}
				
				$sqlFacturaIva = sprintf("SELECT * FROM cp_factura_iva
				WHERE id_factura = '%s'",
					$rowEstadoCuenta['idDocumento']);
				$rsFacturaIva = mysql_query($sqlFacturaIva);
				if (!$rsFacturaIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowFacturaIva = mysql_fetch_array($rsFacturaIva)) {
					$ivaTotalFactura += $rowFacturaIva['base_imponible'] + $rowFacturaIva['subtotal_iva'];
					$ivasFactura += $rowFacturaIva['subtotal_iva'];
				}*/				
			} else if($rowEstadoCuenta['tipoDocumento'] == "NC") { /*-------NOTA DE CRÉDITO--------*/
				$sqlDocumento = sprintf("SELECT * FROM cj_cc_notacredito
				WHERE idNotaCredito = '%s'
					AND fechaNotaCredito = '%s'
					AND aplicaLibros = '1'
					AND idDepartamentoNotaCredito IN (%s) %s
				ORDER BY fechaNotaCredito",
					$rowEstadoCuenta['idDocumento'],
					$row['fecha_origen'],
					$valCadBusq[2],
					$filtroEmpresa);
				
			} else if($rowEstadoCuenta['tipoDocumento'] == "ND"){ /*-------NOTA DE DÉBITO--------*/
				$sqlDocumento = sprintf("SELECT * FROM cj_cc_notadecargo
				WHERE idNotaCargo = '%s'
					AND fechaRegistroNotaCargo = '%s'
					AND aplicaLibros = '1'
					AND idDepartamentoOrigenNotaCargo IN (%s) %s
				ORDER BY fechaRegistroNotaCargo",
					$rowEstadoCuenta['idDocumento'],
					$row['fecha_origen'],
					$valCadBusq[2],
					$filtroEmpresa);
			}
			
			$rsDocumento = mysql_query($sqlDocumento);
			if (!$rsDocumento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowDocumento = mysql_fetch_array($rsDocumento);
			
			if($rowEstadoCuenta["tipoDocumento"] == "FA" && mysql_num_rows($rsDocumento) == 1) {
				$fechaDocumento = date("d-m-Y",strtotime($rowDocumento["fechaRegistroFactura"]));
				$tipoDocumento = "FA";
				$nroFactura = $rowDocumento['numeroFactura'];
				$nroControl = $rowDocumento['numeroControl'];
				$nroFiscal = $rowDocumento['consecutivo_fiscal'];
				$serialImpresora = $rowDocumento['serial_impresora'];
				$nroNotaCredito = "-";
				$nroFacturaAfectada = "-";
				
				$sqlRetencion = sprintf("SELECT idFactura, SUM(IvaRetenido) AS IvaRetenido, idRetencionCabezera FROM cj_cc_retenciondetalle
				WHERE idFactura = '%s' GROUP BY idFactura",
					$rowEstadoCuenta['idDocumento']);
				$rsRetencion = mysql_query($sqlRetencion);
				if (!$rsRetencion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowRetencion = mysql_fetch_array($rsRetencion);
				
				$sqlRetenciones = sprintf("SELECT * FROM cj_cc_retencioncabezera
				WHERE idRetencionCabezera = '%s' AND (fechaComprobante BETWEEN '%s' AND '%s')",
					$rowRetencion['idRetencionCabezera'],
					date("Y-m-d",strtotime($valCadBusq[0])),
					date("Y-m-d",strtotime($valCadBusq[1])));
				$rsRetenciones = mysql_query($sqlRetenciones);
				if (!$rsRetenciones) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowRetenciones = mysql_fetch_array($rsRetenciones);
				
				if($rowDocumento["idFactura"] == $rowRetencion["idFactura"] && mysql_num_rows($rsRetenciones) != 0 && $rowRetenciones["mesPeriodoFiscal"] == date("m",strtotime($valCadBusq[0]))){
					$fechaComprobanteRetencion75 = date("d-m-Y",strtotime($rowRetenciones["fechaComprobante"]));
					$nroComprobanteRetencion75 = $rowRetenciones["numeroComprobante"];
					$sumaRetenciones = $rowRetencion["IvaRetenido"];
					$totalRetenido += $sumaRetenciones;
				} else{
					$fechaComprobanteRetencion75 = "-";
					$nroComprobanteRetencion75 = "-";
				}
				
				//$nroComprobanteRetencion3 = "-";
				
				/* CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO 
				$queryFacturaGastos = sprintf("SELECT * FROM cj_cc_factura_gasto WHERE id_factura = '%s'", $rowDocumento['idFactura']);
				$rsFacturaGasto = mysql_query($queryFacturaGastos);
				if (!$rsFacturaGasto) return $objResponse->alert(mysql_error()." \n\nLine: ".__LINE__);
				$montoGastos = 0;
				while($rowFacturaGasto = mysql_fetch_array($rsFacturaGasto))
					$montoGastos += $rowFacturaGasto['monto']*//*+($rowFacturaGasto['monto']*($rowFacturaGasto['iva']/100))*/;
				
				/* CONSULTA PARA SUMAR LOS IVAS DEL DOCUMENTO 
				$queryFacturaIva = sprintf("SELECT * FROM cp_factura_iva WHERE id_factura = '%s'", $rowDocumento['id_factura']);
				$rsFacturaIva = mysql_query($queryFacturaIva);
				if (!$rsFacturaIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$montoIva = 0;
				$montoBaseImpIvas = 0;
				while($rowFacturaIva = mysql_fetch_array($rsFacturaIva)) {
					$montoIva += $rowFacturaIva['subtotal_iva'];
					if($rowFacturaIva['lujo'] != 1)
						$montoBaseImpIvas += $rowFacturaIva['base_imponible'];
					
					foreach ($arrayIvaNoContribuyente as $indice => $valor) {
						if ($rowFacturaIva['id_iva'] == $arrayIvaNoContribuyente[$indice][0]) {
							$arrayIvaDocNoContribuyente[$arrayIvaNoContribuyente[$indice][2]] += doubleval($rowFacturaIva['subtotal_iva']);
							$arrayIvaDocNoContribuyente[$arrayIvaNoContribuyente[$indice][3]] += doubleval($rowFacturaIva['base_imponible']);
						}
					}
				}*/
				
				$montoIva = $rowDocumento['calculoIvaFactura'] + $rowDocumento['calculoIvaDeLujoFactura'];
				$montoBaseImponible = doubleval($rowDocumento['baseImponible']);
				$ivaDocumento = $rowDocumento['calculoIvaFactura'];
				$ivaLujoDocumento = $rowDocumento['calculoIvaDeLujoFactura'];
				
				$totalCompraConIva = ($rowDocumento['subtotalFactura'] - $rowDocumento['descuentoFactura']) + $montoIva + $montoGastos;
				$montoTotalFactura += $totalCompraConIva; // Observacion Monto Final Factura
				
				$comprasExentas = $rowDocumento["montoExento"];
				
				$comprasExonerado = $rowDocumento["montoExonerado"];
				
				
				if($rowDocumento['idDepartamentoOrigenFactura']==0){
					$totalFacturaRepuestos += $totalCompraConIva;
					$totalBaseRepuestos += $montoBaseImponible;
					$totalIvaRepuestos += $montoIva;
					$totalExentaRepuestos += $comprasExentas;
					$totalExoRepuestos += $comprasExonerado;
					$totalRetenidoRepuesto += $sumaRetenciones;
					
					if ($nroFactura < $primerNumeroFacturaRepuesto || $primerNumeroFacturaRepuesto == '-')
						$primerNumeroFacturaRepuesto = $nroFactura;
					if ($nroFactura > $ultimoNumeroFacturaRepuesto || $ultimoNumeroFacturaRepuesto == '-')
						$ultimoNumeroFacturaRepuesto = $nroFactura;
				}
				if($rowDocumento['idDepartamentoOrigenFactura']==1){
					$totalFacturaServicios += $totalCompraConIva;
					$totalBaseServicios += $montoBaseImponible;
					$totalIvaServicios += $montoIva;
					$totalExentaServicios += $comprasExentas;
					$totalExoServicios += $comprasExonerado;
					$totalRetenidoServicios += $sumaRetenciones;
					
					if ($nroFactura < $primerNumeroFacturaServicio || $primerNumeroFacturaServicio == '-')
						$primerNumeroFacturaServicio = $nroFactura;
					if ($nroFactura > $ultimoNumeroFacturaServicio || $ultimoNumeroFacturaServicio == '-')
						$ultimoNumeroFacturaServicio = $nroFactura;
				}
				if($rowDocumento['idDepartamentoOrigenFactura']==2){
					$totalFacturaAutos += $totalCompraConIva;
					$totalBaseAutos += $montoBaseImponible;
					$totalIvaAutos += $montoIva;
					$totalExentaAutos += $comprasExentas;
					$totalExoAutos += $comprasExonerado;
					$totalRetenidoAutos += $sumaRetenciones;
					
					if ($rowDocumento["calculoIvaDeLujoFactura"] > 0){
						$baseAlicuotaGeneralMasAdicional += $montoBaseImponible;
						$ivaAlicuotaGeneralMasAdicional += $montoIva;
						$exentoAlicuotaGeneralMasAdicional += $comprasExentas;
						$exoneradoAlicuotaGeneralMasAdicional += $comprasExonerado;
						$totalAlicuotaGeneralMasAdicional += $montoBaseImponible + $montoIva + $comprasExentas + $comprasExonerado;
						$retencionAlicuotaGeneralMasAdicional += $sumaRetenciones;
					
						if ($nroFactura < $primerNumeroFacturaAlicuotaGeneralMasAdicional || $primerNumeroFacturaAlicuotaGeneralMasAdicional == '-')
							$primerNumeroFacturaAlicuotaGeneralMasAdicional = $nroFactura;
						if ($nroFactura > $ultimoNumeroFacturaAlicuotaGeneralMasAdicional || $ultimoNumeroFacturaAlicuotaGeneralMasAdicional == '-')
							$ultimoNumeroFacturaAlicuotaGeneralMasAdicional = $nroFactura;
					}
					else{
						$baseAlicuotaGeneral += $montoBaseImponible;
						$ivaAlicuotaGeneral += $montoIva;
						$exentoAlicuotaGeneral += $comprasExentas;
						$exoneradoAlicuotaGeneral += $comprasExonerado;
						$totalAlicuotaGeneral += $montoBaseImponible + $montoIva + $comprasExentas + $comprasExonerado;
						$retencionAlicuotaGeneral += $sumaRetenciones;
					
						if ($nroFactura < $primerNumeroFacturaAlicuotaGeneral || $primerNumeroFacturaAlicuotaGeneral == '-')
							$primerNumeroFacturaAlicuotaGeneral = $nroFactura;
						if ($nroFactura > $ultimoNumeroFacturaAlicuotaGeneral || $ultimoNumeroFacturaAlicuotaGeneral == '-')
							$ultimoNumeroFacturaAlicuotaGeneral = $nroFactura;
					}
					
					//$objResponse -> alert("total autos".$montoBaseImpIvas);
					//$objResponse -> alert("totalBaseAutos".$totalBaseAutos);
					//$objResponse -> alert("montoIva".$montoIva);
					//$objResponse -> alert("comprasExentas".$comprasExentas);
				}
				if($rowDocumento['idDepartamentoOrigenFactura']==3){
					$totalFacturaAdministrativo += $totalCompraConIva;
					$totalBaseAdministrativo += $montoBaseImponible;
					$totalIvaAdministrativo += $montoIva;
					$totalExentaAdministrativo += $comprasExentas;
					$totalExoAdministrativo += $comprasExonerado;
					$totalRetenidoAdministrativo += $sumaRetenciones;
					
					if ($nroFactura < $primerNumeroFacturaAdministracion || $primerNumeroFacturaAdministracion == '-')
						$primerNumeroFacturaAdministracion = $nroFactura;
					if ($nroFactura > $ultimoNumeroFacturaAdministracion || $ultimoNumeroFacturaAdministracion == '-')
						$ultimoNumeroFacturaAdministracion = $nroFactura;
				}
							
			$totalTotalCompraConIva += $totalCompraConIva;
			$totalComprasExentas += $comprasExentas;
			$totalComprasExonerado += $comprasExonerado;
				
				$totalGlobalBaseImp = $totalBaseAdministrativo + $totalBaseAutos + $totalBaseServicios + $totalBaseRepuestos;
				$totalGlobalIvas = $totalIvaAdministrativo + $totalIvaAutos + $totalIvaServicios + $totalIvaRepuestos;
				$totalGlobalExentas = $totalExentaAdministrativo + $totalExentaAutos + $totalExentaServicios + $totalExentaRepuestos;
				$totalGlobalExonerado = $totalExoAdministrativo + $totalExoAutos + $totalExoServicios + $totalExoRepuestos;
				$totalExentasGlobal = $totalExentaAutos+$totalExentaRepuestos+$totalExentaAdministrativo+$totalExentaServicios;
				$totalExoneradoGlobal = $totalExoAutos+$totalExoRepuestos+$totalExoAdministrativo+$totalExoServicios;
				$totalComprasExentasExo = $totalExoneradoGlobal+$totalExentasGlobal; 
				$totalGlobalDocumentos = $totalGlobalBaseImp + $totalGlobalIvas + $totalGlobalExentas + $totalExoneradoGlobal;
				$totalGlobalRetenido = $totalRetenidoRepuesto + $totalRetenidoServicios +$totalRetenidoAutos + $totalRetenidoAdministrativo;			
			
			} else if($rowEstadoCuenta["tipoDocumento"] == "NC" && mysql_num_rows($rsDocumento) == 1) { 							
			/*--------NOTA DE CREDITO---------*/
				$fechaDocumento = date("d-m-Y",strtotime($rowDocumento["fechaNotaCredito"]));
				$tipoDocumento = "NC";
				$nroFactura = "-";
				$nroControl = $rowDocumento['numeroControl'];
				$nroFiscal = $rowDocumento['consecutivo_fiscal'];
				$serialImpresora = $rowDocumento['serial_impresora'];				
				$nroNotaCredito = $rowDocumento["numeracion_nota_credito"];
				
				if($rowDocumento["tipoDocumento"] == "ND") {
					$sqlnota = "SELECT * FROM cj_cc_notadecargo WHERE idNotaCargo ='".$rowDocumento['idDocumento']."'";
					$consulnota = mysql_query($sqlnota);
					if (!$consulnota) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowCargo = mysql_fetch_array($consulnota);
					//echo $rowCargo["numeroControlNotaCargo"];
					$nroFacturaAfectada = "-";
				} else if($rowDocumento["tipoDocumento"] == "FA") {
					$queryFact = "SELECT * FROM cj_cc_encabezadofactura WHERE idFactura ='".$rowDocumento['idDocumento']."'";
					$rsFact = mysql_query($queryFact);
					if (!$rsFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowFactura = mysql_fetch_array($rsFact);
					
					$nroFacturaAfectada = $rowFactura["numeroFactura"];
				} else
					$nroFacturaAfectada = "-";
					
					
				$queryNotaCredito = sprintf("SELECT * FROM cj_cc_notacredito WHERE idNotaCredito = '%s'",$rowDocumento["idNotaCredito"]);
				$rsNotaCredito = mysql_query($queryNotaCredito);
				if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
				
				/* CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
				$queryNotaCreditoGastos = sprintf("SELECT * FROM cp_notacredito_gastos WHERE id_notacredito = '%s'", $rowDocumento["id_notacredito"]);
				$rsNotaCreditoGasto = mysql_query($queryNotaCreditoGastos);
				if (!$rsNotaCreditoGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$montoGastos = 0;
				while($rowNotaCreditoGasto = mysql_fetch_array($rsNotaCreditoGasto))
					$montoGastos += $rowNotaCreditoGasto['monto_gasto_notacredito'] *//**($rowNotaCreditoGasto['iva_notacredito']/100))+$rowNotaCreditoGasto['monto_gasto_notacredito'];*/
				
				/* CONSULTA PARA SUMAR LOS IVAS DEL DOCUMENTO
				$queryNotaCreditoIva = sprintf("SELECT * FROM cp_notacredito_iva WHERE id_notacredito = '%s'", $rowDocumento["id_notacredito"]);
				$rsNotaCreditoIva = mysql_query($queryNotaCreditoIva);
				if (!$rsNotaCreditoIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$montoIva = 0;
				$montoBaseImpIvas = 0;
				while($rowNotaCreditoIva = mysql_fetch_array($rsNotaCreditoIva)){
					$montoIva += $rowNotaCreditoIva['subtotal_iva_notacredito'];
					$montoBaseImpIvas += $rowNotaCreditoIva['baseimponible_notacredito'];
					foreach ($arrayIvaNac as $indice => $valor) {
						if ($rowNotaCreditoIva['id_iva_notacredito'] == $arrayIvaNac[$indice][0]) {
							$arrayIvaDocNac[$arrayIvaNac[$indice][2]] += doubleval($rowNotaCreditoIva['subtotal_iva_notacredito']);
							$arrayIvaDocNac[$arrayIvaNac[$indice][3]] += doubleval($rowNotaCreditoIva['baseimponible_notacredito']);
						}
					}
				} */
				
				$montoIva = -($rowNotaCredito['ivaNotaCredito'] + $rowNotaCredito['ivaLujoNotaCredito']);
				$montoBaseImponible = -(doubleval($rowNotaCredito['baseimponibleNotaCredito']));
				$ivaDocumento = -($rowNotaCredito['ivaNotaCredito']);
				$ivaLujoDocumento = -($rowNotaCredito['ivaLujoNotaCredito']);
				
				$totalCompraConIva = $montoGastos + $montoIva - $rowNotaCredito['subtotalNotaCredito'] + $rowNotaCredito['subtotal_descuento'];//;
				
				$nroComprobanteRetencion75 = "-";
				$nroComprobanteRetencion3 = "-";
				
				$comprasExentas = -$rowDocumento["montoExentoCredito"];
				$comprasExonerado = -$rowDocumento["montoExoneradoCredito"];
				
				if($rowDocumento['idDepartamentoNotaCredito'] == 0){
					$totalFacturaRepuestos += $montoIva + $montoBaseImponible + $comprasExentas + $comprasExonerado;
					$totalBaseRepuestos += $montoBaseImponible;
					$totalIvaRepuestos += $montoIva;
					$totalExentaRepuestos += $comprasExentas;
					$totalExoRepuestos += $comprasExonerado;
				}
				if($rowDocumento['idDepartamentoNotaCredito'] == 1){
					$totalFacturaServicios += $montoIva + $montoBaseImponible + $comprasExentas + $comprasExonerado;
					$totalBaseServicios += $montoBaseImponible;
					$totalIvaServicios += $montoIva;
					$totalExentaServicios += $comprasExentas;
					$totalExoServicios += $comprasExonerado;
				}
				if($rowDocumento['idDepartamentoNotaCredito'] == 2){
					$totalFacturaAutos += $totalCompraConIva;
					$totalBaseAutos += $montoBaseImponible;
					$totalIvaAutos += $montoIva;
					$totalExentaAutos += $comprasExentas;
					$totalExoAutos += $comprasExonerado;
					
					if ($rowDocumento["ivaLujoNotaCredito"] > 0){
						$baseAlicuotaGeneralMasAdicional += $montoBaseImponible;
						$ivaAlicuotaGeneralMasAdicional += $montoIva;
						$exentoAlicuotaGeneralMasAdicional += $comprasExentas;
						$exoneradoAlicuotaGeneralMasAdicional += $comprasExonerado;
						$totalAlicuotaGeneralMasAdicional += $montoBaseImponible + $montoIva + $comprasExentas + $comprasExonerado;
						$retencionAlicuotaGeneralMasAdicional += $sumaRetenciones;
					}
					else{
						$baseAlicuotaGeneral += $montoBaseImponible;
						$ivaAlicuotaGeneral += $montoIva;
						$exentoAlicuotaGeneral += $comprasExentas;
						$exoneradoAlicuotaGeneral += $comprasExonerado;
						$totalAlicuotaGeneral += $montoBaseImponible + $montoIva + $comprasExentas + $comprasExonerado;
						$retencionAlicuotaGeneral += $sumaRetenciones;
					}
				}
				if($rowDocumento['idDepartamentoNotaCredito'] == 3){
					$totalFacturaAdministrativo += $montoIva + $montoBaseImponible + $comprasExentas + $comprasExonerado;
					$totalBaseAdministrativo += $montoBaseImponible;
					$totalIvaAdministrativo += $montoIva;
					$totalExentaAdministrativo += $comprasExentas;
					$totalExoAdministrativo += $comprasExonerado;
				}
				
				/*$montoBaseImponible = $montoBaseImponible * -1;
				$montoIva = $montoIva * -1;*/
				
				$totalTotalCompraConIva += $totalCompraConIva;
				$totalComprasExentas += $comprasExentas;
				$totalComprasExonerado += $comprasExonerado;
				
				$totalGlobalBaseImp = $totalBaseAdministrativo + $totalBaseAutos + $totalBaseServicios + $totalBaseRepuestos;
				$totalGlobalIvas = $totalIvaAdministrativo + $totalIvaAutos + $totalIvaServicios + $totalIvaRepuestos;
				$totalGlobalExentas = $totalExentaAdministrativo + $totalExentaAutos + $totalExentaServicios + $totalExentaRepuestos;
				$totalGlobalExonerado = $totalExoAdministrativo + $totalExoAutos + $totalExoServicios + $totalExoRepuestos;
				$totalExentasGlobal = $totalExentaAutos+$totalExentaRepuestos+$totalExentaAdministrativo+$totalExentaServicios;
				$totalExoneradoGlobal = $totalExoAutos+$totalExoRepuestos+$totalExoAdministrativo+$totalExoServicios;
				$totalComprasExentasExo = $totalExoneradoGlobal+$totalExentasGlobal; 
				$totalGlobalDocumentos = $totalGlobalBaseImp + $totalGlobalIvas + $totalGlobalExentas + $totalExoneradoGlobal;
			
				
			} else if($rowEstadoCuenta["tipoDocumento"] == "ND" && mysql_num_rows($rsDocumento) == 1) { 							
			/*-------NOTA DE DÉBITO---------*/
				$fechaDocumento = date("d-m-Y",strtotime($rowDocumento["fechaRegistroNotaCargo"]));
				$tipoDocumento = "ND";
				$nroFactura = $rowDocumento['numeroNotaCargo'];
				$nroControl = $rowDocumento['numeroControlNotaCargo'];
				$nroFiscal = "-";
				$serialImpresora = "-";
				$nroNotaCredito = "-";
				$nroFacturaAfectada = "-";
				$fechaComprobanteRetencion75 = "-";
				$nroComprobanteRetencion75 = "-";
				$nroComprobanteRetencion3 = "-";
				
				/* CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
				$queryNotaCargoGastos = sprintf("SELECT * FROM cp_notacargo_gastos WHERE idNotaCargo = '%s'", $rowDocumento['idNotaCargo']);
				$rsNotaCargoGasto = mysql_query($queryNotaCargoGastos);
				if (!$rsNotaCargoGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$montoGastos = 0;
				$montoBaseImpIvas = 0;
				while($rowNotaCargoGasto = mysql_fetch_array($rsNotaCargoGasto)) */
					/*$montoGastos += $rowNotaCargoGasto['monto'] * ($rowNotaCargoGasto['iva'] / 100)) + $rowNotaCargoGasto['monto'];*/
				
				/* CONSULTA PARA SUMAR LOS IVAS DEL DOCUMENTO
				$queryNotaCargoIva = sprintf("SELECT * FROM cp_notacargo_iva WHERE id_notacargo = '%s'", $rowDocumento['id_notacargo']);
				$rsNotaCargoIva = mysql_query($queryNotaCargoIva);
				if (!$rsNotaCargoIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$montoIva = 0;
				while($rowNotaCargoIva = mysql_fetch_array($rsNotaCargoIva)) {
					$montoIva += $rowNotaCargoIva['subtotal_iva'];
					$montoBaseImpIvas += $rowNotaCargoIva['baseimponible'];
					foreach ($arrayIvaNac as $indice => $valor) {
						if ($rowNotaCargoIva['id_iva'] == $arrayIvaNac[$indice][0]) {
							$arrayIvaDocNac[$arrayIvaNac[$indice][2]] = doubleval($rowNotaCargoIva['subtotal_iva']);
							$arrayIvaDocNac[$arrayIvaNac[$indice][3]] += doubleval($rowNotaCargoIva['baseimponible']);
						}
					}
				}				 */
				
				$montoIva = $rowDocumento['calculoIvaNotaCargo'] + $rowDocumento['ivaLujoNotaCargo'];
				$montoBaseImponible = doubleval($ivaLujoNotaCargo['baseImponibleNotaCargo']);
				$ivaDocumento = $rowDocumento['calculoIvaNotaCargo'];
				$ivaLujoDocumento = $rowDocumento['ivaLujoNotaCargo'];
				
				$totalCompraConIva = ($montoGastos + $montoIva + $rowDocumento['subtotalNotaCargo'])- $rowDocumento['descuentoNotaCargo'];

				$comprasExentas = $rowDocumento["montoExentoNotaCargo"];
				$comprasExonerado = $rowDocumento["montoExoneradoNotaCargo"];
								
				if($rowDocumento['idDepartamentoOrigenNotaCargo']==0){
					$totalFacturaRepuestos += $totalCompraConIva;
					$totalBaseRepuestos += $montoBaseImpIvas;
					$totalIvaRepuestos += $montoIva;
					$totalExentaRepuestos += $comprasExentas;
					$totalExoRepuestos += $comprasExonerado;
				}
				if($rowDocumento['idDepartamentoOrigenNotaCargo']==1){
					$totalFacturaServicios += $totalCompraConIva;
					$totalBaseServicios += $montoBaseImpIvas;
					$totalIvaServicios += $montoIva;
					$totalExentaServicios += $comprasExentas;
					$totalExoServicios += $comprasExonerado;
				}
				if($rowDocumento['idDepartamentoOrigenNotaCargo']==2){
					$totalFacturaAutos += $totalCompraConIva;
					$totalBaseAutos += $montoBaseImpIvas;
					$totalIvaAutos += $montoIva;
					$totalExentaAutos += $comprasExentas;
					$totalExoAutos += $comprasExonerado;
					
					if ($rowDocumento["ivaLujoNotaCargo"] > 0){
						$baseAlicuotaGeneralMasAdicional += $montoBaseImponible;
						$ivaAlicuotaGeneralMasAdicional += $montoIva;
						$exentoAlicuotaGeneralMasAdicional += $comprasExentas;
						$exoneradoAlicuotaGeneralMasAdicional += $comprasExonerado;
						$totalAlicuotaGeneralMasAdicional += $montoBaseImponible + $montoIva + $comprasExentas + $comprasExonerado;
						$retencionAlicuotaGeneralMasAdicional += $sumaRetenciones;
					}
					else{
						$baseAlicuotaGeneral += $montoBaseImponible;
						$ivaAlicuotaGeneral += $montoIva;
						$exentoAlicuotaGeneral += $comprasExentas;
						$exoneradoAlicuotaGeneral += $comprasExonerado;
						$totalAlicuotaGeneral += $montoBaseImponible + $montoIva + $comprasExentas + $comprasExonerado;
						$retencionAlicuotaGeneral += $sumaRetenciones;
					}
				}
				if($rowDocumento['idDepartamentoOrigenNotaCargo']==3){
					$totalFacturaAdministrativo += $totalCompraConIva;
					$totalBaseAdministrativo += $montoBaseImpIvas;
					$totalIvaAdministrativo += $montoIva;
					$totalExentaAdministrativo += $comprasExentas;
					$totalExoAdministrativo += $comprasExonerado;
				}
							
			$totalTotalCompraConIva += $totalCompraConIva;
			$totalComprasExentas += $comprasExentas;
			$totalComprasExonerado += $comprasExonerado;
				
				$totalGlobalBaseImp = $totalBaseAdministrativo + $totalBaseAutos + $totalBaseServicios + $totalBaseRepuestos;
				$totalGlobalIvas = $totalIvaAdministrativo + $totalIvaAutos + $totalIvaServicios + $totalIvaRepuestos;
				$totalGlobalExentas = $totalExentaAdministrativo + $totalExentaAutos + $totalExentaServicios + $totalExentaRepuestos;
				$totalGlobalExonerado = $totalExoAdministrativo + $totalExoAutos + $totalExoServicios + $totalExoRepuestos;
				$totalExentasGlobal = $totalExentaAutos+$totalExentaRepuestos+$totalExentaAdministrativo+$totalExentaServicios;
				$totalExoneradoGlobal = $totalExoAutos+$totalExoRepuestos+$totalExoAdministrativo+$totalExoServicios;
				$totalComprasExentasExo = $totalExoneradoGlobal+$totalExentasGlobal; 
				$totalGlobalDocumentos = $totalGlobalBaseImp + $totalGlobalIvas + $totalGlobalExentas + $totalExoneradoGlobal;
			}
			
			/*$retencionessql = sprintf("SELECT * FROM cp_retencioncabezera
			WHERE DATE(fechaComprobante) BETWEEN '%s' and '%s'",
				$valCadBusq[0],
				$valCadBusq[1]);
			$retencionesconsulta = mysql_query($retencionessql);
			if (!$retencionesconsulta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$fretenciones = mysql_fetch_array($retencionesconsulta);
			
			$detallesretenciones = sprintf("SELECT * FROM cp_retenciondetalle
			WHERE idRetencionCabezera = '%s'",
				$fretenciones['idRetencionCabezera']);
			$detallesretencionesconsulta = mysql_query($detallesretenciones);
			if (!$detallesretencionesconsulta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$fdetalles = mysql_fetch_array($detallesretencionesconsulta);
			if ($fdetalles['fechaFactura'] < $valCadBusq[0]) {
				//$sumaRetenciones += $fdetalles['IvaRetenido'];
				$sumaivas += $fdetalles['impuestoIva'];
				$sumabase += $fdetalles['baseImponible'];
			} */

			
			if($rowDocumento['idFactura'] != "" || $rowDocumento['idNotaCredito'] != "" || $rowDocumento['idNotaCargo'] != "") {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$sqlCliente = "SELECT * FROM cj_cc_cliente WHERE id = '".$rowDocumento['idCliente']."'";
				$rsCliente = mysql_query($sqlCliente);
				if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowCliente = mysql_fetch_array($rsCliente);
				
		
				$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
					$htmlTb .= "<td align=\"right\">".$fechaDocumento."</td>";
					$htmlTb .= "<td align=\"center\">".$tipoDocumento."</td>";
					$htmlTb .= "<td align=\"right\">".$nroFactura."</td>";
					$htmlTb .= "<td align=\"right\">".$nroControl."</td>";
					if ($valor == 2 || $valor == 3) {//1 = VENEZUELA ; 2 = PANAMA ; 3 = PUERTO RICO
						$htmlTb .= "<td align=\"right\">".$nroFiscal."<br>".$serialImpresora."</td>";
					}
					$htmlTb .= "<td align=\"center\">".$rowCliente['id']."</td>";
					$htmlTb .= "<td align=\"right\">".$rowCliente['lci']."-".$rowCliente['ci']."</td>";
					$htmlTb .= "<td align=\"left\">".utf8_encode($rowCliente['nombre']." ".$rowCliente['apellido'])."</td>";
					$htmlTb .= "<td align=\"right\">".$nroNotaCredito."</td>";
					$htmlTb .= "<td align=\"right\">".$nroFacturaAfectada."</td>";
					$htmlTb .= "<td align=\"right\">".$nroComprobanteRetencion75."</td>";
					$htmlTb .= "<td align=\"right\">".$fechaComprobanteRetencion75."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalCompraConIva,2,",","")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($comprasExentas,2,",","")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($comprasExonerado,2,",","")."</td>";
					
					if ($rowCliente['contribuyente'] == 'No') {
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format(doubleval($montoBaseImponible),2,",",""),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format(doubleval($ivaDocumento),2,",",""),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format(doubleval($ivaLujoDocumento),2,",",""),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"center\">-</td>";
						$htmlTb .= "<td align=\"center\">-</td>";
						$htmlTb .= "<td align=\"center\">-</td>";
						
						$totalBaseImponibleDiaNoContribuyente += $montoBaseImponible;
						$totalIvaDiaNoContribuyente += $ivaDocumento;
						$totalIvaLujoDiaNoContribuyente += $ivaLujoDocumento;
					}
				
					if ($rowCliente['contribuyente'] == 'Si') {
						$htmlTb .= "<td align=\"center\">-</td>";
						$htmlTb .= "<td align=\"center\">-</td>";
						$htmlTb .= "<td align=\"center\">-</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format(doubleval($montoBaseImponible),2,",",""),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format(doubleval($ivaDocumento),2,",",""),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format(doubleval($ivaLujoDocumento),2,",",""),"cero_por_vacio")."</td>";
						
						$totalBaseImponibleDiaContribuyente += $montoBaseImponible;
						$totalIvaDiaContribuyente += $ivaDocumento;
						$totalIvaLujoDiaContribuyente += $ivaLujoDocumento;
					}
					$htmlTb .= "<td align=\"right\">".$sumaRetenciones."</td>";
				$htmlTb .= "</tr>";
			}
		}
		//AQUI
		$htmlTb.= "<tr class=\"trResaltar6\">";
			$htmlTb .= "<td align=\"center\" colspan='".$colspan."'>Total Dia: ".date("d-m-Y",strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalTotalCompraConIva,2,",","")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalComprasExentas,2,",","")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalComprasExonerado,2,",","")."</td>";
		
			$htmlTb .= "<td align=\"right\">".number_format($totalBaseImponibleDiaNoContribuyente,2,",","")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalIvaDiaNoContribuyente,2,",","")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalIvaLujoDiaNoContribuyente,2,",","")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalBaseImponibleDiaContribuyente,2,",","")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalIvaDiaContribuyente,2,",","")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalIvaLujoDiaContribuyente,2,",","")."</td>";
			
			$TotalesBaseImponibleDocNoContribuyente += $totalBaseImponibleDiaNoContribuyente;
			$TotalesIvaDocNoContribuyente += $totalIvaDiaNoContribuyente;
			$TotalesIvaLujoDocNoContribuyente += $totalIvaLujoDiaNoContribuyente;
			$TotalesBaseImponibleDocContribuyente += $totalBaseImponibleDiaContribuyente;
			$TotalesIvaDocContribuyente += $totalIvaDiaContribuyente;
			$TotalesIvaLujoDocContribuyente += $totalIvaLujoDiaContribuyente;
			
			$htmlTb .= "<td align=\"right\">".number_format($totalRetenido,2,",","")."</td>";
		$htmlTb .= "</tr>";
		
		$totalesTotalCompraConIva += $totalTotalCompraConIva;
		$totalesComprasExentas += $totalComprasExentas;
		$totalesComprasExonerado += $totalComprasExonerado;
		$totalesComprasRetenido += $totalRetenido;
	}//AQUI
	$htmlTb.= "<tr class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"center\" colspan='".$colspan."'><b>Total General del:</b> ".date("d-m-Y",strtotime($valCadBusq[0]))." <b>hasta:</b> ".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlTb .= "<td align=\"right\">".number_format($totalesTotalCompraConIva,2,",","")."</td>";
		$htmlTb .= "<td align=\"right\">".number_format($totalesComprasExentas,2,",","")."</td>";
		$htmlTb .= "<td align=\"right\">".number_format($totalesComprasExonerado,2,",","")."</td>";
	
		$htmlTb .= "<td align=\"right\">".number_format($TotalesBaseImponibleDocNoContribuyente,2,",","")."</td>";
		$htmlTb .= "<td align=\"right\">".number_format($TotalesIvaDocNoContribuyente,2,",","")."</td>";
		$htmlTb .= "<td align=\"right\">".number_format($TotalesIvaLujoDocNoContribuyente,2,",","")."</td>";
		$htmlTb .= "<td align=\"right\">".number_format($TotalesBaseImponibleDocContribuyente,2,",","")."</td>";
		$htmlTb .= "<td align=\"right\">".number_format($TotalesIvaDocContribuyente,2,",","")."</td>";
		$htmlTb .= "<td align=\"right\">".number_format($TotalesIvaLujoDocContribuyente,2,",","")."</td>";
	
		$htmlTb .= "<td align=\"right\">".number_format($totalesComprasRetenido,2,",","")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"".(18+$contColum+4)."\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoLibroVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoLibroVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoLibroVenta(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoLibroVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoLibroVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"".(18+$contColum+4)."\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("tdLibroVenta","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTableFin);
	
	$globalTotalIva22 = $arrayTotalesIvaDocNoContribuyente[2]+$arrayTotalesIvaDocNoContribuyente[3];
	
	$totalGlobalRetenido += $totalRetencionesOtrosPeriodos;
	
	$htmlCuadro .="<table width=\"70%\" align=\"center\" border=\"1\" class=\"tabla\" cellpadding=\"2\" style=\"font-size:9px\">";
	$htmlCuadro .="<tr class=\"tituloColumna\" height=\"24\">";
		$htmlCuadro .="<td width=\"30%\"></td>";
		$htmlCuadro .="<td align=\"center\" width=\"5%\">Desde</td>";
		$htmlCuadro .="<td align=\"center\" width=\"5%\">Hasta</td>";
		$htmlCuadro .="<td align=\"center\" width=\"10%\">Base</td>";
		$htmlCuadro .="<td align=\"center\" width=\"10%\">Iva</td>";
		$htmlCuadro .="<td align=\"center\" width=\"10%\">Exentas</td>";
		$htmlCuadro .="<td align=\"center\" width=\"10%\">Exoneradas</td>";
		$htmlCuadro .="<td align=\"center\" width=\"10%\">Total</td>";
		$htmlCuadro .="<td align=\"center\" width=\"10%\">Iva Retenido</td>";
	$htmlCuadro .="</tr>";
	$htmlCuadro .="<tr height=\"24\">";
		$htmlCuadro .="<td>Retenciones Vencidas Otros Meses</td>";
		$htmlCuadro .="<td align=\"center\">-</td>";//".date("d-m-Y",strtotime($valCadBusq[0]))."
		$htmlCuadro .="<td align=\"center\">-</td>";//".date("d-m-Y",strtotime($valCadBusq[1]))."
		$htmlCuadro .="<td align=\"center\"></td>";
		$htmlCuadro .="<td align=\"center\"></td>";
		$htmlCuadro .="<td align=\"center\"></td>";
		$htmlCuadro .="<td align=\"center\"></td>";
		$htmlCuadro .="<td align=\"center\"></td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalRetencionesOtrosPeriodos,2,",","")."</td>";
	$htmlCuadro .="</tr>";
	$htmlCuadro .="<tr height=\"24\">";
		$htmlCuadro .="<td>Libro Venta de Repuestos</td>";
		$htmlCuadro .="<td align=\"center\">".$primerNumeroFacturaRepuesto."</td>";//date("d-m-Y",strtotime($valCadBusq[0]))
		$htmlCuadro .="<td align=\"center\">".$ultimoNumeroFacturaRepuesto."</td>";//date("d-m-Y",strtotime($valCadBusq[1]))
		$htmlCuadro .="<td align=\"center\">".number_format($totalBaseRepuestos,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalIvaRepuestos,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalExentaRepuestos,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalExoRepuestos,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalFacturaRepuestos,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalRetenidoRepuesto,2,",","")."</td>";
	$htmlCuadro .="</tr>";
	$htmlCuadro .="<tr height=\"24\">";
		$htmlCuadro .="<td>Libro Venta de Servicios</td>";
		$htmlCuadro .="<td align=\"center\">".$primerNumeroFacturaServicio."</td>";//date("d-m-Y",strtotime($valCadBusq[0]))
		$htmlCuadro .="<td align=\"center\">".$ultimoNumeroFacturaServicio."</td>";//date("d-m-Y",strtotime($valCadBusq[1]))
		$htmlCuadro .="<td align=\"center\">".number_format($totalBaseServicios,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalIvaServicios,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalExentaServicios,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalExoServicios,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalFacturaServicios,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalRetenidoServicios,2,",","")."</td>";
	$htmlCuadro .="</tr>";
	$htmlCuadro .="<tr bgcolor='#CCCCCC' height=\"24\">";
		$htmlCuadro .="<td>Libro De Ventas de Vehiculos Alicuota General.</td>";
		$htmlCuadro .="<td align=\"center\">".$primerNumeroFacturaAlicuotaGeneral."</td>";//date("d-m-Y",strtotime($valCadBusq[0]))
		$htmlCuadro .="<td align=\"center\">".$ultimoNumeroFacturaAlicuotaGeneral."</td>";//date("d-m-Y",strtotime($valCadBusq[1]))
		$htmlCuadro .="<td align=\"center\">".number_format($baseAlicuotaGeneral,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($ivaAlicuotaGeneral,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($exentoAlicuotaGeneral,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($exoneradoAlicuotaGeneral,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalAlicuotaGeneral,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($retencionAlicuotaGeneral,2,",","")."</td>";
	$htmlCuadro .="</tr>";
	$htmlCuadro .="<tr height=\"24\">";
		$htmlCuadro .="<td>Ventas Internas Gravadas por Alicuota General mas Alicuota Adicional</td>";
		$htmlCuadro .="<td align=\"center\">".$primerNumeroFacturaAlicuotaGeneralMasAdicional."</td>";//date("d-m-Y",strtotime($valCadBusq[0]))
		$htmlCuadro .="<td align=\"center\">".$ultimoNumeroFacturaAlicuotaGeneralMasAdicional."</td>";//date("d-m-Y",strtotime($valCadBusq[1]))
		$htmlCuadro .="<td align=\"center\">".number_format($baseAlicuotaGeneralMasAdicional,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($ivaAlicuotaGeneralMasAdicional,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($exentoAlicuotaGeneralMasAdicional,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($exoneradoAlicuotaGeneralMasAdicional,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalAlicuotaGeneralMasAdicional,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($retencionAlicuotaGeneralMasAdicional,2,",","")."</td>";
	$htmlCuadro .="</tr>";
	//$htmlCuadro .="<tr>";
		//$htmlCuadro .="<td>Libro Venta de Vehiculos</td>";
		//$htmlCuadro .="<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .="<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		//$htmlCuadro .="<td align=\"center\">".number_format($totalBaseAutos,2,",","")."</td>";
		//$htmlCuadro .="<td align=\"center\">".number_format($totalIvaAutos,2,",","")."</td>";
		//$htmlCuadro .="<td align=\"center\">".number_format($totalExentaAutos,2,",","")."</td>";
		//$htmlCuadro .="<td align=\"center\">".number_format($totalExoAutos,2,",","")."</td>";
		//$htmlCuadro .="<td align=\"center\">".number_format($totalFacturaAutos,2,",","")."</td>";
		//$htmlCuadro .="<td align=\"center\">".number_format($totalRetenidoAutos,2,",","")."</td";
	//$htmlCuadro .="</tr>";
	$htmlCuadro .="<tr height=\"24\">";
		$htmlCuadro .="<td>Libro Venta de Administracion</td>";
		$htmlCuadro .="<td align=\"center\">".$primerNumeroFacturaAdministracion."</td>";//date("d-m-Y",strtotime($valCadBusq[0]))
		$htmlCuadro .="<td align=\"center\">".$ultimoNumeroFacturaAdministracion."</td>";//date("d-m-Y",strtotime($valCadBusq[1]))
		$htmlCuadro .="<td align=\"center\">".number_format($totalBaseAdministrativo,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalIvaAdministrativo,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalExentaAdministrativo,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalExoAdministrativo,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalFacturaAdministrativo,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalRetenidoAdministrativo,2,",","")."</td>";
	$htmlCuadro .="</tr>";
	$htmlCuadro .="<tr height=\"24\">";
		$htmlCuadro .="<td>Ventas No Grabadas y/o Sin Derecho a Credito Fiscal</td>";
		$htmlCuadro .="<td align=\"center\">-</td>";//".date("d-m-Y",strtotime($valCadBusq[0]))."
		$htmlCuadro .="<td align=\"center\">-</td>";//".date("d-m-Y",strtotime($valCadBusq[1]))."
		$htmlCuadro .="<td align=\"center\">-</td>";
		$htmlCuadro .="<td align=\"center\">-</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalExentasGlobal,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalExoneradoGlobal,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalComprasExentasExo,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">-</td>";
	$htmlCuadro .="</tr>";
	$htmlCuadro .="<tr height=\"24\">";
		$htmlCuadro .="<td>Total de Ventas y Debitos Fiscales</td>";
		$htmlCuadro .="<td align=\"center\">-</td>";//".date("d-m-Y",strtotime($valCadBusq[0]))."
		$htmlCuadro .="<td align=\"center\">-</td>";//".date("d-m-Y",strtotime($valCadBusq[1]))."
		$htmlCuadro .="<td align=\"center\">".number_format($totalGlobalBaseImp,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalGlobalIvas,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalGlobalExentas,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalGlobalExonerado,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalesTotalCompraConIva,2,",","")."</td>";
		$htmlCuadro .="<td align=\"center\">".number_format($totalGlobalRetenido,2,",","")."</td>";
	$htmlCuadro .="</tr>";
	$htmlCuadro .="</table>";
	
	$objResponse->assign("tdCuadroLibroVenta","innerHTML",$htmlCuadro);
	$objResponse->script("xajax_encabezadoEmpresa(".$idEmpresa.")");
	
	return $objResponse;
}

function volver(){
	$objResponse = new xajaxResponse();
	
	$objResponse -> script(sprintf("
			window.open('cc_libro_venta.php','_self');"));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"listadoLibroVenta");
$xajax->register(XAJAX_FUNCTION,"volver");
?>