<?php
/*
FALTA POR HACER EL CALCULO DEL GASTO Y EL TOTAL DE LA FACTURA CON EL CALCULO DEL IVA 
*/

include("../inc_sesion.php");
//cargando los datos para imprimir la orden de compra:
include_once("../connections/conex.php");

include("html2pdf/html2pdf.class.php");

//CONSULTA PARA TOMAR LA EMPRESA DE LA SESSION
function consultarEmp(){

		$sqlEmpre = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s", $_SESSION['idEmpresaUsuarioSysGts']);
		$queryEmp = mysql_query($sqlEmpre);
		$rowsEmp = mysql_fetch_assoc($queryEmp);
		
			$img = "<img src=\"../".$rowsEmp['logo_empresa']."\" height=\"40\"/>";
		return $img;

}
function style($view){
	switch($view){
		case 1:
			$style = "font-size:12px;";
				break;
		case 2:
			$style = "font-size:10px;";
				break;
	}
		return $style;	
}

//CABESERA DE LA ORDEN DE COMPRA
function orderCompraHead($idOrdenCompra){
	
		$sqlOrdCompa = sprintf("SELECT * FROM  vw_ga_historico_ordenes WHERE id_orden_compra = %s",$idOrdenCompra );
		$queryOrdCompa = mysql_query($sqlOrdCompa);
		if (!$queryOrdCompa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowsOrdCompa = mysql_fetch_array($queryOrdCompa);

			$tabla = "<table width=\"190px\" border=\"0\" align=\"right\">";
				$tabla .= "<tr>";
					$tabla .= "<td align='right'><strong>Orden Compra N:</strong></td>";
					$tabla .= "<td width=\"\">".$rowsOrdCompa['id_orden_compra']."</td>";
				$tabla .= "</tr>";
				$tabla .= "<tr>";
					$tabla .= "<td align='right'><strong>fecha:</strong></td>";
					$tabla .= "<td>".$rowsOrdCompa['fecha']."</td>";
				$tabla .= "</tr>";
				$tabla .= "<tr>";
					$tabla .= "<td align='right'><strong>Solicitud Compra N:</strong></td>";
					$tabla .= "<td>".$rowsOrdCompa['numero_solicitud']."</td>";
				$tabla .= "</tr>";
			$tabla .= "</table>";
			
		return $tabla;
}

function ordenCompraBody($idOrdenCompra){
	
		$sqlOrdCompa = sprintf("SELECT id_orden_compra,fecha_entrega,fecha_cotizacion,tipo_transporte,tipo_pago,subtotal,monto_letras,condiciones_pago, ga_orden_compra.observaciones,
					   ga_orden_compra.id_solicitud_compra, numero_solicitud, fecha_solicitud,
							 ga_orden_compra.id_empresa, nombre_empresa, pg_empresa.rif AS rif_empresa, pg_empresa.direccion AS direccion_empresa,
							 ga_orden_compra.id_proveedor, cp_proveedor.nombre AS nombre_proveedor, cp_proveedor.rif AS rif_proveedor,
							 cp_proveedor.correo AS correo_proveedor, contacto AS persona_contacto, cp_proveedor.telefono AS telf_proveedor,
							 CONCAT_WS(' ',cp_proveedor.telfcontacto, otrotelf) AS telf_contacto_proveedor,
							 cp_proveedor.direccion AS direccion_proveedor, cp_proveedor.fax AS fax_proveedor,
							 id_empleado_contacto,CONCAT_WS(' ',contacto.nombre_empleado, contacto.apellido) AS nombre_contacto, contacto.email AS email_contacto,
							 contacto.id_cargo_departamento,pg_cargo_departamento.id_cargo, nombre_cargo AS nombre_cargo_contacto,
							 id_empleado_recepcion,CONCAT_WS(' ',contacto.nombre_empleado, contacto.apellido) AS nombre_recepcion
				FROM ga_orden_compra
					 LEFT JOIN ga_solicitud_compra ON ga_solicitud_compra.id_solicitud_compra = ga_orden_compra.id_solicitud_compra
						 LEFT JOIN pg_empresa ON pg_empresa.id_empresa = ga_orden_compra.id_empresa
						 LEFT JOIN cp_proveedor ON cp_proveedor.id_proveedor = ga_orden_compra.id_proveedor
						 LEFT JOIN pg_empleado contacto ON contacto.id_empleado = ga_orden_compra.id_empleado_contacto
						 LEFT JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = contacto.id_cargo_departamento
						 LEFT JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
						 LEFT JOIN pg_empleado recepcion ON recepcion.id_empleado = ga_orden_compra.id_empleado_recepcion
						WHERE id_orden_compra = %s",$idOrdenCompra );
		$queryOrdCompa = mysql_query($sqlOrdCompa);
//$objResponse->alert($sqlOrdCompa);
		if (!$queryOrdCompa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowsOrdCompa = mysql_fetch_array($queryOrdCompa);

			$tabla = "<table width=\"90%\" border=\"0\" align=\"center\">";
				$tabla .= "<tr>";
					$tabla .= "<td colspan=\"6\" class=\"tituloArea\" style=\"text-align:center\">Datso de proveedor</td>";
				$tabla .= "</tr>";
				$tabla .= "<tr>";
					$tabla .= "<td align=\"right\"><strong>Nombre / Razon social:</strong></td>";
					$tabla .= "<td>".utf8_encode($rowsOrdCompa['nombre_proveedor'])."</td>";
					$tabla .= "<td colspan=\"3\"align=\"right\"><strong>Rif:</strong></td>";
					$tabla .= "<td>".$rowsOrdCompa['rif_proveedor']."</td>";
				$tabla .= "</tr>";
				$tabla .= "<tr>";
					$tabla .= "<td align=\"right\"><strong>Persona de contacto:</strong></td>";
					$tabla .= "<td>".utf8_encode($rowsOrdCompa['persona_contacto'])."</td>";
					$tabla .= "<td align=\"right\"><strong>Email:</strong></td>";
					$tabla .= "<td colspan=\"3\">".$rowsOrdCompa['correo_proveedor']."</td>";
				$tabla .= "</tr>";
				$tabla .= "<tr>";
					$tabla .= "<td align=\"right\"><strong>Direccion</strong></td>";
					$tabla .= "<td colspan=\"6\">".utf8_encode($rowsOrdCompa['direccion_proveedor'])."</td>";
				$tabla .= "</tr>";
				$tabla .= "<tr>";					
					$tabla .= "<td align=\"right\"><strong>Telf:</strong></td>";
					$tabla .= "<td>".$rowsOrdCompa['telf_proveedor']."</td>";
					$tabla .= "<td align=\"right\"><strong>Telf Contacto:</strong></td>";
					$tabla .= "<td>".$rowsOrdCompa['telf_contacto_proveedor']."</td>";
					$tabla .= "<td align=\"right\"><strong>Fax:</strong></td>";
					$tabla .= "<td colspan=\"2\">".$rowsOrdCompa['fax_proveedor']."</td>";
				$tabla .= "</tr>";
			$tabla .= "</table>";
			
			$br .= "<br />";
			
			$tablaDtaCmp = "<table width=\"90%\" border=\"0\" align=\"center\">";
				$tablaDtaCmp .= "<tr>";
					$tablaDtaCmp .= "<td colspan=\"8\" class=\"tituloArea\" style=\"text-align:center\">Datso de la Compra</td>";
				$tablaDtaCmp .= "</tr>";
				$tablaDtaCmp .= "<tr>";
					$tablaDtaCmp .= "<td align=\"right\"><strong>Factura a nombre de:</strong></td>";
					$tablaDtaCmp .= "<td>".utf8_encode($rowsOrdCompa['nombre_empresa'])."</td>";
					$tablaDtaCmp .= "<td colspan=\"4\" align=\"right\"><strong>Rif:</strong></td>";
					$tablaDtaCmp .= "<td>".$rowsOrdCompa['rif_empresa']."</td>";
				$tablaDtaCmp .= "</tr>";
				$tablaDtaCmp .= "<tr>";
					$tablaDtaCmp .= "<td align=\"right\"><strong>Persona Contacto:</strong></td>";
					$tablaDtaCmp .= "<td>".utf8_encode($rowsOrdCompa['nombre_contacto'])."</td>";
					$tablaDtaCmp .= "<td align=\"right\"><strong>Cargo:</strong></td>";
					$tablaDtaCmp .= "<td>".utf8_encode($rowsOrdCompa['nombre_cargo_contacto'])."</td>";
					$tablaDtaCmp .= "<td align=\"right\"><strong>Email:</strong></td>";
					$tablaDtaCmp .= "<td colspan=\"2\">".$rowsOrdCompa['email_contacto']."</td>";
				$tablaDtaCmp .= "</tr>";
				$tablaDtaCmp .= "<tr>";
					$tablaDtaCmp .= "<td align=\"right\"><strong>Direccion de Entrega:</strong></td>";
					$tablaDtaCmp .= "<td colspan=\"6\">".utf8_encode($rowsOrdCompa['direccion_empresa'])."</td>";
				$tablaDtaCmp .= "</tr>";
				$tablaDtaCmp .= "<tr>";
					$tablaDtaCmp .= "<td align=\"right\"><strong>Resp. De la Recepcion:</strong></td>";
					$tablaDtaCmp .= "<td>".utf8_encode($rowsOrdCompa['nombre_recepcion'])."</td>";
					$tablaDtaCmp .= "<td align=\"right\"><strong>Fecha de Entrega:</strong></td>";
					$tablaDtaCmp .= "<td>".date('d - m - Y',strtotime($rowsOrdCompa['fecha_entrega']))."</td>";
					$tablaDtaCmp .= "<td colspan=\"2\" align=\"right\"><strong>Transporte a cargo de:</strong></td>";
						switch($rowsOrdCompa['tipo_transporte']){
							case 1:
								$transporte = "Propio";
									break;
							case 2:
								$transporte = "Terceros";
									break; 	
						}
					$tablaDtaCmp .= "<td>".$transporte."</td>";
				$tablaDtaCmp .= "</tr>";
			$tablaDtaCmp .= "</table>";
			
	$sqlOrdCompDetalles = sprintf("SELECT
						ga_orden_compra_detalle.id_orden_compra_detalle,ga_orden_compra_detalle.id_orden_compra,ga_orden_compra_detalle.id_articulo,descripcion,
						codigo_articulo,
						ga_orden_compra_detalle.cantidad,ga_orden_compra_detalle.pendiente,ga_orden_compra_detalle.precio_unitario,ga_orden_compra_detalle.id_iva,	
						ga_orden_compra_detalle.iva,ga_orden_compra_detalle.tipo,ga_orden_compra_detalle.id_cliente,(precio_unitario * cantidad) AS subtotal,
						ga_articulos.codigo_articulo,ga_articulos.descripcion,ga_tipos_unidad.id_tipo_unidad,ga_tipos_unidad.unidad
					FROM ga_orden_compra_detalle
						INNER JOIN ga_articulos ON (ga_orden_compra_detalle.id_articulo = ga_articulos.id_articulo)
						INNER JOIN ga_tipos_unidad ON (ga_articulos.id_tipo_unidad = ga_tipos_unidad.id_tipo_unidad)
					WHERE id_orden_compra = %s",$idOrdenCompra);
					
	$queryOrdCompDetalles = mysql_query($sqlOrdCompDetalles);
	//$objResponse->alert($sqlOrdCompa);
	if (!$queryOrdCompDetalles) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$numRows = mysql_num_rows($queryOrdCompDetalles);
		
				
			$tablaContCmp = "<table width=\"90%\" border=\"1\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">";	
				$tablaContCmp .= "<tr>";
					$tablaContCmp .= "<td align=\"center\"><strong>N&deg;</strong></td>";
					$tablaContCmp .= "<td align=\"center\"><strong>CANTIDAD</strong></td>";
					$tablaContCmp .= "<td align=\"center\"><strong>UNIDAD</strong></td>";
					$tablaContCmp .= "<td align=\"center\"><strong>CODIGO</strong></td>";
					$tablaContCmp .= "<td colspan=\"4\" align=\"center\"><strong>DESCRIPCION</strong></td>";
					$tablaContCmp .= "<td align=\"center\"><strong>PRECIO POR UNIDAD</strong></td>";
					$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\"><strong>SUBTOTAL</strong></td>";
			$tablaContCmp .= "</tr>";
			$num = 0;
	while($rowsOrdCompDetalles = mysql_fetch_array($queryOrdCompDetalles)){
			$num++;
			$detallesSubTotal[] = $rowsOrdCompDetalles['subtotal']; 
			$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td align=\"center\">".$num."</td>";
						$tablaContCmp .= "<td>".$rowsOrdCompDetalles['cantidad']."</td>";
						$tablaContCmp .= "<td align=\"center\">".$rowsOrdCompDetalles['unidad']."</td>";
						$tablaContCmp .= "<td>".$rowsOrdCompDetalles['codigo_articulo']."</td>";
						$tablaContCmp .= "<td colspan=\"4\" align=\"center\">".utf8_encode($rowsOrdCompDetalles['descripcion'])."</td>";
						$tablaContCmp .= "<td align=\"center\">".$rowsOrdCompDetalles['precio_unitario']."</td>";
						$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\">".$rowsOrdCompDetalles['subtotal']."</td>";
			$tablaContCmp .= "</tr>";
	}
		$numTd = ($numRows + 1);
	for($td = $numTd; $td <= 20; $td++){
			$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td align=\"center\">".$numTd++."</td>";
						$tablaContCmp .= "<td></td>";
						$tablaContCmp .= "<td align=\"center\"></td>";
						$tablaContCmp .= "<td></td>";
						$tablaContCmp .= "<td colspan=\"4\" align=\"center\"></td>";
						
						$tablaContCmp .= "<td align=\"center\"></td>";
						$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\">-</td>";
			$tablaContCmp .= "</tr>";
	}				
			$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td colspan=\"2\" align=\"left\">Segunda Cotizacion N&deg;: &nbsp;&nbsp;</td>";
						$tablaContCmp .= "<td colspan=\"4\">De Fecha: ".$rowsOrdCompa['fecha_cotizacion']."</td>";
						$tablaContCmp .= "<td align=\"center\" bgcolor=\"#A0A0A4\" colspan=\"2\"><strong>Gasto</strong></td>";
						$tablaContCmp .= "<td align=\"right\"><strong>Sbu_total Bs:</strong></td>";
						$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\">".$rowsOrdCompa['subtotal']."</td>";
			$tablaContCmp .= "</tr>";
				switch($rowsOrdCompa['tipo_pago']){
					case 0:
						$tipoPago = "Crédito"; 
							break;
					case 1:
						$tipoPago = "Contado";
							break;
					} 
			
					
			$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td colspan=\"2\" align=\"left\">Tipo de Pago:</td>";
						$tablaContCmp .= "<td colspan=\"4\" align=\"\">".$tipoPago."</td>";
						$tablaContCmp .= "<td>Sub-Total Gastos:</td>";
						$tablaContCmp .= "<td bgcolor=\"#FFFF00\" width=\"100\"></td>";
						$tablaContCmp .= "<td align=\"right\">Desceunto de:</td>";
						$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\"></td>";
			$tablaContCmp .= "</tr>";
			$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td colspan=\"2\" align=\"left\">Condicion de Pago:</td>";
						$tablaContCmp .= "<td colspan=\"4\" align=\"center\">".$rowsOrdCompa['condiciones_pago']."</td>";
						$tablaContCmp .= "<td colspan=\"2\">* Incluye Iva:</td>";
						$tablaContCmp .= "<td align=\"right\">I.V.A. 12% 12.00%</td>";
						$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\"></td>";
			$tablaContCmp .= "</tr>";
						$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td colspan=\"2\" align=\"left\">Son:</td>";
						$tablaContCmp .= "<td colspan=\"6\" align=\"center\"></td>";
						//$tablaContCmp .= "<td colspan=\"2\"></td>";
						$tablaContCmp .= "<td align=\"right\"><strong>Total Bs</strong></td>";
						$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\"></td>";
			$tablaContCmp .= "</tr>";
			$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td colspan=\"10\" align=\"left\">Observacion: " .$rowsOrdCompa['observaciones']."</td>";
			$tablaContCmp .= "</tr>";
	$tablaContCmp .= "</table>";
			
		return $tabla.$br.$tablaDtaCmp.$tablaContCmp;
}

if($_GET["view"] == 2){
    ob_start();
} 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE 2.0 :. Compras - Orden de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<style>
body{
	<?php echo style($_GET["view"]);?>
}
table{
	<?php echo style($_GET["view"]);?>
	
	border:solid;
	border-width:1px;
}
</style>
</head>
<body>
    <table border="0" align="center" style="width:75%" >
        <tr>
            <td><?php echo consultarEmp(); ?></td>
        </tr>
        <tr>
            <td style="text-align:center" class="tituloArea">ORDEN DE COMPRA</td>
        </tr>
        <tr>
            <td><?php echo orderCompraHead($_GET['id']); ?></td>
        </tr>
        <tr>
            <td><?php echo ordenCompraBody($_GET['id']); ?></td>
        </tr>
    </table> 
    
<!--<table width="100%" border="1">
    <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
-->
    <br />
</body>
</html>

<?php

if($_GET["view"] == 2){
   // ob_start();
    
	//aqui va el contenido
	
    $content = ob_get_clean();

    // convert in PDF
    try{
        $html2pdf = new HTML2PDF('P', 'A4', 'es');
//      $html2pdf->setModeDebug();
        $html2pdf->setDefaultFont('helvetica');
        $html2pdf->writeHTML($content, isset($_GET['vuehtml']));
        $html2pdf->Output('Orden Compra.pdf');
    
	}catch(HTML2PDF_exception $e){
        echo $e;
        exit;
    }
	
}
//CUERPO DE LA ORDEN DE COMPRA

?>