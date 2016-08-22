<?php
//cargando los datos para imprimir la orden de compra:
include_once("../connections/conex.php");
include_once("../clases/numerador.inc.php");

@session_start();

$id_orden_compra = getmysqlnum($_GET['id']);

$queryEmp = sprintf("SELECT * FROM sa_v_empresa_sucursal WHERE id_empresa = %s",
	$_SESSION['idEmpresaUsuarioSysGts']);
$rsEmp = mysql_query($queryEmp, $conex) or die(mysql_error()."\n\nLine: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

if ($id_orden_compra == 0) {
	echo "Error 404: no se encuentra la pagina solicitada.";
	exit;
}

$sql_orden_compra = "SELECT *,
	%s AS ffecha,
	%s AS ffecha_cotizacion ,
	%s AS ffecha_entrega
FROM vw_ga_orden_compra
WHERE id_orden_compra = %s;";
conectar();


$rsOrdenCompra = mysql_query(sprintf($sql_orden_compra, mysqlfecha('fecha'), mysqlfecha('fecha_entrega'), mysqlfecha('fecha_cotizacion'), $id_orden_compra), $conex);

if ($rsOrdenCompra) {
	if (mysql_num_rows($rsOrdenCompra)==0) {
		echo "No exite";	
		//.sprintf($sql_orden_compra , mysqlfecha('fecha'), mysqlfecha('fecha_entrega'), mysqlfecha('fecha_cotizacion'), $id_orden_compra);
		exit;
	}
	$rowOrdenCompra = mysql_fetch_assoc($rsOrdenCompra);
	$descuento = $rowOrdenCompra['descuento_orden'];
		
	$sql_empleado_contacto = "SELECT * FROM pg_v_empleado WHERE id_empleado=%s";
	$result_empleado_contacto=mysql_query(sprintf($sql_empleado_contacto,$rowOrdenCompra['id_empleado_contacto']));
	if($result_empleado_contacto){
		$empleado_contacto=mysql_fetch_assoc($result_empleado_contacto);
	}
	$sql_empleado_recepcion="SELECT * FROM pg_v_empleado WHERE id_empleado=%s";
	$result_empleado_recepcion=mysql_query(sprintf($sql_empleado_recepcion,$rowOrdenCompra['id_empleado_recepcion']));
	if($result_empleado_recepcion){
		$empleado_recepcion=mysql_fetch_assoc($result_empleado_recepcion);
	}
		
	//CONSULTANDO LOS DETALLES DE LA ORDEN DE COMPRA
		
	$sql_detalles = "SELECT 
		ga_orden_compra_detalle.id_orden_compra_detalle,
		ga_orden_compra_detalle.id_articulo,
		ga_orden_compra_detalle.cantidad,
		ga_orden_compra_detalle.pendiente,
		ga_orden_compra_detalle.precio_unitario,
		ga_orden_compra_detalle.id_iva,
		ga_orden_compra_detalle.iva,
		ga_orden_compra_detalle.tipo,
		ga_orden_compra_detalle.id_cliente,
		(precio_unitario * cantidad) AS subtotal,
		ga_articulos.codigo_articulo,
		ga_articulos.descripcion,
		ga_tipos_unidad.id_tipo_unidad,
		ga_tipos_unidad.unidad
	FROM ga_orden_compra_detalle
		INNER JOIN ga_articulos ON (ga_orden_compra_detalle.id_articulo = ga_articulos.id_articulo)
		INNER JOIN ga_tipos_unidad ON (ga_articulos.id_tipo_unidad = ga_tipos_unidad.id_tipo_unidad)
	WHERE id_orden_compra = %s;";
			
	$result_detalles = mysql_query(sprintf($sql_detalles,$id_orden_compra),$conex);
	if($result_detalles) {
		while($row_detalles=mysql_fetch_assoc($result_detalles)) {
			$detalles[] = $row_detalles;
			$subtotal += $row_detalles['subtotal'];
		}
	} else {
		echo mysql_error($conex);
		exit;
	}
		
	$sql_iva="SELECT ga_orden_compra_iva.iva as iva, sum(ga_orden_compra_iva.`base_imponible`) AS base, SUM(ga_orden_compra_iva.`subtotal_iva`) as subtotal, observacion
	FROM ga_orden_compra_iva 
		INNER JOIN pg_iva on (pg_iva.idIva = ga_orden_compra_iva.id_iva) 
	WHERE id_orden_compra=%s
	GROUP BY ga_orden_compra_iva.id_iva
	ORDER BY ga_orden_compra_iva.iva;";
	$result_iva=mysql_query(sprintf($sql_iva,$id_orden_compra),$conex);
	if ($result_iva) {
		while($row_iva = mysql_fetch_assoc($result_iva)) {
			$iva[] = $row_iva;
			//$subtotal+=$row_gastos['subtotal'];
			$subtotal_iva+=$row_iva['subtotal'];
		}
	} else {
		echo mysql_error($conex);
		exit;
	}
			//var_dump($iva);
	$sql_gastos="SELECT 
		ga_orden_compra_gasto.porcentaje_monto AS porcentaje_monto,
		ga_orden_compra_gasto.monto AS monto,
		pg_gastos.nombre as nombre_gasto,
		if(pg_gastos.estatus_iva = 1,'*','') AS iva
	FROM ga_orden_compra_gasto
		INNER JOIN pg_gastos ON (ga_orden_compra_gasto.id_gasto = pg_gastos.id_gasto)
	WHERE id_orden_compra = %s;";
	$result_gastos=mysql_query(sprintf($sql_gastos,$id_orden_compra),$conex);
	if ($result_gastos) {
		while($row_gastos=mysql_fetch_assoc($result_gastos)) {
			$gastos[]=$row_gastos;
			//$subtotal+=$row_gastos['subtotal'];
			$subtotal_gastos+=$row_gastos['monto'];
		}
	} else {
		echo mysql_error($conex);
		exit;
	}
	
	//echo $subtotal, ' ' ,$subtotal_iva,' ',$subtotal_gastos;exit;
	$total=($subtotal-$descuento)+$subtotal_iva+$subtotal_gastos;	
} else {
	echo mysql_error($conex);
	exit;
}

$tipo_transporte = array(0 => "PROPIO", 1 => "TERCEROS");
$tipo_pago = array(0 => "CR&Eacute;DITO", 1 => "CONTADO");

if ($_GET['view'] == 'print') {
	$loadscript="print();";
}

if (isset($_GET['min_rows'])) {
	$min_rows=getmysqlnum($_GET['min_rows']);
	if($min_rows!=0) {
		$max_page=$min_rows;
	}
}

if ($max_page > 30 || $max_page <= 0) {
	$max_page = 20;
}
$offset_page=$max_page-count($detalles);
//integrando al vista:
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE 2.0 :. Compras - Orden de Compra</title>
    
    <link rel="stylesheet" type="text/css" href="ga_orden_compra_estilo.css" />
    
    <script type="text/javascript" language="javascript">
	function cargar(){
		<?php echo $loadscript; ?>
	}
    </script>
</head>
<body onload="cargar();">
	<div id="marco_superior">
	<table id="tabla_encabezado" class="printtable" border="2">
		<colgroup class="grupo_encabezado1"></colgroup>
		<colgroup class="grupo_encabezado2"></colgroup>
		<colgroup class="grupo_encabezado3"></colgroup>
		<colgroup class="grupo_encabezado4"></colgroup>
		<tbody>
			<tr>
				<td rowspan="3">
					<img src="../<?php echo $rowEmp['logo_familia'];?>" height="40">
				</td>
				<td rowspan="3">
					<div id="titulo_orden_compra">ORDEN DE COMPRA</div>
				</td>
				<td >
					<span class="texto_encabezado">ORDEN COMPRA N&deg;</span>
				</td>
				<td class="td_dato">
					<span class="dato_encabezado"><?php echo htmlentities($rowOrdenCompra['id_orden_compra']); ?></span>
				</td>
			</tr>
			</tr>
				<td>
					<span class="texto_encabezado">FECHA</span>
				</td>
				<td class="td_dato">
					<span class="dato_encabezado"><?php echo htmlentities($rowOrdenCompra['ffecha']); ?></span>
				</td>
			</tr>
			</tr>
				<td>
					<span class="texto_encabezado">SOLICITUD COMPRA N&deg;</span>
				</td>
				<td class="td_dato">
					<span class="dato_encabezado"><?php echo htmlentities($rowOrdenCompra['id_pedido_compra']); ?></span>
				</td>
			</tr>
		</tbody>
	</table>
	
	<div class="espaciador"></div>
	
	<table id="tabla_proveedor" class="printtable">
		<caption>DATOS DEL PROVEEDOR</caption>
		<tbody>
			<tr>
				<td id="td_proveedor1" >
					<div class="label_proveedor_principal">NOMBRE / RAZ&Oacute;N SOCIAL:</div>
				</td>
				<td colspan="3" class="td_dato" id="td_proveedor2">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['nombre']); ?></div>
				</td>
				</td>
				<td id="td_proveedor5">
					<div class="label_proveedor">RIF:</div>
				</td>
				<td id="td_proveedor6" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['rif_proveedor']); ?></div>
				</td>
			</tr>
			<tr>
				<td id="td_proveedor1">
					<div class="label_proveedor_principal">PERSONA CONTACTO:</div>
				</td>
				<td id="td_proveedor2" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['contacto']); ?></div>
				</td>
				<td id="td_proveedor3">
					<div class="label_proveedor">CARGO:</div>
				</td>
				<td id="td_proveedor4" class="td_dato">
					<div class="td_dato_in">&nbsp;</div>
				</td>
				<td id="td_proveedor5">
					<div class="label_proveedor">EMAIL:</div>
				</td>
				<td id="td_proveedor6" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['correococtacto']); ?></div>
				</td>
			</tr>
			<tr>
				<td id="td_proveedor1">
					<div class="label_proveedor_principal">DIRECCI&Oacute;N FISCAL:</div>
				</td>
				<td colspan="5" id="td_proveedor2" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['direccion_proveedor']); ?></div>
				</td>
			</tr>
			<tr>
				<td id="td_proveedor1">&nbsp;
				</td>
				<td id="td_proveedor2" class="td_dato">
					<div class="td_dato_in">&nbsp;</div>
				</td>
				<td id="td_proveedor3">
					<div class="label_proveedor">TELF:</div>
				</td>
				<td id="td_proveedor4" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['telefono']); ?></div>
				</td>
				<td id="td_proveedor5">
					<div class="label_proveedor">FAX:</div>
				</td>
				<td id="td_proveedor6" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['fax']); ?></div>
				</td>
			</tr>
		</tbody>
	</table>
	
	<div class="espaciador"></div>
	
	<table id="tabla_compra" class="printtable">
		<caption>DATOS DE LA COMPRA</caption>
		<tbody>
			<tr>
				<td id="td_compra1" >
					<div class="label_compra_principal">FACTURA A NOMBRE DE:</div>
				</td>
				<td colspan="3" class="td_dato" id="td_compra2">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['nombre_empresa']); ?></div>
				</td>
				</td>
				<td id="td_compra5">
					<div class="label_compra">RIF:</div>
				</td>
				<td id="td_compra6" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['rif']); ?></div>
				</td>
			</tr>
			<tr>
				<td id="td_compra1">
					<div class="label_compra_principal">PERSONA CONTACTO:</div>
				</td>
				<td id="td_compra2" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($empleado_contacto['nombre_empleado']); ?></div>
				</td>
				<td id="td_compra3">
					<div class="label_compra">CARGO:</div>
				</td>
				<td id="td_compra4" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($empleado_contacto['nombre_cargo']); ?></div>
				</td>
				<td id="td_compra5">
					<div class="label_compra">EMAIL:</div>
				</td>
				<td id="td_compra6" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($empleado_contacto['email']); ?></div>
				</td>
			</tr>
			<tr>
				<td id="td_compra1">
					<div class="label_compra_principal">DIRECCI&Oacute;N DE ENTREGA:</div>
				</td>
				<td colspan="5" id="td_compra2" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['direccion']); ?></div>
				</td>
			</tr>
		</tbody>
	</table>
	<table id="tabla_compra2" class="printtable">
		<tbody>
			<tr>	
				<td id="td_compra1_2">
					<div class="label_compra_principal">RESP. DE LA RECEPCI&Oacute;N:</div>
				</td>
				<td id="td_compra2_2" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($empleado_recepcion['nombre_empleado']); ?></div>
				</td>
				<td id="td_compra3_2">
					<div class="label_compra">FECHA DE ENTREGA:</div>					
				</td>
				<td align="center" id="td_compra4_2" class="td_dato">
					<div class="td_dato_in"><?php echo htmlentities($rowOrdenCompra['ffecha_entrega']); ?></div>
				</td>
				<td id="td_compra5_2">
					<div class="label_compra">TRANSPORTE A CARGO:</div>				
				</td>
				<td id="td_compra6_2" class="td_dato">
					<div class="td_dato_in"><?php echo $tipo_transporte[$rowOrdenCompra['tipo_transporte']]; ?></div>
				</td>
			</tr>
		</tbody>
	</table>
	
	
	<div class="espaciador" id="espaciador_detalles"></div>
	
	<table id="tabla_detalles" class="printtable">
		<colgroup class="td_grupo_numero"></colgroup>
		<colgroup class="td_grupo_cantidad"></colgroup>
		<colgroup class="td_grupo_unidad"></colgroup>
		<colgroup class="td_grupo_codigo"></colgroup>
		<colgroup class="td_grupo_descripcion"></colgroup>
		<colgroup class="td_grupo_precio"></colgroup>
		<colgroup class="td_grupo_subtotal"></colgroup>
		<thead>
			<tr>
				<td>N&deg;</td>
				<td>CANT.</td>
				<td>UNIDAD</td>
				<td>CODIGO</td>
				<td>DESCRIPCION</td>
				<td>PRECIO POR UNIDAD</td>
				<td>SUBTOTAL</td>
			</tr>
		</thead>
		<tbody>
			<?php 
			//$c=0;
			foreach ($detalles as $key => $value):
			?>
			
			<tr>
				<td><?php echo $key+1; ?></td>
				<td><?php echo utf8_encode($value['cantidad']); ?></td>
				<td><?php echo utf8_encode($value['unidad']); ?></td>
				<td><?php echo utf8_encode($value['codigo_articulo']); ?></td>
				<td><?php echo utf8_encode($value['descripcion']); ?></td>
				<td align="right"><?php echo numformat($value['precio_unitario']); ?></td>
				<td align="right"><?php echo numformat($value['subtotal']); ?></td>
			</tr>
			<?php endforeach; ?>
			
			<?php
				for ($i=0;$i<$offset_page;$i++) {
					echo "<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td align=\"center\">-</td>
					</tr>";
				}
 			?>
		</tbody>
	</table>
    
	<?php $total_offset = 3; ?>
	<table id="tabla_totales" class="printtable">
		<colgroup class="grupo_totales0"></colgroup>
		<colgroup class="grupo_totales1"></colgroup>
		<colgroup class="grupo_totales2"></colgroup>
		<colgroup class="grupo_totales3"></colgroup>
		<tbody>
			<tr>
				<td id="tddatostotales" rowspan="<?php echo (count($iva)+$total_offset);?>">
					<table id="nobordertable">
						<tbody>
							<colgroup id="datostotales1" />
							<colgroup id="datostotales2" />
							<colgroup id="datostotales3" />
							<colgroup id="datostotales4" />
							<tr>
								<td nowrap="nowrap">SEG&Uacute;N COTIZACI&Oacute;N N&deg;:</td>
								<td class="td_underline"><?php echo htmlentities($rowOrdenCompra['segun_cotizacion']); ?></td>
								<td nowrap="nowrap">DE FECHA:</td>
								<td class="td_underline"><?php echo htmlentities($rowOrdenCompra['ffecha_cotizacion']); ?></td>
							</tr>
							<tr>
								<td nowrap="nowrap">TIPO DE PAGO:</td>
								<td colspan="3" class="td_underline"><?php echo $tipo_pago[$rowOrdenCompra['tipo_pago']]; ?></td>
							</tr>
							<tr>
								<td nowrap="nowrap">CONDICI&Oacute;N DE PAGO:</td>
								<td colspan="3" class="td_underline"><?php echo htmlentities($rowOrdenCompra['condiciones_pago']); ?></td>
							</tr>
							<tr>
								<td>SON:</td>
								<td colspan="3" class="td_underline"><?php echo htmlentities(strtoupper(getMoneyNum($total))); ?></td>
							</tr>
						</tbody>					
					</table>
					
				</td>
				<td id="tdgastos" rowspan="<?php echo (count($iva)+$total_offset);?>">				
                    <table class="printtable">
                        <caption>Gastos</caption>
                        <colgroup class="grupo_totales2"></colgroup>
                        <colgroup class="grupo_totales3"></colgroup>
                        <tbody >
                            <?php //var_dump($gastos);
							 foreach($gastos as $key => $value): ?>
                            <tr>
                                <td><?php echo htmlentities($value['nombre_gasto']).$value['iva']; ?></td>
                                <td align="right"><?php echo '('.numformat($value['porcentaje_monto']).'%) '.numformat($value['monto']); ?></td>
                            </tr>
                            <?php endforeach;?>
                            <tr>
                                <td>Sub-total Gastos</td>
                                <td align="right" class="tdtotal"><?php echo /*"951s"*/numformat($subtotal_gastos); ?></td>
                            </tr>
                            <tr>
                                <td colspan="2">* Incluye Iva</td>
                            </tr>
                        </tbody>				
                    </table>
				</td>
				<td class="tdtotal">Sub-total Bs:.</td>
				<td align="right" class="tdtotal"><?php echo numformat($subtotal); ?></td>
			</tr>
			<tr>
				<td>Descuento Bs.</td>
				<td align="right" class="tdtotal"><?php echo numformat($descuento); ?></td>
			</tr>
            	
			<?php 
			if ($iva != null)
					foreach($iva as $key=>$value): ?>
			<tr>
				
				<td><?php echo utf8_encode($value['observacion']).' '.numformat($value['iva']).'%'; ?></td>
				<td align="right"><?php echo numformat($value['subtotal']); ?></td>
			</tr>
			<?php	endforeach; ?>
			<tr>
				<td class="tdtotal">TOTAL Bs.</td>
				<td align="right" class="tdtotal"><?php echo numformat($total); ?></td>
			</tr>
			<tr>
				<td colspan="4">
				Observaciones: <span id="observaciones"><?php echo $rowOrdenCompra['observaciones']; ?></span>
				</td>
			</tr>
		</tbody>
	</table>
	
	<div class="espaciador"></div>
	<table id="tabla_pie" class="printtable">
		<caption>APROBACI&Oacute;N</caption>
		<tbody>
			<tr>
				<td id="preparado">
					<span>PREPARADO POR<br />
					NOMBRE Y FIRMA:<br />
					<br />&nbsp;
					<br />&nbsp;
					<br />
					FECHA:
					</span>
				
				</td>
				<td id="aprobado">
					<span>PREPARADO POR<br />
					NOMBRE Y FIRMA:<br />
					<br />&nbsp;
					<br />&nbsp;
					<br />
					FECHA:
					</span></td>
			</tr>
			<tr>
				<td colspan="2">
					<span id="titulo_condiciones">CONDICIONES DE COMPRA, PRECIO, CALIDAD Y OPORTUNIDADES DE ENTREGA </span>
<br />
				1.- Despachar el pedido con nota de entrega y enviar la factura a la direcci&oacute;n que aparece al pie de p&aacute;gina del presente documento.
				<br />
				2.- Las condiciones de compra que aparecen en el presente documento, tales como precio, especificaciones de calidad, plazos de entrega, lugar de despacho, etc., no son modificables por el proveedor, en caso de prever  alg&uacute;n incumplimiento, o de requerirse alguna modificaci&oacute;n, el proveedor notificar&aacute; a la empresa de manera oportuna cualquier ajuste necesario antes de la fecha de entrega prevista a fin de autorizar su cambio.
				<br />
				3.- Los bienes sujetos a la presente deden ser de la calidad reconocida y regida por aquellas normas que por antelaci&oacute;n se hayan aceptado por las partes.
				<br />
				4.- Los bienes para ser cancelado deben ser verificados y aprobados por el responsable de la recepci&oacute;n seg&uacute;n los requisitos de calidad acordada.
				<br />
				5.- Las devoluciones que surjan por modificaciones realizadas por el proveedor de la presente Orden de Compra, sin previa autorizaci&oacute;n del cliente, ya sea por el incumplimiento de los precios acordados, por rechazos de calidad o por materiales que no cumplan a las condiciones acordadas, ser&aacute;n buscadas por el proveedor en las instalaciones a donde fueron despachadas, asumiendo el proveedor el costo total del transporte.
				</td>
			</tr>
		</tbody>
	</table>
	<table style="width:100%">
		<tbody>
			<tr>
				<td colspan="3" align="center">&nbsp;
					
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					Direcci&oacute;n Fiscal: Av. Andr&eacute;s Galarraga C/C El Sam&aacute;n Edif. Yokomuro Caracas, Piso PB, Urb. Chacao.
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					Telf. 208-1300 / Fax: 208-1398
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">&nbsp;
					
				</td>
			</tr>
			<tr>
				<td style="text-align:left; width:30%">
					N&deg; Actualizaci&oacute;n: 00
				</td>
				<td style="text-align:center; width:*;">
					Original: Proveedor / Duplicado: Gerencia de Administraci&oacute;n
				</td>
				<td style="text-align:right;width:30%;">
					COM-PR-1.2-F02/ Fecha <?php echo date('m-Y'); ?>
				</td>
			</tr>
		</tbody>
	</table>
	</div>
</body>
</html>