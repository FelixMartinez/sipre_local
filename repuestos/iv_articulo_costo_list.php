<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_articulo_costo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_articulo_costo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Costos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
	}
	
	function validarFrmCostoArticulo() {
		if (validarCampo('txtCodigoArticulo','t','') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('txtFechaCosto','t','fecha') == true
		&& validarCampo('txtCosto','t','monto') == true) {
			xajax_guardarCosto(xajax.getFormValues('frmCostoArticulo'), xajax.getFormValues('frmListaCostos'), xajax.getFormValues('frmBuscar'));
		} else {
			validarCampo('txtCodigoArticulo','t','');
			validarCampo('txtIdProv','t','');
			validarCampo('txtFechaCosto','t','fecha');
			validarCampo('txtCosto','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Costos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_exportarCosto(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
                </tr>
                </table>
                
	        <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
		</tr>
        <tr>
        	<td>
            <form id="frmListaCostos" name="frmListaCostos" style="margin:0">
            	<div id="divListaCostos" style="width:100%"></div>
            </form>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmCostoArticulo" name="frmCostoArticulo" onsubmit="return false;" style="margin:0">
    <input type="hidden" id="hddIdArticuloCosto" name="hddIdArticuloCosto" readonly="readonly"/>
    <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
    <table border="0" width="560">
    <tr>
    	<td>
        	<table border="0">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="20%">Código</td>
                <td><input type="text" id="txtCodigoArticulo" name="txtCodigoArticulo" readonly="readonly"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Proveedor:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdProv" name="txtIdProv" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td><button type="button" id="btnInsertarProv" name="btnInsertarProv" onclick="byId('frmBuscarProveedor').reset(); xajax_listaProveedor(0,'','','');" title="Listar"><img src="../img/iconos/help.png"/></button></td>
                        <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha:</td>
                <td><input type="text" id="txtFechaCosto" name="txtFechaCosto" class="inputHabilitado" size="10" style="text-align:center"/></td>
            </tr>
            
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Costo:</td>
                <td><input type="text" id="txtCosto" name="txtCosto" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" size="14" style="text-align:right"></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarCostoArticulo" name="btnGuardarCostoArticulo" onclick="validarFrmCostoArticulo();">Aceptar</button>
            <button type="button" id="btnCancelarCostoArticulo" name="btnCancelarCostoArticulo" onclick="byId('divFlotante1').style.display='none'; byId('divFlotante2').style.display='none';">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
  	
    <table border="0" id="tblListados" width="560">
    <tr id="trBuscarProveedor">
    	<td>
        	<form id="frmBuscarProveedor" name="frmBuscarProveedor" onsubmit="return false;" style="margin:0">
            	<table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                	<td><input type="text" id="txtCriterioBuscarProveedor" name="txtCriterioBuscarProveedor" onkeyup="byId('btnBuscarProveedor').click();"/></td>
                    <td><button type="submit" id="btnBuscarProveedor" name="btnBuscarProveedor" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button></td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td id="tdListado"></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" onclick="byId('divFlotante2').style.display='none';">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
window.onload = function(){
	jQuery(function($){
		$("#txtFechaCosto").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaCosto",
		dateFormat:"%d-%m-%Y"
	});
};

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaCosto(0, 'id_articulo', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>