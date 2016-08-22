<?php
require_once ("../connections/conex.php");
/* Validación del Módulo */
include('../inc_sesion.php');
//validaModulo("an_modificar_vehiculo");
/* Fin Validación del Módulo */

@session_start();

//require_once('clases/rafkLista.php');
$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_te_estado_cuenta.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Estado de Cuentas</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
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
         
window.onload = function(){
	
  new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"red"
	});
        
   new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"red"
	});
	
};
         
	function imprimir(){
            window.open("reportes/te_estado_cuenta_excel.php?IdEmpresa=" + document.getElementById('hddIdEmpresa').value + "&Cuenta=" + document.getElementById('selCuenta').value + "&FechaDesde=" + document.getElementById('txtFecha').value + "&FechaHasta=" + document.getElementById('txtFechaHasta').value + "&Estado=" + document.getElementById('selEstado').value + "&TipoDoc=" + document.getElementById('selTipoDoc').value);	
	}			
	</script>
    
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include ('banner_tesoreria.php'); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaTesoreria">Estado de Cuenta<br /></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<button type="button" id="btnNuevo" onclick="imprimir();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
					</td>
				</tr>
				</table>
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                            <td><button type="button" id="btnListEmpresa" name="btnListEmpresa" onclick="xajax_listEmpresa();" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
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
                    <td align="right" class="tituloCampo" >Nro. Cuenta:</td>
                    <td id="tdSelCuenta">
                        <select id="selCuenta" name="selCuenta">
                            <option value="-1">Seleccione</option>
                        </select>
                    </td>
				</tr>
				<tr align="left">   
                    <td align="right" class="tituloCampo">Estado</td>
                    <td>	
                        <select id="selEstado" name="selEstado">
                            <option value="-1">Todos</option>
                            <option value="1">Por Aplicar</option>
                            <option value="2">Aplicado</option>
                            <option value="3">Conciliado</option>
                        </select> 
                    </td>
                     <td align="right" class="tituloCampo">Tipo Documento</td>
                    <td>	
                        <select id="selTipoDoc" name="selTipoDoc">
                            <option value="">Todos</option>
                            <option value="CH">Cheque</option>
                            <option value="CH ANULADO">Cheque Anulado</option>
                            <option value="NC">Nota Credito</option>
                            <option value="ND">Nota Debito</option>
                            <option value="TR">Transferencia</option>
                            <option value="DP">Deposito</option>
                        </select> 
                    </td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo">Fecha Desde:</td>
					<td align="left">
						<table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" name="txtFecha" id="txtFecha" readonly="readonly" value="<?php echo date( '01-m-Y' ); ?>"/>
                            </td>
                            <td>
<!--                                <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaProveedor" name="imgFechaProveedor" class="puntero" /></div>-->
                                <script type="text/javascript">
//                                    Calendar.setup({
//                                    inputField : "txtFecha",
//                                    ifFormat : "%d-%m-%Y",
//                                    button : "imgFechaProveedor"
//                                });
                                </script>
                           </td>
						</tr>
						</table>
						<td align="right" class="tituloCampo" >Fecha Hasta:</td>
						<td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" name="txtFechaHasta" id="txtFechaHasta" readonly="readonly" value="<?php echo date( 't-m-Y' ); ?>"/>
                            </td>
                            <td>
<!--                                <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero" /></div>-->
                                <script type="text/javascript">
//                                    Calendar.setup({
//                                    inputField : "txtFechaHasta",
//                                    ifFormat : "%d-%m-%Y",
//                                    button : "imgFechaHasta"
//                                });
                                </script>
                           </td>
                	    </tr>
                       </table>
                    </td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarEstadoCuenta(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); document.getElementById('txtFecha').value = ''; document.getElementById('txtFechaHasta').value = ''; document.getElementById('btnBuscar').click();">Limpiar</button>
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
        
        <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                <tbody>
                	<tr>
                        <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                        <td align="center">
                            <table>
                            <tbody><tr>
                                <td><img src="../img/iconos/ico_rojo.gif"></td>
                                <td>Por Aplicar</td>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/ico_amarillo.gif"></td>
                                <td>Aplicado</td>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/ico_verde.gif"></td>
                                <td>Concialiado</td>
                            </tr>
                            </tbody></table>
                        </td>
                	</tr>
				</tbody>
		</table>
    </div>
    <div class="noprint"><?php include("pie_pagina.php") ?></div>
</div>
</body>
</html>
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
</div>
<script>
//xajax_listadoNotaDebito(0,'','','' + '|' + -1 + '|' + 0 + '|' + '' + '|' + '' + '|' + '');
//xajax_listadoEstadoCuenta(0,'','','' + '|' + '-1' + '|' + '' + '|' + '-1');
xajax_buscarEstadoCuenta(xajax.getFormValues('frmBuscar'));


var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>