<?php
require_once("../connections/conex.php");

@session_start();

$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = 100");
$rs = mysql_query($query, $conex) or die(mysql_error());
$row = mysql_fetch_assoc($rs);
?>
<div id="load_animate">&nbsp;</div>

<script type="text/javascript">	
var cerrarVentana = true;
window.onbeforeunload = function() {
	if (cerrarVentana == false) {
		return "Se recomienda CANCELAR este cuadro de mensaje\n\nDebe Cerrar la Ventana para efectuar las transacciones efectivamente";
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

<form class="form-3">
    <table width="100%">
    <tr align="left">
        <td width="40%"></td>
        <td align="center" width="20%">
            <table style="text-align:center; background:#FFF; border-radius:0.4em;">
            <tr>
                <td><img id="imgLogoEmpresa" name="imgLogoEmpresa" src="<?php echo (strlen($row['logo_familia']) > 5) ? "../".$row['logo_familia'] : "../".$_SESSION['logoEmpresaSysGts']; ?>" width="180"></td>
            </tr>
            </table>
        </td>
        <td align="right" width="40%"></td>
    </tr>
    </table>
</form>