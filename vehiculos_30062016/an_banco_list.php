<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_banco_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_an_banco_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Bancos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function validarForm() {
		if (validarCampo('txtNombre','t','') == true
		&& validarCampo('txtPorcentajeComisionFlat','t','numPositivo') == true
		&& validarCampo('txtDiasBuenCobroLocal','t','numPositivo') == true
		&& validarCampo('txtDiasBuenCobroForaneo','t','numPositivo') == true) {
			xajax_guardarBanco(xajax.getFormValues('frmBanco'), xajax.getFormValues('frmListaBanco'));
		} else {
			validarCampo('txtNombre','t','');
			validarCampo('txtPorcentajeComisionFlat','t','numPositivo');
			validarCampo('txtDiasBuenCobroLocal','t','numPositivo');
			validarCampo('txtDiasBuenCobroForaneo','t','numPositivo');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormFactor() {
		if (validarCampo('txtNombreBancoFactor','t','') == true
		&& validarCampo('txtTasa','t','monto') == true
		&& validarCampo('txtMesesFactor','t','numPositivo') == true
		&& validarCampo('txtFactor','t','') == true) {
			xajax_guardarFactor(xajax.getFormValues('frmFactorFinanciero'), xajax.getFormValues('frmListaFactor'));
		} else {
			validarCampo('txtNombreBancoFactor','t','');
			validarCampo('txtTasa','t','monto');
			validarCampo('txtMesesFactor','t','numPositivo');
			validarCampo('txtFactor','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idBanco){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarBanco(idBanco, xajax.getFormValues('frmListaBanco'));
		}
	}
	
	function validarEliminarFactor(idFactor){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarFactor(idFactor, xajax.getFormValues('frmListaFactor'));
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralVehiculos">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Bancos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="100">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="$('btnBuscar').click();"/></td>
                    <td>
                        <input type="submit" id="btnBuscar" onclick="xajax_buscarBanco(xajax.getFormValues('frmBuscar'));" value="Buscar"/>
						<input type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" value="Limpiar"/>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaBanco" name="frmListaBanco" style="margin:0">
            	<div id="tdListaBanco" style="width:100%"></div>
            </form>
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
    
<form id="frmBanco" name="frmBanco" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblBanco" width="520px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td width="60%"><input type="text" id="txtNombre" name="txtNombre" readonly="readonly" size="40"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Porcentaje Comisión FLAT:</td>
                <td><input type="text" id="txtPorcentajeComisionFlat" name="txtPorcentajeComisionFlat" size="12" style="text-align:right"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Días Salvo Buen Cobro Local:</td>
                <td><input type="text" id="txtDiasBuenCobroLocal" name="txtDiasBuenCobroLocal" size="12" style="text-align:right"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Días Salvo Buen Cobro Foráneo:</td>
                <td><input type="text" id="txtDiasBuenCobroForaneo" name="txtDiasBuenCobroForaneo" size="12" style="text-align:right"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="hidden" id="hddIdBanco" name="hddIdBanco" readonly="readonly"/>
            <input type="submit" onclick="validarForm();" value="Guardar">
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
</form>
	
	<table border="0" id="tblListaFactorFinanciero" width="550px">
    <tr>
    	<td>
        <form id="frmFactor" name="frmFactor" style="margin:0">
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="15%">Banco:</td>
                <td width="85%">
            		<input type="hidden" id="hddIdBancoListaFactor" name="hddIdBancoListaFactor" readonly="readonly"/>
                    <input type="text" id="txtNombreBancoListaFactor" name="txtNombreBancoListaFactor" readonly="readonly" size="40"/>
				</td>
            </tr>
			</table>
		</form>
		</td>
    </tr>
    <tr>
    	<td>
        	<button type="button" id="btnInsertarArt" name="btnInsertarArt" onclick="xajax_formFactor(xajax.getFormValues('frmFactor'))" style="cursor:default" title="Agregar Articulo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_agregar.gif"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
        </td>
    </tr>
    <tr>
    	<td>
        <form id="frmListaFactor" name="frmListaFactor" style="margin:0">
            <table cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td id="tdListaFactorFinanciero"></td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" onclick="$('divFlotante1').style.display='none'; $('divFlotante').style.display='none';" value="Cerrar">
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmFactorFinanciero" name="frmFactorFinanciero" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblFactorFinanciero" width="450px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
                <td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Banco:</td>
                <td width="75%">
                	<input type="hidden" id="hddIdBancoFactor" name="hddIdBancoFactor" readonly="readonly"/>
                    <input type="text" id="txtNombreBancoFactor" name="txtNombreBancoFactor" readonly="readonly" size="40"/>
				</td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tasa:</td>
                <td><input type="text" id="txtTasa" name="txtTasa" size="12" style="text-align:right"/>%</td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Meses:</td>
                <td><input type="text" id="txtMesesFactor" name="txtMesesFactor" size="6" style="text-align:center"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Factor:</td>
                <td><input type="text" id="txtFactor" name="txtFactor" size="12" style="text-align:right"/></td>
            </tr>
			</table>
		</td>
	</tr>
    <tr>
        <td align="right">
            <hr>
            <input type="hidden" id="hddIdFactor" name="hddIdFactor" readonly="readonly"/>
            <input type="submit" onclick="validarFormFactor();" value="Guardar">
            <input type="button" onclick="$('divFlotante1').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
</form>
</div>
<script>
xajax_listadoBanco();
</script>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("divFlotanteTitulo1");
	var theRoot   = document.getElementById("divFlotante1");
	Drag.init(theHandle, theRoot);
</script>