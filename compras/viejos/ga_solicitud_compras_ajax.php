<?php
require_once "../connections/conex.php";
//require_once "../forms.inc.php";
require_once("../control/main_control.inc.php");
// procesando ajax:
cache_expires();//reputacionCliente
//Recargas XML

if (isset($_GET['ajax_getcodigoexpress'])) {
	xmlstart();
	$codigo_articulo = excape($_GET['ajax_getcodigoexpress']);
	$tipoCompra = getmysqlnum($_GET['tipo_seccion']);
	$objeto = excape($_GET['objeto']);
	
	echo '<datos>';
	conectar();
	$sql = sprintf("SELECT id_articulo FROM vw_ga_articulos
	WHERE (codigo_articulo = '%s'
			OR codigo_articulo_prov = '%s');",
		$codigo_articulo,
		$codigo_articulo);
	$r = mysql_query($sql,$conex);
	if ($r) {
		$row=mysql_fetch_assoc($r);
		echo '<texto>';
		echo '</texto><capa>';
			//tagxml('codigo_unidad_centro_costo',$row['codigo_unidad_centro_costo']);		
		echo '</capa>';
		echo '<function>';
		if($row['id_articulo'] != ""){
			tagxml('cargarxml',"'articulo',".$row['id_articulo'].",'".$objeto."'");
		}
		//tagxml('alert',"'".$sql."'");
		echo '</function>';
		//tagxml('closelist','ajaxlist');
	}
	echo '</datos>';
}

if (isset($_GET['ajax_getempresa'])) {
	xmlstart();
	$id_empresa = getmysqlnum($_GET['ajax_getempresa']);
	echo '<datos>';
	conectar();
	$sql = "SELECT nombre_empresa, codigo_empresa FROM pg_empresa WHERE id_empresa = ".$id_empresa.";";
	//echo $sql;
	$r = mysql_query($sql,$conex);
	if ($r) {
		$row = mysql_fetch_assoc($r);
		echo '<texto>';
		echo '</texto><capa>';
		tagxml('codigo_empresa',$row['codigo_empresa']);
		tagxml('numero_solicitud',$row['codigo_empresa']);
		
		// BUSCANDO LOS DEPARTAMENTOS
		$departamentos = getMysqlAssoc(sprintf("SELECT dep.id_departamento, dep.nombre_departamento FROM pg_departamento dep WHERE dep.id_empresa = %s;", $id_empresa), $conex);
		$select = inputSelect("id_departamento", $departamentos, $id_departamento, array('onchange'=>'cargar_departamento(this);','class'=>'noprint','style'=>'width: 100%'));
		tagxml('capa_departamento','<![CDATA[ '.$select.' ]]>');
		tagxml('capa_unidad_centro_costo','');
		tagxml('codigo_departamento','');
		tagxml('codigo_unidad_centro_costo','');
	
		echo '</capa>';
		
		//echo '<function>';
		//echo '</function>';
		//tagxml('closelist','ajaxlist');
	}
	echo '</datos>';
}
	
if (isset($_GET['ajax_getdepartamento'])) {
	xmlstart();
	$id_departamento = getmysqlnum($_GET['ajax_getdepartamento']);
	echo '<datos>';
	conectar();
	$sql = "SELECT codigo_departamento from pg_departamento where id_departamento = ".$id_departamento.";";
	//echo $sql;
	$r = mysql_query($sql,$conex);
	if ($r) {
		$row=mysql_fetch_assoc($r);
		echo '<texto>';
		echo '</texto><capa>';
		tagxml('codigo_departamento',$row['codigo_departamento']);
		
		//bnuscando los centros de copsto:
		$centros=getMysqlAssoc("SELECT id_unidad_centro_costo, nombre_unidad_centro_costo FROM pg_unidad_centro_costo
		WHERE id_departamento = ".$id_departamento.";",$conex);
		$select = inputSelect("id_unidad_centro_costo", $centros, $id_unidad_centro_costo, array('onchange'=>'cargar_unidad_centro_costo(this);','class'=>'noprint','style'=>'width: 100%'));
		tagxml('capa_unidad_centro_costo','<![CDATA[ '.$select.' ]]>');
		tagxml('codigo_unidad_centro_costo','');
		
		echo '</capa>';			
		//echo '<function>';
		//echo '</function>';
		//tagxml('closelist','ajaxlist');
	}
	echo '</datos>';
}
	
if (isset($_GET['ajax_getunidad_centro_costo'])) {
	xmlstart();
	$id_unidad_centro_costo = getmysqlnum($_GET['ajax_getunidad_centro_costo']);
	echo '<datos>';
	conectar();
	$sql = "select codigo_unidad_centro_costo from pg_unidad_centro_costo where id_unidad_centro_costo = ".$id_unidad_centro_costo.";";
	//echo $sql;
	$r = mysql_query($sql,$conex);
	if ($r) {
		$row = mysql_fetch_assoc($r);
		echo '<texto>';
		echo '</texto><capa>';
		tagxml('codigo_unidad_centro_costo',$row['codigo_unidad_centro_costo']);		
		echo '</capa>';			
		//echo '<function>';
		//echo '</function>';
		//tagxml('closelist','ajaxlist');
	}
	echo '</datos>';
}

if (isset($_POST['id_articulo'])) {
	xmlstart();
	echo '<datos>';
	
	//identificando el objeto:
	/*$objeto=excape($_GET["objeto"]);
	$id=substr($objeto,6);*/
	
	$articulos = $_POST['id_articulo'];
	$id_proveedor = getmysqlnum($_POST['id_proveedor']);
	$cantidades = $_POST['cantidad'];
	$art = excape(implode(',',$articulos));
	
	conectar();
	//$sql="select id_articulo,precio from ga_articulos_costos where id_articulo in (".$art.") and id_proveedor=".$id_proveedor." order by fecha DESC, id_articulo;";
	//echo $sql;
	$sql = "SELECT superior.id_articulo,(select precio from ga_articulos_costos AS inf
	WHERE inf.id_articulo = superior.id_articulo
		AND superior.id_proveedor = ".$id_proveedor."
		AND inf.fecha = max(superior.fecha)) AS precio FROM ga_articulos_costos AS superior
			WHERE superior.id_articulo IN (".$art.")
				AND id_proveedor = ".$id_proveedor."
			GROUP BY superior.id_articulo";
	error_reporting(0);
	$r = mysql_query($sql,$conex);
	if ($r) {
		$total = 0;
		while ($row = mysql_fetch_assoc($r)) {
			//tagxml('alert',"'articulo".$row['id_articulo']."-precio:".$row['precio']."'");
			if($precios[$row['id_articulo']] == 0){//solo toma el último precio
				$precios[$row['id_articulo']] = $row['precio'];
			}
		}
		//$row=mysql_fetch_assoc($r);
		echo '<texto>';
		//tagxml('codigo'.$id,$row['codigo_articulo']);
		//tagxml('codigo'.$id.'oculto',$row['codigo_articulo']);
		//tagxml('id_articulo'.$id,$row['id_articulo']);
		//tagxml('clientec',$row[1]);
		//tagxml('cedula',$row[1]);
		echo '</texto><capa>';
		//tagxml('edescripcion'.$id,$row['descripcion']);
		//tagxml('nombre',$row[2]);
		//tagxml('apellido',$row[3]);
		//tagxml('thab',$row[4]);
		//tagxml('direccion',$row[5]);
		//tagxml('email',$row[6]);
		//tagxml('ciudad',$row[7]);
		//tagxml('celular',$row[8]);
		//tagxml('sexo',$row[9]);
		//tagxml('toficina',$row[9]);
		/*$c=count($articulos);
		for($i=0;$i<$c;$i++){
			tagxml('eprecio'.($i+1),$precios[$articulos[$i]]);
		}*/
		echo '</capa><function>';
		//tagxml('activa',"'codigo".$id."'");
		//tagxml('enfoca','\'modelo\'');
		/*if($row['r']==1){
			tagxml('reputacion','\'#FF5F5F\',\''.$row['reputacionCliente'].'\',true');
		}else if ($row['r']==2){
			tagxml('reputacion','\'#5AEF59\',\''.$row['reputacionCliente'].'\'');
		}else{
			tagxml('reputacion','\'#FFFFFF\',\'\'');
		}*/
		
		$c = count($articulos);
		for ($i = 0; $i < $c; $i++) {
			$p = $precios[$articulos[$i]];
			tagxml('comparaprecio',"'precio".($i+1)."','".$p."'");
			$total += $p * getmysqlnum($cantidades[$i]);
		}
		tagxml('comparaprecio',"'epreciototal','".$total."'");
		
		echo '</function>';
		//tagxml('closelist','ajaxlist');
	} else {
		
		echo '<function>';
		$c = count($articulos);
		for ($i = 0; $i < $c; $i++) {
			$p = 0;
			tagxml('comparaprecio',"'precio".($i+1)."','".$p."'");
			//$total+=$p*getmysqlnum($cantidades[$i]);
		}
		tagxml('comparaprecio',"'epreciototal','0'");
		//echo '<error>'.$sql.'</error>';
		echo '</function>';
	}
	echo '</datos>';
}

if (isset($_GET['ajax_getarticulo'])) {
	xmlstart();
	echo '<datos>';
	
	//identificando el objeto:
	$objeto=excape($_GET["objeto"]);
	$id=substr($objeto,6);
	$id_articulo=getmysqlnum($_GET['ajax_getarticulo']);
	$id_proveedor=getmysqlnum($_GET['id_proveedor']);
	
	conectar();
	$sql = "SELECT id_articulo, codigo_articulo, marca, codigo_articulo_prov, descripcion, foto, unidad FROM vw_ga_articulos
	WHERE id_articulo = ".$id_articulo.";";
	
	//echo $sql;
	$r = mysql_query($sql,$conex);
	if ($r) {
		$row = mysql_fetch_assoc($r);
		
		//buscando el ultimo precio:
		$precio = getmysql("SELECT precio from ga_articulos_costos
		WHERE id_proveedor = ".$id_proveedor."
			AND id_articulo = ".$id_articulo."
		ORDER BY fecha DESC
		LIMIT 1;");
		
		$fecha = date('d-m-Y',dateAddLab(mktime(),8,true));
		
		echo '<texto>';
		tagxml('codigo'.$id,$row['codigo_articulo_prov']);
		tagxml('codigo'.$id.'oculto',$row['codigo_articulo_prov']);
		tagxml('id_articulo'.$id,$row['id_articulo']);
		tagxml('descripcion'.$id,$row['descripcion']);
		tagxml('precio'.$id,numformat($precio));
		tagxml('fecha_requerida'.$id,$fecha);
		//tagxml('clientec',$row[1]);
		//tagxml('cedula',$row[1]);
		echo '</texto><capa>';
		tagxml('eunidad'.$id,$row['unidad']);
		//tagxml('nombre',$row[2]);
		//tagxml('apellido',$row[3]);
		//tagxml('thab',$row[4]);
		//tagxml('direccion',$row[5]);
		//tagxml('email',$row[6]);
		//tagxml('ciudad',$row[7]);
		//tagxml('celular',$row[8]);
		//tagxml('sexo',$row[9]);
		//tagxml('toficina',$row[9]);
		echo '</capa><function>';
		tagxml('activa',"'codigo".$id."'");
		tagxml('activa',"'precio".$id."'");
		tagxml('habilita',"'elimina".$id."'");
		//tagxml('enfoca','\'modelo\'');
		/*if($row['r']==1){
			tagxml('reputacion','\'#FF5F5F\',\''.$row['reputacionCliente'].'\',true');
		}else if ($row['r']==2){
			tagxml('reputacion','\'#5AEF59\',\''.$row['reputacionCliente'].'\'');
		}else{
			tagxml('reputacion','\'#FFFFFF\',\'\'');
		}*/
		tagxml('agregar',"");
		//tagxml('cargarprecios',"'".$row['id_articulo']."'");
		echo '</function>';
		tagxml('closelist','ajaxlist');
	}
	echo '</datos>';
}
	
//TEXTO PLANO
if (isset($_GET['ajax_codigo'])) {
	$cadena = trim(excape($_GET['ajax_codigo']));
	$cadena = str_replace("#","",$cadena);
	$cadena = str_replace("--","",$cadena);
	$id_proveedor = getmysqlnum($_GET['id_proveedor']);
	$tipoCompra = getmysqlnum($_GET['tipo_compra']);
	
	if ($cadena != "") {
		conectar();
		
		$sql = sprintf("SELECT
			id_articulo,
			codigo_articulo,
			marca,
			codigo_articulo_prov,
			descripcion,
			tipo_articulo,
			foto
		FROM vw_ga_articulos
		WHERE (id_articulo LIKE %s
				OR codigo_articulo LIKE %s
				OR descripcion LIKE %s
				OR codigo_articulo_prov LIKE %s)
			AND estatus_articulo = 1;",
			"'%".$cadena."%'",
			"'%".$cadena."%'",
			"'%".$cadena."%'",
			"'%".$cadena."%'");
		$r = mysql_query($sql,$conex);
		if (!$r) die (mysql_error()."<br><br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($r);
		
		echo "<table border=\"0\" style=\"border-collapse:collapse;\" width=\"100%\">".
		"<tr class=\"trResaltar3\">".
			"<td align=\"right\" class=\"textoNegrita_10px\" width=\"100%\">Mostrando ".$totalRows." de ".$totalRows." Registros&nbsp;</td>".
			"<td><a href=\"javascript:cancelarcodigo('".$_GET['cancelar']."');\"><img border=\"0\" src=\"../img/iconos/eliminarPago.png\" alt=\"Cerrar\" /></a></td>".
		"</tr>".
		"</table>";
		
		echo '<div id="overclientes" class="overflowlist">';
			echo "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
		if ($totalRows > 0) {
			while ($row = mysql_fetch_assoc($r)) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila ++;
				
				echo "<tr class=\"".$clase."\" onclick=\"javascript:cargarxml('articulo',".$row['id_articulo'].",'".$_GET['cancelar']."');\" style=\"cursor:pointer\" height=\"24\">";
					echo "<td>"."<img border=\"0\" src=\"../img/iconos/ico_aceptar.gif\" alt=\"Seleccionar\"/>"."</td>";
					echo "<td width=\"16%\">".utf8_encode($row['codigo_articulo'])."</td>";
					echo "<td width=\"64%\">".utf8_encode($row['descripcion'])."</td>";
					echo "<td width=\"20%\">".utf8_encode($row['tipo_articulo'])."</td>";
				echo "</tr>";
			}
		} else {
			echo "<tr>";
				echo "<td>";
					echo "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
					echo "<tr>";
						echo "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
						echo "<td align=\"center\">No se encontraron registros</td>";
					echo "</tr>";
					echo "</table>";
				echo "</td>";
			echo "</tr>";
		}
			echo "</table>";
		echo "</div>";
		
		cerrar();
	}
	exit;
}
?>