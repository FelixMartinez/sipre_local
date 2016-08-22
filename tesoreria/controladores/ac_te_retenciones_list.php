<?php

function anularRetencion($idRetencion){

	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_retenciones_list","eliminar")){
		return $objResponse;
	}
	
	mysql_query("START TRANSACTION;");
	
	$queryRetencion = sprintf("SELECT id_factura, tipo, monto_retenido 
								FROM te_retencion_cheque 
								WHERE id_retencion_cheque = %s
								AND anulado IS NULL",
					valTpDato($idRetencion,"int"));
	$rsRetencion = mysql_query($queryRetencion);
	if(!$rsRetencion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowRetencion = mysql_fetch_assoc($rsRetencion);
	
	if(mysql_num_rows($rsRetencion) == 0) { return $objResponse->alert("La retencion ya fue anulada"); }
	
	$idDocumento = $rowRetencion["id_factura"];
	$tipo = $rowRetencion["tipo"]; // 0 = factura, 1 = nota de cargo
	$montoRetenido = $rowRetencion["monto_retenido"];
	
	if($tipo == 0){//FACTURA
		$queryDocumento = sprintf("SELECT total_cuenta_pagar, saldo_factura AS saldo_documento 
									FROM cp_factura 
									WHERE id_factura = %s",
							$idDocumento);	
	}else if($tipo == 1){//NOTA DE CARGO
		$queryDocumento = sprintf("SELECT total_cuenta_pagar, saldo_notacargo AS saldo_documento 
									FROM cp_notadecargo 
									WHERE id_notacargo = %s",
							$idDocumento);
	}
	
	$rsDocumento = mysql_query($queryDocumento);
	if(!$rsDocumento) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDocumento = mysql_fetch_assoc($rsDocumento);
	
	$nuevoSaldo = $rowDocumento['saldo_documento'] + $montoRetenido;

	if($nuevoSaldo == $rowDocumento['total_cuenta_pagar']){
		$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
	}elseif($nuevoSaldo == 0){
		$cambioEstado = 1;
	}else{
		$cambioEstado = 2;
	}
	
	if($tipo == 0){//FACTURA
		$queryUpdateDoc = sprintf("UPDATE cp_factura SET saldo_factura = %s, estatus_factura = %s 
									WHERE id_factura = %s",
							$nuevoSaldo,
							$cambioEstado,
							$idDocumento);	
	}else if($tipo == 1){//NOTA DE CARGO
		$queryUpdateDoc = sprintf("UPDATE cp_notadecargo SET saldo_notacargo = %s, estatus_notacargo = %s 
									WHERE id_notacargo = %s",
							$nuevoSaldo,
							$cambioEstado,
							$idDocumento);
	}
	
	$rsUpdateDoc = mysql_query($queryUpdateDoc);
	if(!$rsUpdateDoc) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryUpdatePago = sprintf("UPDATE cp_pagos_documentos SET estatus = NULL, fecha_anulado = NOW(), id_empleado_anulado = %s
								WHERE tipo_pago = 'ISLR' 
								AND id_documento = %s",
						$_SESSION['idEmpleadoSysGts'],
						$idRetencion);
	$rsUpdatePago = mysql_query($queryUpdatePago);
	if(!$rsUpdatePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryUpdateRetencion = sprintf("UPDATE te_retencion_cheque SET anulado = 1
									WHERE id_retencion_cheque = %s",
							$idRetencion);
	$rsUpdateRetencion = mysql_query($queryUpdateRetencion);
	if(!$rsUpdateRetencion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Retencion anulada con exito");
	$objResponse->script("byId('btnBuscar').click();");
	
	return $objResponse;
}

function asignarDetallesRetencion($idRetencion){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_retenciones WHERE id = '".$idRetencion."'";
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$row = mysql_fetch_array($rs);

	$objResponse->assign("hddMontoMayorAplicar","value",$row['importe']);
	$objResponse->assign("hddPorcentajeRetencion","value",$row['porcentaje']);
	$objResponse->assign("hddSustraendoRetencion","value",$row['sustraendo']);
	$objResponse->assign("hddCodigoRetencion","value",$row['codigo']);
	$objResponse->script("calcularRetencion();");
	
	return $objResponse;
}

function asignarFactura($idFactura){
	$objResponse = new xajaxResponse();
	
	$queryFactura = sprintf("SELECT numero_factura_proveedor, fecha_origen, fecha_vencimiento, observacion_factura, saldo_factura FROM cp_factura WHERE id_factura = '%s'",$idFactura);
	$rsFactura = mysql_query($queryFactura);
	if(!$rsFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
	$rowFactura = mysql_fetch_assoc($rsFactura);
		
	$objResponse->assign("txtIdFactura","value",$idFactura);
	$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
	$objResponse->assign("txtSaldoFactura","value",$rowFactura['saldo_factura']);
	$objResponse->assign("txtFechaRegistroFactura","value",$rowFactura['fecha_origen']);
	$objResponse->assign("txtFechaVencimientoFactura","value",$rowFactura['fecha_vencimiento']);
	$objResponse->assign("txtDescripcionFactura","innerHTML",utf8_encode($rowFactura['observacion_factura']));
	$objResponse->assign("hddTipoDocumento","value","0");
	$objResponse->assign("tdFacturaNota","innerHTML","FACTURA");
	
	$queryIvaFactura = sprintf("SELECT base_imponible, iva FROM cp_factura_iva WHERE id_factura = %s ",$idFactura);
	$rsIvaFactura = mysql_query($queryIvaFactura);
	
	if (!$rsIvaFactura) return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__);
			
	if (mysql_num_rows($rsIvaFactura)){
		$rowIvaFactura = mysql_fetch_array($rsIvaFactura);
		$objResponse->assign("hddIva","value",$rowIvaFactura['iva']);
		$objResponse->assign("hddBaseImponible","value",$rowIvaFactura['base_imponible']);
		$objResponse->assign("txtBaseRetencionISLR","value",$rowIvaFactura['base_imponible']);
	}else{
		$objResponse->assign("hddIva","value","0");
		$objResponse->assign("hddBaseImponible","value","0");
		$objResponse->assign("txtBaseRetencionISLR","value","0");
	}
			
	$objResponse->script("xajax_verificarRetencionISLR(".$idFactura.",0);
						document.getElementById('divFlotante3').style.display = 'none';");
	
	return $objResponse;
}

function asignarNotaCargo($idNotaCargo){
	$objResponse = new xajaxResponse();
	
	$queryNotaCargo = sprintf("SELECT numero_notacargo,fecha_origen_notacargo, fecha_vencimiento_notacargo , observacion_notacargo, saldo_notacargo FROM cp_notadecargo WHERE id_notacargo = '%s'",$idNotaCargo);
	$rsNotaCargo = mysql_query($queryNotaCargo);		
	if (!$rsNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);
		
	$objResponse->assign("txtIdFactura","value",$idNotaCargo);
	$objResponse->assign("txtNumeroFactura","value",$rowNotaCargo['numero_notacargo']);
	$objResponse->assign("txtSaldoFactura","value",$rowNotaCargo['saldo_notacargo']);
	$objResponse->assign("txtFechaRegistroFactura","value",$rowNotaCargo['fecha_origen_notacargo']);
	$objResponse->assign("txtFechaVencimientoFactura","value",$rowNotaCargo['fecha_vencimiento_notacargo']);
	$objResponse->assign("txtDescripcionFactura","innerHTML", utf8_encode($rowNotaCargo['observacion_notacargo']));
	$objResponse->assign("hddTipoDocumento","value","1");
	$objResponse->assign("tdFacturaNota","innerHTML","NOTA DE CARGO");
	
	$queryIvaNotaCargo = sprintf("SELECT baseimponible, iva  FROM cp_notacargo_iva WHERE id_notacargo = %s ",$idNotaCargo);
	$rsIvaNotaCargo = mysql_query($queryIvaNotaCargo);
	
	if (!$rsIvaNotaCargo) return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__);
	
	if (mysql_num_rows($rsIvaNotaCargo)){
		$rowIvaNotaCargo = mysql_fetch_array($rsIvaNotaCargo);
		$objResponse->assign("hddIva","value",$rowIvaNotaCargo['iva']);
		$objResponse->assign("hddBaseImponible","value",$rowIvaNotaCargo['baseimponible']);
		$objResponse->assign("txtBaseRetencionISLR","value",$rowIvaNotaCargo['baseimponible']);
	}else{
		$objResponse->assign("hddIva","value","0");
		$objResponse->assign("hddBaseImponible","value","0");
		$objResponse->assign("txtBaseRetencionISLR","value","0");
	}
			
	$objResponse->script("xajax_verificarRetencionISLR(".$idNotaCargo.",1);
						document.getElementById('divFlotante3').style.display = 'none';");
	
	return $objResponse;
}

function buscarRetenciones($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoRetenciones(0,'','','%s' + '|' + '%s' + '|' + '%s'+ '|' + '%s' + '|' + '%s' + '|' + '%s');",
		$valForm['hddIdEmpresa'],
		$valForm['hddBePro'],
		$valForm['txtFecha'],
		$valForm['txtCriterio'],
		$valForm['listAnulado'],
		$valForm['listPago']
		));

	return $objResponse;
}

function buscarDocumento($valform,$id_Empresa,$Fac_nCargo) {
	$objResponse = new xajaxResponse();

	if($Fac_nCargo=="0"){
		$Busq = $valform['txtCriterioBusqFactura'];
		if($Busq==''){
			$objResponse->script("xajax_listarFacturas(0,'','',".$id_Empresa.")");
		}else{
			$objResponse->script("xajax_listarFacturas(0,'','',".$id_Empresa."+'|'+'".$Busq."')");
		}
	}elseif($Fac_nCargo=="1"){
		$Busq = $valform['txtCriterioBusqNotaCargo'];
		if($Busq==''){
			$objResponse->script("xajax_listarNotaCargo(0,'','',".$id_Empresa.")");
		}else{
			$objResponse->script("xajax_listarNotaCargo(0,'','',".$id_Empresa."+'|'+'".$Busq."')");
		}
	}else{//boton buscar
		if($valform['buscarFact'] == "2"){//fact
			$Busq = $valform['txtCriterioBusqFactura'];
			if($Busq==''){
				$objResponse->script("xajax_listarFacturas(0,'','',".$id_Empresa.")");
			}else{
				$objResponse->script("xajax_listarFacturas(0,'','',".$id_Empresa."+'|'+'".$Busq."')");
			}
		}elseif($valform['buscarFact']){//nota
			$Busq = $valform['txtCriterioBusqNotaCargo'];
			if($Busq==''){
				$objResponse->script("xajax_listarNotaCargo(0,'','',".$id_Empresa.")");
			}else{
				$objResponse->script("xajax_listarNotaCargo(0,'','',".$id_Empresa."+'|'+'".$Busq."')");
			}
		}
	}
	
	return $objResponse;
}

function comboRetencionISLR(){
	$objResponse = new xajaxResponse();
	
	$queryRetenciones = "SELECT * FROM te_retenciones WHERE activo = 1";
	$rsRetenciones = mysql_query($queryRetenciones);
	
	$html = "<select id=\"selRetencionISLR\" name=\"selRetencionISLR\" class=\"inputHabilitado\" disabled=\"disabled\" onchange=\"xajax_asignarDetallesRetencion(this.value)\">";
	
	while ($rowRetenciones = mysql_fetch_assoc($rsRetenciones)) {
		$html .= "<option value=\"".$rowRetenciones['id']."\">".utf8_encode($rowRetenciones['descripcion'])."</option>";
	}
	$html .= "</select>";
		
	$objResponse->assign("tdRetencionISLR","innerHTML",$html);
	$objResponse->assign("hddMontoMayorAplicar","innerHTML","0");
	$objResponse->assign("hddPorcentajeRetencion","innerHTML","0");
	$objResponse->assign("hddSustraendoRetencion","innerHTML","0");

	return $objResponse;
}

function guardarRetencion($frmRetencion){
	
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_retenciones_list","insertar")){
		return $objResponse;
	}

	if($frmRetencion['txtMontoRetencionISLR'] < 0){
		return $objResponse->alert("La retencion no puede ser negativa: ".$frmRetencion['txtMontoRetencionISLR']);
	}
	
	if($frmRetencion['txtMontoRetencionISLR'] == 0){
		return $objResponse->alert("La retencion no puede ser Cero: ".$frmRetencion['txtMontoRetencionISLR']);
	}
	
	if ($frmRetencion['txtMontoRetencionISLR'] > 0){
		
		mysql_query("START TRANSACTION;");
		
		$idDocumento = $frmRetencion['txtIdFactura'];
		$tipoDocumento = $frmRetencion['hddTipoDocumento'];// 0 = factura, 1 = nota de cargo
		$fecha = date("Y-m-d", strtotime($frmRetencion["txtFechaRetencion"]));
		$tipoDocumentoPago = ($tipoDocumento == '1') ? 'ND' : 'FA';
		
		//verifico si ya tiene:
		$query = sprintf("SELECT te_retenciones.descripcion
					FROM cp_pagos_documentos pago
					INNER JOIN te_retencion_cheque ON te_retencion_cheque.id_retencion_cheque = pago.id_documento
					INNER JOIN te_retenciones ON te_retencion_cheque.id_retencion = te_retenciones.id
					WHERE pago.tipo_pago = 'ISLR' 
					AND pago.estatus = 1 
					AND pago.tipo_documento_pago = %s 
					AND pago.id_documento_pago = %s",
			valTpDato($tipoDocumentoPago,"text"),
			valTpDato($idDocumento,"int"));				
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
		$row = mysql_fetch_assoc($rs);
		
		if(mysql_num_rows($rs) > 0){//si tiene retencion activa
			return	$objResponse->alert("El documento ya posee retencion: \n".utf8_encode($row['descripcion'])."");
		}
		
		$queryRetencion = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, tipo, tipo_documento, fecha_registro)
									VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idDocumento,"int"), 
							valTpDato($frmRetencion['selRetencionISLR'],"int"),
							valTpDato($frmRetencion['txtBaseRetencionISLR'],"real_inglesa"), 
							valTpDato($frmRetencion['hddSustraendoRetencion'],"real_inglesa"), 
							valTpDato($frmRetencion['hddPorcentajeRetencion'],"real_inglesa"), 
							valTpDato($frmRetencion['txtMontoRetencionISLR'],"real_inglesa"), 
							valTpDato($frmRetencion['hddCodigoRetencion'],"text"), 
							$tipoDocumento,// 0 = factura, 1 = nota de cargo
							2,// 0 = Cheque, 1 = Transferencia, 2 = Sin Documento
							valTpDato($fecha,"date"));

		$rsRetencion = mysql_query($queryRetencion);
		if (!$rsRetencion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

		$idRetencionCheque = mysql_insert_id();
		
		$queryPago = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago,tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) 
									VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
							valTpDato($idDocumento, "int"), 
							valTpDato($tipoDocumentoPago, "text"),// FA, ND
							valTpDato('ISLR', "text"),
							valTpDato($idRetencionCheque, "int"),
							valTpDato($fecha,"date"),
							valTpDato($idRetencionCheque, "int"),
							valTpDato('-', "text"),
							valTpDato('-', "text"),
							valTpDato('-', "text"),
							valTpDato('-', "text"),
							valTpDato($frmRetencion['txtMontoRetencionISLR'], "real_inglesa"),
							valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		
		$rsPago = mysql_query($queryPago);		
		if (!$rsPago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		if($tipoDocumento == '1'){// 0 = factura, 1 = nota cargo
			$sql = sprintf("SELECT saldo_notacargo AS saldo_documento, total_cuenta_pagar FROM cp_notadecargo WHERE id_notacargo = %s LIMIT 1",
				valTpDato($idDocumento, "int"));
		}else{
			$sql = sprintf("SELECT saldo_factura AS saldo_documento, total_cuenta_pagar FROM cp_factura WHERE id_factura = %s LIMIT 1",
				valTpDato($idDocumento, "int"));
		}		
		
		$rs = mysql_query($sql);
		if (!$rs) { $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		$saldoNuevo = $row['saldo_documento'] - $frmRetencion['txtMontoRetencionISLR'];
		
		if($saldoNuevo < 0){ return $objResponse->alert('El saldo del documento '.$tipoDocumentoPago.'  '.$frmRetencion['txtNumeroFactura'].' no puede ser negativo: '.$saldoNuevo.''); 
		}

		if($saldoNuevo == $row['total_cuenta_pagar']){
			$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
		}elseif($saldoNuevo == 0){
			$cambioEstado = 1;
		}else{
			$cambioEstado = 2;
		}
		
		if($tipoDocumento == '1'){// 0 = factura, 1 = nota cargo
			$sqlSaldo = sprintf("UPDATE cp_notadecargo SET estatus_notacargo = %s, saldo_notacargo = %s 
					WHERE id_notacargo = %s ;",
			$cambioEstado,
			$saldoNuevo, 
			$idDocumento);
		}else{
			$sqlSaldo = sprintf("UPDATE cp_factura SET estatus_factura = %s, saldo_factura = %s 
					WHERE id_factura = %s ;",
			$cambioEstado,
			$saldoNuevo, 
			$idDocumento);
		}		
	
		$rs = mysql_query($sqlSaldo);
		if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$objResponse->script("verVentana('reportes/te_imprimir_constancia_retencion_pdf.php?id=".$idRetencionCheque."&documento=3',700,700);");
		
		$objResponse->alert("Retencion creada correctamente");
		$objResponse->script("document.getElementById('divFlotante').style.display='none';");
		$objResponse->script("byId('btnBuscar').click();");
		
		mysql_query("COMMIT");		
	}
	
	return $objResponse;
}

function listadoRetenciones($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_retenciones_list")){
		$objResponse->assign("tdListadoRetenciones","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        
	if ($valCadBusq[0] == ''){
		//$sqlBusq .= " vw_te_retencion_cheque.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
	}else if ($valCadBusq[0] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."vw_te_retencion_cheque.id_empresa = '".$valCadBusq[0]."'";
	}
        
	if ($valCadBusq[1] != 0){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."vw_te_retencion_cheque.id_proveedor = '".$valCadBusq[1]."'";
	}
        
	if ($valCadBusq[2] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%Y/%m') = '".date("Y/m",strtotime('01-'.$valCadBusq[2]))."'";
	}
        
	if ($valCadBusq[3] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" (vw_te_retencion_cheque.numero_factura LIKE %s 
									OR vw_te_retencion_cheque.numero_control_factura LIKE %s) ",
									valTpDato('%'.$valCadBusq[3].'%', 'text'),
									valTpDato('%'.$valCadBusq[3].'%', 'text'));
	}
	
	if ($valCadBusq[4] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if($valCadBusq[4] == 1){
			$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado = 1 ");
		}else{
			$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado IS NULL ");
		}
	}
	
	if ($valCadBusq[5] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.tipo_documento = %s",
									valTpDato($valCadBusq[5],"int"));
	}
	
	$query = sprintf("SELECT 
		vw_te_retencion_cheque.id_retencion_cheque,
		vw_te_retencion_cheque.id_cheque,
		vw_te_retencion_cheque.rif_proveedor,
		vw_te_retencion_cheque.nombre,
		vw_te_retencion_cheque.numero_control_factura,
		vw_te_retencion_cheque.numero_factura,
		vw_te_retencion_cheque.id_factura,
		vw_te_retencion_cheque.codigo,
		vw_te_retencion_cheque.subtotal_factura,
		vw_te_retencion_cheque.monto_retenido,
		vw_te_retencion_cheque.porcentaje_retencion,
		vw_te_retencion_cheque.descripcion,
		vw_te_retencion_cheque.base_imponible_retencion,
		vw_te_retencion_cheque.sustraendo_retencion,
		vw_te_retencion_cheque.tipo,
		vw_te_retencion_cheque.tipo_documento, 
		vw_te_retencion_cheque.anulado,
		DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%s') as fecha_registro_formato
	FROM vw_te_retencion_cheque
	",'%d-%m-%Y').$sqlBusq;  
						
	//$objResponse->alert($query);//alerta
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "1%", $pageNum, "anulado", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "1%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "5%", $pageNum, "fecha_registro_formato", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "8%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "RIF Retenido");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "20%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "1%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Doc.");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "5%", $pageNum, "numero_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "5%", $pageNum, "numero_control_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Pago");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "5%", $pageNum, "subtotal_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Operaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "1%", $pageNum, "id_retencion_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro Comprob.");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "10%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Retenci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "1%", $pageNum, "codigo", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo Concepto");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "1%", $pageNum, "base_imponible_retencion", $campOrd, $tpOrd, $valBusq, $maxRows, "Base Retenci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "1%", $pageNum, "monto_retenido", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Retenido");
		$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "1%", $pageNum, "porcentaje_retencion", $campOrd, $tpOrd, $valBusq, $maxRows, "Porcentaje Retenci&oacute;n");
				$htmlTh .= ordenarCampo("xajax_listadoRetenciones", "1%", $pageNum, "sustraendo_retencion", $campOrd, $tpOrd, $valBusq, $maxRows, "Sustraendo Retenci&oacute;n");
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= "<td width=\"1%\"></td>";		
	$htmlTh .= "</tr>";
	
	$cont = 0;
	$contb = 1;
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;		
		
		if($row['anulado'] == 1){
			$imgAnulado = "<img title=\"Anulado\" src=\"../img/iconos/ico_rojo.gif\">";
		}else{
			$imgAnulado = "<img title=\"Activo\" src=\"../img/iconos/ico_verde.gif\">";
		}
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgAnulado."</td>";
			$htmlTb .= "<td align=\"center\">".$contb."</td>";
			$htmlTb .= "<td align=\"center\">".$row['fecha_registro_formato']."</td>";
			if ($row['tipo'] == 1) {
				$queryNota = sprintf("SELECT 
										cp_proveedor.nombre,
										cp_proveedor.lrif,
										cp_proveedor.rif
									FROM cp_notadecargo
									INNER JOIN cp_proveedor ON (cp_notadecargo.id_proveedor = cp_proveedor.id_proveedor) 
									WHERE cp_notadecargo.id_notacargo = %s", 
							$row['id_factura']);
				$rsNota = mysql_query($queryNota);
				if(!$rsNota) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$rowNota = mysql_fetch_array($rsNota);

				$htmlTb .= "<td align=\"center\">".$rowNota['lrif']."-".$rowNota['rif']."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowNota['nombre'])."</td>";
			} else {
				$htmlTb .= "<td align=\"center\">".$row['rif_proveedor']."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre'])."</td>";
			}
			
			$tipoDocumento = "";
			if($row["tipo"] == 0){
				$tipoDocumento = "FA";
			}elseif($row["tipo"] == 1){
				$tipoDocumento = "ND";
			}
			
			$tipoPago = "";
			if($row["tipo_documento"] == 0){
				$tipoPago = "CH";
			}elseif($row["tipo_documento"] == 1){
				$tipoPago = "TR";
			}
			
			$botonAnular = "";
			if($row['id_cheque'] == "" && $row['anulado'] != 1){//sin pago asociado y no anulado
				$botonAnular = sprintf("<img title=\"Anular\" class=\"puntero\" src=\"../img/iconos/delete.png\" onclick=\"if(confirm('¿Deseas anular la retencion?')){ xajax_anularRetencion(%s); }\"></img>",
					$row['id_retencion_cheque']);
			}
			
			$htmlTb .= "<td align=\"center\">".$tipoDocumento."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_control_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoPago."</td>";
			$htmlTb .= "<td align=\"center\">".$row['subtotal_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_retencion_cheque']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['codigo']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['base_imponible_retencion']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['monto_retenido']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['porcentaje_retencion']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['sustraendo_retencion']."</td>";
			$htmlTb .= "<td align=\"center\" title=\"Imprimir\"><img class=\"puntero\" src=\"../img/iconos/page_white_acrobat.png\" onclick=\"verVentana('reportes/te_imprimir_constancia_retencion_pdf.php?id=".$row['id_retencion_cheque']."&documento=3',700,700);\" ></td>";
			$htmlTb .= "<td>".$botonAnular."</td>";
			$cont +=  $row['monto_retenido'];
			$contb += 1;
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"22\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
	$htmlTblFin .= "<br><br>";
	
	$queryTotales = sprintf("SELECT 
								vw_te_retencion_cheque.rif_proveedor,
								vw_te_retencion_cheque.nombre,
								vw_te_retencion_cheque.numero_control_factura,
								vw_te_retencion_cheque.id_factura,
								vw_te_retencion_cheque.codigo,
								vw_te_retencion_cheque.monto_retenido,
								vw_te_retencion_cheque.porcentaje_retencion,
								vw_te_retencion_cheque.id_retencion_cheque,
								vw_te_retencion_cheque.descripcion,
								vw_te_retencion_cheque.base_imponible_retencion,
								vw_te_retencion_cheque.sustraendo_retencion,
								vw_te_retencion_cheque.tipo,
								vw_te_retencion_cheque.tipo_documento,  
								DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%s') as fecha_registro_formato
							FROM vw_te_retencion_cheque",
					'%d-%m-%Y').$sqlBusq; 
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	while($rowTotales = mysql_fetch_array($rsTotales)){
		$contTotal +=  $rowTotales['monto_retenido'];	
	}
	
	$htmlx.="<table align=\"center\" class=\"tabla\" border=\"1\" width=\"60%\">";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total por P&aacute;gina:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($cont,'2','.',',')."</td>";
		$htmlx.="</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($contTotal,'2','.',',')."</td>";
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	$objResponse->assign("tdListadoRetenciones","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx);
	
		
	return $objResponse;
}

function listarBeneficiarios1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	global $spanRIF;
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" WHERE CONCAT(lci_rif,'-',ci_rif_beneficiario) LIKE %s
		OR CONCAT(lci_rif,ci_rif_beneficiario) LIKE %s
		OR nombre_beneficiario LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id_beneficiario AS id,
		CONCAT(lci_rif,'-',ci_rif_beneficiario) as rif_beneficiario,
		nombre_beneficiario
	FROM te_beneficiarios %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
        
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios1", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("C&oacute;digo"));
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios1", "20%", $pageNum, "rif_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI."/".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios1", "65%", $pageNum, "nombre_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarBeneficiario1('".$row['id']."');\" title=\"Seleccionar Beneficiario\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_beneficiario']."</td>";
			$htmlTb .= "<td>".  utf8_encode($row['nombre_beneficiario'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdContenido","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("document.getElementById('tblBeneficiariosProveedores').style.display = '';");
	
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if (document.getElementById('divFlotante1').style.display == 'none') {
			document.getElementById('divFlotante1').style.display = '';
			centrarDiv(document.getElementById('divFlotante1'));
			
			document.forms['frmBuscarCliente'].reset();
			document.getElementById('txtCriterioBusqBeneficiario').focus();
		}
	");
	
	return $objResponse;
}


function listarProveedores1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	global $spanRIF;
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" WHERE CONCAT(lrif,'-',rif) LIKE %s
		OR CONCAT(lrif,rif) LIKE %s
		OR nombre LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id_proveedor AS id,
		CONCAT(lrif,'-',rif) as rif_proveedor,
		nombre
	FROM cp_proveedor %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
        
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarProveedores1", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listarProveedores1", "20%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI."/".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listarProveedores1", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor1('".$row['id']."');\" title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdContenido","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	//$objResponse->assign("tdCabeceraEstado","innerHTML","");
	
	$objResponse->script("document.getElementById('tblBeneficiariosProveedores').style.display = '';");
	
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if (document.getElementById('divFlotante1').style.display == 'none') {
			document.getElementById('divFlotante1').style.display = '';
			centrarDiv(document.getElementById('divFlotante1'));
			
			document.forms['frmBuscarCliente'].reset();
			document.getElementById('txtCriterioBusqProveedor').focus();
		}
	");
	
	return $objResponse;
}

function listarFacturas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	$sqlBusq = sprintf(" AND (numero_factura_proveedor LIKE %s 
							OR numero_control_factura LIKE %s
							OR cp_proveedor.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"));
		

	$query = sprintf("SELECT cp_factura.*, pg_empresa.nombre_empresa, cp_proveedor.nombre
            FROM cp_factura
			INNER JOIN pg_empresa ON cp_factura.id_empresa = pg_empresa.id_empresa 
			INNER JOIN cp_proveedor ON cp_factura.id_proveedor = cp_proveedor.id_proveedor
            WHERE (cp_factura.id_empresa = %s OR cp_factura.id_empresa IN ((SELECT id_empresa_reg 
														FROM vw_iv_empresas_sucursales 
														WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s )))
            
			AND cp_factura.estatus_factura <> 1 
			AND cp_factura.id_factura NOT IN (SELECT te_propuesta_pago_detalle.id_factura 
												FROM te_propuesta_pago_detalle 
												WHERE te_propuesta_pago_detalle.tipo_documento <> 1 
												
												UNION ALL
												
												SELECT te_propuesta_pago_detalle_transferencia.id_factura 
												FROM te_propuesta_pago_detalle_transferencia 
												WHERE te_propuesta_pago_detalle_transferencia.tipo_documento <> 1) %s",
			$valCadBusq[0],
			$valCadBusq[0],
			$sqlBusq);  
  
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"2%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "20%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "5%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero");
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "5%", $pageNum, "numero_control_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Control");		
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "20%", $pageNum, "observacion_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "5%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listarFacturas", "5%", $pageNum, "saldo_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarFactura('".$row['id_factura']."');\" title=\"Seleccionar Factura\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_control_factura']."</td>";
			$htmlTb .= "<td align=\"left\" class=\"texto_9px\">".utf8_encode($row['observacion_factura'])."</td>";
			$htmlTb .= "<td>".date("d-m-Y",strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['saldo_factura']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarFacturas(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
								  
							  
	$objResponse->assign("tdContenidoDocumento","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	$objResponse->script("document.getElementById('tblFacturasNcargos').style.display = '';");
	$objResponse->script("
		if (document.getElementById('divFlotante3').style.display == 'none') {
			document.getElementById('divFlotante3').style.display = '';
			centrarDiv(document.getElementById('divFlotante3'));
			
			document.forms['frmBuscarDocumento'].reset();
			document.getElementById('txtCriterioBusqFactura').focus();
		}
	");
	
	return $objResponse;
}

function listarNotaCargo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	$sqlBusq = sprintf(" AND (numero_notacargo LIKE %s 
							OR numero_control_notacargo LIKE %s
							OR cp_proveedor.nombre LIKE %s) ",
		valTpDato("%".$valCadBusq[1]."%", "text"),
		valTpDato("%".$valCadBusq[1]."%", "text"),
		valTpDato("%".$valCadBusq[1]."%", "text"));

	$query = sprintf("SELECT cp_notadecargo.*, pg_empresa.nombre_empresa, cp_proveedor.nombre
            FROM cp_notadecargo 
			INNER JOIN pg_empresa ON cp_notadecargo.id_empresa = pg_empresa.id_empresa
			INNER JOIN cp_proveedor ON cp_notadecargo.id_proveedor = cp_proveedor.id_proveedor
            WHERE (cp_notadecargo.id_empresa = %s OR cp_notadecargo.id_empresa IN ((SELECT id_empresa_reg 
														FROM vw_iv_empresas_sucursales 
														WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s )))

			AND estatus_notacargo <> 1 
			AND cp_notadecargo.id_notacargo NOT IN (SELECT te_propuesta_pago_detalle.id_factura 
														FROM te_propuesta_pago_detalle 
														WHERE te_propuesta_pago_detalle.tipo_documento <> 0 
														
														UNION ALL
														
														SELECT te_propuesta_pago_detalle_transferencia.id_factura 
														FROM te_propuesta_pago_detalle_transferencia 
														WHERE te_propuesta_pago_detalle_transferencia.tipo_documento <> 0) %s",
				$valCadBusq[0],
				$valCadBusq[0],
				$sqlBusq);
  
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"2%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "20%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "5%", $pageNum, "numero_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero");
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "5%", $pageNum, "numero_control_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Control");
		
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "20%", $pageNum, "observacion_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "5%", $pageNum, "fecha_origen_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listarNotaCargo", "5%", $pageNum, "saldo_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNotaCargo('".$row['id_notacargo']."');\" title=\"Seleccionar Nota Cargo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_notacargo']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_control_notacargo']."</td>";
			$htmlTb .= "<td align=\"left\" class=\"texto_9px\">".utf8_encode($row['observacion_notacargo'])."</td>";
			$htmlTb .= "<td>".date("d-m-Y",strtotime($row['fecha_origen_notacargo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['saldo_notacargo']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	
	}
		  
	$objResponse->assign("tdContenidoDocumento","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	$objResponse->script("document.getElementById('tblFacturasNcargos').style.display = '';");
	$objResponse->script("
		if (document.getElementById('divFlotante3').style.display == 'none') {
			document.getElementById('divFlotante3').style.display = '';
			centrarDiv(document.getElementById('divFlotante3'));
			
			document.forms['frmBuscarDocumento'].reset();
			document.getElementById('txtCriterioBusqNotaCargo').focus();
		}
	");
	
	return $objResponse;	
}

function verificarRetencionISLR($idDocumento, $tipoDocumento){
	$objResponse = new xajaxResponse();
	
	if($tipoDocumento == 0){//FACTURA
		$tipoDocumentoPago = "FA";
	}elseif($tipoDocumento == 1){//NOTA DE CARGO
		$tipoDocumentoPago = "ND";
	}
	
	$query = sprintf("SELECT te_retenciones.descripcion
						FROM cp_pagos_documentos pago
						INNER JOIN te_retencion_cheque ON te_retencion_cheque.id_retencion_cheque = pago.id_documento
						INNER JOIN te_retenciones ON te_retencion_cheque.id_retencion = te_retenciones.id
						WHERE pago.tipo_pago = 'ISLR' 
						AND pago.estatus = 1 
						AND pago.tipo_documento_pago = %s 
						AND pago.id_documento_pago = %s",
				valTpDato($tipoDocumentoPago,"text"),
				valTpDato($idDocumento,"int"));				
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$row = mysql_fetch_assoc($rs);
	
	if(mysql_num_rows($rs) == 0){//sino tiene retencion, permitir agregar
		$objResponse->script("document.getElementById('selRetencionISLR').disabled = false;
					  		calcularRetencion()");							
	}else{
		$descripcionRetencion = "El documento ya posee retenci&oacute;n: <br><b>".utf8_encode($row['descripcion'])."</b>";
		$objResponse->assign("selRetencionISLR","value","1");
		$objResponse->script("document.getElementById('selRetencionISLR').disabled = true;
							xajax_asignarDetallesRetencion(1);");//lo coloca en 0 si ya habia monto
	}
	
	$objResponse->assign("tdInfoRetencionISLR","innerHTML",$descripcionRetencion);
		
	return $objResponse;
}

function buscarCliente1($valform,$pro_bene) {
	$objResponse = new xajaxResponse();
		
	if($pro_bene==1){	
		$valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']);
		$objResponse->loadCommands(listarProveedores1(0, "", "", $valBusq));
	}elseif($pro_bene=="0"){
		$valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']);
		$objResponse->loadCommands(listarBeneficiarios1(0, "", "", $valBusq));
	}else{//boton buscar pro_bene es null porque no se envia
		if($valform['buscarProv']=="1"){
			$valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']);
			$objResponse->loadCommands(listarProveedores1(0, "", "", $valBusq));
		}elseif($valform['buscarProv']=="2"){
			$valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']);
			$objResponse->loadCommands(listarBeneficiarios1(0, "", "", $valBusq));
		}
	}
	return $objResponse;
}

function asignarProveedor1($id_proveedor){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$id_proveedor."'";
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddBePro","value",$row['id_proveedor']);
	$objResponse->assign("hddSelBePro","value",'1');
	$objResponse->assign("txtBePro","value",  utf8_encode($row['nombre']));
    $objResponse->script("document.getElementById('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarBeneficiario1($id_beneficiario){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_beneficiarios WHERE id_beneficiario = '".$id_beneficiario."'";
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddBePro","value",$row['id_beneficiario']);
	$objResponse->assign("hddSelBePro","value",'0');
	$objResponse->assign("txtBePro","value",  utf8_encode($row['nombre_beneficiario']));
    $objResponse->script("document.getElementById('divFlotante1').style.display = 'none';");             
	
	return $objResponse;
}

function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
        
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa);
	if(!$rsLimitEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa);
		if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsEmpresa);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");			
	$htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitEmpresa)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$rowBanco['id_empresa_reg']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombre_empresa']." - ".$rowBanco['nombre_empresa_suc'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listEmpresa(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdDescripcionArticulo","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("document.getElementById('divFlotante2').style.display = '';
							  document.getElementById('tblListados2').style.display = '';
							  document.getElementById('tdFlotanteTitulo2').innerHTML = 'Seleccione Empresa';
							  centrarDiv(document.getElementById('divFlotante2'))");	
	return $objResponse;
}	
function asignarEmpresa($idEmpresa){
	$objResponse = new xajaxResponse();
	if ($idEmpresa=='')
	$idEmpresa=$_SESSION['idEmpresaUsuarioSysGts'];
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
	$nombreSucursal = "";
	
	if ($rowEmpresa['id_empresa_padre_suc'] > 0){
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
	}
	
	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
	
	$objResponse->assign("txtNombreEmpresa","value",$empresa);
	$objResponse->assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	$objResponse->script("document.getElementById('divFlotante2').style.display = 'none';");
	
	return $objResponse;
}

function asignarEmpresa2($idEmpresa){
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
	$nombreSucursal = "";
	
	if ($rowEmpresa['id_empresa_padre_suc'] > 0){
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
	}
		
	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
	
	$objResponse->assign("txtNombreEmpresa2","value",$empresa);
	$objResponse->assign("hddIdEmpresa2","value",$rowEmpresa['id_empresa_reg']);
	
	//$objResponse->script("document.getElementById('divFlotante').style.display = '';");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarFactura");
$xajax->register(XAJAX_FUNCTION,"asignarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"buscarRetenciones");
$xajax->register(XAJAX_FUNCTION,"buscarDocumento");
$xajax->register(XAJAX_FUNCTION,"comboRetencionISLR");
$xajax->register(XAJAX_FUNCTION,"guardarRetencion");
$xajax->register(XAJAX_FUNCTION,"listadoRetenciones");
$xajax->register(XAJAX_FUNCTION,"listarBeneficiarios1");
$xajax->register(XAJAX_FUNCTION,"listarProveedores1");
$xajax->register(XAJAX_FUNCTION,"listarFacturas");
$xajax->register(XAJAX_FUNCTION,"listarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente1");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor1");
$xajax->register(XAJAX_FUNCTION,"asignarBeneficiario1");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa2");
$xajax->register(XAJAX_FUNCTION,"verificarRetencionISLR");

function tipoDocumento($id){
	
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$id);

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	if($row['tipo_documento'] == 'NC'){
		$queryNC = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'", $row['id_documento']);
		$rsNC = mysql_query($queryNC);
		if (!$rsNC) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowNC = mysql_fetch_array($rsNC);
		
		if($rowNC['tipo_nota_credito'] == '1'){
			$respuesta = "NC";
		}else if($rowNC['tipo_nota_credito'] == '2'){
			$respuesta = "NC/TD";
		}else if($rowNC['tipo_nota_credito'] == '3'){
			$respuesta = "NC/TC";
		}
	}
	
	if($row['tipo_documento'] == 'ND'){
		$respuesta = "ND";
	}
	if($row['tipo_documento'] == 'TR'){
		$respuesta = "TR";
	}
	if($row['tipo_documento'] == 'CH'){
		$respuesta = "CH";
	}
	if($row['tipo_documento'] == 'DP'){
		$respuesta = "DP";
	}
	
	return $respuesta;
}

?>