<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();

//Comienzo de la transaccion BEGIN 
$conAd = ConectarBD();
//$SqlStr = "Select a.codigo,a.descripcion from adcontabilidad.company a
//where   a.codigo <> 'BASEPRUEBA' and  a.codigo <> 'oriomka_inavi' ORDER BY a.codigo";
$SqlStr = "Select a.codigo,a.descripcion from adcontabilidad.company a
where   a.codigo ='E2200000' OR  a.codigo = 'E2300000' ORDER BY a.codigo";

$excAd = EjecutarExec($conAd,$SqlStr) or die($SqlStr);
     while ($rowAd=ObtenerFetch($excAd)){
            $TablaEnc = $rowAd[0];
			$DesTabla = $rowAd[1];	
echo "$TablaEnc <br>";
$SqlStr = "Begin";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			
			
/*$SqlStr = "insert into oriomka_inavi.enc_dif
  select * from $TablaEnc.enc_diario";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "insert into oriomka_inavi.movimiendif
  select * from $TablaEnc.movimien";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
*/
$SqlStr = "insert into oriomka_inavi.enc_dif
  select * from $TablaEnc.enc_dif";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "insert into oriomka_inavi.movimiendif
  select * from $TablaEnc.movimiendif";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
/*$SqlStr = "insert into oriomka_inavi.conse
  select * from $TablaEnc.conse";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);		
*/
}
$SqlStr = "commit ";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);	
	
	
 echo "Proceso Finalizado Satisfactoriamente";
	