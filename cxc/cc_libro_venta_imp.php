<?php
require_once ("../connections/conex.php");

session_start();

require_once('../clases/rafkLista.php');

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_libro_venta_imp.php");

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
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
	<script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
</head>
<body>
	<div id="divGeneralPorcentaje">
		<table width="100%">
		<tr>
			<td id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td id="tdLibroVenta"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td id="tdCuadroLibroVenta"></td>
		</tr>
		<tr class="noprint">
			<td align="right" colspan="2"><hr>
            <?php 
					$url = ($_SERVER['REQUEST_URI']);
					$urlId = explode("?", $url);
					$urlExcel = end($urlId);
					
					// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
					$queryConfig403 = "SELECT * FROM pg_configuracion_empresa config_emp
						INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
					$rsConfig403 = mysql_query($queryConfig403);
					if (!$rsConfig403){ die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsConfig403 = mysql_num_rows($rsConfig403);
					$rowConfig403 = mysql_fetch_assoc($rsConfig403);
					$valor = $rowConfig403['valor'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					
					if ($valor == 1) {//1 = VENEZUELA ; 2 = PANAMA ; 3 = PUERTO RICO
						$display = 'style="display:none"';
					} else if ($valor == 2 || $valor == 3) {//1 = VENEZUELA ; 2 = PANAMA ; 3 = PUERTO RICO
						$display = '';
					}
								
			 ?>
      	 	    <button type="button" id="btnExportar" <?php echo $display; ?> onclick="window.open('reportes/cc_libro_venta_excel.php?<?php echo $urlExcel; ?>','_blank');" class="noprint"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
				<button type="button" onclick="window.print();" class="noprint"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
				<button type="button" id="btnCancelar" name="btnCancelar" onclick="xajax_volver();" class="noprint"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
		</tr>
		</table>
		<input name="ocultoFechaOrigen" id="ocultoFechaOrigen" type="hidden" value="<?php echo $_GET['f1'] ?>" /><input id="ocultoFechaFinal" name="ocultoFechaFinal" type="hidden" value="<?php echo $_GET['f2'] ?>" />

<div id="load_animate">&nbsp;</div>

<script type="text/javascript">	
var cerrarVentana = true;
window.onbeforeunload = function() {
	if (cerrarVentana == false) {
		return "Se recomienda CANCELAR este cuadro de mensaje\n\nDebe Cerrar la Ventana de SIPRE para efectuar las transacciones efectivamente";
	}
}

if (typeof(xajax) != 'undefined') {
	if(xajax != null){
		xajax.callback.global.onRequest = function() {
			//xajax.$('loading').style.display = 'block';
			document.getElementById('load_animate').style.display='';
		}
		xajax.callback.global.beforeResponseProcessing = function() {
			//xajax.$('loading').style.display='none';
			document.getElementById('load_animate').style.display='none';
		}
	}
}
document.getElementById('load_animate').style.display='none';
</script>
</div>
</body>
</html>

<script>
xajax_listadoLibroVenta(0,'','',"<?php echo $_GET['f1']; ?>|<?php echo $_GET['f2']; ?>|<?php echo $_GET['modulos']; ?>|<?php echo $_GET['idEmpresa']; ?>");
</script>