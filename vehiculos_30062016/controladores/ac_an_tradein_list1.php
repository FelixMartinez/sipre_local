<?php


function asignarCliente($idCliente, $idEmpresa, $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	WHERE id = %s
		AND id_empresa = %s
		AND status = 'Activo';",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$idClaveMovimiento = ($idClaveMovimiento == "") ? $rowCliente['id_clave_movimiento_predeterminado'] : $idClaveMovimiento;
	
	if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
		$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
			valTpDato($rowCliente['id_cliente_empresa'], "int"));
		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		$fechaVencimiento = suma_fechas("Y-m-d",date("d-m-Y"),$rowClienteCredito['diascredito']);
		
		$objResponse->assign("txtDiasCreditoCliente","value",$rowClienteCredito['diascredito']);
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
	} else {
		$fechaVencimiento = date("d-m-Y");
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
	$objResponse->assign("txtTelefonosCliente","value",utf8_encode($rowCliente['telf']));
	$objResponse->assign("txtRifCliente","value",utf8_encode($rowCliente['ci_cliente']));
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaCliente').click();");
	}
	
	return $objResponse;
}

function asignarClienteAdeudado($idCliente){
    $objResponse = new xajaxResponse();
    
    $queryCliente = sprintf("SELECT
                            cj_cc_cliente.id,
                            CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente		
                            FROM cj_cc_cliente		
                            WHERE id = %s AND status = 'Activo' LIMIT 1;",
                    valTpDato($idCliente, "int"));
    
	$rsCliente = mysql_query($queryCliente);
        if (!$rsCliente) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
        $rowCliente = mysql_fetch_assoc($rsCliente);
        
        $objResponse->assign("txtIdClienteDeuda","value",$rowCliente['id']);
        $objResponse->assign("txtNombreClienteDeuda","value",utf8_encode($rowCliente['nombre_cliente']));
        
        $objResponse->script("byId('btnCancelarListaClienteAdeudado').click();");
        
        return $objResponse;
}

function asignarTipoVale($idTipoVale) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("txtIdCliente","value","");
	$objResponse->assign("txtNombreCliente","value","");
	$objResponse->assign("hddIdDcto","value","");
	$objResponse->assign("txtNroDcto","value","");
	$idTipoVale = 1;
	if ($idTipoVale == 1) { // DE ENTRADA O SALIDA
		$objResponse->script("
		byId('txtIdCliente').className = 'inputHabilitado';
		//byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		//byId('lstTipoMovimiento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = false;
		byId('btnListarCliente').style.display = '';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value, '', '5,6');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
	} else if ($idTipoVale == 3) { // DE NOTA DE CREDITO DE CxC
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('asignarTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputHabilitado';
		byId('lstTipoMovimiento').className = 'inputInicial';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = '';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id,2);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value, '', '3');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",2);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", 2, "", "3"));
	} else {
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		byId('lstTipoMovimiento').className = 'inputInicial';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		//byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = true;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value);
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", -2));
	}
	
	return $objResponse;
}

function asignarUnidadBasica($nombreObjeto, $idUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
	FROM an_uni_bas uni_bas
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	WHERE id_uni_bas = %s;",
		valTpDato($idUnidadBasica,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtClaveUnidadBasica".$nombreObjeto, "value", utf8_encode($row['clv_uni_bas']));
	$objResponse->assign("txtDescripcion".$nombreObjeto, "value", utf8_encode($row['des_uni_bas']));
	$objResponse->assign("hddIdMarcaUnidadBasica".$nombreObjeto,"value",$row['id_marca']);
	$objResponse->assign("txtMarcaUnidadBasica".$nombreObjeto,"value",utf8_encode($row['nom_marca']));
	$objResponse->assign("hddIdModeloUnidadBasica".$nombreObjeto,"value",$row['id_modelo']);
	$objResponse->assign("txtModeloUnidadBasica".$nombreObjeto,"value",utf8_encode($row['nom_modelo']));
	$objResponse->assign("hddIdVersionUnidadBasica".$nombreObjeto,"value",$row['id_version']);
	$objResponse->assign("txtVersionUnidadBasica".$nombreObjeto,"value",utf8_encode($row['nom_version']));
	
	return $objResponse;
}

function asignarVehiculo($idUnidadFisica){
    $objResponse = new xajaxResponse();
    
    $query = sprintf("SELECT id_unidad_fisica, estado_compra, precio_compra
	FROM an_unidad_fisica 
	WHERE id_unidad_fisica = %s  LIMIT 1 ",
		valTpDato($idUnidadFisica,"int"));
	$rs = mysql_query($query);
        if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
    
        $objResponse->assign("txtIdUnidadFisicaAjuste","value",$row["id_unidad_fisica"]);
        $objResponse->assign("txtEstadoVenta","value",utf8_encode($row["estado_compra"]));
        $objResponse->assign("txtAcv","value",$row["precio_compra"]);
        
        $objResponse->script("copiarMonto('".$row["precio_compra"]."')");
        $objResponse->script("byId('divFlotante3').style.display = 'none';");
        
    return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmAjusteInventario) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmAjusteInventario['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarClienteAdeudado($txtCriterio){
    $objResponse = new xajaxResponse();
    
    $valBusq = sprintf("%s",
		$txtCriterio
                );
	
    $objResponse->loadCommands(listaClienteAdeudado(0, "id", "ASC", $valBusq));
	
    return $objResponse;
}


function buscarUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstEstadoCompraBuscar'],
		$frmBuscar['lstEstadoVentaBuscar'],
		$frmBuscar['lstAlmacen'],
		$frmBuscar['txtCriterio'],
                $frmBuscar['lstAnuladoTradein'],
                $frmBuscar['txtFechaDesde'],
                $frmBuscar['txtFechaHasta']
                );
	
	$objResponse->loadCommands(listaUnidadFisica(0, "CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarVehiculo($txtCriterio){
    $objResponse = new xajaxResponse();
    
    $valBusq = sprintf("%s",
		$txtCriterio
                );
	
    $objResponse->loadCommands(listaVehiculos(0, "uni_fis.id_unidad_fisica", "DESC", $valBusq));
	
    return $objResponse;
}

function cargaLstAlmacen($nombreObjeto, $idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_almacen alm
	WHERE alm.id_empresa = %s
	ORDER BY alm.nom_almacen",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".utf8_encode($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstAno($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = "SELECT id_ano, nom_ano FROM an_ano ORDER BY nom_ano DESC";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAno\" name=\"lstAno\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['id_ano']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=".$row['id_ano'].">".htmlentities($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAno","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\"  ".$accion." style=\"width:200px\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s AND id_clave_movimiento = 82 ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstColor($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_color ORDER BY nom_color");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_color']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_color']."\">".htmlentities($row['nom_color'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCondicion($selId = "") {
	$objResponse = new xajaxResponse();
	
	$array[1] = "Nuevo";			$array[2] = "Usado";		$array[3] = "Usado Particular";
	
	$html = "<select id=\"lstCondicion\" name=\"lstCondicion\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $indice) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCondicion","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoCompraBuscar($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste") ? $array[] = "ALTA" : "";
	($accion != "Ajuste") ? $array[] = "IMPRESO" : "";
	$array[] = "COMPRADO";
	$array[] = "REGISTRADO";
	($accion != "Ajuste") ? $array[] = "CANCELADO" : "";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Todos ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoVenta($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
               
//	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "TRANSITO" : "";
//	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "POR REGISTRAR" : "";
//	($accion != "Ajuste") ? $array[] = "SINIESTRADO" : "";
//	$array[] = "DISPONIBLE";
//	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "RESERVADO" : "";
//	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "VENDIDO" : "";
//	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "ENTREGADO" : "";
//	($accion != "Venta") ? $array[] = "PRESTADO" : "";
//	($accion != "Venta") ? $array[] = "ACTIVO FIJO" : "";
//	($accion != "Venta") ? $array[] = "INTERCAMBIO" : "";
//	($accion != "Venta") ? $array[] = "DEVUELTO" : "";
//	($accion != "Venta") ? $array[] = "ERROR EN TRASPASO" : "";
	
        //nuevo
        $array[] = "DISPONIBLE";
        
        if($selId == ''){
            $inputHabilitado = "class=\"inputHabilitado\"";            
        }
        
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$inputHabilitado." style=\"width:150px\">";
        if($selId == '') { //nuevo
            $html .= "<option value=\"-1\">[ Seleccione ]</option>"; 
            foreach ($array as $indice => $valor) {
                    $selected = ($selId == $array[$indice]) ? "selected=\"selected\"" : "";
                    
                    $html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
            }
        }else{ //cargando            
            $html .= "<option selected=\"selected\" value=\"".$selId."\">".$selId."</option>";//solo mostrar
        }
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoVentaBuscar($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "TRANSITO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "POR REGISTRAR" : "";
	($accion != "Ajuste") ? $array[] = "SINIESTRADO" : "";
	$array[] = "DISPONIBLE";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "RESERVADO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "VENDIDO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "ENTREGADO" : "";
	($accion != "Venta") ? $array[] = "PRESTADO" : "";
	($accion != "Venta") ? $array[] = "ACTIVO FIJO" : "";
	($accion != "Venta") ? $array[] = "INTERCAMBIO" : "";
	($accion != "Venta") ? $array[] = "DEVUELTO" : "";
	($accion != "Venta") ? $array[] = "ERROR EN TRASPASO" : "";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Todos ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['idmoneda']) {
			$selected = "selected=\"selected\"";
		} else if ($row['predeterminada'] == 1 && $selId == "") {
			$selected = "selected=\"selected\"";
		}
		
		$html .= "<option ".$selected." value=\"".$row['idmoneda']."\">".utf8_encode($row['descripcion']." (".$row['abreviacion'].")")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstPaisOrigen($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_origen ORDER BY nom_origen");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstPaisOrigen\" name=\"lstPaisOrigen\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_origen']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_origen']."\">".utf8_encode($row['nom_origen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstPaisOrigen","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoMovimiento($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
	
//	$array = array(
//		1 => "COMPRA",
//		2 => "ENTRADA",
//		3 => "VENTA",
//		4 => "SALIDA");
	$array = array(
		2 => "ENTRADA");
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" style=\"width:150px\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $indice) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".$indice.".- ".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);	
        
	return $objResponse;
}

function cargaLstUnidadBasica($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_uni_bas ORDER BY nom_uni_bas");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstUnidadBasica\" name=\"lstUnidadBasica\" class=\"inputHabilitado\" onchange=\"xajax_asignarUnidadBasica('Ajuste', this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uni_bas']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_uni_bas']."\">".htmlentities($row['nom_uni_bas'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUnidadBasica","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstUso($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_uso ORDER BY nom_uso");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstUso\" name=\"lstUso\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uso']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$row['id_uso']."\" ".$selected.">".htmlentities($row['nom_uso'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUso","innerHTML",$html);
	
	return $objResponse;
}

function formAjusteInventario($frmUnidadFisica, $existeUnidadFisica) {
	$objResponse = new xajaxResponse();
        
	if (!xvalidaAcceso($objResponse,"an_tradein_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarAjusteInventario').click();"); return $objResponse; }
	
	if ($existeUnidadFisica == 1) {
		$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
		//$objResponse->script("byId('trUnidadFisica').style.display = 'none';");
	} else {
		$idUnidadFisica = "";
		//$objResponse->script("byId('trUnidadFisica').style.display = '';");
	}
	
	// BUSCA LOS DATOS DE LA UNIDAD
	$query = sprintf("SELECT 
		alm.id_empresa,
		uni_fis.costo_compra,
		uni_fis.precio_compra
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
		INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
		LEFT JOIN an_color color_ext2 ON (uni_fis.id_color_externo2 = color_ext2.id_color)
		LEFT JOIN an_color color_int2 ON (uni_fis.id_color_interno2 = color_int2.id_color)
		LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
		INNER JOIN an_clase clase ON (uni_fis.id_clase = clase.id_clase)
		INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
		INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
		INNER JOIN an_combustible comb ON (uni_bas.com_uni_bas = comb.id_combustible)
	WHERE uni_fis.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($frmUnidadFisica['hddEstadoVenta'] == "DISPONIBLE" && $frmUnidadFisica['lstEstadoVenta'] != "DISPONIBLE") {
		$lstTipoMovimiento = 4;
		$documentoGenera = 5; // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
	} else if ($frmUnidadFisica['hddEstadoVenta'] != "DISPONIBLE" && $frmUnidadFisica['lstEstadoVenta'] == "DISPONIBLE") {
		$lstTipoMovimiento = 2;
		$documentoGenera = 6; // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
	}
	
	if ($existeUnidadFisica == 1) {            
		$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "Empresa", "ListaEmpresa", "", false));
		
		$objResponse->call("selectedOption","lstTipoVale",1);
		$objResponse->loadCommands(asignarTipoVale(1));
		$objResponse->loadCommands(cargaLstTipoMovimiento("lstTipoMovimiento", $lstTipoMovimiento));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", 2, $lstTipoMovimiento, "", $documentoGenera));
		$objResponse->assign("txtIdUnidadFisicaAjuste","value",$idUnidadFisica);
		$objResponse->assign("txtEstadoVenta","value",$frmUnidadFisica['lstEstadoVenta']);
		$objResponse->assign("txtSubTotal","value",number_format($row['precio_compra'], 2, ".", ","));
		
		$objResponse->script("
		byId('lstTipoVale').onchange = function() {
			selectedOption(this.id, ".(1).");
		}
		byId('txtSubTotal').readOnly = true;
		//byId('txtSubTotal').className = 'inputInicial';");
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id, ".$lstTipoMovimiento.");
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', 2, this.value, '', ".$documentoGenera.");
		}");
	} else {            
            
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));"));
		$objResponse->assign("txtFecha","value",date("d-m-Y"));
		$objResponse->assign("hddIdEmpleado","value",$rowUsuario['id_empleado']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']));
		
		$objResponse->loadCommands(asignarTipoVale(""));
		$objResponse->loadCommands(cargaLstTipoMovimiento("lstTipoMovimiento", $lstTipoMovimiento));
		
		$objResponse->loadCommands(cargaLstUnidadBasica());
		
		$objResponse->loadCommands(cargaLstAno());
		$objResponse->loadCommands(cargaLstCondicion());
		
		$objResponse->loadCommands(cargaLstColor("lstColorExterno1"));
		$objResponse->loadCommands(cargaLstColor("lstColorInterno1"));
		$objResponse->loadCommands(cargaLstColor("lstColorExterno2"));
		$objResponse->loadCommands(cargaLstColor("lstColorInterno2"));
		
		$objResponse->loadCommands(cargaLstPaisOrigen());
		$objResponse->loadCommands(cargaLstUso());
		
		$objResponse->loadCommands(cargaLstAlmacen('lstAlmacenAjuste', $_SESSION['idEmpresaUsuarioSysGts']));
		$objResponse->assign("txtEstadoCompraAjuste","value","REGISTRADO");
		$objResponse->loadCommands(cargaLstEstadoVenta("lstEstadoVentaAjuste", "Ajuste"));
		$objResponse->loadCommands(cargaLstMoneda());
		
		$objResponse->script("
		byId('lstTipoVale').onchange = function() {
			xajax_asignarTipoVale(this.value);
		}
		//byId('txtSubTotal').readOnly = false;
		//byId('txtSubTotal').className = 'inputHabilitado';");
	}
	
	errorGuardarUnidadFisica($objResponse);
	
	return $objResponse;
}

function formTradeIn($idTradein) {
	$objResponse = new xajaxResponse();
	
        if (!xvalidaAcceso($objResponse,"an_tradein_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarGenerarAnticipo').click();"); return $objResponse; }
        
        $query = sprintf("SELECT 
                        an_tradein.id_tradein,
                        an_tradein.id_unidad_fisica,
                        an_tradein.allowance,
                        an_tradein.payoff,
                        an_tradein.acv,
                        an_tradein.id_cliente,
                        cj_cc_anticipo.montoNetoAnticipo,
                        cj_cc_anticipo.numeroAnticipo,
                        cj_cc_anticipo.idDepartamento,
                        cj_cc_anticipo.id_empresa,
                        cj_cc_anticipo.observacionesAnticipo,
                        pg_modulos.descripcionModulo,
                        cj_cc_cliente.id,
                        CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) as nombre_cliente,
                        cj_cc_detalleanticipo.id_concepto,
                        cj_conceptos_formapago.descripcion,
                        formapagos.idFormaPago,
                        formapagos.nombreFormaPago,
                        (SELECT CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) FROM cj_cc_cliente WHERE cj_cc_cliente.id = an_tradein.id_cliente) as nombre_cliente_adeudado
                        
                        FROM an_tradein
                        INNER JOIN cj_cc_anticipo ON an_tradein.id_anticipo = cj_cc_anticipo.idAnticipo
                        INNER JOIN pg_modulos ON cj_cc_anticipo.idDepartamento = pg_modulos.id_modulo
                        INNER JOIN cj_cc_cliente ON cj_cc_anticipo.idCliente = cj_cc_cliente.id
                        INNER JOIN cj_cc_detalleanticipo ON cj_cc_anticipo.idAnticipo = cj_cc_detalleanticipo.idAnticipo
                        INNER JOIN cj_conceptos_formapago ON cj_cc_detalleanticipo.id_concepto = cj_conceptos_formapago.id_concepto
                        INNER JOIN formapagos ON cj_conceptos_formapago.id_formapago = formapagos.idFormaPago
                        WHERE id_tradein = %s LIMIT 1",
                        valTpDato($idTradein,"int"));
        
        $rs = mysql_query($query);
        if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
        $row = mysql_fetch_assoc($rs);
                
        $objResponse->assign("txtIdCliente2", "value", $row['id']);
        $objResponse->assign("txtNombreCliente2", "value", utf8_encode($row['nombre_cliente']));
        $objResponse->assign("hddIdEmpresa", "value", $row['id_empresa']);
        
        $objResponse->assign("hddIdTradein", "value", $row['id_tradein']);
        
        $objResponse->assign("txtAllowance2", "value", $row['allowance']);
        $objResponse->assign("txtPayoff2", "value", $row['payoff']);
        $objResponse->assign("txtAcv2", "value", $row['acv']);
        
        $objResponse->assign("txtNumeroAnticipo2", "value", $row['numeroAnticipo']);
        $objResponse->assign("hddIdModulo", "value", $row['idDepartamento']);
        $objResponse->assign("tdlstModulo2", "innerHTML", $row['descripcionModulo']);
        $objResponse->assign("txtMontoAnticipo2", "value", $row['montoNetoAnticipo']);
        
        $objResponse->assign("hddIdTipoPago", "value", $row['idFormaPago']);
        $objResponse->assign("selTipoPago2", "value", $row['nombreFormaPago']);
        $objResponse->assign("hddIdConceptoPago", "value", $row['id_concepto']);
        $objResponse->assign("selConceptoPago2", "value", $row['descripcion']);
        $objResponse->assign("txtSubTotal2", "value", $row['montoNetoAnticipo']);
        
        
        $objResponse->assign("txtIdClienteDeuda2", "value", $row['id_cliente']);
        $objResponse->assign("txtNombreClienteDeuda2", "value", utf8_encode($row['nombre_cliente_adeudado']));
        
        $objResponse->assign("txtObservacion2", "value", utf8_encode($row['observacionesAnticipo']));
        
        
        $idUnidadFisica = $row["id_unidad_fisica"];
        
	if ($idUnidadFisica > 0) {
		
		
		$query = sprintf("SELECT 
			uni_fis.id_unidad_fisica,
			uni_bas.id_uni_bas,
			uni_bas.nom_uni_bas,
			uni_bas.clv_uni_bas,
			uni_bas.des_uni_bas,
			marca.nom_marca,
			modelo.nom_modelo,
			vers.nom_version,
			ano.nom_ano,
			uni_fis.placa,
			(CASE uni_fis.id_condicion_unidad
				WHEN 1 THEN	'NUEVO'
				WHEN 2 THEN	'USADO'
				WHEN 3 THEN	'USADO PARTICULAR'
			END) AS condicion_unidad, 
			uni_fis.kilometraje,
			uni_fis.fecha_fabricacion,
			uni_bas.imagen_auto,
			alm.nom_almacen,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			color_ext1.nom_color AS color_externo,
			color_int1.nom_color AS color_interno,
			color_ext2.nom_color AS color_externo2,
			color_int2.nom_color AS color_externo2,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.registro_legalizacion,
			uni_fis.registro_federal,
			pais_origen.nom_origen,
			clase.nom_clase,
			uso.nom_uso,
			uni_bas.pto_uni_bas,
			uni_bas.cil_uni_bas,
			uni_bas.ccc_uni_bas,
			uni_bas.cab_uni_bas,
			trans.nom_transmision,
			comb.nom_combustible,
			uni_bas.cap_uni_bas,
			uni_bas.uni_uni_bas,
			uni_bas.anos_de_garantia,
			uni_bas.kilometraje AS kilometraje_garantia,
			uni_fis.fecha_fabricacion,
			uni_fis.serial1,
			uni_fis.codigo_unico_conversion,
			uni_fis.marca_kit,
			uni_fis.modelo_regulador,
			uni_fis.serial_regulador,
			uni_fis.marca_cilindro,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
			INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
			LEFT JOIN an_color color_ext2 ON (uni_fis.id_color_externo2 = color_ext2.id_color)
			LEFT JOIN an_color color_int2 ON (uni_fis.id_color_interno2 = color_int2.id_color)
			LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
			INNER JOIN an_clase clase ON (uni_fis.id_clase = clase.id_clase)
			INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
			INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
			INNER JOIN an_combustible comb ON (uni_bas.com_uni_bas = comb.id_combustible)
		WHERE uni_fis.id_unidad_fisica = %s;",
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("txtIdUnidadFisica", "value", $row['id_unidad_fisica']);
		$objResponse->assign("hddIdUnidadBasica", "value", $row['id_uni_bas']);
		$objResponse->assign("txtNombreUnidadBasica", "value", utf8_encode($row['nom_uni_bas']));
		$objResponse->assign("txtClaveUnidadBasica", "value", utf8_encode($row['clv_uni_bas']));
		$objResponse->assign("txtDescripcion", "innerHTML", utf8_encode($row['des_uni_bas']));
		$objResponse->assign("txtMarcaUnidadBasica", "value", utf8_encode($row['nom_marca']));
		$objResponse->assign("txtModeloUnidadBasica", "value", utf8_encode($row['nom_modelo']));
		$objResponse->assign("txtVersionUnidadBasica", "value", utf8_encode($row['nom_version']));
		$objResponse->assign("txtAno", "value", utf8_encode($row['nom_ano']));
		$objResponse->assign("txtPlaca", "value", utf8_encode($row['placa']));
		$objResponse->assign("txtKilometraje2", "value", $row['kilometraje']);
		$objResponse->assign("txtCondicion", "value", utf8_encode($row['condicion_unidad']));
		$txtFechaFabricacion = ($row['fecha_fabricacion'] != "") ? date("d-m-Y", strtotime($row['fecha_fabricacion'])) : "----------";
		$objResponse->assign("txtFechaFabricacion", "value", $txtFechaFabricacion);
		$objResponse->assign("txtAlmacen", "value", utf8_encode($row['nom_almacen']));
		$objResponse->assign("txtEstadoCompra", "value", utf8_encode($row['estado_compra']));
		$objResponse->loadCommands(cargaLstEstadoVenta("lstEstadoVenta", "Ajuste", utf8_encode($row['estado_venta'])));
		$objResponse->assign("hddEstadoVenta", "value", utf8_encode($row['estado_venta']));
	
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		$objResponse->assign("imgArticulo","src",$imgFoto);
		$objResponse->assign("hddUrlImagen","value",$row['imagen_auto']);
		
		$objResponse->assign("txtColorExterno1", "value", utf8_encode($row['color_externo']));
		$objResponse->assign("txtColorInterno1", "value", utf8_encode($row['color_interno']));
		$objResponse->assign("txtColorExterno2", "value", utf8_encode($row['color_externo2']));
		$objResponse->assign("txtColorInterno2", "value", utf8_encode($row['color_interno2']));
		
		$objResponse->assign("txtSerialCarroceria", "value", utf8_encode($row['serial_carroceria']));
		$objResponse->assign("txtSerialMotor", "value", utf8_encode($row['serial_motor']));
		$objResponse->assign("txtNumeroVehiculo", "value", utf8_encode($row['serial_chasis']));
		$objResponse->assign("txtRegistroLegalizacion", "value", utf8_encode($row['registro_legalizacion']));
		$objResponse->assign("txtRegistroFederal", "value", utf8_encode($row['registro_federal']));
		
		$objResponse->assign("txtPaisOrigen", "value", utf8_encode($row['nom_origen']));
		$objResponse->assign("txtClase", "value", utf8_encode($row['nom_clase']));
		$objResponse->assign("txtUso", "value", utf8_encode($row['nom_uso']));
		$objResponse->assign("txtNumeroPuertas", "value", utf8_encode($row['pto_uni_bas']));
		$objResponse->assign("txtNumeroCilindros", "value", utf8_encode($row['cil_uni_bas']));
		$objResponse->assign("txtCilindrada", "value", utf8_encode($row['ccc_uni_bas']));
		$objResponse->assign("txtCaballosFuerza", "value", utf8_encode($row['cab_uni_bas']));
		$objResponse->assign("txtTransmision", "value", utf8_encode($row['nom_transmision']));
		$objResponse->assign("txtCombustible", "value", utf8_encode($row['nom_combustible']));
		$objResponse->assign("txtCapacidad", "value", $row['cap_uni_bas']);
		$objResponse->assign("txtUnidad", "value", $row['uni_uni_bas']);
		$objResponse->assign("txtAnoGarantia", "value", $row['anos_de_garantia']);
		$objResponse->assign("txtKmGarantia", "value", number_format($row['kilometraje_garantia'], 2, ".", ","));
		
		if (strlen($row['serial1']) > 0) {
			$objResponse->script("byId('trSistemaGNV').style.display = '';");
		}
		$objResponse->assign("txtSerial1", "value", utf8_encode($row['serial1']));
		$objResponse->assign("txtCodigoUnico", "value", utf8_encode($row['codigo_unico_conversion']));
		$objResponse->assign("txtMarcaKit", "value", utf8_encode($row['marca_kit']));
		$objResponse->assign("txtModeloRegulador", "value", utf8_encode($row['modelo_regulador']));
		$objResponse->assign("txtSerialRegulador", "value", utf8_encode($row['serial_regulador']));
		$objResponse->assign("txtMarcaCilindro", "value", utf8_encode($row['marca_cilindro']));
		$objResponse->assign("txtCapacidadCilindro", "value", utf8_encode($row['capacidad_cilindro']));
		$txtFechaCilindro = ($row['fecha_elaboracion_cilindro'] != "") ? date("d-m-Y", strtotime($row['fecha_elaboracion_cilindro'])) : "----------";
		$objResponse->assign("txtFechaCilindro", "value", $txtFechaCilindro);
	}
	
	return $objResponse;
}

function guardarAjusteInventario($frmAjusteInventario, $frmUnidadFisica, $frmListaUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_tradein_list","insertar")) { errorGuardarAjusteInventario($objResponse); return $objResponse; }        
	
        $arrayApertura = validarAperturaCaja(0);//diferente de 1 para que devuelva array y no xajax
        
        if($arrayApertura[0] === false) {errorGuardarAjusteInventario($objResponse); return $objResponse->alert($arrayApertura[1]); }
        
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	
	if ($frmAjusteInventario['lstTipoVale'] == 1) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		$idClaveMovimiento = $frmAjusteInventario['lstClaveMovimiento'];
	} else if ($frmAjusteInventario['lstTipoVale'] == 3) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		switch ($frmAjusteInventario['lstTipoMovimiento']) { // 2 = ENTRADA, 4 = SALIDA
			case 2 : $documentoGenera = 6; break;
			case 4 : $documentoGenera = 5; break;
		}
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.tipo = %s
			AND clave_mov.documento_genera = %s
			AND clave_mov.id_modulo IN (2)
		ORDER BY clave DESC 
		LIMIT 1;",
			valTpDato($frmAjusteInventario['lstTipoMovimiento'], "int"),
			valTpDato($documentoGenera, "int"));
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
		
		$idClaveMovimiento = $rowClaveMov['id_clave_movimiento'];
	}
	
	if (!($frmAjusteInventario['txtIdUnidadFisicaAjuste'] > 0)) {
		$idUnidadBasica = $frmAjusteInventario['lstUnidadBasica'];
		$txtEstadoVenta = $frmAjusteInventario['lstEstadoVentaAjuste'];
		
		// BUSCA LOS DATOS DE LA UNIDAD BASICA
		$queryUnidadBasica = sprintf("SELECT * FROM an_uni_bas
		WHERE id_uni_bas = %s;",
			valTpDato($idUnidadBasica, "int"));
		$rsUnidadBasica = mysql_query($queryUnidadBasica);
		if (!$rsUnidadBasica) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
		
		$insertSQL = sprintf("INSERT INTO an_unidad_fisica (id_uni_bas, ano, id_uso, id_clase, capacidad, id_condicion_unidad, kilometraje, id_color_externo1, id_color_externo2, id_color_interno1, id_color_interno2, id_origen, id_almacen, serial_carroceria, serial_motor, serial_chasis, placa, fecha_fabricacion, registro_legalizacion, registro_federal, descuento_compra, porcentaje_iva_compra, iva_compra, porcentaje_impuesto_lujo_compra, impuesto_lujo_compra, costo_compra, moneda_costo_compra, tasa_cambio_costo_compra, precio_compra, moneda_precio_compra, tasa_cambio_precio_compra, marca_cilindro, capacidad_cilindro, fecha_elaboracion_cilindro, marca_kit, modelo_regulador, serial_regulador, codigo_unico_conversion, serial1, descripcion_siniestro, fecha_pago_venta, estado_compra, estado_venta, estatus, fecha_ingreso, propiedad)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idUnidadBasica, "int"),
			valTpDato($frmAjusteInventario['lstAno'], "int"),
			valTpDato($rowUnidadBasica['tip_uni_bas'], "int"),
			valTpDato($rowUnidadBasica['cla_uni_bas'], "int"),
			valTpDato($rowUnidadBasica['cap_uni_bas'], "real_inglesa"),
			valTpDato($frmAjusteInventario['lstCondicion'], "int"),
			valTpDato($frmAjusteInventario['txtKilometraje'], "int"),
			valTpDato($frmAjusteInventario['lstColorExterno1'], "int"),
			valTpDato($frmAjusteInventario['lstColorExterno2'], "int"),
			valTpDato($frmAjusteInventario['lstColorInterno1'], "int"),
			valTpDato($frmAjusteInventario['lstColorInterno2'], "int"),
			valTpDato($rowUnidadBasica['ori_uni_bas'], "int"),
			valTpDato($frmAjusteInventario['lstAlmacenAjuste'], "int"),
			valTpDato($frmAjusteInventario['txtSerialCarroceriaAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtSerialMotorAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtNumeroVehiculoAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtPlacaAjuste'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmAjusteInventario['txtFechaFabricacionAjuste'])), "date"),
			valTpDato($frmAjusteInventario['txtRegistroLegalizacionAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtRegistroFederalAjuste'], "text"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['lstMoneda'], "int"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['lstMoneda'], "int"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtMarcaCilindro'], "text"),
			valTpDato($frmAjusteInventario['txtCapacidadCilindro'], "text"),
			valTpDato($frmAjusteInventario['txtFechaCilindro'], "text"),
			valTpDato($frmAjusteInventario['txtMarcaKit'], "text"),
			valTpDato($frmAjusteInventario['txtModeloRegulador'], "text"),
			valTpDato($frmAjusteInventario['txtSerialRegulador'], "text"),
			valTpDato($frmAjusteInventario['txtCodigoUnico'], "text"),
			valTpDato($frmAjusteInventario['txtSerial1'], "text"),
			valTpDato("", "text"),
			valTpDato("", "date"),
			valTpDato($frmAjusteInventario['txtEstadoCompraAjuste'], "text"),
			valTpDato($txtEstadoVenta, "text"),
			valTpDato(1, "boolean"), // Null = Anulada, 1 = Activa
			valTpDato("NOW()", "campo"),
			valTpDato("PROPIO", "text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nQuery:".$insertSQL); }
		$idUnidadFisica = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	} else {
		$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
		$idUnidadBasica = $frmUnidadFisica['hddIdUnidadBasica'];
		$txtEstadoVenta = $frmAjusteInventario['txtEstadoVenta'];
	}
	
	if ($frmAjusteInventario['lstTipoVale'] == 3) {
		// INSERTA LA UNIDAD EN EL DETALLE DE LA NOTA DE CREDITO
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo (id_nota_credito, id_unidad_fisica, costo_compra, precio_unitario)
		VALUE (%s, %s, %s, %s);",
			valTpDato($frmAjusteInventario['hddIdDcto'], "int"),
			valTpDato($idUnidadFisica, "int"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nQuery:".$insertSQL); }
		$idNotaCreditoDetalleVehiculo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// BUSCA LOS DATOS DE LOS IMPUESTOS DE LA UNIDAD
		$queryUnidadBasicaImpuesto = sprintf("SELECT
			iva.idIva AS id_iva,
			iva.iva,
			iva.observacion
		FROM an_unidad_basica_impuesto uni_bas_impuesto
			INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo IN (6,2);",
			valTpDato($idUnidadBasica, "int"));
		$rsUnidadBasicaImpuesto = mysql_query($queryUnidadBasicaImpuesto);
		if (!$rsUnidadBasicaImpuesto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowUnidadBasicaImpuesto = mysql_fetch_assoc($rsUnidadBasicaImpuesto)) {
			$hddIdIvaItm = $rowUnidadBasicaImpuesto['id_iva'];
			$hddIvaItm = $rowUnidadBasicaImpuesto['iva'];
			
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo_impuesto (id_nota_credito_detalle_vehiculo, id_impuesto, impuesto) 
			VALUE (%s, %s, %s);",
				valTpDato($idNotaCreditoDetalleVehiculo, "int"),
				valTpDato($hddIdIvaItm, "int"),
				valTpDato($hddIvaItm, "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
			
		$arrayDetIdDctoContabilidad[0] = $frmAjusteInventario['hddIdDcto'];
		$arrayDetIdDctoContabilidad[1] = $idModulo;
		$arrayDetIdDctoContabilidad[2] = "NOTA_CREDITO";
		$arrayIdDctoContabilidad[] = $arrayDetIdDctoContabilidad;
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
	if (!$rsNumeracion) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	switch ($frmAjusteInventario['lstTipoMovimiento']) {
		case 2 : // ENTRADA
			// REGISTRA EL VALE DE ENTRADA
			$insertSQL = sprintf("INSERT INTO an_vale_entrada (numeracion_vale_entrada, id_empresa, fecha, id_documento, id_unidad_fisica, id_cliente, subtotal_factura, tipo_vale_entrada, observacion)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($numeroActual, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato($frmAjusteInventario['hddIdDcto'], "int"),
				valTpDato($idUnidadFisica, "int"),
				valTpDato($frmAjusteInventario['txtIdCliente'], "int"),
				valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
				valTpDato($frmAjusteInventario['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
				valTpDato($frmAjusteInventario['txtObservacion'], "text"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idVale = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$arrayDetIdDctoContabilidad[0] = $idVale;
			$arrayDetIdDctoContabilidad[1] = $idModulo;
			$arrayDetIdDctoContabilidad[2] = "ENTRADA";
			$arrayIdDctoContabilidad[] = $arrayDetIdDctoContabilidad;
			
			$estadoKardex = 0;
			break;
		case 4 : // SALIDA
			// REGISTRA EL VALE DE SALIDA
			$insertSQL = sprintf("INSERT INTO an_vale_salida (numeracion_vale_salida, id_empresa, fecha, id_unidad_fisica, id_cliente, subtotal_factura, tipo_vale_salida, observacion)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($numeroActual, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato($idUnidadFisica, "int"),
				valTpDato($frmAjusteInventario['txtIdCliente'], "int"),
				valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
				valTpDato($frmAjusteInventario['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
				valTpDato($frmAjusteInventario['txtObservacion'], "text"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idVale = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$arrayDetIdDctoContabilidad[0] = $idVale;
			$arrayDetIdDctoContabilidad[1] = $idModulo;
			$arrayDetIdDctoContabilidad[2] = "SALIDA";
			$arrayIdDctoContabilidad[] = $arrayDetIdDctoContabilidad;
			
			$estadoKardex = 1;
			break;
	}
	
	// REGISTRA EL MOVIMIENTO DEL ARTICULO
	$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idVale, "int"),
		valTpDato($idUnidadBasica, "int"),
		valTpDato($idUnidadFisica, "int"),
		valTpDato($frmAjusteInventario['lstTipoMovimiento'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($frmAjusteInventario['lstClaveMovimiento'], "int"),
		valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato(1, "real_inglesa"),
		valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
		valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($estadoKardex, "int"), // 0 = Entrada, 1 = Salida
		valTpDato("NOW()", "campo"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTADO DE VENTA DE LA UNIDAD FÍSICA
	$updateSQL = sprintf("UPDATE an_unidad_fisica SET
		estado_venta = %s
	WHERE id_unidad_fisica = %s;",
		valTpDato($txtEstadoVenta, "text"),
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTADO DE LA UNIDAD FÍSICA
	$updateSQL = sprintf("UPDATE an_unidad_fisica SET
		estatus = %s
	WHERE id_unidad_fisica = %s
		AND estado_venta IN ('DEVUELTO');",
		valTpDato(NULL, "int"), // NULL = Anulada, 1 = Activa
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
        $arrayAnticipo = guardarAnticipo($frmAjusteInventario);
        
        if($arrayAnticipo[0] == false){ errorGuardarAjusteInventario($objResponse); return $objResponse->alert($arrayAnticipo[1]); }
        
        $idAnticipo = $arrayAnticipo[0];
        $objResponse->script($arrayAnticipo[1]);//abre ventana de anticipo impresion
                
        $sql = sprintf("INSERT INTO an_tradein (id_anticipo, id_unidad_fisica, id_cliente, id_vale_entrada, allowance, payoff, acv, id_empleado, tipo_registro) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 1)",
                valTpDato($idAnticipo,"int"),
                valTpDato($idUnidadFisica,"int"),
                valTpDato($frmAjusteInventario['txtIdClienteDeuda'],"int"),
                valTpDato($idVale,"int"),
                valTpDato($frmAjusteInventario['txtAllowance'], "real_inglesa"),
                valTpDato($frmAjusteInventario['txtPayoff'], "real_inglesa"),
                valTpDato($frmAjusteInventario['txtAcv'], "real_inglesa"),
                valTpDato($_SESSION["idEmpleadoSysGts"],"int")
                );
        
        $rs = mysql_query($sql);
        if (!$rs) { errorGuardarAjusteInventario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
        
	mysql_query("COMMIT;");
        
        // MODIFICADO ERNESTO
	if (function_exists("generarAnticiposVe")) { generarAnticiposVe($idAnticipo,"",""); }
	// MODIFICADO ERNESTO

	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "ENTRADA") {
				$idVale = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeEntradaRe")) { generarValeEntradaRe($idVale,"",""); } break;
					case 2 : if (function_exists("generarValeEntradaVe")) { generarValeEntradaVe($idVale,"",""); } break;
				}
			} else if ($tipoDcto == "SALIDA") {
				$idVale = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeSalidaRe")) { generarValeSalidaRe($idVale,"",""); } break;
					case 1 : if (function_exists("generarValeSe")) { generarValeSe($idVale,"",""); } break;
					case 2 : if (function_exists("generarValeSalidaVe")) { generarValeSalidaVe($idVale,"",""); } break;
				}
			} else if ($tipoDcto == "NOTA_CREDITO") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 2 : if (function_exists("generarNotasVentasVe")) { generarNotasVentasVe($idNotaCredito,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	errorGuardarAjusteInventario($objResponse);
	$objResponse->alert(utf8_encode("Vale Guardado con exito, se genero anticipo"));
		
	switch ($frmAjusteInventario['lstTipoMovimiento']) {
		case 2 : $objResponse->script(sprintf("verVentana('an_ajuste_inventario_vale_entrada_imp.php?id=%s', 960, 550);", $idVale)); break;
		case 4 : $objResponse->script(sprintf("verVentana('an_ajuste_inventario_vale_salida_imp.php?id=%s', 960, 550);", $idVale)); break;
	}
	
	$objResponse->script("byId('btnCancelarAjusteInventario').click();");
	$objResponse->script("byId('btnCancelarGenerarAnticipo').click();");
	
	$objResponse->loadCommands(listaUnidadFisica(
		$frmListaUnidadFisica['pageNum'],
		$frmListaUnidadFisica['campOrd'],
		$frmListaUnidadFisica['tpOrd'],
		$frmListaUnidadFisica['valBusq']));
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CR&Eacute;DITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['ci_cliente'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
        
	return $objResponse;
}

function listaClienteAdeudado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CR&Eacute;DITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
//	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
//		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
//		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
//			valTpDato($valCadBusq[0], "int"));
//	}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		#cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
		#INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) 
                %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
        if (!$rsLimit) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
                if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaClienteAdeudado", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaClienteAdeudado", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaClienteAdeudado", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaClienteAdeudado", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarClienteAdeudado('".$row['id']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['ci_cliente'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClienteAdeudado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClienteAdeudado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaClienteAdeudado(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClienteAdeudado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClienteAdeudado(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaClienteAdeudado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
        $objResponse->script("byId('divFlotante4').style.display = '';");
        $objResponse->script("centrarDiv(byId('divFlotante4'));");
	
	return $objResponse;
}


function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('DISPONIBLE', 'PRESTADO', 'ACTIVO FIJO', 'INTERCAMBIO', 'DEVUELTO', 'ERROR EN TRASPASO')");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = alm.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
		
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_compra LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
		
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_venta LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
		
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_almacen = %s",
			valTpDato($valCadBusq[3], "text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s
		OR CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) LIKE %s
		OR cj_cc_anticipo.numeroAnticipo LIKE %s
		OR numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
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
        
        if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
                if($valCadBusq[5] == "0"){
                    $sqlBusq .= $cond.sprintf("an_tradein.anulado IS NULL");
                }elseif($valCadBusq[5] == "1"){
                    $sqlBusq .= $cond.sprintf("an_tradein.anulado = %s",
                            valTpDato($valCadBusq[5],"int"));
                }
	}
        
        if ($valCadBusq[6] != "" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(an_tradein.tiempo_registro) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[6])),"date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[7])),"date"));
	}
        
	
	$query = sprintf("SELECT DISTINCT
		vw_iv_modelo.id_uni_bas,
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) 
                INNER JOIN an_tradein ON uni_fis.id_unidad_fisica = an_tradein.id_unidad_fisica
                INNER JOIN cj_cc_anticipo ON an_tradein.id_anticipo = cj_cc_anticipo.IdAnticipo
                INNER JOIN cj_cc_cliente ON cj_cc_anticipo.idCliente = cj_cc_cliente.id                
                %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
        
        
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "1%", $pageNum, "anulado", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Unidad F&iacute;sica"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "4%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Anticipo"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "tiempo_registro", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Registro"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Color"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanPlaca));
		//$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Ingreso"));
		//$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "4%", $pageNum, "(TO_DAYS(NOW()) - TO_DAYS(fact_comp.fecha_origen))", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("D&iacute;as"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Estado Venta"));
		//$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Asignaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Almac&eacute;n"));
		//$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "7%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Fact. Compra"));		
                $htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "saldoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo Anticipo"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "montoNetoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total Anticipo"));		
                $htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "", $pageNum, "costo_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Costo"));
		$htmlTh .= "<td colspan=\"3\" width=\"0%\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"50\">".utf8_encode($row['vehiculo'])."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'], "int"));
		
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
                        cj_cc_anticipo.idAnticipo,
                        cj_cc_anticipo.numeroAnticipo,                        
                        cj_cc_anticipo.id_empresa as id_empresa_anticipo,                        
                        cj_cc_anticipo.estatus,                        
                        cj_cc_anticipo.saldoAnticipo,
                        cj_cc_anticipo.montoNetoAnticipo,
                        an_tradein.id_tradein,
                        an_tradein.tiempo_registro,
                        an_tradein.anulado,
                        an_tradein.id_vale_entrada,
                        cj_cc_cliente.id,
                        CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) as nombre_cliente,
                        pg_reportesimpresion.numeroReporteImpresion,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			fact_comp.fecha_origen,
			(TO_DAYS(NOW()) - TO_DAYS(fact_comp.fecha_origen)) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			fact_comp.numero_factura_proveedor,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
                        INNER JOIN an_tradein ON uni_fis.id_unidad_fisica = an_tradein.id_unidad_fisica
                        INNER JOIN cj_cc_anticipo ON an_tradein.id_anticipo = cj_cc_anticipo.IdAnticipo
                        INNER JOIN cj_cc_cliente ON cj_cc_anticipo.idCliente = cj_cc_cliente.id                        
                        INNER JOIN pg_reportesimpresion ON cj_cc_anticipo.idAnticipo = pg_reportesimpresion.idDocumento AND pg_reportesimpresion.tipoDocumento = 'AN' AND pg_reportesimpresion.id_departamento = 2
                        %s %s", $sqlBusq, $sqlBusq2);
		$queryUnidadFisica .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$subTotalCosto = 0;
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
                        if($rowUnidadFisica['anulado'] === "1"){ $imgActivo = "../img/iconos/ico_rojo.gif"; } else { $imgActivo = "../img/iconos/ico_verde.gif"; }
                        
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\"><img src=\"".$imgActivo."\" /></td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
				$htmlTb .= "<td align=\"center\" title=\" id_anticipo: ".$rowUnidadFisica['idAnticipo']."\">".$rowUnidadFisica['numeroAnticipo']."</td>";
				$htmlTb .= "<td align=\"center\" >".date("d-m-Y",strtotime($rowUnidadFisica['tiempo_registro']))."</td>";
				$htmlTb .= "<td align=\"center\" title=\" id: ".$rowUnidadFisica['id']."\">".$rowUnidadFisica['nombre_cliente']."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
				//$htmlTb .= "<td align=\"center\">".implode("-",array_reverse(explode("-",$rowUnidadFisica['fecha_origen'])))."</td>";
				//$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['dias_inventario'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowUnidadFisica['estado_venta'])."</td>";
				//$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['idAsignacion'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
				//$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['numero_factura_proveedor'])."</td>";				
				$htmlTb .= "<td align=\"right\">".number_format($rowUnidadFisica['saldoAnticipo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowUnidadFisica['montoNetoAnticipo'], 2, ".", ",")."</td>";
                                $htmlTb .= "<td align=\"right\">".number_format($rowUnidadFisica['precio_compra'], 2, ".", ",")."</td>";
				//$htmlTb .= "<td>"."</td>";
                                
                                $htmlTb .= "<td align=\"center\">";
                                if($rowUnidadFisica['estatus'] === "0" && $rowUnidadFisica['anulado'] !== "1"){//si el anticipo esta anulado y el trade in esta activo, permitir generar otro ancticipo
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadFisica', '%s');\"><img class=\"puntero\" src=\"../img/iconos/book_next.png\" title=\"Volver a Generar Anticipo\"/></a>",
						$contFila,
						$rowUnidadFisica['id_tradein']);
                                }
                                $htmlTb .= "</td>";
                                
				$htmlTb .= "<td align=\"center\">";
                                if($rowUnidadFisica['id_vale_entrada']){
                                    $htmlTb .= sprintf("<a onclick=\"verVentana('an_ajuste_inventario_vale_entrada_imp.php?id=%s', 960, 550);\"><img class=\"puntero\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver vale de entrada\"/></a>",
                                                $rowUnidadFisica['id_vale_entrada']);                                                                
                                }
                                $htmlTb .= "</td>";
                                
                                $htmlTb .= "<td align=\"center\">";
                                    $htmlTb .= sprintf("<a onclick=\"verVentana('../cajax3/reportes/cj_comprobante_pago_anticipo_pdf.php?valBusq=%s|%s|%s|%s',960,550);\"><img class=\"puntero\" src=\"../img/iconos/print.png\" title=\"Ver vale de entrada\"/></a>",
                                                $rowUnidadFisica['id_empresa_anticipo'],
                                                $rowUnidadFisica['idAnticipo'],
                                                $rowUnidadFisica['numeroAnticipo'],
                                                $rowUnidadFisica['numeroReporteImpresion']
                                            );                                                                
                                $htmlTb .= "</td>";
				
			$htmlTb .= "</tr>";
			
			$subTotalCosto += $rowUnidadFisica['precio_compra'];
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">"."Subtotal:"."</td>";
			$htmlTb .= "<td>".number_format($contFila2, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($subTotalCosto, 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
	}
	if ($pageNum == $totalPages) {
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			fact_comp.fecha_origen,
			(TO_DAYS(NOW()) - TO_DAYS(fact_comp.fecha_origen)) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			fact_comp.numero_factura_proveedor,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) 
                        INNER JOIN an_tradein ON uni_fis.id_unidad_fisica = an_tradein.id_unidad_fisica
                        INNER JOIN cj_cc_anticipo ON an_tradein.id_anticipo = cj_cc_anticipo.IdAnticipo
                        INNER JOIN cj_cc_cliente ON cj_cc_anticipo.idCliente = cj_cc_cliente.id                        
                        %s;", $sqlBusq);
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$contFila2++;
			
			$subTotalCosto = $rowUnidadFisica['precio_compra'];
			
			$arrayTotalFinal[0] = $contFila2;
			$arrayTotalFinal[1] += $subTotalCosto;
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">"."Total de Totales:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[0],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[1],2)."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"18\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadFisica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaVehiculos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('DISPONIBLE', 'PRESTADO', 'ACTIVO FIJO', 'INTERCAMBIO', 'DEVUELTO', 'ERROR EN TRASPASO')");
	
	//if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = alm.id_empresa))",
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"),
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"));
	//}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s		
		OR numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
        
        
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_unidad_fisica NOT IN (SELECT an_tradein.id_unidad_fisica FROM an_tradein WHERE an_tradein.id_unidad_fisica = uni_fis.id_unidad_fisica)");
                   
        
	$query = sprintf("SELECT 
		vw_iv_modelo.id_uni_bas,
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
                uni_fis.id_unidad_fisica,
                uni_fis.serial_carroceria,
                uni_fis.serial_motor,
                uni_fis.serial_chasis,
                uni_fis.placa,
                color_ext.nom_color AS color_externo1,
                color_int.nom_color AS color_interno1,
                fact_comp.fecha_origen,
                (TO_DAYS(NOW()) - TO_DAYS(fact_comp.fecha_origen)) AS dias_inventario,
                uni_fis.estado_compra,
                uni_fis.estado_venta,
                asig.idAsignacion,
                alm.nom_almacen,
                fact_comp.numero_factura_proveedor,
                uni_fis.costo_compra,
                uni_fis.precio_compra,
                IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
                INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
                INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
                INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
                INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
                %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
        
        
        if (!$rsLimit) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nLinea: ".$queryLimit); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
                $htmlTh .= "<td></td>";		
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "2%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Unidad F&iacute;sica"));		
		//$htmlTh .= ordenarCampo("xajax_listaVehiculos", "9%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "9%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Color"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Ingreso"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "4%", $pageNum, "(TO_DAYS(NOW()) - TO_DAYS(fact_comp.fecha_origen))", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("D&iacute;as"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "8%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Estado Venta"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Asignaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "14%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaVehiculos", "8%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Almac&eacute;n"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "7%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Fact. Compra"));		                
                $htmlTh .= ordenarCampo("xajax_listaVehiculos", "8%", $pageNum, "costo_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Costo"));
		
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
                        
				$htmlTb .= "<td align=\"center\"><button title=\"Seleccionar\" onclick=\"xajax_asignarVehiculo(".$row['id_unidad_fisica'].");\" type=\"button\"><img src=\"../img/iconos/tick.png\"></button></td>";
				$htmlTb .= "<td align=\"right\">".($row['id_unidad_fisica'])."</td>";
				
				//$htmlTb .= "<td align=\"center\" title=\" id: ".$row['id']."\">".$row['nombre_cliente']."</td>";
				$htmlTb .= "<td>".utf8_encode($row['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($row['serial_carroceria'])."</td>";
				$htmlTb .= "<td>".utf8_encode($row['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
				$htmlTb .= "<td align=\"center\">".implode("-",array_reverse(explode("-",$rowUnidadFisica['fecha_origen'])))."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['dias_inventario'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($row['estado_venta'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['idAsignacion'])."</td>";
				$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($row['nom_almacen'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['numero_factura_proveedor'])."</td>";				
                                $htmlTb .= "<td align=\"right\">".number_format($row['precio_compra'], 2, ".", ",")."</td>";
				
				
			$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"18\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaVehiculos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
        $objResponse->script("byId('divFlotante3').style.display = '';");
        $objResponse->script("centrarDiv(byId('divFlotante3'));");
		
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	//$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (2)");//2 = vehiculos
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:150px\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargarTipoPago(){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	//$query = sprintf("SELECT * FROM formapagos where idFormaPago <= 6 OR idFormaPago = 11");
	$query = sprintf("SELECT * FROM formapagos where idFormaPago = 11");
	$rs = mysql_query($query);
	
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<div align='justify'>
						<select name='selTipoPago' id='selTipoPago' onChange='cambiar()'>
							");
	
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value = '%s'>%s",$row["idFormaPago"],$row["nombreFormaPago"]);
	}
	$html .= sprintf("</select>
						</div>");
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarConceptoPago(){
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM cj_conceptos_formapago WHERE id_formapago = 11 AND estatus = 1 AND id_concepto = 2");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = "<select name='selConceptoPago' id='selConceptoPago'>";
		//$html .= sprintf("<option value=\"-1\">[ Seleccione ]");
		while ($row = mysql_fetch_array($rs)){
			$html .= sprintf("<option value = '%s'>%s",$row["id_concepto"],utf8_encode($row["descripcion"]));
		}
	$html .= "</select>";
	
	$objResponse->assign("tdConceptoPago","innerHTML",$html);
	
	return $objResponse;
}

function validarAperturaCaja($xajax = 1){
	$objResponse = new xajaxResponse();
        
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$fecha = date("Y-m-d");
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM an_apertura WHERE statusAperturaCaja <> 0 AND fechaAperturaCaja NOT LIKE %s AND id_empresa = %s",
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
        if (!$rsCierreCaja){ $arrayApertura = array(false, mysql_error()."\n\nLine: ".__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCierreCaja); }
	
	if (mysql_num_rows($rsCierreCaja) > 0){
		$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
		$fechaUltimaApertura = date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja']));
		$objResponse->alert("Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
		$objResponse->script("byId('btnCancelarAjusteInventario').click();");
		                
                $arrayApertura = array(false, "Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
	} else {
		//VERIFICA SI LA CAJA TIENE APERTURA
		//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
		$queryVerificarApertura = sprintf("SELECT * FROM an_apertura WHERE fechaAperturaCaja = %s AND statusAperturaCaja <> 0 AND id_empresa = %s",
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
                if (!$rsVerificarApertura){ $arrayApertura = array(false, mysql_error()."\n\nLine: ".__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL:".$queryVerificarApertura); }
		
		if (mysql_num_rows($rsVerificarApertura) == 0){
			$objResponse->alert("Esta caja no tiene apertura.");
			$objResponse->script("byId('btnCancelarAjusteInventario').click();");
                        
                        $arrayApertura = array(false, "Esta caja no tiene apertura.");
		}
                
	}
        
        if($xajax){
            return $objResponse;
        }else{
            return $arrayApertura;
        }
	
}

/**
 * Se encarga de generar un nuevo anticipo a partir del anterior, por si anularon un anticipo ya creado,
 * no tener que dar reingreso al vehiculo sino crear otro anticipo con el ingresado
 * @param Array $formTradein xajax Form
 * @param int $esNuevo bool
 * @return \xajaxResponse Objeto xajax
 */
function generarNuevoAnticipo($formTradein,$esNuevo = 0){
    $objResponse = new xajaxResponse();
    
    if (!xvalidaAcceso($objResponse,"an_tradein_list","insertar")) { errorGenerarAnticipo($objResponse); return $objResponse; }        
	
    $arrayApertura = validarAperturaCaja(0);//diferente de 1 para que devuelva array y no xajax

    if($arrayApertura[0] === false) {errorGenerarAnticipo($objResponse); return $objResponse->alert($arrayApertura[1]); }
    
    if($esNuevo){//viene de vehiculo ya registrado
        $arrayGuardadoAnticipo = $formTradein;//frmAjusteInventario
        
        $idUnidadFisica = $formTradein["txtIdUnidadFisicaAjuste"];
        $allowance = $formTradein['txtAllowance'];
        $payoff = $formTradein['txtPayoff'];
        $acv = $formTradein['txtAcv'];   
        $idClienteAdeudado = $formTradein['txtIdClienteDeuda'];
        $tipoRegistro = 2;
        
    }else{//viene de tradein ya registrado 
        $arrayGuardadoAnticipo = array(
            "txtIdEmpresa" => $formTradein["hddIdEmpresa"],
            "txtIdCliente" => $formTradein["txtIdCliente2"],
            "txtMontoAnticipo" => $formTradein["txtMontoAnticipo2"],
            "txtObservacion" => $formTradein["txtObservacion2"],
            "lstModulo" => $formTradein["hddIdModulo"],
            "selConceptoPago" => $formTradein["hddIdConceptoPago"],
            "txtSubTotal" => $formTradein["txtSubTotal2"]
        );
        
        //frmUnidadFisica
        $idUnidadFisica = $formTradein["txtIdUnidadFisica"];
        $allowance = $formTradein['txtAllowance2'];
        $payoff = $formTradein['txtPayoff2'];
        $acv = $formTradein['txtAcv2'];
        $idClienteAdeudado = $formTradein['txtIdClienteDeuda2'];
        $tipoRegistro = 3;
        
    }
    
    mysql_query("START TRANSACTION;");
    
    $arrayAnticipo = guardarAnticipo($arrayGuardadoAnticipo);        
    if($arrayAnticipo[0] == false){ errorGenerarAnticipo($objResponse); return $objResponse->alert($arrayAnticipo[1]); }
    
    $idAnticipo = $arrayAnticipo[0];
    $objResponse->script($arrayAnticipo[1]);//abre ventana de anticipo impresion
    
    
    $sql = sprintf("UPDATE an_tradein SET anulado = 1 WHERE id_unidad_fisica = %s",
            valTpDato($idUnidadFisica,"int")
            );

    $rs = mysql_query($sql);
    if (!$rs) { errorGenerarAnticipo($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

    $sql = sprintf("INSERT INTO an_tradein (id_anticipo, id_unidad_fisica, id_cliente, allowance, payoff, acv, id_empleado, tipo_registro) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
            valTpDato($idAnticipo,"int"),
            valTpDato($idUnidadFisica,"int"),
            valTpDato($idClienteAdeudado,"int"),
            valTpDato($allowance, "real_inglesa"),
            valTpDato($payoff, "real_inglesa"),
            valTpDato($acv, "real_inglesa"),
            valTpDato($_SESSION["idEmpleadoSysGts"],"int"),
            valTpDato($tipoRegistro,"int")
            );

    $rs = mysql_query($sql);
    if (!$rs) { errorGenerarAnticipo($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nQuery:".$sql); }

    mysql_query("COMMIT;");

    // MODIFICADO ERNESTO
    if (function_exists("generarAnticiposVe")) { generarAnticiposVe($idAnticipo,"",""); }
    // MODIFICADO ERNESTO
    
    if($esNuevo){
        $objResponse->alert("Nuevo Trade-in y Anticipo Generado Correctamente");
    }else{
        $objResponse->alert("Anticipo Generado Correctamente");
    }
    
    $objResponse->script("byId('btnCancelarGenerarAnticipo').click();");
    $objResponse->script("byId('btnCancelarAjusteInventario').click();");
    $objResponse->script("byId('btnBuscar').click();");
    errorGenerarAnticipo($objResponse);
    
    return $objResponse;    
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarTipoVale");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacen");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstColor");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicion");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCompraBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVentaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstPaisOrigen");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"cargaLstUso");
$xajax->register(XAJAX_FUNCTION,"formAjusteInventario");
$xajax->register(XAJAX_FUNCTION,"guardarAjusteInventario");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");

$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargarTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargarConceptoPago");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");
$xajax->register(XAJAX_FUNCTION,"formTradeIn");
$xajax->register(XAJAX_FUNCTION,"generarNuevoAnticipo");
$xajax->register(XAJAX_FUNCTION,"buscarVehiculo");
$xajax->register(XAJAX_FUNCTION,"listaVehiculos");
$xajax->register(XAJAX_FUNCTION,"asignarVehiculo");
$xajax->register(XAJAX_FUNCTION,"buscarClienteAdeudado");
$xajax->register(XAJAX_FUNCTION,"listaClienteAdeudado");
$xajax->register(XAJAX_FUNCTION,"asignarClienteAdeudado");


function errorGuardarAjusteInventario($objResponse) {
	$objResponse->script("
	byId('btnGuardarAjusteInventario').disabled = false;
	byId('btnCancelarAjusteInventario').disabled = false;");
}

function errorGuardarUnidadFisica($objResponse) {
	$objResponse->script("
	byId('btnGuardarUnidadFisica').disabled = false;
	byId('btnCancelarGenerarAnticipo').disabled = false;");
}

function errorGenerarAnticipo($objResponse) {//para dos, trade in y para anticipo
	$objResponse->script("
	byId('btnGenerarAnticipo').disabled = false;
        byId('btnGenerarTradein').disabled = false;");
}

function guardarAnticipo($frmAjusteInventario){
	
        $frmAjusteInventario['txtSubTotal'] = implode("",explode(",",$frmAjusteInventario['txtSubTotal']));
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	$idCliente = $frmAjusteInventario['txtIdCliente'];
	$montoAnticipo = $frmAjusteInventario['txtMontoAnticipo'];
        $idUsuario = $_SESSION['idUsuarioSysGts'];
	
	// NUMERACION DEL DOCUMENTO (ANTICIPO)
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = %s
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato(43, "int"), // 43 = Anticipo CXC Vehículos
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return array(false,mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	$sqlInsertAnticipo = sprintf("INSERT INTO cj_cc_anticipo (idCliente, montoNetoAnticipo, saldoAnticipo, totalPagadoAnticipo, fechaAnticipo, observacionesAnticipo, estadoAnticipo, numeroAnticipo, idDepartamento, id_empresa)
	VALUES (%s, %s, %s, %s, NOW(), %s, %s, %s, %s, %s)",
		valTpDato($idCliente,"int"),
		valTpDato($montoAnticipo, "real_inglesa"),
		valTpDato($montoAnticipo, "real_inglesa"),
		valTpDato($montoAnticipo, "real_inglesa"),
		valTpDato(utf8_decode($frmAjusteInventario['txtObservacion']), "text"),
		valTpDato(1,"int"),//0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato($numeroActualAnticipo,"int"),
		valTpDato($frmAjusteInventario['lstModulo'],"int"),
		valTpDato($idEmpresa,"int"));
	$rsInsertAnticipo = mysql_query($sqlInsertAnticipo);
	if (!$rsInsertAnticipo) { return array(false,mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertAnticipo);}
	$idAnticipo = mysql_insert_id();
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (ANTICIPO)
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return array(false,mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	/*INSERT EN EL ESTADO DE CUENTA*/
	$insertEstadoCuenta = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUES ('AN', %s, NOW(), 3)",$idAnticipo);
	$rsEstadoCuenta = mysql_query($insertEstadoCuenta);
	if (!$rsEstadoCuenta) { return array(false,mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertEstadoCuenta);}
	
	       
        //OTRO
        $formaPago = "OT";
        $idConcepto = $frmAjusteInventario['selConceptoPago'];
        $bancoCliente = 1;
        $bancoCompania = 1;
        $numeroCuentaCliente = "-";
        $numeroCuenta = "-";
        $numeroDocumento = "-";
        $campo = "saldoOtro";
        $tomadoEnCierre = 2;


        $sqlSelectDatosAperturaCaja = sprintf("SELECT saldoCaja, id, %s FROM an_apertura
        WHERE idCaja = 1
                AND statusAperturaCaja IN (1,2)
                AND id_empresa = %s",
                $campo,
                $idEmpresa);
        $rsSelectDatosAperturaCaja = mysql_query($sqlSelectDatosAperturaCaja);
        if (!$rsSelectDatosAperturaCaja)  { return array(false,mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectDatosAperturaCaja);}
        $rowSelectDatosAperturaCaja = mysql_fetch_array($rsSelectDatosAperturaCaja);
	
        $sqlUpdateDatosAperturaCaja = sprintf("UPDATE an_apertura SET %s = %s, saldoCaja = %s WHERE id = %s",
                $campo,
                valTpDato($rowSelectDatosAperturaCaja[$campo] + $frmAjusteInventario['txtSubTotal'],"double"),
                valTpDato($rowSelectDatosAperturaCaja['saldoCaja'] +$frmAjusteInventario['txtSubTotal'],"double"),
                valTpDato($rowSelectDatosAperturaCaja['id'],"int"));
        $rsUpdateDatosAperturaCaja = mysql_query($sqlUpdateDatosAperturaCaja);
        if (!$rsUpdateDatosAperturaCaja)  { return array(false,mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateDatosAperturaCaja);}

        $sqlInsertDetalleAnticipo = sprintf("INSERT INTO cj_cc_detalleanticipo (tipoPagoDetalleAnticipo, id_concepto, bancoClienteDetalleAnticipo, bancoCompaniaDetalleAnticipo, numeroCuentaCliente, numeroCuentaCompania, numeroControlDetalleAnticipo, montoDetalleAnticipo, idAnticipo, fechaPagoAnticipo, tomadoEnCierre, idCaja, idCierre)
        VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, 1, 0)",
                valTpDato($formaPago,"text"),
                valTpDato($idConcepto,"int"),
                valTpDato($bancoCliente,"int"),
                valTpDato($bancoCompania,"int"),
                valTpDato($numeroCuentaCliente,"int"),
                valTpDato($numeroCuenta,"text"),
                valTpDato($numeroDocumento,"text"),
                valTpDato($frmAjusteInventario['txtSubTotal'],"real_inglesa"),
                valTpDato($idAnticipo,"int"),                    
                valTpDato($tomadoEnCierre,"int"));
        $rsInsertDetalleAnticipo = mysql_query($sqlInsertDetalleAnticipo);
        if (!$rsInsertDetalleAnticipo)  { return array(false,mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDetalleAnticipo);}
        $idDetalleAnticipo = mysql_insert_id();
        
        //INSERTA EL CONCEPTO DE PAGO PARA HISTORICO
        $queryAnticipoConcepto = sprintf("INSERT INTO cj_cc_anticipo_concepto (id_anticipo, numero_anticipo, idCliente, fecha_registro,  caja, id_usuario, monto_total_anticipo, id_empresa, observacion, id_concepto)
        VALUES (%s, %s, %s, NOW(), %s, %s, %s, %s, 'Anticipo Por Concepto / Vehiculos', %s)",
                valTpDato($idAnticipo, "int"),
                valTpDato($numeroActualAnticipo,"int"),
                valTpDato($idCliente,"int"),
                valTpDato(1, "int"), // 1 = CAJA VEHICULOS ; 2 = CAJA RS
                valTpDato($idUsuario, "int"),
                valTpDato($montoAnticipo, "real_inglesa"),
                valTpDato($idEmpresa,"int"),
                valTpDato($idConcepto,"int"));
        $rsAnticipoConcepto = mysql_query($queryAnticipoConcepto);
        if (!$rsAnticipoConcepto) { return array(false,mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryAnticipoConcepto); }
        
	
	// NUMERACION DEL DOCUMENTO (Recibos de Pagos)
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
	if (!$rsNumeracion) { return array(false,mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	$sqlInsertReporteImpresion = sprintf("INSERT INTO pg_reportesimpresion (fechaDocumento, numeroReporteImpresion, tipoDocumento, idDocumento, idCliente, id_departamento, id_empleado_creador)
	VALUES('%s', %s, 'AN', %s, %s, 2, %s)",
		date("Y-m-d"),
		$numeroActual,
		$idAnticipo,
		$frmAjusteInventario['txtIdCliente'],
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	$rsInsertReporteImpresion = mysql_query($sqlInsertReporteImpresion);
	if (!$rsInsertReporteImpresion) { return array(false,mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertReporteImpresion);}
	$idReporteImpresion = mysql_insert_id();
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (Recibos de Pagos)
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return array(false,mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		        
	$script = sprintf("verVentana('../cajax3/reportes/cj_comprobante_pago_anticipo_pdf.php?valBusq=%s|%s|%s|%s',960,550)", $idEmpresa, $idAnticipo,$numeroActualAnticipo,$numeroActual);
			
	return array($idAnticipo, $script);
}




?>