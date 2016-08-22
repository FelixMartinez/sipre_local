<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_depositos_form"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

$currentPage = $_SERVER["PHP_SELF"];

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_planilla_deposito_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Caja de Repuestos y Servicios - Depósitos</title>
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
    function validarTodoForm2(){
        if (validarCampo('EditNroPlanilla','t','') == true){
            xajax_editarPlanilla(xajax.getFormValues('frmNroPlanilla'));
        } else {
            validarCampo('EditNroPlanilla','t','');
            alert("Ingrese el nuevo Nro. de Planilla");
        }
    }
    
    function validarTodoForm()
        {
            if (validarCampo('txtNroPlanilla','t','') == true
            && validarCampo('fchPlanilla','t','') == true
            && validarCampo('txtBanco','t','') == true
            && validarCampo('txtNroCuenta','t','') == true
            && validarCampo('txtAnulada','t','') == true){
                xajax_guardarDeposito(xajax.getFormValues('frmDeposito'));
            } else {
                validarCampo('txtNroPlanilla','t','');
                validarCampo('fchPlanilla','t','');
                validarCampo('txtBanco','t','');
                validarCampo('txtNroCuenta','t','');
                validarCampo('txtAnulada','t','');
    
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
		<table align="center" border="0" width="100%">
		<tr>
			<td align="center" class="tituloPaginaCajaRS">Depósitos<br/><span class="textoNegroNegrita_10px">(Lista de Dep&oacute;sitos)</span>
            </td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
            <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarDeposito(xajax.getFormValues('frmBuscar')); return false;" style="margin:0" >
				<table align="right" border="0">
				<tr>
					<td align="right" class="tituloCampo" width="120">Nro. Depósito:</td>
					<td align="left">
						<input type="text" id="txtCriterio" name="txtCriterio" size="16" onkeyup="xajax_buscarDeposito(xajax.getFormValues('frmBuscar'));"/>
					</td>
					<td align="right" class="tituloCampo" width="120">Fecha Depósito:</td>
					<td><input type="text" id="txtFecha" name="txtFecha" autocomplete="off" size="10" style="text-align:center"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarDeposito(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>	
				</table>
            </form>
			</td>
		</tr>
		<tr>
			<td id="tdListadoDeposito" colspan="2"></td>
		</tr>
		<tr>
			<td>
				<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
						<table>
						<tr>
							<td><img src="../img/iconos/pencil.png"/></td>
							<td>Editar</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/ico_print.png"/></td>
							<td>Imprimir</td>
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

<!--LISTADO DE PLANILLAS-->
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmListaArtPedido" name="frmListaArtPedido" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblListaArtPedido" width="960">
	<tr>
		<td id="tdEditarPlanillaDeposito"></td>	
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotante').style.display='none';">Cerrar</button>
		</td>
	</tr>
	</table>
</form>
</div>

<!--EDITAR NRO. PLANILLA-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
	<table border="0" width="100%">
    <tr>
        <td align="left">
            <form id="frmNroPlanilla" name="frmNroPlanilla" style="margin:0">
            <table border="0" width="100%">
            <tr>
                <td>
                    <table border="0" width="100%">
                    <tr id="trId" align="left">
                        <td align="right" class="tituloCampo">Nro. Planilla a modificar:</td>
                        <td>
                            <input type="text" id="NroPlanilla" name="NroPlanilla" size="26" readonly="readonly"/>
                            <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
                            <input type="hidden" id="hddIdDeposito" name="hddIdDeposito"/>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Nro. Planilla:</td>
                        <td><input type="text" id="EditNroPlanilla" name="EditNroPlanilla" size="26"/></td>
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
            <button type="button" id="btnEditar" name="btnEditar" onclick="validarTodoForm2();">Guardar</button>
            <button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotante2').style.display='none';">Cancelar</button>
        </td>
    </tr>
	</table>
</div>

<script>
byId('txtCriterio').className = "inputHabilitado";
byId('txtFecha').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
		$("#txtFecha").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"brown"
	});
};

xajax_listadoDeposito(0,'idPlanilla','DESC','' + '|' + -1);
xajax_EditarPlanillaDeposito(0,'','','' + '|' + -1);

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>