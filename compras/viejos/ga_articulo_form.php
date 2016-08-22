<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include("../inc_sesion.php");
if(!(validaAcceso("ga_articulo_list"))
&& !(validaAcceso("ga_articulo_list","insertar"))
&& !(validaAcceso("ga_articulo_list","editar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_articulo_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Compras - Articulos</title>
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/mootabs/mootabs1.css">
	<script type="text/javascript" language="javascript" src="../js/mootabs/mootools.js"></script>
    <script type="text/javascript" language="javascript" src="../js/mootabs/mootabs1.js"></script>
    
    <script>
	function validarFormArt() {
		if (validarCampo('txtCodigoProveedor','t','') == true
		&& validarCampo('lstMarcaArt','t','lista') == true
		&& validarCampo('lstTipoArticuloArt','t','lista') == true
		&& validarCampo('txtDescripcion','t','') == true
		&& validarCampo('lstTipoUnidad','t','lista') == true
		&& validarCampo('lstSeccionArt','t','lista') == true
		&& validarCampo('lstSubSeccionArt','t','lista') == true
		) {
			$valido = false;
			for (i = 0; i <= $('hddCantCodigo').value; i++) {
				$('txtCodigoArticulo'+i).className = "inputInicial";
				if($('txtCodigoArticulo'+i).value.length > 0
				&& $('txtCodigoArticulo'+i).value != null
				&& $('txtCodigoArticulo'+i).value != 'null') {
					$valido = true;
				}
			}
			
			if ($valido == true) {
				xajax_guardarArticulo(xajax.getFormValues('frmArticulo'), 
									  xajax.getFormValues('frmListadoArtSus'), 
									  xajax.getFormValues('frmListadoArtAlt'), 
									  xajax.getFormValues('frmListadoModComp'), 
									  xajax.getFormValues('frmListadoAlm'));
			} else if ($valido == false) {
				for (i = 0; i <= $('hddCantCodigo').value; i++) {
					if(!($('txtCodigoArticulo'+i).length > 0
					&& $('txtCodigoArticulo'+i).value != null
					&& $('txtCodigoArticulo'+i).value != 'null')) {
						validarCampo('txtCodigoArticulo'+i,'t','');
					}
				}
				
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		} else {
			validarCampo('txtCodigoProveedor','t','');
			validarCampo('lstMarcaArt','t','lista');
			validarCampo('lstTipoArticuloArt','t','lista');
			validarCampo('txtDescripcion','t','');
			validarCampo('lstTipoUnidad','t','lista');
			validarCampo('lstSeccionArt','t','lista');
			validarCampo('lstSubSeccionArt','t','lista');
			
			$valido = false;
			for (i = 0; i <= $('hddCantCodigo').value; i++) {
				$('txtCodigoArticulo'+i).className = "inputInicial";
				if($('txtCodigoArticulo'+i).value.length > 0
				&& $('txtCodigoArticulo'+i).value != null
				&& $('txtCodigoArticulo'+i).value != 'null') {
					$valido = true;
				}
			}
			if (!($valido == true)) {
				for (i = 0; i <= $('hddCantCodigo').value; i++) {
					if(!($('txtCodigoArticulo'+i).length > 0
					&& $('txtCodigoArticulo'+i).value != null
					&& $('txtCodigoArticulo'+i).value != 'null')) {
						validarCampo('txtCodigoArticulo'+i,'t','');
					}
				}
			}
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormArtSustituto() {
		if (validarCampo('lstArticuloSus','t','lista') == true
		) {
			$('divFlotante').style.display='none';
			xajax_insertarArticuloSustituto(xajax.getFormValues('frmArtSus'), xajax.getFormValues('frmListadoArtSus'));
		} else {
			validarCampo('lstArticuloSus','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormArtAlterno() {
		if (validarCampo('lstArticuloAlt','t','lista') == true
		) {
			$('divFlotante').style.display='none';
			xajax_insertarArticuloAlterno(xajax.getFormValues('frmArtAlt'), xajax.getFormValues('frmListadoArtAlt'));
		} else {
			validarCampo('lstArticuloAlt','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormModCompatible() {
		/*if (validarCampo('lstModeloComp','t','lista') == true
		) {*/
			$('divFlotante').style.display='none';
			xajax_insertarModeloCompatible(xajax.getFormValues('frmModComp'), xajax.getFormValues('frmListadoModComp'));
		/*} else {
			validarCampo('lstModeloComp','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}*/
	}
	
	function validarFormAlmacen() {
		if (validarCampo('lstAlmacenAct','t','lista') == true
		&& validarCampo('lstCalleAct','t','lista') == true
		&& validarCampo('lstEstanteAct','t','lista') == true
		&& validarCampo('lstTramoAct','t','lista') == true
		&& validarCampo('lstCasillaAct','t','lista') == true
		) {
			xajax_insertarAlmacen(xajax.getFormValues('frmAlmacen'), xajax.getFormValues('frmListadoAlm'));
		} else {
			validarCampo('lstAlmacenAct','t','lista');
			validarCampo('lstCalleAct','t','lista');
			validarCampo('lstEstanteAct','t','lista');
			validarCampo('lstTramoAct','t','lista');
			validarCampo('lstCasillaAct','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function bloquearForm() {
		$('lstMarcaArt').disabled = true;
		$('lstTipoArticuloArt').disabled = true;
		$('txtCodigo').readOnly = true;
		$('txtCodigoProveedor').readOnly = true;
		$('txtDescripcion').readOnly = true;
		$('lstTipoUnidad').disabled = true;
		$('lstSeccionArt').disabled = true;
		$('lstSubSeccionArt').disabled = true;
		$('fleUrlImagen').style.display = 'none';
		
		$('btnInsertarArt').style.display = 'none';
		$('btnEliminarArt').style.display = 'none';
		$('btnInsertarArtAlt').style.display = 'none';
		$('btnEliminarArtAlt').style.display = 'none';
		$('btnInsertarModComp').style.display = 'none';
		$('btnEliminarModComp').style.display = 'none';
		/*$('btnInsertarAlm').style.display = 'none';
		$('btnEliminarAlm').style.display = 'none';*/
		
		$('trAccionesPantalla').style.display = '';
		$('btnGuardar').style.display = 'none';
	}
	
	Array.prototype.count = function() {
		return this.length;
	};
	
	function cambiarStyle(indice, id) {
		if ($('tblUnidadBas'+indice).className == 'divGris') {
			$('tblUnidadBas'+indice).className = 'divMsjInfo2';
			$('tblUnidadBas'+indice).childNodes[0].childNodes[0].childNodes[2].childNodes[0].checked = true;
			$('hddObjModCompPreseleccionado').value = $('hddObjModCompPreseleccionado').value + '|' + id;
		} else {
			$('tblUnidadBas'+indice).className = 'divGris';
			$('tblUnidadBas'+indice).childNodes[0].childNodes[0].childNodes[2].childNodes[0].checked = false;
			
			var longstring = $('hddObjModCompPreseleccionado').value;
			var arrayCadena = longstring.split('|'); 
			
			var cadena = "";
			for (i = 0; i < arrayCadena.count(); i++) {
				if (arrayCadena[i] != id && arrayCadena[i] != "") {
					cadena = cadena + "|" + arrayCadena[i];
				}
			}
			
			$('hddObjModCompPreseleccionado').value = cadena;
		}
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
        	<td class="tituloPaginaCompras">Art&iacute;culo</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr class="noprint" id="trAccionesPantalla" style="display:none">
        	<td>
            	<table align="left" border="0">
                <tr>
                    <td>
<button type="button" id="btnEtiqueta" name="btnEtiqueta" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));" style="cursor:default">
<table align="center" cellpadding="0" cellspacing="0">
<tr>
<td>&nbsp;</td>
<td><img src="../img/iconos/ico_codigo_barra.gif"/></td>
<td>&nbsp;</td>
<td>Etiqueta</td></tr></table></button>
                    </td>
                </tr>
                </table>
            	
            	<table align="right">
                <tr>
                	<td>
                    	<button type="button" onclick="window.print();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td align="left">
            <form action="controladores/ac_upload_file_articulo.php" enctype="multipart/form-data" id="frmArticulo" name="frmArticulo" method="post" style="margin:0" target="iframeUpload">
            	<table border="0" width="100%">
                <tr>
                	<td align="left" class="tituloArea" colspan="6">Datos del Artículo</td>
                </tr>
                <tr>
                	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Marca:</td>
                    <td id="tdlstMarcaArt">
                    	<select id="lstMarcaArt" name="lstMarcaArt">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Artículo:</td>
                    <td id="tdlstTipoArticuloArt">
                    	<select id="lstTipoArticuloArt" name="lstTipoArticuloArt">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                	<td>
                    	<input type="text" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
                        <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" readonly="readonly" value="<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>"/>
                    </td>
                </tr>
                <tr>
                	<td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Código:</td>
                    <td id="tdCodigoArt" width="20%"></td>
                	<td align="right" class="tituloCampo" width="28%"><span class="textoRojoNegrita">*</span>Cód. Articulo (Proveedor):</td>
                    <td width="19%"><input type="text" id="txtCodigoProveedor" name="txtCodigoProveedor" size="26"/></td>
                    <td align="center" colspan="2" rowspan="5" width="17%">
                    	<table border="1" width="100%">
                        <tr>
                        	<td><img border="0" id="imgCodigoBarra" name="imgCodigoBarra"></td>
						</tr>
                        <tr>
                        	<td align="center" class="imgBorde"><img id="imgArticulo" src="../img/logos/logo_gotosystems.jpg" height="100"/></td>
						</tr>
                        <tr>
                        	<td>
                            	<input type="file" id="fleUrlImagen" name="fleUrlImagen" onchange="javascript: submit();" />
                                <iframe name="iframeUpload" style="display:none"></iframe>
                                <input type="hidden" id="hddUrlImagen" name="hddUrlImagen" />
                            </td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr>
                	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                    <td colspan="3"><textarea cols="66" id="txtDescripcion" name="txtDescripcion" rows="4"></textarea></td>
                </tr>
                <tr>
                	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Unidad:</td>
                    <td id="tdlstTipoUnidad">
                    	<select id="lstTipoUnidad" name="lstTipoUnidad">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
					</td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cuenta Contable:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" id="txtCuentaContable" name="txtCodigoContable" readonly="readonly"/>
                                <input type="hidden" id="hddIdCuentaContable" name="hddIdCodigoContable" readonly="readonly" size="8" />
                            </td>
                            <td>
                                <button type="button" id="btnInsertarArt" name="btnInsertarArt" onclick="xajax_listadoCuentaContable();" title="Agregar Cuenta Contable"><img src="../img/iconos/find.png" width="16" height="16" /></button>
                            </td>
                        </tr>
                        </table>
					</td>
				</tr>
                <tr>
                	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Seccion:</td>
                    <td colspan="3" id="tdlstSeccionArt">
                    	<select id="lstSeccionArt" name="lstSeccionArt">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
				</tr>
                <tr>
                	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Sub-Seccion:</td>
                    <td colspan="3" id="tdlstSubSeccionArt">
                    	<select id="lstSubSeccionArt" name="lstSubSeccionArt">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
				</tr>
				</table>
			</form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table style="display:none;" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                	<td valign="top" width="60%">
                        <table border="0" width="100%">
                        <tr>
                            <td align="left" class="tituloArea" colspan="6">Otros Datos</td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="17%">Stock M&aacute;ximo:</td>
                            <td align="left" width="19%"><input type="text" id="txtStockMaximo" name="txtStockMaximo" readonly="readonly" size="12"/></td>
                            <td align="right" class="tituloCampo" width="14%">Saldo:</td>
                            <td align="left" width="18%"><input type="text" id="txtSaldo" name="txtSaldo" readonly="readonly" size="12"/></td>
                            <td align="right" class="tituloCampo" width="14%">En Espera:</td>
                            <td align="left" width="18%"><input type="text" id="txtEspera" name="txtEspera" readonly="readonly" size="12"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Stock Minimo:</td>
                            <td align="left"><input type="text" id="txtStockMinimo" name="txtStockMinimo" readonly="readonly" size="12"/></td>
                            <td align="right" class="tituloCampo">Reservado:</td>
                            <td align="left"><input type="text" id="txtReservado" name="txtReservado" readonly="readonly" size="12"/></td>
                            <td align="right" class="tituloCampo">Pedido:</td>
                            <td align="left"><input type="text" id="txtPedido" name="txtPedido" readonly="readonly" size="12"/></td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td align="right" class="tituloCampo">Disponible:</td>
                            <td align="left"><input type="text" id="txtDisponible" name="txtDisponible" readonly="readonly" size="12"/></td>
                            <td align="right" class="tituloCampo">Futura:</td>
                            <td align="left"><input type="text" id="txtFutura" name="txtFutura" readonly="readonly" size="12"/></td>
                        </tr>
                        </table>

					</td>
					<!--
                	<td valign="top" width="40%">
                        <table border="0" width="100%">
                        <tr>
                            <td align="left" class="tituloArea" colspan="6">Precios</td>
                        </tr>
                        <tr>
                        	<td>

                            	<table border="0" width="100%">
                                <tr class="tituloColumna">
                                	<td width="60%">Descripci&oacute;n</td>
                                    <td width="40%">Precio</td>
                                </tr>
                                <tr id="trItmPieArtPrec"></tr>
                                </table>
							</td>
						</tr>
                        </table>
					</td>
					-->
				</tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td><hr></td>
        </tr>
        <tr>
        	<td align="left">
                <div id="myTabs" class="mootabs">
                    <ul class="mootabs_title">
                        <li title="Articulos Sustitutos">Articulos Sustitutos</li>
                        <li style="display:none;" title="Costos de Proveedores">Costos de Proveedores</li>
                        <li title="Articulos Alternos">Articulos Alternos</li>
                        <li title="Modelos Compatibles">Modelos Compatibles</li>
                        <li style="display:none;" title="Existencia en Almacen">Existencia en Almacen</li>
                    </ul>
                    
                    
                    <div id="Articulos Sustitutos" class="mootabs_panel">
                    <form id="frmListadoArtSus" name="frmListadoArtSus" style="margin:0">
                    	<table border="0" width="100%">
                        <tr>
                            <td class="tituloArea" colspan="3">
                            	<table cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tr>
                                	<td align="left" width="100%">Articulos Sustitutos</td>
                                	<td>
                                    	<button type="button" id="btnInsertarArt" name="btnInsertarArt" 
                                        onclick="xajax_formArticuloSustituto(xajax.getFormValues('frmArticulo'));" title="Agregar Art&iacute;culo Sustituto">
                                        <img src="../img/iconos/ico_agregar.gif" align="absmiddle"/>
                                        </button>
                                    </td>
                                    <td>
                                    	<button type="button" id="btnEliminarArt" name="btnEliminarArt" 
                                        onclick="xajax_eliminarArticuloSustituto(xajax.getFormValues('frmListadoArtSus'));" title="Eliminar Art&iacute;culo Sustituto">
                                        <img src="../img/iconos/ico_quitar.gif" align="absmiddle"/>
                                        </button>
                                    </td>
                                </tr>
                                </table>
							</td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0"  cellpadding="2" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td></td>
                                    <td width="15%">C&oacute;digo Art&iacute;culo</td>
                                    <td width="75%">Descripci&oacute;n</td>

                                    <td width="10%">Existencia</td>
                                    <td></td>
                                </tr>
                                <tr id="trItmPieArtSus"></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                        <input type="hidden" id="hddObjArtSus" name="hddObjArtSus" />
					</form>
                    </div>

                    <div id="Costos de Proveedores" class="mootabs_panel" style="display:none;">
                        <table border="0" width="100%">
                        <tr>
                            <td align="left" class="tituloArea" colspan="3">Costos de Proveedores</td>
                        </tr>
                        <tr>
                            <td id="tdListadoCostosProveedores">
                                <table border="1" class="tabla" cellpadding="2" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td>Proveedor</td>
                                    <td>Costo</td>
                                    <td>Fecha</td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </div>
                    
                    <div id="Articulos Alternos" class="mootabs_panel">
                    <form id="frmListadoArtAlt" name="frmListadoArtAlt" style="margin:0">
                        <table border="0" width="100%">
                        <tr>
                            <td class="tituloArea">
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td align="left" width="100%">Articulos Alternos</td>
                                	<td>
                                    	<button type="button" id="btnInsertarArtAlt" name="btnInsertarArtAlt" onclick="xajax_formArticuloAlterno(xajax.getFormValues('frmArticulo'));" title="Agregar Art&iacute;culo Alterno"><img src="../img/iconos/ico_agregar.gif" align="absmiddle"/></button>
									</td>
                                    <td>
                                    	<button type="button" id="btnEliminarArtAlt" name="btnEliminarArtAlt" onclick="xajax_eliminarArticuloAlterno(xajax.getFormValues('frmListadoArtAlt'));" title="Eliminar Art&iacute;culo Alterno"><img src="../img/iconos/ico_quitar.gif" align="absmiddle"/></button>
                                    </td>
                                </tr>
                                </table>
							</td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0" class="tabla" cellpadding="2" width="100%">
                                <tr align="center" class="tituloColumna">
                                	<td></td>
                                    <td width="15%">C&oacute;digo Art&iacute;culo</td>
                                    <td width="75%">Descripci&oacute;n</td>

                                    <td width="10%">Existencia</td>
                                    <td></td>
                                </tr>
                                <tr id="trItmPieArtAlt"></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                        <input type="hidden" id="hddObjArtAlt" name="hddObjArtAlt" />
					</form>
                    </div>
                    
                    <div id="Modelos Compatibles" class="mootabs_panel">
                    <form id="frmListadoModComp" name="frmListadoModComp" style="margin:0">
                        <table border="0" width="100%">
                        <tr>
                            <td class="tituloArea">
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td align="left" width="100%">Modelos Compatibles</td>
                                	<td>
                                    	<button type="button" id="btnInsertarModComp" name="btnInsertarModComp" onclick="
                                        	xajax_buscarModeloCompatible(xajax.getFormValues('frmModComp'), xajax.getFormValues('frmListadoModComp'), 'inicio');"
										title="Agregar Modelo Compatible"><img src="../img/iconos/ico_agregar.gif" align="absmiddle"/></button>
                                    </td>
                                    <td>
                                    	<button type="button" id="btnEliminarModComp" name="btnEliminarModComp" onclick="xajax_eliminarModeloCompatible(xajax.getFormValues('frmListadoModComp'));" title="Eliminar Modelo Compatible"><img src="../img/iconos/ico_quitar.gif" align="absmiddle"/></button>
                                    </td>
                                </tr>
                                </table>
							</td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0" class="tabla" cellpadding="2" width="100%">
                                <tr align="center" class="tituloColumna">
		                        	<td></td>
                                    <td width="25%">Unidad B&aacute;sica</td>
                                    <td width="25%">Marca</td>
                                    <td width="25%">Modelo</td>
                                    <td width="25%">Version</td>
                                </tr>
                                <tr id="trItmPieModComp"></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                        <input type="hidden" id="hddObjModComp" name="hddObjModComp" />
					</form>
                    </div>
                    
                    <div id="Existencia en Almacen" class="mootabs_panel" style="display:none;">
                    	<table border="0" width="100%">
                        <tr>
                            <td class="tituloArea" colspan="3">
                                <table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left" width="100%">Almacenes</td>
                                    <td><!--<input type="button" onclick="xajax_formAlmacen();" value="+"/>--></td>
                                    <td><!--<input type="button" onclick="" value="-"/>--></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td id="tdListadoAlmacenes">
                                <table border="1" class="tabla" cellpadding="2" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td>Empresa</td>
                                    <td>Almacen</td>
                                    <td>Calle</td>
                                    <td>Estante</td>
                                    <td>Tramo</td>
                                    <td>Casilla</td>
                                    <td>Existencia</td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </div>
                </div>

                
                <script type="text/javascript" charset="utf-8">
					window.addEvent('domready', init);
					function init() {
						myTabs1 = new mootabs('myTabs', {
							height: '300px',
							width: '99%',
							changeTransition: 'none',
							mouseOverClass: 'over'
						});
					}

                </script>
            </td>
        </tr>
        <tr>
        	<td align="right">
                <hr>
            <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormArt();">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr>
                </table>
            </button>
            <button type="button" id="btnCancelar" name="btnCancelar" onclick="top.history.back();" class="close">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr>
                </table>
            </button>
                
                <input type="hidden" id="hddTipoVista" name="hddTipoVista" value="<?php echo $_GET['vw']; ?>">
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
    
    <div id="divFlotanteContenido" style="display:none"></div>
    
	<form id="frmModComp" name="frmModComp" style="margin:0">
    <table border="0" id="tblModComp" style="display:none" width="920px">
    <tr>
		<td>
			<table border="0">
			<tr align="left">
				<td align="right" class="tituloCampo" width="120">Buscar:</td>
				<td><input type="text" id="txtTexto" name="txtTexto" onkeyup="$('btnBuscar').click();"/></td>
                <td>
                <button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarModeloCompatible(xajax.getFormValues('frmModComp'), xajax.getFormValues('frmListadoModComp'));">
                    <table align="center" cellpadding="0" cellspacing="0">
                        <tr><td>&nbsp;</td><td></td><td>&nbsp;</td><td>Buscar</td></tr>
                    </table>
                </button> 
                <button type="button" id="btnVerTdo" name="btnVerTdo" onclick="$('txtTexto').value = ''; $('btnBuscar').click();" class="close">
                    <table align="center" cellpadding="0" cellspacing="0">
                        <tr><td>&nbsp;</td><td></td><td>&nbsp;</td><td>Ver Todo</td></tr>
                    </table>
                </button>
                	<!--<input type="button" id="" onclick="" value="Buscar">
                    <input type="button" onclick="$('txtTexto').value = ''; $('btnBuscar').click();" value="Ver Todo"/>-->
				</td>
			</tr>
			</table>
		</td>
	</tr>
    <tr>
		<td>
        <div id="divListaModeloCompatible" style="overflow:scroll; max-height:400px"></div>
        </td>
	</tr>
	<tr>
		<td align="right">
			<hr>
            <input type="hidden" id="hddObjModCompPreseleccionado" name="hddObjModCompPreseleccionado" readonly="readonly"/>
                <button type="button" id="btnAceptar" name="btnAceptar" onclick="validarFormModCompatible();">
                    <table align="center" cellpadding="0" cellspacing="0">
                        <tr><td>&nbsp;</td><td><img src="../img/iconos/ico_aceptar.gif"></td><td>&nbsp;</td><td>Aceptar</td></tr>
                    </table>
                </button> 
                <button type="button" id="btnVerTdo" name="btnVerTdo" onclick="$('divFlotante').style.display='none';" class="close">
                    <table align="center" cellpadding="0" cellspacing="0">
                        <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr>
                    </table>
                </button>
            
			<!--/*<input type="button" onclick="validarFormModCompatible();" value="Aceptar">
			<input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">*/-->
		</td>
	</tr>
	</table>
    </form>
    
    <table border="0" id="tblAlmacen" style="display:none" width="800px">
    <tr>
    	<td>
        <form id="frmAlmacen" name="frmAlmacen" style="margin:0">
        	<table border="0" width="100%">
            <tr>
                <td valign="top" width="32%">
                    <table width="100%">
                    <tr style="display:none">
                        <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                        <td id="tdlstEmpresa" width="80%">
                            <select id="lstEmpresa" name="lstEmpresa">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Almacen:</td>
                        <td id="tdlstAlmacenAct">
                            <select id="lstAlmacenAct" name="lstAlmacenAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Calle:</td>
                        <td id="tdlstCalleAct">
                            <select id="lstCalleAct" name="lstCalleAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estante:</td>
                        <td id="tdlstEstanteAct">
                            <select id="lstEstanteAct" name="lstEstanteAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tramo:</td>
                        <td id="tdlstTramoAct">
                            <select id="lstTramoAct" name="lstTramoAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Casilla:</td>
                        <td id="tdlstCasillaAct">
                            <select id="lstCasillaAct" name="lstCasillaAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                    </tr>
                    </table>
				</td>
				<td valign="top" width="68%">
                    <fieldset>
                        <legend class="legend">Articulos Existentes en el Almacen</legend>
                        <div id="divArticulosAlmacen" style="height:250px; overflow:auto;">
                        <table width="96%">
                        <tr class="tituloColumna">
                            <td>Código</td>
                            <td>Descripción</td>
                            <td>Existencia</td>
                        </tr>
                        </table>
                        </div>
                    </fieldset>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" onclick="validarFormAlmacen();" value="Guardar">
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblCuentaContable" width="800px">
    <tr>
        <td id="tdCuentaContable"></td>
    </tr>
    <tr>
        <td align="right">
            <hr>
                <button type="button" id="" name="" onclick="$('divFlotante').style.display='none';">
                    <table align="center" cellpadding="0" cellspacing="0">
                        <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Cerrar</td></tr>
                    </table>
                </button>
        </td>
    </tr>
    </table>
</div>

<script>
<?php if (isset($_GET['id'])) { ?>
	<?php if (isset($_GET['vw']) && $_GET['vw'] == "v") { ?>
		xajax_cargarArticulo('<?php echo $_GET['id']; ?>', '<?php echo $_GET['ide']; ?>', 'true');
	<?php } else if (!(isset($_GET['vw']))) { ?>
		<?php
		if(!(validaAcceso("ga_articulo_list","editar"))) {
			echo "alert('Acceso Denegado');";
			echo "top.history.back();";
		} ?>
		xajax_cargarArticulo('<?php echo $_GET['id']; ?>', '<?php echo $_GET['ide']; ?>');
	<?php } ?>
<?php } else { ?>
	<?php
	if(!(validaAcceso("ga_articulo_list","insertar"))) {
		echo "alert('Acceso Denegado');";
		echo "top.history.back();";
	} ?>
	xajax_cargaLstMarca();
	xajax_cargaLstTipoArticulo();
	xajax_cargaLstTipoUnidad();
	xajax_cargaLstSeccion();
		
	xajax_objetoCodigoDinamicoCompras('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
<?php } ?>
</script>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>