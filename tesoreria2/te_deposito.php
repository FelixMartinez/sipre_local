<?php
require_once ("../connections/conex.php");
/* Validación del Módulo */
//include('../inc_sesion.php');
//validaModulo("an_modificar_vehiculo");
/* Fin Validación del Módulo */

@session_start();

include ("../inc_sesion.php");

//require_once('clases/rafkLista.php');
$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_te_deposito.php");

//modificado Ernesto
if(file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")){
	include("../contabilidad/GenerarEnviarContabilidadDirecto.php");
}
//Fin modificado Ernesto

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Depositos</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
   
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
   
       
<!-- <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
     <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
<!--    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
<link rel="stylesheet" type="text/css" media="all" href="../js/calendar-red.css"/>
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>-->
    
	<script>

window.onload = function(){
	
  new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"red"
	});
  new JsDatePick({
		useMode:2,
		target:"txtFecha1",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"red"
	});
	
  new JsDatePick({
		useMode:2,
		target:"txtFechaRegistro",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"red",
                button : "imgFechaRegistro"
	});     
        
	
};

      function validarFormInsertar(){
			if (validarCampo('txtNombreEmpresa','t','') == true
				&& validarCampo('txtNombreBanco','t','') == true
				&& validarCampo('txtObservacion','t','') == true
				&& validarCampo('txtSaldoCuenta','t','') == true 
				&& validarCampo('txtNumeroPlanilla','t','') == true
				&& validarCampo('txtTotalDeposito','t','monto') == true
				&& validarCampo('selMotivo','t','lista') == true)
			{
				xajax_guardarDeposito(xajax.getFormValues('frmDeposito'));
			} else {
				
				validarCampo('txtNombreEmpresa','t','');
				validarCampo('txtNombreBanco','t','');
				validarCampo('txtObservacion','t','');
				validarCampo('txtSaldoCuenta','t','');
				validarCampo('txtNumeroPlanilla','t','');
				validarCampo('txtTotalDeposito','t','monto');
				validarCampo('selMotivo','t','lista');
				
				alert("Los campos señalados en rojo son requeridos");
                                desbloquearGuardado();
	
				return false;
	
			}
		}
		
		function validarFormFormBancoCheque(){
			if (validarCampo('txtBancoCheque','t','') == true
				&& validarCampo('txtNumeroCheque','t','') == true
				&& validarCampo('txtNumeroCuentaCheque','t','') == true
				&& validarCampo('txtMontoCheque','t','monto') == true)
			{
				xajax_insertarCheques(xajax.getFormValues('frmDeposito'),xajax.getFormValues('frmMonto'));
			} else {
				validarCampo('txtBancoCheque','t','');
				validarCampo('txtNumeroCheque','t','');
				validarCampo('txtNumeroCuentaCheque','t','');
				validarCampo('txtMontoCheque','t','monto');
				
				alert("Los campos señalados en rojo son requeridos");                                
	
				return false;
	
			}
		}
                
                function desbloquearGuardado(){                    
                    document.getElementById("btnGuardar").disabled = false;
                }
    </script>
    
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
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include ('banner_tesoreria.php'); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaTesoreria">Dep&oacute;sito<br></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<button type="button" id="btnNuevo" onclick="xajax_nuevoDeposito(xajax.getFormValues('frmDeposito'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
					</td>
				</tr>
				</table>
                <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarDeposito(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdSelEmpresa" align="left">
                        <select id="selEmpresa" name="selEmpresa">
                            <option value="-1">Seleccione</option>
                        </select>
                    </td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo">Fecha Registro:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                Desde:<input style="width:100px;" type="text" name="txtFecha" id="txtFecha" readonly="readonly"/>
                                Hasta:<input style="width:100px;" type="text" name="txtFecha1" id="txtFecha1" readonly="readonly"/>
                            </td>
                            <td>
<!--                                <div style="float:left"><img src="../img/iconos/ico_date.png" onclick="pickDate();" id="imgFechaProveedor" name="imgFechaProveedor" class="puntero"></div>-->
                                <script type="text/javascript">
//                                    Calendar.setup({
//                                    inputField : "txtFecha",
//                                    ifFormat : "%m-%Y",
//                                    button : "imgFechaProveedor"
//                                });
                                </script>
                           </td>
                       </tr>
                       </table>
                    </td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Estado:</td>
					<td id="tdSelEstado">
                  	  <select id="selEstado" name="selEstado"> 
                            <option value="-1">Seleccione</option>
                        </select>
                    </td>
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td><input type="text" name="txtBusq" id="txtBusq" onkeyup="document.getElementById('btnBuscar').click();"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarDeposito(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); document.getElementById('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>		
                </table>
                </form>
            </td>
        </tr>
        <tr>
        	<td id="tdListadoDeposito"></td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                	<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                	<td align="center">
                    	<table>
                        <tr>
                        	<td><img src="../img/iconos/ico_tesoreria.gif"></td>
                            <td>Tesoreria</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_caja_vehiculo.gif"></td>
                            <td>Caja Vehiculos</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_caja_rs.gif"></td>
                            <td>Caja Repuestos y Servicios</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_ingregos_bonificaciones.gif"></td>
                            <td>Ingresos Por Bonificaciones</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_otros_ingresos.gif"></td>
                            <td>Otros Ingresos</td>
                        </tr>
                        </table>
                        <table>
                        	<tr>
							<td><img src="../img/iconos/ico_rojo.gif"></td>
                            <td>Por Aplicar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"></td>
                            <td>Aplicada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif"></td>
                            <td>Conciliada</td>
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
    <form id="frmDeposito" name="frmDeposito">
    <table border="0" id="tblBanco" width="810">
    	<tr>
    		<td>
    			<fieldset><legend><span style="color:#990000">Datos Empresa</span></legend>
    			<table width="100%">
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td colspan="3" align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                                <td><button type="button" id="btnListEmpresa"  name="btnListEmpresa" onclick="xajax_listEmpresa();" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                </table>
                </fieldset>
             </td>
             <td>  
    			<fieldset><legend><span style="color:#990000">Datos Bancos</span></legend>
    			<table width="100%">
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Banco:</td>
                    <td colspan="3" align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly"/>
                                    <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
                                </td>
                                <td><button type="button" id="btnListBanco" name="btnListBanco" onclick="xajax_listBanco();" title="Seleccionar Beneficiario"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Cuentas:</td>
                    <td colspan="3" id="tdSelCuentas"><select name="selCuenta" id="selCuenta"><option value="-1">Seleccione</option></select></td>
                    <td colspan="3" id="tdSelCuentas1"><input type="text" id="txtCuentaBanco" name="txtCuentaBanco" size="25" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr id="trSaldoCuenta">
                    <td align="right" class="tituloCampo" width="120">Saldo Cuenta:</td>
                    <td colspan="3"><input type="text" id="txtSaldoCuenta" name="txtSaldoCuenta" size="25" readonly="readonly" style="text-align:right"/></td>
                </tr>
                </table>
                </fieldset>
              </td>
           </tr>
           <tr>
              <td colspan="2">
                <fieldset><legend><span style="color:#990000">Datos del Deposito</span></legend>
                <table width="100%">
                <tr id="trFolio">
                    <td align="right" class="tituloCampo" width="120" >Folio Deposito:</td>
                    <td align="left"><input type="text" id="txtFolioDeposito" name="txtFolioDeposito" size="25" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Fecha Registro:</td>
                   <td align="left">
                    <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <input type="text" name="txtFechaRegistro" id="txtFechaRegistro" readonly="readonly"/>
                                </td>
                                <td>
<!--                                    <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaRegistro" name="imgFechaRegistro" class="puntero noprint"/>-->
                                    <script type="text/javascript">
//                                        Calendar.setup({
//                                        inputField : "txtFechaRegistro",
//                                        ifFormat : "%d-%m-%Y",
//                                        button : "imgFechaRegistro"
//                                        });
                                    </script>
<!--                                    </div>-->
                               </td>
                           </tr>
                       </table>
					</td>  
                    <td align="right" class="tituloCampo" width="120">Fecha Aplicacion:</td>
                    <td align="left"><input type="text" id="txtFechaAplicacion" name="txtFechaAplicacion" size="25" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr id="trCosiliacion">
					<td align="right" class="tituloCampo" width="120" >Fecha Conciliacion:</td>
                    <td align="left"><input type="text" id="txtFechaConsiliacion" name="txtFechaConsiliacion" size="25" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Numero Planilla de Deposito:</td>
                    <td colspan="3"><input type="text" id="txtNumeroPlanilla" name="txtNumeroPlanilla" size="25" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Observacion:</td>
                    <td colspan="3"><textarea name="txtObservacion" cols="72" rows="2" id="txtObservacion"></textarea></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Estado:</td>
                    <td><input type="text" id="txtEstado" name="txtEstado" size="25" readonly="readonly"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Motivo</td>
                    <td colspan="4" id="tdSelMotivo">
                        <table>
                            <tr>
                                <td><input type="text" size="6" readonly = "readonly" id="selMotivo" name="selMotivo" /></td>
                                <td><button title="Seleccionar Motivo" onclick="xajax_buscarMotivo();" id="btnListMotivo" type="button"><img src="../img/iconos/ico_pregunta.gif"></button></td>
                                <td><input type="text" size="45" readonly = "readonly" id="txtSelMotivo" /></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                </table>
                </fieldset>
                
                <fieldset><legend><span style="color:#990000">Detalles del Deposito</span></legend>
                <table border="0" width="100%">
                <tr align="right">
                	<td>
                    	<table>
                        <tr>
                            <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Total Efectivo:</td>
	                       	<td><input type="text" id="txtTotalEfectivo" name="txtTotalEfectivo" size="25" style="text-align:right" onkeypress="return validarSoloNumerosReales(event);" onkeyup="xajax_actualizarMonto(xajax.getFormValues('frmDeposito'));"/></td>
                        </tr>
                        </table>
                    </td>    
                </tr>
                <tr>
                    <td>
                        <table id ="tblCheques" width="100%">
                            <tr>
                                <td>
                                    <hr><button type="button" id="btnInsertarArt" name="btnInsertarArt" onclick="xajax_formAgregarCheques();" title="Agregar Cheques"><img src="../img/iconos/ico_agregar.gif"/></button>
                                    &nbsp;
                                    <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminaElementos(xajax.getFormValues('frmDeposito'));" title="Eliminar Articulo"><img src="../img/iconos/ico_quitar.gif"/></button>
                                    <div style="max-height:150px; overflow:auto; padding:1px">
                                    <table border="0" width="100%">
                                    <tr class="tituloColumna">
                                        <td></td><!-- Para el checked solo cuando es nuevo -->
                                        <td align="center" width="20%">Banco</td>
                                        <td align="center" width="30%">N&uacute;mero de Cuenta</td>
                                        <td align="center" width="30%">N&uacute;mero de Cheque</td>
                                        <td align="center" width="20%">Monto</td>        	
                                    </tr>
                                    <tr id="trMontosDepositos"></tr>
                                    </table>
                                    </div>
                                </td>
                            </tr>
                        </table>
                     </td>
                </tr>
                <tr>
                	<td id="tdListadoCheques"></td>
                </tr>
                <tr align="right">
                    <td>
                        <table>
                        <tr>
                            <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Total Cheques:</td>
                            <td><input type="text" id="txtTotalCheques" name="txtTotalCheques" size="25" readonly="readonly" onchange="xajax_actualizarMonto(xajax.getFormValues('frmDeposito'));" style="text-align:right"/>
                            <input type="hidden" id="hddObj" name="hddObj"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="right">
                    <td>
                        <table>
                        <tr>
                            <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Total a Depositar:</td>
                            <td><input type="text" id="txtTotalDeposito" name="txtTotalDeposito" size="25" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                     </td>
                  </tr>
                </table>
                </fieldset>
    		</td>
    	</tr>
    	<tr>
    		<td align="right" id="tdDepositoBotones" colspan="2"><hr><input type="button" onclick="document.getElementById('divFlotante').style.display='none';" value="Cancelar"></td>
    	</tr>
    </table>
    </form>
    <table id="tblConsulta">
        <tr>
        	<td id="tdConsultaNotas"></td>
        </tr>
    </table>
    </div>
 
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td></tr></table></div>
<form id="frmMonto" name="frmMonto" style="margin:0">
    <table border="0" id="tblMontos" width="400px">
    <tr>
        <td>
            <table width="100%">
            <tr>
                <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Banco:</td>
                <td  width="15%"><input type="text" id="txtBancoCheque" name="txtBancoCheque" readonly="readonly" size="20"/>
                				 <input type="hidden" id="hddIdBancoCheque" name="hddIdBancoCheque"/>
                                 <button type="button" id="btnListBanco1" name="btnListBanco1" onclick="xajax_listBanco1();" title="Seleccionar Beneficiario"><img src="../img/iconos/ico_pregunta.gif"/></button>
                </td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Numero Cuenta:</td>
                <td  width="20%"><input type="text" id="txtNumeroCuentaCheque" name="txtNumeroCuentaCheque" size="35" onkeypress="return validarSoloNumerosReales(event);"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Numero de Cheque:</td>
                 <td><input type="text" id="txtNumeroCheque" name="txtNumeroCheque"  onkeypress="return validarSoloNumerosReales(event);"></td>
                 <hr>
            </tr>
            <tr>
            	
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto Cheque:</td>
                <td><input type="text" id="txtMontoCheque" name="txtMontoCheque" onkeypress="return validarSoloNumerosReales(event);"></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right" width="15%">
            <hr>
            <input type="button" id="" name="" onclick="validarFormFormBancoCheque();" value="Guardar">
            <input type="button" id="" name="" onclick="document.getElementById('divFlotante3').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
    </form>
</div>   
<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%">Seleccionar Beneficario / Proveedor</td></tr></table></div>
    
    <table border="0" width="700px">
    <tr>
    	<td>
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdBeneficiarios" width="120px">Beneficiarios</td>
                        <td class="rafktabs_title" id="tdProveedores" width="120px">Proveedores</td>
		            </tr>
					</table>
				</td>
            </tr>
            <tr>
				<td class="rafktabs_panel" id="tdContenido"></td>
            </tr>
            </table>
        </td>
    </tr>
	<tr>
    	<td align="right">
			<hr>
			<input type="button" onclick="document.getElementById('divFlotante1').style.display='none';" value="Cancelar">
		</td>
    </tr>
    </table>
  
</div>
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
        <table border="0" id="tblListados2" style="display:none" width="610">
        <tr>
            <td id="tdDescripcionArticulo">
                <table width="100%">
                <tr class="tituloColumna">
                    <td>Orden</td>
                    <td>Nº Orden Propio</td>
                    <td>Nº Referencia</td>
                    <td>Fecha</td>
                    <td>Proveedor</td>
                    <td>Articulos</td>
                    <td>Pedidos</td>
                    <td>Pendientes</td>
                    <td>Total</td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr>
                <input type="button" id="" name="" onclick="document.getElementById('divFlotante2').style.display='none';" value="Cancelar">
            </td>
        </tr>
        </table>
</div>


<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo4" class="handle"><table><tr><td id="tdFlotanteTitulo4" width="100%">Motivos</td></tr></table></div>
        <table border="0" id="tblListados4" width="610">
        <tr>
            <td>
                <form onsubmit="return false;" style="margin:0" name="frmBuscarMotivo" id="frmBuscarMotivo">
                    <table align="right">
                    <tbody><tr align="left">
                        <td width="120" align="right" class="tituloCampo">Criterio:</td>
                        <td><input type="text" onkeyup="byId('btnBuscarMotivo').click();" class="inputHabilitado" name="txtCriterioBuscarMotivo" id="txtCriterioBuscarMotivo"></td>
                        <td>
                            <button onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'));" name="btnBuscarMotivo" id="btnBuscarMotivo" type="button">Buscar</button>
                            <button onclick="document.forms['frmBuscarMotivo'].reset(); byId('btnBuscarMotivo').click();" type="button">Limpiar</button>
                        </td>
                    </tr>
                    </tbody></table>
                </form>
            </td>
        </tr>
            
        <tr>
            <td id="tdListSelMotivo">
                
            </td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr>
                <input type="button" id="" name="" onclick="document.getElementById('divFlotante4').style.display='none';" value="Cancelar"/>
            </td>
        </tr>
        </table>
</div>


<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);

	var theHandle = document.getElementById("divFlotanteTitulo3");
	var theRoot   = document.getElementById("divFlotante3");
	Drag.init(theHandle, theRoot);

	var theHandle = document.getElementById("divFlotanteTitulo1");
	var theRoot   = document.getElementById("divFlotante1");
	Drag.init(theHandle, theRoot);

	var theHandle = document.getElementById("divFlotanteTitulo2");
	var theRoot   = document.getElementById("divFlotante2");
	Drag.init(theHandle, theRoot);

        //listado motivos
	var theHandle = document.getElementById("divFlotanteTitulo4");
	var theRoot   = document.getElementById("divFlotante4");
	Drag.init(theHandle, theRoot);
</script>

<script>
xajax_listadoDeposito(0,'fecha_registro','DESC','' + '|' + -1 + '|' + 0 + '|' + '');
xajax_comboEmpresa(xajax.getFormValues('frmBuscar'));
xajax_comboEstado(xajax.getFormValues('frmBuscar'));
</script>