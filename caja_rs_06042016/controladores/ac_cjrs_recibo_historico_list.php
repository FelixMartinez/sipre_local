<?php


function buscarRecibo($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstTipoDcto'],
		$frmBuscar['lstTipoPago'],
		$frmBuscar['lstModulo'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaRecibo(0, "CONCAT(fechaComprobante, LPAD(numeroComprobante, 20, 0))", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (0,1,3)");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_enlace_concepto'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_enlace_concepto']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function listaRecibo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(CASE
		WHEN (recibo.idTipoDeDocumento = 1) THEN
			cxc_fact.idDepartamentoOrigenFactura
		WHEN (recibo.idTipoDeDocumento = 2) THEN
			cxc_nd.idDepartamentoOrigenNotaCargo
	END) IN (0,1,3)");
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("
	(CASE
		WHEN (recibo.tipoDocumento LIKE 'AN') THEN
			cxc_ant.idDepartamento
	END) IN (0,1,3)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (recibo.idTipoDeDocumento = 1) THEN
				(cxc_fact.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxc_fact.id_empresa))
			WHEN (recibo.idTipoDeDocumento = 2) THEN
				(cxc_nd.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxc_nd.id_empresa))
		END)",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("
		(CASE
			WHEN (recibo.tipoDocumento LIKE 'AN') THEN
				(cxc_ant.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxc_ant.id_empresa))
		END)",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("recibo.fechaComprobante BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("recibo.fechaDocumento BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") { // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = OT
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("query.idTipoDeDocumento = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("query.condicionDePago = %s",
			valTpDato($valCadBusq[4], "boolean"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("query.id_modulo = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("(query.numeroComprobante LIKE %s
		OR query.numeroFactura LIKE %s
		OR query.numeroControl LIKE %s
		OR query.nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT query.*
	FROM (SELECT 
			recibo.idComprobante AS id_recibo_pago,
			recibo.fechaComprobante,
			recibo.numeroComprobante,
			recibo.idTipoDeDocumento,
			(CASE
				WHEN (recibo.idTipoDeDocumento = 1) THEN
					'FA'
				WHEN (recibo.idTipoDeDocumento = 2) THEN
					'ND'
			END) AS tipoDocumento,
			(CASE
				WHEN (recibo.idTipoDeDocumento = 1) THEN
					'Factura'
				WHEN (recibo.idTipoDeDocumento = 2) THEN
					'Nota de Cargo'
			END) AS tipo_documento_pagado,
			(CASE
				WHEN (recibo.idTipoDeDocumento = 1) THEN
					cxc_fact.idFactura
				WHEN (recibo.idTipoDeDocumento = 2) THEN
					cxc_nd.idNotaCargo
			END) AS id_documento_pagado,
			(CASE
				WHEN (recibo.idTipoDeDocumento = 1) THEN
					cxc_fact.fechaRegistroFactura
				WHEN (recibo.idTipoDeDocumento = 2) THEN
					cxc_nd.fechaRegistroNotaCargo
			END) AS fechaRegistroFactura,
			(CASE
				WHEN (recibo.idTipoDeDocumento = 1) THEN
					cxc_fact.numeroFactura
				WHEN (recibo.idTipoDeDocumento = 2) THEN
					cxc_nd.numeroNotaCargo
			END) AS numeroFactura,
			(CASE
				WHEN (recibo.idTipoDeDocumento = 1) THEN
					cxc_fact.numeroControl
				WHEN (recibo.idTipoDeDocumento = 2) THEN
					cxc_nd.numeroControlNotaCargo
			END) AS numeroControl,
			(CASE
				WHEN (recibo.idTipoDeDocumento = 1) THEN
					cxc_fact.idDepartamentoOrigenFactura
				WHEN (recibo.idTipoDeDocumento = 2) THEN
					cxc_nd.idDepartamentoOrigenNotaCargo
			END) AS id_modulo,
			(CASE
				WHEN (recibo.idTipoDeDocumento = 1) THEN
					cxc_fact.condicionDePago
				WHEN (recibo.idTipoDeDocumento = 2) THEN
					0
			END) AS condicionDePago,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_encabezadorecibopago recibo
			LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (recibo.numero_tipo_documento = cxc_fact.idFactura AND recibo.idTipoDeDocumento = 1)
			LEFT JOIN cj_cc_notadecargo cxc_nd ON (recibo.numero_tipo_documento = cxc_nd.idNotaCargo AND recibo.idTipoDeDocumento = 2)
			LEFT JOIN cj_cc_cliente cliente ON ((cxc_fact.idCliente = cliente.id AND recibo.idTipoDeDocumento = 1)
				OR (cxc_nd.idCliente = cliente.id AND recibo.idTipoDeDocumento = 2))
			LEFT JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON ((cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg AND recibo.idTipoDeDocumento = 1)
				OR (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg AND recibo.idTipoDeDocumento = 2)) %s
		
		UNION
		
		SELECT 
			recibo.idReporteImpresion,
			recibo.fechaDocumento,
			recibo.numeroReporteImpresion,
			(CASE
				WHEN (recibo.tipoDocumento LIKE 'AN') THEN
					4
			END) AS idTipoDeDocumento,
			recibo.tipoDocumento,
			(CASE
				WHEN (recibo.tipoDocumento LIKE 'AN') THEN
					'Anticipo'
			END) AS tipo_documento_pagado,
			(CASE
				WHEN (recibo.tipoDocumento LIKE 'AN') THEN
					cxc_ant.idAnticipo
			END) AS id_documento_pagado,
			(CASE
				WHEN (recibo.tipoDocumento LIKE 'AN') THEN
					cxc_ant.fechaAnticipo
			END) AS fechaRegistroFactura,
			(CASE
				WHEN (recibo.tipoDocumento LIKE 'AN') THEN
					cxc_ant.numeroAnticipo
			END) AS numeroFactura,
			(CASE
				WHEN (recibo.tipoDocumento LIKE 'AN') THEN
					cxc_ant.numeroAnticipo
			END) AS numeroControl,
			(CASE
				WHEN (recibo.tipoDocumento LIKE 'AN') THEN
					cxc_ant.idDepartamento
			END) AS id_modulo,
			(CASE
				WHEN (recibo.tipoDocumento LIKE 'AN') THEN
					1
			END) AS condicionDePago,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM pg_reportesimpresion recibo
			LEFT JOIN cj_cc_anticipo cxc_ant ON (recibo.idDocumento = cxc_ant.idAnticipo AND recibo.tipoDocumento LIKE 'AN')
			LEFT JOIN cj_cc_cliente cliente ON ((cxc_ant.idCliente = cliente.id AND recibo.tipoDocumento LIKE 'AN'))
			LEFT JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON ((cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg AND recibo.tipoDocumento LIKE 'AN')) %s) AS query %s", $sqlBusq, $sqlBusq2, $sqlBusq3);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error().$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaRecibo", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaRecibo", "6%", $pageNum, "fechaComprobante", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Recibo");
		$htmlTh .= ordenarCampo("xajax_listaRecibo", "8%", $pageNum, "LPAD(numeroComprobante, 20, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Recibo");
		$htmlTh .= ordenarCampo("xajax_listaRecibo", "8%", $pageNum, "tipo_documento_pagado", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaRecibo", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Factura / Nota de Cargo / Anticipo / Cheque");
		$htmlTh .= ordenarCampo("xajax_listaRecibo", "8%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura / Nota de Cargo / Anticipo / Cheque");
		$htmlTh .= ordenarCampo("xajax_listaRecibo", "8%", $pageNum, "numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaRecibo", "36%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaRecibo", "6%", $pageNum, "condicionDePago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgDctoModulo."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaComprobante']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroComprobante']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_documento_pagado'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicionDePago'] == 1) ? "divMsjInfo" : "divMsjAlerta")."\">";
				$htmlTb .= ($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			switch ($row['idTipoDeDocumento']) {
				case 1 :
					switch ($row['id_modulo']) {
						case 0 : // REPUESTOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>",
								$row['id_documento_pagado']);
							break;
						case 1 : // SERVICIOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>",
								$row['id_documento_pagado']);
							break;
						case 2 : // VEHICULOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>",
								$row['id_documento_pagado']);
							break;
						case 3 : // ADMINISTRACION
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/></a>",
								$row['id_documento_pagado']);
							break;
					}
					break;
				case 2 :
					$htmlTb .= sprintf("<a href=\"javascript:verVentana('../CuentasxCobrar/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Cargo PDF\"/></a>",
						$row['id_documento_pagado']);
					break;
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			switch ($row['idTipoDeDocumento']) {
				case 1 :
					switch ($row['id_modulo']) {
						case 0 : // REPUESTOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../caja_rs/reportes/cjrs_comprobante_pago_factura_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
								$row['id_documento_pagado']);
							break;
						case 1 : // SERVICIOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../caja_rs/reportes/cjrs_comprobante_pago_factura_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
								$row['id_documento_pagado']);
							break;
						case 2 : // VEHICULOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cajax3/reportes/cj_comprobante_pago_factura_pdf.php?valBusq=%s|%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
								$row['id_documento_pagado'],
								$row['id_recibo_pago']);
							break;
						case 3 : // ADMINISTRACION
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../caja_rs/reportes/cjrs_comprobante_pago_factura_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
								$row['id_documento_pagado']);
							break;
					}
					break;
				case 2 :
					$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/cjrs_comprobante_pago_nota_cargo_pdf.php?valBusq=%s|%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
						$row['id_documento_pagado'],
						$row['id_recibo_pago']);
					break;
				case 4 :
					switch ($row['id_modulo']) {
						case 0 : // REPUESTOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../caja_rs/reportes/cjrs_comprobante_pago_anticipo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
								$row['id_documento_pagado']);
							break;
						case 1 : // SERVICIOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../caja_rs/reportes/cjrs_comprobante_pago_anticipo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
								$row['id_documento_pagado']);
							break;
						case 2 : // VEHICULOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cajax3/reportes/cj_comprobante_pago_anticipo_pdf.php?valBusq=%s|%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
								$row['id_documento_pagado'],
								$row['id_recibo_pago']);
							break;
						case 3 : // ADMINISTRACION
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../caja_rs/reportes/cjrs_comprobante_pago_anticipo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
								$row['id_documento_pagado']);
							break;
					}
					break;
				case 5 :
					switch ($row['id_modulo']) {
						case 0 : // REPUESTOS
							break;
						case 1 : // SERVICIOS
							break;
						case 2 : // VEHICULOS
							$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cajax3/reportes/cj_comprobante_pago_cheque_pdf.php?valBusq=%s|%s', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"Recibo(s) de Pago(s)\"/></a>",
								$row['id_documento_pagado'],
								$row['id_recibo_pago']);
							break;
						case 3 : // ADMINISTRACION
							break;
					}
					break;
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		//$arrayTotal[11] += $row['total_pagos'];
	}
	/*if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"4\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[11] += $row['total_pagos'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"4\"></td>";
			$htmlTb .= "</tr>";
		}
	}*/

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"15\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRecibo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRecibo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaRecibo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRecibo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRecibo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"15\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaRecibo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalFacturas += $row['montoTotalFactura'];
		$totalSaldo += $row['saldoFactura'];
		$totalCobranza += $row['montopagado'];
	}
	
	$objResponse->assign("spnTotalCobranzas","innerHTML",number_format($totalCobranza, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarRecibo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"listaRecibo");
?>