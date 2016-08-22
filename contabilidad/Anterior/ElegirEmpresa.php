<?php session_start();
include_once('FuncionesPHP.php');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Contabilidad - Elegir Empresa</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
	<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css" />
	
	<script language="JavaScript"src="./GlobalUtility.js"></script>
	<script language= "javascript" >
	<!--*****************************************************************************************-->
	<!--************************VER CONFIGURACION DE REPORTE*************************************-->
	<!--*****************************************************************************************-->
	function Entrar(){
		document.ElegirEmpresa.target='_self';
		document.ElegirEmpresa.method='post';
		document.ElegirEmpresa.action='ElegirFecha.php';
		//alert(document.ElegirEmpresa.TexDesBaseDatos.value);
		document.ElegirEmpresa.submit();
    }
    
    function SelTexto(obj){
		if (obj.length != 0){
			obj.select();
		}
    }
    </script>
</head>
<body>
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_contabilidad.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
        <span class="textoAzulCelesteNegrita_24px">SISTEMA DE CONTABILIDAD</span>
        <br>
        <span class="textoGrisNegrita_13px">Versión 2.0</span>
    	
        <br><br>
        
        <table align="center">
        <tr>
        	<td>
            <form id="frmEmpresa" name="frmEmpresa" style="margin:0" action="ElegirFecha.php">
            <fieldset><legend class="legend">Seleccione Empresa</legend>
                <table width="360">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="30%">Empresa:</td>
                    <td id="tdlstEmpresa" width="70%">
                        <select id="lstEmpresa" name="lstEmpresa" style="width:200px">
                        	<?php
                            $con = ConectarBDAd();
                            $sTabla='company';
                            $_SESSION["CCSistema"] = $_SESSION["bdContabilidad"];
        
                            if ($_SESSION["bdEmpresa"] == ""){
                                $_SESSION["bdEmpresa"]="erp_automotriz";
                            }
                            
                            if($_SESSION["CCSistema"] != ""){
                                $sCondicion ="codigo ='".$_SESSION["CCSistema"]."'";//*1*
                            } else {
                                $sCondicion ="codigo !=''";
                            }	
                            $sCampos = 'Codigo';
                            $sCampos .= ',Descripcion';
                            $SqlStr = 'SELECT '.$sCampos.' FROM '.$sTabla ." WHERE $sCondicion";
                            $exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
                            if ( NumeroFilas($exc)>0){
                                $iFila = -1;
                                while ($row = ObtenerFetch($exc)) {
                                    $iFila++;
                                    $sCodigo= trim(ObtenerResultado($exc,1,$iFila)); 
                                    $sDescripcion =trim(ObtenerResultado($exc,2,$iFila)); ?>
                            	<option value="<?php print("$sCodigo");?>"><?php print($sDescripcion);?></option>
							<?php
								}
							} ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="right" colspan="2">
                        <button type="submit"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/accept.png"/></td><td>&nbsp;</td><td>Aceptar</td></tr></table></button>
                    </td>
                </tr>
                </table>
            </fieldset>
            </form>
            </td>
        </tr>
        </table>
	</div>
    <div><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>