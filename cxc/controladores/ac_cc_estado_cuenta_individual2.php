<?php
function asignarClientes($idCliente){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM cj_cc_cliente WHERE id = '".$idCliente."'";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rows = mysql_fetch_array($rs);
	
	$objResponse->assign("txtCedulaRifCliente","value",$rows['lci']."-".$rows['ci']);
	$objResponse->assign("txtCodigoCliente","value",$rows['id']);
	if ($rows['nit'] != "" ){
		$objResponse->assign("txtNITCliente","value",$rows['nit']);
	} else {
		$objResponse->assign("txtNITCliente","value",'N/A');		
	}
	$objResponse->assign("txtTelefonoCliente","value",$rows['telf']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rows['nombre']." ".$rows['apellido']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rows['direccion']));
	
	$objResponse->assign("txtCriterioBusqCliente","value","");
		
	$objResponse->script("byId('btnCancelar').click();");
	return $objResponse;
}

function buscarCliente($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBusqCliente']);
	
	$objResponse->loadCommands(listadoClientes(0, "", "", $valBusq));
		
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function cargarModulos(){
	$objResponse = new xajaxResponse();
	
	$queryModulos = sprintf("SELECT * FROM pg_modulos");
	$rsModulos = mysql_query($queryModulos);
	if (!$rsModulos) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);

	$html = "<table border=\"0\" width=\"100%\">";
	$cont = 1;
	while ($rowModulos = mysql_fetch_array($rsModulos)) {
		if (fmod($cont, 4) == 1)
			$html .= "<tr align=\"center\" height=\"22\">";
				$html .= sprintf("<td><input type=\"checkbox\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"%s\"/>%s</td>",
					$rowModulos['id_enlace_concepto'],
					$rowModulos['descripcionModulo']);
		if (fmod($cont, 4) == 0)
			$html .= "</tr>";
	
		$cont++;	
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function listadoClientes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";	
	$sqlBusq .= $cond.sprintf("(id = %s
	OR CONCAT_WS('-',lci,ci) LIKE %s
	OR CONCAT_WS('',lci,ci) LIKE %s
	OR CONCAT_WS(' ',nombre,apellido) LIKE %s)",
		valTpDato($valCadBusq[0], "int"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id,
		CONCAT_WS('-',lci,ci) as cedula_cliente,
		CONCAT_WS(' ',nombre,apellido) as nombre_cliente,
		credito
	FROM cj_cc_cliente %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	
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
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "18%", $pageNum, "cedula_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";		
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarClientes(".$row['id'].");\" title=\"Seleccionar Cliente\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s Registros de un total de %s&nbsp;",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoClientes(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("tdListadoClientes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	$objResponse->assign("tdCabeceraEstado","innerHTML","");
	
	$objResponse->script("
		byId('trBuscarCliente').style.display = '';
		
		byId('tblListadoCliente').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Clientes");
	$objResponse->assign("tblListados","width","600");
	
	return $objResponse;
}

function listarTodoCliente($valForm){
	$objResponse = new xajaxResponse();
	
	// ESTADO DE CUENTA INDIVIDUAL
	$idCliente = $valForm['txtCodigoCliente'];
	$idEmpresa = $valForm['txtIdEmpresa'];
	
	$saldoTotalFactura = 0;
	$saldoTotalNotaCargo = 0;
	$saldoTotalAnticipo = 0;
	$saldoTotalNotaCredito = 0;
	
	$facturaSaldoCorriente = 0;
	$facturaSaldoEntre1 = 0;
	$facturaSaldoEntre2 = 0;
	$facturaSaldoEntre3 = 0;
	$facturaSaldoMasDe = 0;
	
	$anticipoSaldoCorriente = 0;
	$anticipoSaldoEntre1 = 0;
	$anticipoEntre2 = 0;
	$anticipoEntre3 = 0;
	$anticipoMasDe = 0;
	
	$notaCargoSaldoCorriente = 0;
	$notaCargoSaldoEntre1 = 0;
	$notaCargoSaldoEntre2 = 0;
	$notaCargoSaldoEntre3 = 0;
	$notaCargoSaldoMasDe = 0;
	
	$notaCreditoCorriente = 0;
	$notaCreditoEntre1 = 0;
	$notaCreditoEntre2 = 0;
	$notaCreditoEntre3 = 0;
	$notaCreditoMasDe = 0;
	
	$saldoTotal = 0;
	$montoCorrienteTotal = 0;
	$MontoEntre1Total = 0;
	$MontoEntre2Total = 0;
	$MontoEntre3Total = 0;
	$MontoMasDeTotal = 0;
	
	$selectGrupoEstado = "SELECT * FROM gruposestadocuenta";
	$rsGrupoEstado = mysql_query($selectGrupoEstado);
	if (!$rsGrupoEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGrupoEstado = mysql_fetch_array($rsGrupoEstado);
	
	$html = "<table border=\"0\" cellpadding=\"2\" width=\"100%\">
	<tr align=\"center\" class=\"tituloColumna\">
				<td colspan=\"2\" rowspan=\"2\" width='1%'></td>
				<td rowspan='2' width='4%'>Tipo</td>
				<td rowspan='2' width='14%'>Empesa</td>
				<td rowspan='2' width='10%'>Documento</td>
				<td rowspan='2' width='10%'>Nro. Siniestro</td>
				<td rowspan='2' width='10%'>Fecha Registro</td>
				<td rowspan='2' width='10%'>Saldo</td>
				<td rowspan='2' width='10%'>Cta. Corriente</td>
				<td colspan='4' width='40%'>D&iacute;as Vencidos</td>
			</tr>
			<tr align='center' class='tituloColumna'>
				<td width='6%'>De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>
				<td width='6%'>De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>
				<td width='6%'>De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>
				<td width='6%'>Mas de ".$rowGrupoEstado['masDe']."</td>
			</tr>";
					
	
	$condicion = "WHERE idCliente = '".$idCliente."' ";
	
	if ($idEmpresa > 0){
		$condicion .= "AND (vw_cc_antiguedad_saldo.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = vw_cc_antiguedad_saldo.id_empresa))";
	}
	
	if ($valForm['cbxModulo']){
		$idModulos = "AND idDepartamentoOrigenFactura in (";
		foreach ($valForm['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr ($idModulos, 0, (strlen($idModulos)-1));
		$idModulos .= ")";
	}
	
	$queryEstado = "SELECT *,
		(CASE
			WHEN (vw_cc_antiguedad_saldo.tipoDocumentoN = 1) THEN
				(SELECT COUNT(cj_cc_factura_detalle.id_factura) AS cant_items
				FROM cj_cc_factura_detalle
				WHERE cj_cc_factura_detalle.id_factura = vw_cc_antiguedad_saldo.idFactura)
	
			WHEN (vw_cc_antiguedad_saldo.tipoDocumentoN = 2) THEN
				(SELECT COUNT(cj_det_nota_cargo.idNotaCargo) AS cant_items
				FROM cj_det_nota_cargo
				WHERE cj_det_nota_cargo.idNotaCargo = vw_cc_antiguedad_saldo.idFactura)
	
			WHEN (vw_cc_antiguedad_saldo.tipoDocumentoN = 3) THEN
				(SELECT COUNT(cj_cc_detalleanticipo.idAnticipo) AS cant_items
				FROM cj_cc_detalleanticipo
				WHERE cj_cc_detalleanticipo.idAnticipo = vw_cc_antiguedad_saldo.idFactura)
	
			WHEN (vw_cc_antiguedad_saldo.tipoDocumentoN = 2) THEN
				(SELECT COUNT(cj_cc_nota_credito_detalle.id_nota_credito) AS cant_items
				FROM cj_cc_nota_credito_detalle
				WHERE cj_cc_nota_credito_detalle.id_nota_credito = vw_cc_antiguedad_saldo.idFactura)
		END) AS cant_items,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cc_antiguedad_saldo
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cc_antiguedad_saldo.id_empresa = vw_iv_emp_suc.id_empresa_reg) ".$condicion.$idModulos;
	$rsEstado = mysql_query($queryEstado);
	
	if (!$rsEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if (mysql_num_rows($rsEstado) > 0){
		while ($rowEstado = mysql_fetch_array($rsEstado)){
			$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
			$saldo = 0;
			$montoCorriente = 0;
			$MontoEntre1 = 0;
			$MontoEntre2 = 0;
			$MontoEntre3 = 0;
			$MontoMasDe = 0;
			$numeroSiniestro = "";	
			
			$queryEmpresa = "SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$rowEstado['id_empresa']."'";
			$rsEmpresa = mysql_query($queryEmpresa);
			if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowEmpresa = mysql_fetch_array($rsEmpresa);
			
			/* BUSCA LOS DATOS DEL CLIENTE */
			$queryCliente = "SELECT * FROM cj_cc_cliente WHERE id = '".$rowEstado['idCliente']."'";
			$rsCliente = mysql_query($queryCliente);
			if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowCliente = mysql_fetch_array($rsCliente);
			
			$empresa = $rowEmpresa['nombre_empresa'];
			
			if($rowEstado['tipoDocumentoN'] == 1 && $rowCliente['id_clave_movimiento_predeterminado'] == 24){
				/*BUSCA EL NUMERO DE SINIESTRO*/
				$queryNumeroSiniestro = sprintf("SELECT numero_siniestro FROM iv_presupuesto_venta WHERE id_presupuesto_venta = (SELECT iv_pedido_venta.id_presupuesto_venta FROM iv_pedido_venta WHERE id_pedido_venta = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = %s))",valTpDato($rowEstado['idFactura'],"int"));
				$rsNumeroSiniestro = mysql_query($queryNumeroSiniestro);
				
				if (!$rsNumeroSiniestro) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				$rowNumeroSiniestro = mysql_fetch_array($rsNumeroSiniestro);
				
				$numeroSiniestro = $rowNumeroSiniestro['numero_siniestro'];
			}
			
			if ($numeroSiniestro == "")
				$numeroSiniestro = "-";
			
			$arrayFechaOrigen = explode("-",$rowEstado['fechaRegistroFactura']);
			$fechaOrigen = $arrayFechaOrigen[2]."-".$arrayFechaOrigen[1]."-".$arrayFechaOrigen[0];
			
			$arrayFechaVencimiento = explode("-",$rowEstado['fechaVencimientoFactura']);
			$fechaVencimiento = $arrayFechaVencimiento[2]."-".$arrayFechaVencimiento[1]."-".$arrayFechaVencimiento[0];
			
			$fecha2 = mktime(0,0,0,$arrayFechaVencimiento[1],$arrayFechaVencimiento[2],$arrayFechaVencimiento[0]);
			//$fecha1 = mktime(0,0,0,$arrayFechaProveedor[1],$arrayFechaProveedor[2],$arrayFechaProveedor[0]);
			$fechaActual = date("Y-m-d");
			$arrayFechaActual = explode("-",$fechaActual);
			$fecha1 = mktime(0,0,0,$arrayFechaActual[1],$arrayFechaActual[2],$arrayFechaActual[0]);
			
			$dias = ($fecha1 - $fecha2) / 86400;
			
			switch($rowEstado['idDepartamentoOrigenFactura']) {
				case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
				case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
				default : $imgDctoModulo = $rowEstado['idDepartamentoOrigenFactura'];
			}
			
			if ($rowEstado['tipoDocumentoN'] == 1)
				$totalFactura += $rowEstado['saldoFactura'];
			else if ($rowEstado['tipoDocumentoN'] == 2)
				$totalNotaCargo += $rowEstado['saldoFactura'];
			else if ($rowEstado['tipoDocumentoN'] == 4)
				$totalNotaCredito += $rowEstado['saldoFactura'];
			else if ($rowEstado['tipoDocumentoN'] == 3)
				$totalAnticipo += $rowEstado['saldoFactura'];
				
			if ($rowEstado['tipoDocumentoN'] == 1 && $dias > 0){		
				if ($rowEstado['estado'] == 0)
					$estado = "No Cancelado";
				else if ($rowEstado['estado'] == 1)
					$estado = "Cancelado";
				else if ($rowEstado['estado'] == 2)
					$estado = "Parcialmente Cancelado";
			} else if ($rowEstado['tipoDocumentoN'] == 2 && $dias > 0){	
				if ($rowEstado['estado'] == 0)
					$estado = "No Cancelado";
				else if ($rowEstado['estado'] == 1)
					$estado = "Cancelado";
				else if ($rowEstado['estado'] == 2)
					$estado = "Parcialmente Cancelado";
			} else if ($rowEstado['tipoDocumentoN'] == 3 && $dias > 0){
				if ($rowEstado['estado'] == 0)
					$estado = "No Cancelado";
				else if ($rowEstado['estado'] == 1)
					$estado = "Cancelado/ No Asignado";
				else if ($rowEstado['estado'] == 2)
					$estado = "Asignado Parcialmente";
				else if ($rowEstado['estado'] == 3)
					$estado = "Asignado";
			} else if ($rowEstado['tipoDocumentoN'] == 4 && $dias > 0){
				if ($rowEstado['estado'] == 0)
					$estado = "No Cancelado";
				else if ($rowEstado['estado'] == 1)
					$estado = "Cancelado/ No Asignado";
				else if ($rowEstado['estado'] == 2)
					$estado = "Asignado Parcialmente";
				else if ($rowEstado['estado'] == 3)
					$estado = "Asignado";
			}
			
			if ($dias < $rowGrupoEstado['desde1']){
				if ($rowEstado['tipoDocumentoN'] == 1 || $rowEstado['tipoDocumentoN'] == 2 ){
					$montoCorriente += $rowEstado['saldoFactura'];
					$saldo += $rowEstado['saldoFactura'];
				} else if ($rowEstado['tipoDocumentoN'] == 3 || $rowEstado['tipoDocumentoN'] == 4){
					$montoCorriente -= $rowEstado['saldoFactura'];
					$saldo -= $rowEstado['saldoFactura'];
				} 
			} else if (($dias >= $rowGrupoEstado['desde1']) && ($dias <= $rowGrupoEstado['hasta1'])){
				if ($rowEstado['tipoDocumentoN'] == 1 || $rowEstado['tipoDocumentoN'] == 2 ){
					$MontoEntre1 += $rowEstado['saldoFactura'];
					$saldo += $rowEstado['saldoFactura'];
				} else if ($rowEstado['tipoDocumentoN'] == 3 || $rowEstado['tipoDocumentoN'] == 4){
					$MontoEntre1 -= $rowEstado['saldoFactura'];
					$saldo -= $rowEstado['saldoFactura'];
				} 
			} else if (($dias >= $rowGrupoEstado['desde2']) && ($dias <= $rowGrupoEstado['hasta2'])){
				if ($rowEstado['tipoDocumentoN'] == 1 || $rowEstado['tipoDocumentoN'] == 2 ){
					$MontoEntre2 += $rowEstado['saldoFactura'];
					$saldo += $rowEstado['saldoFactura'];
				} else if ($rowEstado['tipoDocumentoN'] == 3 || $rowEstado['tipoDocumentoN'] == 4){
					$MontoEntre2 -= $rowEstado['saldoFactura'];
					$saldo -= $rowEstado['saldoFactura'];
				} 
			} else if (($dias >= $rowGrupoEstado['desde3']) && ($dias <= $rowGrupoEstado['hasta3'])){
				if ($rowEstado['tipoDocumentoN'] == 1 || $rowEstado['tipoDocumentoN'] == 2 ){
					$MontoEntre3 += $rowEstado['saldoFactura'];
					$saldo += $rowEstado['saldoFactura'];
				} else if ($rowEstado['tipoDocumentoN'] == 3 || $rowEstado['tipoDocumentoN'] == 4){
					$MontoEntre3 -= $rowEstado['saldoFactura'];
					$saldo -= $rowEstado['saldoFactura'];
				} 
			} else {
				if ($rowEstado['tipoDocumentoN'] == 1 || $rowEstado['tipoDocumentoN'] == 2 ){
					$MontoMasDe += $rowEstado['saldoFactura'];
					$saldo += $rowEstado['saldoFactura'];
				} else if ($rowEstado['tipoDocumentoN'] == 3 || $rowEstado['tipoDocumentoN'] == 4){
					$MontoMasDe -= $rowEstado['saldoFactura'];
					$saldo -= $rowEstado['saldoFactura'];
				} 
			}

			$imgDctoModuloCondicion = ($rowEstado['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
			
			$saldoTotal += $saldo;
			$montoCorrienteTotal += $montoCorriente;
			$MontoEntre1Total += $MontoEntre1;
			$MontoEntre2Total += $MontoEntre2;
			$MontoEntre3Total += $MontoEntre3;
			$MontoMasDeTotal += $MontoMasDe;
			
			$html .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
				$html .= "<td align=\"center\">".$imgDctoModulo."</td>";
				$html .= "<td align=\"left\">".$imgDctoModuloCondicion."</td>";		
				$html .= "<td align=\"center\">".$rowEstado['tipoDocumento']."</td>";
				$html .= "<td align=\"left\">".utf8_encode($rowEstado['nombre_empresa'])."</td>";
				$html .= "<td align=\"right\">".$rowEstado['numeroFactura']."</td>";
				$html .= "<td align=\"right\">".$numeroSiniestro."</td>";
				$html .= "<td align=\"center\">".date("d-m-Y",strtotime($rowEstado['fechaRegistroFactura']))."</td>";
				$html .= "<td align=\"right\">".number_format($saldo,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($montoCorriente,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($MontoEntre1,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($MontoEntre2,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($MontoEntre3,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($MontoMasDe,2,'.',',')."</td>";
			$html .= "</tr>";
		
		}
		
		$html .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$html .= "<td class=\"tituloCampo\" colspan='7'>Totales</td>";
			$html .= "<td>".number_format($saldoTotal,2,'.',',')."</td>";
			$html .= "<td>".number_format($montoCorrienteTotal,2,'.',',')."</td>";
			$html .= "<td>".number_format($MontoEntre1Total,2,'.',',')."</td>";
			$html .= "<td>".number_format($MontoEntre2Total,2,'.',',')."</td>";
			$html .= "<td>".number_format($MontoEntre3Total,2,'.',',')."</td>";
			$html .= "<td>".number_format($MontoMasDeTotal,2,'.',',')."</td>";
		$html .= "</tr>";

		$html .= "</table>";
			
	$htmlResumen .= "<table border=\"0\" width=\"100%\">
			<tr align='center' class='tituloColumna'>
				<td width='20%'>Facturas</td>
				<td width='20%'>Notas de Credito</td>
				<td width='20%'>Notas de Débito</td>
				<td width='20%'>Anticipos</td>
				<td width='20%'>Total:</br>FA + ND - NC - AN</td>
			</tr>";
		
		$htmlResumen .= "<tr align=\"right\" class='trResaltarTotal' height=\"24\">";
			$htmlResumen .= "<td>".number_format($totalFactura,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalNotaCredito,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalNotaCargo,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalAnticipo,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalFactura + $totalNotaCargo - $totalNotaCredito - $totalAnticipo,2,'.',',')."</td>";
		$htmlResumen .= "</tr>";

		$htmlResumen .= "</table>";
		
	$objResponse->assign("tdCabeceraEstado","innerHTML",$html);
	$objResponse->assign("tdResumen","innerHTML",$htmlResumen);
	
	} else {
		$objResponse->assign("tdCabeceraEstado","innerHTML","<table cellpadding='0' cellspacing='0' class='divMsjError' width='100%'>
				<tr>
					<td width='25'><img src='../img/iconos/ico_fallido.gif' width='25'/></td>
					<td align='center'>No se encontraron registros</td>
				</tr>
			</table>");	
			
		$objResponse->assign("tdResumen","innerHTML","");
	}
		
	return $objResponse;
}

//
$xajax->register(XAJAX_FUNCTION,"asignarClientes");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"listadoClientes");
$xajax->register(XAJAX_FUNCTION,"listarTodoCliente");
?>