<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_cierre_caja_contado"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE 2.0 :. Caja de Repuestos y Servicios - Corte de Caja/Contado</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
	<style type="text/css">
		<!--
			.Estilo3 {color: #FFFFFF}
			.Estilo3 {color: #FFFFFF}
			.Estilo14 {color: #FFFFFF; font-weight: bold; }
		-->
	</style>
	<style type="text/css">
		td img {
			display: block;
		}
		td.tituloCampo,
		table tr td input[type=text]{
			font-size:10pt !important;
		}
		td.tituloColumna{
			font-size:10pt !important;
		}
		td.inputCantidadNoDisponible{
			font-size:10pt !important;
		}
	</style>
	<style type="text/css">
		<!--
			#apDiv1 {
				position:absolute;
				left:1042px;
				top:467px;
				width:47px;
				height:53px;
				z-index:1;
			}
			#apDiv2 {
				position:absolute;
				left:1111px;
				top:609px;
				width:100px;
				height:56px;
				z-index:1;
			}
		-->
	</style>
	<script language="JavaScript" type="text/javascript"></script>
</head>

	<script type="text/javascript" >
		function realizarCierreParcial(caja){
			if (confirm("Esta seguro de realizar el cierre Parcial?")){
				return true
			} else {
				return false
			}
		}
		
		function realizarCierreTotal(caja){
			if (confirm("Esta seguro de realizar el cierre Total?")){
				return true
			} else {
				return false
			}
		}
	</script>
	<body>
		<div id="divGeneralPorcentaje">
			<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
			<?php
				$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
				
				$sql = "SELECT * from pg_empresa WHERE id_empresa = ".$idEmpresa."";
				$consulta = mysql_query($sql);
				$fila = mysql_fetch_array($consulta);
				$fecha = date("Y-m-d");
			?>
			
			<div id="divInfo">
				<table width="100%" border="0" class="solo_print">
				<tr align="left">
					<td width="8%" class="textoNegroNegrita_14px">
						<div align="center">
							<img src="../<?php echo $fila['logo_familia']; ?>" height="50" alt=""/>
						</div>
					</td>
					<td width="92%" class="textoNegroNegrita_14px">
						<?php echo utf8_encode($fila['nombre_empresa']); ?>
							<br/>
						<?php echo $fila[rif]; ?>
					</td>
				</tr>
				</table>
				<table align="center" border="0" width="100%">
				<tr>
					<td align="center" class="tituloPaginaCajaRS">Corte de Caja (Repuestos y Servicios)<br/><span class="textoNegroNegrita_10px">(Resumen de Saldos - CONTADO)</span></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
				</tr>
				</table>
				<?php
					$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
					
					// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
					$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
						INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
						valTpDato(1, "int")); // 1 = Empresa cabecera
					$rsConfig400 = mysql_query($queryConfig400);
					if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsConfig400 = mysql_num_rows($rsConfig400);
					$rowConfig400 = mysql_fetch_assoc($rsConfig400);
						
					if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
						$andEmpresa = sprintf(" AND sa_iv_apertura.id_empresa = %s",
							valTpDato($idEmpresa,"int"));
							
					} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
						$andEmpresa = '';
					}
					
					$sqlConsultarFecha = sprintf("SELECT
						sa_iv_cierredecaja.fechaCierre,
						sa_iv_apertura.fechaAperturaCaja,
						sa_iv_cierredecaja.tipoCierre,
						sa_iv_apertura.cargaEfectivoCaja,
						sa_iv_apertura.id_empresa,
						pg_empresa.nombre_empresa
					FROM
						sa_iv_apertura
						INNER JOIN sa_iv_cierredecaja ON (sa_iv_apertura.id = sa_iv_cierredecaja.id)
						INNER JOIN pg_empresa ON (pg_empresa.id_empresa = sa_iv_apertura.id_empresa)
					WHERE
						sa_iv_cierredecaja.idCierre = (SELECT
															MAX(sa_iv_cierredecaja.idCierre)
														FROM
															sa_iv_apertura
														INNER JOIN sa_iv_cierredecaja ON (sa_iv_apertura.id = sa_iv_cierredecaja.id)
														WHERE
															sa_iv_apertura.idCaja = 2)
						%s", $andEmpresa);
					$consultaConsultaFecha = mysql_query($sqlConsultarFecha) or die(mysql_error());
					
					if($lafila = mysql_fetch_array($consultaConsultaFecha)){
						$montoApertura = $lafila["cargaEfectivoCaja"];
						$fechaActual = date("Y-m-d");
						$nombreEmpresa = $lafila["nombre_empresa"];
						
						if($lafila["fechaCierre"]!=$fechaActual || $lafila["fechaAperturaCaja"]!=$fechaActual){
							$anio = substr($lafila["fechaAperturaCaja"],0,4);
							$mes = substr($lafila["fechaAperturaCaja"],5,2);
							$dia = substr($lafila["fechaAperturaCaja"],8,2);
							$fechaAperturaCaja=$dia."-".$mes."-".$anio;
							
							$anio = substr($lafila["fechaCierre"],0,4);
							$mes = substr($lafila["fechaCierre"],5,2);
							$dia = substr($lafila["fechaCierre"],8,2);
							$fechaCierreCaja = $dia."-".$mes."-".$anio;
				?>
				<script type="text/javascript">
					alert("La caja esta abierta para la empresa <?php echo $nombreEmpresa;?> con fecha <?php echo $fechaAperturaCaja;?>. <?php if ($lafila["tipoCierre"]=='0') { $cierre="Total";} else { if ($lafila["tipoCierre"]=='2') { $cierre="Parcial";} }?>
					<?php if ($lafila["tipoCierre"]!='1') {?>\nCierre <?php echo $cierre." ".$fechaCierreCaja; } ?>\n Debe realizar el cierre de la caja anterior.");
				</script>
				<?php
						}
					}
				?>
				<form id="form1" name="form1">
					<?php
						// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
						$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
							INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
						WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
							valTpDato(1, "int")); // 1 = Empresa cabecera
						$rsConfig400 = mysql_query($queryConfig400);
						if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsConfig400 = mysql_num_rows($rsConfig400);
						$rowConfig400 = mysql_fetch_assoc($rsConfig400);
							
						if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
							$andEmpresa = sprintf(" AND ape.id_empresa = %s",
								valTpDato($idEmpresa,"int"));
								
						} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
							$andEmpresa = '';
						}
					
						$sql3 = sprintf("SELECT
							ape.fechaAperturaCaja,
							ape.horaApertura,
							ape.saldoCaja,
							ape.id,
							ape.statusAperturaCaja,
							ape.cargaEfectivoCaja
						FROM
							caja as ca,
							sa_iv_apertura as ape
						WHERE
							ca.idCaja = ape.idCaja
							AND ape.statusAperturaCaja IN (1,2)
							AND ape.idCaja = 2
							%s",$andEmpresa);
						$consulta3 = mysql_query($sql3);
						
						if(list($fechaAperturaCaja,$horaApertura,$saldoCaja,$id,$statusAperturaCaja,$cargaEfectivoCaja)=mysql_fetch_array($consulta3)){
					?>
					<table width="62%" border="0" align="center">
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Fecha Apertura
						</td>
						<td style="text-align:left"><input name="nombreCaja2" type="text" id="nombreCaja2" style="text-align:center;"
							value="<?php
										$anio = substr($fechaAperturaCaja,0,4);
										$mes = substr($fechaAperturaCaja,5,2);
										$dia = substr($fechaAperturaCaja,8,2);
										$fechaAperturaCaja2 = $dia."-".$mes."-".$anio;
										echo $fechaAperturaCaja2;
									?>"size="25" readonly="readonly" align="top" />
						</td>
						<td>&nbsp;</td>
						<td scope="row" class="tituloCampo" align="left">
							Fecha Actual
						</td>
						<td style="text-align:left">
							<?php $fechaPago = date("d-m-Y"); ?>
							<input name="fechaDePago" type="text" id="fechaDePago" style="text-align:center; " value="<?php echo $fechaPago; ?>" size="25" readonly="readonly"/>
						</td>
					</tr>
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Fecha Cierre
						</td>
						<td style="text-align:left"><input id="txtFechaCierre" name="txtFechaCierre" type="text" style="text-align:center;" value="<?php echo '---'; ?>" size="25" readonly="readonly" align="top"/>
						</td>
						<td>&nbsp;</td>
						<td scope="row" class="tituloCampo" align="left">
							Ejecución Cierre
						</td>
						<td style="text-align:left"><input id="txtFechaCierre" name="txtFechaCierre" type="text" style="text-align:center;" value="<?php echo '---'; ?>" size="25" readonly="readonly" align="top"/>
						</td>
					</tr>
					<tr>
						<td scope="row" class="tituloCampo" align="left">
							Saldo Apertura
						</td>
						<td style="text-align:left">
							<input name="txtCargaEnEfectivo" type="text" id="txtCargaEnEfectivo" style="text-align:right;" value="<?php echo $cargaEfectivoCaja2=number_format($cargaEfectivoCaja,2,".",",");?>" size="25" readonly="readonly" align="right"/>
						</td>
						<td>&nbsp;</td>
						<td align="left" class="inputCantidadNoDisponible" scope="row">
							Estado de Caja
						</td>
						<td style="text-align:left">
							<input name="txtEstadoDeCaja" type="text" id="txtEstadoDeCaja" style="text-align:center;" size="25" readonly="readonly" class="inputCantidadNoDisponible"/>
							<?php
								if ($statusAperturaCaja==1){
							?>
							<script type="text/javascript">
								document.getElementById("txtEstadoDeCaja").value="ABIERTA";
							</script>
							<?php
								}else{
									if ($statusAperturaCaja == 0){
							?>
							<script type="text/javascript">
								document.getElementById("txtEstadoDeCaja").value="CERRADA TOTALMENTE";
							</script>
							<?php
									}else{
										if ($statusAperturaCaja == 2){
							?>
							<script type="text/javascript">
								document.getElementById("txtEstadoDeCaja").value="CERRADA PARCIALMENTE";
							</script>
							<?php
										}
									}
								}
							?>
						</td>
					</tr>
					<tr>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
						<td>&nbsp;</td>
						<td align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2" align="center" scope="row" class="tituloCaja">PAGOS</td>
						<td>&nbsp;</td>
						<td colspan="2" align="center" class="tituloCaja">
							VENTAS
						</td>
					</tr>
					<tr>
						<td width="238" bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Efectivo
						</td>
						<td width="175" style="text-align:left">
							<input name="txtTotalEnEfectivo" type="text" id="txtTotalEnEfectivo" style="text-align:right" value="<?php echo number_format(0,2,".",",");?>" size="25" readonly="readonly"/>
						</td>
						<td width="20">&nbsp;</td>
						<td width="172" class="tituloColumna" align="left">
							Ventas a Contado:
						</td>
						<td width="190" style="text-align:left">
						<input name="txtVentasAContado" type="text" id="txtVentasAContado" class="trResaltarTotal" style="text-align:right" size="25" readonly="readonly"/>
						<?php
							// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
							$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
								INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
							WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
								valTpDato(1, "int")); // 1 = Empresa cabecera
							$rsConfig400 = mysql_query($queryConfig400);
							if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRowsConfig400 = mysql_num_rows($rsConfig400);
							$rowConfig400 = mysql_fetch_assoc($rsConfig400);
								
							if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
								$andEmpresa = sprintf(" AND cj_cc_encabezadofactura.id_empresa = %s",
									valTpDato($idEmpresa,"int"));
									
							} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
								$andEmpresa = '';
							}
							
							$queryVentasAcontado = sprintf("SELECT
								SUM(cj_cc_encabezadofactura.montoTotalFactura) AS monto_total
							FROM cj_cc_encabezadofactura
							WHERE cj_cc_encabezadofactura.condicionDePago = 1
								AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura IN (0,1,3)
								AND cj_cc_encabezadofactura.fechaRegistroFactura = '%s'
								AND cj_cc_encabezadofactura.idFactura NOT IN (SELECT cxc_nc.idDocumento FROM cj_cc_notacredito cxc_nc
																				WHERE cxc_nc.fechaNotaCredito = '%s'
																					AND cxc_nc.tipoDocumento LIKE 'FA') %s",
								$fechaAperturaCaja,
								$fechaAperturaCaja,
								$andEmpresa);
							$rsVentasAcontado = mysql_query($queryVentasAcontado)or die(mysql_error().$queryVentasAcontado);
							$rowVentasAcontado = mysql_fetch_assoc($rsVentasAcontado);
						?>
							<script type="text/javascript">
								document.getElementById("txtVentasAContado").value="<?php echo number_format($rowVentasAcontado['monto_total'],2,".",",");?>";
							</script>
						
						</td>
					</tr>
					<tr>
						<td height="20" bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Cheques
						</td>
						<td style="text-align:left">
							<input name="txtTotalEnCheques" type="text" id="txtTotalEnCheques" style="text-align:right" value="<?php echo number_format(0,2,".",",");?>" size="25" readonly="readonly"/>
						</td>
						<td width="20">&nbsp;</td>
						<td width="172" class="tituloColumna" align="left">
							Ventas a Crédito:
						</td>
						<td width="190" style="text-align:left">
						<input name="txtVentasACredito" type="text" id="txtVentasACredito" class="trResaltarTotal" style="text-align:right" size="25" readonly="readonly"/>
						<?php
							// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
							$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
								INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
							WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
								valTpDato(1, "int")); // 1 = Empresa cabecera
							$rsConfig400 = mysql_query($queryConfig400);
							if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRowsConfig400 = mysql_num_rows($rsConfig400);
							$rowConfig400 = mysql_fetch_assoc($rsConfig400);
							
							if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
								$andEmpresa = sprintf(" AND cj_cc_encabezadofactura.id_empresa = %s",
									valTpDato($idEmpresa,"int"));
									
							} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
								$andEmpresa = '';
							}
							
							$queryVentasAcredito = sprintf("SELECT
								SUM(cj_cc_encabezadofactura.montoTotalFactura) AS monto_total
							FROM
								cj_cc_encabezadofactura
							WHERE
								cj_cc_encabezadofactura.condicionDePago = 0
								AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura IN (0,1,3)
								AND cj_cc_encabezadofactura.fechaRegistroFactura = '%s'
								AND ((cj_cc_encabezadofactura.anulada = 'SI'
										AND (SELECT COUNT(nota_cred.idNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idDocumento = cj_cc_encabezadofactura.idFactura
												AND nota_cred.tipoDocumento LIKE 'FA'
												AND nota_cred.fechaNotaCredito = cj_cc_encabezadofactura.fechaRegistroFactura) = 0)
									OR cj_cc_encabezadofactura.anulada <> 'SI')
								%s",
								$fechaAperturaCaja,$andEmpresa);
							$rsVentasAcredito = mysql_query($queryVentasAcredito)or die(mysql_error().$queryVentasAcredito);
							$rowVentasAcredito = mysql_fetch_assoc($rsVentasAcredito);
						?>
							<script type="text/javascript">
								document.getElementById("txtVentasACredito").value="<?php echo number_format($rowVentasAcredito['monto_total'],2,".",",");?>";
							</script>
						
						</td>
					</tr>
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Depósitos
						</td>
						<td style="text-align:left">
							<input name="txtTotalEnDepositos" type="text" id="txtTotalEnDepositos" style="text-align:right" value="<?php echo number_format(0,2,".",",");?>" size="25" readonly="readonly"/>
						</td>
						<td width="20">&nbsp;</td>
						<td colspan="2" align="center" class="tituloCaja">
							COBRANZAS
						</td>
					</tr>
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Tarjeta De Cr&eacute;dito
						</td>
						<td style="text-align:left">
							<input name="txtTotalEnTarjetaDeCredito" type="text" id="txtTotalEnTarjetaDeCredito" style="text-align:right" size="25" readonly="readonly" />
						</td>
						<td>&nbsp;</td>
						<td bgcolor="#999999" class="tituloCampo" align="left">Cobranzas Repuestos:</td>
						<td><input name="txtCobranzasRep" type="text" id="txtCobranzasRep" style="text-align:right" size="25" readonly="readonly"/></td>
					</tr>
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Tarjeta De D&eacute;bito
						</td>
						<td style="text-align:left">
							<input name="txtTotalEnTarjetaDeDebito" type="text" id="txtTotalEnTarjetaDeDebito" style="text-align:right" size="25" readonly="readonly"/>
						</td>
						<td>&nbsp;</td>
						<td bgcolor="#999999" class="tituloCampo" align="left">Cobranzas Servicios:</td>
						<td><input name="txtCobranzasServ" type="text" id="txtCobranzasServ" style="text-align:right" size="25" readonly="readonly"/></td>
					</tr>
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Transferencia Bancaria
						</td>
						<td style="text-align:left">
							<input name="txtTotalEnTransferenciaBancaria" type="text" id="txtTotalEnTransferenciaBancaria" style="text-align:right" size="25" readonly="readonly"/>
						</td>
						<td>&nbsp;</td>
						<td bgcolor="#999999" class="tituloCampo" align="left">Cobranzas Administración:</td>
						<td><input name="txtCobranzasAdmon" type="text" id="txtCobranzasAdmon" style="text-align:right" size="25" readonly="readonly"/></td>
					</tr>
					<tr>
						<td scope="row" class="tituloColumna" align="left">Subtotal Ingresos:</td>
						<td style="text-align:left">
							<input name="txtSubtotal1" type="text" id="txtSubtotal1" class="trResaltarTotal" size="25" style="text-align:right" readonly="readonly"/>
						</td>
						<td>&nbsp;</td>
						<td scope="row" class="tituloColumna" align="left">Total Cobranzas:</td>
						<td><input name="txtTotalCobranza" type="text" id="txtTotalCobranza" class="trResaltarTotal" size="25" style="text-align:right" readonly="readonly" /></td>
					</tr>
					<tr>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
						<td>&nbsp;</td>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
					</tr>
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">Retenci&oacute;n I.S.L.R</td>
						<td style="text-align:left">
							<input name="txtISLR" type="text" id="txtISLR" size="25" style="text-align:right" readonly="readonly"/>
						</td>
						<td width="20">&nbsp;</td>
						<td colspan="2" align="center" class="tituloCaja">
							CASH BACK
						</td>
					</tr>
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">Retenci&oacute;n IVA</td>
						<td style="text-align:left">
							<input name="txtSaldoRetencion" type="text" id="txtSaldoRetencion" style="text-align:right" size="25" readonly="readonly"/>
						</td>
						<td width="20">&nbsp;</td>
						<td scope="row" class="tituloColumna" align="left">Total Cash Back:</td>
						<td width="190" style="text-align:left">
							<input name="txtTotalCashBack" type="text" id="txtTotalCashBack" class="trResaltarTotal" size="25" style="text-align:right" readonly="readonly" />
						</td>
					</tr>
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">Otros Impuestos</td>
						<td style="text-align:left">
							<input name="txtOtrosImpuestos" type="text" id="txtOtrosImpuestos" size="25" style="text-align:right" readonly="readonly"/>
						</td>
						<td>&nbsp;</td>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
					</tr>
					<tr>
						<td scope="row" class="tituloColumna" align="left">Subtotal Impuestos:</td>
						<td style="text-align:left">
							<input name="txtSubtotalImpuestos" type="text" id="txtSubtotalImpuestos" class="trResaltarTotal" size="25" readonly="readonly" style="text-align:right"/>
						</td>
						<td>&nbsp;</td>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
					</tr>
					<tr>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
						<td>&nbsp;</td>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
					</tr>
					<tr>
						<td scope="row" class="tituloColumna" align="left">Total Pagos:</td>
						<td style="text-align:left">
							<input name="saldoCaja" type="text" id="saldoCaja" class="trResaltarTotal" style="text-align:right; " size="25" readonly="readonly" align="right"/>
						</td>
						<td>&nbsp;</td>
						<td bgcolor="#FFFFFF"></td>
					</tr>
					<!--<tr>
						<td scope="row" class="tituloColumna" align="left">Total Pagos + Cr&eacute;dito:</td>
						<td style="text-align:left">
							<input name="saldoCaja2" type="text" id="saldoCaja2" class="trResaltarTotal3" style="text-align:right; " size="25" readonly="readonly" align="right"/>
						</td>
						<td>&nbsp;</td>
						<td bgcolor="#FFFFFF">&nbsp;</td>
						<td>&nbsp;</td>
					</tr>-->
					<tr>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
						<td>&nbsp;</td>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2" align="center" scope="row" class="tituloCaja">
							OTROS PAGOS
						</td>
						<td>&nbsp;</td>
						<td bgcolor="#FFFFFF">&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Anticipos aplicados
						</td>
						<td style="text-align:left">
							<input name="txtTotalEnAnticipos" type="text" id="txtTotalEnAnticipos" style="text-align:right" value="<?php echo number_format(0,2,".",",");?>" size="25" readonly="readonly"/>
						</td>
						<td>&nbsp;</td>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
					</tr>
					<tr>
						<td bgcolor="#999999" class="tituloCampo" align="left">
							Notas Crédito aplicadas
						</td>
						<td style="text-align:left">
							<input name="txtTotalEnNotaCredito" type="text" id="txtTotalEnNotaCredito" style="text-align:right;" value="<?php echo number_format(0,2,".",",");?>" size="25" readonly="readonly"/>
						</td>
						<td>&nbsp;</td>
						<td>
							
						</td>
					</tr>
					<tr>
						<td class="tituloColumna" align="left">
							Subtotal Otros Pagos:
						</td>
						<td style="text-align:left">
							<input name="txtSubtotal2" type="text" id="txtSubtotal2" class="trResaltarTotal" size="25" style="text-align:right" readonly="readonly" value="<?php echo number_format(0,2,".",",");?>"/>
						</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td scope="row" align="left">&nbsp;</td>
						<td style="text-align:left">&nbsp;</td>
						<td>&nbsp;</td>
						<td bgcolor="#FFFFFF">&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
					<!--<tr>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Primer Nro. Control:
						</td>
						<td style="text-align:left">
							<input name="pNroControl" type="text" id="pNroControl" style="text-align:right; " value="<?php echo number_format(0,2,".",",");?>" size="25" readonly="readonly" align="right"/>
						</td>
						<td width="20">&nbsp;</td>
						<td bgcolor="#999999" scope="row" class="tituloCampo" align="left">
							Ultimo Nro. Control:
						</td>
						<td style="text-align:left">
							<input name="uNroControl" type="text" id="uNroControl" style="text-align:right; " value="" size="25" readonly="readonly" align="right"/>
						</td>
					</tr>
					<tr>
						<td scope="row" class="tituloCampo" align="left">
							Primer Nro. Factura:
						</td>
						<td style="text-align:left">
							<input name="pNroFactura" type="text" id="pNroFactura" style="text-align:right; " value="<?php echo number_format(0,2,".",",");?>" size="25" readonly="readonly" align="right"/>
						</td>
						<td width="20">&nbsp;</td>
						<td scope="row" class="tituloCampo" align="left">
							Ultimo Nro. Factura:
						</td>
						<td style="text-align:left">
							<input name="uNroFactura" type="text" id="uNroFactura" style="text-align:right; " value="" size="25" readonly="readonly" align="right"/>
						</td>
					</tr>
					<tr align="left">
						<td class="tituloCampo">Primer Nro. Nota Crédito:</td>
						<td><input type="text" id="txtPrimerNroNotaCred" name="txtPrimerNroNotaCred" readonly="readonly" size="25" style="text-align:right;"/></td>
						<td width="20">&nbsp;</td>
						<td class="tituloCampo">Ult. Nro. Nota Crédito:</td>
						<td><input type="text" id="txtUltimoNroNotaCred" name="txtUltimoNroNotaCred" readonly="readonly" size="25" style="text-align:right;"/></td>
					</tr>-->
					<tr>
						<td colspan="5" align="left" class="tituloCampo" scope="row">
							Observaci&oacute;n:
						</td>
					</tr>
					<tr>
						<td colspan="5" align="left" scope="row">
							<input name="txtObservacionCierre" type="text" id="txtObservacionCierre" size="112"/>
						</td>
					</tr>
					<tr>
						<td colspan="5" class="noprint"><hr /></td>
					</tr>
					<tr>
						<td colspan="5" class="noprint" bgcolor="#FFFFFF" scope="row" style="text-align:right">
							<button type="button" id="btnImprimir" name="btnImprimir" onclick="window.print();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
							<!--<button type="button" id="btnCierreTotal" name="btnCierreTotal" onclick="window.location.href = 'cjrs_pagos_cargados_dia_contado.php?acc=2'"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/key_go.png"/></td><td>&nbsp;</td><td>Cierre Total</td></tr></table></button>-->
							<input name="ocultoTipoDeCierreDeCaja" type="hidden" id="ocultoTipoDeCierreDeCaja" value="1"/>
						</td>
					</tr>
					<tr>
						<td colspan="5" bgcolor="#FFFFFF" scope="row" style="text-align:right">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="5" bgcolor="#FFFFFF" scope="row" style="text-align:right">&nbsp;</td>
					</tr>
					</table>
					<br/>
					<br/>
					<table width="75%" border="0" id="tabla_usuario" align="center" class="solo_print">
					<tr>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:right">&nbsp;</td>
						<td width="17%" scope="row" valign="top" style="text-align:center; border-bottom:2px solid #000000;">&nbsp;</td>
						<td width="7%" bgcolor="#FFFFFF" style="text-align:right" scope="row">&nbsp;</td>
						<td width="11%" bgcolor="#FFFFFF" style="text-align:right" scope="row">&nbsp;</td>
						<td width="17%" valign="top" style="text-align:center; border-bottom:2px solid #000000;" >&nbsp;</td>
						<td width="9%" valign="top" >&nbsp;</td>
						<td width="11%" valign="top" >&nbsp;</td>
						<td width="16%" valign="top" style="text-align:center; border-bottom:2px solid #000000;" >&nbsp;</td>
					</tr>
					<tr>
						<td width="12%" scope="row" style="text-align:left" class="tituloColumna">
							Elaborado por:
						</td>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:center;font-size:12px;">
							<span style="text-align:center">
								<?php
									$idUsuario = $_SESSION['idUsuarioSysGts'];
									
									$sqlEmpleadoQueRealizoLaTransaccion = "SELECT
										pg_empleado.nombre_empleado,
										pg_empleado.apellido,
										pg_usuario.nombre_usuario
									FROM
										pg_empleado
										INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
									WHERE pg_usuario.id_usuario = ".$idUsuario;
									$consultaEmpleadoQueRealizoLaTransaccion = mysql_query($sqlEmpleadoQueRealizoLaTransaccion)or die(mysql_error().$sqlEmpleadoQueRealizoLaTransaccion);
									if($usuario = mysql_fetch_array($consultaEmpleadoQueRealizoLaTransaccion)){
										echo utf8_encode($usuario["nombre_empleado"]." ".$usuario["apellido"]);
									}
								?>
							</span>
						</td>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:right">&nbsp;</td>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:left" class="tituloColumna">
							Revisado por:
						</td>
						<td width="17%" scope="row" style="text-align:center;font-size:12px;">
							<?php
								$sqlEmpleadoQueRealizoLaTransaccion = "SELECT
									pg_empleado.nombre_empleado,
									pg_empleado.apellido
								FROM
									pg_cargo_departamento
									INNER JOIN pg_empleado ON (pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento)
								WHERE
									pg_cargo_departamento.clave_filtro = 9
									AND pg_empleado.activo = 1";
								$consultaEmpleadoQueRealizoLaTransaccion = mysql_query($sqlEmpleadoQueRealizoLaTransaccion)or die(mysql_error().$sqlEmpleadoQueRealizoLaTransaccion);
								if($usuario = mysql_fetch_array($consultaEmpleadoQueRealizoLaTransaccion)){
									echo utf8_encode($usuario["nombre_empleado"]." ".$usuario["apellido"]);
								}
							?>
						</td>
						<td width="9%" scope="row" style="text-align:center">&nbsp;</td>
						<td width="11%" scope="row" style="text-align:left" class="tituloColumna">
							Revisado Por:
						</td>
						<td width="16%" scope="row" style="text-align:center;font-size:12px;">
							<?php
								$sqlEmpleadoQueRealizoLaTransaccion = "SELECT
									pg_empleado.nombre_empleado,
									pg_empleado.apellido
								FROM
									pg_cargo_departamento
									INNER JOIN pg_empleado ON (pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento)
								WHERE
									pg_cargo_departamento.clave_filtro = 3
									AND pg_empleado.activo = 1";
								$consultaEmpleadoQueRealizoLaTransaccion = mysql_query($sqlEmpleadoQueRealizoLaTransaccion)or die(mysql_error().$sqlEmpleadoQueRealizoLaTransaccion);
									if($usuario = mysql_fetch_array($consultaEmpleadoQueRealizoLaTransaccion)){
										echo utf8_encode($usuario["nombre_empleado"]." ".$usuario["apellido"]);
									}
							?>
						</td>
					</tr>
					<!--<tr>
						<td scope="row" style="text-align:left">&nbsp;</td>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:center">&nbsp;</td>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:right">&nbsp;</td>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:left">&nbsp;</td>
						<td scope="row" style="text-align:center">&nbsp;</td>
						<td scope="row" style="text-align:center">&nbsp;</td>
						<td scope="row" style="text-align:center">&nbsp;</td>
						<td scope="row" style="text-align:center">&nbsp;</td>
					</tr>
					<tr>
						<td scope="row" style="text-align:left">&nbsp;</td>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:center">&nbsp;</td>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:right">&nbsp;</td>
						<td bgcolor="#FFFFFF" scope="row" style="text-align:left">&nbsp;</td>
						<td scope="row" style="text-align:center">&nbsp;</td>
						<td scope="row" style="text-align:center">&nbsp;</td>
						<td scope="row" style="text-align:center">&nbsp;</td>
						<td scope="row" style="text-align:center">&nbsp;</td>
					</tr>-->
					</table>
					<br/>
					<?php
						}else{
					?>
					<script type="text/javascript">
						alert("Esta Caja No tiene Apertura.");
						window.location.href="cjrs_apertura_caja.php";
					</script>
					<?php
						}
					?>
				</form>
			</div>
			<div class="noprint"><?php include("pie_pagina.php"); ?></div>
		</div>
		<?php
			saldosDeCierre($montoApertura);
		?>
		<script type="text/javascript">
			document.getElementById("txtTotalEnEfectivo").value="<?php echo number_format($varCierreTotalEfectivo,2,".",",");?>";
			document.getElementById("txtTotalEnCheques").value="<?php echo number_format($varCierreTotalCheques,2,".",",");?>";
			document.getElementById("txtTotalEnDepositos").value="<?php echo number_format($varCierreTotalDepositos,2,".",",");?>";
			document.getElementById("txtTotalEnTransferenciaBancaria").value="<?php echo number_format($varCierreTotalTransferenciaBancaria,2,".",",");?>";
			document.getElementById("txtTotalEnTarjetaDeCredito").value="<?php echo number_format($varCierreTotalTarjetaDeCredito,2,".",",");?>";
			document.getElementById("txtTotalEnTarjetaDeDebito").value="<?php echo number_format($varCierreTotalEnTarjetaDeDebito,2,".",",");?>";	
			document.getElementById("txtSubtotal1").value="<?php echo number_format($varCierreTotalEfectivo + $varCierreTotalCheques + $varCierreTotalDepositos + $varCierreTotalTarjetaDeCredito + $varCierreTotalEnTarjetaDeDebito + $varCierreTotalTransferenciaBancaria,2,".",",");?>";		
			document.getElementById("txtTotalEnNotaCredito").value="<?php echo number_format($varCierreTotalEnNotaCredito,2,".",",");?>";
			
			document.getElementById("txtISLR").value="<?php echo number_format($varCierreTotalSaldoRetencionISRL,2,".",",");?>";
			document.getElementById("txtSaldoRetencion").value="<?php echo number_format($varCierreTotalSaldoRetencion,2,".",",");?>";
			document.getElementById("txtOtrosImpuestos").value="<?php echo number_format($txtOtrosImpuestos,2,".",",");?>";
			
			document.getElementById("txtTotalEnAnticipos").value="<?php echo number_format($varCierreTotalEnAnticipos,2,".",",");?>";
			document.getElementById("txtSubtotal2").value="<?php echo number_format($varCierreTotalEnNotaCredito + $varCierreTotalEnAnticipos,2,".",",");?>";
			document.getElementById("txtSubtotalImpuestos").value="<?php echo number_format($varCierreTotalSaldoRetencion + $varCierreTotalSaldoRetencionISRL,2,".",",");?>";
			document.getElementById("saldoCaja").value="<?php echo number_format($varCierreTotalEfectivo + $varCierreTotalCheques + $varCierreTotalDepositos + $varCierreTotalTarjetaDeCredito + $varCierreTotalEnTarjetaDeDebito + $varCierreTotalTransferenciaBancaria + $varCierreTotalSaldoRetencion + $varCierreTotalSaldoRetencionISRL,2,".",",");?>";
			//document.getElementById("saldoCaja2").value="<?php echo number_format($varCierreTotalEfectivo + $varCierreTotalCheques + $varCierreTotalDepositos + $varCierreTotalTarjetaDeCredito + $varCierreTotalEnTarjetaDeDebito + $varCierreTotalTransferenciaBancaria + $varCierreTotalSaldoRetencion + $varCierreTotalSaldoRetencionISRL + $rowVentasAcredito['monto_total'],2,".",",");?>";
			
			//CONTADO + CREDITO EXCLUYENDO LOS PAGOS DE LAS FACTURAS A CRÉDITO - $varTotalPagosCredito
			//document.getElementById("pNroControl").value="<?php echo $varPrimerNroControl;?>";
			//document.getElementById("pNroFactura").value="<?php echo $varPrimerNroFactura;?>";
			//document.getElementById("uNroControl").value="<?php echo $varUltimoNroControl;?>";
			//document.getElementById("uNroFactura").value="<?php echo $varUltimoNroFactura;?>";
			//document.getElementById("txtPrimerNroNotaCred").value="<?php echo $varPrimerNroNotaCredito;?>";
			//document.getElementById("txtUltimoNroNotaCred").value="<?php echo $varUltimoNroNotaCredito;?>";
			
			document.getElementById("txtCobranzasRep").value="<?php echo number_format($varCobranzaRep,2,".",",");?>";
			document.getElementById("txtCobranzasServ").value="<?php echo number_format($varCobranzaServ,2,".",",");?>";
			document.getElementById("txtCobranzasAdmon").value="<?php echo number_format($txtCobranzasAdmon,2,".",",");?>";
			document.getElementById("txtTotalCobranza").value="<?php echo number_format($varTotalCobranza,2,".",",");?>";
			
			document.getElementById("txtTotalCashBack").value="<?php echo number_format($varTotalCashBack,2,".",",");?>";
		</script>
	</body>
</html>
<?php
function saldosDeCierre($montoApertura){
	global $conexion, $varCierreTotalEfectivo,$varCierreTotalCheques,$varCierreTotalDepositos,$varCierreTotalTransferenciaBancaria,$varCierreTotalTarjetaDeCredito,$varCierreTotalEnTarjetaDeDebito,$varCierreTotalEnAnticipos,$varCierreTotalEnNotaCredito,$varCierreTotalSaldoRetencion,$varCierreTotalSaldoRetencionISRL,$montoTotalCierre,$montoTotalMasApertura, $varPrimerNroControl, $varPrimerNroFactura, $varUltimoNroControl, $varUltimoNroFactura, $varPrimerNroNotaCredito, $varUltimoNroNotaCredito, $varTotalPagosCredito,$varCobranzaRep, $varCobranzaServ, $varTotalCobranza, $varTotalCashBack;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$andEmpresa1 = sprintf(" AND (ape.id_empresa = %s)",
			valTpDato($idEmpresa,"int"));
		$andEmpresa2 = sprintf(" AND cj_cc_encabezadofactura.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresa3 = sprintf(" AND cj_cc_anticipo.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresa4 = sprintf(" AND cj_cc_notadecargo.id_empresa = %s",
			valTpDato($idEmpresa,"int"));	
						
		$andEmpresaSql1 = sprintf(" AND an.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
		$andEmpresaSql2 = sprintf(" AND fv.id_empresa = %s",
			valTpDato($idEmpresa,"int"));
						
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresa1 = '';	
		$andEmpresa2 = '';
		$andEmpresa3 = '';
		$andEmpresa4 = '';
		
		$andEmpresaSql1 = '';
		$andEmpresaSql2 = '';
		$andEmpresaSql3 = '';
	}	
	
	$fechaApertura = "(SELECT
		ape.fechaAperturaCaja
	FROM
		caja ca
		INNER JOIN sa_iv_apertura ape ON (ca.idCaja = ape.idCaja)
	WHERE
		ape.statusAperturaCaja IN (1,2)
		AND ape.idCaja = 2
		$andEmpresa1)";
		
	$sqlFormaPago = sprintf("SELECT
		pa.formaPago AS formaPago,
		'1' AS tipoDoc,
		formapagos.nombreFormaPago,
		cj_cc_encabezadofactura.id_empresa AS id_empresa,
		'' AS estatus
	FROM ((bancos ba
		JOIN sa_iv_pagos pa ON ((ba.idBanco = pa.bancoOrigen)))
		JOIN cj_cc_encabezadofactura ON ((pa.id_factura = cj_cc_encabezadofactura.idFactura))
		JOIN formapagos ON ((pa.formaPago = formapagos.idFormaPago)))
	WHERE ((pa.fechaPago = (SELECT ape.fechaAperturaCaja AS fechaAperturaCaja
							FROM (sa_iv_apertura ape 
								JOIN caja ca)
							WHERE ((ape.idCaja = 2) 
								AND (ape.idCaja = ca.idCaja)
								AND (ape.statusAperturaCaja IN (1,2)
								%s))))
		AND (pa.tomadoEnCierre = 0)
		AND cj_cc_encabezadofactura.condicionDePago = 1) %s
	GROUP BY pa.formaPago, cj_cc_encabezadofactura.id_empresa
	
	UNION
	
	SELECT
		(CASE pa.tipoPagoDetalleAnticipo
			WHEN 'EF' THEN '1'
			WHEN 'CH' THEN '2'
			WHEN 'DP' THEN '3'
			WHEN 'TB' THEN '4'
			WHEN 'TC' THEN '5'
			WHEN 'TD' THEN '6'
			WHEN 'CB' THEN '11'
		END) AS formaPago,
		'4' AS tipoDoc,
		formapagos.nombreFormaPago,
		cj_cc_anticipo.id_empresa AS id_empresa,
		cj_cc_anticipo.estatus AS estatus
	FROM ((cj_cc_anticipo
		JOIN cj_cc_detalleanticipo pa ON ((cj_cc_anticipo.idAnticipo = pa.idAnticipo)))
		JOIN bancos ba ON ((pa.bancoClienteDetalleAnticipo = ba.idBanco))
		JOIN formapagos ON ((pa.tipoPagoDetalleAnticipo = formapagos.aliasFormaPago)))
	WHERE ((pa.fechaPagoAnticipo = (SELECT ape.fechaAperturaCaja AS fechaAperturaCaja
									FROM (sa_iv_apertura ape
										JOIN caja ca)
									WHERE ((ape.idCaja = 2)
										AND (ape.idCaja = ca.idCaja)
										AND (ape.statusAperturaCaja IN (1,2)
										%s))))
		AND (pa.tomadoEnCierre IN (0,2))
		AND (cj_cc_anticipo.estatus = 1)
		%s)
	GROUP BY pa.tipoPagoDetalleAnticipo, cj_cc_anticipo.id_empresa

	UNION
		
	SELECT
		nc.idFormaPago AS formaPago,
		'2' AS tipoDoc,
		formapagos.nombreFormaPago,
		cj_cc_notadecargo.id_empresa AS id_empresa,
		'' AS estatus
	FROM ((cj_cc_notadecargo
		JOIN cj_det_nota_cargo nc ON ((cj_cc_notadecargo.idNotaCargo = nc.idNotaCargo)))
		JOIN bancos ba)
		JOIN formapagos ON ((nc.idFormaPago = formapagos.idFormaPago))
	WHERE ((nc.fechaPago = (SELECT ape.fechaAperturaCaja AS fechaAperturaCaja
							FROM (sa_iv_apertura ape
								JOIN caja ca)
							WHERE ((ape.idCaja = 2)
								AND (ape.idCaja = ca.idCaja)
								AND (ape.statusAperturaCaja IN (1,2)
								%s))))
		AND	(nc.tomadoEnCierre = 0)
		AND	(ba.idBanco = nc.bancoOrigen)
		AND cj_cc_notadecargo.fechaRegistroNotaCargo = '".$fechaApertura."' %s)
	GROUP BY nc.idFormaPago, cj_cc_notadecargo.id_empresa",
		$andEmpresa1,$andEmpresa2,$andEmpresa1,$andEmpresa3,$andEmpresa1,$andEmpresa4);
	$consultaFormaPago = mysql_query($sqlFormaPago)or die(mysql_error()."<br>Linea:".__LINE__."<br>Sql:".$sqlFormaPago);
	$sw='';
	$existePagoEnEfectivoOcheque = 0;
	
	while($registro = mysql_fetch_array($consultaFormaPago)){
		$nom = $registro["nombreFormaPago"];
		$varFormaPago = $registro["formaPago"];
		if($sw != $varFormaPago){
			if($varFormaPago == 1){
				$existePagoEnEfectivoOcheque = 1;
				$registro = "EF";
			}else{
			if($varFormaPago == 2){
				$existePagoEnEfectivoOcheque = 1;
				$registro = "CH";
			}else{
				if($varFormaPago == 3){
				$registro = "DP";
			}else{
				if($varFormaPago == 4){
				$registro = "TB";
			}else{
				if($varFormaPago == 5){
				$registro = "TC";
			}else{
				if($varFormaPago == 6){
				$registro = "TD";
			}else{
				if($varFormaPago == 11){
				$registro = "CB";
										}
										}
										}
										}
										}
										}
										}
			
			$fechaApertura = "(SELECT
				ape.fechaAperturaCaja
			FROM
				caja ca
				INNER JOIN sa_iv_apertura ape ON (ca.idCaja = ape.idCaja)
			WHERE
				ape.statusAperturaCaja IN (1,2)
				AND ape.idCaja = 2
				".$andEmpresa1.")";
				
			 $sqlMostrarPorFormaPago = "SELECT
				pa.idDetalleAnticipo AS id,
				pa.montoDetalleAnticipo AS montoPagado,
				(CASE pa.tipoPagoDetalleAnticipo
					WHEN 'EF' THEN '1'
					WHEN 'CH' THEN '2'
					WHEN 'DP' THEN '3'
					WHEN 'TB' THEN '4'
					WHEN 'TC' THEN '5'
					WHEN 'TD' THEN '6'
					WHEN 'CB' THEN '11'
				end) AS formaPago,
				an.estatus AS estatus
			FROM
				cj_cc_detalleanticipo pa,
				cj_cc_anticipo an
			WHERE
				pa.fechaPagoAnticipo = $fechaApertura
				AND pa.tomadoEnCierre IN (0,2)
				AND an.idAnticipo = pa.idAnticipo
				AND an.idDepartamento IN (0,1,3)
				".$andEmpresaSql1."
				AND pa.tipoPagoDetalleAnticipo = '".$registro."'
				AND an.estatus = 1
			
			UNION
			
			SELECT
				pa.idPago AS id,
				pa.montoPagado,
				pa.formaPago,
				'' AS estatus
			FROM
				sa_iv_pagos pa,
				cj_cc_encabezadofactura fv
			WHERE
				pa.fechaPago = $fechaApertura
				AND fv.idDepartamentoOrigenFactura IN (0,1,3)
				AND pa.tomadoEnCierre = 0
				AND fv.idFactura = pa.id_factura
				AND pa.tomadoEnComprobante = 1
				".$andEmpresaSql2."
				AND pa.formaPago = ".$varFormaPago."
				AND pa.estatus = 1
				AND fv.condicionDePago = 1
			
			UNION
			
			SELECT
				pa.id_det_nota_cargo as id,
				pa.monto_pago AS montoPagado,
				pa.idFormaPago AS formaPago,
				'' AS estatus
			FROM
				cj_det_nota_cargo pa,
				cj_cc_notadecargo fv
			WHERE
				pa.fechaPago = $fechaApertura
				AND pa.tomadoEnCierre = 0
				AND fv.idNotaCargo = pa.idNotaCargo
				AND pa.tomadoEnComprobante = 1
				AND fv.idDepartamentoOrigenNotaCargo IN (0,1,3)
				".$andEmpresaSql2."
				AND fv.fechaRegistroNotaCargo = $fechaApertura
				AND pa.idFormaPago = ".$varFormaPago."";
			$consultaMostrarPorFormaPago = mysql_query($sqlMostrarPorFormaPago);
			$numReg = mysql_num_rows($consultaMostrarPorFormaPago);			
			if($numReg > 0){
				$montoTotal = 0;
				while($lafila = mysql_fetch_array($consultaMostrarPorFormaPago)){
					$montoTotal = $montoTotal + $lafila["montoPagado"];
					$idFormaPago = $lafila["formaPago"];
				}
					$montoTotalFormateado = $montoTotal;
					switch($idFormaPago){
						case 1:
							$varCierreTotalEfectivo = $montoTotalFormateado;
							break;
						case 2:
							$varCierreTotalCheques = $montoTotalFormateado;
							break;
						case 3:
							$varCierreTotalDepositos = $montoTotalFormateado;
							break;
						case 4:
							$varCierreTotalTransferenciaBancaria = $montoTotalFormateado;
							break;
						case 5:
							$varCierreTotalTarjetaDeCredito = $montoTotalFormateado;
							break;
						case 6:
							$varCierreTotalEnTarjetaDeDebito = $montoTotalFormateado;
							break;
						case 7:
							$varCierreTotalEnAnticipos = $montoTotalFormateado;
							break;
						case 8:
							$varCierreTotalEnNotaCredito = $montoTotalFormateado;
							break;
						case 9:
							$varCierreTotalSaldoRetencion = $montoTotalFormateado;
							break;
						case 10:
							$varCierreTotalSaldoRetencionISRL = $montoTotalFormateado;
							break;
						case 11:
							$varTotalCashBack = $montoTotalFormateado;
							break;
					}
					$montoTotalCierre = $montoTotalCierre + $montoTotal;
			}
	}
		$sw = $varFormaPago;
		}
		$montoTotalMasApertura = $montoApertura + $montoTotalCierre;
	 	
		//min(CAST(numerofactura AS UNSIGNED) = convierte string en entero
		/*$queryNumeros = "SELECT
			min(numerocontrol)as primernrocontrol,
			min(CAST(numerofactura AS UNSIGNED)) as primernrofactura,
			max(numerocontrol)as ultimonrocontrol,
			max(numerofactura)as ultimonrofactura
		FROM
			cj_cc_encabezadofactura
		WHERE
			cj_cc_encabezadofactura.idDepartamentoOrigenFactura IN (0,1,3)
			AND cj_cc_encabezadofactura.fechaRegistroFactura = (SELECT
																	ape.fechaAperturaCaja
																FROM
																	caja ca
																	INNER JOIN sa_iv_apertura ape ON (ca.idCaja = ape.idCaja)
																WHERE
																	ape.statusAperturaCaja IN (1,2)
																	AND ape.idCaja = 2 
																	AND ape.id_empresa = '".$idEmpresa."');";
		$rsNumeros = mysql_query($queryNumeros)or die(mysql_error().$queryNumeros);
		$rowNumero = mysql_fetch_assoc($rsNumeros);
		
		$queryNumerosNotaCredito = "SELECT
			min(numeroControl)as primernrocontrolnotacredito,
			min(numeracion_nota_credito)as primernronotacredito,
			max(numeroControl)as ultimonrocontrolnotacredito,
			max(numeracion_nota_credito)as ultimonronotacredito
		FROM
			cj_cc_notacredito
		WHERE
			cj_cc_notacredito.idDepartamentoNotaCredito IN (0,1,3)
			AND cj_cc_notacredito.fechaNotaCredito = (SELECT
															ape.fechaAperturaCaja
														FROM
															caja ca
															INNER JOIN sa_iv_apertura ape ON (ca.idCaja = ape.idCaja)
														WHERE
															ape.statusAperturaCaja IN (1,2)
															AND ape.idCaja = 2 
															AND ape.id_empresa = '".$idEmpresa."');";
		$rsNumerosNotaCredito = mysql_query($queryNumerosNotaCredito)or die(mysql_error().$queryNumerosNotaCredito);
		$rowNumeroNotaCredito = mysql_fetch_assoc($rsNumerosNotaCredito);
		
		$varPrimerNroFactura = $rowNumero['primernrofactura'];
		$varUltimoNroFactura = $rowNumero['ultimonrofactura'];
		
		if ($rowNumero['ultimonrocontrol'] != '' && $rowNumeroNotaCredito['ultimonrocontrolnotacredito'] != '')
			$varUltimoNroControl = (str_replace('-','',$rowNumero['ultimonrocontrol']) > str_replace('-','',$rowNumeroNotaCredito['ultimonrocontrolnotacredito'])) ? $rowNumero['ultimonrocontrol'] : $rowNumeroNotaCredito['ultimonrocontrolnotacredito'];
		else if ($rowNumero['ultimonrocontrol'] == '')
			$varUltimoNroControl = $rowNumeroNotaCredito['ultimonrocontrolnotacredito'];
		else if ($rowNumeroNotaCredito['ultimonrocontrolnotacredito'] == '')
			$varUltimoNroControl = $rowNumero['ultimonrocontrol'];
			
		if ($rowNumero['primernrocontrol'] != '' && $rowNumeroNotaCredito['primernrocontrolnotacredito'] != '')
			$varPrimerNroControl = (str_replace('-','',$rowNumero['primernrocontrol']) < str_replace('-','',$rowNumeroNotaCredito['primernrocontrolnotacredito'])) ? $rowNumero['primernrocontrol'] : $rowNumeroNotaCredito['primernrocontrolnotacredito'];
		else if ($rowNumero['primernrocontrol'] == '')
			$varPrimerNroControl = $rowNumeroNotaCredito['primernrocontrolnotacredito'];
		else if ($rowNumeroNotaCredito['primernrocontrolnotacredito'] == '')
			$varPrimerNroControl = $rowNumero['primernrocontrol'];
		
		$varPrimerNroNotaCredito = $rowNumeroNotaCredito['primernronotacredito'];
		$varUltimoNroNotaCredito = $rowNumeroNotaCredito['ultimonronotacredito'];*/
		
		$fechaApertura = "(SELECT
			ape.fechaAperturaCaja
		FROM
			caja ca
			INNER JOIN sa_iv_apertura ape ON (ca.idCaja = ape.idCaja)
		WHERE
			ape.statusAperturaCaja IN (1,2)
			AND ape.idCaja = 2
			".$andEmpresa1.")";
		
					
		//COBRANZA: PAGOS RECIBIDOS POR FRACTURAS A CREDITO
		//COBRANZA DE REPUESTOS ES AQUELLA EN LA QUE SE RECIBIO EL PAGO DE LAS FACTURAS A CREDITO			
		$queryCobranzaRep = "SELECT
			SUM(cxc_pago.montopagado) AS total
		FROM cj_cc_encabezadofactura fv
			INNER JOIN sa_iv_pagos cxc_pago ON (fv.idFactura = cxc_pago.id_factura)
		WHERE fv.idDepartamentoOrigenFactura IN (0)
			AND cxc_pago.fechapago != fv.fechaRegistroFactura
			AND cxc_pago.formaPago NOT IN (8)
			AND cxc_pago.fechapago = ".$fechaApertura." ".$andEmpresaSql2.";";
		$rsCobranzaRep = mysql_query($queryCobranzaRep)or die(mysql_error().$queryCobranzaRep);
		$rowCobranzaRep = mysql_fetch_assoc($rsCobranzaRep);
			
		$varCobranzaRep = $rowCobranzaRep['total'];
		
		//COBRANZA DE SERVICIOS ES AQUELLA EN LA QUE SE RECIBIO EL PAGO DE LAS FACTURAS A CREDITO
		$queryCobranzaServ = "SELECT
			SUM(cxc_pago.montopagado) AS total
		FROM cj_cc_encabezadofactura fv
			INNER JOIN sa_iv_pagos cxc_pago ON (fv.idFactura = cxc_pago.id_factura)
		WHERE fv.idDepartamentoOrigenFactura IN (1)
			AND cxc_pago.fechapago != fv.fechaRegistroFactura
			AND cxc_pago.formaPago NOT IN (8)
			AND cxc_pago.fechapago = ".$fechaApertura." ".$andEmpresaSql2.";";
		$rsCobranzaServ = mysql_query($queryCobranzaServ)or die(mysql_error().$queryCobranzaServ);
		$rowCobranzaServ = mysql_fetch_assoc($rsCobranzaServ);
		
		$varCobranzaServ = $rowCobranzaServ['total'];
		
		// COBRANZA DE ADMINISTRACIÓN
		$queryCobranzaAdmon = "SELECT
			SUM(cxc_pago.montopagado) AS total
		FROM cj_cc_encabezadofactura fv
			INNER JOIN sa_iv_pagos cxc_pago ON (fv.idFactura = cxc_pago.id_factura)
		WHERE fv.idDepartamentoOrigenFactura IN (3)
			AND cxc_pago.fechapago != fv.fechaRegistroFactura
			AND cxc_pago.formaPago NOT IN (8)
			AND cxc_pago.fechapago = ".$fechaApertura." ".$andEmpresaSql2.";";
		$rsCobranzaAdmon = mysql_query($queryCobranzaAdmon)or die(mysql_error().$queryCobranzaAdmon);
		$rowCobranzaAdmon = mysql_fetch_assoc($rsCobranzaAdmon);
		
		$txtCobranzasAdmon = $rowCobranzaAdmon['total'];
		
		$varTotalCobranza = $varCobranzaRep + $varCobranzaServ + $txtCobranzasAdmon;
	}
?>