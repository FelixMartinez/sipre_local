<?php session_start();
require('fpdf.php');
include_once("FuncionesPHP.php");
header("Content-Type: application/vnd.ms-excel");

header("Expires: 0");

header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

header("content-disposition: attachment;filename=CatalogoDeCuentas.xls");

$fecha = date("d/m/Y");
$horaElab = date("g:i:s A");

if ($cDesde1 == ""){
   $cDesde1= '';
   $cHasta1= 'zzzzzzzzzzzzzzzzzzzz';
}

$con = ConectarBD();

$EstadoCuenta =  "sipre_contabilidad.cuenta";

$sTabla=$EstadoCuenta;
$sCondicion=" codigo between '$cDesde1' and '$cHasta1' order by codigo";
$sCampos='Codigo';
$sCampos.=',Descripcion';
$SqlStr='Select '.$sCampos.' from '.$sTabla. ' where '. $sCondicion ;
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$htmlHead = "<table><tr>";
	$htmlHead .= "<td colspan=\"2\" align=\"right\"><b>Fecha: ".$fecha."</b></td></tr>";
	$htmlHead .= "<td colspan=\"2\" align=\"right\"><b>Hora: ".$horaElab."</b></td></tr>";
	$htmlHead .= "<td></td></tr>";
	$htmlHead .= "<td colspan=\"2\"><b><center>CATÁLOGO DE CUENTAS</center></b></td>";
$htmlHead .= "</tr></table>";

$htmlHeadCuenta  = "<table border=\"1\"><tr>";
$htmlHeadCuenta .= "<td align=\"center\"><b>Código</b></td>";
$htmlHeadCuenta .= "<td align=\"center\"><b>Descripción</b></td>";
$htmlHeadCuenta .= "</tr></table>";

$htmlCuenta  = "<table>";

if(NumeroFilas($exc)>0){
	$iFila = -1;
    while ($row = ObtenerFetch($exc)) {
    	$iFila++;
    	$codigo = trim(ObtenerResultado($exc,1,$iFila)) ; 
        $descripcion = trim(ObtenerResultado($exc,2,$iFila));
        
		$htmlCuenta .= "<tr>";
			$htmlCuenta .= "<td>$codigo</td>";
			$htmlCuenta .= "<td>$descripcion</td>";	
		$htmlCuenta .= "</tr>";
				
	} 
}

$htmlCuenta .= "</table>";

$html  = $htmlHead;
$html .= $htmlHeadCuenta;
$html .= $htmlCuenta;

echo $html;

$pdf->Output();
?>