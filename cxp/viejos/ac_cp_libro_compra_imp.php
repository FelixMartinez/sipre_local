<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function listaLibroCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	mysql_query("START TRANSACTION;");
	
	$lstFormatoNumero = $valCadBusq[3];
	
	if ($valCadBusq[2] != -1 && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq2 .= $cond.sprintf("id_departamento_notacredito IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq3 .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
		
	$query = sprintf("SELECT
		fecha_origen,
		aplica_libros,
		fecha_factura_proveedor,
		id_factura AS idDocumento,
		'FA' AS tipoDocumento
	FROM cp_factura
	WHERE DATE(fecha_origen) BETWEEN %s AND %s
		AND aplica_libros = '1'
		%s
		
	UNION
	
	SELECT
		fecha_registro_notacredito,
		aplica_libros_notacredito,
		fecha_notacredito,
		id_notacredito AS idDocumento,
		'NC' AS tipoDocumento
	FROM cp_notacredito
	WHERE DATE(fecha_registro_notacredito) BETWEEN %s AND %s
		AND aplica_libros_notacredito = '1'
		%s
		
	UNION
	
	SELECT
		fecha_origen_notacargo,
		aplica_libros_notacargo,
		fecha_notacargo,
		id_notacargo AS idDocumento,
		'ND' AS tipoDocumento
	FROM cp_notadecargo
	WHERE DATE(fecha_origen_notacargo) BETWEEN %s AND %s
		AND aplica_libros_notacargo = '1'
		%s
	ORDER BY 3 ASC",
		valTpDato(date("Y-m-d",strtotime($valCadBusq[0])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
		$sqlBusq,
		valTpDato(date("Y-m-d",strtotime($valCadBusq[0])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
		$sqlBusq2,
		valTpDato(date("Y-m-d",strtotime($valCadBusq[0])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
		$sqlBusq3);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$queryIvaNac = sprintf("SELECT * FROM pg_iva iva
	WHERE estado = 1
		AND iva.tipo IN (1,3)
	ORDER BY iva.activo DESC, iva.tipo");
	$rsIvaNac = mysql_query($queryIvaNac);
	if (!$rsIvaNac) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsIvaNac = mysql_num_rows($rsIvaNac);
	
	$queryIvaImp = sprintf("SELECT * FROM pg_iva iva
	WHERE estado = 1
		AND activo = 1
		AND iva.tipo IN (1,3)
	ORDER BY iva.activo DESC, iva.tipo");
	$rsIvaImp = mysql_query($queryIvaImp);
	if (!$rsIvaImp) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsIvaImp = mysql_num_rows($rsIvaImp);
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" style=\"font-size:9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td rowspan=\"3\">"."Fecha Documento"."</td>
					<td rowspan=\"3\">"."Fecha de Registro"."</td>
					<td rowspan=\"3\">"."Tipo de Documento"."</td>
					<td rowspan=\"3\">"."Nro. de Documento"."</td>
					<td rowspan=\"3\">"."Nro. de Control"."</td>
					<td rowspan=\"3\">"."Codigo Proveedor"."</td>
					<td rowspan=\"3\">".$spanProvCxP."</td>
					<td rowspan=\"3\">"."Proveedor"."</td>
					<td rowspan=\"3\">"."Nro. Nota de Crédito / Debito"."</td>
					<td rowspan=\"3\">"."Numero de Documento Afectado"."</td>
					<td rowspan=\"3\">"."Fecha Comprobante de Retencion"."</td>
					<td rowspan=\"3\">"."Numero de Comprobante de Retencion"."</td>
					<td rowspan=\"3\">"."Total de Compras Incluyendo el Impuesto"."</td>
					<td rowspan=\"3\">"."Compras Exentas"."</td>
					<td rowspan=\"3\">"."Compras Exoneradas"."</td>
					<td colspan=\"".($totalRowsIvaNac * 2)."\">"."Compras Internas Nacionales"."</td>
					<td colspan=\"".(2 + ($totalRowsIvaImp * 2))."\">"."Compras Internas Importadas"."</td>
					<td rowspan=\"3\">"."Impuesto Retenido"."</td>";
	$htmlTh .= "</tr>";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$contPosNac = 0;
	while ($rowIvaNac = mysql_fetch_array($rsIvaNac)) {
		$contPosNac += 2;
		
		$htmlTh .= "<td rowspan=\"2\" title=\"".($contPosNac-2)."\">"."Base Imponible"."</td>
					<td>"."Alicuota Impuesto"."</td>";
	}
		$htmlTh .= "<td rowspan=\"2\">"."Nro. de Planilla de Importacion"."</td>
					<td rowspan=\"2\">"."Nro. de Expediente"."</td>";
	$contPosImp = 0;
	while ($rowIvaImp = mysql_fetch_array($rsIvaImp)) {
		$contPosImp += 2;
		
		$htmlTh .= "<td rowspan=\"2\" title=\"".($contPosImp-2)."\">"."Base Imponible"."</td>
					<td>"."Alicuota Impuesto"."</td>";
	}
	$htmlTh .= "</tr>";
	
	// COLUMNAS DE IVA DE COMPRAS INTERNAS NACIONALES
	$arrayIvaNac = NULL;
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$rsIvaNac = mysql_query($queryIvaNac);
	if (!$rsIvaNac) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$contPosNac = 0;
	while ($rowIvaNac = mysql_fetch_array($rsIvaNac)) {
		$contPosNac += 2;
			
		$arrayIvaNac[$rowIvaNac['idIva']] = array(
			"id_iva" => $rowIvaNac['idIva'],		// ID IVA
			"porc_iva" => $rowIvaNac['iva'],		// IVA
			"pos_total_iva" => $contPosNac-1,	// POSICION DEL MONTO DEL IVA
			"pos_base_iva" => $contPosNac-2);	// POSICION DE LA BASE IMPONIBLE DEL IVA
		
		$htmlTh .= "<td title=\"".($contPosNac-1)."\">".$rowIvaNac['iva']."%</td>";
	}
	
	// COLUMNAS DE IVA DE COMPRAS INTERNAS IMPORTADAS
	$arrayIvaImp = NULL;
	$rsIvaImp = mysql_query($queryIvaImp);
	if (!$rsIvaImp) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$contPosImp = 0;
	while ($rowIvaImp = mysql_fetch_array($rsIvaImp)) {
		$contPosImp += 2;
		
		$arrayIvaImp[$rowIvaImp['idIva']] = array(
			"id_iva" => $rowIvaImp['idIva'],		// ID IVA
			"porc_iva" => $rowIvaImp['iva'],		// IVA
			"pos_total_iva" => $contPosImp-1,	// POSICION DEL MONTO DEL IVA
			"pos_base_iva" => $contPosImp-2);	// POSICION DE LA BASE IMPONIBLE DEL IVA
		
		$htmlTh .= "<td title=\"".($contPosImp-1)."\">".$rowIvaImp['iva']."%</td>";
	}
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		/*$sqlEstadoCuenta = sprintf("SELECT cp_estado_cuenta.*, cp_factura.fecha_factura_proveedor
		FROM cp_estado_cuenta
			INNER JOIN cp_factura ON (cp_factura.id_factura = cp_estado_cuenta.idDocumento)
		WHERE tipoDocumento = 'FA' AND fecha = '%s'
		
		UNION
		
		SELECT cp_estado_cuenta.*, cp_notacredito.fecha_notacredito
		FROM cp_estado_cuenta
			INNER JOIN cp_notacredito ON (cp_notacredito.id_notacredito = cp_estado_cuenta.idDocumento)
		WHERE tipoDocumento = 'NC' AND fecha = '%s'
		
		UNION
		
		SELECT cp_estado_cuenta.*, cp_notadecargo.fecha_notacargo
		FROM cp_estado_cuenta
			INNER JOIN cp_notadecargo ON (cp_notadecargo.id_notacargo = cp_estado_cuenta.idDocumento)
		WHERE tipoDocumento = 'ND' AND fecha = '%s'
		ORDER BY 6",
			$row['fecha_origen'],
			$row['fecha_origen'],
			$row['fecha_origen']);
		$rsEstadoCuenta = mysql_query($sqlEstadoCuenta);
		if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);*/
		
		$totalTotalCompraConIva = 0;
		$totalComprasExentas = 0;
		$totalComprasExonerado = 0;
		$arrayTotalIvaDocNac = NULL;
		$arrayTotalIvaDocImp = NULL;
		$totalRetenido = 0;
		$sumaRetenciones = 0;
		$numeroPlanillaImportacion = "-";
		$numeroExpediente = "-";
		
		for ($i = 0; $i < $contPosNac; $i++) {
			$arrayIvaDocNac[$i] = "";
		}
		for ($i = 0; $i < $contPosImp; $i++) {
			$arrayIvaDocImp[$i] = "";
		}
		
		if ($row['tipoDocumento'] == "FA") { //-------FACTURA--------
			$sqlDocumento = sprintf("SELECT * FROM cp_factura
			WHERE id_factura = %s
				AND fecha_origen = %s
				AND aplica_libros = '1'
				%s
			ORDER BY fecha_factura_proveedor",
				valTpDato($row['idDocumento'], "int"),
				valTpDato($row['fecha_origen'], "date"),
				$sqlBusq);
		} else if ($row['tipoDocumento'] == "NC") { //-------NOTA DE CRÉDITO--------
			$sqlDocumento = sprintf("SELECT * FROM cp_notacredito
			WHERE id_notacredito = %s
				AND fecha_registro_notacredito = %s
				AND aplica_libros_notacredito = '1'
				%s
			ORDER BY fecha_notacredito",
				valTpDato($row['idDocumento'], "int"),
				valTpDato($row['fecha_origen'], "date"),
				$sqlBusq2);
			
		} else if ($row['tipoDocumento'] == "ND") { //-------NOTA DE CARGO--------
			$sqlDocumento = sprintf("SELECT * FROM cp_notadecargo
			WHERE id_notacargo = %s
				AND fecha_origen_notacargo = %s
				AND aplica_libros_notacargo = '1'
				%s
			ORDER BY fecha_origen_notacargo",
				valTpDato($row['idDocumento'], "int"),
				valTpDato($row['fecha_origen'], "date"),
				$sqlBusq3);
		}
		$rsDocumento = mysql_query($sqlDocumento);
		if (!$rsDocumento) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsDocumento = mysql_num_rows($rsDocumento);
		$rowDocumento = mysql_fetch_array($rsDocumento);
		
		$nroNotaCredito = "-";
		$nroFacturaAfectada = "-";
		$fechaComprobanteRetencion75 = "-";
		$nroComprobanteRetencion75 = "-";
		$nroComprobanteRetencion3 = "-";
		$sumaRetenciones = 0;
		$numeroPlanillaImportacion = "-";
		$numeroExpediente = "-";
		$auxImportacion = false;
		if ($row["tipoDocumento"] == "FA" && $totalRowsDocumento > 0) {
			$fechaRegistro = date("d-m-Y",strtotime($rowDocumento["fecha_origen"]));
			$fechaDocumento = date("d-m-Y",strtotime($rowDocumento["fecha_factura_proveedor"]));
			$tipoDocumento = "FA";
			$nroFactura = $rowDocumento['numero_factura_proveedor'];
			$nroControl = $rowDocumento['numero_control_factura'];
			
			// VERIFICA SI LA FACTURA ES DE IMPORTACION
			$queryFacturaImportacion = sprintf("SELECT * FROM cp_factura_importacion WHERE id_factura = %s;",
				valTpDato($rowDocumento['id_factura'], "int"));
			$rsFacturaImportacion = mysql_query($queryFacturaImportacion);
			if (!$rsFacturaImportacion) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsFacturaImportacion = mysql_num_rows($rsFacturaImportacion);
			$rowFacturaImportacion = mysql_fetch_array($rsFacturaImportacion);
			$auxImportacion = false;
			if ($totalRowsFacturaImportacion > 0) {
				$numeroPlanillaImportacion = $rowFacturaImportacion['numero_planilla_importacion'];
				$numeroExpediente = $rowFacturaImportacion['numero_expediente'];
				
				$auxImportacion = true;
			}
			
			$queryRetencion = sprintf("SELECT
				idFactura,
				SUM(IvaRetenido) AS IvaRetenido,
				idRetencionCabezera
			FROM cp_retenciondetalle
			WHERE idFactura = %s
				AND id_nota_credito IS NULL
			GROUP BY idFactura",
				valTpDato($rowDocumento['id_factura'], "int"));
			$rsRetencion = mysql_query($queryRetencion);
			if (!$rsRetencion) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$rowRetencion = mysql_fetch_array($rsRetencion);
			
			$queryRetenciones = sprintf("SELECT * FROM cp_retencioncabezera WHERE idRetencionCabezera = %s;",
				valTpDato($rowRetencion['idRetencionCabezera'], "int"));
			$rsRetenciones = mysql_query($queryRetenciones);
			if (!$rsRetenciones) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$rowRetenciones = mysql_fetch_array($rsRetenciones);
			if ($rowDocumento["id_factura"] == $rowRetencion["idFactura"]) {
				$fechaComprobanteRetencion75 = date("d-m-Y",strtotime($rowRetenciones['fechaComprobante']));
				$nroComprobanteRetencion75 = $rowRetenciones["numeroComprobante"];
				$sumaRetenciones = $rowRetencion['IvaRetenido'];
				$totalRetenido += $sumaRetenciones;
			}
			
			// CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
			$queryFacturaGastos = sprintf("SELECT * FROM cp_factura_gasto
			WHERE id_factura = %s
				AND id_modo_gasto IN (1,3)",
				valTpDato($rowDocumento['id_factura'], "int"));
			$rsFacturaGasto = mysql_query($queryFacturaGastos);
			if (!$rsFacturaGasto) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$montoGastos = 0;
			while($rowFacturaGasto = mysql_fetch_array($rsFacturaGasto)) {
				$montoGastos += $rowFacturaGasto['monto'];
			}
			
			// CONSULTA PARA SUMAR LOS IVA DEL DOCUMENTO
			$queryFacturaIva = sprintf("SELECT * FROM cp_factura_iva WHERE id_factura = %s;",
				valTpDato($rowDocumento['id_factura'], "int"));
			$rsFacturaIva = mysql_query($queryFacturaIva);
			if (!$rsFacturaIva) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$montoIva = 0;
			$montoBaseImpIvas = 0;
			$montoIvaLujo = 0;
			while($rowFacturaIva = mysql_fetch_array($rsFacturaIva)) {
				$montoIva += $rowFacturaIva['subtotal_iva'];
				if ($rowFacturaIva['lujo'] != 1) {
					$montoBaseImpIvas += $rowFacturaIva['base_imponible'];
				} else {
					$montoIvaLujo = $rowFacturaIva['subtotal_iva'];
				}
				
				if ($auxImportacion == false){
					foreach ($arrayIvaNac as $indice => $valor) {
						if ($rowFacturaIva['id_iva'] == $arrayIvaNac[$indice]['id_iva']) {
							$arrayIvaDocNac[$arrayIvaNac[$indice]['pos_total_iva']] += doubleval($rowFacturaIva['subtotal_iva']);
							$arrayIvaDocNac[$arrayIvaNac[$indice]['pos_base_iva']] += doubleval($rowFacturaIva['base_imponible']);
						}
					}
				} else {
					foreach ($arrayIvaImp as $indice => $valor) {
						if ($rowFacturaIva['id_iva'] == $arrayIvaImp[$indice]['id_iva']) {
							$arrayIvaDocImp[($arrayIvaImp[$indice]['pos_total_iva'])] += doubleval($rowFacturaIva['subtotal_iva']);
							$arrayIvaDocImp[($arrayIvaImp[$indice]['pos_base_iva'])] += doubleval($rowFacturaIva['base_imponible']);
						}
					}
				}
			}
			
			$totalCompraConIva = $rowDocumento['subtotal_factura'] - $rowDocumento['subtotal_descuento'] + $montoGastos + $montoIva;
			
			$comprasExentas = $rowDocumento["monto_exento"];
			$comprasExonerado = $rowDocumento["monto_exonerado"];
			
			switch($rowDocumento['id_modulo']) {
				case 0 :
					$totalFacturaRepuestos += $totalCompraConIva;
					$totalBaseRepuestos += $montoBaseImpIvas;
					$totalIvaRepuestos += $montoIva;
					$totalExentaRepuestos += $comprasExentas;
					$totalExoRepuestos += $comprasExonerado;
					$totalRetenidoRepuesto += $sumaRetenciones;
					break;
				case 1 :
					$totalFacturaServicios += $totalCompraConIva;
					$totalBaseServicios += $montoBaseImpIvas;
					$totalIvaServicios += $montoIva;
					$totalExentaServicios += $comprasExentas;
					$totalExoServicios += $comprasExonerado;
					$totalRetenidoServicios += $sumaRetenciones;
					break;
				case 2 :
					$totalFacturaAutos += $totalCompraConIva;
					$totalBaseAutos += $montoBaseImpIvas;
					$totalIvaAutos += $montoIva;
					$totalExentaAutos += $comprasExentas;
					$totalExoAutos += $comprasExonerado;
					$totalRetenidoAutos += $sumaRetenciones;
					
					$totalIvaLujo += $montoIvaLujo;
					if ($montoIvaLujo > 0){
						$totalIva22Vehiculo += $montoIva;
						$totalExentaIva22Vehiculo += $comprasExentas;
						$totalExoneradoIva22Vehiculo += $comprasExonerado;
					} else{
						$totalIva12Vehiculo += $montoIva;
						$totalExentaIva12Vehiculo += $comprasExentas;
						$totalExoneradoIva12Vehiculo += $comprasExonerado;
					}
					break;
				case 3 :
					$totalFacturaAdministrativo += $totalCompraConIva;
					$totalBaseAdministrativo += $montoBaseImpIvas;
					$totalIvaAdministrativo += $montoIva;
					$totalExentaAdministrativo += $comprasExentas;
					$totalExoAdministrativo += $comprasExonerado;
					$totalRetenidoAdministrativo += $sumaRetenciones;
					break;
			}
			
			$totalTotalCompraConIva += $totalCompraConIva;
			$totalComprasExentas += $comprasExentas;
			$totalComprasExonerado += $comprasExonerado;
			
			$totalGlobalBaseImp = $totalBaseAdministrativo + $totalBaseAutos + $totalBaseServicios + $totalBaseRepuestos;
			$totalGlobalIvas = $totalIvaAdministrativo + $totalIvaAutos + $totalIvaServicios + $totalIvaRepuestos;
			$totalGlobalExentas = $totalExentaAdministrativo + $totalExentaAutos + $totalExentaServicios + $totalExentaRepuestos;
			$totalGlobalExonerado = $totalExoAdministrativo + $totalExoAutos + $totalExoServicios + $totalExoRepuestos;
		
		} else if ($row["tipoDocumento"] == "NC" && $totalRowsDocumento > 0) { /*--------NOTA DE CREDITO---------*/
			$fechaRegistro = date("d-m-Y",strtotime($rowDocumento["fecha_registro_notacredito"]));
			$fechaDocumento = date("d-m-Y",strtotime($rowDocumento["fecha_notacredito"]));
			$tipoDocumento = "NC";
			$nroFactura = "-";
			$nroControl = $rowDocumento['numero_control_notacredito'];
			
			$nroNotaCredito = $rowDocumento["numero_nota_credito"];
			
			if ($rowDocumento['tipo_documento'] == "ND") {
				// BUSCA LOS DATOS DE LA NOTA DE CARGO QUE DEVOLVIO
				$sqlnota = sprintf("SELECT * FROM cp_notadecargo WHERE id_notacargo = %s;",
					valTpDato($rowDocumento['id_documento'], "int"));
				$consulnota = mysql_query($sqlnota);
				if (!$consulnota) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
				$rowCargo = mysql_fetch_array($consulnota);
			} else if ($rowDocumento['tipo_documento'] == "FA") {
				// BUSCA LOS DATOS DE LA FACTURA QUE DEVOLVIO
				$queryFact = sprintf("SELECT * FROM cp_factura WHERE id_factura = %s;",
					valTpDato($rowDocumento['id_documento'], "int"));
				$rsFact = mysql_query($queryFact);
				if (!$rsFact) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
				$rowFactura = mysql_fetch_array($rsFact);
				
				$nroFacturaAfectada = $rowFactura["numero_factura_proveedor"];
			
				// VERIFICA SI LA DEVOLUCION ES DE UNA FACTURA ES DE IMPORTACION
				$queryFacturaImportacion = sprintf("SELECT * FROM cp_factura_importacion WHERE id_factura = %s;",
					valTpDato($rowDocumento['id_documento'], "int"));
				$rsFacturaImportacion = mysql_query($queryFacturaImportacion);
				if (!$rsFacturaImportacion) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
				$totalRowsFacturaImportacion = mysql_num_rows($rsFacturaImportacion);
				$rowFacturaImportacion = mysql_fetch_array($rsFacturaImportacion);
				if ($totalRowsFacturaImportacion > 0) {
					$numeroPlanillaImportacion = $rowFacturaImportacion['numero_planilla_importacion'];
					$numeroExpediente = $rowFacturaImportacion['numero_expediente'];
					
					$auxImportacion = true;
				}
			}
			
			$queryRetencion = sprintf("SELECT id_nota_credito, SUM(IvaRetenido) AS IvaRetenido, idRetencionCabezera FROM cp_retenciondetalle
			WHERE id_nota_credito = %s GROUP BY idFactura",
				valTpDato($rowDocumento['id_notacredito'], "int"));
			$rsRetencion = mysql_query($queryRetencion);
			if (!$rsRetencion) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$rowRetencion = mysql_fetch_array($rsRetencion);
			
			$queryRetenciones = sprintf("SELECT * FROM cp_retencioncabezera WHERE idRetencionCabezera = %s;",
				valTpDato($rowRetencion['idRetencionCabezera'], "int"));
			$rsRetenciones = mysql_query($queryRetenciones);
			if (!$rsRetenciones) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$rowRetenciones = mysql_fetch_array($rsRetenciones);
			if ($rowDocumento['id_notacredito'] == $rowRetencion['id_nota_credito']) {
				$fechaComprobanteRetencion75 = date("d-m-Y",strtotime($rowRetenciones['fechaComprobante']));
				$nroComprobanteRetencion75 = $rowRetenciones["numeroComprobante"];
				$sumaRetenciones = $rowRetencion['IvaRetenido'];
				$totalRetenido += $sumaRetenciones;
			}
			
			// CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
			$queryNotaCreditoGastos = sprintf("SELECT * FROM cp_notacredito_gastos
			WHERE id_notacredito = %s
				AND id_modo_gasto IN (1,3)",
				valTpDato($rowDocumento['id_notacredito'], "int"));
			$rsNotaCreditoGasto = mysql_query($queryNotaCreditoGastos);
			if (!$rsNotaCreditoGasto) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$montoGastos = 0;
			while($rowNotaCreditoGasto = mysql_fetch_array($rsNotaCreditoGasto)) {
				$montoGastos += $rowNotaCreditoGasto['monto_gasto_notacredito'];
			}
				
			// CONSULTA PARA SUMAR LOS IVA DEL DOCUMENTO
			$queryNotaCreditoIva = sprintf("SELECT * FROM cp_notacredito_iva WHERE id_notacredito = %s;",
				valTpDato($rowDocumento['id_notacredito'], "int"));
			$rsNotaCreditoIva = mysql_query($queryNotaCreditoIva);
			if (!$rsNotaCreditoIva) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$montoIva = 0;
			$montoBaseImpIvas = 0;
			$montoIvaLujo = 0;
			while($rowNotaCreditoIva = mysql_fetch_array($rsNotaCreditoIva)){
				$montoIva += $rowNotaCreditoIva['subtotal_iva_notacredito'];
				if ($rowNotaCreditoIva['lujo'] != 1) {
					$montoBaseImpIvas = $rowNotaCreditoIva['baseimponible_notacredito'];
				} else {
					$montoIvaLujo = $rowNotaCreditoIva['subtotal_iva_notacredito'];
				}
				
				if ($auxImportacion == false){
					foreach ($arrayIvaNac as $indice => $valor) {
						if ($rowNotaCreditoIva['id_iva_notacredito'] == $arrayIvaNac[$indice]['id_iva']) {
							$arrayIvaDocNac[$arrayIvaNac[$indice]['pos_total_iva']] += doubleval($rowNotaCreditoIva['subtotal_iva_notacredito']);
							$arrayIvaDocNac[$arrayIvaNac[$indice]['pos_base_iva']] += doubleval($rowNotaCreditoIva['baseimponible_notacredito']);
						}
					}
				} else {
					foreach ($arrayIvaImp as $indice => $valor) {
						if ($rowNotaCreditoIva['id_iva_notacredito'] == $arrayIvaImp[$indice]['id_iva']) {
							$arrayIvaDocImp[($arrayIvaImp[$indice]['pos_total_iva'])] += doubleval($rowNotaCreditoIva['subtotal_iva_notacredito']);
							$arrayIvaDocImp[($arrayIvaImp[$indice]['pos_base_iva'])] += doubleval($rowNotaCreditoIva['baseimponible_notacredito']);
						}
					}
				}
			}
			
			$totalCompraConIva = $rowDocumento['subtotal_notacredito'] - $rowDocumento['subtotal_descuento'] + $montoGastos + $montoIva;
			
			$comprasExentas = $rowDocumento["monto_exento_notacredito"];
			$comprasExonerado = $rowDocumento["monto_exonerado_notacredito"];
			
			switch($rowDocumento['id_departamento_notacredito']) {
				case 0 :
					$totalFacturaRepuestos += (-1) * ($montoIva + $montoBaseImpIvas + $comprasExentas + $comprasExonerado);
					$totalBaseRepuestos += (-1) * $montoBaseImpIvas;
					$totalIvaRepuestos += (-1) * $montoIva;
					$totalExentaRepuestos += (-1) * $comprasExentas;
					$totalExoRepuestos += (-1) * $comprasExonerado;
					$totalRetenidoRepuesto += $sumaRetenciones;
					break;
				case 1 :
					$totalFacturaServicios += (-1) * ($montoIva + $montoBaseImpIvas + $comprasExentas + $comprasExonerado);
					$totalBaseServicios += (-1) * $montoBaseImpIvas;
					$totalIvaServicios += (-1) * $montoIva;
					$totalExentaServicios += (-1) * $comprasExentas;
					$totalExoServicios += (-1) * $comprasExonerado;
					$totalRetenidoServicios += $sumaRetenciones;
					break;
				case 2 :
					$totalFacturaAutos += (-1) * ($montoIva + $montoBaseImpIvas + $comprasExentas + $comprasExonerado);
					$totalBaseAutos += (-1) * $montoBaseImpIvas;
					$totalIvaAutos += (-1) * $montoIva;
					$totalExentaAutos += (-1) * $comprasExentas;
					$totalExoAutos += (-1) * $comprasExonerado;
					$totalRetenidoAutos += $sumaRetenciones;
					
					$totalIvaLujo += (-1) * $montoIvaLujo;
					if ($montoIvaLujo > 0) {
						$totalIva22Vehiculo += (-1) * $montoIva;
						$totalExentaIva22Vehiculo += (-1) * $comprasExentas;
						$totalExoneradoIva22Vehiculo += (-1) * $comprasExonerado;
					} else {
						$totalIva12Vehiculo += (-1) * $montoIva;
						$totalExentaIva12Vehiculo += (-1) * $comprasExentas;
						$totalExoneradoIva12Vehiculo += (-1) * $comprasExonerado;
					}
					break;
				case 3 :
					$totalFacturaAdministrativo += (-1) * ($montoIva + $montoBaseImpIvas + $comprasExentas + $comprasExonerado);
					$totalBaseAdministrativo += (-1) * $montoBaseImpIvas;
					$totalIvaAdministrativo += (-1) * $montoIva;
					$totalExentaAdministrativo += (-1) * $comprasExentas;
					$totalExoAdministrativo += (-1) * $comprasExonerado;
					$totalRetenidoAdministrativo += $sumaRetenciones;
					break;
			}
			
			$totalTotalCompraConIva += (-1) * $totalCompraConIva;
			$totalComprasExentas += (-1) * $comprasExentas;
			$totalComprasExonerado += (-1) * $comprasExonerado;
			
			$totalGlobalBaseImp = $totalBaseAdministrativo + $totalBaseAutos + $totalBaseServicios + $totalBaseRepuestos;
			$totalGlobalIvas = $totalIvaAdministrativo + $totalIvaAutos + $totalIvaServicios + $totalIvaRepuestos;
			$totalGlobalExentas = $totalExentaAdministrativo + $totalExentaAutos + $totalExentaServicios + $totalExentaRepuestos;
			$totalGlobalExonerado = $totalExoAdministrativo + $totalExoAutos + $totalExoServicios + $totalExoRepuestos;
			
		} else if($row["tipoDocumento"] == "ND" && $totalRowsDocumento > 0) { /*-------NOTA DE CARGO---------*/
			$fechaRegistro = date("d-m-Y",strtotime($rowDocumento["fecha_origen_notacargo"]));
			$fechaDocumento = date("d-m-Y",strtotime($rowDocumento["fecha_notacargo"]));
			$tipoDocumento = "ND";
			$nroFactura = $rowDocumento['numero_notacargo'];
			$nroControl = $rowDocumento['numero_control_notacargo'];
			
			// CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
			$queryNotaCargoGastos = sprintf("SELECT * FROM cp_notacargo_gastos
			WHERE id_notacargo = %s
				AND id_modo_gasto IN (1,3)",
				valTpDato($rowDocumento['id_notacargo'], "int"));
			$rsNotaCargoGasto = mysql_query($queryNotaCargoGastos);
			if (!$rsNotaCargoGasto) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$montoGastos = 0;
			$montoBaseImpIvas = 0;
			while($rowNotaCargoGasto = mysql_fetch_array($rsNotaCargoGasto)) {
				$montoGastos += $rowNotaCargoGasto['monto']/* * ($rowNotaCargoGasto['iva'] / 100)) + $rowNotaCargoGasto['monto']*/;
			}
			
			// CONSULTA PARA SUMAR LOS IVA DEL DOCUMENTO
			$queryNotaCargoIva = sprintf("SELECT * FROM cp_notacargo_iva WHERE id_notacargo = %s;",
				valTpDato($rowDocumento['id_notacargo'], "int"));
			$rsNotaCargoIva = mysql_query($queryNotaCargoIva);
			if (!$rsNotaCargoIva) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$montoIva = 0;
			while($rowNotaCargoIva = mysql_fetch_array($rsNotaCargoIva)) {
				$montoIva += $rowNotaCargoIva['subtotal_iva'];
				$montoBaseImpIvas += $rowNotaCargoIva['baseimponible'];
				foreach ($arrayIvaNac as $indice => $valor) {
					if ($rowNotaCargoIva['id_iva'] == $arrayIvaNac[$indice]['id_iva']) {
						$arrayIvaDocNac[$arrayIvaNac[$indice]['pos_total_iva']] += doubleval($rowNotaCargoIva['subtotal_iva']);
						$arrayIvaDocNac[$arrayIvaNac[$indice]['pos_base_iva']] += doubleval($rowNotaCargoIva['baseimponible']);
					}
				}
			}				
			
			$totalCompraConIva = $rowDocumento['subtotal_notacargo'] - $rowDocumento['subtotal_descuento_notacargo'] + $montoGastos + $montoIva;

			$comprasExentas = $rowDocumento["monto_exento_notacargo"];
			$comprasExonerado = $rowDocumento["monto_exonerado_notacargo"];
			
			switch($rowDocumento['id_modulo']) {
				case 0 :
					$totalFacturaRepuestos += $totalCompraConIva;
					$totalBaseRepuestos += $montoBaseImpIvas;
					$totalIvaRepuestos += $montoIva;
					$totalExentaRepuestos += $comprasExentas;
					$totalExoRepuestos += $comprasExonerado;
					break;
				case 1 :
					$totalFacturaServicios += $totalCompraConIva;
					$totalBaseServicios += $montoBaseImpIvas;
					$totalIvaServicios += $montoIva;
					$totalExentaServicios += $comprasExentas;
					$totalExoServicios += $comprasExonerado;
					break;
				case 2 :
					$totalFacturaAutos += $totalCompraConIva;
					$totalBaseAutos += $montoBaseImpIvas;
					$totalIvaAutos += $montoIva;
					$totalExentaAutos += $comprasExentas;
					$totalExoAutos += $comprasExonerado;
					break;
				case 3 :
					$totalFacturaAdministrativo += $totalCompraConIva;
					$totalBaseAdministrativo += $montoBaseImpIvas;
					$totalIvaAdministrativo += $montoIva;
					$totalExentaAdministrativo += $comprasExentas;
					$totalExoAdministrativo += $comprasExonerado;
					break;
			}
						
			$totalTotalCompraConIva += $totalCompraConIva;
			$totalComprasExentas += $comprasExentas;
			$totalComprasExonerado += $comprasExonerado;
			
			$totalGlobalBaseImp = $totalBaseAdministrativo + $totalBaseAutos + $totalBaseServicios + $totalBaseRepuestos;
			$totalGlobalIvas = $totalIvaAdministrativo + $totalIvaAutos + $totalIvaServicios + $totalIvaRepuestos;
			$totalGlobalExentas = $totalExentaAdministrativo + $totalExentaAutos + $totalExentaServicios + $totalExentaRepuestos;
			$totalGlobalExonerado = $totalExoAdministrativo + $totalExoAutos + $totalExoServicios + $totalExoRepuestos;
		}
		$totalExentasGlobal = $totalExentaAutos + $totalExentaRepuestos + $totalExentaAdministrativo + $totalExentaServicios;
		$totalExoneradoGlobal = $totalExoAutos + $totalExoRepuestos + $totalExoAdministrativo + $totalExoServicios;
		$totalComprasExentasExo = $totalExoneradoGlobal + $totalExentasGlobal;
		$totalGlobalDocumentos = $totalGlobalBaseImp + $totalGlobalIvas + $totalGlobalExentas + $totalExoneradoGlobal;
		$totalGlobalRetenido = $totalRetenidoRepuesto + $totalRetenidoServicios + $totalRetenidoAutos + $totalRetenidoAdministrativo;	
		
		if ($rowDocumento['id_factura'] > 0 || $rowDocumento['id_notacredito'] > 0 || $rowDocumento['id_notacargo'] > 0) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			if ($rowDocumento['id_factura'] > 0) {
				$signo = 1;
			} else if ($rowDocumento['id_notacredito'] > 0) {
				$signo = (-1);
			} else if ($rowDocumento['id_notacargo'] > 0) {
				$signo = 1;
			}
			
			$queryProveedor = "SELECT prov.*,
				CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor
			FROM cp_proveedor prov
			WHERE prov.id_proveedor='".$rowDocumento['id_proveedor']."'";
			$rsProveedor = mysql_query($queryProveedor);
			if (!$rsProveedor) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$rowProveedor = mysql_fetch_array($rsProveedor);
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$fechaDocumento."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$fechaRegistro."</td>";/*Aki campos libros*/
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$tipoDocumento."</td>";
				$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$nroFactura."</td>";
				$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$nroControl."</td>";
				$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$rowProveedor['id_proveedor']."</td>";
				$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$rowProveedor['rif_proveedor']."</td>";
				$htmlTb .= "<td align=\"left\" nowrap=\"nowrap\">".utf8_encode($rowProveedor['nombre'])."</td>";
				$htmlTb .= "<td align=\"right\">".$nroNotaCredito."</td>";
				$htmlTb .= "<td align=\"right\">".$nroFacturaAfectada."</td>";
				$htmlTb .= "<td align=\"center\">".$fechaComprobanteRetencion75."</td>";
				$htmlTb .= "<td align=\"right\">".$nroComprobanteRetencion75."</td>";
				$htmlTb .= "<td align=\"right\">".formatoNumero($signo * $totalCompraConIva, $lstFormatoNumero)."</td>";
				$htmlTb .= "<td align=\"right\">".formatoNumero($signo * $comprasExentas, $lstFormatoNumero)."</td>";
				$htmlTb .= "<td align=\"right\">".formatoNumero($signo * $comprasExonerado, $lstFormatoNumero)."</td>";
			if (isset($arrayIvaDocNac)) {
				foreach ($arrayIvaDocNac as $indice => $valor) {
					$htmlTb .= "<td align=\"right\">".formatoNumero(doubleval($signo * $arrayIvaDocNac[$indice]), $lstFormatoNumero)."</td>";
					
					$arrayTotalIvaDocNac[$indice] += $signo * $arrayIvaDocNac[$indice];
				}
			}
				$htmlTb .= "<td align=\"right\">".$numeroPlanillaImportacion."</td>";
				$htmlTb .= "<td align=\"right\">".$numeroExpediente."</td>";
			if (isset($arrayIvaDocImp)) {
				foreach ($arrayIvaDocImp as $indice => $valor) {
					$htmlTb .= "<td align=\"right\">".formatoNumero(doubleval($signo * $arrayIvaDocImp[$indice]), $lstFormatoNumero)."</td>";
					
					$arrayTotalIvaDocImp[$indice] += $signo * $arrayIvaDocImp[$indice];
				}
			}
				$htmlTb .= "<td align=\"right\">".formatoNumero($sumaRetenciones, $lstFormatoNumero)."</td>";
			$htmlTb .= "</tr>";
		}
		
		//TOTALIZAR POR DIAS BLOQUEADO EL 17/05/2013
		/*$htmlTb.= "<tr class=\"trResaltar6\">";
			$htmlTb .= "<td align=\"center\" colspan=\"". 12 ."\">"."Total Dia: ".date("d-m-Y",strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".formatoNumero($totalTotalCompraConIva, $lstFormatoNumero)."</td>";
			$htmlTb .= "<td align=\"right\">".formatoNumero($totalComprasExentas, $lstFormatoNumero)."</td>";
			$htmlTb .= "<td align=\"right\">".formatoNumero($totalComprasExonerado, $lstFormatoNumero)."</td>";*/
		if (isset($arrayTotalIvaDocNac)) {
			foreach ($arrayTotalIvaDocNac as $indice => $valor) {
				//$htmlTb .= "<td align=\"right\">".formatoNumero($arrayTotalIvaDocNac[$indice], $lstFormatoNumero)."</td>";
				
				$arrayTotalesIvaDocNac[$indice] += $arrayTotalIvaDocNac[$indice];
			}
		}
			//$htmlTb .= "<td align=\"right\">"."</td>";
			//$htmlTb .= "<td align=\"right\">"."</td>";
		if (isset($arrayTotalIvaDocImp)) {
			foreach ($arrayTotalIvaDocImp as $indice => $valor) {
				//$htmlTb .= "<td align=\"right\">".formatoNumero($arrayTotalIvaDocImp[$indice], $lstFormatoNumero)."</td>";
				
				$arrayTotalesIvaDocImp[$indice] += $arrayTotalIvaDocImp[$indice];
			}
		}
			/*$htmlTb .= "<td align=\"right\">".formatoNumero($totalRetenido, $lstFormatoNumero)."</td>";
		$htmlTb .= "</tr>";*/
		
		$totalesTotalCompraConIva += $totalTotalCompraConIva;
		$totalesComprasExentas += $totalComprasExentas;
		$totalesComprasExonerado += $totalComprasExonerado;
		$totalesComprasRetenido += $totalRetenido;
	}
	$htmlTb.= "<tr class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"center\" colspan=\"12\">"."Total General del ".date("d-m-Y",strtotime($valCadBusq[0]))." al ".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlTb .= "<td align=\"right\">".formatoNumero($totalesTotalCompraConIva, $lstFormatoNumero)."</td>";
		$htmlTb .= "<td align=\"right\">".formatoNumero($totalesComprasExentas, $lstFormatoNumero)."</td>";
		$htmlTb .= "<td align=\"right\">".formatoNumero($totalesComprasExonerado, $lstFormatoNumero)."</td>";
	if (isset($arrayTotalesIvaDocNac)) {
		foreach ($arrayTotalesIvaDocNac as $indice => $valor) {
			$htmlTb .= "<td align=\"right\" title=\"arrayTotalesIvaDocNac[".$indice."]\">".formatoNumero($arrayTotalesIvaDocNac[$indice], $lstFormatoNumero)."</td>";
		}
	}
		$htmlTb .= "<td align=\"right\"></td>";
		$htmlTb .= "<td align=\"right\"></td>";
	if (isset($arrayTotalesIvaDocImp)) {
		foreach ($arrayTotalesIvaDocImp as $indice => $valor) {
			$htmlTb .= "<td align=\"right\">".formatoNumero($arrayTotalesIvaDocImp[$indice], $lstFormatoNumero)."</td>";
		}
	}
		$htmlTb .= "<td align=\"right\">".formatoNumero($totalesComprasRetenido, $lstFormatoNumero)."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"".(18 + ($totalRowsIvaNac * 2) + ($totalRowsIvaImp * 2))."\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaLibroCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"".(18 + ($totalRowsIvaNac * 2) + ($totalRowsIvaImp * 2))."\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("tdLibroCompra","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$globalTotalIva22 = $arrayTotalesIvaDocNac[2]+$arrayTotalesIvaDocNac[3];
	$globalTotalBaseIva10 = $totalBaseAutos - $arrayTotalesIvaDocNac[2];
	$globalTotalIva10 = $totalIvaAutos - $arrayTotalesIvaDocNac[3];
	$totalAlicuotaGeneral = $globalTotalBaseIva10 +	$globalTotalIva10;
	$globalTotalIvaReducido = $arrayTotalesIvaDocNac[4]+$arrayTotalesIvaDocNac[5];
	
	$htmlCuadro .= "<table width=\"70%\" align=\"center\" border=\"1\" class=\"tabla\" cellpadding=\"2\" style=\"font-size:9px\">";
	$htmlCuadro .= "<tr align=\"center\" class=\"tituloColumna\" height=\"24\">";
		$htmlCuadro .= "<td width=\"30%\"></td>";
		//$htmlCuadro .= "<td width=\"5%\">Desde</td>";
		//$htmlCuadro .= "<td width=\"5%\">Hasta</td>";
		$htmlCuadro .= "<td width=\"10%\">Base</td>";
		$htmlCuadro .= "<td width=\"10%\">I.V.A.</td>";
		$htmlCuadro .= "<td width=\"10%\">Exentas</td>";
		$htmlCuadro .= "<td width=\"10%\">Exoneradas</td>";
		$htmlCuadro .= "<td width=\"10%\">Total</td>";
		$htmlCuadro .= "<td width=\"10%\">I.V.A. Retenido</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Repuestos</td>";
		//$htmlCuadro .= "<td>".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .= "<td>".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseRepuestos, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalIvaRepuestos, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalExentaRepuestos, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalExoRepuestos, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaRepuestos, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalRetenidoRepuesto, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr align=\"center\" height=\"24\">"; 
		$htmlCuadro .= "<td>Libro Compra de Servicios</td>";
		//$htmlCuadro .= "<td>".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .= "<td>".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseServicios, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalIvaServicios, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalExentaServicios, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalExoServicios, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaServicios, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalRetenidoServicios, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr align=\"center\" bgcolor='#CCCCCC' height=\"24\">";
		$htmlCuadro .= "<td>Compras Internas Gravadas Alicuotas Reducidas</td>";
		//$htmlCuadro .= "<td>".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .= "<td>".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlCuadro .= "<td>".formatoNumero($arrayTotalesIvaDocNac[4], $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($arrayTotalesIvaDocNac[5], $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($globalTotalIvaReducido, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr bgcolor='#CCCCCC' height=\"24\">";
		$htmlCuadro .= "<td>Libro de Compras de Vehiculos Alicuota General</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($globalTotalBaseIva10, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalIva12Vehiculo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExentaIva12Vehiculo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExoneradoIva12Vehiculo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalAlicuotaGeneral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">-</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr bgcolor='#CCCCCC' height=\"24\">";
		$htmlCuadro .= "<td>Compras Internas Gravadas por Alicuota General más Alicuota Adicional</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($arrayTotalesIvaDocNac[2], $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalIva22Vehiculo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExentaIva22Vehiculo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExoneradoIva22Vehiculo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($globalTotalIva22, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalRetenidoAutos, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	//$htmlCuadro .= "<tr height=\"24\">";
		//$htmlCuadro .= "<td>Libro Compra de Vehiculos</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		//$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalBaseAutos, $lstFormatoNumero)."</td>";
		//$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalIvaAutos, $lstFormatoNumero)."</td>";
		//$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExentaAutos, $lstFormatoNumero)."</td>";
		//$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExoAutos, $lstFormatoNumero)."</td>";
		//$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalFacturaAutos, $lstFormatoNumero)."</td>";
		//$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalRetenidoAutos, $lstFormatoNumero)."</td";
	//$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Administración</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalBaseAdministrativo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalIvaAdministrativo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExentaAdministrativo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExoAdministrativo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalFacturaAdministrativo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalRetenidoAdministrativo, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr height=\"24\">";
		$htmlCuadro .= "<td>Compra No Grabadas y/o Sin Derecho a Credito Fiscal</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlCuadro .= "<td align=\"center\">-</td>";
		$htmlCuadro .= "<td align=\"center\">-</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExentasGlobal, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalExoneradoGlobal, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalComprasExentasExo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">-</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr height=\"24\">";
		$htmlCuadro .= "<td>Total de Compras y Créditos Fiscales</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[0]))."</td>";
		//$htmlCuadro .= "<td align=\"center\">".date("d-m-Y",strtotime($valCadBusq[1]))."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalGlobalBaseImp, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalGlobalIvas, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalGlobalExentas, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalesTotalCompraConIva, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td align=\"center\">".formatoNumero($totalGlobalRetenido, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "</table>";
	
	$objResponse->assign("tdCuadroLibroCompra","innerHTML",$htmlCuadro);
	$objResponse->script("xajax_encabezadoEmpresa(1)");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"listaLibroCompra");

function formatoNumero($monto, $idFormatoNumero = 1){
	switch($idFormatoNumero) {
		case 1 : return number_format($monto, 2, ".", ","); break;
		case 2 : return number_format($monto, 2, ",", "."); break;
		case 3 : return number_format($monto, 2, ".", ""); break;
		case 4 : return number_format($monto, 2, ",", ""); break;
	}
}
?>