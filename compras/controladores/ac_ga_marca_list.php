<?php
function buscar($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBus= sprintf("%s",
		//$frmBuscar["lstSeccionBus"],
		$frmBuscar["txtCriterio"]);

	$objResponse->loadCommands(listadoMarcas(0,'marca','ASC', $valBus));
	
	return $objResponse;
	
	}

function formMarca() {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_marca_list","insertar")) {
		$objResponse->script("
			$('hddIdMarca').value = '';");
		
		$objResponse->script("		
			if ($('divFlotante').style.display == 'none') {
				$('divFlotante').style.display='';
				centrarDiv($('divFlotante'));
			}
		");
	}
	
	return $objResponse;
}

function cargarMarca($idMarca) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_marca_list","editar")) {
		$queryMarca = sprintf("SELECT * FROM ga_marcas WHERE id_marca = %s", $idMarca);
		$rsMarca = mysql_query($queryMarca);
		if (!$rsMarca) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowMarca = mysql_fetch_assoc($rsMarca);
		
		$objResponse->assign("hddIdMarca","value",$idMarca);
		$objResponse->assign("txtMarca","value",$rowMarca['marca']);
		$objResponse->assign("txtDescripcion","value",$rowMarca['descripcion']);
		$objResponse->assign("listEstatu","value",$rowMarca['estatu']);
		
		$objResponse->script("
			if ($('divFlotante').style.display == 'none') {	
				$('divFlotante').style.display='';
				centrarDiv($('divFlotante'));
			}
		");
	}
	
	return $objResponse;
}

function guardarMarca($valForm) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdMarca'] > 0) {
		if (xvalidaAcceso($objResponse,"ga_marca_list","editar")) {
			$updateSQL = sprintf("UPDATE ga_marcas SET
				marca = %s,
				descripcion = %s,
				estatu = %s
			WHERE id_marca = %s",
				valTpDato($valForm['txtMarca'], "text"),
				valTpDato($valForm['txtDescripcion'], "text"),
				valTpDato($valForm['listEstatu'], "int"),
				valTpDato($valForm['hddIdMarca'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"ga_marca_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO ga_marcas (marca, descripcion, estatu) VALUE (%s, %s, %s)",
				valTpDato($valForm['txtMarca'], "text"),
				valTpDato($valForm['txtDescripcion'], "text"),
				valTpDato($valForm['listEstatu'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idMarca = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Marca Guardada con Exito");
	
	$objResponse->script("byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listadoMarcas(0,'marca','ASC'));

		
	return $objResponse;
}

function eliminarMarca($valForm) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_marca_list","eliminar")) {
		if (isset($valForm['cbxMarc'])) {
			mysql_query("START TRANSACTION;");
			
			foreach($valForm['cbxMarc'] as $indiceItm=>$valorItm) {
				$deleteSQL = sprintf("UPDATE ga_marcas SET estatu = 0 WHERE id_marca = %s",
					valTpDato($valorItm, "int"));
				
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			}
			
			mysql_query("COMMIT;");
			
			$numcbx = count($valForm['cbxMarc']);			
			$objResponse->alert("Se eliminaron ".$numcbx." Marcas");
			
			$objResponse->loadCommands(listadoMarcas(0,'marca','ASC'));
			
		} else {
			
			$objResponse->alert("Debe seleccionar un campo");
			
			}
	}
		
	return $objResponse;
}

function eliminarUnaMarca($idMarca) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_marca_list","eliminar")) {
		if ($idMarca != "") {
			mysql_query("START TRANSACTION;");
			
			$deleteSQL = sprintf("UPDATE ga_marcas SET estatu = 0 WHERE id_marca = %s",
				valTpDato($idMarca, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
			mysql_query("COMMIT;");
			
			$objResponse->alert("Registro Eliminado");
			
	$objResponse->loadCommands(listadoMarcas(0,'marca','ASC'));
		}
	}
	
	return $objResponse;
}

function listadoMarcas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
					
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(marca LIKE %s) OR (descripcion LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
		
	
	$query = sprintf("SELECT * FROM ga_marcas %s",
					 $sqlBusq);
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
		//$objResponse->alert($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listadoMarcas", "50%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Marca");
		$htmlTh .= ordenarCampo("xajax_listadoMarcas", "50%", $pageNum, "descripcion",$campOrd,$tpOrd,$valBusq,$maxRows,"Descripcion");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatu']) {
			case 0 : $imgEstatusIva = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatusIva = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusIva = "";
		}	
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusIva."</td>";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxMarc[]\" type=\"checkbox\" value=\"%s\"></td>",$row['id_marca']);
			$htmlTb .= "<td>".utf8_encode($row['marca'])."</td>";
			$htmlTb .= "<td align=\"left\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"left\" width=\"100%\">".utf8_encode($row['descripcion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante(this, 'tblMarca', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_marca']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"eliminar('%s')\" src=\"../img/iconos/cross.png\"/></td>",
				$row['id_marca']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoMarcas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoMarcas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoMarcas(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoMarcas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoMarcas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListMarca","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"formMarca");
$xajax->register(XAJAX_FUNCTION,"cargarMarca");
$xajax->register(XAJAX_FUNCTION,"guardarMarca");
$xajax->register(XAJAX_FUNCTION,"eliminarMarca");
$xajax->register(XAJAX_FUNCTION,"eliminarUnaMarca");
$xajax->register(XAJAX_FUNCTION,"listadoMarcas");
?>