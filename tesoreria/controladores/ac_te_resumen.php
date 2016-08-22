<?php
function comboAplicaDebito($aplicaDebito){
	$objResponse = new xajaxResponse();

	if ($aplicaDebito == 1){
		$uno = "selected=\"selected\"";
		$dos = "";
	}
	else{
		$uno = "";
		$dos = "selected=\"selected\"";
	}
	

	$html = "<select id='selAplicaDebito' name='selAplicaDebito'>
                 <option value='1' ".$uno.">NO</option>
				 <option value='0' ".$dos.">SI</option>
			 </select>";

	$objResponse->assign("tdSelAplicaDebito","innerHTML",$html);
	
	return $objResponse;
}

function comboBancos($idBanco,$idTd,$idSel,$onchange){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE nombreBanco <> '-'");
	$rs = mysql_query($query) or die(mysql_error());
		
	$html = "<select id=\"".$idSel."\" name=\"".$idSel."\" onchange=\"".$onchange."\">";
	$html .= "<option value=\"0\">Todos</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		if($row['idBanco'] == $idBanco)
			$selected = "selected='selected'";
		else
			$selected = "";
			$html .= "<option value=\"".$row['idBanco']."\" ".$selected.">".utf8_encode($row['nombreBanco'])."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function comboEmpresa(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY id_empresa_reg",$_SESSION['idUsuarioSysGts']);
		$rs = mysql_query($query) or die (mysql_error());
		$html = "<select id=\"selEmpresa\" name=\"selEmpresa\" onChange=\"$('btnBuscar').click();\">";
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

function comboEstatus($estatus){
	$objResponse = new xajaxResponse();
	
	if ($estatus == 1){
		$uno = "selected=\"selected\"";
		$dos = "";
	}
	else{
		$uno = "";
		$dos = "selected=\"selected\"";
	}
	
	$html = "<select id='selEstatus' name='selEstatus'>
                 <option value='1' ".$uno.">Activa</option>
				 <option value='0' ".$dos.">Inactiva</option>
			 </select>";
 
	$objResponse->assign("tdSelEstatus","innerHTML",$html);
	
	return $objResponse;
}

function comboMonedas($idMoneda){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM pg_monedas";
	$rs = mysql_query($query) or die(mysql_error());
			
	$html = "<select id=\"selMonedas\" name=\"selMonedas\">";
	while ($row = mysql_fetch_assoc($rs)) {
		if($row['idmoneda'] == $idMoneda)
			$selected = "selected='selected'";
		else
			$selected = "";
			$html .= "<option value=\"".$row['idmoneda']."\" ".$selected.">".  utf8_encode($row['descripcion'])."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelMonedas","innerHTML",$html);
	
	return $objResponse;
}

function guardarCuenta($formCuenta){
	$objResponse = new xajaxResponse();
	
	if ($formCuenta['hddIdCuenta'] == 0){
		$mensaje = "insertada";
		
		$query = "INSERT INTO `erp_tesoreria`.`cuentas` (`idCuentas`, `idBanco`, `numeroCuentaCompania`, `estatus`, `firma_electronica`, `nro_cuenta_contable`, `nro_cuenta_contable_contrapartida`, `nro_cuenta_contable_debitos`, `debito_bancario`, `tipo_cuenta`, `id_moneda`, `firma_1`, `firma_2`, `firma_3`, `firma_4`, `firma_5`, `firma_6`, `tipo_firma_1`, `tipo_firma_2`, `tipo_firma_3`, `tipo_firma_4`, `tipo_firma_5`, `tipo_firma_6`, `comb_1`, `comb_2`, `comb_3`, `restriccion_1`, `restriccion_2`, `restriccion_3`, `saldo`, `saldo_tem`) VALUES ('', '".$formCuenta['selBancoCuentaNueva']."', '".$formCuenta['txtNumeroCuenta']."', '".$formCuenta['selEstatus']."', '".$formCuenta['txtFirmaElectronica']."', '".$formCuenta['txtCuentaContable']."', '".$formCuenta['txtCuentaContableContrapartida']."', '".$formCuenta['txtCuentaDebitosBancarios']."', '".$formCuenta['selAplicaDebito']."', '".$formCuenta['selTipoCuenta']."', '".$formCuenta['selMonedas']."', '".$formCuenta['txtFirmante1']."', '".$formCuenta['txtFirmante2']."', '".$formCuenta['txtFirmante3']."', '".$formCuenta['txtFirmante4']."', '".$formCuenta['txtFirmante5']."', '".$formCuenta['txtFirmante6']."', '".$formCuenta['txtTipoFirmante1']."', '".$formCuenta['txtTipoFirmante2']."', '".$formCuenta['txtTipoFirmante3']."', '".$formCuenta['txtTipoFirmante4']."', '".$formCuenta['txtTipoFirmante5']."', '".$formCuenta['txtTipoFirmante6']."', '".$formCuenta['txtCombinacion1']."', '".$formCuenta['txtCombinacion2']."', '".$formCuenta['txtCombinacion3']."', '".$formCuenta['txtRestriccionCombinacion1']."', '".$formCuenta['txtRestriccionCombinacion2']."', '".$formCuenta['txtRestriccionCombinacion3']."', '".$formCuenta['txtSaldoLibros']."', '".$formCuenta['txtSaldoAnteriorConciliado']."');";
		
	}
	else{
		$mensaje = "modificada";
	
	
	$query = "UPDATE `erp_tesoreria`.`cuentas` SET 
		`idBanco` = '".$formCuenta['selBancoCuentaNueva']."',
		`numeroCuentaCompania` = '".$formCuenta['txtNumeroCuenta']."',
		`estatus` = '".$formCuenta['selEstatus']."',
		`firma_electronica` = '".$formCuenta['txtFirmaElectronica']."',
		`nro_cuenta_contable` = '".$formCuenta['txtCuentaContable']."',
		`nro_cuenta_contable_contrapartida` = '".$formCuenta['txtCuentaContableContrapartida']."',
		`nro_cuenta_contable_debitos` = '".$formCuenta['txtCuentaDebitosBancarios']."',
		`debito_bancario` = '".$formCuenta['selAplicaDebito']."',
		`tipo_cuenta` = '".$formCuenta['selTipoCuenta']."',
		`id_moneda` = '".$formCuenta['selMonedas']."',
		`firma_1` = '".$formCuenta['txtFirmante1']."',
		`firma_2` = '".$formCuenta['txtFirmante2']."',
		`firma_3` = '".$formCuenta['txtFirmante3']."',
		`firma_4` = '".$formCuenta['txtFirmante4']."',
		`firma_5` = '".$formCuenta['txtFirmante5']."',
		`firma_6` = '".$formCuenta['txtFirmante6']."',
		`tipo_firma_1` = '".$formCuenta['txtTipoFirmante1']."',
		`tipo_firma_2` = '".$formCuenta['txtTipoFirmante2']."',
		`tipo_firma_3` = '".$formCuenta['txtTipoFirmante3']."',
		`tipo_firma_4` = '".$formCuenta['txtTipoFirmante4']."',
		`tipo_firma_5` = '".$formCuenta['txtTipoFirmante5']."',
		`tipo_firma_6` = '".$formCuenta['txtTipoFirmante6']."',
		`comb_1` = '".$formCuenta['txtCombinacion1']."',
		`comb_2` = '".$formCuenta['txtCombinacion2']."',
		`comb_3` = '".$formCuenta['txtCombinacion3']."',
		`restriccion_1` = '".$formCuenta['txtRestriccionCombinacion1']."',
		`restriccion_2` = '".$formCuenta['txtRestriccionCombinacion2']."',
		`restriccion_3` = '".$formCuenta['txtRestriccionCombinacion3']."',
		`saldo` = '".$formCuenta['txtSaldoLibros']."',
		`saldo_tem` = '".$formCuenta['txtSaldoAnteriorConciliado']."' 
		WHERE `cuentas`.`idCuentas` = ".$formCuenta['hddIdCuenta']." LIMIT 1 ;";	
	}
	mysql_query($query) or die(mysql_error());
	
	$objResponse->script("xajax_listarCuentas(0,'','',''); $('divFlotante').style.display = 'none';");
						  
	$objResponse->alert("Cuenta ".$mensaje." exitosamente");
	
	return $objResponse;
}

function comboTipoCuenta($tipoCuenta){
	$objResponse = new xajaxResponse();
	
	if ($tipoCuenta == "Corriente"){
		$uno = "selected=\"selected\"";
		$dos = "";
	}
	else if ($tipoCuenta == "Ahorro"){
		$uno = "";
		$dos = "selected=\"selected\"";
	}
	
	$html = "<select id='selTipoCuenta' name='selTipoCuenta'>
                 <option value='Corriente' ".$uno.">Corriente</option>
				 <option value='Ahorro' ".$dos.">Ahorro</option>
			 </select>";
 
	$objResponse->assign("tdSelTipoCuenta","innerHTML",$html);
	
	return $objResponse;
}

function listarCuentas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_resumen")){
		$objResponse->assign("tdListaCuentas","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != 0){
		$condicion = "WHERE idBanco = '".$valCadBusq[0]."' AND ";
	}
	else{
		$condicion = "WHERE ";
	}
	
	if ($valCadBusq[2] != 0){
		if ($valCadBusq[2] == '-1'){
			$condicion .= "id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."' AND ";
		}
		else{
			$condicion .= "id_empresa = '".$valCadBusq[2]."' AND ";	
		}
	}
	
	$queryCuentas = "SELECT * FROM cuentas ".$condicion." (numeroCuentaCompania  LIKE '%".$valCadBusq[1]."%')";
	$rsCuentas = mysql_query($queryCuentas) or die(mysql_error());
        
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitCuenta = sprintf("%s %s LIMIT %d OFFSET %d", $queryCuentas, $sqlOrd, $maxRows, $startRow);	
        
	$rsLimitCuenta = mysql_query($queryLimitCuenta) or die(mysql_error());
			
	if ($totalRows == NULL) {
		$rsCuenta = mysql_query($queryCuentas) or die(mysql_error());
		$totalRows = mysql_num_rows($rsCuentas);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarCuentas", "33%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listarCuentas", "33%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listarCuentas", "34%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Cuenta");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($rowCuenta = mysql_fetch_assoc($rsLimitCuenta)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$queryBanco = "SELECT nombreBanco FROM bancos WHERE idBanco = '".$rowCuenta['idBanco']."'";
		$rsBanco = mysql_query($queryBanco) or die(mysql_error());
		$rowBanco = mysql_fetch_array($rsBanco);
		
		$queryEmpresa = "SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$rowCuenta['id_empresa']."'";
		$rsEmpresa = mysql_query($queryEmpresa) or die (mysql_error());
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);

		$nombreSucursal = "";
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
					
		$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$empresa."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowCuenta['numeroCuentaCompania'])."</td>";
			$htmlTb .= "<td><img src='../img/iconos/ico_view.png' onclick=\"window.open('te_resumen_cuenta.php?cast=".$rowCuenta['idCuentas']." ','_self')\")' /></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCuentas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCuentas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarCuentas(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCuentas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCuentas(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
        $objResponse->assign("tdListaCuentas","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function verCuenta($idCuentas, $accion){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM vw_te_cuentas WHERE idcuentas = ".$idCuentas."";
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
		
	$objResponse->script("xajax_comboBancos(".$row['idBanco'].",'tdSelBancoCuentaNueva','selBancoCuentaNueva','')" );
	$objResponse->script("xajax_comboMonedas(".$row['id_moneda'].");"); 
	$objResponse->script("xajax_comboAplicaDebito(".$row['debito_bancario'].");");
	$objResponse->script("xajax_comboEstatus(".$row['estatus'].");");
	$objResponse->script("xajax_comboTipoCuenta('".$row['tipo_cuenta']."');");
	$objResponse->assign("hddIdCuenta","value",$row['idCuentas']);
	$objResponse->assign("txtNumeroCuenta","value",$row['numeroCuentaCompania']);     
	$objResponse->assign("txtFirmaElectronica","value",$row['firma_electronica']);  
	$objResponse->assign("txtCuentaContable","value",$row['nro_cuenta_contable']);    
	$objResponse->assign("txtCuentaContableContrapartida","value",$row['nro_cuenta_contable_contrapartida']);    
	$objResponse->assign("txtCuentaDebitosBancarios","value",$row['nro_cuenta_contable_debitos']);   
	$objResponse->assign("txtSaldoLibros","value",$row['saldo']);    
	$objResponse->assign("txtSaldoAnteriorConciliado","value",$row['saldo_tem']);     
	
	if ($row['ultimo_nro_chq'] != null)
		$objResponse->assign("txtProximoNroCheque","value",$row['ultimo_nro_chq']);
	else
		$objResponse->assign("txtProximoNroCheque","value","");
	
	$objResponse->assign("txtFirmante1","value",$row['firma_1']);
	$objResponse->assign("txtTipoFirmante1","value",$row['tipo_firma_1']);
	$objResponse->assign("txtFirmante2","value",$row['firma_2']);
	$objResponse->assign("txtTipoFirmante2","value",$row['tipo_firma_2']);
	$objResponse->assign("txtFirmante3","value",$row['firma_3']);
	$objResponse->assign("txtTipoFirmante3","value",$row['tipo_firma_3']);
	$objResponse->assign("txtFirmante4","value",$row['firma_4']);
	$objResponse->assign("txtTipoFirmante4","value",$row['tipo_firma_4']);
	$objResponse->assign("txtFirmante5","value",$row['firma_5']);
	$objResponse->assign("txtTipoFirmante5","value",$row['tipo_firma_5']);
	$objResponse->assign("txtFirmante6","value",$row['firma_6']);
	$objResponse->assign("txtTipoFirmante6","value",$row['tipo_firma_6']);
	$objResponse->assign("txtCombinacion1","value",$row['comb_1']);
	$objResponse->assign("txtRestriccionCombinacion1","value",$row['restriccion_1']);
	$objResponse->assign("txtCombinacion2","value",$row['comb_2']);
	$objResponse->assign("txtRestriccionCombinacion2","value",$row['restriccion_2']);
	$objResponse->assign("txtCombinacion3","value",$row['comb_3']);
	$objResponse->assign("txtRestriccionCombinacion3","value",$row['restriccion_3']);
	
	
	if ($accion == 1){
	$objResponse->script("$('divFlotante').style.display = '';
						  $('divFlotanteTitulo').innerHTML = 'Ver Cuenta';
						  centrarDiv($('divFlotante'));
						  $('bttGuardar').style.display = 'none';");
	}
	else{
		$objResponse->script("$('divFlotante').style.display = '';
							  $('divFlotanteTitulo').innerHTML = 'Ver Cuenta';
						  	  centrarDiv($('divFlotante'));
						  	  $('bttGuardar').style.display = '';");
	}
						  
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"comboAplicaDebito");
$xajax->register(XAJAX_FUNCTION,"comboBancos");
$xajax->register(XAJAX_FUNCTION,"comboEmpresa");
$xajax->register(XAJAX_FUNCTION,"comboEstatus");
$xajax->register(XAJAX_FUNCTION,"comboMonedas");
$xajax->register(XAJAX_FUNCTION,"comboTipoCuenta");
$xajax->register(XAJAX_FUNCTION,"guardarCuenta");
$xajax->register(XAJAX_FUNCTION,"listarCuentas");
$xajax->register(XAJAX_FUNCTION,"verCuenta");
?>