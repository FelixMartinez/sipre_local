<?php


function anularPedido($idPedido, $frmListaPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// ANULA EL PEDIDO
	$updateSQL = sprintf("UPDATE an_pedido SET
		estado_pedido = 5
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	// ANULA LOS ACCESORIOS DEL PEDIDO
	$updateSQL = sprintf("UPDATE an_accesorio_pedido SET
		estatus_accesorio_pedido = 2
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 0 = Pendiente, 1 = Facturado, 2 = Anulado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	// ANULA LOS ACCESORIOS DEL PAQUETE DEL PEDIDO
	$updateSQL = sprintf("UPDATE an_paquete_pedido SET
		estatus_paquete_pedido = 2
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 0 = Pendiente, 1 = Facturado, 2 = Anulado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	// ACTUALIZA EL ESTATUS DE LA UNIDAD FISICA AGREGADA AL PEDIDO DE VENTA
	$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis, an_pedido ped_vent SET
		estado_venta = (CASE uni_fis.estado_compra
							WHEN 'COMPRADO' THEN 'POR REGISTRAR'
							WHEN 'REGISTRADO' THEN 'DISPONIBLE'
						END)
	WHERE uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica
		AND uni_fis.estado_venta LIKE 'RESERVADO'
		AND ped_vent.id_pedido = %s;",
		valTpDato($idPedido, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPedido(
		$frmListaPedido['pageNum'],
		$frmListaPedido['campOrd'],
		$frmListaPedido['tpOrd'],
		$frmListaPedido['valBusq']));

	return $objResponse;
}

function autorizarPedido($idPedido, $frmListaPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","desautorizar")) { return $objResponse; }
	
	// BUSCA LOS DATOS DEL PEDIDO
	$queryPedidoVenta = sprintf("SELECT * FROM an_pedido
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int"));
	$rsPedidoVenta = mysql_query($queryPedidoVenta);
	if (!$rsPedidoVenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRowsPedidoVenta = mysql_num_rows($rsPedidoVenta);
	$rowPedidoVenta = mysql_fetch_assoc($rsPedidoVenta);
	
	$idEmpresa = $rowPedidoVenta['id_empresa'];
	$idUnidadFisica = $rowPedidoVenta['id_unidad_fisica'];
	
	// VERIFICA QUE LA UNIDAD ESTE REGISTRADA
	$queryUnidadFisica = sprintf("SELECT * FROM an_unidad_fisica uni_fis
	WHERE uni_fis.id_unidad_fisica = %s
		AND uni_fis.estado_compra IN ('COMPRADO');",
		valTpDato($idUnidadFisica, "int"));
	$rsUnidadFisica = mysql_query($queryUnidadFisica);
	if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRowsUnidadFisica = mysql_num_rows($rsUnidadFisica);
	
	// VERIFICA QUE LA UNIDAD PERTENEZCA A UN ALMACEN DE LA EMPRESA
	$queryAlmacen = sprintf("SELECT *
	FROM an_almacen alm
		INNER JOIN an_unidad_fisica uni_fis ON (alm.id_almacen = uni_fis.id_almacen)
	WHERE uni_fis.id_unidad_fisica = %s
		AND alm.id_empresa <> %s;",
		valTpDato($idUnidadFisica, "int"),
		valTpDato($idEmpresa, "int"));
	$rsAlmacen = mysql_query($queryAlmacen);
	if (!$rsAlmacen) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRowsAlmacen = mysql_num_rows($rsAlmacen);
	
	if ($totalRowsUnidadFisica == 0 && $totalRowsAlmacen == 0) {
		mysql_query("START TRANSACTION;");
		
		$updateSQL = sprintf("UPDATE an_pedido SET
			estado_pedido = 1
		WHERE id_pedido = %s;",
			valTpDato($idPedido, "int")); // 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	}
	
	if ($totalRowsUnidadFisica > 0) {
		$objResponse->alert("El pedido no puede ser aprobado debido a que el registro de compra de la unidad no ha finalizado totalmente.");
	}
	
	if ($totalRowsAlmacen > 0) {
		$objResponse->alert("El pedido no puede ser aprobado debido a que la unidad esta registrada en un almacen de otra empresa.");
	}
	
	$objResponse->loadCommands(listaPedido(
		$frmListaPedido['pageNum'],
		$frmListaPedido['campOrd'],
		$frmListaPedido['tpOrd'],
		$frmListaPedido['valBusq']));
	
	return $objResponse;
}

function buscarPedido($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatusPedido'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPedido(0, "id_pedido", "DESC", $valBusq));
	
	return $objResponse;
}

function desautorizarPedido($idPedido, $frmListaPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","desautorizar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE an_pedido SET
		estado_pedido = 3
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPedido(
		$frmListaPedido['pageNum'],
		$frmListaPedido['campOrd'],
		$frmListaPedido['tpOrd'],
		$frmListaPedido['valBusq']));
	
	return $objResponse;
}

function listaPedido($pageNum = 0, $campOrd = "id_pedido", $tpOrd = "DESC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estado_pedido IN (1,2,3,4)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(
		(SELECT COUNT(acc_ped.id_pedido) FROM an_accesorio_pedido acc_ped
		WHERE acc_ped.id_pedido = ped_vent.id_pedido
			AND acc_ped.estatus_accesorio_pedido = 0) > 0
		OR (SELECT COUNT(paq_ped.id_pedido) FROM an_paquete_pedido paq_ped
			WHERE paq_ped.id_pedido = ped_vent.id_pedido AND paq_ped.estatus_paquete_pedido = 0) > 0
		OR (SELECT COUNT(uni_fis.id_unidad_fisica) FROM an_unidad_fisica uni_fis
			WHERE uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica
				AND uni_fis.estado_venta = 'RESERVADO') > 0)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ped_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = ped_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_vent.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[3] == "00") {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 0)");
		} else if ($valCadBusq[3] == "22") {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 2)");
		} else if ($valCadBusq[3] == "33") {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 3)");
		} else {
			$sqlBusq .= $cond.sprintf("(ped_vent.estado_pedido = %s)",
				valTpDato($valCadBusq[3], "int"));
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ped_vent.id_pedido LIKE %s
		OR ped_vent.id_presupuesto LIKE %s
		OR ped_vent.id_cliente LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		ped_vent.id_pedido,
		ped_vent.numeracion_pedido,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		cxc_fact.idFactura AS id_factura_reemplazo,
		cxc_fact.numeroFactura AS numero_factura_reemplazo,
		ped_vent.fecha,
		pres_vent_acc.id_presupuesto_accesorio,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT(uni_bas.nom_uni_bas, ': ', marca.nom_marca, ' ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
		uni_fis.serial_carroceria,
		uni_fis.placa,
		
		(ped_vent.precio_venta
			+ IFNULL(ped_vent.precio_venta * (ped_vent.porcentaje_iva + ped_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta,
		
		ped_vent.porcentaje_inicial,
		ped_vent.inicial AS monto_inicial,
		ped_vent.total_inicial_gastos AS total_general,
		
		(SELECT an_factura_venta.tipo_factura FROM an_factura_venta
		WHERE an_factura_venta.numeroPedido = ped_vent.id_pedido
			AND (SELECT COUNT(an_factura_venta.numeroPedido) FROM an_factura_venta
			WHERE an_factura_venta.numeroPedido = ped_vent.id_pedido
				AND tipo_factura IN (1,2)) = 1) AS tipo_factura,
		
		pres_vent.estado AS estado_presupuesto,
		ped_vent.estado_pedido,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_pedido ped_vent
		INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_presupuesto pres_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
		LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
		LEFT JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		LEFT JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		LEFT JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (ped_vent.id_factura_cxc = cxc_fact.idFactura) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedido", "", $pageNum, "estado_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "numeracion_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "numeracion_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "id_presupuesto_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto Accesorios");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "12%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "20%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo / ".$spanSerialCarroceria." / Placa");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "12%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Venta / Inicial");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "8%", $pageNum, "total_general", $campOrd, $tpOrd, $valBusq, $maxRows, "Total General");
		$htmlTh .= "<td colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$imgEstatusPedido = "";
		if ($row['estado_presupuesto'] == 0 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Autorizado\"/>";
		} else if ($row['estado_presupuesto'] == 1 || $row['estado_presupuesto'] == "") {
			switch ($row['estado_pedido']) {
				case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			}
		} else if ($row['estado_presupuesto'] == 2 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto Anulado\"/>";
		} else if ($row['estado_presupuesto'] == 3 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Presupuesto Desautorizado\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeracion_pedido'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeracion_presupuesto'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_presupuesto_accesorio']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td>";
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
					$row['id_factura_reemplazo']);
				$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Factura Venta PDF")."\"/></a>", $row['id_factura_reemplazo']);
				
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td colspan=\"2\">".utf8_encode($row['vehiculo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr class=\"textoNegrita_10px\">";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['serial_carroceria'])."</td>";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['placa'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= ($row['numero_factura_reemplazo'] > 0) ? 
					"<tr><td colspan=\"2\">".
						"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo4\" width=\"100%\">".
						"<tr align=\"center\">".
							"<td height=\"25\" width=\"25\"><img src=\"../img/iconos/exclamation.png\"/></td>".
							"<td>".
								"<table>".
								"<tr align=\"right\">".
									"<td nowrap=\"nowrap\">"."Edición de la Factura Nro. "."</td>".
									"<td nowrap=\"nowrap\">".$aVerDcto."</td>".
									"<td>".$row['numero_factura_reemplazo']."</td>".
								"</tr>".
								"</table>".
							"</td>".
						"</tr>".
						"</table>".
					"</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['porcentaje_inicial'] == 100) ? "divMsjInfo" : "divMsjAlerta")."\">";
				$htmlTb .= ($row['porcentaje_inicial'] == 100) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td colspan=\"2\">".number_format($row['precio_venta'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td class=\"textoNegrita_10px\" width=\"50%\">"."(".number_format($row['porcentaje_inicial'], 2, ".", ",")."%)"."</td>";
					$htmlTb .= "<td width=\"50%\">".number_format($row['monto_inicial'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_general'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('an_ventas_pedido_editar.php?id=%s','_self');\" src=\"../img/iconos/pencil.png\" title=\"Editar Pedido\"/>",
					$row['id_pedido']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado_pedido'] == 1) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarDesautorizar('%s');\" src=\"../img/iconos/cancel.png\" title=\"Desautorizar Pedido\"/>",
					$row['id_pedido']);
			} else if ($row['estado_pedido'] == 3) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAutorizar('%s');\" src=\"../img/iconos/accept.png\" title=\"Autorizar Pedido\"/>",
					$row['id_pedido']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado_pedido'] == 3) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAnular('%s');\" src=\"../img/iconos/ico_delete.png\" title=\"Anular Pedido\"/>",
					$row['id_pedido']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('an_ventas_pedido_editar.php?view=print&id=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir Pedido\"/>",
					$row['id_pedido']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['id_presupuesto_accesorio'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Presupuesto Accesorio PDF\"/>",
					$row['id_presupuesto_accesorio']);
			}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedido(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaPedido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularPedido");
$xajax->register(XAJAX_FUNCTION,"autorizarPedido");
$xajax->register(XAJAX_FUNCTION,"buscarPedido");
$xajax->register(XAJAX_FUNCTION,"desautorizarPedido");
$xajax->register(XAJAX_FUNCTION,"listaPedido");
?>