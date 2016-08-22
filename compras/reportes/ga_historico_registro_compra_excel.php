<?php 
//El informe de errores 
/*error_reporting (E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);*/
// Include PHPExcel 
include_once("../../connections/conex.php");
include_once("../../inc_sesion.php");

require_once ('../../clases/phpExcel_1.7.8/Classes/PHPExcel.php');

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
// echo "<pre>"; print_r($_GET);Exit;
// Set document properties
$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
							 ->setLastModifiedBy("Maarten Balliauw")
							 ->setTitle("PHPExcel Test Document")
							 ->setSubject("PHPExcel Test Document")
							 ->setDescription("Test document for PHPExcel, generated using PHP classes.")
							 ->setKeywords("office PHPExcel php")
							 ->setCategory("Test result file");
							 
/*****DEFINE LOS ESTILO PARA EL EXCEL*****/
//PARA COLOCAR EL BORDER INTERNO DE LA TABLA
$styleArray = array('borders' => array('inside'=> array( 
					'style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '555657'))));
//PARA COLOCAR EL BORDER EXTERIOR DE LA TABLA
$styleArray2 = array('borders' => array('outline'=> array(
					'style' => PHPExcel_Style_Border::BORDER_THICK,'color' => array('argb' => '2D2E30'))));
//
$styleArrayEmp = array('font' => array('bold' => true),'alignment' => array(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT));
//PARA DARLE ESTILO AL TITULO DE LA HOJA EXCEL
$styleArrayTitulo = array ('font' =>array(
						 				'bold' => true,
										'size' => 12 ,
										'name' => 'Verdana',
										'underline'  =>  PHPExcel_Style_Font :: UNDERLINE_SINGLE),
						 'alignment' =>array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
//PAR EL ESTILO DEL ENCABEZADO DE LA TABLA						 
$styleArrayHead = array('font' =>  array('bold' => true,
										 'color' => array('rgb' => 'FFFFFF')),
						'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER), 
						'fill' =>      array('type' => PHPExcel_Style_Fill::FILL_SOLID,
											 'color' => array('rgb' => '73c025')));
//PARA ALINER EL TEXTO A LA DERECHAR CON LA LETRA EN NEGRITA	
$styleArrayTex = array('font' => array('bold' => true),
					   'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT));
						
//PARA CENTAR EL TEXTO
$styleArrayTex2 = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));

//RELLENA DE COLOR LAS CELDA INTERNA 
$styleArrayRelleno = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
											'rotation' => 90,
											'startcolor' => array('argb' => 'f2fff4'),
											'endcolor' => array('argb' => 'FFFFFFFF')));
//PARA RELLENEAR LA CELDA DEL TOTAL 											
$styleArrayTotales = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
										   'rotation' => 90,
										   'startcolor' => array('argb' => 'ECEDEF'),
											'endcolor' => array('argb' => 'FFFFFFFF')),
							'font' => array('bold' => true,
											'underline'  =>  PHPExcel_Style_Font :: UNDERLINE_SINGLE));
		
	$nombreArchivo = "Facturas_compras.xlsx";
	$titulo = "Informe Historico de Comrpas";
	
	$fecha = date("d/m/Y");
	$hora = date("h:i. A");

	//CONSULTO LOS DATOS DE LA EMPRESA
	$SqlEmp = sprintf("SELECT id_empresa, nombre_empresa,logo_empresa,rif,web  FROM ".DBASE_SIPRE_AUT.".pg_empresa 
						WHERE id_empresa = %s",
					   $_SESSION['idEmpresaUsuarioSysGts']);
					   //die($SqlEmp);
	$queryEmp = mysql_query($SqlEmp);  
	if(!$queryEmp){ die("Error: ".mysql_error().__LINE__); } 
	$rowsEmp = mysql_fetch_array($queryEmp);

		$objDrawing = new PHPExcel_Worksheet_Drawing();
		$objDrawing->setName('Logo');
		$objDrawing->setDescription('Logo');
		$objDrawing->setPath("../../".$rowsEmp['logo_empresa']);
		$objDrawing->setHeight(80);
		$objDrawing->setWidth(100);
		$objDrawing->setCoordinates('A1'); 
		$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
		
		//Data de la empresa
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('C1',$rowsEmp['nombre_empresa']);
		$objPHPExcel->getActiveSheet()->getStyle('C1')->applyFromArray($styleArrayEmp);
		$objPHPExcel->getActiveSheet()->setCellValue('C2', $rowsEmp['rif']);
		$objPHPExcel->getActiveSheet()->getStyle('C2')->applyFromArray($styleArrayEmp);
		$objPHPExcel->getActiveSheet()->setCellValue('C3',$rowsEmp['web']);
		$objPHPExcel->getActiveSheet()->mergeCells('C3:D3');
		$objPHPExcel->getActiveSheet()->getCell('C3')->getHyperlink()->setUrl("http://".$rowsEmp['web']);
		$objPHPExcel->getActiveSheet()->getStyle('C3')->applyFromArray($styleArrayEmp);
	
	$sqlUser = sprintf("SELECT id_usuario, nombre_usuario, pg_empleado.id_empleado, 
							CONCAT_WS(' ',nombre_empleado, apellido) AS nombre_apellido_empleado
						  FROM pg_usuario
							INNER JOIN pg_empleado ON pg_usuario.id_empleado = pg_empleado.id_empleado 
						  WHERE id_usuario = %s;",
						$_SESSION['idUsuarioSysGts']);
		$queryUser = mysql_query($sqlUser);  
			if(!$queryUser){ die("Error: ".mysql_error().__LINE__); } 
		$rowsUser = mysql_fetch_array($queryUser);
		
		//fecha y hora
		$objPHPExcel->getActiveSheet()->setCellValue('I1', $fecha);
		//$objPHPExcel->getActiveSheet()->mergeCells('I1:I1');
		$objPHPExcel->getActiveSheet()->getStyle('I1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->setCellValue('I2', $hora);
		$objPHPExcel->getActiveSheet()->getStyle('I2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->setCellValue('G3', 'Genereado por:');
		$objPHPExcel->getActiveSheet()->getStyle('G3')->applyFromArray($styleArrayTex);
		$objPHPExcel->getActiveSheet()->setCellValue('H3', $rowsUser['nombre_apellido_empleado']);
		$objPHPExcel->getActiveSheet()->mergeCells('H3:I3');

		//titulo de la hoja
		$objPHPExcel->getActiveSheet()->setCellValue('B5',$titulo);
		$objPHPExcel->getActiveSheet()->mergeCells('B5:J5');
		$objPHPExcel->getActiveSheet()->getStyle('B5:I5')->applyFromArray($styleArrayTitulo);

	$valCadBusq = explode("|", $_GET['valBusq']);
	$idEmpresa = $valCadBusq[0];
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("numero_factura_proveedor = %s ",
				valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("numero_control_factura = %s ",
				valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_orden_compra = %s ",
				valTpDato($valCadBusq[3], "int"));
	}

	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("numero_solicitud LIKE %s ",
				valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha_origen BETWEEN %s AND %s ",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
		
		$filaInicio = 9;
		
		$objPHPExcel->getActiveSheet()->setCellValue('B7', "Facturas desde: ".date("Y-m-d", strtotime($valCadBusq[5]))." Hasta ".date("Y-m-d", strtotime($valCadBusq[6])));
		$objPHPExcel->getActiveSheet()->getStyle('B7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->mergeCells('B7:D7');
		$objPHPExcel->getActiveSheet()->getStyle('B7:D7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	} else {
		$filaInicio = 7;
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_factura_proveedor LIKE %s
		OR numero_control_factura LIKE %s
		OR id_orden_compra LIKE %s
		OR numero_solicitud LIKE %s
		OR cp_proveedor.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
		//Datos de la cabecera del cuadro
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('B'.$filaInicio, 'Num. Factura')
					->setCellValue('C'.$filaInicio, 'Fecha')
					->setCellValue('D'.$filaInicio, 'Sub-Total')
					->setCellValue('E'.$filaInicio, 'ITBMS')
					->setCellValue('F'.$filaInicio, 'Total')
					->setCellValue('G'.$filaInicio, 'Nombres Provedor')
					->setCellValue('H'.$filaInicio, 'Provedor NIT')
					->setCellValue('I'.$filaInicio, 'Digito Verificador '.$spanNIT)
					->setCellValue('J'.$filaInicio, 'Justificacion de compra');
		$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':J'.$filaInicio)->applyFromArray($styleArrayHead);

	//CONSULTA LAS FACTURAS POR RANGO DE FECHA
	$sqlFactura = sprintf("SELECT numero_factura_proveedor,fecha_origen, subtotal_factura,

						(SELECT SUM(subtotal_iva) AS total_iva
						   FROM cp_factura_iva
						 WHERE cp_factura_iva.id_factura = vw_ga_facturas_compra.id_factura
						   GROUP BY id_factura) AS total_iva,
					
					  cp_proveedor.nombre AS nombre_proveedor, rif AS rif_proveedor, nit AS digito_verificador,vw_ga_facturas_compra.abreviacion,
					
							(CASE
								WHEN (vw_ga_facturas_compra.id_factura IS NULL) THEN
									(((vw_ga_facturas_compra.subtotal_pedido - vw_ga_facturas_compra.subtotal_descuento_pedido)
									+
									IFNULL((SELECT SUM(ord_gasto.monto) AS total_gasto
										FROM ga_orden_compra_gasto ord_gasto
										WHERE (ord_gasto.id_orden_compra = vw_ga_facturas_compra.id_orden_compra)), 0))
										
									+
									IFNULL(
							(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
							   FROM ga_orden_compra_iva ped_iva
							 WHERE (ped_iva.id_orden_compra = vw_ga_facturas_compra.id_orden_compra)), 0))
								WHEN (vw_ga_facturas_compra.id_factura IS NOT NULL) THEN
									(((vw_ga_facturas_compra.subtotal_factura - vw_ga_facturas_compra.subtotal_descuento)
									+
									IFNULL((SELECT SUM(fac_gasto.monto) AS total_gasto
										FROM cp_factura_gasto fac_gasto
										WHERE (fac_gasto.id_factura = vw_ga_facturas_compra.id_factura)), 0))
									+
									IFNULL((SELECT SUM(fac_iva.subtotal_iva) AS total_iva
										FROM cp_factura_iva fac_iva
										WHERE (fac_iva.id_factura = vw_ga_facturas_compra.id_factura)), 0))
							END) AS total_factura,
                            (SELECT justificacion_compra FROM ga_solicitud_compra 
								WHERE id_solicitud_compra = vw_ga_facturas_compra.id_solicitud_compra) AS justificacion_compra
					
					FROM vw_ga_facturas_compra
					INNER JOIN cp_proveedor ON vw_ga_facturas_compra.id_proveedor = cp_proveedor.id_proveedor
						%s
						AND (estatus_orden_compra = 3 OR id_solicitud_compra IS NULL)
						ORDER BY fecha_origen;", 
						$sqlBusq);

	$queryFactura = mysql_query($sqlFactura);  
	
		if(!$queryFactura){ die("Error: ".mysql_error().__LINE__); } 
	$totalFacturas = mysql_num_rows($queryFactura);
//die($totalFacturas);
	$count = $filaInicio;
	$totalFilas = ($count + $totalFacturas);
		while($rowsFactura = mysql_fetch_array($queryFactura)){
			$count++;
			
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('B'.$count, $rowsFactura['numero_factura_proveedor'])
					->setCellValue('C'.$count, date("d-m-Y", strtotime($rowsFactura['fecha_origen'])))
					->setCellValue('D'.$count, $rowsFactura['subtotal_factura'])
					->setCellValue('E'.$count, $rowsFactura['total_iva'])
					->setCellValue('F'.$count, $rowsFactura['total_factura'])
					->setCellValue('G'.$count, $rowsFactura['nombre_proveedor'])
					->setCellValue('H'.$count, $rowsFactura['rif_proveedor'])
					->setCellValue('I'.$count, $rowsFactura['digito_verificador'])
					->setCellValue('J'.$count, utf8_encode($rowsFactura['justificacion_compra']));
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
		
		$objPHPExcel->getActiveSheet()->getStyle('B'.$count.':F'.$count)->applyFromArray($styleArrayTex2);
		$objPHPExcel->getActiveSheet()->getStyle('H'.$count.':I'.$count)->applyFromArray($styleArrayTex2);
		(fmod($count, 2) == 0) ? "" : $objPHPExcel->getActiveSheet()->getStyle('B'.$count.':J'.$count)->applyFromArray($styleArrayRelleno);
		
		}
		//LE COLOCA EL BORDER A TODA LA TABLA
		$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':J'.$totalFilas)->applyFromArray($styleArray);
		$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':J'.$totalFilas)->applyFromArray($styleArray2);

//Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle($titulo); //"'".$tituloPenstana."'"co
$objPHPExcel->setActiveSheetIndex(0);

// Redirect output to a client's web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename='.$nombreArchivo);
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>