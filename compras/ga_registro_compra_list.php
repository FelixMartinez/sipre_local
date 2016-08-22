<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_registro_compra_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_registro_compra_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE 2.0 :. Compras - Registro de Facturas de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
   <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

	<script>
	function validarDesaprobarOrden(idOrden, hddIdItm) {
		if (confirm('¿Seguro desea Anular la Orden?') == true) {
			xajax_desaprobarOrden(idOrden, hddIdItm, xajax.getFormValues('frmListaPedidoVenta'));
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_compras.php"); ?>
    </div>
    
    <div id="divInfo" class="print" >
    	<table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td class="tituloPaginaCompras">Registro de Facturas de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr class="noprint">
        	<td>
            	<table align="left" border="0">
                    <tr>
                        <td>
                            <button type="button" onclick="window.open('ga_registro_compra_form.php','_self');" title="Nuevo Registro de Compra" style="cursor:default">
                                <table align="center" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td><img src="../img/iconos/ico_new.png"/></td>
                                        <td>&nbsp;</td>
                                        <td>Nuevo</td>
                                    </tr>
                                </table>
                            </button>
                        </td>
                        <td>
                            <button type="button" onclick="xajax_encabezadoEmpresa($('lstEmpresa').value); window.print();" style="cursor:default">
                                <table align="center" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td><img src="../img/iconos/ico_print.png"/></td>
                                        <td>&nbsp;</td>
                                        <td>Imprimir</td>
                                    </tr>
                                </table>
                            </button>
                        </td>
                    </tr>
                </table>
				
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                    <tr align="left">
                    	<td align="right" class="tituloCampo">Empresa:</td>
                    	<td id="tdlstEmpresa" colspan="3">
                            <select id="lstEmpresa" name="lstEmpresa">
                                <option value="-1">[ Todos ]</option>
                            </select>
                    	</td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo" >Nro Orden:</td>
                        <td ><input type="text" id="nroOrden" name="nroOrden" style="text-align:center" class="inputHabilitado" size="15"></td>
                        <td align="right" class="tituloCampo" >Nro Solicitud:</td>
                        <td ><input type="text" id="nroSolicitud" name="nroSolicitud" style="text-align:center" class="inputHabilitado" size="15"></td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo" >Fecha Orden de Compra:</td>
                        <td colspan="3">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;Desde:&nbsp;</td>
                                    <td><input type="text" id="txtFechaDesde" name="txtFechaDesde" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/></td>
                                    <td>&nbsp;Hasta:&nbsp;</td>
                                    <td><input type="text" id="txtFechaHasta" name="txtFechaHasta" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo" >Criterio:</td>
                        <td colspan="3"><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" size="25" onkeyup="$('btnBuscar').click();"></td>
                    </tr>
                    <tr>
                        <td colspan="4" align="right">
                            <button type="submit" class="noprint" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
                            <button type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                        </td>
                    </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPedidoVenta" name="frmListaPedidoVenta" style="margin:0">
            	<div id="divListaPedidoVenta" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                    </tr>
                    </table>
				</div>
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
                            <td><img src="../img/iconos/ico_amarillo.gif" /></td>
                            <td>Convertido a Orden</td>
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
<script>
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listadoPedidosCompra(0, "fecha_orden", "DESC", '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|||<?php echo date("01-m-Y")?>|<?php echo date("d-m-Y")?>');

byId('txtFechaDesde').value = "<?php echo date("01-m-Y")?>";
byId('txtFechaHasta').value = "<?php echo date("d-m-Y")?>";
	
	jQuery(function($){
	   $("#txtFechaDesde").maskInput("99-99-9999",{placeholder:" "});
	   $("#txtFechaHasta").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"armygreen"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"armygreen"
	});
</script>