<?php
require_once ("../connections/conex.php");

session_start();

include ("../inc_sesion.php");

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_chequeras.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento de Chequeras</title>
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
	function validarChequera(){
		if (validarCampo('selBancoNuevaChequera','t','lista') == true
		&&  validarCampo('selCuentaNuevaChequera','t','lista') == true
		&&  validarCampo('txtNumeroInicial','t','cantidad') == true
		&&  validarCampo('txtNumeroFinal','t','cantidad') == true){
			if ($('txtNumeroInicial').value < $('txtNumeroFinal').value)
				xajax_guardarChequera(xajax.getFormValues('frmChequera'));
			else{
				alert("Numeros Invalidos");
				$('txtNumeroInicial').className = "inputErrado";
				$('txtNumeroFinal').className = "inputErrado";
			}
		} else {
			validarCampo('selBancoNuevaChequera','t','lista')
			validarCampo('selCuentaNuevaChequera','t','lista')
			validarCampo('txtNumeroInicial','t','cantidad')
			validarCampo('txtNumeroFinal','t','cantidad')
						
			alert("Los campos señalados en rojo son requeridos");
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
		<table width="80%" align="center">
            <tr>
                <td class="tituloPaginaTesoreria" colspan="2">Mantenimiento de Chequeras</td>
            </tr>
            <tr>
            	<td>
                	<form id="frmBuscarChequera" name="frmBuscarChequera">
                    <table align="left" width="100%">
						<tr>
                            <td width="10%" class="tituloCampo">
                            	Banco: 
                            </td>
                            <td id="tdSelBancos" width="30%">
                            	<select id="selBancos" name="selBancos">
									<option value="0">Todos</option>
								</select>
                            </td>
                            <td align="right" class="tituloCampo" width="30%">Numero Cuenta:</td>
                            <td align="left" width="10%"><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscar').click();" size="30"/></td>
                            <td width="5%"><button id="btnBuscar" name="btnBuscar" type="button" class="noprint" onclick="xajax_listarChequeras(0,'','',$('selBancos').value+'|'+$('txtDescripcionBusq').value);">Buscar</button>
                            </td>
                            <td width="5%">
                                <button type="button" class="noprint" style="white-space:nowrap;" onclick="document.forms['frmBuscarChequera'].reset(); $('btnBuscar').click();">Ver Todo</button>
                            </td>
						</tr>
                        <tr>
                        	<td align="left" width="25%">
                                    <button type="button" onclick="xajax_levantarDivFlotante();">Nuevo</button>
		                    </td>
                        </tr>
					</table>
                    </form>
				</td>
            </tr>
        
            <tr>
            	<td id="tdListaChequeras">
					<script>
                        xajax_comboBancos(0,"tdSelBancos","selBancos","$('btnBuscar').click();");
						xajax_listarChequeras(0,'','','0');
                    </script>
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
    <form id="frmChequera" name="frmChequera">
    <table border="0" id="tblChequera" width="660px">
    <tr>
    	<td>
            <table border="0">
                <tr id="tr1">
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Banco:</td>
                    <td id="tdSelBancoNuevaChequera">
                    	<select id="selBancoNuevaChequera" name="selBancoNuevaChequera">
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nro de cuenta:</td>
                    <td id="tdSelCuentas">
                    	<select id="selCuentaNuevaChequera" name="selCuentaNuevaChequera" disabled="disabled">
                        </select>
                    </td>
                </tr>
                <tr id="tr2">
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Numero inicial:</td>
                    <td><input type="text" id="txtNumeroInicial" name="txtNumeroInicial" size="30" onkeypress="return validarSoloNumeros(event);"/></td>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Numero final:</td>
                    <td><input type="text" id="txtNumeroFinal" name="txtNumeroFinal" size="30" onkeypress="return validarSoloNumeros(event);"/></td>
                </tr>
                <tr id="tr3">
                    <td align="right" class="tituloCampo" width="120">Chequera activa:</td>
                    <td id="tdSelChequeraActiva">
                    	<select id="selChequeraActiva" name="selChequeraActiva">
                        	<option value="SI">SI</option>
                            <option value="NO">NO</option>
                        </select>
                    </td>
                    <td id="td1" align="right" class="tituloCampo" width="120">Ultimo nro de cheque:</td>
                    <td id="td2">
                    	<input type="text" id="txtUltimoNumeroCheque" name="txtUltimoNumeroCheque" size="30" readonly="readonly"/>
                        <input type="hidden" id="hddIdChequera" name="hddIdChequera" />
                    </td>
                </tr>
                <tr id="tr4">
                	<td align="right" class="tituloCampo" width="120">Impresos:</td>
                    <td><input type="text" id="txtImpresos" name="txtImpresos" size="30" readonly="readonly"/></td>
                    <td align="right" class="tituloCampo" width="120">Anulados:</td>
                    <td><input type="text" id="txtAnulados" name="txtAnulados" size="30" readonly="readonly"/></td>
                </tr>
                <tr id="tr5">
                    <td align="right" class="tituloCampo" width="120">Disponibles:</td>
                    <td><input type="text" id="txtDisponibles" name="txtDisponibles" size="30" readonly="readonly"/></td>
                    <td align="right" class="tituloCampo" width="120">Cantidad de cheques:</td>
                    <td><input type="text" id="txtCantidadCheque" name="txtCantidadCheque" size="30" readonly="readonly"/></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
        	<input style="display:none" type="button" id="bttGuardar" name="bttGuardar" value="Guardar" onclick="validarChequera();"/>
            <input type="button" onclick="$('divFlotante').style.display='none';  $('txtNumeroInicial').className = 'inputInicial'; $('txtNumeroFinal').className = 'inputInicial';" value="Cancelar">
		</td>
    </tr>
    </table>
    </form>
  
</div>

<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>