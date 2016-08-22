<?php
require_once("../connections/conex.php");

// procesando ajax:
cache_expires();//reputacionCliente
//Recargas XML
if (isset($_GET['ajax_getcliente'])) {
	conectar();
	$sql = sprintf("SELECT
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(': ', CONCAT_WS('-', cliente.lci, cliente.ci), CONCAT_WS(' ', cliente.nombre, cliente.apellido)) AS nombre_cliente,
		cliente.telf,
		cliente.direccion,
		cliente.correo,
		cliente.ciudad,
		cliente.otrotelf,
		IF(cliente.tipo = 'Natural', IF(perfil_prospecto.sexo = 'M', 'Masculino', 'Femenino'),'') AS sexo_cliente,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.reputacionCliente,
		cliente.tipo_cuenta_cliente,
		cliente.tipo
	FROM cj_cc_cliente cliente
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
	WHERE cliente.id = %s;",
		valTpDato(getmysqlnum($_GET['ajax_getcliente']), "int"));
	$r = mysql_query($sql,$conex);
	if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row = mysql_fetch_assoc($r);
	
	xmlstart();
	echo "<datos>";
		echo "<texto>";
			tagxml('txtIdCliente',$row['id']);
			tagxml('clientec',str_replace("&","&#038;",$row['nombre_cliente']));
			tagxml('cedula',str_replace("&","&#038;",$row['nombre_cliente']));
		echo "</texto>";
		echo "<capa>";
			/*tagxml('nombre',$row['nombre']);
			tagxml('apellido',$row['apellido']);
			tagxml('thab',$row['telf']);
			tagxml('direccion',$row['direccion']);
			tagxml('email',$row['correo']);
			tagxml('ciudad',$row['ciudad']);
			tagxml('celular',$row['otrotelf']);
			tagxml('sexo',$row['sexo_cliente']);
			//tagxml('toficina',$row[9]);*/
		echo "</capa>";
		echo "<function>";
			tagxml('activa','\'cedula\'');
			tagxml('enfoca','\'modelo\'');
			if($row['r'] == 1) {
				tagxml('reputacion','\'#FF5F5F\',\''.$row['reputacionCliente'].'\',true');
			} else if ($row['r'] == 2) {
				tagxml('reputacion','\'#5AEF59\',\''.$row['reputacionCliente'].'\'');
			} else {
				tagxml('reputacion','\'#FFFFFF\',\'\'');
			}
		echo '</function>';
		tagxml('closelist','listacliente');
	echo '</datos>';
}

if (isset($_GET['ajax_getvehiculo'])) {
	conectar();
	$queryUnidadBasica = sprintf("SELECT *,
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
		(SELECT des_version FROM an_version WHERE an_version.id_version = uni_bas.ver_uni_bas) AS desc_version
	FROM sa_unidad_empresa unidad_emp
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (unidad_emp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_uni_bas uni_bas ON (unidad_emp.id_unidad_basica = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
	WHERE uni_bas.id_uni_bas = %s
		AND unidad_emp.id_empresa = %s
		AND uni_bas.catalogo = 1;",
		valTpDato(getmysqlnum($_GET['ajax_getvehiculo']), "int"),
		valTpDato(getempresa(), "int"));
	$rsUnidadBasica = mysql_query($queryUnidadBasica, $conex);
	if (!$rsUnidadBasica) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
	
	if ($rowUnidadBasica['isan_uni_bas'] == 1) {
		$query = sprintf("SELECT
			iva.iva,
			iva.observacion
		FROM an_unidad_basica_impuesto uni_bas_impuesto
			INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo = 6;",
			valTpDato($rowUnidadBasica['id_unidad_basica'], "int"));
		$rs = mysql_query($query);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$iva = $row['iva'];
		$cond = (strlen($eviva) > 0) ? " e " : " Incluye ";
		$eviva .= $cond.$row['observacion'];
	} else {
		$iva = '0';
		$eviva = "(E)";
	}
	
	$lujo = '0';
	if ($rowUnidadBasica['impuesto_lujo'] == 1) {
		$query = sprintf("SELECT
			iva.iva,
			iva.observacion
		FROM an_unidad_basica_impuesto uni_bas_impuesto
			INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo = 2;",
			valTpDato($rowUnidadBasica['id_unidad_basica'], "int"));
		$rs = mysql_query($query);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$lujo = $row['iva'];
		$cond = (strlen($eviva) > 0) ? " e " : " Incluye ";
		$eviva .= $cond.$row['observacion'];
	}
	
	$listap = "<select id=\"lstPrecioVenta\" name=\"lstPrecioVenta\" onchange=\"actualizarprecio(this.value);\">";
		$listap .= "<option value=\"\">-</option>";
		$listap .= "<option value=\"".$rowUnidadBasica['pvp_venta1']."\">Precio 1: (".numformat($rowUnidadBasica['pvp_venta1']).")</option>";
		$listap .= "<option value=\"".$rowUnidadBasica['pvp_venta2']."\">Precio 2: (".numformat($rowUnidadBasica['pvp_venta2']).")</option>";
		$listap .= "<option value=\"".$rowUnidadBasica['pvp_venta3']."\">Precio 3: (".numformat($rowUnidadBasica['pvp_venta3']).")</option>";
	$listap .= "</select>";
	
	xmlstart();
	echo "<datos>";
		echo "<texto>";
			tagxml('txtIdUnidadBasica',$rowUnidadBasica['id_uni_bas']);
			tagxml('modelo',$rowUnidadBasica['vehiculo']);
			tagxml('modeloc',$rowUnidadBasica['vehiculo'].' '.$eviva);
			tagxml('porcentaje_iva',$iva);
			tagxml('porcentaje_impuesto_lujo',$lujo);
		echo "</texto>";
		echo "<capa>";
			tagxml('eviva',$eviva);
			tagxml('tdlstPrecioVenta','<![CDATA[ '.$listap.' ]]>');
		echo "</capa>";
		echo "<function>";
			tagxml('activa','\'modelo\'');
			tagxml('activa','\'txtPrecioVenta\'');
			tagxml('percent','');
			tagxml('enfoca','\'lstPrecioVenta\'');
			tagxml("xajax_buscarUnidadFisica","xajax.getFormValues('frmPedido')");
		echo "</function>";
		tagxml('closelist','listavehiculo');
	echo '</datos>';
}