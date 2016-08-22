<?php


function asignarFechaCredito($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$fechaVencimiento = suma_fechas("d-m-Y",$frmDcto['txtFechaFactura'],$frmDcto['txtDiasCreditoCliente']);
	
	$objResponse->assign("txtFechaVencimientoFactura","value",date("d-m-Y",strtotime($fechaVencimiento)));
	
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $calcularDcto = "false") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	if (isset($arrayObjGasto)) {
		$i = 0;
		foreach ($arrayObjGasto as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmGasto:".$valor, "className", $clase." textoGris_11px");
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trIva:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdFactura'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$txtPrecioItm = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valor]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
			$txtTotalItm = $txtCantItm * $txtPrecioItm;
			
			$txtSubTotal += $txtTotalItm;
			$subTotalDescuentoItm += $txtCantItm * $hddMontoDescuentoItm;
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		$txtDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1 || isset($frmDcto['txtIdNotaCredito'])) {
			$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IMPUESTO Y EL SUBTOTAL
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$arrayIva = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
			
			$hddTotalDescuentoItm = ($hddTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $hddTotalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						
						$arrayPosIvaItm[$frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]]] = $valor1[1];
						$arrayIdIvaItm[] = ($hddIdIvaItm > 0) ? $hddIdIvaItm : -1;
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
					}
				}
			}
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE idIva IN (%s);", 
				valTpDato(implode(",", $arrayIdIvaItm), "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$idIva = $rowIva['idIva'];
				$porcIva = $rowIva['iva'];
				$lujoIva = $rowIva['lujo'];
				$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if (($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) || $hddPagaImpuesto == 0) {
					$txtTotalExento += $txtTotalNetoItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
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
			
			$objResponse->assign("txtTotalItm".$valor, "value", number_format($txtTotalItm, 2, ".", ","));
			
			$subTotalDescuentoItm += $txtCantItm * str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
		}
	}
	
	// SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IMPUESTO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s
				AND id_modo_gasto IN (1);", 
				valTpDato($frmTotalDcto['hddIdGasto'.$valor], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				if ($frmTotalDcto['hddTipoGasto'.$valor] == 0) { // SACA EL MONTO MEDIANTE EL PORCENTAJE
					$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
					$txtMontoGasto = ($txtSubTotal == 0) ? 0 : $txtPorcGasto * ($txtSubTotal / 100);
					$objResponse->assign('txtMontoGasto'.$valor, "value", number_format($txtMontoGasto, 2, ".", ","));
				} else if ($frmTotalDcto['hddTipoGasto'.$valor] == 1) { // SACA EL PORCENTAJE MEDIANTE EL MONTO
					$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
					$txtPorcGasto = ($txtSubTotal == 0) ? 0 : $txtMontoGasto * (100 / $txtSubTotal);
					$objResponse->assign('txtPorcGasto'.$valor, "value", number_format($txtPorcGasto, 2, ".", ","));
				}
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$arrayPosIvaItm = array(-1);
				$arrayIdIvaItm = array(-1);
				$arrayIvaItm = array(-1);
				$arrayEstatusIvaItm = array(-1);
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						
						if ($valor1[0] == $valor) {
							$arrayPosIvaItm[$frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]]] = $valor1[1];
							$arrayIdIvaItm[] = $frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]];
							$arrayIvaItm[] = $frmTotalDcto['hddIvaGasto'.$valor.':'.$valor1[1]];
							$arrayEstatusIvaItm[] = $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$valor1[1]];
						}
					}
				}
				
				// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);",
					valTpDato(implode(",", $arrayIdIvaItm), "campo"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
					
					if (($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) || $hddPagaImpuesto == 0) {
						switch ($rowGasto['afecta_documento']) {
							case 1 : $txtGastosSinIva += $txtMontoGasto; break;
							default : $gastosNoAfecta += $txtMontoGasto; break;
						}
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmTotalDcto['hddIvaGasto'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
						$subTotalIvaGasto = ($txtMontoGasto * $porcIva) / 100;
						
						$existIva = false;
						if (isset($arrayIva)) {
							foreach ($arrayIva as $indiceIva => $valorIva) {
								if ($arrayIva[$indiceIva][0] == $idIva) {
									$arrayIva[$indiceIva][1] += $txtMontoGasto;
									$arrayIva[$indiceIva][2] += $subTotalIvaGasto;
									$existIva = true;
								}
							}
						}
						
						if ($idIva > 0 && $existIva == false
						&& $txtMontoGasto > 0) {
							$arrayIva[] = array(
								$idIva,
								$txtMontoGasto,
								$subTotalIvaGasto,
								$porcIva,
								$lujoIva,
								$rowIva['observacion']);
						}
					}
				}
				
				if ($totalRowsIva > 0 && in_array(1,$arrayEstatusIvaItm)) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosConIva += $txtMontoGasto; break;
						default : $gastosNoAfecta += $txtMontoGasto; break;
					}
				} else if (!($totalRowsIva > 0)) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosSinIva += $txtMontoGasto; break;
						default : $gastosNoAfecta += $txtMontoGasto; break;
					}
				}
			}
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIva)) {
		foreach ($arrayIva as $indiceIva => $valorIva) {
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
			
			$subTotalIva += round(doubleval($arrayIva[$indiceIva][2]), 2);
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
	$txtTotalOrden += round(doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva), 2);
	
	$objResponse->assign("txtSubTotal", "value", number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento", "value", number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtGastosConIva", "value", number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign("txtGastosSinIva", "value", number_format($txtGastosSinIva, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalFactura","value",number_format($txtTotalOrden,2,".",","));
	$objResponse->assign("txtMontoPorPagar","value",number_format($txtTotalOrden,2,".",","));
	
	// HABILITA O INHABILITA POR GASTO EL IMPUESTO DEPENDIENDO SI SE INCLUYE O NO
	if (isset($frmTotalDcto['cbxGasto'])) {
		foreach ($frmTotalDcto['cbxGasto'] as $indice2 => $valor2) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGastos = sprintf("SELECT * FROM pg_gastos WHERE pg_gastos.id_gasto = %s;",
				valTpDato($frmTotalDcto['hddIdGasto'.$valor2], "int"));
			$rsGastos = mysql_query($queryGastos);
			if (!$rsGastos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGastos = mysql_fetch_assoc($rsGastos);
			
			if ($rowGastos['id_modo_gasto'] == 1) { // 1 = Nacional
				$objResponse->assign("spnGastoMoneda".$valor2,"innerHTML",$abrevMonedaLocal);
			}
			
			if (($rowGastos['id_modo_gasto'] == 1 && $rowMonedaLocal['incluir_impuestos'] == 0)) {				// 1 = Nacional && 0 = No
				$objResponse->script("byId('trIvaGasto".$valor2."').style.visibility = 'hidden';");
			} else if (($rowGastos['id_modo_gasto'] == 1 && $rowMonedaLocal['incluir_impuestos'] == 1)) {		// 1 = Nacional && 1 = Si
				$objResponse->script("byId('trIvaGasto".$valor2."').style.visibility = '';");
			}
		}
	}
	
	$objResponse->assign("tdSubTotalMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdDescuentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdGastoConIvaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdGastoSinIvaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalRegistroMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalFacturaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExoneradoMoneda", "innerHTML", $abrevMonedaLocal);
	
	if (in_array($calcularDcto, array("1", "true"))) {
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
	
	return $objResponse;
}

function cargarDcto($idDocumento, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$queryPedido = sprintf("SELECT
		vw_iv_ped_vent.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_pedidos_venta vw_iv_ped_vent
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE vw_iv_ped_vent.estatus_pedido_venta = 2
		AND vw_iv_ped_vent.id_pedido_venta = %s;",
		valTpDato($idDocumento, "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPedido = mysql_num_rows($rsPedido);
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$idEmpresa = $rowPedido['id_empresa'];
	$idCliente = $rowPedido['id_cliente'];
	$idCondicionPago = $rowPedido['condicion_pago'];
	$idClaveMovimiento = $rowPedido['id_clave_movimiento'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
	
	if ($totalRowsPedido > 0) {
		$queryPedidoDet = sprintf("SELECT *,
			(SELECT SUM(monto_gasto) AS total_gasto_art FROM iv_pedido_venta_detalle_gastos
			WHERE id_pedido_venta_detalle = ped_vent_det.id_pedido_venta_detalle) AS total_gasto_art
		FROM iv_pedido_venta_detalle ped_vent_det
		WHERE id_pedido_venta = %s
		ORDER BY id_pedido_venta_detalle;",
			valTpDato($idDocumento, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = 0;
		$arrayObj = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$gastoUnit = round($rowPedidoDet['total_gasto_art'],2) / round($rowPedidoDet['cantidad'],2);
			$precioUnitario = round($rowPedidoDet['precio_unitario'],2) + round($gastoUnit,2);
			
			$Result1 = insertarItemArticulo($contFila, $rowPedidoDet['id_pedido_venta_detalle'], "", $rowPedidoDet['id_articulo'], $rowPedidoDet['id_casilla'], $rowPedidoDet['id_articulo_almacen_costo'], $rowPedidoDet['id_articulo_costo'], $rowPedidoDet['cantidad'], $rowPedidoDet['pendiente'], $rowPedidoDet['id_precio'], $precioUnitario, "", "", $rowPedidoDet['id_iva']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
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
		
		if ($idCondicionPago == 0) { // 0 = Credito, 1 = Contado
			$objResponse->assign("hddTipoPago","value",$idCondicionPago);
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
				
				$fechaVencimiento = suma_fechas("d-m-Y",date("d-m-Y"),$rowClienteCredito['diascredito']);
				
				$objResponse->assign("txtDiasCreditoCliente","value",$rowClienteCredito['diascredito']);
			} else {
				$fechaVencimiento = date("d-m-Y");
				
				$objResponse->assign("txtDiasCreditoCliente","value","0");
			}
			
			$objResponse->script("
			byId('trFormaDePago').style.display = 'none';");
			
		} else if ($idCondicionPago == 1) { // 0 = Credito, 1 = Contado
			$objResponse->assign("hddTipoPago","value",$idCondicionPago);
			$objResponse->assign("txtTipoPago","value","CONTADO");
			
			$fechaVencimiento = date("d-m-Y");
			
			$objResponse->assign("txtDiasCreditoCliente","value","0");			
			
			$objResponse->script("
			byId('trFormaDePago').style.display = '';");
		}
		
		$objResponse->assign("tdGastos","innerHTML",formularioGastos(true,$rowPedido['id_pedido_venta'],"PEDIDO_VENTA"));
		
		// DATOS DE LA FACTURA
		$objResponse->assign("txtFechaFactura","value",date("d-m-Y"));
		$objResponse->assign("txtFechaVencimientoFactura","value",date("d-m-Y",strtotime($fechaVencimiento)));
		
		// DATOS DEL CLIENTE
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
		$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
		$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
		$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
		$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
		$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
		
		// DATOS DEL PEDIDO
		$objResponse->assign("txtIdEmpresa","value",$rowPedido['id_empresa']);
		$objResponse->assign("txtEmpresa","value",$rowPedido['nombre_empresa']);
		$objResponse->assign("txtIdPedido","value",$rowPedido['id_pedido_venta']);
		$objResponse->assign("hddFechaPedido","value",date("Y-m-d",strtotime($rowPedido['fecha'])));
		$objResponse->assign("txtNumeroPedidoPropio","value",(($rowPedido['id_pedido_venta_propio'] != "") ? $rowPedido['id_pedido_venta_propio'] : ""));
		$objResponse->assign("hddIdMoneda","value",utf8_encode($rowPedido['id_moneda']));
		$objResponse->assign("txtMoneda","value",utf8_encode($rowPedido['descripcion']));
		$objResponse->assign("hddIdEmpleado","value",$rowPedido['id_empleado_preparador']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowPedido['nombre_empleado']));
		$objResponse->assign("hddIdClaveMovimiento","value",$rowPedido['id_clave_movimiento']);
		$objResponse->assign("txtClaveMovimiento","value",utf8_encode($rowPedido['descripcion_clave_movimiento']));
		$objResponse->assign("txtDescuento","value",number_format($rowPedido['porcentaje_descuento'], 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowPedido['subtotal_descuento'], 2, ".", ","));
		
		$Result1 = buscarNumeroControl($idEmpresa, $rowPedido['id_clave_movimiento']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->assign("txtNumeroControlFactura","value",($Result1[1]));
		}
									
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');");
	} else {
		$objResponse->alert("El Pedido no puede ser cargado debido a que su status no es válido");
		
		$objResponse->script(sprintf("window.location.href='cjrs_factura_venta_list.php';"));
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $frmDetallePago, $frmListaPagos) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cjrs_factura_venta_list","insertar")) { errorGuardarDcto($objResponse); return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	$idPedido = $frmDcto['txtIdPedido'];
	
	$queryPedido = sprintf("SELECT
		vw_iv_ped_vent.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_pedidos_venta vw_iv_ped_vent
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE vw_iv_ped_vent.estatus_pedido_venta = 2
		AND vw_iv_ped_vent.id_pedido_venta = %s;",
		valTpDato($idPedido, "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsPedido = mysql_num_rows($rsPedido);
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$idEmpresa = $rowPedido['id_empresa'];
	$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idClaveMovimiento = $frmDcto['hddIdClaveMovimiento'];
	$idTipoPago = $frmDcto['hddTipoPago'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
	
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
	if (!$rsVerif) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if (mysql_num_rows($rsVerif) > 0) {
		errorGuardarDcto($objResponse); return $objResponse->alert('Este documento ya ha sido facturado');
	}
	
	// VERIFICA QUE EL DOOCUMENTO A CONTADO ESTE CANCELADO EN TU TOTALIDAD
	if ($idTipoPago == 1) { // 0 = Credito, 1 = Contado
		if ($frmListaPagos['txtMontoPorPagar'] != 0) {
			errorGuardarDcto($objResponse); return $objResponse->alert('Debe cancelar el monto total de la factura');
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
	if (!$rsNumeracion) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	if ($rowNumeracion['numero_actual'] == "") { errorGuardarDcto($objResponse); return $objResponse->alert("No se ha configurado la numeracion de facturas"); }
	
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$numeroActualFactura = $numeroActual;
		
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			switch ($frmTotalDcto['hddLujoIva'.$valor]) {
				case 0 :
					$txtBaseImponibleIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
					$txtIva += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
					$txtSubTotalIva += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
					break;
				case 1 :
					$txtBaseImponibleIvaLujo = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
					$txtIvaLujo += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
					$txtSubTotalIvaLujo += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
					break;
			}
		}
	}
	
	// INSERTA LOS DATOS DEL PEDIDO
	$insertSQL = sprintf("INSERT INTO cj_cc_encabezadofactura (numeroControl, fechaRegistroFactura, numeroFactura, fechaVencimientoFactura, montoTotalFactura, saldoFactura, estadoFactura, observacionFactura, idVendedor, numeroSiniestro, fletesFactura, idCliente, numeroPedido, idDepartamentoOrigenFactura, porcentaje_descuento, descuentoFactura, porcentajeIvaFactura, calculoIvaFactura, subtotalFactura, interesesFactura, condicionDePago, base_imponible_iva_lujo, porcentajeIvaDeLujoFactura, calculoIvaDeLujoFactura, baseImponible, diasDeCredito, montoExento, montoExonerado, anulada, aplicaLibros, id_empresa, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($frmDcto['txtNumeroControlFactura'], "text"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato($numeroActualFactura, "text"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaVencimientoFactura'])), "date"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada , 2 = Parcialmente Cancelada
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($frmDcto['hddIdEmpleado'], "int"),
		valTpDato(" ", "text"),
		valTpDato(0, "real_inglesa"),
		valTpDato($idCliente, "int"),
		valTpDato($idPedido, "int"),
		valTpDato($idModulo, "int"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($txtIva, "real_inglesa"),
		valTpDato($txtSubTotalIva , "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($idTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
		valTpDato($txtIvaLujo, "real_inglesa"),
		valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
		valTpDato($txtBaseImponibleIva, "real_inglesa"),
		valTpDato($frmDcto['txtDiasCreditoCliente'], "int"),
		valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
		valTpDato("NO", "text"),
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato($idEmpresa, "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idFactura = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idFactura,
		$idModulo,
		"VENTA");
	
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	VALUE (%s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
		valTpDato(3, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato($idCliente, "int"),
		valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($idTipoPago, "int")); // 0 = Credito, 1 = Contado
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idMovimiento = mysql_insert_id();
	
	// INSERTA EL DETALLE DEL DOCUMENTO
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$idPedDet = $frmListaArticulo['hddIdPedDet'.$valor];
			$idArticulo = $frmListaArticulo['hddIdArt'.$valor];
			$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
			$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$cantDespachada = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$cantPendiente = doubleval($cantPedida) - doubleval($cantDespachada);
			$precioUnitario = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valor]);
			$costoUnitario = str_replace(",", "", $frmListaArticulo['hddCostoItm'.$valor]);
			$hddIdIvaItm = "";
			$hddIvaItm = 0;
			if (isset($arrayObjIvaItm)) {// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
					}
				}
			}
			$totalArticulo = $cantDespachada * $precioUnitario;
			
			$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle (id_factura, id_articulo, cantidad, pendiente, costo_compra, precio_unitario, id_iva, iva, estatus)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($cantPedida, "real_inglesa"),
				valTpDato($cantPendiente, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($hddIdIvaItm, "int"),
				valTpDato($hddIvaItm, "real_inglesa"),
				valTpDato(1, "text")); // 0 = Pendiente, 1 = Entregado, 2 = Devuelto
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idFacturaDetalle = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
				
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
						
						$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_impuesto (id_factura_detalle, id_impuesto, impuesto) 
						VALUE (%s, %s, %s);",
							valTpDato($idFacturaDetalle, "int"),
							valTpDato($hddIdIvaItm, "int"),
							valTpDato($hddIvaItm, "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
					}
				}
			}
			
			// REGISTRA EL MOVIMIENTO DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, tipo_movimiento, id_clave_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
				valTpDato($idModulo, "int"),
				valTpDato($idFactura, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato(3, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($cantDespachada, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
				valTpDato(((str_replace(",", "", $frmTotalDcto['txtDescuento']) * $precioUnitario) / 100), "real_inglesa"),
				valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
				valTpDato($frmTotalDcto['txtObservacion'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idKardex = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA EL DETALLE DEL MOVIMIENTO
			$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idMovimiento, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idKardex, "int"),
				valTpDato($cantDespachada, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
				valTpDato(((str_replace(",", "", $frmTotalDcto['txtDescuento']) * $precioUnitario) / 100), "real_inglesa"),
				valTpDato(0, "int"), // 0 = Unitario, 1 = Import
				valTpDato(0, "boolean"), // 0 = No, 1 = Si
				valTpDato("", "int"),
				valTpDato("", "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$updateSQL = sprintf("UPDATE iv_pedido_venta_detalle SET
				pendiente = %s,
				estatus = IF(%s = 0, 1, estatus)
			WHERE id_pedido_venta_detalle = %s;",
				valTpDato($cantPendiente, "int"),
				valTpDato($cantPendiente, "int"),
				valTpDato($idPedDet, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
			$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
			$Result1 = actualizarSaldos($idArticulo, $idCasilla);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
			
			$objResponse->assign("hddIdFactDet".$valor,"value",$idFacturaDetalle);
		}
	}
	
	// INSERTA LOS GASTOS DE LA FACTURA
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$idGasto = $frmTotalDcto['hddIdGasto'.$valor];
			
			$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
			$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
			
			if (round($txtMontoGasto, 2) != 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_gasto (id_factura, id_gasto, tipo, porcentaje_monto, monto)
				SELECT %s, id_gasto, %s, %s, %s FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
					valTpDato($txtPorcGasto, "real_inglesa"),
					valTpDato($txtMontoGasto, "real_inglesa"),
					valTpDato($idGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$hddIdFacturaGasto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				if ($hddPagaImpuesto == 1) {
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					$contIvaGasto = 0;
					if (isset($arrayObjIvaGasto)) {
						foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
							$valor1 = explode(":", $valor1);
							if ($valor1[0] == $valor) {
								$contIvaGasto++;
								
								$hddIdIvaGasto = $frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]];
								$hddIvaGasto = $frmTotalDcto['hddIvaGasto'.$valor.':'.$valor1[1]];
								$hddEstatusIvaGasto = $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$valor1[1]];
								
								$insertSQL = sprintf("INSERT INTO cj_cc_factura_gasto_impuesto (id_factura_gasto, id_impuesto, impuesto) 
								VALUE (%s, %s, %s);",
									valTpDato($hddIdFacturaGasto, "int"),
									valTpDato($hddIdIvaGasto, "int"),
									valTpDato($hddIvaGasto, "real_inglesa"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
					
					$hddIdIvaGasto = ($contIvaGasto == 1) ? $hddIdIvaGasto : "";
					$hddIvaGasto = ($contIvaGasto == 1) ? $hddIvaGasto : 0;
					$hddEstatusIvaGasto = ($contIvaGasto == 1) ? $hddEstatusIvaGasto : "1";
					
					// EDITA EL IMPUESTO
					$updateSQL = sprintf("UPDATE cj_cc_factura_gasto SET
						id_iva = %s,
						iva = %s,
						estatus_iva = %s
					WHERE id_factura_gasto = %s;",
						valTpDato($hddIdIvaGasto, "int"),
						valTpDato($hddIvaGasto, "real_inglesa"),
						valTpDato($hddEstatusIvaGasto, "boolean"),
						valTpDato($hddIdFacturaGasto, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
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
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("FA", "text"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC, 5 = CH
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// MODIFICA EL ESTATUS DEL PEDIDO DE VENTA
	$estatusPedidoVenta = 3;
	$updateSQL = sprintf("UPDATE iv_pedido_venta SET
		estatus_pedido_venta = %s
	WHERE id_pedido_venta = %s;",
		valTpDato($estatusPedidoVenta, "int"),
		valTpDato($frmDcto['txtIdPedido'], "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// CALCULO DE LAS COMISIONES
	$Result1 = generarComision($idFactura);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
	
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
	
	// SE CONECTA CON EL SISTEMA DE SOLICITUDES
	$Result1 = actualizarEstatusSistemaSolicitud($rowPedido['id_pedido_venta_referencia'], $estatusPedidoVenta);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		errorGuardarDcto($objResponse);
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$objResponse->alert($Result1[1]);
	}
	
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	// INSERTA EL PAGO DEL DOCUMENTO (PAGO DE FACTURAS) SOLO SI ES DE CONTADO
	if ($idTipoPago == 1) { // 0 = Credito, 1 = Contado
		$idCaja = 2; // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		
		// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
		$queryAperturaCaja = sprintf("SELECT * FROM sa_iv_apertura
		WHERE idCaja = %s
			AND statusAperturaCaja IN (1,2)
			AND (sa_iv_apertura.id_empresa = %s
				OR sa_iv_apertura.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
												WHERE suc.id_empresa = %s));",
			valTpDato($idCaja, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsAperturaCaja = mysql_query($queryAperturaCaja);
		if (!$rsAperturaCaja) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$fechaRegistroPago = $rowAperturaCaja["fechaAperturaCaja"];
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(44, "int"), // 44 = Recibo de Pago Repuestos y Servicios
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
                if($rowNumeracion['numero_actual'] == ""){ errorGuardarDcto($objResponse); return $objResponse->alert("No se ha configurado numeracion de comprobantes de pago"); }
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s)",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago, "date"),
			valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = OT (Relacion Tabla tipodedocumentos)
			valTpDato(0, "int"),		
			valTpDato($idFactura, "int"),
			valTpDato($idModulo, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoReciboPago = mysql_insert_id();
	
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
		VALUES (%s, %s)",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoPago = mysql_insert_id();
		
		foreach($arrayObj2 as $indicePago => $valorPago) {
			if (isset($frmListaPagos['txtFormaPago'.$valorPago])) {
				if ($frmListaPagos['txtFormaPago'.$valorPago] == 1) { // EFECTIVO
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = "-";
					$tipoCheque = "-";
					$campo = "saldoEfectivo";
					$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 2) { // CHEQUE
					$bancoCliente = $frmListaPagos['txtBancoCliente'.$valorPago];
					$bancoCompania = 1;
					$numeroCuenta = $frmListaPagos['txtNumeroCuenta'.$valorPago];
					$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "0";
					$campo = "saldoCheques";
					$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 3) { // DEPOSITO
					$bancoCliente = '1';
					$bancoCompania = $frmListaPagos['txtBancoCompania'.$valorPago];
					$numeroCuenta = asignarNumeroCuenta($frmListaPagos['txtNumeroCuenta'.$valorPago]);
					$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoDepositos";
					$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 4) { // TRANSFERENCIA BANCARIA
					$bancoCliente = $frmListaPagos['txtBancoCliente'.$valorPago];
					$bancoCompania = $frmListaPagos['txtBancoCompania'.$valorPago];
					$numeroCuenta = asignarNumeroCuenta($frmListaPagos['txtNumeroCuenta'.$valorPago]);
					$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoTransferencia";
					$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 5) { // TARJETA DE CREDITO
					$bancoCliente = $frmListaPagos['txtBancoCliente'.$valorPago];
					$bancoCompania = $frmListaPagos['txtBancoCompania'.$valorPago];
					$numeroCuenta = asignarNumeroCuenta($frmListaPagos['txtNumeroCuenta'.$valorPago]);
					$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoTarjetaCredito";
					$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 6) { // TARJETA DE DEBITO
					$bancoCliente = $frmListaPagos['txtBancoCliente'.$valorPago];
					$bancoCompania = $frmListaPagos['txtBancoCompania'.$valorPago];
					$numeroCuenta = asignarNumeroCuenta($frmListaPagos['txtNumeroCuenta'.$valorPago]);
					$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoTarjetaDebito";
					$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 7) { // ANTICIPO
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = $frmListaPagos['txtIdDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoAnticipo";
					$txtMonto = 0;
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 8) { // NOTA CREDITO
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = $frmListaPagos['txtIdDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoNotaCredito";
					$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 9) { // RETENCION
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoRetencion";
					$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 10) { // RETENCION ISLR
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = $frmListaPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoRetencion";
					$txtMonto = str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
				}
				
				$sqlSelectDatosAperturaCaja = sprintf("SELECT saldoCaja, id, %s
				FROM sa_iv_apertura
				WHERE idCaja = %s
					AND statusAperturaCaja IN (1,2)
					AND (sa_iv_apertura.id_empresa = %s
						OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = sa_iv_apertura.id_empresa))",
					$campo,
					valTpDato($idCaja, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
				$rsSelectDatosAperturaCaja = mysql_query($sqlSelectDatosAperturaCaja);
				if (!$rsSelectDatosAperturaCaja) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowSelectDatosAperturaCaja = mysql_fetch_array($rsSelectDatosAperturaCaja);
				
				// NO SUMA ANTICIPOS (7) EN EL SALDO DE LA CAJA, YA QUE ESTOS SE SUMAN EN LAS DEMAS FORMAS DE PAGO (EF, CH, DP, TB, TC, TD)
				$updateSQL = sprintf("UPDATE sa_iv_apertura SET
					%s = %s,
					saldoCaja = %s
				WHERE id = %s;",
					$campo,
					valTpDato($rowSelectDatosAperturaCaja[$campo] + str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]), "real_inglesa"),
					valTpDato($rowSelectDatosAperturaCaja['saldoCaja'] + $txtMonto, "real_inglesa"),
					valTpDato($rowSelectDatosAperturaCaja['id'], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// INSERTA LOS PAGOS DEL DOCUMENTO
				$insertSQL = sprintf("INSERT INTO sa_iv_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_encabezado_rs)
				VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
					valTpDato($frmListaPagos['txtFormaPago'.$valorPago], "int"),
					valTpDato($numeroDocumento, "text"),
					valTpDato($bancoCliente, "int"),
					valTpDato($bancoCompania, "int"),
					valTpDato($numeroCuenta, "text"),
					valTpDato($frmListaPagos['txtMonto'.$valorPago], "real_inglesa"),
					valTpDato($numeroActualFactura, "text"),
					valTpDato($tipoCheque, "text"),
					valTpDato(1, "int"),
					valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
					valTpDato($idCaja, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					valTpDato(0, "int"),
					valTpDato(1, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
					valTpDato($idEncabezadoPago, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idPago = mysql_insert_id();
				
				$arrayIdDctoContabilidad[] = array(
					$idPago,
					$idModulo,
					"CAJAENTRADA");
				
				if ($frmListaPagos['txtFormaPago'.$valorPago] == 3) { // DEPOSITO
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
								valTpDato(date("Y-m-d",strtotime($frmListaPagos['txtFechaDeposito'.$valorPago])), "date"),
								valTpDato($arrayFormaPago[$indiceDeposito], "int"),
								valTpDato($bancoDetalleDeposito, "int"),
								valTpDato($nroCuentaDetalleDeposito, "text"),
								valTpDato($nroChequeDetalleDeposito, "text"),
								valTpDato($arrayMonto[$indiceDeposito], "real_inglesa"),
								valTpDato(1, "int"),
								valTpDato($idCaja, "int"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						}
					}
				} else if (in_array($frmListaPagos['txtFormaPago'.$valorPago], array(5,6))) { // T. CREDITO Y DEBITO
					$sqlSelectRetencionPunto = sprintf("SELECT id_retencion_punto FROM te_retencion_punto
					WHERE id_cuenta = %s
						AND id_tipo_tarjeta = %s",
						valTpDato($frmListaPagos['txtNumeroCuenta'.$valorPago], "int"),
						valTpDato($frmListaPagos['txtTipoTarjeta'.$valorPago], "int"));
					$rsSelectRetencionPunto = mysql_query($sqlSelectRetencionPunto);
					if (!$rsSelectRetencionPunto) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowSelectRetencionPunto = mysql_fetch_array($rsSelectRetencionPunto);
					
					$insertSQL = sprintf("INSERT INTO cj_cc_retencion_punto_pago (id_caja, id_pago, id_tipo_documento, id_retencion_punto)
					VALUES (%s, %s, %s, %s)",
						valTpDato($idCaja, "int"),
						valTpDato($idPago, "int"),
						valTpDato(1, "int"),
						valTpDato($rowSelectRetencionPunto['id_retencion_punto'], "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 7) { // ANTICIPO
					$sqlSelectAnticipo = sprintf("SELECT * FROM cj_cc_anticipo WHERE idAnticipo = %s",
						valTpDato($frmListaPagos['txtIdDocumento'.$valorPago], "int"));
					$rsSelectAnticipo = mysql_query($sqlSelectAnticipo);
					if (!$rsSelectAnticipo) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowSelectAnticipo = mysql_fetch_array($rsSelectAnticipo);
					
					$totalAnticipo = $rowSelectAnticipo['saldoAnticipo'] - str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
					$estatusAnticipo = ($totalAnticipo == 0) ? 3 : 2;
					
					$updateSQL = sprintf("UPDATE cj_cc_anticipo SET
						saldoAnticipo = %s,
						estadoAnticipo = %s
					WHERE idAnticipo = %s",
						valTpDato($totalAnticipo, "real_inglesa"),
						valTpDato($estatusAnticipo, "int"),
						valTpDato($frmListaPagos['txtIdDocumento'.$valorPago], "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 8) { // NOTA CREDITO
					$sqlSelectNotaCredito = sprintf("SELECT * FROM cj_cc_notacredito WHERE idNotaCredito = %s",
						valTpDato($frmListaPagos['txtIdDocumento'.$valorPago], "int"));
					$rsSelectNotaCredito = mysql_query($sqlSelectNotaCredito);
					if (!$rsSelectNotaCredito) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowSelectNotaCredito = mysql_fetch_array($rsSelectNotaCredito);
					
					$totalNotaCredito = $rowSelectNotaCredito['saldoNotaCredito'] - str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
					$estatusNotaCredito = ($totalNotaCredito == 0) ? 3 : 2;
					
					$updateSQL = sprintf("UPDATE cj_cc_notacredito SET
						saldoNotaCredito = %s,
						estadoNotaCredito = %s
					WHERE idNotaCredito = %s",
						valTpDato($totalNotaCredito, "real_inglesa"),
						valTpDato($estatusNotaCredito, "int"),
						valTpDato($frmListaPagos['txtIdDocumento'.$valorPago], "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				} else if ($frmListaPagos['txtFormaPago'.$valorPago] == 9) { // RETENCION
					$sqlSelectFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
						valTpDato($idFactura, "int"));
					$rsSelectFactura = mysql_query($sqlSelectFactura);
					if (!$rsSelectFactura) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowSelectFactura = mysql_fetch_array($rsSelectFactura);
					
					$porcentajeAlicuota = $rowSelectFactura['porcentajeIvaFactura'] + $rowSelectFactura['porcentajeIvaDeLujoFactura'];
					$impuestoIva = $rowSelectFactura['calculoIvaFactura'] + $rowSelectFactura['calculoIvaDeLujoFactura'];
					$porcentajeRetenido = ($impuestoIva > 0) ? $frmListaPagos['txtMonto'.$valor] * 100 / $impuestoIva : 0;
					
					$insertSQL = sprintf("INSERT INTO cj_cc_retencioncabezera (numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idCliente, idRegistrosUnidadesFisicas)
					VALUES (%s, %s, %s, %s, %s, %s)",
						valTpDato($frmListaPagos['txtNumeroDocumento'.$valorPago], "text"),
						valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
						valTpDato(date("Y",strtotime($fechaRegistroPago)), "text"),
						valTpDato(date("m",strtotime($fechaRegistroPago)), "text"),
						valTpDato($frmDcto['txtIdCliente'], "int"),
						valTpDato(0, "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}
				
				// INSERTA EL DETALLE DEL RECIBO DE PAGO
				$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
				VALUES (%s, %s)",
					valTpDato($idEncabezadoReciboPago, "int"),
					valTpDato($idPago, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
		}
		
		// ACTUALIZA SALDOS Y ESTATUS DEL DOCUMENTO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		if (str_replace(",", "", $frmListaPagos['txtMontoPorPagar']) > 0) {
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				saldoFactura = %s,
				estadoFactura = 2
			WHERE idFactura = %s;",
				valTpDato($frmListaPagos['txtMontoPorPagar'], "real_inglesa"),
				valTpDato($idFactura, "int"));
		} else {
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				saldoFactura = 0,
				estadoFactura = 1
			WHERE idFactura = %s;",
				valTpDato($idFactura, "int"));
		}
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	}
	}
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
		creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
													WHERE fact_vent.idCliente = cliente_emp.id_cliente
														AND fact_vent.id_empresa = cliente_emp.id_empresa
														AND fact_vent.estadoFactura IN (0,2)), 0)
											+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
													WHERE nota_cargo.idCliente = cliente_emp.id_cliente
														AND nota_cargo.id_empresa = cliente_emp.id_empresa
														AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
											- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
													WHERE cxc_ant.idCliente = cliente_emp.id_cliente
														AND cxc_ant.id_empresa = cliente_emp.id_empresa
														AND cxc_ant.estadoAnticipo IN (1,2)
														AND cxc_ant.estatus = 1), 0)
											- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
													WHERE nota_cred.idCliente = cliente_emp.id_cliente
														AND nota_cred.id_empresa = cliente_emp.id_empresa
														AND nota_cred.estadoNotaCredito IN (1,2)), 0)
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
		creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
									WHERE fact_vent.idCliente = cliente_emp.id_cliente
										AND fact_vent.id_empresa = cliente_emp.id_empresa
										AND fact_vent.estadoFactura IN (0,2)), 0)
							+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
									WHERE nota_cargo.idCliente = cliente_emp.id_cliente
										AND nota_cargo.id_empresa = cliente_emp.id_empresa
										AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
							- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
									WHERE cxc_ant.idCliente = cliente_emp.id_cliente
										AND cxc_ant.id_empresa = cliente_emp.id_empresa
										AND cxc_ant.estadoAnticipo IN (1,2)
										AND cxc_ant.estatus = 1), 0)
							- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
									WHERE nota_cred.idCliente = cliente_emp.id_cliente
										AND nota_cred.id_empresa = cliente_emp.id_empresa
										AND nota_cred.estadoNotaCredito IN (1,2)), 0)
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
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
	
	errorGuardarDcto($objResponse);
	$objResponse->alert("Factura Guardada con Éxito");
	
	switch ($idTipoPago) { // 0 = Credito, 1 = Contado
		case 0 : $objResponse->script(sprintf("window.location.href='cjrs_factura_venta_list.php';")); break;
		case 1 :
			$objResponse->script(sprintf("window.location.href='cjrs_factura_venta_list.php';"));
			//$objResponse->script(sprintf("window.location.href='cjrs_facturas_por_pagar_form.php?id_factura=%s';", $idFactura));
			break;
	}
	
	$objResponse->script("verVentana('../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=".$idFactura."', 960, 550);");
	
	if ($idPago > 0) {
		$objResponse->script(sprintf("verVentana('reportes/cjrs_comprobante_pago_factura_pdf.php?valBusq=%s',960,550)", $idFactura));
	}
	
	return $objResponse;
}

// FUNCIONES AGREGADAS EL 12-05-2014
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

function buscarAnticipoNotaCredito($frmBuscarAnticipoNotaCredito, $frmDcto, $frmDetallePago, $frmListaPagos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	if (isset($arrayObj2)) {
		foreach($arrayObj2 as $indicePago => $valorPago) {
			if ($frmListaPagos['txtFormaPago'.$valorPago] == $frmDetallePago['selTipoPago']) {
				$arrayIdDocumento[] = $frmListaPagos['txtIdDocumento'.$valorPago];
			}
		}
	}
		
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarAnticipoNotaCredito['txtCriterioAnticipoNotaCredito'],
		$frmDcto['txtIdCliente'],
		$frmDetallePago['selTipoPago'],
		(($arrayIdDocumento) ? implode(",",$arrayIdDocumento) : ""));
		
	$objResponse->loadCommands(listaAnticipoNotaCredito(0,"","",$valBusq));
	
	return $objResponse;
}

function calcularPagos($frmListaPagos, $frmTotalDcto) {
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
	
	$objResponse->assign("txtMontoPagadoFactura","value",number_format($txtMontoPagadoFactura,2,".",","));
	$objResponse->assign("txtMontoPorPagar","value",number_format(str_replace(",", "", $frmTotalDcto['txtTotalOrden']) - $txtMontoPagadoFactura,2,".",","));
	
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
	
	$objResponse->assign("txtTotalDeposito","value",number_format($txtMontoPagadoDeposito,2,".",","));
	$objResponse->assign("txtSaldoDepositoBancario","value",number_format(str_replace(",", "", $frmDetallePago['txtMontoPago']) - $txtMontoPagadoDeposito,2,".",","));
	
	return $objResponse;
}

function cargaLstBancoCliente($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT idBanco, nombreBanco FROM bancos WHERE idBanco <> 1 ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected."  value=\"".$row["idBanco"]."\">".utf8_encode($row["nombreBanco"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstBancoCompania($tipoPago = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT idBanco, (SELECT nombreBanco FROM bancos WHERE bancos.idBanco = cuentas.idBanco) AS banco FROM cuentas GROUP BY cuentas.idBanco ORDER BY banco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select name=\"selBancoCompania\" id=\"selBancoCompania\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstCuentaCompania(this.value,".$tipoPago.");\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected."  value=\"".$row["idBanco"]."\">".utf8_encode($row["banco"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselBancoCompania","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCuentaCompania($idBanco, $tipoPago, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas
	WHERE idBanco = %s
		AND estatus = 1",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select name=\"selNumeroCuenta\" id=\"selNumeroCuenta\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstTarjetaCuenta(this.value,".$tipoPago.");\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idCuentas'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstTarjetaCuenta($row["idCuentas"], $tipoPago)); }
		
		$html .= "<option ".$selected." value=\"".$row["idCuentas"]."\">".utf8_encode($row["numeroCuentaCompania"])."</option>";	
	}
	$html .= "</select>";
	$objResponse->assign("divselNumeroCuenta","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTarjetaCuenta($idCuenta, $tipoPago, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 5) { // Tarjeta de Credito
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
			if ($totalRows == 1) { $objResponse->loadCommands(asignarPorcentajeTarjetaCredito($idCuenta, $row["idTipoTarjetaCredito"])); }
			
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

function cargaLstTipoPago($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM formapagos WHERE idFormaPago NOT IN (11) ORDER BY nombreFormaPago ASC;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"selTipoPago\" name=\"selTipoPago\" class=\"inputHabilitado\" onchange=\"asignarTipoPago(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idFormaPago']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row["idFormaPago"]."\">".$row["nombreFormaPago"]."</option>";
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
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPagoDetalleDeposito($row["idFormaPago"])); }
		
		$html .= "<option ".$selected." value=\"".$row["idFormaPago"]."\">".$row["nombreFormaPago"]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarSaldoDocumento($idDocumento, $formaPago) {
	$objResponse = new xajaxResponse();
	
	if ($formaPago == 7) { // ANTICIPOS
		$documento = "Anticipo";
		
		$query = sprintf("SELECT
			saldoAnticipo AS saldoDocumento,
			numeroAnticipo AS numeroDocumento
		FROM cj_cc_anticipo
		WHERE idAnticipo = %s;",
			valTpDato($idDocumento, "int"));
	} else { // NOTAS DE CREDITO
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
	
	if ($frmListaPagos['txtFormaPago'.$pos] == 3)
		$objResponse->script("xajax_eliminarDetalleDeposito(".$pos.",xajax.getFormValues('frmDetallePago'))");
		
	$objResponse->script("
	fila = document.getElementById('trItmPago:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmTotalDcto'))");
	
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

function insertarPago($frmListaPagos, $frmDetallePago, $frmDeposito, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (str_replace(",", "", $frmTotalDcto['txtTotalOrden']) < str_replace(",", "", $frmDetallePago['txtMontoPago'])) {
		errorInsertarPago($objResponse);
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la Factura");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	$contFila = $arrayObj2[count($arrayObj2)-1] + 1;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia Bancaria, 5 = Tarjeta de Credito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Credito, 9 = Retencion, 10 = Retencion ISLR, 11 = Otro
	if (in_array($frmDetallePago['selTipoPago'],array(3,4,5,6))) {
		$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",
			valTpDato($frmDetallePago['selNumeroCuenta'], "int"));
		$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta);
		if (!$rsBuscarNumeroCuenta) { errorInsertarPago($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	}
	
	if ($frmDetallePago['selTipoPago'] == 1) {
		$tipoPago = "Efectivo";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = "-";
		$idDocumento = "-";
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	} else if ($frmDetallePago['selTipoPago'] == 2) {
		$tipoPago = "Cheque";
		$tipoTarjetaCredito = "-";
		$bancoCliente = asignarBanco($frmDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $frmDetallePago['txtNumeroCuenta'];
		$numeroControl = $frmDetallePago['txtNumeroDctoPago'];
		$idDocumento = "-";
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = $frmDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = $frmDetallePago['txtNumeroCuenta'];
		$tipoTarjetaOculto = "-";
	} else if ($frmDetallePago['selTipoPago'] == 3) {
		$tipoPago = "Deposito";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = $frmDetallePago['txtFechaDeposito'];
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = asignarBanco($frmDetallePago['selBancoCompania']);
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $frmDetallePago['txtNumeroDctoPago'];
		$idDocumento = "-";
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = $frmDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $frmDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = "-";
	} else if ($frmDetallePago['selTipoPago'] == 4) {
		$tipoPago = "Transferencia Bancaria";
		$tipoTarjetaCredito = "-";
		$bancoCliente = asignarBanco($frmDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = asignarBanco($frmDetallePago['selBancoCompania']);
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $frmDetallePago['txtNumeroDctoPago'];
		$idDocumento = "-";
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = $frmDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $frmDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $frmDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = "-";
	} else if ($frmDetallePago['selTipoPago'] == 5) {
		$tipoPago = "Tarjeta de Credito";
		$tipoTarjetaCredito = $frmDetallePago['tarjeta'];
		$bancoCliente = asignarBanco($frmDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = $frmDetallePago['porcentajeRetencion'];
		$montoTotalRetencion = $frmDetallePago['montoTotalRetencion'];
		$bancoCompania = asignarBanco($frmDetallePago['selBancoCompania']);
		$porcentajeComision = $frmDetallePago['porcentajeComision'];
		$montoTotalComision = $frmDetallePago['montoTotalComision'];
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $frmDetallePago['txtNumeroDctoPago'];
		$idDocumento = "-";
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = $frmDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $frmDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $frmDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = $frmDetallePago['tarjeta'];
	} else if ($frmDetallePago['selTipoPago'] == 6) {
		$tipoPago = "Tarjeta de Debito";
		$tipoTarjetaCredito = "-";
		$bancoCliente = asignarBanco($frmDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = asignarBanco($frmDetallePago['selBancoCompania']);
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $frmDetallePago['txtNumeroDctoPago'];
		$idDocumento = "-";
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = $frmDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $frmDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $frmDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = 6;
	} else if ($frmDetallePago['selTipoPago'] == 7) {
		$tipoPago = "Anticipo";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $frmDetallePago['txtNumeroDctoPago'];
		$idDocumento = $frmDetallePago['hddIdAnticipoNotaCredito'];
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	} else if ($frmDetallePago['selTipoPago'] == 8) {
		$tipoPago = "Nota de Credito";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $frmDetallePago['txtNumeroDctoPago'];
		$idDocumento = $frmDetallePago['hddIdAnticipoNotaCredito'];
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	} else if ($frmDetallePago['selTipoPago'] == 9) {
		$tipoPago = "Retencion";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $frmDetallePago['txtNumeroDctoPago'];
		$idDocumento = "-";
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	} else if ($frmDetallePago['selTipoPago'] == 10) {
		$tipoPago = "Retencion ISLR";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $frmDetallePago['txtNumeroDctoPago'];
		$idDocumento = "-";
		$montoPagado = str_replace(",", "", $frmDetallePago['txtMontoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$objResponse->script(sprintf("$('#trItmPiePago').before('".
		"<tr align=\"left\" id=\"trItmPago:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmPago:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx2\" name=\"cbx2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMonto%s\" name=\"txtMonto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><button type=\"button\" onclick=\"confirmarEliminarPago(%s);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"/></button>".
				"<input type=\"hidden\" id=\"txtFechaDeposito%s\" name=\"txtFechaDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtFormaPago%s\" name=\"txtFormaPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtNumeroDocumento%s\" name=\"txtNumeroDocumento%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdDocumento%s\" name=\"txtIdDocumento%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtBancoCompania%s\" name=\"txtBancoCompania%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtBancoCliente%s\" name=\"txtBancoCliente%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtNumeroCuenta%s\" name=\"txtNumeroCuenta%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtTipoTarjeta%s\" name=\"txtTipoTarjeta%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$tipoPago,
			$bancoCliente,
			$bancoCompania,
			$numeroCuenta,
			$numeroControl,
			$contFila, $contFila, number_format($montoPagado, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $fechaDeposito,
				$contFila, $contFila, $frmDetallePago['selTipoPago'],
				$contFila, $contFila, $numeroControl,
				$contFila, $contFila, $idDocumento,
				$contFila, $contFila, $bancoCompaniaOculto,
				$contFila, $contFila, $bancoClienteOculto,
				$contFila, $contFila, $numeroCuentaOculto,
				$contFila, $contFila, $tipoTarjetaOculto));
			
	if ($frmDetallePago['selTipoPago'] == 3) {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = explode("|", $frmDeposito['hddObjDetallePagoDeposito']);
		
		$cadenaFormaPagoDeposito = '';
		$cadenaNroDocumentoDeposito = '';
		$cadenaBancoClienteDeposito = '';
		$cadenaNroCuentaDeposito = '';
		$cadenaMontoDeposito = '';
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
	}
	
	$arrayObjDetallePago[] = $contFila;
	foreach($arrayObjDetallePago as $indiceDetallePago => $valorDetallePago) {
		$cadena = $frmDetallePago['hddObjDetallePago']."|".$valorDetallePago;
	}
	
	$objResponse->assign("hddObjDetallePago","value",$cadena);
	if ($frmDetallePago['selTipoPago'] == 3) {
		$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosicionDeposito);
		$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPagoDeposito);
		$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBancoClienteDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuentaDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroDocumentoDeposito);
		$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMontoDeposito);
	}
	
	errorInsertarPago($objResponse);
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmTotalDcto'))");
	
	$objResponse->script("
	xajax_cargaLstTipoPago('1');
	asignarTipoPago('1');");
	
	switch ($frmDetallePago['selTipoPago']) {
		case 3 : // 3 = DEPOSITO
			$objResponse->script("
			byId('imgCerrarDivFlotante1').click();"); break;
		case 7 : // 7 = ANTICIPO
			$objResponse->script("
			byId('imgCerrarDivFlotante2').click();
			byId('imgCerrarDivFlotante1').click();"); break;
		case 8 : // 8 = NOTA CREDITO
			$objResponse->script("
			byId('imgCerrarDivFlotante2').click();
			byId('imgCerrarDivFlotante1').click();"); break;
	}
	
	return $objResponse;
}

function insertarPagoDeposito($frmDeposito) {
	$objResponse = new xajaxResponse();
		
	if (str_replace(",", "", $frmDeposito['txtMontoDeposito']) > str_replace(",", "", $frmDeposito['txtSaldoDepositoBancario'])) {
		errorInsertarPagoDeposito($objResponse);
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
	
	errorInsertarPagoDeposito($objResponse);
	
	$objResponse->script("
	xajax_cargaLstTipoPagoDetalleDeposito('1');
	asignarTipoPagoDetalleDeposito('1');");
	
	$objResponse->script("xajax_calcularPagosDeposito(xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmDetallePago'))");
	
	return $objResponse;
}

function listaAnticipoNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	if ($valCadBusq[2] == 7) { // ANTICIPOS
		$campoIdCliente = "idCliente";
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$campoIdCliente = "idCliente";
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("%s = %s
	AND (dcto.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
			WHERE suc.id_empresa_padre = dcto.id_empresa)
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = dcto.id_empresa)
		OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
											WHERE suc.id_empresa = dcto.id_empresa))",
		$campoIdCliente,
		valTpDato($valCadBusq[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[2] == 7) { // ANTICIPOS
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado)
		$sqlBusq .= $cond.sprintf("estadoAnticipo IN (1,2)
			AND estatus = 1"); // 0 = Anulado ; 1 = Activo
	} else { // NOTAS DE CREDITO
		$sqlBusq .= $cond.sprintf("estadoNotaCredito IN (1,2)");
	}
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 7) { // ANTICIPOS
			$sqlBusq .= $cond.sprintf("(numeroAnticipo LIKE %s
			OR dcto.observacionesAnticipo LIKE %s)",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		} else { // NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf("(numeracion_nota_credito LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		}
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 7) { // ANTICIPOS
			$sqlBusq .= $cond.sprintf("idAnticipo NOT IN (%s)",
				valTpDato($valCadBusq[3], "campo"));
		} else { // NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf("idNotaCredito NOT IN (%s)",
				valTpDato($valCadBusq[3], "campo"));
		}
	}
	
	if ($valCadBusq[2] == 7) { // ANTICIPOS
		$query = sprintf("SELECT
			idAnticipo AS idDocumento,
			saldoAnticipo AS saldoDocumento,
			numeroAnticipo AS numeroDocumento,
			fechaAnticipo AS fechaDocumento,
			observacionesAnticipo AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_anticipo dcto
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else { // NOTAS DE CREDITO
		$query = sprintf("SELECT
			idNotaCredito AS idDocumento,
			saldoNotaCredito AS saldoDocumento,
			numeracion_nota_credito AS numeroDocumento,
			fechaNotaCredito AS fechaDocumento,
			observacionesNotaCredito AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notacredito dcto
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	}
			
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nQuery:".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCredito", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCredito", "10%", $pageNum, "fechaDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCredito", "10%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Documento"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCredito", "40%", $pageNum, "observacionDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Observaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCredito", "20%", $pageNum, "saldoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDcto%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblAnticipoNotaCredito', '%s', '%s');\"><button type=\"button\" title=\"Seleccionar\"><img class=\"puntero\" src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$row['idDocumento'],
					$valCadBusq[2]);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaDocumento']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroDocumento']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['observacionDocumento'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipoNotaCredito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarFechaCredito");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
// FUNCIONES AGREGADAS EL 12-05-2014
$xajax->register(XAJAX_FUNCTION,"asignarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCredito");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"calcularPagosDeposito");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuentaCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"formDeposito");
$xajax->register(XAJAX_FUNCTION,"insertarPago");
$xajax->register(XAJAX_FUNCTION,"insertarPagoDeposito");
$xajax->register(XAJAX_FUNCTION,"listaAnticipoNotaCredito");

// FUNCION AGREGADA EL 17-09-2012
function actualizarNumeroControl($idEmpresa, $idClaveMovimiento) {
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
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	return array(true, "");
}

function buscarNumeroControl($idEmpresa, $idClaveMovimiento) {
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

function errorGuardarDcto($objResponse) {
	$objResponse->script("
	byId('btnGuardar').disabled = false;
	byId('btnCancelar').disabled = false;");
}

function insertarItemArticulo($contFila, $hddIdPedidoDet = "", $hddIdPresupuestoDet = "", $idArticulo = "", $idCasilla = "", $hddIdArticuloAlmacenCosto = "", $hddIdArticuloCosto = "", $cantPedida = "", $cantPendiente = "", $hddIdPrecioItm = "", $precioUnitario = "", $costoUnitario = "", $abrevMonedaCostoUnitario = "", $idIva = "") {
	$contFila++;
	
	if ($hddIdPedidoDet > 0) {
		$totalRowsPresupuestoDetalle = 1;
		
		$queryIdEmpresa = sprintf("(SELECT ped_vent.id_empresa
		FROM iv_pedido_venta_detalle ped_vent_det
			INNER JOIN iv_pedido_venta ped_vent ON (ped_vent_det.id_pedido_venta = ped_vent.id_pedido_venta)
		WHERE ped_vent_det.id_pedido_venta_detalle = %s)",
			valTpDato($hddIdPedidoDet, "int"));
		$rsEmpresa = mysql_query($queryIdEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		$idEmpresa = $rowEmpresa['id_empresa'];
	
		// VERIFICA VALORES DE CONFIGURACION (Manejar Costo de Repuesto)
		$queryConfig12 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 12 AND config_emp.status = 1
			AND config_emp.id_empresa IN (SELECT ped_vent.id_empresa
										FROM iv_pedido_venta_detalle ped_vent_det
											INNER JOIN iv_pedido_venta ped_vent ON (ped_vent_det.id_pedido_venta = ped_vent.id_pedido_venta)
										WHERE ped_vent_det.id_pedido_venta_detalle = %s);",
			valTpDato($hddIdPedidoDet, "int"));
		$rsConfig12 = mysql_query($queryConfig12);
		if (!$rsConfig12) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsConfig12 = mysql_num_rows($rsConfig12);
		$rowConfig12 = mysql_fetch_assoc($rsConfig12);
		
		// BUSCA EL ULTIMO COSTO DEL ARTICULO
		$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos WHERE id_articulo = %s AND id_empresa = %s ORDER BY fecha_registro DESC LIMIT 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsArtCosto = mysql_query($queryArtCosto);
		if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
		
		$costoUnitarioDet = (in_array($rowConfig12['valor'], array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
		$abrevMonedaCostoUnitario = $rowArtCosto['abreviacion'];
	}
	
	$costoUnitario = ($costoUnitario == "" && $totalRowsPresupuestoDetalle > 0) ? $costoUnitarioDet : $costoUnitario;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT art.*
	FROM iv_articulos art
		INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
	WHERE art.id_articulo = %s
		AND art_alm.id_casilla = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idCasilla, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
	$queryUbicacion = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
		valTpDato($idCasilla, "int"));
	$rsUbicacion = mysql_query($queryUbicacion);
	if (!$rsUbicacion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsUbicacion = mysql_num_rows($rsUbicacion);
	$rowUbicacion = mysql_fetch_assoc($rsUbicacion);
	
	$ubicacion = $rowUbicacion['descripcion_almacen']." ".$rowUbicacion['ubicacion'];
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
	FROM pg_iva iva
		INNER JOIN iv_articulos_impuesto art_impuesto ON (iva.idIva = art_impuesto.id_impuesto)
	WHERE art_impuesto.id_articulo = %s
		AND iva.tipo IN (6,2)
		AND %s IS NOT NULL;", 
		valTpDato($idArticulo, "int"),
		valTpDato($idIva, "int"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$contIva = 0;
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
		"<input id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	// BUSCA LOS PRECIOS DEL ARTICULO
	$queryArtPrecio = sprintf("SELECT * FROM vw_iv_articulos_precios
	WHERE id_articulo = %s
		AND estatus = 1
	ORDER BY porcentaje DESC;",
		valTpDato($idArticulo, "int"));
	$rsArtPrecio = mysql_query($queryArtPrecio);
	if (!$rsArtPrecio) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$htmlPreciosArt = "<table width=\"360\">";
	while ($rowArtPrecio = mysql_fetch_assoc($rsArtPrecio)) {
		$styleTr = ($rowArtPrecio['id_precio'] == $hddIdPrecioItm) ? "style=\"font-weight:bold\"" : "";
		
		$htmlPreciosArt .= "<tr align=\"left\" ".$styleTr.">";
			$htmlPreciosArt .= "<td>".utf8_encode($rowArtPrecio['descripcion_precio'])."</td>";
			$htmlPreciosArt .= "<td align=\"right\">".utf8_encode($rowArtPrecio['abreviacion']).number_format($rowArtPrecio['precio'], 2, ".", ",")."</td>";
		$htmlPreciosArt .= "</tr>";
		
		if ($rowArtPrecio['id_precio'] == $hddIdPrecioItm && $rowArtPrecio['tipo'] == 0) { // PRECIO SOBRE COSTO
			$utilidad = ((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($costoUnitario);
		} else if ($rowArtPrecio['id_precio'] == $hddIdPrecioItm && $rowArtPrecio['tipo'] == 1) { // PRECIO SOBRE VENTA
			$utilidad = ((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($precioUnitario);
		}
		$utilidad = number_format($utilidad, 2, ".", ",")."%";
	}
	if (in_array($hddIdPrecioItm, array(6,7,12))) {
		$utilidad = "S/V: ".number_format(((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($precioUnitario), 2, ".", ",")."%";
		$utilidad .= " - ";
		$utilidad .= "S/C: ".number_format(((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($costoUnitario), 2, ".", ",")."%";
	}
	$htmlPreciosArt .= "<tr><td colspan=\"2\"><hr></td></tr>";
	$htmlPreciosArt .= "<tr align=\"left\">";
		$htmlPreciosArt .= "<td><b>"."Costo:"."</b></td>";
		$htmlPreciosArt .= "<td align=\"right\"><b>".utf8_encode($abrevMonedaCostoUnitario).number_format($costoUnitario, 2, ".", ",")."</b></td>";
	$htmlPreciosArt .= "</tr>";
	$htmlPreciosArt .= "<tr align=\"left\">";
		$htmlPreciosArt .= "<td><b>"."Utl. Bruta:"."</b></td>";
		$htmlPreciosArt .= "<td align=\"right\"><b>".$utilidad."</b></td>";
	$htmlPreciosArt .= "</tr>";
	$htmlPreciosArt .= "</table>";
	
	// CREA LA TABLA DE GASTOS
	$queryDetGasto = sprintf("SELECT * FROM iv_pedido_venta_detalle_gastos
	WHERE id_pedido_venta_detalle = %s;",
		valTpDato($hddIdPedidoDet, "int"));
	$rsDetGasto = mysql_query($queryDetGasto);
	if (!$rsDetGasto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$contFilaObj = 0;
	$totalGastoArt = 0;
	$htmlGastoArtObj = "";
	while ($rowDetGasto = mysql_fetch_assoc($rsDetGasto)) {
		$contFilaObj++;
		
		$htmlGastoArtObj .= sprintf("<input type=\"hidden\" id=\"hddIdGastoArt:%s:%s\" name=\"hddIdGastoArt:%s:%s\" readonly=\"readonly\" value=\"%s\">",
			$contFila, $contFilaObj, $contFila, $contFilaObj, $rowDetGasto['id_gasto']);
		$htmlGastoArtObj .= sprintf("<input type=\"hidden\" id=\"txtMontoGastoArt:%s:%s\" name=\"txtMontoGastoArt:%s:%s\" readonly=\"readonly\" value=\"%s\"/>",
			$contFila, $contFilaObj, $contFila, $contFilaObj, $rowDetGasto['monto_gasto']);
		
		$totalGastoArt += $rowDetGasto['monto_gasto'];
	}
	
	$htmlGastoArt = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	$htmlGastoArt .= "<tr>";
		$htmlGastoArt .= "<td><a class=\"modalImg\" id=\"aGastoArt:".$contFila."\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\"/></a></td>";
		$htmlGastoArt .= "<td id=\"tdItmGastoObj:".$contFila."\" title=\"tdItmGastoObj:".$contFila."\">".$htmlGastoArtObj."</td>";
		$htmlGastoArt .= "<td width=\"100%\"><input type=\"text\" id=\"hddGastoItm".$contFila."\" name=\"hddGastoItm".$contFila."\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"".number_format($totalGastoArt, 2, ".", ",")."\"/></td>";
	$htmlGastoArt .= "</tr>";
	$htmlGastoArt .= "</table>";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" id=\"trItm:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><input type=\"text\" id=\"txtCodigoArtItm%s\" name=\"txtCodigoArtItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left;\" value=\"%s\"></td>".
			"<td><div id=\"tdDescripcionItm:%s\">%s</div>".
				"<span id=\"spnUbicacion%s\" class=\"textoNegrita_9px\">%s</span>".
				"%s</td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" readonly=\"readonly\" title=\"Costo Unitario\" value=\"%s\"/></td>".
			"<td id=\"tdIvaItm%s\">%s</td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFactDet%s\" name=\"hddIdFactDet%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedDet%s\" name=\"hddIdPedDet%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArt%s\" name=\"hddIdArt%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPrecioItm%s\" name=\"hddIdPrecioItm%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" readonly=\"readonly\" title=\"Casilla\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarItem:%s').onclick = function() { abrirDivFlotante1(this, 'tblArticulo', '%s', '%s'); }
		byId('aGastoArt:%s').onclick = function() { abrirDivFlotante1(this, 'tblLista', 'Gasto', '%s'); }
		
		byId('txtPrecioItm%s').onmouseover = function() { Tip('%s', TITLE, 'Lista de Precios'); }
		byId('txtPrecioItm%s').onmouseout = function() { UnTip(); }",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$contFila, $contFila,
			$contFila, $contFila, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowArticulo['descripcion'])))),
				$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
				(in_array($rowConfig12['valor'], array(1,2)) ? "" : "<br><span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</span>"),
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$contFila, $contFila, number_format($precioUnitario, 2, ".", ","),
				$contFila, $contFila, $costoUnitario,
			$contFila, $ivaUnidad,
			$contFila, $contFila, number_format((($cantPedida * $precioUnitario) + $totalGastoArt), 2, ".", ","),
				$contFila, $contFila, "",
				$contFila, $contFila, $hddIdPedidoDet,
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $hddIdPrecioItm,
				$contFila, $contFila, $idCasilla,
		
		$contFila, $contFila, $idArticulo,
		$contFila, $contFila,
		
		$contFila, $htmlPreciosArt,
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

// FUNCIONES AGREGADAS EL 12-05-2014
function asignarBanco($idBanco) {
	$query = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s;", valTpDato($idBanco, "int"));
	$rs = mysql_query($query) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	return $row['nombreBanco'];
}

function asignarNumeroCuenta($idCuenta) {
	$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",$idCuenta);
	$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	
	return $rowBuscarNumeroCuenta['numeroCuentaCompania'];
}

function errorInsertarPago($objResponse) {
	$objResponse->script("
	byId('btnGuardarDetallePago').disabled = false;
	byId('btnGuardar').disabled = false;
	byId('btnCancelar').disabled = false;");
}

function errorInsertarPagoDeposito($objResponse) {
	$objResponse->script("
	byId('btnGuardarDetalleDeposito').disabled = false;
	byId('btnGuardarDeposito').disabled = false;
	byId('btnCancelarDeposito').disabled = false;");
}

function validarAperturaCaja($idEmpresa, $fecha) {
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
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura
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
		return array(false, "Debe cerrar la caja del dia: ".date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("SELECT * FROM sa_iv_apertura
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