<?php
require_once ("../connections/conex.php");

session_start();

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_te_transferencia.php");

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
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Transferencia</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css">
<!--	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
<!--    <script type="text/javascript" language="javascript" src="../js/jquery.js" ></script>-->
    <script type="text/javascript" language="javascript" src="../js/jquery.maskedinput.js" ></script>

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
        
        
         #tdFacturas, #tdNotaCargo, #tdBeneficiarios, #tdProveedores{
            -webkit-border-top-left-radius: 10px;
            -webkit-border-top-right-radius: 10px;
            -moz-border-radius-topleft: 10px;
            -moz-border-radius-topright: 10px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;

            border-color:#CCCCCC;                                  
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
	
  //$("#txtNumCuenta").mask("9999-9999-99-9999999999",{placeholder:" "});//panama no usa
        
    new JsDatePick({
            useMode:2,
            target:"txtFechaRegistro",
            dateFormat:"%d-%m-%Y",
            cellColorScheme:"red"
    });
        
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
        
        
        
	function validarTransferencia(){
		
		if (validarCampo('txtNombreEmpresa','t','') == true
		&&  validarCampo('txtNombreBanco','t','') == true
		&&  validarCampo('selCuenta','t','lista') == true
		&&  validarCampo('txtSaldoCuenta','t','') == true
		&&  validarCampo('txtIdBeneficiario','t','') == true
 		&&  validarCampo('txtCiRifBeneficiario','t','') == true
		&&  validarCampo('txtNombreBeneficiario','t','') == true
		&&  validarCampo('txtFechaRegistro','t','') == true
		&&  validarCampo('txtMonto','t','') == true
		&&  validarCampo('txtNumCuenta','t','') == true
		&&  validarCampo('txtComentario','t','') == true
		&&  validarCampo('numTransferencia','t','') == true
		&&  validarCampo('txtIdFactura','t','') == true
		&&  document.getElementById('txtMonto').value > 0){
			xajax_guardarTransferencia(xajax.getFormValues('frmTransferencia'));
		}
		else {
			validarCampo('txtNombreEmpresa','t','')
			validarCampo('txtNombreBanco','t','')
			validarCampo('selCuenta','t','lista')
			validarCampo('txtSaldoCuenta','t','')
			validarCampo('txtIdBeneficiario','t','')
			validarCampo('txtCiRifBeneficiario','t','')
			validarCampo('txtNombreBeneficiario','t','')
			validarCampo('txtFechaRegistro','t','')
			validarCampo('txtMonto','t','')
			validarCampo('txtNumCuenta','t','')
			validarCampo('txtComentario','t','')
			validarCampo('numTransferencia','t','')
			validarCampo('txtIdFactura','t','')
			
			if	(document.getElementById('txtMonto').value <= 0)
				document.getElementById('txtMonto').className = 'inputErrado';
			
			alert("Los campos señalados en rojo son requeridos");
                        desbloquearGuardado();
         
			return false;
		}
	}
	
	function nuevoTransferencia(idEmpresa){
		document.getElementById('txtNombreEmpresa').className = 'inputInicial';
		document.getElementById('txtNombreBanco').className = 'inputInicial';
		document.getElementById('selCuenta').style.display = 'none';
		document.getElementById('txtSaldoCuenta').className = 'inputInicial';
		document.getElementById('txtIdBeneficiario').className = 'inputInicial';
		document.getElementById('txtCiRifBeneficiario').className = 'inputInicial';
		document.getElementById('txtNombreBeneficiario').className = 'inputInicial';
		document.getElementById('txtFechaRegistro').className = 'inputInicial';
		document.getElementById('txtMonto').className = 'inputInicial';
		document.forms['frmTransferencia'].reset();
		document.getElementById('txtDescripcionFactura').innerHTML = '';
		document.getElementById('selRetencionISLR').disabled = 'disabled';
		xajax_asignarEmpresa(idEmpresa,0);
		document.getElementById('divFlotante1').style.display = 'none';
		document.getElementById('divFlotante').style.display = '';
		document.getElementById('btnAceptar').style.display = '';
		document.getElementById('btnActualizar').style.display = 'none';
		document.getElementById('tdFlotanteTitulo').innerHTML = 'Nueva Transferencia';
		centrarDiv(document.getElementById('divFlotante'));
		document.getElementById('trSaldoCuenta').style.display = '';
		document.getElementById('hddPorcentajeRetencion').value = '0';
		document.getElementById('tdTextoRetencionISLR').style.display = 'none';
		document.getElementById('tdMontoRetencionISLR').style.display = 'none';
		document.getElementById('txtMontoRetencionISLR').value = 0;
		document.getElementById('tdTxtSaldoFactura').style.display = '';
		document.getElementById('tdSaldoFactura').style.display = '';
	}
	
		function validarLongitud(campo){
		if (document.getElementById(campo).value.length > 119){
			var cadena = document.getElementById(campo).value.substring(0,119);
			document.getElementById(campo).value = cadena;
		}
	}
	
	
	function validarMonto(){
		if (parseFloat(document.getElementById('txtMonto').value) > parseFloat(document.getElementById('txtSaldoFactura').value) && document.getElementById('hddPermiso').value == 0){
			/*alert("El monto a pagar no puede ser mayor que el saldo de la factura");
			document.getElementById('btnAceptar').disabled = 'disabled';
			//document.getElementById('txtMontoAPagar').focus();
			return false;*/
                        document.getElementById('btnAceptar').disabled = true;
                        if (confirm('El monto de la propuesta es mayor que el saldo en la cuenta Desea Sobregirar la Cuenta?') == true){
                            document.getElementById('divFlotanteClave').style.display = '';
                            centrarDiv(document.getElementById('divFlotanteClave'));
                            document.getElementById('tdFlotanteTituloClave').innerHTML = 'Aprobación';
                            return false;
                        }else{
                            return false;
                        }
		}
		else{
			if (parseFloat(document.getElementById('txtMonto').value)+parseFloat(document.getElementById('hddDiferido').value) > parseFloat(document.getElementById('hddSaldoCuenta').value) && document.getElementById('hddPermiso').value == 0){
			/*alert('El monto de la Transfrencia no puede ser mayor al saldo de la cuenta');
			document.getElementById('txtMonto').className = 'inputErrado';*/
                        document.getElementById('btnAceptar').disabled = true;
                        if (confirm('El monto de la propuesta es mayor que el saldo en la cuenta Desea Sobregirar la Cuenta?') == true){
                            document.getElementById('divFlotanteClave').style.display = '';
                            centrarDiv(document.getElementById('divFlotanteClave'));
                            document.getElementById('tdFlotanteTituloClave').innerHTML = 'Aprobación';
                            return false;
                        }else{
                            return false;
                        }
		}
		else{
		document.getElementById('txtMonto').className = 'inputInicial';}
			}
			
	}
	
	function validarProveedor(){
		if (validarCampo('txtNombreEmpresa','t','') == true
		&&  validarCampo('txtNombreBanco','t','') == true
		&&  validarCampo('selCuenta','t','lista') == true
		&&  validarCampo('txtSaldoCuenta','t','') == true
		&&  validarCampo('txtIdBeneficiario','t','') == true
		&&  validarCampo('txtCiRifBeneficiario','t','') == true
		&&  validarCampo('txtNombreBeneficiario','t','') == true){
			
			
			xajax_listarFacturas(0,'','',document.getElementById('hddIdEmpresa').value + '|' + document.getElementById('txtIdBeneficiario').value);
                        return true;
		}
		else {
			validarCampo('txtNombreEmpresa','t','')
			validarCampo('txtNombreBanco','t','')
			validarCampo('selCuenta','t','lista')
			validarCampo('txtSaldoCuenta','t','')
			validarCampo('txtIdBeneficiario','t','')
			validarCampo('txtCiRifBeneficiario','t','')
			validarCampo('txtNombreBeneficiario','t','')
					
			alert("Los campos señalados en rojo son requeridos");
			document.getElementById('divFlotante1').style.display = 'none';
			return false;
		}
	}
	
	function calcularRetencion(){
		if ((parseFloat(document.getElementById('txtSaldoFactura').value) >= parseFloat(document.getElementById('hddMontoMayorAplicar').value)) && (document.getElementById('hddPorcentajeRetencion').value > 0) ){
			document.getElementById('tdTextoRetencionISLR').style.display = '';
			document.getElementById('tdMontoRetencionISLR').style.display = '';
			
				if (document.getElementById('hddIva').value == 0){
				var monto_retencion = (document.getElementById('txtSaldoFactura').value * document.getElementById('hddPorcentajeRetencion').value / 100)-( document.getElementById('hddSustraendoRetencion').value);
				document.getElementById('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');
				var monto = (parseFloat(document.getElementById('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
				document.getElementById('txtMonto').value = number_format(monto,'2','.','');
				}
			
				else{
				var monto_retencion = (document.getElementById('hddBaseImponible').value * document.getElementById('hddPorcentajeRetencion').value / 100)-(document.getElementById('hddSustraendoRetencion').value);
				document.getElementById('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');
				var monto = (parseFloat(document.getElementById('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
				document.getElementById('txtMonto').value = number_format(monto,'2','.','');
				}
		}
		else{
			document.getElementById('tdTextoRetencionISLR').style.display = 'none';
			document.getElementById('tdMontoRetencionISLR').style.display = 'none';
			document.getElementById('txtMonto').value = document.getElementById('txtSaldoFactura').value;
			document.getElementById('txtMontoRetencionISLR').value = 0;
		}
	}
	
    function number_format( number, decimals, dec_point, thousands_sep ){
		var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
		var d = dec_point == undefined ? "," : dec_point;
		var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
		var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}
	
	function validarClaveAprobacion(){
		if (validarCampo('txtClaveAprobacion','t','') == true){
			xajax_verificarClave(xajax.getFormValues('frmClave'));
		 }
		 else{
		 	validarCampo('txtClaveAprobacion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		 }
	}
	
	
	
		function validarClaveAnular(){
		if (validarCampo('txtClaveAnular','t','') == true){
			xajax_verificarClaveAnular(xajax.getFormValues('frmClaveAnular'));
		 }
		 else{
		 	validarCampo('txtClaveAnular','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		 }
	}
        
        //la llama despues de confirmar la clave el xajax
    function antesAnular(idTransferencia){
        
        //compruebo si tiene impuesto el cheque
        var tiene = xajax.call('tieneImpuesto', {mode:'synchronous', parameters:[idTransferencia]});

        if(tiene == "SI"){
                if(confirm("La transferencia posee ISLR, si ya fue declarado no deberia ser eliminado, ¿deseas eliminar el impuesto?")){
                     xajax_anularTransferencia(idTransferencia,"SI");
                 }else{
                     xajax_anularTransferencia(idTransferencia);
                 }
        }else if(tiene == 'NO'){
            xajax_anularTransferencia(idTransferencia);
        }else{
            alert('Error: ' +tiene);
            return false;
        }       
        
    }
    
    function limpiarPropuesta(){        
        document.getElementById('numeroPropuestaPago').innerHTML = "";
        document.getElementById('fechaPropuestaPago').innerHTML = "";
        document.getElementById('numeroTransferenciaPropuestaPago').innerHTML = "";
        document.getElementById('estadoPropuestaPago').innerHTML = "";
        document.getElementById('detallePropuestaPago').innerHTML = "";
    }
    
    function desbloquearGuardado(){
        document.getElementById('btnAceptar').disabled = false;
    }
        
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include ('banner_tesoreria.php'); ?></div>

    <div id="divInfo" class="print">
    	<table border="0" width="100%">
            <tr>
                <td class="tituloPaginaTesoreria" colspan="2" id="tdReferenciaPagina"></td>
            </tr>
            <tr>
                <td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<button type="button" id="btnNuevo" onclick="nuevoTransferencia(document.getElementById('selEmpresa').value);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
					</td>
				</tr>
				</table>
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
                <tr>
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdSelEmpresa" align="left">
                        <select id="selEmpresa" name="selEmpresa">
                            <option value="-1">Seleccione</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">
                                            Benef. Prov.:
                    </td>
                    <td>
                        <input type="text" name="idProveedorBuscar" id="idProveedorBuscar" readonly="readonly" style="width:30px"></input>
                        <input type="text" name="nombreProveedorBuscar" id="nombreProveedorBuscar" readonly="readonly" size="30"></input>

                    </td>
                    <td>
                        <button title="Seleccionar Beneficiario o Proveedor" onclick="
                                                document.getElementById('tblBancos').style.display = 'none';
                                                document.getElementById('tblFacturasNcargos').style.display = 'none';
                                                document.getElementById('tblBeneficiariosProveedores').style.display = '';
                                                document.getElementById('tdContenido').style.display = '';
                                                document.getElementById('divFlotante1').style.display = '';
                                                document.getElementById('tdFlotanteTitulo1').innerHTML = 'Beneficiario o Provedor';
                                                centrarDiv(document.getElementById('divFlotante1'));
                                                document.getElementById('tdBeneficiarios').className = 'rafktabs_titleActive';
                                                document.getElementById('tdProveedores').className = 'rafktabs_title';
                                                document.getElementById('txtIdFactura').value = '';
                                                document.getElementById('txtNumeroFactura').value = '';
                                                document.getElementById('txtSaldoFactura').value = '';
                                                document.getElementById('txtFechaRegistroFactura').value = '';
                                                document.getElementById('txtFechaVencimientoFactura').value = '';
                                                document.getElementById('txtDescripcionFactura').innerHTML = '';

                                                //si cierra y abre no muestra el buscador input correcto
                                                document.getElementById('txtCriterioBusqProveedor').style.display='none';
                                                document.getElementById('txtCriterioBusqBeneficiario').style.display='';

                                                document.getElementById('buscarListado').value = '1';
                                                document.getElementById('buscarProv').value = '2';//beneficiario

                                                document.getElementById('tdProveedores').onclick = function(){
                                                    xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),1);
                                                     document.getElementById('tdBeneficiarios').className = 'rafktabs_title';
                                                     document.getElementById('tdProveedores').className = 'rafktabs_titleActive';
                                                     document.getElementById('txtCriterioBusqProveedor').style.display='';
                                                     document.getElementById('txtCriterioBusqBeneficiario').style.display='none';
                                                     document.getElementById('buscarProv').value = '1';//proveedor
                                                     };

                                                  document.getElementById('tdBeneficiarios').onclick = function(){
                                                     xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),0);
                                                     document.getElementById('tdBeneficiarios').className = 'rafktabs_titleActive';
                                                     document.getElementById('tdProveedores').className = 'rafktabs_title';
                                                     document.getElementById('txtCriterioBusqProveedor').style.display='none';
                                                     document.getElementById('txtCriterioBusqBeneficiario').style.display='';
                                                     document.getElementById('buscarProv').value = '2';//beneficiario
                                                     };
                                                xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));
                                " type="button"><img src="../img/iconos/ico_pregunta.gif"></button>
                    </td>
                    
				</tr>
				<tr align="left">
                	<td align="right" class="tituloCampo">Fecha Registro:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                Desde:<input style="width:100px" type="text" name="txtFecha" id="txtFecha" readonly="readonly" value=""/>
                                Hasta:<input style="width:100px" type="text" name="txtFecha1" id="txtFecha1" readonly="readonly" value=""/>
                            </td>
                            <td>
<!--                                <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaProveedor" name="imgFechaProveedor" class="puntero"></div>-->
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
                    </td>
                        
                        <td align="right" class="tituloCampo">
                            Concepto:
                        </td>
                        <td>
                            <input type="text" name="conceptoBuscar" id="conceptoBuscar" ></input>                                            
                        </td>
                        
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Estado:</td>
                    <td id="tdSelEstado" align="left" >
                        <select id="selEstado" name="selEstado">

                            <option value="-1">Seleccione</option>
                        </select>
                        
                        
                    </td>
                    <td align="right" class="tituloCampo" >Nro. Transferencia:</td>
                    <td align="left"><input type="text" name="txtBusq" id="txtBusq" onkeyup="document.getElementById('btnBuscar').click();"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarTransferencia(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); document.getElementById('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>			
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListadoTransferencia"></td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_rojo.gif"></td>
                            <td>Por Aplicar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"></td>
                            <td>Aplicado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif"></td>
                            <td>Conciliado</td>
                        </tr>
                        </table>
                    </td>
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
                                        <button type="button"  id="btnBuscarCliente" name="btnBuscarCliente"
                                        
                                         onclick="xajax_listarBeneficiarios();
                                         
                                         
                                        document.getElementById('tblBancos').style.display = 'none';
                                        document.getElementById('tblFacturasNcargos').style.display = 'none';
                                        document.getElementById('tblBeneficiariosProveedores').style.display = '';
                                        document.getElementById('tdContenido').style.display = '';
                                        document.getElementById('divFlotante1').style.display = '';
                                        document.getElementById('tdFlotanteTitulo1').innerHTML = 'Beneficiario o Provedor';
                                        centrarDiv(document.getElementById('divFlotante1'));
                                        document.getElementById('tdBeneficiarios').className = 'rafktabs_titleActive';
                                        document.getElementById('tdProveedores').className = 'rafktabs_title';
                                        document.getElementById('txtIdFactura').value = '';
                                        document.getElementById('txtNumeroFactura').value = '';
                                        document.getElementById('txtSaldoFactura').value = '';
                                        document.getElementById('txtFechaRegistroFactura').value = '';
                                        document.getElementById('txtFechaVencimientoFactura').value = '';
                                        document.getElementById('txtDescripcionFactura').innerHTML = '';
                                        
                                        //si cierra y abre no muestra el buscador input correcto
                                        document.getElementById('txtCriterioBusqProveedor').style.display='none';
                                        document.getElementById('txtCriterioBusqBeneficiario').style.display='';
                                        
                                    document.getElementById('buscarListado').value = '0';
                                        document.getElementById('buscarProv').value = '2';//beneficiario
										
                                        document.getElementById('tdProveedores').onclick = function(){
                                           xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),1);
                                            document.getElementById('tdBeneficiarios').className = 'rafktabs_title';
                                            document.getElementById('tdProveedores').className = 'rafktabs_titleActive';
                                            document.getElementById('txtCriterioBusqProveedor').style.display='';
                                            document.getElementById('txtCriterioBusqBeneficiario').style.display='none';
                                            document.getElementById('buscarProv').value = '1';//proveedor
                                            };
                                            
                                         document.getElementById('tdBeneficiarios').onclick = function(){
                                            xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),0);
                                            document.getElementById('tdBeneficiarios').className = 'rafktabs_titleActive';
                                            document.getElementById('tdProveedores').className = 'rafktabs_title';
                                            document.getElementById('txtCriterioBusqProveedor').style.display='none';
                                            document.getElementById('txtCriterioBusqBeneficiario').style.display='';
                                            document.getElementById('buscarProv').value = '2';//beneficiario
                                            };">
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
                                        <button type="button" id="btnInsertarFactura" name="btnInsertarFactura" title="Seleccionar Factura" 
                                        onclick="
                                    if(validarProveedor() === true){
         
                                        document.getElementById('tblBancos').style.display = 'none';
                                        document.getElementById('tblBeneficiariosProveedores').style.display = 'none';
                                        document.getElementById('tblFacturasNcargos').style.display = '';
                                        document.getElementById('tdContenidoDocumento').style.display = '';
                                        document.getElementById('divFlotante1').style.display = '';
                                        document.getElementById('tdFlotanteTitulo1').innerHTML = 'Factura / Nota de Cargo';
                                        centrarDiv(document.getElementById('divFlotante1'));
                                        document.getElementById('tdFacturas').className = 'rafktabs_titleActive';
                                        document.getElementById('tdNotaCargo').className = 'rafktabs_title';
                                        document.getElementById('txtIdFactura').value = '';
                                        document.getElementById('txtNumeroFactura').value = '';
                                        document.getElementById('txtSaldoFactura').value = '';
                                        document.getElementById('txtFechaRegistroFactura').value = '';
                                        document.getElementById('txtFechaVencimientoFactura').value = '';
                                        document.getElementById('txtDescripcionFactura').innerHTML = '';
                                        document.getElementById('tdFacturaNota').innerHTML = 'SIN DOCUMENTO';
                                        //si cierra y abre no muestra el buscador input correcto
                                        document.getElementById('txtCriterioBusqFactura').style.display='';
                                        document.getElementById('txtCriterioBusqNotaCargo').style.display='none';
                                        
                                        document.getElementById('buscarFact').value = '2';//factura                                                                               

                                        document.getElementById('tdFacturas').onclick = function(){
                                            xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), document.getElementById('hddIdEmpresa').value, document.getElementById('txtIdBeneficiario').value,0);
                                            document.getElementById('tdNotaCargo').className = 'rafktabs_title';
                                            document.getElementById('tdFacturas').className = 'rafktabs_titleActive';
                                            document.getElementById('txtCriterioBusqFactura').style.display='';
                                            document.getElementById('txtCriterioBusqNotaCargo').style.display='none';
                                            document.getElementById('buscarFact').value = '2';//factura
                                            };
                                            
                                         document.getElementById('tdNotaCargo').onclick = function(){
                                            xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), document.getElementById('hddIdEmpresa').value, document.getElementById('txtIdBeneficiario').value,1);
                                            document.getElementById('tdNotaCargo').className = 'rafktabs_titleActive';
                                            document.getElementById('tdFacturas').className = 'rafktabs_title';
                                            document.getElementById('txtCriterioBusqNotaCargo').style.display='';
                                            document.getElementById('txtCriterioBusqFactura').style.display='none';
                                            document.getElementById('buscarFact').value = '1';//nota de cargo
                                            };
                                    }">
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
<!--                            <input type="hidden" id="hddBaseImponible" name="hddBaseImponible" />-->
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
            	<input type="button" id="btnActualizar" name="btnActualizar" value="Actualizar" onclick="xajax_actualizarTransferencia(xajax.getFormValues('frmTransferencia'))" style="display:none">
            	<input type="button" id="btnAceptar" name="btnAceptar" onclick="this.disabled = true; validarTransferencia();" value="Aceptar" disabled="disabled">
            	<input type="button" onclick="document.getElementById('divFlotante').style.display='none'; document.getElementById('divFlotante1').style.display='none';" value="Cancelar">
            </td>
    	</tr>
    </table>
    </form>
     
</div>


<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
   	<table id="tblBeneficiariosProveedores" border="0" style="display:none" width="700px">
    <tr>
    	<td>
        	<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="document.getElementById('btnBuscarCliente').click(); return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdBeneficiarios" width="120px">Beneficiarios</td>
                        <td class="rafktabs_title" id="tdProveedores" width="120px">Proveedores</td>
		            </tr>
              
                        <tr>
                	<td align="right" class="tituloCampo" width="15">Criterio:</td>
                	<td>
                    	<input type="hidden" id="buscarListado" name="buscarListado" value="0" />
                        <input type="hidden" id="buscarProv" name="buscarProv" value="2" />
                    	<input type="text" id="txtCriterioBusqBeneficiario" name="txtCriterioBusqBeneficiario" onkeyup="document.getElementById('tdBeneficiarios').onclick()" style="display:"/>
                        <input type="text" id="txtCriterioBusqProveedor" name="txtCriterioBusqProveedor" onkeyup="document.getElementById('tdProveedores').onclick()" style="display:none"/>
					</td>
                             <td><input type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));" value="Buscar..."/></td>
                

                </tr>
					</table>
				</td>
       
            </tr>
                 
            <tr>
				<td class="rafktabs_panel" id="tdContenido" style="display:none; border:0px;"></td>
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

   
   
   <table id="tblFacturasNcargos" border="0" style="display:none" width="700px">
    <tr>
    	<td>
        	<form id="frmBuscarDocumento" name="frmBuscarDocumento" onsubmit="document.getElementById('btnBuscarDocumento').click(); return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdFacturas" width="120px">Facturas</td>
                        <td class="rafktabs_title" id="tdNotaCargo" width="120px">Notas De Cargo</td>
		            </tr>
              
                        <tr>
                	<td align="right" class="tituloCampo" width="15">Criterio:</td>
                	<td>
                        <input type="hidden" id="buscarFact" name="buscarFact" value="2" />
                    	<input type="text" id="txtCriterioBusqFactura" name="txtCriterioBusqFactura" onkeyup="document.getElementById('tdFacturas').onclick()" style="display:"/>
                        <input type="text" id="txtCriterioBusqNotaCargo" name="txtCriterioBusqNotaCargo" onkeyup="document.getElementById('tdNotaCargo').onclick()" style="display:none"/>
					</td>
                             <td><input type="button" id="btnInsertarFactura" name="btnInsertarFactura" onclick="xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), document.getElementById('hddIdEmpresa').value, document.getElementById('txtIdBeneficiario').value);" value="Buscar..."/></td>
                

 				</tr>
					</table>
				</td>
       
            </tr>
                 
            <tr>
				<td class="rafktabs_panel" id="tdContenidoDocumento" style="display:none; border:0px;"></td>
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

    
    <table border="0" id="tblBancos" style="display:none" width="610">
        <tr>
            <td id="tdDescripcionArticulo">
            </td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr>
                <input type="button" id="" name="" onclick="document.getElementById('divFlotante1').style.display='none';" value="Cancelar">
            </td>
        </tr>
        </table>
</div>




<div id="divFlotanteClave" class="root" style="position:absolute; cursor:auto; display:none; left:0px;; top:0px; z-index:3;">
	<div id="divFlotanteTituloClave" class="handle"><table><tr><td id="tdFlotanteTituloClave" width="100%"></td></tr></table></div>
	<form id="frmClave" name="frmClave" onsubmit="return false;">
            <input type="hidden" id="hddPermiso" name="hddPermiso" title="hddPermiso" value="0" />
	<table border="0" id="tblClaveAprobacionOrden">
		<tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
        <tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="tituloCampo">Clave:</td>
			<td><label>
				<input name="txtClaveAprobacion" id="txtClaveAprobacion" type="password" class="inputInicial" />
			</label></td>
		</tr>
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr>
			<td align="right" colspan="2">
			<hr>
			<input type="submit" id="btnGuardar" name="btnGuardar" onclick="validarClaveAprobacion();" value="Aceptar" />
			<input type="button" onclick="document.getElementById('divFlotanteClave').style.display='none';" value="Cancelar">
			</td>
		</tr>
	</table>
    </form>
</div>



<div id="divFlotanteAnular" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTituloAnular" class="handle"><table><tr><td id="tdFlotanteTituloAnular" width="100%">Eliminar Transferencia</td></tr></table></div>
	<form id="frmClaveAnular" name="frmClaveAnular" onsubmit="return false;">
	<table border="0" id="tblClaveAprobacionOrden">
		<tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
        <tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="tituloCampo" >Nro Transferencia:</td>
		<td>
			<input type="text" id="txtNumTransferencia" name="txtNumTransferencia"  readonly="readonly">
            <input type="hidden" id="hddIdTransferencia" name="hddIdTransferencia" readonly="readonly" />
		</td>
		</tr>
		<tr>
			<td align="right" class="tituloCampo">Clave:</td>
			<td><label>
				<input name="txtClaveAnular" id="txtClaveAnular" type="password" class="inputInicial" />
			</label></td>
		</tr>
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr>
			<td align="right" colspan="2">
			<hr>
			<input type="submit" id="btnGuardar" name="btnGuardar" onclick="validarClaveAnular();" value="Aceptar" />
			<input type="button" onclick="document.getElementById('divFlotanteAnular').style.display='none';" value="Cancelar">
			</td>
		</tr>
	</table>
    </form>
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
xajax_listadoTransferencia(0,'fecha_registro','DESC','-1|0||');
xajax_comboEmpresa('tdSelEmpresa','selEmpresa','');
xajax_comboEstado();
xajax_comboRetencionISLR();
</script>

<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
        
	var theHandle = document.getElementById("divFlotanteTitulo1");
	var theRoot   = document.getElementById("divFlotante1");
	Drag.init(theHandle, theRoot);
        
        var theHandle = document.getElementById("divFlotanteTituloClave");
	var theRoot   = document.getElementById("divFlotanteClave");
	Drag.init(theHandle, theRoot);
	
	
	
        var theHandle = document.getElementById("divFlotanteTituloAnular");
	var theRoot   = document.getElementById("divFlotanteAnular");
	Drag.init(theHandle, theRoot);
        
        var theHandle = document.getElementById("divFlotanteTitulo3");
	var theRoot   = document.getElementById("divFlotante3");
	Drag.init(theHandle, theRoot);
</script>