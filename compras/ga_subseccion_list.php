<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_subseccion_list"))) {
	echo "
	<script>
		alert('Acceso Denegado');
		top.history.back();
	</script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_ga_subseccion_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Compras - Sub-Secciones</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../clases/styleRafkLista.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    <script>
	function abrirDivFlotante(nomObjeto, verTabla, valor) {
		switch(verTabla){
			case 'tblSubSeccion':
				var objeto = 'tblSubSeccion';
				var objeto2 = 'tdFlotanteTitulo';
					break;
			case 'tblTipoActivo':
				var objeto = 'tblTipoActivo';
				var objeto2 = 'tdFlotanteTitulo2';
					break;
			
			}

		if (verTabla == "tblSubSeccion") {
			if (valor > 0) {
				xajax_cargarSubSeccion(valor);
				tituloDiv1 = 'Editar Sub-seccion';
			} else {
				xajax_formSubSeccion();
				tituloDiv1 = 'Agregar Sub-seccion';
			}
		} else {
			tituloDiv1 = 'Lsitado Tipo de Activos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId(objeto2).innerHTML = tituloDiv1;
		
		if (verTabla == "tblSubSeccion") {
			byId('txtSubSeccion').focus();
			byId('txtSubSeccion').select();
		}	
	}
	
	function activaTr(tipoActivo){
		if (tipoActivo == 5){
			
			byId('codTipoActivo').className = 'inputHabilitado';
			byId('desTipoActivo').className = 'inputHabilitado';
			xajax_listadoTipoActivo(0,'Descripcion','ASC');		
			$('#trTipoActivo').show();
			
			} else {
				$('#trTipoActivo').hide();
				//$('#lstTipoActivo').remove();
		}
	}
	
	function validarEliminar(idSubseccion){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarSubSeccion(idSubseccion);
		}
	}
	
	function validarEliminarBloque(){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarSubSeccionBloque(xajax.getFormValues('frmListaSubSeccion'));
		}
	}
		
	function validarForm() {
		if (validarCampo('txtSubSeccion','t','') == true
		&& validarCampo('lstSeccionNuv','t','listaExceptCero') == true
		&& validarCampo('lstEstatu','t','listaExceptCero') == true
		) {

			xajax_guardarSubSeccion(xajax.getFormValues('frmSubSeccion'));
		} else {

			validarCampo('txtSubSeccion','t','');
			validarCampo('lstSeccionNuv','t','listaExceptCero');
			validarCampo('lstEstatu','t','listaExceptCero');
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
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
            <tr>
                <td class="tituloPaginaCompras">Sub-Secciones</td>
            </tr>
            <tr>
            	<td>&nbsp;</td>
            </tr>
            <tr>
                <td align="right" >
                    <table align="left">
                        <tr>
                            <td>
                               <a class="modalImg" id="aNuevo" name="aNuevo" rel="#divFlotante" onclick="abrirDivFlotante(this, 'tblSubSeccion');">
                                    <button type="button" >
                                        <table align="center" cellpadding="0" cellspacing="0">
                                            <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr>
                                        </table>
                                    </button>
                                </a>
                            </td>
                            <td>
                                <button type="button" id="btnEliminar" name="btnEliminar" onclick="validarEliminarBloque();">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr>
                                    </table>
                                </button>
                            </td>
                        </tr>
                    </table>
                    
                    <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                        <table align="right" border="0">
                            <tr align="left">
                                <td align="right" class="tituloCampo">Seccion:</td>
                                <td id="tdlstTipoSubSeccionBus" colspan="2"></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Criterio:</td>
                                <td><input type="text" id="txtCriterio" name="txtCriterio" size="30%" class="inputHabilitado" onkeyup="byId('btnBuscar').click();"/></td>
                                <td>
                                <button type="submit" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'), 'subSeccion');">Buscar</button>
                                <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
            <tr>
                <td><form id="frmListaSubSeccion" name="frmListaSubSeccion" style="margin:0"><div id="divSubSecciones"></div></form></td>
            </tr>
            <tr>
            	<td>
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                            <td align="center">
                                <table>
                                <tr>
                                    <td><img src="../img/iconos/ico_verde.gif" /></td>
                                    <td>Activo</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_rojo.gif" /></td>
                                    <td>Inactivo</td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmSubSeccion" name="frmSubSeccion" style="margin:0">
    <table border="0" id="tblSubSeccion" width="470px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Sub-Sección</td>
                <td align="left" width="75%">
                    <input type="text" id="txtSubSeccion" name="txtSubSeccion" size="25"/>
                    <input type="hidden" id="hddIdSubSeccion" name="hddIdSubSeccion" />
                </td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Sección:</td>
                <td align="left" id="tdlstSeccion">
				</td>
            </tr>
            <tr id="trTipoActivo" style="display:none">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Activo:</td>
                <td align="left">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td><input type="text" id="codTipoActivo" name="codTipoActivo" size="11"/>
                        	<input type="hidden" id="idTipoActivo" name="idTipoActivo" size="11"/>
                        </td>
                        <td>
                            <a class="modalImg" id="aTipoActivo" rel="#divFlotante2" onclick="abrirDivFlotante(this, 'tblTipoActivo');">
                                <button type="button" id="btnTipActivo" name="btnTipActivo" title="Tipo de Activo">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_pregunta.gif"/></td><td>&nbsp;</td><td></td></tr>
                                    </table>
                                </button> 
                            </a>
                       </td>
                        <td><input type="text" id="desTipoActivo" name="desTipoActivo" size="30" /></td>
                      </tr>
                    </table>
                </td>
            </tr>            
              <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td align="left" id="tdlstEstatus">
					<select name="lstEstatu" id="lstEstatu">
                        <option value="-1">[Seleccione]</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select> 
                 </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarForm();">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr>
                </table>
            </button>
            <button type="button" id="btnCancelar" name="btnCancelar" onclick="activaTr(0);document.forms['frmSubSeccion'].reset();" class="close">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr>
                </table>
            </button>
        </td>
    </tr>
    </table>
</form>
</div >
<!--Lista de tipo de activos-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
        <table  border="0" id="tblTipoActivo" width="100%">
          <tr>
            <td>
                <form id="fromTipoAct" name="fromTipoAct" style="margin:0" onsubmit="return false;">
                    <table align="right" border="0">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td><input type="text" id="texTipAct" name="texTipAct" class="inputHabilitado" onkeyup="byId('bstTipoAct').click();"/></td>
                            <td>
                            <button type="button" id="bstTipoAct" name="bstTipoAct" onclick="xajax_buscar(xajax.getFormValues('fromTipoAct'), 'lisTipoAct');">Buscar</button>
                            <button type="button" id="bstLimpTipoAct" name="bstLimpTipoAct" onclick="document.forms['fromTipoAct'].reset(); byId('bstTipoAct').click();">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
          </tr>
          <tr>
            <td><form id="frmListActivo" name="frmListActivo" style="margin:0"><div id="lstTipoActivo"></div></form></td>
          </tr>
          <tr>
            <td align="right">
            <hr />
            <button type="button" id="btnCerrarLstTpoAct" name="btnCerrarLstTpoAct" onclick="document.forms['fromTipoAct'].reset();" class="close">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cerrar</td></tr>
                </table>
            </button>
            </td>
          </tr>
        </table>
</div>
<script language="javascript">
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

	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
	var theHandle = document.getElementById("divFlotanteTitulo2");
	var theRoot   = document.getElementById("divFlotante2");
	Drag.init(theHandle, theRoot);
	
xajax_listadoSubSecciones(0,'descripcion_subseccion','ASC');
//xajax_listadoTipoActivo(0,'Descripcion','ASC');
xajax_cargaLstSeccion("", "buscar");	 
</script>