<?php


function asignarProveedor($idProveedor, $asigDescuento = true) {
	$objResponse = new xajaxResponse();
	
	$queryProveedor = sprintf("SELECT * FROM cp_proveedor prov WHERE prov.id_proveedor = %s", valTpDato($idProveedor, "text"));
	$rsProveedor = mysql_query($queryProveedor);
	if (!$rsProveedor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProveedor = mysql_fetch_assoc($rsProveedor);
	
	$objResponse->assign("txtIdProv","value",$rowProveedor['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",utf8_encode($rowProveedor['nombre']));
	
	if ($asigDescuento == true)
		$objResponse->assign("txtDescuento","value",$rowProveedor['descuento']);
	
	$objResponse->script("
	byId('divFlotante2').style.display='none';");
	
	return $objResponse;
}

function buscarArticulo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$codArticulo,
		$frmBuscar['txtCriterio']);

	$objResponse->loadCommands(listaCosto(0, "id_articulo", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "", "", $valBusq));
	
	return $objResponse;
}

function cargarCosto($idArticulo, $idArticuloCosto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_articulo_costo_list","insertar")
	|| xvalidaAcceso($objResponse,"iv_articulo_costo_list","editar")) {
		if ($idArticuloCosto != "") {
			$queryArticuloCosto = sprintf("SELECT art_costo.*,
				prov.nombre AS nombre_proveedor,
				art.codigo_articulo
			FROM iv_articulos_costos art_costo
				INNER JOIN iv_articulos art ON (art_costo.id_articulo = art.id_articulo)
				INNER JOIN cp_proveedor prov ON (art_costo.id_proveedor = prov.id_proveedor)
			WHERE id_articulo_costo = %s;",
				valTpDato($idArticuloCosto, "int"));
			$rsArticuloCosto = mysql_query($queryArticuloCosto);
			if (!$rsArticuloCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArticuloCosto = mysql_fetch_assoc($rsArticuloCosto);
			
			$idArticulo = $rowArticuloCosto['id_articulo'];
			$codigoArticulo = $rowArticuloCosto['codigo_articulo'];
		} else {
			$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos
			WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArticulo = mysql_fetch_assoc($rsArticulo);
			
			$idArticulo = $rowArticulo['id_articulo'];
			$codigoArticulo = $rowArticulo['codigo_articulo'];
		}
		
		$objResponse->assign("hddIdArticuloCosto","value",$rowArticuloCosto['id_articulo_costo']);
		$objResponse->assign("hddIdArticulo","value",$idArticulo);
		$objResponse->assign("txtCodigoArticulo","value",elimCaracter($codigoArticulo,";"));
		$objResponse->assign("txtIdProv","value",$rowArticuloCosto['id_proveedor']);
		$objResponse->assign("txtNombreProv","value",$rowArticuloCosto['nombre_proveedor']);
		$objResponse->assign("txtFechaCosto","value",implode("-", array_reverse(explode("-", $rowArticuloCosto['fecha']))));
		$objResponse->assign("txtCosto","value",$rowArticuloCosto['costo']);
		
		$objResponse->script("
		byId('txtCodigoArticulo').className = 'inputInicial';
		byId('txtIdProv').className = 'inputInicial';
		byId('txtFechaCosto').className = 'inputInicial';
		byId('txtCosto').className = 'inputInicial';");
			
		$objResponse->assign("tdFlotanteTitulo1","innerHTML","Editar Costo");
		$objResponse->script("
		byId('divFlotante1').style.display='';
		centrarDiv(byId('divFlotante1'));
		
		byId('txtCosto').focus();
		byId('txtCosto').select();");
	}
	
	return $objResponse;
}

function exportarCosto($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_articulo_costo_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function guardarCosto($frmCostoArticulo, $frmListaCostos, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$idArticulo = $frmCostoArticulo['hddIdArticulo'];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_articulo = %s",
		valTpDato($idArticulo, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	// BUSCA QUE EMPRESAS TIENE DICHO ARTICULO
	$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa %s;", $sqlBusq);
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowArtEmp = mysql_fetch_assoc($rsArtEmp)) {
		$idEmpresa = $rowArtEmp['id_empresa'];
		
		if ($frmCostoArticulo['hddIdArticuloCosto'] > 0) {
			// BUSCA EL ULTIMO COSTO DEL ARTICULO
			$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos WHERE id_articulo = %s AND id_empresa = %s ORDER BY fecha_registro DESC LIMIT 1;",
				valTpDato($idArticulo, "int"),
				valTpDato($idEmpresa, "int"));
			$rsCostoArt = mysql_query($queryCostoArt);
			if (!$rsCostoArt) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$existeCosto = false;
			while ($rowCostoArt = mysql_fetch_assoc($rsCostoArt)) {
				if (round($rowCostoArt['costo'],2) == round($frmCostoArticulo['txtCosto'],2)
				&& date("Y-m-d",strtotime($rowCostoArt['fecha'])) == date("Y-m-d",strtotime($frmCostoArticulo['txtFechaCosto']))) {
					$existeCosto = true;
					$idArticuloCosto = $rowCostoArt['id_articulo_costo'];
				}
			}
			
			if ($existeCosto == true) {
				if (!xvalidaAcceso($objResponse,"iv_articulo_costo_list","editar")) { return $objResponse; }
				
				$updateSQL = sprintf("UPDATE iv_articulos_costos SET
					id_proveedor = %s,
					id_articulo = %s,
					costo = %s,
					fecha = %s
				WHERE id_articulo_costo = %s;",
					valTpDato($frmCostoArticulo['txtIdProv'], "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($frmCostoArticulo['txtCosto'], "real_inglesa"),
					valTpDato(date("Y-m-d",strtotime($frmCostoArticulo['txtFechaCosto'])),"date"),
					valTpDato($idArticuloCosto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			} else {
				if (!xvalidaAcceso($objResponse,"iv_articulo_costo_list","insertar")) { return $objResponse; }
				
				$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, costo, fecha, fecha_registro)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idEmpresa, "int"),
					valTpDato($frmCostoArticulo['txtIdProv'], "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($frmCostoArticulo['txtCosto'], "real_inglesa"),
					valTpDato(date("Y-m-d",strtotime($frmCostoArticulo['txtFechaCosto'])),"date"),
					valTpDato("NOW()","campo"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idArticuloCosto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// ACTUALIZA EL COSTO PROMEDIO
			$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		} else {
			if (!xvalidaAcceso($objResponse,"iv_articulo_costo_list","insertar")) { return $objResponse; }
			
			$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, costo, costo_promedio, fecha, fecha_registro)
			VALUE (%s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idEmpresa, "int"),
				valTpDato($frmCostoArticulo['txtIdProv'], "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($frmCostoArticulo['txtCosto'], "real_inglesa"),
				valTpDato($frmCostoArticulo['txtCosto'], "real_inglesa"),
				valTpDato(date("Y-m-d",strtotime($frmCostoArticulo['txtFechaCosto'])),"date"),
				valTpDato("NOW()","campo"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA EL PRECIO DE VENTA
		$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Costo Guardado con Éxito"));
	
	$objResponse->alert(utf8_encode("Los Precios Han Sido Actualizados Con Éxito"));
	
	$objResponse->script("
	byId('btnCancelarCostoArticulo').click();");
	
	$objResponse->loadCommands(listaCosto(
		$frmListaCostos['pageNum'],
		$frmListaCostos['campOrd'],
		$frmListaCostos['tpOrd'],
		$frmListaCostos['valBusq']));
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		prov.id_proveedor,
		prov.nombre,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.direccion,
		prov.contacto,
		prov.correococtacto,
		prov.telefono,
		prov.fax,
		prov.credito
	FROM cp_proveedor prov %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Proveedores");
	$objResponse->assign("tblListados","width","700");
	$objResponse->script("
	if (byId('divFlotante2').style.display == 'none') {
		byId('divFlotante2').style.display='';
		centrarDiv(byId('divFlotante2'));
	}");
	
	return $objResponse;
}

function listaCosto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = " ";
	$sqlBusq2 = " ";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("fact_comp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[2], "int"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(art.id_articulo = %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[2], "int"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT
		art_costo.id_articulo_costo,
		vw_iv_art_emp.id_empresa,
		art_costo.id_proveedor,
		(SELECT prov.nombre AS nombre FROM cp_proveedor prov
		WHERE prov.id_proveedor = art_costo.id_proveedor) AS nombre_proveedor,
		art_costo.fecha,
		vw_iv_art_emp.existencia,
		art_costo.costo,
		art_costo.costo_promedio,
		art_costo.id_moneda,
		(SELECT moneda.abreviacion AS abreviacion FROM pg_monedas moneda
		WHERE moneda.idmoneda = art_costo.id_moneda) AS abreviacion_moneda,
		(SELECT moneda.predeterminada FROM pg_monedas moneda
		WHERE moneda.idmoneda = art_costo.id_moneda
			AND moneda.estatus = 1
			AND moneda.predeterminada = 1) AS predeterminada,
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		art.codigo_articulo_prov,
		art.id_tipo_articulo,
		vw_iv_art_emp.clasificacion,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM iv_articulos art
		LEFT JOIN iv_articulos_costos art_costo ON (art_costo.id_articulo = art.id_articulo)
		LEFT JOIN vw_iv_articulos_empresa vw_iv_art_emp ON (art.id_articulo = vw_iv_art_emp.id_articulo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_art_emp.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE (art_costo.id_articulo_costo = (SELECT art_costo.id_articulo_costo FROM iv_articulos_costos art_costo
											WHERE art_costo.id_articulo = art.id_articulo
												AND art_costo.id_empresa = vw_iv_art_emp.id_empresa
											ORDER BY art_costo.id_articulo_costo DESC
											LIMIT 1)
		OR ISNULL(art_costo.id_articulo_costo)) %s
	UNION
	SELECT
		NULL AS id_articulo_costo,
		fact_comp.id_empresa,
		fact_comp.id_proveedor,
		(SELECT prov.nombre AS nombre FROM cp_proveedor prov
		WHERE prov.id_proveedor = fact_comp.id_proveedor) AS nombre_proveedor,
		fact_comp.fecha_origen,
		NULL AS existencia,
		fact_comp_det_imp.costo_unitario,
		fact_comp_det_imp.costo_unitario,
		fact_comp_imp.id_moneda_tasa_cambio,
		(SELECT moneda.abreviacion AS abreviacion FROM pg_monedas moneda
		WHERE moneda.idmoneda = fact_comp_imp.id_moneda_tasa_cambio) AS abreviacion_moneda,
		(SELECT moneda.predeterminada FROM pg_monedas moneda
		WHERE moneda.idmoneda = fact_comp_imp.id_moneda_tasa_cambio
			AND moneda.estatus = 1
			AND moneda.predeterminada = 1) AS predeterminada,
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		art.codigo_articulo_prov,
		art.id_tipo_articulo,
		art.clasificacion,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_factura_detalle_importacion fact_comp_det_imp
		JOIN cp_factura_detalle fact_comp_det ON (fact_comp_det_imp.id_factura_detalle = fact_comp_det.id_factura_detalle)
		JOIN cp_factura fact_comp ON (fact_comp_det.id_factura = fact_comp.id_factura)
		JOIN cp_factura_importacion fact_comp_imp ON (fact_comp.id_factura = fact_comp_imp.id_factura)
		JOIN iv_articulos art ON (fact_comp_det.id_articulo = art.id_articulo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (fact_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE fact_comp_det.id_factura_detalle = (SELECT fact_comp_det2.id_factura_detalle
												FROM cp_factura_detalle_importacion fact_comp_det_imp2
													JOIN cp_factura_detalle fact_comp_det2 ON (fact_comp_det_imp2.id_factura_detalle = fact_comp_det2.id_factura_detalle)
													JOIN cp_factura fact_comp2 ON (fact_comp_det2.id_factura = fact_comp2.id_factura)
												WHERE fact_comp_det2.id_articulo = art.id_articulo
												ORDER BY fact_comp2.fecha_origen DESC, fact_comp_det_imp2.costo_unitario DESC
												LIMIT 1) %s", $sqlBusq, $sqlBusq2);
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaCosto", "12%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "16%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "10%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "22%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Decripción"));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "8%", $pageNum, "codigo_articulo_prov", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código Prov."));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "6%", $pageNum, "existencia", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Existencia"));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "8%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "8%", $pageNum, "costo_promedio", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Promedio");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$classDisponible = ($row['existencia'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".implode("-", array_reverse(explode("-", $row['fecha'])))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_articulo_prov'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".htmlentities("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".htmlentities("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".htmlentities("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".htmlentities("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".htmlentities("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".htmlentities("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format($row['existencia'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda'].number_format($row['costo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda'].number_format($row['costo_promedio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($row['predeterminada'] == 1 || !$row['fecha']) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_cargarCosto('%s', '%s');\" src=\"../img/iconos/pencil.png\"/>",
					$row['id_articulo'],
					$row['id_articulo_costo']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCosto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaCostos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"cargarCosto");
$xajax->register(XAJAX_FUNCTION,"exportarCosto");
$xajax->register(XAJAX_FUNCTION,"guardarCosto");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaCosto");
?>