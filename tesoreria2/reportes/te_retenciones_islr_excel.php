<?php
require_once ("../../connections/conex.php");

header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=archivo.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border=1> ";


echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Rif Retenido</th>";
        echo "<th>Fecha Registro</th>";
        echo "<th>Numero Comprobante</th>";
        echo "<th>Numero Factura</th>";
        echo "<th>Numero Control</th>";
        echo "<th>Codigo Concepto</th>";
        echo "<th>Monto Operacion</th>";
        echo "<th>Porcentaje Retencion</th>";

echo"</tr>";

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond."vw_te_retencion_cheque.anulado IS NULL";

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

$queryRetencion = "SELECT 
                        vw_te_retencion_cheque.id_retencion_cheque,
                        vw_te_retencion_cheque.rif_proveedor,
                        vw_te_retencion_cheque.nombre,
                        vw_te_retencion_cheque.numero_control_factura,
                        vw_te_retencion_cheque.numero_factura,
                        vw_te_retencion_cheque.codigo,
                        vw_te_retencion_cheque.subtotal_factura,
                        vw_te_retencion_cheque.porcentaje_retencion,
                        vw_te_retencion_cheque.tipo,
                        vw_te_retencion_cheque.id_factura,
                        DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%d-%m-%Y') as fecha_registro_formato
                      FROM
                        vw_te_retencion_cheque".$sqlBusq." ORDER BY id_retencion_cheque ASC";

				
$rsRetencion = mysql_query($queryRetencion) or die(mysql_error());
$cont=1;

while ($rowRetencion = mysql_fetch_array($rsRetencion)){
	

	echo "<tr>";
	echo "<td align='left'>".$cont."</td> ";
	
	if($rowRetencion['tipo']==1){
	$query = sprintf("SELECT 
                            cp_proveedor.lrif,
                            cp_proveedor.rif
                          FROM
                            cp_notadecargo
                            INNER JOIN cp_proveedor ON (cp_notadecargo.id_proveedor = cp_proveedor.id_proveedor) WHERE cp_notadecargo.id_notacargo = %s",$rowRetencion['id_factura']);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
		
	echo "<td align='left'>".$row['lrif'].$row['rif']."</td> ";
	}
	else echo "<td align='left'>".str_replace ("-","",$rowRetencion['rif_proveedor'])."</td> ";
	echo "<td align='center'>".$rowRetencion['fecha_registro_formato']."</td> ";
	echo "<td align='center'>".$rowRetencion['id_retencion_cheque']."</td> ";
	echo "<td align='center'>".$rowRetencion['numero_factura']."</td> ";
	echo "<td align='center' style=\"mso-number-format:'@';\">".str_replace ("00-","",$rowRetencion['numero_control_factura'])."</td> "; 
	echo "<td align='center'>".$rowRetencion['codigo']."</td> ";
	echo "<td align='right' style=\"mso-number-format:'0.00';\">".number_format($rowRetencion['subtotal_factura'],2,",","")."</td> ";
	echo "<td align='right' style=\"mso-number-format:'0.00';\">".number_format($rowRetencion['porcentaje_retencion'],2,",","")."</td> "; 
	echo "</tr> ";
	$cont+=1;
}
echo "</tr> ";
echo "</table> ";
?>