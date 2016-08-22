<?php


function anularPresupuesto($idPresupuesto, $frmListaPresupuesto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_presupuesto_venta_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// ANULA EL PRESUPUESTO
	$updateSQL = sprintf("UPDATE an_presupuesto SET
		estado = 2
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPresupuesto(
		$frmListaPresupuesto['pageNum'],
		$frmListaPresupuesto['campOrd'],
		$frmListaPresupuesto['tpOrd'],
		$frmListaPresupuesto['valBusq']));
	
	return $objResponse;
}

function autorizarPresupuesto($idPresupuesto, $frmListaPresupuesto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_presupuesto_venta_list","desautorizar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE an_presupuesto SET
		estado = 0
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPresupuesto(
		$frmListaPresupuesto['pageNum'],
		$frmListaPresupuesto['campOrd'],
		$frmListaPresupuesto['tpOrd'],
		$frmListaPresupuesto['valBusq']));
	
	return $objResponse;
}

function buscarPresupuesto($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatusPedido'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPresupuesto(0, "id_presupuesto", "DESC", $valBusq));
	
	return $objResponse;
}

function desautorizarPresupuesto($idPresupuesto, $frmListaPresupuesto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_presupuesto_venta_list","desautorizar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE an_presupuesto SET
		estado = 3
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPresupuesto(
		$frmListaPresupuesto['pageNum'],
		$frmListaPresupuesto['campOrd'],
		$frmListaPresupuesto['tpOrd'],
		$frmListaPresupuesto['valBusq']));
	
	return $objResponse;
}

function generarPresupuesto($idPresupuesto) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_presupuesto
		INNER JOIN cj_cc_cliente ON an_presupuesto.id_cliente = cj_cc_cliente.id
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['tipo_cuenta_cliente'] == 2 || $row['tipo_cuenta_cliente'] == "") {
		$objResponse->script("window.open('an_ventas_pedido_generar.php?idPresupuesto=".$idPresupuesto."','_self');");
	} else if ($row['tipo_cuenta_cliente'] == 1) {
		$updateSQL = sprintf("UPDATE cj_cc_cliente SET
			tipo_cuenta_cliente = 1
		WHERE id = %s;",
			valTpDato($row['id_cliente'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$objResponse->alert("El Prospecto perteneciente a este Presupuesto, no está Aprobado como Cliente. Recomendamos lo apruebe en la pantalla de Prospectación, para así generar dicho Presupuesto");
	}
	
	return $objResponse;
}

function listaPresupuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("pres_vent.estado IN (0,3)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pres_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = pres_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pres_vent.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($valCadBusq[3]) {
			case "00" : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 0)");
			case "22" : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 2)");
			case "33" : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 3)");
			default : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 1 AND estado_pedido = %s)",
						valTpDato($valCadBusq[3], "int"));
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pres_vent.id_presupuesto LIKE %s
		OR ped_vent.id_cliente LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT(uni_bas.nom_uni_bas,': ', modelo.nom_modelo,' - ', vers.nom_version) LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		pres_vent.fecha,
		pres_vent_acc.id_presupuesto_accesorio,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT(uni_bas.nom_uni_bas, ': ', marca.nom_marca, ' ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
		
		(IFNULL(pres_vent.precio_venta, 0)
			+ IFNULL(pres_vent.precio_venta * (pres_vent.porcentaje_iva + pres_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta,
		
		pres_vent.porcentaje_inicial,
		pres_vent.monto_inicial,
		pres_vent.total_general,
		
		(SELECT COUNT(*)
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		WHERE id_uni_bas = pres_vent.id_uni_bas
			AND (alm.id_empresa = pres_vent.id_empresa
				OR pres_vent.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = alm.id_empresa)
				OR pres_vent.id_empresa IN (SELECT suc.id_empresa FROM pg_empresa suc
						WHERE suc.id_empresa_padre = alm.id_empresa)
				OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = pres_vent.id_empresa) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
																	WHERE suc.id_empresa = alm.id_empresa))
			AND estado_venta IN ('POR REGISTRAR','DISPONIBLE')
			AND propiedad = 'PROPIO') AS ud,
		
		pres_vent.estado AS estado_presupuesto,
		ped_vent.estado_pedido,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_presupuesto pres_vent
		INNER JOIN cj_cc_cliente cliente ON (pres_vent.id_cliente = cliente.id)
		INNER JOIN an_uni_bas uni_bas ON (pres_vent.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
		INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
		LEFT JOIN an_pedido ped_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
		LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (pres_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "6%", $pageNum, "numeracion_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "6%", $pageNum, "id_presupuesto_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto Accesorios");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "20%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "24%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "8%", $pageNum, "precio_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Venta");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "8%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Inicial");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "8%", $pageNum, "total_general", $campOrd, $tpOrd, $valBusq, $maxRows, "Total General");
		$htmlTh .= "<td colspan=\"6\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusPedido = "";
		if ($row['estado_presupuesto'] == 0) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Autorizado\"/>";
		} else if ($row['estado_presupuesto'] == 1) {
			switch ($row['estado_pedido']) {
				case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			}
		} else if ($row['estado_presupuesto'] == 2) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto Anulado\"/>";
		} else if ($row['estado_presupuesto'] == 3) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Presupuesto Desautorizado\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_presupuesto']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_presupuesto_accesorio']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= utf8_encode($row['vehiculo']);
				$htmlTb .= ($row['ud'] > 0) ? "<br><span class=\"textoNegrita_10px\">Disponible: ".number_format($row['ud'], 2, ".", ",")."</span>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_venta'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format($row['monto_inicial'], 2, ".", ",");
				$htmlTb .= "<br><span class=\"textoNegrita_10px\">".number_format($row['porcentaje_inicial'], 2, ".", ",")."%</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_general'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($row['estado_presupuesto'] == 0 && $row['ud'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_generarPresupuesto('%s')\" src=\"../img/iconos/book_next.png\" title=\"Generar Pedido\"/>",
					$row['id_presupuesto']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";	
			if ($row['estado_presupuesto'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('an_ventas_presupuesto_editar.php?id=%s','_self')\" src=\"../img/iconos/pencil.png\" title=\"Editar Presupuesto\"/>",
					$row['id_presupuesto']);
			} else if($row['estado_presupuesto'] == 3) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAutorizar('%s')\" src=\"../img/iconos/accept.png\" title=\"Autorizar Presupuesto\"/>",
					$row['id_presupuesto']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['id_presupuesto_accesorio'] > 0){
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('an_combo_presupuesto_list.php?view=1&id=%s','_self');\" src=\"../img/iconos/generarPresupuesto.png\" title=\"Editar Presupuesto Accesorios\"/>",
					$row['id_presupuesto']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";	
			if ($row['estado_presupuesto'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarDesautorizar('%s')\" src=\"../img/iconos/cancel.png\" title=\"Desautorizar Presupuesto\"/>",
					$row['id_presupuesto']);
			} else if ($row['estado_presupuesto'] == 3) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAnular('%s','%s')\" src=\"../img/iconos/ico_delete.png\" title=\"Anular Presupuesto\"/>",
					$row['id_presupuesto'],
					$row['id_presupuesto']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('an_ventas_presupuesto_editar.php?view=print&id=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir Presupuesto\"/></td>",
				$row['id_presupuesto']);
			$htmlTb .= "<td>";
			if ($row['id_presupuesto_accesorio'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Presupuesto Accesorio PDF\"/>",
					$row['id_presupuesto_accesorio']);
			}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"16\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPresupuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularPresupuesto");
$xajax->register(XAJAX_FUNCTION,"autorizarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"buscarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"desautorizarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"generarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"listaPresupuesto");
?>