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
include("controladores/ac_cj_nota_cargo_por_pagar_form2.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Pago Nota de Débito</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCaja.css"/>
	
	<script type="text/javascript" language="javascript" src="../vehiculos/vehiculos.inc.js"></script>
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
		else if ($('selTipoPago').value == 7){ anticipo();}
		else if ($('selTipoPago').value == 8){ notaCredito();}
		else if ($('selTipoPago').value == 9){ retencion();}
		else if ($('selTipoPago').value == 10){ retencionISLR();}
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
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
	}
	
	function cheques(){
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
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = 'none';
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
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
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
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
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
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
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
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
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
	}
	
	function anticipo(){
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
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = 'none';
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
	}
	
	function notaCredito(){
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
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = 'none';
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
	}
	
	function retencion(){
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
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = '';
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
	}
	
	function retencionISLR(){
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
		$('tdTituloNumeroDocumento').style.display = '';
		$('tdNumeroDocumento').style.display = '';
		
		$('btnAgregarDetDeposito').style.display = 'none';
		$('agregar').style.display = '';
		$('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
	}
	
	function validar(){
		error = false;
		if ($('selTipoPago').value == 1){/*EFECTIVO*/
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true)){
			validarCampo('txtSaldoNotaCargo','t','monto');
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
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('numeroControl','t','') == true)){
				validarCampo('txtSaldoNotaCargo','t','monto');
				validarCampo('montoPago','t','monto');
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
		if (validarCampo('txtSaldoNotaCargo','t','monto') == true
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
			 else {
			validarCampo('txtSaldoNotaCargo','t','monto') == true
				validarCampo('montoPago','t','monto');
				validarCampo('txtFechaDeposito','t','fecha');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','');
				validarCampo('numeroControl','t','');
				alert("Los campos señalados en rojo son requeridos");
			}
		}
		else if ($('selTipoPago').value == 4){/*TRANSFERENCIA*/
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','') == true
			&& validarCampo('numeroControl','t','') == true)){
			validarCampo('txtSaldoNotaCargo','t','monto');
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
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('tarjeta','t','lista') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('numeroControl','t','') == true
			&& validarCampo('porcentajeRetencion','t','numPositivo') == true
			&& validarCampo('montoTotalRetencion','t','numPositivo') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)){
			validarCampo('txtSaldoNotaCargo','t','monto');
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
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('numeroControl','t','') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)){
			validarCampo('txtSaldoNotaCargo','t','monto');
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
		else if ($('selTipoPago').value == 7){/*ANTICIPO*/
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('numeroControl','t','') == true)){
			validarCampo('txtSaldoNotaCargo','t','monto');
				validarCampo('montoPago','t','monto');
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
		else if ($('selTipoPago').value == 8){/*NOTA CREDITO*/
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('numeroControl','t','') == true)){
			validarCampo('txtSaldoNotaCargo','t','monto');
				validarCampo('montoPago','t','monto');
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
		else if ($('selTipoPago').value == 9){/*RETENCION*/
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('numeroControl','t','') == true)){
			validarCampo('txtSaldoNotaCargo','t','monto');
				validarCampo('montoPago','t','monto');
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
		else if ($('selTipoPago').value == 10){/*RETENCION ISLR*/
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('numeroControl','t','') == true)){
			validarCampo('txtSaldoNotaCargo','t','monto');
				validarCampo('montoPago','t','monto');
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
		else {
			if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true
			&& validarCampo('selTipoPago','t','lista') == true)){
				validarCampo('txtSaldoNotaCargo','t','monto');
				validarCampo('selTipoPago','t','lista');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			}
		}
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
	
	function confirmarEliminarPago(pos){
		if(confirm("Desea elmiminar el pago?"))
			xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),pos);
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
		var saldo = $('hddMontoPorPagar').value.replace(/,/gi,'');
		var contador = 1;
		var total = 0;
		var pagos = 0;
		
		obj = $('hddObjDetallePago').value;
		arregloObj = obj.split("|");
			
		while (contador < arregloObj.length){
			monto = $('txtMonto'+arregloObj[contador]).value.replace(/,/gi,'');
			pagos += parseFloat(monto);
			contador++;
		}
		
		total = saldo - pagos;
		
		$('txtMontoPorPagar').value = formato(parsenum(total));
		$('hddMontoFaltaPorPagar').value = formato(parsenum(total));
		$('txtMontoPagadoNotaCargo').value = formato(parsenum(pagos));
		
		if (total == 0){
			$('btnAgregarDetDeposito').disabled = true;
			$('agregar').disabled = true;
		}		
		else{
			$('btnAgregarDetDeposito').disabled = false;
			$('agregar').disabled = false;
		}		
		
		if (total == saldo)
			$('btnGuardarPago').disabled = true;
		else
			$('btnGuardarPago').disabled = false;
	}
	
	function validarGuardar(){
		error = false;
	
		if (!(validarCampo('txtSaldoNotaCargo','t','monto') == true)){
			validarCampo('txtSaldoNotaCargo','t','monto');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			byId('agregar').disabled = true;
			byId('btnGuardarPago').disabled = true;
			byId('btnCancelar').disabled = true;
			xajax_guardarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListaPagos'),xajax.getFormValues('frmDcto'));
		}		
	}
	
	function validarAgregarAnticipoNotaCreditoCheque(){
		var saldo = $('txtSaldoDocumento').value.replace(/,/gi,'');
		var monto = $('txtMontoDocumento').value.replace(/,/gi,'');
		var montoFaltaPorPagar = $('txtMontoPorPagar').value.replace(/,/gi,'');
		
		if (parseFloat(saldo) < parseFloat(monto))
			alert("El monto a pagar no puede ser mayor que el saldo del documento");
		else{
			if (parseFloat(montoFaltaPorPagar) >= parseFloat(monto)){
				if (confirm("Desea cargar el pago?")){
					$('numeroControl').value = $('txtNroDocumento').value;
					$('hddIdAnticipoNotaCreditoCheque').value = $('hddIdDocumento').value;
					$('montoPago').value = $('txtMontoDocumento').value;
					
					$('divFlotante').style.display = 'none';
					$('divFlotante1').style.display = 'none';
					
					xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
				}
			}
			else
				alert("El monto a pagar no puede ser mayor que el saldo de la Nota de Débito")
		}
	}
	
	function calcularMontoTotalTarjetaCredito() {
		if ($('selTipoPago').value == 5){
		$("montoTotalRetencion").value = formato(parsenum(parsenum($("montoPago").value) * parsenum($("porcentajeRetencion").value) / 100));
		$("montoTotalComision").value = formato(parsenum(parsenum($("montoPago").value) * parsenum($("porcentajeComision").value) / 100));
		}else if($('selTipoPago').value){
		$("montoTotalComision").value = formato(parsenum(parsenum($("montoPago").value) * parsenum($("porcentajeComision").value) / 100));
		}
	}
	</script>
</head>

<body>
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
				<table border="0" width="100%">
				<tr>
					<td align="right" class="tituloCampo" width="120">Empresa:</td>
					<td>
						<table cellpadding="0" cellspacing="0">
						<tr>
							<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
							<td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
						</tr>
						</table>
					</td>
					<td align="right" class="tituloCampo" width="120">Fecha Registro:</td>
					<td><input type="text" id="txtFecha" name="txtFecha" size="10" style="text-align:center" readonly="readonly"/></td>
				</tr>
				<tr>
					<td colspan="4">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td valign="top" width="70%">
							<fieldset><legend class="legend">Cliente</legend>
								<table border="0" width="100%">
								<tr>
									<td align="right" class="tituloCampo">Cliente:</td>
									<td colspan="3">
										<table cellpadding="0" cellspacing="0">
										<tr>
											<td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
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
							<fieldset><legend class="legend">Datos de la Nota de Débito</legend>
								<input type="hidden" id="hddIdNotaCargo" name="hddIdNotaCargo"/>
								<table border="0" width="100%">
								<tr>
									<td align="right" class="tituloCampo" width="120">Nro. Nota de Débito:</td>
									<td>
										<input type="text" id="txtNumeroNotaCargo" name="txtNumeroNotaCargo" readonly="readonly" size="20" style="text-align:center"/>
									</td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" width="120">Nro. Control:</td>
									<td align="left"><input type="text" id="txtNumeroControlNotaCargo" name="txtNumeroControlNotaCargo" size="20" style="color:#F00; font-weight:bold; text-align:center;" readonly="readonly"/></td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" width="120">Fecha Vencimiento:</td>
									<td width="120" align="left" ><input type="text" id="txtFechaVencimiento" name="txtFechaVencimiento" size="10" style="text-align:center" readonly="readonly"/></td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" width="120">Módulo:</td>
									<td align="left" id="tdlstModulo"></td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" width="120">Saldo Nota de Débito:</td>
									<td align="left" width="120"><input type="text" id="txtSaldoNotaCargo" name="txtSaldoNotaCargo" size="20" style="text-align:right" onblur="$('txtMontoPorPagar').value = formato(parsenum(this.value)); $('hddMontoFaltaPorPagar').value = formato(parsenum(this.value)); $('hddMontoPorPagar').value = formato(parsenum(this.value)); this.value = formato(parsenum(this.value));" readonly="readonly"/></td>
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
					<tr>
						<td width="164" height="23" class="tituloCampo" align="left">Tipo de Pago:</td>
						<td id="tdTipoPago">
						<div align="justify">
							<select name="selTipoPago" id="selTipoPago" onChange="cambiar()">
								<option>Tipo pago</option>
							</select>
							<script>xajax_cargarTipoPago();</script>
						</div>
						</td>
						<td width="28"></td>
						<td align="right" scope="row" class="tituloCampo" id="tdTituloTipoTarjeta" style="display:none;">Tipo Tarjeta:</td>
						<td align="left" scope="row" id="tdTipoTarjetaCredito" colspan="4" style="display:none;">
							<select id="tarjeta" name="tarjeta">
								<option value="">Seleccione...</option>
							</select>
						</td>
					</tr>
					<tr>
						<td height="23" id="tdEtiquetaBancoOFechaDep" align="left" class="tituloCampo">Banco Cliente:</td>
						<td id="tdBancoCliente" scope="row" align="left">
							<select name="selBancoCliente" id="selBancoCliente">
								<option value="">Seleccione</option>
							</select>
							<script>xajax_cargarBancoCliente("tdBancoCliente","selBancoCliente");</script>
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
						<td width="28"></td>
						<td class="tituloCampo" id="tdTituloPorcentajeRetencion" width="140" style="display:none;">Porcentaje Retenci&oacute;n:</td>
						<td width="75" scope="row" id='tdPorcentajeRetencion' style="display:none">
							<input name="porcentajeRetencion" type="text" id="porcentajeRetencion" size="10" readonly="readonly" style="text-align:right; background-color:#EEEEEE;"/>
						</td>
						<td scope="row" class="tituloCampo" id="tdTituloMontoRetencion" style="display:none;" width="80">Monto:</td>
						<td align="left" scope="row" width="100" id="tdMontoRetencion" style="display:none;">
							<input name="montoTotalRetencion" type="text" id="montoTotalRetencion" style="text-align:right; background-color:#EEEEEE;" size="19" readonly="readonly"/>
						</td>
					</tr>
					<tr>
						<td height="23" class="tituloCampo" align="left" id="tdTituloBancoCompania">Banco Compa&ntilde;ia:</td>
						<td height="23" id="tdBancoCompania" align="left" ><div align="justify">
							<select name="selBancoCompania" id="selBancoCompania">
								<option></option>
							</select>
							<script>xajax_cargarBancoCompania();</script></div></td>
						<td width="28"></td>
						<td class="tituloCampo" id="tdTituloPorcentajeComision" width="140" style="display:none;">Porcentaje Comisi&oacute;n:</td>
						<td width="75" scope="row" id="tdPorcentajeComision" style="display:none;">
							<input name="porcentajeComision" type="text" id="porcentajeComision" size="10" readonly="readonly" style="text-align:right; background-color:#EEEEEE;" value="0.00"/>
						</td>
						<td scope="row" class="tituloCampo" id="tdTituloMontoComision" style="display:none;" width="80">Monto:</td>
						<td align="left" scope="row" width="100" id="tdMontoComision" style="display:none;">
							<input name="montoTotalComision" type="text" id="montoTotalComision" style="text-align:right; background-color:#EEEEEE;" size="19" readonly="readonly" value="0.00"/>
						</td>
					</tr>
					<tr>
						<td height="23" class="tituloCampo" align="left" id="tdTituloNumeroCuenta">Nro. de Cuenta:</td>
						<td colspan="8" id="tdNumeroCuentaTexto"><div align="justify"><strong>
							<input name="numeroCuenta" type="text" id="numeroCuenta" size="30"/></strong></div>
						</td>
						<td colspan="8" id="tdNumeroCuentaSelect" style="display:none"><div align="justify"><strong>
							<select id="selNumeroCuenta" name="selNumeroCuenta">
								<option value="">Seleccione</option>
							</select></strong></div>
						</td>
					</tr>
					<tr>
						<td height="23" class="tituloCampo" align="left" id="tdTituloNumeroDocumento">Nro.:</td>
						<td height="23" colspan="1" id="tdNumeroDocumento"><div align="justify"><strong>
							<input name="numeroControl" type="text" id="numeroControl" size="30"/>
							<input type="hidden" id="hddIdAnticipoNotaCreditoCheque" name="hddIdAnticipoNotaCreditoCheque"/></strong></div>
						</td>
						<td width="28" id="tdImgAgregarFormaAnticipoNotaCreditoCheque">
							<button style="display:none" type="button" id="btnAgregarDetAnticipoNotaCreditoChequeTransferencia" name="btnAgregarDetAnticipoNotaCreditoChequeTransferencia" onClick="$('btnBuscarAnticipoNotaCreditoCheque').click();" title="Buscar Documento"><img src="../img/iconos/find.png" width="16" height="16"/></button>
						</td>
					</tr>
					<tr>
						<td height="23" class="tituloCampo" align="left">Monto:</td>
						<td width="178" height="23"><div align="justify"><strong>
							<input type="text" name="montoPago" id="montoPago" onblur="this.value=formato(parsenum(this.value)); calcularMontoTotalTarjetaCredito();" onkeypress="return validarSoloNumerosConPunto(event);" style="text-align:right" class="inputHabilitado"/></strong>
							<input name="ocultoAgregarPagoAnticipo" type="hidden" id="ocultoAgregarPagoAnticipo" value="1"/></div>
						</td>
						<td width="28" id="tdImgAgregarFormaDeposito">
							<button style="display:none" type="button" id="btnAgregarDetDeposito" name="btnAgregarDetDeposito" onClick="validar();" title="Agregar Detalle del Deposito"><img src="../img/iconos/money_add.png" width="16" height="16"/></button>
						</td>
						<td width="429" height="23" align="left" colspan="6">
							<button type="button" id="agregar" name="agregar" onclick="validar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Pago</td></tr></table></button>
							<input type="hidden" id="hddObjDetallePago" name="hddObjDetallePago"/>
							<input type="hidden" id="hddObjDetalleDeposito" name="hddObjDetalleDeposito"/>
							<input type="hidden" id="hddObjDetalleDepositoFormaPago" name="hddObjDetalleDepositoFormaPago"/>
							<input type="hidden" id="hddObjDetalleDepositoBanco" name="hddObjDetalleDepositoBanco"/>
							<input type="hidden" id="hddObjDetalleDepositoNroCuenta" name="hddObjDetalleDepositoNroCuenta"/>
							<input type="hidden" id="hddObjDetalleDepositoNroCheque" name="hddObjDetalleDepositoNroCheque"/>
							<input type="hidden" id="hddObjDetalleDepositoMonto" name="hddObjDetalleDepositoMonto"/>
							<input type="hidden" id="hddMontoFaltaPorPagar" name="hddMontoFaltaPorPagar"/>
						</td><!-- botones()-->
					</tr>
				</table>
				</fieldset>
				</form>
			</td>
		</tr>
		<tr>
			<td>
				<form id="frmListaPagos" name="frmListaPagos">
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
						<input type="text" name="txtMontoPorPagar" id="txtMontoPorPagar" readonly="readonly" value="0" style="text-align:right;" class="trResaltarTotal3"/>
						<input type="hidden" name="hddMontoPorPagar" id="hddMontoPorPagar"/></strong>
					</td>
					<td colspan="4"><strong>Monto Pagado de la Nota de Débito:
						<input type="text" name="txtMontoPagadoNotaCargo" id="txtMontoPagadoNotaCargo" value="0" readonly="readonly" style="text-align:right" class="trResaltarTotal"/></strong>	
					</td>
				</tr>
				</table>
				</fieldset>
				</form>
			</td>
		</tr>
		<tr align="right">
			<td colspan="8"><hr>
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
				<option value="-1">Seleccione...</option>
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
				<option value="-1">Seleccione...</option>
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
		<td colspan="6"><hr></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td width="54%" colspan="3"><input type="hidden" id="hddObjDetallePagoDeposito" name="hddObjDetallePagoDeposito"/></td>
		<td width="7%" align="right">
			<button type="button" id="btnGuardarDetalleDeposito" name="btnGuardarDetalleDeposito" onclick="validarAgregarDeposito();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
		</td>
		<td width="7%" align="right">
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="$('divFlotante').style.display='none'; xajax_eliminarPagoDetalleDepositoForzado(xajax.getFormValues('frmDetalleDeposito'))" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
	</form>
	
	<table border="0" id="tblListados" style="display:none" width="1050px">
	<tr id="trBuscarAnticipoNotaCreditoCheque">
		<td>
			<form id="frmBuscarAnticipoNotaCreditoCheque" name="frmBuscarAnticipoNotaCreditoCheque" onsubmit="$('btnBuscarAnticipoNotaCreditoCheque').click(); return false;" style="margin:0">
			<table align="right">
			<tr>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioAnticipoNotaCreditoCheque" name="txtCriterioAnticipoNotaCreditoCheque" onkeyup="$('btnBuscarAnticipoNotaCreditoCheque').click();"/></td>
				<td>
					<button type="submit" id="btnBuscarAnticipoNotaCreditoCheque" name="btnBuscarAnticipoNotaCreditoCheque" onclick="xajax_buscarAnticipoNotaCreditoCheque(xajax.getFormValues('frmBuscarAnticipoNotaCreditoCheque'),$('txtIdCliente').value,$('selTipoPago').value,xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListaPagos'));">Buscar</button>
					<button type="button" onclick="document.forms['frmBuscarAnticipoNotaCreditoCheque'].reset(); byId('btnBuscarAnticipoNotaCreditoCheque').click();">Limpiar</button>
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
					<button type="button" id="btnCancelar" name="btnCancelar" onclick="$('divFlotante').style.display = 'none';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
				</td>
			</tr>
			</table>
			</form>
		</td>
	</tr>
	</table>
</div>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
	<form id="frmDetalleAnticipoNotaCreditoCheque" name="frmDetalleAnticipoNotaCreditoCheque" style="margin:0">
	<table border="0" id="tblDetalleAnticipoNotaCreditoCheque" width="290px">
	<tr>
		<td class="tituloCampo" width="120">Nro. Documento:</td>
		<td><input type="text" name="txtNroDocumento" id="txtNroDocumento" size="15"/></td>
	</tr>
	<tr>
		<td class="tituloCampo" width="120">Saldo:</td>
		<td><input type="text" name="txtSaldoDocumento" id="txtSaldoDocumento" readonly="readonly" size="15" style="text-align:right"/></td>
	</tr>
	<tr>
		<td class="tituloCampo" width="120">Monto a Pagar:</td>
		<td><input type="text" name="txtMontoDocumento" id="txtMontoDocumento" onChange="this.value=formato(parsenum(this.value));" onkeypress="return inputnum(event);" size="15" style="text-align:right" class="inputHabilitado"/></td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
            <input type="hidden" id="hddIdDocumento" name="hddIdDocumento"/>
			<button type="button" id="btnAceptarMontoDocumento" name="btnAceptarMontoDocumento" onclick="validarAgregarAnticipoNotaCreditoCheque();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button>
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="$('divFlotante1').style.display='none';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
	</form>
</div>

<script language="javascript">
xajax_cargarDcto(<?php echo $_GET['id'] ?>);

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>