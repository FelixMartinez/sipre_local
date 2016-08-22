<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_transferencia_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_transferencia_form2.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Transferencia</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCaja.css"/>
	
	<script type="text/javascript" language="javascript" src="../vehiculos/vehiculos.inc.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/jquery.maskedinput.js"></script>
		
	<script language="javascript">
	
	function cambioTipoTransferencia(lstTipoTransferencia){
		
		//cierro ventanas
		limpiarTransferencia();		
//		limpiarCliente();
		if(lstTipoTransferencia == 1 || lstTipoTransferencia == 3){//1 = cliente, 3 = poliza no devengada PND
			
			//byId("btnCliente").disabled = false;
			//byId("txtIdCliente").disabled = false;	
			//byId("txtIdCliente").className = 'inputHabilitado';
			//byId("tituloCliente").innerHTML = "<span class='textoRojoNegrita'>*</span>Cliente:";
						
			if(lstTipoTransferencia == 1){//1 = cliente		
				byId("btnAgregarAnticipo").style.display = "none";
				byId("tituloAnticipos").innerHTML = "Anticipos a Pagar(Bono Suplidor):";	
			}else if(lstTipoTransferencia == 3){//3 = poliza no devengada PND
				byId("btnAgregarAnticipo").style.display = "";
				byId("tituloAnticipos").innerHTML = "<span class='textoRojoNegrita'>*</span>Anticipos a Pagar(PND):";	
				byId("tituloListadoAnticipos").innerHTML = "Anticipos (Bono Suplidor)";
			}
			
		}else if(lstTipoTransferencia == 2){//2 = bono suplidor
			
			//byId("btnCliente").disabled = true;
			//byId("txtIdCliente").disabled = true;
			//byId("txtIdCliente").className = '';
			//byId("tituloCliente").innerHTML = "Cliente:";
			
			byId("btnAgregarAnticipo").style.display = "";
			byId("tituloAnticipos").innerHTML = "<span class='textoRojoNegrita'>*</span>Anticipos a Pagar(Bono Suplidor):";
			byId("tituloListadoAnticipos").innerHTML = "Anticipos (PND)";
		}	
	}
	
	function limpiarCliente(){
		byId("txtIdCliente").value = "";
		byId("txtNombreCliente").value = "";
		byId("txtDireccionCliente").value = "";
		byId("txtRifCliente").value = "";
		byId("txtTelefonosCliente").value = "";
	}
	
	function limpiarTransferencia(){
		//cierro todas las ventanas:
		byId('btnCancelarMetodoPago').click();
		byId('btnCancelarMontoPagar').click();
		byId('btnCancelarListadoAnticipos').click();
		
		//quito anticipos y reestablesco montos de la trasnferencia
		$('#tablaAnticiposAgregados tr:not(:first):not(:last)').remove();//quita anticipos si los hay
		copiarMontoTransferencia();//reinicio monto por pagar
		byId("txtMontoPagado").value = '0.00';//reinicio monto pagado
		
	}
	
	function validarSoloNumerosConPunto(evento){
		if (arguments.length > 1)
			color = arguments[1];
		
		if (evento.target)
			idObj = evento.target.id;
		else if (evento.srcElement)
			idObj = evento.srcElement.id;
		
		teclaCodigo = (document.all) ? evento.keyCode : evento.which;
		
		if ((teclaCodigo != 0)
		&& (teclaCodigo != 8)
		&& (teclaCodigo != 13)
		&& (teclaCodigo != 46)
		&& (teclaCodigo <= 47 || teclaCodigo >= 58)) {
			return false;
		}
	}
	
	function validarTextoNumeros(e){
		tecla = (document.all) ? e.keyCode : e.which;
		
		if (tecla == 0 || tecla == 8){//8 = delete
			return true;
		}else if(tecla == 13){
			return false;
		}else{
			patron = /[-0-9A-Za-z\s ]/;
			te = String.fromCharCode(tecla);
			return patron.test(te);
		}
	}
	
	function validarGuardar(){
		
		tipoTransferencia = byId("lstTipoTransferencia").value;		
		error = false;
		
		//if(tipoTransferencia === "1" || tipoTransferencia === "3"){//tipo cliente y pnd requiere cliente
		//		if (!(validarCampo('txtIdCliente','t','') == true)){					
		//			validarCampo('txtIdCliente','t','');
		//			error = true;
		//		}
		//}
		
		if(!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('lstTipoTransferencia','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtFecha','t','fecha') == true
		&& validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('txtObservacionTransferencia','t','') == true
		&& validarCampo('selBancoCliente','t','lista') == true
		&& validarCampo('selBancoCompania','t','lista') == true
		&& validarCampo('selNumeroCuenta','t','lista') == true
		&& validarCampo('numeroTransferencia','t','') == true
		&& validarCampo('txtMontoTransferencia','t','monto') == true
		)){			
			validarCampo('txtIdEmpresa','t','');
			validarCampo('lstTipoTransferencia','t','');			
			validarCampo('txtIdCliente','t','');			
			validarCampo('txtFecha','t','fecha');
			validarCampo('lstModulo','t','lista');
			validarCampo('txtObservacionTransferencia','t','');
			validarCampo('selBancoCliente','t','lista');
			validarCampo('selBancoCompania','t','lista');
			validarCampo('selNumeroCuenta','t','lista');
			validarCampo('numeroTransferencia','t','');
			validarCampo('txtMontoTransferencia','t','monto');
			error = true;
		}
		
		if(error === true){
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
		}else{
			if(tipoTransferencia === "1"){
				mensaje = "¿Seguro deseas generar la transferencia?";
			}else if(tipoTransferencia === "2"){
				mensaje = "¿Seguro deseas generar pago de anticipo suplidor?";
			}else if(tipoTransferencia === "3"){
				mensaje = "¿Seguro deseas generar pago de anticipo PND?";
			}
			
			if(confirm(mensaje)){ 
				xajax_guardarTransferencia(xajax.getFormValues('frmDcto'),xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmAnticipos'));
			}
			
		}
		
	}
	
	
	function validarAgregarAnticipo(){
		var saldo = byId('txtSaldoDocumento').value.replace(/,/gi,'');
		var monto = byId('txtMontoDocumento').value.replace(/,/gi,'');
		var montoFaltaPorPagar = byId('txtMontoPorPagar').value.replace(/,/gi,'');
		
		if (parseFloat(saldo) < parseFloat(monto)){
			alert("El monto a pagar no puede ser mayor que el saldo del documento");
		}else{
			if (parseFloat(montoFaltaPorPagar) >= parseFloat(monto)){
				if(confirm("Desea cargar el pago?")){										
					byId('divFlotante1').style.display = 'none';
					byId('divFlotante2').style.display = 'none';
					
					xajax_cargarPago(xajax.getFormValues('frmAnticipos'),xajax.getFormValues('frmDetalleAnticipo'));
				}
			}else{
				alert("El monto a pagar no puede ser mayor que el saldo de la Transferencia");
			}
		}
	}
	
	function copiarMontoTransferencia(){
		var saldo = byId('txtMontoTransferencia').value;
		byId('txtMontoPorPagar').value = saldo;
		calcularTotal();
	}
	
	function eliminarAnticipo(objBoton){
		if(confirm("¿Seguro deseas eliminar el anticipo?")){
			 $(objBoton).closest('tr').remove();
			 colorTabla();
			 calcularTotal();
		}
	}
	
	function calcularTotal(){
		xajax_calcularTotal(byId('txtMontoTransferencia').value, xajax.getFormValues('frmAnticipos'));
	}
	
	function colorTabla(){		
	     $('#tablaAnticiposAgregados tr:not(:first):not(:last)').removeClass();//limpio de clases
         $('#tablaAnticiposAgregados tr:not(:first):not(:last):odd').addClass('trResaltar5');//odd impar
		 $('#tablaAnticiposAgregados tr:not(:first):not(:last):even').addClass('trResaltar4');//even par
	}
	
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cj.php"); ?></div>
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCaja">Transferencia</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmDcto" name="frmDcto" style="margin:0" onsubmit="return false;">
				<table border="0" width="100%">
				<tr>
					<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
					<td>
						<table cellpadding="0" cellspacing="0">
						<tr>
							<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
							<td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
						</tr>
						</table>
					</td>
					<td align="right" class="tituloCampo" width="120">Fecha Registro:</td>
					<td><input type="text" id="txtFecha" name="txtFecha" readonly="readonly" size="10" style="text-align:center"/></td>
				</tr>
				<tr>
					<td colspan="4">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td valign="top" width="70%">
                            <fieldset><legend class="legend">Tipo de Transferencia</legend>
                            	<table border="0" width="100%">
									<tr>
                                        <td align="right" class="tituloCampo" width="164"><span class="textoRojoNegrita">*</span>Tipo de Transferencia:</td>
                                        <td colspan="3" id="tdTipoTransferenciaList">
                                        	
                                        </td>
                                    </tr>
                               	</table>
                            </fieldset>
                            
                            
							<fieldset id="datosCliente" ><legend class="legend">Cliente</legend>
								<table border="0" width="100%">
								<tr>
									<td align="right" class="tituloCampo" id="tituloCliente"><span class="textoRojoNegrita">*</span>Cliente:</td>
									<td colspan="3">
										<table cellpadding="0" cellspacing="0">
										<tr>
											<td><input type="text" readonly="readonly" class="inputHabilitado" id="txtIdCliente" name="txtIdCliente" size="6" style="text-align:right" /></td>
											<td><button type="button" id="btnCliente" name="btnCliente" onclick=
                                            					
                                            "document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();" 
                                            title="Listar"><img src="../img/iconos/help.png"/></button></td>
											<td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
										</tr>
										</table>
									</td>
								</tr>
								<tr align="left">
									<td align="right" class="tituloCampo" rowspan="3" width="18%">Dirección:</td>
									<td rowspan="3" width="44%"><textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly" rows="3"></textarea></td>
									<td align="right" class="tituloCampo" width="18%"><?php echo $spanClienteCxC; ?>:</td>
									<td width="20%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
								</tr>
								<tr align="left">
									<td align="right" class="tituloCampo">Teléfono:</td>
									<td><input type="text" id="txtTelefonosCliente" name="txtTelefonosCliente" readonly="readonly" size="12" style="text-align:center"/></td>
								</tr>
								</table>
							</fieldset>							
							</td>
							<td valign="top" width="30%">
							<fieldset><legend class="legend">Transferencia</legend>
								<input type="hidden" id="hddIdTransferencia" name="hddIdTransferencia"/>
								<table border="0" width="100%">
								<tr>
									<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Módulo:</td>
									<td align="left" id="tdlstModulo"></td>
								</tr>
								</table>
							</fieldset>
                            <fieldset><legend class="legend">Observación</legend>
							<table>
							<tr>
								<td width="120" rowspan="2" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
								<td colspan="3" rowspan="2" align="left"><label><textarea name="txtObservacionTransferencia" id="txtObservacionTransferencia" cols="50" rows="3" class="inputHabilitado"></textarea></label></td>
							</tr>
							</table>
							</fieldset>
							</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		<tr>
			<td width="100%">
			<form id="frmDetallePago" name="frmDetallePago" onsubmit="return false;">
				<fieldset><legend class="legend">Forma de Pago</legend>
				<table border="0" width="100%">
                <tr align="left">
                    <td id="tdEtiquetaBancoOFechaDep" align="right" class="tituloCampo" width="164"><span class="textoRojoNegrita">*</span>Banco Cliente:</td>
                    <td id="tdBancoCliente" scope="row">
                    </td>
                </tr>
                <tr>
                	<td height="23" align="right" id="tdTituloBancoCompania" class="tituloCampo"><span class="textoRojoNegrita">*</span>Banco Compañia:</td>
                    <td height="23" align="left" id="tdBancoCompania"></td>
                </tr>
                <tr align="left">
                    <td id="tdTituloNumeroCuenta" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. de Cuenta:</td>
                    <td id="tdNumeroCuentaSelect" colspan="8">
                   		<select id="selNumeroCuenta" name="selNumeroCuenta" class="inputHabilitado">
                            <option value="">Seleccione</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td id="tdTituloNumeroDocumento" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Referencia:</td>
                    <td id="tdNumeroDocumento" colspan="8">
                        <input type="text" id="numeroTransferencia" name="numeroTransferencia"  size="30" onkeypress="return validarTextoNumeros(event);" class="inputHabilitado"/>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto Transferencia:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0"><!-- botones()-->
                        <tr>
                        	<td><input type="text" name="txtMontoTransferencia" id="txtMontoTransferencia" onblur="this.value=formato(parsenum(this.value)); copiarMontoTransferencia();" onkeypress="return validarSoloNumerosConPunto(event);" style="text-align:right" class="inputHabilitado"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
				</table>
				</fieldset>
			</form>
			</td>
		</tr>
        
        
        <tr>
			<td width="100%">
			<form id="frmAnticipos" name="frmAnticipos" onsubmit="return false;">
				<fieldset><legend class="legend">Anticipos (Bono Suplidor / PND)</legend>
                
                
                <table border="0" >
                <tr align="left">
                    <td align="right" class="tituloCampo" width="214" id="tituloAnticipos">Anticipos a Pagar(Bono Suplidor):</td>
                    <td scope="row">
                        <button title="Buscar Documento" onclick="byId('btnbuscarAnticipo').click();" id="btnAgregarAnticipo" type="button" style="display:none;"><img height="16" width="16" src="../img/iconos/find.png"></button>
                    </td>
                    
                </tr> 
                </table>
                    
                 <table width="100%">
                    <tr>
                    	<td>                    
	                        <table width="100%" id="tablaAnticiposAgregados">
                                <tbody>
                                    <tr align="center" class="tituloColumna">
                                        <td width="15%" class="tituloColumna">Nro Anticipo</td>
                                        <td width="10%" class="tituloColumna">Fecha</td>
                                        <td width="65%" class="tituloColumna">Descripci&oacute;n</td>
                                        <td width="10%" class="tituloColumna">Monto</td>
                                        <td class="tituloColumna"></td>
                                    </tr>
                                    <tr id="trItmPie"></tr>                   
                                </tbody>
                         	</table>                
                 		</td>
                 	</tr>
                     <tr>
                         <td>
                             <table width="100%">
                                <tr class="tituloColumna">
                                    <td><strong>Saldo Transferencia:
                                        <input type="text" class="trResaltarTotal3" style="text-align:right;" value="0" readonly="readonly" id="txtMontoPorPagar" name="txtMontoPorPagar">
                                        <input type="hidden" id="hddMontoPorPagar" name="hddMontoPorPagar" value=""></strong>
                                    </td>
                                    <td><strong>Saldo Usado en Pagos:
                                        <input type="text" class="trResaltarTotal" style="text-align:right" readonly="readonly" value="0" id="txtMontoPagado" name="txtMontoPagado"></strong>
                                    </td>
                                </tr>
                            </table>	
                        </td>
                    </tr>
                </table>
				</fieldset>
			</form>
			</td>
		</tr>
		
		<tr align="right">
			<td colspan="8"><hr>
				<button type="button" id="btnGuardarPago" name="btnGuardarPago" onclick="validarGuardar();" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
				<button type="button" id="btnCancelar" name="btnCancelar" onclick="top.history.back();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
		</tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>	
	<table border="0" id="tblListados" style="display:none" width="700px">
	<tr id="trBuscarCliente">
		<td>
			<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
			<table align="right">
			<tr>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioBusqCliente" name="txtCriterioBusqCliente" onkeyup="byId('btnBuscarCliente').click();"/></td>
				<td>                
					<button type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick=" xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>                
					<button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
			</tr>
			</table>
			</form>
		</td>
	</tr>
	<tr>
		<td>
			<form id="frmListado" name="frmListado" style="margin:0" onsubmit="return false;">
			<table width="100%">
			<tr>
				<td id="tdListado"></td>
			</tr>
			<tr>
				<td align="right"><hr>
					<button type="button" id="btnCancelarMetodoPago" name="btnCancelarMetodoPago" onclick="byId('divFlotante').style.display = 'none';">Cancelar</button>
				</td>
			</tr>
			</table>
			</form>
		</td>
	</tr>
	</table>
</div>



<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tituloListadoAnticipos" width="100%"></td></tr></table></div>
	
	<table border="0">
	<tr id="trbuscarAnticipo" >
		<td>
			<form id="frmBuscarAnticipo" name="frmBuscarAnticipo" onsubmit="return false;" style="margin:0">
			<table align="right">
			<tr>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioAnticipo" name="txtCriterioAnticipo" onkeyup="byId('btnbuscarAnticipo').click();"/></td>
				<td>
					<button type="button" id="btnbuscarAnticipo" onclick="xajax_buscarAnticipo(xajax.getFormValues('frmBuscarAnticipo'),xajax.getFormValues('frmAnticipos'), xajax.getFormValues('frmDcto'));">Buscar</button>
					<button type="button" onclick="document.forms['frmBuscarAnticipo'].reset(); byId('btnbuscarAnticipo').click();">Limpiar</button>
				</td>
			</tr>
			</table>
			</form>
		</td>
	</tr>
	<tr>
		<td>
			
			<table width="100%">
			<tr>
				<td id="tdListadoAnticipo"></td>
			</tr>
			<tr>
				<td align="right"><hr>
					<button type="button" id="btnCancelarListadoAnticipos" name="btnCancelar" onclick="byId('divFlotante2').style.display = 'none';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
				</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
</div>


<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td width="100%">Monto a Pagar</td></tr></table></div>
	<form id="frmDetalleAnticipo" name="frmDetalleAnticipo" style="margin:0">
	<table border="0" width="290px">
	<tr>
		<td class="tituloCampo" width="120">Nro. Documento:</td>
		<td><input type="text" name="txtNroDocumento" id="txtNroDocumento" size="15"/></td>
	</tr>
	<tr>
		<td class="tituloCampo" width="120">Monto:</td>
		<td><input type="text" name="txtSaldoDocumento" id="txtSaldoDocumento" readonly="readonly" size="15" style="text-align:right"/></td>
	</tr>
	<tr>
		<td class="tituloCampo" width="120">Monto a Pagar:</td>
		<td><input type="text" name="txtMontoDocumento" id="txtMontoDocumento" onChange="this.value=formato(parsenum(this.value));" onkeypress="return validarSoloNumerosConPunto(event);" size="15" style="text-align:right" readonly="readonly"/></td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
            <input type="hidden" id="hddIdDocumento" name="hddIdDocumento"/>
			<button type="button" id="btnAceptarMontoDocumento" onclick="validarAgregarAnticipo();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button>
			<button type="button" id="btnCancelarMontoPagar" onclick="byId('divFlotante1').style.display='none';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
	</form>
</div>

<script language="javascript">
byId('txtFecha').value = "<?php echo date("d-m-Y")?>";

xajax_validarAperturaCajaXajax();
xajax_asignarEmpresaUsuario(<?php echo $_SESSION['idEmpresaUsuarioSysGts'] ?>, "Empresa", "ListaEmpresa");
xajax_cargaLstModulo();
xajax_cargarBancoCliente("tdBancoCliente","selBancoCliente");
xajax_cargarBancoCompania(4);
xajax_opcionBonoSuplidor();

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

</script>