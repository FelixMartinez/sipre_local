<?php
require_once ("../connections/conex.php");

@session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_solicitud_compra_condicionada_list"))) {
	echo "
	<script>
		alert('Acceso Denegado');
		top.history.back();
	</script>";
}
/* Fin Validación del Módulo */

require_once('../clases/rafkLista.php');
$currentPage = $_SERVER["PHP_SELF"];

include('sa_proceso_compras_code.php');
//require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
//$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
//$xajax->configure('javascript URI', 'controladores/xajax/');

//include("controladores/ac_ga_marca_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

//$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Compras - Solicitudes de Compra Condicionadas</title>
    <?php //$xajax->printJavascript('controladores/xajax/'); 
			getXajaxJavascript();
			includeScripts();?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../clases/styleRafkLista.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    <!--<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    <script type="text/javascript" language="javascript" src="../vehiculos/vehiculos.inc.js"></script>
	
    <script>
	function validarForm() {

	}
	
	function cargar(marca){
		//alert(marca);
		xajax_cargarMarca(marca);
	}
	function eliminar(marca){
		//alert(marca);
		if (utf8confirm('&iquest;Desea eliminar La Solicitud: '+marca+'?')){
			window.location="ga_solicitud_compras_eliminar.php?id="+marca;
		}
	}
	
	function open_win(){
		var theHandle = document.getElementById("divFlotanteTitulo");
		var theRoot   = document.getElementById("divFlotante");
		theRoot.style.display="";
		setCenter("divFlotante",true);
		document.getElementById("codigo_empleado").focus();
	}
	function close_win(){
		window.location="ga_solicitud_compra_list.php";
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_compras.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCompras">Solicitudes de Compra Condicionadas</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
            	<table align="left">
                <tr>
                	 <td><input style="display:none;" type="button" value="Nuevo" onclick="window.location='ga_solicitud_compras_insertar.php';"/></td>
                  <!--  <td><input type="button" id="btnEliminar" name="btnEliminar" onclick="xajax_eliminarMarca(xajax.getFormValues('frmListaMarca'));" value="Eliminar" /></td>
					<button type="button" onclick="window.location='ga_solicitud_compras_insertar.php';"><img border="0" src="../vehiculos/iconos/plus.png" style="padding:2px; vertical-align:middle;" />Agregar</button> -->
                </tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td>
             <form id="frmListaMarca" name="frmListaMarca" style="margin:0">
            	<?php
                $objMarca = new lista();
                $objMarca->iniciar(15, 0, "id_solicitud_compra", "DESC", $currentPage, "Nombre");
                $query = "SELECT 
					vw_ga_solicitudes.*,
					cp_proveedor.nombre AS nombre_proveedor,
					CONCAT_WS('-', codigo_empresa, numero_solicitud) AS numero_solicitud,
					ga_estado_solicitud_compra.estado_solicitud_compras,
					ga_tipo_seccion.tipo_seccion
				FROM vw_ga_solicitudes
					LEFT JOIN cp_proveedor ON (cp_proveedor.id_proveedor = vw_ga_solicitudes.id_proveedor)
					INNER JOIN ga_estado_solicitud_compra ON (vw_ga_solicitudes.id_estado_solicitud_compras = ga_estado_solicitud_compra.id_estado_solicitud_compras)
					INNER JOIN ga_tipo_seccion ON (vw_ga_solicitudes.tipo_compra = ga_tipo_seccion.id_tipo_seccion)
				WHERE vw_ga_solicitudes.id_estado_solicitud_compras = 7
					OR vw_ga_solicitudes.id_estado_solicitud_compras = 6";
                $rsMarca = $objMarca->consulta($database_conex, $conex, $query);
				
                echo $objMarca->tabla(
					array(
						//array("","","id_solicitud_compra","center","checkbox","cbxMarc"),
						array("Nro.","10px","numero_solicitud","left",'nowrap="nowrap"'),
						array("Empresa","20%","nombre_empresa","left"),
						array("Departamento","20%","nombre_departamento","left"),
						array("Centro Costo","20%","nombre_unidad_centro_costo","left"),
						array("Proveedor","10%","nombre_proveedor","left"),
						array("Tipo Compra","10%","tipo_seccion","left"),
						array("Estado","10%","estado_solicitud_compras","left")
						),
					$rsMarca[0],
					array(
						array("../img/iconos/ico_aceptar.gif","xajax_aprobar_solicitud('|id_solicitud_compra|')","onclick"),
						array("../img/iconos/ico_view.png","ga_solicitud_compras_editar.php?view=1&id=|id_solicitud_compra|","href"),
						array("../img/iconos/ico_print.png","ga_solicitud_compras_editar.php?view=print&id=|id_solicitud_compra|","href"),
						array("../img/iconos/ico_edit.png","ga_solicitud_compras_editar.php?view=e&id=|id_solicitud_compra|","href"),
						array("../img/iconos/ico_delete.png","javascript:eliminar('|id_solicitud_compra|');","href")
						)
					);
                ?>
			</form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                        <tr>
                            <td><img src="../img/iconos/ico_aceptar.gif" /></td>
                            <td>Aprobar / Conformar / Procesar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_view.png" /></td>
                            <td>Ver</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_print.png" /></td>
                            <td>Imprimir</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_edit.png" /></td>
                            <td>Editar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_delete.png" /></td>
                            <td>Eliminar</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>
<?php include("sa_form_proceso_solicitud.php"); ?>



