<?php session_start();
$_SESSION["sBasedeDatos"] = "oriomka_inavi";
$_SESSION["sServidor"] = "localhost";
$v=$_REQUEST["v"]; 
include_once('GenerarEnviarContabilidadDirecto.php');
 //eliminarRenglones();	
 $idobjeto = 8412;
 $idct='01';
 $idcc='01';
 

 
 eval("$v");
echo "paso";
?>