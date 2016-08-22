<?php
require_once ("../connections/conex.php");
/* Validación del Módulo */
//include('../inc_sesion.php');
//validaModulo("an_modificar_vehiculo");
/* Fin Validación del Módulo */

@session_start();

//require_once('clases/rafkLista.php');
$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_te_resumen_cuenta.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Resumen de Cuentas</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('controladores/xajax/'); ?>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../tesoreria/clases/styleRafkLista.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
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
	
	.handle {
		padding:3px;
		background-color:#990000;
		color:#FFFFFF;
		font-weight:bold;
		cursor:move;
	}
        
       .acc-section, h3{
            -webkit-box-shadow: 0px 10px 5px 0px rgba(50, 50, 50, 0.75);
            -moz-box-shadow:    0px 10px 5px 0px rgba(50, 50, 50, 0.75);
            box-shadow:         0px 10px 5px 0px rgba(50, 50, 50, 0.75);
        }
        h3{
            padding: 0px;
            margin: 0px;
            border: 0px;
        }
        #acc{
            border: 0px;
        }
	
	</style>
</head>
<body>
    <div id="divGeneralPorcentaje">
        <div class="noprint" align="center">
            <?php include ('banner_tesoreria.php'); ?>
        </div>
        <br />
    <table width="100%">
        <tr>
            <td class="tituloPaginaTesoreria" colspan="2" id="tdResumenCuenta">Resumen de Cuenta<input type="hidden" id="hddIdCuenta" name="hddIdCuenta" value="<?php echo $_GET['cast']; ?>" /></td>
        </tr>
     </table>
	 <br /><br /><br />
    <table align="center" width="100%">
    <tr>   
        <td width="50%" valign="top">   
            <div id="divInfo">   
                <fieldset> <legend><span style="color:#990000">Documentos Sin Aplicar</span></legend>
                <br />
                <table width="100%">
                <tr>
                    <td>
                        <table width="100%" cellspacing="0" cellpadding="0">
						<tr>
                            <td class="tituloCampo" width="25%"  align="left">Disponible Actual:</td>
                            <td id="tdSaldoLibro" width="25%" align="right"></td>
                            <td width="25%"></td>
                            <br /><br /><br />
                        </tr> 
                        <tr>
                            <td class="tituloCampo" width="25%"  align="left">Saldo Anterior:</td>
                            <td id="tdSaldoConciliado" width="25%" align="right"></td>
                            <td width="25%"></td>
                        </tr> 
                        </table>
                        <br /><br />
                        <table border="1" class="tabla" cellspacing="2" cellspacing="2" width="100%">   
                        <tr>
                            <td class="tituloColumna" width="25%">Tipos de Documentos</td>
                            <td class="tituloColumna" width="25%">Montos Documentos</td>
                            <td class="tituloColumna" width="25%">Cantidad de Documentos</td>
                        </tr>  
                        <tr>
                            <td class="tituloCampo">Cheques:</td>
                            <td id="tdTotalCheques"></td>
                            <td id="tdTCheques" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Depositos:</td>
                            <td id="tdTotalDeposito"></td>
                            <td id="tdTDeposito" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Notas de Debitos:</td>
                            <td id="tdTotalDebitos"></td>
                            <td id="tdTDebitos" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Notas de Credito:</td>
                            <td id="tdTotalCredito"></td>
                            <td id="tdTCredito" align="center"></td>
                        </tr>
                         <tr>
                            <td class="tituloCampo">Transferencias:</td>
                            <td id="tdTotalTransferencia"></td>
                            <td id="tdTTransferencia" align="center"></td>
                        </tr>
                        
                        </table>
                        <br /><br />
                        <table class="tabla" cellpadding="2" cellspacing="2" width="100%">
                        <tr>
                            <td class="tituloCampo" width="25%">Total Movimiento Sin Aplicar:</td>
                            <td id="tdMovNoApli" width="25%" align="right" border="1" class="tabla"></td>
                            <td width="25%"></td>
                        </tr>      
                        
                        </table>
                    </td>    
                </tr>   
                </table>
                <br /><br /><br />
                </fieldset>  
                
                
                 <fieldset> <legend><span style="color:#990000">Documentos Aplicados</span></legend>
                <br />
                <table width="100%">
                <tr>
                    <td>

                        <br /><br />
                        <table border="1" class="tabla" cellspacing="2" cellspacing="2" width="100%">   
                        <tr>
                            <td class="tituloColumna" width="25%">Tipos de Documentos</td>
                            <td class="tituloColumna" width="25%">Montos Documentos</td>
                            <td class="tituloColumna" width="25%">Cantidad de Documentos</td>
                        </tr>  
                        <tr>
                            <td class="tituloCampo">Cheques:</td>
                            <td id="tdTotalChequesApl"></td>
                            <td id="tdTChequesApl" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Depositos:</td>
                            <td id="tdTotalDepositoApl"></td>
                            <td id="tdTDepositoApl" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Notas de Debitos:</td>
                            <td id="tdTotalDebitosApl"></td>
                            <td id="tdTDebitosApl" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Notas de Credito:</td>
                            <td id="tdTotalCreditoApl"></td>
                            <td id="tdTCreditoApl" align="center"></td>
                        </tr>
                         <tr>
                            <td class="tituloCampo">Transferencias:</td>
                            <td id="tdTotalTransferenciaApl"></td>
                            <td id="tdTTransferenciaApl" align="center"></td>
                        </tr>
                        
                        </table>
                        <br /><br />
                        <table class="tabla" cellpadding="2" cellspacing="2" width="100%">
                        <tr>
                            <td class="tituloCampo" width="25%">Total Movimientos Aplicados:</td>
                            <td id="tdMovApli" width="25%" align="right" border="1" class="tabla"></td>
                            <td width="25%"></td>
                        </tr>      
                        
                        </table>
                    </td>    
                </tr>   
                </table>
                <br /><br /><br />
                </fieldset>   
            </div>
            
            
    	</td>
    	<td width="50%" valign="top">
            <div id="divInfo">
                <ul class="acc" id="acc">
                    <li>
                        <h3>Cheques</h3>
                        <div class="acc-section">
                        	<div class="acc-content">
                        		<table width="100%"><tr><td id="tdCheques"></td></tr></table>	 
                        	</div>
                        </div>
                    </li>
                      <li>
                        <h3>Depositos</h3>
                        <div class="acc-section">
                       	 	<div class="acc-content">
                        		<table width="100%"><tr><td id="tdDeposito"></td></tr></table>	   
                       		 </div>
                        </div>
                    </li>
                    <li>
                        <h3>Nota de Debito</h3>
                        <div class="acc-section">
                        	<div class="acc-content">
                        		<table width="100%"><tr><td id="tdDebito"></td></tr></table>	   
                        	</div>
                        </div>
                    </li>
                    <li>
                        <h3>Notas de Credito</h3>
                        <div class="acc-section">
                        	<div class="acc-content">
                        		<table width="100%"><tr><td id="tdCredito"></td></tr></table>	  
                        	</div>
                        </div>
                    </li>
                    <li>
                        <h3>Transferencias</h3>
                        <div class="acc-section">
                        	<div class="acc-content">
                        		<table width="100%"><tr><td id="tdTransferencia"></td></tr></table>	  
                        	</div>
                        </div>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
    </table>      	
		<script src="../js/script.js"></script>
        <script src="../js/packed.js"></script>
        <script type="text/javascript">
        
        var parentAccordion=new TINY.accordion.slider("parentAccordion");
        parentAccordion.init("acc","h3",0,0);
		xajax_listarCheques(0,'','',document.getElementById("hddIdCuenta").value);
		xajax_listarNotaDebito(0,'','',document.getElementById("hddIdCuenta").value);
        xajax_listarNotaCredito(0,'','',document.getElementById("hddIdCuenta").value);
		xajax_listarTransferencia(0,'','',document.getElementById("hddIdCuenta").value);
		xajax_listarDeposito(0,'','',document.getElementById("hddIdCuenta").value);
		xajax_cargarDatosCuenta(document.getElementById("hddIdCuenta").value);

        
        </script>
        
        <div class="noprint">
        <?php include("pie_pagina.php") ?>
        </div>
    </div> 
</body>
</html>
