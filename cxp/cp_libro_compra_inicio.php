<?php
require_once("../connections/conex.php");

/* Validación del Módulo */
require_once("../inc_sesion.php");
if(!(validaAcceso("cp_libro_compra_inicio"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cp_libro_compra_inicio.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Pagar - Libro de Compras</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
   	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorPagar.css">
    
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <!--<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">    
    <div class="noprint"><?php include("banner_cuentas_por_pagar.php"); ?></div>
    
    <div id="divInfo" class="print">
    <form id="frmFechasLibros" name="frmFechasLibros" onsubmit="return false;">
        <table border="0" width="100%">
        <tr>
            <td class="tituloPaginaCuentasPorPagar" colspan="3">Libro de Compras</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>            
        <tr>
            <td width="50%">
            <fieldset><legend class="legend">Rango de Fecha</legend>               
                <table border="0" width="100%" align="center">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Fecha Inicial:</td>
                    <td><input type="text" name="txtFechaOrigen" id="txtFechaOrigen" style="text-align:center" size="10" readonly="readonly"/></td>
                    <td align="right" class="tituloCampo" width="120">Fecha Final:</td>
                    <td><input type="text" id="txtFechaFinal" name="txtFechaFinal" style="text-align:center" size="10" readonly="readonly"/></td>  
                </tr>
                </table>
			</fieldset>
            </td>
            <td width="50%">
            <fieldset><legend class="legend">Departamento</legend>                
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td id="tdModulos" valign="top"></td>
                </tr>
                </table>  
            </fieldset>
            </td>                
        </tr>
        <tr>
        	<td>
            <fieldset><legend class="legend">Formato</legend>               
                <table border="0" width="100%" align="center">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Formato Número:</td>
                    <td>
                    	<select id="lstFormatoNumero" name="lstFormatoNumero">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option selected="selected" style="text-align:right" value="1">000,000,000.00</option>
                        	<option style="text-align:right" value="2">000.000.000,00</option>
                        	<option style="text-align:right" value="3">000000000.00</option>
                        	<option style="text-align:right" value="4">000000000,00</option>
                        </select>
                    </td>
                </tr>
                </table>
			</fieldset>
            </td>        
        </tr>
        <tr>
            <td align="center" colspan="2"><hr>
                <button type="submit" id="btnGenerar" name="btnGenerar" onclick="xajax_validaEnvia(xajax.getFormValues('frmFechasLibros'));">Generar</button>
            </td>
        </tr>
        </table>
    </form>
	</div>
    
    <div class="noprint"><?php include('pie_pagina.php'); ?></div>
</div>
</body>
</html>

<script>
byId('txtFechaOrigen').className = 'inputHabilitado';
byId('txtFechaFinal').className = 'inputHabilitado';
byId('lstFormatoNumero').className = 'inputHabilitado';

byId('txtFechaOrigen').value = "<?php echo date("01-m-Y")?>";
byId('txtFechaFinal').value = "<?php echo date("d-m-Y")?>";

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaOrigen").maskInput("99-99-9999",{placeholder:" "});
	   $("#txtFechaFinal").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaOrigen",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"torqoise"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaFinal",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"torqoise"		
	});
};

xajax_cargarModulos();
</script>