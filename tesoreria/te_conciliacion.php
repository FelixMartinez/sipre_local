<?php
require_once ("../connections/conex.php");
/* Validación del Módulo */
//include('../inc_sesion.php');
//validaModulo("an_modificar_vehiculo");
/* Fin Validación del Módulo */

@session_start();

//require_once('clases/rafkLista.php');
$currentPage = $_SERVER["PHP_SELF"];

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_conciliacion.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Conciliacion</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
<!--    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
      <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>

    
<!--    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>-->
    
	<script>
            
            
window.onload = function(){
	
  new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"%m-%Y",
		cellColorScheme:"red"
	});
        
   new JsDatePick({
		useMode:2,
		target:"txtFecha1",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"red"
	});
	
};            
  
                        
      function validarFormInsertar(){
			if (validarCampo('txtCiRifBeneficiario','t','') == true
				&& validarCampo('txtNombreEmpresa','t','') == true
				&& validarCampo('txtNumeroCuenta','t','') == true
				&& validarCampo('txtNumeroTransferencia','t','') == true
				&& validarCampo('txtNombreBanco','t','') == true
				&& validarCampo('txtObservacionNotaDebito','t','') == true
				&& validarCampo('txtSaldoCuenta','t','') == true
				&& validarCampo('txtImporteMovimiento','t','monto') == true)
			{
				xajax_guardarNotaDebito(xajax.getFormValues('frmNotaDebito'));
			} else {
				validarCampo('txtCiRifBeneficiario','t','');
				validarCampo('txtNombreEmpresa','t','');
				validarCampo('txtNumeroCuenta','t','');
				validarCampo('txtNumeroTransferencia','t','');
				validarCampo('txtNombreBanco','t','');
				validarCampo('txtObservacionNotaDebito','t','');
				validarCampo('txtSaldoCuenta','t','');
				validarCampo('txtImporteMovimiento','t','monto');
				
				alert("Los campos señalados en rojo son requeridos");
	
				return false;
	
			}
		}
		
		function validarFormMandar(){
			if (validarCampo('txtEmpresaCon','t','') == true
				&& validarCampo('txtNombreBancoCon','t','') == true
				&& validarCampo('txtFecha1','t','') == true
				&& validarCampo('txtSaldoBanco','t','monto') == true)
			{
				xajax_enviarDocs(xajax.getFormValues('frmBuscar1'));
			} else {
				validarCampo('txtEmpresaCon','t','');
				validarCampo('txtNombreBancoCon','t','');
				validarCampo('txtFecha1','t','');
				validarCampo('txtSaldoBanco','t','monto');
				
				
				alert("Los campos señalados en rojo son requeridos");
	
				return false;
	
			}
		}
                
                function abrirAccesoAnulacion(idAnulacion){
                    limpiarAnulacionForm();
                    
                    document.getElementById('idConciliacionEliminar').value = idAnulacion;
                    document.getElementById('divFlotante4').style.display = '';
                    centrarDiv(document.getElementById('divFlotante4'));
                    document.getElementById('claveAnulacion').focus();
                }
                
                function cerrarAccesoClave(){
                    document.getElementById('divFlotante4').style.display='none';
                    limpiarAnulacionForm();
                }
                
                function limpiarAnulacionForm(){
                    document.getElementById('idConciliacionEliminar').innerHTML = '';
                    document.getElementById('nombreEmpresaAnular').innerHTML = '';
                    document.getElementById('fechaConciliacionAnular').innerHTML = '';
                    document.getElementById('nombreBancoAnular').innerHTML = '';
                    document.getElementById('cuentaAnular').innerHTML = '';
                    document.getElementById('restaAnular').innerHTML = '';
                    document.getElementById('saldoCuentaAnular').innerHTML = '';
                    document.getElementById('nuevoSaldoAnular').innerHTML = '';
                    document.getElementById('tblclaveAnularConciliacion').style.display='';
                    document.getElementById('tblAnularConciliacion').style.display='none'; 
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
        	<td class="tituloPaginaTesoreria">Conciliaci&oacute;n<br /></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<button type="button" id="btnNuevo" onclick="xajax_nuevaConciliacion();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
					</td>
				</tr>
				</table>
                <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarEstadoCuenta(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td width="15%" align="right" class="tituloCampo">Empresa:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="left"><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                            <td align="left"><button type="button" id="btnListEmpresa" name="btnListEmpresa" onclick="xajax_listEmpresa();" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                        </tr>
                        </table>
                    </td>
				</tr>
				<tr align="left">
                    <td width="15%" align="right" class="tituloCampo" >Fecha Registro:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" name="txtFecha" id="txtFecha" readonly="readonly"/>
                            </td>
                            <td>
<!--                                <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaProveedor" name="imgFechaProveedor" class="puntero" /></div>-->
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
                    <td align="right" class="tituloCampo">Banco:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly"/><input type="hidden" id="hddIdBanco" name="hddIdBanco"/></td>
                            <td><button type="button" id="btnListBanco" name="btnListBanco" onclick="xajax_listBanco();" title="Seleccionar Banco"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                        </tr>
                        </table>
                    </td>
                    <td width="15%" align="right" class="tituloCampo" >Nro. Cuenta:</td>
                    <td id="tdSelCuenta" align="left">
                        <select id="selCuenta" name="selCuenta">
                            <option value="-1">Seleccione</option>
                        </select>
                    </td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarEstadoCuenta(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); document.getElementById('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>		
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListadoEstadoCuenta"></td>
        </tr>
        </table>
    </div>
    <div class="noprint" align="center"><?php include("pie_pagina.php") ?></div>
</div>
</body>
</html>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td></tr></table></div>
    	<form id="frmBuscar1" name="frmBuscar1" method="post" action="te_conciliacion_proceso.php">
        <table border="0" id="tblNuevaConciliacion" style="display:none" width="420">
        <tr>
        	<td width="30%" align="right" class="tituloCampo" >Empresa:</td>
            <td>
                <table cellpadding="0" cellspacing="0">
                <tr>
                    <td><input type="text" id="txtEmpresaCon" name="txtEmpresaCon" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresaCon" name="hddIdEmpresaCon"/></td>
                    <td><button type="button" id="btnListBancoCon" name="btnListBancoCon" onclick="xajax_listEmpresa1();" title="Seleccionar Banco"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                </tr>
                </table>
       		</td>
        </tr>
        <tr>
        	<td  align="right" class="tituloCampo" >Banco:</td>
            <td>
                <table cellpadding="0" cellspacing="0">
                <tr>
                    <td><input type="text" id="txtNombreBancoCon" name="txtNombreBancoCon" size="25" readonly="readonly"/><input type="hidden" id="hddIdBancoCon" name="hddIdBancoCon"/></td>
                    <td><button type="button" id="btnListBancoCon" name="btnListBancoCon" onclick="xajax_listBanco1();" title="Seleccionar Banco"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        <td  align="right" class="tituloCampo" >Nº Cuenta:</td>
        <td>
            <table>
            <tr>
                <td id="tdSelCuenta1">
                <select id="selCuenta1" name="selCuenta1">
                <option value="-1">Seleccione</option>
                </select>
                </td>
            </tr>
            </table>
        </td>
        </tr>
        <tr>
        	<td  align="right" class="tituloCampo" >Fecha Registro:</td>
            <td>
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <input type="text" name="txtFecha1" id="txtFecha1" readonly="readonly"/>
                </td>
                <td>
<!--                    <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaProveedor1" name="imgFechaProveedor1" class="puntero" /></div>-->
                    <script type="text/javascript">
//                        Calendar.setup({
//                        inputField : "txtFecha1",
//                        ifFormat : "%d-%m-%Y",
//                        button : "imgFechaProveedor1"
//                    });
                    </script>
               </td>
            </tr>
            </table>
            </td>
        </tr>
        <tr>
        	<td  align="right" class="tituloCampo">Saldo Banco:</td>
            <td><input type="text" name="txtSaldoBanco" id="txtSaldoBanco"/></td>
        </tr>
        <tr>
        <td colspan="3" align="right" id="tdBotonesDiv3">
        <hr />
        <input type="button" id="" name="" onclick="validarFormMandar();" value="Siguiente" />
        <input type="button" id="" name="" onclick="document.getElementById('divFlotante2').style.display='none';document.getElementById('divFlotante3').style.display='none';" value="Cancelar" />
        </td>
        </tr>
        </table>
        </form>
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
                <hr />
                <input type="button" id="" name="" onclick="document.getElementById('divFlotante2').style.display='none';" value="Cancelar" />
            </td>
        </tr>
        </table>
        
        <table border="0" id="tblDatosConciliar" style="display:none" width="610">
        <tr>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv2">
                <hr />
                <input type="button" id="" name="" onclick="document.getElementById('divFlotante2').style.display='none';" value="Cancelar" />
            </td>
        </tr>
        </table>
        
</div>



<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo4" class="handle"><table><tr><td id="tdFlotanteTitulo4" width="100%">Anular Conciliación</td></tr></table></div>
        <form name="claveAnularConciliacion" id="claveAnularConciliacion" onsubmit="return false;">
            <table border="0" id="tblclaveAnularConciliacion" width="200">
                <tr style="white-space:nowrap;">
                    <td align="right" class="tituloCampo" >Clave:&nbsp;</td>
                    <td><input type="password" name="claveAnulacion" id="claveAnulacion" /></td>
                </tr>
                
                <tr>
                    <td colspan="4" align="right" id="tdBotonesDiv">
                        <hr />
                        <input type="button" onclick="xajax_accesoAnulacion(document.getElementById('claveAnulacion').value);" value="Acceder" />
                        <input type="button" onclick="cerrarAccesoClave();" value="Cancelar" />                    
                    </td>
                </tr>
            </table>
        </form>
        
        <form name="anularConciliacion" id="anularConciliacion"  onsubmit="return false;">
            <table border="0" id="tblAnularConciliacion" style="display:none; width:350px;">
                
                <tr style="white-space:nowrap;">
                    <td align="right" class="tituloCampo" width="50%">Empresa:&nbsp;</td>
                    <td id="nombreEmpresaAnular" width="50%"></td>
                </tr>
                <tr style="white-space:nowrap;">
                    <td align="right" class="tituloCampo" width="50%">Fecha:&nbsp;</td>
                    <td id="fechaConciliacionAnular" width="50%"></td>
                </tr>
                <tr style="white-space:nowrap;">
                    <td align="right" class="tituloCampo" width="50%">Banco:&nbsp;</td>
                    <td id="nombreBancoAnular" width="50%"></td>
                </tr>
                <tr style="white-space:nowrap;">
                    <td align="right" class="tituloCampo" width="50%">Cuenta:&nbsp;</td>
                    <td id="cuentaAnular" width="50%"></td>
                </tr>
                <tr style="white-space:nowrap;">
                    <td colspan="2" style="text-align:center;">Anulación Conciliación:</td>
                    
                </tr>
                <tr style="white-space:nowrap;">
                    <td align="right" class="tituloCampo" width="50%">Monto Conciliación(C-D):&nbsp;</td>
                    <td id="restaAnular" width="50%"></td>
                </tr>
                <tr style="white-space:nowrap;">
                    <td align="right" class="tituloCampo" width="50%">Saldo Actual Cuenta:&nbsp;</td>
                    <td id="saldoCuentaAnular" width="50%"></td>
                </tr>
                <tr style="white-space:nowrap;">
                    <td align="right" class="tituloCampo" width="50%">Nuevo Saldo al Anular:&nbsp;</td>
                    <td id="nuevoSaldoAnular" width="50%"></td>
                </tr>
                
                
                <tr>
                    <td colspan="4" align="right" id="tdBotonesDiv">
                        <hr />
                        <input type="button" onclick="xajax_anularConciliacion(document.getElementById('idConciliacionEliminar').value);" value="Anular Conciliación" />
                        <input type="button" onclick="cerrarAccesoClave();" value="Cancelar" />
                        <input type="hidden" value="" id="idConciliacionEliminar" />
                    </td>
                </tr>
            </table>
        </form>
        
</div>

<script>
//xajax_listadoNotaDebito(0,'','','' + '|' + -1 + '|' + 0 + '|' + '' + '|' + '' + '|' + '');
//xajax_listadoEstadoCuenta(0,'','','' + '|' + '-1' + '|' + '' + '|' + '-1');
xajax_buscarEstadoCuenta(xajax.getFormValues('frmBuscar'));

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo4");
var theRoot   = document.getElementById("divFlotante4");
Drag.init(theHandle, theRoot);
</script>