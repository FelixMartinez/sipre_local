<?php
require_once ("../connections/conex.php");

session_start();

//Validación del Módulo
include("../inc_sesion.php");
if (!(validaAcceso("ga_solicitud_compra_historico_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
//Fin Validación del Módulo 

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_solicitud_compra_historico_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Compras - Hist&oacute;rico Solicitudes de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
	
    <script>
		function abrePdf(idSolicituCompras, seccionEmpUsuario){
			window.open('reportes/ga_solicitud_compra_pdf.php?idSolCom='+idSolicituCompras+'&session='+seccionEmpUsuario);
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
        	<td class="tituloPaginaCompras">Hist&oacute;rico Solicitudes de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
                <table>
                    <tr>
                        <td>
                            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                                <table  border="0" align="right">
                                    <tr>
                                        <td align="right" class="tituloCampo">Empresa:</td>
                                        <td id="tdlstEmpresa"></td>
                                        <td align="right" class="tituloCampo">Tipo De Compra:</td>
                                        <td id="tdlsttipCompra">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo">Nro. Solicitud:</td>
                                        <td colspan="3"><input type="text" id="txtNroSolicitud" name="txtNroSolicitud" class="inputHabilitado"/></td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo">Fecha Solicitud:</td>
                                        <td colspan="" >
                                        	Desde:<input type="text" id="txtFechaSol" name="txtFechaSol" class="inputHabilitado" autocomplete="off" size="15" style="text-align:center"/>
                                        </td>
                                        <td colspan="2" >
                                        	Hasta:<input type="text" id="txtFechaSolHasta" name="txtFechaSolHasta" class="inputHabilitado" autocomplete="off" size="15" style="text-align:center"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo">Criterio:</td>
                                        <td colspan="2"><input type="text" id="txtCriterio" name="txtCriterio" size="45" class="inputHabilitado"/></td>
                                         <td>
                                            <button type="button" id="buttBuscar" name="buttBuscar" onclick="xajax_BuscarSolicituComp(xajax.getFormValues('frmBuscar'));" style="cursor:default">
                                                Buscar
                                            </button>
                                            <button type="button" id="buttLimpiar" name="buttLimpiar" onclick="document.forms['frmBuscar'].reset(); byId('buttBuscar').click();" style="cursor:default">
                                                Limpiar
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                            </form>
                        </td>
                    </tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td id="tdListSolictComp"></td>
        </tr>
        <tr>
        	<td class="divMsjInfo2">
                <table cellpadding="0" cellspacing="0"  width="100%">
                    <tr>
                        <td width="25" align="left"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">
                            <table>
                                <tr>
                                <td><img src="../img/iconos/aprob_control_calidad.png"/></td>
                                <td>Ordenada</td>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/page_white_acrobat.png"/></td>
                                <td>Archivo PDF</td>
                                <td>&nbsp;</td>
                                </tr>
                            </table>
                        </td>
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
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', '');
	xajax_combLstTipCompra();
</script>
<script>
byId('txtFechaSol').value = "<?php echo date("01-m-Y"); ?>";
byId('txtFechaSolHasta').value = "<?php echo date("d-m-Y"); ?>";

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaSol").maskInput("99-99-9999",{placeholder:" "});
	   $("#txtFechaSolHasta").maskInput("99-99-9999",{placeholder:" "});
	});
	

	new JsDatePick({
		useMode:2,
		target:"txtFechaSol",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"armygreen"
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaSolHasta",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"armygreen"
	});
};
xajax_BuscarSolicituComp(xajax.getFormValues('frmBuscar'));
</script>
