<?php

require_once ("../connections/conex.php");
@session_start();
$currentPage = $_SERVER["PHP_SELF"];

include ("../inc_sesion.php");
require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_retenciones_list.php");

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Retenciones</title>
	<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css">

    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
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
</style>
    
<script>

	function exportarListado(){
		objInputs = xajax.getFormValues('frmBuscar');
		objBusq = {
			hddIdEmpresa : objInputs.hddIdEmpresa,
			hddBePro : objInputs.hddBePro,
			txtFecha : objInputs.txtFecha,
			txtCriterio : objInputs.txtCriterio,
			listAnulado : objInputs.listAnulado,
			listPago : objInputs.listPago
		}
		window.open("reportes/te_retenciones_islr_excel.php?valBusq="+JSON.stringify(objBusq));
	}

	function exportarSeniat(){
		window.open("reportes/te_retenciones_islr_seniat_excel.php?empresa=" + byId('hddIdEmpresa').value + "&proveedor=" + byId('hddBePro').value + "&fecha=" + byId('txtFecha').value  + "&txtCriterio=" + byId('txtCriterio').value + "&listAnulado=" + byId('listAnulado').value + "&listPago=" + byId('listPago').value);
	}
	
	function imprimirRetencionLotes(){
		objInputs = xajax.getFormValues('frmBuscar');//trae todos input
		//filtro igual que el buscador
		objBusq = {
			hddIdEmpresa : objInputs.hddIdEmpresa,
			hddBePro : objInputs.hddBePro,
			txtFecha : objInputs.txtFecha,
			txtCriterio : objInputs.txtCriterio,
			listAnulado : objInputs.listAnulado,
			listPago : objInputs.listPago
		}
		//console.log(JSON.stringify(objBusq));
		verVentana('reportes/te_imprimir_constancia_retencion_pdf.php?documento=4&valBusq='+JSON.stringify(objBusq),700,700);
	}
	
	function arcv(){
		
		if(byId('hddIdEmpresa').value == ""){
			return alert("Debe seleccionar Empresa");
		}
		
		if(byId('txtFecha').value == ""){
			return alert("Debe Seleccionar Fecha");
		}
		
		if(byId('hddBePro').value == ""){
			return alert("Debe Seleccionar Proveedor");
		}
		 
		verVentana("reportes/arcv.php?empresa=" + byId('hddIdEmpresa').value + "&proveedor=" + byId('hddBePro').value + "&fecha=" + byId('txtFecha').value,700,700);
		
	}
	
	function nuevaRetencion(){
		var idEmpresa = byId('hddIdEmpresa').value;
		
		xajax_asignarEmpresa2(idEmpresa);
		document.forms['frmRetencion'].reset();
		document.getElementById('divFlotante').style.display = '';
		centrarDiv(document.getElementById('divFlotante'));
		
		document.getElementById('txtDescripcionFactura').innerHTML = '';
		document.getElementById('tdInfoRetencionISLR').innerHTML = '';
		
		document.getElementById('selRetencionISLR').className = 'inputHabilitado';
		document.getElementById('txtMontoRetencionISLR').className = 'inputHabilitado';
		document.getElementById('txtBaseRetencionISLR').className = 'inputHabilitado';
		document.getElementById('txtFechaRetencion').className = 'inputHabilitado';
	}
	
	function calcularRetencion(){
		
		var baseRetencion = parseFloat(byId('txtBaseRetencionISLR').value).toFixed(2);
		var montoMayorAplicar = parseFloat(byId('hddMontoMayorAplicar').value).toFixed(2);
		var sustraendo = parseFloat(byId('hddSustraendoRetencion').value).toFixed(2);
		var porcentaje = parseFloat(byId('hddPorcentajeRetencion').value).toFixed(2);		
		var montoRetencion = 0;
		
		//NOTA: toFixed(2) regresa string y debes volver a usar parseFloat
		if(parseFloat(baseRetencion) >= parseFloat(montoMayorAplicar) && !isNaN(baseRetencion)){
			montoRetencion = (baseRetencion * (porcentaje / 100)) - sustraendo;			
		}
		
		byId('txtMontoRetencionISLR').value = montoRetencion.toFixed(2);
	}
	
	function validarRetencionForm(){
		if (validarCampo('selRetencionISLR','t','lista') == true
			&& validarCampo('txtMontoRetencionISLR','t','monto') == true
			&& validarCampo('txtBaseRetencionISLR','t','monto') == true
			&& validarCampo('txtFechaRetencion','t','') == true){
			
			xajax_guardarRetencion(xajax.getFormValues('frmRetencion'));
		}else{
			validarCampo('selRetencionISLR','t','lista') == true;
			validarCampo('txtMontoRetencionISLR','t','monto');
			validarCampo('txtBaseRetencionISLR','t','monto');
			validarCampo('txtFechaRetencion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
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
        	<td class="tituloPaginaTesoreria">Retenciones ISLR<br/></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
                    <td>
                    	<button type="button" id="btnNuevo" onclick="nuevaRetencion(document.getElementById('hddIdEmpresa').value);" class="puntero"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
					<td>
						<button type="button" id="btnExportar" onclick="exportarListado();" class="puntero" style="width:100%;"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Doc Listado</td></tr></table></button>
					</td>
					<td>
						<button type="button" id="btnExportar" onclick="exportarSeniat();" class="puntero" style="width:100%;"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Doc SENIAT</td></tr></table></button>
					</td>
				</tr>
                <tr>
                	<td></td>
                    <td></td>
					<td>
						<button type="button" id="btnArcv" onclick="arcv();" class="puntero" style="width:100%;"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/pdf_ico.png"/></td><td>&nbsp;</td><td>ARCV</td></tr></table></button>
					</td>
                    <td>
                    	<button onclick="imprimirRetencionLotes();" type="button" class="puntero" style="width:100%;"><table cellspacing="0" cellpadding="0" align="center"><tbody><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"></td><td>&nbsp;</td><td>PDF por Lote</td></tr></tbody></table></button>
                    </td>
                </tr>
				</table>
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                            <td><button type="button" id="btnListEmpresa" name="btnListEmpresa" onclick="xajax_listEmpresa();" title="Seleccionar Empresa" class="puntero"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Tipo Pago:</td>
                    <td>
                    	<select name="listPago" id="listPago" onchange="$('#btnBuscar').click();" class="inputHabilitado">
                        	<option value="">[ Todos ]</option>
                            <option value="0">Cheque</option>
                            <option value="1">Transferencia</option>
                            <option value="2">Sin Documento</option>
                        </select>
                    </td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Mes Consulta:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" name="txtFecha" id="txtFecha" class="inputHabilitado" readonly="readonly"/>
                            </td>
                       </tr>
                       </table>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Estado:</td>
                    <td>
                    	<select name="listAnulado" id="listAnulado" onchange="$('#btnBuscar').click();" class="inputHabilitado">
                        	<option value="">[ Todos ]</option>
                            <option value="0" selected="selected">Activo</option>
                            <option value="1">Anulado</option>
                        </select>
                    </td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Proveedor / Beneficiario:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input name="txtBePro" type="text" id="txtBePro" size="35" readonly="readonly"/><input type="hidden" name="hddBePro" id="hddBePro"/><input type="hidden" name="hddSelBePro" id="hddSelBePro"/></td>
                            <td><button type="button" id="btnSeleccionearBP" name="btnSeleccionearBP" title="Seleccionar Proveedor / Beneficiario" class="puntero"
                                onclick="xajax_listarBeneficiarios1();
                                byId('divFlotante1').style.display = '';
                                centrarDiv(byId('divFlotante1'));
                                byId('tdBeneficiarios').className = 'rafktabs_titleActive';
                                byId('tdProveedores').className = 'rafktabs_title';
                                
                                //si cierra y abre no muestra el buscador input correcto
                                byId('txtCriterioBusqProveedor').style.display='none';
                                byId('txtCriterioBusqBeneficiario').style.display='';

                                byId('buscarProv').value = '2';//beneficiario
                                
                                 byId('tdProveedores').onclick = function(){
                                   xajax_buscarCliente1(xajax.getFormValues('frmBuscarCliente'),1)
                                    byId('tdBeneficiarios').className = 'rafktabs_title';
                                    byId('tdProveedores').className = 'rafktabs_titleActive';
                                    byId('txtCriterioBusqProveedor').style.display='';
                                    byId('txtCriterioBusqBeneficiario').style.display='none';
                                    byId('buscarProv').value = '1';//proveedor
                                    };
                                    
                                 byId('tdBeneficiarios').onclick = function(){
                                    xajax_buscarCliente1(xajax.getFormValues('frmBuscarCliente'),0);
                                    byId('tdBeneficiarios').className = 'rafktabs_titleActive';
                                    byId('tdProveedores').className = 'rafktabs_title';
                                    byId('txtCriterioBusqProveedor').style.display='none';
                                    byId('txtCriterioBusqBeneficiario').style.display='';
                                    byId('buscarProv').value = '2';//beneficiario
                                    };">
                                <img src="../img/iconos/ico_pregunta.gif"/>
                                </button></td>	
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Nro. Factura / Control:</td>
                    <td><input type="text" name="txtCriterio" id="txtCriterio" class="inputHabilitado"/></td>
					<td>
						<button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarRetenciones(xajax.getFormValues('frmBuscar'));" class="puntero">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('hddIdEmpresa').value = ''; byId('btnBuscar').click();" class="puntero">Limpiar</button>
					</td>
                </tr>		
                </table>
                </form>
            </td>
        </tr>
        <tr>
        	<td id="tdListadoRetenciones"></td>
        </tr>
        <tr>
        	<td><br>
            	<table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                    <tbody>
                        <tr>
                            <td width="25"></td>
                            <td align="center">
                                <table>
                                <tbody><tr>
                                    <td><img src="../img/iconos/ico_verde.gif"></td>
                                    <td>Activo</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_rojo.gif"></td>
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
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
        <table border="0" id="tblListados2" style="display:none" width="610">
        <tr>
            <td id="tdDescripcionArticulo">
                <table width="100%">
                <tr class="tituloColumna">
                    <td>RIF</td>
                    <td>Nº Factura</td>
                    <td>Nº Control</td>
                    <td>Monto Operaci&oacute;n</td>
                    <td>Porcentaje Retenci&oacute;n</td>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr />
                <button type="button" id="" name="" onclick="byId('divFlotante2').style.display='none';" class="puntero">Cancelar</button>
            </td>
        </tr>
        </table>
</div>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%">Seleccionar Beneficario / Proveedor</td></tr></table></div>
    
    <table id="tblBeneficiariosProveedores" border="0" width="700px">
    <tr>
    	<td>
             	<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
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
                        <input type="hidden" id="buscarProv" name="buscarProv" value="2" />
                    	<input type="text" id="txtCriterioBusqBeneficiario" name="txtCriterioBusqBeneficiario" onkeyup="byId('tdBeneficiarios').onclick()" style="display:"/>
                        <input type="text" id="txtCriterioBusqProveedor" name="txtCriterioBusqProveedor" onkeyup="byId('tdProveedores').onclick()" style="display:none"/>
					</td>
                        <td><button type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente1(xajax.getFormValues('frmBuscarCliente'));" class="puntero">Buscar...</button></td>
                

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
			<button type="button" onclick="byId('divFlotante1').style.display='none';" class="puntero" >Cancelar</button>
		</td>
    </tr>
    </table>
  
</div>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%">Nueva Retenci&oacute;n ISLR</td></tr></table></div>
    <form id="frmRetencion" name="frmRetencion">
    <table border="0" id="tblChequeNuevo" width="810">
    	<tr>
    		<td>
    			<fieldset><legend><span style="color:#990000">Datos Empresa</span></legend>
    			<table width="100%">
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td colspan="3" align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreEmpresa2" name="txtNombreEmpresa2" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa2" name="hddIdEmpresa2"/></td>
                                <td><button type="button" id="btnListEmpresa"  name="btnListEmpresa" onclick="xajax_listEmpresa();" title="Seleccionar Empresa" class="puntero"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                </table>
                </fieldset>
             </td>
           </tr>
           <tr>
              <td colspan="2">                              
                <fieldset><legend><span style="color:#990000">Detalles del Documento</span></legend>
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
                                    <button type="button" id="btnInsertarFactura" name="btnInsertarFactura" title="Seleccionar Factura" class="puntero"
                                    onclick="
	                                    xajax_listarFacturas(0,'','',document.getElementById('hddIdEmpresa2').value);
                                        document.getElementById('tblFacturasNcargos').style.display = '';
                                        document.getElementById('tdContenidoDocumento').style.display = '';                                        
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
                                        xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), document.getElementById('hddIdEmpresa2').value,0);
                                            document.getElementById('tdNotaCargo').className = 'rafktabs_title';
                                            document.getElementById('tdFacturas').className = 'rafktabs_titleActive';
                                            document.getElementById('txtCriterioBusqFactura').style.display='';
                                            document.getElementById('txtCriterioBusqNotaCargo').style.display='none';
                                            document.getElementById('buscarFact').value = '2';//factura
                                            };

                                         document.getElementById('tdNotaCargo').onclick = function(){
    xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), document.getElementById('hddIdEmpresa2').value,1);
                                            document.getElementById('tdNotaCargo').className = 'rafktabs_titleActive';
                                            document.getElementById('tdFacturas').className = 'rafktabs_title';
                                            document.getElementById('txtCriterioBusqNotaCargo').style.display='';
                                            document.getElementById('txtCriterioBusqFactura').style.display='none';
                                            document.getElementById('buscarFact').value = '1';//nota de cargo
                                            };
                                    ">
                                        <img src="../img/iconos/ico_pregunta.gif"/>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="tituloCampo" width="10%" align="right"><span class="textoRojoNegrita">*</span>N&uacute;mero</td>
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
                        <input type="text" id="hddBaseImponible" readonly="readonly" name="hddBaseImponible" size="15" />
                    </td>
                </tr>
                <tr>
                    <td class="tituloCampo" align="right">Descripción</td>
                    <td align="left" colspan="4">
                        <textarea id="txtDescripcionFactura" name="txtDescripcionFactura" readonly="readonly" cols="55"></textarea>
                        <input type="hidden" id="hddIva" name="hddIva" />
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
                
                <fieldset><legend><span style="color:#990000">Retenci&oacute;n ISLR</span></legend>
                <table width="100%" border="0">                
                <tr>
                	<td>
                    	<table width="100%" border="0">
                        	<tr>
								<td class="tituloCampo" align="right" width="35%">
                                    <span class="textoRojoNegrita">*</span>Fecha Retenci&oacute;n:
                                </td>
                                <td align="left" colspan="2">
 	                               <input type="text" size="10" class="inputHabilitado" name="txtFechaRetencion" id="txtFechaRetencion" readonly="readonly"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="tituloCampo" align="right" width="35%">
                                    <span class="textoRojoNegrita">*</span>Retenci&oacute;n ISLR:
                                </td>
                                <td align="left" id="tdRetencionISLR" colspan="2">
                                </td>
                            </tr>
                            <tr>
                            	<td></td>
								<td colspan="2" id="tdInfoRetencionISLR"></td>
							</tr>
                            <tr>
                            	<td class="tituloCampo" align="right" width="35%">
                                	<span class="textoRojoNegrita">*</span>Base Retenci&oacute;n:
                                </td>
								<td>
                                	<input type="text" onkeyup="calcularRetencion();" onkeypress="return validarSoloNumerosReales(event);" class="inputHabilitado" size="10" name="txtBaseRetencionISLR" id="txtBaseRetencionISLR">
                                </td>
                            	<td class="tituloCampo" align="right" width="35%">
                                	<span class="textoRojoNegrita">*</span>Monto Retenci&oacute;n:
                                </td>
                                <td>
                                	<input type="text" readonly="readonly" size="10" name="txtMontoRetencionISLR" id="txtMontoRetencionISLR">
                                </td>
							</tr>
                       </table>
                    </td>
                    <td colspan="2">
						<table width="100%" border="0">
                        <tr>
                        	<td style="white-space:nowrap;" class="tituloCampo" align="right" width="35%">Mayor a aplicar:</td>
                            <td><input size="5" readonly="readonly" id="hddMontoMayorAplicar" name="hddMontoMayorAplicar" /></td>
                            <td style="white-space:nowrap;" class="tituloCampo" align="right" width="35%">Porcentaje:</td>
                            <td style="white-space:nowrap;"><input size="5" readonly="readonly" id="hddPorcentajeRetencion" name="hddPorcentajeRetencion" />%</td>
                        </tr>
                        <tr>
                        	<td style="white-space:nowrap;" class="tituloCampo" align="right" width="35%">C&oacute;digo Concepto:</td>
                            <td><input size="5" readonly="readonly" id="hddCodigoRetencion" name="hddCodigoRetencion" /></td>
                            <td style="white-space:nowrap;" class="tituloCampo" align="right" width="35%">Sustraendo:</td>
                            <td><input size="5" readonly="readonly" id="hddSustraendoRetencion" name="hddSustraendoRetencion" /></td>
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
            	<input type="button" id="btnAceptar" name="btnAceptar" onclick="validarRetencionForm();" value="Aceptar" />
            	<input type="button" onclick="document.getElementById('divFlotante').style.display='none'; document.getElementById('divFlotante3').style.display='none';" value="Cancelar"/>
            </td>
    	</tr>
    </table>
    </form>
     
</div>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%">Factura / Nota de Cargo</td></tr></table></div>
   	<table id="tblFacturasNcargos" border="0" style="display:none" width="800px">
    <tr>
    	<td>
        	<form id="frmBuscarDocumento" name="frmBuscarDocumento" onsubmit="document.getElementById('btnBuscarDocumento').click(); return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdFacturas"  width="120px">Facturas</td>
                        <td class="rafktabs_title" id="tdNotaCargo" width="120px">Notas De Cargo</td>
		            </tr>
              
					<tr>
                        <td align="right" class="tituloCampo" width="15">Criterio:</td>
                        <td>
                            <input type="hidden" id="buscarFact" name="buscarFact" value="2" />
                            <input type="text" id="txtCriterioBusqFactura" name="txtCriterioBusqFactura" onkeyup="document.getElementById('tdFacturas').onclick()" class="inputHabilitado" style="display:"/>
                            <input type="text" id="txtCriterioBusqNotaCargo" name="txtCriterioBusqNotaCargo" onkeyup="document.getElementById('tdNotaCargo').onclick()" class="inputHabilitado" style="display:none"/>
                        </td>
                        <td><input type="button" id="btnInsertarFactura" name="btnInsertarFactura" onclick="xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), document.getElementById('hddIdEmpresa').value);" value="Buscar..."/>
                        </td>
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
			<input type="button" onclick="document.getElementById('divFlotante3').style.display='none';" value="Cancelar">
		</td>         
    </tr>
    </table>
</div>

<script language="javascript">

	new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"%m-%Y",
		cellColorScheme:"red"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaRetencion",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"red"
	});
	
	xajax_asignarEmpresa('');
	$('#btnBuscar').click();
	xajax_comboRetencionISLR();

	//empresa formulario nuevo
	var theHandle = byId("divFlotanteTitulo");
	var theRoot   = byId("divFlotante");
	Drag.init(theHandle, theRoot);
	// listado benef provee
	var theHandle = byId("divFlotanteTitulo1");
	var theRoot   = byId("divFlotante1");
	Drag.init(theHandle, theRoot);
        
	var theHandle = byId("divFlotanteTitulo2");
	var theRoot   = byId("divFlotante2");
	Drag.init(theHandle, theRoot);
	//listado factura nota de cargo
	var theHandle = byId("divFlotanteTitulo3");
	var theRoot   = byId("divFlotante3");
	Drag.init(theHandle, theRoot);
	
	//xajax_listadoRetenciones(0,'','','' + '|' + '-1' + '|' + '' + '|' + '-1');
	//xajax_buscarRetenciones(xajax.getFormValues('frmBuscar'));
</script>