<?php
require_once("../connections/conex.php");
	
session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_seguimiento_historico_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');//clase xajax
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');//Configuranto la ruta del manejador de script
$xajax->configure( 'defaultMode', 'synchronous' ); 

include("controladores/ac_crm_reporte_vendedor_list.php"); //contiene todas las funciones xajax
include("../controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Control de Trafico</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>

    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCrm.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    																					
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script src="../js/highcharts/js/highcharts.js"></script>
	<script src="../js/highcharts/js/modules/exporting.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
<script>
function abrirFrom(idObj, forms, IdObjTitulo, valor, valor2){ 
	//alert(idObj+"\n"+forms+"\n"+IdObjTitulo+"\n"+valor+"\n"+valor2);
	document.forms[forms].reset();
	if(IdObjTitulo == "tdFlotanteTitulo"){	
		RecorrerForm("frmSeguimiento","text","class","inputHabilitado",["txtEmpresa","txtNombreEmpleado"]);
		xajax_asignarEmpresa(byId('lstEmpresa').value);
		xajax_asignarEmpleado('<?php echo $_SESSION['idEmpleadoSysGts']; ?>', byId('txtIdEmpresa').value);
		xajax_eliminarActSeguimiento(xajax.getFormValues('frmSeguimiento'));
		xajax_cargaLstEquipo('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
		if(valor == 0) {
			byId('hddIdPerfilProspecto').value = '';
			byId('hddIdClienteProspecto').value = '';
			byId('hddIdSeguimiento').value = '';
			byId('rdCliente').disabled = false;
			byId('rdProspecto').disabled = false;
			byId('rdProspecto').checked = true;
			byId('rdCliente').onclick = function() {
				abrirFrom(this,'frmBusCliente','tdFlotanteTitulo5', 3, 'tblLstCliente');
			}
			byId('rdProspecto').onclick = function() {
				abrirFrom(this,'frmBusCliente','tdFlotanteTitulo5', 1, 'tblLstCliente');
			}	
			var titulo = "Agregar Control de Trafico";
			xajax_cargarDatos(); 
		}else{
			var titulo = "Editar Control de Trafico";	
			xajax_cargarDatos(valor);
			byId('rdCliente').disabled = true;
			byId('rdProspecto').disabled = true;
		}	
	}else if (IdObjTitulo == "tdFlotanteTitulo2"){
		titulo = "Empresa";
		xajax_listaEmpresa(0,"","","");
	}else if (IdObjTitulo == "tdFlotanteTitulo3"){
		titulo = "Empleado";
	}else if (IdObjTitulo == "tdFlotanteTitulo4"){
		titulo = "Modelo de Interes";
		byId('txtIdEmpresaBuscarModelo').value =  byId('txtIdEmpresa').value;
		byId('txtEmpresaBuscarModelo').value =  byId('txtEmpresa').value;
		document.forms['frmModelo'].reset();
		byId('btnBuscarModelo').click();
		xajax_cargaLstMedio();
		xajax_cargaLstPlanPago();
	}else if (IdObjTitulo == "tdFlotanteTitulo5"){
		byId('lstTipoCuentaCliente').onchange = function() { selectedOption(this.id,valor); }
		xajax_listaCliente( 0, "","",byId('txtIdEmpresa').value+"||||"+valor);
		if(valor == 3){// CLIENTE
			$('.remover').remove();
			titulo = "Cliente";
			arrayElement = new Array(
				'txtIdEmpresa','hddIdEmpleado',
				'txtCompania','txtFechaNacimiento',
				'txtFechaUltAtencion','txtFechaUltEntrevista',
				'txtFechaProxEntrevista','txtUrbanizacionProspecto',
				'txtCalleProspecto','txtCasaProspecto',
				'txtMunicipioProspecto','txtCiudadProspecto',
				'txtEstadoProspecto','txtTelefonoProspecto',
				'txtOtroTelefonoProspecto','txtUrbanizacionComp',
				'txtCalleComp','txtCasaComp',
				'txtMunicipioComp','txtEstadoComp',
				'txtTelefonoComp','txtOtroTelefonoComp',
				'txtEmailComp');
			RecorrerForm('frmSeguimiento','text','class'  ,'inputInicial',arrayElement);
			arrayElement2 = new Array(
				'txtIdEmpresa','hddIdEmpleado',
				'txtCompania','txtFechaNacimiento',
				'txtFechaUltAtencion','txtFechaUltEntrevista',
				'txtFechaProxEntrevista','txtUrbanizacionProspecto',
				'txtCalleProspecto','txtCasaProspecto',
				'txtMunicipioProspecto','txtCiudadProspecto',
				'txtEstadoProspecto','txtTelefonoProspecto',
				'txtOtroTelefonoProspecto','txtUrbanizacionComp',
				'txtCalleComp','txtCasaComp',
				'txtMunicipioComp','txtEstadoComp',
				'txtTelefonoComp','txtOtroTelefonoComp',
				'txtEmailComp');
			RecorrerForm('frmSeguimiento','text','readOnly',true,arrayElement2);
			xajax_cargaLstEquipo("<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>","","Postventa");
		}else if (valor == 1) {//PROSPECTO
			$('.remover').remove();	
			titulo = "Prospecto";
			xajax_cargaLstEquipo('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
			RecorrerForm('frmSeguimiento','text','class','inputHabilitado',['txtEmpresa','txtNombreEmpleado']);
			RecorrerForm('frmSeguimiento','text','readOnly',false,['txtEmpresa','txtNombreEmpleado']);
		}
	}else if (IdObjTitulo == "tdFlotanteTitulo6"){
		titulo = "Historico";
		xajax_lstaHstPosibleCierre(0, "posicion_posibilidad_cierre", "ASC", valor);
		xajax_asignarEmpresa(byId('lstEmpresa').value, "divFlotante6");
	}
	openImg(idObj);
	byId(IdObjTitulo).innerHTML = titulo ;
}

function RecorrerForm(nameFrm,typeElemen,accion,valor,arrayElement){ 
	var frm = document.getElementById(nameFrm);
	var arrayIdElement= new Array();
	for (i=0; i < frm.elements.length; i++)	{// RECORRE LOS ELEMENTOS DEL FROM
		if(frm.elements[i].type == typeElemen){
			if(arrayElement != null){
				existe = arrayElement.indexOf(frm.elements[i].id) > -1;
				if(!existe){
					arrayIdElement.push(frm.elements[i].id);
				}
			}else{
				arrayIdElement.push(frm.elements[i].id);
			}
		}
	}
/*console.log(arrayIdElement);*/
	for(var indice in arrayIdElement){
		switch(accion){
			case "class": document.getElementById(arrayIdElement[indice]).className = valor;  break;
			case "readOnly": document.getElementById(arrayIdElement[indice]).readOnly = valor;  break;
			case "disabled": document.getElementById(arrayIdElement[indice]).disabled = valor;  break;
		}
	}
}	

function validarFrmSeguimiento(){
	RecorrerForm('frmSeguimiento','button','disabled',true);
	if (validarCampo('txtIdEmpresa','t','') == true
	&& validarCampo('txtEmpresa','t','') == true
	&& validarCampo('hddIdEmpleado','t','') == true
	&& validarCampo('txtNombreEmpleado','t','') == true
	&& validarCampo('txtNombreProspecto','t','') == true
	&& validarCampo('txtCedulaProspecto','t','') == true
	&& validarCampo('txtTelefonoProspecto','t','') == true
	&& validarCampo('txtEmailProspecto','t','') == true
	&& validarCampo('lstTipoProspecto','t','lista') == true) {
		xajax_guardarSeguimiento(xajax.getFormValues('frmSeguimiento'));
	} else {
		validarCampo('txtIdEmpresa','t','');
		validarCampo('txtEmpresa','t','');
		validarCampo('hddIdEmpleado','t','');
		validarCampo('txtNombreEmpleado','t','');
		validarCampo('txtNombreProspecto','t','');
		validarCampo('txtCedulaProspecto','t','');
		validarCampo('txtTelefonoProspecto','t','lista');
		validarCampo('txtEmailProspecto','t','lista');
		validarCampo('lstTipoProspecto','t','lista');
		RecorrerForm('frmSeguimiento','button','disabled',false);
		alert("Los campos señalados en rojo son requeridos");
		return false;
	}
}

function validarFrmModelo() {
	if (validarCampo('txtUnidadBasica','t','') == true
	&& validarCampo('lstMedio','t','lista') == true
	&& validarCampo('lstNivelInteres','t','lista') == true
	&& validarCampo('lstPlanPago','t','lista') == true) {
		xajax_insertarModelo(xajax.getFormValues('frmModelo'), xajax.getFormValues('frmSeguimiento'));
	} else {
		validarCampo('txtUnidadBasica','t','');
		validarCampo('lstMedio','t','lista');
		validarCampo('lstNivelInteres','t','lista');
		validarCampo('lstPlanPago','t','lista');
		
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
        <table width="100%" border="0"> <!--tabla principa-->
            <tr><td class="tituloPaginaCrm">Reporte por Vendedor</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
            	<td>
                	<table border="0" width="100%">
                    	<tr>
                            <td valign="top" align="left" width="46%">
                               <button type="button" onclick="xajax_exportarCliente(xajax.getFormValues('frmBuscar'));" style="cursor:default">
                            		<table align="center" cellpadding="0" cellspacing="0">
                            			<tr>
                            				<td>&nbsp;</td>
                            				<td><img src="../img/iconos/page_excel.png"/></td>
                            				<td>&nbsp;</td><td>XLS</td>
                            			</tr>
                            		</table>
                            	</button>
                            </td>
                            <td align="right" width="54%">
                            	<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                                    <table border="0" width="100%">
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Empresa:</td>
                                            <td id="tdlstEmpresa">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Vendedor:</td>
                                            <td id="tdLstVendedor">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Fecha Creacion</td>
                                            <td>
                                            	Desde: <input id="textDesdeCreacion" name="textDesdeCreacion" class="inputHabilitado" value="<?php echo date("d-m-Y") ?>" style="width:28%; text-align:center" />
                                                Hasta: <input id="textHastaCreacion" name="textHastaCreacion" class="inputHabilitado" value="<?php echo date("d-m-Y") ?>" style="width:28%; text-align:center" />
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="120">Criterio</td>
                                            <td>
                                            	<input id="textCriterio" name="textCriterio" class="inputHabilitado" style="width:52%;" onblur="byId('btnBuscar').click();" />
                                            </td>
                                            <td align="left">
                                                <button type="button" id="btnBuscar" onclick="xajax_buscarSeguimiento(xajax.getFormValues('frmBuscar'))">Buscar</button>
                                                <button type="button" id="btnLimpiar" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
                                            </td>
                                        </tr>
                                    </table>
                                </form>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr><td align="left" ><h2><?php echo date("l, F d Y"); ?></h2></td></tr>
            <tr>
            	<td>
                	<form id="frmLstSeguimiento" name="frmLstSeguimiento" onsubmit="return false;" style="margin:0">
                    	<div id="divLstSeguimiento"></div>
                    </form>
                </td>
            </tr>
            <tr align="right">
            </tr>
        </table>

    </div> <!-- fin contenedor interno-->

    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div> <!--fin del contenedor general-->
</body>
</html>
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmSeguimiento" name="frmSeguimiento" style="margin:0" onsubmit="return false;">
	<div class="pane" style="max-height:520px; overflow:auto; width:960px;">
        <table border="0" id="tblProspecto" width="100%">
        <tr>
            <td>
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" class="" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirFrom(this,'frmBusEmpresa','tdFlotanteTitulo2', '', 'tblListEmpresa')">
                                        <button id="btnAsigEmp" name="btnAsigEmp" type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empleado:</td>
                            <td width="88%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarEmpleado" rel="#divFlotante3" onclick="abrirFrom(this,'frmBusEmpelado','tdFlotanteTitulo3', '', 'tblListEmpleado')">
                                        <button id="btnLstEmpleado" name="btnLstEmpleado" type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo De Control de Trafico:</td>
                            <td width="88%"> 
                                Cliente <input type="radio" id="rdCliente" value="3" name="rdTipo" rel="#divFlotante5" />  
                                Prospecto <input type="radio" id="rdProspecto" value="1" name="rdTipo" rel="#divFlotante5" />
                                
                            </td >
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                    <fieldset><legend class="legend">Datos Generales</legend>
                        <table border="0" width="100%">
                        <tr>
                            <td width="11%"></td>
                            <td width="26%"></td>
                            <td width="11%"></td>
                            <td width="25%"></td>
                            <td width="11%"></td>
                            <td width="16%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo:</td>
                            <td>
                                <select id="lstTipoProspecto" name="lstTipoProspecto" class="inputHabilitado" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">Natural</option>
                                    <option value="2">Juridico</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanClienteCxC; ?>:</td>
                            <td colspan="3" nowrap="nowrap">
                            <div style="float:left">
                                <input type="text" id="txtCedulaProspecto" name="txtCedulaProspecto" maxlength="18" size="20" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCI; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                            <td><input type="text"  id="txtNombreProspecto"name="txtNombreProspecto" size="25" maxlength="50"/></td>
                            <td align="right" class="tituloCampo">Apellido:</td>
                            <td><input type="text" id="txtApellidoProspecto" name="txtApellidoProspecto" size="25" maxlength="50"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" width="100%">
                        <tr>
                            <td valign="top" width="50%">
                            <fieldset><legend class="legend">Dirección Particular</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Urbanización:</td>
                                    <td colspan="3"><input type="text" name="txtUrbanizacionProspecto" id="txtUrbanizacionProspecto" style="width:99%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Calle / Av.:</td>
                                    <td><input type="text" name="txtCalleProspecto" id="txtCalleProspecto" style="width:97%"/></td>
                                    <td align="right" class="tituloCampo">Casa / Edif.:</td>
                                    <td><input type="text" name="txtCasaProspecto" id="txtCasaProspecto" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><?php echo $spanMunicipio; ?>:</td>
                                    <td><input type="text" name="txtMunicipioProspecto" id="txtMunicipioProspecto" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Ciudad:</td>
                                    <td><input type="text" name="txtCiudadProspecto" id="txtCiudadProspecto" style="width:97%"/></td>
                                    <td align="right" class="tituloCampo"><?php echo $spanEstado; ?>:</td>
                                    <td><input type="text" name="txtEstadoProspecto" id="txtEstadoProspecto" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Teléfono:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtTelefonoProspecto" id="txtTelefonoProspecto" size="16" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                    <td align="right" class="tituloCampo">Otro Telf.:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtOtroTelefonoProspecto" id="txtOtroTelefonoProspecto" size="16" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanEmail; ?>:</td>
                                    <td colspan="3">
                                    <div style="float:left">
                                        <input type="text" name="txtEmailProspecto" id="txtEmailProspecto" size="30" maxlength="50"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                </table>
                            </fieldset>
                            </td>
                            <td valign="top" width="50%">
                            <fieldset><legend class="legend">Dirección de Trabajo</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Urbanización:</td>
                                    <td colspan="3"><input type="text" name="txtUrbanizacionComp" id="txtUrbanizacionComp" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Calle / Av.:</td>
                                    <td><input type="text" name="txtCalleComp" id="txtCalleComp" style="width:97%"/></td>
                                    <td align="right" class="tituloCampo">Casa / Edif.:</td>
                                    <td><input type="text" name="txtCasaComp" id="txtCasaComp" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><?php echo $spanMunicipio; ?>:</td>
                                    <td><input type="text" name="txtMunicipioComp" id="txtMunicipioComp" style="width:97%"/></td>
                                    <td align="right" class="tituloCampo"><?php echo $spanEstado; ?>:</td>
                                    <td><input type="text" name="txtEstadoComp" id="txtEstadoComp" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Teléfono:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtTelefonoComp" id="txtTelefonoComp" size="16" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                    <td align="right" class="tituloCampo">Otro Telf.:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtOtroTelefonoComp" id="txtOtroTelefonoComp" size="16" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><?php echo $spanEmail; ?>:</td>
                                    <td colspan="3">
                                    <div style="float:left">
                                        <input type="text" name="txtEmailComp" id="txtEmailComp" size="30" maxlength="50"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                </table>
                            </fieldset>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#">Datos Adicionales</a></li>
                        <li><a href="#">Modelo de Interes</a></li>
                        <li><a href="#">Entrevista</a></li>
                        <li><a href="#">Observaci&oacute;n</a></li>
                        <li><a href="#">Asignacion</a></li>
                    </ul>
                    
                    <!-- tab "panes" DATOS ADICIONALES-->
                    <div class="pane">
                        <table border="0" width="100%"> <!--sf/*-/*--->
                        <tr align="left">
                            <td align="right" class="tituloCampo">Compañia:</td>
                            <td><input type="text" name="txtCompania" id="txtCompania" maxlength="50"/></td>
                            <td align="right" class="tituloCampo">Puesto:</td>
                            <td id="tdLstPuesto" align="left">
                            	<select>
                                    <option value="">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo">Título:</td>
                            <td id="tdLstTitulo" align="left">
                            	<select>
                                    <option value="">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                         <tr align="left">
                            <td align="right" class="tituloCampo">Nivel de Influencia:</td>
                            <td id="tdLstNivelInfluencia">
                            	<select>
                                    <option value="">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo">Sector:</td>
                            <td id="tdLstSector">
                            	<select>
                                    <option value="">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo">Estatus:</td>
                            <td id="td_select_estatus"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="14%">Estado Civil:</td>
                            <td id="tdlstEstadoCivil" width="19%">
                                <select size="1" name="lstEstadoCivil" id="lstEstadoCivil">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="14%">Sexo:</td>
                            <td width="19%">
                                <input type="radio" name="rdbSexo" id="rdbSexoM" value="M"/>M
                                <input type="radio" name="rdbSexo" id="rdbSexoF" value="F"/>F
                            </td>
                            <td align="right" class="tituloCampo" width="14%">Fecha Nacimiento:</td>
                            <td width="20%"><input type="text" id="txtFechaNacimiento" name="txtFechaNacimiento" size="12" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Clase Social:</td>
                            <td>
                                <select name="lstNivelSocial" id="lstNivelSocial" class="inputHabilitado">
                                    <option value="">[ Seleccione ]</option>
                                    <option value="3">Alta</option>
                                    <option value="2">Media</option>
                                    <option value="1">Baja</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" rowspan="2">Observación:</td>
                            <td colspan="4" rowspan="2"><textarea id="txtObservacion" name="txtObservacion" class="inputHabilitado" cols="45" rows="2"></textarea></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Motivo de Rechazo:</td>
                            <td id="tdLstMotivoRechazo">
                            	<select>
                                    <option value="">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Posibilidad de cierre:</td>
                            <td colspan="" id="tdLstPosibilidadCierre">
                            	<select>
                                    <option value="">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td colspan="4">
                            	<img id="imgPosibleCierrePerfil" width="80" height="80"/>
                            </td>
                        </tr>
                        </table>
                    </div>
                    
                    <!-- tab "panes" MODELO DE INTERES-->
                    <div class="pane">
                        <table border="0" width="100%">
                        <tr align="left">
                            <td colspan="6">
                                <a class="modalImg" id="aNuevoModelo" rel="#divFlotante4" onclick="abrirFrom(this,'frmBuscarModelo','tdFlotanteTitulo4', '', 'tblListModelInteres')">
                                    <button id="btnAgregarModelo" name="btnAgregarModelo" type="button">
                                        <table align="center" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td><img src="../img/iconos/add.png"/></td>
                                                <td>&nbsp;</td>
                                                <td>Agregar</td>
                                            </tr>
                                        </table>
                                    </button>
                                </a>
                            </td>
                        </tr>
                        <tr align="center" class="tituloColumna">
                            <td></td>
                            <td width="40%">Modelo</td>
                            <td width="15%">Precio</td>
                            <td width="15%">Medio</td>
                            <td width="15%">Niv. Interés</td>
                            <td width="15%">Plan Pago</td>
                        </tr>
                        <tr id="trItmPieModeloInteres"></tr>
                        </table>
                        <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                    </div>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="16%">Ultima Atención:</td>
                            <td width="17%"><input type="text" id="txtFechaUltAtencion" name="txtFechaUltAtencion" autocomplete="off" size="10" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo" width="16%">Ultima Entrevista:</td>
                            <td width="17%"><input type="text" id="txtFechaUltEntrevista" name="txtFechaUltEntrevista" autocomplete="off" size="10" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo" width="16%">Próxima Entrevista:</td>
                            <td width="18%"><input type="text" id="txtFechaProxEntrevista" name="txtFechaProxEntrevista" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </div>
                    
                    <!-- tab "panes" SEGUIMIENTO-->
                    <div class="pane">
                    	<table border="0" width="100%">
                            <tr align="left">
                                <td align="right" class="tituloCampo">Observacion:</td>
                                <td colspan="4">
                                	<textarea id="textAreaObservacion" name="textAreaObservacion" class="inputHabilitado" rows="2" cols="80" ></textarea>
                                </td>
                            </tr>
                            <!--<tr align="center" class="tituloColumna">
                                <td width=""></td>
                                <td width="">id</td>
                                <td width="">Nombre Actividad</td>
                                <td width="">Tipo Activiada</td>
                                <td width="">Posicion</td>
                            </tr>
                        	<tr id="trItmPieActividadSeguimiento"></tr>-->
                        </table>
                    </div>    
                    <!-- tab "panes" ASIGNACION-->
                    <div class="pane">
                    	<table border="0" width="100%">
                            <tr align="left">
                                <td>
                                	<table border="0" width="100%">
                                    	<tr>
                                        	 <td id="tdTipoEquipo" align="right" class="tituloCampo" width="120"></td>
                                             <td id="tdLstEquipo" align="left"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                            	<td>
                                    <fieldset>
                                        <legend class="legend" >Integrante Del Equipo</legend>
                                        <table border="0" width="100%">
                                            <tr align="center" class="tituloColumna">
                                                <td width=""></td>
                                                <td width="">id</td>
                                                <td width="">Nombre Vendedor</td>
                                                <td width="">Cargo</td>
                                                <td width="">Departamento</td>
                                                <td width=""></td>
                                            </tr>
                                            <tr id="trItmIntegrante"></tr>
                                        </table>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                            	<td colspan="6">
                                    <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%"> 
                                        <tr>
                                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                            <td align="center">
                                                <table>
                                                    <tr>
                                                        <td><img src="../img/iconos/user_suit.png" /></td><td>Jefe de Equipo</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>                    
                </div>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
            	<input type="hidden" name="hddIdPerfilProspecto" id="hddIdPerfilProspecto" readonly="readonly"/>
                <input type="hidden" name="hddIdClienteProspecto" id="hddIdClienteProspecto" readonly="readonly"/>
                <input type="hidden" name="hddIdSeguimiento" id="hddIdSeguimiento" readonly="readonly"/>
                <!--<button type="button" id="btnGuardarProspecto" name="btnGuardarProspecto" onclick="validarFrmSeguimiento();">Guardar</button> -->
                <button type="button" id="btnCancelarProspecto" name="btnCancelarProspecto" class="close" onclick="">Salir</button> 
            </td>
        </tr>
        </table>
	</div>
</form>    
</div>
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table width="760" id="tblListEmpresa" >
    	<tr>
        	<td align="right">
            	<form id="frmBusEmpresa" name="frmBusEmpresa" style="margin:0" onsubmit="return false;">
                	<table>
                    	<tr>
                        	<td class="tituloCampo" width="120" align="right">Criterio</td>
                            <td><input id="textCriterio" name="textCriterio" class="inputHabilitado" width="50%" onblur="byId('btnBuscarEmpresa').clik();"/></td>
                        </tr>
                        <tr align="right">
                        	<td colspan="2">
                            	<button id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="">Buscar</button>
                                <button id="btnLimpiarEmpresa" name="btnLimpiarEmpresa" onblur="byId('btnBuscarEmpresa').clik();document.forms['frmBusEmpresa'].reset();">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr><td id="tdListEmpresa"></td></tr>
        <tr>
            <td align="right"><hr />
            	<button id="btnCerrarEmp" name="btnCerrarEmp" class="close">Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td></tr></table></div>
    <table width="760" id="tblListEmpleado" >
    	<tr>
        	<td align="right">
            	<form id="frmBusEmpelado" name="frmBusEmpelado" style="margin:0" onsubmit="return false;">
                	<table>
                    	<tr>
                        	<td class="tituloCampo" width="120" align="right">Criterio</td>
                            <td><input id="textCriterio" name="textCriterio" class="inputHabilitado" width="50%" onblur="byId('btnBuscarEmpleado').clik();"/></td>
                        </tr>
                        <tr align="right">
                        	<td colspan="2">
                            	<button id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="">Buscar</button>
                                <button id="btnLimpiarEmpleado" name="btnLimpiarEmpleado" onblur="byId('btnBuscarEmpleado').clik();document.forms['frmBusEmpelado'].reset();">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr><td id="tdListEmpleado"></td></tr>
        <tr>
            <td align="right"><hr />
            	<button id="btnCerrarEmpleado" name="btnCerrarEmpleado" class="close">Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo4" class="handle"><table><tr><td id="tdFlotanteTitulo4" width="100%"></td></tr></table></div>
    <div id="tblModelo" style="max-height:520px; overflow:auto; width:960px;">
        <table border="0" width="100%" id="tblListModelInteres">
        <tr>
            <td>
            <form id="frmBuscarModelo" name="frmBuscarModelo" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="100">Empresa:</td>
                        <td>
                            <input type="text" id="txtIdEmpresaBuscarModelo" name="txtIdEmpresaBuscarModelo" size="5" readonly="readonly"/>
                            <input type="text" id="txtEmpresaBuscarModelo" name="txtEmpresaBuscarModelo" size="45" readonly="readonly"/>
                        </td>
                    </tr>
                    <tr align="left"> 
                        <td align="right" class="tituloCampo" width="100">Criterio:</td>
                        <td><input type="text" id="txtCriterioBuscarModelo" name="txtCriterioBuscarModelo" class="inputHabilitado" size="60" onkeyup="byId('btnBuscarModelo').click();"/></td>
                    </tr>
                    <tr align="right">   
                        <td colspan="2">
                            <button type="button" id="btnBuscarModelo" name="btnBuscarModelo" onclick="xajax_buscarModelo(xajax.getFormValues('frmBuscarModelo'), xajax.getFormValues('frmSeguimiento'));">Buscar</button>
                            <button type="button" onclick="document.forms['frmBuscarModelo'].reset(); byId('btnBuscarModelo').click();">Limpiar</button>
                        </td>
                    </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
                <div id="divListaModelo" style="width:100%"></div>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmModelo" name="frmModelo" style="margin:0" onsubmit="return false;">
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Unidad Básica:</td>
                    <td colspan="3">
                        <input type="text" id="txtUnidadBasica" name="txtUnidadBasica" readonly="readonly" size="65"/>
                    </td>
                    <td align="right" class="tituloCampo">
                        <?php echo $spanPrecioUnitario; ?>:
                        <br><span class="textoNegrita_10px">(Sin Incluir Impuestos)</span>
                    </td>
                    <td><input type="text" id="txtPrecioUnidadBasica" name="txtPrecioUnidadBasica" readonly="readonly" size="16" style="text-align:right"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Fuente de Información:</td>
                    <td id="tdlstMedio" width="18%"></td>
                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Nivel de Interes:</td>
                    <td width="18%">
                        <select id="lstNivelInteres" name="lstNivelInteres">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="3">Alto</option>
                            <option value="2">Medio</option>
                            <option value="1">Bajo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Plan de Pago:</td>
                    <td id="tdlstPlanPago" width="16%"></td>
                </tr>
                <tr>
                    <td align="right" colspan="6"><hr>
                        <input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica" readonly="readonly"/>
                        <button type="button" id="btnGuardarModelo" name="btnGuardarModelo" onclick="validarFrmModelo();">Guardar</button>
                        <button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        </table>
	</div>
</div> 

<div id="divFlotante5" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo5" class="handle"><table><tr><td id="tdFlotanteTitulo5" width="100%"></td></tr></table></div>
    <table width="100%" id="tblLstCliente"  width="760"> 
        <tr>
            <td>
                <form id="frmBusCliente" name="frmBusCliente" style="margin:0" onsubmit="return false;">
                    <table align="right" border="0">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Tipo de Pago:</td>
                            <td>
                                <select id="lstTipoPago" name="lstTipoPago" class="inputHabilitado" onchange="byId('btnBuscarCliente').click();" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="no">Contado</option>
                                    <option value="si">Crédito</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="120">Estatus:</td>
                            <td>
                                <select id="lstEstatusBuscar" name="lstEstatusBuscar" class="inputHabilitado" onchange="byId('btnBuscarCliente').click();">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option selected="selected" value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Paga Impuesto:</td>
                            <td>
                                <select id="lstPagaImpuesto" name="lstPagaImpuesto" class="inputHabilitado" onchange="byId('btnBuscarCliente').click();">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="0">No</option>
                                    <option value="1">Si</option>
                                </select>
                            </td>
                             <td align="right" class="tituloCampo">Ver:</td>
                            <td>
                                <select id="lstTipoCuentaCliente" name="lstTipoCuentaCliente">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">Prospecto</option>
                                    <option  value="3">Prospecto Aprobado</option>
                                   <option value="2">Cliente Sin Prospectación</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Criterio:</td>
                            <td colspan="3"><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" style="width:80%" onkeyup="byId('btnBuscarCliente').click();"/></td>
                        </tr>
                        <tr align="right">
                            <td colspan="5">                       
                                <button type="button" id="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBusCliente'),xajax.getFormValues('frmSeguimiento'));">
                                Buscar</button>
                                <button type="button" onclick="document.forms['frmBusCliente'].reset(); byId('btnBuscarCliente').click();">
                                Limpiar</button>
                            </td>
                        </tr>
                    </table>
                
                </form>
            &nbsp;</td>
        </tr>
        <tr>
        	<td><div id="divCliente"></div></td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
        	<td align="right"><hr />
            	<button type="button" id="btnCerraCliente" name="btnCerraCliente" class="close"> Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<!--posible cierre-->
<div id="divFlotante6" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo6" class="handle"><table><tr><td id="tdFlotanteTitulo6" width="100%"></td></tr></table></div>
    <table id="tblLstPosibleCierre" border="0" width="760"> 
        <tr align="right">
            <td>
                <form id="frmBusPosibleCierre" name="frmBusPosibleCierre" style="margin:0" onsubmit="return false;"> 
                	<!--<table width="50%" border="0">
                    	<tr>
                        	<td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                        	<td class="tituloCampo" align="right" width="120">Empresa</td>
                            <td>
                                <table cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td><input type="text" style="text-align:center;" size="6" onblur="" name="textIdEmpresaPosibleCierre" id="textIdEmpresaPosibleCierre"></td>
                                        <td><input type="text" size="45" readonly="readonly" name="textEmpresaPosibleCierre" id="textEmpresaPosibleCierre"></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right">
                        	<td colspan="2">
                            	<input id="textHddIdEmpresa" name="textHddIdEmpresa" type="hidden">
                            	<button id="btnBusPosibleCierre" name="btnBusPosibleCierre" onclick="xajax_buscarPosibleCierre(xajax.getFormValues('frmBusPosibleCierre'))">Buscar</button>
                                <button id="btnLimPosibleCierre" name="btnLimPosibleCierre" onclick="document.forms['frmBusPosibleCierre'].reset();byId('btnBusPosibleCierre').click();xajax_asignarEmpresa(byId('lstEmpresa').value, 'divFlotante6');">Limpiar</button>
                            </td>
                        </tr>
                        <tr>
                        	<td colspan="2">&nbsp;</td>
                        </tr>
                    </table>-->
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#">Posibilidad de Cierre</a></li>
                        <li><a href="#">Actividades</a></li>
                    </ul>
                    
                    <!-- tab "panes" DATOS ADICIONALES-->
                    <div class="pane">
	                    <div id="divHstPosibilidadCierre"></div>
                    </div>
                     <!-- tab "panes" DATOS ADICIONALES-->
                    <div class="pane">
                    	<div id=""></div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
            <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                <tr>
                    <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><input type="checkbox" checked="checked" disabled="disabled"></td><td>Estatus Inicial</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/aprob_jefe_taller.png"></td><td>Finaliza control de trafico</td>
                        </tr>
                        </table>
                    </td>
                </tr>
            </table>            
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td align="right"><hr />
                <button type="button" id="btnCerrafrmPosibleCierre" name="btnCerrafrmPosibleCierre" class="close"> Cerrar</button>
            </td>
        </tr>
    </table>
</div>


<script>
window.onload = function(){
	jQuery(function($){
		$("#txtFechaNacimiento").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaUltAtencion").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaUltEntrevista").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaProxEntrevista").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaNacimiento",
		dateFormat:"%d-%m-%Y",
		cellColorScheme :"bananasplit",
	});//textDesdeCreacion
	new JsDatePick({
		useMode:2,
		target:"txtFechaUltAtencion",
		dateFormat:"%d-%m-%Y",
		cellColorScheme :"bananasplit",
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaUltEntrevista",
		dateFormat:"%d-%m-%Y",
		cellColorScheme :"bananasplit",
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaProxEntrevista",
		dateFormat:"%d-%m-%Y",
		cellColorScheme :"bananasplit",
	});
}

xajax_cargaLstVendedor('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>','onchange=\"xajax_cargaLstVendedor(this.value); byId(\'btnBuscar\').click();\"');
xajax_listaEmpleado(0,"","","");
xajax_lstSeguimiento(0,"seguimiento.id_seguimiento","ASC","<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||||"+"<?php echo date("Y-m-d"); ?>"+"|"+"<?php echo date("Y-m-d"); ?>");

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
// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

new JsDatePick({
	useMode:2,
	target:"textHastaCreacion",
	cellColorScheme :"bananasplit",
	dateFormat:"%d-%m-%Y"
});

new JsDatePick({
	useMode:2,
	target:"textDesdeCreacion",
	cellColorScheme :"bananasplit",
	dateFormat:"%d-%m-%Y"
});

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

</script>