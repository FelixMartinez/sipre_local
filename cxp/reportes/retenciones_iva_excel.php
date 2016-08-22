<?php
require_once("../../connections/conex.php");

header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=archivo.xls");
header("Pragma: no-cache");
header("Expires: 0");

$queryRetencion = sprintf("SELECT 
	retencion.idRetencionCabezera,
	retencion.numeroComprobante,
	retencion.fechaComprobante,
	retencion.anoPeriodoFiscal,
	retencion.mesPeriodoFiscal,
	prov.id_proveedor,
	CONCAT(prov.lrif, prov.rif) AS rif_proveedor,
	prov.nombre,
	vw_iv_emp_suc.id_empresa_reg,
	vw_iv_emp_suc.nombre_empresa,
	vw_iv_emp_suc.rif,
	retencion_det.idRetencionDetalle,
	retencion_det.idRetencionCabezera,
	retencion_det.fechaFactura,
	cxc_fact.id_factura,
	(CASE
		WHEN retencion_det.id_nota_cargo IS NOT NULL THEN
			cxp_nd.numero_notacargo
		WHEN retencion_det.id_nota_credito IS NOT NULL THEN
			cxp_nc.numero_nota_credito
		ELSE
			cxc_fact.numero_factura_proveedor
	END) AS numero_factura_proveedor,
	(CASE
		WHEN retencion_det.id_nota_cargo IS NOT NULL THEN
			cxp_nd.numero_control_notacargo
		WHEN retencion_det.id_nota_credito IS NOT NULL THEN
			cxp_nc.numero_control_notacredito
		ELSE
			cxc_fact.numero_control_factura
	END) AS numero_control_factura,
	retencion_det.id_nota_cargo,
	cxp_nd.numero_notacargo,
	retencion_det.id_nota_credito,
	cxp_nc.numero_nota_credito,
	retencion_det.tipoDeTransaccion,
	IF (retencion_det.id_nota_cargo IS NULL AND retencion_det.id_nota_credito IS NULL, '0', cxc_fact.numero_factura_proveedor) AS numeroFacturaAfectada,
	retencion_det.totalCompraIncluyendoIva,
	retencion_det.comprasSinIva,
	retencion_det.baseImponible,
	SUM(retencion_det.porcentajeAlicuota) AS porcentajeAlicuota,
	SUM(retencion_det.impuestoIva) AS impuestoIva,
	SUM(retencion_det.IvaRetenido) AS IvaRetenido,
	retencion_det.porcentajeRetencion
FROM cp_proveedor prov
	INNER JOIN cp_retencioncabezera retencion ON (prov.id_proveedor = retencion.idProveedor)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (retencion.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	INNER JOIN cp_retenciondetalle retencion_det ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera)
	INNER JOIN cp_factura cxc_fact ON (retencion_det.idFactura = cxc_fact.id_factura)
	LEFT JOIN cp_notacredito cxp_nc ON (retencion_det.id_nota_credito = cxp_nc.id_notacredito)
	LEFT JOIN cp_notadecargo cxp_nd ON (retencion_det.id_nota_cargo = cxp_nd.id_notacargo)
WHERE retencion.fechaComprobante BETWEEN %s AND %s
GROUP by retencion_det.idRetencionCabezera, cxc_fact.id_factura, retencion_det.id_nota_cargo, retencion_det.id_nota_credito",
	valTpDato(date("Y-m-d",strtotime($_GET['fechaDesde'])), "date"),
	valTpDato(date("Y-m-d",strtotime($_GET['fechaHasta'])), "date"));
$rsRetencion = mysql_query($queryRetencion);
if (!$rsRetencion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

echo "<table border=\"1\">";
echo "<tr align='left'>";
	echo "<td>"."R.I.F."."</td>"; // R.I.F. DE ALTAUTOS, C.A. (formato general) 10 dígitos
	echo "<td>"."Período Impositivo"."</td>"; // PERIODO IMPOSITIVO AÑO Y MES (Formato general) 6 dígitos
	echo "<td>"."Fecha del Documento"."</td>"; // FECHA DEL DOCUMENTO año-mes-día (formato texto) 10 dígitos	
	echo "<td>"."Tipo de Operación"."</td>"; // Tipo de Operación V=ventas C=compras (Formato general) 1 dígito
	echo "<td>"."Tipo de Dcto."."</td>"; // Tipo de Dcto. 01 = factura 02 = n.debito 03 = n.credito 04 = certificación 05 = importación 06 = exportación (Formato texto) 2 dígitos
	echo "<td>"."R.I.F. del Vendedor"."</td>"; // R.I.F. del Vendedor (formato general) 10 dígitos
	echo "<td>"."Nro. de la factura"."</td>"; // Nro. de la factura (formato general) hasta 20 dígitos
	echo "<td>"."Nro. de control"."</td>"; // Nro. de control (formato general) hasta 20 dígitos
	echo "<td>"."Monto total facturad"."</td>"; // Monto total facturado (formato general) hasta 15 dígitos
	echo "<td>"."Base imponible"."</td>"; // Base imponible (formato general) hasta 15 dígitos
	echo "<td>"."Impuesto retenido"."</td>"; // IVA retenido (formato general) hasta 15 dígitos
	echo "<td>"."Nro. Documento afectado"."</td>"; // Nro. Documento afectado (formato general) hasta 20 dígitos
	echo "<td>"."Nro. Del Comprobante"."</td>"; // Nro. Del Comprobante (formato texto)(alfanumérico) 14 dígitos
	echo "<td>"."Monto exento"."</td>"; // Monto exento (formato general) hasta 15 dígitos
	echo "<td>"."Alícuota impositiva"."</td>"; // Alícuota impositiva (formato general) hasta 3 dígitos
	echo "<td>"."Nro. De expediente"."</td>"; // Nro. De expediente (formato general )hasta 15 dígitos
echo "</tr> ";
while ($rowRetencion = mysql_fetch_array($rsRetencion)){
	echo "<tr align='left'> ";
		echo "<td>".str_replace("-","",$rowRetencion['rif'])."</td> ";
		echo "<td align='center'>".$rowRetencion['anoPeriodoFiscal'].str_pad($rowRetencion['mesPeriodoFiscal'], 2, "0", STR_PAD_LEFT)."</td> ";
		echo "<td align='rigth'>".date("Y-m-d",strtotime($rowRetencion['fechaFactura']))."</td> ";
		echo "<td align='center'>C</td> ";
		echo "<td align='center'>".str_pad($rowRetencion['tipoDeTransaccion'], 2, "0", STR_PAD_LEFT)."</td> ";
		echo "<td align='rigth'>".str_replace ("-","",$rowRetencion['rif_proveedor'])."</td> ";
		echo "<td align='rigth'>".$rowRetencion['numero_factura_proveedor']."</td> ";
		echo "<td align='center'>".$rowRetencion['numero_control_factura']."</td> ";
		echo "<td align='rigth'>".number_format($rowRetencion['totalCompraIncluyendoIva'],2,",","")."</td> ";
		echo "<td align='rigth'>".number_format($rowRetencion['baseImponible'],2,",","")."</td> ";
		echo "<td align='rigth'>".number_format($rowRetencion['IvaRetenido'],2,",","")."</td> ";
		echo "<td align='rigth'>".$rowRetencion['numeroFacturaAfectada']."</td> ";
		echo "<td align='center'>".$rowRetencion['numeroComprobante']."</td> ";
		echo "<td align='rigth'>".number_format($rowRetencion['comprasSinIva'],2,",","")."</td> ";
		echo "<td align='rigth'>".number_format($rowRetencion['porcentajeAlicuota'],0,",","")."</td> ";
		echo "<td align='rigth'>0</td> ";
	echo "</tr> ";
}
echo "</tr> ";
echo "</table> ";
?>