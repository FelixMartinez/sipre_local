<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Impresion de Factura de Servicios</title>
<script>
function verificarImpresion($tipo_doc, $esCotizacion)
{
	if(confirm("Desea salir de la impresion?"))
	{
		if($tipo_doc == 0)
		{
			
			window.location.href = "cjrs_factura_venta_list?acc=4";
			return true;
			
		}
		
	}
	else
		return false;
}
</script>
</head>
<body>
<table width="70%" border="0" cellpadding="0" cellspacing="0">  
  <tr>
    <td>&nbsp;</td>
    <td align="right"><input type="button" name="btnSalir" id="btnSalir" value="Salir" onclick="return verificarImpresion(<?php if ($_GET['doc_type'] == 1) echo $var = 1;
	else
		 echo $var = 0;
		?>,<?php echo $_GET['acc'];?>);" /></td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2"><iframe src="<?php echo sprintf("sa_imprimir_presupuesto_pdf.php?valBusq=%s|%s|%s", $_GET['id'], $_GET['doc_type'], $_GET['acc']);?>" width="100%" height="800" ></iframe></td>
  </tr>
</table>
</body>
</html>

