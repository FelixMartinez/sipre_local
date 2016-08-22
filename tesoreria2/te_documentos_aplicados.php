<?php
require_once ("../connections/conex.php");
/* Validación del Módulo */
//include('../inc_sesion.php');
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

include("controladores/ac_te_documentos_aplicados.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria Documentos Para Aplicar</title>
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

    
    
    
	<script>
            
window.onload = function(){
	
  new JsDatePick({
		useMode:2,
		target:"fechaAplicada1",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"red"
	});
        
   new JsDatePick({
		useMode:2,
		target:"fechaAplicada2",
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
        
		
	function validarFormPermiso() {
		if (validarCampo('txtContrasena','t','') == true 
			&& validarCampo('txtObservacion','t','') == true) {
			
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'),xajax.getFormValues('frmBuscar'));
			
		} else {
			validarCampo('txtContrasena','t','');
			validarCampo('txtObservacion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
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
	<div class="noprint" align="center"><?php include ('banner_tesoreria.php'); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaTesoreria">Aplicar Documentos<br/></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                                <td ><button type="button" id="btnListEmpresa" name="btnListEmpresa" onclick="xajax_listEmpresa();" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                            </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="10">Fecha:</td>
                    <td align="left">
                       Desde:<input type="text" id="fechaAplicada1" name="fechaAplicada1" size="8" readonly="readonly" value="<?php echo date( '01-m-Y' ); ?>" />  
                      
                       Hasta:<input type="text" id="fechaAplicada2" name="fechaAplicada2" size="8" readonly="readonly" value="<?php echo date( 't-m-Y' ); ?>" />                                
                    
                    </td>
                    
                    
				</tr>
				<tr align="left">
                                    <td></td>
                                    <td></td>
                    <td align="right" class="tituloCampo" width="120">Banco:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly" /><input type="hidden" id="hddIdBanco" name="hddIdBanco"/></td>
                                <td><button type="button" id="btnListBanco" name="btnListBanco" onclick="xajax_listBanco();" title="Seleccionar Banco"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                            </tr>
                        </table>
                    </td>
                    <td class="tituloCampo" align="right" width="120">Nro. Cuenta:</td>
                    <td id="tdSelCuenta" align="left">
                        <select id="selCuenta" name="selCuenta">
                            <option value="-1">Seleccione</option>
                        </select>
                    </td>
                    <td colspan="2">
                            <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarEstadoCuenta(xajax.getFormValues('frmBuscar'));">Buscar</button>
                            <button type="button" onclick="document.forms['frmBuscar'].reset();  document.getElementById('fechaAplicada1').value='';  document.getElementById('fechaAplicada2').value = '';  document.getElementById('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>			
                </table>
                </form>
            </td>
        </tr>
        </table>
        
        <table width="100%">
        <tr>
        	<td valign="top">
            	<form id="frmListadoEstadoCuenta" name="frmListadoEstadoCuenta">
            	<fieldset><legend><span style="color:#990000">Documentos Por Aplicar</span></legend>
        		<table width="100%">
            	<tr>
        			<td id="tdListadoEstadoCuenta"></td>
                </tr>
                </table>
            	</fieldset>
                </form>
            </td>
            <td valign="top">
            	<form id="frmListadoEstadoCuenta1" name="frmListadoEstadoCuenta1">
            	<fieldset><legend><span style="color:#990000">Documentos Aplicados</span></legend>
                <table width="100%">
                <tr>
		            <td id="tdListadoEstadoCuenta1"></td>
                </tr>
                </table>
             	</fieldset>
                </form>
            </td>
        </tr>
         <tr>
        	<td colspan="2">
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                	<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                	<td align="center">
                    	<table>
                        <tr>
                            <td><img src="../img/iconos/ico_rojo.gif" /></td>
                            <td>Por Aplicar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif" /></td>
                            <td>Aplicado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif" /></td>
                            <td>Concialiado</td>
                            <td><img src="../img/iconos/ico_agregar.gif" /></td>
                            <td>Aplicar Documentos</td>
                            <td><img src="../img/iconos/ico_quitar.gif" /></td>
                            <td>Desaplicar Documentos</td>
                            <td><img src="../img/iconos/ico_comentario.png" /></td>
                            <td>Comentarios</td>
                            <td><img src="../img/iconos/ico_comentario_f2.png" /></td>
                            <td>Sin Comentarios</td>
                        </tr>
                        </table>
                    </td>
                </tr>
				</table>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint" align="center">
	<?php include("pie_pagina.php") ?>
    </div>
</div>
</body>
</html>
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
        <table border="0" id="tblListados" style="display:none" width="610">
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
                <input type="button" id="" name="" onclick="document.getElementById('divFlotante').style.display='none';" value="Cancelar" />
            </td>
        </tr>
        </table>
        
        
    <form id="frmPermiso" name="frmPermiso" onsubmit="validarFormPermiso(); return false;" style="margin:0px">
    <table border="0" id="tblPermiso" style="display:none" width="450px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="68%">
                	<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" size="30" value="te_documentos_aplicados.php"/>
                    <input type="hidden" id="hddIdEstadoCuenta" name="hddIdEstadoCuenta" size="30"/>
				</td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Observacion:</td>
                <td>
                	<textarea  id="txtObservacion" name="txtObservacion" cols="45" rows="5"></textarea>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" onclick="validarFormPermiso();" value="Aceptar">
            <input type="button" onclick="document.getElementById('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
    </form>
</div>
<script>
//xajax_listadoNotaDebito(0,'','','' + '|' + -1 + '|' + 0 + '|' + '' + '|' + '' + '|' + '');
xajax_listadoEstadoCuenta(0,'','','' + '|' + '-1' + '|' + '' + '|' + '-1');
xajax_buscarEstadoCuenta(xajax.getFormValues('frmBuscar'));
</script>

<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>