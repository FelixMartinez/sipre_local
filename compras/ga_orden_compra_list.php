<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_orden_compra_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_orden_compra_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE 2.0 :. Compras - Ordenes de Compra</title>
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
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_compras.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td class="tituloPaginaCompras">Ordenes de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr class="noprint">
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_encabezadoEmpresa($('lstEmpresa').value); window.print();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                    </td>
                </tr>
                </table>
				
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa"  colspan="2">
                    <select id="lstEmpresa" name="lstEmpresa">
                        <option value="-1">[ Todos ]</option>
                    </select>
                    </td>
                 </tr>
                 <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Fecha Solicitud:</td>
                    <td id="">Desde:<input type="text" id="txtFechaDes" name="txtFechaDes" class="inputHabilitado" autocomplete="off" size="15" style="text-align:center"/></td>
                    <td id="">Hasta:<input type="text" id="txtFechaHas" name="txtFechaHas" class="inputHabilitado" autocomplete="off" size="15" style="text-align:center"/></td>
                 </tr>
                 <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Tipo de Compra:</td>
                    <td colspan="2">
                        <select id="tipCompra" name="tipCompra" class="inputHabilitado" >
                            <option value="-1">[ Selecciona ]</option>
                            <option value="2">Cargos (Activos Fijo)</option>
                            <option value="3">Servicios</option>
                            <option value="4">Gastos / Activos</option>
                        </select>
                    </td>
                  </tr>
                  <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Nro Solicitud:</td>
                    <td colspan="2"><input type="text" id="txtNroSolict" name="txtNroSolict" class="inputHabilitado"></td>
                  </tr>
                 <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" onkeyup="$('btnBuscar').click();"></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            	<div id="divListaPedidoVenta" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                    </tr>
                    </table>
				</div>
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
                                    <td><img src="../img/iconos/ico_aceptar_naranja.png"/></td>
                                    <td>Procesado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_importar.gif"/></td>
                                    <td>Aprobar</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/page_white_acrobat.png"/></td>
                                    <td>Solicitud de Compra Pdf </td>
                                </tr>
                            </table>
                    	</td>
                    </tr>
                </table>
            </td>
        </tr>
        </table>
    </div>
	
    <div class="noprint"><?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>

<script>
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaPedidoCompra(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>' + byId('txtFechaDes').value + '|' + byId('txtFechaHas').value);
byId('txtFechaDes').value = "";
byId('txtFechaHas').value = "";
window.onload = function(){
	byId('txtFechaDes').value = "<?php echo date("01-m-Y") ?>";
	byId('txtFechaHas').value = "<?php echo date("d-m-Y"); ?>";
	jQuery(function($){
	   $("#txtFechaDes").maskInput("99-99-9999",{placeholder:" "});
	   $("#txtFechaHas").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDes",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"armygreen"
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaHas",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"armygreen"
	});
};

</script>