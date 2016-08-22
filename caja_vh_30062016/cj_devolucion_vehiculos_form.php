<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_devolucion_vehiculos_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_devolucion_vehiculos_form.php");
include("../controladores/ac_pg_calcular_comision.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Devolucion de Venta</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCaja.css" />
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
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
	function validarFrmDcto() {
		if (validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtNumeroControlNotaCredito','t','numeroControl') == true
		&& validarCampo('txtIdPedido','t','') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('txtTotalOrden','t','monto') == true) {
			if (confirm("¿Seguro desea generar la Nota de Crédito?") == true) {
				xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
			}
		} else {
			validarCampo('txtIdCliente','t','');
			validarCampo('txtNumeroControlNotaCredito','t','numeroControl');
			validarCampo('txtIdPedido','t','');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtTotalOrden','t','monto');
			
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		}
	}
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cj.php"); ?></div>
    	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
            <td class="tituloPaginaCaja">Devolucion de Venta</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
            <form id="frmDcto" name="frmDcto" style="margin:0">
				<table border="0" width="100%">
				<tr>
					<td colspan="2">
						<table border="0" width="100%">
						<tr align="left">
							<td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
							<td width="58%">
								<table cellpadding="0" cellspacing="0">
								<tr>
									<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
									<td>&nbsp;</td>
									<td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
								</tr>
								</table>
							</td>
							<td align="right" class="tituloCampo" width="12%">Nro. Nota Crédito:</td>
							<td width="18%">
								<input type="hidden" id="txtIdNotaCredito" name="txtIdNotaCredito" readonly="readonly" size="20"/>
                                <input type="hidden" id="hddIdMoneda" name="hddIdMoneda" readonly="readonly" size="20"/>
								<input type="text" id="txtNumeroNotaCredito" name="txtNumeroNotaCredito" readonly="readonly" size="20" style="text-align:center"/>
							</td>
						</tr>
						<tr>
							<td colspan="2"></td>
							<td align="right" class="tituloCampo">Fecha:</td>
							<td>
								<input type="text" id="txtFechaNotaCredito" name="txtFechaNotaCredito" readonly="readonly" size="10" style="text-align:center"/>
							</td>
						</tr>
						<tr>
							<td colspan="2"></td>
							<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
							<td>
								<div style="float:left">
									<input type="text" id="txtNumeroControlNotaCredito" name="txtNumeroControlNotaCredito" size="16" style="color:#F00; font-weight:bold; text-align:center;"/>
								</div>
								<div style="float:left">
									<img src="../img/iconos/information.png" title="Formato Ej.: 00-000000"/>
								</div>
							</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td valign="top" width="70%">
                    <fieldset><legend class="legend">Cliente</legend>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                            <td width="46%">
                            	<table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td></td>
                                    <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                </tr>
                                <tr align="center">
                                    <td id="tdMsjCliente" colspan="3"></td>
                                </tr>
                                </table>
                                <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto"/>
                            </td>
                            <td align="right" class="tituloCampo" width="16%"><?php echo $spanClienteCxC; ?>:</td>
                            <td width="22%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Dirección:</td>
                            <td rowspan="3"><textarea id="txtDireccionCliente" name="txtDireccionCliente" cols="55" readonly="readonly" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Teléfono:</td>
                            <td><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Otro Teléfono:</td>
                            <td><input type="text" id="txtOtroTelefonoCliente" name="txtOtroTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Días Crédito:</td>
                            <td><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                            <td width="16%">
                            	<select id="lstTipoClave" name="lstTipoClave" onchange="xajax_cargaLstClaveMovimiento(this.value)">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">1.- COMPRA</option>
                                    <option value="2">2.- ENTRADA</option>
                                    <option value="3">3.- VENTA</option>
                                    <option value="4">4.- SALIDA</option>
                                </select>
							</td>
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                            <td id="tdlstClaveMovimiento" width="28%"></td>
                            <td width="12%"></td>
                            <td width="20%"></td>
                        </tr>
                        </table>
					</td>
					<td valign="top" width="30%">
					<fieldset><legend class="legend">Datos de la Factura</legend>
						<table border="0" width="100%">
						<tr align="left">
							<td align="right" class="tituloCampo" width="40%">Nro. Factura:</td>
							<td width="60%">
								<input type="hidden" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="20"/>
								<input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" size="18" style="text-align:center"/>
							</td>
						</tr>
						<tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Pedido:</td>
                            <td><input type="text" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="18" style="text-align:center"></td>
                        </tr>
						<tr align="left">
                            <td align="right" class="tituloCampo">Nro. Presupuesto:</td>
                            <td><input type="text" id="txtIdPresupuesto" name="txtIdPresupuesto" readonly="readonly" size="18" style="text-align:center"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Control:</td>
                            <td><input type="text" id="txtNumeroControlFactura" name="txtNumeroControlFactura" readonly="readonly" size="16" style="color:#F00; font-weight:bold; text-align:center;"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fecha Emisión:</td>
                            <td><input type="text" id="txtFechaFactura" name="txtFechaFactura" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fecha Venc.:</td>
                            <td><input type="text" id="txtFechaVencimientoFactura" name="txtFechaVencimientoFactura" readonly="readonly" size="10" style="text-align:center"/></td>
                                </tr>
                        <tr align="left">
							<td align="right" class="tituloCampo">Vendedor:</td>
							<td>
                                <input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/>
                                <input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="20"/>
                            </td>
						</tr>
						<tr align="left">
							<td align="right" class="tituloCampo">Tipo:</td>
							<td><input type="text" id="txtTipoClaveFactura" name="txtTipoClaveFactura" readonly="readonly" size="14"/></td>
						</tr>
						<tr align="left">
							<td align="right" class="tituloCampo">Clave:</td>
							<td>
								<input type="hidden" id="hddIdClaveMovimientoFactura" name="hddIdClaveMovimientoFactura" readonly="readonly"/>
								<input type="text" id="txtClaveMovimientoFactura" name="txtClaveMovimientoFactura" readonly="readonly" size="25"/>
							</td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Tipo de Pago:</td>
                            <td>
                                <input type="hidden" id="hddTipoPago" name="hddTipoPago" readonly="readonly"/>
                                <input type="text" id="txtTipoPago" name="txtTipoPago" class="divMsjInfo2" readonly="readonly" size="20" style="text-align:center"/>
							</td>
						</tr>
						</table>
					</fieldset>
					</td>
				</tr>
				</table>
            </form>
			</td>
		</tr>
		<tr>
			<td>
				<form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                <table border="0" width="100%">
                <tr class="tituloColumna">
					<td></td>
                	<td width="4%">Nro.</td>
					<td width="14%">Código</td>
					<td width="52%">Descripción</td>
                    <td width="12%">Precio Unit.</td>
                    <td width="6%">% Impuesto</td>
                    <td width="12%">Total</td>
				</tr>
				<tr id="trItmPie"></tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td>
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                <table border="0" width="100%">
				<tr>
                	<td valign="top" width="50%">
                    <fieldset id="fieldsetAdicionalOtro"><legend class="legend">Otros Adicionales</legend>
                    	<table border="0" width="100%">
                        <tr class="tituloColumna">
                            <td></td>
                            <td width="6%">Nro.</td>
                            <td width="0%"></td>
                            <td width="54%">Descripción</td>
                            <td width="14%">Precio Unit.</td>
                            <td width="10%">% Impuesto</td>
                            <td width="14%">Total</td>
                        </tr>
                        <tr id="trItmPieAdicionalOtro" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="6">Total Otros Adicionales:</td>
                            <td><input type="text" id="txtTotalAdicionalOtro" name="txtTotalAdicionalOtro" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
					</fieldset>
                    	
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td class="tituloCampo">Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea></td>
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
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td nowrap="nowrap"><input type="text" id="txtDescuento" name="txtDescuento" readonly="readonly" size="6" style="text-align:right"/>%</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Gastos Con Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoConIvaMoneda"></td>
                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IVA-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Gastos Sin Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoSinIvaMoneda"></td>
                            <td><input type="text" id="txtGastosSinIva" name="txtGastosSinIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr id="trNetoOrden" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalRegistroMoneda"></td>
                            <td><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr>
                            <td colspan="5" style="border-top:1px solid;"></td>
                        </tr>
                        <tr align="right">
                        	<td class="tituloCampo">Exento:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExentoMoneda"></td>
                            <td><input type="text" id="txtTotalExento" name="txtTotalExento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                        	<td class="tituloCampo">Exonerado:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExoneradoMoneda"></td>
                            <td><input type="text" id="txtTotalExonerado" name="txtTotalExonerado" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
						</table>
					</td>
				</tr>
				</table>
            </form>
			</td>
		</tr>
        <tr class="noprint">
        	<td align="right">
				<button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('cj_devolucion_vehiculos_list.php','_self');" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
		</tr>
		</table>
	</div>
    	
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
byId('txtNumeroControlNotaCredito').className = 'inputHabilitado';
byId('txtObservacion').className = 'inputHabilitado';

xajax_cargarDcto("<?php echo $_GET['id_factura']; ?>");
</script>