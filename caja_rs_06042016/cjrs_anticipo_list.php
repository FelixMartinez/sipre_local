<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_anticipo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_anticipo_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Caja de Repuestos y Servicios - Anticipos</title>
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

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr class="solo_print">
			<td align="left" id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
			<td class="tituloPaginaCajaRS">Anticipos</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right">
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
						<button type="button" onclick="xajax_validarAperturaCaja(1);" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
						<button type="button" onclick="xajax_imprimirListadoAnticipo(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
					</td>
				</tr>
				</table>
				<form id="frmBuscar" name="frmBuscar" onsubmit="byId('btnBuscar').click(); return false;" style="margin:0">
					<table align="right" border="0">
					<tr id="trEmpresa" align="left">
                        <td align="right" class="tituloCampo" width="120">Empresa:</td>
                        <td id="tdlstEmpresa"></td>
                    </tr>
					<tr align="left">
						<td align="right" class="tituloCampo" width="120">Fecha:</td>
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
                        <td align="right" class="tituloCampo" width="120">Estado:</td>
						<td>
							<label><input id="cbxNoCancelado" name="cbxNoCancelado" type="checkbox" checked="checked" value="0"/> No Cancelado</label>
							<label><input id="cbxCancelado" name="cbxCancelado" type="checkbox" checked="checked" value="1"/>Cancelado/No Asignado</label>
							<label><input id="cbxParcialCancelado" name="cbxParcialCancelado" type="checkbox" checked="checked" value="2"/>Asignado Parcial</label>
							<label><input id="cbxAsignado" name="cbxAsignado" type="checkbox" checked="checked" value="3"/>Asignado</label>
						</td>
						<td align="right" class="tituloCampo">Estatus:</td>
						<td id="tdlstEstatus">
							<select id="lstEstatus" name="lstEstatus" onchange="byId('btnBuscar').click();">
								<option value="-1">[ Todos ]</option>
								<option value="0">Anulado</option>
								<option value="1">Activo</option>
							</select>
						</td>
					</tr>
					<tr align="left">
						<td align="right" class="tituloCampo" width="120">Módulo:</td>
						<td id="tdlstModulo"></td>
						<td align="right" class="tituloCampo" width="120">Criterio:</td>
						<td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>	
						<td>
							<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
							<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
						</td>
					</tr>
					</table>
				</form>
			</td>
		</tr>
		<tr>
			<td id="tdListadoAnticipo"></td>
		</tr>
		<tr>
			<td align="right">
				<table>
				<tr align="left">
					<td align="right" class="tituloCampo" width="140">Saldo:</td>
					<td><input type="text" id="txtSaldo" name="txtSaldo" style="text-align:right" class="trResaltarTotal3"/></td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="140">Total:</td>
					<td><input type="text" id="txtTotal" name="txtTotal" style="text-align:right" class="trResaltarTotal"/></td>
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
                            <td><img src="../img/iconos/ico_verde.gif"/></td>
                            <td>Activo</td>
                            <td><img src="../img/iconos/ico_rojo.gif"/></td>
                            <td>Anulado</td>
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
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

byId('txtFechaDesde').value = "<?php echo date("01-m-Y")?>";
byId('txtFechaHasta').value = "<?php echo date("d-m-Y")?>";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaHasta").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"brown"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"brown"
	});
};

xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_validarAperturaCaja(2);
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstModulo();
xajax_listadoAnticipo(0,'idAnticipo','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('cbxNoCancelado').value + '|' + byId('cbxCancelado').value + '|' + byId('cbxParcialCancelado').value + '|' + byId('cbxAsignado').value + '|' + byId('txtFechaDesde').value + '|' + byId('txtFechaHasta').value + '|' + byId('lstEstatus').value);
</script>