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
		CONCAT_WS('-',lci,ci) AS cedula_cliente,
		CONCAT_WS(' ',nombre,apellido) AS nombre_cliente,
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
	
	//ESTADO DE CUENTA GENERAL
	$idEmpresa = $valForm['txtIdEmpresa'];
	$idCliente = $valForm['txtCodigoCliente'];
	$txtFechaDesde = $valForm['txtFechaInicial'];
	$txtFechaHasta = $valForm['txtFechaFinal'];
	
	$html = "<table border=\"0\" cellpadding=\"2\" width=\"100%\">
 		<tr align=\"center\" class=\"tituloColumna\">
			<td colspan='2' width='1%'></td>
			<td width='9%'>Tipo</td>
			<td width='20%'>Nro. Documento</td>
			<td width='15%'>Fecha Registro</td>
			<td width='15%'>Fecha Vencimiento</td>
			<td width='20%'>Estado</td>
			<td width='10%'>Monto</td>
			<td width='10%'>Saldo</td>
			<td></td>
		</tr>";
		
	if ($valForm['cbxModulo']) {
		$idModulos = "(";
		foreach ($valForm['cbxModulo'] as $pos => $valor) {
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr ($idModulos, 0, (strlen($idModulos)-1));
		$idModulos .= ")";
	}
	
	$queryEstado = "SELECT
		idCliente AS idCliente,
		'FA' AS tipoDocumento,
		'1' AS tipoDocumentoN,
		idFactura AS idFactura,
		numeroFactura AS numeroFactura,
		fechaRegistroFactura AS fechaRegistroFactura,
		fechaVencimientoFactura AS fechaVencimientoFactura,
		montoTotalFactura AS montoTotalFactura,
		saldoFactura AS saldoFactura,
		id_empresa AS id_empresa,
		estadoFactura AS estadoFactura,
		idDepartamentoOrigenFactura AS idDepartamentoOrigenFactura,
		(SELECT COUNT(cj_cc_factura_detalle.id_factura) FROM cj_cc_factura_detalle
			WHERE cj_cc_factura_detalle.id_factura = cj_cc_encabezadofactura.idFactura) AS cant_items
	FROM
		cj_cc_encabezadofactura
	WHERE
		idCliente = '".$idCliente."'
		AND (cj_cc_encabezadofactura.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = cj_cc_encabezadofactura.id_empresa))
		AND idDepartamentoOrigenFactura IN ".$idModulos."
		AND (fechaRegistroFactura BETWEEN \"".date('Y-m-d',strtotime($txtFechaDesde))."\" AND \"".date('Y-m-d',strtotime($txtFechaHasta))."\")
		
	UNION
	
	SELECT
		idCliente AS idCliente,
		(CASE observacionNotaCargo
			WHEN 'DEVOLUCION DE CHEQUE' THEN
				'ND-DC'
			ELSE
				'ND'
		END) AS tipoDocumento,
		'2' AS tipoDocumentoN,
		idNotaCargo AS idFactura,
		numeroNotaCargo AS numeroFactura,
		fechaRegistroNotaCargo AS fechaRegistroFactura,
		fechaVencimientoNotaCargo AS fechaVencimientoFactura,
		montoTotalNotaCargo AS montoTotalFactura,
		saldoNotaCargo AS saldoFactura,
		id_empresa AS id_empresa,
		estadoNotaCargo AS estadoFactura,
		idDepartamentoOrigenNotaCargo AS idDepartamentoOrigenFactura,
		(SELECT COUNT(cj_det_nota_cargo.idNotaCargo) FROM cj_det_nota_cargo
			WHERE cj_det_nota_cargo.idNotaCargo = cj_cc_notadecargo.idNotaCargo) AS cant_items
	FROM
		cj_cc_notadecargo
	WHERE
		idCliente = '".$idCliente."'
		AND (cj_cc_notadecargo.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = cj_cc_notadecargo.id_empresa))
		AND idDepartamentoOrigenNotaCargo IN ".$idModulos."
		AND (fechaRegistroNotaCargo BETWEEN \"".date('Y-m-d',strtotime($txtFechaDesde))."\" AND \"".date('Y-m-d',strtotime($txtFechaHasta))."\")
		
	UNION
	
	SELECT
		idCliente AS idCliente,
		'AN' AS tipoDocumento,
		'3' AS tipoDocumentoN,
		idAnticipo AS idFactura,
		numeroAnticipo AS numeroFactura,
		fechaAnticipo AS fechaRegistroFactura,
		'-' AS fechaVencimientoFactura,
		montoNetoAnticipo AS montoTotalFactura,
		saldoAnticipo AS saldoFactura,
		id_empresa AS id_empresa,
		estadoAnticipo AS estadoFactura,
		idDepartamento AS idDepartamentoOrigenFactura,
		(SELECT COUNT(cj_cc_detalleanticipo.idAnticipo) FROM cj_cc_detalleanticipo
			WHERE cj_cc_detalleanticipo.idAnticipo = cj_cc_anticipo.idAnticipo) AS cant_items
	FROM
		cj_cc_anticipo
	WHERE
		idCliente = '".$idCliente."'
		AND (cj_cc_anticipo.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = cj_cc_anticipo.id_empresa))
		AND idDepartamento IN ".$idModulos." 
		AND estatus = 1
		AND (fechaAnticipo BETWEEN \"".date('Y-m-d',strtotime($txtFechaDesde))."\" AND \"".date('Y-m-d',strtotime($txtFechaHasta))."\")
		
	UNION
	
	SELECT
		idCliente AS idCliente,
		'NC' AS tipoDocumento,
		'4' AS tipoDocumentoN,
		idNotaCredito AS idFactura,
		numeracion_nota_credito AS numeroFactura,
		fechaNotaCredito AS fechaRegistroFactura,
		'-' AS fechaVencimientoFactura,
		montoNetoNotaCredito AS montoTotalFactura,
		saldoNotaCredito AS saldoFactura,
		id_empresa AS id_empresa,
		estadoNotaCredito AS estadoFactura,
		idDepartamentoNotaCredito AS idDepartamentoOrigenFactura,
		(SELECT COUNT(cj_cc_nota_credito_detalle.id_nota_credito) FROM cj_cc_nota_credito_detalle
				WHERE cj_cc_nota_credito_detalle.id_nota_credito = cj_cc_notacredito.idNotaCredito) AS cant_items
	FROM
		cj_cc_notacredito
	WHERE
		idCliente = '".$idCliente."'
		AND (cj_cc_notacredito.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = cj_cc_notacredito.id_empresa))
		AND idDepartamentoNotaCredito IN ".$idModulos." 
		AND (fechaNotaCredito BETWEEN \"".date('Y-m-d',strtotime($txtFechaDesde))."\" AND \"".date('Y-m-d',strtotime($txtFechaHasta))."\")
		
	UNION
	
	SELECT
		idCliente AS idCliente,
		'RE' AS tipoDocumento,
		'5' AS tipoDocumentoN,
		idRetencionCabezera AS idFactura,
		numeroComprobante AS numeroFactura,
		fechaComprobante AS fechaRegistroFactura,
		'-' AS fechaVencimientoFactura,
		(SELECT IvaRetenido FROM cj_cc_retenciondetalle WHERE idRetencionCabezera = cj_cc_retencioncabezera.idRetencionCabezera) AS montoTotalFactura,
		'-' AS saldoFactura,
		(SELECT fact_vent.id_empresa
		FROM cj_cc_retenciondetalle ret_det
			INNER JOIN cj_cc_encabezadofactura fact_vent ON (ret_det.idFactura = fact_vent.idFactura)
		WHERE ret_det.idRetencionCabezera = cj_cc_retencioncabezera.idRetencionCabezera
			AND fact_vent.idFactura = ret_det.idFactura) AS id_empresa,
			
		'-' AS estadoFactura,
		
		(SELECT fact_vent.idDepartamentoOrigenFactura
		FROM cj_cc_retenciondetalle ret_det
			INNER JOIN cj_cc_encabezadofactura fact_vent ON (ret_det.idFactura = fact_vent.idFactura)
		WHERE ret_det.idRetencionCabezera = cj_cc_retencioncabezera.idRetencionCabezera
			AND fact_vent.idFactura = ret_det.idFactura) AS idDepartamentoOrigenFactura,
		(SELECT COUNT(cj_cc_retenciondetalle.idRetencionCabezera) FROM cj_cc_retenciondetalle
			WHERE cj_cc_retenciondetalle.idRetencionCabezera = cj_cc_retencioncabezera.idRetencionCabezera) AS cant_items
	FROM
		cj_cc_retencioncabezera
	WHERE
		idCliente = '".$idCliente."'
		AND (SELECT fact_vent.id_empresa
			FROM cj_cc_retenciondetalle ret_det
				INNER JOIN cj_cc_encabezadofactura fact_vent ON (ret_det.idFactura = fact_vent.idFactura)
			WHERE ret_det.idRetencionCabezera = cj_cc_retencioncabezera.idRetencionCabezera
				AND fact_vent.idFactura = ret_det.idFactura) = '".$idEmpresa."'
		AND (SELECT fact_vent.idDepartamentoOrigenFactura
			FROM cj_cc_retenciondetalle ret_det
				INNER JOIN cj_cc_encabezadofactura fact_vent ON (ret_det.idFactura = fact_vent.idFactura)
			WHERE ret_det.idRetencionCabezera = cj_cc_retencioncabezera.idRetencionCabezera
				AND fact_vent.idFactura = ret_det.idFactura) IN ".$idModulos."
		AND (fechaComprobante BETWEEN \"".date('Y-m-d',strtotime($txtFechaDesde))."\" AND \"".date('Y-m-d',strtotime($txtFechaHasta))."\")
	ORDER BY 6,5 ASC";
	$rsEstado = mysql_query($queryEstado);
	if (!$rsEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if (mysql_num_rows($rsEstado) > 0){
		while ($rowEstado = mysql_fetch_array($rsEstado)){
			$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
			
			$monto = 0;
			
			if ($rowEstado['fechaVencimientoFactura'] != '-')
				$fechaVencimiento = date("d-m-Y",strtotime($rowEstado['fechaVencimientoFactura']));
			else
				$fechaVencimiento = '-';
			
			switch($rowEstado['idDepartamentoOrigenFactura']) {
				case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
				case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
				case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
				default : $imgDctoModulo = $rowEstado['idDepartamentoOrigenFactura'];
			}			

			$imgDctoModuloCondicion = ($rowEstado['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";	
			//FACTURA
			if ($rowEstado['tipoDocumentoN'] == 1){
				//ESTADO
					if ($rowEstado['estadoFactura'] == 0)
						$estado = "No Cancelado";
					else if ($rowEstado['estadoFactura'] == 1)
						$estado = "Cancelado";
					else if ($rowEstado['estadoFactura'] == 2)
						$estado = "Parcialmente Cancelado";	
				//TOTAL
				$totalFactura += $rowEstado['montoTotalFactura'];
				$monto = $rowEstado['montoTotalFactura'];
				$totalSaldoFactura += $rowEstado['saldoFactura'];
				$saldo = $rowEstado['saldoFactura'];
				//ACCION
				$accion = "cc_factura_form.php?id=%s&acc=0";		
			}
			// NOTA DE DÉBITO
			else if ($rowEstado['tipoDocumentoN'] == 2){
				//ESTADO
					if ($rowEstado['estadoFactura'] == 0)
						$estado = "No Cancelado";
					else if ($rowEstado['estadoFactura'] == 1)
						$estado = "Cancelado";
					else if ($rowEstado['estadoFactura'] == 2)
						$estado = "Parcialmente Cancelado";
				//TOTAL
				$totalNotaCargo += $rowEstado['montoTotalFactura'];
				$monto = $rowEstado['montoTotalFactura'];
				$totalSaldoNotaCargo += $rowEstado['saldoFactura'];
				$saldo = $rowEstado['saldoFactura'];
				//ACCION
				$accion = "cc_nota_debito_form.php?id=%s&acc=0";
			}
			//ANTICIPO			
			else if ($rowEstado['tipoDocumentoN'] == 3){
				//ESTADO
					if ($rowEstado['estadoFactura'] == 0)
						$estado = "No Cancelado";
					else if ($rowEstado['estadoFactura'] == 1)
						$estado = "Cancelado/ No Asignado";
					else if ($rowEstado['estado'] == 2)
						$estado = "Asignado Parcialmente";
					else if ($rowEstado['estadoFactura'] == 3)
						$estado = "Asignado";
				//TOTAL
				$totalAnticipo += $rowEstado['montoTotalFactura'];
				$monto = $rowEstado['montoTotalFactura'] * -1;
				$totalSaldoAnticipo += $rowEstado['saldoFactura'];
				$saldo = $rowEstado['saldoFactura'];
				//ACCION
				$accion = "cc_anticipo_form.php?id=%s&acc=0";
			}
			//NOTA DE CREDITO
			else if ($rowEstado['tipoDocumentoN'] == 4){
				//ESTADO
					if ($rowEstado['estadoFactura'] == 0)
						$estado = "No Cancelado";
					else if ($rowEstado['estado'] == 1)
						$estado = "Cancelado/ No Asignado";
					else if ($rowEstado['estadoFactura'] == 2)
						$estado = "Asignado Parcialmente";
					else if ($rowEstado['estadoFactura'] == 3)
						$estado = "Asignado";
				//TOTAL
				$totalNotaCredito += $rowEstado['montoTotalFactura'];
				$monto = $rowEstado['montoTotalFactura'] * -1;
				$totalSaldoNotaCredito += $rowEstado['saldoFactura'];
				$saldo = $rowEstado['saldoFactura'];
				//ACCION
				$accion = "cc_nota_credito_form.php?id=%s&acc=0";
			}
			//RETENCION
			else if ($rowEstado['tipoDocumentoN'] == 5){
				//ESTADO
						$estado = "-";
				//TOTAL
				$totalRetencion += $rowEstado['montoTotalFactura'];
				$monto = $rowEstado['montoTotalFactura'] * -1;
				$totalSaldoRetencion += $rowEstado['saldoFactura'];
				$saldo = $rowEstado['saldoFactura'];
				//ACCION
				//$accion = "window.location.href='cc_retenciones_venta_list.php';";
			}
			
			$montoTotal += $monto;
			$saldoTotal += $saldo;
			
			$html .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
				$html .= "<td align=\"center\">".$imgDctoModulo."</td>";
				$html .= "<td align=\"center\">".$imgDctoModuloCondicion."</td>";	
				$html .= "<td align=\"center\">".$rowEstado['tipoDocumento']."</td>";
				$html .= "<td align=\"right\">".$rowEstado['numeroFactura']."</td>";
				$html .= "<td align=\"center\">".date("d-m-Y",strtotime($rowEstado['fechaRegistroFactura']))."</td>";
				$html .= "<td align=\"center\">".$fechaVencimiento."</td>";
				$html .= "<td align=\"center\">".$estado."</td>";
				$html .= "<td align=\"right\">".number_format($monto,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($saldo,2,'.',',')."</td>";
				if ($rowEstado['tipoDocumentoN'] != 5){
				$html .= sprintf("<td align=\"center\" class=\"noprint\"><a href=\"".$accion."\" target=\"_blank\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Documento\"/></a></td>",$rowEstado['idFactura']);
				} else 
				$html .= "<td align=\"center\"></td>";
			$html .= "</tr>";
		}
		
		$html .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$html .= "<td class=\"tituloCampo\" colspan=\"7\">Totales:</td>";
			$html .= "<td>".number_format($montoTotal,2,'.',',')."</td>";
			$html .= "<td>".number_format($saldoTotal,2,'.',',')."</td>";
			$html .= "<td></td>";
		$html .= "</tr>";
	$html .= "</table>";

	$htmlResumen .= "<fieldset><legend class=\"legend\">Resumen de Montos</legend>
					<table border=\"0\" width=\"100%\">
						<tr align='center' class='tituloColumna'>
							<td width='16%'>Facturas</td>
							<td width='16%'>".utf8_encode('Notas de Débito')."</td>
							<td width='16%'>".utf8_encode('Notas de Crédito')."</td>
							<td width='16%'>Anticipos</td>
							<td width='16%'>Retenciones</td>
							<td width='20%'>Total:</br>FA + ND - NC - AN - RE</td>
						</tr>";
		$htmlResumen .= "<tr align=\"right\" class='trResaltarTotal' height=\"24\">";
			$htmlResumen .= "<td>".number_format($totalFactura,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalNotaCargo,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalNotaCredito,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalAnticipo,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalRetencion,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalFactura + $totalNotaCargo - $totalNotaCredito - $totalAnticipo - $totalRetencion,2,'.',',')."</td>";
		$htmlResumen .= "</tr>";
	$htmlResumen .= "</table></fieldset>";
	
	$htmlResumen .= "<fieldset><legend class=\"legend\">Resumen de Saldos</legend>
					<table border=\"0\" width=\"100%\">
						<tr align='center' class='tituloColumna'>
							<td width='16%'>Facturas</td>
							<td width='16%'>".utf8_encode('Notas de Débito')."</td>
							<td width='16%'>".utf8_encode('Notas de Crédito')."</td>
							<td width='16%'>Anticipos</td>
							<td width='16%'>Retenciones</td>
							<td width='20%'>Total:</br>FA + ND - NC - AN - RE</td>
						</tr>";
		$htmlResumen .= "<tr align=\"right\" class='trResaltarTotal' height=\"24\">";
			$htmlResumen .= "<td>".number_format($totalSaldoFactura,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalSaldoNotaCargo,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalSaldoNotaCredito,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalSaldoAnticipo,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalSaldoRetencion,2,'.',',')."</td>";
			$htmlResumen .= "<td>".number_format($totalSaldoFactura + $totalSaldoNotaCargo - $totalSaldoNotaCredito - $totalSaldoAnticipo - $totalSaldoRetencion,2,'.',',')."</td>";
		$htmlResumen .= "</tr>";
	$htmlResumen .= "</table></fieldset>";
	
	$objResponse->assign("tdCabeceraEstado","innerHTML",$html);
	
	$objResponse->assign("tdResumen","innerHTML",$htmlResumen);
	$objResponse->assign("tdResumenSaldo","innerHTML",$htmlResumenSaldo);
			
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

$xajax->register(XAJAX_FUNCTION,"asignarClientes");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"listadoClientes");
$xajax->register(XAJAX_FUNCTION,"listarTodo");
$xajax->register(XAJAX_FUNCTION,"listarTodoCliente");
$xajax->register(XAJAX_FUNCTION,"listarTodoDetalle");
?>