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
		echo "<table  width='100%'  name='mitabla'  border='0'  align='center'>";
		$documentoAnt = "";
		$color = "#A4A4A4";
		while ($row=ObtenerFetch($exc)){
		        $codigo= $row[0];	
				$descuenta= $row[1];	
				$desmov = $row[2];
				$debe = number_format($row[3],2);
				$haber =number_format($row[4],2);
				$documento = $row[5];
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
		echo "</table>";
}
?>