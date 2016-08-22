<?php
function asignarBanco($id_banco,$id_cuenta = 0){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM bancos WHERE idBanco = '".$id_banco."'";
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	$objResponse->assign("txtSaldoCuenta","value","");
	$objResponse->assign("hddSaldoCuenta","value","");
	
	$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmBuscar'),".$row['idBanco'].",".$id_cuenta.");
						  $('txtNombreBanco').className = 'inputInicial';
						  $('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarDetallesRetencion($idRetencion, $cambioBaseImponible = "NO"){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT id, importe, porcentaje, sustraendo, codigo FROM te_retenciones WHERE id = '".$idRetencion."'";
	$rs = mysql_query($query) or die(mysql_error());
	
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);
	
	$row = mysql_fetch_array($rs);
		
	$objResponse->assign("hddMontoMayorAplicar","value",$row['importe']);
	$objResponse->assign("hddPorcentajeRetencion","value",$row['porcentaje']);
	$objResponse->assign("hddSustraendoRetencion","value",$row['sustraendo']);
	$objResponse->assign("hddCodigoRetencion","value",$row['codigo']);
	$objResponse->assign("hddIdRetencion","value",$row['id']);
	$objResponse->script("calcularRetencion();");
        if($cambioBaseImponible == "SI"){
            $objResponse->script("calcularConBase();");
        }
							
	return $objResponse;
}

function asignarEmpresa($idEmpresa,$accion){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = ($idEmpresa == 0) ? $_SESSION['idEmpresaUsuarioSysGts'] : $idEmpresa;
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
	$rsEmpresa = mysql_query($queryEmpresa) or die (mysql_error());
	
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
	$nombreSucursal = "";
	
	if ($rowEmpresa['id_empresa_padre_suc'] > 0)
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
	
	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
	
	$objResponse -> assign("txtNombreEmpresa","value",$empresa);
	$objResponse -> assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	if ($accion == 0){
		$objResponse->assign("txtNombreBanco","value","");
		$objResponse->assign("hddIdBanco","value","-1");
		$objResponse->assign("txtSaldoCuenta","value","");
		$objResponse->assign("hddSaldoCuenta","value","");
	}
	$objResponse->script("xajax_encabezadoEmpresa($idEmpresa)");
	$objResponse->script("$('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarFactura($valForm,$idFactura,$tipoDocumento){
	$objResponse = new xajaxResponse();
	
	$montoPagar = $valForm['txtMontoAPagar'];
	$montoRetencion = $valForm['txtMontoRetencionISLR'];
	
	if ($montoPagar < 1){
		$queryMontoPagarFacturaPropuesta = sprintf("SELECT monto_pagar, monto_retenido FROM te_propuesta_pago_detalle_transferencia WHERE id_factura = %s AND id_propuesta_pago = %s",valTpDato($idFactura,"int"),valTpDato($_GET['id_propuesta'],"int"));
		$rsMontoPagarFacturaPropuesta = mysql_query($queryMontoPagarFacturaPropuesta);
		
                if (!$rsMontoPagarFacturaPropuesta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$rowMontoPagarFacturaPropuesta = mysql_fetch_array($rsMontoPagarFacturaPropuesta);
		
		$montoPagar = $rowMontoPagarFacturaPropuesta['monto_pagar'];
		$montoRetencion = $rowMontoPagarFacturaPropuesta['monto_retenido'];
	}
        
	if($tipoDocumento==0){//factura
            $queryFactura = sprintf("SELECT id_proveedor, observacion_factura, numero_factura_proveedor, fecha_origen, saldo_factura, estatus_factura, fecha_vencimiento FROM cp_factura WHERE id_factura = %s", valTpDato($idFactura, "int"));
            $rsFactura = mysql_query($queryFactura);
            if (!$rsFactura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            $rowFactura = mysql_fetch_assoc($rsFactura);

            $diasVencimiento = (((strtotime(date("d-m-Y")) - strtotime($rowFactura['fecha_vencimiento'])) / 86400) > 0 ) ? (strtotime(date("d-m-Y")) - strtotime($rowFactura['fecha_vencimiento'])) / 86400 : 0;

            /* INSERTA EL ARTICULO MEDIANTE INJECT */
            $objResponse->script(sprintf("
                    var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px', 'title':'trItm:%s'}).adopt([
                            new Element('td', {'align':'center', 'class':'noprint'}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s'>\"),
                            new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
                            new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
                            new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
                            new Element('td', {'align':'left', 'id':'tdItm:%s'}).setHTML(\"%s\"),
                            new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
                            new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
                            new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s\"),
                            new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s\"),
                            new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s"."<input type='hidden' value='prueb'>\")
                    ]);
                    elemento.injectBefore('trItmPie');",
                    $idFactura."x0", $idFactura."x0",
                    $idFactura."x0",
                    $idFactura, "FA-".$idFactura,
                    $idFactura, proveedor($rowFactura['id_proveedor']),
                    $idFactura, utf8_encode(preg_replace('/\s+/', ' ', $rowFactura['observacion_factura'])),
                    $idFactura, $rowFactura['numero_factura_proveedor'],
                    $idFactura, date("d-m-Y",strtotime($rowFactura['fecha_origen'])),
                    $idFactura, $diasVencimiento,
                    $idFactura, number_format($rowFactura['saldo_factura'],2,'.',','),
                    $idFactura, number_format($montoPagar,2,'.',','),
                    $idFactura, number_format($montoRetencion,2,'.',',')));
	
	}else{//nota de cargo 
            $queryFactura = sprintf("SELECT id_proveedor, observacion_notacargo, numero_notacargo, fecha_origen_notacargo, saldo_notacargo, estatus_notacargo, fecha_vencimiento_notacargo FROM cp_notadecargo WHERE id_notacargo = %s", valTpDato($idFactura, "int"));
            $rsFactura = mysql_query($queryFactura);
            if (!$rsFactura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            $rowFactura = mysql_fetch_assoc($rsFactura);

            $diasVencimiento = (((strtotime(date("d-m-Y")) - strtotime($rowFactura['fecha_vencimiento_notacargo'])) / 86400) > 0 ) ? (strtotime(date("d-m-Y")) - strtotime($rowFactura['fecha_vencimiento_notacargo'])) / 86400 : 0;
	
	/* INSERTA EL ARTICULO MEDIANTE INJECT */
	$objResponse->script(sprintf("
                var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px', 'title':'trItm:%s'}).adopt([
			new Element('td', {'align':'center', 'class':'noprint'}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s'>\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'left', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s"."<input type='hidden' value='prueb'>\")
		]);
		elemento.injectBefore('trItmPie');",
		$idFactura."x1", $idFactura."x1",
		$idFactura."x1",
		$idFactura, "ND-".$idFactura,
		$idFactura, proveedor($rowFactura['id_proveedor']),
		$idFactura, utf8_encode($rowFactura['observacion_notacargo']),
		$idFactura, $rowFactura['numero_notacargo'],
		$idFactura, date("d-m-Y",strtotime($rowFactura['fecha_origen_notacargo'])),
		$idFactura, $diasVencimiento,
		$idFactura, number_format($rowFactura['saldo_notacargo'],2,'.',','),
		$idFactura, number_format($montoPagar,2,'.',','),
		$idFactura, number_format($montoRetencion,2,'.',',')));
	
	}
	
	$objResponse->assign("hddObj","value",$cadena);
	
	return $objResponse;
}

function asignarProveedor($id_proveedor,$id_empresa, $cargar = 0){
	$objResponse = new xajaxResponse();
	$query = sprintf("SELECT id_proveedor, nombre, banco,ncuenta  FROM cp_proveedor WHERE id_proveedor = %s",valTpDato($id_proveedor,"int"));
	$rs = mysql_query($query) or die(mysql_error());
	
	if (!$rs)return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$row = mysql_fetch_array($rs);
	
	$queryProveedorCredito = sprintf("SELECT reimpuesto FROM cp_prove_credito WHERE id_proveedor = %s",valTpDato($id_proveedor,"int"));
	$rsProveedorCredito = mysql_query($queryProveedorCredito);
	
	if (!$rsProveedorCredito)return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	if (mysql_num_rows($rsProveedorCredito)){
		$rowProveedorCredito = mysql_fetch_array($rsProveedorCredito);
		$objResponse->loadCommands(comboRetencionISLR($row['banco']),$rowProveedorCredito['reimpuesto']);
		$objResponse->loadCommands(asignarDetallesRetencion($rowProveedorCredito['reimpuesto']));
	}
	else
		$objResponse->loadCommands(comboRetencionISLR($row['banco']),'');
						
	$objResponse->assign("txtProveedorCabecera","value",utf8_encode($row['nombre']));
	$objResponse->assign("hddIdProveedorCabecera","value",$row['id_proveedor']);
        
        if($cargar != 1){//1 proviene de carga, no limpiar
            $objResponse->assign("txtNumCuenta","value",$row['ncuenta']);
        }
	
	//$objResponse->loadCommands(asignarBanco($row['banco']));
    $objResponse->script("$('divFlotanteProv').style.display = 'none';");
	$objResponse->script("$('btnAgregarFactura').disabled = '';");
	$objResponse->script("$('btnAgregarNotaCargo').disabled = '';");
	
	return $objResponse;
}



function buscarFactura($valForm){
	$objResponse = new xajaxResponse();
        
        $cadenaIdFacturasNotas = implode("x",explode("|",$valForm['arrayIdFactura']));
        $cadenaTipoDocumento = implode("x",explode("|",$valForm['arrayTipoDocumento']));
//xajax.getFormValues('frmBuscar')
	$objResponse->script(sprintf("xajax_listarFacturas(0,'','','%s|%s|%s|%s||%s|%s');",
		$valForm['hddIdEmpresa'],
		$valForm['hddIdProveedorCabecera'],
		$valForm['hddTipoFactura'],
		$valForm['selDiasVencimiento'],
                $cadenaIdFacturasNotas,
                $cadenaTipoDocumento));
		
	return $objResponse;
}

function buscarNotaCargo($valForm){
    $objResponse = new xajaxResponse();
        
    $cadenaIdFacturasNotas = implode("x",explode("|",$valForm['arrayIdFactura']));
    $cadenaTipoDocumento = implode("x",explode("|",$valForm['arrayTipoDocumento']));
            
    $objResponse->script(sprintf("xajax_listarNotaCargo(0,'','','%s|%s||%s|%s');",
		$valForm['hddIdEmpresa'],
		$valForm['hddIdProveedorCabecera'],
                $cadenaIdFacturasNotas,
                $cadenaTipoDocumento));
		
	return $objResponse;
}

function buscarDocumento($id_empresa,$id_proveedor,$tipoFactura,$diasVencimiento,$valfrom,$valFormFacturas){
	$objResponse = new xajaxResponse();
        
        $cadenaIdFacturasNotas = implode("x",explode("|",$valFormFacturas['arrayIdFactura']));
        $cadenaTipoDocumento = implode("x",explode("|",$valFormFacturas['arrayTipoDocumento']));
        
        if($valfrom['buscarFact'] == 1){

            $objResponse->script(sprintf("xajax_listarFacturas(0,'','','%s|%s|%s|%s|%s|%s|%s');",
                    $id_empresa,
                    $id_proveedor,
                    $tipoFactura,
                    $diasVencimiento,
                    $valfrom['txtCriterioBusq'],
                    $cadenaIdFacturasNotas,
                    $cadenaTipoDocumento));
        }elseif($valfrom['buscarFact'] == 2){
            $objResponse->script(sprintf("xajax_listarNotaCargo(0,'','','%s|%s|%s|%s|%s');",
                    $id_empresa,
                    $id_proveedor,
                    $valfrom['txtCriterioBusq'],
                    $cadenaIdFacturasNotas,
                    $cadenaTipoDocumento));
        }
		
	return $objResponse;
}

function cargarPropuesta($idPropuesta){
	$objResponse = new xajaxResponse();
	
	$queryPropuesta = sprintf("SELECT idCuentas, idBanco, id_proveedor, id_empresa, num_cta_transferencia FROM vw_te_propuesta_pago_transferencia WHERE id_propuesta_pago = %s",valTpDato($idPropuesta,"int"));
	$rsPropuesta = mysql_query($queryPropuesta);
	
	if(!$rsPropuesta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$rowPropuesta = mysql_fetch_array($rsPropuesta);
	$objResponse->script("xajax_asignarBanco(".$rowPropuesta['idBanco'].",".$rowPropuesta['idCuentas'].");
						  xajax_asignarEmpresa(".$rowPropuesta['id_empresa'].",1);
						  xajax_asignarProveedor(".$rowPropuesta['id_proveedor'].",".$rowPropuesta['id_empresa'].",1);");	
	
	$queryPropuestaDetalle = sprintf("SELECT * FROM te_propuesta_pago_detalle_transferencia WHERE id_propuesta_pago = %s",valTpDato($idPropuesta,"int"));
	$rsPropuestaDetalle = mysql_query($queryPropuestaDetalle);
	
	if(!$rsPropuestaDetalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$monto_propuesta = 0;
	
	while ($rowPropuestaDetalle = mysql_fetch_array($rsPropuestaDetalle)){
		$arrayIdFactura .= "|".$rowPropuestaDetalle['id_factura'];
		$arrayTipoDocumento .= "|".$rowPropuestaDetalle['tipo_documento'];
		$arrayMonto .= "|".$rowPropuestaDetalle['monto_pagar'];
		$arraySustraendoRetencion .= "|".$rowPropuestaDetalle['sustraendo_retencion'];
		$arrayPorcentajeRetencion .= "|".$rowPropuestaDetalle['porcentaje_retencion'];
		$arrayMontoRetenido .= "|".$rowPropuestaDetalle['monto_retenido'];
		$monto_propuesta += $rowPropuestaDetalle['monto_pagar'];
		$arrayCodigoRetencion .= "|".$rowPropuestaDetalle['codigo'];
		$arrayIdRetencion .= "|".$rowPropuestaDetalle['id_retencion'];
		$arrayBaseImponibleRetencion .= "|".$rowPropuestaDetalle['base_imponible_retencion'];

		
		$objResponse->script("xajax_asignarFactura(xajax.getFormValues('frmBuscar'),".$rowPropuestaDetalle['id_factura'].",".$rowPropuestaDetalle['tipo_documento'].")");
	}
	$objResponse->assign("txtNumCuenta","value",$rowPropuesta['num_cta_transferencia']);
	$objResponse->assign("arrayIdFactura","value",$arrayIdFactura);
	$objResponse->assign("arrayTipoDocumento","value",$arrayTipoDocumento);
	$objResponse->assign("arrayMonto","value",$arrayMonto);
	$objResponse->assign("arraySustraendoRetencion","value",$arraySustraendoRetencion);
	$objResponse->assign("arrayCodigoRetencion","value",$arrayCodigoRetencion);
	$objResponse->assign("arrayIdRetencion","value",$arrayIdRetencion);
	$objResponse->assign("arrayBaseImponibleRetencion","value",$arrayBaseImponibleRetencion);
	$objResponse->assign("arrayPorcentajeRetencion","value",$arrayPorcentajeRetencion);
	$objResponse->assign("arrayMontoRetenido","value",$arrayMontoRetenido);
	$objResponse->assign("txtMontoPropuesta","value",number_format($monto_propuesta,"2",".",","));
	$objResponse->assign("hddMontoPropuesta","value",number_format($monto_propuesta,"2",".",""));
	



$arraySumMonto1= explode("|", $arrayMonto);
$SumaMonto = array_sum($arraySumMonto1);

$arraySumMontoRetenido1= explode("|", $arrayMontoRetenido);
$SumaMontoRetenido = array_sum($arraySumMontoRetenido1);

		
		$html .= "<table border=\"0\" cellpadding=\"2\" width=\"100%\" >";
		$html .= "<tr align=\"center\">";
			$html .= "<td width=\"600\" align=\"right\" class=\"tituloColumna\">Total";
			$html .= "</td>";
			$html .= "<td width=\"65\" align=\"right\">";
				$html .= htmlentities(number_format($SumaMonto,"2",".",","));
			$html .= "</td>";
			$html .= "<td width=\"65\" align=\"right\">";
				$html .= htmlentities(number_format($SumaMontoRetenido,"2",".",","));
			$html .= "</td>";		
		$html .= "</tr>";
		$html .= "</table>";
				

		$objResponse->assign("tdPrueb","innerHTML",$html);

	
	return $objResponse;
}

function cargaSaldoCuenta($id_cuenta,$valForm){
	$objResponse = new xajaxResponse();
	

	$queryCuenta = sprintf("SELECT * FROM cuentas WHERE  idCuentas = '%s'",$id_cuenta);
	$rsCuenta = mysql_query($queryCuenta) or die(mysql_error());
	$rowCuenta = mysql_fetch_array($rsCuenta);
	

		$objResponse->script("$('btnAgregarFactura').disabled = '';");

		
		$Diferido = $rowCuenta['Diferido'];	
		$saldo = $rowCuenta['saldo_tem'];		
	
		$objResponse->assign("txtDiferido","value",number_format($Diferido,'2','.',''));
		$objResponse->assign("hddDiferido","value",number_format($Diferido,'2','.',''));
		
		$objResponse->assign("txtSaldoCuenta","value",number_format($saldo,'2','.',','));
		$objResponse->script("$('txtSaldoCuenta').className = 'inputInicial'");
		$objResponse->assign("hddSaldoCuenta","value",number_format($saldo,'2','.',''));
		$objResponse->assign("hddIdCuenta","value",$id_cuenta);
	

	return $objResponse;
}

function comboCuentas($valForm,$idBanco,$idCuenta = 0){
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdBanco'] == -1){
		$disabled = "disabled=\"disabled\"";
	}
	else{
		if ($valForm['hddIdEmpresa'] == "" && $idCuenta != 0){
			$queryEmpresa = sprintf("SELECT id_empresa FROM cuentas WHERE idCuentas = %s",valTpDato($idCuenta,'int'));
			$rsEmpresa = mysql_query($queryEmpresa);
			
			if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
			$rowEmpresa = mysql_fetch_array($rsEmpresa);
			$idEmpresa = $rowEmpresa['id_empresa'];
		}
		else{
			$idEmpresa = $valForm['hddIdEmpresa'];
		}
		
		$condicion = "WHERE idBanco = '".valTpDato($idBanco,"int")."' AND id_empresa = '".valTpDato($idEmpresa,"int")."'";
		$disabled = "";
	}
	$queryCuentas = "SELECT * FROM cuentas ".$condicion."";

	$rsCuentas = mysql_query($queryCuentas) or die(mysql_error());
	
	$html = "<select id=\"selCuenta\" name=\"selCuenta\" ".$disabled." onchange=\"confirmarCambioCuenta (this.value); \">";
		$html .= "<option value=\"-1\">Seleccione</option>";
	while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
		if ($rowCuentas['idCuentas'] == $idCuenta){
			$selected = "selected='selected'";
			$objResponse->script("xajax_cargaSaldoCuenta(".$idCuenta.",xajax.getFormValues('frmBuscar'));");
		}
		else
			$selected = "";
		
		$html .= "<option value=\"".$rowCuentas['idCuentas']."\" ".$selected.">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuentas","innerHTML",$html);
		
	return $objResponse;
}

function comboRetencionISLR($id_retencion = ''){
	$objResponse = new xajaxResponse();
	
	$queryRetenciones = "SELECT * FROM te_retenciones";
	$rsRetenciones = mysql_query($queryRetenciones);
	
	$html = "<select id=\"selRetencionISLR\" name=\"selRetencionISLR\" onchange=\"xajax_asignarDetallesRetencion(this.value)\">";
	while ($rowRetenciones = mysql_fetch_assoc($rsRetenciones)) {
		if ($rowRetenciones['id'] == $id_retencion){
			$selected = " selected='selected'";
		}
		else
			$selected = "";
		
		$html .= "<option value=\"".$rowRetenciones['id']."\" ".$selected.">".utf8_encode($rowRetenciones['descripcion'])."</option>";
	}
	$html .= "</select>";
		
		$objResponse->assign("tdRetencionISLR","innerHTML",$html);
		$objResponse->assign("hddMontoMayorAplicar","innerHTML","0");
		$objResponse->assign("hddPorcentajeRetencion","innerHTML","0");
		$objResponse->assign("hddSustraendoRetencion","innerHTML","0");


	return $objResponse;
}

function eliminarFactura($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItm'])) {
		foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
                   
			$objResponse->script(sprintf("quitarFactura('%s')",$valorItm));
			
			$objResponse->script(sprintf("
				fila = document.getElementById('trItm:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	
	return $objResponse;
}

function facturaSeleccionada($idDocumento,$tipo){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("$('txtMontoAPagar').className = 'inputInicial'");
	if($tipo==0){//factura
            $query = sprintf("SELECT id_proveedor, numero_factura_proveedor, observacion_factura, saldo_factura FROM cp_factura WHERE id_factura = %s;",$idDocumento);
            $rs = mysql_query($query);

            if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

            $row = mysql_fetch_array($rs);

            $objResponse->assign("hddIdFactura","value",$idDocumento);
            $objResponse->assign("txtProveedor","value",proveedor($row['id_proveedor']));
            $objResponse->assign("txtNumeroFactura","value",$row['numero_factura_proveedor']);
            $objResponse->assign("txtDescripcion","value",utf8_encode($row['observacion_factura']));
            $objResponse->assign("txtSaldoFactura","value",number_format($row['saldo_factura'],2,".",""));
            $objResponse->assign("txtMontoAPagar","value",number_format($row['saldo_factura'],2,".",""));
            $objResponse->assign("hddTipoDocumento","value","0");

            $queryIvaFactura = sprintf("SELECT base_imponible, iva FROM cp_factura_iva WHERE id_factura = %s ",$idDocumento);
            $rsIvaFactura = mysql_query($queryIvaFactura);

            if (!$rsIvaFactura) return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__);

            if (mysql_num_rows($rsIvaFactura)){
                    $rowIvaFactura = mysql_fetch_array($rsIvaFactura);
                    $objResponse->assign("hddIva","value",$rowIvaFactura['iva']);
                    $objResponse->assign("hddBaseImponible","value",$rowIvaFactura['base_imponible']);
            }
            else{
                    $objResponse->assign("hddIva","value","0");
                    $objResponse->assign("hddBaseImponible","value","0");
            }
            
	}else{
            $queryNotaCargo = sprintf("SELECT numero_notacargo,fecha_origen_notacargo, fecha_vencimiento_notacargo , observacion_notacargo, saldo_notacargo, id_proveedor FROM cp_notadecargo WHERE id_notacargo = '%s'",$idDocumento);
            $rsNotaCargo = mysql_query($queryNotaCargo) or die (mysql_error());

            if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);

            $rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);

            $objResponse->assign("hddIdFactura","value",$idDocumento);
            $objResponse->assign("txtProveedor","value",proveedor($rowNotaCargo['id_proveedor']));
            $objResponse->assign("txtNumeroFactura","value",$rowNotaCargo['numero_notacargo']);
            $objResponse->assign("txtSaldoFactura","value",$rowNotaCargo['saldo_notacargo']);
            $objResponse->assign("txtMontoAPagar","value",$rowNotaCargo['saldo_notacargo']);
            $objResponse->assign("txtDescripcion","innerHTML",utf8_encode($rowNotaCargo['observacion_notacargo']));
            $objResponse->assign("hddTipoDocumento","value","1");


            $queryIvaNotaCargo = sprintf("SELECT baseimponible, iva  FROM cp_notacargo_iva WHERE id_notacargo = %s ",$idDocumento);
            $rsIvaNotaCargo = mysql_query($queryIvaNotaCargo);

            if (!$rsIvaNotaCargo) return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__);

            if (mysql_num_rows($rsIvaNotaCargo)){
                    $rowIvaNotaCargo = mysql_fetch_array($rsIvaNotaCargo);
                    $objResponse->assign("hddIva","value",$rowIvaNotaCargo['iva']);
                    $objResponse->assign("hddBaseImponible","value",$rowIvaNotaCargo['baseimponible']);
            }
            else{
                    $objResponse->assign("hddIva","value","0");
                    $objResponse->assign("hddBaseImponible","value","0");
            }
		
	}
	$objResponse->script("$('divFlotante').style.display = '';
						  $('tblFactura').style.display = '';
						  centrarDiv($('divFlotante'));
						  $('tdFlotanteTitulo').innerHTML = 'Seleccione Cuenta';
						  $('tdTextoRetencionISLR').style.display = 'none';
						  $('tdMontoRetencionISLR').style.display = 'none';
						  calcularRetencion();");
	
	return $objResponse;
}


function guardarPropuesta($valForm){
	
	$objResponse = new xajaxResponse();    
        
        mysql_query("START TRANSACTION;");
                
	$arrayIdFacturas = explode("|", $valForm['arrayIdFactura']);
	$arrayMontoTr = explode("|", $valForm['arrayMonto']);
	$arraySustraendoRetencion = explode("|", $valForm['arraySustraendoRetencion']);
	$arrayPorcentajeRetencion = explode("|", $valForm['arrayPorcentajeRetencion']);
	$arrayMontoRetenido = explode("|", $valForm['arrayMontoRetenido']);
	$arrayCodigoRetencion = explode("|", $valForm['arrayCodigoRetencion']);
	$arrayIdRetencion = explode("|", $valForm['arrayIdRetencion']);
	$arrayBaseImponibleRetencion = explode("|", $valForm['arrayBaseImponibleRetencion']);
	$arrayTipoDocumento = explode("|", $valForm['arrayTipoDocumento']);
	
	if ($valForm['hddIdPropuesta'] == 0){
		$queryInsertCabecera = sprintf("INSERT INTO te_propuesta_pago_transferencia (id_propuesta_pago, fecha_propuesta_pago, estatus_propuesta,num_cta_transferencia ,id_cuenta) VALUES('', NOW(), 0, '%s', %s);",
									 $valForm['txtNumCuenta'], $valForm['hddIdCuenta']);
									
		$rsInsertCabecera = mysql_query($queryInsertCabecera);
		$idPropuestaPago = mysql_insert_id();
		
                if (!$rsInsertCabecera) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
				
	$updateSaldo_Diferido = sprintf("UPDATE cuentas SET Diferido = '%s' WHERE idCuentas = %s ;",$valForm['hddDiferido'],$valForm['hddIdCuenta']);	
	$rsUpdateSaldo_Diferido = mysql_query($updateSaldo_Diferido); 
        if (!$rsUpdateSaldo_Diferido) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }

		foreach($arrayIdFacturas as $indice => $valor){
			if ($valor != ""){
				$queryInsertDetalle = sprintf(" INSERT INTO te_propuesta_pago_detalle_transferencia (id_propuesta_pago_detalle, id_propuesta_pago, id_factura, monto_pagar, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, id_retencion, base_imponible_retencion, tipo_documento) VALUES('', %s, %s, '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s')",
								$idPropuestaPago,
								$valor,
								$arrayMontoTr[$indice],
								$arraySustraendoRetencion[$indice],
								$arrayPorcentajeRetencion[$indice],
								$arrayMontoRetenido[$indice],
								$arrayCodigoRetencion[$indice],
								$arrayIdRetencion[$indice],
								$arrayBaseImponibleRetencion[$indice],
								$arrayTipoDocumento[$indice]);
				$rsInsertDetalle = mysql_query($queryInsertDetalle);
				
                                if (!$rsInsertDetalle){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
		}
		
		$objResponse->alert("Propuesta guardada exitosamente");
	
		$objResponse->script("window.open('te_propuesta_pago_tr_mantenimiento.php','_self');");
	}
	else{
		$queryDeletePropuesta = sprintf("DELETE FROM te_propuesta_pago_detalle_transferencia WHERE id_propuesta_pago = %s",$valForm['hddIdPropuesta']);
		$rsDeletePropuesta = mysql_query($queryDeletePropuesta);
                if (!$rsDeletePropuesta) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		foreach($arrayIdFacturas as $indice => $valor){
			if ($valor != ""){
				
                                $updateSaldo_Diferido = sprintf("UPDATE cuentas SET Diferido = '%s' WHERE idCuentas = %s ;",$valForm['hddDiferido'],$valForm['hddIdCuenta']);	
                                $rsUpdateSaldo_Diferido = mysql_query($updateSaldo_Diferido);
                                if (!$rsUpdateSaldo_Diferido) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

				$queryInsertDetalle = sprintf(" INSERT INTO te_propuesta_pago_detalle_transferencia (id_propuesta_pago, id_factura, monto_pagar, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, id_retencion, base_imponible_retencion, tipo_documento) VALUES(%s, %s, '%s', '%s', '%s', '%s', '%s', '%s','%s','%s')",
								$valForm['hddIdPropuesta'],
								$valor,
								$arrayMontoTr[$indice],
								$arraySustraendoRetencion[$indice],
								$arrayPorcentajeRetencion[$indice],
								$arrayMontoRetenido[$indice],
								$arrayCodigoRetencion[$indice],
                                                            $arrayIdRetencion[$indice],
                                                            $arrayBaseImponibleRetencion[$indice],
								$arrayTipoDocumento[$indice]);
				$rsInsertDetalle = mysql_query($queryInsertDetalle);
				
                                if (!$rsInsertDetalle) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				
				
			}
		}
	
	 
		$objResponse->alert("Propuesta Editada exitosamente");
	
		$objResponse->script("window.open('te_propuesta_pago_tr_mantenimiento.php','_self');");
	}
	
        mysql_query("COMMIT;");
        
	return $objResponse;
}

function listarFacturas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 7, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayIdFacturas = explode("x", $valCadBusq[5]);//$valForm['arrayIdFactura']
        $arrayTipoDocumento = array_diff(explode("x", $valCadBusq[6]),array("1"));//$valForm['arrayTipoDocumento']
        
        $arrayIdFacturas = array_intersect_key($arrayIdFacturas,$arrayTipoDocumento);
        	
	$valCadBusq[0] = ($valCadBusq[0] == 0) ? $_SESSION['idEmpresaUsuarioSysGts'] : $valCadBusq[0];
	
	$mensaje = ($valCadBusq[1] == 0) ? "Seleccione Un Proveedor" : "No se encontraron registros";
	
	if ($valCadBusq[2] == 0)
		$condicion = "";
	else if ($valCadBusq[2] == 1)
		$condicion = " AND fecha_vencimiento > NOW() ";
	else
		$condicion = " AND fecha_vencimiento < NOW() ";
	
	$condicionNotIn = "";
	
	if (count($arrayIdFacturas) > 1){
		foreach($arrayIdFacturas as $indiceIdFactura => $valorIdFactura){
			$facturas .= $valorIdFactura.",";
		}
		$facturas = substr ($facturas, 1, strlen($facturas));
		$facturas = substr ($facturas, 0, strlen($facturas) - 1);
		$condicionNotIn = " AND id_factura NOT IN (".$facturas.")";
	}
	 
	
	$sqlBusq = sprintf(" AND numero_factura_proveedor LIKE %s",
		valTpDato("%".$valCadBusq[4]."%", "text"));
		
	$query = sprintf("SELECT id_factura, id_proveedor, observacion_factura, numero_factura_proveedor, fecha_origen, saldo_factura, estatus_factura, fecha_vencimiento, id_empresa 
            FROM cp_factura 
            WHERE (id_empresa = %s OR id_empresa IN ((SELECT id_empresa_reg FROM vw_iv_empresas_sucursales WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s )))
            AND id_proveedor = %s AND estatus_factura <> 1 AND cp_factura.id_factura NOT IN 
    (SELECT te_propuesta_pago_detalle.id_factura FROM te_propuesta_pago_detalle 
    INNER JOIN te_propuesta_pago ON te_propuesta_pago.id_propuesta_pago = te_propuesta_pago_detalle.id_propuesta_pago
    WHERE te_propuesta_pago_detalle.tipo_documento <> 1 AND te_propuesta_pago.estatus_propuesta = 0
  UNION ALL  
    SELECT te_propuesta_pago_detalle_transferencia.id_factura FROM te_propuesta_pago_detalle_transferencia 
    INNER JOIN te_propuesta_pago_transferencia ON te_propuesta_pago_transferencia.id_propuesta_pago = te_propuesta_pago_detalle_transferencia.id_propuesta_pago
    WHERE te_propuesta_pago_detalle_transferencia.tipo_documento <> 1 AND te_propuesta_pago_transferencia.estatus_propuesta = 0)%s %s %s",
	valTpDato($valCadBusq[0],"int"),
	valTpDato($valCadBusq[0],"int"),
	valTpDato($valCadBusq[1],"int"),
	$condicion,
	$condicionNotIn,$sqlBusq);
  
  
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"2%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "10%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero");
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "20%", $pageNum, "observacion_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "10%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "10%", $pageNum, "saldo_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"validarCuentaSeleccionada(".$row['id_factura'].",0);\" title=\"Seleccionar Factura\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".empresa($row['id_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['observacion_factura'])."</td>";
			$htmlTb .= "<td>".date("d-m-Y",strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['saldo_factura']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarFacturas(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
								  
							  
	$objResponse->assign("tdListadoFacNcargo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	$objResponse->script("
		$('tblFacturasNcargos').style.display = '';");
	
	$objResponse->assign("tdFlotanteTituloDoc","innerHTML","Facturas");
	$objResponse->script("
		if ($('divFlotanteDoc').style.display == 'none') {
			$('divFlotanteDoc').style.display = '';
			centrarDiv($('divFlotanteDoc'));
			
			document.forms['frmBuscarDocumento'].reset();
			$('txtCriterioBusq').focus();
		}
	");

	return $objResponse;
}


function listarNotaCargo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 7, $totalRows = NULL){
	$objResponse = new xajaxResponse();

$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	$sqlBusq = sprintf(" AND numero_notacargo LIKE %s",
		valTpDato("%".$valCadBusq[2]."%", "text"));
        
        $condicionNotIn = "";
        $arrayIdFacturas = explode("x", $valCadBusq[3]);//$valForm['arrayIdFactura']
        $arrayTipoDocumento = array_diff(explode("x", $valCadBusq[4]),array("0"));//$valForm['arrayTipoDocumento']
        
        $arrayIdFacturas = array_intersect_key($arrayIdFacturas,$arrayTipoDocumento);
        
        
//        var_dump($arrayIdFacturas);
//        var_dump($arrayTipoDocumento);
      
	if (count($arrayIdFacturas) > 1){
		foreach($arrayIdFacturas as $indiceIdFactura => $valorIdFactura){
			$facturas .= $valorIdFactura.",";
		}
		$facturas = substr ($facturas, 1, strlen($facturas));
		$facturas = substr ($facturas, 0, strlen($facturas) - 1);
		$condicionNotIn = " AND id_notacargo NOT IN (".$facturas.")";
	}
		   
	$query = sprintf("SELECT id_notacargo, id_proveedor, observacion_notacargo, numero_control_notacargo, numero_notacargo, fecha_origen_notacargo, saldo_notacargo, estatus_notacargo, fecha_vencimiento_notacargo, id_empresa 
            FROM cp_notadecargo 
            WHERE (id_empresa = %s OR id_empresa IN ((SELECT id_empresa_reg FROM vw_iv_empresas_sucursales WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s )))
            AND id_proveedor = %s AND estatus_notacargo <> 1 AND cp_notadecargo.id_notacargo NOT IN 
  (SELECT te_propuesta_pago_detalle.id_factura FROM te_propuesta_pago_detalle WHERE te_propuesta_pago_detalle.tipo_documento <> 0 UNION ALL
SELECT te_propuesta_pago_detalle_transferencia.id_factura FROM te_propuesta_pago_detalle_transferencia WHERE te_propuesta_pago_detalle_transferencia.tipo_documento <> 0) %s %s",
                $valCadBusq[0],
                $valCadBusq[0],
                $valCadBusq[1],
                $condicionNotIn, $sqlBusq);
  
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"2%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "10%", $pageNum, "numero_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero");
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "20%", $pageNum, "observacion_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "10%", $pageNum, "fecha_origen_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "10%", $pageNum, "saldo_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
				$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"validarCuentaSeleccionada(".$row['id_notacargo'].",1);\" title=\"Seleccionar Nota Cargo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".empresa($row['id_empresa'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_notacargo']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['observacion_notacargo'])."</td>";
			$htmlTb .= "<td>".date("d-m-Y",strtotime($row['fecha_origen_notacargo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['saldo_notacargo']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	
	}
							  
							  
	$objResponse->assign("tdListadoFacNcargo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	$objResponse->script("
		$('tblFacturasNcargos').style.display = '';");
	
	$objResponse->assign("tdFlotanteTituloDoc","innerHTML","Notas de Cargo");
	$objResponse->script("
		if ($('divFlotanteDoc').style.display == 'none') {
			$('divFlotanteDoc').style.display = '';
			centrarDiv($('divFlotanteDoc'));
			
			document.forms['frmBuscarDocumento'].reset();
			$('txtCriterioBusq').focus();
		}
	");

	return $objResponse;
	
}



function listarProveedores($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" WHERE CONCAT_WS('-',lrif,rif) LIKE %s
		OR CONCAT_WS('',lrif,rif) LIKE %s
		OR nombre LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id_proveedor AS id,
		CONCAT_WS('-',lrif,rif) as rif_proveedor,
		nombre
	FROM cp_proveedor %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "20%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Cedula / RIF.");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";
			$htmlTb .= "<td>"."<button type=\"button\"onclick=\"xajax_asignarProveedor('".$row['id']."','".$valCadBusq[0]."');\" title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarProveedores(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	
	$objResponse->assign("tdListadoProveedores","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	//$objResponse->assign("tdCabeceraEstado","innerHTML","");
	
	$objResponse->script("
		<!--$('trBuscarCliente').style.display = '';-->
		
		$('tblListadoProveedor').style.display = '';");
	
	$objResponse->assign("tdFlotanteTituloProv","innerHTML","Proveedores");
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if ($('divFlotanteProv').style.display == 'none') {
			$('divFlotanteProv').style.display = '';
			centrarDiv($('divFlotanteProv'));
			
			document.forms['frmBuscarCliente'].reset();
			$('txtCriterioBusqProveedor').focus();
		}
	");
	
	return $objResponse;
}

function listBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT bancos.idBanco, bancos.nombreBanco, bancos.sucursal FROM bancos INNER JOIN cuentas ON (cuentas.idBanco = bancos.idBanco) WHERE bancos.idBanco != '1' GROUP BY idBanco";
	$rsBanco = mysql_query($queryBanco) or die(mysql_error());
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitBanco = sprintf(" %s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitBanco = mysql_query($queryLimitBanco) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco) or die(mysql_error());
		$totalRows = mysql_num_rows($rsBanco);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
                $htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
                $htmlTh .= ordenarCampo("xajax_listBanco", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
                $htmlTh .= ordenarCampo("xajax_listBanco", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
                $htmlTh .= ordenarCampo("xajax_listBanco", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");					
        $htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitBanco)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarBanco('".$rowBanco['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['sucursal'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listBanco(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
		$objResponse->assign("tdDescripcion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
		$objResponse->script("$('divFlotante1').style.display = '';
							  $('tblBancos').style.display = '';
							  $('tdFlotanteTitulo1').innerHTML = 'Seleccione Banco';
							  centrarDiv($('divFlotante1'));");	
		
	return $objResponse;
}

function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$valCadBusq[0] = ($valCadBusq[0] == "") ? 0 : $valCadBusq[0];
	
        if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
        
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
        
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
		$totalRows = mysql_num_rows($rsEmpresa);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
            $htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
            $htmlTh .= ordenarCampo("xajax_listEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
            $htmlTh .= ordenarCampo("xajax_listEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");			
	$htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitEmpresa)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$rowBanco['id_empresa_reg']."',0);\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombre_empresa']." - ".$rowBanco['nombre_empresa_suc'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listEmpresa(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
		$objResponse->assign("tdDescripcion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
		$objResponse->script("$('divFlotante1').style.display = '';
							  $('tblBancos').style.display = '';
							  $('tdFlotanteTitulo1').innerHTML = 'Seleccione Empresa';
							  centrarDiv($('divFlotante1'))");	
	
	return $objResponse;
}

function verificarClave($valForm){
	$objResponse = new xajaxResponse();
	
	$queryClave = sprintf("SELECT contrasena FROM vw_pg_claves_modulos WHERE id_usuario = %s AND id_clave_modulo = 34",valTpDato($_SESSION['idUsuarioSysGts'],'int'));
	$rsClave = mysql_query($queryClave);
	if (!$rsClave) return $objResponse->alert(mysql_error()."\n\nLINE: "._LINE_);
	
	if (mysql_num_rows($rsClave)){
		$rowClave = mysql_fetch_array($rsClave);
		if ($rowClave['contrasena'] == $valForm['txtClaveAprobacion']){
			$objResponse->assign("hddPermiso","value",1);
			$objResponse->script("$('divFlotante2').style.display = 'none';");
		}
		else
			$objResponse->alert(utf8_encode("Clave Errada."));
	}
	else{
		$objResponse->alert("No tiene permiso para realizar esta accion");
		$objResponse->script("$('divFlotante').style.display = 'none';");
		$objResponse->script("$('divFlotante2').style.display = 'none';");
	}
	
	return $objResponse;
}

function buscarCliente($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBusqProveedor']);
	
	$objResponse->loadCommands(listarProveedores(0, "", "", $valBusq));
		
	return $objResponse;
}
function encabezadoEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (!($idEmpresa > 0)) {
		$idEmpresa = 100;
	}
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);

	if ($row['id_empresa'] != "") {
		$html .= "<table class=\"textoNegrita_7px\">";
		$html .= "<tr align=\"center\">";
			$html .= "<td>";
				$html .= "<img src=\"../".htmlentities($row['logo_familia'])."\" width=\"100\"/>";
			$html .= "</td>";
			$html .= "<td>";
				$html .= "<table width=\"250\">";
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= utf8_encode($row['nombre_empresa']);
					$html .= "</td>";
				$html .= "</tr>";
			if (strlen($row['rif']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>RIF: ";
						$html .= $row['rif'];
					$html .= "</td>";
				$html .= "</tr>";
			}
			if (strlen($row['direccion']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= utf8_encode($row['direccion']);
					$html .= "</td>";
				$html .= "</tr>";
			}
			if (strlen($row['web']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['web']);
					$html .= "</td>";
				$html .= "</tr>";
			}
				$html .= "</table>";
			$html .= "</td>";
		$html .= "</tr>";
		$html .= "</table>";

		$objResponse->assign("tdEncabezadoImprimir","innerHTML",$html);
	}
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"listarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"encabezadoEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarDocumento");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarFactura");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarFactura");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"cargarPropuesta");
$xajax->register(XAJAX_FUNCTION,"cargaSaldoCuenta");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"comboRetencionISLR");
$xajax->register(XAJAX_FUNCTION,"eliminarFactura");
$xajax->register(XAJAX_FUNCTION,"facturaSeleccionada");
$xajax->register(XAJAX_FUNCTION,"guardarPropuesta");
$xajax->register(XAJAX_FUNCTION,"listarFacturas");
$xajax->register(XAJAX_FUNCTION,"listarProveedores");
$xajax->register(XAJAX_FUNCTION,"listBanco");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"verificarClave");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");

function estadoFactura($estatus){

	if($estatus == 0)
		$respuesta .= " <img src=\"../img/iconos/ico_rojo.gif\">";
	else
		$respuesta .= " <img src=\"../img/iconos/ico_amarillo.gif\">";
	
	return $respuesta;
}

function proveedor($id_proveedor){
	$queryProveedor = sprintf("SELECT nombre FROM cp_proveedor WHERE id_proveedor = %s",$id_proveedor);
	$rsProveedor = mysql_query($queryProveedor);
	
	if (!$rsProveedor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$rowProveedor = mysql_fetch_array($rsProveedor);
	
	return utf8_encode($rowProveedor['nombre']);
}

function empresa($id){
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = utf8_encode($row['nombre_empresa']);
	
	return $respuesta;
}

function errorGuardarDcto($objResponse){
    $objResponse->script("desbloquearGuardado();");
}

?>