<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_nota_cargo_por_pagar_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_nota_cargo_por_pagar_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Pago de Notas de Débito</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCaja.css" />
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
		
	<script>
	//jQuery.noConflict();
	jQuery(function($){
		//$("#txtNumeroCuenta").mask("9999-9999-99-9999999999",{placeholder:" "});
		//$("#txtNroCuentaDeposito").mask("9999-9999-99-9999999999",{placeholder:" "});
	});
	
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblDeposito').style.display = 'none';
		byId('tblLista').style.display = 'none';
		
		if (verTabla == "tblDeposito") {
			if (valor == "Deposito") {
				if (validarCampo('txtTotalFactura','t','monto') == true
				&& validarCampo('txtMontoPago','t','monto') == true
				&& validarCampo('txtFechaDeposito','t','fecha') == true
				&& validarCampo('selBancoCompania','t','lista') == true
				&& validarCampo('selNumeroCuenta','t','') == true
				&& validarCampo('txtNumeroDctoPago','t','') == true) {
					document.forms['frmDeposito'].reset();
					
					byId('tblDeposito').style.display = '';
					byId('tblLista').style.display = 'none';
					
					xajax_formDeposito(xajax.getFormValues('frmDeposito'));
					
					tituloDiv1 = 'Detalle Deposito';
				} else {
					validarCampo('txtTotalFactura','t','monto') == true
					validarCampo('txtMontoPago','t','monto');
					validarCampo('txtFechaDeposito','t','fecha');
					validarCampo('selBancoCompania','t','lista');
					validarCampo('selNumeroCuenta','t','');
					validarCampo('txtNumeroDctoPago','t','');
					
					alert("Los campos señalados en rojo son requeridos");
					return false;
				}
			}
		} else if (verTabla == "tblLista") {
			document.forms['frmBuscarAnticipoNotaCreditoChequeTransferencia'].reset();
			
			byId('trBuscarAnticipoNotaCreditoChequeTransferencia').style.display = '';
			
			byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').className = 'inputHabilitado';
		
			xajax_buscarAnticipoNotaCreditoChequeTransferencia(xajax.getFormValues('frmBuscarAnticipoNotaCreditoChequeTransferencia'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmListaPagos'));
			
			tituloDiv1 = 'Listado';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').focus();
			byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
		
		if (verTabla == "tblAnticipoNotaCreditoChequeTransferencia") {
			byId('txtMontoDocumento').className = 'inputHabilitado';
			
			xajax_cargarSaldoDocumento(valor, valor2, xajax.getFormValues('frmListaPagos'));
			
			tituloDiv2 = '';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblAnticipoNotaCreditoChequeTransferencia") {
			byId('txtMontoDocumento').focus();
			byId('txtMontoDocumento').select();
		}
	}
	
	function asignarTipoPago(idFormaPago) {
		byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = '';
		byId('txtFechaDeposito').value = '';
		byId('txtNumeroCuenta').value = '';
		byId('txtNumeroDctoPago').value = '';
		byId('txtMontoPago').value = '';
		
		byId('trTipoTarjeta').style.display = 'none';
		byId('trPorcentajeRetencion').style.display = 'none';
		byId('trPorcentajeComision').style.display = 'none';
		
		byId('trBancoFechaDeposito').style.display = 'none';
		byId('tdselBancoCliente').style.display = 'none';
		byId('txtFechaDeposito').style.display = 'none';
		
		byId('trBancoCompania').style.display = 'none';
		byId('tdselBancoCompania').style.display = 'none';
		
		byId('trNumeroCuenta').style.display = 'none';
		byId('txtNumeroCuenta').style.display = 'none';
		byId('divselNumeroCuenta').style.display = 'none';
		
		byId('trNumeroDocumento').style.display = 'none';
		byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
		byId('btnAgregarDetDeposito').style.display = 'none';
		
		xajax_cargaLstBancoCliente("selBancoCliente");
		
		switch(idFormaPago) {
			case '1' : // EFECTIVO
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '2' : // CHEQUE
				byId('tdNumeroDocumento').innerHTML = 'Nro. Cheque:';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputInicial';
				byId('txtNumeroDctoPago').readOnly = true;
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
				
				byId('btnGuardarDetallePago').style.display = 'none';
				break;
			case '3' : // DEPOSITO
				byId('tdEtiquetaBancoFechaDeposito').innerHTML = 'Fecha Deposito:';
				byId('tdNumeroDocumento').innerHTML = 'Nro. Planilla:';
				byId('trBancoFechaDeposito').style.display = '';
				byId('txtFechaDeposito').style.display = '';
				
				byId('trBancoCompania').style.display = '';
				byId('tdselBancoCompania').style.display = '';
				
				byId('trNumeroCuenta').style.display = '';
				byId('divselNumeroCuenta').style.display = '';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				byId('btnAgregarDetDeposito').style.display = '';
				
				byId('txtFechaDeposito').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				xajax_cargaLstBancoCompania(3);
				
				byId('btnGuardarDetallePago').style.display = 'none';
				break;
			case '4' : // TRANSFERENCIA
				byId('tdNumeroDocumento').innerHTML = 'Nro. Transferencia:';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputInicial';
				byId('txtNumeroDctoPago').readOnly = true;
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
				
				byId('btnGuardarDetallePago').style.display = 'none';
				break;
			case '5' : // TARJETA CREDITO
				byId('trTipoTarjeta').style.display = '';
				byId('trPorcentajeRetencion').style.display = '';
				byId('trPorcentajeComision').style.display = '';
				
				byId('tdEtiquetaBancoFechaDeposito').innerHTML = 'Banco Cliente:';
				byId('tdNumeroDocumento').innerHTML = 'Nro. Recibo:';
				byId('trBancoFechaDeposito').style.display = '';
				byId('tdselBancoCliente').style.display = '';
				
				byId('trBancoCompania').style.display = '';
				byId('tdselBancoCompania').style.display = '';
				
				byId('trNumeroCuenta').style.display = '';
				byId('divselNumeroCuenta').style.display = '';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				xajax_cargaLstBancoCompania(5);
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '6' : // TARJETA DEBITO
				byId('trPorcentajeComision').style.display = '';
				
				byId('tdEtiquetaBancoFechaDeposito').innerHTML = 'Banco Cliente:';
				byId('tdNumeroDocumento').innerHTML = 'Nro. Recibo:';
				byId('trBancoFechaDeposito').style.display = '';
				byId('tdselBancoCliente').style.display = '';
				
				byId('trBancoCompania').style.display = '';
				byId('tdselBancoCompania').style.display = '';
				
				byId('trNumeroCuenta').style.display = '';
				byId('divselNumeroCuenta').style.display = '';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				xajax_cargaLstBancoCompania(6);
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '7' : // ANTICIPO
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputInicial';
				byId('txtNumeroDctoPago').readOnly = true;
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
				
				byId('btnGuardarDetallePago').style.display = 'none';
				break;
			case '8' : // NOTA CREDITO
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputInicial';
				byId('txtNumeroDctoPago').readOnly = true;
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
				
				byId('btnGuardarDetallePago').style.display = 'none';
				break;
			case '9' : // RETENCION
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '10' :  // RETENCION ISLR
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
		}
	}
	
	function asignarTipoPagoDetalleDeposito(idFormaPago) {
		byId('txtNroCuentaDeposito').value = '';
		byId('txtNroChequeDeposito').value = '';
		byId('txtMontoDeposito').value = '';
		
		switch(idFormaPago) {
			case '1' : // EFECTIVO
				byId('trBancoCliente').style.display = 'none';
				byId('trNroCuenta').style.display = 'none';
				byId('trNroCheque').style.display = 'none';
				byId('trMonto').style.display = '';
				break;
			case '2' : // CHEQUE
				byId('trBancoCliente').style.display = '';
				byId('trNroCuenta').style.display = '';
				byId('trNroCheque').style.display = '';
				byId('trMonto').style.display = '';
				break;
		}
	}
	
	function calcularPorcentajeTarjetaCredito() {
		if (byId('selTipoPago').value == 5) {
			byId('montoTotalRetencion').value = formatoRafk(parseNumRafk(byId('txtMontoPago').value) * parseNumRafk(byId('porcentajeRetencion').value) / 100,2);
			byId('montoTotalComision').value = formatoRafk(parseNumRafk(byId('txtMontoPago').value) * parseNumRafk(byId('porcentajeComision').value) / 100,2);
		} else if (byId('selTipoPago').value) {
			byId('montoTotalComision').value = formatoRafk(parseNumRafk(byId('txtMontoPago').value) * parseNumRafk(byId('porcentajeComision').value) / 100,2);
		}
	}
	
	function confirmarEliminarPago(pos) {
		if (confirm("Desea eliminar el pago?"))
			xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),pos);
	}
	
	function confirmarEliminarPagoDetalleDeposito(pos) {
		if (confirm("Desea eliminar el detalle del deposito?"))
			xajax_eliminarPagoDetalleDeposito(xajax.getFormValues('frmDeposito'),pos);
	}
	
	function validarFrmAnticipoNotaCreditoChequeTransferencia() {
		var saldo = parseNumRafk(byId('txtSaldoDocumento').value);
		var monto = parseNumRafk(byId('txtMontoDocumento').value);
		var montoFaltaPorPagar = parseNumRafk(byId('txtMontoPorPagar').value);
		
		if (parseFloat(saldo) < parseFloat(monto)) {
			alert("El monto a pagar no puede ser mayor que el saldo del documento");
		} else {
			if (parseFloat(montoFaltaPorPagar) >= parseFloat(monto)) {
				if (confirm("Desea cargar el pago?")) {
					byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = byId('hddIdDocumento').value;
					byId('txtNumeroDctoPago').value = byId('txtNroDocumento').value;
					byId('txtMontoPago').value = byId('txtMontoDocumento').value;
					
					error = false;
					if (!(validarCampo('txtTotalFactura','t','monto') == true
					&& validarCampo('txtMontoPago','t','monto') == true
					&& validarCampo('txtNumeroDctoPago','t','') == true
					&& validarCampo('txtMontoDocumento','t','monto') == true)) {
						validarCampo('txtTotalFactura','t','monto');
						validarCampo('txtMontoPago','t','monto');
						validarCampo('txtNumeroDctoPago','t','');
						validarCampo('txtMontoDocumento','t','monto');
						
						error = true;
					}
					
					if (error == true) {
						alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
						return false;
					} else {
						xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
					}
				}
			} else {
				alert("El monto a pagar no puede ser mayor que el saldo de la Nota de Débito");
			}
		}
	}
	
	function validarFrmDcto() {
		error = false;
		if (!(validarCampo('txtTotalOrden','t','monto') == true
		&& validarCampo('txtMontoPorPagar','t','numPositivo') == true)){
			validarCampo('txtTotalOrden','t','monto');
			validarCampo('txtMontoPorPagar','t','numPositivo');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarDcto(xajax.getFormValues('frmDcto'),xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListaPagos'));
		}		
	}
	
	function validarFrmDeposito() {
		if (byId('txtSaldoDepositoBancario').value == 0) {
			xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
		} else {
			alert("El saldo del detalle del deposito debe ser 0 (cero)");
		}
	}
	
	function validarFrmDetallePago() {
		error = false;
		if (byId('selTipoPago').value == 1) { // EFECTIVO
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 2) { // CHEQUES
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('txtNumeroCuenta','t','') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('txtNumeroCuenta','t','');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 4) { // TRANSFERENCIA
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 5) { // TARJETA DE CREDITO
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('tarjeta','t','lista') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true
			&& validarCampo('porcentajeRetencion','t','numPositivo') == true
			&& validarCampo('montoTotalRetencion','t','numPositivo') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('tarjeta','t','lista');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('txtNumeroDctoPago','t','');
				validarCampo('porcentajeRetencion','t','numPositivo');
				validarCampo('montoTotalRetencion','t','numPositivo');
				validarCampo('porcentajeComision','t','numPositivo');
				validarCampo('montoTotalComision','t','numPositivo');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 6) { // TARJETA DE DEBITO
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('txtNumeroDctoPago','t','');
				validarCampo('porcentajeComision','t','numPositivo');
				validarCampo('montoTotalComision','t','numPositivo');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 9) { // RETENCION
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 10) { // RETENCION ISLR
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else {
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('selTipoPago','t','lista') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('selTipoPago','t','lista');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			}
		}
	}
	
	function validarFrmDetalleDeposito() {
		error = false;
		if (byId('lstTipoPago').value == 1) { // EFECTIVO
			if (!(validarCampo('txtMontoDeposito','t','monto') == true)) {
				validarCampo('txtMontoDeposito','t','monto');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPagoDeposito(xajax.getFormValues('frmDeposito'));
			}
		} else if (byId('lstTipoPago').value == 2) { // CHEQUES
			if (!(validarCampo('txtMontoDeposito','t','monto') == true
			&& validarCampo('lstBancoDeposito','t','lista') == true
			&& validarCampo('txtNroCuentaDeposito','t','') == true
			&& validarCampo('txtNroChequeDeposito','t','') == true)) {
				validarCampo('txtMontoDeposito','t','monto');
				validarCampo('lstBancoDeposito','t','lista');
				validarCampo('txtNroCuentaDeposito','t','');
				validarCampo('txtNroChequeDeposito','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPagoDeposito(xajax.getFormValues('frmDeposito'));
			}
		} else {
			if (!(validarCampo('lstTipoPago','t','lista') == true)) {
				validarCampo('lstTipoPago','t','lista');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			}
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cj.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCaja">Pago de Nota de Débito</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmDcto" name="frmDcto" style="margin:0">
            	<table align="right" border="0" width="100%">
                <tr>
                	<td colspan="2">
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="88%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
						</tr>
                        </table>
                    </td>
				</tr>
				<tr>
                    <td valign="top" width="70%">
                    <fieldset><legend class="legend">Cliente</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td></td>
                                    <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                </tr>
                                <tr align="center">
                                    <td id="tdMsjCliente" colspan="3"></td>
                                </tr>
                                </table>
                                <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto"/>
                            </td>
                            <td align="right" class="tituloCampo"><?php echo $spanClienteCxC; ?>:</td>
                            <td><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                            <td colspan="3" rowspan="2"><textarea id="txtDireccionCliente" name="txtDireccionCliente" cols="55" readonly="readonly" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo"><?php echo $spanNIT; ?>:</td>
                            <td><input type="text" id="txtNITCliente" name="txtNITCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Días Crédito:</td>
                            <td>
                                <table border="0" cellspacing="0" width="100%">
                                <tr>
                                    <td width="40%">Días:</td>
                                    <td width="60%"><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                </tr><tr>
                                </tr><tr>
                                    <td>Disponible:</td>
                                    <td><input type="text" id="txtCreditoCliente" name="txtCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                </tr><tr>
                                </tr></table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="16%">Teléfono:</td>
                            <td width="15%"><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo" width="16%">Otro Teléfono:</td>
                            <td width="15%"><input type="text" id="txtOtroTelefonoCliente" name="txtOtroTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                            <td width="16%"></td>
                            <td width="22%"></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top" width="30%">
                    <fieldset><legend class="legend">Nota de Débito</legend>
                        <input type="hidden" id="hddIdFactura" name="hddIdFactura"/>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Nro. Nota de Débito:</td>
                            <td width="60%"><input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" size="20" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Control:</td>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtNumeroControlFactura" name="txtNumeroControlFactura" size="16" readonly="readonly" style="color:#F00; font-weight:bold; text-align:center;"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: 00-000000"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fecha Nota de Débito:</td>
                            <td><input type="text" id="txtFecha" name="txtFecha" size="10" style="text-align:center" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fecha Vencimiento:</td>
                            <td><input type="text" id="txtFechaVencimiento" name="txtFechaVencimiento" size="10" style="text-align:center" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Módulo:</td>
                            <td id="tdlstModulo"></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total:</td>
                            <td><input type="text" id="txtTotalFactura" name="txtTotalFactura" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal3">
                            <td class="tituloCampo">Saldo:</td>
                            <td><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		<tr>
			<td width="100%">
            <fieldset><legend class="legend">Forma de Pago</legend>
            <form id="frmDetallePago" name="frmDetallePago" style="margin:0">
				<table border="0" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="12%">Forma de Pago:</td>
                    <td id="tdselTipoPago" width="26%"></td>
                    <td rowspan="7" valign="top" width="62%">
                    	<table width="100%">
                        <tr>
                        	<td width="20%"></td>
                            <td width="16%"></td>
                        	<td width="20%"></td>
                            <td width="44%"></td>
                        </tr>
                        <tr id="trTipoTarjeta" style="display:none;">
                            <td align="right" class="tituloCampo" scope="row">Tipo Tarjeta:</td>
                            <td id="tdtarjeta" colspan="4" scope="row">
                                <select id="tarjeta" name="tarjeta" style="width:200px">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr id="trPorcentajeRetencion" style="display:none;">
                            <td align="right" class="tituloCampo">Porcentaje Retenci&oacute;n:</td>
                            <td scope="row">
                                <input type="text" id="porcentajeRetencion" name="porcentajeRetencion" readonly="readonly" size="10" style="text-align:right; background-color:#EEEEEE;"/>
                            </td>
                            <td align="right" class="tituloCampo" scope="row">Monto:</td>
                            <td scope="row">
                                <input type="text" id="montoTotalRetencion" name="montoTotalRetencion" readonly="readonly" size="19" style="text-align:right; background-color:#EEEEEE;"/>
                            </td>
                        </tr>
                        <tr id="trPorcentajeComision" style="display:none;">
                            <td align="right" class="tituloCampo">Porcentaje Comisi&oacute;n:</td>
                            <td scope="row">
                                <input type="text" id="porcentajeComision" name="porcentajeComision" readonly="readonly" size="10" style="text-align:right; background-color:#EEEEEE;" value="0.00"/>
                            </td>
                            <td align="right" class="tituloCampo" scope="row">Monto:</td>
                            <td scope="row">
                                <input type="text" id="montoTotalComision" name="montoTotalComision" readonly="readonly" size="19" style="text-align:right; background-color:#EEEEEE;" value="0.00"/>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr id="trBancoFechaDeposito" align="left">
                    <td id="tdEtiquetaBancoFechaDeposito" align="right" class="tituloCampo">Banco Cliente:</td>
                    <td scope="row">
                    	<div id="tdselBancoCliente">
                            <select id="selBancoCliente" name="selBancoCliente" style="width:200px">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </div>
                        <input type="text" id="txtFechaDeposito" name="txtFechaDeposito" autocomplete="off" size="10" style="text-align:center"/>
                    </td>
                </tr>
                <tr id="trBancoCompania" align="left">
                    <td align="right" class="tituloCampo">Banco Compa&ntilde;ia:</td>
                    <td id="tdselBancoCompania">
                        <select id="selBancoCompania" name="selBancoCompania" style="width:200px">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr id="trNumeroCuenta" align="left">
                    <td align="right" class="tituloCampo">Nro. de Cuenta:</td>
                    <td>
                        <div id="divselNumeroCuenta" style="display:none">
                            <select id="selNumeroCuenta" name="selNumeroCuenta" style="width:200px">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </div>
			<input type="text" id="txtNumeroCuenta" name="txtNumeroCuenta" size="30"/>
                    </td>
                </tr>
                <tr id="trNumeroDocumento" align="left">
                    <td id="tdNumeroDocumento" align="right" class="tituloCampo">Nro.:</td>
                    <td>
                    	<table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="txtNumeroDctoPago" name="txtNumeroDctoPago"/></td>
                        	<td>
                            <a class="modalImg" id="btnAgregarDetAnticipoNotaCreditoChequeTransferencia" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar Documento</td></tr></table></button>
                            </a>
                            <a class="modalImg" id="btnAgregarDetDeposito" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblDeposito', 'Deposito');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/money_add.png"/></td><td>&nbsp;</td><td>Agregar Detalle Deposito</td></tr></table></button>
                            </a>
                            </td>
                        </tr>
                        </table>
                        <input type="hidden" id="hddIdAnticipoNotaCreditoChequeTransferencia" name="hddIdAnticipoNotaCreditoChequeTransferencia"/>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Monto:</td>
                    <td>
                    	<table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="txtMontoPago" name="txtMontoPago" class="inputHabilitado" onblur="setFormatoRafk(this,2); calcularPorcentajeTarjetaCredito();" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                            <td><button type="button" id="btnGuardarDetallePago" name="btnGuardarDetallePago" onclick="validarFrmDetallePago();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Pago</td></tr></table></button></td>
                        </tr>
                        </table>
                    </td>
                </tr>
				</table>
                <input type="hidden" id="hddObjDetallePago" name="hddObjDetallePago"/>
                <input type="hidden" id="hddObjDetalleDeposito" name="hddObjDetalleDeposito"/>
                <input type="hidden" id="hddObjDetalleDepositoFormaPago" name="hddObjDetalleDepositoFormaPago"/>
                <input type="hidden" id="hddObjDetalleDepositoBanco" name="hddObjDetalleDepositoBanco"/>
                <input type="hidden" id="hddObjDetalleDepositoNroCuenta" name="hddObjDetalleDepositoNroCuenta"/>
                <input type="hidden" id="hddObjDetalleDepositoNroCheque" name="hddObjDetalleDepositoNroCheque"/>
                <input type="hidden" id="hddObjDetalleDepositoMonto" name="hddObjDetalleDepositoMonto"/>
            </form>
            
            <form id="frmListaPagos" name="frmListaPagos" style="margin:0">
                <fieldset><legend class="legend">Desglose de Pagos</legend>
                    <table width="100%">
                    <tr align="center" class="tituloColumna">
                    	<td></td>
                        <td><img src="../img/iconos/information.png" title="Mostrar en pagos de la factura"/></td>
                        <td><img src="../img/iconos/information.png" title="En la copia del banco sumar al: &#10;C = Pago de Contado &#10;T = Trade In"/></td>
                        <td width="12%">Forma de Pago</td>
                        <td width="48%">Nro. Tranferencia / Cheque / Anticipo / Nota Crédito</td>
                        <td width="15%">Banco Cliente / Cuenta Cliente</td>
                        <td width="15%">Banco Compañia / Cuenta Compañia</td>
                        <td width="10%">Monto</td>
                        <td></td>
                    </tr>
                    <tr id="trItmPiePago" class="trResaltarTotal">
                    	<td align="right" class="tituloCampo" colspan="7">Total Pagos:</td>
                        <td><input type="text" id="txtMontoPagadoFactura" name="txtMontoPagadoFactura" class="inputSinFondo" readonly="readonly" style="text-align:right" value="0.00"/></td>
                        <td></td>
                    </tr>
                    <tr class="trResaltarTotal3">
                    	<td align="right" class="tituloCampo" colspan="7">Total Faltante:</td>
                        <td><input type="text" id="txtMontoPorPagar" name="txtMontoPorPagar" class="inputSinFondo" readonly="readonly" style="text-align:right;" value="0.00"/></td>
                        <td></td>
                    </tr>
                    </table>
                </fieldset>
            </form>
            </fieldset>
			</td>
		</tr>
		<tr align="right">
			<td colspan="8"><hr>
				<button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();" disabled="disabled"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
				<button type="button" id="btnCancelar" name="btnCancelar" onclick="top.history.back();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
		</tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmDeposito" name="frmDeposito" style="margin:0">
	<table border="0" id="tblDeposito" width="760">
    <tr>
    	<td width="20%"></td>
    	<td width="80%"></td>
    </tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Forma de Pago:</td>
		<td id="tdlstTipoPago">
			<select id="lstTipoPago" name="lstTipoPago" style="width:200px">
				<option value="-1">[ Seleccione ]</option>
			</select>
		</td>
	</tr>
	<tr id="trBancoCliente" align="left" style="display:none">
		<td align="right" class="tituloCampo">Banco:</td>
		<td id="tdlstBancoDeposito">
			<select id="lstBancoDeposito" name="lstBancoDeposito" style="width:200px">
				<option value="-1">[ Seleccione ]</option>
			</select>
		</td>
	</tr>
	<tr id="trNroCuenta" align="left" style="display:none">
		<td align="right" class="tituloCampo">Nro. Cuenta:</td>
		<td><input type="text" name="txtNroCuentaDeposito" id="txtNroCuentaDeposito" size="30"/></td>
	</tr>
	<tr id="trNroCheque" align="left" style="display:none">
		<td align="right" class="tituloCampo">Nro. Cheque:</td>
		<td><input type="text" name="txtNroChequeDeposito" id="txtNroChequeDeposito"/></td>
	</tr>
	<tr id="trMonto" align="left" style="display:none">
		<td align="right" class="tituloCampo">Monto:</td>
		<td>
        	<table border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" name="txtMontoDeposito" id="txtMontoDeposito" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                <td><button type="button" id="btnGuardarDetalleDeposito" name="btnGuardarDetalleDeposito" onclick="validarFrmDetalleDeposito();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Pago</td></tr></table></button></td>
            </tr>
            </table>
        </td>
	</tr>
	<tr>
		<td colspan="2">
			<table border="0" width="100%">
			<tr class="tituloColumna" align="center">
            	<td></td>
				<td width="20%">Forma de Pago</td>
				<td width="20%">Banco</td>
				<td width="20%">Nro. Cuenta</td>
				<td width="20%">Nro. Cheque</td>
				<td width="20%">Monto</td>
				<td>&nbsp;</td>
			</tr>
			<tr id="trItmPieDeposito" class="trResaltarTotal">
                <td align="right" class="tituloCampo" colspan="5">Total Pagos:</td>
                <td><input type="text" id="txtTotalDeposito" name="txtTotalDeposito" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                <td></td>
            </tr>
            <tr class="trResaltarTotal3">
                <td align="right" class="tituloCampo" colspan="5">Total Faltante:</td>
                <td><input type="text" id="txtSaldoDepositoBancario" name="txtSaldoDepositoBancario" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                <td></td>
            </tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
        	<input type="hidden" id="hddObjDetallePagoDeposito" name="hddObjDetallePagoDeposito"/>
			<button type="button" id="btnGuardarDeposito" name="btnGuardarDeposito" onclick="validarFrmDeposito();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
            <button type="button" id="btnCancelarDeposito" name="btnCancelarDeposito" class="close"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
</form>
	
	<table border="0" id="tblLista" width="960">
	<tr id="trBuscarAnticipoNotaCreditoChequeTransferencia">
		<td>
        <form id="frmBuscarAnticipoNotaCreditoChequeTransferencia" name="frmBuscarAnticipoNotaCreditoChequeTransferencia" onsubmit="return false;" style="margin:0">
			<table align="right">
			<tr>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioAnticipoNotaCreditoChequeTransferencia" name="txtCriterioAnticipoNotaCreditoChequeTransferencia" onkeyup="byId('btnBuscarAnticipoNotaCreditoChequeTransferencia').click();"/></td>
				<td>
					<button type="submit" id="btnBuscarAnticipoNotaCreditoChequeTransferencia" name="btnBuscarAnticipoNotaCreditoChequeTransferencia" onclick="xajax_buscarAnticipoNotaCreditoChequeTransferencia(xajax.getFormValues('frmBuscarAnticipoNotaCreditoChequeTransferencia'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmListaPagos'));">Buscar</button>
					<button type="button" onclick="document.forms['frmBuscarAnticipoNotaCreditoChequeTransferencia'].reset(); byId('btnBuscarAnticipoNotaCreditoChequeTransferencia').click();">Limpiar</button>
				</td>
			</tr>
			</table>
        </form>
		</td>
	</tr>
    <tr>
    	<td>
        <form id="frmLista" name="frmLista" onsubmit="return false;" style="margin:0">
            <table width="100%">
            <tr>
                <td><div id="divLista" style="width:100%;"></div></td>
            </tr>
            <tr>
                <td align="right"><hr>
                    <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
	</table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmAnticipoNotaCreditoChequeTransferencia" name="frmAnticipoNotaCreditoChequeTransferencia" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblAnticipoNotaCreditoChequeTransferencia" width="360">
	<tr align="left">
		<td align="right" class="tituloCampo" width="40%">Nro. Documento:</td>
		<td width="60%"><input type="text" id="txtNroDocumento" name="txtNroDocumento" readonly="readonly" size="20" style="text-align:center"/></td>
	</tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Saldo:</td>
		<td><input type="text" id="txtSaldoDocumento" name="txtSaldoDocumento" readonly="readonly" style="text-align:right"/></td>
	</tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Monto a Cobrar:</td>
		<td><input type="text" id="txtMontoDocumento" name="txtMontoDocumento" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
	</tr>
	<tr align="left">
		<td align="right" colspan="2"><hr>
            <input type="hidden" id="hddIdDocumento" name="hddIdDocumento"/>
			<button type="submit" id="btnAceptarAnticipoNotaCreditoChequeTransferencia" name="btnAceptarAnticipoNotaCreditoChequeTransferencia" onclick="validarFrmAnticipoNotaCreditoChequeTransferencia();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button>
			<button type="button" id="btnCancelarAnticipoNotaCreditoChequeTransferencia" name="btnCancelarAnticipoNotaCreditoChequeTransferencia" class="close"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
</form>
</div>

<script>
window.onload = function(){
	jQuery(function($){
		$("#txtFechaDeposito").maskInput("99-99-9999",{placeholder:" "});
		
		//$("#txtNumeroCuenta").maskInput("9999-9999-99-9999999999",{placeholder:" "});
		//$("#txtNroCuentaDeposito").maskInput("9999-9999-99-9999999999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDeposito",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"vino"
	});
};

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

asignarTipoPago('-1');
xajax_cargaLstBancoCompania();
<?php if (isset($_GET['id'])) { ?>
	xajax_cargarDcto('<?php echo $_GET['id']; ?>');
<?php } ?>

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>