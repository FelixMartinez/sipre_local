<?php

require_once ("../../connections/conex.php");

header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=archivo.xls");
header("Pragma: no-cache");
header("Expires: 0");


$valForm['buscarDocumento'] = $_GET["tipoDocumento"];
$valForm['hddIdEmpresa'] = $_GET["empresa"];
$valForm['hddBePro'] = $_GET["proveedor"];
$valForm['txtFecha'] = $_GET["fecha1"];
$valForm['txtFecha1'] = $_GET["fecha2"];
$valForm['txtBusq'] = $_GET["txtBusq"];
        
$busq = sprintf("%s|%s|%s|%s|%s",
            $valForm['hddIdEmpresa'],
            $valForm['hddBePro'],
            $valForm['txtFecha'],
            $valForm['txtFecha1'],
            $valForm['txtBusq']);

if($valForm['buscarDocumento'] == 1){
    listadoPagos(0,'numero_factura_proveedor','ASC',$busq);            
}

if($valForm['buscarDocumento'] == 2){
    listadoPagos2(0,'numero_notacargo','ASC', $busq);        
}

function listadoPagos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL){
    
	global $spanCI;
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
        //$valCadBusq[0] = id empresa
        //$valCadBusq[1] = id benef o proveedor(solo se usa proveedor)
        //$valCadBusq[2] = fecha inicio
        //$valCadBusq[3] = fecha fin
        //$valCadBusq[4] = Criterio numero fact/nota
                
//        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
//        $sqlBusq .= $cond."vw_te_retencion_cheque.anulado IS NULL";
        
	if ($valCadBusq[0] == ''){
            //$sqlBusq .= " vw_te_retencion_cheque.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
        }else if ($valCadBusq[0] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."cp_factura.id_empresa = '".$valCadBusq[0]."'";
        }
        
	if ($valCadBusq[1] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."cp_factura.id_proveedor = '".$valCadBusq[1]."'";
        }
        
	if ($valCadBusq[2] != '' && $valCadBusq[3] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("DATE(cp_factura.fecha_origen) BETWEEN %s AND %s",
                    valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"text"),
                    valTpDato(date("Y-m-d",strtotime($valCadBusq[3])),"text")
                    );
        }
        
	if ($valCadBusq[4] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            if(strpos($valCadBusq[4],",")){
                $arrayNumeros = implode("','", array_map('trim',explode(",",$valCadBusq[4])));
                $sqlBusq .= $cond."cp_factura.numero_factura_proveedor IN ('".$arrayNumeros."')";
            }else{
                $sqlBusq .= $cond."cp_factura.numero_factura_proveedor LIKE '%".$valCadBusq[4]."%'";
            }
        }
        
	$query = "SELECT 
                pg_empresa.nombre_empresa,
                CONCAT_WS('-',cp_proveedor.lrif,cp_proveedor.rif) as rif_proveedor,
                cp_proveedor.nombre,
                'FA' as tipo_documento,
		cp_factura.fecha_origen,
		cp_factura.numero_factura_proveedor,
		cp_factura.subtotal_factura,
		IFNULL((SELECT SUM(subtotal_iva) FROM cp_factura_iva WHERE id_factura = cp_factura.id_factura),0) AS iva_factura,
		cp_factura.monto_exento,
		cp_factura.subtotal_descuento,
		(cp_factura.subtotal_factura + cp_factura.subtotal_descuento + IFNULL((SELECT  SUM(subtotal_iva) FROM cp_factura_iva WHERE id_factura = cp_factura.id_factura),0)) AS monto_factura,
		IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle WHERE idFactura = cp_factura.id_factura),0) AS retencion_iva,
                IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque WHERE id_factura = cp_factura.id_factura AND tipo = 0 AND anulado IS NULL),0) AS retencion_islr,		

                ((cp_factura.subtotal_factura + cp_factura.subtotal_descuento + IFNULL((SELECT SUM(subtotal_iva) FROM cp_factura_iva WHERE id_factura = cp_factura.id_factura),0))
                  - cp_factura.subtotal_descuento
                  - IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle WHERE idFactura = cp_factura.id_factura),0)
                  - IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque WHERE id_factura = cp_factura.id_factura AND tipo = 0 AND anulado IS NULL),0) 
                  ) AS monto_pagar
        FROM cp_factura 
        INNER JOIN cp_proveedor ON cp_factura.id_proveedor = cp_proveedor.id_proveedor
        INNER JOIN pg_empresa ON cp_factura.id_empresa = pg_empresa.id_empresa
        ". $sqlBusq;  //#AND numero_factura_proveedor IN ( '53793', '53802', '53804', '53843', '53847', '53862', '53869', '53876' )
		
       
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s #####LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit);
        if(!$rsLimit) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }
	if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if(!$rs) { return die(mysql_error()."\n\nLine: ".__LINE__); }
            $totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"1\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<th>Empresa</th>";
		$htmlTh .= "<th>Proveedor</th>";
		$htmlTh .= "<th>".$spanCI."-".$spanRIF."</th>";
		$htmlTh .= "<th>Tipo Doc.</th>";
		$htmlTh .= "<th>Fecha</th>";
		$htmlTh .= "<th>Nro Documento</th>";
		$htmlTh .= "<th>Sub Total</th>";
		$htmlTh .= "<th>Iva</th>";
		$htmlTh .= "<th>Excento</th>";
		$htmlTh .= "<th>Descuento</th>";
		$htmlTh .= "<th>Monto</th>";
		$htmlTh .= "<th>Retenci&oacute;n Impuesto</th>";
		$htmlTh .= "<th>Retenci&oacute;n ISLR</th>";
		$htmlTh .= "<th>Monto a Pagar</th>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";			
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";                        
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre'])."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['rif_proveedor']."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";                        
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_origen']))."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['numero_factura_proveedor']."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['subtotal_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['iva_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_exento'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['subtotal_descuento'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['retencion_iva'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['retencion_islr'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_pagar'])."</td>";			
		$htmlTb .= "</tr>";		
                
                $arrayTotales["subtotal_factura"] += $row['subtotal_factura'];
                $arrayTotales["iva_factura"] += $row['iva_factura'];
                $arrayTotales["monto_exento"] += $row['monto_exento'];
                $arrayTotales["subtotal_descuento"] += $row['subtotal_descuento'];
                $arrayTotales["monto_factura"] += $row['monto_factura'];
                $arrayTotales["retencion_iva"] += $row['retencion_iva'];
                $arrayTotales["retencion_islr"] += $row['retencion_islr'];
                $arrayTotales["monto_pagar"] += $row['monto_pagar'];
	}
        
        if(isset($arrayTotales)){
            $htmlTb.= "<tr align=\"left\" class=\"tituloColumna\" height=\"24\">";                
                $htmlTb .= "<td align=\"center\" colspan=\"6\"><b>Total</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['subtotal_factura'])."</b></td>";                
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['iva_factura'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['monto_exento'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['subtotal_descuento'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['monto_factura'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['retencion_iva'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['retencion_islr'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['monto_pagar'])."</b></td>";
            $htmlTb .= "</tr>";
        }
        
	echo $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;		
}

function listadoPagos2($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL){
    
	global $spanCI;
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
        //$valCadBusq[0] = id empresa
        //$valCadBusq[1] = id benef o proveedor(solo se usa proveedor)
        //$valCadBusq[2] = fecha inicio
        //$valCadBusq[3] = fecha fin
        //$valCadBusq[4] = Criterio numero fact/nota
                
//        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
//        $sqlBusq .= $cond."vw_te_retencion_cheque.anulado IS NULL";
        
	if ($valCadBusq[0] == ''){
            //$sqlBusq .= " vw_te_retencion_cheque.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
        }else if ($valCadBusq[0] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."cp_notadecargo.id_empresa = '".$valCadBusq[0]."'";
        }
        
	if ($valCadBusq[1] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."cp_notadecargo.id_proveedor = '".$valCadBusq[1]."'";
        }
        
	if ($valCadBusq[2] != '' && $valCadBusq[3] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("DATE(cp_notadecargo.fecha_origen_notacargo) BETWEEN %s AND %s",
                    valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"text"),
                    valTpDato(date("Y-m-d",strtotime($valCadBusq[3])),"text")
                    );
        }
        
	if ($valCadBusq[4] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            if(strpos($valCadBusq[4],",")){
                $arrayNumeros = implode("','", array_map('trim',explode(",",$valCadBusq[4])));
                $sqlBusq .= $cond."cp_notadecargo.numero_notacargo IN ('".$arrayNumeros."')";
            }else{
                $sqlBusq .= $cond."cp_notadecargo.numero_notacargo LIKE '%".$valCadBusq[4]."%'";
            }
        }
        
	
    $query = "SELECT 
                pg_empresa.nombre_empresa,
                CONCAT_WS('-',cp_proveedor.lrif,cp_proveedor.rif) as rif_proveedor,
                cp_proveedor.nombre,
                'ND' as tipo_documento,
		cp_notadecargo.fecha_origen_notacargo,
		cp_notadecargo.numero_notacargo,
		cp_notadecargo.subtotal_notacargo,
		IFNULL((SELECT SUM(subtotal_iva) FROM cp_notacargo_iva WHERE id_notacargo = cp_notadecargo.id_notacargo),0) AS iva_factura,
		cp_notadecargo.monto_exento_notacargo,
		cp_notadecargo.subtotal_descuento_notacargo,
		(cp_notadecargo.subtotal_notacargo + cp_notadecargo.subtotal_descuento_notacargo + IFNULL((SELECT  SUM(subtotal_iva) FROM cp_notacargo_iva WHERE id_notacargo = cp_notadecargo.id_notacargo),0)) AS monto_factura,
		IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle WHERE id_nota_cargo = cp_notadecargo.id_notacargo),0) AS retencion_iva,
                IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque WHERE id_factura = cp_notadecargo.id_notacargo AND tipo = 1 AND anulado IS NULL),0) AS retencion_islr,		
                
                ((cp_notadecargo.subtotal_notacargo + cp_notadecargo.subtotal_descuento_notacargo + IFNULL((SELECT SUM(subtotal_iva) FROM cp_notacargo_iva WHERE id_notacargo = cp_notadecargo.id_notacargo),0))
                  - cp_notadecargo.subtotal_descuento_notacargo
                  - IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle WHERE id_nota_cargo = cp_notadecargo.id_notacargo),0)
                  - IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque WHERE id_factura = cp_notadecargo.id_notacargo AND tipo = 1 AND anulado IS NULL),0) 
                  ) AS monto_pagar
	FROM cp_notadecargo 
        INNER JOIN cp_proveedor ON cp_notadecargo.id_proveedor = cp_proveedor.id_proveedor
        INNER JOIN pg_empresa ON cp_notadecargo.id_empresa = pg_empresa.id_empresa
        ". $sqlBusq;      
       
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s #####LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit);
        if(!$rsLimit) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }
	if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if(!$rs) { return die(mysql_error()."\n\nLine: ".__LINE__); }
            $totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"1\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<th>Empresa</th>";
		$htmlTh .= "<th>Proveedor</th>";
		$htmlTh .= "<th>".$spanCI."-".$spanRIF."</th>";
		$htmlTh .= "<th>Tipo Doc.</th>";
		$htmlTh .= "<th>Fecha</th>";
		$htmlTh .= "<th>Nro Documento</th>";
		$htmlTh .= "<th>Sub Total</th>";
		$htmlTh .= "<th>Iva</th>";
		$htmlTh .= "<th>Excento</th>";
		$htmlTh .= "<th>Descuento</th>";
		$htmlTh .= "<th>Monto</th>";
		$htmlTh .= "<th>Retenci&oacute;n Impuesto</th>";
		$htmlTh .= "<th>Retenci&oacute;n ISLR</th>";
		$htmlTh .= "<th>Monto a Pagar</th>";		                
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";			
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";                        
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre'])."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['rif_proveedor']."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";                        
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_origen_notacargo']))."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['numero_notacargo']."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['subtotal_notacargo'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['iva_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_exento'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['subtotal_descuento'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['retencion_iva'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['retencion_islr'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_pagar'])."</td>";			
		$htmlTb .= "</tr>";		
                
                $arrayTotales["subtotal_notacargo"] += $row['subtotal_notacargo'];
                $arrayTotales["iva_factura"] += $row['iva_factura'];
                $arrayTotales["monto_exento"] += $row['monto_exento'];
                $arrayTotales["subtotal_descuento"] += $row['subtotal_descuento'];
                $arrayTotales["monto_factura"] += $row['monto_factura'];
                $arrayTotales["retencion_iva"] += $row['retencion_iva'];
                $arrayTotales["retencion_islr"] += $row['retencion_islr'];
                $arrayTotales["monto_pagar"] += $row['monto_pagar'];
	}
        
        if(isset($arrayTotales)){
            $htmlTb.= "<tr align=\"left\" class=\"tituloColumna\" height=\"24\">";                
                $htmlTb .= "<td align=\"center\" colspan=\"6\"><b>Total</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['subtotal_notacargo'])."</b></td>";                
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['iva_factura'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['monto_exento'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['subtotal_descuento'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['monto_factura'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['retencion_iva'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['retencion_islr'])."</b></td>";
                $htmlTb .= "<td align=\"center\"><b>".formatoNumero($arrayTotales['monto_pagar'])."</b></td>";
            $htmlTb .= "</tr>";
        }
        
	echo $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;		
	
}

function formatoNumero($numero){
    return number_format($numero,2,".",",");
}

?>