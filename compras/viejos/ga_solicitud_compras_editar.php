<?php
require_once "../connections/conex.php";
@session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
if (!(validaAcceso("ga_solicitud_compra_list"))
&& !(validaAcceso("ga_solicitud_compra_list","editar"))){
	echo "
	<script>
		alert('Acceso Denegado');
		window.location = 'ga_solicitud_compra_list.php';
	</script>";
	
	exit;
}
/* Fin Validación del Módulo */

$id_solicitud_compra = getemptynum($_GET['id'],'null');

if ($id_solicitud_compra == 'NULL') {
	echo 'No se ha seleccionado';
	exit;
}

$sql = "SELECT 
	id_solicitud_compra,
	".mysqlfecha('fecha_solicitud')." AS fecha_solicitud,
	id_unidad_centro_costo,
	tipo_compra,
	id_proveedor,
	justificacion_proveedor,
	sustitucion,
	presupuestado,
	justificacion_compra,
	id_estado_solicitud_compras,
	id_empleado_solicitud,
	".mysqlfecha('fecha_empleado_solicitud')." AS fecha_empleado_solicitud,
	id_empleado_aprobacion,
	".mysqlfecha('fecha_empleado_aprobacion')." AS fecha_empleado_aprobacion,
	id_empleado_conformacion,
	".mysqlfecha('fecha_empleado_conformacion')." AS fecha_empleado_conformacion,
	id_empleado_proceso,
	".mysqlfecha('fecha_empleado_proceso')." AS fecha_empleado_proceso,
	".mysqlfecha('fecha_creacion')." AS fecha_creacion,
	numero_actualizacion,
	codigo_unidad_centro_costo,
	nombre_unidad_centro_costo,
	nombre_departamento,
	codigo_departamento,
	nombre_empresa,
	codigo_empresa,
	id_departamento,
	numero_solicitud,
	id_empresa,
	id_empleado_solicitud,
	id_empleado_condicionamiento,
	motivo_condicionamiento,".
	mysqlfecha('fecha_empleado_condicionamiento')." AS fecha_empleado_condicionamiento
FROM vw_ga_solicitudes
WHERE id_solicitud_compra = ".$id_solicitud_compra.";";
//echo $sql;
conectar();
$result = mysql_query($sql,$conex);
	
if ($result) {
	if (mysql_num_rows($result) == 0) {
		echo 'No existe el registro solicitado';
		exit;
	}
	$row = mysql_fetch_assoc($result);
	
	$fecha_solicitud=$row['fecha_solicitud'];
	$id_departamento=$row['id_departamento'];
	$numero_solicitud=$row['numero_solicitud'];
	$centro_costo=$row['centro_costo'];
	$codigo_centro_costo=$row['codigo_centro_costo'];
	$tipo_compra[$row['tipo_compra']] = array('checked'=>'checked');
	$id_proveedor=$row['id_proveedor'];
	$justificacion_proveedor = $row['justificacion_proveedor'];
	$observaciones = $row['observaciones'];
	$v_sustitucion = $row['sustitucion'];
	$sustitucion[$v_sustitucion] = array('checked'=>'checked');
	$v_presupuestado = $row['presupuestado'];
	if ($v_presupuestado == 1) {
		$presupuestado[0] = array('checked'=>'checked');
	}
	$justificacion_compra = $row['justificacion_compra'];
	$estado = $row['id_estado_solicitud_compras'];
	$id_empleado_solicitud=$row['id_empleado_solicitud'];
	$fecha_empleado_solicitud=$row['fecha_empleado_solicitud'];
	$id_empleado_aprobacion=$row['id_empleado_aprobacion'];
	$fecha_empleado_aprobacion=$row['fecha_empleado_aprobacion'];
	$id_empleado_conformacion=$row['id_empleado_conformacion'];
	$fecha_empleado_conformacion=$row['fecha_empleado_conformacion'];
	$id_empleado_proceso=$row['id_empleado_proceso'];
	$fecha_empleado_proceso=$row['fecha_empleado_proceso'];
	$fecha_creacion=$row['fecha_creacion'];
	$fecha_modificacion=$row['fecha_modificacion'];
	$numero_actualizacion=$row['numero_actualizacion'];
	$nombre_departamento=$row['nombre_departamento'];
	$codigo_departamento=$row['codigo_departamento'];
	$nombre_empresa = $row['nombre_empresa'];
	$codigo_empresa=$row['codigo_empresa'];
	$id_empresa=$row['id_empresa'];
	$motivo_condicionamiento=$row['motivo_condicionamiento'];
	
	$id_empleado_solicitud=$row['id_empleado_solicitud'];
	if($id_proveedor!=0){
		$nombre_proveedor=getmysql("select nombre from cp_proveedor where id_proveedor=".$id_proveedor.";");
	}
	$nombre_unidad_centro_costo=$row['nombre_unidad_centro_costo'];
	$id_empleado_condicionamiento=$row['id_empleado_condicionamiento'];
	$fecha_empleado_condicionamiento=$row['fecha_empleado_condicionamiento'];
	$id_unidad_centro_costo=$row['id_unidad_centro_costo'];
	if($_GET['view']!='e'){
		$modovista=true;
	}
	switch ($estado){
		case 0:
			$nombre_estado="Enviar Solicitud";
			$estado_solicitud="NO Enviada";
			break;
		case 1:
			$nombre_estado="Aprobar";
			$estado_solicitud="En espera de Aprobacion";
			break;
		case 2:
			$nombre_estado="Conformar";
			$estado_solicitud="APROBADA - En espera de Conformación";
			break;
		case 3:
			$nombre_estado="Procesar";
			$estado_solicitud="CONFORMADA - En espera de Proceso";
			break;
		case 5:
			$nombre_estado="Procesada por Orden";
			$estado_solicitud="EN ORDEN DE COMPRA";
			break;
		case 6:
			$nombre_estado="";
			$estado_solicitud="Condicionamiento";
			break;
		case 7:
			$nombre_estado="";
			$estado_solicitud="Rechazo";
			break;
		default:
			$nombre_estado="";
			$estado_solicitud="CULMINADA";
			break;
	}
	
	//consultando los empleados
	$sqlempleado = "SELECT CONCAT_WS(' ', pg_empleado.apellido, pg_empleado.nombre_empleado) AS empleado, codigo_empleado FROM pg_empleado WHERE id_empleado = %s";
	
	if($id_empleado_solicitud!=0){
		$resultempleado=mysql_query(sprintf($sqlempleado,$id_empleado_solicitud));
		if($resultempleado){
			$rowe=mysql_fetch_assoc($resultempleado);
			$codigo_empleado_solicitud=$rowe['codigo_empleado'];
			$nombre_empleado_solicitud=$rowe['empleado'];
		}
	}
	
	if($id_empleado_aprobacion!=0){
		$resultempleado=mysql_query(sprintf($sqlempleado,$id_empleado_aprobacion));
		if($resultempleado){
			$rowe=mysql_fetch_assoc($resultempleado);
			$codigo_empleado_aprobacion=$rowe['codigo_empleado'];
			$nombre_empleado_aprobacion=$rowe['empleado'];
		}
	}
	
	if($id_empleado_conformacion!=0){
		$resultempleado=mysql_query(sprintf($sqlempleado,$id_empleado_conformacion));
		if($resultempleado){
			$rowe=mysql_fetch_assoc($resultempleado);
			$codigo_empleado_conformacion=$rowe['codigo_empleado'];
			$nombre_empleado_conformacion=$rowe['empleado'];
		}
	}
	
	if($id_empleado_proceso!=0){
		$resultempleado=mysql_query(sprintf($sqlempleado,$id_empleado_proceso));
		if($resultempleado){
			$rowe=mysql_fetch_assoc($resultempleado);
			$codigo_empleado_proceso=$rowe['codigo_empleado'];
			$nombre_empleado_proceso=$rowe['empleado'];
		}
	}
	
	if($id_empleado_condicionamiento!=0){
		$resultempleado=mysql_query(sprintf($sqlempleado,$id_empleado_condicionamiento));
		if($resultempleado){
			$rowe=mysql_fetch_assoc($resultempleado);
			$codigo_empleado_condicionamiento=$rowe['codigo_empleado'];
			$nombre_empleado_condicionamiento=$rowe['empleado'];
		}
	}
	//consultando los detalles:
	$cargar_datos='';
	
	$sqldetalles = "SELECT 
		id_detalle_solicitud_compra,
		id_solicitud_compra,
		id_articulo,
		cantidad,
		precio_sugerido,
		".mysqlfecha('fecha_requerida')." AS fecha_requerida,
		estado_proceso,
		codigo_articulo,
		id_marca,
		id_tipo_articulo,
		codigo_articulo_prov,
		descripcion,
		id_subseccion,
		stock_maximo,
		stock_minimo,
		unidad,
		foto
	FROM vw_ga_detalle_articulos_solicitud_compra
	WHERE id_solicitud_compra = ".$id_solicitud_compra.";";
	$resultdetalles = mysql_query($sqldetalles,$conex);
	if ($resultdetalles) {
		while ($rowd = mysql_fetch_assoc($resultdetalles)) {
			$cargar_datos .= sprintf("agregar({
				id_detalle_solicitud_compra:'%s',
				id_articulo:%s,
				unidad:'%s',
				cantidad:%s,
				codigo:'%s',
				descripcion:'%s',
				precio:'%s',
				fecha_requerida:'%s'});",
				$rowd['id_detalle_solicitud_compra'],
				$rowd['id_articulo'],
				$rowd['unidad'],
				$rowd['cantidad'],
				utf8_encode($rowd['codigo_articulo_prov']),
				($_GET['view'] == "e") ? preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/"," ",utf8_encode($rowd['descripcion']))) : preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($rowd['descripcion']))),
				numformat($rowd['precio_sugerido']),
				$rowd['fecha_requerida']);
		}
		if (!$modovista) {
			$cargar_datos .= "agregar();";
		}
		if ($row['tipo_compra'] == 0) {
			$cargar_datos .= "desabilitar_stock();";
		}
		$cargar_datos.="recalcula(null);
			var f=document.getElementById('solicitud');
			setRadioEnabled(f.elements['tipo_compra'],false);
			desabilitar_stock();";
	} else {
		echo 'ERROR Nº:'.mysql_errno($conex).' ['.mysql_error($conex).'] sql:'.$sqldetalles;
		exit;
	}
	
	if ($_GET['view'] == "e") {
		if ($estado == 5) {
			echo "<script>
				alert('No se puede editar la solicitud, ya se encuentra en una Orden de Compra');
				window.location='ga_solicitud_compra_list.php';
			</script>";
			exit;
		}
	}
	
	include "ga_solicitud_compras_insertar.php";
} else {
	echo 'ERROR Nº:'.mysql_errno($conex).' ['.mysql_error($conex).'] sql:'.$sql;
	exit;
}
?>