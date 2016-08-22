<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_catalogo_precios_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_catalogo_precios_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Catálogo de Precios</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2, valor3) {
		byId('tblArticuloPrecio').style.display = 'none';
		
		if (verTabla == "tblArticuloPrecio") {
			document.forms['frmArticuloPrecio'].reset();
			byId('hddIdArticuloPrecio').value = '';
			
			byId('txtPrecioArt').className = 'inputHabilitado';
			
			xajax_formArticuloPrecio(valor, valor2, valor3);
			
			tituloDiv1 = 'Editar Precio';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblArticuloPrecio") {
			byId('txtPrecioArt').focus();
			byId('txtPrecioArt').select();
		}
	}
	
	function validarFrmArticuloPrecio() {
		if (validarCampo('txtPrecioArt','t','monto') == true) {
			xajax_guardarArticuloPrecio(xajax.getFormValues('frmArticuloPrecio'), xajax.getFormValues('frmBuscar'), xajax.getFormValues('frmListaArticulos'));
		} else {
			validarCampo('txtPrecioArt','t','monto');
			
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
            <td class="tituloPaginaRepuestos">Catálogo de Precios</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_exportarCatalogo(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
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
                    <td align="right" class="tituloCampo">Aplica Impuesto:</td>
                    <td>
                    	<select id="lstAplicaIva" name="lstAplicaIva" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="0">No</option>
                        	<option value="1">Si</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Tipo de Artículo:</td>
                    <td id="tdlstTipoArticulo"></td>
                    <td align="right" class="tituloCampo">Clasificación:</td>
                    <td>
                        <select id="lstVerClasificacion" name="lstVerClasificacion" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Ver:</td>
                    <td colspan="3">
                    	<label><input type="checkbox" name="cbxVerUbicDisponible" checked="checked" value="3"/> Con Disponibilidad</label>
                        <label><input type="checkbox" name="cbxVerUbicSinDisponible" checked="checked" value="4"/> Sin Disponibilidad</label>
					</td>
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
            <form id="frmListaArticulos" name="frmListaArticulos" style="margin:0">
            	<div id="divListaArticulos" style="width:100%"></div>
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
							<td><img src="../img/iconos/accept.png"/></td><td>Si Aplica Impuesto</td>
                        </tr>
                        </table>
                        
                        <table>
                        <tr>
							<td>Unid. Disponible = Saldo - Reservada (Serv.)</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
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
	
<form id="frmArticuloPrecio" name="frmArticuloPrecio" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblArticuloPrecio" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
                <td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span><?php echo $spanPrecioUnitario; ?>:</td>
            	<td width="82%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                    	<td><input type="text" id="txtDescripcion" name="txtDescripcion" readonly="readonly" size="30"/></td>
                        <td><input type="text" id="txtPrecioArt" name="txtPrecioArt" maxlength="17" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" size="10" style="text-align:right"/></td>
                        <td id="tdlstMoneda"></td>
					</tr>
                    </table>
				</td>
            </tr>
            </table>
        </td>
	</tr>
    <tr>
    	<td align="right"><hr>
        	<input type="hidden" id="hddIdArticuloPrecio" name="hddIdArticuloPrecio"/>
        	<input type="hidden" id="hddIdArticulo" name="hddIdArticulo"/>
        	<input type="hidden" id="hddIdPrecio" name="hddIdPrecio"/>
            <button type="submit" id="btnGuardarArticuloPrecio" name="btnGuardarArticuloPrecio" onclick="validarFrmArticuloPrecio();">Aceptar</button>
            <button type="button" id="btnCancelarArticuloPrecio" name="btnCancelarArticuloPrecio" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
</form>
</div>

<script>
byId('lstAplicaIva').className = 'inputHabilitado';
byId('lstVerClasificacion').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstTipoArticulo();
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaCatalogoPrecio(0,'codigo_articulo','ASC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>