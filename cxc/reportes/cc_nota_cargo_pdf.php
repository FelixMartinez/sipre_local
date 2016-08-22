<?php
require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$idDocumento = $_GET["valBusq"];

    $query = sprintf("SELECT
            cj_cc_notadecargo.*,
            cj_cc_cliente.id,
            CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS ci_cliente,
            CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
            cj_cc_cliente.telf,
            cj_cc_cliente.direccion AS direccion_cliente

    FROM cj_cc_notadecargo
            INNER JOIN cj_cc_cliente ON (cj_cc_cliente.id = cj_cc_notadecargo.idCliente)
    WHERE cj_cc_notadecargo.idNotaCargo = %s",
            valTpDato($idDocumento, "int"));
    

$rs = mysql_query($query, $conex);
//if (!$rsAlmacen) return $objResponse->alert(mysql_error());
$row = mysql_fetch_assoc($rs);

$img = @imagecreate(530, 630) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

imagestring($img,1,395,10,"NOTA DEBITO",$textColor);

imagestring($img,1,350,20,"SERIE-SR",$textColor);

imagestring($img,1,350,30,"NRO NOTA DE DEBITO",$textColor);

imagestring($img,1,440,30,": ".$row['numeroNotaCargo'],$textColor);

imagestring($img,1,350,50,"FECHA EMISION",$textColor);
imagestring($img,1,440,50,": ".date("d-m-Y", strtotime($row['fechaRegistroNotaCargo'])),$textColor);

/*imagestring($img,1,350,60,"FECHA VENCIMIENTO",$textColor);
imagestring($img,1,440,60,": ".date("d-m-Y", strtotime($row['fechaVencimientoNotaCargo'])),$textColor);*/

if ($row['tipoNotaCargo'] == 0) { // 0 = Credito, 1 = Contado
	imagestring($img,1,450,70,"CRED. ".number_format($row['diasDeCreditoNotaCargo'])." DIAS",$textColor); // <----
}

/*imagestring($img,1,350,80,"ASESOR",$textColor);
imagestring($img,1,440,80,": ".strtoupper($row['nombre_empleado']),$textColor);*/


imagestring($img,1,5,40,strtoupper($row['nombre_cliente']),$textColor); // <----
imagestring($img,1,210,40,"CODIGO",$textColor);
imagestring($img,1,240,40,": ".$row['id'],$textColor);

$direccionCliente = str_replace(",", " ", $row['direccion_cliente']);

imagestring($img,1,5,50,trim(substr($direccionCliente,0,50)),$textColor); // <----

imagestring($img,1,5,60,trim(substr($direccionCliente,50,35)),$textColor); // <----
imagestring($img,1,195,60,"TELEF.",$textColor);
imagestring($img,1,225,60,": ".$row['telf'],$textColor);

imagestring($img,1,5,70,trim(substr($direccionCliente,85,40)),$textColor); // <----
imagestring($img,1,195,70,"R.I.F.",$textColor);
imagestring($img,1,225,70,": ".$row['ci_cliente'],$textColor);


imagestring($img,1,0,100,"-------------------------------------------------------------------------------------------------------------------",$textColor);

imagestring($img,1,10,110,"DESCRIPCION",$textColor); // <----

imagestring($img,1,0,120,"-------------------------------------------------------------------------------------------------------------------",$textColor);

imagestring($img,1,10,130,utf8_decode(strtoupper($row['observacionNotaCargo'])),$textColor);

imagestring($img,1,315,540,"SUB-TOTAL",$textColor);
    imagestring($img,1,400,540,":",$textColor);
    imagestring($img,1,455,540,strtoupper(str_pad(number_format($row['subtotalNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

    imagestring($img,1,315,550,"DESCUENTO",$textColor);
    imagestring($img,1,400,550,":",$textColor);
    imagestring($img,1,455,550,strtoupper(str_pad(number_format($row['descuentoNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

    imagestring($img,1,315,570,"BASE IMPONIBLE",$textColor);
    imagestring($img,1,400,570,":",$textColor);
    imagestring($img,1,455,570,strtoupper(str_pad(number_format($row['baseImponibleNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);


    imagestring($img,1,315,590,"IMPUESTO.",$textColor);
    imagestring($img,1,400,590,":",$textColor);
    
    imagestring($img,1,455,590,strtoupper(str_pad(number_format($row['calculoIvaNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

    imagestring($img,1,315,600,"MONTO NO GRAVADO",$textColor);
    imagestring($img,1,400,600,":",$textColor);
    imagestring($img,1,455,600,strtoupper(str_pad(number_format($row['montoExentoNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

    imagestring($img,1,315,610,"IMPUESTO AL LUJO",$textColor);
    imagestring($img,1,400,610,":",$textColor);
    
    imagestring($img,1,455,610,strtoupper(str_pad(number_format($row['ivaLujoNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

    imagestring($img,1,315,620,"TOTAL",$textColor);
    imagestring($img,1,400,620,":",$textColor);
    imagestring($img,1,455,620,strtoupper(str_pad(number_format($row['montoTotalNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor); // <----


$arrayImg[] = "tmp/"."nota_cargo".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, 55, 580, 680);
	}
}

$pdf->SetDisplayMode(88);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
	}
}
?>