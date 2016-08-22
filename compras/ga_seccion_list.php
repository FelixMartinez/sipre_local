<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_seccion_list"))) {
	echo "
	<script>
		alert('Acceso Denegado');
		top.history.back();
	</script>";
}
/* Fin Validación del Módulo */

require_once('../clases/rafkLista.php');
$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_ga_seccion_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Compras - Secciones</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../clases/styleRafkLista.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblSeccion').style.display = 'none';

		if (verTabla == "tblSeccion") {
			if (valor > 0) {
				xajax_cargarSeccion(valor);
				tituloDiv1 = 'Editar Seccion';
			} else {
				xajax_formSeccion();
				xajax_cargaLstTipoSeccion("", "nuevo");
				tituloDiv1 = 'Agregar Seccion';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo').innerHTML = tituloDiv1;
		
		if (verTabla == "tblSeccion") {
			byId('txtSeccion').focus();
			byId('txtSeccion').select();
		}
	}

	function validarForm() {
		if (validarCampo('txtSeccion','t','') == true
		&& validarCampo('txtAbreviatura','t','') == true
		&& validarCampo('lstTipoSeccionNew','t','listaExceptCero') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true
		) {
			xajax_guardarSeccion(xajax.getFormValues('frmSeccion'));
		} else {
			validarCampo('txtSeccion','t','');
			validarCampo('txtAbreviatura','t','');
			validarCampo('lstTipoSeccionNew','t','listaExceptCero');
			validarCampo('lstEstatus','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	function validarEliminar(idSeccion){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarSeccion(idSeccion);
		}
	}
	
	function validarEliminarBloque(){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarSeccionBloque(xajax.getFormValues('formSecciones'));
		}
	}

	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_compras.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table border="0" width="100%">
            <tr>
                <td class="tituloPaginaCompras">Secciones</td>
            </tr>
            <tr>
            	<td>&nbsp;</td>
            </tr>
            <tr>
                <td>
                    <table align="left" border="0">
                        <tr>
                            <td> 
                                <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="abrirDivFlotante1(this, 'tblSeccion');">
                                    <button type="button">
                                        <table align="center" cellpadding="0" cellspacing="0">
                                            <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr>
                                        </table>
                                    </button>
                                </a>
                            </td>
                            <td>
                                <button type="button" id="btnEliminar" name="btnEliminar" onclick="validarEliminarBloque();" >
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr>
                                    </table>
                                </button>
                            </td>
                        </tr>
                    </table>
                    
                    <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                        <table align="right" border="0">
                            <tr align="left">
                                <td align="right" class="tituloCampo">Tipo de seccion:</td>
                                <td id="tdlstTipoSeccionBus" colspan="2"></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Criterio:</td>
                                <td><input type="text" id="txtCriterio" name="txtCriterio" size="35%" class="inputHabilitado" onkeyup="byId('btnBuscar').click();"/></td>
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
            	<td align="right"></td>
            </tr>
            <tr>
            	<td><form id="formSecciones" name="formSecciones" style="margin:0"><div id="divSecciones"></div></form></td>
            </tr>
            <tr>
                <td>
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                            <td align="center">
                                <table>
                                <tr>
                                    <td><img src="../img/iconos/ico_verde.gif" /></td>
                                    <td>Activo</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_rojo.gif" /></td>
                                    <td>Inactivo</td>
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmSeccion" name="frmSeccion" style="margin:0">
    <table border="0" id="tblSeccion" width="450px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Sección:</td>
                <td width="70%" align="left">
                    <input type="text" id="txtSeccion" name="txtSeccion" size="25"/>
                    <input type="hidden" id="hddIdSeccion" name="hddIdSeccion" />
				</td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Abreviatura:</td>
                <td align="left"><input type="text" id="txtAbreviatura" name="txtAbreviatura" size="25" /></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Compra:</td>
                <td align="left" id="tdlstTipoSeccion"></td>
            </tr>
              <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td align="left" id="tdlstEstatus">
					<select class="inputHabilitado" name="lstEstatus" id="lstEstatus">
                        <option value="-1">[Seleccione]</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select> 
                 </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <button type="button" onclick="validarForm();" > Guardar</button >
            <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>
<script language="javascript">
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
	});
}

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
	
xajax_listadoSecciones(0,'descripcion','ASC');
xajax_cargaLstTipoSeccion("", "buscar");
</script>