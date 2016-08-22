<?php
require("../../connections/conex.php");

$valCadBusq = array_values(json_decode($_GET['valBusq'], true));

$fechaArchivo = "";
if($valCadBusq[2] != ""){
	$fechaArchivo = " ".$valCadBusq[2];
}

header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=\"Listado Retenciones ISLR".$fechaArchivo.".xls\"");
header("Pragma: no-cache");
header("Expires: 0");

$query = sprintf("SELECT
						nombre_empresa,
						rif
					FROM pg_empresa
					WHERE id_empresa = %s",
		valTpDato($valCadBusq[0],"int"));
$rs = mysql_query($query) or die(mysql_error());
$row = mysql_fetch_array($rs);

$fechaPeriodo = implode("", array_reverse(explode("-",$valCadBusq[2])));

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

//$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
//$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

$rsLimit = mysql_query($query) or die(mysql_error());
/*
echo "<table border=\"0\" width=\"100%\">";
	echo "<tr align=\"center\" class=\"tituloColumna\">";
		echo "<th colspan=\"17\" align=\"left\">".$row['nombre_empresa']." ".$row['rif']." ".$fechaArchivo."</th>";
	echo "</tr>";
echo "</table>";*/

echo "<br>";
		
echo "<table border=\"1\" width=\"100%\">";
	echo "<tr align=\"center\" class=\"tituloColumna\">";
		echo "<th style=\"background-color: #bfbfbf;\">Estado</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Nro</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Fecha Registro</th>";
		echo "<th style=\"background-color: #bfbfbf;\">RIF Retenido</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Proveedor</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Tipo Doc.</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Nro. Documento</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Nro. Control</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Tipo Pago</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Monto Operaci&oacute;n</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Nro Comprobante</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Retenci&oacute;n</th>";
		echo "<th style=\"background-color: #bfbfbf;\">C&oacute;digo Concepto</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Base Retenci&oacute;n</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Monto Retenido</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Porcentaje Retenci&oacute;n</th>";
		echo "<th style=\"background-color: #bfbfbf;\">Sustraendo Retenci&oacute;n</th>";
	echo "</tr>";
	
	$cont = 0;
	$contb = 1;

while ($row = mysql_fetch_assoc($rsLimit)) {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltarTesoreria1" : "trResaltarTesoreria2";
	$contFila++;		
	
	if($row['anulado'] == 1){
		$imgAnulado = "<b>Anulado</b>";
	}else{
		$imgAnulado = "Activo";
	}
	
	echo "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
		echo "<td align=\"center\">".$imgAnulado."</td>";
		echo "<td align=\"center\">".$contb."</td>";
		echo "<td align=\"center\">".$row['fecha_registro_formato']."</td>";
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

			echo "<td align=\"center\">".$rowNota['lrif']."-".$rowNota['rif']."</td>";
			echo "<td align=\"center\">".$rowNota['nombre']."</td>";
		} else {
			echo "<td align=\"center\">".$row['rif_proveedor']."</td>";
			echo "<td align=\"center\">".$row['nombre']."</td>";
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
		
		echo "<td align=\"center\">".$tipoDocumento."</td>";
		echo "<td align=\"center\">".$row['numero_factura']."</td>";
		echo "<td align=\"center\">".$row['numero_control_factura']."</td>";
		echo "<td align=\"center\">".$tipoPago."</td>";
		echo "<td align=\"center\">".$row['subtotal_factura']."</td>";
		echo "<td align=\"center\">".$row['id_retencion_cheque']."</td>";
		echo "<td align=\"center\">".$row['descripcion']."</td>";
		echo "<td align=\"center\">".$row['codigo']."</td>";
		echo "<td align=\"center\">".$row['base_imponible_retencion']."</td>";
		echo "<td align=\"center\">".$row['monto_retenido']."</td>";
		echo "<td align=\"center\">".$row['porcentaje_retencion']."</td>";
		echo "<td align=\"center\">".$row['sustraendo_retencion']."</td>";
		$cont +=  $row['monto_retenido'];
		$contb += 1;
	echo "</tr>";
	
}

echo "</table>";

?>