<?php 
include("FuncionesPHP.php");
$FechaCierre = "2007-06-30";
?>
<table align="center" width="600">  
<?php 
$con = ConectarBD();
 		$SqlStr='delete from movimientemp2'  ;
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
        $sTabla='migracion_saldos';
        $sCampos=' cuenta';
		$sCampos.=',saldo_act';
		$SqlStr='Select '.$sCampos.' from '.$sTabla." where saldo_act <> 0"   ;
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		$primera = true;
       if (NumeroFilas($exc)>0){
     		$iFila = -1;
			$iContador =0;
			$CuentaAnt = '';
			$sPrimera = "SI";
            while ($row = ObtenerFetch($exc)) {
			$iFila++;				 
				$iContador++;
			      $Codigo = trim(ObtenerResultado($exc,1,$iFila));	
				  $Saldo_act = strval(ObtenerResultado($exc,2,$iFila));
					$sTabla='migracion_plan';
					$sCampos='codigo';
					$sCondicion="cod_sus='$Codigo'";
					$SqlStr='Select '.$sCampos.' from '.$sTabla. " where $sCondicion";							
					$exc1 = EjecutarExec($con,$SqlStr) or die($SqlStr);
					if (NumeroFilas($exc1)>0){
					   $CodigoFound =  trim(ObtenerResultado($exc1,1,0)); 
					if ($Saldo_act < 0){
					   $GrabaHaber =  substr($Saldo_act,1,strlen($Saldo_act));
					   $GrabaDebe =  0;
					}else{
					   $GrabaDebe =  $Saldo_act;
					   $GrabaHaber =  0;
					}   
					if ($GrabaHaber != 0  || $GrabaDebe != 0){
						$sCondicion1 = "substring(codigo,1,length(rtrim('". $CodigoFound ."'))) ='" . trim($CodigoFound) . "'";
						$sCampos1 = "count(*)";
						$sTabla1 = "cuenta";
						$sql = "Select " .$sCampos1. " from ". $sTabla1  ." where " .$sCondicion1 ;
						$rs4 = EjecutarExec($con,$sql); 
						$ss =NumeroFilas($rs4);
						if (NumeroFilas($rs4)>0){
						if (ObtenerResultado($rs4,1)>1){
							echo "No es cuenta de movimiento $CodigoFound";	
						}
}     				 
					$SqlStr="insert into movimientemp2 (codigo,descripcion,debe,haber,dt,ct,documento,fecha,numero)
					values ('$CodigoFound','Asiento de Apertura',$GrabaDebe,$GrabaHaber,'01','01','$FechaCierre','$FechaCierre',$iContador)";
					$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
					}
					}else{
					   echo "$Codigo <BR>";
					}
					
		          }	
		}		  
			echo "Finalizado el proceso de Migracion";
 ?>
 </table>