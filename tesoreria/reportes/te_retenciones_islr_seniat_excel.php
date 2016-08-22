<?php
require("../../connections/conex.php");

$fechaArchivo = "";
if(isset($_GET['fecha']) && $_GET['fecha'] != ""){
	$fechaArchivo = " ".$_GET['fecha'];
}

header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=\"Listado Retenciones ISLR SENIAT".$fechaArchivo.".xls\"");
header("Pragma: no-cache");
header("Expires: 0");

$query = sprintf("SELECT
						nombre_empresa,
						rif
					FROM pg_empresa
					WHERE id_empresa = %s",
		valTpDato($_GET['empresa'],"int"));
$rs = mysql_query($query) or die(mysql_error());
$row = mysql_fetch_array($rs);

$fechaPeriodo = implode("", array_reverse(explode("-",$_GET['fecha'])));

echo "<table border=1> ";

echo "<tr>";
	echo "<th colspan=\"7\" style=\"background-color: #bfbfbf;\"><b>Agente de Retenci&oacute;n: </b>".$row['nombre_empresa']."</th>";
	echo "<td style=\"background-color: #bfbfbf;\"><b>RIF Agente</b></td>";
	echo "<td align=\"right\">".str_replace("-","",$row['rif'])."</td>";
echo "</tr>";
echo "<tr>";
	echo "<td colspan=\"7\"  style=\"background-color: #bfbfbf;\"></td>";
	echo "<td style=\"background-color: #bfbfbf;\"><b>Periodo</b></td>";
	echo "<td align=\"right\">".$fechaPeriodo."</td>";
echo "</tr>";

echo "<tr>";
	echo "<th>N&uacute;mero Comprobante<br>(Generado Sistema)</th>";
	echo "<th>Nro</th>";
	echo "<th>Rif Retenido</th>";	
	echo "<th>Numero Factura</th>";
	echo "<th>Numero Control</th>";
	echo "<th>Fecha Registro</th>";
	echo "<th>Codigo Concepto</th>";
	echo "<th>Base imponible</th>";
	echo "<th>Porcentaje Retencion</th>";
echo"</tr>";

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";

if($_GET['empresa'] != ''){
    $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
    $sqlBusq .= $cond."vw_te_retencion_cheque.id_empresa = '".$_GET['empresa']."'";
}
                                
if ($_GET['fecha'] != ''){
    $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
    $FechaConsulta=date("Y/m",strtotime('01-'.$_GET['fecha']));
    $sqlBusq .= $cond."DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%Y/%m') = '".$FechaConsulta."'";
}

if ($_GET['proveedor'] != ''){
    $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
    $sqlBusq .= $cond."vw_te_retencion_cheque.id_proveedor = '".$_GET['proveedor']."'";
}

if ($_GET['txtCriterio'] != ''){
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf(" (vw_te_retencion_cheque.numero_factura LIKE %s 
								OR vw_te_retencion_cheque.numero_control_factura LIKE %s) ",
								valTpDato('%'.$_GET['txtCriterio'].'%', 'text'),
								valTpDato('%'.$_GET['txtCriterio'].'%', 'text'));
}

if ($_GET['listAnulado'] != ''){
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if($_GET['listAnulado'] == 1){
		$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado = 1 ");
	}else{
		$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado IS NULL ");
	}
}

if ($_GET['listPago'] != ''){
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.tipo_documento = %s",
								valTpDato($_GET['listPago'],"int"));
}

$queryRetencion = "SELECT 
						vw_te_retencion_cheque.rif_proveedor,
						vw_te_retencion_cheque.nombre,
						vw_te_retencion_cheque.numero_control_factura,
						vw_te_retencion_cheque.numero_factura,
						vw_te_retencion_cheque.id_factura,
						vw_te_retencion_cheque.codigo,
						vw_te_retencion_cheque.subtotal_factura,
						vw_te_retencion_cheque.monto_retenido,
						vw_te_retencion_cheque.porcentaje_retencion,
						vw_te_retencion_cheque.id_retencion_cheque,
						vw_te_retencion_cheque.base_imponible_retencion,
						vw_te_retencion_cheque.tipo,
						vw_te_retencion_cheque.tipo_documento, 
						vw_te_retencion_cheque.anulado,
						DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%d-%m-%Y') as fecha_registro_formato
				  FROM vw_te_retencion_cheque 
				  ".$sqlBusq." 
				  ORDER BY id_retencion_cheque ASC";

$rsRetencion = mysql_query($queryRetencion) or die(mysql_error());
$cont=1;

while ($rowRetencion = mysql_fetch_array($rsRetencion)){

	echo "<tr>";
	echo "<td align='center' style='mso-ansi-font-weight:\"bold\"'>".$rowRetencion['id_retencion_cheque']."</td> ";
	echo "<td align='left'>".$cont."</td> ";
	
	if($rowRetencion['tipo']==1){//si es nota de cargo
		$query = sprintf("SELECT
								cp_proveedor.lrif,
								cp_proveedor.rif
							FROM cp_notadecargo 
							INNER JOIN cp_proveedor ON (cp_notadecargo.id_proveedor = cp_proveedor.id_proveedor)
							WHERE cp_notadecargo.id_notacargo = %s",
				$rowRetencion['id_factura']);
		$rs = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_array($rs);	
			
		echo "<td align='left'>".$row['lrif'].$row['rif']."</td> ";
	}else{ 
		echo "<td align='left'>".str_replace ("-","",$rowRetencion['rif_proveedor'])."</td> ";
	}
		
	echo "<td align='center'>".$rowRetencion['numero_factura']."</td> ";
	echo "<td align='center' style=\"mso-number-format:'@';\">".str_replace ("00-","",$rowRetencion['numero_control_factura'])."</td> "; 
	echo "<td align='center'>".$rowRetencion['fecha_registro_formato']."</td> ";
	echo "<td align='center'>".$rowRetencion['codigo']."</td> ";
	echo "<td align='right' style=\"mso-number-format:'0.00';\">".number_format($rowRetencion['base_imponible_retencion'],2,",","")."</td> ";
	echo "<td align='right' style=\"mso-number-format:'0.00';\">".number_format($rowRetencion['porcentaje_retencion'],2,",","")."</td> "; 
	echo "</tr> ";
	$cont+=1;
}

echo "</tr> ";
echo "</table> ";

?>