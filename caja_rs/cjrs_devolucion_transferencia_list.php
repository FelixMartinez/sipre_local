<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_devolucion_transferencia_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_devolucion_transferencia_list.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Devolución de Transferencias</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script type="text/javascript" language="javascript">
    function abrirDivFlotante1(obj, id_transferencia){
		
    	byId('hddIdTransferencia').value = "";
		byId('txtNroTransferencia').value = "";
		byId('txtMotivoAnulacion').value = "";		
		
		openImg(obj);
		xajax_cargarTransferencia(id_transferencia);
		byId('txtMotivoAnulacion').focus();
    }
	
	function validarAnulacionTransferencia(){
		error = false;
		
		if (!(validarCampo('hddIdTransferencia','t','') == true
		&& validarCampo('txtNroTransferencia','t','') == true
		&& validarCampo('txtMotivoAnulacion','t','') == true)) {
			validarCampo('hddIdTransferencia','t','');
			validarCampo('txtNroTransferencia','t','');
			validarCampo('txtMotivoAnulacion','t','');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if(confirm("¿Seguro deseas anular la transferencia Nro: " + byId('txtNroTransferencia').value + " ?")){
				xajax_anularTransferencia(xajax.getFormValues('formAnulacion'));
			}			
		}
	}
	
	function numerosLetras(e){
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8){//8 = delete
			return true;
		}else if(tecla == 13){
			return false;
		}else{
			patron = /[0-9A-Za-z\s ]/;
			te = String.fromCharCode(tecla);
			return patron.test(te);
		}
	}
	
	</script>
    
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr class="solo_print">
			<td align="left" id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
            <td class="tituloPaginaCajaRS">Devolución de Transferencias</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right">
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
			<button type="button" onclick="xajax_imprimirDevolucionTransferencia(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                    </td>
                </tr>
                </table>
				
		<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr id="trEmpresa" align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;Desde:&nbsp;</td>
                            <td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" style="text-align:center"/></td>
                            <td>&nbsp;Hasta:&nbsp;</td>
                            <td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Estatus:</td>
                    <td id="tdlstEstatus">
                        <select id="lstEstatus" name="lstEstatus" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Anulado</option>
                            <option value="1">Activo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Estado:</td>
                    <td>
                        <select multiple id="lstEstadoTransferencia" name="lstEstadoTransferencia" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option selected="selected" value="0">No Cancelado</option>
                            <option selected="selected" value="1">Cancelado (No Asignado)</option>
                            <option selected="selected" value="2">Asignado Parcial</option>
                            <option selected="selected" value="3">Asignado</option>
                            <option selected="selected" value="4">No Cancelado (Asignado)</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Módulo:</td>
                    <td id="tdlstModulo"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>	
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarTransferencia(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
			</td>
		</tr>
		<tr>
			<td><div id="divListadoTransferencias" style="width:100%"></div></td>		
		</tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_verde.gif"/></td><td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif"/></td><td>Anulado</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
	<tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                        <td><img src="../img/iconos/delete.png" /></td>
                             <td>Anular Transferencia</td>
                             <td>&nbsp;</td>
                             <td><img src="../img/iconos/arrow_rotate_clockwise.png" /></td>
                             <td>Generar Nota de Débito</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
	
		<tr>
			<td align="right"><hr>
            	<table>
                <tr align="right" class="trResaltarTotal">
                    <td class="tituloCampo" width="120">Total Saldo(s):</td>
                    <td width="150"><span id="spnSaldoTransferencias"></span></td>
                    <td class="tituloCampo" width="120">Total Transferencia(s):</td>
                    <td width="150"><span id="spnTotalTransferencias"></span></td>
                </tr>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%">Anular Transferencia</td></tr></table></div>
    <form id="formAnulacion" name="formAnulacion" style="margin:0" onsubmit="return false;">
    <table border="0" width="100%" >
    <tr>
    	<td>
        	<table>
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Nro Transferencia:</td>
                <td>
                    <input type="hidden" id="hddIdTransferencia" name="hddIdTransferencia" />
                    <input type="text" id="txtNroTransferencia" name="txtNroTransferencia" readonly="readonly" />
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Motivo Anulaci&oacute;n:</td>
                <td>                    
                    <textarea cols="40" id="txtMotivoAnulacion" name="txtMotivoAnulacion" class="inputHabilitado" onkeyPress="return numerosLetras(event);"></textarea>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
	        <button type="button" id="btnAnularTransferencia" name="btnAnularTransferencia" onclick="validarAnulacionTransferencia();"><img class="puntero" title="Anular Transferencia" src="../img/iconos/delete.png" style="vertical-align:middle; margin-top:-2px;">Anular Transferencia</button>
            <button type="button" id="btnCancelarAnulacion" name="btnCancelarAnulacion" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    </form>
</div>

<script>
byId('txtFechaDesde').className = 'inputHabilitado';
byId('txtFechaHasta').className = 'inputHabilitado';
byId('lstEstadoTransferencia').className = 'inputHabilitado';
byId('lstEstatus').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

byId('txtFechaDesde').value = "<?php echo date("01-m-Y")?>";
byId('txtFechaHasta').value = "<?php echo date("d-m-Y")?>";

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

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaHasta").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"brown"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"brown"
	});
};

var lstEstadoTransferencia = $.map($("#lstEstadoTransferencia option:selected"), function (el, i) { return el.value; });

xajax_cargaLstModulo();
xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>