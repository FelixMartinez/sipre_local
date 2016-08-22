<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_surtido_taller_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_surtido_taller_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Surtido de Taller</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblSolicitud').style.display = 'none';
		
		if (verTabla == "tblSolicitud") {
			document.forms['frmSolicitud'].reset();
			document.forms['frmListaSolicitudDetalle'].reset();
			
			byId('btnAprobar').style.display = 'none';
			byId('btnDesaprobar').style.display = 'none';
			byId('btnDespachar').style.display = 'none';
			byId('btnDevolver').style.display = 'none';
			byId('btnAnular').style.display = 'none';
			
			xajax_formSolicitudRepuesto(valor, valor2);
			tituloDiv1 = 'Solicitud de Repuestos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor) {
		byId('tblAlmacen').style.display = 'none';
		
		if (verTabla == "tblAlmacen") {
			document.forms['frmAlmacen'].reset();
			
			xajax_formAlmacen(valor,xajax.getFormValues('frmListaSolicitudDetalle'));
			tituloDiv2 = 'Solicitud de Repuestos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
	}
	
	function validarFrmAlmacen() {
		if (validarCampo('lstCasillaArt','t','lista') == true) {
			xajax_asignarAlmacen(xajax.getFormValues('frmAlmacen'));
		} else {
			validarCampo('lstCasillaArt','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmSolicitud() {
		error = false;
		if (!(byId('hddCantActivas').value.length > 0)) {
			alert("No se ha realizado alguna acción");
			return false;
		}
		
		if (byId('hddIdAccionRealizada').value == 2 || byId('hddIdAccionRealizada').value == 3) {
			if (!(validarCampo('lstJefeTaller','t','lista') == true
			&& validarCampo('lstEmpleadoRecibido','t','lista') == true)) {
				validarCampo('lstJefeTaller','t','lista');
				validarCampo('lstEmpleadoRecibido','t','lista');
				
				error = true;
			}
		}
		
		if (byId('hddIdAccionRealizada').value == 4) {
			if (!(validarCampo('lstEmpleadoDevuelto','t','lista') == true)) {
				validarCampo('lstEmpleadoDevuelto','t','lista');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea Guardar la Solicitud?') == true) {
				xajax_guardarSolicitud(xajax.getFormValues('frmSolicitud'), xajax.getFormValues('frmListaSolicitudDetalle'), xajax.getFormValues('frmListaSolicitud'));
			}
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
        	<td class="tituloPaginaRepuestos">Surtido de Taller</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr class="noprint">
        	<td>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
            	<table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td id="tdlstEstadoSolicitud"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarSolicitud(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaSolicitud" name="frmListaSolicitud" style="margin:0">
            	<div id="divListaSolicitud" style="width:100%"></div>
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
                            <td><img src="../img/iconos/ico_marron.gif"></td>
                            <td>Anulada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_morado.gif"></td>
                            <td>Facturada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_gris.gif"></td>
                            <td>Devuelta</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_gris_parcial.gif"></td>
                            <td>Devuelta Parcial</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_naranja.gif"></td>
                            <td>Despachada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_naranja_parcial.gif"></td>
                            <td>Despachada Parcial</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"></td>
                            <td>Aprobada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo_parcial.gif"></td>
                            <td>Aprobada Parcial</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_azul.gif"></td>
                            <td>Abierta</td>
                        </tr>
                        </table>
                    </td>
                </tr>
				</table>
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
                            <td><img src="../img/iconos/ico_examinar.png"></td>
                            <td>Ver Detalle</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/accept.png"></td>
                            <td>Aprobar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/delete.png"></td>
                            <td>Desaprobar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_despachar.png"></td>
                            <td>Despachar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_transferir_para_almacen.gif"></td>
                            <td>Devolver</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cross.png"></td>
                            <td>Anular</td>
                            <td>&nbsp;</td>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
    <div id="tblSolicitud" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
			<form id="frmSolicitud" name="frmSolicitud" style="margin:0">
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#">Datos Solicitud</a></li>
                        <li><a href="#">Datos Orden</a></li>
                        <li><a href="#">Comprobantes</a></li>
                    </ul>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Solicitud:</td>
                            <td>
                            	<input type="hidden" id="hddIdSolicitud" name="hddIdSolicitud"/>
                                <input type="text" id="txtNumeroSolicitud" name="txtNumeroSolicitud" readonly="readonly" size="20" style="text-align:center"/>
							</td>
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td><input type="text" id="txtFechaSolicitud" name="txtFechaSolicitud" readonly="readonly" size="12" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo">Estatus:</td>
                            <td>
                                <input type="text" name="hddIdEstadoSolicitud" id="hddIdEstadoSolicitud" readonly="readonly" size="4" style="text-align:right"/>
                                <input type="text" name="txtEstadoSolicitud" id="txtEstadoSolicitud" readonly="readonly"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Cliente:</td>
                            <td colspan="3"><input type="text" id="txtCliente" name="txtCliente" readonly="readonly" size="45"/></td>
                            <td align="right" class="tituloCampo">Entrega / Despacho:</td>
                            <td>
                                <input type="hidden" id="hddIdEmpleadoEntrega" name="hddIdEmpleadoEntrega" readonly="readonly" size="4"/>
                                <input type="text" id="txtEmpleadoEntrega" name="txtEmpleadoEntrega" readonly="readonly"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="11%"><span class="textoRojoNegrita">*</span>Jefe Taller:</td>
                            <td id="tdlstJefeTaller" width="21%"></td>
                            <td align="right" class="tituloCampo" width="11%"><span class="textoRojoNegrita">*</span>Recibido:</td>
                            <td id="tdlstEmpleadoRecibido" width="21%"></td>
                            <td align="right" class="tituloCampo" width="11%"><span class="textoRojoNegrita">*</span>Devuelto:</td>
                            <td id="tdlstEmpleadoDevuelto" width="25%"></td>
                        </tr>
                        </table>
                    </div>
                    <div class="pane">
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="11%">Nro. Orden:</td>
                            <td width="21%">
                            	<input type="hidden" id="hddIdOrden" name="hddIdOrden"/>
                            	<input type="text" id="txtNumeroOrden" name="txtNumeroOrden" readonly="readonly" size="20" style="text-align:center"/>
							</td>
                            <td align="right" class="tituloCampo" width="11">Fecha:</td>
                            <td width="21%"><input type="text" name="txtFechaOrden" id="txtFechaOrden" readonly="readonly" size="12" style="text-align:center"/></td>
                            <td align="center" class="imgBorde" rowspan="5" width="36%"><img id="imgUnidad" height="80"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Tipo:</td>
                            <td><input type="text" id="txtTipoOrden" name="txtTipoOrden" readonly="readonly" size="20"/></td>
                            <td align="right" class="tituloCampo">Estatus:</td>
                            <td>
                                <input type="hidden" name="hddIdEstadoOrden" id="hddIdEstadoOrden" readonly="readonly"/>
                                <input type="text" name="txtEstadoOrden" id="txtEstadoOrden" readonly="readonly" size="20"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Motor:</td>
                            <td><input type="text" id="txtMotor" name="txtMotor" readonly="readonly" size="25"/></td>
                            <td align="right" class="tituloCampo">Chasis:</td>
                            <td><input type="text" id="txtChasis" name="txtChasis" readonly="readonly" size="20" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Placa:</td>
                            <td><input type="text" id="txtPlaca" name="txtPlaca" readonly="readonly" size="14" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </div>
                    <div id="divListaComprobantes" class="pane" style="max-height:140px; overflow:auto;">
                    </div>
                </div>
			</form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaSolicitudDetalle" name="frmListaSolicitudDetalle" style="margin:0">
            <fieldset><legend class="legend">Respuestos Solicitados</legend>
                <table width="100%">
                <tr id="trAccionRealizada">
                    <td>
                        <table>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Acción:</td>
                            <td>
                                <input type="text" name="hddIdAccionRealizada" id="hddIdAccionRealizada" readonly="readonly" size="4" style="text-align:right"/>
                                <input type="text" name="txtAccionRealizada" id="txtAccionRealizada" readonly="readonly" size="20"/>
                                
                            </td>
                            <td align="right" class="tituloCampo" width="120">Items:</td>
                            <td><input type="text" name="hddCantActivas" id="hddCantActivas" readonly="readonly" size="10" style="text-align:right"/></td>
                            <td>
                            	<button type="button" id="btnAprobar" onclick="xajax_accionBloque(xajax.getFormValues('frmListaSolicitudDetalle'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/accept.png"/></td><td>&nbsp;</td><td>Aprobar</td></tr></table></button>
                                <button type="button" id="btnDesaprobar" onclick="xajax_accionBloque(xajax.getFormValues('frmListaSolicitudDetalle'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Desaprobar</td></tr></table></button>
                                <button type="button" id="btnDespachar" onclick="xajax_accionBloque(xajax.getFormValues('frmListaSolicitudDetalle'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_aceptar_azul.png"/></td><td>&nbsp;</td><td>Despachar</td></tr></table></button>
                                <button type="button" id="btnDevolver" onclick="xajax_accionBloque(xajax.getFormValues('frmListaSolicitudDetalle'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_aceptar_f2.gif"/></td><td>&nbsp;</td><td>Devolver</td></tr></table></button>
                                <button type="button" id="btnAnular" onclick="xajax_accionBloque(xajax.getFormValues('frmListaSolicitudDetalle'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Anular</td></tr></table></button>
                                <button type="button" id="btnDeshacer" onclick="xajax_accionBloque(xajax.getFormValues('frmListaSolicitudDetalle'),true);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/return.png"/></td><td>&nbsp;</td><td>Deshacer</td></tr></table></button>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td><div id="divListaSolicitudDetalle" style="width:100%;"></div></td>
                </tr>
                <tr>
                    <td>
                        <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                            <td align="center">
                                <table>
                                <tr>
                                    <td><img src="../img/iconos/ico_rojo.gif"/></td><td>No Despachado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_marron.gif"/></td><td>Anulado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_gris.gif"/></td><td>Devuelto</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_morado.gif"/></td><td>Facturado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_azul.gif"/></td><td>Despachado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_verde.gif"/></td><td>Aprobado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_amarillo.gif"/></td><td>Solicitado</td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
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
                                    <td><img src="../img/iconos/ico_cambio.png"/></td><td>Cambiar Ubicación</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/accept.png"/></td><td>Aprobar</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/delete.png"/></td><td>Desaprobar</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_aceptar_azul.png"/></td><td>Despachar</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_aceptar_f2.gif"/></td><td>Devolver</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/cross.png"/></td><td>Anular</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/return.png"/></td><td>Deshacer</td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </fieldset>
            </form>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnGuardarSolicitud" name="btnGuardarSolicitud" onclick="validarFrmSolicitud();">Guardar</button>
                <button type="button" id="btnCancelarSolicitud" name="btnCancelarSolicitud" class="close" onclick="xajax_desbloquearSolicitud(xajax.getFormValues('frmSolicitud'), xajax.getFormValues('frmListaSolicitud'));">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
	
<form id="frmAlmacen" name="frmAlmacen" onsubmit="return false;" style="margin:0">
	<input type="hidden" id="hddNroItm" name="hddNroItm" readonly="readonly"/>
    <table border="0" id="tblAlmacen" width="560">
    <tr>
        <td valign="top">
            <table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="25%">Código:</td>
                <td width="75%">
                	<input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
                	<input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/>
				</td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ubicación:</td>
                <td id="tdlstCasillaArt">
                    <select id="lstCasillaArt" name="lstCasillaArt">
                        <option>[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Disponibilidad:</td>
                <td>
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                		<td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right;"/></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
					</tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarAlmacen" name="btnGuardarAlmacen" onclick="validarFrmAlmacen();">Guardar</button>
            <button type="button" id="btnCancelarAlmacen" name="btnCancelarAlmacen" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaDesde").maskInput("99-99-9999",{placeholder:" "});
	   $("#txtFechaHasta").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"%d-%m-%Y"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"%d-%m-%Y"
	});
};

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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEstatusSolicitud();
xajax_listaSolicitud(0, 'id_solicitud', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>