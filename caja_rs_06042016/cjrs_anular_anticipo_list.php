<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_anular_anticipo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_anular_anticipo_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Caja de Repuestos y Servicios - Anulación de Anticipos</title>
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
	
	<script>
	function validarFormPermiso() {
		if (validarCampo('txtContrasena','t','') == true
		&& validarCampo('txtMotivoAnulacion','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
		} else {
			validarCampo('txtContrasena','t','');
			validarCampo('txtMotivoAnulacion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
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
			<td class="tituloPaginaCajaRS">Anulación de Anticipos</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr class="noprint">
			<td align="right">
				<table align="left">
				<tr>
					<td>
						<button type="button" onclick="xajax_imprimirEliminarPagosdelDia(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
					</td>
				</tr>
				</table>
				<form id="frmBuscar" name="frmBuscar" onsubmit="byId('btnBuscar').click(); return false;" style="margin:0">
					<table align="right" border="0">
					<tr id="trEmpresa" align="left">
						<td align="right" class="tituloCampo" width="120">Empresa:</td>
						<td id="tdlstEmpresa">
							<select id="lstEmpresa" name="lstEmpresa">
								<option value="-1">[ Todos ]</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align="right" class="tituloCampo" width="100">Criterio:</td>
						<td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"></td>
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
			<td id="tdListadoAnticipo"></td>
		</tr>
		<tr>
			<td>
				<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
						<table>
						<tr>
							<td><img src="../img/iconos/delete.png"/></td>
							<td>Anular Anticipo</td>
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
	<form id="frmPermiso" name="frmPermiso" onsubmit="byId('btnPermiso').click(); return false;" style="margin:0px">
		<table border="0" id="tblPermiso" width="350px">
		<tr>
			<td>
				<table width="100%">
				<tr>
					<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
					<td width="68%">
						<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
						<input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30" value="cjrs_anular_anticipo"/>
						<input type="hidden" id="hddValores" name="hddValores" readonly="readonly"/>
					</td>
				</tr>
				<tr>
                    <td align="right" class="tituloCampo" width="32%" rowspan="2"><span class="textoRojoNegrita">*</span>Motivo de Anulación:</td>
                    <td  width="68%">
                    	<textarea cols="28" id="txtMotivoAnulacion" name="txtMotivoAnulacion" rows="3"></textarea>
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td align="right"><hr>
				<button type="button" id="btnPermiso" name="btnPermiso" onclick="validarFormPermiso();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button><input type="hidden" name="hddIdDocumento" id="hddIdDocumento"/>
				<button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso" onclick="byId('divFlotante').style.display = 'none'; byId('txtContrasena').value = ''" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
			</td>
		</tr>
		</table>
	</form>
</div>
<script>
byId('txtCriterio').className = "inputHabilitado";

xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_validarAperturaCaja();
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listadoAnticipo(0,'','ASC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|');

</script>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>