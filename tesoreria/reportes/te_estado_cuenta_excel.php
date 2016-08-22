<?php
require_once ("../../connections/conex.php");
//aqui abajo sino el DIE no funciona y se imprime el error dentro del excel generado // LO CAMBIE ARRIBA SINO TIENE ROLLOS CON HTML AMPERSAND CARACTERES ESPECIALES
header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=archivo.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border=1> ";

echo "<tr>";
    echo "<th>Fecha Registro</th>";
    echo "<th>T.D.</th>";
    echo "<th>Numero Documento</th>";
    echo "<th>Beneficiario</th>";
    echo "<th>Descripcion</th>";
    echo "<th>Debito</th>";
    echo "<th>Credito</th>";
    echo "<th>Saldo</th>";
echo"</tr>";


//if ($_GET['IdEmpresa'] == '')
//		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
//	
//	else if ($_GET['IdEmpresa'] != '')
//		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$_GET['IdEmpresa']."'";
		
	if ($_GET['Cuenta'] != 0)
		$sqlBusq .= " AND te_estado_cuenta.id_cuenta = '".$_GET['Cuenta']."'";
	
	if ($_GET['FechaDesde'] != '')
		$sqlBusqFecha .= " AND DATE_FORMAT(te_estado_cuenta.fecha_registro,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($_GET['FechaDesde']))."' AND '".date("Y/m/d",strtotime($_GET['FechaHasta']))."'";
		
	if($_GET['Estado'] > 0){
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$_GET['Estado']."'";
	}
			
	if($_GET['TipoDoc'] == 'CH')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$_GET['TipoDoc']."'";
	else if($_GET['TipoDoc'] == 'CH ANULADO')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$_GET['TipoDoc']."'";
	else if($_GET['TipoDoc'] == 'TR')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$_GET['TipoDoc']."'";
	else if($_GET['TipoDoc'] == 'DP')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$_GET['TipoDoc']."'";
	else if($_GET['TipoDoc'] == 'ND')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$_GET['TipoDoc']."'";
	else if($_GET['TipoDoc'] == 'NC')
		$sqlBusq .= " AND te_estado_cuenta.tipo_documento = '".$_GET['TipoDoc']."'";
        
        
        $querySaldo = "SELECT saldo FROM cuentas WHERE idCuentas='".$_GET['Cuenta']."'";
	$rsSaldo = mysql_query($querySaldo) or die(mysql_error());
	$rowSaldo = mysql_fetch_array($rsSaldo);
	$saldo = $rowSaldo['saldo'];
        
        $idEmpresa = valTpDato($_GET['IdEmpresa'],"int");
        
        //usado para sumar retencion y comision de tarjetas de credito por punto de venta
        $queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
                INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
        WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
                valTpDato($idEmpresa, "int"));
        $rsConfig403 = mysql_query($queryConfig403);
        if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
        
        $rowConfig403 = mysql_fetch_assoc($rsConfig403);
        $esVenezuelaPanama = valTpDato($rowConfig403['valor'],"int"); //1 o null venezuela, 2 panama
	
        $queryMonto = "IF(".$esVenezuelaPanama." = 2 AND tipo_documento = 'NC',
                                
                                CASE 
                                WHEN (SELECT tipo_nota_credito FROM te_nota_credito WHERE te_nota_credito.id_nota_credito = id_documento) = 3 
                                THEN (SELECT te_estado_cuenta.monto + ((te_estado_cuenta.monto*(porcentaje_comision/100)) + (te_estado_cuenta.monto*(porcentaje_islr/100))) FROM te_retencion_punto
                                        WHERE te_retencion_punto.id_cuenta = te_estado_cuenta.id_cuenta AND te_retencion_punto.id_tipo_tarjeta != 6 ORDER BY id_retencion_punto DESC LIMIT 1
                                        )
                                
                                WHEN (SELECT tipo_nota_credito FROM te_nota_credito WHERE te_nota_credito.id_nota_credito = id_documento) = 2
                                THEN (SELECT te_estado_cuenta.monto + (te_estado_cuenta.monto*(porcentaje_comision/100)) FROM te_retencion_punto
                                        WHERE te_retencion_punto.id_cuenta = te_estado_cuenta.id_cuenta AND te_retencion_punto.id_tipo_tarjeta = 6 ORDER BY id_retencion_punto DESC LIMIT 1
                                        )
                                        
                                ELSE
                                    te_estado_cuenta.monto
                                END,
                                te_estado_cuenta.monto) as monto,";
                
        
	$query = "SELECT 
	  te_estado_cuenta.id_estado_cuenta,
	  te_estado_cuenta.tipo_documento,
	  te_estado_cuenta.id_documento,
	  te_estado_cuenta.fecha_registro,
	  te_estado_cuenta.id_cuenta,
	  te_estado_cuenta.id_empresa,
	  ".$queryMonto."
	  te_estado_cuenta.suma_resta,
	  te_estado_cuenta.numero_documento,
	  te_estado_cuenta.desincorporado,
	  te_estado_cuenta.observacion,
	  te_estado_cuenta.estados_principales,
	  DATE_FORMAT(te_estado_cuenta.fecha_registro,'%d-%m-%Y %h:%i %p') as fecha_registro_formato
	FROM
	  te_estado_cuenta
	WHERE
	  te_estado_cuenta.estados_principales <> 0 ".$sqlBusq .$sqlBusqFecha; 

			
$rs = mysql_query($query) or die(mysql_error());
while ($row = mysql_fetch_array($rs)){
	

	echo "<tr> ";
	echo "<td align='left'>".$row['fecha_registro_formato']."</td> ";
	echo "<td align='center'>".tipoDocumento($row['id_estado_cuenta'])."</td> ";
	echo "<td align='center'>".$row['numero_documento']."</td> ";
	echo "<td align='center'>".Beneficiario($row['tipo_documento'],$row['id_documento'])."</td> "; 
	echo "<td align='center'>".$row['observacion']."</td> ";

        if($row['suma_resta'] == 0){
            echo "<td align='right'>".number_format($row['monto'],'2','.',',')."</td> ";
            echo "<td align='right'>".number_format(0,'2','.',',')."</td> ";
            $saldo -= $row['monto'];
        }else if($row['suma_resta'] == 1){
            if($row['tipo_documento'] == "CH ANULADO"){
                echo "<td align='right'>".number_format(0,'2','.',',')."</td> ";
                echo "<td align='right'>".number_format(0,'2','.',',')."</td> ";
            }else{
                echo "<td align='right'>".number_format(0,'2','.',',')."</td>";
                echo "<td align='right'>".number_format($row['monto'],'2','.',',')."</td> ";
                $saldo += $row['monto'];
            }
        }
        
        echo "<td>".number_format($saldo,'2','.',',')."</td>";

	echo "</tr> ";

}
echo "</tr> ";
echo "</table> ";




function Beneficiario($tipodocumento,$id){
	
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$id);

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	if($tipodocumento == 'NC'){
            $respuesta = "Nota de Credito";
        }
		
	if($tipodocumento == 'ND'){
            $respuesta = "Nota de Debito";
        }
		
	if($tipodocumento == 'TR'){
		
            $query = sprintf("SELECT * FROM te_transferencia WHERE id_transferencia = '%s'",$id);
            $rs = mysql_query($query) or die(mysql_error());
            $row = mysql_fetch_array($rs);

            if($row['beneficiario_proveedor'] == 1){
                $respuesta = nombreP($row['id_beneficiario_proveedor']);
            }else{
                $respuesta = nombreB($row['id_beneficiario_proveedor']);
            }
	}
	
	if($tipodocumento == 'CH'){
		
	$query = sprintf("SELECT * FROM te_cheques WHERE id_cheque = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
            if($row['beneficiario_proveedor'] == 1){
                $respuesta = nombreP($row['id_beneficiario_proveedor']);
            }else{
                $respuesta = nombreB($row['id_beneficiario_proveedor']);
            }
	}
	
	if($tipodocumento == 'CH ANULADO'){
		
            $query = sprintf("SELECT * FROM te_cheques_anulados WHERE id_cheque = '%s'",$id);
            $rs = mysql_query($query) or die(mysql_error());
            $row = mysql_fetch_array($rs);
	
            if($row['beneficiario_proveedor'] == 1){
                $respuesta = nombreP($row['id_beneficiario_proveedor']);
            }else{
                $respuesta = nombreB($row['id_beneficiario_proveedor']);
            }
	}
	
	
	if($tipodocumento == 'DP'){
            $respuesta = "Deposito";
        }
	
	return $respuesta;
}



function tipoDocumento($id){
	
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$id);

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	if($row['tipo_documento'] == 'NC'){
		$queryNC = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'", $row['id_documento']);
		$rsNC = mysql_query($queryNC);
		if (!$rsNC) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowNC = mysql_fetch_array($rsNC);
		if($rowNC['tipo_nota_credito'] == '1')
			$respuesta = "NC";
		else if($rowNC['tipo_nota_credito'] == '2')
			$respuesta = "NC/TD";
		else if($rowNC['tipo_nota_credito'] == '3')
			$respuesta = "NC/TC";
		else if($rowNC['tipo_nota_credito'] == '4')
			$respuesta = "NC/TR";
	}
	if($row['tipo_documento'] == 'ND')
		$respuesta = "ND";
	if($row['tipo_documento'] == 'TR')
		$respuesta = "TR";
	if($row['tipo_documento'] == 'CH')
		$respuesta = "CH";
	if($row['tipo_documento'] == 'CH ANULADO')
		$respuesta = "CH ANULADO";
	if($row['tipo_documento'] == 'DP')
		$respuesta = "DP";
	
	return $respuesta;
}


function nombreB($id){	
	
	$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$id);
	$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
	$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
	$respuesta = $rowBeneficiario['nombre_beneficiario'];

	return $respuesta;
}
function nombreP($id){
	
	$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$id);
	$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
	$rowProveedor = mysql_fetch_array($rsProveedor);
	$respuesta = $rowProveedor['nombre'];
	
	return $respuesta;
}


?>