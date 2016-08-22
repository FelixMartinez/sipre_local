<?php
require_once("../connections/conex.php");
	
session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_actividad_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');//clase xajax

$xajax = new xajax();//Instanciando el objeto xajax

$xajax->configure('javascript URI', 'controladores/xajax/');//Configuranto la ruta del manejador de script

include("controladores/ac_crm_integrantes_equipo_list.php"); //contiene todas las funciones xajax
include("../repuestos/controladores/ac_iv_general.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: Sistema ERP :. .: Módulo de Crm :. - Equipos</title>
	<?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCrm.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
	<script>
    function validarEliminar(id_equipo){ 
    //LLAMA LA FUNCION ELIMINAR EN XAJAX
        if (confirm('Seguro desea eliminar este registro?') == true) {
            xajax_eliminarEquipo(id_equipo, xajax.getFormValues('frmListaConfiguracion'));
           }
        }
    //ELIMINA LOS INTEGRANTES DE UN GRUPO 
    function validarEliminarIntegrante(id_integrante_equipo, idEquipo){ 
		//LLAMA LA FUNCION ELIMINAR EN XAJAX
		if (confirm('Esta seguro que desea eliminar este integrante?') == true) {
			xajax_eliminarIntegrante(id_integrante_equipo, idEquipo);
		}
	}
		
    //LISTA LAS EMPRESAS
    function formListaEmpresa(valor, valor2) {
		document.forms['frmBuscarEmpresa'].reset();
		
		byId('hddObjDestino').value = valor;
		byId('hddNomVentana').value = valor2;
		
		byId('btnBuscarEmpresa').click();
		
		tituloDiv1 = 'Empresas';
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv1;
    }
	
    //SELECCIONA EL IDEQUIPO SELECIONADO	
    function selectEquipo(){
        var equipo = $('#txtIdEmpresa').val();
			$('#idEmpresaJefeEquipo').val(equipo);
		var tipoEquipo = $('#comboxTipoEquipo').val();
			$('#tipoEquipoJefeEquipo').val(tipoEquipo);
		return equipo;
	}
	
    //PARA VALIDAR FORMULARIO
	function validarForm() {
		if (validarCampo('txtIdEmpresa','t','') == true 
		&& validarCampo('txtNombreEquipo','t','') == true
		&& validarCampo('comboxTipoEquipo','t','listaExceptCero') == true
		&& validarCampo('idHiddJefeEquipo','t','') == true
		&& validarCampo('textJefeEquipo','t','') == true
		&& validarCampo('listEstatus','t','listaExceptCero') == true) {
			xajax_guadarFormEquipo(xajax.getFormValues('fomrEquipo'));
		}  else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtNombreEquipo','t','');
			validarCampo('comboxTipoEquipo','t','listaExceptCero');
			validarCampo('idHiddJefeEquipo','t','');
			validarCampo('textJefeEquipo','t','');
			validarCampo('listEstatus','t','listaExceptCero');
			//validarCampo('textJefeEquipo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_crm.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%"> <!--tabla principal-->
        <tr>
        	<td class="tituloPaginaCrm">Equipos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left"> <!--boton del formulario -->
                <tr>
                	<td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotanteEquipo" onclick="openImg(this); abrirNuevo(); ">
                            <button type="button" style="cursor:default" onclick="">
                                <table align="center" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td><img class="puntero" src="../img/iconos/ico_new.png" title="Editar"/></td>
                                        <td>&nbsp;</td>
                                        <td>Nuevo</td>
                                    </tr>
                                </table>
                            </button>
                        </a>
                    </td>
                </tr>
                </table> <!--fin boton del formulario-->
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right"> <!--contien el buscador-->			
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                    <td>
                        <button type="button" id="btnBuscar" onclick="xajax_buscarEquipo(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table> <!--fin del buscador-->
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaConfiguracion" name="frmListaConfiguracion" style="margin:0">
            	<div id="divListEquipo" style="width:100%"></div> <!--contien la consulta-->
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%"> <!--cotnie la tabla descripcion-->
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table > <!--cotnien descripcion -->
                        <tr>
                        	<td><img src="../img/iconos/ico_verde.gif" /></td><td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif" /></td><td>Inactivo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/user_suit.png" /></td><td>Agregar Integrante</td>
                        </tr>
                        </table> <!--fin de descripcion-->
                    </td>
				</tr>
				</table> <!--fin de la tabla descripcion-->
            </td>
        </tr>
        </table> <!--fin de la tabla principal-->
    </div> <!--fin del cuerpo de la pag-->
   
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<!--Contiene el formulario agregar equipo-->
<div id="divFlotanteEquipo" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="fomrEquipo" name="fomrEquipo" style="margin:0" onsubmit="return false;">
    <table border="0" width="560">
        <tr>
            <td>
                <table width="100%" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" rel="#divFlotante2" onclick="openImg(this); formListaEmpresa('Empresa', 'ListaEmpresa');">
                                <button type="button" id="btnInsertarEmp" name="btnInsertarEmp" style="cursor:default" title="Listar">
                                    <img src="../img/iconos/ico_pregunta.gif" />
                                </button>
                            </a>
                            </td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                    <td><input type="text" id="txtNombreEquipo" name="txtNombreEquipo" size="50"/></td>
                </tr>
                 <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Equipo de:</td>
                    <td id="tdTipoEquipo"> </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">
                        <span class="textoRojoNegrita">*</span>Jefe de Equipo:
                    </td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="idHiddJefeEquipo" name="idHiddJefeEquipo" readonly="readonly" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" rel="#divJefeEquipo" onclick="openImg(this);">
                                <button id="buttonJefeEquipo" name="buttonJefeEquipo" title="Listar" onclick="abrirJefeEquipo(); selectEquipo();" type="button" disabled='true'>
                                    <img src="../img/iconos/ico_pregunta.gif"/>
                                </button>
                            </a>
                            </td>
                            <td><input type="text" id="textJefeEquipo" name="textJefeEquipo" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Descripcion:</td>
                    <td><textarea name="areaEquipoDescripcion" id="areaEquipoDescripcion" cols="30" rows="2"></textarea></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                    <td>
                        <select name="listEstatus" id="listEstatus">
                            <option value="">[ Seleccione ]</option>
                            <option value="0">Inactivo</option>
                            <option value="1">Activo</option>
                        </select>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr />
        	<input type="hidden" id="hddidEquipo" name="hddidEquipo" />
        	<button type="button" id="subGuardar" name="subGuardar" onclick="validarForm();">
               <table align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_save.png"/></td>
                        <td>&nbsp;</td>
                        <td>Guardar</td>
                    </tr>
                </table>
            </button>
            <button type="button" id="butCerrarEquipo" name="butCerrar" class="close" onclick="cerrarNuevo();">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_error.gif"/></td>
                        <td>&nbsp;</td>
                        <td>Cancelar</td>
                    </tr>
                </table>
            </button></td>
        </tr>
    </table>
</form> 
</div>

<!--Listad de empresas-->
<div id="divFlotante2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
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
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">
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


<!--Agregar integrantes-->
<div id="divIntegrante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="tituloIntegrante" class="handle"><table><tr><td id="tdNombreGrupo" width="100%"></td></tr></table></div>
    
    <!---->
    <table border="0" id="tblListadoEmpresa" width="760">
    <tr>
        <td>
            <div class="wrap">
                <!--the tabs-->
                <ul class="tabs">
                    <li><a href="#">Integrantes del Grupo</a></li>
                    <li><a href="#">Agregar Integrantes</a></li>
                </ul>
                
                <!--tab "panes"-->
                <div class="pane">
                    <div id="divListIntegrantes"></div>
                </div>
                
                <!--tab "panes"-->
                <div class="pane">
                    <table border="0" id="tblListaEmpleado" width="100%">
                    <tr>
                        <td>
                        <form id="frmListaEmpleado" name="frmListaEmpleado" style="margin:0" onsubmit="return false;">
                            <table align="right">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                                <td><input type="text" name="textCriterio" id="textCriterio" onkeyup="byId('btoBuscar').click();"/></td>
                                <td>
                                    <button type="submit" id="btoBuscar" onclick="xajax_buscaEmpleado(xajax.getFormValues('frmListaEmpleado'));">Buscar</button>
                                    <button type="button" onclick="byId('btoBuscar').click();textCriterio.value='';">Limpiar</button>
                                </td>
                            </tr>
                            </table>
                            <input type="text" id="hiddIdEquipo" name="hiddIdEquipo"/>
                            <input type="text" id="hiddIdEmpresa" name="hiddIdEmpresa"/>
                            <input type="text" id="hiddTipoEquipo" name="hiddTipoEquipo"/>
                        </form>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div id="divListEmpleado" style="width:100%"></div>
                        </td>
                    </tr>
                    </table>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td align="right">
           <button type="button" id="btnCancelar2" name="btnCancelar2" class="close" onclick="cerrarIntegrante()">
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

<!--seleccioanr jefe de equipo-->
<div id="divJefeEquipo" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="tituloJefeEquipo" class="handle"><table><tr><td id="tdTituloJefeEquipo" width="100%">Seleccion de jefe de equipo</td></tr></table></div>
    
    <table border="0" width="760">
     <tr>
        <td>
        <form id="frmListaEmpleadoJefeEquipo" name="frmListaEmpleadoJefeEquipo" style="margin:0" onsubmit="return false;">
        	<table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="criterioJefeEquipo" name="criterioJefeEquipo" onkeyup="byId('butBuscarCriterio').click();"/></td>
                <td><input type="hidden" id="idEmpresaJefeEquipo" name="idEmpresaJefeEquipo" onkeyup="byId('butBuscarCriterio').click();"/></td>
                <td><input type="hidden" id="tipoEquipoJefeEquipo" name="tipoEquipoJefeEquipo" onkeyup="byId('butBuscarCriterio').click();"/></td>
                <td>
                    <button type="submit" id="butBuscarCriterio" name="butBuscarCriterio" onclick="xajax_buscaEmpleadoJefeEquipo(xajax.getFormValues('frmListaEmpleadoJefeEquipo'));">Buscar</button>
                    <button type="button" id="butLimpiaCriterio" name="butLimpiaCriterio" onclick="document.forms['frmListaEmpleadoJefeEquipo'].reset(); byId('butBuscarCriterio').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td id="tdLisJefeEquipo"></td>
    </tr>
    <tr>
        <td align="right">
            <button type="button" id="butCancelarJefeEquipo" name="butCancelar" onclick="cerrarJefeEquipo();" class="close">
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
	<script>
    //LLAMADO A FUNCIONES XAJAX
    xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
    xajax_listaEquipo(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
	xajax_comboxTipoEquipo('');
	
//ABRE NUEVO FORMULARIO
	function abrirNuevo($tipo = "") {
		document.getElementById("fomrEquipo").reset();
		
		//activarBoton();
		
		byId('hddidEquipo').value = "";
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtNombreEquipo').className = 'inputHabilitado';
		byId('areaEquipoDescripcion').className = 'inputHabilitado'; 
		byId('idHiddJefeEquipo').className = 'inputInicial';
		byId('textJefeEquipo').className = 'inputInicial';
		byId('listEstatus').className = 'inputInicial';
		$('#comboxTipoEquipo').attr('disabled', false);
		//openImg(document.getElementById('divFlotanteEquipo'));
		$("#divFlotanteEquipo").show(); 
		
			if($tipo == "editar"){
				byId('tdFlotanteTitulo').innerHTML = "Editar Equipo";
				
				} else {
					byId('tdFlotanteTitulo').innerHTML = "Agregar Equipo";
					activarBoton();
			
			//LE ASIGNO LOS VALOS DE EMPRESA A LOS CAMPO DE EMPRESA
			var idEmpresa = $("#lstEmpresa").val();
				if(!idEmpresa == ""){
					$('#txtIdEmpresa').val($("#lstEmpresa").val());
					$("#txtEmpresa").val($("#lstEmpresa option:selected").html());
					}
				}
		
	}

//ACTIVA EL BOTONO SELECCIONAR JEFE DE EQUIPO
	function activarBoton(){
	var tipoEquipo = document.getElementById("comboxTipoEquipo").selectedIndex;
	var buttonJefeEquipo = document.getElementById('buttonJefeEquipo');
	
	if(tipoEquipo == null || tipoEquipo == 0) {
		buttonJefeEquipo.disabled=true;
		//alert('en cero')
	} else {
		buttonJefeEquipo.disabled=false;
		//alert('algo')
		}
	
	} 

//ABRIR INTEGRANTES
	function abreIntegrante(){
		//openImg(document.getElementById('divIntegrante'));
		$("#divIntegrante").show();
	}

//ABRIR JEFE EQUIPO
	function abrirJefeEquipo(){
		var idEmpresa = $('#txtIdEmpresa').val(); //TOMA EL ID DE LA EMPRES QUE ESTA EN INPUT
			$('#divJefeEquipo').show();
		var tipoEquipo = $('#comboxTipoEquipo').val();
		xajax_listaEmpleadoJefeEquipo('','','',idEmpresa+'|'+'|'+tipoEquipo)
	}	

//CIERRA INTEGRANTES
	function cerrarIntegrante(){
		$("#divIntegrante").hide();
	}

//CERRAR JEFE EQUIPO
	function cerrarJefeEquipo(){
		$('#divJefeEquipo').hide();
		document.getElementById("frmListaEmpleadoJefeEquipo").reset(); 
	}

//CIERRA FORMULARIO
	function cerrarNuevo() {
		$("#divFlotanteEquipo").hide(); 
		
	}
	
	function limpiarCampo(){
		document.getElementById("idHiddJefeEquipo").value = "";
		document.getElementById("textJefeEquipo").value = "";

		}

//FUNCIONALIDAD DE LOS TABS
	$(function() {
		$("ul.tabs").tabs("> .pane");
	});
			
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotanteEquipo");
	Drag.init(theHandle, theRoot);//mueve el formulario
	
	var theHandle = document.getElementById("tituloIntegrante");
	var theRoot   = document.getElementById("divIntegrante");divJefeEquipo
	Drag.init(theHandle, theRoot);//mueve el formulario
	
	var theHandle = document.getElementById("tituloJefeEquipo");
	var theRoot   = document.getElementById("divJefeEquipo");
	Drag.init(theHandle, theRoot);//mueve el formulario
	
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