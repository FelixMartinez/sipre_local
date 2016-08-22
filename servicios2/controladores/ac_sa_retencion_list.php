<?php
function buscarRetenciones($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoRetencion(0,'','','%s' + '|' + %s + '|' + %s);",
		$valForm['txtBusq'],
		$valForm['selEmpresa'],
		$valForm['criterioBusqueda']));
	
	return $objResponse;
}

function listadoRetencion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[2] == 0){
		$sqlBusq = sprintf(" WHERE numeroComprobante LIKE %s ",
		valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	else if ($valCadBusq[2] == 1){
		$sqlBusq = sprintf(" WHERE numeroControlFactura LIKE %s ",
		valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	else if ($valCadBusq[2] == 2){
		$sqlBusq = sprintf(" WHERE nombre_proveedor LIKE %s ",
		valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	else if ($valCadBusq[2] == 3){
		$sqlBusq = sprintf(" WHERE numero_factura_proveedor LIKE %s ",
		valTpDato("%".$valCadBusq[0]."%", "text"));
	}
		
	if ($valCadBusq[1] == -1)
		$sqlBusq .= " AND id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
	else if ($valCadBusq[1] != 0)
		$sqlBusq .= " AND id_empresa = '".$valCadBusq[1]."'";
	
	$query = sprintf("SELECT * FROM vw_cp_renteciones").$sqlBusq;
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit) or die(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "14%", $pageNum, "idProveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "8%", $pageNum, "numeroComprobante", $campOrd, $tpOrd, $valBusq, $maxRows, "N&ordm; Comprobante");
	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "8%", $pageNum, "fechaComprobante", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
    $htmlTh .= ordenarCampo("xajax_listadoRetencion", "8%", $pageNum, "numeroControlFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Control Factura");
    $htmlTh .= ordenarCampo("xajax_listadoRetencion", "9%", $pageNum, "totalCompraIncluyendoIva", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
    $htmlTh .= ordenarCampo("xajax_listadoRetencion", "9%", $pageNum, "baseImponible", $campOrd, $tpOrd, $valBusq, $maxRows, "Base Imponible");
    $htmlTh .= ordenarCampo("xajax_listadoRetencion", "9%", $pageNum, "impuestoIva", $campOrd, $tpOrd, $valBusq, $maxRows, "Iva");
	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "9%", $pageNum, "IvaRetenido", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto Retenido");
	
	$htmlTh.= "<td width=\"3%\" class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	//anterior sin ordenamiento
	/*
    $htmlTh .="<td width=\"14%\">Proveedor</td>
            	<td width=\"8%\">".utf8_encode("Nº Comprobante")."</td>
                <td width=\"8%\">".utf8_encode("Fecha")."</td>
                <td width=\"8%\">".utf8_encode("Numero Control Factura")."</td>
                <td width=\"9%\">".utf8_encode("Total")."</td>
				<td width=\"9%\">".utf8_encode("Base Imponible")."</td>
				<td width=\"9%\">".utf8_encode("Iva")."</td>
				<td width=\"9%\">".utf8_encode("Impuesto Retenido")."</td>
				<td width=\"3%\"></td>
            </tr>";
		*/
		
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";
				
		$queryEmpresa = "SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$row['id_empresa']."'";
		$rsEmpresa = mysql_query($queryEmpresa) or die (mysql_error());
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
		$nombreSucursal = "";
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
		
		$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);		
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".utf8_encode(nombreProveedor($row['idProveedor']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroComprobante']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d/m/Y",strtotime($row['fechaComprobante']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroControlFactura']."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['totalCompraIncluyendoIva'],2,",",".")."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['baseImponible'],2,",",".")."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['impuestoIva'],2,",",".")."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['IvaRetenido'],2,",",".")."</td>";
			$htmlTb .= sprintf("<td align=\"center\" class=\"noprint\" ><img class=\"puntero\" onclick=\"window.open('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s','_blank');\" src=\"../img/iconos/ico_view.png\" /></td>",
				$row['idRetencionCabezera']);
		$htmlTb .= "</tr>";
		
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoRetencion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function nombreProveedor($idProveedor){

	$queryProveedor=sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$idProveedor);
	$rsProveedor=mysql_query($queryProveedor) or die(mysql_error());
	$rowProveedor=mysql_fetch_array($rsProveedor);
 	
	$nombreProveedor = $rowProveedor['nombre']; 
	
	return $nombreProveedor;

}

function rifProveedor($idProveedor){

	$queryProveedor=sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$idProveedor);
	$rsProveedor=mysql_query($queryProveedor) or die(mysql_error());
	$rowProveedor=mysql_fetch_array($rsProveedor);
 	
	$rifProveedor = $rowProveedor['lrif']."-".$rowProveedor['rif']; 
	
	return $rifProveedor;

}

function calcularMontoFact($idFactura){

	$queryFactura = sprintf("SELECT * FROM cp_factura WHERE id_factura = '%s'",$idFactura);
	$rsFactura = mysql_query($queryFactura) or die(mysql_error());
	$rowFacura = mysql_fetch_array($rsFactura);
	 
	$queryFacturaGastos = sprintf("SELECT * FROM cp_factura_gasto WHERE id_factura = '%s'", $idFactura);
	$rsFacturaGasto = mysql_query($queryFacturaGastos) or die(mysql_error());
		while($rowFacturaGasto = mysql_fetch_array($rsFacturaGasto)){
			
			$montoIvaGasto=($rowFacturaGasto['monto']*($rowFacturaGasto['iva']/100));
			$montoGastos = $rowFacturaGasto['monto']+$montoGastos;
		
		} 	
	 
	$queryFacturaIva = sprintf("SELECT * FROM cp_factura_iva WHERE id_factura = '%s'", $idFactura);
	$rsFacturaIva = mysql_query($queryFacturaIva) or die(mysql_error());
		while($rowFacturaIva = mysql_fetch_array($rsFacturaIva)){
			
			$montoIva=$rowFacturaIva['subtotal_iva']+$montoIva;
		
		}
//
		$montoFinal = ( $montoGastos+ $montoIva + $rowFacura['subtotal_factura'])-$rowFacura['subtotal_descuento'];

		return  $montoFinal;
}

function estadoFactura($estadoFactura){

if( $estadoFactura == 0 ){
	$estado =  'No Cancelado';
}
if( $estadoFactura == 1 ){
	$estado =  'Cancelado';
}
if( $estadoFactura == 2 ){
	$estado =  'Cancelado Parcial';
}
return $estado;
}

function comboEmpresa($valForm){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
			valTpDato($_SESSION['idUsuarioSysGts'],"int"));
		$rs = mysql_query($query) or die (mysql_error());
		$html = "<select id=\"selEmpresa\" name=\"selEmpresa\" onChange=\"xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar'))\">";
		$html .="<option value=\"0\">Todas</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$nombreSucursal = "";
			if ($row['id_empresa_padre_suc'] > 0)
				$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
			
			$selected = "";
			if ($selId == $row['id_empresa_reg'] || $_SESSION['idEmpresaUsuarioSysGts'] == $row['id_empresa_reg'])
				$selected = "selected='selected'";
		
			$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
		}
		$html .= "</select>";
	
		$objResponse->assign("tdSelEmpresa","innerHTML",$html);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarRetenciones");
$xajax->register(XAJAX_FUNCTION,"listadoRetencion");
$xajax->register(XAJAX_FUNCTION,"comboEmpresa");
?>