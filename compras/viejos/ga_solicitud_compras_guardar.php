<?php
require_once('../connections/conex.php');
@session_start();

require_once("../inc_sesion.php");

//obteniendo variables:
//echo var_dump($_POST);exit;
$id_solicitud_compra = getmysqlnum($_POST['id_solicitud_compra']);
$id_unidad_centro_costo = getmysqlnum($_POST['id_unidad_centro_costo']);

//$numero_solicitud=excape($_POST['numero_solicitud']);
$fecha_solicitud = setmysqlfecha($_POST['fecha_solicitud']);


$tipo_compra = getmysqlnum($_POST['tipo_compra']);

$id_proveedor = getmysqlnum($_POST['id_proveedor']);
if ($id_proveedor == 0) {
	$justificacion_proveedor = '';
	$id_proveedor = 'NULL';
} else {
	$justificacion_proveedor = excape($_POST['justificacion_proveedor']);
}

$idEmpresa = getmysqlnum($_POST['id_empresa']);
$observaciones = excape($_POST['observaciones']);
$sustitucion = getmysqlnum($_POST['sustitucion']);
$presupuestado = isset($_POST['presupuestado']) ? 1: 0;

//echo $sustitucion;exit;

$justificacion_compra = excape($_POST['justificacion_compra']);
$estado = getmysqlnum($_POST['estado']);
$id_empleado_solicitud = getmysqlnum($_POST['id_empleado_solicitud']);
$fecha_empleado_solicitud = setmysqlfecha($_POST['fecha_empleado_solicitud']);
$id_empleado_aprobacion = getmysqlnum($_POST['id_empleado_aprobacion']);

$fecha_empleado_aprobacion = setmysqlfecha($_POST['fecha_empleado_aprobacion']);
$id_empleado_conformacion = getmysqlnum($_POST['id_empleado_conformacion']);
$fecha_empleado_conformacion = setmysqlfecha($_POST['fecha_empleado_conformacion']);
$id_empleado_proceso = getmysqlnum($_POST['id_empleado_proceso']);
$fecha_empleado_proceso = setmysqlfecha($_POST['fecha_empleado_proceso']);
//$fecha_creacion=setmysqlfecha($_POST['fecha_creacion']);
$fecha_modificacion = setmysqlfecha($_POST['fecha_modificacion']);
$numero_actualizacion = getmysqlnum($_POST['numero_actualizacion']);
//$nombre_departamento=excape($_POST['nombre_departamento']);
//$codigo_departamento=excape($_POST['codigo_departamento']);
//$nombre_empresa=excape($_POST['nombre_empresa']);
//$codigo_empresa=excape($_POST['codigo_empresa']);
$id_empresa = excape($_POST['id_empresa']);

conectar();

$numero_solicitud=getmysql(sprintf("SELECT max(numero_solicitud)+1 FROM vw_ga_solicitudes
WHERE id_empresa = %s",
	$id_empresa));
if ($numero_solicitud == "") {
	$numero_solicitud = 1;
}


if ($_POST['id_solicitud_compra'] == "") {
	if (!validaAcceso("ga_solicitud_compra_list","insertar")){
		echo("
		<script type='text/javascript'>
			alert('Acceso Denegado');
			window.location='ga_solicitud_compra_list.php';
		</script>");
		exit;
	}
	
	//insert string
	$id_empleado_solicitud = getmysql(sprintf("SELECT id_empleado FROM pg_usuario
	WHERE id_usuario = %s;",
		$_SESSION['idUsuarioSysGts']));
	
	$sql = "INSERT INTO ga_solicitud_compra (id_empresa, id_estado_solicitud_compras, fecha_empleado_solicitud, fecha_creacion, fecha_solicitud, numero_solicitud, id_unidad_centro_costo, tipo_compra, id_proveedor, presupuestado, sustitucion, observaciones, justificacion_compra, justificacion_proveedor, id_empleado_solicitud, id_solicitud_compra)
	VALUES(%s, 1, CURRENT_DATE(), NOW(), CURRENT_DATE(), ".$numero_solicitud.",
	%s,%s,%s,%s,%s,'%s','%s','%s',%s,%s);";
	$id_solicitud_compra = 'default';
		//echo $sql;
} else {
	if (!validaAcceso("ga_solicitud_compra_list","editar")){
		echo("
		<script type='text/javascript'>
			alert('Acceso Denegado');
			window.location='ga_solicitud_compra_list.php';
		</script>");
		exit;
	}
	
	//update string
	$sql = "UPDATE ga_solicitud_compra SET
		id_empresa = %s,
		fecha_modificacion = NOW(),
		id_unidad_centro_costo = %s,
		tipo_compra = %s,
		id_proveedor = %s,
		presupuestado = %s,
		sustitucion = %s,
		observaciones = '%s',
		justificacion_compra = '%s',
		justificacion_proveedor = '%s',
		id_empleado_solicitud = %s
	WHERE id_solicitud_compra=%s;";
}
$sql = sprintf($sql,
	$idEmpresa,
	$id_unidad_centro_costo,
	$tipo_compra,
	$id_proveedor,
	$presupuestado,
	$sustitucion,
	$observaciones,
	$justificacion_compra,
	$justificacion_proveedor,
	$id_empleado_solicitud,
	$id_solicitud_compra);
	
iniciotransaccion();
inputmysqlutf8();
$result = mysql_query($sql,$conex);

if ($result) {
	if ($_POST['id_solicitud_compra'] == "") {
		$id_solicitud_compra = mysql_insert_id($conex);
	}
	//recorriendo los detalles para su modificacion:
	$id_articulos = $_POST['id_articulo'];
	$id_detalle_solicitud_compras = $_POST['id_detalle_solicitud_compra'];
	$cantidades = $_POST['cantidad'];
	$precios = $_POST['precio'];
	$fechas_requeridas = $_POST['fecha_requerida'];
	$c = count($id_articulos);
	
	for ($i = 0; $i < $c; $i++) {
		$id_articulo = getmysqlnum($id_articulos[$i]);
		$id_detalle_solicitud_compra = getmysqlnum($id_detalle_solicitud_compras[$i]);
		$cantidad = getmysqlnum($cantidades[$i]);
		$precio_sugerido = getmysqlnum($precios[$i]);
		$fecha_requerida = setmysqlfecha($fechas_requeridas[$i]);
		
		//verificando el tipo inser, update o delete
		if ($id_detalle_solicitud_compras[$i] == '') {	//INSERT
			$sqldetalle = 'INSERT INTO ga_detalle_solicitud_compra (id_solicitud_compra,id_articulo,cantidad,precio_sugerido,fecha_requerida,id_detalle_solicitud_compra) VALUES (%s,%s,%s,%s,%s,%s);';
			$id_detalle_solicitud_compra = 'default';
		} else {
			if ($cantidad != 0) {						//UPDATE
				$sqldetalle = "UPDATE ga_detalle_solicitud_compra SET 
					id_solicitud_compra = %s,
					id_articulo = %s,
					cantidad = %s,
					precio_sugerido = %s,
					fecha_requerida = %s
				WHERE id_detalle_solicitud_compra = %s;";
			} else {									//DELETE
				$sqldetalle = '';
			}
		}
		if ($sqldetalle == '' && $id_articulos[$i] != ''){
			$sqldetalle = 'DELETE FROM ga_detalle_solicitud_compra
			WHERE id_detalle_solicitud_compra = '.$id_detalle_solicitud_compra.';';
		} else {
			$sqldetalle = sprintf($sqldetalle,
				$id_solicitud_compra,
				$id_articulo,
				$cantidad,
				$precio_sugerido,
				$fecha_requerida,
				$id_detalle_solicitud_compra);
		}
		if ($id_articulos[$i] != '') {
			//echo $sqldetalle,'<br />';
			$result2 = mysql_query($sqldetalle,$conex);
			if (!result2) {
				rollback();
				echo mysql_error($conex).'<br><br>Nro: '.mysql_errno($conex).'<br><br>SQL: '.$sqldetalle;
				exit;
			}
		}
	}
} else {
	rollback();
	echo mysql_error($conex).'<br><br>Nro: '.mysql_errno($conex).'<br><br>SQL: '.$sql;
	exit;
}
fintransaccion();
cerrar();

//echo var_dump($_POST); ?>
<script language="javascript" type="text/javascript"> 
	alert("Se ha guardado la solicitud correctamente");
	window.location="ga_solicitud_compras_editar.php?view=1&id=<?php echo $id_solicitud_compra; ?>";
</script>