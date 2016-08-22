<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("te_bancos"))) {
	echo "
	<script>
		alert('Acceso Denegado');
		top.history.back();
	</script>";
}
/* Fin Validación del Módulo */


require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_te_bancos.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento de Bancos</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('controladores/xajax/');?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css">
	
	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<style type="text/css">
		.root {
			background-color:#FFFFFF;
			border:6px solid #999999;
			font-family:Verdana, Arial, Helvetica, sans-serif;
			font-size:11px;
			max-width:1050px;
			position:absolute;
		}
		
		.handle {
			padding:3px;
			background-color:#990000;
			color:#FFFFFF;
			font-weight:bold;
			cursor:move;
		}
	</style>
	<script>
	function validarBanco(){
		if (validarCampo('txtNombreBanco','t','') == true/*
		&& validarCampo('txtSucursalBanco','','') == true
		&& validarCampo('txtDireccionBanco','','') == true
		&& validarCampo('txtRIF','','') == true
		&& validarCampo('txtTelefonoBanco','','numPositivo') == true
		&& validarCampo('txtFaxBanco','','numPositivo') == true*/
		//&& validarCampo('txtEmailBanco','','email') == true
		/*&& validarCampo('txtPorcentajeFlatBanco','','monto') == true
		&& validarCampo('txtDSBCLocalesBanco','','numPositivo') == true
		&& validarCampo('txtDSBCForaneosBanco','','numPositivo') == true*/){
				xajax_guardarBanco(xajax.getFormValues('frmBanco'));
		} else {
			validarCampo('txtNombreBanco','t','')
			/*validarCampo('txtSucursalBanco','','')
			validarCampo('txtDireccionBanco','','')
			validarCampo('txtRIF','','')
			validarCampo('txtTelefonoBanco','','numPositivo')
			validarCampo('txtFaxBanco','','numPositivo')*/
			//validarCampo('txtEmailBanco','','email')
			/*validarCampo('txtPorcentajeFlatBanco','','monto')
			validarCampo('txtDSBCLocalesBanco','','numPositivo')
			validarCampo('txtDSBCForaneosBanco','','numPositivo')*/
						
			alert("El campo señalado en rojo es requerido");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
		<?php include ('banner_tesoreria.php'); ?>
	</div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaTesoreria" colspan="2">Mantenimiento de Bancos</td>
		</tr>
		<tr class="noprint">
			<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<button type="button" id="btnNueva" onclick="xajax_levantarDivFlotante();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
					</td>
				</tr>
				</table>
				<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" onkeyup="$('btnBuscar').click();"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarBanco(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td id="tdListaBancos"></td>
		</tr>
		<tr>
			<td>
				<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25" class="puntero"/></td>
					<td align="center">
						<table>
						<tr>
							<td><img src="../img/iconos/ico_view.png"/></td>
							<td>Ver</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/pencil.png"/></td>
							<td>Editar</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/cross.png"/></td>
							<td>Eliminar</td>
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
	<div class="noprint"><?php include("pie_pagina.php") ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
	<form id="frmBanco" name="frmBanco">
	<table border="0" id="tblBanco" width="660px">
	<tr>
		<td>
			<table border="0">
			<tr>
				<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nombre:</td>
				<td>
					<input type="text" id="txtNombreBanco" name="txtNombreBanco" size="30" />
					<input type="hidden" id="hddIdBanco" name="hddIdBanco" />
				</td>
				<td align="right" class="tituloCampo" width="120">Sucursal:</td>
				<td><input type="text" id="txtSucursalBanco" name="txtSucursalBanco" size="30" /></td>
			</tr>
			<tr>
				<td align="right" class="tituloCampo" width="120">Direccion:</td>
                                <td><textarea cols="27" rows="1" id="txtDireccionBanco" name="txtDireccionBanco" /></textarea></td>
				<td align="right" class="tituloCampo" width="120">
                                    <img title="Código superior e inferior de Banco/Sucursal para cheques Puerto Rico" src="../img/iconos/ico_pregunta.gif" style="vertical-align:middle"/>
                                    C&oacute;digo
                                </td>
				<td>
                                    <input type="text" id="txtCodigo1" name="txtCodigo1" size="30" />
                                    <input type="text" id="txtCodigo2" name="txtCodigo2" size="30" />
                                </td>
			</tr>
                        <tr>				
                                <td></td>
                                <td></td>
                                <td align="right" class="tituloCampo" width="120">RIF:</td>
				<td><input type="text" id="txtRIF" name="txtRIF" size="30" /></td>
				
			</tr>
			<tr>
				<td align="right" class="tituloCampo" width="120">Telefono:</td>
				<td><input type="text" id="txtTelefonoBanco" name="txtTelefonoBanco" size="30" onkeypress="return validarTelefono(event);" /></td>
				<td align="right" class="tituloCampo" width="120">Fax:</td>
				<td><input type="text" id="txtFaxBanco" name="txtFaxBanco" size="30" onkeypress="return validarTelefono(event);" /></td>
			</tr>
			<tr>
				<td align="right" class="tituloCampo" width="120">Email:</td>
				<td><input type="text" id="txtEmailBanco" name="txtEmailBanco" size="30" />
					<!--<input type="text" id="txtEmailBanco" name="txtEmailBanco" size="30" onkeypress="return validarCorreo(event);" />-->
				</td>
				<td align="right" class="tituloCampo" width="120">Porecentaje Flat:</td>
				<td><input type="text" id="txtPorcentajeFlatBanco" name="txtPorcentajeFlatBanco" size="30" onkeypress="return validarSoloNumerosReales(event);" /></td>
			</tr>
			<tr>
				<td align="right" class="tituloCampo" width="120">DSBC Locales:</td>
				<td><input type="text" id="txtDSBCLocalesBanco" name="txtDSBCLocalesBanco" size="30" onkeypress="return validarSoloNumeros(event);" /></td>
				<td align="right" class="tituloCampo" width="120">DSBC Foraneos:</td>
				<td><input type="text" id="txtDSBCForaneosBanco" name="txtDSBCForaneosBanco" size="30" onkeypress="return validarSoloNumeros(event);" /></td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="button" id="btnGuardar" name="btnGuardar" onclick="validarBanco();">Guardar</button>
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="$('divFlotante').style.display='none';">Cancelar</button>
		</td>
	</tr>
	</table>
	</form>
</div>

<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
	
xajax_listarBancos(0,'nombreBanco','ASC','');	
</script>