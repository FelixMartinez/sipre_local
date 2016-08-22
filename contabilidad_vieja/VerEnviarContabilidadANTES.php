<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title>.: SIPRE 2.0 :. Contabilidad - Movimientos Contables</title>
<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">

<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

</head>
<body>
<?php
include_once('FuncionesPHP.php');
 $con = ConectarBD();
 if($idDia != "01-01-1900"){
        $SqlStr="select a.codigo,b.descripcion,a.desripcion,a.debe,a.haber,a.documento from movenviarcontabilidad a
			left join cuenta b on a.codigo = b.codigo
			where fecha = '$idDia'
			and a.ct = '$idct'
			and a.cc = '$idcc'
			order by comprobant,documento,a.tipo ";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		$totalrow = mysql_num_rows($exc);
		if ($totalrow>0) {
				echo "<table  width='100%'  name='mitabla'  border='0'  align='center'>";
				echo "<tr class='tituloColumna'><td style='background:#58ACFA' align='center'><b>C&oacute;digo</td>
			 	<td style='background:#58ACFA' align='center' ><b> Cuenta</td>
			 	<td style='background:#58ACFA' align='center'><b> Descripci&oacute;n</td>
			 	<td style='background:#58ACFA' align='center'><b> Debe</td>
			 	<td style='background:#58ACFA' align='center'><b> Haber</td>
			 	<td style='background:#58ACFA' align='center'><b> Documento</td></tr>";
			}
		$documentoAnt = "";
		$color = "#A4A4A4";
		$sumd = 0;
		$sumh = 0;
		while ($row=ObtenerFetch($exc)){
		        $codigo= $row[0];	
				$descuenta= $row[1];	
				$desmov = $row[2];
				$debe = number_format($row[3],2);
				$haber =number_format($row[4],2);
				$documento = $row[5];
				$sumd = $sumd+$row[3];
				$sumh = $sumh+$row[4];
				if ($documento != $documentoAnt){
				      if($color != ""){
							$color = "";
					   }else{
							$color = "#A4A4A4";
                       }
                  $documentoAnt = $documento;					   
				}
				echo "<tr style='background:$color'>";
				echo "<td  > <font size=-1>
				$codigo
				 </font>
			</td>
			<td  ><font size=-1>
				$descuenta
				</font>
			</td>
			<td  ><font size=-1>
				$desmov
				</font>
			</td>
			<td align=right ><font size=-1>
				$debe
				</font>
			</td>
			<td align=right ><font size=-1>
				$haber</font>
			</td>
			<td><font size=-1>
				$documento</font>
			</td>
		</tr>";
		}
		$h = number_format($sumh,2);
		$d = number_format($sumh,2);
		if ($h != $d){
				      
							$colorh = "red";
					   }else{
							$colorh = "#9FF781";
                       }
        if ($totalrow>0) {
			echo "<tr class='tituloColumna'><td colspan='3' style='border:0;text-align:right;font-weight:bold'>Total:</td>";
			echo "<td style='background:$colorh' align='right'><font size=-1>
					$d
					</font></td>";
			echo "<td  style='background:$colorh' align='right'><font size=-1>
					$h
					</font></td></tr>";

			echo "</table>";
		}
}
?>
</body>
</html>