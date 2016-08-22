<?php session_start();
set_time_limit(0);

include("FuncionesPHP.php");

header("Content-Type: application/vnd.ms-excel");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Disposition: attachment; filename=ComprobantesDiarios.xls");


$fechaElab = date("d/m/Y");
$horaElab = date("g:i:s A");

if ($cDesde1== ""){
	$cDesde1= '0';
	$cHasta1= '9999999';
}

if ($cDesde3 != ""){
	if ($cDesde3 == "Diarios"){
		$oTablaSelec = 'D';
	}
	if ($cDesde3 == "Posteriores"){
		$oTablaSelec = 'P';
	}
	if ($cDesde3 == "Historicos"){
		$oTablaSelec = 'H';
	}
}

if ($oTablaSelec == 'D'){
	$sTablaEncabeza = "enc_diario"; 
	$sTablaMovimien = "movimien";
}
if ($oTablaSelec == 'P'){
	$sTablaEncabeza = "enc_dif"; 
	$sTablaMovimien = "movimiendif";
}
if ($oTablaSelec == 'H'){
	$sTablaEncabeza = "enc_historico"; 
	$sAno = trim(intval(obFecha($cHasta2,'M')));
	$sTablaMovimien = "movhistorico".$sAno;
}
//if($_SESSION["CCSistema"] != ""){
$EstadoCuenta = "sipre_contabilidad.cuenta";
$EstadoCT = "sipre_contabilidad.transacciones";
$EstadoDT = "sipre_contabilidad.documentos";
$EstadoCC = "sipre_contabilidad.centrocosto";
$EstadoIM = "sipre_contabilidad.centrocosto";
/*}else{
$EstadoCuenta = "cuenta";
$EstadoCT = "transacciones";
$EstadoDT = "documentos";
$EstadoCC = "centrocosto";
$EstadoIM = "centrocosto";
}*/

$con = ConectarBD();

$SqlDes='Select descrip from parametros';
$exc = EjecutarExec($con,$SqlDes) or die($SqlDes);
while ($row = ObtenerFetch($exc)) {
	$nombreEmpresa = $row[0];
}

$sCampos= " 
	a.comprobant
	,a.concepto
	,a.fecha
	,c.codigo
	,b.documento
	,c.descripcion
	,b.descripcion
	,b.debe
	,b.haber
	,d.codigo as CT
	,e.codigo as DT
	,f.codigo
	,d.descripcion as CTDES
	,e.descripcion as DTDES
	,g.codigo
	,a.Usuario_i";
		
$sTabla=" 
	$sTablaEncabeza a
	,$sTablaMovimien b
	,$EstadoCuenta c
	,$EstadoCT d
	,$EstadoDT e
	,$EstadoCC f
	,$EstadoIM g";
		
if ($cDesde2 != ''){
	$sCondicion=" f.codigo = a.cc and  a.comprobant = b.comprobant and c.codigo = b.codigo and a.comprobant between '$cDesde1' and '$cHasta1'";
	$sCondicion.= " and b.CT = d.codigo and b.DT = e.codigo"; 
	$sCondicion.= " and a.fecha = b.fecha";
	$sCondicion.= " and a.fecha between '$cDesde2' and '$cHasta2'"; 

	
	if($cDesde4 != ''){ 
		$sCondicion.= " and a.cc = '$cDesde4'"; 
		$sCondicion.= " and b.cc = '$cDesde4'"; 
	}	else{
		$sCondicion.= " and a.cc is not null";
		$sCondicion.= " and b.cc is not null";
	}
	$sCondicion.= " and b.im = g.codigo"; 
	$sCondicion.= " order by a.cc,a.comprobant,a.fecha,OrdenRen"; 
}else{
	$sCondicion=" f.codigo = a.cc and a.comprobant = b.comprobant and c.codigo = b.codigo and a.comprobant between '$cDesde1' and '$cHasta1'"; 
	if($cDesde4 != ''){ 
		$sCondicion.=" and a.cc = '$cDesde4'"; 
		$sCondicion.= " and b.cc = '$cDesde4'";
	}else{
		$sCondicion.= " and a.cc is not null";
		$sCondicion.= " and b.cc is not null";
	
	}
    $sCondicion.= " order by a.cc,a.comprobant,a.fecha,OrdenRen"; 
}
$SqlStr='Select '.$sCampos.' from '.$sTabla. " where " . $sCondicion ;
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if (NumeroFilas($exc)>0){
	$AddPfd= false;
	$iFila = -1;
	$iNro = 0;
	$iFilaTemp = 0;
	$sComprobanteAnt = '';
	$fechaAnt= '';
	$CCAnt = '';
	$ToMontoDebe = 0;
	$ToMontoHaber = 0;

		$htmlHeadItm .= "<tr>";		
			$htmlHeadItm .= "<th>Nro</th>";
			$htmlHeadItm .= "<th>Imputación</th>";
			$htmlHeadItm .= "<th>Cuenta Número</th>";
			$htmlHeadItm .= "<th>DG</th>";
			$htmlHeadItm .= "<th>Referencia</th>";
			$htmlHeadItm .= "<th>Descripción de la cuenta</th>";
			$htmlHeadItm .= "<th>CT</th>";
			$htmlHeadItm .= "<th>Descripción del Movimiento</th>";
			$htmlHeadItm .= "<th>Debe</th>";
			$htmlHeadItm .= "<th>Haber</th>";
		$htmlHeadItm .= "</tr>";
	
	while ($row = ObtenerFetch($exc)) {
		$iNro++;
		$iFila++;
		$iFilaTemp++;
		$comprobant = trim(ObtenerResultado($exc,1,$iFila));
		$concepto= trim(ObtenerResultado($exc,2,$iFila));
		$fecha = obFecha(trim(ObtenerResultado($exc,3,$iFila)));
		$CC = trim(ObtenerResultado($exc,12,$iFila));
		$cElaborado = trim(ObtenerResultado($exc,16,$iFila));
		
		if ($sComprobanteAnt != $comprobant || $fechaAnt != $fecha || $CCAnt != $CC){

			/*$comprobant;
			$fecha;
			$concepto;
			$CC;
			$cElaborado;*/
			$htmlHead .= "<table><tr>";
				$htmlHead .= "<td colspan=\"8\"><b>".$nombreEmpresa."</b></td>";
				$htmlHead .= "<td colspan=\"2\">Fecha: ".$fechaElab."</td>";
			$htmlHead .= "</tr>";
			$htmlHead .= "<tr>";
				$htmlHead .= "<td colspan=\"8\"><b><center>ASIENTO DE DIARIO</center></b></td>";
				$htmlHead .= "<td colspan=\"2\">Hora: ".$horaElab."</td>";
			$htmlHead .= "</tr>";
			$htmlHead .= "<tr>";
				$htmlHead .= "<td colspan=\"8\"></td>";
				$htmlHead .= "<td colspan=\"2\">Unidad Imputación: ".$CC."</td>";
			$htmlHead .= "</tr><tr><td>&nbsp;</td></tr>";
			$htmlHead .= "<tr>";
				$htmlHead .= "<td colspan=\"8\"></td>";
				$htmlHead .= "<td colspan=\"2\"><b>Comprobante: ".$comprobant."</b></td>";
			$htmlHead .= "</tr>";
			$htmlHead .= "<tr>";
				$htmlHead .= "<td colspan=\"8\"><b>Concepto:</b> ".$concepto."</td>";
				$htmlHead .= "<td colspan=\"2\"><b>Fecha: ".$fecha."</b></td>";
			$htmlHead .= "</tr><tr><td></td></tr></table>";
			
			$sComprobanteAnt = $comprobant;
			$fechaAnt = $fecha; 
			$CCAnt = $CC;
		}
				   
		$codigo = trim(ObtenerResultado($exc,4,$iFila));
		$documento = trim(ObtenerResultado($exc,5,$iFila));
		$descripcionCuen = trim(ObtenerResultado($exc,6,$iFila));
		$descripcionMov = trim(ObtenerResultado($exc,7,$iFila));
		$debe = number_format(trim(ObtenerResultado($exc,8,$iFila)),2);
		$haber = number_format(trim(ObtenerResultado($exc,9,$iFila)),2);
		$ToMontoDebe = $ToMontoDebe + ObtenerResultado($exc,8,$iFila);
		$ToMontoHaber = $ToMontoHaber + ObtenerResultado($exc,9,$iFila);
		$CT = ObtenerResultado($exc,10,$iFila);
		$DG = ObtenerResultado($exc,11,$iFila);
		$DescripcionIM = ObtenerResultado($exc,15,$iFila);
		
		$campos = array('  '.$iNro,$DescripcionIM,$codigo,$DG,$documento,$descripcionCuen,$CT,$descripcionMov,$debe,$haber);
				
		$htmlItm .= "<tr>";
		foreach($campos as $indice => $campo){
			$htmlItm .= "<td style=\"white-space:nowrap;\">".$campo."</td>";
		}
		$htmlItm .= "</tr>";
		
	}
	

		$campos = array(number_format($ToMontoDebe,2),number_format($ToMontoHaber,2));
		$ToMontoDebe = 0;
		$ToMontoHaber = 0;
		
		$htmlTotal .= "<tr>";
		$htmlTotal .= "<td colspan=\"8\" style=\"text-align:right;\"><b>TOTALES:</b></td>";
		foreach($campos as $indice => $campo){
			$htmlTotal .= "<td><b>".$campo."</b></td>";
		}
		$htmlTotal .= "</tr>";

	
	$campos = array('TOTALES:',number_format($ToMontoDebe,2),number_format($ToMontoHaber,2));
	$Ancho = array ('227','23','23');
	$Tamaño = array ('12','8','8');
	$TipoLetra = array ('B','B','B');
	$Alinear = array ('R','R','R');
	$Bordes = array ('1','1','1');
	$ToMontoDebe = 0;
	$ToMontoHaber = 0;

}

	$htmlpie = "<table>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
		$htmlpie .= "<tr><td>&nbsp;</td></tr>";
	$htmlpie .= "</table>";
	
	$htmlpie1 = "<table border=\"1\">";
		$htmlpie1 .= "<tr>";
			$htmlpie1 .= "<td  colspan=\"4\"><b>OBSERVACIONES</b></td>";
			$htmlpie1 .= "<td colspan=\"2\"><b>PREPARADO POR</b></td>";
			$htmlpie1 .= "<td><b>REVISADO POR</b></td>";
			$htmlpie1 .= "<td><b>AUDITADO POR</b></td>";
			$htmlpie1 .= "<td><b>TRANSCRITO</b></td>";
			$htmlpie1 .= "<td><b>DIARIO POR</b></td>";
		$htmlpie1 .= "</tr>";
		$htmlpie1 .= "<tr>";
			$htmlpie1 .= "<td  colspan=\"4\" rowspan=\"2\" valign=\"top\"></td>";
			$htmlpie1 .= "<td colspan=\"2\" rowspan=\"2\" valign=\"top\">$cElaborado</td>";
			$htmlpie1 .= "<td rowspan=\"2\" valign=\"top\"></td>";
			$htmlpie1 .= "<td rowspan=\"2\" valign=\"top\"></td>";
			$htmlpie1 .= "<td rowspan=\"2\" valign=\"top\"></td>";
			$htmlpie1 .= "<td rowspan=\"2\" valign=\"top\"></td>";
		$htmlpie1 .= "</tr>";
	$htmlpie1 .= "</table>";
	
		$html .= $htmlHead;
	$html .= "<table border=\"1\">";
		$html .= $htmlHeadItm;		
		$html .= $htmlItm;
		$html .= $htmlTotal;		
	$html .= "</table>";
	$html .= $htmlpie;
	$html .= $htmlpie1; 
	
	echo $html;
	
	
	
?>