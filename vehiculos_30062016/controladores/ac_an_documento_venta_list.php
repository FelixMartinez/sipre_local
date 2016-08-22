<?php


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

function formPedido($idPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarPedido').click();"); return $objResponse; }
	
	$query = sprintf("SELECT * FROM an_pedido
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtIdPedido","value",$idPedido);
	$objResponse->assign("txtFechaEntrega","value",date("d-m-Y",strtotime($row['fecha_entrega'])));
	
	return $objResponse;
}

function guardarPedido($frmPedido, $frmListaPedido) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmPedido['txtIdPedido'] > 0) {
		if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_pedido SET
			fecha_entrega = %s
		WHERE id_pedido = %s;",
			valTpDato(date("Y-m-d",strtotime($frmPedido['txtFechaEntrega'])), "date"),
			valTpDato($frmPedido['txtIdPedido'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Pedido guardado con éxito.");
	
	$objResponse->script("byId('btnCancelarPedido').click();");
	
	$objResponse->loadCommands(listaPedido(
		$frmListaPedido['pageNum'],
		$frmListaPedido['campOrd'],
		$frmListaPedido['tpOrd'],
		$frmListaPedido['valBusq']));
	
	return $objResponse;
}

function listaPedido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
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
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 1 AND estado_pedido = %s)",
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
		ped_vent.fecha,
		CONCAT_WS(' ',cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT(uni_bas.nom_uni_bas, ': ', marca.nom_marca, ' ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
		uni_fis.serial_carroceria,
		uni_fis.placa,
		
		(ped_vent.precio_venta
			+ IFNULL(ped_vent.precio_venta * (ped_vent.porcentaje_iva + ped_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta,
		
		ped_vent.porcentaje_inicial,
		ped_vent.inicial AS monto_inicial,
		ped_vent.total_inicial_gastos AS total_general,
		
		(SELECT COUNT(fac_vent_det_vehic.id_factura_detalle_vehiculo)
		FROM cj_cc_factura_detalle_vehiculo fac_vent_det_vehic
			INNER JOIN cj_cc_encabezadofactura fact_vent ON (fac_vent_det_vehic.id_factura = fact_vent.idFactura)
		WHERE fact_vent.numeroPedido = ped_vent.id_pedido
			AND anulada <> 'SI'
			AND fact_vent.idDepartamentoOrigenFactura = 2) AS cant_vehic,
		
		pres_vent.estado AS estado_presupuesto,
		ped_vent.estado_pedido,
		CONCAT_WS(' ',empleado.nombre_empleado,empleado.apellido) AS asesor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_pedido ped_vent
		INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica)
		LEFT JOIN an_presupuesto pres_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
		LEFT JOIN an_uni_bas uni_bas ON (uni_bas.id_uni_bas = uni_fis.id_uni_bas)
		LEFT JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
		LEFT JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
		LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN pg_empleado empleado ON (ped_vent.asesor_ventas = empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedido", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "numeracion_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "numeracion_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "16%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "32%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo / ".$spanSerialCarroceria." / Placa");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "14%", $pageNum, "asesor", $campOrd, $tpOrd, $valBusq, $maxRows, "Asesor");
		$htmlTh .= "<td colspan=\"7\"></td>";
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
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Factura\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			}
		} else if ($row['estado_presupuesto'] == 2 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto Anulado\"/>";
		} else if ($row['estado_presupuesto'] == 3 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Presupuesto Desautorizado\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeracion_pedido'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeracion_presupuesto'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td colspan=\"2\">".utf8_encode($row['vehiculo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr class=\"textoNegrita_10px\">";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['serial_carroceria'])."</td>";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['placa'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['porcentaje_inicial'] == 100) ? "divMsjInfo" : "divMsjAlerta")."\">";
				$htmlTb .= ($row['porcentaje_inicial'] == 100) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['asesor'])."</td>";
			
			$htmlTb .= "<td align=\"center\">";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPedido', '%s');\"><img class=\"puntero\" src=\"../img/iconos/accept.png\" title=\"Vehículo Entregado\"/></a>",
					$contFila,
					$row['id_pedido']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_ventas_cartas_checklist.php?id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/pencil.png\" title=\"Editar Inspección de Pre-Entrega\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_ventas_cartas_checklist.php?view=print&id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/aprobar_presup.png\" title=\"Inspección de Pre-Entrega\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_ventas_cartas_bienvenida.php?view=print&id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/page.png\" title=\"Carta de Bienvenida\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_ventas_cartas_agradecimiento.php?view=print&id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/page_green.png\" title=\"Carta de Agradecimiento\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_carta_certificado_venta_pdf.php?view=print&id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/page_red.png\" title=\"Certificado de Orígen\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('an_ventas_pedido_editar.php?view=print&id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Orden de Pedido\"/>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
		$htmlTb .= "<td colspan=\"16\">";
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

$xajax->register(XAJAX_FUNCTION,"buscarPedido");
$xajax->register(XAJAX_FUNCTION,"formPedido");
$xajax->register(XAJAX_FUNCTION,"guardarPedido");
$xajax->register(XAJAX_FUNCTION,"listaPedido");
?>