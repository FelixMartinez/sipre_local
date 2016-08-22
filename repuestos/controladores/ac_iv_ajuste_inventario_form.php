<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function asignarArticulo($idArticulo, $frmDcto, $precioUnit = "", $hddNumeroArt = "", $frmListaArticulo = "") {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$objResponse->script("
	if (!inArray(byId('lstBuscarArticulo').value, [6,7])) {
		document.forms['frmDatosArticulo'].reset();
		byId('txtDescripcionArt').innerHTML = '';		
	}");
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$queryConfig12 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig12 = mysql_query($queryConfig12);
	if (!$rsConfig12) { errorInsertarArticulo($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsConfig12 = mysql_num_rows($rsConfig12);
	$rowConfig12 = mysql_fetch_assoc($rsConfig12);
	
	// BUSQUEDA DEL ARTICULO POR EL ID
	$queryArticulo = sprintf("SELECT
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		
		(SELECT sec.descripcion
		FROM iv_subsecciones subsec
			INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
		WHERE subsec.id_subseccion = art.id_subseccion LIMIT 1) AS descripcion_seccion,
		
		(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
		WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo LIMIT 1) AS descripcion_tipo_articulo,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad LIMIT 1) AS unidad,
		
		(SELECT tipo_unidad.decimales FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad LIMIT 1) AS decimales,
	
		(CASE art.posee_iva
			WHEN 1 THEN	(SELECT idIva FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1 LIMIT 1)
			ELSE		NULL
		END) AS id_iva,
		
		(CASE art.posee_iva
			WHEN 1 THEN	(SELECT SUM(iva) FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1)
			ELSE		'-'
		END) AS iva,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = art.id_articulo
			AND kardex.tipo_movimiento = 1
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = art.id_articulo
			AND kardex.tipo_movimiento = 3
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_venta,
		
		vw_iv_art_emp.id_casilla_predeterminada,
		vw_iv_art_emp.cantidad_disponible_logica
	FROM vw_iv_articulos_empresa vw_iv_art_emp
		INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo)
	WHERE art.id_articulo = %s
		AND vw_iv_art_emp.id_empresa = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA EL ULTIMO COSTO DEL ARTICULO
	$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos WHERE id_articulo = %s AND id_empresa = %s ORDER BY fecha_registro DESC LIMIT 1;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCostoArt = mysql_query($queryCostoArt);
	if (!$rsCostoArt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
	
	$costoUnitario = ($rowConfig12['valor'] == 1) ? round($rowCostoArt['costo'],3) : round($rowCostoArt['costo_promedio'],3);
	
	$objResponse->assign("hddIdArt","value",$idArticulo);
	$objResponse->assign("txtCodigoArt","value",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",utf8_encode($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",implode("-",array_reverse(explode("-",$rowArticulo['fecha_ultima_compra']))));
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",implode("-",array_reverse(explode("-",$rowArticulo['fecha_ultima_venta']))));
	$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['descripcion_tipo_articulo']));
	$objResponse->assign("txtCantDisponible","value",$rowArticulo['cantidad_disponible_logica']);
	
	if ($rowArticulo['decimales'] == 0) {
		$objResponse->script("
		if (navigator.appName == 'Netscape') {
			byId('txtCantidadArt').onkeypress = function(e){ return validarSoloNumeros(e); }
		} else if (navigator.appName == 'Microsoft Internet Explorer') {
			byId('txtCantidadArt').onkeypress = function(e){ return validarSoloNumeros(event); }
		}");
	} else if ($rowArticulo['decimales'] == 1) {
		$objResponse->script("
		if (navigator.appName == 'Netscape') {
			byId('txtCantidadArt').onkeypress = function(e){ return validarSoloNumerosReales(e); }
		} else if (navigator.appName == 'Microsoft Internet Explorer') {
			byId('txtCantidadArt').onkeypress = function(e){ return validarSoloNumerosReales(event); }
		}");
	}
	
	if ($frmDcto['lstTipoVale'] == 1) { // De Entrada / Salida
		$objResponse->assign("spnCostoArt","innerHTML","Costo:");
		$objResponse->script("
		byId('txtPrecioArt').className = 'inputInicial';
		byId('txtPrecioArt').readOnly = true;");
		$objResponse->assign("txtPrecioArt","value",$costoUnitario);
		$objResponse->assign("txtCostoArt","value",$costoUnitario);
	} else if ($frmDcto['lstTipoVale'] == 3) { // De Nota de Crédito de CxC
		$objResponse->assign("spnCostoArt","innerHTML",$spanPrecioUnitario.":");
		$objResponse->script("
		byId('txtPrecioArt').className = 'inputHabilitado';
		byId('txtPrecioArt').readOnly = false;");
		$objResponse->assign("txtPrecioArt","value","");
		$objResponse->assign("txtCostoArt","value",$costoUnitario);
	}
	
	if ($hddNumeroArt == "") { // NO EXISTE EL ARTICULO EN LA LISTA DEL PEDIDO
		$objResponse->assign("hddNumeroArt","value","");
		$objResponse->assign("txtCantidadArt","value",number_format(0, 2, ".", ","));
	
		$objResponse->script("byId('txtCantDisponible').className = '".(($rowArticulo['cantidad_disponible_logica'] > 0) ? "inputCantidadDisponible" : "inputCantidadNoDisponible")."'");
		
		$objResponse->assign("txtCostoArt","value",number_format($costoUnitario, 2, ".", ","));
		
		$objResponse->script("
		if (byId('lstBuscarArticulo').value == 6 || byId('lstBuscarArticulo').value == 7) {
			byId('txtCantidadArt').value++;
		}
		
		if (byId('hddNumeroArt').value != '') {
			byId('aAgregarArticulo').click();
		} else {
			byId('txtCantidadArt').focus();
			byId('txtCantidadArt').select();
		}");
		
		// CARGA LAS UBICACIONES DEL ARTICULO
		$query = sprintf("SELECT id_almacen, descripcion_almacen
		FROM vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_por_ubic
		WHERE id_articulo = %s
			AND estatus_articulo_almacen = 1
			AND id_empresa = %s
		GROUP BY id_almacen, descripcion_almacen;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$htmlLstIni = "<select id=\"lstCasillaArt\" name=\"lstCasillaArt\" onchange=\"xajax_asignarDisponibilidadUbicacion(this.value,'txtCantidadUbicacion');\">";
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$html .= "<optgroup label=\"".utf8_encode($row['descripcion_almacen'])."\">";
				$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
				WHERE id_articulo = %s
					AND id_almacen = %s
					AND estatus_articulo_almacen = 1
					AND id_empresa = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($row['id_almacen'], "int"),
					valTpDato($idEmpresa, "int"));
				$rsUbic = mysql_query($queryUbic);
				if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowUbic = mysql_fetch_assoc($rsUbic)) {
					$selected = "";
					if ($selIdCasilla == $rowUbic['id_casilla'] || $rowArticulo['id_casilla_predeterminada'] == $rowUbic['id_casilla']) {
						$selected = "selected=\"selected\"";
						$objResponse->script(sprintf("xajax_asignarDisponibilidadUbicacion('%s','txtCantidadUbicacion');", $rowUbic['id_casilla']));
					}
					
					$html .= "<option value=\"".$rowUbic['id_casilla']."\" ".$selected.">".utf8_encode(str_replace("-[]", "", $rowUbic['ubicacion']))."</option>";
				}
			$html .= "</optgroup>";
		}
		$htmlLstFin = "</select>";
		
		$objResponse->assign("tdlstCasillaArt","innerHTML",$htmlLstIni.$html.$htmlLstFin);
	} else { // SI EL ARTICULO YA ESTA AGREGADO EN LA LISTA
		$objResponse->assign("hddNumeroArt","value",$hddNumeroArt);
		
		$objResponse->assign("txtCantidadArt","value",number_format((str_replace(",","",$frmListaArticulo['hddCantArt'.$hddNumeroArt]) + 1), 2, ".", ","));
		
		$precioUnitario = str_replace(",","",$frmListaArticulo['hddPrecioArt'.$hddNumeroArt]);
		$costoUnitario = str_replace(",","",$frmListaArticulo['hddCostoArt'.$hddNumeroArt]);
		$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
		$objResponse->assign("txtCostoArt","value",number_format($costoUnitario, 2, ".", ","));
		
		$objResponse->script("xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $asigDescuento = "true", $cerrarVentana = "true") {
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
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'))");
	
	return $objResponse;
}

function asignarDcto($idDcto) {
	$objResponse = new xajaxResponse();
	
	$queryDcto = sprintf("SELECT nota_cred.*,
		cliente.id AS id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		(nota_cred.subtotalNotaCredito - subtotal_descuento + ivaLujoNotaCredito + ivaNotaCredito) AS total_nota_credito
	FROM cj_cc_notacredito nota_cred
		INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id)
	WHERE idNotaCredito = %s",
		valTpDato($idDcto, "int"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	$objResponse->assign("txtIdCliente","value",$rowDcto['id_cliente']);
	$objResponse->assign("txtNombreCliente","value",$rowDcto['nombre_cliente']);
	
	$objResponse->assign("hddIdDcto","value",$idDcto);
	$objResponse->assign("txtNroDcto","value",$rowDcto['numeracion_nota_credito']);
	
	$objResponse->script("
	byId('btnCancelarLista').click();");
	
	return $objResponse;
}

function asignarDisponibilidadUbicacion($idCasilla, $objetoDestino) {
	$objResponse = new xajaxResponse();
	
	$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_casilla = %s
		AND estatus_articulo_almacen = 1;",
		valTpDato($idCasilla, "int"));
	$rsUbic = mysql_query($queryUbic);
	if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUbic = mysql_fetch_assoc($rsUbic);
	
	$objResponse->assign($objetoDestino,"value",$rowUbic['cantidad_disponible_logica']);
	
	return $objResponse;
}

function asignarTipoVale($idTipoVale) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("txtIdCliente","value","");
	$objResponse->assign("txtNombreCliente","value","");
	$objResponse->assign("hddIdDcto","value","");
	$objResponse->assign("txtNroDcto","value","");
	
	if ($idTipoVale == 1) { // DE ENTRADA O SALIDA
		$objResponse->script("
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		byId('lstTipoMovimiento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = false;
		byId('btnListarCliente').style.display = '';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', this.value, '', '5,6');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "2,4", "", "5,6"));
	} else if ($idTipoVale == 3) { // DE NOTA DE CREDITO DE CxC
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputHabilitado';
		byId('lstTipoMovimiento').className = 'inputInicial';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = '';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id,2);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', this.value, '', '3');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",2);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", 2, "", "3"));
	} else {
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		byId('lstTipoMovimiento').className = 'inputInicial';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = true;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', this.value);
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", -1));
	}
	
	return $objResponse;
}

function buscarArticulo($frmBuscarArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $frmBuscarArticulo['hddCantCodigo']; $cont++) {
		$codArticulo .= $frmBuscarArticulo['txtCodigoArticulo'.$cont].";";
	}
	$auxCodArticulo = $codArticulo;
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
	if (strlen($frmDcto['txtIdEmpresa']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("id_empresa = %s",
			valTpDato($frmDcto['txtIdEmpresa'], "int"));
	}
	
	if ($auxCodArticulo != "---") {
		if ($codArticulo != "-1" && $codArticulo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
				valTpDato($codArticulo, "text"));
		}
	}
	
	if (strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($frmBuscarArticulo['lstBuscarArticulo'] == 7) {
			$sqlBusq .= $cond.sprintf("codigo_articulo_prov LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
		} else if ($frmBuscarArticulo['lstBuscarArticulo'] == 6) {
			$arrayCriterioBuscarArticulo = explode("A", $frmBuscarArticulo['txtCriterioBuscarArticulo']);
			$txtCriterioBuscarArticulo = $arrayCriterioBuscarArticulo['0'];
			array_shift($arrayCriterioBuscarArticulo);
			$arrayPrecioUnit = explode("Z", $arrayCriterioBuscarArticulo[0]);
			$arrayPrecioUnit = array_reverse($arrayPrecioUnit);
			$precioUnit = implode(".",$arrayPrecioUnit);
			$sqlBusq .= $cond.sprintf("id_articulo = %s", valTpDato($txtCriterioBuscarArticulo, "int"));
		} else if ($frmBuscarArticulo['lstBuscarArticulo'] == 5) {
			$sqlBusq .= $cond.sprintf("descripcion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
		} else if ($frmBuscarArticulo['lstBuscarArticulo'] == 4) {
			$sqlBusq .= $cond.sprintf("descripcion_subseccion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
		} else if ($frmBuscarArticulo['lstBuscarArticulo'] == 3) {
			$sqlBusq .= $cond.sprintf("descripcion_seccion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
		} else if ($frmBuscarArticulo['lstBuscarArticulo'] == 2) {
			$sqlBusq .= $cond.sprintf("tipo_articulo LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
		} else if ($frmBuscarArticulo['lstBuscarArticulo'] == 1) {
			$sqlBusq .= $cond.sprintf("marca LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
		}
	}
		
	$objResponse->assign("divListaArticulo","innerHTML","");
	
	if ($auxCodArticulo != "---" || strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$query = sprintf("SELECT id_articulo FROM vw_iv_articulos_empresa_datos_basicos %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		if ($totalRows == 1) {
			$row = mysql_fetch_assoc($rs);
			
			// VERIFICA SI ALGUN ARTICULO YA ESTA INCLUIDO EN EL DOCUMENTO
			$existe = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					if ($frmListaArticulo['hddIdArt'.$valor] == $row['id_articulo']
					&& str_replace(",","",$frmListaArticulo['hddPrecioArt'.$valor]) == str_replace(",","",$precioUnit)) {
						$objResponse->script(sprintf("xajax_asignarArticulo('%s', xajax.getFormValues('frmDcto'), '%s', '%s', xajax.getFormValues('frmListaArticulo'))",
							$row['id_articulo'],
							$precioUnit,
							$valor));
						$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				$objResponse->loadCommands(asignarArticulo($row['id_articulo'], $frmDcto, $precioUnit));
			}
			
			$objResponse->script("byId('txtCriterioBuscarArticulo').value = '';");
		} else if ($totalRows > 1) {
			$valBusq = sprintf("%s|%s|%s|%s",
				$frmDcto['txtIdEmpresa'],
				$codArticulo,
				$frmBuscarArticulo['lstBuscarArticulo'],
				$frmBuscarArticulo['txtCriterioBuscarArticulo']);
			
			$objResponse->loadCommands(listaArticulo(0, "id_articulo", "DESC", $valBusq));
		} else {
			$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
			$htmlTb .= "<td colspan=\"11\">";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTblFin .= "</table>";
			
			$objResponse->assign("divListaArticulo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		}
	}
	
	return $objResponse;	
}

function buscarCliente($frmBuscarLista, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarLista['txtCriterioBuscarLista']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarNotaCredito($frmBuscarLista) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarLista['txtCriterioBuscarLista']);
	
	$objResponse->loadCommands(listaNotaCredito(0, "numeracion_nota_credito", "DESC", $valBusq));
	
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
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantTotalItem += str_replace(",","",$frmListaArticulo['hddCantArt'.$valor]);
			$txtSubTotal += str_replace(",","",$frmListaArticulo['hddCantArt'.$valor]) * str_replace(",","",$frmListaArticulo['hddPrecioArt'.$valor]);
		}
	}
	
	$objResponse->assign("txtCantTotalItem","value",number_format($txtCantTotalItem, 2, ".", ","));
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	
	if (count($arrayObj) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('btnListarEmpresa').style.display = 'none';
		
		byId('lstTipoVale').className = 'inputInicial';
		byId('lstTipoVale').onchange = function () {
			selectedOption(this.id,'".$frmDcto['lstTipoVale']."');
		}
		
		byId('lstTipoMovimiento').className = 'inputInicial';
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,'".$frmDcto['lstTipoMovimiento']."');
		}");
		
		if ($frmDcto['lstTipoVale'] == 3) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC
			$objResponse->script("byId('btnListarDcto').style.display = 'none';");
		}
	} else { // SI NO TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('btnListarEmpresa').style.display = '';");
		
		if ($frmDcto['txtIdCliente'] > 0) {
			$objResponse->script("
			byId('lstTipoVale').className = 'inputInicial';
			byId('lstTipoVale').onchange = function () {
				selectedOption(this.id,'".$frmDcto['lstTipoVale']."');
			}
			
			byId('lstTipoMovimiento').className = 'inputInicial';
			byId('lstTipoMovimiento').onchange = function () {
				selectedOption(this.id,'".$frmDcto['lstTipoMovimiento']."');
			}");
		} else {
			$objResponse->script("
			xajax_asignarTipoVale('".$frmDcto['lstTipoVale']."');
			
			byId('lstTipoVale').className = 'inputHabilitado';
			byId('lstTipoVale').onchange = function () {
				xajax_asignarTipoVale(this.value);
			}
			
			byId('lstTipoMovimiento').className = 'inputHabilitado';
			byId('lstTipoMovimiento').onchange = function () {
				xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', this.value, '".(($frmDcto['lstTipoVale'] == 1) ? "5,6": "3")."');
			}");
		}
		
		if ($frmDcto['lstTipoVale'] == 3) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC
			$objResponse->script("byId('btnListarDcto').style.display = '';");
		}
	}
	
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
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
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

function eliminarArticulo($frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaArticulo['cbxItm'])) {
		foreach ($frmListaArticulo['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
	return $objResponse;
}

function formImportar($nomObjeto) {
	$objResponse = new xajaxResponse();

	$objResponse->script("
	document.forms['frmImportarPedido'].reset();
	byId('hddUrlArchivo').value = '';
	
	byId('fleUrlArchivo').className = 'inputHabilitado';");
	
	$objResponse->script("
	byId('tblImportarPedido').style.display = '';
	byId('tblListaEmpresa').style.display = 'none';
	byId('tblLista').style.display = 'none';
	byId('tblArticulo').style.display = 'none';");
	
	$objResponse->script("openImg(byId('".$nomObjeto."'));");
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Importar Pedido");
	$objResponse->script("
	byId('fleUrlArchivo').focus();
	byId('fleUrlArchivo').select();");
	
	return $objResponse;
}

function formNotaCredito($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmBuscarLista'].reset();
	byId('btnBuscarLista').onclick = function () {
		xajax_buscarNotaCredito(xajax.getFormValues('frmBuscarLista'));
	}");
	
	$objResponse->script("
	byId('tblImportarPedido').style.display = 'none';
	byId('tblListaEmpresa').style.display = 'none';
	byId('tblLista').style.display = '';
	byId('tblArticulo').style.display = 'none';");
	
	$objResponse->loadCommands(listaNotaCredito(0, "numeracion_nota_credito", "DESC"));
	
	$objResponse->script("openImg(byId('".$nomObjeto."'));");
	$objResponse->assign("tdFlotanteTitulo","innerHTML",utf8_encode("Notas de Crédito"));
	$objResponse->assign("tblLista","width","960");
	$objResponse->script("
	byId('txtCriterioBuscarLista').focus();
	byId('txtCriterioBuscarLista').select();");
		
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_ajuste_inventario_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	
	if ($frmDcto['lstTipoVale'] == 1) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	} else if ($frmDcto['lstTipoVale'] == 3) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		switch ($frmDcto['lstTipoMovimiento']) { // 2 = ENTRADA, 4 = SALIDA
			case 2 : $documentoGenera = 6; break;
			case 4 : $documentoGenera = 5; break;
		}
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.tipo = %s
			AND clave_mov.documento_genera = %s
			AND clave_mov.id_modulo IN (0)
		ORDER BY clave DESC 
		LIMIT 1;",
			valTpDato($frmDcto['lstTipoMovimiento'], "int"),
			valTpDato($documentoGenera, "int"));
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
		
		$idClaveMovimiento = $rowClaveMov['id_clave_movimiento'];
	}
	
	// VERIFICA SI ALGUN ARTICULO NO TIENE UNA UBICACIÓN ASIGNADA EN EL ALMACEN
	$sinAlmacen = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($valor > 0 && strlen($frmListaArticulo['hddIdCasilla'.$valor]) == "") {
				$sinAlmacen = true;
			}
		}
	}
	
	if ($sinAlmacen == false) {
		if ($frmDcto['txtIdVale'] == "") {
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
			
			if ($frmDcto['lstTipoMovimiento'] == 2) { // 2 = ENTRADA, 4 = SALIDA
				$insertSQL = sprintf("INSERT INTO iv_vale_entrada (numeracion_vale_entrada, id_empresa, fecha, id_documento, id_cliente, subtotal_documento, tipo_vale_entrada, observacion, id_empleado_creador)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($numeroActual, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])),"date"),
					valTpDato($frmDcto['hddIdDcto'], "int"),
					valTpDato($frmDcto['txtIdCliente'], "int"),
					valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
					valTpDato($frmDcto['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inv. Fisico
					valTpDato($frmTotalDcto['txtObservacion'], "text"),
					valTpDato($frmDcto['hddIdEmpleado'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idVale = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				$arrayDetIdDctoContabilidad[0] = $idVale;
				$arrayDetIdDctoContabilidad[1] = $idModulo;
				$arrayDetIdDctoContabilidad[2] = "ENTRADA";
				$arrayIdDctoContabilidad[] = $arrayDetIdDctoContabilidad;
				
				if ($frmDcto['lstTipoVale'] == 3) { // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inv. Fisico
					// ACTUALIZA EL ESTATUS DE LA NOTA DE CREDITO CREADA POR CUENTAS POR COBRAR
					$updateSQL = sprintf("UPDATE cj_cc_notacredito SET
						id_clave_movimiento = %s,
						estatus_nota_credito = 2
					WHERE idNotaCredito = %s;",
						valTpDato($idClaveMovimiento, "int"),
						valTpDato($frmDcto['hddIdDcto'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
				
				$estadoKardex = 0;
			} else if ($frmDcto['lstTipoMovimiento'] == 4) { // 2 = ENTRADA, 4 = SALIDA
				$insertSQL = sprintf("INSERT INTO iv_vale_salida (numeracion_vale_salida, id_empresa, fecha, id_documento, id_cliente, subtotal_documento, tipo_vale_salida, observacion, id_empleado_creador)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($numeroActual, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])),"date"),
					valTpDato($frmDcto['hddIdDcto'], "int"),
					valTpDato($frmDcto['txtIdCliente'], "int"),
					valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
					valTpDato($frmDcto['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
					valTpDato($frmTotalDcto['txtObservacion'], "text"),
					valTpDato($frmDcto['hddIdEmpleado'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idVale = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				$arrayDetIdDctoContabilidad[0] = $idVale;
				$arrayDetIdDctoContabilidad[1] = $idModulo;
				$arrayDetIdDctoContabilidad[2] = "SALIDA";
				$arrayIdDctoContabilidad[] = $arrayDetIdDctoContabilidad;
				
				$estadoKardex = 1;
			}
		}
		
		// INSERTA EL MOVIMIENTO
		$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
		VALUE (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
			valTpDato($frmDcto['lstTipoMovimiento'], "int"),
			valTpDato($idClaveMovimiento, "int"),
			valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
			valTpDato($idVale, "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato($frmDcto['txtIdCliente'], "int"),
			valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
			valTpDato($_SESSION['idUsuarioSysGts'], "int"),
			valTpDato(1, "boolean")); // 0 = Credito, 1 = Contado
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idMovimiento = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA EL DETALLE DEL MOVIMIENTO
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$idArticulo = $frmListaArticulo['hddIdArt'.$valor];
				$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
				
				$cantRecibida = round(str_replace(",","",$frmListaArticulo['hddCantArt'.$valor]),2);
				$precioUnitario = round(str_replace(",","",$frmListaArticulo['hddPrecioArt'.$valor]),2);
				$costoUnitario = round(str_replace(",","",$frmListaArticulo['hddCostoArt'.$valor]),2);
				
				$totalArticulo = $cantRecibida * $precioUnitario;
				
				switch ($frmDcto['lstTipoMovimiento']) {
					case 2 : // ENTRADA
						if ($idArticulo > 0) {
							$insertSQL = sprintf("INSERT INTO iv_vale_entrada_detalle (id_vale_entrada, id_articulo, id_casilla, cantidad, precio_venta, costo_compra)
							VALUE (%s, %s, %s, %s, %s, %s);",
								valTpDato($idVale, "int"),
								valTpDato($idArticulo, "int"),
								valTpDato($idCasilla, "int"),
								valTpDato($cantRecibida, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) {
								if (mysql_errno() == 1062) {
									return $objResponse->alert("Existe un Registro Duplicado"."\n\nLine: ".__LINE__);
								} else {
									return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								}
							}
							mysql_query("SET NAMES 'latin1';");
						}
						break;
					case 4 : // SALIDA
						if ($idArticulo > 0) {
							$insertSQL = sprintf("INSERT INTO iv_vale_salida_detalle (id_vale_salida, id_articulo, id_casilla, cantidad, precio_venta, costo_compra)
							VALUE (%s, %s, %s, %s, %s, %s);",
								valTpDato($idVale, "int"),
								valTpDato($idArticulo, "int"),
								valTpDato($idCasilla, "int"),
								valTpDato($cantRecibida, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) {
								if (mysql_errno() == 1062) {
									return $objResponse->alert("Existe un Registro Duplicado"."\n\nLine: ".__LINE__);
								} else {
									return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								}
							}
							mysql_query("SET NAMES 'latin1';");
						}
						break;
				}
				
				if ($idArticulo > 0) {
					// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
					$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
						valTpDato($idModulo, "int"),
						valTpDato($idVale, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($frmDcto['lstTipoMovimiento'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($idClaveMovimiento, "int"),
						valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitario, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato($estadoKardex, "int"), // 0 = Entrada, 1 = Salida
						valTpDato($frmTotalDcto['txtObservacion'], "text"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					// REGISTRA EL DETALLE DEL MOVIMIENTO DEL ARTICULO
					$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idKardex, "int"),
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitario, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato(((0 * $precioUnitario) / 100), "real_inglesa"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean"), // 0 = No, 1 = Si
						valTpDato("", "int"),
						valTpDato("", "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				
					// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
					$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
					
					// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
					$Result1 = actualizarSaldos($idArticulo, $idCasilla);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
					
					// BUSCA EL ULTIMO COSTO DEL ARTICULO
					$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos WHERE id_articulo = %s AND id_empresa = %s ORDER BY fecha_registro DESC LIMIT 1;",
						valTpDato($idArticulo, "int"),
						valTpDato($idEmpresa, "int"));
					$rsCostoArt = mysql_query($queryCostoArt);
					if (!$rsCostoArt) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
					$existeCosto = false;
					while ($rowCostoArt = mysql_fetch_assoc($rsCostoArt)) {
						if (round($rowCostoArt['costo'],2) == round($costoUnitario,2)
						&& date("Y-m-d",strtotime($rowCostoArt['fecha'])) == date("Y-m-d")) {
							$existeCosto = true;
						}
					}
					
					if ($existeCosto == false) {
						$rsCostoArt = mysql_query($queryCostoArt);
						if (!$rsCostoArt) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
						
						$idProveedor = ($rowCostoArt['id_proveedor'] > 0) ? $rowCostoArt['id_proveedor'] : $frmDcto['txtIdCliente'];
						
						$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, costo, fecha, fecha_registro)
						VALUE (%s, %s, %s, %s, %s, %s);",
							valTpDato($idEmpresa, "int"),
							valTpDato($idProveedor, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($rowCostoArt['costo'], "real_inglesa"),
							valTpDato(date("Y-m-d"),"date"),
							valTpDato("NOW()","campo"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idArticuloCosto = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
					}
					
					// ACTUALIZA EL COSTO PROMEDIO
					$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
					
					// SI EL VALE ES NORMAL PARA AJUSTAR EL INVENTARIO
					if ($frmDcto['lstTipoVale'] == 1) {
						// ACTUALIZA EL PRECIO DE VENTA
						$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
					}
				}
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->assign("txtIdVale","value",$idVale);
		$objResponse->assign("txtNumeroVale","value",$numeroActual);
		
		$objResponse->alert(utf8_encode("Vale Guardado con Éxito"));
		
		switch ($frmDcto['lstTipoMovimiento']) {
			case 2 : $objResponse->script(sprintf("verVentana('reportes/iv_ajuste_inventario_pdf.php?valBusq=%s|2', 960, 550);", $idVale)); break;
			case 4 : $objResponse->script(sprintf("verVentana('reportes/iv_ajuste_inventario_pdf.php?valBusq=%s|4', 960, 550);", $idVale)); break;
		}
		
		$objResponse->script(sprintf("
		cerrarVentana = true;
		window.location.href='iv_ajuste_inventario_list.php';"));
	
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
				}
				// MODIFICADO ERNESTO
			}
		}
	} else {
		$objResponse->alert(utf8_encode("Existen artículos los cuales no tienen ubicación asignada"));
	}
	
	return $objResponse;
}

function importarDcto($frmImportarPedido, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$inputFileType = 'Excel5';
	//$inputFileType = 'Excel2007';
	//$inputFileType = 'Excel2003XML';
	//$inputFileType = 'OOCalc';
	//$inputFileType = 'Gnumeric';
	$inputFileName = 'reportes/tmp/'.$frmImportarPedido['hddUrlArchivo'];
	
	$phpExcel = new PHPExcel_Reader_Excel2007();
	$archivoExcel = $phpExcel->load($inputFileName);
	
	$archivoExcel->setActiveSheetIndex(0);
	$i = 1;
	while ($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue() != '') {
		$cantPedida = $archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue();
		
		if ($itemExcel == true && doubleval($cantPedida) > 0) {
			$arrayFila[] = array(
				$archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue(), // Código
				$archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue(), // Ped.
				$archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue()); // Costo Unit.);
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Codigo"))) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if (isset($arrayFila)) {
		foreach ($arrayFila as $indice => $valor) {
			// RUTINA PARA AGREGAR EL ARTICULO
			$idEmpresa = ($frmDcto['txtIdEmpresa'] > 0) ? $frmDcto['txtIdEmpresa'] : $_SESSION['idEmpresaUsuarioSysGts'];

			// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Vale de Entrada y Salida)
			$queryConfig16 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 16 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
			$rsConfig16 = mysql_query($queryConfig16);
			if (!$rsConfig16) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowConfig16 = mysql_fetch_assoc($rsConfig16);
			
			// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
			$queryConfig12 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
			$rsConfig12 = mysql_query($queryConfig12);
			if (!$rsConfig12) { errorInsertarArticulo($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsConfig12 = mysql_num_rows($rsConfig12);
			$rowConfig12 = mysql_fetch_assoc($rsConfig12);
			
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArt = sprintf("SELECT 
				id_articulo,
				codigo_articulo
			FROM vw_iv_articulos_empresa vw_iv_art_emp
			WHERE codigo_articulo LIKE %s
				AND id_empresa = %s;",
				valTpDato($arrayFila[$indice][0], "text"),
				valTpDato($idEmpresa, "int"));
			$rsArt = mysql_query($queryArt);
			if (!$rsArt) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsArt = mysql_num_rows($rsArt);
			$rowArt = mysql_fetch_assoc($rsArt);
			
			$idArticulo = $rowArt['id_articulo'];
			
			$existe = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice2 => $valor2) {
					if ($frmListaArticulo['hddIdArt'.$valor2] == $idArticulo && $idArticulo > 0) {
						$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				if (count($arrayObj) < $rowConfig16['valor']) {
					if ($totalRowsArt > 0) {
						// BUSCA EL ULTIMO COSTO DEL ARTICULO
						$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos WHERE id_articulo = %s AND id_empresa = %s ORDER BY fecha_registro DESC LIMIT 1;",
							valTpDato($idArticulo, "int"),
							valTpDato($idEmpresa, "int"));
						$rsCostoArt = mysql_query($queryCostoArt);
						if (!$rsCostoArt) { errorEditarArticulo($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
						
						$costoUnitario = ($rowConfig12['valor'] == 1) ? round($rowCostoArt['costo'],3) : round($rowCostoArt['costo_promedio'],3);
						$precioUnitario = ($frmDcto['lstTipoVale'] == 1) ? $costoUnitario : $arrayFila[$indice][2];
						$cantPedida = $arrayFila[$indice][1];
						
						$Result1 = insertarItem($contFila, $idEmpresa, "", $idArticulo, $cantPedida, $precioUnitario, $costoUnitario, "", "");
						$arrayObjUbicacion = $Result1[3];
						if ($Result1[0] != true && strlen($Result1[1]) > 0) {
							return $objResponse->alert($Result1[1]);
						} else if ($Result1[0] == true) {
							$contFila = $Result1[2];
							$frmListaArticulo['hddIdArt'.$contFila] = $idArticulo;
							$objResponse->script($Result1[1]);
							$arrayObj[] = $contFila;
						}
					} else {
						$arrayObjNoExiste[] = $arrayFila[$indice][0];
					}
				} else {
					$msjCantidadExcedida = "Solo puede agregar un máximo de ".$rowConfig16['valor']." items por vale";
				}
			} else {
				$arrayObjExiste[] = $arrayFila[$indice][0];
			}
		}
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
		if (strlen($msjCantidadExcedida) > 0)
			$objResponse->alert(utf8_encode($msjCantidadExcedida));
			
		if (count($arrayObjNoExiste) > 0)
			$objResponse->alert(utf8_encode("No existe(n) en el sistema ".count($arrayObjNoExiste)." items:\n".implode("\n",$arrayObjNoExiste)));
			
		if (count($arrayObjExiste) > 0) {
			$objResponse->alert(utf8_encode("Ya se encuentra(n) incluido(s) ".count($arrayObjExiste)." items:\n".implode("\n",$arrayObjExiste)));
		} else if (count($arrayObj) > 0) {
			$objResponse->alert(utf8_encode("Pedido importado con éxito"));
		} else {
			$objResponse->alert(utf8_encode("No se pudo importar el archivo"));
		}
		
		$objResponse->script("
		byId('btnCancelarImportarPedido').click();");
	} else {
		$objResponse->alert(utf8_encode("Verifique que el pedido tenga cantidades solicitadas"));
	}
	
	return $objResponse;
}

function insertarArticulo($frmDatosArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$hddNumeroArt = $frmDatosArticulo['hddNumeroArt'];
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idArticulo = $frmDatosArticulo['hddIdArt'];
	$idCasilla = $frmDatosArticulo['lstCasillaArt'];
	$cantPedida = str_replace(",","",$frmDatosArticulo['txtCantidadArt']);
	$precioUnitario = str_replace(",","",$frmDatosArticulo['txtPrecioArt']);
	$costoUnitario = str_replace(",","",$frmDatosArticulo['txtCostoArt']);

	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Vale de Entrada y Salida)
	$queryConfig16 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 16 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig16 = mysql_query($queryConfig16);
	if (!$rsConfig16) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig16 = mysql_fetch_assoc($rsConfig16);
	
	$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_articulo = %s
		AND id_casilla = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idCasilla, "int"));
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
	
	if (($frmDcto['lstTipoMovimiento'] == 2 && doubleval($rowArtEmp['cantidad_disponible_logica'] + $cantPedida) >= 0)
	|| ($frmDcto['lstTipoMovimiento'] == 4 && doubleval($rowArtEmp['cantidad_disponible_logica'] - $cantPedida) >= 0)) {
		if ($hddNumeroArt == "") {
			// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
			$arrayObj = $frmListaArticulo['cbx'];
			$contFila = $arrayObj[count($arrayObj)-1];
			
			if (count($arrayObj) < $rowConfig16['valor']) {
				// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
				$queryArtAlm = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
					valTpDato($idCasilla, "int"));
				$rsArtAlm = mysql_query($queryArtAlm);
				if (!$rsArtAlm) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
				$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
				
				$almacen = $rowArtAlm['descripcion_almacen'];
				$ubicacion = $rowArtAlm['ubicacion'];
				
				$Result1 = insertarItem($contFila, $idEmpresa, "", $idArticulo, $cantPedida, $precioUnitario, $costoUnitario, $almacen, $ubicacion);
				$arrayObjUbicacion = $Result1[3];
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
				
				$objResponse->script("
				if (byId('hddNumeroArt').value != '') {
				} else {
					document.forms['frmDatosArticulo'].reset();
					byId('txtDescripcionArt').innerHTML = '';
					
					if (byId('lstBuscarArticulo').value == 6 || byId('lstBuscarArticulo').value == 7) {
						byId('txtCriterioBuscarArticulo').focus();
						byId('txtCriterioBuscarArticulo').select();
					} else {
						document.forms['frmBuscarArticulo'].reset();
						byId('txtCodigoArticulo0').focus();
						byId('txtCodigoArticulo0').select();
					}
				}");
			
				$objResponse->assign("divListaArticulo","innerHTML","");
						
				$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
			} else {
				$objResponse->alert(utf8_encode("Solo puede agregar un máximo de ".$rowConfig16['valor']." items por Vale"));
			}
		} else {
			$objResponse->assign("hddCantArt".$hddNumeroArt,"value",number_format($cantPedida, 2, ".", ","));
			$objResponse->assign("hddPrecioArt".$hddNumeroArt,"value",number_format($precioUnitario, 2, ".", ","));
			$objResponse->assign("hddCostoArt".$hddNumeroArt,"value",number_format($costoUnitario, 2, ".", ","));
			$objResponse->assign("hddTotalArt".$hddNumeroArt,"value",number_format(($cantPedida * $precioUnitario), 2, ".", ","));
			
			$objResponse->script("
			if (byId('hddNumeroArt').value != '') {
				if (byId('lstBuscarArticulo').value == 6 || byId('lstBuscarArticulo').value == 7) {
					byId('txtCriterioBuscarArticulo').focus();
					byId('txtCriterioBuscarArticulo').select();
				} else {
					document.forms['frmBuscarArticulo'].reset();
					byId('txtCodigoArticulo0').focus();
					byId('txtCodigoArticulo0').select();
				}
			} else {
				document.forms['frmDatosArticulo'].reset();
				byId('txtDescripcionArt').innerHTML = '';
				
				if (byId('lstBuscarArticulo').value == 6 || byId('lstBuscarArticulo').value == 7) {
					byId('txtCriterioBuscarArticulo').focus();
					byId('txtCriterioBuscarArticulo').select();
				} else {
					document.forms['frmBuscarArticulo'].reset();
					byId('txtCodigoArticulo0').focus();
					byId('txtCodigoArticulo0').select();
				}
			}");
		}
	} else {
		$objResponse->alert("No posee disponible la cantidad suficiente");
	}
	
	return $objResponse;
}

function listaArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("vw_iv_art_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if (strlen($valCadBusq[3]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 7) {
			$sqlBusq .= $cond.sprintf("art.codigo_articulo_prov LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
		} else if ($valCadBusq[2] == 6) {
			$sqlBusq .= $cond.sprintf("art.id_articulo = %s", valTpDato($valCadBusq[3], "int"));
		} else if ($valCadBusq[2] == 5) {
			$sqlBusq .= $cond.sprintf("art.descripcion LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
		} else if ($valCadBusq[2] == 4) {
			$sqlBusq .= $cond.sprintf("(SELECT subsec.descripcion
			FROM iv_subsecciones subsec
			WHERE subsec.id_subseccion = vw_iv_articulos_empresa.id_subseccion) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
		} else if ($valCadBusq[2] == 3) {
			$sqlBusq .= $cond.sprintf("(SELECT sec.descripcion
			FROM iv_subsecciones subsec
				INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
			WHERE subsec.id_subseccion = vw_iv_articulos_empresa.id_subseccion) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
		} else if ($valCadBusq[2] == 2) {
			$sqlBusq .= $cond.sprintf("(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
			WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
		} else if ($valCadBusq[2] == 1) {
			$sqlBusq .= $cond.sprintf("(SELECT marca.marca FROM iv_marcas marca
			WHERE marca.id_marca = art.id_marca) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
		}
	}
	
	$query = sprintf("SELECT vw_iv_art_emp.*,
	
		(SELECT marca.marca FROM iv_marcas marca
		WHERE marca.id_marca = art.id_marca) AS marca,
		
		(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
		WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) AS tipo_articulo,
		
		vw_iv_art_emp.clasificacion
	FROM vw_iv_articulos_empresa vw_iv_art_emp
		INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna texto_10px\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "52%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "14%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Marca");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "10%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, "Disponible");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "10%", $pageNum, "cantidad_pedida", $campOrd, $tpOrd, $valBusq, $maxRows, "Pedida a Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$srcIcono = "";
		$class = "";
		if ($row['cantidad_disponible_logica'] == "" || $row['cantidad_disponible_logica'] == 0) {
			$srcIcono = "../img/iconos/cancel.png";
		} else if ($row['cantidad_disponible_logica'] <= $row['stock_minimo']) {
			$srcIcono = "../img/iconos/error.png";
			$class = "class=\"divMsjAlerta\"";
		} else if ($row['cantidad_disponible_logica'] > $row['stock_minimo']) {
			$srcIcono = "../img/iconos/tick.png";
			$class = "class=\"divMsjInfo\"";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarArticulo('".$row['id_articulo']."', xajax.getFormValues('frmDcto'));\" title=\"Seleccionar\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca']." (".$row['tipo_articulo'].")")."</td>";
			$htmlTb .= "<td align=\"right\" ".$class.">".valTpDato(number_format($row['cantidad_disponible_logica'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cantidad_pedida'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".utf8_encode("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".utf8_encode("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".utf8_encode("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".utf8_encode("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".utf8_encode("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".utf8_encode("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divListaArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idDepartamentoNotaCredito IN (0,1)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("subtotalNotaCredito > 0");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((idDepartamentoNotaCredito = 0
		AND (SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle
			WHERE id_nota_credito = idNotaCredito) = 0)
	OR (idDepartamentoNotaCredito = 1
		AND tipoDocumento = 'FA'
		AND (SELECT COUNT(fact_vent.idFactura) FROM cj_cc_encabezadofactura fact_vent
				INNER JOIN cj_cc_factura_detalle fact_vent_det ON (fact_vent.idFactura = fact_vent_det.id_factura)
			WHERE fact_vent.idFactura = nota_cred.idDocumento) = 0
		AND (SELECT COUNT(fact_vent.idFactura) FROM cj_cc_encabezadofactura fact_vent
				INNER JOIN sa_det_fact_tempario fact_vent_det_temp ON (fact_vent.idFactura = fact_vent_det_temp.idFactura)
			WHERE fact_vent.idFactura = nota_cred.idDocumento) = 0
		AND (SELECT COUNT(fact_vent.idFactura) FROM cj_cc_encabezadofactura fact_vent
				INNER JOIN sa_det_fact_tot fact_vent_det_tot ON (fact_vent.idFactura = fact_vent_det_tot.idFactura)
			WHERE fact_vent.idFactura = nota_cred.idDocumento) = 0
		AND (SELECT COUNT(fact_vent.idFactura) FROM cj_cc_encabezadofactura fact_vent
				INNER JOIN sa_det_fact_notas fact_vent_det_nota ON (fact_vent.idFactura = fact_vent_det_nota.idFactura)
			WHERE fact_vent.idFactura = nota_cred.idDocumento) = 0
		)
	)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_vale_entrada) FROM iv_vale_entrada
	WHERE id_documento = idNotaCredito AND tipo_vale_entrada = 3) = 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numeracion_nota_credito LIKE %s
		OR CONCAT_WS('-' , cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT nota_cred.*,
		cliente.id AS id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		(nota_cred.subtotalNotaCredito - subtotal_descuento + ivaLujoNotaCredito + ivaNotaCredito) AS total_nota_credito
	FROM cj_cc_notacredito nota_cred
		INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id) %s", $sqlBusq);
	
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
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "fechaNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "numeracion_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Nota Créd."));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "64%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "16%", $pageNum, "total_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgPedidoModulo = "";
		$imgPedidoModuloCondicion = "";
		if ($row['estatus_pedido_venta'] == "") {
			$imgPedidoModuloCondicion = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("Nota Crédito CxC")."\"/>";
			
			switch($row['idDepartamentoNotaCredito']) {
				case 0 :$imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Nota Crédito Repuestos")."\"/>"; break;
				case 1 :$imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Nota Crédito Servicios")."\"/>"; break;
				case 2 :$imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Nota Crédito Vehículos")."\"/>"; break;
			}
		} else {
			$imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Nota Crédito Repuestos")."\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td>"."<button type=\"button\" onclick=\"xajax_asignarDcto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>",
				$row['idNotaCredito']);//
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaNotaCredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_nota_credito']."</td>";
			$htmlTb .= "<td>".utf8_decode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_nota_credito'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"7\">";
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

function nuevoDcto($frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$arrayObj = $frmListaArticulo['cbx'];
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$queryInvFis = sprintf("SELECT * FROM iv_inventario_fisico inv_fis
	WHERE inv_fis.id_empresa = %s
		AND inv_fis.estatus = 0",
		valTpDato($idEmpresa , "int"));
	$rsInvFis = mysql_query($queryInvFis);
	if (!$rsInvFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsInvFis = mysql_num_rows($rsInvFis);
	
	if ($totalRowsInvFis == 0) {
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indiceItm=>$valorItm) {
				$objResponse->script("
				fila = document.getElementById('trItm:".$valorItm."');
				padre = fila.parentNode;
				padre.removeChild(fila);");
			}
		}
		
		// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
		$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rsUsuario = mysql_query($queryUsuario);
		if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowUsuario = mysql_fetch_assoc($rsUsuario);
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));"));
		$objResponse->assign("txtFecha","value",date("d-m-Y"));
		$objResponse->assign("hddIdEmpleado","value",$rowUsuario['id_empleado']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']));
		
		$objResponse->loadCommands(asignarTipoVale(""));
	} else {
		$objResponse->script("
		alert('".utf8_encode("Usted no puede Crear Vales de Entrada o Salida, debido a que está en Proceso un Inventario Físico")."');
		location='iv_ajuste_inventario_list.php';");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarDcto");
$xajax->register(XAJAX_FUNCTION,"asignarDisponibilidadUbicacion");
$xajax->register(XAJAX_FUNCTION,"asignarTipoVale");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"formImportar");
$xajax->register(XAJAX_FUNCTION,"formNotaCredito");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"importarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
$xajax->register(XAJAX_FUNCTION,"listaNotaCredito");
$xajax->register(XAJAX_FUNCTION,"nuevoDcto");

function insertarItem($contFila, $idEmpresa, $idPedidoCompraDetalle = "", $idArticulo = "", $cantPedida = "", $precioUnitario = "", $costoUnitario = "", $almacen = "", $ubicacion = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPedidoCompraDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
		WHERE id_pedido_compra_detalle = %s;",
			valTpDato($idPedidoCompraDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
	}
	
	$idArticulo = ($idArticulo == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_articulo'] : $idArticulo;
	$cantPedida = ($cantPedida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $cantPedida;
	$precioUnitario = ($precioUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $precioUnitario;
	$costoUnitario = ($costoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['costo_unitario'] : $costoUnitario;
	//$idCasilla = ($idCasilla == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_casilla'] : $idCasilla;
	
	// VERIFICA LA CANTIDAD DE UBICACIONES QUE TIENE
	$queryUbicArt = sprintf("SELECT * FROM vw_iv_articulos_almacen
	WHERE id_empresa = %s
		AND id_articulo = %s
		AND estatus_articulo_almacen = 1;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	$rsUbicArt = mysql_query($queryUbicArt);
	if (!$rsUbicArt) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
	$totalRowsUbicArt = mysql_num_rows($rsUbicArt);
	
	if ($almacen == "" && $ubicacion == "") {
		// BUSCA LA UBICACION PREDETERMINADA
		$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
		WHERE id_empresa = %s
			AND id_articulo = %s
			AND casilla_predeterminada = 1;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"));
		$rsArtAlm = mysql_query($queryArtAlm);
		if (!$rsArtAlm) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
		$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
		
		$idCasilla = $rowArtAlm['id_casilla'];
		$ubicacion = $rowArtAlm['descripcion_almacen']."\n".$rowArtAlm['ubicacion'];
	} else {
		// BUSCA SI EL ARTICULO TIENE ASIGNADA LA UBICACION
		$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
		WHERE id_empresa = %s
			AND id_articulo = %s
			AND descripcion_almacen LIKE %s
			AND REPLACE(CONVERT(ubicacion USING utf8), '-[]', '') LIKE REPLACE(%s, '-[]', '')
			AND estatus_articulo_almacen = 1;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($almacen, "text"),
			valTpDato($ubicacion, "text"));
		$rsArtAlm = mysql_query($queryArtAlm);
		if (!$rsArtAlm) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
		$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
		
		if ($totalRowsArtAlm > 0) {
			$idCasilla = $rowArtAlm['id_casilla'];
			$ubicacion = $rowArtAlm['descripcion_almacen']."\n".$rowArtAlm['ubicacion'];
		} else {
			// BUSCA LOS DATOS DE LA UBICACION
			$queryUbic = sprintf("SELECT * FROM vw_iv_casillas
			WHERE descripcion_almacen LIKE %s
				AND REPLACE(CONVERT(ubicacion USING utf8), '-[]', '') LIKE REPLACE(%s, '-[]', '');",
				valTpDato($almacen, "text"),
				valTpDato($ubicacion, "text"));
			$rsUbic = mysql_query($queryUbic);
			if (!$rsUbic) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
			$totalRowsUbic = mysql_num_rows($rsUbic);
			$rowUbic = mysql_fetch_assoc($rsUbic);
			
			$idCasilla = $rowUbic['id_casilla'];
			
			// VERIFICA SI ALGUN ARTICULO DE LA LISTA TIENE LA UBICACION YA OCUPADA
			$existe = false;
			if (isset($arrayObjUbicacion)) {
				foreach ($arrayObjUbicacion as $indice => $valor) {
					if ($arrayObjUbicacion[$indice][0] != $idArticulo && $arrayObjUbicacion[$indice][1] == $idCasilla) {
						$existe = true;
					}
				}
			}
			
			// VERIFICA SI ALGUN OTRO ARTICULO DE LA BASE DE DATOS TIENE LA UBICACION YA OCUPADA
			$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
			WHERE id_empresa = %s
				AND id_articulo <> %s
				AND descripcion_almacen LIKE %s
				AND REPLACE(ubicacion, '-[]', '') LIKE REPLACE(%s, '-[]', '')
				AND estatus_articulo_almacen = 1;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($almacen, "text"),
				valTpDato($ubicacion, "text"));
			$rsArtAlm = mysql_query($queryArtAlm);
			if (!$rsArtAlm) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
			$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
			
			if ($totalRowsArtAlm > 0)
				$existe = true;
			
			if ($existe == false) {
				$idCasilla = $rowUbic['id_casilla'];
				$ubicacion = $rowUbic['descripcion_almacen']."\n".$rowUbic['ubicacion'];
			} else {
				$totalRowsArtAlm = 0;
				$idCasilla = "";
				$ubicacion = "";
			}
		}
	}
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	if ($totalRowsUbicArt > 1) {
		$claseAlmacen = "trResaltar7";
	} else if (!($idCasilla > 0) && $totalRowsArtAlm == 0) {
		$claseAlmacen = "trResaltar6";
	}
	
	$claseCosto = ($precioUnitario > 0) ? "" : "divMsjError";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td class=\"%s\"><table><tr><td>".
				"<a class=\"modalImg\" id=\"aAlmacenItem:%s\" rel=\"#divFlotante\" style=\"display:none\"><img class=\"puntero\" src=\"../img/iconos/ico_transferir_para_almacen.gif\" title=\"%s\"/>".
			"</td><td id=\"spanUbicacion:%s\" align=\"center\" nowrap=\"nowrap\" width=\"%s\" title=\"spanUbicacion:%s\">%s</td></tr></table></td>".
			"<td id=\"tdCodArt:%s\">%s</td>".
			"<td><div id=\"tdDescArt:%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"hddCantArt%s\" name=\"hddCantArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td class=\"%s\"><input type=\"text\" id=\"hddPrecioArt%s\" name=\"hddPrecioArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoArt%s\" name=\"hddCostoArt%s\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"hddTotalArt%s\" name=\"hddTotalArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArt%s\" name=\"hddIdArt%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdValeDet%s\" name=\"hddIdValeDet%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				 $contFila,
			$contFila, $contFila,
			$claseAlmacen,
				$contFila, utf8_encode("Ubicación"),
				$contFila, "100%", $contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
			$contFila, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowArticulo['descripcion'])))),
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$claseCosto, $contFila, $contFila, number_format($precioUnitario, 2, ".", ","),
				$contFila, $contFila, number_format($costoUnitario, 2, ".", ","),
			$contFila, $contFila, number_format(($cantPedida * $precioUnitario), 2, ".", ","),
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $idCasilla,
				$contFila, $contFila, $idValeDetalle);
	
	return array(true, $htmlItmPie, $contFila, $arrayObjUbicacion);
}
?>