<?php
require_once("../connections/conex.php");
require_once('clases/fpdf/fpdf.php');
require_once('clases/fpdf/fpdf_print.inc.php');
require_once('clases/barcode128.inc.php');

$ruta = "clases/temp_codigo/img_codigo.png";

$valCadBusq = explode("|",$_GET['valBusq']);

$idDocumento = $valCadBusq[0];
$aux = getBarcode($idDocumento,'clases/temp_codigo/img_codigo');
if ($valCadBusq[1] == 1){
	$queryPrincipal = sprintf("SELECT tipo_presupuesto FROM sa_presupuesto WHERE id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
	$rsPrincipal = mysql_query($queryPrincipal) or die(mysql_error());
	$rowPrincipal = mysql_fetch_array($rsPrincipal);
	
	if ($rowPrincipal['tipo_presupuesto'] == 1){
		$queryCliente = sprintf("SELECT 
		  sa_presupuesto.id_presupuesto,
		  sa_presupuesto.fecha_presupuesto,
		  sa_presupuesto.fecha_vencimiento,
		  cj_cc_cliente.nombre AS nombre_cliente,
		  cj_cc_cliente.apellido AS apellido_cliente,
		  CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
		  cj_cc_cliente.direccion AS direccion_cliente,
		  cj_cc_cliente.telf AS telf_cliente,
		  vw_sa_vales_recepcion.nom_uni_bas,
		  vw_sa_vales_recepcion.des_uni_bas,
		  vw_sa_vales_recepcion.nom_marca,
		  vw_sa_vales_recepcion.color,
		  vw_sa_vales_recepcion.placa,
		  vw_sa_vales_recepcion.chasis,
		  vw_sa_vales_recepcion.des_modelo,
		  pg_empleado.nombre_empleado,
		  pg_empleado.apellido AS apellido_empleado,
		  sa_presupuesto.subtotal,
		  sa_presupuesto.porcentaje_descuento,
		  sa_presupuesto.subtotal_descuento,
		  sa_presupuesto.id_empresa,
		  sa_presupuesto.tipo_presupuesto,
		  sa_presupuesto.id_orden,
		  sa_presupuesto.iva,
		  an_ano.nom_ano,
		  an_transmision.nom_transmision
		FROM
		  sa_presupuesto
		  INNER JOIN cj_cc_cliente ON (sa_presupuesto.id_cliente = cj_cc_cliente.id)
		  INNER JOIN sa_orden ON (sa_presupuesto.id_orden = sa_orden.id_orden)
		  INNER JOIN vw_sa_vales_recepcion ON (sa_orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
		  INNER JOIN pg_empleado ON (sa_presupuesto.id_empleado = pg_empleado.id_empleado)
		  INNER JOIN an_ano ON (vw_sa_vales_recepcion.ano_uni_bas = an_ano.id_ano)
		  INNER JOIN an_uni_bas ON (vw_sa_vales_recepcion.id_uni_bas = an_uni_bas.id_uni_bas)
		  INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
		WHERE
		  sa_presupuesto.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
				
		$texto_presupuesto_cotizacion = "PRESUPUESTO";
		}
		else{
			$queryCliente = sprintf("SELECT 
			  sa_presupuesto.id_presupuesto,
			  sa_presupuesto.fecha_presupuesto,
		  	  sa_presupuesto.fecha_vencimiento,
			  cj_cc_cliente.nombre AS nombre_cliente,
			  cj_cc_cliente.apellido AS apellido_cliente,
			  CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			  cj_cc_cliente.direccion AS direccion_cliente,
			  cj_cc_cliente.telf AS telf_cliente,
			  pg_empleado.nombre_empleado,
			  pg_empleado.apellido AS apellido_empleado,
			  sa_presupuesto.subtotal,
			  sa_presupuesto.porcentaje_descuento,
			  sa_presupuesto.subtotal_descuento,
			  sa_presupuesto.id_empresa,
			  sa_presupuesto.tipo_presupuesto,
			  sa_presupuesto.id_orden,
			  sa_presupuesto.iva,
			  an_ano.nom_ano,
			  an_marca.nom_marca,
			  an_modelo.des_modelo,
			  an_transmision.nom_transmision
			FROM
			  sa_presupuesto
			  INNER JOIN cj_cc_cliente ON (sa_presupuesto.id_cliente = cj_cc_cliente.id)
			  INNER JOIN pg_empleado ON (sa_presupuesto.id_empleado = pg_empleado.id_empleado)
			  INNER JOIN an_uni_bas ON (sa_presupuesto.id_unidad_basica = an_uni_bas.id_uni_bas)
			  INNER JOIN an_ano ON (an_uni_bas.ano_uni_bas = an_ano.id_ano)
			  INNER JOIN an_marca ON (an_uni_bas.mar_uni_bas = an_marca.id_marca)
			  INNER JOIN an_modelo ON (an_uni_bas.mod_uni_bas = an_modelo.id_modelo)
			  INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
			WHERE
			  sa_presupuesto.id_presupuesto =  %s",
			valTpDato($idDocumento,"int"));
		$texto_presupuesto_cotizacion = "COTIZACION";
		}
	}
	else{
	$queryCliente = sprintf("SELECT 
			  cj_cc_cliente.nombre AS nombre_cliente,
			  cj_cc_cliente.apellido AS apellido_cliente,
			  CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			  cj_cc_cliente.direccion AS direccion_cliente,
			  cj_cc_cliente.telf AS telf_cliente,
			  vw_sa_vales_recepcion.nom_uni_bas,
			  vw_sa_vales_recepcion.des_uni_bas,
			  vw_sa_vales_recepcion.nom_marca,
			  vw_sa_vales_recepcion.color,
			  vw_sa_vales_recepcion.placa,
			  vw_sa_vales_recepcion.chasis,
			  vw_sa_vales_recepcion.des_modelo,
			  pg_empleado.nombre_empleado,
			  pg_empleado.apellido AS apellido_empleado,
			  sa_orden.id_empresa,
			  sa_orden.id_orden,
			  sa_orden.subtotal,
			  sa_orden.porcentaje_descuento,
			  sa_orden.subtotal_descuento,
			  sa_orden.iva,
			  an_ano.nom_ano,
			  an_transmision.nom_transmision,
			  sa_orden.tiempo_orden as fecha_presupuesto
			FROM
			  sa_orden
			  LEFT JOIN vw_sa_vales_recepcion ON (sa_orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
			  LEFT JOIN pg_empleado ON (sa_orden.id_empleado = pg_empleado.id_empleado)
			  LEFT JOIN an_uni_bas ON (vw_sa_vales_recepcion.id_uni_bas = an_uni_bas.id_uni_bas)
			  LEFT JOIN an_ano ON (vw_sa_vales_recepcion.ano_uni_bas = an_ano.id_ano)
			  LEFT JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
			INNER JOIN cj_cc_cliente ON (IFNULL(vw_sa_vales_recepcion.id_cliente_pago,vw_sa_vales_recepcion.id) = cj_cc_cliente.id)WHERE
			  sa_orden.id_orden = %s",
			valTpDato($idDocumento,"int"));
		
		if($valCadBusq[2] == 3)
			$texto_presupuesto_cotizacion = "VALE DE SALIDA";
		else
			$texto_presupuesto_cotizacion = "ORDEN DE SERVICIO";
			
}
$rsCliente = mysql_query($queryCliente);
$rowCliente = mysql_fetch_assoc($rsCliente);

$queryEmpresa = sprintf("SELECT logo_familia, nombre_empresa, rif, direccion, telefono1, fax FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
						$rowCliente['id_empresa']);

$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error()."\n\nSQL: ".$queryEmpresa."\n\n".$texto_presupuesto_cotizacion);
$rowEmpresa = mysql_fetch_array($rsEmpresa);

$ruta_logo = "../".$rowEmpresa['logo_familia'];

$tamañoPaginaPixel[0] = 816.37;
$tamañoPaginaPixel[1] = 1092.28;

$pdf = new PDF_AutoPrint('P','pt',$tamañoPaginaPixel);

$pdf->AddPage('P',$tamañoPaginaPixel);

/*LOGO EMPRESA*/
$pdf->Image($ruta_logo, '10', '10', '', '40', '','');

/*NOMBRE EMPRESA*/
$pdf->SetFont('Arial','B',12);
$pdf->SetXY(150,12);
$pdf->Cell(100,12,$rowEmpresa['nombre_empresa'],0,0,'L');

/*RIF EMPRESA*/
$pdf->SetFont('Arial','B',12);
$pdf->SetXY(150,32);
$pdf->Cell(100,12,$spanRIF.": ".$rowEmpresa['rif'],0,1,'L');

/*TEXTO "PRESUPUESTOS o COTIZACION o ORDEN DE SERVICIO"*/
$pdf->SetFont('Arial','B',16);
$pdf->SetXY(320,15);
$pdf->Cell(200,30,$texto_presupuesto_cotizacion,0,0,'L');

/*CODIGO DE BARRA*/
$pdf->Image($ruta, 690, 12, '', '', '','');

/*TEXTO "DATOS DEL CLIENTE"*/
$pdf->SetFont('Arial','',10);
$pdf->SetFillColor(234,244,255);
$pdf->SetXY(10,55);
$pdf->Cell(0,12,"DATOS DEL CLIENTE",1,1,'C',"TRUE");

/*TEXTO "NOMBRE"*/
$pdf->SetFont('Arial','',10);
$pdf->SetX(10);
$pdf->Cell(100,12,"NOMBRE",1,0,'C',"TRUE");
/*NOMBRE*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,12,$rowCliente['nombre_cliente']." ".$rowCliente['apellido_cliente'],1,1,'L');

/*TEXTO "DIRECCION"*/
$pdf->SetFont('Arial','',10);
$pdf->SetX(10);
$pdf->Cell(100,12,"DIRECCION",1,0,'C',"TRUE");
/*DIRECCION*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,12,$rowCliente['direccion_cliente'],1,1,'L');

/*TEXTO "TLFS"*/
$pdf->SetFont('Arial','',10);
$pdf->SetX(10);
$pdf->Cell(100,12,"TLFS",1,0,'C',"TRUE");
/*TLFS*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,$rowCliente['telf_cliente'],1,0,'L');
/*TEXTO "RIF/CIV"*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,"RIF/CIV",1,0,'C',"TRUE");
/*RIF/CEDULA*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,$rowCliente['cedula_cliente'],1,0,'L');
/*TEXTO "O.R."*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(80,12,"O.R.",1,0,'C',"TRUE");
/*ORDEN*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,$rowCliente['id_orden'],1,0,'L');  /* <----- Numero de Orden*/
/*TEXTO "FECHA"*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,"FECHA",1,0,'C',"TRUE");
/*FECHA*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,12,date("d-m-Y",strtotime($rowCliente['fecha_presupuesto'])),1,1,'L');

/*TEXTO "ASESOR"*/
$pdf->SetFont('Arial','',10);
$pdf->SetX(10);
$pdf->Cell(100,12,"ASESOR",1,0,'C',"TRUE");
/*ASESOR*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,12,$rowCliente['nombre_empleado']." ".$rowCliente['apellido_empleado'],1,1,'L');

/*TEXTO "DATOS DEL VEHICULO"*/
$pdf->SetFont('Arial','',10);
$pdf->SetFillColor(234,244,255);
$pdf->SetX(10);
$pdf->Cell(0,12,"DATOS DEL VEHICULO",1,1,'C',"TRUE");

/*TEXTO "MARCA"*/
$pdf->SetFont('Arial','',10);
$pdf->SetX(10);
$pdf->Cell(100,12,"MARCA",1,0,'C',"TRUE");
/*MARCA*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(150,12,$rowCliente['nom_marca'],1,0,'L');
/*TEXTO "AÑO"*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,"A".utf8_decode("Ñ")."O",1,0,'C',"TRUE");
/*AÑO*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(150,12,$rowCliente['nom_ano'],1,0,'L');
/*TEXTO "MOTOR"*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,"MOTOR",1,0,'C',"TRUE");
/*MOTOR*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,12,"--",1,1,'L');

/*TEXTO "MODELO"*/
$pdf->SetFont('Arial','',10);
$pdf->SetX(10);
$pdf->Cell(100,12,"MODELO",1,0,'C',"TRUE");
/*MARCA*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(150,12,$rowCliente['des_modelo'],1,0,'L');
/*TEXTO "PLACAS"*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,"PLACAS",1,0,'C',"TRUE");
/*AÑO*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(150,12,$rowCliente['placa'],1,0,'L');
/*TEXTO "FECHA DE VENTA"*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,"FECHA DE VENTA",1,0,'C',"TRUE");
/*FECHA DE VENTA*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,12,"--",1,1,'L');

/*TEXTO "COLOR"*/
$pdf->SetFont('Arial','',10);
$pdf->SetX(10);
$pdf->Cell(100,12,"COLOR",1,0,'C',"TRUE");
/*COLOR*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(150,12,$rowCliente['color'],1,0,'L');
/*TEXTO "CHASIS"*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,"CHASIS",1,0,'C',"TRUE");
/*CHASIS*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(150,12,$rowCliente['chasis'],1,0,'L');
/*TEXTO "TRANSMISION"*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,12,"TRANSMISION",1,0,'C',true);
/*TRANSMISION*/
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,12,$rowCliente['nom_transmision'],1,1,'L');

//$arrayTamCol = array("15","150","258","155","100","100"); LISTADO CON NUMERACION
$arrayTamCol = array("150","258","170","100","100");// LISTADO SIN NUMERACION

$pdf->SetFont('Arial','B',8);
$arrayCol = array("CODIGO",
				  "DESCRIPCI".utf8_decode("Ó")."N \n",
				  "CANTIDAD / MECANICO",
				  "PRECIO UNITARIO",
				  "PRECIO TOTAL"); //LISTADO CON NUMERACION HAY QUE PONER \N DE PRIMERO
$arrayAlineacion = array("L",
						 "L",
						 "R",
						 "R",
						 "R");
$posX = 10;
$posY = $pdf->GetY();
foreach ($arrayCol as $indice => $valor) {
	$pdf->SetXY($posX,$posY);
	$pdf->MultiCell($arrayTamCol[$indice],20,$valor,1,$arrayAlineacion[$indice],true);
	$posX += $arrayTamCol[$indice];
}
$i = 1;
/* DETALLES DE LOS REPUESTOS */
if ($valCadBusq[1] == 1){
	$queryRepuestosGenerales = sprintf("SELECT 
	  sa_det_presup_articulo.cantidad,
	  sa_det_presup_articulo.precio_unitario,
	  vw_iv_articulos.codigo_articulo,
	  vw_iv_articulos.descripcion,
	  sa_det_presup_articulo.iva
	FROM
	  sa_presupuesto
	  INNER JOIN sa_det_presup_articulo ON (sa_presupuesto.id_presupuesto = sa_det_presup_articulo.id_presupuesto)
	  INNER JOIN vw_iv_articulos ON (sa_det_presup_articulo.id_articulo = vw_iv_articulos.id_articulo)
	WHERE
	  sa_presupuesto.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
}
else{
	$queryRepuestosGenerales = sprintf("SELECT 
	  sa_det_orden_articulo.cantidad,
	  sa_det_orden_articulo.precio_unitario,
	  vw_iv_articulos.codigo_articulo,
	  vw_iv_articulos.descripcion,
	  sa_det_orden_articulo.iva
	FROM
	  vw_iv_articulos
	  INNER JOIN sa_det_orden_articulo ON (vw_iv_articulos.id_articulo = sa_det_orden_articulo.id_articulo)
	WHERE
	  sa_det_orden_articulo.id_orden = %s",
		valTpDato($idDocumento,"int"));
}
$rsOrdenDetRep = mysql_query($queryRepuestosGenerales, $conex) or die(mysql_error());
$posY = 140;
if (mysql_num_rows($rsOrdenDetRep) > 0){
	$pdf->Cell(350,16,"",'0',0,'L',false);
	$pdf->Cell(0,16,"REPUESTOS",'0',1,'L',false);
	while ($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)) {
		if ($rowOrdenDetRep['iva'] == 'NULL'){
			$montoExento += $rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario'];
		}
		$pdf->SetX(10);
		$anex = (strlen($rowOrdenDetRep['descripcion_articulo']) > 55) ? "..." : "";
		
		//$pdf->Cell($arrayTamCol[0],16,$i,'1',0,'L',false);
		if ($valCadBusq[1] == 1)
			$codigoRepuesto = "";
		else
			$codigoRepuesto = elimCaracter($rowOrdenDetRep['codigo_articulo'],"-");
			
		$pdf->Cell($arrayTamCol[0],16,$codigoRepuesto,'0',0,'L',false);
		$pdf->Cell($arrayTamCol[1],16,strtoupper(substr($rowOrdenDetRep['descripcion'],0,55).$anex),'0',0,'L',false);
		$pdf->Cell($arrayTamCol[2],16,$rowOrdenDetRep['cantidad'],'0',0,'R',false);
		$pdf->Cell($arrayTamCol[3],16,number_format($rowOrdenDetRep['precio_unitario'],2,".",","),'0',0,'R',false);
		$pdf->Cell($arrayTamCol[4],16,number_format(($rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario']),2,".",","),'0',1,'R',false);
		$totalRepuestos += $rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario'];
		$i++;
	}
	$pdf->SetX(10);
	$pdf->Cell(678,16,"SUB-TOTAL REPUESTOS",'0',0,'R',false);
	$pdf->Cell($arrayTamCol[4],16,number_format($totalRepuestos,2,".",","),'0',1,'R',false);
}

/* DETALLES DE LOS TEMPARIOS */
if ($valCadBusq[1] == 1){
	$queryFactDetTemp = sprintf("SELECT 
	  sa_modo.descripcion_modo,
	  sa_tempario.codigo_tempario,
	  sa_tempario.descripcion_tempario,
	  sa_det_presup_tempario.operador,
	  sa_det_presup_tempario.id_tempario,
	  sa_det_presup_tempario.precio,
	  sa_det_presup_tempario.base_ut_precio,
	  sa_det_presup_tempario.id_modo,
	  (case sa_det_presup_tempario.id_modo when '1' then sa_det_presup_tempario.ut * sa_det_presup_tempario.precio_tempario_tipo_orden / sa_det_presup_tempario.base_ut_precio when '2' then sa_det_presup_tempario.precio when '3' then sa_det_presup_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
	  (case sa_det_presup_tempario.id_modo when '1' then sa_det_presup_tempario.ut when '2' then sa_det_presup_tempario.precio when '3' then sa_det_presup_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
	  sa_det_presup_tempario.id_det_presup_tempario,
	  sa_det_presup_tempario.aprobado,
	  sa_det_presup_tempario.origen_tempario,
	  sa_det_presup_tempario.origen_tempario + 0 AS idOrigen,
	  sa_paquetes.codigo_paquete,
	  sa_paquetes.id_paquete,
	  sa_det_presup_tempario.precio_tempario_tipo_orden
	FROM
	  sa_det_presup_tempario
	  INNER JOIN sa_tempario ON (sa_det_presup_tempario.id_tempario = sa_tempario.id_tempario)
	  INNER JOIN sa_modo ON (sa_det_presup_tempario.id_modo = sa_modo.id_modo)
	  LEFT OUTER JOIN sa_paquetes ON (sa_det_presup_tempario.id_paquete = sa_paquetes.id_paquete)
	WHERE
	  sa_det_presup_tempario.id_presupuesto = %s
	ORDER BY
	  sa_det_presup_tempario.id_paquete",
		valTpDato($idDocumento,"int"));
}
else{
	$queryFactDetTemp = sprintf("SELECT 
	  sa_modo.descripcion_modo,
	  sa_tempario.codigo_tempario,
	  sa_tempario.descripcion_tempario,
	  sa_det_orden_tempario.operador,
	  sa_det_orden_tempario.id_tempario,
	  sa_det_orden_tempario.precio,
	  sa_det_orden_tempario.base_ut_precio,
	  sa_det_orden_tempario.id_modo,
	  (case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut * sa_det_orden_tempario.precio_tempario_tipo_orden / sa_det_orden_tempario.base_ut_precio when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
	  (case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
	  sa_det_orden_tempario.id_det_orden_tempario,
	  sa_det_orden_tempario.aprobado,
	  sa_det_orden_tempario.origen_tempario,
	  sa_det_orden_tempario.origen_tempario + 0 AS idOrigen,
	  sa_paquetes.codigo_paquete,
	  sa_paquetes.id_paquete,
	  sa_det_orden_tempario.precio_tempario_tipo_orden
	FROM
	  sa_det_orden_tempario
	  INNER JOIN sa_tempario ON (sa_det_orden_tempario.id_tempario = sa_tempario.id_tempario)
	  INNER JOIN sa_modo ON (sa_det_orden_tempario.id_modo = sa_modo.id_modo)
	  LEFT OUTER JOIN sa_paquetes ON (sa_det_orden_tempario.id_paquete = sa_paquetes.id_paquete)
	WHERE
	  sa_det_orden_tempario.id_orden = %s
	ORDER BY
	  sa_det_orden_tempario.id_paquete",
		valTpDato($idDocumento,"int"));
}
$rsFactDetTemp = mysql_query($queryFactDetTemp, $conex) or die(mysql_error());
if (mysql_num_rows($rsFactDetTemp) > 0){
	$pdf->Cell(350,16,"",'0',0,'L',false);
	$pdf->Cell(0,16,"MANOS DE OBRA",'0',1,'L',false);
	while ($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)) {
		$pdf->SetX(10);
		$anex = (strlen($rowFactDetTemp['descripcion_tempario']) > 55) ? "..." : "";
		
		$caractCantTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_por_tipo_orden']/100,2,".",",") : number_format(1,2,".",",");//Es entre 100 o la base ut? : $rowFactDetTemp['precio_por_tipo_orden']/100
			
		$caracterPrecioTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_tempario_tipo_orden'],2,".",",") : $rowFactDetTemp['precio_por_tipo_orden'];
		
		$cantidad = $caractCantTempario."/MEC:".sprintf("%04s",$rowFactDetTemp['id_mecanico']);
		$precioUnit = number_format($caracterPrecioTempario,2,".",",");
		$total = number_format($rowFactDetTemp['total_por_tipo_orden'],2,".",",");
		
		//$pdf->Cell($arrayTamCol[0],16,$i,'1',0,'L',false);
		$pdf->Cell($arrayTamCol[0],16,$rowFactDetTemp['codigo_tempario'],'0',0,'L',false);
		$pdf->Cell($arrayTamCol[1],16,strtoupper(substr($rowFactDetTemp['descripcion_tempario'],0,55).$anex),'0',0,'L',false);
		$pdf->Cell($arrayTamCol[2],16,$cantidad,'0',0,'R',false);
		$pdf->Cell($arrayTamCol[3],16,$precioUnit,'0',0,'R',false);
		$pdf->Cell($arrayTamCol[4],16,$total,'0',1,'R',false);
		$totalManoDeObra += $rowFactDetTemp['total_por_tipo_orden'];
		$i++;
	}
	$pdf->SetX(10);
	$pdf->Cell(678,16,"SUB-TOTAL TEMPARIOS",'0',0,'R',false);
	$pdf->Cell($arrayTamCol[4],16,number_format($totalManoDeObra,2,".",","),'0',1,'R',false);
}

/* DETALLE DE LOS TOT */
if ($valCadBusq[1] == 1){
	$queryDetalleTot = sprintf("SELECT 
	  sa_orden_tot.monto_total,
	  sa_det_presup_tot.id_orden_tot,
   	  sa_det_presup_tot.porcentaje_tot
	FROM
	  sa_det_presup_tot
	  INNER JOIN sa_orden_tot ON (sa_det_presup_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
	WHERE
	  sa_det_presup_tot.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
}
else{
	$queryDetalleTot = sprintf("SELECT 
  sa_orden_tot.monto_total,
  sa_det_orden_tot.id_orden_tot,
  sa_det_orden_tot.porcentaje_tot
FROM
  sa_det_orden_tot
  INNER JOIN sa_orden_tot ON (sa_det_orden_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
WHERE
  sa_det_orden_tot.id_orden = %s",
		valTpDato($idDocumento,"int"));
}
$rsDetalleTot = mysql_query($queryDetalleTot) or die(mysql_error());
if (mysql_num_rows($rsDetalleTot) > 0){
	$pdf->Cell(350,16,"",'0',0,'L',false);
	$pdf->Cell(0,16,"TRABAJOS OTROS TALLERES (T.O.T)",'0',1,'L',false);
	$totalTOT = 0;
	while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
		$pdf->SetX(10);
		
		$cantidad = "1";
		$precioUnit = number_format($rowDetalleTot['monto_total'] + ($rowDetalleTot['monto_total'] * $rowDetalleTot['porcentaje_tot'] / 100),2,".",",");
		$total = number_format($rowDetalleTot['monto_total'] + ($rowDetalleTot['monto_total'] * $rowDetalleTot['porcentaje_tot'] / 100),2,".",",");
		
		//$pdf->Cell($arrayTamCol[0],16,$i,'1',0,'L',false);
		$pdf->Cell($arrayTamCol[0],16,$rowDetalleTot['id_orden_tot'],'0',0,'L',false);
		$pdf->Cell($arrayTamCol[1],16,"T.O.T.",'0',0,'L',false);
		$pdf->Cell($arrayTamCol[2],16,$cantidad,'0',0,'R',false);
		$pdf->Cell($arrayTamCol[3],16,$precioUnit,'0',0,'R',false);
		$pdf->Cell($arrayTamCol[4],16,$total,'0',1,'R',false);
		$i++;
		$totalTOT += $rowDetalleTot['monto_total'] + ($rowDetalleTot['monto_total'] * $rowDetalleTot['porcentaje_tot'] / 100);
	}
	$pdf->SetX(10);
	$pdf->Cell(678,16,"SUB-TOTAL TRABAJOS OTROS TALLERES (T.O.T)",'0',0,'R',false);
	$pdf->Cell($arrayTamCol[4],16,number_format($totalTOT,2,".",","),'0',1,'R',false);
}

/* DETALLES DE LAS NOTAS */
if ($valCadBusq[1] == 1){
	$queryDetTipoDocNotas = sprintf("SELECT 
	  sa_det_presup_notas.descripcion_nota,
	  sa_det_presup_notas.precio,
	  sa_det_presup_notas.id_det_presup_nota
	FROM
	  sa_presupuesto
	  INNER JOIN sa_det_presup_notas ON (sa_presupuesto.id_presupuesto = sa_det_presup_notas.id_presupuesto)
	WHERE
	  sa_presupuesto.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
}
else{
	$queryDetTipoDocNotas = sprintf("SELECT 
	  sa_det_orden_notas.descripcion_nota,
	  sa_det_orden_notas.precio,
	  sa_det_orden_notas.id_det_orden_nota
	FROM
	  sa_det_orden_notas
	WHERE
	  sa_det_orden_notas.id_orden = %s",
		valTpDato($idDocumento,"int"));
}
$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas) or die(mysql_error());	
if (mysql_num_rows($rsDetTipoDocNotas) > 0){
	$pdf->Cell(350,16,"",'0',0,'L',false);
	$pdf->Cell(0,16,"NOTAS",'0',1,'L',false);
	while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
		$pdf->SetX(10);
		
		$anex = (strlen($rowDetTipoDocNotas['descripcion_nota']) > 55) ? "..." : "";
		
		$cantidad = "1";
		$precioUnit = number_format($rowDetTipoDocNotas['precio'],2,".",",");
		
		$total = number_format($rowDetTipoDocNotas['precio'],2,".",",");
		
		//$pdf->Cell($arrayTamCol[0],16,$i,'1',0,'L',false);
		$pdf->Cell($arrayTamCol[0],16,"N".$rowDetTipoDocNotas['id_det_presup_nota'],'0',0,'L',false);
		$pdf->Cell($arrayTamCol[1],16,strtoupper(substr($rowDetTipoDocNotas['descripcion_nota'],0,55).$anex),'0',0,'L',false);
		$pdf->Cell($arrayTamCol[2],16,$cantidad,'0',0,'R',false);
		$pdf->Cell($arrayTamCol[3],16,$precioUnit,'0',0,'R',false);
		$pdf->Cell($arrayTamCol[4],16,$total,'0',1,'R',false);
		$i++;
		$totalNotas += $rowDetTipoDocNotas['precio'];
	}
	$pdf->SetX(10);
	$pdf->Cell(678,16,"SUB-TOTAL NOTAS",'0',0,'R',false);
	$pdf->Cell($arrayTamCol[4],16,number_format($totalNotas,2,".",","),'0',1,'R',false);
}

/* DETALLES DE LOS DESCUENTOS */
if ($valCadBusq[1] == 1){
	$queryDetDescuentos = sprintf("SELECT 
	  sa_det_presup_descuento.porcentaje,
	  sa_det_presup_descuento.id_porcentaje_descuento
	FROM
	  sa_det_presup_descuento
	WHERE
	  sa_det_presup_descuento.id_presupuesto = %s",
	valTpDato($idDocumento,"int"));
}
else{
	$queryDetDescuentos = sprintf("SELECT 
	  sa_det_orden_descuento.porcentaje,
	  sa_det_orden_descuento.id_porcentaje_descuento
	FROM
	  sa_det_orden_descuento
	WHERE
	  sa_det_orden_descuento.id_orden = %s",
		valTpDato($idDocumento,"int"));
}	
$rsDetDescuentos = mysql_query($queryDetDescuentos) or die(mysql_error());	
while ($rowDetDescuentos = mysql_fetch_assoc($rsDetDescuentos)) {
	if ($rowDetDescuentos['id_porcentaje_descuento'] == 1)
		$descuentoDetallesManoDeObra += $totalManoDeObra * $rowDetDescuentos['porcentaje'] / 100;
	else
		$descuentoDetallesRepuestos += $totalRepuestos * $rowDetDescuentos['porcentaje'] / 100;
}

/*for (; $i <= 40; $i++){
$pdf->SetX(10);
//$pdf->Cell($arrayTamCol[0],16,$i,'1',0,'L',false);
$pdf->Cell($arrayTamCol[0],16,"-",'1',0,'C',false);
$pdf->Cell($arrayTamCol[1],16,"-",'1',0,'C',false);
$pdf->Cell($arrayTamCol[2],16,"-",'1',0,'C',false);
$pdf->Cell($arrayTamCol[3],16,"-",'1',0,'C',false);
$pdf->Cell($arrayTamCol[4],16,"-",'1',1,'C',false);
}*/

/*TEXTO "SUB-TOTAL"*/
$pdf->SetXY(538,-262);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"SUB-TOTAL:",1,0,'R',"TRUE");
/*SUB-TOTAL*/
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,12,number_format($rowCliente['subtotal'],2,".",","),1,0,'R');

/*TEXTO "DESCUENTO"*/
$pdf->SetXY(538,-250);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"DESCUENTO:",1,0,'R',"TRUE");
/*DESCUENTO*/
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,12,number_format($rowCliente['subtotal_descuento'] + $descuentoDetalles,2,".",","),1,0,'R');

/*TEXTO "DESCUENTO X MANO DE OBRA"*/
$pdf->SetXY(538,-238);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"DESCUENTO X MANO DE OBRA:",1,0,'R',"TRUE");
/*DESCUENTO*/
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,12,number_format($descuentoDetallesManoDeObra,2,".",","),1,0,'R');

/*TEXTO "DESCUENTO X REPUESTOS"*/
$pdf->SetXY(538,-226);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"DESCUENTO X REPUESTOS:",1,0,'R',"TRUE");
/*DESCUENTO*/
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,12,number_format($descuentoDetallesRepuestos,2,".",","),1,0,'R');

/*TEXTO "BASE IMPONIBLE"*/
$pdf->SetXY(538,-214);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"BASE IMPONIBLE:",1,0,'R',"TRUE");
/*BASE IMPONIBLE*/
$baseImponible = $rowCliente['subtotal'] - $montoExento;
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,12,number_format($baseImponible,2,".",","),1,0,'R');

/*TEXTO "MONTO EXENTO"*/
$pdf->SetXY(538,-202);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"MONTO EXENTO:",1,0,'R',"TRUE");
/*MONTO EXENTO*/
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,12,number_format($montoExento,2,".",","),1,0,'R');

/*TEXTO "IVA"*/
$pdf->SetXY(538,-190);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"IVA:",1,0,'R',"TRUE");
/*IVA*/
$iva = $baseImponible * $rowCliente['iva'] / 100;
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,12,number_format($iva,2,".",","),1,0,'R');

/*TEXTO "TOTAL"*/
$pdf->SetXY(538,-178);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"TOTAL:",1,0,'R',"TRUE");
/*TOTAL*/
$totalPresupuesto = $rowCliente['subtotal'] - $rowCliente['subtotal_descuento'] - $descuentoDetalles + $iva;
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,12,number_format($totalPresupuesto,2,".",","),1,0,'R');

/*TEXTO "FECHA APROBACIÓN"*/
$pdf->SetXY(10,-254);
$pdf->SetFont('Arial','',8);
$pdf->Cell(100,20,"FECHA APROBACI".utf8_decode("Ó")."N",1,0,'R',true);
/*SUB-TOTAL*/
$pdf->SetFont('Arial','',8);
$pdf->Cell(90,20,"",1,0,'R');

/*TEXTO "HORA APROBACIÓN"*/
$pdf->SetXY(10,-234);
$pdf->SetFont('Arial','',8);
$pdf->Cell(100,20,"HORA APROBACI".utf8_decode("Ó")."N",1,0,'R',true);
/*DESCUENTO*/
$pdf->SetFont('Arial','',8);
$pdf->Cell(90,20,"",1,0,'R');

/*TEXTO "CLIENTE"*/
$pdf->SetXY(10,-214);
$pdf->SetFont('Arial','',8);
$pdf->Cell(100,20,"CLIENTE",1,0,'R',"TRUE");
/*IVA*/
$pdf->SetFont('Arial','',8);
$pdf->Cell(90,20,"",1,0,'R');

/*TEXTO "FIRMA"*/
$pdf->SetXY(10,-194);
$pdf->SetFont('Arial','',8);
$pdf->Cell(100,20,"FIRMA",1,0,'R',"TRUE");
/*TOTAL*/
$pdf->SetFont('Arial','',8);
$pdf->Cell(90,20,"",1,0,'R');

/*TEXTO "SERVICIO"*/
$pdf->SetXY(130,-160);
$pdf->SetFont('Arial','',8);
$pdf->Cell(100,12,"SERVICIO",0,0,'L');

/*TEXTO "REPUESTO"*/
$pdf->SetXY(600,-160);
$pdf->SetFont('Arial','',8);
$pdf->Cell(100,12,"REPUESTO",0,0,'L');

/*TEXTO "NOMBRE FECHA"*/
$pdf->SetXY(30,-150);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"NOMBRE",0,0,'C');
$pdf->Cell(100,12,"FECHA",0,0,'C');

/*TEXTO "NOMBRE FECHA"*/
$pdf->SetXY(480,-150);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"NOMBRE",0,0,'C');
$pdf->Cell(100,12,"FECHA",0,0,'C');

/*RECTANGULO*/
$pdf->SetXY(30,-140);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,15,"",1,0,'L');
$pdf->Cell(100,15,"",1,0,'L');

/*RECTANGULO*/
$pdf->SetXY(480,-140);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,15,"",1,0,'L');
$pdf->Cell(100,15,"",1,0,'L');

/*TEXTO "HORA"*/
$pdf->SetXY(180,-120);
$pdf->SetFont('Arial','',8);
$pdf->Cell(100,12,"HORA",0,0,'C');

/*TEXTO "HORA"*/
$pdf->SetXY(630,-120);
$pdf->SetFont('Arial','',8);
$pdf->Cell(100,12,"HORA",0,0,'C');

/*RECTANGULO*/
$pdf->SetXY(30,-110);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,15,"","B",0,'L');
$pdf->Cell(100,15,"",1,0,'L');

/*RECTANGULO*/
$pdf->SetXY(480,-110);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,15,"","B",0,'L');
$pdf->Cell(100,15,"",1,0,'L');

/*TEXTO "FIRMA"*/
$pdf->SetXY(30,-95);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"FIRMA",0,0,'C');

/*TEXTO "FIRMA"*/
$pdf->SetXY(480,-95);
$pdf->SetFont('Arial','',8);
$pdf->Cell(150,12,"FIRMA",0,0,'C');

/*NOMBRE EMPRESA DIRECCION EMPRESA TELEFONO FAX*/
$pdf->SetY(-80);
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,12,$rowEmpresa['nombre_empresa']." ".$rowEmpresa['direccion']." ".$rowEmpresa['telefono1']." FAX. ".$rowEmpresa['fax'],0,0,'C',false);

if ($valCadBusq[1] == 1){
/*TEXTO "PRESUPUESTO VÁLIDO POR TANTOS DÍAS"*/
$dias = strtotime($rowCliente['fecha_vencimiento']) - strtotime(date("Y-m-d",strtotime($rowCliente['fecha_presupuesto'])));
$pdf->SetY(-70);
$pdf->SetFont('Arial','',8);
//$pdf->Cell(0,12,round($dias / 86400),0,0,'C',false);
$pdf->Cell(0,12,$texto_presupuesto_cotizacion." V".utf8_decode("Á")."LIDO POR ".($dias / 86400)." D".utf8_decode("Í")."AS",0,0,'C',false);
}

$pdf->SetDisplayMode("fullwidth");
//$pdf->AutoPrint(true);
$pdf->Output();
?>