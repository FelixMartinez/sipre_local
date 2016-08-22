<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

require_once ("../../connections/conex.php");
require_once('../clases/barcode128.inc.php');
require("../../clases/num2letras.php");
/**************************** ARCHIVO PDF ****************************/
require('../clases/fpdf/fpdf.php');
require('../clases/fpdf/fpdf_print.inc.php');
//$pdf = new PDF_AutoPrint('P','pt',array('680','831'));
$pdf = new PDF_AutoPrint('P','cm','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/
$año = array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');

$idCheque = $_GET['id'];
$codigo = getBarcode($idCheque,'../clases/temp_codigo/img_codigo',2,1,25,"c",0);

$queryMoneda = "SELECT descripcion FROM pg_monedas WHERE predeterminada = 1";
$rsMoneda = mysql_query($queryMoneda);
if(!$rsMoneda){ die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$rowMoneda = mysql_fetch_assoc($rsMoneda);
$descripcionMoneda = strtoupper($rowMoneda["descripcion"]);

$query = sprintf("SELECT * FROM vw_te_cheques WHERE id_cheque = %s",
	valTpDato($idCheque,"int"));
$rs = mysql_query($query, $conex);
if(!mysql_num_rows($rs)){
    die("El id de cheque enviado no existe, o ya se encuentra anulado");
}

if(!$rs) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$row = mysql_fetch_assoc($rs);
$idEmpresa = $row['id_empresa'];
// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig403 = mysql_num_rows($rsConfig403);
$rowConfig403 = mysql_fetch_assoc($rsConfig403);
if($rowConfig403['valor'] == NULL){
    //$rowConfig403['valor'] = 1; //por defecto venezuela 1
    die("No se ha configurado formato de cheque. 403");
}

$queryRetencion = sprintf("SELECT sum(monto_retenido) AS monto_retenido FROM vw_te_retencion_cheque WHERE id_cheque = %s AND tipo_documento = 0 GROUP BY id_cheque",
	valTpDato($idCheque,"int"));
$rsRetencion = mysql_query($queryRetencion, $conex);
if(!$rsRetencion){ die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

$montoTotal = 0;

if(mysql_num_rows($rsRetencion)){
	$rowRetencion = mysql_fetch_assoc($rsRetencion);
	$montoTotal = $row['monto_cheque'] + $rowRetencion['monto_retenido'];
	$aux = true;
} else {
	$montoTotal = $row['monto_cheque'];
	$aux = false;
}



if ($row['beneficiario_proveedor'] == 0){
	$queryBeneficiario = sprintf("SELECT nombre_beneficiario FROM te_beneficiarios WHERE id_beneficiario = %s", valTpDato($row['id_beneficiario_proveedor'],"int"));
	$rsBeneficiario = mysql_query($queryBeneficiario);
	$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
	$nombreBeneficiarioProveedor = $rowBeneficiario['nombre_beneficiario'];
} else {
	$queryProveedor = sprintf("SELECT id_proveedor, nombre, direccion  FROM cp_proveedor WHERE id_proveedor = %s",valTpDato($row['id_beneficiario_proveedor'],"int"));
	$rsProveedor = mysql_query($queryProveedor);
	$rowProveedor = mysql_fetch_array($rsProveedor);
	$nombreBeneficiarioProveedor = $rowProveedor['nombre'];
        $direccionProveedor = $rowProveedor['direccion'];//puerto rico
        $idProveedor = $rowProveedor['id_proveedor'];//puerto rico
}
$ano=substr($row['fecha_registro'],0,4);
$mes=substr($row['fecha_registro'],5,2);
$dia=substr($row['fecha_registro'],8,2);

if ($rowConfig403['valor'] == 1) { //VENEZUELA
    $img = @imagecreate(530, 630) or die("No se puede crear la imagen");
    // ESTABLECIENDO LOS COLORES DE LA PALETA
    $backgroundColor = imagecolorallocate($img, 255, 255, 255);
    $textColor = imagecolorallocate($img, 0, 0, 0);
    
    if ($row['nombreBanco'] == "BANCO PROVINCIAL, S.A., BANCO UNIVERSAL"){
    imagestring($img,2,410,14,str_pad(number_format($row['monto_cheque'], 2, ",", "."), 15, "*", STR_PAD_BOTH),$textColor);
    imagestring($img,2,90,57,$nombreBeneficiarioProveedor,$textColor);
    imagestring($img,2,40,108,$dia.strtoupper(" de ".$año[$mes-1]." de ").$ano,$textColor);

            } else if ($row['nombreBanco'] == "BANESCO, BANCO UNIVERSAL, C.A."){
    imagestring($img,2,410,21,str_pad(number_format($row['monto_cheque'], 2, ",", "."), 15, "*", STR_PAD_BOTH),$textColor);
    imagestring($img,2,90,60,$nombreBeneficiarioProveedor,$textColor);
    imagestring($img,2,40,112,$dia.strtoupper(" de ".$año[$mes-1]." de ").$ano,$textColor);


            } else if ($row['nombreBanco'] == "BANCO DE VENEZUELA, S.A., BANCO UNIVERSAL"){
    imagestring($img,2,410,7,str_pad(number_format($row['monto_cheque'], 2, ",", "."), 15, "*", STR_PAD_BOTH),$textColor);
    imagestring($img,2,90,60,$nombreBeneficiarioProveedor,$textColor);
    imagestring($img,2,40,115,$dia.strtoupper(" de ".$año[$mes-1]." de ").$ano,$textColor);


            } else {
    imagestring($img,2,410,17,str_pad(number_format($row['monto_cheque'], 2, ",", "."), 15, "*", STR_PAD_BOTH),$textColor);
    imagestring($img,2,90,66,$nombreBeneficiarioProveedor,$textColor);
    imagestring($img,2,40,118,$dia.strtoupper(" de ".$año[$mes-1]." de ").$ano,$textColor);

    }


    $linea = 0;


    $caracteresCadena1 = (strlen($row['concepto']) >= 50 && !strpos(substr($row['concepto'],0,49),"\n")) ? strrpos(substr($row['concepto'],0,49)," ") : 50;

    for ($i = 0; $i <= strlen($row['concepto']); $i++){
            if ($row['concepto'][$i] != "\n" && $cuentaCaracteres < $caracteresCadena1){
                    $concepto[$linea] = $concepto[$linea].$row['concepto'][$i];
                    $cuentaCaracteres++;
            }
            else{
                    $cuentaCaracteres = 0;
                    $linea++;
                    $caracteresCadena1 = (strlen($row['concepto']) >= 50 && !strpos(substr($row['concepto'],$caracteresCadena1,$caracteresCadena1 + 49),"\n")) ? strrpos(substr($row['concepto'],0,49)," ") : 50;
            }
    }

    imagestring($img,2,70,245,strtoupper($concepto[0]),$textColor);
    imagestring($img,2,70,255,strtoupper($concepto[1]),$textColor);
    imagestring($img,2,70,265,strtoupper($concepto[2]),$textColor);

    imagestring($img,2,355,300,$row['nombreBanco'],$textColor);

    imagestring($img,2,355,325,$row['numeroCuentaCompania'],$textColor);

    imagestring($img,2,70,325,strtoupper($row['nombre_empresa']),$textColor);
    imagestring($img,2,70,335,strtoupper($row['rif_empresa']),$textColor);
    imagestring($img,2,180,335,str_pad($idCheque, 5, "0", STR_PAD_LEFT),$textColor);

    imagestring($img,2,355,345,$row['numero_cheque'],$textColor);

    imagestring($img,2,60,405,$row['idCuentas'],$textColor);
    imagestring($img,2,60,415,"09",$textColor);

    imagestring($img,2,130,405,"EGRESOS",$textColor);
    imagestring($img,2,130,415,$row['nombreBanco'],$textColor);

    if($aux){
        imagestring($img,2,130,445,"RETENCION I.S.L.R.",$textColor);
    }

    imagestring($img,2,330,405,str_pad(number_format($montoTotal, 2, ",", "."), 15, " ", STR_PAD_LEFT),$textColor);
    imagestring($img,2,410,415,str_pad(number_format($row['monto_cheque'], 2, ",", "."), 15, " ", STR_PAD_LEFT),$textColor);

    if($aux){
        imagestring($img,2,250,445,number_format($rowRetencion['monto_retenido'], 2, ".", ","),$textColor);
    }

    imagestring($img,2,310,515,str_pad(number_format($montoTotal, 2, ",", "."), 15, " ", STR_PAD_LEFT),$textColor);
    imagestring($img,2,420,515,str_pad(number_format($montoTotal, 2, ",", "."), 15, " ", STR_PAD_LEFT),$textColor);
    imagestring($img,2,30,600,$row['numero_cheque'],$textColor);



    $r = imagepng($img,"../img/tmp/cheque.png");

    $pdf->AddPage('P','LETTER');
    $pdf->Image("../img/tmp/cheque.png", 0, 1.6, 18, 22.5);
    $pdf->Image("../clases/temp_codigo/img_codigo.png", '6', '13', '1.5', '0.5');
    
} else if ($rowConfig403['valor'] == 2) { //PANAMA
    
	$img = @imagecreate(615, 530) or die("No se puede crear la imagen");
	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);
        $tamanoLetra = 3;
	$posY = 50;
	imagestring($img,$tamanoLetra,350,60,$dia.strtoupper(" de ".$año[$mes-1]." de ").$ano,$textColor);
	$posY += 55;
	imagestring($img,$tamanoLetra,95,$posY,$nombreBeneficiarioProveedor,$textColor);
	imagestring($img,$tamanoLetra,435,$posY,str_pad(number_format($row['monto_cheque'], 2, ".", ","), 15, "*", STR_PAD_BOTH),$textColor);//formato numero panama
	$posY += 20;
        
        $numerosLetras = num2letras(number_format($row['monto_cheque'], 2, ".", ""), false, true,$descripcionMoneda, $rowConfig403['valor']);
	imagestring($img,$tamanoLetra,100,130, utf8_decode(abreviacionCentavos($numerosLetras,$row['monto_cheque'])),$textColor);
	//num2letras(number_format($row['monto_cheque'], 2, ".", ""), false, true);
	// IMPUTACION CONTABLE
	$linea = 0;
	$caracteresCadena1 = (strlen($row['concepto']) >= 50 && !strpos(substr($row['concepto'],0,49),"\n")) ? strrpos(substr($row['concepto'],0,49)," ") : 50;
	for ($i = 0; $i <= strlen($row['concepto']); $i++){
		if ($row['concepto'][$i] != "\n" && $cuentaCaracteres < $caracteresCadena1){
			$concepto[$linea] = $concepto[$linea].$row['concepto'][$i];
			$cuentaCaracteres++;
		}
		else{
			$cuentaCaracteres = 0;
			$linea++;
			$caracteresCadena1 = (strlen($row['concepto']) >= 50 && !strpos(substr($row['concepto'],$caracteresCadena1,$caracteresCadena1 + 49),"\n")) ? strrpos(substr($row['concepto'],0,49)," ") : 50;
		}
	}
	imagestring($img,$tamanoLetra,70,460,strtoupper($concepto[0]),$textColor);
	imagestring($img,$tamanoLetra,70,470,strtoupper($concepto[1]),$textColor);
	imagestring($img,$tamanoLetra,70,480,strtoupper($concepto[2]),$textColor);
	imagestring($img,$tamanoLetra,120,280,$row['nombreBanco'],$textColor);
	imagestring($img,$tamanoLetra,250,280,$row['numeroCuentaCompania'],$textColor);
	imagestring($img,$tamanoLetra,70,310,strtoupper($row['nombre_empresa']),$textColor);
	imagestring($img,$tamanoLetra,160,310,strtoupper($row['rif_empresa']),$textColor);
	imagestring($img,$tamanoLetra,290,310,str_pad($idCheque, 5, "0", STR_PAD_LEFT),$textColor);
	imagestring($img,$tamanoLetra,340,310,"Nro. Cheque ".$row['numero_cheque'],$textColor);
	//405-335     415-345 445-375   515-445-420 600-530
	imagestring($img,$tamanoLetra,60,340,$row['idCuentas'],$textColor);
	imagestring($img,$tamanoLetra,60,350,"09",$textColor);
	imagestring($img,$tamanoLetra,130,340,"EGRESOS",$textColor);
	imagestring($img,$tamanoLetra,130,350,$row['nombreBanco'],$textColor);
	if ($aux) {
            imagestring($img, $tamanoLetra, 130, 380, "RETENCION I.S.L.R.", $textColor);
        }
        imagestring($img,$tamanoLetra,330,340,str_pad(number_format($montoTotal, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,$tamanoLetra,410,350,str_pad(number_format($row['monto_cheque'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	if ($aux) {
            imagestring($img, $tamanoLetra, 250, 380, number_format($rowRetencion['monto_retenido'], 2, ".", ","), $textColor);
        }
        imagestring($img,$tamanoLetra,310,420,str_pad(number_format($montoTotal, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,$tamanoLetra,420,420,str_pad(number_format($montoTotal, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,$tamanoLetra,30,530,$row['numero_cheque'],$textColor);
        
	$r = imagepng($img,"../img/tmp/cheque.png");
	$pdf->AddPage();
	$pdf->Image("../img/tmp/cheque.png", 0.25, 0.25, 21, 18);

} else if ($rowConfig403['valor'] == 3){//puerto rico
        
        //nombre empresa
        $sqlEmpresa = sprintf("SELECT nombre_empresa, logo_familia, direccion, telefono1 FROM pg_empresa WHERE id_empresa = %s LIMIT 1",
                $idEmpresa);
        $rsEmpresa = mysql_query($sqlEmpresa);
        if(!$rsEmpresa) { die(mysql_error()."<br>Linea:".__LINE__); }
        
        $rowEmpresa = mysql_fetch_assoc($rsEmpresa);
        
        $nombreEmpresa = $rowEmpresa["nombre_empresa"];
        $rutaLogo = "../../".$rowEmpresa['logo_familia']; // Logo
    
        $img = @imagecreatetruecolor(893, 770) or die("No se puede crear la imagen");
	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $backgroundColor); //solo si se crea con truecolor
	$textColor = imagecolorallocate($img, 0, 0, 0);
        $tamanoLetra = 3;
	
        //LIMITE SUPERIOR E INFERIOR
	//imagestring($img,$tamanoLetra,350,0,$dia.strtoupper(" de ".$año[$mes-1]." de ").$ano,$textColor);
	//imagestring($img,$tamanoLetra,350,760,"prueba",$textColor);
	
        imagestring($img,$tamanoLetra,430,615,$row['numero_cheque'],$textColor);
        imagestring($img,$tamanoLetra,480,610,$row['codigo1'],$textColor);//"101-7147"
        imagestring($img,$tamanoLetra,490,620,$row['codigo2'],$textColor);//"2215"
        imageline($img, 475, 622, 540, 622, $textColor);//linea divisoria H -
     
        //$rutaLogo = "../img/logos/kia.jpg";
        $src = imagecreatefromjpeg($rutaLogo); 
        $arrayImagenInfo = getimagesize($rutaLogo);
        $ancho = $arrayImagenInfo[0];
        $alto = $arrayImagenInfo[1];
        
        $ratio = $ancho/$alto;
        
        $nuevoAncho = 70;
        $nuevoAlto = 70;
        
        if ($nuevoAncho/$nuevoAlto > $ratio) {
           $nuevoAncho = $nuevoAlto*$ratio;
        } else {
           $nuevoAlto = $nuevoAncho/$ratio;
        }
        
        if($nuevoAlto > 45){
            $yLogo = 550;
        }else{
            $yLogo = 570;
        }
        
        ImageFilter($src, IMG_FILTER_GRAYSCALE);//escala de grises
        imagefilter($src, IMG_FILTER_CONTRAST, -100);//full negro
        if(!imagecopyresampled($img, $src, 24, $yLogo, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto)){//imagen crear, imagen copiar, x,y destino. x,y a copiar, width height a copiar, 100 transparencia
            die ("No funciona el copiado del logo. Ruta:".$rutaLogo);
        }
        
        //$direccion = str_replace(array("\r\n", "\r", "\n")," ",$rowEmpresa["direccion"]);
//        imagestring($img,$tamanoLetra,120,580,substr($direccion,0,20),$textColor);
//        imagestring($img,$tamanoLetra,120,590,substr($direccion,20,20),$textColor);
//        imagestring($img,$tamanoLetra,120,600,substr($direccion,40,20),$textColor);
//        imagestring($img,$tamanoLetra,120,610,substr($direccion,60,20),$textColor);
        
        $direccionArray = explode(",",$rowEmpresa["direccion"]);
        $dirCortaArray = explode("\n", $direccionArray[1]);
        imagestring($img,$tamanoLetra,120,575,trim($dirCortaArray[0]),$textColor);
        imagestring($img,$tamanoLetra,120,585,trim($dirCortaArray[1]),$textColor);
        imagestring($img,$tamanoLetra,120,595,"Tel. ".$rowEmpresa["telefono1"],$textColor);
       
        
        //prueba test
//        $row["nombreBanco"] = "FirstBank PUERTO RICO";
//        $row["sucursalBanco"] = "Munoz Rivera";
//        $row["direccionBanco"] = "San Juan, Puerto Rico";
        
        $maximo = strlen($row["direccionBanco"]); 
        imagestring($img,$tamanoLetra,300,575,str_pad(utf8_decode($row["nombreBanco"]), $maximo+1," ", STR_PAD_BOTH),$textColor);
        imagestring($img,$tamanoLetra,300,587,str_pad(utf8_decode($row["sucursalBanco"]), $maximo+1," ", STR_PAD_BOTH),$textColor);
        imagestring($img,$tamanoLetra,300,597,str_pad(utf8_decode($row["direccionBanco"]), $maximo+1," ", STR_PAD_BOTH),$textColor);
        
        
        
        $subirY = 5;//usar para subir o bajar los recuadros y contenidos: negativo sube, positivo baja
        
        //DATE
        //----------------------------------------------------------------------
        imagestring($img,$tamanoLetra,40,650+$subirY-16,"DATE",$textColor);//cabecera
        imageline($img, 25, 650+$subirY-16, 82, 650+$subirY-16, $textColor);//linea cabecera H - arriba
        //----------------------------------------------------------------------
        imageline($img, 25, 650+$subirY, 82, 650+$subirY, $textColor);//linea H - arriba
        imageline($img, 25, 663+$subirY, 82, 663+$subirY, $textColor);//linea H - abajo
        imageline($img, 25, 650+$subirY-16, 25, 663+$subirY, $textColor);//linea V | izq
        imageline($img, 82, 650+$subirY-16, 82, 663+$subirY, $textColor);//linea V | der
        //----------------------------------------------------------------------
        $fechaCorta = strtoupper(date("dMy",strtotime($row['fecha_registro'])));        
	imagestring($img,$tamanoLetra,30,650+$subirY,$fechaCorta,$textColor);
        
        
        
        //PAY THIS AMOUNT
        //----------------------------------------------------------------------
        imagestring($img,$tamanoLetra,240,650+$subirY-16,"PAY THIS AMOUNT",$textColor);//cabecera
        imageline($img, 180, 650+$subirY-16, 410, 650+$subirY-16, $textColor);//linea cabecera H - arriba
        //----------------------------------------------------------------------
        imageline($img, 180, 650+$subirY, 410, 650+$subirY, $textColor);//linea H - arriba
        imageline($img, 180, 663+$subirY, 410, 663+$subirY, $textColor);//linea H - abajo
        imageline($img, 180, 650+$subirY-16, 180, 663+$subirY, $textColor);//linea V | izq
        imageline($img, 410, 650+$subirY-16, 410, 663+$subirY, $textColor);//linea V | der
        //----------------------------------------------------------------------
        imageline($img, 285, 650+$subirY, 285, 663+$subirY, $textColor);//linea V |  CENTRAL IZQ
        imageline($img, 342, 650+$subirY, 342, 663+$subirY, $textColor);//linea V  | CENTRAL CENTRAL
        imageline($img, 368, 650+$subirY, 368, 663+$subirY, $textColor);//linea V   | CENTRAL DER
        
        imagestring($img,$tamanoLetra,190,650+$subirY,str_pad(number_format(intval($row['monto_cheque']), 0, ".", ","), 13, "*", STR_PAD_LEFT),$textColor);//formato numero panama        
        imagestring($img,$tamanoLetra,290,650+$subirY,"DOLLARS",$textColor);
        imagestring($img,$tamanoLetra,372,650+$subirY,"CENTS",$textColor);
        $arrayCentimos = explode(".", $row['monto_cheque']);
        $centimos = end($arrayCentimos);
        imagestring($img,$tamanoLetra,350,650+$subirY,$centimos,$textColor);
        
        
        
        //AMOUNT OF CHECK
        //----------------------------------------------------------------------
        imagestring($img,$tamanoLetra,438,650+$subirY-16,"AMOUNT OF CHECK",$textColor);//cabecera
        imageline($img, 428, 650+$subirY-16, 550, 650+$subirY-16, $textColor);//linea cabecera H - arriba
        //----------------------------------------------------------------------
        imageline($img, 428, 650+$subirY, 550, 650+$subirY, $textColor);//linea H - arriba
        imageline($img, 428, 663+$subirY, 550, 663+$subirY, $textColor);//linea H - abajo
        imageline($img, 428, 650+$subirY-16, 428, 663+$subirY, $textColor);//linea V | izq
        imageline($img, 550, 650+$subirY-16, 550, 663+$subirY, $textColor);//linea V | der
        //----------------------------------------------------------------------
        
	imagestring($img,$tamanoLetra,435,650+$subirY,str_pad(number_format($row['monto_cheque'], 2, ".", ","), 15, "*", STR_PAD_LEFT),$textColor);//formato numero panama
        
        
        imagestring($img,$tamanoLetra,50,702,"TO",$textColor);
        imagestring($img,$tamanoLetra,46,711,"THE",$textColor);
        imagestring($img,$tamanoLetra,42,720,"ORDER",$textColor);
        imagestring($img,$tamanoLetra,50,729,"OF",$textColor);
        
        imagestring($img,$tamanoLetra,95,710,substr($nombreBeneficiarioProveedor,0,35),$textColor);
        imagestring($img,$tamanoLetra,95,720,substr($nombreBeneficiarioProveedor,35,35),$textColor);
        //imagestring($img,2,95,700,"----------------------------------",$textColor);
        $arrayDirProv = explode("\n",$direccionProveedor);
        imagestring($img,$tamanoLetra,95,730,substr(utf8_decode(trim($arrayDirProv[0])),0,35),$textColor);
        imagestring($img,$tamanoLetra,95,740,substr(utf8_decode(trim($arrayDirProv[1])),0,35),$textColor);
        imagestring($img,$tamanoLetra,95,750,substr(utf8_decode(trim($arrayDirProv[2])),0,35),$textColor);
        
        imagestring($img,$tamanoLetra,280,680,$idProveedor,$textColor);
                
        imagestring($img,$tamanoLetra,350,675,str_pad($nombreEmpresa,30," ",STR_PAD_BOTH),$textColor);
        imagestring($img,$tamanoLetra,390,685,"VOID AFTER 90 DAYS",$textColor);
	
//        imagestring($img,$tamanoLetra,380,720,"**********************",$textColor);
//        imagestring($img,$tamanoLetra,380,730,"*** NOT NEGOTIABLE ***",$textColor);
//        imagestring($img,$tamanoLetra,380,740,"**********************",$textColor);
        
        imagestring($img,$tamanoLetra,355,712,"BY",$textColor);
        imageline($img, 370, 724, 560, 724, $textColor);//linea donde firma 1
        imagestring($img,$tamanoLetra,355,745,"BY",$textColor);
        imageline($img, 370, 755, 560, 755, $textColor);//linea donde firma 2
        
        imagestring($img,$tamanoLetra,390,755,"AUTHORIZED SIGNATURE",$textColor);
        
        
        
        //este es el encabezado con todas las facturas
        
        $queryUsuario = sprintf("SELECT nombre_usuario FROM pg_usuario WHERE id_usuario = %s LIMIT 1",
        valTpDato($row["id_usuario"],"int"));
        $rsUsuario = mysql_query($queryUsuario);
        if(!$rsUsuario) { die(mysql_error()."<br>".__LINE__); }
        
        $rowUusario = mysql_fetch_assoc($rsUsuario);
        
        imagestring($img,$tamanoLetra,30,30,"CHECK",$textColor);
        imagestring($img,$tamanoLetra,30,40,"CONTROL NO. ".$row["numero_cheque"],$textColor);         
        imagestring($img,$tamanoLetra,210,40,"ISSUED BY: ".$rowUusario["nombre_usuario"],$textColor);         
        
        imagestring($img,$tamanoLetra,400,30,$nombreEmpresa,$textColor);         
        imagestring($img,$tamanoLetra,400,40,trim($dirCortaArray[1]),$textColor);         
        
        //PARA RECUADRO FACTURAS        
        imagestring($img,$tamanoLetra,35,60,"INVOICE",$textColor);
        imagestring($img,$tamanoLetra,30,70,"STOCK NO",$textColor);
        
        imagestring($img,$tamanoLetra,100,60,"INVOICE",$textColor);
        imagestring($img,$tamanoLetra,110,70,"DATE",$textColor);
        
        imagestring($img,$tamanoLetra,155,60,"PURCHASE",$textColor);
        imagestring($img,$tamanoLetra,155,70,"ORDER NO",$textColor);
        
        imagestring($img,$tamanoLetra,220,65,"COMMENT / V.I.N.",$textColor);
        
        imagestring($img,$tamanoLetra,360,65,"AMOUNT",$textColor);
        
        imagestring($img,$tamanoLetra,430,60,"DISC /",$textColor);
        imagestring($img,$tamanoLetra,425,70,"ACCT NO",$textColor);
        
        imagestring($img,$tamanoLetra,515,60,"NET /",$textColor);
        imagestring($img,$tamanoLetra,510,70,"AMOUNT",$textColor);
        
        //TRAZADO DE CUADRO
        imageline($img, 27, 60, 570, 60, $textColor);//primera linea H -
        imageline($img, 27, 85, 570, 85, $textColor);//segunda linea H -
        imageline($img, 27, 500, 570, 500, $textColor);//Ultima linea H -
        
        imageline($img, 27, 60, 27, 500, $textColor);//primera linea V |
        imageline($img, 95, 60, 95, 500, $textColor);//segunda linea V |
        imageline($img, 150, 60, 150, 500, $textColor);//tercera linea V |
        imageline($img, 212, 60, 212, 500, $textColor);//cuarta linea V |
        imageline($img, 340, 60, 340, 500, $textColor);//quinta linea V |
        imageline($img, 415, 60, 415, 500, $textColor);//sexta linea V |
        imageline($img, 485, 60, 485, 500, $textColor);//septima linea V |
        imageline($img, 570, 60, 570, 500, $textColor);//octava linea V |
        
        //prueba test usar para cuadrar los items
//        imagestring($img,$tamanoLetra,30,90,"1988-2015",$textColor);
//        imagestring($img,$tamanoLetra,98,90,strtoupper(date("dMy",strtotime($row['fecha_registro']))),$textColor);
//        imagestring($img,$tamanoLetra,150,90,"1988-2015",$textColor);
//        imagestring($img,$tamanoLetra,217,90,substr("SUPERVISION ENERO TOCARS VEGA BAJA",0,17),$textColor);
//        imagestring($img,$tamanoLetra,217,100,substr("SUPERVISION ENERO TOCARS VEGA BAJA",17,17),$textColor);
//        imagestring($img,$tamanoLetra,345,90,str_pad(number_format("213437.57", 2, ".", ","), 10," ", STR_PAD_LEFT),$textColor);
//        imagestring($img,$tamanoLetra,418,90,str_pad(number_format("13437.57", 2, ".", ","), 9," ", STR_PAD_LEFT),$textColor);
//        imagestring($img,$tamanoLetra,485,90,str_pad(number_format("13437.57", 2, ".", ","), 12," ", STR_PAD_LEFT),$textColor);
        
        $queryFacturasCheque = sprintf("SELECT id_factura, tipo_documento FROM te_cheques WHERE id_cheque = %s LIMIT 1",
                               $row["id_cheque"]);
        $rsFacturasCheque = mysql_query($queryFacturasCheque);
        if(!$rsFacturasCheque){ die(mysql_error()."<br>Linea: ".__LINE__); }
        
        $rowFacturasCheque = mysql_fetch_assoc($rsFacturasCheque);
        
        if($rowFacturasCheque["id_factura"] == "0"){//es propuesta
            
            $queryFacturasNotas = sprintf("SELECT  
                                        te_propuesta_pago_detalle.id_factura,
                                        te_propuesta_pago_detalle.monto_pagar,
                                        te_propuesta_pago_detalle.tipo_documento,
                                        IF(te_propuesta_pago_detalle.tipo_documento = 0, 
                                            (SELECT cp_factura.numero_factura_proveedor FROM cp_factura WHERE cp_factura.id_factura = te_propuesta_pago_detalle.id_factura), 
                                            (SELECT cp_notadecargo.numero_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_propuesta_pago_detalle.id_factura)) AS numero,
                                        IF(te_propuesta_pago_detalle.tipo_documento = 0, 
                                            (SELECT cp_factura.fecha_registro FROM cp_factura WHERE cp_factura.id_factura = te_propuesta_pago_detalle.id_factura), 
                                            (SELECT cp_notadecargo.fecha_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_propuesta_pago_detalle.id_factura)) AS fecha,
                                        IF(te_propuesta_pago_detalle.tipo_documento = 0, 
                                            (SELECT cp_factura.observacion_factura FROM cp_factura WHERE cp_factura.id_factura = te_propuesta_pago_detalle.id_factura), 
                                            (SELECT cp_notadecargo.observacion_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_propuesta_pago_detalle.id_factura)) AS observacion,
                                        IF(te_propuesta_pago_detalle.tipo_documento = 0, 
                                            (SELECT cp_factura.subtotal_factura FROM cp_factura WHERE cp_factura.id_factura = te_propuesta_pago_detalle.id_factura), 
                                            (SELECT cp_notadecargo.subtotal_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_propuesta_pago_detalle.id_factura)) AS subtotal,
                                        IF(te_propuesta_pago_detalle.tipo_documento = 0, 
                                            (SELECT cp_factura.subtotal_descuento FROM cp_factura WHERE cp_factura.id_factura = te_propuesta_pago_detalle.id_factura), 
                                            (SELECT cp_notadecargo.subtotal_descuento_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_propuesta_pago_detalle.id_factura)) AS descuento
                                        FROM te_propuesta_pago
                                        INNER JOIN te_propuesta_pago_detalle ON te_propuesta_pago.id_propuesta_pago = te_propuesta_pago_detalle.id_propuesta_pago
                                        WHERE id_cheque = %s",
                            $row["id_cheque"]);
            
        }else{// es individual, tomar en cuenta tipo_documento
            
            $tipoDoc = $rowFacturasCheque["tipo_documento"];
            $queryFacturasNotas = sprintf("SELECT  
                                        te_cheques.id_factura,
                                        te_cheques.monto_cheque AS monto_pagar,
                                        te_cheques.tipo_documento,
                                        IF(".$tipoDoc." = 0, 
                                            (SELECT cp_factura.numero_factura_proveedor FROM cp_factura WHERE cp_factura.id_factura = te_cheques.id_factura), 
                                            (SELECT cp_notadecargo.numero_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_cheques.id_factura)) AS numero,
                                        IF(".$tipoDoc." = 0, 
                                            (SELECT cp_factura.fecha_registro FROM cp_factura WHERE cp_factura.id_factura = te_cheques.id_factura), 
                                            (SELECT cp_notadecargo.fecha_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_cheques.id_factura)) AS fecha,
                                        IF(".$tipoDoc." = 0, 
                                            (SELECT cp_factura.observacion_factura FROM cp_factura WHERE cp_factura.id_factura = te_cheques.id_factura), 
                                            (SELECT cp_notadecargo.observacion_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_cheques.id_factura)) AS observacion,
                                        IF(".$tipoDoc." = 0, 
                                            (SELECT cp_factura.subtotal_factura FROM cp_factura WHERE cp_factura.id_factura = te_cheques.id_factura), 
                                            (SELECT cp_notadecargo.subtotal_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_cheques.id_factura)) AS subtotal,
                                        IF(".$tipoDoc." = 0, 
                                            (SELECT cp_factura.subtotal_descuento FROM cp_factura WHERE cp_factura.id_factura = te_cheques.id_factura), 
                                            (SELECT cp_notadecargo.subtotal_descuento_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_cheques.id_factura)) AS descuento
                                        FROM te_cheques                                        
                                        WHERE id_cheque = %s LIMIT 1",
                            $row["id_cheque"]);
            
        }
        
        $rsFacturasNotas = mysql_query($queryFacturasNotas);
        if(!$rsFacturasNotas) { die(mysql_error()."<br>Linea: ".__LINE__."<br>Query: ".$queryFacturasNotas); }
        
        $y = 90;//linea primera inicial
        $linea = 10;//sumatoria por cada linea
        $separacionItems = 5;//cantidad de separacion entre items, cada factura
        
        //cuento las observaciones para ver cuantas lineas van a salir
        while($rowContarObservacion = mysql_fetch_assoc($rsFacturasNotas)){
            $cantidadRegistros++;
            $cantidadLineasObservacion += ceil(strlen($rowContarObservacion["observacion"])/17);            
        }
        if($cantidadLineasObservacion == 0){ $cantidadLineasObservacion = 1; }
        $totalLineasObservacion = $y + ($cantidadLineasObservacion * $linea)+($cantidadRegistros*$separacionItems);
        //echo $totalLineasObservacion;
        mysql_data_seek($rsFacturasNotas, 0);
        
        if($totalLineasObservacion > 470){//si hay muchas lineas, no desglosar observacion para que salga completa las facturas
            $desgloseObservacion = false;
        }else{//si hay menos
            $desgloseObservacion = true;
        }
        
        while($rowFactuasNotas = mysql_fetch_assoc($rsFacturasNotas)){
            $subtotalTotal += $rowFactuasNotas["subtotal"];
            $descuentoTotal += $rowFactuasNotas["descuento"];
            $montoPagarTotal += $rowFactuasNotas["monto_pagar"];
            
            $lineasObservacion = ceil(strlen($rowFactuasNotas["observacion"])/17);//17 caracteres es el limite del espacio "comment"
            if($lineasObservacion == 0 || $lineasObservacion == ""){
                $lineasObservacion = 1;
            }
            
            imagestring($img,$tamanoLetra,30,$y,substr($rowFactuasNotas["numero"],0,9),$textColor);
            imagestring($img,$tamanoLetra,98,$y,strtoupper(date("dMy",strtotime($rowFactuasNotas["fecha"]))),$textColor);
            //imagestring($img,$tamanoLetra,150,$y,"1988-2015",$textColor);//numero orden de compra no se usa
            if($desgloseObservacion){
                for($i=0; $i<=$lineasObservacion; $i++){
                    imagestring($img,$tamanoLetra,217,$y+($linea*$i),substr($rowFactuasNotas["observacion"],0+($i*17),17),$textColor);
                }
            }else{
                imagestring($img,$tamanoLetra,217,$y,substr($rowFactuasNotas["observacion"],0,17),$textColor);
            }
            imagestring($img,$tamanoLetra,345,$y,str_pad(number_format($rowFactuasNotas["subtotal"], 2, ".", ","), 10," ", STR_PAD_LEFT),$textColor);
            if($rowFactuasNotas["descuento"] !=0){
                imagestring($img,$tamanoLetra,418,$y,str_pad(number_format($rowFactuasNotas["descuento"], 2, ".", ","), 9," ", STR_PAD_LEFT),$textColor);
            }            
            imagestring($img,$tamanoLetra,485,$y,str_pad(number_format($rowFactuasNotas["monto_pagar"], 2, ".", ","), 12," ", STR_PAD_LEFT),$textColor);
            
            if($desgloseObservacion){//sumar lineas de observacion
                $y = $y+($linea*$lineasObservacion)+$separacionItems;
            }else{//sumatoria 1 linea
                $y = $y+$linea+$separacionItems;
            }
            
            //480 es el limite de Y antes de salirse del recuadro (ultima linea escribible)
        }
        
        imagestring($img,$tamanoLetra,345,480,str_pad(number_format($subtotalTotal, 2, ".", ","), 10," ", STR_PAD_LEFT),$textColor);
        if($descuentoTotal != 0){
            imagestring($img,$tamanoLetra,418,480,str_pad(number_format($descuentoTotal, 2, ".", ","), 9," ", STR_PAD_LEFT),$textColor);
        }
        imagestring($img,$tamanoLetra,485,480,str_pad(number_format($montoPagarTotal, 2, ".", ","), 12," ", STR_PAD_LEFT),$textColor);
        
        imagestring($img,$tamanoLetra,30,500,"DETACH AT PERFORATION BEFORE DEPOSITING CHECK",$textColor);
        imagestring($img,$tamanoLetra,430,500,"REMITTANCE ADVICE",$textColor);
        
        //ORIGINAL
	$r = imagepng($img,"../img/tmp/cheque.png");
	if($_GET["reimpresion"] != 1){
		$pdf->AddPage();
		$pdf->Image("../img/tmp/cheque.png", 0.25, 0.25, 31, 26);
	}
        //PRIMERA COPIA
        $img2 = ImageCreateFrompng("../img/tmp/cheque.png");      
//        $textColorGris = imagecolorallocate($img2,  238, 233, 233);
//        $fuenteLetra = "clases/fuentes/arial.ttf";
//        imagecolortransparent($img2, $textColorGris);
        
		imagestring($img2,$tamanoLetra,380,710,"**********************",$textColor);
        imagestring($img2,$tamanoLetra,380,720,"**********************",$textColor);
        imagestring($img2,$tamanoLetra,380,730,"*** NOT NEGOTIABLE ***",$textColor);
        imagestring($img2,$tamanoLetra,380,740,"**********************",$textColor);
        imagestring($img2,$tamanoLetra,235,755,"ACCOUNTING COPY",$textColor);
        $src = imagecreatefrompng("../img/nonegotiable4.png");
        if(!imagecopyresampled($img2, $src, 100, 600, 0, 0, 400, 142, 857, 241)){//imagen crear, imagen copiar, x,y destino. x,y a copiar, width height a copiar, 100 transparencia
            die ("Error no negociable");
        }
        
        imagepng($img2,"../img/tmp/cheque2.png");        
	$pdf->AddPage();
	$pdf->Image("../img/tmp/cheque2.png", 0.25, 0.25, 31, 26);
        
        
        //SEGUNDA COPIA
        $img3 = ImageCreateFrompng("../img/tmp/cheque.png");        
        
		imagestring($img3,$tamanoLetra,380,710,"**********************",$textColor);
        imagestring($img3,$tamanoLetra,380,720,"**********************",$textColor);
        imagestring($img3,$tamanoLetra,380,730,"*** NOT NEGOTIABLE ***",$textColor);
        imagestring($img3,$tamanoLetra,380,740,"**********************",$textColor);
        imagestring($img3,$tamanoLetra,245,755,"FILE COPY",$textColor);        
        if(!imagecopyresampled($img3, $src, 100, 600, 0, 0, 400, 142, 857, 241)){//imagen crear, imagen copiar, x,y destino. x,y a copiar, width height a copiar, 100 transparencia
            die ("Error no negociable");
        }
        imagepng($img3,"../img/tmp/cheque3.png");        
	$pdf->AddPage();
	$pdf->Image("../img/tmp/cheque3.png", 0.25, 0.25, 31, 26);
        
        imagedestroy($img);
        imagedestroy($img2);
        imagedestroy($img3);        
        unlink("../img/tmp/cheque.png");
        unlink("../img/tmp/cheque2.png");
        unlink("../img/tmp/cheque3.png");
        
}

$pdf->SetDisplayMode(88);
//$pdf->AutoPrint(true);
$pdf->Output();

function abreviacionCentavos($string,$monto){
    $separacion = explode("CON",$string);
    if(count($separacion) == 1){
        return $string;
    }
    
    $string = $separacion[0]."CON ";
    
    if($pos = strpos($monto, ",")){//si usa el formato con coma 1.929,28
        $delimitador = ",";
    }else{//si usa solo puntos 1929.28
        $delimitador = ".";
    }
    
    $centavos = end(explode($delimitador,$monto));
    
    return $string.$centavos."/100";
}

?>