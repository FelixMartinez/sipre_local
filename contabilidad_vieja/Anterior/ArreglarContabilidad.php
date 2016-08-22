<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();
$Mesword = MesLetras(12);
$Mes_d = substr($Mesword,0,3)."_d";
$Mes_h = substr($Mesword,0,3)."_h";
$Ano =2008;

$sql = "delete from oriomka_inavi.cuentaverificar" ;
$rs1 = EjecutarExec($con,$sql) or die($sql); 
$sql = "delete from oriomka_inavi.cnt0000_2" ;
$rs1 = EjecutarExec($con,$sql) or die($sql); 

$sCondicion1 = " fecha_year = 2008";
$sql = "insert into oriomka_inavi.cuentaverificar (codigo,Descripcion,saldo_ant,debe,haber)
Select codigo,Descripcion,$Mesword,0,0 from ". "oriomka_inavi.cnt0000 where " .$sCondicion1 ;
$rs1 = EjecutarExec($con,$sql) or die($sql); 

//******************************Actuializar Movimientos*********************************************// 
for($iMes = 1; $iMes <= 11; $iMes++){
$Mesword = MesLetras($iMes);
$Mes_d = substr($Mesword,0,3)."_d";
$Mes_h = substr($Mesword,0,3)."_h";
echo $Mesword . "<br>"; 
$MesMov = "oriomka_inavi.movhistorico".trim($iMes);
$SqlStr = "Select codigo,sum(debe),sum(haber) from $MesMov  group by codigo order by codigo";
	$rs5 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
						if (NumeroFilas($rs5)>0){
							while($row = mysql_fetch_array($rs5)){
							$CodigoMov  = trim($row[0]);
							$DebeMov = $row[1];
							$HaberMov = $row[2];
							 
							 	/*para actualizar las cuentas */
					$SqlStr= "  update oriomka_inavi.cuentaverificar set debe = debe + $DebeMov,
								haber = haber + $HaberMov                                
								where (length(rtrim(oriomka_inavi.cuentaverificar.codigo)) < length(rtrim('$CodigoMov'))
								and rtrim(oriomka_inavi.cuentaverificar.codigo) = substring('$CodigoMov',1,length(rtrim(oriomka_inavi.cuentaverificar.codigo))))
								or oriomka_inavi.cuentaverificar.codigo = '$CodigoMov'"; 
								$rs4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
							}
						}
 

//******************************Fin Actuializar Movimientos*********************************************// 

 $SqlStr=" INSERT oriomka_inavi.cnt0000_2 (codigo,Descripcion,fecha_year)
     select rtrim(a.codigo),a.Descripcion,$Ano  From oriomka_inavi.cuentaverificar a left join oriomka_inavi.cnt0000_2 b 
     on rtrim(a.codigo) = rtrim(b.codigo) AND b.fecha_year = $Ano
     WHERE b.codigo is null";
 $exc1 = EjecutarExec($con,$SqlStr) or die($SqlStr) ;
 
 
  $SqlStr= "	update oriomka_inavi.cnt0000_2 a,oriomka_inavi.cuentaverificar b set a.$Mesword = b.saldo_ant,a.$Mes_d = b.debe,a.$Mes_h = b.haber 
				where a.codigo = b.codigo and fecha_year = $Ano ";
 $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);	

 /* colocar e catalogo de cuenta en cero*/  
$SqlStr= " update oriomka_inavi.cuentaverificar
set saldo_ant = oriomka_inavi.cuentaverificar.saldo_ant +  (oriomka_inavi.cuentaverificar.debe) - (oriomka_inavi.cuentaverificar.haber),
oriomka_inavi.cuentaverificar.debe = 0,oriomka_inavi.cuentaverificar.haber = 0";
$exc3 = EjecutarExec($con,$SqlStr) or die($SqlStr);
/* fin colocar e catalogo de cuenta en cero*/  

 

 
}

echo "proceso realizado satisfactoriamente";