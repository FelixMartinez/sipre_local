<?php
require_once ("../connections/conex.php");

/* Validación del Módulo */
/*include('../inc_sesion.php');
validaModulo("an_clave");*/
/* Fin Validación del Módulo */

session_start();

include ("../inc_sesion.php");

//require_once('clases/rafkLista.php');
$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_te_clave_especial_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Claves Especiales</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <style type="text/css">
	.root {
		background-color:#FFFFFF;
		border:6px solid #999999;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:11px;
		max-width:1050px;
		position:absolute;
	}
	
	.handle {
		padding:3px;
		background-color:#990000;
		color:#FFFFFF;
		font-weight:bold;
		cursor:move;
	}
	
	</style>
    
    <script>
	function validarForm() {
		if (validarCampo('lstUsuario','t','lista') == true
		&& validarCampo('lstModuloClave','t','lista') == true
		&& validarCampo('txtContrasena','t','') == true
		) {
			xajax_guardarClaveUsuario(xajax.getFormValues('frmClaveEspecial'), xajax.getFormValues('frmListadoClavesEspeciales'));
		} else {
			validarCampo('lstUsuario','t','lista');
			validarCampo('lstModuloClave','t','lista');
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idClaveUsuario){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarClaveUsuario(idClaveUsuario, xajax.getFormValues('frmListadoClavesEspeciales'));
		}
	}
	
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include ('banner_tesoreria.php'); ?>
    </div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="80%" align="center">
        <tr>
        	<td class="tituloPaginaTesoreria">Claves Especiales</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_formClaveUsuario();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="100">Usuario:</td>
                    <td><input type="text" id="txtUsuarioBuscar" name="txtUsuarioBuscar" onkeyup="$('btnBuscar').click();"/></td>
                	<td align="right" class="tituloCampo" width="100">M&oacute;dulo:</td>
                    <td id="tdlstModuloClaveBuscar">
                        <select id="lstModuloClaveBuscar" name="lstModuloClaveBuscar">
                            <option value="-1">Todos...</option>
                        </select>
                        <script>
                        xajax_cargaLstModuloClaveBuscar();
                        </script>
                    </td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarClaveUsuario(xajax.getFormValues('frmBuscar'));" >Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();"  >Ver Todo</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListadoClavesEspeciales" name="frmListadoClavesEspeciales" style="margin:0">
                <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td id="tdListadoClavesEspeciales"></td>
                </tr>
                </table>
            </form>
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




<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmClaveEspecial" name="frmClaveEspecial" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblClaveEspecial" width="450px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Usuario:</td>
                <td id="tdlstUsuario" width="75%">
                	<select id="lstUsuario" name="lstUsuario">
                    	<option value="-1">Seleccione...</option>
                    </select>
                </td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
                <td id="tdlstModuloClave">
                    <select id="lstModuloClave" name="lstModuloClave">
                    	<option value="-1">Seleccione...</option>
                    </select>
                </td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Contraseña:</td>
                <td><input type="password" id="txtContrasena" name="txtContrasena" size="20"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="hidden" id="hddIdClaveUsuario" name="hddIdClaveUsuario"/>
            <input type="submit" onclick="validarForm();" value="Guardar">
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
</form>
</div>
<script>
xajax_listadoClavesUsuarios();
</script>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>
