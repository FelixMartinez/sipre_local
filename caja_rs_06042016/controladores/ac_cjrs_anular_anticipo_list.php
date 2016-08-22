<?php


function buscar($frmBuscar){
	$objResponse = new xajaxResponse();

	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$idEmpresa = $frmBuscar['lstEmpresa'];
	}
	
	$valBusq = sprintf("%s|%s",
		$idEmpresa,
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listadoAnticipo(0, '','ASC', $valBusq));
	
	return $objResponse;
}

function cargarPagina($idEmpresa){
	
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
		
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$objResponse->script("
		byId('trEmpresa').style.display = 'none';");
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$objResponse->script("
		byId('trEmpresa').style.display = '';");
	}
	
	return $objResponse;
}

function imprimirEliminarPagosdelDia($frmBuscar){
	$objResponse = new xajaxResponse();

	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
		
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$idEmpresa = $frmBuscar['lstEmpresa'];
	}
	
	$valBusq = sprintf("%s|%s|%s",
		$idEmpresa,
		$frmBuscar['lstTipoPago'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjrs_eliminar_pagos_del_dia_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listadoPagosDelDia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$fechaActual = date("Y-m-d");
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqNotaCargo .= $cond.sprintf("cj_cc_notadecargo.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
			
		$sqlBusqFactura .= $cond.sprintf("cj_cc_encabezadofactura.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));			
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != ""){
		$sqlBusqNotaCargo .= " AND ".sprintf("cj_det_nota_cargo.idFormaPago = %s",
			valTpDato($valCadBusq[1], "int"));
			
		$sqlBusqFactura .= " AND ".sprintf("sa_iv_pagos.formaPago = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqNotaCargo .= $cond.sprintf("(cj_cc_notadecargo.numeroNotaCargo LIKE %s
		OR cj_cc_notadecargo.numeroNotaCargo LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
			
		$sqlBusqFactura .= $cond.sprintf("(cj_cc_encabezadofactura.numeroFactura LIKE %s
		OR cj_cc_encabezadofactura.numeroFactura LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));			
	}
	
	$query = sprintf("SELECT
		'NOTA CARGO' AS tipo_documento,
		cj_cc_notadecargo.id_empresa AS id_empresa,
		cj_det_nota_cargo.id_det_nota_cargo AS id_pago,
		cj_det_nota_cargo.idFormaPago AS id_forma_pago,
		formapagos.nombreFormaPago AS tipo_pago,
		cj_det_nota_cargo.numeroDocumento AS numero_control_pago,
		cj_det_nota_cargo.monto_pago AS monto_pagado,
		cj_cc_notadecargo.numeroNotaCargo AS numero_documento,
		cj_cc_notadecargo.idCliente AS id_cliente,
		CONCAT_WS(' ', nombre, apellido ) AS cliente,
		cj_det_nota_cargo.idNotaCargo AS id_documento,
		'cj_det_nota_cargo' AS tabla_detalle,
		'cj_cc_notadecargo' AS tabla_cabecera,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM
		cj_cc_notadecargo
		INNER JOIN cj_det_nota_cargo ON (cj_cc_notadecargo.idNotaCargo = cj_det_nota_cargo.idNotaCargo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_notadecargo.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN formapagos ON (cj_det_nota_cargo.idFormaPago = formapagos.idFormaPago)
		INNER JOIN cj_cc_cliente ON (cj_cc_notadecargo.idCliente = cj_cc_cliente.id)
	WHERE
		cj_det_nota_cargo.fechaPago = %s AND 
		cj_det_nota_cargo.idCaja = 2 %s
		
	UNION
	
	SELECT
		'FACTURA' AS tipo_documento,
		cj_cc_encabezadofactura.id_empresa AS id_empresa,
		sa_iv_pagos.idPago AS id_pago,
		sa_iv_pagos.formaPago AS id_forma_pago,
		formapagos.nombreFormaPago AS tipo_pago,
		sa_iv_pagos.numeroDocumento AS numero_control_pago,
		sa_iv_pagos.montoPagado AS monto_pagado,
		sa_iv_pagos.numeroFactura AS numero_documento,
		cj_cc_encabezadofactura.idCliente AS id_cliente,
		CONCAT_WS(' ', nombre, apellido ) AS cliente,
		(SELECT idFactura FROM cj_cc_encabezadofactura WHERE cj_cc_encabezadofactura.idFactura = sa_iv_pagos.id_factura
				AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura IN (0,1,3)
				AND cj_cc_encabezadofactura.montoTotalFactura > 0) AS id_documento,
		'sa_iv_pagos' AS tabla_detalle,
		'cj_cc_encabezadofactura' AS tabla_cabecera,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM
		cj_cc_encabezadofactura
		INNER JOIN sa_iv_pagos ON (cj_cc_encabezadofactura.idFactura = sa_iv_pagos.id_factura)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_encabezadofactura.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN formapagos ON (sa_iv_pagos.formaPago = formapagos.idFormaPago)
		INNER JOIN cj_cc_cliente ON (cj_cc_encabezadofactura.idCliente = cj_cc_cliente.id)
	WHERE
		sa_iv_pagos.fechaPago = %s AND 
		sa_iv_pagos.idCaja = 2 %s",
			valTpDato($fechaActual,'date'),	$sqlBusqNotaCargo,
			valTpDato($fechaActual,'date'),	$sqlBusqFactura);			
			
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoPagosDelDia", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoPagosDelDia", "10%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Documento");
		$htmlTh .= ordenarCampo("xajax_listadoPagosDelDia", "10%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listadoPagosDelDia", "10%", $pageNum, "numero_control_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Referencia");
		$htmlTh .= ordenarCampo("xajax_listadoPagosDelDia", "20%", $pageNum, "cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listadoPagosDelDia", "15%", $pageNum, "tipo_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Pago");
		$htmlTh .= ordenarCampo("xajax_listadoPagosDelDia", "15%", $pageNum, "monto_pagado", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "<td class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['tipo_documento']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['numero_documento']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_control_pago']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_pago'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_pagado'],2,',','.')."</td>";
//			$htmlTb .= sprintf("<td align=\"center\" title='Eliminar Pago'><img class=\"puntero\" src=\"../img/iconos/delete.png\" onclick=\"xajax_validarEliminarPago(%s,%s,%s,%s);\"/></td>",valTpDato($row['id_pago'],'int'),valTpDato($row['tabla_detalle'],'text'),valTpDato($row['tabla_cabecera'],'text'),valTpDato($row['id_documento'],'int'));
			$htmlTb .= sprintf("<td align=\"center\" title='Eliminar Pago'><img class=\"puntero\" src=\"../img/iconos/delete.png\" onclick=\"xajax_validarAperturaCaja(%s,%s,%s,%s);\"/></td>",valTpDato($row['id_pago'],'int'),valTpDato($row['tabla_detalle'],'text'),valTpDato($row['tabla_cabecera'],'text'),valTpDato($row['id_documento'],'int'));
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr class=\"noprint\">";
		$htmlTf .= "<td align=\"right\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagosDelDia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagosDelDia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoPagosDelDia(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagosDelDia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagosDelDia(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoPagosDelDia","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoAnticipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$andEmpresa = sprintf(" AND id_empresa = %s",
			valTpDato($valCadBusq[0],"int"));
			
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresa = '';
	}
	
	//CONSULTO LA FECHA DE APERTURA
	$sqlSelectApertura = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura
	WHERE statusAperturaCaja = %s
	%s",
		valTpDato(1,"int"), // 0 = cerrada; 1 = abierta; 2 = cerrada parcial
		$andEmpresa);
	$rsSelectApertura = mysql_query($sqlSelectApertura);
	if (!$rsSelectApertura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectApertura);
	$rowSelectApertura = mysql_fetch_array($rsSelectApertura);
	
	$fechaApertura = $rowSelectApertura['fechaAperturaCaja'];
			
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estadoAnticipo IN (1) 
		AND idDepartamento IN (0,1,3)
		AND estatus = 1
		AND fechaAnticipo = %s",
			valTpDato(date("Y-m-d", strtotime($fechaApertura)),"date"));
			
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("an.id_empresa = %s)",
			valTpDato($valCadBusq[0],"int"));
	}

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("numeroAnticipo LIKE %s
			OR an.idCliente IN (SELECT cl.id FROM cj_cc_cliente cl WHERE cl.nombre LIKE %s OR cl.apellido LIKE %s))",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		an.idAnticipo,
		an.idCliente,
		(SELECT CONCAT_WS(' ',cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id = an.idCliente) AS nombre_cliente,
		(SELECT CONCAT_WS('-',cl.lci,cl.ci) FROM cj_cc_cliente cl WHERE cl.id = an.idCliente) AS rif_cliente,
		an.montoNetoAnticipo,
		an.saldoAnticipo,
		an.fechaAnticipo,
		an.estadoAnticipo,
		an.numeroAnticipo,
		an.idDepartamento,
		(CASE estadoAnticipo
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado/No Asignado'
			WHEN 2 THEN 'Asignado Parcial'
			WHEN 3 THEN 'Asignado'
		END) AS estadoAnticipo,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM
		cj_cc_anticipo an
	INNER JOIN cj_cc_cliente ON (an.idCliente = cj_cc_cliente.id)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "", $pageNum, "idDepartamento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "10%", $pageNum, "fechaAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Anticipo");
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "10%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Anticipo");
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "30%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "10%", $pageNum, "estadoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado");
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "10%", $pageNum, "montoNetoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
		$htmlTh .= ordenarCampo("xajax_listadoAnticipo", "10%", $pageNum, "saldoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= "<td class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		$contFila ++;
		
		switch($row['idDepartamento']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			default : $imgDctoModulo = $row['idDepartamento'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td align=\"left\">".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fechaAnticipo']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroAnticipo']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['estadoAnticipo'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoNetoAnticipo'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoAnticipo'],2,".",",")."</td>";
			$htmlTb .= sprintf("<td align=\"center\" title='Anular Anticipo'><img class=\"puntero\" src=\"../img/iconos/delete.png\" onclick=\"xajax_validarAperturaCaja(%s);\"/></td>",valTpDato($row['idAnticipo'],'int'));
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr class=\"noprint\">";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("tdListadoAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$queryTotal = sprintf("SELECT
		SUM(an.montoNetoAnticipo) AS total,
		SUM(an.saldoAnticipo) AS saldo
	FROM cj_cc_anticipo an %s", $sqlBusq);
	$rsTotal = mysql_query($queryTotal);
	if (!$rsTotal) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryTotal);
	$rowTotal= mysql_fetch_array($rsTotal);
	
	$objResponse->script("byId('txtSaldo').value= '".number_format($rowTotal['saldo'],2,".",",")."';");
	$objResponse->script("byId('txtTotal').value= '".number_format($rowTotal['total'],2,".",",")."';");
	
	return $objResponse;
}

function validarEliminarPago($idAnticipo){
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse, "cjrs_anular_anticipo_list", "eliminar")){
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Ingreso de Clave de Acceso");
		$objResponse->assign("hddValores","value",$idAnticipo);
		$objResponse->script("		
			byId('divFlotante').style.display = '';
			centrarDiv(byId('divFlotante'));
			byId('txtContrasena').focus();
		");
	}
	
	return $objResponse;
}

function validarPermiso($frmBuscar){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($idUsuario, "int"),
		valTpDato($frmBuscar['txtContrasena'], "text"),
		valTpDato($frmBuscar['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		$arrayValores = explode("|",$frmBuscar['hddValores']);
		
		$idAnticipo = $arrayValores[0];
		
		// CONSULTO EL ANTICIPO
		$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo
		WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
		
		$idEmpresa = $rowAnticipo['id_empresa'];
		
		// CONSULTO EL EMPLEADO
		$queryEmpleado = sprintf("SELECT id_empleado FROM pg_usuario
		WHERE id_usuario = %s;",
			valTpDato($idUsuario, "int"));
		$rsEmpleado = mysql_query($queryEmpleado);
		if (!$rsEmpleado) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
		
		$idEmpleado = $rowEmpleado['id_empleado'];
		
		// ACTUALIZO EL ESTATUS A: 0 = INACTIVO
		$updateSQL = sprintf("UPDATE cj_cc_anticipo SET 
			estatus = %s,
			fecha_anulado = NOW(),
			id_empleado_anulado = %s,
			motivo_anulacion = %s
		WHERE idAnticipo = %s;",
			valTpDato(0, "int"),
			valTpDato($idEmpleado, "int"),
			valTpDato($frmBuscar['txtMotivoAnulacion'], "text"),
			valTpDato($idAnticipo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ELIMINO DE ESTADO DE CUENTA (Para que no aparezca en el estado de cuenta de CXC)
		$sqlDeleteEstadoCuenta = sprintf("DELETE FROM cj_cc_estadocuenta
		WHERE
			tipoDocumento = %s
			AND idDocumento = %s
			AND fecha = '%s'
			AND tipoDocumentoN = %s",
			valTpDato('AN',"text"),
			valTpDato($idAnticipo,"int"),
			date("Y-m-d"),
			valTpDato(3,"int"));
		$rsDeleteEstadoCuenta = mysql_query($sqlDeleteEstadoCuenta);
		if (!$rsDeleteEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlDeleteEstadoCuenta);
		
		// CONSULTO DETALLE DEL ANTICIPO
		$queryDetAnticipo = sprintf("SELECT * FROM cj_cc_detalleanticipo
		WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsDetAnticipo = mysql_query($queryDetAnticipo);
		if (!$rsDetAnticipo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($rowDetAnticipo = mysql_fetch_assoc($rsDetAnticipo)) {
			
			$idDetalleAnticipo = $rowDetAnticipo['idDetalleAnticipo'];
			$formaPago = $rowDetAnticipo['tipoPagoDetalleAnticipo'];
			
			if ($formaPago == 'EF'){//1 EFECTIVO
				$campo = "saldoEfectivo";
			}else if ($formaPago == 'CH'){//2 CHEQUE
				$campo = "saldoCheques";
			}else if ($formaPago == 'DP'){//3 DEPOSITO
				$campo = "saldoDepositos";
			}else if ($formaPago == 'TB'){//4 TRANSFERENCIA BANCARIA
				$campo = "saldoTransferencia";
			}else if ($formaPago == 'TC'){//5 TARJETA DE CREDITO
				$campo = "saldoTarjetaCredito";
			}else if ($formaPago == 'TD'){//6 TARJETA DE DEBITO
				$campo = "saldoTarjetaDebito";
			}else if ($formaPago == 'CB'){//7 CASH BACK
				$campo = "saldoCashBack";
			}
			
			// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
			$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato(1, "int")); // 1 = Empresa cabecera
			$rsConfig400 = mysql_query($queryConfig400);
			if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsConfig400 = mysql_num_rows($rsConfig400);
			$rowConfig400 = mysql_fetch_assoc($rsConfig400);
			
			if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
				$andEmpresa = sprintf(" AND id_empresa = %s",
					valTpDato($idEmpresa,"int"));
					
			} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
				$andEmpresa = '';
			}
		
			//CONSULTO LA CAJA PERTENECIENTE AL ANTICIPO // idCaja: 1 = CAJA VEHICULOS ; 2 = CAJA R y S
			$sqlSelectDatosAperturaCaja = sprintf("SELECT saldoCaja, id, %s FROM sa_iv_apertura
			WHERE idCaja = 2
				AND statusAperturaCaja IN (1,2)
				%s",
				$campo,
				$andEmpresa);
			$rsSelectDatosAperturaCaja = mysql_query($sqlSelectDatosAperturaCaja);
			if (!$rsSelectDatosAperturaCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectDatosAperturaCaja);
			$rowSelectDatosAperturaCaja = mysql_fetch_array($rsSelectDatosAperturaCaja);
			
			//RESTO MONTOS EN SALDO DE CAJA Y EL CAMPO CORRESPONDIENTE A LA FORMA DE PAGO
			$sqlUpdateDatosAperturaCaja = sprintf("UPDATE sa_iv_apertura SET %s = %s, saldoCaja = %s WHERE id = %s",
				$campo,
				valTpDato($rowSelectDatosAperturaCaja[$campo] - $rowDetAnticipo['montoDetalleAnticipo'],"double"),
				valTpDato($rowSelectDatosAperturaCaja['saldoCaja'] - $rowDetAnticipo['montoDetalleAnticipo'],"double"),
				valTpDato($rowSelectDatosAperturaCaja['id'],"int"));
			$rsUpdateDatosAperturaCaja = mysql_query($sqlUpdateDatosAperturaCaja);
			if (!$rsUpdateDatosAperturaCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateDatosAperturaCaja);
			
			//NO ELIMINO DETALLE DE ANTICIPO PARA TENER UN HISTORICO
			
			// ELIMINO EL DETALLE DE LA FORMA DE PAGO SI:
			if ($formaPago == 'DP'){//DEPOSITO
				$sqlDeleteDepositoAnt = sprintf("DELETE FROM cj_cc_det_pagos_deposito_anticipos
				WHERE
					idDetalleAnticipo = %s
					AND idFormaPago = %s
					AND id_tipo_documento = %s
					AND idCaja = %s",
					valTpDato($idDetalleAnticipo,"int"),
					valTpDato(3,"int"), // 3 = DEPOSITO
					valTpDato(4,"int"), // 1 = FACTURA, 2 = NOTA CARGO, 4 = ANTICIPO, 6 = OTROS
					valTpDato(2,"int")); // 1 = CAJA VEHICULOS , 2 = CAJA R y S
				$rsDeleteDepositoAnt = mysql_query($sqlDeleteDepositoAnt);
				if (!$rsDeleteDepositoAnt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$DeleteDepositoAnt);
				
			}else if ($formaPago == 'TC' || $formaPago == 'TD'){//TARJETA DE CREDITO //TARJETA DE DEBITO
				
				// ELIMINO LA RETENCION GENERADA POR TARJETA DE DEBITO Y/O CREDITO
				$sqlDeleteEstadoCuenta = sprintf("DELETE FROM cj_cc_retencion_punto_pago
				WHERE
					id_caja = %s
					AND id_pago = %s
					AND id_tipo_documento = %s",
					valTpDato(2,"int"), // 1 = CAJA VEHICULOS , 2 = CAJA R y S
					valTpDato($idDetalleAnticipo,"int"),
					valTpDato(4,"int")); // 1 = FACTURA, 2 = NOTA CARGO, 4 = ANTICIPO, 6 = OTROS
				$rsDeleteEstadoCuenta = mysql_query($sqlDeleteEstadoCuenta);
				if (!$rsDeleteEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlDeleteEstadoCuenta);
			}
		}
		
		$objResponse->alert("Anticipo anulado exitosamente.");
		
		$objResponse->script("byId('btnBuscar').click();
								byId('btnCancelarPermiso').click();");
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function validarAperturaCaja($idAnticipo = ''){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$fecha = date("Y-m-d");
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura WHERE statusAperturaCaja <> 0 AND fechaAperturaCaja NOT LIKE %s AND id_empresa = %s",
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCierreCaja);
	
	if (mysql_num_rows($rsCierreCaja) > 0){
		$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
		$fechaUltimaApertura = date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja']));
		return $objResponse->alert("Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
		
	} else {
	
		//VERIFICA SI LA CAJA TIENE APERTURA
		//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
		$queryVerificarApertura = sprintf("SELECT * FROM sa_iv_apertura WHERE fechaAperturaCaja = %s AND statusAperturaCaja <> 0 AND id_empresa = %s",
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL:".$queryVerificarApertura);
		
		if (mysql_num_rows($rsVerificarApertura) == 0){
			return $objResponse->alert("Esta caja no tiene apertura.");
		}
	}
	
	if ($idAnticipo > 0) {
		$objResponse->loadCommands(validarEliminarPago($idAnticipo));
	}
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"listadoAnticipo");

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"imprimirEliminarPagosdelDia");
$xajax->register(XAJAX_FUNCTION,"listadoPagosDelDia");
$xajax->register(XAJAX_FUNCTION,"validarEliminarPago");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");
?>