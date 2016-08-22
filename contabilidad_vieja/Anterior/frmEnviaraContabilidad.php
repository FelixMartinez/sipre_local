<?php session_start();
include_once('FuncionesPHP.php');
$_SESSION["pag"] = 4;
  $conectadosNormal= verificarConectados("N");
	if(count($conectadosNormal) > 0){
		echo "<script language='javascript'>
				alert('Existen usuarios conectados debe esperar a que se desconecten');
			  	location.href='ListadoConectados.php';
			  </script>";
	}else{
		registrar("E");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmformatos -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->


<title>.: SIPRE 2.0 :. Contabilidad - Enviar a Contabilidads</title>
<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">

<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">

<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script></head>

<body>
<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div> 
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--***********************************FUNCIONES JAVASCRIPT**********************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<script language="JavaScript"src="./GlobalUtility.js">
</script>
<script language= "javascript" >
<!--*****************************************************************************************-->
<!--**********************************SELECCIONAR TEXTO**************************************-->
<!--*****************************************************************************************-->
function SelTexto(obj){
if (obj.length != 0){
obj.select();
}
}// function SelTexto(obj){

function objetoAjax(){
		var xmlhttp=false;
	 	try{
   			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
  		}catch(e){
   			try {
    			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	   		}catch(E){
    			xmlhttp = false;
   			}
  		}
  		if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
   			xmlhttp = new XMLHttpRequest();
  		}
  		return xmlhttp;
	}
function MostrarDetalle(){
		fechaD = document.frmPantallaEnviaraContabilidad.xDFechaD.value +'/'+document.frmPantallaEnviaraContabilidad.xMFechaD.value+'/'+document.frmPantallaEnviaraContabilidad.xAFechaD.value
		fechaH = document.frmPantallaEnviaraContabilidad.xDFechaH.value +'/'+document.frmPantallaEnviaraContabilidad.xMFechaH.value+'/'+document.frmPantallaEnviaraContabilidad.xAFechaH.value
		
		if(!IsDate(fechaD)){
		     alert("Fecha Desde Errada");
			 return;
		}
		if(!IsDate(fechaH)){
		     alert("Fecha Hasta Errada");
			 return;
		}		
		fechaD = document.frmPantallaEnviaraContabilidad.xDFechaD.value +'-'+document.frmPantallaEnviaraContabilidad.xMFechaD.value+'-'+document.frmPantallaEnviaraContabilidad.xAFechaD.value
		fechaH = document.frmPantallaEnviaraContabilidad.xDFechaH.value +'-'+document.frmPantallaEnviaraContabilidad.xMFechaH.value+'-'+document.frmPantallaEnviaraContabilidad.xAFechaH.value
		
		MiDiv = document.getElementById("DivComboNo");
		MiDiv.innerHTML="<table width='900' align=center><tr><td align=center><img src='./Imagenes/multibox_loader.gif'></img></td></tr></table>";
		ajax=objetoAjax();
 		ajax.open("GET","DetalleEnviarContabilidad.php?fechaD="+fechaD+"&fechaH="+fechaH);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				MiDiv.innerHTML  = ajax.responseText
		}
 		}
 		ajax.send(null)
}

function MostrarDia(){
		document.frmPantallaEnviaraContabilidad.target='FrameDetalle';
		document.frmPantallaEnviaraContabilidad.method='post';
		document.frmPantallaEnviaraContabilidad.action='VerEnviarContabilidad.php';
		document.frmPantallaEnviaraContabilidad.submit();
}

function Contabilizar(){
		ajax=objetoAjax();
		MiDiv = document.getElementById("divoculto");
		idDia = document.frmPantallaEnviaraContabilidad.idDia.value;
		idcc  = document.frmPantallaEnviaraContabilidad.idcc.value;
		idct  = document.frmPantallaEnviaraContabilidad.idct.value;
 		ajax.open("GET","VerificarAsientoImportados.php?idDia="+idDia+"&idcc="+idcc+"&idct="+idct,false);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				MiDiv.innerHTML  = ajax.responseText
		}
 		}
 		ajax.send(null)

		if(document.getElementById("hdn_adelante").value!=""){
				if(confirm('Ya se ha generado los movimientos en el comprobante Nro :'+document.getElementById("hdn_adelante").value+ ' Desea Generar el dia nuevamente ' )){
							Contabilizar2();
					}
		}else{
							Contabilizar2();
		}

}

function Contabilizar2(){
		document.frmPantallaEnviaraContabilidad.target='FrameDetalle';
		document.frmPantallaEnviaraContabilidad.method='post';
		document.frmPantallaEnviaraContabilidad.action='GuardarAsientoImportados.php';
		document.frmPantallaEnviaraContabilidad.submit();
		
}

function LimpiaDetalle(){
        CambiarDias();
		MostrarDia();
}

function llamartrans(){
       MiDiv = document.getElementById("divtran");
		ajax=objetoAjax();
		idcc = document.frmPantallaEnviaraContabilidad.idcc.value 
		ajax.open("GET","cargarTransaccionEnviarContabilidad.php?idcc="+idcc);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				MiDiv.innerHTML  = ajax.responseText
		}
 		}
 		ajax.send(null)
}

function CambiarDias(){
		MiDiv = document.getElementById("comboDia");
		ajax=objetoAjax();
		idct = document.frmPantallaEnviaraContabilidad.idct.value 
		idcc = document.frmPantallaEnviaraContabilidad.idcc.value 
		ajax.open("GET","cargarDiaEnviarContabilidad.php?idct="+idct+"&idcc="+idcc);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				MiDiv.innerHTML  = ajax.responseText
		}
 		}
 		ajax.send(null)
}
</script>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<?php 
$conAd = ConectarBD();
$SqlStr = "SELECT max(fecha) FROM enviadosacontabilidad a ";
				$exc = EjecutarExec($conAd,$SqlStr) or die($SqlStr);
				$row=ObtenerFetch($exc);
				$mensajeDes = "";
				$fechaUltimo = date("d-m-Y");
				if(!is_null($row[0])){
				   $mensajeDes = "Fecha último día de envío: ". date("d-m-Y",strtotime($row[0])); 
				   $fechaUltimo = $row[0];
				    $xMesD = date("m",strtotime($fechaUltimo));
					$xAnoD = date("Y",strtotime($fechaUltimo));
				    $xDiaD = date("d",strtotime($fechaUltimo));
				   $fechaUltimo = date("Y-m-d",mktime(0,0,0,$xMesD,$xDiaD+1,$xAnoD)); 
				}
				$xDiaD = date("d",strtotime($fechaUltimo));
				$xMesD = date("m",strtotime($fechaUltimo));
				$xAnoD = date("Y",strtotime($fechaUltimo));
				$xDiaH = date("d",strtotime($fechaUltimo));
				$xMesH = date("m",strtotime($fechaUltimo));
				$xAnoH = date("Y",strtotime($fechaUltimo));
				
?>
<table border="0" width="100%">
    <tr>
	    <td class="tituloPaginaContabilidad">Enviar a Contabilidad</td>            
    </tr>
</table>
<form name="frmPantallaEnviaraContabilidad"action="frmPantallaEnviaraContabilidad.php"method="post">

 <div style="width:100%;">
        <div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>
        <div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc">
            <h3 style="margin-bottom:10px;"></h3>
            <div class="x-form-bd" id="container">
               
<table width="100%">
	<tr>
         <td colspan=3><fieldset>
		  	<legend class="legend"><?=utf8_encode($mensajeDes)?></legend>
			<table border="0" align="center">
                <tr>
					<td align="center">
                        <table>
                            <tr>
                                <td class="tituloCampo" width="144" align="right">
                                    Fecha:
                                </td>	
								<td  height=20 valign=to >Desde: 
                                    <input class='cTexBox' id="xDFechaD" name="xDFechaD" type="text"maxlength=2 onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" onFocus="SelTexto(this);" size="1" value="<?php  print($xDiaD); ?>">
                                    <input class='cTexBox' id="xMFechaD" name="xMFechaD" type="text"maxlength=2 onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" size="1"  value="<?php  print($xMesD); ?>">
                                    <input class='cTexBox' id="xAFechaD"  name="xAFechaD" type="text"maxlength=4 onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" size="4"  value="<?php  print($xAnoD); ?>">	
                                </td> 
                                <td  height=20 valign=to >Hasta: 
                                    <input class='cTexBox' id="xDFechaH" name="xDFechaH" type="text"maxlength=2 onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" onFocus="SelTexto(this);" size="1" value="<?php  print($xDiaH); ?>">
                                    <input class='cTexBox' id="xMFechaH" name="xMFechaH" type="text"maxlength=2 onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" size="1"  value="<?php  print($xMesH); ?>">
                                    <input class='cTexBox' id="xAFechaH"  name="xAFechaH" type="text"maxlength=4 onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" size="4"  value="<?php  print($xAnoH); ?>">	
                                </td> 
							</tr> 
						</table>
					</td>
				</tr>
			</table>
            </fieldset>
		</td>
	</tr>
</table>

                        
<table width="100%">
   <tr>
  		<td align="right"><hr/>
        	<button name="BtnAceptar" type="button" maxlength=23 size=10 onClick=" MostrarDetalle();" value="Aceptar">Aceptar</button></td> 
   </tr>
</table> 
  
</div>
</div></div></div>
<div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>
</div>

<table width="100%" align="left" border="0"cellpadding=0 cellspacing=0 >
    <tr>
	<td><div id="DivComboNo" width='100%'> </div></td>
   </tr>
</table> 
<div id="divoculto">
    <input type="hidden" id="hdn_adelante" name="hdn_adelante" value=""  >
</div>
</form>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<div class="noprint">
 	<?php include("pie_pagina.php"); ?>
</div>
</body>
</html>
<?php } ?>