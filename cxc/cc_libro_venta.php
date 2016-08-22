<?php
require_once ("../connections/conex.php");

session_start();


/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cc_libro_venta"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_libro_venta.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Libro de Ventas</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css"/>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
	
	<script>
	function formListaEmpresa(nomObjeto, objDestino, nomVentana) {
		openImg(nomObjeto);
		
		document.forms['frmBuscarEmpresa'].reset();
		
		byId('hddObjDestino').value = objDestino;
		byId('hddNomVentana').value = nomVentana;
		
		byId('btnBuscarEmpresa').click();
		
		byId('tblListaEmpresa').style.display = '';
		
		byId('tdFlotanteTitulo2').innerHTML = "Empresas";
		
		byId('txtCriterioBuscarEmpresa').focus();
		byId('txtCriterioBuscarEmpresa').select();
	}
	</script>

</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCuentasPorCobrar">Libro de Ventas</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmFechasLibros" name="frmFechasLibros" style="margin:0">
				<table border="0" width="100%" align="center">
				<tr>
					<td align="left">
						<table cellpadding="0" cellspacing="0">
						<tr>
							<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
							<td>
								<input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresa(this.value);" size="6" style="text-align:right;"/></td>
							<td>
								<a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="formListaEmpresa(this,'Empresa','ListaEmpresa');">
									<button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar">
										<img src="../img/iconos/help.png"/>
									</button>
								</a>
							</td>
							<td>
								<input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/>
							</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td valign="top" width="50%">
						<fieldset><legend class="legend">Rango de Fecha</legend>
						<table border="0" width="100%" align="center">
						<tr>
							<td align="right" class="tituloCampo" width="120">Fecha Inicial:</td>
							<td align="left">
								<input type="text" name="txtFechaOrigen" id="txtFechaOrigen" style="text-align:center" size="10" readonly="readonly"/>
							</td>
							<td align="right" class="tituloCampo" width="120">Fecha Final:</td>
							<td align="left">
								<input type="text" id="txtFechaFinal" name="txtFechaFinal" style="text-align:center" size="10" readonly="readonly"/>
							</td>
						</tr>
						</table>
						</fieldset>
					</td>
					<td valign="top" width="50%">
						<fieldset><legend class="legend">Módulos</legend>
						<table border="0" class="tabla" width="100%">
						<tr>
							<td id="tdModulos" valign="top" width="50%"></td>
						</tr>
						</table>
						</fieldset>
					</td>
				</tr>
				<tr>
					<td align="right" colspan="2"><hr>
						<button type="button" id="bttGenerar" name="bttGenerar" onclick="xajax_validaEnvia(xajax.getFormValues('frmFechasLibros'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_examinar.png"/></td><td>&nbsp;</td><td>Generar</td></tr></table></button></td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><a id="aCerrarDivFlotante2" onclick="byId('divFlotante2').style.display='none';"><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></a></td></tr></table></div>
	<table border="0" id="tblListaEmpresa" width="700">
	<tr>
		<td>
		<form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
			<input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
			<input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
			<table align="right">
			<tr align="left">
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td>
					<input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
				<td>
					<button type="submit" id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));">Buscar</button>
					<button type="button" onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
				</td>
			</tr>
			</table>
		</form>
		</td>
	</tr>
	<tr>
		<td>
		<form id="frmListaEmpresa" name="frmListaEmpresa" style="margin:0" onsubmit="return false;">
			<div id="divListaEmpresa" style="width:100%"></div>
		</form>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cerrar</button>
		</td>
	</tr>
	</table>
</div>

<script>
byId('txtFechaOrigen').className = 'inputHabilitado';
byId('txtFechaFinal').className = 'inputHabilitado';

byId('txtFechaOrigen').value = "<?php echo date("01-m-Y")?>";
byId('txtFechaFinal').value = "<?php echo date("d-m-Y")?>";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaOrigen").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaFinal").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaOrigen",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"purple"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaFinal",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"purple"		
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
</script>

<script>
	xajax_cargarModulos();
	
	xajax_asignarEmpresaUsuario('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'Empresa', 'ListaEmpresa');
	
var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>