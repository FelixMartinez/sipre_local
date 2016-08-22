<?php
require_once('../connections/conex.php');

@session_start();

//echo var_dump($_POST);exit;
$id_solicitud_compra = getmysqlnum($_POST['id_solicitud_compra']);
$estado = getmysqlnum($_POST['estado']);
$codigo_empleado = excape(strtoupper($_POST['codigo_empleado']));
$nuevo_estado = $estado + 1;

switch ($nuevo_estado){
	case 1:
		$campo="id_empleado_solicitud";
		$campof="fecha_empleado_solicitud";
		break;
	case 2:
		$campo="id_empleado_aprobacion";
		$campof="fecha_empleado_aprobacion";
		break;
	case 3:
		$campo="id_empleado_conformacion";
		$campof="fecha_empleado_conformacion";
		break;
	case 4:
		$campo="id_empleado_proceso";
		$campof="fecha_empleado_proceso";
		break;
}

conectar();
//verifica si existe el empleado
$id_empleado = getmysql(sprintf("SELECT pg_empleado.id_empleado
FROM pg_empleado empleado
  INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
  INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
WHERE empleado.codigo_empleado = '%s'
	AND dep.id_empresa = %s;",
	$codigo_empleado,
	getempresa()));
	
if ($id_empleado == "") {
?>
	<script type="text/javascript" language="javascript">
		alert('No existe el empleado especificado.');
		window.location='ga_solicitud_compras_editar.php?view=1&id=<?php echo $id_solicitud_compra; ?>';
	</script>
<?php
	exit;
}
//echo $_SESSION['idUsuarioSysGts'].' '.$id_empleado;exit;
//ahora busca el id del elmpleado del usuario:

$id_empleado_usuario = getmysql(sprintf("SELECT id_empleado FROM pg_usuario
WHERE id_usuario = %s;",
	$_SESSION['idUsuarioSysGts']));

if ($id_empleado_usuario != $id_empleado) {
?>
	<script type="text/javascript" language="javascript">
		alert('Debe ingresar al sistema con su clave de usuario.');
		window.location='ga_solicitud_compras_editar.php?view=1&id=<?php echo $id_solicitud_compra; ?>';
	</script>
<?php
	exit;
}
//comproeba si tiene permisos

//modifica el estado de la solicitud:
$sql = sprintf("UPDATE ga_solicitud_compra SET
	id_estado_solicitud_compras = %s,
	%s = %s,
	%s = CURRENT_DATE()
WHERE id_solicitud_compra = %s;",
	$nuevo_estado,
	$campo,
	$id_empleado,
	$campof,
	$id_solicitud_compra);
conectar();
$result = mysql_query($sql,$conex);
if (!$result) {
	echo 'error: ['.mysql_error($conex).'] :'.$sql;
	exit;
}
?>
<script type="text/javascript" language="javascript">
	alert('Se ha modificado con exito');
	window.location='ga_solicitud_compras_editar.php?view=1&id=<?php echo $id_solicitud_compra; ?>';
</script>