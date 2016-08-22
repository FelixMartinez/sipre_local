<?php
require_once "../connections/conex.php";

@session_start();
	
/* Validación del Módulo */
include("../inc_sesion.php");
if(!(validaAcceso("ga_solicitud_compra_list"))
&& !(validaAcceso("ga_solicitud_compra_list","eliminar"))) {
	echo "
	<script>
		alert('Acceso Denegado');
		top.history.back();
	</script>";
}
/* Fin Validación del Módulo */

$id = $_GET['id'];
conectar();
$sql = "DELETE FROM ga_solicitud_compra WHERE id_solicitud_compra = ".$id.";";
	
$r = mysql_query($sql, $conex);
if (!$r) {
	echo "
	<script language='javascript' type='text/javascript'>
		alert('No se puede eliminar el registro ya que existen otros registros dependientes, consulte al administrador del sistema');
		window.location = 'ga_solicitud_compra_list.php';
	</script>";
	exit;		
}

cerrar();

echo "
<script language='javascript' type='text/javascript'>
	alert('Se ha eliminado la solicitud');
	window.location = 'ga_solicitud_compra_list.php';
</script>";
?>