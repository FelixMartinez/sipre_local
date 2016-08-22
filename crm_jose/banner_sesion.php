<?php
$idUsuario = $_SESSION['idUsuarioSysGts'];
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

$hrefIndex = (file_exists("index2.php")) ? "index2.php" : "index.php";
?>

<script type="text/javascript" language="javascript" src="<?php echo $raiz; ?>js/scriptRafk.js"></script>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr align="left">
    <td><a href="<?php echo $hrefIndex; ?>" target="_blank"><img src="<?php echo $raiz; ?>img/iconos/new_window.png" border="0" title="Abrir Nueva Pesta&ntilde;a"/></a></td>
    <td>&nbsp;</td>
    <td style="font-size:15px; font-weight:bold; color:#bdb5aa;" width="100%">
    	<span style="display:inline-block; text-transform:uppercase; color:#35AFC4; padding-left:2px; text-decoration:none">SIPRE 2.0</span>
        <span style="display:inline-block; text-transform:uppercase; color:#C00; padding-right:2px;"><?php echo (strstr($_SESSION['database_conex'], "prueba")) ? "(SISTEMA DE PRUEBA)" : ""; ?></span>
	</td>
	<td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:11px;">
        <table cellpadding="0" cellspacing="0">
        <tr>
            <td><img src="<?php echo $raiz; ?>img/iconos/building.png" title="Empresa"/></td>
            <td>&nbsp;</td>
            <td><?php echo $_SESSION['nombreEmpresaUsuarioSysGts']; ?></td>
        </tr>
        </table>
    </td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>
    <td>&nbsp;</td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:11px;">
        <table cellpadding="0" cellspacing="0">
        <tr>
            <td><img src="<?php echo $raiz; ?>img/iconos/ico_cliente.gif" title="Usuario"/></td>
            <td>&nbsp;</td>
            <td><a class="linkBlanco" href="<?php echo $raiz; ?>pg_cambio_clave.php" title="Cambiar Clave de Usuario"><?php echo $_SESSION['nombreUsuarioSysGts']; ?></a></td>
        </tr>
        </table>
    </td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>
    <td>&nbsp;</td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:11px;">
        <table cellpadding="0" cellspacing="0">
        <tr>
            <td><img src="<?php echo $raiz; ?>img/iconos/ico_cargo.gif" title="Departamento / Cargo"/></td>
            <td>&nbsp;</td>
            <td><?php echo $_SESSION['cargoUsuarioSysGts']; ?></td>
        </tr>
        </table>
    </td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>
    <td>&nbsp;</td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:11px;">
        <table cellpadding="0" cellspacing="0">
        <tr>
            <td><img src="<?php echo $raiz; ?>img/iconos/ico_fecha.gif" title="Fecha"/></td>
            <td>&nbsp;</td>
            <td><?php echo date("d-m-Y"); ?></td>
        </tr>
        </table>
    </td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>
    <td>&nbsp;</td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:11px;">
        <table cellpadding="0" cellspacing="0">
        <tr>
            <td><img src="<?php echo $raiz; ?>img/iconos/ico_reloj.gif" title="Hora"/></td>
            <td>&nbsp;</td>
            <td id="tdHoraSistema"></td>
        </tr>
        </table>
    </td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>
</tr>
</table>
<iframe id="iframeReloj" name="iframeReloj" style="display:none"></iframe>

<script type="text/javascript">
function mueveRelojPHP() {
	document.getElementById('iframeReloj').src = '<?php echo $raiz."reloj.php"; ?>';
	
    setTimeout("mueveRelojPHP();",30000)
}
mueveRelojPHP();
</script>