<?php
require_once ("../connections/conex.php");

session_start();

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_te_list_transferencia_anulada.php");


$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Histórico Transferencias</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
            <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
        
<!--	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
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
        
        .tabla-propuesta{
           border: 1px solid #999999;
           border-collapse: collapse; 
        }
        .tabla-propuesta td{
            border: 1px solid #999999;
            padding: 7px;
        }
        .tabla-propuesta th{
            border: 1px solid #999999;
            background-color: #f0f0f0;
            padding: 7px;
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
		target:"txtFecha1",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"red"
	});
};
    
    
    function limpiarPropuesta(){        
        document.getElementById('numeroPropuestaPago').innerHTML = "";
        document.getElementById('fechaPropuestaPago').innerHTML = "";
        document.getElementById('numeroTransferenciaPropuestaPago').innerHTML = "";
        document.getElementById('estadoPropuestaPago').innerHTML = "";
        document.getElementById('detallePropuestaPago').innerHTML = "";
    }
</script>
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include ('banner_tesoreria.php'); ?>
    </div>

    <div id="divInfo" class="print">
		<table width="100%">
            <tr>
                <td class="tituloPaginaTesoreria" colspan="2" id="tdReferenciaPagina"></td>
            </tr>
            <tr>
                <td align="right">
                    <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarTransferencia(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                    <table width="100%" border="0" align="right">
                        
                        <tr style="white-space:nowrap;">
                            <td></td>
                            
                            
                            <td width="120" align="right" class="tituloCampo">Empresa / Sucursal:</td>
                            <td width="50" id="tdSelEmpresa" align="left">
                                <select id="selEmpresa" name="selEmpresa">
                                    <option value="-1">Seleccione</option>
                                </select>
                            </td>
                            
                           
                            
                            <td></td>
                        </tr>
                        <tr style="white-space:nowrap;" >
                            <td></td>
                            
                            
                            
                        <td width="120" align="right" co class="tituloCampo" >Fecha Transferencia:</td>
                        <td>
                                <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>   
                                            Desde:<input style="width:120px;" type="text" name="txtFecha" id="txtFecha" readonly="readonly" value=""/>
                                            Hasta:<input style="width:120px;" type="text" name="txtFecha1" id="txtFecha1" readonly="readonly" value=""/>                                   
                                        </td>
                                    </tr>
                                </table>
                        </td>
                        <td align="right" class="tituloCampo">
                            Concepto:
                        </td>
                        <td>
                            <input type="text" name="conceptoBuscar" id="conceptoBuscar" style="width:120px;" ></input>                                            
                        </td>
                        <td></td>
                        </tr>
                        <tr align="right">
                            <td></td>
                            
                            <td align="right" class="tituloCampo">
                                Benef. Prov.:
                            </td>
                            <td>
                                <input type="text" style="width:45px" id="idProveedorBuscar" name="idProveedorBuscar" readonly="readonly"/>
                                <input type="text" size="30" id="nombreProveedorBuscar" name="nombreProveedorBuscar" readonly="readonly"/>                                
                                <button type="button" style="vertical-align: middle;" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));" title="Seleccionar Beneficiario o Proveedor"><img src="../img/iconos/ico_pregunta.gif"></button>
                            </td>
                            <td align="right"  width="120" class="tituloCampo" >Nº Transferencia:</td>
                            <td width="50" align="left"><input style="width:120px;" type="text" name="txtBusq" id="txtBusq" onkeyup="document.getElementById('btnBuscar').click();"/>
                                
                            </td>
                            <td width="350">
                                <button type="button" name="btnBuscar" id="btnBuscar" onclick="xajax_buscarTransferencia(xajax.getFormValues('frmBuscar'));" >Buscar</button>
                                <button type="button" onclick="document.forms['frmBuscar'].reset(); document.getElementById('btnBuscar').click();" >Ver Todo</button>
                            </td>
                           
                        </tr>			
                    </table>
                    </form>
                </td>
            </tr>
            <tr>
                <td id="tdListadoTransferencias"></td>
            </tr>
            
                    </table>
                </td>
            </tr>
		</table>
    </div>
    <div class="noprint">
	<?php include ('pie_pagina.php'); ?>
    </div>
</div>
</body>
</html>



<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    <form id="frmTransferencia" name="frmTransferencia">
    <table border="0" id="tblTransferenciaNuevo" width="810">
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
                                    <td><button type="button" id="btnListEmpresa"  name="btnListEmpresa" disabled="disabled" onclick="xajax_listEmpresa();" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
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
                            
                                    <td><button type="button" id="btnListBanco" name="btnListBanco" disabled="disabled" onclick="xajax_listBanco();" title="Seleccionar Beneficiario"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                                </tr>
    						</table>
    					</td>
    				</tr>
    				<tr>
                    	<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Cuentas:</td>
                    	<td colspan="3" id="tdSelCuentas"><select name="selCuenta" id="selCuenta"><option value="-1">Seleccione</option></select></td>
                    </tr>
                    <tr id="trSaldoCuenta">
                        <td align="right" class="tituloCampo" width="120">Saldo Cuenta:</td>
                        <td colspan="3">
                            <input type="hidden" id="hddIdCuenta" name="hddIdCuenta" />
                            <input type="hidden" id="hddSaldoCuenta" name="hddSaldoCuenta" />
                            <input type="text" id="txtSaldoCuenta" name="txtSaldoCuenta" size="25" readonly="readonly" style="text-align:right"/>
                        </td>
                        
                        <td align="right" class="tituloCampo" width="110">Diferido:</td>
                            <td align="left" width="200">
                            <input type="text" id="txtDiferido" name="txtDiferido" readonly="readonly" style="text-align:right" /> 
                            <input type="hidden" id="hddDiferido" name="hddDiferido" />
                            </td>
                    </tr>
                </table>
                </fieldset>
              </td>
           </tr>
           <tr>
              <td colspan="2">
                <fieldset><legend><span style="color:#990000">Datos del Beneficiario o Proveedor</span></legend>
                <table width="100%" border="0">
                	<tr>
                    	<td class="tituloCampo" width="25%" align="right">
                            <span class="textoRojoNegrita">*</span>Beneficiario o Proveedor:
                        </td>
                        <td width="10%" align="left">
                        	<table>
                            	<tr>
                                	<td>
                                        <input type="text" id="txtIdBeneficiario" name="txtIdBeneficiario" readonly="readonly" size="10"/>
                                    </td>
                                    <td>
                                    	<input type="hidden" id="hddBeneficiario_O_Provedor" name="hddBeneficiario_O_Provedor" />
                                    <button type="button" disabled="disabled"   >
                                            <img src="../img/iconos/ico_pregunta.gif"/>
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="tituloCampo" align="right">
                            <span class="textoRojoNegrita">*</span>C.I:/RIF:
                        </td>
                        <td align="left" colspan="1">
                            <input type="text" id="txtCiRifBeneficiario" name="txtCiRifBeneficiario" readonly="readonly" size="30" />
                        </td>
                    </tr>
                    <tr>
                        <td class="tituloCampo" align="right" width="20%">
                            <span class="textoRojoNegrita">*</span>Nombre:
                        </td>
                        <td align="left">
                            <input type="text" id="txtNombreBeneficiario" name="txtNombreBeneficiario" readonly="readonly" size="50" />
                        </td>
                        <td class="tituloCampo" align="right">
                            <span class="textoRojoNegrita">*</span>Retencion ISLR:
                            <input type="hidden" id="hddMontoMayorAplicar" name="hddMontoMayorAplicar" />
                            <input type="hidden" id="hddPorcentajeRetencion" name="hddPorcentajeRetencion" />
                            <input type="hidden" id="hddCodigoRetencion" name="hddCodigoRetencion" />
                            <input type="hidden" id="hddSustraendoRetencion" name="hddSustraendoRetencion" />
                        </td>
                        <td align="left" id="tdRetencionISLR">

                        </td>
                    </tr>
                    
                    
                    <tr>
                        <td class="tituloCampo" align="right" width="20%">
                            <span class="textoRojoNegrita">*</span>Numero Cuenta:
                        </td>
                        <td align="left">
                            <input type="text" id="txtNumCuenta" name="txtNumCuenta" size="30" />
                        </td>
                    </tr>
                </table>
                </fieldset>
               
                <fieldset><legend><span style="color:#990000">Detalles de la factura</span></legend>
                <table border="0" width="100%">
                	<tr>
                    	<td class="tituloCampo" width="15%" align="right"><span class="textoRojoNegrita">*</span>Factura:</td>
                        <td width="10%" align="left">
                        	<table>
                            	<tr>
                                	<td>
                                        <input type="text" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="10"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btnInsertarFactura" name="btnInsertarFactura" disabled="disabled" title="Seleccionar Factura" >
                                            <img src="../img/iconos/ico_pregunta.gif"/>
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="tituloCampo" width="10%" align="right">Numero</td>
                        <td><input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" />
                        <td class="tituloCampo" width="15%" align="right" id="tdSaldoFactura">Saldo Factura</td>
                        <td id="tdTxtSaldoFactura"><input type="text" id="txtSaldoFactura" name="txtSaldoFactura" readonly="readonly" />
                    </tr>
                    <tr>
                        <td class="tituloCampo" align="right">Fecha Registro</td>
                        <td align="left" colspan="1">
                            <input type="text" id="txtFechaRegistroFactura" name="txtFechaRegistroFactura" readonly="readonly" size="15" />
                        </td>
                        <td class="tituloCampo" align="right" width="20%">Fecha Vencimiento</td>
                        <td align="left">
                            <input type="text" id="txtFechaVencimientoFactura" name="txtFechaVencimientoFactura" readonly="readonly" size="15" />
                        </td>
                        
                        <td class="tituloCampo" align="right" width="20%">Base Imponible</td>
                        <td align="left">
                            <input type="text" id="hddBaseImponible" name="hddBaseImponible" readonly="readonly" size="15" />
                        </td>
                    </tr>
                    <tr>
                        <td class="tituloCampo" align="right">Descripción</td>
                        <td align="left" colspan="4">
                            <textarea id="txtDescripcionFactura" name="txtDescripcionFactura" readonly="readonly" cols="55">
                            </textarea>
                            <input type="hidden" id="hddIva" name="hddIva" />
                            <input type="hidden" id="hddBaseImponible" name="hddBaseImponible" />
                            <input type="hidden" id="hddMontoExento" name="hddMontoExento" />
                            <input type="hidden" id="hddTipoDocumento" name="hddTipoDocumento" />
                        </td>
                    <td>
                        <table width="100%" border="0">
                            <tr>
                                <td id="tdFacturaNota" style="white-space:nowrap;" class="divMsjInfo2" align="center">SIN DOCUMENTO</td>
                    </tr>
                        </table>
                    </td>
                </tr>
                </table>
                </fieldset>
                
                <fieldset><legend><span style="color:#990000">Detalles Transferencia</span></legend>
                <table border="0" width="100%">
                <tr>
                <td class="tituloCampo" align="right" width="10%">
                    Fecha:
                </td>
                <td align="left" width="20%">
                    <input type="text" id="txtFechaRegistro" name="txtFechaRegistro" readonly="readonly" size="30"/>
                </td>
            </tr>
            <tr>
                <td class="tituloCampo" align="right">
                     <span class="textoRojoNegrita">*</span>Número Transferencia:
                
                </td>
                <td>
                <input type="text" id="numTransferencia" name="numTransferencia" size="25" style="text-align:left" onblur="validarMonto()"/>
                </td>

            </tr>
            <tr>
                <td class="tituloCampo" align="right">
                    <span class="textoRojoNegrita">*</span>Observación:
                </td>
                <td colspan="4" align="left">
                    <textarea id="txtComentario" name="txtComentario" cols="48" rows="2"  onkeyup="validarLongitud('txtComentario');" onblur="validarLongitud('txtComentario');  validarMonto()"></textarea>
                </td>
            </tr>
                <tr>
                    <td colspan="6">
                        <table id ="tblCheques" width="100%">
                            <tr>
                                <td>
                                    <hr>
                                    <div style="max-height:150px; overflow:auto; padding:1px">
                                    <table border="0" class="tabla" cellpadding="2" width="97%" style="margin:auto;">
                                    	<tr>
                                            <td class="tituloCampo" align="right">
                                                <span class="textoRojoNegrita">*</span>Monto: 
                                            </td>
                                            <td colspan="2" align="left">
                                                <input type="text" id="txtMonto" name="txtMonto" size="30" style="text-align:right" onkeypress="return validarSoloNumerosReales(event);" onblur="validarMonto();" onfocus="document.getElementById('btnAceptar').disabled = ''"/>
                                            </td>
                                            <td class="tituloCampo" align="right" id="tdTextoRetencionISLR" style="display:none">
                                                <span class="textoRojoNegrita">*</span>Retencion ISLR: 
                                            </td>
                                            <td colspan="2" align="left" id="tdMontoRetencionISLR" style="display:none">
                                                <input type="text" id="txtMontoRetencionISLR" name="txtMontoRetencionISLR" size="30" style="text-align:right" readonly="readonly" />
                                            </td>
                                        </tr>
                                    </table>
                                    </div>
                                </td>
                            </tr>
                        </table>
                     </td>
                </tr>
                </table>
                </fieldset>
    		</td>
    	</tr>
    	<tr>
    		<td align="right" id="tdDepositoBotones" colspan="2"><hr>
            	<input type="button" onclick="document.getElementById('divFlotante').style.display='none'; " value="Cancelar">
            </td>
    	</tr>
    </table>
    </form>
     
</div>
<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%">Buscar Proveedor</td></tr></table></div>
   	<table id="tblBeneficiariosProveedores" border="0" style="display:none" width="700px">
    <tr>
    	<td>
        	<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="document.getElementById('btnBuscarCliente').click(); return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                        <tr>
                	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                	<td>
                        <input type="text" id="txtCriterioBusqProveedor" name="txtCriterioBusqProveedor" onkeyup="document.getElementById('btnBuscarCliente').click()" />
			</td>
                             <td><input type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));" value="Buscar..."/></td>
                </tr>
					</table>
				</td>
            </tr>
            <tr>
				<td class="rafktabs_panel" id="tdContenido" style="border:0px;"></td>
            </tr>
            </table></form>
        </td>
    </tr>
	<tr>
    	<td align="right">
			<hr>
			<input type="button" onclick="document.getElementById('divFlotante1').style.display='none';" value="Cancelar">
		</td>
          </form>
    </tr>
    </table>
</div>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%">PROPUESTA DE PAGO</td></tr></table></div>
   	<table border="0" width="100%">
            <tr>
                <td class="tituloCampo" style="white-space:nowrap;" align="right">N&uacute;mero de Propuesta</td>
                <td id="numeroPropuestaPago" ></td>
                <td class="tituloCampo" style="white-space:nowrap;"  align="right">Fecha de Propuesta</td>
                <td id="fechaPropuestaPago" ></td>
                <td class="tituloCampo"  style="white-space:nowrap;" align="right">N&uacute;mero de Trasnferencia</td>
                <td id="numeroTransferenciaPropuestaPago" ></td>
                <td class="tituloCampo"  style="white-space:nowrap;" align="right">Estado de Propuesta</td>
                <td id="estadoPropuestaPago" ></td>
            </tr>
        </table>
        <fieldset>
            <legend>
                <span style="color:#990000">Detalles de la Propuesta</span>
            </legend>
            <div id="detallePropuestaPago"></div>
        </fieldset>
    <table border="0"  width="100%">
        <tr>
            <td align="right">
                <hr>
                <input type="button" onclick="document.getElementById('divFlotante3').style.display='none';" value="Cancelar">
            </td>
        </tr>
    </table>
</div>


<script>
xajax_listarTransferenciaAnulada(0,'fecha_registro','DESC','-1|0||');
xajax_comboEmpresa('tdSelEmpresa','selEmpresa','');

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);
</script>