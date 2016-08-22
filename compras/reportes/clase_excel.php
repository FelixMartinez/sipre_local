<?php
$styleArrayTitulo = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FF4F81BD'
		),
		'size' => 12,
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
    ),
);

$styleArrayCampo = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FFFFFFFF'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF95B3D7'
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => 'FF4F81BD',
        ),
        'endcolor' => array(
            'argb' => 'FF4F81BD',
        ),
    ),
);

$styleArrayCampo2 = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FFFFFFFF'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FFC4D79B'
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => 'FF9BBB59',
        ),
        'endcolor' => array(
            'argb' => 'FF9BBB59',
        ),
    ),
);

$styleArrayColumna = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FFFFFFFF'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
    ),
    'borders' => array(
        'allborders' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF95B3D7'
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => 'FF4F81BD',
        ),
        'endcolor' => array(
            'argb' => 'FF4F81BD',
        ),
    ),
);

$styleArrayFila1 = array(
    'font' => array(
		'color' => array(
			'argb' => 'FF000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_GENERAL,
    ),
    'borders' => array(
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF95B3D7'
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => 'FFDCE6F1',
        ),
        'endcolor' => array(
            'argb' => 'FFDCE6F1',
        ),
    ),
);

$styleArrayFila2 = array(
    'font' => array(
		'color' => array(
			'argb' => 'FF000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_GENERAL,
    ),
    'borders' => array(
        'left' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF95B3D7'
			),
        ),
        'right' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF95B3D7'
			),
        ),
    )
);

$styleArrayResaltarTotal = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FF000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'top' => array(
            'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
			'color' => array(
				'argb' => 'FF4F81BD'
			),
        ),
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF4F81BD'
			),
        ),
    )
);

$styleArrayResaltarTotal2 = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FF000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'top' => array(
            'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
			'color' => array(
				'argb' => 'FF9BBB59'
			),
        ),
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF9BBB59'
			),
        ),
    )
);

function cabeceraExcel($objPHPExcel, $idEmpresa, $colHasta) {
	for ($col = 'A'; $col <= $colHasta; $col++) {
		$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
	}
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$queryEmp = sprintf("SELECT
		id_empresa_reg,
		IF (id_empresa_suc > 0, CONCAT_WS(' - ', nombre_empresa, nombre_empresa_suc), nombre_empresa) AS nombre_empresa,
		rif,
		direccion,
		telefono1,
		telefono2,
		web,
		logo_familia
	FROM vw_iv_empresas_sucursales
	WHERE id_empresa_reg = %s
		OR ((%s = -1 OR %s IS NULL)AND id_empresa_suc IS NULL);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsEmp = mysql_query($queryEmp);
	if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
	$totalRowsEmp = mysql_num_rows($rsEmp);
	$rowEmp = mysql_fetch_assoc($rsEmp);
	
	//Titulo del libro y seguridad
	if ($totalRowsEmp > 0) {
		if (file_exists("../../".$rowEmp['logo_familia']) && strlen("../../".$rowEmp['logo_familia']) > 4) {
			$objDrawing = new PHPExcel_Worksheet_Drawing();
			$objDrawing->setName('Logo');
			$objDrawing->setDescription('Logo');
			$objDrawing->setPath("../../".$rowEmp['logo_familia']);
			$objDrawing->setHeight(100);
			$objPHPExcel->getActiveSheet()->insertNewRowBefore(1, 8);
			$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
		}
		
		$objPHPExcel->getActiveSheet()->SetCellValue("C1", utf8_encode($rowEmp['nombre_empresa']));
		$objPHPExcel->getActiveSheet()->SetCellValue("C2", utf8_encode("R.I.F.: ".$rowEmp['rif']));
		$objPHPExcel->getActiveSheet()->SetCellValue("C3", utf8_encode($rowEmp['direccion']));
		$objPHPExcel->getActiveSheet()->SetCellValue("C4", utf8_encode("Telf.: ".$rowEmp['telefono1']." ".$rowEmp['telefono2']));
		$objPHPExcel->getActiveSheet()->SetCellValue("C5", utf8_encode($rowEmp['web']));
		$objPHPExcel->getActiveSheet()->getCell("C5")->getHyperlink()->setUrl("http://".$rowEmp['web']);
		
		$objPHPExcel->getActiveSheet()->mergeCells("C1:".$colHasta."1");
		$objPHPExcel->getActiveSheet()->mergeCells("C2:".$colHasta."2");
		$objPHPExcel->getActiveSheet()->mergeCells("C3:".$colHasta."3");
		$objPHPExcel->getActiveSheet()->mergeCells("C4:".$colHasta."4");
		$objPHPExcel->getActiveSheet()->mergeCells("C5:".$colHasta."5");
	}
}
?>