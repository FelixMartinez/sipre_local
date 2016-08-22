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

function cargarFecha(){
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("txtFechaInicial","value",date("d-m-Y"));
	$objResponse->assign("txtFechaFinal","value",date("d-m-Y"));
	
	return $objResponse;
}

function listarRetenciones($frmFechasRetenciones){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmFechasRetenciones['txtIdEmpresa'];
	$fechaDesde = $frmFechasRetenciones['txtFechaInicial'];
	$fechaHasta = $frmFechasRetenciones['txtFechaFinal'];
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(q.id_empresa = %s
		OR %s IN (SELECT id_empresa_padre FROM pg_empresa
				WHERE pg_empresa.id_empresa = q.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("q.fechaPago BETWEEN %s AND %s",
		valTpDato(date("Y-m-d",strtotime($fechaDesde)),"date"),
		valTpDato(date("Y-m-d",strtotime($fechaHasta)),"date"));
	
	$queryRetenciones = sprintf("SELECT q.*,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM (SELECT 
				cxc_fact.id_empresa,
				cxc_pago.numeroFactura,
				cxc_fact.idCliente,
				cxc_pago.fechaPago,
				cxc_pago.formaPago,
				cxc_pago.numeroDocumento,
				cxc_pago.montoPagado
			FROM cj_cc_encabezadofactura cxc_fact
				INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			WHERE cxc_pago.formaPago IN (9)
				AND cxc_pago.estatus IN (1)
			
			UNION
			
			SELECT 
				cxc_fact.id_empresa,
				cxc_pago.numeroFactura,
				cxc_fact.idCliente,
				cxc_pago.fechaPago,
				cxc_pago.formaPago,
				cxc_pago.numeroDocumento,
				cxc_pago.montoPagado
			FROM cj_cc_encabezadofactura cxc_fact
				INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			WHERE cxc_pago.formaPago IN (9)
				AND cxc_pago.estatus IN (1)) AS q
		INNER JOIN cj_cc_cliente cliente ON (q.idCliente = cliente.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (q.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	$rs = mysql_query($queryRetenciones);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryRetenciones);
	$totalRows = mysql_num_rows($rs);
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	if ($totalRows > 0){
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"20%\">Empresa</td>";
			$htmlTh .= "<td width=\"10%\">Fecha</td>";
			$htmlTh .= "<td width=\"15%\">".utf8_encode("Nro. Comprobante")."</td>";
			$htmlTh .= "<td width=\"15%\">".utf8_encode("Nro. Factura")."</td>";
			$htmlTh .= "<td width=\"25%\">Cliente</td>";
			$htmlTh .= "<td width=\"15%\">Monto</td>";
		$htmlTh .= "</tr>";
	}
	while ($row = mysql_fetch_array($rs)){
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaPago']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroDocumento']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoPagado'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$montoTotal += $row['montoPagado'];
	}
	if ($totalRows > 0){
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"5\">Total General:</td>";
			$htmlTb .= "<td align=\"right\">".number_format($montoTotal, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdCabeceraEstado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarFecha");
$xajax->register(XAJAX_FUNCTION,"listarRetenciones");
?>