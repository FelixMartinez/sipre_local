<?php
function asignarPorcentajeTarjetaCredito($idCuenta, $idTarjeta) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto
	WHERE id_cuenta = %s
		AND id_tipo_tarjeta = %s",
		valTpDato($idCuenta, "int"),
		valTpDato($idTarjeta, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("porcentajeRetencion","value",$row['porcentaje_islr']);
	$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
	
	$objResponse->script("calcularPorcentajeTarjetaCredito();");
	
	return $objResponse;
}

function buscarAnticipoNotaCreditoChequeTransferencia($frmBuscarAnticipoNotaCreditoChequeTransferencia, $frmDcto, $frmDetallePago, $frmListaPagos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	if (isset($arrayObj2)) {
		foreach($arrayObj2 as $indicePago => $valorPago) {
			if ($frmListaPagos['txtIdFormaPago'.$valorPago] == $frmDetallePago['selTipoPago']) {
				$arrayIdDocumento[] = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
			}
		}
	}
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarAnticipoNotaCreditoChequeTransferencia['txtCriterioAnticipoNotaCreditoChequeTransferencia'],
		$frmDcto['txtIdCliente'],
		$frmDetallePago['selTipoPago'],
		(($arrayIdDocumento) ? implode(",",$arrayIdDocumento) : ""));
		
	$objResponse->loadCommands(listaAnticipoNotaCreditoChequeTransferencia(0,"","",$valBusq));
	
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	for ($cont = 0; isset($frmTotalDcto['txtIva'.$cont]); $cont++) {
		$objResponse->script("
		fila = document.getElementById('trIva:".$cont."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaArticulo['cbx']) && isset($frmTotalDcto['cbx'])) {
		$arrayObj = array_merge($frmListaArticulo['cbx'], $frmTotalDcto['cbx']);
	} else if (isset($frmListaArticulo['cbx'])) {
		$arrayObj = $frmListaArticulo['cbx'];
	} else if (isset($frmTotalDcto['cbx'])) {
		$arrayObj = $frmTotalDcto['cbx'];
	}
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm => $valorItm) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valorItm,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valorItm,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
			
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmListaArticulo['cbx1'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdFactura'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas moneda
	WHERE moneda.estatus = 1
		AND moneda.predeterminada = 1;");
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (isset($frmListaArticulo['cbx']) && in_array($valor, $frmListaArticulo['cbx'])) { // VERIFICA SI EL ITEM ESTA EN EL DETALLE
				$frmListaArticuloAux = $frmListaArticulo;
			} else if (isset($frmTotalDcto['cbx']) && in_array($valor, $frmTotalDcto['cbx'])) { // VERIFICA SI EL ITEM ESTA EN LOS OTROS ADICIONALES
				$frmListaArticuloAux = $frmTotalDcto;
			}
			
			$txtCantRecibItm = 1;
			$txtPrecioItm = str_replace(",", "", $frmListaArticuloAux['txtPrecioItm'.$valor]);
			$txtCostoItm = str_replace(",", "", $frmListaArticuloAux['txtCostoItm'.$valor]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticuloAux['hddMontoDescuentoItm'.$valor]);
			$txtTotalItm = $txtCantRecibItm * $txtPrecioItm;
			
			if ((in_array($frmListaArticuloAux['hddTpItm'.$valor], array(1,2)) && in_array($frmListaArticuloAux['hddTipoAccesorioItm'.$valor], array(1)))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valor], array(3))) {
				$txtSubTotal += $txtTotalItm;
				$subTotalDescuentoItm += $txtCantRecibItm * $hddMontoDescuentoItm;
			} else if (in_array($frmListaArticuloAux['hddTpItm'.$valor], array(1,2)) && in_array($frmListaArticuloAux['hddTipoAccesorioItm'.$valor], array(2,3))) {
				$txtTotalAdicionalOtro += $txtTotalItm;
			}
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		$txtDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IVA Y EL SUBTOTAL
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (isset($frmListaArticulo['cbx']) && in_array($valor, $frmListaArticulo['cbx'])) { // VERIFICA SI EL ITEM ESTA EN EL DETALLE
				$frmListaArticuloAux = $frmListaArticulo;
			} else if (isset($frmTotalDcto['cbx']) && in_array($valor, $frmTotalDcto['cbx'])) { // VERIFICA SI EL ITEM ESTA EN LOS OTROS ADICIONALES
				$frmListaArticuloAux = $frmTotalDcto;
			}
			
			$txtCantRecibItm = 1;
			$txtPrecioItm = str_replace(",", "", $frmListaArticuloAux['txtPrecioItm'.$valor]);
			$txtCostoItm = str_replace(",", "", $frmListaArticuloAux['txtCostoItm'.$valor]);
			$txtTotalItm = $txtCantRecibItm * $txtPrecioItm;
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticuloAux['hddTotalDescuentoItm'.$valor]);
			
			$hddTotalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $hddTotalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObj1)) {
				foreach ($arrayObj1 as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
						$arrayPosIvaItm[$frmListaArticuloAux['hddIdIvaItm'.$valor.':'.$valor1[1]]] = $valor1[1];
						$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
					}
				}
			}
			
			if ((in_array($frmListaArticuloAux['hddTpItm'.$valor], array(1,2)) && in_array($frmListaArticuloAux['hddTipoAccesorioItm'.$valor], array(1)))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valor], array(3))) {
				// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE idIva IN (%s);", 
					valTpDato(implode(",", $arrayIdIvaItm), "campo"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$queryIva);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticuloAux['hddEstatusIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
					
					if ($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
						$txtTotalExento += $txtTotalNetoItm;
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ?  str_replace(",", "", $frmListaArticuloAux['hddIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
						$subTotalIvaItm = ($txtTotalNetoItm * $porcIva) / 100;
						
						$existIva = false;
						if (isset($arrayIva)) {
							foreach ($arrayIva as $indiceIva => $valorIva) {
								if ($arrayIva[$indiceIva][0] == $idIva) {
									$arrayIva[$indiceIva][1] += $txtTotalNetoItm;
									$arrayIva[$indiceIva][2] += $subTotalIvaItm;
									$existIva = true;
								}
							}
						}
						
						if ($idIva > 0 && $existIva == false
						&& ($txtTotalItm - $hddTotalDescuentoItm) > 0) {
							$arrayIva[] = array(
								$idIva,
								$txtTotalNetoItm,
								$subTotalIvaItm,
								$porcIva,
								$lujoIva,
								$rowIva['observacion']);
						}
					}
				}
				
				if ($totalRowsIva == 0) {
					$txtTotalExento += $txtTotalNetoItm;
				}
			}
			
			$objResponse->assign("txtTotalItm".$valor, "value", number_format($txtTotalItm, 2, ".", ","));
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIva)) {
		foreach($arrayIva as $indiceIva => $valorIva) {
			if ($arrayIva[$indiceIva][2] > 0) {				
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
							"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
							"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIva:%s');
					if (obj == undefined)
						$('#trGastosSinIva').before(elemento);", 
					$indiceIva, 
						$indiceIva, utf8_encode($arrayIva[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][0], 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][4], 
							$indiceIva,
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][1], 2), 2, ".", ","), 
						$indiceIva, $indiceIva, $arrayIva[$indiceIva][3], "%", 
						$abrevMonedaLocal, 
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][2], 2), 2, ".", ","), 
					
					$indiceIva));
			}
			
			$subTotalIva += doubleval($arrayIva[$indiceIva][2]);
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIvaLocal)) {
		foreach ($arrayIvaLocal as $indiceIva => $valorIva) {
			if ($arrayIvaLocal[$indiceIva][2] > 0) {
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIvaLocal:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIvaLocal:%s\">%s:".
							"<input type=\"hidden\" id=\"hddIdIvaLocal%s\" name=\"hddIdIvaLocal%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIvaLocal%s\" name=\"hddLujoIvaLocal%s\" value=\"%s\"/>".
							"<input type=\"checkbox\" id=\"cbxIvaLocal\" name=\"cbxIvaLocal[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIvaLocal%s\" name=\"txtBaseImpIvaLocal%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIvaLocal%s\" name=\"txtIvaLocal%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIvaLocal%s\" name=\"txtSubTotalIvaLocal%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIvaLocal:%s');
					if (obj == undefined)
						$('#trRetencionIva').before(elemento);", 
					$indiceIva, 
						$indiceIva, utf8_encode($arrayIvaLocal[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][0], 
							$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][4], 
							$indiceIva,
						$indiceIva, $indiceIva, number_format(round($arrayIvaLocal[$indiceIva][1], 2), 2, ".", ","), 
						$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][3], "%", 
						$abrevMonedaLocal, 
						$indiceIva, $indiceIva, number_format(round($arrayIvaLocal[$indiceIva][2], 2), 2, ".", ","), 
						
					$indiceIva));
			}
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = true;
			byId('txtDescuento').className = 'inputInicial';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = true;
			byId('txtSubTotalDescuento').className = 'inputInicial';");
		}
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = false;
			byId('txtDescuento').className = 'inputHabilitado';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = false;
			byId('txtSubTotalDescuento').className = 'inputHabilitado';");
		}
	}
	$txtDescuento = ($txtDescuento > 0) ? $txtDescuento : 0;
	$txtSubTotalDescuento = ($txtSubTotalDescuento > 0) ? $txtSubTotalDescuento : 0;
	
	$txtTotalOrden = doubleval($txtSubTotal) - doubleval($txtSubTotalDescuento);
	$txtTotalOrden += doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva);
	
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento", "value", number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtTotalAdicionalOtro", "value", number_format($txtTotalAdicionalOtro, 2, ".", ","));
	
	$objResponse->assign("txtGastosConIva", "value", number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign("txtGastosSinIva", "value", number_format($txtGastosSinIva, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalFactura","value",number_format($txtTotalOrden, 2, ".", ","));
	$objResponse->assign("txtMontoPorPagar","value",number_format($txtTotalOrden, 2, ".", ","));
	
	return $objResponse;
}

function calcularPagos($frmListaPagos, $frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	if (isset($arrayObj2)) {
		$i = 0;
		foreach ($arrayObj2 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmPago:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmPago:".$valor,"innerHTML",$i);
			
			$txtMontoPagadoFactura += str_replace(",", "", $frmListaPagos['txtMonto'.$valor]);
		}
	}
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObj2) > 0) ? implode("|",$arrayObj2) : ""));
	
	$objResponse->assign("txtMontoPagadoFactura","value",number_format($txtMontoPagadoFactura, 2, ".", ","));
	$objResponse->assign("txtMontoPorPagar","value",number_format(str_replace(",", "", $frmTotalDcto['txtTotalOrden']) - $txtMontoPagadoFactura, 2, ".", ","));
	
	return $objResponse;
}

function calcularPagosDeposito($frmDeposito, $frmDetallePago) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	if (isset($arrayObj3)) {
		$i = 0;
		foreach ($arrayObj3 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDetalle:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDetalle:".$valor,"innerHTML",$i);
			
			$txtMontoPagadoDeposito += str_replace(",", "", $frmDeposito['txtMontoDetalleDeposito'.$valor]);
		}
	}
	$objResponse->assign("hddObjDetallePagoDeposito","value",((count($arrayObj3) > 0) ? implode("|",$arrayObj3) : ""));
	
	$objResponse->assign("txtTotalDeposito","value",number_format($txtMontoPagadoDeposito, 2, ".", ","));
	$objResponse->assign("txtSaldoDepositoBancario","value",number_format(str_replace(",", "", $frmDetallePago['txtMontoPago']) - $txtMontoPagadoDeposito, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstBancoCliente($nombreObjeto, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$query = sprintf("SELECT idBanco, nombreBanco FROM bancos WHERE idBanco <> 1 ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected."  value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstBancoCompania($tipoPago = "", $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_cargaLstCuentaCompania(this.value,".$tipoPago.");\"";
	
	$query = sprintf("SELECT idBanco, (SELECT nombreBanco FROM bancos WHERE bancos.idBanco = cuentas.idBanco) AS banco FROM cuentas GROUP BY cuentas.idBanco ORDER BY banco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select name=\"selBancoCompania\" id=\"selBancoCompania\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstCuentaCompania($row['idBanco'], $tipoPago)); }
		
		$html .= "<option ".$selected."  value=\"".$row['idBanco']."\">".utf8_encode($row['banco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselBancoCompania","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCreditoTradeIn($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$array = array("0" => "Crédito Negativo", "1" => "Crédito Positivo");
	$totalRows = count($array);
	
	$html .= "<select id=\"lstCreditoTradeIn\" name=\"lstCreditoTradeIn\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCreditoTradeIn","innerHTML", $html);
	
	return $objResponse;
}

function cargaLstCuentaCompania($idBanco, $tipoPago, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_cargaLstTarjetaCuenta(this.value,".$tipoPago.");\"";
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas
	WHERE idBanco = %s
		AND estatus = 1",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select name=\"selNumeroCuenta\" id=\"selNumeroCuenta\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idCuentas'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstTarjetaCuenta($row['idCuentas'], $tipoPago)); }
		
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".utf8_encode($row['numeroCuentaCompania'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("divselNumeroCuenta","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s)", valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTarjetaCuenta($idCuenta, $tipoPago, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 5) { // Tarjeta de Crédito
		$query = sprintf("SELECT idTipoTarjetaCredito, descripcionTipoTarjetaCredito FROM tipotarjetacredito 
		WHERE idTipoTarjetaCredito IN (SELECT id_tipo_tarjeta FROM te_retencion_punto
										WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta NOT IN (6))
		ORDER BY descripcionTipoTarjetaCredito",
			valTpDato($idCuenta, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select id=\"tarjeta\" name=\"tarjeta\" class=\"inputHabilitado\" onchange=\"xajax_asignarPorcentajeTarjetaCredito(".$idCuenta.",this.value)\" style=\"width:200px\">";
			$html .= "<option value=\"\">[ Seleccione ]</option>";
		while($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row['idTipoTarjetaCredito'] || $totalRows == 1) ? "selected=\"selected\"" : "";
			if ($totalRows == 1) { $objResponse->loadCommands(asignarPorcentajeTarjetaCredito($idCuenta, $row['idTipoTarjetaCredito'])); }
			
			$html .= "<option ".$selected." value=\"".$row['idTipoTarjetaCredito']."\">".$row['descripcionTipoTarjetaCredito']."</option>";
		}
		$html .= "</select>";
		$objResponse->assign("tdtarjeta","innerHTML",$html);
	} else if ($tipoPago == 6) { // Tarjeta de Debito
		$query = sprintf("SELECT porcentaje_comision FROM te_retencion_punto WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta IN (6);",
			valTpDato($idCuenta,'int'));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
	}
	
	return $objResponse;
}

function cargaLstTipoPago($idFormaPago = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$idFormaPago = (is_array($idFormaPago)) ? implode(",",$idFormaPago) : $idFormaPago;
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia Bancaria, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito
	// 9 = Retención, 10 = Retencion I.S.L.R., 11 = Otro
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idFormaPago NOT IN (11)");
	
	if ($idFormaPago != "-1" && $idFormaPago != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idFormaPago IN (%s)",
			valTpDato($idFormaPago, "campo"));
	}
	
	$query = sprintf("SELECT * FROM formapagos %s ORDER BY nombreFormaPago ASC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"selTipoPago\" name=\"selTipoPago\" class=\"inputHabilitado\" onchange=\"asignarTipoPago(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idFormaPago'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPago($row['idFormaPago'])); }
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPago']."\">".$row['nombreFormaPago']."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoPagoDetalleDeposito($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM formapagos where idFormaPago <= 2");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select name=\"lstTipoPago\" id=\"lstTipoPago\" class=\"inputHabilitado\" onchange=\"asignarTipoPagoDetalleDeposito(this.value)\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idFormaPago'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPagoDetalleDeposito($row['idFormaPago'])); }
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPago']."\">".$row['nombreFormaPago']."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarSaldoDocumento($formaPago, $idDocumento, $frmListaPagos) {
	$objResponse = new xajaxResponse();
	
	if ($formaPago == 2) { // CHEQUES
		$documento = "Cheque";
		
		$query = sprintf("SELECT saldo_cheque AS saldoDocumento, numero_cheque AS numeroDocumento
		FROM cj_cc_cheque WHERE id_cheque = %s", $idDocumento);
	} else if ($formaPago == 4) { // TRANSFERENCIAS
		$documento = "Transferencia";
		
		$query = sprintf("SELECT saldo_transferencia AS saldoDocumento, numero_transferencia AS numeroDocumento
		FROM cj_cc_transferencia WHERE id_transferencia = %s", $idDocumento);
	} else if ($formaPago == 7) { // ANTICIPOS
		$documento = "Anticipo";
		
		$query = sprintf("SELECT
			saldoAnticipo AS saldoDocumento,
			numeroAnticipo AS numeroDocumento
		FROM cj_cc_anticipo
		WHERE idAnticipo = %s;",
			valTpDato($idDocumento, "int"));
	} else if ($formaPago == 8) { // NOTAS DE CREDITO
		$documento = "Nota de Crédito";
		
		$query = sprintf("SELECT
			saldoNotaCredito AS saldoDocumento,
			numeracion_nota_credito AS numeroDocumento
		FROM cj_cc_notacredito
		WHERE idNotaCredito = %s;",
			valTpDato($idDocumento, "int"));
	}
	$rsSelectDocumento = mysql_query($query);
	if (!$rsSelectDocumento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowSelectDocumento = mysql_fetch_array($rsSelectDocumento);
	
	$objResponse->assign("hddIdDocumento","value",$idDocumento);
	$objResponse->assign("txtNroDocumento","value",$rowSelectDocumento['numeroDocumento']);
	$objResponse->assign("txtSaldoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'], 2, ".", ","));
	$objResponse->assign("txtMontoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'], 2, ".", ","));
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML",$documento);
	
	$objResponse->script("
	byId('txtMontoDocumento').focus();
	byId('txtMontoDocumento').select();");
		
	return $objResponse;
}

function eliminarDetalleDeposito($pos, $frmDetallePago) {
	$objResponse = new xajaxResponse();
	
	$arrayPosiciones = explode("|",$frmDetallePago['hddObjDetalleDeposito']);
	$arrayFormaPago = explode("|",$frmDetallePago['hddObjDetalleDepositoFormaPago']);
	$arrayBanco = explode("|",$frmDetallePago['hddObjDetalleDepositoBanco']);
	$arrayNroCuenta = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCuenta']);
	$arrayNroCheque = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCheque']);
	$arrayMonto = explode("|",$frmDetallePago['hddObjDetalleDepositoMonto']);
	
	$cadenaPosiciones = "";
	$cadenaFormaPago = "";
	$cadenaBanco = "";
	$cadenaNroCuenta = "";
	$cadenaNroCheque = "";
	$cadenaMonto = "";
	
	foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
		if ($valorDeposito != $pos && $valorDeposito != '') {
			$cadenaPosiciones .= $valorDeposito."|";
			$cadenaFormaPago .= $arrayFormaPago[$indiceDeposito]."|";
			$cadenaBanco .= $arrayBanco[$indiceDeposito]."|";
			$cadenaNroCuenta .= $arrayNroCuenta[$indiceDeposito]."|";
			$cadenaNroCheque .= $arrayNroCheque[$indiceDeposito]."|";
			$cadenaMonto .= $arrayMonto[$indiceDeposito]."|";
		}
	}
	
	$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosiciones);
	$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPago);
	$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBanco);
	$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuenta);
	$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroCheque);
	$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMonto);
	
	return $objResponse;
}

function eliminarPago($frmListaPagos, $pos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	$idDocumento = $frmListaPagos['txtIdNumeroDctoPago'.$pos];
	
	if ($frmListaPagos['txtIdFormaPago'.$pos] == 3) { // 3 = Deposito
		$objResponse->script("xajax_eliminarDetalleDeposito(".$pos.",xajax.getFormValues('frmDetallePago'))");
	} else if ($frmListaPagos['txtIdFormaPago'.$pos] == 7) { // 7 = Anticipo
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_anticipo = %s;",
			valTpDato($idDocumento, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			if (isset($arrayObj2)) {
				foreach ($arrayObj2 as $indice => $valor) {
					if ($frmListaPagos['txtIdFormaPago'.$valor] == 8 && $frmListaPagos['txtIdNumeroDctoPago'.$valor] == $rowTradeInNotaCredito['id_nota_credito_cxc']) {
						$objResponse->script("xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),'".$valor."');");
					}
				}
			}
		}
	} else if ($frmListaPagos['txtIdFormaPago'.$pos] == 8) { // 8 = Nota de Crédito
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_nota_credito_cxc = %s;",
			valTpDato($idDocumento, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			if (isset($arrayObj2)) {
				foreach ($arrayObj2 as $indice => $valor) {
					if ($frmListaPagos['txtIdFormaPago'.$valor] == 7 && $frmListaPagos['txtIdNumeroDctoPago'.$valor] == $rowTradeInNotaCredito['id_anticipo']) {
						$objResponse->script("xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),'".$valor."');");
					}
				}
			}
		}
	}
	
	$objResponse->script("
	fila = document.getElementById('trItmPago:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'))");
	
	return $objResponse;
}

function eliminarPagoDetalleDeposito($frmDeposito, $pos) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	fila = document.getElementById('trItmDetalle:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
			
	$montoEliminado = $frmDeposito['txtMontoDetalleDeposito'.$pos];
	
	$objResponse->script("xajax_calcularPagosDeposito(xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmDetallePago'))");
	
	return $objResponse;
}

function formDeposito($frmDeposito) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj3)) {
		foreach($arrayObj3 as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmDetalle:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
				
	$objResponse->loadCommands(cargaLstTipoPagoDetalleDeposito());
	$objResponse->loadCommands(cargaLstBancoCliente("lstBancoDeposito"));
	
	$objResponse->script("
	byId('txtSaldoDepositoBancario').value = byId('txtMontoPago').value;
	byId('txtTotalDeposito').value = '0.00';");
	
	return $objResponse;
}

function formItemsPedido($idPedido){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		ped_vent.id_pedido,
		ped_vent.id_empresa,
		ped_vent.numeracion_pedido,
		ped_vent.id_factura_cxc,
		uni_fis.id_unidad_fisica,
		CONCAT(uni_bas.nom_uni_bas, ': ', marca.nom_marca, ' ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
		uni_fis.placa,
		ped_vent.precio_venta,
		uni_fis.estado_venta,
		ped_vent.estado_pedido
	FROM an_pedido ped_vent
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		LEFT JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
		LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		LEFT JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		LEFT JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	WHERE ped_vent.id_pedido = %s
		AND ((SELECT COUNT(acc_ped.id_pedido) FROM an_accesorio_pedido acc_ped
				WHERE acc_ped.id_pedido = ped_vent.id_pedido AND acc_ped.estatus_accesorio_pedido = 0) > 0
			OR (SELECT COUNT(paq_ped.id_pedido) FROM an_paquete_pedido paq_ped
				WHERE paq_ped.id_pedido = ped_vent.id_pedido AND paq_ped.estatus_paquete_pedido = 0) > 0
			OR uni_fis.estado_venta LIKE 'RESERVADO')
		AND ped_vent.estado_pedido IN (1,2)",
		valTpDato($idPedido, "int"));
	$rsPedido = mysql_query($query);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPedido = mysql_num_rows($rsPedido);
	$rowPedido = mysql_fetch_array($rsPedido);
	
	$idEmpresa = $rowPedido['id_empresa'];
	
	if (!($totalRowsPedido > 0)) {
		$objResponse->alert("Este Pedido no puede ser Facturado");
		return $objResponse->script("window.location.href='cj_factura_venta_list.php';");
	}
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
	
	$objResponse->assign("txtIdPedidoItems", "value", $rowPedido['id_pedido']);
	$objResponse->assign("txtNumeroPedidoItems", "value", $rowPedido['numeracion_pedido']);
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"75%\">Descripción</td>";
		$htmlTh .= "<td width=\"25%\">Total</td>";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,'frmListaItemPedido');\"/></td>";
	$htmlTh .= "</tr>";
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td>".utf8_encode($rowPedido['vehiculo']." (".$rowPedido['placa'].")")."</td>";
		$htmlTb .= "<td align=\"right\">".number_format($rowPedido['precio_venta'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>";
		if ($rowPedido['estado_venta'] == "RESERVADO" || $rowPedido['id_factura_cxc'] > 0) {
			$htmlTb .= sprintf("
			<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" value=\"%s\"/>
			<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" readonly=\"readonly\" value=\"%s\"/>
			<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" readonly=\"readonly\" value=\"%s\"/>",
				$contFila,
				$contFila, $contFila,
				$rowPedido['id_unidad_fisica'],
				$contFila, $contFila,
				3);
		}
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	$queryAccesorioPaquete = sprintf("SELECT 
		paq_ped.id_paquete_pedido,
		(CASE paq_ped.iva_accesorio
			WHEN 0 THEN
				acc.nom_accesorio
			ELSE
				CONCAT(acc.nom_accesorio, ' (E)')
		END) AS nom_accesorio,
		paq_ped.id_tipo_accesorio,
		(CASE paq_ped.id_tipo_accesorio
			WHEN 1 THEN 'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
		END) AS descripcion_tipo_accesorio,
		paq_ped.precio_accesorio,
		paq_ped.costo_accesorio,
		paq_ped.iva_accesorio,
		paq_ped.porcentaje_iva_accesorio,
		paq_ped.id_condicion_pago,
		paq_ped.estatus_paquete_pedido
	FROM an_acc_paq acc_paq
		INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
		INNER JOIN an_paquete_pedido paq_ped ON (acc_paq.Id_acc_paq = paq_ped.id_acc_paq)
	WHERE paq_ped.id_pedido = %s
	ORDER BY paq_ped.id_tipo_accesorio, paq_ped.id_paquete_pedido",
		valTpDato($idPedido, "int"));
	$rsAccesorioPaquete = mysql_query($queryAccesorioPaquete);
	if (!$rsAccesorioPaquete) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while($rowAccesorioPaquete = mysql_fetch_array($rsAccesorioPaquete)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$checked = ($rowAccesorioPaquete['id_tipo_accesorio'] == 3) ? "checked=\"checked\" readonly=\"readonly\" style=\"display:none\"" : "";
		$onchange = ($rowAccesorioPaquete['id_tipo_accesorio'] == 3) ? "onclick=\"this.checked=true\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= utf8_encode($rowAccesorioPaquete['nom_accesorio']);
				$htmlTb .= ($rowAccesorioPaquete['id_condicion_pago'] == 1) ? " <span class=\"textoVerdeNegrita\">[ Pagado ]</span>": "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowAccesorioPaquete['precio_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($rowAccesorioPaquete['estatus_paquete_pedido'] == "0") {
				$htmlTb .= sprintf("
				<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" ".$checked." ".$onchange." value=\"%s\"/>
				<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" readonly=\"readonly\" value=\"%s\"/>
				<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila, $contFila,
					$rowAccesorioPaquete['id_paquete_pedido'],
					$contFila, $contFila,
					1);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$queryAccesorio = sprintf("SELECT 
		acc_ped.id_accesorio_pedido,
		(CASE acc_ped.iva_accesorio
			WHEN 1 THEN
				acc.nom_accesorio
			ELSE
				CONCAT(acc.nom_accesorio, ' (E)')
		END) AS nom_accesorio,
		acc_ped.id_tipo_accesorio,
		(CASE acc_ped.id_tipo_accesorio
			WHEN 1 THEN 'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
		END) AS descripcion_tipo_accesorio,
		acc_ped.precio_accesorio,
		acc_ped.costo_accesorio,
		acc_ped.iva_accesorio,
		acc_ped.porcentaje_iva_accesorio,
		acc_ped.id_condicion_pago,
		acc_ped.estatus_accesorio_pedido
	FROM an_accesorio acc
		INNER JOIN an_accesorio_pedido acc_ped ON (acc.id_accesorio = acc_ped.id_accesorio)
	WHERE acc_ped.id_pedido = %s
	ORDER BY acc_ped.id_tipo_accesorio, acc_ped.id_accesorio_pedido",
		valTpDato($idPedido, "int"));
	$rsAccesorio = mysql_query($queryAccesorio);
	if (!$rsAccesorio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowAccesorio = mysql_fetch_array($rsAccesorio)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$checked = ($rowAccesorio['id_tipo_accesorio'] == 3) ? "checked=\"checked\" readonly=\"readonly\" style=\"display:none\"" : "";
		$onchange = ($rowAccesorio['id_tipo_accesorio'] == 3) ? "onclick=\"this.checked=true\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"80%\">";
						$htmlTb .= utf8_encode($rowAccesorio['nom_accesorio']);
						$htmlTb .= ($rowAccesorio['id_condicion_pago'] == 1) ? " <span class=\"textoVerdeNegrita\">[ Pagado ]</span>": "";
					$htmlTb .= "</td>";
					$htmlTb .= "<td width=\"20%\">".utf8_encode($rowAccesorio['descripcion_tipo_accesorio'])."</td>";
					$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowAccesorio['precio_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($rowAccesorio['estatus_accesorio_pedido'] == "0") {
				$htmlTb .= sprintf("
				<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" ".$checked." ".$onchange." value=\"%s\"/>
				<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" readonly=\"readonly\" value=\"%s\"/>
				<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila, $contFila,
					$rowAccesorio['id_accesorio_pedido'],
					$contFila, $contFila,
					2);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaItemsPedido","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $frmDetallePago, $frmListaPagos){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cj_factura_venta_list","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaLocal = $frmTotalDcto['cbxIvaLocal'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaArticulo['cbx']) && isset($frmTotalDcto['cbx'])) {
		$arrayObj = array_merge($frmListaArticulo['cbx'], $frmTotalDcto['cbx']);
	} else if (isset($frmListaArticulo['cbx'])) {
		$arrayObj = $frmListaArticulo['cbx'];
	} else if (isset($frmTotalDcto['cbx'])) {
		$arrayObj = $frmTotalDcto['cbx'];
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmListaArticulo['cbx1'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	$queryPedido = sprintf("SELECT 
		ped_vent.id_empresa,
		uni_fis.id_unidad_fisica,
		uni_bas.id_uni_bas
	FROM an_uni_bas uni_bas
		INNER JOIN an_unidad_fisica uni_fis ON (uni_bas.id_uni_bas = uni_fis.id_uni_bas)
		RIGHT JOIN an_pedido ped_vent ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica)
	WHERE ped_vent.id_pedido = %s",
		valTpDato($frmDcto['txtIdPedido'], "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsPedido = mysql_num_rows($rsPedido);
	$rowPedido = mysql_fetch_array($rsPedido);
	
	$idEmpresa = $rowPedido['id_empresa'];
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idUnidadFisica = $rowPedido['id_unidad_fisica'];
	$idUnidadBasica = $rowPedido['id_uni_bas'];
	$idEmpleadoAsesor = $frmDcto['hddIdEmpleado'];
	$idClaveMovimiento = $frmDcto['hddIdClaveMovimiento'];
	$idTipoPago = $frmDcto['hddTipoPago'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	mysql_query("START TRANSACTION;");
	
	// VERIFICA SI EL DOCUMENTO YA HA SIDO FACTURADO
	$queryVerif = sprintf("SELECT * FROM cj_cc_encabezadofactura
	WHERE numeroPedido = %s
		AND idDepartamentoOrigenFactura IN (%s)
		AND subtotalFactura = %s;",
		valTpDato($idPedido, "int"),
		valTpDato($idModulo, "int"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"));
	$rsVerif = mysql_query($queryVerif);
	if (!$rsVerif) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if (mysql_num_rows($rsVerif) > 0) {
		return $objResponse->alert('Este documento ya ha sido facturado');
	}
	
	// VERIFICA QUE EL DOOCUMENTO A CONTADO ESTE CANCELADO EN TU TOTALIDAD
	if ($idTipoPago == 1) { // 0 = Credito, 1 = Contado
		if ($frmListaPagos['txtMontoPorPagar'] != 0) {
			return $objResponse->alert('Debe cancelar el monto total de la factura');
		}
	}
	
	$baseImponibleIva = 0;
	$porcIva = 0;
	$subTotalIva = 0;
	$baseImponibleIvaLujo = 0;
	$porcIvaLujo = 0;
	$subTotalIvaLujo = 0;
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			switch ($frmTotalDcto['hddLujoIva'.$valor]) {
				case 0 :
					$baseImponibleIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
					$porcIva += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
					$subTotalIva += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
					break;
				case 1 :
					$baseImponibleIvaLujo = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
					$porcIvaLujo += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
					$subTotalIvaLujo += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
					break;
			}
		}
	}
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$numeroActualFactura = $numeroActual;
	
	if ($frmDcto['txtIdFacturaEditada'] > 0) {
		// BUSCA LOS DATOS DE LA FACTURA
		$queryFact = sprintf("SELECT cxc_fact.*,
			(SELECT clave_mov.id_clave_movimiento_contra FROM pg_clave_movimiento clave_mov
			WHERE clave_mov.id_clave_movimiento = cxc_fact.id_clave_movimiento) AS id_clave_movimiento_contra
		FROM cj_cc_encabezadofactura cxc_fact
		WHERE idFactura = %s;",
			valTpDato($frmDcto['txtIdFacturaEditada'], "int"));
		$rsFact = mysql_query($queryFact);
		if (!$rsFact) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowFact = mysql_fetch_array($rsFact);
		
		$numeroActual = $rowFact['numeroFactura'];
		$frmDcto['txtNumeroControlFactura'] = $rowFact['numeroControl'];
		$frmDcto['txtFechaFactura'] = $rowFact['fechaRegistroFactura'];
		$frmDcto['txtFechaVencimientoFactura'] = $rowFact['fechaVencimientoFactura'];
		
		// BUSCA EL MOVIMIENTO DE LA UNIDAD
		$queryKardex = sprintf("SELECT DATE_ADD(fechaMovimiento, INTERVAL 2 SECOND) AS fechaMovimiento FROM an_kardex kardex
		WHERE kardex.id_documento = %s
			AND kardex.tipoMovimiento = 3;",
			valTpDato($frmDcto['txtIdFacturaEditada'], "int"));
		$rsKardex = mysql_query($queryKardex);
		if (!$rsKardex) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowKardex = mysql_fetch_array($rsKardex);
		
		$fechaMovimiento = $rowKardex['fechaMovimiento'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			numeroFactura = CONCAT(numeroFactura,'(',((SELECT COUNT(an_fact.idFactura) FROM an_factura_venta an_fact
														WHERE an_fact.id_empresa = cxc_fact.id_empresa
															AND an_fact.numeroFactura LIKE CONCAT(cxc_fact.numeroFactura,'%s'))),')'),
			numeroControl = CONCAT(numeroControl,'(',((SELECT COUNT(an_fact.idFactura) FROM an_factura_venta an_fact
														WHERE an_fact.id_empresa = cxc_fact.id_empresa
															AND an_fact.numeroControl LIKE CONCAT(cxc_fact.numeroControl,'%s'))),')'),
			anulada = 'SI',
			aplicaLibros = 0,
			id_empleado_anulacion = %s,
			fecha_anulacion = %s
		WHERE cxc_fact.idFactura = %s;",
			valTpDato("%", "campo"),
			valTpDato("%", "campo"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato("NOW()", "campo"),
			valTpDato($frmDcto['txtIdFacturaEditada'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE an_factura_venta an_fact SET
			numeroFactura = (SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idFactura = an_fact.idFactura),
			numeroControl = (SELECT cxc_fact.numeroControl FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idFactura = an_fact.idFactura),
			anulada = 'SI'
		WHERE an_fact.idFactura = %s;",
			valTpDato($frmDcto['txtIdFacturaEditada'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$Result1 = guardarNotaCreditoCxC($frmDcto['txtIdFacturaEditada']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$arrayIdDctoContabilidad[] = array(
				$Result1[1],
				$Result1[2],
				"NOTA_CREDITO_CXC");
		}
	}
	
	// INSERTA LOS DATOS DE LA FACTURA
	$insertSQL = sprintf("INSERT INTO cj_cc_encabezadofactura (id_empresa, numeroFactura, numeroControl, fechaRegistroFactura, fechaVencimientoFactura, montoTotalFactura, saldoFactura, estadoFactura, observacionFactura, id_clave_movimiento, idVendedor, numeroSiniestro, fletesFactura, idCliente, numeroPedido, idDepartamentoOrigenFactura, porcentaje_descuento, descuentoFactura, porcentajeIvaFactura, calculoIvaFactura, subtotalFactura, interesesFactura, condicionDePago, porcentajeIvaDeLujoFactura, calculoIvaDeLujoFactura, baseImponible, diasDeCredito, montoExento, montoExonerado, anulada, aplicaLibros, id_empleado_creador, id_credito_tradein) 
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($numeroActual, "text"),
		valTpDato($frmDcto['txtNumeroControlFactura'], "text"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVencimientoFactura'])), "date"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada , 2 = Parcialmente Cancelada
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpleadoAsesor, "int"),
		valTpDato(" ", "text"),
		valTpDato(0, "real_inglesa"),
		valTpDato($idCliente, "int"),
		valTpDato($frmDcto['txtIdPedido'], "int"),
		valTpDato($idModulo, "int"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($porcIva, "real_inglesa"),
		valTpDato($subTotalIva, "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($idTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($porcIvaLujo, "real_inglesa"),
		valTpDato($subTotalIvaLujo, "real_inglesa"),
		valTpDato($baseImponibleIva, "real_inglesa"),
		valTpDato($frmDcto['txtDiasCreditoCliente'], "int"),
		valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
		valTpDato("NO", "text"),
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($frmDcto['lstCreditoTradeIn'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idFactura = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idFactura,
		$idModulo,
		"VENTA");
	
	// INSERTA LOS DATOS DE LA FACTURA EN VEHICULOS
	$insertSQL = sprintf("INSERT INTO an_factura_venta (idFactura, id_empresa, numeroControl, numeroPedido, numeroFactura, estadoFactura, fechaRegistroFactura, fechaVencimientoFactura, subtotalFactura, observacionFactura, baseImponible, porcentajeIvaFactura, calculoIvaFactura, montoExonerado, montoNoGravado, porcentajeIvaDeLujoFactura, calculoIvaDeLujoFactura, montoTotalFactura, saldoFactura, idVendedor, idDepartamentoOrigenFactura, condicionDePago, diasDeCredito, anulada, tipo_factura)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($idFactura, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($frmDcto['txtNumeroControlFactura'], "text"),
		valTpDato($frmDcto['txtIdPedido'], "int"),
		valTpDato($numeroActual, "text"),
		valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVencimientoFactura'])), "date"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($baseImponibleIva, "real_inglesa"),
		valTpDato($porcIva, "real_inglesa"),
		valTpDato($subTotalIva, "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
		valTpDato($porcIvaLujo, "real_inglesa"),
		valTpDato($subTotalIvaLujo, "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($idEmpleadoAsesor, "int"),
		valTpDato($idModulo, "int"),
		valTpDato($idTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($frmDcto['txtDiasCreditoCliente'], "int"),
		valTpDato(0, "int"), // 0 = No, 1 = Si
		valTpDato("", "int")); // NULL = Todo, 1 = Vehiculo, 2 = Gastos
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if (isset($frmListaArticulo['cbx']) && in_array($valor, $frmListaArticulo['cbx'])) { // VERIFICA SI EL ITEM ESTA EN EL DETALLE
				$frmListaArticuloAux = $frmListaArticulo;
			} else if (isset($frmTotalDcto['cbx']) && in_array($valor, $frmTotalDcto['cbx'])) { // VERIFICA SI EL ITEM ESTA EN LOS OTROS ADICIONALES
				$frmListaArticuloAux = $frmTotalDcto;
			}
			
			$hddTpItm = $frmListaArticuloAux['hddTpItm'.$valor]; // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física
			
			if (in_array($hddTpItm, array(1,2))) { // 1 = Por Paquete, 2 = Individual
				$hddIdIvaItm = "";
				$hddIvaItm = 0;
				if (isset($arrayObj1)) {// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					foreach ($arrayObj1 as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticuloAux['hddIdIvaItm'.$valor.':'.$valor1[1]];
							$hddIvaItm = $frmListaArticuloAux['hddIvaItm'.$valor.':'.$valor1[1]];
						}
					}
				}
				
				// INSERTA EL DETALLE DE LA FACTURA
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_accesorios (id_factura, id_accesorio, id_tipo_accesorio, cantidad, precio_unitario, costo_compra, id_iva, iva, tipo_accesorio)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($frmListaArticuloAux['hddIdAccesorioItm'.$valor], "int"),
					valTpDato($frmListaArticuloAux['hddTipoAccesorioItm'.$valor], "int"),
					valTpDato(1, "real_inglesa"),
					valTpDato($frmListaArticuloAux['txtPrecioItm'.$valor], "real_inglesa"),
					valTpDato($frmListaArticuloAux['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato($hddIdIvaItm, "int"),
					valTpDato($hddIvaItm, "real_inglesa"),
					valTpDato($hddTpItm, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL); 
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idFacturaDetAccesorio = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				switch ($hddTpItm) { // 1 = Por Paquete, 2 = Individual
					case 1 :
						$idPaquetePedido = $frmListaArticuloAux['hddIdItm'.$valor];
						
						$insertSQL = sprintf("INSERT INTO an_partida (id_unidad_fisica, id_factura_venta, tipo_partida, tipo_registro, operador, id_tabla_tipo_partida, id_accesorio, precio_partida, costo_partida, iva_partida, clave_iva_partida, porcentaje_iva_partida, cantidad)
						SELECT DISTINCT
							%s,
							%s,
							'VENTA',
							'PAQUETE',
							'NORMAL',
							an_acc_paq.id_acc_paq,
							an_accesorio.id_accesorio,
							an_paquete_pedido.precio_accesorio,
							an_paquete_pedido.costo_accesorio,
							an_paquete_pedido.iva_accesorio,
							0,
							an_paquete_pedido.porcentaje_iva_accesorio,
							1
						FROM an_paquete_pedido
							INNER JOIN an_acc_paq ON (an_acc_paq.id_acc_paq = an_paquete_pedido.id_acc_paq)
							INNER JOIN an_accesorio ON (an_accesorio.id_accesorio = an_acc_paq.id_accesorio)
						WHERE id_paquete_pedido = %s;",
							valTpDato($idUnidadFisica, "int"),
							valTpDato($idFactura, "int"),
							valTpDato($idPaquetePedido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						// ACTUALIZA EL ESTADO DE LOS ACCESORIOS DEL PEDIDO
						$updateSQL = sprintf("UPDATE an_paquete_pedido SET
							estatus_paquete_pedido = %s
						WHERE id_paquete_pedido = %s;",
							valTpDato(1, "int"), // 0 = Pendiente, 1 = Facturado
							valTpDato($idPaquetePedido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL); 
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						break;
					case 2 :
						$idAccesorioPedido = $frmListaArticuloAux['hddIdItm'.$valor];
						
						$insertSQL = sprintf("INSERT INTO an_partida (id_unidad_fisica, id_factura_venta, tipo_partida, tipo_registro, operador, id_tabla_tipo_partida, id_accesorio, precio_partida, costo_partida, iva_partida, clave_iva_partida, porcentaje_iva_partida, cantidad) 
						SELECT DISTINCT
							%s,
							%s,
							'VENTA',
							'ACCESORIO',
							'NORMAL',
							an_accesorio.id_accesorio,
							an_accesorio.id_accesorio,
							an_accesorio_pedido.precio_accesorio,
							an_accesorio_pedido.costo_accesorio,
							an_accesorio_pedido.iva_accesorio,
							0,
							an_accesorio_pedido.porcentaje_iva_accesorio,
							1
						FROM an_accesorio_pedido
							INNER JOIN an_accesorio ON (an_accesorio.id_accesorio = an_accesorio_pedido.id_accesorio)
						WHERE id_accesorio_pedido = %s;",
							valTpDato($idUnidadFisica, "int"),
							valTpDato($idFactura, "int"),
							valTpDato($idAccesorioPedido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL); 
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						$updateSQL = sprintf("UPDATE an_accesorio_pedido SET
							estatus_accesorio_pedido = %s
						WHERE id_accesorio_pedido = %s;",
							valTpDato(1, "int"), // 0 = Pendiente, 1 = Facturado
							valTpDato($idAccesorioPedido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL); 
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						break;
				}
				
				$hddIdClienteItm = $frmListaArticuloAux['hddIdClienteItm'.$valor];
				$hddIdMotivoItm = $frmListaArticuloAux['hddIdMotivoItm'.$valor];
				$hddIdTipoComisionItm = str_replace(",", "", $frmListaArticuloAux['hddIdTipoComisionItm'.$valor]);
				$hddPorcentajeComisionItm = str_replace(",", "", $frmListaArticuloAux['hddPorcentajeComisionItm'.$valor]);
				$hddMontoComisionItm = str_replace(",", "", $frmListaArticuloAux['hddMontoComisionItm'.$valor]);
				
				if ($frmListaArticuloAux['hddTipoAccesorioItm'.$valor] == 3 && $hddPorcentajeComisionItm > 0) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato
					$Result1 = guardarNotaCargoCxC($idFactura, $idFacturaDetAccesorio, $hddIdClienteItm, $hddIdMotivoItm, $hddIdTipoComisionItm, $hddPorcentajeComisionItm, $hddMontoComisionItm);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$arrayIdDctoContabilidad[] = array(
							$Result1[1],
							$Result1[2],
							"NOTA_DEBITO_CXC");
					}
				}
			} else if ($hddTpItm == 3) {
				$facturarUnidadFisica = true;
				
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_vehiculo (id_factura, id_unidad_fisica, precio_unitario, costo_compra)
				VALUE (%s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($idUnidadFisica, "int"),
					valTpDato($frmListaArticuloAux['txtPrecioItm'.$valor], "real_inglesa"),
					valTpDato($frmListaArticuloAux['txtCostoItm'.$valor], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idFacturaDetalleVehiculo = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObj1)) {
					foreach ($arrayObj1 as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticuloAux['hddIdIvaItm'.$valor.':'.$valor1[1]];
							$hddIvaItm = $frmListaArticuloAux['hddIvaItm'.$valor.':'.$valor1[1]];
							
							$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_vehiculo_impuesto (id_factura_detalle_vehiculo, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($idFacturaDetalleVehiculo, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
						}
					}
				}
				
				$updateSQL = sprintf("UPDATE an_unidad_fisica SET
					estado_venta = %s,
					fecha_pago_venta = %s
				WHERE id_unidad_fisica = %s;",
					valTpDato("VENDIDO", "text"),
					valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
					valTpDato($idUnidadFisica, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL); 
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($idUnidadBasica, "int"),
					valTpDato($idUnidadFisica, "int"),
					valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($idClaveMovimiento, "int"),
					valTpDato(1, "real_inglesa"),
					valTpDato($frmListaArticuloAux['txtPrecioItm'.$valor], "real_inglesa"),
					valTpDato($frmListaArticuloAux['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
					valTpDato(((str_replace(",", "", $frmTotalDcto['txtDescuento']) * $frmListaArticuloAux['txtPrecioItm'.$valor]) / 100), "real_inglesa"),
					valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
					((isset($fechaMovimiento)) ? valTpDato($fechaMovimiento, "date") : valTpDato("NOW()", "campo")));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL); 
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			if ($frmTotalDcto['txtSubTotalIva'.$valor] > 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"),
					valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['hddLujoIva'.$valor], "boolean"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("FA", "text"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC, 5 = CH, 6 = TB
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// MODIFICA EL ESTATUS DEL PEDIDO DE VENTA
	$updateSQL = sprintf("UPDATE an_pedido SET
		estado_pedido = 2
	WHERE id_pedido = %s;",
		valTpDato($frmDcto['txtIdPedido'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	// INSERTA EL PAGO DEL DOCUMENTO (PAGO DE FACTURAS) SOLO SI ES DE CONTADO
	if ($idTipoPago == 1 || count($arrayObj2) > 0) { // 0 = Credito, 1 = Contado
		// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
		$queryAperturaCaja = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE idCaja = %s
			AND statusAperturaCaja IN (1,2)
			AND (ape.id_empresa = %s
				OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = %s));",
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsAperturaCaja = mysql_query($queryAperturaCaja);
		if (!$rsAperturaCaja) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$idApertura = $rowAperturaCaja['id'];
		$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(45, "int"), // 45 = Recibo de Pago Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado numeracion de comprobantes de pago"); }
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s)",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago, "date"),
			valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
			valTpDato(0, "int"),		
			valTpDato($idFactura, "int"),
			valTpDato($idModulo, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_v (id_factura, fecha_pago)
		VALUES (%s, %s)",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoPago = mysql_insert_id();
		
		foreach($arrayObj2 as $indicePago => $valorPago) {
			$idFormaPago = $frmListaPagos['txtIdFormaPago'.$valorPago];
			
			if (!($frmListaPagos['hddIdPago'.$valorPago] > 0)) {
				if (isset($idFormaPago)) {
					$idCheque = "";
					$tipoCheque = "-";
					$idTransferencia = "";
					$tipoTransferencia = "-";
					$estatusPago = 1;
					if ($idFormaPago == 1) { // 1 = Efectivo
						$idBancoCliente = 1;
						$txtCuentaClientePago = "-";
						$idBancoCompania = 1;
						$txtCuentaCompaniaPago = "-";
						$txtIdNumeroDctoPago = "-";
						$campo = "saldoEfectivo";
						$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$txtMontoSaldoCaja = $txtMonto;
					} else if ($idFormaPago == 2) { // 2 = Cheque
						$idCheque = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
						$idBancoCliente = $frmListaPagos['txtIdBancoCliente'.$valorPago];
						$txtCuentaClientePago = $frmListaPagos['txtCuentaClientePago'.$valorPago];
						$idBancoCompania = 1;
						$txtCuentaCompaniaPago = "-";
						$txtIdNumeroDctoPago = $frmListaPagos['txtNumeroDctoPago'.$valorPago];
						$tipoCheque = "0";
						$campo = "saldoCheques";
						if ($idCheque > 0) { // NO SUMA 2 = Cheque EN EL SALDO DE LA CAJA
							$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
							$txtMonto = 0;
							$txtMontoSaldoCaja = 0;
						} else {
							$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
							$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
							$txtMontoSaldoCaja = $txtMonto;
						}
					} else if ($idFormaPago == 3) { // 3 = Deposito
						$idBancoCliente = 1;
						$txtCuentaClientePago = "-";
						$idBancoCompania = $frmListaPagos['txtIdBancoCompania'.$valorPago];
						$txtCuentaCompaniaPago = asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valorPago]);
						$txtIdNumeroDctoPago = $frmListaPagos['txtNumeroDctoPago'.$valorPago];
						$campo = "saldoDepositos";
						$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$txtMontoSaldoCaja = $txtMonto;
					} else if ($idFormaPago == 4) { // 4 = Transferencia Bancaria
						$idTransferencia = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
						$idBancoCliente = $frmListaPagos['txtIdBancoCliente'.$valorPago];
						$txtCuentaClientePago = "-";
						$idBancoCompania = $frmListaPagos['txtIdBancoCompania'.$valorPago];
						$txtCuentaCompaniaPago = asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valorPago]);
						$txtIdNumeroDctoPago = $frmListaPagos['txtNumeroDctoPago'.$valorPago];
						$tipoTransferencia = "0";
						$campo = "saldoTransferencia";
						if ($idTransferencia > 0) { // NO SUMA 4 = Transferencia Bancaria EN EL SALDO DE LA CAJA
							$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
							$txtMonto = 0;
							$txtMontoSaldoCaja = 0;
						} else {
							$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
							$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
							$txtMontoSaldoCaja = $txtMonto;
						}
					} else if ($idFormaPago == 5) { // 5 = Tarjeta de Crédito
						$idBancoCliente = $frmListaPagos['txtIdBancoCliente'.$valorPago];
						$txtCuentaClientePago = "-";
						$idBancoCompania = $frmListaPagos['txtIdBancoCompania'.$valorPago];
						$txtCuentaCompaniaPago = asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valorPago]);
						$txtIdNumeroDctoPago = $frmListaPagos['txtNumeroDctoPago'.$valorPago];
						$campo = "saldoTarjetaCredito";
						$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$txtMontoSaldoCaja = $txtMonto;
					} else if ($idFormaPago == 6) { // 6 = Tarjeta de Debito
						$idBancoCliente = $frmListaPagos['txtIdBancoCliente'.$valorPago];
						$txtCuentaClientePago = "-";
						$idBancoCompania = $frmListaPagos['txtIdBancoCompania'.$valorPago];
						$txtCuentaCompaniaPago = asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valorPago]);
						$txtIdNumeroDctoPago = $frmListaPagos['txtNumeroDctoPago'.$valorPago];
						$campo = "saldoTarjetaDebito";
						$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$txtMontoSaldoCaja = $txtMonto;
					} else if ($idFormaPago == 7) { // 7 = Anticipo
						$idBancoCliente = 1;
						$txtCuentaClientePago = "-";
						$idBancoCompania = 1;
						$txtCuentaCompaniaPago = "-";
						$txtIdNumeroDctoPago = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
						$campo = "saldoAnticipo";
						$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$txtMontoSaldoCaja = 0;
						
						// BUSCA LOS DATOS DEL ANTICIPO (0 = Anulado; 1 = Activo)
						$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo cxc_ant
						WHERE cxc_ant.idAnticipo = %s
							AND cxc_ant.estatus = 1;",
							valTpDato($txtIdNumeroDctoPago, "int"));
						$rsAnticipo = mysql_query($queryAnticipo);
						if (!$rsAnticipo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowAnticipo = mysql_fetch_array($rsAnticipo);
						
						// (0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
						$estatusPago = (in_array($rowAnticipo['estadoAnticipo'], array(0))) ? 2 : $estatusPago;
					} else if ($idFormaPago == 8) { // 8 = Nota de Crédito
						$idBancoCliente = 1;
						$txtCuentaClientePago = "-";
						$idBancoCompania = 1;
						$txtCuentaCompaniaPago = "-";
						$txtIdNumeroDctoPago = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
						$campo = "saldoNotaCredito";
						$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$txtMontoSaldoCaja = $txtMonto;
					} else if ($idFormaPago == 9) { // 9 = Retención
						$idBancoCliente = 1;
						$txtCuentaClientePago = "-";
						$idBancoCompania = 1;
						$txtCuentaCompaniaPago = "-";
						$txtIdNumeroDctoPago = $frmListaPagos['txtNumeroDctoPago'.$valorPago];
						$campo = "saldoRetencion";
						$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$txtMontoSaldoCaja = $txtMonto;
					} else if ($idFormaPago == 10) { // 10 = Retencion I.S.L.R.
						$idBancoCliente = 1;
						$txtCuentaClientePago = "-";
						$idBancoCompania = 1;
						$txtCuentaCompaniaPago = "-";
						$txtIdNumeroDctoPago = $frmListaPagos['txtNumeroDctoPago'.$valorPago];
						$campo = "saldoRetencion";
						$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$txtMontoSaldoCaja = $txtMonto;
					} else if ($idFormaPago == 11) { // 11 = Otro
					}
					
					// NO SUMA 7 = Anticipo EN EL SALDO DE LA CAJA
					$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
						%s = %s + %s,
						saldoCaja = saldoCaja + %s
					WHERE id = %s;",
						$campo, $campo, valTpDato($txtMonto, "real_inglesa"),
						valTpDato($txtMontoSaldoCaja, "real_inglesa"),
						valTpDato($rowAperturaCaja['id'], "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
					// INSERTA LOS PAGOS DEL DOCUMENTO
					$insertSQL = sprintf("INSERT INTO an_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, numero_cuenta_cliente, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, id_apertura, estatus, id_condicion_mostrar, id_mostrar_contado, id_encabezado_v)
					VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
						valTpDato($idFormaPago, "int"),
						valTpDato($txtIdNumeroDctoPago, "text"),
						valTpDato($idBancoCliente, "int"),
						valTpDato($txtCuentaClientePago, "text"),
						valTpDato($idBancoCompania, "int"),
						valTpDato($txtCuentaCompaniaPago, "text"),
						valTpDato($frmListaPagos['txtMonto'.$valorPago], "real_inglesa"),
						valTpDato($numeroActualFactura, "text"),
						valTpDato($tipoCheque, "text"),
						valTpDato($idCheque, "int"),
						valTpDato($tipoTransferencia, "text"),
						valTpDato($idTransferencia, "int"),
						valTpDato(1, "int"),
						valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
						valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
						valTpDato($idApertura, "int"),
						valTpDato($estatusPago, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
						valTpDato($frmListaPagos['cbxCondicionMostrar'.$valorPago], "int"), // Null = No, 1 = Si
						valTpDato($frmListaPagos['lstSumarA'.$valorPago], "int"), // Null = No, 1 = Si
						valTpDato($idEncabezadoPago, "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idPago = mysql_insert_id();
					
					$arrayIdDctoContabilidad[] = array(
						$idPago,
						$idModulo,
						"CAJAENTRADA");
					
					if ($idFormaPago == 2) { // 2 = Cheque
						// ACTUALIZA EL SALDO DEL CHEQUE (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_cheque cxc_ch SET
							saldo_cheque = monto_neto_cheque,
							total_pagado_cheque = monto_neto_cheque
						WHERE cxc_ch.id_cheque = %s
							AND cxc_ch.estado_cheque IN (0,1,2);",
							valTpDato($idCheque, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// ACTUALIZA EL SALDO DEL CHEQUE SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
						$updateSQL = sprintf("UPDATE cj_cc_cheque cxc_ch SET
							saldo_cheque = saldo_cheque
											- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
													WHERE cxc_pago.id_cheque = cxc_ch.id_cheque
														AND cxc_pago.formaPago IN (2)
														AND cxc_pago.estatus IN (1,2)), 0)
												+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
														WHERE cxc_pago.id_cheque = cxc_ch.id_cheque
															AND cxc_pago.formaPago IN (2)
															AND cxc_pago.estatus IN (1,2)), 0)
												+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
														WHERE cxc_pago.id_cheque = cxc_ch.id_cheque
															AND cxc_pago.idFormaPago IN (2)
															AND cxc_pago.estatus IN (1,2)), 0)
												+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
														WHERE cxc_pago.id_cheque = cxc_ch.id_cheque
															AND cxc_pago.id_forma_pago IN (2)
															AND cxc_pago.estatus IN (1,2)), 0))
						WHERE cxc_ch.id_cheque = %s
							AND cxc_ch.estado_cheque IN (0,1,2);",
							valTpDato($idCheque, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// ACTUALIZA EL ESTATUS DEL CHEQUE (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_cheque cxc_ch SET
							estado_cheque = (CASE
												WHEN (ROUND(monto_neto_cheque, 2) > ROUND(total_pagado_cheque, 2)
													AND ROUND(saldo_cheque, 2) > 0) THEN
													0
												WHEN (ROUND(monto_neto_cheque, 2) = ROUND(total_pagado_cheque, 2)
													AND ROUND(saldo_cheque, 2) <= 0
													AND cxc_ch.id_cheque IN (SELECT * 
																				FROM (SELECT cxc_pago.id_cheque FROM an_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (2)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (2)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
																					WHERE cxc_pago.idFormaPago IN (2)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
																					WHERE cxc_pago.id_forma_pago IN (2)
																						AND cxc_pago.estatus IN (1)) AS q)) THEN
													3
												WHEN (ROUND(monto_neto_cheque, 2) = ROUND(total_pagado_cheque, 2)
													AND ROUND(monto_neto_cheque, 2) = ROUND(saldo_cheque, 2)) THEN
													1
												WHEN (ROUND(monto_neto_cheque, 2) = ROUND(total_pagado_cheque, 2)
													AND ROUND(monto_neto_cheque, 2) > ROUND(saldo_cheque, 2)
													AND ROUND(saldo_cheque, 2) > 0) THEN
													2
												WHEN (ROUND(monto_neto_cheque, 2) = ROUND(total_pagado_cheque, 2)
													AND ROUND(saldo_cheque, 2) <= 0) THEN
													3
												WHEN (ROUND(monto_neto_cheque, 2) > ROUND(total_pagado_cheque, 2)
													AND ROUND(saldo_cheque, 2) <= 0) THEN
													4
											END)
						WHERE cxc_ch.id_cheque = %s
							AND cxc_ch.estado_cheque IN (0,1,2);",
							valTpDato($idCheque, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// VERIFICA EL SALDO DEL CHEQUE A VER SI ESTA NEGATIVO
						$querySaldoDcto = sprintf("SELECT cxc_ch.*,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
						FROM cj_cc_cheque cxc_ch
							INNER JOIN cj_cc_cliente cliente ON (cxc_ch.id_cliente = cliente.id)
						WHERE id_cheque = %s
							AND saldo_cheque < 0;",
							valTpDato($idCheque, "int"));
						$rsSaldoDcto = mysql_query($querySaldoDcto);
						if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
						$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
						$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
						if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("El Cheque Nro. ".$rowSaldoDcto['numero_cheque']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
						
					} else if ($idFormaPago == 3) { // 3 = Deposito
						$arrayPosiciones = explode("|",$frmDetallePago['hddObjDetalleDeposito']);
						$arrayFormaPago = explode("|",$frmDetallePago['hddObjDetalleDepositoFormaPago']);
						$arrayBanco = explode("|",$frmDetallePago['hddObjDetalleDepositoBanco']);
						$arrayNroCuenta = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCuenta']);
						$arrayNroCheque = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCheque']);
						$arrayMonto = explode("|",$frmDetallePago['hddObjDetalleDepositoMonto']);
						
						foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
							if ($valorDeposito == $valorPago) {
								if ($arrayFormaPago[$indiceDeposito] == 1) {
									$bancoDetalleDeposito = "";
									$nroCuentaDetalleDeposito = "";
									$nroChequeDetalleDeposito = "";
								} else {
									$bancoDetalleDeposito = $arrayBanco[$indiceDeposito];
									$nroCuentaDetalleDeposito = $arrayNroCuenta[$indiceDeposito];
									$nroChequeDetalleDeposito = $arrayNroCheque[$indiceDeposito];
								}
								
								$insertSQL = sprintf("INSERT INTO an_det_pagos_deposito_factura (idPago, fecha_deposito, idFormaPago, idBanco, numero_cuenta, numero_cheque, monto, id_tipo_documento, idCaja)
								VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
									valTpDato($idPago, "int"),
									valTpDato(date("Y-m-d", strtotime($frmListaPagos['txtFechaDeposito'.$valorPago])), "date"),
									valTpDato($arrayFormaPago[$indiceDeposito], "int"),
									valTpDato($bancoDetalleDeposito, "int"),
									valTpDato($nroCuentaDetalleDeposito, "text"),
									valTpDato($nroChequeDetalleDeposito, "text"),
									valTpDato($arrayMonto[$indiceDeposito], "real_inglesa"),
									valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
									valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							}
						}
					} else if ($idFormaPago == 4) { // 4 = Transferencia Bancaria
						// ACTUALIZA EL SALDO DE LA TRANSFERENCIA (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_transferencia cxc_tb SET
							saldo_transferencia = monto_neto_transferencia,
							total_pagado_transferencia = monto_neto_transferencia
						WHERE cxc_tb.id_transferencia = %s
							AND cxc_tb.estado_transferencia IN (0,1,2);",
							valTpDato($idTransferencia, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// ACTUALIZA EL SALDO DE LA TRANSFERENCIA SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
						$updateSQL = sprintf("UPDATE cj_cc_transferencia cxc_tb SET
							saldo_transferencia = saldo_transferencia
											- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
													WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
														AND cxc_pago.formaPago IN (4)
														AND cxc_pago.estatus IN (1,2)), 0)
												+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
														WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
															AND cxc_pago.formaPago IN (4)
															AND cxc_pago.estatus IN (1,2)), 0)
												+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
														WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
															AND cxc_pago.idFormaPago IN (4)
															AND cxc_pago.estatus IN (1,2)), 0)
												+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
														WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
															AND cxc_pago.id_forma_pago IN (4)
															AND cxc_pago.estatus IN (1,2)), 0))
						WHERE cxc_tb.id_transferencia = %s
							AND cxc_tb.estado_transferencia IN (0,1,2);",
							valTpDato($idTransferencia, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// ACTUALIZA EL ESTATUS DE LA TRANSFERENCIA (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_transferencia cxc_tb SET
							estado_transferencia = (CASE
												WHEN (ROUND(monto_neto_transferencia, 2) > ROUND(total_pagado_transferencia, 2)
													AND ROUND(saldo_transferencia, 2) > 0) THEN
													0
												WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
													AND ROUND(saldo_transferencia, 2) <= 0
													AND cxc_tb.id_transferencia IN (SELECT * 
																				FROM (SELECT cxc_pago.id_transferencia FROM an_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (4)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (4)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
																					WHERE cxc_pago.idFormaPago IN (4)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
																					WHERE cxc_pago.id_forma_pago IN (4)
																						AND cxc_pago.estatus IN (1)) AS q)) THEN
													3
												WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
													AND ROUND(monto_neto_transferencia, 2) = ROUND(saldo_transferencia, 2)) THEN
													1
												WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
													AND ROUND(monto_neto_transferencia, 2) > ROUND(saldo_transferencia, 2)
													AND ROUND(saldo_transferencia, 2) > 0) THEN
													2
												WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
													AND ROUND(saldo_transferencia, 2) <= 0) THEN
													3
												WHEN (ROUND(monto_neto_transferencia, 2) > ROUND(total_pagado_transferencia, 2)
													AND ROUND(saldo_transferencia, 2) <= 0) THEN
													4
											END)
						WHERE cxc_tb.id_transferencia = %s
							AND cxc_tb.estado_transferencia IN (0,1,2);",
							valTpDato($idTransferencia, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// VERIFICA EL SALDO DE LA TRANSFERENCIA A VER SI ESTA NEGATIVO
						$querySaldoDcto = sprintf("SELECT cxc_tb.*,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
						FROM cj_cc_transferencia cxc_tb
							INNER JOIN cj_cc_cliente cliente ON (cxc_tb.id_cliente = cliente.id)
						WHERE id_transferencia = %s
							AND saldo_transferencia < 0;",
							valTpDato($idTransferencia, "int"));
						$rsSaldoDcto = mysql_query($querySaldoDcto);
						if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
						$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
						$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
						if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Transferencia Nro. ".$rowSaldoDcto['numero_transferencia']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
						
					} else if (in_array($idFormaPago, array(5,6))) { // 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito
						$sqlSelectRetencionPunto = sprintf("SELECT id_retencion_punto FROM te_retencion_punto
						WHERE id_cuenta = %s
							AND id_tipo_tarjeta = %s",
							valTpDato($frmListaPagos['txtIdCuentaCompaniaPago'.$valorPago], "int"),
							valTpDato($frmListaPagos['txtTipoTarjeta'.$valorPago], "int"));
						$rsSelectRetencionPunto = mysql_query($sqlSelectRetencionPunto);
						if (!$rsSelectRetencionPunto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowSelectRetencionPunto = mysql_fetch_array($rsSelectRetencionPunto);
						
						$insertSQL = sprintf("INSERT INTO cj_cc_retencion_punto_pago (id_caja, id_pago, id_tipo_documento, id_retencion_punto)
						VALUES (%s, %s, %s, %s)",
							valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
							valTpDato($idPago, "int"),
							valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
							valTpDato($rowSelectRetencionPunto['id_retencion_punto'], "int"));
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
					} else if ($idFormaPago == 7) { // 7 = Anticipo
						$idAnticipo = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
						
						// ACTUALIZA EL SALDO Y EL MONTO PAGADO DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
							saldoAnticipo = montoNetoAnticipo,
							totalPagadoAnticipo = IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
															WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
																AND (cxc_pago.id_forma_pago NOT IN (11)
																	OR (cxc_pago.id_forma_pago IN (11) AND cxc_pago.id_concepto NOT IN (6,7,8)))
																AND cxc_pago.estatus IN (1,2)), 0)
						WHERE cxc_ant.idAnticipo = %s
							AND cxc_ant.estadoAnticipo IN (0,1,2);",
							valTpDato($idAnticipo, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// ACTUALIZA EL SALDO DEL ANTICIPO SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
						$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
							saldoAnticipo = saldoAnticipo
												- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
															WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
																AND cxc_pago.formaPago IN (7)
																AND cxc_pago.estatus IN (1,2)), 0)
													+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
																WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
																	AND cxc_pago.formaPago IN (7)
																	AND cxc_pago.estatus IN (1,2)), 0)
													+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
																WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
																	AND cxc_pago.idFormaPago IN (7)
																	AND cxc_pago.estatus IN (1,2)), 0))
						WHERE cxc_ant.idAnticipo = %s
							AND cxc_ant.estadoAnticipo IN (0,1,2);",
							valTpDato($idAnticipo, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// ACTUALIZA EL ESTATUS DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
							estadoAnticipo = (CASE
												WHEN (ROUND(montoNetoAnticipo, 2) > ROUND(totalPagadoAnticipo, 2)
													AND ROUND(saldoAnticipo, 2) > 0) THEN
													0
												WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
													AND ROUND(saldoAnticipo, 2) <= 0
													AND cxc_ant.idAnticipo IN (SELECT * 
																				FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (7)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (7)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																					WHERE cxc_pago.idFormaPago IN (7)
																						AND cxc_pago.estatus IN (1)) AS q)) THEN
													3
												WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
													AND ROUND(montoNetoAnticipo, 2) = ROUND(saldoAnticipo, 2)) THEN
													1
												WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
													AND ROUND(montoNetoAnticipo, 2) > ROUND(saldoAnticipo, 2)
													AND ROUND(saldoAnticipo, 2) > 0) THEN
													2
												WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
													AND ROUND(saldoAnticipo, 2) <= 0) THEN
													3
												WHEN (ROUND(montoNetoAnticipo, 2) > ROUND(totalPagadoAnticipo, 2)
													AND ROUND(saldoAnticipo, 2) <= 0) THEN
													4
											END)
						WHERE cxc_ant.idAnticipo = %s
							AND cxc_ant.estadoAnticipo IN (0,1,2);",
							valTpDato($idAnticipo, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// VERIFICA EL SALDO DEL ANTICIPO A VER SI ESTA NEGATIVO
						$querySaldoDcto = sprintf("SELECT cxc_ant.*,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
						FROM cj_cc_anticipo cxc_ant
							INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
						WHERE idAnticipo = %s
							AND saldoAnticipo < 0;",
							valTpDato($idAnticipo, "int"));
						$rsSaldoDcto = mysql_query($querySaldoDcto);
						if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
						$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
						$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
						if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("El Anticipo Nro. ".$rowSaldoDcto['numeroAnticipo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
						
					} else if ($idFormaPago == 8) { // 8 = Nota de Crédito
						$idNotaCredito = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
						
						// ACTUALIZA EL SALDO DEL NOTA CREDITO DEPENDIENDO DE SUS PAGOS (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
							saldoNotaCredito = montoNetoNotaCredito
						WHERE idNotaCredito = %s
							AND estadoNotaCredito IN (0,1,2,3,4);",
							valTpDato($idNotaCredito, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						// ACTUALIZA EL SALDO DEL NOTA CREDITO SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
						$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
							saldoNotaCredito = saldoNotaCredito
												- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
														WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
															AND cxc_pago.formaPago IN (8)
															AND cxc_pago.estatus IN (1,2)), 0)
													+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
															WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
																AND cxc_pago.formaPago IN (8)
																AND cxc_pago.estatus IN (1,2)), 0)
													+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
															WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
																AND cxc_pago.idFormaPago IN (8)
																AND cxc_pago.estatus IN (1,2)), 0)
													+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
															WHERE cxc_pago.numeroControlDetalleAnticipo = cxc_nc.idNotaCredito
																AND cxc_pago.id_forma_pago IN (8)
																AND cxc_pago.estatus IN (1,2)), 0))
						WHERE cxc_nc.idNotaCredito = %s
							AND cxc_nc.estadoNotaCredito IN (0,1,2,3,4);",
							valTpDato($idNotaCredito, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						// ACTUALIZA EL ESTATUS DEL NOTA CREDITO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
							estadoNotaCredito = (CASE
												WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
													AND ROUND(saldoNotaCredito, 2) > 0) THEN
													0
												WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
													AND ROUND(saldoNotaCredito, 2) <= 0
													AND cxc_nc.idNotaCredito IN (SELECT * 
																				FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (8)
																						AND cxc_pago.estatus = 1
																					
																					UNION
																					
																					SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (8)
																						AND cxc_pago.estatus = 1
																					
																					UNION
																					
																					SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																					WHERE cxc_pago.idFormaPago IN (8)
																						AND cxc_pago.estatus = 1
																					
																					UNION
																					
																					SELECT cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
																					WHERE cxc_pago.id_forma_pago IN (8)
																						AND cxc_pago.estatus = 1) AS q)) THEN
													3
												WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
													AND ROUND(montoNetoNotaCredito, 2) = ROUND(saldoNotaCredito, 2)) THEN
													1
												WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
													AND ROUND(montoNetoNotaCredito, 2) > ROUND(saldoNotaCredito, 2)
													AND ROUND(saldoNotaCredito, 2) > 0) THEN
													2
												WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
													AND ROUND(saldoNotaCredito, 2) <= 0) THEN
													3
												WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
													AND ROUND(saldoNotaCredito, 2) <= 0) THEN
													4
											END)
						WHERE cxc_nc.idNotaCredito = %s
							AND cxc_nc.estadoNotaCredito IN (0,1,2,3,4);",
							valTpDato($idNotaCredito, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						// VERIFICA EL SALDO DEL NOTA CREDITO A VER SI ESTA NEGATIVO
						$querySaldoDcto = sprintf("SELECT cxc_nc.*,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
						FROM cj_cc_notacredito cxc_nc
							INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
						WHERE idNotaCredito = %s
							AND saldoNotaCredito < 0;",
							valTpDato($idNotaCredito, "int"));
						$rsSaldoDcto = mysql_query($querySaldoDcto);
						if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
						$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
						$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
						if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Crédito Nro. ".$rowSaldoDcto['numeracion_nota_credito']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
						
					} else if ($idFormaPago == 9) { // 9 = Retención
						$sqlSelectFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura
						WHERE idFactura = %s",
							valTpDato($idFactura, "int"));
						$rsSelectFactura = mysql_query($sqlSelectFactura);
						if (!$rsSelectFactura) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowSelectFactura = mysql_fetch_array($rsSelectFactura);
						
						$porcentajeAlicuota = $rowSelectFactura['porcentajeIvaFactura'] + $rowSelectFactura['porcentajeIvaDeLujoFactura'];
						$impuestoIva = $rowSelectFactura['calculoIvaFactura'] + $rowSelectFactura['calculoIvaDeLujoFactura'];
						$porcentajeRetenido = ($impuestoIva > 0) ? $frmListaPagos['txtMonto'.$valorPago] * 100 / $impuestoIva : 0;
						
						$insertSQL = sprintf("INSERT INTO cj_cc_retencioncabezera (numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idCliente, idRegistrosUnidadesFisicas)
						VALUES (%s, %s, %s, %s, %s, %s)",
							valTpDato($frmListaPagos['txtNumeroDctoPago'.$valorPago], "text"),
							valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
							valTpDato(date("Y", strtotime($fechaRegistroPago)), "text"),
							valTpDato(date("m", strtotime($fechaRegistroPago)), "text"),
							valTpDato($idCliente, "int"),
							valTpDato(0, "int"));
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idRetencionCabecera = mysql_insert_id();
						
						$insertSQL = sprintf("INSERT INTO cj_cc_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
							valTpDato($idRetencionCabecera, "int"),
							valTpDato($rowSelectFactura['fechaRegistroFactura'], "date"),
							valTpDato($rowSelectFactura['idFactura'], "int"),
							valTpDato($rowSelectFactura['numeroControl'], "text"),
							valTpDato(" ", "text"),
							valTpDato(" ", "text"),
							valTpDato(" ", "text"),
							valTpDato(" ", "text"),
							valTpDato($rowSelectFactura['montoTotalFactura'], "real_inglesa"),
							valTpDato($rowSelectFactura['subtotalFactura'], "real_inglesa"),
							valTpDato($rowSelectFactura['baseImponible'], "real_inglesa"),
							valTpDato($porcentajeAlicuota, "real_inglesa"),
							valTpDato($impuestoIva, "real_inglesa"),
							valTpDato($frmListaPagos['txtMonto'.$valorPago], "real_inglesa"),
							valTpDato($porcentajeRetenido, "int"));
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
					} else if ($idFormaPago == 10) { // 10 = Retencion I.S.L.R.
						
					} else if ($idFormaPago == 11) { // 11 = Otro
						
					}
					
					// INSERTA EL DETALLE DEL RECIBO DE PAGO
					$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
					VALUES (%s, %s)",
						valTpDato($idEncabezadoReciboPago, "int"),
						valTpDato($idPago, "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}
			}
		}
		
		// ACTUALIZA EL SALDO DE LA FACTURA
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			saldoFactura = IFNULL(cxc_fact.subtotalFactura, 0)
								- IFNULL(cxc_fact.descuentoFactura, 0)
								+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
										WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
								+ IFNULL((SELECT SUM(cxc_fact_impuesto.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_impuesto
										WHERE cxc_fact_impuesto.id_factura = cxc_fact.idFactura), 0)
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL SALDO DE LA FACTURA DEPENDIENDO DE SUS PAGOS
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			saldoFactura = saldoFactura - IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
											WHERE cxc_pago.id_factura = cxc_fact.idFactura
												AND cxc_pago.estatus = 1), 0)
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			estadoFactura = (CASE
								WHEN (ROUND(saldoFactura, 2) <= 0) THEN
									1
								WHEN (ROUND(saldoFactura, 2) > 0 AND ROUND(saldoFactura, 2) < ROUND(montoTotalFactura, 2)) THEN
									2
								ELSE
									0
							END)
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// VERIFICA EL SALDO DE LA FACTURA A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxc_fact.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			(SELECT COUNT(q.id_factura)
			FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura FROM an_pagos cxc_pago
				WHERE cxc_pago.estatus IN (2)
				
				UNION
				
				SELECT cxc_pago.idPago, cxc_pago.id_factura FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.estatus IN (2)) AS q
			WHERE q.id_factura = cxc_fact.idFactura) AS cant_pagos_pendientes
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		WHERE cxc_fact.idFactura = %s
			AND (cxc_fact.saldoFactura < 0
				OR (cxc_fact.saldoFactura < (SELECT SUM(q.montoPagado)
												FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
													WHERE cxc_pago.estatus IN (2)
													
													UNION
													
													SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
													WHERE cxc_pago.estatus IN (2)) AS q
												WHERE q.id_factura = cxc_fact.idFactura)));",
			valTpDato($idFactura, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) {
			if ($rowSaldoDcto['saldoFactura'] < 0) {
				return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo");
			} else if ($rowSaldoDcto['cant_pagos_pendientes'] > 0) {
				return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." no puede ser pagada en su totalidad debido a que posee ".$rowSaldoDcto['cant_pagos_pendientes']." pagos pendientes. Por favor termine de registrar o anular dichos pagos.");
			}
		}
	}
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	
	// BUSCA LOS PAGOS DE ANTICIPO
	$queryDctoPago = sprintf("SELECT *
	FROM (SELECT 
			cxc_pago.id_factura,
			cxc_pago.fechaPago,
			cxc_pago.formaPago,
			cxc_pago.numeroDocumento AS id_documento_pago,
			cxc_pago.montoPagado
		FROM sa_iv_pagos cxc_pago
		
		UNION
		
		SELECT 
			cxc_pago.id_factura,
			cxc_pago.fechaPago,
			cxc_pago.formaPago,
			cxc_pago.numeroDocumento AS id_documento_pago,
			cxc_pago.montoPagado
		FROM an_pagos cxc_pago) AS query
	WHERE query.id_factura = %s
		AND query.formaPago IN (7);",
		valTpDato($idFactura, "int"));
	$rsDctoPago = mysql_query($queryDctoPago);
	if (!$rsDctoPago) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsDctoPago = mysql_num_rows($rsDctoPago);
	while ($rowDctoPago = mysql_fetch_assoc($rsDctoPago)) {
		$idAnticipo = $rowDctoPago['id_documento_pago'];
		
		// VERIFICA SI ALGUN ANTICIPO DE TRADE IN TIENE ALGUN DOCUMENTO ASOCIADO QUE AFECTE AL COSTO DE LA UNIDAD VENDIDA
		$queryTradeInCxC = sprintf("SELECT
			(CASE 
				WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
					'ND_CXC'
				WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
					'NC_CXC'
			END) AS tipo_documento,
			(CASE 
				WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
					cxc_nd.idNotaCargo
				WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
					cxc_nc.idNotaCredito
			END) AS id_documento,
			(CASE 
				WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
					cxc_nd.montoTotalNotaCargo
				WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
					cxc_nc.montoNetoNotaCredito
			END) AS monto_total
		FROM an_tradein_cxc tradein_cxc
			LEFT JOIN cj_cc_notadecargo cxc_nd ON (tradein_cxc.id_nota_cargo_cxc = cxc_nd.idNotaCargo AND tradein_cxc.id_nota_cargo_cxc IS NOT NULL)
			LEFT JOIN cj_cc_notacredito cxc_nc ON (tradein_cxc.id_nota_credito_cxc = cxc_nc.idNotaCredito AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)
		WHERE tradein_cxc.id_anticipo = %s
			AND tradein_cxc.estatus = 1;",
			valTpDato($idAnticipo, "int"));
		$rsTradeInCxC = mysql_query($queryTradeInCxC);
		if (!$rsTradeInCxC) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsTradeInCxC = mysql_num_rows($rsTradeInCxC);
		while ($rowTradeInCxC = mysql_fetch_assoc($rsTradeInCxC)) {
			$tipoDocumento = $rowTradeInCxC['tipo_documento'];
			$idDocumento = $rowTradeInCxC['id_documento'];
			
			// INSERTA EL DETALLE DEL AGREGADO
			if ($idDocumento > 0) {
				$contAgregado++;
				
				if ($tipoDocumento == 'ND_CXC') {
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_cargo_cxc, monto)
					VALUE (%s, %s, %s);",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($idDocumento, "int"),
						valTpDato($rowTradeInCxC['monto_total'], "real_inglesa"));
				} else if ($tipoDocumento == 'NC_CXC') {
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_credito_cxc, monto)
					VALUE (%s, %s, %s);",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($idDocumento, "int"),
						valTpDato($rowTradeInCxC['monto_total'], "real_inglesa"));
				}
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idUnidadFisicaAgregado = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	if ($contAgregado > 0) {
		// ACTUALIZA EL COSTO DE LOS AGREGADOS
		$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
			costo_agregado = (SELECT SUM(IF((id_factura_cxp IS NOT NULL
											OR id_nota_cargo_cxp IS NOT NULL
											OR id_nota_credito_cxc IS NOT NULL
											OR id_vale_salida IS NOT NULL), 1, (-1)) * monto) FROM an_unidad_fisica_agregado uni_fis_agregado
								WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica
									AND uni_fis_agregado.estatus = 1)
		WHERE id_unidad_fisica = %s;",
			valTpDato($idUnidadFisica, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL COSTO DE LA VENTA DE LA UNIDAD EN LA FACTURA
		$updateSQL = sprintf("UPDATE cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic SET
			costo_compra = (SELECT 
								IFNULL(uni_fis.precio_compra, 0)
									+ IFNULL(uni_fis.costo_agregado, 0)
									- IFNULL(uni_fis.costo_depreciado, 0)
									- IFNULL(uni_fis.costo_trade_in, 0)
							FROM an_unidad_fisica uni_fis
							WHERE uni_fis.id_unidad_fisica = cxc_fact_det_vehic.id_unidad_fisica)
		WHERE cxc_fact_det_vehic.id_factura = %s
			AND cxc_fact_det_vehic.id_unidad_fisica = %s;",
			valTpDato($idFactura, "int"),
			valTpDato($idUnidadFisica, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL COSTO DE LA VENTA DE LA UNIDAD EN EL KARDEX
		$updateSQL = sprintf("UPDATE an_kardex kardex SET
			costo = (SELECT 
						IFNULL(uni_fis.precio_compra, 0)
							+ IFNULL(uni_fis.costo_agregado, 0)
							- IFNULL(uni_fis.costo_depreciado, 0)
							- IFNULL(uni_fis.costo_trade_in, 0)
					FROM an_unidad_fisica uni_fis
					WHERE uni_fis.id_unidad_fisica = kardex.idUnidadFisica)
		WHERE kardex.id_documento = %s
			AND kardex.idUnidadFisica = %s
			AND kardex.tipoMovimiento IN (3);",
			valTpDato($idFactura, "int"),
			valTpDato($idUnidadFisica, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
		creditodisponible = limitecredito - (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
													WHERE cxc_fact.idCliente = cliente_emp.id_cliente
														AND cxc_fact.id_empresa = cliente_emp.id_empresa
														AND cxc_fact.estadoFactura IN (0,2)), 0)
											+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
													WHERE cxc_nd.idCliente = cliente_emp.id_cliente
														AND cxc_nd.id_empresa = cliente_emp.id_empresa
														AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
											- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
													WHERE cxc_ant.idCliente = cliente_emp.id_cliente
														AND cxc_ant.id_empresa = cliente_emp.id_empresa
														AND cxc_ant.estadoAnticipo IN (1,2)
														AND cxc_ant.estatus = 1), 0)
											- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
													WHERE cxc_nc.idCliente = cliente_emp.id_cliente
														AND cxc_nc.id_empresa = cliente_emp.id_empresa
														AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
											+ IFNULL((SELECT
														SUM(IFNULL(ped_vent.subtotal, 0)
															- IFNULL(ped_vent.subtotal_descuento, 0)
															+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																	WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
															+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																	WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
													FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_cliente = cliente_emp.id_cliente
														AND ped_vent.id_empresa = cliente_emp.id_empresa
														AND ped_vent.estatus_pedido_venta IN (2)), 0)),
		creditoreservado = (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
									WHERE cxc_fact.idCliente = cliente_emp.id_cliente
										AND cxc_fact.id_empresa = cliente_emp.id_empresa
										AND cxc_fact.estadoFactura IN (0,2)), 0)
							+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
									WHERE cxc_nd.idCliente = cliente_emp.id_cliente
										AND cxc_nd.id_empresa = cliente_emp.id_empresa
										AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
							- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
									WHERE cxc_ant.idCliente = cliente_emp.id_cliente
										AND cxc_ant.id_empresa = cliente_emp.id_empresa
										AND cxc_ant.estadoAnticipo IN (1,2)
										AND cxc_ant.estatus = 1), 0)
							- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
									WHERE cxc_nc.idCliente = cliente_emp.id_cliente
										AND cxc_nc.id_empresa = cliente_emp.id_empresa
										AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
							+ IFNULL((SELECT
										SUM(IFNULL(ped_vent.subtotal, 0)
											- IFNULL(ped_vent.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
													WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
											+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
													WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
									FROM iv_pedido_venta ped_vent
									WHERE ped_vent.id_cliente = cliente_emp.id_cliente
										AND ped_vent.id_empresa = cliente_emp.id_empresa
										AND ped_vent.estatus_pedido_venta IN (2)
										AND id_empleado_aprobador IS NOT NULL), 0))
	WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
		AND cliente_emp.id_cliente = %s
		AND cliente_emp.id_empresa = %s;",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
		
	$objResponse->assign("txtIdFactura","value",$idFactura);
	$objResponse->assign("txtNumeroFactura","value",$numeroActualFactura);
	
	//CONTABILIZA DOCUMENTO
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "VENTA") {
				$idFactura = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarVentasRe")) { generarVentasRe($idFactura,"",""); } break;
					case 1 : if (function_exists("generarVentasSe")) { generarVentasSe($idFactura,"",""); } break;
					case 2 : if (function_exists("generarVentasVe")) { generarVentasVe($idFactura,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert("Factura Guardada con Exito");
	
	if ($frmDcto['txtIdFacturaEditada'] > 0) {
		$objResponse->script(sprintf("window.location.href='cj_facturas_por_pagar_form.php?id=%s';", $idFactura));
	} else {
		switch ($idTipoPago) { // 0 = Credito, 1 = Contado
			case 0 : $objResponse->script(sprintf("window.location.href='cj_factura_venta_list.php';")); break;
			case 1 : $objResponse->script(sprintf("window.location.href='cj_factura_venta_list.php';")); break;
		}
		
		$objResponse->script("verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=".$idFactura."', 960, 550);");
		
		if ($idEncabezadoReciboPago > 0) {
			$objResponse->script(sprintf("verVentana('reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s',960,550)", $idEncabezadoReciboPago));
		}
	}
	
	return $objResponse;
}

function insertarItem($frmListaItemPedido, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("byId('trCreditoTradeIn').style.display = 'none';");
	if (in_array(idArrayPais,array(3))) {
		$objResponse->script("byId('trCreditoTradeIn').style.display = '';");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaArticulo['cbx']) && isset($frmTotalDcto['cbx'])) {
		$arrayObj = array_merge($frmListaArticulo['cbx'], $frmTotalDcto['cbx']);
	} else if (isset($frmListaArticulo['cbx'])) {
		$arrayObj = $frmListaArticulo['cbx'];
	} else if (isset($frmTotalDcto['cbx'])) {
		$arrayObj = $frmTotalDcto['cbx'];
	}
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$queryPedido = sprintf("SELECT
		ped_vent.id_pedido,
		ped_vent.numeracion_pedido,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		ped_vent.id_factura_cxc,
		ped_vent.id_empresa,
		ped_vent.id_cliente,
		ped_vent.precio_venta,
		ped_vent.monto_descuento,
		ped_vent.porcentaje_inicial,
		ped_vent.porcentaje_iva,
		uni_bas.impuesto_lujo,
		ped_vent.asesor_ventas AS id_empleado,
		emp.nombre_empresa,
		clave_mov.id_clave_movimiento,
		clave_mov.clave,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		ped_vent.observaciones
	FROM an_uni_bas uni_bas
		INNER JOIN an_unidad_fisica uni_fis ON (uni_bas.id_uni_bas = uni_fis.id_uni_bas)
		RIGHT JOIN an_pedido ped_vent ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica)
		LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
		INNER JOIN pg_empresa emp ON (emp.id_empresa = ped_vent.id_empresa)
		INNER JOIN pg_clave_movimiento clave_mov ON (ped_vent.id_clave_movimiento = clave_mov.id_clave_movimiento)
	WHERE ped_vent.id_pedido = %s;",
		valTpDato($frmListaItemPedido['txtIdPedidoItems'], "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPedido = mysql_fetch_array($rsPedido);
	
	$idFactura = $rowPedido['id_factura_cxc'];
	$idEmpresa = $rowPedido['id_empresa'];
	$idCliente = $rowPedido['id_cliente'];
	$condicionPago = ($rowPedido['porcentaje_inicial'] == 100) ? "1" : "0"; // 0 = Credito, 1 = Contado
	
	// VERIFICA VALORES DE CONFIGURACION (Incluir Saldo Negativo del Trade In en Precio de Venta de la Unidad (Copia Banco))
	$queryConfig207 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 207 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig207 = mysql_query($queryConfig207);
	if (!$rsConfig207) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig207 = mysql_num_rows($rsConfig207);
	$rowConfig207 = mysql_fetch_assoc($rsConfig207);
	
	// CARGA LOS DATOS DEL EMPLEADO VENDEDOR
	$queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados WHERE id_empleado = %s",
		valTpDato($rowPedido['id_empleado'], "text"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	// CARGA LOS DATOS DEL CLIENTE
	$queryCliente = sprintf("SELECT
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto
	FROM cj_cc_cliente cliente
	WHERE id = %s;",
		valTpDato($idCliente, "int"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	if ($condicionPago == 0) { // 0 = Credito, 1 = Contado
		$objResponse->assign("hddTipoPago","value",$condicionPago);
		$objResponse->assign("txtTipoPago","value","CRÉDITO");
		
		if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
			$queryClienteCredito = sprintf("SELECT cliente_cred.*
			FROM cj_cc_credito cliente_cred
				INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente_cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
			WHERE cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
				valTpDato($idCliente, "int"),
				valTpDato($idEmpresa, "int"));
			$rsClienteCredito = mysql_query($queryClienteCredito);
			if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsClienteCredito = mysql_num_rows($rsClienteCredito);
			$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
			
			$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
			
			$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$txtDiasCreditoCliente);
			
			$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
		} else {
			$fechaVencimiento = date(spanDateFormat);
			
			$objResponse->assign("txtDiasCreditoCliente","value","0");
		}
		
		$objResponse->script("
		byId('trFormaDePago').style.display = 'none';");
		
	} else if ($condicionPago == 1) { // 0 = Credito, 1 = Contado
		$objResponse->assign("hddTipoPago","value",$condicionPago);
		$objResponse->assign("txtTipoPago","value","CONTADO");
		
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->script("
		byId('trFormaDePago').style.display = '';");
	}
	
	// VERIFICA SI EL CLIENTE TIENE UN ANTICIPO NORMAL, CON CASH BACK / BONO DEALER, TRADE-IN, BONO SUPLIDOR AUN DISPONIBLE PARA ASIGNAR
	// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado)
	$queryAnticipo = sprintf("SELECT *
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		LEFT JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
	WHERE cxc_ant.idCliente = %s
		AND (cxc_ant.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
				WHERE suc.id_empresa_padre = cxc_ant.id_empresa)
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_ant.id_empresa)
			OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
												WHERE suc.id_empresa = cxc_ant.id_empresa))
		AND ((cxc_pago.id_concepto IN (2)
				AND (cxc_ant.saldoAnticipo > 0
					OR (cxc_ant.saldoAnticipo = 0 AND cxc_ant.estadoAnticipo IN (1))))
			OR (cxc_pago.id_concepto IN (1,6,7,8)
				AND cxc_ant.saldoAnticipo > 0)
			OR (cxc_pago.id_concepto IS NULL
				AND cxc_ant.saldoAnticipo > 0))
		AND cxc_ant.estatus = 1;",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	
	if ($totalRowsAnticipo > 0) {
		$objResponse->script("
		byId('trFormaDePago').style.display = '';");
		
		while ($rowAnticipo = mysql_fetch_assoc($rsAnticipo)) {
			// VERIFICA SI EL CLIENTE TIENE UN ANTICIPO CON CASH BACK / BONO DEALER, TRADE-IN, BONO SUPLIDOR, PND AUN DISPONIBLE PARA ASIGNAR
			// (1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida)
			$queryConceptoFormaPago = sprintf("SELECT *
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND forma_pago.idFormaPago = 11
				AND concepto_forma_pago.id_concepto IN (1,2,6,7,8);",
				valTpDato($rowAnticipo['idAnticipo'], "int"));
			$rsConceptoFormaPago = mysql_query($queryConceptoFormaPago);
			if (!$rsConceptoFormaPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsConceptoFormaPago = mysql_num_rows($rsConceptoFormaPago);
			while ($rowConceptoFormaPago = mysql_fetch_assoc($rsConceptoFormaPago)) {
				$arrayConceptoFormaPago[] = $rowConceptoFormaPago['descripcion'];
			}
		}
		
		if ($totalRowsAnticipo > 0) {
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script("$('#trFormaDePago').before('".
			"<tr align=\"left\" id=\"trMsj\">".
				"<td colspan=\"3\">".
					"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo\" width=\"100%\">".
					"<tr>".
						"<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\"/></td>".
						"<td align=\"center\">"."El cliente tiene anticipo(s)".((count($arrayConceptoFormaPago) > 0) ? " con \"".implode(", ",$arrayConceptoFormaPago)."\"" : "")." disponible(s)"."</td>".
					"</tr>".
					"</table>".
				"</td>".
			"</tr>');");
		}
	}
	
	// DATOS DEL CLIENTE
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode(elimCaracter($rowCliente['direccion'],";")));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
	
	// DATOS DEL PEDIDO
	$objResponse->assign("txtIdEmpresa","value",$rowPedido['id_empresa']);
	$objResponse->assign("txtEmpresa","value",$rowPedido['nombre_empresa']);
	$objResponse->assign("txtIdPresupuesto","value",$rowPedido['id_presupuesto']);
	$objResponse->assign("txtNumeroPresupuesto","value",$rowPedido['numeracion_presupuesto']);
	$objResponse->assign("txtIdPedido","value",$rowPedido['id_pedido']);
	$objResponse->assign("txtNumeroPedido","value",$rowPedido['numeracion_pedido']);
	$objResponse->assign("txtFechaFactura","value",date(spanDateFormat));
	$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat, strtotime($fechaVencimiento)));
	$objResponse->assign("hddIdEmpleado","value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowEmpleado['nombre_empleado']));
	$objResponse->call("selectedOption","lstTipoClave",3);
	$objResponse->script("byId('lstTipoClave').onchange = function(){ selectedOption(this.id,'".(3)."'); };");
	$objResponse->assign("hddIdClaveMovimiento","value",$rowPedido['id_clave_movimiento']);
	$objResponse->assign("txtClaveMovimiento","value",utf8_encode($rowPedido['clave'].") ".$rowPedido['descripcion_clave_movimiento']));
	$objResponse->loadCommands(cargaLstCreditoTradeIn($rowConfig207['valor']));
	$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
	
	$objResponse->assign("txtIdFacturaEditada","value",$idFactura);
	if ($idFactura > 0) {
		// BUSCA LOS DATOS DE LA FACTURA
		$queryFactura = sprintf("SELECT *,
			(CASE cxc_fact.estadoFactura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_fact_vent
		FROM cj_cc_encabezadofactura cxc_fact
		WHERE cxc_fact.idFactura = %s
			AND cxc_fact.anulada LIKE 'NO';",
			valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFactura = mysql_fetch_array($rsFactura);
		
		$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
			$rowFactura['idFactura']);
		$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Factura Venta PDF")."\"/></a>", $rowFactura['idFactura']);
		
		$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo4\" width=\"100%\">".
		"<tr align=\"center\">".
			"<td height=\"25\" width=\"25\"><img src=\"../img/iconos/exclamation.png\"/></td>".
			"<td>".
				"<table>".
				"<tr align=\"right\">".
					"<td nowrap=\"nowrap\">"."Edición de la Factura Nro. "."</td>".
					"<td nowrap=\"nowrap\">".$aVerDcto."</td>".
					"<td>".$rowFactura['numeroFactura']."</td>".
				"</tr>".
				"</table>".
			"</td>".
		"</tr>".
		"</table>";
		$objResponse->assign("tdMsjPedido","innerHTML",$html);
	}
	
	$objResponse->assign("txtObservacion","value",utf8_encode($rowPedido['observaciones']));
	
	if (isset($frmListaItemPedido['cbxItm'])) {
		foreach($frmListaItemPedido['cbxItm'] as $indiceItm=>$valorItm) {	
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			
			$hddTpItm = $frmListaItemPedido['hddTpItm'.$valorItm]; // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física
			$hddIdItm = $frmListaItemPedido['hddIdItm'.$valorItm]; // id_unidad_fisica, id_paquete_pedido, id_accesorio_pedido
			
			if (in_array($hddTpItm,array(1,2))) {
				$Result1 = insertarItemAdicional($contFila, $hddIdItm, $hddTpItm);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
			} else if ($hddTpItm == 3) {				
				$Result1 = insertarItemUnidad($contFila, $hddIdItm, $hddTpItm);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
			}
			
			$subtotalFact += $precioItm;
		}
	}
	
	$Result1 = buscarNumeroControl($idEmpresa, $rowPedido['id_clave_movimiento']);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$objResponse->assign("txtNumeroControlFactura","value",($Result1[1]));
	}
	
	$objResponse->script("
	byId('btnCancelarListaItemPedido').onclick = '';
	byId('btnCancelarListaItemPedido').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function insertarPago($frmListaPagos, $frmDetallePago, $frmDeposito, $frmLista, $frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	$contFila = $arrayObj2[count($arrayObj2)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	
	if (str_replace(",", "", $frmTotalDcto['txtTotalOrden']) < str_replace(",", "", $frmDetallePago['txtMontoPago'])) {
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la Factura");
	}
	
    foreach ($arrayObj2 as $indice => $valor){
		$hddIdPago = $frmListaPagos['hddIdPago'.$valor];
		$txtIdFormaPago = $frmListaPagos['txtIdFormaPago'.$valor];
		$txtIdNumeroDctoPago = $frmListaPagos['txtIdNumeroDctoPago'.$valor];
		
        if (!($hddIdPago > 0)
		&& $txtIdFormaPago == $frmDetallePago['selTipoPago']
		&& $txtIdNumeroDctoPago > 0 && $txtIdNumeroDctoPago == $frmDetallePago['hddIdAnticipoNotaCreditoChequeTransferencia']) {
			return $objResponse->alert("El documento seleccionado ya se encuentra agregado");
        }
    }
	
	$idFormaPago = $frmDetallePago['selTipoPago'];
	$txtIdNumeroDctoPago = $frmDetallePago['hddIdAnticipoNotaCreditoChequeTransferencia'];
	$txtNumeroDctoPago = $frmDetallePago['txtNumeroDctoPago'];
	$txtIdBancoCliente = $frmDetallePago['selBancoCliente'];
	$txtCuentaClientePago = $frmDetallePago['txtNumeroCuenta'];
	$txtIdBancoCompania = $frmDetallePago['selBancoCompania'];
	$txtIdCuentaCompaniaPago = $frmDetallePago['selNumeroCuenta'];
	$txtFechaDeposito = $frmDetallePago['txtFechaDeposito'];
	$lstTipoTarjeta = $frmDetallePago['tarjeta'];
	$porcRetencion = $frmDetallePago['porcentajeRetencion'];
	$montoRetencion = $frmDetallePago['montoTotalRetencion'];
	$porcComision = $frmDetallePago['porcentajeComision'];
	$montoComision = $frmDetallePago['montoTotalComision'];
	$txtMontoPago = str_replace(",", "", $frmDetallePago['txtMontoPago']);
	
	$Result1 = insertarItemMetodoPago($contFila, $idFormaPago, $txtIdNumeroDctoPago, $txtNumeroDctoPago, $txtIdBancoCliente, $txtCuentaClientePago, $txtIdBancoCompania, $txtIdCuentaCompaniaPago, $txtFechaDeposito, $lstTipoTarjeta, $porcRetencion, $montoRetencion, $porcComision, $montoComision, $txtMontoPago);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj2[] = $contFila;
	}
	
	if ($idFormaPago == 3) { // 3 = Deposito
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = explode("|", $frmDeposito['hddObjDetallePagoDeposito']);
		
		$cadenaFormaPagoDeposito = "";
		$cadenaNroDocumentoDeposito = "";
		$cadenaBancoClienteDeposito = "";
		$cadenaNroCuentaDeposito = "";
		$cadenaMontoDeposito = "";
		foreach($arrayObj as $indice => $valor) {
			if (isset($frmDeposito['txtIdFormaPagoDetalleDeposito'.$valor])) {
				$cadenaPosicionDeposito .= $contFila."|";
				$cadenaFormaPagoDeposito .= $frmDeposito['txtIdFormaPagoDetalleDeposito'.$valor]."|";		
				$cadenaNroDocumentoDeposito .= $frmDeposito['txtNumeroDocumentoDetalleDeposito'.$valor]."|";
				$cadenaBancoClienteDeposito .= $frmDeposito['txtIdBancoClienteDetalleDeposito'.$valor]."|";
				$cadenaNroCuentaDeposito .= $frmDeposito['txtNumeroCuentaDetalleDeposito'.$valor]."|";
				$cadenaMontoDeposito .= $frmDeposito['txtMontoDetalleDeposito'.$valor]."|";
			}
		}
		$cadenaPosicionDeposito = $frmDetallePago['hddObjDetalleDeposito'].$cadenaPosicionDeposito;
		$cadenaFormaPagoDeposito = $frmDetallePago['hddObjDetalleDepositoFormaPago'].$cadenaFormaPagoDeposito;
		$cadenaBancoClienteDeposito = $frmDetallePago['hddObjDetalleDepositoBanco'].$cadenaBancoClienteDeposito;
		$cadenaNroCuentaDeposito = $frmDetallePago['hddObjDetalleDepositoNroCuenta'].$cadenaNroCuentaDeposito;
		$cadenaNroDocumentoDeposito = $frmDetallePago['hddObjDetalleDepositoNroCheque'].$cadenaNroDocumentoDeposito;
		$cadenaMontoDeposito = $frmDetallePago['hddObjDetalleDepositoMonto'].$cadenaMontoDeposito;
		
		$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosicionDeposito);
		$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPagoDeposito);
		$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBancoClienteDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuentaDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroDocumentoDeposito);
		$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMontoDeposito);
	} else if ($idFormaPago == 7) { // 7 = Anticipo
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT cxc_nc.*
		FROM an_tradein_cxc tradein_cxc
			INNER JOIN cj_cc_notacredito cxc_nc ON (tradein_cxc.id_nota_credito_cxc = cxc_nc.idNotaCredito)
		WHERE tradein_cxc.id_anticipo = %s;",
			valTpDato($txtIdNumeroDctoPago, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			if ($rowTradeInNotaCredito['saldoNotaCredito'] > 0) {
				$Result1 = insertarItemMetodoPago($contFila, 8, $rowTradeInNotaCredito['idNotaCredito'], $rowTradeInNotaCredito['numeracion_nota_credito'], "", "", "", "", "", "", "", "", "", "", $rowTradeInNotaCredito['saldoNotaCredito']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj2[] = $contFila;
				}
			}
		}
	} else if ($idFormaPago == 8) { // 8 = Nota de Crédito
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_nota_credito_cxc = %s;",
			valTpDato($txtIdNumeroDctoPago, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		$rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito);
		
		if ($totalRowsTradeInNotaCredito > 0) {
			$idFormaPago = 7; // // 7 = Anticipo
		}
	}
	
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObj2) > 0) ? implode("|",$arrayObj2) : ""));
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'))");
	
	switch ($idFormaPago) {
		case 2 : // 2 = CHEQUE
			if ($txtIdNumeroDctoPago > 0) {
				$objResponse->loadCommands(cargaLstTipoPago("","2"));
				$objResponse->call(asignarTipoPago,"2");
				$objResponse->script("
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
				byId('imgCerrarDivFlotante2').click();");
			} else {
				$objResponse->loadCommands(cargaLstTipoPago("","1"));
				$objResponse->call(asignarTipoPago,"1");
			}
			break;
		case 3 : // 3 = DEPOSITO
			$objResponse->loadCommands(cargaLstTipoPago("","1"));
			$objResponse->call(asignarTipoPago,"1");
			$objResponse->script("
			byId('imgCerrarDivFlotante1').click();"); break;
		case 4 : // 4 = TRANSFERENCIA
			if ($txtIdNumeroDctoPago > 0) {
				$objResponse->loadCommands(cargaLstTipoPago("","4"));
				$objResponse->call(asignarTipoPago,"4");
				$objResponse->script("
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
				byId('imgCerrarDivFlotante2').click();");
			} else {
				$objResponse->loadCommands(cargaLstTipoPago("","1"));
				$objResponse->call(asignarTipoPago,"1");
			}
			break;
		case 7 : // 7 = ANTICIPO
			$objResponse->loadCommands(cargaLstTipoPago("","7"));
			$objResponse->call(asignarTipoPago,"7");
			/*$objResponse->loadCommands(listaAnticipoNotaCreditoChequeTransferencia(
				$frmLista['pageNum'],
				$frmLista['campOrd'],
				$frmLista['tpOrd'],
				$frmLista['valBusq']));*/
			$objResponse->script("
			byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
			byId('imgCerrarDivFlotante2').click();"); break;
		case 8 : // 8 = NOTA CREDITO
			$objResponse->loadCommands(cargaLstTipoPago("","8"));
			$objResponse->call(asignarTipoPago,"8");
			$objResponse->script("
			byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
			byId('imgCerrarDivFlotante2').click();"); break;
		default:
			$objResponse->loadCommands(cargaLstTipoPago("","1"));
			$objResponse->call(asignarTipoPago,"1");
	}
	
	return $objResponse;
}

function insertarPagoDeposito($frmDeposito) {
	$objResponse = new xajaxResponse();
		
	if (str_replace(",", "", $frmDeposito['txtMontoDeposito']) > str_replace(",", "", $frmDeposito['txtSaldoDepositoBancario'])) {
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo del Deposito.");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	$contFila = $arrayObj3[count($arrayObj3)-1] + 1;
	
	if ($frmDeposito['lstTipoPago'] == 1) {
		$tipoPago = "Efectivo";
		$bancoCliente = "-";
		$numeroCuenta = "-";
		$numeroControl = "-";
		$montoPagado = str_replace(",", "", $frmDeposito['txtMontoDeposito']);
		$bancoClienteOculto = "-";
	} else if ($frmDeposito['lstTipoPago'] == 2) {
		$tipoPago = "Cheque";
		$bancoCliente = asignarBanco($frmDeposito['lstBancoDeposito']);
		$numeroCuenta = $frmDeposito['txtNroCuentaDeposito'];
		$numeroControl = $frmDeposito['txtNroChequeDeposito'];
		$montoPagado = str_replace(",", "", $frmDeposito['txtMontoDeposito']);
		$bancoClienteOculto = $frmDeposito['lstBancoDeposito'];
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$objResponse->script(sprintf("$('#trItmPieDeposito').before('".
		"<tr align=\"left\" id=\"trItmDetalle:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmDetalle:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx3\" name=\"cbx3[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMontoDetalleDeposito%s\" name=\"txtMontoDetalleDeposito%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><button type=\"button\" onclick=\"confirmarEliminarPagoDetalleDeposito(%s);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"/></button>".
				"<input type=\"hidden\" id=\"txtIdFormaPagoDetalleDeposito%s\" name=\"txtIdFormaPagoDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtNumeroDocumentoDetalleDeposito%s\" name=\"txtNumeroDocumentoDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoClienteDetalleDeposito%s\" name=\"txtIdBancoClienteDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtNumeroCuentaDetalleDeposito%s\" name=\"txtNumeroCuentaDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$tipoPago,
			$bancoCliente,
			$numeroCuenta,
			$numeroControl,
			$contFila, $contFila, number_format($montoPagado, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $frmDeposito['lstTipoPago'],
				$contFila, $contFila, $numeroControl,
				$contFila, $contFila, $bancoClienteOculto,
				$contFila, $contFila, $numeroCuenta,
				$contFila, $contFila, $montoPagado));
	
	$objResponse->script("
	xajax_cargaLstTipoPagoDetalleDeposito('1');
	asignarTipoPagoDetalleDeposito('1');");
	
	$objResponse->script("xajax_calcularPagosDeposito(xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmDetallePago'))");
	
	return $objResponse;
}

function listaAnticipoNotaCreditoChequeTransferencia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	if ($valCadBusq[2] == 2) { // CHEQUES
		$campoIdCliente = "id_cliente";
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$campoIdCliente = "id_cliente";
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$campoIdCliente = "idCliente";
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$campoIdCliente = "idCliente";
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(dcto.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = dcto.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = dcto.id_empresa)
	OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = dcto.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[2] == 2) { // CHEQUES
		// 1 = Normal, 2 = Bono Suplidor, 3 = PND
		$sqlBusq .= $cond.sprintf("(id_departamento IN (%s)
		AND (%s = %s AND dcto.tipo_cheque = 1) OR dcto.tipo_cheque IN (2,3))",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		// 1 = Normal, 2 = Bono Suplidor, 3 = PND
		$sqlBusq .= $cond.sprintf("(id_departamento IN (%s)
		AND (%s = %s AND dcto.tipo_transferencia = 1) OR dcto.tipo_transferencia IN (2,3))",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$sqlBusq .= $cond.sprintf("(idDepartamento IN (%s)
		AND %s = %s)",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$sqlBusq .= $cond.sprintf("(idDepartamentoNotaCredito IN (%s)
		AND %s = %s)",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[2] == 2) { // CHEQUES
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado
		$sqlBusq .= $cond.sprintf("estatus IN (1,2) AND saldo_cheque > 0 AND estatus = 1"); // 1 = tipo cliente
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$sqlBusq .= $cond.sprintf("estatus IN (1,2) AND saldo_transferencia > 0 AND estatus = 1");//1 = tipo cliente
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado)
		$sqlBusq .= $cond.sprintf("estadoAnticipo IN (0,1,2) AND estatus = 1");
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado
		$sqlBusq .= $cond.sprintf("estadoNotaCredito IN (1,2)");
	}
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 2) { // CHEQUES
			$sqlBusq .= $cond.sprintf("(dcto.numero_cheque LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
			$sqlBusq .= $cond.sprintf("(dcto.numero_transferencia LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		} else if ($valCadBusq[2] == 7) { // ANTICIPOS
			$sqlBusq .= $cond.sprintf("(numeroAnticipo LIKE %s
			OR cxc_ant.observacionesAnticipo LIKE %s)",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf("(numeracion_nota_credito LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		}
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 2) { // CHEQUES
			$sqlBusq .= $cond.sprintf("dcto.id_cheque NOT IN (%s) ",
				valTpDato($valCadBusq[3], "campo"));
		} else if ($valCadBusq[2] == 4) { // TRANSFERENCIA
			$sqlBusq .= $cond.sprintf("dcto.id_transferencia NOT IN (%s) ",
				valTpDato($valCadBusq[3], "campo"));
		} else if ($valCadBusq[2] == 7) { // ANTICIPOS
			$sqlBusq .= $cond.sprintf("idAnticipo NOT IN (%s)",
				valTpDato($valCadBusq[3], "campo"));
		} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf("idNotaCredito NOT IN (%s) ",
				valTpDato($valCadBusq[3], "campo"));
		}
	}
	
	if ($valCadBusq[2] == 2) { // CHEQUES
		$query = sprintf("SELECT
			dcto.id_cliente AS idCliente,
			dcto.id_departamento AS id_modulo,
			dcto.id_cheque AS idDocumento,
			dcto.saldo_cheque AS saldoDocumento,
			dcto.numero_cheque AS numeroDocumento,
			dcto.fecha_cheque AS fechaDocumento,
			dcto.observacion_cheque AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_cheque dcto 
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$query = sprintf("SELECT
			dcto.id_cliente AS idCliente,
			dcto.id_departamento AS id_modulo,
			dcto.id_transferencia AS idDocumento,
			dcto.saldo_transferencia AS saldoDocumento,
			dcto.numero_transferencia AS numeroDocumento,
			dcto.fecha_transferencia AS fechaDocumento,
			dcto.observacion_transferencia AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_transferencia dcto 
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$query = sprintf("SELECT
			dcto.idAnticipo AS idDocumento,
			dcto.idDepartamento AS id_modulo,
			dcto.saldoAnticipo AS saldoDocumento,
			dcto.numeroAnticipo AS numeroDocumento,
			dcto.fechaAnticipo AS fechaDocumento,
			dcto.observacionesAnticipo AS observacionDocumento,
		
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = dcto.idAnticipo
				AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_anticipo dcto
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$query = sprintf("SELECT
			dcto.idNotaCredito AS idDocumento,
			dcto.idDepartamentoNotaCredito AS id_modulo,
			dcto.saldoNotaCredito AS saldoDocumento,
			dcto.numeracion_nota_credito AS numeroDocumento,
			dcto.fechaNotaCredito AS fechaDocumento,
			dcto.observacionesNotaCredito AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notacredito dcto
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	}
			
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "10%", $pageNum, "fechaDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "14%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Documento"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "42%", $pageNum, "observacionDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Observaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "20%", $pageNum, "saldoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		$onClick = sprintf("abrirDivFlotante2(this, 'tblAnticipoNotaCreditoChequeTransferencia', '%s', '%s');",
			$valCadBusq[2],
			$row['idDocumento']);
		
		if ($valCadBusq[2] == 7) { // 7 = Anticipo
			$idAnticipo = $row['idDocumento'];
			// BUSCA EL TIPO DEL ANTICIPO
			$queryAnticipo = sprintf("SELECT *,
				(CASE
					WHEN (cxc_pago.id_concepto = 2) THEN
						IF (cxc_ant.saldoAnticipo > (SELECT tradein.total_credito FROM an_tradein tradein
													WHERE tradein.id_anticipo = cxc_ant.idAnticipo
														AND tradein.anulado IS NULL) AND cxc_ant.saldoAnticipo > 0,
							(SELECT tradein.total_credito FROM an_tradein tradein
							WHERE tradein.id_anticipo = cxc_ant.idAnticipo
								AND tradein.anulado IS NULL),
							cxc_ant.saldoAnticipo)
				END) AS saldo_anticipo
			FROM cj_cc_anticipo cxc_ant
				LEFT JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
				LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				LEFT JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND (cxc_pago.tipoPagoDetalleAnticipo LIKE 'OT'
					OR cxc_ant.estadoAnticipo IN (0));",
				valTpDato($idAnticipo, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
			while ($rowAnticipo = mysql_fetch_array($rsAnticipo)) {
				// 1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida
				if ((in_array($rowAnticipo['id_concepto'],array(2))
					&& ($rowAnticipo['saldo_anticipo'] > 0 || ($rowAnticipo['saldo_anticipo'] == 0 && $rowAnticipo['estadoAnticipo'] == 1)))
				|| ((in_array($rowAnticipo['id_concepto'],array(1,6,7,8)) || in_array($rowAnticipo['estadoAnticipo'],array(0))) && $rowAnticipo['saldo_anticipo'] > 0)) {
					$onClick = sprintf("
					byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = '%s';
					byId('txtNumeroDctoPago').value = '%s';
					byId('txtMontoPago').value = '%s';
					
					xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));",
						$idAnticipo,
						$rowAnticipo['numeroAnticipo'],
						$rowAnticipo['saldo_anticipo']);
				}
			}
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDcto%s\" rel=\"#divFlotante2\" onclick=\"%s\"><button type=\"button\" title=\"Seleccionar\"><img class=\"puntero\" src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$onClick);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".$imgDctoModulo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaDocumento']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroDocumento']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</span></td></tr>" : "";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['observacionDocumento'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoDocumento'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"calcularPagosDeposito");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstCreditoTradeIn");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuentaCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"formDeposito");
$xajax->register(XAJAX_FUNCTION,"formItemsPedido");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarItem");
$xajax->register(XAJAX_FUNCTION,"insertarPago");
$xajax->register(XAJAX_FUNCTION,"insertarPagoDeposito");
$xajax->register(XAJAX_FUNCTION,"listaAnticipoNotaCreditoChequeTransferencia");

function actualizarNumeroControl($idEmpresa, $idClaveMovimiento){
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
			
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	return array(true, "");
}

function asignarBanco($idBanco) {
	$query = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s;", valTpDato($idBanco, "int"));
	$rs = mysql_query($query) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	return utf8_encode($row['nombreBanco']);
}

function asignarNumeroCuenta($idCuenta) {
	$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s;", valTpDato($idCuenta, "int"));
	$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	
	return $rowBuscarNumeroCuenta['numeroCuentaCompania'];
}

function buscarNumeroControl($idEmpresa, $idClaveMovimiento){
	// VERIFICA VALORES DE CONFIGURACION (Formato Nro. Control)
	$queryConfig401 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 401 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig401 = mysql_query($queryConfig401);
	if (!$rsConfig401) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig401 = mysql_num_rows($rsConfig401);
	$rowConfig401 = mysql_fetch_assoc($rsConfig401);
	
	if (!($totalRowsConfig401 > 0)) return array(false, "No existe un formato de numero de control establecido");
		
	$valor = explode("|",$rowConfig401['valor']);
	$separador = $valor[0];
	$formato = (strlen($separador) > 0) ? explode($separador,$valor[1]) : $valor[1];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	if (strlen($separador) > 0 && isset($formato)) {
		foreach($formato as $indice => $valor) {
			$numeroActualFormato[] = ($indice == count($formato)-1) ? str_pad($numeroActual,strlen($valor),"0",STR_PAD_LEFT) : str_pad(0,strlen($valor),"0",STR_PAD_LEFT);
		}
		$numeroActualFormato = implode($separador, $numeroActualFormato);
	} else {
		$numeroActualFormato = str_pad($numeroActual,strlen($formato),"0",STR_PAD_LEFT);
	}

	return array(true, $numeroActualFormato);
}

function cargaLstSumarPagoItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$array = array(
		"" => array("abrev" => "-", "descripcion" => "-"),
		"1" => array("abrev" => "C", "descripcion" => "Pago de Contado"),
		"2" => array("abrev" => "T", "descripcion" => "Trade In"));
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:40px\">";
	foreach ($array as $indice => $valor) {
		$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
		$html .= "<optgroup label=\"".utf8_encode($valor['descripcion'])."\">";
			$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$indice."\">".($valor['abrev'])."</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	
	return $html;
}

function guardarNotaCargoCxC($idFactura, $idFacturaDetAccesorio, $idCliente, $idMotivo, $idTipoComision, $porcComision, $montoComision) {
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowFacturas = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = $rowFactura['id_empresa'];
	
	// BUSCA LOS DATOS DEL ADICIONAL
	$queryFacturaDetAccesorio = sprintf("SELECT *
	FROM cj_cc_factura_detalle_accesorios fact_det_acc
		INNER JOIN an_accesorio acc ON (fact_det_acc.id_accesorio = acc.id_accesorio)
	WHERE id_factura_detalle_accesorios = %s;",
		valTpDato($idFacturaDetAccesorio, "int"));
	$rsFacturaDetAccesorio = mysql_query($queryFacturaDetAccesorio);
	if (!$rsFacturaDetAccesorio) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowFacturaDetAccesorio = mysql_num_rows($rsFacturaDetAccesorio);
	$rowFacturaDetAccesorio = mysql_fetch_assoc($rsFacturaDetAccesorio);
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE (emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
			OR emp_num.id_numeracion = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(24, "int"), // 24 = Nota de Cargo CxC
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$numeroActualControl = $numeroActual;
	
	$tipoComision = ($idTipoComision == 1) ? $porcComision."%" : cAbrevMoneda.$montoComision;
	$txtFechaRegistro = $rowFactura['fechaRegistroFactura'];
	$idModulo = $rowFactura['idDepartamentoOrigenFactura'];
	$lstTipoPago = 0; // 0 = Credito, 1 = Contado
	$txtFechaVencimiento = ($lstTipoPago == 1) ? $txtFechaRegistro : date(spanDateFormat, strtotime($txtFechaRegistro) + 2592000);
	$txtDiasCreditoCliente = (strtotime($txtFechaVencimiento) - strtotime($txtFechaRegistro)) / 86400;
	$txtTotalNotaCargo = ($idTipoComision == 1) ? ($porcComision * $rowFacturaDetAccesorio['precio_unitario']) / 100 : $montoComision;
	$txtSubTotalDescuento = 0;
	$txtFlete = 0;
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
	$txtMontoExento = $txtTotalNotaCargo;
	$txtMontoExonerado = 0;
	$txtObservacion = "NOTA DE CARGO POR COMISION DE ".$tipoComision." DEL ADICIONAL (".$rowFacturaDetAccesorio['nom_accesorio'].") PERTENECIENTE A LA FACTURA NRO. ".$rowFactura['numeroFactura'];
	
	// INSERTA LA NOTA DE CREDITO
	$insertSQL = sprintf("INSERT INTO cj_cc_notadecargo (numeroControlNotaCargo, fechaRegistroNotaCargo, numeroNotaCargo, fechaVencimientoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, estadoNotaCargo, observacionNotaCargo, fletesNotaCargo, idCliente, idDepartamentoOrigenNotaCargo, descuentoNotaCargo, baseImponibleNotaCargo, porcentajeIvaNotaCargo, calculoIvaNotaCargo, subtotalNotaCargo, interesesNotaCargo, tipoNotaCargo, base_imponible_iva_lujo, porcentaje_iva_lujo, ivaLujoNotaCargo, diasDeCreditoNotaCargo, montoExentoNotaCargo, montoExoneradoNotaCargo, aplicaLibros, referencia_nota_cargo, id_empresa, id_motivo)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActualControl, "text"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistro)), "date"),
		valTpDato($numeroActual, "text"),
		valTpDato(date("Y-m-d", strtotime($txtFechaVencimiento)), "date"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato("0", "int"), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
		valTpDato($txtObservacion, "text"),
		valTpDato($txtFlete, "real_inglesa"),
		valTpDato($idCliente, "int"),
		valTpDato($idModulo, "int"),
		valTpDato($txtSubTotalDescuento, "real_inglesa"),
		valTpDato($txtBaseImponibleIva, "real_inglesa"),
		valTpDato($txtIva, "real_inglesa"),
		valTpDato($txtSubTotalIva, "real_inglesa"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($lstTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
		valTpDato($txtIvaLujo, "real_inglesa"),
		valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
		valTpDato($txtDiasCreditoCliente, "int"),
		valTpDato($txtMontoExento, "real_inglesa"),
		valTpDato($txtMontoExonerado, "real_inglesa"),
		valTpDato(0, "boolean"), // 0 = No, 1 = Si
		valTpDato(1, "int"), // 0 = Cheque Devuelto, 1 = Otros
		valTpDato($idEmpresa, "int"),
		valTpDato($idMotivo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaCargo = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUES (%s, %s, %s, %s);",
		valTpDato("ND", "text"),
		valTpDato($idNotaCargo, "int"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistro)), "date"),
		valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC, 5 = CH, 6 = TB
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	return array(true, $idNotaCargo, $idModulo);
}

function guardarNotaCreditoCxC($idFactura) {
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT cxc_fact.*,
		(SELECT clave_mov.id_clave_movimiento_contra FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.id_clave_movimiento = cxc_fact.id_clave_movimiento) AS id_clave_movimiento_contra
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFact = mysql_fetch_array($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura'];
	$idClaveMovimiento = $rowFact['id_clave_movimiento_contra'];
	
	// INSERTA LOS DATOS DE LA NOTA DE CREDITO
	$updateSQL = sprintf("INSERT INTO cj_cc_notacredito (id_empresa, idCliente, numeracion_nota_credito, numeroControl, fechaNotaCredito, idDepartamentoNotaCredito, id_empleado_vendedor, id_clave_movimiento, idDocumento, tipoDocumento, estadoNotaCredito, montoNetoNotaCredito, saldoNotaCredito, observacionesNotaCredito, subtotalNotaCredito, porcentaje_descuento, subtotal_descuento, baseimponibleNotaCredito, porcentajeIvaNotaCredito, ivaNotaCredito, base_imponible_iva_lujo, porcentajeIvaDeLujoNotaCredito, ivaLujoNotaCredito, montoExentoCredito, montoExoneradoCredito, estatus_nota_credito, aplicaLibros)
	SELECT
		cxc_fa.id_empresa,
		cxc_fa.idCliente,
		cxc_fa.numeroFactura,
		cxc_fa.numeroControl,
		cxc_fa.fechaRegistroFactura,
		cxc_fa.idDepartamentoOrigenFactura,
		cxc_fa.idVendedor,
		%s,
		cxc_fa.idFactura,
		'FA',
		%s,
		cxc_fa.montoTotalFactura,
		cxc_fa.montoTotalFactura,
		%s,
		cxc_fa.subtotalFactura,
		cxc_fa.porcentaje_descuento,
		cxc_fa.descuentoFactura,
		cxc_fa.baseImponible,
		cxc_fa.porcentajeIvaFactura,
		cxc_fa.calculoIvaFactura,
		cxc_fa.base_imponible_iva_lujo,
		cxc_fa.porcentajeIvaDeLujoFactura,
		cxc_fa.calculoIvaDeLujoFactura,
		cxc_fa.montoExento,
		cxc_fa.montoExonerado,
		%s,
		%s
	FROM cj_cc_encabezadofactura cxc_fa
	WHERE cxc_fa.idFactura = %s;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato("EDICION DE LA FACTURA", "text"),
		valTpDato(2, "int"), // 1 = Aprobada, 2 = Aplicada
		valTpDato(0, "int"), // 0 = No, 1 = Si
		valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idNotaCredito,
		$idModulo,
		"NOTA_CREDITO");
	
	// VERIFICA SI LA FACTURA FUE AGREGADA POR VENTA DE VEHICULO O POR CUENTAS POR COBRAR
	$queryFacturaVehiculo = sprintf("SELECT
		cxc_fact.idFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroPedido,
		uni_fis.id_unidad_fisica,
		uni_fis.id_uni_bas
	FROM an_pedido ped_vent
		INNER JOIN an_factura_venta cxc_fact ON (ped_vent.id_pedido = cxc_fact.numeroPedido)
		INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
	WHERE cxc_fact.idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFacturaVehiculo = mysql_query($queryFacturaVehiculo);
	if (!$rsFacturaVehiculo) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsFacturaVehiculo = mysql_num_rows($rsFacturaVehiculo);
	$rowFacturaVehiculo = mysql_fetch_array($rsFacturaVehiculo);
	
	if ($totalRowsFacturaVehiculo > 0) { // FUE AGREGADA POR VENTAS DE VEHÍCULOS
		// INSERTA LOS VEHICULOS DEVUELTOS
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo (id_nota_credito, id_unidad_fisica, costo_compra, precio_unitario, id_iva, iva)
		SELECT %s, id_unidad_fisica, costo_compra, precio_unitario, id_iva, iva FROM cj_cc_factura_detalle_vehiculo cxc_fa_det_vehic
		WHERE cxc_fa_det_vehic.id_factura = %s;",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCreditoDetalleVehiculo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// BUSCA LOS VEHICULOS EN EL DETALLE
		$queryNCDetVehic = sprintf("SELECT * FROM cj_cc_nota_credito_detalle_vehiculo WHERE id_nota_credito = %s;",
			valTpDato($idNotaCredito, "int"));
		$rsNCDetVehic = mysql_query($queryNCDetVehic);
		if (!$rsNCDetVehic) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNCDetVehic = mysql_num_rows($rsNCDetVehic);
		while ($rowNCDetVehic = mysql_fetch_array($rsNCDetVehic)) {
			// INSERTA LOS IMPUESTOS DE LOS VEHICULOS DEVUELTOS
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo_impuesto (id_nota_credito_detalle_vehiculo, id_impuesto, impuesto)
			SELECT
				%s,
				cxc_fa_det_vehic_impuesto.id_impuesto,
				cxc_fa_det_vehic_impuesto.impuesto
			FROM cj_cc_factura_detalle_vehiculo cxc_fa_det_vehic
				INNER JOIN cj_cc_factura_detalle_vehiculo_impuesto cxc_fa_det_vehic_impuesto ON (cxc_fa_det_vehic.id_factura_detalle_vehiculo = cxc_fa_det_vehic_impuesto.id_factura_detalle_vehiculo)
			WHERE cxc_fa_det_vehic.id_unidad_fisica = %s
				AND cxc_fa_det_vehic.id_factura = %s;",
				valTpDato($rowNCDetVehic['id_nota_credito_detalle_vehiculo'], "int"),
				valTpDato($rowNCDetVehic['id_unidad_fisica'], "int"),
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		
		// INSERTA LOS ACCESORIOS DEVUELTOS
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios (id_nota_credito, id_accesorio, id_tipo_accesorio, cantidad, costo_compra, precio_unitario, id_iva, iva, tipo_accesorio)
		SELECT %s, id_accesorio, id_tipo_accesorio, cantidad, costo_compra, precio_unitario, id_iva, iva, tipo_accesorio FROM cj_cc_factura_detalle_accesorios cxc_fa_det_acc
		WHERE cxc_fa_det_acc.id_factura = %s;",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCreditoDetalleAccesorio = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// BUSCA LOS ACCESORIOS EN EL DETALLE
		$queryNCDetAcc = sprintf("SELECT * FROM cj_cc_nota_credito_detalle_accesorios WHERE id_nota_credito = %s;",
			valTpDato($idNotaCredito, "int"));
		$rsNCDetAcc = mysql_query($queryNCDetAcc);
		if (!$rsNCDetAcc) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNCDetAcc = mysql_num_rows($rsNCDetAcc);
		while ($rowNCDetAcc = mysql_fetch_array($rsNCDetAcc)) {
			// INSERTA LOS IMPUESTOS DE LOS ACCESORIOS DEVUELTOS
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios_impuesto (id_nota_credito_detalle_accesorios, id_impuesto, impuesto)
			SELECT
				%s,
				cxc_fa_det_acc_impuesto.id_impuesto,
				cxc_fa_det_acc_impuesto.impuesto
			FROM cj_cc_factura_detalle_accesorios cxc_fa_det_acc
				INNER JOIN cj_cc_factura_detalle_accesorios_impuesto cxc_fa_det_acc_impuesto ON (cxc_fa_det_acc.id_factura_detalle_accesorios = cxc_fa_det_acc_impuesto.id_factura_detalle_accesorios)
			WHERE cxc_fa_det_acc.id_accesorio = %s
				AND cxc_fa_det_acc.id_factura = %s;",
				valTpDato($rowNCDetAcc['id_nota_credito_detalle_accesorios'], "int"),
				valTpDato($rowNCDetAcc['id_accesorio'], "int"),
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		
		// BUSCA SI LA FACTURA A DEVOLVER TIENE UNA UNIDAD
		$queryFADetVehic = sprintf("SELECT * FROM cj_cc_factura_detalle_vehiculo WHERE id_factura = %s;",
			valTpDato($idFactura, "int"));
		$rsFADetVehic = mysql_query($queryFADetVehic);
		if (!$rsFADetVehic) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFADetVehic = mysql_num_rows($rsFADetVehic);
		while ($rowFADetVehic = mysql_fetch_array($rsFADetVehic)) {
			$idUnidadFisica = $rowFADetVehic['id_unidad_fisica'];
			
			// REGISTRA EL MOVIMIENTO DE LA UNIDAD
			$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
			SELECT %s, idUnidadBasica, idUnidadFisica, %s, %s, %s, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, %s, %s FROM an_kardex kardex
			WHERE kardex.id_documento = %s
				AND kardex.idUnidadFisica = %s
				AND kardex.tipoMovimiento = 3;",
				valTpDato($idNotaCredito, "int"),
				valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
				valTpDato($idClaveMovimiento, "int"),
				valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
				valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
				valTpDato("DATE_ADD(fechaMovimiento, INTERVAL 1 SECOND)", "campo"),
				valTpDato($idFactura, "int"),
				valTpDato($idUnidadFisica, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA EL ESTADO DE VENTA DEL VEHÍCULO
			$updateSQL = sprintf("UPDATE an_unidad_fisica SET
				estado_venta = 'DISPONIBLE',
				fecha_pago_venta = '0000-00-00'
			WHERE id_unidad_fisica = %s;",
				valTpDato($idUnidadFisica, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA EN VEHICULOS
		$updateSQL = sprintf("UPDATE an_factura_venta SET
			anulada = 'SI'
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ACTUALIZA LOS DATOS DEL PEDIDO
		$updateSQL = sprintf("UPDATE an_pedido SET
			estado_pedido = 4
		WHERE id_pedido = %s;",
			valTpDato($rowFact['numeroPedido'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	SELECT %s, %s, cxc_fa.fechaRegistroFactura, %s FROM cj_cc_encabezadofactura cxc_fa
	WHERE cxc_fa.idFactura = %s;",
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato("4", "int"), // 1 = FA, 2 = ND, 3 = AN, 4 = NC, 5 = CH, 6 = TB
		valTpDato($idFactura, "int"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// CALCULO DE LAS COMISIONES
	$Result1 = devolverComision($idNotaCredito);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
	
	// VERIFICA SI LA FACTURA TIENE COMO PAGO UN ANTICIPO CON CASH BACK / BONO DEALER, TRADE-IN, BONO SUPLIDOR, PND, O SIN CANCELAR
	// (1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida)
	$queryAnticipo = sprintf("SELECT DISTINCT cxc_pago_an.*
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago),
		an_pagos cxc_pago_an
	WHERE cxc_pago_an.id_factura = %s
		AND ((cxc_pago_an.numeroDocumento = cxc_ant.idAnticipo
				AND (cxc_pago.id_concepto IN (2)
					OR cxc_pago.id_concepto IN (1,6,7,8)
					OR cxc_ant.totalPagadoAnticipo < cxc_ant.montoNetoAnticipo))
			OR cxc_pago_an.estatus = 2)",
		valTpDato($idFactura, "int"));
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	if ($totalRowsAnticipo > 0) {
		while($rowAnticipo = mysql_fetch_assoc($rsAnticipo)) {
			$idAnticipo = $rowAnticipo['numeroDocumento'];
			
			// ANULA EL PAGO
			$updateSQL = sprintf("UPDATE an_pagos SET
				estatus = NULL,
				fecha_anulado = %s,
				id_empleado_anulado = %s
			WHERE idPago = %s;",
				valTpDato("NOW()", "campo"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($rowAnticipo['idPago'], "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// ACTUALIZA EL SALDO Y EL MONTO PAGADO DEL ANTICIPO
			$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
				saldoAnticipo = montoNetoAnticipo,
				totalPagadoAnticipo = IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
												WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
													AND (cxc_pago.id_forma_pago NOT IN (11)
														OR (cxc_pago.id_forma_pago IN (11) AND cxc_pago.id_concepto NOT IN (6,7,8)))
													AND cxc_pago.estatus IN (1,2)), 0)
			WHERE cxc_ant.idAnticipo = %s;",
				valTpDato($idAnticipo, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
			// ACTUALIZA EL SALDO DEL ANTICIPO SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
			$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
				saldoAnticipo = saldoAnticipo
									- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
												WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
													AND cxc_pago.formaPago IN (7)
													AND cxc_pago.estatus IN (1,2)), 0)
										+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
													WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
														AND cxc_pago.formaPago IN (7)
														AND cxc_pago.estatus IN (1,2)), 0)
										+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
													WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
														AND cxc_pago.idFormaPago IN (7)
														AND cxc_pago.estatus IN (1,2)), 0))
			WHERE cxc_ant.idAnticipo = %s;",
				valTpDato($idAnticipo, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// ACTUALIZA EL ESTATUS DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
			$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
				estadoAnticipo = (CASE
									WHEN (ROUND(montoNetoAnticipo, 2) > ROUND(totalPagadoAnticipo, 2)
										AND ROUND(saldoAnticipo, 2) > 0) THEN
										0
									WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
										AND ROUND(saldoAnticipo, 2) <= 0
										AND cxc_ant.idAnticipo IN (SELECT * 
																	FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																		WHERE cxc_pago.formaPago IN (7)
																			AND cxc_pago.estatus IN (1)
																		
																		UNION
																		
																		SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																		WHERE cxc_pago.formaPago IN (7)
																			AND cxc_pago.estatus IN (1)
																		
																		UNION
																		
																		SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																		WHERE cxc_pago.idFormaPago IN (7)
																			AND cxc_pago.estatus IN (1)) AS q)) THEN
										3
									WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
										AND ROUND(montoNetoAnticipo, 2) = ROUND(saldoAnticipo, 2)) THEN
										1
									WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
										AND ROUND(montoNetoAnticipo, 2) > ROUND(saldoAnticipo, 2)
										AND ROUND(saldoAnticipo, 2) > 0) THEN
										2
									WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
										AND ROUND(saldoAnticipo, 2) <= 0) THEN
										3
									WHEN (ROUND(montoNetoAnticipo, 2) > ROUND(totalPagadoAnticipo, 2)
										AND ROUND(saldoAnticipo, 2) <= 0) THEN
										4
								END)
			WHERE cxc_ant.idAnticipo = %s;",
				valTpDato($idAnticipo, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// VERIFICA EL SALDO DEL ANTICIPO A VER SI ESTA NEGATIVO
			$querySaldoDcto = sprintf("SELECT cxc_ant.*,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
			WHERE idAnticipo = %s
				AND saldoAnticipo < 0;",
				valTpDato($idAnticipo, "int"));
			$rsSaldoDcto = mysql_query($querySaldoDcto);
			if (!$rsSaldoDcto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
			$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
			$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
			if ($totalRowsSaldoDcto > 0) { return array(false, "El Anticipo Nro. ".$rowSaldoDcto['numeroAnticipo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
			
			// VERIFICA SI ALGUN ANTICIPO DE TRADE IN TIENE ALGUN DOCUMENTO ASOCIADO QUE AFECTE AL COSTO DE LA UNIDAD VENDIDA
			$queryTradeInCxC = sprintf("SELECT
				(CASE 
					WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
						'ND_CXC'
					WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
						'NC_CXC'
				END) AS tipo_documento,
				(CASE 
					WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
						cxc_nd.idNotaCargo
					WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
						cxc_nc.idNotaCredito
				END) AS id_documento,
				(CASE 
					WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
						cxc_nd.montoTotalNotaCargo
					WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
						cxc_nc.montoNetoNotaCredito
				END) AS monto_total
			FROM an_tradein_cxc tradein_cxc
				LEFT JOIN cj_cc_notadecargo cxc_nd ON (tradein_cxc.id_nota_cargo_cxc = cxc_nd.idNotaCargo AND tradein_cxc.id_nota_cargo_cxc IS NOT NULL)
				LEFT JOIN cj_cc_notacredito cxc_nc ON (tradein_cxc.id_nota_credito_cxc = cxc_nc.idNotaCredito AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)
			WHERE tradein_cxc.id_anticipo = %s
				AND tradein_cxc.estatus = 1;",
				valTpDato($idAnticipo, "int"));
			$rsTradeInCxC = mysql_query($queryTradeInCxC);
			if (!$rsTradeInCxC) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsTradeInCxC = mysql_num_rows($rsTradeInCxC);
			while($rowTradeInCxC = mysql_fetch_assoc($rsTradeInCxC)) {
				$tipoDocumento = $rowTradeInCxC['tipo_documento'];
				$idDocumento = $rowTradeInCxC['id_documento'];
				
				// ANULA EL DETALLE DEL AGREGADO
				if ($idDocumento > 0) {
					$contAgregado++;
					
					if ($tipoDocumento == 'ND_CXC') {
						$updateSQL = sprintf("UPDATE an_unidad_fisica_agregado SET
							estatus = NULL,
							fecha_anulado = %s,
							id_empleado_anulado = %s
						WHERE id_unidad_fisica = %s
							AND id_nota_cargo_cxc = %s
							AND estatus = 1;",
							valTpDato("NOW()", "campo"),
							valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
							valTpDato($idUnidadFisica, "int"),
							valTpDato($idDocumento, "int"));
					} else if ($tipoDocumento == 'NC_CXC') {
						// ANULA EL PAGO
						$updateSQL = sprintf("UPDATE an_pagos SET
							estatus = NULL,
							fecha_anulado = %s,
							id_empleado_anulado = %s
						WHERE id_factura = %s
							AND numeroDocumento = %s
							AND formaPago IN (8);",
							valTpDato("NOW()", "campo"),
							valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
							valTpDato($idFactura, "int"),
							valTpDato($idDocumento, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						$idNotaCreditoAgregado = $idDocumento;
						
						// ACTUALIZA EL SALDO Y EL MONTO PAGADO DE LA NOTA DE CREDITO
						$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
							saldoNotaCredito = montoNetoNotaCredito
						WHERE cxc_nc.idNotaCredito = %s;",
							valTpDato($idNotaCreditoAgregado, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO DEPENDIENDO DE SUS PAGOS
						$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
							saldoNotaCredito = saldoNotaCredito
												- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
															WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
																AND cxc_pago.formaPago IN (8)
																AND cxc_pago.estatus IN (1,2)), 0)
													+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
																WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
																	AND cxc_pago.formaPago IN (8)
																	AND cxc_pago.estatus IN (1,2)), 0)
													+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
																WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
																	AND cxc_pago.idFormaPago IN (8)
																	AND cxc_pago.estatus IN (1,2)), 0)
													+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
																WHERE cxc_pago.numeroControlDetalleAnticipo = cxc_nc.idNotaCredito
																	AND cxc_pago.id_forma_pago IN (8)
																	AND cxc_pago.estatus IN (1,2)), 0))
						WHERE cxc_nc.idNotaCredito = %s;",
							valTpDato($idNotaCreditoAgregado, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							
						// ACTUALIZA EL ESTATUS DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado)
						$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
							estadoNotaCredito = (CASE
												WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
													AND ROUND(saldoNotaCredito, 2) > 0) THEN
													0
												WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
													AND ROUND(saldoNotaCredito, 2) <= 0
													AND cxc_nc.idNotaCredito IN (SELECT * 
																				FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (8)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																					WHERE cxc_pago.formaPago IN (8)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																					WHERE cxc_pago.idFormaPago IN (8)
																						AND cxc_pago.estatus IN (1)
																					
																					UNION
																					
																					SELECT cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
																					WHERE cxc_pago.id_forma_pago IN (8)
																						AND cxc_pago.estatus IN (1)) AS q)) THEN
													3
												WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
													AND ROUND(montoNetoNotaCredito, 2) = ROUND(saldoNotaCredito, 2)) THEN
													1
												WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
													AND ROUND(montoNetoNotaCredito, 2) > ROUND(saldoNotaCredito, 2)
													AND ROUND(saldoNotaCredito, 2) > 0) THEN
													2
												WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
													AND ROUND(saldoNotaCredito, 2) <= 0) THEN
													3
											END)
						WHERE cxc_nc.idNotaCredito = %s;",
							valTpDato($idNotaCreditoAgregado, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						// VERIFICA EL SALDO DE LA NOTA DE CREDITO A VER SI ESTA NEGATIVO
						$querySaldoDcto = sprintf("SELECT cxc_nc.*,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
						FROM cj_cc_notacredito cxc_nc
							INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
						WHERE idNotaCredito = %s
							AND saldoNotaCredito < 0;",
							valTpDato($idNotaCreditoAgregado, "int"));
						$rsSaldoDcto = mysql_query($querySaldoDcto);
						if (!$rsSaldoDcto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
						$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
						$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
						if ($totalRowsSaldoDcto > 0) { return array(false, "La Nota de Crédito Nro. ".$rowSaldoDcto['numeracion_nota_credito']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
						
						$updateSQL = sprintf("UPDATE an_unidad_fisica_agregado SET
							estatus = NULL,
							fecha_anulado = %s,
							id_empleado_anulado = %s
						WHERE id_unidad_fisica = %s
							AND id_nota_credito_cxc = %s
							AND estatus = 1;",
							valTpDato("NOW()", "campo"),
							valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
							valTpDato($idUnidadFisica, "int"),
							valTpDato($idDocumento, "int"));
					}
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
		}
		
		if ($contAgregado > 0) {
			// ACTUALIZA EL COSTO DE LOS AGREGADOS
			$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
				costo_agregado = (SELECT SUM(IF((id_factura_cxp IS NOT NULL
												OR id_nota_cargo_cxp IS NOT NULL
												OR id_nota_credito_cxc IS NOT NULL
												OR id_vale_salida IS NOT NULL), 1, (-1)) * monto) FROM an_unidad_fisica_agregado uni_fis_agregado
									WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica
										AND uni_fis_agregado.estatus = 1)
			WHERE id_unidad_fisica = %s;",
				valTpDato($idUnidadFisica, "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA EL SALDO DE LA FACTURA
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			saldoFactura = IFNULL(cxc_fact.montoTotalFactura, 0)
							- IFNULL((SELECT SUM(pago_dcto.montoPagado) FROM an_pagos pago_dcto
									WHERE pago_dcto.id_factura = cxc_fact.idFactura
										AND pago_dcto.estatus = 1), 0)
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			estadoFactura = (CASE
								WHEN (ROUND(saldoFactura, 2) = 0 OR ROUND(saldoFactura, 2) < 0) THEN
									1
								WHEN (ROUND(saldoFactura, 2) > 0 AND ROUND(saldoFactura, 2) < (IFNULL(cxc_fact.montoTotalFactura, 0))) THEN
									2
								ELSE
									0
							END)
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// VERIFICA EL SALDO DE LA FACTURA A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxc_fact.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			(SELECT COUNT(q.id_factura)
			FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura FROM an_pagos cxc_pago
				WHERE cxc_pago.estatus IN (2)
				
				UNION
				
				SELECT cxc_pago.idPago, cxc_pago.id_factura FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.estatus IN (2)) AS q
			WHERE q.id_factura = cxc_fact.idFactura) AS cant_pagos_pendientes
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		WHERE cxc_fact.idFactura = %s
			AND (cxc_fact.saldoFactura < 0
				OR (cxc_fact.saldoFactura < (SELECT SUM(q.montoPagado)
												FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
													WHERE cxc_pago.estatus IN (2)
													
													UNION
													
													SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
													WHERE cxc_pago.estatus IN (2)) AS q
												WHERE q.id_factura = cxc_fact.idFactura)));",
			valTpDato($idFactura, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) {
			if ($rowSaldoDcto['saldoFactura'] < 0) {
				return array(false, "La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo");
			} else if ($rowSaldoDcto['cant_pagos_pendientes'] > 0) {
				return array(false, "La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." no puede ser pagada en su totalidad debido a que posee ".$rowSaldoDcto['cant_pagos_pendientes']." pagos pendientes. Por favor termine de registrar o anular dichos pagos.");
			}
		}
	}
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFact = mysql_fetch_array($rsFact);
	
	if (in_array($rowFact['estadoFactura'],array(0,2))) { // 0 = No Cancelado, 2 = Parcialmente Cancelado
		// BUSCA LOS DATOS DE LA NOTA DE CREDITO
		$queryNotaCredito = sprintf("SELECT cxc_nc.*,
			(CASE cxc_nc.estadoNotaCredito
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado (No Asignado)'
				WHEN 2 THEN 'Cancelado Parcial'
				WHEN 3 THEN 'Asignado'
			END) AS estado_nota_credito,
			motivo.descripcion AS descripcion_motivo
		FROM pg_motivo motivo
			RIGHT JOIN cj_cc_notacredito cxc_nc ON (motivo.id_motivo = cxc_nc.id_motivo)
		WHERE idNotaCredito = %s",
			valTpDato($idNotaCredito, "int"));
		$rsNotaCredito = mysql_query($queryNotaCredito);
		if (!$rsNotaCredito) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
		
		// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
		$queryAperturaCaja = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE idCaja = %s
			AND statusAperturaCaja IN (1,2)
			AND (ape.id_empresa = %s
				OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = %s));",
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsAperturaCaja = mysql_query($queryAperturaCaja);
		if (!$rsAperturaCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$idApertura = $rowAperturaCaja['id'];
		$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(45, "int"), // 45 = Recibo de Pago Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s)",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago, "date"),
			valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
			valTpDato(0, "int"),		
			valTpDato($idFactura, "int"),
			valTpDato($idModulo, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_v (id_factura, fecha_pago)
		VALUES (%s, %s)",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoPago = mysql_insert_id();
		
		if ($rowFact['estadoFactura'] == 0) { // 0 = No Cancelado
			if ($rowFact['saldoFactura'] == $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = "SI";
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			} else if ($rowFact['saldoFactura'] > $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = $rowFact['anulada'];
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA EL PAGO DEBIDO A LA RETENCION
			$insertSQL = sprintf("INSERT INTO an_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, montoPagado, numeroFactura, tomadoEnComprobante, tomadoEnCierre, idCaja, id_apertura, estatus, id_encabezado_v)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
				valTpDato(8, "int"),
				valTpDato($idNotaCredito, "text"),
				valTpDato(1, "int"),
				valTpDato(1, "int"),
				valTpDato($saldoNotaCred, "real_inglesa"),
				valTpDato($rowFact['numeroFactura'], "text"),
				valTpDato(1, "int"),
				valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato($idApertura, "int"),
				valTpDato(1, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
				valTpDato($idEncabezadoPago, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idPago = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
		} else if ($rowFact['estadoFactura'] == 2) { // 2 = Parcialmente Cancelado
			if ($rowFact['saldoFactura'] == $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = "SI";
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			} else if ($rowFact['saldoFactura'] > $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = $rowFact['anulada'];
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			} else if ($rowFact['saldoFactura'] < $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = "SI";
				$saldoNotaCred = $rowFact['saldoFactura'];
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA EL PAGO DEBIDO A LA RETENCION
			$insertSQL = sprintf("INSERT INTO an_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, montoPagado, numeroFactura, tomadoEnComprobante, tomadoEnCierre, idCaja, id_apertura, estatus, id_encabezado_v)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
				valTpDato(8, "int"),
				valTpDato($idNotaCredito, "text"),
				valTpDato(1, "int"),
				valTpDato(1, "int"),
				valTpDato($saldoNotaCred, "real_inglesa"),
				valTpDato($rowFact['numeroFactura'], "text"),
				valTpDato(1, "int"),
				valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato($idApertura, "int"),
				valTpDato(1, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
				valTpDato($idEncabezadoPago, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idPago = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
		
		$arrayIdDctoContabilidad[] = array(
			$idPago,
			$idModulo,
			"CAJAENTRADA");
		
		// INSERTA EL DETALLE DEL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
		VALUES (%s, %s)",
			valTpDato($idEncabezadoReciboPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		
		// ACTUALIZA EL SALDO DE LA FACTURA
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			saldoFactura = IFNULL(cxc_fact.subtotalFactura, 0)
								- IFNULL(cxc_fact.descuentoFactura, 0)
								+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
										WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
								+ IFNULL((SELECT SUM(cxc_fact_impuesto.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_impuesto
										WHERE cxc_fact_impuesto.id_factura = cxc_fact.idFactura), 0)
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL SALDO DE LA FACTURA DEPENDIENDO DE SUS PAGOS
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			saldoFactura = saldoFactura - IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
											WHERE cxc_pago.id_factura = cxc_fact.idFactura
												AND cxc_pago.estatus = 1), 0)
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			estadoFactura = (CASE
								WHEN (ROUND(saldoFactura, 2) <= 0) THEN
									1
								WHEN (ROUND(saldoFactura, 2) > 0 AND ROUND(saldoFactura, 2) < ROUND(montoTotalFactura, 2)) THEN
									2
								ELSE
									0
							END)
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// VERIFICA EL SALDO DE LA FACTURA A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxc_fact.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			(SELECT COUNT(q.id_factura)
			FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura FROM an_pagos cxc_pago
				WHERE cxc_pago.estatus IN (2)
				
				UNION
				
				SELECT cxc_pago.idPago, cxc_pago.id_factura FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.estatus IN (2)) AS q
			WHERE q.id_factura = cxc_fact.idFactura) AS cant_pagos_pendientes
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		WHERE cxc_fact.idFactura = %s
			AND (cxc_fact.saldoFactura < 0
				OR (cxc_fact.saldoFactura < (SELECT SUM(q.montoPagado)
												FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
													WHERE cxc_pago.estatus IN (2)
													
													UNION
													
													SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
													WHERE cxc_pago.estatus IN (2)) AS q
												WHERE q.id_factura = cxc_fact.idFactura)));",
			valTpDato($idFactura, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) {
			if ($rowSaldoDcto['saldoFactura'] < 0) {
				return array(false, "La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo");
			} else if ($rowSaldoDcto['cant_pagos_pendientes'] > 0) {
				return array(false, "La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." no puede ser pagada en su totalidad debido a que posee ".$rowSaldoDcto['cant_pagos_pendientes']." pagos pendientes. Por favor termine de registrar o anular dichos pagos.");
			}
		}
	} else if ($rowFact['estadoFactura'] == 1) { // 1 = Cancelado
		// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
			anulada = 'SI'
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	// ACTUALIZA EL SALDO Y EL MONTO PAGADO DE LA NOTA DE CREDITO
	$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
		saldoNotaCredito = montoNetoNotaCredito
	WHERE cxc_nc.idNotaCredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO DEPENDIENDO DE SUS PAGOS
	$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
		saldoNotaCredito = saldoNotaCredito
							- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
										WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
											AND cxc_pago.formaPago IN (8)
											AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
											WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
												AND cxc_pago.formaPago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
											WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
												AND cxc_pago.idFormaPago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
											WHERE cxc_pago.numeroControlDetalleAnticipo = cxc_nc.idNotaCredito
												AND cxc_pago.id_forma_pago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0))
	WHERE cxc_nc.idNotaCredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
	// ACTUALIZA EL ESTATUS DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado)
	$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
		estadoNotaCredito = (CASE
							WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
								AND ROUND(saldoNotaCredito, 2) > 0) THEN
								0
							WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
								AND ROUND(saldoNotaCredito, 2) <= 0
								AND cxc_nc.idNotaCredito IN (SELECT * 
															FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																WHERE cxc_pago.formaPago IN (8)
																	AND cxc_pago.estatus IN (1)
																
																UNION
																
																SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																WHERE cxc_pago.formaPago IN (8)
																	AND cxc_pago.estatus IN (1)
																
																UNION
																
																SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																WHERE cxc_pago.idFormaPago IN (8)
																	AND cxc_pago.estatus IN (1)
																
																UNION
																
																SELECT cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
																WHERE cxc_pago.id_forma_pago IN (8)
																	AND cxc_pago.estatus IN (1)) AS q)) THEN
								3
							WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
								AND ROUND(montoNetoNotaCredito, 2) = ROUND(saldoNotaCredito, 2)) THEN
								1
							WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
								AND ROUND(montoNetoNotaCredito, 2) > ROUND(saldoNotaCredito, 2)
								AND ROUND(saldoNotaCredito, 2) > 0) THEN
								2
							WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
								AND ROUND(saldoNotaCredito, 2) <= 0) THEN
								3
						END)
	WHERE cxc_nc.idNotaCredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// VERIFICA EL SALDO DE LA NOTA DE CREDITO A VER SI ESTA NEGATIVO
	$querySaldoDcto = sprintf("SELECT cxc_nc.*,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
	WHERE idNotaCredito = %s
		AND saldoNotaCredito < 0;",
		valTpDato($idNotaCredito, "int"));
	$rsSaldoDcto = mysql_query($querySaldoDcto);
	if (!$rsSaldoDcto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
	$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
	if ($totalRowsSaldoDcto > 0) { return array(false, "La Nota de Crédito Nro. ".$rowSaldoDcto['numeracion_nota_credito']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
		creditodisponible = limitecredito - (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
													WHERE cxc_fact.idCliente = cliente_emp.id_cliente
														AND cxc_fact.id_empresa = cliente_emp.id_empresa
														AND cxc_fact.estadoFactura IN (0,2)), 0)
											+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
													WHERE cxc_nd.idCliente = cliente_emp.id_cliente
														AND cxc_nd.id_empresa = cliente_emp.id_empresa
														AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
											- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
													WHERE anticip.idCliente = cliente_emp.id_cliente
														AND anticip.id_empresa = cliente_emp.id_empresa
														AND anticip.estadoAnticipo IN (1,2)
														AND anticip.estatus = 1), 0)
											- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
													WHERE cxc_nc.idCliente = cliente_emp.id_cliente
														AND cxc_nc.id_empresa = cliente_emp.id_empresa
														AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
											+ IFNULL((SELECT
														SUM(IFNULL(ped_vent.subtotal, 0)
															- IFNULL(ped_vent.subtotal_descuento, 0)
															+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																	WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
															+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																	WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
													FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_cliente = cliente_emp.id_cliente
														AND ped_vent.id_empresa = cliente_emp.id_empresa
														AND ped_vent.estatus_pedido_venta IN (2)), 0)),
		creditoreservado = (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idCliente = cliente_emp.id_cliente
								AND cxc_fact.id_empresa = cliente_emp.id_empresa
								AND cxc_fact.estadoFactura IN (0,2)), 0)
							+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
							WHERE cxc_nd.idCliente = cliente_emp.id_cliente
								AND cxc_nd.id_empresa = cliente_emp.id_empresa
								AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
							- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
							WHERE anticip.idCliente = cliente_emp.id_cliente
								AND anticip.id_empresa = cliente_emp.id_empresa
								AND anticip.estadoAnticipo IN (1,2)
								AND anticip.estatus = 1), 0)
							- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idCliente = cliente_emp.id_cliente
								AND cxc_nc.id_empresa = cliente_emp.id_empresa
								AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
							+ IFNULL((SELECT
										SUM(IFNULL(ped_vent.subtotal, 0)
											- IFNULL(ped_vent.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
													WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
											+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
													WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
									FROM iv_pedido_venta ped_vent
									WHERE ped_vent.id_cliente = cliente_emp.id_cliente
										AND ped_vent.id_empresa = cliente_emp.id_empresa
										AND ped_vent.estatus_pedido_venta IN (2)
										AND id_empleado_aprobador IS NOT NULL), 0))
	WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
		AND cliente_emp.id_cliente = %s
		AND cliente_emp.id_empresa = %s;",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	return array(true, $idNotaCredito, $idModulo);
}

function informacionCheque($idCheque){
	$query = sprintf("SELECT 
		cj_cc_cheque.id_banco_cliente,
		cj_cc_cheque.cuenta_cliente AS numero_cuenta_cliente,
		bancos.nombreBanco AS nombre_banco_cliente
	FROM cj_cc_cheque 
		INNER JOIN bancos ON cj_cc_cheque.id_banco_cliente = bancos.idBanco
	WHERE cj_cc_cheque.id_cheque = %s LIMIT 1",
		valTpDato($idCheque, "int"));
	$rsQuery = mysql_query($query) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowQuery = mysql_fetch_assoc($rsQuery);
	if(mysql_num_rows($rsQuery) == 0) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$query); }
	
	return $rowQuery;
}

function informacionTransferencia($idTransferencia){
	$query = sprintf("SELECT
		cj_cc_transferencia.cuenta_compania AS numero_cuenta_compania,
		cj_cc_transferencia.id_banco_compania,
		cj_cc_transferencia.id_banco_cliente,
		cj_cc_transferencia.id_cuenta_compania,						   
		bancos.nombreBanco AS nombre_banco_cliente,
		bancos2.nombreBanco AS nombre_banco_compania
	FROM cj_cc_transferencia 
		INNER JOIN bancos ON cj_cc_transferencia.id_banco_cliente = bancos.idBanco
		INNER JOIN bancos bancos2 ON cj_cc_transferencia.id_banco_compania = bancos2.idBanco
	WHERE cj_cc_transferencia.id_transferencia = %s LIMIT 1",
		$idTransferencia);
	$rsQuery = mysql_query($query) or die(mysql_error()." Linea: ".__LINE__." Query: ".$query);
	$rowQuery = mysql_fetch_assoc($rsQuery);
	if(mysql_num_rows($rsQuery) == 0){ die(mysql_error()." Linea: ".__LINE__." Query: ".$query); }
	
	return $rowQuery;
}

function insertarItemAdicional($contFila, $hddIdItm, $hddTpItm){
	$contFila++;
	
	if ($hddIdItm > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		if ($hddTpItm == 1) { // 1 = Por Paquete, 2 = Individual
			$queryPedidoDet = sprintf("SELECT 
				paq_ped.id_paquete_pedido,
				acc.id_accesorio,
				(CASE paq_ped.iva_accesorio
					WHEN 1 THEN
						acc.nom_accesorio
					ELSE
						CONCAT(acc.nom_accesorio, ' (E)')
				END) AS nom_accesorio,
				paq_ped.id_tipo_accesorio,
				(CASE paq_ped.id_tipo_accesorio
					WHEN 1 THEN	'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
				END) AS descripcion_tipo_accesorio,
				paq_ped.precio_accesorio,
				paq_ped.costo_accesorio,
				paq_ped.iva_accesorio,
				paq_ped.porcentaje_iva_accesorio,
				paq_ped.id_condicion_pago,
				paq_ped.estatus_paquete_pedido,
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo,
				cliente.id AS id_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				acc.id_tipo_comision,
				acc.porcentaje_comision,
				acc.monto_comision
			FROM an_acc_paq acc_paq
				INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
				LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
				LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
				INNER JOIN an_paquete_pedido paq_ped ON (acc_paq.Id_acc_paq = paq_ped.id_acc_paq)
			WHERE paq_ped.id_paquete_pedido = %s;", 
				valTpDato($hddIdItm, "int"));
		} else if ($hddTpItm == 2) { // 1 = Por Paquete, 2 = Individual
			$queryPedidoDet = sprintf("SELECT 
				acc_ped.id_accesorio_pedido,
				acc.id_accesorio,
				(CASE acc_ped.iva_accesorio
					WHEN 1 THEN
						acc.nom_accesorio
					ELSE
						CONCAT(acc.nom_accesorio, ' (E)')
				END) AS nom_accesorio,
				acc_ped.id_tipo_accesorio,
				(CASE acc_ped.id_tipo_accesorio
					WHEN 1 THEN	'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
				END) AS descripcion_tipo_accesorio,
				acc_ped.precio_accesorio,
				acc_ped.costo_accesorio,
				acc_ped.iva_accesorio,
				acc_ped.porcentaje_iva_accesorio,
				acc_ped.id_condicion_pago,
				acc_ped.estatus_accesorio_pedido,
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo,
				cliente.id AS id_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				acc.id_tipo_comision,
				acc.porcentaje_comision,
				acc.monto_comision
			FROM an_accesorio acc
				LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
				LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
				INNER JOIN an_accesorio_pedido acc_ped ON (acc.id_accesorio = acc_ped.id_accesorio)
			WHERE acc_ped.id_accesorio_pedido = %s", 
				valTpDato($hddIdItm, "int"));
		}
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdAccesorioItm = $rowPedidoDet['id_accesorio'];
		$hddTipoAccesorioItm = $rowPedidoDet['id_tipo_accesorio'];
		$divCodigoItm = "";
		$divDescripcionItm = utf8_encode($rowPedidoDet['nom_accesorio']);
		$divDescripcionItm .= ($rowPedidoDet['id_condicion_pago'] == 1) ? " <span class=\"textoVerdeNegrita\">[ Pagado ]</span>": "";
		$txtPrecioItm = $rowPedidoDet['precio_accesorio'];
		$txtCostoItm = $rowPedidoDet['costo_accesorio'];
		
		$htmlContrato = "";
		if ($hddTipoAccesorioItm == 3) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato
			$htmlContrato = "<table width=\"100%\">";
			$htmlContrato .= "<tr>";
				$htmlContrato .= (strlen($rowPedidoDet['nombre_cliente']) > 0) ? "<tr><td><span class=\"textoNegrita_10px\">".utf8_encode($rowPedidoDet['nombre_cliente'])." ".(($rowPedidoDet['id_motivo'] > 0) ? "(Comisión: ".(($rowPedidoDet['id_tipo_comision'] == 1) ? number_format($rowPedidoDet['porcentaje_comision'], 2, ".", ",")."%" : number_format($rowPedidoDet['monto_comision'], 2, ".", ",")).")" : "")."</span></td></tr>" : "";
				$htmlContrato .= ($rowPedidoDet['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$rowPedidoDet['id_motivo'].".- ".utf8_encode($rowPedidoDet['descripcion_motivo'])."</span></td></tr>" : "";
			$htmlContrato .= "</tr>";
			$htmlContrato .= "<tr>";
				$htmlContrato .= "<td>";
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddIdClienteItm%s\" name=\"hddIdClienteItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['id_cliente']);
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddIdMotivoItm%s\" name=\"hddIdMotivoItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['id_motivo']);
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddIdTipoComisionItm%s\" name=\"hddIdTipoComisionItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['id_tipo_comision']);
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddPorcentajeComisionItm%s\" name=\"hddPorcentajeComisionItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['porcentaje_comision']);
					$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddMontoComisionItm%s\" name=\"hddMontoComisionItm%s\" value=\"%s\">",
						$contFila, $contFila, $rowPedidoDet['monto_comision']);
				$htmlContrato .= "</td>";
			$htmlContrato .= "</tr>";
			$htmlContrato .= "</table>";
		}
		
		if ($rowPedidoDet['iva_accesorio'] == 1) {
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE tipo = 6 AND estado = 1 AND activo = 1 ORDER BY iva;");
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
			$contIva = 0;
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
				"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
					$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
					$contFila.":".$contIva);
			}
		}
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#".(($hddTipoAccesorioItm == 1) ? "trItmPie" : "trItmPieAdicionalOtro")."').before('".
		"<tr id=\"trItm:%s\" align=\"left\" height=\"24\">".
			"<td title=\"trItm:%s\"><input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><div id=\"divCodigoItm%s\">%s</div></td>".
			"<td><div id=\"divDescripcionItm%s\">%s</div>%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddMontoDescuentoItm%s\" name=\"hddMontoDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtCostoItm%s\" name=\"txtCostoItm%s\" value=\"%s\"/></td>".
			"<td><div id=\"divIvaItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedidoDet%s\" name=\"hddIdPedidoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAccesorioItm%s\" name=\"hddIdAccesorioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTotalDescuentoItm%s\" name=\"hddTotalDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoAccesorioItm%s\" name=\"hddTipoAccesorioItm%s\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila,
			$contFila, $contFila,
			$contFila, $contFila, 
			$contFila, $divCodigoItm, 
			$contFila, $divDescripcionItm, $htmlContrato,
			$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, number_format($hddMontoDescuentoItm, 2, ".", ","), 
				$contFila, $contFila, number_format($txtCostoItm, 2, ".", ","), 
			$contFila, $ivaUnidad, 
			$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, $hddIdPedidoDet, 
				$contFila, $contFila, $hddIdAccesorioItm, 
				$contFila, $contFila, $hddIdItm, 
				$contFila, $contFila, $hddTpItm, 
				$contFila, $contFila, number_format($hddTotalDescuentoItm, 2, ".", ","), 
				$contFila, $contFila, $hddTipoAccesorioItm);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemMetodoPago($contFila, $idFormaPago, $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtIdBancoCliente = "", $txtCuentaClientePago = "", $txtIdBancoCompania = "", $txtIdCuentaCompaniaPago = "", $txtFechaDeposito = "", $lstTipoTarjeta = "", $porcRetencion = "", $montoRetencion = "", $porcComision = "", $montoComision = "", $txtMontoPago = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia Bancaria, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito, 9 = Retención, 10 = Retencion I.S.L.R., 11 = Otro
	if (in_array($idFormaPago,array(3,5,6)) || (in_array($idFormaPago,array(4)) && !($txtIdNumeroDctoPago > 0))) {
		$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",
			valTpDato($txtIdCuentaCompaniaPago, "int"));
		$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta);
		if (!$rsBuscarNumeroCuenta) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	}
	
	$queryFormaPago = sprintf("SELECT * FROM formapagos WHERE idFormaPago = %s;", valTpDato($idFormaPago, "int"));
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsFormaPago = mysql_num_rows($rsFormaPago);
	$rowFormaPago = mysql_fetch_array($rsFormaPago);
	
	$nombreFormaPago = $rowFormaPago['nombreFormaPago'];
	
	$txtBancoClientePago = "-";
	$txtBancoCompaniaPago = "-";
	$txtCuentaCompaniaPago = "-";
	switch ($idFormaPago) {
		case 1 : // 1 = Efectivo
			break;
		case 2 : // 2 = Cheque
			if ($txtIdNumeroDctoPago > 0) {
				$arrayInformacionCheque = informacionCheque($txtIdNumeroDctoPago);
				$txtIdBancoCliente = $arrayInformacionCheque['id_banco_cliente'];
				$txtBancoClientePago = $arrayInformacionCheque['nombre_banco_cliente'];
				$txtCuentaClientePago = $arrayInformacionCheque['numero_cuenta_cliente'];
			} else {
				$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
			}
			break;
		case 3 : // 3 = Deposito
			$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
			$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			break;
		case 4 : // 4 = Transferencia Bancaria
			if ($txtIdNumeroDctoPago > 0) {
				$arrayInformacionTransferencia = informacionTransferencia($txtIdNumeroDctoPago);
				$txtIdBancoCliente = $arrayInformacionTransferencia['id_banco_cliente'];
				$txtBancoClientePago = $arrayInformacionTransferencia['nombre_banco_cliente'];
				
				$txtIdBancoCompania = $arrayInformacionTransferencia['id_banco_compania'];
				$txtBancoCompaniaPago = $arrayInformacionTransferencia['nombre_banco_compania'];
				$txtIdCuentaCompaniaPago = $arrayInformacionTransferencia['id_cuenta_compania'];
				$txtCuentaCompaniaPago = $arrayInformacionTransferencia['numero_cuenta_compania'];
			} else {
				$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
				$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
				$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			}
			break;
		case 5 : // 5 = Tarjeta de Crédito
			$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
			$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
			$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			break;
		case 6 : // 6 = Tarjeta de Debito
			$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
			$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
			$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			
			$lstTipoTarjeta = 6;
			break;
		case 7 : // 7 = Anticipo
			// BUSCA EL TIPO DEL ANTICIPO
			$queryAnticipo = sprintf("SELECT cxc_ant.*,
				concepto_forma_pago.descripcion
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND cxc_pago.tipoPagoDetalleAnticipo LIKE 'OT';",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
			while($rowAnticipo = mysql_fetch_array($rsAnticipo)) {
				$arrayConceptoAnticipo[] = $rowAnticipo['descripcion'];
				$observacionDcto = preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowAnticipo['observacionesAnticipo']))));
			}
			
			$nombreFormaPago .= (($totalRowsAnticipo > 0) ? "<br><span class=\"textoNegrita_10px\">(".implode(", ", $arrayConceptoAnticipo).")</span>" : "");
			break;
		case 8 : // 8 = Nota de Crédito
			// BUSCA EL TIPO DEL ANTICIPO
			$queryNotaCredito = sprintf("SELECT cxc_nc.*,
				motivo.descripcion AS descripcion_motivo
			FROM cj_cc_notacredito cxc_nc
				LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura)
				INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
				LEFT JOIN pg_motivo motivo ON (cxc_nc.id_motivo = motivo.id_motivo)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_nc.idNotaCredito = %s;",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsNotaCredito = mysql_num_rows($rsNotaCredito);
			$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
			
			$idMotivo = $rowNotaCredito['id_motivo'];
			$descripcionMotivo = $rowNotaCredito['descripcion_motivo'];
			$observacionDcto = preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowNotaCredito['observacionesNotaCredito']))));
			break;
		case 9 : // 9 = Retención
			break;
		case 10 : // 10 = Retencion I.S.L.R.
			break;
		case 11 : // 11 = Otro
			break;
	}
	
	$checkedCondicionMostrar = "checked=\"checked\"";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPiePago').before('".
		"<tr align=\"left\" id=\"trItmPago:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmPago:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx2\" name=\"cbx2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"divMsjInfo2\">%s</td>".
			"<td class=\"divMsjInfo\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td><table width=\"%s\">".
				"<tr><td>%s</td><td><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr>".
				"%s".
				"%s".
				"</table></td>".
			"<td><input type=\"text\" id=\"txtBancoClientePago%s\" name=\"txtBancoClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaClientePago%s\" name=\"txtCuentaClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMonto%s\" name=\"txtMonto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><button type=\"button\" onclick=\"confirmarEliminarPago(%s);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"/></button>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtFechaDeposito%s\" name=\"txtFechaDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdFormaPago%s\" name=\"txtIdFormaPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoCompania%s\" name=\"txtIdBancoCompania%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdCuentaCompaniaPago%s\" name=\"txtIdCuentaCompaniaPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoCliente%s\" name=\"txtIdBancoCliente%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtTipoTarjeta%s\" name=\"txtTipoTarjeta%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			(in_array(idArrayPais,array(3))) ? "<input type=\"checkbox\" id=\"cbxCondicionMostrar\" name=\"cbxCondicionMostrar".$contFila."\" ".$checkedCondicionMostrar." value=\"1\">" : "",
			(in_array(idArrayPais,array(3))) ? cargaLstSumarPagoItm("lstSumarA".$contFila, $checkedMostrarContado) : "",
			utf8_encode($nombreFormaPago),
			"100%",
				$aVerDcto, $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
					$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				(($idMotivo > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$idMotivo.".- ".utf8_encode($descripcionMotivo)."</span></td></tr>" : ""),
				((strlen($observacionDcto) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".($observacionDcto)."</span></td></tr>" : ""),
			$contFila, $contFila, utf8_encode($txtBancoClientePago),
				$contFila, $contFila, $txtCuentaClientePago,
			$contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
				$contFila, $contFila, $txtCuentaCompaniaPago,
			$contFila, $contFila, number_format($txtMontoPago, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $hddIdPago,
				$contFila, $contFila, $txtFechaDeposito,
				$contFila, $contFila, $idFormaPago,
				$contFila, $contFila, $txtIdBancoCompania,
				$contFila, $contFila, $txtIdCuentaCompaniaPago,
				$contFila, $contFila, $txtIdBancoCliente,
				$contFila, $contFila, $lstTipoTarjeta);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemUnidad($contFila, $idUnidadFisica, $hddTpItm){
	$contFila++;
	
	if ($idUnidadFisica > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT
			uni_bas.id_uni_bas,
			uni_bas.nom_uni_bas,
			marca.nom_marca,
			modelo.nom_modelo,
			vers.nom_version,
			ano.nom_ano,
			uni_fis.id_unidad_fisica,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			color_ext1.nom_color AS color_externo,
			color_int1.nom_color AS color_interno,
			uni_fis.marca_cilindro,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro,
			uni_fis.marca_kit,
			uni_fis.modelo_regulador,
			uni_fis.serial_regulador,
			uni_fis.codigo_unico_conversion,
			uni_fis.serial1,
			ped_vent.precio_venta,
			ped_vent.monto_descuento,
			ped_vent.porcentaje_iva,
			uni_fis.precio_compra,
			uni_fis.costo_depreciado
		FROM an_pedido ped_vent
			INNER JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
			INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
		WHERE ped_vent.id_unidad_fisica = %s
			AND ped_vent.estado_pedido IN (1);", 
			valTpDato($idUnidadFisica, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdUnidadBasicaItm = $rowPedidoDet['id_uni_bas'];
		$hddIdUnidadFisicaItm = $rowPedidoDet['id_unidad_fisica'];
		$divCodigoItm = $rowPedidoDet['nom_uni_bas'];
		$txtPrecioItm = $rowPedidoDet['precio_venta'];
		$hddMontoDescuentoItm = $rowPedidoDet['monto_descuento'];
		$hddTotalDescuentoItm = $rowPedidoDet['monto_descuento'];
		$txtCostoItm = $rowPedidoDet['precio_compra'] - $rowPedidoDet['costo_depreciado'];
		$porcIva = $rowPedidoDet['porcentaje_iva'];
		
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT uni_bas_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
			INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND tipo IN (2,6)
			AND (%s IS NOT NULL AND %s > 0);", 
			valTpDato($hddIdUnidadBasicaItm, "int"),
			valTpDato($porcIva, "real_inglesa"),
			valTpDato($porcIva, "real_inglesa"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$contIva = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$contIva++;
			
			$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
			"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
				$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
				$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
				$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
				$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
				$contFila.":".$contIva);
		}
	}
	
	$divDescripcionItm = "<table width=\"100%\">".
	"<tr height=\"22\">".
		"<td align=\"right\" class=\"tituloCampo\">Marca:</td>"."<td>".utf8_encode($rowPedidoDet['nom_marca'])."</td>".
	"</tr>".
	"<tr height=\"22\">".
		"<td align=\"right\" class=\"tituloCampo\" width=\"20%\">Modelo:</td>"."<td width=\"30%\">".utf8_encode($rowPedidoDet['nom_modelo'])."</td>".
		"<td align=\"right\" class=\"tituloCampo\" width=\"20%\">Versión:</td>"."<td width=\"30%\">".utf8_encode($rowPedidoDet['nom_version'])."</td>".
	"</tr>".
	"<tr>".
		"<td align=\"right\" class=\"tituloCampo\">Año:</td>".
		"<td>".$rowPedidoDet['nom_ano']."</td>".
		"<td align=\"right\" class=\"tituloCampo\">Placa:</td>".
		"<td>".utf8_encode($rowPedidoDet['placa'])."</td>".
	"</tr>".
	"<tr>".
		"<td align=\"right\" class=\"tituloCampo\">Serial Carroceria:</td>".
		"<td>".utf8_encode($rowPedidoDet['serial_carroceria'])."</td>".
		"<td align=\"right\" class=\"tituloCampo\">Serial Motor:</td>".
		"<td>".utf8_encode($rowPedidoDet['serial_motor'])."</td>".
	"</tr>".
	"<tr>".
		"<td align=\"right\" class=\"tituloCampo\">Nro. Vehículo:</td>".
		"<td>".utf8_encode($rowPedidoDet['serial_chasis'])."</td>".
	"</tr>".
	"<tr>".
		"<td align=\"right\" class=\"tituloCampo\">Color Carroceria:</td>".
		"<td>".utf8_encode($rowPedidoDet['color_externo'])."</td>".
		"<td align=\"right\" class=\"tituloCampo\">Tipo Tapiceria:</td>".
		"<td>".utf8_encode($rowPedidoDet['color_interno'])."</td>".
	"</tr>".
	"<tr>".
		"<td align=\"right\" class=\"tituloCampo\">Registro Legalización:</td>".
		"<td>".utf8_encode($rowPedidoDet['registro_legalizacion'])."</td>".
		"<td align=\"right\" class=\"tituloCampo\">Registro Federal:</td>".
		"<td>".utf8_encode($rowPedidoDet['registro_federal'])."</td>".
	"</tr>";
	if (in_array($rowPedidoDet['id_combustible'],array(2,5))) {
		$divDescripcionItm .= "<tr><td align=\"center\" class=\"tituloArea\" colspan=\"4\">SISTEMA GNV</td></tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Serial 1:</td>".
			"<td>".utf8_encode($rowPedidoDet['serial1'])."</td>".
			"<td align=\"right\" class=\"tituloCampo\">Código Único:</td>".
			"<td>".utf8_encode($rowPedidoDet['codigo_unico_conversion'])."</td>".
		"</tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Marca Kit:</td>".
			"<td>".utf8_encode($rowPedidoDet['marca_kit'])."</td>".
		"</tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Modelo Regulador:</td>".
			"<td>".utf8_encode($rowPedidoDet['modelo_regulador'])."</td>".
			"<td align=\"right\" class=\"tituloCampo\">Serial Regulador:</td>".
			"<td>".utf8_encode($rowPedidoDet['serial_regulador'])."</td>".
		"</tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Marca Cilindro:</td>".
			"<td>".utf8_encode($rowPedidoDet['marca_cilindro'])."</td>".
			"<td align=\"right\" class=\"tituloCampo\">Capacidad Cilindro (NG):</td>".
			"<td>".utf8_encode($rowPedidoDet['capacidad_cilindro'])."</td>".
		"</tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Fecha Elab. Cilindro:</td>".
			"<td>".$rowPedidoDet['fecha_elaboracion_cilindro']."</td>".
		"</tr>";
	}
	$divDescripcionItm .= "</table>";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\">".
			"<td title=\"trItm:%s\"><input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><div id=\"divCodigoItm%s\">%s</div></td>".
			"<td><div id=\"divDescripcionItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddMontoDescuentoItm%s\" name=\"hddMontoDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtCostoItm%s\" name=\"txtCostoItm%s\" value=\"%s\"/></td>".
			"<td id=\"tdIvaItm%s\">%s</td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedidoDet%s\" name=\"hddIdPedidoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTotalDescuentoItm%s\" name=\"hddTotalDescuentoItm%s\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, 
			$contFila, $contFila,
			$contFila, $contFila, 
			$contFila, $divCodigoItm, 
			$contFila, $divDescripcionItm,
			$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, number_format($hddMontoDescuentoItm, 2, ".", ","), 
				$contFila, $contFila, number_format($txtCostoItm, 2, ".", ","), 
			$contFila, $ivaUnidad, 
			$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, $hddIdPedidoDet, 
				$contFila, $contFila, $idUnidadFisica, 
				$contFila, $contFila, $hddTpItm, 
				$contFila, $contFila, number_format($hddTotalDescuentoItm, 2, ".", ","));
	
	return array(true, $htmlItmPie, $contFila);
}

function validarAperturaCaja($idEmpresa, $fecha) {
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM ".$apertCajaPpal." ape
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND id_empresa = %s;",
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
	
	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);
		
		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}
?>