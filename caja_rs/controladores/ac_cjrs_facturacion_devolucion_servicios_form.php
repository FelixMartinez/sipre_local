<?php


//ASIGNA Y MUESTRA LA INFORMACION AL SELECCIONARLO EN EL LISTADO DE RECEPCION, LO AGREGA A LA ORDEN
function asignarValeRecepcion($idRecepcion, $accion = "", $valFormTotalDcto){
	$objResponse = new xajaxResponse();
		
	if ($accion == "" || $accion == 1) {
		$queryVerificarSiTieneUnaOrdenSinAsignar = sprintf("SELECT
			COUNT(*) AS nr_items
		FROM sa_orden
		WHERE sa_orden.id_recepcion = %s
			AND sa_orden.id_tipo_orden = 5",
			$idRecepcion);
		$rsVerificarSiTieneUnaOrdenSinAsignar = mysql_query($queryVerificarSiTieneUnaOrdenSinAsignar);
		if (!$rsVerificarSiTieneUnaOrdenSinAsignar) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowVerificarSiTieneUnaOrdenSinAsignar = mysql_fetch_assoc($rsVerificarSiTieneUnaOrdenSinAsignar);
		
		if ($rowVerificarSiTieneUnaOrdenSinAsignar['nr_items'] > 0) {
			$objResponse->alert("No puede escoger este vale, debido a que tiene asociado un tipo de Orden SIN ASIGNAR.");
			return $objResponse;
		}
	}
	
	if ($valFormTotalDcto['hddItemsCargados'] > 0) {
		$objResponse->alert('La orden tiene items cargados. Si desea escoger otro vale de recepcion, elimine los items cargados e intente nuevamente.');
	} else {
		$queryRecepcion = sprintf("SELECT *
									FROM vw_sa_vales_recepcion
									WHERE id_recepcion = %s",
						valTpDato($idRecepcion,"text"));
		$rsRecepcion = mysql_query($queryRecepcion);
		if (!$rsRecepcion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowRecepcion = mysql_fetch_assoc($rsRecepcion);
		
		$queryCliente = "SELECT cj_cc_cliente.*, CONCAT_WS('-',lci, ci) AS ci_cliente
		FROM sa_orden
		INNER JOIN cj_cc_cliente ON sa_orden.id_cliente = cj_cc_cliente.id
		WHERE id_orden =".$_GET["id"];
		$rsCliente = mysql_query($queryCliente);
		if (!$rsCliente) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowCliente = mysql_fetch_assoc($rsCliente);

		$objResponse->assign("txtIdValeRecepcion","value",$rowRecepcion['id_recepcion']);
		$objResponse->assign("numeracionRecepcionMostrar","value",$rowRecepcion['numeracion_recepcion']);
		$objResponse->assign("txtFechaRecepcion","value",$rowRecepcion['fecha_entrada']);
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre']." ".$rowCliente['apellido']));
		$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
		$objResponse->assign("txtTelefonosCliente","value",utf8_encode($rowCliente['telf']));
		$objResponse->assign("txtChasisVehiculo","value",utf8_encode($rowRecepcion['chasis']));
		$objResponse->assign("txtPlacaVehiculo","value",utf8_encode($rowRecepcion['placa']));	
		$objResponse->assign("hddIdUnidadBasica","value",$rowRecepcion['id_uni_bas']);
		$objResponse->assign("txtUnidadBasica","value",utf8_encode($rowRecepcion['nom_uni_bas']));
		$objResponse->assign("txtMarcaVehiculo","value",utf8_encode($rowRecepcion['nom_marca']));
		$objResponse->assign("hddIdModelo","value",utf8_encode($rowRecepcion['id_modelo']));
		$objResponse->assign("txtModeloVehiculo","value",utf8_encode($rowRecepcion['des_modelo']));
		$objResponse->assign("txtAnoVehiculo","value",utf8_encode($rowRecepcion['ano_uni_bas']));
		$objResponse->assign("txtColorVehiculo","value",utf8_encode($rowRecepcion['color']));
		$objResponse->assign("txtRifCliente","value",utf8_encode($rowCliente['ci_cliente']));
		$objResponse->assign("txtKilometrajeVehiculo","value",utf8_encode($rowRecepcion['kilometraje']));
				
		$anio=substr($rowRecepcion['fecha_venta'],0,4);
		$mes=substr($rowRecepcion['fecha_venta'],5,2);
		$dia=substr($rowRecepcion['fecha_venta'],8,2);
		$fecha=$dia."-".$mes."-".$anio; 
		
		$objResponse->assign("txtFechaVentaVehiculo","value",$fecha);
		
		if ($_GET["acc"] == 1) {
			if($rowCliente['descuento'] > 0)
				$objResponse->script(sprintf("
				if(confirm('El Cliente tiene %s%s de Descuento Directo. Desea agregarlo?'))
					byId('txtDescuento').value = %s;", $rowCliente['descuento'], "%", $rowCliente['descuento']));
		}
		
		/*
		if ($_GET['ret'] != 5){//este es el listado que molesta, gregor carga al iniciar y siempre elimina 
			if($_GET['doc_type'] != 3) {// 3 es facturacion			
				$objResponse->script("
				byId('lstTipoOrden').value = '-1';
				byId('lstTipoOrden').focus();");
			}
		}
		*/
			
		$objResponse->script("
		byId('lstTipoOrden').focus();
		byId('divFlotante2').style.display = 'none';
		byId('divFlotante').style.display = 'none';");
		
		$objResponse->script("xajax_calcularTotalDcto();");
	}	
	
	return $objResponse;
}

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


//BUSCA LOS MOVIMIENTOS DEL ARTICULO, OJO EN REPUESTO EN ORDEN Y LUEGO MUESTRA EL LISTADO
function buscarMtoArticulo($idDetalleOrden, $codigoArticulo, $descripcionArticulo){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$idDetalleOrden,
		$codigoArticulo,
		$descripcionArticulo);
	
	$objResponse->script("xajax_verMovimientosArticulo('0','id_solicitud','DESC','".addslashes($valBusq)."')");
	
	return $objResponse;	
}

function buscarNumeroControl($idEmpresa, $idClaveMovimiento, $nombreContenedor){
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Formato Nro. Control)
	$queryConfig401 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 401 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig401 = mysql_query($queryConfig401);
	if (!$rsConfig401) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig401 = mysql_num_rows($rsConfig401);
	$rowConfig401 = mysql_fetch_assoc($rsConfig401);
	
	if (!($totalRowsConfig401 > 0)) return $objResponse->alert("No existe un formato de numero de control establecido");
		
	$valor = explode("|",$rowConfig401['valor']);
	$separador = $valor[0];
	$formato = (strlen($separador) > 0) ? explode($separador,$valor[1]) : $valor[1];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
	WHERE id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
							WHERE clave_mov.id_clave_movimiento = %s)
		AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																		WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC
	LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	if (strlen($separador) > 0 && isset($formato)) {
		foreach($formato as $indice => $valor) {
			$numeroActualFormato[] = ($indice == count($formato)-1) ? str_pad($rowNumeracion['numero_actual'],strlen($valor),"0",STR_PAD_LEFT) : str_pad(0,strlen($valor),"0",STR_PAD_LEFT);
		}
		$numeroActualFormato = implode($separador, $numeroActualFormato);
	} else {
		$numeroActualFormato = str_pad($rowNumeracion['numero_actual'],strlen($formato),"0",STR_PAD_LEFT);
	}
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	
	$objResponse->assign($nombreContenedor,"value",$numeroActualFormato);
	
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
	
	$objResponse->assign("txtTotalDeposito","value",number_format($txtMontoPagadoDeposito, 2, ".", ","));
	$objResponse->assign("txtSaldoDepositoBancario","value",number_format(str_replace(",", "", $frmDetallePago['txtMontoPago']) - $txtMontoPagadoDeposito, 2, ".", ","));
	
	return $objResponse;
}




//SE EJECUTA 2 VECES, NO CUANDO ES NUEVO Y CALCULA EL TOTAL AL ABRIR LA ORDEN
function calcularDcto($valFormDcto, $valForm, $valFormTotalDcto, $valFormPaq, $valFormTemp, $valFormNotas, $valFormTot){
	$objResponse = new xajaxResponse();
        
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valFormTotalDcto['hddObj']); $cont++) {
		$caracter = substr($valFormTotalDcto['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	for ($contPaq = 0; $contPaq <= strlen($valFormTotalDcto['hddObjPaquete']); $contPaq++) {
		$caracterPaq = substr($valFormTotalDcto['hddObjPaquete'], $contPaq, 1);
		
		if ($caracterPaq != "|" && $caracterPaq != "")
			$cadenaPaq .= $caracterPaq;
		else {
			$arrayObjPaq[] = $cadenaPaq;
			$cadenaPaq = "";
		}	
	}
	
	for ($contTemp = 0; $contTemp <= strlen($valFormTotalDcto['hddObjTempario']); $contTemp++) {
		$caracterTemp = substr($valFormTotalDcto['hddObjTempario'], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp .= $caracterTemp;
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}	
	}
	
	for ($contTot = 0; $contTot <= strlen($valFormTotalDcto['hddObjTot']); $contTot++) {
		$caracterTot = substr($valFormTotalDcto['hddObjTot'], $contTot, 1);
		
		if ($caracterTot != "|" && $caracterTot != "")
			$cadenaTot .= $caracterTot;
		else {
			$arrayObjTot[] = $cadenaTot;
			$cadenaTot = "";
		}	
	}
	
	for ($contNota = 0; $contNota <= strlen($valFormTotalDcto['hddObjNota']); $contNota++) {
		$caracterNota = substr($valFormTotalDcto['hddObjNota'], $contNota, 1);
		
		if ($caracterNota != "|" && $caracterNota != "")
			$cadenaNota .= $caracterNota;
		else {
			$arrayObjNota[] = $cadenaNota;
			$cadenaNota = "";
		}	
	}
	
	for ($contDcto = 0; $contDcto <= strlen($valFormTotalDcto['hddObjDescuento']); $contDcto++) {
		$caracterDcto = substr($valFormTotalDcto['hddObjDescuento'], $contDcto, 1);
		
		if ($caracterDcto != "|" && $caracterDcto != "")
			$cadenaDcto .= $caracterDcto;
		else {
			$arrayObjDcto[] = $cadenaDcto;
			$cadenaDcto = "";
		}	
	}
        
	$arrayIvasOrden = $valFormTotalDcto["ivaActivo"];//array con los checkbox activos ivas
	foreach($arrayIvasOrden as $indice => $idIvaActivo){//lleno array con los ivas disponibles
		$arrayIvasBaseImponible[$idIvaActivo] = 0;
		$arrayIvasSubTotal[$idIvaActivo] = 0;
	}
        
	$subTotal = 0;
	$totalExento = 0;
//	$totalExonerado = 0;
//	$arrayIva = NULL;
//	$arrayDetalleIva = NULL;
	$montoExento = 0;
	$baseImponible = 0;
	
	$totalTempTotal = 0;
	$totalArtTotal = 0;
	
	//PAQUETES
	if (isset($arrayObjPaq)) {
		foreach($arrayObjPaq as $indicePaq => $valorPaq) {
			$objResponse->assign(sprintf("hddValorCheckAprobPaq%s",$valorPaq),"value", 0);
			
			if (isset($valFormPaq['cbxItmPaqAprob'])) {
				foreach($valFormPaq['cbxItmPaqAprob'] as $indiceAprob => $valorAprob) {
					if ($valorPaq == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobPaq%s",$valorPaq),"value",1);
						
						$subTotalPaq = $valFormPaq['hddPrecPaq'.$valorPaq];//$valForm['hddCantArt'.$valor]*
						$descuentoPaq = ($valFormTotalDcto['txtDescuento'] * $subTotalPaq) / 100;
						$subTotalPaq = $subTotalPaq - $descuentoPaq;
						
						
						$subTotal += doubleval($valFormPaq['hddPrecPaq'.$valorPaq]);
						
						$totalTempTotal += doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
						$totalArtTotal += doubleval($valFormPaq['hddTotalRptoPqte'.$valorPaq]);
						
						$totalExento += doubleval($valFormPaq['hddTotalExentoRptoPqte'.$valorPaq]);
						
						
						//$baseImponible += doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
						$arrayIdIvasTemparioPaquete = explode(",",$valFormPaq['hddIdIvasTemparioPaquete'.$valorPaq]);
						$arrayPorcentajesTemparioPaquete = explode(",",$valFormPaq['hddPorcentajesIvasTemparioPaquete'.$valorPaq]);
						if($valFormPaq['hddIdIvasTemparioPaquete'.$valorPaq] == ""){//sino tiene iva va a exentos
							$totalExento += doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
						}
						
						foreach($arrayIdIvasTemparioPaquete as $key => $idIva){//con ivas
							$subTotalTempPaquete = doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
							$descuentoTempPaquete = ($valFormTotalDcto['txtDescuento']*$subTotalTempPaquete)/100;
							$subTotalTempPaquete = $subTotalTempPaquete - $descuentoTempPaquete;
							$subTotalIvaTempPaquete = ($subTotalTempPaquete*$arrayPorcentajesTemparioPaquete[$key])/100;
							
							$arrayIvasBaseImponible[$idIva] += $subTotalTempPaquete;
							$arrayIvasSubTotal[$idIva] += $subTotalIvaTempPaquete;
						}
						
						//$baseImponible += doubleval($valFormPaq['hddTotalConIvaRptoPqte'.$valorPaq]);//anterior directo
						
						//Nuevo por ivas
						$arrayIdIvasRepuestoPaquete = explode(",",$valFormPaq['hddIdIvasRepuestoPaquete'.$valorPaq]);
						$arrayIvasRepuestoPaquete = explode(",",$valFormPaq['hddIvasRepuestoPaquete'.$valorPaq]);//base imponible por iva
						$arrayPorcentajesRepuestoPaquete = explode(",",$valFormPaq['hddPorcentajesIvasRepuestoPaquete'.$valorPaq]);

						foreach($arrayIdIvasRepuestoPaquete as $key => $idIva){
							$subTotalArtPaquete = $arrayIvasRepuestoPaquete[$key];
							$descuentoArtPaquete = ($valFormTotalDcto['txtDescuento']*$subTotalArtPaquete)/100;
							$subTotalArtPaquete = $subTotalArtPaquete - $descuentoArtPaquete;
							$subTotalIvaArtPaquete = ($subTotalArtPaquete*$arrayPorcentajesRepuestoPaquete[$key])/100;//rollo devuelve 4 decimales y hace la orden 1 decimal de mas							

							$arrayIvasBaseImponible[$idIva] += $subTotalArtPaquete;
							$arrayIvasSubTotal[$idIva] += $subTotalIvaArtPaquete;
						}
					}	
				}
			}
		}
	}	
	
	//TOT
	if (isset($arrayObjTot)) {
		foreach($arrayObjTot as $indiceTot => $valorTot) {
			$objResponse->assign(sprintf("hddValorCheckAprobTot%s",$valorTot),"value", 0);
			
			if (isset($valFormTot['cbxItmTotAprob'])) {
				foreach($valFormTot['cbxItmTotAprob'] as $indiceAprob => $valorAprob) {
					if($valorTot == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobTot%s",$valorTot),"value",1);
						
						if ($valFormTot['hddIdIvaTot'.$valorTot] == 0 && $valFormTot['hddIvaTot'.$valorTot] == "") {//SIN IVA EXENTO
							$subTotalTot = $valFormTot['hddMontoTotalTot'.$valorTot];//$valForm['hddCantArt'.$valor]*
							$totalExento += doubleval($subTotalTot);
						}else{//CON IVA CALCULAR
							$arrayIdIvasTot = explode(",",$valFormTot['hddIdIvaTot'.$valorTot]);
							$arrayIvasTot = explode(",",$valFormTot['hddIvaTot'.$valorTot]);
							
							foreach($arrayIdIvasTot as $key => $idIva){
								$subTotalTot = $valFormTot['hddMontoTotalTot'.$valorTot];
								$descuentoTot = ($valFormTotalDcto['txtDescuento']*$subTotalTot)/100;
								$subTotalTot = $subTotalTot - $descuentoTot;
								$subTotalIvaTot = ($subTotalTot*$arrayIvasTot[$key])/100;//otro rollo decimales
								
								$arrayIvasBaseImponible[$idIva] += $subTotalTot;
								$arrayIvasSubTotal[$idIva] += $subTotalIvaTot;
							}
						}
						
						$subTotal += doubleval($valFormTot['hddMontoTotalTot'.$valorTot]);
					}
				}
			}
		}
	}
                
	//NOTAS
	if (isset($arrayObjNota)) {
		foreach($arrayObjNota as $indiceNota => $valorNota) {
		
			//SE INICIALIZAN EN 0 Y DESPUES SE LES COLOCA EL CHECK
			$objResponse->assign(sprintf("hddValorCheckAprobNota%s",$valorNota),"value", 0);
			if (isset($valFormNotas['cbxItmNotaAprob'])) {
				foreach($valFormNotas['cbxItmNotaAprob'] as $indiceAprob => $valorAprob) {
					if($valorNota == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobNota%s",$valorNota),"value", 1);
						
						if ($valFormNotas['hddIdIvaNota'.$valorNota] == 0 && $valFormNotas['hddIvaNota'.$valorNota] == "") {//SIN IVA EXENTO
							$subTotalNota = ($valFormNotas['hddPrecNota'.$valorNota]);
							$totalExento += doubleval($subTotalNota);
						}else{//CON IVA CALCULAR
							$arrayIdIvasNota = explode(",",$valFormNotas['hddIdIvaNota'.$valorNota]);
							$arrayIvasNota = explode(",",$valFormNotas['hddIvaNota'.$valorNota]);
							
							foreach($arrayIdIvasNota as $key => $idIva){
								$subTotalNota = $valFormNotas['hddPrecNota'.$valorNota];
								$descuentoNota = ($valFormTotalDcto['txtDescuento']*$subTotalNota)/100;
								$subTotalNota = $subTotalNota - $descuentoNota;
								$subTotalIvaNota = ($subTotalNota*$arrayIvasNota[$key])/100;//otro rollo decimales
								
								$arrayIvasBaseImponible[$idIva] += $subTotalNota;
								$arrayIvasSubTotal[$idIva] += $subTotalIvaNota;
							}
						}
						
						$subTotal += doubleval($valFormNotas['hddPrecNota'.$valorNota]);
					}
				}
			}
		}
	}	
        
        //TEMPARIOS MANOS DE OBRA
	if (isset($arrayObjTemp)) {
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
			$objResponse->assign(sprintf("hddValorCheckAprobTemp%s",$valorTemp),"value", 0);

			if (isset($valFormTemp['cbxItmTempAprob'])) {
				foreach($valFormTemp['cbxItmTempAprob'] as $indiceAprob => $valorAprob) {
					if($valorTemp == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobTemp%s",$valorTemp),"value", 1);
						
						if ($valFormTemp['hddIdIvaTemp'.$valorTemp] == 0 && $valFormTemp['hddIvaTemp'.$valorTemp] == "") {//SIN IVA EXENTO
							$subTotalTemp = $valFormTemp['hddPrecTemp'.$valorTemp];
							$totalExento += doubleval($subTotalTemp);
						}else{//CON IVA CALCULAR
							$arrayIdIvasTempario = explode(",",$valFormTemp['hddIdIvaTemp'.$valorTemp]);
							$arrayIvasTempario = explode(",",$valFormTemp['hddIvaTemp'.$valorTemp]);
							
							foreach($arrayIdIvasTempario as $key => $idIva){								
								$subTotalTemp = $valFormTemp['hddPrecTemp'.$valorTemp];
								$descuentoTemp = ($valFormTotalDcto['txtDescuento']*$subTotalTemp)/100;
								$subTotalTemp = $subTotalTemp - $descuentoTemp;
								$subTotalIvaTemp = ($subTotalTemp*$arrayIvasTempario[$key])/100;//otro rollo decimales
								
								$arrayIvasBaseImponible[$idIva] += $subTotalTemp;
								$arrayIvasSubTotal[$idIva] += $subTotalIvaTemp;
							}
						}
						
						$subTotal += doubleval($valFormTemp['hddPrecTemp'.$valorTemp]);
					}
				}
			}
		}
	}	
		
	//REPUESTOS
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
		$objResponse->assign(sprintf("hddValorCheckAprobRpto%s", $valor),"value", 0);
				
			if (isset($valForm['cbxItmAprob'])) {	
				foreach($valForm['cbxItmAprob'] as $indiceAprob => $valorAprob) {
					if($valor == $valorAprob) {//solo calcular los parobados
						$objResponse->assign(sprintf("hddValorCheckAprobRpto%s", $valor),"value", 1);
												
						$totalExento += doubleval($valForm['hddCantArt'.$valor]*$valForm['hddMontoPmu'.$valor]);//PMU SIEMPRE A EXENTO
						$subTotal += doubleval($valForm['hddCantArt'.$valor]*$valForm['hddMontoPmu'.$valor]);//PMU A SUBTOTAL
						
						if ($valForm['hddIdIvaArt'.$valor] == 0 && $valForm['hddIvaArt'.$valor] == "") {//SIN IVA EXENTO
							$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
							$totalExento += doubleval($subTotalArt);
							
							//$montoExento = $montoExento + $valForm['hddPrecioArt'.$valor];
							//$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
							//$subTotalArt = $subTotalArt - $descuentoArt;
						} else {//CON IVA CALCULAR
											
							$arrayIdIvasRepuesto = explode(",",$valForm['hddIdIvaArt'.$valor]);
							$arrayIvasRepuesto = explode(",",$valForm['hddIvaArt'.$valor]);
							
							foreach($arrayIdIvasRepuesto as $key => $idIva){								
								$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
								$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
								$subTotalArt = $subTotalArt - $descuentoArt;
								$subTotalIvaArt = ($subTotalArt*$arrayIvasRepuesto[$key])/100;//otro rollo decimales
								
								$arrayIvasBaseImponible[$idIva] += $subTotalArt;
								$arrayIvasSubTotal[$idIva] += $subTotalIvaArt;
							}
						}
						
						$subTotal += doubleval($valForm['hddTotalArt'.$valor]);
						//$totalArtTotal += doubleval($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);											
					}
				}
			}
		}
	}	
	
	//NO SE USA
	if (isset($arrayObjDcto)) {
		foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
			
			if($valFormTotalDcto['hddIdDcto'.$valorDcto] != "")
			{
				if($valFormTotalDcto['hddIdDcto'.$valorDcto] == 1)//DCTO MANO OBRA
					$descuentoAdicional = ($valFormTotalDcto['hddPorcDcto'.$valorDcto] * $totalTempTotal)/100;
				else 
					if($valFormTotalDcto['hddIdDcto'.$valorDcto] == 2)// DCTO REPUESTO
						$descuentoAdicional = ($valFormTotalDcto['hddPorcDcto'.$valorDcto] * $totalArtTotal)/100;
						
				$objResponse->assign(sprintf("txtTotalDctoAdcl%s", $valorDcto),"value", number_format($descuentoAdicional,2,".",","));
			}
			$totalDescuentoAdicional += doubleval($descuentoAdicional);
		}
	}
		
	//SIEMPRE TOMAR EL DEL DOCUMENTO        
	$iva_venta = count($arrayIvasOrden);
	
	$subTotalDescuento = ($subTotal * ($valFormTotalDcto['txtDescuento']/100));

	//contiene las bases imponibles de todos los items exceptuando repuestos que se calculan a parte
	$baseImponible -= $baseImponible*($valFormTotalDcto['txtDescuento']/100);//segun impresora fiscal
	
	if($iva_venta != 0 && $iva_venta != ""){//si tiene iva tomar repuestos con descuento
		$totalExento -= $totalExento*($valFormTotalDcto['txtDescuento']/100);//segun impresora fiscal
	}else{//sino tiene iva todo se va a la base imponible
	//anterior
//            $baseImponible += $totalExento;
//            $totalExento = 0;
		//ahora
		$totalExento +=$baseImponible;
		$totalExento -= $totalExento*($valFormTotalDcto['txtDescuento']/100);//segun impresora 2
		$baseImponible = 0;
	}
		   
	//resumando los items restantes, a los repuestos de las bases imponibles
	foreach($arrayIvasBaseImponible as $keyIdIva => $baseImponibleIvas){
		$arrayIvasBaseImponible[$keyIdIva] += $baseImponible;
		$arrayIvasSubTotal[$keyIdIva] += ($baseImponible*$valFormTotalDcto["txtIvaVenta".$keyIdIva])/100;            
	}
	
	$recalculoIvas = array();

	foreach($arrayIvasBaseImponible as $keyIdIva => $baseImponibleIvas){
		$recalculoIvas[$keyIdIva] += round(($baseImponibleIvas*$valFormTotalDcto["txtIvaVenta".$keyIdIva])/100,2);            
	}
	
	$totalIva = array_sum($recalculoIvas); //redondeo subtotal descuento sino da 1 decimal de mas
	$totalPresupuesto = doubleval($subTotal) - doubleval(round($subTotalDescuento,2)) - doubleval($totalDescuentoAdicional) + doubleval($gastosConIva) + doubleval($subTotalIva) +  doubleval($gastosSinIva) + doubleval($totalIva);
	
	function totalesOrden($total){
		//return round($total,2);
		return number_format(round($total,2),2,".",",");
	}
	
	$objResponse->assign("txtSubTotal","value",totalesOrden($subTotal));
	$objResponse->assign("txtSubTotalDescuento","value",totalesOrden($subTotalDescuento));
	$objResponse->assign("txtTotalOrden","value",totalesOrden($totalPresupuesto));	
	$objResponse->assign('txtGastosConIva',"value",totalesOrden($gastosConIva));
	$objResponse->assign('txtMontoExento',"value",totalesOrden($totalExento));	
        
	$objResponse->assign("txtTotalFactura","value",totalesOrden($totalPresupuesto));
	$objResponse->assign("txtMontoPorPagar","value",totalesOrden($totalPresupuesto));
	
	foreach($arrayIvasBaseImponible as $keyIdIva => $baseImponibleIvas){
		$objResponse->assign('txtBaseImponibleIva'.$keyIdIva,"value",totalesOrden($baseImponibleIvas));
		$objResponse->assign('txtTotalIva'.$keyIdIva,"value",totalesOrden($recalculoIvas[$keyIdIva]));            
		
		$objResponse->assign('txtBaseImponible',"value",totalesOrden($baseImponibleIvas));//solo rrellenar, usado en vzla simple iva
	}
        
	$cadena = "";
	foreach($arrayObj as $indice => $valor) {
		if (isset($valForm['hddIdArt'.$valor]))
			$cadena .= "|".$valor;
	}
	$objResponse->assign("hddObj","value",$cadena);

	$cadenaPaq = "";
	foreach($arrayObjPaq as $indicePaq => $valorPaq) {
		if (isset($valFormPaq['hddIdPaq'.$valorPaq]))
			$cadenaPaq .= "|".$valorPaq;
	}
	$objResponse->assign("hddObjPaquete","value",$cadenaPaq);
	
	$cadenaTemp = "";
	foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
		if (isset($valFormTemp['hddIdTemp'.$valorTemp]))
			$cadenaTemp .= "|".$valorTemp;
	}
	$objResponse->assign("hddObjTempario","value",$cadenaTemp);
	
	$cadenaNota = "";
	foreach($arrayObjNota as $indiceNota => $valorNota) {
		if (isset($valFormNotas['hddIdNota'.$valorNota]))
			$cadenaNota .= "|".$valorNota;
	}
	$objResponse->assign("hddObjNota","value",$cadenaNota);
	
	$cadenaDcto = "";
	foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
		if (isset($valFormTotalDcto['hddIdDcto'.$valorDcto]))
			$cadenaDcto .= "|".$valorDcto;
	}
	$objResponse->assign("hddObjDescuento","value", $cadenaDcto);
	
	$objResponse->script("xajax_contarItemsDcto(xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frm_agregar_paq'));");
	
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

//SE EJECUTA 2 VECES, NO CUANDO ES NUEVO, LLAMA CALCULARDCTO DE ARRIBA OTRA VEZ
function calcularTotalDcto(){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'),xajax.getFormValues('frm_agregar_paq'),xajax.getFormValues('frmListaManoObra'),xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'));");
		
	return $objResponse;
}

//ES UN LIST, CARGA EN EL DOCUMENTO, NO LO MUESTRA, LO USA GUARDAR FACTURA Y DEVOLVER VALE DE SALIDA
function cargaLstClaveMovimiento($idTipoClave, $selId = ""){
	$objResponse = new xajaxResponse();
	
	if ($selId != "") {
		
		$queryClaveMto = sprintf("SELECT 
			sa_tipo_orden.id_clave_movimiento,
			sa_tipo_orden.id_clave_movimiento_dev
		FROM sa_tipo_orden
		WHERE sa_tipo_orden.id_tipo_orden = %s", $selId);
		$rsClaveMto = mysql_query($queryClaveMto);
		$rowClaveMto = mysql_fetch_assoc($rsClaveMto); 
		
		if($idTipoClave == "ORDEN"){
			$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s ORDER BY descripcion",
			valTpDato($rowClaveMto['id_clave_movimiento_dev'],"int"));
		}else{
			$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s ORDER BY descripcion",
			valTpDato($rowClaveMto['id_clave_movimiento'],"int"));
		}
	} else {
		$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE tipo = %s AND id_modulo = 0 ORDER BY descripcion",
			valTpDato($idTipoClave,"int"));
	}
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert("Error cargaLstClaveMovimiento \n".mysql_error()."\n Linea: ".__LINE__);
	
	$html = "<select id=\"lstClaveMovimiento\" name=\"lstClaveMovimiento\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['tipo']) ? "selected='selected'" : "";
		if($idTipoClave == "ORDEN"){//dev
			$html .= "<option ".$selected." value=\"".$row['id_clave_movimiento']."\">".utf8_encode($row['descripcion'])."</option>";
		}else{
			$html .= "<option ".$selected." value=\"".$row['id_clave_movimiento']."\">".utf8_encode($row['descripcion'])."</option>";
		}
	}
	$html .= "</select>";
	
	$objResponse->script("byId('lstTipoClave').disabled = true;");
	$objResponse->assign("tdlstClaveMovimiento","innerHTML",$html);
	
	if($idTipoClave == "ORDEN"){
		$objResponse->script("xajax_buscarNumeroControl(byId('txtIdEmpresa').value, ".$rowClaveMto['id_clave_movimiento_dev'].", 'txtNroControl');");
	}else{
		$objResponse->script("xajax_buscarNumeroControl(byId('txtIdEmpresa').value, ".$rowClaveMto['id_clave_movimiento'].", 'txtNroControl');");
	}
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


//ES EL LISTADO DE TIPOS DE ORDEN, MODIFICADO PARA QUE TRAIGA SIEMPRE EL DE LA ORDEN
function cargaLstTipoOrden($selId = ""){
	$objResponse = new xajaxResponse();
	
	$html = "<select id=\"lstTipoOrden\" name=\"lstTipoOrden\" class=\"divMsjInfo2\">";
	
	if($selId != "" || $selId != NULL || $selId != "-1"){
		$sql = sprintf("SELECT id_tipo_orden, nombre_tipo_orden FROM sa_tipo_orden WHERE id_tipo_orden = %s LIMIT 1",
									valTpDato($selId,"int"));
		$query = mysql_query($sql);
		if(! $query) { return $objResponse->alert("Error listado tipo de orden: \n".mysql_error()."\n Sql: ".$sql."\n Linea: ".__LINE__); }
		
		$row = mysql_fetch_assoc($query);
		
		$html .= sprintf("<option selected=\"selected\" value=\"%s\">%s</option>",
					$row["id_tipo_orden"],
					utf8_encode($row["nombre_tipo_orden"]));
	}
		
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoOrden","innerHTML",$html);
	
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



//CUENTA ITEMS AGREGADOS AL DOCUMENTO
function contarItemsDcto($valFormTotalDcto, $valFormPaq){
	$objResponse = new xajaxResponse();
	
	$cont = 0;
	
	$arrayObjPaq = explode("|",$valFormTotalDcto['hddObjPaquete']);
	
	if (isset($arrayObjPaq)) {
		foreach($arrayObjPaq as $indice => $valor){
			if ($valor > 0 && $valor != "") {
				$array = explode("|",$valFormPaq['hddTempPaqAsig'.$valor]);
				$arrayTemparioPaq = NULL;
				if (isset($array)) {
					foreach ($array as $indice2 => $valor2) {
						if ($valor2 > 0 && $valor2 != "")
							$arrayTemparioPaq[] = $valor2;
					}
				}
				
				$array = explode("|",$valFormPaq['hddRepPaqAsig'.$valor]);
				$arrayRepuestosPaq = NULL;
				if (isset($array)) {
					foreach ($array as $indice2 => $valor2) {
						if ($valor2 > 0 && $valor2 != "")
							$arrayRepuestosPaq[] = $valor2;
					}
				}
				
				$arrayDetPaq[0] = $arrayTemparioPaq;
				$arrayDetPaq[1] = $arrayRepuestosPaq;
				
				$arrayPaquete[] = $arrayDetPaq;
				
				$cont += intval(count($arrayTemparioPaq));
				$cont += intval(count($arrayRepuestosPaq));
			}
		}
	}
	
	$arrayObj = explode("|", $valFormTotalDcto['hddObj']);
	
	if (isset($arrayObj)) {
		$arrayRepuestos = NULL;
		foreach($arrayObj as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayRepuestos[] = $valor;
		}
		$cont += intval(count($arrayRepuestos));
	}
	
	$arrayObjTemp = explode("|", $valFormTotalDcto['hddObjTempario']);
	
	if (isset($arrayObjTemp)) {
		$arrayTempario = NULL;
		foreach($arrayObjTemp as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayTempario[] = $valor;
		}
		$cont += intval(count($arrayTempario));
	}
	
	$arrayObjTot = explode("|", $valFormTotalDcto['hddObjTot']);
	
	if (isset($arrayObjTot)) {
		$arrayTot = NULL;
		foreach($arrayObjTot as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayTot[] = $valor;
		}
		$cont += intval(count($arrayTot));
	}
	
	$arrayObjNota = explode("|", $valFormTotalDcto['hddObjNota']);
	
	if (isset($arrayObjNota)) {
		$arrayNota = NULL;
		foreach($arrayObjNota as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayNota[] = $valor;
		}
		$cont += intval(count($arrayNota));
	}
	$objResponse->assign("hddItemsCargados","value", $cont);
			
	return $objResponse;
}

//DEVUELVE LA FACTURA Y CREA NOTA DE CREDITO - FACTURACION
function devolverFacturaVenta($valForm, $valFormTotalDcto){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cjrs_factura_venta_list","insertar")) return $objResponse;
	
		
	$queryVerif = sprintf("SELECT * FROM cj_cc_notacredito
		WHERE id_orden = %s LIMIT 1",
	$valForm['txtIdPresupuesto']);
	$rsVerif = mysql_query($queryVerif);
	if (!$rsVerif) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	if(mysql_num_rows($rsVerif) != 0){
		return $objResponse->alert('Esta factura ya ha sido devuelta.');
	}
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DE LA FACTURA QUE ESTA SIENDO DEVUELTA
	$queryFact = sprintf("SELECT cj_cc_encabezadofactura.*, id_tipo_orden FROM cj_cc_encabezadofactura
						LEFT JOIN sa_orden ON cj_cc_encabezadofactura.numeroPedido = sa_orden.id_orden
	WHERE idFactura = %s LIMIT 1",
		$_GET['idfct']);
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idFactura = $rowFact['idFactura'];
	$idOrden = $rowFact['numeroPedido'];
	$tipoPago = $rowFact['condicionDePago'];
	$idTipoOrden = $rowFact['id_tipo_orden'];//gregor
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
		// BUSCA LA CLAVE DE MOVIMIENTO DE LA DEVOLUCION SEGUN ES TIPO DE ORDEN
	$queryClaveMov = sprintf("SELECT
		tipo_orden.id_clave_movimiento_dev,
		clave_mov.tipo
	FROM sa_tipo_orden tipo_orden
		INNER JOIN pg_clave_movimiento clave_mov ON (tipo_orden.id_clave_movimiento_dev = clave_mov.id_clave_movimiento)
	WHERE id_tipo_orden = %s;",
		valTpDato($idTipoOrden, "int"));//antes $valForm['lstTipoOrden'] a veces no se envia sino tiene el permiso del tipo de orden.
	$rsClaveMov = mysql_query($queryClaveMov);
	if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsClaveMov);
	$rowClaveMov= mysql_fetch_assoc($rsClaveMov);
	
	$idClaveMovimiento = $rowClaveMov['id_clave_movimiento_dev'];
	$idTipoMovimiento = $rowClaveMov['tipo'];
	
	//EL TOTAL PAGADO DE LA FACT = EL SALDO NC
	$saldoNotaCredito = $rowFact['montoTotalFactura'] - $rowFact['saldoFactura'];
	
	//$subtotalNotaCredito = str_replace(",","", $rowFact['montoTotalFactura']) - (str_replace(",","", $rowFact['calculoIvaFactura']) + str_replace(",","", $rowFact['calculoIvaDeLujoFactura']));
	
	if ($saldoNotaCredito == 0) {
		$estadoNotaCredito = 3;
	} else if($saldoNotaCredito > 0) {
		$estadoNotaCredito = 2;
	}
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT * 
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC	LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$numeroActualNota = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado la numeracion de notas de credito"); }
	
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$insertSQLNc = sprintf("INSERT INTO cj_cc_notacredito (numeracion_nota_credito, idCliente, montoNetoNotaCredito, saldoNotaCredito, fechaNotaCredito, id_clave_movimiento, observacionesNotaCredito, estadoNotaCredito, idDocumento, tipoDocumento, porcentajeIvaNotaCredito, ivaNotaCredito, subtotalNotaCredito, porcentaje_descuento, subtotal_descuento, ivaLujoNotaCredito, idDepartamentoNotaCredito, montoExoneradoCredito, montoExentoCredito, aplicaLibros, baseimponibleNotaCredito, numeroControl, id_empresa, id_orden)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($numeroActualNota, "text"),
		valTpDato($rowFact['idCliente'], "int"),
		valTpDato($rowFact['montoTotalFactura'], "real_inglesa"), 
		valTpDato($saldoNotaCredito, "real_inglesa"), 
		valTpDato(date("Y-m-d"), "date"),
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($valFormTotalDcto['txtMotivoRetrabajo'], "text"),
		valTpDato($estadoNotaCredito, "int"),
		valTpDato($idFactura, "int"),
		valTpDato("FA", "text"),
		valTpDato($rowFact['porcentajeIvaFactura'], "real_inglesa"),
		valTpDato($rowFact['calculoIvaFactura'], "real_inglesa"),
		valTpDato($rowFact['subtotalFactura'], "real_inglesa"),
		valTpDato($rowFact['porcentaje_descuento'], "real_inglesa"),
		valTpDato($rowFact['descuentoFactura'], "real_inglesa"),
		valTpDato($rowFact['calculoIvaDeLujoFactura'], "real_inglesa"),
		valTpDato(1, "int"), // 1 = servicios
		valTpDato($rowFact['montoExonerado'], "real_inglesa"),
		valTpDato($rowFact['montoExento'], "real_inglesa"),
		valTpDato(1, "int"),// 1 = aplica a libros
		valTpDato($rowFact['baseImponible'], "real_inglesa"),
		valTpDato($valForm['txtNroControl'], "text"),
		valTpDato($idEmpresa, "int"),
		valTpDato($valForm['txtIdPresupuesto'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$rsInsertarNc = mysql_query($insertSQLNc);
	if (!$rsInsertarNc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$insertSQLNc);
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	//INSERTA IVAS DE LA FACTURA A NOTA DE CREDITO
	$ivasSql = sprintf("INSERT INTO cj_cc_nota_credito_iva (id_nota_credito, base_imponible, subtotal_iva, id_iva, iva, lujo)
						SELECT %s, base_imponible, subtotal_iva, id_iva, iva, lujo
						FROM cj_cc_factura_iva
						WHERE id_factura = %s",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idFactura, "int"));
	$rsIvasSql = mysql_query($ivasSql);
	if (!$rsIvasSql) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$ivasSql); }
        
	if($rowFact['saldoFactura'] > 0){
		
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
		if (!$rsAperturaCaja) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$queryAperturaCaja); }
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
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$queryNumeracion); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$numeroActualPago = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		if($rowNumeracion['numero_actual'] == ""){ return $objResponse->alert("No se ha configurado numeracion de comprobantes de pago"); }
                
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$updateSQL); }
		
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
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$insertSQL); }
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
		VALUES (%s, %s)",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$insertSQL); }
		$idEncabezadoPago = mysql_insert_id();
		
		//INSERTA EL PAGO
		$insertSQL = sprintf("INSERT INTO sa_iv_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, montoPagado, numeroFactura, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, id_encabezado_rs)
			VALUES (%s, %s, 8, %s, 1, 1, %s, %s, 1, 0, 2, 0, %s);",
							$idFactura,
							valTpDato($fechaRegistroPago, "date"),
							$idNotaCredito,
							valTpDato($rowFact['saldoFactura'], "real_inglesa"),
							$valForm['txtNroFacturaVentaServ'],
							valTpDato($idEncabezadoPago, "int"));
		$rsInsertSql = mysql_query($insertSQL);
		if (!$rsInsertSql) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);	
		
		$idPago = mysql_insert_id();
		
		// INSERTA EL DETALLE DEL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
		VALUES (%s, %s)",
			valTpDato($idEncabezadoReciboPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$insertSQL); }
	}
	
	// BUSCA LOS REPUESTOS FACTURADOS DE LA ORDEN PARA DEVOLVERLOS
	$queryArt = sprintf("SELECT 
		sa_det_solicitud_repuestos.id_det_orden_articulo,
		COUNT(sa_det_solicitud_repuestos.id_det_orden_articulo) AS nro_rpto_desp,
		sa_det_solicitud_repuestos.id_casilla,
		sa_det_orden_articulo.id_articulo,
		sa_det_orden_articulo.costo,
		sa_det_orden_articulo.id_articulo_costo,
		sa_det_orden_articulo.id_articulo_almacen_costo
	FROM sa_solicitud_repuestos
		INNER JOIN sa_det_solicitud_repuestos ON (sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud)
		INNER JOIN sa_orden ON (sa_solicitud_repuestos.id_orden = sa_orden.id_orden)
		INNER JOIN sa_det_orden_articulo ON (sa_det_solicitud_repuestos.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)
	WHERE sa_solicitud_repuestos.estado_solicitud = 5
		AND sa_det_solicitud_repuestos.id_estado_solicitud = 5
		AND sa_orden.id_empresa = %s
		AND sa_orden.id_orden = %s
	GROUP BY sa_det_solicitud_repuestos.id_det_orden_articulo,
		sa_det_solicitud_repuestos.id_casilla",
		valTpDato($idEmpresa, "int"),
		valTpDato($idOrden, "int"));
	$rsArt = mysql_query($queryArt);
	if (!$rsArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryArt);
	$contArt = mysql_num_rows($rsArt);
	
		// INSERTA EL MOVIMIENTO
	if ($contArt > 0) {
		$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento,id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
		VALUE (%s, %s, %s, %s, NOW(), %s, %s, NOW(), %s, %s)",
			valTpDato($idTipoMovimiento, "int"), //gregor
			valTpDato($idClaveMovimiento, "int"),
			valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
			valTpDato($idNotaCredito, "int"),
			valTpDato($rowFact['idCliente'], "int"), //id cliente proveedor
			valTpDato(0, "boolean"), // tipo costo
			valTpDato($_SESSION['idUsuarioSysGts'], "int"),
			valTpDato($tipoPago, "boolean"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$idMovimiento = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	while ($rowArt = mysql_fetch_array($rsArt)) {
		$idArticulo = $rowArt['id_articulo'];
		
		if ($rowArt['nro_rpto_desp'] > 0) {
			$idCasilla = $rowArt['id_casilla'];
			
			$contArt++;

			// VERIFICA EL ESTATUS DE LA CASILLA CON LA CUAL SE HABIA DESPACHADO EL ARTICULO
			$estatusCasillaSQL = sprintf("SELECT estatus FROM iv_articulos_almacen
			WHERE id_articulo = %s
				AND id_casilla = %s",
				$idArticulo,
				$idCasilla);
			$rsEstatusCasillaSQL = mysql_query($estatusCasillaSQL);
			if (!$rsEstatusCasillaSQL) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$estatusCasillaSQL);
			$rowEstatusCasillaSQL = mysql_fetch_assoc($rsEstatusCasillaSQL);
			if ($rowEstatusCasillaSQL['estatus']) {
				$idCasilla = $rowArt['id_casilla'];
			} else {
				$nuevaCasillaSQL = sprintf("SELECT id_casilla_predeterminada FROM iv_articulos_empresa
				WHERE id_articulo = %s
					AND id_empresa = %s",
					$idArticulo,
					$idEmpresa);
				$rsNuevaCasillaSQL = mysql_query($nuevaCasillaSQL);
				if (!$rsNuevaCasillaSQL) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$nuevaCasillaSQL);
				$rowNuevaCasillaSQL = mysql_fetch_assoc($rsNuevaCasillaSQL);
				
				$idCasilla = $rowNuevaCasillaSQL['id_casilla_predeterminada'];
			}
			
			if ($idCasilla == NULL || $idCasilla == ""){
				$articuloSql = sprintf("SELECT
					codigo_articulo,
					descripcion
				FROM iv_articulos
				WHERE id_articulo = %s",
					$idArticulo);
				$rsArticuloSql = mysql_query($articuloSql);
				if (!$rsArticuloSql) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$articuloSql);
				$rowArticuloSql = mysql_fetch_assoc($rsArticuloSql);
				
				return $objResponse->alert("No se puede realizar la Nota de Credito debido a que Existen Repuestos Sin Ubicacion:\n\tCodigo: ".$rowArticuloSql['codigo_articulo']."\n\tDescripcion: ".$rowArticuloSql['descripcion']);
			} else {
				
				$sqlIndividual = "";
				if($rowArt['id_articulo_costo'] > 0){
					$sqlIndividual = sprintf(" AND fact_vent_det.id_articulo_costo = %s 
									AND fact_vent_det.id_articulo_almacen_costo = %s ",
									$rowArt['id_articulo_costo'],
									$rowArt['id_articulo_almacen_costo']);
				}
				
				// busco costo precio con el que salio el repuesto, se usa en el movimiento detalle y iv_kardex
				$queryFactDet = sprintf("SELECT  
					fact_vent_det.id_articulo,
					fact_vent_det.cantidad,
					fact_vent_det.precio_unitario,
					fact_vent_det.pmu_unitario,
					fact_vent_det.id_articulo_costo,
					fact_vent_det.id_articulo_almacen_costo,
					fact_vent_det.costo_compra,
					(SELECT valor FROM pg_configuracion_empresa config_emp
						INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
						WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = fact_vent.id_empresa) as modo_costo,
						
					(SELECT costo_promedio FROM iv_articulos_costos 
							WHERE id_articulo = fact_vent_det.id_articulo AND id_empresa = %s ORDER BY id_articulo_costo DESC LIMIT 1) AS costo_promedio
						
				FROM cj_cc_encabezadofactura fact_vent
					INNER JOIN cj_cc_factura_detalle fact_vent_det ON (fact_vent.idFactura = fact_vent_det.id_factura)
				WHERE fact_vent.idFactura = %s 
				AND fact_vent_det.id_articulo = %s %s LIMIT 1",
					valTpDato($idEmpresa, "int"),
					valTpDato($idFactura, "int"),
					valTpDato($idArticulo, "int"),
					$sqlIndividual);//COSTO INDIVIDUAL, ARTICULO AGRUPA
				$rsFactDet = mysql_query($queryFactDet);
				if (!$rsFactDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowFactDet = mysql_fetch_assoc($rsFactDet);
				
				$queryCompruebaLote = "SHOW TABLES LIKE 'vw_iv_articulos_almacen_costo'";
				$rsCompruebaLote = mysql_query($queryCompruebaLote);
				if (!$rsCompruebaLote){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				
				$compruebaLote = mysql_num_rows($rsCompruebaLote);
				
				if($compruebaLote > 0){//USA LOTES
					//buscar id costo id almacen actual
					if($rowFactDet["modo_costo"] == 1 || $rowFactDet["modo_costo"] == 2 || !($rowFactDet['id_articulo_almacen_costo'] > 0)){// 1 = Reposiciï¿½n, 2 = Promedio, 3 = FIFO

						$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
								WHERE vw_iv_art_almacen_costo.id_articulo = %s
										AND vw_iv_art_almacen_costo.id_casilla = %s
										AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
								ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;",
										valTpDato($idArticulo, "int"),
										valTpDato($idCasilla, "int"));
						$rsArtCosto = mysql_query($queryArtCosto);
						if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
						$rowArtCosto = mysql_fetch_assoc($rsArtCosto);

						$idArticuloAlmacenCosto = $rowArtCosto['id_articulo_almacen_costo'];
						$idArticuloCosto = $rowArtCosto['id_articulo_costo'];                        

					}else{//si es fifo 3 se mantiene
						$idArticuloAlmacenCosto = $rowFactDet['id_articulo_almacen_costo'];
						$idArticuloCosto = $rowFactDet['id_articulo_costo'];       
					}
					
					if($idArticuloAlmacenCosto == "" || $idArticuloCosto == ""){
						return $objResponse->alert("No se encontro lote activo, id art: ".$idArticulo." id Casilla: ".$idCasilla."\n\n Id alm cos: ".$idArticuloAlmacenCosto." id art cos: ".$idArticuloCosto."\n\n Sql:".$queryArtCosto);
					}
				
					$queryCostoActual = sprintf("SELECT costo, costo_promedio FROM vw_iv_articulos_almacen_costo 
										WHERE id_articulo_almacen_costo = %s AND id_articulo_costo = %s LIMIT 1",
										$idArticuloAlmacenCosto, 
										$idArticuloCosto);
		
					$rsCostoActual = mysql_query($queryCostoActual);
					if (!$rsCostoActual) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryCostoActual); }
					$rowCostoActual = mysql_fetch_assoc($rsCostoActual);
                                
                                
					if($rowFactDet["modo_costo"] == 1 || $rowFactDet["modo_costo"] == 3){
						$costoPromedioCompra = $rowCostoActual["costo"];
					}else{
						$costoPromedioCompra = $rowCostoActual["costo_promedio"];
					}
				
				}else{//NO USA LOTES
					if($rowFactDet["modo_costo"] == 1){
						$costoPromedioCompra = $rowFactDet["costo_compra"];
					}else{
						$costoPromedioCompra = $rowFactDet["costo_promedio"];
					}					
				}
				
				if($compruebaLote > 0){//USA LOTES
					// VERIFICA SI EL LOTE TIENE LA UBICACION ASIGNADA
					$queryArtAlmCosto = sprintf("SELECT *
					FROM iv_articulos_almacen art_almacen
						INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
					WHERE art_almacen.id_articulo = %s
						AND art_almacen.id_casilla = %s
						AND art_almacen_costo.id_articulo_costo = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($idArticuloCosto, "int"));
					$rsArtAlmCosto = mysql_query($queryArtAlmCosto);
					if (!$rsArtAlmCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtAlm = mysql_num_rows($rsArtAlmCosto);
					$rowArtAlmCosto = mysql_fetch_assoc($rsArtAlmCosto);
					
					$hddIdArticuloAlmacenCosto = $rowArtAlmCosto['id_articulo_almacen_costo'];
				
					if ($totalRowsArtAlm > 0) {
						// ACTUALIZA EL ESTATUS DE LA UBICACION DEL LOTE
						$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo SET
							estatus = 1
						WHERE id_articulo_almacen_costo = %s;",
							valTpDato($hddIdArticuloAlmacenCosto, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					} else {
						// LE ASIGNA EL LOTE A LA UBICACION
						$insertSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, estatus)
						SELECT art_almacen.id_articulo_almacen, %s, 1 FROM iv_articulos_almacen art_almacen
						WHERE art_almacen.id_casilla = %s
							AND art_almacen.id_articulo = %s
							AND art_almacen.estatus = 1;",
								valTpDato($idArticuloCosto, "int"),
								valTpDato($idCasilla, "int"),
								valTpDato($idArticulo, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$hddIdArticuloAlmacenCosto = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
					}
					
					$idArticuloAlmacenCosto = $hddIdArticuloAlmacenCosto;
					
					if($idArticuloAlmacenCosto == ""){
						return $objResponse->alert("No se pudo tomar la relacion con almacen en la devolucion \n\nLote: ".$idArticuloCosto." \n\nAlm Cost: ".$idArticuloAlmacenCosto." \n\nLinea: ".__LINE__);
					}
				}
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$insertSQLKardex = sprintf("INSERT INTO iv_kardex (id_documento, id_modulo, id_articulo, id_casilla, tipo_movimiento, tipo_documento_movimiento, cantidad, precio, pmu_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, id_clave_movimiento, estado, fecha_movimiento, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())",
					valTpDato($idNotaCredito, "int"),
					valTpDato("1", "int"), // 0 = Repuestos, 1 = Servicios, 2 = Vehiculos, 3 = Administracion
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($rowClaveMov['tipo'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato(2, "int"), // 2 DEVOLUCION FACTURA = NOTA CREDITO, 1 DEVOLUCION VALE SALIDA = VALE ENTRADA
					valTpDato($rowArt['nro_rpto_desp'], "int"),
					valTpDato($rowFactDet['precio_unitario'], "real_inglesa"),
					valTpDato($rowFactDet['pmu_unitario'], "real_inglesa"),
					valTpDato($idArticuloCosto, "int"),
					valTpDato($idArticuloAlmacenCosto, "int"),
					valTpDato($costoPromedioCompra, "real_inglesa"),
					valTpDato(0, "int"),//costo cargo
					valTpDato($valFormTotalDcto['txtDescuento'], "text"),
					valTpDato((($valFormTotalDcto['txtDescuento'] * $rowFactDet['precio_unitario']) / 100), "real_inglesa"),
					valTpDato($idClaveMovimiento, "int"),
					valTpDato(0, "int")); // 0 = Entrada, 1 = Salida
					
				mysql_query("SET NAMES 'utf8';");
				$Result1Kardex = mysql_query($insertSQLKardex);
				if (!$Result1Kardex) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql".$insertSQLKardex);
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");				
				
				
				// HACER UN SELECT DE LOS ARTICULOS QUE FUERON FACTURADOS TRAERLOS DE LA TABLA cj_cc_factura_detalle
				// QUE PASA SI EL ARTICULO NO TIENE DESCUENTO INDIVIDUAL?				
				
				
				$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, pmu_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, correlativo1, correlativo2, tipo_costo, llave_costo_identificado, promocion, id_moneda_costo, id_moneda_costo_cambio)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idKardex, "int"),
						valTpDato($rowArt['nro_rpto_desp'], "int"),//antes $rowFactDet['cantidad']
						valTpDato($rowFactDet['precio_unitario'], "real_inglesa"),
						valTpDato($rowFactDet['pmu_unitario'], "real_inglesa"),
						valTpDato($idArticuloCosto, "int"),
						valTpDato($idArticuloAlmacenCosto, "int"),
						valTpDato($costoPromedioCompra, "real_inglesa"),
						valTpDato(0, "int"),//costo cargo
						valTpDato($valFormTotalDcto['txtDescuento'], "text"),
						valTpDato((($valFormTotalDcto['txtDescuento'] * $rowFactDet['precio_unitario']) / 100), "real_inglesa"),
						valTpDato("", "int"),
						valTpDato("", "int"),
						valTpDato(0, "int"),
						valTpDato("", "text"),
						valTpDato(0, "boolean"),
						valTpDato("", "int"),
						valTpDato("", "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS)
				
				// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO - Roger
				$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
				$Result1 = actualizarSaldos($idArticulo, $idCasilla);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
			}
		}
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQLEdoCuenta = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE ('NC', %s, %s, 4);", 
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d"), "date"));
	mysql_query("SET NAMES 'utf8';");
	$Result1EdoCuenta = mysql_query($insertSQLEdoCuenta);
	if (!$Result1EdoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
		
	// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
	$queryActualizarEdoFactura = sprintf("UPDATE cj_cc_encabezadofactura SET
		saldoFactura = '0',
		estadoFactura = %s,
		anulada = 'SI',
		id_empleado_anulacion = %s,
		fecha_anulacion = NOW()
	WHERE idFactura = %s;",
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$rsActualizarEdoFactura = mysql_query($queryActualizarEdoFactura);
	if (!$rsActualizarEdoFactura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	devolverComisionNC($idNotaCredito, $idFactura);
	
	if ($rowFact['condicionDePago'] == 0) {
		$sqlActualizarSaldo = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
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
			valTpDato($rowFact['idCliente'], "int"),
			valTpDato($idEmpresa, "int"));
		$rsActualizarSaldo = mysql_query($sqlActualizarSaldo);
		if (!$rsActualizarSaldo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);
	}
		
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	}
	
	mysql_query("COMMIT;");
		
	//MODIFICADO ERNESTO
	if (function_exists("generarNotasVentasSe")) { generarNotasVentasSe($idNotaCredito,"",""); }
	//MODIFICADO ERNESTO
	
	$objResponse->alert("Nota De Credito Guardada Exitosamente");

	$objResponse->script(sprintf("window.location.href='cjrs_devolucion_venta_list.php';"));
	
	$objResponse->script(sprintf("verVentana('../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
		$idNotaCredito));
		
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

function guardarFactura($frmDcto, $frmTotalDcto, $frmDetallePago, $frmListaPagos){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cjrs_factura_venta_list","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$idModulo = 1; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idOrden = $frmDcto['txtIdPresupuesto'];
	$idTipoOrden = $frmDcto['lstTipoOrden'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];

	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	//VERIFICA SI EL DOCUMENTO YA HA SIDO FACTURADO
	$queryVerif = sprintf("SELECT * FROM cj_cc_encabezadofactura
		WHERE numeroPedido = %s
		AND idDepartamentoOrigenFactura IN (%s) LIMIT 1",
	valTpDato($idOrden,"int"),
	valTpDato($idModulo,"int"));
	$rsVerif = mysql_query($queryVerif);
	if (!$rsVerif) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if (mysql_num_rows($rsVerif) > 0) {
		return $objResponse->alert('Este documento ya ha sido facturado');
	}
	
	//CONSULTO LA CLAVE DE MOVIMIENTO SEGUN EL TIPO DE LA ORDEN
	$queryTipoDoc = sprintf("SELECT * FROM sa_tipo_orden WHERE id_tipo_orden = %s",
		valTpDato($idTipoOrden,"int"));
	$rsTipoDoc = mysql_query($queryTipoDoc);
	if (!$rsTipoDoc) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowTipoDoc = mysql_fetch_assoc($rsTipoDoc);
	
	$idClaveMovimiento = $rowTipoDoc["id_clave_movimiento"];
	$idFiltroOrden = $rowTipoDoc['id_filtro_orden'];
	
	//CONSULTO EL TIPO DE DOCUMENTO QUE GENERA Y SI ES DE CONTADO
	$queryDocGenera = sprintf("SELECT * FROM pg_clave_movimiento
	WHERE id_clave_movimiento = %s
		AND documento_genera = %s
		AND pago_contado = %s",
		valTpDato($idClaveMovimiento,"int"),
		valTpDato(1,"int"), // 1 = FACTURA
		valTpDato(1,"int")); // 0 = NO ; 1 = SI
	$rsDocGenera = mysql_query($queryDocGenera);
	if (!$rsDocGenera) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDocGenera = mysql_fetch_assoc($rsDocGenera);
	if (mysql_num_rows($rsDocGenera) > 0) {
		
		//VERIFICA QUE EL DOOCUMENTO A CONTADO ESTE CANCELADO EN TU TOTALIDAD
		if ($frmListaPagos['txtMontoPorPagar'] != 0) {
			return $objResponse->alert('Debe cancelar el monto total de la factura');
		}
	}
	
	//VERIFICAR SI YA LA ORDEN FUE FACTURADA
	$sqlVerificarEstatusOrden = sprintf("SELECT * FROM sa_orden WHERE id_orden = %s AND id_estado_orden IN (18, 24);",
			valTpDato($idOrden, "int"));
	$rsVerificarEstatusOrden = mysql_query($sqlVerificarEstatusOrden);
	if (!$rsVerificarEstatusOrden) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlVerificarEstatusOrden); }
	if (mysql_num_rows($rsVerificarEstatusOrden) > 0){
		$objResponse->script("byId('btnGuardar').style.display = 'none'");
		return $objResponse->alert("La Orden ".$idOrden." ya fue facturada");
	}
	
	$query = sprintf("SELECT idFactura FROM cj_cc_encabezadofactura
	WHERE numeroControl = %s
		AND idDepartamentoOrigenFactura = 1",
		valTpDato($frmDcto['txtNroControl'], "text"));
	$rs = mysql_query($query);
	if (!$rs)  { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);	
	
	if ($totalRows > 0) {
		return $objResponse->alert("El Numero de Control que Desea Registrar ya Existe Registrado en la Base de Datos");
	}
		
	mysql_query("START TRANSACTION;");
	
	//Puesto adelante para que ocurra primero y no quede la orden en el aire = 13
	// MODIFICA EL ESTADO DE LA ORDEN A FINALIZADA
	$queryActOrden = sprintf("UPDATE sa_orden SET
		fecha_factura = NOW(),
		id_estado_orden = 18
	WHERE id_orden = %s",
		 valTpDato($idOrden, "int"));
	//mysql_query("SET NAMES 'utf8';");
	$rsActOrden = mysql_query($queryActOrden);
	if (!$rsActOrden) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryActOrden); }
	//mysql_query("SET NAMES 'latin1';");
	
	// MODIFICA EL ESTADO DEL VALE DE RECEPCION
	$queryActualizarEdoValeAfact = sprintf("UPDATE sa_recepcion SET
		estado = 1
	WHERE id_recepcion = %s",
		valTpDato($frmDcto['txtIdValeRecepcion'], "int"));
	//mysql_query("SET NAMES 'utf8';");
	$rsActualizarActualizarEdoValeAfact = mysql_query($queryActualizarEdoValeAfact);
	if (!$rsActualizarActualizarEdoValeAfact) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryActualizarEdoValeAfact); }
	//mysql_query("SET NAMES 'latin1';");
			
	// BUSCA LOS DATOS DE LA ORDEN
	$queryOrden = sprintf("SELECT * FROM sa_orden WHERE id_orden = %s;",
		valTpDato($idOrden, "int"));
	$rsOrden = mysql_query($queryOrden);
	if (!$rsOrden) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryClaveMov); }
	$rowOrden = mysql_fetch_assoc($rsOrden);
	
	$idEmpleado = $rowOrden['id_empleado'];
	$idTipoOrden = $rowOrden['id_tipo_orden'];
		
	// BUSCA LOS DIAS DE CREDITO DEL CLIENTE
	$queryDiasCre = sprintf("SELECT cliente_cred.diascredito
		FROM cj_cc_credito cliente_cred
			INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente_cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
		WHERE cliente_emp.id_cliente = %s
		AND cliente_emp.id_empresa = %s",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));		
	$rsDiasCre = mysql_query($queryDiasCre);
	if (!$rsDiasCre) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryDiasCre); }
	$rowClienteCredito = mysql_fetch_assoc($rsDiasCre);
	
	$diasCreditoCliente = ($rowClienteCredito['diascredito'] == '') ? 0 : $rowClienteCredito['diascredito'];
	$fechaVencimientoFactura = suma_fechas("d-m-Y",date("d-m-Y"), $rowClienteCredito['diascredito']);
	
	// BUSCA LA CLAVE DE MOVIMIENTO PARA SABER EL TIPO DE PAGO DE LA ORDEN
	$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s;",
		valTpDato($idClaveMovimiento, "int"));
	$rsClaveMov = mysql_query($queryClaveMov);
	if (!$rsClaveMov) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryClaveMov); }
	$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
	
	$idTipoMovimiento = $rowClaveMov["tipo"];
	
	if ($rowClaveMov['pago_credito'] == 1) {
		$idTipoPago = 0; // 0 = Credito, 1 = Contado
	} else if ($rowClaveMov['pago_contado'] == 1) {
		$idTipoPago = 1; // 0 = Credito, 1 = Contado
	} else {
		$idTipoPago = 1; // 0 = Credito, 1 = Contado
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
	$numeroActualFactura = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado la numeracion de facturas"); }
	
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

	if($frmTotalDcto['txtIvaVenta'] == NULL){
		$frmTotalDcto['txtIvaVenta'] = 0;
	}
		
	$totalIvas = 0;
	$porcentajeIvaSimple = 0;
	$baseImponibleSimple = 0;
	foreach ($frmTotalDcto["ivaActivo"] as $key => $idIvaActivo){
		$totalIvas += str_replace(",","", $frmTotalDcto["txtTotalIva".$idIvaActivo]);
		
		if($valFormTotalDcto["txtTotalIva".$idIvaActivo] > 0){
			$idIvaSimple = $idIvaActivo;
			$porcentajeIvaSimple = $frmTotalDcto["txtIvaVenta".$idIvaActivo];
			$baseImponibleSimple = $frmTotalDcto["txtBaseImponibleIva".$idIvaActivo];
		}
	}
	
	$insertSQL = sprintf("INSERT INTO cj_cc_encabezadofactura (numeroControl, fechaRegistroFactura, numeroFactura, fechaVencimientoFactura, montoTotalFactura, saldoFactura, estadoFactura, id_clave_movimiento, idVendedor, idCliente, numeroPedido, idDepartamentoOrigenFactura, descuentoFactura, porcentaje_descuento, porcentajeIvaFactura, calculoIvaFactura, subtotalFactura, condicionDePago, baseImponible, diasDeCredito, montoExento, anulada, aplicaLibros, id_empresa, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($frmDcto['txtNroControl'], "text"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato($numeroActualFactura, "text"),
		valTpDato(date("Y-m-d",strtotime($fechaVencimientoFactura)), "date"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada , 2 = Parcialmente Cancelada
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpleado, "int"),
		valTpDato($idCliente, "int"),
		valTpDato($idOrden, "int"),
		valTpDato($idModulo, "int"), // 0 = Repuesto, 1 = Sevicios, 2 = Autos, 3 = Administracion
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($porcentajeIvaSimple, "real_inglesa"),//solo simple impuestos 
		valTpDato($totalIvas, "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato($idTipoPago, "int"),
		valTpDato($baseImponibleSimple, "real_inglesa"),//solo simple impuestos 
		valTpDato($diasCreditoCliente, "int"),
		valTpDato($frmTotalDcto['txtMontoExento'], "real_inglesa"),
		valTpDato("NO", "text"),
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato($idEmpresa, "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idFactura = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	//IVAS ORDEN
	$query = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
		SELECT %s, base_imponible, subtotal_iva, id_iva, iva, lujo
		FROM sa_orden_iva 
		WHERE id_orden = %s 
		",
		$idFactura,
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
	$queryCompruebaLote = "SHOW TABLES LIKE 'vw_iv_articulos_almacen_costo'";
	$rsCompruebaLote = mysql_query($queryCompruebaLote);
	if (!$rsCompruebaLote){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
	$compruebaLote = mysql_num_rows($rsCompruebaLote);
			
	if($compruebaLote > 0){//USA LOTES
		$sqlCosto = sprintf("(SELECT
				if((SELECT valor FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s) IN(1,3), 
																					(SELECT vw_iv_articulos_almacen_costo.costo FROM vw_iv_articulos_almacen_costo WHERE vw_iv_articulos_almacen_costo.id_articulo_costo = det_orden_art.id_articulo_costo LIMIT 1), 
																					(SELECT vw_iv_articulos_almacen_costo.costo_promedio FROM vw_iv_articulos_almacen_costo WHERE vw_iv_articulos_almacen_costo.id_articulo_costo = det_orden_art.id_articulo_costo LIMIT 1)
							)) ",
				valTpDato($idEmpresa, "int"));
	}else{//NO USA LOTES
		$sqlCosto = sprintf("(SELECT
		if((SELECT valor FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
		WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s) = 1, iv_articulos_costos.costo, iv_articulos_costos.costo_promedio) AS costo
		FROM iv_articulos_costos 
		WHERE iv_articulos_costos.id_articulo = det_orden_art.id_articulo AND iv_articulos_costos.id_empresa = %s ORDER BY id_articulo_costo DESC LIMIT 1) ",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	}
		
	// BUSCA LOS ARTICULOS DE LA ORDEN
	$queryArt = sprintf("SELECT
		det_orden_art.id_articulo,
		det_orden_art.id_paquete,
		det_orden_art.cantidad,
		det_orden_art.id_precio,
		det_orden_art.precio_unitario,
		det_orden_art.id_articulo_costo,
		det_orden_art.id_articulo_almacen_costo,
		det_orden_art.porcentaje_pmu,
		det_orden_art.base_pmu,
		det_orden_art.pmu_unitario,
		
		%s AS costo,
					
		det_orden_art.id_iva,
		det_orden_art.iva,
		det_orden_art.aprobado,
		det_orden_art.tiempo_asignacion,
		det_orden_art.tiempo_aprobacion,
		det_orden_art.id_empleado_aprobacion,
		det_orden_art.estado_articulo,
		det_orden_art.estado_articulo + 0 AS edo_articulo,
		det_orden_art.id_det_orden_articulo,
		orden.porcentaje_descuento,
		(((det_orden_art.precio_unitario * det_orden_art.cantidad) * orden.porcentaje_descuento) / 100) AS subtotal_descuento
	FROM sa_orden orden
		INNER JOIN sa_det_orden_articulo det_orden_art ON (orden.id_orden = det_orden_art.id_orden)
	WHERE orden.id_empresa = %s
		AND orden.id_orden = %s
		AND det_orden_art.aprobado = 1
		AND det_orden_art.estado_articulo <> 'DEVUELTO';",
		$sqlCosto,
		valTpDato($idEmpresa, "int"),
		valTpDato($idOrden, "int"));
	$rsArt = mysql_query($queryArt);
	if (!$rsArt) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryArt); }
	
	//*************************************************************************************************************
	if (mysql_num_rows($rsArt) > 0) {
				
		/************************************************************************************************/
		//COMPRUEBO QUE TENGA REPUESTO EN SOLICITUD COMO DESPACHADO, POR LO MENOS 1                                
		$queryComprobacion = sprintf("SELECT sa_orden.id_orden 
							FROM sa_orden 
							LEFT JOIN sa_solicitud_repuestos ON sa_orden.id_orden = sa_solicitud_repuestos.id_orden
							LEFT JOIN sa_det_solicitud_repuestos ON sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
							WHERE sa_orden.id_orden = %s AND sa_det_solicitud_repuestos.id_estado_solicitud = 3", //3 DESPACHADO
							valTpDato($idOrden, "int"));

		$rsComprobacion = mysql_query($queryComprobacion);
		if(!$rsComprobacion){ return $objResponse->alert(mysql_error()."sele comprob repst \n\nLine: ".__LINE__); }
		
		if(mysql_num_rows($rsComprobacion)){//si tiene despachado, crear movimiento
			// INSERTA EL MOVIMIENTO
			$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
			VALUE (%s, %s, %s, NOW(), %s, %s, NOW(), %s, %s)",
				valTpDato($idTipoMovimiento, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($idFactura, "int"),
				valTpDato($idCliente, "int"),
				valTpDato(0, "boolean"),
				valTpDato($_SESSION['idUsuarioSysGts'], "int"),
				valTpDato($idTipoPago, "int")); // 0 = Credito, 1 = Contado
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idMovimiento = mysql_insert_id();
		 }
				/**************************************************************************************************/
				
		// INSERTA EL DETALLE DEL MOVIMIENTO
		while($rowArt = mysql_fetch_array($rsArt)) {
			$idArticulo = $rowArt['id_articulo'];
			$costoArt = $rowArt['costo'];
			
			// BUSCA LA CANTIDAD DE REPUESTOS DESPACHADOS
			$queryCont = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
			WHERE id_det_orden_articulo = %s
				AND id_estado_solicitud = 3", 
				valTpDato($rowArt['id_det_orden_articulo'], "int"));
			$rsCont = mysql_query($queryCont);
			if (!$rsCont) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowCont = mysql_fetch_assoc($rsCont);
			
			if ($rowCont['nro_rpto_desp'] > 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle (id_factura, id_articulo, cantidad, pendiente, estatus, precio_unitario, pmu_unitario, id_articulo_costo, id_articulo_almacen_costo, costo_compra, id_iva, iva)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s,  %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($rowCont['nro_rpto_desp'], "int"),
					0,//pendiente
					1,//estatus
					valTpDato($rowArt['precio_unitario'], "real_inglesa"),
					valTpDato($rowArt['pmu_unitario'], "real_inglesa"),
					valTpDato($rowArt['id_articulo_costo'], "int"),
					valTpDato($rowArt['id_articulo_almacen_costo'], "int"),
					valTpDato($costoArt, "real_inglesa"),
					valTpDato($rowArt['id_iva'], "int"),
					valTpDato($rowArt['iva'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idDocumentoDetalleFactura = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				$insertSQLDetFact = sprintf("INSERT INTO sa_det_fact_articulo (idFactura, id_articulo, id_paquete, cantidad, id_precio, precio_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, porcentaje_pmu, base_pmu, pmu_unitario, id_iva, iva, aprobado, tiempo_asignacion,  tiempo_aprobacion, id_empleado_aprobacion, estado_articulo, id_factura_detalle)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, '%s', '%s', %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($rowArt['id_paquete'], "int"),
					valTpDato($rowCont['nro_rpto_desp'], "int"),
					valTpDato($rowArt['id_precio'], "int"),
					valTpDato($rowArt['precio_unitario'], "real_inglesa"),
					valTpDato($rowArt['id_articulo_costo'], "int"),
					valTpDato($rowArt['id_articulo_almacen_costo'], "int"),
					valTpDato($costoArt, "real_inglesa"),
					valTpDato($rowArt['porcentaje_pmu'], "real_inglesa"),
					valTpDato($rowArt['base_pmu'], "real_inglesa"),
					valTpDato($rowArt['pmu_unitario'], "real_inglesa"),
					valTpDato($rowArt['id_iva'], "int"),
					valTpDato($rowArt['iva'], "real_inglesa"),
					$rowArt['aprobado'], 
					$rowArt['tiempo_asignacion'],
					$rowArt['tiempo_aprobacion'],
					$rowArt['id_empleado_aprobacion'],
					$rowArt['edo_articulo'],
					$idDocumentoDetalleFactura);
				mysql_query("SET NAMES 'utf8';");
				$rsDetFact = mysql_query($insertSQLDetFact);
				if (!$rsDetFact) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$idDetalleArtFact = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				//GUARDANDO MULTIPLES IVAS DE LOS REPUESTOS
				$query = sprintf("SELECT id_iva, iva, lujo 
								FROM sa_det_orden_articulo_iva 
								WHERE id_det_orden_articulo = %s",
						$rowArt['id_det_orden_articulo']);
				$rs3 = mysql_query($query);
				if(!$rs3) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				
				while($rowArtIvaOrden = mysql_fetch_assoc($rs3)){
					$query = sprintf("INSERT INTO sa_det_fact_articulo_iva (id_det_fact_articulo, id_iva, iva, lujo)
											VALUES(%s, %s, %s, %s)",
					$idDetalleArtFact,
					$rowArtIvaOrden["id_iva"],
					$rowArtIvaOrden["iva"],
					valTpDato($rowArtIvaOrden["lujo"],"int"));
					
					$rs4 = mysql_query($query);
					if(!$rs4) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query); }
				}

									
				// BUSCA LOS ARTICULOS DESPACHADOS POR UBICACION
				$queryContCas = sprintf("SELECT 
					id_casilla,
					COUNT(*) AS nro_rpto_desp_cas
				FROM sa_det_solicitud_repuestos
				WHERE id_det_orden_articulo = %s
					AND id_estado_solicitud = 3
				GROUP BY id_casilla", 
					valTpDato($rowArt['id_det_orden_articulo'], "int"));
				$rsContCas = mysql_query($queryContCas);
				if (!$rsContCas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				while($rowContCas = mysql_fetch_assoc($rsContCas)) {
					$idCasilla = $rowContCas['id_casilla'];
					$cantidadDespachada = $rowContCas['nro_rpto_desp_cas'];
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO 
					$insertSQL = sprintf("INSERT INTO iv_kardex (id_documento, id_modulo, id_articulo, id_casilla, tipo_movimiento, cantidad, precio, pmu_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, id_clave_movimiento, estado, fecha_movimiento, hora_movimiento)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())",
						valTpDato($idFactura, "int"),
						valTpDato($idModulo, "int"), // 0 = Repuestos, 1 = Servicios, 2 = Vehiculos, 3 = Administracion
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($rowClaveMov['tipo'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($cantidadDespachada, "int"),
						valTpDato($rowArt['precio_unitario'], "real_inglesa"),
						valTpDato($rowArt['pmu_unitario'], "real_inglesa"),
						valTpDato($rowArt['id_articulo_costo'], "int"),	
						valTpDato($rowArt['id_articulo_almacen_costo'], "int"),	
						valTpDato($costoArt, "real_inglesa"),	
						valTpDato(0, "int"),//costo_cargo
						valTpDato($rowArt['porcentaje_descuento'], "text"),
						valTpDato($rowArt['subtotal_descuento'], "real_inglesa"),
						valTpDato($idClaveMovimiento, "int"),
						valTpDato(1, "int")); // 0 = Entrada, 1 = Salida
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					
					// INSERTA EL DETALLE DEL MOVIMIENTO
					$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, pmu_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, correlativo1, correlativo2, tipo_costo, llave_costo_identificado, promocion, id_moneda_costo, id_moneda_costo_cambio)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idKardex, "int"),
						valTpDato($rowCont['nro_rpto_desp'], "int"),
						valTpDato($rowArt['precio_unitario'], "real_inglesa"),
						valTpDato($rowArt['pmu_unitario'], "real_inglesa"),
						valTpDato($rowArt['id_articulo_costo'], "int"),
						valTpDato($rowArt['id_articulo_almacen_costo'], "int"),
						valTpDato($costoArt, "real_inglesa"),
						valTpDato(0, "int"), //costo cargo
						valTpDato($rowArt['porcentaje_descuento'], "text"),
						valTpDato($rowArt['subtotal_descuento'], "real_inglesa"),
						valTpDato("", "int"),
						valTpDato("", "int"),
						valTpDato(0, "int"),
						valTpDato("", "text"),
						valTpDato(0, "boolean"),
						valTpDato("", "int"),
						valTpDato("", "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}
				
				//ACTUALIZAR SOLICITUD PRIMERO:
				// BUSCA LOS ARTICULOS EN LAS SOLICITUDES DONDE FUERON DESPACHADAS
				$queryEdoDetSolicitudDespachado = sprintf("SELECT 
					id_det_solicitud_repuesto,
					id_solicitud
				FROM sa_det_solicitud_repuestos
				WHERE id_det_orden_articulo = %s
					AND id_estado_solicitud = 3;", 
					valTpDato($rowArt['id_det_orden_articulo'], "int"));
				$rsEdoDetSolicitudDespachado = mysql_query($queryEdoDetSolicitudDespachado);
				if (!$rsEdoDetSolicitudDespachado) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				while ($rowEdoDetSolicitudDespachado = mysql_fetch_assoc($rsEdoDetSolicitudDespachado)) {
					// ACTUALIZA EL ESTADO DEL ARTICULO EN EL DETALLE DE LA SOLICITUD COMO FACTURADO
					$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
						id_estado_solicitud = 5
					WHERE id_det_solicitud_repuesto = %s;",
						$rowEdoDetSolicitudDespachado['id_det_solicitud_repuesto']);
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL ESTADO DE LA SOLICITUD COMO FACTURADO
					$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
						estado_solicitud = 5
					WHERE id_solicitud = %s;",
						 valTpDato($rowEdoDetSolicitudDespachado['id_solicitud'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
				
									
				// ACTUALIZA EL ESTADO DEL DETALLE DE LA ORDEN COMO FACTURADO
				$query = sprintf("UPDATE sa_det_orden_articulo SET
					estado_articulo = 6
				WHERE id_det_orden_articulo = %s",
					valTpDato($rowArt['id_det_orden_articulo'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$rs1 = mysql_query($query);
				if (!$rs1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
				mysql_query("SET NAMES 'latin1';");
			}
			
			// BUSCA LOS ARTICULOS DE LA ORDEN POR UBICACION
			$queryContCas = sprintf("SELECT 
				id_casilla,
				COUNT(*) AS nro_rpto_desp_cas
			FROM sa_det_solicitud_repuestos
			WHERE id_det_orden_articulo = %s
			GROUP BY id_casilla", 
				valTpDato($rowArt['id_det_orden_articulo'], "int"));
			$rsContCas = mysql_query($queryContCas);
			if (!$rsContCas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			while($rowContCas = mysql_fetch_assoc($rsContCas)) {
				$idCasilla = $rowContCas['id_casilla'];
				$cantidadDespachada = $rowContCas['nro_rpto_desp_cas'];
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (SALIDAS, RESERVADAS)
				
				
				// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO - Roger
				$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
				$Result1 = actualizarSaldos($idArticulo, $idCasilla);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			

			}
		}
	}
	//*************************************************************************************************************
	
	// INSERTA LAS MANO DE OBRA
	$query = sprintf("
	SELECT 
		sa_det_orden_tempario.id_det_orden_tempario,
		sa_det_orden_tempario.id_paquete,
		sa_det_orden_tempario.id_tempario,
		sa_det_orden_tempario.precio,
		sa_det_orden_tempario.costo,
		sa_det_orden_tempario.costo_orden,
		sa_det_orden_tempario.id_modo,
		sa_det_orden_tempario.base_ut_precio,
		sa_det_orden_tempario.operador,
		sa_det_orden_tempario.aprobado,
		sa_det_orden_tempario.ut,
		sa_det_orden_tempario.tiempo_aprobacion,
		sa_det_orden_tempario.tiempo_asignacion,
		sa_det_orden_tempario.tiempo_inicio,
		sa_det_orden_tempario.tiempo_fin,
		sa_det_orden_tempario.id_mecanico,
		sa_det_orden_tempario.id_empleado_aprobacion,
		sa_det_orden_tempario.origen_tempario,
		sa_det_orden_tempario.estado_tempario,
		sa_det_orden_tempario.precio_tempario_tipo_orden
	FROM sa_orden
		INNER JOIN sa_det_orden_tempario ON (sa_orden.id_orden = sa_det_orden_tempario.id_orden)
	WHERE sa_orden.id_empresa = %s
		AND sa_orden.id_orden= %s
		AND sa_det_orden_tempario.aprobado = 1
		AND estado_tempario <> 'DEVUELTO'",
		valTpDato($idEmpresa, "int"),
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query); }
	
	while($rowTemparioOrden = mysql_fetch_assoc($rs)){	
		$query = sprintf("INSERT INTO sa_det_fact_tempario (idFactura, id_paquete, id_tempario, precio, costo, costo_orden, id_modo, base_ut_precio, operador, aprobado, ut, tiempo_aprobacion, tiempo_asignacion, tiempo_inicio, tiempo_fin, id_mecanico, id_empleado_aprobacion, origen_tempario, estado_tempario, precio_tempario_tipo_orden) 
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idFactura, "int"),
			valTpDato($rowTemparioOrden["id_paquete"], "int"),
			valTpDato($rowTemparioOrden["id_tempario"], "int"),
			valTpDato($rowTemparioOrden["precio"], "real_inglesa"),
			valTpDato($rowTemparioOrden["costo"], "real_inglesa"),
			valTpDato($rowTemparioOrden["costo_orden"], "real_inglesa"),
			valTpDato($rowTemparioOrden["id_modo"], "int"),
			valTpDato($rowTemparioOrden["base_ut_precio"], "int"),
			valTpDato($rowTemparioOrden["operador"], "int"),
			valTpDato($rowTemparioOrden["aprobado"], "int"),
			valTpDato($rowTemparioOrden["ut"], "real_inglesa"),
			"'".$rowTemparioOrden["tiempo_aprobacion"]."'", //a veces vacio conflicto para validar, no puede ser null
			valTpDato($rowTemparioOrden["tiempo_asignacion"], "date"),
			"'".$rowTemparioOrden["tiempo_inicio"]."'", 
			"'".$rowTemparioOrden["tiempo_fin"]."'",
			valTpDato($rowTemparioOrden["id_mecanico"], "int"),
			valTpDato($rowTemparioOrden["id_empleado_aprobacion"], "int"),
			"'".$rowTemparioOrden["origen_tempario"]."'",
			valTpDato($rowTemparioOrden["estado_tempario"], "text"),
			valTpDato($rowTemparioOrden["precio_tempario_tipo_orden"], "real_inglesa"));
		$rs2 = mysql_query($query);
		if (!$rs2) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query); }
		$idDetFactTempario = mysql_insert_id();
		
		$query = sprintf("SELECT id_iva, iva, lujo
							FROM sa_det_orden_tempario_iva 
							WHERE id_det_orden_tempario = %s",
					valTpDato($rowTemparioOrden["id_det_orden_tempario"], "int"));
		$rs3 = mysql_query($query);
		if(!$rs3) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		while($rowTempIvaFact = mysql_fetch_assoc($rs3)){
			$query = sprintf("INSERT INTO sa_det_fact_tempario_iva (id_det_fact_tempario, id_iva, iva, lujo)
									VALUES(%s, %s, %s, %s)",
				valTpDato($idDetFactTempario, "int"),
				valTpDato($rowTempIvaFact["id_iva"], "int"),
				valTpDato($rowTempIvaFact["iva"], "real_inglesa"),
				valTpDato($rowTempIvaFact["lujo"], "int"));
			$rs4 = mysql_query($query);
			if(!$rs4) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		}		
		
	}

	$queryDet = sprintf("UPDATE sa_det_orden_tempario SET
		estado_tempario = 7
	WHERE id_det_orden_tempario IN (SELECT id_det_orden_tempario FROM
										(SELECT sa_det_orden_tempario.id_det_orden_tempario
										FROM sa_det_orden_tempario
										WHERE sa_det_orden_tempario.aprobado = 1
											AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'
											AND sa_det_orden_tempario.id_orden = %s) AS id_det);",
		valTpDato($idOrden, "int"));
	$rsDet = mysql_query($queryDet);
	if (!$rsDet) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryDet); }
	
	
	$query = sprintf("SELECT 
		sa_det_orden_tot.id_det_orden_tot,
		sa_det_orden_tot.id_orden_tot,
		sa_det_orden_tot.id_porcentaje_tot,
		sa_det_orden_tot.porcentaje_tot,
		sa_det_orden_tot.aprobado
	FROM sa_orden
		INNER JOIN sa_det_orden_tot ON (sa_orden.id_orden = sa_det_orden_tot.id_orden)
	WHERE sa_orden.id_empresa = %s
		AND sa_orden.id_orden= %s
		AND sa_det_orden_tot.aprobado = 1",
		valTpDato($idEmpresa, "int"),
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query); }
	
	while($rowTotOrden = mysql_fetch_assoc($rs)) {
		$query = sprintf("INSERT INTO sa_det_fact_tot (idFactura, id_orden_tot, id_porcentaje_tot, porcentaje_tot, aprobado)
			VALUES (%s, %s, %s, %s, %s)",
			valTpDato($idFactura, "int"),
			valTpDato($rowTotOrden['id_orden_tot'], "int"),
			valTpDato($rowTotOrden['id_porcentaje_tot'], "int"),
			valTpDato($rowTotOrden['porcentaje_tot'], "double"),
			valTpDato($rowTotOrden['aprobado'], "int"));
		$rs2 = mysql_query($query);
		if (!$rs2) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query); }
		$idDetFactTot = mysql_insert_id();
		
		$query = sprintf("SELECT id_iva, iva, lujo 
						FROM sa_det_orden_tot_iva 
						WHERE id_det_orden_tot = %s",
			valTpDato($rowTotOrden["id_det_orden_tot"], "int"));
		$rs3 = mysql_query($query);
		if(!$rs3) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
		while($rowTotIvaOrden = mysql_fetch_assoc($rs3)){
			$query = sprintf("INSERT INTO sa_det_fact_tot_iva (id_det_fact_tot, id_iva, iva, lujo)
									VALUES(%s, %s, %s, %s)",
				valTpDato($idDetFactTot, "int"),
				valTpDato($rowTotIvaOrden["id_iva"], "int"),
				valTpDato($rowTotIvaOrden["iva"], "real_inglesa"),
				valTpDato($rowTotIvaOrden["lujo"], "int"));
			$rs4 = mysql_query($query);
			if(!$rs4) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		}
		
		$query = sprintf("UPDATE sa_orden_tot SET
			estatus = 3
		WHERE sa_orden_tot.id_orden_tot = %s",
			valTpDato($rowTotOrden['id_orden_tot'], "int"));
		$rs5 = mysql_query($query);
		if (!$rs5) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query); }
	}
	
	// INSERTA LAS NOTAS
	$query = sprintf("
	SELECT 
		sa_det_orden_notas.id_det_orden_nota,
		sa_det_orden_notas.descripcion_nota,
		sa_det_orden_notas.precio,
		sa_det_orden_notas.aprobado
	FROM sa_orden
		INNER JOIN sa_det_orden_notas ON (sa_orden.id_orden = sa_det_orden_notas.id_orden)
	WHERE sa_orden.id_empresa = %s
		AND sa_orden.id_orden= %s
		AND sa_det_orden_notas.aprobado = 1",
		valTpDato($idEmpresa, "int"),
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query); }
	
	while($rowNotaOrden = mysql_fetch_assoc($rs)) {
		$query = sprintf("INSERT INTO sa_det_fact_notas (idFactura, descripcion_nota, precio, aprobado) 
			VALUES (%s, %s, %s, %s)",
			valTpDato($idFactura, "int"),
			valTpDato($rowNotaOrden['descripcion_nota'], "text"),
			valTpDato($rowNotaOrden['precio'], "real_inglesa"),
			valTpDato($rowNotaOrden['aprobado'], "int"));
		$rs2 = mysql_query($query);
		if (!$rs2) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query); }
		$idDetFactNota = mysql_insert_id();
		
		$query = sprintf("SELECT id_iva, iva, lujo 
						FROM sa_det_orden_notas_iva 
						WHERE id_det_orden_nota = %s",
			valTpDato($rowNotaOrden["id_det_orden_nota"], "int"));
		$rs3 = mysql_query($query);
		if(!$rs3) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

		while($rowNotaIvaFact = mysql_fetch_assoc($rs3)){
			$query = sprintf("INSERT INTO sa_det_fact_notas_iva (id_det_fact_nota, id_iva, iva, lujo)
									VALUES(%s, %s, %s, %s)",
				valTpDato($idDetFactNota, "int"),
				valTpDato($rowNotaIvaFact["id_iva"], "int"),
				valTpDato($rowNotaIvaFact["iva"], "real_inglesa"),
				valTpDato($rowNotaIvaFact["lujo"], "int"));
			$rs4 = mysql_query($query);
			if(!$rs4) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		}
		
	}
	
	
	// INSERTA LOS DESCUENTOS
	$insertSQLDcto = sprintf("INSERT INTO sa_det_fact_descuento(idFactura, id_porcentaje_descuento, porcentaje)
	SELECT 
		%s,
		sa_det_orden_descuento.id_porcentaje_descuento,
		sa_det_orden_descuento.porcentaje
	FROM sa_orden
		INNER JOIN sa_det_orden_descuento ON (sa_orden.id_orden = sa_det_orden_descuento.id_orden)
	WHERE sa_orden.id_empresa = %s
		AND sa_orden.id_orden= %s",
		valTpDato($idFactura, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idOrden, "int"));
	mysql_query("SET NAMES 'utf8';");
	$rsDcto = mysql_query($insertSQLDcto);
	if (!$rsDcto) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$insertSQLDcto); }
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("FA", "text"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC, 5 = CH
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

	// ELIMINA DE LA ORDEN LOS REPUESTOS QUE NO HAYAN SIDO DESPACHADOS
	$deleteSQL = sprintf("DELETE FROM sa_det_orden_articulo 
	WHERE (estado_articulo = 'PENDIENTE' OR estado_articulo = 1)
		AND id_orden = %s",
		valTpDato($idOrden, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$deleteSQL); }
	
	// ASIGNA EL ESTATUS ANULADO A LOS ARTICULOS DE LAS SOLICITUDES QUE NO HAYAN SIDO DESPACHADAS
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos, sa_solicitud_repuestos SET
		id_estado_solicitud = 6
	WHERE sa_det_solicitud_repuestos.id_solicitud = sa_solicitud_repuestos.id_solicitud
		AND sa_solicitud_repuestos.id_orden = %s
		AND (sa_det_solicitud_repuestos.id_estado_solicitud = 1 OR sa_det_solicitud_repuestos.id_estado_solicitud = 2);",
		valTpDato($idOrden, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	// ASIGNA EL ESTATUS FACTURADO A LAS SOLICITUDES DE LA ORDEN
	$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
		estado_solicitud = 5
	WHERE sa_solicitud_repuestos.id_orden = %s;",
		valTpDato($idOrden, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
	// CALCULO DE LAS COMISIONES
	$Result1 = calcular_comision_factura($idFactura);
	if ($Result1[0] != true) { return $objResponse->alert($Result1[1]); }
	
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	}
				
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	//INSERTA EL PAGO DEL DOCUMENTO (PAGO DE FACTURAS) SOLO SI ES DE CONTADO Y NO ES UNA DEVOLUCION
	if (in_array($idFiltroOrden, array(1,7)) && !isset($_GET['dev'])) { // 1 = CONTADO, 7 = LAT/PINTURA CONTADO
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
		
		$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(((in_array($idCajaPpal,array(1))) ? 45 : 44), "int"), // 44 = Recibo de Pago Repuestos y Servicios, 45 = Recibo de Pago Vehículos
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
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
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
					$insertSQL = sprintf("INSERT INTO sa_iv_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, numero_cuenta_cliente, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, estatus, id_condicion_mostrar, id_mostrar_contado, id_encabezado_rs)
					VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
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
						valTpDato(0, "int"),
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
						$sqlCheque = sprintf("SELECT numero_cheque, saldo_cheque FROM cj_cc_cheque WHERE id_cheque = %s;",
							valTpDato($idCheque, "int"));
						$rsCheque = mysql_query($sqlCheque);
						if (!$rsCheque) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowCheque = mysql_fetch_assoc($rsCheque);
						
						$saldoCheque = $rowCheque['saldo_cheque'] - str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$estatusCheque = ($saldoCheque == 0) ? 3 : 2;
						if ($idCheque > 0 && $saldoCheque < 0) { return $objResponse->alert("El saldo del cheque Nro: ".$rowCheque['numero_cheque']." no puede quedar en negativo: ".$saldoCheque); }
						
						$sqlUpdateCheque = sprintf("UPDATE cj_cc_cheque SET
							saldo_cheque = %s,
							estado_cheque = %s
						WHERE id_cheque = %s;",
							valTpDato($saldoCheque, "real_inglesa"),
							valTpDato($estatusCheque, "int"),
							valTpDato($idCheque, "int"));
						$rsUpdateCheque = mysql_query($sqlUpdateCheque);
						if (!$rsUpdateCheque) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
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
									valTpDato(date("Y-m-d",strtotime($frmListaPagos['txtFechaDeposito'.$valorPago])), "date"),
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
						$sqlTransferencia = sprintf("SELECT numero_transferencia, saldo_transferencia FROM cj_cc_transferencia WHERE id_transferencia = %s;",
							valTpDato($idTransferencia, "int"));
						$rsTransferencia = mysql_query($sqlTransferencia);
						if (!$rsTransferencia) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowTransferencia = mysql_fetch_assoc($rsTransferencia);
						
						$saldoTransferencia = $rowTransferencia['saldo_transferencia'] - str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
						$estatusTransferencia = ($saldoTransferencia == 0) ? 3 : 2;
						if ($idTransferencia > 0 && $saldoTransferencia < 0) { return $objResponse->alert("El saldo de la Transferencia Nro: ".$rowTransferencia['numero_transferencia']." no puede quedar en negativo: ".$saldoTransferencia); }
						
						$sqlUpdateTransferencia = sprintf("UPDATE cj_cc_transferencia SET
							saldo_transferencia = %s,
							estado_transferencia = %s
						WHERE id_transferencia = %s;",
							valTpDato($saldoTransferencia, "real_inglesa"),
							valTpDato($estatusTransferencia, "int"),
							valTpDato($idTransferencia, "int"));
						$rsUpdateTransferencia = mysql_query($sqlUpdateTransferencia);
						if (!$rsUpdateTransferencia) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
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
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int")); // AND (cxc_pago.id_concepto IS NULL OR cxc_pago.id_concepto NOT IN (6))
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
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int"));
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
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// VERIFICA EL SALDO DEL ANTICIPO A VER SI ESTA NEGATIVO
						$querySaldoDcto = sprintf("SELECT cxc_ant.*,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
						FROM cj_cc_anticipo cxc_ant
							INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
						WHERE idAnticipo = %s
							AND saldoAnticipo < 0;",
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int"));
						$rsSaldoDcto = mysql_query($querySaldoDcto);
						if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
						$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
						$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
						if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("El Anticipo Nro. ".$rowSaldoDcto['numeroAnticipo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
						
					} else if ($idFormaPago == 8) { // 8 = Nota de Crédito
						// ACTUALIZA EL SALDO DEL NOTA CREDITO DEPENDIENDO DE SUS PAGOS (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
						$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
							saldoNotaCredito = montoNetoNotaCredito
						WHERE idNotaCredito = %s
							AND estadoNotaCredito IN (0,1,2,3,4);",
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int")); // AND (cxc_nc_det.id_concepto IS NULL OR cxc_nc_det.id_concepto NOT IN (6))
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
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int"));
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
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						// VERIFICA EL SALDO DEL NOTA CREDITO A VER SI ESTA NEGATIVO
						$querySaldoDcto = sprintf("SELECT cxc_nc.*,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
						FROM cj_cc_notacredito cxc_nc
							INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
						WHERE idNotaCredito = %s
							AND saldoNotaCredito < 0;",
							valTpDato($frmListaPagos['txtIdNumeroDctoPago'.$valorPago], "int"));
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
							valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
							valTpDato(date("Y",strtotime($fechaRegistroPago)), "text"),
							valTpDato(date("m",strtotime($fechaRegistroPago)), "text"),
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
			saldoFactura = IFNULL(saldoFactura, 0)
								- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
											WHERE cxc_pago.id_factura = cxc_fact.idFactura
												AND cxc_pago.estatus IN (1)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
											WHERE cxc_pago.id_factura = cxc_fact.idFactura
												AND cxc_pago.estatus IN (1)), 0))
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
	
	//CONTABILIZA EL DOCUMENTO
	//MODIFICADO ERNESTO
	if (function_exists("generarVentasSe")) { generarVentasSe($idFactura,"",""); }
	//MODIFICADO ERNESTO
		
	//VERIFICA, SI EL DOCUMENTO ES DE CONTADO CONTABILIZA, YA QUE EL PAGO SE REALIZA AL MOMENTO
	/*if ($idTipoPago == 1) { // 0 = Credito, 1 = Contado
		//CONTABILIZA PAGOS DEL DOCUMENTO
		if (isset($arrayIdDctoContabilidadPago)) {
			foreach ($arrayIdDctoContabilidadPago as $indice => $valor) {			
				// MODIFICADO ERNESTO
					$idPago = $arrayIdDctoContabilidadPago[$indice][0];
						if (function_exists("generarCajasEntradaRe")) { generarCajasEntradaRe($idPago,"",""); }
				// MODIFICADO ERNESTO
			}
		}
	}*/	
		
	
	$objResponse->alert("Factura Guardada con Exito");
	
	$objResponse->script(sprintf("window.location.href='cjrs_factura_venta_list.php';"));
		
	$objResponse->script(sprintf("verVentana('../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s', 960, 550);",
		$idFactura));
						
	if ($idPago > 0) {
		$objResponse->script(sprintf("verVentana('reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s',960,550)", $idEncabezadoReciboPago));
	}
	
	return $objResponse;
}

function insertarPago($frmListaPagos, $frmDetallePago, $frmDeposito, $frmLista, $frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	$contFila = $arrayObj2[count($arrayObj2)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	
	if (str_replace(",", "", $frmListaPagos['txtMontoPorPagar']) < str_replace(",", "", $frmDetallePago['txtMontoPago'])) {
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
			$queryAnticipo = sprintf("SELECT *
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
					&& ($rowAnticipo['saldoAnticipo'] > 0 || ($rowAnticipo['saldoAnticipo'] == 0 && $rowAnticipo['estadoAnticipo'] == 1)))
				|| ((in_array($rowAnticipo['id_concepto'],array(1,6,7,8)) || in_array($rowAnticipo['estadoAnticipo'],array(0))) && $rowAnticipo['saldoAnticipo'] > 0)) {
					$onClick = sprintf("
					byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = '%s';
					byId('txtNumeroDctoPago').value = '%s';
					byId('txtMontoPago').value = '%s';
					
					xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));",
						$idAnticipo,
						$rowAnticipo['numeroAnticipo'],
						$rowAnticipo['saldoAnticipo']);
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
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaDocumento']))."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function manoObraRepuestosPaquete($idPaquete, $codigoPaquete,  $descripcionPaquete, $precioPaquete){
	$objResponse = new xajaxResponse();
	
	$idOrden = $_GET["id"];
	
	$query = sprintf("SELECT							
							sa_tempario.codigo_tempario,
							sa_tempario.descripcion_tempario,
							sa_modo.descripcion_modo,
							sa_operadores.descripcion_operador,
							det_temp.ut,							
							det_temp.precio,
							det_temp.base_ut_precio,
							det_temp.id_tempario,
							det_temp.id_mecanico,
							det_temp.id_det_orden_tempario,
							(case det_temp.id_modo
								when '1' then round((det_temp.ut * det_temp.precio_tempario_tipo_orden/det_temp.base_ut_precio), 2)
								when '2' then det_temp.precio
								when '3' then det_temp.costo
								when '4' then '-'
							end) AS importe,
							pg_empleado.id_empleado,
							CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_mecanico
						FROM sa_det_orden_tempario det_temp
						LEFT JOIN sa_tempario ON det_temp.id_tempario = sa_tempario.id_tempario
						LEFT JOIN sa_modo ON det_temp.id_modo = sa_modo.id_modo
						LEFT JOIN sa_operadores ON det_temp.operador = sa_operadores.id_operador						
						LEFT JOIN sa_mecanicos ON det_temp.id_mecanico = sa_mecanicos.id_mecanico
						LEFT JOIN pg_empleado ON sa_mecanicos.id_empleado = pg_empleado.id_empleado
						WHERE det_temp.aprobado = 1
						AND det_temp.estado_tempario != 'DEVUELTO'
						AND det_temp.id_orden = %s 
						AND det_temp.id_paquete = %s",
		valTpDato($idOrden,"int"),
		valTpDato($idPaquete,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	$htmlTblIni = "<div class=\"table-scroll\"><table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna noRomper\">";	
		$htmlTh .= "<td >Nombre Mec&aacute;nico</td>";
		$htmlTh .= "<td >C&oacute;digo</td>";
		$htmlTh .= "<td >Descripci&oacute;n</td>";
		$htmlTh .= "<td width=\"7%\">Modo</td>";
		$htmlTh .= "<td width=\"7%\">Operador</td>";
		$htmlTh .= "<td width=\"7%\">UT</td>";
		$htmlTh .= "<td width=\"7%\">Precio</td>";
		$htmlTh .= "<td width=\"7%\">Base UT</td>";
		$htmlTh .= "<td width=\"7%\">Importe</td>";	
	$htmlTh .= "</tr>";

	$contFila = 0;
	while($row = mysql_fetch_assoc($rs)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$codigoNombreMecanico = sprintf("%04s",$row['id_mecanico'])." - ".utf8_encode($row['nombre_mecanico']);
		
		$htmlTb.= "<tr class=\"".$clase."\" >";
			$htmlTb .= "<td title=\"id_mecanico: ".$row['id_mecanico']." id_empleado: ".$row['id_empleado']."\">".$codigoNombreMecanico."</td>";
			$htmlTb .= "<td title=\"id_det_orden_tempario: ".$row['id_det_orden_tempario']." id_tempario: ".$row['id_tempario']."\">".utf8_encode($row['codigo_tempario'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_tempario'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_modo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_operador'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['ut']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['precio']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['base_ut_precio']."</td>";
			$htmlTb .= "<td align=\"right\"><b>".$row['importe']."</b></td>";
		$htmlTb .= "</tr>";
		
		$totalImporte += $row['importe'];
	}
	
	$htmlTblFin .= "</table></div>";
	
	if (!(mysql_num_rows($rs) > 0)){
		$htmlTb .= "<td colspan=\"25\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$htmlTb .= sprintf("<tr class=\"trResaltarTotal\">
			<td colspan=\"8\" class=\"tituloCampo\" align=\"right\">Total:</td>
			<td align=\"right\">%s</td>
		</tr>",
		number_format($totalImporte,2));
	
	$htmlTb .= sprintf("<tr class=\"tituloCampo\"><td colspan=\"25\" align=\"right\" class=\"textoNegrita_10px\">Total Registros: %s &nbsp;</td></tr>",
	$contFila);
	
	$query = sprintf("SELECT 
							iv_articulos.codigo_articulo,
							iv_articulos.descripcion,
							det_art.id_det_orden_articulo,
							det_art.id_articulo,
							det_art.cantidad,
							det_art.precio_unitario,
							det_art.id_articulo_costo,
							det_art.id_articulo_almacen_costo,
							(det_art.cantidad * det_art.precio_unitario) AS total_sin_imp,
							(SELECT GROUP_CONCAT(iva) 
								FROM sa_det_orden_articulo_iva 
								WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = det_art.id_det_orden_articulo) AS iva
						FROM sa_det_orden_articulo det_art
						LEFT JOIN iv_articulos ON det_art.id_articulo = iv_articulos.id_articulo											
						WHERE det_art.aprobado = 1
						AND det_art.estado_articulo != 'DEVUELTO'
						AND det_art.id_orden = %s 
						AND det_art.id_paquete = %s",
		valTpDato($idOrden,"int"),
		valTpDato($idPaquete,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	$htmlTblIni2 = "<div class=\"table-scroll\"><table border=\"0\" width=\"100%\">";
	$htmlTh2 .= "<tr align=\"center\" class=\"tituloColumna noRomper\">";	
		$htmlTh2 .= "<td >C&oacute;digo</td>";
		$htmlTh2 .= "<td >Descripci&oacute;n</td>";
		$htmlTh2 .= "<td width=\"7%\">Lote</td>";
		$htmlTh2 .= "<td width=\"7%\">Cantidad</td>";
		$htmlTh2 .= "<td width=\"7%\">Precio Unit.</td>";
		$htmlTh2 .= "<td width=\"1%\">% Impuesto</td>";
		$htmlTh2 .= "<td width=\"7%\">Total S/I</td>";
		$htmlTh2 .= "<td width=\"7%\">Total</td>";
		$htmlTh2 .= "<td width=\"1%\"></td>";	
	$htmlTh2 .= "</tr>";
		

	$contFila = 0;
	while($row = mysql_fetch_assoc($rs)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$arrayIva = explode(",", $row['iva']);
		$totalImpuesto = 0;
		foreach($arrayIva as $porcentaje){
			$totalImpuesto += round($row['total_sin_imp'] * ($porcentaje/100),2);
		}
		
		$caracterIva = ($row['iva'] != "") ? str_replace(",","% - ",$row['iva'])."%" : "NA";
		$imgVerMovimiento = sprintf("<img title=\"Ver Movimientos\" src=\"../img/iconos/ico_view.png\" class=\"puntero noprint\" onclick=\"xajax_buscarMtoArticulo(%s, '%s', '%s')\" />",
						$row['id_det_orden_articulo'],
						addslashes(utf8_encode($row['codigo_articulo'])),
						addslashes(utf8_encode($row['descripcion'])));
		
		$htmlTb2.= "<tr class=\"".$clase."\" >";
			$htmlTb2 .= "<td title=\"id_det_orden_articulo: ".$row['id_det_orden_articulo']." id_articulo: ".$row['id_articulo']."\">".utf8_encode($row['codigo_articulo'])."</td>";
			$htmlTb2 .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb2 .= "<td align=\"center\" title=\"id_articulo_almacen_costo: ".$row['id_articulo_almacen_costo']."\">".$row['id_articulo_costo']."</td>";
			$htmlTb2 .= "<td align=\"center\">".$row['cantidad']."</td>";
			$htmlTb2 .= "<td align=\"center\">".$row['precio_unitario']."</td>";
			$htmlTb2 .= "<td align=\"center\" class=\"noRomper\">".$caracterIva."</td>";
			$htmlTb2 .= "<td align=\"right\">".$row['total_sin_imp']."</td>";
			$htmlTb2 .= "<td align=\"right\"><b>".($row['total_sin_imp'] + $totalImpuesto)."</b></td>";
			
			$htmlTb2 .= "<td align=\"center\">".$imgVerMovimiento."</td>";
		$htmlTb2 .= "</tr>";
		
		$totalFinalSinImpuesto += $row['total_sin_imp'];
		$totalFinalImpuesto += ($row['total_sin_imp'] + $totalImpuesto);
		
	}
	
	$htmlTblFin2 .= "</table></div>";
	
	if (!(mysql_num_rows($rs) > 0)){
		$htmlTb2 .= "<td colspan=\"25\">";
			$htmlTb2 .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb2 .= "<tr>";
				$htmlTb2 .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb2 .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb2 .= "</tr>";
			$htmlTb2 .= "</table>";
		$htmlTb2 .= "</td>";
	}
	
	$htmlTb2 .= sprintf("<tr class=\"trResaltarTotal\">
					<td colspan=\"6\" class=\"tituloCampo\" align=\"right\">Total:</td>
					<td align=\"right\">%s</td>
					<td align=\"right\">%s</td>
					<td></td>
				</tr>",
		number_format($totalFinalSinImpuesto,2),
		number_format($totalFinalImpuesto,2));
	
	$htmlTb2 .= sprintf("<tr class=\"tituloCampo\"><td colspan=\"25\" align=\"right\" class=\"textoNegrita_10px\">Total Registros: %s &nbsp;</td></tr>",
	$contFila);
	
	$objResponse->assign("tdEncabPaquete","innerHTML",$descripcionPaquete);
	$objResponse->assign("tdListadoTempario","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	$objResponse->assign("tdListadoRepuestos","innerHTML",$htmlTblIni2.$htmlTf2.$htmlTh2.$htmlTb2.$htmlTf2.$htmlTblFin2);	
	$objResponse->script("
	if (byId('divFlotante').style.display == 'none') {
		byId('divFlotante').style.display = '';
		centrarDiv(byId('divFlotante'));
	}");
	
	return $objResponse;
}

//NUEVO O EDITAR, SE USA PARA MOSTRAR/DESACTIVAR SECCIONES BOTONES Y TODO DEPENDIENDO DEL DOCUMENTO
function validarTipoDocumento($tipoDocumento, $idDocumento, $idEmpresa, $accion, $valFormDcto){
	$objResponse = new xajaxResponse();
	
	if ($tipoDocumento == 1) {
		$objResponse->script("
		byId('divFlotante2').style.display='none';
		byId('divFlotante').style.display='none';
		byId('btnGuardar').disabled = '';
		byId('btnCancelar').disabled = '';
		byId('tdEtiqTipoDocumento').innerHTML = 'Neto Presupuesto:';
		byId('lydTipoDocumento').innerHTML = 'Datos del Presupuesto:';
		byId('tdIdDocumento').innerHTML = 'Id Presupuesto:';
		byId('tdFechaVecDoc').style.display='';		
		byId('tdPresupuestosPendientes').style.display = 'none';");
	} else {	
		//dependiendo si se muestra o no el mecanico por parametros generales coloco el display		
		$objResponse->script("
		byId('divFlotante2').style.display='none';
		byId('divFlotante').style.display='none';
		byId('btnGuardar').disabled = '';
		byId('btnCancelar').disabled = '';
		byId('tdEtiqTipoDocumento').innerHTML = 'Total:';
		byId('lydTipoDocumento').innerHTML = 'Datos de la Factura';
		byId('tdIdDocumento').innerHTML = 'Nro. Orden:';
		byId('tdFechaVecDoc').style.display='none';");
	}
			
	if ($accion==1 || $accion==3) {
		if ($accion==1) {			
			if($tipoDocumento==1)
				$objResponse->script("byId('tdTituloPaginaServicios').innerHTML = 'Nuevo Presupuesto';");
			if($tipoDocumento==2)
				$objResponse->script("byId('tdTituloPaginaServicios').innerHTML = 'Nueva Orden de Servicio';");
				
			 $objResponse->script("
				byId('tdRepAprob').style.display = 'none';
				byId('tdPaqAprob').style.display = 'none';
				byId('tdNotaAprob').style.display = 'none';
				byId('tdTempAprob').style.display = 'none';
				byId('tdTotAprob').style.display = 'none';");	
		} else {
			if ($accion==3) {
				$objResponse->script("xajax_cargarDcto('".$idDocumento."', xajax.getFormValues('frmTotalDcto'));	desbloquearForm();");
				
				if ($tipoDocumento==1)
					$objResponse->script("byId('tdTituloPaginaServicios').innerHTML = 'Editar Presupuesto de Venta';");
				if ($tipoDocumento==2) {
					if($_GET['ret'] != 5) {
						$objResponse->script("byId('tdTituloPaginaServicios').innerHTML = 'Editar Orden de Servicio';
							byId('tblLeyendaOrden').style.display = '';");
					} else {
						$objResponse->script("byId('tdTituloPaginaServicios').innerHTML = 'Retrabajo Orden de Servicio';
							desbloquearForm();
							byId('tblMotivoRetrabajo').style.display = '';
							byId('tblLeyendaOrden').style.display = 'none';
							
							byId('imgAgregarDescuento').style.display = 'none';");
					}
				}
				$objResponse->script("
				byId('tdRepAprob').style.display = '';
				byId('tdPaqAprob').style.display = '';
				byId('tdNotaAprob').style.display = '';
				byId('tdTempAprob').style.display = '';
				byId('tdTotAprob').style.display = '';");	
			}
		}	

		$objResponse->script("
		byId('tdInsElimPaq').style.display = ''; 
		byId('tdInsElimRep').style.display = '';
		byId('tdInsElimManoObra').style.display = ''; 
		byId('tdInsElimNota').style.display = '';");	
	} else if($accion == 2) {				
		if ($tipoDocumento==1)
			$objResponse->script("byId('tdTituloPaginaServicios').innerHTML = 'Visualizar Presupuesto de Venta';
			byId('btnGuardar').disabled = true;");
			
		if ($tipoDocumento==2)
			$objResponse->script("byId('tituloPaginaCajaRS').innerHTML = 'Visualizar Orden de Servicio';
				byId('btnGuardar').disabled = true;");
		else
			if ($tipoDocumento==3)
				$objResponse->script("
				if(byId('hddDevolucionFactura').value != '') {
					if(byId('hddDevolucionFactura').value == 1){
						byId('tituloPaginaCajaRS').innerHTML = 'Nota de Credito de Servicios';
					}
					else
						byId('tdTituloPaginaServicios').innerHTML = 'Devolucion Vale Salida';
				} else {
					byId('tituloPaginaCajaRS').innerHTML = 'Pago y Facturacion de Servicios';
				}
										
				byId('tdNroControl').style.display = '';
				byId('tdTxtNroControl').style.display = '';
				byId('tdTipoMov').style.display = '';
				byId('tdLstTipoClave').style.display = '';
				byId('tdClave').style.display = '';
				byId('tdlstClaveMovimiento').style.display = '';");
							
		if ($tipoDocumento==4)
			$objResponse->script("
			byId('tdTituloPaginaServicios').innerHTML = 'Generar Presupuesto';			
			byId('tdNroControl').style.display = 'none';
			byId('tdTxtNroControl').style.display = 'none';
			byId('tdEtiqTipoDocumento').innerHTML = 'Neto Presupuesto:';");
							
		$objResponse->script("
		xajax_cargarDcto('".$idDocumento."',
		xajax.getFormValues('frmTotalDcto'));
		byId('tdNotaAprob').style.display = ''; 
		byId('tdRepAprob').style.display = '';
		byId('tdPaqAprob').style.display = ''; 
		byId('tdTempAprob').style.display = '';
		byId('tdTotAprob').style.display = '';
		byId('tdInsElimPaq').style.display = 'none'; 
		byId('tdInsElimRep').style.display = 'none';
		byId('tdInsElimManoObra').style.display = 'none'; 
		byId('tdInsElimNota').style.display = 'none';
		byId('tdInsElimTot').style.display = 'none';");	
	} else if($accion == 4) {
		if ($tipoDocumento == 1)
			$objResponse->script("
			byId('tdTituloPaginaServicios').innerHTML = 'Aprobar Presupuesto de Venta';");
		else
			$objResponse->script("
			byId('tdTituloPaginaServicios').innerHTML = 'Aprobar Orden de Servicio';");
							
		$objResponse->script("
		xajax_cargarDcto('".$idDocumento."', 
		xajax.getFormValues('frmTotalDcto'));
		desbloquearForm();
		byId('tdInsElimPaq').style.display = 'none'; 
		byId('tdInsElimRep').style.display = 'none';
		byId('tdInsElimManoObra').style.display = 'none'; 
		byId('tdInsElimNota').style.display = 'none';
		byId('tdNotaAprob').style.display = ''; 
		byId('tdRepAprob').style.display = '';
		byId('tdPaqAprob').style.display = ''; 
		byId('tdTempAprob').style.display = '';
		byId('tdTotAprob').style.display = '';");
	}
	
	return $objResponse;
}

//ES EL LISTADO DE SOLICITUD - ALMACEN - UBICACION AL ABRIR EL REPUESTO, EL OJO EN LA SECCION DE REPUESTOS EN LA ORDEN
function verMovimientosArticulo($pageNum = 0, $campOrd = "id_solicitud", $tpOrd = "DESC", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0) {
		$sqlBusq = sprintf(" WHERE det_sol_rep.id_det_orden_articulo = %s",
			valTpDato($valCadBusq[0],"int"));	
	}
	
	$query = sprintf("SELECT
		sol_rep.id_solicitud,
		sol_rep.numero_solicitud,
		vw_iv_art_emp_ubic.descripcion_almacen,
		vw_iv_art_emp_ubic.ubicacion,
		estado_sol.id_estado_solicitud,
		estado_sol.descripcion_estado_solicitud,
		det_sol_rep.id_casilla
	FROM sa_solicitud_repuestos sol_rep
		INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
		INNER JOIN sa_estado_solicitud estado_sol ON (det_sol_rep.id_estado_solicitud = estado_sol.id_estado_solicitud)
		INNER JOIN sa_det_orden_articulo det_orden_art ON (det_sol_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
		LEFT JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (det_orden_art.id_articulo = vw_iv_art_emp_ubic.id_articulo)
			AND (vw_iv_art_emp_ubic.id_casilla = det_sol_rep.id_casilla) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
	
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "20%", $pageNum, "id_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "35%", $pageNum, "descripcion_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Almac&eacute;n");
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "20%", $pageNum, "ubicacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "25%", $pageNum, "descripcion_estado_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"right\" title =\" id_solicitud: ".$row['id_solicitud']."\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_almacen'])."</td>";
			$htmlTb .= "<td align=\"center\" title=\"id_casilla: ".$row['id_casilla']."\">".utf8_encode($row['ubicacion'])."</td>";
			$htmlTb .= "<td align=\"center\" title=\"id_estado_solicitud: ".$row['id_estado_solicitud']."\">".utf8_encode($row['descripcion_estado_solicitud'])."</td>";
		$htmlTb .= "</tr>";
			
		$id_solicitud = $row['id_solicitud'];
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoEstadoMtoArt","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	$objResponse->assign("tdCodigoArticuloMto","innerHTML",utf8_encode(elimCaracter($valCadBusq[1], ";")));			 
	$objResponse->assign("tdFlotanteTitulo3","innerHTML",utf8_encode("Estado Solicitud Articulo"));
	$objResponse->script("
	if (byId('divFlotante3').style.display == 'none') {
		byId('divFlotante3').style.display = '';
		centrarDiv(byId('divFlotante3'));
	}");
	
	return $objResponse;
}



$xajax->register(XAJAX_FUNCTION,"actualizarNumeroControl");
$xajax->register(XAJAX_FUNCTION,"asignarValeRecepcion");
$xajax->register(XAJAX_FUNCTION,"asignarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"buscarMtoArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarNumeroControl");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"calcularPagosDeposito");
$xajax->register(XAJAX_FUNCTION,"calcularTotalDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"contarItemsDcto");
$xajax->register(XAJAX_FUNCTION,"devolverFacturaVenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuentaCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"formDeposito");
$xajax->register(XAJAX_FUNCTION,"guardarFactura");
$xajax->register(XAJAX_FUNCTION,"insertarPago");
$xajax->register(XAJAX_FUNCTION,"insertarPagoDeposito");
$xajax->register(XAJAX_FUNCTION,"listaAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"manoObraRepuestosPaquete");
$xajax->register(XAJAX_FUNCTION,"validarTipoDocumento");
$xajax->register(XAJAX_FUNCTION,"verMovimientosArticulo");

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
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	return array(true, "");
}

/**
 * Busca solo los ivas cargados en la orden ya guardada, por si a futuro cambian los ivas, traer el de la orden
 * tambien se utiliza para todos los historicos o los que no sean nuevos. Vistas de orden.
 * @param int $idOrden Id de la orden a cargar
 * @return Array Es un array de arrays con indice como idIva y formato array(array(idIva,iva,observacion))
 */
function cargarIvasOrden($idOrden){
    $arrayIva = array();
    
    $query = sprintf("SELECT pg_iva.idIva, sa_orden_iva.iva, pg_iva.observacion
                      FROM sa_orden_iva 
                      INNER JOIN pg_iva ON sa_orden_iva.id_iva = pg_iva.idIva
                      WHERE id_orden = %s",
                valTpDato($idOrden, "int"));
    $rs = mysql_query($query);
    if(!$rs) { return die(mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }
    
    while($row = mysql_fetch_assoc($rs)){
        $arrayIva[$row["idIva"]] = array("idIva" => $row["idIva"],
                                         "iva" => $row["iva"],
                                         "observacion" => $row["observacion"]);
    }
    
    return $arrayIva;
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
			$nombreFormaPago,
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
		return array(false, "Debe cerrar la caja del dia: ".date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja'])));
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


//CARGA TODA LA INFORMACION DEL DOCUMENTO, SI ES NUEVO DOCUMENTO NO SE USA
function cargarDcto($idDocumento, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($contRep = 0; $contRep <= strlen($valFormTotalDcto['hddObj']); $contRep++) {
		$caracterRep = substr($valFormTotalDcto['hddObj'], $contRep, 1);
		
		if ($caracterRep != "|" && $caracterRep != "")
			$cadenaRep .= $caracterRep;
		else {
			$arrayObj[] = $cadenaRep;
			$cadenaRep = "";
		}	
	}

	for ($contPaq = 0; $contPaq <= strlen($valFormTotalDcto['hddObjPaquete']); $contPaq++) {
		$caracterPaq = substr($valFormTotalDcto['hddObjPaquete'], $contPaq, 1);
		
		if ($caracterPaq != "|" && $caracterPaq != "")
			$cadenaPaq .= $caracterPaq;
		else {
			$arrayObjPaq[] = $cadenaPaq;
			$cadenaPaq = "";
		}	
	}
	
	for ($contTot = 0; $contTot <= strlen($valFormTotalDcto['hddObjTot']); $contTot++) {
		$caracterTot = substr($valFormTotalDcto['hddObjTot'], $contTot, 1);
		
		if ($caracterTot != "|" && $caracterTot != "")
			$cadenaTot .= $caracterTot;
		else {
			$arrayObjTot[] = $cadenaTot;
			$cadenaTot = "";
		}	
	}
	
	for ($contNota = 0; $contNota <= strlen($valFormTotalDcto['hddObjNota']); $contNota++) {
		$caracterNota = substr($valFormTotalDcto['hddObjNota'], $contNota, 1);
		
		if ($caracterNota != "|" && $caracterNota != "")
			$cadenaNota .= $caracterNota;
		else {
			$arrayObjNota[] = $cadenaNota;
			$cadenaNota = "";
		}
	}

	for ($contTemp = 0; $contTemp <= strlen($valFormTotalDcto['hddObjTempario']); $contTemp++) {
		$caracterTemp = substr($valFormTotalDcto['hddObjTempario'], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp .= $caracterTemp;//caracterTempario
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}
	}
	
	for ($contDcto = 0; $contDcto <= strlen($valFormTotalDcto['hddObjDescuento']); $contDcto++) {
		$caracterDcto = substr($valFormTotalDcto['hddObjDescuento'], $contDcto, 1);
		
		if ($caracterDcto != "|" && $caracterDcto != "")
			$cadenaDcto .= $caracterDcto;
		else {
			$arrayObjDcto[] = $cadenaDcto;
			$cadenaDcto = "";
		}
	}
		
	foreach($arrayObj as $indiceItmRep=>$valorItmRep) {
		$objResponse->script(sprintf("
			fila = document.getElementById('tdItmRep:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmRep));
	}
	
	foreach($arrayObjTot as $indiceItmTot=>$valorItmTot) {
		$objResponse->script(sprintf("
			fila = document.getElementById('tdItmTot:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmTot));
	}
	
	foreach($arrayObjPaq as $indiceItmPaq=>$valorItmPaq) {
		$objResponse->script(sprintf("
			fila = document.getElementById('tdItmPaq:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmPaq));
	}
	
	foreach($arrayObjNota as $indiceItmNota=>$valorItmNota) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmNota:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmNota));
	}
	
	foreach($arrayObjTemp as $indiceItmTemp=>$valorItmTemp) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmTemp:%s');	
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmNota));
	}
	
	foreach($arrayObjDcto as $indiceItmDcto=>$valorItmDcto) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmDcto:%s');	
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmDcto));
	}
		
	if ($valFormTotalDcto['hddTipoDocumento'] == 1) { //PRESUPUESTO
		//solo servicios		
	} else {
		
		$query = sprintf("SELECT * FROM vw_sa_orden WHERE id_orden = %s",
			valTpDato($idDocumento,"int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$numeroOrdenMostrar = $row["numero_orden"];
		$idEmpresaOrden = $row["id_empresa"];
		
		$Result1 = validarAperturaCaja($idEmpresaOrden, date("Y-m-d"));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
		
		$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
			valTpDato($idEmpresaOrden,"int"));
		$rsEmp = mysql_query($queryEmp);
		if (!$rsEmp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowEmp = mysql_fetch_assoc($rsEmp);
		
		$empresaOrden = $rowEmp["nombre_empresa"];
		
		$tablaEnc = "sa_orden";
		$campoIdEnc = "id_orden";
		$tablaDocDetArt = "sa_det_orden_articulo";
		$campoTablaIdDetArt = "id_det_orden_articulo";
		$tablaDocDetTemp = "sa_det_orden_tempario";
		$campoTablaIdDetTemp = "id_det_orden_tempario";

		$tablaDocDetTot = "sa_det_orden_tot";
		$campoTablaIdDetTot = "id_det_orden_tot";
		
		$tablaDocDetNota = "sa_det_orden_notas";
		$campoTablaIdDetNota = "id_det_orden_nota";
		
		$tablaDocDetDescuento = "sa_det_orden_descuento";
		$campoTablaIdDetDescuento = "id_det_orden_descuento";
		
		$campoTablaIdDetNotaRelacOrden = "id_det_orden_nota AS id_det_orden_nota_ref";
		$campoTablaIdDetTotRelacOrden = "id_det_orden_tot AS id_det_orden_tot_ref";
		$campoTablaIdDetTempRelacOrden = "id_det_orden_tempario AS id_det_orden_tempario_ref";
		$campoTablaIdDetArtRelacOrden = "id_det_orden_articulo AS id_det_orden_articulo_ref";
		
		$fechaDocumento = $row['fecha_orden'];
		
		if ($row['porcentaje_descuento'] != NULL || $row['porcentaje_descuento'] != "")
			$descuento = $row['porcentaje_descuento'];
		else
			$descuento = 0;
			
		$idTipoOrden = $row['id_tipo_orden'];
		$estado_orden = $row['nombre_estado'];
               
		$id_iva = $row['idIva'];
		$iva = $row['iva'];
		
		//CONSULTO LA CLAVE DE MOVIMIENTO SEGUN EL TIPO DE LA ORDEN
		$queryTipoDoc = sprintf("SELECT * FROM sa_tipo_orden WHERE id_tipo_orden = %s",
			valTpDato($idTipoOrden,"int"));
		$rsTipoDoc = mysql_query($queryTipoDoc);
		if (!$rsTipoDoc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTipoDoc = mysql_fetch_assoc($rsTipoDoc);
		
		$idClaveMovimiento = $rowTipoDoc["id_clave_movimiento"];
		$idFiltroTipoOrden =  $rowTipoDoc['id_filtro_orden'];
		
		//CONSULTO EL TIPO DE DOCUMENTO QUE GENERA Y SI ES DE CONTADO
		$queryDocGenera = sprintf("SELECT * FROM pg_clave_movimiento
		WHERE id_clave_movimiento = %s
			AND documento_genera = %s
			AND pago_contado = %s",
			valTpDato($idClaveMovimiento,"int"),
			valTpDato(1,"int"), // 1 = FACTURA
			valTpDato(1,"int")); // 0 = NO ; 1 = SI
		$rsDocGenera = mysql_query($queryDocGenera);
		if (!$rsDocGenera) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowDocGenera = mysql_fetch_assoc($rsDocGenera);

		if (mysql_num_rows($rsDocGenera) > 0 && !isset($_GET['dev'])) {//SI TIPO DE PAGO ES CONTADO Y NO ES UNA DEVOLUCION
			$objResponse->script("byId('trFormaDePago').style.display = '';
								byId('trDesgloseDePagos').style.display = '';");
		}else{//SI ES A CREDITO O ES UNA DEVOLUCION
			$objResponse->script("byId('trFormaDePago').style.display = 'none';
								byId('trDesgloseDePagos').style.display = 'none';");
		}
	}
		
	$itemsNoChecados = 0;
	
	if ($_GET['acc_ret'] == 2 || $_GET['ret'] != 5) { //RETRABAJO
		$objResponse->script(sprintf("xajax_asignarValeRecepcion(%s, %s, xajax.getFormValues('frmTotalDcto'))", $row['id_recepcion'], $_GET['acc']));
	} else {
		$objResponse->script("xajax_calcularTotalDcto();");
	}
	
	if($valFormTotalDcto['hddAccionTipoDocumento'] == 2)
		$check_disabled = "disabled=\"disabled\"";
	else
		$check_disabled = "";
		
	if($_GET['dev'] == 1)
		$condicionMostrarArticulosFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetArt);
	else
		$condicionMostrarArticulosFacturados = "";
		
	if($_GET['acc_ret'] != 1) { //CUANDO SE GENERA LA ORDEN RETRABAJO
		$queryRepuestosGenerales = sprintf("SELECT
			%s.%s,                        
			%s.cantidad,
			%s.id_articulo_costo,
			%s.id_articulo_almacen_costo,
			%s.id_precio,
			%s.precio_unitario,
			(SELECT GROUP_CONCAT(id_iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.%s) as id_iva,
			(SELECT GROUP_CONCAT(iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.%s) as iva,
			%s.id_articulo,
			%s.%s,
			%s.aprobado,
			%s.costo,
			%s.porcentaje_pmu,
			%s.base_pmu,
			%s.pmu_unitario,
			iv_tipos_articulos.descripcion AS descripcion_tipo,
			iv_articulos.descripcion AS descripcion_articulo,
			iv_secciones.descripcion AS descripcion_seccion,
			iv_subsecciones.id_subseccion,
			iv_articulos.codigo_articulo
		FROM iv_articulos
			INNER JOIN %s ON (iv_articulos.id_articulo = %s.id_articulo)
			INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
			INNER JOIN iv_tipos_articulos ON (iv_articulos.id_tipo_articulo = iv_tipos_articulos.id_tipo_articulo)
			INNER JOIN iv_secciones ON (iv_subsecciones.id_seccion = iv_secciones.id_seccion)
		WHERE %s.%s = %s
			AND %s.id_paquete IS NULL %s
			AND %s.estado_articulo <> 'DEVUELTO'",
			$tablaDocDetArt, $campoTablaIdDetArtRelacOrden,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt, $campoTablaIdDetArt,//id ivas
			$tablaDocDetArt, $campoTablaIdDetArt,//porc ivas
			$tablaDocDetArt,
			$tablaDocDetArt, $campoTablaIdDetArt,
			$tablaDocDetArt, //costo no estaba y se lo agregue
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
			$tablaDocDetArt, $condicionMostrarArticulosFacturados,
			$tablaDocDetArt);	
		$rsDetRep = mysql_query($queryRepuestosGenerales);
		
		if (!$rsDetRep) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$sigValor = 1;
		$arrayObj = NULL;
		
		$readonly_check_ppal_list_repuesto = 0;
		$tieneRptoSinSolicitud = 0;
		while ($rowDetRep = mysql_fetch_assoc($rsDetRep)) {
			$repuestosTomadosEnSolicitud2 = 0;
			
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			$caracterIva = ($rowDetRep['id_iva'] != "") ? str_replace(",","% - ",$rowDetRep['iva'])."%" : "NA";
			
                        
			if ($_GET['cons'] == 1) { // FACTURACION
				// SI ESTAN EN SOLICITUD Y APROBADO
				$queryCont = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
				WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s
					AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3 OR sa_det_solicitud_repuestos.id_estado_solicitud = 5) ", 
				$rowDetRep['id_det_orden_articulo_ref']);
				$rsCont = mysql_query($queryCont);
				$rowCont = mysql_fetch_assoc($rsCont);
                                
                                //OJO SE USA TAMBIEN PARA CALCULAR TOTALES
				if (!$rsCont)
					return $objResponse->alert("Error cargarDcto \n".mysql_error().$rsCont."\n Linea: ".__LINE__);
				else {
					if ($rowCont['nro_rpto_desp'] != NULL || $rowCont['nro_rpto_desp'] != "")
						$cantidad_art = $rowCont['nro_rpto_desp'];
					else
						$cantidad_art = 0;
				}
				
				$queryContTotal = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
				WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s", 
					$rowDetRep['id_det_orden_articulo_ref']);
				$rsContTotal = mysql_query($queryContTotal);
				$rowContTotal = mysql_fetch_assoc($rsContTotal);
				
				$cantidad_art_total = $rowContTotal['nro_rpto_desp'];
			} else {
				$cantidad_art_total = $rowDetRep['cantidad'];
				$cantidad_art = $rowDetRep['cantidad'];
			}	
			
			$query = sprintf("SELECT *
			FROM sa_det_orden_articulo
				INNER JOIN sa_det_solicitud_repuestos ON (sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
				INNER JOIN sa_solicitud_repuestos ON (sa_det_solicitud_repuestos.id_solicitud = sa_solicitud_repuestos.id_solicitud)
			WHERE sa_det_orden_articulo.id_det_orden_articulo = %s
				AND sa_det_solicitud_repuestos.id_estado_solicitud IS NOT NULL
				AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO'",
				valTpDato($rowDetRep['id_det_orden_articulo_ref'],"int"));//AND sa_solicitud_repuestos.estado_solicitud != 0
			$rs = mysql_query($query);
                        
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
					
			if ($row['id_det_solicitud_repuesto'] != '')
				$repuestosTomadosEnSolicitud2 = 1;
				
			if ($rowDetRep['aprobado'] == 1) {
				//si hay por lo menos uno aprobado que desabilite directamente el check principal, ya q los valores tienden a reemplazarlos
				$checkedArt = "checked=\"checked\"";
				$value_checkedArt = 1;

				if($repuestosTomadosEnSolicitud2 == 1)
					$disabledArt = "disabled=\"disabled\"";
				
				if ($valFormTotalDcto['hddAccionTipoDocumento']!=4) {
					$readonly_check_ppal_list_repuesto = 1;
					$displayArt = "style=\"display:none;\"";
					$imgCheckDisabledArt = sprintf("<input id=\"cbxItmAprobDisabled\" name=\"cbxItmAprobDisabled[]\" disabled=\"disabled\" type=\"checkbox\" value=\"%s\" checked=\"checked\" />", $sigValor);
				} else {
					if($repuestosTomadosEnSolicitud2 == 1) {
						$readonly_check_ppal_list_repuesto = 1;
						$displayArt = "style=\"display:none;\"";
						$imgCheckDisabledArt = sprintf("<input id=\"cbxItmAprobDisabled\" name=\"cbxItmAprobDisabled[]\" disabled=\"disabled\" type=\"checkbox\" value=\"%s\" checked=\"checked\" />", $sigValor);
					} else {
						$displayArt = "";
						$imgCheckDisabledArt = "";
						$tieneRptoSinSolicitud = 1;
					}
				}
			} else {
				$itemsNoChecados = 1;
				$checkedArt = " ";
				$value_checkedArt = 0;
				$disabledArt = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayArt = "style=\"display:none;\"";
					$imgCheckDisabledArt = "<input id=\"cbxItmAprobDisabledNoChecked\" name=\"cbxItmAprobDisabledNoChecked[]\" disabled=\"disabled\" type=\"checkbox\"/>";
					$readonly_check_ppal_list_repuesto = 1;
				} else {
					$displayArt = "";
					$imgCheckDisabledArt = "";
					$tieneRptoSinSolicitud = 1;
				}
			}

			$solicitudRep = "";

			$sqlSolicitudRep= "SELECT * FROM sa_det_solicitud_repuestos
			WHERE id_det_orden_articulo = ".$rowDetRep['id_det_orden_articulo_ref']."
				AND id_estado_solicitud IN (1,2)";
			$rsSolicitudRep= mysql_query($sqlSolicitudRep);
			if (!$rsSolicitudRep) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowSolicitudRep= mysql_fetch_assoc($rsSolicitudRep);

			if ($rowSolicitudRep) {
				$solicitudRep= "<span style=\"color:red\"> (S) </span>";
			}
                        
			//CALCULOS MULTIPLES IVAS
			$arrayIdIvasRep = explode(",",$rowDetRep['id_iva']);
			$arrayPorcIvasRep = explode(",",$rowDetRep['iva']);
			$montoMultipleIva = 0;
			foreach($arrayIdIvasRep as $key => $idIvasRep){
				$montoMultipleIva += ($cantidad_art * $rowDetRep['precio_unitario'] * $arrayPorcIvasRep[$key] / 100);
			}
			$montoMultipleIva = $montoMultipleIva + $cantidad_art * $rowDetRep['precio_unitario'];

			$objResponse->script(sprintf("$('#trItmPie').before('".
				"<tr id=\"trItm:%s\" class=\"textoGris_11px %s\" title=\"trItm:%s\">".
					"<td align=\"right\" id=\"tdItmRep:%s\" class=\"color_column_insertar_eliminar_item\">".
						"<input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\" %s /></td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\" class=\"noRomper\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"right\"><b>%s</b></td>".
					"<td align=\"center\">".
						"<img id=\"imgVerMtoArticulo:%s\" src=\"../img/iconos/ico_view.png\" class=\"puntero noprint\"/>".						
						"<input type=\"hidden\" id=\"hddIdPedDet%s\" name=\"hddIdPedDet%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArt%s\" name=\"hddIdArt%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArtCosto%s\" name=\"hddIdArtCosto%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArtAlmacenCosto%s\" name=\"hddIdArtAlmacenCosto%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddCantArt%s\" name=\"hddCantArt%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddPorcentajePmu%s\" name=\"hddPorcentajePmu%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddBasePmu%s\" name=\"hddBasePmu%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddMontoPmu%s\" name=\"hddMontoPmu%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdPrecioArt%s\" name=\"hddIdPrecioArt%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddPrecioArt%s\" name=\"hddPrecioArt%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddCostoArt%s\" name=\"hddCostoArt%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdIvaArt%s\" name=\"hddIdIvaArt%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIvaArt%s\" name=\"hddIvaArt%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddTotalArt%s\" name=\"hddTotalArt%s\" value=\"%s\"/></td>".
					"<td align=\"center\" id=\"tdItmRepAprob:%s\" class=\"color_column_aprobacion_item\">".
						"<input id=\"cbxItmAprob\" name=\"cbxItmAprob[]\" type=\"checkbox\" value=\"%s\" %s onclick=\"xajax_calcularTotalDcto();\" %s /> %s".
						"<input type=\"hidden\" id=\"hddValorCheckAprobRpto%s\" name=\"hddValorCheckAprobRpto%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddRptoEnSolicitud%s\" name=\"hddRptoEnSolicitud%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddRptoTomadoSolicitud2%s\" name=\"hddRptoTomadoSolicitud2%s\" value=\"%s\"/></td>".
				"</tr>');".
			
			"byId('imgVerMtoArticulo:%s').onclick=function(){ 
				xajax_buscarMtoArticulo('%s', '%s', '%s'); 
			}",
			$sigValor, $clase, $sigValor,
				$sigValor, $sigValor, $disabledArt,
				elimCaracter($rowDetRep['codigo_articulo'], ";"),
				utf8_encode(addslashes($solicitudRep.$rowDetRep['descripcion_articulo'])),
				$rowDetRep['id_articulo_costo'],
				$cantidad_art_total,
				//$sigValor, $cantidad_art, // Articulos Despachados
				number_format($rowDetRep['pmu_unitario'],2,".",","),
				number_format($cantidad_art * $rowDetRep['pmu_unitario'],2,".",","),
				number_format($rowDetRep['precio_unitario'],2,".",","),
				$caracterIva,
				//number_format(($cantidad_art * $rowDetRep['precio_unitario']),2,".",","),//anterior ahora:
				//agregado iva nuevo gregor
				number_format(($cantidad_art * $rowDetRep['precio_unitario']),2,".",","),
				number_format($montoMultipleIva + ($cantidad_art * $rowDetRep['pmu_unitario']),2,".",","),
				
				$sigValor, 
					$sigValor, $sigValor, $rowDetRep['id_det_orden_articulo_ref'],
					$sigValor, $sigValor, $rowDetRep['id_articulo'],
					$sigValor, $sigValor, $rowDetRep['id_articulo_costo'],
					$sigValor, $sigValor, $rowDetRep['id_articulo_almacen_costo'],
					$sigValor, $sigValor, $cantidad_art,
					$sigValor, $sigValor, $rowDetRep['porcentaje_pmu'],
					$sigValor, $sigValor, $rowDetRep['base_pmu'],
					$sigValor, $sigValor, $rowDetRep['pmu_unitario'],
					$sigValor, $sigValor, $rowDetRep['id_precio'],
					$sigValor, $sigValor, $rowDetRep['precio_unitario'],
					$sigValor, $sigValor, $rowDetRep['costo'],					
					$sigValor, $sigValor, $rowDetRep['id_iva'],//multiple separado por coma
					$sigValor, $sigValor, $rowDetRep['iva'],//multiple separado por coma
					$sigValor, $sigValor, ($cantidad_art*$rowDetRep['precio_unitario']),
				$sigValor, $sigValor, $checkedArt, $displayArt, $imgCheckDisabledArt,
					$sigValor, $sigValor, $value_checkedArt,
					$sigValor, $sigValor, $row['id_det_solicitud_repuesto'],
					$sigValor, $sigValor, $repuestosTomadosEnSolicitud2,
					
				$sigValor,
					$rowDetRep['id_det_orden_articulo_ref'], $rowDetRep['codigo_articulo'], utf8_encode(addslashes($rowDetRep['descripcion_articulo']))));
			
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1)
				$objResponse->script(sprintf("
				byId('tdInsElimRep').style.display = '';
				byId('tdItmRep:%s').style.display = '';
				byId('tdItmRepAprob:%s').style.display='none';",
					$sigValor,
					$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==2 )
				$objResponse->script(sprintf("
				byId('tdInsElimRep').style.display = 'none';
				byId('tdItmRep:%s').style.display = 'none';
				byId('tdItmRepAprob:%s').style.display='';
				byId('cbxItmAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
				$objResponse->script(sprintf("
				byId('tdInsElimRep').style.display = 'none'; 
				byId('tdItmRep:%s').style.display = 'none'; 
				byId('tdItmRepAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==3)
				$objResponse->script(sprintf("
				byId('tdInsElimRep').style.display = '';
				byId('tdItmRep:%s').style.display = '';
				byId('tdItmRepAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
			
//			if($_GET['cons'] == 1) {
//				$objResponse->script(sprintf("byId('tdCantidadArtDesp%s').style.display = '';",
//					$sigValor));
//			} else {
//				$objResponse->script(sprintf("byId('tdCantidadArtDesp%s').style.display = 'none';",
//					$sigValor));
//			}
			
			$arrayObj[] = $sigValor;
			$sigValor++;
		}
		
//		if ($_GET['cons'] == 1) {
//			$objResponse->script(sprintf("byId('tdDespachado').style.display = '';"));
//		} else {
//			$objResponse->script(sprintf("byId('tdDespachado').style.display = 'none';"));
//		}
			
		if ($repuestosTomadosEnSolicitud2 == 1) {
			$objResponse->assign("tdInsElimRep","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
		
		
		if($readonly_check_ppal_list_repuesto == 1) {
			$objResponse->script("byId('cbxItmAprob').style.display = 'none';");
			$objResponse->assign("tdRepAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			//$objResponse->assign("tdInsElimRep","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
				
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($sigValor == 1) {
				$objResponse->script("
				byId('frmListaArticulo').style.display='none';
				byId('tblListaArticulo').style.display='none';");
			}
			
			/*if($tieneRptoSinSolicitud == 1)
				$objResponse->script("
					byId('lstMecSolRptoBusq').disabled = false;");*/
		} else if ($valFormTotalDcto['hddTipoDocumento'] == 1) { //PRESUPUESTO
			if ($sigValor == 1) {
				$objResponse->script("
				byId('frmListaArticulo').style.display='none';
				byId('tblListaArticulo').style.display='none';");
			}
		}
		
		if ($_GET['cons'] == 1) { // CONTROL DE ARTICULOS DESPACHADOS (LOS QUE SE VAN A FACTURAR)
			$adnlQuery = sprintf("SELECT 
				SUM(sa_det_orden_articulo.precio_unitario) AS TOTAL
			FROM sa_paquetes
				INNER JOIN sa_det_orden_articulo ON (sa_paquetes.id_paquete = sa_det_orden_articulo.id_paquete)
				INNER JOIN sa_det_solicitud_repuestos ON (sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
			WHERE sa_det_orden_articulo.id_orden = %s
				AND sa_det_orden_articulo.id_paquete = idPaq
				AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO'
				AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
					OR sa_det_solicitud_repuestos.id_estado_solicitud = 5) ",
				valTpDato($idDocumento,"int"));
		} else {
			$adnlQuery = sprintf("SELECT 
				SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_articulo <> 'DEVUELTO'", 
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetArt, $tablaDocDetArt, $tablaDocDetArt);
		}
				
		$queryDetPaq = sprintf("SELECT 
			sa_paquetes.id_paquete AS idPaq,
			sa_paquetes.codigo_paquete,
			sa_paquetes.descripcion_paquete,
			
			(CASE (SELECT id_estado_orden FROM %s WHERE %s = %s LIMIT 1)
				WHEN 13 THEN
					(SELECT SUM(%s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
						INNER JOIN sa_det_solicitud_repuestos ON (%s.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) = 0
						AND %s.estado_articulo <> 'DEVUELTO'
						AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
							OR sa_det_solicitud_repuestos.id_estado_solicitud = 5))
				ELSE
					(SELECT SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) = 0
						AND %s.estado_articulo <> 'DEVUELTO')
			END) AS total_art_exento,
			
			(CASE (SELECT id_estado_orden FROM %s WHERE %s = %s LIMIT 1)
				WHEN 13 THEN
					(SELECT SUM(%s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
						INNER JOIN sa_det_solicitud_repuestos ON (%s.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) > 0
						AND %s.estado_articulo <> 'DEVUELTO'
						AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
							OR sa_det_solicitud_repuestos.id_estado_solicitud = 5))
				ELSE
					(SELECT SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) > 0
						AND %s.estado_articulo <> 'DEVUELTO')
			END) AS total_art_con_iva,
				
			(SELECT SUM(
				CASE %s.id_modo
					when '1' then %s.ut * %s.precio_tempario_tipo_orden/ %s.base_ut_precio 
					when '2' then %s.precio
					when '3' then %s.costo end) AS total
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_tempario <> 'DEVUELTO') AS total_tmp,
			(%s) AS total_rpto,
			
			(IFNULL((SELECT SUM(
				CASE %s.id_modo
					when '1' then %s.ut * %s.precio_tempario_tipo_orden/ %s.base_ut_precio 
					when '2' then %s.precio
					when '3' then %s.costo
				END) AS total
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_tempario <> 'DEVUELTO'),0)
			+
			IFNULL((%s),0)) AS precio_paquete 
		FROM sa_paquetes
			LEFT JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			LEFT JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			#LEFT JOIN %s ON (%s.%s = %s.%s) OR (%s.%s = %s.%s) #donde va OR antes iba AND, cambiado para que tome solo 1, cambiado 3inner por left ELIMINADO
		#WHERE %s.%s = %s #ELIMINADO
		WHERE sa_paquetes.id_paquete IN (
			SELECT id_paquete FROM %s WHERE %s.%s = %s AND %s.id_paquete IS NOT NULL
			UNION
			SELECT id_paquete FROM %s WHERE %s.%s = %s AND %s.id_paquete IS NOT NULL
			)
		GROUP BY sa_paquetes.id_paquete",
		//select id_estado_orden
		$tablaEnc, $campoIdEnc, valTpDato($idDocumento,"int"),
		
		
			$tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
		 		$tablaDocDetArt, 
				$tablaDocDetArt,
				$tablaDocDetArt,
				
			$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
		 		$tablaDocDetArt, 
				$tablaDocDetArt,
				$tablaDocDetArt,
		
		//select id_estado_orden
		$tablaEnc, $campoIdEnc, valTpDato($idDocumento,"int"),
		
				
			$tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetArt,
				$tablaDocDetArt,
				$tablaDocDetArt,
			
			$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetArt,
				$tablaDocDetArt,
				$tablaDocDetArt,
			
			
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp, $tablaDocDetTemp,
			$adnlQuery,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp,  $tablaDocDetTemp,
			$adnlQuery,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaEnc, $tablaDocDetTemp, $campoIdEnc, $tablaEnc, $campoIdEnc,
			$tablaEnc, $campoIdEnc, $tablaDocDetArt, $campoIdEnc,
			$tablaEnc, $campoIdEnc, valTpDato($idDocumento,"int"),
			//aqui nuevo gregor UNION 
			$tablaDocDetArt, $tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetArt,
			$tablaDocDetTemp, $tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp
			);
			
		$rsDetPaq = mysql_query($queryDetPaq);
		if (!$rsDetPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryDetPaq);
		
		$sigValor = 1;
		$arrayObjPaq = NULL;
		
		$readonly_check_ppal_list_paquete = 0;
		$tieneRptoSinSolicitudPaq = 0;
		while ($rowDetPaq = mysql_fetch_assoc($rsDetPaq)) {//de cada paquete se consulta sus items
			
			//$objResponse->alert($rowDetPaq['precio_paquete']);
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			$sqlManoObraPaq = sprintf("SELECT 
				%s.id_tempario,
				%s.aprobado,
				%s.id_det_orden_tempario
			FROM %s
			WHERE %s.%s = %s
				AND %s.id_paquete = %s
				AND %s.estado_tempario <> 'DEVUELTO'",
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp, valTpDato($rowDetPaq['idPaq'],"int"),  $tablaDocDetTemp);
			$rsManoObraPaq = mysql_query($sqlManoObraPaq);
			if (!$rsManoObraPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					
			//VALIDACION DE APROBADO POR MANO DE OBRA. DE ESTO DEPENDE SI EL PAQUETE ES O NO APROBADO
			
			// SI LA ACCION ES APROBAR QUE NO LO MUESTRE ASI
			$sqlNroManoObraPaq = sprintf("SELECT 
				COUNT(*) AS numeroMo,
				%s.id_det_orden_tempario
			FROM %s
			WHERE %s.%s = %s
				AND %s.id_paquete = %s
				AND %s.aprobado = 1
				AND %s.estado_tempario <> 'DEVUELTO'
			GROUP BY id_det_orden_tempario",
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetTemp, valTpDato($rowDetPaq['idPaq'],"int"),
				$tablaDocDetTemp,
				$tablaDocDetTemp);				
			$rsNroManoObraPaq = mysql_query($sqlNroManoObraPaq);
			if (!$rsNroManoObraPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowNroManoObraPaq = mysql_fetch_array($rsNroManoObraPaq);
							
			// CUANDO LO VAYA A GUARDAR NO LO PUEDE VOLVER A TOMAR PUESTO QUE YA ESTA GUARDADO, ESTA MISMA CONSULTA COLOCARLA EN GUARDAR 
			
			$repuestosTomadosEnSolicitud = 0;
				
			$sqlRepuestoPaq = sprintf("SELECT 
				%s.id_articulo,
				%s.%s
			FROM %s
			WHERE %s.%s = %s
				AND %s.id_paquete = %s
				AND estado_articulo <> 'DEVUELTO'",
				$tablaDocDetArt,
				$tablaDocDetArt, $campoTablaIdDetArtRelacOrden,
				$tablaDocDetArt, 
				$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetArt, valTpDato($rowDetPaq['idPaq'],"int"));
			$rsRepuestoPaq = mysql_query($sqlRepuestoPaq);
			if (!$rsRepuestoPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						
			$tieneRepuestos = mysql_num_rows($rsRepuestoPaq);
						
			$cadenaRepPaq = "";
			while ($valorRepuestoPaq = mysql_fetch_array($rsRepuestoPaq)) {
				$cadenaRepPaq .= "|".$valorRepuestoPaq['id_articulo'];
				
				$query = sprintf("SELECT det_sol_rep.id_det_solicitud_repuesto
				FROM sa_det_orden_articulo det_orden_art
					INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
					INNER JOIN sa_solicitud_repuestos sol_rep ON (det_sol_rep.id_solicitud = sol_rep.id_solicitud)
				WHERE det_orden_art.id_det_orden_articulo = %s
					AND sol_rep.estado_solicitud != 4;",
					valTpDato($valorRepuestoPaq['id_det_orden_articulo_ref'],"int"));
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$row = mysql_fetch_assoc($rs);
													
				if ($row['id_det_solicitud_repuesto'] != '') {
					$repuestosTomadosEnSolicitud = 1;
				}
			}
										
			if($rowNroManoObraPaq['numeroMo'] > 0 || $tieneRepuestos > 0) {
				$checkedPaq = "checked=\"checked\"";  // o se coloca vacio?
				$value_checkedPaq = 1;
				if($repuestosTomadosEnSolicitud == 1){
					$disabledPaq = "disabled=\"disabled\"";   
				}else{//else sino el while toma el ultimo establecido
                                    $disabledPaq = "";
                                }
						
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_paquete = 1;
					$displayPaq = "style=\"display:none;\"";
					$imgCheckDisabledPaq = sprintf("<input id=\"cbxItmAprobDisabled%s\" name=\"cbxItmAprobDisabled%s\" disabled=\"disabled\" type=\"checkbox\" value=\"%s\" checked=\"checked\" />", $sigValor, $sigValor, $sigValor);
				} else {
					if($repuestosTomadosEnSolicitud == 1) {
						$readonly_check_ppal_list_paquete = 1;
						$displayPaq = "style='display:none;'";
						$imgCheckDisabledPaq = sprintf("<input id=\"cbxItmAprobDisabled\" name=\"cbxItmAprobDisabled[]\" disabled=\"disabled\" type=\"checkbox\" value=\"%s\" checked=\"checked\" />", $sigValor);
					} else {
						$displayPaq = "";
						$imgCheckDisabledPaq = "";
						$tieneRptoSinSolicitudPaq = 1;
					}
				}
			} else {
				//$itemsNoChecados = 1;//error sino tiene repuestos ni mano de obra cae aqui
				$checkedPaq = " ";
				$value_checkedPaq = 0;
				
				if($repuestosTomadosEnSolicitud == 1){
					$disabledPaq = "disabled=\"disabled\"";   
				}else{//else sino el while toma el ultimo establecido
                                    $disabledPaq = "";
                                }
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayPaq = "style='display:none;'";
					$imgCheckDisabledPaq = "<input id=\"cbxItmAprobDisabledNoChecked\" name=\"cbxItmAprobDisabledNoChecked[]\" disabled=\"disabled\" type=\"checkbox\"/>";
					$readonly_check_ppal_list_paquete = 1;
				} else {
					$displayPaq = "";
					$imgCheckDisabledPaq = "";
					$tieneRptoSinSolicitudPaq = 1;
				}
			}
						
			// HACER CONSULTA SI MUESTRA O NO EL PAQUETE DESAPROBADO
			$cadenaManoPaq = "";
			while ($valorManoObraPaq = mysql_fetch_array($rsManoObraPaq)) {
				$cadenaManoPaq .= "|".$valorManoObraPaq['id_tempario'];
				
				//ivas agrupados por iva para paquete
				$sqlIvas = sprintf("SELECT id_iva, iva
									FROM sa_det_orden_tempario_iva
									WHERE id_det_orden_tempario = %s",
							valTpDato($valorManoObraPaq['id_det_orden_tempario'],"int"));
				$rsIvas = mysql_query($sqlIvas);
				if (!$rsIvas) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlIvas);
				
				while($rowIva = mysql_fetch_assoc($rsIvas)){
					$arrayIvasTemparioPaquete[$rowIva['id_iva']] = $rowIva['iva'];
				}
			}
			
			$idIvasTemparioPaquete = implode(",", array_keys($arrayIvasTemparioPaquete));
			$porcentajeIvasTemparioPaquete = implode(",", $arrayIvasTemparioPaquete);
						                        
			//verifico si tiene repuestos en solicitud para deshabilitar el eliminado
			if($repuestosTomadosEnSolicitud == 1){
				$bloqueaEliminacionRepuestos = 1;
			}else{
				$bloqueaEliminacionRepuestos = 0;
			}
					
			if ($value_checkedPaq == 1) {
                            
				$sqlIvasIdPorcPaq = sprintf("SELECT 
												GROUP_CONCAT(sa_det_orden_articulo_iva.id_iva) AS id_iva_paquete, 
												GROUP_CONCAT(sa_det_orden_articulo_iva.iva) AS porc_iva_paquete,
											GROUP_CONCAT(sa_det_orden_articulo.precio_unitario*sa_det_orden_articulo.cantidad) AS base_imponible_paquete
											FROM sa_det_orden_articulo
											INNER JOIN sa_det_orden_articulo_iva ON sa_det_orden_articulo.id_det_orden_articulo = sa_det_orden_articulo_iva.id_det_orden_articulo
											WHERE id_orden = %s AND id_paquete = %s AND estado_articulo != 'DEVUELTO'",
									valTpDato($idDocumento,"int"),
									valTpDato($rowDetPaq['idPaq'],"int"));
				$rsIvasIdPorcPaq = mysql_query($sqlIvasIdPorcPaq);
				if (!$rsIvasIdPorcPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlIvasIdPorcPaq);
				
				$rowIvasIdPorcPaq = mysql_fetch_assoc($rsIvasIdPorcPaq);
				
				$objResponse->script(sprintf("$('#trm_pie_paquete').before('".
					"<tr id=\"trItmPaq:%s\" class=\"textoGris_11px %s\" title=\"trItmPaq:%s\" >".
						"<td align=\"center\" id=\"tdItmPaq:%s\" class=\"color_column_insertar_eliminar_item\">".
							"<input id=\"cbxItmPaq\" name=\"cbxItmPaq[]\" type=\"checkbox\" value=\"%s\" %s /></td>".
						"<td align=\"left\">%s</td>".
						"<td align=\"left\">%s</td>".
						"<td align=\"right\">%s</td>".
						"<td align=\"center\">%s".
							"<img class=\"puntero noprint\" id=\"img:%s\" src=\"../img/iconos/ico_view.png\" title=\"Paquete:%s\"/>".
							"<input type=\"hidden\" id=\"hddIdPedDetPaq%s\" name=\"hddIdPedDetPaq%s\" readonly=\"readonly\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIdPaq%s\" name=\"hddIdPaq%s\" readonly=\"readonly\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddRepPaqAsig%s\" name=\"hddRepPaqAsig%s\" readonly=\"readonly\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddTempPaqAsig%s\" name=\"hddTempPaqAsig%s\" readonly=\"readonly\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddRepPaqAsigEdit%s\" name=\"hddRepPaqAsigEdit%s\" readonly=\"readonly\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddTempPaqAsigEdit%s\" name=\"hddTempPaqAsigEdit%s\" readonly=\"readonly\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddPrecPaq%s\" name=\"hddPrecPaq%s\" readonly=\"readonly\" value=\"%s\"/></td>".
						"<td align=\"center\" id=\"tdItmPaqAprob:%s\" class=\"color_column_aprobacion_item\">%s".
							"<input type=\"checkbox\" id=\"cbxItmPaqAprob%s\" name=\"cbxItmPaqAprob[]\" value=\"%s\" %s %s onchange=\"xajax_validarSiTieneAlmacenAsignado(%s, %s, %s, this.id)\"/>".
							"<input type=\"hidden\" id=\"hddValorCheckAprobPaq%s\" name=\"hddValorCheckAprobPaq%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddRptoPaqEnSolicitud%s\" name=\"hddRptoPaqEnSolicitud%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddTotalRptoPqte%s\" name=\"hddTotalRptoPqte%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddTotalTempPqte%s\" name=\"hddTotalTempPqte%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIdIvasTemparioPaquete%s\" name=\"hddIdIvasTemparioPaquete%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddPorcentajesIvasTemparioPaquete%s\" name=\"hddPorcentajesIvasTemparioPaquete%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddRptoTomadoSolicitud%s\" name=\"hddRptoTomadoSolicitud%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddTotalExentoRptoPqte%s\" name=\"hddTotalExentoRptoPqte%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIdIvasRepuestoPaquete%s\" name=\"hddIdIvasRepuestoPaquete%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIvasRepuestoPaquete%s\" name=\"hddIvasRepuestoPaquete%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddPorcentajesIvasRepuestoPaquete%s\" name=\"hddPorcentajesIvasRepuestoPaquete%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddTotalConIvaRptoPqte%s\" name=\"hddTotalConIvaRptoPqte%s\" value=\"%s\"/></td>".
				"</tr>');".
				
				"byId('img:%s').onclick = function() {
					byId('tblGeneralPaquetes').style.display = '';
					xajax_manoObraRepuestosPaquete('%s','%s','%s','%s');
				}",
				$sigValor, $clase, $sigValor,
					$sigValor, $sigValor, $disabledPaq,
					utf8_encode(addslashes($rowDetPaq['codigo_paquete'])),
					utf8_encode(addslashes($rowDetPaq['descripcion_paquete'])),
					number_format($rowDetPaq['precio_paquete'],2,".",","),
					$estado_paquete,
						$sigValor, $rowDetPaq['idPaq'],
						$sigValor, $sigValor, $sigValor,
						$sigValor, $sigValor, $rowDetPaq['idPaq'],
						$sigValor, $sigValor, $cadenaRepPaq,
						$sigValor, $sigValor, $cadenaManoPaq,
						$rowDetPaq['idPaq'], $rowDetPaq['idPaq'], $cadenaRepPaq,
						$rowDetPaq['idPaq'], $rowDetPaq['idPaq'], $cadenaManoPaq,
						$sigValor, $sigValor, round($rowDetPaq['precio_paquete'], 2),
					$sigValor, $imgCheckDisabledPaq,
						$sigValor, $sigValor, $checkedPaq, $displayPaq, $sigValor, $rowDetPaq['idPaq'], $idDocumento,
						$sigValor, $sigValor, $value_checkedPaq,
						$sigValor, $sigValor, $row['id_det_solicitud_repuesto'],
						$sigValor, $sigValor, round($rowDetPaq['total_rpto'], 2),
						$sigValor, $sigValor, round($rowDetPaq['total_tmp'], 2),
						$sigValor, $sigValor, $idIvasTemparioPaquete,//id iva separados por coma
						$sigValor, $sigValor, $porcentajeIvasTemparioPaquete,//porc iva separados por coma
						$sigValor, $sigValor, $repuestosTomadosEnSolicitud,
						$sigValor, $sigValor, round($rowDetPaq['total_art_exento'], 2),
						$sigValor, $sigValor, $rowIvasIdPorcPaq['id_iva_paquete'],
						$sigValor, $sigValor, $rowIvasIdPorcPaq['base_imponible_paquete'],
						$sigValor, $sigValor, $rowIvasIdPorcPaq['porc_iva_paquete'],
						$sigValor, $sigValor, round($rowDetPaq['total_art_con_iva'], 2),
				
				$sigValor,
					$rowDetPaq['idPaq'], utf8_encode(addslashes($rowDetPaq['codigo_paquete'])), utf8_encode(addslashes($rowDetPaq['descripcion_paquete'])),$rowDetPaq['precio_paquete']));
				// EL CERO EN LA SEXTA POSICION INDICA LA VISUALIZACION DE LOS BOTONES ACEPTAR Y CANCELAR EN LOS PAQUETES GUARDADOS...
		
				/*if($imgCheckDisabled != " ")
					$objResponse->script(sprintf("byId('imgCheckDisabled:%s').style.display='';",$sigValor));*/
			
				if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) { // || $valFormTotalDcto['hddAccionTipoDocumento']==3
					$objResponse->script(sprintf("
					byId('tdInsElimPaq').style.display = '';
					byId('tdItmPaq:%s').style.display = '';
					byId('tdItmPaqAprob:%s').style.display = 'none'",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
					$objResponse->script(sprintf("
					byId('tdInsElimPaq').style.display = 'none';
					byId('tdItmPaq:%s').style.display = 'none';
					byId('tdItmPaqAprob:%s').style.display = '';",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
					$objResponse->script(sprintf("
					byId('tdInsElimPaq').style.display = 'none';
					byId('tdItmPaq:%s').style.display = 'none';
					byId('tdItmPaqAprob:%s').style.display = ''",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
					$objResponse->script(sprintf("
					byId('tdInsElimPaq').style.display = '';
					byId('tdItmPaq:%s').style.display = '';
					byId('tdItmPaqAprob:%s').style.display = '';",
						$sigValor,
						$sigValor));
				}
			}
				
			$arrayObjPaq[] = $sigValor;
			$sigValor++;
		}
	
		if ($readonly_check_ppal_list_paquete == 1) {
			$objResponse->script("
			byId('cbxItmPaqAprob').style.display = 'none';");
			
			$objResponse->assign("tdPaqAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			$objResponse->assign("tdInsElimPaq","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
		
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if($sigValor == 1) {
				$objResponse->script("
				byId('frm_agregar_paq').style.display='none';");
			}
			/*if($tieneRptoSinSolicitudPaq == 1)
				$objResponse->script("
					byId('lstMecSolRptoBusq').disabled = false;");*/
		} else {
			if ($valFormTotalDcto['hddTipoDocumento' ]== 1) { //PRESUPUESTO
				if ($sigValor == 1) {
					$objResponse->script("
					byId('frmListaArticulo').style.display='none';
					byId('tblListaArticulo').style.display='none';");
				}
			}
		}
		
		if($_GET['dev'] == 1)
			$condicionMostrarTotFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetTot);
		else
			$condicionMostrarTotFacturados = "";
	
		$queryDetalleTot = sprintf("SELECT *,
			%s.%s,
			(SELECT GROUP_CONCAT(id_iva) FROM sa_det_orden_tot_iva WHERE sa_det_orden_tot_iva.id_det_orden_tot = %s.%s) as id_iva,
			(SELECT GROUP_CONCAT(iva) FROM sa_det_orden_tot_iva WHERE sa_det_orden_tot_iva.id_det_orden_tot = %s.%s) as iva
		FROM sa_orden_tot
			INNER JOIN %s ON (sa_orden_tot.id_orden_tot = %s.id_orden_tot)
			INNER JOIN cp_proveedor ON (sa_orden_tot.id_proveedor = cp_proveedor.id_proveedor)
		WHERE %s.%s = %s %s",
			$tablaDocDetTot, $campoTablaIdDetTotRelacOrden,
			$tablaDocDetTot, $campoTablaIdDetTot,
			$tablaDocDetTot, $campoTablaIdDetTot,
			$tablaDocDetTot, $tablaDocDetTot,
			$tablaDocDetTot, $campoIdEnc, valTpDato($idDocumento,"int"), $condicionMostrarTotFacturados);
		$rsDetalleTot = mysql_query($queryDetalleTot);
		if (!$rsDetalleTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
		$sigValor = 1;
		$arrayObjTot = NULL;
		while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			
			if($rowDetalleTot['aprobado'] == 1) {
				$checkedTot = "checked=\"checked\"";
				$value_checkedTot = 1;
				//$disabledTot = "disabled='disabled'";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_tot = 1;
					$displayTot = "style=\"display:none;\"";
					$imgCheckDisabledTot = sprintf("<input id=\"cbxItmAprobDisabled\" name=\"cbxItmAprobDisabled[]\" disabled=\"disabled\" type=\"checkbox\" value=\"%s\" checked=\"checked\" />", $sigValor);
				} else {
					$displayTot = "";
					$imgCheckDisabledTot = "";
				}
			} else {
				$itemsNoChecados = 1;
				$checkedTot = " ";
				$value_checkedTot = 0;
				$disabledTot = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayTot = "style=\"display:none;\"";
					$imgCheckDisabledTot = "<input id=\"cbxItmAprobDisabledNoChecked\" name=\"cbxItmAprobDisabledNoChecked[]\" disabled=\"disabled\" type=\"checkbox\"/>";
					$readonly_check_ppal_list_tot = 1;
				}
			}
			
			$totalTot = $rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100);
			
			$caracterIva = ($rowDetalleTot['id_iva'] != "") ? str_replace(",","% - ",$rowDetalleTot['iva'])."%" : "NA";
			
			//CALCULOS MULTIPLES IVAS
			$arrayIdIvasTot = explode(",",$rowDetalleTot['id_iva']);
			$arrayPorcIvasTot = explode(",",$rowDetalleTot['iva']);
			$montoMultipleIva = 0;
			foreach($arrayIdIvasTot as $key => $idIvasTot){
				$montoMultipleIva += ($totalTot * $arrayPorcIvasTot[$key] / 100);
			}
			$montoMultipleIva = $montoMultipleIva + $totalTot;
			
			$objResponse->script(sprintf("$('#trm_pie_tot').before('".
				"<tr id=\"trItmTot:%s\" class=\"textoGris_11px %s\" title=\"trItmTot:%s\">".
					"<td align=\"center\" id=\"tdItmTot:%s\" class=\"color_column_insertar_eliminar_item\">".
						"<input id=\"cbxItmTot\" name=\"cbxItmTot[]\" type=\"checkbox\" value=\"%s\" onclick=\"xajax_calcularTotalDcto();\" %s /></td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\" class=\"noRomper\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"right\"><b>%s</b>".
						"<input type=\"hidden\" id=\"hddIdPedDetTot%s\" name=\"hddIdPedDetTot%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdTot%s\" name=\"hddIdTot%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdIvaTot%s\" name=\"hddIdIvaTot%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIvaTot%s\" name=\"hddIvaTot%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdPorcTot%s\" name=\"hddIdPorcTot%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddPrecTot%s\" name=\"hddPrecTot%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddPorcTot%s\" name=\"hddPorcTot%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddMontoTotalTot%s\" name=\"hddMontoTotalTot%s\" value=\"%s\"/></td>".
					"<td align=\"center\" id=\"tdItmTotAprob:%s\" class=\"color_column_aprobacion_item\">".
						"<input id=\"cbxItmTotAprob\" name=\"cbxItmTotAprob[]\" type=\"checkbox\" value=\"%s\" %s onclick=\"xajax_calcularTotalDcto();\" %s /> %s".
						"<input type=\"hidden\" id=\"hddValorCheckAprobTot%s\" name=\"hddValorCheckAprobTot%s\" value=\"%s\"/></td>".
				"</tr>');",
			$sigValor, $clase, $sigValor,
				$sigValor,  $sigValor, $disabledTot,
				"<idtotoculta style=\"display:none\">".$rowDetalleTot['id_orden_tot']."</idtotoculta>".$rowDetalleTot['numero_tot'],
				utf8_encode(addslashes($rowDetalleTot['nombre'])),
				$rowDetalleTot['tipo_pago'],
				number_format($rowDetalleTot['monto_subtotal'],2,".",","),
				number_format($rowDetalleTot['porcentaje_tot'],2,".",",")."% ".$rowDetalleTot['descripcion'],
				$caracterIva,
				number_format($totalTot,2,".",","),
				number_format($montoMultipleIva,2,".",","),
					$sigValor, $sigValor, $rowDetalleTot['id_det_orden_tot_ref'],//sprintf('%s', $campoTablaIdDetTot)
					$sigValor, $sigValor, $rowDetalleTot['id_orden_tot'],
					$sigValor, $sigValor, $rowDetalleTot['id_iva'],
					$sigValor, $sigValor, $rowDetalleTot['iva'],
					$sigValor, $sigValor, $rowDetalleTot['id_porcentaje_tot'],
					$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['monto_subtotal']),
					$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['porcentaje_tot']),
					$sigValor, $sigValor, str_replace(",","", $totalTot),
			$sigValor, $sigValor, $checkedTot, $displayTot,
				$imgCheckDisabledTot,
				$sigValor, $sigValor, $value_checkedTot));
				
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
				$objResponse->script(sprintf("byId('tdInsElimTot').style.display = '';
				byId('tdItmTot:%s').style.display = '';
				byId('tdItmTotAprob:%s').style.display = 'none'",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
				$objResponse->script(sprintf("byId('tdInsElimTot').style.display = 'none';
				byId('tdItmTot:%s').style.display = 'none';
				byId('tdItmTotAprob:%s').style.display = '';
				byId('cbxItmTotAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
				$objResponse->script(sprintf("byId('tdInsElimTot').style.display = 'none';
				byId('tdItmTot:%s').style.display = 'none';
				byId('tdItmTotAprob:%s').style.display = ''",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
				$objResponse->script(sprintf("
				byId('tdInsElimTot').style.display = '';
				byId('tdItmTot:%s').style.display = '';
				byId('tdItmTotAprob:%s').style.display = '';",
					$sigValor,
					$sigValor));
			}
							
			$arrayObjTot[] = $sigValor;
			$sigValor++;
		}
		
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($sigValor == 1) {
				$objResponse->script("
				byId('frmListaTot').style.display = 'none';");
			}
		} else {
			if ($valFormTotalDcto['hddTipoDocumento'] == 1) { //PRESUPUESTO
				if ($sigValor == 1) {
					$objResponse->script("
					byId('frmListaTot').style.display = 'none';");
				}
			}
		}
		
		if ($readonly_check_ppal_list_tot == 1) {
			$objResponse->script("
			byId('cbxItmTotAprob').style.display = 'none';");
			$objResponse->assign("tdTotAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			//$objResponse->assign("tdInsElimTot","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");	
		}
		
		if ($_GET['dev'] == 1)
			$condicionMostrarTemparioFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetTemp);
		else
			$condicionMostrarTemparioFacturados = "";		
		
		$queryDetTemp = sprintf("SELECT 
			%s.%s,
			(SELECT GROUP_CONCAT(id_iva) FROM sa_det_orden_tempario_iva WHERE sa_det_orden_tempario_iva.id_det_orden_tempario = %s.%s) as id_iva,
			(SELECT GROUP_CONCAT(iva) FROM sa_det_orden_tempario_iva WHERE sa_det_orden_tempario_iva.id_det_orden_tempario = %s.%s) as iva,
			sa_modo.descripcion_modo,
			sa_tempario.codigo_tempario,
			sa_tempario.descripcion_tempario,
			%s.operador,
			sa_operadores.descripcion_operador,
			%s.id_tempario,
			%s.precio,
			%s.base_ut_precio,
			%s.id_modo,
			(case %s.id_modo
				when '1' then
					%s.ut * %s.precio_tempario_tipo_orden/%s.base_ut_precio
				when '2' then
					%s.precio
				when '3' then
					%s.costo
				when '4' then
					'4'
			end) AS total_por_tipo_orden,
			(case %s.id_modo
				when '1' then
					%s.ut
				when '2' then
					%s.precio
				when '3' then
					%s.costo
				when '4' then
					'4'
			end) AS precio_por_tipo_orden,
			%s.%s,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido,
			pg_empleado.id_empleado,
			sa_mecanicos.id_mecanico,
			sa_seccion.descripcion_seccion,
			sa_subseccion.id_seccion,
			sa_subseccion.descripcion_subseccion,
			sa_seccion.id_seccion,
			%s.aprobado,
			%s.origen_tempario,
			%s.origen_tempario + 0 AS idOrigen
		FROM %s
			INNER JOIN sa_tempario ON (%s.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_operadores ON (%s.operador = sa_operadores.id_operador)
			INNER JOIN sa_modo ON (%s.id_modo = sa_modo.id_modo)
			LEFT JOIN sa_mecanicos ON (%s.id_mecanico = sa_mecanicos.id_mecanico)
			LEFT JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado)
			INNER JOIN sa_subseccion ON (sa_tempario.id_subseccion = sa_subseccion.id_subseccion)
			INNER JOIN sa_seccion ON (sa_subseccion.id_seccion = sa_seccion.id_seccion)
		WHERE %s.id_paquete IS NULL
			AND %s.%s = %s %s",
			$tablaDocDetTemp, $campoTablaIdDetTempRelacOrden,
			$tablaDocDetTemp, $campoTablaIdDetTemp,//id vas
			$tablaDocDetTemp, $campoTablaIdDetTemp,//porc ivas
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoTablaIdDetTemp, 
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $condicionMostrarTemparioFacturados);
		$rsDetTemp = mysql_query($queryDetTemp);
		if (!$rsDetTemp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		$sigValor = 1;
		$arrayObjTemp = NULL;
		while ($rowDetTemp = mysql_fetch_assoc($rsDetTemp)) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			if ($rowDetTemp['aprobado'] == 1) {
				$checkedTemp = "checked=\"checked\"";
				$value_checkedTemp = 1;
				//$disabledTemp = "disabled='disabled'";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_tempario = 1;
					$displayTemp = "style=\"display:none;\"";
					$imgCheckDisabledTemp = sprintf("<input id=\"cbxItmAprobDisabled\" name=\"cbxItmAprobDisabled[]\" disabled=\"disabled\" type=\"checkbox\" value=\"%s\" checked=\"checked\" />", $sigValor);
				} else {
					$displayTemp = "";
					$imgCheckDisabledTemp = "";							       
				}
			} else {
				$itemsNoChecados = 1;
				$checkedTemp = " ";
				$value_checkedTemp = 0;
				$disabledTemp = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayTemp = "style=\"display:none;\"";
					$imgCheckDisabledTemp = "<input id=\"cbxItmAprobDisabledNoChecked\" name=\"cbxItmAprobDisabledNoChecked[]\" disabled=\"disabled\" type=\"checkbox\"/>";
					$readonly_check_ppal_list_tempario = 1;
				}/*else {
					$display = "";
					$imgCheckDisabled = "";
				}*/
			}
		
			if ($rowDetTemp['origen_tempario'] == 0) {
				$origen = "ORDEN";
			} else {
				$origen = "CONTROL TALLER";
			}
			
			$caracterIva = ($rowDetTemp['id_iva'] != "") ? str_replace(",","% - ",$rowDetTemp['iva'])."%" : "NA";
			
			//CALCULOS MULTIPLES IVAS
			$arrayIdIvasTemp = explode(",",$rowDetTemp['id_iva']);
			$arrayPorcIvasTemp = explode(",",$rowDetTemp['iva']);
			$montoMultipleIva = 0;
			foreach($arrayIdIvasTemp as $key => $idIvasTemp){
				$montoMultipleIva += ($rowDetTemp['total_por_tipo_orden'] * $arrayPorcIvasTemp[$key] / 100);
			}
			$montoMultipleIva = $montoMultipleIva + $rowDetTemp['total_por_tipo_orden'];
			
			$objResponse->script(sprintf("$('#trm_pie_tempario').before('".
				"<tr id=\"trItmTemp:%s\" class=\"textoGris_11px %s\" title=\"trItmTemp:%s\">".
					"<td align=\"center\" id=\"tdItmTemp:%s\" class=\"color_column_insertar_eliminar_item\">".
						"<input id=\"cbxItmTemp\" name=\"cbxItmTemp[]\" type=\"checkbox\" value=\"%s\" %s /></td>".
					"<td align=\"center\" id=\"tdItmNomMecanico:%s\">%s</td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"center\" class=\"noRomper\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"right\"><b>%s</b>".
						"<input type=\"hidden\" id=\"hddIdPedDetTemp%s\" name=\"hddIdPedDetTemp%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdTemp%s\" name=\"hddIdTemp%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdMec%s\" name=\"hddIdMec%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdIvaTemp%s\" name=\"hddIdIvaTemp%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIvaTemp%s\" name=\"hddIvaTemp%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddPrecTemp%s\" name=\"hddPrecTemp%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdModo%s\" name=\"hddIdModo%s\" value=\"%s\"/></td>".
					"<td align=\"center\" id=\"tdItmTempAprob:%s\" class=\"color_column_aprobacion_item\">".
						"<input id=\"cbxItmTempAprob\" name=\"cbxItmTempAprob[]\" title=\"cbxItmTempAprob:%s\" type=\"checkbox\" value=\"%s\" %s %s onclick=\"xajax_calcularTotalDcto();\" /> %s".
						"<input type=\"hidden\" id=\"hddValorCheckAprobTemp%s\" name=\"hddValorCheckAprobTemp%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdOrigen%s\" name=\"hddIdOrigen%s\" value=\"%s\"/></td>".
				"</tr>');",
			$sigValor, $clase, $sigValor,
				$sigValor, $sigValor, $disabledTemp,
				$sigValor, sprintf("%04s",$rowDetTemp['id_mecanico'])." - ".utf8_encode($rowDetTemp['nombre_empleado']." ".$rowDetTemp['apellido']),
				utf8_encode($rowDetTemp['descripcion_seccion']),
				utf8_encode($rowDetTemp['descripcion_subseccion']),
				utf8_encode(addslashes($rowDetTemp['codigo_tempario'])),
				utf8_encode(addslashes($rowDetTemp['descripcion_tempario'])),
				$origen,
				utf8_encode($rowDetTemp['descripcion_modo']),
				$rowDetTemp['descripcion_operador'],
				$rowDetTemp['precio_por_tipo_orden'],
				$caracterIva,
				number_format($rowDetTemp['total_por_tipo_orden'],2,".",","),
				number_format($montoMultipleIva,2,".",","),
					$sigValor, $sigValor, $rowDetTemp['id_det_orden_tempario_ref'],//sprintf('%s', $campoTablaIdDetTemp), $campoTablaIdDetTempRelacOrden)
					$sigValor, $sigValor, $rowDetTemp['id_tempario'],
					$sigValor, $sigValor, $rowDetTemp['id_mecanico'],
					$sigValor, $sigValor, $rowDetTemp['id_iva'],//id iva multiple separado por comas
					$sigValor, $sigValor, $rowDetTemp['iva'],//iva multiple separado por comas
					//$sigValor, $sigValor, utf8_encode($rowDetTemp['nombre_empleado']." ".$rowDetTemp['apellido']),//no va
					$sigValor, $sigValor, $rowDetTemp['total_por_tipo_orden'],
					$sigValor, $sigValor, utf8_encode($rowDetTemp['descripcion_modo']),
				$sigValor, $sigValor, $sigValor, $checkedTemp, $displayTemp,
					$imgCheckDisabledTemp,
					$sigValor, $sigValor, $value_checkedTemp,
					$sigValor, $sigValor, $rowDetTemp['idOrigen']));
			
			//dependiendo si se muestra o no el mecanico por parametros generales coloco la validacion  $valFormTotalDcto['hddTipoDocumento']==2
		
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
				$objResponse->script(sprintf("
				byId('tdInsElimManoObra').style.display = '';
				byId('tdItmTemp:%s').style.display = '';
				byId('tdItmTempAprob:%s').style.display = 'none'",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
				$objResponse->script(sprintf("
				byId('tdInsElimManoObra').style.display = 'none';
				byId('tdItmTemp:%s').style.display = 'none';
				byId('tdItmTempAprob:%s').style.display = '';
				byId('cbxItmTempAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
				$objResponse->script(sprintf("
				byId('tdInsElimManoObra').style.display = 'none';
				byId('tdItmTemp:%s').style.display = 'none';
				byId('tdItmTempAprob:%s').style.display = ''",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
				$objResponse->script(sprintf("
				byId('tdInsElimTot').style.display = '';
				byId('tdItmTot:%s').style.display = '';
				byId('tdItmTotAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
			}
					
			$arrayObjTemp[] = $sigValor;
			$sigValor++;
		}
	
		if ($readonly_check_ppal_list_tempario == 1) {
			$objResponse->script("
			byId('cbxItmTempAprob').style.display = 'none';");
			$objResponse->assign("tdTempAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			//$objResponse->assign("tdInsElimManoObra","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
				
		}
			
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if($sigValor==1) {
				$objResponse->script("
				byId('frmListaManoObra').style.display='none';");
			}
		} else {
			if($valFormTotalDcto['hddTipoDocumento']==1) { //PRESUPUESTO
				if($sigValor==1) {
					$objResponse->script("
					byId('frmListaManoObra').style.display='none';");
				}
			}
		}
	
		if($_GET['dev'] == 1) {
			$condicionMostrarNotaFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetNota);
		} else {
			$condicionMostrarNotaFacturados = "";
		}
	
		$queryDetTipoDocNotas = sprintf("SELECT
			%s.%s,
			(SELECT GROUP_CONCAT(id_iva) FROM sa_det_orden_notas_iva WHERE sa_det_orden_notas_iva.id_det_orden_nota = %s.%s) as id_iva,
			(SELECT GROUP_CONCAT(iva) FROM sa_det_orden_notas_iva WHERE sa_det_orden_notas_iva.id_det_orden_nota = %s.%s) as iva,
			%s AS idDetNota,
			%s AS idDoc,
			descripcion_nota,
			precio, 
			aprobado
		FROM %s
		WHERE %s = %s %s",
			$tablaDocDetNota, $campoTablaIdDetNotaRelacOrden, 
			$tablaDocDetNota, $campoTablaIdDetNota, 
			$tablaDocDetNota, $campoTablaIdDetNota,
			$campoTablaIdDetNota,
			$campoIdEnc,
			$tablaDocDetNota,
			$campoIdEnc, valTpDato($idDocumento,"int"), $condicionMostrarNotaFacturados);
		$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas);
		if (!$rsDetTipoDocNotas) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

		$sigValor = 1;
		$arrayObjNota = NULL;
		while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			//$caracterIva = ($rowPresupuestoDet['id_iva'] != "" && $rowPresupuestoDet['id_iva'] != "0") ? $rowPresupuestoDet['iva']."%" : "NA";
			if ($rowDetTipoDocNotas['aprobado'] == 1) {
				$checkedNota = "checked=\"checked\""; 
				$value_checkedNota = 1;
				//$disabledNota = "disabled='disabled'";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_nota = 1;
					$displayNota = "style=\"display:none;\"";
					$imgCheckDisabledNota = sprintf("<input id=\"cbxItmAprobDisabled\" name=\"cbxItmAprobDisabled[]\" disabled=\"disabled\" type=\"checkbox\" value=\"%s\" checked=\"checked\" />", $sigValor);
				} else {
					$displayNota = "";
					$imgCheckDisabledNota = "";
				}
			} else {
				$itemsNoChecados = 1;
				$checkedNota = " ";
				$value_checkedNota = 0;
				$disabledNota = "";
				/*$display = "style=\"display:none;\"";
				$imgCheckDisabled = "<input id=\"cbxItmAprobDisabledNoChecked\" name=\"cbxItmAprobDisabledNoChecked[]\" disabled=\"disabled\" type=\"checkbox\"/>";*/
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayNota = "style=\"display:none;\"";
					$imgCheckDisabledNota = "<input id=\"cbxItmAprobDisabledNoChecked\" name=\"cbxItmAprobDisabledNoChecked[]\" disabled=\"disabled\" type=\"checkbox\"/>";
					$readonly_check_ppal_list_nota = 1;
				}/*else {
					$display = "";
					$imgCheckDisabled = "";
					$display = "style='display:none;'";
					$imgCheckDisabled = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
				}*/
			}
			
			$caracterIva = ($rowDetTipoDocNotas['id_iva'] != "") ? str_replace(",","% - ",$rowDetTipoDocNotas['iva'])."%" : "NA";
			
			//CALCULOS MULTIPLES IVAS
			$arrayIdIvasNota = explode(",",$rowDetTipoDocNotas['id_iva']);
			$arrayPorcIvasNota = explode(",",$rowDetTipoDocNotas['iva']);
			$montoMultipleIva = 0;
			foreach($arrayIdIvasNota as $key => $idIvasNota){
				$montoMultipleIva += ($rowDetTipoDocNotas['precio'] * $arrayPorcIvasNota[$key] / 100);
			}
			$montoMultipleIva = $montoMultipleIva + $rowDetTipoDocNotas['precio'];
			
			$objResponse->script(sprintf("$('#trm_pie_nota').before('".
				"<tr id=\"trItmNota:%s\" class=\"textoGris_11px %s\" title=\"trItmNota:%s\">".
					"<td id=\"tdItmNota:%s\" class=\"color_column_insertar_eliminar_item\">".
						"<input id=\"cbxItmNota\" name=\"cbxItmNota[]\" type=\"checkbox\" value=\"%s\" %s />".
					"<td align=\"left\">%s</td>".
					"<td align=\"center\" class=\"noRomper\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"right\"><b>%s</b>".
						"<input type=\"hidden\" id=\"hddIdPedDetNota%s\" name=\"hddIdPedDetNota%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdNota%s\" name=\"hddIdNota%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdIvaNota%s\" name=\"hddIdIvaNota%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIvaNota%s\" name=\"hddIvaNota%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddDesNota%s\" name=\"hddDesNota%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddPrecNota%s\" name=\"hddPrecNota%s\" value=\"%s\"/></td>".
					"<td align=\"center\" id=\"tdItmNotaAprob:%s\" class=\"color_column_aprobacion_item\">".
						"<input id=\"cbxItmNotaAprob\" name=\"cbxItmNotaAprob[]\" type=\"checkbox\" value=\"%s\" %s %s onclick=\"xajax_calcularTotalDcto();\"/> %s".
						"<input type=\"hidden\" id=\"hddValorCheckAprobNota%s\" name=\"hddValorCheckAprobNota%s\" value=\"%s\"/></td>".
				"</tr>');",
			$sigValor, $clase, $sigValor,
				$sigValor, $sigValor, $disabledNota,
				utf8_encode(addslashes($rowDetTipoDocNotas['descripcion_nota'])),
				$caracterIva,
				number_format($rowDetTipoDocNotas['precio'],2,".",","),
				number_format($montoMultipleIva,2,".",","),
					$sigValor, $sigValor, $rowDetTipoDocNotas['id_det_orden_nota_ref'],//'idDetNota'
					$sigValor, $sigValor, $sigValor,
					$sigValor, $sigValor, $rowDetTipoDocNotas['id_iva'],//id iva multiple separado por comas
					$sigValor, $sigValor, $rowDetTipoDocNotas['iva'],//iva multiple separado por comas
					$sigValor, $sigValor, utf8_encode(addslashes($rowDetTipoDocNotas['descripcion_nota'])),
					$sigValor, $sigValor, $rowDetTipoDocNotas['precio'],
				$sigValor, $sigValor, $checkedNota, $displayNota,
					$imgCheckDisabledNota,
					$sigValor, $sigValor, $value_checkedNota));
					
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
				$objResponse->script(sprintf("
				byId('tdInsElimNota').style.display = '';
				byId('tdItmNota:%s').style.display = '';
				byId('tdItmNotaAprob:%s').style.display = 'none'",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
				$objResponse->script(sprintf("
				byId('tdInsElimNota').style.display = 'none';
				byId('tdItmNota:%s').style.display = 'none'; 
				byId('tdItmNotaAprob:%s').style.display = '';
				byId('cbxItmNotaAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
				$objResponse->script(sprintf("
				byId('tdInsElimNota').style.display = 'none';
				byId('tdItmNota:%s').style.display = 'none';
				byId('tdItmNotaAprob:%s').style.display = '';",
					$sigValor,
					$sigValor));
			}
					
			$arrayObjNota[] = $sigValor;
			$sigValor++;
		}
	
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($sigValor == 1) {
				$objResponse->script("
				byId('frmListaNota').style.display='none';");
			}
		} else {
			if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
				if ($sigValor == 1) {
					$objResponse->script("
					byId('frmListaNota').style.display='none';");
				}
			}
		}
		
		if ($readonly_check_ppal_list_nota == 1) {
			$objResponse->script("
			byId('cbxItmNotaAprob').style.display = 'none';");
			$objResponse->assign("tdNotaAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked'/>");
			//$objResponse->assign("tdInsElimNota","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
	
		$queryDetPorcentajeAdicional = sprintf("SELECT 
			%s.%s,
			%s.id_porcentaje_descuento,
			%s.porcentaje,
			%s.%s AS idDetDcto,
			sa_porcentaje_descuento.descripcion
		FROM sa_porcentaje_descuento
			INNER JOIN %s ON (sa_porcentaje_descuento.id_porcentaje_descuento = %s.id_porcentaje_descuento)
		WHERE %s.%s = %s",
			$tablaDocDetDescuento, $campoIdEnc,
			$tablaDocDetDescuento,
			$tablaDocDetDescuento,
			$tablaDocDetDescuento, $campoTablaIdDetDescuento,
			$tablaDocDetDescuento, $tablaDocDetDescuento,
			$tablaDocDetDescuento, $campoIdEnc, valTpDato($idDocumento,"int"));
		$rsDetPorcentajeAdicional = mysql_query($queryDetPorcentajeAdicional);
		if (!$rsDetPorcentajeAdicional) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
		$sigValor = 1;
		$arrayObjDcto = NULL;
		while ($rowDetPorcentajeAdicional = mysql_fetch_assoc($rsDetPorcentajeAdicional)) {

			$objResponse->script(sprintf("$('#trm_pie_dcto').before('".
				"<tr id=\"trItmDcto:%s\" class=\"textoGris_11px\" title=\"trItmDcto:%s\">".
					"<td align=\"right\" id=\"tdItmDcto:%s\" class=\"tituloCampo\">%s</td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"right\">".
						"<input type=\"text\" id=\"hddPorcDcto%s\" name=\"hddPorcDcto%s\" size=\"6\" style=\"text-align:right\" readonly=\"readonly\" value=\"%s\"/>%s</td>".
					"<td align=\"right\">".
						"<img id=\"imgElimDcto:%s\" name=\"imgElimDcto:%s\" src=\"../img/iconos/delete.png\" class=\"puntero noprint\" title=\"Porcentaje Adcl:%s\" />".
						"<input type=\"hidden\" id=\"hddIdDetDcto%s\" name=\"hddIdDetDcto%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdDcto%s\" name=\"hddIdDcto%s\" value=\"%s\"/></td>".
					"<td align=\"right\" id=\"tdItmNotaAprob:%s\">".
						"<input type=\"text\" id=\"txtTotalDctoAdcl%s\" name=\"txtTotalDctoAdcl%s\" readonly=\"readonly\" style=\"text-align:right\" size=\"18\"/></td>".
				"</tr>');".
			
			"byId('imgElimDcto:%s').onclick = function() {
				xajax_eliminarDescuentoAdicional(%s);			
			}",
			$sigValor, $sigValor,
				$sigValor, $rowDetPorcentajeAdicional['descripcion'].":",
				"",
				$sigValor, $sigValor, $rowDetPorcentajeAdicional['porcentaje'], "%",
				$sigValor, $sigValor, $sigValor,
					$sigValor, $sigValor, $rowDetPorcentajeAdicional['idDetDcto'],
					$sigValor, $sigValor, $rowDetPorcentajeAdicional['id_porcentaje_descuento'],
				$sigValor, $sigValor, $sigValor,
			
			$sigValor,
				$sigValor));
			
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1 || $valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
				$objResponse->script(sprintf("
				byId('imgElimDcto:%s').style.display = '';
				byId('imgAgregarDescuento').style.display = '';",
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento']==2 || $valFormTotalDcto['hddAccionTipoDocumento']==4) {
				$objResponse->script(sprintf("
				byId('imgElimDcto:%s').style.display = 'none';
				byId('imgAgregarDescuento').style.display = 'none';",
					$sigValor));
			}
		
			$arrayObjDcto[] = $sigValor;
			$sigValor++;
		}
	
		$cadenaRep = "";
		if(isset($arrayObj)){
			foreach($arrayObj as $indiceRep => $valorRep) {
				$cadenaRep .= "|".$valorRep;
			}
			$objResponse->assign("hddObj","value",$cadenaRep);
		}	
		
		$cadenaPaq = "";
		if(isset($arrayObjPaq)){
			foreach($arrayObjPaq as $indicePaq => $valorPaq) {
				$cadenaPaq .= "|".$valorPaq;
			}
			$objResponse->assign("hddObjPaquete","value",$cadenaPaq);
		}	
	
		$cadenaTot = "";
		if(isset($arrayObjTot)){
			foreach($arrayObjTot as $indiceTot => $valorTot) {
				$cadenaTot .= "|".$valorTot;
			}
			$objResponse->assign("hddObjTot","value",$cadenaTot);
		}	
	
		$cadenaTemp = "";
		if(isset($arrayObjTemp)){
			foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
				$cadenaTemp .= "|".$valorTemp;
			}
			$objResponse->assign("hddObjTempario","value",$cadenaTemp);
		}	
		
		$cadenaNota = "";
		if(isset($arrayObjNota)){
			foreach($arrayObjNota as $indiceNota => $valorNota) {
				$cadenaNota .= "|".$valorNota;
			}
			$objResponse->assign("hddObjNota","value",$cadenaNota);
		}

		$cadenaDcto = "";
		if(isset($arrayObjDcto)){
			foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
				$cadenaDcto .= "|".$valorDcto;
			}
			$objResponse->assign("hddObjDescuento", "value", $cadenaDcto);
		}
	}
	
	
	
	if (isset($_GET['dev'])) {
		if ($_GET['dev'] == 1) {
			$query = sprintf("SELECT 
				cj_cc_encabezadofactura.idFactura,
				cj_cc_encabezadofactura.numeroControl,
				cj_cc_encabezadofactura.numeroFactura,
				cj_cc_encabezadofactura.fechaRegistroFactura
			FROM cj_cc_encabezadofactura
			WHERE cj_cc_encabezadofactura.idFactura = %s",
				$_GET['idfct']);
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			//byId('trTipoClave').style.display='none';
			//byId('trClaveMov').style.display='none';
			$objResponse->script(sprintf("
			byId('tdNroFacturaVenta').style.display='';
			byId('tdTxtNroFacturaVenta').style.display='';
			
			byId('tblMotivoRetrabajo').style.display = '';
			byId('tblLeyendaOrden').style.display='none';
			
			byId('txtNroFacturaVentaServ').value='%s';
			byId('tdNroControl').style.display='';
			byId('tdTxtNroControl').style.display='';",
				$row['numeroFactura']));
		} else {//cargar vale de salida
			//solo servicios
		}
	}
		
	$objResponse->assign("txtFechaPresupuesto","value", $fechaDocumento);
	$objResponse->assign("txtIdPresupuesto","value",utf8_encode($idDocumento));
	$objResponse->assign("numeroOrdenMostrar","value",utf8_encode($numeroOrdenMostrar));

	$objResponse->assign("txtIdEmpresa","value",utf8_encode($idEmpresaOrden));
	$objResponse->assign("txtEmpresa","value",utf8_encode($empresaOrden));
	
	//numeracion de orden y presupuesto a mostrar:
	
	if($valFormTotalDcto['hddTipoDocumento'] == 1){//1 es Presupuesto
		$numeroOrdenPresupuestoMostrar = $numeroPresupuestoMostrar;
	}else{ //Cualquier otra cosa es Orden
		$numeroOrdenPresupuestoMostrar = $numeroOrdenMostrar;
	}
	$objResponse->assign("numeroOrdenPresupuestoMostrar","value",$numeroOrdenPresupuestoMostrar);
	//
	
	$objResponse->assign("txtDescuento","value", $descuento);
	
	$objResponse->assign("hddItemsNoAprobados","value", $itemsNoChecados);
	
        
	//$objResponse->assign("hddIdIvaVenta","value", $id_iva);
	//$objResponse->assign("txtIvaVenta","value", $iva);
		
	/*$id_iva = $row['idIva'];
	$iva = $row['iva'];
	$objResponse->alert($row['idIva']."".$row['iva']);*/
	
	//OJO CON ESTO... PUEDO ESTAR HACIENDO ALGUNA ACTUALIZACION QUE SEA DE ORDEN Y LA HAGA DE PRESUPUESTO O VICEVERSA...
	//$objResponse->assign("hddIdOrden","value",utf8_encode($idDocumento));
	
	$objResponse->assign("txtEstadoOrden","value", utf8_encode($estado_orden));
	$objResponse->assign("txtFechaVencimiento","value", $fechaDocumento);
		
	if ($_GET['dev'] == 1) {	
		$objResponse->script(sprintf("
		byId('fldPresupuesto').style.display='';
		xajax_cargaLstClaveMovimiento('ORDEN', %s);",
			$idTipoOrden));
	}else{	
		$objResponse->script(sprintf("
		byId('fldPresupuesto').style.display='';
		xajax_cargaLstClaveMovimiento(byId('lstTipoClave').value, %s);",
			$idTipoOrden));
	}
	
	$objResponse->script(sprintf("
	xajax_cargaLstTipoOrden('%s');",$idTipoOrden));
	
	$objResponse->loadCommands(cargaLstModulo(1,"",true));
	$objResponse->loadCommands(cargaLstTipoPago("","1"));
	$objResponse->call(asignarTipoPago,"1");
	
	$objResponse->assign("hddIdEmpleado","value",$_SESSION['idEmpleadoSysGts']);
		
	$objResponse->script("xajax_calcularTotalDcto();");
		
	return $objResponse;
}


?>