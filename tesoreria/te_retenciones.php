<?php
require_once ("../connections/conex.php");

session_start();

include ("../inc_sesion.php");

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_retenciones.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento de Retenciones</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
        
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
	function validarRetencion(){

		if (validarCampo('txtDescripcion','t','') == true
		&&  validarCampo('txtRetencion','t','') == true
		&&  validarCampo('txtCodigo','t','') == true){
				xajax_guardarRetencion(xajax.getFormValues('frmCuenta'));
		} else {
			validarCampo('txtDescripcion','t','')
			validarCampo('txtRetencion','t','')
			validarCampo('txtCodigo','t','')
						
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function letrasNumerosEspeciales(e) {
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8)
			return true;
		patron = /[-,.()0-9A-Za-z\s ]/;
		te = String.fromCharCode(tecla);
		return patron.test(te);
	}

	function numeros(e) {
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8)
			return true;
		patron = /[0-9]/;
		te = String.fromCharCode(tecla);
		return patron.test(te);
	}
	
	/*function numerosPunto(e){
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8)
			return true;
		patron = /[0-9.]/;
		te = String.fromCharCode(tecla);
		return patron.test(te);
	}*/
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include ('banner_tesoreria.php'); ?>
    </div>

    <div id="divInfo" class="print">
		<table width="80%" align="center">
            <tr>
                <td class="tituloPaginaTesoreria" colspan="2">Mantenimiento de Retenciones</td>
            </tr>
            <tr>
            	<td align="left">
                    <button type="button" onclick="xajax_levantarDivFlotante();">Nuevo</button>
				</td>
			</tr>
        
            <tr>
            	<td id="tdListaRetenciones">
                </td>
            </tr>
            <tr>
                <td>
                    <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                        <tbody>
                            <tr>
                                <td width="25"></td>
                                <td align="center">
                                    <table>
                                    <tbody><tr>
                                        <td><img src="../img/iconos/ico_verde.gif"/></td>
                                        <td>Activo</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td><img src="../img/iconos/ico_rojo.gif"/></td>
                                        <td>Inactivo</td>
                                    </tr>
                                    </tbody></table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
		</table>
    </div>
    <div class="noprint">
	<?php include("pie_pagina.php") ?>
    </div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    <form id="frmCuenta" name="frmCuenta">
    <table border="0" id="tblCuenta" width="370px">
    <tr>
    	<td>
            <table border="0">
                <tr>
                    <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Descripcion:</td>
                    <td>
                    	<input type="text" id="txtDescripcion" name="txtDescripcion" size="30" onkeypress="return letrasNumerosEspeciales(event);" class="inputHabilitado"/>
                    	<input type="hidden" id="hddIdRetencion" name="hddIdRetencion" />
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="50%">Importe >= Para Aplicar:</td>
                    <td><input type="text" id="txtImporte" name="txtImporte" size="30" onkeypress="return validarSoloNumerosReales(event);" class="inputHabilitado"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="50%">Unidad Tributaria:</td>
                    <td><input type="text" id="txtUnidadTributaria" name="txtUnidadTributaria"  size="30" onkeypress="return validarSoloNumerosReales(event);" class="inputHabilitado"/></td>
		</tr>
                <tr>
                    <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>% Retención:</td>
                    <td><input type="text" id="txtRetencion" name="txtRetencion"  size="30" onkeypress="return validarSoloNumerosReales(event);" class="inputHabilitado"/></td>
		</tr>
                <tr>
                    <td align="right" class="tituloCampo" width="50%">Sustraendo en Retención:</td>
                    <td><input type="text" id="txtSustraendo" name="txtSustraendo"  size="30" onkeypress="return validarSoloNumerosReales(event);" class="inputHabilitado"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Codigo Concepto:</td>
                    <td><input type="text" id="txtCodigo" name="txtCodigo"  size="30" onkeypress="return numeros(event);" class="inputHabilitado"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Estado:</td>
                    <td>
                        <select name="selActivo" id="selActivo" class="inputHabilitado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
        	<input type="button" id="bttGuardar" name="bttGuardar" value="Guardar" onclick="validarRetencion();"/>
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
		</td>
    </tr>
    </table>
    </form>
  
</div>

<script language="javascript">
	xajax_listarRetenciones(0,'','','0');

	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>