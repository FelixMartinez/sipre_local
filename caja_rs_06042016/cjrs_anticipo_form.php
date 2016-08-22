<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_anticipo_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_anticipo_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE 2.0 :. Caja de Repuestos y Servicios - Anticipo</title>
	<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
	
	<script type="text/javascript" language="javascript" src="../vehiculos/vehiculos.inc.js"></script>
	<script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
	<script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
	<script type="text/javascript" language="javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" language="javascript" src="../js/jquery.maskedinput.js"></script>
		
	<script>
	jQuery.noConflict();
		jQuery(function($){
			//$("#numeroCuenta").mask("9999-9999-99-9999999999",{placeholder:" "});
			//$("#txtNroCuentaDeposito").mask("9999-9999-99-9999999999",{placeholder:" "});
		});
	</script>
	<script language="javascript">
	function cambiar(){
		validarCampo('selTipoPago','t','lista');
		
		if ($('selTipoPago').value == 1){ efectivo();}
		else if ($('selTipoPago').value == 2){ cheques();}
		else if ($('selTipoPago').value == 3){ deposito();}
		else if ($('selTipoPago').value == 4){ transferencia();}
		else if ($('selTipoPago').value == 5){ tarjetaCredito();}
		else if ($('selTipoPago').value == 6){ tarjetaDebito();}
		else if ($('selTipoPago').value == 11){ cashBack();}
	}
	
	function efectivo(){
		$('tdTituloTipoTarjeta').style.display = 'none';
		$('tdTipoTarjetaCredito').style.display = 'none';
		$('tdEtiquetaBancoOFechaDep').style.display = 'none';
		$('tdBancoCliente').style.display = 'none';
		$('tdTablaFechaDeposito').style.display = 'none';
		$('tdTituloPorcentajeRetencion').style.display = 'none';
		$('tdPorcentajeRetencion').style.display = 'none';
		$('tdTituloMontoRetencion').style.display = 'none';
		$('tdMontoRetencion').style.display = 'none';
		$('tdTituloBancoCompania').style.display = 'none';
		$('tdBancoCompania').style.display = 'none';
		$('tdTituloPorcentajeComision').style.display = 'none';
		$('tdPorcentajeComision').style.display = 'none';
		$('tdTituloMontoComision').style.display = 'none';
		$('tdMontoComision').style.display = 'none';
		$('tdTituloNumeroCuenta').style.display = 'none';
		$('tdNumeroCuentaTexto').style.display = 'none';
		$('tdNumeroCuentaSelect').style.display = 'none';
		$('tdTituloNumeroDocumento').style.display = 'none';
		$('tdNumeroDocumento').style.display = 'none';
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = '';
	}
	
	function cheques(){
		$('tdTituloTipoTarjeta').style.display = 'none';
		$('tdTipoTarjetaCredito').style.display = 'none';
		$('tdEtiquetaBancoOFechaDep').style.display = '';
		$('tdBancoCliente').style.display = '';
		$('tdTablaFechaDeposito').style.display = 'none';
		$('tdTituloPorcentajeRetencion').style.display = 'none';
		$('tdPorcentajeRetencion').style.display = 'none';
		$('tdTituloMontoRetencion').style.display = 'none';
		$('tdMontoRetencion').style.display = 'none';
		$('tdTituloBancoCompania').style.display = 'none';
		$('tdBancoCompania').style.display = 'none';
		$('tdTituloPorcentajeComision').style.display = 'none';
		$('tdPorcentajeComision').style.display = 'none';
		$('tdTituloMontoComision').style.display = 'none';
		$('tdMontoComision').style.display = 'none';
		$('tdTituloNumeroCuenta').style.display = '';
		$('tdNumeroCuentaTexto').style.display = '';
		$('tdNumeroCuentaSelect').style.display = 'none';
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
		
		xajax_cargarBancoCompania(2);
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = '';
	}
	
	function deposito(){
		$('tdTituloTipoTarjeta').style.display = 'none';
		$('tdTipoTarjetaCredito').style.display = 'none';
		$('tdEtiquetaBancoOFechaDep').style.display = '';
		$('tdBancoCliente').style.display = 'none';
		$('tdTablaFechaDeposito').style.display = '';
		$('tdTituloPorcentajeRetencion').style.display = 'none';
		$('tdPorcentajeRetencion').style.display = 'none';
		$('tdTituloMontoRetencion').style.display = 'none';
		$('tdMontoRetencion').style.display = 'none';
		$('tdTituloBancoCompania').style.display = '';
		$('tdBancoCompania').style.display = '';
		$('tdTituloPorcentajeComision').style.display = 'none';
		$('tdPorcentajeComision').style.display = 'none';
		$('tdTituloMontoComision').style.display = 'none';
		$('tdMontoComision').style.display = 'none';
		$('tdTituloNumeroCuenta').style.display = '';
		$('tdNumeroCuentaTexto').style.display = 'none';
		$('tdNumeroCuentaSelect').style.display = '';
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('tdEtiquetaBancoOFechaDep').innerHTML = 'Fecha Deposito:';
		
		xajax_cargarBancoCompania(3);
		
		$('btnAgregarDetDeposito').style.display = '';
		$('agregar').style.display = 'none';
	}
	
	function transferencia(){
		$('tdTituloTipoTarjeta').style.display = 'none';
		$('tdTipoTarjetaCredito').style.display = 'none';
		$('tdEtiquetaBancoOFechaDep').style.display = '';
		$('tdBancoCliente').style.display = '';
		$('tdTablaFechaDeposito').style.display = 'none';
		$('tdTituloPorcentajeRetencion').style.display = 'none';
		$('tdPorcentajeRetencion').style.display = 'none';
		$('tdTituloMontoRetencion').style.display = 'none';
		$('tdMontoRetencion').style.display = 'none';
		$('tdTituloBancoCompania').style.display = '';
		$('tdBancoCompania').style.display = '';
		$('tdTituloPorcentajeComision').style.display = 'none';
		$('tdPorcentajeComision').style.display = 'none';
		$('tdTituloMontoComision').style.display = 'none';
		$('tdMontoComision').style.display = 'none';
		$('tdTituloNumeroCuenta').style.display = '';
		$('tdNumeroCuentaTexto').style.display = 'none';
		$('tdNumeroCuentaSelect').style.display = '';
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
		
		xajax_cargarBancoCompania(4);
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = '';
	}
	
	function tarjetaCredito(){
		$('tdTituloTipoTarjeta').style.display = '';
		$('tdTipoTarjetaCredito').style.display = '';
		$('tdEtiquetaBancoOFechaDep').style.display = '';
		$('tdBancoCliente').style.display = '';
		$('tdTablaFechaDeposito').style.display = 'none';
		$('tdTituloPorcentajeRetencion').style.display = '';
		$('tdPorcentajeRetencion').style.display = '';
		$('tdTituloMontoRetencion').style.display = '';
		$('tdMontoRetencion').style.display = '';
		$('tdTituloBancoCompania').style.display = '';
		$('tdBancoCompania').style.display = '';
		$('tdTituloPorcentajeComision').style.display = '';
		$('tdPorcentajeComision').style.display = '';
		$('tdTituloMontoComision').style.display = '';
		$('tdMontoComision').style.display = '';
		$('tdTituloNumeroCuenta').style.display = '';
		$('tdNumeroCuentaTexto').style.display = 'none';
		$('tdNumeroCuentaSelect').style.display = '';
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
		
		xajax_cargarBancoCompania(5);
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = '';
	}
		
	function tarjetaDebito(){
		$('tdTituloTipoTarjeta').style.display = 'none';
		$('tdTipoTarjetaCredito').style.display = 'none';
		$('tdEtiquetaBancoOFechaDep').style.display = '';
		$('tdBancoCliente').style.display = '';
		$('tdTablaFechaDeposito').style.display = 'none';
		$('tdTituloPorcentajeRetencion').style.display = 'none';
		$('tdPorcentajeRetencion').style.display = 'none';
		$('tdTituloMontoRetencion').style.display = 'none';
		$('tdMontoRetencion').style.display = 'none';
		$('tdTituloBancoCompania').style.display = '';
		$('tdBancoCompania').style.display = '';
		$('tdTituloPorcentajeComision').style.display = '';
		$('tdPorcentajeComision').style.display = '';
		$('tdTituloMontoComision').style.display = '';
		$('tdMontoComision').style.display = '';
		$('tdTituloNumeroCuenta').style.display = '';
		$('tdNumeroCuentaTexto').style.display = 'none';
		$('tdNumeroCuentaSelect').style.display = '';
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
		
		xajax_cargarBancoCompania(6);
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = '';
	}
	
	function cashBack(){
		$('tdTituloTipoTarjeta').style.display = 'none';
		$('tdTipoTarjetaCredito').style.display = 'none';
		$('tdEtiquetaBancoOFechaDep').style.display = 'none';
		$('tdBancoCliente').style.display = 'none';
		$('tdTablaFechaDeposito').style.display = 'none';
		$('tdTituloPorcentajeRetencion').style.display = 'none';
		$('tdPorcentajeRetencion').style.display = 'none';
		$('tdTituloMontoRetencion').style.display = 'none';
		$('tdMontoRetencion').style.display = 'none';
		$('tdTituloBancoCompania').style.display = 'none';
		$('tdBancoCompania').style.display = 'none';
		$('tdTituloPorcentajeComision').style.display = 'none';
		$('tdPorcentajeComision').style.display = 'none';
		$('tdTituloMontoComision').style.display = 'none';
		$('tdMontoComision').style.display = 'none';
		$('tdTituloNumeroCuenta').style.display = 'none';
		$('tdNumeroCuentaTexto').style.display = 'none';
		$('tdNumeroCuentaSelect').style.display = 'none';
		$('tdTituloNumeroDocumento').style.display = 'none';
		$('tdNumeroDocumento').style.display = 'none';
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = '';
	}
	
	function validar(){
		error = false;
		if ($('selTipoPago').value == 1){/*EFECTIVO*/
		if (!(validarCampo('montoPago','t','monto') == true)){
				validarCampo('montoPago','t','monto');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardarPago').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			}
		}
		else if ($('selTipoPago').value == 2){/*CHEQUES*/
		if (!(validarCampo('montoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('numeroCuenta','t','') == true
			&& validarCampo('numeroControl','t','') == true)){
				validarCampo('montoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('numeroCuenta','t','');
				validarCampo('numeroControl','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardarPago').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			}
		}
		else if ($('selTipoPago').value == 3){/*DEPOSITO*/
			if (validarCampo('montoPago','t','monto') == true
			 && validarCampo('txtFechaDeposito','t','fecha') == true
			 && validarCampo('selBancoCompania','t','lista') == true
			 && validarCampo('selNumeroCuenta','t','') == true
			 && validarCampo('numeroControl','t','') == true){
				$('divFlotante').style.display = '';
				$('tdFlotanteTitulo').innerHTML = 'Detalle Deposito';
				$('tblDetallePago').style.display = '';
				$('tblListados').style.display = 'none';
				document.forms['frmDetalleDeposito'].reset();
				centrarDiv($('divFlotante'));
				
				$('txtSaldoDepositoBancario').value = $('montoPago').value;
				$('hddSaldoDepositoBancario').value = $('montoPago').value;
				$('txtTotalDeposito').value = "0.00";
			 }
			 else {
				validarCampo('montoPago','t','monto');
				validarCampo('txtFechaDeposito','t','fecha');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','');
				validarCampo('numeroControl','t','');
				alert("Los campos señalados en rojo son requeridos");
			}
		}
		else if ($('selTipoPago').value == 4){/*TRANSFERENCIA*/
		if (!(validarCampo('montoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','') == true
			&& validarCampo('numeroControl','t','') == true)){
				validarCampo('montoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','');
				validarCampo('numeroControl','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardarPago').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			}
		}
		else if ($('selTipoPago').value == 5){/*TARJETA DE CREDITO*/
		if (!(validarCampo('montoPago','t','monto') == true
			&& validarCampo('tarjeta','t','lista') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('numeroControl','t','') == true
			&& validarCampo('porcentajeRetencion','t','numPositivo') == true
			&& validarCampo('montoTotalRetencion','t','numPositivo') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)){
				validarCampo('montoPago','t','monto');
				validarCampo('tarjeta','t','lista');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('numeroControl','t','');
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
				byId('agregar').disabled = true;
				byId('btnGuardarPago').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			}
		}
		else if ($('selTipoPago').value == 6){/*TARJETA DE DEBITO*/
		if (!(validarCampo('montoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('numeroControl','t','') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)){
				validarCampo('montoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('numeroControl','t','');
				validarCampo('porcentajeComision','t','numPositivo');
				validarCampo('montoTotalComision','t','numPositivo');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardarPago').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			}
		}
		if ($('selTipoPago').value == 11){/*CASH BACK*/
		if (!(validarCampo('montoPago','t','monto') == true)){
				validarCampo('montoPago','t','monto');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardarPago').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			}
		}		
		else {
			if (!(validarCampo('selTipoPago','t','lista') == true)){
				validarCampo('selTipoPago','t','lista');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			}
		}
	}

	function validarSoloNumerosConPunto (evento){
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
	
	function confirmarEliminarPago(pos){
		if(confirm("Desea elmiminar el pago?"))
			xajax_eliminarPago(xajax.getFormValues('frmListadoPagos'),pos);
	}
	
	function cambiarTipoPagoDetalleDeposito(){
		if ($('lstTipoPago').value == 1){
			$('trBancoCliente').style.display = 'none';
			$('trNroCuenta').style.display = 'none';
			$('trNroCheque').style.display = 'none';
			$('trMonto').style.display = '';
		}
		else if ($('lstTipoPago').value == 2){
			$('trBancoCliente').style.display = '';
			$('trNroCuenta').style.display = '';
			$('trNroCheque').style.display = '';
			$('trMonto').style.display = '';}
		
	}
	
	function validarDetalleDeposito(){
		var saldo = $('hddSaldoDepositoBancario').value.replace(/,/gi,'');
		var contador = 1;
		var total = 0;
		var pagos = 0;
		
		obj = $('hddObjDetallePagoDeposito').value;
		arregloObj = obj.split("|");
			
		while (contador < arregloObj.length){
			monto = $('txtMontoDetalleDeposito'+arregloObj[contador]).value.replace(/,/gi,'');
			pagos += parseFloat(monto);
			contador++;
		}
		
		total = saldo - pagos;		
		
		if ($('lstTipoPago').value == 1){/*EFECTIVO*/
			if (validarCampo('txtMontoDeposito','t','monto') == true)
				if ((total + 0.001) >= $('txtMontoDeposito').value.replace(/,/gi,''))
					xajax_cargarPagoDetalleDeposito(xajax.getFormValues('frmDetalleDeposito'));
				else
					alert("El monto a pagar no puede ser mayor que el saldo del deposito");
			else{
				validarCampo('txtMontoDeposito','t','monto');
				alert("El campo señalado en rojo es requerido");
			}
		}
		else if ($('lstTipoPago').value == 2){/*CHEQUES*/
			if (validarCampo('txtMontoDeposito','t','monto') == true
			 && validarCampo('lstBancoDeposito','t','lista') == true
			 && validarCampo('txtNroCuentaDeposito','t','') == true
			 && validarCampo('txtNroChequeDeposito','t','') == true)
				if ((total + 0.001) >= $('txtMontoDeposito').value.replace(/,/gi,''))
					xajax_cargarPagoDetalleDeposito(xajax.getFormValues('frmDetalleDeposito'));
				else
					alert("El monto a pagar no puede ser mayor que el saldo del deposito");
			else{
				validarCampo('txtMontoDeposito','t','monto');
				validarCampo('lstBancoDeposito','t','lista');
				validarCampo('txtNroCuentaDeposito','t','');
				validarCampo('txtNroChequeDeposito','t','');
				alert("Los campos señalados en rojo son requeridos");
			}
		}
		else{
			validarCampo('lstTipoPago','t','lista');
			alert("Seleccione una forma de pago");
		}
	}
	
	function validarAgregarDeposito(){
		if($('txtSaldoDepositoBancario').value == 0){
			xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
		}
		else
			alert("El saldo del detalle del deposito debe ser 0 (cero)");
	}
	
	function calcularMontoTotalTarjetaCredito() {
		if ($('selTipoPago').value == 5){
		$("montoTotalRetencion").value = formato(parsenum(parsenum($("montoPago").value) * parsenum($("porcentajeRetencion").value) / 100));
		$("montoTotalComision").value = formato(parsenum(parsenum($("montoPago").value) * parsenum($("porcentajeComision").value) / 100));
		}else if($('selTipoPago').value){
		$("montoTotalComision").value = formato(parsenum(parsenum($("montoPago").value) * parsenum($("porcentajeComision").value) / 100));
		}
	}
							
	function confirmarEliminarPagoDetalleDeposito(pos){
		if(confirm("Desea elmiminar el detalle del deposito?"))
			xajax_eliminarPagoDetalleDeposito(xajax.getFormValues('frmDetalleDeposito'),pos);
	}
	
	function calcularTotalDeposito(){
		var saldo = $('hddSaldoDepositoBancario').value.replace(/,/gi,'');
		var contador = 1;
		var total = 0;
		var pagos = 0;
		
		obj = $('hddObjDetallePagoDeposito').value;
		arregloObj = obj.split("|");
			
		while (contador < arregloObj.length){
			monto = $('txtMontoDetalleDeposito'+arregloObj[contador]).value.replace(/,/gi,'');
			pagos += parseFloat(monto);
			contador++;
		}
		
		total = saldo - pagos;
		
		if (total < 0.001 && total > -0.001)
			total = 0;
			
		$('txtSaldoDepositoBancario').value = formato(parsenum(total));
		$('txtTotalDeposito').value = formato(parsenum(pagos));
	}
	
	function calcularTotal(){
		var saldo = parseFloat($('txtMontoAnticipo').value.replace(/,/gi,''));
		var contador = 1;
		var total = 0;
		var pagos = 0;
		
		obj = $('hddObjDetallePago').value;
		arregloObj = obj.split("|");
			
		while (contador < arregloObj.length){
			monto = parseFloat($('txtMonto'+arregloObj[contador]).value.replace(/,/gi,''));
			pagos += monto;
			contador++;
		}
		
		total = saldo - pagos;
		
		$('txtSaldoAnticipo').value = formato(parsenum(total));
		$('hddMontoFaltaPorPagar').value = formato(parsenum(total));
		$('txtMontoPagadoAnticipo').value = formato(parsenum(pagos));
		
		if (total > 0) {
			$('btnAgregarDetDeposito').disabled = false;
			$('agregar').disabled = false;
			$('btnGuardarPago').disabled = false;
			$('btnCancelar').disabled = false;
		} else {
			$('btnAgregarDetDeposito').disabled = true;
			$('agregar').disabled = true;
			$('btnGuardarPago').disabled = false;
			$('btnCancelar').disabled = false;
		}	
	}
	
	function validarGuardar(){
		if(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtFecha','t','fecha') == true
		&& validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('txtObservacionAnticipo','t','') == true
		&& validarCampo('txtMontoAnticipo','t','monto') == true){
			$('btnGuardarPago').disabled = true;		
			xajax_guardarAnticipo(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListadoPagos'),xajax.getFormValues('frmDcto'));
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtFecha','t','fecha');
			validarCampo('lstModulo','t','lista');
			validarCampo('txtObservacionAnticipo','t','');
			validarCampo('txtMontoAnticipo','t','monto');
			
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
		}
	}
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCajaRS">Anticipo</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmDcto" name="frmDcto" style="margin:0">
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
							<fieldset><legend class="legend">Datos del Cliente</legend>
								<table border="0" width="100%">
								<tr>
									<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
									<td colspan="3">
										<table cellpadding="0" cellspacing="0">
										<tr>
											<td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" class="inputHabilitado" size="6" style="text-align:right"/></td>
											<td><button type="button" id="btnCliente" name="btnCliente" onclick="document.forms['frmBuscarCliente'].reset(); $('btnBuscarCliente').click();" title="Listar"><img src="../img/iconos/help.png"/></button></td>
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
							<fieldset><legend class="legend">Observación</legend>
							<table>
							<tr>
								<td width="120" rowspan="2" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
								<td colspan="3" rowspan="2" align="left"><label><textarea name="txtObservacionAnticipo" id="txtObservacionAnticipo" cols="60" rows="2"></textarea></label></td>
							</tr>
							</table>
							</fieldset>
							</td>
							<td valign="top" width="30%">
							<fieldset><legend class="legend">Datos del Anticipo</legend>
								<input type="hidden" id="hddIdAnticipo" name="hddIdAnticipo"/>
								<table border="0" width="100%">
								<tr>
									<td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Nro. Anticipo:</td>
									<td width="60%">
										<input type="text" id="txtNumeroAnticipo" name="txtNumeroAnticipo" value="Por Asignar" readonly="readonly" size="20" style="text-align:center"/>
									</td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Departamento:</td>
									<td align="left" id="tdlstModulo"></td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Monto:</td>
									<td><input type="text" id="txtMontoAnticipo" name="txtMontoAnticipo" size="20" class="inputHabilitado" style="text-align:right" onblur="$('txtSaldoAnticipo').value = formato(parsenum(this.value)); $('hddMontoFaltaPorPagar').value = formato(parsenum(this.value));$('hddSaldoAnticipo').value = formato(parsenum(this.value)); this.value = formato(parsenum(this.value));"/></td>
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
			<form id="frmDetallePago" name="frmDetallePago">
				<fieldset><legend class="legend">Forma de Pago</legend>
				<table border="0" width="100%">
				<tr align="left">
					<td align="right" class="tituloCampo" width="164">Tipo de Pago:</td>
					<td id="tdTipoPago">
						<select name="selTipoPago" id="selTipoPago" onChange="cambiar()">
							<option>Tipo pago</option>
						</select>
					</td>
					<td width="28"></td>
					<td id="tdTituloTipoTarjeta" align="right" class="tituloCampo" scope="row" style="display:none;">Tipo Tarjeta:</td>
					<td id="tdTipoTarjetaCredito" colspan="4" scope="row" style="display:none;">
						<select id="tarjeta" name="tarjeta">
							<option value="">[ Seleccione ]</option>
						</select>
					</td>
				</tr>
				<tr align="left">
					<td id="tdEtiquetaBancoOFechaDep" align="right" class="tituloCampo">Banco Cliente:</td>
					<td id="tdBancoCliente" scope="row">
						<select name="selBancoCliente" id="selBancoCliente">
							<option value="">[ Seleccione ]</option>
						</select>
					</td>
					<td id="tdTablaFechaDeposito" style="display:none">
						<table width="26%" border="0" cellpadding="0" cellspacing="0" id="tblFechaDeposito">
						<tr>
							<td width="35%">
								<label><input type="text" name="txtFechaDeposito" id="txtFechaDeposito" readonly="readonly" onClick="imgFechaDeposito.onclick();"/></label>
							</td>
							<td width="65%" align="left">
								<img id="imgFechaDeposito" src="../img/iconos/ico_date.png" width="21" height="17"/>
								<script type="text/javascript">
								Calendar.setup({
								inputField : "txtFechaDeposito", // id del campo de texto
								ifFormat : "%d-%m-%Y", // formato de la fecha que se escriba en el campo de texto
								button : "imgFechaDeposito" // el id del boton que lanzara el calendario
								});
								</script>
							</td>
						</tr>
						</table>
					</td>
					<td></td>
					<td id="tdTituloPorcentajeRetencion" align="right" class="tituloCampo" style="display:none;">Porcentaje Retenci&oacute;n:</td>
					<td id="tdPorcentajeRetencion" scope="row" style="display:none">
						<input name="porcentajeRetencion" type="text" id="porcentajeRetencion" size="10" readonly="readonly" style="text-align:right; background-color:#EEEEEE;"/>
					</td>
					<td id="tdTituloMontoRetencion" align="right" class="tituloCampo" scope="row" style="display:none;">Monto:</td>
					<td id="tdMontoRetencion" scope="row" style="display:none;">
						<input name="montoTotalRetencion" type="text" id="montoTotalRetencion" style="text-align:right; background-color:#EEEEEE;" size="19" readonly="readonly"/>
					</td>
				</tr>
				<tr align="left">
					<td id="tdTituloBancoCompania" align="right" class="tituloCampo">Banco Compa&ntilde;ia:</td>
					<td id="tdBancoCompania">
						<select name="selBancoCompania" id="selBancoCompania">
							<option></option>
						</select>
					</td>
					<td></td>
					<td id="tdTituloPorcentajeComision" align="right" class="tituloCampo" style="display:none;">Porcentaje Comisi&oacute;n:</td>
					<td id="tdPorcentajeComision" scope="row" style="display:none;">
						<input name="porcentajeComision" type="text" id="porcentajeComision" size="10" readonly="readonly" style="text-align:right; background-color:#EEEEEE;" value="0.00"/>
					</td>
					<td id="tdTituloMontoComision" align="right" class="tituloCampo" scope="row" style="display:none;">Monto:</td>
					<td id="tdMontoComision" scope="row" style="display:none;">
						<input name="montoTotalComision" type="text" id="montoTotalComision" style="text-align:right; background-color:#EEEEEE;" size="19" readonly="readonly" value="0.00"/>
					</td>
				</tr>
				<tr align="left">
					<td id="tdTituloNumeroCuenta" align="right" class="tituloCampo">Nro. de Cuenta:</td>
					<td id="tdNumeroCuentaTexto" colspan="8">
						<input type="text" id="numeroCuenta" name="numeroCuenta" size="30"/>
					</td>
					<td id="tdNumeroCuentaSelect" colspan="8" style="display:none">
						<select id="selNumeroCuenta" name="selNumeroCuenta">
							<option value="">[ Seleccione ]</option>
						</select>
					</td>
				</tr>
				<tr align="left">
					<td id="tdTituloNumeroDocumento" align="right" class="tituloCampo">Nro.:</td>
					<td id="tdNumeroDocumento" colspan="8">
						<input type="text" id="numeroControl" name="numeroControl" size="30"/>
					</td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo">Monto:</td>
					<td>
						<table cellpadding="0" cellspacing="0"><!-- botones()-->
						<tr>
							<td><input type="text" name="montoPago" id="montoPago" onblur="this.value=formato(parsenum(this.value)); calcularMontoTotalTarjetaCredito();" onkeypress="return validarSoloNumerosConPunto(event);" style="text-align:right" class="inputHabilitado"/></td>
							<td id="tdImgAgregarFormaDeposito">
								<button style="display:none" type="button" id="btnAgregarDetDeposito" name="btnAgregarDetDeposito" onClick="validar();" title="Agregar Detalle del Deposito"> <img src="../img/iconos/money_add.png" width="16" height="16"/></button>
							</td>
							<td>
								<button type="button" id="agregar" name="agregar" onclick="validar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Pago</td></tr></table></button>
							</td>
						</tr>
						</table>
						<input type="hidden" id="ocultoAgregarPagoAnticipo" name="ocultoAgregarPagoAnticipo" value="1"/>
						<input type="hidden" id="hddObjDetallePago" name="hddObjDetallePago"/>
						<input type="hidden" id="hddObjDetalleDeposito" name="hddObjDetalleDeposito"/>
						<input type="hidden" id="hddObjDetalleDepositoFormaPago" name="hddObjDetalleDepositoFormaPago"/>
						<input type="hidden" id="hddObjDetalleDepositoBanco" name="hddObjDetalleDepositoBanco"/>
						<input type="hidden" id="hddObjDetalleDepositoNroCuenta" name="hddObjDetalleDepositoNroCuenta"/>
						<input type="hidden" id="hddObjDetalleDepositoNroCheque" name="hddObjDetalleDepositoNroCheque"/>
						<input type="hidden" id="hddObjDetalleDepositoMonto" name="hddObjDetalleDepositoMonto"/>
						<input type="hidden" id="hddMontoFaltaPorPagar" name="hddMontoFaltaPorPagar"/>
					</td>
				</tr>
				</table>
				</fieldset>
			</form>
			</td>
		</tr>
		<tr>
			<td>
				<form id="frmListadoPagos" name="frmListadoPagos">
				<fieldset><legend class="legend">Desglose de Pagos</legend>
				<table>
					<tr align="center" class="tituloColumna">
						<td width="15%" class="tituloColumna">Tipo Pago</td>
						<td width="20%" class="tituloColumna">Banco Cliente</td>
						<td width="20%" class="tituloColumna">Banco Compañia</td>
						<td width="25%" class="tituloColumna">Cuenta</td>
						<td width="10%" class="tituloColumna">Nro. Control</td>
						<td width="10%" class="tituloColumna">Monto</td>
						<td class="tituloColumna">&nbsp;</td>
					</tr>
					<tr id="trItmPie"></tr>
					<tr class="tituloColumna">
						<td colspan="3"><strong>Monto que falta por pagar:
							<input type="text" name="txtSaldoAnticipo" id="txtSaldoAnticipo" readonly="readonly" value="0" style="text-align:right;" class="trResaltarTotal3"/>
							<input type="hidden" name="hddSaldoAnticipo" id="hddSaldoAnticipo"/></strong>
						</td>
						<td colspan="4"><strong>Monto Pagado del Anticipo:
							<input type="text" name="txtMontoPagadoAnticipo" id="txtMontoPagadoAnticipo" value="0" readonly="readonly" style="text-align:right" class="trResaltarTotal"/></strong>
						</td>
					</tr>
				</table>
				</fieldset>
				</form>
			</td>
		</tr>
		<tr align="right">
			<td colspan="8"><hr/>
				<button type="button" id="btnGuardarPago" name="btnGuardarPago" onclick="validarGuardar();" disabled="disabled"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
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
	<form id="frmDetalleDeposito" name="frmDetalleDeposito" style="margin:0">
	<table border="0" id="tblDetallePago" style="display:none" width="700px">
	<tr>
		<td width="32%">&nbsp;</td>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr align="left">
		<td class="tituloCampo" width="120">Forma de Pago:</td>
		<td id="tdlstTipoPago" colspan="5">
			<select id="lstTipoPago" name="lstTipoPago">
				<option value="-1">[ Seleccione ]</option>
			</select>
			<script>
				xajax_cargarTipoPagoDetalleDeposito();
			</script>
		</td>
	</tr>
	<tr id="trBancoCliente" style="display:none">
		<td class="tituloCampo">Banco:</td>
		<td colspan="5" id="tdBancoDeposito"> 
			<select id="lstBancoDeposito" name="lstBancoDeposito">
				<option value="-1">[ Seleccione ]</option>
			</select>
			<script>
				xajax_cargarBancoCliente("tdBancoDeposito","lstBancoDeposito");
			</script>
		</td>
	</tr>
	<tr id="trNroCuenta" style="display:none">
		<td class="tituloCampo">Nro. Cuenta:</td>
		<td colspan="5"><input type="text" name="txtNroCuentaDeposito" id="txtNroCuentaDeposito" size="30"/></td>
	</tr>
	<tr id="trNroCheque" style="display:none">
		<td class="tituloCampo">Nro. Cheque:</td>
		<td colspan="5"> <input type="text" name="txtNroChequeDeposito" id="txtNroChequeDeposito"/></td>
	</tr>
	<tr id="trMonto" style="display:none">
		<td class="tituloCampo">Monto:</td>
		<td colspan="5" align="left">
			<input type="text" name="txtMontoDeposito" id="txtMontoDeposito" onChange="this.value=formato(parsenum(this.value));" onkeypress="return inputnum(event);"/>
			<button type="button" id="btnAgregarMontoDeposito" name="btnAgregarMontoDeposito" onclick="validarDetalleDeposito();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Agregar</td></tr></table></button>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">
			<table width="100%" border="0" cellpadding="0">
			<tr class="tituloColumna" align="center">
				<td>Forma de Pago</td>
				<td>Banco</td>
				<td>Nro. Cuenta</td>
				<td>Nro. Cheque</td>
				<td>Monto</td>
				<td>&nbsp;</td>
			</tr>
			<tr id="trItmPieDeposito"></tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>Saldo:</td>
		<td>
			<input name="txtSaldoDepositoBancario" type="text" id="txtSaldoDepositoBancario" readonly="readonly" style="text-align:right" class="trResaltarTotal3"/>
			<input name="hddSaldoDepositoBancario" type="hidden" id="hddSaldoDepositoBancario" readonly="readonly"/>
		</td>
		<td>&nbsp;</td>
		<td>Total:</td>
		<td><input name="txtTotalDeposito" type="text" id="txtTotalDeposito" readonly="readonly" style="text-align:right" class="trResaltarTotal"/></td>
	</tr>
	<tr>
		<td colspan="6"><hr/></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td width="54%" colspan="3"><input type="hidden" name="hddObjDetallePagoDeposito" id="hddObjDetallePagoDeposito"/></td>
		<td width="7%" align="right">
			<button type="button" id="btnGuardarDetalleDeposito" name="btnGuardarDetalleDeposito" onclick="validarAgregarDeposito();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
		</td>
		<td width="7%" align="right">
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="$('divFlotante').style.display='none'; xajax_eliminarPagoDetalleDepositoForzado(xajax.getFormValues('frmDetalleDeposito'))" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
	</form>
	<table border="0" id="tblListados" style="display:none" width="700px">
	<tr id="trBuscarCliente">
		<td>
			<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="$('btnBuscarCliente').click(); return false;" style="margin:0">
			<table align="right">
			<tr>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioBusqCliente" name="txtCriterioBusqCliente" onkeyup="$('btnBuscarCliente').click();"/></td>
				<td>
					<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
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
					<button type="button" id="btnCancelarMetodoPago" name="btnCancelarMetodoPago" onclick="$('divFlotante').style.display = 'none';">Cancelar</button>
				</td>
			</tr>
			</table>
			</form>
		</td>
	</tr>
	</table>
</div>

<script language="javascript">
byId('txtFecha').value = "<?php echo date("d-m-Y")?>";

xajax_validarAperturaCaja();
xajax_asignarEmpresaUsuario(<?php echo $_SESSION['idEmpresaUsuarioSysGts'] ?>, "Empresa", "ListaEmpresa");
xajax_cargaLstModulo();
xajax_cargarTipoPago();
xajax_cargarBancoCliente("tdBancoCliente","selBancoCliente");
xajax_cargarBancoCompania();

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>