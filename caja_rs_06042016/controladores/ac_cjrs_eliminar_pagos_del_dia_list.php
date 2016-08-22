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
	
	$valBusq = sprintf("%s|%s|%s",
		$idEmpresa,
		$frmBuscar['lstTipoPago'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listadoPagosDelDia(0, '','ASC', $valBusq));
	
	return $objResponse;
}

function cargaLstTipoPago(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM formapagos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error());
	
	$html = "<select id=\"lstTipoPago\" name=\"lstTipoPago\" onchange=\"byId('btnBuscar').click();\" class=\"inputHabilitado\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['idFormaPago']."\">".utf8_encode($row['nombreFormaPago'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdSelTipoPago","innerHTML",$html);
	
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

function validarEliminarPago($idPago,$tablaDetalle,$tablaCabecera,$idDocumento){
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse, "cjrs_eliminar_pagos_del_dia_list", "eliminar")){
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Ingreso de Clave de Acceso");
		$objResponse->assign("hddValores","value",$idPago."|".$tablaDetalle."|".$tablaCabecera."|".$idDocumento);
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

	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
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
		
		$idPago = $arrayValores[0];
		$tablaDetalle = $arrayValores[1];
		$tablaCabecera = $arrayValores[2];
		$idDocumento = $arrayValores[3];
		
		if ($tablaCabecera == 'cj_cc_notadecargo'){
			$campos .= ' idFormaPago AS idFormaPago, ';
			$campos .= ' monto_pago AS montoPagado, ';
			$campos .= ' numeroDocumento AS numeroReferencia ';
			$nombreIdDetalle = ' id_det_nota_cargo ';
		}
		if ($tablaCabecera == 'cj_cc_encabezadofactura'){
			$campos .= ' formaPago AS idFormaPago, ';
			$campos .= ' montoPagado AS montoPagado, ';
			$campos .= ' numeroDocumento AS numeroReferencia ';
			$nombreIdDetalle = ' idPago ';
		}
		
		$sqlDatosPago = sprintf("SELECT %s FROM %s WHERE %s = %s",$campos,$tablaDetalle,$nombreIdDetalle,$idPago);
		$rsDatosPago = mysql_query($sqlDatosPago);
		if (!$rsDatosPago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlDatosPago);
		$rowDatosPago = mysql_fetch_array($rsDatosPago);
		
		if ($rowDatosPago['idFormaPago'] == 1){ //EFECTIVO
			$campoTipoPago = 'saldoEfectivo';
		}
		else if ($rowDatosPago['idFormaPago'] == 2){ //CHEQUES
			$campoTipoPago = 'saldoCheques';
		}
		else if ($rowDatosPago['idFormaPago'] == 3){ //DEPOSITO
			$campoTipoPago = 'saldoDepositos';
		}
		else if ($rowDatosPago['idFormaPago'] == 4){ //TRANSFERENCIA
			$campoTipoPago = 'saldoTransferencia';
		}
		else if ($rowDatosPago['idFormaPago'] == 5){ //TARJETA DE CRÉDITO
			$campoTipoPago = 'saldoTarjetaCredito';
		}
		else if ($rowDatosPago['idFormaPago'] == 6){ //TARJETA DE DÉDITO
			$campoTipoPago = 'saldoTarjetaDebito';
		}
		else if ($rowDatosPago['idFormaPago'] == 7){ //ANTICIPO
			$campoTipoPago = 'saldoAnticipo';
			
			$sqlUpdateAnticipo = sprintf("UPDATE cj_cc_anticipo SET saldoAnticipo = (saldoAnticipo + %s), estadoAnticipo = 2
			WHERE idAnticipo = %s AND idDepartamento IN (0,1,3)",
				valTpDato($rowDatosPago['montoPagado'],"double"),
				valTpDato($rowDatosPago['numeroReferencia'],"int"));
			$rsUpdateAnticipo = mysql_query($sqlUpdateAnticipo);
			if (!$rsUpdateAnticipo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlUpdateAnticipo);
		}
		else if ($rowDatosPago['idFormaPago'] == 8){ //NOTA DE CRÉDITO
			$campoTipoPago = 'saldoNotaCredito';
			
			$sqlUpdateNotaCredito = sprintf("UPDATE cj_cc_notacredito SET saldoNotaCredito = (saldoNotaCredito + %s), estadoNotaCredito = 2
			WHERE idNotaCredito = %s",
				valTpDato($rowDatosPago['montoPagado'],"double"),
				valTpDato($rowDatosPago['numeroReferencia'],"int"));
			$rsUpdateNotaCredito = mysql_query($sqlUpdateNotaCredito);
			if (!$rsUpdateNotaCredito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlUpdateNotaCredito);
		}
		else if ($rowDatosPago['idFormaPago'] == 9){ //RETENCION
			$campoTipoPago = 'saldoRetencion';
			
			$sqlDeleteRetencionIva = sprintf("DELETE FROM cj_cc_retencioncabezera WHERE idRetencionCabezera = (SELECT idRetencionCabezera FROM cj_cc_retenciondetalle WHERE idFactura = %s LIMIT 0,1)",
				valTpDato($idDocumento,"int"));
			$rsDeleteRetencionIva = mysql_query($sqlDeleteRetencionIva);
			if (!$rsDeleteRetencionIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlDeleteRetencionIva);
		}
		else if ($rowDatosPago['idFormaPago'] == 10){ //RETENCION ISLR
			$campoTipoPago = 'saldoRetencion';
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
					
		$sqlUpdateAperturaCaja = sprintf("UPDATE sa_iv_apertura SET %s = (%s - %s), saldoCaja = (saldoCaja - %s)
		WHERE idCaja = 2 AND statusAperturaCaja = 1 %s ",
			$campoTipoPago,
			$campoTipoPago,
			valTpDato($rowDatosPago['montoPagado'],"double"),
			valTpDato($rowDatosPago['montoPagado'],"double"),
			$andEmpresa);
		$rsUpdateAperturaCaja = mysql_query($sqlUpdateAperturaCaja);
		if (!$rsUpdateAperturaCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlUpdateAperturaCaja);
		
		if ($tablaCabecera == 'cj_cc_notadecargo'){
			$sqlEditarSaldoDocumento = sprintf("UPDATE cj_cc_notadecargo SET saldoNotaCargo = (saldoNotaCargo + %s), estadoNotaCargo = 2 WHERE idNotaCargo = %s",
				valTpDato($rowDatosPago['montoPagado'],"double"),
				valTpDato($idDocumento,"int"));
		}
		
		if ($tablaCabecera == 'cj_cc_encabezadofactura'){
			$sqlEditarSaldoDocumento = sprintf("UPDATE cj_cc_encabezadofactura SET saldoFactura = (saldoFactura + %s), estadoFactura = 2 WHERE idFactura = %s",
				valTpDato($rowDatosPago['montoPagado'],"double"),
				valTpDato($idDocumento,"int"));
		}
		
		$rsEditarSaldoDocumento = mysql_query($sqlEditarSaldoDocumento);
		if (!$rsEditarSaldoDocumento) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlEditarSaldoDocumento);
		
		$sqlEliminarPago = sprintf("DELETE FROM %s WHERE %s = %s",$tablaDetalle,$nombreIdDetalle,$idPago);
		$rsEliminarPago = mysql_query($sqlEliminarPago);
		if (!$rsEliminarPago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlEliminarPago);
		
		$objResponse->alert("Pago eliminado exitosamente.");
		
		$objResponse->script("byId('btnBuscar').click();
								byId('btnCancelarPermiso').click();");
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function validarAperturaCaja($idPago = '', $tablaDetalle = '', $tablaCabecera = '', $idDocumento = ''){
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
	
	if ($idPago > 0) {
		$objResponse->loadCommands(validarEliminarPago($idPago, $tablaDetalle, $tablaCabecera, $idDocumento));
	}
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"imprimirEliminarPagosdelDia");
$xajax->register(XAJAX_FUNCTION,"listadoPagosDelDia");
$xajax->register(XAJAX_FUNCTION,"validarEliminarPago");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");
?>