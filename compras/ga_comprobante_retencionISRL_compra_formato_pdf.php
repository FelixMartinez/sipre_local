<?php
$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE 2.0 :. Compras - Impresion del Comprobante de Retención</title>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <script>
    function verificarImpresion() {
        if (confirm("Desea salir de la impresion del Comprobante de Retención?")) {
			window.location.href = "ga_registro_compra_list.php";
            return true;
        } else
            return false;
    }
    </script>
</head>

<body>
<center>
    <table width="75%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td>&nbsp;</td>
        <td align="right"><input type="button" name="btnSalir" id="btnSalir" value="Salir" onclick="return verificarImpresion();" /></td>
    </tr>
    <tr>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2"><iframe src="<?php 
			echo sprintf("../tesoreria/reportes/te_imprimir_constancia_retencion_pdf.php?id=%s&documento=3", 
				$valCadBusq['4']);?>" width="100%" height="900" ></iframe>
        </td>
    </tr>
    </table>
</center>
</body>
</html>