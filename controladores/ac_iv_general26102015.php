<?php
set_time_limit(0);

$ruta = explode("↓",str_replace(array("/","\\"),"↓",getcwd()));
$ruta = array_reverse($ruta);
$raizPpal = false;
foreach ($ruta as $indice => $valor) {
	$valor2 = explode("_",$valor);
	if ($valor2[0] != "erp" && $raizPpal == false) {
		$raiz .= "../";
		break;
	} else if ($valor2[0] == "erp") {
		$raizPpal = true;
	}
}

function actualizarEstatusSistemaSolicitud($numReferencia, $estatusPedidoVenta) {
	global $conex;
	
	$arrayRef = explode("-", $numReferencia);
	if ($arrayRef[0] == "SOL") {
		// ESTATUS DE ERP VS SOLICITUDES
		$arrayEstatus = array(
			NULL 	=> 1, 	// -						Vs	Proceso
			NULL 	=> 2,	// -						Vs	Cerrado
			0 		=> 3,	// Pendiente por Terminar	Vs	Espera Confirmacion
			1 		=> 4,	// Convertido a Pedido		Vs	Autorizado
			2 		=> 5,	// Pedido Aprobado			Vs	Confirmado
			3 		=> 6,	// Facturado				Vs	Facturado
			NULL 	=> 7,	// -						Vs	Despachado
			5 		=> 8);	// Anulado					Vs	Anulado
		
		//mysql_query("USE ".DBASE_SIGSO.";");
		
		$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".encabezado_pedido SET
			total_inicial = (SELECT SUM(det_ped.precio_cantidad) FROM ".DBASE_SIGSO.".detalle_pedido det_ped
							WHERE det_ped.id_pedido = encabezado_pedido.id_pedido),
			total_con_iva = (SELECT SUM(det_ped.precio_cantidad_iva) FROM ".DBASE_SIGSO.".detalle_pedido det_ped
							WHERE det_ped.id_pedido = encabezado_pedido.id_pedido),
			estatus = %s
		WHERE id_pedido = %s;",
			valTpDato($arrayEstatus[$estatusPedidoVenta], "int"),
			valTpDato($arrayRef[1], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
		
		return array(true, "La Solicitud ".$arrayRef[1]." Creada por el Sistema de Solicitudes ha sido Actualizado Correctamente");
		
		//mysql_query("USE ".DBASE_SIPRE_AUT.";");
	}
	
	return array(NULL, "");
}

function actualizarCantidadSistemaSolicitud($numReferencia, $idArticulo, $cantPendiente, $precioUnitario = 0, $gastoUnitario = 0, $porcIva = 0) {
	global $conex;
	
	$arrayRef = explode("-", $numReferencia);
	if ($arrayRef[0] == "SOL") {
		//mysql_query("USE ".DBASE_SIGSO.";");
		
		$cantPendiente = str_replace(",","",$cantPendiente);
		$precioUnitario = str_replace(",","",$precioUnitario);
		$gastoUnitario = str_replace(",","",$gastoUnitario);
		$porcIva = str_replace(",","",$porcIva);
		
		// BUSCAR PEDIDO DEPENDIENDO DEL NUMERO DE REFERENCIA
		$queryPedido = sprintf("SELECT * FROM ".DBASE_SIGSO.".encabezado_pedido
		WHERE id_pedido = %s;",
			valTpDato($arrayRef[1], "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		$idSolicitudPedido = $rowPedido['id_pedido'];
		
		$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".detalle_pedido SET 
			cantidad_despachada = %s
		WHERE id_pedido = %s
			AND id_articulo = %s;",
			valTpDato($cantPendiente, "int"),
			valTpDato($idSolicitudPedido, "int"),
			valTpDato($idArticulo, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
		
		if ($precioUnitario > 0) {
			// ACTUALIZA EL PRECIO DEL ARTICULO
			$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".detalle_pedido SET 
				precio_unitario = %s,
				precio_cantidad = cantidad_despachada * precio_unitario,
				precio_cantidad_iva = cantidad_despachada * (precio_unitario + (precio_unitario * %s / 100))
			WHERE id_pedido = %s
				AND id_articulo = %s;",
				valTpDato(($precioUnitario + $gastoUnitario), "real_inglesa"),
				valTpDato($porcIva, "real_inglesa"),
				valTpDato($idSolicitudPedido, "int"),
				valTpDato($idArticulo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA EL PRECIO DE LOS ARTICULOS QUE NO TIENEN CANTIDADES DESPACHADAS
		$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".detalle_pedido SET
			precio_cantidad = cantidad_despachada * precio_unitario,
			precio_cantidad_iva = cantidad_despachada * (precio_unitario + (precio_unitario * %s / 100))
		WHERE id_pedido = %s
			AND cantidad_despachada = 0;",
			valTpDato($porcIva, "real_inglesa"),
			valTpDato($idSolicitudPedido, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL TOTAL DEL PEDIDO
		$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".encabezado_pedido SET 
			total_inicial = (SELECT SUM(ped_det.precio_cantidad) FROM ".DBASE_SIGSO.".detalle_pedido ped_det
							WHERE ped_det.id_pedido = encabezado_pedido.id_pedido),
			total_con_iva = (SELECT SUM(ped_det.precio_cantidad_iva) FROM ".DBASE_SIGSO.".detalle_pedido ped_det
							WHERE ped_det.id_pedido = encabezado_pedido.id_pedido)
		WHERE id_pedido = %s;",
			valTpDato($idSolicitudPedido, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
		
		return array(true, "");
		
		//mysql_query("USE ".DBASE_SIPRE_AUT.";");
	}
	
	return array(NULL, "");
}

function calculoPorcentaje($frmTotalDcto, $tpCalculo, $valor, $objDestino) {
	$objResponse = new xajaxResponse();
	
	$subTotal = str_replace(",","",$frmTotalDcto['txtSubTotal']);
	$valor = str_replace(",","",$valor);
	
	if ($subTotal > 0) {
		$monto = ($tpCalculo == "Porc") ? $valor * ($subTotal / 100) : $valor * (100 / $subTotal);
	} else {
		$monto = 0;
	}
	
	$objResponse->assign($objDestino,"value",number_format($monto, 2, ".", ","));
		
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

if (!function_exists("cargaLstEmpresaFinal")) {
	function cargaLstEmpresaFinal($selId = "", $accion = "onchange=\"xajax_objetoCodigoDinamico('tdCodigoArt',this.value); byId('btnBuscar').click();\"", $nombreObj = "lstEmpresa") {
		$objResponse = new xajaxResponse();
		
		// EMPRESAS PRINCIPALES
		$queryUsuarioSuc = sprintf("SELECT DISTINCT
			id_empresa_reg,
			nombre_empresa
		FROM vw_iv_usuario_empresa
		WHERE id_usuario = %s
			AND id_empresa_padre_suc IS NULL
		ORDER BY nombre_empresa_suc ASC",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
		if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
			$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
		
			$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".utf8_encode($rowUsuarioSuc['id_empresa_reg'].".- ".$rowUsuarioSuc['nombre_empresa'])."</option>";	
		}
		
		// EMPRESAS CON SUCURSALES
		$query = sprintf("SELECT DISTINCT
			id_empresa,
			nombre_empresa
		FROM vw_iv_usuario_empresa
		WHERE id_usuario = %s
			AND id_empresa_padre_suc IS NOT NULL
		ORDER BY nombre_empresa",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		while ($row = mysql_fetch_assoc($rs)) {
			$htmlOption .= "<optgroup label=\"".$row['nombre_empresa']."\">";
			
			$queryUsuarioSuc = sprintf("SELECT DISTINCT
				id_empresa_reg,
				nombre_empresa_suc,
				sucursal
			FROM vw_iv_usuario_empresa
			WHERE id_usuario = %s
				AND id_empresa_padre_suc = %s
			ORDER BY nombre_empresa_suc ASC",
				valTpDato($_SESSION['idUsuarioSysGts'], "int"),
				valTpDato($row['id_empresa'], "int"));
			$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
			if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
				$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
			
				$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".utf8_encode($rowUsuarioSuc['id_empresa_reg'].".- ".$rowUsuarioSuc['nombre_empresa_suc'])."</option>";	
			}
		
			$htmlOption .= "</optgroup>";
		}
		
		$html = "<select id=\"".$nombreObj."\" name=\"".$nombreObj."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
			$html .= "<option value=\"\">[ Todos ]</option>";
			$html .= $htmlOption;
		$html .= "</select>";
		
		$objResponse->assign("td".$nombreObj,"innerHTML",$html);
		
		return $objResponse;
	}
}

function asignarEmpresaUsuario($idEmpresa, $objDestino, $nomVentana, $scriptCalculo = "", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT
		id_empresa_reg,
		IF (vw_iv_usu_emp.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_usu_emp.nombre_empresa, vw_iv_usu_emp.nombre_empresa_suc), vw_iv_usu_emp.nombre_empresa) AS nombre_empresa
	FROM vw_iv_usuario_empresa vw_iv_usu_emp
	WHERE id_empresa_reg = %s",
		valTpDato($idEmpresa, "text"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$objResponse->assign("txtId".$objDestino,"value",$rowEmpresa['id_empresa_reg']);
	$objResponse->assign("txt".$objDestino,"value",utf8_encode($rowEmpresa['nombre_empresa']));
	
	$objResponse->loadCommands(objetoCodigoDinamico("tdCodigoArt",$rowEmpresa['id_empresa_reg']));
	
	// VERIFICA VALORES DE CONFIGURACION (Días de Vencimiento del Presupuesto)
	$queryConfig8 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 8
		AND config_emp.status = 1
		AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig8 = mysql_query($queryConfig8);
	if (!$rsConfig8) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig8 = mysql_fetch_assoc($rsConfig8);
	
	$objResponse->assign("txtFechaVencimientoPresupuesto","value",date("d-m-Y",dateAddLab(time(),$rowConfig8['valor'],true)));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("
		byId('btnCancelar".$nomVentana."').click();");
	}
	
	if (strlen($scriptCalculo) > 0) {
		$objResponse->script($scriptCalculo);
	}
	
	return $objResponse;
}

function listadoEmpresasUsuario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanRIF;
	global $raiz;
	
	$objDestino = $valCadBusq[0];
	$nomVentana = $valCadBusq[1];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	
	if (strlen($valCadBusq[2]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR nombre_empresa_suc LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoEmpresasUsuario", "8%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listadoEmpresasUsuario", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanRIF));
		$htmlTh .= ordenarCampo("xajax_listadoEmpresasUsuario", "36%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listadoEmpresasUsuario", "36%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpresaUsuario('".$row['id_empresa_reg']."','".$objDestino."','".$nomVentana."');\" title=\"Seleccionar\"><img src=\"".$raiz."img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['rif'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".htmlentities($nombreSucursal)."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf .= "<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf .= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("div".$nomVentana,"innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function formularioGastos($bloquea = false, $idPedido = "", $tipoPedido = "", $modoCompra = 1, $frm = NULL) {
	$objResponse = new xajaxResponse();
	
	if ($modoCompra == 1) { // 1 = Nacional
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("gasto.id_modo_gasto IN (1)");
	} else if ($modoCompra == 2) { // 2 = Importacion
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("gasto.id_modo_gasto IN (1,3)");
	} else if ($modoCompra == 3) { // 3 = Nacional por Importacion
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("gasto.id_modo_gasto IN (3)");
	}
	
	$queryGastos = sprintf("SELECT
		gasto.id_gasto,
		gasto.nombre,
		gasto.id_modo_gasto,
		gasto.afecta_documento,
		gasto.id_iva,
		iva_comp.iva AS iva_compra,
		iva_comp.observacion AS observacion_iva_compra,
		iva_comp.tipo AS tipo_iva_compra,
		iva_comp.activo AS activo_iva_compra,
		iva_comp.estado AS estado_iva_compra,
		gasto.id_iva_venta,
		iva_vent.iva AS iva_venta,
		iva_vent.observacion AS observacion_iva_venta,
		iva_vent.tipo AS tipo_iva_venta,
		iva_vent.activo AS activo_iva_venta,
		iva_vent.estado AS estado_iva_venta,
		gasto.estatus_iva
	FROM pg_gastos gasto
		LEFT JOIN pg_iva iva_comp ON (gasto.id_iva = iva_comp.idIva)
		LEFT JOIN pg_iva iva_vent ON (gasto.id_iva_venta = iva_vent.idIva) %s
	ORDER BY gasto.id_modo_gasto, gasto.nombre ASC;", $sqlBusq);
	$rsGastos = mysql_query($queryGastos);
	if (!$rsGastos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	$html = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$contFila = 0;
	while ($rowGastos = mysql_fetch_assoc($rsGastos)) {
		$contFila++;
		
		$classPorc = ($checkPorc == "checked=\"checked\"") ? "class=\"inputInicial\"" : "";
		$classMonto = ($checkMonto == "checked=\"checked\"") ? "class=\"inputInicial\"" : "";
		
		$tipoGasto = 0;
			
		if ($frm != NULL) {
			$valueMontoPorc = number_format(str_replace(",","",$frm['txtPorcGasto'.$contFila]), 3, ".", ",");
			$valueMonto = number_format(str_replace(",","",$frm['txtMontoGasto'.$contFila]), 3, ".", ",");
			$checkPorc = ($frm['rbtGasto'.$contFila] == 1) ? "checked=\"checked\"" : "";
			$checkMonto = ($frm['rbtGasto'.$contFila] == 2) ? "checked=\"checked\"" : "";
			if ($checkPorc == "" && $checkMonto == "")
				$checkMonto = "checked=\"checked\"";
			$readOnlyMonto = ($checkPorc == "checked=\"checked\"") ? "readonly=\"readonly\"" : "";
		} else {
			$valueMontoPorc = number_format(0, 3, ".", ",");
			$valueMonto = number_format(0, 3, ".", ",");
			$checkPorc = "";
			$checkMonto = "checked=\"checked\"";
			$readOnlyMonto = ($checkPorc == "checked=\"checked\"") ? "readonly=\"readonly\"" : "";
		}
		
		if (in_array($tipoPedido, array("PRESUPUESTO","PEDIDO_VENTA","FACTURA_VENTA","NOTA_CREDITO"))) {
			// BUSCA LOS IMPUESTOS DEL GASTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryGastoImpuesto = sprintf("SELECT gasto_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
			FROM pg_iva iva
				INNER JOIN pg_gastos_impuesto gasto_impuesto ON (iva.idIva = gasto_impuesto.id_impuesto)
			WHERE iva.estado = 1 AND iva.tipo IN (6,2)
				AND gasto_impuesto.id_gasto = %s
			ORDER BY iva;",
				valTpDato($rowGastos['id_gasto'], "int"));
			$rsGastoImpuesto = mysql_query($queryGastoImpuesto);
			if (!$rsGastoImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsGastoImpuesto = mysql_num_rows($rsGastoImpuesto);
			$arrayIdIvaItm = array(-1);
			while ($rowGastoImpuesto = mysql_fetch_assoc($rsGastoImpuesto)) {
				$arrayIdIvaItm[] = $rowGastoImpuesto['id_impuesto'];
			}
			$hddIdIvaItm = implode(",",$arrayIdIvaItm);
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (6,2)
				AND idIva IN (%s);",
				valTpDato($hddIdIvaItm, "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		} else {
			// BUSCA LOS IMPUESTOS DEL GASTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryGastoImpuesto = sprintf("SELECT gasto_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
			FROM pg_iva iva
				INNER JOIN pg_gastos_impuesto gasto_impuesto ON (iva.idIva = gasto_impuesto.id_impuesto)
			WHERE iva.estado = 1 AND iva.tipo IN (1,3)
				AND gasto_impuesto.id_gasto = %s
			ORDER BY iva;",
				valTpDato($rowGastos['id_gasto'], "int"));
			$rsGastoImpuesto = mysql_query($queryGastoImpuesto);
			if (!$rsGastoImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsGastoImpuesto = mysql_num_rows($rsGastoImpuesto);
			$arrayIdIvaItm = array(-1);
			while ($rowGastoImpuesto = mysql_fetch_assoc($rsGastoImpuesto)) {
				$arrayIdIvaItm[] = $rowGastoImpuesto['id_impuesto'];
			}
			$hddIdIvaItm = implode(",",$arrayIdIvaItm);
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (1,3)
				AND idIva IN (%s);",
				valTpDato($hddIdIvaItm, "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		}
		
		if ($idPedido > 0) {
			switch ($tipoPedido) {
				case "COMPRA" : 
					$queryPedidoGastos = "SELECT * FROM iv_pedido_compra_gasto
					WHERE id_pedido_compra = %s
						AND id_gasto = %s;";
					break;
				case "COMPRA_GA" :
					$queryPedidoGastos = "SELECT * FROM ga_orden_compra_gasto
					WHERE id_orden_compra = %s
						AND id_gasto = %s;";
					break;
			 	case "PREREGISTRO" :
					$queryPedidoGastos = "SELECT * FROM iv_factura_compra_gasto
					WHERE id_factura_compra = %s
						AND id_gasto = %s;";
					break;
			 	case "REGISTRO" :
					$queryPedidoGastos = "SELECT * FROM cp_factura_gasto
					WHERE id_factura = %s
						AND id_gasto = %s;";
					break;
			 	case "PRESUPUESTO" :
					$queryPedidoGastos = "SELECT * FROM iv_presupuesto_venta_gasto
					WHERE id_presupuesto_venta = %s
						AND id_gasto = %s;";
					break;
			 	case "PEDIDO_VENTA" :
					$queryPedidoGastos = "SELECT * FROM iv_pedido_venta_gasto
					WHERE id_pedido_venta = %s
						AND id_gasto = %s;";
					break;
			 	case "FACTURA_VENTA" :
					$queryPedidoGastos = "SELECT * FROM cj_cc_factura_gasto
					WHERE id_factura = %s
						AND id_gasto = %s;";
					break;
			 	case "NOTA_CREDITO" :
					$queryPedidoGastos = "SELECT * FROM cj_cc_nota_credito_gasto
					WHERE id_nota_credito = %s
						AND id_gasto = %s;";
					break;
			}
			$queryPedidoGastos = sprintf($queryPedidoGastos,
				valTpDato($idPedido, "int"),
				valTpDato($rowGastos['id_gasto'], "int"));
			$rsPedidoGastos = mysql_query($queryPedidoGastos);
			if (!$rsPedidoGastos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			$rowPedidoGastos = mysql_fetch_assoc($rsPedidoGastos);
			
			if ($rowPedidoGastos['tipo_iva_compra'] == "0" || $rowPedidoGastos['tipo'] == "0") { // PORCENTAJE
				$valueMontoPorc = number_format($rowPedidoGastos['porcentaje_monto'], 3, ".", ",");
				$valueMonto = number_format($rowPedidoGastos['monto'], 3, ".", ",");
				$checkPorc = "checked=\"checked\"";
				$checkMonto = "";
				$readOnlyPorc = "";
				$readOnlyMonto = "readonly=\"readonly\"";
				$tipoGasto = 0; // 0 = Porcentaje
			} else { // MONTO
				$valueMontoPorc = number_format($rowPedidoGastos['porcentaje_monto'], 3, ".", ",");
				$valueMonto = number_format($rowPedidoGastos['monto'], 3, ".", ",");
				$checkPorc = "";
				$checkMonto = "checked=\"checked\"";
				$readOnlyPorc = "readonly=\"readonly\"";
				$readOnlyMonto = "";
				$tipoGasto = 1; // 1 = Monto Fijo
			}
		}
		
		if ($bloquea == false) {
			$classPorc = ($checkPorc == "checked=\"checked\"") ? "class=\"inputHabilitado\"" : "";
			$classMonto = ($checkMonto == "checked=\"checked\"") ? "class=\"inputHabilitado\"" : "";
			
			$tipoGasto = ($checkPorc == "checked=\"checked\"") ? 0 : 1;
		} else {
			$disabled = "disabled=\"disabled\"";
			$readOnlyPorc = "readonly=\"readonly\"";
			$readOnlyMonto = "readonly=\"readonly\"";
			$classPorc = "class=\"inputInicial\"";
			$classMonto = "class=\"inputInicial\"";
		}
	
	$html .= "<tr align=\"right\" id=\"trGasto:".$contFila."\">";
		$html .= "<td class=\"tituloCampo\" title=\"trGasto:".$contFila."\" width=\"35%\">".utf8_encode($rowGastos['nombre']).":";
			$html .= "<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"".$contFila."\">";
			$html .= "<input type=\"hidden\" id=\"hddIdGasto".$contFila."\" name=\"hddIdGasto".$contFila."\" value=\"".$rowGastos['id_gasto']."\">";
			$html .= "<input type=\"hidden\" id=\"hddTipoGasto".$contFila."\" name=\"hddTipoGasto".$contFila."\" value=\"".$tipoGasto."\">";
		$html .= "</td>";
		$html .= "<td nowrap=\"nowrap\" width=\"20%\">";
			$html .= "<input type=\"radio\" id=\"rbtGastoPorc".$contFila."\" name=\"rbtGasto".$contFila."\" ".$checkPorc." ".$disabled." onclick=\"
			byId('hddTipoGasto".$contFila."').value = 0;
			byId('txtPorcGasto".$contFila."').readOnly = false;
			byId('txtPorcGasto".$contFila."').className = 'inputHabilitado';
			byId('txtMontoGasto".$contFila."').readOnly = true;
			byId('txtMontoGasto".$contFila."').className = 'inputInicial';\" value=\"1\"/>";
			
			$html .= sprintf("<input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" maxlength=\"8\" size=\"6\" style=\"text-align:right\"
			onblur=\"
			setFormatoRafk(this,2);
			if (byId('rbtGastoPorc%s').checked == true) {
				xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Porc', this.value, 'txtMontoGasto%s');
			}\"
			onfocus=\"if (byId('txtPorcGasto%s').value <= 0){ byId('txtPorcGasto%s').select(); }\"
			onkeypress=\"return validarSoloNumerosReales(event);\" value=\"%s\" %s %s/>",
				$contFila, $contFila,
				$contFila,
				$contFila,
				$contFila, $contFila,
				$valueMontoPorc, $classPorc, $readOnlyPorc);
		$html .= "%</td>";
		$html .= "<td nowrap=\"nowrap\" width=\"30%\">";
			$html .= "<input type=\"radio\" id=\"rbtGastoMonto".$contFila."\" name=\"rbtGasto".$contFila."\" ".$checkMonto." ".$disabled." onclick=\"
			byId('hddTipoGasto".$contFila."').value = 1;
			byId('txtPorcGasto".$contFila."').readOnly = true;
			byId('txtPorcGasto".$contFila."').className = 'inputInicial';
			byId('txtMontoGasto".$contFila."').readOnly = false;
			byId('txtMontoGasto".$contFila."').className = 'inputHabilitado';\" value=\"2\"/>";
				
			$html .= "<span id=\"spnGastoMoneda".$contFila."\"></span>&nbsp;";
			
			$html .= sprintf("<input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" maxlength=\"12\" size=\"16\" style=\"text-align:right\"
			onblur=\"
			setFormatoRafk(this,2);
			if (byId('rbtGastoMonto%s').checked == true) {
				xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtPorcGasto%s');
			}\"
			onfocus=\"if (byId('txtMontoGasto%s').value <= 0){ byId('txtMontoGasto%s').select(); }\"
			onkeypress=\"return validarSoloNumerosReales(event);\" value=\"%s\" %s %s/>",
				$contFila, $contFila,
				$contFila,
				$contFila,
				$contFila, $contFila,
				$valueMonto, $classMonto, $readOnlyMonto);
		$html .= "</td>";
		$html .= "<td width=\"15%\">";
			$contIva = 0;
			$ivaUnidad = "";
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr><td><img id=\"imgIvaGasto%s:%s\" src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/></td><td width=\"%s\">".
				"<input type=\"text\" id=\"hddIvaGasto%s:%s\" name=\"hddIvaGasto%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaGasto%s:%s\" name=\"hddIdIvaGasto%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIvaGasto%s:%s\" name=\"hddLujoIvaGasto%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s:%s\" name=\"hddEstatusIvaGasto%s:%s\" value=\"%s\">".
				"<input id=\"cbxIvaGasto\" name=\"cbxIvaGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td></tr></table>", 
					"100%", $contFila, $contIva, "100%",
					$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
					$contFila.":".$contIva);
			}
			$html .= $ivaUnidad;
			if ($rowGastos['id_modo_gasto'] == 1 && $rowGastos['afecta_documento'] == 0) {
				$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"."</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
		$html .= "</td>";
	$html .= "</tr>";
	}
	$html .= "<tr>";
		$html .= "<td colspan=\"4\" class=\"divMsjInfo2\">";
			$html .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$html .= "<tr>";
				$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\" width=\"25\"/></td>";
				$html .= "<td align=\"center\">";
					$html .= "<table>";
					$html .= "<tr>";
						$html .= "<td><img src=\"../img/iconos/accept.png\" /></td>";
						$html .= "<td>Gastos que llevan impuesto</td>";
						$html .= "<td>&nbsp;</td>";
						$html .= "<td><img src=\"../img/iconos/stop.png\" /></td>";
						$html .= "<td>No afecta cuenta por pagar</td>";
					$html .= "</tr>";
					$html .= "</table>";
				$html .= "</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	return $html;
}


function encabezadoEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (!($idEmpresa > 0)) {
		$idEmpresa = 100;
	}
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$row = mysql_fetch_assoc($rs);
	
	if ($row['id_empresa'] != "") {
		$html .= "<table class=\"textoNegrita_8px\">";
		$html .= "<tr align=\"center\">";
			$html .= "<td>";
				$html .= "<img src=\"../".htmlentities($row['logo_familia'])."\" width=\"100\"/>";
			$html .= "</td>";
			$html .= "<td>";
				$html .= "<table width=\"250\">";
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['nombre_empresa']);
					$html .= "</td>";
				$html .= "</tr>";
			if (strlen($row['rif']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>R.I.F.: ";
						$html .= $row['rif'];
					$html .= "</td>";
				$html .= "</tr>";
			}
			if (strlen($row['direccion']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['direccion']);
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
				$html .= "<table>";
			$html .= "</td>";
		$html .= "</tr>";
		$html .= "<table>";
		
		$objResponse->assign("tdEncabezadoImprimir","innerHTML",$html);
	}
	
	return $objResponse;
}


function objetoCodigoDinamico($tdUbicacion, $idEmpresa, $idEmpArticulo = "", $valor = "", $formato = "", $bloquearObj = false, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("-", $formato);
	
	$valorCad = explode(";", $valor);
	
	if ($idEmpresa == "" && $formato == "")
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	// SI NO SE PASA UN FORMATO, SE BUSCA EL FORMATO PREDEFINIDO DE LA EMPRESA
	if ($formato == "") {
		$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
			valTpDato($idEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$row = mysql_fetch_assoc($rs);
		
		if ($row['id_empresa_reg'] != "")
			$valCadBusq = explode("-", $row['formato_codigo_repuestos']);
		else {
			$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
				valTpDato($idEmpArticulo, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			$row = mysql_fetch_assoc($rs);
			
			$valCadBusq = explode("-", $row['formato_codigo_repuestos']);
		}
		
		$contTamano = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamano += $valor;
		}
	}
	
	// SI LA CANTIDAD DE SUBDIVISIONES DEL FORMATO NO ES IGUAL A EL DE LA DATA, SE CONTRUIRA UN SOLO OBJETO CON TODO EL
	// CODIGO SIN SUBDIVISIONES
	if (count($valCadBusq) != count($valorCad) && $valor != "" && count($valorCad) > 1) {
		$contTamanoFormato = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamanoFormato += $valor;
		}
		$contTamanoCaracter = 0;
		foreach ($valorCad as $indice => $valor) {
			$contTamanoCaracter += strlen($valor);
		}
		
		$contTamano = $contTamanoFormato;
		if ($contTamanoFormato <= $contTamanoCaracter)
			$contTamano = $contTamanoCaracter;
		
		$valCadBusq = NULL;
		$valCadBusq[] = $contTamano;
		
		$value = "";
		foreach ($valorCad as $indice => $valor)
			$value .= $valor;
		
		$valorCad = NULL;
		$valorCad[] = $value;
	}
	
	$readonly = "";
	if ($bloquearObj == true) {
		$readonly = "readonly=\"readonly\"";
	}
	
	$html = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	$html .= "<tr>";
	foreach ($valCadBusq as $indice => $valor) {
		$value = "";
		if ($valor != "")
			$value = $valorCad[$indice];
		
		$tamanoObjeto = $valor+2;
		if (count($valCadBusq) == 1) {
			$tamanoObjeto = intval($contTamano) + 4;
		}
		
		$html .= sprintf("<td><input type=\"text\" id=\"%s\" name=\"%s\" onkeyup=\"letrasMayusculas(event, this.id);\" onkeypress=\"return validarCodigoArticulo(event);\" ".$readonly." size=\"%s\" maxlength=\"%s\" value=\"%s\"/></td><td>&nbsp;</td>",
			"txtCodigoArticulo".$nombreObjeto.$indice,
			"txtCodigoArticulo".$nombreObjeto.$indice,
			$tamanoObjeto,
			$valor,
			$value);
		
		$cantObjetos = strval($indice);
	}
	$html .= "</tr>";
	$html .= "</table>";
	$html .= sprintf("<input type=\"hidden\" id=\"%s\" name=\"%s\" readonly=\"readonly\" size=\"2\" value=\"%s\"/>",
		"hddCantCodigo".$nombreObjeto,
		"hddCantCodigo".$nombreObjeto,
		$cantObjetos);
	
	$objResponse->assign($tdUbicacion,"innerHTML",$html);

	return $objResponse;
}

function objetoCodigoDinamicoCompras($tdUbicacion, $idEmpresa, $idEmpArticulo = "", $valor = "", $formato = "", $bloquearObj = "false") {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("-", $formato);
	
	$valorCad = explode("-", $valor);
	
	if ($idEmpresa == "" && $formato == "")
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	/* SI NO SE PASA UN FORMATO, SE BUSCA EL FORMATO PREDEFINIDO DE LA EMPRESA*/
	if ($formato == "") {
		$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
			valTpDato($idEmpresa,"int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$row = mysql_fetch_assoc($rs);
		
		if ($row['id_empresa_reg'] != "")
			$valCadBusq = explode("-", $row['formato_codigo_compras']);
		else {
			$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
				valTpDato($idEmpArticulo,"int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			$row = mysql_fetch_assoc($rs);
			
			$valCadBusq = explode("-", $row['formato_codigo_compras']);
		}
		
		$contTamano = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamano += $valor;
		}
	}
	
	/* SI LA CANTIDAD DE SUBDIVISIONES DEL FORMATO NO ES IGUAL A EL DE LA DATA, SE CONTRUIRA UN SOLO OBJETO CON TODO EL
	CODIGO SIN SUBDIVISIONES */
	if (count($valCadBusq) != count($valorCad) && $valor != "" && count($valorCad) > 1) {
		$contTamanoFormato = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamanoFormato += $valor;
		}
		$contTamanoCaracter = 0;
		foreach ($valorCad as $indice => $valor) {
			$contTamanoCaracter += strlen($valor);
		}
		
		$contTamano = $contTamanoFormato;
		if ($contTamanoFormato <= $contTamanoCaracter)
			$contTamano = $contTamanoCaracter;
		
		$valCadBusq = NULL;
		$valCadBusq[] = $contTamano;
		
		$value = "";
		foreach ($valorCad as $indice => $valor)
			$value .= $valor;
		
		$valorCad = NULL;
		$valorCad[] = $value;
	}
	
	$readonly = "";
	if ($bloquearObj == "true") {
		$readonly = "readonly=\"readonly\"";
	}
	
	$html = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	$html .= "<tr>";
	foreach ($valCadBusq as $indice => $valor) {
		$value = "";
		if ($valor != "")
			$value = $valorCad[$indice];
		
		$tamanoObjeto = $valor+2;
		if (count($valCadBusq) == 1) {
			$tamanoObjeto = intval($contTamano) + 4;
		}
		
		$html .= sprintf("<td><input type=\"text\" id=\"txtCodigoArticulo%s\" name=\"txtCodigoArticulo%s\" onkeyup=\"letrasMayusculas(event, this.id);\" ".$readonly." size=\"%s\" maxlength=\"%s\" value=\"%s\"/></td><td>&nbsp;</td>",
			$indice,
			$indice,
			$tamanoObjeto,
			$valor,
			$value);
		
		$cantObjetos = strval($indice);
	}
	$html .= "</tr>";
	$html .= "</table>";
	$html .= sprintf("<input type=\"hidden\" id=\"hddCantCodigo\" name=\"hddCantCodigo\" readonly=\"readonly\" size=\"2\" value=\"%s\"/>",
		$cantObjetos);
	
	$objResponse->assign($tdUbicacion,"innerHTML",$html);

	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"calculoPorcentaje");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresaFinal");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresaUsuario");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresasUsuario");
$xajax->register(XAJAX_FUNCTION,"formularioGastos");
$xajax->register(XAJAX_FUNCTION,"encabezadoEmpresa");
$xajax->register(XAJAX_FUNCTION,"objetoCodigoDinamico");
$xajax->register(XAJAX_FUNCTION,"objetoCodigoDinamicoCompras");

function actualizarEstatusSolicitudRepuestos($idSolicitud = "") {
	global $conex;
	
	if ($idSolicitud != "-1" && $idSolicitud != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("id_solicitud = %s",
			valTpDato($idSolicitud, "int"));
	}
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE MIENTRAS LA SOLICITUD NO ESTE BLOQUEDA (REPUESTOS QUE NO FUERON APROBADOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 1
	WHERE id_estado_solicitud = 2
		AND (tiempo_aprobacion = 0 OR tiempo_aprobacion IS NULL)
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE (REPUESTOS QUE NO FUERON DESAPROBADOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 2
	WHERE id_estado_solicitud = 1
		AND tiempo_aprobacion > 0
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE (REPUESTOS QUE NO FUERON DESPACHADOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 2
	WHERE id_estado_solicitud = 3
		AND (tiempo_despacho = 0 OR tiempo_despacho IS NULL)
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE (REPUESTOS QUE NO FUERON DEVUELTOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 3
	WHERE id_estado_solicitud = 4
		AND (tiempo_devolucion = 0 OR tiempo_devolucion IS NULL)
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE (REPUESTOS QUE NO FUERON ANULADOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 1
	WHERE id_estado_solicitud = 6
		AND (tiempo_anulacion = 0 OR tiempo_anulacion IS NULL)
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("sol_rep.id_usuario_bloqueo > 0 OR sol_rep.id_usuario_bloqueo IS NOT NULL OR sol_rep.id_usuario_bloqueo IS NULL");
		
	if ($idSolicitud != "-1" && $idSolicitud != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sol_rep.id_solicitud = %s",
			valTpDato($idSolicitud, "int"));
	}
	
	// BUSCA LOS DATOS DE LA SOLICITUD
	$query = sprintf("SELECT
		id_solicitud,
		
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud) AS cantidad,
		
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 1) AS cantidad_solicitada,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 2
			AND tiempo_aprobacion > 0) AS cantidad_aprobada,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 3
			AND tiempo_despacho > 0) AS cantidad_despachada,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 4
			AND tiempo_devolucion > 0) AS cantidad_devuelta,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 5) AS cantidad_facturada,
			
			(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 6) AS cantidad_anulada,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 10) AS cantidad_no_despachada
		
	FROM sa_solicitud_repuestos sol_rep %s;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	while ($row = mysql_fetch_assoc($rs)) {
		if ($row['cantidad_facturada'] > 0) {
			$estatusSolicitud = 5; // FACTURADO
		} else if ($row['cantidad_devuelta'] == $row['cantidad']) {
			$estatusSolicitud = 4; // DEVUELTO
		} else if ($row['cantidad_devuelta'] > 0 && $row['cantidad_devuelta'] >= $row['cantidad_despachada']) {
			$estatusSolicitud = 9; // DEVUELTA PARCIAL
		} else if ($row['cantidad_despachada'] == $row['cantidad']) {
			$estatusSolicitud = 3; // DESPACHADO
		} else if ($row['cantidad_despachada'] > 0 && $row['cantidad_despachada'] >= $row['cantidad_devuelta']) {
			$estatusSolicitud = 8; // DESPACHADA PARCIAL
		} else if ($row['cantidad_aprobada'] == $row['cantidad']) {
			$estatusSolicitud = 2; // APROBADO
		} else if ($row['cantidad_aprobada'] > 0 && $row['cantidad_aprobada'] < $row['cantidad']) {
			$estatusSolicitud = 7; // APROBADA PARCIAL
		} else if ($row['cantidad_anulada'] == $row['cantidad']) {
			$estatusSolicitud = 6; // SOLICITADO
		} else if ($row['cantidad_solicitada'] == $row['cantidad'] || $row['cantidad_solicitada'] > 0) {
			$estatusSolicitud = 1; // SOLICITADO
			
			$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET 
				id_jefe_taller = NULL,
				id_jefe_repuesto = NULL,
				id_gerente_postventa = NULL,
				id_empleado_recibo = NULL,
				id_empleado_entrega = NULL,
				id_empleado_devuelto = NULL
			WHERE id_solicitud = %s;",
				valTpDato($row['id_solicitud'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA EL ESTADO DE LA SOLICITUD
		$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET 
			estado_solicitud = %s
		WHERE id_solicitud = %s;",
			valTpDato($estatusSolicitud, "int"),
			valTpDato($row['id_solicitud'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
	}
	
	return array(true, "");
}

function actualizarSaldos($idArticulo = "", $idCasilla = "", $idCasillaAnt = "") {
	global $conex;
	
	($idCasilla != "-1" && $idCasilla != "") ? $arrayIdCasilla[] = $idCasilla : "";
	($idCasillaAnt != "-1" && $idCasillaAnt != "") ? $arrayIdCasilla[] = $idCasillaAnt : "";
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADA)
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if (count($arrayIdCasilla) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_casilla IN (%s)",
			valTpDato(implode(",",$arrayIdCasilla), "campo"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
		cantidad_entrada = (SELECT SUM(kardex.cantidad)
							FROM iv_kardex kardex
							WHERE kardex.id_articulo = iv_articulos_almacen.id_articulo
								AND kardex.id_casilla = iv_articulos_almacen.id_casilla
								AND kardex.tipo_movimiento IN (1,2)),
		cantidad_salida = (SELECT SUM(kardex.cantidad)
							FROM iv_kardex kardex
							WHERE kardex.id_articulo = iv_articulos_almacen.id_articulo
								AND kardex.id_casilla = iv_articulos_almacen.id_casilla
								AND kardex.tipo_movimiento IN (3,4)),
		cantidad_reservada = (SELECT COUNT(det_orden_art.id_articulo) AS cantidad_reservada
							FROM sa_det_solicitud_repuestos det_solicitud_rep
								JOIN sa_det_orden_articulo det_orden_art ON (det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
							WHERE det_orden_art.id_articulo = iv_articulos_almacen.id_articulo
								AND det_solicitud_rep.id_casilla = iv_articulos_almacen.id_casilla
								AND det_solicitud_rep.id_estado_solicitud IN (2,3)),
		cantidad_espera = (SELECT SUM(ped_venta_det.pendiente)
							FROM iv_pedido_venta_detalle ped_venta_det
								JOIN iv_pedido_venta ON (ped_venta_det.id_pedido_venta = iv_pedido_venta.id_pedido_venta)
							WHERE ped_venta_det.id_articulo = iv_articulos_almacen.id_articulo
								AND ped_venta_det.id_casilla = iv_articulos_almacen.id_casilla
								AND ped_venta_det.estatus IN (0,1)
								AND (SELECT iv_pedido_venta.estatus_pedido_venta AS estatus_pedido_venta FROM iv_pedido_venta
									WHERE iv_pedido_venta.id_pedido_venta = ped_venta_det.id_pedido_venta) IN (0,1,2)),
		cantidad_bloqueada = (SELECT SUM(bloqueo_vent_det.cantidad)
								FROM iv_bloqueo_venta_detalle bloqueo_vent_det
								WHERE bloqueo_vent_det.id_articulo = iv_articulos_almacen.id_articulo
									AND bloqueo_vent_det.id_casilla = iv_articulos_almacen.id_casilla
									AND bloqueo_vent_det.estatus IN (1,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_alm.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LAS UBICACIONES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen art_alm
	WHERE ((art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_espera) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_bloqueada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) < 0)
		AND art_alm.estatus = 1 %s", $sqlBusq);
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	while ($rowArtAlm = mysql_fetch_assoc($rsArtAlm)) {
		$arrayAlmacenInvalido[] = $rowArtAlm['id_articulo_almacen'];
	}
	if ($totalRowsArtAlm > 0) { return array(false, "La(s) ubicacion(es) Id. ".implode(", ", $arrayAlmacenInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	return array(true, "");
}

function actualizarPedidas($idArticulo) {
	global $conex;
	
	// INICIALIZA LAS PEDIDAS EN LOS ARTICULOS QUE TENGAN ALGUNA UBICACION
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET cantidad_pedida = 0
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	// ACTUALIZA LA PEDIDAS DE LOS ARTICULOS QUE TIENEN UBICACION
	$updateSQL = sprintf("UPDATE iv_articulos_almacen art_alm, iv_almacenes alm, iv_calles calle, iv_estantes estante, iv_tramos tramo, iv_casillas casilla SET
		cantidad_pedida = IFNULL((SELECT SUM(pendiente)
							FROM iv_pedido_compra ped_comp
							 INNER JOIN iv_pedido_compra_detalle ped_comp_det ON (ped_comp.id_pedido_compra = ped_comp_det.id_pedido_compra)
							WHERE ped_comp_det.id_articulo = art_alm.id_articulo
								AND ped_comp_det.estatus IN (0)
								AND ped_comp.id_empresa = alm.id_empresa
								AND ped_comp.estatus_pedido_compra IN (0,1,2)), 0)
	WHERE alm.id_almacen = calle.id_almacen
		AND calle.id_calle = estante.id_calle
		AND estante.id_estante = tramo.id_estante
		AND tramo.id_tramo = casilla.id_tramo
		AND casilla.id_casilla = art_alm.id_casilla
		AND art_alm.id_articulo = %s
		AND IF (((SELECT count(art_emp.id_articulo) AS casilla_predeterminada FROM iv_articulos_empresa art_emp
			WHERE ((art_emp.id_empresa = alm.id_empresa)
				AND (art_emp.id_articulo = art_alm.id_articulo)
				AND (art_emp.id_casilla_predeterminada_compra = casilla.id_casilla))) > 0), 1, NULL) = 1
		AND (art_alm.estatus = 1 AND alm.estatus_almacen_compra = 1);",
		valTpDato($idArticulo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	// INICIALIZA LAS PEDIDAS EN LOS ARTICULOS QUE NO TIENEN UBICACION
	$updateSQL = sprintf("UPDATE iv_articulos_empresa SET cantidad_pedida = 0
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	// ACTUALIZA LA PEDIDAS DE LOS ARTICULOS QUE NO TIENEN UBICACION
	$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
		cantidad_pedida = (SELECT SUM(pendiente)
							FROM iv_pedido_compra ped_comp
							 INNER JOIN iv_pedido_compra_detalle ped_comp_det ON (ped_comp.id_pedido_compra = ped_comp_det.id_pedido_compra)
							WHERE ped_comp_det.id_articulo = iv_articulos_empresa.id_articulo
								AND ped_comp_det.estatus IN (0)
								AND ped_comp.id_empresa = iv_articulos_empresa.id_empresa
								AND ped_comp.estatus_pedido_compra IN (0,1,2))
	WHERE id_articulo = %s
		AND ((id_casilla_predeterminada IS NULL
				OR (SELECT COUNT(art_alm.id_articulo)
					FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = iv_articulos_empresa.id_casilla_predeterminada
						AND art_alm.id_articulo = iv_articulos_empresa.id_articulo
						AND art_alm.estatus = 1) = 0)
			AND (id_casilla_predeterminada_compra IS NULL
				OR (SELECT COUNT(art_alm.id_articulo)
					FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = iv_articulos_empresa.id_casilla_predeterminada_compra
						AND art_alm.id_articulo = iv_articulos_empresa.id_articulo
						AND art_alm.estatus = 1) = 0));",
		valTpDato($idArticulo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	return array(true, "");
}

function actualizarReservada($idArticulo = "", $idCasilla = "", $idCasillaAnt = "") {
	global $conex;
	
	($idCasilla != "-1" && $idCasilla != "") ? $arrayIdCasilla[] = $idCasilla : "";
	($idCasillaAnt != "-1" && $idCasillaAnt != "") ? $arrayIdCasilla[] = $idCasillaAnt : "";
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADA)
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if (count($arrayIdCasilla) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_casilla IN (%s)",
			valTpDato(implode(",",$arrayIdCasilla), "campo"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
		cantidad_reservada = (SELECT COUNT(det_orden_art.id_articulo) AS cantidad_reservada
							FROM sa_det_solicitud_repuestos det_solicitud_rep
								JOIN sa_det_orden_articulo det_orden_art ON (det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
							WHERE det_orden_art.id_articulo = iv_articulos_almacen.id_articulo
								AND det_solicitud_rep.id_casilla = iv_articulos_almacen.id_casilla
								AND det_solicitud_rep.id_estado_solicitud IN (2,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_alm.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LAS UBICACIONES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen art_alm
	WHERE ((art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_espera) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_bloqueada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) < 0)
		AND art_alm.estatus = 1 %s", $sqlBusq);
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	while ($rowArtAlm = mysql_fetch_assoc($rsArtAlm)) {
		$arrayAlmacenInvalido[] = $rowArtAlm['id_articulo_almacen'];
	}
	if ($totalRowsArtAlm > 0) { return array(false, "La(s) ubicacion(es) Id. ".implode(", ", $arrayAlmacenInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
		
	return array(true, "");
}

function actualizacionEsperaPorFacturar($idArticulo = "", $idCasilla = "", $idCasillaAnt = "") {
	global $conex;
	
	($idCasilla != "-1" && $idCasilla != "") ? $arrayIdCasilla[] = $idCasilla : "";
	($idCasillaAnt != "-1" && $idCasillaAnt != "") ? $arrayIdCasilla[] = $idCasillaAnt : "";
	
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if (count($arrayIdCasilla) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_casilla IN (%s)",
			valTpDato(implode(",",$arrayIdCasilla), "campo"));
	}
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
		cantidad_espera = (SELECT SUM(ped_venta_det.pendiente)
							FROM iv_pedido_venta_detalle ped_venta_det
								JOIN iv_pedido_venta ON (ped_venta_det.id_pedido_venta = iv_pedido_venta.id_pedido_venta)
							WHERE ped_venta_det.id_articulo = iv_articulos_almacen.id_articulo
								AND ped_venta_det.id_casilla = iv_articulos_almacen.id_casilla
								AND ped_venta_det.estatus NOT IN (2)
								AND (
									(SELECT iv_pedido_venta.estatus_pedido_venta AS estatus_pedido_venta FROM iv_pedido_venta
									WHERE iv_pedido_venta.id_pedido_venta = ped_venta_det.id_pedido_venta) = 0
									OR (SELECT iv_pedido_venta.estatus_pedido_venta AS estatus_pedido_venta FROM iv_pedido_venta
										WHERE iv_pedido_venta.id_pedido_venta = ped_venta_det.id_pedido_venta) = 1
									OR (SELECT iv_pedido_venta.estatus_pedido_venta AS estatus_pedido_venta FROM iv_pedido_venta
										WHERE iv_pedido_venta.id_pedido_venta = ped_venta_det.id_pedido_venta) = 2
								)
							) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		
	return array(true, "");
}

function actualizarBloqueada($idArticulo = "", $idCasilla = "", $idCasillaAnt = "") {
	global $conex;
	
	($idCasilla != "-1" && $idCasilla != "") ? $arrayIdCasilla[] = $idCasilla : "";
	($idCasillaAnt != "-1" && $idCasillaAnt != "") ? $arrayIdCasilla[] = $idCasillaAnt : "";
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADA)
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if (count($arrayIdCasilla) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_casilla IN (%s)",
			valTpDato(implode(",",$arrayIdCasilla), "campo"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
		cantidad_bloqueada = (SELECT SUM(bloqueo_vent_det.cantidad)
								FROM iv_bloqueo_venta_detalle bloqueo_vent_det
								WHERE bloqueo_vent_det.id_articulo = iv_articulos_almacen.id_articulo
									AND bloqueo_vent_det.id_casilla = iv_articulos_almacen.id_casilla
									AND bloqueo_vent_det.estatus IN (1,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_alm.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LAS UBICACIONES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen art_alm
	WHERE ((art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_espera) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_bloqueada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) < 0)
		AND art_alm.estatus = 1 %s", $sqlBusq);
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	while ($rowArtAlm = mysql_fetch_assoc($rsArtAlm)) {
		$arrayAlmacenInvalido[] = $rowArtAlm['id_articulo_almacen'];
	}
	if ($totalRowsArtAlm > 0) { return array(false, "La(s) ubicacion(es) Id. ".implode(", ", $arrayAlmacenInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
		
	return array(true, "");
}

function actualizarMovimientoTotal($idArticulo = "", $idEmpresa = "") {
	global $conex;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((SELECT SUM(IF(estado = 0, cantidad, (-1) * cantidad)) AS cantidad FROM iv_kardex kardex
	WHERE kardex.id_articulo = art_emp.id_articulo
		AND (SELECT almacen.id_empresa
			FROM iv_calles calle
				INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
				INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
				INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
				INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
			WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa) = 0
	OR art_emp.id_kardex_corte IS NULL)");
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT DATE(kardex.fecha_movimiento) FROM iv_kardex kardex
	WHERE kardex.id_articulo = art_emp.id_articulo
		AND (SELECT almacen.id_empresa
			FROM iv_calles calle
				INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
				INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
				INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
				INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
			WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
	ORDER BY CONCAT_WS(' ', kardex.fecha_movimiento, kardex.hora_movimiento) DESC
	LIMIT 1) = DATE(NOW())");
	
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$query = sprintf("SELECT * FROM iv_articulos_empresa art_emp %s", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_array($rs)) {
		$idTipoCorte = ($row['id_kardex_corte'] > 0) ? 1 : 2; // 1 = Saldo en Cero, 2 = Unica Compra
		
		// ULTIMO MOVIMIENTO PARA CUANDO SE PUSO EN CERO
		$queryKardex = sprintf("SELECT * FROM iv_kardex kardex
		WHERE kardex.id_articulo = %s
			AND (SELECT almacen.id_empresa
				FROM iv_calles calle
					INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
					INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
					INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
					INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
				WHERE casilla.id_casilla = kardex.id_casilla) = %s
		ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) DESC, kardex.id_kardex DESC
		LIMIT 1;",
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['id_empresa'], "int"));
		$rsKardex = mysql_query($queryKardex);
		if (!$rsKardex) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$totalRowsKardex = mysql_num_rows($rsKardex);
		$rowKardex = mysql_fetch_array($rsKardex);
		
		$fechaKardexCorte = $rowKardex['fecha_movimiento'];
		
		$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
			id_kardex_corte = %s,
			fecha_kardex_corte = %s,
			id_tipo_corte = %s
		WHERE id_articulo_empresa = %s;",
			valTpDato($rowKardex['id_kardex'], "int"),
			valTpDato($fechaKardexCorte, "date"),
			valTpDato($idTipoCorte, "int"),
			valTpDato($row['id_articulo_empresa'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
	}
	
	
	$sqlBusq = "";
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
		cantidad_compra = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (1)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (SELECT almacen.id_empresa
								FROM iv_calles calle
									INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
									INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
									INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
									INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
								WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		valor_compra = (SELECT SUM(kardex.cantidad * (kardex.costo + IFNULL(kardex.costo_cargo, 0) - IFNULL(kardex.subtotal_descuento, 0)))
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (1)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (SELECT almacen.id_empresa
								FROM iv_calles calle
									INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
									INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
									INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
									INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
								WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_entrada = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (2)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (SELECT almacen.id_empresa
								FROM iv_calles calle
									INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
									INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
									INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
									INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
								WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		valor_entrada = (SELECT
							(CASE tipo_documento_movimiento
								WHEN 1 THEN
									SUM(kardex.cantidad * (kardex.costo + IFNULL(kardex.costo_cargo, 0) - IFNULL(kardex.subtotal_descuento, 0)))
								WHEN 2 THEN
									SUM(kardex.cantidad * kardex.costo)
							END)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (2)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (SELECT almacen.id_empresa
								FROM iv_calles calle
									INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
									INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
									INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
									INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
								WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_venta = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (3)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (SELECT almacen.id_empresa
								FROM iv_calles calle
									INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
									INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
									INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
									INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
								WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		valor_venta = (SELECT SUM(kardex.cantidad * kardex.costo)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (3)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (SELECT almacen.id_empresa
								FROM iv_calles calle
									INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
									INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
									INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
									INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
								WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_salida = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (4)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (SELECT almacen.id_empresa
								FROM iv_calles calle
									INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
									INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
									INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
									INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
								WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		valor_salida = (SELECT
							(CASE tipo_documento_movimiento
								WHEN 1 THEN
									SUM(kardex.cantidad * kardex.costo)
								WHEN 2 THEN
									SUM(kardex.cantidad * (kardex.costo + IFNULL(kardex.costo_cargo, 0) - IFNULL(kardex.subtotal_descuento, 0)))
							END)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (4)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (SELECT almacen.id_empresa
								FROM iv_calles calle
									INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
									INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
									INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
									INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
								WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))) %s;", $sqlBusq);
	/*$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
		cantidad_compra = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (1)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		valor_compra = (SELECT SUM(mov_det.cantidad * (mov_det.costo - mov_det.subtotal_descuento))
						FROM iv_movimiento_detalle mov_det
							INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
						WHERE kardex.tipo_movimiento IN (1)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_entrada = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (2)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		valor_entrada = (SELECT SUM(mov_det.cantidad * (mov_det.costo - mov_det.subtotal_descuento))
						FROM iv_movimiento_detalle mov_det
							INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
						WHERE kardex.tipo_movimiento IN (2)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_venta = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (3)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		valor_venta = (SELECT SUM(mov_det.cantidad * mov_det.costo)
						FROM iv_movimiento_detalle mov_det
							INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
						WHERE kardex.tipo_movimiento IN (3)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_salida = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (4)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		valor_salida = (SELECT SUM(mov_det.cantidad * mov_det.costo)
						FROM iv_movimiento_detalle mov_det
							INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
						WHERE kardex.tipo_movimiento IN (4)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))) %s;", $sqlBusq);*/
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
		
	return array(true, "");
}

function actualizarOrdenServicio($idOrden) {
	
	// RECALCULA LOS MONTOS DE LA ORDEN
	$sqlDetOrden = sprintf("SELECT SUM(precio_unitario * cantidad) AS valor FROM sa_det_orden_articulo
	WHERE id_orden = %s
			AND aprobado = 1
			AND estado_articulo <> 'DEVUELTO'
			AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva 
					WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo
				) > 0;",
			$idOrden);
	$rsDetOrden = mysql_query($sqlDetOrden);
	if (!$rsDetOrden) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowDetOrden = mysql_fetch_array($rsDetOrden);
	$valorBaseimponibleRep = $rowDetOrden['valor'];

	$sqlDetRepuestosIvas = sprintf("SELECT SUM(precio_unitario * cantidad) AS base_imponible, sa_det_orden_articulo_iva.id_iva
	FROM sa_det_orden_articulo
	INNER JOIN sa_det_orden_articulo_iva ON sa_det_orden_articulo.id_det_orden_articulo = sa_det_orden_articulo_iva.id_det_orden_articulo
	WHERE id_orden = %s
			AND aprobado = 1
			AND estado_articulo <> 'DEVUELTO'
			GROUP BY sa_det_orden_articulo_iva.id_iva",
			$idOrden);
	$rsDetRepuestosIvas = mysql_query($sqlDetRepuestosIvas);
	if (!$rsDetRepuestosIvas) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));

	while($rowDetRepuestosIvas = mysql_fetch_assoc($rsDetRepuestosIvas)){
		$arrayIvasArticulos[$rowDetRepuestosIvas["id_iva"]] = $rowDetRepuestosIvas["base_imponible"];
	}


	$sqlDetOrdenExento = sprintf("SELECT SUM(precio_unitario * cantidad) AS valorExento FROM sa_det_orden_articulo
	WHERE id_orden = %s
			AND aprobado = 1
			AND estado_articulo <> 'DEVUELTO'
			AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva 
					WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo
				) = 0;",
			$idOrden);
	$rsDetOrdenExento = mysql_query($sqlDetOrdenExento);
	if (!$rsDetOrdenExento) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowDetOrdenExento = mysql_fetch_array($rsDetOrdenExento);
	$valorExentoRep = $rowDetOrdenExento['valorExento'];


	$sqlTemp = sprintf("SELECT
	SUM((CASE id_modo
			WHEN 1 THEN
					(precio_tempario_tipo_orden * ut) / base_ut_precio
			WHEN 2 THEN
					precio
	END)) AS valorTemp
	FROM sa_det_orden_tempario
	WHERE id_orden = %s AND estado_tempario <> 'DEVUELTO';",
			$idOrden);
	$rsTemp = mysql_query($sqlTemp);
	if (!$rsTemp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowTemp = mysql_fetch_array($rsTemp);
	$valorTemp = $rowTemp['valorTemp'];

	$sqlTOT = sprintf("SELECT
			orden.id_orden,
			orden.id_tipo_orden,
			SUM(orden_tot.monto_subtotal) AS monto_subtotalTot
	FROM sa_orden_tot orden_tot
			INNER JOIN sa_orden orden ON (orden_tot.id_orden_servicio = orden.id_orden)
	WHERE orden.id_orden = %s
	GROUP BY orden.id_orden, orden.id_tipo_orden;",
			$idOrden);
	$rsTOT = mysql_query($sqlTOT);
	if (!$rsTOT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowTOT = mysql_fetch_array($rsTOT);

	$queryPorcentajeTot = sprintf("SELECT *
	FROM sa_det_orden_tot
	WHERE id_orden = %s;",
			valTpDato($idOrden, "int"));
	$rsPorcentajeTot = mysql_query($queryPorcentajeTot);
	if (!$rsPorcentajeTot) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowPorcentajeTot = mysql_fetch_assoc($rsPorcentajeTot);

	$valorDetTOT = $rowTOT['monto_subtotalTot'] + (($rowPorcentajeTot['porcentaje_tot'] * $rowTOT['monto_subtotalTot']) / 100);

	$sqlNota = sprintf("SELECT SUM(precio) AS precio FROM sa_det_orden_notas
	WHERE id_orden = %s 
	AND aprobado = 1;",
			$idOrden);
	$rsNota = mysql_query($sqlNota);
	if (!$rsNota) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowNota = mysql_fetch_array($rsNota);

	$sqlDesc = sprintf("SELECT porcentaje_descuento FROM sa_orden
	WHERE id_orden = %s;",
			$idOrden);
	$rsDesc = mysql_query($sqlDesc);
	if (!$rsDesc) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowDesc = mysql_fetch_array($rsDesc);

	$sqlIvas = sprintf("SELECT base_imponible, subtotal_iva, id_iva, iva
	FROM sa_orden_iva
	WHERE id_orden = %s;",
			$idOrden);
	$rsIvas = mysql_query($sqlIvas);
	if (!$rsIvas) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));

	$tieneIva = mysql_num_rows($rsIvas);

	$totalExento = 0;//total exento de la orden
	$totalBaseImponibleItems = 0;//total base imponible de items que no sean repuestos

	if($tieneIva){//si tiene iva, separar exentos de repuestos
		$totalExento += $valorExentoRep;
		$totalBaseImponibleItems += $rowNota['precio'] + $valorDetTOT + $valorTemp;
	}else{//si no, todo se va al exento
		$totalExento += $rowNota['precio'] + $valorDetTOT + $valorTemp + $valorExentoRep;
	}

	while($rowIvas = mysql_fetch_assoc($rsIvas)){//busco ivas y porcentajes de la orden
		$arrayIvasOrden[$rowIvas["id_iva"]] = $rowIvas["iva"];
	}

	$totalIva = 0;
	foreach($arrayIvasOrden as $idIva => $porcIva){//recorro ivas de la orden e ivas de los repuestos
		$baseIva = $totalBaseImponibleItems + $arrayIvasArticulos[$idIva];//sumo base items + base articulos que aplican            
		$baseIvaDesc = round($baseIva - ($baseIva*($rowDesc['porcentaje_descuento']/100)),2);            
		$ivaSubTotal = round($baseIvaDesc*($porcIva/100),2);            
		$totalIva += $ivaSubTotal;

		$sqlUpdateIva = sprintf("UPDATE sa_orden_iva SET base_imponible = %s, subtotal_iva = %s
								WHERE id_orden = %s 
								AND id_iva = %s;",
						valTpDato($baseIvaDesc, "double"),
						valTpDato($ivaSubTotal, "double"),
						$idOrden,
						$idIva);
		$rsUpdateIva = mysql_query($sqlUpdateIva);
		if (!$rsUpdateIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));            
	}

	$subtotal = $totalBaseImponibleItems + $totalExento + $valorBaseimponibleRep;

	$Desc = round($subtotal * ($rowDesc['porcentaje_descuento']/100),2);
	$totalExento = round($totalExento - ($totalExento*($rowDesc['porcentaje_descuento']/100)),2);
	$totalOrden = round($subtotal - $Desc + $totalIva,2);
	$updateSQL = "UPDATE sa_orden SET
			subtotal = ".valTpDato($subtotal, "double").",
			monto_exento = ".valTpDato($totalExento, "double").",
			subtotal_iva = ".valTpDato($totalIva, "double").",
			subtotal_descuento = ".valTpDato($Desc, "double").",
			total_orden = ".valTpDato($totalOrden, "double")."
	WHERE id_orden = ".$idOrden;
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		
	//return array(false, "Prueba de totales\nSubtotal".$subtotal."\nTotalExento".$totalExento."\ntotalIva:".$totalIva."\nSubtotal Desc.".round($Desc,2)."\n\nTOTAL ORDEN:".($totalOrden));
		
	return array(true, "");
}

function actualizarCostoPromedio($idArticulo = "", $idEmpresa = "") {
	global $conex;
	
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	// BUSCA QUE EMPRESAS TIENE DICHO ARTICULO
	$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa %s;", $sqlBusq);
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	while ($rowArtEmp = mysql_fetch_assoc($rsArtEmp)) {
		$idEmpresa = $rowArtEmp['id_empresa'];
		$idArticulo = $rowArtEmp['id_articulo'];
		
		// BUSCA EL ULTIMO COSTO DE LA PIEZA Y LA FECHA DE LA ULTIMA COMPRA
		$queryArticuloCosto = sprintf("SELECT art_costo.*,
			(SELECT DATE(kardex.fecha_movimiento) FROM iv_kardex kardex
			WHERE kardex.tipo_movimiento IN (1)
				AND kardex.id_articulo = art_costo.id_articulo
			LIMIT 1) AS fecha_movimiento
		FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo = %s
			AND art_costo.id_empresa = %s
		ORDER BY art_costo.id_articulo_costo DESC
		LIMIT 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsArticuloCosto = mysql_query($queryArticuloCosto);
		if (!$rsArticuloCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowArticuloCosto = mysql_fetch_assoc($rsArticuloCosto);
		
		$idArticuloCosto = $rowArticuloCosto['id_articulo_costo'];
		
		// BUSCA EL COSTO DE LA ULTIMA VENTA O SALIDA
		$queryMov = sprintf("SELECT
			kardex.id_kardex,
			kardex.fecha_movimiento,
			mov_det.costo
		FROM iv_movimiento_detalle mov_det
			INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
		WHERE kardex.id_articulo = %s
			AND (SELECT almacen.id_empresa
				FROM iv_calles calle
					INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
					INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
					INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
					INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
				WHERE casilla.id_casilla = kardex.id_casilla) = %s
			AND kardex.tipo_movimiento IN (3,4)
			AND kardex.fecha_movimiento >= %s
		ORDER BY kardex.fecha_movimiento DESC;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($rowArtEmp['fecha_kardex_corte'], "date"));
		$rsMov = mysql_query($queryMov);
		if (!$rsMov) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowMov = mysql_fetch_assoc($rsMov);
		
		$cantidad = $rowArtEmp['cantidad_compra'] + $rowArtEmp['cantidad_entrada'] - $rowArtEmp['cantidad_venta'] - $rowArtEmp['cantidad_salida'];
		
		if ($cantidad > 0) {
			$valor = $rowArtEmp['valor_compra'] + $rowArtEmp['valor_entrada'] - $rowArtEmp['valor_venta'] - $rowArtEmp['valor_salida'];
			
			$costoPromedio = round($valor,2) / round($cantidad,2);
		} else {
			$costoPromedio = $rowMov['costo'];
		}
		
		$costoPromedio = ($cantidad > 0 && $costoPromedio > 0) ? $costoPromedio : $rowMov['costo'];
		
		// ACTUALIZA EL COSTO PROMEDIO
		$updateSQL = sprintf("UPDATE iv_articulos_costos SET
			costo_promedio = %s
		WHERE id_articulo_costo = %s;",
			valTpDato($costoPromedio, "real_inglesa"),
			valTpDato($idArticuloCosto, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
	}
	
	return array(true, "");
}

function actualizarPrecioVenta($idArticulo = "", $idEmpresa = "", $idPrecio = "", $ejecutarAumento = false) {
	global $conex;
	
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	// ACTUALIZA EL PRECIO DE LOS ARTICULOS DE LA EMPRESA
	$queryArticuloEmpresa = sprintf("SELECT * FROM iv_articulos_empresa art_emp %s;", $sqlBusq);
	$rsArticuloEmpresa = mysql_query($queryArticuloEmpresa);
	if (!$rsArticuloEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	while ($rowArticuloEmpresa = mysql_fetch_assoc($rsArticuloEmpresa)) {
		$idArticulo = $rowArticuloEmpresa['id_articulo'];
		$idEmpresa = $rowArticuloEmpresa['id_empresa'];
		
		// BUSCA EL ULTIMO COSTO DEL ARTICULO
		$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos
		WHERE id_articulo = %s
			AND id_empresa = %s
		ORDER BY id_articulo_costo
		DESC LIMIT 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsCostoArt = mysql_query($queryCostoArt);
		if (!$rsCostoArt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
		
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("estatus = 1");
							
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("emp_precio.actualizar_con_costo = 1");
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("precio.id_precio NOT IN (6,7)");
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("emp_precio.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		if ($idPrecio != "-1" && $idPrecio != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("precio.id_precio = %s",
				valTpDato($idPrecio, "int"));
		}
		
		$queryPrecios = sprintf("SELECT *
		FROM pg_empresa_precios emp_precio
			INNER JOIN pg_precios precio ON (emp_precio.id_precio = precio.id_precio) %s;", $sqlBusq2);
		$rsPrecios = mysql_query($queryPrecios);
		if (!$rsPrecios) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		while ($rowPrecio = mysql_fetch_assoc($rsPrecios)) {
			$idPrecio = $rowPrecio['id_precio'];
			
			switch ($rowPrecio['tipo_costo']) {
				case 1 : $costoUnitario = $rowCostoArt['costo']; break;
				case 2 : $costoUnitario = $rowCostoArt['costo_promedio']; break;
			}
			
			switch ($ejecutarAumento) {
				case false : $porcMarkUp = $rowPrecio['porcentaje']; break;
				case true : $porcMarkUp = $rowPrecio['porcentaje_aumento']; break;
			}
				
			if ($costoUnitario > 0) {
				$queryArtPrecio = sprintf("SELECT * FROM iv_articulos_precios
				WHERE id_articulo = %s
					AND id_empresa = %s
					AND id_precio = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($idPrecio, "int"));
				$rsArtPrecio = mysql_query($queryArtPrecio);
				if (!$rsArtPrecio) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
				
				if ($rowPrecio['tipo'] == 0) { // PRECIO SOBRE COSTO
					$montoGanancia = (doubleval($costoUnitario) * doubleval($porcMarkUp / 100)) + doubleval($costoUnitario);
				} else if ($rowPrecio['tipo'] == 1) { // PRECIO SOBRE VENTA
					$montoGanancia = (doubleval($costoUnitario) * 100) / (100 - doubleval($porcMarkUp));
				}
				
				if ($rowArtPrecio['id_articulo_precio'] == "") {
					$insertSQL = sprintf("INSERT INTO iv_articulos_precios (id_empresa, id_articulo, id_precio, precio)
					VALUE (%s, %s, %s, %s);",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idPrecio, "int"),
						valTpDato($montoGanancia, "double"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				} else {
					$updateSQL = sprintf("UPDATE iv_articulos_precios SET
						precio = %s
					WHERE id_articulo_precio = %s;",
						valTpDato($montoGanancia, "double"),
						valTpDato($rowArtPrecio['id_articulo_precio'], "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				}
			}
		}
	}
		
	return array(true, "");
}

function valorConfiguracion($idConfiguracion, $idEmpresa) {
	global $conex;
	
	$idEmpresa = preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/"," ",utf8_encode(str_replace("\"","",$idEmpresa))));
	$idEmpresa = (strlen($idEmpresa) > 0) ? $idEmpresa : "-1";
	
	// VERIFICA VALORES DE CONFIGURACION (Manejar Costo de Repuesto)
	$queryConfig = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = %s AND config_emp.status = 1 AND config_emp.id_empresa IN (%s);",
		valTpDato($idConfiguracion, "int"),
		valTpDato($idEmpresa, "campo"));
	$rsConfig = mysql_query($queryConfig);
	if (!$rsConfig) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsConfig = mysql_num_rows($rsConfig);
	$rowConfig = mysql_fetch_assoc($rsConfig);
	
	return array(true, $rowConfig['valor']);
}

/*
* fi: fecha de partida
* intervalo_dias: cantidad de dias a sumar
* sabado_no_lab: incluye al sábado como dia no laborable
*/

include($raiz."clases/adodb-time.inc.php");
if (!function_exists("dateAddLab")) {
	function dateAddLab($fi, $intervalo_dias, $sabado_no_lab = false) {
		$total_dias_nolab = 1;
		
		if ($sabado_no_lab) {
			$total_dias_nolab = 2;
		}
		
		$di = adodb_date('j',$fi);
		$mi = adodb_date('n',$fi);
		$yi = adodb_date('Y',$fi);
		$fecha_inicial = adodb_mktime(0,0,0,$mi,$di,$yi);		
		$fecha_final = $fecha_inicial + ((60*60*24) * $intervalo_dias);		
		$fechai = $fecha_inicial;
		$nolab = 0;
		for ($i = 1; $i <= $intervalo_dias; $i++) {
			$fechai = $fechai + ((60*60*24)); // 1 dia
			$dow = adodb_date('w',$fechai);
			if(($dow == 6 && $total_dias_nolab == 2) || ($dow == 0)){ // domingo
				if($dow == 6){
					$fechai = $fechai + ((60*60*24)*2); // 2 dia
				}else{
					$fechai = $fechai + ((60*60*24)); // 1 dia				
				}
			}
		}
		$fecha_final = $fechai;		
		if(adodb_date('w',$fecha_final) == 6 && $total_dias_nolab == 2){ // domingo
			$fecha_final = $fecha_final + ((60*60*24));
		}
		if(adodb_date('w',$fecha_final) == 0){ // domingo
			$fecha_final = $fecha_final + ((60*60*24));
		}
		
		return $fecha_final;
	}
}
?>