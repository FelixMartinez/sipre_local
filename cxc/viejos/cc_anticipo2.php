<?php
require_once ("../connections/conex.php");

require_once("../inc_sesion.php");

session_start();

require ('../controladores/xajax/xajax_core/xajax.inc.php');

$xajax = new xajax();

$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_anticipo2.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Anticipo</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css"/>
	<script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
	<script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
	<script type="text/javascript" language="javascript" src="../js/jquery.js" ></script>
	<script type="text/javascript" language="javascript" src="../js/jquery.maskedinput.js" ></script>
	
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
	
	function validar(){
		if ($('selTipoPago').value == 1){/*EFECTIVO*/
			if (validarCampo('txtMontoAnticipo','t','monto') == true
			 && validarCampo('montoPago','t','monto') == true)
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			else{
				validarCampo('montoPago','t','monto');
				alert("El campo señalado en rojo es requerido");
			}
		}
		else if ($('selTipoPago').value == 2){/*CHEQUES*/
			if (validarCampo('txtMontoAnticipo','t','monto') == true
			 && validarCampo('montoPago','t','monto') == true
			 && validarCampo('selBancoCliente','t','lista') == true
			 && validarCampo('numeroCuenta','t','') == true
			 && validarCampo('numeroControl','t','') == true)
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			else{
				validarCampo('txtMontoAnticipo','t','monto') == true
				validarCampo('montoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('numeroCuenta','t','');
				validarCampo('numeroControl','t','');
				alert("Los campos señalados en rojo son requeridos");
			}
		}
		else if ($('selTipoPago').value == 3){/*DEPOSITO*/
			if (validarCampo('txtMontoAnticipo','t','monto') == true
			 && validarCampo('montoPago','t','monto') == true
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
			else{
				validarCampo('txtMontoAnticipo','t','monto') == true
				validarCampo('montoPago','t','monto');
				validarCampo('txtFechaDeposito','t','fecha');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','');
				validarCampo('numeroControl','t','');
				alert("Los campos señalados en rojo son requeridos");
			}
		}
		else if ($('selTipoPago').value == 4){/*TRANSFERENCIA*/
			if (validarCampo('txtMontoAnticipo','t','monto') == true
			 && validarCampo('montoPago','t','monto') == true
			 && validarCampo('selBancoCliente','t','lista') == true
			 && validarCampo('selBancoCompania','t','lista') == true
			 && validarCampo('selNumeroCuenta','t','') == true
			 && validarCampo('numeroControl','t','') == true)
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			else{
				validarCampo('txtMontoAnticipo','t','monto') == true
				validarCampo('montoPago','t','monto');
			 	validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','');
				validarCampo('numeroControl','t','');
				alert("Los campos señalados en rojo son requeridos");
			}
		}
		else if ($('selTipoPago').value == 5){/*TARJETA DE CREDITO*/
			if (validarCampo('txtMontoAnticipo','t','monto') == true
			 && validarCampo('montoPago','t','monto') == true
			 && validarCampo('tarjeta','t','lista') == true
			 && validarCampo('selBancoCliente','t','lista') == true
			 && validarCampo('selBancoCompania','t','lista') == true
			 && validarCampo('selNumeroCuenta','t','lista') == true
			 && validarCampo('numeroControl','t','') == true){
			 	if (validarCampo('porcentajeRetencion','t','monto') == true
			 	 && validarCampo('montoTotalRetencion','t','monto') == true
			 	 && validarCampo('porcentajeComision','t','monto') == true
			 	 && validarCampo('montoTotalComision','t','monto') == true)
					xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
				else{
					alert("El monto de retencion y de comision no pueden estar vacios");
				}
			 }
			else{
				validarCampo('txtMontoAnticipo','t','monto') == true
				validarCampo('montoPago','t','monto');
				validarCampo('tarjeta','t','lista');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('numeroControl','t','');
				alert("Los campos señalados en rojo son requeridos");
			}
		}
		else if ($('selTipoPago').value == 6){/*TARJETA DE DEBITO*/
			if (validarCampo('txtMontoAnticipo','t','monto') == true
			 && validarCampo('montoPago','t','monto') == true
			 && validarCampo('selBancoCliente','t','lista') == true
			 && validarCampo('selBancoCompania','t','lista') == true
			 && validarCampo('selNumeroCuenta','t','lista') == true
			 && validarCampo('numeroControl','t','') == true){
			 	if (validarCampo('porcentajeComision','t','monto') == true
			 	 && validarCampo('montoTotalComision','t','monto') == true)
					xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
				else{
					alert("El monto de comision no puede estar vacio");
				}
			 }
			else{
				validarCampo('txtMontoAnticipo','t','monto') == true
				validarCampo('montoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('numeroControl','t','');
				alert("Los campos señalados en rojo son requeridos");
			}
		}
		else{
			validarCampo('txtMontoAnticipo','t','monto')
			validarCampo('selTipoPago','t','lista');
			alert("Los Campos señalados en rojo son requeridos");
		}
	}
	
	function validarSoloNumerosConPunto (evento) {
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
		var saldo = $('hddSaldoDepositoBancario').value.replace(',','');
		var contador = 1;
		var total = 0;
		var pagos = 0;
		
		obj = $('hddObjDetallePagoDeposito').value;
		arregloObj = obj.split("|");
			
		while (contador < arregloObj.length){
			monto = $('txtMontoDetalleDeposito'+arregloObj[contador]).value.replace(',','');
			pagos += parseFloat(monto);
			contador++;
		}
		
		total = saldo - pagos;		
		
		if ($('lstTipoPago').value == 1){/*EFECTIVO*/
			if (validarCampo('txtMontoDeposito','t','monto') == true)
				if (total >= $('txtMontoDeposito').value.replace(',',''))
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
				if (total >= $('txtMontoDeposito').value.replace(',',''))
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
			alert("el saldo del detalle debe ser 0 (cero)");
	}
	
	function confirmarEliminarPagoDetalleDeposito(pos){
		if(confirm("Desea elmiminar el detalle del deposito?"))
			xajax_eliminarPagoDetalleDeposito(xajax.getFormValues('frmDetalleDeposito'),pos);
	
	}
	
	function calcularTotalDeposito(){
		var saldo = $('hddSaldoDepositoBancario').value.replace(',','');
		var contador = 1;
		var total = 0;
		var pagos = 0;
		
		obj = $('hddObjDetallePagoDeposito').value;
		arregloObj = obj.split("|");
			
		while (contador < arregloObj.length){
			monto = $('txtMontoDetalleDeposito'+arregloObj[contador]).value.replace(',','');
			pagos += parseFloat(monto);
			contador++;
		}
		
		total = saldo - pagos;
		
		$('txtSaldoDepositoBancario').value = formato(parsenum(total));
		$('txtTotalDeposito').value = formato(parsenum(pagos));
	}
	
	function calcularTotal(){
		var saldo = $('hddSaldoAnticipo').value.replace(',','');
		var contador = 1;
		var total = 0;
		var pagos = 0;
		
		obj = $('hddObjDetallePago').value;
		arregloObj = obj.split("|");
			
		while (contador < arregloObj.length){
			monto = $('txtMonto'+arregloObj[contador]).value.replace(',','');
			pagos += parseFloat(monto);
			contador++;
		}
		
		total = saldo - pagos;
		
		$('txtSaldoAnticipo').value = formato(parsenum(total));
		$('hddMontoFaltaPorPagar').value = formato(parsenum(total));
		$('txtMontoPagadoAnticipo').value = formato(parsenum(pagos));
		
		if (total == 0){
			$('btnAgregarDetDeposito').disabled = true;
			$('agregar').disabled = true;
			$('btnGuardarPago').disabled = false;
		}		
		else{
			$('btnAgregarDetDeposito').disabled = false;
			$('agregar').disabled = false;
			$('btnGuardarPago').disabled = true;
		}		
				
	}
	
	function validarGuardar(){
		if(validarCampo('lstEmpresa','t','lista') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtNombreCliente','t','') == true
		&& validarCampo('txtRifCliente','t','') == true
		&& validarCampo('txtFecha','t','fecha') == true
		&& validarCampo('slctDeparmamento','t','listaExceptCero') == true
		&& validarCampo('txtObservacionAnticipo','t','') == true
		&& validarCampo('txtMontoAnticipo','t','monto') == true){			
			xajax_guardarAnticipo(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListadoPagos'),xajax.getFormValues('frmDatosAnticipo'));
		}
		else{
			validarCampo('lstEmpresa','t','lista');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtNombreCliente','t','');
			validarCampo('txtRifCliente','t','');
			validarCampo('txtFecha','t','fecha');
			validarCampo('slctDeparmamento','t','lista');
			validarCampo('txtObservacionAnticipo','t','');
			validarCampo('txtMontoAnticipo','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
		}
	}
	</script>
	</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr class="solo_print">
			<td align="left" id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
			<td class="tituloPaginaCuentasPorCobrar"><span id="tituloPagina">Anticipo</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
			<form id="frmDatosAnticipo" name="frmDatosAnticipo" style="margin:0">
			<table border="0" width="100%">	
			<tr>
				<td colspan="2">
					<table border="0">
					<tr align="left">
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
						<td>
							<table cellpadding="0" cellspacing="0">
							<tr>
								<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresa(this.value);" size="6" style="text-align:right;"/></td>
								<td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
							</tr>
							</table>
						</td>
					</tr>
					</table>
					<!--<table border="0">
					<tr align="left">
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
						<td align="left" id="tdSelEmpresa"></td>
					</tr>
					</table>-->
				</td>
			</tr>
			<tr>
				<td valign="top" width="65%">
				<fieldset><legend class="legend">Datos del Cliente</legend>
					<table border="0" width="100%">
					<tr align="left">
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Cliente:</td>
						<td width="55%">
							<table cellpadding="0" cellspacing="0">
							<tr>
								<td>
									<input type="text" id="txtIdCliente" name="txtIdCliente" size="6" readonly="readonly" style="text-align:right"/>
								</td>
								<td>
									<input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/>
								</td>
							</tr>
							</table>
						</td>
						<td align="right" class="tituloCampo" width="120"><?php echo $spanClienteCxC; ?>:</td>
						<td width="15%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
					</tr>
					<tr align="left">
						<td align="right" class="tituloCampo" rowspan="2" width="120">Dirección:</td>
						<td rowspan="2"><textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly" rows="3"></textarea></td>
						<td align="right" class="tituloCampo" width="120"><?php echo $spanNIT; ?>:</td>
						<td><input type="text" id="txtNITCliente" name="txtNITCliente" readonly="readonly" size="16" style="text-align:center"/></td>
					</tr>
					<tr align="left">
						<td align="right" class="tituloCampo" width="120">Teléfono:</td>
						<td><input type="text" id="txtTelefonosCliente" name="txtTelefonosCliente" readonly="readonly" size="12" style="text-align:center"/></td>
					</tr>
					</table>
					</fieldset>
					<fieldset><legend class="legend">Observación</legend>
					<table>
					<tr>
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Observación:</td>
						<td align="left">
							<label><textarea name="txtObservacionAnticipo" id="txtObservacionAnticipo" cols="60" rows="2" readonly="readonly"></textarea></label>
						</td>
					</tr>
					</table>
					</fieldset>
				</td>
				<td valign="top" width="35%">
				<fieldset><legend class="legend">Anticipo</legend>
					<input type="hidden" id="hddIdAnticipo" name="hddIdAnticipo"/>
					<table border="0" width="100%">
					<tr align="left">
						<td align="right" class="tituloCampo" width="120">Nro. Anticipo:</td>
						<td><input type="text" id="txtNumeroAnticipo" name="txtNumeroAnticipo" size="20" value="Por Asignar" style="text-align:center" readonly="readonly"/></td>
					</tr>
					<tr align="left">
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Fecha Anticipo:</td>
						<td><input type="text" id="txtFecha" name="txtFecha" size="16" readonly="readonly"/></td>
					</tr>
					<tr align="left">
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Módulo:</td>
						<td id="tdDeparmamento"><label>
							<select name="slctDeparmamento" id="slctDeparmamento">
								<option value="" selected="selected">Seleccione</option>
								<option value="0" >Repuestos</option>
								<option value="1" >Servicios</option>
								<option value="2" >Vehiculos</option>
								<option value="3" >Administracion</option>
							</select></label>
						</td>
					</tr>
					<tr align="left">
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Monto Anticipo:</td>
						<td><input type="text" id="txtMontoAnticipo" name="txtMontoAnticipo" size="20" style="text-align:right" class="trResaltarTotal" readonly="readonly"/></td>
					</tr>
					<tr align="left">
						<td align="right" class="tituloCampo" width="120">Saldo Anticipo:</td>
						<td><input type="text" id="txtSaldoAnticipo" name="txtSaldoAnticipo" size="20" style="text-align:right" class="trResaltarTotal3" readonly="readonly"/></td>
					</tr>
				</table>
				</fieldset>
                <fieldset id="fieldAnulado"><legend class="legend">Información Adicional</legend>
                <table>
                <tr align="left" id="tdEstatus">
                    <td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td><input type="text" id="txtEstatus" name="txtEstatus" size="15" style="text-align:center" class="divMsjError" readonly="readonly"/></td>
                    <td rowspan="2" align="right" class="tituloCampo" width="120">Motivo Anulación:</td>
                    <td rowspan="2" align="left">
                        <label><textarea name="txtMotivoAnulacion" id="txtMotivoAnulacion" cols="25" rows="2" readonly="readonly"></textarea></label>
                    </td>
                </tr>
                <tr align="left" id="tdEmpleadoAnulacion">
                    <td align="right" class="tituloCampo" width="120">Anulado Por:</td>
                    <td><input type="text" id="txtEmpleadoAnulacion" name="txtEmpleadoAnulacion" size="15" style="text-align:center" class="divMsjError" readonly="readonly"/></td>
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
			<td valign="top">
				<fieldset><legend class="legend">Desglose de pagos</legend>
				<table border="0" width="100%">
				<tr align="left">
					<td>
					<form id="frmListadoPagos" name="frmListadoPagos">
						<table width="100%">
						<tr>
							<td colspan="7" id="tdDesglosePagos"></td>
						</tr>
						</table>
					</form>
					</td>
				</tr>
				</table>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<fieldset><legend class="legend">Documentos Pagados</legend>
				<table border="0" width="100%">
				<tr align="left">
					<td>
						<form id="frmListadoPagos" name="frmListadoPagos">
						<table width="100%">
							<tr>
								<td colspan="7" id="tdDocumentosPagos"></td>
							</tr>
							</table>
						</form>
					</td>
				</tr>
				</table>
				</fieldset>
			</td>
		</tr>
		<tr align="center">
			<td align="right"><hr>
				<button type="button" id="btnCancelar" name="btnCancelar" onClick="history.back();">Cancelar</button>
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
	<table border="0" id="tblDetallePago" style="display:none" width="500px">
	<tr>
		<td width="32%">&nbsp;</td>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr align="left">
		<td class="tituloCampo">Forma pago:</td>
		<td id="tdlstTipoPago" colspan="5" >
			<select id="lstTipoPago" name="lstTipoPago">
				<option value="-1">Seleccione...</option>
			</select>
			<!--<script>
				xajax_cargarTipoPagoDetalleDeposito();
			</script>-->
		</td>
	</tr>
	<tr id="trBancoCliente" style="display:none">
		<td class="tituloCampo" >Banco:</td>
		<td colspan="5" id="tdBancoDeposito"> 
			<select id="lstBancoDeposito" name="lstBancoDeposito">
				<option value="-1">Seleccione...</option>
			</select>
		<!--	<script>
				xajax_cargarBancoCliente("tdBancoDeposito","lstBancoDeposito");
			</script>-->
		</td>
	</tr>
	<tr id="trNroCuenta" style="display:none">
		<td class="tituloCampo">Nro Cuenta:</td>
		<td colspan="5"><input type="text" name="txtNroCuentaDeposito" id="txtNroCuentaDeposito" size="30"/></td>
	</tr>
	<tr id="trNroCheque" style="display:none">
		<td class="tituloCampo">Nro Cheque:</td>
		<td colspan="5"> <input type="text" name="txtNroChequeDeposito" id="txtNroChequeDeposito"/></td>
	</tr>
	<tr id="trMonto" style="display:none">
		<td class="tituloCampo">Monto:</td>
		<td colspan="5" align="left">
			<input type="text" name="txtMontoDeposito" id="txtMontoDeposito" onChange="this.value=formato(parsenum(this.value));" onkeypress="return inputnum(event);"/>
			<input type="button" name="btnAgregarMontoDeposito" id="btnAgregarMontoDeposito" value="Agregar" onClick="validarDetalleDeposito();"/>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">
			<table width="100%" border="0" cellpadding="0">
			<tr class="tituloColumna">
				<td >Forma Pago</td>
				<td >Banco</td>
				<td >Nro Cuenta</td>
				<td >Nro Cheque</td>
				<td >Monto</td>
				<td >&nbsp;</td>
			</tr>
			<tr id="trItmPieDeposito"></tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>Saldo:</td>
		<td>
			<input name="txtSaldoDepositoBancario" type="text" id="txtSaldoDepositoBancario" readonly="readonly" style="text-align:right"/>
			<input name="hddSaldoDepositoBancario" type="hidden" id="hddSaldoDepositoBancario" readonly="readonly"/>
		</td>
		<td>&nbsp;</td>
		<td>Total:</td>
		<td><input name="txtTotalDeposito" type="text" id="txtTotalDeposito" readonly="readonly" style="text-align:right"/></td>
	</tr>
	<tr>
		<td colspan="6"><hr/></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td width="54%" colspan="3"><input type="hidden" name="hddObjDetallePagoDeposito" id="hddObjDetallePagoDeposito"/></td>
		<td width="7%" align="right"><input type="button" name="btnGuardarDetalleDeposito" id="btnGuardarDetalleDeposito" value="Guardar" onClick="validarAgregarDeposito();"/></td>
		<td width="7%" align="right"><input type="button" value="Cancelar" onClick="$('divFlotante').style.display='none'; xajax_eliminarPagoDetalleDepositoForzado(xajax.getFormValues('frmDetalleDeposito'))"/></td>
	</tr>
	</table>
	</form>
	
	<table border="0" id="tblListados" style="display:none" width="1050px">
	<tr id="trBuscarCliente">
		<td>
		<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="$('btnBuscarCliente').click(); return false;" style="margin:0">
			<table>
			<tr>
				<td align="right" class="tituloCampo" width="115">Criterio:</td>
				<td><input type="text" id="txtCriterioBusqCliente" name="txtCriterioBusqCliente" onkeyup="$('btnBuscarCliente').click();"/></td>
				<td><input type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));" value="Buscar"/></td>
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
					<input type="button" onclick="$('divFlotante').style.display = 'none';" value="Cancelar">
				</td>
			</tr>
			</table>
		</form>
		</td>
	</tr>
	</table>
</div>

<script>	
xajax_cargarAnticipo(<?php echo $_GET['id']; ?>,<?php echo $_GET['acc']; ?>);

//xajax_validarAperturaCaja();
var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>