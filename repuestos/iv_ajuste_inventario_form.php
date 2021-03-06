<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_ajuste_inventario_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_ajuste_inventario_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);
	
/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Ajuste de Inventario</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css" />
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <!--<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
    <script>
	function formListaCliente(nomObjeto) {
		document.forms['frmBuscarLista'].reset();
		byId('btnBuscarLista').onclick = function () {
			xajax_buscarCliente(xajax.getFormValues('frmBuscarLista'), xajax.getFormValues('frmDcto'));
		}
		
		byId('btnBuscarLista').click();
		
		byId('tblImportarPedido').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblLista').style.display = '';
		byId('tblArticulo').style.display = 'none';
		
		openImg(nomObjeto);
		byId('tdFlotanteTitulo').innerHTML = "Clientes";
		byId('tblLista').width = 760;
		
		byId('txtCriterioBuscarLista').focus();
		byId('txtCriterioBuscarLista').select();
	}
	
	function formListaEmpresa(nomObjeto, objDestino, nomVentana) {
		openImg(nomObjeto);
		
		document.forms['frmBuscarEmpresa'].reset();
		
		byId('hddObjDestino').value = objDestino;
		byId('hddNomVentana').value = nomVentana;
		
		byId('btnBuscarEmpresa').click();
		
		byId('tblImportarPedido').style.display = 'none';
		byId('tblListaEmpresa').style.display = '';
		byId('tblLista').style.display = 'none';
		byId('tblArticulo').style.display = 'none';
		
		byId('tdFlotanteTitulo').innerHTML = "Empresas";
		
		byId('txtCriterioBuscarEmpresa').focus();
		byId('txtCriterioBuscarEmpresa').select();
	}
	
	function validarAgregarArticulo(nomObjeto, hddFrm) {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('lstTipoVale','t','lista') == true
		&& validarCampo('lstTipoMovimiento','t','lista') == true) {
			if (hddFrm == 'tblArticulo') {
				document.forms['frmBuscarArticulo'].reset();
				document.forms['frmDatosArticulo'].reset();
				byId('txtDescripcionArt').innerHTML = '';
				
				byId('txtCodigoArt').className = 'inputInicial';
				byId('txtCantidadArt').className = 'inputHabilitado';
				byId('txtPrecioArt').className = 'inputInicial';
				byId('lstCasillaArt').className = 'inputHabilitado';
				
				cerrarVentana = false;
				
				byId('divListaArticulo').innerHTML = '';
				
				byId('tdFlotanteTitulo').innerHTML = 'Artículos';
				
				byId('tblImportarPedido').style.display = 'none';
				byId('tblListaEmpresa').style.display = 'none';
				byId('tblLista').style.display = 'none';
				byId('tblArticulo').style.display = '';
				
				openImg(nomObjeto);
				byId('tdFlotanteTitulo').innerHTML = 'Artículos';
				
				byId('txtCodigoArticulo0').focus();
				byId('txtCodigoArticulo0').select();
			} else if (hddFrm == 'tblImportarPedido') {
				xajax_formImportar(nomObjeto.id);
			}
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('lstTipoVale','t','lista');
			validarCampo('lstTipoMovimiento','t','lista');
			
			alert('Los campos señalados en rojo son requeridos');
			return false; 
		}
	}
	
	function validarFormArticulo() {
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadArt','t','cantidad') == true
		&& validarCampo('txtPrecioArt','t','monto') == true
		&& validarCampo('lstCasillaArt','t','lista') == true) {
			xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadArt','t','cantidad');
			validarCampo('txtPrecioArt','t','monto');
			validarCampo('lstCasillaArt','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormDcto() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtFecha','t','fecha') == true
		&& validarCampo('lstTipoVale','t','lista') == true
		&& validarCampo('lstTipoMovimiento','t','lista') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('txtSubTotal','t','monto') == true
		&& validarCampo('txtObservacion','t','') == true) {
			if (confirm('¿Seguro desea guardar el Vale?') == true) {
				xajax_guardarDcto(xajax.getFormValues('frmDcto'),xajax.getFormValues('frmListaArticulo'),xajax.getFormValues('frmTotalDcto'));
			}
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtFecha','t','fecha');
			validarCampo('lstTipoVale','t','lista');
			validarCampo('lstTipoMovimiento','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtSubTotal','t','monto');
			validarCampo('txtObservacion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormImportar() {
		if (validarCampo('hddUrlArchivo','t','') == true) {
			xajax_importarDcto(xajax.getFormValues('frmImportarPedido'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('hddUrlArchivo','t','');
			
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
        	<td class="tituloPaginaRepuestos">Ajuste de Inventario</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            <form id="frmDcto" name="frmDcto" style="margin:0">
                <table border="0" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td width="58%">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td>
                            <a class="modalImg" id="aListarEmpresa" rel="#divFlotante" onclick="formListaEmpresa(this,'Empresa','ListaEmpresa');">
                                <button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="12%">Fecha:</td>
                    <td width="18%"><input type="text" id="txtFecha" name="txtFecha" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Empleado:</td>
                    <td>
                        <input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/>
                        <input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="25"/>
					</td>
				</tr>
                <tr>
                	<td colspan="4">
                    	<table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td valign="top" width="70%">
                            <fieldset><legend class="legend">Datos Personales</legend>
                                <table border="0" width="100%">
                                <tr>
                                    <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                    <td width="85%">
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdCliente" name="txtIdCliente" onblur="xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'true', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarCliente" rel="#divFlotante" onclick="formListaCliente(this);">
                                                <button type="button" id="btnListarCliente" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                        </tr>
                                        </table>
                                    </td>
								</tr>
                                </table>
                            </fieldset>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Datos del Vale</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%">Nro. Vale:</td>
                                    <td width="60%">
                                        <input type="hidden" id="txtIdVale" name="txtIdVale" readonly="readonly"/>
                                        <input type="text" id="txtNumeroVale" name="txtNumeroVale" readonly="readonly" size="20" style="text-align:center;"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Vale</td>
                                    <td>
                                        <select id="lstTipoVale" name="lstTipoVale" onchange="xajax_asignarTipoVale(this.value);">
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="1">Entrada / Salida</option>
                                            <option value="3">Nota de Crédito de CxC</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr align="left" id="trNroDcto" style="display:none">
                                	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Nota Crédito:</td>
                                	<td>
                                    	<table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>
                                            	<input type="hidden" id="hddIdDcto" name="hddIdDcto" readonly="readonly"/>
                                            	<input type="text" id="txtNroDcto" name="txtNroDcto" readonly="readonly" size="20" style="text-align:center;"/>
											</td>
                                            <td>
                                            <a class="modalImg" id="aListarDcto" rel="#divFlotante" onclick="xajax_formNotaCredito(this.id);">
                                                <button type="button" id="btnListarDcto" name="btnListarDcto" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="left">
                                	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                                    <td>
                                        <select id="lstTipoMovimiento" name="lstTipoMovimiento">
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="2">2.- ENTRADA</option>
                                            <option value="4">4.- SALIDA</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                                    <td id="tdlstClaveMovimiento">
                                        <select id="lstClaveMovimiento" name="lstClaveMovimiento">
                                            <option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                </tr>
                                </table>
							</fieldset>
                            </td>
						</tr>
                        </table>
                    </td>
                </tr>
                </table>
            </form>
			</td>
		</tr>
        <tr>
            <td>
            	<table align="left">
                <tr>
                	<td>
                    <a class="modalImg" id="aAgregarArticulo" rel="#divFlotante" onclick="validarAgregarArticulo(this, 'tblArticulo');">
                        <button type="button" title="Agregar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                    </a>
                    <button type="button" id="btnQuitarArticulo" name="btnQuitarArticulo" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));" title="Eliminar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    <a class="modalImg" id="aImportar" rel="#divFlotante" onclick="validarAgregarArticulo(this, 'tblImportarPedido');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
					</td>
				</tr>
                </table>
                
                <table align="right" cellpadding="0" cellspacing="0" class="divMsjInfo2" width="400">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td class="trResaltar6" style="border:1px solid #000000">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>Artículo Sin Ubicación</td>
                            <td>&nbsp;</td>
                            <td class="trResaltar7" style="border:1px solid #000000">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>Artículo Con Multiple Ubicación</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaArticulo" name="frmListaArticulo" onsubmit="return false;" style="margin:0">
                <table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                    <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,1);"/></td>
                    <td width="4%">Nro.</td>
                    <td width="10%">Ubic.</td>
                    <td width="14%">Código</td>
                    <td width="52%">Descripción</td>
                    <td width="4%">Cant.</td>
                    <td width="8%">Precio / Costo<br/>Unit.</td>
                    <td width="8%">Total</td>
                </tr>
                <tr id="trItmPie">
                	<td colspan="4"></td>
                	<td class="trResaltarTotal" title="Cantidad Total de Articulos"><input type="text" id="txtCantTotalItem" name="txtCantTotalItem" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td align="right">
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                <table border="0" width="100%">
                <tr>
                    <td valign="top" width="50%">
                    	<table width="100%">
                        <tr align="left">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea id="txtObservacion" name="txtObservacion" class="inputHabilitado" rows="3" style="width:99%"></textarea></td>
                        </tr>
                        </table>
                    </td>
                    <td valign="top" width="50%">
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="36%">Subtotal:</td>
                            <td style="border-top:1px solid;" width="24%"></td>
                            <td style="border-top:1px solid;" width="13%"></td>
                            <td style="border-top:1px solid;" id="tdSubTotalMoneda" width="5%"></td>
                            <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
			</form>
            </td>
        </tr>
        <tr>
        	<td align="right"><hr>
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('iv_ajuste_inventario_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    
<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarPedido" name="frmImportarPedido" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarPedido" width="960">
    <tr align="left">
    	<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Archivo de Excel:</td>
        <td width="85%">
        	<input type="file" id="fleUrlArchivo" name="fleUrlArchivo" class="inputHabilitado" onchange="javascript:submit();" />
			<iframe name="iframeUpload" style="display:none"></iframe>
            <input type="hidden" id="hddUrlArchivo" name="hddUrlArchivo" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table width="100%">
                    <tr>
                        <td colspan="5">El formato del archivo excel a importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr align="center" class="tituloColumna">
                        <td>Código</td>
                        <td>Ped.</td>
                        <td><?php echo $spanPrecioUnitario; ?></td>
                    </tr>
                    <tr>
                        <td colspan="5">La columna "<?php echo $spanPrecioUnitario; ?>" solo se toma en cuenta cuando el tipo de vale es "Nota de Crédito de CxC"</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
            <div id="divMsjImportar"></div>
        </td>
    </tr>
    <tr>
    	<td align="right" colspan="2"><hr>
        	<button type="submit" id="btnGuardarImportarPedido" name="btnGuardarImportarPedido" onclick="validarFormImportar();">Aceptar</button>
        	<button type="button" id="btnCancelarImportarPedido" name="btnCancelarImportarPedido" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpresa" name="frmListaEmpresa" onsubmit="return false;" style="margin:0">
            <div id="divListaEmpresa" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
	
    <table border="0" id="tblLista" style="display:none" width="960">
    <tr>
    	<td>
        	<form id="frmBuscarLista" name="frmBuscarLista" onsubmit="return false;" style="margin:0">
            	<table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                	<td><input type="text" id="txtCriterioBuscarLista" name="txtCriterioBuscarLista" onkeyup="byId('btnBuscarLista').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscarLista" name="btnBuscarLista">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscarLista'].reset(); byId('btnBuscarLista').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divLista" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblArticulo" style="display:none" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArticulo" name="frmBuscarArticulo" onsubmit="return false;" style="margin:0">
        	<table align="right">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="120">Buscar por:</td>
                <td>
                	<select id="lstBuscarArticulo" name="lstBuscarArticulo" class="inputHabilitado" style="width:150px">
                    	<option value="1">Marca</option>
                        <option value="2">Tipo Artículo</option>
                        <option value="3">Sección</option>
                        <option value="4">Sub-Sección</option>
                        <option selected="selected" value="5">Descripción</option>
                        <option value="6">Cód. Barra</option>
                        <option value="7">Cód. Artículo Prov.</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarArticulo" name="txtCriterioBuscarArticulo" class="inputHabilitado"/></td>
                <td align="right" class="tituloCampo" width="120">Código:</td>
                <td id="tdCodigoArt"></td>
                <td align="right" nowrap="nowrap">
                	<button type="submit" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarArticulo'].reset(); byId('btnBuscarArticulo').click();">Limpiar</button>
				</td>
			</tr>
			</table>
		</form>
		</td>
    </tr>
    <tr>
    	<td><div id="divListaArticulo"></div></td>
    </tr>
    <tr>
    	<td>
        <form id="frmDatosArticulo" name="frmDatosArticulo" onsubmit="return false;" style="margin:0">
        	<input type="hidden" id="hddNumeroArt" name="hddNumeroArt" readonly="readonly"/>
        <fieldset>
        	<table border="0" width="100%">
            <tr>
                <td width="10%"></td>
                <td width="30%"></td>
                <td width="38%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
                <td>
                	<input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/>
                    <input type="hidden" id="hddIdArt" name="hddIdArt" readonly="readonly"/>
				</td>
                <td rowspan="3" valign="top">
                	<textarea id="txtDescripcionArt" name="txtDescripcionArt" cols="50" rows="3" readonly="readonly"></textarea>
				</td>
                <td align="right" class="tituloCampo">Ult. Compra:</td>
                <td><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Sección:</td>
                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" readonly="readonly" size="38"/></td>
                <td align="right" class="tituloCampo">Ult. Venta:</td>
                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Tipo Artículo:</td>
                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"/></td>
                <td align="right" class="tituloCampo">Disponible:</td>
                <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right;"/></td>
            </tr>
            <tr align="left">
            	<td class="divMsjAlerta" colspan="5" id="tdMsjArticulo" style="display:none"></td>
            </tr>
			</table>
        </fieldset>
            
            <table border="0" width="100%">
            <tr>
            	<td width="10%"></td>
                <td width="28%"></td>
                <td width="10%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
                <td width="30%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                		<td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="12" onkeypress="return validarSoloNumeros(event);" size="10" style="text-align:right;"/></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
					</tr>
                    </table>
				</td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><span id="spnCostoArt">Costo:</span></td>
                <td>
                	<input type="text" id="txtPrecioArt" name="txtPrecioArt" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="10" style="text-align:right"/>
                    <input type="hidden" id="txtCostoArt" name="txtCostoArt" readonly="readonly" size="10" style="text-align:right"/>
                    </td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ubicación:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                    	<td id="tdlstCasillaArt">
                            <select id="lstCasillaArt" name="lstCasillaArt">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
						</td>
                        <td>&nbsp;</td>
                        <td><input type="text" id="txtCantidadUbicacion" name="txtCantidadUbicacion" readonly="readonly" size="10" style="text-align:right"/></td>
					</tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td align="right" colspan="6"><hr>
                    <button type="submit" id="btnGuardarArticulo" name="btnGuardarArticulo" onclick="validarFormArticulo();">Aceptar</button>
                    <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo" class="close">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
		</td>
	</tr>
    </table>
</div>

<script>
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

xajax_nuevoDcto(xajax.getFormValues('frmTotalDcto'));

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>