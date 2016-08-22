<?php
require_once ("../connections/conex.php");

session_start();

include ("../inc_sesion.php");

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_cuentas.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
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
    <script type="text/javascript" language="javascript" src="../js/jquery.js" ></script>
    <script type="text/javascript" language="javascript" src="../js/jquery.maskedinput.js" ></script>
   
    <script>
        
    jQuery.noConflict();
            jQuery(function($){
                //$("#customer_phone").mask("9999-9999-99-9999999999",{placeholder:" "});               
            });
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
	function verRetencionPunto(check){
		if(check.checked){
			$('tblpunto').style.display= '';				
		}else{
			$('tblpunto').style.display= 'none';	
		}
	}
	
	
		function validarFormTarjeta(){
			if (validarCampo('selTarjeta','t','lista') == true
				&& validarCampo('txtComision','t','') == true
				
				)
			{
				xajax_insertarTarjetas(xajax.getFormValues('frmCuenta'),xajax.getFormValues('frmTarjeta'));
			} else {
				validarCampo('selTarjeta','t','lista');
				validarCampo('txtComision','t','');
								
				
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
		<table width="100%" align="center">
            <tr>
                <td class="tituloPaginaTesoreria" colspan="2">Mantenimiento de Cuentas</td>
            </tr>
            <tr>
            	<td>
                	<form id="frmBuscarCuenta" name="frmBuscarCuenta">
                    <table align="left" width="100%">
						<tr>
                        	<td width="10%" class="tituloCampo" align="right">
                            	Empresa/Sucursal:
                            </td>
                            <td id="tdSelEmpresa" align="left">
                            	<select>
                                	<option>Seleccione</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="10%" class="tituloCampo" align="right">
                            	Banco:
                            </td>
                            <td id="tdSelBancos" width="30%" align="left">
                            	<select id="selBancos" name="selBancos">
									<option value="0">Todos</option>
								</select>
                            </td>
                            <td align="right" class="tituloCampo" width="30%">Numero Cuenta:</td>
                            <td align="left" width="10%"><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscar').click();" size="30"/></td>
                            <td width="5%"><button id="btnBuscar" name="btnBuscar" type="button" class="noprint" onclick="xajax_listarCuentas(0,'','',$('selBancos').value+'|'+$('txtDescripcionBusq').value + '|' + $('selEmpresa').value);" >Buscar</button>									
                            </td>
                            <td width="5%">
                                <button style="white-space:nowrap;" type="button" class="noprint" onclick="document.forms['frmBuscarCuenta'].reset(); $('btnBuscar').click();" >Ver Todo</button>
                            </td>
						</tr>
                        <tr>
                        	<td align="left" width="25%">
                                    <button type="button" onclick="xajax_levantarDivFlotante();" >Nuevo</button>
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
                    	<input type="text" id="txtNumeroCuenta" name="txtNumeroCuenta" size="25"  />
                        <input type="hidden" id="hddIdCuenta" name="hddIdCuenta" />
					</td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="50%">Tipo Cuenta:</td>
                    <td align="left" id="tdSelTipoCuenta">
                    	<select id="selTipoCuenta" name="selTipoCuenta">
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="50%">Posee Punto de Venta</td>
                    <td><input type="checkbox" id="cbxItm" onclick="verRetencionPunto(this)"/></td>
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
                    <td><input type="text" id="txtSaldoLibros" name="txtSaldoLibros" onkeypress="return validarSoloNumerosReales(event);" /></td>
                    <td align="right" class="tituloCampo" width="50%">Saldo en Libros:</td>
                    <td><input type="text" id="txtSaldoAnteriorConciliado" name="txtSaldoAnteriorConciliado" onkeypress="return validarSoloNumerosReales(event);"/></td>
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
    <td id="tblpunto">
    <fieldset>
    	<legend><b>Comisiones y ISLR Puntos de Venta</b></legend>
            <table border="0" width="100%">

        <tr>
              <td>
                                    <button type="button" id="btnInsertarArt" name="btnInsertarArt" onclick="xajax_levantarDivTarjeta();" title="Agregar Tarjetas"><img src="../img/iconos/ico_agregar.gif"/></button>
                                    &nbsp;
                                    <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminaElementos(xajax.getFormValues('frmCuenta'));" title="Eliminar Tarjeta"><img src="../img/iconos/ico_quitar.gif"/></button>
                                    <div style="max-height:150px; overflow:auto; padding:1px">
                                    <table border="1" class="tabla" cellpadding="2" width="97%" style="margin:auto;">
                                    <tr class="tituloColumna">
                                    <td><input type="hidden" id="hddObj" name="hddObj"/></td>
                                        <td align="center" width="30%">Tarjeta</td>
                                        <td align="center" width="30%">Comision</td>
                                        <td align="center" width="30%">ISLR</td>      	
                                    </tr>
                                    <tr id="trMontosDepositos"></tr>
                                    </table>
                                    </div>
                                </td>
        </tr>
        </table>
        </fieldset>
       </td>
    </tr>  
    <tr align="right">
    	<td align="right"><hr>
        	<input style="display:none" type="button" id="bttGuardar" name="bttGuardar" value="Guardar" onclick="validarCuenta();"/>
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
		</td>
    </tr>   
    </table>

    

    </form>
  
</div>


<div id="divTarjeta" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%">Agregar Tarjeta</td></tr></table></div>
	<form id="frmTarjeta" name="frmTarjeta" onsubmit="return false;">
	<table border="0" id="tblTarjeta">
		<tr>
			<td align="right" class="tituloCampo" >Tarjeta</td>
		<td id="tdSelTarjetas" width="30%" align="left">
                            	<select id="selTarjeta" name="selTarjeta">
									<option value="0">Todos</option>
								</select>
        </td>
        </tr>
		<tr>
			<td align="right" class="tituloCampo">Comision:</td>
			<td><label>
				<input name="txtComision" id="txtComision" type="txt" class="inputInicial" />
			</label>
            <input type="hidden" id="hddId" name="hddId"/></td>
		</tr>
       
       <tr>
			<td align="right" class="tituloCampo">ISLR:</td>
			<td><label>
				<input name="txtISLR" id="txtISLR" type="txt" class="inputInicial" />
                
			</label></td>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>
		<tr>
			<td align="right" colspan="2">
			<input type="button" id="btnGuardar" name="" onclick="validarFormTarjeta();" value="Guardar">
            <input type="button" id="GuardarModifica" name="" onclick="xajax_guardarCambioTarjeta(xajax.getFormValues('frmTarjeta'));" value="Guardar">
            <input type="button" id="GuardarNuevo" name="" onclick="xajax_insertarNuevaTarjeta(xajax.getFormValues('frmTarjeta'));" value="Guardar">
			<input type="button" onclick="$('divTarjeta').style.display='none';" value="Cancelar">
			</td>
		</tr>
	</table>
    </form>
</div>

<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("divFlotanteTitulo1");
	var theRoot   = document.getElementById("divTarjeta");
	Drag.init(theHandle, theRoot);
        
</script>