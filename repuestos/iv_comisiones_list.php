<?php
require_once("../connections/conex.php");
include('../js/libGraficos/Code/PHP/Includes/FusionCharts.php');
include('../js/libGraficos/Code/PHP/Includes/FC_Colors.php');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("if_comisiones_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("../informe/controladores/ac_if_comisiones_list.php");

//$xajax->setFlag('debug',true); 
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Informe Gerencial - Comisiones</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<!--<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Comisiones</td>
        </tr>
        <tr>
            <td class="noprint">
            	<table align="left">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_imprimirComisiones(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                    	<button type="button" onclick="xajax_exportarComisiones(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Mes - Año:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtFecha" name="txtFecha" class="inputHabilitado" readonly="readonly" size="10" style="text-align:center" value="<?php echo date("m-Y"); ?>"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/ico_date.png" id="imgFecha" name="imgFecha" class="puntero noprint"/>
                        <script type="text/javascript">
                        Calendar.setup({
							inputField : "txtFecha",
							ifFormat : "%m-%Y",
							button : "imgFecha"
                        });
                        </script>
                    </div>
                    </td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Cargo:</td>
                    <td id="tdlstCargo">
                        <select id="lstCargo" name="lstCargo">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo" width="120">Empleado:</td>
                    <td id="tdlstEmpleado">
                        <select id="lstEmpleado" name="lstEmpleado">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo" width="120">Módulo:</td>
                    <td id="tdlstModulo">
                        <select id="lstModulo" name="lstModulo">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarComision(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaComisiones" name="frmListaComisiones" style="margin:0">
            	<div id="divListaComisiones" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese los datos de la Comisiones a Buscar</td>
                    </tr>
                    </table>
                </div>
            </form>
            </td>
        </tr>
        </table>
	</div>
	
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>


<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
    <table border="0" width="960">
    <tr>
    	<td id="tdListadoComisionDetalle"></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" width="360">
    <tr>
    	<td align="right" class="tituloCampo">Porcentaje:</td>
        <td><input type="text" id="txtPorcentaje" name="txtPorcentaje"/></td>
    </tr>
    <tr>
    	<td align="right" colspan="2"><hr>
            <button type="button" id="btnCancelar2" name="btnCancelar2" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script type="text/javascript">
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'onchange=\"xajax_cargaLstCargo(this.value); byId(\'btnBuscar\').click();\"');
xajax_cargaLstCargo('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstModulo(0);

function openImg(idObj) {
	var oldMaskZ = null;
	var $oldMask = $(null);
	
	$(".modalImg").each(function() {
		$(idObj).overlay({
			//effect: 'apple',
			oneInstance: false,
			zIndex: 10100,
			
			onLoad: function() {
				if ($.mask.isLoaded()) {
					oldMaskZ = $.mask.getConf().zIndex; // this is a second overlay, get old settings
					$oldMask = $.mask.getExposed();
					$.mask.getConf().closeSpeed = 0;
					$.mask.close();
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0,
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false
					});
				} // Other onLoad functions
			},
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) { // re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0
					});
					
					$(".apple_overlay").css("zIndex", oldMaskZ + 2); // Assumes the other overlay has apple_overlay class
				}
			}
		}).load();
		
		/*$(idObj).overlay({
			closeOnClick: true,
			oneInstance: false,
			//effect: 'apple',
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) {	//re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						loadSpeed: 0
					});
					$(".apple_overlay").css("zIndex", oldMaskZ + 2);//Assumes the other overlay has apple_overlay class
				}
			}
		}).load();*/
		
		/*$(idObj).overlay({
			mask: {
				color: '#000000',
				zIndex: 10090,
				maskId: 'validExposeMask',
				closeOnClick: false,
				closeOnEsc: false,
			},
			effect: 'apple',
			oneInstance:false,
			closeOnClick: false,
			closeOnEsc: false,
			zIndex: 10100,
			close: 'noClose'
		}).load();*/
		
		
		/*$(idObj).overlay({
			mask: { // some mask tweaks suitable for modal dialogs
				color: '#000000',
				loadSpeed: 200,
				opacity: 0.7
			},
			closeOnClick: true,
			oneInstance: false
		}).load();*/
	});
	
	//alert('exposeMask'+i);
}

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>