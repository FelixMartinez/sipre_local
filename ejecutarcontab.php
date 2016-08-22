<?php session_start();
$_SESSION["sBasedeDatos"] = "oriomka_prueba";
$_SESSION["sServidor"] = "localhost";
include_once('GenerarEnviarContabilidadDirecto.php');
 eliminarRenglones();	
 $idobjeto = 8412;
 $idct='01';
 $idcc='01';
 
generarAnticiposRe(1,$Desde="",$Hasta="");
echo "paso";
?>