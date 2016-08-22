<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_depositos_form"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_depositos_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Caja de Repuestos y Servicios - Generar/Depositar Planilla</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>

	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
	
	<script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
	<script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
	
	<script language="javascript">
	function confirmarEliminarPago(posItem){
		if (confirm("¿Seguro que desea eliminar el pago seleccionado de la Planilla de Deposito?"))
			xajax_eliminarPago(posItem);
	}
	
	function validarPlanilla(){
		if (validarCampo('txtNumeroPlanilla','t','') == true
		 && validarCampo('selBanco','t','lista') == true
		 && validarCampo('selNumeroCuenta','t','lista') == true){
		 	if (confirm("Seguro que desea guardar esta Planilla de Deposito?"))
			 	xajax_depositarPlanilla(xajax.getFormValues('frmPlanilla'))
		 } else {
			validarCampo('txtNumeroPlanilla','t','');
			validarCampo('selBanco','t','lista');
			validarCampo('selNumeroCuenta','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
		}
	}
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr class="solo_print">
			<td align="left" id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
			<td class="tituloPaginaCajaRS">Generar / Depositar Planilla</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmPagos" name="frmPagos" style="margin:0">
				<table border="0" width="100%">
				<tr>
					<td>
						<fieldset><legend class="legend">Pagos a Depositar</legend>
						<table border="0" width="100%">
							<tr>
								<td align="right" class="tituloCampo" width="120">Forma de Pago:</td>
								<td align="left">
									<select id="selTipoPago" name="selTipoPago">
										<option value="0">[ Seleccione ]</option>
										<option value="1">Efectivo</option>
										<option value="2">Cheques</option>
										<option value="3">Cash Back</option>
									</select>
								</td>
								<td align="right" colspan="2">
									<button type="button" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmPagos'));">Buscar</button>
									<button type="button" onclick="xajax_listaPagoDepositar(0,'','','');">Limpiar</button>
								</td>
							</tr>
							<tr>
								<td id="tdListado" colspan="6"></td>
							</tr>
							<tr align="center">
								<td align="right" colspan="8"><hr>
									<button type="button" id="btnCargarPlanilla" name="btnCargarPlanilla" class="puntero" style="display:none" onclick="xajax_validarCargarAPlanilla(xajax.getFormValues('frmPagos'),xajax.getFormValues('frmPlanilla'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_put.png"/></td><td>&nbsp;</td><td>Cargar a Planilla</td></tr></table></button>
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
		<tr id="trPlanillaDeposito" style="display:none">
			<td>
			<form id="frmPlanilla" name="frmPlanilla">
				<fieldset><legend class="legend">Planilla de Depósito</legend>
				<table border="0" cellpadding="2" width="100%">
					<tr align="left">
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Banco:</td>
						<td align="left" id="tdSelBanco">
							<select id="selBanco" name="selBanco" class="inputHabilitado">
								<option value="">Seleccione</option>
							</select>
						</td>
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nro. Cuenta:</td>
						<td align="left" id="tdSelNumeroCuenta">
							<select id="selNumeroCuenta" name="selNumeroCuenta" style="width:250px" class="inputHabilitado">
								<option value="">Seleccione</option>
							</select>
						</td>
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nro. Depósito:</td>
						<td><input type="text" id="txtNumeroPlanilla" name="txtNumeroPlanilla" class="inputHabilitado"/></td>
					</tr>
					<tr>
						<td colspan="7">
							<table border="0" width="100%">
								<tr class="tituloColumna" height="24">
									<td width="25%">Banco</td>
									<td width="25%">Cuenta</td>
									<td width="25%">Numero</td>
									<td width="25%">Monto</td>
									<td></td>
								</tr>
								<tr id="trPie" align="right">
									<td class="tituloColumna" colspan="3">Total Efectivo:</td>
									<td class="trResaltarTotal3"><input type="text" id="txtTotalEfectivo" name="txtTotalEfectivo" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
								</tr>
								<tr align="right" >
									<td class="tituloColumna" colspan="3">Total Cheques:</td>
									<td class="trResaltarTotal3"><input type="text" id="txtTotalCheques" name="txtTotalCheques" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
								</tr>
								<tr align="right" >
									<td class="tituloColumna" colspan="3">Total Cash Back:</td>
									<td class="trResaltarTotal3"><input type="text" id="txtTotalCashBack" name="txtTotalCashBack" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
								</tr>
								<tr align="right" >
									<td class="tituloColumna" colspan="3">Total Depósito:</td>
									<td class="trResaltarTotal"><input type="text" id="txtTotalDeposito" name="txtTotalDeposito" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td align="right" colspan="7"><hr>
							<input type="hidden" id="hddObjDetallePago" name="hddObjDetallePago"/>
							<button type="button" id="btnDepositar" name="btnDepositar" onclick="validarPlanilla();" class="puntero" disabled="disabled"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/coins.png"/></td><td>&nbsp;</td><td>Depositar</td></tr></table></button>
						</td>
					</tr>
				</table>
				</fieldset>
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
							<td><img src="../img/iconos/money.png"/></td><td>Efectivo</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/cheque.png"/></td><td>Cheques</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/page_white_put.png"/></td><td>Cargar Planilla</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/delete.png"/></td><td>Eliminar Pago</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/coins.png"/></td><td>Depositar</td>
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

<script>
byId('selTipoPago').className = "inputHabilitado";

xajax_cargaSelBanco();
xajax_listaPagoDepositar(0,"","","");
</script>