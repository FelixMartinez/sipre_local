<?php
require("../../connections/conex.php");

//$valCadBusq = array_values(json_decode($_GET['valBusq'], true));
//die(var_dump(json_decode($_GET['valBusq'], true)));

header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=\"Listado Cheques.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

buscarCheque(json_decode($_GET['valBusq'], true));

function buscarCheque($valForm) {
	
	$valBusq = sprintf ("%s|%s|%s|%s|%s|%s|%s",
						$valForm['selEmpresa'],
						$valForm['selEstado'],
						$valForm['txtBusq'],
						$valForm['txtFecha'],
						$valForm['txtFecha1'],
						$valForm['idProveedorBuscar'],
						$valForm['conceptoBuscar']
						);
	//ordenamiento en historico siempre mostrar primero el ultimo
	if($_GET['acc'] == 3){//3 es historico devolucion            
		echo listadoCheques(0,"fecha_registro","DESC",$valBusq);
	}else{
		echo listadoCheques(0,"numero_cheque","ASC",$valBusq);
	}
}

function listadoCheques($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	$acc= $_GET['acc'];
	
	if ($_GET['acc'] == 1){//CHEQUES INDIVIDUALES TIENE IMPRESION
		$cadenaFiltro = " WHERE id_propuesta_pago = 0 AND entregado <> 1 ";
		$auxTd = true;
	}else if ($_GET['acc'] == 2){//IMPRESION DE CHEQUES PROPUESTA DE PAGO
		$cadenaFiltro = " WHERE id_propuesta_pago > 0 AND entregado <> 1 ";
		$auxTd = true;
	}else{//HISTORICO DE CHEQUES
		$cadenaFiltro = " WHERE entregado = 1 ";		
		$auxTd = false;
	}
	
	if($valCadBusq[0] != 0){
		if($valCadBusq[0] == -1){
			if($cadenaFiltro == ""){
				$cadenaFiltro = sprintf(" WHERE id_empresa = %s",$_SESSION['idEmpresaUsuarioSysGts']);
			}else{
				$cadenaFiltro .= sprintf(" AND id_empresa = %s",$_SESSION['idEmpresaUsuarioSysGts']);
			}
		}else{
			if($cadenaFiltro == ""){
				$cadenaFiltro = sprintf(" WHERE id_empresa = %s",$valCadBusq[0]);
			}else{
				$cadenaFiltro .= sprintf(" AND id_empresa = %s",$valCadBusq[0]);
			}
		}
	}
	
	if($valCadBusq[1] != 0){
		if($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE estado_documento = %s",$valCadBusq[1]);
		}else{
			$cadenaFiltro .= sprintf(" AND estado_documento = %s",$valCadBusq[1]);
		}
	}
	
	if($valCadBusq[2] != ""){
		if($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE numero_cheque = '%s'",$valCadBusq[2]);
		}else{
			$cadenaFiltro .= sprintf(" AND numero_cheque = '%s'",$valCadBusq[2]);
		}
	}
	
	if ($valCadBusq[3] != "" && $valCadBusq[4] !=""){
		if ($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE fecha_registro BETWEEN '%s' AND '%s'",
                                date("Y-m-d",strtotime($valCadBusq[3])),
                                date("Y-m-d",strtotime($valCadBusq[4])));
		}else{
			$cadenaFiltro .= sprintf(" AND fecha_registro BETWEEN '%s' AND '%s'",
					date("Y-m-d",strtotime($valCadBusq[3])),
					date("Y-m-d",strtotime($valCadBusq[4])));
		}			
	}
        
	if ($valCadBusq[5] != ""){
		if ($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE id_beneficiario_proveedor = '%s'",$valCadBusq[5]);
		}else{
			$cadenaFiltro .= sprintf(" AND id_beneficiario_proveedor = '%s'",$valCadBusq[5]);
		}
	}
	
	if ($valCadBusq[6] != ""){
		if ($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE concepto LIKE %s",
								valTpDato("%%".$valCadBusq[6]."%%","text"));
		}else{
			$cadenaFiltro .= sprintf(" AND concepto LIKE %s",
								valTpDato("%%".$valCadBusq[6]."%%","text"));
		}
	}
	
	$query = sprintf("SELECT * FROM vw_te_cheques".$cadenaFiltro);
        
	if($campOrd == "fecha_cheque" OR $campOrd == "fecha_registro"){//agrupar por fecha y luego ordenar por numero
		$campOrd2 = $campOrd." ".$tpOrd.", numero_cheque DESC";
	}else{
		$campOrd2 = $campOrd." ".$tpOrd;
	}
        
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s ", $campOrd2) : "";
	$queryLimit = sprintf(" %s %s ", $query, $sqlOrd);
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$queryLimit); }	
		
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return die(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
        
	$htmlTblIni .= "<table border=\"1\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Estado</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Empresa</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Proveedor</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">N&uacute;mero Cheque</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Monto Cheque</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Fecha Registro</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Fecha Aplicaci&oacute;n</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Fecha Conciliaci&oacute;n</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Concepto</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Banco Compa&ntilde;ia</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Cuenta Compa&ntilde;ia</th>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
		$contFila++;
				
		if ($row['fecha_aplicacion'] == null){
			$fechaAplicacion = "-";
		}else{
			$fechaAplicacion = date("d-m-Y",strtotime($row['fecha_aplicacion']));
		}
		
		if ($row['fecha_conciliacion'] == null){
			$fechaConciliacion = "-";
		}else{
			$fechaConciliacion = date("d-m-Y",strtotime($row['fecha_conciliacion']));
		}
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".estadoDocumento($row['estado_documento'],$row['entregado'])."</td>";
			$htmlTb .= "<td align=\"center\" >".empresa($row['id_empresa'])."</td>";
			$htmlTb .= "<td>".NombreBP($row['beneficiario_proveedor'],$row['id_beneficiario_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_cheque']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_cheque'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaAplicacion."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaConciliacion."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['concepto'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroCuentaCompania']."</td>";
		$htmlTb .= "</tr>";	
	}
	
	$htmlTblFin .= "</table>";

	return $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;
}



function NombreBP($Bene_Prove,$id){
	if ($Bene_Prove == 1){		
		$query = sprintf("SELECT nombre FROM cp_proveedor WHERE id_proveedor = '%s'",$id);
		$rs = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_array($rs);
		
		$respuesta = utf8_encode($row['nombre']);
	}else{		
		$query = sprintf("SELECT nombre_beneficiario FROM te_beneficiarios WHERE id_beneficiario = '%s'",$id);
		$rs = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_array($rs);
		
		$respuesta = utf8_encode($row['nombre_beneficiario']);		
	}
		
	return $respuesta;
}

function empresa($id){
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = utf8_encode($row['nombre_empresa']);
	
	return $respuesta;
}

function estadoDocumento($id,$entregado){

	$query = sprintf("SELECT * FROM te_estados_principales WHERE id_estados_principales = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if (!$entregado){
		$respuesta = "No entregado";
	}else{
		$respuesta = "Entregado";
	}
	
	$respuesta .= " - ".utf8_encode($row["descripcion"]);
	
	return $respuesta;
}

?>