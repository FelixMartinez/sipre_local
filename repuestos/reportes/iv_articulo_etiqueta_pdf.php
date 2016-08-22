<?php
set_time_limit(0);
if (file_exists("../../connections/conex.php")) { require_once("../../connections/conex.php"); }
if (file_exists('../../inc_sesion.php')) { require_once('../../inc_sesion.php'); }
require_once('../../clases/barcode128.inc.php');

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','cm', array('3.18','5.71')); // ANCHO DEL DOCUMENTO = 5.71;
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valBusq2 = $_GET["valBusq2"];
$valBusq3 = $_GET["valBusq3"];

$valCadBusq = explode("|",$valBusq);

if (strlen($valBusq3) > 0) {
	$arrayEtiqueta = explode("|",$valBusq3);
} else if ($valBusq2 > 0) {
	$query = sprintf("SELECT 
		tasa_cambio.nombre_tasa_cambio
	FROM cp_factura_importacion fact_comp_imp
		INNER JOIN pg_tasa_cambio tasa_cambio ON (fact_comp_imp.id_tasa_cambio = tasa_cambio.id_tasa_cambio)
	WHERE fact_comp_imp.id_factura = %s;",
		valTpDato($valBusq2, "int"));
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$nombreTasaCambio = ($totalRows > 0) ? strtoupper("ADQUIRIDO CON ".$row['nombre_tasa_cambio']) : "";
	
	$query = sprintf("SELECT * FROM cp_factura_detalle fact_comp_det
	WHERE fact_comp_det.id_factura = %s;",
		valTpDato($valBusq2, "int"));
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayEtiqueta[] = $row['id_articulo'].",".$row['id_casilla'].",".$row['cantidad'];
	}
} else if (strlen($valBusq) > 0) {
	$arrayEtiqueta = $valCadBusq;
}

foreach ($arrayEtiqueta as $indice => $valor){
	$arrayUbicacion = explode(",",$valor);
	
	if (strlen($valBusq) > 0) {
		$idArticulo = $arrayUbicacion[0];
		$idCasilla = $arrayUbicacion[1];
		$cantImprimir = $arrayUbicacion[2];
		$nombreTasaCambio = (strlen($arrayUbicacion[3]) > 0) ? strtoupper("ADQUIRIDO CON ".$arrayUbicacion[3]) : "";
	} else if ($valBusq2 > 0) {
		$idArticulo = $arrayUbicacion[0];
		$idCasilla = $arrayUbicacion[1];
		$cantImprimir = $arrayUbicacion[2];
	}
	
	if ($idCasilla > 0 || strlen($valBusq3) > 0) {
		if (!(strlen($valBusq3) > 0)) {
			// BUSCA LOS DATOS DEL ARTICULO EN LA UBICACION
			$queryArtAlm = sprintf("SELECT vw_iv_art_alm.*,
				art.codigo_articulo,
				art.descripcion
			FROM iv_articulos art
				INNER JOIN vw_iv_articulos_almacen vw_iv_art_alm ON (art.id_articulo = vw_iv_art_alm.id_articulo)
			WHERE id_casilla = %s
				AND estatus_articulo_almacen = 1;",
				valTpDato($idCasilla, "int"));
			$rsArtAlm = mysql_query($queryArtAlm);
			if (!$rsArtAlm) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
			$rowArtAlm = mysql_fetch_array($rsArtAlm);
		}
		
		if ($totalRowsArtAlm > 0) {
			$idEmpresa = $rowArtAlm['id_empresa'];
			$idArticulo = $rowArtAlm['id_articulo'];
			
			// VERIFICA VALORES DE CONFIGURACION (Mostrar Logotipo en la Etiqueta)
			$queryConfig13 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 13 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
			$rsConfig13 = mysql_query($queryConfig13);
			if (!$rsConfig13) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsConfig13 = mysql_num_rows($rsConfig13);
			$rowConfig13 = mysql_fetch_assoc($rsConfig13);
			
			// VERIFICA VALORES DE CONFIGURACION (Mostrar Precio en la Etiqueta)
			$queryConfig14 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 14 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
			$rsConfig14 = mysql_query($queryConfig14);
			if (!$rsConfig14) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsConfig14 = mysql_num_rows($rsConfig14);
			$rowConfig14 = mysql_fetch_assoc($rsConfig14);
			
			// VERIFICA VALORES DE CONFIGURACION (Mostrar Tasa de Cambio en la Etiqueta)
			$queryConfig15 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 15 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
			$rsConfig15 = mysql_query($queryConfig15);
			if (!$rsConfig15) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsConfig15 = mysql_num_rows($rsConfig15);
			$rowConfig15 = mysql_fetch_assoc($rsConfig15);
			
			// BUSCA LOS DATOS DE LA EMPRESA
			$queryEmpresa = sprintf("SELECT nombre_empresa, rif, logo_familia FROM vw_iv_empresas_sucursales
			WHERE id_empresa_reg = %s;",
				valTpDato($idEmpresa, "int"));
			$rsEmpresa = mysql_query($queryEmpresa);
			if (!$rsEmpresa) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$rowEmpresa = mysql_fetch_array($rsEmpresa);
			
			$rifEmpresa = $rowEmpresa['rif'];
			$nombreEmpresa = $rowEmpresa['nombre_empresa'];
			
			if ($rowConfig14['valor'] == 1) {
				// BUSCA EL PRECIO DEL ARTICULO
				$queryArtPrecio = sprintf("SELECT
					art_precio.precio AS precio_unitario,
					
					(SELECT iva.observacion
					FROM iv_articulos_impuesto art_impsto
						INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
					WHERE iva.estado = 1 AND iva.tipo IN (6) AND iva.activo = 1
						AND art_impsto.id_articulo = art.id_articulo
					LIMIT 1) AS descripcion_iva,
					
					(SELECT SUM(iva.iva)
					FROM iv_articulos_impuesto art_impsto
						INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
					WHERE iva.tipo IN (6,2)
						AND art_impsto.id_articulo = art.id_articulo) AS porcentaje_iva,
					
					(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
					WHERE kardex.id_articulo = art.id_articulo
						AND kardex.tipo_movimiento = 1
					ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra
					
				FROM iv_articulos art
					INNER JOIN iv_articulos_precios art_precio ON (art.id_articulo = art_precio.id_articulo)
				WHERE art_precio.id_articulo = %s
					AND art_precio.id_precio = art.id_precio_predeterminado;",
					valTpDato($idArticulo, "int"));
				$rsArtPrecio = mysql_query($queryArtPrecio);
				if (!$rsArtPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
				$totalRowsArtPrecio = mysql_num_rows($rsArtPrecio);
				$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
				
				$precioUnit = $rowArtPrecio['precio_unitario'];
				$precioUnitFinal = $rowArtPrecio['precio_unitario'] + ($rowArtPrecio['precio_unitario'] * $rowArtPrecio['porcentaje_iva'] / 100);
				$arrayPrecioUnit = explode(".", $precioUnit);
				$arrayPrecioUnit = array_reverse($arrayPrecioUnit);
				$codigoPrecio = "A".implode("Z",$arrayPrecioUnit);
			}
			
			$codificacionBarra = $rowArtAlm['id_articulo'].$codigoPrecio;
			$codigoArticulo = elimCaracter($rowArtAlm['codigo_articulo'],";");
			$descripcionArticulo = $rowArtAlm['descripcion'];
			$descripcionAlmacen = $rowArtAlm['descripcion_almacen'];
			$ubicacionAlmacen = str_replace("-[]", "", $rowArtAlm['ubicacion']);
		} else {
			$codificacionBarra = $arrayUbicacion[0];
			$codigoArticulo = $arrayUbicacion[0];
			$descripcionArticulo = $arrayUbicacion[1];
			$rifEmpresa = $arrayUbicacion[2];
			$nombreEmpresa = $arrayUbicacion[3];
			$descripcionAlmacen = $arrayUbicacion[4];
			$ubicacionAlmacen = $arrayUbicacion[5];
			$nombreTasaCambio = (strlen($arrayUbicacion[6]) > 0) ? strtoupper("ADQUIRIDO CON ".$arrayUbicacion[6]) : "";
			$cantImprimir = $arrayUbicacion[7];
			$rowConfig15['valor'] = (strlen($nombreTasaCambio) > 0) ? 1 : 0;
		}
		
		if (strlen($codigoArticulo) > 0) {
			$rutaLogo = (strlen($rowEmpresa['logo_familia']) > 5) ? "../../".$rowEmpresa['logo_familia'] : "";
			
			$rutaCodigoBarra = "tmp/img_codigo".$indice;
			$imagenCodigoBarra = getBarcode($codificacionBarra, $rutaCodigoBarra, 2, 1, 25, "a", 1);
			$imagenLogo = getimagesize($rutaLogo);
			
			if ($imagenCodigoBarra) {
				$anchoCm = ($imagenCodigoBarra[0] / 37.795276); // 1cm = 37.795276 px
				$altoCm = ($imagenCodigoBarra[1] / 37.795276); // 1cm = 37.795276 px
				$altoCmLogo = $imagenLogo[1] / 37.795276;
				$anchoCmLogo = $imagenLogo[0] / 37.795276;
				
				// CALCULA EL ANCHO MAXIMO DEPENDIENDO DEL ALTO MAXIMO PERMITIDO
				$anchoCmValidado = 1.4 * $anchoCm / $altoCm; // ALTO MAXIMO DEL CODIGO DE BARRA = 1.4;
				$anchoCmValidadoLogo = ($altoCmLogo > 0) ? 1.4 * $anchoCmLogo / $altoCmLogo : 0; // ALTO MAXIMO DEL LOGO = 1.4;
				if ($rowConfig13['valor'] == 1) {
					$anchoDisponible = 5.71 - 1.5 - 0.2; // LE RESTO 0.2 PARA QUE NO QUEDE TAN EXACTO DE LAS ORILLAS
					if ($anchoCmValidado > $anchoDisponible) {
						$anchoCmValidado = $anchoDisponible;
						$ancho = $anchoDisponible;
						$alto = '';
					} else {
						$ancho = '';
						$alto = '1.4';
					}
					// PARA CENTRAR LA IMAGEN MEDIANTE POSICION X
					$posXCodigoBarra = ($anchoDisponible / 2) - (($anchoDisponible / 2) - (($anchoDisponible - $anchoCmValidado) / 2)) + 1.5 + 0.1;
					
					$anchoDisponibleLogo = 1.5;
					if ($anchoCmValidadoLogo > $anchoDisponibleLogo) {
						$anchoCmValidadoLogo = $anchoDisponibleLogo;
						$anchoLogo = $anchoDisponibleLogo;
						$altoLogo = '';
					} else {
						$anchoLogo = '';
						$altoLogo = '1.4';
					}
					// PARA CENTRAR LA IMAGEN MEDIANTE POSICION X
					$posXLogo = ($anchoDisponibleLogo / 2) - (($anchoDisponibleLogo / 2) - (($anchoDisponibleLogo - $anchoCmValidadoLogo) / 2));
				} else {
					$anchoDisponible = 5.71 - 0.2; // LE RESTO 0.2 PARA QUE NO QUEDE TAN EXACTO DE LAS ORILLAS
					if ($anchoCmValidado > $anchoDisponible) {
						$anchoCmValidado = $anchoDisponible;
						$ancho = $anchoDisponible;
						$alto = '';
					} else {
						$ancho = '';
						$alto = '1.4';
					}
					// PARA CENTRAR LA IMAGEN MEDIANTE POSICION X
					$posXCodigoBarra = ($anchoDisponible / 2) - (($anchoDisponible / 2) - (($anchoDisponible - $anchoCmValidado) / 2)) + 0.1;
				}
				
				for ($i = 0; $i < $cantImprimir; $i++){
					$pdf->AddPage();
					
					// DATOS DE LA EMPRESA
					$pdf->SetFont('Arial','B',4);
					$pdf->SetY(0.2); $pdf->SetX(0.1);
					$pdf->Cell(0,0,utf8_decode($nombreEmpresa),0,0,'L');
					$pdf->SetY(0.4); $pdf->SetX(0.1);
					$pdf->Cell(0,0,utf8_decode($rifEmpresa),0,0,'L');
					
					$pdf->SetFont('Arial','B',8);
					$pdf->SetY(0.3); $pdf->SetX(0); $pdf->Cell(0,0,utf8_decode(($rowConfig15['valor'] == 1) ? $nombreTasaCambio : ""),0,0,'R');
					
					// DATOS DE LA UBICACION
					$pdf->SetFont('Arial','B',8);
					$pdf->SetY(0.66);
					$pdf->Cell(0,0,utf8_decode($codigoArticulo),0,0,'C');
					$pdf->SetFont('Arial','B',5);
					$pdf->SetY(0.9);
					$pdf->Cell(0,0,substr(utf8_decode($descripcionArticulo), 0, 46),0,0,'C');
					
					// IMAGEN LOGO
					if ($rowConfig13['valor'] == 1) {
						if (strlen($rutaLogo) > 5) {
							$pdf->Image($rutaLogo, $posXLogo, '1.04', $anchoLogo, $altoLogo, '','');
						}
					}
					
					// IMAGEN CODIGO DE BARRA
					$pdf->Image($rutaCodigoBarra.".png", $posXCodigoBarra, '1.04', $ancho, $alto, '','');
					
					// DATOS DEL ARTICULO
					$pdf->SetFont('Arial','B',7);
					$pdf->SetY(2.6);
					$pdf->Cell(3.3,0,utf8_decode($descripcionAlmacen),0,0,'C');
					$pdf->SetY(2.85); $pdf->SetX(0);
					$pdf->Cell(3.3,0,utf8_decode($ubicacionAlmacen),0,0,'C');
					
					if ($rowConfig14['valor'] == 1) {
						// DATOS DEL PRECIO
						$pdf->SetFont('Arial','B',6);
						$pdf->SetY(2.58); $pdf->SetX(3.2);
						$pdf->Cell(0.6, 0, "P.V.P.:", 0, 0, 'L');
						$pdf->Cell(1.6, 0, number_format($precioUnitFinal, 2, ".", ","), 0, 0, 'R');
						
						$pdf->SetFont('Arial','B',4);
						$pdf->SetY(2.73); $pdf->SetX(3.2);
						$pdf->Cell(0.6, 0, utf8_decode($rowArtPrecio['descripcion_iva'])." Incluido", 0, 0, 'L');
						
						$pdf->SetFont('Arial','B',6);
						$pdf->SetY(2.9); $pdf->SetX(3.2);
						$pdf->Cell(0.6, 0, "Fecha:", 0, 0, 'L');
						$pdf->Cell(1.6, 0, date("d-m-Y", strtotime($rowArtPrecio['fecha_ultima_compra'])), 0, 0, 'R');
					}
				}
				unlink($rutaCodigoBarra.".png");
			} else {
				echo "Error al Crear el Código de Barra";
			}
		}
	}
}
//$pdf->AutoPrint(true);
$pdf->Output();
?>