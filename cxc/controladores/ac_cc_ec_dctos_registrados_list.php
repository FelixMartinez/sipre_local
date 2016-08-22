<?php


function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente.id = %s",
		valTpDato($idCliente, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($estatusCliente != "-1" && $estatusCliente != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.status = %s",
			valTpDato($estatusCliente, "text"));
	}
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto,
		cliente.status
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$idClaveMovimiento = ($idClaveMovimiento == "") ? $rowCliente['id_clave_movimiento_predeterminado'] : $idClaveMovimiento;
	
	if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
		$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
			valTpDato($rowCliente['id_cliente_empresa'], "int"));
		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		$fechaVencimiento = suma_fechas("d-m-Y",date("d-m-Y"),$rowClienteCredito['diascredito']);
		
		$objResponse->assign("txtDiasCreditoCliente","value",$rowClienteCredito['diascredito']);
		$objResponse->assign("txtCreditoCliente","value",number_format($rowClienteCredito['creditodisponible'], 2, ".", ","));
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "0", "1", $idClaveMovimiento, "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""));
		
		$objResponse->script("
		byId('aDesbloquearClaveMovimiento').style.display = '';
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','0','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
		}");*/
	} else {
		$fechaVencimiento = date("d-m-Y");
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", $idClaveMovimiento, "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""));
		
		$objResponse->script("
		byId('aDesbloquearClaveMovimiento').style.display = '';
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','1','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
		}");*/
	}
	
	if ($rowCliente['id'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",$tdMsjCliente);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "ASC", $valBusq));
		
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

function buscarEstadoCuenta($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdCliente'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['radioOpcion'],
		$frmBuscar['lstTipoDetalle'],
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"),
		((count($frmBuscar['cbxDcto']) > 0) ? implode(",",$frmBuscar['cbxDcto']) : "-1"),
		implode(",",$frmBuscar['lstEstadoFactura']),
		$frmBuscar['txtCriterio']);
	
	switch ($frmBuscar['radioOpcion']) {
		case 1 : $objResponse->loadCommands(listaECIndividual(0, "CONCAT(q.fechaRegistroFactura, q.idEstadoCuenta)", "DESC", $valBusq)); break;
		case 2 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
		case 3 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
		case 4 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
	}
	
	return $objResponse;
}

function cargaLstEstadoFactura($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$array = array("0" => "No Cancelado", "1" => "Cancelado (No Asignado)", "2" => "Asignado Parcial", "3" => "Asignado", "4" => "No Cancelado (Asignado)");
	$totalRows = count($array);
	
	$html .= "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstEstadoFactura\" name=\"lstEstadoFactura\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoFactura","innerHTML", $html);
	
	return $objResponse;
}

function cargarModulos(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td width=\"50%\"><label><input type=\"checkbox\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"".$row['id_modulo']."\"/> ".$row['descripcionModulo']."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function cargarTipoDocumento(){
	$objResponse = new xajaxResponse();
	
	$array = array(1 => "Factura", 2 => "Nota de Débito", 3 => "Anticipo", 4 => "Nota de Crédito", 5 => "Cheque", 6 => "Transferencia");
	
	$html = "<table border=\"0\" width=\"100%\">";
	foreach ($array as $indice => $valor) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td width=\"50%\"><label><input type=\"checkbox\" id=\"cbxDcto\" name=\"cbxDcto[]\" checked=\"checked\" value=\"".($indice)."\"/> ".utf8_encode($array[$indice])."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdTipoDocumento","innerHTML",$html);
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
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
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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

function listaECGeneral($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = q.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq1) > 0) ? " AND " : " WHERE ";
		$sqlBusq1 .= $cond.sprintf("cxc_nd.fechaRegistroNotaCargo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cxc_ant.fechaAnticipo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_nc.fechaNotaCredito BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_ch.fecha_cheque BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("cxc_tb.fecha_transferencia BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
	}
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("q.fechaRegistroFactura BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.id_modulo IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.tipoDocumentoN IN (%s)",
			valTpDato($valCadBusq[7], "campo"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.estadoFactura IN (%s)",
			valTpDato($valCadBusq[8], "campo"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.numeroFactura LIKE %s
		OR CONCAT_WS(' ', cliente.lci,	cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11)) LIKE %s
		OR q.observacionFactura LIKE %s)",
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"));
	}
	
	$query = sprintf("SELECT q.*,
		CONCAT_WS(' ', cliente.lci,	cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		
		IF (q.tipoDocumento IN ('FA') AND q.id_modulo IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = q.idFactura))) 
			, NULL) AS numero_siniestro,
		
		IF (q.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) AS descripcion_concepto_forma_pago,
		
		(CASE
			WHEN (q.tipoDocumento IN ('FA','ND')) THEN
				(CASE q.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END)
			WHEN (q.tipoDocumento IN ('AN','NC','CH','TB')) THEN
				(CASE q.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
					WHEN 4 THEN 'No Cancelado (Asignado)'
				END)
		END) AS estado_documento,
		
		(CASE q.tipoDocumento
			WHEN ('FA') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.estatus IN (1)
						
					UNION
		
					SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_factura = q.idFactura)
			WHEN ('ND') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.idNotaCargo, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.idNotaCargo = q.idFactura)
			WHEN ('AN') THEN
				(SELECT MAX(q2.fechaPagoAnticipo)
				FROM (SELECT cxc_pago.idAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.idAnticipo = q.idFactura)
			WHEN ('NC') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroControlDetalleAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (8)
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.numeroDocumento = q.idFactura)
			WHEN ('CH') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_cheque = q.idFactura)
			WHEN ('TB') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_transferencia = q.idFactura)
		END) AS fecha_ultimo_pago,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM (SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_fact.idFactura,
				cxc_fact.id_empresa,
				cxc_fact.fechaRegistroFactura,
				cxc_fact.fechaVencimientoFactura,
				cxc_fact.numeroFactura,
				cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
				cxc_fact.idCliente,
				cxc_fact.estadoFactura,
				cxc_fact.observacionFactura,
				cxc_fact.montoTotalFactura,
				cxc_fact.saldoFactura
			FROM cj_cc_encabezadofactura cxc_fact
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 1) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_nd.idNotaCargo,
				cxc_nd.id_empresa,
				cxc_nd.fechaRegistroNotaCargo,
				cxc_nd.fechaVencimientoNotaCargo,
				cxc_nd.numeroNotaCargo,
				cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
				cxc_nd.idCliente,
				cxc_nd.estadoNotaCargo,
				cxc_nd.observacionNotaCargo,
				cxc_nd.montoTotalNotaCargo,
				cxc_nd.saldoNotaCargo
			FROM cj_cc_notadecargo cxc_nd
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nd.idNotaCargo = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 2) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_ant.idAnticipo,
				cxc_ant.id_empresa,
				cxc_ant.fechaAnticipo,
				NULL AS fechaVencimientoFactura,
				cxc_ant.numeroAnticipo,
				cxc_ant.idDepartamento AS id_modulo,
				cxc_ant.idCliente,
				cxc_ant.estadoAnticipo,
				cxc_ant.observacionesAnticipo,
				cxc_ant.montoNetoAnticipo,
				cxc_ant.saldoAnticipo
			FROM cj_cc_anticipo cxc_ant
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ant.idAnticipo = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 3) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_nc.idNotaCredito,
				cxc_nc.id_empresa,
				cxc_nc.fechaNotaCredito,
				NULL AS fechaVencimientoFactura,
				cxc_nc.numeracion_nota_credito,
				cxc_nc.idDepartamentoNotaCredito,
				cxc_nc.idCliente,
				cxc_nc.estadoNotaCredito,
				cxc_nc.observacionesNotaCredito,
				cxc_nc.montoNetoNotaCredito,
				cxc_nc.saldoNotaCredito
			FROM cj_cc_notacredito cxc_nc
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nc.idNotaCredito = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 4) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_ch.id_cheque,
				cxc_ch.id_empresa,
				cxc_ch.fecha_cheque,
				NULL AS fechaVencimientoFactura,
				cxc_ch.numero_cheque,
				cxc_ch.id_departamento,
				cxc_ch.id_cliente,
				cxc_ch.estado_cheque,
				cxc_ch.observacion_cheque,
				cxc_ch.monto_neto_cheque,
				cxc_ch.saldo_cheque
			FROM cj_cc_cheque cxc_ch
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ch.id_cheque = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 5) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_tb.id_transferencia,
				cxc_tb.id_empresa,
				cxc_tb.fecha_transferencia,
				NULL AS fechaVencimientoFactura,
				cxc_tb.numero_transferencia,
				cxc_tb.id_departamento,
				cxc_tb.id_cliente,
				cxc_tb.estado_transferencia,
				cxc_tb.observacion_transferencia,
				cxc_tb.monto_neto_transferencia,
				cxc_tb.saldo_transferencia
			FROM cj_cc_transferencia cxc_tb
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_tb.id_transferencia = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 6) %s) AS q
		INNER JOIN cj_cc_cliente cliente ON (q.idCliente = cliente.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (q.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq, $sqlBusq1, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__.$query);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	if (in_array($valCadBusq[4],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			if (in_array($valCadBusq[4],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "1%", $pageNum, "idCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			} else {
				$htmlTh .= "<td width=\"1%\"></td>";
			}
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "q.fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "q.fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "q.fecha_ultimo_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ult. Pago");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "q.tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "q.numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "25%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "q.saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "q.montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto.");
		$htmlTh .= "</tr>";
	} else {
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			$htmlTh .= ($valCadBusq[5] == 1) ? ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa") : "";
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "idCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "62%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "q.saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "q.montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto.");
		$htmlTh .= "</tr>";
	}
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$arrayDcto = array(
			"id_modulo" => $row['id_modulo'],
			"estadoFactura" => $row['estadoFactura'],
			"tipoDocumentoN" => $row['tipoDocumentoN'],
			"idFactura" => $row['idFactura'],
			"nombre_empresa" => $row['nombre_empresa'],
			"fechaRegistroFactura" => $row['fechaRegistroFactura'],
			"fechaVencimientoFactura" => $row['fechaVencimientoFactura'],
			"fecha_ultimo_pago" => $row['fecha_ultimo_pago'],
			"idEstadoCuenta" => $row['idEstadoCuenta'],
			"tipoDocumento" => $row['tipoDocumento'],
			"numeroFactura" => $row['numeroFactura'],
			"numero_siniestro" => $row['numero_siniestro'],
			"nombre_cliente" => $row['nombre_cliente'],
			"descripcion_concepto_forma_pago" => $row['descripcion_concepto_forma_pago'],
			"observacionFactura" => $row['observacionFactura'],
			"estado_documento" => $row['estado_documento'],
			"abreviacion_moneda_local" => $row['abreviacion_moneda_local'],
			"saldoFactura" => $row['saldoFactura'],
			"montoTotalFactura" => $row['montoTotalFactura']);
		
		$existe = false;
		if (isset($arrayECGeneral)) {
			foreach($arrayECGeneral as $indice => $valor) {
				if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
					//$groupBy = ($valCadBusq[5] == 1) ? "GROUP BY q.id_empresa, q.idCliente" : "GROUP BY q.idCliente";
					if ($valCadBusq[5] == 1) { // 1 = Detallado por Empresa, 2 = Consolidado
						if ($arrayECGeneral[$indice]['id_empresa'] == $row['id_empresa'] && $arrayECGeneral[$indice]['id_cliente'] == $row['idCliente']) {
							$lstTipoDetalle = 1;
							$existe = true;
							
							$arrayDctoAux = $arrayECGeneral[$indice]['arrayDcto'];
							array_push($arrayDctoAux, $arrayDcto);
							$arrayECGeneral[$indice]['arrayDcto'] = $arrayDctoAux;
						}
					} else {
						if ($arrayECGeneral[$indice]['id_cliente'] == $row['idCliente']) {
							$lstTipoDetalle = 2;
							$existe = true;
							
							$arrayDctoAux = $arrayECGeneral[$indice]['arrayDcto'];
							array_push($arrayDctoAux, $arrayDcto);
							$arrayECGeneral[$indice]['arrayDcto'] = $arrayDctoAux;
						}
					}
				} else {
					//$groupBy = "GROUP BY q.id_empresa, vw_cxc_as.idCliente";
					if ($arrayECGeneral[$indice]['id_empresa'] == $row['id_empresa'] && $arrayECGeneral[$indice]['id_cliente'] == $row['idCliente']) {
						$lstTipoDetalle = 1;
						$existe = true;
						
						$arrayDctoAux = $arrayECGeneral[$indice]['arrayDcto'];
						array_push($arrayDctoAux, $arrayDcto);
						$arrayECGeneral[$indice]['arrayDcto'] = $arrayDctoAux;
					}
				}
			}
		}
		
		if ($existe == false) {
			$arrayDctoAux = NULL;
			$arrayDctoAux[] = $arrayDcto;
			if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
				if ($valCadBusq[5] == 1) { // 1 = Detallado por Empresa, 2 = Consolidado
					$arrayECGeneral[] = array(
						"id_empresa" => $row['id_empresa'],
						"nombre_empresa" => $row['nombre_empresa'],
						"id_cliente" => $row['idCliente'],
						"nombre_cliente" => $row['nombre_cliente'],
						"arrayDcto" => $arrayDctoAux);
				} else {
					$arrayECGeneral[] = array(
						"id_cliente" => $row['idCliente'],
						"nombre_cliente" => $row['nombre_cliente'],
						"arrayDcto" => $arrayDctoAux);
				}
			} else {
				$arrayECGeneral[] = array(
					"id_empresa" => $row['id_empresa'],
					"nombre_empresa" => $row['nombre_empresa'],
					"id_cliente" => $row['idCliente'],
					"nombre_cliente" => $row['nombre_cliente'],
					"arrayDcto" => $arrayDctoAux);
			}
		}
	}
	if (isset($arrayECGeneral)) {
		foreach($arrayECGeneral as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$totalSaldoCliente = 0;
			$totalMontoCliente = 0;
			
			$idEmpresa = $valor['id_empresa'];
			$nombreEmpresa = $valor['nombre_empresa'];
			$idCliente = $valor['id_cliente'];
			$nombreCliente = $valor['nombre_cliente'];
			$arrayDcto = $valor['arrayDcto'];
			
			if (in_array($valCadBusq[4],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
				$htmlTb .= ($contFila > 1) ? "<tr height=\"24\"><td>&nbsp;</td></tr>" : "";
				
				$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
					$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px tituloCampo\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".$idCliente."</td>";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">".utf8_encode($nombreCliente)."</td>";
				$htmlTb .= "</tr>";
				
				$contFila2 = 0;
			}
			
			if (isset($arrayDcto)) {
				foreach($arrayDcto as $indice => $valor) {
					// 1 = Factura, 2 = Nota de Débito, 3 = Anticipo, 4 = Nota Credito, 5 = Cheque, 6 = Transferencia
					$signo = (in_array($valor['tipoDocumentoN'],array(1,2))) ? 1 : (-1);
					
					switch($valor['id_modulo']) {
						case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
						case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
						case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
						case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
						case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
						default : $imgPedidoModulo = $valor['id_modulo'];
					}
					
					switch($valor['estadoFactura']) {
						case 0 : $class = "class=\"divMsjError\""; break;
						case 1 : $class = "class=\"divMsjInfo\""; break;
						case 2 : $class = "class=\"divMsjAlerta\""; break;
						case 3 : $class = "class=\"divMsjInfo3\""; break;
						case 4 : $class = "class=\"divMsjInfo4\""; break;
					}
					
					switch ($valor['tipoDocumentoN']) {
						case 1 : // 1 = Factura
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
								$valor['idFactura']);
							switch ($valor['id_modulo']) {
								case 0 : // REPUESTOS
									$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s', 960, 550);",
										$valor['idFactura']);
									break;
								case 1 : // SERVICIOS
									$aVerDctoAux = sprintf("javascript:verVentana('../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s', 960, 550);",
										$valor['idFactura']);
									break;
								case 2 : // VEHICULOS
									$aVerDctoAux = sprintf("javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);",
										$valor['idFactura']);
									break;
								case 3 : // ADMINISTRACION
									$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s', 960, 550);",
										$valor['idFactura']);
									break;
							}
							$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Factura Venta PDF")."\"/></a>" : "";
							break;
						case 2 : // 2 = Nota de Débito
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_nota_debito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Débito")."\"/><a>",
								$valor['idFactura']);
							$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/><a>",
								$valor['idFactura']);
							break;
						case 3 : // 3 = Anticipo
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_anticipo_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Anticipo")."\"/><a>",
								$valor['idFactura']);
							if (in_array($valor['id_modulo'],array(2,4))) {
								$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
									$valor['idFactura']);
							} else if (in_array($valor['id_modulo'],array(0,1,3))) {
								$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
									$valor['idFactura']);
							}
							$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
							break;
						case 4 : // 4 = Nota Credito
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/><a>",
								$valor['idFactura']);
							switch ($valor['id_modulo']) {
								case 0 : // REPUESTOS
									$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
										$valor['idFactura']);
									break;
								case 1 : // SERVICIOS
									$aVerDctoAux = sprintf("javascript:verVentana('../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
										$valor['idFactura']);
									break;
								case 2 : // VEHICULOS
									$aVerDctoAux = sprintf("javascript:verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
										$valor['idFactura']);
									break;
								case 3 : // ADMINISTRACION
									$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
										$valor['idFactura']);
									break;
								default : $aVerDctoAux = "";
							}
							$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
							break;
						case 5 : // 5 = Cheque
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_cheque_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Cheque")."\"/><a>",
								$valor['idFactura']);
							if (in_array($valor['id_modulo'],array(2,4))) {
								$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=5&id=%s', 960, 550);",
									$valor['idFactura']);
							} else if (in_array($valor['id_modulo'],array(0,1,3))) {
								$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=5&id=%s', 960, 550);",
									$valor['idFactura']);
							}
							$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
							break;
						case 6 : // 6 = Transferencia
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_transferencia_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Transferencia")."\"/><a>",
								$valor['idFactura']);
							if (in_array($valor['id_modulo'],array(2,4))) {
								$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=6&id=%s', 960, 550);",
									$valor['idFactura']);
							} else if (in_array($valor['id_modulo'],array(0,1,3))) {
								$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=6&id=%s', 960, 550);",
									$valor['idFactura']);
							}
							$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
							break;
						default : $aVerDcto = "";
					}
					
					if (in_array($valCadBusq[4],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
						$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila2++;
						
						$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
							$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila2) + (($pageNum) * $maxRows))."</td>";
							$htmlTb .= "<td>".$imgPedidoModulo."</td>";
							$htmlTb .= "<td>".utf8_encode($valor['nombre_empresa'])."</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($valor['fechaRegistroFactura']))."</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($valor['fechaVencimientoFactura'] != "") ? date("d-m-Y",strtotime($valor['fechaVencimientoFactura'])) : "-")."</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($valor['fecha_ultimo_pago'] != "") ? date("d-m-Y",strtotime($valor['fecha_ultimo_pago'])) : "-")."</td>";
							$htmlTb .= "<td align=\"center\" title=\"Id Estado Cuenta: ".$valor['idEstadoCuenta']."\">".utf8_encode($valor['tipoDocumento']).(($valor['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
							$htmlTb .= "<td align=\"right\">";
								$htmlTb .= "<table width=\"100%\">";
								$htmlTb .= "<tr>";
									$htmlTb .= "<td align=\"left\" width=\"100%\">".$aVerDcto."</td>";
									$htmlTb .= "<td>".$valor['numeroFactura']."</td>";
								$htmlTb .= "</tr>";
								$htmlTb .= ($valor['numero_siniestro']) ? "<tr><td colspan=\"2\">NRO. SINIESTRO: ".$valor['numero_siniestro']."</td></tr>" : "";
								$htmlTb .= ($dias > 0) ? "<tr><td colspan=\"2\"><span class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</span></td></tr>" : "";
								$htmlTb .= "</table>";
							$htmlTb .= "</td>";
							$htmlTb .= "<td>";
								$htmlTb .= "<table width=\"100%\">";
								$htmlTb .= "<tr>";
									$htmlTb .= "<td width=\"100%\">".utf8_encode($valor['nombre_cliente'])."</td>";
								$htmlTb .= "</tr>";
								$htmlTb .= (strlen($valor['descripcion_concepto_forma_pago']) > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".utf8_encode($valor['descripcion_concepto_forma_pago'])."</span></td></tr>" : "";
								$htmlTb .= (strlen($valor['observacionFactura']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($valor['observacionFactura'])."<span></td></tr>" : "";
								$htmlTb .= "</table>";
							$htmlTb .= "</td>";
							$htmlTb .= "<td align=\"center\" ".$class.">".$valor['estado_documento']."</td>";
							$htmlTb .= "<td align=\"right\">".$valor['abreviacion_moneda_local'].number_format($signo * $valor['saldoFactura'], 2, ".", ",")."</td>";
							$htmlTb .= "<td align=\"right\">".$valor['abreviacion_moneda_local'].number_format($signo * $valor['montoTotalFactura'], 2, ".", ",")."</td>";
						$htmlTb .= "</tr>";
					}
					
					$arrayTotalSaldos[$valor['tipoDocumentoN']]['cant_dctos'] = $arrayTotalSaldos[$valor['tipoDocumentoN']]['cant_dctos'] + 1;
					$arrayTotalSaldos[$valor['tipoDocumentoN']]['saldo_dctos'] = $arrayTotalSaldos[$valor['tipoDocumentoN']]['saldo_dctos'] + ($signo * $valor['saldoFactura']);
					$arrayTotalSaldos[$valor['tipoDocumentoN']]['total_dctos'] = $arrayTotalSaldos[$valor['tipoDocumentoN']]['total_dctos'] + ($signo * $valor['montoTotalFactura']);
					
					$totalSaldoCliente += $signo * $valor['saldoFactura'];
					$totalMontoCliente += $signo * $valor['montoTotalFactura'];
				}
				
				if (in_array($valCadBusq[4],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
					$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
						$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"10\">".utf8_encode($nombreCliente).":</td>";
						$htmlTb .= "<td align=\"right\">".number_format($totalSaldoCliente, 2, ".", ",")."</td>";
						$htmlTb .= "<td align=\"right\">".number_format($totalMontoCliente, 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
				} else if (in_array($valCadBusq[4],array(4))) { // 3 = General por Cliente, 4 = General por Dcto.
				} else {
					$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
						$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
						$htmlTb .= ($valCadBusq[5] == 1) ? "<td>".utf8_encode($nombreEmpresa)."</td>" : "";
						$htmlTb .= "<td align=\"right\">".$idCliente."</td>";
						$htmlTb .= "<td>".utf8_encode($nombreCliente)."</td>";
						$htmlTb .= "<td align=\"right\">".number_format($totalSaldoCliente, 2, ".", ",")."</td>";
						$htmlTb .= "<td align=\"right\">".number_format($totalMontoCliente, 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
				}
				
				$arrayTotal[11] += $totalSaldoCliente;
				$arrayTotal[12] += $totalMontoCliente;
			}
		}
	}
	if ($contFila > 0) {
		if (in_array($valCadBusq[4],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">".utf8_encode("Total Página:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"".(($valCadBusq[5] == 1) ? 4 : 3)."\">".utf8_encode("Total Página:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				// 1 = Factura, 2 = Nota de Débito, 3 = Anticipo, 4 = Nota Credito, 5 = Cheque, 6 = Transferencia
				$signo = (in_array($row['tipoDocumentoN'],array(1,2))) ? 1 : (-1);
				
				$arrayTotalFinal[11] += $signo * $row['saldoFactura'];
				$arrayTotalFinal[12] += $signo * $row['montoTotalFactura'];
			}
			
			if (in_array($valCadBusq[4],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">".utf8_encode("Total de Totales:")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[11], 2, ".", ",")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
			} else {
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"".(($valCadBusq[5] == 1) ? 4 : 3)."\">".utf8_encode("Total de Totales:")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[11], 2, ".", ",")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
			}
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	if ($totalPages > 0) {
		$arrayTotalSaldos = NULL;
		$rs = mysql_query($query);
		while ($row = mysql_fetch_assoc($rs)) {
			// 1 = Factura, 2 = Nota de Débito, 3 = Anticipo, 4 = Nota Credito, 5 = Cheque, 6 = Transferencia
			$signo = (in_array($row['tipoDocumentoN'],array(1,2))) ? 1 : (-1);
			
			$arrayTotalSaldos[$row['tipoDocumentoN']]['cant_dctos'] = $arrayTotalSaldos[$row['tipoDocumentoN']]['cant_dctos'] + 1;
			$arrayTotalSaldos[$row['tipoDocumentoN']]['saldo_dctos'] = $arrayTotalSaldos[$row['tipoDocumentoN']]['saldo_dctos'] + ($signo * $row['saldoFactura']);
			$arrayTotalSaldos[$row['tipoDocumentoN']]['total_dctos'] = $arrayTotalSaldos[$row['tipoDocumentoN']]['total_dctos'] + ($signo * $row['montoTotalFactura']);
		}
	}
	
	$htmlTblIni .= "<tr>";
		$htmlTblIni .= "<td colspan=\"12\">";
			$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"12\">"."Saldos"."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Factura")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Nota de Débito")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Anticipo")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\" height=\"24\">";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos[1]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[1]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[1]['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos[2]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[2]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[2]['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos[3]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[3]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[3]['total_dctos'], 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Nota de Crédito")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Cheque")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Transferencia")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\" height=\"24\">";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[4]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[4]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[4]['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[5]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[5]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[5]['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[6]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[6]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[6]['total_dctos'], 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "</table>";
		$htmlTblIni .= "</td>";
	$htmlTblIni .= "</tr>";
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"13\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaECIndividual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = q.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq1) > 0) ? " AND " : " WHERE ";
		$sqlBusq1 .= $cond.sprintf("cxc_nd.fechaRegistroNotaCargo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cxc_ant.fechaAnticipo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_nc.fechaNotaCredito BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_ch.fecha_cheque BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("cxc_tb.fecha_transferencia BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
	}
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("q.fechaRegistroFactura BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.id_modulo IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.tipoDocumentoN IN (%s)",
			valTpDato($valCadBusq[7], "campo"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.estadoFactura IN (%s)",
			valTpDato($valCadBusq[8], "campo"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.numeroFactura LIKE %s
		OR CONCAT_WS(' ', cliente.lci,	cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11)) LIKE %s
		OR q.observacionFactura LIKE %s)",
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"));
	}
	
	$query = sprintf("SELECT q.*,
		CONCAT_WS(' ', cliente.lci,	cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		
		IF (q.tipoDocumento IN ('FA') AND q.id_modulo IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = q.idFactura))) 
			, NULL) AS numero_siniestro,
		
		IF (q.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) AS descripcion_concepto_forma_pago,
		
		(CASE
			WHEN (q.tipoDocumento IN ('FA','ND')) THEN
				(CASE q.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END)
			WHEN (q.tipoDocumento IN ('AN','NC','CH','TB')) THEN
				(CASE q.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
					WHEN 4 THEN 'No Cancelado (Asignado)'
				END)
		END) AS estado_documento,
		
		(CASE q.tipoDocumento
			WHEN ('FA') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.estatus IN (1)
						
					UNION
		
					SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_factura = q.idFactura)
			WHEN ('ND') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.idNotaCargo, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.idNotaCargo = q.idFactura)
			WHEN ('AN') THEN
				(SELECT MAX(q2.fechaPagoAnticipo)
				FROM (SELECT cxc_pago.idAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.idAnticipo = q.idFactura)
			WHEN ('NC') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroControlDetalleAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (8)
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.numeroDocumento = q.idFactura)
			WHEN ('CH') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_cheque = q.idFactura)
			WHEN ('TB') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_transferencia = q.idFactura)
		END) AS fecha_ultimo_pago,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM (SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_fact.idFactura,
				cxc_fact.id_empresa,
				cxc_fact.fechaRegistroFactura,
				cxc_fact.fechaVencimientoFactura,
				cxc_fact.numeroFactura,
				cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
				cxc_fact.idCliente,
				cxc_fact.estadoFactura,
				cxc_fact.observacionFactura,
				cxc_fact.montoTotalFactura,
				cxc_fact.saldoFactura
			FROM cj_cc_encabezadofactura cxc_fact
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 1) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_nd.idNotaCargo,
				cxc_nd.id_empresa,
				cxc_nd.fechaRegistroNotaCargo,
				cxc_nd.fechaVencimientoNotaCargo,
				cxc_nd.numeroNotaCargo,
				cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
				cxc_nd.idCliente,
				cxc_nd.estadoNotaCargo,
				cxc_nd.observacionNotaCargo,
				cxc_nd.montoTotalNotaCargo,
				cxc_nd.saldoNotaCargo
			FROM cj_cc_notadecargo cxc_nd
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nd.idNotaCargo = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 2) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_ant.idAnticipo,
				cxc_ant.id_empresa,
				cxc_ant.fechaAnticipo,
				NULL AS fechaVencimientoFactura,
				cxc_ant.numeroAnticipo,
				cxc_ant.idDepartamento AS id_modulo,
				cxc_ant.idCliente,
				cxc_ant.estadoAnticipo,
				cxc_ant.observacionesAnticipo,
				cxc_ant.montoNetoAnticipo,
				cxc_ant.saldoAnticipo
			FROM cj_cc_anticipo cxc_ant
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ant.idAnticipo = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 3) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_nc.idNotaCredito,
				cxc_nc.id_empresa,
				cxc_nc.fechaNotaCredito,
				NULL AS fechaVencimientoFactura,
				cxc_nc.numeracion_nota_credito,
				cxc_nc.idDepartamentoNotaCredito,
				cxc_nc.idCliente,
				cxc_nc.estadoNotaCredito,
				cxc_nc.observacionesNotaCredito,
				cxc_nc.montoNetoNotaCredito,
				cxc_nc.saldoNotaCredito
			FROM cj_cc_notacredito cxc_nc
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nc.idNotaCredito = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 4) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_ch.id_cheque,
				cxc_ch.id_empresa,
				cxc_ch.fecha_cheque,
				NULL AS fechaVencimientoFactura,
				cxc_ch.numero_cheque,
				cxc_ch.id_departamento,
				cxc_ch.id_cliente,
				cxc_ch.estado_cheque,
				cxc_ch.observacion_cheque,
				cxc_ch.monto_neto_cheque,
				cxc_ch.saldo_cheque
			FROM cj_cc_cheque cxc_ch
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ch.id_cheque = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 5) %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_tb.id_transferencia,
				cxc_tb.id_empresa,
				cxc_tb.fecha_transferencia,
				NULL AS fechaVencimientoFactura,
				cxc_tb.numero_transferencia,
				cxc_tb.id_departamento,
				cxc_tb.id_cliente,
				cxc_tb.estado_transferencia,
				cxc_tb.observacion_transferencia,
				cxc_tb.monto_neto_transferencia,
				cxc_tb.saldo_transferencia
			FROM cj_cc_transferencia cxc_tb
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_tb.id_transferencia = cxc_ec.idDocumento AND cxc_ec.tipoDocumentoN = 6) %s) AS q
		INNER JOIN cj_cc_cliente cliente ON (q.idCliente = cliente.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (q.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq, $sqlBusq1, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "q.fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "q.fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "q.fecha_ultimo_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ult. Pago");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "q.tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "q.numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "26%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "q.saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "q.montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto.");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		// 1 = Factura, 2 = Nota de Débito, 3 = Anticipo, 4 = Nota Credito, 5 = Cheque, 6 = Transferencia
		$signo = (in_array($row['tipoDocumentoN'],array(1,2))) ? 1 : (-1);
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		switch($row['estadoFactura']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		switch ($row['tipoDocumentoN']) {
			case 1 : // 1 = Factura
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
					$row['idFactura']);
				switch ($row['id_modulo']) {
					case 0 : // REPUESTOS
						$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s', 960, 550);",
							$row['idFactura']);
						break;
					case 1 : // SERVICIOS
						$aVerDctoAux = sprintf("javascript:verVentana('../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s', 960, 550);",
							$row['idFactura']);
						break;
					case 2 : // VEHICULOS
						$aVerDctoAux = sprintf("javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);",
							$row['idFactura']);
						break;
					case 3 : // ADMINISTRACION
						$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s', 960, 550);",
							$row['idFactura']);
						break;
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Factura Venta PDF")."\"/></a>" : "";
				break;
			case 2 : // 2 = Nota de Débito
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_nota_debito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Débito")."\"/><a>",
					$row['idFactura']);
				$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/><a>",
					$row['idFactura']);
				break;
			case 3 : // 3 = Anticipo
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_anticipo_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Anticipo")."\"/><a>",
					$row['idFactura']);
				if (in_array($row['id_modulo'],array(2,4))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
						$row['idFactura']);
				} else if (in_array($row['id_modulo'],array(0,1,3))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
						$row['idFactura']);
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
				break;
			case 4 : // 4 = Nota Credito
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/><a>",
					$row['idFactura']);
				switch ($row['id_modulo']) {
					case 0 : // REPUESTOS
						$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
							$row['idFactura']);
						break;
					case 1 : // SERVICIOS
						$aVerDctoAux = sprintf("javascript:verVentana('../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
							$row['idFactura']);
						break;
					case 2 : // VEHICULOS
						$aVerDctoAux = sprintf("javascript:verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
							$row['idFactura']);
						break;
					case 3 : // ADMINISTRACION
						$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
							$row['idFactura']);
						break;
					default : $aVerDctoAux = "";
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
				break;
			case 5 : // 5 = Cheque
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_cheque_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Cheque")."\"/><a>",
					$row['idFactura']);
				if (in_array($row['id_modulo'],array(2,4))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=5&id=%s', 960, 550);",
						$row['idFactura']);
				} else if (in_array($row['id_modulo'],array(0,1,3))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=5&id=%s', 960, 550);",
						$row['idFactura']);
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
				break;
			case 6 : // 6 = Transferencia
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_transferencia_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Transferencia")."\"/><a>",
					$row['idFactura']);
				if (in_array($row['id_modulo'],array(2,4))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=6&id=%s', 960, 550);",
						$row['idFactura']);
				} else if (in_array($row['id_modulo'],array(0,1,3))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=6&id=%s', 960, 550);",
						$row['idFactura']);
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
				break;
			default : $aVerDcto = "";
		}
				
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($row['fechaVencimientoFactura'] != "") ? date("d-m-Y",strtotime($row['fechaVencimientoFactura'])) : "-")."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($row['fecha_ultimo_pago'] != "") ? date("d-m-Y",strtotime($row['fecha_ultimo_pago'])) : "-")."</td>";
			$htmlTb .= "<td align=\"center\" title=\"Id Estado Cuenta: ".$row['idEstadoCuenta']."\">".utf8_encode($row['tipoDocumento']).(($row['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"left\" width=\"100%\">".$aVerDcto."</td>";
					$htmlTb .= "<td>".$row['numeroFactura']."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= ($row['numero_siniestro']) ? "<tr><td colspan=\"2\">NRO. SINIESTRO: ".$row['numero_siniestro']."</td></tr>" : "";
				$htmlTb .= ($dias > 0) ? "<tr><td colspan=\"2\"><span class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</span></td></tr>" : "";
				$htmlTb .= (strlen($row['observacionFactura']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionFactura'])."<span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['estado_documento']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($signo * $row['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($signo * $row['montoTotalFactura'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotalSaldos[$row['tipoDocumentoN']]['cant_dctos'] = $arrayTotalSaldos[$row['tipoDocumentoN']]['cant_dctos'] + 1;
		$arrayTotalSaldos[$row['tipoDocumentoN']]['saldo_dctos'] = $arrayTotalSaldos[$row['tipoDocumentoN']]['saldo_dctos'] + ($signo * $row['saldoFactura']);
		$arrayTotalSaldos[$row['tipoDocumentoN']]['total_dctos'] = $arrayTotalSaldos[$row['tipoDocumentoN']]['total_dctos'] + ($signo * $row['montoTotalFactura']);
		
		$arrayTotal[11] += $signo * $row['saldoFactura'];
		$arrayTotal[12] += $signo * $row['montoTotalFactura'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">".utf8_encode("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				// 1 = Factura, 2 = Nota de Débito, 3 = Anticipo, 4 = Nota Credito, 5 = Cheque, 6 = Transferencia
				$signo = (in_array($row['tipoDocumentoN'],array(1,2))) ? 1 : (-1);
				
				$arrayTotalFinal[11] += $signo * $row['saldoFactura'];
				$arrayTotalFinal[12] += $signo * $row['montoTotalFactura'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">".utf8_encode("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	if ($totalPages > 0) {
		$arrayTotalSaldos = NULL;
		$rs = mysql_query($query);
		while ($row = mysql_fetch_assoc($rs)) {
			// 1 = Factura, 2 = Nota de Débito, 3 = Anticipo, 4 = Nota Credito, 5 = Cheque, 6 = Transferencia
			$signo = (in_array($row['tipoDocumentoN'],array(1,2))) ? 1 : (-1);
			
			$arrayTotalSaldos[$row['tipoDocumentoN']]['cant_dctos'] = $arrayTotalSaldos[$row['tipoDocumentoN']]['cant_dctos'] + 1;
			$arrayTotalSaldos[$row['tipoDocumentoN']]['saldo_dctos'] = $arrayTotalSaldos[$row['tipoDocumentoN']]['saldo_dctos'] + ($signo * $row['saldoFactura']);
			$arrayTotalSaldos[$row['tipoDocumentoN']]['total_dctos'] = $arrayTotalSaldos[$row['tipoDocumentoN']]['total_dctos'] + ($signo * $row['montoTotalFactura']);
		}
	}
	
	$htmlTblIni .= "<tr>";
		$htmlTblIni .= "<td colspan=\"12\">";
			$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"12\">"."Saldos"."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Factura")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Nota de Débito")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Anticipo")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\" height=\"24\">";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos[1]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[1]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[1]['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos[2]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[2]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[2]['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos[3]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[3]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos[3]['total_dctos'], 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Nota de Crédito")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Cheque")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Transferencia")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\" height=\"24\">";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[4]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[4]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[4]['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[5]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[5]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[5]['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[6]['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[6]['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos[6]['total_dctos'], 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "</table>";
		$htmlTblIni .= "</td>";
	$htmlTblIni .= "</tr>";
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"13\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoFactura");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"cargarTipoDocumento");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaECGeneral");
$xajax->register(XAJAX_FUNCTION,"listaECIndividual");
?>