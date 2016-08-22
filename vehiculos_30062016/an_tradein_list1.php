<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_tradein_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_tradein_list.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Trade-in</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../vehiculos/vehiculos.inc.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblUnidadFisica').style.display = 'none';
		byId('tblLista').style.display = 'none';
		
		if (verTabla == "tblUnidadFisica") {
			document.forms['frmUnidadFisica'].reset();
			byId('hddIdTradein').value = "";
                        byId('hddIdModulo').value = "";
                        byId('hddIdTipoPago').value = "";
                        byId('hddIdConceptoPago').value = "";
                        byId('hddIdEmpresa').value = "";
                        
			xajax_formTradeIn(valor);
			
			tituloDiv1 = 'Generar Nuevo Anticipo';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblUnidadFisica") {
			byId('txtNombreUnidadBasica').focus();
			byId('txtNombreUnidadBasica').select();
		} 
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, soloAnticipo) {
		byId('tblAjusteInventario').style.display = 'none';
		byId('tblListaCliente').style.display = 'none';
		
		if (verTabla == "tblAjusteInventario") {
			document.forms['frmAjusteInventario'].reset();
			byId('hddIdDcto').value = "";
			
			byId('txtIdCliente').className = 'inputHabilitado';
			byId('txtPlacaAjuste').className = 'inputHabilitado';
			byId('txtKilometraje').className = 'inputHabilitado';
			byId('txtFechaFabricacionAjuste').className = 'inputHabilitado';
			byId('txtSerialCarroceriaAjuste').className = 'inputHabilitado';
			byId('txtSerialMotorAjuste').className = 'inputHabilitado';
			byId('txtNumeroVehiculoAjuste').className = 'inputHabilitado';
			byId('txtRegistroLegalizacionAjuste').className = 'inputHabilitado';
			byId('txtRegistroFederalAjuste').className = 'inputHabilitado';
			byId('txtObservacion').className = 'inputHabilitado';
			byId('txtIdUnidadFisicaAjuste').className = '';
                        
			
                        byId('trUnidadFisica').style.display = '';
			byId('datosVale').style.display = '';
                        byId('btnGuardarAjusteInventario').style.display = '';
                        byId('btnGenerarTradein').style.display = 'none';
                        byId('btnListarVehiculos').style.display = 'none';
                        byId('txtAllowance').className = 'inputHabilitado';
                        byId('txtPayoff').className = 'inputHabilitado';
                        byId('txtMontoAnticipo').className = '';
                        byId('txtSubTotal').className = '';
                        byId('txtAcv').className = 'inputHabilitado';                     
                        byId('txtAcv').readOnly = false;                     
			
			jQuery(function($){
				$("#txtFechaFabricacionAjuste").maskInput("99-99-9999",{placeholder:" "});
			});
			
			new JsDatePick({
				useMode:2,
				target:"txtFechaFabricacionAjuste",
				dateFormat:"%d-%m-%Y",
				cellColorScheme:"orange"
			});
                        
                        if(soloAnticipo === 1){//solo mostrar datos para anticipo y cambiar boton
                            byId('trUnidadFisica').style.display = 'none';
                            byId('datosVale').style.display = 'none';
                            byId('btnGuardarAjusteInventario').style.display = 'none';
                            byId('btnGenerarTradein').style.display = '';
                            byId('btnListarVehiculos').style.display = '';                                                    
                            byId('txtAcv').className = '';                     
                            byId('txtAcv').readOnly = true;
                        }
			
			xajax_formAjusteInventario(xajax.getFormValues('frmUnidadFisica'), valor);
			
			tituloDiv2 = 'Ingreso de veh&iacute;culo trade-in';
		} else if (verTabla == "tblListaCliente") {
			document.forms['frmBuscarCliente'].reset();
			
			byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
			
			byId('btnBuscarCliente').click();
			//tituloDiv2 = 'Clientes';
		} 
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblAjusteInventario") {
			byId('txtIdCliente').focus();
			byId('txtIdCliente').select();
		} else if (verTabla == "tblListaCliente") {
			byId('txtCriterioBuscarCliente').focus();
			byId('txtCriterioBuscarCliente').select();
		}
	}     
	
	function validarFrmAjusteInventario() {
		error = false;
		if (!(validarCampo('txtIdEmpresa', 't', '') == true
		&& validarCampo('txtIdCliente', 't', '') == true
		&& validarCampo('lstClaveMovimiento', 't', 'lista') == true
		&& validarCampo('txtSubTotal', 't', 'monto') == true
		&& validarCampo('txtMontoAnticipo', 't', 'monto') == true
		&& validarCampo('txtAllowance', 't', 'monto') == true
		&& validarCampo('txtPayoff', 't', '') == true
		&& validarCampo('txtAcv', 't', 'monto') == true
		&& validarCampo('txtObservacion', 't', '') == true
                && validarCampo('lstModulo', 't', 'lista') == true
                && validarCampo('selTipoPago', 't', 'lista') == true
                && validarCampo('selConceptoPago', 't', 'lista') == true
                )) {
			validarCampo('txtIdEmpresa', 't', '');
			validarCampo('txtIdCliente', 't', '');
			validarCampo('lstClaveMovimiento', 't', 'lista');
			validarCampo('txtSubTotal', 't', 'monto');
			validarCampo('txtMontoAnticipo', 't', 'monto');
			validarCampo('txtAllowance', 't', 'monto');
			validarCampo('txtPayoff', 't', '');
			validarCampo('txtAcv', 't', 'monto');
			validarCampo('txtObservacion', 't', '');
                        validarCampo('lstModulo', 't', 'lista');
                        validarCampo('selTipoPago', 't', 'lista');
                        validarCampo('selConceptoPago', 't', 'lista');
                        
			
			error = true;
		}
		
		if (!(byId('txtIdUnidadFisicaAjuste').value > 0)) {
			if (!(validarCampo('lstUnidadBasica', 't', 'lista') == true
			&& validarCampo('lstAno', 't', 'lista') == true
			&& validarCampo('lstCondicion', 't', 'lista') == true
			&& validarCampo('txtKilometraje', 't', '') == true
			&& validarCampo('txtFechaFabricacionAjuste', 't', '') == true
			&& validarCampo('lstColorExterno1', 't', 'lista') == true
			&& validarCampo('lstColorInterno1', 't', 'lista') == true
			&& validarCampo('txtSerialCarroceriaAjuste', 't', '') == true
			&& validarCampo('txtSerialMotorAjuste', 't', '') == true
			&& validarCampo('txtNumeroVehiculoAjuste', 't', '') == true
			&& validarCampo('txtRegistroLegalizacionAjuste', 't', '') == true
			&& validarCampo('txtRegistroFederalAjuste', 't', '') == true
			&& validarCampo('lstAlmacenAjuste', 't', 'lista') == true
			&& validarCampo('lstEstadoVentaAjuste', 't', 'lista') == true
			&& validarCampo('lstMoneda', 't', 'lista') == true)) {
				validarCampo('lstUnidadBasica', 't', 'lista');
				validarCampo('lstAno', 't', 'lista');
				validarCampo('lstCondicion', 't', 'lista');
				validarCampo('txtKilometraje', 't', '');
				validarCampo('txtFechaFabricacionAjuste', 't', '');
				validarCampo('lstColorExterno1', 't', 'lista');
				validarCampo('lstColorInterno1', 't', 'lista');
				validarCampo('txtSerialCarroceriaAjuste', 't', '');
				validarCampo('txtSerialMotorAjuste', 't', '');
				validarCampo('txtNumeroVehiculoAjuste', 't', '');
				validarCampo('txtRegistroLegalizacionAjuste', 't', '');
				validarCampo('txtRegistroFederalAjuste', 't', '');
				validarCampo('lstAlmacenAjuste', 't', 'lista');
				validarCampo('lstEstadoVentaAjuste', 't', 'lista');
				validarCampo('lstMoneda', 't', 'lista');
				
				error = true;
			}
			
			if (byId('lstTipoVale').value == 3) {
				if (!(validarCampo('txtNroDcto', 't', '') == true)) {
					validarCampo('txtNroDcto', 't', '');
					
					error = true;
				}
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar el Vale y generar Anticipo?') == true) {
				byId('btnGuardarAjusteInventario').disabled = true;
				byId('btnCancelarAjusteInventario').disabled = true;
				xajax_guardarAjusteInventario(xajax.getFormValues('frmAjusteInventario'), xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmListaUnidadFisica'));
			}
		}
	}
	
	function validarFrmUnidadFisica() {
		error = false;
		
		if (!(validarCampo('lstEstadoVenta', 't', 'lista') == true)) {
			validarCampo('lstEstadoVenta', 't', 'lista');
			
			error = true;
		}
		
		if (byId('hddEstadoVenta').value == byId('lstEstadoVenta').options[byId('lstEstadoVenta').selectedIndex].text) {
			byId('lstEstadoVenta').className = "inputErrado";
			
			alert("El campo señalado en rojo no ha variado");
			return false;
		}
		
		if (byId('hddEstadoVenta').value != "DISPONIBLE" && byId('lstEstadoVenta').options[byId('lstEstadoVenta').selectedIndex].text != "DISPONIBLE") {
			alert("Variación de estado inválido");
			return false;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos");
			return false;
		} else {
			byId('btnCancelarUnidadFisica').disabled = true;
			byId('btnCancelarUnidadFisica').disabled = true;
			
			abrirDivFlotante2(byId('aGuardarUnidadFisica'), 'tblAjusteInventario', 1);
		}
	}
        
        /**
         * Se encarga de crear tradein y anticipo a partir de un trade in ya realizado
         * @returns void
         */
        function validarFrmGenerarAnticipo(){
            
            error = false;
            if (!(validarCampo('hddIdTradein', 't', '') == true
            && validarCampo('hddIdEmpresa', 't', '') == true
            && validarCampo('hddIdModulo', 't', '') == true
            && validarCampo('hddIdTipoPago', 't', '') == true
            && validarCampo('hddIdConceptoPago', 't', '') == true
            && validarCampo('txtIdCliente2', 't', '') == true
            && validarCampo('txtIdUnidadFisica', 't', '') == true
            && validarCampo('txtAllowance2', 't', 'monto') == true
            && validarCampo('txtPayoff2', 't', '') == true
            && validarCampo('txtAcv2', 't', 'monto') == true

            )) {
                    validarCampo('hddIdTradein', 't', '');
                    validarCampo('hddIdEmpresa', 't', '');
                    validarCampo('hddIdModulo', 't', '');
                    validarCampo('hddIdTipoPago', 't', '');
                    validarCampo('hddIdConceptoPago', 't', '');
                    validarCampo('txtIdCliente2', 't', '');
                    validarCampo('txtIdUnidadFisica', 't', '');
                    validarCampo('txtAllowance2', 't', 'monto');
                    validarCampo('txtPayoff2', 't', '');
                    validarCampo('txtAcv2', 't', 'monto');
                    error = true;
            }
            
            if(error){                
                alert("No se pudo procesar faltan campos principales");
                byId("btnGenerarAnticipo").disabled = false;
            }else{                
                xajax_generarNuevoAnticipo(xajax.getFormValues('frmUnidadFisica'));
            }            
        }
        
        /**
         * Se encarga de crear tradein y anticipo a partir de un vehiculo en unidad fisica
         * @returns void
         */
        function validarFrmGenerarTradein(){
            
            error = false;
            
            if (!(validarCampo('txtIdEmpresa', 't', '') == true
		&& validarCampo('txtIdCliente', 't', '') == true		
		&& validarCampo('txtIdUnidadFisicaAjuste', 't', '') == true		
		&& validarCampo('txtSubTotal', 't', 'monto') == true
		&& validarCampo('txtMontoAnticipo', 't', 'monto') == true
		&& validarCampo('txtAllowance', 't', 'monto') == true
		&& validarCampo('txtPayoff', 't', '') == true
		&& validarCampo('txtAcv', 't', 'monto') == true
		&& validarCampo('txtObservacion', 't', '') == true
                && validarCampo('lstModulo', 't', 'lista') == true
                && validarCampo('selTipoPago', 't', 'lista') == true
                && validarCampo('selConceptoPago', 't', 'lista') == true
                )) {
			validarCampo('txtIdEmpresa', 't', '');
			validarCampo('txtIdCliente', 't', '');			
			validarCampo('txtIdUnidadFisicaAjuste', 't', '');			
			validarCampo('txtSubTotal', 't', 'monto');
			validarCampo('txtMontoAnticipo', 't', 'monto');
			validarCampo('txtAllowance', 't', 'monto');
			validarCampo('txtPayoff', 't', '');
			validarCampo('txtAcv', 't', 'monto');
			validarCampo('txtObservacion', 't', '');
                        validarCampo('lstModulo', 't', 'lista');
                        validarCampo('selTipoPago', 't', 'lista');
                        validarCampo('selConceptoPago', 't', 'lista');
                        
			
			error = true;
		}
                
                if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea generar Trade-In y generar Anticipo?') == true) {
				byId('btnGenerarTradein').disabled = true;
                                xajax_generarNuevoAnticipo(xajax.getFormValues('frmAjusteInventario'),1);				
			}
		}
            
        }
        
        
        function copiarMonto(montoVehiculo){
            byId('txtMontoAnticipo').value = montoVehiculo;
            byId('txtSubTotal').value = montoVehiculo;
        }
        
        
        function validarSoloNumeros(e) {
            tecla = (document.all) ? e.keyCode : e.which;
            if (tecla == 0 || tecla == 8)
                return true;
            patron = /[0-9]/;
            te = String.fromCharCode(tecla);
            return patron.test(te);
        }
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Trade-in</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblAjusteInventario', 0); xajax_validarAperturaCaja();">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo Registro Trade-In"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        </a>
                    </td>
                    <td>&nbsp;
                        <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblAjusteInventario', 0, 1); xajax_validarAperturaCaja();">
                            <button type="button" style="height:27px;"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/generarPresupuesto.png" title="Agregar vehículo existente a Trade-In"/></td><td>&nbsp;</td><td>Registrados</td></tr></table></button>
                        </a>
                    </td>
                </tr>
                </table>
                
            <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Estado de Compra:</td>
                    <td id="tdlstEstadoCompraBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Estado de Venta:</td>
                    <td id="tdlstEstadoVentaBuscar"></td>           
                   
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input class="inputHabilitado" type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input class="inputHabilitado" type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
		</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Almacén:</td>
                    <td id="tdlstAlmacen"></td>
                    <td align="right" class="tituloCampo" width="120">Estado Trade-In:</td>
                    <td>
                        <select class="inputHabilitado" id="lstAnuladoTradein" name="lstAnuladoTradein">
                            <option value="-1">[ Todos ]</option>
                            <option value="0">Activo</option>
                            <option value="1">Anulado</option>
                        </select>
                    </td> 
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" name="txtCriterio" id="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
            <td>
                <form id="frmListaUnidadFisica" name="frmListaUnidadFisica" style="margin:0">
                    <div id="divListaUnidadFisica" style="width:100%"></div>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                    <tbody><tr>
                            <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                            <td align="center">
                                    <table>
                                    <tbody><tr>
                                            <td><img src="../img/iconos/ico_new.png"/></td>
                                            <td>Nuevo Registro Trade-In</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/generarPresupuesto.png"/></td>
                                            <td>Agregar vehículo existente a Trade-In</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/book_next.png"/></td>
                                            <td>Volver a Generar Anticipo</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/page_white_acrobat.png"/></td>
                                            <td>Ver Vale de Entrada</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/ico_print.png"/></td>
                                            <td>Imprimir Anticipo</td>
                                    </tr>
                                    </tbody></table>
                            </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                    <tbody><tr>
                            <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                            <td align="center">
                                    <table>
                                    <tbody><tr>
                                            <td><img src="../img/iconos/ico_verde.gif"/></td>
                                            <td>Activo</td>
                                            <td><img src="../img/iconos/ico_rojo.gif"/></td>
                                            <td>Anulado</td>                                            
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
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
	
<form id="frmUnidadFisica" name="frmUnidadFisica" style="margin:0" onsubmit="return false;">
    <div id="tblUnidadFisica" style="max-height:520px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
        	<td>
            	<table width="100%">
                <tr>
                    <td width="68%"></td>
                    <td width="32%"></td>
                </tr>
                                 
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Datos de la Unidad</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%">Nombre:</td>
                            <td width="30%">
                            	<input type="text" id="txtNombreUnidadBasica" name="txtNombreUnidadBasica" readonly="readonly" size="24"/>
				            	<input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica"/>
							</td>
                            <td align="right" class="tituloCampo" width="20%">Clave:</td>
                            <td width="30%"><input type="text" id="txtClaveUnidadBasica" name="txtClaveUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Descripción:</td>
                            <td rowspan="3"><textarea id="txtDescripcion" name="txtDescripcion" cols="20" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td><input type="text" id="txtMarcaUnidadBasica" name="txtMarcaUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td><input type="text" id="txtModeloUnidadBasica" name="txtModeloUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Versión:</td>
                            <td><input type="text" id="txtVersionUnidadBasica" name="txtVersionUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Año:</td>
                            <td><input type="text" id="txtAno" name="txtAno" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                            <td><input type="text" id="txtPlaca" name="txtPlaca" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Condición:</td>
                            <td><input type="text" id="txtCondicion" name="txtCondicion" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo">Fabricación:</td>
                            <td><input type="text" id="txtFechaFabricacion" name="txtFechaFabricacion" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanKilometraje; ?>:</td>
                            <td><input type="text" id="txtKilometraje2" name="txtKilometraje2" readonly="readonly" size="24"/></td>                            
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td rowspan="3" valign="top">
                        <table border="0" width="100%">
                        <tr>
                            <td align="center" class="imgBorde" colspan="2"><img id="imgArticulo" width="220"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Nro. Unidad Física:</td>
                            <td width="60%"><input type="text" id="txtIdUnidadFisica" name="txtIdUnidadFisica" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Almacén:</td>
                            <td><input type="text" id="txtAlmacen" name="txtAlmacen" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Compra:</td>
                            <td><input type="text" id="txtEstadoCompra" name="txtEstadoCompra" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Venta:</td>
                            <td>
                            	<div id="tdlstEstadoVenta"></div>
                            	<input type="hidden" id="hddEstadoVenta" name="hddEstadoVenta">
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                    <fieldset><legend class="legend">Colores</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%">Color Externo 1:</td>
                            <td width="30%"><input type="text" id="txtColorExterno1" name="txtColorExterno1" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo" width="20%">Color Interno 1:</td>
                            <td width="30%"><input type="text" id="txtColorInterno1" name="txtColorInterno1" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Color Externo 2:</td>
                            <td><input type="text" id="txtColorExterno2" name="txtColorExterno2" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo">Color Interno 2:</td>
                            <td><input type="text" id="txtColorInterno2" name="txtColorInterno2" readonly="readonly" size="24"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                	<td>
                    <fieldset><legend class="legend">Seriales</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><?php echo $spanSerialCarroceria; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialCarroceria" name="txtSerialCarroceria" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo" width="20%"><?php echo $spanSerialMotor; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialMotor" name="txtSerialMotor" readonly="readonly"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Vehículo:</td>
                            <td><input type="text" id="txtNumeroVehiculo" name="txtNumeroVehiculo" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Registro Legalización:</td>
                            <td><input type="text" id="txtRegistroLegalizacion" name="txtRegistroLegalizacion" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo">Registro Federal:</td>
                            <td><input type="text" id="txtRegistroFederal" name="txtRegistroFederal" readonly="readonly"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
            
        <tr>
            <td>
                <fieldset><legend class="legend">Datos Personales</legend>
                    <table width="100%" border="0">
                    <tbody><tr>
                        <td width="15%" align="right" class="tituloCampo">Cliente:</td>
                        <td width="85%">
                            <table cellspacing="0" cellpadding="0">
                            <tbody><tr>
                                <td><input type="text" style="text-align:right;" size="6"  name="txtIdCliente2" id="txtIdCliente2" /></td>
                                <td>
                                    <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" />
                                </td>
                                <td><input type="text" size="45" readonly="readonly" name="txtNombreCliente2" id="txtNombreCliente2" /></td>
                            </tr>
                            </tbody></table>
                        </td>
                    </tr>
                    </tbody></table>
                </fieldset>
            </td>
        </tr>   
        <tr>
            <td>
                <table>
                    <tr>
                        <td>
                            <fieldset>
                            <legend class="legend">Trade In</legend>
                                <table border="0" >
                                <tr style="display:none;">
                                    <td align="right" class="tituloCampo" width="120">Id Trade In:</td>
                                    <td align="right" width="120" style="white-space:nowrap;">
                                        <input type="text" id="hddIdTradein" name="idTradein" maxlength="12" style="text-align:right"/>
                                    </td>
                                </tr>
                                <tr align="left">                        
                                    <td align="right" class="tituloCampo" width="120">Allowance:</td>
                                    <td align="right" width="120" style="white-space:nowrap;">
                                        <input type="text" readonly="readonly" id="txtAllowance2" name="txtAllowance2" maxlength="12" style="text-align:right"/>
                                        <img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Limite máximo permitido" />
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo" width="120" >Payoff:</td>
                                    <td align="right" width="120" style="white-space:nowrap;">
                                        <input type="text" readonly="readonly" id="txtPayoff2" name="txtPayoff2" maxlength="12" style="text-align:right"/>
                                        <img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Total pago adeudado" />
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo" width="120" >ACV:</td>
                                    <td align="right" width="120" style="white-space:nowrap;">
                                        <input type="text" readonly="readonly" id="txtAcv2" name="txtAcv2" maxlength="12" style="text-align:right"/>
                                        <img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Valor actual del vehículo para ingreso a inventario y anticipo" />
                                    </td>
                                </tr>
                                </table>
                            </fieldset>
                        </td>

                        <td>
                            <fieldset><legend class="legend">Datos del Anticipo</legend>                                    
                                    <table border="0" width="100%">
                                    <tr>
                                            <td align="right" class="tituloCampo" width="40%">Nro. Anticipo:</td>
                                            <td width="60%">
                                                    <input type="text" id="txtNumeroAnticipo2" name="txtNumeroAnticipo2" readonly="readonly" size="20" style="text-align:center"/>
                                            </td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo" width="120">Departamento:<input type="hidden" id="hddIdModulo" name="hddIdModulo"></input></td>
                                            <td align="center" id="tdlstModulo2"></td>
                                    </tr>
                                    <tr>
                                            <td align="right" class="tituloCampo" width="120">Monto:</td>
                                            <td><input type="text" id="txtMontoAnticipo2" name="txtMontoAnticipo2" readonly="readonly" size="20" style="text-align:right" /></td>
                                    </tr>
                                    </table>
                            </fieldset>
                        </td>
                        <td>
                            <fieldset>
                                <legend class="legend">Forma de Pago</legend>
                                <table border="0" width="100%">                                    
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="164">Tipo de Pago:<input type="hidden" id="hddIdTipoPago" name="hddIdTipoPago"></input></td>
                                        <td>
                                            <input type="text" id="selTipoPago2" readonly="readonly" ></input>                                            
                                        </td>
                                    </tr>
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="120">Concepto de Pago:<input type="hidden" id="hddIdConceptoPago" name="hddIdConceptoPago"></input></td>
                                        <td>
                                            <input id="selConceptoPago2" readonly="readonly"></input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo" width="120">Subtotal:</td>
                                        <td align="right" width="120"><input type="text" id="txtSubTotal2" name="txtSubTotal2" maxlength="12" readonly="readonly" style="text-align:right"/></td>
                                    </tr>
                                </table>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="20">
                            <table border="0" width="100%">
                                <tr>
                                    <td align="right" class="tituloCampo" width=""><span class="textoRojoNegrita"></span>Adeudado a:</td>
                                    <td width="">
                                        <table cellspacing="0" cellpadding="0">
                                        <tbody>
                                            <tr>
                                                <td><input type="text" style="text-align:right;" size="6" readonly="readonly" name="txtIdClienteDeuda2" id="txtIdClienteDeuda2" /></td>                                            
                                                <td><input type="text" size="45" readonly="readonly" name="txtNombreClienteDeuda2" id="txtNombreClienteDeuda2"/></td>
                                            </tr>
                                        </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>      
            </td>
        </tr>
        <tr>
            <td>
                <fieldset>
                <legend class="legend">Observación Vale - Anticipo</legend>
                    <table border="0" width="100%">
                    <tr align="left">
                        <td align="right" class="tituloCampo" rowspan="4" width="14%">Observación:</td>
                        <td rowspan="4" width="52%"><textarea id="txtObservacion2" name="txtObservacion2" readonly="readonly" rows="3" style="width:99%"></textarea></td>                        
                    </tr>
                    <tr>
                            <td>&nbsp;</td>
                    </tr>
                    <tr>
                            <td>&nbsp;</td>
                    </tr>
                    </table>
                </fieldset>
            </td>
        </tr>
            
        <tr>
            <td align="right"><hr>
            	<a class="modalImg" id="aGuardarUnidadFisica" rel="#divFlotante2"></a>
<!--                <button type="button" id="btnGuardarUnidadFisica" name="btnGuardarUnidadFisica" onclick="validarFrmUnidadFisica();">Guardar</button>-->
                <button type="button" id="btnGenerarAnticipo" name="btnGenerarAnticipo" onclick="if(confirm('¿Seguro deseas generar un nuevo anticipo a partir de este vehículo?')){ this.disabled = true; validarFrmGenerarAnticipo(); }">Generar Nuevo Anticipo</button>
                <button type="button" id="btnCancelarGenerarAnticipo" name="btnCancelarGenerarAnticipo" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
	
    <table border="0" id="tblLista" style="display:none" width="960">
    <tr>
    	<td>
        	<form id="frmBuscarLista" name="frmBuscarLista" onsubmit="return false;" style="margin:0">
            	<table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                	<td><input type="text" id="txtCriterioBuscarLista" name="txtCriterioBuscarLista" onkeyup="byId('btnBuscarLista').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscarLista" name="btnBuscarLista">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscarLista'].reset(); byId('btnBuscarLista').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divLista" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
	
<form id="frmAjusteInventario" name="frmAjusteInventario" style="margin:0" onsubmit="return false;">
    <div id="tblAjusteInventario" style="max-height:520px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
        	<td>
            	<table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" size="6" readonly="readonly" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
		</tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td valign="top" width="65%">
                    <fieldset><legend class="legend">Datos Personales</legend>
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                            <td width="85%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" onblur="xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, '', '', 'true', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarCliente" rel="#divFlotante2" onclick="abrirDivFlotante2(null, 'tblListaCliente');">
                                        <button type="button" id="btnListarCliente" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%">Nro. Unidad Fisica:</td>
                            <td width="10%"><input type="text" id="txtIdUnidadFisicaAjuste" name="txtIdUnidadFisicaAjuste" readonly="readonly" size="24" style="text-align:center"/></td>
                            <td width="10%">
                                <a  class="modalImg" id="aNuevo" rel="#divFlotante3" onclick="byId('btnBuscarVehiculo').click();">
                                    <button type="button" id="btnListarVehiculos" name="btnListarVehiculos" title="Listar Vehículos"><img src="../img/iconos/help.png"/></button>
                                </a>
                            </td>
                            <td align="right" class="tituloCampo" width="20%">Estado de Venta:</td>
                            <td width="30%"><input type="text" id="txtEstadoVenta" name="txtEstadoVenta" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        </table>
                        
                    </td>
                    <td valign="top" width="35%" style="display:none;" id="datosVale">
                    <fieldset><legend class="legend">Datos del Vale</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Nro. Vale:</td>
                            <td width="60%">
                                <input type="hidden" id="txtIdVale" name="txtIdVale" readonly="readonly"/>
                                <input type="text" id="txtNumeroVale" name="txtNumeroVale" readonly="readonly" size="20" style="text-align:center;"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Vale</td>
                            <td>
                                <select id="lstTipoVale" name="lstTipoVale" onchange="xajax_asignarTipoVale(this.value);">
<!--                                    <option value="-1">[ Seleccione ]</option>-->
                                    <option value="1">Entrada / Salida</option>
<!--                                    <option value="3">Nota de Crédito de CxC</option>-->
                                </select>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov:</td>
                            <td id="tdlstTipoMovimiento"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                            <td id="tdlstClaveMovimiento"></td>
                        </tr>
                        <tr align="left" id="trNroDcto" style="display:none">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Nota Crédito:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <input type="hidden" id="hddIdDcto" name="hddIdDcto" readonly="readonly"/>
                                        <input type="text" id="txtNroDcto" name="txtNroDcto" readonly="readonly" size="20" style="text-align:center;"/>
                                    </td>
                                    <td>
                                    <a class="modalImg" id="aListarDcto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista');">
                                        <button type="button" id="btnListarDcto" name="btnListarDcto" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr id="trUnidadFisica">
        	<td>
            <fieldset><legend class="legend">Unidad Física</legend>
            	<table width="100%">
                <tr>
                	<td valign="top" width="68%">
                    <fieldset><legend class="legend">Datos de la Unidad</legend>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                            <td id="tdlstUnidadBasica" width="30%"></td>
                            <td align="right" class="tituloCampo" width="20%">Clave:</td>
                            <td width="30%"><input type="text" id="txtClaveUnidadBasicaAjuste" name="txtClaveUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Descripción:</td>
                            <td rowspan="3"><textarea id="txtDescripcionAjuste" name="txtDescripcionAjuste" cols="20" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td><input type="text" id="txtMarcaUnidadBasicaAjuste" name="txtMarcaUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td><input type="text" id="txtModeloUnidadBasicaAjuste" name="txtModeloUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Versión:</td>
                            <td><input type="text" id="txtVersionUnidadBasicaAjuste" name="txtVersionUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Año:</td>
                            <td id="tdlstAno"></td>
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                            <td><input type="text" id="txtPlacaAjuste" name="txtPlacaAjuste" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Condición:</td>
                            <td id="tdlstCondicion"></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fabricación:</td>
                            <td><input type="text" id="txtFechaFabricacionAjuste" name="txtFechaFabricacionAjuste" autocomplete="off" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?>:</td>
                            <td><input type="text" id="txtKilometraje" name="txtKilometraje" onkeypress="return validarSoloNumeros(event);" size="24" style="text-align:right"/></td>                            
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top" width="32%">
                    	<table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Almacén:</td>
                            <td id="tdlstAlmacenAjuste" width="60%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Compra:</td>
                            <td id="tdlstEstadoCompraAjuste"><input type="text" id="txtEstadoCompraAjuste" name="txtEstadoCompraAjuste" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estado Venta:</td>
                            <td id="tdlstEstadoVentaAjuste"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                            <td id="tdlstMoneda"></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr>
                	<td valign="top">
                    <fieldset><legend class="legend">Colores</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Color Externo 1:</td>
                            <td id="tdlstColorExterno1" width="30%"></td>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Color Interno 1:</td>
                            <td id="tdlstColorInterno1" width="30%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Color Externo 2:</td>
                            <td id="tdlstColorExterno2"></td>
                            <td align="right" class="tituloCampo">Color Interno 2:</td>
                            <td id="tdlstColorInterno2"></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                	<td>
                    <fieldset><legend class="legend">Seriales</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialCarroceria; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialCarroceriaAjuste" name="txtSerialCarroceriaAjuste"/></td>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialMotor; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialMotorAjuste" name="txtSerialMotorAjuste"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Vehículo:</td>
                            <td><input type="text" id="txtNumeroVehiculoAjuste" name="txtNumeroVehiculoAjuste"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Legalización:</td>
                            <td><input type="text" id="txtRegistroLegalizacionAjuste" name="txtRegistroLegalizacionAjuste"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Federal:</td>
                            <td><input type="text" id="txtRegistroFederalAjuste" name="txtRegistroFederalAjuste"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
			</fieldset>
            </td>
        </tr>
        
        <tr>
            <td>
                <table>
                    <tr>
                        <td>
                            <fieldset>
                            <legend class="legend">Trade In</legend>
                                <table border="0" >
                                <tr align="left">                        
                                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Allowance:</td>
                                    <td align="right" width="120" style="white-space:nowrap;">
                                        <input type="text" class="inputHabilitado"  id="txtAllowance" name="txtAllowance" onblur="setFormatoRafk(this,2);" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/>
                                        <img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Limite máximo permitido" />
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo" width="120" ><span class="textoRojoNegrita">*</span>Payoff:</td>
                                    <td align="right" width="120" style="white-space:nowrap;">
                                        <input type="text" class="inputHabilitado"  id="txtPayoff" name="txtPayoff" onblur="setFormatoRafk(this,2);" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/>
                                        <img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Total pago adeudado" />
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo" width="120" ><span class="textoRojoNegrita">*</span>ACV:</td>
                                    <td align="right" width="120" style="white-space:nowrap;">
                                        <input type="text" class="inputHabilitado"  id="txtAcv" name="txtAcv" onblur="setFormatoRafk(this,2); copiarMonto(this.value);" maxlength="12" onkeypress="return validarSoloNumerosReales(event); " style="text-align:right"/>
                                        <img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Valor actual del vehículo para ingreso a inventario y anticipo" />
                                    </td>
                                </tr>
                                </table>
                            </fieldset>
                        </td>
                        
                        <td>
                            <fieldset><legend class="legend">Datos del Anticipo</legend>                                    
                                    <table border="0" width="100%">
                                    <tr>
                                            <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Nro. Anticipo:</td>
                                            <td width="60%">
                                                    <input type="text" id="txtNumeroAnticipo" name="txtNumeroAnticipo" value="Por Asignar" readonly="readonly" size="20" style="text-align:center"/>
                                            </td>
                                    </tr>
                                    <tr>
                                            <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Departamento:</td>
                                            <td align="center" id="tdlstModulo"></td>
                                    </tr>
                                    <tr>
                                            <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Monto:</td>
                                            <td><input type="text" id="txtMontoAnticipo" name="txtMontoAnticipo" readonly="readonly" size="20" style="text-align:right" onblur="//$('txtSaldoAnticipo').value = formato(parsenum(this.value)); $('hddMontoFaltaPorPagar').value = formato(parsenum(this.value));$('hddSaldoAnticipo').value = formato(parsenum(this.value)); this.value = formato(parsenum(this.value));"/></td>
                                    </tr>
                                    </table>
                            </fieldset>
                        </td>
                        <td>
                            <fieldset>
                                <legend class="legend">Forma de Pago</legend>
                                <table border="0" width="100%">                                    
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="164"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                                        <td id="tdTipoPago">
                                            <select name="selTipoPago" id="selTipoPago" onChange="cambiar()">
                                                <option>Tipo pago</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr id="trConceptoPago" align="left">
                                        <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Concepto de Pago:</td>
                                        <td id="tdConceptoPago">
                                            <select name="selConceptoPago" id="selConceptoPago">
                                                <option value="-1">[ Seleccione ]</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Subtotal:</td>
                                        <td align="right" width="120"><input type="text" id="txtSubTotal" name="txtSubTotal" onblur="//setFormatoRafk(this,2);" maxlength="12" onkeypress="//return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/></td>
                                    </tr>
                                </table>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="20">
                            <table border="0" width="100%">
                                <tr>
                                    <td align="right" class="tituloCampo" width=""><span class="textoRojoNegrita"></span>Adeudado a:</td>
                                    <td width="">
                                        <table cellspacing="0" cellpadding="0">
                                        <tbody><tr>
                                            <td><input type="text" style="text-align:right;" size="6" onblur="xajax_asignarClienteAdeudado(this.value);" name="txtIdClienteDeuda" id="txtIdClienteDeuda" class="inputHabilitado"></td>
                                            <td>
                                                <button title="Listar" name="btnListarClienteDeuda" id="btnListarClienteDeuda" onClick="byId('btnBuscarClienteAdeudado').click();" type="button"><img src="../img/iconos/help.png"></button>
                                            </td>
                                            <td><input type="text" size="45" readonly="readonly" name="txtNombreClienteDeuda" id="txtNombreClienteDeuda"/></td>
                                        </tr>
                                        </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>                
            </td>            
        </tr>
            
        <tr>
            <td>
                <fieldset>
                <legend class="legend">Observación para Vale - Anticipo</legend>
                    <table border="0" width="100%">
                    <tr align="left">
                        <td align="right" class="tituloCampo" rowspan="4" width="14%"><span class="textoRojoNegrita">*</span>Observación:</td>
                        <td rowspan="4" width="52%"><textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea></td>                        
                    </tr>
                    <tr>
                            <td>&nbsp;</td>
                    </tr>
                    <tr>
                            <td>&nbsp;</td>
                    </tr>
                    </table>
                </fieldset>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" style="display:none;" id="btnGuardarAjusteInventario" name="btnGuardarAjusteInventario" onclick="validarFrmAjusteInventario();">Guardar</button>
                <button type="button" style="display:none;" id="btnGenerarTradein" name="btnGenerarTradein" onclick="validarFrmGenerarTradein();">Generar a Trade-In</button>
                <button type="button" id="btnCancelarAjusteInventario" name="btnCancelarAjusteInventario" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
  	
    <table border="0" id="tblListaCliente" width="760">
    <tr>
    	<td>
        <form id="frmBuscarCliente" name="frmBuscarCliente" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmAjusteInventario'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaCliente" style="width:100%;"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaCliente" name="btnCancelarListaCliente" onclick="
            byId('tblAjusteInventario').style.display = '';
            byId('tblListaCliente').style.display = 'none';">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:12000;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%">Listado Unidades Físicas</td></tr></table></div>
      
        <table border="0" id="tblListaVehiculo" width="960" >
            <tr>
                <td>
                <form id="frmBuscarVehiculo" name="frmBuscarVehiculo" style="margin:0" onsubmit="return false;">
                    <table align="right">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="120">Criterio:</td>
                        <td><input type="text" id="txtCriterioBuscarVehiculo" name="txtCriterioBuscarVehiculo" onkeyup="byId('btnBuscarVehiculo').click();"/></td>
                        <td>
                            <button type="submit" id="btnBuscarVehiculo" name="btnBuscarVehiculo" onclick="xajax_buscarVehiculo(byId('txtCriterioBuscarVehiculo').value);">Buscar</button>
                            <button type="button" onclick="byId('txtCriterioBuscarVehiculo').value = ''; byId('btnBuscarVehiculo').click();">Limpiar</button>
                        </td>
                    </tr>
                    </table>
                </form>
                </td>
            </tr>
            <tr>
                <td><div id="divListaVehiculos" style="width:100%;"></div></td>
            </tr>
            <tr>
                <td align="right"><hr>
                    <button type="button" id="btnCancelarListaVehiculos" name="btnCancelarListaVehiculos" onclick="
                    byId('divFlotante3').style.display = 'none';">Cerrar</button>
                </td>
            </tr>
        </table>
</div>



<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:12000;">
	<div id="divFlotanteTitulo4" class="handle"><table><tr><td id="tdFlotanteTitulo4" width="100%">Listado Clientes</td></tr></table></div>
      
        <table border="0" width="760" >
            <tr>
                <td>
                <form id="frmBuscarClienteAdeudado" name="frmBuscarClienteAdeudado" style="margin:0" onsubmit="return false;">
                    <table align="right">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="120">Criterio:</td>
                        <td><input type="text" id="txtCriterioBuscarClienteAdeudado" name="txtCriterioBuscarClienteAdeudado" onkeyup="byId('btnBuscarClienteAdeudado').click();"/></td>
                        <td>
                            <button type="submit" id="btnBuscarClienteAdeudado" name="btnBuscarClienteAdeudado" onclick="xajax_buscarClienteAdeudado(byId('txtCriterioBuscarClienteAdeudado').value);">Buscar</button>
                            <button type="button" onclick="byId('txtCriterioBuscarClienteAdeudado').value = ''; byId('btnBuscarClienteAdeudado').click();">Limpiar</button>
                        </td>
                    </tr>
                    </table>
                </form>
                </td>
            </tr>
            <tr>
                <td><div id="divListaClienteAdeudado" style="width:100%;"></div></td>
            </tr>
            <tr>
                <td align="right"><hr>
                    <button type="button" id="btnCancelarListaClienteAdeudado" onclick="byId('divFlotante4').style.display = 'none';">Cerrar</button>
                </td>
            </tr>
        </table>
</div>

<script>
byId('txtCriterio').className = "inputHabilitado";

function openImg(idObj) {
	var oldMaskZ = null;
	var $oldMask = $(null);
	
	$(".modalImg").each(function() {
		$(idObj).overlay({
			//effect: 'apple',
			oneInstance: false,
			zIndex: 10100,
			
			onLoad: function() {
				if ($.mask.isLoaded()) {
					oldMaskZ = $.mask.getConf().zIndex; // this is a second overlay, get old settings
					$oldMask = $.mask.getExposed();
					$.mask.getConf().closeSpeed = 0;
					$.mask.close();
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0,
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false
					});
				} // Other onLoad functions
			},
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) { // re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0
					});
					
					$(".apple_overlay").css("zIndex", oldMaskZ + 2); // Assumes the other overlay has apple_overlay class
				}
			}
		}).load();
	});
}

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEstadoCompraBuscar('lstEstadoCompraBuscar', 'Ajuste');
xajax_cargaLstEstadoVentaBuscar('lstEstadoVentaBuscar', 'Ajuste');
xajax_cargaLstAlmacen('lstAlmacen', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaUnidadFisica(0, 'CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

xajax_cargaLstModulo();
xajax_cargarTipoPago();
xajax_cargarConceptoPago();
xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', '2', '', '5,6');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo4");
var theRoot   = document.getElementById("divFlotante4");
Drag.init(theHandle, theRoot);

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaHasta").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"orange"
	});
};


</script>