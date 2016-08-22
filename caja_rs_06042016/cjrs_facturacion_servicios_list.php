<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_factura_venta_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

$_GET['acc'] = 4;

$currentPage = $_SERVER["PHP_SELF"];

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_facturacion_servicios_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE 2.0 :. Caja de Repuestos y Servicios - Listado de Ordenes de Servicios</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
	
	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<style type="text/css">
	.root {
		background-color:#FFFFFF;
		border:6px solid #999999;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:11px;
		max-width:1050px;
		position:absolute;
	}
	</style>
	<script>
		function validarFormDetenerOrden(){
			if (validarCampo('lstMotivoDetencion','t','lista') == true) {
				if(confirm("Esta seguro de Detener la Orden?"))
				{
					xajax_guardarDetencionOrden(xajax.getFormValues('frmDetenerOrden'));
				}
	
			} else {
				validarCampo('lstMotivoDetencion','t','lista');
					
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		}
		
		function validarFormReanudarOrden(){
			if (validarCampo('lstReanudarOrden','t','lista') == true) {
				if(confirm("Esta seguro de Reanudar la Orden?"))
				{
					xajax_guardarReanudoOrden(xajax.getFormValues('frmReanudarOrden'));
				}
	
			} else {
				validarCampo('lstReanudarOrden','t','lista');
					
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		}
		
		function validarFormAprobacionOrden(){
			if (validarCampo('txtClaveAprobacion','t','') == true) {
				if(confirm("Esta seguro de Aprobar la Orden?"))
				{
					xajax_aprobarOrden(xajax.getFormValues('frmClaveAprobacionOrden'));
				}
	
			} else {
				validarCampo('txtClaveAprobacion','t','');
					
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		
		}
			
		function validarDevolver(){
		if (validarCampo('txtClave','t','') == true)
		{
			xajax_verificarClave(xajax.getFormValues('frmDevolver'),1);
		 }
		 else{
		 	validarCampo('txtClave','t','')
			alert("Los campos señalados en rojo son requeridos");
			return false;
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
		<td id="tdTituloListado" class="tituloPaginaCajaRS"></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td align="right">
			<table align="left">
			<tr>
				<td width="112"><button class="noprint" type="button" id="btnImprimir" name="btnImprimir" onclick="window.print();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button></td>
			</tr>
			</table>
			<form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarOrden(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
				<table align="right" border="0">
                <tr id="trEmpresa" align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
                </tr>
				<tr>
					<td class="tituloCampo" align="right" width="120">Tipo:</td>
					<td id="tdlstTipoOrden">
						<select id="lstTipoOrden" name="lstTipoOrden">
							<option value="-1">[ Todos ]</option>
						</select>
						<script>
							xajax_cargaLstTipoOrden();
						</script>
					</td>
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td id="tdlstAno"><input type="text" id="txtPalabra" name="txtPalabra" onkeyup="$('btnBuscar').click();"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarOrden(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		<tr>
			<td id="tdListadoOrden"></td>
		</tr>
		<tr>
			<td>
				<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
						<table>
						<tr>
							<td><img src="../img/iconos/ico_importar.gif"/></td>
							<td>Facturar</td><td>&nbsp;</td>
							<td><img src="../img/iconos/ico_return.png"/></td>
							<td>Devolver Orden</td>
						</tr>                    
						</table>
					</td>
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
	<table border="0" id="tblDcto" width="980px">
	<tr>
		<td>
			<table>
			<tr>
				<td align="right" class="tituloCampo" width="140">Código:</td>
				<td><input type="text" id="txtCodigoArticulo" name="txtCodigoArticulo" size="30" readonly="readonly"></td>
			</tr>
			<tr>
				<td align="right" class="tituloCampo">Descripcion:</td>
				<td><textarea id="txtArticulo" name="txtArticulo" cols="75" rows="3" readonly="readonly"></textarea></td>
			</tr>
			<tr>
				<td align="right" class="tituloCampo" id="tdTituloCampoDcto" width="100"></td>
				<td><input type="text" id="txtCantidad" name="txtCantidad" size="30" readonly="readonly"></td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td id="tdListadoDcto">
			<table width="100%">
			<tr class="tituloColumna">
				<td>Código</td>
				<td>Descripción</td>
				<td>Marca</td>
				<td>Tipo</td>
				<td>Sección</td>
				<td>Sub-Sección</td>
				<td>Disponible</td>
				<td>Reservado</td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<input type="button" onclick="validarFormArt();" value="Aceptar">
			<input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
		</td>
	</tr>
	</table>
	<form id="frmDetenerOrden" name="frmDetenerOrden" style="margin:0">
	<table width="35%" border="0" id="tblDetencionOrden">
	<tr>
		<td colspan="2" id="tdTituloListado">&nbsp;</td>
	</tr>
	<tr>
		<td width="33%">&nbsp;</td>
		<td width="67%">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" class="tituloCampo" width="140">Nro. Orden:</td>
		<td>
			<input type="text" id="txtNroOrden" name="txtNroOrden" size="30" readonly="readonly">
			<input type="hidden" id="hddValBusq" name="hddValBusq" size="30" readonly="readonly">
			<input type="hidden" id="hddPageNum" name="hddPageNum" size="30" readonly="readonly">
			<input type="hidden" id="hddCampOrd" name="hddCampOrd" size="30" readonly="readonly">
			<input type="hidden" id="hddTpOrd" name="hddTpOrd" size="30" readonly="readonly">
			<input type="hidden" id="hddMaxRows" name="hddMaxRows" size="30" readonly="readonly">
		</td>
	</tr>
	<tr>
		<td class="tituloCampo">Motivo:</td>
		<td id="tdListMotivoDetencionOrden">
			<select id="lstMotivoDetencion" name="lstMotivoDetencion">
				<option value="-1">Seleccione...</option>
			</select>
			<script>
				//xajax_cargaLstMotivoDetencionOrden();
			</script>
		</td>
	</tr>
	<tr>
		<td class="tituloCampo">Observacion:</td>
		<td><textarea name="txtAreaObservacionDetencion" id="txtAreaObservacionDetencion" cols="45" rows="5"></textarea></td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
			<input type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormDetenerOrden();" value="Guardar"/>
			<input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
			</td>
	</tr>
	</table> 
	</form>
	<form id="frmReanudarOrden" name="frmReanudarOrden" style="margin:0">
	<table width="35%" border="0" id="tblReanudarOrden">
	<tr>
		<td colspan="2" id="tdTituloListado">&nbsp;</td>
	</tr>
	<tr>
		<td width="33%">&nbsp;</td>
		<td width="67%">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" class="tituloCampo" width="140">Nro. Orden:</td>
		<td>
			<input type="text" id="txtNroOrdenRe" name="txtNroOrdenRe" size="30" readonly="readonly">
			<input type="hidden" id="hddValBusqRe" name="hddValBusqRe" size="30" readonly="readonly">
			<input type="hidden" id="hddPageNumRe" name="hddPageNumRe" size="30" readonly="readonly">
			<input type="hidden" id="hddCampOrdRe" name="hddCampOrdRe" size="30" readonly="readonly">
			<input type="hidden" id="hddTpOrdRe" name="hddTpOrdRe" size="30" readonly="readonly">
			<input type="hidden" id="hddMaxRowsRe" name="hddMaxRowsRe" size="30" readonly="readonly">
		</td>
	</tr>
	<tr>
		<td class="tituloCampo">Motivo:</td>
		<td id="tdListReanudarOrden">
			<select id="lstReanudarOrden" name="lstReanudarOrden">
				<option value="-1">Seleccione...</option>
			</select>
			<script>
				//xajax_cargaLstReanudarOrden();
			</script>
		</td>
	</tr>
	<tr>
		<td class="tituloCampo">Observacion:</td>
		<td><textarea name="txtAreaObservacionReanudo" id="txtAreaObservacionReanudo" cols="45" rows="5"></textarea></td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
			<input type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormReanudarOrden();" value="Guardar"/>
			<input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
		</td>
	</tr>
	</table>
	</form>
	<form id="frmClaveAprobacionOrden" name="frmClaveAprobacionOrden" style="margin:0">
	<table width="40%" border="0" id="tblClaveAprobacionOrden">
	<tr>
		<td colspan="2" id="tdTituloListado">&nbsp;</td>
	</tr>
	<tr>
		<td width="50%">&nbsp;</td>
		<td width="50%">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" class="tituloCampo" width="41%">Nro. Orden:</td>
		<td>
			<input type="text" id="txtNroOrdenAprob" name="txtNroOrdenAprob" readonly="readonly">
			<input type="hidden" id="txtIdClaveUsuario" name="txtIdClaveUsuario" readonly="readonly">
			<input type="hidden" id="hddValBusqAprob" name="hddValBusqAprob" readonly="readonly">
			<input type="hidden" id="hddPageNumAprob" name="hddPageNumAprob" readonly="readonly">
			<input type="hidden" id="hddCampOrdAprob" name="hddCampOrdAprob" readonly="readonly">
			<input type="hidden" id="hddTpOrdAprob" name="hddTpOrdAprob" readonly="readonly">
			<input type="hidden" id="hddMaxRowsAprob" name="hddMaxRowsAprob" readonly="readonly">
			<input type="hidden" id="hddIdMecanicoAprob" name="hddIdMecanicoAprob" readonly="readonly">
			<input type="hidden" id="hddIdJefeTallerAprob" name="hddIdJefeTallerAprob" readonly="readonly">
			<input type="hidden" id="hddIdControlTallerAprob" name="hddIdControlTallerAprob" readonly="readonly">
		</td>
	</tr>
	<tr>
		<td align="right" class="tituloCampo">Clave:</td>
		<td>
			<label><input name="txtClaveAprobacion" id="txtClaveAprobacion" type="password" class="inputInicial"/></label>
		</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
			<input type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormAprobacionOrden();" value="Guardar"/>
			<input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar"></td>
	</tr>
	</table>
	</form>
</div>

<div id="divDevolver" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%">Devolver Orden</td></tr></table></div>
	<form id="frmDevolver" name="frmDevolver" onsubmit="return false;">
	<table border="0" id="tblClaveAprobacionOrden">
	<tr>
		<td colspan="2" id="tdTituloListado">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" class="tituloCampo">Nro. Orden:</td>
		<td>
			<input type="text" id="txtNumOrden" name="txtNumOrden" readonly="readonly" style="color:#F00; font-weight:bold;">
			<input type="hidden" id="hddIdOrden" name="hddIdOrden" readonly="readonly"/>
		</td>
	</tr>
	<tr>
		<td align="right" class="tituloCampo">Clave Aprobación:</td>
		<td>
			<input name="txtClave" id="txtClave" type="password" class="inputHabilitado"/>
		</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
            <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarDevolver();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button>
            <button type="button" id="btnCancelar" name="btnCancelar" onclick="$('divDevolver').style.display='none';"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
	</form>
</div>

<script>
byId('lstTipoOrden').className = "inputHabilitado";
byId('txtPalabra').className = "inputHabilitado";

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot = document.getElementById("divDevolver");
Drag.init(theHandle, theRoot);

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_validarAperturaCaja();
xajax_listadoOrdenes(0,'numero_orden','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|');
</script>