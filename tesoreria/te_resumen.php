<?php
require_once ("../connections/conex.php");
include('../inc_sesion.php');
session_start();

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_resumen.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento de Cuentas</title>
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
	function validarCuenta(){
		if (validarCampo('selBancoCuentaNueva','t','lista') == true
		&&  validarCampo('txtNumeroCuenta','t','numeroCuenta') == true){
				xajax_guardarCuenta(xajax.getFormValues('frmCuenta'));
		} else {
			validarCampo('selBancoCuentaNueva','t','lista')
			validarCampo('txtNumeroCuenta','t','numeroCuenta')
						
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include ('banner_tesoreria.php'); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
            <td class="tituloPaginaTesoreria" colspan="2">Resumen de Cuenta</td>
        </tr>
            <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <form id="frmBuscarCuenta" name="frmBuscarCuenta">
				<table align="right" border="0">
				<tr align="left">
                    <td class="tituloCampo" align="right" width="120">
                        Empresa:
                    </td>
                    <td id="tdSelEmpresa" align="left">
                        <select>
                            <option>Seleccione</option>
                        </select>
                    </td>
				</tr>
				<tr align="left">
                    <td class="tituloCampo" align="right" width="120">Banco:</td>
                    <td id="tdSelBancos" align="left">
                        <select id="selBancos" name="selBancos">
                            <option value="0">Todos</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Nro. Cuenta:</td>
                    <td align="left"><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscar').click();" size="30"/></td>
                    <td><button id="btnBuscar" name="btnBuscar" type="button" class="noprint" onclick="xajax_listarCuentas(0,'','',$('selBancos').value+'|'+$('txtDescripcionBusq').value + '|' + $('selEmpresa').value);" >Buscar</button>									
                    </td>
                    <td>
                        <button type="button" class="noprint" onclick="document.forms['frmBuscarCuenta'].reset(); $('btnBuscar').click();" >Ver Todo</button>
                    </td>
                </tr>
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListaCuentas">
                <script>
                        xajax_comboBancos(0,"tdSelBancos","selBancos","$('btnBuscar').click(); xajax_comboBancos((this.value),'tdSelBancoCuentaNueva','selBancoCuentaNueva','');");
                        xajax_listarCuentas(0,'','','0' + '|' + '' + '|' + '-1');
                        xajax_comboEmpresa();
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
    <form id="frmCuenta" name="frmCuenta">
    <table border="0" id="tblCuenta" width="900px">
    <tr>
    	<td>
            <table border="0">
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Banco:</td>
                <td id="tdSelBancoCuentaNueva">
                    <select id="selBancoCuentaNueva" name="selBancoCuentaNueva">
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Numero de Cuenta:</td>
                <td>
                    <input type="text" id="txtNumeroCuenta" name="txtNumeroCuenta" size="30" />
                    <input type="hidden" id="hddIdCuenta" name="hddIdCuenta" />
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Tipo Cuenta:</td>
                <td align="left" id="tdSelTipoCuenta">
                    <select id="selTipoCuenta" name="selTipoCuenta">
                    </select>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firma Electronica:</td>
                <td><input type="text" id="txtFirmaElectronica" name="txtFirmaElectronica" size="30" /></td>
                <td align="right" class="tituloCampo" width="50%">Moneda</td>
                <td align="left" id="tdSelMonedas">
                    <select id="selMonedas" name="selMonedas">
                    </select>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Cuenta Contable:</td>
                <td><input type="text" id="txtCuentaContable" name="txtCuentaContable"/></td>
                <td align="right" class="tituloCampo" width="50%">Cuenta Contable Contrapartida:</td>
                <td><input type="text" id="txtCuentaContableContrapartida" name="txtCuentaContableContrapartida"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Aplicar Debito Bancario:</td>
                <td id="tdSelAplicaDebito">
                    <select id="selAplicaDebito" name="selAplicaDebito">
                        <option value="1">NO</option>
                        <option value="0">SI</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="50%">Cuenta Debitos Bancarios:</td>
                <td><input type="text" id="txtCuentaDebitosBancarios" name="txtCuentaDebitosBancarios"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Saldo Anterior Conciliado:</td>
                <td><input type="text" id="txtSaldoLibros" name="txtSaldoLibros"/></td>
                <td align="right" class="tituloCampo" width="50%">Saldo en Libros:</td>
                <td><input type="text" id="txtSaldoAnteriorConciliado" name="txtSaldoAnteriorConciliado"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Proximo nro Cheque:</td>
                <td><input type="text" id="txtProximoNroCheque" name="txtProximoNroCheque" readonly="readonly"/></td>
                <td align="right" class="tituloCampo" width="50%">Estatus</td>
                <td id="tdSelEstatus">
                    <select id="selEstatus" name="selEstatus">
                        <option value="1">Activa</option>
                        <option value="0">Inactiva</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>&ensp;</td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 1:</td>
                <td><input type="text" id="txtFirmante1" name="txtFirmante1"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 1:</td>
                <td><input type="text" id="txtTipoFirmante1" name="txtTipoFirmante1"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 2:</td>
                <td><input type="text" id="txtFirmante2" name="txtFirmante2"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 2:</td>
                <td><input type="text" id="txtTipoFirmante2" name="txtTipoFirmante2"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 3:</td>
                <td><input type="text" id="txtFirmante3" name="txtFirmante3"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 3:</td>
                <td><input type="text" id="txtTipoFirmante3" name="txtTipoFirmante3"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 4:</td>
                <td><input type="text" id="txtFirmante4" name="txtFirmante4"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 4:</td>
                <td><input type="text" id="txtTipoFirmante4" name="txtTipoFirmante4"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 5:</td>
                <td><input type="text" id="txtFirmante5" name="txtFirmante5"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 5:</td>
                <td><input type="text" id="txtTipoFirmante5" name="txtTipoFirmante5"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 6:</td>
                <td><input type="text" id="txtFirmante6" name="txtFirmante6"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 6:</td>
                <td><input type="text" id="txtTipoFirmante6" name="txtTipoFirmante6"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Combinacion 1:</td>
                <td><input type="text" id="txtCombinacion1" name="txtCombinacion1"/></td>
                <td align="right" class="tituloCampo" width="50%">Restriccion Combinacion 1:</td>
                <td><input type="text" id="txtRestriccionCombinacion1" name="txtRestriccionCombinacion1"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Combinacion 2:</td>
                <td><input type="text" id="txtCombinacion2" name="txtCombinacion2"/></td>
                <td align="right" class="tituloCampo" width="50%">Restriccion Combinacion 2:</td>
                <td><input type="text" id="txtRestriccionCombinacion2" name="txtRestriccionCombinacion2"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Combinacion 3:</td>
                <td><input type="text" id="txtCombinacion3" name="txtCombinacion3"/></td>
                <td align="right" class="tituloCampo" width="50%">Restriccion Combinacion 3:</td>
                <td><input type="text" id="txtRestriccionCombinacion3" name="txtRestriccionCombinacion3"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
        	<input style="display:none" type="button" id="bttGuardar" name="bttGuardar" value="Guardar" onclick="validarCuenta();"/>
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
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