<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_registro_compra_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_registro_compra_form.php");

//MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
//MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Compras - Registro de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <!--<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jquery.1.4.2.js"></script>-->
	<script type="text/javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>

   	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
<!--
	
   -->

    <script>
	/*function comparar(fe1,fe2) {
		dia1 = fe1.substring(0,2);
		d1 = parseInt(dia1,10);
		mes1 = fe1.substring(3,5);
		m1 = parseInt(mes1,10);
		año1 = fe1.substring(6,10);
		a1 = parseInt(año1,10);
		dia2 = fe2.substring(0,2);
		d2 = parseInt(dia2,10);	
		mes2 = fe2.substring(3,5);
		m2 = parseInt(mes2,10);
		año2 = fe2.substring(6,10);
		a2 = parseInt(año2,10);
		
		if (a1 > a2)
			return 1;
		else if (a1 < a2)
			return -1;
		else
			if (m1 > m2)
				return 1;
			else if (m1 < m2)
				return -1;
			else
				if (d1 > d2)
					return 1;
				else if (d1 < d2)
					return -1;
				else
					return 0;
	}*/
	
	function abrirDivFlotante(accin, nomObjeto){
		
		switch(accin){
			case "agregar": 
				//alert(validarencabezadoOrden()); 
				if(validarencabezadoOrden() == true){
					if(validarTotales() == true){
						nomObjeto = "";
						byId('txtTotalFactura').value = "";
						byId('tdFlotanteTitulo8').innerHTML = "Indique el Total de la Factura de Compra";
						nomObjeto = divFlotante8;
					}else{
						xajax_cargaLstTipoArt();
						xajax_listadoArticulos(0,'','',byId('txtIdEmpresa').value);
					}	
				}else{
					alert("Los campos señalados en rojo son requeridos");
					return false;
				}
					break;
					
			case "editar": 
			//tomar los id del impuesto y pasarcelo a la funcion para que genere el listado de impuesto
				byId('tdFlotanteTitulo').innerHTML = "Editar Articulo";
				byId('hddTextAccion').value = "editarArt";
				/*$('#trDatosArticulo').show();
				$('#trBstDatosArticulo').show();*/
				if ($('#trIva').is(':visible')){
					byId('trIva').style.display = 'none';
				}
				byId('txtCantidadArt').readOnly = true; 
				byId('txtCantidadArt').className = 'inputInicial'; 
				xajax_eliminarIva(xajax.getFormValues('frmDatosArticulo'));
					break;
			case "MostrarImpuesto": 
				document.forms['frmLstImpuesto'].reset();
				byId('tdFlotanteTitulo5').innerHTML = "Lista de Impuesto";
				byId('btsAceptarImpuestoPorBLoque').style.display = 'none';
				byId('btsAceptarImpuesto').style.display = '';
				if ($('#divFlotante4').is(':visible')){
					byId('btnListArt').click();
				}
					break;
			case "MostrarImpuestoPorBLoque":
				document.forms['frmLstImpuesto'].reset();
				byId('tdFlotanteTitulo5').innerHTML = "Lista de Impuesto";
				byId('btsAceptarImpuestoPorBLoque').style.display = '';
				byId('btsAceptarImpuesto').style.display = 'none';
					break;
			case "MostrarGastos":
				document.forms['frmLstGasto'].reset();
				byId('btnLimpiarGastos').click();
				byId('tdFlotanteTitulo6').innerHTML = "Lista de Gastos";
					break;
			case "Mostrarsolicitud":
				if(validarTotales() == true){
					nomObjeto = "";
					byId('txtTotalFactura').value = "";
					byId('tdFlotanteTitulo8').innerHTML = "Indique el Total de la Factura de Compra";
					nomObjeto = divFlotante8;
				}else{
					byId('tdFlotanteTitulo7').innerHTML = "Buscar Solicitud";
				}	
					break;

			default:
				xajax_listProveedores(0,'','','');
					break;
			}
		openImg(nomObjeto);
	}
	
	function calcularArtDescuento(){
		var a = byId('rbtPorcDescuentoArt').checked;
		if(a == true){ // si coloca el %
			var total =	(byId('txtCostoArt').value * byId('txtCantidadArt').value);
			var totalDeDesc = total * (byId('txtPorcDescuentoArt').value / 100);
				byId('txtMontoDescuentoArt').value = parseFloat(totalDeDesc).toFixed(2);
		} else { // si coloca el monto
			var total =	(byId('txtCostoArt').value * byId('txtCantidadArt').value);
			var porcentaje = (byId('txtMontoDescuentoArt').value * 100) / total;
				byId('txtPorcDescuentoArt').value = parseFloat(porcentaje).toFixed(2);
				
		}	
	}
	
	function cargaDatosArt(idArt, accion, idObj){
		
		openImg(idObj); 
		
		xajax_asignarArticulo(null,false,accion,idArt);
	
		if(accion == 'AgregarListArt'){
			byId('txtCantidadArt').readOnly = false; 
			byId('txtCantidadArt').className = 'inputHabilitado';
		}
		
	}
	
	function eliminarItems(Item){
		if(Item == "Iva"){
			if (confirm('¿Seguro Desea eliminar este Item?') == true){
				switch(Item){ 
					case "Iva": xajax_eliminarIva(xajax.getFormValues('frmDatosArticulo'),'clickBoton'); break;
				}
			}else{
				fila = document.getElementById('trItemArtIva:0');
				padre = fila.parentNode;
				padre.removeChild(fila);		
			}
		}
	}
	
	function habilitar(idObj, nameObj, accion){
		switch(nameObj){
			case "rbtTipoArt": //radio Tipo
				if(idObj == "rbtTipoArtReposicion"){
					byId('txtIdClienteArt').value = ''; 
					byId('txtNombreClienteArt').value = ''; 
					byId('txtIdClienteArt').disabled = true;
					byId('txtNombreClienteArt').disabled = true;
					byId('ButtInsertClienteArt').style.display = 'none';
				} else {
					byId('txtIdClienteArt').value = '';
					byId('txtNombreClienteArt').value = ''; 
					byId('txtIdClienteArt').disabled = '';
					byId('txtNombreClienteArt').disabled = ''; 
					byId('ButtInsertClienteArt').style.display = '';
					xajax_listaCliente(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
				}
					break;
			case "rbtDescuento": //radio descuento
				if(idObj == "rbtPorcDescuentoArt"){
					byId('txtPorcDescuentoArt').readOnly = false; 
					byId('txtPorcDescuentoArt').className = 'inputHabilitado'; 
					byId('txtMontoDescuentoArt').readOnly = true; 
					byId('txtMontoDescuentoArt').className = 'inputInicial'; 
						if (byId('txtPorcDescuentoArt').value != 0) {
							byId('txtMontoDescuentoArt').value = '0.00';
							byId('txtPorcDescuentoArt').value = '0.00'; 
						}
				}else {
					byId('txtMontoDescuentoArt').readOnly = false;
					byId('txtMontoDescuentoArt').className = 'inputHabilitado';
					byId('txtPorcDescuentoArt').readOnly = true; 
					byId('txtPorcDescuentoArt').className = 'inputInicial';
						if (byId('txtMontoDescuentoArt').value != 0) {
							byId('txtPorcDescuentoArt').value = '0.00'; 
							byId('txtMontoDescuentoArt').value = '0.00'; 
						}
				}
					break;
			case "btnAgregarProv": //para el boton de proveedores
				if(accion == "hide"){
					byId('btnAgregarProv').style.display = 'none';
					byId('btnAgregarProv').disabled = true;
				} else{
					byId('btnAgregarProv').style.display = 'block';
					byId('btnAgregarProv').disabled = false;
				}
					break;
			case "btnAgregarArt": 
				if(accion == "hide"){
					byId('btnAgregarArt').style.display = 'none';
					byId('btnAgregarArt').disabled = true;
				} else{
					byId('btnAgregarArt').style.display = 'block';
					byId('btnAgregarArt').disabled = false;
				}
					break;
			case "MostarOrden": 
				if(accion == "hide"){
					if ($('#tdListOrdenes').is(':visible')){
						byId('tdListOrdenes').style.display = 'none';
					}
					if ($('#tdListItemsOrden').is(':visible')){
						byId('tdListItemsOrden').style.display = 'none';
					}	
					document.forms['frmBuscarOrden'].reset();				
				}else{
					byId(idObj).style.display = '';
				}
					break;
		}
	}



	function seleccionarTodosCheckbox(idObj,clase){
		if ($('#'+idObj).get(0).checked == true){
			$('.'+clase).each(function() { 
                this.checked = true;    
            });
		} else {
			$('.'+clase).each(function() { 
                this.checked = false;    
            });
		}
	}
	
	/*falta validar los art ingresados a la solicitud*/
	function validarFormArticulo(accion) {

		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadRecibArt','t','cantidad') == true
		&& validarCampo('txtCostoArt','t','monto') == true
		//&& validarCampo('lstIvaArt','t','listaExceptCero') == true
		&& validarCampo('txtCantidadArt','t','') == true) {
			
			if (byId('rbtTipoArtCliente').checked == true 
				&& validarCampo('txtNombreClienteArt','t','') != true
				&& validarCampo('txtIdClienteArt','t','') != true ) {
					
				alert("Los campos señalados en rojo son requeridos");
				return false;
				
			} else if (parseInt(byId('txtCantidadRecibArt').value) > parseInt(byId('txtCantidadArt').value)) {
				
				alert("La cantidad recibida no puede ser mayor a la pedida");
				return false;
			} else {
				var accion = byId('hddTextAccion').value;

				 switch(accion){ //
					case "AgregarListArt": 
					xajax_validarArt(xajax.getFormValues('frmListaArticulo'),xajax.getFormValues('frmDatosArticulo'));
					//xajax_AgregarArticulo(xajax.getFormValues('frmDatosArticulo'),xajax.getFormValues('frmListaArticulo')); break;	
					case "editarArt":
					 xajax_editarArticulo(xajax.getFormValues('frmDatosArticulo')); 
						break;	 
				}
			}

		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadRecibArt','t','cantidad');
			validarCampo('txtCostoArt','t','monto');
			//validarCampo('lstIvaArt','t','listaExceptCero');
			validarCampo('txtCantidadArt','t','');
			
			if (byId('rbtTipoArtCliente').checked == true){
				validarCampo('txtNombreClienteArt','t','');
				validarCampo('txtIdClienteArt','t','');
			}
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormOrden() {
		error = false;
		if (!(validarCampo('txtFechaRegistroCompra','t','') == true
		&& validarCampo('txtNumeroFacturaProveedor','t','') == true
		&& validarCampo('txtNumeroControl','t','numeroControl') == true
		&& validarCampo('txtFechaProveedor','t','fecha') == true
		&& validarCampo('lstTipoClave','t','lista') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('txtNombreProv','t','') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('txtTotalOrdenValidar','t','monto') == true
		&& validarCampo('lstRetencionImpuesto','t','listaExceptCero') == true)) {
			validarCampo('txtFechaRegistroCompra','t','');
			validarCampo('txtNumeroFacturaProveedor','t','');
			validarCampo('txtNumeroControl','t','numeroControl');
			validarCampo('txtFechaProveedor','t','fecha');
			validarCampo('lstTipoClave','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtIdProv','t','');
			validarCampo('txtNombreProv','t','');
			validarCampo('txtDescuento','t','numPositivo');
			validarCampo('txtTotalOrdenValidar','t','monto');
			validarCampo('lstRetencionImpuesto','t','listaExceptCero');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			/*if ((comparar(byId('txtFechaProveedor').value, byId('txtFechaPedido').value) > -1) && (comparar(byId('txtFechaProveedor').value, byId('txtFechaOrden').value) > -1) && (comparar(byId('txtFechaProveedor').value, byId('txtFechaRegistroCompra').value) < 1)) {*/
				if (confirm('¿Seguro Desea Registrar La Compra?') == true) {
					byId('btnGuardar').disabled = 'disabled';
					byId('btnCancelar').disabled = 'disabled';
					
					xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
				}
			/*} else {
				alert("La fecha de la factura del proveedor no es válida");
				return false;
			}*/
		}
	}
	
	function validarencabezadoOrden() {
		if (validarCampo('txtNumeroFacturaProveedor','t','') == true
			&& validarCampo('txtNumeroControl','t','numeroControl') == true
			&& validarCampo('txtFechaProveedor','t','fecha') == true
			&& validarCampo('lstTipoClave','t','lista') == true
			&& validarCampo('lstClaveMovimiento','t','lista') == true
			&& validarCampo('txtIdProv','t','') == true
			&& validarCampo('txtNombreProv','t','') == true
		) {
				byId('tdFlotanteTitulo').innerHTML = "Agregar Articulo";
				document.forms['frmBuscarArt'].reset(); 
					if ($('#trIva').is(':visible')){
						byId('trIva').style.display ='none';
					}
				
				error = true;	

		} else {
			validarCampo('txtNumeroFacturaProveedor','t','');
			validarCampo('txtNumeroControl','t','numeroControl');
			validarCampo('txtFechaProveedor','t','fecha');
			validarCampo('lstTipoClave','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtIdProv','t','');
			validarCampo('txtNombreProv','t','');

			error = false;
		}
		return error;
	}
	
	function validarFormAlmacen() {
		if (validarCampo('txtCodigoArticulo','t','') == true
		&& validarCampo('txtArticulo','t','') == true
		&& validarCampo('lstEmpresa','t','lista') == true
		&& validarCampo('lstAlmacenAct','t','lista') == true
		&& validarCampo('lstCalleAct','t','lista') == true
		&& validarCampo('lstEstanteAct','t','lista') == true
		&& validarCampo('lstTramoAct','t','lista') == true
		&& validarCampo('lstCasillaAct','t','lista') == true
		) {
			if (confirm('Desea realizar la distribución del Artículo a este Almacen?')) {
				xajax_asignarAlmacen(xajax.getFormValues('frmAlmacen'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
			}
		} else {
			validarCampo('txtCodigoArticulo','t','');
			validarCampo('txtArticulo','t','');
			validarCampo('lstEmpresa','t','lista');
			validarCampo('lstAlmacenAct','t','lista');
			validarCampo('lstCalleAct','t','lista');
			validarCampo('lstEstanteAct','t','lista');
			validarCampo('lstTramoAct','t','lista');
			validarCampo('lstCasillaAct','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmTotalFactura(){
		if(validarCampo('txtTotalFactura','t','') == true){
			var totalFacturaUsuario = byId('txtTotalFactura').value;
			byId('txtTotalOrdenValidar').value = totalFacturaUsuario;
			byId('btnCancelarTotalFactura').click();
		}else{
			validarCampo('txtTotalFactura','t','');
			
			alert("Debe indicar el total de factura");
		}	
	}
	
	
	function validarTotales(){
		byId('txtTotalOrdenValidar').value = "";
		if(byId('txtTotalOrdenValidar').value == "" || (byId('txtTotalOrdenValidar').value != byId('txtTotalOrden').value)){		 
			abrirFormTotal = true; //abre formulario totales
		}else{
			abrirFormTotal = false; //no abre formulario totales
		}
		return abrirFormTotal;		
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_compras.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
        <table border="0" width="100%">
            <tr><td class="tituloPaginaCompras">Registro de Compra</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td align="left"><!--FORMULARIO EN CABEZADO-->
                    <form id="frmDcto" name="frmDcto" style="margin:0"> 
                        <table border="0" width="100%">
                            <tr align="left">
                                <td colspan="4"></td>
                                <td align="right" class="tituloCampo">Id Reg. Compra:</td>
                                <td><input type="text" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="20" style="text-align:center"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" width="12%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                                <td align="left" colspan="3">
                                    <input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/>
                                    <input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/>
                                </td>
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha:</td>
                                <td>
                                    <div style="float:left">
                                        <input type="text" id="txtFechaRegistroCompra" name="txtFechaRegistroCompra" readonly="readonly" size="10" style="text-align:center"/>
                                    </div>
                                </td>
                            </tr>
                            <tr align="left">
                           	 	<td colspan="2" rowspan="5" valign="top">
                                <fieldset>
                                <legend class="legend">Proveedor </legend>
                                    <table border="0" width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Proveedor:</td>
                                            <td colspan="3">
                                                <table cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td>
                                                        	<input type="text" id="txtIdProv" name="txtIdProv" readonly="readonly" size="6" style="text-align:right"/></td>
                                                        <td>
                                							<a class="modalImg" id="alistProveed" rel="#divFlotante3" onclick="abrirDivFlotante('',this);">
                                                                <button type="button" id="btnAgregarProv" name="btnAgregarProv" style="cursor:pointer" title="Agregar Proveedor">
                                                                    <table align="center" cellpadding="0" cellspacing="0">
                                                                        <tr>
                                                                            <td>&nbsp;</td>
                                                                            <td><img src="../img/cita_add.png"/></td>
                                                                            <td>&nbsp;</td>
                                                                        </tr>
                                                                    </table>
                                                                </button>
                                                            </a>
                                                        </td>
                                                    <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" rowspan="3" width="20%">Dirección:</td>
                                            <td rowspan="3" width="38%"><textarea id="txtDireccionProv" name="txtDireccionProv" cols="28" readonly="readonly" rows="3"></textarea></td>
                                            <td align="right" class="tituloCampo" width="20%">C.I. / R.I.F.:</td>
                                            <td width="22%"><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Teléfono:</td>
                                            <td><input type="text" id="txtTelefonosProv" name="txtTelefonosProv" readonly="readonly" size="12" style="text-align:center"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Días Crédito:</td>
                                            <td><input type="text" id="txtDiasCredito" name="txtDiasCredito" readonly="readonly" size="12" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Email:</td>
                                            <td colspan="3"><input type="text" id="txtEmailContactoProv" name="txtEmailContactoProv" readonly="readonly" size="26"/></td>
                                        </tr>
                                    </table>
                                </fieldset>
                                </td>
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Factura Prov.:</td>
                                <td><input type="text" id="txtNumeroFacturaProveedor" name="txtNumeroFacturaProveedor" class="inputHabilitado" size="20" style="text-align:center;"/></td>
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control Prov.:</td>
                                <td>
                                    <div style="float:left">
                                        <input type="text" id="txtNumeroControl" name="txtNumeroControl" class="inputHabilitado" size="20" style="text-align:center"/>&nbsp;
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/ico_pregunta.gif" title="Formato Ej.: 00-000000 / Máquinas Fiscales"/>
                                    </div>
                                </td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Factura Prov.:</td>
                                <td><input type="text" id="txtFechaProveedor" name="txtFechaProveedor" class="inputHabilitado" onblur="xajax_asignarFechaRegistro(xajax.getFormValues('frmDcto'))" size="10" style="text-align:center"/></td>
                        	</tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                                <td id="tdlstTipoClave">
                                    <select id="lstTipoClave" name="lstTipoClave" >
                                        <option value="-1">[ Seleccione ]</option>
                                        <!--<option value="1" selected="selected">COMPRA</option>
                                        <option value="2">ENTRADA</option>onchange="selectedOption(this.id,1); xajax_cargaLstClaveMovimiento(this.value)"-->
                                    </select>
                                </td>
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                                <td id="tdlstClaveMovimiento">
                                    <select id="lstClaveMovimiento" name="lstClaveMovimiento" class="inputHabilitado">
                                        <option value="-1">[ Seleccione ]</option>
                                    </select>
                                </td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                                <td>
                                    <input id="rbtTipoPagoCredito" name="rbtTipoPago" type="radio" value="0" checked="checked"/>Crédito
                                    <input id="rbtTipoPagoContado" name="rbtTipoPago" type="radio" value="1"/>Contado
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
            <tr>
                <td> <!--TABLAS QUE CONTIEN LOS BOTONES DE AGREGAR Y QUITAR ART-->
                    <table align="left">
                        <tr>
                            <td><div id="divBtnAgregar"></div></td>
                            <td>
                                <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));" style="cursor:default" title="Eliminar Artículo">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/delete.png"/></td>
                                            <td>&nbsp;</td>
                                            <td>Quitar</td>
                                        </tr>
                                    </table>
                                </button>
                            </td>
                             <td>
                             	<a class="modalImg" id="AgregarImpuArt" onclick="abrirDivFlotante('MostrarImpuestoPorBLoque',this);" rel="#divFlotante5">
                                <button type="button" id="btnImpuestoArt" name="btnImpuestoArt" style="cursor:default" title="Agregar Impuesto">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/text_signature.png"/></td>
                                            <td>&nbsp;</td>
                                            <td>Impuesto</td>
                                        </tr>
                                    </table>
                                </button>
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td><!--FORMULARIO QUE CARGA EL LISTADO DE LOS ARTICULO DEL REGISTRO-->
                    <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0"> 
                        <table border="0" width="100%" id="tableItmDoc">
                            <tr align="center" class="tituloColumna">
                                <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,1);"/></td>
                                <td width="4%">Nro.</td>
                                <td></td>
                                <td style="display:none">Ubic.</td> <!---->
                                <td width="14%">Código</td>
                                <td width="50%">Descripción</td>
                                <td width="4%">Ped.</td>
                                <td width="4%">Recib.</td>
                                <td width="4%">Pend.</td>
                                <td width="8%">Costo Unit.</td>
                                <td width="4%">% Impuesto</td>
                                <td width="8%">Total</td>
                            </tr>
                        <tr id="trItmPie"></tr>
                        </table>
                    </form>
                </td>
            </tr>
            <tr>
                <td align="right"><!--FORMULARIO DE LOS TOTALES TOTAL DE GASTO TOTAL DE IMPUESTO-->
                    <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0"> 
                    <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                        <table border="0" width="100%">
                            <tr>
                                <td align="right" id="tdGastos" valign="top" width="50%"> <!--LOS GASTO DE LA FACTURA-->
                                	<fieldset>
                                    	<legend class="legend">Gastos</legend>
                                    <table width="100%" border="0">
                                    	<tr>
                                        <td colspan="6"><!--BOTON AGREGAR GASTOS-->
                                        	<a class="modalImg" id="AgregarGastos" rel="#divFlotante6" onclick="abrirDivFlotante('MostrarGastos',this);">
                                            <button title="Agregar Gastos" type="button">
                                                <table cellspacing="0" cellpadding="0" align="center">
                                                    <tr>
                                                        <td>&nbsp;</td>
                                                        <td><img src="../img/iconos/add.png"></td>
                                                        <td>&nbsp;</td>
                                                        <td>Agregar</td>
                                                    </tr>
                                                </table>
                                            </button>
                                            </a>
                                        <!--BOTON DE QUITAR GASTOS-->
                                            <button title="Quitar Gastos" onclick="xajax_eliminarItems('Gasto',xajax.getFormValues('frmTotalDcto'))" name="btnQuitarGasto" id="btnQuitarGasto" type="button">
                                                <table cellspacing="0" cellpadding="0" align="center">
                                                    <tr>
                                                        <td>&nbsp;</td>
                                                        <td><img src="../img/iconos/delete.png"></td>
                                                        <td>&nbsp;</td>
                                                        <td>Quitar</td>
                                                    </tr>
                                                </table>
                                            </button>
                                        </td>
                                        </tr>
                                        <tr class="tituloColumna" align="center">
                                            <td><input id="checkGastoItemFactura" type="checkbox" onclick="seleccionarTodosCheckbox('checkGastoItemFactura','checkItemClaseGasto');"></td>
                                            <td>Descripcion Gasto</td>
                                            <td>% Gasto</td>
                                            <td>Monto Gasto</td>
                                            <td>Impuesto</td>
                                            <td></td>
                                        </tr>
                                        <tr id="trItmPieGastos"></tr>
                                        <tr class="trResaltarTotal">
                                        	<td colspan="3" class="tituloCampo" align="right">Total Gasto</td>
                                            <td> 
                                            	<input id="txtTotalGasto" class="inputSinFondo" type="text" style="text-align:right" readonly="readonly" name="txtTotalGasto">
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                        <tr>
                                            <td class="divMsjInfo2" colspan="6">
                                                <table width="100%" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                                                        <td align="center">
                                                            <table>
                                                                <tr>
                                                                    <td><img src="../img/iconos/accept.png"></td>
                                                                    <td>Gastos que llevan impuesto</td>
                                                                    <td>&nbsp;</td>
                                                                    <td><img src="../img/iconos/stop.png"></td>
                                                                    <td>No afecta cuenta por pagar</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    </fieldset>
                                </td>
                                <td width="50%">
                                    <table border="0" width="100%">
                                        <tr align="right">
                                            <td class="tituloCampo" width="36%">Sub-Total:</td>
                                            <td width="24%"></td>
                                            <td width="13%"></td>
                                            <td id="tdSubTotalMoneda" width="5%"></td>
                                            <td width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="right">
                                            <td class="tituloCampo">Descuento:</td>
                                            <td></td>
                                            <td nowrap="nowrap">
                                            <input type="text" id="txtDescuento" name="txtDescuento" class="inputHabilitado" size="6" style="text-align:right" 
                                                onfocus="if (byId('txtDescuento').value <= 0){ byId('txtDescuento').select(); }" 
                                                onkeypress="return validarSoloNumerosReales(event);" 
                                                onkeyup="xajax_calcularDcto(xajax.getFormValues('frmDcto'),
                                                            xajax.getFormValues('frmListaArticulo'), 
                                                            xajax.getFormValues('frmTotalDcto'))"/>%
                                            </td>
                                            <td id="tdDescuentoMoneda"></td>
                                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="right">
                                            <td class="tituloCampo">Gastos Con Impuesto:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdGastoConIvaMoneda"></td>
                                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                    <!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
                                        <tr align="right" id="trGastosSinIva">
                                            <td class="tituloCampo">Gastos Sin Impuesto:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdGastoSinIvaMoneda"></td>
                                            <td><input type="text" id="txtGastosSinIva" name="txtGastosSinIva" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5"><hr></td>
                                        </tr>
                                        <tr align="right" id="trNetoOrden">
                                            <td class="tituloCampo">Neto Orden:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdTotalRegistroMoneda"></td>
                                            <td>
                                            	<input type="hidden" id="txtTotalOrden" name="txtTotalOrden" readonly="readonly" size="16" style="text-align:right"/>
                                                <input type="text" id="txtTotalOrdenValidar" name="txtTotalOrdenValidar" readonly="readonly" size="16" style="text-align:right"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5"><hr></td>
                                        </tr>
                                        <tr align="right">
                                            <td class="tituloCampo">Exento:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdExentoMoneda"></td>
                                            <td><input type="text" id="txtTotalExento" name="txtTotalExento" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="right">
                                            <td class="tituloCampo">Exonerado:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdExoneradoMoneda"></td>
                                            <td><input type="text" id="txtTotalExonerado" name="txtTotalExonerado" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="right" id="trRetencionIva" style="display:none">
                                            <td class="tituloCampo">Retención de Impuesto:</td>
                                            <td colspan="4">
                                                <table border="0" width="100%">
                                                    <tr>
                                                        <td id="tdlstRetencionImpuesto"></td>
                                                        <td>
                                                            <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                                                            <tr>
                                                                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                                                <td align="center">Usted es Contribuyente Especial</td>
                                                            </tr>
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
                                <td colspan="2">
                                    <table border="0" width="100%">
                                        <tr>
                                            <td valign="top" width="50%">
                                                <fieldset><legend class="legend">Datos del Pedido</legend>
                                                    <table border="0" width="100%">
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo">Id Pedido:</td>
                                                            <td><input type="text" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="18"/></td>
                                                            <td align="right" class="tituloCampo">Fecha:</td>
                                                            <td><input type="text" id="txtFechaPedido" name="txtFechaPedido" readonly="readonly" size="18"/></td>
                                                        </tr>
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo" width="24%">Nro. Pedido Propio:</td>
                                                            <td width="26%"><input type="text" id="txtNumeroPedidoPropio" name="txtNumeroPedidoPropio" readonly="readonly" size="18"/></td>
                                                            <td align="right" class="tituloCampo" width="24%">Nro. Referencia:</td>
                                                            <td width="26%"><input type="text" id="txtNumeroReferencia" name="txtNumeroReferencia" readonly="readonly" size="18"/></td>
                                                        </tr>
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo">Empleado:</td>
                                                            <td><input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="18"/></td>
                                                        </tr>
                                                    </table>
                                                </fieldset>
                                            </td>
                                            <td valign="top" width="50%">
                                                <fieldset><legend class="legend">Datos de la Orden</legend>
                                                    <table border="0" width="100%">
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo" width="24%">Id Orden Compra:</td>
                                                            <td width="26%"><input type="text" id="txtIdOrdenCompra" name="txtIdOrdenCompra" readonly="readonly" size="16"/></td>
                                                            <td align="right" class="tituloCampo" width="24%">Fecha:</td>
                                                            <td width="26%">
                                                            <div style="float:left">
                                                                <input type="text" id="txtFechaOrden" name="txtFechaOrden" readonly="readonly" size="10"/>
                                                            </div>
                                                            </td>
                                                        </tr>
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo">Observaciones:</td>
                                                            <td colspan="3"><textarea cols="30" id="txtObservacionFactura" name="txtObservacionFactura" rows="2"></textarea></td>
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
                <td align="right"><hr><!--BOTONES PARA GUARDAR O CANCELAR EL REGISTRO-->
                    <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormOrden();" style="cursor:default">
                        <table align="center" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/ico_save.png"/></td>
                                <td>&nbsp;</td>
                                <td>Guardar</td>
                            </tr>
                        </table>
                    </button>
                    <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('ga_registro_compra_list.php','_self');" style="cursor:default">
                        <table align="center" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/ico_error.gif"/></td>
                                <td>&nbsp;</td>
                                <td>Cancelar</td>
                            </tr>
                        </table>
                    </button>
                </td>
            </tr>
        </table>
    </div>
	
    <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>

<!--PARA EDITAR O AGREGAR LOS DATOS DEL ARTICULO-->
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTitulo" class="handle"> <!--CONTIENE EL ICONO DE CERRAR Y EL TITULO-->
        <table>
            <tr>
                <td id="tdFlotanteTitulo" width="100%" align="left"></td>
                <td><img src="../img/iconos/ico_delete.png" onclick="byId('btnCancelarDatosArticulo').click();"/></td>
            </tr>
        </table>
    </div>
<!--FORMULARIO PARA EDITAR O AGREGAR EL ARTICULO-->
    <form id="frmDatosArticulo" name="frmDatosArticulo" style="margin:0" onsubmit="return false;">
    <input type="hidden" id="hddNumeroArt" name="hddNumeroArt" readonly="readonly"/>
        <table border="0" id="tblArticulo" width="960" style="">
            <tr id="trDatosArticulo" ><!--style="display:none"-->
                <td>
                    <fieldset><!--CARGA LOS DATOS DEL ART-->
                        <legend class="legend">Datos de Articulo</legend>
                        <table border="0" width="100%"> 
                            <tr align="left">
                                <td align="right" class="tituloCampo">	
                                    <span class="textoRojoNegrita">*</span>Código:	
                                </td>
                                <td>
                                    <input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/>
                                    <input type="hidden" id="hddIdArt" name="hddIdArt" readonly="readonly"/>
                                </td>
                                <td class="tituloCampo" align="center">Descripcion del Articulo</td>
                                <td align="right" class="tituloCampo">Fecha Ult. Compra:</td>
                                <td><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" readonly="readonly" size="10" style="text-align:center"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Sección:</td>
                                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" readonly="readonly" size="38"/></td>
                                <td rowspan="2" valign="top" align="center"><textarea id="txtDescripcionArt" name="txtDescripcionArt"  cols="50" rows="3" readonly="readonly"></textarea></td>
                                <td align="right" class="tituloCampo">Fecha Ult. Venta:</td>
                                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" readonly="readonly" size="10" style="text-align:center"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Tipo de Pieza:</td>
                                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"/></td>
                                <td align="right" class="tituloCampo">Disponible:</td>
                                <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right"/></td>
                            </tr>
                        </table>
                    </fieldset>
                        <table width="100%" border="0"><!--TABLA PARA EDITAR EL PEDIDO DEL ART-->
                          <tr>
                            <td valign="top">
                                <table border="0" width="100%"> 
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="11%"><span class="textoRojoNegrita">*</span>Cantidad Pedida:</td>
                                            <td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="6" onkeypress="return validarSoloNumeros(event);" readonly="readonly" size="12" style="text-align:right"/></td>
                                            <td align="right" class="tituloCampo" width="13%"><span class="textoRojoNegrita">*</span>Cantidad Recibida:</td> <!--txtCantidadRecibArt-->
                                            <td width="15%" colspan="2"><input type="text" id="txtCantidadRecibArt" name="txtCantidadRecibArt" onkeyDown="" class="inputHabilitado" size="10" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Costo:</td>
                                            <td ><input type="text" id="txtCostoArt" name="txtCostoArt" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" class="inputHabilitado" size="10" style="text-align:right"/></td>
                                            <td align="right" class="tituloCampo">Descuento:</td>
                                            <td width="18%">
                                                <input type="radio" id="rbtPorcDescuentoArt" name="rbtDescuento" checked="checked" onclick="habilitar('rbtPorcDescuentoArt','rbtDescuento')" value="0">
                                                <input type="text" id="txtPorcDescuentoArt" name="txtPorcDescuentoArt" 
                                                onkeypress="return validarSoloNumerosReales(event);" onkeyup="calcularArtDescuento();"
                                                class="inputHabilitado" size="10" style="text-align:right"/>%
                                            </td>
                                            <td colspan="2">
                                                <input type="radio" id="rbtMontoDescuentoArt" name="rbtDescuento" onclick="habilitar('rbtMontoDescuentoArt','rbtDescuento')" value="1">
                                                <input type="text" id="txtMontoDescuentoArt" name="txtMontoDescuentoArt" onkeyup="calcularArtDescuento();"
                                                onkeypress="return validarSoloNumerosReales(event);" 
                                                size="10" style="text-align:right"/>
                                            </td>
                                            <!--<td align="right" class="tituloCampo" >
                                                <span class="textoRojoNegrita">*</span>% Impuesto:
                                            </td>
                                            <td id="tdlstIvaArt"></td>-->
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Tipo:</td>
                                            <td width="25%">
                                                <input id="rbtTipoArtReposicion" name="rbtTipoArt" onclick="habilitar('rbtTipoArtReposicion','rbtTipoArt');" type="radio" value="0" checked="checked"/> Reposicion
                                                &nbsp;&nbsp;
                                                <input id="rbtTipoArtCliente" name="rbtTipoArt" onclick="habilitar('rbtTipoArtCliente','rbtTipoArt');" type="radio" value="1" /> Cliente
                                            </td>
                                            <td align="right" class="tituloCampo">Nombre:</td>
                                            <td colspan="4">
                                                <table cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td><input id="txtIdClienteArt" type="text" size="8" readonly="readonly" name="txtIdClienteArt"></td>
                                                        <td>
                                                            <button id="ButtInsertClienteArt" name="ButtInsertClienteArt" class="modalImg" onclick="openImg(divFlotante2)" style="display:none">
                                                                <img src="../img/iconos/help.png">
                                                            </button>
                                                        </td>
                                                        <td><input id="txtNombreClienteArt" type="text" size="30"  name="txtNombreClienteArt"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                            </td>
                            <td>
                                <fieldset>
                                <legend class="legend">% Impuesto</legend>
                                    <table width="100%" cellpadding="0" cellspacing="0" align="center" border="0">
                                        <tr>
                                            <td  align="left" colspan="2"> <!--BOTON AGREGAR IMPUESTO-->
                                                <a class="modalImg" id="AgregarIpuesto" rel="#divFlotante5" onclick="abrirDivFlotante('MostrarImpuesto', this)">
                                                    <button id="btnAgregarImpuesto" name="btnAgregarImpuesto" type="button" title="Agregar Impuesto">
                                                        <table cellspacing="0" cellpadding="0" align="center">
                                                            <tr>
                                                                <td>&nbsp;</td>
                                                                <td><img src="../img/iconos/add.png"></td>
                                                                <td>&nbsp;</td>
                                                                <td>Agregar</td>
                                                            </tr>
                                                        </table>
                                                    </button>
                                                </a><!--/**/-->
                                                <button name="btnQuitarImpuesto" id="btnQuitarImpuesto" onclick="xajax_eliminarItems('Impuesto',xajax.getFormValues('frmDatosArticulo'))" type="button" title="Quitar Impuesto">
                                                    <table cellspacing="0" cellpadding="0" align="center">
                                                        <tr>
                                                            <td>&nbsp;</td>
                                                            <td><img src="../img/iconos/delete.png"></td>
                                                            <td>&nbsp;</td>
                                                            <td>Quitar</td>
                                                        </tr>
                                                    </table>
                                                </button>
                                            </td>
                                        </tr>
                                    <tr>
                                        <td colspan="2"><!--TABALA DONDE SE AGRAGAN LOS IMPUESTOS-->
                                            <table border="0" id="tblIva" width="100%">                                       	
                                                <tr class="tituloColumna" align="center">
                                                    <td width="10%" align="center">
                                                    <input type="checkbox" id="checkImpuesto" onclick="seleccionarTodosCheckbox('checkImpuesto','checkItemClaseImpuesto');"/>
                                                    </td>
                                                    <td width="13%">Id</td>
                                                    <td>Impuesto</td>
                                                </tr>
                                                <!--<tr id="trItemArtIva"></tr>-->
                                            </table> 
                                        </td>                                    
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo" width="30%">Total Impuesto:</td>
                                        <td><input type="text" id="textTotaIva" name="textTotaIva" readonly="readonly" class="inputSinFondo" value=""/> </td>
                                    </tr>
                                    </table>
                                </fieldset>
                            </td>
                          </tr>
                        </table>

                    
                    
                </td>
            </tr>
            <tr > <!--MUESTRA LA TABLA DE IMPUESTO-->
            	<td id="trIva" style="display:none">
                	<!--<fieldset><legend ></legend>-->
                    <table width="100%" border="0">
                    	<caption id="capTituloTable" class="legend"></caption>
                        <tr>
                            <td id=""></td>
                        </tr>
                        <!--<tr>
                            <td align="right">
                                <hr />
                                <button id="btsCerraImpuesto" name="btsCerraImpuesto" class="close">Cerrar</button>
                            </td>
                        </tr>-->
                    </table>
                    <!--</fieldset>-->
                </td>
            </tr>
            <tr id="trBstDatosArticulo"> <!--BOTONES Y CAMPOS PARA GUDAR O CALCELAR-->
                <td align="right"><hr>
                    <input type="hidden" id="hddTextIdArtAsigando" name="hddTextIdArtAsigando" />
                    <input type="hidden" id="hddTextAccion" name="hddTextAccion" />
                    <button type="button" id="btnGuardarDatosArticulo" name="btnGuardarDatosArticulo" onclick="validarFormArticulo();">Aceptar</button>
                    <button type="button" id="btnCancelarDatosArticulo" name="btnCancelarDatosArticulo" class="close" onclick="xajax_eliminarIva(xajax.getFormValues('frmDatosArticulo'));">Cerrar</button>
                </td>
            </tr>
        </table>
    </form>
<!--FORMULARIO PARA GUARDA EN ALMACEN--> 
    <!--<form id="frmAlmacen" name="frmAlmacen" style="margin:0" onsubmit="return false;"> 
        <table id="tblAlmacen" width="960">
            <tr>
                <td valign="top" width="40%">
                    <table width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="26%">
                                <span class="textoRojoNegrita">*</span>Código:
                            </td>
                            <td width="74%">
                                <input type="hidden" id="hddNumeroArt2" name="hddNumeroArt2" readonly="readonly"/>
                                <input type="hidden" id="hddIdDetallePedido" name="hddIdDetallePedido" readonly="readonly"/>
                                <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
                                <input type="text" id="txtCodigoArticulo" name="txtCodigoArticulo" size="30" readonly="readonly">
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Articulo:
                            </td>
                            <td>
                                <textarea id="txtArticulo" name="txtArticulo" cols="40" rows="3" readonly="readonly">
                                </textarea>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Empresa:
                            </td>
                            <td id="tdlstEmpresa">
                            <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                            </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Almacen:
                            </td>
                            <td id="tdlstAlmacenAct">
                                <select id="lstAlmacenAct" name="lstAlmacenAct">
                                <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Calle:
                            </td>
                            <td id="tdlstCalleAct">
                                <select id="lstCalleAct" name="lstCalleAct">
                                <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Estante:
                            </td>
                            <td id="tdlstEstanteAct">
                                <select id="lstEstanteAct" name="lstEstanteAct">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Tramo:
                            </td>
                            <td id="tdlstTramoAct">
                                <select id="lstTramoAct" name="lstTramoAct">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Casilla:
                            </td>
                            <td id="tdlstCasillaAct">
                                <select id="lstCasillaAct" name="lstCasillaAct">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Cantidad por Distribuir:
                            </td>
                            <td>
                                <input type="text" id="txtCantidadDisponible" name="txtCantidadDisponible" readonly="readonly" size="10"/>
                            </td>
                        </tr>
                    </table>
                </td>
                <td valign="top" width="60%">
                    <fieldset>
                        <legend class="legend">Articulos Existentes en el Almacen</legend>
                            <div id="divArticulosAlmacen" style="height:250px; overflow:auto;">
                                <table border="1" class="tabla" cellpadding="2" width="96%">
                                    <tr align="center" class="tituloColumna">
                                        <td width="38%">Código</td>
                                        <td width="50%">Descripción</td>
                                        <td width="12%">Exist.</td>
                                    </tr>
                                </table>
                            </div>
                    </fieldset>
                </td>
            
            </tr>
            <tr>
                <td align="right" colspan="2"><hr>
                <button type="submit" id="btnGuardarAlmacen" name="btnGuardarAlmacen" onclick="validarFormAlmacen();">Guardar</button>
                <button type="button" id="btnCancelarAlmacen" name="btnCancelarDatosArticulo" class="close">Cerrar</button>
                </td>
            </tr>
        </table>
    </form>-->
</div>

<!--LISTADO DE CLIENTES-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"> <!--TITULO DEL LISTADO-->
    	<table width="700">
            <tr>
                <td id="tdFlotanteTitulo2" width="100%" align="left">Cliente</td>
                <td><img src="../img/iconos/ico_delete.png" class="close"/></td>
            </tr>
        </table>
    </div>
    <table width="100%">
        <tr>
            <td align="right"> <!--CONTIENE EL BUSCADOR DEL LISTADO-->
                <table cellpadding="0" cellspacing="0" align="right"> 
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                    	<form id="frmBuscarCliente" name="frmBuscarCliente" style="margin:0" onsubmit="return false;">
                        <td class="tituloCampo">Criterio:</td>
                        <td><input type="text" id="textCriterioCliente" name="textCriterioCliente" size="25"/></td>
                        <td><button id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_BuscarCliente(xajax.getFormValues('frmBuscarCliente'));">Buscar</button></td>
                        <td><button id="btnLimpiarCliente" name="btnLimpiarCliente" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button></td>
                   		</form>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr> <!--DONDE SERA CREA EL LISTADO-->
            <td id="tdListaCliente" align="right"></td>
        </tr> 
        <tr><!--CONTIENE EL BOTON DE CERRAR EL LISTADO-->
            <td id=""  align="right">
            	<hr />
                <button id="btnCerrarListaCLiente" name="btnCerrarListaCLiente" class="close">
                    Cerrar
                </button>
            </td>
        </tr>
    </table>
</div>

<!--LISTADO DE PROVEEDORES-->
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"> <!--TITULO DEL LISTADO-->
    	<table width="700">
            <tr>
                <td id="tdFlotanteTitulo3" width="100%" align="left">Proveedores</td>
                <td><img src="../img/iconos/ico_delete.png" class="close"/></td>
            </tr>
        </table>
    </div>
    <table width="100%"> <!--CONTIENE EL BUSCADOR Y LISTADO PROVEEDORES-->
        <tr>
        	<td>
            	<form id="frmBuscarProveedor" name="frmBuscarProveedor" style="margin:0" onsubmit="return false;">
                <table cellpadding="0" cellspacing="0" align="right"> <!--CONTIENE EL BUSCADOR DEL LISTADO-->
                    <tr><td colspan="4">&nbsp;</td></tr>
                    <tr>
                        <td class="tituloCampo">Criterio:</td>
                        <td><input type="text" id="textCriterioProveed" name="textCriterioProveed" size="25"/></td>
                        <td><button id="BtnBuscarProvee" name="BtnBuscarProvee" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button></td>
                        <td><button id="BtnLimpiarProvee" name="BtnLimpiarProvee" onclick="document.forms['frmBuscarProveedor'].reset(); byId('BtnBuscarProvee').click();">Limpiar</button></td>
                    </tr>
                    <tr><td colspan="4">&nbsp;</td></tr>
                </table>
                </form>
            </td>
        </tr>
        <tr><td id="tdListProveedores"></td></tr><!--LISTADO PROVEEDORES-->
        <tr>
        	<td align="right"><hr />
            	<button id="btnCerrarListaProveedor" class="close" >Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<!--LISTADO DE ART-->
<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo4" class="handle"> <!--TITULO DEL LISTADO-->
    	<table width="100%">
            <tr>
                <td id="tdFlotanteTitulo3" width="100%" align="left">Listado de Articulos</td>
                <td><img src="../img/iconos/ico_delete.png" class="close"/></td>
            </tr>
        </table>
    </div>
<!--TABLA QUE CONTIENE EL LISTADO ARTICULO Y EL BUSCADOR DE ART style="display:none"-->
    <table border="0" id="tblArticulo" width="960">
        <tr id="tdBuscarArt"> <!--CONTIENE EL FORM PAR ABUSCAR ART EN EL LIST ART-->
            <td align="right">
                <form id="frmBuscarArt" name="frmBuscarArt" style="margin:0" onsubmit="return false;">
                    <table>
                        <tr>
                            <td class="tituloCampo">Codifo Art:</td>
                            <td><input type="text" id="textCodigoArtBus" name="textCodigoArtBus" class="inputHabilitado"/></td>
                            <td class="tituloCampo">Tipo de Art: </td>
                            <td id="tdTipoArticulo">
                                <select id="lstTipoArticuloBus" name="lstTipoArticuloBus" class="inputHabilitado">
                                <option value="-"> [ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Cirterio:</td>
                            <td><input type="text" id="textCriterioBus" name="textCriterioBus" class="inputHabilitado" size="35"/></td>
                            <td colspan="2" align="center">
                                <button type="button" id="btnBuscarArt" name="btnBuscarArt" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArt'),xajax.getFormValues('frmDcto'));">Buscar</button>
                                <button type="button" id="btnLimpiar" name="btnLimpiar" onclick="document.forms['frmBuscarArt'].reset(); byId('btnBuscarArt').click();">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListadoArticulo"></td><!--LIST ART--> 
        </tr>
        <tr>
            <td align="right"><hr /><button id="btnListArt" name="btnListArt" class="close">Cerrar</button></td>
        </tr>
    </table >
</div>

<!--LISTADO IMPUESTO-->
<div id="divFlotante5" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo5" class="handle"> <!--TITULO DEL LISTADO-->
    	<table width="100%">
            <tr>
                <td id="tdFlotanteTitulo5" width="100%" align="left"></td>
                <td><img src="../img/iconos/ico_delete.png" class="close"/></td>
            </tr>
        </table>
    </div>
    <table width="100%" border="0">
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
            	<form id="frmLstImpuesto" onsubmit="return false" style="margin:0">
                	<div id="divListIpmuesto"></div>
                </form>
            </td>
        </tr>
        <tr>
            <td align="right">
                <hr />
                <button id="btsAceptarImpuestoPorBLoque" name="btsAceptarImpuestoPorBLoque" style="display:none" onclick="xajax_agregarBloqueImpuesto(xajax.getFormValues('frmListaArticulo'),xajax.getFormValues('frmLstImpuesto'));">Aceptar</button>	
                <button id="btsAceptarImpuesto" name="btsAceptarImpuesto" onclick="xajax_validarItems('Impuesto',xajax.getFormValues('frmLstImpuesto'), xajax.getFormValues('frmDatosArticulo'));">Aceptar</button>
                <button id="btsCerraImpuesto" name="btsCerraImpuesto" class="close">Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<!--LISTA DE GASTOS-->
<div id="divFlotante6" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo6" class="handle"> <!--TITULO DEL LISTADO-->
    	<table width="100%">
            <tr>
                <td id="tdFlotanteTitulo6" width="100%" align="left"></td>
                <td><img src="../img/iconos/ico_delete.png" class="close"/></td>
            </tr>
        </table>
    </div>
    <table width="100%" border="0">
    	<tr>
        	<td id="tdBuscarGastos" align="right">
            	<form id="frmBuscarGastos" onsubmit="return false" style="margin:0">
                	<table border="0">
                        <tr>
                        	<td class="tituloCampo">Modo:</td>
                            <td>
                            	<select id="selctModoGastos" name="selctModoGastos" onchange="byId('btnBuscarGastos').click()">
                                	<option value="-1">[ Todo ]</option>
                                    <option value="1"> Gastos </option>
                                    <option value="2"> Otros Cargos </option>
                                    <option value="3"> Gastos por Importación </option>
                                </select>
                            </td>
                        	<td class="tituloCampo">Afecta Documento:</td>
                            <td>
                            	<select id="selctAfectaDoct" name="selctAfectaDoct" onchange="byId('btnBuscarGastos').click()">
                                	<option value="-1">[ Seleccione ]</option>
                                    <option value="1"> SI </option>
                                    <option value="0"> No </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                        	<td  class="tituloCampo">Criterio:</td>
                            <td colspan="3"><input type="text" name="txtBuscarCriterio" id="txtBuscarCriterio" size="40" onkeyup="byId('btnBuscarGastos').click()"></td>
                        </tr>
                        <tr>
                        	<td colspan="4" align="right">
                            	<button id="btnBuscarGastos" name="btnBuscarGastos" onclick="xajax_BuscarGastos(xajax.getFormValues('frmBuscarGastos'))">Buscar</button>
                                <button id="btnLimpiarGastos" name="btnLimpiarGastos" onclick="document.forms['frmBuscarGastos'].reset(); byId('btnBuscarGastos').click();">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form id="frmLstGasto" onsubmit="return false" style="margin:0">
                    <div id="divLstGastos"></div>
                </form>
            </td>
        <tr>
        	<td class="divMsjInfo2">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                        <td align="center">
                            <table>
                                <tr>
                                    <td><img title="Activo" src="../img/iconos/ico_verde.gif"></td>
                                    <td>Impuestos Activo</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/stop.png"></td>
                                    <td>No afecta cuenta por pagar</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        </tr>
		<tr>
        	<td align="right"><hr  />
            	<button id="btnAceptarGastos" name="btnAceptarGastos" onclick="xajax_validarItems('Gasto',xajax.getFormValues('frmLstGasto'), xajax.getFormValues('frmTotalDcto'));">Aceptar</button>
                <button id="btnCerraGastos" name="btnCerraGastos" class="close">Cerrar</button>
            </td> 
        </tr>
    </table>
</div>
<!--BUSCAR SOLICITUD-->
<div id="divFlotante7" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo7" class="handle"> <!--TITULO DEL LISTADO-->
    	<table width="100%">
            <tr>
                <td id="tdFlotanteTitulo7" width="100%" align="left"></td>
                <td><img src="../img/iconos/ico_delete.png" class="close"/></td>
            </tr>
        </table>
    </div>
    <table border="0" width="960">
         <tr>
            <td>
            	<form id="frmBuscarOrden" name="frmBuscarOrden" onsubmit="return false" style="margin:0">
                	<table align="right" width="30%"> 
                    	<tr>
                            <td class="tituloCampo" >Num Orden:</td>
                            <td><input id="textNumOrden" name="textNumOrden" class="inputHabilitado"/></td>
                        </tr>
                        <tr>
                        	<td colspan="2" align="right">
                            	<button id="btnBuscarNumOrden" name="btnBuscarNumOrden" onclick="xajax_buscarNumOrden(xajax.getFormValues('frmBuscarOrden'))">Buscar</button>
                           		<button id="btnLimpiarNumOrden" name="btnLimpiarNumOrden" onclick="habilitar('tdListOrdenes', 'MostarOrden', 'hide')" >Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListOrdenes" style="display:none">
            	<form id="frmLstOrden" onsubmit="return false" style="margin:0">
                    <fieldset >
                        <legend class="legend"> Listado de Ordenes</legend>
                        <div id="divLstOrden"></div>
                    </fieldset>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListItemsOrden" style="display:none">
            	<form id="frmLstOrdenDetalle" onsubmit="return false" style="margin:0">
                    <fieldset >
                        <legend id="lgdOrden" class="legend"></legend>
                        <div id="divLstOrdenDetalle"></div>
                    </fieldset>
                </form>
            </td>
        </tr>
        <tr>
            <td align="right">
                <hr />
               
                <button id="btsCerraArtOrden" name="btsCerraArtOrden" class="close" onclick="byId('btnLimpiarNumOrden').click();">Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<div id="divFlotante8" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo8" class="handle"> <!--TITULO DEL LISTADO-->
    	<table width="100%">
            <tr>
                <td id="tdFlotanteTitulo8" width="100%" align="left"></td>
                <td></td>
            </tr>
        </table>
    </div>
    
    <table width="350" border="0">
  <tr>
    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Total Factura Compra:</td>
    <td>
    <input type="text" size="16" style="text-align:right" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);" name="txtTotalFactura" id="txtTotalFactura" class="inputHabilitado">
    </td>
  </tr>
  <tr>
    <td colspan="2" align="right"> 
    	<hr>
			<input type="hidden" readonly="readonly" name="hddFrm" id="hddFrm" value="tblArticulosPedido">
            <button onclick="validarFrmTotalFactura();" name="btnGuardarTotalFactura" id="btnGuardarTotalFactura" type="submit">Aceptar</button>
            <button class="close" name="btnCancelarTotalFactura" id="btnCancelarTotalFactura" type="button">Cerrar</button>
    </td>
  </tr>
</table>
</div>

<script>
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

jQuery(function($){
	$("#txtFechaProveedor").maskInput("99-99-9999",{placeholder:" "});
});
	
/*new JsDatePick({
	useMode:2,
	target:"txtFechaProveedor",
	dateFormat:"%d-%m-%Y",
	cellColorScheme:"greenish"
	selectedDate:{				This is an example of what the full configuration offers.
		day:5,						For full documentation about these settings please see the full version of the code.
		month:9,
		year:2006
	},
	yearsRange:[1978,2020],
	limitToToday:false,
	imgPath:"img/"
	dateFormat:"%m-%d-%Y",
	weekStartDay:1
});*/
xajax_listIva(0,'','iva','ASC');
//xajax_listadoOrdenes(0,'','','ASCs');
xajax_listadoGastos(0,'id_gasto','ASC');	
<?php if (!(isset($_GET['id']))) { ?>
	xajax_nuevoDcto();
<?php } else { ?>
	xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
<?php } ?>

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
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

var theHandle = document.getElementById("divFlotanteTitulo5");
var theRoot   = document.getElementById("divFlotante5");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo6");
var theRoot   = document.getElementById("divFlotante6");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo7");
var theRoot   = document.getElementById("divFlotante7");
Drag.init(theHandle, theRoot);


</script>