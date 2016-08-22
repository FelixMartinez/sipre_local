<?php
function buscarOrden($valForm){
	$objResponse = new xajaxResponse();

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
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$idEmpresa = $frmBuscar['lstEmpresa'];
	}
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$idEmpresa,
		$valForm['lstTipoOrden'],
		$valForm['lstEstadoOrden'],
		$valForm['txtPalabra']);
		
	$objResponse->loadCommands(listadoOrdenes(0, "numero_orden", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstTipoOrden($selId = ""){
	$objResponse = new xajaxResponse();
			
	$query = "SELECT * FROM sa_tipo_orden ORDER BY sa_tipo_orden.nombre_tipo_orden";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstTipoOrden\" name=\"lstTipoOrden\" class=\"inputHabilitado\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_tipo_orden'])
			$selected = "selected='selected'";
		$html .= "<option ".$selected." value=\"".$row['id_tipo_orden']."\">".utf8_encode($row['nombre_tipo_orden'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoOrden","innerHTML",$html);
	
	return $objResponse;
}

function cargarPagina($idEmpresa){
	
	$objResponse = new xajaxResponse();
	
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
		$objResponse->script("
		byId('trEmpresa').style.display = 'none';");
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$objResponse->script("
		byId('trEmpresa').style.display = '';");
	}
	
	return $objResponse;
}

function devolverOrden($id_orden){
	$objResponse = new xajaxResponse();
	
	$sql= "UPDATE sa_orden SET
		id_estado_orden = 21,
		id_empleado_aprobacion_factura = NULL
	WHERE id_orden = ".$id_orden;
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if($rs){
		$objResponse->alert('Devolucion de Orden realizado con Exito');
		$objResponse->script("$('divDevolver').style.display = 'none';");
		$objResponse->script("xajax_buscarOrden(xajax.getFormValues('frmBuscar');");
	}
	
	return $objResponse;
}

function listadoOrdenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_sa_orden.id_estado_orden = %s AND vw_sa_orden.modo_factura != 'VALE SALIDA'",
		13); //TERMINADO
			
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_sa_orden.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_sa_orden.id_tipo_orden = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_sa_orden.id_estado_orden = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf(" vw_sa_orden.nombre LIKE %s
		OR vw_sa_orden.apellido_cliente_vale LIKE %s
		OR vw_sa_orden.numero_orden = %s
		OR vw_sa_orden.placa LIKE %s
		OR vw_sa_orden.chasis LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato($valCadBusq[3],"int"),
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"));
	}
		
	$query = sprintf("SELECT
		cj_cc_cliente.nombre AS nombre_cliente,
		cj_cc_cliente.apellido AS apellido_cliente,
		cj_cc_cliente.lci,
		cj_cc_cliente.ci,
		cj_cc_cliente.nit,
		vw_sa_orden.*,
		sa_retrabajo_orden.id_orden_retrabajo,
		(SELECT vw_sa_orden.numero_orden FROM vw_sa_orden WHERE vw_sa_orden.id_orden = sa_retrabajo_orden.id_orden_retrabajo) AS numero_orden_retrabajo
	FROM cj_cc_cliente
		INNER JOIN vw_sa_orden ON (cj_cc_cliente.id = vw_sa_orden.id_cliente_pago_orden)
		LEFT OUTER JOIN sa_retrabajo_orden ON (vw_sa_orden.id_orden = sa_retrabajo_orden.id_orden) %s",
		$sqlBusq);
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "tiempo_orden_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Orden"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "numeracion_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Recepción"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, ("Catálogo"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, "Placa");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "13", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, "Chasis");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7", $pageNum, "numero_orden_retrabajo", $campOrd, $tpOrd, $valBusq, $maxRows, "Orden Retrabajo");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "25%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");	
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "13%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Orden");
		$htmlTh .= "<td class=\"noprint\" colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$objResponse->assign("tdTituloListado","innerHTML",("Facturación de Servicios"));
	$cons = 1;
			
	$imgAprobarOrdenDisabled = "<img class=\"puntero\" src=\"../img/iconos/aprobar_presup_disabled.png\"/>";
	$imgEstadoConvertirAPresupuestoDisabled = "<img class=\"puntero\" src=\"../img/iconos/generarPresupuesto_disabled.png\"/>";
	$imgEstadoViewDisabled = "<img class=\"puntero\" src=\"../img/iconos/ico_view_des.png\"/>";
	$imgEstadoAprobDisabled = "<img class=\"puntero\" src=\"../img/iconos/check_disabled.jpg\"/>";
	$imgEdicionDisabled = "<img class=\"puntero\" src=\"../img/iconos/ico_edit_disabled.png\"/>";
	$imgRetrabajoDisabled = "<img class=\"puntero\" src=\"../img/iconos/retrabajo_disabled.png\"/>";
	$imgImprimirOrdenDisabled = "<img class=\"puntero\" src=\"../img/iconos/print_disabled.png\" onclick=\"alert('No se puede imprimir la orden, debido a que no tiene un tipo de orden asignado');\"/>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		/*$Result1 = calcularMontosOrden($row['id_orden']);
		if ($Result1[0] != true) {
			return $objResponse->alert($Result1[1]);
		}*/
		
		$imgEdicion = sprintf("<img class=\"puntero\" onclick=\"xajax_verificarBloqueoSolicitud(".$row['id_orden'].", ".$row['id_empresa'].", 3);\" src=\"../img/iconos/pencil.png\"/>");
		$imgSaltar = sprintf("<img class=\"puntero\" onclick=\"xajax_verificarAprobacion(%s);\" src=\"../img/iconos/time_go.png\"/>", $row['id_orden']);

		if ($row["id_tipo_orden"] == 5) { //SIN ASIGNAR O TIPO ORDEN == 6QUE COLOQUE DESABILITA
			$imgEstadoView = $imgEstadoViewDisabled;
			$imgAprobarOrden = $imgAprobarOrdenDisabled;
			$imgEstadoConvertirAPresupuesto = $imgEstadoConvertirAPresupuestoDisabled;
			$imgRetrabajo = $imgRetrabajoDisabled;
			$imgImprimir = $imgImprimirOrdenDisabled;
		} else {
				
			$imgEstadoViewHistoricoVale = sprintf("<img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Orden\" onclick=\"verVentana('sa_imprimir_historico_vale.php?valBusq=".$row['id_vale_salida']."|2|3', 1000, 500);\"/>");
			
			$imgEstadoViewHistoricoOrden = sprintf("<img class=\"puntero\" src=\"../img/iconos/ico_view.png\" onclick=\"verVentana('sa_factura_venta_pdf.php?cod=".$row['idFactura']."', 1000, 500);\"/>");
				
			$imgGenerarFactura = sprintf("<img class=\"puntero\" src=\"../img/iconos/ico_importar.gif\" title=\"Facturar\" onclick=\"window.open('cjrs_facturacion_devolucion_servicios_form.php?doc_type=3&id=%s&ide=%s&acc=2&cons=%s','_self');\"/>",
				$row['id_orden'],
				$row['id_empresa'],
				$cons);
				
			if ($row['retrabajo'] == 1) { //DESABILITE GENERAR PRESUPUESTO, APROBACION, Y LOS OTROS...
				$imgRetrabajo = $imgRetrabajoDisabled;
			} else {
				$imgRetrabajo = sprintf("<img class=\"puntero\" src=\"../img/iconos/retrabajo.png\" onclick=\"window.open('cjrs_facturacion_devolucion_servicios_form.php?doc_type=2&id=%s&ide=%s&acc=3&cons=%s&ret=%s','_self');\" src=\"../img/iconos/ico_view.png\"/>",
					$row['id_orden'],
					$row['id_empresa'],
					$cons,
					$_GET['acc']);
			}
						
			if ($row['id_tipo_estado'] == 5) { //Trabajo Finalizado: MAYCOL TIENE EN EL MAGNETOPLANO UN BOTON PARA COLOCAR LOS TRABAJOS EN FINALIZADO...
				$imgEdicion = $imgEdicionDisabled;
				$imgAprobarOrden = $imgAprobarOrdenDisabled;
				$imgEstadoConvertirAPresupuesto = $imgEstadoConvertirAPresupuestoDisabled;
							
				if ($row['id_empleado_aprobacion_factura'] != NULL || $row['id_empleado_aprobacion_factura'] != 0) {
					$imgEstadoAprobControlCalidad = $imgEstadoAprobDisabled;
				} else {
					$imgEstadoAprobControlCalidad = sprintf("<img class=\"puntero\" id='aprobarOrden".$row['id_orden']."' onclick=\"xajax_aprobarOrdenForm(%s, $('sobregiro').value, 'aprb_fin_ord', '%s', %s,'%s','%s','%s',%s);\" src=\"../img/iconos/aprob_mecanico.png\" title=\"Aprobacion Control Calidad\"/>",
						$row['id_orden'],
						$row['id_empleado_aprobacion_factura'],
						$pageNum,
						$campOrd,
						$tpOrd,
						$valBusq,
						$maxRows);
					$imgEstadoAprobHora = "<img class=\"puntero\" onclick=\"abrirHora('".$row['id_orden']."');\" src=\"../img/iconos/time_add.png\" title=\"Asignar Fecha y Hora de Entrega\"/>";
					$imgCambioCliente = "<img class=\"puntero\" onclick=\"abrirCliente('".$row['id_orden']."');\" src=\"../img/iconos/user_suit.png\" title=\"Cambiar Cliente\"/>";
					$imgStatus= sprintf("<img class=\"puntero\" onclick=\"abrirStatus(%s);\" src=\"../img/iconos/page_refresh.png\" alt='Cambiar Estado' title='Cambiar Estado Orden'/>", $row['id_orden']);
				}
			}
			
			if ($row['id_tipo_estado'] == 4) { // DETENIDO
				$imgEstadoAprobMec = $imgEstadoAprobDisabled;
				$imgEstadoAprobControlCalidad = $imgEstadoAprobDisabled;
				$imgEdicion = $imgEdicionDisabled;
				$imgAprobarOrden = $imgAprobarOrdenDisabled;
				$imgEstadoConvertirAPresupuesto = $imgEstadoConvertirAPresupuestoDisabled;
			}
			
			if ($row['id_tipo_estado'] == 1 || $row['id_tipo_estado'] == 2 || $row['id_tipo_estado'] == 3) { //ABIERTO
				$imgEstadoAprobMec = $imgEstadoAprobDisabled;
				$imgEstadoAprobControlCalidad = $imgEstadoAprobDisabled;
				
				if ($row['id_estado_orden'] == 2 || $row['id_estado_orden'] == 22) { //|| $row['id_estado_orden'] == 3 || $row['id_estado_orden'] == 19
					$imgAprobarOrden = $imgAprobarOrdenDisabled;
					$imgEstadoConvertirAPresupuesto = $imgEstadoConvertirAPresupuestoDisabled;
					if($row['id_estado_orden'] == 2)
						$imgEdicion = $imgEdicionDisabled;
				} else {
					$imgAprobarOrden = sprintf("<img class=\"puntero\" onclick=\"xajax_verificarBloqueoSolicitud(".$row['id_orden'].", ".$row['id_empresa'].", 4);\" src=\"../img/iconos/aprobar_presup.png\" title=\"Aprobar Orden\"/>",
						$row['id_orden'],
						$row['id_empresa']);
					
					$imgEstadoConvertirAPresupuesto = sprintf("<img class=\"puntero\" onclick=\"window.open('cjrs_facturacion_devolucion_servicios_form.php?doc_type=4&id=%s&ide=%s&acc=2','_self');\" src=\"../img/iconos/generarPresupuesto.png\" title=\"Generar presupuesto\"/>",
						$row['id_orden'],
						$row['id_empresa']);
				}
			}
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['tiempo_orden_registro']))."</td>";
			$htmlTb .= "<td align=\"right\" idordenoculta=\"".$row['id_orden']."\" idempresaoculta=\"".$row['id_empresa']."\">".$row['numero_orden']."</td>";
			$htmlTb .= "<td align=\"right\" idrecepcionoculto =\"".$row['id_recepcion']."\">".$row['numeracion_recepcion']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['placa']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['chasis']."</td>";
			$htmlTb .= "<td align=\"right\" idordenocultaretrabajo =\"".$row['id_orden_retrabajo']."\" >".$row['numero_orden_retrabajo']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente']." ".$row['apellido_cliente'])."</td>";
			$htmlTb .= ($row['nombre_tipo_orden'] == 'CREDITO') ? "<td align=\"center\" class=\"divMsjAlerta\">" : "<td align=\"center\" class=\"divMsjInfo\">";
			$htmlTb .= $row['nombre_tipo_orden'];
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'],2,".",",")."</td>";
			$htmlTb .= sprintf("<td align='center' class=\"puntero\">"."<img src=\"../img/iconos/ico_return.png\" title=\"Devolver Orden\" onclick=\"if (confirm('Desea devolver la orden ".$row['numero_orden']."?') == true){
				$('divDevolver').style.display = '';
				centrarDiv($('divDevolver'));
				$('txtNumOrden').value = '".$row['numero_orden']."';
				$('hddIdOrden').value = '".$row['id_orden']."';
			}\"/>"."</td>");
			$htmlTb .= sprintf("<td align='center' id=\"imgGenerarFactura\" class=\"noprint\">%s</td>", $imgGenerarFactura);
		$html .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_cj_rs.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoOrden","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function verificarClave($valForm){
	$objResponse = new xajaxResponse();
		
	$queryClave = sprintf("SELECT 
		sa_claves.clave AS contrasena,
		pg_empleado.id_empleado
	FROM pg_empleado
		INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
		INNER JOIN sa_claves ON (pg_empleado.id_empleado = sa_claves.id_empleado)
	WHERE sa_claves.modulo = 'ac_sa_orden_list'
		AND pg_usuario.id_usuario = %s",valTpDato($_SESSION['idUsuarioSysGts'],'int'));
	$rsClave = mysql_query($queryClave);
	if (!$rsClave) return $objResponse->alert(mysql_error()."\n\nLINE: "._LINE_);
	
	if (mysql_num_rows($rsClave)){
		$rowClave = mysql_fetch_array($rsClave);
		if ($rowClave['contrasena'] == md5($valForm['txtClave'])){
			$objResponse->script("xajax_devolverOrden('".$valForm['hddIdOrden']."')");
			$objResponse->script("$('divFlotante').style.display = 'none';");
		} else
			$objResponse->alert(utf8_encode("Contraseña Errada."));
	} else {
		$objResponse->alert("No tiene permiso para realizar esta accion");
		$objResponse->script("$('divFlotante').style.display = 'none';");
	}
	return $objResponse;
}

function validarAperturaCaja(){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$fecha = date("Y-m-d");
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura WHERE statusAperturaCaja <> 0 AND fechaAperturaCaja NOT LIKE %s AND id_empresa = %s",
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCierreCaja);
	
	if (mysql_num_rows($rsCierreCaja) > 0){
		$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
		$fechaUltimaApertura = date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja']));
		$objResponse->alert("Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
		
	} else {
	
		//VERIFICA SI LA CAJA TIENE APERTURA
		//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
		$queryVerificarApertura = sprintf("SELECT * FROM sa_iv_apertura WHERE fechaAperturaCaja = %s AND statusAperturaCaja <> 0 AND id_empresa = %s",
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL:".$queryVerificarApertura);
		
		if (mysql_num_rows($rsVerificarApertura) == 0){
			$objResponse->alert("Esta caja no tiene apertura.");
		}
	}
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"devolverOrden");
$xajax->register(XAJAX_FUNCTION,"listadoOrdenes");
$xajax->register(XAJAX_FUNCTION,"verificarClave");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");

/*function calcularMontosOrden($idOrden){
	
// RECALCULA LOS MONTOS DE LA ORDEN
	$sqlDetOrden = sprintf("SELECT SUM(precio_unitario * cantidad) AS valor FROM sa_det_orden_articulo
	WHERE id_orden = %s
		AND aprobado = 1
		AND estado_articulo <> 'DEVUELTO';",
		$idOrden);
	$rsDetOrden = mysql_query($sqlDetOrden);
	if (!$rsDetOrden) return array(false, mysql_error()."\n\nLine: ".__LINE__);
	$rowDetOrden = mysql_fetch_array($rsDetOrden);
	$valor = $rowDetOrden['valor'];

	$sqlDesc = sprintf("SELECT * FROM sa_orden
	WHERE id_orden = %s;",
		$idOrden);
	$rsDesc = mysql_query($sqlDesc);
	if (!$rsDesc) return array(false, mysql_error()."\n\nLine: ".__LINE__);
	$rowDesc = mysql_fetch_array($rsDesc);
	
	// SI NO TIENE IVA LE ASIGNA EL IVA DE VENTA QUE EXISTE POR DEFECTO
	if ($rowDesc['subtotal'] > 0 && $rowDesc['iva'] == 0) {
		$queryIva = sprintf("SELECT * FROM pg_iva WHERE estado = 1 AND (tipo = 6) ORDER BY iva");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\n\nLine: ".__LINE__);
		$rowIva = mysql_fetch_assoc($rsIva);
		
		$rowDesc['idIva'] = $rowIva['idIva'];
		$rowDesc['iva'] = $rowIva['iva'];
	}
	
	$Desc = ($rowDesc['porcentaje_descuento'] * $valor) / 100;
	$valorConDesc = $valor - $Desc;
	
	$sqlTemp = sprintf("SELECT 
		id_modo,
		precio_tempario_tipo_orden,
		ut,
		base_ut_precio,
		precio
	FROM sa_det_orden_tempario 
	WHERE id_orden = %s
		AND estado_tempario <> 'DEVUELTO';",$idOrden);
	$rsTemp = mysql_query($sqlTemp);
	if (!$rsTemp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$valorTemp = 0;
	
	while ($rowTemp = mysql_fetch_assoc($rsTemp)) {
		$monto = 0;
		if($rowTemp['id_modo'] == 1){
			$monto = ($rowTemp['precio_tempario_tipo_orden'] * $rowTemp['ut']) / $rowTemp['base_ut_precio'];
			$valorTemp = $valorTemp + $monto;
		} else if($rowTemp['id_modo'] == 2){		
			$valorTemp = $valorTemp + $rowTemp['precio'];
			}
	}
	
	//$sqlTemp = sprintf("SELECT
//	(CASE id_modo
//		WHEN 1 THEN
//			SUM((precio_tempario_tipo_orden * ut) / base_ut_precio)
//		WHEN 2 THEN
//			SUM(precio)
//	END) AS valorTemp
//	FROM sa_det_orden_tempario
//	WHERE id_orden = %s AND estado_tempario <> 'DEVUELTO';",
//		$idOrden);
//	$rsTemp = mysql_query($sqlTemp);
//	if (!$rsTemp) return array(false, mysql_error()."\n\nLine: ".__LINE__);
//	$rowTemp = mysql_fetch_array($rsTemp);
//	$valorTemp = $rowTemp['valorTemp'];

	$sqlTOT = sprintf("SELECT SUM(orden_tot.monto_subtotal)as monto_subtotalTot, 
		orden.id_orden,
		orden.id_tipo_orden
	FROM sa_orden_tot orden_tot
		INNER JOIN sa_orden orden ON (orden_tot.id_orden_servicio = orden.id_orden)
	WHERE orden.id_orden = %s
	GROUP BY orden.id_orden, orden.id_tipo_orden;",
		$idOrden);
	$rsTOT = mysql_query($sqlTOT);
	if (!$rsTOT) return array(false, mysql_error()."\n\nLine: ".__LINE__);
	$rowTOT = mysql_fetch_array($rsTOT);
	
	$queryPorcentajeTot = sprintf("SELECT
		sa_tipo_orden.id_tipo_orden,
		sa_tipo_orden.porcentaje_tot
	FROM sa_tipo_orden
	WHERE sa_tipo_orden.id_tipo_orden = %s;",
		valTpDato($rowTOT['id_tipo_orden'],"int"));
	$rsPorcentajeTot = mysql_query($queryPorcentajeTot);
	if (!$rsPorcentajeTot) return array(false, mysql_error()."\n\nLine: ".__LINE__);
	$rowPorcentajeTot = mysql_fetch_assoc($rsPorcentajeTot);

	$valorDetTOT = $rowTOT['monto_subtotalTot'] + (($rowPorcentajeTot['porcentaje_tot'] * $rowTOT['monto_subtotalTot']) / 100);

	$sqlNota = sprintf("SELECT SUM(precio) AS precio FROM sa_det_orden_notas
	WHERE id_orden = %s 
		AND aprobado= 1;",
		$idOrden);
	$rsNota = mysql_query($sqlNota);
	if (!$rsNota) return array(false, mysql_error()."\n\nLine: ".__LINE__);
	$rowNota = mysql_fetch_array($rsNota);

	$totalConDesc = $rowNota['precio'] + $valorDetTOT + $valorTemp + $valorConDesc;
	$totalSinDesc = $rowNota['precio'] + $valorDetTOT + $valorTemp + $valor;
	$totalIva = ($totalConDesc * $rowDesc['iva'])/100;

	$sqlActualizaOrden = "UPDATE sa_orden SET
		subtotal = ".valTpDato($totalSinDesc, "double").",
		base_imponible = ".valTpDato($totalConDesc, "double").",
		idIva = ".valTpDato($rowDesc['idIva'], "int").",
		iva = ".valTpDato($rowDesc['iva'], "double").",
		subtotal_iva = ".valTpDato($totalIva, "double").",
		subtotal_descuento = ".valTpDato($Desc, "double")."
	WHERE id_orden = ".$idOrden;
	$rsActualizaOrden = mysql_query($sqlActualizaOrden);
	if (!$rsActualizaOrden) return array(false, mysql_error()."\n\nLine: ".__LINE__);
	
	return array(true, $sqlActualizaOrden);}*/
?>
