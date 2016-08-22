<?php 


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
			$html .= "<tr align=\"center\" height=\"24\">";
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
	
	$sqlBusq = sprintf(" WHERE id = %s
		OR (CONCAT_WS('-',lci,ci) LIKE %s
		OR CONCAT_WS('',lci,ci) LIKE %s
		OR CONCAT_WS(' ',nombre,apellido) LIKE %s)",
		valTpDato($valCadBusq[0], "int"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id,
		CONCAT_WS('-',lci,ci) as cedula_cliente,
		CONCAT_WS(' ',nombre,apellido) as nombre_cliente
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
	
	$htmlTblIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "20%", $pageNum, "cedula_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "65%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarClientes(".$row['id'].");\" title=\"Seleccionar Cliente\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"4\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr align=\"center\">";
				$htmlTf .= "<td width=\"50%\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td>";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"230\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"35\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td align=\"center\" width=\"35\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"90\">";
						
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
						$htmlTf .= "<td width=\"35\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"35\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td class=\"textoNegrita_10px\" width=\"50%\">";
					$htmlTf .= sprintf("Mostrando %s Registros de un total de %s",
						$contFila,
						$totalRows);
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoClientes","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	$objResponse->assign("tdCabeceraEstado","innerHTML","");
	
	$objResponse->script("
		byId('trBuscarCliente').style.display = '';
		
		byId('tblListadoCliente').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Clientes");
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if (byId('divFlotante').style.display == 'none') {
			byId('divFlotante').style.display = '';
			centrarDiv(byId('divFlotante'));
			
			document.forms['frmBuscarCliente'].reset();
			byId('txtCriterioBusqCliente').focus();
		}
	");
	
	return $objResponse;
}

function listarTodo($valForm){
	$objResponse = new xajaxResponse();
	
	//ESTADO DE CUENTA POR FECHA
	$idEmpresa = $valForm['txtIdEmpresa'];
	$txtFechaInicial = $valForm['txtFechaInicial'];
	$txtFechaFinal = $valForm['txtFechaFinal'];
	
	$html = "<table border=\"0\" cellpadding=\"2\" width=\"100%\">
			<tr align='center' class='tituloColumna'>
				<td colspan='2' width='1%'></td>
				<td width='3%'>Tipo</td>
				<td width='16%'>Documento</td>
				<td width='16%'>Nro. Control</td>
				<td width='16%'>Fecha Registro</td>
				<td width='16%'>Fecha Vencimiento</td>
				<td width='16%'>Saldo</td>
				<td width='16%'>Monto</td>
			</tr>";
	
	if ($valForm['cbxModulo']){
		$idModulos = "(";
		foreach ($valForm['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr ($idModulos, 0, (strlen($idModulos)-1));
		$idModulos .= ")";
	}
	
	$queryEstadoCabecera = "SELECT
		idCliente AS idCliente,
		id_empresa AS id_empresa
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE (cxc_fact.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = cxc_fact.id_empresa))
		AND idDepartamentoOrigenFactura IN ".$idModulos."
		AND (fechaRegistroFactura BETWEEN \"".date('Y-m-d',strtotime($txtFechaInicial))."\" AND \"".date('Y-m-d',strtotime($txtFechaFinal))."\")
	
	UNION
	
	SELECT
		idCliente AS idCliente,
		id_empresa AS id_empresa
	FROM cj_cc_notadecargo cxc_nd
	WHERE idCliente = '".$idCliente."'
		AND (cxc_nd.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = cxc_nd.id_empresa))
		AND idDepartamentoOrigenNotaCargo IN ".$idModulos."
		AND (fechaRegistroNotaCargo BETWEEN \"".date('Y-m-d',strtotime($txtFechaInicial))."\" AND \"".date('Y-m-d',strtotime($txtFechaFinal))."\")
	
	UNION
	
	SELECT
		idCliente AS idCliente,
		id_empresa AS id_empresa
	FROM cj_cc_anticipo cxc_ant
	WHERE idCliente = '".$idCliente."'
		AND (cxc_ant.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = cxc_ant.id_empresa))
		AND idDepartamento IN ".$idModulos."
		AND (fechaAnticipo BETWEEN \"".date('Y-m-d',strtotime($txtFechaInicial))."\" AND \"".date('Y-m-d',strtotime($txtFechaFinal))."\")
	
	UNION
	
	SELECT
		idCliente AS idCliente,
		id_empresa AS id_empresa
	FROM cj_cc_notacredito cxc_nc
	WHERE idCliente = '".$idCliente."'
		AND (cxc_nc.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = cxc_nc.id_empresa))
		AND idDepartamentoNotaCredito IN ".$idModulos."
		AND (fechaNotaCredito BETWEEN \"".date('Y-m-d',strtotime($txtFechaInicial))."\" AND \"".date('Y-m-d',strtotime($txtFechaFinal))."\") 
	GROUP BY idCliente";
	$rsEstadoCabecera = mysql_query($queryEstadoCabecera);
	if (!$rsEstadoCabecera) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if (mysql_num_rows($rsEstadoCabecera) > 0){
		while ($rowEstadoCabecera = mysql_fetch_array($rsEstadoCabecera)){
			$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
			
			$monto = 0;
			$saldo = 0;	
			$montoFactura = 0;
			$montoNotaCargo = 0;
			$montoAnticipo = 0;
			$montoNotaCredito = 0;
			
			$queryEmpresa = "SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$rowEstadoCabecera['id_empresa']."'";
			$rsEmpresa = mysql_query($queryEmpresa);
			if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowEmpresa = mysql_fetch_array($rsEmpresa);
			
			// BUSCA LOS DATOS DEL CLIENTE
			$queryCliente = "SELECT * FROM cj_cc_cliente WHERE id = '".$rowEstadoCabecera['idCliente']."'";
			$rsCliente = mysql_query($queryCliente);
			if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowCliente = mysql_fetch_array($rsCliente);
			
			$empresa = $rowEmpresa['nombre_empresa'];
			
			$queryEstado = sprintf("SELECT
				idCliente AS idCliente,
				'FA' AS tipoDocumento,
				'1' AS tipoDocumentoN,
				numeroFactura AS numeroFactura,
				numeroControl AS numeroControl,
				fechaRegistroFactura AS fechaRegistroFactura,
				fechaVencimientoFactura AS fechaVencimientoFactura,
				montoTotalFactura AS montoTotalFactura,
				saldoFactura AS saldoFactura,
				id_empresa AS id_empresa,
				estadoFactura AS estadoFactura,
				idDepartamentoOrigenFactura AS idDepartamentoOrigenFactura,
				(SELECT COUNT(cj_cc_factura_detalle.id_factura) FROM cj_cc_factura_detalle
				WHERE cj_cc_factura_detalle.id_factura = cxc_fact.idFactura) AS cant_items
			FROM cj_cc_encabezadofactura cxc_fact
			WHERE idCliente = '".$rowEstadoCabecera['idCliente']."'
				AND (cxc_fact.id_empresa = '".$idEmpresa."'
					OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
											WHERE pg_empresa.id_empresa = cxc_fact.id_empresa))
				AND idDepartamentoOrigenFactura IN ".$idModulos."
				AND (fechaRegistroFactura BETWEEN \"".date('Y-m-d',strtotime($txtFechaInicial))."\" AND \"".date('Y-m-d',strtotime($txtFechaFinal))."\")
			
			UNION
			
			SELECT
				idCliente AS idCliente,
				'ND' AS tipoDocumento,
				'2' AS tipoDocumentoN,
				numeroNotaCargo AS numeroFactura,
				numeroControlNotaCargo AS numeroControl,
				fechaRegistroNotaCargo AS fechaRegistroFactura,
				fechaVencimientoNotaCargo AS fechaVencimientoFactura,
				montoTotalNotaCargo AS montoTotalFactura,
				saldoNotaCargo AS saldoFactura,
				id_empresa AS id_empresa,
				estadoNotaCargo AS estadoFactura,
				idDepartamentoOrigenNotaCargo AS idDepartamentoOrigenFactura,
				(SELECT COUNT(cj_det_nota_cargo.idNotaCargo) FROM cj_det_nota_cargo
				WHERE cj_det_nota_cargo.idNotaCargo = cxc_nd.idNotaCargo) AS cant_items
			FROM cj_cc_notadecargo cxc_nd
			WHERE idCliente = '".$rowEstadoCabecera['idCliente']."'
				AND (cxc_nd.id_empresa = '".$idEmpresa."'
					OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
											WHERE pg_empresa.id_empresa = cxc_nd.id_empresa))
				AND idDepartamentoOrigenNotaCargo IN ".$idModulos."
				AND (fechaRegistroNotaCargo BETWEEN \"".date('Y-m-d',strtotime($txtFechaInicial))."\" AND \"".date('Y-m-d',strtotime($txtFechaFinal))."\")
			
			UNION
			
			SELECT
				idCliente AS idCliente,
				'AN' AS tipoDocumento,
				'3' AS tipoDocumentoN,
				numeroAnticipo AS numeroFactura,
				'-' AS numeroControl,
				fechaAnticipo AS fechaRegistroFactura,
				'-' AS fechaVencimientoFactura,
				montoNetoAnticipo AS montoTotalFactura,
				saldoAnticipo AS saldoFactura,
				id_empresa AS id_empresa,
				estadoAnticipo AS estadoFactura,
				idDepartamento AS idDepartamentoOrigenFactura,
				(SELECT COUNT(cj_cc_detalleanticipo.idAnticipo) FROM cj_cc_detalleanticipo
				WHERE cj_cc_detalleanticipo.idAnticipo = cxc_ant.idAnticipo) AS cant_items
			FROM cj_cc_anticipo cxc_ant
			WHERE idCliente = '".$rowEstadoCabecera['idCliente']."'
				AND (cxc_ant.id_empresa = '".$idEmpresa."'
					OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
											WHERE pg_empresa.id_empresa = cxc_ant.id_empresa))
				AND idDepartamento IN ".$idModulos."
				AND (fechaAnticipo BETWEEN \"".date('Y-m-d',strtotime($txtFechaInicial))."\" AND \"".date('Y-m-d',strtotime($txtFechaFinal))."\")
			
			UNION
			
			SELECT
				idCliente AS idCliente,
				'NC' AS tipoDocumento,
				'4' AS tipoDocumentoN,
				numeracion_nota_credito AS numeroFactura,
				numeroControl AS numeroControl,
				fechaNotaCredito AS fechaRegistroFactura,
				'-' AS fechaVencimientoFactura,
				montoNetoNotaCredito AS montoTotalFactura,
				saldoNotaCredito AS saldoFactura,
				id_empresa AS id_empresa,
				estadoNotaCredito AS estadoFactura,
				idDepartamentoNotaCredito AS idDepartamentoOrigenFactura,
				(SELECT COUNT(cj_cc_nota_credito_detalle.id_nota_credito) FROM cj_cc_nota_credito_detalle
				WHERE cj_cc_nota_credito_detalle.id_nota_credito = cxc_nc.idNotaCredito) AS cant_items
			FROM cj_cc_notacredito cxc_nc
			WHERE idCliente = '".$rowEstadoCabecera['idCliente']."'
				AND (cxc_nc.id_empresa = '".$idEmpresa."'
					OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
											WHERE pg_empresa.id_empresa = cxc_nc.id_empresa))
				AND idDepartamentoNotaCredito IN ".$idModulos."
				AND (fechaNotaCredito BETWEEN \"".date('Y-m-d',strtotime($txtFechaInicial))."\" AND \"".date('Y-m-d',strtotime($txtFechaFinal))."\") ");
			$rsEstado = mysql_query($queryEstado);
			if (!$rsEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$html .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$html .= "<td align=\"right\" colspan=\"3\" class=\"tituloCampo\">Cliente:</td>";
				$html .= "<td colspan=\"6\">".utf8_encode($rowCliente['nombre'])." ".utf8_encode($rowCliente['apellido'])."</td>";
			$html .= "</tr>";
			
			while ($rowEstado = mysql_fetch_array($rsEstado)) {
				$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
				
				switch($rowEstado['idDepartamentoOrigenFactura']) {
					case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
					case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
					case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
					case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
					case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
					default : $imgDctoModulo = $rowEstado['idDepartamentoOrigenFactura'];
				}			

				$imgDctoModuloCondicion = ($rowEstado['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
			
				$fechaVencimiento = ($rowEstado['fechaVencimientoFactura'] == '-') ? $rowEstado['fechaVencimientoFactura'] : date("d-m-Y",strtotime($rowEstado['fechaVencimientoFactura']));
				
				$html .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";	
					$html .= "<td align=\"center\">".$imgDctoModulo."</td>";
					$html .= "<td align=\"center\">".$imgDctoModuloCondicion."</td>";			
					$html .= "<td align=\"center\" >".$rowEstado['tipoDocumento']."</td>";
					$html .= "<td align=\"right\" >".$rowEstado['numeroFactura']."</td>";
					$html .= "<td align=\"right\" >".$rowEstado['numeroControl']."</td>";
					$html .= "<td align=\"center\" >".date("d-m-Y",strtotime($rowEstado['fechaRegistroFactura']))."</td>";
					$html .= "<td align=\"center\" >".$fechaVencimiento."</td>";
					$html .= "<td align=\"right\">".number_format($rowEstado['saldoFactura'],2,'.',',')."</td>";
					$html .= "<td align=\"right\">".number_format($rowEstado['montoTotalFactura'],2,'.',',')."</td>";
				$html .= "</tr>";
				
				if ($rowEstado['tipoDocumentoN'] == 1){				
					$montoFactura += $rowEstado['montoTotalFactura'];
					$monto += $rowEstado['montoTotalFactura'];
					$saldo += $rowEstado['saldoFactura'];
				} else if ($rowEstado['tipoDocumentoN'] == 2){
					$montoNotaCargo += $rowEstado['montoTotalFactura'];
					$monto += $rowEstado['montoTotalFactura'];
					$saldo += $rowEstado['saldoFactura'];
				} else if ($rowEstado['tipoDocumentoN'] == 3){
					$montoAnticipo += $rowEstado['montoTotalFactura'];
					$monto -= $rowEstado['montoTotalFactura'];
					$saldo -= $rowEstado['saldoFactura'];
				} else if ($rowEstado['tipoDocumentoN'] == 4){
					$montoNotaCredito += $rowEstado['montoTotalFactura'];
					$monto -= $rowEstado['montoTotalFactura'];
					$saldo -= $rowEstado['saldoFactura'];
				}
			}
			
			$montoTotal += $monto;
			$saldoTotal += $saldo;
			$montoFacturaTotal += $montoFactura;
			$MontoNotaCargoTotal += $montoNotaCargo;
			$MontoAnticipoTotal += $montoAnticipo;
			$MontoNotaCreditoTotal += $montoNotaCredito;
			
			$html .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$html .= "<td class=\"tituloCampo\" colspan=\"7\">Total Cliente ".utf8_encode($rowCliente['nombre']." ".$rowCliente['apellido']).":</td>";
				$html .= "<td>".number_format($saldo,2,'.',',')."</td>";
				$html .= "<td>".number_format($monto,2,'.',',')."</td>";
			$html .= "</tr>";
		}
		
		$html .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
			$html .= "<td class=\"tituloCampo\" colspan=\"7\">Totales</td>";
			$html .= "<td>".number_format($saldoTotal,2,'.',',')."</td>";
			$html .= "<td>".number_format($montoTotal,2,'.',',')."</td>";
		$html .= "</tr>";
		
		$html .= "</table>";
		
		$objResponse->assign("tdCabeceraEstado","innerHTML",$html);
	} else {
		$objResponse->assign("tdCabeceraEstado","innerHTML","<table cellpadding='0' cellspacing='0' class='divMsjError' width='100%'>
				<tr>
					<td width='25'><img src='../img/iconos/ico_fallido.gif' width='25'/></td>
					<td align='center'>No se encontraron registros</td>
				</tr>
			</table>");							
	}
	
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"listadoClientes");
$xajax->register(XAJAX_FUNCTION,"listarTodo");
?>