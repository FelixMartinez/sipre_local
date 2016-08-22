<?php
function buscarEstadoCuenta($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoEstadoCuenta(0,'','','%s' + '|' + '%s' + '|' + '%s' + '|' + '%s' + '|' + '%s'+ '|' + '%s');",
		$valForm['hddIdEmpresa'],
		$valForm['selCuenta'],
		$valForm['txtFecha'],
		$valForm['selEstado'],
		$valForm['selTipoDoc'],
		$valForm['txtFechaHasta']
		
		));
			
	//$objResponse ->alert($valForm['selCuenta']);
	
	//$objResponse->script(sprintf("xajax_listadoEstadoCuenta(0,'','');"));
	
	return $objResponse;
}

function listadoEstadoCuenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
		
	if (!xvalidaAcceso($objResponse,"te_estado_cuenta")){
		$objResponse->assign("tdListadoEstadoCuenta","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
        
        
//	if ($valCadBusq[0] == '')
//		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
//	
//	else if ($valCadBusq[0] != '')
//		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$valCadBusq[0]."'";
		
	if ($valCadBusq[1] != 0)
		$sqlBusq .= " AND te_estado_cuenta.id_cuenta = '".$valCadBusq[1]."'";
	
	if ($valCadBusq[2] != '')
		$sqlBusqFecha .= " AND DATE(te_estado_cuenta.fecha_registro) BETWEEN '".date("Y-m-d",strtotime($valCadBusq[2]))."' AND '".date("Y-m-d",strtotime($valCadBusq[5]))."'";
		
	if($valCadBusq[3] > 0){
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$valCadBusq[3]."'";
	}
		
	if($valCadBusq[4] == 'CH')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'CH ANULADO')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'TR')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'DP')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'ND')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'NC')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";

        $idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
        
        //usado para sumar retencion y comision de tarjetas de credito por punto de venta
        $queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
                INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
        WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
                valTpDato($idEmpresa, "int"));
        $rsConfig403 = mysql_query($queryConfig403);
        if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
        
        $rowConfig403 = mysql_fetch_assoc($rsConfig403);
        $esVenezuelaPanama = valTpDato($rowConfig403['valor'],"int"); //1 o null venezuela, 2 panama
        
        //para usarlo 4 veces
//        $queryMonto = "IF(".$esVenezuelaPanama." = 2 
//                            AND tipo_documento = 'NC' 
//                            AND IF(tipo_documento = 'NC', 
//                                (SELECT tipo_nota_credito 
//                                FROM te_nota_credito 
//                                WHERE te_nota_credito.id_nota_credito = id_documento)
//                                , 0) = 3, 
//
//                            IFNULL((SELECT te_estado_cuenta.monto + ((te_estado_cuenta.monto*(porcentaje_comision/100)) + (te_estado_cuenta.monto*(porcentaje_islr/100))) 
//                                FROM te_retencion_punto
//                                INNER JOIN cj_cc_retencion_punto_pago ON te_retencion_punto.id_retencion_punto = cj_cc_retencion_punto_pago.id_retencion_punto
//                                LEFT JOIN an_pagos ON cj_cc_retencion_punto_pago.id_pago = an_pagos.idPago AND an_pagos.formaPago IN(5,7)
//                                LEFT JOIN sa_iv_pagos ON cj_cc_retencion_punto_pago.id_pago = sa_iv_pagos.idPago AND sa_iv_pagos.formaPago IN(5,7)
//                                WHERE 
//                                #te_retencion_punto.id_cuenta = te_estado_cuenta.id_cuenta 
//                                te_retencion_punto.id_tipo_tarjeta != 6 
//                                AND (an_pagos.numeroDocumento = id_documento OR sa_iv_pagos.numeroDocumento = id_documento)
//                            ),te_estado_cuenta.monto),
//                            te_estado_cuenta.monto) as monto,";
        
        //panama actual:
//        $queryMonto = "IF(".$esVenezuelaPanama." = 2 
//                                AND tipo_documento = 'NC' 
//                                AND IF(tipo_documento = 'NC', 
//                                    (SELECT tipo_nota_credito 
//                                    FROM te_nota_credito 
//                                    WHERE te_nota_credito.id_nota_credito = id_documento)
//                                    , 0) = 3, 
//
//                                (SELECT te_estado_cuenta.monto + ((te_estado_cuenta.monto*(porcentaje_comision/100)) + (te_estado_cuenta.monto*(porcentaje_islr/100))) FROM te_retencion_punto
//                                WHERE te_retencion_punto.id_cuenta = te_estado_cuenta.id_cuenta AND te_retencion_punto.id_tipo_tarjeta != 6 ORDER BY id_retencion_punto DESC LIMIT 1
//                                ),
//
//                                te_estado_cuenta.monto) as monto,";
        
        $queryMonto = "IF(".$esVenezuelaPanama." = 2 AND tipo_documento = 'NC',
                                
                                CASE 
                                WHEN (SELECT tipo_nota_credito FROM te_nota_credito WHERE te_nota_credito.id_nota_credito = id_documento) = 3 
                                THEN (SELECT te_estado_cuenta.monto + ((te_estado_cuenta.monto*(porcentaje_comision/100)) + (te_estado_cuenta.monto*(porcentaje_islr/100))) FROM te_retencion_punto
                                        WHERE te_retencion_punto.id_cuenta = te_estado_cuenta.id_cuenta AND te_retencion_punto.id_tipo_tarjeta != 6 ORDER BY id_retencion_punto DESC LIMIT 1
                                        )
                                
                                WHEN (SELECT tipo_nota_credito FROM te_nota_credito WHERE te_nota_credito.id_nota_credito = id_documento) = 2
                                THEN (SELECT te_estado_cuenta.monto + (te_estado_cuenta.monto*(porcentaje_comision/100)) FROM te_retencion_punto
                                        WHERE te_retencion_punto.id_cuenta = te_estado_cuenta.id_cuenta AND te_retencion_punto.id_tipo_tarjeta = 6 ORDER BY id_retencion_punto DESC LIMIT 1
                                        )
                                        
                                ELSE
                                    te_estado_cuenta.monto
                                END,
                                te_estado_cuenta.monto) as monto,";
        
	$querySaldo = "SELECT saldo FROM cuentas WHERE idCuentas='".$valCadBusq[1]."'";
	$rsSaldo = mysql_query($querySaldo) or die(mysql_error()."\nLine: ".__LINE__);
	$rowSaldo = mysql_fetch_array($rsSaldo);
	$saldo = $rowSaldo['saldo'];
	
	$query = sprintf("SELECT 
						  te_estado_cuenta.id_estado_cuenta,
						  te_estado_cuenta.id_empresa,
						  te_estado_cuenta.tipo_documento,
						  te_estado_cuenta.id_documento,
						  te_estado_cuenta.fecha_registro,
						  te_estado_cuenta.id_cuenta,
						  te_estado_cuenta.id_empresa,
                                                  ".$queryMonto."                                                        
						  te_estado_cuenta.suma_resta,
						  te_estado_cuenta.numero_documento,
						  te_estado_cuenta.desincorporado,
						  te_estado_cuenta.observacion,
						  te_estado_cuenta.estados_principales,
						  DATE_FORMAT(te_estado_cuenta.fecha_registro,'%s') as fecha_registro_formato,
                                                  #SOLO PARA ORDENAMIENTO
                                                  if(suma_resta = 0, monto, 0)as debito,                                                  
                                                  if(suma_resta = 1, monto, 0)as credito     
						FROM
						  te_estado_cuenta
						WHERE
						  te_estado_cuenta.estados_principales <> 0 ",'%d-%m-%Y %h:%i %p').$sqlBusq .$sqlBusqFecha; 
	
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
              
	$rsLimit = mysql_query($queryLimit) or die(mysql_error()."\nLine: ".__LINE__."<br>".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error()."\nLine: ".__LINE__."SINLIMIT<br>".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
        //acumulado de la paginas anteriores para que el saldo de correcto y no por pagina:
        if($startRow){
            
            $saldo = saldoPorPagina($pageNum-1, "", "", $valBusq."|".$queryMonto, $maxRows*$pageNum, $totalRows);
            
//            $limite = sprintf("LIMIT %s ", ($startRow)); //paginas anteriores
//            $queryAcumulado = sprintf("SELECT 
//                                        (SELECT SUM(monto) FROM (SELECT monto FROM te_estado_cuenta
//                                                WHERE suma_resta = 1 
//                                                      AND tipo_documento != 'CH ANULADO' 
//                                                      AND estados_principales <> 0 %s %s %s ) tabla_aux) as SUMA", 
//                                        $sqlBusq, $sqlBusqFecha, $limite);
//            $rsAcumulado = mysql_query($queryAcumulado);  
//         
//            if(!$rsAcumulado){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__."\n\nQuery: ".$queryAcumulado); }
//            $limite = sprintf("LIMIT %s ", ($startRow));
//            $queryAcumulado2 = sprintf("SELECT 
//                                            (SELECT SUM(monto) FROM (SELECT monto FROM te_estado_cuenta
//                                                WHERE suma_resta = 0 
//                                                      AND estados_principales <> 0 %s %s %s ) tabla_aux) as RESTA",
//                                        $sqlBusq, $sqlBusqFecha, $limite);
//            $rsAcumulado2 = mysql_query($queryAcumulado2);        
//            if(!$rsAcumulado2){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__."\n\nQuery: ".$queryAcumulado2); }
//
//            $rowAcumulado = mysql_fetch_assoc($rsAcumulado);
//            $rowAcumulado2 = mysql_fetch_assoc($rsAcumulado2);
//            $saldo = $saldo + $rowAcumulado['SUMA'];
//            $saldo = $saldo - $rowAcumulado2['RESTA'];
            
        }
        
        //$objResponse->script("console.log(".$queryLimit.");");
        //$objResponse->script("console.log(".$valBusq.");");
                
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "11", $pageNum, "estados_principales", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "11", $pageNum, "fecha_registro_formato", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "11", $pageNum, "id_estado_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "T.D.");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "11%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "17%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Beneficiario");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "17%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "11%", $pageNum, "debito", $campOrd, $tpOrd, $valBusq, $maxRows, "D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "11%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Cr&eacute;dito");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuenta", "11%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
	$htmlTh .= "</tr>";
	
	$conta = 0; 
	$contb = 0;
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		if($row['estados_principales'] == 1){
			$titleEstado = "Por Aplicar";
			$imgEstado = "<img src=\"../img/iconos/ico_rojo.gif\">";
		}elseif($row['estados_principales'] == 2){
			$titleEstado = "Aplicado";
			$imgEstado = "<img src=\"../img/iconos/ico_amarillo.gif\">";
		}elseif($row['estados_principales'] == 3){
			$titleEstado = "Conciliado";
			$imgEstado = "<img src=\"../img/iconos/ico_verde.gif\">";
		}
			
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" title=\"".$titleEstado."\">".$imgEstado."</td>";
			$htmlTb .= "<td align=\"center\" title=\"id_empresa: ".$row['id_empresa']." \">".$row['fecha_registro_formato']."</td>";
			$htmlTb .= "<td align=\"center\" title=\"id_estado_cuenta: ".$row['id_estado_cuenta']." suma_resta: ".$row['suma_resta']." \">".tipoDocumento($row['id_estado_cuenta'])."</td>";
			$htmlTb .= "<td align=\"center\" title=\"id_documento: ".$row['id_documento']." \">".$row['numero_documento']."</td>";
			$htmlTb .= "<td align=\"center\">".strtoupper(Beneficiario($row['tipo_documento'],$row['id_documento']))."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['observacion'])."</td>";
			if($row['suma_resta'] == 0){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
				$saldo = $saldo - $row['monto'];
				$htmlTb .= "<td align=\"right\">".number_format($saldo,'2','.',',')."</td>";
				$conta +=  $row['monto'];
			} else {
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
                        }
			if($row['suma_resta'] == 1){
				if($row['tipo_documento'] == "CH ANULADO"){
                                    $htmlTb .= "<td align=\"right\">0.00</td>";
                                    $htmlTb .= "<td align=\"right\">".number_format($saldo,'2','.',',')."</td>";
                                    
                                } else {
                                    $htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
                                    $saldo = $saldo + $row['monto'];
                                    $htmlTb .= "<td align=\"right\">".number_format($saldo,'2','.',',')."</td>";
                                    $contb +=  $row['monto'];                                    
                                }
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
		
	$queryTotales = sprintf("SELECT 
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.fecha_registro,
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.id_empresa,
		".$queryMonto."                    
		te_estado_cuenta.suma_resta,
		te_estado_cuenta.numero_documento,
		te_estado_cuenta.desincorporado,
		te_estado_cuenta.observacion,
		te_estado_cuenta.estados_principales,
		DATE_FORMAT(te_estado_cuenta.fecha_registro,'%s') as fecha_registro_formato
	FROM
		te_estado_cuenta
	WHERE
		te_estado_cuenta.desincorporado <> 0 AND te_estado_cuenta.desincorporado <> 3 AND te_estado_cuenta.estados_principales <> 0",'%d-%m-%Y %h:%i %p').$sqlBusq; 
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	while($rowTotales = mysql_fetch_array($rsTotales)){
	
	if($rowTotales['suma_resta'] == 0)
				$contTotales1 +=  $rowTotales['monto'];
	if($rowTotales['suma_resta'] == 1)
				$contTotales2 +=  $rowTotales['monto'];
	}
        
        
        //TOTALES PERO EN RANGO DE FECHA gregor
        
        $contTotalesFecha1 = 0;
        $contTotalesFecha2 = 0;
        $queryTotales = sprintf("SELECT 
                te_estado_cuenta.id_estado_cuenta,
                te_estado_cuenta.tipo_documento,
                te_estado_cuenta.id_documento,
                te_estado_cuenta.fecha_registro,
                te_estado_cuenta.id_cuenta,
                te_estado_cuenta.id_empresa,
                ".$queryMonto."                    
                te_estado_cuenta.suma_resta,
                te_estado_cuenta.numero_documento,
                te_estado_cuenta.desincorporado,
                te_estado_cuenta.observacion,
                te_estado_cuenta.estados_principales,
                DATE_FORMAT(te_estado_cuenta.fecha_registro,'%s') as fecha_registro_formato
        FROM
                te_estado_cuenta
        WHERE
                te_estado_cuenta.desincorporado <> 0 AND te_estado_cuenta.desincorporado <> 3 AND te_estado_cuenta.estados_principales <> 0",'%d-%m-%Y %h:%i %p').$sqlBusq.$sqlBusqFecha; 
        $rsTotales = mysql_query($queryTotales);
        if (!$rsTotales) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

        while($rowTotales = mysql_fetch_array($rsTotales)){

        if($rowTotales['suma_resta'] == 0)
                                $contTotalesFecha1 +=  $rowTotales['monto'];
        if($rowTotales['suma_resta'] == 1)
                                $contTotalesFecha2 +=  $rowTotales['monto'];
        }
	
	$htmlx.="<table align=\"center\" border=\"0\" height=\"24\" width=\"60%\">";
		$htmlx.="<tr class=\"tituloColumna\">
					<td width=\"20%\"></td>
					<td width=\"20%\">"."D&eacute;bito"."</td>
					<td width=\"20%\">"."Cr&eacute;dito"."</td>
				</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total por P&aacute;gina:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($conta,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" width=\"80\">".number_format($contb,'2','.',',')."</td>";
		$htmlx.="</tr>";
		if($valCadBusq[2] != ''){
                    $htmlx.="<tr>";
                            $htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total Entre Fechas:</td>";
                            $htmlx.="<td align=\"right\" width=\"83\">".number_format($contTotalesFecha1,'2','.',',')."</td>";
                            $htmlx.="<td align=\"right\" width=\"80\">".number_format($contTotalesFecha2,'2','.',',')."</td>";
                    $htmlx.="</tr>";
                }
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($contTotales1,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" width=\"80\">".number_format($contTotales2,'2','.',',')."</td>";
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
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
	
	//$objResponse->assign("tdListadoEstadoCuenta","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin.$htmlx);
	$objResponse->assign("tdListadoEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx);
        
	return $objResponse;
}
function listBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT bancos.idBanco, bancos.nombreBanco, bancos.sucursal FROM bancos INNER JOIN cuentas ON (cuentas.idBanco = bancos.idBanco) WHERE bancos.idBanco != '1' GROUP BY bancos.idBanco";
	$rsBanco = mysql_query($queryBanco) or die(mysql_error()."\nLine: ".__LINE__);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitBanco = sprintf(" %s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitBanco = mysql_query($queryLimitBanco) or die(mysql_error()."\nLine: ".__LINE__);
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco) or die(mysql_error()."\nLine: ".__LINE__);
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
								  document.getElementById('tdFlotanteTitulo2').innerHTML = 'Seleccione Banco';
								  centrarDiv(document.getElementById('divFlotante2'))");	
	return $objResponse;
}
function asignarBanco($id_banco){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("document.getElementById('divFlotante2').style.display = 'none'");	
	
	$query = "SELECT * FROM bancos WHERE idBanco = '".$id_banco."'";
	$rs = mysql_query($query) or die(mysql_error()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmBuscar'))");
	
	return $objResponse;
}
function comboCuentas($valForm){
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdBanco'] == -1){
		$disabled = "disabled=\"disabled\"";
	}
	else{
		$condicion = "WHERE idBanco = '".$valForm['hddIdBanco']."' AND id_empresa = '".$valForm['hddIdEmpresa']."'";
		$disabled = "";
	}
	
	$queryCuentas = "SELECT * FROM cuentas ".$condicion."";
	$rsCuentas = mysql_query($queryCuentas) or die(mysql_error()."\nLine: ".__LINE__);
	
	$html = "<select id=\"selCuenta\" name=\"selCuenta\" ".$disabled.">";
			$html .= "<option value=\"-1\">Seleccione</option>";
		while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
			$html .= "<option value=\"".$rowCuentas['idCuentas']."\">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuenta","innerHTML",$html);
	
		
	return $objResponse;
}
function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
        if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
			
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error()."\nLine: ".__LINE__);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa) or die(mysql_error()."\nLine: ".__LINE__);
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error()."\nLine: ".__LINE__);
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
	
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
		$rsEmpresa = mysql_query($queryEmpresa) or die (mysql_error()."\nLine: ".__LINE__);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
		$nombreSucursal = "";
		
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
		
		$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
		
		$objResponse -> assign("txtNombreEmpresa","value",$empresa);
		$objResponse -> assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
		$objResponse->script("document.getElementById('divFlotante2').style.display = 'none';");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"listadoEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"listBanco");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");



function fecha($id){

	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	if($row['fecha_concialicion'] == NULL)
		$respuesta = "";
	else
		$respuesta = date("d/m/Y",strtotime($row['fecha_conciliacion']));
		
	return $respuesta; 

}

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
		if($rowNC['tipo_nota_credito'] == '1')
			$respuesta = "NC";
		else if($rowNC['tipo_nota_credito'] == '2')
			$respuesta = "NC/TD";
		else if($rowNC['tipo_nota_credito'] == '3')
			$respuesta = "NC/TC";
		else if($rowNC['tipo_nota_credito'] == '4')
			$respuesta = "NC/TR";
	}
	if($row['tipo_documento'] == 'ND')
		$respuesta = "ND";
	if($row['tipo_documento'] == 'TR')
		$respuesta = "TR";
	if($row['tipo_documento'] == 'CH')
		$respuesta = "CH";
		if($row['tipo_documento'] == 'CH ANULADO')
		$respuesta = "CH ANULADO";
	if($row['tipo_documento'] == 'DP')
		$respuesta = "DP";
	
	return $respuesta;
}

function Beneficiario($tipodocumento,$id){
	
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$id);

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	if($tipodocumento == 'NC')
		$respuesta = "Nota de Credito";
		
	if($tipodocumento == 'ND')
		$respuesta = "Nota de Debito";
		
	if($tipodocumento == 'TR'){
		
	$query = sprintf("SELECT * FROM te_transferencia WHERE id_transferencia = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	if($row['beneficiario_proveedor'] == 1)
	$respuesta = nombreP($row['id_beneficiario_proveedor']);
	
	 else
	$respuesta = nombreB($row['id_beneficiario_proveedor']);
	}
	
	if($tipodocumento == 'CH'){
		
	$query = sprintf("SELECT * FROM te_cheques WHERE id_cheque = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
		if($row['beneficiario_proveedor'] == 1)
		$respuesta = nombreP($row['id_beneficiario_proveedor']);
		
		 else
		
		$respuesta = nombreB($row['id_beneficiario_proveedor']);
	}
	
	if($tipodocumento == 'CH ANULADO'){
		
	$query = sprintf("SELECT * FROM te_cheques_anulados WHERE id_cheque = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
		if($row['beneficiario_proveedor'] == 1)
		$respuesta = nombreP($row['id_beneficiario_proveedor']);
		
		 else
		
		$respuesta = nombreB($row['id_beneficiario_proveedor']);
	}
	
	
	if($tipodocumento == 'DP')
		$respuesta = "Deposito";
	
	return $respuesta;
}


function nombreB($id){
	
	
	$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$id);
	$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error()."\nLine: ".__LINE__);
	$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
	$respuesta = utf8_encode($rowBeneficiario['nombre_beneficiario']);

	return $respuesta;
}
function nombreP($id){
	
	$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$id);
	$rsProveedor = mysql_query($queryProveedor) or die(mysql_error()."\nLine: ".__LINE__);
	$rowProveedor = mysql_fetch_array($rsProveedor);
	$respuesta = utf8_encode($rowProveedor['nombre']);
	
	return $respuesta;
}

//hace el conteo para llevar exacto el saldo a sumar o restar para la proxima pag al darle a los botones de paginacion
function saldoPorPagina($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL){
    $valCadBusq = explode("|", $valBusq);
    $startRow = 0;
    
    //$valCadBusq[6] Sql para totales
    
        if ($valCadBusq[1] != 0)
		$sqlBusq .= " AND te_estado_cuenta.id_cuenta = '".$valCadBusq[1]."'";
	
	if ($valCadBusq[2] != '')
		$sqlBusqFecha .= " AND DATE(te_estado_cuenta.fecha_registro) BETWEEN '".date("Y-m-d",strtotime($valCadBusq[2]))."' AND '".date("Y-m-d",strtotime($valCadBusq[5]))."'";
		
	if($valCadBusq[3] == 1)
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$valCadBusq[3]."'";
	else if($valCadBusq[3] == 2)
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$valCadBusq[3]."'";
		
	if($valCadBusq[4] == 'CH')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'CH ANULADO')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'TR')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'DP')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'ND')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";
	else if($valCadBusq[4] == 'NC')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$valCadBusq[4]."'";

        $queryMonto = $valCadBusq[6];
        
	$querySaldo = "SELECT saldo FROM cuentas WHERE idCuentas='".$valCadBusq[1]."'";
	$rsSaldo = mysql_query($querySaldo) or die(mysql_error()."\nLine: ".__LINE__);
	$rowSaldo = mysql_fetch_array($rsSaldo);
	$saldo = $rowSaldo['saldo'];
	
	$query = sprintf("SELECT 
                            te_estado_cuenta.id_estado_cuenta,
                            te_estado_cuenta.tipo_documento,
                            te_estado_cuenta.id_documento,
                            te_estado_cuenta.fecha_registro,
                            te_estado_cuenta.id_cuenta,
                            te_estado_cuenta.id_empresa,
                            ".$queryMonto."                                
                            te_estado_cuenta.suma_resta,
                            te_estado_cuenta.numero_documento,
                            te_estado_cuenta.desincorporado,
                            te_estado_cuenta.observacion,
                            te_estado_cuenta.estados_principales,
                            DATE_FORMAT(te_estado_cuenta.fecha_registro,'%s') as fecha_registro_formato,
                            #SOLO PARA ORDENAMIENTO
                            if(suma_resta = 0, monto, 0)as debito,                                                  
                            if(suma_resta = 1, monto, 0)as credito     
                          FROM
                            te_estado_cuenta
                          WHERE
                            te_estado_cuenta.estados_principales <> 0 ",'%d-%m-%Y %h:%i %p').$sqlBusq .$sqlBusqFecha; 
	
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
    
        $rsLimit = mysql_query($queryLimit) or die(mysql_error()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
        
        $conta = 0; 
	$contb = 0;
        
        while ($row = mysql_fetch_assoc($rsLimit)) {

            $contFila++;

            if($row['suma_resta'] == 0){
                    $saldo = $saldo - $row['monto'];
                    $conta +=  $row['monto'];
            } else {

            }
            if($row['suma_resta'] == 1){
                    if($row['tipo_documento'] == "CH ANULADO"){

                    } else {
                        $saldo = $saldo + $row['monto'];
                        $contb +=  $row['monto'];                                    
                    }
            }		
        }
        
        return $saldo;
}

?>