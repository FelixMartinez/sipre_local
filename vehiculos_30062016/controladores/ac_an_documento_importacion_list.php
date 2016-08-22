<?php


function asignarFacturaGasto($idFactura) {
	$objResponse = new xajaxResponse();
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFactura = sprintf("SELECT cxp_fact.*,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor
	FROM cp_factura cxp_fact
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
	WHERE cxp_fact.id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	
	$objResponse->assign("txtIdFacturaCargo","value",$rowFactura['id_factura']);
	$objResponse->assign("txtNumeroFacturaProveedorCargo","value",$rowFactura['numero_factura_proveedor']);
	$objResponse->assign("txtIdProvCargo","value",$rowFactura['id_proveedor']);
	$objResponse->assign("txtNombreProvCargo","value",utf8_encode($rowFactura['nombre_proveedor']));
	$objResponse->assign("txtSubtotal","value",number_format($rowFactura['subtotal_factura'], 2, ".", ","));
	
	$objResponse->script("byId('btnCancelarListaRegistroCompra').click();");
	
	return $objResponse;
}

function buscarGastoImportacion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaGastoImportacion(0, "id_factura_gasto", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarRegistroCompra($frmBuscarRegistroCompra) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarRegistroCompra['txtFechaDesdeBuscarRegistroCompra'],
		$frmBuscarRegistroCompra['txtFechaHastaBuscarRegistroCompra'],
		$frmBuscarRegistroCompra['txtCriterioBuscarRegistroCompra']);
	
	$objResponse->loadCommands(listaFacturaCompra(0, "id_factura", "DESC", $valBusq));
	
	return $objResponse;
}

function cargarGastoImportacion($idFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_registro_compra_form","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarTotalDcto').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DE LA FACTURA Y DEL CARGO PENDIENTE
	$queryFactura = sprintf("SELECT *
	FROM cp_factura_gasto cxp_fact_gasto
		INNER JOIN cp_factura cxp_fact ON (cxp_fact_gasto.id_factura = cxp_fact.id_factura)
	WHERE cxp_fact_gasto.id_factura_gasto = %s;",
		valTpDato($idFacturaGasto, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idFactura = $rowFactura['id_factura'];
	$idModulo = $rowFactura['id_modulo'];
	$idMonedaLocal = $rowFactura['id_moneda'];
	$idMonedaOrigen = ($rowFactura['id_moneda_tasa_cambio'] > 0) ? $rowFactura['id_moneda_tasa_cambio'] : $rowFactura['id_moneda'];
	
	$objResponse->assign("spnNumeroFacturaProveedor","innerHTML",$rowFactura['numero_factura_proveedor']);
	$objResponse->assign("spnNumeroControl","innerHTML",$rowFactura['numero_control_factura']);
	$objResponse->assign("spnFechaProveedor","innerHTML",date("d-m-Y",strtotime($rowFactura['fecha_factura_proveedor'])));
	$objResponse->assign("lstMoneda","value",$idMonedaOrigen); // MONEDA ORIGEN
	$objResponse->assign("hddIdMoneda","value",$idMonedaLocal); // MONEDA LOCAL
	
	// BUSCA LA TASA DE CAMBIO
	$query = sprintf("SELECT * FROM cp_factura_importacion fact_imp
	WHERE fact_imp.id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$hddNacionalizar = $row['nacionalizada'];
	$txtTasaCambio = $row['tasa_cambio'];
	if ($txtTasaCambio > 0) {
		$objResponse->assign("lstMoneda","value",$row['id_moneda_tasa_cambio']); // MONEDA ORIGEN
	}
	
	$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 2, ".", ","));
	
	// BUSCA LOS MONTOS DEL DETALLE DE LA FACTURA
	if ($idModulo == 0) {
		$query = sprintf("SELECT 
			SUM((cxp_fact_det.cantidad * cxp_fact_det_imp.costo_unitario)) AS subtotal
		FROM cp_factura_detalle_importacion cxp_fact_det_imp
			INNER JOIN cp_factura_detalle cxp_fact_det ON (cxp_fact_det_imp.id_factura_detalle = cxp_fact_det.id_factura_detalle)
		WHERE cxp_fact_det.id_factura = %s;",
			valTpDato($idFactura, "int"));
	} else if ($idModulo == 2) {
		$query = sprintf("SELECT 
			SUM((1 * cxp_fact_det_imp.costo_unitario)) AS subtotal
		FROM cp_factura_detalle_unidad_importacion cxp_fact_det_imp
			INNER JOIN cp_factura_detalle_unidad cxp_fact_det ON (cxp_fact_det_imp.id_factura_detalle_unidad = cxp_fact_det.id_factura_detalle_unidad)
		WHERE cxp_fact_det.id_factura = %s;",
			valTpDato($idFactura, "int"));
	}
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$subTotalFactura = $row['subtotal'];
	
	// BUSCA LOS MONTOS DE LOS GASTOS DE LA FACTURA
	$query = sprintf("SELECT
		(SUM(fact_gasto.monto) / (SELECT fact_imp.tasa_cambio
								FROM cp_factura_importacion fact_imp
								WHERE fact_imp.id_factura = fact_gasto.id_factura)) AS subtotal_gastos
	FROM cp_factura_gasto fact_gasto
	WHERE fact_gasto.id_factura = %s
		AND fact_gasto.id_modo_gasto IN (1)
		AND fact_gasto.afecta_documento = 1;",
		valTpDato($idFactura, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$subTotalGastos = $row['subtotal_gastos'];
	$totalFactura = $subTotalFactura + $subTotalGastos;
	
	$objResponse->assign("tdGastos","innerHTML",formularioGastos(false, $idFactura, "REGISTRO", 3, $frmTotalDcto));
	
	$objResponse->assign("hddIdFactura","value",$idFactura);
	$objResponse->assign("hddIdModulo","value",$idModulo);
	$objResponse->assign("hddNacionalizar","value",$hddNacionalizar);
	
	$objResponse->assign("txtGastos","value",number_format($subTotalGastos,2,".",","));
	$objResponse->assign("txtSubTotal","value",number_format($subTotalFactura,2,".",","));
	$objResponse->assign("txtTotalOrden","value",number_format($totalFactura,2,".",","));
	
	$objResponse->script("xajax_calcularDcto('', '', xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	for ($cont = 0; isset($frmTotalDcto['txtIva'.$cont]); $cont++) {
		$objResponse->script("
		fila = document.getElementById('trIva:".$cont."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
	}
	
	$idFactura = $frmTotalDcto['hddIdFactura'];
	$idModulo = $frmTotalDcto['hddIdModulo'];
	$hddNacionalizar = $frmTotalDcto['hddNacionalizar'];
	
	$idMonedaLocal = $frmTotalDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmTotalDcto['hddIdMoneda'] == $frmTotalDcto['lstMoneda']) ? $frmTotalDcto['hddIdMoneda'] : $frmTotalDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = str_replace(",","",$frmTotalDcto['txtTasaCambio']);
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	$incluirIvaMonedaOrigen = $rowMonedaOrigen['incluir_impuestos'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	$subTotal = str_replace(",","",$frmTotalDcto['txtSubTotal']);
	
	// BUSCA LOS MONTOS DE LOS GASTOS DE LA FACTURA
	$query = sprintf("SELECT * FROM cp_factura_gasto fact_gasto
	WHERE fact_gasto.id_factura = %s
		AND fact_gasto.id_modo_gasto IN (1)
		AND fact_gasto.afecta_documento = 1;",
		valTpDato($idFactura, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowGasto = mysql_fetch_assoc($rs)) {
		$monto = $rowGasto['monto'];
		$subTotalGastos += $monto;
	}
	$subTotalGastos = $subTotalGastos / $txtTasaCambio; // PARA CONVERTIRLO EN LA MONEDA DE ORIGEN
	
	// BUSCA LOS MONTOS DEL ADVALOREM DEL DETALLE DE LA FACTURA
	if ($idModulo == 0) {
		$query = sprintf("SELECT 
			cxp_fact_det.cantidad,
			cxp_fact_det_imp.costo_unitario,
			cxp_fact_det_imp.porcentaje_grupo,
			cxp_fact_det.id_iva,
			cxp_fact_det.iva
		FROM cp_factura_detalle_importacion cxp_fact_det_imp
			INNER JOIN cp_factura_detalle cxp_fact_det ON (cxp_fact_det_imp.id_factura_detalle = cxp_fact_det.id_factura_detalle)
		WHERE cxp_fact_det.id_factura = %s;",
			valTpDato($idFactura, "int"));
	} else if ($idModulo == 2) {
		$query = sprintf("SELECT 
			1 AS cantidad,
			cxp_fact_det_imp.costo_unitario,
			cxp_fact_det_imp.porcentaje_grupo,
			
			(SELECT cxp_fact_iva.id_iva FROM cp_factura_iva cxp_fact_iva
			WHERE cxp_fact_iva.id_factura = cxp_fact_det.id_factura
				AND (cxp_fact_iva.lujo IS NULL OR cxp_fact_iva.lujo = 0)) AS id_iva,
			
			(SELECT cxp_fact_iva.iva FROM cp_factura_iva cxp_fact_iva
			WHERE cxp_fact_iva.id_factura = cxp_fact_det.id_factura
				AND (cxp_fact_iva.lujo IS NULL OR cxp_fact_iva.lujo = 0)) AS iva
			
		FROM cp_factura_detalle_unidad_importacion cxp_fact_det_imp
			INNER JOIN cp_factura_detalle_unidad cxp_fact_det ON (cxp_fact_det_imp.id_factura_detalle_unidad = cxp_fact_det.id_factura_detalle_unidad)
		WHERE cxp_fact_det.id_factura = %s;",
			valTpDato($idFactura, "int"));
	}
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$hddTotalArt = $row['cantidad'] * $row['costo_unitario'];
		
		$montoGasto = $subTotalGastos;
		$gastosArt = (($hddTotalArt - $hddTotalDescuentoArt) * $montoGasto) / $subTotal;
		
		$hddGastosArt = $gastosArt;
				
		// CALCULA LOS DATOS DE IMPORTACION
		$precioTotalFOB = $hddTotalArt;
		$totalCIF = $precioTotalFOB + $hddGastosArt;
		$totalPrecioCIF = $totalCIF * $txtTasaCambio;
		$tarifaAdValorem = ($totalPrecioCIF * $row['porcentaje_grupo']) / 100;
				
		$monto = $totalPrecioCIF + $tarifaAdValorem;
		
		$existeAdValorem = false;
		if (isset($arrayAdValorem)) {
			foreach ($arrayAdValorem as $indice2 => $valor2) {
				if (str_replace(",","",$frmListaArticulo['txtTarifaAdValorem'.$valor]) == $arrayAdValorem[$indice2][0]) {
					$existeAdValorem = true;
					$arrayAdValorem[$indice2][1] = $arrayAdValorem[$indice2][1] + $tarifaAdValorem;
					$arrayAdValorem[$indice2][2]++;
				}
			}
		}
				
		if ($existeAdValorem == false) {
			$arrayDetalle[0] = str_replace(",","",$frmListaArticulo['txtTarifaAdValorem'.$valor]);
			$arrayDetalle[1] = $tarifaAdValorem;
			$arrayDetalle[2] = 1;
			$arrayAdValorem[] = $arrayDetalle;
		}
				
		$estatusIva = ($hddNacionalizar == 1 && $incluirIvaMonedaLocal == 1) ? 1 : 0;
		$totalRowsIva = ($row['id_iva'] > 0) ? 1 : 0;
		$idIva = $row['id_iva'];
		$porcIva = $row['iva'];
		
		if ($totalRowsIva == 0 || $estatusIva == 0) {
			$totalExentoLocal += $monto;
		} else {
			$existIva = false;
			if (isset($arrayIva)) {
				foreach ($arrayIva as $indiceIva => $valorIva) {
					if ($arrayIva[$indiceIva][0] == $idIva) {
						$arrayIva[$indiceIva][1] += $monto;
						$arrayIva[$indiceIva][2] += ($monto * ($porcIva / 100));
						$existIva = true;
					}
				}
			}
			
			if ($idIva > 0 && $existIva == false && $monto > 0) {
				$arrayDetalleIva[0] = $idIva;
				$arrayDetalleIva[1] = $monto;
				$arrayDetalleIva[2] = ($monto * ($porcIva / 100));
				$arrayDetalleIva[3] = $porcIva;
				
				$arrayIva[] = $arrayDetalleIva;
			}
		}
		
		$totalADV += $tarifaAdValorem;
	}
	
	$objResponse->assign("txtSubTotalAdValorem","value",number_format($totalADV,2,".",","));
	
	// SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IVA
	$gastosConIvaOrigen = 0;
	$gastosSinIvaOrigen = 0;
	if (isset($frmTotalDcto['cbxGasto'])) {
		foreach ($frmTotalDcto['cbxGasto'] as $indice2 => $valor2) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s;",
				valTpDato($frmTotalDcto['hddIdGasto'.$valor2], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGasto = mysql_fetch_assoc($rsGasto);
			
			if ($frmTotalDcto['hddTipoGasto'.$valor2] == 0) { // SACA EL MONTO MEDIANTE EL PORCENTAJE
				$porcentaje = ($subTotal == 0) ? 0 : str_replace(",","",$frmTotalDcto['txtPorcGasto'.$valor2]);
				$monto = ($subTotal == 0) ? 0 : $porcentaje * ($subTotal / 100);
				$objResponse->assign('txtMontoGasto'.$valor2,"value",number_format($monto, 2, ".", ","));
			} else if ($frmTotalDcto['hddTipoGasto'.$valor2] == 1) { // SACA EL PORCENTAJE MEDIANTE EL MONTO
				$monto = ($subTotal == 0) ? 0 : str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor2]);
				$porcentaje = ($subTotal == 0) ? 0 : $monto * (100 / $subTotal);
				$objResponse->assign('txtPorcGasto'.$valor2,"value",number_format($porcentaje, 2, ".", ","));
			}
			
			$monto = str_replace(",","",$monto);
			
			if ($idModoCompra == 2 && ($incluirIvaMonedaOrigen == 1 || $incluirIvaMonedaLocal == 1)) { // 2 = Importacion
				if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 1) {
					$estatusIva = 1;
					$queryIva = sprintf("SELECT * FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
				} else if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 0 && $incluirIvaMonedaLocal == 1) {
					$estatusIva = 0;
					// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
					$queryIva = sprintf("SELECT * FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (1) AND iva.activo = 1;");
				} else if ($rowGasto['id_modo_gasto'] == 3 && $incluirIvaMonedaLocal == 1) {
					$estatusIva = 1;
					$queryIva = sprintf("SELECT * FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
				}
			} else {
				$estatusIva = 1;
				$queryIva = sprintf("SELECT * FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
			}
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$idIva = $rowIva['idIva'];
			$porcIva = $rowIva['iva'];
			
			if ($totalRowsIva == 0 || $estatusIva == 0) {
				if ($rowGasto['id_modo_gasto'] == 1) // 1 = Nacional
					$gastosSinIvaOrigen += $monto;
				else if ($rowGasto['id_modo_gasto'] == 3) // 3 = Nacional por Importacion
					$gastosSinIva += $monto;
			} else {
				if ($idModoCompra == 2 && $incluirIvaMonedaLocal == 1) { // 2 = Importacion
					$ivaArt = $porcIva;
				} else {
					$ivaArt = ($frmDcto['txtIdFactura'] > 0) ? str_replace(",","",$frmTotalDcto['hddIvaGasto'.$valor2]) : $porcIva;
				}
				
				$existIva = false;
				if (isset($arrayIva)) {
					foreach ($arrayIva as $indiceIva => $valorIva) {
						if ($arrayIva[$indiceIva][0] == $idIva) {
							$arrayIva[$indiceIva][1] += $monto;
							$arrayIva[$indiceIva][2] += ($monto * ($ivaArt / 100));
							$existIva = true;
						}
					}
				}
				
				if ($idIva > 0 && $existIva == false && $monto > 0) {
					$arrayDetalleIva[0] = $idIva;
					$arrayDetalleIva[1] = $monto;
					$arrayDetalleIva[2] = ($monto * ($ivaArt / 100));
					$arrayDetalleIva[3] = $ivaArt;
					
					$arrayIva[] = $arrayDetalleIva;
				}
				
				if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 1) {
					$gastosConIvaOrigen += $monto;
				} else if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 0 && $incluirIvaMonedaLocal == 1) {
					$gastosConIva += $monto;
				} else if ($rowGasto['id_modo_gasto'] == 3 && $incluirIvaMonedaLocal == 1) {
					$gastosConIva += $monto;
				}
			}
		}
	}
	
	// CREA LOS ELEMENTOS DE IVA
	if (isset($arrayIva)) {
		foreach ($arrayIva as $indiceIva => $valorIva) {
			$queryIva = sprintf("SELECT * FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($arrayIva[$indiceIva][0], "int"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$ivaArt = ($frmDcto['txtIdFactura'] != "") ? $arrayIva[$indiceIva][3] : $rowIva['iva'];
			
			if ($arrayIva[$indiceIva][2] > 0) {
				// INSERTA EL ARTICULO SIN INJECT
				$htmlIva = sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:</div>".
							"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';",
					$indiceIva,
						$indiceIva, htmlentities($rowIva['observacion']),
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][0],
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][1],2), 2, ".", ","),
						$indiceIva, $indiceIva, $ivaArt, "%",
						$abrevMonedaLocal,
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][2],2), 2, ".", ","));
				
				if ($idModoCompra == 2) { // 2 = Importacion
					$objResponse->script(sprintf("
						%s
						
						obj = byId('trIva:%s');
						if(obj == undefined)
							$('#trRetencionIva').before(elemento);",
						$htmlIva,
						$indiceIva));
				} else {
					$objResponse->script(sprintf("
						%s
						
						obj = byId('trIva:%s');
						if(obj == undefined)
							$('#trGastosSinIva').before(elemento);",
						$htmlIva,
						$indiceIva));
				}
			}
			
			$subTotalIva += ($idModoCompra == 1) ? doubleval($arrayIva[$indiceIva][2]) : 0; // 1 = Nacional
		}
	}
	
	$objResponse->assign("tdSubTotalMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdDescuentoMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdGastoConIvaMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdGastoSinIvaMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdTotalRegistroMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdTotalFacturaMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdTotalAdValorem","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdExentoMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdExoneradoMoneda","innerHTML",$abrevMonedaOrigen);
	
	return $objResponse;
}

function frmFacturaGasto($idFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_documento_importacion_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarFacturaGasto').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFactura = sprintf("SELECT DISTINCT
		cxp_fact.id_factura,
		cxp_fact.id_modo_compra,
		
		(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			CONCAT_WS('*','',(SELECT reg_comp_uni_fis.numeroFactura
			FROM an_registro_compras_unidades_fisicas reg_comp_uni_fis
			WHERE reg_comp_uni_fis.idUnidadFisica = uni_fis.id_unidad_fisica
			LIMIT 1))
		ELSE
			cxp_fact.numero_factura_proveedor
		END) AS numero_factura_proveedor,
		
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.numeroControl, cxp_fact.numero_control_factura) AS numero_control_factura,
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.referenciaPedido, ped_comp.idPedidoCompra) AS id_pedido_compra,
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.fechaCompra, cxp_fact.fecha_factura_proveedor) AS fecha_factura_proveedor,
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.fechaActualizado, cxp_fact.fecha_origen) AS fecha_origen,
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.fechaVencimiento, cxp_fact.fecha_vencimiento) AS fecha_vencimiento,
		
		cxp_fact.id_proveedor,
		
		(SELECT CONCAT_WS('-', prov.lrif, prov.rif) FROM cp_proveedor prov
		WHERE prov.id_proveedor IN (cxp_fact.id_proveedor, reg_comp_uni_fis.proveedor)
		LIMIT 1) AS rif_proveedor,
		
		(SELECT prov.nombre FROM cp_proveedor prov
		WHERE prov.id_proveedor IN (cxp_fact.id_proveedor, reg_comp_uni_fis.proveedor)
		LIMIT 1) AS nombre_proveedor,
		
		cxp_fact.id_modulo,
		origen.nom_origen,
		uni_fis.placa,
		uni_fis.serial_carroceria,
		
		(CASE id_modulo
			WHEN 2 THEN
				(SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
				+
				(SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura)
			ELSE
				(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
				WHERE cxp_fact_det.id_factura = cxp_fact.id_factura)
		END) AS cant_items,
		
		(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			(reg_comp_uni_fis.importeVehiculo + reg_comp_uni_fis.totalPaquete)
		ELSE
			cxp_fact.subtotal_factura
		END) AS subtotal_factura,
		
		(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			reg_comp_uni_fis.descuentoVehiculo
		ELSE
			cxp_fact.subtotal_descuento
		END) AS subtotal_descuento,
		
		reg_comp_uni_fis.porcentajeIvaVehiculo AS porcentaje_iva,
		reg_comp_uni_fis.ivaVehiculo AS subtotal_iva,
		reg_comp_uni_fis.porcentajeImpuestoLujoVehiculo AS porcentaje_iva_lujo,
		reg_comp_uni_fis.impuestoLujoVehiculo AS subtotal_iva_lujo,
		reg_comp_uni_fis.montoExento AS monto_exento,
		reg_comp_uni_fis.montoExonerado AS monto_exonerado,
		uni_fis.id_unidad_fisica,
		uni_fis.placa,
		uni_fis.serial_carroceria,
		ped_comp_det.flotilla,
		
		(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			reg_comp_uni_fis.montoTotal
		ELSE
			(IFNULL(cxp_fact.subtotal_factura, 0)
				- IFNULL(cxp_fact.subtotal_descuento, 0)
				+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
						FROM cp_factura_gasto cxp_fact_gasto
						WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
							AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
				+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
						FROM cp_factura_iva cxp_fact_iva
						WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0))
		END) AS total,
		
		cxp_fact.activa,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = cxp_fact.id_factura
			AND cxp_fact.id_modulo IN (2)
		LIMIT 1) AS idRetencionCabezera
		
	FROM an_unidad_fisica uni_fis
		LEFT JOIN an_registro_compras_unidades_fisicas reg_comp_uni_fis ON (uni_fis.id_unidad_fisica = reg_comp_uni_fis.idUnidadFisica)
		LEFT JOIN an_origen origen ON (uni_fis.id_origen = origen.id_origen)
		INNER JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		INNER JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
	WHERE cxp_fact.id_factura IN (SELECT cxp_fact_gasto.id_factura FROM cp_factura_gasto cxp_fact_gasto
									WHERE cxp_fact_gasto.id_factura_gasto = %s);",
		valTpDato($idFacturaGasto, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	// BUSCA LOS DATOS DEL GASTO
	$queryFacturaGasto = sprintf("SELECT cxp_fact_gasto.*,
		gasto.nombre
	FROM cp_factura_gasto cxp_fact_gasto
		INNER JOIN pg_gastos gasto ON (cxp_fact_gasto.id_gasto = gasto.id_gasto)
	WHERE cxp_fact_gasto.id_factura_gasto = %s;",
		valTpDato($idFacturaGasto, "int"));
	$rsFacturaGasto = mysql_query($queryFacturaGasto);
	if (!$rsFacturaGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFacturaGasto = mysql_num_rows($rsFacturaGasto);
	$rowFacturaGasto = mysql_fetch_assoc($rsFacturaGasto);
	
	$objResponse->assign("hddIdFacturaGasto","value",$idFacturaGasto);
	$objResponse->assign("txtNumeroFacturaProveedor","value",$rowFactura['numero_factura_proveedor']);
	$objResponse->assign("txtIdProv","value",$rowFactura['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",utf8_encode($rowFactura['nombre_proveedor']));
	$objResponse->assign("txtNombreGasto","value",utf8_encode($rowFacturaGasto['nombre']));
	$objResponse->assign("txtSubtotalEstimado","value",number_format($rowFacturaGasto['monto'], 2, ".", ","));
	
	return $objResponse;
}

function guardarBaseImponible($frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_registro_compra_form","insertar")) { errorGuardarBaseImponible($objResponse); return $objResponse; }
	
	$idFactura = $frmTotalDcto{'hddIdFactura'};
	
	mysql_query("START TRANSACTION;");
	
	if (isset($frmTotalDcto['cbxGasto'])) {
		foreach ($frmTotalDcto['cbxGasto'] as $indice => $valor) {
			$idGasto = $frmTotalDcto['hddIdGasto'.$valor];
			
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s;",
				valTpDato($idGasto, "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) { errorGuardarBaseImponible($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowGastos = mysql_fetch_assoc($rsGasto);
			
			// ELIMINA LOS DATOS DEL GASTO DE LA FACTURA
			$deleteSQL = sprintf("DELETE FROM cp_factura_gasto
			WHERE id_factura = %s
				AND id_gasto = %s;",
				valTpDato($idFactura, "int"),
				valTpDato($idGasto, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarBaseImponible($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			if ($idModoCompra == 2 && $rowGastos['id_modo_gasto'] == 1) { // 2 = Importacion && 1 = Nacional
				$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor]) * $txtTasaCambio;
				$porcMontoGasto = ($montoGasto * 100) / $txtSubTotal;
			} else {
				$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor]);
				$porcMontoGasto = str_replace(",","",$frmTotalDcto['txtPorcGasto'.$valor]);
			}
			
			if (round($montoGasto, 2) != 0) {
				$insertSQL = sprintf("INSERT INTO cp_factura_gasto (id_factura, id_gasto, tipo, porcentaje_monto, monto, id_iva, iva, id_modo_gasto)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($idGasto, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
					valTpDato($porcMontoGasto, "real_inglesa"),
					valTpDato($montoGasto, "real_inglesa"),
					valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"),
					valTpDato($frmTotalDcto['hddIvaGasto'.$valor], "real_inglesa"),
					valTpDato($rowGastos['id_modo_gasto'], "int")); // 1 = Nacional, 2 = Importacion, 3 = Nacional por Importacion
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarBaseImponible($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// EDITA LOS IVA DE LA FACTURA
	for ($cont = 0; isset($frmTotalDcto['hddIdIva'.$cont]); $cont++) {
		$updateSQL = sprintf("UPDATE cp_factura_iva SET
			base_imponible = %s,
			subtotal_iva = %s
		WHERE id_factura = %s
			AND id_iva = %s;",
			valTpDato($frmTotalDcto['txtBaseImpIva'.$cont], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalIva'.$cont], "real_inglesa"),
			valTpDato($idFactura, "int"),
			valTpDato($frmTotalDcto['hddIdIva'.$cont], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarBaseImponible($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarBaseImponible($objResponse);
	$objResponse->alert("Registro de Compra guardado con éxito.");
	
	$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	
	return $objResponse;
}

function guardarFacturaGasto($frmFacturaGasto, $frmListaGastoImportacion) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_documento_importacion_list","insertar")) { errorGuardarFacturaGasto($objResponse); return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE cp_factura_gasto SET
		id_factura_compra_cargo = %s,
		id_condicion_gasto = %s
	WHERE id_factura_gasto = %s;",
		valTpDato($frmFacturaGasto['txtIdFacturaCargo'], "int"),
		valTpDato(3, "int"), // 1 = Real, 2 = Estimado, 3 = Estimado Asignado
		valTpDato($frmFacturaGasto['hddIdFacturaGasto'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarFacturaGasto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	errorGuardarFacturaGasto($objResponse);
	$objResponse->alert("Registro de Compra asignado con éxito al cargo");
	
	$objResponse->script("
	byId('btnCancelarFacturaGasto').click();");
	
	$objResponse->loadCommands(listaGastoImportacion(
		$frmListaGastoImportacion['pageNum'],
		$frmListaGastoImportacion['campOrd'],
		$frmListaGastoImportacion['tpOrd'],
		$frmListaGastoImportacion['valBusq']));
	
	return $objResponse;
}

function listaGastoImportacion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact_gasto.id_condicion_gasto = 2
	AND cxp_fact_gasto.id_modo_gasto IN (2)
	AND cxp_fact.id_modulo IN (2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxp_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha_origen BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxp_fact.numero_factura_proveedor LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxp_fact_gasto.id_factura_gasto,
		cxp_fact.fecha_origen,
		cxp_fact.numero_factura_proveedor,
		prov.nombre AS nombre_proveedor,
		gasto.nombre,
		cxp_fact_gasto.monto
	FROM cp_factura_gasto cxp_fact_gasto
		INNER JOIN pg_gastos gasto ON (cxp_fact_gasto.id_gasto = gasto.id_gasto)
		INNER JOIN cp_factura cxp_fact ON (cxp_fact_gasto.id_factura = cxp_fact.id_factura)
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Reg. Compra");
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "26%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "48%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Gasto");
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "10%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Subtotal Estimado");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_factura_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAsignar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblFacturaGasto', '%s');\"><img class=\"puntero\" src=\"../img/iconos/page_link.png\" title=\"Asignar Dcto.\"/></a>",
					$contFila,
					$row['id_factura_gasto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if (xvalidaAcceso(false,"an_registro_compra_form","insertar")) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGastoImportacion', '%s');\"><img class=\"puntero\" src=\"../img/iconos/folder_edit.png\" title=\"Editar Gastos Nacionales de Importación\"/></a>",
					$contFila,
					$row['id_factura_gasto']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('an_documento_importacion_form.php?id=%s','_self');\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/>",
					$row['id_factura_gasto']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaGastoImportacion","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listaFacturaCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_factura NOT IN (SELECT id_factura_compra_cargo
															FROM cp_factura_gasto cxp_fact_gasto
																INNER JOIN cp_factura cxp_fact ON (cxp_fact_gasto.id_factura = cxp_fact.id_factura)
															WHERE cxp_fact.activa IS NOT NULL
																AND id_factura_compra_cargo IS NOT NULL)");
	
	if ($valCadBusq[0] != "" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_fact.fecha_origen BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[0])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_fact.numero_control_factura LIKE %s
		OR cxp_fact.numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxp_fact.id_factura,
		cxp_fact.fecha_origen,
		cxp_fact.fecha_factura_proveedor,
		cxp_fact.numero_factura_proveedor,
		prov.nombre AS nombre_proveedor,
		
		(CASE id_modulo
			WHEN 2 THEN
				(SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
				+
				(SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura)
			ELSE
				(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
				WHERE cxp_fact_det.id_factura = cxp_fact.id_factura)
		END) AS cant_items,
		
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto IN (1)
						AND cxp_fact_gasto.afecta_documento = 1), 0)
			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva cxp_fact_iva
					WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total,
		
		moneda_local.abreviacion AS abreviacion_moneda,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = cxp_fact.id_factura
		LIMIT 1) AS idRetencionCabezera
	FROM cp_factura cxp_fact
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Compra");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "58%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "6%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "12%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarFacturaCompra%s\" onclick=\"xajax_asignarFacturaGasto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_factura']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cant_items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")." ".$row['abreviacion_moneda']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../compras/reportes/ga_registro_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro Compra PDF\"/>",
					$row['id_factura']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Comprobante de Retención\"/>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";

			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaRegistroCompra","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"buscarGastoImportacion");
$xajax->register(XAJAX_FUNCTION,"buscarRegistroCompra");
$xajax->register(XAJAX_FUNCTION,"calcularBaseImponible");
$xajax->register(XAJAX_FUNCTION,"cargarGastoImportacion");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"frmFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"guardarBaseImponible");
$xajax->register(XAJAX_FUNCTION,"guardarFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"listaGastoImportacion");
$xajax->register(XAJAX_FUNCTION,"listaFacturaCompra");

function errorGuardarBaseImponible($objResponse) {
	$objResponse->script("
	byId('btnGuardarTotalDcto').disabled = false;
	byId('btnCancelarTotalDcto').disabled = false;");
}

function errorGuardarFacturaGasto($objResponse) {
	$objResponse->script("
	byId('btnGuardarFacturaGasto').disabled = false;
	byId('btnCancelarFacturaGasto').disabled = false;");
}
?>