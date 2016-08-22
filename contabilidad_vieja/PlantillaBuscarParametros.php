<?php session_start();
include_once('FuncionesPHP.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />-->

<script language="JavaScript" src="./GlobalUtility.js">
</script>
<script language="JavaScript">

function BuscarJ(sP,iCa,parExceloPdf){
	sPar = "";
	bAmper = "";
	for(i=1;i<=iCa;i++){
		if (typeof(eval('document.PlantillaBuscarParametros.xDcDesde'+Alltrim(i.toString()))) != 'undefined'){
			sValorFecha = eval('document.PlantillaBuscarParametros.xAcDesde'+Alltrim(i.toString())+'.value') + "-" + eval('document.PlantillaBuscarParametros.xMcDesde'+Alltrim(i.toString())+'.value') + "-" + eval('document.PlantillaBuscarParametros.xDcDesde'+Alltrim(i.toString())+'.value');
		  	sPar = sPar + bAmper + 'cDesde' + Alltrim(i.toString()) + "=" + sValorFecha;
		   	bAmper = "&";
		  	if (typeof(eval('document.PlantillaBuscarParametros.xDcHasta'+Alltrim(i.toString()))) != 'undefined'){	  
		  		sValorFecha = eval('document.PlantillaBuscarParametros.xAcHasta'+Alltrim(i.toString())+'.value') + "-" + eval('document.PlantillaBuscarParametros.xMcHasta'+Alltrim(i.toString())+'.value') + "-" + eval('document.PlantillaBuscarParametros.xDcHasta'+Alltrim(i.toString())+'.value');
		  		sPar = sPar + bAmper + 'cHasta' + Alltrim(i.toString()) + "=" + sValorFecha;
		  	}
		}else{
    		if (typeof(eval('document.PlantillaBuscarParametros.cDesde'+Alltrim(i.toString()))) != 'undefined'){
				sPar = sPar + bAmper + 'cDesde' + Alltrim(i.toString()) + "=" + eval('document.PlantillaBuscarParametros.cDesde'+Alltrim(i.toString())+'.value');
			}
			bAmper = "&";		
			if (typeof(eval('document.PlantillaBuscarParametros.cHasta'+Alltrim(i.toString()))) != 'undefined'){
				sPar = sPar + bAmper + 'cHasta' + Alltrim(i.toString()) + "=" + eval('document.PlantillaBuscarParametros.cHasta'+Alltrim(i.toString())+'.value');

			}
		}
	}
	day = new Date();
	id = day.getTime();
//eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=1,menubar=0,resizable=0,"+result+"');");
	eval("page" + id + "= open('','" + id + "','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no');");
	sPar = sPar + "&ExceloPdf="+parExceloPdf;
	eval("page" + id + ".location ='"+sP+'?'+sPar+"'");
}

function JJ(){
   document.PlantillaBuscarParametros.method='post';
   document.PlantillaBuscarParametros.target='topFrame';
   document.PlantillaBuscarParametros.action='FrmArriba.php';
   document.PlantillaBuscarParametros.submit();
}

function Titu(obj){
  obj.title = obj.value; 
}

function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
   document.PlantillaBuscarParametros.TACondicion.value=sCampoBuscar + "= '" + sValor + "'";   
   document.PlantillaBuscarParametros.TAValores.value=oArreglo;   
   document.PlantillaBuscarParametros.method='post';
   document.PlantillaBuscarParametros.target='topFrame';
   document.PlantillaBuscarParametros.action='BusTablaParametros.php';
   document.PlantillaBuscarParametros.submit();
}
function AbrirBus(sObjeto,oArreglo){
	winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
}

</script>
<?php 
$_SESSION["sGPosterior"] = $_GET["spPosterior"];
?>

<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

</head>
<body>

<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div> 
<form method="post"  name="PlantillaBuscarParametros">

<?php
    function FixSqlstring($sValor){
    	return str_replace("'","''",$sValor);
    }
	
    $sCampos = "Plantilla"; //1
    $sCampos.= ",Longitud";//2
    $sCampos.= ",TitulodelCampo";//3
    $sCampos.= ",Objeto";//4
    $sCampos.= ",CamposRelacionados";//5
    $sCampos.= ",NombreTabla";//6 se escojera este campo como el nombre de  la pagina que sera llamda despues de esta pantalla
    $sCampos.= ",TipoLinea";//7
	$sCampos.= ",TipoLogico";//8
	
    $sCampos.= ",Busqueda";//9
    $con = ConectarBDAd();
    //$_GET["sPlan"]
    if ( trim($sPlan) == ''){
    	$sPlantilla = $_GET["TexsPlantillaB"];
    }
    else{
    	$sPlanti = trim($_GET["sPlan"]);
		$sPlantilla = substr($sPlanti,0,3);	
    }
    //OJO variables de session
    //session("sConPara")= ""
    //session("sPlantillaSe")= request.QueryString("sPlan")
    //.$sPlantilla.
    $sql = "Select " .$sCampos. " from plantillas where rtrim(Codigo) = '$sPlantilla' order by orden";	
    
	$rs = EjecutarExecAd($con,$sql);
    $sEof = "Si";
    if ($NrodeRegistro != 0){
    	$iNrodeRegistro = $NrodeRegistro;
    }else{
    	$iNrodeRegistro = 20;
    }

    $sNombreTabla = trim($NombreTabla);
?>

<title><?php print($Plantilla) ?></title>
<?php 
$bprimeratabla = true; 
//$row = ObtenerFetch($rs);

$Plantilla  = ObtenerResultado($rs,1);
?>
<div id="divInfo" class="print">
<table border="0" width="100%">
  	<tr> 
        <td width="730" align="center" height="15" class="tituloPaginaContabilidad">
        	<?php  print("REPORTE DE ". strtoupper(trim($Plantilla))); ?>
        </td>
  	</tr>
</table>

<table width="100%">
	<tr><br/>
        <td>
        	<fieldset>
            <legend class="legend">Generar Reporte
            	</legend>
            <table border="0" align="center">
                	<tr>
                        <td width="130" class="cabecera" height="15">
                            <strong><font size="1" face="Verdana, Arial, Helvetica, sans-serif"></font></strong>
                        </td>
                        <td width="350" class="cabecera" align="center" height="15">
                            <strong><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Desde</font></strong>
                        </td>
                        <td width="350"  class="cabecera" align="center" height="15">
                            <strong><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Hasta</font></strong>
                        </td>
                    </tr>
<?php
$iFila = -1;

while($row = ObtenerFetch($rs)){
    $iFila++;
	$Plantilla  = ObtenerResultado($rs,1,$iFila);
	$Longitud = ObtenerResultado($rs,2,$iFila);
    $TitulodelCampo = ObtenerResultado($rs,3,$iFila);
	$Objeto= ObtenerResultado($rs,4,$iFila);
	$CamposRelacionados= ObtenerResultado($rs,5,$iFila);
	$NombreTabla= ObtenerResultado($rs,6,$iFila);
	$TipoLinea= ObtenerResultado($rs,7,$iFila);
	$TipoLogico= ObtenerResultado($rs,8,$iFila);
	$Busqueda= trim(ObtenerResultado($rs,9,$iFila));

	if ($Busqueda == 'SI') { ?>
<?php   $sNombreCampo =  trim($Nombrecolumna);
		$sNombreCampo1 = 'cDesde' .$iFila;
		$sNombreCampo2 = 'cHasta' .$iFila;
		$sAcom = '';
		$sAste = '';

		if (trim($TipoLinea) == "Normal"){
?>
                    <tr> 
<?php
	if ((trim($TitulodelCampo))<> '') {
?>
                        <td class="tituloCampo" width="140" align="right">  
							<?php print(trim($TitulodelCampo));?>:
                        </td>
<?php
	}else{
?>
						<td width="140" align="right" bgcolor="#FFFFFF" bordercolor="#FFFFFF">&nbsp;
                        	
                        </td>
<?php 
	}
?>
                
	
<?php 	if (trim($Objeto) == 'Numerico' or trim($Objeto) == 'Entero'){ ?>
						<td align="left"> 
        
<?php 	if (trim($Objeto) == 'Numerico'){ ?>
<?php 		if (trim($sNombreCampo1 == '')) { ?> 
							<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right"  size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo1)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" onblur=<?php print("this.value=Format(this.value," .trim($NumeroDecimales))?> value="0">
<?php 
        	}else {?>
        					<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo1)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" onblur=<?php print("this.value=Format(this.value," .trim($NumeroDecimales)) ?> value="0">
<?php 		}?>                  										  
<?php 	}else { ?>
<?php 		if (trim($NombreCampo1) == '') {?> 
							<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo1)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" value="0">
<?php   	}else{	?>
							<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo1)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" value="0">                   
<?php   	} ?>                  										   
<?php 	} ?>
						</td>	  

<?php 	if (trim($TipoLogico) == 'Between'){ ?>	  
						<td align="left" > 

<?php 		if (trim($Objeto) == 'Numerico'){ ?>
<?php 			if (trim($sNombreCampo2) == '') { ?> 
	    					<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" onblur=<?php print("this.value=Format(this.value," .trim($NumeroDecimales). ");"); ?> value="0">
   			
<?php    		}else{?>
         					<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" onblur=<?php print("this.value=Format(this.value," .trim($NumeroDecimales)); ?> value="0">
<?php 	 		} ?> 		  
<?php 		}else{ ?>
<?php 		if (trim($sNombreCampo2) == ''){ ?> 
 	        				<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" value="0">
<?php 		}else{ ?> 		  									  
			<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" value="0">
<?php   	} ?> 		  
<?php 	} ?>
						</td>	  	  
<?php 
	} ?>
<?php 
} ?>

<?php 
if (trim($Objeto) == 'ComboValores' ){ ?>
						<td align="left"> 
        					<select onkeyup=fn(this.form,this,event,'') name="<?php print(trim($sNombreCampo1)) ?>"  class="cTexBox">

<?
	$sMonto = trim($CamposRelacionados);
    $Monto = '';
    $Num = strlen(trim($CamposRelacionados));
	for ($L = 0; $L <= $Num; $L++){		
		if (substr($sMonto, $L, 1) == ','){ ?>
								<option value="<?php print($Monto); ?>">
									<?php print($Monto); ?>
                                </option>
				
<?php $Monto = ''; ?>
<?php   }else{
			$Monto.= substr($sMonto, $L, 1);
		}
	}
?>
                            </select>
                        </td>				  
<?php 
	if ($TipoLogico == 'Between'){ ?>	  
                        <td align="left"> 
                        	<select onkeyup=fn(this.form,this,event,'') name="<?php print(trim($sNombreCampo2)); ?>"  class="cTexBox">

<?php 
		$sMonto = trim($CamposRelacionados);
        $Monto = '';
        $Num = strlen(trim($CamposRelacionados));
        for ($L = 0; $L <= $Num; $L++) {
        	if (substr($sMonto, $L, 1) == ','){
?>
								<option value=" <?php print($Monto); ?>">
									<?php print($Monto); ?>
                                </option>

<?php  				$Monto = '' ?>
<?php 			
			}else{
				$Monto.= substr($sMonto, $L, 1);
			}
		}
?>
                            </select>
                        </td>				  
<?php 
	} ?>	  
<?php 
} ?>

<?php   
if (trim($Objeto) == 'Combo'){  ?>
                        <td align="left"> 
                            <select  onkeyup=fn(this.form,this,event,'') name="<?php print(trim($sNombreCampo1)); ?>" class="cTexBox">

<?php   
	$sCampos = trim($CamposRelacionados);
	$sTablaRelacionada = trim($TablaRelacionada);
	$sql = "Select  "  .$sCampos. " from " .$sTablaRelacionada;
	$rs2 = EjecutarExecAd($con,$sql);
	exit;
	while($row = ObtenerFetch($rs2)){
?>
								<option value=<?php print(ObtenerResultado($rs2,1)); ?>><?php print(ObtenerResultado($rs2,2)); ?>
                                </option>

<?php 		
    } ?>
							</select>
                       	</td>
      
			
<?php 
	if (trim($TipoLogico) == 'Between'){ ?>	  
                        <td align="left"> 
                        	<select onkeyup=fn(this.form,this,event,'') name="<?php trim($sNombreCampo2) ?>" class="cTexBox">
					
<?php 
		while ($row = ObtenerFetch($rs2)){ ?>
								<option value=<?php print(ObtenerResultado($rs2,2)); ?>><?php print(ObtenerResultado($rs2,1)); ?></option>

<?php 
		}
?>
                            </select>
                        </td>

<?php 
	} ?>
<?php 
} ?>
				
<?php 
if (trim($Objeto) == "Texto"){ ?>
                        <td align="left"> 
                            <input type="text" maxlength=<?php print(trim(strval($Longitud))); ?> class="cTexBox" size="42" onkeyup=fn(this.form,this,event,'') name=<?PHP print(trim($sNombreCampo1)); ?> value=""> 
                        </td>
     	
<?php 
	if (trim($TipoLogico) == "Between"){ ?>	  
                        <td align="left"> 
                            <input type="text" maxlength=<?php print(trim(strval($Longitud))); ?> class="cTexBox" size="42" onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> value=""> 
                        </td>
<?php 
	} ?>					  
<?php 
} ?>

<!--***************************************************solo para las Fechas*********************************************************-->

<?php 
if (trim($Objeto) == "Fecha"){ 
	$xDia =obFecha($_SESSION["sFec_Proceso"],'D');
 	$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
	$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
	$Dia1 = '01';
	$Mes1 = $xMes;
	$Year1 = $xAno;
	$Dia2 = $xDia;
	$Mes2 = $xMes;
	$Year2 = $xAno;
						 
?>
                        <td align="left"> 
                            <input type="text" size=3 class="cTexBox" onkeyup=fn(this.form,this,event,'') name=<?php print("xD". trim($sNombreCampo1))?> value=<?=$Dia1?> >/
                            <input type="text" size=3 class="cTexBox" onkeyup=fn(this.form,this,event,'') name=<?php print("xM" . trim($sNombreCampo1))?> value=<?=$Mes1?> >/
                            <input type="text" size=7 class="cTexBox" onkeyup=fn(this.form,this,event,'') name=<?php print("xA" . trim($sNombreCampo1)) ?> value=<?=$Year1?> ><font size=-14>dd/mm/aaaa</font>
						</td>
		
<?php 
	if (trim($TipoLogico) == "Between"){ ?>	  
                        <td align="left"> 
                            <input type="text" size=3 class="cTexBox" onkeyup=fn(this.form,this,event,'') name=<?php print("xD". trim($sNombreCampo2)) ?> value=<?=$Dia2?> >/
                            <input type="text" size=3 class="cTexBox" onkeyup=fn(this.form,this,event,'') name=<?php print("xM". trim($sNombreCampo2)) ?> value=<?=$Mes2?> >/
                            <input type="text" size=7 class="cTexBox" onkeyup=fn(this.form,this,event,'') name=<?php print("xA". trim($sNombreCampo2)); ?> value=<?=$Year2?> ><font size=-14>dd/mm/aaaa</font>
                        </td>

<?php 
	} ?>
<?php 
} ?>

<!--***********************************************solo para Tablas con valores*****************************************************-->

<?php 
if (trim($Objeto) == 'TablaValores' ){ ?>
						<td align="left"> 

<?  $sMonto = trim($CamposRelacionados);
	$Monto = "";
	$Num = strlen(trim($CamposRelacionados));
	$bPrimera = true;
	$bPrimeraCam = true;
	$sClaveCon = "";
	for ($L = 0; $L <= $Num; $L++){
		if (substr($sMonto, $L, 1) == ','){
			if ($bPrimera){
				$Arretabla[0][0]= $Monto;
				$Arretabla[0][1]= 'T';
				$IArr = 0;
				$bPrimera = false;
				$sTabla = $Monto;
			}else{
				$IArr++;
				$Arretabla[$IArr][0]= $Monto;
				$Arretabla[$IArr][1]= 'C';
				if ($bPrimeraCam){
					$bPrimeraCam = false;
					$sClaveCon = $Monto; 
				}
			}
			$Monto = ''; ?>

<?php   }else{
		  	$Monto.= substr($sMonto, $L, 1);
		}
	}
	  //para el ultimo del campo
	$IArr++;
	$Arretabla[$IArr][0]= $Monto;
	$Arretabla[$IArr][1]= 'C';
	//fin para el ultimo del campo
	$IArr++;
	$Arretabla[$IArr][0]= $sNombreCampo1;
	$Arretabla[$IArr][1]= 'O';
	$IArr++;
	$Arretabla[$IArr][0]= 'DescD'. $sTabla;
	$Arretabla[$IArr][1]= 'O';
	$IArr++;
	$Arretabla[$IArr][0]= 'PlantillaBuscarParametros';
	$Arretabla[$IArr][1]= 'P';
	$Arre = array_envia($Arretabla);
?>
															
                            <input type="text"  onDblClick="<?php print("AbrirBus(this.name,'$Arre')");?>"  class="cTexBox" size="5" onkeyup=fn(this.form,this,event,'')  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre')");?>" name=<?PHP print(trim($sNombreCampo1)); ?> value=""> 	
                            <input type="text"  disabled class="cTexBox" size="28" onkeyup=fn(this.form,this,event,'') name=<?PHP print(trim('DescD'.$sTabla)); ?> value=""> 
                                            
                        </td>				  

<?php  
	if ($TipoLogico == 'Between'){ ?>	
						<td align="left">   
		
<? 			
		$sMonto = trim($CamposRelacionados);
		$Monto = "";
		$Num = strlen(trim($CamposRelacionados));
		$bPrimera = true;
		$bPrimeraCam = true;
		$sClaveCon = "";
		for ($L = 0; $L <= $Num; $L++){
			if (substr($sMonto, $L, 1) == ','){
				if ($bPrimera){
					$Arretabla[0][0]= $Monto;
					$Arretabla[0][1]= 'T';
					$IArr = 0;
					$bPrimera = false;
					$sTabla = $Monto;
				}else{
					$IArr++;
					$Arretabla[$IArr][0]= $Monto;
					$Arretabla[$IArr][1]= 'C';
					if ($bPrimeraCam){
						$bPrimeraCam = false;
						$sClaveCon = $Monto; 
					}
				}
				$Monto = ''; ?>
<?php 
			}else{
				$Monto.= substr($sMonto, $L, 1);
	        }
		}
//para el ultimo del campo
		$IArr++;
		$Arretabla[$IArr][0]= $Monto;
		$Arretabla[$IArr][1]= 'C';
//fin para el ultimo del campo
	    $IArr++;
		$Arretabla[$IArr][0]= $sNombreCampo2;
		$Arretabla[$IArr][1]= 'O';
		$IArr++;
		$Arretabla[$IArr][0]= 'DescH'. $sTabla;
		$Arretabla[$IArr][1]= 'O';
	    $IArr++;
		$Arretabla[$IArr][0]= 'PlantillaBuscarParametros';
		$Arretabla[$IArr][1]= 'P';
		$Arre = array_envia($Arretabla);
?>															
                            <input type="text" onChange="Titu(this);" onDblClick="<?php print("AbrirBus(this.name,'$Arre')");?>"  class="cTexBox" size="5" onkeyup=fn(this.form,this,event,'')  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre')");?>" name=<?PHP print(trim($sNombreCampo2)); ?> value=""> 	
                            <input type="text"  disabled class="cTexBox" size="28" onkeyup=fn(this.form,this,event,'') name=<?PHP print(trim('DescH'.$sTabla)); ?> value=""> 	  
                        </td>					     
<?php 
	} ?>	  				  
					</tr>
<?php          }
}
	}
}
?>

<!--solo para un espacio en blanco-->
                    <tr> 
                        <td width="101" height="15">
                            <p> </p>
                        </td>
                    </tr>
<!--Fin solo para un espacio en blanco--> 
				</table>
             </fieldset>
             </td>
		</tr>
</table>

<table width="100%">			  
<!--solo para un espacio en blanco-->
	<tr> 
		<td align="right"><hr/> 
			 <button id="BtnAceptar1" type="submit"  align="middle" onkeyup=fn(this.form,this,event,'') name="BtnAceptar1" value="PDF" onClick = "<?php print("BuscarJ('$NombreTabla','$iFila','P');")?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
			 <button id="BtnAceptar2" type="submit"  align="middle" onkeyup=fn(this.form,this,event,'') name="BtnAceptar2" value="EXCEL" onClick = "<?php print("BuscarJ('$NombreTabla','$iFila','E');")?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
		</td>
	</tr>
<!--Fin solo para un espacio en blanco--> 
</table>

<input type="hidden" name="TexOcultoStatus">
<!--solo para un espacio en blanco-->

<table>
	<tr> 
		<td width="101" height="15">
			<p> </p>
		</td>
	</tr>
<!--Fin solo para un espacio en blanco--> 
</table>
              <!--*************************************************************************************************-->  
              <!--*************************************************************************************************-->  				 
              <!--*********************************esto se coloco para las busuqedas de tablas*********************-->  
              <!--*************************************************************************************************-->  				 
              <!--*************************************************************************************************-->  				 
	<input type="hidden" name="TAValores"> 
	<input type="hidden" name="TACondicion"> 
			
</form>
</div>




<div class="noprint">
	<?php include("pie_pagina.php"); ?>
</div>

</body>
</html>
