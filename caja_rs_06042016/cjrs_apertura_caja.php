<?php 
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_apertura_caja"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_apertura_caja.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Caja de Repuestos y Servicios - Apertura de Caja</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>

	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
</head>

<script language="javascript" type="text/javascript">
function validar() {
	if (!(validarCampo('txtCargaEfectivo','t','') == true)){
		validarCampo('txtCargaEfectivo','t','');
		
		error = true;
	}
	
	if (error == true) {
		alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
		return false;
	} else {
		byId('btnApertura').disabled = true;
		xajax_aperturaCaja(xajax.getFormValues('frmApertura'));
	}
}

function horaActual() {
	momentoActual = new Date();
	
	hora = momentoActual.getHours();
	minuto = momentoActual.getMinutes();
	segundo = momentoActual.getSeconds();
	
	tiempo = "a.m."
	if (parseInt(hora) == 0) {
		hora = 12;
	} else if (parseInt(hora) > 12) {
		hora = hora - 12;
		tiempo = "p.m."
	}
	
	if (parseInt(minuto) >= 0 && parseInt(minuto) <= 9)
		minuto = "0" + minuto;
		
	if (parseInt(segundo) >= 0 && parseInt(segundo) <= 9)
		segundo = "0" + segundo;

	horaImprimible = hora + ":" + minuto + ":" + segundo + " " + tiempo;
	
	document.getElementById('tdHoraActual').innerHTML = horaImprimible
	
	setTimeout("horaActual()",1000)
}
</script>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
	<div id="divInfo" class="print" style="vertical-align:middle">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCajaRS">Apertura de Caja Repuestos y Servicios</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right">
			<form id="frmApertura" name="frmApertura" style="margin:0">
				<table border="0" width="100%" align="center">
				<tr>
					<td>
						<fieldset><legend class="legend">Apertura de Caja</legend>
						<table border="0" width="100%" align="center">
						<tr>
							<td valign="top">
								<table border="0" align="center">
                                <tr>
									<td align="right" class="tituloCampo" width="120">Estado de Caja:</td>
                                    <td style="text-align:left">
                                        <label><input type="text" id="txtEstadoDeCaja" name="txtEstadoDeCaja" style="text-align:center;" size="25" readonly="readonly" class="inputCantidadNoDisponible"/></label>
                                    </td>
                                </tr>
								<tr>
									<td align="right" class="tituloCampo" width="120">Empresa:</td>
									<td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" autocomplete="off" size="20" readonly="readonly" style="text-align:center"/></td>
									<td align="right" class="tituloCampo" width="120"><?php echo $spanRIF; ?>:</td>
									<td><input type="text" id="txtRif" name="txtRif" autocomplete="off" size="20" readonly="readonly" style="text-align:center"/></td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" width="120">Fecha de Apertura:</td>
									<td><input type="text" id="txtFechaApertura" name="txtFechaApertura" autocomplete="off" size="16" style="text-align:center" readonly="readonly"/></td>
									<td align="right" class="tituloCampo" width="120">Hora Actual:</td>
									<td id="tdHoraActual"></td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" width="120">Saldo de Caja</td>
									<td><input type="text" id="txtSaldoCaja" name="txtSaldoCaja" readonly="readonly" value="0.00" size="16" style="text-align:right"/></td>
									<td align="right" class="tituloCampo" width="120">Carga de Efectivo</td>
									<td><input type="text" id="txtCargaEfectivo" name="txtCargaEfectivo" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);" size="16" style="text-align:right" class="inputHabilitado"/></td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
						</fieldset>
					</td>
				</tr>
				<tr>
					<td align="right"><hr/>
						<button type="button" id="btnApertura" name="btnApertura" onclick="validar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/key.png"/></td><td>&nbsp;</td><td>Aperturar</td></tr></table></button>
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		</table>
	</div>
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
	horaActual();
	xajax_cargarDatosCaja();
</script>