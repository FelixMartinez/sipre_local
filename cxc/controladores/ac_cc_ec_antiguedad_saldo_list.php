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

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarEstadoCuenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdCliente'],
		$frmBuscar['txtFecha'],
		$frmBuscar['radioOpcion'],
		$frmBuscar['lstTipoDetalle'],
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"),
		((count($frmBuscar['cbxDcto']) > 0) ? implode(",",$frmBuscar['cbxDcto']) : "-1"),
		((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
		$frmBuscar['txtCriterio']);
	
	switch ($frmBuscar['radioOpcion']) {
		case 1 : $objResponse->loadCommands(listaECIndividual(0, "CONCAT(vw_cxc_as.fechaRegistroFactura, vw_cxc_as.idEstadoCuenta)", "DESC", $valBusq)); break;
		case 2 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
		case 3 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
		case 4 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
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

function cargarDiasVencidos(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM gruposestadocuenta");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"corriente\"/> Cta. Corriente</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde1\"/> De ".$row['desde1']." a ".$row['hasta1']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde2\"/> De ".$row['desde2']." a ".$row['hasta2']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde3\"/> De ".$row['desde3']." a ".$row['hasta3']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"masDe\"/> Mas de ".$row['masDe']."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdDiasVencidos","innerHTML",$html);
	
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

function exportarAntiguedadSaldo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdCliente'],
		$frmBuscar['txtFecha'],
		$frmBuscar['radioOpcion'],
		$frmBuscar['lstTipoDetalle'],
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"),
		((count($frmBuscar['cbxDcto']) > 0) ? implode(",",$frmBuscar['cbxDcto']) : "-1"),
		((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cc_antiguedad_saldo_excel.php?valBusq=".$valBusq."','_self');");
	
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
	
	$queryGrupoEstado = "SELECT * FROM gruposestadocuenta";
	$rsGrupoEstado = mysql_query($queryGrupoEstado);
	if (!$rsGrupoEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGrupoEstado = mysql_fetch_array($rsGrupoEstado);
				
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_as.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxc_as.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxc_as.fechaRegistroFactura <= %s",
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
	
	if (strtotime($valCadBusq[2]) >= strtotime(date("d-m-Y"))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				(((SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
					WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
						AND cxc_ant.estatus IN (1)) = 1
					AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
				
				OR
				
				(SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
				WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
					AND cxc_ant.montoNetoAnticipo > cxc_ant.totalPagadoAnticipo
					AND cxc_ant.estadoAnticipo IN (4)
					AND cxc_ant.estatus IN (1)) = 1)
			ELSE
				ROUND(vw_cxc_as.saldoFactura, 2) > 0
		END))");
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
				(CASE
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
						(vw_cxc_as.montoTotal
							- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4)) THEN
						(vw_cxc_as.montoTotal
							- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
				END)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
			
			WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (2)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (4)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
		END) > 0
			AND NOT ((CASE
					WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
						(CASE
							WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
								(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s)))
							WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4)) THEN
								(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s)))
						END)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
						(SELECT MAX(cxc_pago.fechaPago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s)))
						
					WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
						WHERE q.numeroDocumento = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
						WHERE q.numeroDocumento = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
						WHERE q.id_cheque = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
						WHERE q.id_transferencia = vw_cxc_as.idFactura)
				END) < %s
					AND ((vw_cxc_as.tipoDocumento IN ('FA','ND') AND vw_cxc_as.estadoFactura IN (1))
						OR (vw_cxc_as.tipoDocumento IN ('AN','NC') AND vw_cxc_as.estadoFactura IN (3)))))",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		// 1 = Detallado por Empresa, 2 = Consolidado
		$groupBy = ($valCadBusq[4] == 1) ? "GROUP BY vw_cxc_as.id_empresa, vw_cxc_as.idCliente" : "GROUP BY vw_cxc_as.idCliente";
	} else {
		$groupBy = "GROUP BY vw_cxc_as.id_empresa, vw_cxc_as.idCliente";
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.tipoDocumentoN IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde1",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde2",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde3",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("masDe",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_as.numeroFactura LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_cxc_as.*,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cc_antiguedad_saldo vw_cxc_as
		INNER JOIN cj_cc_cliente cliente ON (vw_cxc_as.idCliente = cliente.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $groupBy);
	
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
	if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"".((in_array($valCadBusq[3],array(3))) ? 9 : 10)."\"></td>";
			$htmlTh .= "<td colspan=\"4\">D&iacute;as Vencidos</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "1%", $pageNum, "idCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			} else {
				$htmlTh .= "<td width=\"1%\"></td>";
			}
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
			if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "11%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "11%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
				$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
				$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
				$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
				$htmlTh .= "<td width=\"9%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
			} else {
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
				$htmlTh .= "<td width=\"7%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
				$htmlTh .= "<td width=\"7%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
				$htmlTh .= "<td width=\"7%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
				$htmlTh .= "<td width=\"7%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
			}
		$htmlTh .= "</tr>";
	} else {
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"".(($valCadBusq[4] == 1) ? 6 : 5)."\"></td>";
			$htmlTh .= "<td colspan=\"4\">D&iacute;as Vencidos</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			$htmlTh .= ($valCadBusq[4] == 1) ? ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa") : "";
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "idCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "20%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "11%", $pageNum, "SUM(saldoFactura)", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "11%", $pageNum, "SUM(saldoFactura)", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
			$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
			$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
			$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
			$htmlTh .= "<td width=\"8%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
		$htmlTh .= "</tr>";
	}
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$totalSaldoCliente = 0;
		$totalCorrienteCliente = 0;
		$totalEntre1Cliente = 0;
		$totalEntre2Cliente = 0;
		$totalEntre3Cliente = 0;
		$totalMasDeCliente = 0;
		
		$sqlBusq2 = "";
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			if ($valCadBusq[4] == 1) { // 1 = Detallado por Empresa, 2 = Consolidado
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.id_empresa = %s)",
					valTpDato($row['id_empresa'], "int"));
			} else {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = vw_cxc_as.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
			}
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.id_empresa = %s)",
				valTpDato($row['id_empresa'], "int"));
		}
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxc_as.idCliente = %s",
			valTpDato($row['idCliente'], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxc_as.fechaRegistroFactura <= %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		
		if (strtotime($valCadBusq[2]) >= strtotime(date("d-m-Y"))) {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					(((SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
						WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
							AND cxc_ant.estatus IN (1)) = 1
						AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
					
					OR
					
					(SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
					WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
						AND cxc_ant.montoNetoAnticipo > cxc_ant.totalPagadoAnticipo
						AND cxc_ant.estadoAnticipo IN (4)
						AND cxc_ant.estatus IN (1)) = 1)
				ELSE
					ROUND(vw_cxc_as.saldoFactura, 2) > 0
			END))");
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("((CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
					(CASE
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
							(vw_cxc_as.montoTotal
								- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
										WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
											AND cxc_pago.fechaPago <= %s
											AND (cxc_pago.estatus IN (1)
												OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4)) THEN
							(vw_cxc_as.montoTotal
								- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
										WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
											AND cxc_pago.fechaPago <= %s
											AND (cxc_pago.estatus IN (1)
												OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
					END)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (2)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (2)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago IN (2)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (2)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (4)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (4)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago IN (4)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (4)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
			END) > 0
				AND NOT ((CASE
						WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
							(CASE
								WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
									(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s)))
								WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4)) THEN
									(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s)))
							END)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
							(SELECT MAX(cxc_pago.fechaPago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) 
							
						WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago = 7
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago = 7
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago = 7
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
							WHERE q.numeroDocumento = vw_cxc_as.idFactura)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago = 8
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago = 8
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago = 8
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
							WHERE q.numeroDocumento = vw_cxc_as.idFactura)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
									WHERE cxc_pago.id_forma_pago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
							WHERE q.id_cheque = vw_cxc_as.idFactura)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
									UNION
									
									SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
									WHERE cxc_pago.id_forma_pago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
							WHERE q.id_transferencia = vw_cxc_as.idFactura)
					END) < %s
						AND ((vw_cxc_as.tipoDocumento IN ('FA','ND') AND vw_cxc_as.estadoFactura IN (1))
							OR (vw_cxc_as.tipoDocumento IN ('AN','NC') AND vw_cxc_as.estadoFactura IN (3)))))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
			
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxc_as.idDepartamentoOrigenFactura IN (%s)",
				valTpDato($valCadBusq[5], "campo"));
		}
		
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxc_as.tipoDocumentoN IN (%s)",
				valTpDato($valCadBusq[6], "campo"));
		}
	
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$arrayDiasVencidos = NULL;
			if (in_array("corriente",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde1",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde2",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde3",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("masDe",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
			}
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
		}
		
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.numeroFactura LIKE %s
			OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"));
		}
		
		$queryEstado = sprintf("SELECT
			vw_cxc_as.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			
			IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
				(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
				WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
														WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																							WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
				, NULL) AS numero_siniestro,
			
			IF (vw_cxc_as.tipoDocumento IN ('AN'),
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
					AND cxc_pago.id_forma_pago IN (11))
				, NULL) AS descripcion_concepto_forma_pago,
			
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
					(CASE
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
							IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4)) THEN
							IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					END)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (2)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (4)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
			END) AS total_pagos,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cc_antiguedad_saldo vw_cxc_as
			INNER JOIN cj_cc_cliente cliente ON (vw_cxc_as.idCliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			$sqlBusq2);
		$rsEstado = mysql_query($queryEstado);
		if (!$rsEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsEstado = mysql_num_rows($rsEstado);
		
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= ($contFila > 1) ? "<tr height=\"24\"><td>&nbsp;</td></tr>" : "";
			
			$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px tituloCampo\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".$row['idCliente']."</td>";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "</tr>";
			
			$contFila2 = 0;
		}
		
		while ($rowEstado = mysql_fetch_array($rsEstado)) {
			$totalSaldo = 0;
			$totalCorriente = 0;
			$totalEntre1 = 0;
			$totalEntre2 = 0;
			$totalEntre3 = 0;
			$totalMasDe = 0;
			
			$fecha1 = strtotime($valCadBusq[2]);
			$fecha2 = strtotime($rowEstado['fechaVencimientoFactura']);
			
			$dias = ($fecha1 - $fecha2) / 86400;
			
			switch($rowEstado['idDepartamentoOrigenFactura']) {
				case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
				case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
				case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
				case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
				case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
				default : $imgPedidoModulo = $rowEstado['idDepartamentoOrigenFactura'];
			}
			
			switch ($rowEstado['tipoDocumentoN']) {
				case 1 : // 1 = Factura
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
						$rowEstado['idFactura']);
					switch ($rowEstado['idDepartamentoOrigenFactura']) {
						case 0 : // REPUESTOS
							$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s', 960, 550);",
								$rowEstado['idFactura']);
							break;
						case 1 : // SERVICIOS
							$aVerDctoAux = sprintf("javascript:verVentana('../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s', 960, 550);",
								$rowEstado['idFactura']);
							break;
						case 2 : // VEHICULOS
							$aVerDctoAux = sprintf("javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);",
								$rowEstado['idFactura']);
							break;
						case 3 : // ADMINISTRACION
							$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s', 960, 550);",
								$rowEstado['idFactura']);
							break;
					}
					$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Factura Venta PDF")."\"/></a>" : "";
					break;
				case 2 : // 2 = Nota de Débito
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_nota_debito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Débito")."\"/><a>",
						$rowEstado['idFactura']);
					$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/><a>",
						$rowEstado['idFactura']);
					break;
				case 3 : // 3 = Anticipo
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_anticipo_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Anticipo")."\"/><a>",
						$rowEstado['idFactura']);
					if (in_array($rowEstado['idDepartamentoOrigenFactura'],array(2,4))) {
						$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
							$rowEstado['idFactura']);
					} else if (in_array($rowEstado['idDepartamentoOrigenFactura'],array(0,1,3))) {
						$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
							$rowEstado['idFactura']);
					}
					$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
					break;
				case 4 : // 4 = Nota Credito
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/><a>",
						$rowEstado['idFactura']);
					switch ($rowEstado['idDepartamentoOrigenFactura']) {
						case 0 : // REPUESTOS
							$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
								$rowEstado['idFactura']);
							break;
						case 1 : // SERVICIOS
							$aVerDctoAux = sprintf("javascript:verVentana('../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
								$rowEstado['idFactura']);
							break;
						case 2 : // VEHICULOS
							$aVerDctoAux = sprintf("javascript:verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
								$rowEstado['idFactura']);
							break;
						case 3 : // ADMINISTRACION
							$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
								$rowEstado['idFactura']);
							break;
						default : $aVerDctoAux = "";
					}
					$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
					break;
				case 5 : // 5 = Cheque
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_cheque_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Cheque")."\"/><a>",
						$rowEstado['idFactura']);
					if (in_array($rowEstado['idDepartamentoOrigenFactura'],array(2,4))) {
						$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=5&id=%s', 960, 550);",
							$rowEstado['idFactura']);
					} else if (in_array($rowEstado['idDepartamentoOrigenFactura'],array(0,1,3))) {
						$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=5&id=%s', 960, 550);",
							$rowEstado['idFactura']);
					}
					$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
					break;
				case 6 : // 6 = Transferencia
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_transferencia_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Transferencia")."\"/><a>",
						$rowEstado['idFactura']);
					if (in_array($rowEstado['idDepartamentoOrigenFactura'],array(2,4))) {
						$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=6&id=%s', 960, 550);",
							$rowEstado['idFactura']);
					} else if (in_array($rowEstado['idDepartamentoOrigenFactura'],array(0,1,3))) {
						$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=6&id=%s', 960, 550);",
							$rowEstado['idFactura']);
					}
					$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
					break;
				default : $aVerDcto = "";
			}
			
			if (in_array($rowEstado['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
				$totalSaldo += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
			} else if (in_array($rowEstado['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
				$totalSaldo -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
			}
			
			if ($dias < $rowGrupoEstado['desde1']){
				if (in_array($rowEstado['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
					$totalCorriente += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
					$totalCorriente -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde1']) && ($dias <= $rowGrupoEstado['hasta1'])){
				if (in_array($rowEstado['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
					$totalEntre1 += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
					$totalEntre1 -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde2']) && ($dias <= $rowGrupoEstado['hasta2'])){
				if (in_array($rowEstado['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
					$totalEntre2 += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
					$totalEntre2 -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde3']) && ($dias <= $rowGrupoEstado['hasta3'])){
				if (in_array($rowEstado['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
					$totalEntre3 += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
					$totalEntre3 -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else {
				if (in_array($rowEstado['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
					$totalMasDe += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
					$totalMasDe -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			}
			
			if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
				$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila2++;
				
				$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
					$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila2) + (($pageNum) * $maxRows))."</td>";
					$htmlTb .= "<td align=\"center\">".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($rowEstado['nombre_empresa'])."</td>";
					$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($rowEstado['fechaRegistroFactura']))."</td>";
					$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($rowEstado['fechaVencimientoFactura']))."</td>";
					$htmlTb .= "<td align=\"center\" title=\"Id Estado Cuenta: ".$rowEstado['idEstadoCuenta']."\">".utf8_encode($rowEstado['tipoDocumento']).(($rowEstado['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
					$htmlTb .= "<td align=\"right\">";
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr>";
							$htmlTb .= "<td align=\"left\" width=\"100%\">".$aVerDcto."</td>";
							$htmlTb .= "<td>".$rowEstado['numeroFactura']."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= ($rowEstado['numero_siniestro']) ? "<tr><td colspan=\"2\">NRO. SINIESTRO: ".$rowEstado['numero_siniestro']."</td></tr>" : "";
						$htmlTb .= ($dias > 0) ? "<tr><td colspan=\"2\"><span class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</span></td></tr>" : "";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
					} else {
						$htmlTb .= "<td>".utf8_encode($rowEstado['nombre_cliente'])."</td>";
					}
					$htmlTb .= "<td align=\"right\">".number_format($totalSaldo, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalCorriente, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalEntre1, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalEntre2, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalEntre3, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalMasDe, 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
			}
			
			$totalSaldoCliente += $totalSaldo;
			$totalCorrienteCliente += $totalCorriente;
			$totalEntre1Cliente += $totalEntre1;
			$totalEntre2Cliente += $totalEntre2;
			$totalEntre3Cliente += $totalEntre3;
			$totalMasDeCliente += $totalMasDe;
		}
		
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"7\">".utf8_encode($row['nombre_cliente']).":</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalSaldoCliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalCorrienteCliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre1Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre2Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre3Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalMasDeCliente, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else if (in_array($valCadBusq[3],array(4))) { // 3 = General por Cliente, 4 = General por Dcto.
		} else {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
				$htmlTb .= ($valCadBusq[4] == 1) ? "<td>".utf8_encode($row['nombre_empresa'])."</td>" : "";
				$htmlTb .= "<td align=\"right\">".$row['idCliente']."</td>";
				$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalSaldoCliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalCorrienteCliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre1Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre2Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre3Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalMasDeCliente, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotal[4] += $totalSaldoCliente;
		$arrayTotal[5] += $totalCorrienteCliente;
		$arrayTotal[6] += $totalEntre1Cliente;
		$arrayTotal[7] += $totalEntre2Cliente;
		$arrayTotal[8] += $totalEntre3Cliente;
		$arrayTotal[9] += $totalMasDeCliente;
	}
	if ($contFila > 0) {
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"7\">Totales:</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[4], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else if (in_array($valCadBusq[3],array(4))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">Totales:</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[4], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"".(($valCadBusq[4] == 1) ? 4 : 3)."\">Totales:</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[4], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
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
	
	$queryGrupoEstado = "SELECT * FROM gruposestadocuenta";
	$rsGrupoEstado = mysql_query($queryGrupoEstado);
	if (!$rsGrupoEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGrupoEstado = mysql_fetch_array($rsGrupoEstado);
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_as.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxc_as.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxc_as.fechaRegistroFactura <= %s",
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
	
	if (strtotime($valCadBusq[2]) >= strtotime(date("d-m-Y"))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				(((SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
					WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
						AND cxc_ant.estatus IN (1)) = 1
					AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
				
				OR
				
				(SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
				WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
					AND cxc_ant.montoNetoAnticipo > cxc_ant.totalPagadoAnticipo
					AND cxc_ant.estadoAnticipo IN (4)
					AND cxc_ant.estatus IN (1)) = 1)
			ELSE
				ROUND(vw_cxc_as.saldoFactura, 2) > 0
		END))");
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
				(CASE
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
						(vw_cxc_as.montoTotal
							- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4)) THEN
						(vw_cxc_as.montoTotal
							- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
				END)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (2)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (4)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0))
		END) > 0
			AND NOT ((CASE
					WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
						(CASE
							WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
								(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s)))
							WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4)) THEN
								(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s)))
						END)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
						(SELECT MAX(cxc_pago.fechaPago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s)))
						
					WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
						WHERE q.numeroDocumento = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
						WHERE q.numeroDocumento = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
						WHERE q.id_cheque = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))) AS q
						WHERE q.id_transferencia = vw_cxc_as.idFactura)
				END) < %s
					AND ((vw_cxc_as.tipoDocumento IN ('FA','ND') AND vw_cxc_as.estadoFactura IN (1))
						OR (vw_cxc_as.tipoDocumento IN ('AN','NC') AND vw_cxc_as.estadoFactura IN (3)))))",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.tipoDocumentoN IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde1",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde2",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde3",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("masDe",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_as.numeroFactura LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_cxc_as.*,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		
		IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
			, NULL) AS numero_siniestro,
		
		IF (vw_cxc_as.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) AS descripcion_concepto_forma_pago,
		
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
				(CASE
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
						IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4)) THEN
						IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				END)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (2)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (4)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) >= %s))),0)
		END) AS total_pagos,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cc_antiguedad_saldo vw_cxc_as
		INNER JOIN cj_cc_cliente cliente ON (vw_cxc_as.idCliente = cliente.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s",
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
		$sqlBusq);
	
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
		$htmlTh .= "<td colspan=\"9\"></td>";
		$htmlTh .= "<td colspan=\"4\">D&iacute;as Vencidos</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "14%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
		$htmlTh .= "<td width=\"8%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
		$htmlTh .= "<td width=\"8%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
		$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
		$htmlTh .= "<td width=\"9%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$totalSaldo = 0;
		$totalCorriente = 0;
		$totalEntre1 = 0;
		$totalEntre2 = 0;
		$totalEntre3 = 0;
		$totalMasDe = 0;
		
		$fecha1 = strtotime($valCadBusq[2]);
		$fecha2 = strtotime($row['fechaVencimientoFactura']);
		
		$dias = ($fecha1 - $fecha2) / 86400;
		
		switch($row['idDepartamentoOrigenFactura']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['idDepartamentoOrigenFactura'];
		}
		
		switch ($row['tipoDocumentoN']) {
			case 1 : // 1 = Factura
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
					$row['idFactura']);
				switch ($row['idDepartamentoOrigenFactura']) {
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
				if (in_array($row['idDepartamentoOrigenFactura'],array(2,4))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
						$row['idFactura']);
				} else if (in_array($row['idDepartamentoOrigenFactura'],array(0,1,3))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
						$row['idFactura']);
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
				break;
			case 4 : // 4 = Nota Credito
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/><a>",
					$row['idFactura']);
				switch ($row['idDepartamentoOrigenFactura']) {
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
				if (in_array($row['idDepartamentoOrigenFactura'],array(2,4))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=5&id=%s', 960, 550);",
						$row['idFactura']);
				} else if (in_array($row['idDepartamentoOrigenFactura'],array(0,1,3))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=5&id=%s', 960, 550);",
						$row['idFactura']);
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
				break;
			case 6 : // 6 = Transferencia
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"cc_transferencia_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Transferencia")."\"/><a>",
					$row['idFactura']);
				if (in_array($row['idDepartamentoOrigenFactura'],array(2,4))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=6&id=%s', 960, 550);",
						$row['idFactura']);
				} else if (in_array($row['idDepartamentoOrigenFactura'],array(0,1,3))) {
					$aVerDctoAux = sprintf("javascript:verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=6&id=%s', 960, 550);",
						$row['idFactura']);
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
				break;
			default : $aVerDcto = "";
		}
		
		if (in_array($row['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
			$totalSaldo += $row['montoTotal'] - $row['total_pagos'];
		} else if (in_array($row['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
			$totalSaldo -= $row['montoTotal'] - $row['total_pagos'];
		}
		
		if ($dias < $rowGrupoEstado['desde1']){
			if (in_array($row['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
				$totalCorriente += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
				$totalCorriente -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde1'] && $dias <= $rowGrupoEstado['hasta1']){
			if (in_array($row['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
				$totalEntre1 += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
				$totalEntre1 -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde2'] && $dias <= $rowGrupoEstado['hasta2']){
			if (in_array($row['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
				$totalEntre2 += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
				$totalEntre2 -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde3'] && $dias <= $rowGrupoEstado['hasta3']){
			if (in_array($row['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
				$totalEntre3 += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
				$totalEntre3 -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else {
			if (in_array($row['tipoDocumentoN'],array(1,2))) { // 1 = Factura, 2 = Nota de Débito
				$totalMasDe += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumentoN'],array(3,4,5,6))){ // 3 = Anticipo, 4 = Nota de Crédito, 5 = Cheque, 6 = Transferencia
				$totalMasDe -= $row['montoTotal'] - $row['total_pagos'];
			}
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['fechaVencimientoFactura']))."</td>";
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
			$htmlTb .= "<td align=\"right\">".number_format($totalSaldo, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalCorriente, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalEntre1, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalEntre2, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalEntre3, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalMasDe, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[8] += $totalSaldo;
		$arrayTotal[9] += $totalCorriente;
		$arrayTotal[10] += $totalEntre1;
		$arrayTotal[11] += $totalEntre2;
		$arrayTotal[12] += $totalEntre3;
		$arrayTotal[13] += $totalMasDe;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"7\">Totales:</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
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
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"cargarDiasVencidos");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"cargarTipoDocumento");
$xajax->register(XAJAX_FUNCTION,"exportarAntiguedadSaldo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaECGeneral");
$xajax->register(XAJAX_FUNCTION,"listaECIndividual");
?>