<?php
require_once ("../connections/conex.php");

@session_start();

include ("../inc_sesion.php");

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_pagos_list.php");

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Retenciones</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css">

        <!--    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
    <style type="text/css">
	.root {
		background-color:#FFFFFF;
		border:6px solid #999999;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:11px;
		max-width:1050px;
		position:absolute;
	}
	
	.handle {
		padding:3px;
		background-color:#990000;
		color:#FFFFFF;
		font-weight:bold;
		cursor:move;
	}
        
        #tdFacturas, #tdNotaCargo, #tdBeneficiarios, #tdProveedores{
            -webkit-border-top-left-radius: 10px;
            -webkit-border-top-right-radius: 10px;
            -moz-border-radius-topleft: 10px;
            -moz-border-radius-topright: 10px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;

            border-color:#CCCCCC;                                  
	}
	</style>
<script>
window.onload = function(){
	
    new JsDatePick({
        useMode:2,
        target:"txtFecha",
        dateFormat:"%d-%m-%Y",
        cellColorScheme:"red"
    });
    
    new JsDatePick({
        useMode:2,
        target:"txtFecha1",
        dateFormat:"%d-%m-%Y",
        cellColorScheme:"red"
    });

};

function excel(){
    tipoDocumento = $("input[type='radio'][name='buscarDocumento']:checked").val();
    if(isNaN(tipoDocumento) || tipoDocumento == ""){
        return alert("Se debe seleccionar tipo de documento a generar");
    }
    window.open("reportes/te_pagos_excel.php?empresa=" + document.getElementById('hddIdEmpresa').value + "&proveedor=" + document.getElementById('hddBePro').value + "&fecha1=" + document.getElementById('txtFecha').value + "&fecha2=" + document.getElementById('txtFecha1').value + "&tipoDocumento=" + tipoDocumento + "&txtBusq=" + document.getElementById('txtBusq').value);
}
</script>
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include('banner_tesoreria.php'); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaTesoreria">Listado Pagos<br/></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <button type="button" id="btnExportar" onclick="excel();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
                        </td>
                    </tr>
                </table>
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                            <td><button type="button" id="btnListEmpresa" name="btnListEmpresa" onclick="xajax_listEmpresa();" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                        </tr>
                        </table>
                    </td>
                    
                    <td align="right" class="tituloCampo" width="120">Proveedor / Beneficiario:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input style="width:30px" type="text" readonly="readonly" name="hddBePro" id="hddBePro"/> <input name="txtBePro" type="text" id="txtBePro" size="30" readonly="readonly"/><input type="hidden" name="hddSelBePro" id="hddSelBePro"/></td>
                            <td><button type="button" id="btnSeleccionearBP" name="btnSeleccionearBP" title="Seleccionar Proveedor / Beneficiario" 
                                onclick="xajax_listarBeneficiarios1();
                                document.getElementById('divFlotante1').style.display = '';
                                centrarDiv(document.getElementById('divFlotante1'));
                                document.getElementById('tdBeneficiarios').className = 'rafktabs_titleActive';
                                document.getElementById('tdProveedores').className = 'rafktabs_title';
                                
                                //si cierra y abre no muestra el buscador input correcto
                                document.getElementById('txtCriterioBusqProveedor').style.display='none';
                                document.getElementById('txtCriterioBusqBeneficiario').style.display='';

                                document.getElementById('buscarProv').value = '2';//beneficiario
                                
                                 document.getElementById('tdProveedores').onclick = function(){
                                   xajax_buscarCliente1(xajax.getFormValues('frmBuscarCliente'),1)
                                    document.getElementById('tdBeneficiarios').className = 'rafktabs_title';
                                    document.getElementById('tdProveedores').className = 'rafktabs_titleActive';
                                    document.getElementById('txtCriterioBusqProveedor').style.display='';
                                    document.getElementById('txtCriterioBusqBeneficiario').style.display='none';
                                    document.getElementById('buscarProv').value = '1';//proveedor
                                    };
                                    
                                 document.getElementById('tdBeneficiarios').onclick = function(){
                                    xajax_buscarCliente1(xajax.getFormValues('frmBuscarCliente'),0);
                                    document.getElementById('tdBeneficiarios').className = 'rafktabs_titleActive';
                                    document.getElementById('tdProveedores').className = 'rafktabs_title';
                                    document.getElementById('txtCriterioBusqProveedor').style.display='none';
                                    document.getElementById('txtCriterioBusqBeneficiario').style.display='';
                                    document.getElementById('buscarProv').value = '2';//beneficiario
                                    };">
                                <img src="../img/iconos/ico_pregunta.gif"/>
                                </button></td>	
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Fecha Registro:</td>
                    <td align="left">
                        <table cellspacing="0" cellpadding="0">
                            <tbody>
                                <tr>
                                    <td>
                                        Desde:<input type="text" value="<?php echo date("01-m-Y"); ?>" readonly="readonly" id="txtFecha" name="txtFecha" style="width:100px" />
                                        Hasta:<input type="text" value="<?php echo date("t-m-Y"); ?>" readonly="readonly" id="txtFecha1" name="txtFecha1" style="width:100px" />
                                    </td>                                    
                                </tr>
                           </tbody>
                        </table>
                    </td>                                        
                    
		</tr>
                <tr align="left">
                    
                    <td align="right" class="tituloCampo" width="120">Tipo Documento:</td>
                    <td>
                        <input type="radio" name="buscarDocumento" id="buscarFacturas" value="1" checked="checked" class="puntero" /><label for="buscarFacturas" class="puntero">Facturas</label>
                        <input type="radio" name="buscarDocumento" id="buscarNotas" value="2" class="puntero" /><label for="buscarNotas" class="puntero">Notas de Cargo</label>
                    </td>
                    
                    <td width="120" align="right" class="tituloCampo">Nro. Documento:</td>
                    <td align="left"><input type="text" id="txtBusq" name="txtBusq"/></td>
                    
                    <td align="right">
                            <button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPagos(xajax.getFormValues('frmBuscar'));">Buscar</button>
                            <button type="button" onclick="document.forms['frmBuscar'].reset(); document.getElementById('hddIdEmpresa').value = ''; document.getElementById('btnBuscar').click();">Limpiar</button>
                    </td>                        
                    
                </tr>		
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListadoPagos"></td>        	
        </tr>
        </table>
    </div>
    
    <div class="noprint">
	<?php include("pie_pagina.php") ?>
    </div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%">Seleccionar Beneficario / Proveedor</td></tr></table></div>
    
    <table id="tblBeneficiariosProveedores" border="0" width="700px">
    <tr>
    	<td>
             	<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdBeneficiarios" width="120px">Beneficiarios</td>
                        <td class="rafktabs_title" id="tdProveedores" width="120px">Proveedores</td>
		            </tr>
                      <tr>
                	<td align="right" class="tituloCampo" width="15">Criterio:</td>
                	<td>
                        <input type="hidden" id="buscarProv" name="buscarProv" value="2" />
                    	<input type="text" id="txtCriterioBusqBeneficiario" name="txtCriterioBusqBeneficiario" onkeyup="document.getElementById('tdBeneficiarios').onclick()" style="display:"/>
                        <input type="text" id="txtCriterioBusqProveedor" name="txtCriterioBusqProveedor" onkeyup="document.getElementById('tdProveedores').onclick()" style="display:none"/>
					</td>
                        <td><button type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente1(xajax.getFormValues('frmBuscarCliente'));" >Buscar...</button></td>
                

                </tr>
					</table>
				</td>
            </tr>
            <tr>
                <td class="rafktabs_panel" id="tdContenido" style="border:0px;"></td>
            </tr>
            </table></form>
        </td>
    </tr>
	<tr>
    	<td align="right">
			<hr>
                            <button type="button" onclick="document.getElementById('divFlotante1').style.display='none';" >Cancelar</button>
		</td>
    </tr>
    </table>
  
</div>


<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
        <table border="0" id="tblListados2" style="display:none" width="610">
        <tr>
            <td id="tdDescripcionArticulo">
            </td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr />
                <button type="button" id="" name="" onclick="document.getElementById('divFlotante2').style.display='none';" >Cancelar</button>
            </td>
        </tr>
        </table>
</div>


<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo1");
	var theRoot   = document.getElementById("divFlotante1");
	Drag.init(theHandle, theRoot);
        
        var theHandle = document.getElementById("divFlotanteTitulo2");
	var theRoot   = document.getElementById("divFlotante2");
	Drag.init(theHandle, theRoot);
        
</script>

<script>
xajax_asignarEmpresa('');
xajax_listadoPagos(0,'','','' + '|' + '-1' + '|' + '' + '|' + '-1');
</script>