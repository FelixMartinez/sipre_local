<?php
require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$idCheque = $_GET['id'];
$tipoDocumento = $_GET['documento']; //1 cheque, 2 transferencia, 3 retencion, 4 lote envia el id dependiendo del documento cheque/transf para buscar todas las retenciones, id retencion una sola retencion (reimpresion)
$contador = 0;

if($tipoDocumento == 1 || $tipoDocumento == 2){//1 cheque, 2 transferencia (multiple)
    $query = sprintf("SELECT * FROM vw_te_retencion_cheque WHERE id_cheque = %s",
		valTpDato($idCheque,"int"));
}elseif($tipoDocumento == 3){//3 retencion (individual
    $query = sprintf("SELECT * FROM vw_te_retencion_cheque WHERE id_retencion_cheque = %s",
		valTpDato($idCheque,"int"));
}elseif($tipoDocumento == 4){//4 impresion por lotes (busqueda del listado)
	$valCadBusq = array_values(json_decode($_GET['valBusq'], true));

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        
	if ($valCadBusq[0] == ''){
		//$sqlBusq .= " vw_te_retencion_cheque.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
	}else if ($valCadBusq[0] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."vw_te_retencion_cheque.id_empresa = '".$valCadBusq[0]."'";
	}
        
	if ($valCadBusq[1] != 0){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."vw_te_retencion_cheque.id_proveedor = '".$valCadBusq[1]."'";
	}
        
	if ($valCadBusq[2] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."DATE_FORMAT(vw_te_retencion_cheque.fecha_registro,'%Y/%m') = '".date("Y/m",strtotime('01-'.$valCadBusq[2]))."'";
	}
        
	if ($valCadBusq[3] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" (vw_te_retencion_cheque.numero_factura LIKE %s 
									OR vw_te_retencion_cheque.numero_control_factura LIKE %s) ",
									valTpDato('%'.$valCadBusq[3].'%', 'text'),
									valTpDato('%'.$valCadBusq[3].'%', 'text'));
	}
	
	if ($valCadBusq[4] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if($valCadBusq[4] == 1){
			$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado = 1 ");
		}else{
			$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado IS NULL ");
		}
	}
	
	if ($valCadBusq[5] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.tipo_documento = %s",
									valTpDato($valCadBusq[5],"int"));
	}
	
	$query = "SELECT * FROM vw_te_retencion_cheque ".$sqlBusq. " ORDER BY id_retencion_cheque ASC";
	
}

$rs = mysql_query($query) or die(mysql_error());
if(mysql_num_rows($rs) == 0){
	die("No se encontraron registros con el criterio de busqueda.");
}

while ($row = mysql_fetch_assoc($rs)){
    $img = @imagecreate(530, 630) or die("No se puede crear la imagen");

    $queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
            $row['id_empresa']);
    $rsEmp = mysql_query($queryEmp);
    if (!$rsEmp){ die(mysql_error()."<br><br>Line: ".__LINE__); }
    $rowEmp = mysql_fetch_assoc($rsEmp);

    $queryBaseImponible = sprintf("SELECT base_imponible, iva FROM cp_factura_iva WHERE id_factura = %s ",$row['id_factura']);
    $rsBaseImponible = mysql_query($queryBaseImponible);

    if (!$rsBaseImponible){ return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__); }
    $rowBaseImponible = mysql_fetch_array($rsBaseImponible);

    if ($rowBaseImponible['base_imponible']==0){
        $BaseImponible=$row['subtotal_factura'];
    }else {
        $BaseImponible=$rowBaseImponible['base_imponible'];
    }

    // ESTABLECIENDO LOS COLORES DE LA PALETA
    $backgroundColor = imagecolorallocate($img, 255, 255, 255);
    $textColor = imagecolorallocate($img, 0, 0, 0);

    imagestring($img,1,60,10,$row['nombre_empresa'],$textColor);
    imagestring($img,1,60,20,"RIF : ",$textColor);
    imagestring($img,1,110,20,$row['rif_empresa'],$textColor);

    $direccionEmpresa = str_replace(",", " ", $row['direccion_empresa']);
    imagestring($img,1,19,50,"DIR : ",$textColor);
    imagestring($img,1,64,50,trim(substr(strtoupper($direccionEmpresa),0,80)),$textColor);
    imagestring($img,1,64,60,trim(substr(strtoupper($direccionEmpresa),80,160)),$textColor);
    imagestring($img,1,64,70,trim(substr(strtoupper($direccionEmpresa),160,240)),$textColor);

    imagestring($img,1,19,85,"N COMPROBANTE: ",$textColor);
    imagestring($img,1,100,85,str_pad($row['id_retencion_cheque'], 4, "0", STR_PAD_LEFT),$textColor);

    imagestring($img,1,0,100,str_pad("COMPROBANTE DE RETENCION DE I.S.L.R", 110, " ", STR_PAD_BOTH),$textColor);

    imagestring($img,1,19,120,"FOLIO : ",$textColor);
    //imagestring($img,1,64,121,$row['rif_empresa'],$textColor);

    imagestring($img,1,400,120,"FECHA : ",$textColor);
    imagestring($img,1,450,120,": ".date("d-m-Y", strtotime($row['fecha_registro'])),$textColor);

    imagestring($img,1,0,140,str_pad("DATOS DEL BENEFICIARIO", 110, " ", STR_PAD_BOTH),$textColor);
    imagestring($img,1,0,150,"-------------------------------------------------------------------------------------------------------------------",$textColor);

    if($row['tipo']==1){
        $queryNota = sprintf("SELECT 
								cp_proveedor.lrif,
								cp_proveedor.rif,
								cp_proveedor.nombre,
								cp_proveedor.direccion,
								cp_proveedor.telefono
							FROM cp_notadecargo
							INNER JOIN cp_proveedor ON (cp_notadecargo.id_proveedor = cp_proveedor.id_proveedor) 
							WHERE cp_notadecargo.id_notacargo = %s",
					$row['id_factura']);
        $rsNota = mysql_query($queryNota) or die(mysql_error());
        $rowNota = mysql_fetch_array($rsNota);

        imagestring($img,1,19,160,"NOMBRE : ",$textColor);
        imagestring($img,1,100,160,$rowNota['nombre'],$textColor);

        $direccionEmpresa = str_replace(",", " ", $rowNota['direccion']);
        imagestring($img,1,19,170,"DIRECCION : ",$textColor);
        imagestring($img,1,100,170,trim(substr(strtoupper($direccionEmpresa),0,60)),$textColor);
        imagestring($img,1,100,180,trim(substr(strtoupper($direccionEmpresa),60,120)),$textColor);
        imagestring($img,1,100,190,trim(substr(strtoupper($direccionEmpresa),120,180)),$textColor);

        imagestring($img,1,19,200,"RIF : ",$textColor);
        imagestring($img,1,100,200,$rowNota['lrif']."-".$rowNota['rif'],$textColor);

        imagestring($img,1,19,210,"TELEFONO : ",$textColor);
        imagestring($img,1,100,210,$rowNota['telefono'],$textColor);

        imagestring($img,1,300,280,"BASE RETENCION : ",$textColor);

        $subTotalFactura = $row['subtotal_factura'];

        if($row['base_imponible_retencion'] != 0){
            $subTotalFactura = $row['base_imponible_retencion'];
        }
        imagestring($img,1,400,280,strtoupper(str_pad(number_format($subTotalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

    }else{

        imagestring($img,1,19,160,"NOMBRE : ",$textColor);
        imagestring($img,1,100,160,$row['nombre'],$textColor);

        $direccionEmpresa = str_replace(",", " ", $row['direccion_proveedor']);
        imagestring($img,1,19,170,"DIRECCION : ",$textColor);
        imagestring($img,1,100,170,trim(substr(strtoupper($direccionEmpresa),0,60)),$textColor);
        imagestring($img,1,100,180,trim(substr(strtoupper($direccionEmpresa),60,120)),$textColor);
        imagestring($img,1,100,190,trim(substr(strtoupper($direccionEmpresa),120,180)),$textColor);

        imagestring($img,1,19,200,"RIF : ",$textColor);
        imagestring($img,1,100,200,$row['rif_proveedor'],$textColor);

        imagestring($img,1,19,210,"TELEFONO : ",$textColor);
        imagestring($img,1,100,210,$row['telefono'],$textColor);

        imagestring($img,1,300,280,"BASE RETENCION : ",$textColor);
        if($row['base_imponible_retencion'] != 0){
            $BaseImponible = $row['base_imponible_retencion'];
        }
        imagestring($img,1,400,280,strtoupper(str_pad(number_format($BaseImponible, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

    }

    imagestring($img,1,0,220,"-------------------------------------------------------------------------------------------------------------------",$textColor);

    imagestring($img,1,0,230,str_pad("DATOS DE LA RETENCION", 110, " ", STR_PAD_BOTH),$textColor);
    imagestring($img,1,0,240,"-------------------------------------------------------------------------------------------------------------------",$textColor);

    imagestring($img,1,19,260,"CONCEPTO : ",$textColor);
    //imagestring($img,1,113,472,"?",$textColor);

    imagestring($img,1,19,280,"FACTURA No : ",$textColor);
    imagestring($img,1,100,280,str_pad($row['numero_factura'], 15, " ", STR_PAD_LEFT),$textColor);

    imagestring($img,1,19,290,"No CONTROL: ",$textColor);
    imagestring($img,1,100,290,str_pad($row['numero_control_factura'], 15, " ", STR_PAD_LEFT),$textColor);
    
    if($row['tipo_documento'] == 0){//CHEQUE
        $queryVerificarMonto = sprintf("SELECT 
											monto_pagar 
                                        FROM te_propuesta_pago 
                                        INNER JOIN te_propuesta_pago_detalle ON (te_propuesta_pago.id_propuesta_pago = te_propuesta_pago_detalle.id_propuesta_pago) 
                                        WHERE id_factura = %s 
										AND id_cheque = %s 
										AND tipo_documento = %s",
                                valTpDato($row['id_factura'],'int'),
                                valTpDato($row['id_cheque'],"int"), 
                                valTpDato($row['tipo'],"int"));
								
    }else if($row['tipo_documento'] == 1){//TRANSFERENCIA         
        $queryVerificarMonto = sprintf("SELECT monto_pagar
                                        FROM te_propuesta_pago_transferencia 
                                        INNER JOIN te_propuesta_pago_detalle_transferencia ON (te_propuesta_pago_transferencia.id_propuesta_pago = te_propuesta_pago_detalle_transferencia.id_propuesta_pago) 
                                        WHERE id_factura = %s 
										AND id_cheque = %s 
										AND tipo_documento = %s",
                                valTpDato($row['id_factura'],'int'),
                                valTpDato($row['id_cheque'],"int"), 
                                valTpDato($row['tipo'],"int"));
    }
    
    $rsVerificarMonto = mysql_query($queryVerificarMonto);
    
    if (mysql_num_rows($rsVerificarMonto)){
		$rowVerificarMonto = mysql_fetch_array($rsVerificarMonto);
		$monto_pagado = $rowVerificarMonto['monto_pagar'];
    }else{
		$monto_pagado = $row['monto_cheque'];                
    }

    imagestring($img,1,19,300,"MONTO PAGADO : ",$textColor);
    imagestring($img,1,100,300,str_pad(number_format($monto_pagado, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);

    imagestring($img,1,300,300,"IMPUESTO RETENIDO : ",$textColor);
    imagestring($img,1,400,300,strtoupper(str_pad(number_format($row['monto_retenido'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

	// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
	$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($row["id_empresa"], "int"));
	$rsConfig403 = mysql_query($queryConfig403);
	if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsConfig403 = mysql_num_rows($rsConfig403);
	$rowConfig403 = mysql_fetch_assoc($rsConfig403);
	if($rowConfig403['valor'] == NULL){
		//$rowConfig403['valor'] = 1; //por defecto venezuela 1
		die("No se ha configurado formato de cheque. 403");
	}

	if($rowConfig403['valor'] == 3){// Total a pagar solo puerto
		imagestring($img,1,300,310,"TOTAL A PAGAR : ",$textColor);
		imagestring($img,1,400,310,strtoupper(str_pad(number_format($row['total_cuenta_pagar'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	}

    imagestring($img,1,300,290,"% RETENIDO : ",$textColor);
    imagestring($img,1,400,290,strtoupper(str_pad(number_format($row['porcentaje_retencion'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

    imagestring($img,1,70,550,"FIRMA Y SELLO DEL AGENTE",$textColor);
    imagestring($img,1,90,560,"DE RETENCION",$textColor);

    imagestring($img,1,330,550,"FIRMA DEL BENEFICIARIO",$textColor);

    imageline($img,200,540,30,540,$textColor);
    imageline($img,480,540,300,540,$textColor);

    $r = imagepng($img,"../img/tmp/constancia_retencion_impuesto".$contador.".png");
    $contador++;
    
}

for ($i = 0;$i < $contador;$i++){
	$pdf->AddPage();
	
	$pdf->Image("../img/tmp/constancia_retencion_impuesto".$i.".png", 15, 55, 580, 680);
	$pdf->Image("../../".$rowEmp['logo_familia'], '20', '65', '50', '25');
	unlink("../img/tmp/constancia_retencion_impuesto".$i.".png");//limpiando temporales
}

$pdf->SetDisplayMode(88);
//$pdf->AutoPrint(true);
$pdf->Output();

?>