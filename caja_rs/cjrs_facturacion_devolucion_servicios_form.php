<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_factura_venta_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_cjrs_facturacion_devolucion_servicios_form.php");
require("../controladores/ac_pg_calcular_comision_servicio.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Pago y Facturación de Servicios</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
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
				alert("El monto a pagar no puede ser mayor que el saldo de la Factura");
			}
		}
	}
	
	function validarDevolucion(){	
		if (validarCampo('lstClaveMovimiento','t','lista') == true 
		&& validarCampo('txtMotivoRetrabajo','t','') == true)
		{
			if(confirm("Desea generar la Nota Credito?"))
				xajax_devolverFacturaVenta(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			else
				return false;
		}else{
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtMotivoRetrabajo','t','');
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}	
	}
	
	function validarFrmDcto() {
		if (validarCampo('txtIdPresupuesto','t','') == true 
		&& validarCampo('txtNroControl','t','') == true 
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('txtTotalOrden','t','monto') == true
		&& validarCampo('txtMontoPorPagar','t','numPositivo') == true){
			if(byId('hddItemsNoAprobados').value == 1){
				alert("La orden tiene items no aprobados");
				return false;
			}
				
			if(confirm("¿Desea Generar la Factura?")){
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_guardarFactura(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListaPagos'));
			} else {
				return false;
			}
		}else{
			validarCampo('txtIdPresupuesto','t','')
			validarCampo('txtNroControl','t','');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtTotalOrden','t','monto');
			validarCampo('txtMontoPorPagar','t','numPositivo');
			
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
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
        
        <style type="text/css">
            .noRomper{ white-space: nowrap; }    
			.table-scroll{
				overflow:auto;
				max-height:250px;
			}
        </style>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table border="0" width="100%">
        <tr>
            <td class="tituloPaginaCajaRS" id="tituloPaginaCajaRS"></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
            <form id="frmDcto" name="frmDcto" style="margin:0">
            <input type="hidden" name="hddTipoOrdenAnt" id="hddTipoOrdenAnt" value="0"/>
            <table border="0" width="100%">
				<tr>
					<td colspan="2">
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="60%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo" width="12%">Fecha:</td>
                            <td width="18%"><input type="text" id="txtFechaPresupuesto" name="txtFechaPresupuesto" readonly="readonly" style="text-align:center" size="10"/></td>
						</tr>
                        </table>
                    </td>
				</tr>
                <tr align="left">
					<td valign="top" width="70%">
						<fieldset><legend class="legend">Cliente</legend>
							<table border="0" width="100%">
							<tr>
								<td align="right" class="tituloCampo" width="120">Nro. Vale:</td>
								<td>
									<table width="25%" border="0" cellpadding="0" cellspacing="0">
										<tr>
                                            <td width="21%">
                                                <label>
                                                <input name="numeracionRecepcionMostrar" type="text" id="numeracionRecepcionMostrar" size="8" readonly="readonly"/>
                                                <input name="txtIdValeRecepcion" type="hidden" id="txtIdValeRecepcion" size="8" readonly="readonly"/>
                                                </label>
                                            </td>
											<td class="noprint"></td>
										</tr>
									</table>
								</td>
								<td class="tituloCampo" align="right" width="120">Fecha Vale:</td>
								<td><input name="txtFechaRecepcion" type="text" id="txtFechaRecepcion" size="18" readonly="readonly"/></td>
							</tr>
							<tr>
								<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Cliente:</td>
								<td>
									<table cellpadding="0" cellspacing="0">
									<tr>
										<td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="8"/></td>
										<td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="47"/></td>
									</tr>
									</table>
								</td>
								<td align="right" class="tituloCampo" width="120"><?php echo $spanClienteCxC; ?>:</td>
								<td width="15%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="18"/></td>
							</tr>
							<tr>
								<td align="right" class="tituloCampo" width="120">Dirección:</td>
                            	<td rowspan="3">
                                	<textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly"></textarea>
									<input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/>
									<input type="hidden" id="hddAgregarOrdenFacturada" name="hddAgregarOrdenFacturada" readonly="readonly"/>
								</td>
                            	<td align="right" class="tituloCampo" width="120">Teléfono:</td>
								<td><input type="text" id="txtTelefonosCliente" name="txtTelefonosCliente" readonly="readonly" size="25"/></td>
                            </tr>
                            </table>
						</fieldset>
                        
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" id="tdTipoMov" class="tituloCampo" style="display:none" width="120"><span class="textoRojoNegrita">*</span>Tipo:</td>
                            <td style="display:none" id="tdLstTipoClave">
                                <?php 
                                    if(isset($_GET['dev'])) {
                                        if($_GET['dev'] == 1) {
                                            $valorSelectEntrada = "selected='selected'";
                                            $valorSelectSalida = "";
                                        } else {
                                            $valorSelectEntrada = "";
                                            $valorSelectSalida = "selected='selected'";
                                        }
                                    } else {
                                        $valorSelectEntrada = "";
                                        $valorSelectSalida = "selected='selected'";
                                    }
                                ?>
                                <select id="lstTipoClave" name="lstTipoClave" onchange="xajax_cargaLstClaveMovimiento(this.value)">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="2"<?php echo $valorSelectEntrada;?>>Entrada</option>
                                    <option value="3"<?php echo $valorSelectSalida;?>>Venta</option>
                                    <option value="4">Salida</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" style="display:none" id="tdClave" width="120"><span class="textoRojoNegrita">*</span>Clave:</td>
                            <td colspan="3" id="tdlstClaveMovimiento" style="display:none">
                                <select id="lstClaveMovimiento" name="lstClaveMovimiento">
                                </select>
                            </td>
                            <td class="tituloCampo" align="right" width="120">Tipo de Orden:</td>
                            <td id="tdlstTipoOrden"></td>
                        </tr>
                        </table>
                    </td>
                    <td valign="top">
                        <fieldset>
                            <legend id="lydTipoDocumento" class="legend"></legend>
                            <table border="0" width="100%">
                            <tr>
                                <td>
                                    <table border="0" id="fldPresupuesto">
                                    <tr>
                                        <td align="right" class="tituloCampo" id="tdNroFacturaVenta" style="display:none" width="120">Nro. Factura</td>
                                        <td id="tdTxtNroFacturaVenta" style="display:none">
                                            <label><input name="txtNroFacturaVentaServ" type="text" id="txtNroFacturaVentaServ" size="25" readonly="readonly"/></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo" id="tdIdDocumento" width="120"></td>
                                        <td width="67%">
                                            <input type="text" id="numeroOrdenMostrar" name="numeroOrdenMostrar" readonly="readonly" size="25" value="0"/>
                                            <input type="hidden" id="txtIdPresupuesto" name="txtIdPresupuesto" readonly="readonly" size="25" value="0"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="tituloCampo" align="right" width="120">Estado:</td>
                                        <td><input name="txtEstadoOrden" id="txtEstadoOrden" type="text" readonly="readonly"/></td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo" id="tdNroControl" style="display:none"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
                                        <td colspan="3" id="tdTxtNroControl" style="display:none">
                                            <div style="float:left"><input type="text" id="txtNroControl" name="txtNroControl" size="16" style="color:#F00; font-weight:bold; text-align:center;"/>
                                            </div>
                                            <div style="float:left">
                                                <img src="../img/iconos/information.png" title="Formato Ej.: 00-000000"/>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr align="right" class="trResaltarTotal">
                                        <td align="right" class="tituloCampo" width="120">Total Factura:</td>
                                        <td align="left"><input type="text" id="txtTotalFactura" name="txtTotalFactura"  class="inputSinFondo" size="20" style="text-align:right" readonly="readonly"/></td>
                                    </tr>
                                    </table>
                                    <table border="0" style="display:none">
                                    <tr id="tdFechaVecDoc" style="display:none">
                                        <td align="right" class="tituloCampo">Fecha Vencimiento:</td>
                                        <td><input type="text" id="txtFechaVencimiento" name="txtFechaVencimiento" size="10" style="text-align:center" readonly="readonly"/></td>
                                    </tr>
                                    <tr style="display:none">
                                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                                        <td id="tdlstMoneda">
                                            <select id="lstMoneda" name="lstMoneda">
                                                <option value="-1">Seleccione...</option>
                                            </select>
                                        </td>
                                        <td id="tdlstMoneda">&nbsp;</td>
                                    </tr>
                                    </table>
                                </td>
                            </tr>
                            </table>
                        </fieldset>
                    </td>
                </tr>
                <tr align="left">
                    <td valign="top">
                    <fieldset>
                        <legend class="legend">Datos del vehiculo</legend>
                        <table width="100%" border="0">
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Placa:</td>
                            <td><input type="text" id="txtPlacaVehiculo" name="txtPlacaVehiculo" readonly="readonly"/></td>
                            <td>&nbsp;</td>
                            <td align="right" class="tituloCampo" width="120">A&ntilde;o:
                                <input type="hidden" name="hdd_id_modelo" id="hdd_id_modelo"/>
                                <input type="hidden" name="hddIdUnidadBasica" id="hddIdUnidadBasica"/>
                            </td>
                            <td><input type="text" id="txtAnoVehiculo" name="txtAnoVehiculo" readonly="readonly"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Chasis:</td>
                            <td><input type="text" id="txtChasisVehiculo" name="txtChasisVehiculo" readonly="readonly"/></td>
                            <td>&nbsp;</td>
                            <td align="right" class="tituloCampo" width="120">Color:</td>
                            <td><input type="text" id="txtColorVehiculo" name="txtColorVehiculo" readonly="readonly"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Marca:</td>
                            <td><input type="text" id="txtMarcaVehiculo" name="txtMarcaVehiculo" readonly="readonly"/></td>
                            <td>&nbsp;</td>
                            <td align="right" class="tituloCampo" width="120">F. venta:</td>
                            <td><label><input type="text" name="txtFechaVentaVehiculo" id="txtFechaVentaVehiculo" readonly="readonly"/></label>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Modelo:</td>
                            <td><input type="text" id="txtModeloVehiculo" name="txtModeloVehiculo" readonly="readonly"/></td>
                            <td>&nbsp;</td>
                            <td align="right" class="tituloCampo" width="120">Kilometraje:</td>
                            <td><label><input type="text" name="txtKilometrajeVehiculo" id="txtKilometrajeVehiculo" readonly="readonly"/></label>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Unidad Basica:</td>
                            <td><label><input type="text" name="txtUnidadBasica" id="txtUnidadBasica" readonly="readonly"/></label>
                            </td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" valign="top"></td>
                </tr>
            	</table>
            	</form>
        	</td>
        </tr>
        <tr>
			<td align="left">
				<form name="frm_agregar_paq" id="frm_agregar_paq" style="margin:0">
				<table width="100%" border="0" cellpadding="0">				
				<tr>
					<td colspan="9">
						<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea" style="border:0px;">
						<tr>
							<td width="44%" height="22" align="left"></td>
							<td width="56%" align="left">PAQUETES</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="tituloColumna">
					<td width="22" align="center" class="color_column_insertar_eliminar_item" id="tdInsElimPaq" style="width:20px; display:none;"><input type="checkbox" id="cbxItmPaq" onclick="selecAllChecks(this.checked,this.id,2);"/> </td>
					<td width="151" align="center" class="celda_punteada">C&oacute;digo</td>
					<td width="561" align="center" class="celda_punteada">Descripci&oacute;n</td>
					<td width="99" align="center" class="celda_punteada">Total</td>
					<td width="15" align="center" class="celda_punteada"></td>
					<td width="49" align="center" class="color_column_aprobacion_item" id="tdPaqAprob" style="text-align:center; width:20px;"><input type="checkbox" id="cbxItmPaqAprob" onclick="selecAllChecksName(this.checked,'cbxItmPaqAprob[]',2); xajax_calcularTotalDcto();" checked="checked"/>
					</td>
				</tr>
				<tr id="trm_pie_paquete"></tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td align="left">&nbsp;</td>
		</tr>
		<tr>
			<td></td>
		</tr>
		<tr>
			<td>
				<form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
				<table width="100%" border="0" cellpadding="0" cellspacing="0" id="tblListaArticulo">
				<tr>
					<td  colspan="11" class="tituloArea" style="border:0px;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="40%" height="22" align="left">
								
							</td>
							<td width="60%" align="left">REPUESTOS GENERALES</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
				<table width="100%" border="0" cellpadding="0">
				<tr class="tituloColumna">
                	<td style="width:20px; display:none;" id="tdInsElimRep" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,3);"/>
					</td>					
                    <td>Código</td>
                    <td>Descripción</td>
                    <td>Lote</td>
                    <td>Cantidad</td>					
                    <td>PMU Unit.</td>
                    <td>Total PMU</td>
                    <td>Precio Unit.</td>
                    <td>% Impuesto</td>
                    <td title="Total sin Impuestos">Total S/I</td>
                    <td>Total</td>
                    <td>&nbsp;</td>
                    <td style="text-align:center; width:20px; " id="tdRepAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmAprob" onclick="selecAllChecks(this.checked,this.id,3); xajax_calcularTotalDcto();" checked="checked"/></td>
				</tr>
				<tr id="trItmPie"></tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<form id="frmListaManoObra" name="frmListaManoObra" style="margin:0">
				<table border="0" width="100%">
				<tr>
					<td colspan="16" class="tituloArea" style="border:0px;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="40%" height="22" align="left">								
							</td>
							<td width="60%" align="left">MANO DE OBRA GENERAL</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="tituloColumna">
					<td style="width:20px; display:none;" id="tdInsElimManoObra" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItmTemp" onclick="selecAllChecks(this.checked,this.id,4);"/></td>
					<td id="tdNombreMecanico">Nombre Mec&aacute;nico</td>
					<td>Secci&oacute;n</td>
					<td>Subsecci&oacute;n</td>
					<td>Código Tempario</td>
					<td>Descripción</td>
					<td>Origen</td>
					<td>Modo</td>
					<td>Operador</td>
					<td>UT/Precio</td>
					<td>% Impuesto</td>
					<td>Total S/I</td>
					<td>Total</td>
					<td style="width:20px;" id="tdTempAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmTempAprob" onclick="selecAllChecks(this.checked,this.id,4); xajax_calcularTotalDcto();" checked="checked"/></td>
				</tr>
				<tr id="trm_pie_tempario"></tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<form id="frmListaTot" name="frmListaTot" style="margin:0">
				<table border="0" width="100%">
				<tr>
					<td colspan="19" class="tituloArea" style="border:0px;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="36%" height="22" align="left">
                                                            
							</td>
							<td width="100%" align="left">TRABAJOS OTROS TALLERES (T.O.T)</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="tituloColumna">
					<td id="tdInsElimTot" class="color_column_insertar_eliminar_item" style="width:20px; display:none;"><input type="checkbox" id="cbxItmTot" onclick="selecAllChecks(this.checked,this.id,5);"/></td>
					<td>Nro. T.O.T.</td>
					<td>Proveedor</td>
					<td>Tipo Pago</td>
					<td>Monto T.O.T.</td>
					<td>Porcentaje T.O.T.</td>
					<td>% Impuesto</td>
					<td>Total S/I</td>
					<td>Total</td>
					<td style="width:20px;" id="tdTotAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmTotAprob" onclick="selecAllChecks(this.checked,this.id,5); xajax_calcularTotalDcto();" checked="checked"/></td>
				</tr>
				<tr id="trm_pie_tot"></tr>
				</table> 
				</form>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<form id="frmListaNota" name="frmListaNota" style="margin:0">
				<table border="0" width="100%">
				<tr>
					<td colspan="12" class="tituloArea" style="border:0px;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="40%" height="22" align="left">
                                                            
							</td>
							<td width="100%" align="left">NOTAS / CARGO ADICIONAL</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="tituloColumna">
					<td width="38" class="color_column_insertar_eliminar_item" id="tdInsElimNota" style="width:20px">
						<input type="checkbox" id="cbxItmNota" onclick="selecAllChecks(this.checked,this.id,6);"/></td>
					<td width="">Descripción</td>
					<td width="200">% Impuesto</td>
					<td width="120">Total S/I</td>
					<td width="120">Total</td>
					<td width="" class="color_column_aprobacion_item" id="tdNotaAprob" style="width:20px;"><input type="checkbox" id="cbxItmNotaAprob" onclick="selecAllChecks(this.checked,this.id,6); xajax_calcularTotalDcto();" checked="checked"/></td>
				</tr>
				<tr id="trm_pie_nota"></tr>
				</table>
				</form>
			</td>
		<tr>
		<tr>
			<td align="right">
			<form id="frmTotalDcto" name="frmTotalDcto" style="margin:0"><hr>
				<input type="hidden" name="hddDevolucionFactura" id="hddDevolucionFactura" value="<?php echo $_GET['dev'];?>"/>
				<input type="hidden" id="hddObj" name="hddObj"/>
				<input type="hidden" id="hddObjPaquete" name="hddObjPaquete" readonly="readonly"/>
				<input type="hidden" id="hddObjRepuestosPaquete" name="hddObjRepuestosPaquete" readonly="readonly"/>
				<input type="hidden" id="hddObjTempario" name="hddObjTempario" readonly="readonly"/>
				<input type="hidden" id="hddObjTot" name="hddObjTot" readonly="readonly"/>
				<input type="hidden" id="hddObjNota" name="hddObjNota" readonly="readonly"/>
				<input type="hidden" id="hddTipoDocumento" name="hddTipoDocumento" value="<?php echo $_GET['doc_type'];?>"/>
				<input type="hidden" id="hddAccionTipoDocumento" name="hddAccionTipoDocumento" value="<?php echo $_GET['acc'];?>"/>
				<input type="hidden" id="hddMecanicoEnOrden" name="hddMecanicoEnOrden"/>
				<input type="hidden" id="hddItemsCargados" name="hddItemsCargados"/>
				<input type="hidden" id="hddNroItemsPorDcto" name="hddNroItemsPorDcto" value="40"/>
				<input type="hidden" id="hddObjDescuento" name="hddObjDescuento"/>
				<input type="hidden" id="hddObjCons" name="hddObjCons" value="<?php echo $_GET['cons'];?>"/>
				<input type="hidden" id="hddItemsNoAprobados" name="hddItemsNoAprobados"/>
				<input type="hidden" id="hddOrdenEscogida" name="hddOrdenEscogida"/>
				<input type="hidden" id="hddLaOrdenEsRetrabajo" name="hddLaOrdenEsRetrabajo" value="<?php echo $_GET['ret'];?>"/>
				<table border="0" width="100%">
				<tr>
					<td width="40%" colspan="2" align="right" id="tdGastos">
						<table cellpadding="0" cellspacing="0" width="100%" class="divMsjInfo" id="tblLeyendaOrden">
						<tr>
							<td width="25"><img src="../img/iconos/ico_info2.gif" width="25"/></td>
							<td align="center">
								<table>
								<tr>
									<td><img src="../img/iconos/ico_aceptar.gif"/></td>
									<td>Paquete o Repuesto Disponibilidad Suficiente</td>
								</tr>
								<tr>
									<td><img src="../img/iconos/ico_alerta.gif"/></td>
									<td>Paquete o Repuesto Poca Disponibilidad</td>
								</tr>
								<tr>
									<td><img src="../img/iconos/cancel.png"/></td>
									<td>Paquete o Repuesto sin Disponibilidad</td>
								</tr>
								<tr>
									<td class="color_column_insertar_eliminar_item" style="border:1px dotted #999999">&nbsp;</td>
									<td>Eliminar Item</td>
								</tr>
								<tr>
									<td class="color_column_aprobacion_item" style="border:1px dotted #999999">&nbsp;</td>
									<td>Aprobar Item</td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
						<table id="tblMotivoRetrabajo" style="display:none" cellpadding="0" cellspacing="0" align="center">
						<tr>
							<td colspan="2" class="tituloCampo">Motivo:</td>
						</tr>
						<tr>
							<td colspan="2"><textarea name="txtMotivoRetrabajo" id="txtMotivoRetrabajo" cols="45" rows="5"></textarea></td>
						</tr>
						</table>
					</td>
					<td valign="top" width="50%">
						<table border="0" width="100%">
						<tr align="right">
							<td class="tituloCampo" width="36%">Subtotal:</td>
                            <td style="border-top:1px solid;" width="25%"></td>
                            <td style="border-top:1px solid;" width="15%"></td>
							<td style="border-top:1px solid;" width="23%"><input type="text" id="txtSubTotal" name="txtSubTotal" readonly="readonly" size="18" style="text-align:right" class="inputSinFondo"/></td>
						</tr>
						<tr align="right">
                            <td class="tituloCampo">Descuento:</td>
							<td></td>
							<td nowrap="nowrap">
								<input type="hidden" name="hddPuedeAgregarDescuentoAdicional" id="hddPuedeAgregarDescuentoAdicional"/> 
								<input type="text" id="txtDescuento" name="txtDescuento" size="6" style="text-align:right" readonly="readonly" value="0" />%</td>
							<td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" size="18" readonly="readonly" style="text-align:right" class="inputSinFondo"/></td>
						</tr>
						<tr id="trm_pie_dcto"></tr>
  						<tr align="right" style="display:none">
							<td class="tituloCampo">Base Imponible:</td>
                            <td></td>
							<td></td>
							<td align="right"><input type="text" id="txtBaseImponible" name="txtBaseImponible" size="18" readonly="readonly" style="text-align:right" class="inputSinFondo"/></td>
						</tr>
						<tr align="right" style="display:none">
							<td class="tituloCampo">Items Con Impuesto:</td>
                            <td></td>
                            <td></td>
							<td align="right"><input type="text" id="txtGastosConIva" name="txtGastosConIva" readonly="readonly" size="18" style="text-align:right"/></td>
						</tr>
						<!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
						<tr align="right" id="trGastosSinIva">
							<td class="tituloCampo">Exento:</td>
                            <td></td>
							<td></td>
							<td align="right"><input type="text" id="txtMontoExento" name="txtMontoExento" readonly="readonly" size="18" style="text-align:right" class="inputSinFondo"/></td>
						</tr>                                                
                                                
						<?php 
                            $arrayIvas = cargarIvasOrden($_GET["id"]);
                            foreach($arrayIvas as $key => $arrayIva){//funcion en ac_iv_general 
                        ?>
                            <tr align="right">
                                <td class="tituloCampo"><?php echo $arrayIva["observacion"]; ?></td>
                                <td>
                                    <input style="display:none" class="puntero" type="checkbox" name="ivaActivo[]" checked="checked" id="ivaActivo<?php echo $key; ?>"  value="<?php echo $key; ?>" onclick="return false"/>                                    
                                    <input class="inputSinFondo" type="text" id="txtBaseImponibleIva<?php echo $key; ?>" name="txtBaseImponibleIva<?php echo $key; ?>" readonly="readonly" size="18" style="text-align:right"/>                                    
                                </td>
                                <td>                                    
                                    <input type="hidden" id="hddIdIvaVenta<?php echo $key; ?>" name="hddIdIvaVenta<?php echo $key; ?>" value="<?php echo $key ?>"  readonly="readonly"/>
                                    <input type="text" id="txtIvaVenta<?php echo $key; ?>" name="txtIvaVenta<?php echo $key; ?>" value="<?php echo $arrayIva["iva"]; ?>"  readonly="readonly" size="6" style="text-align:right" value="0"/>%
                                </td>
                                <td><input class="inputSinFondo" type="text" id="txtTotalIva<?php echo $key; ?>" name="txtTotalIva<?php echo $key; ?>" readonly="readonly" size="18" style="text-align:right"/></td>
                            </tr>
                        <?php } ?>
                                                
						<tr align="right" id="trNetoPresupuesto" class="trResaltarTotal">
							<td id="tdEtiqTipoDocumento" class="tituloCampo"></td>
                            <td></td>
							<td></td>
							<td align="right"><input type="text" id="txtTotalOrden" name="txtTotalOrden" readonly="readonly" size="18" style="text-align:right" class="inputSinFondo"/></td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
        <tr id="trFormaDePago">
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

				<button class="noprint" type="button" id="btnGuardar" name="btnGuardar" onclick="
					if(byId('hddDevolucionFactura').value === '1'){
							validarDevolucion();
					}else{
                            validarFrmDcto();            
					}" style="cursor:default">
					<table width="73" align="center" cellpadding="0" cellspacing="0">
					<tr>
						<td width="10">&nbsp;</td>
						<td width="18"><img src="../img/iconos/save.png"/></td>
						<td width="10">&nbsp;</td>
						<td width="47">Guardar</td>
					</tr>
					</table>
				</button>
				<button class="noprint" type="button" id="btnCancelar" name="btnCancelar" onclick="
					if(byId('hddTipoDocumento').value == 3){
						if(byId('hddObjCons').value == 0){
							window.location.href='cjrs_devolucion_venta_list.php';
						}else{
							window.location.href='cjrs_factura_venta_list.php';
						}
					}else{
						window.location.href='cjrs_factura_venta_list.php';
					}" style="cursor:default">
					<table width="77" align="center" cellpadding="0" cellspacing="0">
					<tr>
						<td width="10">&nbsp;</td>
						<td width="18"><img src="../img/iconos/cancel.png"/></td>
						<td width="10">&nbsp;</td>
						<td width="51">Cancelar</td>
					</tr>
					</table>
				</button>
			</td>
		</tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%">Paquetes</td></tr></table></div>
	
	<!-- TABLA DE PAQUETES -->
	<table id="tblGeneralPaquetes" border="0" width="960">
    	<tr>
            <td id="tdEncabPaquete" class="tituloPaginaCajaRS"></td>
        </tr>
        <tr class="tituloColumna">
            <td align="center" class="tituloCampo">Mano de Obra</td>
        </tr>
		<tr>
            <td id="tdListadoTempario" align="center"></td>
        </tr>
		<tr>
            <td></td>
        </tr>
		<tr class="tituloColumna">
            <td align="center" class="tituloCampo">Repuestos</td>
        </tr>
		<tr>
            <td id="tdListadoRepuestos" align="center" ></td>
        </tr>
        <tr>
        	<td colspan="2" align="right"><hr /><input type="button" value="Cerrar" onclick="byId('divFlotante3').style.display='none'; byId('divFlotante').style.display='none';"/></td>
        </tr>
	</table>
    
</div>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td></tr></table></div>
		
	<!-- Movimientos Articulos Almancen -->
	<table width="100%" border="0" id="tblMtosArticulos">	
		<tr align="left">
			<td align="right" class="tituloCampo" width="30%">C&oacute;digo Art&iacute;culo:</td>
			<td id="tdCodigoArticuloMto" width="70%">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" id="tdListadoEstadoMtoArt"></td>
		</tr>
		<tr>
			<td colspan="2" align="right"><hr /><input type="button" name="btnCancelarMtoArt" id="btnCancelarMtoArt" value="Cerrar" onclick="byId('divFlotante3').style.display='none';"/></td>
		</tr>
	</table>
</div>

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
        	<input type="hidden" name="hddObjDetallePagoDeposito" id="hddObjDetallePagoDeposito"/>
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
		cellColorScheme:"brown"
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
xajax_validarTipoDocumento('<?php echo $_GET['doc_type']; ?>','<?php echo $_GET['id']; ?>','<?php echo $_GET['ide']; ?>','<?php echo $_GET['acc']; ?>', xajax.getFormValues('frmTotalDcto')); //if(byId('hddAccionTipoDocumento').value != 1) //xajax_visualizarMecanicoEnOrden();

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);
</script>