<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
/*if(!(validaAcceso("cc"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}*/
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_index.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();

if ($_SESSION['session_first_select'] == false) {
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND predeterminada IS TRUE;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error()."<br><br>Line: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);

	if (!$rowEmpresa['id_empresa_reg']) {
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa
		WHERE id_usuario = %s;",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error()."<br><br>Line: ".__LINE__);
		if (mysql_num_rows($rsEmpresa) == 0) {
			echo "
			<script>
			alert('Usted no tiene Empresa(s)/Sucursal(es) Asignada(s), consulte al Administrador del Sistema');
			window.location = 'index.php';
			</script>";
			exit;
		}
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$updateSQL = sprintf("UPDATE pg_usuario_empresa SET
			predeterminada = 1
		WHERE id_usuario_empresa = %s;",
			valTpDato($rowEmpresa['id_usuario_empresa'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {echo "<script>alert('".mysql_error()."');</script>";}
		
		$mensaje = true;
	}
	$_SESSION['idEmpresaUsuarioSysGts'] = $rowEmpresa['id_empresa_reg'];
	$_SESSION['logoEmpresaSysGts'] = $rowEmpresa['logo_familia'];
	$_SESSION['session_first_select'] = true;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css">
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
	<div id="divInfo" class="print">
		<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
		<span class="textoMoradoNegrita_24px">SISTEMA DE CUENTAS POR COBRAR</span>
		<br>
		<span class="textoGrisNegrita_13px">Versión 3.0</span>
		
		<br><br>
		
		<table align="center" style="display:none">
		<tr>
			<td>
			<form id="frmEmpresa" name="frmEmpresa" onsubmit="return false;" style="margin:0">
			<fieldset><legend class="legend">Seleccione Empresa</legend>
			
				<table align="center" width="350">
				<tr align="left">
					<td align="right" class="tituloCampo" width="30%">Empresa:</td>
					<td id="tdlstEmpresa">
						<select id="lstEmpresa" name="lstEmpresa">
							<option value="">[ Seleccione ]</option>
						</select>
					</td>
				</tr>
				<tr align="left" id="trlstSucursal">
					<td align="right" class="tituloCampo">Sucursal:</td>
					<td id="tdlstSucursal">
						<select id="lstSucursal" name="lstSucursal">
							<option value="">[ Seleccione ]</option>
						</select>
					</td>
				</tr>
				<tr align="left">
					<td align="right" colspan="2">
						<button type="submit" onClick="xajax_asignarEmpresa(xajax.getFormValues('frmEmpresa'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_aceptar.gif"/></td><td>&nbsp;</td><td>Aceptar</td></tr></table></button>
					</td>
				</tr>
				</table>
			</fieldset>
			</form>
			</td>
		</tr>
		</table>
	</div>
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>
<?php
if ($mensaje)
	echo "<script>alert('Usted no tenia asignada una empresa predeterminada, debido a eso, se le asignó la que verá a continuación');</script>"; ?>
<script>
xajax_cargarSesion('<?php echo $_SESSION['idUsuarioSysGts']; ?>','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>