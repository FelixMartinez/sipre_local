<?php

function actualizarDatos($frmAplicarDesaplicarDcto, $valFormListadoEstadoCuenta, $valFormListadoEstadoCuenta1){
	$objResponse = new xajaxResponse();
	
	$idEstadoCuenta = $frmAplicarDesaplicarDcto["hddIdEstadoCuenta"];
	$fechaAplicar = date("Y-m-d", strtotime($frmAplicarDesaplicarDcto["txtFechaAplicar"]));	
	
	if(!xvalidaAcceso($objResponse,"te_documentos_aplicados","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = %s", 
						valTpDato($idEstadoCuenta, "int"));
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEstadoCuenta = mysql_fetch_array($rsEstadoCuenta);
	
	$queryAuditoria = sprintf("INSERT INTO te_auditoria_aplicacion(id_estado_de_cuenta, id_usuario, tipo_accion, fecha_cambio, observacion) VALUE (%s, %s, 1, NOW(), %s)",
								$rowEstadoCuenta['id_estado_cuenta'],
								$_SESSION['idUsuarioSysGts'],
								valTpDato($frmAplicarDesaplicarDcto["txtObservacion"], "text"));
	$rsAuditoria = mysql_query($queryAuditoria);
	if (!$rsAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if($rowEstadoCuenta['tipo_documento'] == 'DP' ){
		$queryDeposito = sprintf("UPDATE te_depositos SET estado_documento = '%s', fecha_aplicacion = %s 
									WHERE id_deposito = %s",
									valTpDato(2, "int"),
									valTpDato($fechaAplicar, "date"),
									valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', fecha_registro = %s
											WHERE id_estado_cuenta = %s",
											valTpDato(2, "int"),
											valTpDato($fechaAplicar, "date"),
											valTpDato($idEstadoCuenta, "int"));
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'ND' ){
		$queryDeposito = sprintf("UPDATE te_nota_debito SET estado_documento = '%s', fecha_aplicacion = %s 
									WHERE id_nota_debito = %s",
									valTpDato(2, "int"),
									valTpDato($fechaAplicar, "date"),
									valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', fecha_registro = %s
												WHERE id_estado_cuenta = %s",
												valTpDato(2, "int"),
												valTpDato($fechaAplicar, "date"),
												valTpDato($idEstadoCuenta, "int"));
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'TR' ){
		$queryDeposito = sprintf("UPDATE te_transferencia SET estado_documento = '%s', fecha_aplicacion = %s 
									WHERE id_transferencia = %s",
									valTpDato(2, "int"),
									valTpDato($fechaAplicar, "date"),
									valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', fecha_registro = %s
												WHERE id_estado_cuenta = %s",
												valTpDato(2, "int"),
												valTpDato($fechaAplicar, "date"),
												valTpDato($idEstadoCuenta, "int"));
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'NC' ){
		$queryDeposito = sprintf("UPDATE te_nota_credito SET estado_documento = '%s', fecha_aplicacion = %s 
									WHERE id_nota_credito = %s",
									valTpDato(2, "int"),
									valTpDato($fechaAplicar, "date"),
									valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = %s, fecha_registro = %s 
												WHERE id_estado_cuenta = %s",
												valTpDato(2, "int"),
												valTpDato($fechaAplicar, "date"),
												valTpDato($idEstadoCuenta, "int"));
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'CH' ){
		$queryDeposito = sprintf("UPDATE te_cheques SET estado_documento = '%s', fecha_aplicacion = %s 
									WHERE id_cheque = %s",
									valTpDato(2, "int"),
									valTpDato($fechaAplicar, "date"),
									valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', fecha_registro = %s 
												WHERE id_estado_cuenta = %s",
												valTpDato(2, "int"),
												valTpDato($fechaAplicar, "date"),
												valTpDato($idEstadoCuenta, "int"));
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

	}
	
	$objResponse->script(sprintf("xajax_listadoEstadoCuenta('%s','%s','%s','%s')",
		$valFormListadoEstadoCuenta['pageNum'],
		$valFormListadoEstadoCuenta['campOrd'],
		$valFormListadoEstadoCuenta['tpOrd'],
		$valFormListadoEstadoCuenta['valBusq']));
	
	$objResponse->script(sprintf("xajax_listadoEstadoCuentaAplicados('%s','%s','%s','%s')",
		$valFormListadoEstadoCuenta1['pageNum'],
		$valFormListadoEstadoCuenta1['campOrd'],
		$valFormListadoEstadoCuenta1['tpOrd'],
		$valFormListadoEstadoCuenta1['valBusq']));
												   
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function actualizarDatosDesAplicar($frmAplicarDesaplicarDcto,$valFormListadoEstadoCuenta,$valFormListadoEstadoCuenta1){
	$objResponse = new xajaxResponse();
	
	if(!xvalidaAcceso($objResponse,"te_documentos_aplicados","editar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$frmAplicarDesaplicarDcto['hddIdEstadoCuenta']);
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEstadoCuenta = mysql_fetch_array($rsEstadoCuenta);
	
	$queryAuditoria = sprintf("INSERT INTO te_auditoria_aplicacion(id_estado_de_cuenta, id_usuario, tipo_accion, fecha_cambio, observacion) VALUE ('%s','%s', 0, NOW(),'%s')",
								$rowEstadoCuenta['id_estado_cuenta'],
								$_SESSION['idUsuarioSysGts'],
								$frmAplicarDesaplicarDcto['txtObservacion']);
	$rsAuditoria = mysql_query($queryAuditoria);
	if (!$rsAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if($rowEstadoCuenta['tipo_documento'] == 'DP'){
		$queryDeposito = sprintf("UPDATE te_depositos SET estado_documento = '%s' WHERE id_deposito = %s",
									1,
									valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s' WHERE id_estado_cuenta = %s",1,$frmAplicarDesaplicarDcto['hddIdEstadoCuenta']);
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'ND' ){
		$queryDeposito = sprintf("UPDATE te_nota_debito SET estado_documento = '%s' WHERE id_nota_debito = %s",
									1,
									valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s' WHERE id_estado_cuenta = %s",1,$frmAplicarDesaplicarDcto['hddIdEstadoCuenta']);
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }		
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'TR' ){
		$queryDeposito = sprintf("UPDATE te_transferencia SET estado_documento = '%s' WHERE id_transferencia = %s",
								1,
								valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s' WHERE id_estado_cuenta = %s",1,$frmAplicarDesaplicarDcto['hddIdEstadoCuenta']);
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'NC' ){
		$queryDeposito = sprintf("UPDATE te_nota_credito SET estado_documento = '%s' WHERE id_nota_credito = %s",
								1,
								valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s' WHERE id_estado_cuenta = %s",1,$frmAplicarDesaplicarDcto['hddIdEstadoCuenta']);
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'CH' ){
		$queryDeposito = sprintf("UPDATE te_cheques SET estado_documento = '%s' WHERE id_cheque = %s",
								1,
								valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rsDeposito = mysql_query($queryDeposito);
		if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s' WHERE id_estado_cuenta = %s",1,$frmAplicarDesaplicarDcto['hddIdEstadoCuenta']);
		$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
		if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	$objResponse->script(sprintf("xajax_listadoEstadoCuenta('%s','%s','%s','%s')",
		$valFormListadoEstadoCuenta['pageNum'],
		$valFormListadoEstadoCuenta['campOrd'],
		$valFormListadoEstadoCuenta['tpOrd'],
		$valFormListadoEstadoCuenta['valBusq']));
	
	$objResponse->script(sprintf("xajax_listadoEstadoCuentaAplicados('%s','%s','%s','%s')",
		$valFormListadoEstadoCuenta1['pageNum'],
		$valFormListadoEstadoCuenta1['campOrd'],
		$valFormListadoEstadoCuenta1['tpOrd'],
		$valFormListadoEstadoCuenta1['valBusq']));
												   
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function asignarBanco($id_banco){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("byId('divFlotante').style.display = 'none'");	
	
	$query = "SELECT * FROM bancos WHERE idBanco = '".$id_banco."'";
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtNombreBanco","value",  utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmBuscar'))");
	
	return $objResponse;
}


function asignarEmpresa($idEmpresa){
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
	$nombreSucursal = "";
	
	if ($rowEmpresa['id_empresa_padre_suc'] > 0){
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
	}
	
	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
	
	$objResponse->assign("txtNombreEmpresa","value",$empresa);
	$objResponse->assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	$objResponse->script("byId('divFlotante').style.display = 'none';");
	
	return $objResponse;
}

function buscarEstadoCuenta($valForm) {
	$objResponse = new xajaxResponse();
	
	$fecha = date("m-Y");
	
	$objResponse->script(sprintf("xajax_listadoEstadoCuenta(0,'','','%s' + '|' + '%s' + '|' + '%s' + '|' + '%s');",
		$valForm['hddIdEmpresa'],
		$valForm['selCuenta'],
		$fecha,//los no aplicado no tienen filtro fecha, estan en NULL
		$valForm['selEstado']
		));
		
	$objResponse->script(sprintf("xajax_listadoEstadoCuentaAplicados(0,'','','%s' + '|' + '%s' + '|' + '%s' + '|' + '%s' + '|' + '%s' + '|' + '%s');",
		$valForm['hddIdEmpresa'],
		$valForm['selCuenta'],
		$fecha,
		$valForm['selEstado'],
		$valForm['fechaAplicada1'],
		$valForm['fechaAplicada2']
		));	
	//$objResponse ->alert($valForm['selCuenta']);
	
	//$objResponse->script(sprintf("xajax_listadoEstadoCuenta(0,'','');"));
	
	return $objResponse;
}

function comboCuentas($valForm){
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdBanco'] == -1){
		$disabled = "disabled=\"disabled\"";
	}else{
		$condicion = "WHERE idBanco = '".$valForm['hddIdBanco']."' AND id_empresa = '".$valForm['hddIdEmpresa']."' ";
		$disabled = "";
	}
	
	$queryCuentas = "SELECT * FROM cuentas ".$condicion."";
	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$totalCuentas = mysql_num_rows($rsCuentas);
	
	$selected = "";
	if($totalCuentas == 1){
		$selected = "selected=\"selected\"";		
	}
	
	$html = "<select id=\"selCuenta\" name=\"selCuenta\" ".$disabled." class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
	$html .= "<option value=\"-1\">Seleccione</option>";
		while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
			$html .= "<option value=\"".$rowCuentas['idCuentas']."\" ".$selected." >".$rowCuentas['numeroCuentaCompania']."</option>";
		}
	$html .= "</select>";
	
	$objResponse->assign("tdSelCuenta","innerHTML",$html);
	if($totalCuentas == 1){
		$objResponse->script("byId('btnBuscar').click();");
	}
	return $objResponse;
}

function listadoEstadoCuenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] == ''){
		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
	}else if ($valCadBusq[0] != ''){
		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$valCadBusq[0]."'";
	}
		
	if ($valCadBusq[1] != 0){
		$sqlBusq .= " AND te_estado_cuenta.id_cuenta = '".$valCadBusq[1]."'";
	}
	
	/*if ($valCadBusq[2] != '')
		$sqlBusq .= " AND DATE_FORMAT(te_estado_cuenta.fecha_registro,'%Y/%m') = '".date("Y/m",strtotime('01-'.$valCadBusq[2]))."'";*/
		
	if($valCadBusq[3] == 1){
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$valCadBusq[3]."'";
	}
	/*else if($valCadBusq[3] == 2)
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$valCadBusq[3]."'";*/
	
	$query = sprintf("SELECT 
						te_estado_cuenta.id_estado_cuenta,
						te_estado_cuenta.tipo_documento,
						te_estado_cuenta.id_documento,
						te_estado_cuenta.fecha_registro,
						te_estado_cuenta.id_cuenta,
						te_estado_cuenta.id_empresa,
						te_estado_cuenta.monto,
						te_estado_cuenta.suma_resta,
						te_estado_cuenta.numero_documento,
						te_estado_cuenta.desincorporado,
						te_estado_cuenta.observacion,
						te_estado_cuenta.estados_principales,
						DATE_FORMAT(te_estado_cuenta.fecha_registro,'%s') as fecha_registro_formato,
						#SOLO PARA ORDENAMIENTO
						if(suma_resta = 0, monto, 0)as debito,                                                  
						if(suma_resta = 1, monto, 0)as credito      
					FROM te_estado_cuenta
					WHERE te_estado_cuenta.desincorporado <> 0 AND te_estado_cuenta.estados_principales = 1",'%d-%m-%Y %h:%i %p').$sqlBusq;
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	//$htmlTableIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "8%", $pageNum, "fecha_registro_formato", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Documento");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "5%", $pageNum, "estados_principales", $campOrd, $tpOrd, $valBusq, $maxRows, "Estados");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "15%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "20%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "3%", $pageNum, "debito", $campOrd, $tpOrd, $valBusq, $maxRows, "D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "3%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Cr&eacute;dito");
		$htmlTh .="<td width=\"3%\"></td>
                            <td width=\"3%\"></td>";
		$htmlTh .= "</tr>";
                
	$conta = 0;
	$contb = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {		
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$fechaNull = date("d/m/Y",strtotime($row['fecha_registro_formato']));
		if($fechaNull == "31/12/1969"){ 
			$fechaNull = "-";
		}
                 
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".$fechaNull."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";
			$htmlTb .= "<td align=\"center\">".estadoNota($row['estados_principales'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_documento']."</td>";
			if($row['suma_resta'] == 0){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$conta +=  $row['monto'];
			}else{
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			}
			if($row['suma_resta'] == 1){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$contb +=  $row['monto'];
			}else{
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			}
			$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"xajax_formAplicarDesaplicarDcto(".$row['id_estado_cuenta'].",2);\" src=\"../img/iconos/ico_agregar.gif\" /></td>";
			$queryAuditoriaM = sprintf("SELECT * FROM te_auditoria_aplicacion WHERE id_estado_de_cuenta = '%s'",$row['id_estado_cuenta']);
			$rsAuditoriaM = mysql_query($queryAuditoriaM);
			if (!$rsAuditoriaM){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowAuditoriaM = mysql_fetch_array($rsAuditoriaM);
			
			if($rowAuditoriaM['id_auditoria_aplicacion'] == ''){
				$htmlTb .= "<td align=\"center\" ><img src=\"../img/iconos/ico_comentario_f2.png\" /></td>";
			}else{
				$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"xajax_listAuditoria('0','','','".$row['id_estado_cuenta']."');\" src=\"../img/iconos/ico_comentario.png\" /></td>";
			}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
        $htmlTblFin .="<br><br>";        
	
	$queryTotales = sprintf("SELECT 
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.fecha_registro,
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.id_empresa,
		te_estado_cuenta.monto,
		te_estado_cuenta.suma_resta,
		te_estado_cuenta.numero_documento,
		te_estado_cuenta.desincorporado,
		te_estado_cuenta.observacion,
		te_estado_cuenta.estados_principales,
		DATE_FORMAT(te_estado_cuenta.fecha_registro,'%s') as fecha_registro_formato
	FROM
		te_estado_cuenta
	WHERE
		te_estado_cuenta.desincorporado <> '0' AND te_estado_cuenta.estados_principales = '1'",'%d-%m-%Y %h:%i %p').$sqlBusq;
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	while($rowTotales = mysql_fetch_array($rsTotales)){	
		if($rowTotales['suma_resta'] == 0){
			$contTotales1 +=  $rowTotales['monto'];
		}else if($rowTotales['suma_resta'] == 1){
			$contTotales2 +=  $rowTotales['monto'];
		}
	}
	
	$queryCuentas = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[1]);
	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuentas = mysql_fetch_array($rsCuentas);
	
	$htmlx.="<table align=\"right\" border=\"0\" width=\"60%\">";
		$htmlx.="<tr class=\"tituloColumna\">";
			$htmlx.="<td align=\"center\"></td>";
			$htmlx.="<td align=\"center\">D&eacute;bito</td>";
			$htmlx.="<td align=\"center\">Cr&eacute;dito</td>";
			//$htmlx.="<td width=\"10%\">".htmlentities("Saldo")."</td>";
		$htmlx.="</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($contTotales1,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($contTotales2,'2','.',',')."</td>";
			//$htmlx.="<td align=\"right\" >".number_format($rowCuentas['saldo_tem'],'2','.',',')."</td>";
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdListadoEstadoCuenta","innerHTML",$htmlSalIni.$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx);
	
	return $objResponse;
}

function listadoEstadoCuentaAplicados($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	/*if ($valCadBusq[0] == ''){
		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
	
	}else*/ 
	if ($valCadBusq[0] != ''){
		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$valCadBusq[0]."'";
	}
	
	if ($valCadBusq[1] != 0){
		$sqlBusq .= " AND te_estado_cuenta.id_cuenta = '".$valCadBusq[1]."'";
	}
		
	/*if ($valCadBusq[2] != '00-00-000')
		$sqlBusq .= " AND DATE_FORMAT(te_estado_cuenta.fecha_registro,'%m-%Y') = '".$valCadBusq[2]."'";*/
		
	if($valCadBusq[3] == 2){
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$valCadBusq[3]."'";
	}
	/*else if($valCadBusq[3] == 2)
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$valCadBusq[3]."'";*/
        
	if($valCadBusq[4] !="" && $valCadBusq[5] !=""){
		$fecha1 = date("Y-m-d",strtotime($valCadBusq[4]));
		$fecha2 = date("Y-m-d",strtotime($valCadBusq[5]));
	$sqlBusq .= sprintf(" AND DATE(te_estado_cuenta.fecha_registro) BETWEEN '%s' AND '%s'",
					$fecha1,
					$fecha2);
	}
	
	$query = sprintf("SELECT 
						te_estado_cuenta.id_estado_cuenta,
						te_estado_cuenta.tipo_documento,
						te_estado_cuenta.id_documento,
						te_estado_cuenta.fecha_registro,
						te_estado_cuenta.id_cuenta,
						te_estado_cuenta.id_empresa,
						te_estado_cuenta.monto,
						te_estado_cuenta.suma_resta,
						te_estado_cuenta.numero_documento,
						te_estado_cuenta.desincorporado,
						te_estado_cuenta.observacion,
						te_estado_cuenta.estados_principales,
						DATE_FORMAT(te_estado_cuenta.fecha_registro,'%s') as fecha_registro_formato,
						#SOLO PARA ORDENAMIENTO
						if(suma_resta = 0, monto, 0)as debito,                                                  
						if(suma_resta = 1, monto, 0)as credito                                              
					FROM te_estado_cuenta
					WHERE te_estado_cuenta.desincorporado <> 0 
					AND te_estado_cuenta.estados_principales = 2",'%d-%m-%Y %h:%i %p').$sqlBusq;
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$querySaldoIni = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[1]);
	$rsSaldoIni = mysql_query($querySaldoIni);
	if (!$rsSaldoIni){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowSaldoIni = mysql_fetch_array($rsSaldoIni);
	
	$htmlSalIni = "<table border=\"0\" width=\"100%\">";
	$htmlSalIni .= "<tr>";
		$htmlSalIni .= "<td align=\"right\">";
			$htmlSalIni .= "<table class=\"tabla\" border=\"0\" cellpadding=\"2\">";
			$htmlSalIni .= "<tr align=\"right\">";
				$htmlSalIni .= "<td width=\"100\" style=\"border:none;\" class=\"tituloCampo\">Saldo:</td>";
				$htmlSalIni .= "<td><input style=\"text-align:right\" class=\"trResaltarTotal\" type=\"text\" id=\"txtSaldoInicial\" name=\"txtSaldoInicial\" size=\"25\" readonly=\"readonly\" value = \"".$rowSaldoIni['saldo']."\"/></td>";
			$htmlSalIni .= "</tr>";
			$htmlSalIni .= "</table>";
		$htmlSalIni .= "</td>";
	$htmlSalIni .= "</tr>";
	$htmlSalIni .= "</table>";
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "8%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Documento");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "5%", $pageNum, "estados_principales", $campOrd, $tpOrd, $valBusq, $maxRows, "Estados");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "15%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "20%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "3%", $pageNum, "debito", $campOrd, $tpOrd, $valBusq, $maxRows, "D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "3%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Cr&eacute;dito");
		$htmlTh .="<td width=\"3%\"></td>
                            <td width=\"3%\"></td>";
		$htmlTh .= "</tr>";
                
	$conta = 0;
	$contb = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".date("d/m/Y",strtotime($row['fecha_registro_formato']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";
			$htmlTb .= "<td align=\"center\">".estadoNota($row['estados_principales'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_documento']."</td>";
			if($row['suma_resta'] == 0){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$conta +=  $row['monto'];
			}else
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			if($row['suma_resta'] == 1){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$contb +=  $row['monto'];
			}else
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"xajax_formAplicarDesaplicarDcto(".$row['id_estado_cuenta'].",1);\" src=\"../img/iconos/ico_quitar.gif\" /></td>";
			$queryAuditoriaM = sprintf("SELECT * FROM te_auditoria_aplicacion WHERE id_estado_de_cuenta = '%s'",$row['id_estado_cuenta']);
			$rsAuditoriaM = mysql_query($queryAuditoriaM);
			if (!$rsAuditoriaM){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowAuditoriaM = mysql_fetch_array($rsAuditoriaM);
			if($rowAuditoriaM['id_auditoria_aplicacion'] == ''){
				$htmlTb .= "<td align=\"center\" ><img src=\"../img/iconos/ico_comentario_f2.png\" /></td>";
			}
			else{
				$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"xajax_listAuditoria('0','','','".$row['id_estado_cuenta']."');\" src=\"../img/iconos/ico_comentario.png\" /></td>";
			}
				
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s);\">%s</a>",
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
						  te_estado_cuenta.id_estado_cuenta,
						  te_estado_cuenta.tipo_documento,
						  te_estado_cuenta.id_documento,
						  te_estado_cuenta.fecha_registro,
						  te_estado_cuenta.id_cuenta,
						  te_estado_cuenta.id_empresa,
						  te_estado_cuenta.monto,
						  te_estado_cuenta.suma_resta,
						  te_estado_cuenta.numero_documento,
						  te_estado_cuenta.desincorporado,
						  te_estado_cuenta.observacion,
						  te_estado_cuenta.estados_principales,
						  DATE_FORMAT(te_estado_cuenta.fecha_registro,'%s') as fecha_registro_formato
						FROM
						  te_estado_cuenta
						WHERE
						  te_estado_cuenta.desincorporado <> '0' AND te_estado_cuenta.estados_principales = '2'",'%d-%m-%Y %h:%i %p').$sqlBusq;
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	while($rowTotales = mysql_fetch_array($rsTotales)){	
		if($rowTotales['suma_resta'] == 0){
			$contTotales1 +=  $rowTotales['monto'];
		}else if($rowTotales['suma_resta'] == 1){
			$contTotales2 +=  $rowTotales['monto'];
		}
	}
	
	$queryCuentas = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[1]);
	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuentas = mysql_fetch_array($rsCuentas);
		
	$saldoTotal = ($rowCuentas['saldo'] + $contTotales2) - $contTotales1; 
	
	$htmlx.="<table align=\"right\" border=\"0\" width=\"60%\">";
		$htmlx.="<tr class=\"tituloColumna\">
					<td width=\"20%\"></td>
					<td align=\"center\">"."D&eacute;bito"."</td>
					<td align=\"center\">"."Cr&eacute;dito"."</td>
					<td align=\"center\">"."Saldo"."</td>
				</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($contTotales1,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($contTotales2,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($saldoTotal,'2','.',',')."</td>";
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoEstadoCuenta1","innerHTML",$htmlSalIni.$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx);
		
	return $objResponse;
}

function listBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT bancos.* 
					FROM bancos 
					INNER JOIN cuentas ON bancos.idBanco = cuentas.idBanco
					WHERE bancos.idBanco != '1' 
					GROUP BY bancos.idBanco";
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitBanco = sprintf(" %s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitBanco = mysql_query($queryLimitBanco);
	if(!$rsLimitBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco);
		if(!$rsBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsBanco);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
                $htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
                $htmlTh .= ordenarCampo("xajax_listBanco", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
                $htmlTh .= ordenarCampo("xajax_listBanco", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
                $htmlTh .= ordenarCampo("xajax_listBanco", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");					
        $htmlTh .= "</tr>";
	
	while ($rowBanco = mysql_fetch_assoc($rsLimitBanco)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarBanco('".$rowBanco['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['sucursal'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listBanco(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdTabla","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	$objResponse->script("byId('divFlotante').style.display = '';
							  byId('tblListados').style.display = '';
							  byId('tblAplicacionDesaplicacion').style.display = 'none';
							  byId('tdFlotanteTitulo').innerHTML = 'Seleccione Banco';
							  centrarDiv(byId('divFlotante'))");	
	return $objResponse;
}

function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
			
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ",$_SESSION['idUsuarioSysGts']);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa);
	if(!$rsLimitEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa);
		if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
				
	$objResponse->assign("tdTabla","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	$objResponse->script("byId('divFlotante').style.display = '';
							  byId('tblListados').style.display = '';
							  byId('tblAplicacionDesaplicacion').style.display = 'none';
							  byId('tdFlotanteTitulo').innerHTML = 'Seleccione Empresa';
							  centrarDiv(byId('divFlotante'))");
	return $objResponse;
}

function formAplicarDesaplicarDcto($idEstadoCuenta, $accionAplicarDesaplicar) {
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT 
					te_estado_cuenta.numero_documento AS numero_documento_estado_cuenta,
					te_estado_cuenta.tipo_documento AS tipo_documento_estado_cuenta,
					te_estado_cuenta.fecha_registro AS fecha_aplicacion_estado_cuenta,
					
					CASE te_estado_cuenta.tipo_documento
						WHEN 'DP' THEN (SELECT fecha_registro FROM te_depositos WHERE id_deposito = te_estado_cuenta.id_documento)
						WHEN 'ND' THEN (SELECT fecha_registro FROM te_nota_debito WHERE id_nota_debito = te_estado_cuenta.id_documento)
						WHEN 'NC' THEN (SELECT fecha_registro FROM te_nota_credito WHERE id_nota_credito = te_estado_cuenta.id_documento)
						WHEN 'CH' THEN (SELECT fecha_registro FROM te_cheques WHERE id_cheque = te_estado_cuenta.id_documento)
						WHEN 'TR' THEN (SELECT fecha_registro FROM te_transferencia WHERE id_transferencia = te_estado_cuenta.id_documento)
						ELSE ''
					END AS fecha_registro_documento
						
				FROM te_estado_cuenta 
				WHERE id_estado_cuenta = %s",
			valTpDato($idEstadoCuenta, "int"));
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign();
	
	$objResponse->script("
		document.forms['frmAplicarDesaplicarDcto'].reset();
		byId('tblAplicacionDesaplicacion').style.display = '';
		byId('tblListados').style.display = 'none';
		byId('divFlotante').style.display = '';
		centrarDiv(byId('divFlotante'));
		byId('txtObservacion').focus();
	");
	
	if($accionAplicarDesaplicar == 1){// 1 = Desaplicar, 2 = Aplicar
		$titulo = "Desaplicaci&oacute;n de documento";
		$objResponse->script("byId('trFechaAplicar').style.display = 'none';");
	}else if ($accionAplicarDesaplicar == 2){		
		$titulo = "Aplicaci&oacute;n de documento";
		$objResponse->script("byId('trFechaAplicar').style.display = '';
		byId('txtObservacion').value = 'DOCUMENTO APLICADO'");
	}
	
	$objResponse->assign('txtNroDocumento','value',$row["numero_documento_estado_cuenta"]);
	$objResponse->assign('txtTipoDocumento','value',$row["tipo_documento_estado_cuenta"]);
	$objResponse->assign('txtFechaRegistro','value',fecha($row["fecha_registro_documento"]));
	$objResponse->assign('txtFechaAplicado','value',fecha($row["fecha_aplicacion_estado_cuenta"]));
	$objResponse->assign('txtFechaAplicar','value',fecha($row["fecha_aplicacion_estado_cuenta"]));
	
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML",$titulo);
	$objResponse->assign('hddAccAplicarDesaplicar','value',$accionAplicarDesaplicar);
	$objResponse->assign('hddIdEstadoCuenta','value',$idEstadoCuenta);
	
	return $objResponse;
}

function listAuditoria($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$queryAuditoria = sprintf("SELECT * FROM te_auditoria_aplicacion WHERE id_estado_de_cuenta = '%s'",$valBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitAuditoria = sprintf(" %s %s LIMIT %d OFFSET %d", $queryAuditoria, $sqlOrd, $maxRows, $startRow);        
	
	$rsLimitAuditoria = mysql_query($queryLimitAuditoria);
	if(!$rsLimitAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsAuditoria = mysql_query($queryAuditoria);
		if(!$rsAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsAuditoria);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listAuditoria", "5%", $pageNum, "fecha_cambio", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listAuditoria", "15%", $pageNum, "id_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, "Ususario");			
		$htmlTh .= ordenarCampo("xajax_listAuditoria", "5%", $pageNum, "tipo_accion", $campOrd, $tpOrd, $valBusq, $maxRows, "Acci&oacute;n");			
		$htmlTh .= ordenarCampo("xajax_listAuditoria", "40%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaciones");			
	$htmlTh .= "</tr>";
	
	
	while ($rowAuditoria = mysql_fetch_assoc($rsLimitAuditoria)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		if($rowAuditoria['tipo_accion'] == 1){
			$tipoAccion = "Aplicado";
		}else{
			$tipoAccion = "Desaplicado";
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".date("d/m/Y",strtotime($rowAuditoria['fecha_cambio']))."</td>";
			$htmlTb .= "<td align=\"center\">".usuario($rowAuditoria['id_usuario'])."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoAccion."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowAuditoria['observacion'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listAuditoria(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
		
	$objResponse->assign("tdTabla","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("byId('divFlotante').style.display = '';
							  byId('tblListados').style.display = '';
							  byId('tblAplicacionDesaplicacion').style.display = 'none';
							  byId('tdFlotanteTitulo').innerHTML = 'Comentarios';
							  centrarDiv(byId('divFlotante'))");
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"listadoEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"listadoEstadoCuentaAplicados");
$xajax->register(XAJAX_FUNCTION,"listBanco");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"actualizarDatos");
$xajax->register(XAJAX_FUNCTION,"actualizarDatosDesAplicar");
$xajax->register(XAJAX_FUNCTION,"formAplicarDesaplicarDcto");
$xajax->register(XAJAX_FUNCTION,"listAuditoria");

function estadoNota($id){
	$query = sprintf("SELECT * FROM te_estados_principales WHERE id_estados_principales = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['id_estados_principales'] == 1){
		$respuesta = "<img src=\"../img/iconos/ico_rojo.gif\">";
	}else if($row['id_estados_principales'] == 2){
		$respuesta = "<img src=\"../img/iconos/ico_amarillo.gif\">";
	}
	
	return $respuesta;
}

function usuario($id){	
	$query = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error($query));
	$row = mysql_fetch_array($rs);
	
	$respuesta = $row['nombre_empleado']." ".$row['apellido'];
	
	return utf8_encode($respuesta);	
}

function fecha($fecha){
	if($fecha != ""){
		$fecha = date("d-m-Y",strtotime($fecha));
	}
	return $fecha;
}

?>